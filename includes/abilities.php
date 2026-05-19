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
	 * Register all abilities
	 */
	public static function register_all_abilities() {
		// Register ability category first
		self::register_category();

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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_posts' ),
			)
		);

		// Create Post
		wp_register_ability(
			'wmc/create-post',
			array(
				'label'       => 'Create Post',
				'description' => 'Create a new blog post with title, content, status, categories, and tags',
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
							'description' => 'Post status (publish, draft, pending)',
							'default'     => 'draft',
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
				'callback'            => array( self::class, 'create_post' ),
			)
		);

		// Update Post
		wp_register_ability(
			'wmc/update-post',
			array(
				'label'       => 'Update Post',
				'description' => 'Update an existing blog post',
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
							'description' => 'New post status',
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
				'callback'            => array( self::class, 'update_post' ),
			)
		);

		// Delete Post
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_post' ),
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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_pages' ),
			)
		);

		// Create Page
		wp_register_ability(
			'wmc/create-page',
			array(
				'label'       => 'Create Page',
				'description' => 'Create a new WordPress page',
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
							'description' => 'Page status (publish, draft)',
							'default'     => 'draft',
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
				'callback'            => array( self::class, 'create_page' ),
			)
		);

		// Update Page
		wp_register_ability(
			'wmc/update-page',
			array(
				'label'       => 'Update Page',
				'description' => 'Update an existing WordPress page',
				'category'    => 'wp-content-manager',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string' ),
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
				'callback'            => array( self::class, 'update_page' ),
			)
		);

		// Delete Page
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_page' ),
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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_categories' ),
			)
		);

		// Create Category
		wp_register_ability(
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
				'callback'            => array( self::class, 'create_category' ),
			)
		);

		// Update Category
		wp_register_ability(
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
				'callback'            => array( self::class, 'update_category' ),
			)
		);

		// Delete Category
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_category' ),
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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_tags' ),
			)
		);

		// Create Tag
		wp_register_ability(
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
				'callback'            => array( self::class, 'create_tag' ),
			)
		);

		// Update Tag
		wp_register_ability(
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
				'callback'            => array( self::class, 'update_tag' ),
			)
		);

		// Delete Tag
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_tag' ),
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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_media' ),
			)
		);

		// Create Media (Upload)
		wp_register_ability(
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
				'callback'            => array( self::class, 'create_media' ),
			)
		);

		// Update Media
		wp_register_ability(
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
				'callback'            => array( self::class, 'update_media' ),
			)
		);

		// Delete Media
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_media' ),
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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_comments' ),
			)
		);

		// Moderate Comment
		wp_register_ability(
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
				'callback'            => array( self::class, 'moderate_comment' ),
			)
		);

		// Delete Comment
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_comment' ),
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
		wp_register_ability(
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
				'callback'            => array( self::class, 'get_users' ),
			)
		);

		// Create User
		wp_register_ability(
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
				'callback'            => array( self::class, 'create_user' ),
			)
		);

		// Update User
		wp_register_ability(
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
				'callback'            => array( self::class, 'update_user' ),
			)
		);

		// Delete User
		wp_register_ability(
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
				'callback'            => array( self::class, 'delete_user' ),
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
			'post_status'    => array( 'publish', 'draft', 'pending' ),
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

		$post_data = array(
			'post_title'   => $input['title'],
			'post_content' => $input['content'] ?? '',
			'post_excerpt' => $input['excerpt'] ?? '',
			'post_status'  => $input['status'] ?? 'draft',
			'post_type'    => 'post',
		);

		$post_id = wp_insert_post( $post_data );

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

		return array(
			'id'      => $post_id,
			'title'   => $input['title'],
			'url'     => get_permalink( $post_id ),
			'status'  => $input['status'] ?? 'draft',
			'success' => true,
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

		$result = wp_update_post( $post_data );

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

		return array(
			'id'      => $input['id'],
			'success' => true,
			'message' => 'Post updated successfully',
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
			'post_status'    => array( 'publish', 'draft', 'pending' ),
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

		$page_data = array(
			'post_title'   => $input['title'],
			'post_content' => $input['content'] ?? '',
			'post_status'  => $input['status'] ?? 'draft',
			'post_type'    => 'page',
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			return array(
				'success' => false,
				'message' => $page_id->get_error_message(),
			);
		}

		return array(
			'id'      => $page_id,
			'title'   => $input['title'],
			'url'     => get_permalink( $page_id ),
			'success' => true,
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

		$result = wp_update_post( $page_data );

		if ( is_wp_error( $result ) ) {
			return array(
				'id'      => $input['id'],
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'id'      => $input['id'],
			'success' => true,
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
		wp_register_ability(
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
				'callback'         => array( self::class, 'get_options' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		wp_register_ability(
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
				'callback'         => array( self::class, 'update_option' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_options( $input ) {
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
		wp_register_ability(
			'wmc/get-menus',
			array(
				'label'       => 'Get Menus',
				'description' => 'Retrieve all registered menus and their items',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'callback'         => array( self::class, 'get_menus' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		wp_register_ability(
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
				'callback'         => array( self::class, 'create_menu' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		wp_register_ability(
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
				'callback'         => array( self::class, 'delete_menu' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_menus( $input ) {
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
		wp_register_ability(
			'wmc/get-sidebars',
			array(
				'label'       => 'Get Widget Areas',
				'description' => 'Retrieve all widget areas/sidebars and their widgets',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'callback'         => array( self::class, 'get_sidebars' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		wp_register_ability(
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
				'callback'         => array( self::class, 'update_widget_option' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_sidebars( $input ) {
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
		wp_register_ability(
			'wmc/get-themes',
			array(
				'label'       => 'Get Themes',
				'description' => 'Retrieve all installed themes and current theme info',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'callback'         => array( self::class, 'get_themes' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		wp_register_ability(
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
				'callback'         => array( self::class, 'activate_theme' ),
				'permission_callback' => fn() => current_user_can( 'switch_themes' ),
			)
		);

		wp_register_ability(
			'wmc/get-theme-mods',
			array(
				'label'       => 'Get Theme Settings',
				'description' => 'Retrieve current theme customization settings',
				'category'    => 'wp-content-manager',
				'output_schema' => array(
					'type' => 'object',
				),
				'callback'         => array( self::class, 'get_theme_mods' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);

		wp_register_ability(
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
				'callback'         => array( self::class, 'update_theme_mod' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	public static function get_themes( $input ) {
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

	public static function get_theme_mods( $input ) {
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
}
