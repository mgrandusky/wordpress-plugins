# WP Speed Booster - Installation & Usage Guide

## Installation

### Standard Installation
1. Upload the `wp-speed-booster` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings → WP Speed Booster** to configure

### Via WordPress Admin
1. Go to Plugins → Add New
2. Upload the `wp-speed-booster.zip` file
3. Activate the plugin
4. Configure at **Settings → WP Speed Booster**

## Quick Start Guide

### 1. Enable Basic Optimizations
After activation, the following features are enabled by default:
- ✅ Page Caching
- ✅ HTML Minification
- ✅ CSS/JS Minification
- ✅ Lazy Loading
- ✅ Remove Emojis
- ✅ JavaScript Defer

### 2. Configure Cache (Cache Tab)
- **Enable Page Caching**: Enabled by default
- **Cache Lifespan**: 10 hours (36000 seconds)
- **Mobile Cache**: Separate cache for mobile devices
- **Exclude URLs**: Add patterns like `/cart/`, `/checkout/` to exclude from caching

### 3. Optimize Assets (Optimization Tab)
- **HTML Minification**: Remove whitespace and comments
- **CSS Minification**: Reduce CSS file sizes
- **JS Minification**: Reduce JavaScript file sizes
- **Defer JavaScript**: Load JS asynchronously
- **Remove Query Strings**: Clean up version parameters

### 4. Lazy Loading (Media Tab)
- **Lazy Load Images**: Delay loading until visible
- **Lazy Load iframes**: For YouTube, etc.
- **Exclude by Class**: Skip specific images
- **Skip Images**: Number of above-fold images to skip

### 5. Database Optimization (Database Tab)
- **Clean Revisions**: Keep last N revisions
- **Clean Auto-drafts**: Remove automatic drafts
- **Clean Trash**: Empty trash
- **Optimize Tables**: Defragment database
- **Clean Transients**: Remove expired options
- **Automatic Optimization**: Schedule daily/weekly/monthly

### 6. Advanced Features (Advanced Tab)
- **CDN Integration**: Enter your CDN URL
- **DNS Prefetch**: Add external domains
- **Disable Emojis**: Remove emoji scripts
- **Disable Embeds**: Remove embed functionality
- **Remove jQuery Migrate**: For newer themes

## Dashboard Overview

The **Dashboard** tab provides:
- Cache statistics
- Database size
- Active optimizations counter
- Quick action buttons:
  - Clear Cache
  - Optimize Database
  - Preload Cache

## Common Tasks

### Clearing Cache
1. Go to Dashboard or Cache tab
2. Click "Clear Cache" button
3. Wait for confirmation message

### Database Optimization
1. Go to Database tab
2. Select optimization tasks to perform
3. Click "Run Optimization Now"
4. Review results

### Cache Preloading
1. Go to Cache tab
2. Click "Preload Cache" button
3. Wait for process to complete (may take a few minutes)

## Best Practices

### For New Sites
1. Start with default settings
2. Enable cache and basic minification
3. Test thoroughly
4. Gradually enable advanced features

### For Existing Sites
1. Backup your site first
2. Enable features one at a time
3. Test after each change
4. Monitor for conflicts

### Recommended Settings

**Beginner:**
- Cache: Enabled
- HTML Minification: Enabled
- Lazy Loading: Enabled
- Database: Manual optimization only

**Intermediate:**
- All Beginner settings
- CSS/JS Minification: Enabled
- JS Defer: Enabled
- Database: Weekly automatic optimization

**Advanced:**
- All Intermediate settings
- CDN: Enabled (if available)
- CSS/JS Combine: Test carefully
- Custom exclusions as needed

## Troubleshooting

### Site Looks Broken
1. Clear cache
2. Disable CSS/JS combine
3. Check for conflicts in browser console
4. Add problematic files to exclusion list

### Performance Not Improved
1. Clear browser cache
2. Test with cache enabled
3. Check cache statistics
4. Verify minification is working

### WooCommerce Issues
The plugin automatically excludes:
- Cart pages
- Checkout pages
- Account pages
- Any page with query parameters

Add additional exclusions in Cache tab if needed.

## Developer Hooks

### Actions
```php
// Before cache is saved
do_action( 'wpsb_before_cache_save', $cache_file, $content );

// After cache is saved
do_action( 'wpsb_after_cache_save', $cache_file, $content );

// Before cache is cleared
do_action( 'wpsb_before_cache_clear' );

// After cache is cleared
do_action( 'wpsb_after_cache_clear' );
```

### Filters
```php
// Filter minified HTML
apply_filters( 'wpsb_minify_html', $html );

// Filter minified CSS
apply_filters( 'wpsb_minify_css', $css );

// Filter minified JS
apply_filters( 'wpsb_minify_js', $js );

// Filter lazy load placeholder
apply_filters( 'wpsb_lazy_load_placeholder', $placeholder );

// Filter URLs to preload
apply_filters( 'wpsb_preload_urls', $urls );
```

## WP-CLI Commands

```bash
# Clear cache
wp cache flush

# Optimize database
wp wpsb optimize-db
```

## Support & Documentation

For issues or questions:
1. Check the readme.txt file
2. Review this guide
3. Check WordPress.org support forums
4. Submit issues on GitHub

## Plugin Requirements

- WordPress: 5.0 or higher
- PHP: 7.2 or higher
- Apache/Nginx web server
- MySQL: 5.6 or higher

## File Locations

- **Cache Directory**: `wp-content/cache/wp-speed-booster/`
- **Backup**: `.htaccess.wpsb.backup` (in WordPress root)
- **Settings**: Stored in `wp_options` table as `wpsb_options`

## Performance Tips

1. **Cache Lifespan**: Longer = better performance, but less fresh content
2. **Mobile Cache**: Enable for mobile-optimized sites
3. **Lazy Loading**: Skip 1-3 above-fold images for best results
4. **Database**: Optimize weekly for high-traffic sites
5. **CDN**: Use if serving global audience
6. **Minification**: Test thoroughly before enabling combine

## Compatibility

✅ Compatible with:
- WooCommerce
- Yoast SEO
- Contact Form 7
- Elementor
- Gutenberg
- Most popular themes and plugins

⚠️ May conflict with:
- Other caching plugins (disable one)
- Other minification plugins
- Security plugins that modify HTML

## Uninstallation

1. Deactivate the plugin
2. Delete the plugin files
3. The plugin will:
   - Remove all settings
   - Clear cache directory
   - Restore .htaccess backup
   - Remove scheduled tasks

## License

GPL v2 or later
