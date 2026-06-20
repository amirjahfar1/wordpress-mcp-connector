# WordPress MCP Connector

A WordPress plugin that exposes **142 abilities** across all major WordPress and WooCommerce features through the [WordPress Abilities API](https://developer.wordpress.org/abilities-api/) for seamless MCP (Model Context Protocol) integration. Control every ability individually from the WordPress admin dashboard.

**Current Version:** 3.0.0 | **Requires:** WordPress 6.9+ | **PHP:** 7.4+ | **WooCommerce:** 5.0+ (optional)

---

## Table of Contents

- [Installation](#installation)
- [All 142 Abilities](#all-142-abilities)
  - [Posts](#-posts-4-abilities)
  - [Pages](#-pages-4-abilities)
  - [Categories & Tags](#️-categories--tags-8-abilities)
  - [Media](#️-media-9-abilities)
  - [Comments](#-comments-3-abilities)
  - [Users](#-users-4-abilities)
  - [Settings](#️-settings-2-abilities)
  - [Menus](#-menus-3-abilities)
  - [Widgets](#-widgets-2-abilities)
  - [Themes](#-themes-4-abilities)
  - [SEO Meta](#-seo-meta-4-abilities)
  - [Plugin Management](#-plugin-management-5-abilities)
  - [User Roles](#-user-roles-2-abilities)
  - [System / Maintenance](#️-system--maintenance-3-abilities)
  - [WooCommerce Core](#-woocommerce-core-6-abilities)
  - [WooCommerce Extended](#-woocommerce-extended-47-abilities)
  - [Developer Toolkit](#-developer-toolkit-tier-1--2-17-abilities)
  - [SEO & Performance](#-seo--performance-tier-4-6-abilities)
  - [Security & Backup](#-security--backup-tier-5-6-abilities)
  - [Product Importer](#-product-importer-3-abilities)
- [Admin Dashboard](#admin-dashboard)
- [API Usage](#api-usage)
- [Permissions](#permissions)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)

---

## Installation

1. Go to [Releases](https://github.com/amirjahfar1/wordpress-mcp-connector) → **Download ZIP**
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Activate**
4. Verify at: `https://yoursite.com/wp-json/wmc/v1/diagnose`

**Or manually via FTP:**
```
wp-content/plugins/wordpress-mcp-connector/
```

---

## All 142 Abilities

All abilities are registered under category `wp-content-manager` and accessible via:
```
POST https://yoursite.com/wp-json/wp-abilities/v1/abilities/{ability-name}/run
```

---

### 📝 Posts (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-posts` | List posts with pagination, search, filtering | `per_page`, `page`, `search`, `status`, `category_id`, `tag_id` |
| `wmc/create-post` | Create a new post | `title`, `content`, `status`, `categories`, `tags`, `date` |
| `wmc/update-post` | Update an existing post | `id`, `title`, `content`, `status`, `date` |
| `wmc/delete-post` | Permanently delete a post | `id` |

**Scheduling:** Pass a `date` field to schedule for future publish.

---

### 📄 Pages (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-pages` | List pages with filtering | `per_page`, `page`, `search`, `status` |
| `wmc/create-page` | Create a new page | `title`, `content`, `status`, `parent`, `date` |
| `wmc/update-page` | Update an existing page | `id`, `title`, `content`, `status`, `date` |
| `wmc/delete-page` | Permanently delete a page | `id` |

---

### 🏷️ Categories & Tags (8 abilities)

| Ability | Description |
|---|---|
| `wmc/get-categories` | List all categories |
| `wmc/create-category` | Create a category |
| `wmc/update-category` | Update a category |
| `wmc/delete-category` | Delete a category |
| `wmc/get-tags` | List all tags |
| `wmc/create-tag` | Create a tag |
| `wmc/update-tag` | Update a tag |
| `wmc/delete-tag` | Delete a tag |

---

### 🖼️ Media (9 abilities)

| Ability | Description |
|---|---|
| `wmc/get-media` | List media library files |
| `wmc/create-media` | Upload a media file from URL |
| `wmc/update-media` | Update a single media item |
| `wmc/delete-media` | Delete a media file |
| `wmc/get-media-details` | Full metadata: alt, caption, dimensions, file size, attached post |
| `wmc/get-media-without-alt` | Find all images missing alt text — SEO audit |
| `wmc/search-media` | Advanced filter: MIME type, date range, attached status, alt presence |
| `wmc/bulk-update-media` | Update alt, title, description, caption for multiple items in one call |
| `wmc/bulk-delete-media` | Delete multiple items by ID array in one call |

---

### 💬 Comments (3 abilities)

| Ability | Description |
|---|---|
| `wmc/get-comments` | List comments with filtering |
| `wmc/moderate-comment` | Approve, hold, or mark as spam |
| `wmc/delete-comment` | Delete a comment |

---

### 👤 Users (4 abilities)

| Ability | Description |
|---|---|
| `wmc/get-users` | List users with role filtering |
| `wmc/create-user` | Create a new user |
| `wmc/update-user` | Update a user |
| `wmc/delete-user` | Delete a user |

---

### ⚙️ Settings (2 abilities)

| Ability | Description |
|---|---|
| `wmc/get-options` | Retrieve WordPress options |
| `wmc/update-option` | Update a WordPress option |

---

### 📋 Menus (3 abilities)

| Ability | Description |
|---|---|
| `wmc/get-menus` | List all menus with items |
| `wmc/create-menu` | Create a navigation menu |
| `wmc/delete-menu` | Delete a menu |

---

### 🎨 Widgets (2 abilities)

| Ability | Description |
|---|---|
| `wmc/get-sidebars` | List widget areas and registered widgets |
| `wmc/update-widget-option` | Update a widget's settings |

---

### 🎭 Themes (4 abilities)

| Ability | Description |
|---|---|
| `wmc/get-themes` | List all installed themes |
| `wmc/activate-theme` | Switch the active theme |
| `wmc/get-theme-mods` | Get current theme customization settings |
| `wmc/update-theme-mod` | Update a theme customization value |

---

### 🔍 SEO Meta (4 abilities)

Supports **Yoast SEO**, **Rank Math**, and **All in One SEO**. Auto-detects installed plugin.

| Ability | Description |
|---|---|
| `wmc/get-post-seo-meta` | Read SEO metadata from a post |
| `wmc/update-post-seo-meta` | Update SEO metadata on a post |
| `wmc/get-page-seo-meta` | Read SEO metadata from a page |
| `wmc/update-page-seo-meta` | Update SEO metadata on a page |

---

### 🔌 Plugin Management (5 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-plugins` | List all installed plugins with status | `status` (`all`/`active`/`inactive`) |
| `wmc/activate-plugin` | Activate an installed plugin | `plugin` |
| `wmc/deactivate-plugin` | Deactivate an active plugin | `plugin` |
| `wmc/install-plugin` | Install from WordPress.org or ZIP URL | `slug` or `zip_url`, `activate` |
| `wmc/delete-plugin` | Deactivate and permanently delete a plugin | `plugin` |

---

### 👥 User Roles (2 abilities)

| Ability | Description |
|---|---|
| `wmc/get-roles` | List all WordPress user roles |
| `wmc/assign-role` | Assign a role to a user |

---

### ⚙️ System / Maintenance (3 abilities)

| Ability | Description |
|---|---|
| `wmc/get-site-health` | Full site health report (WP, PHP, DB, server) |
| `wmc/clear-cache` | Clear WordPress cache (W3TC, WP Rocket, LiteSpeed, transients) |
| `wmc/get-cron-jobs` | List all scheduled cron jobs |

---

### 🛒 WooCommerce Core (6 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-products` | List WooCommerce products |
| `wmc/create-woo-product` | Create a new simple product |
| `wmc/update-woo-product` | Update an existing product |
| `wmc/get-woo-orders` | List orders with filtering |
| `wmc/update-woo-order-status` | Change an order's status |
| `wmc/get-woo-customers` | List WooCommerce customers |

---

### 🛒 WooCommerce Extended (47 abilities)

> No API keys required. All abilities run server-side using WooCommerce PHP classes.

#### Products (18)

| Ability | Description |
|---|---|
| `wmc/get-woo-product-detail` | Full product details including meta, gallery, attributes, variations |
| `wmc/delete-woo-product` | Permanently delete a product |
| `wmc/set-woo-product-image` | Set or replace the featured image |
| `wmc/get-woo-product-categories` | List all product categories with counts |
| `wmc/create-woo-product-category` | Create a new product category |
| `wmc/update-woo-product-category` | Update category name, slug, or description |
| `wmc/delete-woo-product-category` | Delete a product category |
| `wmc/get-woo-product-tags` | List all product tags |
| `wmc/create-woo-product-tag` | Create a new product tag |
| `wmc/delete-woo-product-tag` | Delete a product tag |
| `wmc/get-woo-product-meta` | Read any custom meta field on a product |
| `wmc/update-woo-product-meta` | Write any custom meta field on a product |
| `wmc/create-variable-product` | Create a variable product with attributes and variations |
| `wmc/get-woo-variations` | List all variations of a variable product |
| `wmc/update-woo-variation` | Update price, stock, or image of a variation |
| `wmc/delete-woo-variation` | Delete a specific variation |
| `wmc/assign-woo-product-categories` | Assign/replace categories on a product |
| `wmc/get-woo-low-stock` | List products below a stock threshold |

#### Orders (7)

| Ability | Description |
|---|---|
| `wmc/get-woo-order-detail` | Full order: line items, billing, shipping, meta |
| `wmc/add-woo-order-note` | Add a public or private note to an order |
| `wmc/create-woo-order` | Programmatically create a new order |
| `wmc/delete-woo-order` | Delete an order permanently |
| `wmc/create-woo-refund` | Issue a full or partial refund |
| `wmc/get-woo-order-notes` | List all notes on an order |
| `wmc/get-woo-customer-detail` | Full customer profile with order history |

#### Coupons (5)

| Ability | Description |
|---|---|
| `wmc/get-woo-coupons` | List all coupons |
| `wmc/create-woo-coupon` | Create a coupon (percent/fixed, expiry, usage limits) |
| `wmc/update-woo-coupon` | Update an existing coupon |
| `wmc/delete-woo-coupon` | Delete a coupon |
| `wmc/get-woo-coupon-usage` | See who used a coupon and when |

#### Reviews (4)

| Ability | Description |
|---|---|
| `wmc/get-woo-reviews` | List product reviews |
| `wmc/create-woo-review` | Create a product review |
| `wmc/update-woo-review` | Update or approve/reject a review |
| `wmc/delete-woo-review` | Delete a review |

#### Shipping (4)

| Ability | Description |
|---|---|
| `wmc/get-woo-shipping-zones` | List all shipping zones and methods |
| `wmc/create-woo-shipping-zone` | Create a new shipping zone |
| `wmc/add-woo-shipping-method` | Add flat rate, free shipping, etc. to a zone |
| `wmc/delete-woo-shipping-zone` | Delete a shipping zone |

#### Tax (3)

| Ability | Description |
|---|---|
| `wmc/get-woo-tax-rates` | List all tax rates |
| `wmc/create-woo-tax-rate` | Create a tax rate for a country/state |
| `wmc/delete-woo-tax-rate` | Delete a tax rate |

#### Reports (2)

| Ability | Description |
|---|---|
| `wmc/get-woo-sales-report` | Revenue, orders, refunds for today/week/month/year |
| `wmc/get-woo-top-products` | Best-selling products by quantity or revenue |

#### Customers (2)

| Ability | Description |
|---|---|
| `wmc/update-woo-customer` | Update any customer field |
| `wmc/get-woo-customer-orders` | All orders placed by a specific customer |

#### WooCommerce Settings (2)

| Ability | Description |
|---|---|
| `wmc/get-woo-settings` | Read any WooCommerce settings group |
| `wmc/update-woo-setting` | Update any individual WooCommerce setting |

---

### 🔧 Developer Toolkit — Tier 1 & 2 (17 abilities)

#### Tier 1 — Server Control (9)

| Ability | Description |
|---|---|
| `wmc/install-plugin` | Install plugin from WordPress.org (by slug) or ZIP URL |
| `wmc/install-theme` | Install theme from WordPress.org (by slug) or ZIP URL |
| `wmc/delete-plugin` | Deactivate and delete a plugin |
| `wmc/execute-php` | Execute raw PHP code on the server and return output |
| `wmc/get-options` | Read wp_options by key list or partial name search |
| `wmc/update-option` | Write any value to wp_options |
| `wmc/search-replace-db` | Search & replace in database — handles serialized data, dry-run mode |
| `wmc/get-error-logs` | Read PHP / WordPress / WooCommerce error logs (last N lines) |
| `wmc/get-server-info` | PHP version, memory, disk space, extensions, WP config |

#### Tier 2 — Filesystem & Developer (8)

| Ability | Description |
|---|---|
| `wmc/read-file` | Read any theme or plugin file |
| `wmc/write-file` | Write or overwrite any file (append mode available) |
| `wmc/list-files` | List files in a directory — recursive and extension filter |
| `wmc/run-wp-cli` | Run any WP-CLI command on the server |
| `wmc/manage-transients` | Get, set, delete, or flush all transients |
| `wmc/manage-redirects` | Add, delete, list 301/302 redirects (auto-enforced) |
| `wmc/get-post-meta` | Read all custom fields or a specific key on any post |
| `wmc/update-post-meta` | Set or delete a custom field on any post |

---

### 📈 SEO & Performance — Tier 4 (6 abilities)

| Ability | Description |
|---|---|
| `wmc/bulk-update-seo` | Bulk update SEO title + description — Yoast, Rank Math, AIOSEO auto-detected |
| `wmc/get-broken-links` | Scan post content for internal broken links |
| `wmc/get-404-logs` | List recent 404 errors (tracked automatically in background) |
| `wmc/manage-robots-txt` | Read or write robots.txt |
| `wmc/manage-htaccess` | Read, write, or append to .htaccess |
| `wmc/compress-images` | Regenerate image thumbnails for media items |

---

### 🔐 Security & Backup — Tier 5 (6 abilities)

| Ability | Description |
|---|---|
| `wmc/get-login-attempts` | List recent failed logins (tracked automatically) |
| `wmc/block-ip` | Block or unblock an IP address (enforced on every request) |
| `wmc/create-backup` | Export database as SQL or theme as ZIP to `wmc-backups/` folder |
| `wmc/list-backups` | List all backup files created by the connector |
| `wmc/manage-user-sessions` | List, or destroy all/other sessions for any user |
| `wmc/reset-user-password` | Reset password — manual or auto-generate |

---

### 🌐 Product Importer (3 abilities)

Import products from **any public WooCommerce store** — images, prices, categories, descriptions. No API keys needed on either site.

| Ability | Description |
|---|---|
| `wmc/preview-woo-import` | Preview products and categories from a source store — nothing imported yet |
| `wmc/import-woo-products` | Import products with all images (downloaded to media library), categories, tags, price adjustment support |
| `wmc/import-woo-categories` | Import only product categories from a source store |

**Example:**
```json
{
  "source_url": "https://competitor-store.com",
  "category_slug": "electronics",
  "per_page": 20,
  "status": "draft",
  "import_images": true,
  "price_adjustment": { "type": "percent_increase", "amount": 15 }
}
```

---

## Admin Dashboard

**Location:** WordPress Admin → MCP Connector

Every ability category has **Read** and **Write** toggles. When a toggle is off, calling the ability returns:

```json
{
  "success": false,
  "status": "disabled",
  "message": "Read operation is currently disabled",
  "disabled": true
}
```

---

## API Usage

### Authentication

```bash
# WordPress Application Password (recommended)
curl -u 'admin:xxxx xxxx xxxx xxxx xxxx xxxx' \
  -X POST https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-posts/run
```

### Examples

**Import products from another WooCommerce store:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"source_url":"https://store.com","category_slug":"shoes","per_page":10,"status":"draft"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/import-woo-products/run
```

**Install a plugin:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"slug":"contact-form-7","activate":true}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/install-plugin/run
```

**Search & replace in database:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"search":"http://old-domain.com","replace":"https://new-domain.com","dry_run":true}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/search-replace-db/run
```

**Block an IP address:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"action":"block","ip":"1.2.3.4","reason":"Too many failed logins"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/block-ip/run
```

**Create a database backup:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"type":"database","filename":"pre-update-backup"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/create-backup/run
```

**Read a theme file:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"path":"wp-content/themes/my-theme/functions.php"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/read-file/run
```

**Bulk update SEO:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"items":[{"post_id":1,"seo_title":"New Title","seo_description":"New desc"}],"plugin":"auto"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/bulk-update-seo/run
```

### Diagnostic Endpoint

```bash
curl -u 'admin:pass' https://yoursite.com/wp-json/wmc/v1/diagnose
```

Returns: plugin version, WP/PHP versions, hook fire status, `wmc_abilities_count`, full abilities list.

---

## Permissions

| Operation | Required Capability |
|---|---|
| All read/write operations | `manage_options` |
| Install / delete plugins & themes | `install_plugins` / `delete_plugins` / `install_themes` |
| WooCommerce operations | `manage_options` |
| User sessions / password reset | `manage_options` |

---

## Troubleshooting

**Abilities not showing?**
1. Confirm WordPress ≥ 6.9
2. Check plugin is activated
3. Visit `/wp-json/wmc/v1/diagnose` — look for `"wmc_abilities_count": 142`

**WooCommerce abilities not working?**
- Install and activate WooCommerce 5.0+

**Product import failing?**
- Confirm source site is a WooCommerce store with public Store API enabled
- Test: `https://source-site.com/wp-json/wc/store/v1/products` should return JSON

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

**v3.0.0** — WooCommerce Full Control (47), Developer Toolkit (29), Product Importer (3) — 142 total abilities  
**v2.5.0** — Modern card-based admin dashboard  
**v2.4.0** — Bulk & advanced media management (5 new)  
**v2.3.0** — Plugin management, User Roles, WooCommerce core, System maintenance (16 new)  
**v2.2.x** — Bug fixes for Abilities API registration on WP 6.9+  
**v2.2.0** — Post & Page scheduling  
**v2.1.0** — SEO Meta (Yoast, Rank Math, AIOSEO)  
**v2.0.0** — Settings, Menus, Widgets, Themes  
**v1.0.0** — Initial release

---

## License

GPL-2.0-or-later
