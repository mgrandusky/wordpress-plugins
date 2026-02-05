<?php
/**
 * VelocityWP Admin Header
 *
 * @package VelocityWP
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';

// Tab titles
$tab_titles = array(
    'dashboard' => __('Dashboard', 'velocitywp'),
    'fonts' => __('Font Optimization', 'velocitywp'),
    'object-cache' => __('Object Cache', 'velocitywp'),
    'fragment-cache' => __('Fragment Cache', 'velocitywp'),
    'resource-hints' => __('Resource Hints', 'velocitywp'),
    'cloudflare' => __('Cloudflare Integration', 'velocitywp'),
    'database' => __('Database Optimization', 'velocitywp'),
    'heartbeat' => __('Heartbeat Control', 'velocitywp'),
    'lazy-load' => __('Lazy Loading', 'velocitywp'),
    'performance' => __('Performance Monitoring', 'velocitywp'),
    'performance-metrics' => __('Performance Metrics', 'velocitywp'),
    'woocommerce' => __('WooCommerce Optimization', 'velocitywp'),
    'critical-css' => __('Critical CSS', 'velocitywp'),
    'webp' => __('WebP Image Optimization', 'velocitywp'),
    'help' => __('Help & Documentation', 'velocitywp'),
);

$page_title = isset($tab_titles[$current_tab]) ? $tab_titles[$current_tab] : __('VelocityWP', 'velocitywp');
?>

<div class="velocitywp-page-header">
    <div class="velocitywp-page-header-content">
        <h1 class="velocitywp-page-title">
            <?php echo esc_html($page_title); ?>
        </h1>
        
        <div class="velocitywp-page-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save Changes', 'velocitywp'); ?>
            </button>
        </div>
    </div>
</div>
