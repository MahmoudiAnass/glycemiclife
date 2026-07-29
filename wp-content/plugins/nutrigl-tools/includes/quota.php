<?php
/**
 * Quota enforcement.
 *
 * Anonymous visitors: 1 check per calendar day (UTC) per (IP + browser fingerprint).
 * Registered users:   3 checks per day. Same IP+fingerprint pool is *also* capped
 *                      at 3, so making 3 accounts on one device does NOT help.
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NUTRIGL_QUOTA_ANON', 1 );
define( 'NUTRIGL_QUOTA_USER', 3 );

/**
 * Best-effort client IP.
 */
function nutrigl_tools_client_ip() {
	$ip = '';
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$ip = wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		$ip    = trim( $parts[0] );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
	}
	return preg_replace( '/[^0-9a-f\.\:]/i', '', $ip );
}

/**
 * Anonymous key: hash of IP + browser fingerprint.
 *
 * @param string $fingerprint Hex fingerprint provided by client.
 * @return string
 */
function nutrigl_tools_anon_key( $fingerprint ) {
	$fp = is_string( $fingerprint ) ? substr( preg_replace( '/[^a-z0-9]/i', '', $fingerprint ), 0, 64 ) : '';
	if ( '' === $fp ) {
		$fp = 'nofp';
	}
	return hash( 'sha256', nutrigl_tools_client_ip() . '|' . $fp );
}

/**
 * Today, UTC.
 *
 * @return string YYYY-MM-DD
 */
function nutrigl_tools_today() {
	return gmdate( 'Y-m-d' );
}

/**
 * Read usage count for today.
 *
 * @param string $scope 'anon'|'user'.
 * @param string $key   Scope key.
 * @return int
 */
function nutrigl_tools_get_usage( $scope, $key ) {
	global $wpdb;
	$t     = $wpdb->prefix . 'nutrigl_usage';
	$today = nutrigl_tools_today();
	$row   = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
		"SELECT count FROM $t WHERE scope=%s AND scope_key=%s AND day_key=%s LIMIT 1",
		$scope,
		$key,
		$today
	) );
	return $row ? (int) $row->count : 0;
}

/**
 * Increment usage row (upsert).
 *
 * @param string $scope Scope.
 * @param string $key   Key.
 */
function nutrigl_tools_increment_usage( $scope, $key ) {
	global $wpdb;
	$t     = $wpdb->prefix . 'nutrigl_usage';
	$today = nutrigl_tools_today();
	$now   = gmdate( 'Y-m-d H:i:s' );
	$wpdb->query( $wpdb->prepare( // phpcs:ignore
		"INSERT INTO $t (scope, scope_key, day_key, count, updated_at)
		 VALUES (%s, %s, %s, 1, %s)
		 ON DUPLICATE KEY UPDATE count = count + 1, updated_at = VALUES(updated_at)",
		$scope,
		$key,
		$today,
		$now
	) );
}

/**
 * Compute current quota. If a user is logged in, they get NUTRIGL_QUOTA_USER
 * checks total for the day — but the IP+fingerprint pool is capped at the same
 * limit, so shared devices can't stack accounts.
 *
 * @param object|null $user        Current user or null.
 * @param string      $fingerprint Client fingerprint.
 * @return array{used:int,limit:int,remaining:int,scope:string}
 */
function nutrigl_tools_check_quota( $user, $fingerprint ) {
	$anon_key  = nutrigl_tools_anon_key( $fingerprint );
	$anon_used = nutrigl_tools_get_usage( 'anon', $anon_key );

	if ( $user ) {
		$user_used = nutrigl_tools_get_usage( 'user', (string) $user->user_id );
		$used      = max( $user_used, $anon_used );
		return array(
			'used'      => $used,
			'limit'     => NUTRIGL_QUOTA_USER,
			'remaining' => max( 0, NUTRIGL_QUOTA_USER - $used ),
			'scope'     => 'user',
		);
	}
	return array(
		'used'      => $anon_used,
		'limit'     => NUTRIGL_QUOTA_ANON,
		'remaining' => max( 0, NUTRIGL_QUOTA_ANON - $anon_used ),
		'scope'     => 'anon',
	);
}

/**
 * Consume quota on a successful check. Increments BOTH anon and user counters
 * so the shared device pool tracks the same total.
 *
 * @param object|null $user        Current user.
 * @param string      $fingerprint Client fingerprint.
 */
function nutrigl_tools_consume_quota( $user, $fingerprint ) {
	$anon_key = nutrigl_tools_anon_key( $fingerprint );
	nutrigl_tools_increment_usage( 'anon', $anon_key );
	if ( $user ) {
		nutrigl_tools_increment_usage( 'user', (string) $user->user_id );
	}
}
