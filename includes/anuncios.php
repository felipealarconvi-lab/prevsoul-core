<?php
/**
 * Anuncios: carrusel de la portada (reemplaza la caja azul de [ps_hero] cuando hay anuncios visibles)
 * y rebaja automática del producto vinculado durante las fechas del anuncio.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PS_ANUNCIO_COLORS = array(
	'sala'  => 'Verde · sala de espera',
	'promo' => 'Amarillo · oferta',
	'brand' => 'Azul · marca',
);

add_action(
	'init',
	function () {
		register_post_type(
			'ps_anuncio',
			array(
				'labels'        => array(
					'name'          => 'Anuncios',
					'singular_name' => 'Anuncio',
					'add_new'       => 'Añadir anuncio',
					'add_new_item'  => 'Añadir anuncio',
					'edit_item'     => 'Editar anuncio',
					'all_items'     => 'Todos los anuncios',
				),
				'public'        => false,
				'show_ui'       => true,
				'menu_icon'     => 'dashicons-megaphone',
				'menu_position' => 24,
				'supports'      => array( 'title' ),
			)
		);
	}
);

/* Primer anuncio: el mensaje actual de la sala de espera. */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_anuncios_seed' ) ) {
			return;
		}
		update_option( 'ps_anuncios_seed', 1 );
		if ( get_posts( array( 'post_type' => 'ps_anuncio', 'numberposts' => 1, 'post_status' => 'any', 'fields' => 'ids' ) ) ) {
			return;
		}
		$id = wp_insert_post( array( 'post_type' => 'ps_anuncio', 'post_status' => 'publish', 'post_title' => 'Habla ahora con un odontólogo real' ) );
		if ( $id && ! is_wp_error( $id ) ) {
			$m = array(
				'texto'  => 'Orientación dental gratuita y planes preventivos con odontólogo de cabecera.',
				'color'  => 'sala',
				'b1'     => 'Entrar a la sala de espera',
				'b1_url' => '#sala',
				'b2'     => 'Ver Prevsoul Live',
				'b2_url' => '/prevsoul-live/',
				'orden'  => 1,
				'activo' => '1',
			);
			foreach ( $m as $k => $v ) {
				update_post_meta( $id, '_ps_' . $k, $v );
			}
		}
	},
	25
);

function ps_anuncio_meta( $id, $k ) {
	return get_post_meta( $id, '_ps_' . $k, true );
}
/** visible | programado | vencido | inactivo */
function ps_anuncio_estado( $id ) {
	if ( 'publish' !== get_post_status( $id ) || '1' !== (string) ps_anuncio_meta( $id, 'activo' ) ) {
		return 'inactivo';
	}
	$hoy   = wp_date( 'Y-m-d' );
	$desde = ps_anuncio_meta( $id, 'desde' );
	$hasta = ps_anuncio_meta( $id, 'hasta' );
	if ( $desde && $hoy < $desde ) {
		return 'programado';
	}
	if ( $hasta && $hoy > $hasta ) {
		return 'vencido';
	}
	return 'visible';
}
function ps_anuncios_visibles() {
	$ids = get_posts( array( 'post_type' => 'ps_anuncio', 'post_status' => 'publish', 'numberposts' => 30, 'fields' => 'ids' ) );
	$ids = array_values( array_filter( $ids, function ( $id ) { return 'visible' === ps_anuncio_estado( $id ); } ) );
	usort(
		$ids,
		function ( $a, $b ) {
			return (int) ps_anuncio_meta( $a, 'orden' ) <=> (int) ps_anuncio_meta( $b, 'orden' );
		}
	);
	return $ids;
}

/* -------------------------------------------------------------------------
 * Carrusel en la portada
 * ---------------------------------------------------------------------- */
function ps_slider_html( $ids ) {
	$out = '<section class="ps-sec ps-bg-oscuro ps-hero ps-slider" aria-roledescription="carrusel" aria-label="Novedades Prevsoul"><div class="ps-slider-track">';
	foreach ( $ids as $n => $id ) {
		$m     = function ( $k ) use ( $id ) {
			return ps_anuncio_meta( $id, $k );
		};
		$color = $m( 'color' ) ? $m( 'color' ) : 'brand';
		$chip  = '';
		if ( 'sala' === $color && ! $m( 'etiqueta' ) ) {
			$on   = ps_online_count();
			$chip = $on ? '<span class="ps-chip ps-chip-live"><i class="ps-sala-dot"></i>Sala de espera abierta · ' . (int) $on . ( 1 === $on ? ' odontólogo disponible' : ' odontólogos disponibles' ) . '</span>' : '<span class="ps-chip ps-chip-live is-closed"><i class="ps-sala-dot off"></i>Deja tu consulta · te respondemos pronto</span>';
		} elseif ( $m( 'etiqueta' ) ) {
			$chip = '<span class="ps-chip ps-slide-chip ps-chip-' . esc_attr( $color ) . '">' . ( 'sala' === $color ? '<i class="ps-sala-dot"></i>' : '' ) . esc_html( $m( 'etiqueta' ) ) . '</span>';
		}
		$tag   = 0 === $n ? 'h1' : 'h2';
		$price = '';
		$b1url = $m( 'b1_url' );
		$pid   = (int) $m( 'producto' );
		$prod  = ( $pid && function_exists( 'wc_get_product' ) ) ? wc_get_product( $pid ) : null;
		if ( $prod ) {
			$reg  = (float) $prod->get_regular_price();
			$cur  = (float) $prod->get_price();
			$mes  = in_array( 'planes-dentales', ps_parent_slugs( $prod ), true ) || has_term( 'planes-dentales', 'product_cat', $pid ) ? ' <span class="ps-slide-per">/ mes</span>' : '';
			if ( $cur > 0 ) {
				$price = '<div class="ps-slide-price">' . esc_html( ps_clp( $cur ) ) . ( $reg > $cur ? '<s>' . esc_html( ps_clp( $reg ) ) . '</s>' : '' ) . $mes . '</div>';
			}
			if ( ! $b1url ) {
				$b1url = add_query_arg( 'add-to-cart', $pid, wc_get_cart_url() );
			}
		}
		$img = (int) $m( 'imagen' );
		$out .= '<div class="ps-slide" role="group" aria-roledescription="anuncio" aria-label="' . (int) ( $n + 1 ) . ' de ' . count( $ids ) . '"' . ( $n ? ' aria-hidden="true"' : '' ) . '><div class="ps-wrap ps-slide-in"><div class="ps-slide-tx">' . $chip;
		$out .= '<' . $tag . ' class="ps-slide-title">' . esc_html( get_the_title( $id ) ) . '</' . $tag . '>';
		$out .= $m( 'texto' ) ? '<p class="ps-hero-sub">' . esc_html( $m( 'texto' ) ) . '</p>' : '';
		$out .= $price . '<div class="ps-actions">';
		if ( $m( 'b1' ) && $b1url ) {
			$out .= '<a class="ps-btn ps-btn-lg" href="' . esc_url( $b1url ) . '">' . esc_html( $m( 'b1' ) ) . '</a>';
		}
		if ( $m( 'b2' ) && $m( 'b2_url' ) ) {
			$out .= '<a class="ps-btn ps-btn-outline-light ps-btn-lg" href="' . esc_url( $m( 'b2_url' ) ) . '">' . esc_html( $m( 'b2' ) ) . '</a>';
		}
		$out .= '</div></div>';
		if ( $img ) {
			$out .= '<div class="ps-slide-art">' . wp_get_attachment_image( $img, 'large', false, array( 'loading' => $n ? 'lazy' : 'eager', 'alt' => '' ) ) . '</div>';
		}
		$out .= '</div></div>';
	}
	$out .= '</div>';
	if ( count( $ids ) > 1 ) {
		$out .= '<button class="ps-slider-nav ps-prev" type="button" aria-label="Anuncio anterior">‹</button><button class="ps-slider-nav ps-next" type="button" aria-label="Anuncio siguiente">›</button><div class="ps-slider-dots">';
		foreach ( $ids as $n => $id ) {
			$out .= '<button type="button" aria-label="Ir al anuncio ' . (int) ( $n + 1 ) . '"' . ( 0 === $n ? ' class="on" aria-current="true"' : '' ) . '></button>';
		}
		$out .= '</div>';
	}
	$out .= '</section>';
	static $js = false;
	if ( ! $js && count( $ids ) > 1 ) {
		$js   = true;
		$out .= '<script>' . ps_slider_js() . '</script>';
	}
	return $out;
}
function ps_slider_js() {
	return "(function(){document.querySelectorAll('.ps-slider').forEach(function(s){var tr=s.querySelector('.ps-slider-track'),sl=tr.children,n=sl.length,d=s.querySelectorAll('.ps-slider-dots button'),i=0,t=null,rm=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;function go(k){i=(k+n)%n;tr.style.transform='translateX('+(-100*i)+'%)';for(var j=0;j<n;j++){sl[j].setAttribute('aria-hidden',j===i?'false':'true');if(d[j]){d[j].classList.toggle('on',j===i);d[j].toggleAttribute('aria-current',j===i)}}}function play(){stop();if(!rm)t=setInterval(function(){go(i+1)},6000)}function stop(){if(t){clearInterval(t);t=null}}s.querySelector('.ps-prev').onclick=function(){go(i-1);play()};s.querySelector('.ps-next').onclick=function(){go(i+1);play()};d.forEach(function(b,k){b.onclick=function(){go(k);play()}});s.addEventListener('mouseenter',stop);s.addEventListener('mouseleave',play);s.addEventListener('focusin',stop);var x=null;s.addEventListener('touchstart',function(e){x=e.touches[0].clientX;stop()},{passive:true});s.addEventListener('touchend',function(e){if(x!==null){var dx=e.changedTouches[0].clientX-x;if(Math.abs(dx)>40)go(i+(dx<0?1:-1));x=null}play()});document.addEventListener('visibilitychange',function(){document.hidden?stop():play()});go(0);play()})})();";
}

/* -------------------------------------------------------------------------
 * Ficha del anuncio en el panel
 * ---------------------------------------------------------------------- */
add_action(
	'add_meta_boxes_ps_anuncio',
	function () {
		add_meta_box( 'ps_anuncio_box', 'Contenido del anuncio', 'ps_anuncio_box', 'ps_anuncio', 'normal', 'high' );
		add_meta_box( 'ps_anuncio_pub', 'Cuándo se muestra', 'ps_anuncio_pub_box', 'ps_anuncio', 'side', 'high' );
		add_meta_box( 'ps_anuncio_prev', 'Vista previa', 'ps_anuncio_prev_box', 'ps_anuncio', 'normal', 'default' );
	}
);
function ps_anuncio_box( $post ) {
	wp_nonce_field( 'ps_anuncio', 'ps_anuncio_nonce' );
	$m   = function ( $k ) use ( $post ) {
		return ps_anuncio_meta( $post->ID, $k );
	};
	$row = function ( $label, $html, $hint = '' ) {
		echo '<tr><th>' . wp_kses_post( $label ) . '</th><td>' . $html . ( $hint ? '<p class="description">' . wp_kses_post( $hint ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	};
	echo '<p class="description">El <strong>título</strong> del anuncio es el texto grande. Escríbelo arriba, en «Añadir el título».</p><table class="form-table" role="presentation">';
	$row( '<label for="ps_a_texto">Texto</label>', '<textarea class="large-text" rows="3" id="ps_a_texto" name="ps_a[texto]">' . esc_textarea( $m( 'texto' ) ) . '</textarea>' );
	$opts = '';
	foreach ( PS_ANUNCIO_COLORS as $k => $l ) {
		$opts .= '<option value="' . esc_attr( $k ) . '" ' . selected( $m( 'color' ) ? $m( 'color' ) : 'brand', $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	$row( '<label for="ps_a_etiqueta">Etiqueta</label>', '<input class="regular-text" id="ps_a_etiqueta" name="ps_a[etiqueta]" value="' . esc_attr( $m( 'etiqueta' ) ) . '" placeholder="Ej: Oferta de noviembre"> <select id="ps_a_color" name="ps_a[color]">' . $opts . '</select>', 'Con el color verde y la etiqueta vacía se muestra el estado de la sala de espera en vivo.' );
	$src = $m( 'imagen' ) ? wp_get_attachment_image_url( (int) $m( 'imagen' ), 'medium' ) : '';
	$row( 'Imagen', '<div class="ps-img-field"><img src="' . esc_url( (string) $src ) . '" alt="" style="width:160px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #dcdcde;vertical-align:middle;' . ( $src ? '' : 'display:none;' ) . '"> <input type="hidden" name="ps_a[imagen]" value="' . esc_attr( $m( 'imagen' ) ) . '"> <button type="button" class="button ps-img-pick">Elegir imagen</button> <button type="button" class="button-link ps-img-clear"' . ( $m( 'imagen' ) ? '' : ' style="display:none"' ) . '>Quitar</button></div>', 'Opcional. Va a la derecha en computador y arriba en el celular. Ideal: 800 × 600 px.' );
	$prods = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'limit' => 200, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) ) : array();
	$sel   = '<option value="">— Ninguno —</option>';
	foreach ( $prods as $p ) {
		$sel .= '<option value="' . (int) $p->get_id() . '" data-price="' . esc_attr( (float) $p->get_regular_price() ) . '" ' . selected( (int) $m( 'producto' ), $p->get_id(), false ) . '>' . esc_html( $p->get_name() . ( $p->get_regular_price() ? ' · ' . ps_clp( $p->get_regular_price() ) : '' ) ) . '</option>';
	}
	$row( '<label for="ps_a_producto">Producto vinculado</label>', '<select id="ps_a_producto" name="ps_a[producto]">' . $sel . '</select>', 'Opcional. Muestra el precio y, si el botón 1 no tiene enlace, lo lleva directo al carro con ese producto.' );
	$row( '<label for="ps_a_oferta">Precio de oferta</label>', '<input type="number" min="0" step="1" id="ps_a_oferta" name="ps_a[oferta]" value="' . esc_attr( $m( 'oferta' ) ) . '" placeholder="Ej: 39990">', '<strong>Rebaja el producto de verdad</strong> durante las fechas del anuncio: la tienda cobra este precio y muestra el normal tachado. Al vencer, desactivar o borrar el anuncio, el producto vuelve a su precio.' );
	$row( '<label for="ps_a_b1">Botón 1</label>', '<input id="ps_a_b1" name="ps_a[b1]" value="' . esc_attr( $m( 'b1' ) ) . '" placeholder="Comprar ahora"> <input class="regular-text" name="ps_a[b1_url]" value="' . esc_attr( $m( 'b1_url' ) ) . '" placeholder="Enlace (vacío = al carro con el producto)">' );
	$row( '<label for="ps_a_b2">Botón 2</label>', '<input id="ps_a_b2" name="ps_a[b2]" value="' . esc_attr( $m( 'b2' ) ) . '" placeholder="Ver detalles"> <input class="regular-text" name="ps_a[b2_url]" value="' . esc_attr( $m( 'b2_url' ) ) . '" placeholder="/packs-dentales/">' );
	echo '</table>';
}
function ps_anuncio_pub_box( $post ) {
	$m      = function ( $k ) use ( $post ) {
		return ps_anuncio_meta( $post->ID, $k );
	};
	$activo = 'auto-draft' === $post->post_status ? '1' : $m( 'activo' );
	echo '<p><label for="ps_a_desde"><strong>Mostrar desde</strong></label><br><input type="date" id="ps_a_desde" name="ps_a[desde]" value="' . esc_attr( $m( 'desde' ) ) . '" style="width:100%"></p>';
	echo '<p><label for="ps_a_hasta"><strong>Mostrar hasta</strong></label><br><input type="date" id="ps_a_hasta" name="ps_a[hasta]" value="' . esc_attr( $m( 'hasta' ) ) . '" style="width:100%"></p>';
	echo '<p class="description">Vacío = siempre.</p>';
	echo '<p><label for="ps_a_orden"><strong>Orden en el carrusel</strong></label><br><input type="number" min="1" id="ps_a_orden" name="ps_a[orden]" value="' . esc_attr( $m( 'orden' ) ? $m( 'orden' ) : 10 ) . '" style="width:80px"></p>';
	echo '<p><label><input type="checkbox" name="ps_a[activo]" value="1" ' . checked( $activo, '1', false ) . '> Activo</label></p>';
}
function ps_anuncio_prev_box() {
	echo '<div id="ps-a-prev" style="background:#0B2545;color:#fff;border-radius:10px;padding:22px;font-family:\'Plus Jakarta Sans\',system-ui,sans-serif;display:flex;gap:16px;align-items:center"><div style="flex:1;min-width:0"><span id="pa-chip" style="display:inline-block;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700"></span><div id="pa-t" style="font-size:26px;font-weight:800;line-height:1.1;margin:10px 0 6px"></div><div id="pa-d" style="color:#D3E0F5;font-size:14px;margin-bottom:10px"></div><div id="pa-p" style="font-size:22px;font-weight:800;margin-bottom:10px"></div><span id="pa-b1" style="display:inline-block;background:#1D5FD1;border-radius:8px;padding:8px 12px;font-size:13px;font-weight:800;margin-right:6px"></span><span id="pa-b2" style="display:inline-block;border:1.5px solid #fff;border-radius:8px;padding:7px 11px;font-size:13px;font-weight:800"></span></div><img id="pa-img" alt="" style="width:160px;height:120px;object-fit:cover;border-radius:10px"></div>';
}
add_action(
	'admin_footer-post.php',
	'ps_anuncio_prev_js'
);
add_action(
	'admin_footer-post-new.php',
	'ps_anuncio_prev_js'
);
function ps_anuncio_prev_js() {
	$s = get_current_screen();
	if ( ! $s || 'ps_anuncio' !== $s->post_type ) {
		return;
	}
	?>
	<script>
	(function(){var C={sala:['#fff','#157347'],promo:['#FFD166','#5A3A00'],brand:['#E8F0FC','#1D5FD1']};
	function q(s){return document.querySelector(s)}function v(n){var e=q('[name="'+n+'"]');return e?e.value:''}
	function clp(n){return '$'+Math.round(n).toLocaleString('es-CL')}
	function up(){var col=v('ps_a[color]')||'brand',ch=q('#pa-chip'),et=v('ps_a[etiqueta]');
	ch.textContent=et||(col==='sala'?'Sala de espera abierta':'');ch.style.display=ch.textContent?'inline-block':'none';ch.style.background=C[col][0];ch.style.color=C[col][1];
	q('#pa-t').textContent=(q('#title')||{}).value||'Título del anuncio';q('#pa-d').textContent=v('ps_a[texto]');
	var o=q('#ps_a_producto'),opt=o&&o.options[o.selectedIndex],reg=opt?parseFloat(opt.getAttribute('data-price')||0):0,of=parseFloat(v('ps_a[oferta]')||0),p=q('#pa-p');
	p.innerHTML=reg?(of&&of<reg?clp(of)+' <s style="font-size:14px;color:#9FB2CC">'+clp(reg)+'</s>':clp(reg)):'';
	[['#pa-b1','ps_a[b1]'],['#pa-b2','ps_a[b2]']].forEach(function(b){var e=q(b[0]);e.textContent=v(b[1]);e.style.display=e.textContent?'inline-block':'none'});
	var im=q('.ps-img-field img'),pi=q('#pa-img');pi.src=im&&im.style.display!=='none'?im.src:'';pi.style.display=pi.src&&pi.getAttribute('src')?'block':'none'}
	document.addEventListener('input',up);document.addEventListener('change',up);document.addEventListener('click',function(){setTimeout(up,300)});up();})();
	</script>
	<?php
}

add_action(
	'save_post_ps_anuncio',
	function ( $post_id ) {
		if ( ! isset( $_POST['ps_anuncio_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_anuncio_nonce'] ), 'ps_anuncio' ) || ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$in    = isset( $_POST['ps_a'] ) && is_array( $_POST['ps_a'] ) ? wp_unslash( $_POST['ps_a'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$clean = array(
			'texto'    => sanitize_textarea_field( $in['texto'] ?? '' ),
			'etiqueta' => sanitize_text_field( $in['etiqueta'] ?? '' ),
			'color'    => isset( PS_ANUNCIO_COLORS[ $in['color'] ?? '' ] ) ? $in['color'] : 'brand',
			'imagen'   => ! empty( $in['imagen'] ) ? (string) absint( $in['imagen'] ) : '',
			'producto' => ! empty( $in['producto'] ) ? (string) absint( $in['producto'] ) : '',
			'oferta'   => isset( $in['oferta'] ) && '' !== $in['oferta'] ? (string) absint( $in['oferta'] ) : '',
			'b1'       => sanitize_text_field( $in['b1'] ?? '' ),
			'b1_url'   => ps_clean_link( $in['b1_url'] ?? '' ),
			'b2'       => sanitize_text_field( $in['b2'] ?? '' ),
			'b2_url'   => ps_clean_link( $in['b2_url'] ?? '' ),
			'desde'    => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in['desde'] ?? '' ) ? $in['desde'] : '',
			'hasta'    => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in['hasta'] ?? '' ) ? $in['hasta'] : '',
			'orden'    => max( 1, absint( $in['orden'] ?? 10 ) ),
			'activo'   => empty( $in['activo'] ) ? '' : '1',
		);
		foreach ( $clean as $k => $v ) {
			update_post_meta( $post_id, '_ps_' . $k, $v );
		}
		ps_anuncio_sync_price( $post_id );
	},
	20
);
function ps_clean_link( $u ) {
	$u = trim( (string) $u );
	if ( '' === $u ) {
		return '';
	}
	if ( 0 === strpos( $u, '#' ) || 0 === strpos( $u, '/' ) ) {
		return sanitize_text_field( $u );
	}
	return esc_url_raw( $u );
}

/* -------------------------------------------------------------------------
 * Rebaja automática del producto vinculado
 * ---------------------------------------------------------------------- */
function ps_anuncio_clear_price( $anuncio_id ) {
	$pid = (int) get_post_meta( $anuncio_id, '_ps_rebaja_en', true );
	if ( ! $pid ) {
		return;
	}
	$p = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
	if ( $p && (int) $p->get_meta( '_ps_rebaja_anuncio' ) === (int) $anuncio_id ) {
		$p->set_sale_price( '' );
		$p->set_date_on_sale_from( null );
		$p->set_date_on_sale_to( null );
		$p->delete_meta_data( '_ps_rebaja_anuncio' );
		$p->save();
	}
	delete_post_meta( $anuncio_id, '_ps_rebaja_en' );
}
function ps_anuncio_sync_price( $id ) {
	ps_anuncio_clear_price( $id );
	$pid    = (int) ps_anuncio_meta( $id, 'producto' );
	$oferta = (float) ps_anuncio_meta( $id, 'oferta' );
	$estado = ps_anuncio_estado( $id );
	if ( ! $pid || $oferta <= 0 || in_array( $estado, array( 'inactivo', 'vencido' ), true ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$p = wc_get_product( $pid );
	if ( ! $p || ! $p->is_type( 'simple' ) || $oferta >= (float) $p->get_regular_price() ) {
		return;
	}
	$p->set_sale_price( (string) $oferta );
	$desde = ps_anuncio_meta( $id, 'desde' );
	$hasta = ps_anuncio_meta( $id, 'hasta' );
	$p->set_date_on_sale_from( $desde ? $desde . ' 00:00:00' : null );
	$p->set_date_on_sale_to( $hasta ? $hasta . ' 23:59:59' : null );
	$p->update_meta_data( '_ps_rebaja_anuncio', $id );
	$p->save();
	update_post_meta( $id, '_ps_rebaja_en', $pid );
}
add_action(
	'wp_trash_post',
	function ( $id ) {
		if ( 'ps_anuncio' === get_post_type( $id ) ) {
			ps_anuncio_clear_price( $id );
		}
	}
);
add_action(
	'before_delete_post',
	function ( $id ) {
		if ( 'ps_anuncio' === get_post_type( $id ) ) {
			ps_anuncio_clear_price( $id );
		}
	}
);
add_action(
	'untrashed_post',
	function ( $id ) {
		if ( 'ps_anuncio' === get_post_type( $id ) ) {
			ps_anuncio_sync_price( $id );
		}
	}
);

/* -------------------------------------------------------------------------
 * Lista de anuncios
 * ---------------------------------------------------------------------- */
add_filter(
	'manage_ps_anuncio_posts_columns',
	function () {
		return array( 'cb' => '<input type="checkbox">', 'ps_orden' => 'Orden', 'title' => 'Anuncio', 'ps_fechas' => 'Fechas', 'ps_estado' => 'Estado', 'ps_rebaja' => 'Rebaja' );
	}
);
add_action(
	'manage_ps_anuncio_posts_custom_column',
	function ( $col, $id ) {
		switch ( $col ) {
			case 'ps_orden':
				echo (int) ps_anuncio_meta( $id, 'orden' );
				break;
			case 'ps_fechas':
				$d = ps_anuncio_meta( $id, 'desde' );
				$h = ps_anuncio_meta( $id, 'hasta' );
				echo esc_html( ! $d && ! $h ? 'Siempre' : ( $d ? 'desde ' . wp_date( 'j M', strtotime( $d ) ) : '' ) . ( $d && $h ? ' ' : '' ) . ( $h ? 'hasta ' . wp_date( 'j M', strtotime( $h ) ) : '' ) );
				break;
			case 'ps_estado':
				$e = ps_anuncio_estado( $id );
				$c = array( 'visible' => array( '#E3F4EA', '#157347', 'Visible' ), 'programado' => array( '#FFF4D6', '#8A5A00', 'Programado' ), 'vencido' => array( '#F0F0F1', '#646970', 'Vencido' ), 'inactivo' => array( '#F0F0F1', '#646970', 'Inactivo' ) );
				echo '<span style="display:inline-block;border-radius:99px;padding:1px 10px;font-weight:600;background:' . esc_attr( $c[ $e ][0] ) . ';color:' . esc_attr( $c[ $e ][1] ) . '">' . esc_html( $c[ $e ][2] ) . '</span>';
				break;
			case 'ps_rebaja':
				$pid = (int) get_post_meta( $id, '_ps_rebaja_en', true );
				echo $pid ? esc_html( get_the_title( $pid ) . ' a ' . ps_clp( ps_anuncio_meta( $id, 'oferta' ) ) ) : '—';
				break;
		}
	},
	10,
	2
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() && $q->is_main_query() && 'ps_anuncio' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
			$q->set( 'meta_key', '_ps_orden' );
			$q->set( 'orderby', 'meta_value_num' );
			$q->set( 'order', 'ASC' );
		}
	}
);
