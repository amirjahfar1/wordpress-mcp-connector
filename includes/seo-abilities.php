<?php
/**
 * SEO Abilities for WordPress MCP Connector
 * Covers: Meta Tags, Open Graph, Twitter Card, Schema, Sitemap,
 *         Canonical, Redirects, Focus Keywords, Robots, SEO Reports
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WMC_SEO_Abilities {

	private static function register( $name, $args ) {
		if ( ! function_exists( 'wp_register_ability' ) ) return null;
		if ( ! isset( $args['meta'] ) )        $args['meta']          = array();
		if ( ! isset( $args['meta']['mcp'] ) ) $args['meta']['mcp']   = array();
		$args['meta']['show_in_rest']   = true;
		$args['meta']['mcp']['public']  = true;
		return wp_register_ability( $name, $args );
	}

	private static function admin() {
		return current_user_can( 'manage_options' );
	}

	// ================================================================
	//  REGISTER ALL
	// ================================================================

	public static function register_all() {
		self::register_meta_abilities();
		self::register_social_abilities();
		self::register_schema_abilities();
		self::register_sitemap_abilities();
		self::register_redirect_abilities();
		self::register_keyword_abilities();
		self::register_robots_abilities();
		self::register_report_abilities();
	}

	// ================================================================
	//  DISABLED RESPONSE HELPER
	// ================================================================

	private static function is_enabled( $group, $operation ) {
		if ( class_exists( 'WMC_Settings' ) ) {
			return WMC_Settings::is_ability_enabled( $group, $operation );
		}
		return true;
	}

	private static function disabled( $label, $group, $op ) {
		$url = admin_url( 'admin.php?page=wmc-settings' );
		return array(
			'success'      => false,
			'disabled'     => true,
			'ability'      => $label,
			'message'      => "'{$label}' is currently DISABLED on your WordPress site.",
			'how_to_enable'=> "Go to: WordPress Admin → MCP Connector → Enable '{$label}' → Save Changes",
			'settings_url' => $url,
			'config_key'   => "{$group} → {$op}",
		);
	}

	// ================================================================
	//  DETECT ACTIVE SEO PLUGIN
	// ================================================================

	private static function detect_seo_plugin() {
		if ( defined( 'RANK_MATH_VERSION' ) )   return 'rankmath';
		if ( defined( 'WPSEO_VERSION' ) )        return 'yoast';
		if ( defined( 'AIOSEO_VERSION' ) )       return 'aioseo';
		return 'none';
	}

	private static function seo_meta_keys( $plugin = 'auto' ) {
		if ( $plugin === 'auto' ) $plugin = self::detect_seo_plugin();
		$map = array(
			'rankmath' => array(
				'title'    => 'rank_math_title',
				'desc'     => 'rank_math_description',
				'robots'   => 'rank_math_robots',
				'keyword'  => 'rank_math_focus_keyword',
				'canonical'=> 'rank_math_canonical_url',
				'og_title' => 'rank_math_facebook_title',
				'og_desc'  => 'rank_math_facebook_description',
				'og_image' => 'rank_math_facebook_image',
				'tw_title' => 'rank_math_twitter_title',
				'tw_desc'  => 'rank_math_twitter_description',
				'tw_image' => 'rank_math_twitter_image',
				'schema'   => 'rank_math_rich_snippet',
				'noindex'  => 'rank_math_robots',
				'sitemap_exclude' => 'rank_math_sitemap_exclude',
			),
			'yoast' => array(
				'title'    => '_yoast_wpseo_title',
				'desc'     => '_yoast_wpseo_metadesc',
				'robots'   => '_yoast_wpseo_meta-robots-noindex',
				'keyword'  => '_yoast_wpseo_focuskw',
				'canonical'=> '_yoast_wpseo_canonical',
				'og_title' => '_yoast_wpseo_opengraph-title',
				'og_desc'  => '_yoast_wpseo_opengraph-description',
				'og_image' => '_yoast_wpseo_opengraph-image',
				'tw_title' => '_yoast_wpseo_twitter-title',
				'tw_desc'  => '_yoast_wpseo_twitter-description',
				'tw_image' => '_yoast_wpseo_twitter-image',
				'schema'   => '_yoast_wpseo_schema_page_type',
				'noindex'  => '_yoast_wpseo_meta-robots-noindex',
				'sitemap_exclude' => '_yoast_wpseo_sitemap-exclude',
			),
			'aioseo' => array(
				'title'    => '_aioseo_title',
				'desc'     => '_aioseo_description',
				'robots'   => '_aioseo_robots_default',
				'keyword'  => '_aioseo_keyphrases',
				'canonical'=> '_aioseo_canonical_url',
				'og_title' => '_aioseo_og_title',
				'og_desc'  => '_aioseo_og_description',
				'og_image' => '_aioseo_og_image_custom_url',
				'tw_title' => '_aioseo_twitter_title',
				'tw_desc'  => '_aioseo_twitter_description',
				'tw_image' => '_aioseo_twitter_image_custom_url',
				'schema'   => '_aioseo_schema',
				'noindex'  => '_aioseo_robots_noindex',
				'sitemap_exclude' => '_aioseo_sitemap_exclude',
			),
			'none' => array(
				'title'    => '_wmc_seo_title',
				'desc'     => '_wmc_seo_description',
				'robots'   => '_wmc_seo_robots',
				'keyword'  => '_wmc_seo_keyword',
				'canonical'=> '_wmc_canonical_url',
				'og_title' => '_wmc_og_title',
				'og_desc'  => '_wmc_og_description',
				'og_image' => '_wmc_og_image',
				'tw_title' => '_wmc_tw_title',
				'tw_desc'  => '_wmc_tw_description',
				'tw_image' => '_wmc_tw_image',
				'schema'   => '_wmc_schema_markup',
				'noindex'  => '_wmc_seo_robots',
				'sitemap_exclude' => '_wmc_sitemap_exclude',
			),
		);
		return $map[ $plugin ] ?? $map['none'];
	}

	// ================================================================
	//  1. META TAG ABILITIES
	// ================================================================

	private static function register_meta_abilities() {

		// ---------- detect-seo-setup ----------
		self::register( 'wmc/detect-seo-setup', array(
			'label'               => 'Detect SEO Setup',
			'description'         => 'ALWAYS call this first before any SEO read/write operation. Returns which SEO plugin is active (Yoast/RankMath/AIOSEO/none), its version, supported content types, correct meta keys for posts and terms, and the recommended ability to use for each content type. Prevents trial-and-error when updating meta on posts, pages, products, categories, or tags.',
			'category'            => 'wp-content-manager',
			'input_schema'        => array( 'type' => 'object', 'properties' => array() ),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'detect_seo_setup' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- get-post-seo ----------
		self::register( 'wmc/get-post-seo', array(
			'label'       => 'Get Post SEO Data',
			'description' => 'Get full SEO data for a post/page: title, description, focus keyword, robots, canonical URL, Open Graph, Twitter Card',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'plugin'  => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_post_seo' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- set-post-seo ----------
		self::register( 'wmc/set-post-seo', array(
			'label'       => 'Set Post SEO Data',
			'description' => 'Set SEO title, description, focus keyword, canonical, robots for a post/page (auto-detects SEO plugin)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'         => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'seo_title'       => array( 'type' => 'string',  'description' => 'SEO meta title' ),
					'seo_description' => array( 'type' => 'string',  'description' => 'SEO meta description' ),
					'focus_keyword'   => array( 'type' => 'string',  'description' => 'Focus/target keyword' ),
					'canonical_url'   => array( 'type' => 'string',  'description' => 'Canonical URL (leave empty to use default)' ),
					'robots'          => array( 'type' => 'string',  'description' => 'Robots directive: index, noindex, follow, nofollow (comma separated)' ),
					'plugin'          => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_post_seo' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- get-term-seo ----------
		self::register( 'wmc/get-term-seo', array(
			'label'       => 'Get Term SEO Data',
			'description' => 'Get SEO title and description for a category, tag, or product category/tag',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'term_id'  => array( 'type' => 'integer', 'description' => 'Term ID (category, tag, product_cat, product_tag, etc.)' ),
					'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug: category, post_tag, product_cat, product_tag. Default: category', 'default' => 'category' ),
					'plugin'   => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'term_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_term_seo' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- set-term-seo ----------
		self::register( 'wmc/set-term-seo', array(
			'label'       => 'Set Term SEO Data',
			'description' => 'Set SEO title, description, and focus keyword for a category, tag, or product category/tag (Yoast, Rank Math, AIOSEO)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'term_id'         => array( 'type' => 'integer', 'description' => 'Term ID (category, tag, product_cat, product_tag, etc.)' ),
					'taxonomy'        => array( 'type' => 'string',  'description' => 'Taxonomy slug: category, post_tag, product_cat, product_tag. Default: category', 'default' => 'category' ),
					'seo_title'       => array( 'type' => 'string',  'description' => 'SEO meta title' ),
					'seo_description' => array( 'type' => 'string',  'description' => 'SEO meta description' ),
					'focus_keyword'   => array( 'type' => 'string',  'description' => 'Focus/target keyword' ),
					'plugin'          => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'term_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_term_seo' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- get-seo-audit ----------
		self::register( 'wmc/get-seo-audit', array(
			'label'       => 'Get SEO Audit',
			'description' => 'Run an SEO health check on a post: title length, description length, keyword density, headings, image alt tags, internal links',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'plugin'  => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_seo_audit' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  2. OPEN GRAPH & TWITTER CARD ABILITIES
	// ================================================================

	private static function register_social_abilities() {

		// ---------- set-open-graph ----------
		self::register( 'wmc/set-open-graph', array(
			'label'       => 'Set Open Graph Tags',
			'description' => 'Set Facebook/WhatsApp Open Graph title, description, and image for a post/page',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'    => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'og_title'   => array( 'type' => 'string',  'description' => 'Open Graph title' ),
					'og_description' => array( 'type' => 'string', 'description' => 'Open Graph description' ),
					'og_image'   => array( 'type' => 'string',  'description' => 'Open Graph image URL' ),
					'plugin'     => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_open_graph' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- set-twitter-card ----------
		self::register( 'wmc/set-twitter-card', array(
			'label'       => 'Set Twitter/X Card',
			'description' => 'Set Twitter/X Card title, description, and image for a post/page',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'          => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'twitter_title'    => array( 'type' => 'string',  'description' => 'Twitter card title' ),
					'twitter_description' => array( 'type' => 'string', 'description' => 'Twitter card description' ),
					'twitter_image'    => array( 'type' => 'string',  'description' => 'Twitter card image URL' ),
					'card_type'        => array( 'type' => 'string',  'enum' => array( 'summary', 'summary_large_image' ), 'default' => 'summary_large_image' ),
					'plugin'           => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_twitter_card' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  3. SCHEMA MARKUP ABILITIES
	// ================================================================

	private static function register_schema_abilities() {

		// ---------- add-schema-markup ----------
		self::register( 'wmc/add-schema-markup', array(
			'label'       => 'Add Schema Markup',
			'description' => 'Add JSON-LD schema markup to a post/page (Article, Product, FAQ, HowTo, LocalBusiness, BreadcrumbList, etc.)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'     => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'schema_type' => array(
						'type'        => 'string',
						'description' => 'Schema type: Article, BlogPosting, Product, FAQPage, HowTo, LocalBusiness, Organization, WebPage, Event, Recipe, Review, BreadcrumbList, or "custom"',
					),
					'schema_data' => array(
						'type'        => 'object',
						'description' => 'Schema properties as key-value pairs. For FAQ: {"questions":[{"q":"...","a":"..."}]}. For HowTo: {"steps":["step1","step2"]}. For custom: provide full JSON-LD object.',
					),
				),
				'required' => array( 'post_id', 'schema_type' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'add_schema_markup' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- get-schema-markup ----------
		self::register( 'wmc/get-schema-markup', array(
			'label'       => 'Get Schema Markup',
			'description' => 'Get the current JSON-LD schema markup saved for a post/page',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_schema_markup' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  4. SITEMAP ABILITIES
	// ================================================================

	private static function register_sitemap_abilities() {

		// ---------- get-sitemap-urls ----------
		self::register( 'wmc/get-sitemap-urls', array(
			'label'       => 'Get Sitemap URLs',
			'description' => 'List all URLs registered in the WordPress sitemap (via SEO plugin or core sitemap)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_type' => array( 'type' => 'string', 'description' => 'Filter by post type (post, page, product, etc.). Empty = all.', 'default' => '' ),
					'limit'     => array( 'type' => 'integer', 'default' => 100 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_sitemap_urls' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- ping-search-engines ----------
		self::register( 'wmc/ping-search-engines', array(
			'label'       => 'Ping Search Engines',
			'description' => 'Send sitemap ping to Google and Bing to request re-indexing',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'sitemap_url' => array( 'type' => 'string', 'description' => 'Custom sitemap URL (leave empty to auto-detect)' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'ping_search_engines' ),
			'permission_callback' => fn() => self::admin(),
		) );

		// ---------- exclude-from-sitemap ----------
		self::register( 'wmc/exclude-from-sitemap', array(
			'label'       => 'Exclude Post from Sitemap',
			'description' => 'Include or exclude a specific post/page from the XML sitemap',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'exclude' => array( 'type' => 'boolean', 'description' => 'true = exclude from sitemap, false = include', 'default' => true ),
					'plugin'  => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'exclude_from_sitemap' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  5. CANONICAL & REDIRECTS ABILITIES
	// ================================================================

	private static function register_redirect_abilities() {

		// ---------- set-canonical-url ----------
		self::register( 'wmc/set-canonical-url', array(
			'label'       => 'Set Canonical URL',
			'description' => 'Set or clear the canonical URL for a post/page to prevent duplicate content issues',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'       => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'canonical_url' => array( 'type' => 'string',  'description' => 'Canonical URL. Leave empty to remove/reset.' ),
					'plugin'        => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_canonical_url' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- manage-redirects ----------
		self::register( 'wmc/manage-redirects', array(
			'label'       => 'Manage Redirects',
			'description' => 'Add, list, or delete 301/302 redirects stored in WordPress options',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'action'      => array( 'type' => 'string', 'enum' => array( 'list', 'add', 'delete' ), 'description' => 'Action to perform' ),
					'from'        => array( 'type' => 'string', 'description' => 'Source path (e.g. /old-page/) — required for add/delete' ),
					'to'          => array( 'type' => 'string', 'description' => 'Destination URL — required for add' ),
					'type'        => array( 'type' => 'integer', 'enum' => array( 301, 302 ), 'default' => 301, 'description' => 'Redirect type' ),
				),
				'required' => array( 'action' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'manage_redirects' ),
			'permission_callback' => fn() => self::admin(),
		) );
	}

	// ================================================================
	//  6. FOCUS KEYWORD ABILITIES
	// ================================================================

	private static function register_keyword_abilities() {

		// ---------- set-focus-keyword ----------
		self::register( 'wmc/set-focus-keyword', array(
			'label'       => 'Set Focus Keyword',
			'description' => 'Set the focus/target keyword for a post/page in Rank Math, Yoast, or AIOSEO',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'keyword' => array( 'type' => 'string',  'description' => 'Focus keyword or keyphrase' ),
					'plugin'  => array( 'type' => 'string',  'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id', 'keyword' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_focus_keyword' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- bulk-set-focus-keywords ----------
		self::register( 'wmc/bulk-set-focus-keywords', array(
			'label'       => 'Bulk Set Focus Keywords',
			'description' => 'Set focus keywords for multiple posts at once',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'items' => array(
						'type'  => 'array',
						'description' => '[{"post_id": 1, "keyword": "my keyword"}, ...]',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'post_id' => array( 'type' => 'integer' ),
								'keyword' => array( 'type' => 'string' ),
							),
						),
					),
					'plugin' => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'items' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'bulk_set_focus_keywords' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  7. ROBOTS / NOINDEX ABILITIES
	// ================================================================

	private static function register_robots_abilities() {

		// ---------- set-post-robots ----------
		self::register( 'wmc/set-post-robots', array(
			'label'       => 'Set Post Robots (noindex/nofollow)',
			'description' => 'Set robots directives for a single post/page (noindex, nofollow, noarchive, etc.)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Post or page ID' ),
					'noindex' => array( 'type' => 'boolean', 'description' => 'true = noindex, false = index', 'default' => false ),
					'nofollow'=> array( 'type' => 'boolean', 'description' => 'true = nofollow, false = follow', 'default' => false ),
					'noarchive'=> array( 'type' => 'boolean', 'description' => 'true = noarchive', 'default' => false ),
					'plugin'  => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_post_robots' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- bulk-set-robots ----------
		self::register( 'wmc/bulk-set-robots', array(
			'label'       => 'Bulk Set Robots (noindex/nofollow)',
			'description' => 'Set noindex/nofollow for multiple posts at once',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'List of post IDs' ),
					'noindex'  => array( 'type' => 'boolean', 'default' => false ),
					'nofollow' => array( 'type' => 'boolean', 'default' => false ),
					'plugin'   => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
				'required' => array( 'post_ids' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'bulk_set_robots' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  8. SEO REPORT ABILITIES
	// ================================================================

	private static function register_report_abilities() {

		// ---------- seo-overview-report ----------
		self::register( 'wmc/seo-overview-report', array(
			'label'       => 'SEO Overview Report',
			'description' => 'Get a full SEO status report for all posts: which ones are missing title, description, keyword, or have duplicate meta',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_type' => array( 'type' => 'string', 'default' => 'post', 'description' => 'post, page, product, etc.' ),
					'limit'     => array( 'type' => 'integer', 'default' => 100 ),
					'plugin'    => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'seo_overview_report' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );

		// ---------- posts-missing-seo ----------
		self::register( 'wmc/posts-missing-seo', array(
			'label'       => 'Posts Missing SEO',
			'description' => 'List posts/pages that are missing SEO title, description, or focus keyword',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_type' => array( 'type' => 'string', 'default' => 'post' ),
					'missing'   => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string', 'enum' => array( 'title', 'description', 'keyword' ) ),
						'description' => 'Which fields to check. Default: all three.',
						'default' => array( 'title', 'description', 'keyword' ),
					),
					'limit'     => array( 'type' => 'integer', 'default' => 50 ),
					'plugin'    => array( 'type' => 'string', 'enum' => array( 'auto', 'yoast', 'rankmath', 'aioseo', 'none' ), 'default' => 'auto' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'posts_missing_seo' ),
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		) );
	}

	// ================================================================
	//  CALLBACKS — META
	// ================================================================

	public static function detect_seo_setup( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'meta' ) ) return self::disabled( 'Detect SEO Setup', 'seo_adv', 'meta' );

		$plugin  = self::detect_seo_plugin();
		$version = '';
		$keys    = self::seo_meta_keys( $plugin );

		if ( $plugin === 'yoast' && defined( 'WPSEO_VERSION' ) ) {
			$version = WPSEO_VERSION;
		} elseif ( $plugin === 'rankmath' && defined( 'RANK_MATH_VERSION' ) ) {
			$version = RANK_MATH_VERSION;
		} elseif ( $plugin === 'aioseo' && defined( 'AIOSEO_VERSION' ) ) {
			$version = AIOSEO_VERSION;
		}

		// Term meta keys per plugin
		$term_keys_map = array(
			'yoast'    => array( 'title' => 'wpseo_title',        'desc' => 'wpseo_desc',             'keyword' => 'wpseo_focuskw' ),
			'rankmath' => array( 'title' => 'rank_math_title',    'desc' => 'rank_math_description',  'keyword' => 'rank_math_focus_keyword' ),
			'aioseo'   => array( 'title' => '_aioseo_title',      'desc' => '_aioseo_description',    'keyword' => '_aioseo_keyphrases' ),
			'none'     => array( 'title' => '_wmc_seo_title',     'desc' => '_wmc_seo_description',   'keyword' => '_wmc_seo_keyword' ),
		);
		$term_keys = $term_keys_map[ $plugin ] ?? $term_keys_map['none'];

		// Check if term meta is REST-exposed
		$term_rest_exposed = false;
		$rest_routes       = rest_get_server()->get_routes();
		foreach ( array_keys( $rest_routes ) as $route ) {
			if ( strpos( $route, 'categories' ) !== false || strpos( $route, 'tags' ) !== false ) {
				$term_rest_exposed = true;
				break;
			}
		}

		return array(
			'success'            => true,
			'seo_plugin'         => $plugin,
			'version'            => $version,
			'supported_types'    => array(
				'posts'     => array(
					'supported'          => true,
					'recommended_ability'=> 'wmc/set-post-seo',
					'meta_key_title'     => $keys['title'],
					'meta_key_desc'      => $keys['desc'],
					'meta_key_keyword'   => $keys['keyword'],
					'storage'            => 'post_meta',
				),
				'pages'     => array(
					'supported'          => true,
					'recommended_ability'=> 'wmc/set-post-seo',
					'meta_key_title'     => $keys['title'],
					'meta_key_desc'      => $keys['desc'],
					'storage'            => 'post_meta',
				),
				'products'  => array(
					'supported'          => true,
					'recommended_ability'=> 'wmc/set-post-seo',
					'note'               => 'WooCommerce products are posts — use post_id with wmc/set-post-seo',
					'meta_key_title'     => $keys['title'],
					'meta_key_desc'      => $keys['desc'],
					'storage'            => 'post_meta',
				),
				'terms'     => array(
					'supported'          => true,
					'recommended_ability'=> 'wmc/set-term-seo',
					'note'               => 'Works for category, post_tag, product_cat, product_tag. Use term_id + taxonomy.',
					'meta_key_title'     => $term_keys['title'],
					'meta_key_desc'      => $term_keys['desc'],
					'meta_key_keyword'   => $term_keys['keyword'],
					'storage'            => 'term_meta',
					'rest_exposed'       => $term_rest_exposed,
				),
			),
			'do_not_use'         => array(
				'wp_get_seo_meta on terms' => 'Does not work — terms use term_meta not post_meta',
				'REST /wp-json/yoast/v1 on terms' => $plugin === 'yoast' ? 'Yoast does not expose term meta via REST — use wmc/set-term-seo directly' : 'N/A',
			),
			'instruction'        => 'Use wmc/set-post-seo for posts/pages/products. Use wmc/set-term-seo for all taxonomy terms. Never use post_meta functions on terms.',
		);
	}

	public static function get_term_seo( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'term_meta' ) ) return self::disabled( 'Get / Set Term SEO', 'seo_adv', 'term_meta' );
		$term_id  = intval( $params['term_id'] ?? 0 );
		$taxonomy = sanitize_text_field( $params['taxonomy'] ?? 'category' );
		$term     = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) return array( 'success' => false, 'message' => 'Term not found.' );

		$plugin = $params['plugin'] ?? 'auto';
		$active = $plugin === 'auto' ? self::detect_seo_plugin() : $plugin;

		$title   = '';
		$desc    = '';
		$keyword = '';

		if ( $active === 'yoast' ) {
			// Yoast stores ALL term meta in a single wp_options entry
			$tax_meta = get_option( 'wpseo_taxonomy_meta', array() );
			$entry    = $tax_meta[ $taxonomy ][ $term_id ] ?? array();
			$title    = $entry['wpseo_title']   ?? '';
			$desc     = $entry['wpseo_desc']    ?? '';
			$keyword  = $entry['wpseo_focuskw'] ?? '';
		} elseif ( $active === 'rankmath' ) {
			$title   = get_term_meta( $term_id, 'rank_math_title', true );
			$desc    = get_term_meta( $term_id, 'rank_math_description', true );
			$keyword = get_term_meta( $term_id, 'rank_math_focus_keyword', true );
		} elseif ( $active === 'aioseo' ) {
			$title   = get_term_meta( $term_id, '_aioseo_title', true );
			$desc    = get_term_meta( $term_id, '_aioseo_description', true );
			$keyword = get_term_meta( $term_id, '_aioseo_keyphrases', true );
		} else {
			$title   = get_term_meta( $term_id, '_wmc_seo_title', true );
			$desc    = get_term_meta( $term_id, '_wmc_seo_description', true );
			$keyword = get_term_meta( $term_id, '_wmc_seo_keyword', true );
		}

		return array(
			'success'         => true,
			'term_id'         => $term_id,
			'taxonomy'        => $taxonomy,
			'name'            => $term->name,
			'slug'            => $term->slug,
			'seo_plugin'      => $active,
			'seo_title'       => $title,
			'seo_description' => $desc,
			'focus_keyword'   => $keyword,
		);
	}

	public static function set_term_seo( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'term_meta' ) ) return self::disabled( 'Get / Set Term SEO', 'seo_adv', 'term_meta' );
		$term_id  = intval( $params['term_id'] ?? 0 );
		$taxonomy = sanitize_text_field( $params['taxonomy'] ?? 'category' );
		$term     = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) return array( 'success' => false, 'message' => 'Term not found.' );

		$plugin  = $params['plugin'] ?? 'auto';
		$active  = $plugin === 'auto' ? self::detect_seo_plugin() : $plugin;
		$updated = array();

		if ( $active === 'yoast' ) {
			// Yoast stores ALL term meta in a single wp_options entry — NOT in term_meta table
			$tax_meta = get_option( 'wpseo_taxonomy_meta', array() );
			if ( ! isset( $tax_meta[ $taxonomy ] ) ) $tax_meta[ $taxonomy ] = array();
			if ( ! isset( $tax_meta[ $taxonomy ][ $term_id ] ) ) $tax_meta[ $taxonomy ][ $term_id ] = array();

			if ( isset( $params['seo_title'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['wpseo_title']   = sanitize_text_field( $params['seo_title'] );
				$updated[] = 'seo_title';
			}
			if ( isset( $params['seo_description'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['wpseo_desc']    = sanitize_text_field( $params['seo_description'] );
				$updated[] = 'seo_description';
			}
			if ( isset( $params['focus_keyword'] ) ) {
				$tax_meta[ $taxonomy ][ $term_id ]['wpseo_focuskw'] = sanitize_text_field( $params['focus_keyword'] );
				$updated[] = 'focus_keyword';
			}
			update_option( 'wpseo_taxonomy_meta', $tax_meta );

		} elseif ( $active === 'rankmath' ) {
			if ( isset( $params['seo_title'] ) )       { update_term_meta( $term_id, 'rank_math_title',          sanitize_text_field( $params['seo_title'] ) );       $updated[] = 'seo_title'; }
			if ( isset( $params['seo_description'] ) ) { update_term_meta( $term_id, 'rank_math_description',    sanitize_text_field( $params['seo_description'] ) ); $updated[] = 'seo_description'; }
			if ( isset( $params['focus_keyword'] ) )   { update_term_meta( $term_id, 'rank_math_focus_keyword',  sanitize_text_field( $params['focus_keyword'] ) );   $updated[] = 'focus_keyword'; }

		} elseif ( $active === 'aioseo' ) {
			if ( isset( $params['seo_title'] ) )       { update_term_meta( $term_id, '_aioseo_title',       sanitize_text_field( $params['seo_title'] ) );       $updated[] = 'seo_title'; }
			if ( isset( $params['seo_description'] ) ) { update_term_meta( $term_id, '_aioseo_description', sanitize_text_field( $params['seo_description'] ) ); $updated[] = 'seo_description'; }
			if ( isset( $params['focus_keyword'] ) )   { update_term_meta( $term_id, '_aioseo_keyphrases',  sanitize_text_field( $params['focus_keyword'] ) );   $updated[] = 'focus_keyword'; }

		} else {
			if ( isset( $params['seo_title'] ) )       { update_term_meta( $term_id, '_wmc_seo_title',       sanitize_text_field( $params['seo_title'] ) );       $updated[] = 'seo_title'; }
			if ( isset( $params['seo_description'] ) ) { update_term_meta( $term_id, '_wmc_seo_description', sanitize_text_field( $params['seo_description'] ) ); $updated[] = 'seo_description'; }
			if ( isset( $params['focus_keyword'] ) )   { update_term_meta( $term_id, '_wmc_seo_keyword',     sanitize_text_field( $params['focus_keyword'] ) );   $updated[] = 'focus_keyword'; }
		}

		return array(
			'success'    => true,
			'term_id'    => $term_id,
			'taxonomy'   => $taxonomy,
			'name'       => $term->name,
			'seo_plugin' => $active,
			'updated'    => $updated,
			'message'    => 'Term SEO updated for ' . count( $updated ) . ' field(s).',
		);
	}

	public static function get_post_seo( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'meta' ) ) return self::disabled( 'Get / Set / Audit Post SEO', 'seo_adv', 'meta' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$plugin = $params['plugin'] ?? 'auto';
		$keys   = self::seo_meta_keys( $plugin );
		$active = $plugin === 'auto' ? self::detect_seo_plugin() : $plugin;

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'title'          => $post->post_title,
			'permalink'      => get_permalink( $post_id ),
			'seo_plugin'     => $active,
			'seo_title'      => get_post_meta( $post_id, $keys['title'], true ),
			'seo_description'=> get_post_meta( $post_id, $keys['desc'], true ),
			'focus_keyword'  => get_post_meta( $post_id, $keys['keyword'], true ),
			'canonical_url'  => get_post_meta( $post_id, $keys['canonical'], true ),
			'robots'         => get_post_meta( $post_id, $keys['robots'], true ),
			'og_title'       => get_post_meta( $post_id, $keys['og_title'], true ),
			'og_description' => get_post_meta( $post_id, $keys['og_desc'], true ),
			'og_image'       => get_post_meta( $post_id, $keys['og_image'], true ),
			'twitter_title'  => get_post_meta( $post_id, $keys['tw_title'], true ),
			'twitter_desc'   => get_post_meta( $post_id, $keys['tw_desc'], true ),
			'twitter_image'  => get_post_meta( $post_id, $keys['tw_image'], true ),
			'schema'         => get_post_meta( $post_id, $keys['schema'], true ),
			'sitemap_exclude'=> get_post_meta( $post_id, $keys['sitemap_exclude'], true ),
		);
	}

	public static function set_post_seo( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'meta' ) ) return self::disabled( 'Get / Set / Audit Post SEO', 'seo_adv', 'meta' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$plugin  = $params['plugin'] ?? 'auto';
		$keys    = self::seo_meta_keys( $plugin );
		$updated = array();

		$fields = array(
			'seo_title'       => 'title',
			'seo_description' => 'desc',
			'focus_keyword'   => 'keyword',
			'canonical_url'   => 'canonical',
			'robots'          => 'robots',
		);

		foreach ( $fields as $param_key => $meta_key ) {
			if ( isset( $params[ $param_key ] ) ) {
				update_post_meta( $post_id, $keys[ $meta_key ], sanitize_text_field( $params[ $param_key ] ) );
				$updated[] = $param_key;
			}
		}

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'seo_plugin' => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'updated'    => $updated,
			'message'    => 'SEO data updated for ' . count( $updated ) . ' field(s).',
		);
	}

	public static function get_seo_audit( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'meta' ) ) return self::disabled( 'Get / Set / Audit Post SEO', 'seo_adv', 'meta' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$plugin  = $params['plugin'] ?? 'auto';
		$keys    = self::seo_meta_keys( $plugin );
		$content = wp_strip_all_tags( $post->post_content );
		$words   = str_word_count( $content );

		$seo_title = get_post_meta( $post_id, $keys['title'], true ) ?: $post->post_title;
		$seo_desc  = get_post_meta( $post_id, $keys['desc'], true );
		$keyword   = get_post_meta( $post_id, $keys['keyword'], true );

		$title_len = mb_strlen( $seo_title );
		$desc_len  = mb_strlen( $seo_desc );

		// Count headings
		preg_match_all( '/<h[1-6][^>]*>/i', $post->post_content, $h_matches );
		$heading_count = count( $h_matches[0] );

		// Count images and images without alt
		preg_match_all( '/<img[^>]+>/i', $post->post_content, $img_matches );
		$total_images   = count( $img_matches[0] );
		$no_alt_images  = 0;
		foreach ( $img_matches[0] as $img ) {
			if ( ! preg_match( '/alt=["\'][^"\']+["\']/', $img ) ) {
				$no_alt_images++;
			}
		}

		// Count internal links
		$site_host = parse_url( get_home_url(), PHP_URL_HOST );
		preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $link_matches );
		$internal_links = 0;
		foreach ( $link_matches[1] as $href ) {
			$parsed = parse_url( $href, PHP_URL_HOST );
			if ( ! $parsed || $parsed === $site_host ) $internal_links++;
		}

		// Keyword density
		$keyword_density = 0;
		if ( $keyword && $words > 0 ) {
			$count = substr_count( strtolower( $content ), strtolower( $keyword ) );
			$keyword_density = round( ( $count / $words ) * 100, 2 );
		}

		// Build checks
		$checks = array(
			array( 'check' => 'SEO Title exists',          'pass' => ! empty( $seo_title ),   'value' => $seo_title ?: '(missing)' ),
			array( 'check' => 'SEO Title length (50-60)',   'pass' => $title_len >= 30 && $title_len <= 60, 'value' => $title_len . ' chars' ),
			array( 'check' => 'Meta Description exists',   'pass' => ! empty( $seo_desc ),    'value' => $seo_desc ?: '(missing)' ),
			array( 'check' => 'Meta Desc length (120-160)', 'pass' => $desc_len >= 100 && $desc_len <= 160, 'value' => $desc_len . ' chars' ),
			array( 'check' => 'Focus Keyword set',         'pass' => ! empty( $keyword ),     'value' => $keyword ?: '(missing)' ),
			array( 'check' => 'Keyword in title',          'pass' => $keyword && stripos( $seo_title, $keyword ) !== false, 'value' => $keyword ? 'yes/no' : 'n/a' ),
			array( 'check' => 'Keyword density (1-3%)',    'pass' => $keyword_density >= 1 && $keyword_density <= 3, 'value' => $keyword_density . '%' ),
			array( 'check' => 'Content word count (>300)', 'pass' => $words >= 300,           'value' => $words . ' words' ),
			array( 'check' => 'Has headings (H2/H3)',      'pass' => $heading_count > 0,      'value' => $heading_count . ' headings' ),
			array( 'check' => 'Images have alt text',      'pass' => $no_alt_images === 0,    'value' => $no_alt_images . ' images missing alt' ),
			array( 'check' => 'Has internal links',        'pass' => $internal_links > 0,     'value' => $internal_links . ' internal links' ),
		);

		$passed = count( array_filter( $checks, fn( $c ) => $c['pass'] ) );
		$score  = round( ( $passed / count( $checks ) ) * 100 );

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'title'          => $post->post_title,
			'seo_plugin'     => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'score'          => $score . '%',
			'passed'         => $passed,
			'total_checks'   => count( $checks ),
			'checks'         => $checks,
			'word_count'     => $words,
			'keyword_density'=> $keyword_density . '%',
			'images'         => array( 'total' => $total_images, 'missing_alt' => $no_alt_images ),
			'internal_links' => $internal_links,
		);
	}

	// ================================================================
	//  CALLBACKS — SOCIAL
	// ================================================================

	public static function set_open_graph( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'social' ) ) return self::disabled( 'Open Graph & Twitter/X Card', 'seo_adv', 'social' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$plugin  = $params['plugin'] ?? 'auto';
		$keys    = self::seo_meta_keys( $plugin );
		$updated = array();

		if ( isset( $params['og_title'] ) ) {
			update_post_meta( $post_id, $keys['og_title'], sanitize_text_field( $params['og_title'] ) );
			$updated[] = 'og_title';
		}
		if ( isset( $params['og_description'] ) ) {
			update_post_meta( $post_id, $keys['og_desc'], sanitize_textarea_field( $params['og_description'] ) );
			$updated[] = 'og_description';
		}
		if ( isset( $params['og_image'] ) ) {
			update_post_meta( $post_id, $keys['og_image'], esc_url_raw( $params['og_image'] ) );
			$updated[] = 'og_image';
		}

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'seo_plugin' => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'updated'    => $updated,
			'message'    => 'Open Graph tags updated.',
		);
	}

	public static function set_twitter_card( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'social' ) ) return self::disabled( 'Open Graph & Twitter/X Card', 'seo_adv', 'social' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$plugin  = $params['plugin'] ?? 'auto';
		$keys    = self::seo_meta_keys( $plugin );
		$updated = array();

		if ( isset( $params['twitter_title'] ) ) {
			update_post_meta( $post_id, $keys['tw_title'], sanitize_text_field( $params['twitter_title'] ) );
			$updated[] = 'twitter_title';
		}
		if ( isset( $params['twitter_description'] ) ) {
			update_post_meta( $post_id, $keys['tw_desc'], sanitize_textarea_field( $params['twitter_description'] ) );
			$updated[] = 'twitter_description';
		}
		if ( isset( $params['twitter_image'] ) ) {
			update_post_meta( $post_id, $keys['tw_image'], esc_url_raw( $params['twitter_image'] ) );
			$updated[] = 'twitter_image';
		}
		if ( isset( $params['card_type'] ) ) {
			update_post_meta( $post_id, '_wmc_tw_card_type', sanitize_text_field( $params['card_type'] ) );
			$updated[] = 'card_type';
		}

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'seo_plugin' => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'updated'    => $updated,
			'message'    => 'Twitter/X Card tags updated.',
		);
	}

	// ================================================================
	//  CALLBACKS — SCHEMA
	// ================================================================

	public static function add_schema_markup( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'schema' ) ) return self::disabled( 'Schema Markup (JSON-LD)', 'seo_adv', 'schema' );
		$post_id     = intval( $params['post_id'] ?? 0 );
		$schema_type = sanitize_text_field( $params['schema_type'] ?? '' );
		$schema_data = $params['schema_data'] ?? array();
		$post        = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$schema = self::build_schema( $schema_type, $schema_data, $post );
		if ( is_wp_error( $schema ) ) return array( 'success' => false, 'message' => $schema->get_error_message() );

		update_post_meta( $post_id, '_wmc_schema_markup', wp_json_encode( $schema ) );

		return array(
			'success'     => true,
			'post_id'     => $post_id,
			'schema_type' => $schema_type,
			'schema_json' => $schema,
			'message'     => 'Schema markup saved. It will be output in <head> via wp_head.',
		);
	}

	private static function build_schema( $type, $data, $post ) {
		$url   = get_permalink( $post->ID );
		$title = $post->post_title;
		$date  = $post->post_date;
		$mod   = $post->post_modified;

		switch ( strtolower( $type ) ) {
			case 'article':
			case 'bloggingpost':
				return array(
					'@context'         => 'https://schema.org',
					'@type'            => 'Article',
					'headline'         => $data['headline'] ?? $title,
					'datePublished'    => $date,
					'dateModified'     => $mod,
					'url'              => $url,
					'author'           => is_array( $data['author'] ?? null )
						? array( '@type' => 'Person', 'name' => $data['author']['name'] ?? get_bloginfo( 'name' ) )
						: array( '@type' => 'Person', 'name' => $data['author'] ?? get_bloginfo( 'name' ) ),
					'publisher'        => is_array( $data['publisher'] ?? null )
						? array( '@type' => 'Organization', 'name' => $data['publisher']['name'] ?? get_bloginfo( 'name' ), 'url' => $data['publisher']['url'] ?? get_home_url() )
						: array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => get_home_url() ),
					'description'      => $data['description'] ?? wp_trim_words( $post->post_content, 30 ),
				);

			case 'faqpage':
			case 'faq':
				$questions = $data['questions'] ?? array();
				$entries   = array();
				foreach ( $questions as $q ) {
					$entries[] = array(
						'@type'          => 'Question',
						'name'           => $q['q'] ?? $q['question'] ?? '',
						'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q['a'] ?? $q['answer'] ?? '' ),
					);
				}
				return array(
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => $entries,
				);

			case 'howto':
				$steps = $data['steps'] ?? array();
				$step_objs = array();
				foreach ( $steps as $i => $step ) {
					$step_objs[] = array( '@type' => 'HowToStep', 'name' => 'Step ' . ( $i + 1 ), 'text' => $step );
				}
				return array(
					'@context'    => 'https://schema.org',
					'@type'       => 'HowTo',
					'name'        => $data['name'] ?? $title,
					'description' => $data['description'] ?? '',
					'step'        => $step_objs,
				);

			case 'localbusiness':
				return array(
					'@context'    => 'https://schema.org',
					'@type'       => 'LocalBusiness',
					'name'        => $data['name'] ?? get_bloginfo( 'name' ),
					'url'         => $url,
					'telephone'   => $data['telephone'] ?? '',
					'address'     => array(
						'@type'           => 'PostalAddress',
						'streetAddress'   => $data['street'] ?? '',
						'addressLocality' => $data['city'] ?? '',
						'addressRegion'   => $data['region'] ?? '',
						'postalCode'      => $data['zip'] ?? '',
						'addressCountry'  => $data['country'] ?? '',
					),
					'openingHours'=> $data['hours'] ?? '',
				);

			case 'product':
				return array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Product',
					'name'        => $data['name'] ?? $title,
					'description' => $data['description'] ?? '',
					'image'       => $data['image'] ?? '',
					'brand'       => array( '@type' => 'Brand', 'name' => $data['brand'] ?? '' ),
					'offers'      => array(
						'@type'         => 'Offer',
						'price'         => $data['price'] ?? '',
						'priceCurrency' => $data['currency'] ?? 'USD',
						'availability'  => 'https://schema.org/' . ( $data['availability'] ?? 'InStock' ),
					),
				);

			case 'breadcrumblist':
				$items = $data['items'] ?? array();
				$list  = array();
				foreach ( $items as $i => $item ) {
					$list[] = array(
						'@type'    => 'ListItem',
						'position' => $i + 1,
						'name'     => $item['name'] ?? '',
						'item'     => $item['url'] ?? '',
					);
				}
				return array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => $list,
				);

			case 'webpage':
			case 'webPage':
				return array(
					'@context'    => 'https://schema.org',
					'@type'       => 'WebPage',
					'name'        => $data['name'] ?? $title,
					'url'         => $url,
					'description' => $data['description'] ?? '',
					'datePublished' => $date,
					'dateModified'  => $mod,
				);

			case 'custom':
				if ( ! empty( $data ) ) {
					if ( ! isset( $data['@context'] ) ) $data['@context'] = 'https://schema.org';
					return $data;
				}
				return new WP_Error( 'missing_data', 'For custom schema, provide schema_data with the full JSON-LD object.' );

			default:
				return array(
					'@context' => 'https://schema.org',
					'@type'    => $type,
					'name'     => $data['name'] ?? $title,
					'url'      => $url,
				) + (array) $data;
		}
	}

	public static function get_schema_markup( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'schema' ) ) return self::disabled( 'Schema Markup (JSON-LD)', 'seo_adv', 'schema' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$raw    = get_post_meta( $post_id, '_wmc_schema_markup', true );
		$parsed = $raw ? json_decode( $raw, true ) : null;

		return array(
			'success'     => true,
			'post_id'     => $post_id,
			'has_schema'  => ! empty( $raw ),
			'schema_json' => $parsed,
			'schema_raw'  => $raw,
		);
	}

	// ================================================================
	//  CALLBACKS — SITEMAP
	// ================================================================

	public static function get_sitemap_urls( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'sitemap' ) ) return self::disabled( 'Sitemap Management', 'seo_adv', 'sitemap' );
		$post_type = sanitize_key( $params['post_type'] ?? '' );
		$limit     = intval( $params['limit'] ?? 100 );

		$args = array(
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
		);
		if ( $post_type ) {
			$args['post_type'] = $post_type;
		} else {
			$args['post_type'] = get_post_types( array( 'public' => true ) );
		}

		$ids  = get_posts( $args );
		$urls = array();
		foreach ( $ids as $id ) {
			$urls[] = array(
				'id'        => $id,
				'url'       => get_permalink( $id ),
				'title'     => get_the_title( $id ),
				'post_type' => get_post_type( $id ),
				'modified'  => get_post_modified_time( 'Y-m-d', false, $id ),
			);
		}

		$sitemap_url = get_home_url() . '/wp-sitemap.xml';

		return array(
			'success'     => true,
			'total'       => count( $urls ),
			'sitemap_xml' => $sitemap_url,
			'urls'        => $urls,
		);
	}

	public static function ping_search_engines( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'sitemap' ) ) return self::disabled( 'Sitemap Management', 'seo_adv', 'sitemap' );
		$sitemap = esc_url_raw( $params['sitemap_url'] ?? '' );
		if ( ! $sitemap ) {
			$sitemap = get_home_url() . '/wp-sitemap.xml';
		}

		$results = array();

		$engines = array(
			'Google' => 'https://www.google.com/ping?sitemap=' . urlencode( $sitemap ),
			'Bing'   => 'https://www.bing.com/ping?sitemap=' . urlencode( $sitemap ),
		);

		foreach ( $engines as $name => $ping_url ) {
			$response = wp_remote_get( $ping_url, array( 'timeout' => 15 ) );
			if ( is_wp_error( $response ) ) {
				$results[ $name ] = array( 'success' => false, 'error' => $response->get_error_message() );
			} else {
				$code             = wp_remote_retrieve_response_code( $response );
				$results[ $name ] = array( 'success' => $code === 200, 'http_code' => $code );
			}
		}

		return array(
			'success'     => true,
			'sitemap_url' => $sitemap,
			'results'     => $results,
			'message'     => 'Ping requests sent to search engines.',
		);
	}

	public static function exclude_from_sitemap( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'sitemap' ) ) return self::disabled( 'Sitemap Management', 'seo_adv', 'sitemap' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$exclude = (bool) ( $params['exclude'] ?? true );
		$plugin  = $params['plugin'] ?? 'auto';
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$keys = self::seo_meta_keys( $plugin );
		update_post_meta( $post_id, $keys['sitemap_exclude'], $exclude ? '1' : '0' );

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'excluded'   => $exclude,
			'seo_plugin' => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'message'    => $exclude ? 'Post excluded from sitemap.' : 'Post included in sitemap.',
		);
	}

	// ================================================================
	//  CALLBACKS — CANONICAL & REDIRECTS
	// ================================================================

	public static function set_canonical_url( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'canonical' ) ) return self::disabled( 'Canonical URL & Redirects', 'seo_adv', 'canonical' );
		$post_id       = intval( $params['post_id'] ?? 0 );
		$canonical_url = esc_url_raw( $params['canonical_url'] ?? '' );
		$plugin        = $params['plugin'] ?? 'auto';
		$post          = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$keys = self::seo_meta_keys( $plugin );

		if ( $canonical_url ) {
			update_post_meta( $post_id, $keys['canonical'], $canonical_url );
			$action = 'set';
		} else {
			delete_post_meta( $post_id, $keys['canonical'] );
			$action = 'cleared';
		}

		return array(
			'success'       => true,
			'post_id'       => $post_id,
			'canonical_url' => $canonical_url ?: '(default: ' . get_permalink( $post_id ) . ')',
			'action'        => $action,
			'seo_plugin'    => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'message'       => 'Canonical URL ' . $action . '.',
		);
	}

	public static function manage_redirects( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'canonical' ) ) return self::disabled( 'Canonical URL & Redirects', 'seo_adv', 'canonical' );
		$action = sanitize_key( $params['action'] ?? '' );
		$from   = sanitize_text_field( $params['from'] ?? '' );
		$to     = esc_url_raw( $params['to'] ?? '' );
		$type   = intval( $params['type'] ?? 301 );

		$redirects = get_option( 'wmc_redirects', array() );

		if ( $action === 'list' ) {
			return array( 'success' => true, 'count' => count( $redirects ), 'redirects' => $redirects );
		}

		if ( $action === 'add' ) {
			if ( ! $from || ! $to ) return array( 'success' => false, 'message' => '"from" and "to" are required for add.' );
			$from = '/' . ltrim( $from, '/' );
			$redirects[ $from ] = array( 'to' => $to, 'type' => $type );
			update_option( 'wmc_redirects', $redirects, false );
			return array( 'success' => true, 'message' => "Redirect added: $from → $to ($type)", 'total' => count( $redirects ) );
		}

		if ( $action === 'delete' ) {
			if ( ! $from ) return array( 'success' => false, 'message' => '"from" is required for delete.' );
			$from = '/' . ltrim( $from, '/' );
			if ( ! isset( $redirects[ $from ] ) ) return array( 'success' => false, 'message' => 'Redirect not found.' );
			unset( $redirects[ $from ] );
			update_option( 'wmc_redirects', $redirects, false );
			return array( 'success' => true, 'message' => "Redirect deleted: $from", 'total' => count( $redirects ) );
		}

		return array( 'success' => false, 'message' => 'Unknown action. Use list, add, or delete.' );
	}

	// ================================================================
	//  CALLBACKS — KEYWORDS
	// ================================================================

	public static function set_focus_keyword( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'keywords' ) ) return self::disabled( 'Focus Keywords (Bulk)', 'seo_adv', 'keywords' );
		$post_id = intval( $params['post_id'] ?? 0 );
		$keyword = sanitize_text_field( $params['keyword'] ?? '' );
		$plugin  = $params['plugin'] ?? 'auto';
		$post    = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );
		if ( ! $keyword ) return array( 'success' => false, 'message' => 'Keyword cannot be empty.' );

		$keys = self::seo_meta_keys( $plugin );
		update_post_meta( $post_id, $keys['keyword'], $keyword );

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'keyword'    => $keyword,
			'seo_plugin' => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'message'    => 'Focus keyword set.',
		);
	}

	public static function bulk_set_focus_keywords( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'keywords' ) ) return self::disabled( 'Focus Keywords (Bulk)', 'seo_adv', 'keywords' );
		$items  = $params['items'] ?? array();
		$plugin = $params['plugin'] ?? 'auto';
		$keys   = self::seo_meta_keys( $plugin );
		$results = array();

		foreach ( $items as $item ) {
			$post_id = intval( $item['post_id'] ?? 0 );
			$keyword = sanitize_text_field( $item['keyword'] ?? '' );
			if ( ! $post_id || ! $keyword ) {
				$results[] = array( 'post_id' => $post_id, 'success' => false, 'message' => 'Invalid post_id or keyword.' );
				continue;
			}
			$post = get_post( $post_id );
			if ( ! $post ) {
				$results[] = array( 'post_id' => $post_id, 'success' => false, 'message' => 'Post not found.' );
				continue;
			}
			update_post_meta( $post_id, $keys['keyword'], $keyword );
			$results[] = array( 'post_id' => $post_id, 'keyword' => $keyword, 'success' => true );
		}

		$ok   = count( array_filter( $results, fn( $r ) => $r['success'] ) );
		$fail = count( $results ) - $ok;

		return array(
			'success' => true,
			'updated' => $ok,
			'failed'  => $fail,
			'results' => $results,
		);
	}

	// ================================================================
	//  CALLBACKS — ROBOTS
	// ================================================================

	public static function set_post_robots( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'robots' ) ) return self::disabled( 'Robots (noindex / nofollow)', 'seo_adv', 'robots' );
		$post_id   = intval( $params['post_id'] ?? 0 );
		$noindex   = (bool) ( $params['noindex'] ?? false );
		$nofollow  = (bool) ( $params['nofollow'] ?? false );
		$noarchive = (bool) ( $params['noarchive'] ?? false );
		$plugin    = $params['plugin'] ?? 'auto';
		$post      = get_post( $post_id );
		if ( ! $post ) return array( 'success' => false, 'message' => 'Post not found.' );

		$directives = array();
		if ( $noindex )   $directives[] = 'noindex';
		if ( ! $noindex ) $directives[] = 'index';
		if ( $nofollow )  $directives[] = 'nofollow';
		if ( ! $nofollow )$directives[] = 'follow';
		if ( $noarchive ) $directives[] = 'noarchive';

		$keys        = self::seo_meta_keys( $plugin );
		$robots_str  = implode( ',', $directives );

		$active = $plugin === 'auto' ? self::detect_seo_plugin() : $plugin;

		if ( $active === 'yoast' ) {
			update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', $noindex ? '1' : '0' );
			update_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', $nofollow ? '1' : '0' );
		} elseif ( $active === 'rankmath' ) {
			update_post_meta( $post_id, 'rank_math_robots', $directives );
		} else {
			update_post_meta( $post_id, $keys['robots'], $robots_str );
		}

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'robots'     => $directives,
			'seo_plugin' => $active,
			'message'    => 'Robots directives set: ' . $robots_str,
		);
	}

	public static function bulk_set_robots( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'robots' ) ) return self::disabled( 'Robots (noindex / nofollow)', 'seo_adv', 'robots' );
		$post_ids  = array_map( 'intval', $params['post_ids'] ?? array() );
		$noindex   = (bool) ( $params['noindex'] ?? false );
		$nofollow  = (bool) ( $params['nofollow'] ?? false );
		$plugin    = $params['plugin'] ?? 'auto';
		$results   = array();

		foreach ( $post_ids as $post_id ) {
			$r = self::set_post_robots( array(
				'post_id'  => $post_id,
				'noindex'  => $noindex,
				'nofollow' => $nofollow,
				'plugin'   => $plugin,
			) );
			$results[] = array( 'post_id' => $post_id, 'success' => $r['success'] );
		}

		$ok = count( array_filter( $results, fn( $r ) => $r['success'] ) );

		return array(
			'success' => true,
			'updated' => $ok,
			'failed'  => count( $results ) - $ok,
			'results' => $results,
		);
	}

	// ================================================================
	//  CALLBACKS — REPORTS
	// ================================================================

	public static function seo_overview_report( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'reports' ) ) return self::disabled( 'SEO Reports & Audit', 'seo_adv', 'reports' );
		$post_type = sanitize_key( $params['post_type'] ?? 'post' );
		$limit     = intval( $params['limit'] ?? 100 );
		$plugin    = $params['plugin'] ?? 'auto';
		$keys      = self::seo_meta_keys( $plugin );

		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'all',
		) );

		$report     = array();
		$seo_titles = array();

		foreach ( $posts as $post ) {
			$id        = $post->ID;
			$seo_title = get_post_meta( $id, $keys['title'], true );
			$seo_desc  = get_post_meta( $id, $keys['desc'], true );
			$keyword   = get_post_meta( $id, $keys['keyword'], true );

			$issues = array();
			if ( empty( $seo_title ) )  $issues[] = 'missing_title';
			if ( empty( $seo_desc ) )   $issues[] = 'missing_description';
			if ( empty( $keyword ) )    $issues[] = 'missing_keyword';
			if ( mb_strlen( $seo_title ) > 60 ) $issues[] = 'title_too_long';
			if ( mb_strlen( $seo_title ) < 30 && ! empty( $seo_title ) ) $issues[] = 'title_too_short';
			if ( mb_strlen( $seo_desc ) > 160 ) $issues[] = 'description_too_long';

			// Check duplicate titles
			if ( $seo_title ) {
				if ( in_array( $seo_title, $seo_titles, true ) ) {
					$issues[] = 'duplicate_title';
				}
				$seo_titles[] = $seo_title;
			}

			$report[] = array(
				'post_id'         => $id,
				'title'           => $post->post_title,
				'url'             => get_permalink( $id ),
				'seo_title'       => $seo_title ?: null,
				'seo_description' => $seo_desc ?: null,
				'focus_keyword'   => $keyword ?: null,
				'issues'          => $issues,
				'score'           => empty( $issues ) ? 'good' : ( count( $issues ) <= 2 ? 'needs_improvement' : 'poor' ),
			);
		}

		$good   = count( array_filter( $report, fn( $r ) => $r['score'] === 'good' ) );
		$medium = count( array_filter( $report, fn( $r ) => $r['score'] === 'needs_improvement' ) );
		$poor   = count( array_filter( $report, fn( $r ) => $r['score'] === 'poor' ) );

		return array(
			'success'    => true,
			'seo_plugin' => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'total'      => count( $report ),
			'summary'    => array( 'good' => $good, 'needs_improvement' => $medium, 'poor' => $poor ),
			'posts'      => $report,
		);
	}

	public static function posts_missing_seo( $params ) {
		if ( ! self::is_enabled( 'seo_adv', 'reports' ) ) return self::disabled( 'SEO Reports & Audit', 'seo_adv', 'reports' );
		$post_type   = sanitize_key( $params['post_type'] ?? 'post' );
		$limit       = intval( $params['limit'] ?? 50 );
		$plugin      = $params['plugin'] ?? 'auto';
		$missing_req = $params['missing'] ?? array( 'title', 'description', 'keyword' );
		$keys        = self::seo_meta_keys( $plugin );

		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'all',
		) );

		$missing_posts = array();

		foreach ( $posts as $post ) {
			$id         = $post->ID;
			$seo_title  = get_post_meta( $id, $keys['title'], true );
			$seo_desc   = get_post_meta( $id, $keys['desc'], true );
			$keyword    = get_post_meta( $id, $keys['keyword'], true );

			$missing = array();
			if ( in_array( 'title', $missing_req, true ) && empty( $seo_title ) )       $missing[] = 'title';
			if ( in_array( 'description', $missing_req, true ) && empty( $seo_desc ) )  $missing[] = 'description';
			if ( in_array( 'keyword', $missing_req, true ) && empty( $keyword ) )       $missing[] = 'keyword';

			if ( ! empty( $missing ) ) {
				$missing_posts[] = array(
					'post_id' => $id,
					'title'   => $post->post_title,
					'url'     => get_permalink( $id ),
					'missing' => $missing,
				);
			}
		}

		return array(
			'success'       => true,
			'seo_plugin'    => $plugin === 'auto' ? self::detect_seo_plugin() : $plugin,
			'total_checked' => count( $posts ),
			'missing_count' => count( $missing_posts ),
			'posts'         => $missing_posts,
		);
	}
}

// ================================================================
//  OUTPUT SCHEMA MARKUP IN <head>
// ================================================================
add_action( 'wp_head', function() {
	if ( ! is_singular() ) return;
	$post_id = get_the_ID();
	if ( ! $post_id ) return;
	$schema = get_post_meta( $post_id, '_wmc_schema_markup', true );
	if ( empty( $schema ) ) return;
	echo '<script type="application/ld+json">' . wp_json_encode( json_decode( $schema ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
} );

// ================================================================
//  APPLY WMC REDIRECTS
// ================================================================
add_action( 'template_redirect', function() {
	$redirects = get_option( 'wmc_redirects', array() );
	if ( empty( $redirects ) ) return;
	$current = '/' . ltrim( $_SERVER['REQUEST_URI'] ?? '', '/' );
	$current = strtok( $current, '?' );
	if ( isset( $redirects[ $current ] ) ) {
		$r = $redirects[ $current ];
		wp_redirect( $r['to'], $r['type'] ?? 301 );
		exit;
	}
} );
