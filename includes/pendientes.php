<?php
/**
 * Lista «Antes de publicar»: pendientes para abrir el sitio al público.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

function ps_pend_base() {
	return array(
		'demo'      => 'Apagar los datos de demostración de la prueba social (Prevsoul → Prueba social).',
		'datos'     => 'Borrar los datos de prueba: Dra. Camila Prueba, clínica de prueba y cupón PRUEBA100.',
		'soul'      => 'Elegir el color de «soul» en el logo y generar los archivos definitivos del logo.',
		'media'     => 'Revisar y borrar las imágenes de prueba del logo en la biblioteca de medios.',
		'red'       => 'Cargar odontólogos y clínicas reales de la red.',
		'legal'     => 'Revisión legal: «plan» y no «seguro» en todo el sitio y documentos.',
		'iva'       => 'Definir el IVA en los precios.',
		'precios'   => 'Cerrar la estructura de precios y la comisión a clínicas y odontólogos.',
		'resenas'   => 'Juntar reseñas reales (con autorización de testimonio) para la prueba social.',
		'meta'      => 'Definir el presupuesto de campañas en Meta.',
		'agentes'   => 'Crear los agentes pendientes: gestor de red, analista de precios, media buyer, editor de blog/SEO, analista de datos y cumplimiento.',
		'manual'    => 'Actualizar el manual de usuario con Vista responsive, Prueba social y esta lista.',
	);
}

function ps_pend_state() {
	$s = get_option( 'ps_pendientes', array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), array( 'hecho' => array(), 'extra' => array() ) );
}

/** Estado automático de algunos ítems. */
function ps_pend_auto( $k ) {
	if ( 'demo' === $k ) {
		return '1' !== (string) get_option( 'ps_social_demo', '' );
	}
	if ( 'datos' === $k ) {
		return ! get_option( 'ps_demo' );
	}
	return null;
}

function ps_pend_items() {
	$st  = ps_pend_state();
	$out = array();
	foreach ( ps_pend_base() as $k => $t ) {
		$a     = ps_pend_auto( $k );
		$out[] = array( $k, $t, null === $a ? ! empty( $st['hecho'][ $k ] ) : $a, null !== $a );
	}
	foreach ( $st['extra'] as $i => $t ) {
		$k     = 'x' . $i;
		$out[] = array( $k, $t, ! empty( $st['hecho'][ $k ] ), false );
	}
	return $out;
}

function ps_pend_faltan() {
	return count( array_filter( ps_pend_items(), function ( $i ) { return ! $i[2]; } ) );
}

add_action(
	'admin_menu',
	function () {
		$n = ps_pend_faltan();
		add_submenu_page( 'prevsoul', 'Antes de publicar', 'Antes de publicar' . ( $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '' ), 'manage_options', 'ps-pendientes', 'ps_pend_page' );
	},
	19
);

add_action(
	'admin_post_ps_pendientes',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_pendientes' );
		$st          = ps_pend_state();
		$st['hecho'] = array();
		foreach ( (array) ( isset( $_POST['hecho'] ) ? wp_unslash( $_POST['hecho'] ) : array() ) as $k => $v ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$st['hecho'][ sanitize_key( $k ) ] = 1;
		}
		$nuevo = isset( $_POST['nuevo'] ) ? sanitize_text_field( wp_unslash( $_POST['nuevo'] ) ) : '';
		if ( $nuevo ) {
			$st['extra'][] = $nuevo;
		}
		if ( isset( $_POST['borrar'] ) ) {
			$b = absint( $_POST['borrar'] );
			unset( $st['extra'][ $b ], $st['hecho'][ 'x' . $b ] );
		}
		update_option( 'ps_pendientes', $st, false );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-pendientes&ok=1' ) );
		exit;
	}
);

function ps_pend_page() {
	$items = ps_pend_items();
	$n     = ps_pend_faltan();
	echo '<div class="wrap"><h1>Antes de publicar</h1><p>Lo que falta para abrir prevsoul.cl al público. ' . ( $n ? '<strong>Quedan ' . (int) $n . ' pendientes.</strong>' : '<strong>¡Todo listo!</strong>' ) . ' Algunos ítems se marcan solos cuando se cumplen.</p>';
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Guardado.</p></div>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_pendientes">';
	wp_nonce_field( 'ps_pendientes' );
	echo '<ul style="font-size:14px;max-width:820px">';
	foreach ( $items as $it ) {
		list( $k, $t, $done, $auto ) = $it;
		echo '<li style="padding:8px 10px;margin:0 0 6px;background:#fff;border:1px solid #dcdcde;border-radius:6px;display:flex;gap:10px;align-items:center"><input type="checkbox" name="hecho[' . esc_attr( $k ) . ']" value="1"' . checked( $done, true, false ) . ( $auto ? ' disabled' : '' ) . '><span style="' . ( $done ? 'text-decoration:line-through;color:#8a94a6' : '' ) . '">' . esc_html( $t ) . '</span>' . ( $auto ? '<em style="margin-left:auto;color:#8a94a6;font-size:12px">automático</em>' : '' );
		if ( 'x' === $k[0] ) {
			echo '<button class="button-link" style="margin-left:auto;color:#b32d2e" name="borrar" value="' . (int) substr( $k, 1 ) . '">Quitar</button>';
		}
		echo '</li>';
	}
	echo '</ul><p><input type="text" name="nuevo" placeholder="Agregar un pendiente…" style="width:420px"></p>';
	submit_button( 'Guardar' );
	echo '</form></div>';
}

/* Aviso en el admin mientras queden pendientes. */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$n    = ps_pend_faltan();
		$demo = '1' === (string) get_option( 'ps_social_demo', '' );
		if ( ! $n ) {
			return;
		}
		$on = function_exists( 'ps_construccion_on' ) && ps_construccion_on();
		echo '<div class="notice ' . ( $on ? 'notice-info' : 'notice-error' ) . '"><p>' . ( $on ? '' : '<strong>El sitio está abierto al público</strong> y ' ) . 'quedan <strong>' . (int) $n . ' pendientes antes de publicar</strong>' . ( $demo ? ', entre ellos apagar los <strong>datos de demostración</strong>' : '' ) . '. <a href="' . esc_url( admin_url( 'admin.php?page=ps-pendientes' ) ) . '">Ver la lista</a></p></div>';
	}
);
