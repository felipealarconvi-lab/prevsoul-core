<?php
/**
 * Agenda Prevsoul (etapa 3): recordatorios y seguimiento automáticos.
 *
 * Con hora agendada:
 *   - correo 48 h antes (qué esperar + cambiar o cancelar) y correo la mañana de la cita;
 *   - recordatorio por WhatsApp el día anterior: por defecto llega a la dirección como tarea
 *     («envía este WhatsApp»); el envío automático (WhatsApp Cloud API) queda instalado pero apagado.
 * Sin hora (pagó y no agenda):
 *   - correo al paciente al día 1, 3 y 7; aviso a la clínica a las 48 h; alerta a la dirección al día 5.
 * Vigencia (6 meses desde la compra): aviso al paciente 30 y 7 días antes de vencer.
 *
 * Todo corre cada hora (WP-Cron) y cada aviso se envía una sola vez.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

function ps_rec_cfg() {
	$o = get_option( 'ps_rec_cfg', array() );
	return wp_parse_args(
		is_array( $o ) ? $o : array(),
		array(
			'aviso'    => get_option( 'admin_email' ),
			'wa_modo'  => 'manual',
			'wa_token' => '',
			'wa_phone' => '',
			'wa_tpl'   => 'recordatorio_cita',
			'wa_lang'  => 'es',
			'meses'    => 6,
		)
	);
}

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ps_rec_cron' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'ps_rec_cron' );
		}
	}
);
add_action( 'ps_rec_cron', 'ps_rec_run' );

/** Momento en que la orden quedó (otra vez) «Por agendar». */
function ps_rec_ref( $oid ) {
	$ref = get_post_time( 'U', true, $oid );
	foreach ( (array) get_post_meta( $oid, '_ps_log', true ) as $l ) {
		if ( isset( $l['e'], $l['t'] ) && 'recibida' === $l['e'] ) {
			$ref = max( $ref, (int) $l['t'] );
		}
	}
	return (int) $ref;
}

/** Borra las marcas de «sin hora» cuando la orden vuelve a Por agendar. */
function ps_rec_reset( $oid ) {
	foreach ( array( 'n1', 'n3', 'n7', 'nclin', 'nadm', 'r48', 'rdia', 'rwa' ) as $k ) {
		delete_post_meta( $oid, '_ps_rec_' . $k );
	}
}
add_action(
	'updated_post_meta',
	function ( $mid, $oid, $key, $val ) {
		if ( '_ps_estado' === $key && 'recibida' === $val && 'ps_orden' === get_post_type( $oid ) ) {
			ps_rec_reset( $oid );
		}
		if ( '_ps_cita' === $key && 'ps_orden' === get_post_type( $oid ) ) {
			foreach ( array( 'r48', 'rdia', 'rwa' ) as $k ) {
				delete_post_meta( $oid, '_ps_rec_' . $k );
			}
		}
	},
	10,
	4
);

function ps_rec_flag( $oid, $k ) {
	if ( get_post_meta( $oid, '_ps_rec_' . $k, true ) ) {
		return false;
	}
	update_post_meta( $oid, '_ps_rec_' . $k, time() );
	return true;
}

/** Segundos desde que se tomó la hora actual. */
function ps_rec_agendada_hace( $oid ) {
	$t = 0;
	foreach ( (array) get_post_meta( $oid, '_ps_log', true ) as $l ) {
		if ( isset( $l['e'], $l['t'] ) && 'agendada' === $l['e'] ) {
			$t = max( $t, (int) $l['t'] );
		}
	}
	return $t ? time() - $t : PHP_INT_MAX;
}

function ps_rec_cita_ts( $oid ) {
	$c = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', (string) get_post_meta( $oid, '_ps_cita', true ), wp_timezone() );
	return $c ? $c->getTimestamp() : 0;
}

function ps_rec_btn( $url, $txt ) {
	return '<p><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#167A64;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:700">' . esc_html( $txt ) . '</a></p>';
}

/** Texto del WhatsApp de recordatorio. */
function ps_rec_wa_texto( $oid ) {
	$prov = (int) get_post_meta( $oid, '_ps_prestador', true );
	$nom  = trim( strtok( (string) get_post_meta( $oid, '_ps_paciente', true ), ' ' ) );
	$dir  = get_post_meta( $prov, '_ps_direccion', true );
	return 'Hola ' . $nom . ', te escribimos de Prevsoul para recordarte tu hora de ' . get_post_meta( $oid, '_ps_producto', true ) . ' el ' . ps_fmt_date( get_post_meta( $oid, '_ps_cita', true ) ) . ' en ' . get_the_title( $prov ) . ( $dir ? ', ' . $dir : '' ) . '. Si necesitas cambiarla, puedes hacerlo aquí: ' . ps_ag_url( $oid ) . ' ¡Te esperamos!';
}

/** Envío automático por WhatsApp Cloud API (solo si está activado y configurado). */
function ps_rec_wa_auto( $oid ) {
	$c   = ps_rec_cfg();
	$tel = preg_replace( '/\D+/', '', (string) get_post_meta( $oid, '_ps_telefono', true ) );
	if ( 'auto' !== $c['wa_modo'] || ! $c['wa_token'] || ! $c['wa_phone'] || ! $tel ) {
		return false;
	}
	$prov = (int) get_post_meta( $oid, '_ps_prestador', true );
	$par  = array(
		trim( strtok( (string) get_post_meta( $oid, '_ps_paciente', true ), ' ' ) ),
		ps_fmt_date( get_post_meta( $oid, '_ps_cita', true ) ),
		get_the_title( $prov ),
		ps_ag_url( $oid ),
	);
	$body = array(
		'messaging_product' => 'whatsapp',
		'to'                => $tel,
		'type'              => 'template',
		'template'          => array(
			'name'       => $c['wa_tpl'],
			'language'   => array( 'code' => $c['wa_lang'] ),
			'components' => array( array( 'type' => 'body', 'parameters' => array_map( function ( $t ) { return array( 'type' => 'text', 'text' => (string) $t ); }, $par ) ) ),
		),
	);
	$r = wp_remote_post(
		'https://graph.facebook.com/v20.0/' . rawurlencode( $c['wa_phone'] ) . '/messages',
		array( 'headers' => array( 'Authorization' => 'Bearer ' . $c['wa_token'], 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ), 'timeout' => 15 )
	);
	return ! is_wp_error( $r ) && 2 === (int) ( wp_remote_retrieve_response_code( $r ) / 100 );
}

/** Tareas de WhatsApp para la dirección. */
function ps_rec_tareas() {
	$t = get_option( 'ps_rec_tareas', array() );
	return is_array( $t ) ? $t : array();
}
function ps_rec_tarea_add( $oid, $tipo ) {
	$t = ps_rec_tareas();
	$t[ $oid . '-' . $tipo ] = array( 'oid' => (int) $oid, 'tipo' => $tipo, 't' => time(), 'hecha' => 0 );
	update_option( 'ps_rec_tareas', $t, false );
}

function ps_rec_mail_pac( $oid, $asunto, $inner ) {
	if ( function_exists( 'ps_ag_mail_simple' ) ) {
		ps_ag_mail_simple( $oid, 'paciente', $asunto, $inner );
	}
}

/* ---------- La corrida de cada hora ---------- */

function ps_rec_run() {
	$c     = ps_rec_cfg();
	$now   = time();
	$aviso = array();
	$ids   = get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => 500, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_estado', 'value' => array( 'recibida', 'agendada' ), 'compare' => 'IN' ) ) ) );
	foreach ( $ids as $oid ) {
		$st   = get_post_meta( $oid, '_ps_estado', true );
		$prov = (int) get_post_meta( $oid, '_ps_prestador', true );
		$nom  = esc_html( trim( strtok( (string) get_post_meta( $oid, '_ps_paciente', true ), ' ' ) ) );
		$prod = esc_html( get_post_meta( $oid, '_ps_producto', true ) );
		$clin = esc_html( get_the_title( $prov ) );

		if ( 'agendada' === $st ) {
			$ts = ps_rec_cita_ts( $oid );
			if ( ! $ts || $ts < $now ) {
				continue;
			}
			$falta = $ts - $now;
			$cita  = esc_html( ps_fmt_date( get_post_meta( $oid, '_ps_cita', true ) ) );
			if ( $falta <= 48 * HOUR_IN_SECONDS && $falta > 20 * HOUR_IN_SECONDS && ps_rec_agendada_hace( $oid ) > 6 * HOUR_IN_SECONDS && ps_rec_flag( $oid, 'r48' ) ) {
				$dur = function_exists( 'ps_ag_cfg' ) ? (int) ps_ag_cfg( $prov )['dur'] : 30;
				ps_rec_mail_pac(
					$oid,
					'Tu hora en Prevsoul es pronto: ' . wp_strip_all_tags( $cita ),
					'<p>Hola ' . $nom . ', te recordamos tu hora:</p><div style="background:#E6F7F2;border-radius:10px;padding:14px 16px;margin:12px 0"><strong>' . ucfirst( $cita ) . '</strong><br>' . $prod . ' · ' . $clin . '</div><p><strong>Qué va a pasar:</strong> la atención dura cerca de ' . (int) $dur . ' minutos. Antes de empezar, el odontólogo te explica qué hará y por qué. Puedes hacer todas las preguntas que quieras y pedir una pausa cuando lo necesites.</p><p>Esta hora está reservada solo para ti. Si no puedes ir, cámbiala o cancélala con al menos 24 horas de anticipación y se la damos a otra persona.</p>' . ps_rec_btn( ps_ag_url( $oid ), 'Ver, cambiar o cancelar mi hora' )
				);
			}
			$hoy = wp_date( 'Y-m-d', $now ) === wp_date( 'Y-m-d', $ts );
			if ( $hoy && (int) wp_date( 'G', $now ) >= 7 && $falta > HOUR_IN_SECONDS && ps_rec_flag( $oid, 'rdia' ) ) {
				$dir  = get_post_meta( $prov, '_ps_direccion', true );
				$mapa = get_post_meta( $prov, '_ps_mapa', true );
				ps_rec_mail_pac( $oid, 'Hoy es tu hora en Prevsoul', '<p>Hola ' . $nom . ', hoy te esperamos:</p><p><strong>' . ucfirst( $cita ) . '</strong><br>' . $clin . ( $dir ? '<br>' . esc_html( $dir ) : '' ) . '</p>' . ( $mapa ? ps_rec_btn( $mapa, 'Cómo llegar' ) : '' ) . '<p>Llega 10 minutos antes. ¡Nos vemos!</p>' );
			}
			if ( $falta <= 26 * HOUR_IN_SECONDS && $falta > 2 * HOUR_IN_SECONDS && get_post_meta( $oid, '_ps_telefono', true ) && ps_rec_flag( $oid, 'rwa' ) ) {
				if ( ! ps_rec_wa_auto( $oid ) ) {
					ps_rec_tarea_add( $oid, 'recordatorio' );
					$aviso[] = '<li><strong>WhatsApp de recordatorio</strong> para ' . $nom . ' (' . $cita . ', ' . $clin . '). <a href="' . esc_url( ps_wa_link( get_post_meta( $oid, '_ps_telefono', true ), ps_rec_wa_texto( $oid ) ) ) . '">Abrir WhatsApp con el mensaje listo</a></li>';
				}
			}
			continue;
		}

		/* Por agendar. */
		$dias = ( $now - ps_rec_ref( $oid ) ) / DAY_IN_SECONDS;
		$url  = ps_ag_url( $oid );
		$msgs = array(
			'n1' => array( 1, '¿Ya elegiste tu hora?', '<p>Hola ' . $nom . ', tu ' . $prod . ' ya está pagada. Solo falta elegir el día y la hora que te acomoden.</p>' ),
			'n3' => array( 3, 'Tu atención te está esperando', '<p>Hola ' . $nom . ', todavía no tienes hora para tu ' . $prod . '. Elegirla toma un minuto, y mientras antes te atiendas, más fácil es prevenir.</p>' ),
			'n7' => array( 7, 'Te ayudamos a agendar', '<p>Hola ' . $nom . ', vimos que aún no agendas tu ' . $prod . '. Si algo te complica (horario, distancia o un poco de nervios con el dentista, que es muy común), respóndenos este correo y lo resolvemos contigo.</p>' ),
		);
		foreach ( $msgs as $k => $m ) {
			if ( $dias >= $m[0] && $dias < $m[0] + 3 && ps_rec_flag( $oid, $k ) ) {
				ps_rec_mail_pac( $oid, $m[1], $m[2] . ps_rec_btn( $url, 'Elegir mi hora' ) );
			}
		}
		if ( $dias >= 2 && ps_rec_flag( $oid, 'nclin' ) && function_exists( 'ps_ag_mail_simple' ) ) {
			ps_ag_mail_simple( $oid, 'prestador', 'Prevsoul: paciente aún sin hora', '<p>El paciente ' . esc_html( get_post_meta( $oid, '_ps_paciente', true ) ) . ' (' . esc_html( get_post_meta( $oid, '_ps_telefono', true ) ) . ') compró ' . $prod . ' hace 2 días y todavía no agenda. ¿Puedes contactarlo para darle hora? Pedido #' . esc_html( get_post_meta( $oid, '_ps_numero', true ) ) . '.</p>' );
		}
		if ( $dias >= 5 && ps_rec_flag( $oid, 'nadm' ) ) {
			ps_rec_tarea_add( $oid, 'sin_hora' );
			$aviso[] = '<li><strong>Sin hora hace 5 días:</strong> ' . $nom . ' · ' . $prod . ' · ' . $clin . '. Llamar o cambiar de clínica. <a href="' . esc_url( ps_wa_link( get_post_meta( $oid, '_ps_telefono', true ), 'Hola ' . wp_strip_all_tags( $nom ) . ', te escribimos de Prevsoul por tu compra de ' . wp_strip_all_tags( $prod ) . '. ¿Te ayudamos a elegir tu hora? Puedes hacerlo aquí: ' . $url ) ) . '">WhatsApp</a></li>';
		}
		/* Vigencia. */
		$vence = strtotime( '+' . (int) $c['meses'] . ' months', get_post_time( 'U', true, $oid ) );
		$resta = ( $vence - $now ) / DAY_IN_SECONDS;
		foreach ( array( 'v30' => 30, 'v7' => 7 ) as $k => $d ) {
			if ( $resta <= $d && $resta > 0 && ps_rec_flag( $oid, $k ) ) {
				ps_rec_mail_pac( $oid, 'Te quedan ' . (int) ceil( $resta ) . ' días para usar tu atención', '<p>Hola ' . $nom . ', tu ' . $prod . ' vence el ' . esc_html( wp_date( 'j \d\e F', $vence ) ) . '. Elige tu hora antes para no perderla.</p>' . ps_rec_btn( $url, 'Elegir mi hora' ) );
			}
		}
	}
	if ( $aviso && is_email( $c['aviso'] ) ) {
		wp_mail( $c['aviso'], 'Prevsoul: ' . count( $aviso ) . ' recordatorio(s) para enviar', ps_mail_wrap( 'Tareas de seguimiento', '<ul>' . implode( '', $aviso ) . '</ul><p>Al enviarlos, márcalos como hechos en Prevsoul → Recordatorios.</p><p><a href="' . esc_url( admin_url( 'admin.php?page=ps-recordatorios' ) ) . '">Abrir Recordatorios</a></p>' ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}

/* ---------- Escritorio: Prevsoul → Recordatorios ---------- */

add_action(
	'admin_menu',
	function () {
		$n = count( array_filter( ps_rec_tareas(), function ( $t ) { return empty( $t['hecha'] ); } ) );
		add_submenu_page( 'prevsoul', 'Recordatorios', 'Recordatorios' . ( $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '' ), 'manage_options', 'ps-recordatorios', 'ps_rec_page' );
	},
	18
);

add_action(
	'admin_post_ps_rec',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_rec' );
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $in['hecha'] ) ) {
			$t = ps_rec_tareas();
			$k = sanitize_text_field( $in['hecha'] );
			if ( isset( $t[ $k ] ) ) {
				$t[ $k ]['hecha'] = time();
				update_option( 'ps_rec_tareas', $t, false );
			}
		} elseif ( ! empty( $in['correr'] ) ) {
			ps_rec_run();
		} else {
			$c             = ps_rec_cfg();
			$c['aviso']    = sanitize_email( $in['aviso'] ?? $c['aviso'] );
			$c['wa_modo']  = in_array( $in['wa_modo'] ?? '', array( 'manual', 'auto', 'off' ), true ) ? $in['wa_modo'] : 'manual';
			$c['wa_phone'] = preg_replace( '/\D+/', '', (string) ( $in['wa_phone'] ?? '' ) );
			$c['wa_tpl']   = sanitize_key( $in['wa_tpl'] ?? $c['wa_tpl'] );
			$c['wa_lang']  = sanitize_text_field( $in['wa_lang'] ?? $c['wa_lang'] );
			if ( ! empty( $in['wa_token'] ) ) {
				$c['wa_token'] = sanitize_text_field( $in['wa_token'] );
			}
			update_option( 'ps_rec_cfg', $c, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ps-recordatorios&ok=1' ) );
		exit;
	}
);

function ps_rec_page() {
	$c   = ps_rec_cfg();
	$t   = ps_rec_tareas();
	$pen = array_filter( $t, function ( $x ) { return empty( $x['hecha'] ) && 'ps_orden' === get_post_type( $x['oid'] ); } );
	uasort( $pen, function ( $a, $b ) { return $b['t'] <=> $a['t']; } );
	$nonce = wp_nonce_field( 'ps_rec', '_wpnonce', true, false );
	$act   = esc_url( admin_url( 'admin-post.php' ) );
	echo '<div class="wrap"><h1>Recordatorios</h1>';
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success"><p>Listo.</p></div>';
	}
	echo '<p>Lo que el sitio hace solo cada hora: correo al paciente 48 h antes y la mañana de su cita; a quien pagó y no agenda, correos al día 1, 3 y 7, aviso a la clínica a las 48 h y una tarea para ti al día 5; avisos de vencimiento 30 y 7 días antes. Los WhatsApp te llegan aquí y a tu correo como tareas.</p>';
	echo '<h2>WhatsApp por enviar (' . count( $pen ) . ')</h2><table class="widefat striped"><thead><tr><th>Para</th><th>Motivo</th><th>Cita / compra</th><th></th></tr></thead><tbody>';
	if ( ! $pen ) {
		echo '<tr><td colspan="4">No hay WhatsApp pendientes.</td></tr>';
	}
	foreach ( $pen as $k => $x ) {
		$oid = $x['oid'];
		$txt = 'recordatorio' === $x['tipo'] ? ps_rec_wa_texto( $oid ) : 'Hola ' . trim( strtok( (string) get_post_meta( $oid, '_ps_paciente', true ), ' ' ) ) . ', te escribimos de Prevsoul por tu compra de ' . get_post_meta( $oid, '_ps_producto', true ) . '. ¿Te ayudamos a elegir tu hora? Puedes hacerlo aquí: ' . ps_ag_url( $oid );
		$wa  = ps_wa_link( get_post_meta( $oid, '_ps_telefono', true ), $txt );
		echo '<tr><td><strong>' . esc_html( get_post_meta( $oid, '_ps_paciente', true ) ) . '</strong><br>' . esc_html( get_post_meta( $oid, '_ps_telefono', true ) ) . '</td><td>' . ( 'recordatorio' === $x['tipo'] ? 'Recordar su cita de mañana' : 'Lleva 5 días sin agendar' ) . '</td><td>' . esc_html( get_post_meta( $oid, '_ps_producto', true ) ) . '<br>' . esc_html( get_post_meta( $oid, '_ps_cita', true ) ? ps_fmt_date( get_post_meta( $oid, '_ps_cita', true ) ) : 'sin hora' ) . '</td><td>' . ( $wa ? '<a class="button button-primary" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">Abrir WhatsApp</a> ' : '' ) . '<form method="post" action="' . $act . '" style="display:inline">' . $nonce . '<input type="hidden" name="action" value="ps_rec"><input type="hidden" name="hecha" value="' . esc_attr( $k ) . '"><button class="button">Marcar enviado</button></form></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	echo '<h2>Ajustes</h2><form method="post" action="' . $act . '">' . $nonce . '<input type="hidden" name="action" value="ps_rec"><table class="form-table">'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<tr><th>Correo que recibe las tareas</th><td><input type="email" name="aviso" value="' . esc_attr( $c['aviso'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th>Recordatorios por WhatsApp</th><td><select name="wa_modo"><option value="manual" ' . selected( $c['wa_modo'], 'manual', false ) . '>Avisarme para enviarlos yo (recomendado ahora)</option><option value="auto" ' . selected( $c['wa_modo'], 'auto', false ) . '>Enviar automático con WhatsApp Business (Cloud API)</option><option value="off" ' . selected( $c['wa_modo'], 'off', false ) . '>Sin WhatsApp</option></select><p class="description">El envío automático necesita una cuenta de WhatsApp Business Platform, un número verificado y una plantilla aprobada por Meta con 4 variables: nombre, fecha y hora, clínica y enlace. Si falta algo, el sitio vuelve a avisarte para enviarlo tú.</p></td></tr>';
	echo '<tr><th>Identificador del número (Phone number ID)</th><td><input type="text" name="wa_phone" value="' . esc_attr( $c['wa_phone'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th>Token de acceso</th><td><input type="password" name="wa_token" value="" autocomplete="new-password" class="regular-text" placeholder="' . ( $c['wa_token'] ? 'Guardado (escribe solo para cambiarlo)' : 'Lo pegas tú' ) . '"></td></tr>';
	echo '<tr><th>Plantilla y idioma</th><td><input type="text" name="wa_tpl" value="' . esc_attr( $c['wa_tpl'] ) . '"> <input type="text" name="wa_lang" value="' . esc_attr( $c['wa_lang'] ) . '" size="6"></td></tr>';
	echo '</table><p><button class="button button-primary">Guardar</button></p></form>';
	echo '<form method="post" action="' . $act . '">' . $nonce . '<input type="hidden" name="action" value="ps_rec"><input type="hidden" name="correr" value="1"><p><button class="button">Revisar ahora</button> <span class="description">Próxima revisión automática: ' . esc_html( wp_next_scheduled( 'ps_rec_cron' ) ? wp_date( 'j M H:i', wp_next_scheduled( 'ps_rec_cron' ) ) : '—' ) . '</span></p></form></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
