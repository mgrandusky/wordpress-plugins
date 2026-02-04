<?php
/**
 * VelocityWP Admin Navigation Sidebar
 *
 * @package VelocityWP
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'fonts';

// Navigation items
$nav_items = array(
    'optimization' => array(
        'title' => __('Optimization', 'velocitywp'),
        'items' => array(
            'fonts' => array(
                'icon' => '⚡',
                'label' => __('Font Optimization', 'velocitywp'),
                'description' => __('Self-host fonts, preload', 'velocitywp')
            ),
            'object-cache' => array(
                'icon' => '💾',
                'label' => __('Object Cache', 'velocitywp'),
                'description' => __('Redis, Memcached', 'velocitywp')
            ),
            'fragment-cache' => array(
                'icon' => '📦',
                'label' => __('Fragment Cache', 'velocitywp'),
                'description' => __('Widgets, sidebars, menus', 'velocitywp')
            ),
            'resource-hints' => array(
                'icon' => '🌐',
                'label' => __('Resource Hints', 'velocitywp'),
                'description' => __('Preload, prefetch, preconnect', 'velocitywp')
            ),
            'lazy-load' => array(
                'icon' => '🖼️',
                'label' => __('Lazy Loading', 'velocitywp'),
                'description' => __('Images, iframes, videos', 'velocitywp')
            ),
            'critical-css' => array(
                'icon' => '🎨',
                'label' => __('Critical CSS', 'velocitywp'),
                'description' => __('Eliminate render-blocking', 'velocitywp')
            ),
            'webp' => array(
                'icon' => '📸',
                'label' => __('WebP Images', 'velocitywp'),
                'description' => __('WebP, compression', 'velocitywp')
            ),
        )
    ),
    'infrastructure' => array(
        'title' => __('Infrastructure', 'velocitywp'),
        'items' => array(
            'cloudflare' => array(
                'icon' => '☁️',
                'label' => __('Cloudflare', 'velocitywp'),
                'description' => __('CDN & APO integration', 'velocitywp')
            ),
            'database' => array(
                'icon' => '🗄️',
                'label' => __('Database', 'velocitywp'),
                'description' => __('Cleanup & optimization', 'velocitywp')
            ),
            'heartbeat' => array(
                'icon' => '💓',
                'label' => __('Heartbeat Control', 'velocitywp'),
                'description' => __('Reduce AJAX requests', 'velocitywp')
            ),
        )
    ),
    'platform' => array(
        'title' => __('Platform Specific', 'velocitywp'),
        'items' => array(
            'woocommerce' => array(
                'icon' => '🛒',
                'label' => __('WooCommerce', 'velocitywp'),
                'description' => __('Cart fragments, scripts', 'velocitywp')
            ),
        )
    ),
    'monitoring' => array(
        'title' => __('Monitoring', 'velocitywp'),
        'items' => array(
            'performance' => array(
                'icon' => '📊',
                'label' => __('Performance Monitor', 'velocitywp'),
                'description' => __('Core Web Vitals, RUM', 'velocitywp')
            ),
            'performance-metrics' => array(
                'icon' => '📈',
                'label' => __('Performance Metrics', 'velocitywp'),
                'description' => __('PageSpeed Insights', 'velocitywp')
            ),
        )
    )
);
?>

<div class="velocitywp-nav-sidebar">
    <!-- Logo/Header -->
    <div class="velocitywp-nav-header">
        <div class="velocitywp-logo">
            <span class="velocitywp-logo-icon">⚡</span>
            <span class="velocitywp-logo-text">VelocityWP</span>
        </div>
        <div class="velocitywp-version">v<?php echo esc_html(VELOCITYWP_VERSION); ?></div>
    </div>

    <!-- Navigation Menu -->
    <nav class="velocitywp-nav-menu">
        <?php foreach ($nav_items as $section_key => $section): ?>
            <div class="velocitywp-nav-section">
                <div class="velocitywp-nav-section-title">
                    <?php echo esc_html($section['title']); ?>
                </div>
                
                <ul class="velocitywp-nav-items">
                    <?php foreach ($section['items'] as $item_key => $item): ?>
                        <?php
                        $is_active = ($current_tab === $item_key);
                        $class = $is_active ? 'velocitywp-nav-item active' : 'velocitywp-nav-item';
                        $url = admin_url('admin.php?page=velocitywp&tab=' . $item_key);
                        ?>
                        <li>
                            <a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr($class); ?>">
                                <span class="velocitywp-nav-icon"><?php echo esc_html($item['icon']); ?></span>
                                <span class="velocitywp-nav-label">
                                    <span class="velocitywp-nav-title"><?php echo esc_html($item['label']); ?></span>
                                    <span class="velocitywp-nav-desc"><?php echo esc_html($item['description']); ?></span>
                                </span>
                                <?php if ($is_active): ?>
                                    <span class="velocitywp-nav-indicator"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="velocitywp-nav-footer">
        <a href="https://velocitywp.com/docs" target="_blank" class="velocitywp-nav-footer-link">
            📚 <?php _e('Documentation', 'velocitywp'); ?>
        </a>
        <a href="https://velocitywp.com/support" target="_blank" class="velocitywp-nav-footer-link">
            💬 <?php _e('Support', 'velocitywp'); ?>
        </a>
    </div>
</div>
