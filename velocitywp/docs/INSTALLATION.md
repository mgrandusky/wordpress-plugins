# VelocityWP Installation Guide

This guide provides detailed instructions for installing VelocityWP on your WordPress site using various methods.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation Methods](#installation-methods)
   - [Via WordPress Admin (Recommended)](#via-wordpress-admin-recommended)
   - [Manual Installation via FTP](#manual-installation-via-ftp)
   - [Via WP-CLI](#via-wp-cli)
   - [Via Composer](#via-composer)
3. [Post-Installation Setup](#post-installation-setup)
4. [Server Requirements Check](#server-requirements-check)
5. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before installing VelocityWP, ensure your server meets these requirements:

### Minimum Requirements
- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher (or MariaDB 10.0+)
- **Memory:** 64MB minimum (128MB+ recommended)
- **Disk Space:** 5MB for plugin files

### Recommended for Optimal Performance
- **PHP:** 8.0 or higher
- **Memory:** 256MB+
- **Redis** or **Memcached** installed
- **Imagick** or **GD Library** for image optimization
- **mod_rewrite** enabled (for .htaccess rules)
- **OpenSSL** for HTTPS connections
- **cURL** for external API calls

---

## Installation Methods

### Via WordPress Admin (Recommended)

This is the easiest method for most users.

#### Step 1: Download the Plugin

1. Download the latest release from [GitHub Releases](https://github.com/mgrandusky/wordpress-plugins/releases)
2. Save the `velocitywp.zip` file to your computer

#### Step 2: Upload via WordPress

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Click the **Upload Plugin** button at the top
4. Click **Choose File** and select the `velocitywp.zip` file
5. Click **Install Now**
6. Wait for the upload and installation to complete

#### Step 3: Activate

1. Once installed, click **Activate Plugin**
2. You'll be redirected to the VelocityWP welcome screen

**Time Required:** 2-3 minutes

---

### Manual Installation via FTP

Use this method if you have FTP access and prefer manual control.

#### Step 1: Download and Extract

1. Download `velocitywp.zip` from [GitHub Releases](https://github.com/mgrandusky/wordpress-plugins/releases)
2. Extract the ZIP file on your computer
3. You should see a folder named `velocitywp`

#### Step 2: Upload via FTP

1. Connect to your server via FTP (using FileZilla, Cyberduck, or similar)
2. Navigate to `/wp-content/plugins/`
3. Upload the entire `velocitywp` folder
4. Ensure all files are uploaded successfully

#### Step 3: Set Permissions (if needed)

```bash
chmod 755 /wp-content/plugins/velocitywp
chmod 644 /wp-content/plugins/velocitywp/*
```

#### Step 4: Activate

1. Go to your WordPress admin dashboard
2. Navigate to **Plugins → Installed Plugins**
3. Find VelocityWP in the list
4. Click **Activate**

**Time Required:** 5-10 minutes

---

### Via WP-CLI

For developers and system administrators who prefer command-line installation.

#### Prerequisites
- WP-CLI installed on your server
- SSH access

#### Installation Steps

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Download and install the plugin
wp plugin install https://github.com/mgrandusky/wordpress-plugins/releases/download/v1.0.0/velocitywp.zip

# Activate the plugin
wp plugin activate velocitywp

# Verify installation
wp plugin list --status=active | grep velocitywp
```

#### Advanced Options

```bash
# Install and activate in one command
wp plugin install velocitywp --activate

# Install for multisite network
wp plugin install velocitywp --network

# Install specific version
wp plugin install https://github.com/mgrandusky/wordpress-plugins/releases/download/v1.0.0/velocitywp.zip --activate
```

**Time Required:** 1-2 minutes

---

### Via Composer

For projects using Composer for dependency management.

#### Add to composer.json

```json
{
  "require": {
    "mgrandusky/velocitywp": "^1.0"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/mgrandusky/wordpress-plugins"
    }
  ]
}
```

#### Install

```bash
composer install
```

#### Activate

```bash
wp plugin activate velocitywp
```

---

## Post-Installation Setup

After installation, follow these steps to configure VelocityWP:

### 1. Access Plugin Settings

1. In WordPress admin, go to **Settings → VelocityWP**
2. You'll see the VelocityWP dashboard

### 2. Run System Check

1. Click the **System Info** tab
2. Review the system requirements
3. Install any missing PHP extensions if needed:
   - Redis/Memcached for object caching
   - Imagick for image optimization

### 3. Quick Setup (Recommended)

For first-time users, we recommend starting with these safe defaults:

#### Enable Basic Optimizations

1. **Lazy Loading**
   - Enable lazy loading for images
   - Enable lazy loading for iframes
   - Skip first 2 images (preserves LCP)

2. **Database Optimization**
   - Enable automatic weekly cleanup
   - Clean post revisions (keep last 3)
   - Remove auto-drafts older than 7 days

3. **Heartbeat Control**
   - Disable on frontend
   - Set to 60 seconds in admin
   - Set to 15 seconds in editor

4. **Resource Hints**
   - Enable DNS prefetch
   - Enable preconnect

#### Enable Advanced Features (if available)

5. **Object Caching** (requires Redis/Memcached)
   - Configure connection settings
   - Test connection
   - Enable caching

6. **Image Optimization** (requires Imagick/GD)
   - Enable optimization on upload
   - Set quality to 85
   - Enable WebP conversion

### 4. Test Your Site

After enabling features:

1. Clear all caches
2. Visit your site in an incognito/private window
3. Test all functionality:
   - Forms still work
   - Images load correctly
   - Navigation functions
   - Shopping cart (if WooCommerce)

### 5. Monitor Performance

1. Go to **VelocityWP → Performance Monitoring**
2. Let it collect data for 24 hours
3. Review Core Web Vitals improvements

---

## Server Requirements Check

### Checking PHP Version

```bash
php -v
```

Expected output: PHP 7.4 or higher

### Checking Available Extensions

```bash
php -m | grep -E "(redis|memcached|imagick|gd)"
```

### Checking Memory Limit

```bash
php -i | grep memory_limit
```

Recommended: 256M or higher

### Checking Apache Modules (if using Apache)

```bash
apache2 -M | grep rewrite
```

Should show: `rewrite_module`

### Checking Write Permissions

The plugin needs write access to:

```bash
# Cache directory
wp-content/cache/velocitywp/

# Uploads directory (for image optimization)
wp-content/uploads/

# .htaccess file (optional, for browser caching)
.htaccess
```

Test write permissions:

```bash
# Check cache directory
mkdir -p wp-content/cache/velocitywp
touch wp-content/cache/velocitywp/test.txt
rm wp-content/cache/velocitywp/test.txt
```

---

## Troubleshooting

### Installation Issues

#### "The plugin does not have a valid header"

**Cause:** Corrupted or incomplete download

**Solution:**
1. Delete the plugin folder
2. Re-download the ZIP file
3. Reinstall

#### "Destination folder already exists"

**Cause:** Previous installation not completely removed

**Solution:**
```bash
# Via FTP or SSH, remove the old folder
rm -rf wp-content/plugins/velocitywp

# Then reinstall
```

#### "Failed to create directory"

**Cause:** Insufficient permissions

**Solution:**
```bash
# Set correct permissions
chmod 755 wp-content/plugins
chown -R www-data:www-data wp-content/plugins
```

### Activation Issues

#### "The plugin requires PHP 7.4 or higher"

**Cause:** Server running older PHP version

**Solution:**
1. Contact your hosting provider to upgrade PHP
2. Or use a compatible server

#### "Fatal error: Cannot redeclare class"

**Cause:** Conflicting plugin

**Solution:**
1. Deactivate other performance plugins
2. Re-activate VelocityWP

### Configuration Issues

#### Cannot Connect to Redis/Memcached

**Solution:**
```bash
# Check if Redis is running
redis-cli ping

# Should return: PONG

# Check if Memcached is running
echo "stats" | nc localhost 11211
```

#### Image Optimization Not Working

**Solution:**
```bash
# Install Imagick
sudo apt-get install php-imagick
sudo systemctl restart apache2

# Or check if GD is available
php -m | grep gd
```

### Getting Help

If you encounter issues not covered here:

1. Check the [FAQ](FAQ.md)
2. Search [GitHub Issues](https://github.com/mgrandusky/wordpress-plugins/issues)
3. Ask in [Community Forum](https://github.com/mgrandusky/wordpress-plugins/discussions)
4. Contact support@velocitywp.com

---

## Next Steps

After successful installation:

1. Read the [Configuration Guide](CONFIGURATION.md)
2. Review [Complete Feature Documentation](FEATURES.md)
3. Check out [FAQ](FAQ.md) for common questions

---

**Installation complete! Ready to make your WordPress fly!** 🚀
