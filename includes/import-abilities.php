<?php
/**
 * WooCommerce Product Importer Ability
 * Imports products from any public WooCommerce store (no API keys needed)
 * Uses WC Store API (wc/store/v1) — publicly accessible
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WMC_Import_Abilities {

	private static function register( $name, $args ) {
		if ( ! function_exists( 'wp_register_ability' ) ) return null;
		if ( ! isset( $args['meta'] ) )         $args['meta']            = array();
		if ( ! isset( $args['meta']['mcp'] ) )  $args['meta']['mcp']     = array();
		$args['meta']['show_in_rest']    = true;
		$args['meta']['mcp']['public']   = true;
		return wp_register_ability( $name, $args );
	}

	public static function register_all() {
		self::register_import_abilities();
	}

	private static function register_import_abilities() {

		// ---------- preview-woo-import ----------
		self::register( 'wmc/preview-woo-import', array(
			'label'       => 'Preview WooCommerce Import',
			'description' => 'Preview products available to import from a WooCommerce store (no API key needed)',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'source_url'    => array( 'type' => 'string', 'description' => 'Target WooCommerce store URL (e.g. https://example.com)' ),
					'category_slug' => array( 'type' => 'string', 'description' => 'Filter by category slug (optional)' ),
					'search'        => array( 'type' => 'string', 'description' => 'Search keyword (optional)' ),
					'per_page'      => array( 'type' => 'integer', 'default' => 20, 'description' => 'Products per page (max 100)' ),
					'page'          => array( 'type' => 'integer', 'default' => 1 ),
				),
				'required' => array( 'source_url' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'preview_import' ),
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		) );

		// ---------- import-woo-products ----------
		self::register( 'wmc/import-woo-products', array(
			'label'       => 'Import Products from WooCommerce Store',
			'description' => 'Import products from any public WooCommerce store including images, prices, categories, descriptions',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'source_url'         => array( 'type' => 'string', 'description' => 'Source WooCommerce store URL (e.g. https://competitor.com)' ),
					'category_slug'      => array( 'type' => 'string', 'description' => 'Import only products from this category slug (optional)' ),
					'search'             => array( 'type' => 'string', 'description' => 'Import only products matching this keyword (optional)' ),
					'per_page'           => array( 'type' => 'integer', 'default' => 10, 'description' => 'How many products to import in this batch (max 50)' ),
					'page'               => array( 'type' => 'integer', 'default' => 1, 'description' => 'Page number for pagination' ),
					'import_images'      => array( 'type' => 'boolean', 'default' => true, 'description' => 'Download and import product images' ),
					'import_categories'  => array( 'type' => 'boolean', 'default' => true, 'description' => 'Create matching categories on your store' ),
					'status'             => array( 'type' => 'string', 'enum' => array( 'publish', 'draft', 'private' ), 'default' => 'draft', 'description' => 'Status for imported products' ),
					'price_adjustment'   => array(
						'type' => 'object',
						'description' => 'Optional price adjustment (e.g. increase by 20%)',
						'properties' => array(
							'type'    => array( 'type' => 'string', 'enum' => array( 'percent_increase', 'percent_decrease', 'fixed_increase', 'fixed_decrease', 'set_fixed' ) ),
							'amount'  => array( 'type' => 'number' ),
						),
					),
					'skip_existing'      => array( 'type' => 'boolean', 'default' => true, 'description' => 'Skip products with same name if already exists' ),
					'product_ids'        => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Import only specific product IDs from source store (optional)' ),
				),
				'required' => array( 'source_url' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'import_products' ),
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		) );

		// ---------- import-woo-categories ----------
		self::register( 'wmc/import-woo-categories', array(
			'label'       => 'Import Categories from WooCommerce Store',
			'description' => 'Import product categories from any public WooCommerce store',
			'category'    => 'wp-content-manager',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'source_url' => array( 'type' => 'string', 'description' => 'Source WooCommerce store URL' ),
				),
				'required' => array( 'source_url' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( self::class, 'import_categories' ),
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		) );
	}

	// ================================================================
	//  HELPERS
	// ================================================================

	/**
	 * Fetch from Store API (public, no auth)
	 */
	private static function store_api_fetch( $base_url, $endpoint, $params = array() ) {
		$base_url = rtrim( $base_url, '/' );
		$url      = $base_url . '/wp-json/wc/store/v1/' . ltrim( $endpoint, '/' );
		if ( ! empty( $params ) ) $url .= '?' . http_build_query( $params );

		$response = wp_remote_get( $url, array(
			'timeout'    => 30,
			'user-agent' => 'Mozilla/5.0 (compatible; WP-Importer/1.0)',
			'headers'    => array( 'Accept' => 'application/json' ),
		) );

		if ( is_wp_error( $response ) ) return $response;
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error( 'api_error', "Store API returned HTTP {$code} for {$url}" );
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Adjust price based on user's price_adjustment setting
	 */
	private static function adjust_price( $price, $adjustment ) {
		if ( empty( $adjustment ) || empty( $adjustment['type'] ) || ! isset( $adjustment['amount'] ) ) {
			return $price;
		}
		$amount = (float) $adjustment['amount'];
		switch ( $adjustment['type'] ) {
			case 'percent_increase': return round( $price * ( 1 + $amount / 100 ), 2 );
			case 'percent_decrease': return round( $price * ( 1 - $amount / 100 ), 2 );
			case 'fixed_increase':   return round( $price + $amount, 2 );
			case 'fixed_decrease':   return round( $price - $amount, 2 );
			case 'set_fixed':        return round( $amount, 2 );
			default:                 return $price;
		}
	}

	/**
	 * Parse Store API price (Store API returns price in minor units as string)
	 * e.g. "1999" means 19.99 with 2 decimal places
	 */
	private static function parse_price( $price_data, $key = 'price' ) {
		if ( empty( $price_data ) ) return '';
		$raw      = isset( $price_data[ $key ] ) ? (int) $price_data[ $key ] : 0;
		$decimals = isset( $price_data['currency_minor_unit'] ) ? (int) $price_data['currency_minor_unit'] : 2;
		if ( $raw === 0 ) return '';
		return number_format( $raw / pow( 10, $decimals ), $decimals, '.', '' );
	}

	/**
	 * Find or create a product category by name+slug
	 */
	private static function get_or_create_category( $name, $slug, $parent_id = 0 ) {
		$existing = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $existing ) return $existing->term_id;
		$result = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent_id ) );
		return is_wp_error( $result ) ? 0 : $result['term_id'];
	}

	/**
	 * Sideload an image from URL into WP media library
	 */
	private static function import_image( $image_url, $product_id, $alt = '' ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$attachment_id = media_sideload_image( $image_url, $product_id, $alt, 'id' );
		if ( is_wp_error( $attachment_id ) ) return 0;
		return (int) $attachment_id;
	}

	/**
	 * Create a single WooCommerce product from Store API product data
	 */
	private static function create_product_from_data( $item, $args ) {
		if ( ! class_exists( 'WC_Product_Simple' ) ) return array( 'success' => false, 'message' => 'WooCommerce not active' );

		$skip_existing    = $args['skip_existing']    ?? true;
		$import_images    = $args['import_images']    ?? true;
		$import_categories= $args['import_categories']?? true;
		$status           = $args['status']           ?? 'draft';
		$price_adjustment = $args['price_adjustment'] ?? null;

		$name = wp_strip_all_tags( $item['name'] ?? 'Imported Product' );

		// Skip if exists
		if ( $skip_existing ) {
			$existing = get_posts( array(
				'post_type'   => 'product',
				'post_status' => 'any',
				'title'       => $name,
				'numberposts' => 1,
				'fields'      => 'ids',
			) );
			if ( ! empty( $existing ) ) {
				return array( 'success' => false, 'skipped' => true, 'message' => "Already exists: {$name}" );
			}
		}

		// Prices
		$prices        = $item['prices'] ?? array();
		$regular_price = self::parse_price( $prices, 'regular_price' );
		$sale_price    = self::parse_price( $prices, 'sale_price' );
		$price         = self::parse_price( $prices, 'price' );
		if ( empty( $regular_price ) ) $regular_price = $price;

		if ( $price_adjustment && $regular_price ) {
			$regular_price = self::adjust_price( (float) $regular_price, $price_adjustment );
			if ( $sale_price ) $sale_price = self::adjust_price( (float) $sale_price, $price_adjustment );
		}

		// Create product
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_status( $status );
		$product->set_description( wp_kses_post( $item['description'] ?? '' ) );
		$product->set_short_description( wp_kses_post( $item['short_description'] ?? '' ) );
		$product->set_slug( $item['slug'] ?? '' );
		if ( $regular_price ) $product->set_regular_price( (string) $regular_price );
		if ( $sale_price )    $product->set_sale_price( (string) $sale_price );

		// Stock
		$product->set_manage_stock( false );
		if ( isset( $item['is_in_stock'] ) ) {
			$product->set_stock_status( $item['is_in_stock'] ? 'instock' : 'outofstock' );
		}

		// Categories
		if ( $import_categories && ! empty( $item['categories'] ) ) {
			$cat_ids = array();
			foreach ( $item['categories'] as $cat ) {
				$cat_id = self::get_or_create_category( $cat['name'], $cat['slug'] );
				if ( $cat_id ) $cat_ids[] = $cat_id;
			}
			if ( $cat_ids ) $product->set_category_ids( $cat_ids );
		}

		// Tags
		if ( ! empty( $item['tags'] ) ) {
			$tag_ids = array();
			foreach ( $item['tags'] as $tag ) {
				$existing = get_term_by( 'slug', $tag['slug'], 'product_tag' );
				if ( $existing ) {
					$tag_ids[] = $existing->term_id;
				} else {
					$result = wp_insert_term( $tag['name'], 'product_tag', array( 'slug' => $tag['slug'] ) );
					if ( ! is_wp_error( $result ) ) $tag_ids[] = $result['term_id'];
				}
			}
			if ( $tag_ids ) $product->set_tag_ids( $tag_ids );
		}

		// Save first to get ID
		$product_id = $product->save();
		if ( ! $product_id ) return array( 'success' => false, 'message' => 'Failed to create product' );

		// Images
		$imported_images = 0;
		if ( $import_images && ! empty( $item['images'] ) ) {
			$gallery_ids = array();
			foreach ( $item['images'] as $idx => $img ) {
				$img_url = $img['src'] ?? '';
				if ( ! $img_url ) continue;
				// Remove query strings that might block download
				$img_url = preg_replace( '/\?.*$/', '', $img_url );
				$att_id  = self::import_image( $img_url, $product_id, $img['alt'] ?? $name );
				if ( $att_id ) {
					if ( $idx === 0 ) {
						$product->set_image_id( $att_id );
					} else {
						$gallery_ids[] = $att_id;
					}
					$imported_images++;
				}
			}
			if ( $gallery_ids ) $product->set_gallery_image_ids( $gallery_ids );
			$product->save();
		}

		// Store source info in meta
		update_post_meta( $product_id, '_wmc_imported_from', $item['permalink'] ?? '' );
		update_post_meta( $product_id, '_wmc_source_id', $item['id'] ?? 0 );

		return array(
			'success'         => true,
			'product_id'      => $product_id,
			'name'            => $name,
			'regular_price'   => $regular_price,
			'sale_price'      => $sale_price,
			'images_imported' => $imported_images,
			'status'          => $status,
		);
	}

	// ================================================================
	//  CALLBACKS
	// ================================================================

	public static function preview_import( $input ) {
		$source_url = esc_url_raw( rtrim( $input['source_url'], '/' ) );
		$params     = array(
			'per_page' => min( (int) ( $input['per_page'] ?? 20 ), 100 ),
			'page'     => (int) ( $input['page'] ?? 1 ),
		);
		if ( ! empty( $input['category_slug'] ) ) $params['category'] = sanitize_text_field( $input['category_slug'] );
		if ( ! empty( $input['search'] ) )        $params['search']   = sanitize_text_field( $input['search'] );

		$data = self::store_api_fetch( $source_url, 'products', $params );
		if ( is_wp_error( $data ) ) return array( 'success' => false, 'message' => $data->get_error_message() );
		if ( ! is_array( $data ) )  return array( 'success' => false, 'message' => 'Unexpected response from source store' );

		$preview = array();
		foreach ( $data as $item ) {
			$prices         = $item['prices'] ?? array();
			$regular_price  = self::parse_price( $prices, 'regular_price' );
			$sale_price     = self::parse_price( $prices, 'sale_price' );
			$preview[] = array(
				'id'            => $item['id'],
				'name'          => wp_strip_all_tags( $item['name'] ?? '' ),
				'slug'          => $item['slug'] ?? '',
				'regular_price' => $regular_price,
				'sale_price'    => $sale_price,
				'categories'    => array_map( fn($c) => $c['name'], $item['categories'] ?? array() ),
				'images_count'  => count( $item['images'] ?? array() ),
				'first_image'   => $item['images'][0]['src'] ?? null,
				'in_stock'      => $item['is_in_stock'] ?? null,
				'permalink'     => $item['permalink'] ?? '',
			);
		}

		// Also try to get categories list
		$cats_data = self::store_api_fetch( $source_url, 'products/categories', array( 'per_page' => 100 ) );
		$categories = array();
		if ( is_array( $cats_data ) ) {
			foreach ( $cats_data as $cat ) {
				$categories[] = array( 'id' => $cat['id'], 'name' => $cat['name'], 'slug' => $cat['slug'], 'count' => $cat['count'] ?? 0 );
			}
		}

		return array(
			'success'    => true,
			'source'     => $source_url,
			'count'      => count( $preview ),
			'page'       => $params['page'],
			'categories' => $categories,
			'products'   => $preview,
			'note'       => 'Use wmc/import-woo-products to actually import these products',
		);
	}

	public static function import_products( $input ) {
		if ( ! class_exists( 'WooCommerce' ) ) return array( 'success' => false, 'message' => 'WooCommerce is not active' );

		$source_url = esc_url_raw( rtrim( $input['source_url'], '/' ) );
		$per_page   = min( (int) ( $input['per_page'] ?? 10 ), 50 );

		// Build API params
		$params = array(
			'per_page' => $per_page,
			'page'     => (int) ( $input['page'] ?? 1 ),
		);
		if ( ! empty( $input['category_slug'] ) ) $params['category'] = sanitize_text_field( $input['category_slug'] );
		if ( ! empty( $input['search'] ) )        $params['search']   = sanitize_text_field( $input['search'] );

		// If specific IDs requested, fetch each individually
		if ( ! empty( $input['product_ids'] ) ) {
			$items = array();
			foreach ( array_slice( $input['product_ids'], 0, $per_page ) as $pid ) {
				$item = self::store_api_fetch( $source_url, 'products/' . (int) $pid );
				if ( ! is_wp_error( $item ) && is_array( $item ) ) $items[] = $item;
			}
		} else {
			$items = self::store_api_fetch( $source_url, 'products', $params );
		}

		if ( is_wp_error( $items ) ) return array( 'success' => false, 'message' => $items->get_error_message() );
		if ( ! is_array( $items ) ) return array( 'success' => false, 'message' => 'Unexpected response format from source store' );
		if ( empty( $items ) )      return array( 'success' => true, 'message' => 'No products found on page ' . ( $input['page'] ?? 1 ), 'imported' => 0, 'results' => array() );

		$results  = array();
		$imported = 0;
		$skipped  = 0;
		$failed   = 0;

		foreach ( $items as $item ) {
			$result = self::create_product_from_data( $item, $input );
			if ( ! empty( $result['skipped'] ) ) {
				$skipped++;
			} elseif ( $result['success'] ) {
				$imported++;
			} else {
				$failed++;
			}
			$results[] = $result;
		}

		return array(
			'success'  => true,
			'source'   => $source_url,
			'page'     => $input['page'] ?? 1,
			'total'    => count( $items ),
			'imported' => $imported,
			'skipped'  => $skipped,
			'failed'   => $failed,
			'status'   => $input['status'] ?? 'draft',
			'results'  => $results,
			'tip'      => $imported > 0 ? "Products imported as '{$input['status']}'. Review them in WooCommerce > Products, then publish." : null,
		);
	}

	public static function import_categories( $input ) {
		$source_url = esc_url_raw( rtrim( $input['source_url'], '/' ) );
		$data       = self::store_api_fetch( $source_url, 'products/categories', array( 'per_page' => 100 ) );

		if ( is_wp_error( $data ) ) return array( 'success' => false, 'message' => $data->get_error_message() );
		if ( ! is_array( $data ) ) return array( 'success' => false, 'message' => 'Could not fetch categories' );

		$results  = array();
		$created  = 0;
		$existing = 0;

		foreach ( $data as $cat ) {
			$name   = $cat['name'] ?? '';
			$slug   = $cat['slug'] ?? '';
			$parent = $cat['parent'] ?? 0;
			if ( ! $name || ! $slug ) continue;

			$found = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $found ) {
				$existing++;
				$results[] = array( 'name' => $name, 'slug' => $slug, 'status' => 'already exists', 'id' => $found->term_id );
				continue;
			}
			$result = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
			if ( is_wp_error( $result ) ) {
				$results[] = array( 'name' => $name, 'slug' => $slug, 'status' => 'error', 'message' => $result->get_error_message() );
			} else {
				$created++;
				$results[] = array( 'name' => $name, 'slug' => $slug, 'status' => 'created', 'id' => $result['term_id'] );
			}
		}

		return array(
			'success'  => true,
			'source'   => $source_url,
			'total'    => count( $data ),
			'created'  => $created,
			'existing' => $existing,
			'results'  => $results,
		);
	}
}
