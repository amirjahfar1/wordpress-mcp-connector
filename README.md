# WordPress MCP Connector

A powerful WordPress plugin that exposes comprehensive CRUD operations through the WordPress Abilities API for seamless MCP (Model Context Protocol) integration.

## Features

✅ **Posts Management**
- Get posts (with pagination and filtering)
- Create new posts
- Update existing posts
- Delete posts (Admin/Editor only)

✅ **Pages Management**
- Get all pages
- Create new pages
- Update pages
- Delete pages (Admin/Editor only)

✅ **Categories & Tags**
- Get/Create/Delete categories
- Get/Create/Delete tags

✅ **Media Management**
- List uploaded media
- Delete media files (Admin/Editor only)

✅ **Comments**
- Get comments
- Moderate comments (approve/unapprove/spam)
- Delete comments (Admin/Editor only)

✅ **Users**
- Get user list with filtering by role

✅ **Settings Management**
- Get WordPress settings (blogname, admin_email, etc.)
- Update safe WordPress options
- Manage General, Reading, Writing, Discussion settings

✅ **Menus Management**
- Get all menus and menu items
- Create new menus
- Delete menus
- Assign menus to locations

✅ **Widgets Management**
- Get all widget areas (sidebars)
- List registered widgets
- Update widget settings

✅ **Themes Management**
- Get all installed themes
- Get current active theme
- Activate different themes
- Get and update theme customization settings

## Requirements

- WordPress 6.9 or higher (requires Abilities API support)
- PHP 7.4 or higher
- Admin/Editor user role for delete operations

## Installation

1. **Copy the plugin folder** to your WordPress installation:
   ```
   wp-content/plugins/wordpress-mcp-connector/
   ```

2. **Activate the plugin** from WordPress Admin:
   - Go to Plugins → Installed Plugins
   - Find "WordPress MCP Connector"
   - Click "Activate"

3. **Verify installation**:
   - Visit: `https://yoursite.com/wp-json/wp-abilities/v1/abilities`
   - You should see all registered abilities with "wmc/" prefix

## Available Abilities

All abilities are registered under the "WordPress Content Manager" category and can be accessed via:
`https://yoursite.com/wp-json/wp-abilities/v1/abilities`

### Posts Abilities
- `wmc/get-posts` - List posts
- `wmc/create-post` - Create new post
- `wmc/update-post` - Update post
- `wmc/delete-post` - Delete post (Admin/Editor)

### Pages Abilities
- `wmc/get-pages` - List pages
- `wmc/create-page` - Create new page
- `wmc/update-page` - Update page
- `wmc/delete-page` - Delete page (Admin/Editor)

### Categories Abilities
- `wmc/get-categories` - List categories
- `wmc/create-category` - Create category
- `wmc/delete-category` - Delete category (Admin/Editor)

### Tags Abilities
- `wmc/get-tags` - List tags
- `wmc/create-tag` - Create tag
- `wmc/delete-tag` - Delete tag (Admin/Editor)

### Media Abilities
- `wmc/get-media` - List media files
- `wmc/delete-media` - Delete media (Admin/Editor)

### Comments Abilities
- `wmc/get-comments` - List comments
- `wmc/moderate-comment` - Approve/unapprove/spam comment
- `wmc/delete-comment` - Delete comment (Admin/Editor)

### Users Abilities
- `wmc/get-users` - List users with role filtering

### Settings Abilities
- `wmc/get-options` - Retrieve WordPress settings (blogname, blogdescription, admin_email, etc.)
- `wmc/update-option` - Update safe WordPress options

### Menus Abilities
- `wmc/get-menus` - List all registered menus and menu items
- `wmc/create-menu` - Create a new menu
- `wmc/delete-menu` - Delete a menu

### Widgets Abilities
- `wmc/get-sidebars` - List all widget areas
- `wmc/update-widget-option` - Update widget settings

### Themes Abilities
- `wmc/get-themes` - List all installed themes
- `wmc/activate-theme` - Switch active theme
- `wmc/get-theme-mods` - Get theme customization settings
- `wmc/update-theme-mod` - Update theme customization settings

## MCP Integration

Once activated, your MCP can discover and use all these abilities through the WordPress Abilities API endpoint.

### Example: Delete a Post via MCP

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/wp-abilities/v1/abilities/wmc/delete-post/run \
  -d '{"id": 1}'
```

## Permissions

- **Read Operations**: Available to authenticated users
- **Create/Update Operations**: Requires `publish_posts`, `publish_pages`, or `manage_categories` capability
- **Delete Operations**: Requires `delete_posts`, `delete_pages`, or `manage_categories` capability (Admin/Editor roles)

User roles with delete permission:
- ✅ Administrator
- ✅ Editor
- ❌ Author (limited to own posts)
- ❌ Contributor
- ❌ Subscriber

## Configuration

No additional configuration needed! The plugin works out of the box with default WordPress settings.

### Customizing Permissions

To modify permission requirements, edit `includes/abilities.php` and change the `permission_callback` for specific abilities.

Example:
```php
'permission_callback' => fn() => current_user_can( 'publish_posts' )
```

## Troubleshooting

### Abilities not showing up?

1. Verify WordPress version is 6.9+
2. Check plugin is activated
3. Ensure you're accessing the correct endpoint: `/wp-json/wp-abilities/v1/abilities`

### Permission denied errors?

- Check your JWT token is valid
- Verify user role has required permissions
- Admin/Editor roles are needed for delete operations

### MCP not finding the abilities?

1. Clear any caches
2. Restart your MCP connection
3. Query the abilities endpoint to confirm they're registered

## Support & Contribution

For issues or feature requests, please contact the development team.

## License

GPL-2.0-or-later

## Settings & Permissions

### Access Control
The plugin includes a comprehensive settings page where administrators can enable/disable each ability individually.

**Location:** WordPress Admin → MCP Connector Settings

Each ability can be toggled on/off. When disabled:
- The ability will NOT be available via the API
- Requests to disabled abilities return an error response
- Settings are saved securely in WordPress database

### Permission Matrix

| Resource | Read | Create | Update | Delete | Requires |
|----------|------|--------|--------|--------|----------|
| Posts | ✅ | ✅ | ✅ | ✅ | manage_options |
| Pages | ✅ | ✅ | ✅ | ✅ | manage_options |
| Categories | ✅ | ✅ | - | ✅ | manage_options |
| Tags | ✅ | ✅ | - | ✅ | manage_options |
| Media | ✅ | ✅ | ✅ | ✅ | manage_options |
| Comments | ✅ | - | ✅ | ✅ | manage_options |
| Users | ✅ | ✅ | ✅ | ✅ | manage_options |
| Settings | ✅ | - | ✅ | - | manage_options |
| Menus | ✅ | ✅ | - | ✅ | manage_options |
| Widgets | ✅ | - | ✅ | - | manage_options |
| Themes | ✅ | - | ✅ | ✅ | manage_options |

## Changelog

### Version 2.0.0
- **NEW:** Settings Management abilities (get/update WordPress options)
- **NEW:** Menus Management abilities (create, delete, list menus)
- **NEW:** Widgets Management abilities (list sidebars, update widgets)
- **NEW:** Themes Management abilities (list, activate, customize themes)
- **NEW:** Comprehensive admin settings page with granular ability controls
- **IMPROVED:** Enhanced permission checking for all operations
- **IMPROVED:** Better error messages and status responses
- All new abilities support enable/disable toggles in admin panel

### Version 1.0.0
- Initial release
- Full CRUD operations for Posts, Pages, Categories, Tags, Media, Comments, Users
- Admin/Editor delete permissions
- Complete Abilities API integration
