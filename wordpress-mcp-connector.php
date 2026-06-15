<?php
/**
 * Plugin Name: WordPress MCP Connector
 * Plugin URI: https://nextbrainsolutions.com
 * Description: Exposes full WordPress CRUD operations via Abilities API for MCP integration. Manage posts, pages, categories, tags, media, comments, users, settings, menus, widgets, themes, plugins, roles, WooCommerce, and system maintenance.
 * Version: 2.6.0
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
define( 'WMC_VERSION', '2.6.0' );

/**
 * Load the abilities and settings files
 */
require_once WMC_PLUGIN_DIR . 'includes/settings.php';
require_once WMC_PLUGIN_DIR . 'includes/abilities.php';

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
