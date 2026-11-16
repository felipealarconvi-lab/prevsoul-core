<?php
/**
 * Prevsoul → Armador: precios desde el arancel UCO, packs, planes, oportunidades del buscador y rendimiento.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Armador de packs y planes', 'Armador', 'manage_options', 'ps-armador', 'ps_armador_page' );
	},
	25
);

/** Catálogo base: unidades UCO y factor público por prestación (* = unidades por confirmar). */
function ps_arm_catalogo() {
	return array(
		array( 'id' => 'ex', 'n' => 'Examen y diagnóstico', 'u' => 2, 'f' => 0.5 ),
		array( 'id' => 'ct', 'n' => 'Control periódico', 'u' => 1, 'f' => 0.55 ),
		array( 'id' => 'hp', 'n' => 'Higiene y profilaxis adulto (incluye destartraje supragingival)', 'u' => 2, 'f' => 0.65 ),
		array( 'id' => 'hn', 'n' => 'Higiene y profilaxis niño', 'u' => 1, 'f' => 0.6 ),
		array( 'id' => 'ds', 'n' => 'Destartraje supragingival por sextante', 'u' => 1.5, 'f' => 0.5 ),
		array( 'id' => 'fl', 'n' => 'Aplicación de flúor gel', 'u' => 2, 'f' => 0.3 ),
		array( 'id' => 'se', 'n' => 'Sellante por pieza', 'u' => 1.5, 'f' => 0.45 ),
		array( 'id' => 'rp', 'n' => 'Radiografía panorámica', 'u' => 3.1, 'f' => 0.36 ),
		array( 'id' => 'ih', 'n' => 'Enseñanza de técnica de higiene*', 'u' => 1, 'f' => 0.4 ),
		array( 'id' => 'ur', 'n' => 'Consulta de urgencia*', 'u' => 2, 'f' => 0.6 ),
	);
}

/** Rangos de mercado 2026 (mín, máx, fuente). */
function ps_arm_mercado() {
	return array(
		'ex' => array( 18000, 35000, 'CuantoMeCuesta 2026' ),
		'ct' => array( 15000, 40000, 'Uno Salud, convenios' ),
		'hp' => array( 25000, 50000, 'CuantoMeCuesta 2026' ),
		'hn' => array( 15000, 25000, 'Maipo Salud, Vimadent' ),
		'ds' => array( 18000, 27000, 'Vimadent, Maipo Salud' ),
		'fl' => array( 10000, 20000, 'U. de Chile, Maipo Salud' ),
		'se' => array( 12000, 18000, 'Vimadent, Maipo, convenio UAndes' ),
		'rp' => array( 15000, 35000, 'CuantoMeCuesta 2026' ),
		'ih' => array( 7500, 20000, 'U. de Chile, convenio UAndes' ),
		'ur' => array( 25000, 42000, 'Colmena 2026 (pocas fuentes)' ),
	);
}

function ps_arm_gancho() {
	return array(
		array( 'n' => 'Uno Salud · diagnóstico + radiografías + limpieza (mar-2026)', 'p' => 9900, 'ref' => 90890, 'items' => array( 'ex', 'hp', 'rp' ) ),
		array( 'n' => 'Uno Salud · limpieza + radiografías + diagnóstico (jul-2025)', 'p' => 11900, 'ref' => 90890, 'items' => array( 'ex', 'hp', 'rp' ) ),
		array( 'n' => 'Uno Salud · limpieza (jun-2025)', 'p' => 11900, 'ref' => 0, 'items' => array( 'hp' ) ),
	);
}

function ps_arm_cfg() {
	$def = array( 'cU' => 26850, 'cS' => 24, 'cP' => 28, 'cD' => 10, 'cFp' => 0.38, 'cFam' => 75, 'cG' => 3.51, 'cI' => 1, 'cUF' => 39800, 'cR' => 1000, 'plu' => 75, 'plch' => 4, 'plmo' => 40 );
	$o   = get_option( 'ps_arm', array() );
	return array_merge( $def, isset( $o['cfg'] ) && is_array( $o['cfg'] ) ? $o['cfg'] : array() );
}

/** Sugerencia de prestaciones según el nombre de la necesidad detectada por el buscador. */
function ps_arm_items_para( $need ) {
	$n = mb_strtolower( $need );
	$map = array(
		'urgenc|dolor|duele'          => array( array( 'ur', 1 ), array( 'rp', 1 ) ),
		'niñ|infantil|hij'            => array( array( 'ex', 1 ), array( 'hn', 1 ), array( 'fl', 1 ), array( 'se', 2 ) ),
		'encía|encia|sangr|periodon'  => array( array( 'ex', 1 ), array( 'ds', 2 ), array( 'ih', 1 ) ),
		'limpi|sarro|destartr'        => array( array( 'ex', 1 ), array( 'hp', 1 ) ),
		'control|revis|chequeo|rutin' => array( array( 'ct', 1 ), array( 'hp', 1 ) ),
		'radiograf|rx'                => array( array( 'ex', 1 ), array( 'rp', 1 ) ),
		'sellant|caries'              => array( array( 'ex', 1 ), array( 'se', 2 ), array( 'fl', 1 ) ),
	);
	foreach ( $map as $re => $items ) {
		if ( preg_match( '/' . $re . '/u', $n ) ) {
			return $items;
		}
	}
	return array( array( 'ex', 1 ), array( 'hp', 1 ) );
}

function ps_arm_tipo( $p ) {
	$all = array_merge( ps_parent_slugs( $p ), wp_get_post_terms( $p->get_parent_id() ? $p->get_parent_id() : $p->get_id(), 'product_cat', array( 'fields' => 'slugs' ) ) );
	if ( in_array( 'planes-dentales', $all, true ) ) {
		return 'plan';
	}
	if ( in_array( 'packs-dentales', $all, true ) ) {
		return 'pack';
	}
	return 'at';
}

/** Canal de una compra: 0 anuncios Meta, 1 redes orgánico, 2 buscador, 3 consulta online, 4 directo/otros. */
function ps_arm_canal( $o, $precitas ) {
	$src = strtolower( (string) $o->get_meta( '_wc_order_attribution_utm_source' ) );
	$med = strtolower( (string) $o->get_meta( '_wc_order_attribution_utm_medium' ) );
	$cnt = strtolower( (string) $o->get_meta( '_wc_order_attribution_utm_content' ) );
	if ( 'meta' === $src || 'paid' === $med || 0 === strpos( $cnt, 'ab-' ) ) {
		return 0;
	}
	if ( in_array( $src, array( 'instagram', 'facebook', 'ig', 'fb' ), true ) ) {
		return 1;
	}
	if ( $o->get_meta( '_ps_bus' ) ) {
		return 2;
	}
	if ( isset( $precitas[ strtolower( (string) $o->get_billing_email() ) ] ) ) {
		return 3;
	}
	return 4;
}

function ps_arm_datos( $since, $now ) {
	/* Oportunidades desde el registro del buscador. */
	$by = array();
	foreach ( (array) get_option( 'ps_search_log', array() ) as $e ) {
		if ( empty( $e['t'] ) || $e['t'] < $since || '' === ( $e['need'] ?? '' ) ) {
			continue;
		}
		$k = $e['need'];
		if ( ! isset( $by[ $k ] ) ) {
			$by[ $k ] = array( 'n' => $k, 'q' => 0, 'c' => 0, 'p' => 0, 'items' => ps_arm_items_para( $k ) );
		}
		$by[ $k ]['q']++;
		$by[ $k ]['c'] += empty( $e['c'] ) ? 0 : 1;
		$by[ $k ]['p'] += empty( $e['p'] ) ? 0 : 1;
	}
	uasort( $by, function ( $a, $b ) { return $b['q'] <=> $a['q']; } );

	/* Precitas del período (correo → sí). */
	$pre = array();
	foreach ( get_posts( array( 'post_type' => 'ps_precita', 'post_status' => 'any', 'numberposts' => -1, 'date_query' => array( array( 'after' => gmdate( 'Y-m-d H:i:s', $since - 30 * DAY_IN_SECONDS ) ) ), 'fields' => 'ids' ) ) as $pid ) {
		$em = strtolower( (string) get_post_meta( $pid, '_ps_email', true ) );
		if ( $em ) {
			$pre[ $em ] = 1;
		}
	}

	/* Rendimiento desde los pedidos pagados. */
	$perf = array();
	$ab   = array( 'a' => array( 'n' => 'A', 's' => 0, 'ing' => 0 ), 'b' => array( 'n' => 'B', 's' => 0, 'ing' => 0 ) );
	$orders = function_exists( 'ps_orders_between' ) ? ps_orders_between( $since, $now ) : array();
	foreach ( $orders as $o ) {
		$canal = ps_arm_canal( $o, $pre );
		$cnt   = strtolower( (string) $o->get_meta( '_wc_order_attribution_utm_content' ) );
		foreach ( $o->get_items() as $it ) {
			$p = $it->get_product();
			if ( ! $p ) {
				continue;
			}
			$id = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
			if ( ! isset( $perf[ $id ] ) ) {
				$perf[ $id ] = array( 'n' => get_the_title( $id ), 't' => ps_arm_tipo( $p ), 'v' => 0, 'ing' => 0, 'mixn' => array( 0, 0, 0, 0, 0 ), 'bus' => 0, 'pre' => 0, 'op' => get_post_meta( $id, '_ps_arm_opp', true ) ? 1 : 0, 'pr' => (float) $p->get_price() );
			}
			$q = (int) $it->get_quantity();
			$perf[ $id ]['v']   += $q;
			$perf[ $id ]['ing'] += (float) $it->get_total() + (float) $it->get_total_tax();
			$perf[ $id ]['mixn'][ $canal ] += $q;
			$perf[ $id ]['bus'] += 2 === $canal ? $q : 0;
			$perf[ $id ]['pre'] += 3 === $canal ? $q : 0;
			if ( preg_match( '/^ab-([ab])-(.+)$/', $cnt, $m ) ) {
				$ab[ $m[1] ]['s']   += $q;
				$ab[ $m[1] ]['ing'] += (float) $it->get_total() + (float) $it->get_total_tax();
				$ab[ $m[1] ]['n']    = strtoupper( $m[1] ) . ' · ' . $m[2];
			}
		}
	}
	uasort( $perf, function ( $a, $b ) { return $b['ing'] <=> $a['ing']; } );

	/* Planes activos y abandono (últimos 31 días frente a los 31 anteriores). */
	$plans = array();
	if ( function_exists( 'ps_orders_between' ) ) {
		$ult = array();
		$ant = array();
		foreach ( array( array( $now - 31 * DAY_IN_SECONDS, $now, 'u' ), array( $now - 62 * DAY_IN_SECONDS, $now - 31 * DAY_IN_SECONDS - 1, 'a' ) ) as $r ) {
			foreach ( ps_orders_between( $r[0], $r[1] ) as $o ) {
				foreach ( $o->get_items() as $it ) {
					$p = $it->get_product();
					if ( $p && 'plan' === ps_arm_tipo( $p ) ) {
						$id = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
						$em = strtolower( (string) $o->get_billing_email() );
						if ( 'u' === $r[2] ) {
							$ult[ $id ][ $em ] = (float) $p->get_price();
						} else {
							$ant[ $id ][ $em ] = 1;
						}
					}
				}
			}
		}
		foreach ( array_unique( array_merge( array_keys( $ult ), array_keys( $ant ) ) ) as $id ) {
			$u    = isset( $ult[ $id ] ) ? $ult[ $id ] : array();
			$a    = isset( $ant[ $id ] ) ? $ant[ $id ] : array();
			$lost = count( array_diff_key( $a, $u ) );
			$plans[] = array( 'n' => get_the_title( $id ), 'act' => count( $u ), 'ch' => $a ? round( $lost / count( $a ) * 100, 1 ) : null, 'pr' => $u ? (float) reset( $u ) : (float) get_post_meta( $id, '_price', true ) );
		}
	}

	return array( 'opp' => array_values( $by ), 'perf' => array_values( $perf ), 'plans' => $plans, 'ab' => array_values( $ab ) );
}

define( 'PS_ARM_CSS', <<<'CSS'
#psarm{--n:#0B2545;--p:#1D5FD1;--b:#EAF1FC;--m:#5FD3B8;--mp:#167A64;--ms:#E6F7F2;--g:#4A5B73;--l:#D7E1EE;--f:#F4F7FB}
#psarm .lead{color:var(--g);margin:0 0 14px}
#psarm .tabs{display:flex;gap:4px;border-bottom:1px solid #c3c4c7;margin-bottom:14px}
#psarm .tabs button{border:1px solid #c3c4c7;border-bottom:0;background:#e5e5e5;padding:8px 14px;font:inherit;font-weight:600;cursor:pointer;border-radius:4px 4px 0 0}
#psarm .tabs button.on{background:#f0f0f1;margin-bottom:-1px}
#psarm .pane{display:none}
#psarm .pane.on{display:block}
#psarm .card{background:#fff;border:1px solid var(--l);border-radius:12px;padding:14px 16px;margin-bottom:12px}
#psarm .card h3{margin:0 0 10px;font-size:14px}
#psarm .grid{display:grid;grid-template-columns:1.3fr 1fr;gap:12px}
#psarm table{width:100%;border-collapse:collapse}
#psarm th,#psarm td{padding:6px 8px;border-bottom:1px solid #EEF1F5;text-align:left;vertical-align:middle}
#psarm th{font-size:11.5px;color:var(--g);font-weight:700}
#psarm td.n,#psarm th.n{text-align:right;font-variant-numeric:tabular-nums}
#psarm input[type=number],#psarm input[type=text],#psarm select{border:1px solid #c3c4c7;border-radius:4px;padding:4px 6px;font:inherit}
#psarm input.s{width:70px}
#psarm .btn{background:var(--p);color:#fff;border:0;border-radius:4px;padding:6px 12px;font:inherit;font-weight:600;cursor:pointer}
#psarm .btn.ghost{background:#fff;color:var(--p);border:1px solid var(--p)}
#psarm .btn:disabled{opacity:.5;cursor:not-allowed}
#psarm .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:8px 0}
#psarm .kpi{background:var(--f);border-radius:10px;padding:10px}
#psarm .kpi b{display:block;font-size:18px;color:var(--n)}
#psarm .kpi span{font-size:11px;color:var(--g)}
#psarm .ok{color:var(--mp);font-weight:700}
#psarm .wa{color:#8A5300;font-weight:700}
#psarm .note{font-size:11.5px;color:var(--g);background:var(--f);border-radius:8px;padding:8px 10px;margin-top:8px}
#psarm .prev{border:1px solid var(--l);border-radius:16px;padding:16px;background:var(--b);font-family:"Plus Jakarta Sans",-apple-system,sans-serif}
#psarm .prev .t{font-weight:800;font-size:17px;color:var(--n)}
#psarm .prev ul{margin:8px 0;padding-left:18px;color:var(--n)}
#psarm .prev .ref{font-size:12px;color:var(--g)}
#psarm .prev .pr{font-size:24px;font-weight:800;color:var(--n)}
#psarm .prev .who{font-size:12px;color:var(--g)}
#psarm .chip{display:inline-block;background:var(--ms);color:var(--mp);border-radius:99px;padding:2px 8px;font-size:11px;font-weight:700}
#psarm .opp td:first-child{font-weight:700}
#psarm .tag{font-size:10.5px;font-weight:800;background:#FFF4DE;color:#8A5300;border-radius:99px;padding:2px 8px}
#psarm .light{display:flex;gap:10px;align-items:flex-start;border-radius:12px;padding:12px 14px;margin:10px 0;font-weight:600}
#psarm .light.g{background:#E6F7F2;border:1px solid #5FD3B8;color:#0E5A49}
#psarm .light.r{background:#FDECEC;border:1px solid #F3A6A6;color:#8F1D1D}
#psarm .light .dot{width:14px;height:14px;border-radius:50%;flex:none;margin-top:2px}
#psarm .light.g .dot{background:#1F9D74}
#psarm .light.r .dot{background:#D93636}
#psarm .light ul{margin:4px 0 0;padding-left:18px;font-weight:400}
#psarm .bdg{font-size:11px;font-weight:700;border-radius:99px;padding:2px 8px;white-space:nowrap}
#psarm .bdg.g{background:#E6F7F2;color:#0E5A49}
#psarm .bdg.r{background:#FDECEC;color:#8F1D1D}
#psarm .bdg.a{background:#FFF4DE;color:#8A5300}
#psarm .pg{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;letter-spacing:.04em;border-radius:6px;padding:2px 7px;cursor:help}
#psarm .pg.a{background:#FFF4DE;color:#8A5300;border:1px solid #F1C77A}
#psarm .pg.r{background:#FDECEC;color:#8F1D1D;border:1px solid #F3A6A6}
#psarm .pgbox{border-radius:12px;padding:10px 14px;margin:10px 0;font-size:12.5px}
#psarm .pgbox.a{background:#FFF8EC;border:1px solid #F1C77A;color:#6B4200}
#psarm .pgbox.r{background:#FDECEC;border:1px solid #F3A6A6;color:#8F1D1D}
#psarm .pgbox b{font-weight:800}
#psarm{--pk:#D63384;--pks:#FCE4F0;--pkb:#F5B8D4}
#psarm .tabs button.pink{background:var(--pks);color:#9C1F5E;border-color:var(--pkb)}
#psarm .tabs button.pink.on{background:#fff;color:#9C1F5E;box-shadow:inset 0 3px 0 var(--pk)}
#psarm #opp .card{border-color:var(--pkb);background:linear-gradient(#fff,#fff)}
#psarm #opp h3{color:#9C1F5E}
#psarm .opp tbody tr{background:#FFF5FA}
#psarm .opp tbody tr:hover{background:var(--pks)}
#psarm .opp td:first-child{border-left:4px solid var(--pk)}
#psarm .chip-pk{display:inline-block;background:var(--pks);color:#9C1F5E;border:1px solid var(--pkb);border-radius:99px;padding:1px 8px;font-size:11px;font-weight:700}
#psarm #opp .btn.ghost{color:#9C1F5E;border-color:var(--pk)}
#psarm .pills{display:flex;gap:4px;margin-bottom:10px}
#psarm .pills button{border:1px solid #c3c4c7;background:#fff;border-radius:99px;padding:4px 12px;font:inherit;font-size:12px;cursor:pointer}
#psarm .pills button.on{background:var(--n);color:#fff;border-color:var(--n)}
#psarm .k4{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px}
#psarm .mix{display:flex;height:10px;border-radius:99px;overflow:hidden;min-width:120px;background:#EEF1F5}
#psarm .mix span{display:block;height:100%}
#psarm .leg{display:flex;gap:10px;flex-wrap:wrap;font-size:11px;color:var(--g);margin-top:6px}
#psarm .leg i{display:inline-block;width:9px;height:9px;border-radius:2px;margin-right:4px;vertical-align:-1px}
#psarm .dotc{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px}
#psarm .dotc.g{background:#1F9D74}
#psarm .dotc.r{background:#D93636}
#psarm .vs{font-size:11px;color:var(--g)}
#psarm .rec{margin:0;padding-left:18px}
#psarm .rec li{margin-bottom:6px}
#psarm .rec b{color:var(--n)}
#psarm .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
#psarm{color:#1d2327;max-width:1300px}#psarm .lead{color:#4A5B73;margin:0 0 14px}#psarm .card{max-width:none;min-width:0;box-shadow:none}
CSS
);
define( 'PS_ARM_BODY', <<<'HTML'
<div class="row" style="justify-content:space-between;margin-bottom:10px"><p class="lead" style="margin:0">Precios anclados en el arancel referencial UCO, comisión por tipo de producto, oportunidades del buscador y rendimiento real.</p><form method="post" action="__ACTION__" id="psarmf"><input type="hidden" name="action" value="ps_arm_save">__NONCE__<input type="hidden" name="data" id="psarmd"><button class="btn" type="submit">Guardar factores y ajustes</button></form></div>


<div class="tabs"><button data-t="cat" class="on">Catálogo UCO</button><button data-t="pack">Armar pack</button><button data-t="plan">Armar plan</button><button data-t="opp" class="pink">Oportunidades</button><button data-t="perf">Rendimiento</button><button data-t="rec">Recomendaciones</button><button data-t="cfg">Ajustes</button></div>

<div class="pane on" id="cat"><div class="card"><h3>Catálogo de prestaciones</h3>
<table><thead><tr><th>Prestación</th><th class="n">UCO</th><th class="n">Arancel ref.</th><th class="n">Factor público</th><th class="n">Precio Prevsoul</th><th class="n">% del arancel</th><th class="n">Mercado (mín–máx)</th><th>Frente al mercado</th><th class="n">Pago clínica (suelta)</th><th class="n">Prevsoul neto</th></tr></thead><tbody id="catb"></tbody></table>
<p class="note">El factor público ajusta cada prestación a precios de mercado: el UCO es la referencia, no el precio final. Las unidades marcadas con * están por confirmar contra la tabla UCO vigente.</p></div></div>

<div class="pane" id="pack"><div class="grid"><div class="card"><h3>Prestaciones del pack</h3>
<div class="row" style="margin-bottom:8px"><input type="text" id="pkn" value="Pack Control + Limpieza" style="flex:1"><select id="pkadd"></select><button class="btn ghost" id="pkaddb">Agregar</button></div>
<table><thead><tr><th>Prestación</th><th class="n">Cant.</th><th class="n">Arancel</th><th class="n">Precio suelto</th><th></th></tr></thead><tbody id="pkb"></tbody></table>
<div class="kpis"><div class="kpi"><span>Precio del pack</span><b id="pkp">–</b></div><div class="kpi"><span>Pago a la clínica</span><b id="pkc">–</b></div><div class="kpi"><span>Le queda a Prevsoul (neto)</span><b id="pkm">–</b></div></div>
<div id="pkchk"></div><div id="pklight"></div><h3 style="margin-top:10px">Recomendaciones</h3><ul class="rec" id="pkrec"></ul>
<div class="row" style="margin-top:8px"><button class="btn" disabled>Crear producto en WooCommerce</button><span class="note" style="margin:0">Desactivado por ahora: el pack se crea a mano en Productos con los datos de esta pantalla.</span></div></div>
<div class="card"><h3>Así lo ve el paciente</h3><div class="prev" id="pkprev"></div><p class="note">Monto redondo, arancel de referencia visible y sin lenguaje de oferta. La comisión no se muestra al paciente.</p></div></div></div>

<div class="pane" id="plan"><div class="grid"><div class="card"><h3>Prestaciones incluidas al año</h3>
<div class="row" style="margin-bottom:8px"><input type="text" id="pln" value="Plan Preventivo" style="flex:1"><select id="pladd"></select><button class="btn ghost" id="pladdb">Agregar</button></div>
<table><thead><tr><th>Prestación</th><th class="n">Veces al año</th><th class="n">Precio Prevsoul</th><th></th></tr></thead><tbody id="plb"></tbody></table>
<div class="row" style="margin-top:8px">Cuota a evaluar <input type="number" id="plqv" value="12000" style="width:90px"> <button class="btn ghost" id="plqs">Usar la sugerida</button> · Costo por cliente en Meta (estimado) <input type="number" id="plcacv" value="25000" style="width:90px"></div><div class="row" style="margin-top:8px">Uso esperado <input type="number" class="s" id="plu" value="75">% · Abandono <input type="number" class="s" id="plch" value="4">%/mes · Margen objetivo <input type="number" class="s" id="plmo" value="40">% · Descuento en tratamientos <input type="number" class="s" id="pld" value="15">%</div>
<div class="kpis"><div class="kpi"><span>Cuota sugerida (margen objetivo)</span><b id="plq">–</b></div><div class="kpi"><span>Margen al año</span><b id="plm">–</b></div><div class="kpi"><span>Valor de vida (LTV)</span><b id="plltv">–</b></div></div>
<div class="kpis"><div class="kpi"><span>Costo máx. por cliente en Meta</span><b id="plcac">–</b></div><div class="kpi"><span>Pago a clínicas al año (esperado)</span><b id="plpc">–</b></div><div class="kpi"><span>Cuota en UF</span><b id="pluf">–</b></div></div>
<div id="pllight"></div><h3 style="margin-top:10px">Recomendaciones</h3><ul class="rec" id="plrec"></ul><p class="note">Pago a la clínica en planes: factor clínica de plan × arancel, por prestación usada. Costo máximo en Meta = un tercio del LTV.</p></div>
<div class="card"><h3>Así lo ve el paciente</h3><div class="prev" id="plprev"></div><p class="note">Precio por persona; en el familiar cada integrante adicional paga el porcentaje definido en Ajustes.</p></div></div></div>

<div class="pane" id="opp"><div class="card"><h3>Oportunidades detectadas por el buscador</h3>
<table class="opp"><thead><tr><th>Necesidad</th><th class="n">Búsquedas</th><th class="n">Consultas online</th><th class="n">Compras</th><th>Pack sugerido</th><th class="n">Precio sugerido</th><th></th></tr></thead><tbody id="oppb"></tbody></table>
<p class="note">Lee el registro del buscador del período elegido: necesidades detectadas, consultas online y compras que empezaron con una búsqueda. «Falta producto» aparece cuando hay 10 o más búsquedas y menos de 5 % termina en compra. Las búsquedas que no se entendieron están en Prevsoul → Buscador. «Armar este pack» lo carga en la pestaña Armar pack.</p></div></div>

<div class="pane" id="perf">
<div id="perfper"></div>
<div class="k4"><div class="kpi"><span>Ventas de packs</span><b id="kp1">–</b></div><div class="kpi"><span>Planes activos</span><b id="kp2">–</b></div><div class="kpi"><span>Le queda a Prevsoul (neto)</span><b id="kp3">–</b></div><div class="kpi"><span>Costo real por cliente en Meta</span><b id="kp4">–</b></div></div>
<div class="card"><h3>Rendimiento por producto</h3>
<table><thead><tr><th>Producto</th><th class="n">Ventas</th><th class="n">Ingresos</th><th class="n">Prevsoul neto</th><th class="n">Margen</th><th>De dónde vinieron</th><th class="n">Desde buscador</th><th class="n">Desde precita</th><th>Estado</th></tr></thead><tbody id="perfb"></tbody></table>
<div class="leg"><span><i style="background:#1D5FD1"></i>Anuncios en Meta</span><span><i style="background:#7CC4F2"></i>Instagram y Facebook orgánico</span><span><i style="background:#D63384"></i>Buscador</span><span><i style="background:#0B2545"></i>Consulta online</span><span><i style="background:#C3CBD6"></i>Directo y otros</span></div><p class="note" id="perfempty" style="display:none">Todavía no hay ventas en este período. La tabla se llena sola con los pedidos pagados.</p></div>
<div class="grid"><div class="card"><h3>Planes: supuesto frente a realidad</h3><table><thead><tr><th>Plan</th><th class="n">Activos</th><th class="n">Abandono (mes)</th><th class="n">LTV real</th><th>Semáforo</th></tr></thead><tbody id="perfpl"></tbody></table><p class="note">Activos: personas que pagaron el plan en los últimos 31 días. Abandono: quienes pagaron el mes anterior y no este. El LTV usa la cuota, el uso supuesto del Armador y el abandono real. Si el semáforo cambia a rojo, revisa la cuota en «Armar plan».</p></div>
<div class="card"><h3>Prueba A/B (Versión para redes)</h3><table><thead><tr><th>Versión</th><th class="n">Ventas</th><th class="n">Ingresos</th><th class="n">Costo por venta</th></tr></thead><tbody id="perfab"></tbody></table><p class="note">Se identifica por el enlace con seguimiento (utm_content ab-a-… / ab-b-…) que arma el recuadro «Prueba A/B» de cada producto. Los clics se ven en Google Analytics.</p>
<div class="row">Gasto en Meta del período <input type="number" id="pmeta" value="0" style="width:110px"> <span class="vs">se ingresa a mano hasta conectar Meta; se guarda con «Guardar»</span></div></div></div>
<div class="card"><h3>De dónde salen estos datos</h3><ul class="rec">
<li><b>Pedidos de WooCommerce:</b> ventas e ingresos por producto y período (igual que el Tablero).</li>
<li><b>Atribución de pedidos:</b> la fuente de cada compra (Meta, Instagram, Facebook, buscador, directo) desde los enlaces con seguimiento de la Versión para redes y la Prueba A/B.</li>
<li><b>Buscador:</b> qué compras empezaron con una búsqueda; las oportunidades en rosado que se convirtieron en producto aparecen marcadas.</li>
<li><b>Consulta online:</b> qué precitas terminaron en un pack o plan.</li>
<li><b>Margen:</b> estimado con la comisión de packs y atenciones y el margen objetivo de planes definidos en Ajustes.</li>
<li><b>Armador:</b> los factores, la comisión y el semáforo con que se diseñó cada producto.</li></ul></div></div>
<div class="pane" id="rec"><div class="card"><h3>Decisiones recomendadas en precios</h3><ul class="rec" id="recall"></ul></div>
<div class="card"><h3>Precios gancho detectados</h3><table><thead><tr><th>Campaña</th><th class="n">Precio</th><th class="n">Valor referencial que informan</th></tr></thead><tbody>
<tr><td>Uno Salud · diagnóstico + radiografías + limpieza + abono $10.000 (mar-2026)</td><td class="n">$9.900</td><td class="n">$90.890</td></tr>
<tr><td>Uno Salud · limpieza + radiografías + diagnóstico (jul-2025)</td><td class="n">$11.900</td><td class="n">$90.890</td></tr>
<tr><td>Uno Salud · diagnóstico + radiografías + limpieza (abr-2024)</td><td class="n">$26.800</td><td class="n">$86.980</td></tr>
</tbody></table><p class="note">Son precios de entrada para captar pacientes y vender tratamientos después. <span class="pg a">⚠ PG</span> ámbar: categoría con gancho en el mercado. <span class="pg r">⚠ PG</span> rojo: tu precio cae en zona de liquidación.</p></div>
<div class="card"><h3>Precios de planes en el mercado chileno</h3><table><thead><tr><th>Plan</th><th class="n">Cuota mensual</th><th>Qué incluye</th></tr></thead><tbody>
<tr><td>Redental (prevención individual)</td><td class="n">≈ $6.500</td><td>Prevención básica</td></tr>
<tr><td>BCI Seguro Dental · Urgencia</td><td class="n">$8.765</td><td>Urgencia, radiografía de la pieza, extracción simple</td></tr>
<tr><td>BCI Seguro Dental · Básico</td><td class="n">$10.359</td><td>Urgencia + 1 limpieza al año + examen</td></tr>
<tr><td>Plan Soy RedSalud Dental</td><td class="n">≈ $10.800 (UF 0,28)</td><td>Prevención por beneficiario</td></tr>
<tr><td>BCI Seguro Dental · Full</td><td class="n">$17.132</td><td>Básico + obturaciones, endodoncia, extracciones</td></tr>
<tr><td>Promedio individual del mercado</td><td class="n">≈ $18.000</td><td>Rango general $5.000–$80.000</td></tr>
</tbody></table><p class="note">Fuentes: BCI, RedSalud y CuantoMeCuesta (2026). Los planes con tratamientos incluidos son seguros regulados por la CMF.</p></div></div>
<div class="pane" id="cfg"><div class="card"><h3>Ajustes</h3>
<table><tbody>
<tr><td>Valor UCO 2026</td><td><input type="number" id="cU" value="26850"></td></tr>
<tr><td>Comisión en atenciones sueltas</td><td><input type="number" class="s" id="cS" value="24"> %</td></tr>
<tr><td>Comisión en packs</td><td><input type="number" class="s" id="cP" value="28"> %</td></tr>
<tr><td>Descuento del pack frente a la suma de precios sueltos</td><td><input type="number" class="s" id="cD" value="10"> %</td></tr>
<tr><td>Factor clínica en planes (× arancel)</td><td><input type="number" class="s" id="cFp" value="0.38" step="0.01"></td></tr>
<tr><td>Integrante adicional en plan familiar</td><td><input type="number" class="s" id="cFam" value="75"> % de la cuota</td></tr>
<tr><td>Pasarela de pago</td><td><input type="number" class="s" id="cG" value="3.51" step="0.01"> %</td></tr>
<tr><td>IVA sobre la comisión de Prevsoul</td><td><label><input type="checkbox" id="cI" checked> Aplicar 19 % (confirmar con el contador)</label></td></tr>
<tr><td>Valor UF</td><td><input type="number" id="cUF" value="39800"> <span class="note" style="display:inline">referencial, actualizar</span></td></tr>
<tr><td>Redondeo</td><td><select id="cR"><option value="1000" selected>A miles ($30.000)</option><option value="500">A 500</option></select></td></tr>
</tbody></table></div></div>

HTML
);
define( 'PS_ARM_JS', <<<'JS'

var D=window.PSARM,MK=D.mk,GANCHO=D.gancho,C=D.cat;
function pgCheck(ids,price,margin,mmin){var cat=GANCHO.filter(function(g){return g.items.every(function(i){return ids.indexOf(i)>=0})||g.items.filter(function(i){return ids.indexOf(i)>=0}).length>=2||(g.items.length===1&&ids.indexOf(g.items[0])>=0&&ids.length<=2)});
 var zone=(margin<0.15)||(mmin&&price<mmin*0.8);if(!cat.length&&!zone)return null;var low=cat.length?Math.min.apply(null,cat.map(function(g){return g.p})):0;
 return {lvl:zone?'r':'a',low:low,cat:cat}}
function pgBadge(r){if(!r)return '';return '<span class="pg '+r.lvl+'" title="'+(r.lvl==='r'?'Precio gancho: tu precio está en zona de liquidación':'Categoría con precios gancho en el mercado: no competir por precio')+'">⚠ PG</span>'}
var $=function(i){return document.getElementById(i)},v=function(i){return +$(i).value},R=function(x){var s=v('cR');return Math.round(x/s)*s},M=function(x){return '$'+Math.round(x).toLocaleString('es-CL')},get=function(id){return C.filter(function(c){return c.id===id})[0]};
function ar(c){return c.u*v('cU')}function pr(c){return R(ar(c)*c.f)}
function net(gross,price){return (document.getElementById('cI').checked?gross/1.19:gross)-price*v('cG')/100}
function cat(){$('catb').innerHTML=C.map(function(c,i){var p=pr(c),cl=R(p*(1-v('cS')/100)),n=net(p-cl,p);return '<tr><td>'+c.n+'</td><td class="n">'+String(c.u).replace('.',',')+'</td><td class="n">'+M(ar(c))+'</td><td class="n"><input type="number" class="s" step="0.01" value="'+c.f+'" data-i="'+i+'"></td><td class="n"><b>'+M(p)+'</b></td><td class="n">'+Math.round(p/ar(c)*100)+'%</td>'+mk(c.id,p)+'<td class="n">'+M(cl)+'</td><td class="n">'+M(n)+'</td></tr>'}).join('');[].forEach.call(document.querySelectorAll('#catb input'),function(el){el.oninput=function(){C[+el.dataset.i].f=+el.value;all()}})}
var PK=[['ex',1],['hp',1],['ih',1]],PL=[['ct',2],['hp',2],['rp',1]];
function opts(){var o=C.map(function(c){return '<option value="'+c.id+'">'+c.n+'</option>'}).join('');$('pkadd').innerHTML=o;$('pladd').innerHTML=o}
function rows(list,tb,yr){$(tb).innerHTML=list.map(function(r,i){var c=get(r[0]);return '<tr><td>'+c.n+'</td><td class="n"><input type="number" class="s" min="1" value="'+r[1]+'" data-i="'+i+'"></td>'+(yr?'':'<td class="n">'+M(ar(c)*r[1])+'</td>')+'<td class="n">'+M(pr(c)*r[1])+'</td><td><a href="#" data-x="'+i+'">Quitar</a></td></tr>'}).join('');[].forEach.call(document.querySelectorAll('#'+tb+' input'),function(el){el.oninput=function(){list[+el.dataset.i][1]=Math.max(1,+el.value);all()}});[].forEach.call(document.querySelectorAll('#'+tb+' a[data-x]'),function(a){a.onclick=function(e){e.preventDefault();list.splice(+a.dataset.x,1);all()}})}
function pack(){rows(PK,'pkb',false);var A=0,S=0;PK.forEach(function(r){var c=get(r[0]);A+=ar(c)*r[1];S+=pr(c)*r[1]});var P=R(S*(1-v('cD')/100)),cl=R(P*(1-v('cP')/100)),n=net(P-cl,P);
 $('pkp').textContent=M(P);$('pkc').textContent=M(cl);$('pkm').textContent=M(n)+' ('+(P?Math.round(n/P*100):0)+'%)';
 var chk=[[P%1000===0,'Monto redondo, sin terminación en 9'],[P<A,'Bajo el arancel de referencia ('+(A?Math.round((1-P/A)*100):0)+'% menos)'],[n>0,'Margen positivo para Prevsoul']];
 $('pkchk').innerHTML='<ul style="list-style:none;padding:0;margin:6px 0">'+chk.map(function(c){return '<li><span class="'+(c[0]?'ok':'wa')+'">'+(c[0]?'✓':'!')+'</span> '+c[1]+'</li>'}).join('')+'</ul>';
var mmin=0,mmax=0;PK.forEach(function(r){var m=MK[r[0]];if(m){mmin+=m[0]*r[1];mmax+=m[1]*r[1]}});
 var mg=P?n/P:0,okp=mg>=0.15&&P<=mmax*0.95&&P>=mmin*0.8;
 light('pklight',okp,okp?'Pack rentable y dentro del mercado':'Pack no rentable o fuera de mercado',[(mg>=0.15?'Margen neto '+Math.round(mg*100)+'% (mínimo recomendado 15%)':'Margen neto '+Math.round(mg*100)+'%: bajo el mínimo recomendado de 15%'),'Suma de precios de mercado de lo incluido: '+M(mmin)+' – '+M(mmax)+(P>mmax*0.95?' · el pack queda muy cerca o sobre el máximo':(P<mmin*0.8?' · el pack queda '+Math.round((1-P/mmin)*100)+'% bajo el mínimo del mercado':' · dentro del rango'))]);
 var ids=PK.map(function(r){return r[0]}),pg=pgCheck(ids,P,mg,mmin);
 $('pklight').innerHTML+=pg?'<div class="pgbox '+pg.lvl+'"><b>⚠ PG · Precio gancho.</b> '+(pg.lvl==='r'?'Tu precio está en zona de liquidación (margen bajo 15 % o más de 20 % bajo el mínimo del mercado). <b>No conviene</b>: no cubre el costo de conseguir clientes y empuja a la marca a competir solo por precio.':'En esta categoría hay campañas gancho'+(pg.low?' desde '+M(pg.low):'')+' ('+pg.cat[0].n+'). <b>No conviene competir por precio</b>: diferencia por odontólogo de cabecera y transparencia, o úsalo solo como entrada al plan.')+'</div>':'';
 var rp=[];if(pg)rp.push('<b>PG:</b> no bajes este pack para alcanzar al gancho'+(pg.low?' de '+M(pg.low):'')+'. Mejor: inclúyelo como beneficio del plan (primer control incluido) y comunica lo que el gancho no da: mismo dentista y seguimiento.');if(mg<0.15)rp.push('<b>Sube la comisión del pack</b> o <b>baja el descuento</b> del pack (hoy '+v('cD')+'%).');if(P>mmax*0.95)rp.push('<b>Baja el factor público</b> de las prestaciones marcadas «Sobre el mercado» en el Catálogo.');if(P<mmin*0.8)rp.push('<b>Sube el precio</b> al menos a '+M(R(mmin*0.8))+' (o baja el descuento del pack): bajo eso compites con campañas gancho.');rp.push('Cada pack necesita varias ventas para pagar un cliente en Meta: úsalo como <b>entrada al plan</b> (por ejemplo, con un mes de plan incluido).');if(PK.length<2)rp.push('Un pack de una sola prestación no aporta: <b>combina diagnóstico + prevención</b>.');recs('pkrec',rp);
 $('pkprev').innerHTML='<span class="chip">Pack dental</span><div class="t" style="margin-top:6px">'+$('pkn').value+'</div><ul>'+PK.map(function(r){var c=get(r[0]);return '<li>'+(r[1]>1?r[1]+' × ':'')+c.n.replace('*','')+'</li>'}).join('')+'</ul><div class="ref">Arancel de referencia: '+M(A)+'</div><div class="pr">'+M(P)+'</div><div class="who">Valor final, sin cobros adicionales · Lo realiza: clínica asociada</div>'}
function plan(){rows(PL,'plb',true);var S=0,A=0;PL.forEach(function(r){var c=get(r[0]);S+=pr(c)*r[1];A+=ar(c)*r[1]});var u=v('plu')/100,pc=A*v('cFp')*u,mo=v('plmo')/100;
 var qs=Math.max(1000,R((pc/(1-mo-v('cG')/100))/12));PLQS=qs;var q=v('plqv')||qs,rev=q*12,m=rev-pc-rev*v('cG')/100,ltv=(m/12)/(v('plch')/100),cac=v('plcacv'),mg=rev?m/rev:0,ratio=cac?ltv/cac:0;
 $('plq').textContent=M(qs)+'/mes';$('plm').textContent=M(m)+' ('+Math.round(m/rev*100)+'%)';$('plltv').textContent=M(ltv);$('plcac').textContent=M(ltv/3);$('plpc').textContent=M(pc);$('pluf').textContent=(q/v('cUF')).toFixed(2).replace('.',',')+' UF';
var okm=mg>=mo,okr=ratio>=3,ok=okm&&okr&&m>0;
 light('pllight',ok,ok?'Plan rentable con esta cuota ('+M(q)+'/mes)':'Plan no rentable con esta cuota ('+M(q)+'/mes)',[(okm?'Margen '+Math.round(mg*100)+'%, sobre el objetivo de '+v('plmo')+'%':'Margen '+Math.round(mg*100)+'%, bajo el objetivo de '+v('plmo')+'%'),(okr?'Cada cliente deja '+ratio.toFixed(1).replace('.',',')+' veces lo que cuesta conseguirlo (mínimo 3)':'Cada cliente deja solo '+ratio.toFixed(1).replace('.',',')+' veces lo que cuesta conseguirlo (mínimo 3)'),'Mercado: BCI Básico $10.359 · RedSalud ≈ $10.800 · promedio individual ≈ $18.000']);
 var pgp=(q<6500||m<=0)?{lvl:'r'}:(q<8765?{lvl:'a'}:null);
 if(pgp)$('pllight').innerHTML+='<div class="pgbox '+pgp.lvl+'"><b>⚠ PG · Precio gancho.</b> '+(pgp.lvl==='r'?'La cuota está bajo el plan más barato del mercado (≈ $6.500) o deja margen negativo. <b>No conviene.</b>':'La cuota está bajo el plan de urgencia de BCI ($8.765) incluyendo más prestaciones: compites por precio en la parte más barata del mercado. <b>Revisa si es necesario.</b>')+'</div>';
 var rl=[];if(pgp)rl.push('<b>PG:</b> sube la cuota al menos a '+M(Math.max(qs,10000))+' o quita prestaciones; un plan barato atrae clientes que se van con la siguiente oferta.');if(!okm)rl.push('<b>Sube la cuota a '+M(qs)+'</b> (la sugerida para tu margen objetivo) o <b>quita una prestación</b> de las incluidas.');if(!okr)rl.push('<b>Baja el abandono</b> con pago anual (10 cuotas) y controles agendados, o <b>baja el costo por cliente</b> en Meta: con estos números puedes pagar hasta '+M(ltv/3)+' por cliente.');if(q>18000)rl.push('La cuota supera el promedio del mercado individual (≈ $18.000): <b>justifícala</b> con odontólogo de cabecera y lo incluido, o arma un nivel más simple.');if(q<10359&&ok)rl.push('Estás bajo el BCI Básico ($10.359) con más prestaciones: <b>hay espacio para subir</b> la cuota.');if(v('plu')>=90)rl.push('Con uso de '+v('plu')+'% el plan solo funciona con cuota alta: revisa la <b>activación escalonada</b> (limpieza desde el 2.º mes) y la <b>permanencia mínima</b>.');rl.push('Los tratamientos van con <b>descuento ('+v('pld')+'%)</b>, no incluidos: así el plan no se vuelve un seguro ni atrae solo a quien ya necesita tratamiento.');recs('plrec',rl);
 var fam=R(q*v('cFam')/100);
 $('plprev').innerHTML='<span class="chip">Plan dental</span><div class="t" style="margin-top:6px">'+$('pln').value+'</div><ul>'+PL.map(function(r){var c=get(r[0]);return '<li>'+r[1]+' × '+c.n.replace('*','').toLowerCase()+' al año</li>'}).join('')+'<li>'+v('pld')+'% de descuento en tratamientos</li></ul><div class="ref">Valor de lo incluido según arancel de referencia: '+M(A)+' al año</div><div class="pr">'+(q/v('cUF')).toFixed(2).replace('.',',')+' UF <span style="font-size:14px;font-weight:600">al mes (≈ '+M(q)+')</span></div><div class="who">Integrante adicional: '+M(fam)+' al mes · Pago anual: 10 cuotas</div>'}
var OPP=D.opp;
function opp(){if(!OPP.length){$('oppb').innerHTML='<tr><td colspan="7">Todavía no hay búsquedas con necesidad detectada en este período.</td></tr>';return}$('oppb').innerHTML=OPP.map(function(o,i){var S=0;o.items.forEach(function(r){S+=pr(get(r[0]))*r[1]});var P=R(S*(1-v('cD')/100));var hot=o.q>=10&&o.p/o.q<0.05;var ids=o.items.map(function(r){return r[0]}),pgo=pgCheck(ids,P,1,0);return '<tr><td><span class="chip-pk">Oportunidad</span> '+o.n+(hot?' <span class="tag">Falta producto</span>':'')+' '+pgBadge(pgo)+'</td><td class="n">'+o.q+'</td><td class="n">'+o.c+'</td><td class="n">'+o.p+'</td><td>'+o.items.map(function(r){return (r[1]>1?r[1]+'× ':'')+get(r[0]).n.replace('*','').split(' (')[0]}).join(' + ')+'</td><td class="n">'+M(P)+'</td><td><button class="btn ghost" data-o="'+i+'">Armar este pack</button></td></tr>'}).join('');
 [].forEach.call(document.querySelectorAll('#oppb button'),function(b){b.onclick=function(){var o=OPP[+b.dataset.o];PK=o.items.map(function(r){return r.slice()});$('pkn').value='Pack '+o.n;tab('pack');all()}})}
function mst(id,p){var m=MK[id];if(!m)return 'a';return p<m[0]?'b':(p>m[1]?'s':'ok')}
function mk(id,p){var m=MK[id];if(!m)return '<td class="n">—</td><td></td>';var st=mst(id,p);var t={ok:['g','Dentro del rango'],b:['a','Bajo el mercado'],s:['r','Sobre el mercado']}[st];var pgc=GANCHO.some(function(g){return g.items.length===1&&g.items[0]===id})||(st==='b')?{lvl:st==='b'?'r':'a'}:null;return '<td class="n" title="'+m[2]+'">'+M(m[0])+' – '+M(m[1])+'</td><td><span class="bdg '+t[0]+'">'+t[1]+'</span> '+pgBadge(pgc)+'</td>'}
function light(el,ok,title,reasons){$(el).innerHTML='<div class="light '+(ok?'g':'r')+'"><span class="dot"></span><div>'+title+'<ul>'+reasons.map(function(x){return '<li>'+x+'</li>'}).join('')+'</ul></div></div>'}
function recs(el,list){$(el).innerHTML=list.length?list.map(function(x){return '<li>'+x+'</li>'}).join(''):'<li>Sin ajustes necesarios con estos supuestos.</li>'}
function recall(){var out=0,list=[];C.forEach(function(c){if(mst(c.id,pr(c))==='s')out++});
 list.push('<b>Precios:</b> parte del arancel UCO como referencia y ajusta el factor público hasta que cada prestación quede «Dentro del rango» del mercado'+(out?' (hoy hay '+out+' sobre el mercado).':'.'));
 list.push('<b>Comisión:</b> escalonada según lo que aporta Prevsoul: atenciones '+v('cS')+'%, packs '+v('cP')+'%, planes con factor clínica '+String(v('cFp')).replace('.',',')+' del arancel por prestación usada.');
 list.push('<b>Prioridad comercial:</b> los planes sostienen la marca; las atenciones y packs son la entrada. Invierte en Meta solo en planes con semáforo verde.');
 list.push('<b>Percepción:</b> montos redondos, arancel de referencia visible y planes en UF; nunca «oferta» ni terminaciones en 9.');
 list.push('<b>No competir con campañas gancho:</b> hay redes que publican diagnóstico + radiografías + limpieza a $9.900; Prevsoul compite con odontólogo de cabecera y transparencia, no con el precio más bajo.');
 list.push('<b>Riesgo:</b> prevención incluida, tratamientos con descuento, activación escalonada y permanencia mínima.');
 list.push('<b>Legal:</b> presentar los planes como «plan de prestaciones», no como seguro (los seguros requieren autorización de la CMF).');
 list.push('<b>Sigla ⚠ PG (precio gancho):</b> aparece en el Armador cuando el mercado tiene campañas de entrada muy bajas en esa categoría (ámbar: no compitas por precio) o cuando tu propio precio cae en zona de liquidación (rojo: no conviene).');
 list.push('<b>Productos dentales:</b> fuera por ahora (decisión del 15-nov-2026).');
 recs('recall',list)}
var COL=['#1D5FD1','#7CC4F2','#D63384','#0B2545','#C3CBD6'];
function perf(){var P=D.perf,tp=0,tn=0,meta=0,gasto=v('pmeta');$('perfempty').style.display=P.length?'none':'';
 P.forEach(function(x){if(x.t==='pack')tp+=x.v;meta+=x.mixn[0]});
 var est=function(x){var g=v('cG')/100,ci=document.getElementById('cI').checked?1.19:1;return x.t==='plan'?x.ing*(v('plmo')/100):x.ing*((x.t==='pack'?v('cP'):v('cS'))/100/ci-g)};
 $('perfb').innerHTML=P.map(function(x){var neto=est(x);tn+=neto;var mg=x.ing?neto/x.ing:0,ok=x.t==='plan'?true:mg>=0.15,tot=x.mixn.reduce(function(a,b){return a+b},0)||1;
  var pgx=(x.t==='plan'&&x.pr&&x.pr<8765)?'<span class="pg a" title="Bajo el plan de urgencia de BCI">⚠ PG</span>':'';
  return '<tr><td>'+x.n+' <span class="vs">'+({pack:'pack',plan:'plan',at:'atención'}[x.t])+'</span>'+(x.op?' <span class="chip-pk">Nació de una oportunidad</span>':'')+'</td><td class="n">'+x.v+'</td><td class="n">'+M(x.ing)+'</td><td class="n">'+M(neto)+'</td><td class="n">'+Math.round(mg*100)+'%</td><td><div class="mix">'+x.mixn.map(function(c,i){return '<span style="width:'+(c/tot*100)+'%;background:'+COL[i]+'" title="'+c+'"></span>'}).join('')+'</div></td><td class="n">'+x.bus+'</td><td class="n">'+x.pre+'</td><td><span class="dotc '+(ok?'g':'r')+'"></span> '+(ok?'Rentable':'Revisar')+' '+pgx+'</td></tr>'}).join('');
 var cac=meta?gasto/meta:0;$('kp1').textContent=tp;$('kp2').textContent=D.plans.reduce(function(a,b){return a+b.act},0);$('kp3').textContent=M(tn);$('kp4').textContent=meta&&gasto?M(cac):'—';
 $('perfpl').innerHTML=D.plans.length?D.plans.map(function(x){var ch=x.ch!==null?x.ch:v('plch'),m=(x.pr*12*(1-v('cG')/100))*(v('plmo')/100),ltv=ch>0?(m/12)/(ch/100):0,ok=ch<5&&(!cac||ltv>=3*cac);return '<tr><td>'+x.n+'</td><td class="n">'+x.act+'</td><td class="n">'+(x.ch!==null?String(x.ch).replace('.',',')+'%':'—')+' <span class="vs">(supuesto '+v('plch')+'%)</span></td><td class="n">'+M(ltv)+'</td><td><span class="dotc '+(ok?'g':'r')+'"></span> '+(ok?'Rentable':'No rentable')+'</td></tr>'}).join(''):'<tr><td colspan="5">Todavía no hay planes pagados.</td></tr>';
 $('perfab').innerHTML=D.ab.map(function(a){return '<tr><td>'+a.n+'</td><td class="n">'+a.s+'</td><td class="n">'+M(a.ing)+'</td><td class="n">'+(a.s&&gasto?M(gasto/2/a.s):'—')+'</td></tr>'}).join('')}
function all(){cat();pack();plan();opp();recall();perf()}
function tab(t){try{history.replaceState(null,'','#'+t)}catch(e){}[].forEach.call(document.querySelectorAll('.tabs button'),function(b){b.classList.toggle('on',b.dataset.t===t)});[].forEach.call(document.querySelectorAll('.pane'),function(p){p.classList.toggle('on',p.id===t)})}
[].forEach.call(document.querySelectorAll('.tabs button'),function(b){b.onclick=function(){tab(b.dataset.t)}});
$('pmeta').value=D.meta||0;$('perfper').innerHTML=D.pills;$('pmeta').addEventListener('input',perf);var PLQS=0;$('plqs').onclick=function(){$('plqv').value=PLQS;all()};$('pkaddb').onclick=function(){PK.push([$('pkadd').value,1]);all()};$('pladdb').onclick=function(){PL.push([$('pladd').value,1]);all()};
[].forEach.call(document.querySelectorAll('#cfg input,#cfg select,#plan input[type=number],#pkn,#pln,#plqv,#plcacv'),function(el){el.addEventListener('input',all)});
(function(){var c=D.cfg;['cU','cS','cP','cD','cFp','cFam','cG','cUF','plu','plch','plmo'].forEach(function(k){if(c[k]!==undefined&&$(k))$(k).value=c[k]});$('cI').checked=!!c.cI;$('cR').value=String(c.cR||1000)})();
$('psarmf').addEventListener('submit',function(){var c={};['cU','cS','cP','cD','cFp','cFam','cG','cUF','plu','plch','plmo','cR'].forEach(function(k){c[k]=v(k)});c.cI=$('cI').checked?1:0;var f={};C.forEach(function(x){f[x.id]=x.f});$('psarmd').value=JSON.stringify({cfg:c,f:f,meta:v('pmeta')})});
opts();all();

if(location.hash&&document.getElementById(location.hash.slice(1)))tab(location.hash.slice(1));

JS
);

function ps_armador_page() {
	$d     = function_exists( 'ps_periodo_actual' ) ? ps_periodo_actual() : 30;
	$since = function_exists( 'ps_periodo_inicio' ) ? ps_periodo_inicio( $d ) : time() - 30 * DAY_IN_SECONDS;
	$o     = get_option( 'ps_arm', array() );
	$cat   = ps_arm_catalogo();
	foreach ( $cat as $i => $c ) {
		if ( isset( $o['f'][ $c['id'] ] ) ) {
			$cat[ $i ]['f'] = (float) $o['f'][ $c['id'] ];
		}
	}
	$data = array_merge(
		ps_arm_datos( $since, time() ),
		array(
			'cat'    => $cat,
			'mk'     => ps_arm_mercado(),
			'gancho' => ps_arm_gancho(),
			'cfg'    => ps_arm_cfg(),
			'meta'   => isset( $o['meta'][ $d ] ) ? (float) $o['meta'][ $d ] : 0,
			'pills'  => function_exists( 'ps_periodo_pills' ) ? ps_periodo_pills( 'ps-armador' ) : '',
		)
	);
	$form = str_replace( array( '__ACTION__', '__NONCE__' ), array( esc_url( admin_url( 'admin-post.php' ) ), wp_nonce_field( 'ps_arm_save', '_wpnonce', true, false ) . '<input type="hidden" name="dias" value="' . (int) $d . '">' ), PS_ARM_BODY );
	echo ( function_exists( 'ps_admin_css' ) ? ps_admin_css() : '' ) . '<style>' . PS_ARM_CSS . '</style><div class="wrap" id="psarm"><h1>Armador de packs y planes</h1>';
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Factores y ajustes guardados.</p></div>';
	}
	echo $form . '</div><script>window.PSARM=' . wp_json_encode( $data ) . ';</script><script>' . PS_ARM_JS . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

add_action(
	'admin_post_ps_arm_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_arm_save' ) ) {
			wp_die( 'No permitido' );
		}
		$in  = json_decode( wp_unslash( $_POST['data'] ?? '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$d   = isset( $_POST['dias'] ) ? (int) $_POST['dias'] : 30;
		$o   = get_option( 'ps_arm', array() );
		$ids = wp_list_pluck( ps_arm_catalogo(), 'id' );
		if ( is_array( $in ) ) {
			$cfg = array();
			foreach ( array_keys( ps_arm_cfg() ) as $k ) {
				if ( isset( $in['cfg'][ $k ] ) && is_numeric( $in['cfg'][ $k ] ) ) {
					$cfg[ $k ] = (float) $in['cfg'][ $k ];
				}
			}
			$o['cfg'] = $cfg;
			foreach ( (array) ( $in['f'] ?? array() ) as $id => $f ) {
				if ( in_array( $id, $ids, true ) && is_numeric( $f ) ) {
					$o['f'][ $id ] = max( 0.05, min( 1.5, (float) $f ) );
				}
			}
			if ( isset( $in['meta'] ) && is_numeric( $in['meta'] ) ) {
				$o['meta'][ $d ] = max( 0, (float) $in['meta'] );
			}
			update_option( 'ps_arm', $o, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ps-armador&dias=' . $d . '&ok=1' ) );
		exit;
	}
);
