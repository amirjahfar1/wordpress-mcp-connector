# WordPress MCP Connector v2.0.0 - Quick Start Guide

## 🚀 Installation & Setup

### Step 1: Upload Plugin
Copy the plugin folder to:
```
/wp-content/plugins/wordpress-mcp-connector/
```

### Step 2: Activate Plugin
1. Go to WordPress Admin → Plugins
2. Find "WordPress MCP Connector"
3. Click "Activate"

### Step 3: Configure Settings
1. Go to WordPress Admin → MCP Connector (new menu item)
2. You'll see the settings page with all options

---

## ⚙️ Settings Page Overview

### Four Main Sections:

#### 1. ⚙️ Settings Management
- **Read Settings:** Retrieve WordPress options
- **Update Settings:** Modify WordPress settings
- Default: ✅ ENABLED

#### 2. 📋 Menus Management
- **Read Menus:** List all menus and items
- **Write Menus:** Create/Delete menus
- Default: ✅ ENABLED

#### 3. 🎨 Widgets Management
- **Read Widgets:** List widget areas
- **Write Widgets:** Update widget settings
- Default: ✅ ENABLED

#### 4. 🎭 Themes Management
- **Read Themes:** List installed themes
- **Write Themes:** Activate themes, modify settings
- Default: ✅ ENABLED

---

## ✅ Enabling/Disabling Features

### To ENABLE a Feature:
1. Check the checkbox ☑️
2. Click "Save Changes"
3. Feature is now available via MCP

### To DISABLE a Feature:
1. Uncheck the checkbox ☐
2. Click "Save Changes"
3. Requests to disabled feature will return error

### Important Notes:
⚠️ When you disable a feature:
- It will NOT work via MCP
- Requests will get proper error response
- No operation will execute
- Feature stays disabled until you re-enable it

---

## 🔐 Security Notes

### Permission Requirements
- All features require **Admin** role
- Or users with `manage_options` capability
- Stored securely in WordPress database

### Safe Options
Settings Management only allows these safe options:
- blogname (Site Title)
- blogdescription (Tagline)
- admin_email
- posts_per_page
- posts_per_rss
- default_category
- Comment/Pingback settings
- And more...

⚠️ **Dangerous options are protected** - cannot be modified

---

## 📚 Using the Abilities

### Example 1: Get WordPress Settings
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-options/run
```

### Example 2: Create a Menu
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/create-menu/run \
  -d '{"name": "My New Menu"}'
```

### Example 3: Get All Installed Themes
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-themes/run
```

### Example 4: Activate a Theme
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/activate-theme/run \
  -d '{"theme_slug": "twenty-twenty-four"}'
```

---

## 🚨 Error Handling

### When Feature is DISABLED
You'll get response like:
```json
{
  "success": false,
  "message": "Read operation is currently disabled",
  "status": "disabled",
  "disabled": true,
  "help": "Please contact administrator to enable this feature. Go to WordPress Admin → MCP Connector to manage permissions."
}
```

### When Feature is ENABLED but Error Occurs
```json
{
  "success": false,
  "message": "Error description here",
  "status": "error"
}
```

---

## 📋 Complete Abilities List

### Settings (2 abilities)
- `wmc/get-options` - Read settings
- `wmc/update-option` - Update settings

### Menus (3 abilities)
- `wmc/get-menus` - Read menus
- `wmc/create-menu` - Create menu
- `wmc/delete-menu` - Delete menu

### Widgets (2 abilities)
- `wmc/get-sidebars` - Read widget areas
- `wmc/update-widget-option` - Update widgets

### Themes (4 abilities)
- `wmc/get-themes` - Read themes
- `wmc/activate-theme` - Activate theme
- `wmc/get-theme-mods` - Read theme settings
- `wmc/update-theme-mod` - Update theme settings

---

## ❓ Troubleshooting

### Feature Not Working?
1. Check if enabled in admin settings
2. Verify you're using correct ability name
3. Confirm user has `manage_options` capability
4. Check JWT token is valid

### Getting "Disabled" Error?
1. Go to WordPress Admin → MCP Connector
2. Find the feature section
3. Check the checkbox ☑️
4. Click "Save Changes"
5. Try again

### Need to See All Available Abilities?
```bash
curl https://yoursite.com/wp-json/wp-abilities/v1/abilities
```

---

## 🔄 Plugin Update Info

**Version:** 2.0.0
**Released:** May 2026
**Upgrade from:** 1.0.0

### What's New:
- 4 new feature categories
- 11 new abilities
- Comprehensive admin settings
- Full enable/disable control

### No Migration Needed!
- Existing features continue to work
- All previous abilities still available
- New features are optional

---

## 📞 Support

For help:
1. Check README.md for detailed documentation
2. Review CHANGELOG_v2.md for technical details
3. Check WordPress admin settings page
4. Verify permissions and feature status

---

## ✅ Checklist

- [ ] Plugin installed in `/wp-content/plugins/`
- [ ] Plugin activated in WordPress Admin
- [ ] Visited "MCP Connector" settings page
- [ ] Reviewed all feature toggles
- [ ] Enabled features you need
- [ ] Saved changes
- [ ] Tested first ability via curl/API

---

**Status:** ✅ Ready to Use  
**Last Updated:** May 2026
