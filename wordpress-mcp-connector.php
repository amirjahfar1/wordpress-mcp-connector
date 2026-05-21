<?php
/**
 * Plugin Name: WordPress MCP Connector
 * Plugin URI: https://github.com/essentialshoodie
 * Description: Exposes full WordPress CRUD operations via Abilities API for MCP integration. Manage posts, pages, categories, tags, media, comments, users, settings, menus, widgets, and themes.
 * Version: 2.2.0
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
define( 'WMC_VERSION', '2.2.0' );

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
