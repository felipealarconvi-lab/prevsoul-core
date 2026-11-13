<?php
/**
 * Backoffice del buscador: qué busca la gente, qué no encuentra y qué termina en consulta o compra.
 * No guarda datos personales: solo el texto buscado, lo que entendió y si hubo consulta o compra.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_bus_cookie_js() {
	if ( empty( $GLOBALS['ps_bus_id'] ) ) {
		return '';
	}
	return '<script>document.cookie="ps_bus=' . esc_js( $GLOBALS['ps_bus_id'] ) . ';path=/;max-age=2592000;SameSite=Lax";</script>';
}

function ps_csv_safe( $v ) {
	$v = (string) $v;
	return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
}
function ps_bus_cookie() {
	return isset( $_COOKIE['ps_bus'] ) ? preg_replace( '/[^a-f0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE['ps_bus'] ) ) ) : '';
}

function ps_bus_mark( $id, $key ) {
	if ( ! $id ) {
		return;
	}
	$log = get_option( 'ps_search_log', array() );
	foreach ( $log as $i => $e ) {
		if ( isset( $e['id'] ) && $e['id'] === $id ) {
			$log[ $i ][ $key ] = 1;
			update_option( 'ps_search_log', $log, false );
			return;
		}
	}
}

/* Consulta online después de buscar. */
add_action(
	'wp_insert_post',
	function ( $post_id, $post, $update ) {
		if ( ! $update && 'ps_precita' === $post->post_type ) {
			ps_bus_mark( ps_bus_cookie(), 'c' );
		}
	},
	10,
	3
);

/* Compra después de buscar. */
function ps_bus_order_tag( $order ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
	$id    = ps_bus_cookie();
	if ( $order && $id ) {
		$order->update_meta_data( '_ps_bus', $id );
		$order->save();
	}
}
add_action( 'woocommerce_checkout_order_processed', 'ps_bus_order_tag' );
add_action( 'woocommerce_store_api_checkout_order_processed', 'ps_bus_order_tag' );
function ps_bus_order_paid( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_ps_bus_ok' ) ) {
		return;
	}
	$id = $order->get_meta( '_ps_bus' );
	if ( $id ) {
		ps_bus_mark( $id, 'p' );
		$order->update_meta_data( '_ps_bus_ok', 1 );
		$order->save();
	}
}
add_action( 'woocommerce_order_status_processing', 'ps_bus_order_paid', 20 );
add_action( 'woocommerce_order_status_completed', 'ps_bus_order_paid', 20 );

/* -------------------------------------------------------------------------
 * Página de administración
 * ---------------------------------------------------------------------- */
function ps_periodos() {
	return array( 1 => 'Hoy', 7 => '7 días', 30 => '30 días', 90 => '90 días' );
}
function ps_periodo_actual() {
	$d = isset( $_GET['dias'] ) ? (int) $_GET['dias'] : 30; // phpcs:ignore WordPress.Security.NonceVerification
	return isset( ps_periodos()[ $d ] ) ? $d : 30;
}
function ps_periodo_inicio( $d ) {
	return 1 === $d ? strtotime( 'today', current_time( 'timestamp' ) ) - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) : time() - $d * DAY_IN_SECONDS;
}
function ps_periodo_pills( $page ) {
	$cur = ps_periodo_actual();
	$o   = '<div class="ps-ad-pills">';
	foreach ( ps_periodos() as $d => $l ) {
		$o .= '<a class="' . ( $d === $cur ? 'on' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $page . '&dias=' . $d ) ) . '">' . esc_html( $l ) . '</a>';
	}
	return $o . '</div>';
}
function ps_admin_css() {
	return '<style>
.ps-ad{max-width:1180px;font-family:"Plus Jakarta Sans",-apple-system,system-ui,sans-serif;color:#0B2347}
.ps-ad h1{font-weight:800;font-size:24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.ps-ad-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:8px 0 16px}.ps-ad-top h1{flex:1;margin:0}
.ps-ad-pills{display:flex;gap:6px;flex-wrap:wrap}.ps-ad-pills a{border:1px solid #D7E1EE;background:#fff;border-radius:99px;padding:5px 13px;font-weight:700;color:#33415C;text-decoration:none;font-size:13px}.ps-ad-pills a.on{background:#0B2545;color:#fff;border-color:#0B2545}
.ps-ad-k{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:12px}
.ps-ad-k>div,.ps-ad-c{background:#fff;border:1px solid #D7E1EE;border-radius:14px;padding:14px 16px;min-width:0;box-sizing:border-box}
.ps-ad-k span{display:block;font-size:12px;color:#4A5B73}.ps-ad-k b{display:block;font-size:26px;margin:4px 0;font-weight:800}.ps-ad-k em{font-style:normal;font-size:12px;font-weight:700;color:#157347}.ps-ad-k em.bad{color:#B42318}.ps-ad-k em.warn{color:#8A5300}
.ps-ad-g{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:12px;margin-bottom:12px}
.ps-ad-c h2{font-size:15px;font-weight:800;margin:0 0 10px}
.ps-ad-hb div{display:grid;grid-template-columns:minmax(110px,170px) minmax(0,1fr) 44px;gap:10px;align-items:center;font-size:13px;margin:7px 0}.ps-ad-hb i{display:block;height:14px;background:#1D5FD1;border-radius:0 4px 4px 0;min-width:2px}
.ps-ad table{width:100%;border-collapse:collapse;font-size:13px}.ps-ad td,.ps-ad th{padding:7px 6px;border-bottom:1px solid #EEF1F5;text-align:left;vertical-align:middle}.ps-ad th{color:#4A5B73;font-weight:700}
.ps-ad .num{text-align:right;font-variant-numeric:tabular-nums}
.ps-ad-empty{color:#4A5B73;font-size:13px;margin:4px 0}
.ps-ad-as{display:flex;gap:6px;align-items:center}.ps-ad-as select{max-width:150px}
.ps-ad-note{font-size:12px;color:#4A5B73;margin:8px 0 0}
@media (max-width:960px){.ps-ad-k{grid-template-columns:repeat(2,minmax(0,1fr))}.ps-ad-g{grid-template-columns:1fr}}
</style>';
}
function ps_hbars( $rows ) {
	if ( ! $rows ) {
		return '<p class="ps-ad-empty">Aún no hay datos en este periodo.</p>';
	}
	$max = max( $rows );
	$o   = '<div class="ps-ad-hb">';
	foreach ( $rows as $k => $v ) {
		$o .= '<div><span>' . esc_html( $k ) . '</span><i style="width:' . round( 100 * $v / max( 1, $max ) ) . '%"></i><b class="num">' . (int) $v . '</b></div>';
	}
	return $o . '</div>';
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Buscador', 'Buscador', 'manage_options', 'ps-buscador', 'ps_buscador_page' );
	},
	20
);

function ps_bus_entries( $since ) {
	return array_values( array_filter( (array) get_option( 'ps_search_log', array() ), function ( $e ) use ( $since ) { return isset( $e['t'] ) && $e['t'] >= $since; } ) );
}

function ps_buscador_page() {
	$d       = ps_periodo_actual();
	$since   = ps_periodo_inicio( $d );
	$e       = ps_bus_entries( $since );
	$span    = time() - $since;
	$prev    = array_filter( (array) get_option( 'ps_search_log', array() ), function ( $x ) use ( $since, $span ) { return isset( $x['t'] ) && $x['t'] < $since && $x['t'] >= $since - $span; } );
	$n       = count( $e );
	$ok      = count( array_filter( $e, function ( $x ) { return '' !== ( $x['need'] ?? '' ); } ) );
	$miss    = $n - $ok;
	$buy     = count( array_filter( $e, function ( $x ) { return ! empty( $x['p'] ); } ) );
	$cons    = count( array_filter( $e, function ( $x ) { return ! empty( $x['c'] ); } ) );
	$delta   = count( $prev ) ? round( 100 * ( $n - count( $prev ) ) / count( $prev ) ) : null;
	$ignore  = (array) get_option( 'ps_bus_ignore', array() );
	$needs   = array();
	$by      = array();
	$nores   = array();
	$tags    = array();
	foreach ( $e as $x ) {
		$need = $x['need'] ?? '';
		if ( '' !== $need ) {
			$needs[ $need ] = ( $needs[ $need ] ?? 0 ) + 1;
			$by[ $need ]    = array(
				'n' => ( $by[ $need ]['n'] ?? 0 ) + 1,
				'c' => ( $by[ $need ]['c'] ?? 0 ) + ( empty( $x['c'] ) ? 0 : 1 ),
				'p' => ( $by[ $need ]['p'] ?? 0 ) + ( empty( $x['p'] ) ? 0 : 1 ),
			);
		} else {
			$k = trim( ps_norm( $x['q'] ) );
			if ( '' !== $k && ! in_array( $k, $ignore, true ) ) {
				$nores[ $k ] = array( 'q' => $x['q'], 'n' => ( $nores[ $k ]['n'] ?? 0 ) + 1 );
			}
		}
		if ( ! empty( $x['tag'] ) ) {
			$tags[ $x['q'] ] = ( $tags[ $x['q'] ] ?? 0 ) + 1;
		}
	}
	arsort( $needs );
	arsort( $tags );
	uasort( $by, function ( $a, $b ) { return $b['p'] <=> $a['p'] ?: $b['n'] <=> $a['n']; } );
	uasort( $nores, function ( $a, $b ) { return $b['n'] <=> $a['n']; } );
	$rules = ps_rules();
	echo ps_admin_css(); // phpcs:ignore
	echo '<div class="wrap ps-ad"><div class="ps-ad-top"><h1>Lo que busca la gente</h1>' . ps_periodo_pills( 'ps-buscador' ) . '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ps_bus_csv&dias=' . $d ), 'ps_bus_csv' ) ) . '">Exportar CSV</a></div>'; // phpcs:ignore
	if ( isset( $_GET['ps_ok'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>Listo: el buscador ya reconoce esa frase.</p></div>';
	}
	echo '<div class="ps-ad-k">';
	echo '<div><span>Búsquedas</span><b>' . (int) $n . '</b>' . ( null === $delta ? '<em>&nbsp;</em>' : '<em class="' . ( $delta < 0 ? 'bad' : '' ) . '">' . ( $delta >= 0 ? '▲ ' : '▼ ' ) . abs( $delta ) . '% vs periodo anterior</em>' ) . '</div>';
	echo '<div><span>Entendidas</span><b>' . ( $n ? round( 100 * $ok / $n ) : 0 ) . '%</b><em>reconoció la necesidad</em></div>';
	echo '<div><span>Sin resultado</span><b style="color:#B42318">' . (int) $miss . '</b><em class="bad">oportunidades</em></div>';
	echo '<div><span>Terminan en compra</span><b>' . ( $n ? number_format( 100 * $buy / $n, 1, ',', '' ) : '0' ) . '%</b><em>' . (int) $buy . ' de ' . (int) $n . ' · ' . (int) $cons . ' fueron a consulta</em></div>';
	echo '</div><div class="ps-ad-g"><div class="ps-ad-c"><h2>Necesidades más buscadas</h2>' . ps_hbars( array_slice( $needs, 0, 8, true ) ) . '</div>'; // phpcs:ignore
	echo '<div class="ps-ad-c"><h2>Sin resultado: enséñale al buscador</h2>';
	if ( $nores ) {
		echo '<table><tr><th>Escribieron</th><th class="num">Veces</th><th>Unir a</th></tr>';
		foreach ( array_slice( $nores, 0, 12, true ) as $k => $r ) {
			echo '<tr><td>«' . esc_html( $r['q'] ) . '»</td><td class="num">' . (int) $r['n'] . '</td><td><form class="ps-ad-as" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_bus_asignar"><input type="hidden" name="dias" value="' . (int) $d . '"><input type="hidden" name="frase" value="' . esc_attr( $k ) . '">' . wp_nonce_field( 'ps_bus_asignar', '_wpnonce', true, false ) . '<select name="need" aria-label="Necesidad"><option value="">Elegir…</option>';
			foreach ( $rules as $i => $rule ) {
				echo '<option value="' . (int) $i . '">' . esc_html( $rule['need'] ) . '</option>';
			}
			echo '<option value="-1">No es relevante (ocultar)</option></select><button class="button button-small">Asignar</button></form></td></tr>';
		}
		echo '</table><p class="ps-ad-note">«Asignar» agrega la frase a esa necesidad en las reglas del buscador, y la próxima vez sí encuentra.</p>';
	} else {
		echo '<p class="ps-ad-empty">Ninguna en este periodo.</p>';
	}
	echo '</div></div><div class="ps-ad-g"><div class="ps-ad-c"><h2>Intención de compra por necesidad</h2>';
	if ( $by ) {
		echo '<table><tr><th>Necesidad</th><th class="num">Búsquedas</th><th class="num">Consulta</th><th class="num">Compra</th></tr>';
		foreach ( array_slice( $by, 0, 10, true ) as $k => $r ) {
			echo '<tr><td>' . esc_html( $k ) . '</td><td class="num">' . (int) $r['n'] . '</td><td class="num">' . (int) $r['c'] . '</td><td class="num"><b>' . (int) $r['p'] . '</b></td></tr>';
		}
		echo '</table>';
	} else {
		echo '<p class="ps-ad-empty">Aún no hay datos en este periodo.</p>';
	}
	echo '</div><div class="ps-ad-c"><h2>Tags más tocados</h2>' . ps_hbars( array_slice( $tags, 0, 8, true ) ) . '</div></div>'; // phpcs:ignore
	echo '<p class="ps-ad-note">Sin datos personales: se guarda el texto buscado, lo que entendió el buscador, si se tocó un tag y si la persona terminó en consulta online o compra (en los 30 días siguientes, en el mismo navegador). Se conservan las últimas 3.000 búsquedas.</p></div>';
}

add_action(
	'admin_post_ps_bus_asignar',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_bus_asignar' ) ) {
			wp_die( 'Sin permiso' );
		}
		$frase = sanitize_text_field( wp_unslash( $_POST['frase'] ?? '' ) );
		$need  = isset( $_POST['need'] ) && '' !== $_POST['need'] ? (int) $_POST['need'] : null;
		$d     = (int) ( $_POST['dias'] ?? 30 );
		if ( '' !== $frase && null !== $need ) {
			if ( -1 === $need ) {
				$ig   = (array) get_option( 'ps_bus_ignore', array() );
				$ig[] = $frase;
				update_option( 'ps_bus_ignore', array_values( array_unique( $ig ) ), false );
			} else {
				$opts  = get_option( 'ps_settings', array() );
				$lines = preg_split( '/\r?\n/', (string) ps_get( 'rules' ) );
				$idx   = -1;
				foreach ( $lines as $li => $line ) {
					$p = array_map( 'trim', explode( '|', $line ) );
					if ( count( $p ) >= 2 && $p[0] ) {
						$idx++;
						if ( $idx === $need ) {
							$p[1]         = $p[1] . ', ' . str_replace( array( '|', ',' ), ' ', $frase );
							$lines[ $li ] = implode( ' | ', $p );
							break;
						}
					}
				}
				$opts['rules'] = implode( "\n", $lines );
				update_option( 'ps_settings', $opts );
				// Las búsquedas anteriores con esa frase pasan a contarse como entendidas.
				$rules = ps_rules();
				$log   = get_option( 'ps_search_log', array() );
				foreach ( $log as $i => $x ) {
					if ( '' === ( $x['need'] ?? '' ) && trim( ps_norm( $x['q'] ) ) === $frase && isset( $rules[ $need ] ) ) {
						$log[ $i ]['need'] = $rules[ $need ]['need'];
					}
				}
				update_option( 'ps_search_log', $log, false );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ps-buscador&ps_ok=1&dias=' . $d ) );
		exit;
	}
);

add_action(
	'admin_post_ps_bus_csv',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_bus_csv' ) ) {
			wp_die( 'Sin permiso' );
		}
		$d = ps_periodo_actual();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=prevsoul-busquedas-' . gmdate( 'Y-m-d' ) . '.csv' );
		$f = fopen( 'php://output', 'w' );
		fwrite( $f, "\xEF\xBB\xBF" );
		fputcsv( $f, array( 'Fecha', 'Búsqueda', 'Necesidad entendida', 'Tag', 'Fue a consulta', 'Compró' ), ';' );
		foreach ( ps_bus_entries( ps_periodo_inicio( $d ) ) as $x ) {
			fputcsv( $f, array( wp_date( 'Y-m-d H:i', $x['t'] ), ps_csv_safe( $x['q'] ), ps_csv_safe( $x['need'] ?? '' ), empty( $x['tag'] ) ? '' : 'sí', empty( $x['c'] ) ? '' : 'sí', empty( $x['p'] ) ? '' : 'sí' ), ';' );
		}
		fclose( $f );
		exit;
	}
);
