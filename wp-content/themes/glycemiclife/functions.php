<?php
/**
 * GlycemicLife theme functions.
 *
 * @package GlycemicLife
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GLYCEMICLIFE_VERSION', '2.3.1' );
define( 'GLYCEMICLIFE_DIR', get_template_directory() );
define( 'GLYCEMICLIFE_URI', get_template_directory_uri() );
define( 'GLYCEMICLIFE_APP_URL', 'https://play.google.com/store/apps/details?id=com.oushen.NutriGLInsight' );

/**
 * Theme setup.
 */
function glycemiclife_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 60,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'glycemiclife' ),
			'footer'  => __( 'Footer Menu', 'glycemiclife' ),
		)
	);
}
add_action( 'after_setup_theme', 'glycemiclife_setup' );

/**
 * Fallback favicon (GlycemicLife logo) when no Site Icon is set in Customizer.
 */
function glycemiclife_favicon_fallback() {
	if ( has_site_icon() ) {
		return;
	}
	$logo = GLYCEMICLIFE_URI . '/assets/images/logo.png';
	echo '<link rel="icon" href="' . esc_url( $logo ) . '" sizes="192x192">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $logo ) . '">' . "\n";
}
add_action( 'wp_head', 'glycemiclife_favicon_fallback' );

/**
 * Enqueue assets. Google Fonts (Inter), one CSS file, deferred JS.
 */
function glycemiclife_enqueue_assets() {
	// Inter — modern, free, high-legibility. Preconnect + display=swap for perf.
	wp_enqueue_style(
		'glycemiclife-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'glycemiclife-main',
		GLYCEMICLIFE_URI . '/assets/css/main.css',
		array( 'glycemiclife-fonts' ),
		GLYCEMICLIFE_VERSION
	);

	wp_enqueue_script(
		'glycemiclife-main',
		GLYCEMICLIFE_URI . '/assets/js/main.js',
		array(),
		GLYCEMICLIFE_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'glycemiclife_enqueue_assets' );

/**
 * Preconnect to Google Fonts hosts for faster first paint.
 */
function glycemiclife_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com', 'crossorigin' );
		$urls[] = array( 'href' => 'https://fonts.gstatic.com',    'crossorigin' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'glycemiclife_resource_hints', 10, 2 );

/**
 * Add defer/async fallback for older WP.
 */
function glycemiclife_defer_scripts( $tag, $handle ) {
	if ( 'glycemiclife-main' === $handle && false === strpos( $tag, 'defer' ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'glycemiclife_defer_scripts', 10, 2 );

require_once GLYCEMICLIFE_DIR . '/inc/cleanup.php';
require_once GLYCEMICLIFE_DIR . '/inc/seo.php';
require_once GLYCEMICLIFE_DIR . '/inc/cta.php';

/**
 * Human-readable reading time on posts.
 */
function glycemiclife_reading_time( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$content = get_post_field( 'post_content', $post_id );
	$words   = str_word_count( wp_strip_all_tags( $content ) );
	$minutes = max( 1, (int) ceil( $words / 220 ) );
	/* translators: %d: reading time in minutes. */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'glycemiclife' ), $minutes );
}

/**
 * Reusable post-card markup used across index, archive, and homepage.
 */
function glycemiclife_post_card() {
	$cats      = get_the_category();
	$has_thumb = has_post_thumbnail();
	?>
	<article <?php post_class( 'post-card' ); ?>>
		<a class="post-card__thumb <?php echo $has_thumb ? '' : 'post-card__thumb--placeholder'; ?>" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php if ( $has_thumb ) : ?>
				<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title() ) ) ); ?>
			<?php else : ?>
				<span>🥗</span>
			<?php endif; ?>
		</a>
		<div class="post-card__body">
			<?php if ( $cats ) : ?>
				<a class="post-card__chip" href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
			<?php endif; ?>
			<h3 class="post-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
			<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
			<p class="post-card__meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<span aria-hidden="true">·</span>
				<span><?php echo esc_html( glycemiclife_reading_time() ); ?></span>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Excerpt tweaks.
 */
function glycemiclife_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'glycemiclife_excerpt_length' );

function glycemiclife_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'glycemiclife_excerpt_more' );

/**
 * Register widget area for sidebar (used sparingly).
 */
function glycemiclife_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer Column', 'glycemiclife' ),
			'id'            => 'footer-1',
			'before_widget' => '<div class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'glycemiclife_widgets_init' );
