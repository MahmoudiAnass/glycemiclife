<?php
/**
 * Custom (non-WordPress) auth for calculator visitors.
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NUTRIGL_AUTH_COOKIE', 'nutrigl_auth' );
define( 'NUTRIGL_SESSION_DAYS', 30 );

/**
 * Create / upgrade tables. Safe to call multiple times.
 */
function nutrigl_tools_install_tables() {
	global $wpdb;
	$charset = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$users = "CREATE TABLE {$wpdb->prefix}nutrigl_users (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		email VARCHAR(191) NOT NULL,
		password_hash VARCHAR(255) NOT NULL,
		created_at DATETIME NOT NULL,
		last_login_at DATETIME DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY email (email)
	) $charset;";

	$sessions = "CREATE TABLE {$wpdb->prefix}nutrigl_sessions (
		token CHAR(64) NOT NULL,
		user_id BIGINT UNSIGNED NOT NULL,
		created_at DATETIME NOT NULL,
		expires_at DATETIME NOT NULL,
		PRIMARY KEY  (token),
		KEY user_id (user_id)
	) $charset;";

	$usage = "CREATE TABLE {$wpdb->prefix}nutrigl_usage (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		scope VARCHAR(16) NOT NULL,
		scope_key VARCHAR(191) NOT NULL,
		day_key CHAR(10) NOT NULL,
		count INT NOT NULL DEFAULT 0,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY scope_day (scope, scope_key, day_key)
	) $charset;";

	dbDelta( $users );
	dbDelta( $sessions );
	dbDelta( $usage );
}

/**
 * Ensure schema is current on load (survives fresh git deploys where the
 * activation hook did not fire).
 */
function nutrigl_tools_maybe_install_tables() {
	$installed = get_option( 'nutrigl_tools_db_version' );
	if ( $installed !== NUTRIGL_TOOLS_VERSION ) {
		nutrigl_tools_install_tables();
		update_option( 'nutrigl_tools_db_version', NUTRIGL_TOOLS_VERSION );
	}
}
add_action( 'plugins_loaded', 'nutrigl_tools_maybe_install_tables', 5 );

/**
 * Housekeeping: clear expired sessions occasionally (1-in-50 requests).
 */
function nutrigl_tools_gc_sessions() {
	if ( wp_rand( 0, 49 ) !== 0 ) {
		return;
	}
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->prefix}nutrigl_sessions WHERE expires_at < UTC_TIMESTAMP()" ); // phpcs:ignore
}
add_action( 'init', 'nutrigl_tools_gc_sessions' );

/**
 * User lookups.
 *
 * @param string $email Email.
 * @return object|null
 */
function nutrigl_tools_get_user_by_email( $email ) {
	global $wpdb;
	$t = $wpdb->prefix . 'nutrigl_users';
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE email=%s LIMIT 1", $email ) ); // phpcs:ignore
}

/**
 * @param int $id User id.
 * @return object|null
 */
function nutrigl_tools_get_user_by_id( $id ) {
	global $wpdb;
	$t = $wpdb->prefix . 'nutrigl_users';
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id=%d LIMIT 1", $id ) ); // phpcs:ignore
}

/**
 * Create user. Returns new id or 0.
 *
 * @param string $email    Sanitized email.
 * @param string $password Plaintext password (hashed here).
 * @return int
 */
function nutrigl_tools_create_user( $email, $password ) {
	global $wpdb;
	$ok = $wpdb->insert(
		$wpdb->prefix . 'nutrigl_users',
		array(
			'email'         => $email,
			'password_hash' => password_hash( $password, PASSWORD_DEFAULT ),
			'created_at'    => gmdate( 'Y-m-d H:i:s' ),
		),
		array( '%s', '%s', '%s' )
	);
	return $ok ? (int) $wpdb->insert_id : 0;
}

/**
 * @param object $user
 * @param string $password
 * @return bool
 */
function nutrigl_tools_verify_password( $user, $password ) {
	return isset( $user->password_hash ) && password_verify( $password, $user->password_hash );
}

/**
 * Build the auth cookie options in a way that respects HTTPS/HTTP.
 *
 * @param int $expires Expiration timestamp.
 * @return array
 */
function nutrigl_tools_cookie_opts( $expires ) {
	return array(
		'expires'  => $expires,
		'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
		'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	);
}

/**
 * Create a session, set cookie, return token.
 *
 * @param int $user_id User id.
 * @return string
 */
function nutrigl_tools_create_session( $user_id ) {
	global $wpdb;
	$token = bin2hex( random_bytes( 32 ) );
	$now   = time();
	$exp   = $now + ( NUTRIGL_SESSION_DAYS * DAY_IN_SECONDS );

	$wpdb->insert(
		$wpdb->prefix . 'nutrigl_sessions',
		array(
			'token'      => $token,
			'user_id'    => $user_id,
			'created_at' => gmdate( 'Y-m-d H:i:s', $now ),
			'expires_at' => gmdate( 'Y-m-d H:i:s', $exp ),
		),
		array( '%s', '%d', '%s', '%s' )
	);

	$wpdb->update(
		$wpdb->prefix . 'nutrigl_users',
		array( 'last_login_at' => gmdate( 'Y-m-d H:i:s' ) ),
		array( 'id' => $user_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( ! headers_sent() ) {
		setcookie( NUTRIGL_AUTH_COOKIE, $token, nutrigl_tools_cookie_opts( $exp ) );
		$_COOKIE[ NUTRIGL_AUTH_COOKIE ] = $token;
	}

	return $token;
}

/**
 * Destroy current session and clear cookie.
 */
function nutrigl_tools_destroy_session() {
	global $wpdb;
	if ( ! empty( $_COOKIE[ NUTRIGL_AUTH_COOKIE ] ) ) {
		$token = preg_replace( '/[^a-f0-9]/i', '', wp_unslash( $_COOKIE[ NUTRIGL_AUTH_COOKIE ] ) );
		if ( strlen( $token ) === 64 ) {
			$wpdb->delete( $wpdb->prefix . 'nutrigl_sessions', array( 'token' => $token ), array( '%s' ) );
		}
	}
	if ( ! headers_sent() ) {
		setcookie( NUTRIGL_AUTH_COOKIE, '', nutrigl_tools_cookie_opts( time() - 3600 ) );
	}
	unset( $_COOKIE[ NUTRIGL_AUTH_COOKIE ] );
}

/**
 * Read the current auth cookie and return the session row, or null.
 *
 * @return object|null Object with user_id + email, or null.
 */
function nutrigl_tools_current_user() {
	static $cached = false;
	if ( false !== $cached ) {
		return $cached;
	}
	if ( empty( $_COOKIE[ NUTRIGL_AUTH_COOKIE ] ) ) {
		$cached = null;
		return null;
	}
	$token = preg_replace( '/[^a-f0-9]/i', '', wp_unslash( $_COOKIE[ NUTRIGL_AUTH_COOKIE ] ) );
	if ( strlen( $token ) !== 64 ) {
		$cached = null;
		return null;
	}
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
		"SELECT s.user_id, u.email
		 FROM {$wpdb->prefix}nutrigl_sessions s
		 JOIN {$wpdb->prefix}nutrigl_users u ON u.id = s.user_id
		 WHERE s.token = %s AND s.expires_at > UTC_TIMESTAMP()
		 LIMIT 1",
		$token
	) );
	$cached = $row ? $row : null;
	return $cached;
}
