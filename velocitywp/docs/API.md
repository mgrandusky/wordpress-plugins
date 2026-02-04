# VelocityWP API Documentation

Developer documentation for programmatically interacting with VelocityWP features, hooks, and functions.

---

## Table of Contents

1. [Overview](#overview)
2. [Action Hooks](#action-hooks)
3. [Filter Hooks](#filter-hooks)
4. [Functions](#functions)
5. [Constants](#constants)
6. [WP-CLI Commands](#wp-cli-commands)
7. [REST API Endpoints](#rest-api-endpoints)
8. [Examples](#examples)

---

## Overview

VelocityWP provides extensive hooks and functions for developers to customize behavior, integrate with other plugins, and automate tasks.

### Namespace

All VelocityWP functions use the `velocitywp_` prefix to avoid conflicts.

### Requirements

- WordPress 5.0+
- PHP 7.4+
- VelocityWP 1.0.0+

---

## Action Hooks

### Cache Management

#### `velocitywp_before_cache_save`

Fires before content is saved to cache.

**Parameters:**
- `string $cache_key` - Unique cache identifier
- `mixed $content` - Content to be cached
- `int $ttl` - Time to live in seconds

**Example:**

```php
add_action( 'velocitywp_before_cache_save', function( $cache_key, $content, $ttl ) {
    error_log( "Caching {$cache_key} for {$ttl} seconds" );
}, 10, 3 );
```

#### `velocitywp_after_cache_save`

Fires after content is saved to cache.

**Parameters:**
- `string $cache_key` - Unique cache identifier
- `mixed $content` - Cached content
- `bool $success` - Whether save was successful

**Example:**

```php
add_action( 'velocitywp_after_cache_save', function( $cache_key, $content, $success ) {
    if ( $success ) {
        error_log( "Successfully cached {$cache_key}" );
    }
}, 10, 3 );
```

#### `velocitywp_before_cache_clear`

Fires before cache is cleared.

**Parameters:**
- `string $type` - Type of cache being cleared (all, object, fragment, cloudflare)

**Example:**

```php
add_action( 'velocitywp_before_cache_clear', function( $type ) {
    error_log( "Clearing {$type} cache" );
} );
```

#### `velocitywp_after_cache_clear`

Fires after cache is cleared.

**Parameters:**
- `string $type` - Type of cache cleared
- `bool $success` - Whether clear was successful

**Example:**

```php
add_action( 'velocitywp_after_cache_clear', function( $type, $success ) {
    if ( $success ) {
        // Notify admin
        wp_mail( get_option( 'admin_email' ), 'Cache Cleared', "{$type} cache was cleared" );
    }
}, 10, 2 );
```

### Database Optimization

#### `velocitywp_before_database_optimization`

Fires before database optimization runs.

**Example:**

```php
add_action( 'velocitywp_before_database_optimization', function() {
    // Backup database before optimization
    do_action( 'backup_database' );
} );
```

#### `velocitywp_after_database_optimization`

Fires after database optimization completes.

**Parameters:**
- `array $results` - Optimization results

**Example:**

```php
add_action( 'velocitywp_after_database_optimization', function( $results ) {
    error_log( 'Database optimization results: ' . print_r( $results, true ) );
} );
```

### Image Optimization

#### `velocitywp_before_image_optimization`

Fires before image is optimized.

**Parameters:**
- `int $attachment_id` - Attachment ID
- `string $file_path` - Full path to image file

**Example:**

```php
add_action( 'velocitywp_before_image_optimization', function( $attachment_id, $file_path ) {
    // Backup original before optimization
    copy( $file_path, $file_path . '.backup' );
}, 10, 2 );
```

#### `velocitywp_after_image_optimization`

Fires after image is optimized.

**Parameters:**
- `int $attachment_id` - Attachment ID
- `array $results` - Optimization results (original_size, optimized_size, savings)

**Example:**

```php
add_action( 'velocitywp_after_image_optimization', function( $attachment_id, $results ) {
    $savings_percent = ( $results['savings'] / $results['original_size'] ) * 100;
    error_log( "Image {$attachment_id} optimized: {$savings_percent}% smaller" );
}, 10, 2 );
```

### Critical CSS

#### `velocitywp_critical_css_generated`

Fires when critical CSS is generated.

**Parameters:**
- `int $post_id` - Post ID (or 0 for global)
- `string $css` - Generated CSS

**Example:**

```php
add_action( 'velocitywp_critical_css_generated', function( $post_id, $css ) {
    // Save to custom location
    file_put_contents( "/custom/path/critical-{$post_id}.css", $css );
}, 10, 2 );
```

---

## Filter Hooks

### Cache Filters

#### `velocitywp_cache_ttl`

Modify cache TTL (time to live).

**Parameters:**
- `int $ttl` - Default TTL in seconds
- `string $type` - Cache type (widget, menu, sidebar, shortcode)

**Returns:** `int` - Modified TTL

**Example:**

```php
add_filter( 'velocitywp_cache_ttl', function( $ttl, $type ) {
    // Longer cache for menus
    if ( 'menu' === $type ) {
        return 86400; // 24 hours
    }
    
    // Shorter cache for widgets on homepage
    if ( 'widget' === $type && is_front_page() ) {
        return 600; // 10 minutes
    }
    
    return $ttl;
}, 10, 2 );
```

#### `velocitywp_cache_key`

Modify cache key generation.

**Parameters:**
- `string $key` - Generated cache key
- `array $args` - Arguments used to generate key

**Returns:** `string` - Modified cache key

**Example:**

```php
add_filter( 'velocitywp_cache_key', function( $key, $args ) {
    // Add user role to cache key for role-specific caching
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $key .= '_' . $user->roles[0];
    }
    return $key;
}, 10, 2 );
```

#### `velocitywp_cache_exclude_urls`

Exclude URLs from caching.

**Parameters:**
- `array $excluded` - Array of URL patterns to exclude

**Returns:** `array` - Modified exclusion list

**Example:**

```php
add_filter( 'velocitywp_cache_exclude_urls', function( $excluded ) {
    $excluded[] = '/my-dynamic-page/*';
    $excluded[] = '/api/*';
    $excluded[] = '/?custom_param=*';
    return $excluded;
} );
```

### Content Filters

#### `velocitywp_minify_html`

Modify HTML before/after minification.

**Parameters:**
- `string $html` - HTML content

**Returns:** `string` - Modified HTML

**Example:**

```php
add_filter( 'velocitywp_minify_html', function( $html ) {
    // Add custom meta tag
    $html = str_replace( '</head>', '<meta name="optimized" content="velocitywp"></head>', $html );
    return $html;
} );
```

#### `velocitywp_minify_css`

Modify CSS before/after minification.

**Parameters:**
- `string $css` - CSS content

**Returns:** `string` - Modified CSS

**Example:**

```php
add_filter( 'velocitywp_minify_css', function( $css ) {
    // Add custom CSS variable
    $css = ':root { --optimized: true; } ' . $css;
    return $css;
} );
```

#### `velocitywp_critical_css`

Modify critical CSS before inline output.

**Parameters:**
- `string $css` - Critical CSS content
- `int $post_id` - Post ID (or 0 for global)

**Returns:** `string` - Modified CSS

**Example:**

```php
add_filter( 'velocitywp_critical_css', function( $css, $post_id ) {
    // Add custom critical CSS for all pages
    $custom_css = '.above-fold { display: block; }';
    return $custom_css . ' ' . $css;
}, 10, 2 );
```

### Lazy Loading Filters

#### `velocitywp_lazy_load_placeholder`

Modify lazy load placeholder image.

**Parameters:**
- `string $placeholder` - Placeholder image URL

**Returns:** `string` - Modified placeholder URL

**Example:**

```php
add_filter( 'velocitywp_lazy_load_placeholder', function( $placeholder ) {
    // Use custom placeholder
    return 'data:image/svg+xml,...'; // Custom SVG
} );
```

#### `velocitywp_lazy_load_skip_images`

Set number of images to skip lazy loading.

**Parameters:**
- `int $skip` - Number of images to skip
- `string $context` - Context (homepage, single, archive, etc.)

**Returns:** `int` - Modified skip count

**Example:**

```php
add_filter( 'velocitywp_lazy_load_skip_images', function( $skip, $context ) {
    // Skip more images on homepage
    if ( 'homepage' === $context ) {
        return 3;
    }
    return $skip;
}, 10, 2 );
```

### Database Filters

#### `velocitywp_db_revisions_to_keep`

Set number of post revisions to keep.

**Parameters:**
- `int $keep` - Number of revisions to keep

**Returns:** `int` - Modified count

**Example:**

```php
add_filter( 'velocitywp_db_revisions_to_keep', function( $keep ) {
    // Keep more revisions for important post types
    if ( 'product' === get_post_type() ) {
        return 10;
    }
    return $keep;
} );
```

---

## Functions

### Cache Functions

#### `velocitywp_get_cache( $key, $group = '' )`

Retrieve cached data.

**Parameters:**
- `string $key` - Cache key
- `string $group` - Optional cache group

**Returns:** `mixed` - Cached data or `false` if not found

**Example:**

```php
$data = velocitywp_get_cache( 'my_expensive_query', 'custom' );
if ( false === $data ) {
    // Data not cached, generate it
    $data = perform_expensive_operation();
    velocitywp_set_cache( 'my_expensive_query', $data, 3600, 'custom' );
}
```

#### `velocitywp_set_cache( $key, $value, $ttl = 3600, $group = '' )`

Save data to cache.

**Parameters:**
- `string $key` - Cache key
- `mixed $value` - Data to cache
- `int $ttl` - Time to live in seconds
- `string $group` - Optional cache group

**Returns:** `bool` - Success status

**Example:**

```php
$products = get_popular_products(); // Expensive query
velocitywp_set_cache( 'popular_products', $products, 7200 );
```

#### `velocitywp_delete_cache( $key, $group = '' )`

Delete cached data.

**Parameters:**
- `string $key` - Cache key
- `string $group` - Optional cache group

**Returns:** `bool` - Success status

**Example:**

```php
// Clear cache when product is updated
add_action( 'save_post_product', function( $post_id ) {
    velocitywp_delete_cache( 'popular_products' );
    velocitywp_delete_cache( "product_{$post_id}" );
} );
```

#### `velocitywp_clear_all_cache()`

Clear all VelocityWP caches.

**Returns:** `bool` - Success status

**Example:**

```php
// Clear all caches after theme switch
add_action( 'after_switch_theme', function() {
    velocitywp_clear_all_cache();
} );
```

### Performance Functions

#### `velocitywp_get_metrics()`

Get current performance metrics.

**Returns:** `array` - Performance data

**Example:**

```php
$metrics = velocitywp_get_metrics();
echo "Page generation time: " . $metrics['generation_time'] . "s\n";
echo "Database queries: " . $metrics['query_count'] . "\n";
echo "Memory usage: " . $metrics['memory_usage'] . "MB\n";
```

#### `velocitywp_is_cache_enabled()`

Check if caching is enabled.

**Returns:** `bool` - Cache status

**Example:**

```php
if ( velocitywp_is_cache_enabled() ) {
    // Use cached data
    $data = velocitywp_get_cache( 'key' );
} else {
    // Fetch fresh data
    $data = get_fresh_data();
}
```

### Image Functions

#### `velocitywp_optimize_image( $attachment_id )`

Optimize a specific image.

**Parameters:**
- `int $attachment_id` - Attachment ID

**Returns:** `array` - Optimization results

**Example:**

```php
$results = velocitywp_optimize_image( 123 );
if ( $results['success'] ) {
    echo "Saved {$results['savings_percent']}%";
}
```

#### `velocitywp_generate_webp( $attachment_id )`

Generate WebP version of image.

**Parameters:**
- `int $attachment_id` - Attachment ID

**Returns:** `string|false` - WebP file path or false on failure

**Example:**

```php
$webp_path = velocitywp_generate_webp( 123 );
if ( $webp_path ) {
    echo "WebP created: {$webp_path}";
}
```

### Critical CSS Functions

#### `velocitywp_generate_critical_css( $url, $viewport = 'desktop' )`

Generate critical CSS for a URL.

**Parameters:**
- `string $url` - URL to analyze
- `string $viewport` - Viewport size (desktop, mobile, tablet)

**Returns:** `string|false` - Critical CSS or false on failure

**Example:**

```php
$css = velocitywp_generate_critical_css( home_url(), 'mobile' );
if ( $css ) {
    update_option( 'velocitywp_mobile_critical_css', $css );
}
```

---

## Constants

### Plugin Constants

```php
// Plugin version
VELOCITYWP_VERSION // '1.0.0'

// Plugin directory path
VELOCITYWP_PLUGIN_DIR // '/path/to/wp-content/plugins/velocitywp/'

// Plugin URL
VELOCITYWP_PLUGIN_URL // 'https://example.com/wp-content/plugins/velocitywp/'

// Cache directory
VELOCITYWP_CACHE_DIR // '/path/to/wp-content/cache/velocitywp/'
```

### Configuration Constants

```php
// Define in wp-config.php to override settings

// Enable/disable object cache
define( 'VELOCITYWP_ENABLE_OBJECT_CACHE', true );

// Enable/disable debug mode
define( 'VELOCITYWP_DEBUG', true );

// Set Redis host
define( 'VELOCITYWP_REDIS_HOST', '127.0.0.1' );

// Set Redis port
define( 'VELOCITYWP_REDIS_PORT', 6379 );

// Set cache TTL
define( 'VELOCITYWP_DEFAULT_TTL', 3600 );
```

---

## WP-CLI Commands

### Cache Commands

```bash
# Clear all caches
wp velocitywp cache clear

# Clear specific cache type
wp velocitywp cache clear --type=object
wp velocitywp cache clear --type=fragment
wp velocitywp cache clear --type=cloudflare

# Get cache statistics
wp velocitywp cache stats

# Warm cache for all pages
wp velocitywp cache warmup
```

### Database Commands

```bash
# Optimize database
wp velocitywp db optimize

# Clean database (revisions, auto-drafts, etc.)
wp velocitywp db clean

# Get database statistics
wp velocitywp db stats
```

### Image Commands

```bash
# Optimize all images
wp velocitywp images optimize --all

# Optimize specific image
wp velocitywp images optimize --id=123

# Generate WebP for all images
wp velocitywp images webp --all

# Get optimization statistics
wp velocitywp images stats
```

### Critical CSS Commands

```bash
# Generate critical CSS for homepage
wp velocitywp criticalcss generate --url=https://example.com

# Generate for all pages
wp velocitywp criticalcss generate --all

# Clear critical CSS cache
wp velocitywp criticalcss clear
```

---

## REST API Endpoints

### Get Performance Metrics

```
GET /wp-json/velocitywp/v1/metrics
```

**Response:**

```json
{
  "generation_time": 0.245,
  "query_count": 12,
  "memory_usage": 14.5,
  "cache_hit_rate": 0.92,
  "core_web_vitals": {
    "lcp": 1.2,
    "fid": 45,
    "cls": 0.05
  }
}
```

### Clear Cache

```
POST /wp-json/velocitywp/v1/cache/clear
```

**Body:**

```json
{
  "type": "all"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Cache cleared successfully"
}
```

### Get Cache Statistics

```
GET /wp-json/velocitywp/v1/cache/stats
```

**Response:**

```json
{
  "object_cache": {
    "hits": 15420,
    "misses": 1230,
    "hit_rate": 0.926
  },
  "fragment_cache": {
    "keys": 145,
    "size": "2.4MB"
  }
}
```

---

## Examples

### Example 1: Custom Widget Caching

```php
<?php
/**
 * Custom widget with caching.
 */
class My_Custom_Widget extends WP_Widget {
    
    public function widget( $args, $instance ) {
        // Generate unique cache key
        $cache_key = 'my_widget_' . md5( serialize( $instance ) );
        
        // Try to get from cache
        $output = velocitywp_get_cache( $cache_key, 'widgets' );
        
        if ( false === $output ) {
            // Not cached, generate output
            ob_start();
            
            echo $args['before_widget'];
            echo '<h3>' . esc_html( $instance['title'] ) . '</h3>';
            // ... widget content ...
            echo $args['after_widget'];
            
            $output = ob_get_clean();
            
            // Cache for 1 hour
            velocitywp_set_cache( $cache_key, $output, 3600, 'widgets' );
        }
        
        echo $output;
    }
}
```

### Example 2: Conditional Critical CSS

```php
<?php
/**
 * Add custom critical CSS based on page type.
 */
add_filter( 'velocitywp_critical_css', function( $css, $post_id ) {
    // Add product-specific critical CSS
    if ( 'product' === get_post_type( $post_id ) ) {
        $css .= '
            .product-gallery { display: grid; }
            .product-info { display: block; }
            .add-to-cart { display: inline-block; }
        ';
    }
    
    // Add blog-specific critical CSS
    if ( is_singular( 'post' ) ) {
        $css .= '
            .post-header { display: block; }
            .post-content { display: block; }
            .post-meta { display: flex; }
        ';
    }
    
    return $css;
}, 10, 2 );
```

### Example 3: Custom Cache Invalidation

```php
<?php
/**
 * Clear related caches when product is updated.
 */
add_action( 'save_post_product', function( $post_id ) {
    // Clear product cache
    velocitywp_delete_cache( "product_{$post_id}" );
    
    // Clear category caches
    $categories = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'ids' ) );
    foreach ( $categories as $cat_id ) {
        velocitywp_delete_cache( "category_{$cat_id}_products" );
    }
    
    // Clear homepage cache
    velocitywp_delete_cache( 'homepage_featured_products' );
    
    // Clear Cloudflare cache
    do_action( 'velocitywp_purge_cloudflare_url', get_permalink( $post_id ) );
} );
```

### Example 4: Performance Monitoring Integration

```php
<?php
/**
 * Log slow pages to custom system.
 */
add_action( 'wp_footer', function() {
    $metrics = velocitywp_get_metrics();
    
    // Alert if generation time exceeds 2 seconds
    if ( $metrics['generation_time'] > 2 ) {
        error_log( sprintf(
            'Slow page detected: %s (%.2fs, %d queries)',
            $_SERVER['REQUEST_URI'],
            $metrics['generation_time'],
            $metrics['query_count']
        ) );
        
        // Send to monitoring service
        send_to_monitoring_service( $metrics );
    }
} );
```

### Example 5: Custom Image Optimization Pipeline

```php
<?php
/**
 * Add custom optimization step after VelocityWP optimization.
 */
add_action( 'velocitywp_after_image_optimization', function( $attachment_id, $results ) {
    // Upload optimized image to CDN
    $file_path = get_attached_file( $attachment_id );
    upload_to_cdn( $file_path );
    
    // Update attachment meta with CDN URL
    $cdn_url = get_cdn_url_for_file( $file_path );
    update_post_meta( $attachment_id, '_cdn_url', $cdn_url );
    
    // Log optimization
    error_log( sprintf(
        'Image %d optimized: %d%% smaller, uploaded to CDN',
        $attachment_id,
        $results['savings_percent']
    ) );
}, 10, 2 );
```

---

## Need More Help?

- **Documentation:** [Full documentation](../)
- **Issues:** [GitHub Issues](https://github.com/mgrandusky/wordpress-plugins/issues)
- **Discussions:** [GitHub Discussions](https://github.com/mgrandusky/wordpress-plugins/discussions)

---

**Happy coding!** 🚀
