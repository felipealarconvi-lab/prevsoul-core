<?php
/**
 * Logos de temporada: logo base (diente con caries) y adornos que cambian solos por fecha.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_logo_arte() {
	static $a = null;
	if ( null === $a ) {
		$a = require __DIR__ . '/logos-arte.php';
	}
	return $a;
}

/** Temporadas en orden de prioridad (la primera que calce gana). */
function ps_temporadas_def() {
	return array(
		'navidad'   => array( 'nombre' => 'Navidad', 'desc' => 'Gorro caricaturesco, acebo, estrellas y copos', 'rangos' => '01-12:06-01', 'desde' => 0, 'activo' => 1 ),
		'primavera' => array( 'nombre' => 'Primavera', 'desc' => 'Corona de flores, sol, abeja, mariposa y tulipanes', 'rangos' => '21-09:30-09', 'desde' => 2027, 'activo' => 1 ),
		'verano'    => array( 'nombre' => 'Verano', 'desc' => 'Lentes de sol, sol detrás y olas', 'rangos' => '21-11:30-11, 07-01:20-03', 'desde' => 0, 'activo' => 1 ),
	);
}
function ps_temporadas() {
	$saved = (array) get_option( 'ps_temporadas', array() );
	$out   = array();
	foreach ( ps_temporadas_def() as $k => $d ) {
		$out[ $k ] = array_merge( $d, isset( $saved[ $k ] ) && is_array( $saved[ $k ] ) ? array_intersect_key( $saved[ $k ], array_flip( array( 'rangos', 'desde', 'activo' ) ) ) : array() );
	}
	return $out;
}

/** «21-11:30-11, 07-01:20-03» → [[1121,1130],[107,320]] (mes*100+día). */
function ps_rangos_parse( $txt ) {
	$r = array();
	foreach ( preg_split( '/[,;\n]+/', (string) $txt ) as $part ) {
		if ( preg_match( '/(\d{1,2})-(\d{1,2})\s*[:a]+\s*(\d{1,2})-(\d{1,2})/u', $part, $m ) ) {
			$r[] = array( (int) $m[2] * 100 + (int) $m[1], (int) $m[4] * 100 + (int) $m[3] );
		}
	}
	return $r;
}

function ps_logo_actual( $ts = null ) {
	$forzar = (string) get_option( 'ps_logo_forzar', '' );
	if ( '' !== $forzar ) {
		return $forzar;
	}
	$ts  = $ts ? $ts : time();
	$md  = (int) wp_date( 'n', $ts ) * 100 + (int) wp_date( 'j', $ts );
	$y   = (int) wp_date( 'Y', $ts );
	foreach ( ps_temporadas() as $slug => $t ) {
		if ( empty( $t['activo'] ) ) {
			continue;
		}
		foreach ( ps_rangos_parse( $t['rangos'] ) as $r ) {
			$wrap = $r[0] > $r[1];
			$in   = $wrap ? ( $md >= $r[0] || $md <= $r[1] ) : ( $md >= $r[0] && $md <= $r[1] );
			if ( ! $in ) {
				continue;
			}
			// Año de inicio del tramo (en tramos que cruzan el año, enero pertenece al año anterior).
			$start_y = ( $wrap && $md <= $r[1] ) ? $y - 1 : $y;
			if ( ! empty( $t['desde'] ) && $start_y < (int) $t['desde'] ) {
				continue;
			}
			return $slug;
		}
	}
	return 'base';
}

/**
 * SVG del logo.
 *
 * @param string $ctx   header | full | icon.
 * @param string $color Color del contorno del diente.
 * @param string $bg    Color de fondo (el verano rellena el diente para tapar el sol).
 * @param string $slug  Temporada (por defecto la de hoy).
 */
function ps_logo_svg( $ctx = 'header', $color = '#1D5FD1', $bg = '#fff', $slug = null ) {
	$a    = ps_logo_arte();
	$slug = $slug ? $slug : ps_logo_actual();
	$fill = 'verano' === $slug ? $bg : 'none';
	$t    = '<path d="' . $a['tooth'] . '" fill="' . esc_attr( $fill ) . '" stroke="' . esc_attr( $color ) . '" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>';
	switch ( $slug ) {
		case 'navidad':
			$vb  = 'full' === $ctx ? '-5 -7 31 31' : ( 'icon' === $ctx ? '-4 -6 27 27' : '-3.4 -5 22.4 26.6' );
			$in  = ( 'full' === $ctx ? $a['xmas_deco'] : '' ) . $t . $a['xmas_hat'] . ( 'header' === $ctx ? $a['xmas_hdr'] : '' );
			break;
		case 'primavera':
			$vb  = 'full' === $ctx ? '-5 -5 31 31' : ( 'icon' === $ctx ? '-3 -3 26 26' : '0.4 -2.4 22.6 24.4' );
			$in  = 'full' === $ctx ? $a['sp_sky'] . $t . $a['sp_ground'] . $a['sp_crown'] : ( 'header' === $ctx ? $a['sp_hdr'] : '' ) . $t . $a['sp_crown'];
			break;
		case 'verano':
			$vb  = 'full' === $ctx ? '-5 -5 31 31' : ( 'icon' === $ctx ? '-3 -4 28 28' : '2 -5.2 21.8 27' );
			$in  = $a['su_back'] . $t . $a['su_glasses'] . ( 'full' === $ctx ? $a['su_waves'] : '' );
			break;
		default:
			$vb = 'icon' === $ctx ? '0 0 24 24' : '2 1.4 18.2 21';
			$in = $t;
	}
	return '<svg class="ps-logo-svg ps-logo-' . esc_attr( $slug ) . '" viewBox="' . $vb . '" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' . $in . '</svg>';
}

/* Ícono de la pestaña (solo si no hay uno configurado en WordPress). */
add_action(
	'wp_head',
	function () {
		if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
			return;
		}
		$svg = ps_logo_svg( 'icon', '#1D5FD1', '#fff' );
		echo '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,' . rawurlencode( $svg ) . '">' . "\n"; // phpcs:ignore
	},
	2
);

/* -------------------------------------------------------------------------
 * Administración: Prevsoul → Logos de temporada
 * ---------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Logos de temporada', 'Logos de temporada', 'manage_options', 'ps-logos', 'ps_logos_page' );
	},
	21
);

function ps_logos_page() {
	$hoy    = ps_logo_actual();
	$temps  = ps_temporadas();
	$forzar = (string) get_option( 'ps_logo_forzar', '' );
	$names  = array( 'base' => 'Logo base' ) + array_map( function ( $t ) { return $t['nombre']; }, $temps );
	echo '<div class="wrap"><h1>Logos de temporada</h1>';
	if ( isset( $_GET['ps_ok'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>Guardado.</p></div>';
	}
	echo '<p>Hoy se muestra: <strong>' . esc_html( $names[ $hoy ] ?? $hoy ) . '</strong>' . ( $forzar ? ' (fijado a mano)' : ' (automático por fecha)' ) . '.</p>';
	echo '<style>.ps-lg td{vertical-align:middle}.ps-lg .ps-logo-svg{width:84px;height:84px;display:block}.ps-lg input.rg{width:260px}</style>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_logos_save">' . wp_nonce_field( 'ps_logos_save', '_wpnonce', true, false ); // phpcs:ignore
	echo '<table class="widefat striped ps-lg"><thead><tr><th>Logo</th><th>Temporada</th><th>Fechas (día-mes)</th><th>Desde el año</th><th>Activa</th></tr></thead><tbody>';
	foreach ( $temps as $k => $t ) {
		echo '<tr><td>' . ps_logo_svg( 'full', '#1D5FD1', '#fff', $k ) . '</td><td><strong>' . esc_html( $t['nombre'] ) . '</strong><br><span class="description">' . esc_html( $t['desc'] ) . '</span></td>'; // phpcs:ignore
		echo '<td><input class="rg" type="text" name="t[' . esc_attr( $k ) . '][rangos]" value="' . esc_attr( $t['rangos'] ) . '"><br><span class="description">Ej: 21-11:30-11, 07-01:20-03</span></td>';
		echo '<td><input type="number" min="0" max="2100" style="width:90px" name="t[' . esc_attr( $k ) . '][desde]" value="' . (int) $t['desde'] . '"><br><span class="description">0 = siempre</span></td>';
		echo '<td><label><input type="checkbox" name="t[' . esc_attr( $k ) . '][activo]" value="1" ' . checked( ! empty( $t['activo'] ), true, false ) . '> Sí</label></td></tr>';
	}
	echo '<tr><td>' . ps_logo_svg( 'full', '#1D5FD1', '#fff', 'base' ) . '</td><td><strong>Logo base</strong><br><span class="description">Cuando no hay temporada activa</span></td><td colspan="3">Resto del año</td></tr>'; // phpcs:ignore
	echo '</tbody></table><p><label>Mostrar ahora: <select name="forzar"><option value="">Automático por fecha</option>';
	foreach ( $names as $k => $n ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $forzar, $k, false ) . '>' . esc_html( $n ) . '</option>';
	}
	echo '</select></label> <span class="description">Úsalo solo para probar; déjalo en «Automático» para que cambie solo.</span></p>';
	echo '<p class="description">Si dos temporadas coinciden, gana la que está más arriba (Navidad, luego Primavera, luego Verano).</p>';
	submit_button( 'Guardar' );
	echo '</form></div>';
}

add_action(
	'admin_post_ps_logos_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_logos_save' ) ) {
			wp_die( 'Sin permiso' );
		}
		$in  = isset( $_POST['t'] ) && is_array( $_POST['t'] ) ? wp_unslash( $_POST['t'] ) : array(); // phpcs:ignore
		$out = array();
		foreach ( array_keys( ps_temporadas_def() ) as $k ) {
			$out[ $k ] = array(
				'rangos' => sanitize_text_field( $in[ $k ]['rangos'] ?? '' ),
				'desde'  => (int) ( $in[ $k ]['desde'] ?? 0 ),
				'activo' => empty( $in[ $k ]['activo'] ) ? 0 : 1,
			);
		}
		update_option( 'ps_temporadas', $out );
		$f = sanitize_key( wp_unslash( $_POST['forzar'] ?? '' ) );
		update_option( 'ps_logo_forzar', ( 'base' === $f || isset( $out[ $f ] ) ) ? $f : '' );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-logos&ps_ok=1' ) );
		exit;
	}
);
