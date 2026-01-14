# EMERGENCY FIX TESTING GUIDE - Version 1.0.1

## Critical Issue Resolved

PR #9 changes were **NOT properly applied** to the main branch. The `get_thumbnail_url()` method was still returning Google's direct URLs instead of using the proxy endpoint.

This has now been fixed in version 1.0.1.

## What Was Changed

### 1. Fixed Proxy Endpoint Usage
**File**: `includes/class-gdrive-api.php`

The `get_thumbnail_url()` method now correctly returns:
```
http://your-site.com/gdrive-image/FILE_ID?size=medium
```

Instead of Google's direct URLs:
```
https://lh3.googleusercontent.com/drive-storage/...
```

### 2. Version Bumped to 1.0.1
**File**: `google-drive-gallery.php`

- Plugin header version: `1.0.1`
- Constant `GDRIVE_GALLERY_VERSION`: `1.0.1`

### 3. Emergency Cache Clearing
**Files**: 
- `includes/class-gdrive-cache.php` - New `clear_all()` method
- `google-drive-gallery.php` - Hook to call `clear_all()` on init

When `GDRIVE_FORCE_CACHE_CLEAR` is defined and set to `true`, the plugin will clear all caches on every page load.

### 4. Debug Logging
**File**: `includes/class-gdrive-api.php`

When `WP_DEBUG` is enabled, the plugin logs every thumbnail URL it generates to help troubleshoot issues.

### 5. Admin Version Notice
**File**: `includes/class-gdrive-admin.php`

The admin pages now display the current plugin version and proxy endpoint URL.

## Testing Instructions

### Step 1: Update Your wp-config.php

Add these lines to your `wp-config.php` file (before the "That's all, stop editing!" line):

```php
// Enable debugging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// Force cache clearing (TEMPORARY - remove after testing)
define( 'GDRIVE_FORCE_CACHE_CLEAR', true );
```

### Step 2: Deactivate and Reactivate the Plugin

1. Go to **Plugins** in WordPress admin
2. Deactivate "Google Drive Photo Gallery"
3. Reactivate "Google Drive Photo Gallery"

This ensures the new version is loaded and caches are cleared.

### Step 3: Verify Version in Admin

1. Go to **Settings > Google Drive Gallery**
2. You should see a blue notice box at the top with:
   - **Google Drive Gallery Version: 1.0.1**
   - **Proxy endpoint:** `http://your-site.com/gdrive-image/TEST_ID?size=medium`

If you see "Version: 1.0.0", the update didn't work properly.

### Step 4: Test Image Loading

1. Visit a page that displays a Google Drive gallery
2. Open browser Developer Tools (F12 or right-click > Inspect)
3. Go to the **Network** tab
4. Reload the page
5. Filter for image requests

**✅ SUCCESS**: Image URLs start with your site URL:
```
http://your-site.com/gdrive-image/1ABC...XYZ?size=medium
```

**❌ FAILURE**: Image URLs still point to Google:
```
https://lh3.googleusercontent.com/drive-storage/...
```

### Step 5: Check Debug Logs

1. Connect to your WordPress installation via FTP/SFTP or file manager
2. Navigate to `wp-content/debug.log`
3. Look for lines like:
```
[14-Jan-2026 18:58:00 UTC] GDrive Thumbnail URL generated: http://your-site.com/gdrive-image/1ABC...XYZ?size=medium
```

If you see these log entries, the new code is running correctly.

### Step 6: Verify Images Display

1. Check that images actually load (not broken)
2. Click on an image to open the lightbox
3. Verify full-size images also work

If images don't display:
- Check that the image proxy endpoint is registered
- Check for errors in the browser console
- Check WordPress error logs

## After Testing Successfully

Once you've confirmed images load correctly from the proxy endpoint:

### Remove Temporary Debug Settings

Edit your `wp-config.php` and **remove or comment out**:

```php
// Remove these after testing:
// define( 'WP_DEBUG', true );
// define( 'WP_DEBUG_LOG', true );
// define( 'GDRIVE_FORCE_CACHE_CLEAR', true );
```

Or at minimum, set `GDRIVE_FORCE_CACHE_CLEAR` to `false`:

```php
define( 'GDRIVE_FORCE_CACHE_CLEAR', false );
```

Leaving `GDRIVE_FORCE_CACHE_CLEAR` enabled will clear caches on **every page load**, which significantly impacts performance.

## Troubleshooting

### Images Still Load from Google URLs

**Possible Causes:**

1. **Browser Cache**: Hard refresh the page (Ctrl+Shift+R or Cmd+Shift+R)
2. **PHP Opcode Cache**: Restart PHP-FPM or Apache
3. **CDN/Proxy Cache**: Purge your CDN/proxy cache
4. **Page Cache Plugin**: Clear cache in plugins like WP Super Cache, W3 Total Cache, etc.

**Steps to Fix:**

```bash
# If you have SSH access:
# Restart PHP-FPM (example for PHP 8.1)
sudo systemctl restart php8.1-fpm

# Or restart Apache
sudo systemctl restart apache2

# Or restart Nginx
sudo systemctl restart nginx
```

### Version Still Shows 1.0.0

The plugin files weren't updated properly:

1. Re-upload the plugin files via FTP/SFTP
2. Make sure you're overwriting the existing files
3. Deactivate and reactivate the plugin
4. Hard refresh the browser cache

### Debug Log is Empty

Check that:
1. `WP_DEBUG` and `WP_DEBUG_LOG` are both set to `true`
2. The `wp-content` directory is writable
3. You're actually visiting a page with a gallery (the code only runs when galleries are displayed)

### Images Don't Load at All (Broken)

Check that:
1. The image proxy endpoint is registered (happens on plugin activation)
2. Your `.htaccess` or Nginx config allows custom rewrite rules
3. There are no 404 errors in the Network tab for `/gdrive-image/...` URLs

Try flushing rewrite rules:
1. Go to **Settings > Permalinks**
2. Click **Save Changes** (don't change anything)
3. This will flush and regenerate rewrite rules

## What's Different from PR #9?

PR #9 was supposed to change `get_thumbnail_url()` to use the proxy endpoint, but the changes weren't actually applied to the codebase. This emergency fix:

1. **Actually applies** the proxy endpoint changes
2. **Adds version bump** to force WordPress to recognize the update
3. **Adds cache clearing** to eliminate cached HTML/data
4. **Adds debug logging** to verify the code is running
5. **Adds admin notice** to confirm version

## Support

If images still load from Google URLs after following all these steps:

1. Take a screenshot of the browser Network tab showing the image URLs
2. Copy the contents of `wp-content/debug.log` (or the last 100 lines)
3. Take a screenshot of the admin version notice
4. Share all of these with the development team

## Expected Timeline

- **Immediate**: Version should show 1.0.1 after reactivation
- **1-2 minutes**: Debug logs should appear when viewing galleries
- **2-5 minutes**: Images should load from proxy endpoint after clearing caches
- **5-10 minutes**: All caches cleared, everything working normally

If it takes longer than 10 minutes, there may be additional caching layers (CDN, server-level, etc.) that need to be cleared.
