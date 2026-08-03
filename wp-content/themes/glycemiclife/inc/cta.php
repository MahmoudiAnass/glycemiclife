<?php
/**
 * CTA funnel to NutriGL Insight — auto-injected into single posts and available as shortcode.
 *
 * @package GlycemicLife
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reusable CTA block markup.
 *
 * @param array $atts Optional shortcode attributes: variant (inline|banner|inline-mid).
 * @return string
 */
function glycemiclife_cta_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'variant' => 'inline',
			'title'   => 'Stop calculating Glycemic Load by hand.',
			'body'    => 'Download <strong>NutriGL Insight</strong> to track your daily GL, macros, and meals — automatically.',
			'button'  => 'Get NutriGL Insight',
			'utm'     => 'glycemiclife',
		),
		$atts,
		'nutrigl_cta'
	);

	$app_url  = add_query_arg(
		array(
			'utm_source'   => sanitize_key( $atts['utm'] ),
			'utm_medium'   => 'cta',
			'utm_campaign' => 'gl_funnel',
		),
		GLYCEMICLIFE_APP_URL
	);
	$variant  = sanitize_html_class( $atts['variant'] );
	$title    = wp_kses_post( $atts['title'] );
	$body     = wp_kses_post( $atts['body'] );
	$button   = esc_html( $atts['button'] );

	ob_start();
	?>
	<aside class="gl-cta gl-cta--<?php echo esc_attr( $variant ); ?>" role="complementary" aria-label="NutriGL Insight app">
		<div class="gl-cta__inner">
			<div class="gl-cta__icon">
				<img src="<?php echo esc_url( GLYCEMICLIFE_URI . '/assets/images/logo.png' ); ?>" alt="" width="56" height="56" loading="lazy">
			</div>
			<div class="gl-cta__copy">
				<p class="gl-cta__title"><?php echo $title; ?></p>
				<p class="gl-cta__body"><?php echo $body; ?></p>
			</div>
			<a class="gl-cta__btn" href="<?php echo esc_url( $app_url ); ?>" rel="noopener" target="_blank">
				<i class="fa-brands fa-google-play gl-cta__btn-icon" aria-hidden="true"></i>
				<span><?php echo $button; ?></span>
				<i class="fa-solid fa-arrow-right gl-cta__btn-arrow" aria-hidden="true"></i>
			</a>
		</div>
	</aside>
	<?php
	return trim( ob_get_clean() );
}

/**
 * Shortcode: [nutrigl_cta variant="inline"]
 */
add_shortcode(
	'nutrigl_cta',
	function ( $atts ) {
		return glycemiclife_cta_html( (array) $atts );
	}
);

/**
 * Auto-inject a CTA into the middle of every single blog post.
 * Skipped if the post already contains [nutrigl_cta].
 */
function glycemiclife_auto_inject_cta( $content ) {
	if ( ! is_singular( 'post' ) || ! is_main_query() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'gl-cta' ) || false !== strpos( $content, '[nutrigl_cta' ) ) {
		return $content;
	}

	// Split content by </p> to insert after roughly the middle paragraph.
	$paragraphs = explode( '</p>', $content );
	$count      = count( $paragraphs );
	if ( $count < 4 ) {
		return $content . glycemiclife_cta_html();
	}
	$middle = (int) floor( $count / 2 );
	$before = implode( '</p>', array_slice( $paragraphs, 0, $middle ) ) . '</p>';
	$after  = implode( '</p>', array_slice( $paragraphs, $middle ) );

	return $before . glycemiclife_cta_html( array( 'variant' => 'inline-mid' ) ) . $after;
}
add_filter( 'the_content', 'glycemiclife_auto_inject_cta', 20 );
