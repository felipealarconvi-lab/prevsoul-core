<?php
/**
 * Máquina de piezas: cada producto genera solo su slide del inicio, su cuadrado
 * para redes y su historia, con el estilo de la marca (palabra héroe escrita a
 * mano, frase, pincelada y color por familia).
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

const PS_PZ_FONTS = 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@800&family=Quicksand:wght@500;700&family=Caveat+Brush&family=Homemade+Apple&family=Space+Mono&display=swap';

function ps_pz_familias() {
	return array(
		'celeste'    => array( '#7BA7D9', 'Celeste · atenciones' ),
		'rosa'       => array( '#EDA0A8', 'Rosa · niños y familia' ),
		'lima'       => array( '#CFDC82', 'Lima · rutina y estética' ),
		'periwinkle' => array( '#4E5BA6', 'Azul · planes y urgencia' ),
		'oliva'      => array( '#ABB672', 'Oliva · adultos y grupos' ),
	);
}

/** Lo que definió el equipo creativo para los productos actuales (por inicio del título). */
function ps_pz_semillas() {
	return array(
		'Consulta y diagnóstico'     => array( 'Conversemos', 'Primero te escuchamos, después decidimos juntos', 'celeste' ),
		'Radiografía panorámica'     => array( 'A la vista', 'Ver todo antes de decidir nada', 'celeste' ),
		'Limpieza dental'            => array( 'Liviano', 'Esa sensación de salir nuevo', 'celeste' ),
		'Aplicación de flúor'        => array( 'Fuerte', 'Un hábito chico que suma', 'rosa' ),
		'Sellantes'                  => array( 'Escudo', 'Sus dientes nuevos, bien cuidados desde el inicio', 'rosa' ),
		'Blanqueamiento'             => array( 'Brillo', 'Tu sonrisa, con más ganas de mostrarse', 'lima' ),
		'Pack Control + Limpieza'    => array( 'Al día', 'Una cosa menos en tu lista', 'lima' ),
		'Pack Niños'                 => array( 'Valientes', 'Sin miedo y con ganas de volver', 'rosa' ),
		'Pack Evaluación de Dolor'   => array( 'Alivio', 'Cuando duele, no esperes solo', 'periwinkle' ),
		'Pack Encías'                => array( 'Firme', 'Cuidar lo que sostiene tu sonrisa', 'oliva' ),
		'Plan Preventivo Individual' => array( 'Tranqui', 'Tu año dental resuelto, mes a mes', 'periwinkle' ),
		'Plan Preventivo Familiar'   => array( 'Yo también', 'Tú cuidas a todos. Nosotros nos acordamos por ti', 'rosa' ),
		'Plan Colectivo'             => array( 'Juntos', 'Cuidarse en equipo sale mejor', 'oliva' ),
	);
}

function ps_pz_es_plan( $prod ) {
	$pid = $prod->get_id();
	return has_term( PS_CATS['planes'], 'product_cat', $pid ) || in_array( PS_CATS['planes'], ps_parent_slugs( $prod ), true );
}

/** Precio que va en la pincelada cuando el texto lleva {precio}. */
function ps_pz_precio( $prod ) {
	$pid = $prod->get_id();
	if ( function_exists( 'ps_es_familiar' ) && ps_es_familiar( $pid ) ) {
		return 'desde ' . ps_clp( ps_fam_total( 2 ) ) . ' al mes';
	}
	$p = (float) $prod->get_price();
	if ( $p <= 0 ) {
		return '';
	}
	return ps_clp( $p ) . ( ps_pz_es_plan( $prod ) ? ' al mes' : '' );
}

/** Sugerencia automática para un producto recién armado. */
function ps_pz_sugerido( $prod ) {
	$t = $prod->get_name();
	foreach ( ps_pz_semillas() as $k => $v ) {
		if ( 0 === mb_stripos( $t, $k ) ) {
			return array( 'heroe' => $v[0], 'frase' => $v[1], 'pincel' => '{precio}', 'familia' => $v[2] );
		}
	}
	$pid  = $prod->get_id();
	$all  = array_merge( ps_parent_slugs( $prod ), wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'slugs' ) ) );
	$fam  = 'celeste';
	$heroe = 'Al día';
	if ( preg_match( '/niñ|infantil|kids/iu', $t ) ) {
		$fam   = 'rosa';
		$heroe = 'Valientes';
	} elseif ( preg_match( '/famil/iu', $t ) ) {
		$fam   = 'rosa';
		$heroe = 'Yo también';
	} elseif ( preg_match( '/dolor|urgenc/iu', $t ) ) {
		$fam   = 'periwinkle';
		$heroe = 'Alivio';
	} elseif ( in_array( PS_CATS['planes'], $all, true ) ) {
		$fam   = 'periwinkle';
		$heroe = 'Tranqui';
	} elseif ( in_array( PS_CATS['packs'], $all, true ) ) {
		$fam = 'lima';
	}
	$para  = trim( (string) get_post_meta( $pid, '_ps_para', true ) );
	$frase = ( $para && count( preg_split( '/\s+/', $para ) ) <= 12 ) ? rtrim( $para, '.' ) : 'Así se siente elegir bien';
	return array( 'heroe' => $heroe, 'frase' => $frase, 'pincel' => '{precio}', 'familia' => $fam );
}

/** Datos de la pieza (guardados o sugeridos). */
function ps_pz_get( $prod ) {
	$s = get_post_meta( $prod->get_id(), '_ps_pz', true );
	$d = ps_pz_sugerido( $prod );
	if ( is_array( $s ) && ! empty( $s['heroe'] ) ) {
		$d         = array_merge( $d, array_intersect_key( $s, $d ) );
		$d['auto'] = false;
	} else {
		$d['auto'] = true;
	}
	$d['slider'] = is_array( $s ) && ! empty( $s['slider'] );
	if ( ! isset( ps_pz_familias()[ $d['familia'] ] ) ) {
		$d['familia'] = 'celeste';
	}
	return $d;
}


/** Texto del botón según el tipo de producto (reemplaza al antiguo «PREVSOUL»). */
function ps_pz_cta( $prod ) {
	$all = array_merge( ps_parent_slugs( $prod ), wp_get_post_terms( $prod->get_id(), 'product_cat', array( 'fields' => 'slugs' ) ) );
	if ( in_array( PS_CATS['planes'], $all, true ) ) {
		return '' === $prod->get_price() ? 'Cotizar plan' : 'Contratar plan';
	}
	if ( in_array( PS_CATS['packs'], $all, true ) ) {
		return 'Ver pack';
	}
	return 'Agendar';
}

/** Logo nuevo (diente con escudo + prevsoul) para firmar piezas. */
function ps_pz_logo( $color = '#fff', $ring = '#0B1A2E' ) {
	$svg = function_exists( 'ps_logo_svg' ) ? ps_logo_svg( 'header', $color, $ring, 'base' ) : '';
	return '<span class="ps-pz-firma" style="color:' . esc_attr( $color ) . '">' . $svg . '<span class="ps-wm"><span class="ps-wm-p">prev</span><span class="ps-wm-s">soul</span></span></span>';
}

/** Datos listos para dibujar. */
function ps_pz_js( $prod ) {
	$d   = ps_pz_get( $prod );
	$img = $prod->get_image_id() ? wp_get_attachment_image_url( $prod->get_image_id(), 'full' ) : '';
	return array(
		'id'      => $prod->get_id(),
		'nombre'  => $prod->get_name(),
		'heroe'   => $d['heroe'],
		'frase'   => $d['frase'],
		'pincel'  => trim( str_replace( '{precio}', ps_pz_precio( $prod ), $d['pincel'] ) ),
		'familia' => $d['familia'],
		'img'     => $img ? $img : '',
		'cta'     => ps_pz_cta( $prod ),
	);
}

/* -------------------------------------------------------------------------
 * Panel: recuadro en la ficha del producto
 * ---------------------------------------------------------------------- */
function ps_pz_admin_css() {
	return '.ps-pz-editor{display:grid;grid-template-columns:minmax(220px,300px) 1fr;gap:20px}.ps-pz-editor label{display:block;font-weight:600;margin:10px 0 4px}.ps-pz-editor input[type=text]{width:100%}.ps-pz-fams{display:flex;flex-wrap:wrap;gap:6px}.ps-pz-fams label{display:flex;align-items:center;gap:6px;margin:0;font-weight:400;border:1px solid #dcdcde;border-radius:99px;padding:3px 10px 3px 4px;cursor:pointer}.ps-pz-fams i{width:18px;height:18px;border-radius:50%;display:inline-block}.ps-pz-fams input{display:none}.ps-pz-fams input:checked+i{outline:2px solid #1d2327;outline-offset:1px}.ps-pz-prev{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start}.ps-pz-prev canvas{border-radius:8px;display:block;background:#f0f0f1}.ps-pz-prev figure{margin:0}.ps-pz-prev figcaption{font-size:12px;color:#646970;margin-top:4px}.ps-pz-warn{margin:8px 0;padding:8px 10px 8px 26px;background:#fcf0e3;border-radius:6px;color:#8a4b00}.ps-pz-ok{color:#1a7f37;margin:8px 0}.ps-pz-btns{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.ps-pz-hint{color:#646970;font-size:12px;margin:4px 0 0}.ps-pz-gallery{display:grid;gap:16px;margin-top:16px}.ps-pz-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 16px}.ps-pz-card h3{margin:0 0 8px;font-size:15px}@media(max-width:900px){.ps-pz-editor{grid-template-columns:1fr}}';
}
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$scr = get_current_screen();
		$ok  = ( $scr && in_array( $scr->post_type, array( 'product', 'ps_anuncio' ), true ) && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) || ( isset( $_GET['page'] ) && 'ps-piezas' === $_GET['page'] ); // phpcs:ignore
		if ( ! $ok ) {
			return;
		}
		wp_enqueue_style( 'ps-pz-fonts', PS_PZ_FONTS, array(), null ); // phpcs:ignore
		wp_register_style( 'ps-pz-admin', false, array(), PS_VERSION );
		wp_enqueue_style( 'ps-pz-admin' );
		wp_add_inline_style( 'ps-pz-admin', ps_pz_admin_css() );
		wp_enqueue_script( 'ps-piezas', PS_URL . 'assets/piezas.js', array(), PS_VERSION, true );
	}
);

add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box( 'ps_pz_box', 'Pieza Prevsoul · slider, redes e historia', 'ps_pz_box', 'product', 'normal', 'high' );
	}
);
function ps_pz_box( $post ) {
	$prod = wc_get_product( $post->ID );
	if ( ! $prod ) {
		echo '<p>Guarda el producto para armar sus piezas.</p>';
		return;
	}
	$d    = ps_pz_get( $prod );
	$js   = ps_pz_js( $prod );
	$base = array(
		'nombre'  => $js['nombre'],
		'img'     => $js['img'],
		'precio'  => ps_pz_precio( $prod ),
		'familia' => $d['familia'],
		'cta'     => $js['cta'],
	);
	wp_nonce_field( 'ps_pz_save', 'ps_pz_nonce' );
	echo '<div class="ps-pz-editor" data-pz="' . esc_attr( wp_json_encode( $base ) ) . '"><div>';
	if ( $d['auto'] ) {
		echo '<p class="ps-pz-hint" style="margin-top:0"><b>Sugerencia automática.</b> Ajústala si quieres; al guardar queda fija para este producto.</p>';
	}
	echo '<label for="ps-pz-h">Palabra héroe</label><input type="text" id="ps-pz-h" name="ps_pz[heroe]" maxlength="24" value="' . esc_attr( $d['heroe'] ) . '">';
	echo '<p class="ps-pz-hint">1 a 3 palabras, escritas a mano en la pieza (Valientes, Alivio, Tranqui…).</p>';
	echo '<label for="ps-pz-f">Frase</label><input type="text" id="ps-pz-f" name="ps_pz[frase]" maxlength="90" value="' . esc_attr( $d['frase'] ) . '">';
	echo '<p class="ps-pz-hint">Máximo 12 palabras, voz de amigo, sin promesas clínicas.</p>';
	echo '<label for="ps-pz-p">Pincelada</label><input type="text" id="ps-pz-p" name="ps_pz[pincel]" maxlength="40" value="' . esc_attr( $d['pincel'] ) . '">';
	echo '<p class="ps-pz-hint"><code>{precio}</code> pone el precio vigente solo (' . esc_html( ps_pz_precio( $prod ) ? ps_pz_precio( $prod ) : 'sin precio aún' ) . ').</p>';
	echo '<label>Familia de color</label><div class="ps-pz-fams">';
	foreach ( ps_pz_familias() as $k => $f ) {
		echo '<label title="' . esc_attr( $f[1] ) . '"><input type="radio" name="ps_pz[familia]" value="' . esc_attr( $k ) . '"' . checked( $d['familia'], $k, false ) . '><i style="background:' . esc_attr( $f[0] ) . '"></i>' . esc_html( strtok( $f[1], ' ' ) ) . '</label>';
	}
	echo '</div>';
	echo '<label style="font-weight:400;margin-top:14px"><input type="checkbox" name="ps_pz[slider]" value="1"' . checked( $d['slider'], true, false ) . '> Mostrar en el slider del inicio</label>';
	if ( ! $d['auto'] ) {
		echo '<label style="font-weight:400"><input type="checkbox" name="ps_pz_reset" value="1"> Volver a la sugerencia automática</label>';
	}
	echo '<div class="ps-pz-audit"></div></div><div>';
	echo '<div class="ps-pz-prev"><figure><canvas data-fmt="cuadrado" style="width:200px"></canvas><figcaption>Redes 1080 × 1080</figcaption></figure><figure><canvas data-fmt="historia" style="width:113px"></canvas><figcaption>Historia 1080 × 1920</figcaption></figure></div>';
	echo '<figure style="margin:12px 0 0"><canvas data-fmt="slide" style="width:100%;max-width:640px;border-radius:8px;display:block"></canvas><figcaption class="ps-pz-hint">Slide del inicio 1600 × 600 (en el sitio se arma en vivo con el mismo diseño)</figcaption></figure>';
	echo '<div class="ps-pz-btns"><button class="button button-primary" data-dl="todo">Descargar las 3 piezas</button><button class="button" data-dl="cuadrado">Cuadrado</button><button class="button" data-dl="historia">Historia</button><button class="button" data-dl="slide">Slide</button></div>';
	echo '<p class="ps-pz-hint">La foto es la imagen del producto: usa personas con luz natural, sin texto encima. Los cambios se ven al instante; se guardan con «Actualizar».</p>';
	echo '</div></div>';
}
add_action(
	'save_post_product',
	function ( $pid ) {
		if ( ! isset( $_POST['ps_pz_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_pz_nonce'] ), 'ps_pz_save' ) || ! current_user_can( 'edit_post', $pid ) || wp_is_post_revision( $pid ) ) {
			return;
		}
		$in     = isset( $_POST['ps_pz'] ) ? (array) wp_unslash( $_POST['ps_pz'] ) : array(); // phpcs:ignore
		$slider = ! empty( $in['slider'] );
		if ( ! empty( $_POST['ps_pz_reset'] ) ) {
			update_post_meta( $pid, '_ps_pz', array( 'slider' => $slider ) );
			return;
		}
		$fam = isset( $in['familia'] ) ? sanitize_key( $in['familia'] ) : 'celeste';
		update_post_meta(
			$pid,
			'_ps_pz',
			array(
				'heroe'   => sanitize_text_field( isset( $in['heroe'] ) ? $in['heroe'] : '' ),
				'frase'   => sanitize_text_field( isset( $in['frase'] ) ? $in['frase'] : '' ),
				'pincel'  => sanitize_text_field( isset( $in['pincel'] ) ? $in['pincel'] : '' ),
				'familia' => isset( ps_pz_familias()[ $fam ] ) ? $fam : 'celeste',
				'slider'  => $slider,
			)
		);
	}
);

/* -------------------------------------------------------------------------
 * Panel: galería con las piezas de todos los productos
 * ---------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Piezas de productos', 'Piezas', 'manage_options', 'ps-piezas', 'ps_pz_page' );
	},
	20
);
function ps_pz_page() {
	$prods = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 100, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
	$list  = array();
	foreach ( $prods as $p ) {
		$list[] = ps_pz_js( $p );
	}
	echo '<div class="wrap"><h1>Piezas de productos</h1><p>Cada producto arma solo su slide, su cuadrado para redes y su historia con el estilo Prevsoul. Para cambiar textos o color, edita el producto (recuadro «Pieza Prevsoul»).</p>';
	echo '<p><button class="button button-primary" id="ps-pz-all">Descargar todas las piezas</button></p>';
	echo '<div class="ps-pz-gallery" data-pzs="' . esc_attr( wp_json_encode( $list ) ) . '">';
	foreach ( $list as $k => $d ) {
		$pz = ps_pz_get( wc_get_product( $d['id'] ) );
		echo '<div class="ps-pz-card" data-k="' . (int) $k . '"><h3>' . esc_html( $d['nombre'] ) . ' <span class="ps-pz-hint">· ' . ( $pz['auto'] ? 'sugerencia automática' : 'ajustada' ) . ( $pz['slider'] ? ' · en el slider' : '' ) . '</span></h3>';
		echo '<div class="ps-pz-prev"><canvas data-fmt="cuadrado" style="width:170px"></canvas><canvas data-fmt="historia" style="width:96px"></canvas><canvas data-fmt="slide" style="width:453px;max-width:100%"></canvas></div>';
		echo '<div class="ps-pz-audit"></div><div class="ps-pz-btns"><button class="button" data-dl="todo">Descargar las 3</button><a class="button-link" href="' . esc_url( get_edit_post_link( $d['id'] ) ) . '">Editar textos</a></div></div>';
	}
	echo '</div></div>';
}

/* -------------------------------------------------------------------------
 * Sitio: slide del inicio armado en vivo
 * ---------------------------------------------------------------------- */
/** Productos marcados para el slider que no tienen ya un anuncio propio. */
function ps_pz_slider_extra( $ads ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$ya = array();
	foreach ( $ads as $a ) {
		$ya[] = (int) ps_anuncio_meta( $a, 'producto' );
	}
	$out = array();
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 30, 'fields' => 'ids', 'orderby' => 'menu_order', 'order' => 'ASC', 'meta_key' => '_ps_pz' ) ); // phpcs:ignore
	foreach ( $ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		$d = ps_pz_get( $p );
		if ( $d['slider'] && ! in_array( $p->get_id(), $ya, true ) ) {
			$out[] = $p->get_id();
		}
	}
	return $out;
}
function ps_pz_en_slider( $prod ) {
	return $prod && ps_pz_get( $prod )['slider'];
}

/** Palabra héroe y color sugeridos para un anuncio sin producto. */
function ps_pz_anuncio_sugerido( $id ) {
	$t = get_the_title( $id ) . ' ' . ps_anuncio_meta( $id, 'texto' ) . ' ' . ps_anuncio_meta( $id, 'b1_url' );
	if ( preg_match( '/niñ|cepill|infantil/iu', $t ) ) {
		return array( 'A cepillar', 'rosa' );
	}
	if ( preg_match( '/famil/iu', $t ) ) {
		return array( 'Yo también', 'rosa' );
	}
	if ( preg_match( '/consult|odont[oó]log|sala|habla/iu', $t ) ) {
		return array( 'Hablemos', 'celeste' );
	}
	if ( preg_match( '/dolor|urgenc/iu', $t ) ) {
		return array( 'Alivio', 'periwinkle' );
	}
	return array( 'Elige bien', 'periwinkle' );
}

/** Datos del slide para un anuncio del carrusel (con o sin producto vinculado). */
function ps_pz_anuncio_data( $id ) {
	$m    = function ( $k ) use ( $id ) {
		return ps_anuncio_meta( $id, $k );
	};
	$pid  = (int) $m( 'producto' );
	$prod = ( $pid && function_exists( 'wc_get_product' ) ) ? wc_get_product( $pid ) : null;
	$url  = $m( 'b1_url' );
	if ( $prod ) {
		$j = ps_pz_js( $prod );
		$d = array(
			'nombre'  => $j['nombre'],
			'heroe'   => $j['heroe'],
			'frase'   => $j['frase'],
			'pincel'  => $j['pincel'],
			'familia' => $j['familia'],
			'img'     => $m( 'imagen' ) ? (int) $m( 'imagen' ) : (int) $prod->get_image_id(),
		);
		$url = $url ? $url : get_permalink( $pid );
	} else {
		$sug = ps_pz_anuncio_sugerido( $id );
		$d   = array(
			'nombre'  => '',
			'sub'     => $m( 'texto' ),
			'heroe'   => $sug[0],
			'frase'   => get_the_title( $id ),
			'pincel'  => '',
			'familia' => $sug[1],
			'img'     => (int) $m( 'imagen' ),
		);
	}
	if ( $m( 'pz_heroe' ) ) {
		$d['heroe'] = $m( 'pz_heroe' );
	}
	if ( $m( 'pz_familia' ) && isset( ps_pz_familias()[ $m( 'pz_familia' ) ] ) ) {
		$d['familia'] = $m( 'pz_familia' );
	}
	$chip = '';
	if ( 'sala' === $m( 'color' ) && ! $m( 'etiqueta' ) ) {
		$on   = ps_online_count();
		$chip = $on ? 'Sala de espera abierta · ' . $on . ( 1 === $on ? ' odontólogo disponible' : ' odontólogos disponibles' ) : 'Deja tu consulta · te respondemos pronto';
	} elseif ( $m( 'etiqueta' ) ) {
		$chip = $m( 'etiqueta' );
	}
	$d['chip']  = $chip;
	$d['live']  = 'sala' === $m( 'color' ) && ! $m( 'etiqueta' ) && ps_online_count() > 0;
	$d['url']   = $url ? $url : ps_get( 'sala_url' );
	$d['boton'] = $m( 'b1' ) ? $m( 'b1' ) : ( $prod ? ps_pz_cta( $prod ) : 'Ver más' );
	return $d;
}

function ps_pz_slide_html( $prod, $url, $tag ) {
	$j = ps_pz_js( $prod );
	$j['img']   = (int) $prod->get_image_id();
	$j['url']   = $url ? $url : get_permalink( $prod->get_id() );
	$j['boton'] = $j['cta'];
	$j['chip']  = '';
	return ps_pz_slide_render( $j, $tag );
}
function ps_pz_slide_render( $d, $tag ) {
	$fam = ps_pz_familias()[ isset( ps_pz_familias()[ $d['familia'] ] ) ? $d['familia'] : 'celeste' ];
	$len = mb_strlen( $d['heroe'] );
	$o   = '<div class="ps-pz ps-pz-' . esc_attr( $d['familia'] ) . '" style="--pz:' . esc_attr( $fam[0] ) . '">';
	if ( ! empty( $d['img'] ) ) {
		$o .= '<div class="ps-pz-foto">' . wp_get_attachment_image( (int) $d['img'], 'large', false, array( 'alt' => '', 'loading' => 'eager' ) ) . '</div>';
	}
	$o .= '<div class="ps-pz-tx">';
	$o .= ! empty( $d['chip'] ) ? '<span class="ps-pz-chip">' . ( ! empty( $d['live'] ) ? '<i class="ps-sala-dot"></i>' : '' ) . esc_html( $d['chip'] ) . '</span>' : '';
	$o .= $d['frase'] ? '<p class="ps-pz-frase">“' . esc_html( $d['frase'] ) . '”</p>' : '';
	$o .= ! empty( $d['sub'] ) ? '<p class="ps-pz-sub">' . esc_html( $d['sub'] ) . '</p>' : '';
	$o .= $d['nombre'] ? '<span class="ps-pz-eti">' . esc_html( mb_strtolower( $d['nombre'] ) ) . '</span>' : '';
	$o .= $d['pincel'] ? '<span class="ps-pz-pincel">' . esc_html( $d['pincel'] ) . '</span>' : '';
	$o .= '</div><' . $tag . ' class="ps-pz-heroe"' . ( $len > 8 ? ' style="font-size:' . ( $len > 11 ? '8' : '10' ) . 'cqw"' : '' ) . '>' . esc_html( $d['heroe'] ) . '<span class="screen-reader-text"> · ' . esc_html( $d['frase'] ) . '</span></' . $tag . '>';
	$claro = in_array( $d['familia'], array( 'celeste', 'lima' ), true );
	$o    .= '<span class="ps-pz-logo">' . ps_pz_logo( $claro ? '#0B2545' : '#fff', $fam[0] ) . '</span>';
	$o    .= '<a class="ps-pz-btn ps-pz-btn-tx" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['boton'] ) . '</a></div>';
	return $o . ps_pz_fonts_once();
}

/** Carga las tipografías de las piezas una sola vez por página. */
function ps_pz_fonts_once() {
	static $f = false;
	if ( $f ) {
		return '';
	}
	$f = true;
	return '<link rel="stylesheet" href="' . esc_url( PS_PZ_FONTS ) . '">'; // phpcs:ignore
}

/** Cuadrado con el estilo de la marca para las tarjetas de productos. */
function ps_pz_square_html( $prod ) {
	$d   = ps_pz_js( $prod );
	$fam = ps_pz_familias()[ $d['familia'] ];
	$len = mb_strlen( $d['heroe'] );
	$o   = '<a class="ps-pzq ps-pz-' . esc_attr( $d['familia'] ) . '" href="' . esc_url( $prod->get_permalink() ) . '" style="--pz:' . esc_attr( $fam[0] ) . '" aria-label="' . esc_attr( $d['nombre'] ) . '" tabindex="-1">';
	if ( $prod->get_image_id() ) {
		$o .= wp_get_attachment_image( $prod->get_image_id(), 'woocommerce_single', false, array( 'alt' => '', 'loading' => 'lazy', 'class' => 'ps-pzq-img' ) );
	}
	$o .= '<span class="ps-pzq-in"><span class="ps-pzq-h"' . ( $len > 8 ? ' style="font-size:' . ( $len > 11 ? '14' : '17' ) . 'cqw"' : '' ) . '>' . esc_html( $d['heroe'] ) . '</span>';
	$o .= $d['pincel'] ? '<span class="ps-pzq-p">' . esc_html( $d['pincel'] ) . '</span>' : '';
	$o .= '</span><span class="ps-pzq-l">' . ps_pz_logo( '#fff', '#0B1A2E' ) . '</span></a>';
	return $o . ps_pz_fonts_once();
}
function ps_pz_frase( $prod ) {
	$d = ps_pz_get( $prod );
	return $d['frase'] ? '<p class="ps-pz-cita">“' . esc_html( $d['frase'] ) . '”</p>' : '';
}

/* Tienda y categorías de WooCommerce: el cuadrado reemplaza la foto sola. */
add_action(
	'init',
	function () {
		if ( ! function_exists( 'woocommerce_template_loop_product_thumbnail' ) ) {
			return;
		}
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		add_action(
			'woocommerce_before_shop_loop_item_title',
			function () {
				global $product;
				if ( $product ) {
					echo ps_pz_square_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
			},
			10
		);
	},
	20
);
