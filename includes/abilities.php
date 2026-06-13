<?php
/**
 * WordPress MCP Connector - Abilities Registration
 *
 * This file registers all the WordPress Abilities for MCP integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WMC_Abilities {

	/**
	 * Check if ability is enabled
	 */
	public static function is_enabled( $resource, $operation ) {
		// Import settings class
		require_once WMC_PLUGIN_DIR . 'includes/settings.php';
		return WMC_Settings::is_ability_enabled( $resource, $operation );
	}

	/**
	 * Get disabled error response
	 */
	public static function get_disabled_error( $operation ) {
		return array(
			'success'  => false,
			'message'  => ucfirst( $operation ) . ' operation is currently disabled',
			'status'   => 'disabled',
			'help'     => 'Please contact administrator to enable this feature. Go to WordPress Admin → MCP Connector to manage permissions.',
			'disabled' => true,
		);
	}

	/**
	 * Wrapper around wp_register_ability() that defaults meta.show_in_rest=true
	 * so the ability is reachable via /wp-json/wp-abilities/v1/abilities/... .
	 * Pass meta.show_in_rest=false explicitly to opt out.
	 */
	public static function wmc_register( $name, $args ) {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return null;
		}
		if ( ! isset( $args['meta'] ) || ! is_array( $args['meta'] ) ) {
			$args['meta'] = array();
		}
		if ( ! array_key_exists( 'show_in_rest', $args['meta'] ) ) {
			$args['meta']['show_in_rest'] = true;
		}
		if ( ! isset( $args['meta']['mcp'] ) || ! is_array( $args['meta']['mcp'] ) ) {
			$args['meta']['mcp'] = array();
		}
		if ( ! array_key_exists( 'public', $args['meta']['mcp'] ) ) {
			$args['meta']['mcp']['public'] = true;
		}
		return wp_register_ability( $name, $args );
	}

	/**
	 * Resolve a scheduled-publish date for a post/page.
	 *
	 * Accepts "YYYY-MM-DD HH:MM:SS" (WP local format) or ISO 8601 (e.g. "2026-06-01T10:00:00",
	 * "2026-06-01T10:00:00Z", "2026-06-01T10:00:00+05:00"). The site's timezone is used when
	 * the input has no timezone designator.
	 *
	 * Returns:
	 *   - null if no date was supplied (caller continues as usual)
	 *   - WP_Error on invalid input
	 *   - array{ post_status, post_date, post_date_gmt } when a date is set. If the date is
	 *     in the future and the caller asked for "publish", the status is auto-promoted to
	 *     "future"; otherwise the caller's status is preserved.
	 */
	public static function resolve_schedule( $raw_date, $status ) {
		if ( empty( $raw_date ) ) {
			return null;
		}

		$site_tz = wp_timezone();
		try {
			// If the input has no timezone marker, interpret it as site-local time.
			if ( preg_match( '/(Z|[+\-]\d{2}:?\d{2})$/', $raw_date ) ) {
				$dt = new DateTime( $raw_date );
			} else {
				$dt = new DateTime( $raw_date, $site_tz );
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'wmc_invalid_date',
				'Invalid "date" value. Use "YYYY-MM-DD HH:MM:SS" or ISO 8601.'
			);
		}

		$local = clone $dt;
		$local->setTimezone( $site_tz );
		$gmt = clone $dt;
		$gmt->setTimezone( new DateTimeZone( 'UTC' ) );

		$is_future    = $dt->getTimestamp() > time();
		$final_status = $status;
		if ( $is_future && ( $status === 'publish' || $status === 'future' ) ) {
			$final_status = 'future';
		}

		return array(
			'post_status'   => $final_status,
			'post_date'     => $local->format( 'Y-m-d H:i:s' ),
			'post_date_gmt' => $gmt->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Register all abilities.
	 *
	 * Note: the ability CATEGORY ('wp-content-manager') is registered separately
	 * in the main plugin file on `wp_abilities_api_categories_init`, which fires
	 * before `wp_abilities_api_init`. Do NOT call self::register_category() from
	 * here — calling `wp_register_ability_category()` outside its required hook
	 * triggers `_doing_it_wrong` and returns null.
	 */
	public static function register_all_abilities() {
		// Register all abilities
		self::register_post_abilities();
		self::register_page_abilities();
		self::register_category_abilities();
		self::register_tag_abilities();
		self::register_media_abilities();
		self::register_comment_abilities();
		self::register_user_abilities();
		self::register_settings_abilities();
		self::register_menu_abilities();
		self::register_widget_abilities();
		self::register_theme_abilities();
		self::register_seo_abilities();
		self::register_plugin_abilities();
		self::register_roles_abilities();
		self::register_woocommerce_abilities();
		self::register_system_abilities();
	}

	/**
	 * Register ability category
	 */
	private static function register_category() {
		if ( function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category(
				'wp-content-manager',
				array(
					'label'       => 'WordPress Content Manager',
					'description' => 'Abilities for managing all WordPress content via MCP',
				)
			);
		}
	}

	/**
	 * ==================== POST ABILITIES ====================
	 */

	/**
	 * Register post abilities
	 */
	private static function register_post_abilities() {
		// Get Posts
		self::wmc_register(
			'wmc/get-posts',
			array(
				'label'       => 'Get Posts',
				'description' => 'Retrieve published blog posts with pagination, search, and filtering capabilities',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'        => 'integer',
							'description' => 'Number of posts per page (default: 50, max: 100)',
							'default'     => 50,
							'minimum'     => 1,
							'maximum'     => 100,
						),
						'page'     => array(
							'type'        => 'integer',
							'description' => 'Page number (default: 1)',
							'default'     => 1,
							'minimum'     => 1,
						),
						'status'   => array(
							'type'        => 'string',
							'description' => 'Filter by post status (publish, draft, pending, etc.)',
							'default'     => 'publish',
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'posts' => array(
							'type'        => 'array',
							'description' => 'Array of posts',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array( 'type' => 'integer' ),
									'title'    => array( 'type' => 'string' ),
									'content'  => array( 'type' => 'string' ),
									'excerpt'  => array( 'type' => 'string' ),
									'url'      => array( 'type' => 'string' ),
									'date'     => array( 'type' => 'string' ),
									'author'   => array( 'type' => 'string' ),
									'status'   => array( 'type' => 'string' ),
								),
							),
						),
						'total'  => array(
							'type'        => 'integer',
							'description' => 'Total number of posts',
						),
					),
				),
				'permission_callback' => fn() => true,
				'execute_callback'    => array( self::class, 'get_posts' ),
			)
		);

		// Create Post
		self::wmc_register(
			'wmc/create-post',
			array(
				'label'       => 'Create Post',
				'description' => 'Create a new blog post with title, content, status, categories, tags, and optional scheduled date',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'title'      => array(
							'type'        => 'string',
							'description' => 'Post title (required)',
						),
						'content'    => array(
							'type'        => 'string',
							'description' => 'Post content/body',
						),
						'excerpt'    => array(
							'type'        => 'string',
							'description' => 'Post excerpt',
						),
						'status'     => array(
							'type'        => 'string',
							'description' => 'Post status: publish, draft, pending, or future (scheduled). If a future-dated "date" is passed with status "publish", it is auto-converted to "future".',
							'default'     => 'draft',
							'enum'        => array( 'publish', 'draft', 'pending', 'future', 'private' ),
						),
						'date'       => array(
							'type'        => 'string',
							'description' => 'Scheduled publish date in site timezone. Accepts "YYYY-MM-DD HH:MM:SS" or ISO 8601 (e.g. "2026-06-01T10:00:00"). When set to a future date, the post is scheduled.',
						),
						'categories' => array(
							'type'        => 'array',
							'description' => 'Array of category IDs',
							'items'       => array( 'type' => 'integer' ),
						),
						'tags'       => array(
							'type'        => 'array',
							'description' => 'Array of tag names',
							'items'       => array( 'type' => 'string' ),
						),
					),
					'required'   => array( 'title' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'url'     => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'publish_posts' ),
				'execute_callback'    => array( self::class, 'create_post' ),
			)
		);

		// Update Post
		self::wmc_register(
			'wmc/update-post',
			array(
				'label'       => 'Update Post',
				'description' => 'Update an existing blog post, including rescheduling via the optional "date" field',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array(
							'type'        => 'integer',
							'description' => 'Post ID (required)',
						),
						'title'      => array(
							'type'        => 'string',
							'description' => 'New post title',
						),
						'content'    => array(
							'type'        => 'string',
							'description' => 'New post content',
						),
						'status'     => array(
							'type'        => 'string',
							'description' => 'New post status (publish, draft, pending, future, private)',
							'enum'        => array( 'publish', 'draft', 'pending', 'future', 'private' ),
						),
						'date'       => array(
							'type'        => 'string',
							'description' => 'New publish date. "YYYY-MM-DD HH:MM:SS" or ISO 8601. A future date with status "publish" is auto-promoted to "future" (scheduled).',
						),
						'categories' => array(
							'type'        => 'array',
							'description' => 'Array of category IDs',
							'items'       => array( 'type' => 'integer' ),
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'execute_callback'    => array( self::class, 'update_post' ),
			)
		);

		// Delete Post
		self::wmc_register(
			'wmc/delete-post',
			array(
				'label'       => 'Delete Post',
				'description' => 'Permanently delete a blog post (Admin/Editor only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Post ID to delete (required)',
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'delete_posts' ),
				'execute_callback'    => array( self::class, 'delete_post' ),
			)
		);
	}

	/**
	 * ==================== PAGE ABILITIES ====================
	 */

	/**
	 * Register page abilities
	 */
	private static function register_page_abilities() {
		// Get Pages
		self::wmc_register(
			'wmc/get-pages',
			array(
				'label'       => 'Get Pages',
				'description' => 'Retrieve all WordPress pages',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'        => 'integer',
							'description' => 'Number of pages per page',
							'default'     => 50,
							'minimum'     => 1,
							'maximum'     => 100,
						),
						'page'     => array(
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'pages' => array(
							'type'        => 'array',
							'description' => 'Array of pages',
						),
						'total'  => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => fn() => true,
				'execute_callback'    => array( self::class, 'get_pages' ),
			)
		);

		// Create Page
		self::wmc_register(
			'wmc/create-page',
			array(
				'label'       => 'Create Page',
				'description' => 'Create a new WordPress page, optionally scheduled via "date"',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'title'   => array(
							'type'        => 'string',
							'description' => 'Page title (required)',
						),
						'content' => array(
							'type'        => 'string',
							'description' => 'Page content',
						),
						'status'  => array(
							'type'        => 'string',
							'description' => 'Page status (publish, draft, pending, future, private)',
							'default'     => 'draft',
							'enum'        => array( 'publish', 'draft', 'pending', 'future', 'private' ),
						),
						'date'    => array(
							'type'        => 'string',
							'description' => 'Scheduled publish date. "YYYY-MM-DD HH:MM:SS" or ISO 8601. Future-dated "publish" becomes "future" (scheduled).',
						),
					),
					'required'   => array( 'title' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'url'     => array( 'type' => 'string' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'publish_pages' ),
				'execute_callback'    => array( self::class, 'create_page' ),
			)
		);

		// Update Page
		self::wmc_register(
			'wmc/update-page',
			array(
				'label'       => 'Update Page',
				'description' => 'Update an existing WordPress page, including rescheduling via "date"',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'status'  => array(
							'type' => 'string',
							'enum' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
						),
						'date'    => array(
							'type'        => 'string',
							'description' => 'New publish date. "YYYY-MM-DD HH:MM:SS" or ISO 8601. Future-dated "publish" becomes "future" (scheduled).',
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'edit_pages' ),
				'execute_callback'    => array( self::class, 'update_page' ),
			)
		);

		// Delete Page
		self::wmc_register(
			'wmc/delete-page',
			array(
				'label'       => 'Delete Page',
				'description' => 'Permanently delete a WordPress page (Admin/Editor only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Page ID to delete',
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'delete_pages' ),
				'execute_callback'    => array( self::class, 'delete_page' ),
			)
		);
	}

	/**
	 * ==================== CATEGORY ABILITIES ====================
	 */

	/**
	 * Register category abilities
	 */
	private static function register_category_abilities() {
		// Get Categories
		self::wmc_register(
			'wmc/get-categories',
			array(
				'label'       => 'Get Categories',
				'description' => 'Retrieve all post categories',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'        => 'integer',
							'default'     => 100,
							'maximum'     => 100,
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'categories' => array( 'type' => 'array' ),
						'total'      => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => fn() => true,
				'execute_callback'    => array( self::class, 'get_categories' ),
			)
		);

		// Create Category
		self::wmc_register(
			'wmc/create-category',
			array(
				'label'       => 'Create Category',
				'description' => 'Create a new post category',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'name' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'name'    => array( 'type' => 'string' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'manage_categories' ),
				'execute_callback'    => array( self::class, 'create_category' ),
			)
		);

		// Update Category
		self::wmc_register(
			'wmc/update-category',
			array(
				'label'       => 'Update Category',
				'description' => 'Update a post category',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'manage_categories' ),
				'execute_callback'    => array( self::class, 'update_category' ),
			)
		);

		// Delete Category
		self::wmc_register(
			'wmc/delete-category',
			array(
				'label'       => 'Delete Category',
				'description' => 'Delete a post category (Admin/Editor only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'delete_categories' ),
				'execute_callback'    => array( self::class, 'delete_category' ),
			)
		);
	}

	/**
	 * ==================== TAG ABILITIES ====================
	 */

	/**
	 * Register tag abilities
	 */
	private static function register_tag_abilities() {
		// Get Tags
		self::wmc_register(
			'wmc/get-tags',
			array(
				'label'       => 'Get Tags',
				'description' => 'Retrieve all post tags',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'    => 'integer',
							'default' => 100,
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'tags'  => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => fn() => true,
				'execute_callback'    => array( self::class, 'get_tags' ),
			)
		);

		// Create Tag
		self::wmc_register(
			'wmc/create-tag',
			array(
				'label'       => 'Create Tag',
				'description' => 'Create a new post tag',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'name' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'name'    => array( 'type' => 'string' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'manage_post_tags' ),
				'execute_callback'    => array( self::class, 'create_tag' ),
			)
		);

		// Update Tag
		self::wmc_register(
			'wmc/update-tag',
			array(
				'label'       => 'Update Tag',
				'description' => 'Update a post tag',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'manage_post_tags' ),
				'execute_callback'    => array( self::class, 'update_tag' ),
			)
		);

		// Delete Tag
		self::wmc_register(
			'wmc/delete-tag',
			array(
				'label'       => 'Delete Tag',
				'description' => 'Delete a post tag (Admin/Editor only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'delete_post_tags' ),
				'execute_callback'    => array( self::class, 'delete_tag' ),
			)
		);
	}

	/**
	 * ==================== MEDIA ABILITIES ====================
	 */

	/**
	 * Register media abilities
	 */
	private static function register_media_abilities() {
		// Get Media
		self::wmc_register(
			'wmc/get-media',
			array(
				'label'       => 'Get Media',
				'description' => 'Retrieve uploaded media files',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'    => 'integer',
							'default' => 50,
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'media' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'get_media' ),
			)
		);

		// Create Media (Upload)
		self::wmc_register(
			'wmc/create-media',
			array(
				'label'       => 'Create Media',
				'description' => 'Upload a new media file (image, document, etc.)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'title'       => array(
							'type'        => 'string',
							'description' => 'Media title',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Media description',
						),
						'alt_text'    => array(
							'type'        => 'string',
							'description' => 'Alt text for images',
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'url'     => array( 'type' => 'string' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'create_media' ),
			)
		);

		// Update Media
		self::wmc_register(
			'wmc/update-media',
			array(
				'label'       => 'Update Media',
				'description' => 'Update media file metadata',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'alt_text'    => array( 'type' => 'string' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'update_media' ),
			)
		);

		// Delete Media
		self::wmc_register(
			'wmc/delete-media',
			array(
				'label'       => 'Delete Media',
				'description' => 'Delete a media file (Admin/Editor only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Media attachment ID',
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'delete_posts' ),
				'execute_callback'    => array( self::class, 'delete_media' ),
			)
		);

		// Get Media Details
		self::wmc_register(
			'wmc/get-media-details',
			array(
				'label'       => 'Get Media Details',
				'description' => 'Get full metadata of a single media item (alt text, caption, description, dimensions, file size, attached post)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Media attachment ID',
						),
					),
					'required' => array( 'id' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'get_media_details' ),
			)
		);

		// Get Media Without Alt Text
		self::wmc_register(
			'wmc/get-media-without-alt',
			array(
				'label'       => 'Get Media Without Alt Text',
				'description' => 'Find all images missing alt text — useful for SEO audits and bulk fixing',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'        => 'integer',
							'description' => 'Results per page (default: 50, max: 200)',
							'default'     => 50,
							'minimum'     => 1,
							'maximum'     => 200,
						),
						'page' => array(
							'type'    => 'integer',
							'default' => 1,
						),
						'mime_type' => array(
							'type'        => 'string',
							'description' => 'Filter by MIME type prefix: image, image/jpeg, image/png, image/webp (default: image)',
							'default'     => 'image',
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'get_media_without_alt' ),
			)
		);

		// Search Media (Advanced)
		self::wmc_register(
			'wmc/search-media',
			array(
				'label'       => 'Search Media',
				'description' => 'Advanced media search and filter by title, alt text, description, MIME type, date range, or attached/unattached status',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'search'     => array(
							'type'        => 'string',
							'description' => 'Search in title, alt text, description, caption',
						),
						'mime_type'  => array(
							'type'        => 'string',
							'description' => 'Filter by MIME type: image, image/jpeg, image/png, image/webp, video, audio, application/pdf',
						),
						'attached'   => array(
							'type'        => 'string',
							'description' => 'Filter by attachment status: all, attached, unattached',
							'enum'        => array( 'all', 'attached', 'unattached' ),
							'default'     => 'all',
						),
						'date_after' => array(
							'type'        => 'string',
							'description' => 'Uploaded after this date (YYYY-MM-DD)',
						),
						'date_before' => array(
							'type'        => 'string',
							'description' => 'Uploaded before this date (YYYY-MM-DD)',
						),
						'has_alt'    => array(
							'type'        => 'string',
							'description' => 'Filter by alt text presence: all, yes, no',
							'enum'        => array( 'all', 'yes', 'no' ),
							'default'     => 'all',
						),
						'per_page'   => array( 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200 ),
						'page'       => array( 'type' => 'integer', 'default' => 1 ),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'search_media' ),
			)
		);

		// Bulk Update Media
		self::wmc_register(
			'wmc/bulk-update-media',
			array(
				'label'       => 'Bulk Update Media',
				'description' => 'Update alt text, title, description, and caption for multiple media items in one call',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'items' => array(
							'type'        => 'array',
							'description' => 'Array of media items to update',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'          => array(
										'type'        => 'integer',
										'description' => 'Media attachment ID (required)',
									),
									'alt_text'    => array(
										'type'        => 'string',
										'description' => 'Alt text for the image',
									),
									'title'       => array(
										'type'        => 'string',
										'description' => 'Media title',
									),
									'description' => array(
										'type'        => 'string',
										'description' => 'Media description (post_content)',
									),
									'caption'     => array(
										'type'        => 'string',
										'description' => 'Media caption (post_excerpt)',
									),
								),
								'required' => array( 'id' ),
							),
						),
					),
					'required' => array( 'items' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'execute_callback'    => array( self::class, 'bulk_update_media' ),
			)
		);

		// Bulk Delete Media
		self::wmc_register(
			'wmc/bulk-delete-media',
			array(
				'label'       => 'Bulk Delete Media',
				'description' => 'Delete multiple media files in one call',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'ids' => array(
							'type'        => 'array',
							'description' => 'Array of media attachment IDs to delete',
							'items'       => array( 'type' => 'integer' ),
						),
					),
					'required' => array( 'ids' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => fn() => current_user_can( 'delete_posts' ),
				'execute_callback'    => array( self::class, 'bulk_delete_media' ),
			)
		);
	}

	/**
	 * ==================== COMMENT ABILITIES ====================
	 */

	/**
	 * Register comment abilities
	 */
	private static function register_comment_abilities() {
		// Get Comments
		self::wmc_register(
			'wmc/get-comments',
			array(
				'label'       => 'Get Comments',
				'description' => 'Retrieve post comments',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'    => 'integer',
							'default' => 50,
						),
						'status'   => array(
							'type'    => 'string',
							'default' => 'approve',
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'comments' => array( 'type' => 'array' ),
						'total'    => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'moderate_comments' ),
				'execute_callback'    => array( self::class, 'get_comments' ),
			)
		);

		// Moderate Comment
		self::wmc_register(
			'wmc/moderate-comment',
			array(
				'label'       => 'Moderate Comment',
				'description' => 'Approve, unapprove, or trash a comment',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'     => array( 'type' => 'integer' ),
						'status' => array(
							'type'        => 'string',
							'description' => 'approve, unapprove, or spam',
						),
					),
					'required'   => array( 'id', 'status' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'moderate_comments' ),
				'execute_callback'    => array( self::class, 'moderate_comment' ),
			)
		);

		// Delete Comment
		self::wmc_register(
			'wmc/delete-comment',
			array(
				'label'       => 'Delete Comment',
				'description' => 'Permanently delete a comment (Admin/Editor only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'moderate_comments' ),
				'execute_callback'    => array( self::class, 'delete_comment' ),
			)
		);
	}

	/**
	 * ==================== USER ABILITIES ====================
	 */

	/**
	 * Register user abilities
	 */
	private static function register_user_abilities() {
		// Get Users
		self::wmc_register(
			'wmc/get-users',
			array(
				'label'       => 'Get Users',
				'description' => 'Retrieve WordPress user list with basic information',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array(
							'type'    => 'integer',
							'default' => 50,
						),
						'role'     => array(
							'type'        => 'string',
							'description' => 'Filter by user role',
						),
					),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'users' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'list_users' ),
				'execute_callback'    => array( self::class, 'get_users' ),
			)
		);

		// Create User
		self::wmc_register(
			'wmc/create-user',
			array(
				'label'       => 'Create User',
				'description' => 'Create a new WordPress user (Admin only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'username' => array(
							'type'        => 'string',
							'description' => 'Username (required)',
						),
						'email'    => array(
							'type'        => 'string',
							'description' => 'Email address (required)',
						),
						'password' => array(
							'type'        => 'string',
							'description' => 'Password (required)',
						),
						'first_name' => array( 'type' => 'string' ),
						'last_name'  => array( 'type' => 'string' ),
						'role'       => array(
							'type'        => 'string',
							'description' => 'User role (subscriber, contributor, author, editor, administrator)',
							'default'     => 'subscriber',
						),
					),
					'required'   => array( 'username', 'email', 'password' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'username' => array( 'type' => 'string' ),
						'email'   => array( 'type' => 'string' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'create_users' ),
				'execute_callback'    => array( self::class, 'create_user' ),
			)
		);

		// Update User
		self::wmc_register(
			'wmc/update-user',
			array(
				'label'       => 'Update User',
				'description' => 'Update a WordPress user (Admin only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array(
							'type'        => 'integer',
							'description' => 'User ID (required)',
						),
						'email'      => array( 'type' => 'string' ),
						'first_name' => array( 'type' => 'string' ),
						'last_name'  => array( 'type' => 'string' ),
						'role'       => array( 'type' => 'string' ),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'edit_users' ),
				'execute_callback'    => array( self::class, 'update_user' ),
			)
		);

		// Delete User
		self::wmc_register(
			'wmc/delete-user',
			array(
				'label'       => 'Delete User',
				'description' => 'Delete a WordPress user (Admin only)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array(
							'type'        => 'integer',
							'description' => 'User ID to delete (required)',
						),
						'reassign_to' => array(
							'type'        => 'integer',
							'description' => 'User ID to reassign posts to',
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => fn() => current_user_can( 'delete_users' ),
				'execute_callback'    => array( self::class, 'delete_user' ),
			)
		);
	}

	/**
	 * ==================== CALLBACK IMPLEMENTATIONS ====================
	 */

	/**
	 * Get Posts callback
	 */
	public static function get_posts( $input ) {
		$args  = array(
			'post_type'      => 'post',
			'posts_per_page' => $input['per_page'] ?? 50,
			'paged'          => $input['page'] ?? 1,
			'post_status'    => $input['status'] ?? 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$posts[] = array(
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'content'  => $post->post_content,
				'excerpt'  => $post->post_excerpt,
				'url'      => get_permalink( $post->ID ),
				'date'     => $post->post_date,
				'author'   => get_the_author_meta( 'display_name', $post->post_author ),
				'status'   => $post->post_status,
			);
		}

		return array(
			'posts' => $posts,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Create Post callback
	 */
	public static function create_post( $input ) {
		// CHECK PERMISSION: Is create post enabled?
		if ( ! self::is_enabled( 'posts', 'create' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		// DUPLICATE PREVENTION: Check if post with same title already exists
		$existing = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'title'          => $input['title'],
			'posts_per_page' => 1,
		) );

		if ( ! empty( $existing ) ) {
			return array(
				'success'     => false,
				'message'     => 'Post with this title already exists',
				'existing_id' => $existing[0]->ID,
				'duplicate'   => true,
			);
		}

		$status = $input['status'] ?? 'draft';

		$post_data = array(
			'post_title'   => $input['title'],
			'post_content' => $input['content'] ?? '',
			'post_excerpt' => $input['excerpt'] ?? '',
			'post_status'  => $status,
			'post_type'    => 'post',
		);

		// Handle scheduled date
		$schedule = self::resolve_schedule( $input['date'] ?? null, $status );
		if ( is_wp_error( $schedule ) ) {
			return array(
				'success' => false,
				'message' => $schedule->get_error_message(),
			);
		}
		if ( $schedule ) {
			$post_data['post_status']   = $schedule['post_status'];
			$post_data['post_date']     = $schedule['post_date'];
			$post_data['post_date_gmt'] = $schedule['post_date_gmt'];
			// edit_date forces WP to honor our date instead of overwriting with "now"
			$post_data['edit_date']     = true;
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return array(
				'success' => false,
				'message' => $post_id->get_error_message(),
			);
		}

		// Set categories
		if ( ! empty( $input['categories'] ) ) {
			wp_set_post_categories( $post_id, $input['categories'] );
		}

		// Set tags
		if ( ! empty( $input['tags'] ) ) {
			wp_set_post_tags( $post_id, $input['tags'] );
		}

		$final_post = get_post( $post_id );

		return array(
			'id'        => $post_id,
			'title'     => $input['title'],
			'url'       => get_permalink( $post_id ),
			'status'    => $final_post ? $final_post->post_status : ( $post_data['post_status'] ),
			'date'      => $final_post ? $final_post->post_date : null,
			'scheduled' => $final_post && $final_post->post_status === 'future',
			'success'   => true,
		);
	}

	/**
	 * Update Post callback
	 */
	public static function update_post( $input ) {
		// CHECK PERMISSION: Is update post enabled?
		if ( ! self::is_enabled( 'posts', 'update' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$post_data = array(
			'ID' => $input['id'],
		);

		if ( isset( $input['title'] ) ) {
			$post_data['post_title'] = $input['title'];
		}

		if ( isset( $input['content'] ) ) {
			$post_data['post_content'] = $input['content'];
		}

		if ( isset( $input['status'] ) ) {
			$post_data['post_status'] = $input['status'];
		}

		// Handle scheduled date update
		if ( isset( $input['date'] ) ) {
			$current_status = $input['status']
				?? ( get_post_status( $input['id'] ) ?: 'publish' );
			$schedule = self::resolve_schedule( $input['date'], $current_status );
			if ( is_wp_error( $schedule ) ) {
				return array(
					'id'      => $input['id'],
					'success' => false,
					'message' => $schedule->get_error_message(),
				);
			}
			if ( $schedule ) {
				$post_data['post_status']   = $schedule['post_status'];
				$post_data['post_date']     = $schedule['post_date'];
				$post_data['post_date_gmt'] = $schedule['post_date_gmt'];
				$post_data['edit_date']     = true;
			}
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		// Update categories
		if ( isset( $input['categories'] ) ) {
			wp_set_post_categories( $input['id'], $input['categories'] );
		}

		$final_post = get_post( $input['id'] );

		return array(
			'id'        => $input['id'],
			'success'   => true,
			'message'   => 'Post updated successfully',
			'status'    => $final_post ? $final_post->post_status : null,
			'date'      => $final_post ? $final_post->post_date : null,
			'scheduled' => $final_post && $final_post->post_status === 'future',
		);
	}

	/**
	 * Delete Post callback
	 */
	public static function delete_post( $input ) {
		// CHECK PERMISSION: Is delete post enabled?
		if ( ! self::is_enabled( 'posts', 'delete' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_post( $input['id'], true );

		if ( ! $result ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
				'message' => 'Failed to delete post',
			);
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
			'message' => 'Post deleted successfully',
		);
	}

	/**
	 * Get Pages callback
	 */
	public static function get_pages( $input ) {
		$args  = array(
			'post_type'      => 'page',
			'posts_per_page' => $input['per_page'] ?? 50,
			'paged'          => $input['page'] ?? 1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );
		$pages = array();

		foreach ( $query->posts as $post ) {
			$pages[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'content' => $post->post_content,
				'url'     => get_permalink( $post->ID ),
				'status'  => $post->post_status,
			);
		}

		return array(
			'pages' => $pages,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Create Page callback
	 */
	public static function create_page( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'pages', 'create' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		// DUPLICATE PREVENTION: Check if page with same title already exists
		$existing = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'title'          => $input['title'],
			'posts_per_page' => 1,
		) );

		if ( ! empty( $existing ) ) {
			return array(
				'success'     => false,
				'message'     => 'Page with this title already exists',
				'existing_id' => $existing[0]->ID,
				'duplicate'   => true,
			);
		}

		$status = $input['status'] ?? 'draft';

		$page_data = array(
			'post_title'   => $input['title'],
			'post_content' => $input['content'] ?? '',
			'post_status'  => $status,
			'post_type'    => 'page',
		);

		// Handle scheduled date
		$schedule = self::resolve_schedule( $input['date'] ?? null, $status );
		if ( is_wp_error( $schedule ) ) {
			return array(
				'success' => false,
				'message' => $schedule->get_error_message(),
			);
		}
		if ( $schedule ) {
			$page_data['post_status']   = $schedule['post_status'];
			$page_data['post_date']     = $schedule['post_date'];
			$page_data['post_date_gmt'] = $schedule['post_date_gmt'];
			$page_data['edit_date']     = true;
		}

		$page_id = wp_insert_post( $page_data, true );

		if ( is_wp_error( $page_id ) ) {
			return array(
				'success' => false,
				'message' => $page_id->get_error_message(),
			);
		}

		$final_page = get_post( $page_id );

		return array(
			'id'        => $page_id,
			'title'     => $input['title'],
			'url'       => get_permalink( $page_id ),
			'status'    => $final_page ? $final_page->post_status : $page_data['post_status'],
			'date'      => $final_page ? $final_page->post_date : null,
			'scheduled' => $final_page && $final_page->post_status === 'future',
			'success'   => true,
		);
	}

	/**
	 * Update Page callback
	 */
	public static function update_page( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'pages', 'update' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$page_data = array(
			'ID' => $input['id'],
		);

		if ( isset( $input['title'] ) ) {
			$page_data['post_title'] = $input['title'];
		}

		if ( isset( $input['content'] ) ) {
			$page_data['post_content'] = $input['content'];
		}

		if ( isset( $input['status'] ) ) {
			$page_data['post_status'] = $input['status'];
		}

		// Handle scheduled date update
		if ( isset( $input['date'] ) ) {
			$current_status = $input['status']
				?? ( get_post_status( $input['id'] ) ?: 'publish' );
			$schedule = self::resolve_schedule( $input['date'], $current_status );
			if ( is_wp_error( $schedule ) ) {
				return array(
					'id'      => $input['id'],
					'success' => false,
					'message' => $schedule->get_error_message(),
				);
			}
			if ( $schedule ) {
				$page_data['post_status']   = $schedule['post_status'];
				$page_data['post_date']     = $schedule['post_date'];
				$page_data['post_date_gmt'] = $schedule['post_date_gmt'];
				$page_data['edit_date']     = true;
			}
		}

		$result = wp_update_post( $page_data, true );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		$final_page = get_post( $input['id'] );

		return array(
			'id'        => $input['id'],
			'success'   => true,
			'status'    => $final_page ? $final_page->post_status : null,
			'date'      => $final_page ? $final_page->post_date : null,
			'scheduled' => $final_page && $final_page->post_status === 'future',
		);
	}

	/**
	 * Delete Page callback
	 */
	public static function delete_page( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'pages', 'delete' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_post( $input['id'], true );

		if ( ! $result ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
			);
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
		);
	}

	/**
	 * Get Categories callback
	 */
	public static function get_categories( $input ) {
		$categories = get_categories( array(
			'number' => $input['per_page'] ?? 100,
		) );

		$formatted = array();
		foreach ( $categories as $cat ) {
			$formatted[] = array(
				'id'          => $cat->term_id,
				'name'        => $cat->name,
				'slug'        => $cat->slug,
				'description' => $cat->description,
				'count'       => $cat->count,
			);
		}

		return array(
			'categories' => $formatted,
			'total'      => count( $formatted ),
		);
	}

	/**
	 * Create Category callback
	 */
	public static function create_category( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'categories', 'write' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		// DUPLICATE PREVENTION: Check if category with same name already exists
		$existing = get_term_by( 'name', $input['name'], 'category' );

		if ( $existing ) {
			return array(
				'success'     => false,
				'message'     => 'Category with this name already exists',
				'existing_id' => $existing->term_id,
				'duplicate'   => true,
			);
		}

		$result = wp_insert_category( array(
			'cat_name'             => $input['name'],
			'category_description' => $input['description'] ?? '',
		) );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'id'      => $result,
			'name'    => $input['name'],
			'success' => true,
		);
	}

	/**
	 * Update Category callback
	 */
	public static function update_category( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'categories', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$update_data = array(
			'cat_ID' => $input['id'],
		);

		if ( isset( $input['name'] ) ) {
			$update_data['cat_name'] = $input['name'];
		}

		if ( isset( $input['description'] ) ) {
			$update_data['category_description'] = $input['description'];
		}

		$result = wp_update_category( $update_data );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
			);
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
		);
	}

	/**
	 * Delete Category callback
	 */
	public static function delete_category( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'categories', 'write' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_category( $input['id'] );

		if ( is_wp_error( $result ) ) {
			return array( 'success' => false );
		}

		return array( 'success' => true );
	}

	/**
	 * Get Tags callback
	 */
	public static function get_tags( $input ) {
		$tags = get_tags( array(
			'number' => $input['per_page'] ?? 100,
		) );

		$formatted = array();
		foreach ( $tags as $tag ) {
			$formatted[] = array(
				'id'    => $tag->term_id,
				'name'  => $tag->name,
				'slug'  => $tag->slug,
				'count' => $tag->count,
			);
		}

		return array(
			'tags'  => $formatted,
			'total' => count( $formatted ),
		);
	}

	/**
	 * Create Tag callback
	 */
	public static function create_tag( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'tags', 'write' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		// DUPLICATE PREVENTION: Check if tag with same name already exists
		$existing = get_term_by( 'name', $input['name'], 'post_tag' );

		if ( $existing ) {
			return array(
				'success'     => false,
				'message'     => 'Tag with this name already exists',
				'existing_id' => $existing->term_id,
				'duplicate'   => true,
			);
		}

		$result = wp_insert_term(
			$input['name'],
			'post_tag',
			array(
				'description' => $input['description'] ?? '',
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'id'      => $result['term_id'],
			'name'    => $input['name'],
			'success' => true,
		);
	}

	/**
	 * Update Tag callback
	 */
	public static function update_tag( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'tags', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$update_data = array();

		if ( isset( $input['name'] ) ) {
			$update_data['name'] = $input['name'];
		}

		if ( isset( $input['description'] ) ) {
			$update_data['description'] = $input['description'];
		}

		$result = wp_update_term( $input['id'], 'post_tag', $update_data );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
			);
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
		);
	}

	/**
	 * Delete Tag callback
	 */
	public static function delete_tag( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'tags', 'write' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_term( $input['id'], 'post_tag' );

		if ( is_wp_error( $result ) ) {
			return array( 'success' => false );
		}

		return array( 'success' => true );
	}

	/**
	 * Get Media callback
	 */
	public static function get_media( $input ) {
		$args  = array(
			'post_type'      => 'attachment',
			'posts_per_page' => $input['per_page'] ?? 50,
			'post_status'    => 'inherit',
		);

		$query = new WP_Query( $args );
		$media = array();

		foreach ( $query->posts as $attachment ) {
			$media[] = array(
				'id'    => $attachment->ID,
				'title' => $attachment->post_title,
				'url'   => wp_get_attachment_url( $attachment->ID ),
				'type'  => get_post_mime_type( $attachment->ID ),
			);
		}

		return array(
			'media' => $media,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Create Media callback
	 */
	public static function create_media( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'media', 'write' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		// Note: In a real implementation, you'd handle file uploads from the request
		// This is a placeholder for metadata creation
		$media_data = array(
			'post_title'     => $input['title'] ?? 'Media',
			'post_content'   => $input['description'] ?? '',
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
		);

		$attachment_id = wp_insert_post( $media_data );

		if ( is_wp_error( $attachment_id ) ) {
			return array(
				'success' => false,
				'message' => $attachment_id->get_error_message(),
			);
		}

		// Update alt text if provided
		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $input['alt_text'] );
		}

		return array(
			'id'      => $attachment_id,
			'url'     => wp_get_attachment_url( $attachment_id ),
			'success' => true,
		);
	}

	/**
	 * Update Media callback
	 */
	public static function update_media( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'media', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$media_data = array(
			'ID' => $input['id'],
		);

		if ( isset( $input['title'] ) ) {
			$media_data['post_title'] = $input['title'];
		}

		if ( isset( $input['description'] ) ) {
			$media_data['post_content'] = $input['description'];
		}

		$result = wp_update_post( $media_data );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
			);
		}

		// Update alt text if provided
		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $input['id'], '_wp_attachment_image_alt', $input['alt_text'] );
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
		);
	}

	/**
	 * Delete Media callback
	 */
	public static function delete_media( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'media', 'write' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_attachment( $input['id'], true );

		if ( ! $result ) {
			return array(
				'success' => false,
				'message' => 'Failed to delete media',
			);
		}

		return array(
			'success' => true,
			'message' => 'Media deleted successfully',
		);
	}

	/**
	 * Get Media Details callback
	 */
	public static function get_media_details( $input ) {
		if ( ! self::is_enabled( 'media', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$id   = (int) $input['id'];
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'attachment' ) {
			return array( 'success' => false, 'message' => 'Media not found' );
		}

		$meta      = wp_get_attachment_metadata( $id );
		$file_path = get_attached_file( $id );
		$file_size = $file_path && file_exists( $file_path ) ? filesize( $file_path ) : null;

		return array(
			'success'       => true,
			'id'            => $id,
			'title'         => $post->post_title,
			'alt_text'      => get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'caption'       => $post->post_excerpt,
			'description'   => $post->post_content,
			'url'           => wp_get_attachment_url( $id ),
			'mime_type'     => $post->post_mime_type,
			'file_name'     => basename( get_attached_file( $id ) ),
			'file_size'     => $file_size,
			'file_size_kb'  => $file_size ? round( $file_size / 1024, 2 ) : null,
			'width'         => $meta['width'] ?? null,
			'height'        => $meta['height'] ?? null,
			'date_uploaded' => $post->post_date,
			'attached_to'   => array(
				'post_id'    => $post->post_parent ?: null,
				'post_title' => $post->post_parent ? get_the_title( $post->post_parent ) : null,
				'post_url'   => $post->post_parent ? get_permalink( $post->post_parent ) : null,
			),
			'sizes'         => isset( $meta['sizes'] ) ? array_map( fn( $s ) => array(
				'width'  => $s['width'],
				'height' => $s['height'],
				'file'   => $s['file'],
			), $meta['sizes'] ) : array(),
		);
	}

	/**
	 * Get Media Without Alt Text callback
	 */
	public static function get_media_without_alt( $input ) {
		if ( ! self::is_enabled( 'media', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$per_page  = min( (int) ( $input['per_page'] ?? 50 ), 200 );
		$page      = (int) ( $input['page'] ?? 1 );
		$mime_type = $input['mime_type'] ?? 'image';

		// Find attachments where _wp_attachment_image_alt is empty or missing
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_mime_type' => $mime_type,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '=',
				),
			),
		);

		$query  = new WP_Query( $args );
		$result = array();

		foreach ( $query->posts as $post ) {
			$result[] = array(
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'url'       => wp_get_attachment_url( $post->ID ),
				'mime_type' => $post->post_mime_type,
				'date'      => $post->post_date,
				'attached_to' => $post->post_parent ? get_the_title( $post->post_parent ) : null,
			);
		}

		return array(
			'success'     => true,
			'count'       => count( $result ),
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'message'     => $query->found_posts . ' image(s) missing alt text',
			'media'       => $result,
		);
	}

	/**
	 * Search Media (Advanced) callback
	 */
	public static function search_media( $input ) {
		if ( ! self::is_enabled( 'media', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$per_page = min( (int) ( $input['per_page'] ?? 50 ), 200 );
		$page     = (int) ( $input['page'] ?? 1 );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		if ( ! empty( $input['mime_type'] ) ) {
			$args['post_mime_type'] = $input['mime_type'];
		}

		// attached / unattached filter
		$attached = $input['attached'] ?? 'all';
		if ( $attached === 'attached' ) {
			$args['post_parent__not_in'] = array( 0 );
		} elseif ( $attached === 'unattached' ) {
			$args['post_parent'] = 0;
		}

		// date range
		$date_query = array();
		if ( ! empty( $input['date_after'] ) ) {
			$date_query['after'] = sanitize_text_field( $input['date_after'] );
		}
		if ( ! empty( $input['date_before'] ) ) {
			$date_query['before'] = sanitize_text_field( $input['date_before'] );
		}
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}

		// has_alt filter
		$has_alt = $input['has_alt'] ?? 'all';
		if ( $has_alt === 'no' ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_wp_attachment_image_alt', 'value' => '', 'compare' => '=' ),
			);
		} elseif ( $has_alt === 'yes' ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '!=',
				),
			);
		}

		$query  = new WP_Query( $args );
		$result = array();

		foreach ( $query->posts as $post ) {
			$alt = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
			$result[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'alt_text'    => $alt,
				'caption'     => $post->post_excerpt,
				'description' => $post->post_content,
				'url'         => wp_get_attachment_url( $post->ID ),
				'mime_type'   => $post->post_mime_type,
				'date'        => $post->post_date,
				'attached_to' => $post->post_parent ? array(
					'id'    => $post->post_parent,
					'title' => get_the_title( $post->post_parent ),
				) : null,
			);
		}

		return array(
			'success'     => true,
			'count'       => count( $result ),
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'media'       => $result,
		);
	}

	/**
	 * Bulk Update Media callback
	 */
	public static function bulk_update_media( $input ) {
		if ( ! self::is_enabled( 'media', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$items    = $input['items'] ?? array();
		$updated  = array();
		$failed   = array();

		foreach ( $items as $item ) {
			$id = (int) ( $item['id'] ?? 0 );
			if ( ! $id ) {
				$failed[] = array( 'id' => $id, 'reason' => 'Missing ID' );
				continue;
			}

			$post = get_post( $id );
			if ( ! $post || $post->post_type !== 'attachment' ) {
				$failed[] = array( 'id' => $id, 'reason' => 'Not found' );
				continue;
			}

			$post_data = array( 'ID' => $id );
			if ( isset( $item['title'] ) )       $post_data['post_title']   = sanitize_text_field( $item['title'] );
			if ( isset( $item['description'] ) ) $post_data['post_content'] = wp_kses_post( $item['description'] );
			if ( isset( $item['caption'] ) )     $post_data['post_excerpt'] = sanitize_text_field( $item['caption'] );

			if ( count( $post_data ) > 1 ) {
				$result = wp_update_post( $post_data, true );
				if ( is_wp_error( $result ) ) {
					$failed[] = array( 'id' => $id, 'reason' => $result->get_error_message() );
					continue;
				}
			}

			if ( isset( $item['alt_text'] ) ) {
				update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $item['alt_text'] ) );
			}

			$updated[] = array(
				'id'          => $id,
				'title'       => get_the_title( $id ),
				'alt_text'    => get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'url'         => wp_get_attachment_url( $id ),
			);
		}

		return array(
			'success'       => true,
			'updated_count' => count( $updated ),
			'failed_count'  => count( $failed ),
			'updated'       => $updated,
			'failed'        => $failed,
		);
	}

	/**
	 * Bulk Delete Media callback
	 */
	public static function bulk_delete_media( $input ) {
		if ( ! self::is_enabled( 'media', 'write' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$ids     = $input['ids'] ?? array();
		$deleted = array();
		$failed  = array();

		foreach ( $ids as $id ) {
			$id   = (int) $id;
			$post = get_post( $id );

			if ( ! $post || $post->post_type !== 'attachment' ) {
				$failed[] = array( 'id' => $id, 'reason' => 'Not found' );
				continue;
			}

			$title  = $post->post_title;
			$result = wp_delete_attachment( $id, true );

			if ( $result ) {
				$deleted[] = array( 'id' => $id, 'title' => $title );
			} else {
				$failed[] = array( 'id' => $id, 'reason' => 'Delete failed' );
			}
		}

		return array(
			'success'       => true,
			'deleted_count' => count( $deleted ),
			'failed_count'  => count( $failed ),
			'deleted'       => $deleted,
			'failed'        => $failed,
		);
	}

	/**
	 * Get Comments callback
	 */
	public static function get_comments( $input ) {
		$args     = array(
			'number'       => $input['per_page'] ?? 50,
			'status'       => $input['status'] ?? 'approve',
		);

		$comments = get_comments( $args );
		$formatted = array();

		foreach ( $comments as $comment ) {
			$formatted[] = array(
				'id'      => $comment->comment_ID,
				'author'  => $comment->comment_author,
				'content' => $comment->comment_content,
				'status'  => $comment->comment_approved,
				'post_id' => $comment->comment_post_ID,
			);
		}

		return array(
			'comments' => $formatted,
			'total'    => count( $formatted ),
		);
	}

	/**
	 * Moderate Comment callback
	 */
	public static function moderate_comment( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'comments', 'moderate' ) ) {
			return self::get_disabled_error( 'Moderate' );
		}

		$status_map = array(
			'approve'   => 1,
			'unapprove' => 0,
			'spam'      => 'spam',
		);

		$result = wp_update_comment( array(
			'comment_ID'       => $input['id'],
			'comment_approved' => $status_map[ $input['status'] ] ?? 0,
		) );

		if ( ! $result ) {
			return array( 'success' => false );
		}

		return array( 'success' => true );
	}

	/**
	 * Delete Comment callback
	 */
	public static function delete_comment( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'comments', 'delete' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_comment( $input['id'], true );

		if ( ! $result ) {
			return array( 'success' => false );
		}

		return array( 'success' => true );
	}

	/**
	 * Get Users callback
	 */
	public static function get_users( $input ) {
		$args = array(
			'number' => $input['per_page'] ?? 50,
		);

		if ( ! empty( $input['role'] ) ) {
			$args['role'] = $input['role'];
		}

		$users     = get_users( $args );
		$formatted = array();

		foreach ( $users as $user ) {
			$formatted[] = array(
				'id'       => $user->ID,
				'username' => $user->user_login,
				'email'    => $user->user_email,
				'name'     => $user->display_name,
				'role'     => ! empty( $user->roles ) ? $user->roles[0] : 'subscriber',
			);
		}

		return array(
			'users' => $formatted,
			'total' => count( $formatted ),
		);
	}

	/**
	 * Create User callback
	 */
	public static function create_user( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'users', 'write' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		// DUPLICATE PREVENTION: Check if username already exists
		if ( username_exists( $input['username'] ) ) {
			return array(
				'success'   => false,
				'message'   => 'Username already exists',
				'duplicate' => true,
			);
		}

		// DUPLICATE PREVENTION: Check if email already exists
		if ( email_exists( $input['email'] ) ) {
			return array(
				'success'   => false,
				'message'   => 'Email already registered',
				'duplicate' => true,
			);
		}

		$user_data = array(
			'user_login' => $input['username'],
			'user_email' => $input['email'],
			'user_pass'  => $input['password'],
			'first_name' => $input['first_name'] ?? '',
			'last_name'  => $input['last_name'] ?? '',
			'role'       => $input['role'] ?? 'subscriber',
		);

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return array(
				'success' => false,
				'message' => $user_id->get_error_message(),
			);
		}

		$user = get_user_by( 'id', $user_id );

		return array(
			'id'       => $user_id,
			'username' => $user->user_login,
			'email'    => $user->user_email,
			'success'  => true,
		);
	}

	/**
	 * Update User callback
	 */
	public static function update_user( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'users', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$user_data = array(
			'ID' => $input['id'],
		);

		if ( isset( $input['email'] ) ) {
			$user_data['user_email'] = $input['email'];
		}

		if ( isset( $input['first_name'] ) ) {
			$user_data['first_name'] = $input['first_name'];
		}

		if ( isset( $input['last_name'] ) ) {
			$user_data['last_name'] = $input['last_name'];
		}

		$result = wp_update_user( $user_data );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		// Update user role if provided
		if ( isset( $input['role'] ) ) {
			$user = get_user_by( 'id', $input['id'] );
			$user->set_role( $input['role'] );
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
		);
	}

	/**
	 * Delete User callback
	 */
	public static function delete_user( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'users', 'write' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$reassign_to = $input['reassign_to'] ?? null;
		$result      = wp_delete_user( $input['id'], $reassign_to );

		if ( ! $result ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
				'message' => 'Failed to delete user',
			);
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
			'message' => 'User deleted successfully',
		);
	}

	/**
	 * ==================== SETTINGS ABILITIES ====================
	 */

	private static function register_settings_abilities() {
		self::wmc_register(
			'wmc/get-options',
			array(
				'label'       => 'Get Settings',
				'description' => 'Retrieve WordPress settings (General, Reading, Writing, Discussion, Media)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'option_names' => array(
							'type'        => 'array',
							'description' => 'Specific option names to retrieve (e.g., "blogname", "home", "admin_email")',
							'items'       => array( 'type' => 'string' ),
						),
					),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'get_options' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/update-option',
			array(
				'label'       => 'Update Setting',
				'description' => 'Modify WordPress settings',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'option_name'  => array(
							'type'        => 'string',
							'description' => 'The option name to update (e.g., "blogname", "blogdescription")',
						),
						'option_value' => array(
							'description' => 'The new value for the option',
						),
					),
					'required' => array( 'option_name', 'option_value' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'update_option' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_options( $input = array() ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'settings', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$option_names = $input['option_names'] ?? array(
			'blogname',
			'blogdescription',
			'home',
			'siteurl',
			'admin_email',
			'posts_per_page',
			'posts_per_rss',
			'default_category',
			'default_comment_status',
			'default_ping_status',
			'default_pingback_flag',
			'comments_notify',
			'moderation_notify',
		);

		$options = array();
		foreach ( $option_names as $name ) {
			$options[ $name ] = get_option( $name );
		}

		return array(
			'success' => true,
			'options' => $options,
		);
	}

	public static function update_option( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'settings', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		// Only allow safe options
		$allowed_options = array(
			'blogname',
			'blogdescription',
			'admin_email',
			'posts_per_page',
			'posts_per_rss',
			'default_category',
			'default_comment_status',
			'default_ping_status',
			'default_pingback_flag',
			'comments_notify',
			'moderation_notify',
		);

		if ( ! in_array( $input['option_name'], $allowed_options, true ) ) {
			return array(
				'success' => false,
				'message' => 'Option "' . esc_attr( $input['option_name'] ) . '" is not allowed to be modified',
			);
		}

		$updated = update_option( $input['option_name'], $input['option_value'] );

		return array(
			'success'      => $updated || get_option( $input['option_name'] ) === $input['option_value'],
			'option_name'  => $input['option_name'],
			'option_value' => $input['option_value'],
			'message'      => 'Option updated successfully',
		);
	}

	/**
	 * ==================== MENU ABILITIES ====================
	 */

	private static function register_menu_abilities() {
		self::wmc_register(
			'wmc/get-menus',
			array(
				'label'       => 'Get Menus',
				'description' => 'Retrieve all registered menus and their items',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'get_menus' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/create-menu',
			array(
				'label'       => 'Create Menu',
				'description' => 'Create a new WordPress menu',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array(
							'type'        => 'string',
							'description' => 'The menu name',
						),
					),
					'required' => array( 'name' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'create_menu' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/delete-menu',
			array(
				'label'       => 'Delete Menu',
				'description' => 'Delete a WordPress menu',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'menu_id' => array(
							'type'        => 'integer',
							'description' => 'The menu ID to delete',
						),
					),
					'required' => array( 'menu_id' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'delete_menu' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_menus( $input = array() ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'menus', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$menus = wp_get_nav_menus();
		$menu_data = array();

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			$menu_data[] = array(
				'id'    => $menu->term_id,
				'name'  => $menu->name,
				'slug'  => $menu->slug,
				'count' => $menu->count,
				'items' => $items ? wp_list_pluck( $items, 'title', 'ID' ) : array(),
			);
		}

		return array(
			'success' => true,
			'menus'   => $menu_data,
			'count'   => count( $menu_data ),
		);
	}

	public static function create_menu( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'menus', 'write' ) ) {
			return self::get_disabled_error( 'Create' );
		}

		$menu_id = wp_create_nav_menu( $input['name'] );

		if ( is_wp_error( $menu_id ) ) {
			return array(
				'success' => false,
				'message' => $menu_id->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'menu_id' => $menu_id,
			'name'    => $input['name'],
			'message' => 'Menu created successfully',
		);
	}

	public static function delete_menu( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'menus', 'write' ) ) {
			return self::get_disabled_error( 'Delete' );
		}

		$result = wp_delete_nav_menu( $input['menu_id'] );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success'  => true,
			'menu_id'  => $input['menu_id'],
			'message'  => 'Menu deleted successfully',
		);
	}

	/**
	 * ==================== WIDGET ABILITIES ====================
	 */

	private static function register_widget_abilities() {
		self::wmc_register(
			'wmc/get-sidebars',
			array(
				'label'       => 'Get Widget Areas',
				'description' => 'Retrieve all widget areas/sidebars and their widgets',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'get_sidebars' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/update-widget-option',
			array(
				'label'       => 'Update Widget Settings',
				'description' => 'Modify widget options/settings',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'widget_id'  => array(
							'type'        => 'string',
							'description' => 'The widget ID',
						),
						'option_key' => array(
							'type'        => 'string',
							'description' => 'The option key to update',
						),
						'option_value' => array(
							'description' => 'The new value',
						),
					),
					'required' => array( 'widget_id', 'option_key' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'update_widget_option' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_sidebars( $input = array() ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'widgets', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		global $wp_registered_sidebars;
		$sidebars_data = array();

		if ( isset( $wp_registered_sidebars ) && is_array( $wp_registered_sidebars ) ) {
			foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
				$sidebars_data[] = array(
					'id'   => $sidebar_id,
					'name' => $sidebar['name'],
					'description' => $sidebar['description'] ?? '',
				);
			}
		}

		return array(
			'success'  => true,
			'sidebars' => $sidebars_data,
			'count'    => count( $sidebars_data ),
		);
	}

	public static function update_widget_option( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'widgets', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		update_option( 'widget_' . $input['widget_id'], $input['option_value'] );

		return array(
			'success'       => true,
			'widget_id'     => $input['widget_id'],
			'option_key'    => $input['option_key'],
			'option_value'  => $input['option_value'],
			'message'       => 'Widget updated successfully',
		);
	}

	/**
	 * ==================== THEME ABILITIES ====================
	 */

	private static function register_theme_abilities() {
		self::wmc_register(
			'wmc/get-themes',
			array(
				'label'       => 'Get Themes',
				'description' => 'Retrieve all installed themes and current theme info',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'get_themes' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/activate-theme',
			array(
				'label'       => 'Activate Theme',
				'description' => 'Switch the active WordPress theme',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'theme_slug' => array(
							'type'        => 'string',
							'description' => 'The theme directory name (slug)',
						),
					),
					'required' => array( 'theme_slug' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'activate_theme' ),
				'permission_callback' => fn() => current_user_can( 'switch_themes' ),
			)
		);

		self::wmc_register(
			'wmc/get-theme-mods',
			array(
				'label'       => 'Get Theme Settings',
				'description' => 'Retrieve current theme customization settings',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'get_theme_mods' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/update-theme-mod',
			array(
				'label'       => 'Update Theme Setting',
				'description' => 'Modify theme customization options',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'mod_name'   => array(
							'type'        => 'string',
							'description' => 'The theme mod name',
						),
						'mod_value'  => array(
							'description' => 'The new value for the theme mod',
						),
					),
					'required' => array( 'mod_name' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'execute_callback' => array( self::class, 'update_theme_mod' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_themes( $input = array() ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'themes', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$themes = wp_get_themes();
		$current_theme = wp_get_theme();
		$theme_data = array();

		foreach ( $themes as $theme ) {
			$theme_data[] = array(
				'slug'       => $theme->get_stylesheet(),
				'name'       => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'author'     => $theme->get( 'Author' ),
				'active'     => $theme->get_stylesheet() === $current_theme->get_stylesheet(),
			);
		}

		return array(
			'success'       => true,
			'current'       => $current_theme->get( 'Name' ),
			'current_slug'  => $current_theme->get_stylesheet(),
			'themes'        => $theme_data,
			'count'         => count( $theme_data ),
		);
	}

	public static function activate_theme( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'themes', 'write' ) ) {
			return self::get_disabled_error( 'Activate' );
		}

		// Check if theme exists
		$theme = wp_get_theme( $input['theme_slug'] );
		if ( ! $theme->exists() ) {
			return array(
				'success' => false,
				'message' => 'Theme does not exist',
			);
		}

		switch_theme( $input['theme_slug'] );
		$current = wp_get_theme();

		return array(
			'success'       => $current->get_stylesheet() === $input['theme_slug'],
			'theme_slug'    => $input['theme_slug'],
			'current_theme' => $current->get( 'Name' ),
			'message'       => 'Theme activated successfully',
		);
	}

	public static function get_theme_mods( $input = array() ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'themes', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$mods = get_theme_mods();

		return array(
			'success'     => true,
			'theme_mods'  => $mods ?: array(),
			'count'       => count( $mods ?: array() ),
		);
	}

	public static function update_theme_mod( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'themes', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		set_theme_mod( $input['mod_name'], $input['mod_value'] );

		return array(
			'success'     => true,
			'mod_name'    => $input['mod_name'],
			'mod_value'   => $input['mod_value'],
			'message'     => 'Theme setting updated successfully',
		);
	}

	/**
	 * ==================== SEO META ABILITIES ====================
	 */

	private static function register_seo_abilities() {
		// Get Post SEO Meta
		self::wmc_register(
			'wmc/get-post-seo-meta',
			array(
				'label'       => 'Get Post SEO Meta',
				'description' => 'Retrieve SEO metadata (title, description) from Yoast, Rank Math, or All in One SEO plugins',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => 'Post ID (required)',
						),
					),
					'required' => array( 'post_id' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'execute_callback'    => array( self::class, 'get_post_seo_meta' ),
			)
		);

		// Update Post SEO Meta
		self::wmc_register(
			'wmc/update-post-seo-meta',
			array(
				'label'       => 'Update Post SEO Meta',
				'description' => 'Update SEO metadata (title, description) for posts in Yoast, Rank Math, or All in One SEO',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'        => array(
							'type'        => 'integer',
							'description' => 'Post ID (required)',
						),
						'meta_title'     => array(
							'type'        => 'string',
							'description' => 'SEO meta title',
						),
						'meta_description' => array(
							'type'        => 'string',
							'description' => 'SEO meta description',
						),
						'meta_robots'    => array(
							'type'        => 'string',
							'description' => 'SEO robots meta (e.g., "index,follow")',
						),
					),
					'required' => array( 'post_id' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'execute_callback'    => array( self::class, 'update_post_seo_meta' ),
			)
		);

		// Get Page SEO Meta
		self::wmc_register(
			'wmc/get-page-seo-meta',
			array(
				'label'       => 'Get Page SEO Meta',
				'description' => 'Retrieve SEO metadata (title, description) from Yoast, Rank Math, or All in One SEO plugins for pages',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'page_id' => array(
							'type'        => 'integer',
							'description' => 'Page ID (required)',
						),
					),
					'required' => array( 'page_id' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'permission_callback' => fn() => current_user_can( 'edit_pages' ),
				'execute_callback'    => array( self::class, 'get_page_seo_meta' ),
			)
		);

		// Update Page SEO Meta
		self::wmc_register(
			'wmc/update-page-seo-meta',
			array(
				'label'       => 'Update Page SEO Meta',
				'description' => 'Update SEO metadata (title, description) for pages in Yoast, Rank Math, or All in One SEO',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'page_id'        => array(
							'type'        => 'integer',
							'description' => 'Page ID (required)',
						),
						'meta_title'     => array(
							'type'        => 'string',
							'description' => 'SEO meta title',
						),
						'meta_description' => array(
							'type'        => 'string',
							'description' => 'SEO meta description',
						),
						'meta_robots'    => array(
							'type'        => 'string',
							'description' => 'SEO robots meta (e.g., "index,follow")',
						),
					),
					'required' => array( 'page_id' ),
				),
				'output_schema' => array(
					'type' => 'object',
				),
				'permission_callback' => fn() => current_user_can( 'edit_pages' ),
				'execute_callback'    => array( self::class, 'update_page_seo_meta' ),
			)
		);
	}

	/**
	 * Detect which SEO plugin is active
	 */
	private static function detect_seo_plugin() {
		if ( defined( 'WPSEO_FILE' ) ) {
			return 'yoast'; // Yoast SEO
		} elseif ( defined( 'RANK_MATH_FILE' ) ) {
			return 'rankmath'; // Rank Math
		} elseif ( function_exists( 'aioseo' ) ) {
			return 'aioseo'; // All in One SEO
		}
		return null;
	}

	/**
	 * Get SEO Meta for Post
	 */
	public static function get_post_seo_meta( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'seo', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$post_id = $input['post_id'];
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => 'Post not found',
			);
		}

		$seo_plugin = self::detect_seo_plugin();
		$seo_meta = array(
			'post_id'    => $post_id,
			'post_title' => $post->post_title,
			'post_url'   => get_permalink( $post_id ),
			'plugin'     => $seo_plugin,
		);

		if ( $seo_plugin === 'yoast' ) {
			$seo_meta['meta_title'] = get_post_meta( $post_id, '_yoast_wpseo_title', true );
			$seo_meta['meta_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
			$seo_meta['meta_robots'] = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
			$seo_meta['focus_keyword'] = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
		} elseif ( $seo_plugin === 'rankmath' ) {
			$seo_meta['meta_title'] = get_post_meta( $post_id, 'rank_math_title', true );
			$seo_meta['meta_description'] = get_post_meta( $post_id, 'rank_math_description', true );
			$seo_meta['meta_robots'] = get_post_meta( $post_id, 'rank_math_robots', true );
			$seo_meta['focus_keyword'] = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
		} elseif ( $seo_plugin === 'aioseo' ) {
			$aioseo_data = get_post_meta( $post_id, '_aioseo_title', true );
			$seo_meta['meta_title'] = $aioseo_data ?: get_post_meta( $post_id, '_aioseo_title', true );
			$seo_meta['meta_description'] = get_post_meta( $post_id, '_aioseo_description', true );
			$seo_meta['meta_robots'] = get_post_meta( $post_id, '_aioseo_robots', true );
		}

		return array(
			'success'  => true,
			'seo_meta' => $seo_meta,
		);
	}

	/**
	 * Update SEO Meta for Post
	 */
	public static function update_post_seo_meta( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'seo', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$post_id = $input['post_id'];
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => 'Post not found',
			);
		}

		$seo_plugin = self::detect_seo_plugin();
		$updated = false;

		if ( $seo_plugin === 'yoast' ) {
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_robots'] ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', sanitize_text_field( $input['meta_robots'] ) );
				$updated = true;
			}
		} elseif ( $seo_plugin === 'rankmath' ) {
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_robots'] ) ) {
				update_post_meta( $post_id, 'rank_math_robots', sanitize_text_field( $input['meta_robots'] ) );
				$updated = true;
			}
		} elseif ( $seo_plugin === 'aioseo' ) {
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $post_id, '_aioseo_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $post_id, '_aioseo_description', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_robots'] ) ) {
				update_post_meta( $post_id, '_aioseo_robots', sanitize_text_field( $input['meta_robots'] ) );
				$updated = true;
			}
		} else {
			// Fallback: Update generic meta fields if no plugin detected
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $post_id, '_meta_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $post_id, '_meta_description', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
		}

		return array(
			'success'   => $updated,
			'post_id'   => $post_id,
			'plugin'    => $seo_plugin,
			'message'   => $updated ? 'SEO meta updated successfully' : 'No SEO plugin detected or no fields updated',
		);
	}

	/**
	 * Get SEO Meta for Page
	 */
	public static function get_page_seo_meta( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'seo', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$page_id = $input['page_id'];
		$page = get_post( $page_id );

		if ( ! $page || $page->post_type !== 'page' ) {
			return array(
				'success' => false,
				'message' => 'Page not found',
			);
		}

		$seo_plugin = self::detect_seo_plugin();
		$seo_meta = array(
			'page_id'    => $page_id,
			'page_title' => $page->post_title,
			'page_url'   => get_permalink( $page_id ),
			'plugin'     => $seo_plugin,
		);

		if ( $seo_plugin === 'yoast' ) {
			$seo_meta['meta_title'] = get_post_meta( $page_id, '_yoast_wpseo_title', true );
			$seo_meta['meta_description'] = get_post_meta( $page_id, '_yoast_wpseo_metadesc', true );
			$seo_meta['meta_robots'] = get_post_meta( $page_id, '_yoast_wpseo_meta-robots-noindex', true );
		} elseif ( $seo_plugin === 'rankmath' ) {
			$seo_meta['meta_title'] = get_post_meta( $page_id, 'rank_math_title', true );
			$seo_meta['meta_description'] = get_post_meta( $page_id, 'rank_math_description', true );
			$seo_meta['meta_robots'] = get_post_meta( $page_id, 'rank_math_robots', true );
		} elseif ( $seo_plugin === 'aioseo' ) {
			$seo_meta['meta_title'] = get_post_meta( $page_id, '_aioseo_title', true );
			$seo_meta['meta_description'] = get_post_meta( $page_id, '_aioseo_description', true );
			$seo_meta['meta_robots'] = get_post_meta( $page_id, '_aioseo_robots', true );
		}

		return array(
			'success'  => true,
			'seo_meta' => $seo_meta,
		);
	}

	/**
	 * Update SEO Meta for Page
	 */
	public static function update_page_seo_meta( $input ) {
		// CHECK PERMISSION
		if ( ! self::is_enabled( 'seo', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}

		$page_id = $input['page_id'];
		$page = get_post( $page_id );

		if ( ! $page || $page->post_type !== 'page' ) {
			return array(
				'success' => false,
				'message' => 'Page not found',
			);
		}

		$seo_plugin = self::detect_seo_plugin();
		$updated = false;

		if ( $seo_plugin === 'yoast' ) {
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $page_id, '_yoast_wpseo_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $page_id, '_yoast_wpseo_metadesc', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_robots'] ) ) {
				update_post_meta( $page_id, '_yoast_wpseo_meta-robots-noindex', sanitize_text_field( $input['meta_robots'] ) );
				$updated = true;
			}
		} elseif ( $seo_plugin === 'rankmath' ) {
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $page_id, 'rank_math_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $page_id, 'rank_math_description', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_robots'] ) ) {
				update_post_meta( $page_id, 'rank_math_robots', sanitize_text_field( $input['meta_robots'] ) );
				$updated = true;
			}
		} elseif ( $seo_plugin === 'aioseo' ) {
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $page_id, '_aioseo_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $page_id, '_aioseo_description', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_robots'] ) ) {
				update_post_meta( $page_id, '_aioseo_robots', sanitize_text_field( $input['meta_robots'] ) );
				$updated = true;
			}
		} else {
			// Fallback: Update generic meta fields if no plugin detected
			if ( isset( $input['meta_title'] ) ) {
				update_post_meta( $page_id, '_meta_title', sanitize_text_field( $input['meta_title'] ) );
				$updated = true;
			}
			if ( isset( $input['meta_description'] ) ) {
				update_post_meta( $page_id, '_meta_description', sanitize_text_field( $input['meta_description'] ) );
				$updated = true;
			}
		}

		return array(
			'success'   => $updated,
			'page_id'   => $page_id,
			'plugin'    => $seo_plugin,
			'message'   => $updated ? 'SEO meta updated successfully' : 'No SEO plugin detected or no fields updated',
		);
	}

	/**
	 * ==================== PLUGIN MANAGEMENT ABILITIES ====================
	 */

	private static function register_plugin_abilities() {
		self::wmc_register(
			'wmc/get-plugins',
			array(
				'label'       => 'Get Plugins',
				'description' => 'List all installed WordPress plugins with their status',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type'        => 'string',
							'description' => 'Filter by status: all, active, inactive',
							'enum'        => array( 'all', 'active', 'inactive' ),
							'default'     => 'all',
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_plugins' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/activate-plugin',
			array(
				'label'       => 'Activate Plugin',
				'description' => 'Activate an installed WordPress plugin',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'plugin' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g. "woocommerce/woocommerce.php")',
						),
					),
					'required' => array( 'plugin' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'activate_plugin_cb' ),
				'permission_callback' => fn() => current_user_can( 'activate_plugins' ),
			)
		);

		self::wmc_register(
			'wmc/deactivate-plugin',
			array(
				'label'       => 'Deactivate Plugin',
				'description' => 'Deactivate an active WordPress plugin',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'plugin' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g. "woocommerce/woocommerce.php")',
						),
					),
					'required' => array( 'plugin' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'deactivate_plugin_cb' ),
				'permission_callback' => fn() => current_user_can( 'activate_plugins' ),
			)
		);
	}

	public static function get_plugins( $input ) {
		if ( ! self::is_enabled( 'plugins', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$status_filter  = $input['status'] ?? 'all';
		$result         = array();

		foreach ( $all_plugins as $file => $data ) {
			$is_active = in_array( $file, $active_plugins, true );

			if ( $status_filter === 'active' && ! $is_active ) {
				continue;
			}
			if ( $status_filter === 'inactive' && $is_active ) {
				continue;
			}

			$result[] = array(
				'file'        => $file,
				'name'        => $data['Name'],
				'version'     => $data['Version'],
				'description' => $data['Description'],
				'author'      => $data['Author'],
				'status'      => $is_active ? 'active' : 'inactive',
			);
		}

		return array(
			'success' => true,
			'count'   => count( $result ),
			'plugins' => $result,
		);
	}

	public static function activate_plugin_cb( $input ) {
		if ( ! self::is_enabled( 'plugins', 'write' ) ) {
			return self::get_disabled_error( 'Activate Plugin' );
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin = sanitize_text_field( $input['plugin'] );
		$result = activate_plugin( $plugin );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'plugin'  => $plugin,
			'message' => 'Plugin activated successfully',
		);
	}

	public static function deactivate_plugin_cb( $input ) {
		if ( ! self::is_enabled( 'plugins', 'write' ) ) {
			return self::get_disabled_error( 'Deactivate Plugin' );
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin = sanitize_text_field( $input['plugin'] );
		deactivate_plugins( $plugin );

		return array(
			'success' => true,
			'plugin'  => $plugin,
			'message' => 'Plugin deactivated successfully',
		);
	}

	/**
	 * ==================== USER ROLES & PERMISSIONS ABILITIES ====================
	 */

	private static function register_roles_abilities() {
		self::wmc_register(
			'wmc/get-roles',
			array(
				'label'       => 'Get User Roles',
				'description' => 'List all WordPress user roles with their capabilities',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'include_caps' => array(
							'type'        => 'boolean',
							'description' => 'Include full capabilities list for each role',
							'default'     => false,
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_roles' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/assign-role',
			array(
				'label'       => 'Assign Role',
				'description' => 'Assign a role to a WordPress user',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'user_id' => array(
							'type'        => 'integer',
							'description' => 'The user ID',
						),
						'role' => array(
							'type'        => 'string',
							'description' => 'Role slug (e.g. "editor", "author", "subscriber")',
						),
					),
					'required' => array( 'user_id', 'role' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'assign_role' ),
				'permission_callback' => fn() => current_user_can( 'edit_users' ),
			)
		);
	}

	public static function get_roles( $input ) {
		if ( ! self::is_enabled( 'roles', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		global $wp_roles;
		$include_caps = ! empty( $input['include_caps'] );
		$result       = array();

		foreach ( $wp_roles->roles as $slug => $role ) {
			$entry = array(
				'slug'  => $slug,
				'name'  => $role['name'],
				'count' => count_users()['avail_roles'][ $slug ] ?? 0,
			);
			if ( $include_caps ) {
				$entry['capabilities'] = array_keys( array_filter( $role['capabilities'] ) );
			}
			$result[] = $entry;
		}

		return array(
			'success' => true,
			'roles'   => $result,
		);
	}

	public static function assign_role( $input ) {
		if ( ! self::is_enabled( 'roles', 'write' ) ) {
			return self::get_disabled_error( 'Assign Role' );
		}

		$user = get_user_by( 'id', (int) $input['user_id'] );
		if ( ! $user ) {
			return array( 'success' => false, 'message' => 'User not found' );
		}

		global $wp_roles;
		$role = sanitize_key( $input['role'] );
		if ( ! isset( $wp_roles->roles[ $role ] ) ) {
			return array( 'success' => false, 'message' => 'Invalid role: ' . $role );
		}

		$user->set_role( $role );

		return array(
			'success'  => true,
			'user_id'  => $user->ID,
			'username' => $user->user_login,
			'role'     => $role,
			'message'  => 'Role assigned successfully',
		);
	}

	/**
	 * ==================== WOOCOMMERCE ABILITIES ====================
	 */

	private static function register_woocommerce_abilities() {
		self::wmc_register(
			'wmc/get-woo-products',
			array(
				'label'       => 'Get WooCommerce Products',
				'description' => 'List WooCommerce products (requires WooCommerce)',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
						'page'     => array( 'type' => 'integer', 'default' => 1 ),
						'status'   => array( 'type' => 'string', 'description' => 'publish, draft, etc.' ),
						'search'   => array( 'type' => 'string' ),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_woo_products' ),
				'permission_callback' => fn() => current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/create-woo-product',
			array(
				'label'       => 'Create WooCommerce Product',
				'description' => 'Create a new WooCommerce product',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'name'              => array( 'type' => 'string', 'description' => 'Product name' ),
						'description'       => array( 'type' => 'string' ),
						'short_description' => array( 'type' => 'string' ),
						'regular_price'     => array( 'type' => 'string', 'description' => 'Price (e.g. "29.99")' ),
						'sale_price'        => array( 'type' => 'string' ),
						'status'            => array( 'type' => 'string', 'default' => 'publish' ),
						'stock_quantity'    => array( 'type' => 'integer' ),
						'sku'               => array( 'type' => 'string' ),
					),
					'required' => array( 'name', 'regular_price' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'create_woo_product' ),
				'permission_callback' => fn() => current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/update-woo-product',
			array(
				'label'       => 'Update WooCommerce Product',
				'description' => 'Update an existing WooCommerce product',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'                => array( 'type' => 'integer', 'description' => 'Product ID' ),
						'name'              => array( 'type' => 'string' ),
						'description'       => array( 'type' => 'string' ),
						'short_description' => array( 'type' => 'string' ),
						'regular_price'     => array( 'type' => 'string' ),
						'sale_price'        => array( 'type' => 'string' ),
						'status'            => array( 'type' => 'string' ),
						'stock_quantity'    => array( 'type' => 'integer' ),
						'sku'               => array( 'type' => 'string' ),
					),
					'required' => array( 'id' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'update_woo_product' ),
				'permission_callback' => fn() => current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/get-woo-orders',
			array(
				'label'       => 'Get WooCommerce Orders',
				'description' => 'List WooCommerce orders with filtering',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
						'page'     => array( 'type' => 'integer', 'default' => 1 ),
						'status'   => array( 'type' => 'string', 'description' => 'pending, processing, completed, cancelled, refunded, failed' ),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_woo_orders' ),
				'permission_callback' => fn() => current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/update-woo-order-status',
			array(
				'label'       => 'Update WooCommerce Order Status',
				'description' => 'Change the status of a WooCommerce order',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'order_id' => array( 'type' => 'integer', 'description' => 'Order ID' ),
						'status'   => array( 'type' => 'string', 'description' => 'New status: pending, processing, completed, cancelled, refunded, failed' ),
						'note'     => array( 'type' => 'string', 'description' => 'Optional order note' ),
					),
					'required' => array( 'order_id', 'status' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'update_woo_order_status' ),
				'permission_callback' => fn() => current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/get-woo-customers',
			array(
				'label'       => 'Get WooCommerce Customers',
				'description' => 'List WooCommerce customers',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
						'page'     => array( 'type' => 'integer', 'default' => 1 ),
						'search'   => array( 'type' => 'string', 'description' => 'Search by name or email' ),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_woo_customers' ),
				'permission_callback' => fn() => current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ),
			)
		);
	}

	private static function woo_active() {
		return class_exists( 'WooCommerce' );
	}

	public static function get_woo_products( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => (int) ( $input['per_page'] ?? 20 ),
			'paged'          => (int) ( $input['page'] ?? 1 ),
			'post_status'    => $input['status'] ?? 'publish',
		);
		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$query    = new WP_Query( $args );
		$products = array();

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post->ID );
			if ( ! $product ) {
				continue;
			}
			$products[] = array(
				'id'            => $product->get_id(),
				'name'          => $product->get_name(),
				'sku'           => $product->get_sku(),
				'status'        => $product->get_status(),
				'regular_price' => $product->get_regular_price(),
				'sale_price'    => $product->get_sale_price(),
				'stock_qty'     => $product->get_stock_quantity(),
				'type'          => $product->get_type(),
				'url'           => get_permalink( $post->ID ),
			);
		}

		return array(
			'success'     => true,
			'count'       => count( $products ),
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'products'    => $products,
		);
	}

	public static function create_woo_product( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'write' ) ) {
			return self::get_disabled_error( 'Create' );
		}
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}

		$product = new WC_Product_Simple();
		$product->set_name( sanitize_text_field( $input['name'] ) );
		$product->set_status( $input['status'] ?? 'publish' );

		if ( isset( $input['description'] ) ) {
			$product->set_description( wp_kses_post( $input['description'] ) );
		}
		if ( isset( $input['short_description'] ) ) {
			$product->set_short_description( wp_kses_post( $input['short_description'] ) );
		}
		if ( isset( $input['regular_price'] ) ) {
			$product->set_regular_price( sanitize_text_field( $input['regular_price'] ) );
		}
		if ( isset( $input['sale_price'] ) ) {
			$product->set_sale_price( sanitize_text_field( $input['sale_price'] ) );
		}
		if ( isset( $input['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $input['sku'] ) );
		}
		if ( isset( $input['stock_quantity'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $input['stock_quantity'] );
		}

		$id = $product->save();

		return array(
			'success' => true,
			'id'      => $id,
			'name'    => $product->get_name(),
			'url'     => get_permalink( $id ),
			'message' => 'Product created successfully',
		);
	}

	public static function update_woo_product( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}

		$product = wc_get_product( (int) $input['id'] );
		if ( ! $product ) {
			return array( 'success' => false, 'message' => 'Product not found' );
		}

		if ( isset( $input['name'] ) )              $product->set_name( sanitize_text_field( $input['name'] ) );
		if ( isset( $input['description'] ) )       $product->set_description( wp_kses_post( $input['description'] ) );
		if ( isset( $input['short_description'] ) ) $product->set_short_description( wp_kses_post( $input['short_description'] ) );
		if ( isset( $input['regular_price'] ) )     $product->set_regular_price( sanitize_text_field( $input['regular_price'] ) );
		if ( isset( $input['sale_price'] ) )        $product->set_sale_price( sanitize_text_field( $input['sale_price'] ) );
		if ( isset( $input['status'] ) )            $product->set_status( $input['status'] );
		if ( isset( $input['sku'] ) )               $product->set_sku( sanitize_text_field( $input['sku'] ) );
		if ( isset( $input['stock_quantity'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $input['stock_quantity'] );
		}

		$product->save();

		return array(
			'success' => true,
			'id'      => $product->get_id(),
			'name'    => $product->get_name(),
			'message' => 'Product updated successfully',
		);
	}

	public static function get_woo_orders( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}

		$args = array(
			'limit'  => (int) ( $input['per_page'] ?? 20 ),
			'page'   => (int) ( $input['page'] ?? 1 ),
			'return' => 'objects',
		);
		if ( ! empty( $input['status'] ) ) {
			$args['status'] = sanitize_text_field( $input['status'] );
		}

		$orders = wc_get_orders( $args );
		$result = array();

		foreach ( $orders as $order ) {
			$result[] = array(
				'id'         => $order->get_id(),
				'status'     => $order->get_status(),
				'total'      => $order->get_total(),
				'currency'   => $order->get_currency(),
				'customer'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'email'      => $order->get_billing_email(),
				'date'       => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : null,
				'items_count'=> count( $order->get_items() ),
			);
		}

		return array(
			'success' => true,
			'count'   => count( $result ),
			'orders'  => $result,
		);
	}

	public static function update_woo_order_status( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'write' ) ) {
			return self::get_disabled_error( 'Update' );
		}
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}

		$order = wc_get_order( (int) $input['order_id'] );
		if ( ! $order ) {
			return array( 'success' => false, 'message' => 'Order not found' );
		}

		$valid_statuses = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
		$new_status     = sanitize_text_field( $input['status'] );

		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			return array( 'success' => false, 'message' => 'Invalid status. Use: ' . implode( ', ', $valid_statuses ) );
		}

		$order->update_status( $new_status, $input['note'] ?? '' );

		return array(
			'success'  => true,
			'order_id' => $order->get_id(),
			'status'   => $order->get_status(),
			'message'  => 'Order status updated successfully',
		);
	}

	public static function get_woo_customers( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}

		$args = array(
			'role'   => 'customer',
			'number' => (int) ( $input['per_page'] ?? 20 ),
			'paged'  => (int) ( $input['page'] ?? 1 ),
		);
		if ( ! empty( $input['search'] ) ) {
			$args['search']         = '*' . sanitize_text_field( $input['search'] ) . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$users  = get_users( $args );
		$result = array();

		foreach ( $users as $user ) {
			$result[] = array(
				'id'           => $user->ID,
				'name'         => $user->display_name,
				'email'        => $user->user_email,
				'registered'   => $user->user_registered,
				'orders_count' => wc_get_customer_order_count( $user->ID ),
				'total_spent'  => wc_get_customer_total_spent( $user->ID ),
			);
		}

		return array(
			'success'   => true,
			'count'     => count( $result ),
			'customers' => $result,
		);
	}

	/**
	 * ==================== SYSTEM / MAINTENANCE ABILITIES ====================
	 */

	private static function register_system_abilities() {
		self::wmc_register(
			'wmc/get-site-health',
			array(
				'label'       => 'Get Site Health',
				'description' => 'Retrieve WordPress site health information',
				'category'    => 'wp-content-manager',
				'input_schema'        => array( 'type' => 'object', 'properties' => array() ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_site_health' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/clear-cache',
			array(
				'label'       => 'Clear Cache',
				'description' => 'Clear WordPress object cache and common caching plugins',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'type' => array(
							'type'        => 'string',
							'description' => 'Cache type: all, object, transients',
							'enum'        => array( 'all', 'object', 'transients' ),
							'default'     => 'all',
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'clear_cache' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		self::wmc_register(
			'wmc/get-cron-jobs',
			array(
				'label'       => 'Get Cron Jobs',
				'description' => 'List all scheduled WordPress cron jobs',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'filter' => array(
							'type'        => 'string',
							'description' => 'Filter by hook name (partial match)',
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( self::class, 'get_cron_jobs' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_site_health( $input ) {
		if ( ! self::is_enabled( 'system', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		global $wpdb;

		$update_data = get_site_transient( 'update_core' );
		$wp_version  = get_bloginfo( 'version' );
		$latest_wp   = $update_data->updates[0]->version ?? $wp_version;

		return array(
			'success'        => true,
			'wordpress'      => array(
				'version'        => $wp_version,
				'latest'         => $latest_wp,
				'up_to_date'     => version_compare( $wp_version, $latest_wp, '>=' ),
				'multisite'      => is_multisite(),
				'debug_mode'     => defined( 'WP_DEBUG' ) && WP_DEBUG,
			),
			'php'            => array(
				'version'        => PHP_VERSION,
				'memory_limit'   => ini_get( 'memory_limit' ),
				'max_execution'  => ini_get( 'max_execution_time' ) . 's',
				'upload_max'     => ini_get( 'upload_max_filesize' ),
			),
			'database'       => array(
				'version'        => $wpdb->db_version(),
				'prefix'         => $wpdb->prefix,
				'charset'        => DB_CHARSET,
			),
			'server'         => array(
				'software'       => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
				'https'          => is_ssl(),
				'timezone'       => wp_timezone_string(),
			),
			'active_plugins' => count( get_option( 'active_plugins', array() ) ),
			'theme'          => wp_get_theme()->get( 'Name' ),
			'uploads_dir'    => wp_upload_dir()['basedir'],
		);
	}

	public static function clear_cache( $input ) {
		if ( ! self::is_enabled( 'system', 'write' ) ) {
			return self::get_disabled_error( 'Clear Cache' );
		}

		$type    = $input['type'] ?? 'all';
		$cleared = array();

		if ( in_array( $type, array( 'all', 'object' ), true ) ) {
			wp_cache_flush();
			$cleared[] = 'object_cache';
		}

		if ( in_array( $type, array( 'all', 'transients' ), true ) ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'" );
			$cleared[] = 'transients';
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			$cleared[] = 'w3tc';
		}

		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			$cleared[] = 'wp_super_cache';
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			$cleared[] = 'wp_rocket';
		}

		// LiteSpeed Cache
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			\LiteSpeed_Cache_API::purge_all();
			$cleared[] = 'litespeed';
		}

		return array(
			'success' => true,
			'cleared' => $cleared,
			'message' => 'Cache cleared: ' . implode( ', ', $cleared ),
		);
	}

	public static function get_cron_jobs( $input ) {
		if ( ! self::is_enabled( 'system', 'read' ) ) {
			return self::get_disabled_error( 'Read' );
		}

		$crons  = _get_cron_array();
		$filter = $input['filter'] ?? '';
		$result = array();

		if ( ! is_array( $crons ) ) {
			return array( 'success' => true, 'count' => 0, 'jobs' => array() );
		}

		foreach ( $crons as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events ) {
				if ( $filter && stripos( $hook, $filter ) === false ) {
					continue;
				}
				foreach ( $events as $key => $event ) {
					$result[] = array(
						'hook'      => $hook,
						'next_run'  => date( 'Y-m-d H:i:s', $timestamp ),
						'interval'  => $event['schedule'] ?: 'one-time',
						'args'      => $event['args'],
					);
				}
			}
		}

		usort( $result, fn( $a, $b ) => strcmp( $a['next_run'], $b['next_run'] ) );

		return array(
			'success' => true,
			'count'   => count( $result ),
			'jobs'    => $result,
		);
	}
}
