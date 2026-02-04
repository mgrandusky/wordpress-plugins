# Contributing to VelocityWP

Thank you for considering contributing to VelocityWP! This document provides guidelines and instructions for contributing.

---

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [How Can I Contribute?](#how-can-i-contribute)
3. [Development Setup](#development-setup)
4. [Coding Standards](#coding-standards)
5. [Testing](#testing)
6. [Submitting Changes](#submitting-changes)
7. [Reporting Bugs](#reporting-bugs)
8. [Suggesting Features](#suggesting-features)

---

## Code of Conduct

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Positive behavior includes:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behavior includes:**
- Trolling, insulting/derogatory comments, and personal attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

### Enforcement

Project maintainers have the right to remove, edit, or reject comments, commits, code, issues, and other contributions that do not align with this Code of Conduct.

---

## How Can I Contribute?

### Reporting Bugs

**Before creating a bug report:**
1. Check the [FAQ](FAQ.md) for common issues
2. Search [existing issues](https://github.com/mgrandusky/wordpress-plugins/issues) to avoid duplicates
3. Update to the latest version to see if the issue persists

**When creating a bug report, include:**
- Clear, descriptive title
- Detailed steps to reproduce
- Expected behavior vs actual behavior
- Screenshots (if applicable)
- Environment details:
  - WordPress version
  - PHP version
  - Theme name and version
  - Active plugins list
  - Hosting provider
  - Browser (if frontend issue)

**Example bug report:**

```markdown
## Bug Description
Lazy loading not working on homepage images

## Steps to Reproduce
1. Go to VelocityWP → Lazy Loading
2. Enable lazy loading for images
3. Clear all caches
4. Visit homepage
5. Check image loading attribute

## Expected Behavior
Images should have `loading="lazy"` attribute

## Actual Behavior
Images don't have lazy loading attribute

## Environment
- WordPress: 6.4.2
- PHP: 8.1.0
- Theme: Astra 4.0.0
- Active Plugins: WooCommerce 8.4.0, Yoast SEO 21.0
- Hosting: SiteGround
- Browser: Chrome 120

## Screenshots
[Attach screenshots]
```

### Suggesting Features

**Before suggesting a feature:**
1. Check if it's already in [planned features](CHANGELOG.md#planned-features)
2. Search [existing feature requests](https://github.com/mgrandusky/wordpress-plugins/issues?q=is%3Aissue+label%3Aenhancement)

**When suggesting a feature, include:**
- Clear, descriptive title with [Feature Request] prefix
- Use case description (why is this needed?)
- Detailed explanation of expected behavior
- Examples from other tools (if applicable)
- Potential implementation approach (optional)
- Benefits to users
- Any potential drawbacks

**Example feature request:**

```markdown
## Feature Request: AVIF Image Format Support

## Use Case
AVIF offers better compression than WebP (30% smaller files) and is now supported by major browsers. Users need this for maximum performance.

## Description
Add AVIF image format generation alongside existing WebP support. Allow users to choose preferred modern format or enable both with automatic fallback.

## Expected Behavior
1. Convert images to AVIF on upload
2. Serve AVIF to supporting browsers
3. Fallback to WebP, then original format
4. Bulk conversion tool for existing images

## Benefits
- 30% smaller image files than WebP
- Faster page loads
- Lower bandwidth costs
- Better mobile performance

## Similar Implementations
- ShortPixel plugin
- Imagify plugin

## Potential Challenges
- Server-side AVIF encoding support
- Browser compatibility detection
```

### Improving Documentation

Documentation improvements are always welcome:
- Fix typos or clarify explanations
- Add examples or use cases
- Translate documentation
- Add screenshots or diagrams
- Update outdated information

**Process:**
1. Fork the repository
2. Make changes to relevant `.md` files
3. Submit pull request with clear description

### Writing Code

See [Development Setup](#development-setup) below.

---

## Development Setup

### Prerequisites

- **Git** for version control
- **PHP** 7.4+ (8.0+ recommended)
- **WordPress** 5.0+
- **Composer** for dependencies (optional)
- **Node.js** and **npm** for build tools (optional)
- **Redis/Memcached** for testing caching features

### Local Development Environment

**Recommended setups:**
- Local by Flywheel
- XAMPP
- MAMP
- Docker (wordpress:latest image)
- VVV (Varying Vagrant Vagrants)

### Clone and Setup

```bash
# Clone the repository
git clone https://github.com/mgrandusky/wordpress-plugins.git
cd wordpress-plugins/velocitywp

# Install dependencies (if using Composer)
composer install

# Install npm packages (if applicable)
npm install

# Copy to WordPress plugins directory
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/velocitywp

# Or for Windows:
# mklink /D C:\xampp\htdocs\wordpress\wp-content\plugins\velocitywp C:\path\to\velocitywp
```

### Activate Plugin

```bash
# Via WP-CLI
wp plugin activate velocitywp

# Or via WordPress admin:
# Plugins → Installed Plugins → Activate VelocityWP
```

### Enable Debug Mode

```php
// In wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('VELOCITYWP_DEBUG', true);
```

---

## Coding Standards

### PHP Standards

VelocityWP follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

**Key points:**
- Use tabs for indentation
- Use single quotes unless parsing variables
- Space after control structures: `if ( condition )`
- Brace style: opening brace on same line
- Yoda conditions: `if ( true === $var )`
- Proper PHPDoc blocks

**Example:**

```php
<?php
/**
 * Calculate cache TTL based on content type.
 *
 * @since 1.0.0
 * @param string $type Cache type (widget, menu, sidebar).
 * @param int    $default_ttl Default TTL in seconds.
 * @return int Calculated TTL in seconds.
 */
function velocitywp_calculate_ttl( $type, $default_ttl ) {
// Validate input
if ( empty( $type ) || ! is_numeric( $default_ttl ) ) {
return 3600; // Default to 1 hour
}

// Apply filters
$ttl = apply_filters( 'velocitywp_cache_ttl', $default_ttl, $type );

// Ensure minimum
if ( $ttl < 60 ) {
$ttl = 60; // Minimum 1 minute
}

return absint( $ttl );
}
```

### JavaScript Standards

Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/).

**Key points:**
- Use tabs for indentation
- Use single quotes
- Use `===` for comparison
- Proper JSDoc comments

**Example:**

```javascript
/**
 * Initialize lazy loading for images.
 *
 * @since 1.0.0
 */
function velocityWPInitLazyLoad() {
const images = document.querySelectorAll( 'img[data-src]' );

if ( 'IntersectionObserver' in window ) {
const observer = new IntersectionObserver( ( entries ) => {
entries.forEach( ( entry ) => {
if ( entry.isIntersecting ) {
const img = entry.target;
img.src = img.dataset.src;
img.classList.add( 'loaded' );
observer.unobserve( img );
}
} );
} );

images.forEach( ( img ) => observer.observe( img ) );
}
}
```

### CSS Standards

Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/).

### Code Organization

```
velocitywp/
├── velocitywp.php           # Main plugin file
├── includes/                # PHP classes
│   ├── class-cache.php
│   ├── class-lazy-load.php
│   └── ...
├── admin/                   # Admin interface
│   ├── class-admin.php
│   ├── admin.css
│   └── admin.js
├── assets/                  # Frontend assets
│   ├── lazy-load.js
│   └── frontend.css
└── docs/                    # Documentation
    ├── FEATURES.md
    └── ...
```

### Naming Conventions

**PHP:**
- Classes: `VelocityWP_Class_Name`
- Functions: `velocitywp_function_name()`
- Variables: `$variable_name`
- Constants: `VELOCITYWP_CONSTANT_NAME`

**JavaScript:**
- Functions: `velocityWPFunctionName()`
- Variables: `variableName`
- Constants: `VELOCITY_WP_CONSTANT`

**CSS:**
- Classes: `.velocitywp-class-name`
- IDs: `#velocitywp-id-name`

### Security Best Practices

**Always:**
- Escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize input: `sanitize_text_field()`, `sanitize_email()`, etc.
- Validate input: `absint()`, `is_email()`, etc.
- Use nonces for forms: `wp_nonce_field()`, `wp_verify_nonce()`
- Check capabilities: `current_user_can( 'manage_options' )`
- Prepare SQL: `$wpdb->prepare()`

**Example:**

```php
<?php
// Bad - vulnerable to XSS
echo $_POST['user_input'];

// Good - escaped output
echo esc_html( sanitize_text_field( $_POST['user_input'] ) );

// Bad - SQL injection vulnerability
$wpdb->query( "SELECT * FROM table WHERE id = {$_GET['id']}" );

// Good - prepared statement
$wpdb->get_results( $wpdb->prepare( 
"SELECT * FROM table WHERE id = %d", 
absint( $_GET['id'] ) 
) );
```

---

## Testing

### Manual Testing

**Before submitting code:**

1. **Functionality Testing**
   - Test new feature thoroughly
   - Test in different scenarios
   - Test with various themes and plugins

2. **Compatibility Testing**
   - Test with popular themes (Astra, GeneratePress)
   - Test with popular plugins (WooCommerce, Yoast SEO)
   - Test on different hosting environments

3. **Browser Testing**
   - Chrome (latest)
   - Firefox (latest)
   - Safari (latest)
   - Edge (latest)
   - Mobile browsers

4. **Performance Testing**
   - Run PageSpeed Insights
   - Measure before/after metrics
   - Check for memory leaks
   - Profile with Query Monitor

### Automated Testing

**PHP Syntax Check:**

```bash
# Check single file
php -l includes/class-cache.php

# Check all PHP files
find . -name "*.php" -exec php -l {} \;
```

**Coding Standards:**

```bash
# Install PHPCS and WordPress standards
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs

# Check files
phpcs --standard=WordPress includes/
```

### Test Checklist

Before submitting a pull request:

- [ ] Code follows WordPress coding standards
- [ ] All new functions have PHPDoc comments
- [ ] Security best practices followed
- [ ] Tested in local environment
- [ ] Tested with WP_DEBUG enabled (no errors)
- [ ] Tested with popular themes
- [ ] Tested with popular plugins
- [ ] Browser compatibility verified
- [ ] Performance impact measured
- [ ] Documentation updated (if needed)

---

## Submitting Changes

### Pull Request Process

1. **Fork the Repository**
   - Click "Fork" button on GitHub
   - Clone your fork locally

2. **Create a Branch**
   ```bash
   git checkout -b feature/my-new-feature
   # or
   git checkout -b fix/bug-description
   ```

3. **Make Changes**
   - Write clean, well-documented code
   - Follow coding standards
   - Test thoroughly

4. **Commit Changes**
   ```bash
   git add .
   git commit -m "Add feature: description of feature"
   ```

   **Commit message format:**
   - Start with verb: Add, Fix, Update, Remove
   - Be descriptive but concise
   - Reference issues if applicable: "Fix #123: Bug description"

5. **Push to Your Fork**
   ```bash
   git push origin feature/my-new-feature
   ```

6. **Create Pull Request**
   - Go to original repository on GitHub
   - Click "New Pull Request"
   - Select your branch
   - Fill in PR template

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
How has this been tested?

## Checklist
- [ ] Code follows project coding standards
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Tests added/updated (if applicable)
- [ ] All tests passing

## Screenshots (if applicable)
[Add screenshots]

## Related Issues
Fixes #123
```

### Code Review Process

1. Maintainer reviews PR
2. Feedback provided if changes needed
3. You update PR based on feedback
4. Once approved, maintainer merges
5. Your contribution becomes part of VelocityWP!

### What Happens Next?

- Your PR is reviewed within 1-2 weeks
- If approved, merged into `develop` branch
- Included in next release
- You're credited in changelog and contributors list

---

## Git Commit Guidelines

### Commit Message Format

```
<type>: <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `perf`: Performance improvements
- `test`: Adding/updating tests
- `chore`: Maintenance tasks

**Examples:**

```
feat: Add AVIF image format support

- Implemented AVIF encoding with Imagick
- Added browser detection for AVIF support
- Created fallback chain: AVIF → WebP → original
- Added bulk conversion option

Closes #456
```

```
fix: Resolve lazy loading conflict with Elementor

Fixed issue where lazy loading interfered with Elementor's
image loading mechanism causing broken galleries.

Fixes #789
```

---

## Getting Help

### Where to Ask Questions

1. **General Questions:** [GitHub Discussions](https://github.com/mgrandusky/wordpress-plugins/discussions)
2. **Bug Reports:** [GitHub Issues](https://github.com/mgrandusky/wordpress-plugins/issues)
3. **Development Help:** Comment on related issue or discussion

### Communication Channels

- GitHub Discussions for general talk
- GitHub Issues for specific problems
- Pull Request comments for code discussions

---

## Recognition

### Contributors

All contributors are recognized in:
- Project README
- Release announcements
- Changelog

### Significant Contributions

Major contributions may earn:
- Special mention in release notes
- Contributor badge
- Direct collaboration on future features

---

## License

By contributing to VelocityWP, you agree that your contributions will be licensed under the GPL v2 or later license.

---

## Questions?

Don't hesitate to ask questions! We're here to help:

- Open a [discussion](https://github.com/mgrandusky/wordpress-plugins/discussions)
- Comment on an [issue](https://github.com/mgrandusky/wordpress-plugins/issues)
- Reach out to maintainers

---

**Thank you for contributing to VelocityWP!** 🎉
