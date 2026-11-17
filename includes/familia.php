<?php
/**
 * 2.17.0 · Plan Familiar «Prevsoul cuida a quien cuida» y meta personal («bien-ser», concepto interno).
 *
 * - Precio por integrante: titular al precio del Plan Individual + cada adicional al % de Armador → Ajustes.
 * - Ficha del Plan Familiar: «Tu control va primero», contador de integrantes, segundo responsable
 *   y bloque «Nosotros nos acordamos por ti».
 * - Mi cuenta → «Mi plan»: integrantes, meta personal de cada uno y estado de sus controles.
 * - Recordatorio mensual (uno por familia) y correo de seguimiento a los 6 meses (1 a 5).
 * - «Para qué te sirve» en cada producto y tarjetas nuevas en el Armador.
 *
 * Privacidad: nombres, metas y respuestas quedan solo en el sitio; nunca se envían a Meta ni a Clarity.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PS_FAM_MAX = 8;

/* -------------------------------------------------------------------------
 * Productos y precio
 * ---------------------------------------------------------------------- */
function ps_fam_pid_by( $slug, $word ) {
	$p = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $p ) {
		return (int) $p->ID;
	}
	$q = get_posts( array( 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 's' => $word, 'post_status' => 'publish' ) );
	return $q ? (int) $q[0] : 0;
}
function ps_fam_pid() {
	static $id = null;
	if ( null === $id ) {
		$id = (int) apply_filters( 'ps_fam_pid', ps_fam_pid_by( 'plan-preventivo-familiar', 'Plan Preventivo Familiar' ) );
	}
	return $id;
}
function ps_ind_pid() {
	static $id = null;
	if ( null === $id ) {
		$id = (int) apply_filters( 'ps_ind_pid', ps_fam_pid_by( 'plan-preventivo-individual', 'Plan Preventivo Individual' ) );
	}
	return $id;
}
function ps_fam_titular() {
	$p = ps_ind_pid() ? wc_get_product( ps_ind_pid() ) : null;
	$t = $p ? (float) $p->get_price() : 0;
	return $t > 0 ? $t : 9990;
}
function ps_fam_pct() {
	$c = function_exists( 'ps_arm_cfg' ) ? ps_arm_cfg() : array();
	$v = isset( $c['cFam'] ) ? (float) $c['cFam'] : 75;
	return max( 10, min( 100, $v ) );
}
function ps_fam_adicional() {
	return round( ps_fam_titular() * ps_fam_pct() / 100 / 10 ) * 10;
}
function ps_fam_total( $n ) {
	$n = max( 2, min( PS_FAM_MAX, (int) $n ) );
	return ps_fam_titular() + ps_fam_adicional() * ( $n - 1 );
}
function ps_es_familiar( $pid ) {
	return $pid && (int) $pid === ps_fam_pid();
}
function ps_es_plan_pid( $pid ) {
	if ( ! $pid ) {
		return false;
	}
	if ( in_array( (int) $pid, array( ps_fam_pid(), ps_ind_pid() ), true ) ) {
		return true;
	}
	$p = wc_get_product( $pid );
	return $p && function_exists( 'ps_parent_slugs' ) && in_array( 'planes-dentales', ps_parent_slugs( $p ), true );
}

/* Precio que se muestra en la tienda: desde 2 integrantes. */
add_filter(
	'woocommerce_get_price_html',
	function ( $html, $product ) {
		if ( ! ps_es_familiar( $product->get_id() ) ) {
			return $html;
		}
		$pre = is_product() ? '' : 'Desde ';
		$n   = is_product() ? 3 : 2;
		return '<span class="ps-fam-price">' . $pre . '<span class="ps-fam-total">' . wc_price( ps_fam_total( $n ) ) . '</span></span>';
	},
	20,
	2
);

/* El texto «Hasta [N] integrantes» de la descripción se muestra con el máximo real. */
add_filter(
	'the_content',
	function ( $c ) {
		return ( function_exists( 'is_product' ) && is_product() && ps_es_familiar( get_the_ID() ) ) ? str_replace( '[N]', (string) PS_FAM_MAX, $c ) : $c;
	}
);

/* -------------------------------------------------------------------------
 * Ficha del Plan Familiar
 * ---------------------------------------------------------------------- */
add_action(
	'woocommerce_single_product_summary',
	function () {
		if ( ! ps_es_familiar( get_the_ID() ) ) {
			return;
		}
		echo '<div class="ps-fam-star"><i>1</i><div><b>Tu control va primero</b><span>Quien organiza la salud de la casa también cuenta: tu control y limpieza vienen incluidos desde el primer mes.</span></div></div>';
	},
	9
);

add_action(
	'woocommerce_before_add_to_cart_button',
	function () {
		if ( ! ps_es_familiar( get_the_ID() ) ) {
			return;
		}
		$t = ps_fam_titular();
		$a = ps_fam_adicional();
		echo '<div class="ps-fam-box"><h3>¿Cuántos son en tu familia?</h3><div class="ps-fam-cnt"><button type="button" class="ps-fam-m" aria-label="Quitar integrante">−</button><input type="number" name="ps_integrantes" class="ps-fam-n" value="2" min="2" max="' . (int) PS_FAM_MAX . '" aria-label="Integrantes"><button type="button" class="ps-fam-p" aria-label="Sumar integrante">+</button><span>integrantes, contándote a ti</span></div>';
		echo '<p class="ps-fam-brk" data-t="' . esc_attr( $t ) . '" data-a="' . esc_attr( $a ) . '"></p><p class="ps-fam-tot"><span class="ps-fam-tot-v"></span> <small>/ mes</small></p><p class="ps-fam-help">Sin letra chica: puedes sumar o quitar integrantes cuando quieras.</p></div>';
		echo '<div class="ps-fam-box"><label class="ps-fam-ck"><input type="checkbox" name="ps_r2" value="1" class="ps-fam-r2"> <span><b>Agregar un segundo responsable</b><br><span class="ps-fam-sub">Tu pareja u otra persona de la casa también recibe los recordatorios.</span></span></label><div class="ps-fam-r2f" hidden><input type="text" name="ps_r2_nombre" placeholder="Nombre" maxlength="60" autocomplete="off"><input type="email" name="ps_r2_email" placeholder="Correo" maxlength="100" autocomplete="off"></div></div>';
		echo '<p class="ps-fam-help">Después de contratar te pediremos solo el nombre y la edad de cada integrante. Nunca pedimos datos de salud en el formulario.</p>';
		echo '<script>(function(){var b=document.querySelector(".ps-fam-brk");if(!b)return;var T=+b.dataset.t,A=+b.dataset.a,n=document.querySelector(".ps-fam-n"),v=document.querySelector(".ps-fam-tot-v");function f(x){return "$"+Math.round(x).toLocaleString("es-CL")}function up(){var k=Math.max(2,Math.min(' . (int) PS_FAM_MAX . ',parseInt(n.value,10)||2));n.value=k;b.innerHTML="Tú (titular): "+f(T)+"<br>"+(k-1)+" integrante"+(k>2?"s adicionales":" adicional")+": "+f(A)+(k>2?" c/u":"");v.textContent=f(T+A*(k-1));document.querySelectorAll(".summary .ps-fam-total").forEach(function(e){e.textContent=f(T+A*(k-1))})}document.querySelector(".ps-fam-m").onclick=function(){n.value=+n.value-1;up()};document.querySelector(".ps-fam-p").onclick=function(){n.value=+n.value+1;up()};n.oninput=up;var c=document.querySelector(".ps-fam-r2"),r=document.querySelector(".ps-fam-r2f");c.onchange=function(){r.hidden=!c.checked};up()})();</script>';
	}
);

add_filter(
	'woocommerce_product_single_add_to_cart_text',
	function ( $t, $p = null ) {
		return ( $p && ps_es_familiar( $p->get_id() ) ) ? 'Contratar Plan Familiar' : $t;
	},
	10,
	2
);

add_action(
	'woocommerce_after_single_product_summary',
	function () {
		if ( ! ps_es_familiar( get_the_ID() ) ) {
			return;
		}
		$s = array(
			array( 'Tú primero', 'Agendamos tu control y limpieza en las primeras semanas.' ),
			array( 'Cada uno a su tiempo', 'Te avisamos cuándo le toca a cada integrante.' ),
			array( 'Un solo mensaje', 'Un recordatorio para toda la familia, no uno por persona.' ),
			array( 'Todos al día', 'En Mi cuenta → Mi plan ves quién está al día y a quién le toca.' ),
		);
		$o = '<section class="ps-fam-rem"><h2>Nosotros nos acordamos por ti</h2><p>Sin anotar nada. Sin perseguir a nadie.</p><ol>';
		foreach ( $s as $i => $x ) {
			$o .= '<li><em>' . ( $i + 1 ) . '</em><b>' . esc_html( $x[0] ) . '</b><span>' . esc_html( $x[1] ) . '</span></li>';
		}
		echo $o . '</ol></section>'; // phpcs:ignore WordPress.Security.EscapeOutput
	},
	5
);

/* -------------------------------------------------------------------------
 * Carro, precio y pedido
 * ---------------------------------------------------------------------- */
add_filter(
	'woocommerce_add_cart_item_data',
	function ( $data, $pid ) {
		if ( ! ps_es_familiar( $pid ) ) {
			return $data;
		}
		// phpcs:disable WordPress.Security.NonceVerification
		$n                     = isset( $_POST['ps_integrantes'] ) ? (int) $_POST['ps_integrantes'] : 3;
		$data['ps_integrantes'] = max( 2, min( PS_FAM_MAX, $n ) );
		if ( ! empty( $_POST['ps_r2'] ) ) {
			$data['ps_r2_nombre'] = sanitize_text_field( wp_unslash( $_POST['ps_r2_nombre'] ?? '' ) );
			$em                   = sanitize_email( wp_unslash( $_POST['ps_r2_email'] ?? '' ) );
			$data['ps_r2_email']  = is_email( $em ) ? $em : '';
		}
		// phpcs:enable
		$data['ps_fam_key'] = md5( wp_json_encode( $data ) . microtime() );
		return $data;
	},
	10,
	2
);

add_action(
	'woocommerce_before_calculate_totals',
	function ( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		foreach ( $cart->get_cart() as $item ) {
			if ( ! empty( $item['ps_integrantes'] ) && ps_es_familiar( $item['product_id'] ) ) {
				$item['data']->set_price( ps_fam_total( $item['ps_integrantes'] ) );
			}
		}
	},
	20
);

add_filter(
	'woocommerce_get_item_data',
	function ( $rows, $item ) {
		if ( ! empty( $item['ps_integrantes'] ) ) {
			$rows[] = array( 'key' => 'Integrantes', 'value' => (int) $item['ps_integrantes'] );
		}
		if ( ! empty( $item['ps_r2_nombre'] ) ) {
			$rows[] = array( 'key' => 'Segundo responsable', 'value' => $item['ps_r2_nombre'] );
		}
		return $rows;
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $line, $key, $values, $order ) {
		if ( empty( $values['ps_integrantes'] ) ) {
			return;
		}
		$line->add_meta_data( 'Integrantes', (int) $values['ps_integrantes'], true );
		if ( ! empty( $values['ps_r2_nombre'] ) ) {
			$line->add_meta_data( 'Segundo responsable', $values['ps_r2_nombre'], true );
		}
		$order->update_meta_data( '_ps_integrantes', (int) $values['ps_integrantes'] );
		$order->update_meta_data( '_ps_r2', array( 'nombre' => $values['ps_r2_nombre'] ?? '', 'email' => $values['ps_r2_email'] ?? '' ) );
	},
	10,
	4
);

/* -------------------------------------------------------------------------
 * Datos del plan (en el pedido)
 * ---------------------------------------------------------------------- */
/** Pedido de plan más reciente del usuario (o del correo). */
function ps_plan_order( $user_id ) {
	if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
		return null;
	}
	$orders = wc_get_orders( array( 'customer_id' => $user_id, 'status' => array( 'processing', 'completed' ), 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ) );
	foreach ( $orders as $o ) {
		foreach ( $o->get_items() as $it ) {
			if ( ps_es_plan_pid( $it->get_product_id() ) ) {
				return $o;
			}
		}
	}
	return null;
}
function ps_plan_es_familiar( $order ) {
	foreach ( $order->get_items() as $it ) {
		if ( ps_es_familiar( $it->get_product_id() ) ) {
			return true;
		}
	}
	return false;
}
/** Integrantes: [ [nombre, rel, edad, meta, ultimo(Y-m-d)] ], titular primero. */
function ps_plan_miembros( $order ) {
	$m = $order->get_meta( '_ps_miembros' );
	$m = is_array( $m ) ? $m : array();
	$n = ps_plan_es_familiar( $order ) ? max( 2, (int) $order->get_meta( '_ps_integrantes' ) ) : 1;
	if ( empty( $m[0] ) ) {
		$m[0] = array( 'nombre' => $order->get_billing_first_name(), 'rel' => 'titular', 'edad' => '', 'meta' => '', 'ultimo' => '' );
	}
	for ( $i = 1; $i < $n; $i++ ) {
		if ( empty( $m[ $i ] ) ) {
			$m[ $i ] = array( 'nombre' => '', 'rel' => '', 'edad' => '', 'meta' => '', 'ultimo' => '' );
		}
	}
	return array_slice( $m, 0, $n );
}
/** al-dia | pronto | agendar */
function ps_plan_estado( $ultimo ) {
	if ( ! $ultimo ) {
		return 'agendar';
	}
	$d = ( time() - strtotime( $ultimo ) ) / DAY_IN_SECONDS;
	return $d > 183 ? 'agendar' : ( $d > 150 ? 'pronto' : 'al-dia' );
}
function ps_plan_estado_txt( $e ) {
	return array( 'al-dia' => 'Al día', 'pronto' => 'Le toca pronto', 'agendar' => 'Por agendar' )[ $e ];
}

/* -------------------------------------------------------------------------
 * Mi cuenta → Mi plan
 * ---------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		add_rewrite_endpoint( 'mi-plan', EP_ROOT | EP_PAGES );
	}
);
add_action(
	'init',
	function () {
		if ( get_option( 'ps_miplan_flush' ) !== '1' ) {
			flush_rewrite_rules( false );
			update_option( 'ps_miplan_flush', '1' );
		}
	},
	99
);
add_filter(
	'woocommerce_get_query_vars',
	function ( $v ) {
		$v['mi-plan'] = 'mi-plan';
		return $v;
	}
);
add_filter(
	'woocommerce_account_menu_items',
	function ( $items ) {
		$new = array();
		foreach ( $items as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'dashboard' === $k ) {
				$new['mi-plan'] = 'Mi plan';
			}
		}
		return isset( $new['mi-plan'] ) ? $new : array( 'mi-plan' => 'Mi plan' ) + $items;
	}
);
add_filter(
	'woocommerce_endpoint_mi-plan_title',
	function () {
		return 'Mi plan';
	}
);
add_action(
	'woocommerce_account_mi-plan_endpoint',
	function () {
		$o = ps_plan_order( get_current_user_id() );
		if ( ! $o ) {
			$u = get_term_link( 'planes-dentales', 'product_cat' );
			echo '<div class="ps-mp"><p>Aún no tienes un plan Prevsoul.</p><p><a class="ps-btn" href="' . esc_url( is_wp_error( $u ) ? home_url( '/' ) : $u ) . '">Ver planes</a></p></div>';
			return;
		}
		$fam = ps_plan_es_familiar( $o );
		$m   = ps_plan_miembros( $o );
		$al  = 0;
		foreach ( $m as $x ) {
			$al += 'al-dia' === ps_plan_estado( $x['ultimo'] ) ? 1 : 0;
		}
		$r2  = $o->get_meta( '_ps_r2' );
		$out = '<div class="ps-mp">';
		if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$out .= '<div class="woocommerce-message">Guardado.</div>';
		}
		$out .= '<span class="ps-mp-chip">' . ( $fam ? 'Plan Familiar · ' . count( $m ) . ' integrantes' : 'Plan Individual' ) . '</span>';
		$out .= '<p class="ps-mp-lead">' . ( $fam ? 'Así va la salud dental de tu casa. Nosotros te avisamos cuando le toque a alguien.' : 'Tu plan y tu meta. Nosotros te avisamos cuando te toque.' ) . '</p>';
		$out .= '<div class="ps-mp-sum"><div><strong>' . (int) $al . ' de ' . count( $m ) . '</strong><span>al día con su control</span></div><div><strong>' . (int) ( count( $m ) - $al ) . '</strong><span>por agendar o pronto</span></div></div>';
		$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_miplan_save"><input type="hidden" name="o" value="' . (int) $o->get_id() . '">' . wp_nonce_field( 'ps_miplan', '_wpnonce', true, false );
		foreach ( $m as $i => $x ) {
			$e    = ps_plan_estado( $x['ultimo'] );
			$ini  = mb_strtoupper( mb_substr( $x['nombre'] ? $x['nombre'] : '?', 0, 2 ) );
			$out .= '<div class="ps-mp-mem' . ( 0 === $i ? ' tit' : '' ) . '"><span class="ps-mp-av">' . esc_html( $ini ) . '</span><div class="ps-mp-who">';
			$out .= '<div class="ps-mp-row"><input name="m[' . $i . '][nombre]" value="' . esc_attr( $x['nombre'] ) . '" placeholder="Nombre" maxlength="40" aria-label="Nombre">';
			if ( $i ) {
				$out .= '<input name="m[' . $i . '][rel]" value="' . esc_attr( $x['rel'] ) . '" placeholder="Relación (hijo, pareja…)" maxlength="30" aria-label="Relación">';
			} else {
				$out .= '<span class="ps-mp-tag">Titular · tu control va primero</span>';
			}
			$out .= '<input name="m[' . $i . '][edad]" value="' . esc_attr( $x['edad'] ) . '" placeholder="Edad" maxlength="3" inputmode="numeric" class="ps-mp-edad" aria-label="Edad"></div>';
			$out .= '<label class="ps-mp-meta"><span>' . ( 0 === $i ? 'Mi meta' : 'Su meta' ) . '</span><input name="m[' . $i . '][meta]" value="' . esc_attr( $x['meta'] ) . '" placeholder="Ej: comer de todo sin molestias" maxlength="120"></label>';
			$out .= '<span class="ps-mp-ult">' . ( $x['ultimo'] ? 'Último control: ' . esc_html( date_i18n( 'j M Y', strtotime( $x['ultimo'] ) ) ) : 'Sin control registrado aún' ) . '</span></div>';
			$out .= '<span class="ps-mp-st ' . esc_attr( $e ) . '">' . esc_html( ps_plan_estado_txt( $e ) ) . '</span></div>';
		}
		if ( $fam ) {
			$r2 = is_array( $r2 ) ? $r2 : array();
			$out .= '<div class="ps-mp-r2"><b>Segundo responsable</b><span>También recibe los recordatorios.</span><div class="ps-mp-row"><input name="r2[nombre]" value="' . esc_attr( $r2['nombre'] ?? '' ) . '" placeholder="Nombre" maxlength="60"><input type="email" name="r2[email]" value="' . esc_attr( $r2['email'] ?? '' ) . '" placeholder="Correo" maxlength="100"></div></div>';
		}
		$out .= '<p><button class="ps-btn" type="submit">Guardar</button></p><p class="ps-mp-help">La meta la defines tú con tu odontólogo de cabecera. Aquí solo hay fechas y estado; el detalle clínico queda en la ficha de la clínica.</p></form></div>';
		echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
	}
);
add_action(
	'admin_post_ps_miplan_save',
	function () {
		$o = isset( $_POST['o'] ) ? wc_get_order( (int) $_POST['o'] ) : null;
		if ( ! $o || ! is_user_logged_in() || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ?? '' ), 'ps_miplan' ) || (int) $o->get_customer_id() !== get_current_user_id() ) {
			wp_die( 'No permitido' );
		}
		$m  = ps_plan_miembros( $o );
		$in = isset( $_POST['m'] ) && is_array( $_POST['m'] ) ? wp_unslash( $_POST['m'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $m as $i => $x ) {
			if ( isset( $in[ $i ] ) && is_array( $in[ $i ] ) ) {
				foreach ( array( 'nombre' => 40, 'rel' => 30, 'edad' => 3, 'meta' => 120 ) as $k => $len ) {
					if ( isset( $in[ $i ][ $k ] ) ) {
						$m[ $i ][ $k ] = mb_substr( sanitize_text_field( $in[ $i ][ $k ] ), 0, $len );
					}
				}
			}
		}
		$m[0]['rel'] = 'titular';
		$o->update_meta_data( '_ps_miembros', $m );
		if ( isset( $_POST['r2'] ) && is_array( $_POST['r2'] ) ) {
			$r  = wp_unslash( $_POST['r2'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$em = sanitize_email( $r['email'] ?? '' );
			$o->update_meta_data( '_ps_r2', array( 'nombre' => sanitize_text_field( $r['nombre'] ?? '' ), 'email' => is_email( $em ) ? $em : '' ) );
		}
		$o->save();
		wp_safe_redirect( add_query_arg( 'ok', 1, wc_get_account_endpoint_url( 'mi-plan' ) ) );
		exit;
	}
);

/* Admin: fechas de control de cada integrante, en el pedido. */
add_action(
	'add_meta_boxes',
	function () {
		$screens = array( 'shop_order' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}
		foreach ( array_unique( $screens ) as $s ) {
			add_meta_box(
				'ps_plan_box',
				'Prevsoul · Plan y controles',
				function ( $post_or_order ) {
					$o = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
					if ( ! $o || ! ps_plan_order_has_plan( $o ) ) {
						echo '<p>Este pedido no incluye un plan.</p>';
						return;
					}
					echo '<p>Fecha del último control de cada integrante (la ven en Mi cuenta → Mi plan).</p>';
					foreach ( ps_plan_miembros( $o ) as $i => $x ) {
						echo '<p><label>' . esc_html( ( $x['nombre'] ? $x['nombre'] : 'Integrante ' . ( $i + 1 ) ) . ( $x['meta'] ? ' · Meta: «' . $x['meta'] . '»' : '' ) ) . '<br><input type="date" name="ps_ult[' . (int) $i . ']" value="' . esc_attr( $x['ultimo'] ) . '"></label></p>';
					}
					$seg = $o->get_meta( '_ps_seg' );
					if ( is_array( $seg ) && $seg ) {
						$l = end( $seg );
						echo '<p><b>Seguimiento 6 meses:</b> ' . (int) $l['r'] . ' de 5 (' . esc_html( $l['f'] ) . ')</p>';
					}
				},
				$s,
				'side'
			);
		}
	}
);
function ps_plan_order_has_plan( $o ) {
	foreach ( $o->get_items() as $it ) {
		if ( ps_es_plan_pid( $it->get_product_id() ) ) {
			return true;
		}
	}
	return false;
}
add_action(
	'woocommerce_process_shop_order_meta',
	function ( $order_id ) {
		if ( empty( $_POST['ps_ult'] ) || ! is_array( $_POST['ps_ult'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$o = wc_get_order( $order_id );
		$m = ps_plan_miembros( $o );
		foreach ( wp_unslash( $_POST['ps_ult'] ) as $i => $d ) { // phpcs:ignore
			$d = sanitize_text_field( $d );
			if ( isset( $m[ (int) $i ] ) ) {
				$m[ (int) $i ]['ultimo'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : '';
			}
		}
		$o->update_meta_data( '_ps_miembros', $m );
		$o->save();
	}
);

/* -------------------------------------------------------------------------
 * Correos: recordatorio mensual y seguimiento a los 6 meses
 * ---------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ps_plan_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ps_plan_cron' );
		}
	}
);
function ps_mail_html( $title, $body ) {
	return '<div style="background:#EEF2F7;padding:24px;font-family:Arial,sans-serif;color:#0B2545"><div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden"><div style="background:#EAF1FC;padding:16px 22px;font-weight:800;font-size:20px">prev<span style="color:#1D5FD1">soul</span></div><div style="padding:20px 22px;font-size:15px;line-height:1.55"><h2 style="font-size:21px;margin:0 0 10px">' . esc_html( $title ) . '</h2>' . $body . '</div><div style="padding:12px 22px;font-size:12px;color:#4A5B73;border-top:1px solid #D7E1EE">Recibes este correo porque tienes un plan Prevsoul.</div></div></div>';
}
function ps_mail_btn( $url, $txt ) {
	return '<p><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#1D5FD1;color:#fff;text-decoration:none;font-weight:800;padding:12px 18px;border-radius:10px">' . esc_html( $txt ) . '</a></p>';
}
function ps_plan_destinatarios( $o ) {
	$to = array( $o->get_billing_email() );
	$r2 = $o->get_meta( '_ps_r2' );
	if ( is_array( $r2 ) && ! empty( $r2['email'] ) ) {
		$to[] = $r2['email'];
	}
	return array_values( array_filter( array_unique( $to ), 'is_email' ) );
}
function ps_seg_url( $o, $r ) {
	$k = substr( wp_hash( 'ps_seg|' . $o->get_id() . '|' . $r ), 0, 16 );
	return add_query_arg( array( 'action' => 'ps_seg', 'o' => $o->get_id(), 'r' => $r, 'k' => $k ), admin_url( 'admin-post.php' ) );
}
add_action( 'ps_plan_cron', 'ps_plan_cron_run' );
function ps_plan_cron_run() {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return;
	}
	$orders = wc_get_orders( array( 'status' => array( 'processing', 'completed' ), 'limit' => 300, 'date_created' => '>' . ( time() - 3 * YEAR_IN_SECONDS ) ) );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$sala    = function_exists( 'ps_get' ) ? ps_get( 'sala_url' ) : home_url( '/' );
	foreach ( $orders as $o ) {
		if ( ! ps_plan_order_has_plan( $o ) ) {
			continue;
		}
		$to   = ps_plan_destinatarios( $o );
		$m    = ps_plan_miembros( $o );
		$now  = time();
		$name = $m[0]['nombre'] ? $m[0]['nombre'] : 'Hola';
		/* Recordatorio mensual: uno por familia, solo si alguien está por agendar. */
		$last = (int) $o->get_meta( '_ps_rem' );
		$pend = array_filter( $m, function ( $x ) { return 'agendar' === ps_plan_estado( $x['ultimo'] ); } );
		$desde = $o->get_date_created() ? $o->get_date_created()->getTimestamp() : $now;
		if ( $to && $pend && $now - $last > 30 * DAY_IN_SECONDS && $now - $desde > 3 * DAY_IN_SECONDS ) {
			$rows = '';
			foreach ( $m as $i => $x ) {
				$e     = ps_plan_estado( $x['ultimo'] );
				$c     = array( 'al-dia' => '#157347', 'pronto' => '#7A5300', 'agendar' => '#B42318' )[ $e ];
				$rows .= '<tr><td style="padding:8px 10px;border-top:1px solid #D7E1EE"><b>' . esc_html( $x['nombre'] ? $x['nombre'] : 'Integrante ' . ( $i + 1 ) ) . '</b>' . ( 0 === $i ? ' (tú)' : '' ) . '</td><td style="padding:8px 10px;border-top:1px solid #D7E1EE;color:' . $c . ';font-weight:700;text-align:right">' . esc_html( ps_plan_estado_txt( $e ) ) . '</td></tr>';
			}
			$body = '<p>' . esc_html( $name ) . ', te escribimos para que no tengas que acordarte tú. Así va tu plan:</p><table style="width:100%;border-collapse:collapse;border:1px solid #D7E1EE;border-radius:10px">' . $rows . '</table>' . ps_mail_btn( wc_get_account_endpoint_url( 'mi-plan' ), 'Ver Mi plan' ) . '<p style="font-size:13px;color:#4A5B73">Un aviso al mes como máximo.</p>';
			wp_mail( $to, count( $pend ) > 1 ? 'Este mes les toca control a ' . count( $pend ) . ' integrantes' : 'Este mes toca un control en tu plan', ps_mail_html( 'Nosotros nos acordamos por ti', $body ), $headers );
			$o->update_meta_data( '_ps_rem', $now );
			$o->save();
		}
		/* Seguimiento cada 6 meses desde la compra. */
		$seg_last = (int) $o->get_meta( '_ps_seg_env' );
		$ref      = $seg_last ? $seg_last : $desde;
		if ( $to && $now - $ref > 182 * DAY_IN_SECONDS ) {
			$meta  = $m[0]['meta'];
			$scale = '';
			for ( $r = 1; $r <= 5; $r++ ) {
				$scale .= '<a href="' . esc_url( ps_seg_url( $o, $r ) ) . '" style="display:inline-block;width:44px;text-align:center;padding:10px 0;margin-right:6px;border:1.5px solid #D7E1EE;border-radius:10px;font-weight:800;color:#0B2545;text-decoration:none">' . $r . '</a>';
			}
			$body = ( $meta ? '<p>Hace un tiempo nos contaste tu meta: <b>«' . esc_html( $meta ) . '»</b>. ¿Cómo vas?</p>' : '<p>Queremos saber cómo vas.</p>' ) . '<p>' . $scale . '</p><p style="font-size:13px;color:#4A5B73">1 = nada · 5 = totalmente. Un clic y listo. Tu respuesta solo la ve tu odontólogo de cabecera.</p>';
			wp_mail( array( $to[0] ), $name . ', ¿tu boca te deja hacer lo que quieres?', ps_mail_html( '¿Tu boca te deja hacer lo que quieres?', $body ), $headers );
			$o->update_meta_data( '_ps_seg_env', $now );
			$o->save();
		}
	}
}
add_action( 'admin_post_nopriv_ps_seg', 'ps_seg_handle' );
add_action( 'admin_post_ps_seg', 'ps_seg_handle' );
function ps_seg_handle() {
	// phpcs:disable WordPress.Security.NonceVerification
	$oid = (int) ( $_GET['o'] ?? 0 );
	$r   = (int) ( $_GET['r'] ?? 0 );
	$k   = sanitize_text_field( wp_unslash( $_GET['k'] ?? '' ) );
	// phpcs:enable
	$o = $oid ? wc_get_order( $oid ) : null;
	if ( ! $o || $r < 1 || $r > 5 || ! hash_equals( substr( wp_hash( 'ps_seg|' . $oid . '|' . $r ), 0, 16 ), $k ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	$seg   = $o->get_meta( '_ps_seg' );
	$seg   = is_array( $seg ) ? $seg : array();
	$seg[] = array( 'f' => wp_date( 'Y-m-d' ), 'r' => $r );
	$o->update_meta_data( '_ps_seg', array_slice( $seg, -10 ) );
	$o->save();
	$sala = function_exists( 'ps_get' ) ? ps_get( 'sala_url' ) : home_url( '/' );
	$low  = $r <= 3;
	$t    = $low ? 'Gracias por contarnos' : '¡Qué bueno!';
	$p    = $low ? 'Si algo todavía te frena, conversémoslo. Una consulta online toma unos minutos y te dice cómo seguir, sin compromiso.' : 'Seguimos acompañándote. Te avisamos antes de tu próximo control.';
	$btn  = $low ? '<a class="b" href="' . esc_url( add_query_arg( 'ps_motivo', 'Seguimiento de mi plan', $sala ) ) . '">Ir a consulta online</a>' : '<a class="b" href="' . esc_url( home_url( '/' ) ) . '">Volver a Prevsoul</a>';
	nocache_headers();
	echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Prevsoul</title><style>body{margin:0;background:#EAF1FC;font-family:"Plus Jakarta Sans",Arial,sans-serif;color:#0B2545;display:grid;place-items:center;min-height:100vh}.c{background:#fff;border-radius:18px;padding:30px;max-width:460px;margin:20px}.l{font-weight:800;font-size:22px;margin-bottom:14px}.l span{color:#1D5FD1}h1{font-size:26px;margin:0 0 10px}p{color:#4A5B73;line-height:1.55}.b{display:inline-block;background:#1D5FD1;color:#fff;text-decoration:none;font-weight:800;padding:13px 20px;border-radius:12px;margin-top:8px}</style></head><body><div class="c"><div class="l">prev<span>soul</span></div><h1>' . esc_html( $t ) . '</h1><p>' . esc_html( $p ) . '</p>' . $btn . '</div></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}

/* -------------------------------------------------------------------------
 * «Para qué te sirve» en cada producto
 * ---------------------------------------------------------------------- */
add_action(
	'woocommerce_product_options_general_product_data',
	function () {
		woocommerce_wp_text_input(
			array(
				'id'          => '_ps_para',
				'label'       => 'Para qué te sirve',
				'description' => 'Una frase sobre lo que el producto te permite hacer en la vida diaria (comer, hablar, reír…). Sin promesas clínicas.',
				'desc_tip'    => true,
			)
		);
	}
);
add_action(
	'woocommerce_process_product_meta',
	function ( $pid ) {
		if ( isset( $_POST['_ps_para'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $pid, '_ps_para', sanitize_text_field( wp_unslash( $_POST['_ps_para'] ) ) ); // phpcs:ignore
		}
	}
);
add_action(
	'woocommerce_single_product_summary',
	function () {
		$t = get_post_meta( get_the_ID(), '_ps_para', true );
		if ( $t ) {
			echo '<div class="ps-para"><b>Para qué te sirve</b>' . esc_html( $t ) . '</div>';
		}
	},
	8
);
/* Textos iniciales (una sola vez; después se editan en cada producto). */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_para_seed' ) || ! post_type_exists( 'product' ) ) {
			return;
		}
		$t = array(
			'Consulta y diagnóstico'      => 'Para saber cómo está tu boca hoy y armar un plan que te deje comer, hablar y reír tranquilo.',
			'Radiografía panorámica'      => 'Para ver lo que no se ve a simple vista y decidir con información, sin sorpresas.',
			'Limpieza dental'             => 'Para que tus encías estén tranquilas y te sientas cómodo al hablar y sonreír de cerca.',
			'Aplicación de flúor'         => 'Para que los dientes de tus hijos resistan mejor el día a día: colaciones, dulces y todo.',
			'Sellantes'                   => 'Para proteger las muelas nuevas de tus hijos justo donde el cepillo no alcanza.',
			'Blanqueamiento'              => 'Para sonreír en las fotos con más confianza, con un resultado natural.',
			'Pack Control + Limpieza'     => 'Para empezar con todo al día: saber cómo estás y salir con la boca limpia.',
			'Pack Niños'                  => 'Para que tus hijos conozcan al dentista con calma y tengan sus dientes protegidos.',
			'Pack Evaluación de Dolor'    => 'Para entender qué pasa y saber cómo seguir, para que el dolor no te quite el día.',
			'Pack Encías'                 => 'Para cuidar la base de tus dientes y seguir masticando de todo por muchos años.',
			'Plan Preventivo Individual'  => 'Para que tu boca no te frene: controles al día y un odontólogo que te conoce.',
			'Plan Preventivo Familiar'    => 'Para que en tu casa todos coman, rían y conversen tranquilos, sin que tengas que acordarte de todo.',
			'Plan Colectivo'              => 'Para cuidar la salud bucal de tu equipo u organización con un plan simple para todos.',
		);
		foreach ( get_posts( array( 'post_type' => 'product', 'numberposts' => 100, 'post_status' => 'any' ) ) as $p ) {
			if ( get_post_meta( $p->ID, '_ps_para', true ) ) {
				continue;
			}
			foreach ( $t as $k => $v ) {
				if ( 0 === mb_stripos( $p->post_title, $k ) ) {
					update_post_meta( $p->ID, '_ps_para', $v );
					break;
				}
			}
		}
		update_option( 'ps_para_seed', 1 );
	},
	30
);

/* -------------------------------------------------------------------------
 * Armador: tarjetas nuevas
 * ---------------------------------------------------------------------- */
add_filter(
	'ps_armador_extra',
	function ( $html ) {
		$planes = 0;
		$fam    = 0;
		$ints   = 0;
		$r2     = 0;
		$tit    = 0;
		$tot_m  = 0;
		$al     = 0;
		$conm   = 0;
		$seg    = array( 'n' => 0, 'hi' => 0, 'lo' => 0 );
		if ( function_exists( 'wc_get_orders' ) ) {
			foreach ( wc_get_orders( array( 'status' => array( 'processing', 'completed' ), 'limit' => 500 ) ) as $o ) {
				if ( ! ps_plan_order_has_plan( $o ) ) {
					continue;
				}
				++$planes;
				$m = ps_plan_miembros( $o );
				if ( ps_plan_es_familiar( $o ) ) {
					++$fam;
					$ints += count( $m );
					$x     = $o->get_meta( '_ps_r2' );
					$r2   += ( is_array( $x ) && ! empty( $x['nombre'] ) ) ? 1 : 0;
				}
				$tit += $m[0]['ultimo'] ? 1 : 0;
				foreach ( $m as $y ) {
					++$tot_m;
					$al   += 'al-dia' === ps_plan_estado( $y['ultimo'] ) ? 1 : 0;
					$conm += $y['meta'] ? 1 : 0;
				}
				$s = $o->get_meta( '_ps_seg' );
				if ( is_array( $s ) && $s ) {
					$l = end( $s );
					++$seg['n'];
					$seg[ $l['r'] >= 4 ? 'hi' : 'lo' ]++;
				}
			}
		}
		$pc  = function ( $a, $b ) {
			return $b ? round( $a / $b * 100 ) . ' %' : '—';
		};
		$k   = function ( $v, $t ) {
			return '<div class="kpi"><b>' . esc_html( $v ) . '</b><span>' . esc_html( $t ) . '</span></div>';
		};
		$c1  = '<div class="card" data-ps-pane="perf"><h3>Plan Familiar y metas · «Prevsoul cuida a quien cuida»</h3><div class="kpis">' . $k( $fam . ' de ' . $planes, 'Planes Familiares sobre el total de planes (meta 35 %): ' . $pc( $fam, $planes ) ) . $k( $fam ? number_format( $ints / $fam, 1, ',', '' ) : '—', 'Integrantes promedio por Plan Familiar' ) . $k( $r2 . ' de ' . $fam, 'Planes Familiares con segundo responsable' ) . '</div>';
		$c1 .= '<div class="kpis">' . $k( $pc( $tit, $planes ), 'Titulares con su propio control registrado (meta 60 % a los 3 meses)' ) . $k( $pc( $al, $tot_m ), 'Integrantes al día (meta 70 % a los 6 meses)' ) . $k( $pc( $conm, $tot_m ), 'Integrantes con meta personal (meta 80 %)' ) . '</div>';
		$c1 .= '<div class="kpis">' . $k( (string) $seg['n'], 'Respuestas al correo de los 6 meses' ) . $k( $pc( $seg['hi'], $seg['n'] ), 'Respondieron 4 o 5 (meta 75 %)' ) . $k( (string) $seg['lo'], 'Respondieron 1 a 3 (invitados a consulta online)' ) . '</div>';
		$c1 .= '<div class="note">Datos de los pedidos con plan y de Mi cuenta → Mi plan. Las fechas de control se registran en cada pedido (recuadro «Prevsoul · Plan y controles»). Nada de esto se envía a Meta ni a Clarity.</div></div>';
		$rows = '';
		for ( $n = 2; $n <= 6; $n++ ) {
			$t     = ps_fam_total( $n );
			$rows .= '<tr><td>' . $n . '</td><td class="n">' . esc_html( wp_strip_all_tags( wc_price( $t ) ) ) . '</td><td class="n">' . esc_html( wp_strip_all_tags( wc_price( round( $t / $n ) ) ) ) . '</td></tr>';
		}
		$c2 = '<div class="card" data-ps-pane="plan"><h3>Precio por integrante del Plan Familiar</h3><p class="lead" style="margin:0 0 8px">Titular al precio del Plan Individual (' . esc_html( wp_strip_all_tags( wc_price( ps_fam_titular() ) ) ) . ') y cada integrante adicional al ' . esc_html( ps_fam_pct() ) . ' % (se cambia en Ajustes → «Integrante adicional en plan familiar»).</p><table><thead><tr><th>Integrantes</th><th class="n">Cuota mensual</th><th class="n">Cuota por persona</th></tr></thead><tbody>' . $rows . '</tbody></table><div class="note">Revisa la rentabilidad de cada tamaño en «Armar plan» con el uso esperado de tu familia tipo.</div></div>';
		$js = '<script>document.querySelectorAll("#psarm [data-ps-pane]").forEach(function(c){var p=document.getElementById(c.dataset.psPane);if(p)p.appendChild(c)});</script>';
		return $html . $c1 . $c2 . $js;
	}
);
