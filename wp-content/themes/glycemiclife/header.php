<?php
/**
 * Header template.
 *
 * @package GlycemicLife
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip-link screen-reader-text" href="#content">Skip to content</a>
<header class="site-header" role="banner">
	<div class="container site-header__inner">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<nav class="primary-nav" aria-label="Primary">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'primary-nav__list',
					'fallback_cb'    => 'glycemiclife_default_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
</header>
<main id="content" class="site-main" role="main">
<?php
/**
 * Fallback menu.
 */
function glycemiclife_default_menu() {
	echo '<ul class="primary-nav__list">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/calculator/' ) ) . '">GL Calculator</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/gi-database/' ) ) . '">GI Database</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
	echo '</ul>';
}
