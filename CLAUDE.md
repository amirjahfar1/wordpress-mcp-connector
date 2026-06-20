# WordPress MCP Connector — Project Instructions

## Plugin Location
`Plugin/` directory — main file: `Plugin/wordpress-mcp-connector.php`

## Key Files
| File | Purpose |
|---|---|
| `includes/abilities.php` | Core WordPress abilities (posts, pages, media, users, etc.) |
| `includes/advanced-abilities.php` | Advanced abilities (Tier 1-5) + Permalink abilities |
| `includes/woocommerce-extended.php` | WooCommerce abilities |
| `includes/import-abilities.php` | Product import abilities |
| `includes/seo-abilities.php` | SEO abilities (meta, OG, schema, sitemap, robots, reports) |
| `includes/settings.php` | Admin settings page with enable/disable toggles |

---

## MANDATORY RULE — Settings Page Must Always Be Updated

**Every time a new ability is added to the plugin, the settings page (`includes/settings.php`) MUST also be updated.**

### What to update in settings.php:

**1. Add the ability to `get_sections()` array**

Each ability needs an entry in the correct section (or a new section if it's a new category):

```php
array(
    'id'    => 'section_id',       // unique snake_case ID
    'title' => 'Section Title',
    'icon'  => '🔗',
    'abilities' => array(
        array(
            'label' => 'Human readable label',
            'slug'  => 'wmc/ability-name',          // ability name(s) shown on card
            'desc'  => 'What this ability does.',
            'badge' => 'read',                       // read | write | danger | moderate
            'key'   => array( 'resource', 'operation' ),  // used for enable/disable
        ),
    ),
),
```

**2. Add the key to `get_default_config()`**

```php
'resource' => array( 'operation' => 1 ),  // 1 = enabled by default
```

**3. Update the total abilities count** in the stats bar (the `168` number).

---

## MANDATORY RULE — Every Callback Must Check is_enabled()

**Every ability callback must check if it is enabled before running.**

### In seo-abilities.php (uses WMC_SEO_Abilities helper):

```php
public static function my_callback( $params ) {
    if ( ! self::is_enabled( 'resource', 'operation' ) ) {
        return self::disabled( 'Human Label', 'resource', 'operation' );
    }
    // ... rest of callback
}
```

### In advanced-abilities.php (uses WMC_Advanced_Abilities helper):

```php
public static function my_callback( $params ) {
    if ( ! self::permalink_is_enabled( 'operation' ) ) {
        return self::permalink_disabled( 'Human Label', 'operation' );
    }
    // ... rest of callback
}
```

### In abilities.php (uses WMC_Abilities helper):

```php
if ( ! self::is_enabled( 'resource', 'operation' ) ) {
    return self::get_disabled_error( 'operation' );
}
```

The disabled response tells Claude exactly where to go to enable the ability:
```
'[Ability Name]' is currently DISABLED on your WordPress site.
How to enable: Go to WordPress Admin → MCP Connector → Enable '[Ability Name]' → Save Changes
Settings URL: https://site.com/wp-admin/admin.php?page=wmc-settings
```

---

## Adding a New Ability — Full Checklist

- [ ] Write the ability registration (`self::register(...)`) in the correct file
- [ ] Write the callback function
- [ ] Add `is_enabled()` check at the TOP of the callback
- [ ] Add the ability to `get_sections()` in `settings.php`
- [ ] Add the key to `get_default_config()` in `settings.php`
- [ ] Update the total abilities count in the stats bar in `settings.php`
- [ ] Update the plugin version (patch bump: 3.5.0 → 3.5.1, or minor: 3.5.0 → 3.6.0)
- [ ] Update the `Description:` in the plugin header with new count
- [ ] Commit and push to GitHub

---

## Version Convention
- **Patch** (3.x.0 → 3.x.1): Bug fix or minor tweak to existing ability
- **Minor** (3.x.0 → 3.(x+1).0): New abilities added
- **Major** (3.x.0 → 4.0.0): Breaking change or major restructure

## GitHub Repo
https://github.com/amirjahfar1/wordpress-mcp-connector
