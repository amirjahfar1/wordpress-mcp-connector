<?php
/**
 * Plugin Name: WordPress MCP Connector
 * Plugin URI: https://nextbrainsolutions.com
 * Description: Exposes 171 abilities via Abilities API for MCP integration. Full control over WordPress, WooCommerce, files, database, advanced SEO (meta, Open Graph, Twitter Card, Schema, Sitemap, Redirects, Robots), security, backups, permalink/slug management, and product importing from other WooCommerce stores.
 * Version: 3.6.3
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
define( 'WMC_VERSION', '3.6.3' );

/**
 * Load the abilities and settings files
 */
require_once WMC_PLUGIN_DIR . 'includes/settings.php';
require_once WMC_PLUGIN_DIR . 'includes/abilities.php';
require_once WMC_PLUGIN_DIR . 'includes/woocommerce-extended.php';
require_once WMC_PLUGIN_DIR . 'includes/advanced-abilities.php';
require_once WMC_PLUGIN_DIR . 'includes/import-abilities.php';
require_once WMC_PLUGIN_DIR . 'includes/seo-abilities.php';

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
	if ( class_exists( 'WMC_SEO_Abilities' ) ) {
		WMC_SEO_Abilities::register_all();
	}
}
add_action( 'wp_abilities_api_init', 'wmc_register_abilities' );

/**
 * Inject WordPress MCP Connector system prompt into the MCP server.
 *
 * This runs on the `mcp_adapter_default_server_config` filter so every
 * MCP-compatible client (Claude, Cursor, ChatGPT, etc.) receives the
 * instructions automatically on connect — no manual setup needed.
 *
 * IMPORTANT: Every time a new ability is added to this plugin, the
 * `wmc_get_system_prompt()` function below MUST also be updated to
 * reflect the new ability so AI clients always have accurate instructions.
 */
add_filter( 'mcp_adapter_default_server_config', function( $config ) {
	$config['server_name']        = 'WordPress MCP Connector';
	$config['server_description'] = wmc_get_system_prompt();
	return $config;
} );

/**
 * Returns the system prompt / instructions sent to the AI client on connect.
 *
 * Describes what this plugin does, how to use it, and lists all available
 * ability groups with their key abilities so the AI knows what tools exist
 * and when to use them — without needing any external documentation.
 *
 * ── MANDATORY UPDATE RULE ──────────────────────────────────────────────────
 * Whenever a new ability is added to the plugin, you MUST update this
 * function to include that ability in the correct group below.
 * ───────────────────────────────────────────────────────────────────────────
 */
function wmc_get_system_prompt(): string {
	$site_name = get_bloginfo( 'name' );
	$site_url  = get_home_url();

	return <<<PROMPT
You are connected to "{$site_name}" ({$site_url}) via the WordPress MCP Connector plugin.

## How This Works
This plugin exposes all WordPress and WooCommerce management as MCP tools. Every task — creating posts, managing products, updating SEO, running backups, installing plugins — is done by calling the right tool. You do NOT need REST API credentials or direct database access. Just call the appropriate tool.

## Core Tools (always available)
- mcp-adapter/discover-abilities — List every available ability with name and description
- mcp-adapter/get-ability-info   — Get full details (parameters, description) for any ability
- mcp-adapter/execute-ability    — Execute any registered ability by name with parameters

## Available Ability Groups

### POSTS & PAGES
wmc/get-posts, wmc/create-post, wmc/update-post, wmc/delete-post
wmc/get-pages, wmc/create-page, wmc/update-page, wmc/delete-page
wmc/get-post-meta, wmc/update-post-meta

### CATEGORIES & TAGS
wmc/get-categories, wmc/create-category, wmc/update-category, wmc/delete-category
wmc/get-tags, wmc/create-tag, wmc/update-tag, wmc/delete-tag

### MEDIA
wmc/get-media, wmc/create-media, wmc/update-media, wmc/delete-media
wmc/get-media-details, wmc/get-media-without-alt, wmc/search-media
wmc/bulk-update-media, wmc/bulk-delete-media, wmc/compress-images

### USERS & SECURITY
wmc/get-users, wmc/create-user, wmc/update-user, wmc/delete-user
wmc/get-roles, wmc/assign-role, wmc/manage-user-sessions, wmc/reset-user-password
wmc/get-login-attempts, wmc/block-ip

### WOOCOMMERCE — PRODUCTS
wmc/get-woo-products, wmc/create-woo-product, wmc/update-woo-product, wmc/delete-woo-product
wmc/get-woo-product-detail, wmc/set-woo-product-image
wmc/create-variable-product, wmc/get-woo-variations, wmc/update-woo-variation, wmc/delete-woo-variation
wmc/get-woo-product-categories, wmc/create-woo-product-category, wmc/update-woo-product-category, wmc/delete-woo-product-category
wmc/get-woo-product-tags, wmc/create-woo-product-tag, wmc/delete-woo-product-tag
wmc/get-woo-product-meta, wmc/update-woo-product-meta
wmc/assign-woo-product-categories, wmc/get-woo-low-stock
wmc/bulk-update-product-status (via execute-ability)

### WOOCOMMERCE — ORDERS & CUSTOMERS
wmc/get-woo-orders, wmc/create-woo-order, wmc/get-woo-order-detail
wmc/update-woo-order-status, wmc/delete-woo-order, wmc/create-woo-refund
wmc/add-woo-order-note, wmc/get-woo-order-notes
wmc/get-woo-customers, wmc/get-woo-customer-detail, wmc/update-woo-customer, wmc/get-woo-customer-orders

### WOOCOMMERCE — COUPONS, REVIEWS, SHIPPING, TAX
wmc/get-woo-coupons, wmc/create-woo-coupon, wmc/update-woo-coupon, wmc/delete-woo-coupon, wmc/get-woo-coupon-usage
wmc/get-woo-reviews, wmc/create-woo-review, wmc/update-woo-review, wmc/delete-woo-review
wmc/get-woo-shipping-zones, wmc/create-woo-shipping-zone, wmc/add-woo-shipping-method, wmc/delete-woo-shipping-zone
wmc/get-woo-tax-rates, wmc/create-woo-tax-rate, wmc/delete-woo-tax-rate
wmc/get-woo-settings, wmc/update-woo-setting
wmc/get-woo-sales-report, wmc/get-woo-top-products

### SEO (Yoast / Rank Math / AIOSEO — auto-detected)
wmc/detect-seo-setup                        — ⚠️ ALWAYS call this first before any SEO operation. Returns active plugin, version, correct meta keys, and recommended ability per content type (post/page/product/term). Prevents trial-and-error.
wmc/get-post-seo, wmc/set-post-seo          — Read/write SEO for posts, pages, products
wmc/get-term-seo, wmc/set-term-seo          — Read/write SEO for categories, tags, product_cat, product_tag
wmc/set-open-graph, wmc/set-twitter-card    — Social meta (OG + Twitter/X Card)
wmc/add-schema-markup, wmc/get-schema-markup — JSON-LD schema (Article, Product, FAQ, HowTo, etc.)
wmc/set-canonical-url, wmc/set-post-robots  — Canonical URL and noindex/nofollow
wmc/set-focus-keyword, wmc/bulk-set-focus-keywords
wmc/bulk-set-robots, wmc/bulk-update-seo
wmc/get-seo-audit                           — SEO health score for a post
wmc/seo-overview-report, wmc/posts-missing-seo
wmc/get-sitemap-urls, wmc/ping-search-engines, wmc/exclude-from-sitemap
wmc/manage-robots-txt                       — Read/write robots.txt file
wmc/manage-redirects                        — 301/302 redirects (add, list, delete)
wmc/get-broken-links, wmc/get-404-logs

### THEMES, PLUGINS & SETTINGS
wmc/get-themes, wmc/activate-theme, wmc/get-theme-mods, wmc/update-theme-mod
wmc/get-plugins, wmc/activate-plugin, wmc/deactivate-plugin, wmc/install-plugin, wmc/delete-plugin
wmc/install-theme, wmc/get-options, wmc/update-option
wmc/get-menus, wmc/create-menu, wmc/delete-menu
wmc/get-sidebars, wmc/update-widget-option

### PERMALINKS & SLUGS
wmc/get-permalink-structure, wmc/set-permalink-structure, wmc/flush-rewrite-rules
wmc/get-post-slug, wmc/set-post-slug, wmc/check-slug-availability
wmc/get-category-slug, wmc/set-category-slug

### SERVER, FILES & DATABASE
wmc/get-server-info, wmc/get-site-health, wmc/get-cron-jobs
wmc/get-error-logs, wmc/clear-cache
wmc/read-file, wmc/write-file, wmc/list-files
wmc/execute-php                             — Run custom PHP (use carefully)
wmc/search-replace-db                       — Find & replace in database (handles serialized data)
wmc/create-backup, wmc/list-backups
wmc/manage-transients, wmc/run-wp-cli
wmc/manage-htaccess                         — Read/write .htaccess file

### PRODUCT IMPORT
wmc/preview-woo-import, wmc/import-woo-products, wmc/import-woo-categories
Import products from any public WooCommerce store (no API key needed).

### COMMENTS
wmc/get-comments, wmc/moderate-comment, wmc/delete-comment

## Key Rules
0. ALWAYS call wmc/detect-seo-setup first before any SEO read/write — it tells you the active plugin, correct meta keys, and which ability to use. Never assume.
1. For posts/pages/products SEO → use wmc/get-post-seo and wmc/set-post-seo (post_id is the WooCommerce product ID too)
2. For category/tag SEO → use wmc/get-term-seo and wmc/set-term-seo (specify taxonomy: category, post_tag, product_cat, product_tag)
3. WooCommerce reviews require comment_approved = '1' (integer string), not 'approve'
4. To discover all available abilities call mcp-adapter/discover-abilities first if unsure which tool to use
5. All abilities check if they are enabled in the plugin settings — if disabled, the response will tell you how to enable them
PROMPT;
}

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
 * AJAX: Generate Application Password for MCP (called from admin dashboard)
 */
add_action( 'wp_ajax_wmc_gen_app_password', function() {
	check_ajax_referer( 'wmc_gen_app_password', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

	$user_id = intval( $_POST['user_id'] ?? 0 );
	if ( ! $user_id ) wp_send_json_error( 'Invalid user.' );

	$user = get_user_by( 'ID', $user_id );
	if ( ! $user || ! user_can( $user, 'manage_options' ) ) {
		wp_send_json_error( 'User not found or not an administrator.' );
	}

	if ( ! class_exists( 'WP_Application_Passwords' ) ) {
		wp_send_json_error( 'Application Passwords require WordPress 5.6+.' );
	}

	$app_name = 'Claude MCP Connector';

	// Delete existing password with the same name so we can create fresh
	$existing = WP_Application_Passwords::get_user_application_passwords( $user_id );
	foreach ( $existing as $app ) {
		if ( $app['name'] === $app_name ) {
			WP_Application_Passwords::delete_application_password( $user_id, $app['uuid'] );
		}
	}

	$result = WP_Application_Passwords::create_new_application_password(
		$user_id,
		array( 'name' => $app_name )
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}

	// Format password with spaces (groups of 4) — required by @automattic/mcp-wordpress-remote
	// and standard WordPress Application Password format: xxxx xxxx xxxx xxxx xxxx xxxx
	$raw      = $result[0];
	$chunks   = str_split( $raw, 4 );
	$password = implode( ' ', $chunks );

	wp_send_json_success( array(
		'password' => $password,
		'username' => $user->user_login,
		'site_url' => get_site_url(),
	) );
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
	if ( version_compare( get_bloginfo( 'version' ), '6.9', '<' ) ) {
		wp_die( 'WordPress MCP Connector requires WordPress 6.9 or higher.' );
	}
	set_transient( 'wmc_activation_redirect', true, 30 );
}
register_activation_hook( __FILE__, 'wmc_activate' );

// Redirect to settings page after activation
add_action( 'admin_init', function() {
	if ( get_transient( 'wmc_activation_redirect' ) ) {
		delete_transient( 'wmc_activation_redirect' );
		if ( ! isset( $_GET['activate-multi'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=wmc-settings' ) );
			exit;
		}
	}
} );

/**
 * Deactivation hook
 */
function wmc_deactivate() {
	// Cleanup if needed
}
register_deactivation_hook( __FILE__, 'wmc_deactivate' );

/**
 * Fix MIME type detection for external images sideloaded via WooCommerce CSV importer
 * or any media_sideload_image() call. WordPress sometimes can't detect MIME type from
 * the file extension alone when downloading from external URLs — this filter fills the
 * gap by mapping common image extensions to their correct MIME types.
 */
add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes, $real_mime ) {
	if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
		return $data; // already detected — leave it alone
	}

	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	$map = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'webp' => 'image/webp',
		'avif' => 'image/avif',
		'svg'  => 'image/svg+xml',
		'ico'  => 'image/x-icon',
	);

	if ( isset( $map[ $ext ] ) ) {
		$data['ext']  = $ext === 'jpg' ? 'jpg' : $ext;
		$data['type'] = $map[ $ext ];
	}

	return $data;
}, 10, 5 );

/**
 * Increase timeout for downloading external images (WooCommerce CSV importer uses
 * wp_remote_get under the hood — default 5s is too short for large product images).
 */
add_filter( 'http_request_timeout', function( $timeout ) {
	return max( $timeout, 60 );
} );
