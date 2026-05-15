# WordPress MCP Connector - Project Structure

## Complete Directory Layout

```
wordpress-mcp-connector/
│
├── 📄 wordpress-mcp-connector.php      [Main Plugin File]
│   └── v2.0.0 - Complete implementation
│
├── 📁 includes/                        [Core Plugin Code]
│   ├── abilities.php                   [All 31 Ability Definitions]
│   │   ├── Posts (4 abilities)
│   │   ├── Pages (4 abilities)
│   │   ├── Categories (3 abilities)
│   │   ├── Tags (3 abilities)
│   │   ├── Media (2 abilities)
│   │   ├── Comments (3 abilities)
│   │   ├── Users (1 ability)
│   │   ├── Settings (2 abilities) ⭐ NEW
│   │   ├── Menus (3 abilities) ⭐ NEW
│   │   ├── Widgets (2 abilities) ⭐ NEW
│   │   └── Themes (4 abilities) ⭐ NEW
│   │
│   └── settings.php                    [Admin Settings Page & Controls]
│       ├── Settings Management toggle
│       ├── Menus Management toggle
│       ├── Widgets Management toggle
│       ├── Themes Management toggle
│       └── All enable/disable functionality
│
├── 📁 assets/                          [Frontend Assets]
│   ├── css/                            [Stylesheets]
│   └── js/                             [JavaScript Files]
│
├── 📁 admin/                           [Admin-Specific Code]
│   └── [Reserved for future admin pages]
│
├── 📁 languages/                       [Translations]
│   └── [Translation files for i18n]
│
├── 📁 .github/                         [GitHub Configuration]
│   ├── workflows/                      [CI/CD Workflows]
│   │   └── [GitHub Actions configs]
│   └── ISSUE_TEMPLATE/                 [Issue templates]
│
├── 📚 Documentation Files
│   ├── README.md                       [Original README]
│   ├── README_COMPLETE.md              [Comprehensive README]
│   ├── QUICK_START.md                  [Quick Start Guide]
│   ├── INSTALL.md                      [Installation Instructions]
│   ├── CONTRIBUTING.md                 [Contributing Guidelines]
│   ├── CHANGELOG_v2.md                 [Version History]
│   ├── GITHUB_SETUP.md                 [GitHub Setup Guide]
│   └── PROJECT_STRUCTURE.md            [This File]
│
├── ⚙️ Configuration Files
│   ├── composer.json                   [PHP Dependencies]
│   ├── .gitignore                      [Git Ignore Rules]
│   └── LICENSE                         [GPL-2.0-or-later License]
│
└── 🔑 Git Files
    └── .git/                           [Git Repository]
```

## File Descriptions

### Main Plugin File: `wordpress-mcp-connector.php`

**Purpose:** WordPress plugin entry point
**Size:** ~55 lines
**Key Features:**
- Plugin metadata (name, version, license, etc.)
- Hook initialization
- Version constants
- Requires WordPress 6.9+, PHP 7.4+

### Abilities: `includes/abilities.php`

**Purpose:** Register all WordPress MCP abilities
**Size:** ~2000+ lines
**Components:**

```
Ability Class (WMC_Abilities)
├── register_all_abilities()           - Register all 31 abilities
├── is_enabled()                       - Check if ability enabled
├── get_disabled_error()               - Return error for disabled abilities
│
├── Posts Abilities (4)
├── Pages Abilities (4)
├── Categories Abilities (3)
├── Tags Abilities (3)
├── Media Abilities (2)
├── Comments Abilities (3)
├── Users Abilities (1)
├── Settings Abilities (2) ⭐ NEW
├── Menus Abilities (3) ⭐ NEW
├── Widgets Abilities (2) ⭐ NEW
└── Themes Abilities (4) ⭐ NEW
```

### Settings: `includes/settings.php`

**Purpose:** Admin settings page and controls
**Size:** ~500+ lines
**Components:**

```
Settings Class (WMC_Settings)
├── init()                             - Initialize settings
├── add_admin_menu()                   - Add menu item
├── render_settings_page()             - Render admin page
├── is_ability_enabled()               - Check if feature enabled
└── get_default_config()               - Get default settings
```

## Features by Category

### 📝 Content Management (18 abilities)
- Posts: List, Create, Update, Delete
- Pages: List, Create, Update, Delete
- Categories: List, Create, Delete
- Tags: List, Create, Delete
- Media: List, Delete
- Comments: List, Moderate, Delete

### 👤 User Management (1 ability)
- Users: List with role filtering

### ⚙️ Settings Management (2 abilities) ⭐ NEW
- Get WordPress options
- Update safe WordPress options

### 📋 Menus Management (3 abilities) ⭐ NEW
- List menus
- Create menus
- Delete menus

### 🎨 Widgets Management (2 abilities) ⭐ NEW
- List widget areas
- Update widget settings

### 🎭 Themes Management (4 abilities) ⭐ NEW
- List installed themes
- Activate theme
- Get theme settings
- Update theme customization

## Permission Structure

```
Permission Levels:
├── Public
│   └── No authentication required
│
├── Authenticated Users
│   └── Valid JWT token or session
│
└── Administrators (manage_options)
    ├── All read operations
    ├── All create operations
    ├── All update operations
    └── All delete operations
```

## Database Usage

### WordPress wp_options Table

**Option Name:** `wmc_abilities_config`

**Structure:**
```php
Array(
    'posts' => Array(
        'read' => 1,
        'create' => 1,
        'update' => 1,
        'delete' => 1
    ),
    'pages' => Array(...),
    'categories' => Array(...),
    'tags' => Array(...),
    'media' => Array(...),
    'comments' => Array(...),
    'users' => Array(...),
    'settings' => Array(...),    // NEW
    'menus' => Array(...),        // NEW
    'widgets' => Array(...),      // NEW
    'themes' => Array(...)        // NEW
)
```

## API Endpoints

### Abilities API Endpoint
```
GET /wp-json/wp-abilities/v1/abilities
```

### Ability Execution
```
POST /wp-json/wp-abilities/v1/abilities/{ability_id}/run
```

## Code Organization

### Function Naming Conventions
- Abilities: `wmc/{resource}-{action}`
  - Example: `wmc/get-posts`, `wmc/create-menu`

- Internal Functions: `snake_case`
  - Example: `get_options()`, `register_settings()`

- Class Names: `PascalCase`
  - Example: `WMC_Abilities`, `WMC_Settings`

### Permission Checks Pattern
```php
// Always check first
if (!self::is_enabled('feature', 'operation')) {
    return self::get_disabled_error('Operation');
}

// Then check WordPress permissions
if (!current_user_can('manage_options')) {
    return array('success' => false, 'message' => 'Permission denied');
}

// Then execute
// ... implementation code ...
```

## Documentation Files Guide

| File | Purpose | Audience |
|------|---------|----------|
| README.md | Quick overview | Everyone |
| README_COMPLETE.md | Comprehensive guide | Developers, Users |
| QUICK_START.md | Getting started | New Users |
| INSTALL.md | Installation steps | System Admins |
| CONTRIBUTING.md | Contributing guide | Developers |
| CHANGELOG_v2.md | Version history | Everyone |
| GITHUB_SETUP.md | GitHub setup | Developers |
| PROJECT_STRUCTURE.md | This file | Developers |

## Size Statistics

```
Total Files: 12 core files + documentation
Core Code: ~2,500+ lines of PHP
Documentation: ~3,000+ lines
Comments/Docblocks: ~500+ lines
Total Size: ~6,000+ lines of total content
```

## Dependencies

### PHP Dependencies
- WordPress 6.9+ (Abilities API)
- PHP 7.4+
- MySQL 5.7+

### No External Libraries Required
- Built using WordPress APIs only
- Zero third-party dependencies
- Lightweight and performant

## Extensibility

### How to Add New Abilities

1. **Add ability registration** in `includes/abilities.php`
2. **Implement callback function** in same file
3. **Add settings toggle** in `includes/settings.php`
4. **Update documentation** in README files

### How to Add Admin Pages

1. Create file in `admin/` directory
2. Add menu item in `settings.php`
3. Implement functionality

## Security Checklist

- ✅ Permission checks on all operations
- ✅ Safe options whitelisted
- ✅ Enable/disable controls
- ✅ JWT token support
- ✅ User role verification
- ✅ Input validation
- ✅ Error handling
- ✅ Database escaping

## Version 2.0.0 Improvements

### Added v2.0.0
- Settings Management (2 new abilities)
- Menus Management (3 new abilities)
- Widgets Management (2 new abilities)
- Themes Management (4 new abilities)
- Admin settings interface
- Enhanced documentation

### Total Abilities
- v1.0.0: 20 abilities
- v2.0.0: 31 abilities (+11 new) ⭐

---

**Plugin Architecture:** Clean, Modular, Extensible
**Code Quality:** Well-documented, Secure, Standards-compliant
**Performance:** Lightweight, No external dependencies
