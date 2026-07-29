<?php
/**
 * Meal Builder REST endpoints under /wp-json/nutrigl/v1/meals
 *
 * Lets logged-in NutriGL users combine foods from the local GI/GL dataset
 * into a saved "meal" and see the combined Glycemic Load. Values are always
 * computed server-side from the trusted local dataset (never trusts
 * client-supplied GI/carb numbers), so this feature does not touch the
 * upstream API or the daily quota at all.
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NUTRIGL_MEAL_MAX_ITEMS', 20 );
define( 'NUTRIGL_MEAL_MAX_MEALS', 40 );

/**
 * Register meal routes.
 */
function nutrigl_tools_meal_routes() {
	$common = array( 'permission_callback' => '__return_true' );

	register_rest_route( 'nutrigl/v1', '/meals', array_merge( $common, array(
		'methods'  => 'GET',
		'callback' => 'nutrigl_tools_rest_meals_list',
	) ) );
	register_rest_route( 'nutrigl/v1', '/meals', array_merge( $common, array(
		'methods'  => 'POST',
		'callback' => 'nutrigl_tools_rest_meals_create',
	) ) );
	register_rest_route( 'nutrigl/v1', '/meals/(?P<id>\d+)', array_merge( $common, array(
		'methods'  => 'GET',
		'callback' => 'nutrigl_tools_rest_meals_get',
	) ) );
	register_rest_route( 'nutrigl/v1', '/meals/(?P<id>\d+)', array_merge( $common, array(
		'methods'  => 'DELETE',
		'callback' => 'nutrigl_tools_rest_meals_delete',
	) ) );
}
add_action( 'rest_api_init', 'nutrigl_tools_meal_routes' );

/**
 * Require a logged-in NutriGL user or return an error response.
 *
 * @return object|WP_REST_Response User row, or an error response to return directly.
 */
function nutrigl_tools_require_user() {
	$user = nutrigl_tools_current_user();
	if ( ! $user ) {
		return nutrigl_tools_json_err( 'Log in or sign up free to build and save meals.', 'auth_required', 401 );
	}
	return $user;
}

/**
 * Look up a food in the local dataset by exact (case-insensitive) name match.
 *
 * @param string $name Food name.
 * @return array|null
 */
function nutrigl_tools_find_local_food( $name ) {
	$needle = strtolower( trim( $name ) );
	if ( '' === $needle ) {
		return null;
	}
	foreach ( nutrigl_tools_get_foods() as $f ) {
		if ( ! empty( $f['name'] ) && strtolower( $f['name'] ) === $needle ) {
			return $f;
		}
	}
	return null;
}

/**
 * Classify a meal-level GL total. Meal thresholds are looser than a single
 * food serving since meals combine multiple items.
 *
 * @param float $gl Total GL.
 * @return string low|med|high
 */
function nutrigl_tools_meal_tier( $gl ) {
	if ( $gl < 20 ) {
		return 'low';
	}
	if ( $gl < 40 ) {
		return 'med';
	}
	return 'high';
}

/**
 * Fetch a meal + its items, scoped to a user. Returns null if not found/owned.
 *
 * @param int $meal_id Meal ID.
 * @param int $user_id Owner user ID.
 * @return array|null
 */
function nutrigl_tools_get_meal_for_user( $meal_id, $user_id ) {
	global $wpdb;
	$meal = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
		"SELECT * FROM {$wpdb->prefix}nutrigl_meals WHERE id=%d AND user_id=%d LIMIT 1",
		$meal_id,
		$user_id
	) );
	if ( ! $meal ) {
		return null;
	}
	$items = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
		"SELECT food_name, grams, gi, carbs, gl FROM {$wpdb->prefix}nutrigl_meal_items WHERE meal_id=%d ORDER BY sort_order ASC, id ASC",
		$meal_id
	) );
	return nutrigl_tools_format_meal( $meal, $items );
}

/**
 * Shape a meal row + items into the REST response format.
 *
 * @param object $meal  Meal row.
 * @param array  $items Item rows.
 * @return array
 */
function nutrigl_tools_format_meal( $meal, $items ) {
	$total_gl    = 0.0;
	$total_carbs = 0.0;
	$out_items   = array();
	foreach ( $items as $it ) {
		$total_gl    += (float) $it->gl;
		$total_carbs += (float) $it->carbs;
		$out_items[]  = array(
			'food'  => $it->food_name,
			'grams' => (float) $it->grams,
			'gi'    => (float) $it->gi,
			'carbs' => round( (float) $it->carbs, 1 ),
			'gl'    => round( (float) $it->gl, 1 ),
		);
	}
	$total_gl = round( $total_gl, 1 );
	return array(
		'id'          => (int) $meal->id,
		'name'        => $meal->name,
		'created_at'  => $meal->created_at,
		'item_count'  => count( $out_items ),
		'total_carbs' => round( $total_carbs, 1 ),
		'total_gl'    => $total_gl,
		'tier'        => nutrigl_tools_meal_tier( $total_gl ),
		'items'       => $out_items,
	);
}

/**
 * GET /meals — list the current user's saved meals (summaries only).
 */
function nutrigl_tools_rest_meals_list() {
	$user = nutrigl_tools_require_user();
	if ( $user instanceof WP_REST_Response ) {
		return $user;
	}

	global $wpdb;
	$meals = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
		"SELECT * FROM {$wpdb->prefix}nutrigl_meals WHERE user_id=%d ORDER BY created_at DESC LIMIT %d",
		$user->user_id,
		NUTRIGL_MEAL_MAX_MEALS
	) );

	$out = array();
	foreach ( $meals as $m ) {
		$items = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT food_name, grams, gi, carbs, gl FROM {$wpdb->prefix}nutrigl_meal_items WHERE meal_id=%d ORDER BY sort_order ASC, id ASC",
			$m->id
		) );
		$out[] = nutrigl_tools_format_meal( $m, $items );
	}

	return array( 'meals' => $out );
}

/**
 * POST /meals — { name, items:[{food, grams}, ...] }
 * Computes GI/carbs/GL server-side from the trusted local dataset.
 */
function nutrigl_tools_rest_meals_create( WP_REST_Request $req ) {
	$user = nutrigl_tools_require_user();
	if ( $user instanceof WP_REST_Response ) {
		return $user;
	}

	$name  = sanitize_text_field( (string) $req->get_param( 'name' ) );
	$items = $req->get_param( 'items' );

	if ( '' === $name ) {
		$name = 'My meal';
	}
	$name = mb_substr( $name, 0, 120 );

	if ( ! is_array( $items ) || empty( $items ) ) {
		return nutrigl_tools_json_err( 'Add at least one food to the meal.', 'empty_meal' );
	}
	if ( count( $items ) > NUTRIGL_MEAL_MAX_ITEMS ) {
		return nutrigl_tools_json_err( 'A meal can have at most ' . NUTRIGL_MEAL_MAX_ITEMS . ' items.', 'too_many_items' );
	}

	global $wpdb;

	// Existing-meal cap.
	$existing_count = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
		"SELECT COUNT(*) FROM {$wpdb->prefix}nutrigl_meals WHERE user_id=%d",
		$user->user_id
	) );
	if ( $existing_count >= NUTRIGL_MEAL_MAX_MEALS ) {
		return nutrigl_tools_json_err( 'You have reached the maximum of ' . NUTRIGL_MEAL_MAX_MEALS . ' saved meals. Delete one first.', 'meal_cap' );
	}

	$rows = array();
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) || empty( $item['food'] ) ) {
			continue;
		}
		$food = nutrigl_tools_find_local_food( (string) $item['food'] );
		if ( ! $food ) {
			return nutrigl_tools_json_err( 'Unknown food: "' . esc_html( (string) $item['food'] ) . '". Pick one from the list.', 'unknown_food' );
		}
		$grams = isset( $item['grams'] ) ? (float) $item['grams'] : (float) $food['serving_g'];
		if ( $grams < 1 || $grams > 2000 ) {
			return nutrigl_tools_json_err( 'Grams must be between 1 and 2000 for "' . esc_html( $food['name'] ) . '".', 'invalid_grams' );
		}
		$gi    = (float) $food['gi'];
		$carbs = ( (float) $food['carbs_per_100g'] * $grams ) / 100;
		$gl    = ( $gi * $carbs ) / 100;
		$rows[] = array(
			'food_name' => $food['name'],
			'grams'     => $grams,
			'gi'        => $gi,
			'carbs'     => $carbs,
			'gl'        => $gl,
		);
	}

	if ( empty( $rows ) ) {
		return nutrigl_tools_json_err( 'Add at least one valid food to the meal.', 'empty_meal' );
	}

	$now = gmdate( 'Y-m-d H:i:s' );
	$wpdb->insert(
		$wpdb->prefix . 'nutrigl_meals',
		array(
			'user_id'    => $user->user_id,
			'name'       => $name,
			'created_at' => $now,
			'updated_at' => $now,
		),
		array( '%d', '%s', '%s', '%s' )
	);
	$meal_id = (int) $wpdb->insert_id;
	if ( ! $meal_id ) {
		return nutrigl_tools_json_err( 'Could not save the meal. Try again.', 'db_error', 500 );
	}

	$order = 0;
	foreach ( $rows as $r ) {
		$wpdb->insert(
			$wpdb->prefix . 'nutrigl_meal_items',
			array(
				'meal_id'    => $meal_id,
				'food_name'  => $r['food_name'],
				'grams'      => $r['grams'],
				'gi'         => $r['gi'],
				'carbs'      => $r['carbs'],
				'gl'         => $r['gl'],
				'sort_order' => $order++,
			),
			array( '%d', '%s', '%f', '%f', '%f', '%f', '%d' )
		);
	}

	$meal = nutrigl_tools_get_meal_for_user( $meal_id, $user->user_id );
	return array( 'ok' => true, 'meal' => $meal );
}

/**
 * GET /meals/{id}
 */
function nutrigl_tools_rest_meals_get( WP_REST_Request $req ) {
	$user = nutrigl_tools_require_user();
	if ( $user instanceof WP_REST_Response ) {
		return $user;
	}
	$meal = nutrigl_tools_get_meal_for_user( (int) $req->get_param( 'id' ), $user->user_id );
	if ( ! $meal ) {
		return nutrigl_tools_json_err( 'Meal not found.', 'not_found', 404 );
	}
	return array( 'meal' => $meal );
}

/**
 * DELETE /meals/{id}
 */
function nutrigl_tools_rest_meals_delete( WP_REST_Request $req ) {
	$user = nutrigl_tools_require_user();
	if ( $user instanceof WP_REST_Response ) {
		return $user;
	}
	global $wpdb;
	$meal_id = (int) $req->get_param( 'id' );

	$owned = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
		"SELECT id FROM {$wpdb->prefix}nutrigl_meals WHERE id=%d AND user_id=%d LIMIT 1",
		$meal_id,
		$user->user_id
	) );
	if ( ! $owned ) {
		return nutrigl_tools_json_err( 'Meal not found.', 'not_found', 404 );
	}

	$wpdb->delete( $wpdb->prefix . 'nutrigl_meal_items', array( 'meal_id' => $meal_id ), array( '%d' ) );
	$wpdb->delete( $wpdb->prefix . 'nutrigl_meals', array( 'id' => $meal_id ), array( '%d' ) );

	return array( 'ok' => true );
}
