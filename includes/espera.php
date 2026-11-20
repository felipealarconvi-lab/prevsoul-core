<?php
/**
 * «Avísame cuando haya cupo»: lista de espera por producto agotado.
 * Correo automático apenas vuelve a haber cupo; WhatsApp queda como tarea en Recordatorios.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

function ps_esp_lista( $pid ) {
	$l = get_post_meta( $pid, '_ps_espera', true );
	return is_array( $l ) ? $l : array();
}

/** Cuántas personas esperan, por producto, para un prestador (o todas). */
function ps_esp_resumen( $prov = 0 ) {
	$out = array();
	$ids = get_posts( array( 'post_type' => 'product', 'numberposts' => 200, 'fields' => 'ids', 'meta_key' => '_ps_espera', 'meta_compare' => 'EXISTS' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $ids as $pid ) {
		if ( $prov && ! in_array( (string) $prov, array_map( 'strval', (array) get_post_meta( $pid, '_ps_prestador', false ) ), true ) ) {
			continue;
		}
		$n = count( ps_esp_lista( $pid ) );
		if ( $n ) {
			$out[ $pid ] = $n;
		}
	}
	return $out;
}

/** Botón y formulario (tarjeta y página del producto). */
function ps_esp_form( $pid, $compacto = true ) {
	$id   = 'psesp' . (int) $pid . wp_rand( 10, 99 );
	$mail = is_user_logged_in() ? wp_get_current_user()->user_email : '';
	$act  = esc_url( admin_url( 'admin-post.php' ) );
	$h    = '<button type="button" class="ps-btn ps-btn-ghost ps-esp-btn" onclick="this.style.display=\'none\';document.getElementById(\'' . $id . '\').style.display=\'flex\'">'
		. '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg> Avísame</button>';
	$h   .= '<form id="' . $id . '" class="ps-esp-f" method="post" action="' . $act . '" style="display:none;flex-basis:100%;flex-direction:column;gap:6px;margin-top:8px">'
		. '<strong style="font-size:13px">Te avisamos cuando haya cupo</strong>'
		. '<span style="display:flex;gap:4px;font-size:12px"><label style="flex:1;border:1px solid #D9E3F2;border-radius:8px;padding:6px;text-align:center;cursor:pointer"><input type="radio" name="canal" value="correo" checked onchange="this.form.dato.type=\'email\';this.form.dato.placeholder=\'tu@correo.cl\'"> Correo</label><label style="flex:1;border:1px solid #D9E3F2;border-radius:8px;padding:6px;text-align:center;cursor:pointer"><input type="radio" name="canal" value="wa" onchange="this.form.dato.type=\'tel\';this.form.dato.placeholder=\'+56 9 1234 5678\'"> WhatsApp</label></span>'
		. '<input name="dato" type="email" required placeholder="tu@correo.cl" value="' . esc_attr( $mail ) . '" style="width:100%;box-sizing:border-box;border:1px solid #D9E3F2;border-radius:8px;padding:8px;font:inherit;font-size:13px">'
		. '<label style="font-size:12px;color:#4A5B73;display:flex;gap:6px;align-items:flex-start"><input type="checkbox" name="ok" value="1" required> Acepto que Prevsoul me escriba solo para este aviso.</label>'
		. '<input type="hidden" name="action" value="ps_avisame"><input type="hidden" name="pid" value="' . (int) $pid . '"><input type="hidden" name="volver" value="' . esc_attr( ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' ) ) . '">' // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		. wp_nonce_field( 'ps_avisame', '_psn', false, false )
		. '<input type="text" name="web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">'
		. '<button class="ps-btn" type="submit">Avisarme</button></form>';
	if ( isset( $_GET['ps_avisame'] ) && (int) $_GET['ps_avisame'] === (int) $pid ) { // phpcs:ignore WordPress.Security.NonceVerification
		$h = '<span class="ps-esp-ok" style="flex-basis:100%;background:#E6F7F2;color:#167A64;border-radius:10px;padding:8px 10px;font-size:13px;font-weight:600">✓ Listo, te avisaremos apenas haya cupo.</span>';
	}
	return $h;
}

/* Inscripción. */
$ps_esp_post = function () {
	$in   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	$pid  = absint( $in['pid'] ?? 0 );
	$back = wp_validate_redirect( esc_url_raw( (string) ( $in['volver'] ?? '' ) ), home_url( '/' ) );
	if ( ! $pid || 'product' !== get_post_type( $pid ) || ! wp_verify_nonce( (string) ( $in['_psn'] ?? '' ), 'ps_avisame' ) || ! empty( $in['web'] ) || empty( $in['ok'] ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$canal = 'wa' === ( $in['canal'] ?? '' ) ? 'wa' : 'correo';
	$dato  = 'wa' === $canal ? preg_replace( '/[^\d+]/', '', (string) ( $in['dato'] ?? '' ) ) : sanitize_email( (string) ( $in['dato'] ?? '' ) );
	if ( ( 'wa' === $canal && strlen( preg_replace( '/\D/', '', $dato ) ) < 8 ) || ( 'correo' === $canal && ! is_email( $dato ) ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$l = ps_esp_lista( $pid );
	foreach ( $l as $x ) {
		if ( strtolower( $x['dato'] ) === strtolower( $dato ) ) {
			wp_safe_redirect( add_query_arg( 'ps_avisame', $pid, $back ) );
			exit;
		}
	}
	if ( count( $l ) < 500 ) {
		$l[] = array( 'canal' => $canal, 'dato' => $dato, 't' => time(), 'uid' => get_current_user_id() );
		update_post_meta( $pid, '_ps_espera', $l );
	}
	wp_safe_redirect( add_query_arg( 'ps_avisame', $pid, $back ) );
	exit;
};
add_action( 'admin_post_ps_avisame', $ps_esp_post );
add_action( 'admin_post_nopriv_ps_avisame', $ps_esp_post );

/* Página del producto: si está agotado, el formulario va donde iría el botón de compra. */
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( $product && function_exists( 'ps_cupos_producto' ) && 0 === ps_cupos_producto( $product->get_id() ) ) {
			echo '<div class="ps-esp-wrap" style="display:flex;flex-wrap:wrap;gap:8px;margin:6px 0 14px;max-width:360px">' . ps_esp_form( $product->get_id(), false ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	31
);

/* Revisión: cada 15 minutos, y cuando cambia la agenda o una orden. */
add_filter(
	'cron_schedules',
	function ( $s ) {
		$s['ps_15m'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Cada 15 minutos' );
		return $s;
	}
);
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ps_esp_cron' ) ) {
			wp_schedule_event( time() + 120, 'ps_15m', 'ps_esp_cron' );
		}
	}
);
add_action( 'ps_esp_cron', 'ps_esp_revisar' );

function ps_esp_revisar() {
	foreach ( array_keys( ps_esp_resumen() ) as $pid ) {
		$c = function_exists( 'ps_cupos_producto' ) ? ps_cupos_producto( $pid ) : null;
		if ( null !== $c && $c < 1 ) {
			continue;
		}
		ps_esp_avisar( $pid );
	}
}

function ps_esp_avisar( $pid ) {
	$l = ps_esp_lista( $pid );
	if ( ! $l ) {
		return;
	}
	delete_post_meta( $pid, '_ps_espera' );
	$prod = get_the_title( $pid );
	$url  = get_permalink( $pid );
	$prov = (int) get_post_meta( $pid, '_ps_prestador', true );
	$con  = $prov ? ' con ' . get_the_title( $prov ) : '';
	$wa   = get_option( 'ps_esp_wa', array() );
	$wa   = is_array( $wa ) ? $wa : array();
	foreach ( $l as $x ) {
		if ( 'correo' === $x['canal'] ) {
			$inner = '<p>Hola, se liberaron horas para <strong>' . esc_html( $prod ) . '</strong>' . esc_html( $con ) . '.</p><p>Los cupos se toman por orden de compra, así que si te interesa, este es el momento.</p><p><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#1D5FD1;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:700">Comprar ahora</a></p><p style="color:#4A5B73;font-size:13px">Te avisamos una sola vez. Si ya no te interesa, no tienes que hacer nada.</p>';
			wp_mail( $x['dato'], '¡Se liberó un cupo en Prevsoul!', function_exists( 'ps_mail_wrap' ) ? ps_mail_wrap( '¡Se liberó un cupo!', $inner ) : $inner, array( 'Content-Type: text/html; charset=UTF-8' ) );
		} else {
			$wa[] = array( 'tel' => $x['dato'], 'pid' => $pid, 't' => time() );
		}
	}
	update_option( 'ps_esp_wa', array_slice( $wa, -200 ), false );
}

/* Recordatorios: WhatsApp de cupo liberado para enviar a mano. */
add_action(
	'admin_post_ps_esp_wa_ok',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_esp_wa_ok' );
		$wa = get_option( 'ps_esp_wa', array() );
		unset( $wa[ absint( $_POST['i'] ?? -1 ) ] );
		update_option( 'ps_esp_wa', array_values( (array) $wa ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-recordatorios&ok=1' ) );
		exit;
	}
);
function ps_esp_wa_html() {
	$wa = get_option( 'ps_esp_wa', array() );
	if ( ! $wa ) {
		return '';
	}
	$h = '<h2>WhatsApp de cupo liberado (' . count( $wa ) . ')</h2><table class="widefat striped"><thead><tr><th>Para</th><th>Atención</th><th></th></tr></thead><tbody>';
	foreach ( $wa as $i => $x ) {
		$txt = 'Hola, te escribimos de Prevsoul: se liberaron horas para ' . get_the_title( $x['pid'] ) . '. Los cupos se toman por orden de compra: ' . get_permalink( $x['pid'] );
		$lnk = function_exists( 'ps_wa_link' ) ? ps_wa_link( $x['tel'], $txt ) : '';
		$h  .= '<tr><td>' . esc_html( $x['tel'] ) . '</td><td>' . esc_html( get_the_title( $x['pid'] ) ) . '<br><span class="description">' . esc_html( wp_date( 'j M H:i', $x['t'] ) ) . '</span></td><td>' . ( $lnk ? '<a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $lnk ) . '">Abrir WhatsApp</a> ' : '' ) . '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">' . wp_nonce_field( 'ps_esp_wa_ok', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_esp_wa_ok"><input type="hidden" name="i" value="' . (int) $i . '"><button class="button">Marcar enviado</button></form></td></tr>';
	}
	return $h . '</tbody></table>';
}

/* Panel del prestador: cuántas personas esperan. */
function ps_esp_panel_html( $prov ) {
	$r = ps_esp_resumen( $prov );
	if ( ! $r ) {
		return '';
	}
	$parts = array();
	foreach ( $r as $pid => $n ) {
		$parts[] = get_the_title( $pid ) . ' ' . $n;
	}
	$tot = array_sum( $r );
	return '<div style="margin-top:10px;background:#FFF4E0;color:#7A5300;border-radius:10px;padding:10px 12px;font-size:14px;font-weight:600">🔔 ' . (int) $tot . ' ' . ( 1 === $tot ? 'persona espera' : 'personas esperan' ) . ' cupo (' . esc_html( implode( ' · ', $parts ) ) . '). Si liberas una hora en tu agenda, les avisamos solos.</div>';
}
