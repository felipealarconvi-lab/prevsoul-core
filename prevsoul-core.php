<?php
/**
 * Plugin Name:       Prevsoul Core
 * Description:       Odontólogos, clínicas, Prevsoul Live, sala de espera (solicitud de precitas), catálogo por categorías, gráfico de ahorro y buscador de necesidades para prevsoul.cl.
 * Version:           1.6.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Prevsoul
 * Text Domain:       prevsoul
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PS_VERSION', '1.6.0' );
define( 'PS_FILE', __FILE__ );
define( 'PS_URL', plugin_dir_url( __FILE__ ) );
require_once __DIR__ . '/includes/updater.php';
require_once __DIR__ . '/includes/prestadores.php';
require_once __DIR__ . '/includes/demo.php';
require_once __DIR__ . '/includes/anuncios.php';

const PS_RANKS = array(
	0 => 'Asociado',
	1 => 'Odontólogo de cabecera',
	2 => 'Senior',
	3 => 'Embajador Prevsoul',
);
const PS_LIVE_STATES = array(
	'programada' => 'Programada',
	'en-vivo'    => 'En vivo',
	'finalizada' => 'Finalizada',
);
const PS_PRECITA_STATES = array(
	'nueva'      => 'Nueva',
	'contactada' => 'Contactada',
	'atendida'   => 'Precita realizada',
	'convertida' => 'Contrató plan o pack',
	'cerrada'    => 'Cerrada sin compra',
);
const PS_CATS = array(
	'atencion' => 'atencion-dental',
	'packs'    => 'packs-dentales',
	'planes'   => 'planes-dentales',
);

/* -------------------------------------------------------------------------
 * Ajustes por defecto
 * ---------------------------------------------------------------------- */
function ps_default_settings() {
	return array(
		'video_url'    => '',
		'whatsapp'     => '',
		'notify_email' => get_option( 'admin_email' ),
		'sala_url'     => home_url( '/sala-de-espera/' ),
		'monthly'      => 9990,
		'years'        => 5,
		'treatments'   => "Obturación (tapadura) | 45000 | 3\nEndodoncia | 180000 | 1\nCorona | 350000 | 1\nTratamiento periodontal | 250000 | 1\nUrgencia por dolor | 40000 | 2",
		'example'      => 1,
		'rules'        => "Sangrado de encías | sangr, encia, gingiv, inflam | PACK-ENCIAS, AT-LIMPIEZA | El sangrado al cepillarse suele indicar inflamación de encías. Un odontólogo puede orientarte ahora.\nDolor dental | dolor, duele, muela, sensib, punzada | PACK-DOLOR, AT-CONSULTA | El dolor necesita evaluación. Habla con un odontólogo para saber si es urgente.\nSalud bucal de niños | hijo, hija, nino, nina, bebe, infantil, guagua | PACK-NINOS, PLAN-FAM | Prevención para los más pequeños, sin miedo.\nPrevención y controles | control, limpieza, prevenc, chequeo, sarro | PACK-CONTROL, PLAN-IND | Un control a tiempo evita tratamientos más complejos.\nEstética dental | blanque, mancha, amarill, estetic | AT-BLANQ, PACK-CONTROL | Primero una evaluación, luego el tratamiento estético adecuado.\nPlan para organización | empresa, junta, organizacion, sindicato, colectivo, trabajadores | PLAN-COL | Armamos un plan preventivo a la medida de tu organización.",
	);
}

function ps_get( $key ) {
	$opts = wp_parse_args( get_option( 'ps_settings', array() ), ps_default_settings() );
	return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
}

/* -------------------------------------------------------------------------
 * Tipos de contenido
 * ---------------------------------------------------------------------- */
add_action( 'init', 'ps_register_types' );
function ps_register_types() {
	register_post_type(
		'ps_odontologo',
		array(
			'labels'        => array(
				'name'          => 'Odontólogos',
				'singular_name' => 'Odontólogo',
				'add_new'       => 'Agregar odontólogo',
				'add_new_item'  => 'Agregar odontólogo',
				'edit_item'     => 'Editar odontólogo',
				'all_items'     => 'Todos los odontólogos',
			),
			'public'        => true,
			'has_archive'   => 'odontologos',
			'rewrite'       => array( 'slug' => 'odontologo' ),
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 25,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);
	register_post_type(
		'ps_clinica',
		array(
			'labels'        => array(
				'name'          => 'Clínicas',
				'singular_name' => 'Clínica',
				'add_new'       => 'Agregar clínica',
				'add_new_item'  => 'Agregar clínica',
				'edit_item'     => 'Editar clínica',
				'all_items'     => 'Todas las clínicas',
			),
			'public'        => true,
			'has_archive'   => 'clinicas',
			'rewrite'       => array( 'slug' => 'clinica' ),
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 26,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);
	register_post_type(
		'ps_transmision',
		array(
			'labels'        => array(
				'name'          => 'Prevsoul Live',
				'singular_name' => 'Transmisión',
				'add_new'       => 'Agregar transmisión',
				'add_new_item'  => 'Agregar transmisión',
				'edit_item'     => 'Editar transmisión',
				'all_items'     => 'Transmisiones',
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'live' ),
			'menu_icon'     => 'dashicons-video-alt3',
			'menu_position' => 27,
			'supports'      => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'  => true,
		)
	);
	register_post_type(
		'ps_precita',
		array(
			'labels'          => array(
				'name'          => 'Sala de espera',
				'singular_name' => 'Solicitud de precita',
				'all_items'     => 'Solicitudes de precita',
				'edit_item'     => 'Ver solicitud',
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-clock',
			'menu_position'   => 28,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}

/* -------------------------------------------------------------------------
 * Campos (meta boxes)
 * ---------------------------------------------------------------------- */
function ps_fields() {
	return array(
		'ps_odontologo'  => array(
			'titulo'  => array( 'Título profesional', 'text', 'Ej: Cirujano dentista · Odontología general' ),
			'clinica' => array( 'Clínica', 'clinica', '' ),
			'rango'   => array( 'Rango dentro de Prevsoul', 'rank', '' ),
			'activo'  => array( 'Disponible en la sala de espera', 'check', '' ),
			'telefono' => array( 'Teléfono de contacto para pacientes', 'text', '+56 9 …' ),
			'whatsapp' => array( 'WhatsApp', 'text', '56912345678' ),
			'email'    => array( 'Correo (recibe los avisos de tratamientos vendidos)', 'text', 'nombre@correo.cl' ),
			'agenda'   => array( 'Cómo agendar (se muestra al paciente después de comprar)', 'textarea', 'Ej: Escríbenos por WhatsApp con tu número de pedido y te damos hora dentro de 48 horas.' ),
		),
		'ps_clinica'     => array(
			'direccion' => array( 'Dirección', 'text', 'Calle, número, comuna' ),
			'telefono'  => array( 'Teléfono', 'text', '+56 9 …' ),
			'horario'   => array( 'Horario', 'text', 'Ej: Lunes a viernes 9:00 a 19:00' ),
			'mapa'      => array( 'Enlace a Google Maps', 'url', 'https://maps.google.com/…' ),
			'logo'      => array( 'Logo de la clínica', 'image', '' ),
			'whatsapp'  => array( 'WhatsApp', 'text', '56912345678' ),
			'email'     => array( 'Correo (recibe los avisos de tratamientos vendidos)', 'text', 'agenda@clinica.cl' ),
			'agenda'    => array( 'Cómo agendar (se muestra al paciente después de comprar)', 'textarea', 'Ej: Llama al +56 2 … de lunes a viernes, indica tu número de pedido Prevsoul y te agendamos.' ),
		),
		'ps_transmision' => array(
			'fecha'      => array( 'Fecha y hora', 'datetime', '' ),
			'odontologo' => array( 'Odontólogo que transmite', 'odontologo', '' ),
			'publico'    => array( 'Para quién', 'text', 'Ej: Niños y familias' ),
			'youtube'    => array( 'Enlace de YouTube (en vivo o grabado)', 'url', 'https://www.youtube.com/watch?v=…' ),
			'estado'     => array( 'Estado', 'live', '' ),
		),
		'ps_precita'     => array(
			'nombre'   => array( 'Nombre', 'text', '' ),
			'email'    => array( 'Correo', 'text', '' ),
			'telefono' => array( 'Teléfono', 'text', '' ),
			'motivo'   => array( 'Motivo', 'text', '' ),
			'mensaje'  => array( 'Mensaje del paciente', 'textarea', '' ),
			'estado'   => array( 'Estado del caso', 'precita', '' ),
			'notas'    => array( 'Notas internas', 'textarea', '' ),
		),
	);
}

add_action( 'add_meta_boxes', 'ps_add_meta_boxes' );
function ps_add_meta_boxes() {
	foreach ( ps_fields() as $type => $fields ) {
		add_meta_box( 'ps_datos', 'Datos', 'ps_render_meta_box', $type, 'normal', 'high' );
	}
}

function ps_render_meta_box( $post ) {
	$fields = ps_fields()[ $post->post_type ];
	wp_nonce_field( 'ps_save_meta', 'ps_meta_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( $fields as $key => $f ) {
		$id  = 'ps_' . $key;
		$val = get_post_meta( $post->ID, '_ps_' . $key, true );
		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'textarea':
				echo '<textarea class="large-text" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">' . esc_textarea( $val ) . '</textarea>';
				break;
			case 'check':
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="1" ' . checked( $val, '1', false ) . '> Sí</label>';
				break;
			case 'rank':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">';
				foreach ( PS_RANKS as $k => $label ) {
					echo '<option value="' . esc_attr( $k ) . '" ' . selected( (string) $val, (string) $k, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'live':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">';
				foreach ( PS_LIVE_STATES as $k => $label ) {
					echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val ? $val : 'programada', $k, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select><p class="description">Solo una transmisión debería estar «En vivo» a la vez. Al marcar una, las demás en vivo pasan a «Finalizada».</p>';
				break;
			case 'precita':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">';
				foreach ( PS_PRECITA_STATES as $k => $label ) {
					echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val ? $val : 'nueva', $k, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'clinica':
			case 'odontologo':
				$pt    = 'clinica' === $f[1] ? 'ps_clinica' : 'ps_odontologo';
				$items = get_posts(
					array(
						'post_type'   => $pt,
						'numberposts' => 200,
						'post_status' => array( 'publish', 'draft' ),
						'orderby'     => 'title',
						'order'       => 'ASC',
					)
				);
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '"><option value="">— Elegir —</option>';
				foreach ( $items as $it ) {
					echo '<option value="' . esc_attr( $it->ID ) . '" ' . selected( (int) $val, $it->ID, false ) . '>' . esc_html( $it->post_title ) . '</option>';
				}
				echo '</select>';
				break;
			case 'image':
				$src = $val ? wp_get_attachment_image_url( (int) $val, 'thumbnail' ) : '';
				echo '<div class="ps-img-field"><img src="' . esc_url( (string) $src ) . '" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:contain;background:#f0f0f1;border:1px solid #dcdcde;vertical-align:middle;' . ( $src ? '' : 'display:none;' ) . '"> <input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '"> <button type="button" class="button ps-img-pick">Elegir imagen</button> <button type="button" class="button-link ps-img-clear"' . ( $val ? '' : ' style="display:none"' ) . '>Quitar</button><p class="description">Se muestra en un círculo junto a los productos que realiza. Ideal: cuadrado y con fondo blanco.</p></div>';
				break;
			case 'datetime':
				echo '<input type="datetime-local" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '">';
				break;
			case 'url':
				echo '<input type="url" class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $f[2] ) . '">';
				break;
			default:
				echo '<input type="text" class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $f[2] ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	if ( 'ps_odontologo' === $post->post_type ) {
		echo '<p class="description">La <strong>foto profesional</strong> se sube en «Imagen destacada» (columna derecha). La <strong>descripción profesional</strong> va en el texto principal. Las valoraciones llegan como comentarios con estrellas y se aprueban en Comentarios.</p>';
	}
	if ( 'ps_clinica' === $post->post_type ) {
		echo '<p class="description">La <strong>foto de la clínica</strong> se sube en «Imagen destacada»; el <strong>logo</strong> va en el campo «Logo de la clínica».</p>';
	}
}

add_action( 'save_post', 'ps_save_meta', 10, 2 );
function ps_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ps_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_meta_nonce'] ), 'ps_save_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$all = ps_fields();
	if ( empty( $all[ $post->post_type ] ) ) {
		return;
	}
	foreach ( $all[ $post->post_type ] as $key => $f ) {
		$name = 'ps_' . $key;
		if ( 'check' === $f[1] ) {
			update_post_meta( $post_id, '_ps_' . $key, isset( $_POST[ $name ] ) ? '1' : '' );
			continue;
		}
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $name ] );
		switch ( $f[1] ) {
			case 'textarea':
				$v = sanitize_textarea_field( $raw );
				break;
			case 'url':
				$v = esc_url_raw( $raw );
				break;
			case 'clinica':
			case 'odontologo':
			case 'rank':
			case 'image':
				$v = $raw ? (string) absint( $raw ) : '';
				break;
			default:
				$v = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, '_ps_' . $key, $v );
	}
	// Solo una transmisión en vivo a la vez.
	if ( 'ps_transmision' === $post->post_type && 'en-vivo' === get_post_meta( $post_id, '_ps_estado', true ) ) {
		$others = get_posts(
			array(
				'post_type'    => 'ps_transmision',
				'numberposts'  => 50,
				'post__not_in' => array( $post_id ),
				'meta_key'     => '_ps_estado',
				'meta_value'   => 'en-vivo',
				'fields'       => 'ids',
			)
		);
		foreach ( $others as $oid ) {
			update_post_meta( $oid, '_ps_estado', 'finalizada' );
		}
	}
}

/* Columnas útiles en el listado de solicitudes de precita */
add_filter(
	'manage_ps_precita_posts_columns',
	function ( $cols ) {
		return array(
			'cb'       => $cols['cb'],
			'title'    => 'Paciente',
			'ps_motivo' => 'Motivo',
			'ps_contacto' => 'Contacto',
			'ps_estado' => 'Estado',
			'date'     => 'Fecha',
		);
	}
);
add_action(
	'manage_ps_precita_posts_custom_column',
	function ( $col, $id ) {
		if ( 'ps_motivo' === $col ) {
			echo esc_html( get_post_meta( $id, '_ps_motivo', true ) );
		} elseif ( 'ps_contacto' === $col ) {
			echo esc_html( get_post_meta( $id, '_ps_email', true ) ) . '<br>' . esc_html( get_post_meta( $id, '_ps_telefono', true ) );
		} elseif ( 'ps_estado' === $col ) {
			$s = get_post_meta( $id, '_ps_estado', true );
			echo esc_html( isset( PS_PRECITA_STATES[ $s ] ) ? PS_PRECITA_STATES[ $s ] : 'Nueva' );
		}
	},
	10,
	2
);
add_filter(
	'manage_ps_odontologo_posts_columns',
	function ( $cols ) {
		$cols['ps_rango']  = 'Rango';
		$cols['ps_rating'] = 'Valoración';
		return $cols;
	}
);
add_action(
	'manage_ps_odontologo_posts_custom_column',
	function ( $col, $id ) {
		if ( 'ps_rango' === $col ) {
			$r = (int) get_post_meta( $id, '_ps_rango', true );
			echo esc_html( PS_RANKS[ $r ] ?? '' );
		} elseif ( 'ps_rating' === $col ) {
			$r = ps_rating( $id );
			echo $r['count'] ? esc_html( number_format( $r['avg'], 1 ) . ' ★ (' . $r['count'] . ')' ) : '—';
		}
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Valoraciones con estrellas para odontólogos (comentarios moderados)
 * ---------------------------------------------------------------------- */
add_action(
	'comment_form_logged_in_after',
	'ps_rating_field'
);
add_action( 'comment_form_after_fields', 'ps_rating_field' );
function ps_rating_field() {
	if ( 'ps_odontologo' !== get_post_type() ) {
		return;
	}
	echo '<p class="ps-rating-field"><label for="ps_rating">Tu valoración</label><select id="ps_rating" name="ps_rating" required><option value="">Elegir</option>';
	for ( $i = 5; $i >= 1; $i-- ) {
		echo '<option value="' . (int) $i . '">' . esc_html( str_repeat( '★', $i ) . str_repeat( '☆', 5 - $i ) ) . '</option>';
	}
	echo '</select></p>';
}
add_action(
	'comment_post',
	function ( $comment_id ) {
		if ( isset( $_POST['ps_rating'] ) ) {
			$v = max( 1, min( 5, absint( wp_unslash( $_POST['ps_rating'] ) ) ) );
			add_comment_meta( $comment_id, 'ps_rating', $v, true );
		}
	}
);
// Las valoraciones de odontólogos siempre quedan pendientes de moderación.
add_filter(
	'pre_comment_approved',
	function ( $approved, $data ) {
		if ( isset( $data['comment_post_ID'] ) && 'ps_odontologo' === get_post_type( $data['comment_post_ID'] ) && ! current_user_can( 'moderate_comments' ) ) {
			return 0;
		}
		return $approved;
	},
	10,
	2
);
add_filter(
	'comment_text',
	function ( $text, $comment = null ) {
		if ( $comment && 'ps_odontologo' === get_post_type( $comment->comment_post_ID ) ) {
			$r = (int) get_comment_meta( $comment->comment_ID, 'ps_rating', true );
			if ( $r ) {
				$text = '<span class="ps-stars" aria-label="' . esc_attr( $r . ' de 5' ) . '">' . esc_html( str_repeat( '★', $r ) . str_repeat( '☆', 5 - $r ) ) . '</span> ' . $text;
			}
		}
		return $text;
	},
	10,
	2
);

function ps_rating( $post_id ) {
	$comments = get_comments(
		array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'fields'  => 'ids',
		)
	);
	$sum = 0;
	$n   = 0;
	foreach ( $comments as $cid ) {
		$r = (int) get_comment_meta( $cid, 'ps_rating', true );
		if ( $r ) {
			$sum += $r;
			$n++;
		}
	}
	return array(
		'avg'   => $n ? $sum / $n : 0,
		'count' => $n,
	);
}
function ps_stars_html( $avg, $count ) {
	$full = (int) round( $avg );
	$txt  = str_repeat( '★', $full ) . str_repeat( '☆', 5 - $full );
	$lbl  = $count ? sprintf( 'Promedio %s de 5 (%d valoraciones)', number_format( $avg, 1, ',', '' ), $count ) : 'Sin valoraciones aún';
	return '<span class="ps-stars" aria-label="' . esc_attr( $lbl ) . '">' . esc_html( $txt ) . '</span> <span class="ps-muted">(' . (int) $count . ')</span>';
}

/* -------------------------------------------------------------------------
 * Estilos
 * ---------------------------------------------------------------------- */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'prevsoul-font', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap', array(), null );
		wp_enqueue_style( 'prevsoul', PS_URL . 'assets/prevsoul.css', array(), PS_VERSION );
	}
);

/* -------------------------------------------------------------------------
 * Shortcodes
 * ---------------------------------------------------------------------- */
function ps_avatar( $post_id, $size = 64 ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, 'thumbnail', array( 'class' => 'ps-avatar', 'style' => 'width:' . (int) $size . 'px;height:' . (int) $size . 'px', 'alt' => 'Foto de ' . get_the_title( $post_id ) ) );
	}
	$name     = preg_replace( '/^(Dra?\.)\s*/u', '', get_the_title( $post_id ) );
	$parts    = preg_split( '/\s+/u', trim( $name ) );
	$initials = '';
	foreach ( array_slice( $parts, 0, 2 ) as $p ) {
		$initials .= mb_substr( $p, 0, 1 );
	}
	return '<span class="ps-avatar ps-initials" aria-hidden="true" style="width:' . (int) $size . 'px;height:' . (int) $size . 'px">' . esc_html( mb_strtoupper( $initials ) ) . '</span>';
}

/** [ps_odontologos clinica="ID" limite="12"] */
add_shortcode(
	'ps_odontologos',
	function ( $atts ) {
		$a    = shortcode_atts( array( 'clinica' => '', 'limite' => 24 ), $atts );
		$args = array(
			'post_type'   => 'ps_odontologo',
			'numberposts' => (int) $a['limite'],
			'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		);
		if ( $a['clinica'] ) {
			$args['meta_query'] = array( array( 'key' => '_ps_clinica', 'value' => (string) absint( $a['clinica'] ) ) );
		}
		$items = get_posts( $args );
		if ( ! $items ) {
			return '<p class="ps-muted">Pronto presentaremos a nuestro equipo.</p>';
		}
		$out = '<div class="ps-grid">';
		foreach ( $items as $d ) {
			$cl     = (int) get_post_meta( $d->ID, '_ps_clinica', true );
			$rank   = (int) get_post_meta( $d->ID, '_ps_rango', true );
			$active = get_post_meta( $d->ID, '_ps_activo', true );
			$r      = ps_rating( $d->ID );
			$desc   = has_excerpt( $d ) ? get_the_excerpt( $d ) : wp_trim_words( wp_strip_all_tags( $d->post_content ), 28 );
			$out   .= '<article class="ps-card"><div class="ps-row">' . ps_avatar( $d->ID, 64 ) . '<div><h3 class="ps-card-title"><a href="' . esc_url( get_permalink( $d ) ) . '">' . esc_html( $d->post_title ) . '</a></h3>';
			$out   .= '<div class="ps-muted ps-small">' . esc_html( get_post_meta( $d->ID, '_ps_titulo', true ) ) . '</div>';
			$out   .= $cl ? '<div class="ps-muted ps-small">' . esc_html( get_the_title( $cl ) ) . '</div>' : '';
			$out   .= '</div></div><div class="ps-chips"><span class="ps-chip ps-rank-' . (int) $rank . '">' . esc_html( PS_RANKS[ $rank ] ?? '' ) . '</span>' . ( $active ? '<span class="ps-chip ps-good">En la sala de espera</span>' : '' ) . '</div>';
			$out   .= '<p class="ps-small">' . esc_html( $desc ) . '</p><div>' . ps_stars_html( $r['avg'], $r['count'] ) . '</div>';
			$out   .= '<a class="ps-btn ps-btn-ghost" href="' . esc_url( get_permalink( $d ) ) . '#comments">Ver perfil y valorar</a></article>';
		}
		return $out . '</div>';
	}
);

/** [ps_clinicas] */
add_shortcode(
	'ps_clinicas',
	function () {
		$items = get_posts(
			array(
				'post_type'   => 'ps_clinica',
				'numberposts' => 50,
				'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			)
		);
		if ( ! $items ) {
			return '';
		}
		$out = '<div class="ps-grid">';
		foreach ( $items as $c ) {
			$dents = get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 100, 'fields' => 'ids', 'meta_key' => '_ps_clinica', 'meta_value' => (string) $c->ID ) );
			$out  .= '<article class="ps-card">' . ( has_post_thumbnail( $c ) ? get_the_post_thumbnail( $c, 'medium_large', array( 'class' => 'ps-cover', 'alt' => 'Foto de ' . $c->post_title ) ) : '' );
			$out  .= '<h3 class="ps-card-title">' . esc_html( $c->post_title ) . '</h3>';
			$out  .= '<div class="ps-muted ps-small">' . esc_html( get_post_meta( $c->ID, '_ps_direccion', true ) ) . '</div>';
			$h     = get_post_meta( $c->ID, '_ps_horario', true );
			$out  .= $h ? '<div class="ps-small">' . esc_html( $h ) . '</div>' : '';
			$out  .= '<p class="ps-small">' . esc_html( has_excerpt( $c ) ? get_the_excerpt( $c ) : wp_trim_words( wp_strip_all_tags( $c->post_content ), 28 ) ) . '</p>';
			$out  .= '<div class="ps-muted ps-small">' . count( $dents ) . ' odontólogos</div>';
			$map   = get_post_meta( $c->ID, '_ps_mapa', true );
			$out  .= $map ? '<a class="ps-btn ps-btn-ghost" href="' . esc_url( $map ) . '" target="_blank" rel="noopener">Cómo llegar</a>' : '';
			$out  .= '</article>';
		}
		return $out . '</div>';
	}
);

/** Perfil del odontólogo: agrega datos y valoración arriba del contenido. */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_singular( 'ps_odontologo' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id   = get_the_ID();
		$cl   = (int) get_post_meta( $id, '_ps_clinica', true );
		$rank = (int) get_post_meta( $id, '_ps_rango', true );
		$r    = ps_rating( $id );
		$head = '<div class="ps-card ps-profile"><div class="ps-row">' . ps_avatar( $id, 96 ) . '<div><div class="ps-muted">' . esc_html( get_post_meta( $id, '_ps_titulo', true ) ) . '</div>' . ( $cl ? '<div class="ps-muted">' . esc_html( get_the_title( $cl ) ) . '</div>' : '' ) . '<div class="ps-chips"><span class="ps-chip ps-rank-' . (int) $rank . '">' . esc_html( PS_RANKS[ $rank ] ?? '' ) . '</span></div><div>' . ps_stars_html( $r['avg'], $r['count'] ) . '</div></div></div></div>';
		return $head . $content . '<p><a class="ps-btn" href="' . esc_url( ps_get( 'sala_url' ) ) . '">Pedir una precita</a></p>';
	}
);

/** [ps_live] Transmisión en vivo o próxima, con agenda. */
add_shortcode(
	'ps_live',
	function () {
		$live = get_posts( array( 'post_type' => 'ps_transmision', 'numberposts' => 1, 'meta_key' => '_ps_estado', 'meta_value' => 'en-vivo' ) );
		$next = get_posts(
			array(
				'post_type'   => 'ps_transmision',
				'numberposts' => 6,
				'meta_query'  => array( array( 'key' => '_ps_estado', 'value' => 'programada' ) ),
				'meta_key'    => '_ps_fecha',
				'orderby'     => 'meta_value',
				'order'       => 'ASC',
			)
		);
		$main = $live ? $live[0] : ( $next ? array_shift( $next ) : null );
		if ( ! $main ) {
			return '<div class="ps-card"><strong>Pronto anunciaremos la próxima transmisión de Prevsoul Live.</strong></div>';
		}
		$is_live = (bool) $live;
		$yt      = get_post_meta( $main->ID, '_ps_youtube', true );
		$embed   = $yt ? wp_oembed_get( $yt, array( 'width' => 960 ) ) : '';
		$host    = (int) get_post_meta( $main->ID, '_ps_odontologo', true );
		$out     = '<div class="ps-live"><div class="ps-live-player">';
		if ( $embed ) {
			$out .= '<div class="ps-embed">' . $embed . '</div>';
		} else {
			$out .= '<div class="ps-video-placeholder"><span class="ps-chip ' . ( $is_live ? 'ps-crit' : 'ps-acc' ) . '">' . ( $is_live ? 'EN VIVO' : 'Próxima transmisión' ) . '</span><span>' . esc_html( $is_live ? 'Transmisión en curso' : 'Comienza el ' . ps_fmt_date( get_post_meta( $main->ID, '_ps_fecha', true ) ) ) . '</span></div>';
		}
		$out .= '</div><div class="ps-card ps-live-info"><span class="ps-chip ' . ( $is_live ? 'ps-crit' : 'ps-acc' ) . '">' . ( $is_live ? 'Prevsoul Live · en vivo' : 'Próxima · ' . esc_html( ps_fmt_date( get_post_meta( $main->ID, '_ps_fecha', true ) ) ) ) . '</span>';
		$out .= '<h3 class="ps-card-title">' . esc_html( $main->post_title ) . '</h3>';
		if ( $host ) {
			$out .= '<div class="ps-row">' . ps_avatar( $host, 44 ) . '<div><strong>' . esc_html( get_the_title( $host ) ) . '</strong></div></div>';
		}
		$pub  = get_post_meta( $main->ID, '_ps_publico', true );
		$out .= $pub ? '<span class="ps-chip">Para: ' . esc_html( $pub ) . '</span>' : '';
		$out .= '<div class="ps-small">' . wp_kses_post( wpautop( $main->post_content ) ) . '</div>';
		$out .= $yt ? '<a class="ps-btn ps-btn-ghost" href="' . esc_url( $yt ) . '" target="_blank" rel="noopener">Ver y comentar en YouTube</a>' : '';
		$out .= '<a class="ps-btn" href="' . esc_url( ps_get( 'sala_url' ) ) . '">¿Duda sobre tu caso? Ve a la sala de espera</a></div></div>';
		if ( $next ) {
			$out .= '<div class="ps-card ps-agenda"><h3 class="ps-card-title">Próximas transmisiones</h3><ul>';
			foreach ( $next as $n ) {
				$h    = (int) get_post_meta( $n->ID, '_ps_odontologo', true );
				$out .= '<li><span class="ps-date">' . esc_html( ps_fmt_date( get_post_meta( $n->ID, '_ps_fecha', true ) ) ) . '</span><span><strong>' . esc_html( $n->post_title ) . '</strong>' . ( $h ? ' <span class="ps-muted">· ' . esc_html( get_the_title( $h ) ) . '</span>' : '' ) . '</span></li>';
			}
			$out .= '</ul></div>';
		}
		return $out;
	}
);

function ps_fmt_date( $value ) {
	if ( ! $value ) {
		return 'fecha por confirmar';
	}
	$ts = strtotime( $value );
	return $ts ? wp_date( 'l j \d\e F, H:i', $ts, wp_timezone() ) : $value;
}

/** [ps_sala_espera] Solicitud de precita (orientación, no reemplaza la consulta). */
add_shortcode( 'ps_sala_espera', 'ps_sala_shortcode' );
function ps_sala_shortcode() {
	$done = isset( $_GET['ps_precita'] ) && 'ok' === sanitize_key( wp_unslash( $_GET['ps_precita'] ) );
	$dent = get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 12, 'meta_key' => '_ps_activo', 'meta_value' => '1' ) );
	$out  = '<div class="ps-sala">';
	if ( $done ) {
		$video = ps_get( 'video_url' );
		$wa    = preg_replace( '/\D+/', '', (string) ps_get( 'whatsapp' ) );
		$out  .= '<div class="ps-card ps-ok"><h3 class="ps-card-title">¡Listo, ya estás en la sala de espera!</h3><p>Un odontólogo de Prevsoul te contactará para tu precita de orientación.</p>';
		$out  .= $video ? '<p><a class="ps-btn" href="' . esc_url( $video ) . '" target="_blank" rel="noopener">Entrar a la videollamada</a></p>' : '';
		$out  .= $wa ? '<p><a class="ps-btn ps-btn-ghost" href="' . esc_url( 'https://wa.me/' . $wa ) . '" target="_blank" rel="noopener">Escribir por WhatsApp</a> <span class="ps-muted ps-small">+' . esc_html( $wa ) . '</span></p>' : '';
		$out  .= '<p class="ps-muted ps-small">La precita es orientación informativa: no reemplaza una consulta ni un diagnóstico presencial.</p></div></div>';
		return $out;
	}
	$needs = array_map(
		function ( $r ) {
			return $r['need'];
		},
		ps_rules()
	);
	$needs[] = 'Otra consulta';
	$pre     = isset( $_GET['ps_motivo'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_motivo'] ) ) : '';
	$out    .= '<div class="ps-sala-grid"><form class="ps-card ps-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$out    .= '<input type="hidden" name="action" value="ps_precita">' . wp_nonce_field( 'ps_precita', 'ps_nonce', true, false );
	$out    .= '<input type="hidden" name="ps_back" value="' . esc_attr( get_permalink() ) . '">';
	$out    .= '<p class="ps-hp" aria-hidden="true"><label>No llenar <input type="text" name="ps_web" tabindex="-1" autocomplete="off"></label></p>';
	$out    .= '<h3 class="ps-card-title">Entra a la sala de espera</h3>';
	$out    .= '<p><label for="ps_nombre">Tu nombre</label><input id="ps_nombre" name="ps_nombre" type="text" required autocomplete="name"></p>';
	$out    .= '<p class="ps-two"><span><label for="ps_email">Correo</label><input id="ps_email" name="ps_email" type="email" required autocomplete="email"></span><span><label for="ps_tel">Teléfono</label><input id="ps_tel" name="ps_tel" type="tel" required autocomplete="tel" placeholder="+56 9 …"></span></p>';
	$out    .= '<p><label for="ps_motivo">¿Qué te trae hoy?</label><select id="ps_motivo" name="ps_motivo">';
	foreach ( $needs as $n ) {
		$out .= '<option ' . selected( $pre, $n, false ) . '>' . esc_html( $n ) . '</option>';
	}
	$out .= '</select></p><p><label for="ps_msg">Cuéntanos brevemente (opcional)</label><textarea id="ps_msg" name="ps_msg" rows="3" maxlength="600"></textarea></p>';
	$out .= '<p class="ps-consent"><label><input type="checkbox" name="ps_ok" value="1" required> Acepto que Prevsoul use estos datos, incluida información de salud que comparta, solo para orientarme y contactarme, según la <a href="' . esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politica-de-privacidad/' ) ) . '">política de privacidad</a>.</label></p>';
	$out .= '<p><button class="ps-btn" type="submit">Entrar a la sala de espera</button></p><p class="ps-muted ps-small">La precita es orientación informativa: no reemplaza una consulta ni un diagnóstico presencial.</p></form>';
	$out .= '<div class="ps-card"><h3 class="ps-card-title">Odontólogos en la sala</h3>';
	if ( $dent ) {
		foreach ( $dent as $d ) {
			$rank = (int) get_post_meta( $d->ID, '_ps_rango', true );
			$out .= '<div class="ps-row ps-person">' . ps_avatar( $d->ID, 40 ) . '<div><strong>' . esc_html( $d->post_title ) . '</strong><div class="ps-muted ps-small">' . esc_html( PS_RANKS[ $rank ] ?? '' ) . '</div></div></div>';
		}
	} else {
		$out .= '<p class="ps-muted ps-small">Deja tus datos y te contactamos apenas se conecte un odontólogo.</p>';
	}
	return $out . '</div></div></div>';
}

add_action( 'admin_post_nopriv_ps_precita', 'ps_handle_precita' );
add_action( 'admin_post_ps_precita', 'ps_handle_precita' );
function ps_handle_precita() {
	$back = isset( $_POST['ps_back'] ) ? esc_url_raw( wp_unslash( $_POST['ps_back'] ) ) : home_url( '/' );
	if ( ! isset( $_POST['ps_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_nonce'] ), 'ps_precita' ) || ! empty( $_POST['ps_web'] ) || empty( $_POST['ps_ok'] ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$nombre = sanitize_text_field( wp_unslash( $_POST['ps_nombre'] ?? '' ) );
	$email  = sanitize_email( wp_unslash( $_POST['ps_email'] ?? '' ) );
	$tel    = sanitize_text_field( wp_unslash( $_POST['ps_tel'] ?? '' ) );
	$motivo = sanitize_text_field( wp_unslash( $_POST['ps_motivo'] ?? '' ) );
	$msg    = sanitize_textarea_field( wp_unslash( $_POST['ps_msg'] ?? '' ) );
	if ( ! $nombre || ! is_email( $email ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'ps_precita',
			'post_status' => 'publish',
			'post_title'  => $nombre . ' · ' . $motivo,
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		foreach ( array( 'nombre' => $nombre, 'email' => $email, 'telefono' => $tel, 'motivo' => $motivo, 'mensaje' => $msg, 'estado' => 'nueva' ) as $k => $v ) {
			update_post_meta( $id, '_ps_' . $k, $v );
		}
		$to = ps_get( 'notify_email' );
		if ( $to ) {
			wp_mail( $to, 'Nueva solicitud de precita: ' . $motivo, "Paciente: $nombre\nCorreo: $email\nTeléfono: $tel\nMotivo: $motivo\n\nVer en el panel: " . admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		}
	}
	wp_safe_redirect( add_query_arg( 'ps_precita', 'ok', $back ) );
	exit;
}

/** Productos de WooCommerce agrupados por subcategoría.
 *  [ps_productos tipo="atencion|packs|planes"] */
add_shortcode(
	'ps_productos',
	function ( $atts ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '<p class="ps-muted">Activa WooCommerce para mostrar los productos.</p>';
		}
		$a      = shortcode_atts( array( 'tipo' => 'packs' ), $atts );
		$parent = get_term_by( 'slug', PS_CATS[ $a['tipo'] ] ?? $a['tipo'], 'product_cat' );
		if ( ! $parent ) {
			return '<p class="ps-muted">Aún no hay productos en esta sección. Impórtalos desde WooCommerce → Productos → Importar.</p>';
		}
		$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent->term_id, 'hide_empty' => true ) );
		if ( $children && ! is_wp_error( $children ) ) {
			// Respeta el orden que defines arrastrando categorías en WooCommerce; si no hay, el orden de creación.
			usort(
				$children,
				function ( $x, $y ) {
					$ox = (int) get_term_meta( $x->term_id, 'order', true );
					$oy = (int) get_term_meta( $y->term_id, 'order', true );
					return $ox === $oy ? $x->term_id - $y->term_id : $ox - $oy;
				}
			);
		}
		$groups = $children && ! is_wp_error( $children ) ? $children : array( $parent );
		$nav      = '';
		$body     = '';
		foreach ( $groups as $g ) {
			$prods = wc_get_products( array( 'status' => 'publish', 'limit' => 60, 'category' => array( $g->slug ), 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
			if ( ! $prods ) {
				continue;
			}
			$anchor = 'ps-' . $g->slug;
			$nav   .= '<a href="#' . esc_attr( $anchor ) . '">' . esc_html( $g->name ) . '</a>';
			$body  .= '<section class="ps-cat" id="' . esc_attr( $anchor ) . '"><h3 class="ps-cat-title">' . esc_html( $g->name ) . ' <span class="ps-muted ps-small">' . count( $prods ) . ( 1 === count( $prods ) ? ' opción' : ' opciones' ) . '</span></h3><div class="ps-grid">';
			foreach ( $prods as $p ) {
				$body .= ps_product_card( $p );
			}
			$body .= '</div></section>';
		}
		if ( ! $body ) {
			return '<p class="ps-muted">Aún no hay productos publicados en esta sección.</p>';
		}
		return ( count( $groups ) > 1 ? '<nav class="ps-catnav" aria-label="Categorías">' . $nav . '</nav>' : '' ) . $body;
	}
);

function ps_product_card( $p ) {
	$price  = $p->get_price();
	$items  = array_filter( array_map( 'trim', explode( "\n", wp_strip_all_tags( $p->get_description() ) ) ) );
	$out    = '<article class="ps-card ps-product"><h4 class="ps-card-title"><a href="' . esc_url( $p->get_permalink() ) . '">' . esc_html( $p->get_name() ) . '</a></h4>';
	$out   .= ps_prestador_chip( $p->get_id() );
	$out   .= '<p class="ps-muted ps-small">' . esc_html( wp_strip_all_tags( $p->get_short_description() ) ) . '</p>';
	if ( $items ) {
		$out .= '<ul class="ps-checks">';
		foreach ( array_slice( $items, 0, 6 ) as $it ) {
			$out .= '<li>' . esc_html( ltrim( $it, "-•* \t" ) ) . '</li>';
		}
		$out .= '</ul>';
	}
	if ( $p->get_review_count() ) {
		$out .= '<div>' . ps_stars_html( (float) $p->get_average_rating(), (int) $p->get_review_count() ) . '</div>';
	}
	if ( '' !== $price && null !== $price ) {
		$monthly = has_term( 'planes-dentales', 'product_cat', $p->get_id() ) || in_array( 'planes-dentales', ps_parent_slugs( $p ), true );
		$out    .= '<div class="ps-price">' . wp_kses_post( $p->get_price_html() ) . ( $monthly ? ' <span class="ps-muted ps-small">/ mes</span>' : '' ) . '</div>';
		$out    .= '<div class="ps-actions"><a class="ps-btn" href="' . esc_url( $p->add_to_cart_url() ) . '" data-quantity="1" data-product_id="' . (int) $p->get_id() . '" rel="nofollow">' . ( $monthly ? 'Contratar' : 'Comprar' ) . '</a><a class="ps-btn ps-btn-ghost" href="' . esc_url( $p->get_permalink() ) . '#reviews">Ver y valorar</a></div>';
	} else {
		$out .= '<div class="ps-price">Cotizar</div><div class="ps-actions"><a class="ps-btn" href="' . esc_url( add_query_arg( 'ps_motivo', 'Plan para organización', ps_get( 'sala_url' ) ) ) . '">Solicitar cotización</a></div>';
	}
	return $out . '</article>';
}
function ps_parent_slugs( $p ) {
	$slugs = array();
	foreach ( wc_get_product_term_ids( $p->get_id(), 'product_cat' ) as $tid ) {
		foreach ( get_ancestors( $tid, 'product_cat' ) as $anc ) {
			$t = get_term( $anc, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				$slugs[] = $t->slug;
			}
		}
	}
	return $slugs;
}

/** [ps_ahorro] Gráfico comercial de ahorro preventivo. */
add_shortcode(
	'ps_ahorro',
	function () {
		$monthly = (float) ps_get( 'monthly' );
		$years   = max( 1, min( 10, (int) ps_get( 'years' ) ) );
		$corr    = 0;
		foreach ( preg_split( '/\r?\n/', (string) ps_get( 'treatments' ) ) as $line ) {
			$p = array_map( 'trim', explode( '|', $line ) );
			if ( count( $p ) >= 3 ) {
				$corr += (float) preg_replace( '/[^\d.]/', '', $p[1] ) * (float) $p[2];
			}
		}
		$prev   = $monthly * 12 * $years;
		$saving = max( 0, $corr - $prev );
		$max    = max( $prev, $corr, 1 );
		$clp    = function ( $n ) {
			return '$' . number_format( (float) $n, 0, ',', '.' );
		};
		$out  = '<div class="ps-card ps-ahorro"><div class="ps-ahorro-tiles"><div class="ps-tile ps-tile-dark"><span>Ahorro estimado en ' . (int) $years . ' años</span><strong>' . esc_html( $clp( $saving ) ) . '</strong><span>' . ( $corr > 0 && $saving > 0 ? esc_html( round( $saving / $corr * 100 ) . '% menos que tratar después' ) : 'Ajusta los valores en Prevsoul → Ajustes' ) . '</span></div>';
		$out .= '<div class="ps-tile"><span>Con plan preventivo</span><strong>' . esc_html( $clp( $prev ) ) . '</strong></div><div class="ps-tile"><span>Sin prevención</span><strong>' . esc_html( $clp( $corr ) ) . '</strong></div></div>';
		$out .= '<div class="ps-legend"><span><i class="ps-sw ps-sw-a"></i>Con plan preventivo (acumulado)</span><span><i class="ps-sw ps-sw-b"></i>Sin prevención (acumulado)</span></div><div class="ps-bars" role="img" aria-label="' . esc_attr( 'Costo acumulado por año, con y sin prevención' ) . '">';
		for ( $y = 1; $y <= $years; $y++ ) {
			$a    = $monthly * 12 * $y;
			$b    = $corr * $y / $years;
			$out .= '<div class="ps-bar-group"><div class="ps-bar-pair"><span class="ps-bar ps-bar-a" style="height:' . esc_attr( round( $a / $max * 100, 2 ) ) . '%" title="' . esc_attr( 'Año ' . $y . ' con plan: ' . $clp( $a ) ) . '"></span><span class="ps-bar ps-bar-b" style="height:' . esc_attr( round( $b / $max * 100, 2 ) ) . '%" title="' . esc_attr( 'Año ' . $y . ' sin prevención: ' . $clp( $b ) ) . '"></span></div><span class="ps-muted ps-small">Año ' . (int) $y . '</span></div>';
		}
		$out .= '</div><p class="ps-muted ps-small">Estimación referencial' . ( ps_get( 'example' ) ? ' con valores de ejemplo' : '' ) . '. No constituye un presupuesto ni un diagnóstico.</p></div>';
		return $out;
	}
);

/** Reglas del buscador: "Necesidad | palabras | SKU1, SKU2 | mensaje" */
function ps_rules() {
	$rules = array();
	foreach ( preg_split( '/\r?\n/', (string) ps_get( 'rules' ) ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( count( $p ) >= 2 && $p[0] ) {
			$rules[] = array(
				'need' => $p[0],
				'kw'   => array_filter( array_map( 'ps_norm', array_map( 'trim', explode( ',', $p[1] ) ) ) ),
				'skus' => isset( $p[2] ) ? array_filter( array_map( 'trim', explode( ',', $p[2] ) ) ) : array(),
				'msg'  => $p[3] ?? '',
			);
		}
	}
	return $rules;
}
function ps_norm( $t ) {
	return strtolower( remove_accents( (string) $t ) );
}

/** [ps_buscador] */
add_shortcode(
	'ps_buscador',
	function () {
		$q   = isset( $_GET['ps_q'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_q'] ) ) : '';
		$out = '<form class="ps-search" method="get" action="' . esc_url( get_permalink() ) . '#ps-buscador" id="ps-buscador" role="search"><label class="screen-reader-text" for="ps_q">Describe lo que te pasa</label><input id="ps_q" name="ps_q" type="search" value="' . esc_attr( $q ) . '" placeholder="Ej: me sangran las encías al cepillarme"><button class="ps-btn" type="submit">Buscar</button></form>';
		if ( '' === $q ) {
			return $out;
		}
		$t     = ps_norm( $q );
		$best  = null;
		$score = 0;
		foreach ( ps_rules() as $r ) {
			$s = 0;
			foreach ( $r['kw'] as $k ) {
				if ( $k && false !== strpos( $t, $k ) ) {
					$s++;
				}
			}
			if ( $s > $score ) {
				$score = $s;
				$best  = $r;
			}
		}
		ps_log_search( $q, $best ? $best['need'] : '' );
		$sala = ps_get( 'sala_url' );
		if ( ! $best ) {
			return $out . '<div class="ps-card"><strong>Cuéntaselo a un odontólogo</strong><p>No tenemos una respuesta automática para «' . esc_html( $q ) . '», pero un odontólogo puede orientarte en la sala de espera.</p><a class="ps-btn" href="' . esc_url( $sala ) . '">Ir a la sala de espera</a></div>';
		}
		$out .= '<div class="ps-card"><span class="ps-chip ps-acc">Lo que entendimos: ' . esc_html( $best['need'] ) . '</span><p>' . esc_html( $best['msg'] ) . '</p>';
		if ( $best['skus'] && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$out .= '<div class="ps-grid">';
			foreach ( $best['skus'] as $sku ) {
				$pid = wc_get_product_id_by_sku( $sku );
				$p   = $pid ? wc_get_product( $pid ) : null;
				if ( $p && 'publish' === $p->get_status() ) {
					$out .= ps_product_card( $p );
				}
			}
			$out .= '</div>';
		}
		return $out . '<a class="ps-btn" href="' . esc_url( add_query_arg( 'ps_motivo', $best['need'], $sala ) ) . '">Hablar con un odontólogo sobre esto</a></div>';
	}
);

/** Registro de búsquedas (últimas 300) para ver necesidades sin regla. */
function ps_log_search( $q, $need ) {
	$log = get_option( 'ps_search_log', array() );
	array_unshift( $log, array( 'q' => mb_substr( $q, 0, 120 ), 'need' => $need, 't' => time() ) );
	update_option( 'ps_search_log', array_slice( $log, 0, 300 ), false );
}

/* -------------------------------------------------------------------------
 * Modo en construcción: los visitantes ven «Estamos restaurando».
 * Quien inicia sesión con permiso de edición ve el sitio normal.
 * ---------------------------------------------------------------------- */
function ps_construccion_on() {
	return '1' === (string) get_option( 'ps_construccion', '1' );
}
add_action(
	'parse_request',
	function ( $wp ) {
		if ( ! ps_construccion_on() || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( ! empty( $wp->query_vars['rest_route'] ) || isset( $_GET['wc-ajax'] ) || 'panel-prestador' === trim( (string) $wp->request, '/' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( is_user_logged_in() && ( current_user_can( 'edit_posts' ) || apply_filters( 'ps_construccion_bypass', false ) ) ) {
			return;
		}
		status_header( 503 );
		header( 'Retry-After: 86400' );
		nocache_headers();
		include plugin_dir_path( PS_FILE ) . 'templates/construccion.php';
		exit;
	},
	0
);
add_action(
	'admin_post_ps_construccion',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_construccion' ) ) {
			wp_die( 'No permitido.' );
		}
		update_option( 'ps_construccion', empty( $_GET['on'] ) ? '0' : '1' ); // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( admin_url( 'admin.php?page=prevsoul' ) );
		exit;
	}
);
function ps_construccion_url( $on ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=ps_construccion&on=' . ( $on ? 1 : 0 ) ), 'ps_construccion' );
}
add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$on = ps_construccion_on();
		$bar->add_node(
			array(
				'id'    => 'ps-construccion',
				'title' => $on ? '🦷 En construcción' : '🦷 Sitio publicado',
				'href'  => admin_url( 'admin.php?page=prevsoul' ),
				'meta'  => array( 'title' => $on ? 'Los visitantes ven «Estamos restaurando». Tú ves el sitio porque iniciaste sesión.' : 'El sitio está visible para todos.' ),
			)
		);
	},
	100
);

/* -------------------------------------------------------------------------
 * Página de ajustes: Prevsoul
 * ---------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Prevsoul', 'Prevsoul', 'manage_options', 'prevsoul', 'ps_settings_page', 'dashicons-heart', 24 );
	}
);
add_action(
	'admin_init',
	function () {
		register_setting(
			'ps_settings_group',
			'ps_settings',
			array(
				'sanitize_callback' => function ( $in ) {
					$d = ps_default_settings();
					return array(
						'video_url'    => esc_url_raw( $in['video_url'] ?? '' ),
						'whatsapp'     => sanitize_text_field( $in['whatsapp'] ?? '' ),
						'notify_email' => sanitize_email( $in['notify_email'] ?? $d['notify_email'] ),
						'sala_url'     => esc_url_raw( $in['sala_url'] ?? $d['sala_url'] ),
						'monthly'      => absint( $in['monthly'] ?? $d['monthly'] ),
						'years'        => max( 1, min( 10, absint( $in['years'] ?? $d['years'] ) ) ),
						'treatments'   => sanitize_textarea_field( $in['treatments'] ?? '' ),
						'example'      => empty( $in['example'] ) ? 0 : 1,
						'rules'        => sanitize_textarea_field( $in['rules'] ?? '' ),
					);
				},
			)
		);
	}
);

function ps_settings_page() {
	$f = function ( $k ) {
		return esc_attr( ps_get( $k ) );
	};
	$counts = wp_count_posts( 'ps_precita' );
	$new    = get_posts( array( 'post_type' => 'ps_precita', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => 'nueva' ) );
	$log    = get_option( 'ps_search_log', array() );
	$miss   = array_slice( array_values( array_filter( $log, function ( $r ) { return '' === $r['need']; } ) ), 0, 10 );
	?>
	<div class="wrap">
		<h1>Prevsoul</h1>
		<div style="display:flex;gap:16px;flex-wrap:wrap;margin:16px 0">
			<?php ps_demo_card(); ?>
			<div class="card" style="margin:0;min-width:240px"><h2 style="margin-top:0">Actualizaciones</h2><p>Versión instalada: <strong><?php echo esc_html( PS_VERSION ); ?></strong><?php $ps_l = ps_gh_latest(); if ( $ps_l && version_compare( $ps_l['version'], PS_VERSION, '>' ) ) { echo '<br>Disponible: <strong>' . esc_html( $ps_l['version'] ) . '</strong>'; } ?></p><p>Las versiones nuevas se instalan solas. Si quieres traerla ya:</p><p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ps_check_updates' ), 'ps_check_updates' ) ); ?>">Buscar actualizaciones ahora</a></p><p><a href="https://github.com/felipealarconvi-lab/prevsoul-core/releases" target="_blank" rel="noopener">Ver qué cambió en cada versión</a></p></div>
			<div class="card" style="margin:0;min-width:260px;border-left:4px solid <?php echo ps_construccion_on() ? '#d63638' : '#00a32a'; ?>"><h2 style="margin-top:0">Visibilidad del sitio</h2>
				<?php if ( ps_construccion_on() ) : ?>
					<p><strong>En construcción.</strong> Los visitantes ven la página «Estamos restaurando». Tú ves el sitio completo porque iniciaste sesión.</p>
					<p><a class="button button-primary" href="<?php echo esc_url( ps_construccion_url( false ) ); ?>" onclick="return confirm('¿Publicar el sitio? Todos podrán verlo.');">Publicar el sitio</a></p>
					<p class="description">Para ver lo que ve un visitante, abre prevsoul.cl en una ventana de incógnito.</p>
				<?php else : ?>
					<p><strong>Publicado.</strong> Todos pueden ver el sitio.</p>
					<p><a class="button" href="<?php echo esc_url( ps_construccion_url( true ) ); ?>">Volver a «en construcción»</a></p>
				<?php endif; ?>
			</div>
			<div class="card" style="margin:0;min-width:200px"><h2 style="margin-top:0">Sala de espera</h2><p style="font-size:28px;margin:0"><strong><?php echo count( $new ); ?></strong></p><p>solicitudes nuevas · <?php echo (int) ( $counts->publish ?? 0 ); ?> en total</p><p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ps_precita' ) ); ?>">Ver solicitudes</a></p></div>
			<div class="card" style="margin:0;min-width:260px;flex:1"><h2 style="margin-top:0">Búsquedas sin resultado</h2>
				<?php if ( $miss ) : ?>
					<ul style="margin:0"><?php foreach ( $miss as $m ) : ?><li>«<?php echo esc_html( $m['q'] ); ?>» <span style="color:#666"><?php echo esc_html( human_time_diff( $m['t'] ) ); ?></span></li><?php endforeach; ?></ul>
					<p class="description">Agrégalas como reglas del buscador más abajo.</p>
				<?php else : ?>
					<p>Ninguna por ahora.</p>
				<?php endif; ?>
			</div>
		</div>
		<form method="post" action="options.php">
			<?php settings_fields( 'ps_settings_group' ); ?>
			<h2>Sala de espera</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="ps_video">Enlace de videollamada</label></th><td><input class="large-text" type="url" id="ps_video" name="ps_settings[video_url]" value="<?php echo $f( 'video_url' ); ?>" placeholder="https://meet.google.com/… o https://zoom.us/j/…"><p class="description">Se muestra al paciente después de entrar a la sala de espera.</p></td></tr>
				<tr><th><label for="ps_wa">WhatsApp</label></th><td><input type="text" id="ps_wa" name="ps_settings[whatsapp]" value="<?php echo $f( 'whatsapp' ); ?>" placeholder="56912345678"></td></tr>
				<tr><th><label for="ps_mail">Correo de avisos</label></th><td><input type="email" class="regular-text" id="ps_mail" name="ps_settings[notify_email]" value="<?php echo $f( 'notify_email' ); ?>"><p class="description">Aquí llega un aviso por cada nueva solicitud de precita.</p></td></tr>
				<tr><th><label for="ps_sala">Página de la sala de espera</label></th><td><input class="large-text" type="url" id="ps_sala" name="ps_settings[sala_url]" value="<?php echo $f( 'sala_url' ); ?>"></td></tr>
			</table>
			<h2>Argumento de ahorro preventivo</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="ps_monthly">Valor mensual del plan (CLP)</label></th><td><input type="number" min="0" id="ps_monthly" name="ps_settings[monthly]" value="<?php echo $f( 'monthly' ); ?>"></td></tr>
				<tr><th><label for="ps_years">Horizonte (años)</label></th><td><input type="number" min="1" max="10" id="ps_years" name="ps_settings[years]" value="<?php echo $f( 'years' ); ?>"></td></tr>
				<tr><th><label for="ps_treat">Tratamientos que se evitan</label></th><td><textarea class="large-text code" rows="6" id="ps_treat" name="ps_settings[treatments]"><?php echo esc_textarea( ps_get( 'treatments' ) ); ?></textarea><p class="description">Una línea por tratamiento: <code>Nombre | valor en pesos | veces</code></p></td></tr>
				<tr><th>Aviso</th><td><label><input type="checkbox" name="ps_settings[example]" value="1" <?php checked( ps_get( 'example' ), 1 ); ?>> Mostrar «valores de ejemplo» en el sitio</label></td></tr>
			</table>
			<h2>Buscador de necesidades</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="ps_rules">Reglas</label></th><td><textarea class="large-text code" rows="10" id="ps_rules" name="ps_settings[rules]"><?php echo esc_textarea( ps_get( 'rules' ) ); ?></textarea><p class="description">Una línea por necesidad: <code>Necesidad | palabras clave separadas por coma | SKU de productos | mensaje al paciente</code>. Escribe raíces: «sangr» cubre sangra y sangrado.</p></td></tr>
			</table>
			<?php submit_button( 'Guardar ajustes' ); ?>
		</form>
		<h2>Bloques para tus páginas</h2>
		<p>En Elementor, arrastra el widget <strong>Shortcode</strong> y pega uno de estos códigos:</p>
		<table class="widefat striped" style="max-width:900px"><tbody>
			<tr><td><code>[ps_sala_espera]</code></td><td>Sala de espera: solicitud de precita</td></tr>
			<tr><td><code>[ps_live]</code></td><td>Prevsoul Live: transmisión actual y agenda</td></tr>
			<tr><td><code>[ps_buscador]</code></td><td>Buscador de necesidades</td></tr>
			<tr><td><code>[ps_productos tipo="atencion"]</code></td><td>Atenciones dentales por categoría</td></tr>
			<tr><td><code>[ps_productos tipo="packs"]</code></td><td>Packs dentales por categoría</td></tr>
			<tr><td><code>[ps_productos tipo="planes"]</code></td><td>Planes dentales por categoría</td></tr>
			<tr><td><code>[ps_odontologos]</code></td><td>Equipo de odontólogos con foto, rango y valoraciones</td></tr>
			<tr><td><code>[ps_clinicas]</code></td><td>Clínicas</td></tr>
			<tr><td><code>[ps_ahorro]</code></td><td>Gráfico de ahorro preventivo</td></tr>
		</tbody></table>
	</div>
	<?php
}

/* Aviso de solicitudes nuevas en el menú */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$new = get_posts( array( 'post_type' => 'ps_precita', 'numberposts' => 99, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => 'nueva' ) );
		if ( ! $new ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=ps_precita' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . count( $new ) . '</span></span>';
			}
		}
	},
	99
);

/* -------------------------------------------------------------------------
 * Activación: páginas, menú y categorías
 * ---------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'ps_activate' );
function ps_activate() {
	ps_register_types();
	if ( ! get_option( 'ps_settings' ) ) {
		add_option( 'ps_settings', ps_default_settings() );
	}

	$pages = ps_pages_def();
	$ids = array();
	foreach ( $pages as $slug => $p ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		$ids[ $slug ] = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $p[0],
				'post_name'    => $slug,
				'post_content' => $p[1],
			)
		);
		update_post_meta( $ids[ $slug ], '_wp_page_template', PS_TEMPLATE );
	}
	if ( ! empty( $ids['inicio'] ) && 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['inicio'] );
	}
	$opts             = get_option( 'ps_settings', array() );
	$opts['sala_url'] = get_permalink( $ids['sala-de-espera'] );
	update_option( 'ps_settings', $opts );

	// Menú principal.
	$menu_name = 'Menú principal Prevsoul';
	if ( ! wp_get_nav_menu_object( $menu_name ) ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( ! is_wp_error( $menu_id ) ) {
			foreach ( array( 'inicio', 'atencion-dental', 'packs-dentales', 'planes-dentales', 'prevsoul-live' ) as $i => $slug ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $ids[ $slug ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $i + 1,
					)
				);
			}
			$locs = get_registered_nav_menus();
			if ( $locs ) {
				$set = get_theme_mod( 'nav_menu_locations', array() );
				$first = array_key_first( $locs );
				if ( empty( $set[ $first ] ) ) {
					$set[ $first ] = $menu_id;
					set_theme_mod( 'nav_menu_locations', $set );
				}
			}
		}
	}

	// Categorías de productos (si WooCommerce ya está activo).
	if ( taxonomy_exists( 'product_cat' ) ) {
		ps_create_product_cats();
	}

	// Ejemplos en borrador para ver cómo se llenan las fichas.
	if ( ! get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 1, 'post_status' => 'any' ) ) ) {
		$cl = wp_insert_post( array( 'post_type' => 'ps_clinica', 'post_status' => 'draft', 'post_title' => 'Clínica de ejemplo (borrar o editar)', 'post_content' => 'Descripción breve de la clínica.' ) );
		update_post_meta( $cl, '_ps_direccion', '[DIRECCIÓN], [COMUNA]' );
		$od = wp_insert_post( array( 'post_type' => 'ps_odontologo', 'post_status' => 'draft', 'post_title' => 'Odontólogo de ejemplo (borrar o editar)', 'post_content' => 'Descripción profesional: enfoque, experiencia y forma de atender.', 'comment_status' => 'open' ) );
		update_post_meta( $od, '_ps_titulo', 'Cirujano dentista · Odontología general' );
		update_post_meta( $od, '_ps_clinica', (string) $cl );
		update_post_meta( $od, '_ps_rango', '1' );
		update_post_meta( $od, '_ps_activo', '1' );
		$tr = wp_insert_post( array( 'post_type' => 'ps_transmision', 'post_status' => 'draft', 'post_title' => 'Cómo enseñar a cepillarse a los niños', 'post_content' => 'Técnica de cepillado por edad, cuánta pasta usar y cómo convertirlo en un juego diario.' ) );
		update_post_meta( $tr, '_ps_estado', 'programada' );
		update_post_meta( $tr, '_ps_publico', 'Niños y familias' );
		update_post_meta( $tr, '_ps_odontologo', (string) $od );
	}
	update_option( 'ps_design_version', PS_VERSION );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/* Crea las categorías al activar WooCommerce después de este plugin. */
add_action(
	'woocommerce_init',
	function () {
		if ( ! get_option( 'ps_cats_done' ) ) {
			ps_create_product_cats();
		}
	}
);
function ps_create_product_cats() {
	$tree = array(
		'atencion-dental' => array( 'Atención dental', array( 'Diagnóstico', 'Prevención', 'Estética' ) ),
		'packs-dentales'  => array( 'Packs dentales', array( 'Prevención', 'Niños y familia', 'Urgencias' ) ),
		'planes-dentales' => array( 'Planes dentales', array( 'Personas', 'Familias', 'Organizaciones' ) ),
	);
	foreach ( $tree as $slug => $def ) {
		$parent = get_term_by( 'slug', $slug, 'product_cat' );
		$pid    = $parent ? $parent->term_id : 0;
		if ( ! $pid ) {
			$r   = wp_insert_term( $def[0], 'product_cat', array( 'slug' => $slug ) );
			$pid = is_wp_error( $r ) ? 0 : $r['term_id'];
		}
		if ( ! $pid ) {
			continue;
		}
		foreach ( $def[1] as $child ) {
			$cslug = sanitize_title( $slug . '-' . $child );
			if ( ! get_term_by( 'slug', $cslug, 'product_cat' ) ) {
				wp_insert_term( $child, 'product_cat', array( 'slug' => $cslug, 'parent' => $pid ) );
			}
		}
	}
	update_option( 'ps_cats_done', 1 );
}

/* -------------------------------------------------------------------------
 * Diseño Prevsoul (v1.1): plantilla de página completa y bloques de sección
 * ---------------------------------------------------------------------- */
const PS_TEMPLATE = 'prevsoul-full.php';

add_filter(
	'theme_page_templates',
	function ( $templates ) {
		$templates[ PS_TEMPLATE ] = 'Prevsoul · diseño completo';
		return $templates;
	}
);
add_filter(
	'template_include',
	function ( $template ) {
		if ( is_singular( 'page' ) && PS_TEMPLATE === get_page_template_slug() ) {
			return plugin_dir_path( PS_FILE ) . 'templates/' . PS_TEMPLATE;
		}
		return $template;
	}
);

function ps_sc( $code ) {
	return '<!-- wp:shortcode -->' . $code . '<!-- /wp:shortcode -->';
}
function ps_online_count() {
	return count( get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 50, 'fields' => 'ids', 'meta_key' => '_ps_activo', 'meta_value' => '1' ) ) );
}

/** [ps_hero titulo="" bajada="" boton="" boton_url="" boton2="" boton2_url=""] */
add_shortcode(
	'ps_hero',
	function ( $atts ) {
		$a  = shortcode_atts( array( 'titulo' => '', 'bajada' => '', 'boton' => 'Entrar a la sala de espera', 'boton_url' => '', 'boton2' => '', 'boton2_url' => '' ), $atts );
		if ( function_exists( 'ps_anuncios_visibles' ) ) {
			$ads = ps_anuncios_visibles();
			if ( $ads ) {
				return ps_slider_html( $ads );
			}
		}
		$n  = ps_online_count();
		$u1 = $a['boton_url'] ? $a['boton_url'] : ps_get( 'sala_url' );
		$o  = '<section class="ps-sec ps-bg-oscuro ps-hero"><div class="ps-wrap">';
		$o .= $n ? '<span class="ps-chip ps-chip-live"><i class="ps-sala-dot"></i>Sala de espera abierta · ' . (int) $n . ( 1 === $n ? ' odontólogo disponible' : ' odontólogos disponibles' ) . '</span>' : '<span class="ps-chip ps-chip-live is-closed"><i class="ps-sala-dot off"></i>Deja tu consulta · te respondemos pronto</span>';
		$o .= '<h1>' . esc_html( $a['titulo'] ) . '</h1>' . ( $a['bajada'] ? '<p class="ps-hero-sub">' . esc_html( $a['bajada'] ) . '</p>' : '' );
		$o .= '<div class="ps-actions"><a class="ps-btn ps-btn-lg" href="' . esc_url( $u1 ) . '">' . esc_html( $a['boton'] ) . '</a>';
		$o .= $a['boton2'] ? '<a class="ps-btn ps-btn-outline-light ps-btn-lg" href="' . esc_url( $a['boton2_url'] ) . '">' . esc_html( $a['boton2'] ) . '</a>' : '';
		return $o . '</div></div></section>';
	}
);

/** [ps_cabecera titulo="" bajada=""] franja de título de página */
add_shortcode(
	'ps_cabecera',
	function ( $atts ) {
		$a = shortcode_atts( array( 'titulo' => '', 'bajada' => '' ), $atts );
		return '<section class="ps-sec ps-bg-oscuro ps-pagehead"><div class="ps-wrap"><h1>' . esc_html( $a['titulo'] ) . '</h1>' . ( $a['bajada'] ? '<p class="ps-hero-sub">' . esc_html( $a['bajada'] ) . '</p>' : '' ) . '</div></section>';
	}
);

/** [ps_seccion fondo="blanco|claro|oscuro" titulo="" bajada="" id=""]…[/ps_seccion] */
add_shortcode(
	'ps_seccion',
	function ( $atts, $content = '' ) {
		$a  = shortcode_atts( array( 'fondo' => 'blanco', 'titulo' => '', 'bajada' => '', 'id' => '' ), $atts );
		$bg = in_array( $a['fondo'], array( 'blanco', 'claro', 'oscuro' ), true ) ? $a['fondo'] : 'blanco';
		$o  = '<section class="ps-sec ps-bg-' . esc_attr( $bg ) . '"' . ( $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : '' ) . '><div class="ps-wrap">';
		$o .= $a['titulo'] ? '<h2 class="ps-sec-title">' . esc_html( $a['titulo'] ) . '</h2>' : '';
		$o .= $a['bajada'] ? '<p class="ps-lead">' . esc_html( $a['bajada'] ) . '</p>' : '';
		return $o . do_shortcode( shortcode_unautop( trim( $content ) ) ) . '</div></section>';
	}
);

/** [ps_cta titulo="" bajada="" boton="" url=""] */
add_shortcode(
	'ps_cta',
	function ( $atts ) {
		$a = shortcode_atts( array( 'titulo' => '', 'bajada' => '', 'boton' => 'Hablar con un odontólogo', 'url' => '' ), $atts );
		$u = $a['url'] ? $a['url'] : ps_get( 'sala_url' );
		return '<section class="ps-sec ps-bg-oscuro ps-cta"><div class="ps-wrap"><h2 class="ps-sec-title">' . esc_html( $a['titulo'] ) . '</h2>' . ( $a['bajada'] ? '<p class="ps-hero-sub">' . esc_html( $a['bajada'] ) . '</p>' : '' ) . '<a class="ps-btn ps-btn-lg" href="' . esc_url( $u ) . '">' . esc_html( $a['boton'] ) . '</a></div></section>';
	}
);

/** Contenido de las páginas que crea el plugin. */
function ps_pages_def() {
	$sala = '/sala-de-espera/';
	return array(
		'inicio'                 => array(
			'Inicio',
			ps_sc( '[ps_hero titulo="Habla ahora con un odontólogo real" bajada="Orientación dental gratuita y planes preventivos con odontólogo de cabecera." boton="Entrar a la sala de espera" boton_url="#sala" boton2="Ver Prevsoul Live" boton2_url="/prevsoul-live/"]' )
			. ps_sc( '[ps_seccion id="sala" fondo="blanco" titulo="Sala de espera" bajada="Odontólogos conectados para orientarte en una precita gratuita. No reemplaza una consulta presencial."][ps_sala_espera][/ps_seccion]' )
			. ps_sc( '[ps_seccion id="live" fondo="claro" titulo="Prevsoul Live" bajada="Transmisiones en vivo de nuestros odontólogos: aprende a cuidar tu boca y haz tus preguntas en directo."][ps_live][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="¿Qué te pasa? Cuéntanos y te orientamos" bajada="Escribe con tus palabras y te mostramos el camino."][ps_buscador][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="claro" titulo="Planes preventivos" bajada="Para ti, tu familia o tu organización. Todos incluyen precitas ilimitadas."][ps_productos tipo="planes"][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Con prevención, tu boca y tu bolsillo ganan" bajada="Compara lo que inviertes en un plan preventivo con lo que podría costarte tratar después."][ps_ahorro][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="claro" titulo="Tus odontólogos de cabecera" bajada="Profesionales reales, valorados por pacientes."][ps_odontologos limite="6"][/ps_seccion]' )
			. ps_sc( '[ps_cta titulo="Empieza a prevenir hoy" bajada="Tu primera precita es gratis." boton="Hablar con un odontólogo" url="#sala"]' ),
		),
		'atencion-dental'        => array(
			'Atención dental',
			ps_sc( '[ps_cabecera titulo="Atención dental" bajada="Diagnóstico, prevención y estética con tu odontólogo de cabecera."]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Nuestras atenciones" bajada="Ordenadas por categoría."][ps_productos tipo="atencion"][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="claro" titulo="¿No sabes qué necesitas?" bajada="Cuéntanos qué te pasa y te orientamos."][ps_buscador][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Quiénes te atienden"][ps_odontologos][/ps_seccion]' )
			. ps_sc( '[ps_cta titulo="¿Tienes dudas antes de agendar?" bajada="Habla gratis con un odontólogo en la sala de espera." boton="Ir a la sala de espera" url="' . $sala . '"]' ),
		),
		'packs-dentales'         => array(
			'Packs dentales',
			ps_sc( '[ps_cabecera titulo="Packs dentales" bajada="Soluciones puntuales con precio cerrado para empezar hoy."]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Elige tu pack"][ps_productos tipo="packs"][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="claro" titulo="¿No sabes qué pack necesitas?" bajada="Cuéntanos qué te pasa y te orientamos."][ps_buscador][/ps_seccion]' )
			. ps_sc( '[ps_cta titulo="¿Prefieres que un odontólogo te recomiende?" bajada="En la sala de espera te orientamos gratis en una precita." boton="Ir a la sala de espera" url="' . $sala . '"]' ),
		),
		'planes-dentales'        => array(
			'Planes dentales',
			ps_sc( '[ps_cabecera titulo="Planes dentales" bajada="Planes preventivos con odontólogo de cabecera para ti, tu familia o tu organización."]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Elige tu plan" bajada="Todos incluyen precitas ilimitadas con tu odontólogo."][ps_productos tipo="planes"][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="claro" titulo="Cuánto ahorras con un plan preventivo"][ps_ahorro][/ps_seccion]' )
			. ps_sc( '[ps_cta titulo="¿No sabes qué plan elegir?" bajada="Un odontólogo te recomienda el plan adecuado en una precita gratuita." boton="Ir a la sala de espera" url="' . $sala . '"]' ),
		),
		'prevsoul-live'          => array(
			'Prevsoul Live',
			ps_sc( '[ps_cabecera titulo="Prevsoul Live" bajada="Transmisiones en vivo con odontólogos de Prevsoul: técnicas de cepillado, higiene y prevención para toda la familia."]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Transmitiendo ahora"][ps_live][/ps_seccion]' )
			. ps_sc( '[ps_cta titulo="¿Tienes una duda sobre tu caso?" bajada="En la sala de espera un odontólogo te orienta de forma personal." boton="Ir a la sala de espera" url="' . $sala . '"]' ),
		),
		'sala-de-espera'         => array(
			'Sala de espera',
			ps_sc( '[ps_cabecera titulo="Sala de espera" bajada="Habla con un odontólogo real en una precita gratuita de orientación."]' )
			. ps_sc( '[ps_seccion fondo="blanco"][ps_sala_espera][/ps_seccion]' ),
		),
		'odontologos-y-clinicas' => array(
			'Odontólogos y clínicas',
			ps_sc( '[ps_cabecera titulo="Odontólogos y clínicas" bajada="Profesionales reales, valorados por pacientes."]' )
			. ps_sc( '[ps_seccion fondo="blanco" titulo="Nuestro equipo"][ps_odontologos][/ps_seccion]' )
			. ps_sc( '[ps_seccion fondo="claro" titulo="Nuestras clínicas"][ps_clinicas][/ps_seccion]' ),
		),
	);
}

/** Aplica el diseño Prevsoul a las páginas (reemplaza su contenido). */
function ps_apply_design() {
	$done = 0;
	foreach ( ps_pages_def() as $slug => $p ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_content' => $p[1] ) );
			update_post_meta( $page->ID, '_wp_page_template', PS_TEMPLATE );
			delete_post_meta( $page->ID, '_elementor_edit_mode' );
		} else {
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_name' => $slug, 'post_content' => $p[1] ) );
			update_post_meta( $id, '_wp_page_template', PS_TEMPLATE );
		}
		$done++;
	}
	update_option( 'ps_design_version', PS_VERSION );
	return $done;
}
add_action(
	'admin_post_ps_apply_design',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_apply_design' ) ) {
			wp_die( 'No permitido.' );
		}
		$n = ps_apply_design();
		wp_safe_redirect( admin_url( 'admin.php?page=prevsoul&ps_design=' . (int) $n ) );
		exit;
	}
);
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['ps_design'] ) ) {
			echo '<div class="notice notice-success"><p>Listo: se aplicó el diseño Prevsoul a ' . (int) $_GET['ps_design'] . ' páginas. <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank">Ver el sitio</a></p></div>';
			return;
		}
		if ( version_compare( (string) get_option( 'ps_design_version', '0' ), '1.1.0', '<' ) ) {
			echo '<div class="notice notice-info"><p><strong>Prevsoul 1.1</strong> trae el diseño del prototipo: encabezado, portada, secciones, pie de página y barra de pestañas en el celular.</p><p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ps_apply_design' ), 'ps_apply_design' ) ) . '">Aplicar diseño Prevsoul a las páginas</a> <span class="description">Reemplaza el contenido de Inicio, Atención dental, Packs dentales, Planes dentales, Prevsoul Live, Sala de espera y Odontólogos y clínicas.</span></p></div>';
		}
	}
);

function ps_page_block( $title, $sub, $parts ) {
	$c = '<!-- wp:paragraph {"className":"ps-lead"} --><p class="ps-lead">' . esc_html( $sub ) . '</p><!-- /wp:paragraph -->';
	foreach ( $parts as $p ) {
		$c .= 0 === strpos( $p, '[' ) ? '<!-- wp:shortcode -->' . $p . '<!-- /wp:shortcode -->' : $p;
	}
	return $c;
}
function ps_page_inicio() {
	return '<!-- wp:group {"className":"ps-hero"} --><div class="wp-block-group ps-hero"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Habla ahora con un odontólogo real</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Orientación dental gratuita y planes preventivos con odontólogo de cabecera.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#sala">Entrar a la sala de espera</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/prevsoul-live/">Ver Prevsoul Live</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->'
		. '<!-- wp:heading {"anchor":"sala"} --><h2 class="wp-block-heading" id="sala">Sala de espera</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Odontólogos conectados para orientarte en una precita gratuita. No reemplaza una consulta presencial.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[ps_sala_espera]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Prevsoul Live</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Transmisiones en vivo de nuestros odontólogos: aprende a cuidar tu boca y haz tus preguntas en directo.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[ps_live]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">¿Qué te pasa? Cuéntanos y te orientamos</h2><!-- /wp:heading --><!-- wp:shortcode -->[ps_buscador]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Planes preventivos</h2><!-- /wp:heading --><!-- wp:shortcode -->[ps_productos tipo="planes"]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Con prevención, tu boca y tu bolsillo ganan</h2><!-- /wp:heading --><!-- wp:shortcode -->[ps_ahorro]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Tus odontólogos de cabecera</h2><!-- /wp:heading --><!-- wp:shortcode -->[ps_odontologos limite="6"]<!-- /wp:shortcode -->';
}
