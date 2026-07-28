<?php
/**
 * GlycemicLife theme functions.
 *
 * @package GlycemicLife
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GLYCEMICLIFE_VERSION', '1.0.0' );
define( 'GLYCEMICLIFE_DIR', get_template_directory() );
define( 'GLYCEMICLIFE_URI', get_template_directory_uri() );
define( 'GLYCEMICLIFE_APP_URL', 'https://nutriglinsight.com' );

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
 * Enqueue assets. One CSS file, deferred JS. That's it.
 */
function glycemiclife_enqueue_assets() {
	wp_enqueue_style(
		'glycemiclife-main',
		GLYCEMICLIFE_URI . '/assets/css/main.css',
		array(),
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
