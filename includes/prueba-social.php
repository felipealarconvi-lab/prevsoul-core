<?php
/**
 * Prueba social: vendidos reales, dientrellas, sellos y testimonio autorizado.
 * Todo se calcula con datos reales; solo se puede ocultar, nunca inflar.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

function ps_ps_sellos() {
	return array( '' => 'Ninguno', 'mas' => 'Más contratado', 'nuevo' => 'Nuevo', 'familias' => 'Favorito de las familias', 'reco' => 'Recomendado' );
}

function ps_ps_cfg( $pid ) {
	$c = get_post_meta( $pid, '_ps_social', true );
	return wp_parse_args( is_array( $c ) ? $c : array(), array( 'vend' => 'auto', 'rat' => 'auto', 'sello' => '', 'tes' => 0 ) );
}

function ps_ps_es_plan( $p ) {
	return has_term( 'planes-dentales', 'product_cat', $p->get_id() ) || ( function_exists( 'ps_parent_slugs' ) && in_array( 'planes-dentales', ps_parent_slugs( $p ), true ) );
}

/** Ventas reales (WooCommerce las suma al pagarse cada pedido). */
function ps_ps_ventas( $p ) {
	return (int) $p->get_total_sales();
}

/** Tramo redondeado hacia abajo; null si hay menos de 5. */
function ps_ps_tramo( $n ) {
	$r = null;
	foreach ( array( 5, 10, 25, 50, 100, 250, 500, 1000 ) as $t ) {
		if ( $n >= $t ) {
			$r = $t;
		}
	}
	return $r;
}

function ps_ps_vendidos_txt( $p ) {
	$c = ps_ps_cfg( $p->get_id() );
	if ( 'off' === $c['vend'] ) {
		return '';
	}
	$t = ps_ps_tramo( ps_ps_ventas( $p ) );
	return $t ? '+' . $t . ( ps_ps_es_plan( $p ) ? ' contratados' : ' vendidos' ) : '';
}

/* ------------------------------------------------------------------
 * Dientrellas
 * ---------------------------------------------------------------- */
function ps_ps_destello( $x, $y, $r, $c, $d, $core ) {
	$k = $r * .12;
	$p = sprintf( 'M%1$s %2$sQ%3$s %4$s %5$s %6$sQ%3$s %7$s %1$s %8$sQ%9$s %7$s %10$s %6$sQ%9$s %4$s %1$s %2$sz', $x, $y - $r, $x + $k, $y - $k, $x + $r, $y, $y + $k, $y + $r, $x - $k, $x - $r );
	return '<g class="ps-dt-sp" style="animation-delay:' . $d . 's"><path fill="' . $c . '" stroke="#B8800F" stroke-width=".35" d="' . $p . '"/>' . ( $core ? '<circle cx="' . $x . '" cy="' . $y . '" r="' . round( $r * .18, 2 ) . '" fill="#fff"/>' : '' ) . '</g>';
}

function ps_ps_diente( $fill, $variant, $size, $i ) {
	static $n = 0;
	$n++;
	$id = 'psdt' . $n;
	$t  = 'M6 3.2c1.8-.9 3.4.2 6 .2s4.2-1.1 6-.2c2.2 1.1 2.3 4.6 1.4 7.6-.9 3-1 8.3-2.9 10.1-1.2 1.1-2.1-.3-2.4-2.3-.3-2.1-.8-4.6-2.1-4.6s-1.8 2.5-2.1 4.6c-.3 2-1.2 3.4-2.4 2.3C5.6 19.1 5.5 13.8 4.6 10.8 3.7 7.8 3.8 4.3 6 3.2z';
	$o  = '<svg class="ps-dt" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="-2 -4 32 30" aria-hidden="true"><defs><clipPath id="c' . $id . '"><rect x="0" y="-4" width="' . round( 4 + 20 * $fill, 2 ) . '" height="30"/></clipPath><linearGradient id="g' . $id . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F7D35C"/><stop offset="1" stop-color="#D49A1F"/></linearGradient></defs>';
	$o .= '<path d="' . $t . '" fill="#E8E2D2"/><path d="' . $t . '" fill="url(#g' . $id . ')" clip-path="url(#c' . $id . ')"/>';
	if ( $fill > 0 ) {
		$d = $i * .25;
		if ( 'B2' === $variant ) {
			$o .= ps_ps_destello( 3, 3, 4.2, '#FFD54A', $d, 1 ) . ps_ps_destello( 26, 2.5, 4.8, '#FFD54A', $d + .6, 1 ) . ps_ps_destello( 28, 17.5, 3.2, '#FFD54A', $d + 1.2, 1 ) . ps_ps_destello( .5, 15.5, 2.8, '#F2C94C', $d + 1.7, 1 ) . ps_ps_destello( 14, -2.5, 2.2, '#F2C94C', $d + .3, 0 );
		} else {
			$o .= ps_ps_destello( 3.5, 3.5, 3.4, '#FFD54A', $d, 1 ) . ps_ps_destello( 25.5, 3, 3.8, '#FFD54A', $d + .6, 1 ) . ps_ps_destello( 27, 17.5, 2.6, '#F2C94C', $d + 1.2, 0 ) . ps_ps_destello( 1.5, 15.5, 2.2, '#F2C94C', $d + 1.7, 0 );
		}
	}
	return $o . '</svg>';
}

/** Fila de dientrellas; vacía si no hay reseñas. */
function ps_dientrellas_html( $avg, $count, $size = 16, $variant = 'B1', $largo = false ) {
	if ( ! $count ) {
		return '';
	}
	$avg = max( 0, min( 5, (float) $avg ) );
	$num = number_format( $avg, 1, ',', '' );
	$o   = '<span class="ps-rating ps-dts ps-dts-' . esc_attr( strtolower( $variant ) ) . '" role="img" aria-label="' . esc_attr( sprintf( 'Valoración %s de 5, %d reseñas', $num, $count ) ) . '">';
	for ( $i = 0; $i < 5; $i++ ) {
		$o .= ps_ps_diente( max( 0, min( 1, $avg - $i ) ), $variant, $size, $i );
	}
	$txt = $largo ? $num . ' de 5 · ' . $count . ( 1 === (int) $count ? ' reseña' : ' reseñas' ) : $num . ' · ' . $count;
	return $o . '<span class="ps-dts-n">' . esc_html( $txt ) . '</span></span>';
}

/** Línea para tarjetas: dientrellas B1 + vendidos. */
function ps_ps_linea( $p, $size = 15 ) {
	$c = ps_ps_cfg( $p->get_id() );
	$o = 'off' === $c['rat'] ? '' : ps_dientrellas_html( (float) $p->get_average_rating(), (int) $p->get_review_count(), $size, 'B1' );
	$v = ps_ps_vendidos_txt( $p );
	if ( ! $o && ! $v ) {
		return '';
	}
	return '<div class="ps-ps-line">' . $o . ( $v ? '<span class="ps-ps-vend">' . esc_html( $v ) . '</span>' : '' ) . '</div>';
}

function ps_ps_sello_html( $p ) {
	$c = ps_ps_cfg( $p->get_id() );
	$s = ps_ps_sellos();
	return ( $c['sello'] && isset( $s[ $c['sello'] ] ) ) ? '<span class="ps-ps-sello">' . esc_html( $s[ $c['sello'] ] ) . '</span>' : '';
}

function ps_ps_testimonio_html( $p ) {
	$c = ps_ps_cfg( $p->get_id() );
	if ( ! $c['tes'] ) {
		return '';
	}
	$cm = get_comment( (int) $c['tes'] );
	if ( ! $cm || '1' !== (string) $cm->comment_approved || (int) $cm->comment_post_ID !== $p->get_id() || ! get_comment_meta( $cm->comment_ID, 'ps_testimonio_ok', true ) ) {
		return '';
	}
	$txt = trim( wp_strip_all_tags( $cm->comment_content ) );
	$txt = mb_strlen( $txt ) > 160 ? rtrim( mb_substr( $txt, 0, 157 ) ) . '…' : $txt;
	$who = preg_split( '/\s+/u', trim( $cm->comment_author ) );
	$who = $who[0] . ( isset( $who[1] ) ? ' ' . mb_substr( $who[1], 0, 1 ) . '.' : '' );
	return '<blockquote class="ps-ps-tes">«' . esc_html( $txt ) . '»<cite>' . esc_html( $who ) . '</cite></blockquote>';
}

/* ------------------------------------------------------------------
 * Ficha del producto y tienda
 * ---------------------------------------------------------------- */
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	},
	30
);
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( ! $product ) {
			return;
		}
		$c = ps_ps_cfg( $product->get_id() );
		$s = ps_ps_sello_html( $product );
		$v = ps_ps_vendidos_txt( $product );
		$r = 'off' === $c['rat'] ? '' : ps_dientrellas_html( (float) $product->get_average_rating(), (int) $product->get_review_count(), 22, 'B2', true );
		if ( $s || $v || $r ) {
			echo '<div class="ps-ps-ficha">' . ( $s || $v ? '<div class="ps-ps-top">' . $s . ( $v ? '<span class="ps-ps-vend">' . esc_html( $v ) . '</span>' : '' ) . '</div>' : '' ) . ( $r ? '<a class="ps-ps-rlink" href="#reviews">' . $r . '</a>' : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	6
);
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( $product ) {
			echo ps_ps_testimonio_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	25
);
add_action(
	'woocommerce_after_shop_loop_item_title',
	function () {
		global $product;
		if ( $product ) {
			echo ps_ps_linea( $product, 15 ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	5
);

/* Casilla de autorización para usar la reseña como testimonio. */
add_filter(
	'woocommerce_product_review_comment_form_args',
	function ( $a ) {
		$a['comment_field'] = ( isset( $a['comment_field'] ) ? $a['comment_field'] : '' ) . '<p class="ps-ps-ok"><label><input type="checkbox" name="ps_testimonio_ok" value="1"> Autorizo a Prevsoul a mostrar mi reseña como testimonio, con mi nombre y la inicial de mi apellido.</label></p>';
		return $a;
	}
);
add_action(
	'comment_post',
	function ( $cid ) {
		$cm = get_comment( $cid );
		if ( $cm && 'product' === get_post_type( $cm->comment_post_ID ) && ! empty( $_POST['ps_testimonio_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_comment_meta( $cid, 'ps_testimonio_ok', 1, true );
		}
	}
);

/* ------------------------------------------------------------------
 * Panel: Prevsoul → Prueba social
 * ---------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Prueba social', 'Prueba social', 'manage_options', 'ps-social', 'ps_ps_page' );
	},
	22
);

add_action(
	'admin_post_ps_social_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_social' );
		$in = isset( $_POST['ps'] ) && is_array( $_POST['ps'] ) ? wp_unslash( $_POST['ps'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$se = ps_ps_sellos();
		foreach ( $in as $pid => $r ) {
			$pid = absint( $pid );
			if ( ! $pid || 'product' !== get_post_type( $pid ) ) {
				continue;
			}
			$tes = isset( $r['tes'] ) ? absint( $r['tes'] ) : 0;
			if ( $tes && ! get_comment_meta( $tes, 'ps_testimonio_ok', true ) ) {
				$tes = 0;
			}
			update_post_meta(
				$pid,
				'_ps_social',
				array(
					'vend'  => ( isset( $r['vend'] ) && 'off' === $r['vend'] ) ? 'off' : 'auto',
					'rat'   => ( isset( $r['rat'] ) && 'off' === $r['rat'] ) ? 'off' : 'auto',
					'sello' => ( isset( $r['sello'] ) && isset( $se[ $r['sello'] ] ) ) ? $r['sello'] : '',
					'tes'   => $tes,
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ps-social&ok=1' ) );
		exit;
	}
);

function ps_ps_page() {
	$prods = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 100, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
	$se    = ps_ps_sellos();
	echo '<div class="wrap"><h1>Prueba social</h1>';
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Guardado.</p></div>';
	}
	echo '<p>Los vendidos y las dientrellas salen solos de las ventas y reseñas reales. Aquí puedes ocultarlos, elegir un sello y destacar un testimonio autorizado por el paciente.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_social_save">';
	wp_nonce_field( 'ps_social' );
	echo '<table class="widefat striped ps-ps-adm"><thead><tr><th>Producto</th><th>Ventas reales</th><th>Se muestra</th><th>Vendidos</th><th>Dientrellas</th><th>Sello</th><th>Testimonio destacado</th></tr></thead><tbody>';
	foreach ( $prods as $p ) {
		$id  = $p->get_id();
		$c   = ps_ps_cfg( $id );
		$n   = ps_ps_ventas( $p );
		$t   = ps_ps_tramo( $n );
		$rc  = (int) $p->get_review_count();
		$k   = 'ps[' . $id . ']';
		$tes = get_comments( array( 'post_id' => $id, 'status' => 'approve', 'type' => 'review', 'number' => 50, 'meta_key' => 'ps_testimonio_ok', 'meta_value' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		echo '<tr><td><a href="' . esc_url( get_edit_post_link( $id ) ) . '"><strong>' . esc_html( $p->get_name() ) . '</strong></a></td>';
		echo '<td>' . (int) $n . '</td><td>' . ( $t ? esc_html( '+' . $t . ( ps_ps_es_plan( $p ) ? ' contratados' : ' vendidos' ) ) : '<span style="color:#8a94a6">Aún no (menos de 5)</span>' ) . '</td>';
		echo '<td><select name="' . esc_attr( $k ) . '[vend]"><option value="auto">Automático</option><option value="off"' . selected( $c['vend'], 'off', false ) . '>Ocultar</option></select></td>';
		echo '<td><select name="' . esc_attr( $k ) . '[rat]"><option value="auto">Automático</option><option value="off"' . selected( $c['rat'], 'off', false ) . '>Ocultar</option></select><br><small>' . ( $rc ? esc_html( number_format( (float) $p->get_average_rating(), 1, ',', '' ) . ' de 5 · ' . $rc . ' reseñas' ) : 'Sin reseñas aún' ) . '</small></td>';
		echo '<td><select name="' . esc_attr( $k ) . '[sello]">';
		foreach ( $se as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $c['sello'], $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td><td><select name="' . esc_attr( $k ) . '[tes]" style="max-width:260px"><option value="0">Sin testimonio</option>';
		foreach ( $tes as $cm ) {
			$tx = trim( wp_strip_all_tags( $cm->comment_content ) );
			echo '<option value="' . (int) $cm->comment_ID . '"' . selected( (int) $c['tes'], (int) $cm->comment_ID, false ) . '>' . esc_html( mb_substr( $tx, 0, 60 ) . ( mb_strlen( $tx ) > 60 ? '…' : '' ) . ' — ' . $cm->comment_author ) . '</option>';
		}
		echo '</select>' . ( $tes ? '' : '<br><small>Sin reseñas autorizadas</small>' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	submit_button( 'Guardar' );
	echo '</form><h2>Vista previa</h2><p>' . ps_dientrellas_html( 4.5, 12, 18, 'B1' ) . ' &nbsp; tarjetas (B1)</p><p>' . ps_dientrellas_html( 4.5, 12, 24, 'B2', true ) . ' &nbsp; ficha del producto (B2)</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<style>' . ps_ps_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

function ps_ps_css() {
	return '.ps-dts{display:inline-flex;align-items:center;gap:1px;font-size:12px;color:#4A5B73;line-height:1}.ps-dts svg.ps-dt{overflow:visible;flex:none}.ps-dts-b2{gap:4px;font-size:14px}.ps-dts-n{margin-left:6px;font-weight:600}'
		. '@keyframes psdt{0%,100%{opacity:.45;transform:scale(.7) rotate(0)}50%{opacity:1;transform:scale(1.1) rotate(20deg)}}.ps-dt-sp{animation:psdt 2.2s ease-in-out infinite;transform-box:fill-box;transform-origin:center}'
		. '@media (prefers-reduced-motion:reduce){.ps-dt-sp{animation:none}}';
}

add_action(
	'wp_head',
	function () {
		echo '<style id="ps-social-css">' . ps_ps_css() // phpcs:ignore WordPress.Security.EscapeOutput
			. '.ps-ps-line{display:flex;flex-wrap:wrap;align-items:center;gap:4px 10px;margin:4px 0 6px}.ps-ps-vend{font-size:12px;font-weight:700;color:#167A64}'
			. '.ps-ps-sello{position:absolute;top:3.5cqw;right:3.5cqw;z-index:2;background:#164BA8;color:#fff;font:700 max(10px,3.6cqw)/1 "Plus Jakarta Sans",sans-serif;padding:1.6cqw 3cqw;border-radius:99px;white-space:nowrap}'
			. '.ps-ps-ficha{margin:6px 0 12px;display:flex;flex-direction:column;gap:8px}.ps-ps-top{display:flex;flex-wrap:wrap;align-items:center;gap:8px}.ps-ps-top .ps-ps-sello{position:static;font-size:12px;padding:5px 12px}.ps-ps-top .ps-ps-vend{font-size:13px}.ps-ps-rlink{text-decoration:none!important;align-self:flex-start}'
			. '.ps-ps-tes{margin:12px 0;padding:2px 0 2px 12px;border-left:3px solid #E3B23C;font-style:italic;color:#4A5B73;font-size:14px}.ps-ps-tes cite{display:block;font-style:normal;font-weight:700;font-size:12px;margin-top:4px;color:#0B2545}.ps-ps-ok{font-size:13px}.ps-ps-ok input{margin-right:6px}'
			. '</style>';
	},
	30
);
