<?php
/**
 * Datos: origen de cada venta, foto diaria de indicadores, diccionario de
 * métricas y exportación anonimizada (Prevsoul → Datos).
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------
 * Utilidades
 * ---------------------------------------------------------------- */
function ps_dt_hash( $v ) {
	$v = strtolower( trim( (string) $v ) );
	return $v ? 'P' . strtoupper( substr( hash_hmac( 'sha256', $v, wp_salt( 'auth' ) ), 0, 9 ) ) : '';
}

function ps_dt_es_plan( $pid ) {
	if ( has_term( 'planes-dentales', 'product_cat', $pid ) ) {
		return true;
	}
	$p = wc_get_product( $pid );
	return $p && function_exists( 'ps_parent_slugs' ) && in_array( 'planes-dentales', ps_parent_slugs( $p ), true );
}

function ps_dt_tipo_plan( $pid, $name ) {
	if ( false !== mb_stripos( $name, 'familiar' ) ) {
		return 'Familiar';
	}
	if ( false !== mb_stripos( $name, 'colectiv' ) ) {
		return 'Colectivo';
	}
	return 'Individual';
}

/** Pedido de prueba: cupón PRUEBA100 o marcado como demostración. */
function ps_dt_es_prueba( $o ) {
	foreach ( $o->get_coupon_codes() as $c ) {
		if ( 'prueba100' === strtolower( $c ) ) {
			return true;
		}
	}
	return (bool) $o->get_meta( '_ps_demo' );
}

function ps_dt_ordenes( $desde, $hasta, $status = array( 'processing', 'completed' ) ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	$args = array( 'limit' => -1, 'status' => $status, 'type' => 'shop_order' );
	if ( array_intersect( $status, array( 'processing', 'completed' ) ) ) {
		$args['date_paid'] = $desde . '...' . $hasta;
	} else {
		$args['date_created'] = $desde . '...' . $hasta;
	}
	return array_filter( wc_get_orders( $args ), function ( $o ) { return ! ps_dt_es_prueba( $o ); } );
}

/* ------------------------------------------------------------------
 * 1. Origen de cada venta (primer contacto del visitante)
 * ---------------------------------------------------------------- */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() || wp_doing_ajax() || headers_sent() ) {
			return;
		}
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( preg_match( '/bot|crawl|spider|preview/', $ua ) ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$sala = function_exists( 'ps_get' ) ? (string) wp_parse_url( (string) ps_get( 'sala_url' ), PHP_URL_PATH ) : '';
		if ( $sala && '/' !== $sala && 0 === strpos( $path, $sala ) && empty( $_COOKIE['ps_cons'] ) ) {
			setcookie( 'ps_cons', '1', array( 'expires' => time() + 90 * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		}
		if ( ! empty( $_COOKIE['ps_src'] ) ) {
			return;
		}
		$g   = function ( $k ) { return isset( $_GET[ $k ] ) ? substr( sanitize_text_field( wp_unslash( $_GET[ $k ] ) ), 0, 80 ) : ''; }; // phpcs:ignore WordPress.Security.NonceVerification
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), PHP_URL_HOST ) : '';
		if ( $ref && wp_parse_url( home_url(), PHP_URL_HOST ) === $ref ) {
			$ref = '';
		}
		$d = array(
			's'  => $g( 'utm_source' ),
			'm'  => $g( 'utm_medium' ),
			'c'  => $g( 'utm_campaign' ),
			'g'  => $g( 'gclid' ) ? 1 : 0,
			'f'  => $g( 'fbclid' ) ? 1 : 0,
			'r'  => substr( $ref, 0, 80 ),
			'l'  => substr( $path, 0, 120 ),
			't'  => time(),
		);
		setcookie( 'ps_src', base64_encode( wp_json_encode( $d ) ), array( 'expires' => time() + 90 * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		$_COOKIE['ps_src'] = base64_encode( wp_json_encode( $d ) );
	}
);

function ps_dt_canal( $d ) {
	$s = strtolower( isset( $d['s'] ) ? $d['s'] : '' );
	$m = strtolower( isset( $d['m'] ) ? $d['m'] : '' );
	$r = strtolower( isset( $d['r'] ) ? $d['r'] : '' );
	if ( preg_match( '/instagram|facebook|^ig$|^fb$|meta/', $s ) || ! empty( $d['f'] ) || preg_match( '/instagram|facebook/', $r ) ) {
		return ( preg_match( '/cpc|paid|ads|pago/', $m ) || ( $s && ! $m ) || ! empty( $d['f'] ) ) && $s ? 'Meta Ads' : 'Instagram y Facebook (orgánico)';
	}
	if ( ! empty( $d['g'] ) || ( 'google' === $s && preg_match( '/cpc|paid|ads/', $m ) ) ) {
		return 'Google Ads';
	}
	if ( preg_match( '/google|bing|yahoo|duckduckgo|ecosia/', $r ) || 'google' === $s ) {
		return 'Buscadores (orgánico)';
	}
	if ( preg_match( '/mail|correo|newsletter/', $s . $m ) ) {
		return 'Correo';
	}
	if ( preg_match( '/whatsapp|wa\.me/', $s . $r ) ) {
		return 'WhatsApp';
	}
	if ( $s ) {
		return 'Campaña: ' . $s;
	}
	if ( $r ) {
		return 'Otros sitios';
	}
	return 'Directo';
}

add_action(
	'woocommerce_checkout_create_order',
	function ( $order ) {
		$d = array();
		if ( ! empty( $_COOKIE['ps_src'] ) ) {
			$j = json_decode( base64_decode( sanitize_text_field( wp_unslash( $_COOKIE['ps_src'] ) ) ), true );
			$d = is_array( $j ) ? $j : array();
		}
		$order->update_meta_data( '_ps_origen', $d );
		$order->update_meta_data( '_ps_canal', ps_dt_canal( $d ) );
		if ( ! empty( $_COOKIE['ps_cons'] ) ) {
			$order->update_meta_data( '_ps_paso_consulta', 1 );
		}
	}
);

/* ------------------------------------------------------------------
 * 2. Foto diaria de indicadores
 * ---------------------------------------------------------------- */
function ps_dt_activos() {
	$ord  = ps_dt_ordenes( gmdate( 'Y-m-d', time() - 31 * DAY_IN_SECONDS ), gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) );
	$keys = array();
	foreach ( $ord as $o ) {
		foreach ( $o->get_items() as $it ) {
			$pid = $it->get_product_id();
			if ( ! ps_dt_es_plan( $pid ) ) {
				continue;
			}
			$k          = ps_dt_hash( $o->get_billing_email() ) . ':' . $pid;
			$keys[ $k ] = array( ps_dt_tipo_plan( $pid, $it->get_name() ), (float) $it->get_total() + (float) $it->get_total_tax() );
		}
	}
	return $keys;
}

function ps_dt_foto( $fecha = null ) {
	$tz    = wp_timezone();
	$fecha = $fecha ? $fecha : ( new DateTime( 'yesterday', $tz ) )->format( 'Y-m-d' );
	$ini   = ( new DateTime( $fecha . ' 00:00:00', $tz ) )->getTimestamp();
	$fin   = $ini + DAY_IN_SECONDS - 1;
	$f     = array( 'ventas_n' => 0, 'ventas_monto' => 0, 'prod' => array() );
	foreach ( ps_dt_ordenes( $ini, $fin ) as $o ) {
		$f['ventas_n']++;
		$f['ventas_monto'] += (float) $o->get_total();
		foreach ( $o->get_items() as $it ) {
			$pid               = $it->get_product_id();
			$f['prod'][ $pid ] = ( isset( $f['prod'][ $pid ] ) ? $f['prod'][ $pid ] : 0 ) + $it->get_quantity();
		}
	}
	$sin            = ps_dt_ordenes( $ini, $fin, array( 'pending', 'failed', 'cancelled' ) );
	$f['sinpago_n'] = count( $sin );
	$f['sinpago_m'] = array_sum( array_map( function ( $o ) { return (float) $o->get_total(); }, $sin ) );
	$act            = ps_dt_activos();
	$prev           = get_option( 'ps_dt_keys', null );
	$f['activos']   = array( 'Individual' => 0, 'Familiar' => 0, 'Colectivo' => 0 );
	$f['mrr']       = 0;
	foreach ( $act as $a ) {
		$f['activos'][ $a[0] ]++;
		$f['mrr'] += $a[1];
	}
	$f['altas'] = is_array( $prev ) ? count( array_diff( array_keys( $act ), $prev ) ) : 0;
	$f['bajas'] = is_array( $prev ) ? count( array_diff( $prev, array_keys( $act ) ) ) : 0;
	update_option( 'ps_dt_keys', array_keys( $act ), false );
	$f['aten'] = array();
	foreach ( array_keys( defined( 'PS_ORDEN_STATES' ) ? PS_ORDEN_STATES : array() ) as $e ) {
		$f['aten'][ $e ] = count( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => $e ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$f['resenas'] = (int) get_comments( array( 'type' => 'review', 'status' => 'approve', 'count' => true ) );
	$fotos        = get_option( 'ps_dt_fotos', array() );
	$fotos        = is_array( $fotos ) ? $fotos : array();
	$fotos[ $fecha ] = $f;
	ksort( $fotos );
	update_option( 'ps_dt_fotos', array_slice( $fotos, -800, null, true ), false );
	return $f;
}

add_action( 'ps_dt_foto_diaria', function () { ps_dt_foto(); } );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ps_dt_foto_diaria' ) ) {
			$t = new DateTime( 'tomorrow 03:00', wp_timezone() );
			wp_schedule_event( $t->getTimestamp(), 'daily', 'ps_dt_foto_diaria' );
		}
	}
);

/* Atenciones por agendar con más de 7 días. */
function ps_dt_aten_atrasadas() {
	return count( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => 'recibida', 'date_query' => array( array( 'before' => '7 days ago' ) ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
}

/* ------------------------------------------------------------------
 * 3. Diccionario de métricas
 * ---------------------------------------------------------------- */
function ps_dt_dicc() {
	$def = array(
		'Plan activo'          => 'Plan con una cuota pagada en los últimos 31 días. Se cuenta por paciente y plan.',
		'Alta'                 => 'Plan que está activo hoy y no lo estaba en la foto anterior.',
		'Baja'                 => 'Plan que estaba activo en la foto anterior y hoy ya no (sin cuota pagada en 31 días).',
		'MRR'                  => 'Ingreso recurrente mensual: suma de la última cuota de cada plan activo.',
		'Venta'                => 'Pedido pagado (procesando o completado). Excluye pedidos con cupón PRUEBA100 y datos de demostración.',
		'Pagos sin completar'  => 'Pedidos creados que quedaron pendientes, fallidos o cancelados.',
		'Conversión'           => 'Pedidos pagados ÷ sesiones del período (requiere Analytics, etapa 2).',
		'Canal'                => 'Primer origen del paciente (UTM, referente o directo) guardado al comprar.',
		'Atención atrasada'    => 'Atención por agendar con más de 7 días desde la compra.',
	);
	$s = get_option( 'ps_dt_dicc', array() );
	return array_merge( $def, is_array( $s ) ? $s : array() );
}

add_action(
	'admin_post_ps_dt_dicc',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_dt_dicc' );
		$in  = isset( $_POST['d'] ) && is_array( $_POST['d'] ) ? wp_unslash( $_POST['d'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$out = array();
		foreach ( $in as $k => $v ) {
			$out[ sanitize_text_field( $k ) ] = sanitize_textarea_field( $v );
		}
		update_option( 'ps_dt_dicc', $out, false );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-datos&tab=dicc&ok=1' ) );
		exit;
	}
);

/* ------------------------------------------------------------------
 * 4. Exportación anonimizada (CSV)
 * ---------------------------------------------------------------- */
add_action(
	'admin_post_ps_dt_export',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_dt_export' );
		$tipo = isset( $_GET['tipo'] ) ? sanitize_key( $_GET['tipo'] ) : '';
		$rows = array();
		if ( 'pedidos' === $tipo || 'planes' === $tipo ) {
			$rows[] = array( 'fecha_pago', 'pedido', 'paciente', 'comuna', 'producto', 'tipo', 'cantidad', 'monto_linea', 'total_pedido', 'cupon', 'canal', 'paso_consulta', 'integrantes' );
			foreach ( ps_dt_ordenes( '2000-01-01', gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ) as $o ) {
				foreach ( $o->get_items() as $it ) {
					$pid  = $it->get_product_id();
					$plan = ps_dt_es_plan( $pid );
					if ( 'planes' === $tipo && ! $plan ) {
						continue;
					}
					$rows[] = array(
						$o->get_date_paid() ? $o->get_date_paid()->date_i18n( 'Y-m-d H:i' ) : '',
						ps_dt_hash( 'o' . $o->get_id() ),
						ps_dt_hash( $o->get_billing_email() ),
						$o->get_billing_city(),
						$it->get_name(),
						$plan ? 'Plan ' . ps_dt_tipo_plan( $pid, $it->get_name() ) : ( has_term( 'packs-dentales', 'product_cat', $pid ) ? 'Pack' : 'Atención' ),
						$it->get_quantity(),
						round( (float) $it->get_total() + (float) $it->get_total_tax() ),
						round( (float) $o->get_total() ),
						implode( ' ', $o->get_coupon_codes() ),
						$o->get_meta( '_ps_canal' ) ? $o->get_meta( '_ps_canal' ) : 'Sin dato',
						$o->get_meta( '_ps_paso_consulta' ) ? 'sí' : 'no',
						(int) $o->get_meta( '_ps_integrantes' ),
					);
				}
			}
		} elseif ( 'fotos' === $tipo ) {
			$rows[] = array( 'fecha', 'ventas', 'monto_ventas', 'planes_individual', 'planes_familiar', 'planes_colectivo', 'mrr', 'altas', 'bajas', 'pagos_sin_completar', 'monto_sin_completar', 'resenas' );
			foreach ( (array) get_option( 'ps_dt_fotos', array() ) as $d => $f ) {
				$rows[] = array( $d, $f['ventas_n'], round( $f['ventas_monto'] ), $f['activos']['Individual'], $f['activos']['Familiar'], $f['activos']['Colectivo'], round( $f['mrr'] ), $f['altas'], $f['bajas'], $f['sinpago_n'], round( $f['sinpago_m'] ), $f['resenas'] );
			}
		} elseif ( 'atenciones' === $tipo ) {
			$rows[] = array( 'fecha', 'atencion', 'prestador', 'producto', 'estado', 'monto' );
			foreach ( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1 ) ) as $p ) {
				$monto  = get_post_meta( $p->ID, '_ps_monto', true );
				$rows[] = array( get_the_date( 'Y-m-d', $p ), ps_dt_hash( 'a' . $p->ID ), get_the_title( (int) get_post_meta( $p->ID, '_ps_prestador', true ) ), (string) get_post_meta( $p->ID, '_ps_producto', true ), get_post_meta( $p->ID, '_ps_estado', true ), round( (float) ( '' !== $monto ? $monto : get_post_meta( $p->ID, '_ps_pago', true ) ) ) );
			}
		} else {
			wp_die( 'Tipo no válido' );
		}
		$log   = (array) get_option( 'ps_dt_log', array() );
		$log[] = array( time(), wp_get_current_user()->user_login, $tipo, count( $rows ) - 1 );
		update_option( 'ps_dt_log', array_slice( $log, -100 ), false );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=prevsoul-' . $tipo . '-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		foreach ( $rows as $r ) {
			fputcsv( $out, $r, ';' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/* ------------------------------------------------------------------
 * 4b. Conexión para Google Sheets / Looker Studio (solo agregados)
 * ---------------------------------------------------------------- */
function ps_dt_bi_rows( $t ) {
	$rows = array();
	if ( 'fotos' === $t ) {
		$rows[] = array( 'fecha', 'ventas', 'monto_ventas', 'planes_individual', 'planes_familiar', 'planes_colectivo', 'planes_activos', 'mrr', 'altas', 'bajas', 'pagos_sin_completar', 'resenas' );
		foreach ( (array) get_option( 'ps_dt_fotos', array() ) as $d => $f ) {
			$rows[] = array( $d, $f['ventas_n'], round( $f['ventas_monto'] ), $f['activos']['Individual'], $f['activos']['Familiar'], $f['activos']['Colectivo'], array_sum( $f['activos'] ), round( $f['mrr'] ), $f['altas'], $f['bajas'], $f['sinpago_n'], $f['resenas'] );
		}
		return $rows;
	}
	$agg = array();
	foreach ( ps_dt_ordenes( '2000-01-01', gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ) as $o ) {
		$mes = $o->get_date_paid() ? $o->get_date_paid()->date_i18n( 'Y-m-01' ) : '';
		if ( 'canales' === $t ) {
			$k = $mes . '|' . ( $o->get_meta( '_ps_canal' ) ? $o->get_meta( '_ps_canal' ) : 'Sin dato' );
			if ( ! isset( $agg[ $k ] ) ) {
				$agg[ $k ] = array( 0, 0, 0 );
			}
			$agg[ $k ][0]++;
			$agg[ $k ][1] += (float) $o->get_total();
			$agg[ $k ][2] += $o->get_meta( '_ps_paso_consulta' ) ? 1 : 0;
		} else {
			foreach ( $o->get_items() as $it ) {
				$pid  = $it->get_product_id();
				$tipo = ps_dt_es_plan( $pid ) ? 'Plan' : ( has_term( 'packs-dentales', 'product_cat', $pid ) ? 'Pack' : 'Atención' );
				$k    = $mes . '|' . $it->get_name() . '|' . $tipo;
				if ( ! isset( $agg[ $k ] ) ) {
					$agg[ $k ] = array( 0, 0 );
				}
				$agg[ $k ][0] += $it->get_quantity();
				$agg[ $k ][1] += (float) $it->get_total() + (float) $it->get_total_tax();
			}
		}
	}
	$rows[] = 'canales' === $t ? array( 'mes', 'canal', 'pedidos', 'ingresos', 'paso_por_consulta' ) : array( 'mes', 'producto', 'tipo', 'cantidad', 'ingresos' );
	foreach ( $agg as $k => $v ) {
		$rows[] = array_merge( explode( '|', $k ), array_map( 'round', $v ) );
	}
	return $rows;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'prevsoul/v1',
			'/bi',
			array(
				'methods'             => 'GET',
				'permission_callback' => function ( $r ) {
					$k = (string) get_option( 'ps_dt_bi_key', '' );
					return $k && hash_equals( $k, (string) $r->get_param( 'k' ) );
				},
				'callback'            => function ( $r ) {
					$t = sanitize_key( (string) $r->get_param( 't' ) );
					if ( ! in_array( $t, array( 'fotos', 'canales', 'productos' ), true ) ) {
						return new WP_Error( 'ps_bi', 'Tipo no válido', array( 'status' => 400 ) );
					}
					nocache_headers();
					header( 'Content-Type: text/csv; charset=utf-8' );
					$out = fopen( 'php://output', 'w' );
					foreach ( ps_dt_bi_rows( $t ) as $row ) {
						fputcsv( $out, $row );
					}
					fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					exit;
				},
			)
		);
	}
);

add_action(
	'admin_post_ps_dt_bikey',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_dt_bikey' );
		update_option( 'ps_dt_bi_key', wp_generate_password( 32, false ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-datos&tab=looker&ok=1' ) );
		exit;
	}
);

/* ------------------------------------------------------------------
 * 5. Pantalla Prevsoul → Datos
 * ---------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Datos', 'Datos', 'manage_options', 'ps-datos', 'ps_dt_page' );
	},
	19
);

function ps_dt_money( $n ) {
	return '$' . number_format( (float) $n, 0, ',', '.' );
}

function ps_dt_chart( $fotos ) {
	$f = array_slice( $fotos, -60, null, true );
	if ( count( $f ) < 2 ) {
		return '<p class="ps-dt-muted">El gráfico se dibuja desde la segunda foto diaria (cada noche a las 03:00).</p>';
	}
	$series = array( 'Familiar' => '#1D5FD1', 'Individual' => '#D9727F', 'Colectivo' => '#4A5B73' );
	$max    = 1;
	foreach ( $f as $x ) {
		$max = max( $max, max( $x['activos'] ) );
	}
	$w = 640;
	$h = 160;
	$n = count( $f ) - 1;
	$o = '<svg viewBox="0 0 ' . $w . ' ' . ( $h + 24 ) . '" style="width:100%;max-width:760px;height:auto" role="img" aria-label="Planes activos por tipo">';
	for ( $g = 0; $g <= 4; $g++ ) {
		$y  = 6 + ( $h - 12 ) * $g / 4;
		$o .= '<line x1="30" x2="' . $w . '" y1="' . $y . '" y2="' . $y . '" stroke="#e3e8f0" stroke-width="1"/><text x="24" y="' . ( $y + 4 ) . '" font-size="11" fill="#4A5B73" text-anchor="end">' . round( $max * ( 4 - $g ) / 4 ) . '</text>';
	}
	foreach ( $series as $k => $c ) {
		$pts = array();
		$i   = 0;
		foreach ( $f as $x ) {
			$pts[] = round( 30 + ( $w - 40 ) * $i / $n, 1 ) . ',' . round( 6 + ( $h - 12 ) * ( 1 - $x['activos'][ $k ] / $max ), 1 );
			$i++;
		}
		$o .= '<polyline fill="none" stroke="' . $c . '" stroke-width="2" points="' . implode( ' ', $pts ) . '"/>';
	}
	$keys = array_keys( $f );
	$o   .= '<text x="30" y="' . ( $h + 18 ) . '" font-size="11" fill="#4A5B73">' . esc_html( $keys[0] ) . '</text><text x="' . $w . '" y="' . ( $h + 18 ) . '" font-size="11" fill="#4A5B73" text-anchor="end">' . esc_html( end( $keys ) ) . '</text></svg>';
	$o   .= '<p class="ps-dt-leg">';
	foreach ( $series as $k => $c ) {
		$o .= '<span><i style="background:' . $c . '"></i>' . esc_html( $k ) . '</span>';
	}
	return $o . '</p>';
}

function ps_dt_page() {
	$fotos = get_option( 'ps_dt_fotos', array() );
	if ( ! $fotos ) {
		ps_dt_foto();
		$fotos = get_option( 'ps_dt_fotos', array() );
	}
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'res'; // phpcs:ignore WordPress.Security.NonceVerification
	$dias = isset( $_GET['dias'] ) ? max( 7, min( 365, absint( $_GET['dias'] ) ) ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
	$base = admin_url( 'admin.php?page=ps-datos' );
	$tabs = array( 'res' => 'Resumen', 'origen' => 'Origen de ventas', 'dicc' => 'Diccionario', 'exp' => 'Exportar', 'looker' => 'Looker Studio' );
	echo '<div class="wrap ps-dt"><h1>Datos</h1>';
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Guardado.</p></div>';
	}
	$last = $fotos ? array_key_last( $fotos ) : '';
	echo '<p class="ps-dt-muted">Última foto: ' . esc_html( $last ? $last : '—' ) . ' · Se toma sola cada noche a las 03:00. Excluye pedidos de prueba (PRUEBA100).</p>';
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $l ) {
		echo '<a class="nav-tab' . ( $tab === $k ? ' nav-tab-active' : '' ) . '" href="' . esc_url( add_query_arg( array( 'tab' => $k, 'dias' => $dias ), $base ) ) . '">' . esc_html( $l ) . '</a>';
	}
	echo '</nav><div class="ps-dt-body">';

	if ( 'res' === $tab ) {
		echo '<p>Período: ';
		foreach ( array( 30 => 'Últimos 30 días', 90 => 'Últimos 90 días', 365 => 'Último año' ) as $d => $l ) {
			echo '<a class="button' . ( $dias === $d ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( array( 'tab' => 'res', 'dias' => $d ), $base ) ) . '">' . esc_html( $l ) . '</a> ';
		}
		echo '</p>';
		$tz    = wp_timezone();
		$desde = ( new DateTime( '-' . $dias . ' days', $tz ) )->getTimestamp();
		$ord   = ps_dt_ordenes( $desde, time() );
		$prev  = ps_dt_ordenes( $desde - $dias * DAY_IN_SECONDS, $desde - 1 );
		$m     = array_sum( array_map( function ( $o ) { return (float) $o->get_total(); }, $ord ) );
		$mp    = array_sum( array_map( function ( $o ) { return (float) $o->get_total(); }, $prev ) );
		$act   = ps_dt_activos();
		$mrr   = array_sum( array_map( function ( $a ) { return $a[1]; }, $act ) );
		$altas = 0;
		$bajas = 0;
		foreach ( $fotos as $d => $f ) {
			if ( strtotime( $d ) >= $desde ) {
				$altas += $f['altas'];
				$bajas += $f['bajas'];
			}
		}
		$old    = null;
		foreach ( $fotos as $d => $f ) {
			if ( strtotime( $d ) <= $desde ) {
				$old = $f;
			}
		}
		$sin    = ps_dt_ordenes( $desde, time(), array( 'pending', 'failed', 'cancelled' ) );
		$sinm   = array_sum( array_map( function ( $o ) { return (float) $o->get_total(); }, $sin ) );
		$pend   = count( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => 'recibida' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$var    = function ( $a, $b ) {
			if ( ! $b ) {
				return '<i>sin período anterior</i>';
			}
			$p = round( ( $a - $b ) / $b * 100 );
			return '<i class="' . ( $p >= 0 ? 'up' : 'dn' ) . '">' . ( $p >= 0 ? '+' : '' ) . $p . ' % vs período anterior</i>';
		};
		$k = array(
			array( 'Ingreso recurrente (MRR)', ps_dt_money( $mrr ), $old ? $var( $mrr, $old['mrr'] ) : '<i>sin foto anterior</i>' ),
			array( 'Planes activos', count( $act ), '<i>+' . $altas . ' altas · ' . $bajas . ' bajas</i>' ),
			array( 'Ventas del período', ps_dt_money( $m ), $var( $m, $mp ) ),
			array( 'Pedidos pagados', count( $ord ), '<i>Ticket promedio ' . ( $ord ? ps_dt_money( $m / count( $ord ) ) : '—' ) . '</i>' ),
			array( 'Pagos sin completar', count( $sin ), '<i>' . ps_dt_money( $sinm ) . ' sin pagar</i>' ),
			array( 'Atenciones por agendar', $pend, '<i>' . ps_dt_aten_atrasadas() . ' con más de 7 días</i>' ),
		);
		echo '<div class="ps-dt-k">';
		foreach ( $k as $x ) {
			echo '<div><p>' . esc_html( $x[0] ) . '</p><b>' . esc_html( $x[1] ) . '</b>' . wp_kses_post( $x[2] ) . '</div>';
		}
		echo '</div><h2>Planes activos por tipo</h2>' . ps_dt_chart( $fotos ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p class="ps-dt-muted">Conversión: disponible al conectar Analytics con Looker Studio (etapa 2).</p>';
	} elseif ( 'origen' === $tab ) {
		$ord = ps_dt_ordenes( '2000-01-01', gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) );
		$c   = array();
		$con = 0;
		foreach ( $ord as $o ) {
			$k = $o->get_meta( '_ps_canal' ) ? $o->get_meta( '_ps_canal' ) : 'Sin dato (antes de medir)';
			if ( ! isset( $c[ $k ] ) ) {
				$c[ $k ] = array( 0, 0 );
			}
			$c[ $k ][0]++;
			$c[ $k ][1] += (float) $o->get_total();
			$con        += $o->get_meta( '_ps_paso_consulta' ) ? 1 : 0;
		}
		uasort( $c, function ( $a, $b ) { return $b[1] <=> $a[1]; } );
		$mx = $c ? max( array_map( function ( $x ) { return $x[1]; }, $c ) ) : 1;
		echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>Canal</th><th>Pedidos</th><th>Ingresos</th><th style="width:35%">Peso</th></tr></thead><tbody>';
		if ( ! $c ) {
			echo '<tr><td colspan="4">Aún no hay ventas pagadas.</td></tr>';
		}
		foreach ( $c as $k => $x ) {
			echo '<tr><td>' . esc_html( $k ) . '</td><td>' . (int) $x[0] . '</td><td>' . esc_html( ps_dt_money( $x[1] ) ) . '</td><td><div class="ps-dt-bar" style="width:' . ( $mx ? round( $x[1] / $mx * 100 ) : 0 ) . '%"></div></td></tr>';
		}
		echo '</tbody></table><p>Pedidos de pacientes que pasaron por la Consulta online antes de comprar: <strong>' . (int) $con . '</strong></p>';
		echo '<p class="ps-dt-muted">Se guarda el primer origen del visitante (UTM, gclid, fbclid o sitio de referencia) durante 90 días. Para medir campañas, usa enlaces con utm_source, utm_medium y utm_campaign.</p>';
	} elseif ( 'dicc' === $tab ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_dt_dicc">';
		wp_nonce_field( 'ps_dt_dicc' );
		echo '<table class="form-table">';
		foreach ( ps_dt_dicc() as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td><textarea name="d[' . esc_attr( $k ) . ']" rows="2" style="width:100%;max-width:640px">' . esc_textarea( $v ) . '</textarea></td></tr>';
		}
		echo '</table>';
		submit_button( 'Guardar diccionario' );
		echo '</form><p class="ps-dt-muted">Estas definiciones las usan esta pantalla, las exportaciones y los agentes de datos.</p>';
	} elseif ( 'looker' === $tab ) {
		$key = (string) get_option( 'ps_dt_bi_key', '' );
		echo '<p>Enlaces privados con datos <strong>solo agregados</strong> (por día, mes, canal o producto; sin pacientes) para Google Sheets y Looker Studio. Quien tenga un enlace puede ver esos totales: no los compartas. Si se filtran, genera una clave nueva y los anteriores dejan de funcionar.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_dt_bikey">';
		wp_nonce_field( 'ps_dt_bikey' );
		submit_button( $key ? 'Generar clave nueva' : 'Generar clave', $key ? 'secondary' : 'primary', 'submit', false );
		echo '</form>';
		if ( $key ) {
			echo '<table class="widefat striped" style="max-width:980px;margin-top:14px"><thead><tr><th>Hoja</th><th>Fórmula para Google Sheets (celda A1)</th></tr></thead><tbody>';
			foreach ( array( 'fotos' => 'Fotos diarias', 'canales' => 'Canales por mes', 'productos' => 'Productos por mes' ) as $t => $l ) {
				$u = add_query_arg( array( 'k' => $key, 't' => $t ), rest_url( 'prevsoul/v1/bi' ) );
				echo '<tr><td>' . esc_html( $l ) . '</td><td><input type="text" readonly onclick="this.select()" style="width:100%;font-family:monospace;font-size:12px" value="' . esc_attr( '=IMPORTDATA("' . $u . '")' ) . '"></td></tr>';
			}
			echo '</tbody></table><p class="ps-dt-muted">Google Sheets actualiza estas hojas cada hora aprox. Looker Studio se conecta a la planilla y a Google Analytics (sesiones y conversión).</p>';
		}
	} else {
		echo '<p>Archivos CSV anonimizados (separados por punto y coma, se abren en Excel). El paciente aparece como código; no incluyen nombre, RUT, correo, teléfono ni datos clínicos.</p><p>';
		foreach ( array( 'pedidos' => 'Pedidos', 'planes' => 'Planes', 'fotos' => 'Fotos diarias', 'atenciones' => 'Atenciones de prestadores' ) as $t => $l ) {
			echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ps_dt_export&tipo=' . $t ), 'ps_dt_export' ) ) . '"><span class="dashicons dashicons-download" style="vertical-align:-4px"></span> ' . esc_html( $l ) . '</a> ';
		}
		echo '</p><h2>Registro de descargas</h2><table class="widefat striped" style="max-width:640px"><thead><tr><th>Fecha</th><th>Usuario</th><th>Archivo</th><th>Filas</th></tr></thead><tbody>';
		$log = array_reverse( (array) get_option( 'ps_dt_log', array() ) );
		if ( ! $log ) {
			echo '<tr><td colspan="4">Sin descargas aún.</td></tr>';
		}
		foreach ( array_slice( $log, 0, 20 ) as $l ) {
			echo '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i', $l[0] ) ) . '</td><td>' . esc_html( $l[1] ) . '</td><td>' . esc_html( $l[2] ) . '</td><td>' . (int) $l[3] . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div></div><style>.ps-dt-muted{color:#646970}.ps-dt-body{margin-top:16px}.ps-dt-k{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;max-width:1100px;margin:0 0 20px}.ps-dt-k>div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 14px}.ps-dt-k p{margin:0;color:#646970}.ps-dt-k b{display:block;font-size:22px;margin:4px 0;color:#0B2545}.ps-dt-k i{font-style:normal;font-size:12px;color:#646970}.ps-dt-k i.up{color:#167A64}.ps-dt-k i.dn{color:#b32d2e}.ps-dt-bar{height:8px;border-radius:4px;background:#1D5FD1}.ps-dt-leg span{margin-right:14px}.ps-dt-leg i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:5px;vertical-align:-1px}</style>';
}
