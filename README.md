# WordPress MCP Connector

A WordPress plugin that exposes **58 abilities** across 16 categories through the [WordPress Abilities API](https://developer.wordpress.org/abilities-api/) for seamless MCP (Model Context Protocol) integration. Control every ability individually from the WordPress admin dashboard.

**Current Version:** 2.3.0 | **Requires:** WordPress 6.9+ | **PHP:** 7.4+

---

## Table of Contents

- [Installation](#installation)
- [All 58 Abilities](#all-58-abilities)
- [Admin Dashboard](#admin-dashboard)
- [API Usage](#api-usage)
- [Permissions](#permissions)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)

---

## Installation

1. Download this repository as a ZIP
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Activate**
4. Verify at: `https://yoursite.com/wp-json/wp-abilities/v1/abilities`  
   You should see all `wmc/*` abilities listed

**Or manually:**
```
wp-content/plugins/wordpress-mcp-connector/
```

---

## All 58 Abilities

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

**Scheduling:** Pass a `date` field to schedule for future publish:
```json
{ "title": "My Post", "content": "...", "status": "publish", "date": "2026-07-01 10:00:00" }
```
Response includes `"scheduled": true` and `"status": "future"` when post is queued.

---

### 📄 Pages (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-pages` | List pages with filtering | `per_page`, `page`, `search`, `status` |
| `wmc/create-page` | Create a new page | `title`, `content`, `status`, `parent`, `date` |
| `wmc/update-page` | Update an existing page | `id`, `title`, `content`, `status`, `date` |
| `wmc/delete-page` | Permanently delete a page | `id` |

---

### 🏷️ Categories (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-categories` | List all categories | `per_page`, `search`, `hide_empty` |
| `wmc/create-category` | Create a new category | `name`, `slug`, `description`, `parent` |
| `wmc/update-category` | Update a category | `id`, `name`, `slug`, `description` |
| `wmc/delete-category` | Delete a category | `id` |

---

### 🔖 Tags (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-tags` | List all tags | `per_page`, `search` |
| `wmc/create-tag` | Create a new tag | `name`, `slug`, `description` |
| `wmc/update-tag` | Update a tag | `id`, `name`, `slug`, `description` |
| `wmc/delete-tag` | Delete a tag | `id` |

---

### 🖼️ Media (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-media` | List media library files | `per_page`, `page`, `search`, `mime_type` |
| `wmc/create-media` | Upload a media file | `url`, `title`, `alt_text` |
| `wmc/update-media` | Update media metadata | `id`, `title`, `alt_text`, `caption` |
| `wmc/delete-media` | Delete a media file | `id` |

---

### 💬 Comments (3 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-comments` | List comments with filtering | `per_page`, `post_id`, `status` |
| `wmc/moderate-comment` | Approve, unapprove, or mark as spam | `id`, `status` (`approve`/`hold`/`spam`) |
| `wmc/delete-comment` | Delete a comment | `id` |

---

### 👤 Users (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-users` | List users with role filtering | `per_page`, `role`, `search` |
| `wmc/create-user` | Create a new user | `username`, `email`, `password`, `role` |
| `wmc/update-user` | Update a user | `id`, `email`, `first_name`, `last_name`, `role` |
| `wmc/delete-user` | Delete a user | `id`, `reassign` |

---

### ⚙️ Settings (2 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-options` | Retrieve WordPress settings | `option_names` (array, optional) |
| `wmc/update-option` | Update a WordPress setting | `option_name`, `option_value` |

**Supported options:** `blogname`, `blogdescription`, `admin_email`, `siteurl`, `home`, `posts_per_page`, `default_category`, `default_comment_status`, and more.

---

### 📋 Menus (3 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-menus` | List all menus with their items | — |
| `wmc/create-menu` | Create a new navigation menu | `name`, `location` |
| `wmc/delete-menu` | Delete a menu | `id` |

---

### 🎨 Widgets (2 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-sidebars` | List all widget areas and registered widgets | — |
| `wmc/update-widget-option` | Update a widget's settings | `widget_id`, `option_key`, `option_value` |

---

### 🎭 Themes (4 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-themes` | List all installed themes | — |
| `wmc/activate-theme` | Switch the active theme | `theme_slug` |
| `wmc/get-theme-mods` | Get current theme customization settings | — |
| `wmc/update-theme-mod` | Update a theme customization value | `mod_name`, `mod_value` |

---

### 🔍 SEO Meta (4 abilities)

Supports **Yoast SEO**, **Rank Math**, and **All in One SEO**. Auto-detects installed plugin. Falls back to generic meta fields if no SEO plugin found.

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-post-seo-meta` | Read SEO metadata from a post | `post_id` |
| `wmc/update-post-seo-meta` | Update SEO metadata on a post | `post_id`, `meta_title`, `meta_description`, `meta_robots` |
| `wmc/get-page-seo-meta` | Read SEO metadata from a page | `page_id` |
| `wmc/update-page-seo-meta` | Update SEO metadata on a page | `page_id`, `meta_title`, `meta_description`, `meta_robots` |

---

### 🔌 Plugin Management (3 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-plugins` | List all installed plugins with status | `status` (`all`/`active`/`inactive`) |
| `wmc/activate-plugin` | Activate an installed plugin | `plugin` (e.g. `"woocommerce/woocommerce.php"`) |
| `wmc/deactivate-plugin` | Deactivate an active plugin | `plugin` |

---

### 👥 User Roles & Permissions (2 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-roles` | List all WordPress user roles | `include_caps` (boolean — include capabilities list) |
| `wmc/assign-role` | Assign a role to a user | `user_id`, `role` (e.g. `"editor"`, `"author"`) |

---

### 🛒 WooCommerce (6 abilities)

> Requires WooCommerce to be installed and active. All abilities return a clear error message if WooCommerce is not present.

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-woo-products` | List WooCommerce products | `per_page`, `page`, `status`, `search` |
| `wmc/create-woo-product` | Create a new product | `name`, `regular_price`, `description`, `sku`, `stock_quantity` |
| `wmc/update-woo-product` | Update an existing product | `id`, `name`, `regular_price`, `sale_price`, `status`, `stock_quantity` |
| `wmc/get-woo-orders` | List orders with filtering | `per_page`, `page`, `status` |
| `wmc/update-woo-order-status` | Change an order's status | `order_id`, `status`, `note` |
| `wmc/get-woo-customers` | List WooCommerce customers | `per_page`, `page`, `search` |

**Order statuses:** `pending`, `processing`, `on-hold`, `completed`, `cancelled`, `refunded`, `failed`

---

### ⚙️ System / Maintenance (3 abilities)

| Ability | Description | Key Inputs |
|---|---|---|
| `wmc/get-site-health` | Full site health report (WP, PHP, DB, server) | — |
| `wmc/clear-cache` | Clear WordPress cache | `type` (`all`/`object`/`transients`) |
| `wmc/get-cron-jobs` | List all scheduled cron jobs | `filter` (hook name partial match) |

**Cache clearing supports:** WordPress object cache, transients, W3 Total Cache, WP Super Cache, WP Rocket, LiteSpeed Cache.

**Site health returns:** WP version, PHP version & limits, DB version, HTTPS status, active plugin count, active theme, timezone, debug mode status.

---

## Admin Dashboard

**Location:** WordPress Admin → MCP Connector

Every ability category has its own **Read** and **Write** toggle. When a toggle is off:
- The ability is still registered (API doesn't error on discovery)
- Calling it returns a structured error:

```json
{
  "success": false,
  "status": "disabled",
  "message": "Read operation is currently disabled",
  "disabled": true,
  "help": "Go to WordPress Admin → MCP Connector to manage permissions."
}
```

### Dashboard Sections

| Section | Controls |
|---|---|
| 📝 Posts | Read, Create, Update, Delete |
| 📄 Pages | Read, Create, Update, Delete |
| 🏷️ Categories & Tags | Read, Write |
| 🖼️ Media | Read, Write |
| 💬 Comments | Read, Moderate, Delete |
| 👤 Users | Read, Write |
| ⚙️ Settings | Read, Write |
| 📋 Menus | Read, Write |
| 🎨 Widgets | Read, Write |
| 🎭 Themes | Read, Write |
| 🔍 SEO Meta | Read, Write |
| 🔌 Plugin Management | Read, Write |
| 👥 User Roles | Read, Write |
| 🛒 WooCommerce | Read, Write |
| ⚙️ System / Maintenance | Read, Write |

---

## API Usage

### Authentication

Use HTTP Basic Auth or a JWT token:

```bash
# Basic Auth
curl -u 'admin:app-password' \
  -X POST https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-posts/run

# JWT
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -X POST https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-posts/run
```

### Examples

**Get all posts:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"per_page": 10, "status": "publish"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-posts/run
```

**Create a scheduled post:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"title":"Summer Sale","content":"<p>Big discounts!</p>","status":"publish","date":"2026-07-01 09:00:00"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/create-post/run
```

**Get WooCommerce orders:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"status": "processing", "per_page": 20}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-woo-orders/run
```

**Update order status:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"order_id": 101, "status": "completed", "note": "Shipped via DHL"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/update-woo-order-status/run
```

**Get site health:**
```bash
curl -u 'admin:pass' -X POST \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-site-health/run
```

**Clear all cache:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"type": "all"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/clear-cache/run
```

**Activate a plugin:**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"plugin": "woocommerce/woocommerce.php"}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/activate-plugin/run
```

**Update post SEO (Yoast/Rank Math/AIOSEO):**
```bash
curl -u 'admin:pass' -X POST \
  -H 'Content-Type: application/json' \
  -d '{"post_id": 42, "meta_title": "Best Hoodie 2026", "meta_description": "Shop our top hoodies..."}' \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/update-post-seo-meta/run
```

### Diagnostic Endpoint

Check plugin load status and registered ability count:
```bash
curl -u 'admin:pass' https://yoursite.com/wp-json/wmc/v1/diagnose
```
Returns: plugin version, WP/PHP versions, hook fire status, `wmc_abilities_count`, full abilities list.

---

## Permissions

| Operation | Required Capability |
|---|---|
| Posts / Pages / Media read & write | `manage_options` |
| Categories / Tags / Comments / Users | `manage_options` |
| Settings / Menus / Widgets / Themes | `manage_options` |
| SEO meta read & write | `manage_options` |
| Plugin activate / deactivate | `activate_plugins` |
| Assign roles to users | `edit_users` |
| WooCommerce read & write | `manage_woocommerce` or `manage_options` |
| System health / cache / cron | `manage_options` |

---

## Troubleshooting

**Abilities not showing at `/wp-json/wp-abilities/v1/abilities`?**
1. Confirm WordPress version ≥ 6.9
2. Check plugin is activated
3. Visit `/wp-json/wmc/v1/diagnose` — look for `"wmc_abilities_count": 58` and `"wmc_category_registered": true`

**"Operation is currently disabled" error?**
- Go to **WordPress Admin → MCP Connector** and enable the relevant toggle

**WooCommerce abilities return "not installed"?**
- Install and activate WooCommerce plugin first

**SEO meta not updating?**
- Confirm one of Yoast SEO, Rank Math, or All in One SEO is installed
- Without a plugin, fallback generic meta fields (`_meta_title`, `_meta_description`) are used

**Scheduled posts not publishing?**
- WordPress cron must be functional on your server
- Or use a real cron job: `*/5 * * * * wget -q -O - https://yoursite.com/wp-cron.php`

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

**v2.3.0** — Plugin Management, User Roles, WooCommerce, System/Maintenance (16 new abilities)  
**v2.2.x** — Bug fixes for Abilities API registration on WP 6.9+  
**v2.2.0** — Post & Page scheduling via `date` field  
**v2.1.0** — SEO Meta Fields (Yoast, Rank Math, All in One SEO)  
**v2.0.0** — Settings, Menus, Widgets, Themes management  
**v1.0.0** — Initial release

---

## License

GPL-2.0-or-later
