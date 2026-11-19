<?php
/**
 * Agenda Prevsoul para los controles de los planes.
 *
 * Cada integrante de un plan puede «Agendar control» (Mi plan o correo mensual): se crea
 * una orden de atención «Control preventivo» con el prestador del plan y se abre la agenda.
 * Al marcarse «Realizada», se actualiza su último control en Mi plan.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

function ps_ctrl_key( $order_id, $i ) {
	return substr( wp_hash( 'ps_ctrl|' . $order_id . '|' . $i ), 0, 16 );
}

function ps_ctrl_url( $order, $i ) {
	$id = is_object( $order ) ? $order->get_id() : (int) $order;
	return add_query_arg( array( 'ps_ctrl' => $id, 'm' => (int) $i, 'k' => ps_ctrl_key( $id, $i ) ), home_url( '/' ) );
}

/** Prestador del plan (el elegido al comprar o el del producto). */
function ps_ctrl_prov( $order ) {
	foreach ( $order->get_items() as $it ) {
		if ( function_exists( 'ps_es_plan_pid' ) && ps_es_plan_pid( $it->get_product_id() ) ) {
			$p = (int) $it->get_meta( '_ps_prestador' );
			if ( ! ps_is_prestador_post( $p ) ) {
				$p = ps_product_prestador( $it->get_product_id() );
			}
			return array( $p, $it->get_product_id() );
		}
	}
	return array( 0, 0 );
}

/** Pago a la clínica por cada control de plan (CLP). */
function ps_ctrl_pago() {
	return (float) get_option( 'ps_ctrl_pago', 6000 );
}

add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['ps_ctrl'], $_GET['m'], $_GET['k'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$id = absint( $_GET['ps_ctrl'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$i  = absint( $_GET['m'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$o  = $id ? wc_get_order( $id ) : null;
		if ( ! $o || ! hash_equals( ps_ctrl_key( $id, $i ), sanitize_text_field( wp_unslash( $_GET['k'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		$m = ps_plan_miembros( $o );
		if ( ! isset( $m[ $i ] ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'mi-plan' ) );
			exit;
		}
		list( $prov, $pid ) = ps_ctrl_prov( $o );
		if ( ! $prov ) {
			wp_safe_redirect( add_query_arg( 'ctrl', 'sinred', wc_get_account_endpoint_url( 'mi-plan' ) ) );
			exit;
		}
		$clave = $id . '-' . $i;
		$exist = get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_ctrl', 'value' => $clave ), array( 'key' => '_ps_estado', 'value' => array( 'recibida', 'agendada' ), 'compare' => 'IN' ) ) ) );
		if ( $exist ) {
			wp_safe_redirect( ps_ag_url( $exist[0] ) );
			exit;
		}
		$nombre = $m[ $i ]['nombre'] ? $m[ $i ]['nombre'] : trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() );
		$oid    = wp_insert_post( array( 'post_type' => 'ps_orden', 'post_status' => 'publish', 'post_title' => sprintf( 'Plan #%s · Control preventivo · %s', $o->get_order_number(), $nombre ) ) );
		if ( ! $oid || is_wp_error( $oid ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'mi-plan' ) );
			exit;
		}
		$meta = array(
			'pedido' => $o->get_id(), 'numero' => $o->get_order_number(), 'item' => 0, 'producto' => 'Control preventivo (plan)', 'product_id' => $pid,
			'cantidad' => 1, 'prestador' => $prov, 'paciente' => $nombre, 'email' => $o->get_billing_email(), 'telefono' => $o->get_billing_phone(),
			'monto' => 0, 'comision' => 0, 'pago' => ps_ctrl_pago(), 'lista' => 0, 'regla' => 'plan', 'estado' => 'recibida', 'cita' => '', 'pagada' => '',
			'ctrl' => $clave, 'log' => array( array( 't' => time(), 'e' => 'recibida', 'u' => 'Paciente (control de plan)' ) ),
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $oid, '_ps_' . $k, $v );
		}
		wp_safe_redirect( ps_ag_url( $oid ) );
		exit;
	},
	5
);

/* Control realizado: actualizar «último control» del integrante. */
add_action(
	'updated_post_meta',
	function ( $mid, $oid, $key, $val ) {
		if ( '_ps_estado' !== $key || 'realizada' !== $val || 'ps_orden' !== get_post_type( $oid ) ) {
			return;
		}
		$clave = (string) get_post_meta( $oid, '_ps_ctrl', true );
		if ( ! $clave || false === strpos( $clave, '-' ) ) {
			return;
		}
		list( $id, $i ) = array_map( 'intval', explode( '-', $clave, 2 ) );
		$o = wc_get_order( $id );
		if ( ! $o ) {
			return;
		}
		$m = ps_plan_miembros( $o );
		if ( isset( $m[ $i ] ) ) {
			$m[ $i ]['ultimo'] = wp_date( 'Y-m-d' );
			$o->update_meta_data( '_ps_miembros', $m );
			$o->save();
		}
	},
	20,
	4
);
