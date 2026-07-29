<?php
/**
 * REST endpoints under /wp-json/nutrigl/v1/*
 *
 * Public endpoints (no WP nonce). Auth state is read from the custom cookie.
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NUTRIGL_API_URL', 'https://nutriglinsight-api-24d456c86e48.herokuapp.com/api/glycemic-check' );

/**
 * Resolve the upstream API key from (in order):
 *   1. Environment variable NUTRIGL_API_KEY (e.g. cPanel Env vars)
 *   2. Constant NUTRIGL_API_KEY defined in wp-config.php
 *   3. WP option 'nutrigl_tools_api_key' (settable via Settings → NutriGL)
 *
 * Returns empty string if none is configured.
 *
 * @return string
 */
function nutrigl_tools_api_key() {
	$env = getenv( 'NUTRIGL_API_KEY' );
	if ( is_string( $env ) && '' !== $env ) {
		return trim( $env );
	}
	if ( defined( 'NUTRIGL_API_KEY' ) && NUTRIGL_API_KEY ) {
		return (string) NUTRIGL_API_KEY;
	}
	$opt = get_option( 'nutrigl_tools_api_key', '' );
	return is_string( $opt ) ? trim( $opt ) : '';
}

/**
 * Register routes.
 */
function nutrigl_tools_rest_routes() {
	$common = array( 'permission_callback' => '__return_true' );

	register_rest_route( 'nutrigl/v1', '/signup', array_merge( $common, array(
		'methods'  => 'POST',
		'callback' => 'nutrigl_tools_rest_signup',
	) ) );
	register_rest_route( 'nutrigl/v1', '/login', array_merge( $common, array(
		'methods'  => 'POST',
		'callback' => 'nutrigl_tools_rest_login',
	) ) );
	register_rest_route( 'nutrigl/v1', '/logout', array_merge( $common, array(
		'methods'  => 'POST',
		'callback' => 'nutrigl_tools_rest_logout',
	) ) );
	register_rest_route( 'nutrigl/v1', '/me', array_merge( $common, array(
		'methods'  => 'GET',
		'callback' => 'nutrigl_tools_rest_me',
	) ) );
	register_rest_route( 'nutrigl/v1', '/gl-check', array_merge( $common, array(
		'methods'  => 'POST',
		'callback' => 'nutrigl_tools_rest_gl_check',
	) ) );
}
add_action( 'rest_api_init', 'nutrigl_tools_rest_routes' );

/**
 * Standard JSON error response.
 *
 * @param string $msg    Human message.
 * @param string $code   Machine code.
 * @param int    $status HTTP status.
 * @param array  $extra  Extra fields.
 * @return WP_REST_Response
 */
function nutrigl_tools_json_err( $msg, $code, $status = 400, $extra = array() ) {
	return new WP_REST_Response( array_merge( array( 'error' => $msg, 'code' => $code ), $extra ), $status );
}

/**
 * Simple IP-based rate limiter for auth endpoints (transient window).
 *
 * @param string $bucket Bucket key.
 * @param int    $max    Max attempts.
 * @param int    $window Seconds.
 * @return bool True if allowed.
 */
function nutrigl_tools_rate_ok( $bucket, $max, $window ) {
	$key = 'nutrigl_rl_' . $bucket . '_' . md5( nutrigl_tools_client_ip() );
	$n   = (int) get_transient( $key );
	if ( $n >= $max ) {
		return false;
	}
	set_transient( $key, $n + 1, $window );
	return true;
}

/**
 * POST /signup — { email, password }
 */
function nutrigl_tools_rest_signup( WP_REST_Request $req ) {
	if ( ! nutrigl_tools_rate_ok( 'signup', 5, 10 * MINUTE_IN_SECONDS ) ) {
		return nutrigl_tools_json_err( 'Too many signup attempts. Try again later.', 'rate_limit', 429 );
	}
	$email    = sanitize_email( (string) $req->get_param( 'email' ) );
	$password = (string) $req->get_param( 'password' );

	if ( ! is_email( $email ) ) {
		return nutrigl_tools_json_err( 'Enter a valid email address.', 'invalid_email' );
	}
	if ( strlen( $password ) < 8 ) {
		return nutrigl_tools_json_err( 'Password must be at least 8 characters.', 'weak_password' );
	}
	if ( strlen( $password ) > 200 ) {
		return nutrigl_tools_json_err( 'Password is too long.', 'weak_password' );
	}
	if ( nutrigl_tools_get_user_by_email( $email ) ) {
		return nutrigl_tools_json_err( 'An account already exists for this email. Log in instead.', 'email_taken' );
	}

	$id = nutrigl_tools_create_user( $email, $password );
	if ( ! $id ) {
		return nutrigl_tools_json_err( 'Could not create your account.', 'db_error', 500 );
	}

	nutrigl_tools_create_session( $id );

	$fingerprint = (string) $req->get_param( 'fingerprint' );
	$user        = (object) array( 'user_id' => $id, 'email' => $email );

	return array(
		'ok'    => true,
		'user'  => array( 'email' => $email ),
		'quota' => nutrigl_tools_check_quota( $user, $fingerprint ),
	);
}

/**
 * POST /login — { email, password }
 */
function nutrigl_tools_rest_login( WP_REST_Request $req ) {
	$email    = sanitize_email( (string) $req->get_param( 'email' ) );
	$password = (string) $req->get_param( 'password' );

	if ( ! is_email( $email ) || '' === $password ) {
		return nutrigl_tools_json_err( 'Enter your email and password.', 'invalid_input' );
	}
	if ( ! nutrigl_tools_rate_ok( 'login', 8, 15 * MINUTE_IN_SECONDS ) ) {
		return nutrigl_tools_json_err( 'Too many login attempts. Try again in a bit.', 'rate_limit', 429 );
	}

	$user = nutrigl_tools_get_user_by_email( $email );
	if ( ! $user || ! nutrigl_tools_verify_password( $user, $password ) ) {
		return nutrigl_tools_json_err( 'Wrong email or password.', 'bad_credentials', 401 );
	}

	nutrigl_tools_create_session( (int) $user->id );

	$fingerprint = (string) $req->get_param( 'fingerprint' );
	$as_current  = (object) array( 'user_id' => (int) $user->id, 'email' => $user->email );

	return array(
		'ok'    => true,
		'user'  => array( 'email' => $user->email ),
		'quota' => nutrigl_tools_check_quota( $as_current, $fingerprint ),
	);
}

/**
 * POST /logout
 */
function nutrigl_tools_rest_logout( WP_REST_Request $req ) {
	nutrigl_tools_destroy_session();
	$fingerprint = (string) $req->get_param( 'fingerprint' );
	return array(
		'ok'    => true,
		'quota' => nutrigl_tools_check_quota( null, $fingerprint ),
	);
}

/**
 * GET /me?fingerprint=xxx
 */
function nutrigl_tools_rest_me( WP_REST_Request $req ) {
	$user        = nutrigl_tools_current_user();
	$fingerprint = (string) $req->get_param( 'fingerprint' );
	return array(
		'user'  => $user ? array( 'email' => $user->email ) : null,
		'quota' => nutrigl_tools_check_quota( $user, $fingerprint ),
	);
}

/**
 * POST /gl-check — proxy to Heroku API with quota enforcement.
 */
function nutrigl_tools_rest_gl_check( WP_REST_Request $req ) {
	$food        = trim( (string) $req->get_param( 'food' ) );
	$grams_raw   = $req->get_param( 'grams' );
	$fingerprint = (string) $req->get_param( 'fingerprint' );

	if ( '' === $food || mb_strlen( $food ) > 60 ) {
		return nutrigl_tools_json_err( 'Enter a food name (max 60 characters).', 'invalid_food' );
	}
	if ( null === $grams_raw || '' === $grams_raw ) {
		$grams = 100.0;
	} else {
		$grams = (float) $grams_raw;
	}
	if ( $grams < 1 || $grams > 2000 ) {
		return nutrigl_tools_json_err( 'Serving must be between 1 and 2000 grams.', 'invalid_grams' );
	}

	$user  = nutrigl_tools_current_user();
	$quota = nutrigl_tools_check_quota( $user, $fingerprint );

	if ( $quota['remaining'] <= 0 ) {
		if ( $user ) {
			return nutrigl_tools_json_err(
				"You've used all {$quota['limit']} of today's checks. Come back tomorrow.",
				'quota_daily_user',
				429,
				array( 'quota' => $quota )
			);
		}
		return nutrigl_tools_json_err(
			"You've used today's free check. Sign up free for 2 more.",
			'quota_daily_anon',
			429,
			array( 'quota' => $quota )
		);
	}

	// Coarse burst limiter to prevent hammering our proxy.
	if ( ! nutrigl_tools_rate_ok( 'gl-check', 20, MINUTE_IN_SECONDS ) ) {
		return nutrigl_tools_json_err( 'Slow down a moment and try again.', 'rate_burst', 429, array( 'quota' => $quota ) );
	}

	$api_key = nutrigl_tools_api_key();
	if ( '' === $api_key ) {
		return nutrigl_tools_json_err(
			'Calculator is not configured yet. Please try again later.',
			'server_misconfigured',
			503,
			array( 'quota' => $quota )
		);
	}

	$resp = wp_remote_post(
		NUTRIGL_API_URL,
		array(
			'timeout' => 12,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Web-Key'    => $api_key,
			),
			'body'    => wp_json_encode(
				array(
					'food'  => $food,
					'grams' => $grams,
				)
			),
		)
	);

	if ( is_wp_error( $resp ) ) {
		return nutrigl_tools_json_err( 'Could not reach the calculator service. Try again.', 'upstream_error', 502, array( 'quota' => $quota ) );
	}

	$status = (int) wp_remote_retrieve_response_code( $resp );
	$body   = wp_remote_retrieve_body( $resp );
	$data   = json_decode( $body, true );

	if ( 429 === $status ) {
		return nutrigl_tools_json_err( 'The calculator service is busy. Try again in a minute.', 'upstream_rate', 429, array( 'quota' => $quota ) );
	}
	if ( 200 !== $status || ! is_array( $data ) ) {
		$msg = is_array( $data ) && ! empty( $data['error'] ) ? $data['error'] : 'Could not calculate for that food. Try another name.';
		return nutrigl_tools_json_err( $msg, 'upstream_bad', 400, array( 'quota' => $quota ) );
	}

	// Only charge quota on real success.
	nutrigl_tools_consume_quota( $user, $fingerprint );
	$quota = nutrigl_tools_check_quota( $user, $fingerprint );

	return array(
		'result' => array(
			'food'              => isset( $data['food'] ) ? (string) $data['food'] : $food,
			'grams'             => isset( $data['grams'] ) ? (float) $data['grams'] : $grams,
			'gi'                => isset( $data['gi'] ) ? (float) $data['gi'] : null,
			'carbs_for_serving' => isset( $data['carbs_for_serving'] ) ? (float) $data['carbs_for_serving'] : null,
			'glycemic_load'     => isset( $data['glycemic_load'] ) ? (float) $data['glycemic_load'] : null,
			'category'          => isset( $data['category'] ) ? (string) $data['category'] : 'medium',
		),
		'quota'  => $quota,
		'user'   => $user ? array( 'email' => $user->email ) : null,
	);
}
