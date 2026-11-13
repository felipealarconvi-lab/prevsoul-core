<?php
/**
 * Plantilla «Prevsoul · diseño completo»: encabezado, contenido a todo el ancho,
 * pie de página y barra de pestañas en el celular, como en el prototipo.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$ps_cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$ps_cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$ps_sala       = ps_get( 'sala_url' );
$ps_tabs       = array(
	'inicio'          => array( 'Inicio', 'M3 11l9-8 9 8v10h-6v-6H9v6H3z' ),
	'atencion-dental' => array( 'Atención', 'M7 3c-2.5 0-4 2-4 4.5 0 3 2 5 2.5 9 .3 2 1.5 2.5 2 1l1-4c.4-1.2 2.6-1.2 3 0l1 4c.5 1.5 1.7 1 2-1 .5-4 2.5-6 2.5-9C21 5 19.5 3 17 3c-1.8 0-3 1-5 1S8.8 3 7 3z' ),
	'packs-dentales'  => array( 'Packs', 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10' ),
	'planes-dentales' => array( 'Planes', 'M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6zM9 12l2 2 4-4' ),
	'prevsoul-live'   => array( 'Live', 'M2 7h20v12H2zM7 3l5 4 5-4' ),
);
if ( function_exists( 'ps_live_visible' ) && ! ps_live_visible() ) {
	unset( $ps_tabs['prevsoul-live'] );
}
$ps_current    = get_post_field( 'post_name', get_queried_object_id() );
if ( (int) get_option( 'page_on_front' ) === get_queried_object_id() ) {
	$ps_current = 'inicio';
}
$ps_icon = function ( $d, $size = 22 ) {
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $d ) . '"></path></svg>';
};
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0B2545">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ps-body' ); ?>>
<?php wp_body_open(); ?>
<a class="ps-skip" href="#ps-main">Saltar al contenido</a>
<header class="ps-head">
	<div class="ps-wrap ps-head-in">
		<a class="ps-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Prevsoul, inicio">
			<?php
			if ( has_custom_logo() ) {
				echo wp_kses_post( wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'medium', false, array( 'class' => 'ps-logo-img', 'alt' => 'Prevsoul' ) ) );
			} else {
				echo ( function_exists( 'ps_logo_svg' ) ? ps_logo_svg( 'header' ) : '' ) . '<span>prevsoul</span>'; // phpcs:ignore
			}
			?>
		</a>
		<span class="ps-sp" aria-hidden="true"></span>
		<nav class="ps-nav" aria-label="Menú principal">
			<?php
			wp_nav_menu(
				array(
					'menu'        => 'Menú principal Prevsoul',
					'container'   => false,
					'menu_class'  => 'ps-nav-list',
					'fallback_cb' => false,
					'depth'       => 1,
				)
			);
			?>
		</nav>
		<span class="ps-sp ps-sp2" aria-hidden="true"></span>
		<span class="ps-vl" aria-hidden="true"></span>
		<div class="ps-head-actions">
		<a class="ps-cart" href="<?php echo esc_url( $ps_cart_url ); ?>" aria-label="<?php echo esc_attr( 'Carro de compras, ' . (int) $ps_cart_count . ' productos' ); ?>">
			<?php echo $ps_icon( 'M6 6h15l-1.5 9h-12zM6 6 5 3H2M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM18 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2z', 21 ); // phpcs:ignore ?>
			<span class="ps-cart-n"><?php echo (int) $ps_cart_count; ?></span>
		</a>
		<?php $ps_open = ps_online_count() > 0; ?>
		<a class="ps-btn ps-head-cta<?php echo $ps_open ? ' is-open' : ''; ?>" href="<?php echo esc_url( $ps_sala ); ?>" aria-label="<?php echo esc_attr( $ps_open ? 'Consulta online, abierta ahora' : 'Consulta online, deja tu consulta' ); ?>">
			<?php echo function_exists( 'ps_icon_video' ) ? ps_icon_video( 20 ) : ''; // phpcs:ignore ?>
			<span class="ps-cta-txt"><span class="ps-cta-main">Consulta online</span><span class="ps-cta-st"><i class="ps-sala-dot<?php echo $ps_open ? '' : ' off'; ?>"></i><?php echo $ps_open ? 'Abierta ahora' : 'Deja tu consulta'; ?></span></span>
			<span class="ps-cta-short">Consulta<i class="ps-sala-dot<?php echo $ps_open ? '' : ' off'; ?>"></i></span>
		</a>
		</div>
	</div>
</header>

<main id="ps-main" class="ps-main">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>

<footer class="ps-foot">
	<div class="ps-wrap ps-foot-in">
		<div>
			<strong class="ps-foot-brand"><?php echo function_exists( 'ps_logo_svg' ) ? ps_logo_svg( 'full', '#fff', '#0B2545' ) : ''; // phpcs:ignore ?>prevsoul</strong>
			<p>Planes dentales preventivos con odontólogo de cabecera.</p>
		</div>
		<nav aria-label="Pie de página">
			<?php
			wp_nav_menu(
				array(
					'menu'        => 'Menú principal Prevsoul',
					'container'   => false,
					'menu_class'  => 'ps-foot-list',
					'fallback_cb' => false,
					'depth'       => 1,
				)
			);
			?>
		</nav>
		<?php echo function_exists( 'ps_blog_footer' ) ? ps_blog_footer() : ''; // phpcs:ignore ?>
		<p class="ps-foot-note">Las precitas entregan orientación informativa y no reemplazan la consulta odontológica presencial.<?php if ( get_privacy_policy_url() ) : ?> · <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Política de privacidad</a><?php endif; ?><br>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Prevsoul</p>
	</div>
</footer>

<nav class="ps-tabbar" aria-label="Navegación">
	<?php foreach ( $ps_tabs as $slug => $t ) : ?>
		<?php
		$pg  = get_page_by_path( $slug );
		$url = $pg ? get_permalink( $pg ) : home_url( '/' );
		$on  = ( $ps_current === $slug );
		?>
		<a href="<?php echo esc_url( $url ); ?>" class="<?php echo $on ? 'on' : ''; ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo $ps_icon( $t[1] ); // phpcs:ignore ?><span><?php echo esc_html( $t[0] ); ?></span><?php if ( 'prevsoul-live' === $slug && function_exists( 'ps_live_now' ) ) : ?><i class="ps-sala-dot ps-tab-dot<?php echo ps_live_now() ? '' : ' off'; ?>"></i><?php endif; ?></a>
	<?php endforeach; ?>
</nav>
<?php wp_footer(); ?>
</body>
</html>
