# VelocityWP Configuration Guide

This guide provides detailed configuration instructions for all VelocityWP features with real-world examples and best practices.

---

## Table of Contents

1. [Quick Start Configuration](#quick-start-configuration)
2. [Configuration by Site Type](#configuration-by-site-type)
3. [Feature-Specific Configuration](#feature-specific-configuration)
4. [Advanced Configuration](#advanced-configuration)
5. [Performance Tuning](#performance-tuning)
6. [Troubleshooting](#troubleshooting)

---

## Quick Start Configuration

### For First-Time Users

If you're new to performance optimization, start with these safe, proven settings:

#### Step 1: Enable Basic Features (5 minutes)

1. **Lazy Loading**
   ```
   Navigate to: VelocityWP → Lazy Loading
   
   ✅ Enable lazy loading for images
   ✅ Enable lazy loading for iframes
   Skip first images: 2
   Placeholder: Transparent
   ```
   
   **Impact:** 50-70% faster initial page load

2. **Heartbeat Control**
   ```
   Navigate to: VelocityWP → Heartbeat
   
   Frontend: Disabled
   Admin: 60 seconds
   Editor: 15 seconds
   ```
   
   **Impact:** 70-80% fewer AJAX requests

3. **Resource Hints**
   ```
   Navigate to: VelocityWP → Resource Hints
   
   ✅ Enable DNS Prefetch
   ✅ Enable Preconnect
   ✅ Auto-detect common services
   ```
   
   **Impact:** 200-500ms faster external resources

#### Step 2: Weekly Maintenance (Automated)

4. **Database Optimization**
   ```
   Navigate to: VelocityWP → Database
   
   ✅ Enable scheduled optimization
   Frequency: Weekly
   Time: 3:00 AM Sunday
   
   Clean:
   ✅ Post revisions (keep last 3)
   ✅ Auto-drafts (older than 7 days)
   ✅ Trash (older than 30 days)
   ✅ Spam comments
   ✅ Expired transients
   
   ✅ Optimize database tables
   ✅ Email report after optimization
   ```
   
   **Impact:** 10-30% database size reduction

#### Step 3: Advanced Features (If Available)

5. **Object Caching** (requires Redis/Memcached)
   ```
   Navigate to: VelocityWP → Object Cache
   
   Backend: Redis (or Memcached/APCu)
   Host: 127.0.0.1
   Port: 6379 (Redis) or 11211 (Memcached)
   
   Click "Test Connection"
   If successful: Enable
   ```
   
   **Impact:** 50-80% fewer database queries

6. **Image Optimization** (requires Imagick or GD)
   ```
   Navigate to: VelocityWP → Images
   
   ✅ Optimize on upload
   ✅ Convert to WebP
   Quality: 85
   Max width: 2560px
   Max height: 2560px
   ```
   
   **Impact:** 30-60% image size reduction

**Expected Results After Basic Setup:**
- Page load: 40-60% faster
- Page size: 30-50% smaller
- Server load: 40-60% reduction

---

## Configuration by Site Type

### Personal Blog

**Profile:**
- Low to medium traffic
- Mostly static content
- Simple functionality
- Affordable hosting

**Recommended Configuration:**

```yaml
Font Optimization:
  Self-host Google Fonts: ✅
  Font-display: swap
  Preload fonts: 1-2 critical fonts

Object Caching:
  Backend: APCu (if available) or disabled
  
Fragment Caching:
  Widgets: ✅ (TTL: 3600s)
  Menus: ✅ (TTL: 86400s)
  Sidebars: ✅ (TTL: 7200s)

Lazy Loading:
  Images: ✅ (skip first 2)
  Iframes: ✅
  Videos: ✅

Database Optimization:
  Schedule: Weekly
  Post revisions: Keep 3
  Auto-cleanup: ✅

Heartbeat Control:
  Frontend: Disabled
  Admin: 60s
  Editor: 15s

Resource Hints:
  DNS Prefetch: ✅
  Preconnect: ✅ (fonts, analytics)

Image Optimization:
  Optimize on upload: ✅
  WebP: ✅
  Quality: 85

Critical CSS:
  Mode: Automatic
  Per-template: ✅

Performance Monitoring:
  Enable: ✅ (track improvements)

WooCommerce:
  N/A

Cloudflare:
  Optional (free plan)
```

**Expected Results:**
- Page load: 70-80% faster
- Perfect Core Web Vitals scores
- Minimal maintenance required

---

### Business Website

**Profile:**
- Medium traffic
- Mix of static and dynamic content
- Contact forms, galleries
- Professional hosting

**Recommended Configuration:**

```yaml
Font Optimization:
  Self-host Google Fonts: ✅
  Font-display: swap
  Preload fonts: 2-3 critical fonts
  Subsetting: Latin only

Object Caching:
  Backend: Redis (recommended) or Memcached
  Host: 127.0.0.1
  TTL: 3600s
  
Fragment Caching:
  Widgets: ✅ (TTL: 3600s)
  Menus: ✅ (TTL: 43200s - 12 hours)
  Sidebars: ✅ (TTL: 7200s)
  Shortcodes: ✅ (TTL: 3600s)

Lazy Loading:
  Images: ✅ (skip first 3)
  Iframes: ✅
  Videos: ✅
  YouTube embeds: ✅ with thumbnail

Database Optimization:
  Schedule: Weekly
  Post revisions: Keep 5
  Auto-cleanup: ✅
  Email reports: ✅

Heartbeat Control:
  Frontend: Disabled
  Admin: 60s
  Editor: 15s

Resource Hints:
  DNS Prefetch: ✅
  Preconnect: ✅
  Preload: Critical resources

Image Optimization:
  Optimize on upload: ✅
  WebP: ✅
  Quality: 82-85
  Bulk optimization: Run once

Critical CSS:
  Mode: Automatic
  Per-template: ✅
  Mobile-specific: ✅

Performance Monitoring:
  Enable: ✅
  Track by device: ✅
  Email weekly reports: ✅

Cloudflare:
  Auto cache purge: ✅
  APO: ✅ (if subscribed)
  SSL: Full (Strict)
```

**Expected Results:**
- Page load: 75-85% faster
- Handle 3-5x more traffic
- Professional performance metrics

---

### E-commerce (WooCommerce)

**Profile:**
- High traffic, dynamic content
- Product catalog, shopping cart
- Checkout, user accounts
- Requires careful cache exclusions

**Recommended Configuration:**

```yaml
Font Optimization:
  Self-host Google Fonts: ✅
  Font-display: swap
  Preload: Product page fonts
  Subsetting: Customer languages

Object Caching:
  Backend: Redis (strongly recommended)
  Persistent: ✅
  Dedicated server: Recommended
  
Fragment Caching:
  Widgets: Selective
    - Product categories: ✅ (TTL: 21600s - 6 hours)
    - Recent products: ❌ (changes often)
    - Shopping cart: ❌ (user-specific)
  Menus: ✅ (TTL: 86400s)
  Shortcodes: ✅ for static content

Lazy Loading:
  Images: ✅ (skip first 1 - hero product)
  Iframes: ✅
  Product galleries: Smart loading
  YouTube reviews: ✅ with thumbnail

Database Optimization:
  Schedule: Daily (3 AM)
  Post revisions: Keep 3
  Transients: Clean expired
  Order cleanup: Manual only
  Email reports: ✅

Heartbeat Control:
  Frontend: Disabled
  Admin: 60s
  Editor: 30s (product editing)

Resource Hints:
  DNS Prefetch: ✅ (payment gateways)
  Preconnect: ✅ (checkout.stripe.com, etc.)
  Prefetch: Checkout page

Image Optimization:
  Optimize on upload: ✅
  WebP: ✅
  Quality: 85 (product images)
  Bulk optimization: ✅

Critical CSS:
  Mode: Automatic
  Per-template: ✅
    - Shop page
    - Product page
    - Cart page
    - Checkout page
  Defer CSS: ✅

Performance Monitoring:
  Enable: ✅
  Track by page type: ✅
  Conversion tracking: ✅
  Real user monitoring: ✅

WooCommerce Optimization:
  Cart fragments: Disabled on non-shop pages
  Script management: ✅
    - Remove on blog/pages
  Password strength meter: Remove
  Geolocation: Disabled
  Session optimization: ✅
  Widget queries: Optimize

Cloudflare:
  Auto cache purge: ✅
    - Products
    - Categories
    - Shop pages
  APO: ✅ with bypass for:
    - /cart
    - /checkout
    - /my-account
    - /?add-to-cart=*
  SSL: Full (Strict)
  Page Rules: Set up for cart/checkout
```

**Cache Exclusions (Critical!):**
```
Exclude from caching:
/cart
/checkout
/my-account
/?add-to-cart=*
/?remove_item=*
/?wc-ajax=*
```

**Expected Results:**
- Product pages: 70-80% faster
- Category pages: 75-85% faster
- Blog posts: 80-90% faster (WC scripts removed)
- Handle 5-10x more traffic
- Reduced server costs

---

### High-Traffic News Site

**Profile:**
- Very high traffic
- Frequently updated content
- Many authors, comments
- Media-heavy pages

**Recommended Configuration:**

```yaml
Font Optimization:
  Self-host Google Fonts: ✅
  Font-display: optional (performance first)
  Preload: 1 critical font only
  Subsetting: Aggressive (Latin only)

Object Caching:
  Backend: Redis cluster (multiple servers)
  Persistent: ✅
  Separate servers: Read/Write split
  
Fragment Caching:
  Widgets: ✅ (Aggressive TTLs)
    - Breaking news: 300s (5 min)
    - Trending: 600s (10 min)
    - Popular: 1800s (30 min)
    - Categories: 3600s (1 hour)
  Menus: ✅ (TTL: 3600s)
  Sidebars: ✅ (TTL: 600s)
  User-aware: ✅

Lazy Loading:
  Images: ✅ (skip first 1 - hero article)
  Iframes: ✅
  Videos: ✅
  YouTube embeds: ✅ with thumbnail
  Background images: ✅

Database Optimization:
  Schedule: Daily (3 AM)
  Post revisions: Keep 5
  Comments: Clean spam daily
  Tables: Optimize daily
  Archive old posts: ✅ (after 1 year)
  Email reports: ✅

Heartbeat Control:
  Frontend: Disabled
  Admin: 90s (multiple editors)
  Editor: 30s (real-time editing)

Resource Hints:
  DNS Prefetch: ✅ (all CDNs)
  Preconnect: ✅ (critical only)
  Prefetch: Category pages

Image Optimization:
  Optimize on upload: ✅
  WebP: ✅
  Quality: 80 (balance quality/size)
  Lazy optimization: ✅ (background)
  CDN offload: ✅

Critical CSS:
  Mode: Automatic
  Per-template: ✅
    - Homepage
    - Article (single)
    - Category
    - Author
  Mobile-specific: ✅
  Background generation: ✅

Performance Monitoring:
  Enable: ✅
  Real-time monitoring: ✅
  Alerts: ✅ (performance degradation)
  Historical data: 90 days
  A/B testing: ✅

Cloudflare:
  Auto cache purge: ✅
    - By URL (specific articles)
    - By tags (category updates)
  APO: ✅
  Cache Everything: ✅
  Edge Cache TTL: 2 hours
  Browser Cache TTL: 4 hours
  SSL: Full (Strict)
  HTTP/3: ✅
  Argo Smart Routing: ✅
```

**Server Configuration:**
```
Redis: 2+ servers (master-replica)
Memory: 16GB+ for Redis
PHP: 8.1+ with OPcache
PHP-FPM: 50+ workers
Database: Read replicas
```

**Expected Results:**
- Article pages: 80-90% faster
- Handle 10-20x more traffic
- 70-90% bandwidth savings
- Global 200+ edge caching
- Sub-second TTFB worldwide

---

## Feature-Specific Configuration

### Font Optimization Configuration

#### Basic Setup
```
Navigate to: VelocityWP → Fonts

✅ Enable Font Optimization
✅ Self-host Google Fonts
Font-display: swap
✅ Auto-detect fonts

Detected fonts will appear in list
Click "Download All Fonts"
```

#### Advanced Setup
```
✅ Enable Font Optimization
✅ Self-host Google Fonts
Font-display: swap
✅ Font subsetting
Subsets: latin, latin-ext

Preload fonts (one per line):
/wp-content/uploads/velocitywp-fonts/roboto-400.woff2
/wp-content/uploads/velocitywp-fonts/open-sans-600.woff2

✅ Enable resource hints
✅ DNS Prefetch for external fonts
```

**Testing:**
1. Check source code for preload tags
2. Test with PageSpeed Insights
3. Verify fonts load correctly
4. Check for FOIT/FOUT

---

### Object Cache Configuration

#### Redis Setup

**Step 1: Install Redis**
```bash
# Ubuntu/Debian
sudo apt-get install redis-server php-redis
sudo systemctl start redis-server

# Test installation
redis-cli ping
# Should return: PONG
```

**Step 2: Configure in VelocityWP**
```
Navigate to: VelocityWP → Object Cache

Backend: Redis
Host: 127.0.0.1
Port: 6379
Password: (leave blank if no auth)
Database: 0
Timeout: 1 second
Persistent: ✅

Click "Test Connection"
If successful: Click "Enable Object Cache"
```

**Step 3: Monitor Performance**
```
Navigate to: VelocityWP → Object Cache → Statistics

Monitor:
- Hit rate (target: 90%+)
- Memory usage
- Keys stored
- Evictions (should be 0 or low)
```

**Optimization:**
```
# Redis configuration (redis.conf)
maxmemory 256mb
maxmemory-policy allkeys-lru
save "" # Disable persistence for performance
```

---

### Critical CSS Configuration

#### Automatic Generation

**Step 1: Enable**
```
Navigate to: VelocityWP → Critical CSS

✅ Enable Critical CSS
Mode: Automatic
Viewport: 1920x1080 (desktop), 375x667 (mobile)
✅ Generate for mobile separately
```

**Step 2: Generate**
```
Generate critical CSS automatically:
- Click "Generate for Homepage"
- Click "Bulk Generate" for all pages
- Or enable auto-generation on publish
```

**Step 3: Verify**
```
View page source:
1. Look for <style> tag in <head>
2. Contains inline critical CSS
3. Full CSS loaded with preload
```

#### Manual Override

```
For specific pages:
1. Edit post/page
2. Find "Critical CSS" meta box
3. Paste custom CSS or click "Generate"
4. Saves per-page override
```

---

### WooCommerce Optimization

**Comprehensive Setup:**

```
Navigate to: VelocityWP → WooCommerce

Cart Fragments:
  ✅ Disable on non-shop pages
  ✅ Control AJAX update frequency
  Update interval: 8 hours (or disable)

Script Management:
  ✅ Remove WooCommerce CSS on:
     - Blog posts
     - Regular pages
     - Homepage (if not shop)
  
  ✅ Remove WooCommerce JS on:
     - Blog posts
     - Regular pages

Specific Features:
  ✅ Remove password strength meter
  ✅ Disable cart fragments completely (if not needed)
  ✅ Disable geolocation
  ❌ Keep reviews (or disable if not used)

Session Optimization:
  ✅ Don't create sessions for non-shop pages
  ✅ Clear expired sessions daily

Performance:
  ✅ Optimize product queries
  ✅ Optimize widget queries
  ✅ Lazy load product images
```

**Test After Configuration:**
1. Test checkout process
2. Test adding to cart
3. Test cart updates
4. Test product search
5. Verify user accounts work

---

## Advanced Configuration

### Multi-Site Network

**Network-Wide Settings:**
```
Network Admin → VelocityWP

Apply to all sites:
- Object caching
- Database optimization schedule
- Image optimization defaults
- Critical CSS generation

Site-specific:
- Content-specific settings
- Cache exclusions
- Cloudflare zones
```

### CDN Configuration

**Cloudflare:**
```
VelocityWP → Cloudflare

API Token: your-token
Zone ID: your-zone-id

Auto-purge triggers:
✅ Post publish/update
✅ Comment approval
✅ Theme change

Purge method: By URL (faster, more precise)
```

**Generic CDN:**
```
VelocityWP → CDN

CDN URL: https://cdn.yoursite.com
Replace URLs for:
✅ Images (.jpg, .png, .gif, .webp)
✅ CSS files
✅ JavaScript files
❌ Fonts (served locally)
```

### Developer Hooks

**Customize Behavior:**

```php
// Exclude URLs from caching
add_filter('velocitywp_cache_exclude_urls', function($excluded) {
    $excluded[] = '/custom-dynamic-page';
    return $excluded;
});

// Modify Critical CSS
add_filter('velocitywp_critical_css', function($css) {
    // Add custom critical CSS
    return $css . '.custom-class { display: block; }';
});

// Control fragment cache TTL
add_filter('velocitywp_fragment_cache_ttl', function($ttl, $type) {
    if ($type === 'widget' && is_front_page()) {
        return 300; // 5 minutes on homepage
    }
    return $ttl;
}, 10, 2);

// Custom cache purge
do_action('velocitywp_purge_cache'); // Purge all
do_action('velocitywp_purge_post_cache', $post_id); // Specific post
```

---

## Performance Tuning

### Finding Optimal Settings

#### Step 1: Baseline Measurement

```
Before enabling features:
1. Run PageSpeed Insights
2. Run GTmetrix
3. Note Core Web Vitals scores
4. Record page load time
5. Check server resource usage
```

#### Step 2: Enable Features Gradually

```
Week 1: Basic features
- Lazy loading
- Heartbeat control
- Resource hints

Week 2: Caching
- Object caching
- Fragment caching

Week 3: Advanced
- Critical CSS
- Image optimization

Week 4: Integration
- Cloudflare APO
- WooCommerce optimization
```

#### Step 3: Monitor & Adjust

```
Use VelocityWP → Performance Monitoring

Key metrics:
- LCP: Target <2.5s
- FID: Target <100ms
- CLS: Target <0.1
- TTFB: Target <600ms

If targets not met:
- Review settings
- Check for conflicts
- Adjust TTLs
- Optimize images further
```

### Common Optimization Scenarios

#### High Database Load

**Symptoms:**
- Slow query log showing issues
- High CPU from MySQL
- Long page generation times

**Solutions:**
```
1. Enable Object Caching (Redis recommended)
2. Increase Fragment Cache TTLs
3. Run Database Optimization
4. Add database indexes (carefully)
5. Consider read replicas
```

#### High Memory Usage

**Symptoms:**
- PHP memory limit errors
- Redis/Memcached evictions
- Server slowdowns

**Solutions:**
```
1. Increase PHP memory_limit (256M+)
2. Increase Redis maxmemory
3. Adjust cache TTLs (reduce)
4. Clear orphaned cache keys
5. Optimize image uploads (resize)
```

#### Slow International Load Times

**Symptoms:**
- Fast locally, slow globally
- High TTFB for distant users

**Solutions:**
```
1. Enable Cloudflare APO
2. Use global CDN
3. Optimize database queries
4. Enable object caching
5. Consider regional servers
```

---

## Troubleshooting

### Configuration Issues

#### "Settings Not Saving"

**Causes:**
- PHP max_input_vars too low
- Nonce timeout
- File permissions

**Solutions:**
```
1. Check PHP settings:
   max_input_vars = 3000

2. Increase WordPress nonce lifetime:
   define('NONCE_LIFE', 43200); // 12 hours

3. Check file permissions:
   chmod 644 wp-config.php
```

#### "Changes Not Visible"

**Causes:**
- Browser cache
- CDN cache
- Object cache

**Solutions:**
```
1. Hard refresh browser (Ctrl+Shift+R)
2. Clear Cloudflare cache
3. Clear object cache
4. Purge all caches in VelocityWP
```

### Feature-Specific Issues

See [FAQ.md](FAQ.md) for detailed troubleshooting.

---

## Configuration Checklist

Before going live with new configuration:

- [ ] Tested all forms (contact, search, etc.)
- [ ] Verified shopping cart works (if e-commerce)
- [ ] Checked all images load correctly
- [ ] Tested mobile responsiveness
- [ ] Verified user login/registration
- [ ] Checked admin functionality
- [ ] Ran PageSpeed Insights
- [ ] Tested Core Web Vitals
- [ ] Verified with incognito/private window
- [ ] Backup configuration (export settings)
- [ ] Documented custom settings

---

**Need more help?** See [FAQ.md](FAQ.md) or visit our [support forum](https://github.com/mgrandusky/wordpress-plugins/discussions).
