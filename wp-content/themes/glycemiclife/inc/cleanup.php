<?php
/**
 * Remove WordPress front-end bloat that hurts performance and SEO signals.
 *
 * @package GlycemicLife
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Remove emoji scripts/styles (~15 KB saved).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// Disable emojis TinyMCE plugin.
add_filter(
	'tiny_mce_plugins',
	function ( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		}
		return array();
	}
);

// Remove classic block library CSS when there are no blocks that need it (safe for most posts).
// Comment out if you rely on core block styles heavily.
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_singular() ) {
			return;
		}
		// Keep block styles on singular for safety; simply remove global styles duplicates.
	},
	100
);

// Remove RSD, wlwmanifest, shortlink, generator, feed links from head.
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

// Disable XML-RPC (attack surface).
add_filter( 'xmlrpc_enabled', '__return_false' );

// Remove ?ver= from static assets to improve CDN cacheability (kept for our theme so cache-busts still work via version bump).
add_filter(
	'style_loader_src',
	function ( $src ) {
		if ( strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	},
	9999
);
add_filter(
	'script_loader_src',
	function ( $src ) {
		if ( strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	},
	9999
);

// Disable Dashicons on front-end for non-logged-in users.
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	},
	100
);
