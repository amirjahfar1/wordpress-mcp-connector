# 🔍 SEO Meta Fields - Usage Guide

## Overview
The WordPress MCP Connector v2.1.0 now supports **managing SEO meta fields** from popular SEO plugins directly via the MCP API.

## Supported SEO Plugins
✅ **Yoast SEO** (Most Popular)
✅ **Rank Math SEO**
✅ **All in One SEO Pack**
✅ **Generic Fallback** (No plugin required)

---

## Available Abilities

### 1️⃣ **Get Post SEO Meta**
**Ability ID:** `wmc/get-post-seo-meta`

Retrieve SEO metadata from a blog post.

#### Input Parameters
```json
{
  "post_id": 455
}
```

#### Response Example
```json
{
  "success": true,
  "seo_meta": {
    "post_id": 455,
    "post_title": "WordPress: The Powerful Content Management System",
    "post_url": "https://yoursite.com/wordpress-post/",
    "plugin": "yoast",
    "meta_title": "WordPress CMS - Best Guide 2026",
    "meta_description": "Learn WordPress fundamentals...",
    "meta_robots": "index,follow",
    "focus_keyword": "WordPress guide"
  }
}
```

---

### 2️⃣ **Update Post SEO Meta**
**Ability ID:** `wmc/update-post-seo-meta`

Update SEO metadata for a blog post.

#### Input Parameters
```json
{
  "post_id": 455,
  "meta_title": "New SEO Title (60 chars max)",
  "meta_description": "New description for search results (160 chars max)",
  "meta_robots": "index,follow"
}
```

#### Response Example
```json
{
  "success": true,
  "post_id": 455,
  "plugin": "yoast",
  "message": "SEO meta updated successfully"
}
```

#### Notes
- At least `post_id` is required
- Other fields are optional - update only what you need
- All text is automatically sanitized
- Works with all 3 major SEO plugins + fallback

---

### 3️⃣ **Get Page SEO Meta**
**Ability ID:** `wmc/get-page-seo-meta`

Retrieve SEO metadata from a WordPress page.

#### Input Parameters
```json
{
  "page_id": 12
}
```

#### Response Example
```json
{
  "success": true,
  "seo_meta": {
    "page_id": 12,
    "page_title": "About Us",
    "page_url": "https://yoursite.com/about/",
    "plugin": "rankmath",
    "meta_title": "About Our Company | Professional Services",
    "meta_description": "Learn more about our team and mission...",
    "meta_robots": "index,follow"
  }
}
```

---

### 4️⃣ **Update Page SEO Meta**
**Ability ID:** `wmc/update-page-seo-meta`

Update SEO metadata for a WordPress page.

#### Input Parameters
```json
{
  "page_id": 12,
  "meta_title": "Updated Page Title",
  "meta_description": "Updated page description"
}
```

#### Response Example
```json
{
  "success": true,
  "page_id": 12,
  "plugin": "rankmath",
  "message": "SEO meta updated successfully"
}
```

---

## 🔐 Permission Management

### Enable/Disable SEO Features

1. Go to **WordPress Admin Dashboard**
2. Navigate to **MCP Connector** (left sidebar)
3. Scroll to **"🔍 SEO Meta Management"** section
4. Toggle checkboxes:
   - ☑️ **Read SEO Meta** - Allow retrieving SEO metadata
   - ☑️ **Update SEO Meta** - Allow modifying SEO metadata

### Default Status
- ✅ Both features are **enabled by default** for new installations
- Requires `manage_options` capability (Admin only)

---

## 📊 Plugin Detection

The plugin **automatically detects** which SEO plugin you have installed and uses the correct meta keys:

### Yoast SEO
```
_yoast_wpseo_title           → Meta Title
_yoast_wpseo_metadesc        → Meta Description
_yoast_wpseo_meta-robots-noindex → Meta Robots
_yoast_wpseo_focuskw         → Focus Keyword
```

### Rank Math
```
rank_math_title              → Meta Title
rank_math_description        → Meta Description
rank_math_robots             → Meta Robots
rank_math_focus_keyword      → Focus Keyword
```

### All in One SEO
```
_aioseo_title                → Meta Title
_aioseo_description          → Meta Description
_aioseo_robots               → Meta Robots
```

### Fallback (No Plugin)
```
_meta_title                  → Meta Title
_meta_description            → Meta Description
```

---

## 🚀 API Examples

### Using cURL

#### Get Post SEO Meta
```bash
curl -u 'websiteadmin:password' \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{"post_id": 455}' \
  'https://yoursite.com/wp-json/wp-abilities/v1/wmc/get-post-seo-meta'
```

#### Update Post SEO Meta
```bash
curl -u 'websiteadmin:password' \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{
    "post_id": 455,
    "meta_title": "Awesome New Title",
    "meta_description": "Compelling description for SEO"
  }' \
  'https://yoursite.com/wp-json/wp-abilities/v1/wmc/update-post-seo-meta'
```

#### Get Page SEO Meta
```bash
curl -u 'websiteadmin:password' \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{"page_id": 12}' \
  'https://yoursite.com/wp-json/wp-abilities/v1/wmc/get-page-seo-meta'
```

#### Update Page SEO Meta
```bash
curl -u 'websiteadmin:password' \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{
    "page_id": 12,
    "meta_title": "Updated Page Title",
    "meta_description": "Better description here"
  }' \
  'https://yoursite.com/wp-json/wp-abilities/v1/wmc/update-page-seo-meta'
```

---

## ✅ Best Practices

### Meta Title
- **Optimal Length:** 50-60 characters
- **Tip:** Include primary keyword at start
- **Bad:** "Post"
- **Good:** "How to Master WordPress in 2026"

### Meta Description
- **Optimal Length:** 150-160 characters
- **Tip:** Include call-to-action
- **Bad:** "This is about WordPress"
- **Good:** "Learn WordPress from basics to advanced. Complete guide with examples and best practices. Start your journey today."

### Meta Robots
Common values:
- `index,follow` - Normal indexing (default)
- `noindex,follow` - Hide from search, follow links
- `index,nofollow` - Index page, don't follow links
- `noindex,nofollow` - Hide completely

---

## ⚠️ Common Issues

### Issue: "No SEO plugin detected"
**Solution:** 
- Install Yoast SEO, Rank Math, or All in One SEO
- OR plugin will use generic fallback meta keys
- Check `plugin` field in response

### Issue: Updates not showing in WordPress admin
**Solution:**
- Refresh WordPress admin page
- Check that your SEO plugin is active
- Verify `Update SEO Meta` is enabled in MCP Connector settings

### Issue: Permission denied error
**Solution:**
- Verify you're logged in as Admin
- Check that SEO abilities are enabled in MCP Connector settings
- Verify WordPress user has `manage_options` capability

---

## 📋 Checklist for Using SEO Abilities

- [ ] Install a supported SEO plugin (Yoast/Rank Math/AIOSEO)
- [ ] Enable plugin in WordPress
- [ ] Go to WordPress Admin → MCP Connector
- [ ] Confirm SEO features are checked (enabled)
- [ ] Create/edit a post with SEO metadata
- [ ] Test `wmc/get-post-seo-meta` ability
- [ ] Test `wmc/update-post-seo-meta` ability
- [ ] Verify changes in WordPress admin

---

## 🔗 Related Documentation

- **CHANGELOG_v2.1.md** - Version history and new features
- **README.md** - Complete plugin documentation
- **QUICK_START.md** - Initial setup guide

---

## 💡 Support

Need help?
1. Check WordPress admin → MCP Connector → SEO Settings
2. Verify your SEO plugin is active and installed
3. Review response messages from API
4. Check plugin detection in response (`"plugin": "yoast"`, etc.)

Happy SEO-ing! 🚀
