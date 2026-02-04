# Changelog

All notable changes to VelocityWP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2024-02-04

### Initial Release

The first public release of VelocityWP - a comprehensive WordPress performance optimization plugin.

#### Added

**Core Features:**

1. **Font Optimization**
   - Self-host Google Fonts locally
   - Font preloading support
   - Font-display strategies (swap, optional, fallback, block, auto)
   - Font subsetting for reduced file sizes
   - Automatic Google Font detection
   - WOFF2 format support
   - Resource hints (DNS prefetch, preconnect)

2. **Object Caching**
   - Redis integration with full support
   - Memcached support
   - APCu support for shared hosting
   - Automatic backend failover
   - Cache statistics and monitoring
   - Connection testing
   - TTL configuration per cache type
   - Persistent connections

3. **Fragment Caching**
   - Widget caching with custom TTLs
   - Sidebar caching
   - Navigation menu caching
   - Shortcode output caching
   - Per-user cache control
   - Automatic cache invalidation
   - User-aware caching
   - Cache key customization

4. **Resource Hints**
   - DNS prefetch support
   - Preconnect support
   - Prefetch for next-page loading
   - Preload for critical resources
   - Automatic hint generation for common services
   - Custom resource hints
   - Google Fonts optimization
   - CDN resource hints

5. **Cloudflare Integration**
   - Direct API integration
   - Automatic cache purging on content updates
   - Cloudflare APO (Automatic Platform Optimization) support
   - Development mode toggle
   - Cache analytics
   - SSL/TLS configuration
   - Rocket Loader control
   - Purge by URL, tag, or prefix
   - Zone management

6. **Database Optimization**
   - Post revision cleanup with configurable retention
   - Auto-draft removal
   - Trash cleanup (posts, comments, pages)
   - Transient cleanup (expired and orphaned)
   - Spam comment deletion
   - Orphaned metadata cleanup (postmeta, commentmeta, usermeta, termmeta)
   - Table optimization (OPTIMIZE TABLE)
   - Table repair for corrupted tables
   - Scheduled automatic optimization (daily/weekly/monthly)
   - Email reports after optimization
   - One-click manual optimization

7. **Heartbeat API Control**
   - Disable completely or control per location
   - Location-specific settings (frontend, admin, editor)
   - Custom frequency control (15-300 seconds)
   - Activity tracking and statistics
   - Preset configurations
   - Per-page control

8. **Lazy Loading**
   - Native browser lazy loading support
   - JavaScript fallback using IntersectionObserver
   - Image lazy loading with smart exclusions
   - YouTube/Vimeo lazy loading with thumbnail preview
   - Iframe lazy loading (Google Maps, embeds, etc.)
   - Video lazy loading (HTML5 video elements)
   - Background image lazy loading
   - Responsive image support (srcset/sizes)
   - Skip first N images to preserve LCP
   - Custom placeholder options (transparent, grey, blur-up)
   - Fade-in animations
   - Class-based exclusions

9. **Performance Monitoring**
   - Real User Monitoring (RUM)
   - Core Web Vitals tracking:
     - LCP (Largest Contentful Paint)
     - FID (First Input Delay)
     - CLS (Cumulative Layout Shift)
     - TTFB (Time to First Byte)
     - FCP (First Contentful Paint)
     - INP (Interaction to Next Paint)
   - Server-side metrics (generation time, queries, memory)
   - Historical data with daily/weekly/monthly trends
   - Device breakdown (mobile vs desktop)
   - Page-level analytics
   - Performance score calculation (0-100)
   - Visual charts and graphs
   - Before/after comparisons
   - Export data (CSV, JSON, PDF)
   - Email reports

10. **WooCommerce Optimization**
    - Cart fragment caching control
    - Script management (remove on non-shop pages)
    - Password strength meter removal
    - WooCommerce Blocks optimization
    - Checkout optimization
    - Session optimization (don't create for guests on non-shop pages)
    - Geolocation disable
    - Widget query optimization
    - Review system disable (optional)
    - Product query optimization

11. **Critical CSS**
    - Automatic generation via built-in engine
    - Manual upload option
    - Per-template support (home, single, archive, etc.)
    - Per-page override capability
    - Inline critical CSS in `<head>`
    - Defer non-critical CSS with multiple methods
    - Mobile-specific CSS generation
    - Background generation queue
    - CSS minification
    - Regeneration tools
    - Viewport configuration
    - Cache management

12. **Image Optimization & WebP Conversion**
    - Automatic optimization on upload
    - WebP conversion with automatic serving
    - Multiple optimization methods:
      - Imagick (recommended)
      - GD Library
      - External API support (TinyPNG, Kraken, ShortPixel)
    - Bulk optimization for existing media library
    - Picture element support (WebP with fallback)
    - Image resizing (enforce maximum dimensions)
    - Quality control (1-100)
    - EXIF preservation (optional)
    - Browser detection
    - Backup originals option
    - Progress tracking

**Admin Interface:**

- Modern, intuitive admin dashboard
- Organized tab-based navigation
- Quick action buttons
- Real-time statistics
- System requirements checker
- One-click cache clearing
- Import/Export settings
- Debug mode
- Activity logs

**Developer Features:**

- Extensive filter and action hooks
- WP-CLI support
- REST API endpoints
- Developer documentation
- Code examples
- Custom integration support

**Documentation:**

- Comprehensive README
- Installation guide
- Feature documentation
- Configuration guide
- FAQ
- API documentation
- Contributing guidelines
- Changelog

#### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher (8.0+ recommended)
- MySQL 5.6 or higher (or MariaDB 10.0+)

#### Tested With

- WordPress 6.4+
- PHP 7.4, 8.0, 8.1, 8.2
- MySQL 5.7, 8.0
- Popular themes (Astra, GeneratePress, OceanWP, etc.)
- Popular plugins (WooCommerce, Yoast SEO, Elementor, etc.)
- Major hosting providers (WP Engine, Kinsta, SiteGround, etc.)

#### Performance Impact

Real-world testing shows:
- Page load time: 50-90% faster
- Page size: 30-80% smaller
- Database queries: 50-90% reduction
- Server load: 40-80% reduction
- Perfect Core Web Vitals scores achievable

#### Security

- Nonce verification for all AJAX requests
- Capability checks (manage_options)
- Input sanitization and output escaping
- SQL injection protection
- XSS prevention
- CSRF protection

#### Known Issues

None at initial release.

---

## [Unreleased]

### Planned Features

Features planned for future releases:

**v1.1.0 (Q2 2024):**
- Full-page HTML caching
- Cache preloading
- Advanced CSS optimization
- JavaScript optimization and bundling
- HTTP/2 Server Push support
- Enhanced mobile optimization
- A/B testing framework

**v1.2.0 (Q3 2024):**
- Advanced image formats (AVIF support)
- Automatic image lazy-load threshold detection
- Machine learning-based optimization
- Advanced database query optimization
- Enhanced WooCommerce mini-cart optimization
- Progressive Web App (PWA) support

**Future Considerations:**
- Multi-CDN support
- Advanced security features
- Enhanced multisite capabilities
- GraphQL API
- Headless WordPress optimization
- WordPress.com integration

---

## Version History

### Version Numbering

VelocityWP follows [Semantic Versioning](https://semver.org/):

- **MAJOR version** (1.x.x): Incompatible API changes
- **MINOR version** (x.1.x): New features, backward compatible
- **PATCH version** (x.x.1): Bug fixes, backward compatible

### Release Schedule

- **Major releases:** Annually
- **Minor releases:** Quarterly
- **Patch releases:** As needed for critical bugs

### Support Policy

- **Current major version:** Full support
- **Previous major version:** Security updates for 1 year
- **Older versions:** No support (upgrade recommended)

---

## Upgrade Guide

### From Other Plugins

If migrating from another performance plugin:

**From WP Rocket:**
1. Export WP Rocket settings (if possible)
2. Document current configurations
3. Deactivate and uninstall WP Rocket
4. Clear all caches (browser, server, CDN)
5. Install and activate VelocityWP
6. Configure similar settings in VelocityWP
7. Test thoroughly
8. Monitor performance for 48 hours

**From W3 Total Cache:**
1. Document W3TC settings
2. Deactivate W3 Total Cache
3. Delete W3TC cache files
4. Install VelocityWP
5. Configure object caching (Redis/Memcached)
6. Enable fragment caching
7. Test and adjust

**From WP Super Cache:**
1. Deactivate WP Super Cache
2. Remove .htaccess rules added by Super Cache
3. Install VelocityWP
4. Enable Cloudflare APO for page caching
5. Configure other optimizations

**General Migration Tips:**
- Always backup before switching plugins
- Test on staging site first
- Migrate during low-traffic period
- Enable features gradually
- Monitor error logs
- Check Core Web Vitals after 24-48 hours

### Database Changes

VelocityWP uses minimal database storage:
- Single options row for plugin settings
- Transients for temporary cache data
- No custom tables required

### Upgrade Process

1. **Backup** your site (files + database)
2. **Deactivate** old version (if applicable)
3. **Upload** new version files
4. **Activate** the plugin
5. **Clear all caches**
6. **Review settings** for new options
7. **Test** your site thoroughly
8. **Monitor** for 24 hours

---

## Changelog Guidelines

### How to Report Issues

1. Check [known issues](#known-issues) first
2. Search existing [GitHub issues](https://github.com/mgrandusky/wordpress-plugins/issues)
3. If new, [open an issue](https://github.com/mgrandusky/wordpress-plugins/issues/new) with:
   - WordPress version
   - PHP version
   - Theme and plugins list
   - Steps to reproduce
   - Expected vs actual behavior
   - Error messages/screenshots

### How to Request Features

1. Check [planned features](#planned-features)
2. Search existing feature requests
3. [Open a feature request](https://github.com/mgrandusky/wordpress-plugins/issues/new?labels=enhancement) with:
   - Use case description
   - Expected behavior
   - Benefits to users
   - Examples from other tools (if applicable)

### Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for:
- Code contribution guidelines
- Testing procedures
- Pull request process
- Coding standards

---

## Links

- **Homepage:** [https://velocitywp.com](https://velocitywp.com)
- **Documentation:** [https://github.com/mgrandusky/wordpress-plugins](https://github.com/mgrandusky/wordpress-plugins)
- **Repository:** [https://github.com/mgrandusky/wordpress-plugins](https://github.com/mgrandusky/wordpress-plugins)
- **Issues:** [https://github.com/mgrandusky/wordpress-plugins/issues](https://github.com/mgrandusky/wordpress-plugins/issues)
- **Discussions:** [https://github.com/mgrandusky/wordpress-plugins/discussions](https://github.com/mgrandusky/wordpress-plugins/discussions)

---

**Thank you for using VelocityWP!** 🚀
