<?php
/**
 * WooCommerce Extended Abilities for WordPress MCP Connector
 * Adds full WooCommerce control: Products, Orders, Coupons, Reviews, Shipping, Tax, Reports
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WMC_WooCommerce_Extended {

	private static function woo_active() {
		return class_exists( 'WooCommerce' );
	}

	private static function woo_check() {
		if ( ! self::woo_active() ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not installed or active' );
		}
		return null;
	}

	private static function register( $name, $args ) {
		if ( ! function_exists( 'wp_register_ability' ) ) return null;
		if ( ! isset( $args['meta'] ) ) $args['meta'] = array();
		$args['meta']['show_in_rest'] = true;
		if ( ! isset( $args['meta']['mcp'] ) ) $args['meta']['mcp'] = array();
		$args['meta']['mcp']['public'] = true;
		return wp_register_ability( $name, $args );
	}

	private static function perm() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	// ==================== REGISTER ALL ====================

	public static function register_all() {
		self::register_product_abilities();
		self::register_order_abilities();
		self::register_coupon_abilities();
		self::register_review_abilities();
		self::register_shipping_abilities();
		self::register_tax_abilities();
		self::register_report_abilities();
		self::register_customer_abilities();
		self::register_settings_abilities();
	}

	// ==================== PRODUCTS ====================

	private static function register_product_abilities() {

		self::register( 'wmc/get-woo-product-detail', array(
			'label'       => 'Get WooCommerce Product Detail',
			'description' => 'Get full details of a single product including images, categories, tags, attributes, meta',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array( 'type' => 'integer', 'description' => 'Product ID' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_product_detail' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-product', array(
			'label'       => 'Delete WooCommerce Product',
			'description' => 'Permanently delete a WooCommerce product',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id'    => array( 'type' => 'integer', 'description' => 'Product ID' ),
					'force' => array( 'type' => 'boolean', 'description' => 'Skip trash and delete permanently', 'default' => true ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_product' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/set-woo-product-image', array(
			'label'       => 'Set WooCommerce Product Image',
			'description' => 'Set featured image and gallery images for a product using media IDs',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id'               => array( 'type' => 'integer', 'description' => 'Product ID' ),
					'featured_image_id' => array( 'type' => 'integer', 'description' => 'Media ID for featured image' ),
					'gallery_ids'      => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Array of media IDs for gallery' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'set_product_image' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-product-categories', array(
			'label'       => 'Get WooCommerce Product Categories',
			'description' => 'List all product categories with hierarchy',
			'category'    => 'wp-content-manager',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_product_categories' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-product-category', array(
			'label'       => 'Create WooCommerce Product Category',
			'description' => 'Create a new product category',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'name'        => array( 'type' => 'string', 'description' => 'Category name' ),
					'description' => array( 'type' => 'string' ),
					'parent'      => array( 'type' => 'integer', 'description' => 'Parent category ID (0 for top-level)' ),
					'image_id'    => array( 'type' => 'integer', 'description' => 'Media ID for category image' ),
				),
				'required' => array( 'name' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_product_category' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/update-woo-product-category', array(
			'label'       => 'Update WooCommerce Product Category',
			'description' => 'Update a product category',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id'          => array( 'type' => 'integer', 'description' => 'Category ID' ),
					'name'        => array( 'type' => 'string' ),
					'description' => array( 'type' => 'string' ),
					'parent'      => array( 'type' => 'integer' ),
					'image_id'    => array( 'type' => 'integer' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_product_category' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-product-category', array(
			'label'       => 'Delete WooCommerce Product Category',
			'description' => 'Delete a product category',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array( 'type' => 'integer', 'description' => 'Category ID' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_product_category' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-product-tags', array(
			'label'       => 'Get WooCommerce Product Tags',
			'description' => 'List all product tags',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'per_page'   => array( 'type' => 'integer', 'default' => 50 ),
					'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
					'search'     => array( 'type' => 'string' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_product_tags' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-product-tag', array(
			'label'       => 'Create WooCommerce Product Tag',
			'description' => 'Create a new product tag',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'name'        => array( 'type' => 'string' ),
					'description' => array( 'type' => 'string' ),
				),
				'required' => array( 'name' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_product_tag' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-product-tag', array(
			'label'       => 'Delete WooCommerce Product Tag',
			'description' => 'Delete a product tag',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array( 'type' => 'integer' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_product_tag' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-product-meta', array(
			'label'       => 'Get WooCommerce Product Meta',
			'description' => 'Get custom meta fields of a product (ACF, custom fields, etc.)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id'       => array( 'type' => 'integer', 'description' => 'Product ID' ),
					'meta_key' => array( 'type' => 'string', 'description' => 'Specific meta key (optional — omit to get all)' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_product_meta' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/update-woo-product-meta', array(
			'label'       => 'Update WooCommerce Product Meta',
			'description' => 'Set or update a custom meta field on a product',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id'         => array( 'type' => 'integer', 'description' => 'Product ID' ),
					'meta_key'   => array( 'type' => 'string', 'description' => 'Meta key' ),
					'meta_value' => array( 'description' => 'Meta value (string, number, or object)' ),
				),
				'required' => array( 'id', 'meta_key', 'meta_value' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_product_meta' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-variable-product', array(
			'label'       => 'Create Variable WooCommerce Product',
			'description' => 'Create a variable product with attributes and variations (e.g. Size, Color)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'name'              => array( 'type' => 'string' ),
					'description'       => array( 'type' => 'string' ),
					'short_description' => array( 'type' => 'string' ),
					'status'            => array( 'type' => 'string', 'default' => 'publish' ),
					'sku'               => array( 'type' => 'string' ),
					'category_ids'      => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					'attributes'        => array(
						'type' => 'array',
						'description' => 'e.g. [{"name":"Color","options":["Red","Blue"],"visible":true,"variation":true}]',
						'items' => array( 'type' => 'object' ),
					),
					'variations' => array(
						'type' => 'array',
						'description' => 'e.g. [{"attributes":{"Color":"Red"},"regular_price":"29.99","stock_quantity":10,"sku":"SKU-RED"}]',
						'items' => array( 'type' => 'object' ),
					),
				),
				'required' => array( 'name', 'attributes' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_variable_product' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-variations', array(
			'label'       => 'Get Product Variations',
			'description' => 'List all variations of a variable product',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'product_id' => array( 'type' => 'integer', 'description' => 'Variable product ID' ),
				),
				'required' => array( 'product_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_variations' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/update-woo-variation', array(
			'label'       => 'Update Product Variation',
			'description' => 'Update price, stock, SKU, or status of a specific variation',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'product_id'    => array( 'type' => 'integer' ),
					'variation_id'  => array( 'type' => 'integer' ),
					'regular_price' => array( 'type' => 'string' ),
					'sale_price'    => array( 'type' => 'string' ),
					'stock_quantity'=> array( 'type' => 'integer' ),
					'sku'           => array( 'type' => 'string' ),
					'status'        => array( 'type' => 'string' ),
				),
				'required' => array( 'product_id', 'variation_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_variation' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-variation', array(
			'label'       => 'Delete Product Variation',
			'description' => 'Delete a specific variation from a variable product',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'product_id'   => array( 'type' => 'integer' ),
					'variation_id' => array( 'type' => 'integer' ),
				),
				'required' => array( 'product_id', 'variation_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_variation' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/assign-woo-product-categories', array(
			'label'       => 'Assign Categories to Product',
			'description' => 'Set or append categories for a product. Pass append:true to add to existing categories instead of replacing them.',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'product_id'   => array( 'type' => 'integer' ),
					'category_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					'append'       => array( 'type' => 'boolean', 'description' => 'If true, add to existing categories instead of replacing. Default: false.' ),
				),
				'required' => array( 'product_id', 'category_ids' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'assign_product_categories' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-low-stock', array(
			'label'       => 'Get Low Stock Products',
			'description' => 'List products that are low in stock or out of stock',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'threshold' => array( 'type' => 'integer', 'description' => 'Stock threshold (default: 5)', 'default' => 5 ),
					'per_page'  => array( 'type' => 'integer', 'default' => 50 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_low_stock' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== ORDERS ====================

	private static function register_order_abilities() {

		self::register( 'wmc/get-woo-order-detail', array(
			'label'       => 'Get WooCommerce Order Detail',
			'description' => 'Get full details of a single order including items, billing, shipping, notes',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'order_id' => array( 'type' => 'integer', 'description' => 'Order ID' ),
				),
				'required' => array( 'order_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_order_detail' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/add-woo-order-note', array(
			'label'       => 'Add WooCommerce Order Note',
			'description' => 'Add a note to an order (customer-visible or private)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'order_id'      => array( 'type' => 'integer' ),
					'note'          => array( 'type' => 'string', 'description' => 'Note text' ),
					'customer_note' => array( 'type' => 'boolean', 'description' => 'True = visible to customer', 'default' => false ),
				),
				'required' => array( 'order_id', 'note' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'add_order_note' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-order', array(
			'label'       => 'Create WooCommerce Order',
			'description' => 'Manually create a new WooCommerce order',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'status'            => array( 'type' => 'string', 'default' => 'pending' ),
					'customer_id'       => array( 'type' => 'integer', 'description' => '0 for guest order' ),
					'billing_first_name'=> array( 'type' => 'string' ),
					'billing_last_name' => array( 'type' => 'string' ),
					'billing_email'     => array( 'type' => 'string' ),
					'billing_phone'     => array( 'type' => 'string' ),
					'billing_address_1' => array( 'type' => 'string' ),
					'billing_city'      => array( 'type' => 'string' ),
					'billing_country'   => array( 'type' => 'string' ),
					'line_items'        => array(
						'type' => 'array',
						'description' => '[{"product_id":123,"quantity":2},{"variation_id":456,"quantity":1}]',
						'items' => array( 'type' => 'object' ),
					),
					'payment_method'    => array( 'type' => 'string', 'description' => 'e.g. bacs, cheque, cod' ),
					'note'              => array( 'type' => 'string' ),
				),
				'required' => array( 'line_items' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_order' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-order', array(
			'label'       => 'Delete WooCommerce Order',
			'description' => 'Permanently delete a WooCommerce order',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'order_id' => array( 'type' => 'integer' ),
					'force'    => array( 'type' => 'boolean', 'default' => true, 'description' => 'Skip trash' ),
				),
				'required' => array( 'order_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_order' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-refund', array(
			'label'       => 'Create WooCommerce Refund',
			'description' => 'Refund an order partially or fully',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'order_id' => array( 'type' => 'integer' ),
					'amount'   => array( 'type' => 'string', 'description' => 'Refund amount (e.g. "29.99"). Omit for full refund.' ),
					'reason'   => array( 'type' => 'string' ),
				),
				'required' => array( 'order_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_refund' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-order-notes', array(
			'label'       => 'Get WooCommerce Order Notes',
			'description' => 'Get all notes for a specific order',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'order_id' => array( 'type' => 'integer' ),
				),
				'required' => array( 'order_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_order_notes' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-customer-detail', array(
			'label'       => 'Get WooCommerce Customer Detail',
			'description' => 'Get full details of a customer including order history and total spent',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'customer_id' => array( 'type' => 'integer', 'description' => 'WordPress user ID' ),
				),
				'required' => array( 'customer_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_customer_detail' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== COUPONS ====================

	private static function register_coupon_abilities() {

		self::register( 'wmc/get-woo-coupons', array(
			'label'       => 'Get WooCommerce Coupons',
			'description' => 'List all coupons with details',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'per_page' => array( 'type' => 'integer', 'default' => 20 ),
					'page'     => array( 'type' => 'integer', 'default' => 1 ),
					'search'   => array( 'type' => 'string', 'description' => 'Search by coupon code' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_coupons' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-coupon', array(
			'label'       => 'Create WooCommerce Coupon',
			'description' => 'Create a discount coupon (percentage, fixed cart, free shipping)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'code'                => array( 'type' => 'string', 'description' => 'Coupon code' ),
					'discount_type'       => array( 'type' => 'string', 'enum' => array( 'percent', 'fixed_cart', 'fixed_product' ), 'default' => 'percent' ),
					'amount'              => array( 'type' => 'string', 'description' => 'Discount amount (e.g. "20" for 20%)' ),
					'description'         => array( 'type' => 'string' ),
					'expiry_date'         => array( 'type' => 'string', 'description' => 'Expiry date YYYY-MM-DD' ),
					'usage_limit'         => array( 'type' => 'integer', 'description' => 'Max times coupon can be used' ),
					'usage_limit_per_user'=> array( 'type' => 'integer', 'description' => 'Max uses per customer' ),
					'minimum_amount'      => array( 'type' => 'string', 'description' => 'Minimum order amount' ),
					'maximum_amount'      => array( 'type' => 'string', 'description' => 'Maximum order amount' ),
					'product_ids'         => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Restrict to product IDs' ),
					'exclude_product_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					'free_shipping'       => array( 'type' => 'boolean', 'default' => false ),
					'individual_use'      => array( 'type' => 'boolean', 'default' => false ),
				),
				'required' => array( 'code', 'discount_type', 'amount' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_coupon' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/update-woo-coupon', array(
			'label'       => 'Update WooCommerce Coupon',
			'description' => 'Update an existing coupon',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id'                  => array( 'type' => 'integer', 'description' => 'Coupon ID' ),
					'code'                => array( 'type' => 'string' ),
					'discount_type'       => array( 'type' => 'string' ),
					'amount'              => array( 'type' => 'string' ),
					'expiry_date'         => array( 'type' => 'string' ),
					'usage_limit'         => array( 'type' => 'integer' ),
					'usage_limit_per_user'=> array( 'type' => 'integer' ),
					'minimum_amount'      => array( 'type' => 'string' ),
					'maximum_amount'      => array( 'type' => 'string' ),
					'free_shipping'       => array( 'type' => 'boolean' ),
					'individual_use'      => array( 'type' => 'boolean' ),
					'product_ids'         => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_coupon' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-coupon', array(
			'label'       => 'Delete WooCommerce Coupon',
			'description' => 'Delete a coupon permanently',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array( 'type' => 'integer', 'description' => 'Coupon ID' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_coupon' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-coupon-usage', array(
			'label'       => 'Get WooCommerce Coupon Usage',
			'description' => 'Get how many times a coupon has been used',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array( 'type' => 'integer', 'description' => 'Coupon ID' ),
				),
				'required' => array( 'id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_coupon_usage' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== REVIEWS ====================

	private static function register_review_abilities() {

		self::register( 'wmc/get-woo-reviews', array(
			'label'       => 'Get WooCommerce Reviews',
			'description' => 'List product reviews with filtering',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'product_id' => array( 'type' => 'integer', 'description' => 'Filter by product ID' ),
					'status'     => array( 'type' => 'string', 'description' => 'approve, hold, spam, trash, all', 'default' => 'approve' ),
					'per_page'   => array( 'type' => 'integer', 'default' => 20 ),
					'page'       => array( 'type' => 'integer', 'default' => 1 ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_reviews' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-review', array(
			'label'       => 'Create WooCommerce Review',
			'description' => 'Add a review to a product',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'product_id'     => array( 'type' => 'integer' ),
					'reviewer'       => array( 'type' => 'string', 'description' => 'Reviewer name' ),
					'reviewer_email' => array( 'type' => 'string' ),
					'review'         => array( 'type' => 'string', 'description' => 'Review content' ),
					'rating'         => array( 'type' => 'integer', 'description' => '1 to 5', 'minimum' => 1, 'maximum' => 5 ),
					'status'         => array( 'type' => 'string', 'default' => 'approve' ),
				),
				'required' => array( 'product_id', 'reviewer', 'reviewer_email', 'review', 'rating' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_review' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/update-woo-review', array(
			'label'       => 'Update WooCommerce Review',
			'description' => 'Approve, hold, edit or update rating of a review',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'review_id' => array( 'type' => 'integer' ),
					'status'    => array( 'type' => 'string', 'description' => 'approve, hold, spam, trash' ),
					'review'    => array( 'type' => 'string', 'description' => 'Updated review content' ),
					'rating'    => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ),
				),
				'required' => array( 'review_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_review' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-review', array(
			'label'       => 'Delete WooCommerce Review',
			'description' => 'Delete a product review',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'review_id' => array( 'type' => 'integer' ),
					'force'     => array( 'type' => 'boolean', 'default' => true ),
				),
				'required' => array( 'review_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_review' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== SHIPPING ====================

	private static function register_shipping_abilities() {

		self::register( 'wmc/get-woo-shipping-zones', array(
			'label'       => 'Get WooCommerce Shipping Zones',
			'description' => 'List all shipping zones and their methods',
			'category'    => 'wp-content-manager',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_shipping_zones' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-shipping-zone', array(
			'label'       => 'Create WooCommerce Shipping Zone',
			'description' => 'Create a new shipping zone',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'name'  => array( 'type' => 'string', 'description' => 'Zone name' ),
					'order' => array( 'type' => 'integer', 'description' => 'Zone display order', 'default' => 0 ),
				),
				'required' => array( 'name' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_shipping_zone' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/add-woo-shipping-method', array(
			'label'       => 'Add Shipping Method to Zone',
			'description' => 'Add flat_rate, free_shipping, or local_pickup to a shipping zone',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'zone_id'       => array( 'type' => 'integer' ),
					'method_id'     => array( 'type' => 'string', 'enum' => array( 'flat_rate', 'free_shipping', 'local_pickup' ) ),
					'title'         => array( 'type' => 'string', 'description' => 'Method title shown to customer' ),
					'cost'          => array( 'type' => 'string', 'description' => 'Cost for flat_rate (e.g. "5.00")' ),
					'min_amount'    => array( 'type' => 'string', 'description' => 'Minimum order for free_shipping' ),
				),
				'required' => array( 'zone_id', 'method_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'add_shipping_method' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-shipping-zone', array(
			'label'       => 'Delete WooCommerce Shipping Zone',
			'description' => 'Delete a shipping zone',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'zone_id' => array( 'type' => 'integer' ),
				),
				'required' => array( 'zone_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_shipping_zone' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== TAX ====================

	private static function register_tax_abilities() {

		self::register( 'wmc/get-woo-tax-rates', array(
			'label'       => 'Get WooCommerce Tax Rates',
			'description' => 'List all tax rates',
			'category'    => 'wp-content-manager',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_tax_rates' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/create-woo-tax-rate', array(
			'label'       => 'Create WooCommerce Tax Rate',
			'description' => 'Add a new tax rate',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'country'  => array( 'type' => 'string', 'description' => 'Country code e.g. US, PK' ),
					'state'    => array( 'type' => 'string', 'description' => 'State code (optional)' ),
					'rate'     => array( 'type' => 'string', 'description' => 'Tax rate e.g. "17.0000"' ),
					'name'     => array( 'type' => 'string', 'description' => 'Tax name e.g. "GST"' ),
					'shipping' => array( 'type' => 'boolean', 'description' => 'Apply to shipping', 'default' => true ),
					'priority' => array( 'type' => 'integer', 'default' => 1 ),
					'compound' => array( 'type' => 'boolean', 'default' => false ),
					'class'    => array( 'type' => 'string', 'description' => 'Tax class: standard, reduced-rate, zero-rate', 'default' => 'standard' ),
				),
				'required' => array( 'country', 'rate', 'name' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'create_tax_rate' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/delete-woo-tax-rate', array(
			'label'       => 'Delete WooCommerce Tax Rate',
			'description' => 'Delete a tax rate by ID',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'tax_rate_id' => array( 'type' => 'integer' ),
				),
				'required' => array( 'tax_rate_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'delete_tax_rate' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== REPORTS ====================

	private static function register_report_abilities() {

		self::register( 'wmc/get-woo-sales-report', array(
			'label'       => 'Get WooCommerce Sales Report',
			'description' => 'Get sales data — total revenue, orders count, average order value by date range',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'date_from' => array( 'type' => 'string', 'description' => 'Start date YYYY-MM-DD' ),
					'date_to'   => array( 'type' => 'string', 'description' => 'End date YYYY-MM-DD' ),
					'period'    => array( 'type' => 'string', 'description' => 'today, week, month, year (overrides date range)', 'enum' => array( 'today', 'week', 'month', 'year' ) ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_sales_report' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-top-products', array(
			'label'       => 'Get Top Selling Products',
			'description' => 'Get best-selling products by sales count or revenue',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'limit'     => array( 'type' => 'integer', 'default' => 10 ),
					'date_from' => array( 'type' => 'string', 'description' => 'YYYY-MM-DD' ),
					'date_to'   => array( 'type' => 'string', 'description' => 'YYYY-MM-DD' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_top_products' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== CUSTOMERS ====================

	private static function register_customer_abilities() {

		self::register( 'wmc/update-woo-customer', array(
			'label'       => 'Update WooCommerce Customer',
			'description' => 'Update customer billing/shipping address or details',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'customer_id'        => array( 'type' => 'integer' ),
					'first_name'         => array( 'type' => 'string' ),
					'last_name'          => array( 'type' => 'string' ),
					'email'              => array( 'type' => 'string' ),
					'billing_address_1'  => array( 'type' => 'string' ),
					'billing_city'       => array( 'type' => 'string' ),
					'billing_country'    => array( 'type' => 'string' ),
					'billing_phone'      => array( 'type' => 'string' ),
					'shipping_address_1' => array( 'type' => 'string' ),
					'shipping_city'      => array( 'type' => 'string' ),
					'shipping_country'   => array( 'type' => 'string' ),
				),
				'required' => array( 'customer_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_customer' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/get-woo-customer-orders', array(
			'label'       => 'Get Customer Orders',
			'description' => 'Get all orders placed by a specific customer',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'customer_id' => array( 'type' => 'integer' ),
					'per_page'    => array( 'type' => 'integer', 'default' => 20 ),
					'status'      => array( 'type' => 'string' ),
				),
				'required' => array( 'customer_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_customer_orders' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== WOOCOMMERCE SETTINGS ====================

	private static function register_settings_abilities() {

		self::register( 'wmc/get-woo-settings', array(
			'label'       => 'Get WooCommerce Settings',
			'description' => 'Read WooCommerce settings: currency, tax, checkout, account, email options',
			'category'    => 'wp-content-manager',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'get_woo_settings' ),
			'permission_callback' => fn() => self::perm(),
		) );

		self::register( 'wmc/update-woo-setting', array(
			'label'       => 'Update WooCommerce Setting',
			'description' => 'Update a specific WooCommerce setting by option name',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'option_name'  => array( 'type' => 'string', 'description' => 'WC option name e.g. woocommerce_currency, woocommerce_calc_taxes' ),
					'option_value' => array( 'description' => 'New value' ),
				),
				'required' => array( 'option_name', 'option_value' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'update_woo_setting' ),
			'permission_callback' => fn() => self::perm(),
		) );
	}

	// ==================== EXECUTE CALLBACKS ====================
	// ---- PRODUCTS ----

	public static function get_product_detail( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$product = wc_get_product( (int) $input['id'] );
		if ( ! $product ) return array( 'success' => false, 'message' => 'Product not found' );

		$image_id  = $product->get_image_id();
		$gallery   = $product->get_gallery_image_ids();

		$data = array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'type'              => $product->get_type(),
			'status'            => $product->get_status(),
			'sku'               => $product->get_sku(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'manage_stock'      => $product->get_manage_stock(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'stock_status'      => $product->get_stock_status(),
			'weight'            => $product->get_weight(),
			'dimensions'        => array(
				'length' => $product->get_length(),
				'width'  => $product->get_width(),
				'height' => $product->get_height(),
			),
			'categories'        => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'all' ) ),
			'tags'              => wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'all' ) ),
			'attributes'        => $product->get_attributes(),
			'featured_image_id' => $image_id,
			'featured_image_url'=> $image_id ? wp_get_attachment_url( $image_id ) : '',
			'gallery_ids'       => $gallery,
			'gallery_urls'      => array_map( 'wp_get_attachment_url', $gallery ),
			'url'               => get_permalink( $product->get_id() ),
			'date_created'      => $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d H:i:s' ) : null,
			'date_modified'     => $product->get_date_modified() ? $product->get_date_modified()->date( 'Y-m-d H:i:s' ) : null,
			'total_sales'       => $product->get_total_sales(),
		);

		if ( $product->is_type( 'variable' ) ) {
			$data['variations'] = $product->get_children();
		}

		return array( 'success' => true, 'product' => $data );
	}

	public static function delete_product( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$product = wc_get_product( (int) $input['id'] );
		if ( ! $product ) return array( 'success' => false, 'message' => 'Product not found' );
		$force = $input['force'] ?? true;
		$result = $product->delete( $force );
		return array( 'success' => (bool) $result, 'message' => $result ? 'Product deleted' : 'Delete failed' );
	}

	public static function set_product_image( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$product = wc_get_product( (int) $input['id'] );
		if ( ! $product ) return array( 'success' => false, 'message' => 'Product not found' );

		if ( isset( $input['featured_image_id'] ) ) {
			$product->set_image_id( (int) $input['featured_image_id'] );
		}
		if ( isset( $input['gallery_ids'] ) && is_array( $input['gallery_ids'] ) ) {
			$product->set_gallery_image_ids( array_map( 'intval', $input['gallery_ids'] ) );
		}
		$product->save();
		return array( 'success' => true, 'message' => 'Product images updated' );
	}

	public static function get_product_categories( $input = null ) {
		if ( $err = self::woo_check() ) return $err;
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'number'     => $input['per_page'] ?? 50,
			'hide_empty' => $input['hide_empty'] ?? false,
		) );
		if ( is_wp_error( $terms ) ) return array( 'success' => false, 'message' => $terms->get_error_message() );
		$cats = array();
		foreach ( $terms as $term ) {
			$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$cats[] = array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'parent'      => $term->parent,
				'count'       => $term->count,
				'description' => $term->description,
				'image_url'   => $thumb_id ? wp_get_attachment_url( $thumb_id ) : '',
			);
		}
		return array( 'success' => true, 'count' => count( $cats ), 'categories' => $cats );
	}

	public static function create_product_category( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$args = array(
			'description' => sanitize_text_field( $input['description'] ?? '' ),
			'parent'      => (int) ( $input['parent'] ?? 0 ),
		);
		$result = wp_insert_term( sanitize_text_field( $input['name'] ), 'product_cat', $args );
		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		if ( ! empty( $input['image_id'] ) ) {
			update_term_meta( $result['term_id'], 'thumbnail_id', (int) $input['image_id'] );
		}
		return array( 'success' => true, 'id' => $result['term_id'], 'message' => 'Category created' );
	}

	public static function update_product_category( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$args = array();
		if ( isset( $input['name'] ) )        $args['name']        = sanitize_text_field( $input['name'] );
		if ( isset( $input['description'] ) ) $args['description'] = sanitize_text_field( $input['description'] );
		if ( isset( $input['parent'] ) )      $args['parent']      = (int) $input['parent'];
		$result = wp_update_term( (int) $input['id'], 'product_cat', $args );
		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		if ( isset( $input['image_id'] ) ) {
			update_term_meta( (int) $input['id'], 'thumbnail_id', (int) $input['image_id'] );
		}
		return array( 'success' => true, 'message' => 'Category updated' );
	}

	public static function delete_product_category( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$result = wp_delete_term( (int) $input['id'], 'product_cat' );
		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		return array( 'success' => (bool) $result, 'message' => 'Category deleted' );
	}

	public static function get_product_tags( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$args = array( 'taxonomy' => 'product_tag', 'number' => $input['per_page'] ?? 50, 'hide_empty' => $input['hide_empty'] ?? false );
		if ( ! empty( $input['search'] ) ) $args['search'] = sanitize_text_field( $input['search'] );
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) return array( 'success' => false, 'message' => $terms->get_error_message() );
		$tags = array_map( fn($t) => array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'count' => $t->count ), $terms );
		return array( 'success' => true, 'count' => count( $tags ), 'tags' => $tags );
	}

	public static function create_product_tag( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$result = wp_insert_term( sanitize_text_field( $input['name'] ), 'product_tag', array( 'description' => $input['description'] ?? '' ) );
		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		return array( 'success' => true, 'id' => $result['term_id'], 'message' => 'Tag created' );
	}

	public static function delete_product_tag( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$result = wp_delete_term( (int) $input['id'], 'product_tag' );
		if ( is_wp_error( $result ) ) return array( 'success' => false, 'message' => $result->get_error_message() );
		return array( 'success' => (bool) $result, 'message' => 'Tag deleted' );
	}

	public static function get_product_meta( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$id = (int) $input['id'];
		if ( ! get_post( $id ) ) return array( 'success' => false, 'message' => 'Product not found' );
		if ( ! empty( $input['meta_key'] ) ) {
			$value = get_post_meta( $id, sanitize_key( $input['meta_key'] ), true );
			return array( 'success' => true, 'meta_key' => $input['meta_key'], 'meta_value' => $value );
		}
		$all = get_post_meta( $id );
		$clean = array();
		foreach ( $all as $key => $values ) {
			$clean[ $key ] = count( $values ) === 1 ? $values[0] : $values;
		}
		return array( 'success' => true, 'meta' => $clean );
	}

	public static function update_product_meta( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$result = update_post_meta( (int) $input['id'], sanitize_key( $input['meta_key'] ), $input['meta_value'] );
		return array( 'success' => true, 'updated' => $result !== false, 'message' => 'Meta updated' );
	}

	public static function create_variable_product( $input ) {
		if ( $err = self::woo_check() ) return $err;

		$product = new WC_Product_Variable();
		$product->set_name( sanitize_text_field( $input['name'] ) );
		$product->set_status( $input['status'] ?? 'publish' );
		if ( isset( $input['description'] ) )       $product->set_description( wp_kses_post( $input['description'] ) );
		if ( isset( $input['short_description'] ) ) $product->set_short_description( wp_kses_post( $input['short_description'] ) );
		if ( isset( $input['sku'] ) )               $product->set_sku( sanitize_text_field( $input['sku'] ) );
		if ( isset( $input['category_ids'] ) )      $product->set_category_ids( array_map( 'intval', $input['category_ids'] ) );

		$wc_attributes = array();
		foreach ( ( $input['attributes'] ?? array() ) as $attr ) {
			$wc_attr = new WC_Product_Attribute();
			$wc_attr->set_name( sanitize_text_field( $attr['name'] ) );
			$wc_attr->set_options( array_map( 'sanitize_text_field', $attr['options'] ?? array() ) );
			$wc_attr->set_visible( $attr['visible'] ?? true );
			$wc_attr->set_variation( $attr['variation'] ?? true );
			$wc_attributes[] = $wc_attr;
		}
		$product->set_attributes( $wc_attributes );
		$product_id = $product->save();

		$variation_ids = array();
		foreach ( ( $input['variations'] ?? array() ) as $var_data ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$attrs = array();
			foreach ( ( $var_data['attributes'] ?? array() ) as $attr_name => $attr_value ) {
				$attrs[ 'attribute_' . sanitize_title( $attr_name ) ] = sanitize_text_field( $attr_value );
			}
			$variation->set_attributes( $attrs );
			if ( isset( $var_data['regular_price'] ) ) $variation->set_regular_price( sanitize_text_field( $var_data['regular_price'] ) );
			if ( isset( $var_data['sale_price'] ) )    $variation->set_sale_price( sanitize_text_field( $var_data['sale_price'] ) );
			if ( isset( $var_data['sku'] ) )           $variation->set_sku( sanitize_text_field( $var_data['sku'] ) );
			if ( isset( $var_data['stock_quantity'] ) ) {
				$variation->set_manage_stock( true );
				$variation->set_stock_quantity( (int) $var_data['stock_quantity'] );
			}
			$variation->set_status( 'publish' );
			$variation_ids[] = $variation->save();
		}

		WC_Product_Variable::sync( $product_id );
		return array( 'success' => true, 'product_id' => $product_id, 'variation_ids' => $variation_ids, 'message' => 'Variable product created' );
	}

	public static function get_variations( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$product = wc_get_product( (int) $input['product_id'] );
		if ( ! $product || ! $product->is_type( 'variable' ) ) return array( 'success' => false, 'message' => 'Variable product not found' );
		$vars = array();
		foreach ( $product->get_children() as $var_id ) {
			$var = wc_get_product( $var_id );
			if ( ! $var ) continue;
			$vars[] = array(
				'id'            => $var->get_id(),
				'sku'           => $var->get_sku(),
				'status'        => $var->get_status(),
				'regular_price' => $var->get_regular_price(),
				'sale_price'    => $var->get_sale_price(),
				'stock_quantity'=> $var->get_stock_quantity(),
				'attributes'    => $var->get_variation_attributes(),
			);
		}
		return array( 'success' => true, 'count' => count( $vars ), 'variations' => $vars );
	}

	public static function update_variation( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$var = wc_get_product( (int) $input['variation_id'] );
		if ( ! $var ) return array( 'success' => false, 'message' => 'Variation not found' );
		if ( isset( $input['regular_price'] ) ) $var->set_regular_price( sanitize_text_field( $input['regular_price'] ) );
		if ( isset( $input['sale_price'] ) )    $var->set_sale_price( sanitize_text_field( $input['sale_price'] ) );
		if ( isset( $input['sku'] ) )           $var->set_sku( sanitize_text_field( $input['sku'] ) );
		if ( isset( $input['status'] ) )        $var->set_status( $input['status'] );
		if ( isset( $input['stock_quantity'] ) ) {
			$var->set_manage_stock( true );
			$var->set_stock_quantity( (int) $input['stock_quantity'] );
		}
		$var->save();
		WC_Product_Variable::sync( (int) $input['product_id'] );
		return array( 'success' => true, 'message' => 'Variation updated' );
	}

	public static function delete_variation( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$var = wc_get_product( (int) $input['variation_id'] );
		if ( ! $var ) return array( 'success' => false, 'message' => 'Variation not found' );
		$result = $var->delete( true );
		WC_Product_Variable::sync( (int) $input['product_id'] );
		return array( 'success' => (bool) $result, 'message' => 'Variation deleted' );
	}

	public static function assign_product_categories( $input ) {
		if ( ! self::is_enabled( 'woocommerce', 'write' ) ) {
			return self::get_disabled_error( 'write' );
		}
		if ( $err = self::woo_check() ) return $err;
		$product = wc_get_product( (int) $input['product_id'] );
		if ( ! $product ) return array( 'success' => false, 'message' => 'Product not found' );

		$new_ids = array_map( 'intval', $input['category_ids'] );

		if ( ! empty( $input['append'] ) ) {
			$existing = array_map( 'intval', $product->get_category_ids() );
			$new_ids  = array_values( array_unique( array_merge( $existing, $new_ids ) ) );
		}

		$product->set_category_ids( $new_ids );
		$product->save();

		return array(
			'success'      => true,
			'message'      => 'Categories assigned',
			'category_ids' => $new_ids,
		);
	}

	public static function get_low_stock( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$threshold = (int) ( $input['threshold'] ?? 5 );
		$query = new WP_Query( array(
			'post_type'      => array( 'product', 'product_variation' ),
			'posts_per_page' => $input['per_page'] ?? 50,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array( 'key' => '_manage_stock', 'value' => 'yes' ),
				array( 'key' => '_stock', 'value' => $threshold, 'compare' => '<=', 'type' => 'NUMERIC' ),
			),
		) );
		$products = array();
		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$products[] = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'sku'   => $product->get_sku(),
					'stock' => $product->get_stock_quantity(),
					'type'  => $product->get_type(),
				);
			}
		}
		return array( 'success' => true, 'count' => count( $products ), 'products' => $products );
	}

	// ---- ORDERS ----

	public static function get_order_detail( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$order = wc_get_order( (int) $input['order_id'] );
		if ( ! $order ) return array( 'success' => false, 'message' => 'Order not found' );

		$items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$items[] = array(
				'item_id'    => $item_id,
				'product_id' => $item->get_product_id(),
				'name'       => $item->get_name(),
				'quantity'   => $item->get_quantity(),
				'subtotal'   => $item->get_subtotal(),
				'total'      => $item->get_total(),
				'sku'        => ( $p = $item->get_product() ) ? $p->get_sku() : '',
			);
		}

		return array(
			'success' => true,
			'order'   => array(
				'id'               => $order->get_id(),
				'status'           => $order->get_status(),
				'currency'         => $order->get_currency(),
				'total'            => $order->get_total(),
				'subtotal'         => $order->get_subtotal(),
				'total_tax'        => $order->get_total_tax(),
				'shipping_total'   => $order->get_shipping_total(),
				'discount_total'   => $order->get_discount_total(),
				'payment_method'   => $order->get_payment_method_title(),
				'transaction_id'   => $order->get_transaction_id(),
				'customer_id'      => $order->get_customer_id(),
				'customer_note'    => $order->get_customer_note(),
				'date_created'     => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : null,
				'date_paid'        => $order->get_date_paid() ? $order->get_date_paid()->date( 'Y-m-d H:i:s' ) : null,
				'date_completed'   => $order->get_date_completed() ? $order->get_date_completed()->date( 'Y-m-d H:i:s' ) : null,
				'billing'          => array(
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'email'      => $order->get_billing_email(),
					'phone'      => $order->get_billing_phone(),
					'address_1'  => $order->get_billing_address_1(),
					'address_2'  => $order->get_billing_address_2(),
					'city'       => $order->get_billing_city(),
					'state'      => $order->get_billing_state(),
					'postcode'   => $order->get_billing_postcode(),
					'country'    => $order->get_billing_country(),
				),
				'shipping'         => array(
					'first_name' => $order->get_shipping_first_name(),
					'last_name'  => $order->get_shipping_last_name(),
					'address_1'  => $order->get_shipping_address_1(),
					'city'       => $order->get_shipping_city(),
					'state'      => $order->get_shipping_state(),
					'postcode'   => $order->get_shipping_postcode(),
					'country'    => $order->get_shipping_country(),
				),
				'items'            => $items,
				'coupons'          => $order->get_coupon_codes(),
				'refunds'          => array_map( fn($r) => array( 'id' => $r->get_id(), 'amount' => $r->get_amount(), 'reason' => $r->get_reason() ), $order->get_refunds() ),
			),
		);
	}

	public static function add_order_note( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$order = wc_get_order( (int) $input['order_id'] );
		if ( ! $order ) return array( 'success' => false, 'message' => 'Order not found' );
		$note_id = $order->add_order_note( wp_kses_post( $input['note'] ), (bool) ( $input['customer_note'] ?? false ) );
		return array( 'success' => true, 'note_id' => $note_id, 'message' => 'Note added' );
	}

	public static function create_order( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$order = wc_create_order( array(
			'status'      => $input['status'] ?? 'pending',
			'customer_id' => (int) ( $input['customer_id'] ?? 0 ),
		) );
		if ( is_wp_error( $order ) ) return array( 'success' => false, 'message' => $order->get_error_message() );

		foreach ( ( $input['line_items'] ?? array() ) as $item ) {
			if ( isset( $item['variation_id'] ) && $item['variation_id'] ) {
				$order->add_product( wc_get_product( $item['variation_id'] ), (int) ( $item['quantity'] ?? 1 ) );
			} elseif ( isset( $item['product_id'] ) ) {
				$order->add_product( wc_get_product( $item['product_id'] ), (int) ( $item['quantity'] ?? 1 ) );
			}
		}

		$billing_fields = array( 'first_name', 'last_name', 'email', 'phone', 'address_1', 'city', 'country' );
		foreach ( $billing_fields as $field ) {
			if ( isset( $input[ 'billing_' . $field ] ) ) {
				$order->{ 'set_billing_' . $field }( sanitize_text_field( $input[ 'billing_' . $field ] ) );
			}
		}
		if ( isset( $input['payment_method'] ) ) {
			$order->set_payment_method( sanitize_text_field( $input['payment_method'] ) );
		}
		if ( isset( $input['note'] ) ) {
			$order->set_customer_note( sanitize_text_field( $input['note'] ) );
		}
		$order->calculate_totals();
		$order->save();

		return array( 'success' => true, 'order_id' => $order->get_id(), 'total' => $order->get_total(), 'message' => 'Order created' );
	}

	public static function delete_order( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$order = wc_get_order( (int) $input['order_id'] );
		if ( ! $order ) return array( 'success' => false, 'message' => 'Order not found' );
		$result = $order->delete( $input['force'] ?? true );
		return array( 'success' => (bool) $result, 'message' => $result ? 'Order deleted' : 'Delete failed' );
	}

	public static function create_refund( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$order = wc_get_order( (int) $input['order_id'] );
		if ( ! $order ) return array( 'success' => false, 'message' => 'Order not found' );

		$amount = isset( $input['amount'] ) ? floatval( $input['amount'] ) : $order->get_total();
		$refund = wc_create_refund( array(
			'order_id' => $order->get_id(),
			'amount'   => $amount,
			'reason'   => sanitize_text_field( $input['reason'] ?? '' ),
		) );
		if ( is_wp_error( $refund ) ) return array( 'success' => false, 'message' => $refund->get_error_message() );
		return array( 'success' => true, 'refund_id' => $refund->get_id(), 'amount' => $refund->get_amount(), 'message' => 'Refund created' );
	}

	public static function get_order_notes( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$notes = wc_get_order_notes( array( 'order_id' => (int) $input['order_id'] ) );
		$result = array_map( fn($n) => array(
			'id'             => $n->id,
			'note'           => $n->content,
			'author'         => $n->added_by,
			'customer_note'  => $n->customer_note,
			'date'           => $n->date_created->date( 'Y-m-d H:i:s' ),
		), $notes );
		return array( 'success' => true, 'count' => count( $result ), 'notes' => $result );
	}

	public static function get_customer_detail( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$customer = new WC_Customer( (int) $input['customer_id'] );
		if ( ! $customer->get_id() ) return array( 'success' => false, 'message' => 'Customer not found' );
		return array(
			'success'  => true,
			'customer' => array(
				'id'             => $customer->get_id(),
				'email'          => $customer->get_email(),
				'first_name'     => $customer->get_first_name(),
				'last_name'      => $customer->get_last_name(),
				'username'       => $customer->get_username(),
				'orders_count'   => wc_get_customer_order_count( $customer->get_id() ),
				'total_spent'    => wc_get_customer_total_spent( $customer->get_id() ),
				'date_created'   => $customer->get_date_created() ? $customer->get_date_created()->date( 'Y-m-d H:i:s' ) : null,
				'billing'        => array(
					'first_name' => $customer->get_billing_first_name(),
					'last_name'  => $customer->get_billing_last_name(),
					'email'      => $customer->get_billing_email(),
					'phone'      => $customer->get_billing_phone(),
					'address_1'  => $customer->get_billing_address_1(),
					'city'       => $customer->get_billing_city(),
					'country'    => $customer->get_billing_country(),
				),
				'shipping'       => array(
					'first_name' => $customer->get_shipping_first_name(),
					'last_name'  => $customer->get_shipping_last_name(),
					'address_1'  => $customer->get_shipping_address_1(),
					'city'       => $customer->get_shipping_city(),
					'country'    => $customer->get_shipping_country(),
				),
			),
		);
	}

	// ---- COUPONS ----

	public static function get_coupons( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => (int) ( $input['per_page'] ?? 20 ),
			'paged'          => (int) ( $input['page'] ?? 1 ),
			'post_status'    => 'publish',
		);
		if ( ! empty( $input['search'] ) ) $args['s'] = sanitize_text_field( $input['search'] );
		$query   = new WP_Query( $args );
		$coupons = array();
		foreach ( $query->posts as $post ) {
			$coupon    = new WC_Coupon( $post->ID );
			$coupons[] = array(
				'id'             => $coupon->get_id(),
				'code'           => $coupon->get_code(),
				'discount_type'  => $coupon->get_discount_type(),
				'amount'         => $coupon->get_amount(),
				'expiry_date'    => $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : null,
				'usage_count'    => $coupon->get_usage_count(),
				'usage_limit'    => $coupon->get_usage_limit(),
				'free_shipping'  => $coupon->get_free_shipping(),
				'minimum_amount' => $coupon->get_minimum_amount(),
			);
		}
		return array( 'success' => true, 'count' => count( $coupons ), 'total' => $query->found_posts, 'coupons' => $coupons );
	}

	public static function create_coupon( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$coupon = new WC_Coupon();
		$coupon->set_code( sanitize_text_field( $input['code'] ) );
		$coupon->set_discount_type( $input['discount_type'] );
		$coupon->set_amount( floatval( $input['amount'] ) );
		if ( isset( $input['description'] ) )          $coupon->set_description( sanitize_text_field( $input['description'] ) );
		if ( isset( $input['expiry_date'] ) )          $coupon->set_date_expires( sanitize_text_field( $input['expiry_date'] ) );
		if ( isset( $input['usage_limit'] ) )          $coupon->set_usage_limit( (int) $input['usage_limit'] );
		if ( isset( $input['usage_limit_per_user'] ) ) $coupon->set_usage_limit_per_user( (int) $input['usage_limit_per_user'] );
		if ( isset( $input['minimum_amount'] ) )       $coupon->set_minimum_amount( floatval( $input['minimum_amount'] ) );
		if ( isset( $input['maximum_amount'] ) )       $coupon->set_maximum_amount( floatval( $input['maximum_amount'] ) );
		if ( isset( $input['free_shipping'] ) )        $coupon->set_free_shipping( (bool) $input['free_shipping'] );
		if ( isset( $input['individual_use'] ) )       $coupon->set_individual_use( (bool) $input['individual_use'] );
		if ( isset( $input['product_ids'] ) )          $coupon->set_product_ids( array_map( 'intval', $input['product_ids'] ) );
		if ( isset( $input['exclude_product_ids'] ) )  $coupon->set_excluded_product_ids( array_map( 'intval', $input['exclude_product_ids'] ) );
		$id = $coupon->save();
		return array( 'success' => true, 'id' => $id, 'code' => $coupon->get_code(), 'message' => 'Coupon created' );
	}

	public static function update_coupon( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$coupon = new WC_Coupon( (int) $input['id'] );
		if ( ! $coupon->get_id() ) return array( 'success' => false, 'message' => 'Coupon not found' );
		if ( isset( $input['code'] ) )                 $coupon->set_code( sanitize_text_field( $input['code'] ) );
		if ( isset( $input['discount_type'] ) )        $coupon->set_discount_type( $input['discount_type'] );
		if ( isset( $input['amount'] ) )               $coupon->set_amount( floatval( $input['amount'] ) );
		if ( isset( $input['expiry_date'] ) )          $coupon->set_date_expires( sanitize_text_field( $input['expiry_date'] ) );
		if ( isset( $input['usage_limit'] ) )          $coupon->set_usage_limit( (int) $input['usage_limit'] );
		if ( isset( $input['usage_limit_per_user'] ) ) $coupon->set_usage_limit_per_user( (int) $input['usage_limit_per_user'] );
		if ( isset( $input['minimum_amount'] ) )       $coupon->set_minimum_amount( floatval( $input['minimum_amount'] ) );
		if ( isset( $input['maximum_amount'] ) )       $coupon->set_maximum_amount( floatval( $input['maximum_amount'] ) );
		if ( isset( $input['free_shipping'] ) )        $coupon->set_free_shipping( (bool) $input['free_shipping'] );
		if ( isset( $input['individual_use'] ) )       $coupon->set_individual_use( (bool) $input['individual_use'] );
		if ( isset( $input['product_ids'] ) )          $coupon->set_product_ids( array_map( 'intval', $input['product_ids'] ) );
		$coupon->save();
		return array( 'success' => true, 'message' => 'Coupon updated' );
	}

	public static function delete_coupon( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$coupon = new WC_Coupon( (int) $input['id'] );
		if ( ! $coupon->get_id() ) return array( 'success' => false, 'message' => 'Coupon not found' );
		$result = wp_delete_post( $coupon->get_id(), true );
		return array( 'success' => (bool) $result, 'message' => 'Coupon deleted' );
	}

	public static function get_coupon_usage( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$coupon = new WC_Coupon( (int) $input['id'] );
		if ( ! $coupon->get_id() ) return array( 'success' => false, 'message' => 'Coupon not found' );
		return array(
			'success'     => true,
			'code'        => $coupon->get_code(),
			'usage_count' => $coupon->get_usage_count(),
			'usage_limit' => $coupon->get_usage_limit(),
			'remaining'   => $coupon->get_usage_limit() ? max( 0, $coupon->get_usage_limit() - $coupon->get_usage_count() ) : 'unlimited',
		);
	}

	// ---- REVIEWS ----

	public static function get_reviews( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$args = array(
			'post_type' => 'product',
			'number'    => $input['per_page'] ?? 20,
			'offset'    => ( ( $input['page'] ?? 1 ) - 1 ) * ( $input['per_page'] ?? 20 ),
			'status'    => $input['status'] ?? 'approve',
		);
		if ( ! empty( $input['product_id'] ) ) $args['post_id'] = (int) $input['product_id'];
		$comments = get_comments( $args );
		$reviews  = array_map( fn($c) => array(
			'id'         => $c->comment_ID,
			'product_id' => $c->comment_post_ID,
			'author'     => $c->comment_author,
			'email'      => $c->comment_author_email,
			'content'    => $c->comment_content,
			'rating'     => (int) get_comment_meta( $c->comment_ID, 'rating', true ),
			'status'     => $c->comment_approved,
			'date'       => $c->comment_date,
		), $comments );
		return array( 'success' => true, 'count' => count( $reviews ), 'reviews' => $reviews );
	}

	public static function create_review( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$comment_id = wp_insert_comment( array(
			'comment_post_ID'      => (int) $input['product_id'],
			'comment_author'       => sanitize_text_field( $input['reviewer'] ),
			'comment_author_email' => sanitize_email( $input['reviewer_email'] ),
			'comment_content'      => wp_kses_post( $input['review'] ),
			'comment_approved'     => $input['status'] ?? 'approve',
			'comment_type'         => 'review',
		) );
		if ( ! $comment_id ) return array( 'success' => false, 'message' => 'Failed to create review' );
		update_comment_meta( $comment_id, 'rating', (int) $input['rating'] );
		update_comment_meta( $comment_id, 'verified', 0 );
		return array( 'success' => true, 'review_id' => $comment_id, 'message' => 'Review created' );
	}

	public static function update_review( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$data = array( 'comment_ID' => (int) $input['review_id'] );
		if ( isset( $input['review'] ) )  $data['comment_content']  = wp_kses_post( $input['review'] );
		if ( isset( $input['status'] ) )  $data['comment_approved'] = sanitize_text_field( $input['status'] );
		$result = wp_update_comment( $data );
		if ( isset( $input['rating'] ) ) update_comment_meta( (int) $input['review_id'], 'rating', (int) $input['rating'] );
		return array( 'success' => (bool) $result, 'message' => 'Review updated' );
	}

	public static function delete_review( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$result = wp_delete_comment( (int) $input['review_id'], $input['force'] ?? true );
		return array( 'success' => (bool) $result, 'message' => $result ? 'Review deleted' : 'Delete failed' );
	}

	// ---- SHIPPING ----

	public static function get_shipping_zones( $input = null ) {
		if ( $err = self::woo_check() ) return $err;
		$zones  = WC_Shipping_Zones::get_zones();
		$result = array();
		foreach ( $zones as $zone_data ) {
			$zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
			$methods = array();
			foreach ( $zone->get_shipping_methods() as $method ) {
				$methods[] = array(
					'instance_id' => $method->instance_id,
					'id'          => $method->id,
					'title'       => $method->title,
					'enabled'     => $method->is_enabled(),
				);
			}
			$result[] = array(
				'id'       => $zone->get_id(),
				'name'     => $zone->get_zone_name(),
				'order'    => $zone->get_zone_order(),
				'methods'  => $methods,
			);
		}
		$rest_zone    = new WC_Shipping_Zone( 0 );
		$rest_methods = array();
		foreach ( $rest_zone->get_shipping_methods() as $method ) {
			$rest_methods[] = array( 'instance_id' => $method->instance_id, 'id' => $method->id, 'title' => $method->title );
		}
		$result[] = array( 'id' => 0, 'name' => 'Rest of the World', 'methods' => $rest_methods );
		return array( 'success' => true, 'count' => count( $result ), 'zones' => $result );
	}

	public static function create_shipping_zone( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( sanitize_text_field( $input['name'] ) );
		$zone->set_zone_order( (int) ( $input['order'] ?? 0 ) );
		$zone->save();
		return array( 'success' => true, 'zone_id' => $zone->get_id(), 'message' => 'Shipping zone created' );
	}

	public static function add_shipping_method( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$zone = WC_Shipping_Zones::get_zone( (int) $input['zone_id'] );
		if ( ! $zone ) return array( 'success' => false, 'message' => 'Zone not found' );
		$instance_id = $zone->add_shipping_method( sanitize_text_field( $input['method_id'] ) );
		if ( $instance_id && ( isset( $input['title'] ) || isset( $input['cost'] ) || isset( $input['min_amount'] ) ) ) {
			$option_key = 'woocommerce_' . $input['method_id'] . '_' . $instance_id . '_settings';
			$settings   = get_option( $option_key, array() );
			if ( isset( $input['title'] ) )      $settings['title']      = sanitize_text_field( $input['title'] );
			if ( isset( $input['cost'] ) )        $settings['cost']       = sanitize_text_field( $input['cost'] );
			if ( isset( $input['min_amount'] ) )  $settings['min_amount'] = sanitize_text_field( $input['min_amount'] );
			update_option( $option_key, $settings );
		}
		return array( 'success' => true, 'instance_id' => $instance_id, 'message' => 'Shipping method added' );
	}

	public static function delete_shipping_zone( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$zone = WC_Shipping_Zones::get_zone( (int) $input['zone_id'] );
		if ( ! $zone ) return array( 'success' => false, 'message' => 'Zone not found' );
		$zone->delete();
		return array( 'success' => true, 'message' => 'Shipping zone deleted' );
	}

	// ---- TAX ----

	public static function get_tax_rates( $input = null ) {
		if ( $err = self::woo_check() ) return $err;
		global $wpdb;
		$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_priority, tax_rate_order" );
		if ( ! is_array( $rates ) ) $rates = array();
		return array( 'success' => true, 'count' => count( $rates ), 'tax_rates' => $rates );
	}

	public static function create_tax_rate( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$tax_rate = array(
			'tax_rate_country'  => strtoupper( sanitize_text_field( $input['country'] ) ),
			'tax_rate_state'    => strtoupper( sanitize_text_field( $input['state'] ?? '' ) ),
			'tax_rate'          => sanitize_text_field( $input['rate'] ),
			'tax_rate_name'     => sanitize_text_field( $input['name'] ),
			'tax_rate_priority' => (int) ( $input['priority'] ?? 1 ),
			'tax_rate_compound' => (int) ( $input['compound'] ?? 0 ),
			'tax_rate_shipping' => (int) ( $input['shipping'] ?? 1 ),
			'tax_rate_class'    => sanitize_text_field( $input['class'] === 'standard' ? '' : $input['class'] ?? '' ),
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
		return array( 'success' => true, 'tax_rate_id' => $tax_rate_id, 'message' => 'Tax rate created' );
	}

	public static function delete_tax_rate( $input ) {
		if ( $err = self::woo_check() ) return $err;
		WC_Tax::_delete_tax_rate( (int) $input['tax_rate_id'] );
		return array( 'success' => true, 'message' => 'Tax rate deleted' );
	}

	// ---- REPORTS ----

	public static function get_sales_report( $input ) {
		if ( $err = self::woo_check() ) return $err;
		global $wpdb;

		$period = $input['period'] ?? null;
		if ( $period ) {
			$now = current_time( 'Y-m-d' );
			switch ( $period ) {
				case 'today': $date_from = $now; $date_to = $now; break;
				case 'week':  $date_from = date( 'Y-m-d', strtotime( 'monday this week' ) ); $date_to = $now; break;
				case 'month': $date_from = date( 'Y-m-01' ); $date_to = $now; break;
				case 'year':  $date_from = date( 'Y-01-01' ); $date_to = $now; break;
				default:      $date_from = $now; $date_to = $now;
			}
		} else {
			$date_from = sanitize_text_field( $input['date_from'] ?? date( 'Y-m-01' ) );
			$date_to   = sanitize_text_field( $input['date_to'] ?? current_time( 'Y-m-d' ) );
		}

		$results = $wpdb->get_row( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID) as order_count,
			        SUM(pm.meta_value) as total_revenue
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
			 WHERE p.post_type = 'shop_order'
			   AND p.post_status IN ('wc-completed','wc-processing')
			   AND DATE(p.post_date) BETWEEN %s AND %s",
			$date_from, $date_to
		) );

		$revenue      = floatval( $results->total_revenue ?? 0 );
		$order_count  = intval( $results->order_count ?? 0 );

		return array(
			'success'            => true,
			'period'             => array( 'from' => $date_from, 'to' => $date_to ),
			'total_revenue'      => $revenue,
			'total_orders'       => $order_count,
			'average_order_value'=> $order_count > 0 ? round( $revenue / $order_count, 2 ) : 0,
		);
	}

	public static function get_top_products( $input ) {
		if ( $err = self::woo_check() ) return $err;
		global $wpdb;
		$limit = (int) ( $input['limit'] ?? 10 );
		$where = '';
		if ( ! empty( $input['date_from'] ) && ! empty( $input['date_to'] ) ) {
			$where = $wpdb->prepare( " AND DATE(p.post_date) BETWEEN %s AND %s", $input['date_from'], $input['date_to'] );
		}
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT oi.order_item_name as product_name,
			        oim.meta_value as product_id,
			        SUM(oim2.meta_value) as qty_sold,
			        SUM(oim3.meta_value) as revenue
			 FROM {$wpdb->prefix}woocommerce_order_items oi
			 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim  ON oi.order_item_id = oim.order_item_id AND oim.meta_key  = '_product_id'
			 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2 ON oi.order_item_id = oim2.order_item_id AND oim2.meta_key = '_qty'
			 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim3 ON oi.order_item_id = oim3.order_item_id AND oim3.meta_key = '_line_total'
			 INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
			 WHERE p.post_status IN ('wc-completed','wc-processing') $where
			 GROUP BY oim.meta_value
			 ORDER BY qty_sold DESC
			 LIMIT %d",
			$limit
		) );
		return array( 'success' => true, 'count' => count( $results ), 'products' => $results );
	}

	// ---- CUSTOMER ----

	public static function update_customer( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$customer = new WC_Customer( (int) $input['customer_id'] );
		if ( ! $customer->get_id() ) return array( 'success' => false, 'message' => 'Customer not found' );
		if ( isset( $input['first_name'] ) )         $customer->set_first_name( sanitize_text_field( $input['first_name'] ) );
		if ( isset( $input['last_name'] ) )          $customer->set_last_name( sanitize_text_field( $input['last_name'] ) );
		if ( isset( $input['email'] ) )              $customer->set_email( sanitize_email( $input['email'] ) );
		if ( isset( $input['billing_address_1'] ) )  $customer->set_billing_address_1( sanitize_text_field( $input['billing_address_1'] ) );
		if ( isset( $input['billing_city'] ) )       $customer->set_billing_city( sanitize_text_field( $input['billing_city'] ) );
		if ( isset( $input['billing_country'] ) )    $customer->set_billing_country( sanitize_text_field( $input['billing_country'] ) );
		if ( isset( $input['billing_phone'] ) )      $customer->set_billing_phone( sanitize_text_field( $input['billing_phone'] ) );
		if ( isset( $input['shipping_address_1'] ) ) $customer->set_shipping_address_1( sanitize_text_field( $input['shipping_address_1'] ) );
		if ( isset( $input['shipping_city'] ) )      $customer->set_shipping_city( sanitize_text_field( $input['shipping_city'] ) );
		if ( isset( $input['shipping_country'] ) )   $customer->set_shipping_country( sanitize_text_field( $input['shipping_country'] ) );
		$customer->save();
		return array( 'success' => true, 'message' => 'Customer updated' );
	}

	public static function get_customer_orders( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$args = array(
			'customer_id' => (int) $input['customer_id'],
			'limit'       => (int) ( $input['per_page'] ?? 20 ),
			'return'      => 'objects',
		);
		if ( ! empty( $input['status'] ) ) $args['status'] = sanitize_text_field( $input['status'] );
		$orders = wc_get_orders( $args );
		$result = array_map( fn($o) => array(
			'id'       => $o->get_id(),
			'status'   => $o->get_status(),
			'total'    => $o->get_total(),
			'currency' => $o->get_currency(),
			'date'     => $o->get_date_created() ? $o->get_date_created()->date( 'Y-m-d H:i:s' ) : null,
		), $orders );
		return array( 'success' => true, 'count' => count( $result ), 'orders' => $result );
	}

	// ---- SETTINGS ----

	public static function get_woo_settings( $input = null ) {
		if ( $err = self::woo_check() ) return $err;
		$group = sanitize_text_field( $input['group'] ?? 'general' );
		$map   = array(
			'general'  => array( 'woocommerce_store_address', 'woocommerce_store_city', 'woocommerce_default_country', 'woocommerce_currency', 'woocommerce_currency_pos', 'woocommerce_price_thousand_sep', 'woocommerce_price_decimal_sep', 'woocommerce_price_num_decimals' ),
			'products' => array( 'woocommerce_weight_unit', 'woocommerce_dimension_unit', 'woocommerce_enable_reviews', 'woocommerce_review_rating_required', 'woocommerce_manage_stock', 'woocommerce_stock_email_recipient', 'woocommerce_notify_low_stock_amount', 'woocommerce_notify_no_stock_amount' ),
			'tax'      => array( 'woocommerce_calc_taxes', 'woocommerce_prices_include_tax', 'woocommerce_tax_based_on', 'woocommerce_shipping_tax_class', 'woocommerce_tax_round_at_subtotal', 'woocommerce_tax_display_shop', 'woocommerce_tax_display_cart' ),
			'shipping' => array( 'woocommerce_ship_to_destination', 'woocommerce_shipping_debug_mode' ),
			'checkout' => array( 'woocommerce_checkout_page_id', 'woocommerce_cart_page_id', 'woocommerce_enable_guest_checkout', 'woocommerce_enable_checkout_login_reminder', 'woocommerce_enable_signup_and_login_from_checkout', 'woocommerce_registration_generate_password' ),
			'account'  => array( 'woocommerce_myaccount_page_id', 'woocommerce_enable_myaccount_registration' ),
			'email'    => array( 'woocommerce_email_from_name', 'woocommerce_email_from_address', 'woocommerce_email_header_image', 'woocommerce_email_footer_text', 'woocommerce_email_base_color' ),
		);
		$keys     = $map[ $group ] ?? $map['general'];
		$settings = array();
		foreach ( $keys as $key ) {
			$settings[ $key ] = get_option( $key );
		}
		return array( 'success' => true, 'group' => $group, 'settings' => $settings );
	}

	public static function update_woo_setting( $input ) {
		if ( $err = self::woo_check() ) return $err;
		$option_name = sanitize_key( $input['option_name'] );
		if ( strpos( $option_name, 'woocommerce_' ) !== 0 ) {
			return array( 'success' => false, 'message' => 'Only woocommerce_ options are allowed via this ability' );
		}
		update_option( $option_name, $input['option_value'] );
		return array( 'success' => true, 'option' => $option_name, 'value' => get_option( $option_name ), 'message' => 'Setting updated' );
	}
}
