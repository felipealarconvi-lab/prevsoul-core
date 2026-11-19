<?php
/**
 * Verificación interna de prestadores (odontólogos y clínicas). Nada de esto se muestra al paciente:
 * solo controla quién puede vender y aparecer en el sitio cuando se publique.
 *
 * Odontólogo: Registro Nacional de Prestadores Individuales (Superintendencia de Salud).
 * Clínica: autorización sanitaria de la SEREMI y director técnico verificado.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

const PS_VER_URL_REGISTRO = 'https://www.superdesalud.gob.cl/orientacion-en-salud/conozca-el-registro-nacional-de-prestadores-individuales-de-salud/';
const PS_VER_MESES        = 12;

function ps_ver_campos( $tipo ) {
	if ( 'ps_clinica' === $tipo ) {
		return array(
			'razon'    => array( 'Razón social', true ),
			'rut'      => array( 'RUT de la clínica', true ),
			'res_num'  => array( 'N.º de resolución sanitaria (SEREMI)', true ),
			'res_fec'  => array( 'Fecha de la resolución', true, 'date' ),
			'region'   => array( 'Región', true ),
			'director' => array( 'Director técnico', true, 'director' ),
			'res_pdf'  => array( 'Resolución sanitaria (PDF)', true, 'file' ),
			'conv_pdf' => array( 'Convenio firmado con Prevsoul (PDF)', false, 'file' ),
			'seg_pdf'  => array( 'Seguro de responsabilidad civil (PDF, opcional)', false, 'file' ),
		);
	}
	return array(
		'run'      => array( 'RUN', true ),
		'reg_num'  => array( 'N.º de registro en la Superintendencia', true ),
		'reg_fec'  => array( 'Fecha del registro', true, 'date' ),
		'esp'      => array( 'Especialidad certificada (si tiene)', false ),
		'cert_pdf' => array( 'Certificado del Registro (PDF)', false, 'file' ),
		'conv_pdf' => array( 'Convenio firmado con Prevsoul (PDF)', false, 'file' ),
		'seg_pdf'  => array( 'Seguro de responsabilidad civil (PDF, opcional)', false, 'file' ),
	);
}

function ps_ver_datos( $id ) {
	$d = get_post_meta( $id, '_ps_ver', true );
	return is_array( $d ) ? $d : array();
}

/** pendiente | verificado | vencido */
function ps_ver_estado( $id ) {
	if ( 'verificado' !== get_post_meta( $id, '_ps_ver_estado', true ) ) {
		return 'pendiente';
	}
	return (int) get_post_meta( $id, '_ps_ver_hasta', true ) < time() ? 'vencido' : 'verificado';
}

function ps_ver_ok( $id ) {
	return 'verificado' === ps_ver_estado( $id );
}

/** Lo que falta para poder marcarlo verificado. */
function ps_ver_faltan( $id ) {
	$d   = ps_ver_datos( $id );
	$out = array();
	foreach ( ps_ver_campos( get_post_type( $id ) ) as $k => $c ) {
		if ( $c[1] && empty( $d[ $k ] ) ) {
			$out[] = $c[0];
		}
	}
	if ( 'ps_clinica' === get_post_type( $id ) && ! empty( $d['director'] ) && ! ps_ver_ok( (int) $d['director'] ) ) {
		$out[] = 'que el director técnico esté verificado';
	}
	return $out;
}

/** Restricciones solo con el sitio abierto al público (en construcción se puede probar todo). */
function ps_ver_exigir() {
	return ! ( function_exists( 'ps_construccion_on' ) && ps_construccion_on() );
}

/* ---------- Ficha en WordPress ---------- */

add_action(
	'post_edit_form_tag',
	function ( $post ) {
		if ( in_array( $post->post_type, array( 'ps_clinica', 'ps_odontologo' ), true ) ) {
			echo ' enctype="multipart/form-data"';
		}
	}
);

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'ps_clinica', 'ps_odontologo' ) as $t ) {
			add_meta_box( 'ps_ver', 'Verificación Prevsoul (interna)', 'ps_ver_box', $t, 'normal', 'default' );
		}
	}
);

function ps_ver_box( $post ) {
	$d   = ps_ver_datos( $post->ID );
	$est = ps_ver_estado( $post->ID );
	$col = array( 'verificado' => '#167A64', 'pendiente' => '#996800', 'vencido' => '#b32d2e' );
	wp_nonce_field( 'ps_ver_' . $post->ID, 'ps_ver_nonce' );
	echo '<p><strong style="color:' . esc_attr( $col[ $est ] ) . '">' . esc_html( ucfirst( $est ) ) . '</strong>';
	if ( 'pendiente' !== $est ) {
		$u = get_userdata( (int) get_post_meta( $post->ID, '_ps_ver_por', true ) );
		echo ' · por ' . esc_html( $u ? $u->display_name : '—' ) . ' el ' . esc_html( wp_date( 'j M Y', (int) get_post_meta( $post->ID, '_ps_ver_en', true ) ) ) . ' · vence el ' . esc_html( wp_date( 'j M Y', (int) get_post_meta( $post->ID, '_ps_ver_hasta', true ) ) );
	}
	echo '</p><p class="description">Solo para uso interno: el paciente no ve estos datos. Con el sitio publicado, un prestador sin verificar no aparece en el sitio ni se pueden comprar sus atenciones.</p><table class="form-table" role="presentation">';
	foreach ( ps_ver_campos( $post->post_type ) as $k => $c ) {
		$v    = $d[ $k ] ?? '';
		$tipo = $c[2] ?? 'text';
		echo '<tr><th><label for="psv_' . esc_attr( $k ) . '">' . esc_html( $c[0] ) . ( $c[1] ? ' *' : '' ) . '</label></th><td>';
		if ( 'file' === $tipo ) {
			if ( $v ) {
				echo '<a href="' . esc_url( wp_get_attachment_url( (int) $v ) ) . '" target="_blank">Ver archivo</a> · <label><input type="checkbox" name="psv_borrar[' . esc_attr( $k ) . ']" value="1"> quitar</label><br>';
			}
			echo '<input type="file" id="psv_' . esc_attr( $k ) . '" name="psv_' . esc_attr( $k ) . '" accept="application/pdf,image/*">';
		} elseif ( 'director' === $tipo ) {
			echo '<select id="psv_director" name="psv[director]"><option value="">— Elegir —</option>';
			foreach ( get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 300, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $o ) {
				echo '<option value="' . (int) $o->ID . '" ' . selected( (int) $v, $o->ID, false ) . '>' . esc_html( $o->post_title . ( ps_ver_ok( $o->ID ) ? ' (verificado)' : ' (sin verificar)' ) ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input type="' . ( 'date' === $tipo ? 'date' : 'text' ) . '" id="psv_' . esc_attr( $k ) . '" name="psv[' . esc_attr( $k ) . ']" value="' . esc_attr( $v ) . '" class="regular-text">';
		}
		if ( 'reg_num' === $k ) {
			echo ' <a class="button" href="' . esc_url( PS_VER_URL_REGISTRO ) . '" target="_blank" rel="noopener">Consultar en el Registro</a>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	if ( 'ps_clinica' === $post->post_type ) {
		echo '<p><label><input type="checkbox" name="psv_check" value="1"> Revisé la resolución: está a nombre de la clínica y la dirección autorizada coincide con la de la ficha.</label></p>';
	} else {
		echo '<p><label><input type="checkbox" name="psv_check" value="1"> Revisé el Registro de la Superintendencia: el nombre y el RUN coinciden y está habilitado como cirujano dentista.</label></p>';
	}
	echo '<p><button type="submit" class="button button-primary" name="psv_accion" value="verificar">Guardar y marcar verificado</button> ';
	if ( 'pendiente' !== $est ) {
		echo '<button type="submit" class="button" name="psv_accion" value="quitar">Quitar verificación</button>';
	}
	echo '</p>';
	$f = ps_ver_faltan( $post->ID );
	if ( $f ) {
		echo '<p class="description">Para verificar falta: ' . esc_html( implode( ', ', $f ) ) . '.</p>';
	}
}

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( ! in_array( $post->post_type, array( 'ps_clinica', 'ps_odontologo' ), true ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ps_ver_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_ver_nonce'] ), 'ps_ver_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$d      = ps_ver_datos( $post_id );
		$campos = ps_ver_campos( $post->post_type );
		$in     = isset( $_POST['psv'] ) ? (array) wp_unslash( $_POST['psv'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $campos as $k => $c ) {
			$tipo = $c[2] ?? 'text';
			if ( 'file' === $tipo ) {
				if ( ! empty( $_POST['psv_borrar'][ $k ] ) ) {
					$d[ $k ] = '';
				}
				if ( ! empty( $_FILES[ 'psv_' . $k ]['name'] ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$aid = media_handle_upload( 'psv_' . $k, $post_id );
					if ( ! is_wp_error( $aid ) ) {
						$d[ $k ] = (int) $aid;
						update_post_meta( $aid, '_ps_privado', '1' );
					}
				}
			} elseif ( array_key_exists( $k, $in ) ) {
				$d[ $k ] = 'director' === $tipo ? absint( $in[ $k ] ) : sanitize_text_field( $in[ $k ] );
			}
		}
		update_post_meta( $post_id, '_ps_ver', $d );
		$acc = sanitize_key( wp_unslash( $_POST['psv_accion'] ?? '' ) );
		if ( 'quitar' === $acc ) {
			update_post_meta( $post_id, '_ps_ver_estado', 'pendiente' );
		} elseif ( 'verificar' === $acc ) {
			if ( empty( $_POST['psv_check'] ) || ps_ver_faltan( $post_id ) ) {
				set_transient( 'ps_ver_msg_' . get_current_user_id(), 'No se marcó verificado: completa los campos con * y confirma la revisión.', 60 );
				return;
			}
			update_post_meta( $post_id, '_ps_ver_estado', 'verificado' );
			update_post_meta( $post_id, '_ps_ver_por', get_current_user_id() );
			update_post_meta( $post_id, '_ps_ver_en', time() );
			update_post_meta( $post_id, '_ps_ver_hasta', strtotime( '+' . PS_VER_MESES . ' months' ) );
			delete_post_meta( $post_id, '_ps_ver_aviso' );
		}
	},
	20,
	2
);

add_action(
	'admin_notices',
	function () {
		$m = get_transient( 'ps_ver_msg_' . get_current_user_id() );
		if ( $m ) {
			delete_transient( 'ps_ver_msg_' . get_current_user_id() );
			echo '<div class="notice notice-warning"><p>' . esc_html( $m ) . '</p></div>';
		}
	}
);

/* Archivos de verificación: fuera de la biblioteca de medios a la vista. */
add_filter(
	'ajax_query_attachments_args',
	function ( $q ) {
		$q['meta_query'] = array( array( 'key' => '_ps_privado', 'compare' => 'NOT EXISTS' ) );
		return $q;
	}
);

/* ---------- Con el sitio publicado: sin verificación no aparece ni vende ---------- */

add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || wp_doing_cron() || ! ps_ver_exigir() ) {
			return;
		}
		$pt = (array) $q->get( 'post_type' );
		if ( ! array_intersect( $pt, array( 'ps_clinica', 'ps_odontologo' ) ) ) {
			return;
		}
		$mq   = (array) $q->get( 'meta_query' );
		$mq[] = array( 'key' => '_ps_ver_estado', 'value' => 'verificado' );
		$mq[] = array( 'key' => '_ps_ver_hasta', 'value' => time(), 'compare' => '>=', 'type' => 'NUMERIC' );
		$q->set( 'meta_query', $mq );
	}
);

add_filter(
	'woocommerce_is_purchasable',
	function ( $ok, $product ) {
		if ( ! $ok || ! ps_ver_exigir() || ! function_exists( 'ps_product_prestador' ) ) {
			return $ok;
		}
		$pid  = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$prov = ps_product_prestador( $pid );
		return $prov ? ps_ver_ok( $prov ) : $ok;
	},
	20,
	2
);

/* ---------- Aviso un mes antes del vencimiento ---------- */

add_action(
	'ps_rec_cron',
	function () {
		$ids = get_posts( array( 'post_type' => array( 'ps_clinica', 'ps_odontologo' ), 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ps_ver_estado', 'meta_value' => 'verificado' ) ); // phpcs:ignore
		$lst = array();
		foreach ( $ids as $id ) {
			$h = (int) get_post_meta( $id, '_ps_ver_hasta', true );
			if ( $h && $h - time() < 30 * DAY_IN_SECONDS && ! get_post_meta( $id, '_ps_ver_aviso', true ) ) {
				$lst[] = get_the_title( $id ) . ' (vence el ' . wp_date( 'j M Y', $h ) . ')';
				update_post_meta( $id, '_ps_ver_aviso', '1' );
			}
		}
		if ( $lst ) {
			$to = function_exists( 'ps_co_buzon' ) ? ps_co_buzon() : get_option( 'admin_email' );
			wp_mail( $to, 'Prevsoul: renovar verificación de prestadores', "Hay que volver a verificar:\n\n- " . implode( "\n- ", $lst ) . "\n\n" . admin_url( 'admin.php?page=ps-red' ) );
		}
	},
	30
);

/* ---------- Prevsoul → Red ---------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Red: verificación', 'Red: verificación', 'manage_options', 'ps-red', 'ps_ver_page' );
	},
	35
);

function ps_ver_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>Red: verificación de prestadores</h1><p>Uso interno. Odontólogos: <a href="' . esc_url( PS_VER_URL_REGISTRO ) . '" target="_blank" rel="noopener">Registro Nacional de Prestadores Individuales</a>. Clínicas: resolución de autorización sanitaria de la SEREMI. La verificación dura ' . (int) PS_VER_MESES . ' meses.</p>';
	echo '<p>' . ( ps_ver_exigir() ? '<strong>El sitio está publicado:</strong> los prestadores sin verificar no aparecen ni venden.' : 'Mientras el sitio esté en construcción, los prestadores sin verificar se pueden seguir probando.' ) . '</p>';
	echo '<table class="widefat striped"><thead><tr><th>Prestador</th><th>Tipo</th><th>Estado</th><th>Vence</th><th>Falta</th></tr></thead><tbody>';
	$col = array( 'verificado' => '#167A64', 'pendiente' => '#996800', 'vencido' => '#b32d2e' );
	$any = false;
	foreach ( array( 'ps_clinica' => 'Clínica', 'ps_odontologo' => 'Odontólogo' ) as $pt => $lab ) {
		foreach ( get_posts( array( 'post_type' => $pt, 'numberposts' => 300, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
			$any = true;
			$e   = ps_ver_estado( $p->ID );
			$h   = (int) get_post_meta( $p->ID, '_ps_ver_hasta', true );
			echo '<tr><td><a href="' . esc_url( get_edit_post_link( $p->ID ) . '#ps_ver' ) . '">' . esc_html( $p->post_title ) . '</a></td><td>' . esc_html( $lab ) . '</td><td style="color:' . esc_attr( $col[ $e ] ) . ';font-weight:600">' . esc_html( ucfirst( $e ) ) . '</td><td>' . ( 'pendiente' === $e ? '—' : esc_html( wp_date( 'j M Y', $h ) ) ) . '</td><td>' . esc_html( 'verificado' === $e ? '' : implode( ', ', ps_ver_faltan( $p->ID ) ) ) . '</td></tr>';
		}
	}
	if ( ! $any ) {
		echo '<tr><td colspan="5"><em>Aún no hay prestadores cargados.</em></td></tr>';
	}
	echo '</tbody></table></div>';
}

/** Todos los prestadores publicados están verificados (y hay al menos uno). */
function ps_ver_red_lista() {
	$ids = get_posts( array( 'post_type' => array( 'ps_clinica', 'ps_odontologo' ), 'numberposts' => -1, 'fields' => 'ids', 'post_status' => 'publish' ) );
	if ( ! $ids ) {
		return false;
	}
	foreach ( $ids as $id ) {
		if ( ! ps_ver_ok( $id ) ) {
			return false;
		}
	}
	return true;
}
