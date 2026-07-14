---
name: woocommerce-product-seo-optimizer
description: SEO audit and fixes specific to WooCommerce product pages — title/description,
  Product schema, image alt text, and category/tag SEO. Use when asked to optimize product SEO,
  fix store SEO, or audit WooCommerce product pages.
---

# WooCommerce Product SEO Optimizer

Product pages need Product schema built from real price/stock data (see `schema-markup-builder`), unique meta per product (default WooCommerce meta is usually generic and duplicated), and category-level SEO since category pages often rank for broad "buy X" queries.

## Steps

1. `wmc/detect-seo-setup`
2. `wmc/get-woo-products` — pull the catalog (paginate for large stores).
3. Per product: `wmc/get-woo-product-detail` (price, stock, description) + `wmc/get-post-seo` (current meta) + `wmc/get-schema-markup` (existing structured data).
4. `wmc/get-media-without-alt` scoped to product gallery images — cross-reference with the `image-seo-fixer` skill.
5. `wmc/get-woo-product-categories` / `wmc/get-woo-product-tags` + `wmc/get-term-seo` — check category/tag pages have their own unique meta rather than defaults.
6. Build fixes: unique SEO title/description per product, Product schema via `wmc/add-schema-markup` using real price/currency/availability from step 3, alt text for gallery images, category-level meta via `wmc/set-term-seo`.
7. Apply via `wmc/set-post-seo`, `wmc/add-schema-markup`, `wmc/update-media`, `wmc/set-term-seo` — confirm the product list with the user before bulk-applying.

## Output format

Per-product checklist sorted by most gaps first: Product | Unique Title ✓/✗ | Unique Description ✓/✗ | Schema ✓/✗ | Images with Alt (X/Y) | Category Meta ✓/✗.
