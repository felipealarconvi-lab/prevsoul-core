<?php
/**
 * Agenda Prevsoul (etapa 1): cupos por prestador y reserva del paciente después de pagar.
 *
 * - Cada clínica u odontólogo define sus «cupos Prevsoul» (días, horarios, duración, bloqueos).
 *   Solo esos cupos se ofrecen, así no chocan con su agenda propia (Dentalink u otra).
 * - El paciente elige hora en la página /agendar/ con un enlace firmado; la hora queda
 *   confirmada al instante y la orden pasa a «Agendada».
 * - Se envía confirmación al paciente (con archivo de calendario) y aviso al prestador.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

const PS_AG_DIAS = array( 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo' );

function ps_ag_cfg( $prov ) {
	$c = get_post_meta( $prov, '_ps_ag', true );
	return wp_parse_args( is_array( $c ) ? $c : array(), array( 'on' => '0', 'dur' => 30, 'dias' => array(), 'bloq' => array(), 'ant' => 24, 'max' => 30 ) );
}

function ps_ag_activa( $prov ) {
	$c = ps_ag_cfg( $prov );
	return '1' === (string) $c['on'] && ! empty( $c['dias'] );
}

/** Página pública de reserva. */
function ps_ag_page_url() {
	$p = get_page_by_path( 'agendar' );
	return $p ? get_permalink( $p ) : home_url( '/agendar/' );
}

add_action(
	'init',
	function () {
		add_shortcode( 'ps_agenda', 'ps_ag_render' );
		if ( '1' !== get_option( 'ps_agenda_v1' ) ) {
			if ( ! get_page_by_path( 'agendar' ) ) {
				$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Agenda tu hora', 'post_name' => 'agendar', 'post_content' => '<!-- wp:shortcode -->[ps_agenda]<!-- /wp:shortcode -->' ) );
				if ( $id && ! is_wp_error( $id ) && defined( 'PS_TEMPLATE' ) ) {
					update_post_meta( $id, '_wp_page_template', PS_TEMPLATE );
				}
			}
			update_option( 'ps_agenda_v1', '1' );
		}
	},
	20
);

/** Firma del enlace de una orden (no se puede adivinar). */
function ps_ag_key( $oid ) {
	return substr( wp_hash( 'ps_ag|' . $oid . '|' . get_post_meta( $oid, '_ps_email', true ) ), 0, 20 );
}

function ps_ag_url( $oid ) {
	return add_query_arg( array( 'o' => (int) $oid, 'k' => ps_ag_key( $oid ) ), ps_ag_page_url() );
}

function ps_ag_valida( $oid, $k ) {
	return $oid && 'ps_orden' === get_post_type( $oid ) && hash_equals( ps_ag_key( $oid ), (string) $k );
}

/** Horas ya tomadas por pacientes Prevsoul en un día (Y-m-d) para un prestador. */
function ps_ag_ocupadas( $prov, $ymd, $excluir = 0 ) {
	$out = array();
	$ids = get_posts(
		array(
			'post_type'   => 'ps_orden',
			'numberposts' => 200,
			'fields'      => 'ids',
			'meta_query'  => array(
				array( 'key' => '_ps_prestador', 'value' => $prov ),
				array( 'key' => '_ps_estado', 'value' => 'agendada' ),
				array( 'key' => '_ps_cita', 'value' => $ymd, 'compare' => 'LIKE' ),
			),
		)
	);
	foreach ( $ids as $id ) {
		if ( (int) $id === (int) $excluir ) {
			continue;
		}
		$out[] = substr( (string) get_post_meta( $id, '_ps_cita', true ), 11, 5 );
	}
	return $out;
}

/** Cupos libres de un día: lista de 'H:i'. */
function ps_ag_cupos( $prov, $ymd, $excluir = 0 ) {
	$c  = ps_ag_cfg( $prov );
	$tz = wp_timezone();
	$d  = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, $tz );
	if ( ! $d || in_array( $ymd, (array) $c['bloq'], true ) ) {
		return array();
	}
	$rangos = $c['dias'][ (int) $d->format( 'N' ) ] ?? array();
	$dur    = max( 10, (int) $c['dur'] );
	$min    = ( new DateTimeImmutable( 'now', $tz ) )->getTimestamp() + max( 0, (int) $c['ant'] ) * HOUR_IN_SECONDS;
	$tom    = ps_ag_ocupadas( $prov, $ymd, $excluir );
	$out    = array();
	foreach ( (array) $rangos as $r ) {
		if ( empty( $r[0] ) || empty( $r[1] ) ) {
			continue;
		}
		$t   = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $r[0], $tz );
		$fin = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $r[1], $tz );
		while ( $t && $fin && $t->modify( '+' . $dur . ' minutes' ) <= $fin ) {
			$h = $t->format( 'H:i' );
			if ( $t->getTimestamp() >= $min && ! in_array( $h, $tom, true ) ) {
				$out[] = $h;
			}
			$t = $t->modify( '+' . $dur . ' minutes' );
		}
	}
	return array_values( array_unique( $out ) );
}

/** Próximos días con cupos (hasta $n). */
function ps_ag_dias_libres( $prov, $n = 8, $excluir = 0 ) {
	$c   = ps_ag_cfg( $prov );
	$tz  = wp_timezone();
	$d   = new DateTimeImmutable( 'today', $tz );
	$out = array();
	for ( $i = 0; $i <= (int) $c['max'] && count( $out ) < $n; $i++ ) {
		$ymd = $d->modify( "+$i days" )->format( 'Y-m-d' );
		if ( ps_ag_cupos( $prov, $ymd, $excluir ) ) {
			$out[] = $ymd;
		}
	}
	return $out;
}

function ps_ag_fecha( $ymd, $fmt = 'l j \d\e F' ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
	return $d ? wp_date( $fmt, $d->getTimestamp(), wp_timezone() ) : $ymd;
}

/* ---------- Página del paciente: [ps_agenda] ---------- */

function ps_ag_render() {
	$oid = isset( $_GET['o'] ) ? absint( $_GET['o'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$k   = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! ps_ag_valida( $oid, $k ) ) {
		return '<div class="ps-card"><p>Este enlace para agendar no es válido. Usa el botón que te enviamos por correo después de tu compra, o escríbenos a ' . esc_html( function_exists( 'ps_co_buzon' ) ? ps_co_buzon() : get_option( 'admin_email' ) ) . '.</p></div>';
	}
	$m    = function ( $x ) use ( $oid ) {
		return get_post_meta( $oid, '_ps_' . $x, true );
	};
	$prov = (int) $m( 'prestador' );
	$st   = $m( 'estado' );
	$css  = '<style>.ps-ag{max-width:620px;margin:0 auto}.ps-ag-d{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}.ps-ag-d a,.ps-ag-h button{border:1px solid #c9d6ea;border-radius:10px;padding:8px 12px;background:#fff;color:#0B2545;text-decoration:none;font:inherit;cursor:pointer;line-height:1.3;text-align:center}.ps-ag-d a.on,.ps-ag-h button:focus{border:2px solid #1D5FD1;color:#1D5FD1}.ps-ag-h{display:grid;grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:8px;margin:12px 0}.ps-ag-ok{background:#E6F7F2;color:#167A64;border-radius:10px;padding:12px 14px}.ps-ag small{color:#4A5B73}</style>';
	$head = '<p class="ps-small" style="color:#167A64;margin:0">Pedido #' . esc_html( $m( 'numero' ) ) . '</p><h2 style="margin:4px 0">' . ( 'agendada' === $st ? 'Tu hora está agendada' : 'Elige tu hora' ) . '</h2><p class="ps-muted">' . esc_html( $m( 'producto' ) ) . ' · ' . esc_html( get_the_title( $prov ) ) . ( get_post_meta( $prov, '_ps_direccion', true ) ? ' · ' . esc_html( get_post_meta( $prov, '_ps_direccion', true ) ) : '' ) . '</p>';
	$out  = $css . '<div class="ps-ag ps-card">' . $head;
	if ( isset( $_GET['ok'] ) && 'agendada' === $st ) { // phpcs:ignore WordPress.Security.NonceVerification
		return $out . '<div class="ps-ag-ok"><strong>¡Listo! Te esperamos el ' . esc_html( ps_fmt_date( $m( 'cita' ) ) ) . '.</strong><br>Te enviamos la confirmación por correo.</div><p><a class="ps-btn ps-btn-ghost" href="' . esc_url( add_query_arg( 'ics', 1, ps_ag_url( $oid ) ) ) . '">Agregar a mi calendario</a></p></div>';
	}
	if ( 'agendada' === $st ) {
		return $out . '<div class="ps-ag-ok">Tu hora: <strong>' . esc_html( ps_fmt_date( $m( 'cita' ) ) ) . '</strong></div><p><a class="ps-btn ps-btn-ghost" href="' . esc_url( add_query_arg( 'ics', 1, ps_ag_url( $oid ) ) ) . '">Agregar a mi calendario</a></p><small>Si necesitas cambiarla, contacta a ' . esc_html( get_the_title( $prov ) ) . ' con tu número de pedido.</small></div>';
	}
	if ( 'recibida' !== $st ) {
		return $out . '<p>Esta atención ya no está pendiente de agendar (' . esc_html( PS_ORDEN_STATES[ $st ] ?? $st ) . ').</p></div>';
	}
	if ( ! ps_ag_activa( $prov ) ) {
		return $out . '<p>' . esc_html( get_the_title( $prov ) ) . ' agenda directamente contigo. Usa los datos de contacto que te enviamos por correo e indica tu número de pedido.</p></div>';
	}
	$dias = ps_ag_dias_libres( $prov, 10 );
	if ( ! $dias ) {
		return $out . '<p>Por ahora no hay horas disponibles en línea. Te avisaremos y también puedes contactar a ' . esc_html( get_the_title( $prov ) ) . ' directamente.</p></div>';
	}
	$sel = isset( $_GET['d'] ) ? sanitize_text_field( wp_unslash( $_GET['d'] ) ) : $dias[0]; // phpcs:ignore WordPress.Security.NonceVerification
	$sel = in_array( $sel, $dias, true ) ? $sel : $dias[0];
	if ( isset( $_GET['tomada'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$out .= '<p style="color:#B42318">Esa hora se acaba de ocupar. Elige otra, por favor.</p>';
	}
	$out .= '<div class="ps-ag-d">';
	foreach ( $dias as $d ) {
		$out .= '<a class="' . ( $d === $sel ? 'on' : '' ) . '" href="' . esc_url( add_query_arg( 'd', $d, ps_ag_url( $oid ) ) ) . '">' . esc_html( ps_ag_fecha( $d, 'D' ) ) . '<br><strong>' . esc_html( ps_ag_fecha( $d, 'j M' ) ) . '</strong></a>';
	}
	$out .= '</div><p style="margin:8px 0 0"><strong>' . esc_html( ucfirst( ps_ag_fecha( $sel ) ) ) . '</strong> · elige una hora</p>';
	$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_agendar"><input type="hidden" name="o" value="' . (int) $oid . '"><input type="hidden" name="k" value="' . esc_attr( $k ) . '"><input type="hidden" name="d" value="' . esc_attr( $sel ) . '"><div class="ps-ag-h">';
	foreach ( ps_ag_cupos( $prov, $sel ) as $h ) {
		$out .= '<button type="submit" name="h" value="' . esc_attr( $h ) . '" onclick="return confirm(\'¿Confirmas tu hora el ' . esc_js( ps_ag_fecha( $sel ) ) . ' a las ' . esc_js( $h ) . '?\')">' . esc_html( $h ) . '</button>';
	}
	$out .= '</div></form><small>Al elegir, tu hora queda confirmada y te llega un correo con la dirección y un archivo para tu calendario.</small></div>';
	return $out;
}

/* ---------- Reservar ---------- */

function ps_ag_reservar() {
	$oid = isset( $_POST['o'] ) ? absint( $_POST['o'] ) : 0;
	$k   = isset( $_POST['k'] ) ? sanitize_text_field( wp_unslash( $_POST['k'] ) ) : '';
	$d   = isset( $_POST['d'] ) ? sanitize_text_field( wp_unslash( $_POST['d'] ) ) : '';
	$h   = isset( $_POST['h'] ) ? sanitize_text_field( wp_unslash( $_POST['h'] ) ) : '';
	if ( ! ps_ag_valida( $oid, $k ) ) {
		wp_die( 'Enlace no válido.' );
	}
	$prov = (int) get_post_meta( $oid, '_ps_prestador', true );
	$back = ps_ag_url( $oid );
	if ( 'recibida' !== get_post_meta( $oid, '_ps_estado', true ) || ! in_array( $h, ps_ag_cupos( $prov, $d, $oid ), true ) ) {
		wp_safe_redirect( add_query_arg( array( 'd' => $d, 'tomada' => 1 ), $back ) );
		exit;
	}
	update_post_meta( $oid, '_ps_cita', $d . 'T' . $h );
	update_post_meta( $oid, '_ps_cita_fuente', 'agenda' );
	ps_orden_set_estado( $oid, 'agendada', 'Paciente (Agenda Prevsoul)' );
	ps_ag_avisar( $oid );
	wp_safe_redirect( add_query_arg( 'ok', 1, $back ) );
	exit;
}
add_action( 'admin_post_ps_agendar', 'ps_ag_reservar' );
add_action( 'admin_post_nopriv_ps_agendar', 'ps_ag_reservar' );

/** Archivo .ics de la cita. */
function ps_ag_ics( $oid ) {
	$c    = (string) get_post_meta( $oid, '_ps_cita', true );
	$prov = (int) get_post_meta( $oid, '_ps_prestador', true );
	$tz   = wp_timezone();
	$ini  = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $c, $tz );
	if ( ! $ini ) {
		return '';
	}
	$fin = $ini->modify( '+' . max( 10, (int) ps_ag_cfg( $prov )['dur'] ) . ' minutes' );
	$utc = new DateTimeZone( 'UTC' );
	$esc = function ( $s ) {
		return str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\\;', '\\,', '\\n' ), (string) $s );
	};
	return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Prevsoul//Agenda//ES\r\nBEGIN:VEVENT\r\nUID:ps-orden-" . (int) $oid . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) . "\r\nDTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\nDTSTART:" . $ini->setTimezone( $utc )->format( 'Ymd\THis\Z' ) . "\r\nDTEND:" . $fin->setTimezone( $utc )->format( 'Ymd\THis\Z' ) . "\r\nSUMMARY:" . $esc( get_post_meta( $oid, '_ps_producto', true ) . ' · ' . get_the_title( $prov ) ) . "\r\nLOCATION:" . $esc( get_post_meta( $prov, '_ps_direccion', true ) ) . "\r\nDESCRIPTION:" . $esc( 'Pedido Prevsoul #' . get_post_meta( $oid, '_ps_numero', true ) ) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
}

add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['ics'], $_GET['o'], $_GET['k'] ) || ! is_page( 'agendar' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$oid = absint( $_GET['o'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! ps_ag_valida( $oid, sanitize_text_field( wp_unslash( $_GET['k'] ) ) ) || ! ( $ics = ps_ag_ics( $oid ) ) ) { // phpcs:ignore
			return;
		}
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=cita-prevsoul.ics' );
		echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	},
	0
);

function ps_ag_avisar( $oid ) {
	$prov  = (int) get_post_meta( $oid, '_ps_prestador', true );
	$cita  = ps_fmt_date( get_post_meta( $oid, '_ps_cita', true ) );
	$num   = get_post_meta( $oid, '_ps_numero', true );
	$prod  = get_post_meta( $oid, '_ps_producto', true );
	$dir   = get_post_meta( $prov, '_ps_direccion', true );
	$mapa  = get_post_meta( $prov, '_ps_mapa', true );
	$ics   = ps_ag_ics( $oid );
	$h     = array( 'Content-Type: text/html; charset=UTF-8' );
	$to    = sanitize_email( (string) get_post_meta( $oid, '_ps_email', true ) );
	if ( $to ) {
		$inner  = '<p>Hola ' . esc_html( get_post_meta( $oid, '_ps_paciente', true ) ) . ', tu hora quedó agendada.</p>';
		$inner .= '<div style="background:#E6F7F2;border-radius:10px;padding:14px 16px;margin:12px 0"><strong style="font-size:17px">' . esc_html( ucfirst( $cita ) ) . '</strong><br>' . esc_html( $prod ) . '<br>' . esc_html( get_the_title( $prov ) ) . ( $dir ? '<br>' . esc_html( $dir ) : '' ) . '</div>';
		$inner .= ( $mapa ? '<p><a href="' . esc_url( $mapa ) . '" style="color:#1D5FD1">Ver cómo llegar</a></p>' : '' ) . '<p>Llega 10 minutos antes e indica tu pedido Prevsoul #' . esc_html( $num ) . '. Te adjuntamos un archivo para agregar la hora a tu calendario.</p><p style="color:#4A5B73;font-size:13px">Si necesitas cambiarla, avísale a ' . esc_html( get_the_title( $prov ) ) . ' con al menos 24 horas de anticipación.</p>';
		$file = '';
		if ( $ics ) {
			$file = trailingslashit( get_temp_dir() ) . 'cita-prevsoul-' . (int) $oid . '.ics';
			file_put_contents( $file, $ics ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		wp_mail( $to, 'Tu hora en Prevsoul: ' . $cita, ps_mail_wrap( 'Hora confirmada', $inner ), $h, $file ? array( $file ) : array() );
		if ( $file && file_exists( $file ) ) {
			wp_delete_file( $file );
		}
	}
	$pto = sanitize_email( (string) get_post_meta( $prov, '_ps_email', true ) );
	if ( $pto ) {
		$inner = '<p>Un paciente Prevsoul agendó en tus cupos:</p><p><strong>' . esc_html( ucfirst( $cita ) ) . '</strong><br>' . esc_html( $prod ) . '<br>Paciente: ' . esc_html( get_post_meta( $oid, '_ps_paciente', true ) ) . ' · ' . esc_html( get_post_meta( $oid, '_ps_telefono', true ) ) . '<br>Pedido #' . esc_html( $num ) . '</p><p>Si también usas otra agenda (por ejemplo Dentalink), anota esta hora ahí.</p><p><a href="' . esc_url( ps_panel_url() ) . '" style="display:inline-block;background:#1D5FD1;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:700">Abrir mi panel</a></p>';
		wp_mail( $pto, 'Prevsoul: nueva hora agendada · ' . $cita, ps_mail_wrap( 'Nueva hora agendada', $inner ), $h );
	}
}

/* ---------- Botón «Elegir mi hora» en los pasos del paciente ---------- */

/** Devuelve el botón de agenda para un pedido y prestador, si su agenda está activa. */
function ps_ag_boton( $order, $prov, $email = false ) {
	if ( ! ps_ag_activa( $prov ) ) {
		return '';
	}
	$ids = get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => 20, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_pedido', 'value' => $order->get_id() ), array( 'key' => '_ps_prestador', 'value' => $prov ) ) ) );
	$out = '';
	foreach ( $ids as $oid ) {
		$st    = get_post_meta( $oid, '_ps_estado', true );
		$label = 'agendada' === $st ? 'Tu hora: ' . ps_fmt_date( get_post_meta( $oid, '_ps_cita', true ) ) : 'Elegir mi hora' . ( count( $ids ) > 1 ? ' · ' . get_post_meta( $oid, '_ps_producto', true ) : '' );
		$style = $email ? ' style="display:inline-block;background:#167A64;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:700;margin:6px 6px 0 0"' : ' class="ps-btn"';
		$out  .= '<a' . $style . ' href="' . esc_url( ps_ag_url( $oid ) ) . '">' . esc_html( $label ) . '</a>';
	}
	return $out;
}

/* ---------- Panel del prestador: «Mi agenda Prevsoul» ---------- */

add_shortcode(
	'ps_agenda_cupos',
	function () {
		$prov = function_exists( 'ps_user_prestador' ) ? ps_user_prestador() : 0;
		if ( ! $prov && current_user_can( 'manage_options' ) && isset( $_GET['prestador'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$prov = absint( $_GET['prestador'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return ps_is_prestador_post( $prov ) ? ps_ag_form( $prov ) : '';
	}
);

function ps_ag_form( $prov ) {
	$c    = ps_ag_cfg( $prov );
	$out  = '<section class="ps-card ps-ag-cfg" id="mi-agenda"><h3 class="ps-card-title">Mi agenda Prevsoul</h3><p class="ps-muted ps-small">Define los cupos que reservas para pacientes Prevsoul. Solo estos horarios se ofrecen en línea, así no chocan con tu otra agenda. Las horas tomadas te llegan por correo y aparecen en tus órdenes.</p>';
	$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_ag_cfg_' . $prov, '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_ag_cfg"><input type="hidden" name="prov" value="' . (int) $prov . '"><input type="hidden" name="back" value="' . esc_attr( remove_query_arg( 'ps_ok' ) ) . '">';
	$out .= '<p><label><input type="checkbox" name="on" value="1" ' . checked( $c['on'], '1', false ) . '> <strong>Recibir reservas en línea</strong></label></p>';
	$out .= '<p><label>Duración de cada cita <select name="dur">';
	foreach ( array( 15, 20, 30, 40, 45, 60, 90 ) as $v ) {
		$out .= '<option value="' . $v . '" ' . selected( (int) $c['dur'], $v, false ) . '>' . $v . ' min</option>';
	}
	$out .= '</select></label> &nbsp; <label>Anticipación mínima <select name="ant">';
	foreach ( array( 2, 4, 12, 24, 48 ) as $v ) {
		$out .= '<option value="' . $v . '" ' . selected( (int) $c['ant'], $v, false ) . '>' . $v . ' h</option>';
	}
	$out .= '</select></label> &nbsp; <label>Mostrar hasta <select name="max">';
	foreach ( array( 14, 30, 45, 60 ) as $v ) {
		$out .= '<option value="' . $v . '" ' . selected( (int) $c['max'], $v, false ) . '>' . $v . ' días</option>';
	}
	$out .= '</select></label></p><table class="ps-ag-tab" style="width:100%;border-collapse:collapse"><tr><th style="text-align:left">Día</th><th style="text-align:left">Bloque 1</th><th style="text-align:left">Bloque 2 (opcional)</th></tr>';
	foreach ( PS_AG_DIAS as $n => $dia ) {
		$r    = $c['dias'][ $n ] ?? array();
		$out .= '<tr><td>' . esc_html( $dia ) . '</td>';
		for ( $b = 0; $b < 2; $b++ ) {
			$out .= '<td><input type="time" name="dias[' . $n . '][' . $b . '][0]" value="' . esc_attr( $r[ $b ][0] ?? '' ) . '" step="900"> a <input type="time" name="dias[' . $n . '][' . $b . '][1]" value="' . esc_attr( $r[ $b ][1] ?? '' ) . '" step="900"></td>';
		}
		$out .= '</tr>';
	}
	$out .= '</table><p><label>Días bloqueados (vacaciones, feriados), separados por coma<br><input type="text" name="bloq" value="' . esc_attr( implode( ', ', (array) $c['bloq'] ) ) . '" placeholder="2026-12-24, 2026-12-25" style="width:100%"></label></p>';
	$out .= '<p><button class="ps-btn" type="submit">Guardar mi agenda</button></p></form>';
	if ( ps_ag_activa( $prov ) ) {
		$prox = ps_ag_dias_libres( $prov, 3 );
		$out .= '<p class="ps-small">Próximos días con cupos libres: ' . ( $prox ? esc_html( implode( ', ', array_map( 'ps_ag_fecha', $prox ) ) ) : 'ninguno' ) . '.</p>';
	}
	return $out . '</section>';
}

add_action(
	'admin_post_ps_ag_cfg',
	function () {
		$prov = isset( $_POST['prov'] ) ? absint( $_POST['prov'] ) : 0;
		if ( ! $prov || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_ag_cfg_' . $prov ) || ! ps_is_prestador_post( $prov ) ) {
			wp_die( 'No permitido.' );
		}
		if ( ps_user_prestador() !== $prov && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Esta agenda no corresponde a tu cuenta.' );
		}
		$in   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$hora = function ( $v ) {
			return preg_match( '/^\d{2}:\d{2}$/', (string) $v ) ? $v : '';
		};
		$dias = array();
		foreach ( PS_AG_DIAS as $n => $dia ) {
			foreach ( (array) ( $in['dias'][ $n ] ?? array() ) as $r ) {
				$a = $hora( $r[0] ?? '' );
				$b = $hora( $r[1] ?? '' );
				if ( $a && $b && $a < $b ) {
					$dias[ $n ][] = array( $a, $b );
				}
			}
		}
		$bloq = array_values( array_filter( array_map( 'trim', explode( ',', (string) ( $in['bloq'] ?? '' ) ) ), function ( $d ) { return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ); } ) );
		update_post_meta(
			$prov,
			'_ps_ag',
			array(
				'on'   => empty( $in['on'] ) ? '0' : '1',
				'dur'  => in_array( (int) ( $in['dur'] ?? 30 ), array( 15, 20, 30, 40, 45, 60, 90 ), true ) ? (int) $in['dur'] : 30,
				'ant'  => in_array( (int) ( $in['ant'] ?? 24 ), array( 2, 4, 12, 24, 48 ), true ) ? (int) $in['ant'] : 24,
				'max'  => in_array( (int) ( $in['max'] ?? 30 ), array( 14, 30, 45, 60 ), true ) ? (int) $in['max'] : 30,
				'dias' => $dias,
				'bloq' => $bloq,
			)
		);
		$back = isset( $in['back'] ) ? esc_url_raw( $in['back'] ) : ps_panel_url();
		wp_safe_redirect( add_query_arg( 'ps_ok', 1, $back ) . '#mi-agenda' );
		exit;
	}
);

/* Enlace a la agenda desde la ficha del prestador en el escritorio. */
add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'ps_clinica', 'ps_odontologo' ) as $pt ) {
			add_meta_box(
				'ps_ag_box',
				'Agenda Prevsoul',
				function ( $post ) {
					$on = ps_ag_activa( $post->ID );
					echo '<p>' . ( $on ? '<span style="color:#00a32a">● Recibe reservas en línea</span>' : '<span style="color:#b26200">● Sin agenda en línea</span>' ) . '</p>';
					if ( 'publish' === $post->post_status ) {
						echo '<p><a class="button" href="' . esc_url( add_query_arg( 'prestador', $post->ID, ps_panel_url() ) . '#mi-agenda' ) . '" target="_blank" rel="noopener">Configurar cupos</a></p>';
					}
					echo '<p class="description">Los cupos se configuran en el panel del prestador (el prestador puede hacerlo él mismo).</p>';
				},
				$pt,
				'side'
			);
		}
	}
);
