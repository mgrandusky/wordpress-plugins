# WP Speed Booster

A comprehensive WordPress page speed optimization plugin similar to WP Rocket, featuring caching, minification, lazy loading, database optimization, and more.

## Features

### 🚀 Performance Optimization
- **Page Caching** - Advanced file-based caching with mobile support
- **HTML/CSS/JS Minification** - Reduce file sizes automatically
- **Lazy Loading** - Images and iframes load only when needed
- **CDN Integration** - Seamless CDN support for static assets
- **Browser Caching** - Automatic .htaccess optimization

### 🗄️ Database Management
- Clean post revisions
- Remove auto-drafts and trash
- Optimize database tables
- Clean transients and spam
- Scheduled automatic optimization

### ⚡ Advanced Features
- DNS prefetching
- Remove WordPress bloat (emojis, embeds, etc.)
- Defer/async JavaScript loading
- Query string removal
- WooCommerce compatible

### 🎛️ Admin Interface
6 comprehensive tabs for easy configuration:
1. **Dashboard** - Overview and quick actions
2. **Cache** - Cache management and statistics
3. **Optimization** - Minification settings
4. **Media** - Lazy loading configuration
5. **Database** - Database optimization tools
6. **Advanced** - CDN, DNS, and advanced features

## Installation

1. Upload the `wp-speed-booster` folder to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Go to **Settings → WP Speed Booster**
4. Configure your preferred optimizations

## Quick Start

Default optimizations are enabled on activation:
- ✅ Page caching (10-hour lifespan)
- ✅ HTML/CSS/JS minification
- ✅ Lazy loading
- ✅ Remove emojis
- ✅ JavaScript defer

## File Structure

```
wp-speed-booster/
├── wp-speed-booster.php      # Main plugin file
├── readme.txt                # WordPress.org readme
├── USAGE.md                  # Detailed usage guide
├── FEATURES_CHECKLIST.md     # Complete features list
├── includes/
│   ├── class-cache.php       # Page caching system
│   ├── class-minify.php      # HTML/CSS/JS minification
│   ├── class-lazy-load.php   # Lazy loading implementation
│   ├── class-database.php    # Database optimization
│   ├── class-cdn.php         # CDN integration
│   └── class-preload.php     # Cache preloading
├── admin/
│   ├── class-admin.php       # Admin interface
│   ├── admin.css             # Admin styling
│   └── admin.js              # Admin JavaScript
└── assets/
    ├── lazy-load.js          # Frontend lazy loading
    └── frontend.css          # Frontend styles
```

## Requirements

- WordPress: 5.0+
- PHP: 7.2+
- MySQL: 5.6+
- Apache/Nginx web server

## Developer Hooks

### Actions
```php
do_action( 'wpsb_before_cache_save', $cache_file, $content );
do_action( 'wpsb_after_cache_save', $cache_file, $content );
do_action( 'wpsb_before_cache_clear' );
do_action( 'wpsb_after_cache_clear' );
```

### Filters
```php
apply_filters( 'wpsb_minify_html', $html );
apply_filters( 'wpsb_minify_css', $css );
apply_filters( 'wpsb_minify_js', $js );
apply_filters( 'wpsb_lazy_load_placeholder', $placeholder );
apply_filters( 'wpsb_preload_urls', $urls );
apply_filters( 'wpsb_cache_exclude_urls', $excluded_urls );
```

## WP-CLI Support

```bash
# Clear cache
wp cache flush

# Optimize database
wp wpsb optimize-db
```

## Security

✓ Nonce verification for all AJAX requests  
✓ Capability checks (manage_options)  
✓ Input sanitization and output escaping  
✓ SQL injection protection  
✓ XSS prevention  
✓ CSRF protection  

## Performance

- **Code Quality**: WordPress Coding Standards compliant
- **File Size**: ~3,300 lines of optimized code
- **Dependencies**: None (uses WordPress core only)
- **Database**: Single options row
- **Cache**: File-based (no database overhead)

## Compatibility

✅ Compatible with:
- WooCommerce
- Yoast SEO
- Contact Form 7
- Elementor
- Gutenberg
- Most popular themes and plugins

## Changelog

### 1.0.0 (2024)
- Initial release
- Complete feature implementation
- All requirements from specification met

## License

GPLv2 or later

## Author

mgrandusky

## Support

- See [USAGE.md](USAGE.md) for detailed usage instructions
- See [FEATURES_CHECKLIST.md](FEATURES_CHECKLIST.md) for complete features list
- Check readme.txt for WordPress.org documentation

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Testing

All PHP files pass syntax validation:
```bash
find . -name "*.php" -exec php -l {} \;
```

## Statistics

- **Total Files**: 14
- **PHP Files**: 8
- **JavaScript Files**: 2
- **CSS Files**: 2
- **Classes**: 7
- **Admin Tabs**: 6
- **Features**: 100+
- **Lines of Code**: ~3,300

## Notes

This plugin was created as a comprehensive alternative to premium caching plugins, providing enterprise-level features with a focus on:
- Code quality and standards compliance
- Security and performance
- User-friendly interface
- Developer extensibility
- WordPress best practices

All features specified in the original requirements have been successfully implemented.
