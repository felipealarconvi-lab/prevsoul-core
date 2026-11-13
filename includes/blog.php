<?php
/**
 * Blog Prevsoul: 14 artículos fundamentados en el estudio «La historia de un diente»,
 * portada con filtros por etapa, diseño de artículo y columna en el pie de página.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

const PS_BLOG_ETAPAS = array(
	'Entender' => array( 'entender-la-caries', 'Entender la caries' ),
	'Comparar' => array( 'comparar-caminos', 'Comparar caminos' ),
	'Decidir'  => array( 'decidir-con-informacion', 'Decidir con información' ),
);
const PS_BLOG_DESTACADOS = array( 'la-mancha-blanca', 'tu-primera-tapadura-no-es-el-final', 'cuanto-cuesta-perder-una-muela-en-chile' );

function ps_blog_articulos() {
	$all = array();
	foreach ( array( 1, 2, 3 ) as $n ) {
		$f = plugin_dir_path( PS_FILE ) . 'blog/articulos-' . $n . '.php';
		if ( file_exists( $f ) ) {
			$all = array_merge( $all, require $f );
		}
	}
	return $all;
}

function ps_blog_etapa( $post_id ) {
	$e = get_post_meta( $post_id, '_ps_etapa', true );
	return isset( PS_BLOG_ETAPAS[ $e ] ) ? $e : 'Entender';
}

/* Publicación única: categorías, artículos y página Blog. */
add_action(
	'init',
	function () {
		if ( get_option( 'ps_blog_v1' ) ) {
			return;
		}
		update_option( 'ps_blog_v1', 1 );
		$parent = term_exists( 'blog', 'category' );
		$parent = $parent ? (int) $parent['term_id'] : (int) ( wp_insert_term( 'Blog', 'category', array( 'slug' => 'blog' ) )['term_id'] ?? 0 );
		$cats   = array();
		foreach ( PS_BLOG_ETAPAS as $k => $e ) {
			$t          = term_exists( $e[0], 'category' );
			$cats[ $k ] = $t ? (int) $t['term_id'] : (int) ( wp_insert_term( $e[1], 'category', array( 'slug' => $e[0], 'parent' => $parent ) )['term_id'] ?? 0 );
		}
		$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
		$author = $admins ? (int) $admins[0] : 1;
		$time   = current_time( 'timestamp' );
		kses_remove_filters();
		foreach ( ps_blog_articulos() as $i => $a ) {
			if ( get_page_by_path( $a['slug'], OBJECT, 'post' ) ) {
				continue;
			}
			$src = '<div class="ps-blog-src"><h2>Fuentes</h2><ul><li>' . implode( '</li><li>', $a['fuentes'] ) . '</li></ul></div>';
			$id  = wp_insert_post(
				array(
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => $a['title'],
					'post_name'     => $a['slug'],
					'post_excerpt'  => $a['excerpt'],
					'post_content'  => '<!-- wp:html -->' . $a['html'] . $src . '<!-- /wp:html -->',
					'post_author'   => $author,
					'post_category' => array_filter( array( $cats[ $a['etapa'] ] ?? 0 ) ),
					'post_date'     => wp_date( 'Y-m-d H:i:s', $time - ( 20 - $i ) * HOUR_IN_SECONDS ),
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_ps_etapa', $a['etapa'] );
				update_post_meta( $id, '_ps_min', (int) $a['min'] );
				update_post_meta( $id, '_ps_orden', $i + 1 );
			}
		}
		kses_init_filters();
		if ( ! get_page_by_path( 'blog' ) ) {
			$pid = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Blog',
					'post_name'    => 'blog',
					'post_content' => ps_sc( '[ps_cabecera titulo="Blog Prevsoul" bajada="Prevención explicada con evidencia: por qué cuidar hoy evita tratamientos mañana."]' ) . ps_sc( '[ps_seccion fondo="blanco"][ps_blog][/ps_seccion]' ),
				)
			);
			if ( $pid && ! is_wp_error( $pid ) ) {
				update_post_meta( $pid, '_wp_page_template', PS_TEMPLATE );
			}
		}
	},
	85
);

function ps_blog_posts( $args = array() ) {
	return get_posts(
		array_merge(
			array(
				'post_type'   => 'post',
				'numberposts' => 50,
				'meta_key'    => '_ps_orden', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'     => 'meta_value_num',
				'order'       => 'ASC',
			),
			$args
		)
	);
}

function ps_blog_card( $p ) {
	$e = ps_blog_etapa( $p->ID );
	$m = (int) get_post_meta( $p->ID, '_ps_min', true );
	return '<article class="ps-blog-card" data-etapa="' . esc_attr( $e ) . '"><div class="ps-blog-tag"><span class="ps-et-' . esc_attr( strtolower( $e ) ) . '">' . esc_html( PS_BLOG_ETAPAS[ $e ][1] ) . '</span>' . ( $m ? '<span>' . $m . ' min</span>' : '' ) . '</div><h3><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></h3><p>' . esc_html( get_the_excerpt( $p ) ) . '</p><a class="ps-blog-more" href="' . esc_url( get_permalink( $p ) ) . '" aria-hidden="true" tabindex="-1">Leer artículo →</a></article>';
}

/** [ps_blog] Portada con filtros por etapa. */
function ps_blog() {
	$posts = ps_blog_posts();
	if ( ! $posts ) {
		return '<p class="ps-muted">Pronto publicaremos los primeros artículos.</p>';
	}
	$o = '<div class="ps-blog"><div class="ps-blog-chips" role="group" aria-label="Filtrar por etapa"><button type="button" class="is-on" data-etapa="" aria-pressed="true">Todos (' . count( $posts ) . ')</button>';
	foreach ( PS_BLOG_ETAPAS as $k => $e ) {
		$o .= '<button type="button" data-etapa="' . esc_attr( $k ) . '" aria-pressed="false">' . esc_html( $e[1] ) . '</button>';
	}
	$o .= '</div><div class="ps-blog-grid">';
	foreach ( $posts as $p ) {
		$o .= ps_blog_card( $p );
	}
	$o .= '</div></div>';
	$o .= "<script>(function(){function go(){document.querySelectorAll('.ps-blog').forEach(function(w){w.querySelectorAll('.ps-blog-chips button').forEach(function(b){b.addEventListener('click',function(){var e=b.getAttribute('data-etapa');w.querySelectorAll('.ps-blog-chips button').forEach(function(x){var on=x===b;x.classList.toggle('is-on',on);x.setAttribute('aria-pressed',on?'true':'false')});w.querySelectorAll('.ps-blog-card').forEach(function(c){c.hidden=!!e&&c.getAttribute('data-etapa')!==e})})})})}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',go)}else{go()}})();</script>";
	return $o;
}
add_action(
	'init',
	function () {
		add_shortcode( 'ps_blog', 'ps_blog' );
	},
	36
);

/* Artículos con la plantilla Prevsoul. */
add_filter(
	'template_include',
	function ( $t ) {
		return is_singular( 'post' ) ? plugin_dir_path( PS_FILE ) . 'templates/' . PS_TEMPLATE : $t;
	},
	21
);

/* Encabezado, llamado a la acción, aviso y «sigue leyendo» en cada artículo. */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id    = get_the_ID();
		$e     = ps_blog_etapa( $id );
		$m     = (int) get_post_meta( $id, '_ps_min', true );
		$blog  = get_page_by_path( 'blog' );
		$burl  = $blog ? get_permalink( $blog ) : home_url( '/blog/' );
		$planes = get_page_by_path( 'planes-dentales' );
		$head  = '<nav class="ps-blog-bc" aria-label="Migas"><a href="' . esc_url( $burl ) . '">Blog</a> › ' . esc_html( PS_BLOG_ETAPAS[ $e ][1] ) . '</nav><h1 class="ps-blog-h1">' . esc_html( get_the_title() ) . '</h1><p class="ps-blog-meta">' . esc_html( apply_filters( 'ps_blog_meta_line', ( $m ? $m . ' min de lectura · ' : '' ) . 'Equipo odontológico Prevsoul', $id ) ) . '</p>';
		$cta   = '<div class="ps-blog-cta"><div><strong>Corta la espiral en la fase 0</strong><span>Controles y detección temprana con tu odontólogo de cabecera.</span></div><a class="ps-btn" href="' . esc_url( $planes ? get_permalink( $planes ) : home_url( '/planes-dentales/' ) ) . '">Ver planes</a><a class="ps-btn ps-btn-ghost" href="' . esc_url( ps_get( 'sala_url' ) ) . '">Consulta online gratis</a></div><p class="ps-blog-aviso">Contenido informativo basado en evidencia. No reemplaza una evaluación odontológica personal.</p>';
		$rel   = ps_blog_posts( array( 'numberposts' => 2, 'post__not_in' => array( $id ), 'meta_query' => array( array( 'key' => '_ps_etapa', 'value' => $e ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$more  = '';
		if ( $rel ) {
			$more = '<div class="ps-blog-rel"><h2>Sigue leyendo</h2><div class="ps-blog-grid">';
			foreach ( $rel as $p ) {
				$more .= ps_blog_card( $p );
			}
			$more .= '</div></div>';
		}
		return '<section class="ps-sec ps-bg-blanco"><div class="ps-wrap"><article class="ps-blog-art">' . $head . '<div class="ps-blog-body">' . $content . '</div>' . $cta . $more . '</article></div></section>';
	},
	20
);

/** Columna del pie de página. */
function ps_blog_footer() {
	$blog = get_page_by_path( 'blog' );
	if ( ! $blog || 'publish' !== $blog->post_status ) {
		return '';
	}
	$o = '<div class="ps-foot-blog"><strong class="ps-foot-h">Blog Prevsoul</strong><ul>';
	foreach ( PS_BLOG_DESTACADOS as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p && 'publish' === $p->post_status ) {
			$o .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></li>';
		}
	}
	return $o . '<li><a class="ps-foot-all" href="' . esc_url( get_permalink( $blog ) ) . '">Ver todos los artículos →</a></li></ul></div>';
}
