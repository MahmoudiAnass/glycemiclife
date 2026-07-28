<?php
/**
 * On-page SEO: canonical, meta description, Open Graph, Twitter, JSON-LD schema.
 *
 * Works out of the box; does NOT conflict with Yoast/RankMath if installed later
 * (this only outputs tags when the plugin equivalents are missing).
 *
 * @package GlycemicLife
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True if a known SEO plugin is active. If so, we stand down.
 */
function glycemiclife_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Compute a clean meta description for the current view.
 */
function glycemiclife_meta_description() {
	if ( is_singular() ) {
		$excerpt = get_the_excerpt();
		if ( ! $excerpt ) {
			$excerpt = wp_strip_all_tags( get_the_content() );
		}
		$excerpt = preg_replace( '/\s+/', ' ', $excerpt );
		return wp_html_excerpt( $excerpt, 158, '…' );
	}
	if ( is_home() || is_front_page() ) {
		$tag = get_bloginfo( 'description' );
		return $tag ? $tag : 'Learn Glycemic Load and Glycemic Index. Free GL calculator, GI food database, and evidence-based guides for stable blood sugar and fat loss.';
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
		if ( $desc ) {
			return wp_html_excerpt( wp_strip_all_tags( $desc ), 158, '…' );
		}
		return single_term_title( '', false ) . ' — articles and guides on Glycemic Index & Glycemic Load.';
	}
	return get_bloginfo( 'description' );
}

/**
 * Output <head> SEO tags.
 */
function glycemiclife_head_seo() {
	if ( glycemiclife_seo_plugin_active() ) {
		return;
	}

	$desc  = esc_attr( glycemiclife_meta_description() );
	$url   = esc_url( glycemiclife_current_url() );
	$title = wp_get_document_title();
	$site  = get_bloginfo( 'name' );

	echo "\n\t<!-- GlycemicLife SEO -->\n";
	echo '<meta name="description" content="' . $desc . "\">\n";
	echo '<link rel="canonical" href="' . $url . "\">\n";
	echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">' . "\n";

	// Open Graph.
	$og_type = is_singular( 'post' ) ? 'article' : 'website';
	echo '<meta property="og:type" content="' . esc_attr( $og_type ) . "\">\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . "\">\n";
	echo '<meta property="og:description" content="' . $desc . "\">\n";
	echo '<meta property="og:url" content="' . $url . "\">\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $site ) . "\">\n";
	echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) . "\">\n";

	if ( is_singular() && has_post_thumbnail() ) {
		$img = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img[0] ) . "\">\n";
			echo '<meta property="og:image:width" content="' . (int) $img[1] . "\">\n";
			echo '<meta property="og:image:height" content="' . (int) $img[2] . "\">\n";
		}
	}

	// Twitter.
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . "\">\n";
	echo '<meta name="twitter:description" content="' . $desc . "\">\n";

	// JSON-LD.
	glycemiclife_output_jsonld();
}
add_action( 'wp_head', 'glycemiclife_head_seo', 2 );

/**
 * Current URL helper.
 */
function glycemiclife_current_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return get_term_link( get_queried_object() );
	}
	if ( is_front_page() || is_home() ) {
		return home_url( '/' );
	}
	global $wp;
	return home_url( add_query_arg( array(), $wp->request ) );
}

/**
 * JSON-LD structured data.
 */
function glycemiclife_output_jsonld() {
	$site_url = home_url( '/' );
	$name     = get_bloginfo( 'name' );

	$org = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => $name,
		'url'      => $site_url,
		'logo'     => array(
			'@type' => 'ImageObject',
			'url'   => function_exists( 'get_custom_logo' ) && has_custom_logo()
				? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' )
				: $site_url . 'wp-content/themes/glycemiclife/assets/img/logo.png',
		),
	);

	$website = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => $name,
		'url'             => $site_url,
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $site_url . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_SLASHES ) . "</script>\n";
	echo '<script type="application/ld+json">' . wp_json_encode( $website, JSON_UNESCAPED_SLASHES ) . "</script>\n";

	if ( is_singular( 'post' ) ) {
		$article = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'headline'         => get_the_title(),
			'description'      => glycemiclife_meta_description(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author(),
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => $name,
			),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink(),
			),
		);
		if ( has_post_thumbnail() ) {
			$img = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
			if ( $img ) {
				$article['image'] = array( $img[0] );
			}
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $article, JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}

	if ( is_singular() ) {
		$crumbs = glycemiclife_breadcrumbs_items();
		if ( $crumbs ) {
			$list = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array(),
			);
			foreach ( $crumbs as $i => $c ) {
				$list['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $c['name'],
					'item'     => $c['url'],
				);
			}
			echo '<script type="application/ld+json">' . wp_json_encode( $list, JSON_UNESCAPED_SLASHES ) . "</script>\n";
		}
	}
}

/**
 * Simple breadcrumbs data provider.
 */
function glycemiclife_breadcrumbs_items() {
	$items = array(
		array(
			'name' => 'Home',
			'url'  => home_url( '/' ),
		),
	);
	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$items[] = array(
				'name' => $cats[0]->name,
				'url'  => get_category_link( $cats[0]->term_id ),
			);
		}
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
		return $items;
	}
	if ( is_page() ) {
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
		return $items;
	}
	return array();
}

/**
 * Preconnect / preload for perf.
 */
function glycemiclife_head_perf() {
	echo '<meta name="theme-color" content="#ffffff">' . "\n";
	// No external fonts; system UI stack is used in CSS.
}
add_action( 'wp_head', 'glycemiclife_head_perf', 1 );
