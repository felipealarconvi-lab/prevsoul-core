<?php
/**
 * Actualizaciones automáticas desde GitHub (releases de felipealarconvi-lab/prevsoul-core).
 * WordPress revisa cada ~12 horas; esta clase responde con la última versión publicada.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PS_GH_REPO', 'felipealarconvi-lab/prevsoul-core' );

function ps_gh_latest( $force = false ) {
	$cached = get_site_transient( 'ps_gh_latest' );
	if ( ! $force && false !== $cached ) {
		return $cached;
	}
	$res  = wp_remote_get(
		'https://api.github.com/repos/' . PS_GH_REPO . '/releases/latest',
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'Prevsoul-Core/' . PS_VERSION,
			),
		)
	);
	$info = array();
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		$rel = json_decode( wp_remote_retrieve_body( $res ), true );
		$zip = '';
		foreach ( (array) ( $rel['assets'] ?? array() ) as $a ) {
			if ( 'prevsoul-core.zip' === ( $a['name'] ?? '' ) ) {
				$zip = $a['browser_download_url'];
			}
		}
		if ( $zip && ! empty( $rel['tag_name'] ) ) {
			$info = array(
				'version' => ltrim( $rel['tag_name'], 'vV' ),
				'zip'     => $zip,
				'notes'   => (string) ( $rel['body'] ?? '' ),
				'date'    => (string) ( $rel['published_at'] ?? '' ),
			);
		}
	}
	set_site_transient( 'ps_gh_latest', $info, $info ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
	return $info;
}

add_filter(
	'pre_set_site_transient_update_plugins',
	function ( $t ) {
		if ( ! is_object( $t ) ) {
			return $t;
		}
		$file = plugin_basename( PS_FILE );
		$info = ps_gh_latest();
		$item = (object) array(
			'id'          => 'github.com/' . PS_GH_REPO,
			'slug'        => 'prevsoul-core',
			'plugin'      => $file,
			'new_version' => $info ? $info['version'] : PS_VERSION,
			'url'         => 'https://github.com/' . PS_GH_REPO,
			'package'     => $info ? $info['zip'] : '',
		);
		if ( $info && version_compare( $info['version'], PS_VERSION, '>' ) ) {
			$t->response[ $file ] = $item;
		} else {
			unset( $t->response[ $file ] );
			$t->no_update[ $file ] = $item; // Permite activar «Actualizaciones automáticas» en la lista de plugins.
		}
		return $t;
	}
);

/* Ventana «Ver detalles» con las notas de la versión. */
add_filter(
	'plugins_api',
	function ( $res, $action, $args ) {
		if ( 'plugin_information' !== $action || 'prevsoul-core' !== ( $args->slug ?? '' ) ) {
			return $res;
		}
		$info = ps_gh_latest();
		return (object) array(
			'name'          => 'Prevsoul Core',
			'slug'          => 'prevsoul-core',
			'version'       => $info ? $info['version'] : PS_VERSION,
			'author'        => 'Prevsoul',
			'homepage'      => 'https://github.com/' . PS_GH_REPO,
			'download_link' => $info ? $info['zip'] : '',
			'last_updated'  => $info ? $info['date'] : '',
			'sections'      => array( 'changelog' => $info && $info['notes'] ? wpautop( esc_html( $info['notes'] ) ) : 'Sin notas.' ),
		);
	},
	10,
	3
);

/* Después de actualizar, olvidar la versión guardada para no volver a ofrecerla. */
add_action(
	'upgrader_process_complete',
	function () {
		delete_site_transient( 'ps_gh_latest' );
	}
);

/* Botón «Buscar actualizaciones ahora» en la página Prevsoul. */
add_action(
	'admin_post_ps_check_updates',
	function () {
		if ( ! current_user_can( 'update_plugins' ) || ! check_admin_referer( 'ps_check_updates' ) ) {
			wp_die( 'No permitido.' );
		}
		ps_gh_latest( true );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		wp_safe_redirect( admin_url( 'update-core.php' ) );
		exit;
	}
);

/* Activa las actualizaciones automáticas de este plugin la primera vez. */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'ps_autoupdate_set' ) ) {
			return;
		}
		$auto = (array) get_site_option( 'auto_update_plugins', array() );
		$file = plugin_basename( PS_FILE );
		if ( ! in_array( $file, $auto, true ) ) {
			$auto[] = $file;
			update_site_option( 'auto_update_plugins', $auto );
		}
		update_option( 'ps_autoupdate_set', 1 );
	}
);
