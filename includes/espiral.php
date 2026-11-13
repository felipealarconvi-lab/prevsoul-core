<?php
/**
 * [ps_espiral] «La espiral de un diente»: argumento para las objeciones en Planes dentales.
 * Fases según la historia natural de la caries y el ciclo restaurador; valores del arancel referencial UCO 2026.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_espiral_datos() {
	return array(
		'm' => array( 'n' => 'molar', 'v' => array( 0, 93975, 134250, 80550, 322200, 617550, 107400, 832350, 805500 ) ),
		'a' => array( 'n' => 'diente de adelante', 'v' => array( 0, 80550, 107400, 80550, 214800, 657825, 107400, 832350, 805500 ) ),
	);
}

function ps_espiral_icon( $i, $size = 52 ) {
	static $n = 0;
	$n++;
	$tp  = 'M7 3c-2.5 0-4 2-4 4.5 0 2 .8 3.4 1.5 5 .8 2 1 4.5 1.6 6.6.4 1.5 1.9 1.6 2.3 0l.9-3.6c.3-1 1.1-1.5 1.7-1.5s1.4.5 1.7 1.5l.9 3.6c.4 1.6 1.9 1.5 2.3 0 .6-2.1.8-4.6 1.6-6.6.7-1.6 1.5-3 1.5-5C21 5 19.5 3 17 3c-1.8 0-3 1-5 1S8.8 3 7 3z';
	$pd  = 'M9.6 8.4c0-1.1.8-1.7 1.5-1.7.4 0 .6.2.9.2s.5-.2.9-.2c.7 0 1.5.6 1.5 1.7 0 .9-.5 1.5-.9 2.2l-.4 1.1c-.1-.5-.6-.9-1.1-.9s-1 .4-1.1.9l-.4-1.1c-.4-.7-.9-1.3-.9-2.2z';
	$cn  = 'M10.4 11.6c-.5 2.3-.9 4.6-1.4 7.4M13.6 11.6c.5 2.3.9 4.6 1.4 7.4';
	$dej = '<path d="M4.4 9C4.6 6.6 5.8 5.2 7.4 5.2c1.5 0 2.7.9 4.6.9s3.1-.9 4.6-.9c1.6 0 2.8 1.4 3 3.8" fill="none" stroke="#E6D6B8" stroke-width=".5"/>';
	$rs  = 'fill="#DCE6F4" stroke="#6F86A8" stroke-width=".4" stroke-linejoin="round"';
	$pulp = function ( $c, $s ) use ( $pd, $cn ) {
		return '<path d="' . $pd . '" fill="' . $c . '" stroke="' . $s . '" stroke-width=".3"/><path d="' . $cn . '" fill="none" stroke="' . $c . '" stroke-width=".85" stroke-linecap="round"/>';
	};
	$p0 = $pulp( '#F9C4C4', '#E8A0A0' );
	$en = '<path d="' . $pd . '" fill="#F5A97F" stroke="#D9622B" stroke-width=".3"/><path d="' . $cn . '" fill="none" stroke="#D9622B" stroke-width=".9" stroke-linecap="round"/>';
	$cr = '<path d="M0 0h24v9.6c-1.4.3-2.6.3-3.4.3H3.4c-.8 0-2-.1-3.4-.3z" fill="#F6F9FD"/><path d="M3.2 9.85h17.6" stroke="#6F86A8" stroke-width=".5" stroke-linecap="round"/><path d="M5.4 5.3c.5-1 1.2-1.6 2-1.7" fill="none" stroke="#C9D6E8" stroke-width=".7" stroke-linecap="round"/><path d="M11.2 9.9h1.6l-.3 3.2h-1z" fill="#AEB8C6"/>';
	$lay = array(
		array( $p0 . '<path d="M14.2 3.4c.9.6 2 .8 3.1.6.3.4.4.9.2 1.3-1 .5-2.2.5-3.2.1-.3-.6-.3-1.3-.1-2z" fill="#F4F6FA" stroke="#B9C3D2" stroke-width=".35"/>', '<path d="M20.5 1.1l.4 1 1 .4-1 .4-.4 1-.4-1-1-.4 1-.4z" fill="#2E9E5B"/>' ),
		array( $p0 . '<path d="M13.8 2.5c.9.9 2.3 1.4 3.6 1.3.2 1 .1 1.9-.4 2.6.5.9.4 1.8-.3 2.4-1.1.4-2.3.3-3.1-.4-.6-.9-.6-2.1-.1-3-.4-1-.3-2 .3-2.9z" fill="#C79A69"/><path d="M14.4 3.3c.7.6 1.6.9 2.5.9.1.7 0 1.3-.4 1.8.3.6.2 1.2-.3 1.6-.8.2-1.6.1-2.1-.4-.4-.6-.3-1.4.1-2-.2-.6-.1-1.3.2-1.9z" fill="#5E3B22"/>', '' ),
		array( $p0 . '<path d="M10.6 2.6c2.4.9 5.5 1 8.6-.3.9 1.7.8 3.6-.2 5-2.6 1-5.6 1.1-8.1.3-.8-1.5-.9-3.3-.3-5z" ' . $rs . '/><path d="M10.6 7.2c.5.6 1.2.9 1.9.9l-.4 1.1c-.8-.1-1.4-.6-1.7-1.3z" fill="#5E3B22"/>', '' ),
		array( $pulp( '#E85D5D', '#C23A3A' ) . '<path d="M12.6 2.4c1.6.9 3.6 1.2 5.4.9.4 1.4.2 2.8-.7 3.9-1 1.1-2.3 1.8-3.6 2.1-.8-.3-1.3-1-1.5-1.9-.4-1.7-.3-3.4.4-5z" fill="#C79A69"/><path d="M13.2 3.1c1.3.6 2.8.8 4.1.6.2 1 0 1.9-.6 2.7-.8.8-1.7 1.3-2.7 1.6-.5-.2-.8-.7-.9-1.3-.2-1.2-.2-2.4.1-3.6z" fill="#5E3B22"/>', '<g fill="none" stroke="#E0A100" stroke-width=".75" stroke-linecap="round" stroke-linejoin="round"><path d="M21.1 8.6l1.4-.7-.6 1.3 1.5-.4"/><path d="M21.2 12.3l1.5.3-1.3.7 1.3.7"/><path d="M2.6 9.2l-1.4-.8.5 1.4-1.5-.5"/></g>' ),
		array( $en . '<path d="M12.2 2.6c1.6.9 3.5 1.1 5.4.6.5 1.3.3 2.7-.5 3.8-1.5.6-3.2.6-4.6 0-.6-1.4-.7-2.9-.3-4.4z" ' . $rs . '/>', '' ),
		array( $en . $cr, '' ),
		array( $en . $cr . '<path d="M12.6 1.8l-.8 2.8 1.2 1.7-1.4 2.6 1.1 2.2-1 2.8.6 2.6" fill="none" stroke="#B42318" stroke-width=".9" stroke-linecap="round" stroke-linejoin="round"/>', '' ),
	);
	$u = 'psx' . $n;
	if ( $i < 7 ) {
		$in = '<defs><clipPath id="' . $u . '"><path d="' . $tp . '"/></clipPath></defs><g clip-path="url(#' . $u . ')"><rect width="24" height="24" fill="#FFF8EA"/><path d="M0 0h24v9.3C20 8.7 17 5.2 12 5.6S4 8.7 0 9.3z" fill="#fff"/>' . $dej . $lay[ $i ][0] . '</g><path d="' . $tp . '" fill="none" stroke="#1D5FD1" stroke-width="1.15" stroke-linejoin="round"/>' . $lay[ $i ][1];
	} else {
		$in = '<defs><clipPath id="' . $u . '"><rect x="1.5" y="1" width="21" height="22.5" rx="4"/></clipPath></defs><g clip-path="url(#' . $u . ')"><rect y="12.2" width="24" height="12" fill="#F2E3C6"/><path d="M0 12.4q3-1.4 6 0t6 0 6 0 6 0V14.4H0z" fill="#F4B3C1"/></g>';
		if ( 7 === $i ) {
			$in .= '<path d="' . $tp . '" fill="none" stroke="#1D5FD1" stroke-width=".9" stroke-dasharray="1.3 1" opacity=".55"/>';
		}
		$in .= '<path d="M10 12.2h4l-.3 7.6c0 .9-.75 1.6-1.7 1.6s-1.7-.7-1.7-1.6z" fill="#B9C3D1" stroke="#56647A" stroke-width=".4"/>';
		foreach ( array( 14.4, 16.1, 17.8, 19.5 ) as $y ) {
			$in .= '<path d="M9.95 ' . $y . 'l4.1-.55" stroke="#56647A" stroke-width=".45" stroke-linecap="round"/>';
		}
		$in .= '<path d="M11 10.4h2v1.9h-2z" fill="#D5DCE6" stroke="#56647A" stroke-width=".4"/>';
		if ( 8 === $i ) {
			$in .= '<path d="M3.4 8.6C3.3 5.2 5 3 7 3c1.8 0 3.2 1 5 1s3.2-1 5-1c2 0 3.7 2.2 3.6 5.6-.1.9-.7 1.6-1.6 1.7H5c-.9-.1-1.5-.8-1.6-1.7z" fill="#fff" stroke="#1D5FD1" stroke-width="1.15" stroke-linejoin="round"/><path d="M5.4 5.3c.5-1 1.2-1.6 2-1.7" fill="none" stroke="#D7E1EE" stroke-width=".7" stroke-linecap="round"/>';
		}
	}
	return '<svg class="ps-esp-ic" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $in . '</svg>';
}

function ps_espiral_panel( $k, $d, $hidden ) {
	$names  = array( 'Mancha blanca en el esmalte', 'La caries llega a la dentina', 'La tapadura falla', 'Aparece el dolor', 'Endodoncia', 'Corona', 'Se fractura: extracción', 'Implante', 'Corona sobre implante' );
	$levels = array( 'Prevención', 'Prev. secundaria', 'Prev. terciaria', 'Prev. terciaria', 'Prev. terciaria', 'Prev. terciaria', 'Rehabilitación', 'Rehabilitación', 'Rehabilitación' );
	$short  = array( 'Mancha', 'Tapad.', 'Recamb.', 'Dolor', 'Endo', 'Corona', 'Extrac.', 'Implan.', 'Coron.' );
	$clp    = function ( $n ) {
		return '$' . number_format( (float) $n, 0, ',', '.' );
	};
	$acc = array();
	$s   = 0;
	$o   = '<div class="ps-esp-panel" data-diente="' . esc_attr( $k ) . '"' . ( $hidden ? ' hidden' : '' ) . '><ol class="ps-esp-fases">';
	foreach ( $d['v'] as $i => $v ) {
		$s    += $v;
		$acc[] = $s;
		$cls   = 0 === $i ? ' is-ok' : ( $i >= 6 ? ' is-lost' : '' );
		$o    .= '<li class="ps-esp-fase' . $cls . '">' . ps_espiral_icon( $i ) . '<span class="ps-esp-lv">Fase ' . $i . ' · ' . esc_html( $levels[ $i ] ) . '</span><strong>' . esc_html( $names[ $i ] ) . '</strong><span class="ps-esp-pr">' . ( 0 === $i ? 'Incluido en tu plan' : esc_html( $clp( $v ) ) ) . '</span></li>';
	}
	$tot = $s;
	$o  .= '</ol><div class="ps-esp-two"><div class="ps-esp-card"><h3>Lo que se va sumando</h3>';
	// Escalera de costos acumulados.
	$w    = 400;
	$h    = 210;
	$l    = 40;
	$r    = 8;
	$t    = 20;
	$b    = 36;
	$max  = max( 3000000, ceil( $tot / 1000000 ) * 1000000 );
	$bw   = ( $w - $l - $r ) / 9;
	$svg  = '<svg viewBox="0 0 400 210" width="100%" role="img" aria-label="' . esc_attr( 'Costo acumulado por fase: hasta ' . $clp( $tot ) ) . '">';
	for ( $m = 0; $m <= $max / 1000000; $m++ ) {
		$y    = $t + ( 1 - $m * 1000000 / $max ) * ( $h - $t - $b );
		$svg .= '<line x1="' . $l . '" x2="' . ( $w - $r ) . '" y1="' . round( $y, 1 ) . '" y2="' . round( $y, 1 ) . '" stroke="#E1E0D9"/><text x="' . ( $l - 6 ) . '" y="' . round( $y + 4, 1 ) . '" text-anchor="end" font-size="10" fill="#898781">' . $m . ' M</text>';
	}
	foreach ( $acc as $i => $a ) {
		$x    = $l + $i * $bw + 3;
		$bwi  = $bw - 6;
		$hh   = max( 2, $a / $max * ( $h - $t - $b ) );
		$y    = $h - $b - $hh;
		$svg .= '<rect x="' . round( $x, 1 ) . '" y="' . round( $y, 1 ) . '" width="' . round( $bwi, 1 ) . '" height="' . round( $hh, 1 ) . '" rx="3" fill="' . ( 0 === $i ? '#157347' : '#D9622B' ) . '"><title>' . esc_html( 'Fase ' . $i . ': ' . $clp( $a ) ) . '</title></rect>';
		$svg .= '<text x="' . round( $x + $bwi / 2, 1 ) . '" y="' . ( $h - $b + 13 ) . '" text-anchor="middle" font-size="9.5" fill="#4A5B73">' . $i . '</text><text x="' . round( $x + $bwi / 2, 1 ) . '" y="' . ( $h - $b + 25 ) . '" text-anchor="middle" font-size="8.5" fill="#898781">' . esc_html( $short[ $i ] ) . '</text>';
	}
	$svg .= '<text x="' . round( $l + 8 * $bw, 1 ) . '" y="' . round( $h - $b - $tot / $max * ( $h - $t - $b ) - 6, 1 ) . '" text-anchor="middle" font-size="11" font-weight="800" fill="#B42318">' . esc_html( $clp( $tot ) ) . '</text><text x="' . ( $l + 2 ) . '" y="' . ( $h - $b - 10 ) . '" font-size="10" font-weight="800" fill="#157347">$0 extra</text></svg>';
	$o   .= $svg . '</div><div class="ps-esp-card"><h3>Cuánto ahorras según dónde la cortas</h3><dl class="ps-esp-ahorro"><dt class="ps-esp-h">Si se corta en…</dt><dd class="ps-esp-h">Te ahorras</dd>';
	foreach ( array( 0 => 'Fase 0 · con controles del plan', 1 => 'Fase 1 · antes del primer recambio', 3 => 'Fase 3 · antes de la endodoncia', 5 => 'Fase 5 · antes de perder el diente' ) as $i => $label ) {
		$o .= '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $clp( $tot - $acc[ $i ] ) ) . '</dd>';
	}
	return $o . '<dt>Nunca · se pierde el diente</dt><dd class="is-lost">$0</dd></dl></div></div></div>';
}

function ps_espiral() {
	$datos = ps_espiral_datos();
	$o     = '<div class="ps-esp"><div class="ps-esp-sw" role="group" aria-label="Elige el diente"><span>Ver con:</span><button type="button" class="is-on" data-diente="m" aria-pressed="true">Una muela</button><button type="button" data-diente="a" aria-pressed="false">Un diente de adelante</button></div>';
	$o    .= '<p class="ps-lead ps-esp-lead">Así avanza una caries que nadie detectó a tiempo: cada tratamiento abre el siguiente, más grande y más caro, hasta perder el diente.</p>';
	$o    .= ps_espiral_panel( 'm', $datos['m'], false ) . ps_espiral_panel( 'a', $datos['a'], true );
	$o    .= '<p class="ps-esp-inc">Tu plan incluye controles, detección temprana y reparación de tapaduras antes que reemplazo, con tu mismo odontólogo de cabecera: lo que corta la espiral en la fase 0.</p>';
	$o    .= '<p class="ps-muted ps-small">Valores del arancel referencial UCO 2026 ($26.850 por UCO). Las fases de implante no incluyen el implante, aditamentos ni laboratorio. Estimación referencial: no constituye un presupuesto ni un diagnóstico.</p>';
	$o    .= '<p class="ps-puente"><a href="#planes"><span aria-hidden="true">↑</span> Elige tu plan y corta la espiral en la fase 0</a></p></div>';
	$o    .= "<script>(function(){function go(){document.querySelectorAll('.ps-esp').forEach(function(w){w.querySelectorAll('.ps-esp-sw button').forEach(function(b){b.addEventListener('click',function(){var k=b.getAttribute('data-diente');w.querySelectorAll('.ps-esp-sw button').forEach(function(x){var on=x===b;x.classList.toggle('is-on',on);x.setAttribute('aria-pressed',on?'true':'false')});w.querySelectorAll('.ps-esp-panel').forEach(function(p){p.hidden=p.getAttribute('data-diente')!==k})})})})}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',go)}else{go()}})();</script>";
	return $o;
}
add_action(
	'init',
	function () {
		add_shortcode( 'ps_espiral', 'ps_espiral' );
	},
	36
);

/* Página Planes dentales: la sección de ahorro pasa a ser la espiral (una vez). */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_espiral_v1' ) ) {
			return;
		}
		update_option( 'ps_espiral_v1', 1 );
		$page = get_page_by_path( 'planes-dentales' );
		if ( ! $page ) {
			return;
		}
		$c   = $page->post_content;
		$new = '<!-- wp:shortcode -->[ps_seccion fondo="claro" titulo="¿Para qué un plan si no me duele nada?"][ps_espiral][/ps_seccion]<!-- /wp:shortcode -->';
		if ( preg_match( '#<!-- wp:shortcode -->\[ps_seccion[^\]]*\]\[ps_ahorro\]\[/ps_seccion\]<!-- /wp:shortcode -->#', $c, $m ) ) {
			$c = str_replace( $m[0], $new, $c );
		} elseif ( false === strpos( $c, '[ps_espiral]' ) ) {
			$c .= $new;
		}
		if ( preg_match( '#\[ps_seccion(?![^\]]*\bid=)([^\]]*)\]\[ps_productos tipo="planes"\]#', $c, $m2 ) ) {
			$c = str_replace( $m2[0], '[ps_seccion id="planes"' . $m2[1] . '][ps_productos tipo="planes"]', $c );
		}
		if ( $c !== $page->post_content ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
		}
	},
	76
);
