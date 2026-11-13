<?php
/**
 * Consulta online: página exclusiva de la sala de espera y aviso del inicio.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_icon_video( $size = 20 ) {
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2.5"/><path d="M15.5 10.5 21 7.5v9l-5.5-3"/></svg>';
}

/** [ps_consulta_aviso] Aviso pequeño para el inicio. */
function ps_consulta_aviso() {
	$open = ps_online_count() > 0;
	return '<div class="ps-aviso"><span class="ps-aviso-ic">' . ps_icon_video( 22 ) . '</span><div class="ps-aviso-tx"><strong>Consulta online gratis</strong><span><i class="ps-sala-dot' . ( $open ? '' : ' off' ) . '"></i>' . ( $open ? 'Abierta ahora' : 'Deja tu consulta' ) . ' · Habla con un odontólogo real desde donde estés</span></div><a class="ps-btn" href="' . esc_url( ps_get( 'sala_url' ) ) . '">Ir a consulta online →</a></div>';
}

/** [ps_consulta_online] Página completa. */
function ps_consulta_online() {
	$n    = ps_online_count();
	$chip = $n ? 'Abierta ahora · ' . $n . ( 1 === $n ? ' odontólogo disponible' : ' odontólogos disponibles' ) : 'Deja tu consulta · te contactamos';
	$o    = '<section class="ps-sec ps-bg-oscuro ps-pagehead ps-co-hero"><div class="ps-wrap"><span class="ps-co-chip"><i class="ps-sala-dot' . ( $n ? '' : ' off' ) . '"></i>' . esc_html( $chip ) . '</span><h1>Consulta online</h1><p class="ps-hero-sub">Habla gratis con un odontólogo real desde donde estés. Te orientamos y te decimos qué hacer.</p></div></section>';
	$steps = array(
		array( 'Cuéntanos', 'Elige qué te pasa y deja tus datos.', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>' ),
		array( 'Sala de espera', 'Te avisamos cuando es tu turno.', '<path d="M5 11V7a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v4M3 13a2 2 0 0 1 4 0v2h10v-2a2 2 0 0 1 4 0v5H3zM6 18v2M18 18v2"/>' ),
		array( 'Te orientamos', 'Por videollamada o teléfono.', '<rect x="2.5" y="6" width="13" height="12" rx="2.5"/><path d="M15.5 10.5 21 7.5v9l-5.5-3"/>' ),
	);
	$o .= '<section class="ps-sec ps-bg-blanco"><div class="ps-wrap"><h2 class="ps-sec-title">Cómo funciona</h2><ol class="ps-co-steps">';
	foreach ( $steps as $i => $s ) {
		$o .= '<li><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $s[2] . '</svg><strong>' . ( $i + 1 ) . '. ' . esc_html( $s[0] ) . '</strong><span>' . esc_html( $s[1] ) . '</span></li>';
	}
	$o .= '</ol>' . ps_sala2() . '</div></section>';
	$faq = array(
		array( '¿Tiene costo?', 'No. La consulta online es una precita de orientación gratuita.' ),
		array( '¿Reemplaza una consulta presencial?', 'No. Te orientamos y, si lo necesitas, te decimos qué tratamiento o control te conviene y dónde hacerlo.' ),
		array( '¿Cuánto tengo que esperar?', 'Si hay un odontólogo conectado, te contactamos en minutos. Si no, apenas se conecte uno.' ),
	);
	$o .= '<section class="ps-sec ps-bg-claro"><div class="ps-wrap"><h2 class="ps-sec-title">Preguntas frecuentes</h2><div class="ps-co-faq">';
	foreach ( $faq as $f ) {
		$o .= '<details><summary>' . esc_html( $f[0] ) . '</summary><p>' . esc_html( $f[1] ) . '</p></details>';
	}
	return $o . '</div></div></section>';
}

add_action(
	'init',
	function () {
		add_shortcode( 'ps_consulta_aviso', 'ps_consulta_aviso' );
		add_shortcode( 'ps_consulta_online', 'ps_consulta_online' );
	},
	36
);

/** Migración única: la página Sala de espera pasa a ser «Consulta online». */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_consulta_v1' ) ) {
			return;
		}
		update_option( 'ps_consulta_v1', 1 );
		$content = ps_sc( '[ps_consulta_online]' );
		$page    = get_page_by_path( 'consulta-online' );
		if ( ! $page ) {
			$page = get_page_by_path( 'sala-de-espera' );
		}
		if ( $page ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_title' => 'Consulta online', 'post_name' => 'consulta-online', 'post_content' => $content ) );
			$id = $page->ID;
		} else {
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Consulta online', 'post_name' => 'consulta-online', 'post_content' => $content ) );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			return;
		}
		update_post_meta( $id, '_wp_page_template', PS_TEMPLATE );
		$url              = get_permalink( $id );
		$opts             = get_option( 'ps_settings', array() );
		$opts['sala_url'] = $url;
		update_option( 'ps_settings', $opts );

		// Inicio: la sección completa se reemplaza por el aviso y los enlaces #sala apuntan a la página.
		$home = get_page_by_path( 'inicio' );
		if ( ! $home ) {
			$front = (int) get_option( 'page_on_front' );
			$home  = $front ? get_post( $front ) : null;
		}
		if ( $home ) {
			$c = preg_replace( '/\[ps_seccion id="sala"[^\]]*\]\[ps_sala_espera\]\[\/ps_seccion\]/', '[ps_seccion fondo="blanco"][ps_consulta_aviso][/ps_seccion]', $home->post_content );
			$c = str_replace( '"#sala"', '"' . $url . '"', $c );
			if ( $c !== $home->post_content ) {
				wp_update_post( array( 'ID' => $home->ID, 'post_content' => $c ) );
			}
		}
		// Anuncios que apuntaban a #sala.
		foreach ( get_posts( array( 'post_type' => 'ps_anuncio', 'numberposts' => 50, 'post_status' => 'any', 'fields' => 'ids' ) ) as $a ) {
			foreach ( array( 'b1_url', 'b2_url' ) as $k ) {
				if ( '#sala' === get_post_meta( $a, '_ps_' . $k, true ) ) {
					update_post_meta( $a, '_ps_' . $k, $url );
				}
			}
		}
	},
	70
);

/** /sala-de-espera/ antigua → Consulta online. */
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
		if ( preg_match( '#(^|/)sala-de-espera$#', $path ) ) {
			wp_safe_redirect( ps_get( 'sala_url' ), 301 );
			exit;
		}
	}
);
