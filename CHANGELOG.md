# WordPress MCP Connector — Changelog

---

## v2.5.0 — 2026-06-12

### Feature: Modern Card-Based Admin Dashboard

Complete redesign of the WordPress Admin → MCP Connector settings page:

- **Card UI** — every ability control is shown as its own card with toggle switch, badge, description, and ability slug
- **Live counters** — active/disabled count updates instantly when toggles are flipped, no page reload needed
- **Stats bar** — shows Total Abilities (63), Active, Categories (16), Disabled at a glance
- **Section collapse** — every category is collapsible; arrow indicator shows open/closed state
- **Enable All / Disable All** — global buttons plus per-section All/None buttons
- **Badge system** — READ-ONLY (blue), WRITE (green), DESTRUCTIVE (red), MODERATE (orange)
- **Card state** — enabled cards show with indigo border/tint; disabled cards are greyed/faded
- **Diagnose link** — header shortcut to `/wp-json/wmc/v1/diagnose` for quick health check
- Fully responsive (collapses to 1-column on mobile)

---

## v2.4.0 — 2026-06-12

### Feature: Bulk & Advanced Media Management

**5 new abilities added (total: 63):**

| Ability | Description |
|---|---|
| `wmc/get-media-details` | Full metadata of one media item: alt, caption, description, dimensions, file size, attached post |
| `wmc/get-media-without-alt` | Find all images missing alt text — paginated SEO audit tool |
| `wmc/search-media` | Advanced filter: title/alt/desc search, MIME type, date range, attached/unattached, has-alt filter |
| `wmc/bulk-update-media` | Update alt text, title, description, caption for multiple items in one API call |
| `wmc/bulk-delete-media` | Delete multiple media items by ID array in one call |

`bulk-update-media` and `bulk-delete-media` return per-item `updated`/`deleted` and `failed` arrays so the caller knows exactly what succeeded.

---

## v2.3.0 — 2026-06-12

### Feature: Plugin Management, User Roles, WooCommerce & System Maintenance

**16 new abilities across 4 new categories (total: 58):**

| Category | Abilities |
|---|---|
| **Plugin Management** | `wmc/get-plugins`, `wmc/activate-plugin`, `wmc/deactivate-plugin` |
| **User Roles & Permissions** | `wmc/get-roles`, `wmc/assign-role` |
| **WooCommerce** | `wmc/get-woo-products`, `wmc/create-woo-product`, `wmc/update-woo-product`, `wmc/get-woo-orders`, `wmc/update-woo-order-status`, `wmc/get-woo-customers` |
| **System / Maintenance** | `wmc/get-site-health`, `wmc/clear-cache`, `wmc/get-cron-jobs` |

**Admin dashboard:** 4 new sections added with individual read/write toggles. All abilities respect enable/disable state — disabled abilities return a structured error response.

**WooCommerce:** Abilities gracefully return `"WooCommerce is not installed or active"` if the plugin is missing, so registration always succeeds regardless of environment.

**Cache clearing** supports: WordPress object cache, transients, W3 Total Cache, WP Super Cache, WP Rocket, LiteSpeed Cache.

---

## v2.2.3 — 2026-05-22

### Fix: Abilities now correctly register on WP 6.9+

Root cause of zero-abilities issue identified by reading WP core source directly:

1. `wp_register_ability_category()` **must** run on `wp_abilities_api_categories_init`. Called from any other hook it silently returns null. The category was being registered inside `register_all_abilities()` which runs on the later `wp_abilities_api_init` hook — so the category was never in the registry.

2. The abilities registry rejects any ability whose category is not registered. With our category missing, all 42 `wp_register_ability()` calls were silently dropped.

3. The `init` / `abilities_api_init` fallback hooks added in v2.2.1 were actively harmful — `wp_register_ability()` also checks `doing_action()` and returns null outside `wp_abilities_api_init`.

**Changes:**
- `wmc_register_category()` now hooked exclusively on `wp_abilities_api_categories_init`
- `wmc_register_abilities()` hooked exclusively on `wp_abilities_api_init` — fallback hooks removed
- `register_all_abilities()` no longer calls `register_category()`
- Diagnostic endpoint extended with `did_wp_abilities_api_categories_init`, `wmc_category_registered`, `fn_wp_has_ability_category`

After upgrading, `GET /wp-json/wmc/v1/diagnose` should show:
```json
{
  "wmc_category_registered": true,
  "wmc_abilities_count": 42
}
```

---

## v2.2.2 — 2026-05-21

### Fix: Corrected ability registration keys

Diagnostic from v2.2.1 revealed the real problem: all `wp_register_ability()` calls were being silently rejected because of wrong array key names.

WP 6.9+ Abilities API expects:
- `execute_callback` (not `callback`)
- `meta.show_in_rest = true` (was missing)

**Changes:**
- Added `WMC_Abilities::wmc_register()` wrapper that defaults `meta.show_in_rest = true`
- Replaced all 42 ability registrations to use `execute_callback`
- No behavior changes to any ability

---

## v2.2.1 — 2026-05-21

### Fix: Robust abilities registration + diagnostic endpoint

On some WP 6.9.x builds the plugin registered zero abilities despite being active.

**Changes:**
- Registration callback added on three hooks with idempotency guard: `wp_abilities_api_init`, `abilities_api_init`, `init` priority 20 *(Note: fallbacks later removed in v2.2.3)*
- Added `GET /wp-json/wmc/v1/diagnose` endpoint (requires `manage_options`) for external inspection of plugin load state, hook status, and registered ability count

---

## v2.2.0 — 2026-05-21

### Feature: Post & Page Scheduling via `date` field

**Affected abilities:** `wmc/create-post`, `wmc/update-post`, `wmc/create-page`, `wmc/update-page`

New optional `date` parameter accepted in all four abilities above.

**Accepted formats:**
- `"YYYY-MM-DD HH:MM:SS"` — site timezone
- ISO 8601 (`"2026-06-01T10:00:00"`, with or without timezone offset)

**Behavior:**
- Future date + `status: publish` → post scheduled (`post_status = future`)
- Past date → post backdated
- Invalid date string → structured error response

**Response additions:** `status`, `date`, `scheduled` (boolean) fields in create/update responses.

**Internal:** Added `WMC_Abilities::resolve_schedule()` helper for date parsing and timezone handling. Duplicate-detection queries now include `future` status.

---

## v2.1.0 — 2026-05-19

### Feature: SEO Meta Fields Support (Yoast, Rank Math, All in One SEO)

**4 new abilities:**
| Ability | Description |
|---|---|
| `wmc/get-post-seo-meta` | Read SEO metadata from posts |
| `wmc/update-post-seo-meta` | Update SEO metadata on posts |
| `wmc/get-page-seo-meta` | Read SEO metadata from pages |
| `wmc/update-page-seo-meta` | Update SEO metadata on pages |

**Supported plugins:**
- Yoast SEO: `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`, `_yoast_wpseo_focuskw`, `_yoast_wpseo_meta-robots-noindex`
- Rank Math: `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`, `rank_math_robots`
- All in One SEO: `_aioseo_title`, `_aioseo_description`, `_aioseo_robots`
- Fallback: `_meta_title`, `_meta_description` (if no SEO plugin detected)

**Admin panel:** New "SEO Meta Management" section added in WordPress Admin → MCP Connector with read/write toggles.

---

## v2.0.0 — 2026-05-15

### Feature: Settings, Menus, Widgets & Themes Management

**11 new abilities across 4 categories:**

| Category | Abilities |
|---|---|
| **Settings** | `wmc/get-options`, `wmc/update-option` |
| **Menus** | `wmc/get-menus`, `wmc/create-menu`, `wmc/delete-menu` |
| **Widgets** | `wmc/get-sidebars`, `wmc/update-widget-option` |
| **Themes** | `wmc/get-themes`, `wmc/activate-theme`, `wmc/get-theme-mods`, `wmc/update-theme-mod` |

**Admin panel:** All new abilities have individual enable/disable toggles in WordPress Admin → MCP Connector.

**Security:** Settings management only allows whitelisted WordPress options. All operations require `manage_options` capability.

---

## v1.0.0 — Initial Release

Core WordPress CRUD abilities:

| Category | Abilities |
|---|---|
| **Posts** | `wmc/get-posts`, `wmc/create-post`, `wmc/update-post`, `wmc/delete-post` |
| **Pages** | `wmc/get-pages`, `wmc/create-page`, `wmc/update-page`, `wmc/delete-page` |
| **Categories** | `wmc/get-categories`, `wmc/create-category`, `wmc/update-category`, `wmc/delete-category` |
| **Tags** | `wmc/get-tags`, `wmc/create-tag`, `wmc/update-tag`, `wmc/delete-tag` |
| **Media** | `wmc/get-media`, `wmc/create-media`, `wmc/update-media`, `wmc/delete-media` |
| **Comments** | `wmc/get-comments`, `wmc/moderate-comment`, `wmc/delete-comment` |
| **Users** | `wmc/get-users`, `wmc/create-user`, `wmc/update-user`, `wmc/delete-user` |
