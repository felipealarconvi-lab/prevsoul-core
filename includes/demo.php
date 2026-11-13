<?php
/**
 * Datos de prueba: odontóloga disponible, clínica proveedora, productos asignados,
 * cuenta de clínica y cupón PRUEBA100. Todo queda marcado para borrarlo con un clic.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ps_demo_email( $tag ) {
	$base = sanitize_email( (string) ps_get( 'notify_email' ) );
	if ( ! $base ) {
		$base = get_option( 'admin_email' );
	}
	$parts = explode( '@', $base );
	return 2 === count( $parts ) ? $parts[0] . '+' . $tag . '@' . $parts[1] : $base;
}

function ps_demo_exists() {
	return (bool) get_option( 'ps_demo' );
}

function ps_demo_create() {
	if ( ps_demo_exists() ) {
		return;
	}
	$demo = array( 'posts' => array(), 'products' => array(), 'user' => 0, 'coupon' => 0 );

	$dent = wp_insert_post(
		array(
			'post_type'    => 'ps_odontologo',
			'post_status'  => 'publish',
			'post_title'   => 'Dra. Camila Prueba',
			'post_content' => 'Odontóloga de prueba de Prevsoul. Atiende la sala de espera, transmite en Prevsoul Live y es la odontóloga de cabecera de los planes. Cambia este texto o borra los datos de prueba cuando cargues a tu equipo real.',
		)
	);
	$clin = wp_insert_post(
		array(
			'post_type'    => 'ps_clinica',
			'post_status'  => 'publish',
			'post_title'   => 'Clínica Demo Prevsoul',
			'post_content' => 'Clínica proveedora de prueba. Recibe las órdenes de los tratamientos vendidos.',
		)
	);
	foreach ( array( $dent, $clin ) as $id ) {
		update_post_meta( $id, '_ps_demo', 1 );
		$demo['posts'][] = $id;
	}
	$meta = array(
		$dent => array(
			'titulo'   => 'Cirujana dentista · Odontología preventiva',
			'rango'    => '2',
			'activo'   => '1',
			'telefono' => '+56 9 1111 1111',
			'email'    => ps_demo_email( 'dentista' ),
			'agenda'   => 'Esto es una cuenta de prueba. Aquí irían las indicaciones de la odontóloga para coordinar tu primer control del plan.',
		),
		$clin => array(
			'direccion' => 'Av. Ejemplo 123, Viña del Mar',
			'telefono'  => '+56 9 2222 2222',
			'horario'   => 'Lunes a viernes 9:00 a 19:00',
			'email'     => ps_demo_email( 'clinica' ),
			'agenda'    => 'Esto es una clínica de prueba. Aquí irían sus indicaciones, por ejemplo: «Llámanos de lunes a viernes, indica tu número de pedido Prevsoul y te damos hora dentro de 48 horas».',
		),
	);
	foreach ( $meta as $id => $fields ) {
		foreach ( $fields as $k => $v ) {
			update_post_meta( $id, '_ps_' . $k, $v );
		}
	}

	// Productos sin prestador: los planes a la odontóloga, el resto a la clínica.
	if ( function_exists( 'wc_get_products' ) ) {
		foreach ( wc_get_products( array( 'limit' => 200, 'status' => array( 'publish', 'draft', 'private' ), 'return' => 'ids' ) ) as $pid ) {
			if ( ps_product_prestador( $pid ) ) {
				continue;
			}
			$p     = wc_get_product( $pid );
			$plan  = $p && ( has_term( 'planes-dentales', 'product_cat', $pid ) || in_array( 'planes-dentales', ps_parent_slugs( $p ), true ) );
			update_post_meta( $pid, '_ps_prestador', $plan ? $dent : $clin );
			$demo['products'][] = $pid;
		}
	}

	// Cuenta de la clínica de prueba: WordPress le envía el enlace para crear su contraseña.
	$login = 'clinica-demo';
	if ( ! username_exists( $login ) && ! email_exists( ps_demo_email( 'clinica' ) ) ) {
		$uid = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => ps_demo_email( 'clinica' ),
				'user_pass'    => wp_generate_password( 24 ),
				'display_name' => 'Clínica Demo Prevsoul',
				'role'         => 'ps_prestador',
			)
		);
		if ( ! is_wp_error( $uid ) ) {
			update_user_meta( $uid, 'ps_prestador_id', $clin );
			wp_new_user_notification( $uid, null, 'user' );
			$demo['user'] = $uid;
		}
	}

	// Cupón para comprar sin pagar.
	if ( class_exists( 'WC_Coupon' ) && ! wc_get_coupon_id_by_code( 'PRUEBA100' ) ) {
		$c = new WC_Coupon();
		$c->set_code( 'PRUEBA100' );
		$c->set_discount_type( 'percent' );
		$c->set_amount( 100 );
		$c->set_usage_limit( 20 );
		$c->set_description( 'Cupón de prueba de Prevsoul: compra cualquier producto a $0. Se borra con los datos de prueba.' );
		$demo['coupon'] = $c->save();
	}
	update_option( 'ps_demo', $demo, false );
}

function ps_demo_delete() {
	$demo = get_option( 'ps_demo' );
	if ( ! $demo ) {
		return;
	}
	foreach ( (array) $demo['products'] as $pid ) {
		if ( in_array( (int) get_post_meta( $pid, '_ps_prestador', true ), array_map( 'intval', (array) $demo['posts'] ), true ) ) {
			delete_post_meta( $pid, '_ps_prestador' );
		}
	}
	foreach ( (array) $demo['posts'] as $id ) {
		foreach ( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ps_prestador', 'meta_value' => $id ) ) as $oid ) {
			wp_delete_post( $oid, true );
		}
		wp_delete_post( $id, true );
	}
	if ( ! empty( $demo['user'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( (int) $demo['user'] );
	}
	if ( ! empty( $demo['coupon'] ) ) {
		wp_delete_post( (int) $demo['coupon'], true );
	}
	delete_option( 'ps_demo' );
}

add_action(
	'admin_post_ps_demo',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_demo' ) ) {
			wp_die( 'No permitido.' );
		}
		$do = isset( $_GET['do'] ) ? sanitize_key( $_GET['do'] ) : '';
		if ( 'crear' === $do ) {
			ps_demo_create();
		} elseif ( 'borrar' === $do ) {
			ps_demo_delete();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=prevsoul&ps_demo_done=' . $do ) );
		exit;
	}
);

function ps_demo_card() {
	$url  = function ( $do ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=ps_demo&do=' . $do ), 'ps_demo' );
	};
	$demo = get_option( 'ps_demo' );
	echo '<div class="card" style="margin:0;min-width:260px;flex:1"><h2 style="margin-top:0">Datos de prueba</h2>';
	if ( $demo ) {
		echo '<p><strong>Activos:</strong> Dra. Camila Prueba (disponible en la sala), Clínica Demo Prevsoul, ' . count( (array) $demo['products'] ) . ' productos asignados, cuenta «clinica-demo» y cupón <code>PRUEBA100</code> (100% de descuento).</p>';
		echo '<p><a class="button" href="' . esc_url( add_query_arg( 'prestador', (int) $demo['posts'][1], ps_panel_url() ) ) . '" target="_blank">Ver panel de la clínica</a> <a class="button" href="' . esc_url( $url( 'borrar' ) ) . '" onclick="return confirm(\'¿Borrar todos los datos de prueba? Los productos quedan sin prestador.\');">Borrar datos de prueba</a></p>';
	} else {
		echo '<p>Crea una odontóloga disponible, una clínica proveedora, asigna los productos sin responsable, una cuenta de clínica y el cupón <code>PRUEBA100</code> para probar compras a $0.</p><p><a class="button button-primary" href="' . esc_url( $url( 'crear' ) ) . '">Crear datos de prueba</a></p>';
	}
	echo '</div>';
}
