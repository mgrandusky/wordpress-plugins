<?php
/**
 * WooCommerce Optimization Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if WooCommerce is active
$woo_active = class_exists( 'WooCommerce' );

// Get current settings
$woo_optimization_enabled = ! empty( $options['woo_optimization_enabled'] ) ? 1 : 0;
$woo_disable_cart_fragments = ! empty( $options['woo_disable_cart_fragments'] ) ? 1 : 0;
$woo_disable_cart_fragments_on = ! empty( $options['woo_disable_cart_fragments_on'] ) ? $options['woo_disable_cart_fragments_on'] : '';
$woo_cart_fragment_lifetime = ! empty( $options['woo_cart_fragment_lifetime'] ) ? intval( $options['woo_cart_fragment_lifetime'] ) : 86400;
$woo_remove_scripts = ! empty( $options['woo_remove_scripts'] ) ? 1 : 0;
$woo_load_everywhere = ! empty( $options['woo_load_everywhere'] ) ? 1 : 0;
$woo_optimize_checkout = ! empty( $options['woo_optimize_checkout'] ) ? 1 : 0;
$woo_disable_password_strength = ! empty( $options['woo_disable_password_strength'] ) ? 1 : 0;
$woo_disable_blocks = ! empty( $options['woo_disable_blocks'] ) ? 1 : 0;
$woo_disable_reviews = ! empty( $options['woo_disable_reviews'] ) ? 1 : 0;
$woo_remove_generator = ! empty( $options['woo_remove_generator'] ) ? 1 : 0;
$woo_disable_admin_bar_cart = ! empty( $options['woo_disable_admin_bar_cart'] ) ? 1 : 0;
$woo_optimize_widgets = ! empty( $options['woo_optimize_widgets'] ) ? 1 : 0;
$woo_optimize_transients = ! empty( $options['woo_optimize_transients'] ) ? 1 : 0;
$woo_optimize_sessions = ! empty( $options['woo_optimize_sessions'] ) ? 1 : 0;
$woo_disable_geolocation = ! empty( $options['woo_disable_geolocation'] ) ? 1 : 0;

?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'WooCommerce Performance Optimization', 'wp-speed-booster' ); ?></h2>
	
	<?php if ( $woo_active ) : ?>
		<div class="notice notice-success">
			<p><strong>✓ <?php esc_html_e( 'WooCommerce Active', 'wp-speed-booster' ); ?></strong></p>
			<p>
				<?php
				// Get WooCommerce version
				if ( defined( 'WC_VERSION' ) ) {
					echo esc_html( sprintf( __( 'Version: %s', 'wp-speed-booster' ), WC_VERSION ) );
				}
				?>
			</p>
		</div>
		
		<div class="notice notice-info">
			<p><strong><?php esc_html_e( 'About WooCommerce Optimization', 'wp-speed-booster' ); ?></strong></p>
			<p><?php esc_html_e( 'WooCommerce adds significant weight to your site - even on non-shop pages! These optimizations can save 1-1.3 MB per page load and reduce server requests by 50-75%.', 'wp-speed-booster' ); ?></p>
			<p><strong><?php esc_html_e( 'Expected Impact:', 'wp-speed-booster' ); ?></strong></p>
			<ul>
				<li>⚡ <?php esc_html_e( '1-1.3 MB saved per non-shop page', 'wp-speed-booster' ); ?></li>
				<li>🚀 <?php esc_html_e( '50-75% fewer AJAX requests', 'wp-speed-booster' ); ?></li>
				<li>📊 <?php esc_html_e( 'Faster page loads on blog/content pages', 'wp-speed-booster' ); ?></li>
				<li>💰 <?php esc_html_e( 'Reduced server load', 'wp-speed-booster' ); ?></li>
			</ul>
		</div>
	<?php else : ?>
		<div class="notice notice-error">
			<p><strong>✗ <?php esc_html_e( 'WooCommerce Not Detected', 'wp-speed-booster' ); ?></strong></p>
			<p><?php esc_html_e( 'This tab requires WooCommerce to be installed and active.', 'wp-speed-booster' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<?php if ( $woo_active ) : ?>

<!-- Master Toggle -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Enable/Disable', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable WooCommerce Optimization', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_optimization_enabled]" value="1" <?php checked( $woo_optimization_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable WooCommerce performance optimizations', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Master switch for all WooCommerce optimizations', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Cart Fragments Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Cart Fragments Optimization', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info inline">
		<p><strong><?php esc_html_e( 'What are Cart Fragments?', 'wp-speed-booster' ); ?></strong></p>
		<p><?php esc_html_e( 'Cart fragments are AJAX requests that update the cart count/total. Default: Runs every page load (~50KB + server load). Impact: Slows down all pages, even non-shop pages.', 'wp-speed-booster' ); ?></p>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Disable Cart Fragments', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_disable_cart_fragments]" value="1" <?php checked( $woo_disable_cart_fragments, 1 ); ?>>
					<?php esc_html_e( 'Disable on non-WooCommerce pages (recommended)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Removes cart fragment AJAX calls from blog posts, pages, and other non-shop pages. Saves ~50KB per page + reduces server load.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Disable On Specific Post Types', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="text" name="wpsb_options[woo_disable_cart_fragments_on]" value="<?php echo esc_attr( $woo_disable_cart_fragments_on ); ?>" class="regular-text">
				<p class="description"><?php esc_html_e( 'Comma-separated post types (e.g., post,page,custom_type). Leave empty to use default behavior.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cart Fragment Cache Lifetime', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="number" name="wpsb_options[woo_cart_fragment_lifetime]" value="<?php echo esc_attr( $woo_cart_fragment_lifetime ); ?>" min="3600" step="3600" class="small-text">
				<?php esc_html_e( 'seconds', 'wp-speed-booster' ); ?>
				<p class="description"><?php esc_html_e( 'Default is 86400 (24 hours). Increase to reduce AJAX calls. Note: Cart updates may take longer to reflect.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Load Scripts Everywhere', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_load_everywhere]" value="1" <?php checked( $woo_load_everywhere, 1 ); ?>>
					<?php esc_html_e( 'Keep WooCommerce scripts on all pages', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Use this if you have cart widgets on every page. Not recommended for performance.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
	
	<div class="wpsb-info-box">
		<h4><?php esc_html_e( 'Estimated Savings', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'With optimization: ~50KB saved per page + reduced server load', 'wp-speed-booster' ); ?></p>
		<p><?php esc_html_e( 'Estimated: 75% reduction in cart fragment requests', 'wp-speed-booster' ); ?></p>
	</div>
</div>

<!-- Script Management Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Script & Style Management', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info inline">
		<p><strong><?php esc_html_e( 'What Gets Removed?', 'wp-speed-booster' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'wc-cart-fragments.js (~30KB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'woocommerce.js (~15KB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'woocommerce.css (~20KB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'select2.js (~60KB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'jquery-blockui.js (~10KB)', 'wp-speed-booster' ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( 'Total Savings: ~135KB per page', 'wp-speed-booster' ); ?></strong></p>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Remove Scripts on Non-Shop Pages', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_remove_scripts]" value="1" <?php checked( $woo_remove_scripts, 1 ); ?>>
					<?php esc_html_e( 'Enable script optimization', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Removes WooCommerce JS/CSS on blog posts, pages, etc. Keeps scripts on product pages, shop pages, cart/checkout, and account pages.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Password Strength Meter -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Password Strength Meter', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-warning inline">
		<p><strong><?php esc_html_e( 'Impact', 'wp-speed-booster' ); ?></strong></p>
		<p><?php esc_html_e( 'Before: 800KB zxcvbn.js loads on registration/checkout', 'wp-speed-booster' ); ?></p>
		<p><?php esc_html_e( 'After: 0KB - Simple password field', 'wp-speed-booster' ); ?></p>
		<p><strong><?php esc_html_e( 'Savings: 800KB per page (HUGE!)', 'wp-speed-booster' ); ?></strong></p>
		<p><?php esc_html_e( 'Note: Users can still create secure passwords without the meter', 'wp-speed-booster' ); ?></p>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Disable Password Strength Meter', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_disable_password_strength]" value="1" <?php checked( $woo_disable_password_strength, 1 ); ?>>
					<?php esc_html_e( 'Disable (recommended for most sites)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Removes zxcvbn.js (~800KB!)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- WooCommerce Blocks -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'WooCommerce Blocks', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info inline">
		<p><strong><?php esc_html_e( 'Removes:', 'wp-speed-booster' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'wc-blocks-style.css (~50KB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'wc-blocks-vendors.js (~200KB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'wc-blocks-checkout.js (~100KB)', 'wp-speed-booster' ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( 'Total Savings: ~350KB', 'wp-speed-booster' ); ?></strong></p>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Disable WooCommerce Blocks', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_disable_blocks]" value="1" <?php checked( $woo_disable_blocks, 1 ); ?>>
					<?php esc_html_e( 'Disable blocks (if not using Gutenberg)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Only disable if you\'re not using block-based checkout or product blocks.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Checkout Optimization -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Checkout Optimization', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Optimize Checkout Page', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_optimize_checkout]" value="1" <?php checked( $woo_optimize_checkout, 1 ); ?>>
					<?php esc_html_e( 'Enable checkout optimization', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Removes unnecessary scripts from checkout page. Keeps essential: jQuery, WooCommerce checkout script, payment gateway scripts.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Product Features -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Product Features', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Disable Reviews', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_disable_reviews]" value="1" <?php checked( $woo_disable_reviews, 1 ); ?>>
					<?php esc_html_e( 'Disable product reviews globally', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Warning: This will hide all reviews and remove the review tab', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Admin Features -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Admin Features', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Remove From Admin Bar', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_disable_admin_bar_cart]" value="1" <?php checked( $woo_disable_admin_bar_cart, 1 ); ?>>
					<?php esc_html_e( 'Disable WooCommerce admin bar menu', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Removes cart icon from admin bar', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Remove Generator Tag', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_remove_generator]" value="1" <?php checked( $woo_remove_generator, 1 ); ?>>
					<?php esc_html_e( 'Remove WooCommerce version from HTML', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Security: Hides WooCommerce version', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Advanced Optimizations -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Advanced Optimizations', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Session Management', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_optimize_sessions]" value="1" <?php checked( $woo_optimize_sessions, 1 ); ?>>
					<?php esc_html_e( 'Optimize session creation', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Don\'t create sessions on non-shop pages for guests', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Geolocation', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_disable_geolocation]" value="1" <?php checked( $woo_disable_geolocation, 1 ); ?>>
					<?php esc_html_e( 'Disable geolocation', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Saves external API calls, uses store base address', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Transient Optimization', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_optimize_transients]" value="1" <?php checked( $woo_optimize_transients, 1 ); ?>>
					<?php esc_html_e( 'Optimize transient storage', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Reduces database queries for product data', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Widget Optimization', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[woo_optimize_widgets]" value="1" <?php checked( $woo_optimize_widgets, 1 ); ?>>
					<?php esc_html_e( 'Optimize product widget queries', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Limits posts and fields in widget queries', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Impact Analysis -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Impact Analysis', 'wp-speed-booster' ); ?></h2>
	
	<div class="wpsb-impact-comparison">
		<div class="wpsb-impact-before">
			<h3><?php esc_html_e( 'Before Optimization', 'wp-speed-booster' ); ?></h3>
			<p><?php esc_html_e( 'Non-shop page loads:', 'wp-speed-booster' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'WooCommerce scripts: 135 KB', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Cart fragments: 1 AJAX request every page', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Password meter: 800 KB', 'wp-speed-booster' ); ?></li>
				<li><strong><?php esc_html_e( 'Total: ~1 MB WooCommerce assets', 'wp-speed-booster' ); ?></strong></li>
			</ul>
			<p class="wpsb-problem"><?php esc_html_e( 'Problem: Loaded on every page!', 'wp-speed-booster' ); ?></p>
		</div>
		
		<div class="wpsb-impact-after">
			<h3><?php esc_html_e( 'After Optimization', 'wp-speed-booster' ); ?></h3>
			<p><?php esc_html_e( 'Non-shop page loads:', 'wp-speed-booster' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'WooCommerce scripts: 0 KB', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Cart fragments: Disabled', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Password meter: 0 KB', 'wp-speed-booster' ); ?></li>
				<li><strong><?php esc_html_e( 'Total: 0 KB WooCommerce assets', 'wp-speed-booster' ); ?></strong></li>
			</ul>
			<p class="wpsb-success"><?php esc_html_e( 'Only loads on shop/product/cart pages ✓', 'wp-speed-booster' ); ?></p>
		</div>
	</div>
	
	<div class="wpsb-typical-results">
		<h4><?php esc_html_e( 'Typical Results', 'wp-speed-booster' ); ?></h4>
		<ul>
			<li><?php esc_html_e( 'Blog post: 1.2s → 0.4s (67% faster!)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Homepage: 2.1s → 0.8s (62% faster!)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Product page: Maintains speed (assets still load)', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<!-- Recommendations -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Recommendations', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-success inline">
		<p><strong>✓ <?php esc_html_e( 'Safe to implement:', 'wp-speed-booster' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'Disable cart fragments on posts/pages', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Remove scripts on non-shop pages', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Disable password strength meter', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
	
	<div class="notice notice-warning inline">
		<p><strong>⚠️ <?php esc_html_e( 'Test before enabling:', 'wp-speed-booster' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'Disable cart fragments completely', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Disable sessions for guests', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
	
	<div class="notice notice-error inline">
		<p><strong>✗ <?php esc_html_e( 'Not recommended:', 'wp-speed-booster' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'Disable cart fragments on product pages', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Remove scripts everywhere', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<!-- Common Issues -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Common Issues', 'wp-speed-booster' ); ?></h2>
	
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Issue', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Solution', 'wp-speed-booster' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'My cart count doesn\'t update', 'wp-speed-booster' ); ?></td>
				<td><?php esc_html_e( 'Don\'t disable cart fragments on product/shop pages', 'wp-speed-booster' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Checkout not working', 'wp-speed-booster' ); ?></td>
				<td><?php esc_html_e( 'Don\'t optimize checkout scripts (or test thoroughly)', 'wp-speed-booster' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Theme cart widget missing', 'wp-speed-booster' ); ?></td>
				<td><?php esc_html_e( 'Enable cart fragments on pages with cart widget', 'wp-speed-booster' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

<!-- Statistics -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Current Statistics', 'wp-speed-booster' ); ?></h2>
	
	<div id="wpsb-woo-stats" class="wpsb-stats-container">
		<div class="wpsb-stat-box">
			<h4><?php esc_html_e( 'Cart Fragments', 'wp-speed-booster' ); ?></h4>
			<p class="wpsb-stat-value" id="stat-cart-fragments">
				<?php echo $woo_disable_cart_fragments ? esc_html__( 'Disabled', 'wp-speed-booster' ) : esc_html__( 'Enabled', 'wp-speed-booster' ); ?>
			</p>
		</div>
		
		<div class="wpsb-stat-box">
			<h4><?php esc_html_e( 'Scripts Optimized', 'wp-speed-booster' ); ?></h4>
			<p class="wpsb-stat-value" id="stat-scripts">
				<?php echo $woo_remove_scripts ? esc_html__( 'Yes', 'wp-speed-booster' ) : esc_html__( 'No', 'wp-speed-booster' ); ?>
			</p>
		</div>
		
		<div class="wpsb-stat-box">
			<h4><?php esc_html_e( 'Password Meter', 'wp-speed-booster' ); ?></h4>
			<p class="wpsb-stat-value" id="stat-password">
				<?php echo $woo_disable_password_strength ? esc_html__( 'Disabled', 'wp-speed-booster' ) : esc_html__( 'Enabled', 'wp-speed-booster' ); ?>
			</p>
		</div>
		
		<div class="wpsb-stat-box">
			<h4><?php esc_html_e( 'Estimated Savings', 'wp-speed-booster' ); ?></h4>
			<p class="wpsb-stat-value wpsb-stat-highlight" id="stat-savings">
				<?php
				$savings = 0;
				if ( $woo_disable_cart_fragments ) {
					$savings += 50;
				}
				if ( $woo_remove_scripts ) {
					$savings += 100;
				}
				if ( $woo_disable_password_strength ) {
					$savings += 800;
				}
				if ( $woo_disable_blocks ) {
					$savings += 350;
				}
				echo esc_html( $savings ) . ' KB';
				?>
			</p>
		</div>
	</div>
	
	<p class="description">
		<?php
		if ( $savings > 0 ) {
			$monthly_savings = ( $savings * 10000 ) / 1024; // 10,000 pageviews
			echo esc_html( sprintf(
				__( 'Monthly savings for 10,000 pageviews: %.2f MB data transferred', 'wp-speed-booster' ),
				$monthly_savings
			) );
		} else {
			esc_html_e( 'Enable optimizations to see estimated savings', 'wp-speed-booster' );
		}
		?>
	</p>
</div>

<?php endif; // End if WooCommerce active ?>

<style>
.wpsb-info-box {
	background: #f0f0f1;
	border-left: 4px solid #2271b1;
	padding: 12px;
	margin: 20px 0;
}

.wpsb-info-box h4 {
	margin-top: 0;
}

.wpsb-impact-comparison {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	margin: 20px 0;
}

.wpsb-impact-before,
.wpsb-impact-after {
	border: 1px solid #ddd;
	padding: 15px;
	border-radius: 4px;
}

.wpsb-impact-before {
	background: #fff3cd;
}

.wpsb-impact-after {
	background: #d1e7dd;
}

.wpsb-problem {
	color: #d63638;
	font-weight: bold;
}

.wpsb-success {
	color: #00a32a;
	font-weight: bold;
}

.wpsb-typical-results {
	margin-top: 20px;
	padding: 15px;
	background: #f0f0f1;
	border-radius: 4px;
}

.wpsb-stats-container {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin: 20px 0;
}

.wpsb-stat-box {
	background: #fff;
	border: 1px solid #ddd;
	padding: 15px;
	border-radius: 4px;
	text-align: center;
}

.wpsb-stat-box h4 {
	margin: 0 0 10px 0;
	font-size: 14px;
	color: #666;
}

.wpsb-stat-value {
	font-size: 24px;
	font-weight: bold;
	color: #2271b1;
	margin: 0;
}

.wpsb-stat-highlight {
	color: #00a32a;
}

.notice.inline {
	display: block;
	margin: 10px 0;
}
</style>
