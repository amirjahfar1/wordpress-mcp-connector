# WordPress MCP Connector

A WordPress plugin that gives Claude **171 abilities** to fully control your WordPress site — posts, pages, media, WooCommerce, plugins, themes, SEO, security, backups, and more.

Connects via the [WordPress Abilities API](https://developer.wordpress.org/abilities-api/) and the `@automattic/mcp-wordpress-remote` MCP adapter.

**Version:** 3.6.7 &nbsp;|&nbsp; **Requires:** WordPress 6.9+ &nbsp;|&nbsp; **PHP:** 7.4+ &nbsp;|&nbsp; **WooCommerce:** 5.0+ (optional)

---

## Quick Start

### Step 1 — Install the Plugin

1. Go to [Releases](https://github.com/amirjahfar1/wordpress-mcp-connector/releases) → **Download ZIP**
2. **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP → **Activate**
4. The plugin opens the **Settings page automatically**

---

### Step 2 — Generate Application Password

On the Settings page you'll see the **"Connect Claude Code to Your Site"** card:

1. Select your administrator account from the dropdown
2. Click **⚡ Generate**
3. Your credentials appear — **copy the JSON config shown**

The generated JSON looks like this:

```json
{
  "mcpServers": {
    "wordpress-yoursite-com": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://yoursite.com/wp-json/mcp/mcp-adapter-default-server",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

---

### Step 3 — Connect Claude Code

**Option A — Tell Claude directly:**
> Paste the JSON and say: *"Update my MCP config with this"*

Claude will update your `.mcp.json` file automatically.

**Option B — Paste manually:**

Add the JSON to `.mcp.json` in your project root, or to `claude_desktop_config.json`:

```
C:\Users\YourName\AppData\Roaming\Claude\claude_desktop_config.json   (Windows)
~/Library/Application Support/Claude/claude_desktop_config.json        (Mac)
```

Then **restart Claude Code**.

---

### Done!

Claude is now connected to your WordPress site. You never need to reconnect unless you revoke the password.

**To disconnect:** WordPress Admin → Users → Your Profile → Application Passwords → Delete "Claude MCP Connector"

---

## All 142 Abilities

Toggle each ability individually from **WP Admin → MCP Connector**.

### 📝 Posts (4)

| Ability | Description |
|---|---|
| `wmc/get-posts` | List posts with pagination, search, category/tag/status filters |
| `wmc/create-post` | Create post — supports future scheduling via `date` field |
| `wmc/update-post` | Update title, content, status, scheduled date |
| `wmc/delete-post` | Permanently delete a post |

---

### 📄 Pages (4)

| Ability | Description |
|---|---|
| `wmc/get-pages` | List pages with search and status filter |
| `wmc/create-page` | Create page — supports parent pages and scheduling |
| `wmc/update-page` | Update content, status, scheduled date |
| `wmc/delete-page` | Permanently delete a page |

---

### 🏷️ Categories & Tags (8)

| Ability | Description |
|---|---|
| `wmc/get-categories` | List all categories with optional search |
| `wmc/create-category` | Create new category with optional parent |
| `wmc/update-category` | Rename or update a category |
| `wmc/delete-category` | Delete a category |
| `wmc/get-tags` | List all tags |
| `wmc/create-tag` | Create a new tag |
| `wmc/update-tag` | Update tag name or slug |
| `wmc/delete-tag` | Delete a tag |

---

### 🖼️ Media (9)

| Ability | Description |
|---|---|
| `wmc/get-media` | List media library with filters |
| `wmc/get-media-details` | Full details of a single media item |
| `wmc/search-media` | Search by MIME type, date, attached status |
| `wmc/get-media-without-alt` | Find images missing alt text (SEO audit) |
| `wmc/create-media` | Upload media from URL or base64 |
| `wmc/update-media` | Update alt text, title, caption, description |
| `wmc/bulk-update-media` | Bulk update multiple media items |
| `wmc/delete-media` | Delete a single media file |
| `wmc/bulk-delete-media` | Delete multiple media files at once |

---

### 💬 Comments (3)

| Ability | Description |
|---|---|
| `wmc/get-comments` | List comments filtered by post or status |
| `wmc/moderate-comment` | Approve, unapprove, or mark as spam |
| `wmc/delete-comment` | Permanently delete a comment |

---

### 👤 Users (4)

| Ability | Description |
|---|---|
| `wmc/get-users` | List users with role filter and search |
| `wmc/create-user` | Create a new user account |
| `wmc/update-user` | Update user info, password, or role |
| `wmc/delete-user` | Delete a user account |

---

### ⚙️ WordPress Settings (2)

| Ability | Description |
|---|---|
| `wmc/get-options` | Read site options (blogname, siteurl, etc.) |
| `wmc/update-option` | Update whitelisted options — dangerous ones blocked |

---

### 📋 Menus (3)

| Ability | Description |
|---|---|
| `wmc/get-menus` | List all navigation menus and items |
| `wmc/create-menu` | Create a new menu |
| `wmc/delete-menu` | Delete an existing menu |

---

### 🎨 Widgets (2)

| Ability | Description |
|---|---|
| `wmc/get-sidebars` | List all widget areas and their widgets |
| `wmc/update-widget-option` | Modify widget settings |

---

### 🎭 Themes (4)

| Ability | Description |
|---|---|
| `wmc/get-themes` | List installed themes with status |
| `wmc/activate-theme` | Switch the active theme |
| `wmc/get-theme-mods` | Read current theme customizer settings |
| `wmc/update-theme-mod` | Update a theme customizer value |

---

### 🔍 SEO Meta (4)

Auto-detects Yoast SEO, Rank Math, or All in One SEO.

| Ability | Description |
|---|---|
| `wmc/get-post-seo-meta` | Read SEO title, description, robots, focus keyword for a post |
| `wmc/update-post-seo-meta` | Write SEO meta to a post |
| `wmc/get-page-seo-meta` | Read SEO meta for a page |
| `wmc/update-page-seo-meta` | Write SEO meta to a page |

---

### 🔌 Plugin Management (5)

| Ability | Description |
|---|---|
| `wmc/get-plugins` | List all plugins with version and status |
| `wmc/activate-plugin` | Activate an installed plugin |
| `wmc/deactivate-plugin` | Deactivate a plugin |
| `wmc/install-plugin` | Install a plugin from WordPress.org |
| `wmc/delete-plugin` | Delete an inactive plugin |

---

### 👥 User Roles (2)

| Ability | Description |
|---|---|
| `wmc/get-roles` | List all roles with optional capabilities |
| `wmc/assign-role` | Assign a role to a user |

---

### 🖥️ System / Maintenance (3)

| Ability | Description |
|---|---|
| `wmc/get-site-health` | Site health report — WP, PHP, DB, server info |
| `wmc/get-cron-jobs` | List all scheduled WP cron tasks |
| `wmc/clear-cache` | Clear object cache, transients, W3TC, WP Rocket, LiteSpeed |

---

### 🛒 WooCommerce Core (6)

Requires WooCommerce to be active.

| Ability | Description |
|---|---|
| `wmc/get-woo-products` | List products with filters |
| `wmc/create-woo-product` | Create a new product |
| `wmc/update-woo-product` | Update product details |
| `wmc/get-woo-orders` | List orders with status filter |
| `wmc/update-woo-order-status` | Change order status |
| `wmc/get-woo-customers` | List customers |

---

### 🛍️ WooCommerce Extended (47)

Full WooCommerce control — products, orders, refunds, coupons, shipping, taxes, reviews, reports, and more. All abilities run server-side via WooCommerce PHP classes — no REST API keys needed.

<details>
<summary>Show all 47 abilities</summary>

**Products (18)**

| Ability | Description |
|---|---|
| `wmc/get-woo-product-detail` | Full product details including gallery, attributes, variations |
| `wmc/delete-woo-product` | Permanently delete a product |
| `wmc/set-woo-product-image` | Set or replace featured image |
| `wmc/get-woo-product-categories` | List all product categories with counts |
| `wmc/create-woo-product-category` | Create product category |
| `wmc/update-woo-product-category` | Update category name, slug, description |
| `wmc/delete-woo-product-category` | Delete product category |
| `wmc/get-woo-product-tags` | List all product tags |
| `wmc/create-woo-product-tag` | Create a product tag |
| `wmc/delete-woo-product-tag` | Delete a product tag |
| `wmc/get-woo-product-meta` | Read custom meta on a product |
| `wmc/update-woo-product-meta` | Write custom meta on a product |
| `wmc/create-variable-product` | Create variable product with attributes and variations |
| `wmc/get-woo-variations` | List all variations of a variable product |
| `wmc/update-woo-variation` | Update variation price, stock, or image |
| `wmc/delete-woo-variation` | Delete a variation |
| `wmc/assign-woo-product-categories` | Assign categories to a product |
| `wmc/get-woo-low-stock` | List products below stock threshold |

**Orders (7)**

| Ability | Description |
|---|---|
| `wmc/get-woo-order-detail` | Full order with line items, billing, shipping |
| `wmc/add-woo-order-note` | Add public or private note to order |
| `wmc/create-woo-order` | Programmatically create an order |
| `wmc/delete-woo-order` | Delete an order |
| `wmc/create-woo-refund` | Issue full or partial refund |
| `wmc/get-woo-order-notes` | List all notes on an order |
| `wmc/get-woo-customer-detail` | Full customer profile with order history |

**Coupons (4)**

| Ability | Description |
|---|---|
| `wmc/get-woo-coupons` | List coupons with search |
| `wmc/create-woo-coupon` | Create a coupon with rules |
| `wmc/update-woo-coupon` | Update coupon details |
| `wmc/delete-woo-coupon` | Delete a coupon |

**Shipping (4)**

| Ability | Description |
|---|---|
| `wmc/get-woo-shipping-zones` | List shipping zones |
| `wmc/get-woo-shipping-methods` | List methods in a zone |
| `wmc/create-woo-shipping-zone` | Create a shipping zone |
| `wmc/update-woo-shipping-method` | Update shipping method settings |

**Taxes (3)**

| Ability | Description |
|---|---|
| `wmc/get-woo-tax-rates` | List tax rates by class |
| `wmc/create-woo-tax-rate` | Create a tax rate |
| `wmc/delete-woo-tax-rate` | Delete a tax rate |

**Reviews (3)**

| Ability | Description |
|---|---|
| `wmc/get-woo-reviews` | List product reviews |
| `wmc/approve-woo-review` | Approve a review |
| `wmc/delete-woo-review` | Delete a review |

**Reports & Settings (6)**

| Ability | Description |
|---|---|
| `wmc/get-woo-sales-report` | Sales summary by date range |
| `wmc/get-woo-top-sellers` | Top-selling products |
| `wmc/get-woo-store-settings` | Read WooCommerce settings |
| `wmc/update-woo-store-setting` | Update a WooCommerce setting |
| `wmc/get-woo-payment-gateways` | List payment gateways and status |
| `wmc/toggle-woo-payment-gateway` | Enable or disable a payment gateway |

**Attributes (2)**

| Ability | Description |
|---|---|
| `wmc/get-woo-attributes` | List all product attributes |
| `wmc/create-woo-attribute` | Create a product attribute |

</details>

---

### 🛠️ Developer Toolkit — Tier 1 & 2 (17)

Direct access to WordPress internals. Use with care.

| Ability | Description |
|---|---|
| `wmc/install-plugin` | Install plugin from WordPress.org |
| `wmc/install-theme` | Install theme from WordPress.org |
| `wmc/delete-plugin` | Delete an inactive plugin |
| `wmc/execute-php` | Execute arbitrary PHP (admin only) |
| `wmc/get-options` | Read WordPress options |
| `wmc/update-option` | Update an option value |
| `wmc/search-replace-db` | Search and replace in the database (handles serialized data) |
| `wmc/get-error-logs` | Read PHP error log |
| `wmc/get-server-info` | Server environment details |
| `wmc/read-file` | Read any file on the server |
| `wmc/write-file` | Write content to a file |
| `wmc/list-files` | List files in a directory |
| `wmc/run-wp-cli` | Execute WP-CLI commands |
| `wmc/manage-transients` | Get, set, or delete transients |
| `wmc/manage-redirects` | Create or delete redirects |
| `wmc/get-post-meta` | Read any post meta field |
| `wmc/update-post-meta` | Write any post meta field |

---

### 📈 SEO & Performance — Tier 4 (6)

| Ability | Description |
|---|---|
| `wmc/bulk-update-seo` | Bulk update SEO meta across multiple posts |
| `wmc/get-broken-links` | Scan for broken links |
| `wmc/get-404-logs` | View recent 404 errors |
| `wmc/manage-robots-txt` | Read or update robots.txt |
| `wmc/manage-htaccess` | Read or update .htaccess |
| `wmc/compress-images` | Compress images in media library |

---

### 🔒 Security & Backup — Tier 5 (6)

| Ability | Description |
|---|---|
| `wmc/get-login-attempts` | View failed login attempts |
| `wmc/block-ip` | Block an IP address |
| `wmc/create-backup` | Create a full site backup |
| `wmc/list-backups` | List available backups |
| `wmc/manage-user-sessions` | View or destroy active user sessions |
| `wmc/reset-user-password` | Reset a user's password |

---

### 📦 Product Importer (3)

Import products from any public WooCommerce store — images, prices, categories, everything. No API keys needed on the source site.

| Ability | Description |
|---|---|
| `wmc/preview-woo-import` | Preview products from a source store before importing |
| `wmc/import-woo-products` | Import products with images, categories, price adjustment |
| `wmc/import-woo-categories` | Import only the category structure |

**Price adjustment example:** Import products at 90% of original price, or add a fixed markup.

---

## Admin Dashboard

**WP Admin → MCP Connector**

- Toggle each of the 142 abilities individually
- Enable/disable entire sections (Posts, WooCommerce, Security, etc.)
- Generate Application Password for Claude Code connection
- One-click copy of MCP config JSON

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.9+ |
| PHP | 7.4+ |
| WooCommerce | 5.0+ (optional — only for WooCommerce abilities) |

---

## Troubleshooting

**"Abilities not showing up in Claude"**
- Restart Claude Code after updating `.mcp.json`
- Verify the plugin is active at `WP Admin → Plugins`

**"Authentication failed"**
- The Application Password shown at generation time cannot be retrieved again — generate a new one from Settings page
- Make sure `WP_API_USERNAME` matches exactly the WordPress username (case-sensitive)

**"WooCommerce abilities not working"**
- Ensure WooCommerce is installed and active
- Check `WP Admin → MCP Connector` that WooCommerce abilities are enabled

**Diagnose endpoint:**
```
https://yoursite.com/wp-json/wmc/v1/diagnose
```
Returns plugin version, ability count, WordPress version, and PHP version.

---

## Developer

Built by **Amir Ali** — WordPress & AI Integration Specialist

- GitHub: [github.com/amirjahfar1](https://github.com/amirjahfar1/)
- LinkedIn: [linkedin.com/in/scalewithaamir](https://www.linkedin.com/in/scalewithaamir/)
- Facebook: [facebook.com/DigitalAamirAli](https://www.facebook.com/DigitalAamirAli)

---

## License

GPL-2.0-or-later
