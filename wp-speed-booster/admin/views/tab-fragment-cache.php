<?php
/**
 * Fragment Cache Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize Fragment Cache class to get stats
$fragment_cache = new WPSB_Fragment_Cache();
$stats = $fragment_cache->get_stats();

// Get settings
$fragment_enabled = ! empty( $options['fragment_cache_enabled'] ) ? 1 : 0;
$cache_widgets = ! empty( $options['cache_widgets'] ) ? 1 : 0;
$cache_sidebars = ! empty( $options['cache_sidebars'] ) ? 1 : 0;
$cache_menus = ! empty( $options['cache_menus'] ) ? 1 : 0;
$cache_shortcodes = ! empty( $options['cache_shortcodes'] ) ? 1 : 0;
$fragment_cache_time = ! empty( $options['fragment_cache_time'] ) ? $options['fragment_cache_time'] : 3600;
$fragment_cache_logged_in = ! empty( $options['fragment_cache_logged_in'] ) ? 1 : 0;

// Get cached lists
$cached_widget_list = ! empty( $options['cached_widget_list'] ) ? $options['cached_widget_list'] : array();
$cached_sidebar_list = ! empty( $options['cached_sidebar_list'] ) ? $options['cached_sidebar_list'] : array();
$cached_menu_list = ! empty( $options['cached_menu_list'] ) ? $options['cached_menu_list'] : array();
$cached_shortcode_list = ! empty( $options['cached_shortcode_list'] ) ? $options['cached_shortcode_list'] : '';

// Get registered widgets
global $wp_widget_factory;
$registered_widgets = array();
if ( isset( $wp_widget_factory->widgets ) ) {
	foreach ( $wp_widget_factory->widgets as $widget ) {
		$registered_widgets[ $widget->id_base ] = $widget->name;
	}
}

// Get registered sidebars
global $wp_registered_sidebars;
$registered_sidebars = $wp_registered_sidebars;

// Get menu locations
$menu_locations = get_registered_nav_menus();
$assigned_menus = get_nav_menu_locations();
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Fragment Caching', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is Fragment Caching?', 'wp-speed-booster' ); ?></strong></p>
		<p><?php esc_html_e( 'Fragment caching stores the HTML output of widgets, sidebars, menus, and shortcodes. This prevents WordPress from having to regenerate the same content on every page load, dramatically improving performance for widget-heavy sites.', 'wp-speed-booster' ); ?></p>
		<p><strong><?php esc_html_e( 'Expected Performance Impact:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( '20-40% faster page generation, 50-80% reduction in database queries for widget content, lower CPU usage.', 'wp-speed-booster' ); ?></p>
	</div>
</div>

<!-- Enable/Disable Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Enable/Disable Features', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Fragment Caching', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[fragment_cache_enabled]" value="1" <?php checked( $fragment_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable fragment caching system', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Master toggle for all fragment caching features', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Widgets', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cache_widgets]" value="1" <?php checked( $cache_widgets, 1 ); ?>>
					<?php esc_html_e( 'Cache individual widget output', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Recommended for sites with many widgets', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Sidebars', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cache_sidebars]" value="1" <?php checked( $cache_sidebars, 1 ); ?>>
					<?php esc_html_e( 'Cache entire sidebars', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Cache complete sidebar output including all widgets', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Menus', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cache_menus]" value="1" <?php checked( $cache_menus, 1 ); ?>>
					<?php esc_html_e( 'Cache navigation menus', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Particularly beneficial for mega menus or menus with many items', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Shortcodes', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cache_shortcodes]" value="1" <?php checked( $cache_shortcodes, 1 ); ?>>
					<?php esc_html_e( 'Cache shortcode output', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Only caches shortcodes specified in the whitelist below', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Configuration Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'General Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Default Cache Lifespan', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="number" name="wpsb_options[fragment_cache_time]" value="<?php echo esc_attr( $fragment_cache_time ); ?>" min="60" max="86400" class="small-text"> <?php esc_html_e( 'seconds', 'wp-speed-booster' ); ?>
				<p class="description"><?php esc_html_e( 'How long to cache fragments (3600 = 1 hour, recommended)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache for Logged-in Users', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[fragment_cache_logged_in]" value="1" <?php checked( $fragment_cache_logged_in, 1 ); ?>>
					<?php esc_html_e( 'Skip caching for logged-in users', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Enable if widgets show user-specific content', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Widget Settings -->
<?php if ( ! empty( $registered_widgets ) ): ?>
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Widget Cache Settings', 'wp-speed-booster' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Select which widget types to cache. Only selected widgets will be cached.', 'wp-speed-booster' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Widgets', 'wp-speed-booster' ); ?></th>
			<td>
				<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $registered_widgets as $id_base => $name ): ?>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="wpsb_options[cached_widget_list][]" value="<?php echo esc_attr( $id_base ); ?>" <?php checked( in_array( $id_base, $cached_widget_list ) ); ?>>
							<strong><?php echo esc_html( $name ); ?></strong>
							<span style="color: #666; font-size: 12px;">(<?php echo esc_html( $id_base ); ?>)</span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Tip: Start with static widgets like "Categories", "Recent Posts", "Archives"', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>
<?php endif; ?>

<!-- Sidebar Settings -->
<?php if ( ! empty( $registered_sidebars ) ): ?>
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Sidebar Cache Settings', 'wp-speed-booster' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Select which sidebars to cache. Entire sidebar output will be cached including all widgets.', 'wp-speed-booster' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Sidebars', 'wp-speed-booster' ); ?></th>
			<td>
				<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $registered_sidebars as $sidebar_id => $sidebar ): ?>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="wpsb_options[cached_sidebar_list][]" value="<?php echo esc_attr( $sidebar_id ); ?>" <?php checked( in_array( $sidebar_id, $cached_sidebar_list ) ); ?>>
							<strong><?php echo esc_html( $sidebar['name'] ); ?></strong>
							<?php if ( ! empty( $sidebar['description'] ) ): ?>
								<span style="color: #666; font-size: 12px;"> - <?php echo esc_html( $sidebar['description'] ); ?></span>
							<?php endif; ?>
							<span style="color: #999; font-size: 11px;">(ID: <?php echo esc_html( $sidebar_id ); ?>)</span>
						</label>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>
	</table>
</div>
<?php endif; ?>

<!-- Menu Settings -->
<?php if ( ! empty( $menu_locations ) ): ?>
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Menu Cache Settings', 'wp-speed-booster' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Select which menu locations to cache.', 'wp-speed-booster' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Menus', 'wp-speed-booster' ); ?></th>
			<td>
				<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $menu_locations as $location => $description ): ?>
						<?php
						$assigned_menu = '';
						if ( isset( $assigned_menus[ $location ] ) ) {
							$menu_obj = wp_get_nav_menu_object( $assigned_menus[ $location ] );
							if ( $menu_obj ) {
								$assigned_menu = $menu_obj->name;
							}
						}
						?>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="wpsb_options[cached_menu_list][]" value="<?php echo esc_attr( $location ); ?>" <?php checked( in_array( $location, $cached_menu_list ) ); ?>>
							<strong><?php echo esc_html( $description ); ?></strong>
							<?php if ( $assigned_menu ): ?>
								<span style="color: #666; font-size: 12px;"> - Assigned: <?php echo esc_html( $assigned_menu ); ?></span>
							<?php else: ?>
								<span style="color: #d63638; font-size: 12px;"> - No menu assigned</span>
							<?php endif; ?>
							<span style="color: #999; font-size: 11px;">(<?php echo esc_html( $location ); ?>)</span>
						</label>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>
	</table>
</div>
<?php endif; ?>

<!-- Shortcode Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Shortcode Cache Settings', 'wp-speed-booster' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Enter shortcode tags to cache, one per line. Only these shortcodes will be cached.', 'wp-speed-booster' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Shortcodes', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[cached_shortcode_list]" rows="10" class="large-text code" placeholder="<?php esc_attr_e( 'gallery', 'wp-speed-booster' ); ?>&#10;<?php esc_attr_e( 'contact-form', 'wp-speed-booster' ); ?>&#10;<?php esc_attr_e( 'recent-posts', 'wp-speed-booster' ); ?>"><?php echo esc_textarea( $cached_shortcode_list ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Enter one shortcode tag per line (without brackets). Common examples:', 'wp-speed-booster' ); ?><br>
					<code>gallery</code>, <code>audio</code>, <code>video</code>, <code>playlist</code>, <code>embed</code>
				</p>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Warning:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Do not cache shortcodes that display user-specific or frequently changing content (e.g., shopping carts, user dashboards).', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Statistics Dashboard -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Cache Statistics', 'wp-speed-booster' ); ?>
		<button type="button" class="button" id="wpspeed-refresh-fragment-stats" style="margin-left: 10px;">
			<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> <?php esc_html_e( 'Refresh', 'wp-speed-booster' ); ?>
		</button>
	</h2>
	
	<div id="wpspeed-fragment-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Total Fragments', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #2271b1;">
				<span id="total-fragments"><?php echo esc_html( number_format( $stats['total_fragments'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Cache Size', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #2271b1;">
				<span id="total-size"><?php echo esc_html( $stats['total_size_formatted'] ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Widget Hits', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="widget-hits"><?php echo esc_html( number_format( $stats['widget_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Sidebar Hits', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="sidebar-hits"><?php echo esc_html( number_format( $stats['sidebar_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Menu Hits', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="menu-hits"><?php echo esc_html( number_format( $stats['menu_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Shortcode Hits', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="shortcode-hits"><?php echo esc_html( number_format( $stats['shortcode_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Overall Hit Ratio', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: <?php echo $stats['overall_hit_ratio'] >= 90 ? '#00a32a' : ( $stats['overall_hit_ratio'] >= 70 ? '#dba617' : '#d63638' ); ?>;">
				<span id="hit-ratio"><?php echo esc_html( $stats['overall_hit_ratio'] ); ?></span>%
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Avg Fragment Size', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #2271b1;">
				<span id="avg-size"><?php echo esc_html( size_format( $stats['average_fragment_size'] ) ); ?></span>
			</p>
		</div>
	</div>
</div>

<!-- Actions Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Cache Management', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Clear Cache', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" class="button" id="wpspeed-clear-all-fragments">
					<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> <?php esc_html_e( 'Clear All Fragment Cache', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" class="button" id="wpspeed-clear-widget-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Widget Cache', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" class="button" id="wpspeed-clear-sidebar-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Sidebar Cache', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" class="button" id="wpspeed-clear-menu-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Menu Cache', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" class="button" id="wpspeed-clear-shortcode-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Shortcode Cache', 'wp-speed-booster' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Clear cached fragments. Cache will be automatically cleared when content is updated.', 'wp-speed-booster' ); ?></p>
				<div id="fragment-cache-result" style="margin-top: 10px;"></div>
			</td>
		</tr>
	</table>
</div>

<!-- Best Practices -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Best Practices & Tips', 'wp-speed-booster' ); ?></h2>
	
	<div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px;">
		<h3 style="margin-top: 0;"><?php esc_html_e( '✓ Which Elements to Cache', 'wp-speed-booster' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Static widgets: Categories, Archives, Tag Cloud, Recent Posts', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Sidebars with multiple widgets that don\'t change frequently', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Navigation menus, especially mega menus with many items', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Shortcodes for static content (galleries, embeds)', 'wp-speed-booster' ); ?></li>
		</ul>
		
		<h3><?php esc_html_e( '✗ What NOT to Cache', 'wp-speed-booster' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'User-specific widgets (login forms, user info)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Dynamic content that changes on every page load', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Shopping cart widgets or WooCommerce mini cart', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Real-time data widgets (stock tickers, live feeds)', 'wp-speed-booster' ); ?></li>
		</ul>
		
		<h3><?php esc_html_e( '⏱ Cache Lifespan Recommendations', 'wp-speed-booster' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( '1 hour (3600s):', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Default, good for most sites', 'wp-speed-booster' ); ?></li>
			<li><strong><?php esc_html_e( '6 hours (21600s):', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'For very static content', 'wp-speed-booster' ); ?></li>
			<li><strong><?php esc_html_e( '15 minutes (900s):', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'For frequently updated sites', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var nonce = '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>';
	
	// Clear all fragments
	$('#wpspeed-clear-all-fragments').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to clear all fragment cache?', 'wp-speed-booster' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		var $result = $('#fragment-cache-result');
		
		$button.prop('disabled', true);
		$result.html('<div class="notice notice-info inline"><p><?php esc_html_e( 'Clearing cache...', 'wp-speed-booster' ); ?></p></div>');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_clear_fragment_cache',
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					refreshStats();
				} else {
					$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
				}
			},
			error: function() {
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to clear cache', 'wp-speed-booster' ); ?></p></div>');
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});
	
	// Clear specific fragment types
	function clearFragmentType(type, buttonId) {
		$('#' + buttonId).on('click', function() {
			var $button = $(this);
			var $result = $('#fragment-cache-result');
			
			$button.prop('disabled', true);
			$result.html('<div class="notice notice-info inline"><p><?php esc_html_e( 'Clearing cache...', 'wp-speed-booster' ); ?></p></div>');
			
			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'wpsb_clear_fragment_type',
					type: type,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
						refreshStats();
					} else {
						$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
					}
				},
				error: function() {
					$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to clear cache', 'wp-speed-booster' ); ?></p></div>');
				},
				complete: function() {
					$button.prop('disabled', false);
				}
			});
		});
	}
	
	clearFragmentType('widget', 'wpspeed-clear-widget-cache');
	clearFragmentType('sidebar', 'wpspeed-clear-sidebar-cache');
	clearFragmentType('menu', 'wpspeed-clear-menu-cache');
	clearFragmentType('shortcode', 'wpspeed-clear-shortcode-cache');
	
	// Refresh statistics
	function refreshStats() {
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_get_fragment_stats',
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					var stats = response.data;
					$('#total-fragments').text(stats.total_fragments.toLocaleString());
					$('#total-size').text(stats.total_size_formatted);
					$('#widget-hits').text(stats.widget_hits.toLocaleString());
					$('#sidebar-hits').text(stats.sidebar_hits.toLocaleString());
					$('#menu-hits').text(stats.menu_hits.toLocaleString());
					$('#shortcode-hits').text(stats.shortcode_hits.toLocaleString());
					$('#hit-ratio').text(stats.overall_hit_ratio);
					$('#avg-size').text(stats.average_fragment_size.toLocaleString() + ' B');
					
					// Update hit ratio color
					var ratio = parseFloat(stats.overall_hit_ratio);
					var color = ratio >= 90 ? '#00a32a' : (ratio >= 70 ? '#dba617' : '#d63638');
					$('#hit-ratio').parent().css('color', color);
				}
			}
		});
	}
	
	$('#wpspeed-refresh-fragment-stats').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true);
		refreshStats();
		setTimeout(function() {
			$button.prop('disabled', false);
		}, 1000);
	});
});
</script>
