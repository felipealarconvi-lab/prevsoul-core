<?php
/**
 * Prestadores (clínicas y profesionales que realizan los tratamientos vendidos),
 * órdenes de atención, pasos a seguir del paciente, panel del prestador y liquidación.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PS_ORDEN_STATES = array(
	'recibida'  => 'Por agendar',
	'agendada'  => 'Agendada',
	'realizada' => 'Realizada',
	'cancelada' => 'Cancelada',
);

/* -------------------------------------------------------------------------
 * Tipo de contenido, rol y página del panel
 * ---------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		register_post_type(
			'ps_orden',
			array(
				'labels'          => array(
					'name'          => 'Órdenes de atención',
					'singular_name' => 'Orden de atención',
					'all_items'     => 'Órdenes de atención',
					'edit_item'     => 'Ver orden de atención',
					'menu_name'     => 'Órdenes',
				),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-clipboard',
				'menu_position'   => 29,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}
);

function ps_prest_upgrade() {
	if ( '1' === get_option( 'ps_prest_version' ) ) {
		return;
	}
	if ( ! get_role( 'ps_prestador' ) ) {
		add_role( 'ps_prestador', 'Prestador Prevsoul (clínica o profesional)', array( 'read' => true ) );
	}
	if ( ! get_page_by_path( 'panel-prestador' ) ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Panel de prestadores',
				'post_name'    => 'panel-prestador',
				'post_content' => '<!-- wp:shortcode -->[ps_panel_prestador]<!-- /wp:shortcode -->',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', PS_TEMPLATE );
		}
	}
	update_option( 'ps_prest_version', '1' );
}
add_action( 'init', 'ps_prest_upgrade', 20 );

function ps_panel_url() {
	$p = get_page_by_path( 'panel-prestador' );
	return $p ? get_permalink( $p ) : home_url( '/panel-prestador/' );
}

/* -------------------------------------------------------------------------
 * Ayudas
 * ---------------------------------------------------------------------- */
function ps_is_prestador_post( $id ) {
	return $id && in_array( get_post_type( $id ), array( 'ps_clinica', 'ps_odontologo' ), true );
}
function ps_product_prestador( $product_id ) {
	$id = (int) get_post_meta( $product_id, '_ps_prestador', true );
	return ps_is_prestador_post( $id ) ? $id : 0;
}
function ps_prestador_tipo( $id ) {
	if ( 'ps_clinica' === get_post_type( $id ) ) {
		return 'Clínica';
	}
	$t = get_post_meta( $id, '_ps_titulo', true );
	return $t ? $t : 'Profesional';
}
function ps_prestador_img( $id, $size = 40 ) {
	$logo = (int) get_post_meta( $id, '_ps_logo', true );
	if ( 'ps_clinica' === get_post_type( $id ) && $logo ) {
		return wp_get_attachment_image( $logo, 'thumbnail', false, array( 'class' => 'ps-avatar ps-logo', 'style' => 'width:' . (int) $size . 'px;height:' . (int) $size . 'px', 'alt' => 'Logo de ' . get_the_title( $id ) ) );
	}
	return ps_avatar( $id, $size );
}
function ps_prestador_chip( $product_id ) {
	$id = ps_product_prestador( $product_id );
	if ( ! $id ) {
		return '';
	}
	$name = esc_html( get_the_title( $id ) );
	$link = 'publish' === get_post_status( $id ) ? '<a href="' . esc_url( get_permalink( $id ) ) . '">' . $name . '</a>' : '<strong>' . $name . '</strong>';
	return '<div class="ps-prest">' . ps_prestador_img( $id, 36 ) . '<div><span class="ps-prest-k">Realiza: ' . esc_html( ps_prestador_tipo( $id ) ) . '</span>' . $link . '</div></div>';
}
function ps_clp( $n ) {
	return '$' . number_format( (float) $n, 0, ',', '.' );
}
function ps_comision_default() {
	return (float) get_option( 'ps_comision_default', 20 );
}
/** Comisión de Prevsoul para una línea vendida: porcentaje del total o monto fijo por unidad. */
function ps_comision( $product_id, $total, $qty ) {
	$tipo  = get_post_meta( $product_id, '_ps_com_tipo', true );
	$valor = (float) get_post_meta( $product_id, '_ps_com_valor', true );
	if ( 'fijo' === $tipo ) {
		return min( (float) $total, $valor * max( 1, (int) $qty ) );
	}
	$pct = 'pct' === $tipo ? $valor : ps_comision_default();
	return round( (float) $total * $pct / 100 );
}
function ps_wa_link( $num, $text = '' ) {
	$num = preg_replace( '/\D+/', '', (string) $num );
	return $num ? 'https://wa.me/' . $num . ( $text ? '?text=' . rawurlencode( $text ) : '' ) : '';
}

/* -------------------------------------------------------------------------
 * Producto: elegir quién realiza el tratamiento y la comisión
 * ---------------------------------------------------------------------- */
add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box( 'ps_prestador_box', 'Quién realiza el tratamiento', 'ps_product_box', 'product', 'side', 'high' );
	}
);
function ps_product_box( $post ) {
	wp_nonce_field( 'ps_product_box', 'ps_product_nonce' );
	$cur   = (int) get_post_meta( $post->ID, '_ps_prestador', true );
	$tipo  = get_post_meta( $post->ID, '_ps_com_tipo', true );
	$valor = get_post_meta( $post->ID, '_ps_com_valor', true );
	echo '<p><label for="ps_prestador"><strong>Prestador</strong></label><br><select id="ps_prestador" name="ps_prestador" style="width:100%"><option value="">— Sin asignar —</option>';
	foreach ( array( 'ps_clinica' => 'Clínicas', 'ps_odontologo' => 'Profesionales' ) as $pt => $label ) {
		$items = get_posts( array( 'post_type' => $pt, 'numberposts' => 300, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'title', 'order' => 'ASC' ) );
		if ( $items ) {
			echo '<optgroup label="' . esc_attr( $label ) . '">';
			foreach ( $items as $it ) {
				echo '<option value="' . (int) $it->ID . '" ' . selected( $cur, $it->ID, false ) . '>' . esc_html( $it->post_title ) . '</option>';
			}
			echo '</optgroup>';
		}
	}
	echo '</select></p><p class="description">Aparece con su foto o logo junto al producto. Cuando alguien compra, le llega la orden de atención.</p>';
	echo '<p><label for="ps_com_tipo"><strong>Comisión Prevsoul</strong></label><br><select id="ps_com_tipo" name="ps_com_tipo" style="width:100%">';
	echo '<option value="" ' . selected( $tipo, '', false ) . '>General (' . esc_html( ps_comision_default() ) . '%)</option>';
	echo '<option value="pct" ' . selected( $tipo, 'pct', false ) . '>Porcentaje propio</option>';
	echo '<option value="fijo" ' . selected( $tipo, 'fijo', false ) . '>Monto fijo por unidad</option></select></p>';
	echo '<p><input type="number" min="0" step="0.01" name="ps_com_valor" value="' . esc_attr( $valor ) . '" placeholder="Ej: 15 (%) o 8000 ($)" style="width:100%"></p>';
	echo '<p class="description">El prestador recibe el valor pagado menos esta comisión.</p>';
}
add_action(
	'save_post_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['ps_product_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_product_nonce'] ), 'ps_product_box' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$pid = isset( $_POST['ps_prestador'] ) ? absint( $_POST['ps_prestador'] ) : 0;
		update_post_meta( $post_id, '_ps_prestador', ps_is_prestador_post( $pid ) ? $pid : '' );
		$tipo = isset( $_POST['ps_com_tipo'] ) ? sanitize_key( $_POST['ps_com_tipo'] ) : '';
		update_post_meta( $post_id, '_ps_com_tipo', in_array( $tipo, array( 'pct', 'fijo' ), true ) ? $tipo : '' );
		update_post_meta( $post_id, '_ps_com_valor', isset( $_POST['ps_com_valor'] ) && '' !== $_POST['ps_com_valor'] ? (string) (float) $_POST['ps_com_valor'] : '' );
	}
);
add_filter(
	'manage_edit-product_columns',
	function ( $cols ) {
		$cols['ps_prestador'] = 'Realiza';
		return $cols;
	}
);
add_action(
	'manage_product_posts_custom_column',
	function ( $col, $id ) {
		if ( 'ps_prestador' === $col ) {
			$p = ps_product_prestador( $id );
			echo $p ? esc_html( get_the_title( $p ) ) : '<span style="color:#b32d2e">Sin asignar</span>';
		}
	},
	10,
	2
);

/* Selector de imagen (logo) en la ficha de la clínica. */
add_action(
	'admin_enqueue_scripts',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'ps_clinica' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			"jQuery(function($){\$(document).on('click','.ps-img-pick',function(e){e.preventDefault();var w=\$(this).closest('.ps-img-field');var f=wp.media({title:'Logo de la clínica',button:{text:'Usar este logo'},library:{type:'image'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();w.find('input').val(a.id);w.find('img').attr('src',(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url)).show();w.find('.ps-img-clear').show();});f.open();});\$(document).on('click','.ps-img-clear',function(e){e.preventDefault();var w=\$(this).closest('.ps-img-field');w.find('input').val('');w.find('img').hide();\$(this).hide();});});"
		);
	}
);

/* -------------------------------------------------------------------------
 * Tienda: mostrar el prestador en la ficha, el carro y el pedido
 * ---------------------------------------------------------------------- */
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( $product ) {
			echo ps_prestador_chip( $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	7
);
add_filter(
	'woocommerce_get_item_data',
	function ( $data, $cart_item ) {
		$id = ps_product_prestador( $cart_item['product_id'] );
		if ( $id ) {
			$data[] = array( 'key' => 'Realiza', 'value' => get_the_title( $id ) );
		}
		return $data;
	},
	10,
	2
);
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $item, $key, $values ) {
		$id = ps_product_prestador( $values['product_id'] );
		if ( $id ) {
			$item->add_meta_data( '_ps_prestador', $id, true );
			$item->add_meta_data( 'Realiza', get_the_title( $id ), true );
		}
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Pago confirmado: crear órdenes de atención y avisar a cada prestador
 * ---------------------------------------------------------------------- */
function ps_crear_ordenes( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_ps_ordenes' ) ) {
		return;
	}
	$creadas = array();
	$por     = array();
	$paciente = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	foreach ( $order->get_items() as $item_id => $item ) {
		$pid  = $item->get_product_id();
		$prov = (int) $item->get_meta( '_ps_prestador' );
		if ( ! ps_is_prestador_post( $prov ) ) {
			$prov = ps_product_prestador( $pid );
		}
		if ( ! $prov ) {
			continue;
		}
		$qty   = (int) $item->get_quantity();
		$total = (float) $item->get_total() + (float) $item->get_total_tax();
		$com   = ps_comision( $pid, $total, $qty );
		$oid   = wp_insert_post(
			array(
				'post_type'   => 'ps_orden',
				'post_status' => 'publish',
				'post_title'  => sprintf( 'Pedido #%s · %s · %s', $order->get_order_number(), $item->get_name(), $paciente ),
			)
		);
		if ( ! $oid || is_wp_error( $oid ) ) {
			continue;
		}
		$meta = array(
			'pedido'     => $order->get_id(),
			'numero'     => $order->get_order_number(),
			'item'       => $item_id,
			'producto'   => $item->get_name(),
			'product_id' => $pid,
			'cantidad'   => $qty,
			'prestador'  => $prov,
			'paciente'   => $paciente,
			'email'      => $order->get_billing_email(),
			'telefono'   => $order->get_billing_phone(),
			'monto'      => $total,
			'comision'   => $com,
			'pago'       => max( 0, $total - $com ),
			'estado'     => 'recibida',
			'cita'       => '',
			'pagada'     => '',
			'log'        => array( array( 't' => time(), 'e' => 'recibida', 'u' => 'Sistema' ) ),
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $oid, '_ps_' . $k, $v );
		}
		$creadas[]      = $oid;
		$por[ $prov ][] = $oid;
	}
	$order->update_meta_data( '_ps_ordenes', $creadas ? $creadas : array( 0 ) );
	$order->save();
	foreach ( $por as $prov => $ids ) {
		$sent = ps_avisar_prestador( $prov, $ids, $order );
		$order->add_order_note( sprintf( 'Prevsoul: orden de atención enviada a %s%s.', get_the_title( $prov ), $sent ? '' : ' (sin correo configurado, avísale por otro medio)' ) );
	}
}
add_action( 'woocommerce_order_status_processing', 'ps_crear_ordenes' );
add_action( 'woocommerce_order_status_completed', 'ps_crear_ordenes' );

function ps_cancelar_ordenes( $order_id ) {
	foreach ( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'meta_key' => '_ps_pedido', 'meta_value' => $order_id, 'fields' => 'ids' ) ) as $oid ) {
		ps_orden_set_estado( $oid, 'cancelada', 'Sistema (pedido cancelado o reembolsado)' );
	}
}
add_action( 'woocommerce_order_status_cancelled', 'ps_cancelar_ordenes' );
add_action( 'woocommerce_order_status_refunded', 'ps_cancelar_ordenes' );

function ps_orden_set_estado( $oid, $estado, $who ) {
	if ( ! isset( PS_ORDEN_STATES[ $estado ] ) ) {
		return;
	}
	update_post_meta( $oid, '_ps_estado', $estado );
	$log   = (array) get_post_meta( $oid, '_ps_log', true );
	$log[] = array( 't' => time(), 'e' => $estado, 'u' => $who );
	update_post_meta( $oid, '_ps_log', $log );
}

function ps_mail_wrap( $title, $inner ) {
	return '<div style="font-family:Arial,Helvetica,sans-serif;color:#0B2347;max-width:600px;margin:0 auto"><div style="font-size:22px;font-weight:800;color:#1D5FD1;margin:16px 0">prevsoul</div><h2 style="font-size:20px;margin:0 0 12px">' . esc_html( $title ) . '</h2>' . $inner . '<p style="color:#5A6B85;font-size:12px;margin-top:24px">Prevsoul · Prevención odontológica a tu medida</p></div>';
}

function ps_avisar_prestador( $prov, $ids, $order ) {
	$to = sanitize_email( (string) get_post_meta( $prov, '_ps_email', true ) );
	if ( ! $to ) {
		return false;
	}
	$rows = '';
	foreach ( $ids as $oid ) {
		$rows .= '<tr><td style="padding:8px;border-bottom:1px solid #e3e8ef">' . esc_html( get_post_meta( $oid, '_ps_producto', true ) ) . ' × ' . (int) get_post_meta( $oid, '_ps_cantidad', true ) . '</td><td style="padding:8px;border-bottom:1px solid #e3e8ef;text-align:right">' . esc_html( ps_clp( get_post_meta( $oid, '_ps_pago', true ) ) ) . '</td></tr>';
	}
	$oid0  = $ids[0];
	$inner = '<p>Hola ' . esc_html( get_the_title( $prov ) ) . ', Prevsoul vendió un tratamiento que realizas tú.</p>';
	$inner .= '<p><strong>Paciente:</strong> ' . esc_html( get_post_meta( $oid0, '_ps_paciente', true ) ) . '<br><strong>Teléfono:</strong> ' . esc_html( get_post_meta( $oid0, '_ps_telefono', true ) ) . '<br><strong>Correo:</strong> ' . esc_html( get_post_meta( $oid0, '_ps_email', true ) ) . '<br><strong>Pedido Prevsoul:</strong> #' . esc_html( $order->get_order_number() ) . '</p>';
	$inner .= '<table style="width:100%;border-collapse:collapse;font-size:14px"><tr><th style="text-align:left;padding:8px;background:#E8F0FC">Tratamiento</th><th style="text-align:right;padding:8px;background:#E8F0FC">Te corresponde</th></tr>' . $rows . '</table>';
	$inner .= '<p>El paciente ya recibió tus indicaciones para agendar. Cuando tenga hora y cuando lo atiendas, márcalo en tu panel:</p><p><a href="' . esc_url( ps_panel_url() ) . '" style="display:inline-block;background:#1D5FD1;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:700">Abrir mi panel de prestador</a></p>';
	return wp_mail( $to, 'Prevsoul: nuevo tratamiento vendido · Pedido #' . $order->get_order_number(), ps_mail_wrap( 'Nueva orden de atención', $inner ), array( 'Content-Type: text/html; charset=UTF-8' ) );
}

/* -------------------------------------------------------------------------
 * Paciente: pasos a seguir (página de gracias, Mi cuenta y correo)
 * ---------------------------------------------------------------------- */
function ps_pasos_html( $order, $email = false ) {
	$groups = array();
	foreach ( $order->get_items() as $item ) {
		$prov = (int) $item->get_meta( '_ps_prestador' );
		if ( ! ps_is_prestador_post( $prov ) ) {
			$prov = ps_product_prestador( $item->get_product_id() );
		}
		if ( $prov ) {
			$groups[ $prov ][] = $item->get_name();
		}
	}
	if ( ! $groups ) {
		return '';
	}
	$num = $order->get_order_number();
	$box = $email ? 'border:1px solid #d9e2f1;border-radius:10px;padding:16px;margin:0 0 14px' : '';
	$btn = $email ? 'display:inline-block;background:#1D5FD1;color:#fff;padding:9px 14px;border-radius:8px;text-decoration:none;font-weight:700;margin:4px 6px 0 0' : '';
	$out = $email ? '<h2 style="color:#0B2347">Pasos a seguir para tu tratamiento</h2>' : '<section class="ps-pasos"><h2>Pasos a seguir para tu tratamiento</h2>';
	$out .= '<ol' . ( $email ? ' style="padding-left:20px;color:#0B2347"' : ' class="ps-pasos-list"' ) . '><li>Contacta a quien realiza tu tratamiento con los datos de abajo.</li><li>Agenda tu hora indicando tu <strong>número de pedido #' . esc_html( $num ) . '</strong>.</li><li>Asiste a tu atención. Después te pediremos que nos cuentes cómo te fue.</li></ol>';
	foreach ( $groups as $prov => $names ) {
		$tel    = get_post_meta( $prov, '_ps_telefono', true );
		$wa     = ps_wa_link( get_post_meta( $prov, '_ps_whatsapp', true ), 'Hola, compré en Prevsoul (pedido #' . $num . '): ' . implode( ', ', $names ) . '. Quiero agendar mi hora.' );
		$mail   = get_post_meta( $prov, '_ps_email', true );
		$dir    = get_post_meta( $prov, '_ps_direccion', true );
		$mapa   = get_post_meta( $prov, '_ps_mapa', true );
		$agenda = get_post_meta( $prov, '_ps_agenda', true );
		if ( ! $agenda ) {
			$agenda = 'Contáctate con ' . get_the_title( $prov ) . ' por cualquiera de estos medios e indica tu número de pedido #' . $num . ' para agendar tu hora.';
		}
		$out .= $email ? '<div style="' . $box . '">' : '<div class="ps-card ps-paso">';
		$out .= $email ? '<p style="margin:0 0 6px;font-size:16px"><strong>' . esc_html( get_the_title( $prov ) ) . '</strong> · ' . esc_html( ps_prestador_tipo( $prov ) ) . '</p>' : '<div class="ps-row">' . ps_prestador_img( $prov, 56 ) . '<div><span class="ps-prest-k">' . esc_html( ps_prestador_tipo( $prov ) ) . '</span><h3 class="ps-card-title">' . esc_html( get_the_title( $prov ) ) . '</h3></div></div>';
		$out .= '<p' . ( $email ? ' style="margin:6px 0;color:#5A6B85"' : ' class="ps-muted ps-small"' ) . '>Realiza: ' . esc_html( implode( ', ', $names ) ) . '</p>';
		$out .= '<p' . ( $email ? ' style="margin:8px 0"' : '' ) . '>' . nl2br( esc_html( $agenda ) ) . '</p>';
		if ( $dir ) {
			$out .= '<p' . ( $email ? ' style="margin:6px 0"' : ' class="ps-small"' ) . '>📍 ' . esc_html( $dir ) . '</p>';
		}
		$out .= $email ? '<p>' : '<div class="ps-actions">';
		$cls  = $email ? ' style="' . $btn . '"' : ' class="ps-btn"';
		$cls2 = $email ? ' style="' . $btn . '"' : ' class="ps-btn ps-btn-ghost"';
		if ( $wa ) {
			$out .= '<a' . $cls . ' href="' . esc_url( $wa ) . '">Escribir por WhatsApp</a>';
		}
		if ( $tel ) {
			$out .= '<a' . $cls2 . ' href="' . esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', $tel ) ) . '">Llamar ' . esc_html( $tel ) . '</a>';
		}
		if ( $mail ) {
			$out .= '<a' . $cls2 . ' href="' . esc_url( 'mailto:' . $mail . '?subject=' . rawurlencode( 'Agendar hora · Pedido Prevsoul #' . $num ) ) . '">Enviar correo</a>';
		}
		if ( $mapa ) {
			$out .= '<a' . $cls2 . ' href="' . esc_url( $mapa ) . '">Ver mapa</a>';
		}
		$out .= $email ? '</p></div>' : '</div></div>';
	}
	return $out . ( $email ? '' : '</section>' );
}
add_action(
	'woocommerce_thankyou',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && $order->is_paid() ) {
			echo ps_pasos_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	1
);
add_action(
	'woocommerce_view_order',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && $order->is_paid() ) {
			echo ps_pasos_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	5
);
add_action(
	'woocommerce_email_before_order_table',
	function ( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $sent_to_admin || ! is_object( $email ) || ! in_array( $email->id, array( 'customer_processing_order', 'customer_completed_order' ), true ) ) {
			return;
		}
		$html = ps_pasos_html( $order, true );
		if ( ! $html ) {
			return;
		}
		echo $plain_text ? wp_strip_all_tags( str_replace( array( '</li>', '</p>', '</a>' ), array( "\n", "\n", '  ' ), $html ) ) . "\n\n" : $html; // phpcs:ignore WordPress.Security.EscapeOutput
	},
	5,
	4
);

/* -------------------------------------------------------------------------
 * Cuentas de prestador
 * ---------------------------------------------------------------------- */
function ps_user_prestador( $user_id = 0 ) {
	$id = (int) get_user_meta( $user_id ? $user_id : get_current_user_id(), 'ps_prestador_id', true );
	return ps_is_prestador_post( $id ) ? $id : 0;
}
function ps_user_prestador_field( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cur = ps_user_prestador( $user->ID );
	echo '<h2>Prevsoul</h2><table class="form-table" role="presentation"><tr><th><label for="ps_prestador_id">Prestador vinculado</label></th><td><select id="ps_prestador_id" name="ps_prestador_id"><option value="">— Ninguno —</option>';
	foreach ( array( 'ps_clinica' => 'Clínicas', 'ps_odontologo' => 'Profesionales' ) as $pt => $label ) {
		echo '<optgroup label="' . esc_attr( $label ) . '">';
		foreach ( get_posts( array( 'post_type' => $pt, 'numberposts' => 300, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $it ) {
			echo '<option value="' . (int) $it->ID . '" ' . selected( $cur, $it->ID, false ) . '>' . esc_html( $it->post_title ) . '</option>';
		}
		echo '</optgroup>';
	}
	echo '</select><p class="description">Con el perfil «Prestador Prevsoul», esta persona entra a <a href="' . esc_url( ps_panel_url() ) . '">su panel</a> y ve solo las órdenes de este prestador.</p></td></tr></table>';
}
add_action( 'show_user_profile', 'ps_user_prestador_field' );
add_action( 'edit_user_profile', 'ps_user_prestador_field' );
function ps_user_prestador_save( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['ps_prestador_id'] ) || ! check_admin_referer( 'update-user_' . $user_id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$id = absint( $_POST['ps_prestador_id'] );
	update_user_meta( $user_id, 'ps_prestador_id', ps_is_prestador_post( $id ) ? $id : '' );
}
add_action( 'personal_options_update', 'ps_user_prestador_save' );
add_action( 'edit_user_profile_update', 'ps_user_prestador_save' );

function ps_is_only_prestador( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user && $user->exists() && in_array( 'ps_prestador', (array) $user->roles, true ) && ! $user->has_cap( 'edit_posts' );
}
add_filter(
	'login_redirect',
	function ( $to, $req, $user ) {
		return ( $user instanceof WP_User && ps_is_only_prestador( $user ) ) ? ps_panel_url() : $to;
	},
	10,
	3
);
add_filter(
	'show_admin_bar',
	function ( $show ) {
		return ps_is_only_prestador() ? false : $show;
	}
);
add_action(
	'admin_init',
	function () {
		if ( ps_is_only_prestador() && ! wp_doing_ajax() && 'admin-post.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
			wp_safe_redirect( ps_panel_url() );
			exit;
		}
	},
	1
);
/* Los prestadores también pueden ver el sitio mientras está «en construcción». */
add_filter(
	'ps_construccion_bypass',
	function ( $bypass ) {
		return $bypass || ps_user_prestador();
	}
);

/* -------------------------------------------------------------------------
 * Panel del prestador: [ps_panel_prestador]
 * ---------------------------------------------------------------------- */
add_shortcode(
	'ps_panel_prestador',
	function () {
		if ( ! is_user_logged_in() ) {
			return '<div class="ps-card ps-panel-login"><h3 class="ps-card-title">Panel de prestadores Prevsoul</h3><p class="ps-muted">Ingresa con la cuenta que te entregó Prevsoul para ver los tratamientos que te corresponden.</p>' . wp_login_form( array( 'echo' => false, 'redirect' => ps_panel_url(), 'label_username' => 'Correo o usuario', 'label_log_in' => 'Ingresar' ) ) . '</div>';
		}
		$prov = ps_user_prestador();
		if ( ! $prov && current_user_can( 'manage_options' ) && isset( $_GET['prestador'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$prov = absint( $_GET['prestador'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$prov = ps_is_prestador_post( $prov ) ? $prov : 0;
		}
		if ( ! $prov ) {
			$msg = current_user_can( 'manage_options' ) ? 'Eres administrador: para ver el panel como lo ve un prestador, agrega <code>?prestador=ID</code> a la dirección, o vincula una cuenta en Usuarios → editar → «Prestador vinculado».' : 'Tu cuenta todavía no está vinculada a un prestador. Escríbele a Prevsoul para activarla.';
			return '<div class="ps-card"><p>' . wp_kses_post( $msg ) . '</p></div>';
		}
		$filtro = isset( $_GET['estado'] ) ? sanitize_key( $_GET['estado'] ) : 'activas'; // phpcs:ignore WordPress.Security.NonceVerification
		$all    = get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => 500, 'meta_key' => '_ps_prestador', 'meta_value' => $prov, 'orderby' => 'date', 'order' => 'DESC' ) );
		$count  = array_fill_keys( array_keys( PS_ORDEN_STATES ), 0 );
		$cobrar = 0;
		foreach ( $all as $o ) {
			$e = get_post_meta( $o->ID, '_ps_estado', true );
			if ( isset( $count[ $e ] ) ) {
				$count[ $e ]++;
			}
			if ( 'realizada' === $e && ! get_post_meta( $o->ID, '_ps_pagada', true ) ) {
				$cobrar += (float) get_post_meta( $o->ID, '_ps_pago', true );
			}
		}
		$out  = '<div class="ps-panel">';
		if ( isset( $_GET['ps_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$out .= '<div class="ps-card ps-ok"><strong>Listo, quedó actualizado.</strong></div>';
		}
		$out .= '<div class="ps-row ps-panel-head">' . ps_prestador_img( $prov, 64 ) . '<div><span class="ps-prest-k">' . esc_html( ps_prestador_tipo( $prov ) ) . '</span><h2 class="ps-card-title">' . esc_html( get_the_title( $prov ) ) . '</h2>' . ( current_user_can( 'manage_options' ) && ! ps_user_prestador() ? '<span class="ps-chip ps-acc">Vista de administrador</span>' : '<a class="ps-small" href="' . esc_url( wp_logout_url( ps_panel_url() ) ) . '">Cerrar sesión</a>' ) . '</div></div>';
		$out .= '<div class="ps-tiles"><div class="ps-tile"><span>Por agendar</span><strong>' . (int) $count['recibida'] . '</strong></div><div class="ps-tile"><span>Agendadas</span><strong>' . (int) $count['agendada'] . '</strong></div><div class="ps-tile"><span>Realizadas</span><strong>' . (int) $count['realizada'] . '</strong></div><div class="ps-tile ps-tile-dark"><span>Por cobrar a Prevsoul</span><strong>' . esc_html( ps_clp( $cobrar ) ) . '</strong></div></div>';
		$tabs = array( 'activas' => 'Pendientes', 'realizada' => 'Realizadas', 'cancelada' => 'Canceladas', 'todas' => 'Todas' );
		$base = remove_query_arg( array( 'estado', 'ps_ok' ) );
		$out .= '<nav class="ps-catnav">';
		foreach ( $tabs as $k => $label ) {
			$out .= '<a class="ps-chip' . ( $filtro === $k ? ' ps-acc' : '' ) . '" href="' . esc_url( add_query_arg( 'estado', $k, $base ) ) . '">' . esc_html( $label ) . '</a>';
		}
		$out .= '</nav>';
		$shown = 0;
		foreach ( $all as $o ) {
			$e = get_post_meta( $o->ID, '_ps_estado', true );
			if ( ( 'activas' === $filtro && ! in_array( $e, array( 'recibida', 'agendada' ), true ) ) || ( ! in_array( $filtro, array( 'activas', 'todas' ), true ) && $e !== $filtro ) ) {
				continue;
			}
			$shown++;
			$m    = function ( $k ) use ( $o ) {
				return get_post_meta( $o->ID, '_ps_' . $k, true );
			};
			$chip = array( 'recibida' => 'ps-warn', 'agendada' => 'ps-acc', 'realizada' => 'ps-good', 'cancelada' => '' );
			$out .= '<article class="ps-card ps-orden"><div class="ps-orden-top"><div><h3 class="ps-card-title">' . esc_html( $m( 'producto' ) ) . ( (int) $m( 'cantidad' ) > 1 ? ' × ' . (int) $m( 'cantidad' ) : '' ) . '</h3><div class="ps-muted ps-small">Pedido #' . esc_html( $m( 'numero' ) ) . ' · vendido el ' . esc_html( get_the_date( 'j \d\e F Y', $o ) ) . '</div></div><span class="ps-chip ' . esc_attr( $chip[ $e ] ?? '' ) . '">' . esc_html( PS_ORDEN_STATES[ $e ] ?? $e ) . '</span></div>';
			$out .= '<div class="ps-orden-grid"><div><span class="ps-prest-k">Paciente</span><strong>' . esc_html( $m( 'paciente' ) ) . '</strong><div class="ps-small">' . esc_html( $m( 'telefono' ) ) . ' · ' . esc_html( $m( 'email' ) ) . '</div></div><div><span class="ps-prest-k">Te corresponde</span><strong>' . esc_html( ps_clp( $m( 'pago' ) ) ) . '</strong>' . ( $m( 'pagada' ) ? '<div class="ps-small">Pagado el ' . esc_html( $m( 'pagada' ) ) . '</div>' : '' ) . '</div>' . ( $m( 'cita' ) ? '<div><span class="ps-prest-k">Cita</span><strong>' . esc_html( ps_fmt_date( $m( 'cita' ) ) ) . '</strong></div>' : '' ) . '</div>';
			if ( in_array( $e, array( 'recibida', 'agendada' ), true ) ) {
				$out .= '<form class="ps-orden-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_orden_' . $o->ID, '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_orden_estado"><input type="hidden" name="orden" value="' . (int) $o->ID . '"><input type="hidden" name="back" value="' . esc_attr( $base ) . '">';
				$out .= '<label class="ps-small">Fecha y hora de la cita<input type="datetime-local" name="cita" value="' . esc_attr( $m( 'cita' ) ) . '"></label>';
				$out .= '<button class="ps-btn ps-btn-ghost" type="submit" name="estado" value="agendada">' . ( 'agendada' === $e ? 'Cambiar fecha' : 'Marcar agendada' ) . '</button><button class="ps-btn" type="submit" name="estado" value="realizada">Marcar realizada</button></form>';
			}
			$out .= '</article>';
		}
		if ( ! $shown ) {
			$out .= '<div class="ps-card"><p class="ps-muted">No hay órdenes en esta sección.</p></div>';
		}
		return $out . '</div>';
	}
);

add_action( 'admin_post_ps_orden_estado', 'ps_handle_orden_estado' );
function ps_handle_orden_estado() {
	$oid = isset( $_POST['orden'] ) ? absint( $_POST['orden'] ) : 0;
	if ( ! $oid || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_orden_' . $oid ) || 'ps_orden' !== get_post_type( $oid ) ) {
		wp_die( 'No permitido.' );
	}
	$prov  = (int) get_post_meta( $oid, '_ps_prestador', true );
	$owner = ps_user_prestador() === $prov;
	if ( ! $owner && ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Esta orden no corresponde a tu cuenta.' );
	}
	$estado = isset( $_POST['estado'] ) ? sanitize_key( $_POST['estado'] ) : '';
	$cita   = isset( $_POST['cita'] ) ? sanitize_text_field( wp_unslash( $_POST['cita'] ) ) : '';
	if ( $cita ) {
		update_post_meta( $oid, '_ps_cita', $cita );
	}
	if ( in_array( $estado, array( 'agendada', 'realizada' ), true ) ) {
		$before = get_post_meta( $oid, '_ps_estado', true );
		$who    = wp_get_current_user()->display_name . ( $owner ? ' (' . get_the_title( $prov ) . ')' : ' (Prevsoul)' );
		ps_orden_set_estado( $oid, $estado, $who );
		if ( 'realizada' === $estado && 'realizada' !== $before ) {
			ps_invitar_valorar( $oid );
		}
	}
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : ps_panel_url();
	wp_safe_redirect( add_query_arg( 'ps_ok', 1, $back ) );
	exit;
}

function ps_invitar_valorar( $oid ) {
	$to   = sanitize_email( (string) get_post_meta( $oid, '_ps_email', true ) );
	$pid  = (int) get_post_meta( $oid, '_ps_product_id', true );
	$prov = (int) get_post_meta( $oid, '_ps_prestador', true );
	if ( ! $to || ! $pid ) {
		return;
	}
	$inner  = '<p>Hola ' . esc_html( get_post_meta( $oid, '_ps_paciente', true ) ) . ', ' . esc_html( get_the_title( $prov ) ) . ' nos informó que ya realizaste tu atención: <strong>' . esc_html( get_post_meta( $oid, '_ps_producto', true ) ) . '</strong>.</p>';
	$inner .= '<p>¿Nos cuentas cómo te fue? Tu opinión ayuda a otros pacientes y a mantener la calidad de nuestra red.</p><p><a href="' . esc_url( get_permalink( $pid ) . '#reviews' ) . '" style="display:inline-block;background:#1D5FD1;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:700">Valorar mi atención</a></p>';
	wp_mail( $to, '¿Cómo te fue con tu atención Prevsoul?', ps_mail_wrap( '¿Cómo te fue?', $inner ), array( 'Content-Type: text/html; charset=UTF-8' ) );
}

/* -------------------------------------------------------------------------
 * Administración: lista de órdenes, ficha y liquidación
 * ---------------------------------------------------------------------- */
add_filter(
	'manage_ps_orden_posts_columns',
	function () {
		return array( 'cb' => '<input type="checkbox">', 'title' => 'Orden', 'ps_prest' => 'Prestador', 'ps_estado' => 'Estado', 'ps_monto' => 'Venta', 'ps_com' => 'Comisión', 'ps_pago' => 'A pagar', 'ps_pagada' => 'Pagada', 'date' => 'Fecha' );
	}
);
add_action(
	'manage_ps_orden_posts_custom_column',
	function ( $col, $id ) {
		$m = function ( $k ) use ( $id ) {
			return get_post_meta( $id, '_ps_' . $k, true );
		};
		switch ( $col ) {
			case 'ps_prest':
				echo esc_html( get_the_title( (int) $m( 'prestador' ) ) );
				break;
			case 'ps_estado':
				echo esc_html( PS_ORDEN_STATES[ $m( 'estado' ) ] ?? '' );
				break;
			case 'ps_monto':
				echo esc_html( ps_clp( $m( 'monto' ) ) );
				break;
			case 'ps_com':
				echo esc_html( ps_clp( $m( 'comision' ) ) );
				break;
			case 'ps_pago':
				echo esc_html( ps_clp( $m( 'pago' ) ) );
				break;
			case 'ps_pagada':
				echo $m( 'pagada' ) ? esc_html( $m( 'pagada' ) ) : '—';
				break;
		}
	},
	10,
	2
);
add_action(
	'restrict_manage_posts',
	function ( $pt ) {
		if ( 'ps_orden' !== $pt ) {
			return;
		}
		$cur = isset( $_GET['ps_estado'] ) ? sanitize_key( $_GET['ps_estado'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<select name="ps_estado"><option value="">Todos los estados</option>';
		foreach ( PS_ORDEN_STATES as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	}
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() && $q->is_main_query() && 'ps_orden' === $q->get( 'post_type' ) && ! empty( $_GET['ps_estado'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$q->set( 'meta_key', '_ps_estado' );
			$q->set( 'meta_value', sanitize_key( $_GET['ps_estado'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
);
add_action(
	'add_meta_boxes_ps_orden',
	function () {
		add_meta_box( 'ps_orden_box', 'Detalle de la orden', 'ps_orden_box', 'ps_orden', 'normal', 'high' );
	}
);
function ps_orden_box( $post ) {
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, '_ps_' . $k, true );
	};
	$prov  = (int) $m( 'prestador' );
	$order = wc_get_order( (int) $m( 'pedido' ) );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th>Prestador</th><td><strong>' . esc_html( get_the_title( $prov ) ) . '</strong> · <a href="' . esc_url( add_query_arg( 'prestador', $prov, ps_panel_url() ) ) . '" target="_blank">ver su panel</a></td></tr>';
	echo '<tr><th>Pedido</th><td>' . ( $order ? '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $m( 'numero' ) ) . '</a>' : '#' . esc_html( $m( 'numero' ) ) ) . '</td></tr>';
	echo '<tr><th>Tratamiento</th><td>' . esc_html( $m( 'producto' ) ) . ' × ' . (int) $m( 'cantidad' ) . '</td></tr>';
	echo '<tr><th>Paciente</th><td>' . esc_html( $m( 'paciente' ) ) . '<br>' . esc_html( $m( 'telefono' ) ) . ' · ' . esc_html( $m( 'email' ) ) . '</td></tr>';
	echo '<tr><th>Venta / comisión / a pagar</th><td>' . esc_html( ps_clp( $m( 'monto' ) ) ) . ' / ' . esc_html( ps_clp( $m( 'comision' ) ) ) . ' / <strong>' . esc_html( ps_clp( $m( 'pago' ) ) ) . '</strong>' . ( $m( 'pagada' ) ? ' · pagado el ' . esc_html( $m( 'pagada' ) ) : '' ) . '</td></tr>';
	echo '<tr><th>Cita</th><td>' . ( $m( 'cita' ) ? esc_html( ps_fmt_date( $m( 'cita' ) ) ) : '—' ) . '</td></tr>';
	echo '<tr><th>Estado</th><td>' . wp_nonce_field( 'ps_orden_admin', 'ps_orden_nonce', true, false ) . '<select name="ps_orden_estado">';
	foreach ( PS_ORDEN_STATES as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $m( 'estado' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select> <span class="description">Guárdalo con «Actualizar».</span></td></tr>';
	echo '<tr><th>Historial</th><td><ul style="margin:0">';
	foreach ( (array) $m( 'log' ) as $l ) {
		if ( is_array( $l ) ) {
			echo '<li>' . esc_html( wp_date( 'j M Y H:i', (int) $l['t'] ) ) . ' · <strong>' . esc_html( PS_ORDEN_STATES[ $l['e'] ] ?? $l['e'] ) . '</strong> · ' . esc_html( $l['u'] ) . '</li>';
		}
	}
	echo '</ul></td></tr></table>';
}
add_action(
	'save_post_ps_orden',
	function ( $post_id ) {
		if ( ! isset( $_POST['ps_orden_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ps_orden_nonce'] ), 'ps_orden_admin' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$new = isset( $_POST['ps_orden_estado'] ) ? sanitize_key( $_POST['ps_orden_estado'] ) : '';
		$old = get_post_meta( $post_id, '_ps_estado', true );
		if ( $new && $new !== $old ) {
			ps_orden_set_estado( $post_id, $new, wp_get_current_user()->display_name . ' (Prevsoul)' );
			if ( 'realizada' === $new ) {
				ps_invitar_valorar( $post_id );
			}
		}
	}
);

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=ps_orden', 'Liquidación de prestadores', 'Liquidación', 'manage_options', 'ps-liquidacion', 'ps_liquidacion_page' );
	}
);
function ps_liquidacion_page() {
	$rows = array();
	foreach ( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'meta_key' => '_ps_estado', 'meta_value' => 'realizada' ) ) as $o ) {
		if ( get_post_meta( $o->ID, '_ps_pagada', true ) ) {
			continue;
		}
		$p = (int) get_post_meta( $o->ID, '_ps_prestador', true );
		if ( ! isset( $rows[ $p ] ) ) {
			$rows[ $p ] = array( 'n' => 0, 'venta' => 0, 'com' => 0, 'pago' => 0 );
		}
		$rows[ $p ]['n']++;
		$rows[ $p ]['venta'] += (float) get_post_meta( $o->ID, '_ps_monto', true );
		$rows[ $p ]['com']   += (float) get_post_meta( $o->ID, '_ps_comision', true );
		$rows[ $p ]['pago']  += (float) get_post_meta( $o->ID, '_ps_pago', true );
	}
	echo '<div class="wrap"><h1>Liquidación de prestadores</h1>';
	if ( isset( $_GET['pagado'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success"><p>Órdenes marcadas como pagadas.</p></div>';
	}
	echo '<p>Atenciones <strong>realizadas</strong> que todavía no le pagas a cada prestador. Cuando hagas la transferencia, pulsa «Marcar pagadas».</p>';
	echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Prestador</th><th>Atenciones</th><th>Venta</th><th>Comisión Prevsoul</th><th>A pagar</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="6">No hay pagos pendientes.</td></tr>';
	}
	foreach ( $rows as $p => $r ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=ps_pagar&prestador=' . $p ), 'ps_pagar_' . $p );
		echo '<tr><td>' . esc_html( get_the_title( $p ) ) . '</td><td>' . (int) $r['n'] . '</td><td>' . esc_html( ps_clp( $r['venta'] ) ) . '</td><td>' . esc_html( ps_clp( $r['com'] ) ) . '</td><td><strong>' . esc_html( ps_clp( $r['pago'] ) ) . '</strong></td><td><a class="button" href="' . esc_url( $url ) . '" onclick="return confirm(\'¿Marcar como pagadas las ' . (int) $r['n'] . ' atenciones de este prestador?\');">Marcar pagadas</a></td></tr>';
	}
	echo '</tbody></table>';
	echo '<h2 style="margin-top:32px">Comisión general</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_comision', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_comision"><input type="number" min="0" max="100" step="0.1" name="ps_comision" value="' . esc_attr( ps_comision_default() ) . '" style="width:90px"> % <button class="button button-primary">Guardar</button><p class="description">Se aplica a los productos sin comisión propia. Cada producto puede tener su porcentaje o un monto fijo en «Quién realiza el tratamiento». Las órdenes ya creadas mantienen la comisión con que se vendieron.</p></form></div>';
}
add_action(
	'admin_post_ps_pagar',
	function () {
		$p = isset( $_GET['prestador'] ) ? absint( $_GET['prestador'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_pagar_' . $p ) ) {
			wp_die( 'No permitido.' );
		}
		foreach ( get_posts( array( 'post_type' => 'ps_orden', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_prestador', 'value' => $p ), array( 'key' => '_ps_estado', 'value' => 'realizada' ) ) ) ) as $oid ) {
			if ( ! get_post_meta( $oid, '_ps_pagada', true ) ) {
				update_post_meta( $oid, '_ps_pagada', wp_date( 'Y-m-d' ) );
			}
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=ps_orden&page=ps-liquidacion&pagado=1' ) );
		exit;
	}
);
add_action(
	'admin_post_ps_comision',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_comision' ) ) {
			wp_die( 'No permitido.' );
		}
		$v = isset( $_POST['ps_comision'] ) ? (float) $_POST['ps_comision'] : 20;
		update_option( 'ps_comision_default', max( 0, min( 100, $v ) ) );
		wp_safe_redirect( admin_url( 'edit.php?post_type=ps_orden&page=ps-liquidacion' ) );
		exit;
	}
);

add_action(
	'admin_head',
	function () {
		echo '<style>.wp-list-table .column-ps_prestador{width:150px}.wp-list-table .column-ps_prest{width:160px}</style>';
	}
);
