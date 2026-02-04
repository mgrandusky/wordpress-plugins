# VelocityWP - Frequently Asked Questions

Common questions and answers about VelocityWP configuration, troubleshooting, and optimization.

---

## Table of Contents

1. [General Questions](#general-questions)
2. [Installation & Setup](#installation--setup)
3. [Compatibility](#compatibility)
4. [Performance](#performance)
5. [Caching](#caching)
6. [Image Optimization](#image-optimization)
7. [WooCommerce](#woocommerce)
8. [Troubleshooting](#troubleshooting)
9. [Hosting & Server](#hosting--server)
10. [Advanced Topics](#advanced-topics)

---

## General Questions

### What is VelocityWP?

VelocityWP is a comprehensive WordPress performance optimization plugin that includes 12 powerful modules: object caching, lazy loading, critical CSS, image optimization, database optimization, and more. It's designed to dramatically improve page load speed and achieve perfect Core Web Vitals scores.

### Is VelocityWP free?

Yes! VelocityWP is completely free and open-source, licensed under GPL v2 or later.

### How is this different from WP Rocket or other premium plugins?

VelocityWP offers similar features to premium plugins but is completely free. Key features include:
- Object caching (Redis, Memcached, APCu)
- Fragment caching
- Critical CSS generation
- WebP image conversion
- Cloudflare integration
- Performance monitoring
- And 6 more comprehensive modules

### Do I need technical knowledge to use VelocityWP?

No! VelocityWP includes safe, pre-configured defaults that work for most sites. The Quick Start guide helps you enable basic optimizations in minutes. Advanced users can access detailed configuration options.

### Will VelocityWP break my site?

VelocityWP is designed with safety in mind and has been thoroughly tested. However, it's always recommended to:
1. Backup your site first
2. Test on a staging site if possible
3. Enable features gradually
4. Start with conservative settings

### Can I use VelocityWP with other performance plugins?

It's **not recommended** to use multiple caching/performance plugins simultaneously as they may conflict. If you're switching from another plugin:
1. Deactivate and uninstall the old plugin
2. Clear all caches
3. Install and configure VelocityWP
4. Test thoroughly

---

## Installation & Setup

### What are the minimum requirements?

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher (8.0+ recommended)
- **MySQL:** 5.6 or higher (or MariaDB 10.0+)
- **Memory:** 64MB minimum (128MB+ recommended)

### How do I install VelocityWP?

**Via WordPress Admin:**
1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click Install Now
4. Activate the plugin

See [INSTALLATION.md](INSTALLATION.md) for detailed instructions and alternative methods.

### Where are the plugin settings?

After activation, go to **Settings → VelocityWP** in your WordPress admin dashboard.

### Do I need to install Redis or Memcached?

No, these are optional but highly recommended for optimal performance. VelocityWP works fine without them, but object caching provides significant performance benefits (50-80% fewer database queries).

### How do I know if Redis is installed?

Run this command via SSH:
```bash
redis-cli ping
```

If it returns `PONG`, Redis is installed and running.

### What's the recommended first-time setup?

Start with these safe features:
1. **Lazy Loading** (images and iframes)
2. **Heartbeat Control** (disable on frontend)
3. **Resource Hints** (DNS prefetch and preconnect)
4. **Database Optimization** (weekly schedule)

Then gradually add advanced features after testing.

---

## Compatibility

### Is VelocityWP compatible with my theme?

Yes! VelocityWP is designed to work with any WordPress theme. It follows WordPress best practices and doesn't modify theme files.

### Does it work with WooCommerce?

Absolutely! VelocityWP includes specific WooCommerce optimizations:
- Cart fragment control
- Script management (remove on non-shop pages)
- Session optimization
- Geolocation control

### Is it compatible with page builders?

Yes, tested with:
- ✅ Elementor
- ✅ Beaver Builder
- ✅ Divi Builder
- ✅ WPBakery
- ✅ Gutenberg (block editor)

### What about Yoast SEO, Rank Math, etc.?

VelocityWP is compatible with all major SEO plugins. No conflicts have been reported.

### Does it work with Contact Form 7, Gravity Forms, etc.?

Yes, form plugins work normally. Forms are automatically excluded from aggressive optimizations.

### Can I use it with Cloudflare?

Yes! VelocityWP has direct Cloudflare integration including:
- Automatic cache purging
- APO (Automatic Platform Optimization) support
- Development mode toggle
- Analytics

### Is it multisite compatible?

Yes, VelocityWP fully supports WordPress multisite networks.

---

## Performance

### How much faster will my site be?

Results vary based on your site, but typical improvements:
- Page load time: 50-90% faster
- Page size: 30-80% smaller
- Database queries: 50-90% fewer
- Server load: 40-80% reduction

See real-world examples in [README.md](../README.md#combined-performance-impact).

### How long does it take to see results?

Immediate features (instant results):
- Lazy loading
- Heartbeat control
- Resource hints

Caching features (require cache warm-up):
- Object caching: 1-2 hours
- Fragment caching: 1-2 hours
- Cloudflare APO: 2-4 hours

Database optimization: Immediate for cleanup, cumulative for ongoing benefits.

### What are Core Web Vitals and why do they matter?

Core Web Vitals are Google's metrics for measuring user experience:
- **LCP** (Largest Contentful Paint): Main content load time
- **FID** (First Input Delay): Interactivity
- **CLS** (Cumulative Layout Shift): Visual stability

They directly affect Google search rankings. VelocityWP helps achieve perfect scores.

### How can I measure performance improvements?

**Before and After:**
1. Run PageSpeed Insights (https://pagespeed.web.dev/)
2. Run GTmetrix (https://gtmetrix.com/)
3. Note current scores

**After enabling VelocityWP:**
1. Wait 24 hours for cache to build
2. Run the same tests again
3. Compare results

**Use built-in monitoring:**
- VelocityWP → Performance Monitoring
- Track Core Web Vitals over time
- Compare before/after data

### My site is already fast. Will this help?

Yes! Even fast sites benefit from:
- Reduced server load (lower hosting costs)
- Better Core Web Vitals (higher Google rankings)
- Improved mobile performance
- Better international load times
- Future-proofing for traffic growth

---

## Caching

### What's the difference between page caching, object caching, and fragment caching?

| Type | What It Caches | When to Use |
|------|---------------|-------------|
| **Object Cache** | Database queries, WordPress objects | All sites (high priority) |
| **Fragment Cache** | Widgets, sidebars, menus | Sites with expensive widgets |
| **Page Cache** | Entire HTML output | Static sites (not included in VelocityWP, use CDN) |

### Do I need all types of caching?

**Essential:** Object caching (if available)
**Recommended:** Fragment caching
**Optional:** Page-level caching (use Cloudflare APO instead)

### How do I clear the cache?

**All Caches:**
```
VelocityWP → Dashboard → "Clear All Caches"
```

**Specific Caches:**
- Object Cache: VelocityWP → Object Cache → Clear
- Fragment Cache: VelocityWP → Fragment Cache → Clear
- Cloudflare: VelocityWP → Cloudflare → Purge

**Via WP-CLI:**
```bash
wp cache flush
wp velocitywp cache clear
```

### When should I clear the cache?

Cache is automatically cleared when:
- Post published/updated
- Theme changed
- Plugin activated/deactivated
- Widgets modified

Manual clearing needed for:
- Major configuration changes
- Troubleshooting display issues
- After theme customization

### Why is my cache hit rate low?

**Common causes:**
1. **Just enabled:** Wait 1-2 hours for cache to build
2. **TTL too short:** Increase cache lifetime
3. **High traffic:** Cache is being frequently regenerated
4. **Not enough memory:** Increase Redis/Memcached memory

**Check:**
```
VelocityWP → Object Cache → Statistics
Target hit rate: 90%+
```

### Can I exclude specific pages from caching?

Yes! For fragment caching:
```
VelocityWP → Fragment Cache → Exclusions
Add page IDs or URL patterns
```

For Cloudflare:
```
VelocityWP → Cloudflare → Cache Rules
Bypass specific URLs
```

---

## Image Optimization

### What's the difference between image optimization and WebP conversion?

**Image Optimization:**
- Reduces file size without converting format
- Removes metadata
- Compresses image data
- Savings: 30-60%

**WebP Conversion:**
- Converts to modern WebP format
- Additional compression
- Browser-dependent
- Savings: Additional 25-35% beyond optimization

Combined: 50-80% total size reduction!

### Do I need to re-upload images?

No! Use the **Bulk Optimization** tool:
```
VelocityWP → Images → Bulk Optimization
Select:
- Optimize existing images: ✅
- Convert to WebP: ✅
- Backup originals: ✅ (recommended)

Click "Start Bulk Optimization"
```

This processes all existing images in your media library.

### Will WebP work in all browsers?

VelocityWP automatically serves:
- **WebP** to supporting browsers (Chrome, Firefox, Edge, Safari 14+)
- **Original format** to older browsers
- Uses `<picture>` element for compatibility

### Can I adjust image quality?

Yes!
```
VelocityWP → Images → Quality

Recommended settings:
- Photos: 82-85 quality
- Graphics/screenshots: 85-90 quality
- Thumbnails: 75-80 quality
```

Lower quality = smaller files but may show artifacts.

### What happens to original images?

**Default behavior:**
- Original images are preserved
- Optimized versions replace them
- WebP versions created alongside

**With backup enabled:**
- Originals stored in separate folder
- Can restore if needed
- Takes more disk space

### My images look blurry after optimization!

**Solution:**
1. Go to VelocityWP → Images
2. Increase quality setting (try 90)
3. Click "Regenerate All"
4. If still blurry, disable optimization for specific images

**Exclude images:**
```
Add CSS class: no-optimize
<img src="..." class="no-optimize">
```

---

## WooCommerce

### Will this slow down my WooCommerce store?

No, the opposite! VelocityWP includes specific WooCommerce optimizations that can make your store 50-75% faster.

### What's "cart fragmentation" and should I disable it?

Cart fragments are AJAX requests that update the cart count in the header. They can cause:
- 2-10 requests per page
- 50-200KB per request
- Slowdowns on non-shop pages

**Recommended:**
```
VelocityWP → WooCommerce
✅ Disable cart fragments on non-shop pages
✅ Or set update frequency to 8+ hours
```

Your cart will still work, just updates less frequently on blog/pages.

### Can I remove WooCommerce scripts from blog posts?

Yes!
```
VelocityWP → WooCommerce → Script Management
✅ Remove WooCommerce CSS on non-shop pages
✅ Remove WooCommerce JS on non-shop pages
```

Savings: 800KB - 1.3MB per page!

### Will this break my checkout?

No. VelocityWP automatically excludes:
- Cart page
- Checkout page
- My Account page
- AJAX endpoints

These pages work normally with all WooCommerce functionality intact.

### Should I disable the password strength meter?

If you don't need strong password requirements:
```
VelocityWP → WooCommerce
✅ Disable password strength meter
```

Saves ~800KB on checkout page.

### My cart stopped updating in real-time!

This is intentional if you disabled cart fragments. The cart still works, it just updates:
- When you click cart icon
- When you go to cart/checkout
- On page refresh

If you need real-time updates, re-enable cart fragments or increase update frequency.

---

## Troubleshooting

### My site looks broken after enabling VelocityWP!

**Immediate fix:**
1. Go to VelocityWP → Dashboard
2. Click "Disable All Features"
3. Clear all caches

**Then identify the issue:**
1. Enable features one at a time
2. Test after each
3. When site breaks, you've found the conflicting feature

**Common culprits:**
- Critical CSS (can hide content)
- JavaScript delay/defer
- Aggressive lazy loading

### Images aren't lazy loading

**Check:**
1. Feature enabled: VelocityWP → Lazy Loading → ✅ Enable
2. Clear cache and hard refresh (Ctrl+Shift+R)
3. Check browser console for errors
4. Verify images don't have `no-lazy` class

**Excluded by default:**
- First N images (preserves LCP)
- Images with `no-lazy` class
- Images in `<noscript>` tags

### Critical CSS broke my layout

**Quick fix:**
```
VelocityWP → Critical CSS
Mode: Disabled
Clear cache
```

**Proper fix:**
1. Regenerate critical CSS
2. Check for CSS conflicts
3. Add custom critical CSS if needed
4. Use per-template critical CSS

### Object cache isn't working

**Verify installation:**
```bash
# For Redis:
redis-cli ping
php -m | grep redis

# For Memcached:
echo "stats" | nc localhost 11211
php -m | grep memcached
```

**Test connection:**
```
VelocityWP → Object Cache
Click "Test Connection"
```

If it fails:
- Check server is running
- Verify PHP extension installed
- Check host/port settings

### Database optimization failed

**Check:**
1. Database credentials correct
2. User has OPTIMIZE permission
3. Table isn't corrupted

**Manual fix:**
```sql
-- Via phpMyAdmin or command line:
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
OPTIMIZE TABLE wp_options;
```

### Settings aren't saving

**Increase PHP limits:**
```php
// In wp-config.php:
ini_set('max_input_vars', 3000);
ini_set('max_execution_time', 300);
```

**Or edit php.ini:**
```
max_input_vars = 3000
max_execution_time = 300
post_max_size = 20M
```

### Performance didn't improve

**Checklist:**
- [ ] Cleared all caches after enabling features
- [ ] Waited 24 hours for cache to build
- [ ] Tested in incognito/private window
- [ ] Used different testing tool
- [ ] Checked server resources (not overloaded)

**Test properly:**
1. Clear all caches
2. Visit site in incognito window
3. Test with PageSpeed Insights
4. Check actual user experience

---

## Hosting & Server

### What hosting is recommended?

**VelocityWP works with any hosting, but performs best with:**

**Recommended:**
- WP Engine
- Kinsta
- SiteGround
- Cloudways
- Any host with Redis/Memcached

**Works well with:**
- Bluehost
- HostGator
- DreamHost
- Most shared hosting

**Avoid:**
- Hosts that block performance plugins
- Very limited resources (<512MB RAM)

### Do I need a VPS or dedicated server?

No! VelocityWP works great on:
- Shared hosting (with APCu object cache)
- VPS (with Redis/Memcached)
- Dedicated servers (optimal performance)

### My host doesn't support Redis. What should I do?

**Options:**
1. Use APCu (usually available on shared hosting)
2. Use Memcached if available
3. Use fragment caching (no external server needed)
4. Consider upgrading hosting
5. Use Cloudflare APO for edge caching

### Can I use this on localhost/development?

Yes! VelocityWP works on local development environments:
- XAMPP
- MAMP
- Local by Flywheel
- Docker
- Vagrant

Some features (like Cloudflare) require production environment.

### How much disk space does VelocityWP use?

**Plugin itself:** ~5MB

**With features enabled:**
- Object cache: In memory (no disk)
- Fragment cache: 10-50MB
- Image optimization: Original sizes
- WebP versions: +50-70% of image sizes
- Critical CSS: <1MB

**Example:**
- 1000 images (500MB total)
- Image optimization: No extra (replaces originals)
- WebP conversion: +250MB (50% additional)
- Total: 750MB

### Does VelocityWP increase server load?

**Short answer:** No, it decreases server load significantly.

**Details:**
- Object caching: 50-80% reduction in database queries
- Fragment caching: Reduces CPU usage
- Lazy loading: Reduces bandwidth
- Database optimization: Faster queries

**Only optimization that increases load:**
- Initial critical CSS generation (one-time)
- Initial WebP conversion (one-time)

After initial setup, server load decreases substantially.

---

## Advanced Topics

### Can I use VelocityWP programmatically?

Yes! See [API.md](API.md) for complete developer documentation.

**Quick examples:**

```php
// Clear specific cache
do_action('velocitywp_clear_cache_for_post', $post_id);

// Get performance metrics
$metrics = velocitywp_get_metrics();

// Check if cache is enabled
if (velocitywp_is_cache_enabled()) {
    // Do something
}
```

### How do I configure for staging vs production?

**Use environment-based configuration:**

```php
// In wp-config.php:
if (defined('WP_ENV') && WP_ENV === 'production') {
    define('VELOCITYWP_ENABLE_CACHE', true);
    define('VELOCITYWP_ENABLE_CDN', true);
} else {
    define('VELOCITYWP_ENABLE_CACHE', false);
    define('VELOCITYWP_ENABLE_CDN', false);
}
```

### Can I customize cache TTLs programmatically?

Yes!

```php
// Customize fragment cache TTL
add_filter('velocitywp_fragment_cache_ttl', function($ttl, $type) {
    if ($type === 'widget' && is_front_page()) {
        return 1800; // 30 minutes on homepage
    }
    return $ttl;
}, 10, 2);
```

### How do I debug caching issues?

**Enable debug mode:**
```php
// In wp-config.php:
define('VELOCITYWP_DEBUG', true);
```

**Check logs:**
```
/wp-content/debug.log
```

**Check cache keys:**
```php
// View all cache keys
$keys = velocitywp_get_cache_keys();
print_r($keys);
```

### Can I export/import settings?

Yes!
```
VelocityWP → Settings → Import/Export

Export: Saves all settings to JSON file
Import: Restore settings from JSON file
```

Perfect for:
- Moving staging → production
- Duplicating across multiple sites
- Backup before major changes

### How do I contribute to VelocityWP?

See [CONTRIBUTING.md](CONTRIBUTING.md) for:
- Code contribution guidelines
- Bug reporting
- Feature requests
- Testing procedures

---

## Still Need Help?

### Support Channels

1. **Documentation**
   - [Installation Guide](INSTALLATION.md)
   - [Feature Documentation](FEATURES.md)
   - [Configuration Guide](CONFIGURATION.md)
   - [API Documentation](API.md)

2. **Community**
   - [GitHub Discussions](https://github.com/mgrandusky/wordpress-plugins/discussions)
   - [Issue Tracker](https://github.com/mgrandusky/wordpress-plugins/issues)

3. **Contact**
   - Email: support@velocitywp.com

### Before Asking for Help

Please provide:
- WordPress version
- PHP version
- Theme name
- Active plugins list
- Steps to reproduce issue
- Error messages (if any)
- Screenshots (if visual issue)

This helps us help you faster!

---

**Can't find your question?** [Ask in the community](https://github.com/mgrandusky/wordpress-plugins/discussions) or [open an issue](https://github.com/mgrandusky/wordpress-plugins/issues/new).
