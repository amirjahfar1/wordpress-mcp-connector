<?php
/**
 * Advanced Abilities for WordPress MCP Connector
 * Tier 1: Full Control | Tier 2: Developer | Tier 4: SEO & Performance | Tier 5: Security & Backup
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WMC_Advanced_Abilities {

	private static function register( $name, $args ) {
		if ( ! function_exists( 'wp_register_ability' ) ) return null;
		if ( ! isset( $args['meta'] ) )         $args['meta']             = array();
		if ( ! isset( $args['meta']['mcp'] ) )  $args['meta']['mcp']      = array();
		$args['meta']['show_in_rest']     = true;
		$args['meta']['mcp']['public']    = true;
		return wp_register_ability( $name, $args );
	}

	private static function admin() {
		return current_user_can( 'manage_options' );
	}

	// ================================================================
	//  REGISTER ALL
	// ================================================================

	public static function register_all() {
		self::register_tier1();
		self::register_tier2();
		self::register_tier4();
		self::register_tier5();
		self::register_permalink_abilities();
	}

	// ================================================================
	//  TIER 1 — FULL CONTROL
	// ================================================================

	private static function register_tier1() {

		// ---------- install-plugin ----------
		self::register( 'wmc/install-plugin', array(
			'label'       => 'Install Plugin',
			'description' => 'Install a plugin from WordPress.org by slug, or from a ZIP URL',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'slug'    => array( 'type' => 'string', 'description' => 'WordPress.org plugin slug (e.g. "contact-form-7")' ),
					'zip_url' => array( 'type' => 'string', 'description' => 'Direct URL to plugin ZIP (alternative to slug)' ),
					'activate'=> array( 'type' => 'boolean', 'default' => true, 'description' => 'Activate after install' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'install_plugin' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- install-theme ----------
		self::register( 'wmc/install-theme', array(
			'label'       => 'Install Theme',
			'description' => 'Install a theme from WordPress.org by slug, or from a ZIP URL',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'slug'    => array( 'type' => 'string', 'description' => 'WordPress.org theme slug' ),
					'zip_url' => array( 'type' => 'string', 'description' => 'Direct URL to theme ZIP' ),
					'activate'=> array( 'type' => 'boolean', 'default' => false ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'install_theme' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- delete-plugin ----------
		self::register( 'wmc/delete-plugin', array(
			'label'       => 'Delete Plugin',
			'description' => 'Deactivate and permanently delete an installed plugin',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'plugin' => array( 'type' => 'string', 'description' => 'Plugin file path e.g. "contact-form-7/wp-contact-form-7.php"' ),
				),
				'required' => array( 'plugin' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_plugin' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- execute-php ----------
		self::register( 'wmc/execute-php', array(
			'label'       => 'Execute PHP',
			'description' => 'Run custom PHP code on the server and return the output. Use with caution.',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'code' => array( 'type' => 'string', 'description' => 'PHP code to execute (without <?php tag). Return or echo output.' ),
				),
				'required' => array( 'code' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'execute_php' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-options ----------
		self::register( 'wmc/get-options', array(
			'label'       => 'Get WordPress Options',
			'description' => 'Read one or multiple wp_options values directly',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'keys'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Array of option names to retrieve' ),
					'search' => array( 'type' => 'string', 'description' => 'Partial match to list matching option names' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_options' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- update-option ----------
		self::register( 'wmc/update-option', array(
			'label'       => 'Update WordPress Option',
			'description' => 'Write a value to wp_options table directly',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'option_name'  => array( 'type' => 'string', 'description' => 'Option name' ),
					'option_value' => array( 'description' => 'New value (string, number, array, or object)' ),
					'autoload'     => array( 'type' => 'string', 'enum' => array( 'yes', 'no' ), 'default' => 'yes' ),
				),
				'required' => array( 'option_name', 'option_value' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_option_val' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- search-replace-db ----------
		self::register( 'wmc/search-replace-db', array(
			'label'       => 'Search & Replace in Database',
			'description' => 'Find and replace text in WordPress database tables (handles serialized data). Useful for URL migration.',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'search'     => array( 'type' => 'string', 'description' => 'Text to search for' ),
					'replace'    => array( 'type' => 'string', 'description' => 'Text to replace with' ),
					'tables'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Specific tables to search (empty = all wp_ tables)' ),
					'dry_run'    => array( 'type' => 'boolean', 'default' => true, 'description' => 'Preview changes without writing (recommended first)' ),
				),
				'required' => array( 'search', 'replace' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'search_replace_db' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-error-logs ----------
		self::register( 'wmc/get-error-logs', array(
			'label'       => 'Get Error Logs',
			'description' => 'Read PHP error log or WordPress debug.log',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'type'  => array( 'type' => 'string', 'enum' => array( 'php', 'wordpress', 'woocommerce' ), 'default' => 'wordpress' ),
					'lines' => array( 'type' => 'integer', 'description' => 'Number of last lines to return', 'default' => 100 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_error_logs' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-server-info ----------
		self::register( 'wmc/get-server-info', array(
			'label'       => 'Get Server Info',
			'description' => 'PHP version, memory limit, disk space, WordPress config, active extensions',
			'category'    => 'wp-content-manager',
			'input_schema' => array( 'type' => 'object', 'properties' => array() ),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_server_info' ),
			'permission_callback' => fn() => self::admin(),
		) );
	}

	// ================================================================
	//  TIER 2 — DEVELOPER CONTROL
	// ================================================================

	private static function register_tier2() {

		// ---------- read-file ----------
		self::register( 'wmc/read-file', array(
			'label'       => 'Read File',
			'description' => 'Read contents of a theme or plugin file',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'path'  => array( 'type' => 'string', 'description' => 'File path relative to ABSPATH (e.g. "wp-content/themes/ewebot/functions.php")' ),
					'lines' => array( 'type' => 'integer', 'description' => 'Max lines to return (default: all)' ),
				),
				'required' => array( 'path' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'read_file' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- write-file ----------
		self::register( 'wmc/write-file', array(
			'label'       => 'Write File',
			'description' => 'Write or overwrite a file (theme/plugin). Creates file if not exists.',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'path'    => array( 'type' => 'string', 'description' => 'File path relative to ABSPATH' ),
					'content' => array( 'type' => 'string', 'description' => 'Full file content to write' ),
					'append'  => array( 'type' => 'boolean', 'default' => false, 'description' => 'Append instead of overwrite' ),
				),
				'required' => array( 'path', 'content' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'write_file' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- list-files ----------
		self::register( 'wmc/list-files', array(
			'label'       => 'List Files',
			'description' => 'List files and directories in a given path',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'path'      => array( 'type' => 'string', 'description' => 'Path relative to ABSPATH (e.g. "wp-content/themes/ewebot")' ),
					'recursive' => array( 'type' => 'boolean', 'default' => false ),
					'extension' => array( 'type' => 'string', 'description' => 'Filter by extension (e.g. "php", "css")' ),
				),
				'required' => array( 'path' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'list_files' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- run-wp-cli ----------
		self::register( 'wmc/run-wp-cli', array(
			'label'       => 'Run WP-CLI Command',
			'description' => 'Execute a WP-CLI command on the server (requires WP-CLI installed)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'command' => array( 'type' => 'string', 'description' => 'WP-CLI command without "wp" prefix (e.g. "cache flush", "db export backup.sql")' ),
				),
				'required' => array( 'command' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'run_wp_cli' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- manage-transients ----------
		self::register( 'wmc/manage-transients', array(
			'label'       => 'Manage Transients',
			'description' => 'Get, set, or delete WordPress transients',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'action'     => array( 'type' => 'string', 'enum' => array( 'get', 'set', 'delete', 'delete_all' ), 'description' => 'Action to perform' ),
					'key'        => array( 'type' => 'string', 'description' => 'Transient key (not needed for delete_all)' ),
					'value'      => array( 'description' => 'Value (for set action only)' ),
					'expiration' => array( 'type' => 'integer', 'description' => 'Expiry in seconds (for set action, 0 = no expiry)' ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'manage_transients' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- manage-redirects ----------
		self::register( 'wmc/manage-redirects', array(
			'label'       => 'Manage Redirects',
			'description' => 'Get, add, or delete 301/302 redirects (stored in wp_options)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'action'  => array( 'type' => 'string', 'enum' => array( 'list', 'add', 'delete' ) ),
					'from'    => array( 'type' => 'string', 'description' => 'Source URL path (e.g. "/old-page")' ),
					'to'      => array( 'type' => 'string', 'description' => 'Destination URL' ),
					'type'    => array( 'type' => 'integer', 'enum' => array( 301, 302 ), 'default' => 301 ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'manage_redirects' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-post-meta ----------
		self::register( 'wmc/get-post-meta', array(
			'label'       => 'Get Post Meta',
			'description' => 'Read custom fields / ACF fields for any post or page',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'post_id'  => array( 'type' => 'integer' ),
					'meta_key' => array( 'type' => 'string', 'description' => 'Specific key (omit to get all meta)' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_post_meta_all' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- update-post-meta ----------
		self::register( 'wmc/update-post-meta', array(
			'label'       => 'Update Post Meta',
			'description' => 'Set or update a custom field on any post or page',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'post_id'    => array( 'type' => 'integer' ),
					'meta_key'   => array( 'type' => 'string' ),
					'meta_value' => array( 'description' => 'Value to set' ),
					'delete'     => array( 'type' => 'boolean', 'default' => false, 'description' => 'Delete this meta key instead of updating' ),
				),
				'required' => array( 'post_id', 'meta_key' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_post_meta_val' ),
			'permission_callback' => fn() => self::admin(),
		) );
	}

	// ================================================================
	//  TIER 4 — SEO & PERFORMANCE
	// ================================================================

	private static function register_tier4() {

		// ---------- bulk-update-seo ----------
		self::register( 'wmc/bulk-update-seo', array(
			'label'       => 'Bulk Update SEO Meta',
			'description' => 'Update SEO title and description for multiple posts/pages at once (Yoast, Rank Math, AIOSEO)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'items' => array(
						'type'  => 'array',
						'description' => '[{"post_id":1,"seo_title":"New Title","seo_description":"New Desc"}, ...]',
						'items' => array(
							'type' => 'object',
							'properties' => array(
								'post_id'         => array( 'type' => 'integer' ),
								'seo_title'       => array( 'type' => 'string' ),
								'seo_description' => array( 'type' => 'string' ),
							),
						),
					),
					'plugin' => array( 'type' => 'string', 'enum' => array( 'yoast', 'rankmath', 'aioseo', 'auto' ), 'default' => 'auto', 'description' => 'SEO plugin to target (auto = detect)' ),
				),
				'required' => array( 'items' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'bulk_update_seo' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-broken-links ----------
		self::register( 'wmc/get-broken-links', array(
			'label'       => 'Get Broken Links',
			'description' => 'Scan post content for internal broken links (404 pages)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'post_type' => array( 'type' => 'string', 'default' => 'post', 'description' => 'post, page, product, etc.' ),
					'limit'     => array( 'type' => 'integer', 'default' => 50 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_broken_links' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-404-logs ----------
		self::register( 'wmc/get-404-logs', array(
			'label'       => 'Get 404 Error Logs',
			'description' => 'List recent 404 errors tracked by WordPress (stored in transient/option)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'limit' => array( 'type' => 'integer', 'default' => 50 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_404_logs' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- manage-robots-txt ----------
		self::register( 'wmc/manage-robots-txt', array(
			'label'       => 'Manage robots.txt',
			'description' => 'Read or write the robots.txt file',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'action'  => array( 'type' => 'string', 'enum' => array( 'read', 'write' ) ),
					'content' => array( 'type' => 'string', 'description' => 'New robots.txt content (for write action)' ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'manage_robots_txt' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- manage-htaccess ----------
		self::register( 'wmc/manage-htaccess', array(
			'label'       => 'Manage .htaccess',
			'description' => 'Read or write the .htaccess file',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'action'  => array( 'type' => 'string', 'enum' => array( 'read', 'write', 'append' ) ),
					'content' => array( 'type' => 'string', 'description' => 'Content to write or append (for write/append actions)' ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'manage_htaccess' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- compress-images ----------
		self::register( 'wmc/compress-images', array(
			'label'       => 'Compress / Regenerate Images',
			'description' => 'Regenerate WordPress image thumbnails for selected media items',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'media_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Specific media IDs (empty = regenerate all)' ),
					'limit'     => array( 'type' => 'integer', 'default' => 10, 'description' => 'Max images to process per call' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'compress_images' ),
			'permission_callback' => fn() => self::admin(),
		) );
	}

	// ================================================================
	//  TIER 5 — SECURITY & BACKUP
	// ================================================================

	private static function register_tier5() {

		// ---------- get-login-attempts ----------
		self::register( 'wmc/get-login-attempts', array(
			'label'       => 'Get Login Attempts',
			'description' => 'List recent failed login attempts tracked by WordPress',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'limit' => array( 'type' => 'integer', 'default' => 50 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_login_attempts' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- block-ip ----------
		self::register( 'wmc/block-ip', array(
			'label'       => 'Block / Unblock IP Address',
			'description' => 'Add or remove an IP from the WordPress blocked list (stored in wp_options)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'action' => array( 'type' => 'string', 'enum' => array( 'block', 'unblock', 'list' ) ),
					'ip'     => array( 'type' => 'string', 'description' => 'IP address (not needed for list action)' ),
					'reason' => array( 'type' => 'string', 'description' => 'Optional reason for blocking' ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'block_ip' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- create-backup ----------
		self::register( 'wmc/create-backup', array(
			'label'       => 'Create Backup',
			'description' => 'Export WordPress database as SQL file to wp-content/uploads/wmc-backups/',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'type'      => array( 'type' => 'string', 'enum' => array( 'database', 'uploads', 'theme' ), 'default' => 'database' ),
					'filename'  => array( 'type' => 'string', 'description' => 'Custom filename (without extension)' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_backup' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- list-backups ----------
		self::register( 'wmc/list-backups', array(
			'label'       => 'List Backups',
			'description' => 'List all backup files created by the MCP Connector',
			'category'    => 'wp-content-manager',
			'input_schema' => array( 'type' => 'object', 'properties' => array() ),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'list_backups' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- manage-user-sessions ----------
		self::register( 'wmc/manage-user-sessions', array(
			'label'       => 'Manage User Sessions',
			'description' => 'List all active sessions for a user, or terminate all sessions',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'action'  => array( 'type' => 'string', 'enum' => array( 'list', 'destroy_all', 'destroy_others' ) ),
					'user_id' => array( 'type' => 'integer', 'description' => 'Target user ID (0 = current user)' ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'manage_user_sessions' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- reset-user-password ----------
		self::register( 'wmc/reset-user-password', array(
			'label'       => 'Reset User Password',
			'description' => 'Set a new password for a WordPress user',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'user_id'      => array( 'type' => 'integer', 'description' => 'User ID' ),
					'new_password' => array( 'type' => 'string', 'description' => 'New password (min 8 chars). Leave empty to auto-generate.' ),
					'notify_user'  => array( 'type' => 'boolean', 'default' => false, 'description' => 'Send password reset email to user' ),
				),
				'required' => array( 'user_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'reset_user_password' ),
			'permission_callback' => fn() => self::admin(),
		) );
	}

	// ================================================================
	//  EXECUTE CALLBACKS — TIER 1
	// ================================================================

	public static function install_plugin( $input ) {
		if ( ! current_user_can( 'install_plugins' ) ) return array( 'success' => false, 'message' => 'Permission denied' );

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );

		if ( ! empty( $input['zip_url'] ) ) {
			$result = $upgrader->install( esc_url_raw( $input['zip_url'] ) );
		} elseif ( ! empty( $input['slug'] ) ) {
			$api = plugins_api( 'plugin_information', array( 'slug' => sanitize_key( $input['slug'] ) ) );
			if ( is_wp_error( $api ) ) return array( 'success' => false, 'message' => $api->get_error_message() );
			$result = $upgrader->install( $api->download_link );
		} else {
			return array( 'success' => false, 'message' => 'Provide slug or zip_url' );
		}

		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );

		$plugin_file = $upgrader->plugin_info();
		if ( ! empty( $input['activate'] ) && $plugin_file ) {
			activate_plugin( $plugin_file );
		}
		return array( 'success' => true, 'plugin' => $plugin_file, 'activated' => ! empty( $input['activate'] ), 'message' => 'Plugin installed successfully' );
	}

	public static function install_theme( $input ) {
		if ( ! current_user_can( 'install_themes' ) ) return array( 'success' => false, 'message' => 'Permission denied' );

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/theme-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Theme_Upgrader( $skin );

		if ( ! empty( $input['zip_url'] ) ) {
			$result = $upgrader->install( esc_url_raw( $input['zip_url'] ) );
		} elseif ( ! empty( $input['slug'] ) ) {
			$api = themes_api( 'theme_information', array( 'slug' => sanitize_key( $input['slug'] ) ) );
			if ( is_wp_error( $api ) ) return array( 'success' => false, 'message' => $api->get_error_message() );
			$result = $upgrader->install( $api->download_link );
		} else {
			return array( 'success' => false, 'message' => 'Provide slug or zip_url' );
		}

		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		if ( ! empty( $input['activate'] ) ) {
			switch_theme( sanitize_key( $input['slug'] ?? '' ) );
		}
		return array( 'success' => true, 'activated' => ! empty( $input['activate'] ), 'message' => 'Theme installed successfully' );
	}

	public static function delete_plugin( $input ) {
		if ( ! current_user_can( 'delete_plugins' ) ) return array( 'success' => false, 'message' => 'Permission denied' );
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$plugin = sanitize_text_field( $input['plugin'] );
		if ( is_plugin_active( $plugin ) ) deactivate_plugins( $plugin );
		$result = delete_plugins( array( $plugin ) );
		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		return array( 'success' => true, 'message' => 'Plugin deleted: ' . $plugin );
	}

	public static function execute_php( $input ) {
		$code = $input['code'];
		ob_start();
		try {
			$return_val = eval( $code );
		} catch ( Throwable $e ) {
			ob_end_clean();
			return array( 'success' => false, 'error' => $e->getMessage() );
		}
		$output = ob_get_clean();
		return array(
			'success'      => true,
			'output'       => $output,
			'return_value' => is_string( $return_val ) || is_numeric( $return_val ) ? $return_val : json_encode( $return_val ),
		);
	}

	public static function get_options( $input ) {
		global $wpdb;
		if ( ! empty( $input['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( sanitize_text_field( $input['search'] ) ) . '%';
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 100", $like
			) );
			$options = array();
			foreach ( $results as $row ) {
				$options[ $row->option_name ] = maybe_unserialize( $row->option_value );
			}
			return array( 'success' => true, 'count' => count( $options ), 'options' => $options );
		}
		if ( ! empty( $input['keys'] ) && is_array( $input['keys'] ) ) {
			$options = array();
			foreach ( $input['keys'] as $key ) {
				$options[ $key ] = get_option( sanitize_key( $key ) );
			}
			return array( 'success' => true, 'options' => $options );
		}
		return array( 'success' => false, 'message' => 'Provide keys array or search string' );
	}

	public static function update_option_val( $input ) {
		$name  = sanitize_text_field( $input['option_name'] );
		$value = $input['option_value'];
		update_option( $name, $value, $input['autoload'] ?? 'yes' );
		return array( 'success' => true, 'option' => $name, 'new_value' => get_option( $name ), 'message' => 'Option updated' );
	}

	public static function search_replace_db( $input ) {
		global $wpdb;
		$search  = $input['search'];
		$replace = $input['replace'];
		$dry_run = $input['dry_run'] ?? true;

		$all_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
		$tables     = ! empty( $input['tables'] ) ? array_intersect( $input['tables'], $all_tables ) : $all_tables;

		$report = array();
		$total  = 0;

		foreach ( $tables as $table ) {
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A );
			$primary = '';
			$col_names = array();
			foreach ( $columns as $col ) {
				if ( $col['Key'] === 'PRI' ) $primary = $col['Field'];
				$col_names[] = $col['Field'];
			}
			if ( ! $primary ) continue;

			$count = 0;
			foreach ( $col_names as $col ) {
				$found = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE `{$col}` LIKE %s",
					'%' . $wpdb->esc_like( $search ) . '%'
				) );
				if ( $found > 0 ) {
					$count += (int) $found;
					if ( ! $dry_run ) {
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT `{$primary}`, `{$col}` FROM `{$table}` WHERE `{$col}` LIKE %s",
							'%' . $wpdb->esc_like( $search ) . '%'
						), ARRAY_A );
						foreach ( $rows as $row ) {
							$old = $row[ $col ];
							$new = self::recursive_unserialize_replace( $search, $replace, $old );
							if ( $new !== $old ) {
								$wpdb->update( $table, array( $col => $new ), array( $primary => $row[ $primary ] ) );
							}
						}
					}
				}
			}
			if ( $count > 0 ) {
				$report[] = array( 'table' => $table, 'matches' => $count );
				$total   += $count;
			}
		}

		return array(
			'success'  => true,
			'dry_run'  => $dry_run,
			'search'   => $search,
			'replace'  => $dry_run ? '(preview only)' : $replace,
			'total'    => $total,
			'tables'   => $report,
			'message'  => $dry_run ? "Found {$total} matches. Set dry_run=false to apply changes." : "Replaced {$total} occurrences.",
		);
	}

	private static function recursive_unserialize_replace( $search, $replace, $data ) {
		if ( is_string( $data ) ) {
			$unserialized = @unserialize( $data );
			if ( $unserialized !== false ) {
				$replaced = self::recursive_unserialize_replace( $search, $replace, $unserialized );
				return serialize( $replaced );
			}
			return str_replace( $search, $replace, $data );
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$data[ $k ] = self::recursive_unserialize_replace( $search, $replace, $v );
			}
		}
		return $data;
	}

	public static function get_error_logs( $input ) {
		$type  = $input['type'] ?? 'wordpress';
		$lines = (int) ( $input['lines'] ?? 100 );

		switch ( $type ) {
			case 'wordpress':
				$log_file = WP_CONTENT_DIR . '/debug.log';
				break;
			case 'woocommerce':
				$logs_dir = WP_CONTENT_DIR . '/uploads/wc-logs/';
				$files    = glob( $logs_dir . '*.log' );
				$log_file = $files ? end( $files ) : '';
				break;
			default:
				$log_file = ini_get( 'error_log' );
				break;
		}

		if ( ! $log_file || ! file_exists( $log_file ) ) {
			return array( 'success' => false, 'message' => 'Log file not found: ' . ( $log_file ?: 'unknown' ) );
		}

		$content = file( $log_file );
		$content = array_slice( $content, -$lines );
		return array(
			'success'  => true,
			'log_file' => $log_file,
			'lines'    => count( $content ),
			'content'  => implode( '', $content ),
		);
	}

	public static function get_server_info( $input ) {
		$disk_total = @disk_total_space( ABSPATH );
		$disk_free  = @disk_free_space( ABSPATH );
		return array(
			'success' => true,
			'server'  => array(
				'php_version'        => PHP_VERSION,
				'php_sapi'           => PHP_SAPI,
				'memory_limit'       => ini_get( 'memory_limit' ),
				'memory_usage'       => size_format( memory_get_usage( true ) ),
				'memory_peak'        => size_format( memory_get_peak_usage( true ) ),
				'max_execution_time' => ini_get( 'max_execution_time' ),
				'upload_max_size'    => ini_get( 'upload_max_filesize' ),
				'post_max_size'      => ini_get( 'post_max_size' ),
				'disk_total'         => $disk_total ? size_format( $disk_total ) : 'N/A',
				'disk_free'          => $disk_free ? size_format( $disk_free ) : 'N/A',
				'disk_used_pct'      => $disk_total ? round( ( $disk_total - $disk_free ) / $disk_total * 100, 1 ) . '%' : 'N/A',
				'os'                 => PHP_OS,
				'server_software'    => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
				'extensions'         => get_loaded_extensions(),
			),
			'wordpress' => array(
				'version'          => get_bloginfo( 'version' ),
				'site_url'         => get_site_url(),
				'abspath'          => ABSPATH,
				'wp_content_dir'   => WP_CONTENT_DIR,
				'wp_debug'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'wp_debug_log'     => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
				'db_version'       => get_bloginfo( 'version' ),
				'active_plugins'   => get_option( 'active_plugins' ),
				'active_theme'     => wp_get_theme()->get( 'Name' ),
			),
		);
	}

	// ================================================================
	//  EXECUTE CALLBACKS — TIER 2
	// ================================================================

	public static function read_file( $input ) {
		$path = realpath( ABSPATH . sanitize_text_field( $input['path'] ) );
		if ( ! $path || strpos( $path, realpath( ABSPATH ) ) !== 0 ) {
			return array( 'success' => false, 'message' => 'Invalid or disallowed path' );
		}
		if ( ! file_exists( $path ) ) return array( 'success' => false, 'message' => 'File not found' );
		$content = file_get_contents( $path );
		if ( isset( $input['lines'] ) ) {
			$all_lines = explode( "\n", $content );
			$content   = implode( "\n", array_slice( $all_lines, 0, (int) $input['lines'] ) );
		}
		return array( 'success' => true, 'path' => $path, 'size' => filesize( $path ), 'content' => $content );
	}

	public static function write_file( $input ) {
		$path = ABSPATH . sanitize_text_field( $input['path'] );
		$real = realpath( dirname( $path ) );
		if ( ! $real || strpos( $real, realpath( ABSPATH ) ) !== 0 ) {
			return array( 'success' => false, 'message' => 'Invalid or disallowed path' );
		}
		$flag   = ! empty( $input['append'] ) ? FILE_APPEND : 0;
		$result = file_put_contents( $path, $input['content'], $flag );
		if ( $result === false ) return array( 'success' => false, 'message' => 'Could not write file — check permissions' );
		return array( 'success' => true, 'path' => $path, 'bytes_written' => $result, 'message' => 'File written successfully' );
	}

	public static function list_files( $input ) {
		$base = realpath( ABSPATH . sanitize_text_field( $input['path'] ) );
		if ( ! $base || strpos( $base, realpath( ABSPATH ) ) !== 0 ) {
			return array( 'success' => false, 'message' => 'Invalid or disallowed path' );
		}
		if ( ! is_dir( $base ) ) return array( 'success' => false, 'message' => 'Not a directory' );

		$ext_filter = ! empty( $input['extension'] ) ? strtolower( ltrim( $input['extension'], '.' ) ) : null;
		$recursive  = ! empty( $input['recursive'] );

		$files = array();
		$iter  = $recursive
			? new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) )
			: new DirectoryIterator( $base );

		foreach ( $iter as $file ) {
			if ( $file->isDot() ) continue;
			if ( $ext_filter && strtolower( $file->getExtension() ) !== $ext_filter ) continue;
			$files[] = array(
				'name'     => $file->getFilename(),
				'path'     => str_replace( ABSPATH, '', $file->getPathname() ),
				'type'     => $file->isDir() ? 'dir' : 'file',
				'size'     => $file->isFile() ? size_format( $file->getSize() ) : null,
				'modified' => date( 'Y-m-d H:i:s', $file->getMTime() ),
			);
		}
		return array( 'success' => true, 'path' => $base, 'count' => count( $files ), 'files' => $files );
	}

	public static function run_wp_cli( $input ) {
		$wp_cli = shell_exec( 'which wp 2>/dev/null' ) ?: shell_exec( 'where wp 2>NUL' );
		if ( ! $wp_cli ) return array( 'success' => false, 'message' => 'WP-CLI not found on this server' );
		$safe_cmd = escapeshellcmd( trim( $input['command'] ) );
		$output   = shell_exec( 'wp ' . $safe_cmd . ' --path=' . escapeshellarg( ABSPATH ) . ' 2>&1' );
		return array( 'success' => true, 'command' => 'wp ' . $input['command'], 'output' => $output );
	}

	public static function manage_transients( $input ) {
		$action = $input['action'];
		$key    = isset( $input['key'] ) ? sanitize_key( $input['key'] ) : null;

		switch ( $action ) {
			case 'get':
				$value = get_transient( $key );
				return array( 'success' => true, 'key' => $key, 'value' => $value, 'exists' => $value !== false );
			case 'set':
				set_transient( $key, $input['value'], (int) ( $input['expiration'] ?? 0 ) );
				return array( 'success' => true, 'message' => 'Transient set: ' . $key );
			case 'delete':
				delete_transient( $key );
				return array( 'success' => true, 'message' => 'Transient deleted: ' . $key );
			case 'delete_all':
				global $wpdb;
				$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
				return array( 'success' => true, 'message' => 'All transients deleted' );
			default:
				return array( 'success' => false, 'message' => 'Unknown action' );
		}
	}

	public static function manage_redirects( $input ) {
		$action    = $input['action'];
		$redirects = get_option( 'wmc_redirects', array() );

		switch ( $action ) {
			case 'list':
				return array( 'success' => true, 'count' => count( $redirects ), 'redirects' => $redirects );
			case 'add':
				$from = '/' . ltrim( sanitize_text_field( $input['from'] ), '/' );
				$redirects[ $from ] = array(
					'to'   => esc_url_raw( $input['to'] ),
					'type' => (int) ( $input['type'] ?? 301 ),
					'added'=> current_time( 'Y-m-d H:i:s' ),
				);
				update_option( 'wmc_redirects', $redirects );
				return array( 'success' => true, 'message' => "Redirect added: {$from} → " . $input['to'] );
			case 'delete':
				$from = '/' . ltrim( sanitize_text_field( $input['from'] ), '/' );
				unset( $redirects[ $from ] );
				update_option( 'wmc_redirects', $redirects );
				return array( 'success' => true, 'message' => 'Redirect deleted' );
			default:
				return array( 'success' => false, 'message' => 'Unknown action' );
		}
	}

	public static function get_post_meta_all( $input ) {
		$id = (int) $input['post_id'];
		if ( ! get_post( $id ) ) return array( 'success' => false, 'message' => 'Post not found' );
		if ( ! empty( $input['meta_key'] ) ) {
			$val = get_post_meta( $id, sanitize_key( $input['meta_key'] ), true );
			return array( 'success' => true, 'post_id' => $id, 'meta_key' => $input['meta_key'], 'value' => $val );
		}
		$raw = get_post_meta( $id );
		$out = array();
		foreach ( $raw as $key => $vals ) {
			$out[ $key ] = count( $vals ) === 1 ? maybe_unserialize( $vals[0] ) : array_map( 'maybe_unserialize', $vals );
		}
		return array( 'success' => true, 'post_id' => $id, 'meta' => $out );
	}

	public static function update_post_meta_val( $input ) {
		$id  = (int) $input['post_id'];
		$key = sanitize_key( $input['meta_key'] );
		if ( ! empty( $input['delete'] ) ) {
			delete_post_meta( $id, $key );
			return array( 'success' => true, 'message' => "Meta '{$key}' deleted from post {$id}" );
		}
		update_post_meta( $id, $key, $input['meta_value'] );
		return array( 'success' => true, 'post_id' => $id, 'meta_key' => $key, 'message' => 'Meta updated' );
	}

	// ================================================================
	//  EXECUTE CALLBACKS — TIER 4
	// ================================================================

	public static function bulk_update_seo( $input ) {
		$plugin = $input['plugin'] ?? 'auto';
		if ( $plugin === 'auto' ) {
			if ( defined( 'RANK_MATH_VERSION' ) )      $plugin = 'rankmath';
			elseif ( defined( 'WPSEO_VERSION' ) )      $plugin = 'yoast';
			elseif ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) $plugin = 'aioseo';
			else $plugin = 'yoast';
		}

		$map = array(
			'yoast'    => array( 'title' => '_yoast_wpseo_title',    'desc' => '_yoast_wpseo_metadesc' ),
			'rankmath' => array( 'title' => 'rank_math_title',       'desc' => 'rank_math_description' ),
			'aioseo'   => array( 'title' => '_aioseo_title',         'desc' => '_aioseo_description' ),
		);
		$keys = $map[ $plugin ] ?? $map['yoast'];

		$results = array();
		foreach ( $input['items'] as $item ) {
			$id = (int) $item['post_id'];
			if ( ! get_post( $id ) ) { $results[] = array( 'post_id' => $id, 'success' => false, 'message' => 'Post not found' ); continue; }
			if ( isset( $item['seo_title'] ) )       update_post_meta( $id, $keys['title'], sanitize_text_field( $item['seo_title'] ) );
			if ( isset( $item['seo_description'] ) ) update_post_meta( $id, $keys['desc'],  sanitize_text_field( $item['seo_description'] ) );
			$results[] = array( 'post_id' => $id, 'success' => true );
		}
		$done = count( array_filter( $results, fn($r) => $r['success'] ) );
		return array( 'success' => true, 'plugin' => $plugin, 'updated' => $done, 'total' => count( $results ), 'results' => $results );
	}

	public static function get_broken_links( $input ) {
		$post_type = sanitize_key( $input['post_type'] ?? 'post' );
		$posts     = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => (int) ( $input['limit'] ?? 50 ), 'post_status' => 'publish' ) );
		$broken    = array();

		foreach ( $posts as $post ) {
			preg_match_all( '/href=["\']([^"\']+)["\']/i', $post->post_content, $matches );
			foreach ( $matches[1] as $url ) {
				if ( strpos( $url, get_site_url() ) === 0 ) {
					$path = parse_url( $url, PHP_URL_PATH );
					if ( $path ) {
						$q = new WP_Query( array( 'pagename' => trim( $path, '/' ), 'posts_per_page' => 1 ) );
						if ( ! $q->have_posts() && ! get_page_by_path( trim( $path, '/' ) ) ) {
							$broken[] = array( 'post_id' => $post->ID, 'post_title' => $post->post_title, 'broken_url' => $url );
						}
					}
				}
			}
		}
		return array( 'success' => true, 'count' => count( $broken ), 'broken_links' => $broken );
	}

	public static function get_404_logs( $input ) {
		$logs  = get_option( 'wmc_404_log', array() );
		$limit = (int) ( $input['limit'] ?? 50 );
		$logs  = array_slice( array_reverse( $logs ), 0, $limit );
		return array( 'success' => true, 'count' => count( $logs ), 'logs' => $logs );
	}

	public static function manage_robots_txt( $input ) {
		$path = ABSPATH . 'robots.txt';
		if ( $input['action'] === 'read' ) {
			if ( ! file_exists( $path ) ) return array( 'success' => true, 'exists' => false, 'content' => '', 'note' => 'No physical robots.txt — WordPress generates one dynamically' );
			return array( 'success' => true, 'exists' => true, 'content' => file_get_contents( $path ) );
		}
		if ( $input['action'] === 'write' ) {
			$result = file_put_contents( $path, $input['content'] ?? '' );
			if ( $result === false ) return array( 'success' => false, 'message' => 'Could not write robots.txt — check permissions' );
			return array( 'success' => true, 'bytes_written' => $result, 'message' => 'robots.txt updated' );
		}
		return array( 'success' => false, 'message' => 'Unknown action' );
	}

	public static function manage_htaccess( $input ) {
		$path = ABSPATH . '.htaccess';
		if ( $input['action'] === 'read' ) {
			if ( ! file_exists( $path ) ) return array( 'success' => false, 'message' => '.htaccess not found' );
			return array( 'success' => true, 'content' => file_get_contents( $path ) );
		}
		$content = $input['content'] ?? '';
		$flag    = $input['action'] === 'append' ? FILE_APPEND : 0;
		$result  = file_put_contents( $path, $content, $flag );
		if ( $result === false ) return array( 'success' => false, 'message' => 'Could not write .htaccess' );
		return array( 'success' => true, 'bytes_written' => $result, 'message' => '.htaccess updated' );
	}

	public static function compress_images( $input ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$ids = ! empty( $input['media_ids'] ) ? array_map( 'intval', $input['media_ids'] ) : array();
		if ( empty( $ids ) ) {
			$ids = get_posts( array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => (int) ( $input['limit'] ?? 10 ),
				'fields'         => 'ids',
			) );
		}
		$results = array();
		foreach ( array_slice( $ids, 0, (int) ( $input['limit'] ?? 10 ) ) as $id ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) { $results[] = array( 'id' => $id, 'success' => false, 'message' => 'File not found' ); continue; }
			$meta = wp_generate_attachment_metadata( $id, $file );
			wp_update_attachment_metadata( $id, $meta );
			$results[] = array( 'id' => $id, 'file' => basename( $file ), 'success' => true, 'message' => 'Thumbnails regenerated' );
		}
		return array( 'success' => true, 'processed' => count( $results ), 'results' => $results );
	}

	// ================================================================
	//  EXECUTE CALLBACKS — TIER 5
	// ================================================================

	public static function get_login_attempts( $input ) {
		$limit    = (int) ( $input['limit'] ?? 50 );
		$attempts = get_option( 'wmc_failed_logins', array() );
		$attempts = array_slice( array_reverse( $attempts ), 0, $limit );
		return array( 'success' => true, 'count' => count( $attempts ), 'attempts' => $attempts );
	}

	public static function block_ip( $input ) {
		$blocked = get_option( 'wmc_blocked_ips', array() );
		switch ( $input['action'] ) {
			case 'list':
				return array( 'success' => true, 'count' => count( $blocked ), 'blocked_ips' => $blocked );
			case 'block':
				$ip = sanitize_text_field( $input['ip'] );
				if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) return array( 'success' => false, 'message' => 'Invalid IP address' );
				$blocked[ $ip ] = array( 'reason' => sanitize_text_field( $input['reason'] ?? '' ), 'date' => current_time( 'Y-m-d H:i:s' ) );
				update_option( 'wmc_blocked_ips', $blocked );
				return array( 'success' => true, 'message' => "IP blocked: {$ip}" );
			case 'unblock':
				$ip = sanitize_text_field( $input['ip'] );
				unset( $blocked[ $ip ] );
				update_option( 'wmc_blocked_ips', $blocked );
				return array( 'success' => true, 'message' => "IP unblocked: {$ip}" );
			default:
				return array( 'success' => false, 'message' => 'Unknown action' );
		}
	}

	public static function create_backup( $input ) {
		global $wpdb;
		$backup_dir = WP_CONTENT_DIR . '/uploads/wmc-backups/';
		wp_mkdir_p( $backup_dir );

		$type     = $input['type'] ?? 'database';
		$filename = sanitize_file_name( $input['filename'] ?? ( 'backup-' . current_time( 'Y-m-d-His' ) ) );

		if ( $type === 'database' ) {
			$file = $backup_dir . $filename . '.sql';
			$tables = $wpdb->get_col( 'SHOW TABLES' );
			$sql    = "-- WordPress MCP Backup\n-- Date: " . current_time( 'Y-m-d H:i:s' ) . "\n-- Site: " . get_site_url() . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

			foreach ( $tables as $table ) {
				$create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
				$sql   .= "\nDROP TABLE IF EXISTS `{$table}`;\n" . $create[1] . ";\n\n";
				$rows   = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
				foreach ( $rows as $row ) {
					$values = array_map( fn($v) => $v === null ? 'NULL' : "'" . esc_sql( $v ) . "'", $row );
					$sql   .= "INSERT INTO `{$table}` VALUES (" . implode( ', ', $values ) . ");\n";
				}
				$sql .= "\n";
			}
			$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
			file_put_contents( $file, $sql );
			return array( 'success' => true, 'file' => $file, 'size' => size_format( filesize( $file ) ), 'tables' => count( $tables ), 'message' => 'Database backup created' );
		}

		if ( $type === 'theme' ) {
			$theme_dir = get_template_directory();
			$zip_file  = $backup_dir . $filename . '.zip';
			if ( ! class_exists( 'ZipArchive' ) ) return array( 'success' => false, 'message' => 'ZipArchive extension not available' );
			$zip  = new ZipArchive();
			$zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );
			$iter = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $theme_dir ) );
			foreach ( $iter as $file_item ) {
				if ( ! $file_item->isDir() ) {
					$zip->addFile( $file_item->getPathname(), str_replace( $theme_dir . '/', '', $file_item->getPathname() ) );
				}
			}
			$zip->close();
			return array( 'success' => true, 'file' => $zip_file, 'size' => size_format( filesize( $zip_file ) ), 'message' => 'Theme backup created' );
		}

		return array( 'success' => false, 'message' => 'Unsupported backup type' );
	}

	public static function list_backups( $input ) {
		$backup_dir = WP_CONTENT_DIR . '/uploads/wmc-backups/';
		if ( ! is_dir( $backup_dir ) ) return array( 'success' => true, 'count' => 0, 'backups' => array() );
		$files   = glob( $backup_dir . '*' );
		$backups = array();
		foreach ( $files as $file ) {
			$backups[] = array(
				'filename' => basename( $file ),
				'size'     => size_format( filesize( $file ) ),
				'date'     => date( 'Y-m-d H:i:s', filemtime( $file ) ),
				'url'      => content_url( 'uploads/wmc-backups/' . basename( $file ) ),
			);
		}
		usort( $backups, fn($a, $b) => strcmp( $b['date'], $a['date'] ) );
		return array( 'success' => true, 'count' => count( $backups ), 'backups' => $backups );
	}

	public static function manage_user_sessions( $input ) {
		$user_id = (int) ( $input['user_id'] ?? 0 ) ?: get_current_user_id();
		$manager = WP_Session_Tokens::get_instance( $user_id );
		$action  = $input['action'];

		switch ( $action ) {
			case 'list':
				$sessions = $manager->get_all();
				$result   = array();
				foreach ( $sessions as $token => $session ) {
					$result[] = array(
						'token'      => substr( $token, 0, 8 ) . '...',
						'ip'         => $session['ip'] ?? 'unknown',
						'ua'         => $session['ua'] ?? 'unknown',
						'login'      => isset( $session['login'] ) ? date( 'Y-m-d H:i:s', $session['login'] ) : null,
						'expiration' => isset( $session['expiration'] ) ? date( 'Y-m-d H:i:s', $session['expiration'] ) : null,
					);
				}
				return array( 'success' => true, 'user_id' => $user_id, 'active_sessions' => count( $result ), 'sessions' => $result );
			case 'destroy_all':
				$manager->destroy_all();
				return array( 'success' => true, 'message' => "All sessions destroyed for user {$user_id}" );
			case 'destroy_others':
				$manager->destroy_others( wp_get_session_token() );
				return array( 'success' => true, 'message' => "All other sessions destroyed for user {$user_id}" );
			default:
				return array( 'success' => false, 'message' => 'Unknown action' );
		}
	}

	public static function reset_user_password( $input ) {
		$user_id = (int) $input['user_id'];
		$user    = get_userdata( $user_id );
		if ( ! $user ) return array( 'success' => false, 'message' => 'User not found' );

		$new_pass = ! empty( $input['new_password'] ) ? $input['new_password'] : wp_generate_password( 16, true, true );
		if ( strlen( $new_pass ) < 8 ) return array( 'success' => false, 'message' => 'Password must be at least 8 characters' );

		wp_set_password( $new_pass, $user_id );

		if ( ! empty( $input['notify_user'] ) ) {
			wp_new_user_notification( $user_id, null, 'user' );
		}
		$auto_generated = empty( $input['new_password'] );
		return array(
			'success'       => true,
			'user_id'       => $user_id,
			'username'      => $user->user_login,
			'auto_generated'=> $auto_generated,
			'new_password'  => $auto_generated ? $new_pass : '(as provided)',
			'message'       => 'Password reset successfully',
		);
	}

	// ================================================================
	//  PERMALINK & SLUG ABILITIES
	// ================================================================

	public static function register_permalink_abilities() {

		// ---------- get-permalink-structure ----------
		self::register( 'wmc/get-permalink-structure', array(
			'label'               => 'Get Permalink Structure',
			'description'         => 'Get the current site-wide permalink structure setting',
			'category'            => 'wp-content-manager',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_permalink_structure' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- set-permalink-structure ----------
		self::register( 'wmc/set-permalink-structure', array(
			'label'               => 'Set Permalink Structure',
			'description'         => 'Change the site-wide permalink structure. Flushes rewrite rules automatically.',
			'category'            => 'wp-content-manager',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'structure' => array(
						'type'        => 'string',
						'description' => 'Permalink structure tag or a common preset name: "plain", "day", "month", "numeric", "postname", "custom". For custom pass a WP structure tag like "/%category%/%postname%/".',
					),
				),
				'required' => array( 'structure' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_permalink_structure' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- flush-rewrite-rules ----------
		self::register( 'wmc/flush-rewrite-rules', array(
			'label'               => 'Flush Rewrite Rules',
			'description'         => 'Flush WordPress rewrite rules (same as saving Permalinks settings page)',
			'category'            => 'wp-content-manager',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'flush_rewrite_rules_ability' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- get-post-slug ----------
		self::register( 'wmc/get-post-slug', array(
			'label'               => 'Get Post/Page Slug',
			'description'         => 'Get the current slug (URL name) of a post or page',
			'category'            => 'wp-content-manager',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_post_slug' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- set-post-slug ----------
		self::register( 'wmc/set-post-slug', array(
			'label'               => 'Set Post/Page Slug',
			'description'         => 'Change the slug (URL name) of a post or page',
			'category'            => 'wp-content-manager',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'slug'    => array( 'type' => 'string', 'description' => 'New slug (URL-safe, lowercase, hyphens allowed). Leave empty to auto-generate from post title.' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_post_slug' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- check-slug-availability ----------
		self::register( 'wmc/check-slug-availability', array(
			'label'               => 'Check Slug Availability',
			'description'         => 'Check if a slug is already in use on this site',
			'category'            => 'wp-content-manager',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'slug'      => array( 'type' => 'string', 'description' => 'Slug to check' ),
					'post_type' => array( 'type' => 'string', 'description' => 'Post type to check against (default: post)', 'default' => 'post' ),
					'exclude_id'=> array( 'type' => 'integer', 'description' => 'Exclude this post ID from the check (useful when editing existing post)' ),
				),
				'required' => array( 'slug' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'check_slug_availability' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- get-category-slug ----------
		self::register( 'wmc/get-category-slug', array(
			'label'               => 'Get Category/Tag Slug',
			'description'         => 'Get the slug of a category, tag, or custom taxonomy term',
			'category'            => 'wp-content-manager',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'term_id'  => array( 'type' => 'integer', 'description' => 'Term ID' ),
					'taxonomy' => array( 'type' => 'string', 'description' => 'Taxonomy name (default: category)', 'default' => 'category' ),
				),
				'required' => array( 'term_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_term_slug' ),
			'permission_callback' => fn() => current_user_can( 'manage_categories' ),
		) );

		// ---------- set-category-slug ----------
		self::register( 'wmc/set-category-slug', array(
			'label'               => 'Set Category/Tag Slug',
			'description'         => 'Change the slug of a category, tag, or custom taxonomy term',
			'category'            => 'wp-content-manager',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'term_id'  => array( 'type' => 'integer', 'description' => 'Term ID' ),
					'slug'     => array( 'type' => 'string', 'description' => 'New slug' ),
					'taxonomy' => array( 'type' => 'string', 'description' => 'Taxonomy name (default: category)', 'default' => 'category' ),
				),
				'required' => array( 'term_id', 'slug' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_term_slug' ),
			'permission_callback' => fn() => current_user_can( 'manage_categories' ),
		) );
	}

	// ----------------------------------------------------------------
	//  PERMALINK CALLBACKS
	// ----------------------------------------------------------------

	private static function permalink_disabled( $label, $op ) {
		$url = admin_url( 'admin.php?page=wmc-settings' );
		return array(
			'success'      => false,
			'disabled'     => true,
			'ability'      => $label,
			'message'      => "'{$label}' is currently DISABLED on your WordPress site.",
			'how_to_enable'=> "Go to: WordPress Admin → MCP Connector → Permalinks & Slugs → Enable '{$label}' → Save Changes",
			'settings_url' => $url,
			'config_key'   => "permalinks → {$op}",
		);
	}

	private static function permalink_is_enabled( $op ) {
		if ( class_exists( 'WMC_Settings' ) ) {
			return WMC_Settings::is_ability_enabled( 'permalinks', $op );
		}
		return true;
	}

	public static function get_permalink_structure( $params = array() ) {
		if ( ! self::permalink_is_enabled( 'read' ) ) return self::permalink_disabled( 'Get Permalink Structure', 'read' );
		$structure = get_option( 'permalink_structure' );
		$presets = array(
			''                    => 'plain',
			'/%year%/%monthnum%/%day%/%postname%/' => 'day',
			'/%year%/%monthnum%/%postname%/'       => 'month',
			'/archives/%post_id%'                  => 'numeric',
			'/%postname%/'                         => 'postname',
		);
		$preset = isset( $presets[ $structure ] ) ? $presets[ $structure ] : 'custom';

		return array(
			'success'           => true,
			'structure'         => $structure,
			'preset'            => $preset,
			'category_base'     => get_option( 'category_base' ),
			'tag_base'          => get_option( 'tag_base' ),
			'sample_post_url'   => get_permalink( get_option( 'page_on_front' ) ?: 1 ) ?: get_home_url(),
		);
	}

	public static function set_permalink_structure( $params ) {
		if ( ! self::permalink_is_enabled( 'write' ) ) return self::permalink_disabled( 'Set Permalink Structure', 'write' );
		$input = $params['structure'] ?? '';

		$presets = array(
			'plain'    => '',
			'day'      => '/%year%/%monthnum%/%day%/%postname%/',
			'month'    => '/%year%/%monthnum%/%postname%/',
			'numeric'  => '/archives/%post_id%',
			'postname' => '/%postname%/',
		);

		if ( isset( $presets[ $input ] ) ) {
			$structure = $presets[ $input ];
		} else {
			$structure = $input;
		}

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules();

		return array(
			'success'   => true,
			'structure' => $structure,
			'message'   => 'Permalink structure updated and rewrite rules flushed.',
		);
	}

	public static function flush_rewrite_rules_ability( $params = array() ) {
		if ( ! self::permalink_is_enabled( 'write' ) ) return self::permalink_disabled( 'Flush Rewrite Rules', 'write' );
		flush_rewrite_rules( true );
		return array(
			'success' => true,
			'message' => 'Rewrite rules flushed successfully.',
		);
	}

	public static function get_post_slug( $params ) {
		if ( ! self::permalink_is_enabled( 'read' ) ) return self::permalink_disabled( 'Get Post/Page Slug', 'read' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array( 'success' => false, 'message' => 'Post not found.' );
		}
		return array(
			'success'   => true,
			'post_id'   => $post_id,
			'slug'      => $post->post_name,
			'permalink' => get_permalink( $post_id ),
			'title'     => $post->post_title,
			'post_type' => $post->post_type,
			'status'    => $post->post_status,
		);
	}

	public static function set_post_slug( $params ) {
		if ( ! self::permalink_is_enabled( 'write' ) ) return self::permalink_disabled( 'Set Post/Page Slug', 'write' );
		$post_id  = intval( $params['post_id'] ?? 0 );
		$new_slug = sanitize_title( $params['slug'] ?? '' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array( 'success' => false, 'message' => 'Post not found.' );
		}

		if ( empty( $new_slug ) ) {
			$new_slug = sanitize_title( $post->post_title );
		}

		$old_slug = $post->post_name;

		$result = wp_update_post( array(
			'ID'        => $post_id,
			'post_name' => $new_slug,
		), true );

		if ( is_wp_error( $result ) ) {
			return array( 'success' => false, 'message' => $result->get_error_message() );
		}

		$final_slug = get_post( $post_id )->post_name;

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'old_slug'   => $old_slug,
			'new_slug'   => $final_slug,
			'permalink'  => get_permalink( $post_id ),
			'message'    => 'Slug updated successfully.',
		);
	}

	public static function check_slug_availability( $params ) {
		if ( ! self::permalink_is_enabled( 'read' ) ) return self::permalink_disabled( 'Check Slug Availability', 'read' );
		$slug       = sanitize_title( $params['slug'] ?? '' );
		$post_type  = sanitize_key( $params['post_type'] ?? 'post' );
		$exclude_id = intval( $params['exclude_id'] ?? 0 );

		if ( empty( $slug ) ) {
			return array( 'success' => false, 'message' => 'Slug cannot be empty.' );
		}

		$args = array(
			'name'           => $slug,
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		if ( $exclude_id ) {
			$args['post__not_in'] = array( $exclude_id );
		}

		$posts = get_posts( $args );
		$available = empty( $posts );

		return array(
			'success'     => true,
			'slug'        => $slug,
			'available'   => $available,
			'post_type'   => $post_type,
			'message'     => $available ? 'Slug is available.' : 'Slug is already in use.',
			'used_by_id'  => ! $available ? $posts[0] : null,
		);
	}

	public static function get_term_slug( $params ) {
		if ( ! self::permalink_is_enabled( 'read' ) ) return self::permalink_disabled( 'Get Category/Tag Slug', 'read' );
		$term_id  = intval( $params['term_id'] ?? 0 );
		$taxonomy = sanitize_key( $params['taxonomy'] ?? 'category' );

		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) || ! $term ) {
			return array( 'success' => false, 'message' => 'Term not found.' );
		}

		return array(
			'success'  => true,
			'term_id'  => $term_id,
			'taxonomy' => $taxonomy,
			'name'     => $term->name,
			'slug'     => $term->slug,
			'url'      => get_term_link( $term ),
		);
	}

	public static function set_term_slug( $params ) {
		if ( ! self::permalink_is_enabled( 'write' ) ) return self::permalink_disabled( 'Set Category/Tag Slug', 'write' );
		$term_id  = intval( $params['term_id'] ?? 0 );
		$new_slug = sanitize_title( $params['slug'] ?? '' );
		$taxonomy = sanitize_key( $params['taxonomy'] ?? 'category' );

		if ( empty( $new_slug ) ) {
			return array( 'success' => false, 'message' => 'Slug cannot be empty.' );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) || ! $term ) {
			return array( 'success' => false, 'message' => 'Term not found.' );
		}

		$old_slug = $term->slug;

		$result = wp_update_term( $term_id, $taxonomy, array( 'slug' => $new_slug ) );

		if ( is_wp_error( $result ) ) {
			return array( 'success' => false, 'message' => $result->get_error_message() );
		}

		$updated = get_term( $term_id, $taxonomy );

		return array(
			'success'  => true,
			'term_id'  => $term_id,
			'taxonomy' => $taxonomy,
			'old_slug' => $old_slug,
			'new_slug' => $updated->slug,
			'url'      => get_term_link( $updated ),
			'message'  => 'Term slug updated successfully.',
		);
	}
}

// ================================================================
//  REDIRECT ENFORCER — runs on init if wmc_redirects option exists
// ================================================================
add_action( 'template_redirect', function() {
	$redirects = get_option( 'wmc_redirects', array() );
	if ( empty( $redirects ) ) return;
	$current = '/' . ltrim( $_SERVER['REQUEST_URI'] ?? '', '/' );
	$path    = strtok( $current, '?' );
	if ( isset( $redirects[ $path ] ) ) {
		$r = $redirects[ $path ];
		wp_redirect( $r['to'], $r['type'] ?? 301 );
		exit;
	}
}, 1 );

// ================================================================
//  FAILED LOGIN TRACKER — logs to wp_options for get-login-attempts
// ================================================================
add_action( 'wp_login_failed', function( $username ) {
	$attempts   = get_option( 'wmc_failed_logins', array() );
	$attempts[] = array(
		'username' => $username,
		'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
		'time'     => current_time( 'Y-m-d H:i:s' ),
		'ua'       => $_SERVER['HTTP_USER_AGENT'] ?? '',
	);
	$attempts = array_slice( $attempts, -500 );
	update_option( 'wmc_failed_logins', $attempts, false );
} );

// ================================================================
//  404 TRACKER — logs to wp_options for get-404-logs
// ================================================================
add_action( 'wp', function() {
	if ( ! is_404() ) return;
	$logs   = get_option( 'wmc_404_log', array() );
	$logs[] = array(
		'url'  => $_SERVER['REQUEST_URI'] ?? '',
		'ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
		'ref'  => $_SERVER['HTTP_REFERER'] ?? '',
		'time' => current_time( 'Y-m-d H:i:s' ),
	);
	$logs = array_slice( $logs, -1000 );
	update_option( 'wmc_404_log', $logs, false );
} );

// ================================================================
//  IP BLOCKER — blocks IPs on init
// ================================================================
add_action( 'init', function() {
	$blocked = get_option( 'wmc_blocked_ips', array() );
	if ( empty( $blocked ) ) return;
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	if ( isset( $blocked[ $ip ] ) ) {
		wp_die( 'Access denied. Your IP has been blocked.', 'Forbidden', array( 'response' => 403 ) );
	}
}, 1 );

