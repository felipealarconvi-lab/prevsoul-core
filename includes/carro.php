<?php
/**
 * Carro y pago con el diseño Prevsoul, y regla de cupones para la liquidación.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_es_carro() {
	return function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );
}

/* Plantilla Prevsoul para carro y pago. */
add_filter(
	'template_include',
	function ( $template ) {
		if ( is_singular( 'page' ) && ps_es_carro() ) {
			return plugin_dir_path( PS_FILE ) . 'templates/' . PS_TEMPLATE;
		}
		return $template;
	},
	20
);

/* Pasos y contenedor. */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! ps_es_carro() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$recibido = function_exists( 'is_order_received_page' ) && is_order_received_page();
		$paso     = is_cart() ? 1 : ( $recibido ? 3 : 2 );
		$labels   = array( 1 => 'Tu carro', 2 => 'Tus datos y pago', 3 => 'Listo' );
		$steps    = '<ol class="ps-co-pasos" aria-label="Pasos de la compra">';
		foreach ( $labels as $n => $l ) {
			$steps .= '<li class="' . ( $n === $paso ? 'on' : ( $n < $paso ? 'ok' : '' ) ) . '"' . ( $n === $paso ? ' aria-current="step"' : '' ) . '><span>' . $n . '</span>' . esc_html( $l ) . '</li>';
		}
		$steps .= '</ol>';
		$nota    = '';
		if ( 1 === $paso ) {
			$nota = '<p class="ps-co-nota">Después de pagar te enviamos los pasos y el contacto para agendar tu hora con quien realiza tu tratamiento.</p>';
		} elseif ( 2 === $paso ) {
			$nota = '<p class="ps-co-nota ps-co-seguro"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Pago seguro. Tus datos de salud no se piden aquí.</p>';
		}
		return '<section class="ps-sec ps-bg-claro ps-carro"><div class="ps-wrap">' . $steps . $content . $nota . '</div></section>';
	},
	20
);

/* Pesos chilenos con punto de miles y sin decimales (una vez). */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_precio_cl2' ) || ! function_exists( 'get_woocommerce_currency' ) ) {
			return;
		}
		update_option( 'ps_precio_cl2', 1 );
		$mil = (string) get_option( 'woocommerce_price_thousand_sep' );
		if ( '' === $mil || $mil === (string) get_option( 'woocommerce_price_decimal_sep' ) ) {
			update_option( 'woocommerce_price_thousand_sep', '.' );
			update_option( 'woocommerce_price_decimal_sep', ',' );
			update_option( 'woocommerce_price_num_decimals', 0 );
		}
	},
	80
);

/* -------------------------------------------------------------------------
 * Cupones: ¿quién absorbe el descuento?
 * ---------------------------------------------------------------------- */
const PS_ABSORBE = array(
	'prevsoul'   => 'Prevsoul: la clínica recibe sobre el precio de lista',
	'compartido' => 'Compartido: la clínica recibe sobre lo que pagó el paciente',
	'prueba'     => 'Cupón de prueba: no genera pago a la clínica',
);

add_action(
	'woocommerce_coupon_options',
	function ( $coupon_id ) {
		$v = get_post_meta( $coupon_id, '_ps_absorbe', true );
		woocommerce_wp_select(
			array(
				'id'          => '_ps_absorbe',
				'label'       => '¿Quién absorbe el descuento?',
				'options'     => PS_ABSORBE,
				'value'       => $v ? $v : 'prevsoul',
				'desc_tip'    => true,
				'description' => 'Define cuánto se le paga a la clínica o profesional cuando un paciente usa este cupón.',
			)
		);
	}
);
add_action(
	'woocommerce_coupon_options_save',
	function ( $post_id ) {
		$v = isset( $_POST['_ps_absorbe'] ) ? sanitize_key( wp_unslash( $_POST['_ps_absorbe'] ) ) : 'prevsoul'; // phpcs:ignore WordPress.Security.NonceVerification
		update_post_meta( $post_id, '_ps_absorbe', isset( PS_ABSORBE[ $v ] ) ? $v : 'prevsoul' );
	}
);

/** Regla del pedido: prueba si algún cupón es de prueba; compartido si todos lo son; si no, Prevsoul. */
function ps_regla_cupon( $order ) {
	$reglas = array();
	foreach ( $order->get_coupon_codes() as $code ) {
		$cid      = function_exists( 'wc_get_coupon_id_by_code' ) ? wc_get_coupon_id_by_code( $code ) : 0;
		$r        = $cid ? get_post_meta( $cid, '_ps_absorbe', true ) : '';
		$reglas[] = $r ? $r : ( 'PRUEBA100' === strtoupper( $code ) ? 'prueba' : 'prevsoul' );
	}
	if ( ! $reglas ) {
		return '';
	}
	if ( in_array( 'prueba', $reglas, true ) ) {
		return 'prueba';
	}
	return count( array_unique( $reglas ) ) === 1 && 'compartido' === $reglas[0] ? 'compartido' : 'prevsoul';
}

/* El cupón de prueba existente queda marcado como prueba. */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_cupon_v1' ) || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
			return;
		}
		update_option( 'ps_cupon_v1', 1 );
		$cid = wc_get_coupon_id_by_code( 'PRUEBA100' );
		if ( $cid ) {
			update_post_meta( $cid, '_ps_absorbe', 'prueba' );
		}
	},
	80
);
