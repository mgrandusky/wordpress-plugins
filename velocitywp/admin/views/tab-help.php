<?php
/**
 * Help & Documentation Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<style>
.velocitywp-help-container {
	max-width: 1200px;
	margin: 0 auto;
}

.velocitywp-help-hero {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 40px;
	border-radius: 8px;
	margin-bottom: 30px;
	text-align: center;
}

.velocitywp-help-hero h1 {
	font-size: 32px;
	margin: 0 0 10px 0;
	color: white;
}

.velocitywp-help-hero p {
	font-size: 18px;
	margin: 0;
	opacity: 0.95;
}

.velocitywp-help-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 30px;
	margin-bottom: 20px;
}

.velocitywp-help-section h2 {
	margin-top: 0;
	font-size: 24px;
	color: #333;
	border-bottom: 2px solid #667eea;
	padding-bottom: 10px;
	margin-bottom: 20px;
}

.velocitywp-quick-start {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-bottom: 20px;
}

.velocitywp-quick-start-step {
	background: #f8f9fa;
	padding: 20px;
	border-radius: 6px;
	border-left: 4px solid #667eea;
	transition: transform 0.2s;
}

.velocitywp-quick-start-step:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.velocitywp-quick-start-step .step-number {
	display: inline-block;
	width: 32px;
	height: 32px;
	background: #667eea;
	color: white;
	border-radius: 50%;
	text-align: center;
	line-height: 32px;
	font-weight: bold;
	margin-bottom: 10px;
}

.velocitywp-quick-start-step h3 {
	margin: 10px 0;
	font-size: 16px;
	color: #333;
}

.velocitywp-quick-start-step p {
	margin: 5px 0 0 0;
	font-size: 14px;
	color: #666;
}

.velocitywp-accordion {
	border: 1px solid #e0e0e0;
	border-radius: 6px;
	overflow: hidden;
}

.velocitywp-accordion-item {
	border-bottom: 1px solid #e0e0e0;
}

.velocitywp-accordion-item:last-child {
	border-bottom: none;
}

.velocitywp-accordion-header {
	background: #f8f9fa;
	padding: 15px 20px;
	cursor: pointer;
	display: flex;
	justify-content: space-between;
	align-items: center;
	transition: background 0.2s;
}

.velocitywp-accordion-header:hover {
	background: #e9ecef;
}

.velocitywp-accordion-header h3 {
	margin: 0;
	font-size: 16px;
	color: #333;
}

.velocitywp-accordion-icon {
	font-size: 20px;
	transition: transform 0.3s;
}

.velocitywp-accordion-item.active .velocitywp-accordion-icon {
	transform: rotate(180deg);
}

.velocitywp-accordion-content {
	display: none;
	padding: 20px;
	background: #fff;
}

.velocitywp-accordion-item.active .velocitywp-accordion-content {
	display: block;
}

.velocitywp-accordion-content ul {
	margin: 10px 0;
	padding-left: 20px;
}

.velocitywp-accordion-content li {
	margin: 8px 0;
	line-height: 1.6;
}

.velocitywp-accordion-content code {
	background: #f4f4f4;
	padding: 2px 6px;
	border-radius: 3px;
	font-family: monospace;
	font-size: 13px;
}

.velocitywp-faq-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
}

.velocitywp-faq-item {
	background: #f8f9fa;
	padding: 20px;
	border-radius: 6px;
	border-left: 3px solid #667eea;
}

.velocitywp-faq-item h4 {
	margin: 0 0 10px 0;
	color: #333;
	font-size: 16px;
}

.velocitywp-faq-item p {
	margin: 0;
	color: #666;
	font-size: 14px;
	line-height: 1.6;
}

.velocitywp-troubleshooting-list {
	list-style: none;
	padding: 0;
}

.velocitywp-troubleshooting-list li {
	background: #fff3cd;
	padding: 15px;
	margin-bottom: 10px;
	border-radius: 6px;
	border-left: 4px solid #ffc107;
}

.velocitywp-troubleshooting-list li strong {
	display: block;
	color: #333;
	margin-bottom: 5px;
}

.velocitywp-support-links {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.velocitywp-support-link {
	display: block;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white !important;
	padding: 20px;
	border-radius: 8px;
	text-align: center;
	text-decoration: none !important;
	transition: transform 0.2s, box-shadow 0.2s;
}

.velocitywp-support-link:hover {
	transform: translateY(-3px);
	box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}

.velocitywp-support-link .icon {
	font-size: 32px;
	margin-bottom: 10px;
}

.velocitywp-support-link .title {
	display: block;
	font-size: 18px;
	font-weight: bold;
	margin-bottom: 5px;
}

.velocitywp-support-link .description {
	display: block;
	font-size: 14px;
	opacity: 0.9;
}

.velocitywp-system-info {
	background: #f8f9fa;
	padding: 20px;
	border-radius: 6px;
	font-family: monospace;
	font-size: 13px;
}

.velocitywp-system-info-item {
	display: flex;
	justify-content: space-between;
	padding: 8px 0;
	border-bottom: 1px solid #e0e0e0;
}

.velocitywp-system-info-item:last-child {
	border-bottom: none;
}

.velocitywp-system-info-item .label {
	font-weight: bold;
	color: #333;
}

.velocitywp-system-info-item .value {
	color: #666;
}
</style>

<div class="velocitywp-help-container">
	<!-- Hero Section -->
	<div class="velocitywp-help-hero">
		<h1>❓ Help & Documentation</h1>
		<p>Everything you need to optimize your WordPress site with VelocityWP</p>
	</div>

	<!-- Quick Start Guide -->
	<div class="velocitywp-help-section">
		<h2>🚀 Quick Start Guide</h2>
		<p>Follow these 5 simple steps to get started with VelocityWP:</p>
		<div class="velocitywp-quick-start">
			<div class="velocitywp-quick-start-step">
				<span class="step-number">1</span>
				<h3>Enable Font Optimization</h3>
				<p>Optimize web fonts for faster loading and better Core Web Vitals</p>
			</div>
			<div class="velocitywp-quick-start-step">
				<span class="step-number">2</span>
				<h3>Enable Lazy Loading</h3>
				<p>Defer offscreen images and iframes to improve initial page load</p>
			</div>
			<div class="velocitywp-quick-start-step">
				<span class="step-number">3</span>
				<h3>Enable Object Cache</h3>
				<p>Use Redis or Memcached for faster database queries</p>
			</div>
			<div class="velocitywp-quick-start-step">
				<span class="step-number">4</span>
				<h3>Optimize Database</h3>
				<p>Clean up unnecessary data and optimize database tables</p>
			</div>
			<div class="velocitywp-quick-start-step">
				<span class="step-number">5</span>
				<h3>Monitor Performance</h3>
				<p>Track Core Web Vitals and monitor improvements</p>
			</div>
		</div>
	</div>

	<!-- Feature Documentation -->
	<div class="velocitywp-help-section">
		<h2>📚 Feature Documentation</h2>
		<div class="velocitywp-accordion">
			<!-- Font Optimization -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>⚡ Font Optimization</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Optimizes web fonts to reduce render-blocking and improve page load times.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Self-host Google Fonts locally</li>
						<li>Font preloading for critical fonts</li>
						<li>Font-display: swap for better performance</li>
						<li>DNS prefetch and preconnect for font domains</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Enable font optimization</li>
						<li>Use font-display: swap</li>
						<li>Enable preconnect for Google Fonts</li>
					</ul>
				</div>
			</div>

			<!-- Lazy Loading -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>🖼️ Lazy Loading</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Defers loading of offscreen images, iframes, and videos until they're needed.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Native browser lazy loading support</li>
						<li>JavaScript fallback for older browsers</li>
						<li>Lazy load images, iframes, and videos</li>
						<li>YouTube and Vimeo lazy loading</li>
						<li>Background image lazy loading</li>
						<li>Fade-in animation support</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Enable lazy loading for images</li>
						<li>Enable lazy loading for iframes</li>
						<li>Use native lazy loading when available</li>
						<li>Exclude above-the-fold images</li>
					</ul>
				</div>
			</div>

			<!-- Object Cache -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>💾 Object Cache</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Stores database query results in memory for faster retrieval.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Redis support (recommended)</li>
						<li>Memcached support</li>
						<li>Automatic cache key prefix</li>
						<li>Cache statistics and monitoring</li>
					</ul>
					<p><strong>Requirements:</strong></p>
					<ul>
						<li>Redis or Memcached server installed</li>
						<li>PHP Redis or Memcached extension</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Use Redis for best performance</li>
						<li>Set appropriate cache expiration times</li>
						<li>Monitor hit/miss ratio</li>
					</ul>
				</div>
			</div>

			<!-- Database Optimization -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>🗄️ Database Optimization</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Cleans up unnecessary data and optimizes database tables.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Remove post revisions</li>
						<li>Delete auto-drafts</li>
						<li>Empty trash</li>
						<li>Remove spam comments</li>
						<li>Clean transients</li>
						<li>Optimize database tables</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Enable automatic optimization</li>
						<li>Schedule weekly optimization</li>
						<li>Keep some recent revisions</li>
						<li>Enable email reports</li>
					</ul>
				</div>
			</div>

			<!-- Critical CSS -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>🎨 Critical CSS</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Inlines critical CSS and defers non-critical stylesheets.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Automatic critical CSS generation</li>
						<li>Separate mobile and desktop critical CSS</li>
						<li>Defer non-critical stylesheets</li>
						<li>Remove unused CSS</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Enable critical CSS</li>
						<li>Generate separate mobile/desktop CSS</li>
						<li>Test thoroughly before enabling</li>
						<li>Regenerate after theme changes</li>
					</ul>
				</div>
			</div>

			<!-- Fragment Cache -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>📦 Fragment Cache</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Caches expensive page fragments like widgets and sidebars.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Cache widgets</li>
						<li>Cache sidebars</li>
						<li>Cache navigation menus</li>
						<li>Cache shortcode output</li>
						<li>Configurable expiration times</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Enable fragment caching</li>
						<li>Cache static widgets</li>
						<li>Set appropriate expiration times</li>
						<li>Disable for logged-in users if needed</li>
					</ul>
				</div>
			</div>

			<!-- CDN Integration -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>☁️ Cloudflare Integration</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Integrates with Cloudflare CDN for global content delivery.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Automatic cache purging on content updates</li>
						<li>Restore visitor IP addresses</li>
						<li>Cloudflare APO support</li>
						<li>Cache purge on comment submission</li>
					</ul>
					<p><strong>Requirements:</strong></p>
					<ul>
						<li>Cloudflare account</li>
						<li>API key and Zone ID</li>
						<li>Site proxied through Cloudflare</li>
					</ul>
				</div>
			</div>

			<!-- Performance Monitor -->
			<div class="velocitywp-accordion-item">
				<div class="velocitywp-accordion-header">
					<h3>📊 Performance Monitor</h3>
					<span class="velocitywp-accordion-icon">▼</span>
				</div>
				<div class="velocitywp-accordion-content">
					<p><strong>What it does:</strong> Tracks Core Web Vitals and performance metrics.</p>
					<p><strong>Features:</strong></p>
					<ul>
						<li>Real User Monitoring (RUM)</li>
						<li>Core Web Vitals tracking (LCP, FID, CLS)</li>
						<li>Server-side performance tracking</li>
						<li>Performance history and trends</li>
						<li>Debug comments in HTML</li>
					</ul>
					<p><strong>Recommended Settings:</strong></p>
					<ul>
						<li>Enable performance monitoring</li>
						<li>Enable RUM for real-world data</li>
						<li>Monitor trends over time</li>
						<li>Focus on Core Web Vitals improvements</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<!-- FAQ Section -->
	<div class="velocitywp-help-section">
		<h2>❓ Frequently Asked Questions</h2>
		<div class="velocitywp-faq-grid">
			<div class="velocitywp-faq-item">
				<h4>Which features should I enable first?</h4>
				<p>Start with Font Optimization, Lazy Loading, and Database Optimization. These provide immediate benefits with minimal risk. Then gradually enable other features while testing.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>Is VelocityWP compatible with my theme?</h4>
				<p>VelocityWP is designed to be compatible with all WordPress themes. However, always test features in a staging environment first, especially Critical CSS and minification.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>How do I clear the cache?</h4>
				<p>Each caching feature has its own clear cache button. Look for "Clear Cache" or "Purge Cache" buttons in the respective feature tabs.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>What if my site breaks after enabling a feature?</h4>
				<p>Immediately disable the feature that caused the issue. Most features can be disabled without side effects. Check the Troubleshooting section below for specific solutions.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>Does VelocityWP work with WooCommerce?</h4>
				<p>Yes! VelocityWP includes a dedicated WooCommerce tab with optimizations specifically for WooCommerce, including cart fragment caching and script optimization.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>How often should I optimize the database?</h4>
				<p>Weekly optimization is recommended for most sites. High-traffic sites may benefit from more frequent optimization. Enable automatic scheduled optimization for best results.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>What are Core Web Vitals?</h4>
				<p>Core Web Vitals are Google's metrics for page experience: LCP (loading speed), FID (interactivity), and CLS (visual stability). VelocityWP helps improve all three.</p>
			</div>
			<div class="velocitywp-faq-item">
				<h4>Can I use VelocityWP with other optimization plugins?</h4>
				<p>While possible, it's not recommended. Multiple optimization plugins can conflict. VelocityWP provides comprehensive optimization, so other plugins are usually unnecessary.</p>
			</div>
		</div>
	</div>

	<!-- Troubleshooting -->
	<div class="velocitywp-help-section">
		<h2>🔧 Troubleshooting Guide</h2>
		<ul class="velocitywp-troubleshooting-list">
			<li>
				<strong>⚠️ Site broke after enabling a feature</strong>
				Disable the feature immediately. Clear all caches (browser, server, CDN). Check browser console for JavaScript errors. If using Critical CSS, try regenerating it or disabling temporarily.
			</li>
			<li>
				<strong>⚠️ Cache not clearing</strong>
				Try clearing cache from multiple locations: VelocityWP tabs, browser cache, and CDN cache. If using Cloudflare, manually purge from Cloudflare dashboard. Check file permissions on cache directory.
			</li>
			<li>
				<strong>⚠️ Performance didn't improve</strong>
				Test with multiple tools (Google PageSpeed Insights, GTmetrix, WebPageTest). Some optimizations need time to show results. Check that features are actually enabled and working. Verify server resources are adequate.
			</li>
			<li>
				<strong>⚠️ Images not lazy loading</strong>
				Check if native lazy loading is enabled. Verify images don't have <code>loading="eager"</code> attribute. Ensure JavaScript isn't being deferred/delayed in a way that breaks lazy loading. Check browser console for errors.
			</li>
			<li>
				<strong>⚠️ Layout shifts (CLS issues)</strong>
				Add explicit width and height attributes to images. Reserve space for ads and embeds. Use font-display: swap carefully. Avoid inserting content above existing content after page load.
			</li>
			<li>
				<strong>⚠️ Object cache not working</strong>
				Verify Redis/Memcached is installed and running. Check PHP extension is installed (<code>php -m | grep redis</code>). Verify connection credentials. Check error logs for connection issues.
			</li>
			<li>
				<strong>⚠️ Critical CSS causing style issues</strong>
				Regenerate Critical CSS after any theme changes. Check viewport settings for mobile/desktop. Increase critical CSS selector specificity if needed. Consider disabling for specific pages.
			</li>
		</ul>
	</div>

	<!-- Support Links -->
	<div class="velocitywp-help-section">
		<h2>💬 Get Support</h2>
		<div class="velocitywp-support-links">
			<a href="https://github.com/mgrandusky/wordpress-plugins/issues" target="_blank" class="velocitywp-support-link">
				<span class="icon">🐛</span>
				<span class="title">Report a Bug</span>
				<span class="description">Found a bug? Let us know on GitHub</span>
			</a>
			<a href="https://github.com/mgrandusky/wordpress-plugins/issues/new" target="_blank" class="velocitywp-support-link">
				<span class="icon">💡</span>
				<span class="title">Request a Feature</span>
				<span class="description">Suggest new features or improvements</span>
			</a>
			<a href="https://github.com/mgrandusky/wordpress-plugins" target="_blank" class="velocitywp-support-link">
				<span class="icon">📖</span>
				<span class="title">View on GitHub</span>
				<span class="description">Star the project, contribute code</span>
			</a>
		</div>
	</div>

	<!-- System Information -->
	<div class="velocitywp-help-section">
		<h2>ℹ️ System Information</h2>
		<div class="velocitywp-system-info">
			<div class="velocitywp-system-info-item">
				<span class="label">VelocityWP Version:</span>
				<span class="value"><?php echo esc_html( VELOCITYWP_VERSION ); ?></span>
			</div>
			<div class="velocitywp-system-info-item">
				<span class="label">WordPress Version:</span>
				<span class="value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
			</div>
			<div class="velocitywp-system-info-item">
				<span class="label">PHP Version:</span>
				<span class="value"><?php echo esc_html( PHP_VERSION ); ?></span>
			</div>
			<div class="velocitywp-system-info-item">
				<span class="label">Active Theme:</span>
				<span class="value"><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></span>
			</div>
			<div class="velocitywp-system-info-item">
				<span class="label">Redis Available:</span>
				<span class="value"><?php echo class_exists( 'Redis' ) ? '✓ Yes' : '✗ No'; ?></span>
			</div>
			<div class="velocitywp-system-info-item">
				<span class="label">Memcached Available:</span>
				<span class="value"><?php echo class_exists( 'Memcached' ) ? '✓ Yes' : '✗ No'; ?></span>
			</div>
			<div class="velocitywp-system-info-item">
				<span class="label">WooCommerce Active:</span>
				<span class="value"><?php echo class_exists( 'WooCommerce' ) ? '✓ Yes' : '✗ No'; ?></span>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Accordion functionality
	$('.velocitywp-accordion-header').on('click', function() {
		var $item = $(this).closest('.velocitywp-accordion-item');
		var isActive = $item.hasClass('active');
		
		// Close all accordion items
		$('.velocitywp-accordion-item').removeClass('active');
		
		// Open clicked item if it wasn't active
		if (!isActive) {
			$item.addClass('active');
		}
	});
});
</script>
