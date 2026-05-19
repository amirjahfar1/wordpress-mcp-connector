# WordPress MCP Connector

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)
![WordPress](https://img.shields.io/badge/wordpress-6.9%2B-blue)
![PHP](https://img.shields.io/badge/php-7.4%2B-blue)

A powerful WordPress plugin that exposes comprehensive WordPress management capabilities through the Model Context Protocol (MCP). Manage posts, pages, settings, menus, widgets, themes, and more directly via MCP.

## 🎯 Overview

WordPress MCP Connector bridges the gap between WordPress and AI assistants by providing a comprehensive set of abilities (APIs) for managing WordPress through the Abilities API. It enables full CRUD operations and advanced management of all major WordPress elements.

### Perfect For:
- 🤖 AI assistants and agents
- 🔄 Automation workflows
- 📱 Headless WordPress management
- 🛠️ WordPress automation scripts
- 🔌 Custom integrations

## ✨ Features

### 📝 Content Management
- **Posts:** Create, read, update, delete posts with pagination and filtering
- **Pages:** Manage pages with full CRUD operations
- **Categories & Tags:** Organize content with categories and tags
- **Media:** Upload, list, and manage media files
- **Comments:** Moderate comments and manage discussions

### ⚙️ Settings Management (NEW v2.0.0)
- **Get Settings:** Retrieve all WordPress options
- **Update Settings:** Modify safe WordPress options
- Site name, tagline, admin email, posts per page, and more

### 📋 Menus Management (NEW v2.0.0)
- **List Menus:** Get all registered menus
- **Create Menus:** Add new menus
- **Delete Menus:** Remove menus
- **Menu Items:** Manage menu structure

### 🎨 Widgets Management (NEW v2.0.0)
- **List Sidebars:** Get all widget areas
- **Update Widgets:** Modify widget settings
- **Widget Options:** Configure widget behavior

### 🎭 Themes Management (NEW v2.0.0)
- **List Themes:** See all installed themes
- **Activate Themes:** Switch active theme
- **Theme Settings:** Manage theme customization
- **Theme Mods:** Update theme modifications

### 👤 User Management
- **List Users:** Get user information with role filtering
- **User Details:** Retrieve complete user profiles

## 🚀 Quick Start

### Installation

```bash
# Clone to wp-content/plugins
cd /wp-content/plugins
git clone https://github.com/amirjahfar1/wordpress-mcp-connector.git

# Or use composer
composer require amirjahfar/wordpress-mcp-connector
```

### Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "WordPress MCP Connector"
3. Click "Activate"

### Configure Settings

1. Go to WordPress Admin → MCP Connector
2. Enable/disable features as needed
3. Click "Save Changes"

See [INSTALL.md](INSTALL.md) for detailed installation instructions.

## 📊 What This Plugin Can Do

### Abilities Overview

| Resource | Read | Create | Update | Delete | Status |
|----------|------|--------|--------|--------|--------|
| Posts | ✅ | ✅ | ✅ | ✅ | Enabled |
| Pages | ✅ | ✅ | ✅ | ✅ | Enabled |
| Categories | ✅ | ✅ | - | ✅ | Enabled |
| Tags | ✅ | ✅ | - | ✅ | Enabled |
| Media | ✅ | ✅ | ✅ | ✅ | Enabled |
| Comments | ✅ | - | ✅ | ✅ | Enabled |
| Users | ✅ | ✅ | ✅ | ✅ | Enabled |
| Settings | ✅ | - | ✅ | - | Enabled |
| Menus | ✅ | ✅ | - | ✅ | Enabled |
| Widgets | ✅ | - | ✅ | - | Enabled |
| Themes | ✅ | - | ✅ | ✅ | Enabled |

### Complete Abilities List

#### Posts (4 abilities)
- `wmc/get-posts` - List posts with pagination and filtering
- `wmc/create-post` - Create new posts
- `wmc/update-post` - Update existing posts
- `wmc/delete-post` - Delete posts

#### Pages (4 abilities)
- `wmc/get-pages` - List all pages
- `wmc/create-page` - Create new pages
- `wmc/update-page` - Update pages
- `wmc/delete-page` - Delete pages

#### Categories (3 abilities)
- `wmc/get-categories` - List categories
- `wmc/create-category` - Create categories
- `wmc/delete-category` - Delete categories

#### Tags (3 abilities)
- `wmc/get-tags` - List tags
- `wmc/create-tag` - Create tags
- `wmc/delete-tag` - Delete tags

#### Media (2 abilities)
- `wmc/get-media` - List media files
- `wmc/delete-media` - Delete media

#### Comments (3 abilities)
- `wmc/get-comments` - List comments
- `wmc/moderate-comment` - Approve/reject comments
- `wmc/delete-comment` - Delete comments

#### Users (1 ability)
- `wmc/get-users` - List users with role filtering

#### Settings (2 abilities) ⭐ NEW
- `wmc/get-options` - Get WordPress settings
- `wmc/update-option` - Update WordPress options

#### Menus (3 abilities) ⭐ NEW
- `wmc/get-menus` - List all menus
- `wmc/create-menu` - Create menus
- `wmc/delete-menu` - Delete menus

#### Widgets (2 abilities) ⭐ NEW
- `wmc/get-sidebars` - List widget areas
- `wmc/update-widget-option` - Update widget settings

#### Themes (4 abilities) ⭐ NEW
- `wmc/get-themes` - List installed themes
- `wmc/activate-theme` - Activate themes
- `wmc/get-theme-mods` - Get theme settings
- `wmc/update-theme-mod` - Update theme settings

**Total: 31 abilities** 🎉

## 🔐 Security Features

### Enable/Disable Controls
Every ability can be individually enabled or disabled from the admin panel. Disabled abilities:
- ❌ Will NOT execute
- ❌ Return proper error response
- ❌ Cannot be exploited

### Permission Checks
- All operations require `manage_options` capability
- Only administrators can access MCP settings
- User role-based access control

### Safe Options
Settings management only allows:
- ✅ blogname (site title)
- ✅ blogdescription (tagline)
- ✅ admin_email
- ✅ posts_per_page
- ❌ Database credentials (protected)
- ❌ Security settings (protected)

### Authentication
- JWT token support
- Basic authentication
- WordPress user roles

## 💻 API Usage

### Get Available Abilities

```bash
curl https://yoursite.com/wp-json/wp-abilities/v1/abilities
```

### Example: Get Posts

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/get-posts/run \
  -d '{"per_page": 10, "page": 1}'
```

### Example: Create Post

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/create-post/run \
  -d '{
    "title": "Hello World",
    "content": "This is my first post",
    "status": "publish"
  }'
```

### Example: Activate Theme

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/activate-theme/run \
  -d '{"theme_slug": "twenty-twenty-four"}'
```

### Example: Update Site Settings

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/update-option/run \
  -d '{
    "option_name": "blogname",
    "option_value": "My Awesome Blog"
  }'
```

## 📚 Documentation

- **[INSTALL.md](INSTALL.md)** - Installation and setup guide
- **[QUICK_START.md](QUICK_START.md)** - Quick start tutorial
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Contribution guidelines
- **[CHANGELOG_v2.md](CHANGELOG_v2.md)** - Version history and changes

## 🛠️ Plugin Structure

```
wordpress-mcp-connector/
├── wordpress-mcp-connector.php    # Main plugin file
├── includes/
│   ├── abilities.php              # All ability definitions
│   └── settings.php               # Admin settings page
├── assets/
│   ├── css/                       # Stylesheets
│   └── js/                        # JavaScript files
├── admin/                         # Admin-specific code
├── languages/                     # Translation files
├── README.md                      # This file
├── INSTALL.md                     # Installation guide
├── QUICK_START.md                 # Quick start guide
├── CONTRIBUTING.md                # Contributing guide
├── CHANGELOG_v2.md               # Change history
├── composer.json                  # PHP dependencies
└── LICENSE                        # GPL-2.0-or-later license
```

## 🔄 Workflow Examples

### Scenario 1: Automated Blog Publishing
```
MCP Agent → Create Post → Set Category → Assign Tags → Publish
```

### Scenario 2: Theme Management
```
MCP Agent → List Themes → Check Theme Compatibility → Activate Theme
```

### Scenario 3: Site Configuration
```
MCP Agent → Get Settings → Update Site Name → Update Admin Email
```

### Scenario 4: Menu Management
```
MCP Agent → Create Menu → Add Menu Items → Assign to Location
```

## 📋 Requirements

- **WordPress:** 6.9 or higher (requires Abilities API)
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **SSL/HTTPS:** Recommended for production

## 🐛 Troubleshooting

### Abilities Not Showing?
1. Verify WordPress 6.9+
2. Check plugin is activated
3. Clear WordPress cache
4. Verify user role has `manage_options`

### Getting "Disabled" Error?
1. Go to WordPress Admin → MCP Connector
2. Check the feature checkbox
3. Click "Save Changes"
4. Try again

### Permission Denied?
1. Verify user is Administrator
2. Check JWT token is valid
3. Verify `manage_options` capability

See [QUICK_START.md](QUICK_START.md) for more troubleshooting.

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### How to Contribute:
1. Fork the repository
2. Create feature branch (`feature/my-feature`)
3. Make your changes
4. Submit pull request

## 📄 License

This plugin is licensed under the **GNU General Public License v2.0 or later**.

See [LICENSE](LICENSE) for details.

## 👨‍💻 Author

**Amir Jahfar**
- Email: amirjahfar@gmail.com
- GitHub: [@amirjahfar1](https://github.com/amirjahfar1)

## 🔗 Links

- **Repository:** https://github.com/amirjahfar1/wordpress-mcp-connector
- **Issues:** https://github.com/amirjahfar1/wordpress-mcp-connector/issues
- **Discussions:** https://github.com/amirjahfar1/wordpress-mcp-connector/discussions

## 📊 Version History

### v2.0.0 (May 2026) - Major Update ⭐
- ✨ Settings Management (2 abilities)
- ✨ Menus Management (3 abilities)
- ✨ Widgets Management (2 abilities)
- ✨ Themes Management (4 abilities)
- ✨ Admin settings panel with granular controls
- ✨ Enhanced security and permission checks
- 📖 Comprehensive documentation

### v1.0.0 (Initial Release)
- Posts, Pages, Categories, Tags management
- Media and Comments management
- User listing
- Abilities API integration

See [CHANGELOG_v2.md](CHANGELOG_v2.md) for detailed history.

## 🌟 Features Roadmap

- [ ] Custom Post Types management
- [ ] Custom Taxonomy management
- [ ] WooCommerce integration
- [ ] Advanced user management
- [ ] Email notifications
- [ ] Webhook support
- [ ] Rate limiting
- [ ] Audit logging

## ❓ FAQ

**Q: Is this plugin safe?**
A: Yes. It has comprehensive security checks, permission verification, and safe options whitelisting.

**Q: Can I disable specific features?**
A: Yes. Each feature has an enable/disable toggle in WordPress admin settings.

**Q: Does it work with multisite?**
A: Yes, but requires proper network-level configuration.

**Q: Is there a free version?**
A: Yes, this is open-source and free. Licensed under GPL-2.0-or-later.

---

**Made with ❤️ for the WordPress & AI community**

⭐ Star us on GitHub if you find this useful!
