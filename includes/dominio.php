<?php
/**
 * Dirección del sitio: muestra WordPress (instalado en /wp/) directamente en la raíz del dominio,
 * sin mover archivos. Escribe index.php y el bloque de reglas en la raíz, cambia la «Dirección del sitio»
 * y deja un botón para volver atrás.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

function ps_dom_root() {
	return trailingslashit( dirname( untrailingslashit( ABSPATH ) ) );
}

function ps_dom_activo() {
	return untrailingslashit( home_url() ) !== untrailingslashit( site_url() );
}

function ps_dom_index() {
	return "<?php\n/**\n * Prevsoul: WordPress vive en /wp/ y se muestra en la raíz del dominio.\n * Archivo creado por Prevsoul Core (Prevsoul → Dirección del sitio).\n */\ndefine( 'WP_USE_THEMES', true );\nrequire __DIR__ . '/wp/wp-blog-header.php';\n";
}

function ps_dom_reglas() {
	return array(
		'<IfModule mod_rewrite.c>',
		'RewriteEngine On',
		'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
		'RewriteBase /',
		'RewriteRule ^index\.php$ - [L]',
		'RewriteCond %{REQUEST_FILENAME} !-f',
		'RewriteCond %{REQUEST_FILENAME} !-d',
		'RewriteRule . /index.php [L]',
		'</IfModule>',
	);
}

function ps_dom_activar() {
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	$root  = ps_dom_root();
	$index = $root . 'index.php';
	if ( ! wp_is_writable( $root ) ) {
		return 'No se puede escribir en la carpeta principal del hosting.';
	}
	if ( file_exists( $index ) ) {
		$cur = (string) file_get_contents( $index ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === strpos( $cur, 'Prevsoul Core' ) ) {
			copy( $index, $root . 'index.php.antes-prevsoul' );
		}
	}
	if ( file_exists( $root . '.htaccess' ) && ! file_exists( $root . '.htaccess.antes-prevsoul' ) ) {
		copy( $root . '.htaccess', $root . '.htaccess.antes-prevsoul' );
	}
	if ( false === file_put_contents( $index, ps_dom_index() ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return 'No se pudo crear el archivo de inicio en la raíz.';
	}
	if ( ! insert_with_markers( $root . '.htaccess', 'WordPress', ps_dom_reglas() ) ) {
		@unlink( $index ); // phpcs:ignore
		return 'No se pudieron escribir las reglas de direcciones en la raíz.';
	}
	update_option( 'ps_dom_antes', home_url(), false );
	update_option( 'home', untrailingslashit( preg_replace( '#/wp/?$#', '', site_url() ) ) );
	update_option( 'ps_dom_flush', '1', false );
	return '';
}

function ps_dom_volver() {
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	$root = ps_dom_root();
	update_option( 'home', site_url() );
	$index = $root . 'index.php';
	if ( file_exists( $index ) && false !== strpos( (string) file_get_contents( $index ), 'Prevsoul Core' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		@unlink( $index ); // phpcs:ignore
		if ( file_exists( $root . 'index.php.antes-prevsoul' ) ) {
			rename( $root . 'index.php.antes-prevsoul', $index );
		}
	}
	if ( file_exists( $root . '.htaccess' ) ) {
		insert_with_markers( $root . '.htaccess', 'WordPress', array() );
	}
	update_option( 'ps_dom_flush', '1', false );
}

/* Tras cambiar la dirección, regenerar los enlaces permanentes en la siguiente carga. */
add_action(
	'init',
	function () {
		if ( '1' === get_option( 'ps_dom_flush' ) ) {
			delete_option( 'ps_dom_flush' );
			flush_rewrite_rules( false );
		}
	},
	99
);

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Dirección del sitio', 'Dirección del sitio', 'manage_options', 'ps-dominio', 'ps_dom_page' );
	},
	40
);

add_action(
	'admin_post_ps_dominio',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso.' );
		}
		check_admin_referer( 'ps_dominio' );
		$que = sanitize_key( wp_unslash( $_POST['que'] ?? '' ) );
		$err = '';
		if ( 'activar' === $que ) {
			$err = ps_dom_activar();
		} elseif ( 'volver' === $que ) {
			ps_dom_volver();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ps-dominio&' . ( $err ? 'err=' . rawurlencode( $err ) : 'ok=' . $que ) ) );
		exit;
	}
);

function ps_dom_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$on   = ps_dom_activo();
	$base = untrailingslashit( preg_replace( '#/wp/?$#', '', site_url() ) );
	echo '<div class="wrap"><h1>Dirección del sitio</h1>';
	if ( ! empty( $_GET['err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['err'] ) ) ) . ' No se cambió nada.</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
	} elseif ( ! empty( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success"><p>Listo. Si una página del sitio te pide entrar de nuevo, inicia sesión otra vez.</p></div>';
	}
	echo '<table class="form-table"><tr><th>Los visitantes ven el sitio en</th><td><code>' . esc_html( home_url( '/' ) ) . '</code></td></tr>';
	echo '<tr><th>Archivos de WordPress y administración</th><td><code>' . esc_html( site_url( '/' ) ) . '</code> (no cambian)</td></tr></table>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'ps_dominio' );
	echo '<input type="hidden" name="action" value="ps_dominio">';
	if ( $on ) {
		echo '<p>El sitio se muestra en <strong>' . esc_html( $base ) . '</strong>. Los enlaces antiguos con <code>/wp/</code> llevan solos a la dirección nueva.</p>';
		echo '<p><button class="button" name="que" value="volver" onclick="return confirm(\'¿Volver a mostrar el sitio en /wp/?\')">Volver a /wp/</button></p>';
	} else {
		echo '<p>Hoy el sitio se ve en <code>/wp/</code> y <strong>' . esc_html( $base ) . '</strong> muestra una lista de carpetas. Este botón hace que ' . esc_html( $base ) . ' abra el sitio directamente, sin mover archivos. Los enlaces con <code>/wp/</code> seguirán funcionando y llevarán a la dirección nueva.</p>';
		echo '<p><button class="button button-primary" name="que" value="activar">Mostrar el sitio en ' . esc_html( $base ) . '</button></p>';
	}
	echo '</form></div>';
}
