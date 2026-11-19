<?php
/**
 * Contacto: formulario en /contacto/ (enlazado en el pie de página), bandeja de casos en
 * Prevsoul → Contacto y copia al paciente con su número de caso.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

const PS_CT_MOTIVOS = array(
	'compra'    => 'Mi compra o mi hora',
	'planes'    => 'Consulta sobre planes',
	'reclamo'   => 'Reclamo',
	'prestador' => 'Soy clínica u odontólogo y quiero sumarme',
	'otro'      => 'Otro',
);
const PS_CT_ESTADOS = array(
	'nuevo'    => 'Nuevo',
	'curso'    => 'En curso',
	'resuelto' => 'Resuelto',
);
const PS_CT_HORARIO = 'Puedes escribirnos las 24 horas, todos los días. Respondemos por correo en horario hábil (lunes a viernes, de 9 a 18 h), en un plazo de hasta 3 días hábiles.';

function ps_ct_url() {
	$p = get_page_by_path( 'contacto' );
	return $p ? get_permalink( $p ) : home_url( '/contacto/' );
}

function ps_ct_num( $id ) {
	return 'PS-' . str_pad( (string) (int) get_post_meta( $id, '_ps_ct_n', true ), 4, '0', STR_PAD_LEFT );
}

/** Plazo de respuesta: 3 días hábiles desde la hora de llegada (si llega fuera de horario, cuenta desde el siguiente día hábil). */
function ps_ct_plazo( $ts ) {
	$d = ( new DateTimeImmutable( '@' . (int) $ts ) )->setTimezone( wp_timezone() );
	if ( (int) $d->format( 'N' ) > 5 || (int) $d->format( 'G' ) >= 18 ) {
		$d = $d->setTime( 9, 0 )->modify( '+1 day' );
	}
	while ( (int) $d->format( 'N' ) > 5 ) {
		$d = $d->modify( '+1 day' );
	}
	for ( $i = 0; $i < 3; $i++ ) {
		$d = $d->modify( '+1 day' );
		while ( (int) $d->format( 'N' ) > 5 ) {
			$d = $d->modify( '+1 day' );
		}
	}
	return $d->setTime( 18, 0 )->getTimestamp();
}

add_action(
	'init',
	function () {
		register_post_type(
			'ps_contacto',
			array(
				'label'           => 'Contacto',
				'public'          => false,
				'show_ui'         => false,
				'capability_type' => 'post',
				'supports'        => array( 'title' ),
			)
		);
		add_shortcode( 'ps_contacto', 'ps_ct_form' );
		if ( '1' !== get_option( 'ps_contacto_v1' ) ) {
			if ( ! get_page_by_path( 'contacto' ) ) {
				$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Contacto', 'post_name' => 'contacto', 'post_content' => '<!-- wp:shortcode -->[ps_contacto]<!-- /wp:shortcode -->' ) );
				if ( $id && ! is_wp_error( $id ) && defined( 'PS_TEMPLATE' ) ) {
					update_post_meta( $id, '_wp_page_template', PS_TEMPLATE );
				}
			}
			update_option( 'ps_contacto_v1', '1' );
		}
	},
	20
);

/* ---------- Formulario ---------- */

function ps_ct_form() {
	$ok = isset( $_GET['ct'] ) ? sanitize_key( wp_unslash( $_GET['ct'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$u  = wp_get_current_user();
	$o  = '<section class="ps-sec ps-bg-claro"><div class="ps-wrap"><div class="ps-card ps-ct" style="max-width:640px;margin:0 auto">';
	$o .= '<h1 class="ps-sec-title" style="margin-top:0">Escríbenos</h1><p class="ps-muted">' . esc_html( PS_CT_HORARIO ) . '</p>';
	if ( 'ok' === $ok && ! empty( $_GET['n'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$n = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( sanitize_text_field( wp_unslash( $_GET['n'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		return $o . '<div class="ps-ok" role="status" style="background:#E6F7F2;color:#167A64;padding:14px 16px;border-radius:10px"><strong>Recibimos tu mensaje.</strong> Tu número de caso es <strong>' . esc_html( $n ) . '</strong> y te enviamos una copia a tu correo.</div><p><a href="' . esc_url( home_url( '/' ) ) . '">Volver al inicio</a></p></div></div></section>';
	}
	if ( 'err' === $ok ) {
		$o .= '<div role="alert" style="background:#FCEBEB;color:#A32D2D;padding:10px 14px;border-radius:10px;margin-bottom:12px">Revisa tu nombre, tu correo y el mensaje, y acepta el uso de tus datos para que podamos responderte.</div>';
	}
	if ( 'lim' === $ok ) {
		$o .= '<div role="alert" style="background:#FCEBEB;color:#A32D2D;padding:10px 14px;border-radius:10px;margin-bottom:12px">Recibimos varios mensajes seguidos desde tu conexión. Intenta de nuevo en unos minutos.</div>';
	}
	$priv = get_privacy_policy_url();
	$in   = 'style="width:100%;padding:10px;border:1px solid #CFD9E8;border-radius:8px;font:inherit"';
	$o   .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-clarity-mask="true">';
	$o   .= '<input type="hidden" name="action" value="ps_contacto"><input type="hidden" name="t" value="' . (int) time() . '">';
	$o   .= '<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Sitio web<input name="web" tabindex="-1" autocomplete="off"></label></div>';
	$o   .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">';
	$o   .= '<label>Nombre<input name="nombre" required maxlength="80" autocomplete="name" value="' . esc_attr( $u->exists() ? $u->display_name : '' ) . '" ' . $in . '></label>';
	$o   .= '<label>Correo<input type="email" name="correo" required maxlength="120" autocomplete="email" value="' . esc_attr( $u->exists() ? $u->user_email : '' ) . '" ' . $in . '></label>';
	$o   .= '<label>Teléfono (opcional)<input type="tel" name="tel" maxlength="20" autocomplete="tel" placeholder="+56 9 1234 5678" ' . $in . '></label>';
	$o   .= '<label>Motivo<select name="motivo" ' . $in . '>';
	foreach ( PS_CT_MOTIVOS as $k => $t ) {
		$o .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $t ) . '</option>';
	}
	$o .= '</select></label><label>N.º de pedido (opcional)<input name="pedido" inputmode="numeric" maxlength="12" placeholder="1042" ' . $in . '></label></div>';
	$o .= '<label style="display:block;margin-top:12px">Mensaje<textarea name="msg" required maxlength="3000" rows="5" ' . $in . '></textarea></label>';
	$o .= '<p style="background:#FAEEDA;color:#854F0B;padding:10px 12px;border-radius:8px;font-size:14px">Por tu privacidad, no escribas aquí síntomas ni datos de tu salud. Si tienes dolor, usa la <a href="' . esc_url( home_url( '/consulta-online/' ) ) . '">Consulta online</a> o llama a tu clínica.</p>';
	$o .= '<label style="display:flex;gap:8px;align-items:flex-start;font-size:14px"><input type="checkbox" name="acepto" value="1" required> <span>Acepto que Prevsoul use estos datos solo para responderme' . ( $priv ? ', según la <a href="' . esc_url( $priv ) . '">política de privacidad</a>' : '' ) . '.</span></label>';
	$o .= '<p><button type="submit" class="ps-btn">Enviar mensaje</button></p></form></div></div></section>';
	return $o;
}

/* ---------- Recepción ---------- */

function ps_ct_recibir() {
	$back = ps_ct_url();
	if ( ! empty( $_POST['web'] ) || ( time() - (int) ( $_POST['t'] ?? 0 ) ) < 3 ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( add_query_arg( array( 'ct' => 'ok', 'n' => 'PS-0000' ), $back ) );
		exit;
	}
	$ip  = md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
	$cnt = (int) get_transient( 'ps_ct_' . $ip );
	if ( $cnt >= 5 ) {
		wp_safe_redirect( add_query_arg( 'ct', 'lim', $back ) );
		exit;
	}
	// phpcs:disable WordPress.Security.NonceVerification
	$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
	$correo = sanitize_email( wp_unslash( $_POST['correo'] ?? '' ) );
	$tel    = preg_replace( '/[^0-9+ ]/', '', sanitize_text_field( wp_unslash( $_POST['tel'] ?? '' ) ) );
	$motivo = sanitize_key( wp_unslash( $_POST['motivo'] ?? 'otro' ) );
	$pedido = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['pedido'] ?? '' ) ) );
	$msg    = sanitize_textarea_field( wp_unslash( $_POST['msg'] ?? '' ) );
	$acepto = ! empty( $_POST['acepto'] );
	// phpcs:enable
	if ( ! $nombre || ! is_email( $correo ) || strlen( $msg ) < 5 || ! $acepto ) {
		wp_safe_redirect( add_query_arg( 'ct', 'err', $back ) );
		exit;
	}
	$motivo = isset( PS_CT_MOTIVOS[ $motivo ] ) ? $motivo : 'otro';
	set_transient( 'ps_ct_' . $ip, $cnt + 1, 10 * MINUTE_IN_SECONDS );
	$n  = (int) get_option( 'ps_ct_seq', 0 ) + 1;
	update_option( 'ps_ct_seq', $n, false );
	$id = wp_insert_post( array( 'post_type' => 'ps_contacto', 'post_status' => 'publish', 'post_title' => PS_CT_MOTIVOS[ $motivo ] . ' · ' . $nombre ) );
	if ( ! $id || is_wp_error( $id ) ) {
		wp_safe_redirect( add_query_arg( 'ct', 'err', $back ) );
		exit;
	}
	$now = time();
	foreach ( array( 'n' => $n, 'nombre' => $nombre, 'correo' => $correo, 'tel' => $tel, 'motivo' => $motivo, 'pedido' => $pedido, 'msg' => $msg, 'estado' => 'nuevo', 'ts' => $now, 'plazo' => ps_ct_plazo( $now ), 'uid' => get_current_user_id() ) as $k => $v ) {
		update_post_meta( $id, '_ps_ct_' . $k, $v );
	}
	$num = ps_ct_num( $id );
	$h   = array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $nombre . ' <' . $correo . '>' );
	$adm = '<p><strong>' . esc_html( $num ) . '</strong> · ' . esc_html( PS_CT_MOTIVOS[ $motivo ] ) . '</p><p><strong>' . esc_html( $nombre ) . '</strong><br>' . esc_html( $correo ) . ( $tel ? '<br>' . esc_html( $tel ) : '' ) . ( $pedido ? '<br>Pedido #' . esc_html( $pedido ) : '' ) . '</p><p style="white-space:pre-line;background:#EAF1FC;padding:12px;border-radius:8px">' . esc_html( $msg ) . '</p><p>Responde a este correo para contestarle, o abre la <a href="' . esc_url( admin_url( 'admin.php?page=ps-contacto' ) ) . '">bandeja de Contacto</a>. Plazo: ' . esc_html( wp_date( 'D j M, H:i', ps_ct_plazo( $now ) ) ) . '.</p>';
	wp_mail( function_exists( 'ps_co_buzon' ) ? ps_co_buzon() : get_option( 'admin_email' ), ( 'reclamo' === $motivo ? 'RECLAMO ' : '' ) . 'Contacto ' . $num . ' · ' . PS_CT_MOTIVOS[ $motivo ], ps_mail_wrap( 'Nuevo mensaje de contacto', $adm ), $h );
	$pac = '<p>Hola ' . esc_html( $nombre ) . ', recibimos tu mensaje. Tu número de caso es <strong>' . esc_html( $num ) . '</strong>.</p><p>' . esc_html( PS_CT_HORARIO ) . '</p><p style="white-space:pre-line;background:#EAF1FC;padding:12px;border-radius:8px">' . esc_html( $msg ) . '</p><p>Si quieres agregar algo, responde a este correo.</p>';
	wp_mail( $correo, 'Recibimos tu mensaje · caso ' . $num, ps_mail_wrap( 'Recibimos tu mensaje', $pac ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	wp_safe_redirect( add_query_arg( array( 'ct' => 'ok', 'n' => $num ), $back ) );
	exit;
}
add_action( 'admin_post_ps_contacto', 'ps_ct_recibir' );
add_action( 'admin_post_nopriv_ps_contacto', 'ps_ct_recibir' );

/* ---------- Bandeja ---------- */

function ps_ct_abiertos() {
	$q = new WP_Query( array( 'post_type' => 'ps_contacto', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_ct_estado', 'value' => 'resuelto', 'compare' => '!=' ) ) ) ); // phpcs:ignore
	return (int) $q->found_posts;
}

add_action(
	'admin_menu',
	function () {
		$n = ps_ct_abiertos();
		add_submenu_page( 'prevsoul', 'Contacto', 'Contacto' . ( $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '' ), 'manage_options', 'ps-contacto', 'ps_ct_page' );
	},
	30
);

add_action(
	'admin_post_ps_ct_estado',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso.' );
		}
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'ps_ct_' . $id );
		$e = sanitize_key( wp_unslash( $_GET['e'] ?? '' ) );
		if ( 'ps_contacto' === get_post_type( $id ) && isset( PS_CT_ESTADOS[ $e ] ) ) {
			update_post_meta( $id, '_ps_ct_estado', $e );
			if ( 'resuelto' === $e ) {
				update_post_meta( $id, '_ps_ct_fin', time() );
			}
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=ps-contacto' ) );
		exit;
	}
);

function ps_ct_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$f = isset( $_GET['f'] ) ? sanitize_key( wp_unslash( $_GET['f'] ) ) : 'abiertos'; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap"><h1>Contacto</h1><p>Los mensajes del formulario de <a href="' . esc_url( ps_ct_url() ) . '" target="_blank">' . esc_html( wp_parse_url( ps_ct_url(), PHP_URL_PATH ) ) . '</a> (enlazado en el pie de página). También llegan a tu buzón; al responder el correo le contestas directo al paciente. ' . esc_html( PS_CT_HORARIO ) . '</p>';
	$tabs = array( 'abiertos' => 'Abiertos', 'nuevo' => 'Nuevos', 'curso' => 'En curso', 'resuelto' => 'Resueltos', 'todos' => 'Todos' );
	echo '<p>';
	foreach ( $tabs as $k => $t ) {
		echo '<a class="button' . ( $f === $k ? ' button-primary' : '' ) . '" style="margin-right:4px" href="' . esc_url( admin_url( 'admin.php?page=ps-contacto&f=' . $k ) ) . '">' . esc_html( $t ) . '</a>';
	}
	echo '</p>';
	$mq = array();
	if ( 'abiertos' === $f ) {
		$mq[] = array( 'key' => '_ps_ct_estado', 'value' => 'resuelto', 'compare' => '!=' );
	} elseif ( isset( PS_CT_ESTADOS[ $f ] ) ) {
		$mq[] = array( 'key' => '_ps_ct_estado', 'value' => $f );
	}
	$ids = get_posts( array( 'post_type' => 'ps_contacto', 'numberposts' => 200, 'fields' => 'ids', 'meta_query' => $mq ) ); // phpcs:ignore
	if ( ! $ids ) {
		echo '<p><em>No hay mensajes en esta vista.</em></p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th style="width:80px">Caso</th><th style="width:170px">Motivo</th><th style="width:220px">Persona</th><th>Mensaje</th><th style="width:130px">Plazo</th><th style="width:190px">Estado</th></tr></thead><tbody>';
	foreach ( $ids as $id ) {
		$m   = function ( $k ) use ( $id ) {
			return get_post_meta( $id, '_ps_ct_' . $k, true );
		};
		$est = (string) $m( 'estado' );
		$pl  = (int) $m( 'plazo' );
		$tar = 'resuelto' !== $est && $pl && time() > $pl;
		$num = ps_ct_num( $id );
		$rec = 'reclamo' === $m( 'motivo' );
		$mail = 'mailto:' . rawurlencode( (string) $m( 'correo' ) ) . '?subject=' . rawurlencode( 'Re: caso ' . $num . ' · Prevsoul' );
		echo '<tr><td><strong>' . esc_html( $num ) . '</strong><br><small>' . esc_html( wp_date( 'j M H:i', (int) $m( 'ts' ) ) ) . '</small></td>';
		echo '<td' . ( $rec ? ' style="color:#b32d2e;font-weight:600"' : '' ) . '>' . esc_html( PS_CT_MOTIVOS[ $m( 'motivo' ) ] ?? '' ) . '</td>';
		echo '<td>' . esc_html( $m( 'nombre' ) ) . '<br><small>' . esc_html( $m( 'correo' ) ) . ( $m( 'tel' ) ? ' · ' . esc_html( $m( 'tel' ) ) : '' ) . ( $m( 'pedido' ) ? '<br>Pedido #' . esc_html( $m( 'pedido' ) ) : '' ) . '</small></td>';
		echo '<td style="white-space:pre-line">' . esc_html( $m( 'msg' ) ) . '</td>';
		echo '<td' . ( $tar ? ' style="color:#b32d2e;font-weight:600"' : '' ) . '>' . ( 'resuelto' === $est ? '—' : esc_html( ( $tar ? 'Vencido · ' : '' ) . wp_date( 'D j M H:i', $pl ) ) ) . '</td><td><a class="button button-small button-primary" href="' . esc_url( $mail ) . '">Responder</a> ';
		foreach ( PS_CT_ESTADOS as $k => $t ) {
			if ( $k === $est ) {
				echo '<strong style="margin:0 4px">' . esc_html( $t ) . '</strong>';
			} else {
				echo '<a style="margin:0 4px" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ps_ct_estado&id=' . $id . '&e=' . $k ), 'ps_ct_' . $id ) ) . '">' . esc_html( $t ) . '</a>';
			}
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}
