# WordPress MCP Connector — Changelog

---

## v3.7.0 — 2026-07-14

### Added — 13 SEO Skills + Non-Technical Setup Guide

Adds a `skills/` directory of Claude skills that chain the existing SEO/technical abilities into repeatable workflows, plus a `SETUP.md` written for non-technical users.

#### New Skills

- `on-page-seo-audit` — deep audit of a single post/page/product
- `site-wide-seo-health-check` — full-site scan for missing/duplicate meta, broken links, 404s, sitemap gaps
- `image-seo-fixer` — find and fix missing alt text, compress oversized images
- `broken-link-redirect-fixer` — detect broken links/404s and set up 301 redirects
- `schema-markup-builder` — add/verify JSON-LD schema (Article, Product, FAQ, HowTo, etc.)
- `meta-bulk-optimizer` — bulk-generate missing SEO titles/descriptions/keywords
- `duplicate-content-canonical-check` — find and fix duplicate meta with rewrites or canonical URLs
- `indexability-audit` — find pages accidentally blocked by noindex, robots.txt, or sitemap exclusion
- `slug-permalink-audit` — flag non-SEO-friendly slugs and fix permalink structure
- `woocommerce-product-seo-optimizer` — SEO audit/fixes for product pages and categories
- `social-preview-optimizer` — fix Open Graph/Twitter Card previews for social sharing
- `error-log-diagnostics` — diagnose PHP errors/site health issues causing SEO regressions
- `weekly-technical-seo-report` — master skill chaining the others into one prioritized report

#### Docs

- **Added:** `SETUP.md` — step-by-step, non-technical walkthrough covering Node.js installation (required for the `npx @automattic/mcp-wordpress-remote` connector), plugin installation, Application Password generation, and Claude Code/Desktop configuration
- **Added:** "Available Skills" and "Skill Usage Examples" sections in `README.md`
- **Fixed:** `WMC_VERSION` constant was out of sync with the plugin header version (`3.6.4` vs `3.6.7`) — both now read `3.7.0`

---

## v3.1.0 — 2026-06-20

### Simplified Connection — No OAuth, No Python Script

Removed all OAuth/token-based auth complexity. Connection now uses WordPress Application Passwords — the native, secure standard built into WordPress 5.6+.

#### What Changed

- **Removed:** OAuth flow, polling system, Python connect scripts (`wmc_connect.py`, `wmc_callback_server.py`)
- **Removed:** Bearer token authentication filter and custom token endpoints
- **Added:** Application Password generator built into the Settings page — select a user, click Generate, copy the JSON config, give it to Claude
- **Added:** Auto-redirect to Settings page immediately after plugin activation
- **Added:** Step-by-step setup UI with how-to guide directly on the settings page
- **Improved:** OAuth auth page completely redesigned with dark glassmorphism UI and SVG permission icons

#### New Connection Flow

```
Install plugin → Settings page opens automatically
→ Select admin user → Click "Generate"
→ Copy JSON config → Tell Claude: "Update my MCP config with this"
→ Restart Claude Code → Connected forever
```

No username/password to remember. No scripts to run. No OAuth pages. Just generate and paste.

---

## v3.0.0 — 2026-06-20

### Major Expansion: WooCommerce Full Control + Developer Toolkit + Product Importer

This is the biggest release since v1.0. Three completely new modules have been added bringing the total ability count from **63 → 142 abilities**.

---

### 🛒 Module 1: WooCommerce Extended (`includes/woocommerce-extended.php`)

**47 new WooCommerce abilities** — full control over every WooCommerce feature without any REST API keys. All abilities run server-side using WooCommerce PHP classes directly.

#### Products (18 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-product-detail` | Full product details including all meta, gallery, attributes, variations |
| `wmc/delete-woo-product` | Permanently delete a product |
| `wmc/set-woo-product-image` | Set or replace the featured image of a product |
| `wmc/get-woo-product-categories` | List all product categories with counts |
| `wmc/create-woo-product-category` | Create a new product category (with optional parent) |
| `wmc/update-woo-product-category` | Update category name, slug, or description |
| `wmc/delete-woo-product-category` | Delete a product category |
| `wmc/get-woo-product-tags` | List all product tags |
| `wmc/create-woo-product-tag` | Create a new product tag |
| `wmc/delete-woo-product-tag` | Delete a product tag |
| `wmc/get-woo-product-meta` | Read any custom meta field on a product |
| `wmc/update-woo-product-meta` | Write any custom meta field on a product |
| `wmc/create-variable-product` | Create a variable product with attributes and variations |
| `wmc/get-woo-variations` | List all variations of a variable product |
| `wmc/update-woo-variation` | Update price, stock, or image of a specific variation |
| `wmc/delete-woo-variation` | Delete a specific variation |
| `wmc/assign-woo-product-categories` | Assign/replace categories on a product |
| `wmc/get-woo-low-stock` | List products below a stock threshold |

#### Orders (7 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-order-detail` | Full order details: line items, billing, shipping, meta |
| `wmc/add-woo-order-note` | Add a public or private note to an order |
| `wmc/create-woo-order` | Programmatically create a new order |
| `wmc/delete-woo-order` | Delete an order permanently |
| `wmc/create-woo-refund` | Issue a full or partial refund on an order |
| `wmc/get-woo-order-notes` | List all notes on an order |
| `wmc/get-woo-customer-detail` | Full customer profile with order history |

#### Coupons (5 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-coupons` | List all coupons |
| `wmc/create-woo-coupon` | Create a coupon (percent/fixed, expiry, usage limits) |
| `wmc/update-woo-coupon` | Update an existing coupon |
| `wmc/delete-woo-coupon` | Delete a coupon |
| `wmc/get-woo-coupon-usage` | See who used a coupon and when |

#### Reviews (4 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-reviews` | List product reviews with filtering |
| `wmc/create-woo-review` | Create a product review |
| `wmc/update-woo-review` | Update or approve/reject a review |
| `wmc/delete-woo-review` | Delete a review |

#### Shipping (4 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-shipping-zones` | List all shipping zones and their methods |
| `wmc/create-woo-shipping-zone` | Create a new shipping zone |
| `wmc/add-woo-shipping-method` | Add a method (flat rate, free shipping, etc.) to a zone |
| `wmc/delete-woo-shipping-zone` | Delete a shipping zone |

#### Tax (3 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-tax-rates` | List all tax rates |
| `wmc/create-woo-tax-rate` | Create a tax rate for a country/state |
| `wmc/delete-woo-tax-rate` | Delete a tax rate |

#### Reports (2 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-sales-report` | Revenue, orders, refunds for today/week/month/year |
| `wmc/get-woo-top-products` | Best-selling products by quantity or revenue |

#### Customers (2 abilities)

| Ability | Description |
|---|---|
| `wmc/update-woo-customer` | Update any customer field (billing, shipping, meta) |
| `wmc/get-woo-customer-orders` | All orders placed by a specific customer |

#### WooCommerce Settings (2 abilities)

| Ability | Description |
|---|---|
| `wmc/get-woo-settings` | Read any WooCommerce settings group (general, products, tax, shipping, checkout, account, email) |
| `wmc/update-woo-setting` | Update any individual WooCommerce setting by group and key |

---

### 🔧 Module 2: Advanced Developer / Admin / Security (`includes/advanced-abilities.php`)

**29 new abilities** covering server control, file system, SEO, security, and backup — organized in 4 tiers.

#### Tier 1 — Full Server Control (9 abilities)

| Ability | Description |
|---|---|
| `wmc/install-plugin` | Install a plugin from WordPress.org by slug or from a ZIP URL |
| `wmc/install-theme` | Install a theme from WordPress.org by slug or from a ZIP URL |
| `wmc/delete-plugin` | Deactivate and permanently delete an installed plugin |
| `wmc/execute-php` | Execute raw PHP code on the server and return output |
| `wmc/get-options` | Read wp_options values by key list or partial name search |
| `wmc/update-option` | Write any value to wp_options directly |
| `wmc/search-replace-db` | Search & replace text in all database tables — handles serialized data, dry-run mode included |
| `wmc/get-error-logs` | Read PHP error log, WordPress debug.log, or WooCommerce logs |
| `wmc/get-server-info` | PHP version, memory, disk space, loaded extensions, WordPress config |

#### Tier 2 — Developer Filesystem (8 abilities)

| Ability | Description |
|---|---|
| `wmc/read-file` | Read any theme or plugin file by path |
| `wmc/write-file` | Write or overwrite any file (creates file if not exists, append mode available) |
| `wmc/list-files` | List files in any directory — recursive and extension filter supported |
| `wmc/run-wp-cli` | Run any WP-CLI command on the server (requires WP-CLI installed) |
| `wmc/manage-transients` | Get, set, delete, or flush all WordPress transients |
| `wmc/manage-redirects` | Add, delete, and list 301/302 redirects (enforced automatically via template_redirect) |
| `wmc/get-post-meta` | Read all custom fields or a specific meta key on any post/page |
| `wmc/update-post-meta` | Set or delete a custom field on any post/page |

#### Tier 4 — SEO & Performance (6 abilities)

| Ability | Description |
|---|---|
| `wmc/bulk-update-seo` | Bulk update SEO title + description for multiple posts — supports Yoast, Rank Math, AIOSEO, auto-detects active plugin |
| `wmc/get-broken-links` | Scan post content for internal broken links (404 pages) |
| `wmc/get-404-logs` | List recent 404 errors — tracked automatically in background |
| `wmc/manage-robots-txt` | Read or write the robots.txt file |
| `wmc/manage-htaccess` | Read, write, or append to .htaccess |
| `wmc/compress-images` | Regenerate WordPress image thumbnails for selected media items |

#### Tier 5 — Security & Backup (6 abilities)

| Ability | Description |
|---|---|
| `wmc/get-login-attempts` | List recent failed logins — tracked automatically in background |
| `wmc/block-ip` | Block or unblock an IP address (enforced automatically on every request) |
| `wmc/create-backup` | Export database as SQL file or create theme ZIP — saved to `wp-content/uploads/wmc-backups/` |
| `wmc/list-backups` | List all backup files created by the connector |
| `wmc/manage-user-sessions` | List, or destroy all/other sessions for any user |
| `wmc/reset-user-password` | Set a new password for any user — auto-generate mode available |

**Background automation (always active, no ability call needed):**
- Failed login tracker — every failed login saved to `wmc_failed_logins` option
- 404 tracker — every 404 saved to `wmc_404_log` option
- IP blocker — blocked IPs receive `403 Forbidden` on every request
- Redirect enforcer — managed redirects applied automatically on `template_redirect`

---

### 🌐 Module 3: WooCommerce Product Importer (`includes/import-abilities.php`)

**3 new abilities** to copy products from any public WooCommerce store — including images, prices, categories, descriptions, and tags. No API keys required on either side.

| Ability | Description |
|---|---|
| `wmc/preview-woo-import` | Preview products and categories available to import from a source store — nothing is created |
| `wmc/import-woo-products` | Import products from a WooCommerce store including all images (downloaded to media library), prices, categories, tags, and descriptions |
| `wmc/import-woo-categories` | Import only the product categories from a source store |

**Import features:**
- Images are downloaded and stored in your media library (including gallery images)
- Categories are created automatically if they don't exist
- Optional price adjustment: increase/decrease by percent or fixed amount
- Pagination support — import in batches
- Skip existing products by name to avoid duplicates
- Products imported as `draft` by default for review before publishing
- Source URL and original product ID stored in post meta for reference

**How it works:** Uses the WooCommerce Store API (`/wp-json/wc/store/v1/products`) which is publicly accessible on all WooCommerce stores without authentication.

---

### 🔢 Ability Count Summary

| Module | New Abilities | Running Total |
|---|---|---|
| v2.5.0 baseline | — | 63 |
| WooCommerce Extended | +47 | 110 |
| Advanced (Tier 1–5) | +29 | 139 |
| Product Importer | +3 | **142** |

---

## v2.5.0 — 2026-06-12

### Feature: Modern Card-Based Admin Dashboard

Complete redesign of the WordPress Admin → MCP Connector settings page:

- **Card UI** — every ability control is shown as its own card with toggle switch, badge, description, and ability slug
- **Live counters** — active/disabled count updates instantly when toggles are flipped, no page reload needed
- **Stats bar** — shows Total Abilities, Active, Categories, Disabled at a glance
- **Section collapse** — every category is collapsible; arrow indicator shows open/closed state
- **Enable All / Disable All** — global buttons plus per-section All/None buttons
- **Badge system** — READ-ONLY (blue), WRITE (green), DESTRUCTIVE (red), MODERATE (orange)
- **Card state** — enabled cards show with indigo border/tint; disabled cards are greyed/faded
- **Diagnose link** — header shortcut to `/wp-json/wmc/v1/diagnose` for quick health check
- Fully responsive (collapses to 1-column on mobile)

---

## v2.4.0 — 2026-06-12

### Feature: Bulk & Advanced Media Management

**5 new abilities added:**

| Ability | Description |
|---|---|
| `wmc/get-media-details` | Full metadata of one media item: alt, caption, description, dimensions, file size, attached post |
| `wmc/get-media-without-alt` | Find all images missing alt text — paginated SEO audit tool |
| `wmc/search-media` | Advanced filter: title/alt/desc search, MIME type, date range, attached/unattached, has-alt filter |
| `wmc/bulk-update-media` | Update alt text, title, description, caption for multiple items in one API call |
| `wmc/bulk-delete-media` | Delete multiple media items by ID array in one call |

---

## v2.3.0 — 2026-06-12

### Feature: Plugin Management, User Roles, WooCommerce & System Maintenance

**16 new abilities across 4 new categories:**

| Category | Abilities |
|---|---|
| **Plugin Management** | `wmc/get-plugins`, `wmc/activate-plugin`, `wmc/deactivate-plugin` |
| **User Roles & Permissions** | `wmc/get-roles`, `wmc/assign-role` |
| **WooCommerce** | `wmc/get-woo-products`, `wmc/create-woo-product`, `wmc/update-woo-product`, `wmc/get-woo-orders`, `wmc/update-woo-order-status`, `wmc/get-woo-customers` |
| **System / Maintenance** | `wmc/get-site-health`, `wmc/clear-cache`, `wmc/get-cron-jobs` |

---

## v2.2.3 — 2026-05-22

### Fix: Abilities now correctly register on WP 6.9+

Root cause identified: `wp_register_ability_category()` must run on `wp_abilities_api_categories_init`. The category was being registered on the wrong hook, causing all 42 ability registrations to be silently dropped.

**Changes:**
- `wmc_register_category()` now hooked exclusively on `wp_abilities_api_categories_init`
- `wmc_register_abilities()` hooked exclusively on `wp_abilities_api_init`
- Fallback hooks removed

---

## v2.2.2 — 2026-05-21

### Fix: Corrected ability registration keys

WP 6.9+ Abilities API expects `execute_callback` (not `callback`) and `meta.show_in_rest = true`. Added `WMC_Abilities::wmc_register()` wrapper that defaults these correctly.

---

## v2.2.1 — 2026-05-21

### Fix: Robust abilities registration + diagnostic endpoint

Added `GET /wp-json/wmc/v1/diagnose` endpoint for external inspection of plugin load state.

---

## v2.2.0 — 2026-05-21

### Feature: Post & Page Scheduling

New optional `date` parameter on `wmc/create-post`, `wmc/update-post`, `wmc/create-page`, `wmc/update-page`. Future date + `status: publish` schedules the post automatically.

---

## v2.1.0 — 2026-05-19

### Feature: SEO Meta Fields (Yoast, Rank Math, All in One SEO)

4 new abilities: `wmc/get-post-seo-meta`, `wmc/update-post-seo-meta`, `wmc/get-page-seo-meta`, `wmc/update-page-seo-meta`.

---

## v2.0.0 — 2026-05-15

### Feature: Settings, Menus, Widgets & Themes Management

11 new abilities: options, menus, widgets, themes, theme mods.

---

## v1.0.0 — Initial Release

Core WordPress CRUD: Posts, Pages, Categories, Tags, Media, Comments, Users.
