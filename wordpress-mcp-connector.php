<?php
/**
 * Plugin Name: WordPress MCP Connector
 * Plugin URI: https://nextbrainsolutions.com
 * Description: Exposes 142 abilities via Abilities API for MCP integration. Full control over WordPress, WooCommerce, files, database, SEO, security, backups, and product importing from other WooCommerce stores.
 * Version: 3.0.0
 * Author: Amir Ali
 * Author URI: https://nextbrainsolutions.com
 * License: GPL-2.0-or-later
 * Text Domain: wordpress-mcp-connector
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WMC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WMC_VERSION', '3.0.0' );

/**
 * Load the abilities and settings files
 */
require_once WMC_PLUGIN_DIR . 'includes/settings.php';
require_once WMC_PLUGIN_DIR . 'includes/abilities.php';
require_once WMC_PLUGIN_DIR . 'includes/woocommerce-extended.php';
require_once WMC_PLUGIN_DIR . 'includes/advanced-abilities.php';
require_once WMC_PLUGIN_DIR . 'includes/import-abilities.php';
require_once WMC_PLUGIN_DIR . 'includes/oauth.php';

/**
 * Load bundled MCP Adapter (so no separate plugin needed)
 */
if ( ! class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
	require_once WMC_PLUGIN_DIR . 'vendor/autoload.php';

	spl_autoload_register( function( $class ) {
		$prefix = 'WP\\MCP\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}
		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$file = WMC_PLUGIN_DIR . 'mcp-adapter-lib/' . $relative . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	} );
}

add_action( 'plugins_loaded', function() {
	if ( class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
		\WP\MCP\Core\McpAdapter::instance();
	}
} );

/**
 * Register SEO meta fields for REST API access
 * This ensures Rank Math/Yoast meta can be read/written via REST
 */
function wmc_register_seo_meta_fields() {
	// Rank Math meta fields
	$rank_math_fields = array(
		'rank_math_title',
		'rank_math_description',
		'rank_math_robots',
		'rank_math_focus_keyword',
	);

	foreach ( $rank_math_fields as $field ) {
		register_rest_field(
			array( 'page', 'post' ),
			$field,
			array(
				'get_callback' => function( $post ) use ( $field ) {
					return get_post_meta( $post['id'], $field, true );
				},
				'update_callback' => function( $value, $post ) use ( $field ) {
					if ( isset( $value ) ) {
						return update_post_meta( $post->ID, $field, sanitize_text_field( $value ) );
					}
				},
				'schema' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			)
		);
	}

	// Yoast meta fields
	$yoast_fields = array(
		'_yoast_wpseo_title',
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_meta-robots-noindex',
		'_yoast_wpseo_focuskw',
	);

	foreach ( $yoast_fields as $field ) {
		register_rest_field(
			array( 'page', 'post' ),
			$field,
			array(
				'get_callback' => function( $post ) use ( $field ) {
					return get_post_meta( $post['id'], $field, true );
				},
				'update_callback' => function( $value, $post ) use ( $field ) {
					if ( isset( $value ) ) {
						return update_post_meta( $post->ID, $field, sanitize_text_field( $value ) );
					}
				},
				'schema' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			)
		);
	}

	// All in One SEO meta fields
	$aioseo_fields = array(
		'_aioseo_title',
		'_aioseo_description',
		'_aioseo_robots',
	);

	foreach ( $aioseo_fields as $field ) {
		register_rest_field(
			array( 'page', 'post' ),
			$field,
			array(
				'get_callback' => function( $post ) use ( $field ) {
					return get_post_meta( $post['id'], $field, true );
				},
				'update_callback' => function( $value, $post ) use ( $field ) {
					if ( isset( $value ) ) {
						return update_post_meta( $post->ID, $field, sanitize_text_field( $value ) );
					}
				},
				'schema' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'wmc_register_seo_meta_fields' );

/**
 * Register the plugin's ability CATEGORY.
 *
 * In WP 6.9+ this MUST run on `wp_abilities_api_categories_init` — the core
 * function `wp_register_ability_category()` checks `doing_action()` and
 * returns null with a `_doing_it_wrong` notice if called from any other hook.
 * The categories hook fires before `wp_abilities_api_init`, so the category
 * exists by the time abilities reference it.
 */
function wmc_register_category() {
	if ( function_exists( 'wp_register_ability_category' ) ) {
		wp_register_ability_category(
			'wp-content-manager',
			array(
				'label'       => 'WordPress Content Manager',
				'description' => 'Abilities for managing all WordPress content via MCP.',
			)
		);
	}
}
add_action( 'wp_abilities_api_categories_init', 'wmc_register_category' );

/**
 * Register all of the plugin's abilities.
 *
 * In WP 6.9+ this MUST run on `wp_abilities_api_init` — `wp_register_ability()`
 * checks `doing_action()` and silently rejects (returns null + _doing_it_wrong)
 * if called from any other context. Earlier versions of this plugin attached
 * fallback callbacks on `init` and `abilities_api_init`, which actually made
 * things worse by calling the registration function in invalid contexts.
 */
function wmc_register_abilities() {
	if ( class_exists( 'WMC_Abilities' ) ) {
		WMC_Abilities::register_all_abilities();
	}
}
add_action( 'wp_abilities_api_init', 'wmc_register_abilities' );

/**
 * Diagnostic REST endpoint — exposed at /wp-json/wmc/v1/diagnose. Lets us
 * inspect, from outside the site, whether the plugin loaded, what hooks fired,
 * which Abilities-API helpers exist, and how many wmc/* abilities ended up
 * registered. Read-only and gated to `manage_options` so it's safe to ship.
 */
function wmc_register_diagnostic_route() {
	register_rest_route(
		'wmc/v1',
		'/diagnose',
		array(
			'methods'             => 'GET',
			'callback'            => 'wmc_diagnose_callback',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
}
add_action( 'rest_api_init', 'wmc_register_diagnostic_route' );

/**
 * Auto-Connect Endpoint — POST /wp-json/wmc/v1/connect
 *
 * Accepts username + password, validates them, and automatically creates
 * (or returns existing) an Application Password named "MCP Connector".
 * This means users never have to manually create Application Passwords.
 *
 * Rate-limited to 5 attempts per IP per hour to prevent brute-force.
 */
function wmc_register_connect_route() {
	register_rest_route(
		'wmc/v1',
		'/connect',
		array(
			'methods'             => 'POST',
			'callback'            => 'wmc_connect_callback',
			'permission_callback' => '__return_true', // public — validated inside
		)
	);
}
add_action( 'rest_api_init', 'wmc_register_connect_route' );

function wmc_connect_callback( WP_REST_Request $request ) {
	// --- Rate limiting ---
	$ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	$rate_key    = 'wmc_connect_attempts_' . md5( $ip );
	$attempts    = (int) get_transient( $rate_key );
	if ( $attempts >= 5 ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'rate_limited',
			'message' => 'Too many attempts. Please wait 1 hour before trying again.',
		), 429 );
	}
	set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

	// --- Get credentials from body ---
	$username = sanitize_text_field( $request->get_param( 'username' ) );
	$password = $request->get_param( 'password' ); // do not sanitize — passwords can have special chars

	if ( empty( $username ) || empty( $password ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'missing_credentials',
			'message' => 'username and password are required.',
		), 400 );
	}

	// --- Authenticate user ---
	$user = wp_authenticate( $username, $password );
	if ( is_wp_error( $user ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'invalid_credentials',
			'message' => 'Invalid username or password.',
		), 401 );
	}

	// --- Must be admin ---
	if ( ! user_can( $user, 'manage_options' ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'insufficient_permissions',
			'message' => 'The provided account does not have administrator permissions.',
		), 403 );
	}

	// --- Application Passwords must be available (WP 5.6+) ---
	if ( ! class_exists( 'WP_Application_Passwords' ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'app_passwords_unavailable',
			'message' => 'Application Passwords require WordPress 5.6 or higher.',
		), 500 );
	}

	$app_name = 'MCP Connector — Claude';

	// --- Check if one already exists ---
	$existing = WP_Application_Passwords::get_user_application_passwords( $user->ID );
	foreach ( $existing as $app_pass ) {
		if ( $app_pass['name'] === $app_name ) {
			// Already exists but we cannot retrieve the plain-text password again.
			// Revoke old one and create fresh so the caller gets usable credentials.
			WP_Application_Passwords::delete_application_password( $user->ID, $app_pass['uuid'] );
		}
	}

	// --- Create new Application Password ---
	$result = WP_Application_Passwords::create_new_application_password(
		$user->ID,
		array( 'name' => $app_name )
	);

	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'creation_failed',
			'message' => $result->get_error_message(),
		), 500 );
	}

	// $result[0] is the plain-text password (only available at creation time)
	$plain_password = $result[0];

	// Clear rate-limit on success
	delete_transient( $rate_key );

	return new WP_REST_Response( array(
		'success'      => true,
		'message'      => 'Connected successfully. Save the credentials below — the password cannot be retrieved again.',
		'site_url'     => get_site_url(),
		'site_name'    => get_bloginfo( 'name' ),
		'wp_version'   => get_bloginfo( 'version' ),
		'username'     => $user->user_login,
		'app_password' => $plain_password,
		'app_name'     => $app_name,
		'abilities_endpoint' => get_site_url() . '/wp-json/wp-abilities/v1/abilities',
		'diagnose_endpoint'  => get_site_url() . '/wp-json/wmc/v1/diagnose',
		'note'         => 'Use username + app_password for all future MCP API calls (HTTP Basic Auth).',
	), 200 );
}

/**
 * Secret Token Authentication
 *
 * Registers a REST authentication handler that accepts the WMC Secret Token
 * (from the admin dashboard) in the Authorization header or as a query param.
 * This means no username or application password is needed — just the token.
 *
 * Header:  Authorization: Bearer YOUR_SECRET_TOKEN
 * Or URL:  ?wmc_token=YOUR_SECRET_TOKEN
 */
add_filter( 'rest_authentication_errors', 'wmc_token_auth', 5 );
function wmc_token_auth( $result ) {
	// If another auth already succeeded/failed, don't interfere
	if ( ! empty( $result ) ) return $result;

	// Extract token from Authorization header or query string
	$token = '';
	$header = $_SERVER['HTTP_AUTHORIZATION'] ?? ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '' );
	if ( $header && stripos( $header, 'Bearer ' ) === 0 ) {
		$token = trim( substr( $header, 7 ) );
	}
	if ( empty( $token ) && ! empty( $_REQUEST['wmc_token'] ) ) {
		$token = sanitize_text_field( $_REQUEST['wmc_token'] );
	}
	if ( empty( $token ) ) return $result; // not a token-based request — let WP handle normally

	// Validate token
	$stored = get_option( 'wmc_secret_token', '' );
	if ( empty( $stored ) || ! hash_equals( $stored, $token ) ) {
		return new WP_Error( 'wmc_invalid_token', 'Invalid or missing WMC secret token.', array( 'status' => 401 ) );
	}

	// Token valid — authenticate as the site owner (first admin user)
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
	if ( empty( $admins ) ) return new WP_Error( 'wmc_no_admin', 'No administrator found.', array( 'status' => 500 ) );

	wp_set_current_user( $admins[0] );
	return true;
}

/**
 * Token info endpoint — GET /wp-json/wmc/v1/token-info
 * Returns site info when authenticated with the secret token.
 * Used by Claude to verify the connection is working.
 */
add_action( 'rest_api_init', function() {
	register_rest_route( 'wmc/v1', '/token-info', array(
		'methods'             => 'GET',
		'callback'            => function() {
			return array(
				'success'            => true,
				'connected'          => true,
				'site_url'           => get_site_url(),
				'site_name'          => get_bloginfo( 'name' ),
				'wp_version'         => get_bloginfo( 'version' ),
				'plugin_version'     => WMC_VERSION,
				'abilities_endpoint' => rest_url( 'wp-abilities/v1/abilities' ),
				'abilities_count'    => function_exists( 'wp_get_abilities' ) ? count( wp_get_abilities() ) : null,
				'message'            => 'Connection successful! WordPress MCP Connector is active.',
			);
		},
		'permission_callback' => function() {
			return current_user_can( 'manage_options' );
		},
	) );
} );

/**
 * AJAX: Regenerate secret token (called from admin dashboard)
 */
add_action( 'wp_ajax_wmc_regen_token', function() {
	check_ajax_referer( 'wmc_regen_token', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );
	$new_token = bin2hex( random_bytes( 32 ) );
	update_option( 'wmc_secret_token', $new_token, false );
	wp_send_json_success( array( 'token' => $new_token ) );
} );

function wmc_diagnose_callback() {
	global $wp_filter;

	// Count registered abilities via the public registry, if reachable.
	$wmc_abilities = array();
	if ( function_exists( 'wp_get_abilities' ) ) {
		$all = wp_get_abilities();
		foreach ( (array) $all as $a ) {
			$name = is_object( $a ) ? ( $a->name ?? '' ) : ( $a['name'] ?? '' );
			if ( $name && str_starts_with( (string) $name, 'wmc/' ) ) {
				$wmc_abilities[] = $name;
			}
		}
	}

	return array(
		'plugin_version'                          => defined( 'WMC_VERSION' ) ? WMC_VERSION : '(undefined)',
		'plugin_dir'                              => defined( 'WMC_PLUGIN_DIR' ) ? WMC_PLUGIN_DIR : '(undefined)',
		'wp_version'                              => get_bloginfo( 'version' ),
		'php_version'                             => PHP_VERSION,
		'class_WMC_Abilities_loaded'              => class_exists( 'WMC_Abilities' ),
		'class_WMC_Settings_loaded'               => class_exists( 'WMC_Settings' ),
		'fn_wp_register_ability'                  => function_exists( 'wp_register_ability' ),
		'fn_wp_register_ability_category'         => function_exists( 'wp_register_ability_category' ),
		'fn_wp_has_ability_category'              => function_exists( 'wp_has_ability_category' ),
		'fn_wp_get_abilities'                     => function_exists( 'wp_get_abilities' ),
		'did_wp_abilities_api_categories_init'    => (int) did_action( 'wp_abilities_api_categories_init' ),
		'did_wp_abilities_api_init'               => (int) did_action( 'wp_abilities_api_init' ),
		'did_init'                                => (int) did_action( 'init' ),
		'wmc_category_registered'                 => function_exists( 'wp_has_ability_category' )
			? wp_has_ability_category( 'wp-content-manager' )
			: 'unknown',
		'wmc_abilities_count'                     => count( $wmc_abilities ),
		'wmc_abilities'                           => $wmc_abilities,
	);
}

/**
 * Activation hook
 */
function wmc_activate() {
	// Ensure WordPress version supports Abilities API (6.9+)
	if ( version_compare( get_bloginfo( 'version' ), '6.9', '<' ) ) {
		wp_die( 'WordPress MCP Connector requires WordPress 6.9 or higher.' );
	}
}
register_activation_hook( __FILE__, 'wmc_activate' );

/**
 * Deactivation hook
 */
function wmc_deactivate() {
	// Cleanup if needed
}
register_deactivation_hook( __FILE__, 'wmc_deactivate' );
