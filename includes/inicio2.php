<?php
/**
 * Inicio 2.0: buscador humanizado con tags, carruseles de atenciones y packs ordenados por valoración
 * (ícono «dienteestrella»), sala de espera en formato conversación, Prevsoul Live «cine» y tabla de planes.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Dienteestrella
 * ---------------------------------------------------------------------- */
function ps_diente_svg( $on = true, $size = 16 ) {
	$tooth = '<path d="M8 6.3c-2.1 0-3.4 1.6-3.4 3.7 0 1.7.7 2.8 1.3 4.2.6 1.6.8 3.7 1.3 5.4.3 1.2 1.5 1.3 1.9 0l.7-3c.3-.9.9-1.3 1.4-1.3s1.1.4 1.4 1.3l.7 3c.4 1.3 1.6 1.2 1.9 0 .5-1.7.7-3.8 1.3-5.4.6-1.4 1.3-2.5 1.3-4.2 0-2.1-1.3-3.7-3.4-3.7-1.5 0-2.4.8-4.1.8S9.5 6.3 8 6.3z" fill="#fff" stroke="' . ( $on ? '#F5B301' : '#C9D3E2' ) . '" stroke-width="1.6" stroke-linejoin="round"/>';
	$spark = $on ? '<path d="M12 1.2l.9 2.3 2.3.9-2.3.9-.9 2.3-.9-2.3-2.3-.9 2.3-.9z" fill="#F5B301"/><path d="M8.2 9.2c.5-.7 1.2-1 2-.9" stroke="#FFE08A" stroke-width="1.2" stroke-linecap="round" fill="none"/>' : '';
	return '<svg class="ps-de" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" aria-hidden="true">' . $spark . $tooth . '</svg>';
}
function ps_dientes_html( $avg, $count, $size = 16 ) {
	if ( ! $count ) {
		return '<span class="ps-rating ps-rating-new">' . ps_diente_svg( true, $size ) . ' Nuevo</span>';
	}
	$f = (int) round( $avg );
	$o = '<span class="ps-rating" role="img" aria-label="' . esc_attr( sprintf( 'Valoración %s de 5, %d opiniones', number_format( $avg, 1, ',', '' ), $count ) ) . '">';
	for ( $i = 0; $i < 5; $i++ ) {
		$o .= ps_diente_svg( $i < $f, $size );
	}
	return $o . '<strong>' . esc_html( number_format( $avg, 1, ',', '' ) ) . '</strong><span class="ps-muted">(' . (int) $count . ')</span></span>';
}

/* -------------------------------------------------------------------------
 * Ayudas de productos
 * ---------------------------------------------------------------------- */
function ps_best_review( $pid ) {
	$best = null;
	foreach ( get_comments( array( 'post_id' => $pid, 'status' => 'approve', 'type' => 'review', 'number' => 30 ) ) as $c ) {
		$r   = (int) get_comment_meta( $c->comment_ID, 'rating', true );
		$len = mb_strlen( trim( wp_strip_all_tags( $c->comment_content ) ) );
		if ( $r >= 4 && $len >= 12 && ( ! $best || $r > $best[1] ) ) {
			$best = array( $c, $r );
		}
	}
	if ( ! $best ) {
		return '';
	}
	$txt = trim( wp_strip_all_tags( $best[0]->comment_content ) );
	$txt = mb_strlen( $txt ) > 110 ? rtrim( mb_substr( $txt, 0, 107 ) ) . '…' : $txt;
	$who = preg_split( '/\s+/u', trim( $best[0]->comment_author ) );
	$who = $who[0] . ( isset( $who[1] ) ? ' ' . mb_substr( $who[1], 0, 1 ) . '.' : '' );
	return '<blockquote class="ps-quote">«' . esc_html( $txt ) . '»<cite>' . esc_html( $who ) . '</cite></blockquote>';
}
function ps_child_cat_name( $p ) {
	foreach ( wc_get_product_term_ids( $p->get_id(), 'product_cat' ) as $tid ) {
		$t = get_term( $tid, 'product_cat' );
		if ( $t && ! is_wp_error( $t ) && $t->parent ) {
			return $t->name;
		}
	}
	return '';
}
function ps_sorted_products( $tipo, $limit = 24 ) {
	$parent = get_term_by( 'slug', PS_CATS[ $tipo ] ?? $tipo, 'product_cat' );
	if ( ! $parent ) {
		return array();
	}
	$slugs = array( $parent->slug );
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'child_of' => $parent->term_id, 'hide_empty' => false ) ) as $t ) {
		$slugs[] = $t->slug;
	}
	$ps = wc_get_products( array( 'status' => 'publish', 'limit' => $limit, 'category' => $slugs ) );
	usort(
		$ps,
		function ( $a, $b ) {
			$ra = (float) $a->get_average_rating();
			$rb = (float) $b->get_average_rating();
			if ( $ra !== $rb ) {
				return $rb <=> $ra;
			}
			if ( $a->get_review_count() !== $b->get_review_count() ) {
				return $b->get_review_count() <=> $a->get_review_count();
			}
			return $a->get_menu_order() <=> $b->get_menu_order();
		}
	);
	return $ps;
}
function ps_product_lines( $p, $max = 3 ) {
	$items = array_filter( array_map( 'trim', explode( "\n", wp_strip_all_tags( $p->get_description() ) ) ) );
	return array_slice( array_map( function ( $i ) { return ltrim( $i, "-•* \t" ); }, $items ), 0, $max );
}
function ps_price_html( $p ) {
	$reg = (float) $p->get_regular_price();
	$cur = (float) $p->get_price();
	if ( $cur <= 0 ) {
		return '';
	}
	return '<div class="ps-cprice">' . esc_html( ps_clp( $cur ) ) . ( $reg > $cur ? '<s>' . esc_html( ps_clp( $reg ) ) . '</s>' : '' ) . '</div>';
}

/* -------------------------------------------------------------------------
 * [ps_carrusel tipo="atencion|packs"]
 * ---------------------------------------------------------------------- */
function ps_carrusel_card( $p, $tipo ) {
	$on_sale = $p->is_on_sale();
	$img     = $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) ) : '<span class="ps-cph-ph">' . ps_diente_svg( true, 42 ) . '</span>';
	$o       = '<article class="ps-ccard"><a class="ps-cph" href="' . esc_url( $p->get_permalink() ) . '" tabindex="-1">' . $img . ( $on_sale ? '<span class="ps-coff">Oferta</span>' : '' ) . '</a><div class="ps-cbody">';
	$cat     = 'packs' === $tipo ? '' : ps_child_cat_name( $p );
	$o      .= $cat ? '<span class="ps-ccat">' . esc_html( $cat ) . '</span>' : '';
	$o      .= '<h3 class="ps-ctitle"><a href="' . esc_url( $p->get_permalink() ) . '">' . esc_html( $p->get_name() ) . '</a></h3>';
	$o      .= ps_dientes_html( (float) $p->get_average_rating(), (int) $p->get_review_count(), 15 );
	if ( 'packs' === $tipo && ( $lines = ps_product_lines( $p ) ) ) {
		$o .= '<ul class="ps-cchk">';
		foreach ( $lines as $l ) {
			$o .= '<li>' . esc_html( $l ) . '</li>';
		}
		$o .= '</ul>';
	}
	$o .= ps_best_review( $p->get_id() );
	$prov = function_exists( 'ps_product_prestador' ) ? ps_product_prestador( $p->get_id() ) : 0;
	if ( $prov ) {
		$o .= '<div class="ps-cprov">' . ps_prestador_img( $prov, 22 ) . '<span>Realiza: ' . esc_html( get_the_title( $prov ) ) . '</span></div>';
	}
	$o .= ps_price_html( $p );
	$o .= '<div class="ps-cbtns">' . ( '' !== $p->get_price() ? '<a class="ps-btn" href="' . esc_url( $p->add_to_cart_url() ) . '" rel="nofollow">Comprar</a>' : '' ) . '<a class="ps-btn ps-btn-ghost" href="' . esc_url( $p->get_permalink() ) . '">Ver</a></div>';
	return $o . '</div></article>';
}
add_action(
	'init',
	function () {
		add_shortcode(
			'ps_carrusel',
			function ( $atts ) {
				$a  = shortcode_atts( array( 'tipo' => 'atencion', 'limite' => 24, 'ver_todo' => '' ), $atts );
				$ps = function_exists( 'wc_get_products' ) ? ps_sorted_products( $a['tipo'], (int) $a['limite'] ) : array();
				if ( ! $ps ) {
					return '<p class="ps-muted">Pronto publicaremos opciones en esta sección.</p>';
				}
				$id  = 'ps-car-' . wp_unique_id();
				$out = '<div class="ps-car" data-car><div class="ps-car-nav"><button type="button" class="ps-car-btn" data-dir="-1" aria-controls="' . esc_attr( $id ) . '" aria-label="Anteriores">‹</button><button type="button" class="ps-car-btn" data-dir="1" aria-controls="' . esc_attr( $id ) . '" aria-label="Siguientes">›</button></div><div class="ps-car-track" id="' . esc_attr( $id ) . '" tabindex="0">';
				foreach ( $ps as $p ) {
					$out .= ps_carrusel_card( $p, $a['tipo'] );
				}
				$out .= '</div>';
				if ( $a['ver_todo'] ) {
					$out .= '<p class="ps-car-all"><a href="' . esc_url( $a['ver_todo'] ) . '">Ver todo →</a></p>';
				}
				return $out . '</div>' . ps_inicio_js();
			}
		);
		add_shortcode( 'ps_buscador', 'ps_buscador2' );
		add_shortcode( 'ps_sala_espera', 'ps_sala2' );
		add_shortcode( 'ps_live_cine', 'ps_live_cine' );
		add_shortcode( 'ps_planes', 'ps_planes' );
	},
	35
);
function ps_inicio_js() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return "<script>(function(){function ps2(){document.querySelectorAll('[data-car]').forEach(function(c){var t=c.querySelector('.ps-car-track');c.querySelectorAll('.ps-car-btn').forEach(function(b){b.addEventListener('click',function(){var card=t.firstElementChild;var w=card?card.getBoundingClientRect().width+16:260;t.scrollBy({left:w*(+b.dataset.dir)*(window.innerWidth>860?2:1),behavior:'smooth'})})})});document.querySelectorAll('[data-cd]').forEach(function(el){var end=new Date(el.dataset.cd).getTime();function tk(){var r=Math.max(0,(end-Date.now())/1000),p=function(n){return String(Math.floor(n)).padStart(2,'0')};el.querySelector('[data-d]').textContent=p(r/86400);el.querySelector('[data-h]').textContent=p(r%86400/3600);el.querySelector('[data-m]').textContent=p(r%3600/60);el.querySelector('[data-s]').textContent=p(r%60)}tk();setInterval(tk,1000)});document.querySelectorAll('.ps-sala2-chips input').forEach(function(i){i.addEventListener('change',function(){var f=i.closest('form');if(f)f.classList.add('ps-step2')})})}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',ps2)}else{ps2()}})();</script>";
}

/* -------------------------------------------------------------------------
 * Buscador humanizado
 * ---------------------------------------------------------------------- */
function ps_host_dentist() {
	$on = get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 1, 'meta_key' => '_ps_activo', 'meta_value' => '1' ) );
	if ( $on ) {
		return array( $on[0]->ID, true );
	}
	$any = get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 1 ) );
	return array( $any ? $any[0]->ID : 0, false );
}
function ps_tags( $k ) {
	$def = array(
		'sientes' => "Me sangran las encías\nMe duele una muela\nSensibilidad al frío\nMal aliento\nEncías inflamadas\nSe me rompió un diente\nMi hijo tiene caries\nMe rechinan los dientes",
		'trat'    => "Limpieza\nBlanqueamiento\nConsulta\nRadiografías\nFlúor\nSellantes\nOrtodoncia\nImplantes\nEndodoncia",
	);
	return array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) get_option( 'ps_tags_' . $k, $def[ $k ] ) ) ) );
}
function ps_buscador2() {
	$q    = isset( $_GET['ps_q'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$base = remove_query_arg( 'ps_q' );
	list( $doc, $online ) = ps_host_dentist();
	$out  = '<div class="ps-bus" id="ps-buscador"><div class="ps-bus-hi">' . ( $doc ? '<span class="ps-bus-av' . ( $online ? ' is-on' : '' ) . '">' . ps_avatar( $doc, 48 ) . '</span>' : '' );
	$out .= '<div class="ps-bus-bub"><strong>Hola, ¿cómo podemos ayudarte hoy?</strong>Cuéntanos qué sientes o qué estás buscando, tal como se lo dirías a tu dentista. Te orientamos al tiro.</div></div>';
	$out .= '<form class="ps-bus-form" method="get" action="' . esc_url( $base ) . '#ps-buscador" role="search"><label class="screen-reader-text" for="ps_q">Cuéntanos qué te pasa</label><input id="ps_q" name="ps_q" type="search" value="' . esc_attr( $q ) . '" placeholder="Por ejemplo: «me sangran las encías cuando me cepillo»"><button class="ps-btn" type="submit">Orientarme</button></form>';
	foreach ( array( 'sientes' => 'Lo que sientes', 'trat' => 'Tratamientos' ) as $k => $label ) {
		$tags = ps_tags( $k );
		if ( ! $tags ) {
			continue;
		}
		$out .= '<div class="ps-bus-tags ps-bus-' . esc_attr( $k ) . '"><span>' . esc_html( $label ) . '</span><div class="ps-bus-line">';
		foreach ( $tags as $t ) {
			$out .= '<a class="ps-bus-tag" href="' . esc_url( add_query_arg( 'ps_q', rawurlencode( $t ), $base ) . '#ps-buscador' ) . '">' . esc_html( $t ) . '</a>';
		}
		$out .= '</div></div>';
	}
	if ( '' === $q ) {
		return $out . '</div>';
	}
	$t     = ps_norm( $q );
	$best  = null;
	$score = 0;
	foreach ( ps_rules() as $r ) {
		$s = 0;
		foreach ( $r['kw'] as $kw ) {
			if ( $kw && false !== strpos( $t, $kw ) ) {
				$s++;
			}
		}
		if ( $s > $score ) {
			$score = $s;
			$best  = $r;
		}
	}
	$prods = array();
	if ( $best && $best['skus'] ) {
		foreach ( $best['skus'] as $sku ) {
			$pid = wc_get_product_id_by_sku( $sku );
			$p   = $pid ? wc_get_product( $pid ) : null;
			if ( $p && 'publish' === $p->get_status() ) {
				$prods[] = $p;
			}
		}
	}
	if ( ! $best && function_exists( 'wc_get_products' ) ) {
		$prods = wc_get_products( array( 'status' => 'publish', 'limit' => 6, 's' => $q ) );
	}
	ps_log_search( $q, $best ? $best['need'] : ( $prods ? 'Tratamiento: ' . $prods[0]->get_name() : '' ) );
	$sala = ps_get( 'sala_url' );
	$out .= '<div class="ps-bus-res">';
	if ( $best ) {
		$out .= '<span class="ps-chip ps-acc">Lo que entendimos: ' . esc_html( $best['need'] ) . '</span><p>' . esc_html( $best['msg'] ) . '</p>';
	} elseif ( $prods ) {
		$out .= '<span class="ps-chip ps-acc">Encontramos esto para «' . esc_html( $q ) . '»</span>';
	} else {
		$out .= '<p><strong>Cuéntaselo a un odontólogo.</strong> Todavía no tenemos una respuesta automática para «' . esc_html( $q ) . '», pero en la sala de espera te orientan personalmente.</p>';
	}
	if ( $prods ) {
		$out .= '<div class="ps-car" data-car><div class="ps-car-track">';
		foreach ( $prods as $p ) {
			$out .= ps_carrusel_card( $p, 'atencion' );
		}
		$out .= '</div></div>';
	}
	$out .= '<a class="ps-btn" href="' . esc_url( add_query_arg( 'ps_motivo', $best ? $best['need'] : 'Otra consulta', $sala ) ) . '">' . ( $prods ? '¿Prefieres hablar con alguien? Entra a la sala de espera' : 'Ir a la sala de espera' ) . '</a></div>';
	return $out . '</div>' . ps_inicio_js();
}

/* -------------------------------------------------------------------------
 * Sala de espera: panel en vivo + conversación
 * ---------------------------------------------------------------------- */
function ps_sala2() {
	$done = isset( $_GET['ps_precita'] ) && 'ok' === sanitize_key( wp_unslash( $_GET['ps_precita'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( $done ) {
		return ps_sala_shortcode();
	}
	$dent    = get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 6, 'meta_key' => '_ps_activo', 'meta_value' => '1' ) );
	$open    = (bool) $dent;
	$waiting = count( get_posts( array( 'post_type' => 'ps_precita', 'post_status' => 'any', 'numberposts' => 50, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => 'nueva', 'date_query' => array( array( 'after' => '12 hours ago' ) ) ) ) );
	$host    = $dent ? $dent[0]->ID : ps_host_dentist()[0];
	$first   = $host ? preg_split( '/\s+/u', preg_replace( '/^(Dra?\.)\s*/u', '', get_the_title( $host ) ) )[0] : '';
	$sal     = $host ? ( 0 === strpos( get_the_title( $host ), 'Dra' ) ? 'la Dra. ' : ( 0 === strpos( get_the_title( $host ), 'Dr' ) ? 'el Dr. ' : '' ) ) . $first : 'el equipo Prevsoul';
	$out     = '<div class="ps-sala2"><div class="ps-sala2-now' . ( $open ? ' is-open' : '' ) . '"><span class="ps-sala2-st"><i class="ps-sala-dot' . ( $open ? '' : ' off' ) . '"></i>' . ( $open ? 'Sala abierta ahora' : 'Sala cerrada en este momento' ) . '</span>';
	if ( $dent ) {
		$out .= '<div class="ps-sala2-docs">';
		foreach ( $dent as $d ) {
			$out .= ps_avatar( $d->ID, 44 );
		}
		$names = array_map( function ( $d ) { return $d->post_title; }, $dent );
		$out  .= '</div><p>' . esc_html( count( $names ) > 1 ? implode( ', ', array_slice( $names, 0, -1 ) ) . ' y ' . end( $names ) . ' están atendiendo' : $names[0] . ' está atendiendo' ) . '</p>';
	} else {
		$out .= '<p>Deja tu consulta y te contactamos apenas se conecte un odontólogo.</p>';
	}
	$out .= '<div class="ps-sala2-num"><div><strong>' . (int) $waiting . '</strong><span>' . ( 1 === $waiting ? 'persona esperando' : 'personas esperando' ) . '</span></div><div><strong>Gratis</strong><span>precita de orientación</span></div></div></div>';

	$needs   = array_map( function ( $r ) { return $r['need']; }, ps_rules() );
	$needs[] = 'Otra consulta';
	$pre     = isset( $_GET['ps_motivo'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_motivo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$out    .= '<form class="ps-sala2-chat' . ( $pre ? ' ps-step2' : '' ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_precita">' . wp_nonce_field( 'ps_precita', 'ps_nonce', true, false ) . '<input type="hidden" name="ps_back" value="' . esc_attr( get_permalink() ) . '"><p class="ps-hp" aria-hidden="true"><label>No llenar <input type="text" name="ps_web" tabindex="-1" autocomplete="off"></label></p>';
	$av      = $host ? ps_avatar( $host, 34 ) : '';
	$out    .= '<div class="ps-msg">' . $av . '<p>Hola, soy ' . esc_html( $sal ) . '. ¿Qué te trae hoy por aquí?</p></div><fieldset class="ps-sala2-chips"><legend class="screen-reader-text">Motivo de tu consulta</legend>';
	foreach ( $needs as $i => $n ) {
		$out .= '<label><input type="radio" name="ps_motivo" value="' . esc_attr( $n ) . '" ' . checked( $pre ? $pre : '', $n, false ) . ( 0 === $i ? ' required' : '' ) . '><span>' . esc_html( $n ) . '</span></label>';
	}
	$out .= '</fieldset><div class="ps-sala2-step"><div class="ps-msg">' . $av . '<p>Déjame tu nombre y cómo contactarte, y te llamo en cuanto sea tu turno.</p></div>';
	$out .= '<div class="ps-sala2-fields"><label><span>Tu nombre</span><input type="text" name="ps_nombre" required autocomplete="name"></label><label><span>Teléfono</span><input name="ps_tel" type="tel" required autocomplete="tel" placeholder="+56 9 …"></label><label><span>Correo</span><input name="ps_email" type="email" required autocomplete="email"></label><label class="ps-sala2-wide"><span>Cuéntanos brevemente (opcional)</span><textarea name="ps_msg" rows="2" maxlength="600"></textarea></label></div>';
	$out .= '<p class="ps-consent"><label><input type="checkbox" name="ps_ok" value="1" required> Acepto que Prevsoul use estos datos, incluida información de salud que comparta, solo para orientarme y contactarme, según la <a href="' . esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politica-de-privacidad/' ) ) . '">política de privacidad</a>.</label></p>';
	$out .= '<button class="ps-btn ps-btn-lg ps-sala2-go" type="submit">Entrar a la sala de espera</button><p class="ps-muted ps-small">La precita es orientación informativa: no reemplaza una consulta ni un diagnóstico presencial.</p></div></form></div>';
	return $out . ps_inicio_js();
}

/* -------------------------------------------------------------------------
 * Prevsoul Live «cine»
 * ---------------------------------------------------------------------- */
function ps_yt_id( $url ) {
	return preg_match( '~(?:v=|youtu\.be/|/live/|/embed/|/shorts/)([A-Za-z0-9_-]{11})~', (string) $url, $m ) ? $m[1] : '';
}
function ps_live_cine() {
	if ( ! function_exists( 'ps_live_visible' ) || ! ps_live_visible() ) {
		return '';
	}
	$main = ps_live_main();
	if ( ! $main ) {
		return '';
	}
	$estado = get_post_meta( $main->ID, '_ps_estado', true );
	$fecha  = get_post_meta( $main->ID, '_ps_fecha', true );
	$host   = (int) get_post_meta( $main->ID, '_ps_odontologo', true );
	$url    = home_url( '/prevsoul-live/' );
	$out    = '<section class="ps-sec ps-bg-oscuro ps-cine" id="live"><div class="ps-wrap">' . ( ps_live_on() ? '' : '<p class="ps-cine-hidden">Prevsoul Live está oculto: solo tú y los dentistas ven esta sección.</p>' ) . '<div class="ps-cine-grid"><div>';
	$out   .= 'en-vivo' === $estado ? '<span class="ps-live-badge is-live" style="position:static"><i></i>EN VIVO AHORA</span>' : '<span class="ps-cine-lb">' . ( 'programada' === $estado ? 'Próximo Prevsoul Live' : 'Última transmisión' ) . '</span>';
	$out   .= '<h2 class="ps-cine-title">' . esc_html( $main->post_title ) . '</h2>';
	if ( $host ) {
		$out .= '<div class="ps-row ps-cine-host">' . ps_avatar( $host, 32 ) . '<span>Con ' . esc_html( get_the_title( $host ) ) . ( $fecha ? ' · ' . esc_html( ps_fmt_date( $fecha ) ) : '' ) . '</span></div>';
	}
	if ( 'programada' === $estado && $fecha && strtotime( $fecha ) ) {
		$iso  = wp_date( 'c', ( new DateTime( $fecha, wp_timezone() ) )->getTimestamp() );
		$out .= '<div class="ps-cd" data-cd="' . esc_attr( $iso ) . '"><div><b data-d>00</b><small>días</small></div><div><b data-h>00</b><small>horas</small></div><div><b data-m>00</b><small>min</small></div><div><b data-s>00</b><small>seg</small></div></div>';
		$n    = count( ps_inscritos( $main->ID ) );
		$out .= '<div class="ps-cine-act">' . ps_recordarme_form( $main->ID, true ) . ( $n ? '<span class="ps-cine-n">' . (int) $n . ( 1 === $n ? ' persona ya se inscribió' : ' personas ya se inscribieron' ) . '</span>' : '' ) . '</div>';
	} else {
		$out .= '<p><a class="ps-btn ps-btn-lg" href="' . esc_url( $url ) . '">' . ( 'en-vivo' === $estado ? 'Ver ahora' : 'Ver la grabación' ) . '</a></p>';
	}
	$out .= '</div><div class="ps-cine-recs"><h3>Mira las anteriores</h3>';
	$prev = get_posts( array( 'post_type' => 'ps_transmision', 'post_status' => 'publish', 'numberposts' => 3, 'post__not_in' => array( $main->ID ), 'meta_key' => '_ps_fecha', 'orderby' => 'meta_value', 'order' => 'DESC', 'meta_query' => array( array( 'key' => '_ps_estado', 'value' => 'finalizada' ) ) ) );
	if ( $prev ) {
		foreach ( $prev as $r ) {
			$yt   = ps_yt_id( get_post_meta( $r->ID, '_ps_youtube', true ) );
			$out .= '<a class="ps-cine-rec" href="' . esc_url( $url ) . '"><span class="ps-cine-th">' . ( $yt ? '<img src="' . esc_url( 'https://i.ytimg.com/vi/' . $yt . '/mqdefault.jpg' ) . '" alt="" loading="lazy">' : '' ) . '<i>▶</i></span><span><strong>' . esc_html( $r->post_title ) . '</strong><small>' . esc_html( wp_date( 'j \d\e F Y', strtotime( (string) get_post_meta( $r->ID, '_ps_fecha', true ) ) ) ) . '</small></span></a>';
		}
	} else {
		$out .= '<p class="ps-cine-empty">Aquí aparecerán las grabaciones de las transmisiones anteriores.</p>';
	}
	return $out . '<p><a class="ps-cine-all" href="' . esc_url( $url ) . '">Ir a Prevsoul Live →</a></p></div></div></div></section>' . ps_inicio_js();
}

/* -------------------------------------------------------------------------
 * Planes en tabla de comparación
 * ---------------------------------------------------------------------- */
function ps_planes() {
	$ps = function_exists( 'wc_get_products' ) ? ps_sorted_products( 'planes', 6 ) : array();
	if ( ! $ps ) {
		return '<p class="ps-muted">Pronto publicaremos los planes.</p>';
	}
	usort(
		$ps,
		function ( $a, $b ) {
			$pa = (float) $a->get_price();
			$pb = (float) $b->get_price();
			return ( $pa ? $pa : PHP_INT_MAX ) <=> ( $pb ? $pb : PHP_INT_MAX );
		}
	);
	$top = null;
	foreach ( $ps as $p ) {
		if ( '' !== $p->get_price() && ( ! $top || $p->get_total_sales() > $top->get_total_sales() ) ) {
			$top = $p;
		}
	}
	if ( $top && 0 === (int) $top->get_total_sales() ) {
		$fam = wc_get_product_id_by_sku( 'PLAN-FAM' );
		foreach ( $ps as $p ) {
			if ( $p->get_id() === (int) $fam ) {
				$top = $p;
			}
		}
	}
	$icons = array(
		'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c0-4 3.6-7 8-7s8 3 8 7',
		'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c0-3.5 3-6 7-6s7 2.5 7 6M17 3.5a4 4 0 0 1 0 7.5M22 21c0-2.8-1.8-5-4.5-5.7',
		'M3 21h18M5 21V7l7-4 7 4v14M9 9h1M14 9h1M9 13h1M14 13h1M10 21v-4h4v4',
	);
	$out = '<div class="ps-plans">';
	foreach ( $ps as $i => $p ) {
		$hl   = $top && $p->get_id() === $top->get_id();
		$has  = '' !== $p->get_price();
		$out .= '<article class="ps-plan' . ( $hl ? ' is-top' : '' ) . '">' . ( $hl ? '<span class="ps-plan-top">El más elegido</span>' : '' );
		$out .= '<span class="ps-plan-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $icons[ min( $i, 2 ) ] ) . '"/></svg></span>';
		$out .= '<h3>' . esc_html( preg_replace( '/^Plan( Preventivo)?\s*/u', '', $p->get_name() ) ) . '</h3>';
		$out .= $has ? '<div class="ps-plan-price">' . esc_html( ps_clp( $p->get_price() ) ) . ' <small>/ mes</small></div>' : '<div class="ps-plan-price">A tu medida</div>';
		$lines = ps_product_lines( $p, 5 );
		if ( $lines ) {
			$out .= '<ul>';
			foreach ( $lines as $l ) {
				$out .= '<li>' . esc_html( $l ) . '</li>';
			}
			$out .= '</ul>';
		}
		if ( $p->get_review_count() ) {
			$out .= ps_dientes_html( (float) $p->get_average_rating(), (int) $p->get_review_count(), 14 );
		}
		$out .= $has ? '<a class="ps-btn' . ( $hl ? '' : ' ps-btn-ghost' ) . '" href="' . esc_url( $p->add_to_cart_url() ) . '" rel="nofollow">Contratar</a>' : '<a class="ps-btn ps-btn-ghost" href="' . esc_url( add_query_arg( 'ps_motivo', 'Plan para organización', ps_get( 'sala_url' ) ) ) . '">Solicitar cotización</a>';
		$out .= '<a class="ps-plan-more" href="' . esc_url( $p->get_permalink() ) . '">Ver detalle</a></article>';
	}
	return $out . '</div>';
}

/* -------------------------------------------------------------------------
 * Tags del buscador (Prevsoul → recuadro)
 * ---------------------------------------------------------------------- */
add_action(
	'admin_post_ps_tags',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_tags' ) ) {
			wp_die( 'No permitido.' );
		}
		foreach ( array( 'sientes', 'trat' ) as $k ) {
			if ( isset( $_POST[ 'ps_tags_' . $k ] ) ) {
				update_option( 'ps_tags_' . $k, sanitize_textarea_field( wp_unslash( $_POST[ 'ps_tags_' . $k ] ) ) );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=prevsoul&ps_tags=1#ps-tags' ) );
		exit;
	}
);
function ps_tags_card() {
	echo '<h2 id="ps-tags">Tags del buscador</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_tags', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_tags"><table class="form-table" role="presentation">';
	echo '<tr><th><label for="ps_tags_sientes">Lo que sientes</label></th><td><textarea class="large-text" rows="5" id="ps_tags_sientes" name="ps_tags_sientes">' . esc_textarea( implode( "\n", ps_tags( 'sientes' ) ) ) . '</textarea><p class="description">Una necesidad por línea, como la diría un paciente.</p></td></tr>';
	echo '<tr><th><label for="ps_tags_trat">Tratamientos</label></th><td><textarea class="large-text" rows="5" id="ps_tags_trat" name="ps_tags_trat">' . esc_textarea( implode( "\n", ps_tags( 'trat' ) ) ) . '</textarea><p class="description">Un tratamiento por línea. Revisa en «Búsquedas sin resultado» qué escribe la gente para sumar tags nuevos.</p></td></tr>';
	echo '</table>';
	submit_button( 'Guardar tags' );
	echo '</form>';
}

/* -------------------------------------------------------------------------
 * Nuevo orden del inicio (una sola vez; se guarda una copia del anterior)
 * ---------------------------------------------------------------------- */
function ps_inicio2_content() {
	return ps_sc( '[ps_hero titulo="Habla ahora con un odontólogo real" bajada="Orientación dental gratuita y planes preventivos con odontólogo de cabecera." boton="Entrar a la sala de espera" boton_url="#sala" boton2="Ver Prevsoul Live" boton2_url="/prevsoul-live/"]' )
		. ps_sc( '[ps_seccion fondo="blanco"][ps_buscador][/ps_seccion]' )
		. ps_sc( '[ps_seccion fondo="claro" titulo="Atención dental" bajada="Las mejor valoradas por nuestros pacientes."][ps_carrusel tipo="atencion" ver_todo="/atencion-dental/"][/ps_seccion]' )
		. ps_sc( '[ps_seccion id="sala" fondo="blanco" titulo="Sala de espera" bajada="Una precita gratuita con un odontólogo real, desde donde estés."][ps_sala_espera][/ps_seccion]' )
		. ps_sc( '[ps_seccion fondo="claro" titulo="Packs dentales" bajada="Todo lo que necesitas, en un solo pack."][ps_carrusel tipo="packs" ver_todo="/packs-dentales/"][/ps_seccion]' )
		. ps_sc( '[ps_live_cine]' )
		. ps_sc( '[ps_seccion fondo="blanco" titulo="Planes dentales" bajada="Tu odontólogo de cabecera todo el año. Elige el plan para ti."][ps_planes][/ps_seccion]' )
		. ps_sc( '[ps_seccion fondo="claro" titulo="Con prevención, tu boca y tu bolsillo ganan" bajada="Compara lo que inviertes en un plan preventivo con lo que podría costarte tratar después."][ps_ahorro][/ps_seccion]' )
		. ps_sc( '[ps_seccion fondo="blanco" titulo="Tus odontólogos de cabecera" bajada="Profesionales reales, valorados por pacientes."][ps_odontologos limite="6"][/ps_seccion]' )
		. ps_sc( '[ps_cta titulo="Empieza a prevenir hoy" bajada="Tu primera precita es gratis." boton="Hablar con un odontólogo" url="#sala"]' );
}
add_action(
	'init',
	function () {
		if ( get_option( 'ps_inicio2' ) ) {
			return;
		}
		update_option( 'ps_inicio2', 1 );
		$page = get_page_by_path( 'inicio' );
		if ( ! $page ) {
			$front = (int) get_option( 'page_on_front' );
			$page  = $front ? get_post( $front ) : null;
		}
		if ( ! $page || false === strpos( $page->post_content, '[ps_hero' ) ) {
			return;
		}
		update_post_meta( $page->ID, '_ps_inicio_anterior', $page->post_content );
		wp_update_post( array( 'ID' => $page->ID, 'post_content' => ps_inicio2_content() ) );
	},
	60
);
