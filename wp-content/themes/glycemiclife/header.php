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
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-BEYHP9JREJ"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'G-BEYHP9JREJ');
	</script>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip-link screen-reader-text" href="#content">Skip to content</a>

<header class="site-header" role="banner">
	<div class="site-header__inner">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-title site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<img class="site-logo__img" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="GlycemicLife" width="40" height="40">
					<span class="site-title__text">Glycemic<em>Life</em></span>
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

		<div class="header-actions">
			<?php echo do_shortcode( '[nutrigl_account]' ); ?>
			<?php
			$header_app_url = add_query_arg(
				array(
					'utm_source'   => 'glycemiclife',
					'utm_medium'   => 'header',
					'utm_campaign' => 'gl_funnel',
				),
				GLYCEMICLIFE_APP_URL
			);
			?>
			<a class="header-cta" href="<?php echo esc_url( $header_app_url ); ?>" rel="noopener" target="_blank">
				Get the App
			</a>
		</div>
	</div>
</header>

<main id="content" class="site-main" role="main">
<?php
/**
 * Fallback menu.
 */
function glycemiclife_default_menu() {
	echo '<ul class="primary-nav__list">';
	echo '<li><a href="' . esc_url( home_url( '/calculator/' ) ) . '">GL Calculator</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/meal-builder/' ) ) . '">Meal Builder</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/gi-database/' ) ) . '">Food Database</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/blog/' ) ) . '">Articles</a></li>';
	echo '</ul>';
}
