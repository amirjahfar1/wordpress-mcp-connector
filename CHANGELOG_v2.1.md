# WordPress MCP Connector - Changelog

## Version 2.1.0 - SEO Meta Fields Support 🆕

### What's New ✨
**4 New SEO Abilities for Yoast, Rank Math & All in One SEO:**

#### 1. **Get Post SEO Meta** (`wmc/get-post-seo-meta`)
- Retrieve SEO metadata from blog posts
- Supports: Yoast SEO, Rank Math, All in One SEO
- Returns: Meta title, description, robots, focus keyword
- Auto-detects installed SEO plugin

#### 2. **Update Post SEO Meta** (`wmc/update-post-seo-meta`)
- Update SEO fields for posts
- Update any of: meta title, description, robots
- Supports all 3 major SEO plugins
- Falls back to generic meta fields if no plugin detected

#### 3. **Get Page SEO Meta** (`wmc/get-page-seo-meta`)
- Retrieve SEO metadata from pages
- Same plugin support as posts
- Separate ability for page-specific operations

#### 4. **Update Page SEO Meta** (`wmc/update-page-seo-meta`)
- Update SEO fields for pages
- Independent controls from posts

### Features 🎯

#### Multi-Plugin Support
```
✅ Yoast SEO (Most Popular)
   - _yoast_wpseo_title
   - _yoast_wpseo_metadesc
   - _yoast_wpseo_meta-robots-noindex
   - _yoast_wpseo_focuskw

✅ Rank Math SEO
   - rank_math_title
   - rank_math_description
   - rank_math_robots
   - rank_math_focus_keyword

✅ All in One SEO Pack
   - _aioseo_title
   - _aioseo_description
   - _aioseo_robots

✅ Fallback Support
   - Generic _meta_title & _meta_description
   - Works if no SEO plugin installed
```

#### Permission Management
- New SEO admin settings in WordPress dashboard
- Enable/disable SEO operations individually
- Location: WordPress Admin → MCP Connector → SEO Meta Management
- Requires `manage_options` capability

#### API Integration
All SEO abilities integrated with existing MCP flow:
- Authentication: Basic Auth + JWT tokens
- Input validation & sanitization
- Error handling with proper responses
- Enable/disable controls in WordPress admin

### Updated Files

#### `includes/abilities.php`
- Added `register_seo_abilities()` function
- Implemented 4 SEO callback functions
- Added plugin detection method
- Updated `register_all_abilities()` to register SEO abilities

#### `includes/settings.php`
- Added "🔍 SEO Meta Management" section in admin panel
- Added checkboxes for Read SEO Meta and Update SEO Meta
- Updated default config to include SEO (enabled by default)

### Example API Calls

#### Get Post SEO Meta
```bash
curl -u 'websiteadmin:password' \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{"post_id": 455}' \
  'https://essentialshoodiefrance.fr/wp-json/wp-abilities/v1/wmc/get-post-seo-meta'
```

Response:
```json
{
  "success": true,
  "seo_meta": {
    "post_id": 455,
    "post_title": "WordPress: The Powerful Content Management System",
    "post_url": "https://essentialshoodiefrance.fr/post/...",
    "plugin": "yoast",
    "meta_title": "WordPress CMS - Complete Guide 2026",
    "meta_description": "Learn how to use WordPress effectively...",
    "focus_keyword": "WordPress guide"
  }
}
```

#### Update Post SEO Meta
```bash
curl -u 'websiteadmin:password' \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{
    "post_id": 455,
    "meta_title": "New SEO Title",
    "meta_description": "New meta description for SEO"
  }' \
  'https://essentialshoodiefrance.fr/wp-json/wp-abilities/v1/wmc/update-post-seo-meta'
```

### Admin Panel Updates 📊

New settings section added:
- **Read SEO Meta** - Allow getting SEO metadata
- **Update SEO Meta** - Allow modifying SEO fields

Both controls are **enabled by default** for new installations.

### Version History

- **v2.1.0** (2026-05-19) - SEO Meta Fields Support
- **v2.0.0** (2026-05-15) - Settings, Menus, Widgets, Themes management
- **v1.0.0** - Initial release with Posts, Pages, Media, Comments, Users

### Compatible SEO Plugins

| Plugin | Status | Meta Keys Support |
|--------|--------|-------------------|
| Yoast SEO | ✅ Full | Yes |
| Rank Math | ✅ Full | Yes |
| All in One SEO Pack | ✅ Full | Yes |
| No SEO Plugin | ✅ Fallback | Generic keys |

### Next Steps 🚀

Future enhancements planned:
- [ ] SEO analysis integration (readability, keyword density)
- [ ] Bulk SEO meta update for multiple posts
- [ ] SEO audit reporting
- [ ] XML Sitemap management
- [ ] Robots.txt editing

### Breaking Changes
None - Fully backward compatible with v2.0.0

### Testing
To verify SEO abilities are working:
1. Install Yoast SEO, Rank Math, or All in One SEO
2. Create a post with SEO metadata
3. Test `wmc/get-post-seo-meta` ability
4. Update with `wmc/update-post-seo-meta`
5. Verify changes in WordPress admin

### Support
For issues or questions about SEO abilities, check:
- `QUICK_START.md` - Setup guide
- `README.md` - Feature documentation
- Admin panel: WordPress → MCP Connector → SEO Settings
