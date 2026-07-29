<?php
/**
 * Admin settings page for the NutriGL API key.
 *
 * Location: Settings → NutriGL.
 * Only runs in wp-admin.
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the option + settings section/field.
 */
function nutrigl_tools_admin_register() {
	register_setting(
		'nutrigl_tools',
		'nutrigl_tools_api_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'nutrigl_tools_sanitize_key',
			'show_in_rest'      => false,
			'default'           => '',
		)
	);

	add_settings_section(
		'nutrigl_tools_main',
		'API credentials',
		'nutrigl_tools_section_intro',
		'nutrigl_tools'
	);

	add_settings_field(
		'nutrigl_tools_api_key_field',
		'NutriGL Insight API key',
		'nutrigl_tools_key_field',
		'nutrigl_tools',
		'nutrigl_tools_main'
	);
}
add_action( 'admin_init', 'nutrigl_tools_admin_register' );

/**
 * Add the menu item under Settings.
 */
function nutrigl_tools_admin_menu() {
	add_options_page(
		'NutriGL Tools',
		'NutriGL',
		'manage_options',
		'nutrigl-tools',
		'nutrigl_tools_admin_page'
	);
}
add_action( 'admin_menu', 'nutrigl_tools_admin_menu' );

/**
 * Sanitize the API key.
 *
 * @param string $val Raw value.
 * @return string
 */
function nutrigl_tools_sanitize_key( $val ) {
	$val = is_string( $val ) ? trim( $val ) : '';
	// Only allow the character set the API uses (alphanumeric + a few safe chars).
	return preg_replace( '/[^A-Za-z0-9_\-]/', '', $val );
}

/**
 * Section intro.
 */
function nutrigl_tools_section_intro() {
	echo '<p>Credentials for the upstream NutriGL Insight API. The key is used only server-side and never sent to browsers.</p>';
}

/**
 * Render the API key input.
 */
function nutrigl_tools_key_field() {
	$env_set   = (bool) getenv( 'NUTRIGL_API_KEY' );
	$const_set = defined( 'NUTRIGL_API_KEY' ) && NUTRIGL_API_KEY;
	$db_value  = (string) get_option( 'nutrigl_tools_api_key', '' );

	if ( $env_set ) {
		echo '<p><strong>Loaded from environment variable</strong> <code>NUTRIGL_API_KEY</code>. Overrides any value below.</p>';
	} elseif ( $const_set ) {
		echo '<p><strong>Loaded from wp-config.php</strong> constant <code>NUTRIGL_API_KEY</code>. Overrides any value below.</p>';
	}

	$masked = '' !== $db_value ? str_repeat( '*', max( 0, strlen( $db_value ) - 4 ) ) . substr( $db_value, -4 ) : '';
	printf(
		'<input type="password" id="nutrigl_tools_api_key" name="nutrigl_tools_api_key" value="%s" class="regular-text" autocomplete="off" placeholder="%s" />',
		esc_attr( $db_value ),
		esc_attr( '' !== $masked ? 'Current: ' . $masked : 'Paste your API key' )
	);
	echo '<p class="description">Get this from the NutriGL Insight backend. Stored in the WordPress options table (never in git).</p>';
}

/**
 * Render the settings page.
 */
function nutrigl_tools_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$resolved = nutrigl_tools_api_key();
	$status   = '' !== $resolved
		? '<span style="color:#22c55e;font-weight:600;">&#9679; Configured</span>'
		: '<span style="color:#ef4444;font-weight:600;">&#9679; Missing &mdash; calculator will return an error to visitors</span>';
	?>
	<div class="wrap">
		<h1>NutriGL Tools</h1>
		<p style="font-size:14px;margin:8px 0 20px;">API status: <?php echo $status; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'nutrigl_tools' );
			do_settings_sections( 'nutrigl_tools' );
			submit_button( 'Save API key' );
			?>
		</form>

		<hr />
		<h2>Alternative: set via <code>wp-config.php</code></h2>
		<p>For extra safety, define the key as a PHP constant instead of storing it in the database:</p>
		<pre style="background:#0b1e3a;color:#e6edf7;padding:14px 16px;border-radius:8px;overflow:auto;"><code>define( 'NUTRIGL_API_KEY', 'your-key-here' );</code></pre>
		<p>The constant overrides the option above. An environment variable named <code>NUTRIGL_API_KEY</code> (e.g. set in cPanel &rarr; Setup Node.js/PHP App or via <code>.htaccess</code>) overrides both.</p>

		<?php if ( function_exists( 'nutrigl_installer_render' ) ) { nutrigl_installer_render(); } ?>
	</div>
	<?php
}
