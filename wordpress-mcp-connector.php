<?php
/**
 * Plugin Name: WordPress MCP Connector
 * Plugin URI: https://github.com/essentialshoodie
 * Description: Exposes full WordPress CRUD operations via Abilities API for MCP integration. Manage posts, pages, categories, tags, media, comments, users, settings, menus, widgets, and themes.
 * Version: 2.1.0
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
define( 'WMC_VERSION', '2.0.0' );

/**
 * Load the abilities and settings files
 */
require_once WMC_PLUGIN_DIR . 'includes/settings.php';
require_once WMC_PLUGIN_DIR . 'includes/abilities.php';

/**
 * Initialize the plugin
 */
function wmc_init() {
	// Hook into WordPress Abilities API initialization
	add_action( 'wp_abilities_api_init', array( 'WMC_Abilities', 'register_all_abilities' ) );
}
add_action( 'plugins_loaded', 'wmc_init' );

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
