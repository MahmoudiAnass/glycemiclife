<?php
/**
 * Plugin Name: NutriGL Tools
 * Plugin URI:  https://nutriglinsight.com
 * Description: Interactive Glycemic Load calculator + food database + custom visitor auth with strict 1-free / 3-with-signup daily quota.
 * Version:     2.0.0
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

define( 'NUTRIGL_TOOLS_VERSION', '2.0.0' );
define( 'NUTRIGL_TOOLS_FILE', __FILE__ );
define( 'NUTRIGL_TOOLS_DIR', plugin_dir_path( __FILE__ ) );
define( 'NUTRIGL_TOOLS_URL', plugin_dir_url( __FILE__ ) );

require_once NUTRIGL_TOOLS_DIR . 'includes/auth.php';
require_once NUTRIGL_TOOLS_DIR . 'includes/quota.php';
require_once NUTRIGL_TOOLS_DIR . 'includes/rest.php';

if ( is_admin() ) {
	require_once NUTRIGL_TOOLS_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'nutrigl_tools_install_tables' );

/**
 * Load foods once (cached per request).
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
	$raw     = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$decoded = json_decode( $raw, true );
	$foods   = is_array( $decoded ) ? $decoded : array();
	return $foods;
}

/**
 * Map a food name to an emoji. Falls back to category icon, then generic.
 *
 * @param string $name Food name.
 * @param string $cat  Category.
 * @return string Single emoji.
 */
function nutrigl_tools_food_emoji( $name, $cat = '' ) {
	$n = strtolower( $name );
	$map = array(
		'apple' => '🍎', 'banana' => '🍌', 'orange' => '🍊', 'grape' => '🍇',
		'watermelon' => '🍉', 'mango' => '🥭', 'pineapple' => '🍍',
		'strawberr' => '🍓', 'blueberr' => '🫐', 'cherr' => '🍒',
		'pear' => '🍐', 'peach' => '🍑', 'plum' => '🫐', 'kiwi' => '🥝',
		'papaya' => '🍈', 'apricot' => '🍑', 'date' => '🌴', 'raisin' => '🍇',
		'prune' => '🍑', 'grapefruit' => '🍊', 'cantaloupe' => '🍈',
		'lemon' => '🍋', 'avocado' => '🥑',
		'potato' => '🥔', 'fries' => '🍟', 'sweet potato' => '🍠',
		'carrot' => '🥕', 'corn' => '🌽', 'peas' => '🫛',
		'broccoli' => '🥦', 'cauliflower' => '🥬', 'spinach' => '🥬',
		'tomato' => '🍅', 'cucumber' => '🥒', 'pepper' => '🫑',
		'pumpkin' => '🎃', 'beetroot' => '🍠', 'onion' => '🧅',
		'bread' => '🍞', 'sourdough' => '🥖', 'rye' => '🍞',
		'pita' => '🫓', 'tortilla' => '🌯',
		'rice' => '🍚', 'quinoa' => '🌾', 'oat' => '🥣', 'muesli' => '🥣',
		'cornflake' => '🥣', 'barley' => '🌾', 'bulgur' => '🌾', 'couscous' => '🍚',
		'pasta' => '🍝', 'noodle' => '🍜',
		'lentil' => '🫘', 'chickpea' => '🫘', 'bean' => '🫘',
		'hummus' => '🥙', 'tofu' => '🍢', 'edamame' => '🫛',
		'milk' => '🥛', 'yogurt' => '🥛', 'cheese' => '🧀',
		'ice cream' => '🍦', 'chocolate' => '🍫',
		'honey' => '🍯', 'sugar' => '🍬',
		'popcorn' => '🍿', 'chip' => '🍟', 'pretzel' => '🥨',
		'biscuit' => '🍪', 'cookie' => '🍪', 'doughnut' => '🍩',
		'cake' => '🍰', 'muffin' => '🧁', 'granola' => '🌾',
		'juice' => '🧃', 'cola' => '🥤', 'sports' => '🥤', 'coffee' => '☕',
		'peanut' => '🥜', 'cashew' => '🌰', 'almond' => '🌰', 'walnut' => '🌰',
		'pizza' => '🍕', 'sushi' => '🍣', 'falafel' => '🥙',
	);

	foreach ( $map as $needle => $emoji ) {
		if ( false !== strpos( $n, $needle ) ) {
			return $emoji;
		}
	}

	$cat_map = array(
		'Fruits'          => '🍎',
		'Vegetables'      => '🥕',
		'Bread & Grains'  => '🍞',
		'Legumes'         => '🫘',
		'Dairy'           => '🥛',
		'Sweets & Snacks' => '🍪',
		'Beverages'       => '🥤',
		'Nuts & Seeds'    => '🌰',
		'Mixed Meals'     => '🍽️',
	);
	return isset( $cat_map[ $cat ] ) ? $cat_map[ $cat ] : '🥗';
}

/**
 * Enqueue auth CSS + JS on every frontend page (so the modal + account
 * badge work anywhere). Tool assets (calculator/database) load only when
 * their shortcode is present.
 */
function nutrigl_tools_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	// Global auth CSS + JS.
	wp_enqueue_style(
		'nutrigl-tools-auth',
		NUTRIGL_TOOLS_URL . 'assets/css/nutrigl-auth.css',
		array(),
		NUTRIGL_TOOLS_VERSION
	);
	wp_enqueue_script(
		'nutrigl-tools-auth',
		NUTRIGL_TOOLS_URL . 'assets/js/nutrigl-auth.js',
		array(),
		NUTRIGL_TOOLS_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
	wp_add_inline_script(
		'nutrigl-tools-auth',
		'window.NUTRIGL_CFG = ' . wp_json_encode(
			array(
				'rest' => esc_url_raw( rest_url( 'nutrigl/v1/' ) ),
			)
		) . ';',
		'before'
	);

	// Per-shortcode assets.
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
			array( 'nutrigl-tools-auth', 'nutrigl-tools-data' ),
			NUTRIGL_TOOLS_VERSION,
			array( 'strategy' => 'defer', 'in_footer' => true )
		);
	}
	if ( $has_db ) {
		wp_enqueue_script(
			'nutrigl-tools-database',
			NUTRIGL_TOOLS_URL . 'assets/js/database.js',
			array( 'nutrigl-tools-data' ),
			NUTRIGL_TOOLS_VERSION,
			array( 'strategy' => 'defer', 'in_footer' => true )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nutrigl_tools_enqueue_assets' );

/**
 * Output the shared auth modal + account container once at the end of
 * every frontend page.
 */
function nutrigl_tools_render_modal() {
	if ( is_admin() ) {
		return;
	}
	?>
	<div class="nutrigl-modal" id="nutrigl-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="nutrigl-modal-title">
		<div class="nutrigl-modal__backdrop" data-close></div>
		<div class="nutrigl-modal__panel">
			<button type="button" class="nutrigl-modal__close" data-close aria-label="Close">&times;</button>
			<div class="nutrigl-tabs" role="tablist">
				<button type="button" class="nutrigl-tab is-active" data-tab="signup" role="tab">Sign up</button>
				<button type="button" class="nutrigl-tab" data-tab="login" role="tab">Log in</button>
			</div>
			<h3 id="nutrigl-modal-title" class="nutrigl-modal__title">Get 2 more free checks</h3>
			<p class="nutrigl-modal__sub">1 free daily calculation &middot; sign up and get 3. No spam, ever.</p>

			<form class="nutrigl-form" data-form="signup" novalidate>
				<label>Email
					<input type="email" name="email" required autocomplete="email" placeholder="you@example.com" />
				</label>
				<label>Password
					<input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="at least 8 characters" />
				</label>
				<p class="nutrigl-form__msg" role="alert"></p>
				<button type="submit" class="btn btn--primary btn--block">Create free account</button>
			</form>

			<form class="nutrigl-form" data-form="login" style="display:none;" novalidate>
				<label>Email
					<input type="email" name="email" required autocomplete="email" />
				</label>
				<label>Password
					<input type="password" name="password" required autocomplete="current-password" />
				</label>
				<p class="nutrigl-form__msg" role="alert"></p>
				<button type="submit" class="btn btn--primary btn--block">Log in</button>
			</form>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'nutrigl_tools_render_modal', 20 );

/**
 * Shortcode: [nutrigl_account] — renders login/signup badge.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function nutrigl_tools_account_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'theme' => 'dark' ), $atts, 'nutrigl_account' );
	$class = 'nutrigl-account' . ( 'light' === $atts['theme'] ? ' nutrigl-account--light' : '' );
	return '<span id="nutrigl-account" class="' . esc_attr( $class ) . '"></span>';
}
add_shortcode( 'nutrigl_account', 'nutrigl_tools_account_shortcode' );

/**
 * GL classification helpers.
 */
function nutrigl_tools_gi_tier( $gi ) {
	if ( $gi < 55 ) return 'low';
	if ( $gi < 70 ) return 'med';
	return 'high';
}
function nutrigl_tools_gl_tier( $gl ) {
	if ( $gl < 10 ) return 'low';
	if ( $gl < 20 ) return 'med';
	return 'high';
}

/**
 * Shortcode: [gl_calculator]
 *
 * Now calls our WP proxy (which forwards to the Heroku API with our web key)
 * and enforces a strict 1-free / 3-with-signup daily quota per user + per
 * (IP + browser fingerprint) pool.
 */
function nutrigl_tools_calculator_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'    => 'Glycemic Load Calculator',
			'subtitle' => 'Type any food, set the portion, and see its blood-sugar impact instantly.',
		),
		$atts,
		'gl_calculator'
	);

	$foods = nutrigl_tools_get_foods();

	// Build a de-duplicated list of food names for the datalist autocomplete.
	$names = array();
	foreach ( $foods as $f ) {
		if ( ! empty( $f['name'] ) ) {
			$names[ strtolower( $f['name'] ) ] = $f['name'];
		}
	}
	ksort( $names );

	ob_start();
	?>
	<section class="gl-calc" id="gl-calculator">
		<h2 class="gl-calc__title"><?php echo esc_html( $atts['title'] ); ?></h2>
		<p class="gl-calc__sub"><?php echo esc_html( $atts['subtitle'] ); ?></p>

		<div class="gl-calc__grid">
			<div class="gl-field">
				<label for="glc-food">Food</label>
				<input id="glc-food" class="gl-input" type="text" list="glc-food-list" placeholder="e.g. banana, brown rice, watermelon" maxlength="60" autocomplete="off" spellcheck="false">
				<datalist id="glc-food-list">
					<?php foreach ( $names as $name ) : ?>
						<option value="<?php echo esc_attr( $name ); ?>"></option>
					<?php endforeach; ?>
				</datalist>
			</div>

			<div class="gl-field">
				<label for="glc-grams">Serving (grams)</label>
				<input id="glc-grams" class="gl-input" type="number" min="1" max="2000" step="1" value="100" inputmode="numeric">
			</div>

			<div class="gl-field gl-field--btn">
				<label for="glc-run">&nbsp;</label>
				<button type="button" id="glc-run" class="btn btn--primary btn--block">Calculate</button>
			</div>
		</div>

		<div class="gl-result" role="status" aria-live="polite">
			<div class="gl-result__cell">
				<span class="gl-result__label">Glycemic Index</span>
				<span id="glc-out-gi" class="gl-result__value">&mdash;</span>
			</div>
			<div class="gl-result__cell">
				<span class="gl-result__label">Carbs (serving)</span>
				<span id="glc-out-carbs" class="gl-result__value">&mdash;</span>
			</div>
			<div class="gl-result__cell">
				<span class="gl-result__label">Glycemic Load</span>
				<span id="glc-out-gl" class="gl-result__value">&mdash;</span>
			</div>
			<p id="glc-hint" class="gl-result__hint">Type a food and grams, then hit Calculate.</p>
		</div>

		<div class="gl-quota" id="glc-quota">
			<div class="gl-quota__bar"><span id="glc-quota-fill" class="gl-quota__fill"></span></div>
			<div id="glc-quota-text" class="gl-quota__text">Checking your daily allowance&hellip;</div>
			<div id="glc-quota-cta" class="gl-quota__cta"></div>
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
			'title'    => '',
			'subtitle' => '',
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
	<section class="gl-db" id="gl-database">
		<?php if ( $atts['title'] ) : ?><h2 class="gl-calc__title" style="text-align:center;margin-bottom:8px;"><?php echo wp_kses_post( $atts['title'] ); ?></h2><?php endif; ?>
		<?php if ( $atts['subtitle'] ) : ?><p class="gl-calc__sub" style="text-align:center;margin-bottom:32px;"><?php echo esc_html( $atts['subtitle'] ); ?></p><?php endif; ?>

		<div class="with-sidebar">
			<aside class="sidebar" aria-label="Filter foods">
				<div class="sidebar__group">
					<p class="sidebar__title">Glycemic Index</p>
					<ul class="sidebar__list">
						<li><label><input type="checkbox" data-filter="gi" value="low"><span class="dot dot--low"></span>Low (&lt; 55)</label></li>
						<li><label><input type="checkbox" data-filter="gi" value="med"><span class="dot dot--med"></span>Medium (55–69)</label></li>
						<li><label><input type="checkbox" data-filter="gi" value="high"><span class="dot dot--high"></span>High (70+)</label></li>
					</ul>
				</div>
				<div class="sidebar__group">
					<p class="sidebar__title">Glycemic Load</p>
					<ul class="sidebar__list">
						<li><label><input type="checkbox" data-filter="gl" value="low"><span class="dot dot--low"></span>Low (&lt; 10)</label></li>
						<li><label><input type="checkbox" data-filter="gl" value="med"><span class="dot dot--med"></span>Medium (10–19)</label></li>
						<li><label><input type="checkbox" data-filter="gl" value="high"><span class="dot dot--high"></span>High (20+)</label></li>
					</ul>
				</div>
				<div class="sidebar__group">
					<p class="sidebar__title">Category</p>
					<ul class="sidebar__list">
						<?php foreach ( array_keys( $categories ) as $cat ) : ?>
							<li><label><input type="checkbox" data-filter="cat" value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></label></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<button class="sidebar__clear" type="button" id="gldb-clear">Clear all filters</button>
			</aside>

			<div>
				<div class="gl-db__topbar">
					<div class="gl-db__search">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path>
						</svg>
						<input id="gldb-search" type="search" placeholder="Search foods (banana, rice, oats…)" aria-label="Search foods">
					</div>
					<select id="gldb-sort" class="gl-db__sort" aria-label="Sort by">
						<option value="name-asc">Sort: Name (A–Z)</option>
						<option value="gl-asc">Sort: GL (low → high)</option>
						<option value="gl-desc">Sort: GL (high → low)</option>
						<option value="gi-asc">Sort: GI (low → high)</option>
						<option value="gi-desc">Sort: GI (high → low)</option>
					</select>
				</div>

				<p id="gldb-stats" class="gl-db__stats" aria-live="polite"><?php echo count( $foods ); ?> foods.</p>

				<div class="food-grid" id="gldb-grid">
					<?php foreach ( $foods as $f ) :
						$gl       = round( ( (float) $f['gi'] * (float) $f['serving_carbs'] ) / 100, 1 );
						$gi_tier  = nutrigl_tools_gi_tier( (int) $f['gi'] );
						$gl_tier  = nutrigl_tools_gl_tier( $gl );
						$emoji    = nutrigl_tools_food_emoji( $f['name'], $f['category'] );
						?>
						<article class="food-card"
							data-name="<?php echo esc_attr( strtolower( $f['name'] ) ); ?>"
							data-cat="<?php echo esc_attr( $f['category'] ); ?>"
							data-gi="<?php echo esc_attr( $f['gi'] ); ?>"
							data-gl="<?php echo esc_attr( $gl ); ?>"
							data-gi-tier="<?php echo esc_attr( $gi_tier ); ?>"
							data-gl-tier="<?php echo esc_attr( $gl_tier ); ?>">
							<span class="food-card__pill food-card__pill--<?php echo esc_attr( $gl_tier ); ?>">
								<?php echo $gl_tier === 'low' ? 'Low GL' : ( $gl_tier === 'med' ? 'Medium GL' : 'High GL' ); ?>
							</span>
							<div class="food-card__head">
								<div class="food-card__emoji" aria-hidden="true"><?php echo esc_html( $emoji ); ?></div>
								<div>
									<h3 class="food-card__name"><?php echo esc_html( $f['name'] ); ?></h3>
									<p class="food-card__cat"><?php echo esc_html( $f['category'] ); ?> · <?php echo (int) $f['serving_g']; ?> g</p>
								</div>
							</div>
							<div class="food-card__stats">
								<div class="food-card__stat food-card__stat--gi">
									<span>GI</span>
									<strong class="<?php echo esc_attr( $gi_tier ); ?>"><?php echo (int) $f['gi']; ?></strong>
								</div>
								<div class="food-card__stat">
									<span>Carbs</span>
									<strong><?php echo (int) $f['serving_carbs']; ?>g</strong>
								</div>
								<div class="food-card__stat food-card__stat--gl">
									<span>GL</span>
									<strong class="<?php echo esc_attr( $gl_tier ); ?>"><?php echo esc_html( $gl ); ?></strong>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<div class="food-empty" id="gldb-empty" style="display:none;">
					<p>No foods match those filters.</p>
					<p><button class="btn btn--ghost" type="button" onclick="document.getElementById('gldb-clear').click();">Clear filters</button></p>
				</div>
			</div>
		</div>
	</section>
	<?php
	return trim( ob_get_clean() );
}
add_shortcode( 'gl_database', 'nutrigl_tools_database_shortcode' );
