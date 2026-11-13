<?php
/**
 * Tablero: ventas, ingreso Prevsoul, consultas online, órdenes a clínicas y actividad por perfil.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Tablero Prevsoul', 'Tablero', 'manage_options', 'ps-tablero', 'ps_tablero_page', 0 );
	},
	20
);

function ps_clp_short( $v ) {
	return '$' . number_format( (float) $v, 0, ',', '.' );
}

function ps_orders_between( $from, $to ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	return wc_get_orders(
		array(
			'status'       => array( 'wc-processing', 'wc-completed' ),
			'limit'        => -1,
			'date_created' => $from . '...' . $to,
		)
	);
}

function ps_tablero_page() {
	$d     = ps_periodo_actual();
	$since = ps_periodo_inicio( $d );
	$now   = time();
	$span  = $now - $since;

	$orders = ps_orders_between( $since, $now );
	$prevo  = ps_orders_between( $since - $span, $since - 1 );
	$sum    = function ( $os ) {
		return array_sum( array_map( function ( $o ) { return (float) $o->get_total(); }, $os ) );
	};
	$ventas = $sum( $orders );
	$vprev  = $sum( $prevo );

	$ord_ids = array();
	foreach ( $orders as $o ) {
		$ord_ids[] = $o->get_id();
	}
	$pago_clin = 0;
	foreach ( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'fields' => 'ids' ) ) as $oid ) {
		if ( in_array( (int) get_post_meta( $oid, '_ps_pedido', true ), $ord_ids, true ) && 'cancelada' !== get_post_meta( $oid, '_ps_estado', true ) ) {
			$pago_clin += (float) get_post_meta( $oid, '_ps_pago', true );
		}
	}
	$ingreso = $ventas - $pago_clin;

	$prec = get_posts( array( 'post_type' => 'ps_precita', 'post_status' => 'any', 'numberposts' => -1, 'date_query' => array( array( 'after' => gmdate( 'Y-m-d H:i:s', $since ), 'inclusive' => true ) ) ) );
	$emails = array();
	foreach ( $orders as $o ) {
		$emails[ strtolower( $o->get_billing_email() ) ] = 1;
	}
	$prec_buy = 0;
	$atend    = 0;
	foreach ( $prec as $p ) {
		if ( isset( $emails[ strtolower( (string) get_post_meta( $p->ID, '_ps_email', true ) ) ] ) ) {
			$prec_buy++;
		}
		if ( 'nueva' !== get_post_meta( $p->ID, '_ps_estado', true ) ) {
			$atend++;
		}
	}

	$all_ord   = get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1 ) );
	$pend      = 0;
	$pend_old  = 0;
	$clin      = array();
	$realiz    = 0;
	$agendas   = array();
	foreach ( $all_ord as $o ) {
		$e    = get_post_meta( $o->ID, '_ps_estado', true );
		$prov = (int) get_post_meta( $o->ID, '_ps_prestador', true );
		if ( ! isset( $clin[ $prov ] ) ) {
			$clin[ $prov ] = array( 'pend' => 0, 'real' => 0, 'pagar' => 0 );
		}
		if ( 'recibida' === $e ) {
			$pend++;
			if ( strtotime( $o->post_date_gmt . ' UTC' ) < $now - 2 * DAY_IN_SECONDS ) {
				$pend_old++;
			}
		}
		if ( in_array( $e, array( 'recibida', 'agendada' ), true ) ) {
			$clin[ $prov ]['pend']++;
		}
		if ( 'realizada' === $e && ! get_post_meta( $o->ID, '_ps_pagada', true ) ) {
			$clin[ $prov ]['pagar'] += (float) get_post_meta( $o->ID, '_ps_pago', true );
		}
		$t_rec = 0;
		foreach ( (array) get_post_meta( $o->ID, '_ps_log', true ) as $l ) {
			if ( ! is_array( $l ) ) {
				continue;
			}
			if ( 'recibida' === ( $l['e'] ?? '' ) ) {
				$t_rec = (int) $l['t'];
			}
			if ( 'agendada' === ( $l['e'] ?? '' ) && $t_rec && $l['t'] >= $since ) {
				$agendas[] = ( $l['t'] - $t_rec ) / DAY_IN_SECONDS;
			}
			if ( 'realizada' === ( $l['e'] ?? '' ) && $l['t'] >= $since ) {
				$realiz++;
				$clin[ $prov ]['real']++;
			}
		}
	}

	$busq = count( ps_bus_entries( $since ) );
	$live = count( get_posts( array( 'post_type' => 'ps_transmision', 'numberposts' => -1, 'fields' => 'ids', 'date_query' => array( array( 'after' => gmdate( 'Y-m-d H:i:s', $since ) ) ) ) ) );
	$preg = count( get_posts( array( 'post_type' => 'ps_pregunta', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'date_query' => array( array( 'after' => gmdate( 'Y-m-d H:i:s', $since ) ) ) ) ) );
	$users = count( get_users( array( 'role__in' => array( 'customer', 'subscriber' ), 'fields' => 'ID', 'date_query' => array( array( 'after' => gmdate( 'Y-m-d H:i:s', $since ) ) ) ) ) );
	$pac   = count( array_unique( array_merge( array_keys( $emails ), array_map( function ( $p ) { return strtolower( (string) get_post_meta( $p->ID, '_ps_email', true ) ); }, $prec ) ) ) );

	// Barras.
	if ( 1 === $d ) {
		$step = 4 * HOUR_IN_SECONDS;
		$nb   = 6;
	} elseif ( 90 === $d ) {
		$step = WEEK_IN_SECONDS;
		$nb   = 13;
	} else {
		$step = DAY_IN_SECONDS;
		$nb   = $d;
	}
	$start = $now - $step * $nb;
	$bars  = array_fill( 0, $nb, 0 );
	foreach ( $orders as $o ) {
		$t = $o->get_date_created() ? $o->get_date_created()->getTimestamp() : 0;
		$i = (int) floor( ( $t - $start ) / $step );
		if ( $i >= 0 && $i < $nb ) {
			$bars[ $i ] += (float) $o->get_total();
		}
	}
	$max = max( 1, max( $bars ) );
	$w   = 560;
	$h   = 150;
	$bw  = min( 24, ( $w - 50 ) / $nb - 3 );
	$svg = '<svg viewBox="0 0 ' . $w . ' ' . ( $h + 22 ) . '" width="100%" role="img" aria-label="Ventas del periodo">';
	foreach ( array( 0, 0.5, 1 ) as $g ) {
		$y    = 6 + ( 1 - $g ) * ( $h - 6 );
		$svg .= '<line x1="50" x2="' . $w . '" y1="' . $y . '" y2="' . $y . '" stroke="#e1e0d9"/><text x="44" y="' . ( $y + 4 ) . '" text-anchor="end" font-size="10" fill="#898781">' . esc_html( ps_clp_short( $max * $g ) ) . '</text>';
	}
	foreach ( $bars as $i => $v ) {
		$x    = 50 + ( $w - 50 ) * ( $i + 0.5 ) / $nb - $bw / 2;
		$bh   = $v > 0 ? max( 2, ( $h - 6 ) * $v / $max ) : 0;
		$lbl  = wp_date( 90 === $d ? 'j M' : ( 1 === $d ? 'H:i' : 'j' ), $start + $step * $i );
		$svg .= '<rect x="' . round( $x, 1 ) . '" y="' . round( $h - $bh, 1 ) . '" width="' . round( $bw, 1 ) . '" height="' . round( $bh, 1 ) . '" rx="3" fill="#1D5FD1"><title>' . esc_html( $lbl . ': ' . ps_clp_short( $v ) ) . '</title></rect>';
		if ( $nb <= 13 || 0 === $i % 5 ) {
			$svg .= '<text x="' . round( $x + $bw / 2, 1 ) . '" y="' . ( $h + 16 ) . '" text-anchor="middle" font-size="10" fill="#898781">' . esc_html( $lbl ) . '</text>';
		}
	}
	$svg .= '</svg>';

	$delta = $vprev > 0 ? round( 100 * ( $ventas - $vprev ) / $vprev ) : null;
	$fmax  = max( 1, $busq, count( $prec ), count( $orders ), $realiz );
	$fn    = function ( $label, $v ) use ( $fmax ) {
		return '<div><span>' . esc_html( $label ) . '</span><i style="width:' . round( 100 * $v / $fmax ) . '%"></i><b class="num">' . (int) $v . '</b></div>';
	};

	echo ps_admin_css(); // phpcs:ignore
	echo '<div class="wrap ps-ad"><div class="ps-ad-top"><h1>Tablero</h1>' . ps_periodo_pills( 'ps-tablero' ) . '</div>'; // phpcs:ignore
	echo '<div class="ps-ad-k">';
	echo '<div><span>Ventas</span><b>' . esc_html( ps_clp_short( $ventas ) ) . '</b>' . ( null === $delta ? '<em>' . count( $orders ) . ' pedidos</em>' : '<em class="' . ( $delta < 0 ? 'bad' : '' ) . '">' . ( $delta >= 0 ? '▲ ' : '▼ ' ) . abs( $delta ) . '% vs periodo anterior</em>' ) . '</div>';
	echo '<div><span>Ingreso Prevsoul</span><b>' . esc_html( ps_clp_short( $ingreso ) ) . '</b><em>ventas menos pago a clínicas</em></div>';
	echo '<div><span>Consultas online</span><b>' . count( $prec ) . '</b><em>' . (int) $prec_buy . ' terminaron en compra</em></div>';
	echo '<div><span>Órdenes por agendar</span><b style="color:' . ( $pend ? '#8A5300' : '#0B2347' ) . '">' . (int) $pend . '</b><em class="' . ( $pend_old ? 'warn' : '' ) . '">' . ( $pend_old ? (int) $pend_old . ' con más de 48 h' : 'al día' ) . '</em></div>';
	echo '</div><div class="ps-ad-g"><div class="ps-ad-c"><h2>Ventas del periodo</h2>' . $svg . '</div>'; // phpcs:ignore
	echo '<div class="ps-ad-c ps-ad-hb"><h2>Del interés a la compra</h2>' . $fn( 'Búsquedas', $busq ) . $fn( 'Consultas online', count( $prec ) ) . $fn( 'Compras', count( $orders ) ) . $fn( 'Tratamientos realizados', $realiz ) . '</div></div>'; // phpcs:ignore
	echo '<div class="ps-ad-g"><div class="ps-ad-c"><h2>Clínicas: órdenes y liquidación</h2>';
	$clin = array_filter( $clin, function ( $c, $k ) { return $k && ( $c['pend'] || $c['real'] || $c['pagar'] ); }, ARRAY_FILTER_USE_BOTH );
	if ( $clin ) {
		echo '<table><tr><th>Clínica o profesional</th><th class="num">Pendientes</th><th class="num">Realizadas</th><th class="num">Por pagar</th></tr>';
		foreach ( $clin as $prov => $c ) {
			echo '<tr><td>' . esc_html( get_the_title( $prov ) ) . '</td><td class="num">' . (int) $c['pend'] . '</td><td class="num">' . (int) $c['real'] . '</td><td class="num">' . esc_html( ps_clp_short( $c['pagar'] ) ) . '</td></tr>';
		}
		echo '</table><p class="ps-ad-note"><a href="' . esc_url( admin_url( 'edit.php?post_type=ps_orden&page=ps-liquidacion' ) ) . '">Ir a Liquidación →</a></p>';
	} else {
		echo '<p class="ps-ad-empty">Aún no hay órdenes a clínicas.</p>';
	}
	echo '</div><div class="ps-ad-c"><h2>Actividad por perfil</h2><table>';
	$rows = array(
		'Pacientes: personas distintas que compraron o consultaron' => $pac,
		'Pacientes: cuentas nuevas'                                   => $users,
		'Dentistas: consultas online atendidas'                       => $atend,
		'Dentistas: transmisiones Live'                               => $live,
		'Pacientes: preguntas en Live'                                => $preg,
		'Clínicas: tiempo medio en agendar'                           => $agendas ? number_format( array_sum( $agendas ) / count( $agendas ), 1, ',', '' ) . ' días' : '—',
	);
	foreach ( $rows as $k => $v ) {
		echo '<tr><td>' . esc_html( $k ) . '</td><td class="num"><b>' . esc_html( (string) $v ) . '</b></td></tr>';
	}
	echo '</table></div></div><p class="ps-ad-note">Las ventas cuentan pedidos pagados (en proceso o completados). Los datos de prueba también se suman mientras existan.</p></div>';
}
