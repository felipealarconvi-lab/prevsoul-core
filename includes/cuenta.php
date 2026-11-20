<?php
/**
 * Mi cuenta con el diseño Prevsoul: cabecera del sitio, franja de saludo,
 * menú simple con íconos, Inicio con resumen y Mis atenciones en tarjetas.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_es_cuenta() {
	return function_exists( 'is_account_page' ) && is_account_page() && ! ( function_exists( 'ps_es_carro' ) && ps_es_carro() );
}

/* Plantilla Prevsoul. */
add_filter(
	'template_include',
	function ( $template ) {
		if ( is_singular( 'page' ) && ps_es_cuenta() ) {
			return plugin_dir_path( PS_FILE ) . 'templates/' . PS_TEMPLATE;
		}
		return $template;
	},
	21
);

/* Franja de saludo + contenedor. */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! ps_es_cuenta() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( is_user_logged_in() ) {
			$u   = wp_get_current_user();
			$nom = $u->first_name ? $u->first_name : $u->display_name;
			$h1  = 'Hola, ' . $nom;
			$sub = 'Tus atenciones, tu plan y tus datos en un solo lugar.';
		} else {
			$h1  = 'Mi cuenta';
			$sub = 'Ingresa para ver tus atenciones, agendar y valorar.';
		}
		return '<section class="ps-sec ps-bg-oscuro ps-pagehead ps-cu-head"><div class="ps-wrap"><h1>' . esc_html( $h1 ) . '</h1><p class="ps-hero-sub">' . esc_html( $sub ) . '</p></div></section><section class="ps-sec ps-cu"><div class="ps-wrap">' . $content . '</div></section>';
	},
	20
);

/* Menú: nombres simples, sin Descargas ni Dirección, en este orden. */
add_filter(
	'woocommerce_account_menu_items',
	function ( $items ) {
		$orden = array(
			'dashboard'       => 'Inicio',
			'mis-atenciones'  => 'Mis atenciones',
			'mi-plan'         => 'Mi plan',
			'orders'          => 'Mis compras',
			'edit-account'    => 'Mis datos',
			'customer-logout' => 'Salir',
		);
		$out = array();
		foreach ( $orden as $k => $v ) {
			if ( isset( $items[ $k ] ) ) {
				$out[ $k ] = $v;
			}
		}
		return $out;
	},
	200
);
add_filter(
	'woocommerce_endpoint_orders_title',
	function () {
		return 'Mis compras';
	}
);
add_filter(
	'woocommerce_endpoint_edit-account_title',
	function () {
		return 'Mis datos';
	}
);

/* Inicio: sin el texto genérico de WooCommerce. */
add_filter(
	'wc_get_template',
	function ( $located, $name ) {
		if ( 'myaccount/dashboard.php' === $name ) {
			return plugin_dir_path( PS_FILE ) . 'templates/vacio.php';
		}
		return $located;
	},
	10,
	2
);

/** Estado resumido de las órdenes del usuario. */
function ps_cu_resumen() {
	$r = array( 'agendar' => array(), 'proxima' => null, 'valorar' => 0 );
	if ( ! function_exists( 'ps_val_mis_ordenes' ) ) {
		return $r;
	}
	$ahora = time();
	foreach ( ps_val_mis_ordenes() as $oid ) {
		$e = (string) get_post_meta( $oid, '_ps_estado', true );
		if ( 'recibida' === $e ) {
			$r['agendar'][] = $oid;
		} elseif ( 'agendada' === $e ) {
			$t = strtotime( (string) get_post_meta( $oid, '_ps_cita', true ) );
			if ( $t && $t >= $ahora && ( ! $r['proxima'] || $t < $r['proxima'][1] ) ) {
				$r['proxima'] = array( $oid, $t );
			}
		} elseif ( function_exists( 'ps_val_puede' ) && ps_val_puede( $oid ) ) {
			++$r['valorar'];
		}
	}
	return $r;
}

add_action(
	'woocommerce_account_dashboard',
	function () {
		$r    = ps_cu_resumen();
		$url  = function_exists( 'ps_val_url' ) ? ps_val_url() : '';
		$prox = $r['proxima'] ? ps_fmt_date( get_post_meta( $r['proxima'][0], '_ps_cita', true ) ) : 'Sin agendar';
		echo '<div class="ps-cu-tiles"><a class="ps-cu-tile" href="' . esc_url( $url ) . '"><span>Por agendar</span><strong>' . count( $r['agendar'] ) . '</strong></a><a class="ps-cu-tile" href="' . esc_url( $url ) . '"><span>Próxima hora</span><strong class="ps-cu-small">' . esc_html( $prox ) . '</strong></a><a class="ps-cu-tile" href="' . esc_url( $url ) . '"><span>Por valorar</span><strong>' . (int) $r['valorar'] . '</strong></a></div>';
		if ( $r['agendar'] ) {
			$oid = $r['agendar'][0];
			echo '<div class="ps-cu-card ps-cu-at"><div class="ps-cu-l"><span class="ps-cu-ico">' . ps_cu_svg( 'cal' ) . '</span><div><strong>' . ( count( $r['agendar'] ) > 1 ? 'Tienes ' . count( $r['agendar'] ) . ' atenciones por agendar' : 'Tienes una atención por agendar' ) . '</strong><br><span class="ps-cu-mu">' . esc_html( get_post_meta( $oid, '_ps_producto', true ) ) . '</span></div></div><a class="ps-btn" href="' . esc_url( function_exists( 'ps_ag_url' ) ? ps_ag_url( $oid ) : $url ) . '">Elegir hora</a></div>';
		} elseif ( $r['valorar'] ) {
			echo '<div class="ps-cu-card ps-cu-at"><div class="ps-cu-l"><span class="ps-cu-ico">' . ps_cu_svg( 'star' ) . '</span><div><strong>¿Cómo te fue?</strong><br><span class="ps-cu-mu">Tu opinión ayuda a otros pacientes.</span></div></div><a class="ps-btn" href="' . esc_url( $url ) . '">Valorar</a></div>';
		} elseif ( ! $r['proxima'] ) {
			echo '<div class="ps-cu-card ps-cu-at"><div class="ps-cu-l"><span class="ps-cu-ico">' . ps_cu_svg( 'tooth' ) . '</span><div><strong>Tu próxima prevención</strong><br><span class="ps-cu-mu">Revisa atenciones, packs y planes.</span></div></div><a class="ps-btn" href="' . esc_url( home_url( '/atencion-dental/' ) ) . '">Ver atenciones</a></div>';
		}
	}
);

function ps_cu_svg( $k ) {
	$p = array(
		'cal'   => 'M4 5h16v15H4zM4 10h16M8 3v4M16 3v4M12 13v4M10 15h4',
		'star'  => 'M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9z',
		'tooth' => 'M7 3c-2.5 0-4 2-4 4.5 0 3 2 5 2.5 9 .3 2 1.5 2.5 2 1l1-4c.4-1.2 2.6-1.2 3 0l1 4c.5 1.5 1.7 1 2-1 .5-4 2.5-6 2.5-9C21 5 19.5 3 17 3c-1.8 0-3 1-5 1S8.8 3 7 3z',
		'check' => 'M20 6L9 17l-5-5',
	);
	return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $p[ $k ] ?? $p['tooth'] ) . '"/></svg>';
}

/* Estilos de Mi cuenta. */
add_action(
	'wp_head',
	function () {
		if ( ! ps_es_cuenta() ) {
			return;
		}
		$ic = array(
			'dashboard'       => 'M3 11l9-8 9 8v10h-6v-6H9v6H3z',
			'mis-atenciones'  => 'M4 5h16v15H4zM4 10h16M8 3v4M16 3v4',
			'mi-plan'         => 'M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6zM9 12l2 2 4-4',
			'orders'          => 'M6 2h12v20l-3-2-3 2-3-2-3 2zM9 7h6M9 11h6M9 15h4',
			'edit-account'    => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c0-4 3.6-7 8-7s8 3 8 7',
			'customer-logout' => 'M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3',
		);
		$css = '';
		foreach ( $ic as $k => $d ) {
			$svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
			$css .= '.woocommerce-MyAccount-navigation-link--' . $k . ' a:before{-webkit-mask-image:url("data:image/svg+xml,' . rawurlencode( $svg ) . '");mask-image:url("data:image/svg+xml,' . rawurlencode( $svg ) . '")}';
		}
		?>
<style>
.ps-cu{background:#F7FAFE;padding:28px 0 48px}
.ps-cu .woocommerce{display:grid;grid-template-columns:230px minmax(0,1fr);gap:24px;align-items:start}
.ps-cu .woocommerce-MyAccount-navigation,.ps-cu .woocommerce-MyAccount-content{float:none;width:auto;margin:0}
.ps-cu .woocommerce-MyAccount-navigation ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:4px}
.ps-cu .woocommerce-MyAccount-navigation a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;color:#0B2545;text-decoration:none;font-weight:600;font-size:15px}
.ps-cu .woocommerce-MyAccount-navigation a:before{content:"";width:18px;height:18px;flex:none;background:#4A5B73;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat}
.ps-cu .woocommerce-MyAccount-navigation a:hover{background:#fff}
.ps-cu .woocommerce-MyAccount-navigation .is-active a{background:#EAF1FC;color:#164BA8}
.ps-cu .woocommerce-MyAccount-navigation .is-active a:before{background:#1D5FD1}
.ps-cu .woocommerce-MyAccount-navigation-link--customer-logout{margin-top:8px;padding-top:8px;border-top:1px solid #E3E8EF}
.ps-cu .woocommerce-MyAccount-navigation-link--customer-logout a{color:#4A5B73;font-weight:500}
<?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput ?>
.ps-cu .woocommerce-MyAccount-content{display:flex;flex-direction:column;gap:12px;min-width:0}
.ps-cu-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.ps-cu-tile{background:#fff;border:1px solid #D9E3F2;border-radius:14px;padding:14px;text-decoration:none;color:#4A5B73;font-size:13px}
.ps-cu-tile strong{display:block;font-size:26px;color:#164BA8;font-weight:800;margin-top:2px}
.ps-cu-tile strong.ps-cu-small{font-size:15px;margin-top:8px}
.ps-cu-card,.ps-cu .ps-ma{background:#fff;border:1px solid #D9E3F2;border-radius:14px}
.ps-cu-card{padding:14px 16px}
.ps-cu-at{display:flex;justify-content:space-between;align-items:center;gap:12px}
.ps-cu-l{display:flex;align-items:center;gap:12px;min-width:0}
.ps-cu-ico{width:42px;height:42px;border-radius:12px;background:#E6F7F2;color:#167A64;display:flex;align-items:center;justify-content:center;flex:none}
.ps-cu-ico.az{background:#EAF1FC;color:#1D5FD1}
.ps-cu-mu{font-size:13px;color:#4A5B73}
.ps-cu-der{display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.ps-cu-chip{font-size:12px;border-radius:999px;padding:3px 10px;white-space:nowrap;font-weight:600}
.ps-cu table.shop_table{background:#fff;border:1px solid #D9E3F2;border-radius:14px;overflow:hidden;border-collapse:separate}
.ps-cu .woocommerce-EditAccountForm,.ps-cu .woocommerce-form-login,.ps-cu .woocommerce-form-register{background:#fff;border:1px solid #D9E3F2;border-radius:14px;padding:18px}
.ps-cu .woocommerce-message,.ps-cu .woocommerce-info{background:#EAF1FC;border:0;border-radius:12px;color:#164BA8}
.ps-cu .woocommerce > .woocommerce-notices-wrapper{grid-column:1/-1}
.ps-cu .woocommerce:not(:has(.woocommerce-MyAccount-navigation)){display:block;max-width:520px}
@media(max-width:760px){
.ps-cu .woocommerce{grid-template-columns:1fr;gap:14px}
.ps-cu .woocommerce-MyAccount-navigation ul{flex-direction:row;overflow-x:auto;gap:6px;padding-bottom:4px}
.ps-cu .woocommerce-MyAccount-navigation a{white-space:nowrap;background:#fff;border:1px solid #D9E3F2;padding:8px 12px;font-size:14px}
.ps-cu .woocommerce-MyAccount-navigation-link--customer-logout{margin:0;padding:0;border:0}
.ps-cu-tiles{gap:6px}.ps-cu-tile{padding:10px}.ps-cu-tile strong{font-size:22px}
.ps-cu-at{flex-wrap:wrap}
}
</style>
		<?php
	}
);
