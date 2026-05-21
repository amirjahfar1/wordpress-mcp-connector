<?php
/**
 * Plugin Name: WordPress MCP Connector
 * Plugin URI: https://github.com/essentialshoodie
 * Description: Exposes full WordPress CRUD operations via Abilities API for MCP integration. Manage posts, pages, categories, tags, media, comments, users, settings, menus, widgets, and themes.
 * Version: 2.2.1
 * Author: WordPress MCP Team
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
define( 'WMC_VERSION', '2.2.1' );

/**
 * Load the abilities and settings files
 */
require_once WMC_PLUGIN_DIR . 'includes/settings.php';
require_once WMC_PLUGIN_DIR . 'includes/abilities.php';

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
 * Idempotent abilities-registration entry point.
 *
 * Calls WMC_Abilities::register_all_abilities() at most once per request, and
 * only when the Abilities API is actually available. We hang this function off
 * three hooks (see wmc_init below) because the Abilities API has moved hook
 * names between releases: pre-core Automattic plugin used `wp_abilities_api_init`,
 * the public docs still use `wp_abilities_api_init`, but some WP 6.9 builds fire
 * `abilities_api_init` instead. Whichever fires first wins; the guard prevents
 * double-registration.
 */
function wmc_register_abilities_once() {
	static $done = false;
	if ( $done ) {
		return;
	}
	if ( ! function_exists( 'wp_register_ability' ) ) {
		// Abilities API not loaded yet on this hook — let a later hook try.
		return;
	}
	$done = true;
	WMC_Abilities::register_all_abilities();
}

/**
 * Initialize the plugin
 */
function wmc_init() {
	// Primary hook (per official Abilities API docs).
	add_action( 'wp_abilities_api_init', 'wmc_register_abilities_once' );
	// Alternate name observed in some WP 6.9+ core builds.
	add_action( 'abilities_api_init', 'wmc_register_abilities_once' );
	// Hard fallback — runs after both abilities-specific hooks would have fired.
	// Idempotency guard above ensures we don't double-register.
	add_action( 'init', 'wmc_register_abilities_once', 20 );
}
add_action( 'plugins_loaded', 'wmc_init' );

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
		'plugin_version'                  => defined( 'WMC_VERSION' ) ? WMC_VERSION : '(undefined)',
		'plugin_dir'                      => defined( 'WMC_PLUGIN_DIR' ) ? WMC_PLUGIN_DIR : '(undefined)',
		'wp_version'                      => get_bloginfo( 'version' ),
		'php_version'                     => PHP_VERSION,
		'class_WMC_Abilities_loaded'      => class_exists( 'WMC_Abilities' ),
		'class_WMC_Settings_loaded'       => class_exists( 'WMC_Settings' ),
		'fn_wp_register_ability'          => function_exists( 'wp_register_ability' ),
		'fn_wp_register_ability_category' => function_exists( 'wp_register_ability_category' ),
		'fn_wp_get_abilities'             => function_exists( 'wp_get_abilities' ),
		'did_wp_abilities_api_init'       => (int) did_action( 'wp_abilities_api_init' ),
		'did_abilities_api_init'          => (int) did_action( 'abilities_api_init' ),
		'did_init'                        => (int) did_action( 'init' ),
		'wmc_abilities_count'             => count( $wmc_abilities ),
		'wmc_abilities'                   => $wmc_abilities,
		'register_callbacks_attached'     => array(
			'wp_abilities_api_init' => isset( $wp_filter['wp_abilities_api_init'] ),
			'abilities_api_init'    => isset( $wp_filter['abilities_api_init'] ),
			'init'                  => isset( $wp_filter['init'] ),
		),
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
