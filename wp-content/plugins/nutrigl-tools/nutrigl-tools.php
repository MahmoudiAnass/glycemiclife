<?php
/**
 * Plugin Name: NutriGL Tools
 * Plugin URI:  https://nutriglinsight.com
 * Description: Interactive Glycemic Load calculator and searchable GI/GL food database, delivered as WordPress shortcodes. Fast (no jQuery), accessible, SEO-friendly.
 * Version:     1.0.0
 * Author:      NutriGL Insight
 * Author URI:  https://nutriglinsight.com
 * License:     GPL v2 or later
 * Text Domain: nutrigl-tools
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NUTRIGL_TOOLS_VERSION', '1.0.0' );
define( 'NUTRIGL_TOOLS_FILE', __FILE__ );
define( 'NUTRIGL_TOOLS_DIR', plugin_dir_path( __FILE__ ) );
define( 'NUTRIGL_TOOLS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load foods once. Cached in memory per request.
 *
 * @return array<int, array<string, mixed>>
 */
function nutrigl_tools_get_foods() {
	static $foods = null;
	if ( null !== $foods ) {
		return $foods;
	}
	$file = NUTRIGL_TOOLS_DIR . 'data/foods.json';
	if ( ! file_exists( $file ) ) {
		$foods = array();
		return $foods;
	}
	$raw   = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$decoded = json_decode( $raw, true );
	$foods = is_array( $decoded ) ? $decoded : array();
	return $foods;
}

/**
 * Enqueue tool assets only when a page uses a tool shortcode.
 */
function nutrigl_tools_maybe_enqueue() {
	if ( ! is_singular() ) {
		return;
	}
	global $post;
	if ( ! $post ) {
		return;
	}
	$has_calc = has_shortcode( $post->post_content, 'gl_calculator' );
	$has_db   = has_shortcode( $post->post_content, 'gl_database' );
	if ( ! $has_calc && ! $has_db ) {
		return;
	}

	// Inline food dataset (avoids extra HTTP request; already tiny once gzipped).
	$foods = nutrigl_tools_get_foods();
	wp_register_script( 'nutrigl-tools-data', '', array(), NUTRIGL_TOOLS_VERSION, true );
	wp_enqueue_script( 'nutrigl-tools-data' );
	wp_add_inline_script(
		'nutrigl-tools-data',
		'window.NUTRIGL_FOODS = ' . wp_json_encode( $foods ) . ';',
		'before'
	);

	if ( $has_calc ) {
		wp_enqueue_script(
			'nutrigl-tools-calculator',
			NUTRIGL_TOOLS_URL . 'assets/js/calculator.js',
			array( 'nutrigl-tools-data' ),
			NUTRIGL_TOOLS_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
	if ( $has_db ) {
		wp_enqueue_script(
			'nutrigl-tools-database',
			NUTRIGL_TOOLS_URL . 'assets/js/database.js',
			array( 'nutrigl-tools-data' ),
			NUTRIGL_TOOLS_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nutrigl_tools_maybe_enqueue' );

/**
 * Shortcode: [gl_calculator]
 */
function nutrigl_tools_calculator_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'    => 'Glycemic Load Calculator',
			'subtitle' => 'Pick a food, enter the amount in grams, and see the Glycemic Load instantly.',
		),
		$atts,
		'gl_calculator'
	);

	$foods = nutrigl_tools_get_foods();

	ob_start();
	?>
	<section class="gl-tool gl-tool--calculator" id="gl-calculator" itemscope itemtype="https://schema.org/WebApplication">
		<meta itemprop="name" content="Glycemic Load Calculator">
		<meta itemprop="applicationCategory" content="HealthApplication">
		<meta itemprop="operatingSystem" content="Web">
		<meta itemprop="url" content="<?php echo esc_url( home_url( '/calculator/' ) ); ?>">

		<h2 class="gl-tool__title"><?php echo esc_html( $atts['title'] ); ?></h2>
		<p class="gl-tool__sub"><?php echo esc_html( $atts['subtitle'] ); ?></p>

		<div class="gl-field">
			<label for="glc-food">Food</label>
			<select id="glc-food" class="gl-select" aria-describedby="glc-food-help">
				<option value="">— Choose a food —</option>
				<?php
				$grouped = array();
				foreach ( $foods as $idx => $f ) {
					$cat = isset( $f['category'] ) ? $f['category'] : 'Other';
					$grouped[ $cat ][] = array( 'idx' => $idx, 'food' => $f );
				}
				ksort( $grouped );
				foreach ( $grouped as $cat => $items ) {
					echo '<optgroup label="' . esc_attr( $cat ) . '">';
					foreach ( $items as $i ) {
						$f = $i['food'];
						echo '<option value="' . (int) $i['idx'] . '" data-gi="' . esc_attr( $f['gi'] ) . '" data-carbs="' . esc_attr( $f['carbs_per_100g'] ) . '">';
						echo esc_html( $f['name'] );
						echo '</option>';
					}
					echo '</optgroup>';
				}
				?>
			</select>
			<small id="glc-food-help" class="screen-reader-text">Choose a food from the list.</small>
		</div>

		<div class="gl-field">
			<label for="glc-grams">Serving size (grams)</label>
			<input id="glc-grams" class="gl-input" type="number" min="1" max="2000" step="1" value="100" inputmode="numeric">
		</div>

		<div class="gl-result" role="status" aria-live="polite">
			<div class="gl-result__cell">
				<span class="gl-result__label">Glycemic Index</span>
				<span id="glc-out-gi" class="gl-result__value">—</span>
			</div>
			<div class="gl-result__cell">
				<span class="gl-result__label">Net Carbs</span>
				<span id="glc-out-carbs" class="gl-result__value">—</span>
			</div>
			<div class="gl-result__cell">
				<span class="gl-result__label">Glycemic Load</span>
				<span id="glc-out-gl" class="gl-result__value">—</span>
			</div>
			<p id="glc-hint" class="gl-result__hint">Select a food and enter a serving size to see the result.</p>
		</div>
	</section>
	<?php
	return trim( ob_get_clean() );
}
add_shortcode( 'gl_calculator', 'nutrigl_tools_calculator_shortcode' );

/**
 * Shortcode: [gl_database]
 */
function nutrigl_tools_database_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'    => 'GI &amp; GL Database — 100 Common Foods',
			'subtitle' => 'Search or filter by category. Sort by Glycemic Index or Glycemic Load per typical serving.',
		),
		$atts,
		'gl_database'
	);

	$foods = nutrigl_tools_get_foods();

	// Build category list.
	$categories = array();
	foreach ( $foods as $f ) {
		if ( ! empty( $f['category'] ) ) {
			$categories[ $f['category'] ] = true;
		}
	}
	ksort( $categories );

	ob_start();
	?>
	<section class="gl-tool gl-tool--database" id="gl-database">
		<h2 class="gl-tool__title"><?php echo wp_kses_post( $atts['title'] ); ?></h2>
		<p class="gl-tool__sub"><?php echo esc_html( $atts['subtitle'] ); ?></p>

		<div class="gl-db__controls">
			<input id="gldb-search" class="gl-input" type="search" placeholder="Search foods (e.g. banana, rice, oats)…" aria-label="Search foods">
			<select id="gldb-cat" class="gl-select" aria-label="Filter by category">
				<option value="">All categories</option>
				<?php foreach ( array_keys( $categories ) as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="gl-db__table-wrap">
			<table class="gl-db__table" aria-describedby="gldb-stats">
				<thead>
					<tr>
						<th data-sort="name"    aria-sort="ascending" scope="col">Food</th>
						<th data-sort="cat"     scope="col">Category</th>
						<th data-sort="gi"      scope="col" class="gl-num">GI</th>
						<th data-sort="serving" scope="col">Serving</th>
						<th data-sort="gl"      scope="col" class="gl-num">GL / serving</th>
					</tr>
				</thead>
				<tbody id="gldb-body">
					<!-- Populated by JS. Server-side fallback below for SEO / no-JS. -->
					<?php foreach ( $foods as $f ) :
						$gl = round( ( (float) $f['gi'] * (float) $f['serving_carbs'] ) / 100, 1 );
						$gl_class = $gl < 10 ? 'low' : ( $gl < 20 ? 'med' : 'high' );
						$gi_class = $f['gi'] < 55 ? 'low' : ( $f['gi'] < 70 ? 'med' : 'high' );
						?>
						<tr data-name="<?php echo esc_attr( strtolower( $f['name'] ) ); ?>"
							data-cat="<?php echo esc_attr( $f['category'] ); ?>"
							data-gi="<?php echo esc_attr( $f['gi'] ); ?>"
							data-gl="<?php echo esc_attr( $gl ); ?>"
							data-serving-g="<?php echo esc_attr( $f['serving_g'] ); ?>">
							<td><?php echo esc_html( $f['name'] ); ?></td>
							<td><?php echo esc_html( $f['category'] ); ?></td>
							<td class="gl-num"><span class="gl-badge gl-badge--<?php echo esc_attr( $gi_class ); ?>"><?php echo (int) $f['gi']; ?></span></td>
							<td><?php echo esc_html( $f['serving_g'] ); ?> g</td>
							<td class="gl-num"><span class="gl-badge gl-badge--<?php echo esc_attr( $gl_class ); ?>"><?php echo esc_html( $gl ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p id="gldb-stats" class="gl-db__stats" aria-live="polite"><?php echo count( $foods ); ?> foods listed.</p>
	</section>
	<?php
	return trim( ob_get_clean() );
}
add_shortcode( 'gl_database', 'nutrigl_tools_database_shortcode' );
