=== VelocityWP ===
Contributors: mgrandusky
Tags: cache, performance, speed, optimization, minify, lazy-load
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive page speed optimization plugin with caching, minification, lazy loading, database optimization, and more.

== Description ==

VelocityWP is a powerful performance optimization plugin that helps you speed up your WordPress website. It includes features similar to premium plugins like WP Rocket, providing:

* **Page Caching** - Advanced file-based caching with separate mobile cache
* **HTML/CSS/JS Minification** - Reduce file sizes with optional file combining
* **Lazy Loading** - Load images and iframes only when needed
* **Database Optimization** - Clean and optimize your database
* **CDN Integration** - Easy CDN setup for static assets
* **Browser Caching** - Set proper cache headers via .htaccess
* **GZIP Compression** - Enable GZIP for faster transfers
* **Performance Enhancements** - DNS prefetch, disable emojis, remove query strings, and more

== Features ==

= Page Caching =
* File-based caching system
* Separate cache for mobile devices
* Configurable cache lifespan (default: 10 hours)
* Automatic cache clearing on content updates
* Cache preloading functionality
* One-click cache clearing
* Exclude specific URLs from caching
* Cache statistics

= Minification =
* HTML minification
* CSS minification with optional combining
* JavaScript minification with optional combining
* Defer JavaScript loading
* Exclude specific files from minification
* Remove query strings from static resources

= Lazy Loading =
* Native lazy loading for modern browsers
* JavaScript fallback for older browsers
* Lazy load iframes (YouTube, etc.)
* Exclude images by class
* Skip above-the-fold images

= Database Optimization =
* Clean post revisions
* Remove auto-drafts
* Clean trashed posts/comments
* Optimize database tables
* Remove transient options
* Remove spam comments
* Schedule automatic optimization

= CDN Integration =
* Replace URLs for static assets
* Support for custom CDN domains
* Works with images, CSS, and JS files

= Advanced Features =
* DNS prefetching
* Preconnect to required origins
* Disable emoji scripts
* Disable embeds
* Remove jQuery migrate
* Remove RSD/WLW links
* Remove REST API links
* Browser caching via .htaccess
* GZIP compression via .htaccess

= Admin Dashboard =
* Clean and intuitive interface
* Organized in easy-to-use tabs
* Quick action buttons
* Performance statistics
* One-click optimization presets
* Import/export settings

== Installation ==

1. Upload the `wp-speed-booster` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → VelocityWP to configure the plugin
4. Enable desired optimization features
5. Clear cache and test your site

== Frequently Asked Questions ==

= Will this plugin break my site? =

VelocityWP is designed with safety in mind. It includes automatic .htaccess backups and safe defaults. However, it's always recommended to test on a staging site first and backup your site before making changes.

= How do I clear the cache? =

Go to Settings → VelocityWP → Cache tab and click the "Clear Cache" button. You can also use WP-CLI: `wp cache flush`

= Can I exclude specific pages from caching? =

Yes, go to the Cache tab and add URL patterns to the "Exclude URLs" field, one per line.

= Does this work with WooCommerce? =

Yes, VelocityWP is compatible with WooCommerce and other popular plugins. It automatically excludes cart and checkout pages from caching.

= How do I enable CDN? =

Go to the Advanced tab, enable CDN, and enter your CDN URL (e.g., https://cdn.example.com).

= Can I combine CSS and JS files? =

Yes, but be careful as this may break some themes or plugins. Enable one at a time and test thoroughly.

= How often should I optimize the database? =

It depends on your site activity. Weekly or monthly is usually sufficient for most sites.

== Screenshots ==

1. Dashboard tab - Overview and quick actions
2. Cache tab - Configure caching options
3. Optimization tab - Minification and file optimization
4. Media tab - Lazy loading settings
5. Database tab - Database optimization tools
6. Advanced tab - CDN and advanced features

== Changelog ==

= 1.0.0 =
* Initial release
* Page caching system
* HTML/CSS/JS minification
* Lazy loading for images and iframes
* Database optimization
* CDN integration
* Browser caching and GZIP
* Comprehensive admin interface

== Upgrade Notice ==

= 1.0.0 =
Initial release of VelocityWP.

== Developer Hooks ==

VelocityWP provides several hooks for developers:

* `wpsb_before_cache_save` - Before cache file is saved
* `wpsb_after_cache_save` - After cache file is saved
* `wpsb_before_cache_clear` - Before cache is cleared
* `wpsb_after_cache_clear` - After cache is cleared
* `wpsb_minify_html` - Filter minified HTML
* `wpsb_minify_css` - Filter minified CSS
* `wpsb_minify_js` - Filter minified JavaScript
* `wpsb_lazy_load_placeholder` - Filter lazy load placeholder
* `wpsb_cache_exclude_urls` - Filter URLs excluded from cache

== WP-CLI Support ==

Clear cache: `wp cache flush`
Optimize database: `wp wpsb optimize-db`

== Credits ==

Developed by mgrandusky
