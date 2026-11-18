<?php
/**
 * Prevsoul → Correo: acceso a la bandeja, estado del envío (SPF, DKIM, DMARC),
 * correo de prueba e historial de los correos que envía el sitio.
 *
 * Privacidad: el historial guarda correos de destino enmascarados y se borra a los 90 días.
 * No se guarda ninguna contraseña de buzón.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

function ps_co_buzon() {
	$a = get_option( 'woocommerce_email_from_address', '' );
	return is_email( $a ) ? $a : 'contacto@prevsoul.cl';
}

function ps_co_webmail() {
	$u = (string) get_option( 'ps_correo_webmail', '' );
	return $u ? $u : 'https://' . wp_parse_url( home_url(), PHP_URL_HOST ) . ':2096/';
}

/** Correos de WordPress (contraseñas, avisos) salen desde el buzón de Prevsoul y no desde wordpress@. */
add_filter(
	'wp_mail_from',
	function ( $from ) {
		return 0 === strpos( (string) $from, 'wordpress@' ) ? ps_co_buzon() : $from;
	}
);
add_filter(
	'wp_mail_from_name',
	function ( $name ) {
		return 'WordPress' === $name ? 'Prevsoul' : $name;
	}
);

/** Enmascara un correo: fel•••@gmail.com. */
function ps_co_mask( $e ) {
	$e = trim( (string) $e );
	if ( preg_match( '/<([^>]+)>/', $e, $m ) ) {
		$e = $m[1];
	}
	if ( false === strpos( $e, '@' ) ) {
		return '—';
	}
	list( $u, $d ) = explode( '@', $e, 2 );
	return mb_substr( $u, 0, 3 ) . '•••@' . $d;
}

function ps_co_log_add( $to, $subject, $ok, $err = '' ) {
	$log = get_option( 'ps_correo_log', array() );
	$log = is_array( $log ) ? $log : array();
	$lim = time() - 90 * DAY_IN_SECONDS;
	$log = array_values( array_filter( $log, function ( $r ) use ( $lim ) { return isset( $r['t'] ) && $r['t'] >= $lim; } ) );
	$to  = is_array( $to ) ? $to : explode( ',', (string) $to );
	array_unshift(
		$log,
		array(
			't'  => time(),
			'to' => implode( ', ', array_map( 'ps_co_mask', array_slice( $to, 0, 3 ) ) ) . ( count( $to ) > 3 ? ' +' . ( count( $to ) - 3 ) : '' ),
			's'  => mb_substr( wp_strip_all_tags( (string) $subject ), 0, 90 ),
			'ok' => $ok ? 1 : 0,
			'e'  => mb_substr( (string) $err, 0, 140 ),
		)
	);
	update_option( 'ps_correo_log', array_slice( $log, 0, 300 ), false );
}

add_action(
	'wp_mail_succeeded',
	function ( $d ) {
		ps_co_log_add( $d['to'] ?? '', $d['subject'] ?? '', true );
	}
);
add_action(
	'wp_mail_failed',
	function ( $err ) {
		$d = is_wp_error( $err ) ? (array) $err->get_error_data() : array();
		ps_co_log_add( $d['to'] ?? '', $d['subject'] ?? '', false, is_wp_error( $err ) ? $err->get_error_message() : '' );
	}
);

/** Revisa SPF, DKIM y DMARC del dominio del buzón (se guarda 12 horas). */
function ps_co_dns( $force = false ) {
	$c = get_transient( 'ps_correo_dns' );
	if ( $c && ! $force ) {
		return $c;
	}
	$dom = substr( strrchr( ps_co_buzon(), '@' ), 1 );
	$txt = function ( $h ) {
		$r = function_exists( 'dns_get_record' ) ? @dns_get_record( $h, DNS_TXT ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return is_array( $r ) ? array_map( function ( $x ) { return isset( $x['txt'] ) ? $x['txt'] : ''; }, $r ) : array();
	};
	$spf   = array_values( array_filter( $txt( $dom ), function ( $t ) { return 0 === stripos( $t, 'v=spf1' ); } ) );
	$dkim  = array_values( array_filter( $txt( 'default._domainkey.' . $dom ), function ( $t ) { return false !== stripos( $t, 'p=' ); } ) );
	$dmarc = array_values( array_filter( $txt( '_dmarc.' . $dom ), function ( $t ) { return 0 === stripos( $t, 'v=DMARC1' ); } ) );
	$pol   = $dmarc && preg_match( '/p=(\w+)/i', $dmarc[0], $m ) ? strtolower( $m[1] ) : '';
	$c     = array( 'dom' => $dom, 'spf' => (bool) $spf, 'dkim' => (bool) $dkim, 'dmarc' => (bool) $dmarc, 'pol' => $pol, 'at' => time() );
	set_transient( 'ps_correo_dns', $c, 12 * HOUR_IN_SECONDS );
	return $c;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Correo', 'Correo', 'manage_options', 'ps-correo', 'ps_co_page' );
	},
	18
);

add_action(
	'admin_post_ps_correo_prueba',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_correo_prueba' );
		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		if ( ! is_email( $to ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ps-correo&r=mal' ) );
			exit;
		}
		$body = "Hola:\n\nEste es un correo de prueba enviado desde prevsoul.cl para revisar que los correos lleguen a la bandeja de entrada.\n\nSi lo encontraste en Spam, márcalo como «No es spam» y avísanos.\n\nPrevsoul\n" . home_url( '/' );
		$ok   = wp_mail( $to, 'Prueba de correo de Prevsoul', $body, array( 'From: Prevsoul <' . ps_co_buzon() . '>', 'Reply-To: Prevsoul <' . ps_co_buzon() . '>' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-correo&r=' . ( $ok ? 'ok' : 'error' ) ) );
		exit;
	}
);

add_action(
	'admin_post_ps_correo_dns',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_correo_dns' );
		ps_co_dns( true );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-correo' ) );
		exit;
	}
);

function ps_co_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$b   = ps_co_buzon();
	$d   = ps_co_dns();
	$log = get_option( 'ps_correo_log', array() );
	$r   = isset( $_GET['r'] ) ? sanitize_key( wp_unslash( $_GET['r'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$ok  = function ( $v, $t ) {
		return '<p class="ps-co-l ' . ( $v ? 'si' : 'no' ) . '"><span class="dashicons dashicons-' . ( $v ? 'yes' : 'warning' ) . '"></span> ' . esc_html( $t ) . '</p>';
	};
	?>
	<div class="wrap ps-co">
		<style>
			.ps-co-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin:16px 0}
			.ps-co-c{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}
			.ps-co-c h2{margin:0 0 8px;font-size:14px;color:#50575e;font-weight:500}
			.ps-co-b{font-size:16px;font-weight:600;margin:0 0 10px}
			.ps-co-l{margin:4px 0}.ps-co-l.si{color:#00a32a}.ps-co-l.no{color:#b26200}
			.ps-co-m{color:#646970;font-size:12px;margin:8px 0 0}
			.ps-co-t td.f{color:#d63638}.ps-co-t td.k{color:#00a32a}
		</style>
		<h1>Correo</h1>
		<?php if ( 'ok' === $r ) : ?>
			<div class="notice notice-success"><p>Correo de prueba enviado. Revisa si llegó a Recibidos o a Spam.</p></div>
		<?php elseif ( 'error' === $r ) : ?>
			<div class="notice notice-error"><p>El servidor no pudo enviar el correo. Revisa el historial abajo.</p></div>
		<?php elseif ( 'mal' === $r ) : ?>
			<div class="notice notice-warning"><p>Escribe un correo válido para la prueba.</p></div>
		<?php endif; ?>
		<div class="ps-co-g">
			<div class="ps-co-c">
				<h2><span class="dashicons dashicons-email-alt"></span> Bandeja de contacto</h2>
				<p class="ps-co-b"><?php echo esc_html( $b ); ?></p>
				<p><a class="button button-primary" href="<?php echo esc_url( ps_co_webmail() ); ?>" target="_blank" rel="noopener">Abrir bandeja ↗</a></p>
				<p class="ps-co-m">Se abre en otra pestaña (la primera vez pide la clave del buzón). Lo que llega aquí también se reenvía a tu Gmail.</p>
			</div>
			<div class="ps-co-c">
				<h2><span class="dashicons dashicons-shield"></span> Estado del envío</h2>
				<?php
				echo $ok( true, 'Remitente: Prevsoul <' . $b . '>' ); // phpcs:ignore
				echo $ok( $d['spf'], $d['spf'] ? 'SPF configurado' : 'Falta SPF en el dominio' ); // phpcs:ignore
				echo $ok( $d['dkim'], $d['dkim'] ? 'DKIM configurado' : 'Falta DKIM en el dominio' ); // phpcs:ignore
				echo $ok( $d['dmarc'] && 'none' !== $d['pol'], $d['dmarc'] ? ( 'none' === $d['pol'] ? 'DMARC en modo observar (p=none)' : 'DMARC activo (p=' . $d['pol'] . ')' ) : 'Falta DMARC en el dominio' ); // phpcs:ignore
				?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
					<input type="hidden" name="action" value="ps_correo_dns"><?php wp_nonce_field( 'ps_correo_dns' ); ?>
					<button class="button button-small">Revisar de nuevo</button>
					<span class="ps-co-m">Revisado <?php echo esc_html( human_time_diff( $d['at'] ) ); ?> atrás.</span>
				</form>
			</div>
			<div class="ps-co-c">
				<h2><span class="dashicons dashicons-email"></span> Correo de prueba</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ps_correo_prueba"><?php wp_nonce_field( 'ps_correo_prueba' ); ?>
					<p><input type="email" name="to" class="regular-text" style="width:100%" placeholder="nombre@gmail.com" required></p>
					<p><button class="button">Enviar prueba</button></p>
				</form>
				<p class="ps-co-m">Envía un correo desde <?php echo esc_html( $b ); ?> para comprobar que llega a Recibidos y no a Spam.</p>
			</div>
		</div>
		<h2>Correos enviados por el sitio</h2>
		<table class="widefat striped ps-co-t">
			<thead><tr><th style="width:16%">Fecha</th><th style="width:26%">Para</th><th>Asunto</th><th style="width:14%">Estado</th></tr></thead>
			<tbody>
			<?php if ( empty( $log ) ) : ?>
				<tr><td colspan="4">Todavía no hay correos registrados. Desde ahora queda anotado cada correo que envíe el sitio.</td></tr>
			<?php else : ?>
				<?php foreach ( array_slice( $log, 0, 100 ) as $x ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( 'd-m-Y H:i', $x['t'] ) ); ?></td>
						<td><?php echo esc_html( $x['to'] ); ?></td>
						<td><?php echo esc_html( $x['s'] ); ?></td>
						<td class="<?php echo $x['ok'] ? 'k' : 'f'; ?>" title="<?php echo esc_attr( $x['e'] ); ?>"><?php echo $x['ok'] ? 'Enviado' : 'Falló'; ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<p class="ps-co-m">Se guardan los últimos 90 días (máximo 300). Los correos de destino se muestran enmascarados. «Enviado» significa que el servidor lo despachó; si llega a Recibidos o a Spam lo decide el correo de quien lo recibe.</p>
	</div>
	<?php
}
