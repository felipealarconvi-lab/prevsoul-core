<?php
/**
 * Agenda Prevsoul en el inicio y horas ocupadas por el prestador.
 *
 * - [ps_agenda_semana]: bloque «Las horas se están tomando» para la portada. Muestra los próximos
 *   días de un prestador: horas tomadas en Prevsoul (azul, «PS»), horas disponibles (blanco, hora
 *   en azul) y horas no disponibles (gris). Solo usa datos reales.
 * - Liberación por tandas: el paciente ve abiertas solo las primeras 3 horas libres de cada día;
 *   cuando se toma una, se abre la siguiente (ps_ag_abiertas en agenda.php).
 * - «Ocupar horas»: el prestador marca las horas en que atiende pacientes de fuera de Prevsoul.
 *   Esas horas dejan de ofrecerse y el paciente las ve como «no disponible».
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

/** Candado de línea (SVG), para no depender de fuentes de íconos. */
function ps_agw_candado( $color = '#8A97A8' ) {
	return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';
}

/** Hora corta para mostrar: '09:00' → '9'. */
function ps_agw_h( $h ) {
	return (string) (int) substr( $h, 0, 2 );
}

/** Prestador que se muestra en el inicio: el elegido en Ajustes o el primero con agenda en línea activa. */
function ps_agw_prestador() {
	$sel = (int) get_option( 'ps_ag_inicio_prov', 0 );
	if ( $sel && function_exists( 'ps_ag_activa' ) && ps_ag_activa( $sel ) && 'publish' === get_post_status( $sel ) ) {
		return $sel;
	}
	$ids = get_posts( array( 'post_type' => array( 'ps_clinica', 'ps_odontologo' ), 'post_status' => 'publish', 'numberposts' => 50, 'fields' => 'ids', 'meta_key' => '_ps_ag', 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	foreach ( $ids as $id ) {
		if ( ps_ag_activa( $id ) ) {
			return (int) $id;
		}
	}
	return 0;
}

/** Próximos días (hasta $n) que todavía se pueden reservar (respeta la anticipación mínima). */
function ps_agw_dias( $prov, $n = 5 ) {
	$tz  = wp_timezone();
	$d   = new DateTimeImmutable( 'today', $tz );
	$min = time() + max( 0, (int) ps_ag_cfg( $prov )['ant'] ) * HOUR_IN_SECONDS;
	$out = array();
	for ( $i = 0; $i < 21 && count( $out ) < $n; $i++ ) {
		$ymd = $d->modify( "+$i days" )->format( 'Y-m-d' );
		foreach ( ps_ag_slots_dia( $prov, $ymd ) as $h ) {
			$t = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $h, $tz );
			if ( $t && $t->getTimestamp() >= $min ) {
				$out[] = $ymd;
				break;
			}
		}
	}
	return $out;
}

/** Estilos del bloque (una sola vez por página). */
function ps_agw_css() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style>
.ps-agw{background:#EAF1FC;border-radius:28px;padding:28px 24px;display:flex;flex-direction:column;gap:16px;max-width:880px;margin:0 auto;color:#0B2545}
.ps-agw-eye{margin:0;font-size:13px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#167A64}
.ps-agw-tit{margin:0;font-size:clamp(24px,4vw,34px);font-weight:800;line-height:1.15;letter-spacing:-.5px;color:#0B2545}
.ps-agw-box{background:#fff;border-radius:18px;padding:6px 16px}
.ps-agw-dia{padding:12px 0}
.ps-agw-dia+.ps-agw-dia{border-top:1px solid #D9E3F2}
.ps-agw-top{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px}
.ps-agw-top b{font-size:15px}
.ps-agw-q{font-size:13px;font-weight:700;color:#1D5FD1}
.ps-agw-q.poco{color:#7A5300}
.ps-agw-q.lleno{color:#4A5B73}
.ps-agw-fila{display:flex;gap:3px}
.ps-agw-c{flex:1;min-width:0;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;box-sizing:border-box}
.ps-agw-c.lib{background:#fff;border:1.5px solid #1D5FD1;color:#1D5FD1}
.ps-agw-c.ps{background:#1D5FD1;color:#fff}
.ps-agw-c.no{background:#DDE3EB}
.ps-agw-ley{display:flex;flex-wrap:wrap;gap:14px;justify-content:center;font-size:13px;color:#4A5B73}
.ps-agw-ley i{display:inline-block;width:13px;height:13px;border-radius:4px;vertical-align:-2px;margin-right:4px;box-sizing:border-box}
.ps-agw .ps-btn{align-self:center}
@media(max-width:560px){.ps-agw{padding:22px 14px;border-radius:22px}.ps-agw-box{padding:4px 10px}.ps-agw-fila{gap:2px}.ps-agw-c{height:26px;font-size:11px;border-radius:5px}}
</style>';
}

/** [ps_agenda_semana prestador="" dias="5" url=""] */
add_shortcode(
	'ps_agenda_semana',
	function ( $atts ) {
		$a    = shortcode_atts( array( 'prestador' => 0, 'dias' => 5, 'url' => '/atencion-dental/' ), $atts );
		$prov = (int) $a['prestador'];
		if ( ! function_exists( 'ps_ag_slots_dia' ) ) {
			return '';
		}
		if ( ! $prov || ! ps_ag_activa( $prov ) ) {
			$prov = ps_agw_prestador();
		}
		if ( ! $prov ) {
			return current_user_can( 'manage_options' ) ? '<p class="ps-muted ps-small" style="text-align:center">[Solo lo ves tú] El bloque «Las horas se están tomando» aparecerá cuando una clínica u odontólogo active «Recibir reservas en línea» en su agenda.</p>' : '';
		}
		$dias = ps_agw_dias( $prov, max( 1, min( 7, (int) $a['dias'] ) ) );
		if ( ! $dias ) {
			return '';
		}
		$out = ps_agw_css() . '<div class="ps-agw" aria-label="Disponibilidad de horas Prevsoul"><p class="ps-agw-eye">Agenda Prevsoul · próximos días</p><h2 class="ps-agw-tit">Las horas se están tomando</h2><div class="ps-agw-box">';
		foreach ( $dias as $ymd ) {
			$tom   = ps_ag_ocupadas( $prov, $ymd );
			$abier = ps_ag_abiertas( $prov, $ymd );
			$n     = count( $abier );
			$q     = 0 === $n ? array( 'Completo', 'lleno' ) : array( 'Quedan ' . $n, $n <= 2 ? 'poco' : '' );
			$out  .= '<div class="ps-agw-dia"><div class="ps-agw-top"><b>' . esc_html( ucfirst( ps_ag_fecha( $ymd, 'D j' ) ) ) . '</b><span class="ps-agw-q ' . esc_attr( $q[1] ) . '">' . esc_html( $q[0] ) . '</span></div><div class="ps-agw-fila">';
			foreach ( ps_ag_slots_dia( $prov, $ymd ) as $h ) {
				$rango = $h . '–' . gmdate( 'H:i', strtotime( '2000-01-01 ' . $h ) + PS_AG_DUR * 60 );
				if ( in_array( $h, $tom, true ) ) {
					$out .= '<span class="ps-agw-c ps" title="' . esc_attr( $rango . ' · tomada en Prevsoul' ) . '">PS</span>';
				} elseif ( in_array( $h, $abier, true ) ) {
					$out .= '<span class="ps-agw-c lib" title="' . esc_attr( $rango . ' · disponible' ) . '">' . esc_html( ps_agw_h( $h ) ) . '</span>';
				} else {
					$out .= '<span class="ps-agw-c no" title="' . esc_attr( $rango . ' · no disponible' ) . '"></span>';
				}
			}
			$out .= '</div></div>';
		}
		$out .= '</div><div class="ps-agw-ley"><span><i style="border:1.5px solid #1D5FD1;background:#fff"></i>Disponible</span><span><i style="background:#1D5FD1"></i>Tomada</span><span><i style="background:#DDE3EB"></i>No disponible</span></div>';
		$out .= '<a class="ps-btn" href="' . esc_url( home_url( $a['url'] ) ) . '">Ver horas disponibles</a></div>';
		return $out;
	}
);

/* ---------- Panel del prestador: «Ocupar horas» ---------- */

function ps_ag_ocupar_html( $prov ) {
	if ( ! ps_ag_activa( $prov ) ) {
		return '';
	}
	$tz   = wp_timezone();
	$off  = isset( $_GET['osem'] ) ? max( 0, min( 8, (int) $_GET['osem'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$ini  = ( new DateTimeImmutable( 'monday this week', $tz ) )->modify( ( 7 * $off ) . ' days' );
	$now  = ( new DateTimeImmutable( 'now', $tz ) )->getTimestamp();
	$blq  = ps_ag_bloqueos( $prov );
	$dias = array();
	$hrs  = array();
	for ( $i = 0; $i < 7; $i++ ) {
		$ymd   = $ini->modify( "+$i days" )->format( 'Y-m-d' );
		$slots = ps_ag_slots_dia( $prov, $ymd );
		if ( $slots ) {
			$dias[ $ymd ] = array( 'slots' => $slots, 'tom' => ps_ag_ocupadas( $prov, $ymd ), 'abier' => ps_ag_abiertas( $prov, $ymd ) );
			$hrs          = array_merge( $hrs, $slots );
		}
	}
	$hrs  = array_values( array_unique( $hrs ) );
	sort( $hrs );
	$base = remove_query_arg( array( 'osem', 'ps_ok' ) );
	$out  = '<style>.ps-ocu{overflow-x:auto}.ps-ocu table{border-collapse:separate;border-spacing:4px;min-width:520px;width:100%}.ps-ocu th{font-size:12px;font-weight:700;color:#0B2545;text-align:center}.ps-ocu th.h{text-align:left;font-weight:600;color:#4A5B73;white-space:nowrap}.ps-ocu button,.ps-ocu span.c{width:100%;height:32px;border-radius:8px;font:inherit;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;box-sizing:border-box}.ps-ocu button{cursor:pointer}.ps-ocu .lib{background:#fff;border:1.5px solid #1D5FD1;color:#1D5FD1}.ps-ocu .ps{background:#1D5FD1;color:#fff;border:0}.ps-ocu .no{background:#DDE3EB;border:0;color:#8A97A8}.ps-ocu .vac{background:transparent;border:0}</style>';
	$out .= '<section class="ps-card" id="ocupar-horas"><div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap"><h3 class="ps-card-title" style="margin:0">Ocupar horas</h3><span>' . ( $off > 0 ? '<a class="ps-chip" href="' . esc_url( add_query_arg( 'osem', $off - 1, $base ) . '#ocupar-horas' ) . '">← Anterior</a> ' : '' ) . '<a class="ps-chip' . ( 0 === $off ? ' ps-acc' : '' ) . '" href="' . esc_url( $base . '#ocupar-horas' ) . '">Esta semana</a> <a class="ps-chip" href="' . esc_url( add_query_arg( 'osem', $off + 1, $base ) . '#ocupar-horas' ) . '">Siguiente →</a></span></div>';
	$out .= '<p class="ps-muted ps-small">Toca una hora para ocuparla con un paciente tuyo de fuera de Prevsoul (queda gris con candado). Tócala otra vez para liberarla. Los pacientes la ven como «no disponible» y nunca se muestra quién la ocupa. Prevsoul abre ' . (int) ps_ag_tanda() . ' horas por día; cuando se toma una, se abre la siguiente.</p>';
	if ( ! $dias ) {
		return $out . '<p class="ps-muted ps-small">No tienes horas definidas esta semana. Revisa «Mi agenda Prevsoul».</p></section>';
	}
	$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ps-ocu">' . wp_nonce_field( 'ps_ag_ocup_' . $prov, '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_ag_ocupar"><input type="hidden" name="prov" value="' . (int) $prov . '"><input type="hidden" name="back" value="' . esc_attr( add_query_arg( 'osem', $off, $base ) ) . '"><table><thead><tr><th></th>';
	foreach ( array_keys( $dias ) as $ymd ) {
		$out .= '<th>' . esc_html( ucfirst( ps_ag_fecha( $ymd, 'D j' ) ) ) . '</th>';
	}
	$out .= '</tr></thead><tbody>';
	foreach ( $hrs as $h ) {
		$fin  = gmdate( 'H:i', strtotime( '2000-01-01 ' . $h ) + PS_AG_DUR * 60 );
		$out .= '<tr><th class="h" scope="row">' . esc_html( ps_agw_h( $h ) . ':00–' . ps_agw_h( $h ) . ':45' ) . '</th>';
		foreach ( $dias as $ymd => $d ) {
			$k    = $ymd . ' ' . $h;
			$ts   = ( DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $k, $tz ) )->getTimestamp();
			$lbl  = ucfirst( ps_ag_fecha( $ymd, 'l j' ) ) . ', ' . $h . '–' . $fin;
			$out .= '<td>';
			if ( ! in_array( $h, $d['slots'], true ) ) {
				$out .= '<span class="c vac"></span>';
			} elseif ( in_array( $h, $d['tom'], true ) ) {
				$out .= '<span class="c ps" title="' . esc_attr( $lbl . ' · paciente Prevsoul' ) . '">PS</span>';
			} elseif ( ! empty( $blq[ $k ] ) ) {
				$out .= '<button type="submit" name="t" value="' . esc_attr( $k ) . '" class="no" aria-label="' . esc_attr( 'Liberar ' . $lbl ) . '" title="Liberar">' . ps_agw_candado() . '</button>';
			} elseif ( $ts <= $now ) {
				$out .= '<span class="c no"></span>';
			} elseif ( in_array( $h, $d['abier'], true ) ) {
				$out .= '<button type="submit" name="t" value="' . esc_attr( $k ) . '" class="lib" aria-label="' . esc_attr( 'Ocupar ' . $lbl ) . '" title="Abierta a pacientes · tocar para ocupar">' . esc_html( ps_agw_h( $h ) . ':00' ) . '</button>';
			} else {
				$out .= '<button type="submit" name="t" value="' . esc_attr( $k ) . '" class="no" aria-label="' . esc_attr( 'Ocupar ' . $lbl ) . '" title="En espera · tocar para ocupar"></button>';
			}
			$out .= '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</tbody></table></form>';
	$out .= '<p class="ps-small ps-muted" style="display:flex;gap:14px;flex-wrap:wrap"><span><span class="ps-chip" style="background:#1D5FD1;color:#fff">PS</span> Paciente Prevsoul</span><span><span class="ps-chip" style="background:#fff;border:1.5px solid #1D5FD1;color:#1D5FD1">9:00</span> Abierta a pacientes</span><span><span class="ps-chip" style="background:#DDE3EB">' . ps_agw_candado() . '</span> Ocupada por ti</span><span><span class="ps-chip" style="background:#DDE3EB">&nbsp;&nbsp;</span> En espera o pasada</span></p>';
	return $out . '</section>';
}

add_action(
	'admin_post_ps_ag_ocupar',
	function () {
		$prov = isset( $_POST['prov'] ) ? absint( $_POST['prov'] ) : 0;
		if ( ! $prov || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_ag_ocup_' . $prov ) || ! ps_is_prestador_post( $prov ) ) {
			wp_die( 'No permitido.' );
		}
		if ( ps_user_prestador() !== $prov && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Esta agenda no corresponde a tu cuenta.' );
		}
		$t = isset( $_POST['t'] ) ? sanitize_text_field( wp_unslash( $_POST['t'] ) ) : '';
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})$/', $t, $m ) && in_array( $m[2], ps_ag_slots_dia( $prov, $m[1] ), true ) ) {
			$blq = ps_ag_bloqueos( $prov );
			if ( ! empty( $blq[ $t ] ) ) {
				unset( $blq[ $t ] );
			} elseif ( ! in_array( $m[2], ps_ag_ocupadas( $prov, $m[1] ), true ) ) {
				$blq[ $t ] = 1;
			}
			$hoy = ( new DateTimeImmutable( 'today', wp_timezone() ) )->format( 'Y-m-d' );
			foreach ( array_keys( $blq ) as $k ) {
				if ( substr( $k, 0, 10 ) < $hoy ) {
					unset( $blq[ $k ] );
				}
			}
			update_post_meta( $prov, '_ps_ag_ocup', $blq );
		}
		$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : ps_panel_url();
		wp_safe_redirect( $back . '#ocupar-horas' );
		exit;
	}
);

/* 2.41.0: el inicio ya publicado suma el bloque «Las horas se están tomando» después del buscador (una sola vez). */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_ag_inicio_v1' ) ) {
			return;
		}
		update_option( 'ps_ag_inicio_v1', 1 );
		$page = get_page_by_path( 'inicio' );
		if ( ! $page ) {
			$front = (int) get_option( 'page_on_front' );
			$page  = $front ? get_post( $front ) : null;
		}
		if ( ! $page || false !== strpos( $page->post_content, '[ps_agenda_semana' ) ) {
			return;
		}
		$c     = $page->post_content;
		$nuevo = ps_sc( '[ps_seccion fondo="blanco"][ps_agenda_semana][/ps_seccion]' );
		if ( preg_match( '#<!-- wp:shortcode -->\[ps_seccion[^\]]*\]\[ps_buscador[^\]]*\]\[/ps_seccion\]<!-- /wp:shortcode -->#', $c, $b ) ) {
			$c = str_replace( $b[0], $b[0] . $nuevo, $c );
		} else {
			$c .= $nuevo;
		}
		wp_update_post( array( 'ID' => $page->ID, 'post_content' => $c ) );
	},
	70
);
