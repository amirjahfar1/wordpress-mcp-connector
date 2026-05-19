# WordPress Test Project - MCP Integration

## Overview
This is a test project for WordPress integration using the Model Context Protocol (MCP). The project uses an extended MCP server with comprehensive WordPress management capabilities.

## Connected Website
- **URL:** https://essentialshoodiefrance.fr/
- **Status:** ✅ Active Integration
- **Access:** Authenticated via WordPress REST API with JWT tokens
- **MCP Plugin:** WordPress MCP Connector v2.0.0

## 🆕 Plugin Update - v2.0.0
**Extended WordPress MCP Connector with 11 new abilities:**
- ⚙️ Settings Management (2 abilities)
- 📋 Menus Management (3 abilities)
- 🎨 Widgets Management (2 abilities)
- 🎭 Themes Management (4 abilities)

All features include enable/disable controls in WordPress admin panel.

## WordPress MCP Configuration

### MCP Server Setup
The WordPress MCP server is configured in `.claude/settings.json`:

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://essentialshoodiefrance.fr/wp-json/mcp/mcp-adapter-default-server",
        "WP_API_USERNAME": "websiteadmin",
        "WP_API_PASSWORD": "eCyC XPtl IbYC iI21 cHLO 6efF"
      }
    }
  }
}
```

### Credentials
- **Username:** websiteadmin
- **API Endpoint:** https://essentialshoodiefrance.fr/wp-json/

## Available Operations via MCP

### Content Management (Original)
- **Posts:** List, Create, Update, Delete
- **Pages:** List, Create, Update, Delete
- **Categories & Tags:** List, Create, Update, Delete
- **Media:** List, Upload, Delete
- **Comments:** List, Moderate, Delete
- **Users:** List, Create, Update, Delete

### Settings Management (NEW v2.0.0)
- **Get Options:** Retrieve WordPress settings
- **Update Option:** Modify safe WordPress options

### Menus Management (NEW v2.0.0)
- **Get Menus:** List all menus
- **Create Menu:** Add new menu
- **Delete Menu:** Remove menu

### Widgets Management (NEW v2.0.0)
- **Get Sidebars:** List widget areas
- **Update Widget Option:** Modify widget settings

### Themes Management (NEW v2.0.0)
- **Get Themes:** List installed themes
- **Activate Theme:** Switch active theme
- **Get Theme Mods:** Retrieve customization settings
- **Update Theme Mod:** Modify theme settings

### API Access
- WordPress REST API: `/wp-json/wp/v2/`
- WordPress Abilities API: `/wp-json/wp-abilities/v1/`

## Sample Post Created
A sample post about WordPress has been created:
- **Title:** "WordPress: The Powerful Content Management System"
- **Post ID:** 455
- **URL:** https://essentialshoodiefrance.fr/wordpress-the-powerful-content-management-system/
- **Status:** Published
- **Created:** 2026-05-15

### Post Content Includes:
- Overview of WordPress
- Key features (Easy to Use, Extensible, SEO Friendly, Mobile Responsive, Community Support)
- Getting started guide
- Note about creation via MCP integration

## Important Notes

### Scope Limitation
⚠️ **Work Only Within Test Folder**
- All WordPress MCP operations must be performed through this Test project
- Do NOT perform operations outside the Test folder
- This ensures clean separation and maintains project integrity

### Feature Control
✅ **Admin Settings Panel**
- All MCP features can be enabled/disabled individually
- Location: WordPress Admin → MCP Connector
- Enable/disable checkboxes for each feature category
- Settings saved to WordPress database (wp_options)
- When disabled: requests return proper error response, no operation executes

### Security
✅ **Safe Operations**
- Settings management only allows whitelisted WordPress options
- All operations require `manage_options` capability
- Disabled features cannot be exploited
- Proper permission checks on all abilities

## Authentication
All API calls use Basic Authentication with the websiteadmin credentials configured above. JWT tokens are also available in `.claude/settings.local.json` for enhanced security.

## MCP Plugin Capabilities (v2.0.0)

### v2.0.0 Features
- ✅ WordPress Post/Page CRUD operations
- ✅ Category and Tag management
- ✅ Media library operations
- ✅ Comment moderation
- ✅ User listing and filtering
- ✅ **Settings Management** (NEW)
- ✅ **Menus Management** (NEW)
- ✅ **Widgets Management** (NEW)
- ✅ **Themes Management** (NEW)
- ✅ Custom WordPress Abilities API access
- ✅ Granular enable/disable controls

## Testing
To test the MCP integration:
```bash
# List all posts
curl -s -u 'websiteadmin:eCyC XPtl IbYC iI21 cHLO 6efF' \
  'https://essentialshoodiefrance.fr/wp-json/wp/v2/posts?per_page=20'

# Create a new post
curl -X POST -u 'websiteadmin:eCyC XPtl IbYC iI21 cHLO 6efF' \
  -H 'Content-Type: application/json' \
  -d '{"title":"Test","content":"Content","status":"publish"}' \
  'https://essentialshoodiefrance.fr/wp-json/wp/v2/posts'
```

## Plugin Documentation

### Local Files
- **README.md** - Complete feature documentation
- **CHANGELOG_v2.md** - v2.0.0 update details
- **QUICK_START.md** - Setup and usage guide
- **wordpress-mcp-connector.php** - Main plugin file
- **includes/settings.php** - Settings page & controls
- **includes/abilities.php** - All ability definitions

### External References
- WordPress REST API: https://developer.wordpress.org/rest-api/
- WordPress Abilities API: WordPress 6.9+
- Automattic MCP WordPress: https://github.com/Automattic/mcp-wordpress-remote

## File Locations
```
Test/
├── Wordpress-MCP-Connector/
│   ├── wordpress-mcp-connector.php (v2.0.0)
│   ├── includes/
│   │   ├── abilities.php (11 new abilities)
│   │   └── settings.php (4 new feature sections)
│   ├── README.md (updated)
│   ├── CHANGELOG_v2.md (new)
│   └── QUICK_START.md (new)
└── claude.md (this file)
```

## Version History
- **v2.0.0** (Current) - Settings, Menus, Widgets, Themes management
- **v1.0.0** - Initial release with Posts, Pages, Media, Comments, Users
