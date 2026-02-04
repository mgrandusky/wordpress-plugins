<?php
/**
 * Fragment Cache Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize Fragment Cache class to get stats
$fragment_cache = new VelocityWP_Fragment_Cache();
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

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Fragment Caching', 'velocitywp' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is Fragment Caching?', 'velocitywp' ); ?></strong></p>
		<p><?php esc_html_e( 'Fragment caching stores the HTML output of widgets, sidebars, menus, and shortcodes. This prevents WordPress from having to regenerate the same content on every page load, dramatically improving performance for widget-heavy sites.', 'velocitywp' ); ?></p>
		<p><strong><?php esc_html_e( 'Expected Performance Impact:', 'velocitywp' ); ?></strong> <?php esc_html_e( '20-40% faster page generation, 50-80% reduction in database queries for widget content, lower CPU usage.', 'velocitywp' ); ?></p>
	</div>
</div>

<!-- Enable/Disable Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Enable/Disable Features', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Fragment Caching', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[fragment_cache_enabled]" value="1" <?php checked( $fragment_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable fragment caching system', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Master toggle for all fragment caching features', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Widgets', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cache_widgets]" value="1" <?php checked( $cache_widgets, 1 ); ?>>
					<?php esc_html_e( 'Cache individual widget output', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Recommended for sites with many widgets', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Sidebars', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cache_sidebars]" value="1" <?php checked( $cache_sidebars, 1 ); ?>>
					<?php esc_html_e( 'Cache entire sidebars', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Cache complete sidebar output including all widgets', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Menus', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cache_menus]" value="1" <?php checked( $cache_menus, 1 ); ?>>
					<?php esc_html_e( 'Cache navigation menus', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Particularly beneficial for mega menus or menus with many items', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Shortcodes', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cache_shortcodes]" value="1" <?php checked( $cache_shortcodes, 1 ); ?>>
					<?php esc_html_e( 'Cache shortcode output', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Only caches shortcodes specified in the whitelist below', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Configuration Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'General Settings', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Default Cache Lifespan', 'velocitywp' ); ?></th>
			<td>
				<input type="number" name="velocitywp_options[fragment_cache_time]" value="<?php echo esc_attr( $fragment_cache_time ); ?>" min="60" max="86400" class="small-text"> <?php esc_html_e( 'seconds', 'velocitywp' ); ?>
				<p class="description"><?php esc_html_e( 'How long to cache fragments (3600 = 1 hour, recommended)', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache for Logged-in Users', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[fragment_cache_logged_in]" value="1" <?php checked( $fragment_cache_logged_in, 1 ); ?>>
					<?php esc_html_e( 'Skip caching for logged-in users', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Enable if widgets show user-specific content', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Widget Settings -->
<?php if ( ! empty( $registered_widgets ) ): ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Widget Cache Settings', 'velocitywp' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Select which widget types to cache. Only selected widgets will be cached.', 'velocitywp' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Widgets', 'velocitywp' ); ?></th>
			<td>
				<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $registered_widgets as $id_base => $name ): ?>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="velocitywp_options[cached_widget_list][]" value="<?php echo esc_attr( $id_base ); ?>" <?php checked( in_array( $id_base, $cached_widget_list ) ); ?>>
							<strong><?php echo esc_html( $name ); ?></strong>
							<span style="color: #666; font-size: 12px;">(<?php echo esc_html( $id_base ); ?>)</span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Tip: Start with static widgets like "Categories", "Recent Posts", "Archives"', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>
<?php endif; ?>

<!-- Sidebar Settings -->
<?php if ( ! empty( $registered_sidebars ) ): ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Sidebar Cache Settings', 'velocitywp' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Select which sidebars to cache. Entire sidebar output will be cached including all widgets.', 'velocitywp' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Sidebars', 'velocitywp' ); ?></th>
			<td>
				<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $registered_sidebars as $sidebar_id => $sidebar ): ?>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="velocitywp_options[cached_sidebar_list][]" value="<?php echo esc_attr( $sidebar_id ); ?>" <?php checked( in_array( $sidebar_id, $cached_sidebar_list ) ); ?>>
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
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Menu Cache Settings', 'velocitywp' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Select which menu locations to cache.', 'velocitywp' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Menus', 'velocitywp' ); ?></th>
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
							<input type="checkbox" name="velocitywp_options[cached_menu_list][]" value="<?php echo esc_attr( $location ); ?>" <?php checked( in_array( $location, $cached_menu_list ) ); ?>>
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
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Shortcode Cache Settings', 'velocitywp' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Enter shortcode tags to cache, one per line. Only these shortcodes will be cached.', 'velocitywp' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cached Shortcodes', 'velocitywp' ); ?></th>
			<td>
				<textarea name="velocitywp_options[cached_shortcode_list]" rows="10" class="large-text code" placeholder="<?php esc_attr_e( 'gallery', 'velocitywp' ); ?>&#10;<?php esc_attr_e( 'contact-form', 'velocitywp' ); ?>&#10;<?php esc_attr_e( 'recent-posts', 'velocitywp' ); ?>"><?php echo esc_textarea( $cached_shortcode_list ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Enter one shortcode tag per line (without brackets). Common examples:', 'velocitywp' ); ?><br>
					<code>gallery</code>, <code>audio</code>, <code>video</code>, <code>playlist</code>, <code>embed</code>
				</p>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Warning:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Do not cache shortcodes that display user-specific or frequently changing content (e.g., shopping carts, user dashboards).', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Statistics Dashboard -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Cache Statistics', 'velocitywp' ); ?>
		<button type="button" class="button" id="velocitywp-refresh-fragment-stats" style="margin-left: 10px;">
			<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> <?php esc_html_e( 'Refresh', 'velocitywp' ); ?>
		</button>
	</h2>
	
	<div id="velocitywp-fragment-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Total Fragments', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #2271b1;">
				<span id="total-fragments"><?php echo esc_html( number_format( $stats['total_fragments'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Cache Size', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #2271b1;">
				<span id="total-size"><?php echo esc_html( $stats['total_size_formatted'] ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Widget Hits', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="widget-hits"><?php echo esc_html( number_format( $stats['widget_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Sidebar Hits', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="sidebar-hits"><?php echo esc_html( number_format( $stats['sidebar_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Menu Hits', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="menu-hits"><?php echo esc_html( number_format( $stats['menu_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Shortcode Hits', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #00a32a;">
				<span id="shortcode-hits"><?php echo esc_html( number_format( $stats['shortcode_hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Overall Hit Ratio', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: <?php echo $stats['overall_hit_ratio'] >= 90 ? '#00a32a' : ( $stats['overall_hit_ratio'] >= 70 ? '#dba617' : '#d63638' ); ?>;">
				<span id="hit-ratio"><?php echo esc_html( $stats['overall_hit_ratio'] ); ?></span>%
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Avg Fragment Size', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #2271b1;">
				<span id="avg-size"><?php echo esc_html( size_format( $stats['average_fragment_size'] ) ); ?></span>
			</p>
		</div>
	</div>
</div>

<!-- Actions Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Cache Management', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Clear Cache', 'velocitywp' ); ?></th>
			<td>
				<button type="button" class="button" id="velocitywp-clear-all-fragments">
					<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> <?php esc_html_e( 'Clear All Fragment Cache', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button" id="velocitywp-clear-widget-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Widget Cache', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button" id="velocitywp-clear-sidebar-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Sidebar Cache', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button" id="velocitywp-clear-menu-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Menu Cache', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button" id="velocitywp-clear-shortcode-cache" style="margin-left: 5px;">
					<?php esc_html_e( 'Clear Shortcode Cache', 'velocitywp' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Clear cached fragments. Cache will be automatically cleared when content is updated.', 'velocitywp' ); ?></p>
				<div id="fragment-cache-result" style="margin-top: 10px;"></div>
			</td>
		</tr>
	</table>
</div>

<!-- Best Practices -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Best Practices & Tips', 'velocitywp' ); ?></h2>
	
	<div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px;">
		<h3 style="margin-top: 0;"><?php esc_html_e( '✓ Which Elements to Cache', 'velocitywp' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Static widgets: Categories, Archives, Tag Cloud, Recent Posts', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Sidebars with multiple widgets that don\'t change frequently', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Navigation menus, especially mega menus with many items', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Shortcodes for static content (galleries, embeds)', 'velocitywp' ); ?></li>
		</ul>
		
		<h3><?php esc_html_e( '✗ What NOT to Cache', 'velocitywp' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'User-specific widgets (login forms, user info)', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Dynamic content that changes on every page load', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Shopping cart widgets or WooCommerce mini cart', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Real-time data widgets (stock tickers, live feeds)', 'velocitywp' ); ?></li>
		</ul>
		
		<h3><?php esc_html_e( '⏱ Cache Lifespan Recommendations', 'velocitywp' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( '1 hour (3600s):', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Default, good for most sites', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( '6 hours (21600s):', 'velocitywp' ); ?></strong> <?php esc_html_e( 'For very static content', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( '15 minutes (900s):', 'velocitywp' ); ?></strong> <?php esc_html_e( 'For frequently updated sites', 'velocitywp' ); ?></li>
		</ul>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var nonce = '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>';
	
	// Clear all fragments
	$('#velocitywp-clear-all-fragments').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to clear all fragment cache?', 'velocitywp' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		var $result = $('#fragment-cache-result');
		
		$button.prop('disabled', true);
		$result.html('<div class="notice notice-info inline"><p><?php esc_html_e( 'Clearing cache...', 'velocitywp' ); ?></p></div>');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'velocitywp_clear_fragment_cache',
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
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to clear cache', 'velocitywp' ); ?></p></div>');
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
			$result.html('<div class="notice notice-info inline"><p><?php esc_html_e( 'Clearing cache...', 'velocitywp' ); ?></p></div>');
			
			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'velocitywp_clear_fragment_type',
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
					$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to clear cache', 'velocitywp' ); ?></p></div>');
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
				action: 'velocitywp_get_fragment_stats',
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
	
	$('#velocitywp-refresh-fragment-stats').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true);
		refreshStats();
		setTimeout(function() {
			$button.prop('disabled', false);
		}, 1000);
	});
});
</script>
