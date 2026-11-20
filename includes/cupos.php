<?php
/**
 * Cupos de productos desde la agenda real (bolsa de horas).
 *
 * Para cada prestador con agenda en línea activa se calcula una bolsa de horas UCO
 * (45 min de atención + 15 de limpieza) para los próximos 14 días:
 *
 *   horas libres  = horas de su agenda − presenciales (Ocupar horas) − ya reservadas en Prevsoul
 *   tope          = 60 % de las horas libres
 *   bolsa         = tope − controles de planes que vencen en el período (1 hora cada uno)
 *                        − horas ya vendidas que aún no se agendan (todas las sesiones de cada pack)
 *
 * Cada producto muestra cuántos cupos le alcanzan: bolsa ÷ «Horas UCO que usa» (sus sesiones).
 * Si no alcanza, el producto queda «Agotado por ahora» y no se puede comprar.
 * Los planes no usan cupos. Todo sale de datos reales.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

const PS_CUPOS_DIAS = 14;

function ps_cupos_pct() {
	return max( 10, min( 100, (int) get_option( 'ps_cupos_pct', 60 ) ) );
}

/** Desde cuántos cupos se muestra el número («Quedan N»); sobre eso, «Cupos disponibles». */
function ps_cupos_umbral() {
	return max( 1, (int) get_option( 'ps_cupos_umbral', 5 ) );
}

/** Horas UCO que usa un producto (sus sesiones). Mínimo 1. */
function ps_cupos_horas( $pid ) {
	return max( 1, (int) get_post_meta( $pid, '_ps_horas_uco', true ) );
}

/** ¿El producto se rige por cupos? (tiene prestador con agenda activa y no es un plan) */
function ps_cupos_aplica( $pid ) {
	if ( ! function_exists( 'ps_product_prestador' ) || ! function_exists( 'ps_ag_activa' ) ) {
		return 0;
	}
	if ( function_exists( 'ps_es_plan_pid' ) && ps_es_plan_pid( $pid ) ) {
		return 0;
	}
	if ( 'no' === get_post_meta( $pid, '_ps_usa_cupos', true ) ) {
		return 0;
	}
	$prov = ps_product_prestador( $pid );
	return ( $prov && ps_ag_activa( $prov ) ) ? $prov : 0;
}

/** Controles de planes de este prestador que vencen en el período y aún no tienen hora ni orden. */
function ps_cupos_controles( $prov, $hasta ) {
	if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'ps_plan_order_has_plan' ) || ! function_exists( 'ps_ctrl_prov' ) ) {
		return 0;
	}
	$n      = 0;
	$orders = wc_get_orders( array( 'status' => array( 'processing', 'completed' ), 'limit' => 300, 'date_created' => '>' . ( time() - 3 * YEAR_IN_SECONDS ) ) );
	foreach ( $orders as $o ) {
		if ( ! ps_plan_order_has_plan( $o ) ) {
			continue;
		}
		list( $p ) = ps_ctrl_prov( $o );
		if ( (int) $p !== (int) $prov ) {
			continue;
		}
		foreach ( ps_plan_miembros( $o ) as $i => $x ) {
			$vence = $x['ultimo'] ? strtotime( $x['ultimo'] ) + 183 * DAY_IN_SECONDS : 0;
			if ( $vence > $hasta ) {
				continue;
			}
			$ya = get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_ctrl', 'value' => $o->get_id() . '-' . $i ), array( 'key' => '_ps_estado', 'value' => array( 'recibida', 'agendada' ), 'compare' => 'IN' ) ) ) );
			if ( ! $ya ) {
				++$n;
			}
		}
	}
	return $n;
}

/** Horas vendidas que todavía consumen la bolsa (no agendadas, o sesiones restantes de un pack). */
function ps_cupos_pendientes( $prov ) {
	$h   = 0;
	$ids = get_posts(
		array(
			'post_type'   => 'ps_orden',
			'numberposts' => 300,
			'fields'      => 'ids',
			'date_query'  => array( array( 'after' => PS_CUPOS_DIAS . ' days ago' ) ),
			'meta_query'  => array(
				array( 'key' => '_ps_prestador', 'value' => $prov ),
				array( 'key' => '_ps_estado', 'value' => array( 'recibida', 'agendada' ), 'compare' => 'IN' ),
			),
		)
	);
	foreach ( $ids as $oid ) {
		$pid   = (int) get_post_meta( $oid, '_ps_product_id', true );
		$ctrl  = (string) get_post_meta( $oid, '_ps_ctrl', true );
		$total = $ctrl ? 1 : ps_cupos_horas( $pid ) * max( 1, (int) get_post_meta( $oid, '_ps_cantidad', true ) );
		$h    += 'agendada' === get_post_meta( $oid, '_ps_estado', true ) ? max( 0, $total - 1 ) : $total;
	}
	return $h;
}

/** Bolsa del prestador: array con el detalle del cálculo. Se guarda 10 minutos. */
function ps_cupos_bolsa( $prov, $fresca = false ) {
	$key = 'ps_bolsa_' . (int) $prov;
	if ( ! $fresca ) {
		$c = get_transient( $key );
		if ( is_array( $c ) ) {
			return $c;
		}
	}
	$tz    = wp_timezone();
	$ahora = time();
	$min   = $ahora + max( 0, (int) ps_ag_cfg( $prov )['ant'] ) * HOUR_IN_SECONDS;
	$hasta = $ahora + PS_CUPOS_DIAS * DAY_IN_SECONDS;
	$blq   = ps_ag_bloqueos( $prov );
	$tot   = 0;
	$pres  = 0;
	$psr   = 0;
	$hoy   = new DateTimeImmutable( 'today', $tz );
	for ( $i = 0; $i <= PS_CUPOS_DIAS; $i++ ) {
		$ymd = $hoy->modify( "+$i days" )->format( 'Y-m-d' );
		$tom = ps_ag_ocupadas( $prov, $ymd );
		foreach ( ps_ag_slots_dia( $prov, $ymd ) as $h ) {
			$ts = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $h, $tz )->getTimestamp();
			if ( $ts < $min || $ts > $hasta ) {
				continue;
			}
			++$tot;
			if ( in_array( $h, $tom, true ) ) {
				++$psr;
			} elseif ( ! empty( $blq[ $ymd . ' ' . $h ] ) ) {
				++$pres;
			}
		}
	}
	$libres = max( 0, $tot - $pres - $psr );
	$tope   = (int) floor( $libres * ps_cupos_pct() / 100 );
	$ctrl   = ps_cupos_controles( $prov, $hasta );
	$pend   = ps_cupos_pendientes( $prov );
	$b      = array(
		'total'      => $tot,
		'presencial' => $pres,
		'prevsoul'   => $psr,
		'libres'     => $libres,
		'pct'        => ps_cupos_pct(),
		'tope'       => $tope,
		'controles'  => $ctrl,
		'vendidas'   => $pend,
		'bolsa'      => max( 0, $tope - $ctrl - $pend ),
	);
	set_transient( $key, $b, 10 * MINUTE_IN_SECONDS );
	return $b;
}

function ps_cupos_limpiar( $prov ) {
	delete_transient( 'ps_bolsa_' . (int) $prov );
}

/** Cupos de un producto, o null si no se rige por cupos. */
function ps_cupos_producto( $pid ) {
	$prov = ps_cupos_aplica( $pid );
	if ( ! $prov ) {
		return null;
	}
	return (int) floor( ps_cupos_bolsa( $prov )['bolsa'] / ps_cupos_horas( $pid ) );
}

/** Etiqueta «Quedan N cupos» / «Agotado por ahora» (vacía si el producto no usa cupos). */
function ps_cupos_badge( $pid ) {
	$c = ps_cupos_producto( $pid );
	if ( null === $c ) {
		return '';
	}
	if ( 0 === $c ) {
		return '<p class="ps-cupos" style="margin:0;font-size:13px;font-weight:700;color:#4A5B73;background:#F0F4FA;border-radius:999px;padding:4px 12px;align-self:flex-start">Agotado por ahora</p>';
	}
	$poco  = $c <= 2;
	$texto = $c > ps_cupos_umbral() ? 'Cupos disponibles' : 'Quedan ' . (int) $c . ' cupo' . ( 1 === $c ? '' : 's' );
	return '<p class="ps-cupos" style="margin:0;font-size:13px;font-weight:700;color:' . ( $poco ? '#7A5300' : '#167A64' ) . ';background:' . ( $poco ? '#FFF4E0' : '#E6F7F2' ) . ';border-radius:999px;padding:4px 12px;align-self:flex-start">' . esc_html( $texto ) . '</p>';
}

/* Sin cupos, no se puede comprar. */
add_filter(
	'woocommerce_is_purchasable',
	function ( $ok, $product ) {
		if ( ! $ok || ! $product ) {
			return $ok;
		}
		$pid = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$c   = ps_cupos_producto( $pid );
		return ( null !== $c && $c < 1 ) ? false : $ok;
	},
	20,
	2
);

/* No dejar comprar más cupos de los que hay (cantidad en el carro). */
add_filter(
	'woocommerce_add_to_cart_validation',
	function ( $ok, $pid, $qty ) {
		$c = ps_cupos_producto( $pid );
		if ( null === $c || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $ok;
		}
		$en = 0;
		foreach ( WC()->cart->get_cart() as $it ) {
			if ( (int) $it['product_id'] === (int) $pid ) {
				$en += (int) $it['quantity'];
			}
		}
		if ( $en + (int) $qty > $c ) {
			wc_add_notice( $c > 0 ? 'Por ahora quedan ' . $c . ' cupo' . ( 1 === $c ? '' : 's' ) . ' para este producto.' : 'Este producto está agotado por ahora. Vuelve pronto: los cupos se liberan cada semana.', 'error' );
			return false;
		}
		return $ok;
	},
	20,
	3
);

/* Ficha del producto: etiqueta de cupos bajo el precio. */
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( $product ) {
			echo ps_cupos_badge( $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	11
);

/* Campo «Horas UCO que usa» en el producto. */
add_action(
	'woocommerce_product_options_general_product_data',
	function () {
		$pid = get_the_ID();
		woocommerce_wp_checkbox(
			array(
				'id'          => '_ps_usa_cupos',
				'label'       => 'Usar cupos de agenda',
				'value'       => 'no' === get_post_meta( $pid, '_ps_usa_cupos', true ) ? 'no' : 'yes',
				'cbvalue'     => 'yes',
				'description' => 'Marcado: se vende según los cupos que libera la agenda del prestador (muestra «Cupos disponibles», «Quedan N» o «Agotado por ahora»). Desmarcado: se vende sin límite.',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_ps_horas_uco',
				'label'             => 'Horas UCO que usa',
				'type'              => 'number',
				'value'             => ps_cupos_horas( get_the_ID() ),
				'custom_attributes' => array( 'min' => 1, 'max' => 12, 'step' => 1 ),
				'desc_tip'          => true,
				'description'       => 'Cuántas horas de agenda (45 min + 15 de limpieza) usa este producto. En un pack, su número de sesiones. Se descuentan de los cupos al venderse.',
			)
		);
	}
);
add_action(
	'woocommerce_process_product_meta',
	function ( $pid ) {
		update_post_meta( $pid, '_ps_usa_cupos', empty( $_POST['_ps_usa_cupos'] ) ? 'no' : 'yes' ); // phpcs:ignore WordPress.Security.NonceVerification
		foreach ( (array) get_post_meta( $pid, '_ps_prestador', false ) as $prov ) {
			ps_cupos_limpiar( (int) $prov );
		}
		if ( isset( $_POST['_ps_horas_uco'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $pid, '_ps_horas_uco', max( 1, min( 12, absint( $_POST['_ps_horas_uco'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
);

/* Recalcular la bolsa cuando cambia algo que la afecta. */
$ps_cupos_meta = function ( $mid, $oid, $key ) {
	if ( in_array( $key, array( '_ps_ag', '_ps_ag_ocup' ), true ) ) {
		ps_cupos_limpiar( $oid );
	} elseif ( in_array( $key, array( '_ps_estado', '_ps_cita', '_ps_prestador' ), true ) && 'ps_orden' === get_post_type( $oid ) ) {
		ps_cupos_limpiar( (int) get_post_meta( $oid, '_ps_prestador', true ) );
	}
};
add_action( 'updated_post_meta', $ps_cupos_meta, 10, 3 );
add_action( 'added_post_meta', $ps_cupos_meta, 10, 3 );

/* Panel del prestador: su bolsa del período. */
function ps_cupos_panel_html( $prov ) {
	if ( ! ps_ag_activa( $prov ) ) {
		return '';
	}
	$b    = ps_cupos_bolsa( $prov, true );
	$row  = function ( $t, $v, $strong = false ) {
		return '<div style="display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid #E3E8EF"><span>' . esc_html( $t ) . '</span><span style="font-weight:' . ( $strong ? '800' : '600' ) . '">' . esc_html( $v ) . '</span></div>';
	};
	$out  = '<section class="ps-card" id="mis-cupos"><h3 class="ps-card-title">Mis cupos Prevsoul · próximos ' . PS_CUPOS_DIAS . ' días</h3>';
	$out .= '<p class="ps-muted ps-small">Se calculan solos desde tu agenda. Cada vez que ocupas o liberas una hora, o se vende o agenda algo, se recalculan.</p><div class="ps-small">';
	$out .= $row( 'Horas de tu agenda', $b['total'] . ' h' );
	$out .= $row( '− Presenciales (Ocupar horas)', $b['presencial'] . ' h' );
	$out .= $row( '− Ya reservadas en Prevsoul', $b['prevsoul'] . ' h' );
	$out .= $row( '= Horas libres', $b['libres'] . ' h', true );
	$out .= $row( 'Para Prevsoul (' . $b['pct'] . ' %)', $b['tope'] . ' h' );
	$out .= $row( '− Controles de planes que vencen', $b['controles'] . ' h' );
	$out .= $row( '− Vendidas por agendar', $b['vendidas'] . ' h' );
	$out .= $row( '= Bolsa disponible para vender', $b['bolsa'] . ' h', true );
	$out .= '</div>';
	$prods = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 50, 'fields' => 'ids', 'meta_key' => '_ps_prestador', 'meta_value' => $prov ) );
	$lis   = '';
	foreach ( $prods as $pid ) {
		$c = ps_cupos_producto( $pid );
		if ( null !== $c ) {
			$lis .= '<li>' . esc_html( get_the_title( $pid ) ) . ' (' . ps_cupos_horas( $pid ) . ' h): <strong>' . ( $c ? (int) $c . ' cupo' . ( 1 === $c ? '' : 's' ) : 'agotado' ) . '</strong></li>';
		}
	}
	if ( $lis ) {
		$out .= '<p class="ps-small" style="margin:8px 0 0"><strong>Cupos de tus productos</strong></p><ul class="ps-small" style="margin:4px 0 0 18px;padding:0">' . $lis . '</ul>';
	}
	return $out . '</section>';
}
