<?php
/**
 * SEO del Blog Prevsoul: título y descripción, resumen con palabra clave, preguntas frecuentes,
 * enlace interno, firma de revisión, Open Graph y datos estructurados.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

const PS_REVISOR       = 'Dr. Felipe Alarcón Villarroel';
const PS_REVISOR_TITLE = 'Cirujano Dentista';

function ps_blog_seo_data() {
	static $d = null;
	if ( null === $d ) {
		$f = plugin_dir_path( PS_FILE ) . 'blog/seo.php';
		$d = file_exists( $f ) ? require $f : array();
	}
	return $d;
}

/** Datos SEO del artículo que se está viendo, o null. */
function ps_blog_seo_actual() {
	if ( ! is_singular( 'post' ) ) {
		return null;
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	$d    = ps_blog_seo_data();
	return isset( $d[ $slug ] ) ? $d[ $slug ] + array( 'slug' => $slug ) : null;
}

function ps_es_blog_portada() {
	return is_page( 'blog' );
}

function ps_og_img( $slug ) {
	$rel = 'assets/og/' . $slug . '.jpg';
	return file_exists( plugin_dir_path( PS_FILE ) . $rel ) ? plugins_url( $rel, PS_FILE ) : '';
}

/* Título de la pestaña y de Google. */
add_filter(
	'pre_get_document_title',
	function ( $t ) {
		$s = ps_blog_seo_actual();
		if ( $s ) {
			return $s['st'];
		}
		if ( ps_es_blog_portada() ) {
			return 'Blog Prevsoul: prevención dental explicada con evidencia';
		}
		return $t;
	},
	20
);

/* La descripción del tema se reemplaza por la nuestra en el blog. */
add_action(
	'wp',
	function () {
		if ( ps_blog_seo_actual() || ps_es_blog_portada() ) {
			remove_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );
		}
	}
);

add_action(
	'wp_head',
	function () {
		$s = ps_blog_seo_actual();
		if ( ! $s && ! ps_es_blog_portada() ) {
			return;
		}
		$site = 'Prevsoul';
		if ( $s ) {
			$id    = get_queried_object_id();
			$url   = get_permalink( $id );
			$title = $s['st'];
			$desc  = $s['md'];
			$img   = ps_og_img( $s['slug'] );
			$type  = 'article';
		} else {
			$id    = get_queried_object_id();
			$url   = get_permalink( $id );
			$title = 'Blog Prevsoul: prevención dental explicada con evidencia';
			$desc  = 'Artículos sobre caries, tapaduras, implantes y prevención dental, escritos con evidencia científica y revisados por un cirujano dentista.';
			$img   = ps_og_img( 'blog' );
			$type  = 'website';
		}
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:locale" content="es_CL">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		if ( $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
			echo '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		if ( $img ) {
			echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
		}
		if ( $s ) {
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $id ) ) . '">' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $id ) ) . '">' . "\n";
		}
		echo '<script type="application/ld+json">' . wp_json_encode( ps_blog_schema( $s, $id, $url, $title, $desc, $img ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	},
	3
);

function ps_blog_schema( $s, $id, $url, $title, $desc, $img ) {
	$home = home_url( '/' );
	$blog = get_page_by_path( 'blog' );
	$burl = $blog ? get_permalink( $blog ) : home_url( '/blog/' );
	$org  = array(
		'@type' => 'Organization',
		'@id'   => $home . '#organization',
		'name'  => 'Prevsoul',
		'url'   => $home,
		'logo'  => array( '@type' => 'ImageObject', 'url' => ps_og_img( 'blog' ) ),
	);
	if ( function_exists( 'ps_mk_same_as' ) && ps_mk_same_as() ) {
		$org['sameAs'] = ps_mk_same_as();
	}
	$rev  = array(
		'@type'    => 'Person',
		'@id'      => $home . '#revisor',
		'name'     => PS_REVISOR,
		'jobTitle' => PS_REVISOR_TITLE,
		'worksFor' => array( '@id' => $home . '#organization' ),
	);
	if ( ! $s ) {
		$items = array();
		foreach ( ps_blog_posts() as $i => $p ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $p ), 'name' => get_the_title( $p ) );
		}
		return array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				$org,
				array( '@type' => 'CollectionPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => $title, 'description' => $desc, 'inLanguage' => 'es-CL', 'publisher' => array( '@id' => $home . '#organization' ), 'mainEntity' => array( '@type' => 'ItemList', 'itemListElement' => $items ) ),
			),
		);
	}
	$etapa = ps_blog_etapa( $id );
	$cat   = get_term_by( 'slug', PS_BLOG_ETAPAS[ $etapa ][0], 'category' );
	$faq   = array();
	foreach ( $s['faq'] as $q ) {
		$faq[] = array( '@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q[1] ) );
	}
	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			$org,
			$rev,
			array(
				'@type'         => 'WebPage',
				'@id'           => $url . '#webpage',
				'url'           => $url,
				'name'          => $title,
				'description'   => $desc,
				'inLanguage'    => 'es-CL',
				'reviewedBy'    => array( '@id' => $home . '#revisor' ),
				'lastReviewed'  => get_the_modified_date( 'Y-m-d', $id ),
				'breadcrumb'    => array( '@id' => $url . '#breadcrumb' ),
			),
			array(
				'@type'            => 'BlogPosting',
				'@id'              => $url . '#article',
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'headline'         => get_the_title( $id ),
				'description'      => $desc,
				'image'            => $img ? array( $img ) : array(),
				'datePublished'    => get_the_date( 'c', $id ),
				'dateModified'     => get_the_modified_date( 'c', $id ),
				'author'           => array( '@id' => $home . '#organization' ),
				'publisher'        => array( '@id' => $home . '#organization' ),
				'keywords'         => $s['kw'],
				'articleSection'   => PS_BLOG_ETAPAS[ $etapa ][1],
				'inLanguage'       => 'es-CL',
			),
			array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => array(
					array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Blog', 'item' => $burl ),
					array( '@type' => 'ListItem', 'position' => 2, 'name' => PS_BLOG_ETAPAS[ $etapa ][1], 'item' => $cat ? get_term_link( $cat ) : $burl ),
					array( '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title( $id ) ),
				),
			),
			array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => $faq ),
		),
	);
}

/* Resumen con la palabra clave, enlace interno y preguntas frecuentes (antes del cierre del artículo). */
add_filter(
	'the_content',
	function ( $content ) {
		$s = ps_blog_seo_actual();
		if ( ! $s || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$lead = '<p class="ps-blog-lead"><strong>En pocas palabras:</strong> ' . esc_html( $s['md'] ) . '</p>';
		// Enlace interno contextual después del segundo párrafo.
		if ( ! empty( $s['link'] ) ) {
			$p = get_page_by_path( $s['link'][0], OBJECT, 'post' );
			if ( $p ) {
				$rel = '<p class="ps-blog-link">Te puede interesar: <a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $s['link'][1] ) . '</a>.</p>';
				$n   = 0;
				$content = preg_replace_callback(
					'#</p>#',
					function ( $m ) use ( &$n, $rel ) {
						$n++;
						return 2 === $n ? '</p>' . $rel : '</p>';
					},
					$content
				);
			}
		}
		$faq = '<section class="ps-blog-faq"><h2>Preguntas frecuentes</h2>';
		foreach ( $s['faq'] as $q ) {
			$faq .= '<details><summary>' . esc_html( $q[0] ) . '</summary><p>' . esc_html( $q[1] ) . '</p></details>';
		}
		$faq .= '</section>';
		// Las preguntas van antes de las fuentes.
		if ( false !== strpos( $content, '<div class="ps-blog-src">' ) ) {
			$content = str_replace( '<div class="ps-blog-src">', $faq . '<div class="ps-blog-src">', $content );
		} else {
			$content .= $faq;
		}
		return $lead . $content;
	},
	15
);

/* Firma de revisión en la línea bajo el título. */
add_filter(
	'ps_blog_meta_line',
	function ( $line, $id ) {
		$m = (int) get_post_meta( $id, '_ps_min', true );
		return ( $m ? $m . ' min de lectura · ' : '' ) . 'Revisado por ' . PS_REVISOR . ', ' . PS_REVISOR_TITLE . ' · Actualizado el ' . get_the_modified_date( 'j \d\e F \d\e Y', $id );
	},
	10,
	2
);
