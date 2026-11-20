<?php
/**
 * Valoraciones verificadas: solo quien compró y se atendió valora, desde Mi cuenta → Mis atenciones.
 * Tarjetas de producto clickeables completas (sin botón «Ver y valorar»).
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Tarjeta clickeable: tocar la tarjeta abre el producto (los botones siguen funcionando).
 * ---------------------------------------------------------------------- */
add_action(
	'wp_footer',
	function () {
		?>
<style>.ps-card.ps-product{cursor:pointer;transition:box-shadow .15s,transform .15s}.ps-card.ps-product:hover{box-shadow:0 8px 22px rgba(11,37,69,.12);transform:translateY(-2px)}</style>
<script>document.addEventListener('click',function(e){var c=e.target.closest('.ps-card.ps-product');if(!c||e.target.closest('a,button,input,select,label,textarea'))return;var a=c.querySelector('.ps-card-title a');if(a){if(e.ctrlKey||e.metaKey){window.open(a.href,'_blank')}else{location.href=a.href}}});</script>
		<?php
	}
);

/* -------------------------------------------------------------------------
 * Órdenes Prevsoul del usuario actual.
 * ---------------------------------------------------------------------- */
function ps_val_es_mia( $oid, $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	if ( ! $uid || 'ps_orden' !== get_post_type( $oid ) ) {
		return false;
	}
	$u = get_userdata( $uid );
	if ( $u && $u->user_email && 0 === strcasecmp( (string) get_post_meta( $oid, '_ps_email', true ), $u->user_email ) ) {
		return true;
	}
	$o = function_exists( 'wc_get_order' ) ? wc_get_order( (int) get_post_meta( $oid, '_ps_pedido', true ) ) : null;
	return $o && (int) $o->get_customer_id() === (int) $uid;
}

function ps_val_mis_ordenes( $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	$u   = get_userdata( $uid );
	if ( ! $u ) {
		return array();
	}
	$ids = get_posts(
		array(
			'post_type'   => 'ps_orden',
			'numberposts' => 100,
			'fields'      => 'ids',
			'meta_key'    => '_ps_email', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $u->user_email, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( function_exists( 'wc_get_orders' ) ) {
		$peds = wc_get_orders( array( 'customer_id' => $uid, 'limit' => 100, 'return' => 'ids' ) );
		if ( $peds ) {
			$ids = array_merge(
				$ids,
				get_posts(
					array(
						'post_type'   => 'ps_orden',
						'numberposts' => 200,
						'fields'      => 'ids',
						'meta_query'  => array( array( 'key' => '_ps_pedido', 'value' => $peds, 'compare' => 'IN' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
					)
				)
			);
		}
	}
	$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
	usort(
		$ids,
		function ( $a, $b ) {
			return get_post_time( 'U', true, $b ) <=> get_post_time( 'U', true, $a );
		}
	);
	return $ids;
}

/** ¿Esta orden se puede valorar ahora? (realizada, propia y sin valorar). */
function ps_val_puede( $oid ) {
	return ps_val_es_mia( $oid )
		&& 'realizada' === get_post_meta( $oid, '_ps_estado', true )
		&& ! get_post_meta( $oid, '_ps_valorada', true )
		&& (int) get_post_meta( $oid, '_ps_product_id', true ) > 0;
}

/* -------------------------------------------------------------------------
 * Mi cuenta → Mis atenciones
 * ---------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		add_rewrite_endpoint( 'mis-atenciones', EP_ROOT | EP_PAGES );
		if ( get_option( 'ps_misaten_flush' ) !== '1' ) {
			flush_rewrite_rules( false );
			update_option( 'ps_misaten_flush', '1' );
		}
	}
);
add_filter(
	'woocommerce_get_query_vars',
	function ( $v ) {
		$v['mis-atenciones'] = 'mis-atenciones';
		return $v;
	}
);
add_filter(
	'woocommerce_account_menu_items',
	function ( $items ) {
		$new = array();
		foreach ( $items as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'dashboard' === $k ) {
				$new['mis-atenciones'] = 'Mis atenciones';
			}
		}
		return isset( $new['mis-atenciones'] ) ? $new : array( 'mis-atenciones' => 'Mis atenciones' ) + $items;
	},
	100
);
add_filter(
	'woocommerce_endpoint_mis-atenciones_title',
	function () {
		return 'Mis atenciones';
	}
);

function ps_val_estrellas( $n ) {
	$n = max( 0, min( 5, (int) $n ) );
	return '<span style="color:#E8A317;letter-spacing:1px">' . str_repeat( '★', $n ) . '<span style="color:#D9E3F2">' . str_repeat( '★', 5 - $n ) . '</span></span>';
}

function ps_val_url() {
	return function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'mis-atenciones' ) : home_url( '/' );
}

add_action(
	'woocommerce_account_mis-atenciones_endpoint',
	function () {
		$ids = ps_val_mis_ordenes();
		echo '<style>.ps-ma{border:1px solid #D9E3F2;border-radius:14px;overflow:hidden;background:#fff}.ps-ma-r{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-top:1px solid #EEF2F8}.ps-ma-r:first-child{border-top:0}.ps-ma-m{font-size:13px;color:#4A5B73}.ps-ma-ok{font-size:12px;background:#E6F7F2;color:#167A64;border-radius:999px;padding:3px 10px;white-space:nowrap}.ps-ma-f{padding:12px 0 0;display:none}.ps-ma-f.on{display:block}.ps-ma-st{display:flex;gap:2px;flex-direction:row-reverse;justify-content:flex-end}.ps-ma-st input{display:none}.ps-ma-st label{font-size:28px;color:#D9E3F2;cursor:pointer;line-height:1}.ps-ma-st input:checked~label,.ps-ma-st label:hover,.ps-ma-st label:hover~label{color:#E8A317}.ps-ma-f textarea{width:100%;min-height:70px;border:1px solid #D9E3F2;border-radius:8px;padding:8px;font:inherit}</style>';
		if ( isset( $_GET['ps_val'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<p class="ps-ma-ok" style="display:inline-block;margin-bottom:12px">¡Gracias por tu valoración!</p>';
		}
		if ( ! $ids ) {
			echo '<div class="ps-cu-card ps-cu-at"><span>Aún no tienes atenciones Prevsoul.</span><a class="ps-btn" href="' . esc_url( home_url( '/atencion-dental/' ) ) . '">Ver atenciones</a></div>';
			return;
		}
		$est = array(
			'recibida'   => 'Por agendar',
			'agendada'   => 'Agendada',
			'realizada'  => 'Realizada',
			'no_asistio' => 'No asististe',
			'cancelada'  => 'Cancelada',
		);
		$chips = array(
			'recibida'   => array( '#FFF4E0', '#7A5300' ),
			'agendada'   => array( '#EAF1FC', '#164BA8' ),
			'realizada'  => array( '#E6F7F2', '#167A64' ),
			'no_asistio' => array( '#F0F4FA', '#4A5B73' ),
			'cancelada'  => array( '#F0F4FA', '#4A5B73' ),
		);
		foreach ( $ids as $oid ) {
			$e    = (string) get_post_meta( $oid, '_ps_estado', true );
			$pid  = (int) get_post_meta( $oid, '_ps_product_id', true );
			$cita = (string) get_post_meta( $oid, '_ps_cita', true );
			$fec  = 'recibida' === $e || ! $cita ? 'Compra del ' . get_the_date( 'j M Y', $oid ) : ucfirst( ps_fmt_date( $cita ) );
			$cc   = $chips[ $e ] ?? $chips['cancelada'];
			$chip = '<span class="ps-cu-chip" style="background:' . $cc[0] . ';color:' . $cc[1] . '">' . esc_html( $est[ $e ] ?? ucfirst( $e ) ) . '</span>';
			$btn  = '';
			$form = '';
			$vid  = (int) get_post_meta( $oid, '_ps_valorada', true );
			if ( $vid ) {
				$btn = '<span class="ps-cu-mu">Valorada ' . ps_val_estrellas( get_comment_meta( $vid, 'rating', true ) ) . '</span>';
			} elseif ( ps_val_puede( $oid ) ) {
				$btn   = '<button type="button" class="ps-btn ps-btn-ghost" onclick="this.style.display=\'none\';document.getElementById(\'psv' . (int) $oid . '\').classList.add(\'on\')">Valorar</button>';
				$stars = '';
				for ( $i = 5; $i >= 1; $i-- ) {
					$stars .= '<input type="radio" id="psv' . (int) $oid . 's' . $i . '" name="rating" value="' . $i . '" required><label for="psv' . (int) $oid . 's' . $i . '" title="' . $i . '">★</label>';
				}
				$form = '<form class="ps-ma-f" id="psv' . (int) $oid . '" method="post" action="' . esc_url( site_url( '/wp-comments-post.php' ) ) . '">'
					. '<div class="ps-ma-st">' . $stars . '</div>'
					. '<p><textarea name="comment" placeholder="¿Cómo te fue? (opcional)"></textarea></p>'
					. '<p class="ps-ma-m"><label><input type="checkbox" name="ps_testimonio_ok" value="1"> Autorizo a Prevsoul a mostrar mi reseña como testimonio, con mi nombre y la inicial de mi apellido.</label></p>'
					. '<input type="hidden" name="comment_post_ID" value="' . (int) $pid . '"><input type="hidden" name="ps_orden" value="' . (int) $oid . '">'
					. wp_nonce_field( 'ps_val_' . $oid, 'ps_val_nonce', true, false )
					. '<button class="ps-btn" type="submit">Enviar valoración</button></form>';
			} elseif ( in_array( $e, array( 'recibida', 'agendada' ), true ) && function_exists( 'ps_ag_url' ) ) {
				$btn = '<a class="ps-btn' . ( 'agendada' === $e ? ' ps-btn-ghost' : '' ) . '" href="' . esc_url( ps_ag_url( $oid ) ) . '">' . ( 'agendada' === $e ? 'Ver o cambiar hora' : 'Elegir hora' ) . '</a>';
			}
			$ico = function_exists( 'ps_cu_svg' ) ? '<span class="ps-cu-ico' . ( 'realizada' === $e ? '' : ' az' ) . '">' . ps_cu_svg( 'realizada' === $e ? 'check' : ( 'recibida' === $e ? 'cal' : 'tooth' ) ) . '</span>' : '';
			echo '<div class="ps-cu-card"><div class="ps-cu-at"><div class="ps-cu-l">' . $ico . '<div><strong>' . esc_html( get_post_meta( $oid, '_ps_producto', true ) ) . '</strong><br><span class="ps-cu-mu">' . esc_html( $fec ) . '</span></div></div><div class="ps-cu-der">' . $chip . $btn . '</div></div>' . $form . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
);

/* -------------------------------------------------------------------------
 * Solo se aceptan valoraciones de producto que vienen de Mis atenciones.
 * ---------------------------------------------------------------------- */
add_filter(
	'preprocess_comment',
	function ( $data ) {
		$pid = (int) ( $data['comment_post_ID'] ?? 0 );
		if ( 'product' !== get_post_type( $pid ) || is_admin() ) {
			return $data;
		}
		if ( ! empty( $data['comment_parent'] ) && current_user_can( 'moderate_comments' ) ) {
			return $data; // Respuesta de Prevsoul a una reseña.
		}
		$oid = isset( $_POST['ps_orden'] ) ? absint( $_POST['ps_orden'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$ok  = $oid
			&& isset( $_POST['ps_val_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ps_val_nonce'] ) ), 'ps_val_' . $oid )
			&& ps_val_puede( $oid )
			&& (int) get_post_meta( $oid, '_ps_product_id', true ) === $pid;
		if ( ! $ok ) {
			wp_die( 'Solo pueden valorar quienes compraron y se atendieron, desde Mi cuenta → Mis atenciones.', 'Prevsoul', array( 'response' => 403, 'back_link' => true ) );
		}
		$data['comment_type'] = 'review';
		if ( '' === trim( (string) $data['comment_content'] ) ) {
			$data['comment_content'] = ' ';
		}
		return $data;
	},
	1
);
add_filter(
	'allow_empty_comment',
	function ( $allow, $data ) {
		return ( isset( $_POST['ps_orden'] ) && 'product' === get_post_type( (int) ( $data['comment_post_ID'] ?? 0 ) ) ) ? true : $allow; // phpcs:ignore WordPress.Security.NonceVerification
	},
	10,
	2
);
add_action(
	'comment_post',
	function ( $cid ) {
		$oid = isset( $_POST['ps_orden'] ) ? absint( $_POST['ps_orden'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$c   = get_comment( $cid );
		if ( ! $oid || ! $c || 'product' !== get_post_type( $c->comment_post_ID ) ) {
			return;
		}
		$r = isset( $_POST['rating'] ) ? max( 1, min( 5, absint( $_POST['rating'] ) ) ) : 5; // phpcs:ignore WordPress.Security.NonceVerification
		update_comment_meta( $cid, 'rating', $r );
		update_comment_meta( $cid, 'verified', 1 );
		update_comment_meta( $cid, 'ps_orden', $oid );
		update_post_meta( $oid, '_ps_valorada', $cid );
		if ( class_exists( 'WC_Comments' ) ) {
			WC_Comments::clear_transients( $c->comment_post_ID );
		}
	},
	20
);
add_filter(
	'comment_post_redirect',
	function ( $loc ) {
		return isset( $_POST['ps_orden'] ) ? add_query_arg( 'ps_val', 1, ps_val_url() ) : $loc; // phpcs:ignore WordPress.Security.NonceVerification
	}
);

/* -------------------------------------------------------------------------
 * Página del producto: valoraciones de solo lectura, sin formulario.
 * ---------------------------------------------------------------------- */
add_filter(
	'woocommerce_product_tabs',
	function ( $tabs ) {
		global $product;
		if ( ! $product ) {
			return $tabs;
		}
		$n              = (int) $product->get_review_count();
		$tabs['reviews'] = array(
			'title'    => 'Valoraciones (' . $n . ')',
			'priority' => 30,
			'callback' => 'ps_val_lista',
		);
		return $tabs;
	},
	98
);

function ps_val_nombre( $c ) {
	$p = preg_split( '/\s+/', trim( (string) $c->comment_author ) );
	return $p[0] . ( isset( $p[1] ) && '' !== $p[1] ? ' ' . mb_substr( $p[1], 0, 1 ) . '.' : '' );
}

function ps_val_lista() {
	global $product;
	$cs = get_comments( array( 'post_id' => $product->get_id(), 'status' => 'approve', 'type' => 'review', 'parent' => 0, 'number' => 50 ) );
	echo '<div id="reviews" class="ps-val-lista"><h2 style="font-size:20px;margin:0 0 6px">Lo que dicen los pacientes</h2>';
	if ( ! $cs ) {
		echo '<p class="ps-muted">Aún no hay valoraciones de esta atención.</p>';
	}
	foreach ( $cs as $c ) {
		$ver   = get_comment_meta( $c->comment_ID, 'verified', true ) || get_comment_meta( $c->comment_ID, 'ps_orden', true );
		$resp  = get_comments( array( 'parent' => $c->comment_ID, 'status' => 'approve' ) );
		$texto = trim( (string) $c->comment_content );
		echo '<div style="border-top:1px solid #EEF2F8;padding:12px 0">' . ps_val_estrellas( get_comment_meta( $c->comment_ID, 'rating', true ) ) . ' <strong>' . esc_html( ps_val_nombre( $c ) ) . '</strong>' . ( $ver ? ' <span style="font-size:11px;background:#E6F7F2;color:#167A64;border-radius:999px;padding:2px 8px">Atención verificada</span>' : '' ) . ' <span class="ps-muted ps-small">' . esc_html( get_comment_date( 'j M Y', $c ) ) . '</span>' . ( '' !== $texto ? '<div style="margin-top:4px">' . wp_kses_post( wpautop( $texto ) ) . '</div>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
		foreach ( $resp as $r ) {
			echo '<div style="margin:8px 0 0 16px;padding:8px 12px;background:#EAF1FC;border-radius:10px;font-size:14px"><strong>Respuesta de Prevsoul:</strong> ' . esc_html( $r->comment_content ) . '</div>';
		}
		echo '</div>';
	}
	echo '<p style="background:#EAF1FC;border-radius:10px;padding:10px 12px;font-size:13px;color:#164BA8;margin-top:8px">Solo pueden valorar quienes compraron y se atendieron, desde <a href="' . esc_url( ps_val_url() ) . '">Mi cuenta → Mis atenciones</a>.</p></div>';
}
