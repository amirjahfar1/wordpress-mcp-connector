# WordPress MCP Connector - Installation Guide

## System Requirements

- **WordPress:** 6.9 or higher (requires Abilities API support)
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **SSL/HTTPS:** Recommended for API access

## Installation Methods

### Method 1: Direct Upload (Recommended)

1. **Download the Plugin**
   ```bash
   cd /path/to/wp-content/plugins
   git clone https://github.com/amirjahfar1/wordpress-mcp-connector.git
   ```

2. **Activate in WordPress Admin**
   - Go to WordPress Admin Dashboard
   - Navigate to **Plugins** → **Installed Plugins**
   - Find "WordPress MCP Connector"
   - Click **Activate**

### Method 2: Manual Upload

1. **Download ZIP File**
   - Download from GitHub: https://github.com/amirjahfar1/wordpress-mcp-connector/archive/refs/heads/main.zip

2. **Upload via FTP**
   ```
   /wp-content/plugins/wordpress-mcp-connector/
   ```

3. **Activate in WordPress Admin**

### Method 3: Composer (For Development)

```bash
composer require amirjahfar/wordpress-mcp-connector
```

## Post-Installation

### 1. Verify Installation
Visit this URL in your browser (replace with your domain):
```
https://yoursite.com/wp-json/wp-abilities/v1/abilities
```

You should see a JSON response with all registered abilities.

### 2. Configure Settings
1. Go to **WordPress Admin Dashboard**
2. Click **MCP Connector** in the left sidebar
3. Review the feature toggles:
   - ⚙️ Settings Management
   - 📋 Menus Management
   - 🎨 Widgets Management
   - 🎭 Themes Management
4. Enable/disable features as needed
5. Click **Save Changes**

### 3. Get API Credentials
1. In WordPress Admin, go to **MCP Connector**
2. Note the JWT tokens in `.claude/settings.local.json`
3. Use these tokens for API authentication

## Troubleshooting

### Plugin Not Showing in Admin?

**Check WordPress version:**
```php
echo get_bloginfo('version');
```
Must be 6.9 or higher.

**Check PHP version:**
```php
phpversion();
```
Must be 7.4 or higher.

### Abilities Not Showing?

1. Clear WordPress cache
```php
wp_cache_flush();
```

2. Verify Abilities API is available
```
curl https://yoursite.com/wp-json/wp-abilities/v1/abilities
```

3. Check plugin is activated
```
wp plugin list
```

### Permission Denied Errors?

1. Verify you have `manage_options` capability
2. Check user role is Administrator
3. Verify JWT token is valid (if using token auth)

## Updating the Plugin

### From Git
```bash
cd /wp-content/plugins/wordpress-mcp-connector
git pull origin main
```

### From WordPress Admin
- WordPress will notify you of updates
- Click **Update** button
- Plugin will auto-update

## Uninstallation

### Remove Plugin
1. Go to **WordPress Admin** → **Plugins**
2. Find "WordPress MCP Connector"
3. Click **Deactivate**
4. Click **Delete**

### Clean Database
When deleting, the plugin will automatically remove:
- Settings stored in `wp_options`
- Registered abilities
- All configuration data

No data loss will occur.

## Security Recommendations

1. **HTTPS Only**
   - All API calls should use HTTPS
   - Set `WP_HOME` and `WP_SITEURL` to https://

2. **JWT Tokens**
   - Store tokens securely
   - Rotate tokens regularly
   - Never share tokens publicly

3. **User Permissions**
   - Only grant `manage_options` to trusted users
   - Use role-based access control
   - Monitor admin user activity

4. **Firewall Rules**
   - Restrict API access by IP if possible
   - Block suspicious requests
   - Monitor failed authentication attempts

## Support & Documentation

- **Documentation:** See [README.md](README.md)
- **Quick Start:** See [QUICK_START.md](QUICK_START.md)
- **Changelog:** See [CHANGELOG_v2.md](CHANGELOG_v2.md)
- **GitHub Issues:** https://github.com/amirjahfar1/wordpress-mcp-connector/issues

## Getting Help

If you encounter issues:

1. Check [QUICK_START.md](QUICK_START.md) for common solutions
2. Review [README.md](README.md) for feature documentation
3. Check GitHub issues for similar problems
4. Enable WordPress debug logging:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

---

**Version:** 2.0.0  
**Last Updated:** May 2026  
**License:** GPL-2.0-or-later
