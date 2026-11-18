<?php
/**
 * Finanzas: modelo financiero de 24 meses (3 escenarios × red de clínicas / tú atiendes),
 * supuestos editables, detalle mensual, real vs. presupuesto y descarga en Excel.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

/** Definición de supuestos: clave => [sección, etiqueta, [cons, base, opt], formato, nota, fila en el Excel]. */
function ps_fin_def() {
	return array(
		'precio_ind'     => array( 'Ingresos: planes', 'Precio Plan Individual (mensual, con IVA)', array( 10000, 10000, 10000 ), 'm', 'Se toma del catálogo si está vacío.', 6 ),
		'precio_tit'     => array( 'Ingresos: planes', 'Precio titular Plan Familiar (mensual)', array( 10000, 10000, 10000 ), 'm', 'Regla: titular + 75 % por integrante extra.', 7 ),
		'pct_adic'       => array( 'Ingresos: planes', '% del precio por integrante adicional', array( 0.75, 0.75, 0.75 ), 'p', '', 8 ),
		'integ_fam'      => array( 'Ingresos: planes', 'Integrantes promedio Plan Familiar', array( 2.3, 2.5, 2.8 ), 'n', 'Supuesto (mínimo 2).', 9 ),
		'pct_fam'        => array( 'Ingresos: planes', '% de planes nuevos que son Familiar', array( 0.3, 0.4, 0.5 ), 'p', 'Supuesto.', 10 ),
		'meta'           => array( 'Adquisición y permanencia', 'Presupuesto mensual Meta Ads', array( 150000, 300000, 500000 ), 'm', 'Lo define el usuario.', 12 ),
		'cac'            => array( 'Adquisición y permanencia', 'Costo por plan nuevo (CAC)', array( 40000, 25000, 15000 ), 'm', 'Supuesto: recalibrar con Meta.', 13 ),
		'churn'          => array( 'Adquisición y permanencia', 'Abandono mensual de planes', array( 0.08, 0.05, 0.03 ), 'p', 'Supuesto.', 14 ),
		'mes_ini'        => array( 'Adquisición y permanencia', 'Mes de inicio de publicidad', array( 1, 1, 1 ), 'i', '', 15 ),
		'valor_lista'    => array( 'Costo de prestaciones de planes', 'Valor lista prestaciones por integrante/mes', array( 10000, 10000, 10000 ), 'm', 'Ajustar a la lista cerrada de prestaciones.', 17 ),
		'pago_clin_plan' => array( 'Costo de prestaciones de planes', 'Pago a la clínica (% del valor lista)', array( 0.65, 0.60, 0.55 ), 'p', 'Comisión aún no definida.', 18 ),
		'uso'            => array( 'Costo de prestaciones de planes', 'Tasa de uso de las prestaciones', array( 0.80, 0.70, 0.60 ), 'p', 'Supuesto.', 19 ),
		'packs_m1'       => array( 'Ingresos: packs y atenciones', 'Ventas de packs/atenciones en el mes 1', array( 5, 10, 20 ), 'i', 'Supuesto.', 21 ),
		'packs_g'        => array( 'Ingresos: packs y atenciones', 'Crecimiento mensual de esas ventas', array( 0.05, 0.10, 0.15 ), 'p', 'Supuesto.', 22 ),
		'packs_tope'     => array( 'Ingresos: packs y atenciones', 'Tope de ventas mensuales', array( 40, 80, 150 ), 'i', 'Capacidad de la red.', 23 ),
		'ticket'         => array( 'Ingresos: packs y atenciones', 'Ticket promedio pack/atención (con IVA)', array( 45000, 45000, 45000 ), 'm', 'Supuesto.', 24 ),
		'pago_clin_pack' => array( 'Ingresos: packs y atenciones', 'Pago a la clínica (% del precio neto)', array( 0.75, 0.70, 0.65 ), 'p', 'Comisión aún no definida.', 25 ),
		'comision'       => array( 'Costos por venta e impuestos', 'Comisión pasarela de pago (con IVA)', array( 0.038, 0.038, 0.038 ), 'p', 'Flow: 3,19 % + IVA.', 27 ),
		'iva'            => array( 'Costos por venta e impuestos', 'IVA sobre las ventas', array( 0.19, 0.19, 0.19 ), 'p', 'Validar con contador.', 28 ),
		'hosting'        => array( 'Costos fijos mensuales', 'Hosting (Neolo)', array( 4542, 4542, 4542 ), 'm', '', 30 ),
		'dominio'        => array( 'Costos fijos mensuales', 'Dominio (prorrateado)', array( 1500, 1500, 1500 ), 'm', '', 31 ),
		'herram'         => array( 'Costos fijos mensuales', 'Herramientas (Canva, Google, correo, otras)', array( 25000, 25000, 25000 ), 'm', '', 32 ),
		'conta'          => array( 'Costos fijos mensuales', 'Contabilidad y boletas electrónicas', array( 60000, 60000, 60000 ), 'm', '', 33 ),
		'honor'          => array( 'Costos fijos mensuales', 'Honorarios odontólogos Consulta online / Live', array( 100000, 100000, 100000 ), 'm', '', 34 ),
		'sueldo'         => array( 'Costos fijos mensuales', 'Sueldo o retiro de la dirección', array( 0, 0, 0 ), 'm', '', 35 ),
		'pct_tu'         => array( 'Versión «tú atiendes»', '% de las atenciones que haces tú', array( 1, 1, 1 ), 'p', '', 37 ),
		'cap_tu'         => array( 'Versión «tú atiendes»', 'Capacidad máxima de atenciones tuyas al mes', array( 80, 80, 80 ), 'i', '', 38 ),
		'costo_tu'       => array( 'Versión «tú atiendes»', 'Costo directo de tus atenciones (% del valor)', array( 0.30, 0.25, 0.20 ), 'p', 'Insumos, box, asistente, esterilización.', 39 ),
		'aten_ano'       => array( 'Versión «tú atiendes»', 'Atenciones por integrante al año (plan)', array( 2, 2, 2 ), 'n', '', 40 ),
		'dur'            => array( 'Versión «tú atiendes»', 'Duración promedio por atención (horas)', array( 0.75, 0.75, 0.75 ), 'n', '', 41 ),
		'caja'           => array( 'Caja', 'Caja inicial (capital disponible)', array( 3000000, 3000000, 3000000 ), 'm', 'Lo define el usuario.', 43 ),
	);
}

function ps_fin_sup() {
	$saved = get_option( 'ps_fin_sup', array() );
	$out   = array();
	foreach ( ps_fin_def() as $k => $d ) {
		$out[ $k ] = ( isset( $saved[ $k ] ) && is_array( $saved[ $k ] ) && 3 === count( $saved[ $k ] ) ) ? array_map( 'floatval', $saved[ $k ] ) : $d[2];
	}
	// Precios desde el catálogo mientras no se hayan editado a mano.
	if ( function_exists( 'wc_get_product' ) ) {
		$pi = wc_get_product( 44 );
		$pf = wc_get_product( 45 );
		if ( empty( $saved['precio_ind'] ) && $pi && (float) $pi->get_price() > 0 ) {
			$out['precio_ind'] = array_fill( 0, 3, (float) $pi->get_price() );
		}
		if ( empty( $saved['precio_tit'] ) && $pf && (float) $pf->get_price() > 0 ) {
			$out['precio_tit'] = array_fill( 0, 3, round( (float) $pf->get_price() / ( 1 + $out['pct_adic'][1] ) ) );
		}
	}
	return $out;
}

/** Corre el modelo para un escenario (0 cons, 1 base, 2 opt). */
function ps_fin_run( $sup, $e, $own ) {
	$g   = function ( $k ) use ( $sup, $e ) { return (float) $sup[ $k ][ $e ]; };
	$fij = $g( 'hosting' ) + $g( 'dominio' ) + $g( 'herram' ) + $g( 'conta' ) + $g( 'honor' ) + $g( 'sueldo' );
	$ai  = 0;
	$af  = 0;
	$acc = 0;
	$out = array();
	for ( $m = 1; $m <= 24; $m++ ) {
		$new  = $m >= $g( 'mes_ini' ) && $g( 'cac' ) > 0 ? $g( 'meta' ) / $g( 'cac' ) : 0;
		$nf   = $new * $g( 'pct_fam' );
		$ai   = $ai * ( 1 - $g( 'churn' ) ) + ( $new - $nf );
		$af   = $af * ( 1 - $g( 'churn' ) ) + $nf;
		$int  = $ai + $af * $g( 'integ_fam' );
		$mrr  = $ai * $g( 'precio_ind' ) + $af * $g( 'precio_tit' ) * ( 1 + $g( 'pct_adic' ) * ( $g( 'integ_fam' ) - 1 ) );
		$npk  = min( $g( 'packs_tope' ), $g( 'packs_m1' ) * pow( 1 + $g( 'packs_g' ), $m - 1 ) );
		$ipk  = $npk * $g( 'ticket' );
		$bru  = $mrr + $ipk;
		$neto = $bru / ( 1 + $g( 'iva' ) );
		$sh   = 0;
		$hrs  = 0;
		$dem  = 0;
		if ( $own ) {
			$dem = $int * $g( 'uso' ) * $g( 'aten_ano' ) / 12 + $npk;
			$tu  = min( $g( 'cap_tu' ), $dem * $g( 'pct_tu' ) );
			$sh  = $dem > 0 ? $tu / $dem : $g( 'pct_tu' );
			$hrs = $tu * $g( 'dur' );
		}
		$cpl  = $int * $g( 'valor_lista' ) * $g( 'uso' ) * ( ( 1 - $sh ) * $g( 'pago_clin_plan' ) + $sh * $g( 'costo_tu' ) );
		$cpk  = $ipk / ( 1 + $g( 'iva' ) ) * ( ( 1 - $sh ) * $g( 'pago_clin_pack' ) + $sh * $g( 'costo_tu' ) );
		$com  = $bru * $g( 'comision' );
		$mc   = $neto - $cpl - $cpk - $com;
		$pub  = $m >= $g( 'mes_ini' ) ? $g( 'meta' ) : 0;
		$res  = $mc - $pub - $fij;
		$acc += $res;
		$out[ $m ] = array(
			'planes' => $ai + $af, 'ind' => $ai, 'fam' => $af, 'integ' => $int, 'mrr' => $mrr, 'bruto' => $bru, 'neto' => $neto,
			'clin'   => $cpl + $cpk, 'com' => $com, 'mc' => $mc, 'pub' => $pub, 'fijos' => $fij, 'res' => $res, 'acum' => $acc,
			'caja'   => $g( 'caja' ) + $acc, 'horas' => $hrs, 'derivadas' => $own ? $dem - $dem * $sh : 0,
		);
	}
	return $out;
}

function ps_fin_kpis( $sup, $e, $own ) {
	$r   = ps_fin_run( $sup, $e, $own );
	$g   = function ( $k ) use ( $sup, $e ) { return (float) $sup[ $k ][ $e ]; };
	$sh  = $own ? $g( 'pct_tu' ) : 0;
	$mg  = $g( 'precio_ind' ) / ( 1 + $g( 'iva' ) ) - $g( 'valor_lista' ) * $g( 'uso' ) * ( ( 1 - $sh ) * $g( 'pago_clin_plan' ) + $sh * $g( 'costo_tu' ) ) - $g( 'precio_ind' ) * $g( 'comision' );
	$per = $g( 'churn' ) > 0 ? 1 / $g( 'churn' ) : 0;
	$min = min( array_column( $r, 'caja' ) );
	$pm  = null;
	foreach ( $r as $m => $x ) {
		if ( $x['res'] >= 0 ) {
			$pm = $m;
			break;
		}
	}
	$fp = $r[1]['fijos'] + $g( 'meta' );
	return array(
		'planes12' => $r[12]['planes'], 'planes24' => $r[24]['planes'], 'mrr24' => $r[24]['mrr'], 'acum24' => $r[24]['acum'],
		'cajamin'  => $min, 'mes_eq' => $pm, 'ltvcac' => $g( 'cac' ) > 0 ? $mg * $per / $g( 'cac' ) : 0,
		'eq'       => $mg > 0 ? $fp / $mg : 0, 'horas24' => $r[24]['horas'], 'serie' => array_column( $r, 'acum' ),
	);
}

/* ------------------------------------------------------------------
 * Guardado y descarga
 * ---------------------------------------------------------------- */
add_action(
	'admin_post_ps_fin_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_fin' );
		if ( isset( $_POST['reset'] ) ) {
			delete_option( 'ps_fin_sup' );
		} else {
			$in  = isset( $_POST['s'] ) && is_array( $_POST['s'] ) ? wp_unslash( $_POST['s'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$out = array();
			foreach ( ps_fin_def() as $k => $d ) {
				$row = array();
				for ( $e = 0; $e < 3; $e++ ) {
					$v     = isset( $in[ $k ][ $e ] ) ? str_replace( array( '.', ',', '%', '$', ' ' ), array( '', '.', '', '', '' ), (string) $in[ $k ][ $e ] ) : '';
					$v     = is_numeric( $v ) ? (float) $v : $d[2][ $e ];
					$row[] = 'p' === $d[3] ? $v / 100 : $v;
				}
				$out[ $k ] = $row;
			}
			update_option( 'ps_fin_sup', $out, false );
		}
		if ( isset( $_POST['ps_fin_inicio'] ) ) {
			update_option( 'ps_fin_inicio', preg_match( '/^\d{4}-\d{2}$/', sanitize_text_field( wp_unslash( $_POST['ps_fin_inicio'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['ps_fin_inicio'] ) ) : '', false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ps-finanzas&tab=sup&ok=1' ) );
		exit;
	}
);

add_action(
	'admin_post_ps_fin_xlsx',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permiso' );
		}
		check_admin_referer( 'ps_fin_xlsx' );
		$src = plugin_dir_path( PS_FILE ) . 'assets/finanzas-modelo.xlsx';
		if ( ! class_exists( 'ZipArchive' ) || ! file_exists( $src ) ) {
			wp_die( 'No se pudo generar el Excel en este servidor.' );
		}
		$tmp = wp_tempnam( 'prevsoul-fin' );
		copy( $src, $tmp );
		$z   = new ZipArchive();
		$z->open( $tmp );
		$xml = $z->getFromName( 'xl/worksheets/sheet2.xml' );
		$sup = ps_fin_sup();
		foreach ( ps_fin_def() as $k => $d ) {
			foreach ( array( 'B', 'C', 'D' ) as $e => $col ) {
				$ref = $col . $d[5];
				$val = rtrim( rtrim( sprintf( '%.6F', $sup[ $k ][ $e ] ), '0' ), '.' );
				$xml = preg_replace( '#(<c r="' . $ref . '"[^>]*><v>)[^<]*(</v>)#', '${1}' . $val . '${2}', $xml );
			}
		}
		$z->addFromString( 'xl/worksheets/sheet2.xml', $xml );
		$z->close();
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename=Prevsoul-modelo-financiero-' . gmdate( 'Y-m-d' ) . '.xlsx' );
		readfile( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		wp_delete_file( $tmp );
		exit;
	}
);

/* ------------------------------------------------------------------
 * Pantalla
 * ---------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Finanzas', 'Finanzas', 'manage_options', 'ps-finanzas', 'ps_fin_page' );
	},
	19
);

function ps_fin_fmt( $v, $f ) {
	if ( 'p' === $f ) {
		return number_format( $v * 100, 1, ',', '.' ) . ' %';
	}
	if ( 'm' === $f ) {
		return ( $v < 0 ? '−$' : '$' ) . number_format( abs( $v ), 0, ',', '.' );
	}
	if ( 'n' === $f ) {
		return number_format( $v, 1, ',', '.' );
	}
	return number_format( $v, 0, ',', '.' );
}
function ps_fin_cls( $v ) {
	return $v < 0 ? ' class="ps-fin-neg"' : '';
}

function ps_fin_page() {
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'res'; // phpcs:ignore WordPress.Security.NonceVerification
	$ver  = isset( $_GET['ver'] ) && 'tu' === $_GET['ver'] ? 'tu' : 'red'; // phpcs:ignore WordPress.Security.NonceVerification
	$esc  = isset( $_GET['esc'] ) ? max( 0, min( 2, absint( $_GET['esc'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
	$own  = 'tu' === $ver;
	$sup  = ps_fin_sup();
	$base = admin_url( 'admin.php?page=ps-finanzas' );
	$names = array( 'Conservador', 'Base', 'Optimista' );
	echo '<div class="wrap ps-fin"><h1>Finanzas</h1><p class="ps-fin-muted">Modelo a 24 meses. Borrador del director de finanzas: los supuestos amarillos se validan con datos reales y lo tributario con un contador.</p>';
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Supuestos guardados. Todo se recalculó.</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( array( 'res' => 'Resumen', 'sup' => 'Supuestos', 'mes' => 'Mes a mes', 'real' => 'Real vs. presupuesto', 'xls' => 'Descargar Excel' ) as $k => $l ) {
		echo '<a class="nav-tab' . ( $tab === $k ? ' nav-tab-active' : '' ) . '" href="' . esc_url( add_query_arg( array( 'tab' => $k, 'ver' => $ver, 'esc' => $esc ), $base ) ) . '">' . esc_html( $l ) . '</a>';
	}
	echo '</nav><div class="ps-fin-body">';
	$verbtns = function ( $t ) use ( $ver, $esc, $base ) {
		$o = '<p>';
		foreach ( array( 'red' => 'Red de clínicas', 'tu' => 'Tú atiendes' ) as $k => $l ) {
			$o .= '<a class="button' . ( $ver === $k ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( array( 'tab' => $t, 'ver' => $k, 'esc' => $esc ), $base ) ) . '">' . esc_html( $l ) . '</a> ';
		}
		return $o . '</p>';
	};

	if ( 'res' === $tab ) {
		echo $verbtns( 'res' ); // phpcs:ignore WordPress.Security.EscapeOutput
		$k = array( ps_fin_kpis( $sup, 0, $own ), ps_fin_kpis( $sup, 1, $own ), ps_fin_kpis( $sup, 2, $own ) );
		echo '<div class="ps-fin-k">';
		foreach ( array( array( 'Resultado acumulado 24 meses (Base)', ps_fin_fmt( $k[1]['acum24'], 'm' ) ), array( 'Caja mínima (Base)', ps_fin_fmt( $k[1]['cajamin'], 'm' ) ), array( 'Primer mes sin pérdida (Base)', $k[1]['mes_eq'] ? 'Mes ' . $k[1]['mes_eq'] : 'No llega' ), array( 'Planes para no perder (Base)', ps_fin_fmt( $k[1]['eq'], 'i' ) ) ) as $x ) {
			echo '<div><p>' . esc_html( $x[0] ) . '</p><b>' . esc_html( $x[1] ) . '</b></div>';
		}
		echo '</div><table class="widefat striped ps-fin-t"><thead><tr><th>Indicador</th><th>Conservador</th><th>Base</th><th>Optimista</th></tr></thead><tbody>';
		$rows = array(
			array( 'Planes activos mes 12', 'planes12', 'n' ), array( 'Planes activos mes 24', 'planes24', 'n' ), array( 'Ingreso recurrente (MRR) mes 24', 'mrr24', 'm' ),
			array( 'Resultado acumulado 24 meses', 'acum24', 'm' ), array( 'Caja mínima en 24 meses', 'cajamin', 'm' ), array( 'Primer mes sin pérdida', 'mes_eq', 'mes' ),
			array( 'LTV / CAC (meta ≥ 3)', 'ltvcac', 'x' ), array( 'Planes individuales equivalentes para no perder', 'eq', 'i' ),
		);
		if ( $own ) {
			$rows[] = array( 'Tus horas clínicas en el mes 24', 'horas24', 'n' );
		}
		foreach ( $rows as $r ) {
			echo '<tr><td>' . esc_html( $r[0] ) . '</td>';
			for ( $e = 0; $e < 3; $e++ ) {
				$v = $k[ $e ][ $r[1] ];
				$t = 'mes' === $r[2] ? ( $v ? 'Mes ' . $v : 'No llega' ) : ( 'x' === $r[2] ? number_format( $v, 1, ',', '.' ) . 'x' : ps_fin_fmt( $v, $r[2] ) );
				echo '<td' . ( is_numeric( $v ) ? ps_fin_cls( $v ) : '' ) . '>' . esc_html( $t ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
		// gráfico SVG del resultado acumulado
		$all = array_merge( $k[0]['serie'], $k[1]['serie'], $k[2]['serie'] );
		$mx  = max( $all );
		$mn  = min( 0, min( $all ) );
		$w   = 720;
		$h   = 200;
		$y   = function ( $v ) use ( $mx, $mn, $h ) { return round( 10 + ( $h - 20 ) * ( 1 - ( $v - $mn ) / max( 1, $mx - $mn ) ), 1 ); };
		$svg = '<svg viewBox="0 0 ' . $w . ' ' . ( $h + 20 ) . '" style="width:100%;max-width:820px" role="img" aria-label="Resultado acumulado por escenario"><line x1="40" x2="' . $w . '" y1="' . $y( 0 ) . '" y2="' . $y( 0 ) . '" stroke="#c3c4c7"/>';
		foreach ( array( '#D9727F', '#1D5FD1', '#1F9D74' ) as $e => $c ) {
			$pts = array();
			foreach ( $k[ $e ]['serie'] as $i => $v ) {
				$pts[] = round( 40 + ( $w - 50 ) * $i / 23, 1 ) . ',' . $y( $v );
			}
			$svg .= '<polyline fill="none" stroke="' . $c . '" stroke-width="2.5" points="' . implode( ' ', $pts ) . '"/>';
		}
		$svg .= '<text x="40" y="' . ( $h + 16 ) . '" font-size="11" fill="#646970">Mes 1</text><text x="' . $w . '" y="' . ( $h + 16 ) . '" font-size="11" fill="#646970" text-anchor="end">Mes 24</text></svg>';
		echo '<h2>Resultado acumulado</h2>' . $svg . '<p class="ps-fin-leg"><span><i style="background:#D9727F"></i>Conservador</span><span><i style="background:#1D5FD1"></i>Base</span><span><i style="background:#1F9D74"></i>Optimista</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( $own ) {
			echo '<p class="ps-fin-muted">En «tú atiendes» el resultado incluye el pago de tu trabajo clínico (no es sueldo aparte) y no descuenta tus impuestos personales.</p>';
		}
	} elseif ( 'sup' === $tab ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ps_fin_save">';
		wp_nonce_field( 'ps_fin' );
		echo '<p>Mes 1 del modelo (apertura del sitio): <input type="month" name="ps_fin_inicio" value="' . esc_attr( get_option( 'ps_fin_inicio', '' ) ) . '"></p>';
		echo '<table class="widefat ps-fin-t ps-fin-sup"><thead><tr><th>Supuesto</th><th>Conservador</th><th>Base</th><th>Optimista</th><th>Nota</th></tr></thead><tbody>';
		$sec = '';
		foreach ( ps_fin_def() as $kk => $d ) {
			if ( $d[0] !== $sec ) {
				$sec = $d[0];
				echo '<tr class="ps-fin-sec"><td colspan="5">' . esc_html( $sec ) . '</td></tr>';
			}
			echo '<tr><td>' . esc_html( $d[1] ) . '</td>';
			for ( $e = 0; $e < 3; $e++ ) {
				$v  = $sup[ $kk ][ $e ];
				$sv = 'p' === $d[3] ? rtrim( rtrim( number_format( $v * 100, 2, ',', '' ), '0' ), ',' ) : rtrim( rtrim( number_format( $v, 2, ',', '' ), '0' ), ',' );
				echo '<td><input type="text" name="s[' . esc_attr( $kk ) . '][' . (int) $e . ']" value="' . esc_attr( $sv ) . '" size="9">' . ( 'p' === $d[3] ? ' %' : '' ) . '</td>';
			}
			echo '<td class="ps-fin-muted">' . esc_html( $d[4] ) . '</td></tr>';
		}
		echo '</tbody></table><p>';
		submit_button( 'Guardar y recalcular', 'primary', 'submit', false );
		echo ' <button class="button" name="reset" value="1" onclick="return confirm(\'¿Volver a los supuestos originales?\')">Restaurar originales</button></p></form>';
	} elseif ( 'mes' === $tab ) {
		echo $verbtns( 'mes' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p>';
		foreach ( $names as $e => $n ) {
			echo '<a class="button' . ( $esc === $e ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( array( 'tab' => 'mes', 'ver' => $ver, 'esc' => $e ), $base ) ) . '">' . esc_html( $n ) . '</a> ';
		}
		echo '</p>';
		$r    = ps_fin_run( $sup, $esc, $own );
		$rows = array( array( 'Planes activos', 'planes', 'n' ), array( 'Integrantes cubiertos', 'integ', 'n' ), array( 'Ingreso recurrente (MRR)', 'mrr', 'm' ), array( 'Ingreso bruto (con IVA)', 'bruto', 'm' ), array( 'Ingreso neto (sin IVA)', 'neto', 'm' ), array( $own ? 'Costo prestaciones (clínicas + tuyo)' : 'Pago a clínicas', 'clin', 'm' ), array( 'Comisión pasarela', 'com', 'm' ), array( 'Margen de contribución', 'mc', 'm' ), array( 'Publicidad', 'pub', 'm' ), array( 'Costos fijos', 'fijos', 'm' ), array( 'Resultado del mes', 'res', 'm' ), array( 'Resultado acumulado', 'acum', 'm' ), array( 'Caja', 'caja', 'm' ) );
		if ( $own ) {
			$rows[] = array( 'Tus horas clínicas', 'horas', 'n' );
			$rows[] = array( 'Atenciones derivadas (exceso)', 'derivadas', 'n' );
		}
		echo '<div class="ps-fin-scroll"><table class="widefat striped ps-fin-t"><thead><tr><th>Mes</th>';
		for ( $m = 1; $m <= 24; $m++ ) {
			echo '<th>' . (int) $m . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $rows as $rw ) {
			echo '<tr><td>' . esc_html( $rw[0] ) . '</td>';
			for ( $m = 1; $m <= 24; $m++ ) {
				echo '<td' . ps_fin_cls( $r[ $m ][ $rw[1] ] ) . '>' . esc_html( ps_fin_fmt( $r[ $m ][ $rw[1] ], $rw[2] ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	} elseif ( 'real' === $tab ) {
		echo $verbtns( 'real' ); // phpcs:ignore WordPress.Security.EscapeOutput
		$ini = (string) get_option( 'ps_fin_inicio', '' );
		if ( ! $ini ) {
			echo '<p>Define el <strong>mes 1</strong> (apertura del sitio) en la pestaña Supuestos para comparar con lo real.</p>';
		} else {
			$r     = ps_fin_run( $sup, 1, $own );
			$fotos = (array) get_option( 'ps_dt_fotos', array() );
			$real  = array();
			foreach ( $fotos as $d => $f ) {
				$ym = substr( $d, 0, 7 );
				if ( ! isset( $real[ $ym ] ) ) {
					$real[ $ym ] = array( 'ventas' => 0, 'planes' => 0, 'mrr' => 0 );
				}
				$real[ $ym ]['ventas'] += $f['ventas_monto'];
				$real[ $ym ]['planes']  = array_sum( $f['activos'] );
				$real[ $ym ]['mrr']     = $f['mrr'];
			}
			echo '<p class="ps-fin-muted">Compara con el escenario Base. Lo real sale de las fotos diarias de Prevsoul → Datos.</p><table class="widefat striped ps-fin-t"><thead><tr><th>Mes</th><th>Planes (ppto.)</th><th>Planes (real)</th><th>MRR (ppto.)</th><th>MRR (real)</th><th>Ventas (ppto.)</th><th>Ventas (real)</th></tr></thead><tbody>';
			$dt = DateTime::createFromFormat( 'Y-m-d', $ini . '-01' );
			for ( $m = 1; $m <= 24; $m++ ) {
				$ym = $dt->format( 'Y-m' );
				$rr = isset( $real[ $ym ] ) ? $real[ $ym ] : null;
				echo '<tr><td>' . (int) $m . ' · ' . esc_html( $ym ) . '</td><td>' . esc_html( ps_fin_fmt( $r[ $m ]['planes'], 'n' ) ) . '</td><td>' . ( $rr ? esc_html( ps_fin_fmt( $rr['planes'], 'i' ) ) : '—' ) . '</td><td>' . esc_html( ps_fin_fmt( $r[ $m ]['mrr'], 'm' ) ) . '</td><td>' . ( $rr ? esc_html( ps_fin_fmt( $rr['mrr'], 'm' ) ) : '—' ) . '</td><td>' . esc_html( ps_fin_fmt( $r[ $m ]['bruto'], 'm' ) ) . '</td><td>' . ( $rr ? esc_html( ps_fin_fmt( $rr['ventas'], 'm' ) ) : '—' ) . '</td></tr>';
				$dt->modify( '+1 month' );
			}
			echo '</tbody></table>';
		}
	} else {
		echo '<p>Descarga el modelo completo en Excel, con fórmulas y con los supuestos que tienes guardados. Al abrirlo, Excel o Google Sheets recalculan todo.</p><p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ps_fin_xlsx' ), 'ps_fin_xlsx' ) ) . '"><span class="dashicons dashicons-download" style="vertical-align:-4px"></span> Descargar Prevsoul-modelo-financiero.xlsx</a></p>';
	}
	echo '</div></div><style>.ps-fin-muted{color:#646970}.ps-fin-body{margin-top:14px}.ps-fin-k{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;max-width:1000px;margin:0 0 16px}.ps-fin-k>div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 14px}.ps-fin-k p{margin:0;color:#646970}.ps-fin-k b{display:block;font-size:20px;margin-top:4px;color:#0B2545}.ps-fin-t{max-width:1000px}.ps-fin-t td:not(:first-child),.ps-fin-t th:not(:first-child){text-align:right;white-space:nowrap}.ps-fin-sup td:last-child{text-align:left!important;white-space:normal!important}.ps-fin-neg{color:#b32d2e}.ps-fin-sec td{background:#EAF1FC;color:#164BA8;font-weight:600}.ps-fin-sup input{text-align:right;background:#ffffe0}.ps-fin-scroll{overflow-x:auto;max-width:100%}.ps-fin-scroll .ps-fin-t{max-width:none}.ps-fin-leg span{margin-right:14px}.ps-fin-leg i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:5px}</style>';
}
