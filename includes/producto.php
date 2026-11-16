<?php
/**
 * Página de producto con el diseño Prevsoul (2.15.0):
 * usa la plantilla completa (cabecera, pie y barra móvil), la paleta corporativa,
 * una etiqueta de categoría, un segundo botón hacia la Consulta online
 * y tres garantías cortas bajo el botón de compra.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ps_es_producto() {
	return function_exists( 'is_product' ) && is_product();
}

/* Plantilla Prevsoul también en la ficha de producto. */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ps_es_producto() && defined( 'PS_TEMPLATE' ) ) {
			return plugin_dir_path( PS_FILE ) . 'templates/' . PS_TEMPLATE;
		}
		return $template;
	},
	30
);

add_filter(
	'body_class',
	function ( $c ) {
		if ( ps_es_producto() ) {
			$c[] = 'ps-producto';
		}
		return $c;
	}
);

/* Etiqueta de categoría sobre el título. */
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( ! $product ) {
			return;
		}
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return;
		}
		$t = $terms[0];
		foreach ( $terms as $term ) {
			if ( $term->parent ) {
				$t = $term;
				break;
			}
		}
		echo '<span class="ps-prod-cat">' . esc_html( $t->name ) . '</span>';
	},
	4
);

/* Textos de las garantías (editables con el filtro ps_producto_garantias). */
function ps_producto_garantias() {
	return apply_filters(
		'ps_producto_garantias',
		array(
			array( 'Sin letra chica', 'Lo que incluye está detallado abajo.' ),
			array( 'Tú eliges cuándo', 'Te damos los pasos para agendar.' ),
			array( 'Odontólogo de cabecera', 'Te acompaña después.' ),
		)
	);
}

/* Segundo botón y garantías, justo después de «Añadir al carrito». */
add_action(
	'woocommerce_single_product_summary',
	function () {
		$url = function_exists( 'ps_get' ) ? ps_get( 'sala_url' ) : '';
		$o   = '';
		if ( $url ) {
			$o .= '<a class="ps-prod-dudas" href="' . esc_url( $url ) . '">¿Dudas? Consulta online</a>';
		}
		$o .= '<ul class="ps-prod-trust">';
		foreach ( ps_producto_garantias() as $g ) {
			$o .= '<li><b>' . esc_html( $g[0] ) . '</b>' . esc_html( $g[1] ) . '</li>';
		}
		echo $o . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	},
	31
);
