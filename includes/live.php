<?php
/**
 * Prevsoul Live: interruptor de visibilidad, página Live con preguntas y «Recordarme»,
 * cuentas de dentista y panel «Mis transmisiones».
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Visibilidad
 * ---------------------------------------------------------------------- */
function ps_live_on() {
	return '1' === (string) get_option( 'ps_live_on', '0' );
}
function ps_user_dentista( $user_id = 0 ) {
	$id = (int) get_user_meta( $user_id ? $user_id : get_current_user_id(), 'ps_odontologo_id', true );
	return ( $id && 'ps_odontologo' === get_post_type( $id ) ) ? $id : 0;
}
/** Los pacientes ven Live solo si está encendido; tú y los dentistas lo ven siempre. */
function ps_live_visible() {
	return ps_live_on() || current_user_can( 'edit_posts' ) || ps_user_dentista();
}
function ps_is_live_url( $url ) {
	return false !== strpos( (string) $url, 'prevsoul-live' ) || false !== strpos( (string) $url, '/live/' );
}

add_filter(
	'wp_nav_menu_objects',
	function ( $items ) {
		if ( ps_live_visible() ) {
			return $items;
		}
		return array_values( array_filter( $items, function ( $it ) { return ! ps_is_live_url( $it->url ); } ) );
	}
);
add_action(
	'template_redirect',
	function () {
		if ( ps_live_visible() ) {
			return;
		}
		if ( is_page( 'prevsoul-live' ) || is_singular( 'ps_transmision' ) || is_post_type_archive( 'ps_transmision' ) ) {
			wp_safe_redirect( home_url( '/' ), 302 );
			exit;
		}
	}
);
add_filter(
	'ps_construccion_bypass',
	function ( $bypass ) {
		return $bypass || ps_user_dentista();
	}
);

add_action(
	'admin_post_ps_live_toggle',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_live_toggle' ) ) {
			wp_die( 'No permitido.' );
		}
		update_option( 'ps_live_on', empty( $_GET['on'] ) ? '0' : '1' ); // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( admin_url( 'admin.php?page=prevsoul' ) );
		exit;
	}
);
function ps_live_card() {
	$on  = ps_live_on();
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=ps_live_toggle&on=' . ( $on ? 0 : 1 ) ), 'ps_live_toggle' );
	echo '<div class="card" style="margin:0;min-width:260px;border-left:4px solid ' . ( $on ? '#00a32a' : '#8c8f94' ) . '"><h2 style="margin-top:0">Prevsoul Live</h2>';
	if ( $on ) {
		echo '<p><strong>Visible.</strong> Prevsoul Live aparece en el menú, la portada y su página.</p><p><a class="button" href="' . esc_url( $url ) . '">Ocultar del sitio</a></p>';
	} else {
		echo '<p><strong>Oculto.</strong> Los pacientes no ven nada de Prevsoul Live: se quita del menú y de la portada, la página lleva al inicio y los correos de «Recordarme» quedan en pausa. Tú y los dentistas pueden seguir preparándolo.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '" onclick="return confirm(\'¿Mostrar Prevsoul Live a todos los visitantes?\');">Mostrar en el sitio</a></p>';
	}
	echo '<p><a href="' . esc_url( ps_panel_dentista_url() ) . '">Panel del dentista</a> · <a href="' . esc_url( home_url( '/prevsoul-live/' ) ) . '">Página Live</a></p></div>';
}

/* -------------------------------------------------------------------------
 * Preguntas, rol de dentista y página del panel
 * ---------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		register_post_type(
			'ps_pregunta',
			array(
				'labels'          => array( 'name' => 'Preguntas de Live', 'singular_name' => 'Pregunta', 'all_items' => 'Preguntas' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=ps_transmision',
				'supports'        => array( 'title' ),
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
		if ( '1' !== get_option( 'ps_live_version' ) ) {
			if ( ! get_role( 'ps_dentista' ) ) {
				add_role( 'ps_dentista', 'Dentista Prevsoul', array( 'read' => true ) );
			}
			if ( ! get_page_by_path( 'panel-dentista' ) ) {
				$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Panel del dentista', 'post_name' => 'panel-dentista', 'post_content' => '<!-- wp:shortcode -->[ps_panel_dentista]<!-- /wp:shortcode -->' ) );
				if ( $id && ! is_wp_error( $id ) ) {
					update_post_meta( $id, '_wp_page_template', PS_TEMPLATE );
				}
			}
			update_option( 'ps_live_version', '1' );
		}
		add_shortcode( 'ps_live', 'ps_live_render' );
	},
	30
);
function ps_panel_dentista_url() {
	$p = get_page_by_path( 'panel-dentista' );
	return $p ? get_permalink( $p ) : home_url( '/panel-dentista/' );
}

function ps_user_dentista_field( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cur = ps_user_dentista( $user->ID );
	echo '<table class="form-table" role="presentation"><tr><th><label for="ps_odontologo_id">Odontólogo vinculado</label></th><td><select id="ps_odontologo_id" name="ps_odontologo_id"><option value="">— Ninguno —</option>';
	foreach ( get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 300, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $it ) {
		echo '<option value="' . (int) $it->ID . '" ' . selected( $cur, $it->ID, false ) . '>' . esc_html( $it->post_title ) . '</option>';
	}
	echo '</select><p class="description">Con el perfil «Dentista Prevsoul», esta persona entra a <a href="' . esc_url( ps_panel_dentista_url() ) . '">su panel</a> para programar y transmitir en Prevsoul Live y responder preguntas.</p></td></tr></table>';
}
add_action( 'show_user_profile', 'ps_user_dentista_field', 11 );
add_action( 'edit_user_profile', 'ps_user_dentista_field', 11 );
function ps_user_dentista_save( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['ps_odontologo_id'] ) || ! check_admin_referer( 'update-user_' . $user_id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$id = absint( $_POST['ps_odontologo_id'] );
	update_user_meta( $user_id, 'ps_odontologo_id', ( $id && 'ps_odontologo' === get_post_type( $id ) ) ? $id : '' );
}
add_action( 'personal_options_update', 'ps_user_dentista_save' );
add_action( 'edit_user_profile_update', 'ps_user_dentista_save' );

function ps_is_only_dentista( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user && $user->exists() && in_array( 'ps_dentista', (array) $user->roles, true ) && ! $user->has_cap( 'edit_posts' );
}
add_filter(
	'login_redirect',
	function ( $to, $req, $user ) {
		return ( $user instanceof WP_User && ps_is_only_dentista( $user ) ) ? ps_panel_dentista_url() : $to;
	},
	11,
	3
);
add_filter(
	'show_admin_bar',
	function ( $show ) {
		return ps_is_only_dentista() ? false : $show;
	}
);
add_action(
	'admin_init',
	function () {
		if ( ps_is_only_dentista() && ! wp_doing_ajax() && 'admin-post.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
			wp_safe_redirect( ps_panel_dentista_url() );
			exit;
		}
	},
	1
);

/* -------------------------------------------------------------------------
 * Ayudas de transmisiones
 * ---------------------------------------------------------------------- */
function ps_live_main( $odontologo = 0 ) {
	$base = array( 'post_type' => 'ps_transmision', 'post_status' => 'publish' );
	$own  = $odontologo ? array( array( 'key' => '_ps_odontologo', 'value' => $odontologo ) ) : array();
	$live = get_posts( $base + array( 'numberposts' => 1, 'meta_query' => array_merge( array( array( 'key' => '_ps_estado', 'value' => 'en-vivo' ) ), $own ) ) );
	if ( $live ) {
		return $live[0];
	}
	$next = get_posts( $base + array( 'numberposts' => 1, 'meta_key' => '_ps_fecha', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => array_merge( array( array( 'key' => '_ps_estado', 'value' => 'programada' ) ), $own ) ) );
	if ( $next ) {
		return $next[0];
	}
	$last = get_posts( $base + array( 'numberposts' => 1, 'meta_key' => '_ps_fecha', 'orderby' => 'meta_value', 'order' => 'DESC', 'meta_query' => array_merge( array( array( 'key' => '_ps_estado', 'value' => 'finalizada' ) ), $own ) ) );
	return $last ? $last[0] : null;
}
function ps_live_upcoming( $exclude = 0 ) {
	return get_posts( array( 'post_type' => 'ps_transmision', 'post_status' => 'publish', 'numberposts' => 5, 'post__not_in' => array( $exclude ), 'meta_key' => '_ps_fecha', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => array( array( 'key' => '_ps_estado', 'value' => 'programada' ) ) ) );
}
function ps_preguntas( $tid, $all = false ) {
	$q = get_posts( array( 'post_type' => 'ps_pregunta', 'post_status' => 'publish', 'numberposts' => 200, 'post_parent' => $tid ) );
	usort(
		$q,
		function ( $a, $b ) {
			$ra = 'respondida' === get_post_meta( $a->ID, '_ps_estado', true );
			$rb = 'respondida' === get_post_meta( $b->ID, '_ps_estado', true );
			return $ra === $rb ? (int) get_post_meta( $b->ID, '_ps_votos', true ) <=> (int) get_post_meta( $a->ID, '_ps_votos', true ) : ( $ra ? 1 : -1 );
		}
	);
	return $q;
}
function ps_inscritos( $tid ) {
	$l = get_post_meta( $tid, '_ps_inscritos', true );
	return is_array( $l ) ? $l : array();
}
function ps_ip_key() {
	return substr( hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) ), 0, 20 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
}
function ps_back( $anchor = '' ) {
	$b = wp_get_referer();
	$b = $b ? remove_query_arg( array( 'ps_q', 'ps_rec', 'ps_ok', 'ps_err' ), $b ) : home_url( '/prevsoul-live/' );
	return $b;
}

/* -------------------------------------------------------------------------
 * Página Live: [ps_live]
 * ---------------------------------------------------------------------- */
function ps_live_render() {
	if ( ! ps_live_visible() ) {
		return '';
	}
	$out = '';
	if ( ! ps_live_on() ) {
		$out .= '<div class="ps-card ps-live-hidden"><strong>Prevsoul Live está oculto.</strong> Solo tú y los dentistas ven esto. Se muestra a los pacientes desde Prevsoul → «Mostrar en el sitio».</div>';
	}
	$main = ps_live_main();
	if ( ! $main ) {
		return $out . '<div class="ps-card"><strong>Pronto anunciaremos la próxima transmisión de Prevsoul Live.</strong></div>';
	}
	$estado = get_post_meta( $main->ID, '_ps_estado', true );
	$fecha  = get_post_meta( $main->ID, '_ps_fecha', true );
	$yt     = get_post_meta( $main->ID, '_ps_youtube', true );
	$embed  = $yt ? wp_oembed_get( $yt, array( 'width' => 960 ) ) : '';
	$host   = (int) get_post_meta( $main->ID, '_ps_odontologo', true );
	$badge  = 'en-vivo' === $estado ? '<span class="ps-live-badge is-live"><i></i>EN VIVO</span>' : ( 'programada' === $estado ? '<span class="ps-live-badge is-soon">' . esc_html( ps_fmt_date( $fecha ) ) . '</span>' : '<span class="ps-live-badge is-rec">Grabación</span>' );
	$out   .= '<div class="ps-live2"><div class="ps-live2-main"><div class="ps-live2-player">' . $badge;
	$out   .= $embed ? '<div class="ps-embed">' . $embed . '</div>' : '<div class="ps-video-placeholder"><span>' . esc_html( 'programada' === $estado ? 'La transmisión comienza el ' . ps_fmt_date( $fecha ) : ( 'en-vivo' === $estado ? 'Transmisión en curso' : 'La grabación estará disponible pronto' ) ) . '</span></div>';
	$out   .= '</div><h3 class="ps-live2-title">' . esc_html( $main->post_title ) . '</h3>';
	if ( $host ) {
		$t    = get_post_meta( $host, '_ps_titulo', true );
		$out .= '<div class="ps-row ps-live2-host">' . ps_avatar( $host, 40 ) . '<div><strong>' . esc_html( get_the_title( $host ) ) . '</strong>' . ( $t ? '<div class="ps-muted ps-small">' . esc_html( $t ) . '</div>' : '' ) . '</div></div>';
	}
	$out .= $main->post_content ? '<div class="ps-small ps-live2-desc">' . wp_kses_post( wpautop( $main->post_content ) ) . '</div>' : '';
	if ( 'finalizada' === $estado ) {
		$out .= '<div class="ps-live2-cta"><span>¿Te quedó una duda sobre tu caso?</span><a class="ps-btn" href="' . esc_url( ps_get( 'sala_url' ) ) . '">Habla con un odontólogo</a></div>';
	} elseif ( 'programada' === $estado ) {
		$out .= ps_recordarme_form( $main->ID, true );
	}
	$out .= '</div><div class="ps-live2-side">';

	// Preguntas.
	$out .= '<div class="ps-card" id="ps-preguntas"><h4 class="ps-card-title">' . ( 'finalizada' === $estado ? 'Preguntas de esta transmisión' : 'Haz tu pregunta' ) . '</h4>';
	if ( isset( $_GET['ps_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$out .= '<p class="ps-small ps-live2-ok">' . ( 'ok' === $_GET['ps_q'] ? 'Recibimos tu pregunta.' : 'No pudimos recibirla. Espera unos segundos y vuelve a intentarlo.' ) . '</p>'; // phpcs:ignore WordPress.Security.NonceVerification
	}
	if ( 'finalizada' !== $estado ) {
		$out .= '<form class="ps-qform" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_pregunta', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_pregunta"><input type="hidden" name="t" value="' . (int) $main->ID . '"><input type="text" name="ps_web" value="" tabindex="-1" autocomplete="off" class="ps-hp" aria-hidden="true">';
		$out .= '<label class="screen-reader-text" for="ps_qt">Tu pregunta</label><textarea id="ps_qt" name="texto" rows="2" maxlength="220" required placeholder="Ej: ¿Desde qué edad usar pasta con flúor?"></textarea>';
		$out .= '<div class="ps-qrow"><label class="screen-reader-text" for="ps_qn">Tu nombre</label><input id="ps_qn" name="nombre" maxlength="30" placeholder="Tu nombre (opcional)"><button class="ps-btn" type="submit">Enviar</button></div>';
		$out .= '<p class="ps-muted ps-small">Se publica solo tu nombre de pila. No incluyas datos personales ni de salud.</p></form>';
	}
	$qs = ps_preguntas( $main->ID );
	if ( $qs ) {
		$out .= '<ul class="ps-qlist">';
		foreach ( $qs as $q ) {
			$ok   = 'respondida' === get_post_meta( $q->ID, '_ps_estado', true );
			$out .= '<li class="' . ( $ok ? 'is-ok' : '' ) . '"><span>' . esc_html( $q->post_title ) . '<small>' . esc_html( get_post_meta( $q->ID, '_ps_nombre', true ) ) . ( $ok ? ' · respondida' : '' ) . '</small></span>';
			$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_voto', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_voto"><input type="hidden" name="q" value="' . (int) $q->ID . '"><button class="ps-vote" type="submit" aria-label="Apoyar esta pregunta">▲ ' . (int) get_post_meta( $q->ID, '_ps_votos', true ) . '</button></form></li>';
		}
		$out .= '</ul>';
	} elseif ( 'finalizada' === $estado ) {
		$out .= '<p class="ps-muted ps-small">No hubo preguntas en esta transmisión.</p>';
	}
	$out .= '</div>';

	// Próximas.
	$next = ps_live_upcoming( $main->ID );
	if ( $next ) {
		if ( isset( $_GET['ps_rec'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$out .= '<div class="ps-card ps-ok ps-small"><strong>Listo, te avisaremos por correo cuando empiece.</strong></div>';
		}
		$out .= '<div class="ps-card"><h4 class="ps-card-title">Próximas transmisiones</h4>';
		foreach ( $next as $n ) {
			$h    = (int) get_post_meta( $n->ID, '_ps_odontologo', true );
			$out .= '<div class="ps-up"><div><strong>' . esc_html( $n->post_title ) . '</strong><div class="ps-muted ps-small">' . esc_html( ps_fmt_date( get_post_meta( $n->ID, '_ps_fecha', true ) ) ) . ( $h ? ' · ' . esc_html( get_the_title( $h ) ) : '' ) . '</div></div>' . ps_recordarme_form( $n->ID ) . '</div>';
		}
		$out .= '</div>';
	}
	return $out . '</div></div>';
}
function ps_recordarme_form( $tid, $big = false ) {
	$o  = '<details class="ps-rec' . ( $big ? ' ps-rec-big' : '' ) . '"><summary class="ps-btn ' . ( $big ? '' : 'ps-btn-ghost' ) . '">Recordarme</summary>';
	$o .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_rec', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_recordarme"><input type="hidden" name="t" value="' . (int) $tid . '"><input type="text" name="ps_web" value="" tabindex="-1" autocomplete="off" class="ps-hp" aria-hidden="true">';
	$o .= '<label class="screen-reader-text" for="ps_rm' . (int) $tid . '">Tu correo</label><input id="ps_rm' . (int) $tid . '" type="email" name="email" required placeholder="tu@correo.cl" autocomplete="email"><button class="ps-btn" type="submit">Avísame</button>';
	$o .= '<p class="ps-muted ps-small">Usamos tu correo solo para avisarte de esta transmisión.</p></form></details>';
	return $o;
}

/* -------------------------------------------------------------------------
 * Acciones públicas: preguntar, votar, recordarme
 * ---------------------------------------------------------------------- */
function ps_public_action( $name, $cb ) {
	add_action( 'admin_post_' . $name, $cb );
	add_action( 'admin_post_nopriv_' . $name, $cb );
}
ps_public_action(
	'ps_pregunta',
	function () {
		$tid  = isset( $_POST['t'] ) ? absint( $_POST['t'] ) : 0;
		$back = ps_back();
		$ok   = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_pregunta' ) && empty( $_POST['ps_web'] ) && 'ps_transmision' === get_post_type( $tid ) && ps_live_visible();
		$txt  = isset( $_POST['texto'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['texto'] ) ) ) : '';
		$rate = 'ps_q_' . ps_ip_key();
		if ( ! $ok || '' === $txt || get_transient( $rate ) || 'finalizada' === get_post_meta( $tid, '_ps_estado', true ) ) {
			wp_safe_redirect( add_query_arg( 'ps_q', 'err', $back ) . '#ps-preguntas' );
			exit;
		}
		$nombre = isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '';
		$nombre = $nombre ? mb_substr( preg_split( '/\s+/u', trim( $nombre ) )[0], 0, 20 ) : 'Anónimo';
		$qid    = wp_insert_post( array( 'post_type' => 'ps_pregunta', 'post_status' => 'publish', 'post_title' => mb_substr( $txt, 0, 220 ), 'post_parent' => $tid ) );
		if ( $qid && ! is_wp_error( $qid ) ) {
			update_post_meta( $qid, '_ps_nombre', $nombre );
			update_post_meta( $qid, '_ps_votos', 1 );
			update_post_meta( $qid, '_ps_estado', 'nueva' );
			set_transient( $rate, 1, 30 );
		}
		wp_safe_redirect( add_query_arg( 'ps_q', 'ok', $back ) . '#ps-preguntas' );
		exit;
	}
);
ps_public_action(
	'ps_voto',
	function () {
		$qid = isset( $_POST['q'] ) ? absint( $_POST['q'] ) : 0;
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_voto' ) && 'ps_pregunta' === get_post_type( $qid ) && empty( $_COOKIE[ 'ps_v' . $qid ] ) ) {
			$k = 'ps_v_' . $qid . '_' . ps_ip_key();
			if ( ! get_transient( $k ) ) {
				update_post_meta( $qid, '_ps_votos', (int) get_post_meta( $qid, '_ps_votos', true ) + 1 );
				set_transient( $k, 1, DAY_IN_SECONDS );
				setcookie( 'ps_v' . $qid, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			}
		}
		wp_safe_redirect( ps_back() . '#ps-preguntas' );
		exit;
	}
);
ps_public_action(
	'ps_recordarme',
	function () {
		$tid   = isset( $_POST['t'] ) ? absint( $_POST['t'] ) : 0;
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_rec' ) && empty( $_POST['ps_web'] ) && is_email( $email ) && 'ps_transmision' === get_post_type( $tid ) ) {
			$l = ps_inscritos( $tid );
			if ( ! in_array( strtolower( $email ), $l, true ) && count( $l ) < 5000 ) {
				$l[] = strtolower( $email );
				update_post_meta( $tid, '_ps_inscritos', $l );
			}
		}
		wp_safe_redirect( add_query_arg( 'ps_rec', 1, ps_back() ) );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Iniciar / terminar y avisos
 * ---------------------------------------------------------------------- */
function ps_live_set_estado( $tid, $estado ) {
	if ( 'en-vivo' === $estado ) {
		foreach ( get_posts( array( 'post_type' => 'ps_transmision', 'numberposts' => 20, 'fields' => 'ids', 'post__not_in' => array( $tid ), 'meta_key' => '_ps_estado', 'meta_value' => 'en-vivo' ) ) as $o ) {
			update_post_meta( $o, '_ps_estado', 'finalizada' );
		}
	}
	update_post_meta( $tid, '_ps_estado', $estado );
	if ( 'en-vivo' === $estado ) {
		ps_live_avisar( $tid );
	}
}
function ps_live_avisar( $tid ) {
	if ( ! ps_live_on() || get_post_meta( $tid, '_ps_avisados', true ) ) {
		return 0;
	}
	$n   = 0;
	$url = home_url( '/prevsoul-live/' );
	foreach ( ps_inscritos( $tid ) as $to ) {
		$inner = '<p>¡Estamos en vivo! Empezó <strong>' . esc_html( get_the_title( $tid ) ) . '</strong>.</p><p><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#D92D20;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:700">Ver Prevsoul Live</a></p><p style="font-size:12px;color:#5A6B85">Recibes este correo porque pediste que te avisáramos de esta transmisión.</p>';
		if ( wp_mail( $to, 'En vivo ahora: ' . get_the_title( $tid ), ps_mail_wrap( 'Prevsoul Live', $inner ), array( 'Content-Type: text/html; charset=UTF-8' ) ) ) {
			$n++;
		}
	}
	update_post_meta( $tid, '_ps_avisados', time() );
	return $n;
}
function ps_can_manage_transmision( $tid ) {
	return current_user_can( 'manage_options' ) || ( ps_user_dentista() && (int) get_post_meta( $tid, '_ps_odontologo', true ) === ps_user_dentista() );
}
add_action(
	'admin_post_ps_live_estado',
	function () {
		$tid = isset( $_POST['t'] ) ? absint( $_POST['t'] ) : 0;
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_live_estado_' . $tid ) || 'ps_transmision' !== get_post_type( $tid ) || ! ps_can_manage_transmision( $tid ) ) {
			wp_die( 'No permitido.' );
		}
		if ( isset( $_POST['youtube'] ) ) {
			update_post_meta( $tid, '_ps_youtube', esc_url_raw( wp_unslash( $_POST['youtube'] ) ) );
		}
		$acc = isset( $_POST['accion'] ) ? sanitize_key( $_POST['accion'] ) : '';
		if ( 'iniciar' === $acc ) {
			ps_live_set_estado( $tid, 'en-vivo' );
		} elseif ( 'terminar' === $acc ) {
			ps_live_set_estado( $tid, 'finalizada' );
		}
		wp_safe_redirect( add_query_arg( 'ps_ok', $acc ? $acc : 'guardado', ps_back() ) );
		exit;
	}
);
add_action(
	'admin_post_ps_live_nueva',
	function () {
		$doc = ps_user_dentista();
		if ( ! $doc && current_user_can( 'manage_options' ) && isset( $_POST['o'] ) ) {
			$doc = absint( $_POST['o'] );
		}
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_live_nueva' ) || ! $doc || 'ps_odontologo' !== get_post_type( $doc ) ) {
			wp_die( 'No permitido.' );
		}
		$titulo = isset( $_POST['titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['titulo'] ) ) : '';
		$fecha  = isset( $_POST['fecha'] ) ? sanitize_text_field( wp_unslash( $_POST['fecha'] ) ) : '';
		if ( $titulo && $fecha ) {
			$id = wp_insert_post( array( 'post_type' => 'ps_transmision', 'post_status' => 'publish', 'post_title' => $titulo, 'post_content' => isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '' ) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_ps_fecha', $fecha );
				update_post_meta( $id, '_ps_odontologo', $doc );
				update_post_meta( $id, '_ps_estado', 'programada' );
				update_post_meta( $id, '_ps_publico', isset( $_POST['publico'] ) ? sanitize_text_field( wp_unslash( $_POST['publico'] ) ) : '' );
			}
		}
		wp_safe_redirect( add_query_arg( 'ps_ok', 'programada', ps_back() ) );
		exit;
	}
);
add_action(
	'admin_post_ps_q_estado',
	function () {
		$qid = isset( $_POST['q'] ) ? absint( $_POST['q'] ) : 0;
		$tid = (int) wp_get_post_parent_id( $qid );
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'ps_q_' . $qid ) || 'ps_pregunta' !== get_post_type( $qid ) || ! ps_can_manage_transmision( $tid ) ) {
			wp_die( 'No permitido.' );
		}
		$acc = isset( $_POST['accion'] ) ? sanitize_key( $_POST['accion'] ) : '';
		if ( 'respondida' === $acc ) {
			update_post_meta( $qid, '_ps_estado', 'respondida' );
		} elseif ( 'ocultar' === $acc ) {
			wp_trash_post( $qid );
		}
		wp_safe_redirect( ps_back() . '#ps-dq' );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Panel del dentista: [ps_panel_dentista]
 * ---------------------------------------------------------------------- */
add_shortcode(
	'ps_panel_dentista',
	function () {
		if ( ! is_user_logged_in() ) {
			return '<div class="ps-card ps-panel-login"><h3 class="ps-card-title">Panel del dentista Prevsoul</h3><p class="ps-muted">Ingresa con tu cuenta de Prevsoul para programar tus transmisiones y responder preguntas.</p>' . wp_login_form( array( 'echo' => false, 'redirect' => ps_panel_dentista_url(), 'label_username' => 'Correo o usuario', 'label_log_in' => 'Ingresar' ) ) . '</div>';
		}
		$doc = ps_user_dentista();
		if ( ! $doc && current_user_can( 'manage_options' ) ) {
			$doc = isset( $_GET['odontologo'] ) ? absint( $_GET['odontologo'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! $doc ) {
				$first = get_posts( array( 'post_type' => 'ps_odontologo', 'numberposts' => 1, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC' ) );
				$doc   = $first ? $first[0] : 0;
			}
			$doc = 'ps_odontologo' === get_post_type( $doc ) ? $doc : 0;
		}
		if ( ! $doc ) {
			return '<div class="ps-card"><p>' . ( current_user_can( 'manage_options' ) ? 'Primero crea un odontólogo en Odontólogos.' : 'Tu cuenta todavía no está vinculada a un odontólogo. Escríbele a Prevsoul para activarla.' ) . '</p></div>';
		}
		$admin_view = ! ps_user_dentista();
		$main       = ps_live_main( $doc );
		$estado     = $main ? get_post_meta( $main->ID, '_ps_estado', true ) : '';
		$qs         = $main ? ps_preguntas( $main->ID ) : array();
		$pend       = count( array_filter( $qs, function ( $q ) { return 'respondida' !== get_post_meta( $q->ID, '_ps_estado', true ); } ) );
		$hechas     = count( get_posts( array( 'post_type' => 'ps_transmision', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ps_odontologo', 'value' => $doc ), array( 'key' => '_ps_estado', 'value' => 'finalizada' ) ) ) ) );
		$out        = '<div class="ps-panel">';
		$msgs       = array( 'iniciar' => 'Estás en vivo. ' . ( ps_live_on() ? 'Avisamos por correo a los inscritos.' : 'Prevsoul Live está oculto: no se enviaron avisos.' ), 'terminar' => 'Transmisión terminada. Quedó publicada como grabación.', 'guardado' => 'Enlace guardado.', 'programada' => 'Transmisión programada.' );
		if ( isset( $_GET['ps_ok'] ) && isset( $msgs[ $_GET['ps_ok'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$out .= '<div class="ps-card ps-ok"><strong>' . esc_html( $msgs[ sanitize_key( $_GET['ps_ok'] ) ] ) . '</strong></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( ! ps_live_on() ) {
			$out .= '<div class="ps-card ps-live-hidden"><strong>Prevsoul Live está oculto en el sitio.</strong> Puedes preparar y probar tus transmisiones; los pacientes no las verán hasta que Prevsoul lo active.</div>';
		}
		$out .= '<div class="ps-row ps-panel-head">' . ps_avatar( $doc, 64 ) . '<div><span class="ps-prest-k">Mis transmisiones</span><h2 class="ps-card-title">' . esc_html( get_the_title( $doc ) ) . '</h2>' . ( $admin_view ? '<span class="ps-chip ps-acc">Vista de administrador</span>' : '<a class="ps-small" href="' . esc_url( wp_logout_url( ps_panel_dentista_url() ) ) . '">Cerrar sesión</a>' ) . '</div></div>';
		$out .= '<div class="ps-tiles"><div class="ps-tile"><span>Inscritos</span><strong>' . ( $main ? count( ps_inscritos( $main->ID ) ) : 0 ) . '</strong></div><div class="ps-tile"><span>Preguntas por responder</span><strong>' . (int) $pend . '</strong></div><div class="ps-tile"><span>Transmisiones realizadas</span><strong>' . (int) $hechas . '</strong></div></div>';
		$out .= '<div class="ps-dgrid"><div class="ps-card"><h3 class="ps-card-title">' . ( 'en-vivo' === $estado ? 'Estás en vivo' : 'Mi próxima transmisión' ) . '</h3>';
		if ( $main && 'finalizada' !== $estado ) {
			$out .= '<p><strong>' . esc_html( $main->post_title ) . '</strong><br><span class="ps-muted ps-small">' . esc_html( ps_fmt_date( get_post_meta( $main->ID, '_ps_fecha', true ) ) ) . '</span></p>';
			$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ps-dform">' . wp_nonce_field( 'ps_live_estado_' . $main->ID, '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_live_estado"><input type="hidden" name="t" value="' . (int) $main->ID . '">';
			$out .= '<label class="ps-small">Enlace de YouTube<input type="url" name="youtube" value="' . esc_attr( get_post_meta( $main->ID, '_ps_youtube', true ) ) . '" placeholder="https://www.youtube.com/live/…"></label>';
			$out .= 'en-vivo' === $estado ? '<button class="ps-btn ps-btn-stop" type="submit" name="accion" value="terminar">Terminar transmisión</button>' : '<button class="ps-btn ps-btn-rec" type="submit" name="accion" value="iniciar">Iniciar transmisión</button>';
			$out .= '<button class="ps-btn ps-btn-ghost" type="submit" name="accion" value="">Guardar enlace</button></form>';
			$out .= '<p class="ps-muted ps-small">Transmite desde YouTube Studio en el computador (u OBS) y pega aquí el enlace. Al iniciar, la página Live muestra «En vivo»' . ( ps_live_on() ? ' y avisamos a los inscritos.' : '.' ) . '</p>';
		} else {
			$out .= '<p class="ps-muted">No tienes transmisiones programadas.</p>';
		}
		$out .= '</div><div class="ps-card"><h3 class="ps-card-title">Programar transmisión</h3><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ps-dform">' . wp_nonce_field( 'ps_live_nueva', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_live_nueva"><input type="hidden" name="o" value="' . (int) $doc . '">';
		$out .= '<label class="ps-small">Título<input name="titulo" required placeholder="Ej: Cepillado sin miedo para niños"></label><label class="ps-small">Fecha y hora<input type="datetime-local" name="fecha" required></label><label class="ps-small">Para quién<input name="publico" placeholder="Ej: Niños y familias"></label><label class="ps-small">De qué tratará<textarea name="desc" rows="2"></textarea></label><button class="ps-btn" type="submit">Programar</button></form></div></div>';
		$out .= '<div class="ps-card" id="ps-dq"><h3 class="ps-card-title">Preguntas del público' . ( $main ? ' · ' . esc_html( $main->post_title ) : '' ) . '</h3>';
		if ( $qs ) {
			$out .= '<ul class="ps-qlist">';
			foreach ( $qs as $q ) {
				$ok   = 'respondida' === get_post_meta( $q->ID, '_ps_estado', true );
				$f    = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( 'ps_q_' . $q->ID, '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ps_q_estado"><input type="hidden" name="q" value="' . (int) $q->ID . '">';
				$out .= '<li class="' . ( $ok ? 'is-ok' : '' ) . '"><span>' . esc_html( $q->post_title ) . '<small>' . esc_html( get_post_meta( $q->ID, '_ps_nombre', true ) ) . ' · ' . (int) get_post_meta( $q->ID, '_ps_votos', true ) . ' apoyos</small></span>' . $f . ( $ok ? '<span class="ps-chip ps-good">Respondida</span>' : '<button class="ps-vote" type="submit" name="accion" value="respondida">Respondida</button>' ) . '<button class="ps-vote ps-vote-x" type="submit" name="accion" value="ocultar" aria-label="Ocultar pregunta">Ocultar</button></form></li>';
			}
			$out .= '</ul>';
		} else {
			$out .= '<p class="ps-muted ps-small">Aún no hay preguntas.</p>';
		}
		return $out . '</div></div>';
	}
);


/* -------------------------------------------------------------------------
 * 1.9: botón «Prevsoul Live» del menú con punto verde (transmitiendo) o gris
 * ---------------------------------------------------------------------- */
function ps_live_now() {
	static $now = null;
	if ( null === $now ) {
		$now = (bool) get_posts( array( 'post_type' => 'ps_transmision', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_ps_estado', 'meta_value' => 'en-vivo' ) );
	}
	return $now;
}
add_filter(
	'nav_menu_item_title',
	function ( $title, $item ) {
		if ( ps_is_live_url( $item->url ) && false === strpos( $title, 'ps-menu-dot' ) ) {
			$title .= '<i class="ps-sala-dot ps-menu-dot' . ( ps_live_now() ? '' : ' off' ) . '" aria-hidden="true"></i>';
		}
		return $title;
	},
	10,
	2
);
add_filter(
	'nav_menu_link_attributes',
	function ( $atts, $item ) {
		if ( ps_is_live_url( $item->url ) ) {
			$atts['title']      = ps_live_now() ? 'Prevsoul Live · en vivo ahora' : 'Prevsoul Live · sin transmisión en este momento';
			$atts['aria-label'] = $atts['title'];
		}
		return $atts;
	},
	10,
	2
);
add_action(
	'init',
	function () {
		if ( get_option( 'ps_menu_live_full' ) ) {
			return;
		}
		update_option( 'ps_menu_live_full', 1 );
		$menu = wp_get_nav_menu_object( 'Menú principal Prevsoul' );
		if ( ! $menu ) {
			return;
		}
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $it ) {
			if ( 'Live' === $it->title ) {
				wp_update_post( array( 'ID' => $it->ID, 'post_title' => 'Prevsoul Live' ) );
			}
		}
	},
	45
);
