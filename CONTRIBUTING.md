# Contributing to WordPress MCP Connector

Thank you for your interest in contributing! This document provides guidelines and instructions for contributing to the project.

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Report security issues privately
- Respect intellectual property

## How to Contribute

### Reporting Bugs

1. **Check existing issues** - Avoid duplicate reports
2. **Provide details:**
   - WordPress version
   - PHP version
   - Browser/client information
   - Steps to reproduce
   - Expected vs. actual behavior
3. **Include error logs** if available

**Report here:** https://github.com/amirjahfar1/wordpress-mcp-connector/issues

### Suggesting Features

1. **Check existing discussions** on GitHub
2. **Describe the feature:**
   - What problem does it solve?
   - How would it work?
   - Any alternative approaches?
3. **Provide use cases**

### Submitting Pull Requests

#### Setup Development Environment

```bash
# Clone repository
git clone https://github.com/amirjahfar1/wordpress-mcp-connector.git
cd wordpress-mcp-connector

# Install dependencies
composer install

# Create feature branch
git checkout -b feature/your-feature-name
```

#### Code Standards

- **PHP:** PSR-12 coding standards
- **Naming:** snake_case for functions, camelCase for variables
- **Documentation:** DocBlocks for all functions
- **Permissions:** Always check `current_user_can()`

#### Making Changes

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes to files
# Update documentation as needed

# Commit with descriptive message
git commit -m "feat: add new feature description"

# Push to your fork
git push origin feature/my-feature
```

#### Commit Message Format

```
<type>: <subject>

<body>

<footer>
```

**Types:**
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation only
- `style:` Code style changes
- `refactor:` Code refactoring
- `test:` Tests
- `chore:` Build/dependencies

**Example:**
```
feat: add widget management abilities

Add new abilities for retrieving and updating widget settings.
Includes sidebar listing and widget option management.

Fixes #123
```

#### Testing

Before submitting:
1. Test all modified code
2. Verify no errors in WordPress debug log
3. Check functionality in admin panel
4. Test with multiple WordPress versions

#### Creating Pull Request

1. **Push to your fork**
2. **Open PR on GitHub**
3. **Provide description:**
   - What changes?
   - Why these changes?
   - How to test?
   - Closes #issue_number

## Development Guidelines

### Adding New Abilities

```php
// In includes/abilities.php

/**
 * Register new ability
 */
private static function register_new_abilities() {
    wp_register_ability(
        'wmc/new-ability',
        array(
            'label'       => 'New Ability Label',
            'description' => 'Clear description of what it does',
            'category'    => 'wp-content-manager',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'param_name' => array(
                        'type'        => 'string',
                        'description' => 'Parameter description',
                    ),
                ),
                'required' => array('param_name'),
            ),
            'output_schema' => array('type' => 'object'),
            'callback'         => array(self::class, 'callback_function_name'),
            'permission_callback' => fn() => current_user_can('manage_options'),
        )
    );
}

/**
 * Callback function
 */
public static function callback_function_name($input) {
    // Check if enabled
    if (!self::is_enabled('feature', 'operation')) {
        return self::get_disabled_error('Operation');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        return array('success' => false, 'message' => 'Insufficient permissions');
    }
    
    // Implement logic
    // Return proper response
    return array('success' => true, 'message' => 'Success message');
}
```

### Adding Settings

```php
// In includes/settings.php

<h2>🆕 New Feature Category</h2>
<table class="form-table">
    <tr>
        <th scope="row">
            <label for="wmc_feature_read">
                <input type="checkbox" id="wmc_feature_read" 
                    name="wmc_abilities_config[feature][read]" value="1"
                    <?php checked(self::is_ability_enabled('feature', 'read'), true); ?> />
                Read Feature
            </label>
        </th>
        <td>Allow reading feature data</td>
    </tr>
    <tr>
        <th scope="row">
            <label for="wmc_feature_write">
                <input type="checkbox" id="wmc_feature_write" 
                    name="wmc_abilities_config[feature][write]" value="1"
                    <?php checked(self::is_ability_enabled('feature', 'write'), true); ?> />
                Write Feature
            </label>
        </th>
        <td>Allow modifying feature data</td>
    </tr>
</table>
```

## Documentation

- Update README.md for user-facing changes
- Update CHANGELOG.md for version history
- Add code comments for complex logic
- Document new abilities in README

## Reporting Security Issues

⚠️ **Do NOT open public issues for security vulnerabilities**

Email security concerns to: amirjahfar@gmail.com

Include:
- Description of vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

## Review Process

1. **Automated Checks**
   - Code style validation
   - PHP compatibility check
   - Documentation check

2. **Manual Review**
   - Code quality review
   - Security review
   - Functionality testing

3. **Approval & Merge**
   - Requires at least one approval
   - All checks must pass
   - Changes merged to main

## Community

- **GitHub Discussions:** Share ideas and get feedback
- **Issues:** Report bugs and request features
- **Pull Requests:** Submit code contributions

## License

By contributing, you agree that your contributions will be licensed under the GPL-2.0-or-later license.

---

Thank you for helping improve WordPress MCP Connector! 🙏
