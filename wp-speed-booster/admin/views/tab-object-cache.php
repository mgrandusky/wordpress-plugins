<?php
/**
 * Object Cache Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize Object Cache class
$object_cache = new WPSB_Object_Cache();

// Get settings
$cache_type = ! empty( $options['object_cache_backend'] ) ? $options['object_cache_backend'] : 'auto';
$redis_host = ! empty( $options['redis_host'] ) ? $options['redis_host'] : '127.0.0.1';
$redis_port = ! empty( $options['redis_port'] ) ? $options['redis_port'] : 6379;
$redis_password = ! empty( $options['redis_password'] ) ? $options['redis_password'] : '';
$redis_database = ! empty( $options['redis_database'] ) ? $options['redis_database'] : 0;
$memcached_servers = ! empty( $options['memcached_servers'] ) ? $options['memcached_servers'] : '127.0.0.1:11211';

// Get current status
$detected_type = $object_cache->detect_cache_type();
$is_installed = $object_cache->is_dropin_installed();
$stats = $object_cache->get_stats();

// Check availability of each backend
$redis_available = class_exists( 'Redis' );
$memcached_available = class_exists( 'Memcached' );
$apcu_available = function_exists( 'apcu_fetch' );
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Object Caching Integration', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is Object Caching?', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Object caching stores database query results in memory (RAM) instead of repeatedly querying the database. This is one of the most impactful performance optimizations for WordPress sites, typically reducing database queries by 50-80% and dramatically improving page load times.', 'wp-speed-booster' ); ?></p>
	</div>
</div>

<!-- Status Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Status', 'wp-speed-booster' ); ?></h2>
	
	<div class="wpspeed-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
		<div class="wpspeed-status-box" style="padding: 15px; background: <?php echo $is_installed ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'Drop-in Status', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 24px; font-weight: bold;">
				<?php echo $is_installed ? '✅ ' . esc_html__( 'Installed', 'wp-speed-booster' ) : '❌ ' . esc_html__( 'Not Installed', 'wp-speed-booster' ); ?>
			</p>
		</div>
		
		<div class="wpspeed-status-box" style="padding: 15px; background: <?php echo $detected_type ? '#d4edda' : '#fff3cd'; ?>; border-radius: 5px;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'Cache Type', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 24px; font-weight: bold;">
				<?php echo $detected_type ? esc_html( ucfirst( $detected_type ) ) : esc_html__( 'None', 'wp-speed-booster' ); ?>
			</p>
		</div>
	</div>
</div>

<!-- Statistics Dashboard -->
<?php if ( $is_installed ): ?>
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Cache Statistics', 'wp-speed-booster' ); ?> 
		<button type="button" class="button" id="wpspeed-refresh-stats" style="margin-left: 10px;">
			<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> <?php esc_html_e( 'Refresh', 'wp-speed-booster' ); ?>
		</button>
	</h2>
	
	<div class="wpspeed-stats-grid" id="wpspeed-cache-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Cache Hits', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #28a745;">
				<span id="cache-hits"><?php echo esc_html( number_format( $stats['hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Cache Misses', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #dc3545;">
				<span id="cache-misses"><?php echo esc_html( number_format( $stats['misses'] ) ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Hit Ratio', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: <?php echo $stats['ratio'] >= 90 ? '#28a745' : ( $stats['ratio'] >= 70 ? '#ffc107' : '#dc3545' ); ?>;">
				<span id="cache-ratio"><?php echo esc_html( $stats['ratio'] ); ?></span>%
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Memory Used', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #007bff;">
				<span id="cache-memory"><?php echo $stats['memory'] ? esc_html( $stats['memory'] ) : esc_html__( 'N/A', 'wp-speed-booster' ); ?></span>
			</p>
		</div>
		
		<div class="wpspeed-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Total Entries', 'wp-speed-booster' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #6c757d;">
				<span id="cache-entries"><?php echo esc_html( number_format( $stats['entries'] ) ); ?></span>
			</p>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Configuration Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Configuration', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Type', 'wp-speed-booster' ); ?></th>
			<td>
				<select name="wpsb_options[object_cache_backend]" id="wpspeed-cache-type">
					<option value="auto" <?php selected( $cache_type, 'auto' ); ?>><?php esc_html_e( 'Auto-detect', 'wp-speed-booster' ); ?></option>
					<option value="redis" <?php selected( $cache_type, 'redis' ); ?> <?php disabled( ! $redis_available ); ?>><?php esc_html_e( 'Redis', 'wp-speed-booster' ); ?><?php echo ! $redis_available ? ' (' . esc_html__( 'Not Available', 'wp-speed-booster' ) . ')' : ''; ?></option>
					<option value="memcached" <?php selected( $cache_type, 'memcached' ); ?> <?php disabled( ! $memcached_available ); ?>><?php esc_html_e( 'Memcached', 'wp-speed-booster' ); ?><?php echo ! $memcached_available ? ' (' . esc_html__( 'Not Available', 'wp-speed-booster' ) . ')' : ''; ?></option>
					<option value="apcu" <?php selected( $cache_type, 'apcu' ); ?> <?php disabled( ! $apcu_available ); ?>><?php esc_html_e( 'APCu', 'wp-speed-booster' ); ?><?php echo ! $apcu_available ? ' (' . esc_html__( 'Not Available', 'wp-speed-booster' ) . ')' : ''; ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Select the object caching backend to use', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
	
	<!-- Redis Settings -->
	<div id="redis-settings" class="wpspeed-cache-settings" style="display: none;">
		<h3><?php esc_html_e( 'Redis Settings', 'wp-speed-booster' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Host', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="text" name="wpsb_options[redis_host]" value="<?php echo esc_attr( $redis_host ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Redis server hostname or IP address (default: 127.0.0.1)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Port', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="number" name="wpsb_options[redis_port]" value="<?php echo esc_attr( $redis_port ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'Redis server port (default: 6379)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Password', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="password" name="wpsb_options[redis_password]" value="<?php echo esc_attr( $redis_password ); ?>" class="regular-text" autocomplete="off">
					<p class="description"><?php esc_html_e( 'Redis password (leave empty if not required)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Database', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="number" name="wpsb_options[redis_database]" value="<?php echo esc_attr( $redis_database ); ?>" min="0" max="15" class="small-text">
					<p class="description"><?php esc_html_e( 'Database number (0-15, default: 0)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<button type="button" class="button" id="wpspeed-test-redis">
						<?php esc_html_e( 'Test Redis Connection', 'wp-speed-booster' ); ?>
					</button>
					<span id="redis-test-result" style="margin-left: 10px;"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<!-- Memcached Settings -->
	<div id="memcached-settings" class="wpspeed-cache-settings" style="display: none;">
		<h3><?php esc_html_e( 'Memcached Settings', 'wp-speed-booster' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Servers', 'wp-speed-booster' ); ?></th>
				<td>
					<textarea name="wpsb_options[memcached_servers]" rows="5" class="large-text code"><?php echo esc_textarea( $memcached_servers ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Enter one server per line in format host:port', 'wp-speed-booster' ); ?><br>
						<?php esc_html_e( 'Example: 127.0.0.1:11211', 'wp-speed-booster' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<button type="button" class="button" id="wpspeed-test-memcached">
						<?php esc_html_e( 'Test Memcached Connection', 'wp-speed-booster' ); ?>
					</button>
					<span id="memcached-test-result" style="margin-left: 10px;"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<!-- APCu Settings -->
	<div id="apcu-settings" class="wpspeed-cache-settings" style="display: none;">
		<h3><?php esc_html_e( 'APCu Settings', 'wp-speed-booster' ); ?></h3>
		<p><?php esc_html_e( 'APCu does not require any configuration. Just ensure it is installed and enabled on your server.', 'wp-speed-booster' ); ?></p>
		<button type="button" class="button" id="wpspeed-test-apcu">
			<?php esc_html_e( 'Test APCu', 'wp-speed-booster' ); ?>
		</button>
		<span id="apcu-test-result" style="margin-left: 10px;"></span>
	</div>
</div>

<!-- Installation Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Drop-in Management', 'wp-speed-booster' ); ?></h2>
	
	<p class="description">
		<?php esc_html_e( 'The object cache drop-in file (wp-content/object-cache.php) needs to be installed to enable object caching.', 'wp-speed-booster' ); ?>
	</p>
	
	<p>
		<?php if ( ! $is_installed ): ?>
			<button type="button" class="button button-primary" id="wpspeed-install-dropin">
				<?php esc_html_e( 'Install Object Cache', 'wp-speed-booster' ); ?>
			</button>
		<?php else: ?>
			<button type="button" class="button button-secondary" id="wpspeed-remove-dropin">
				<?php esc_html_e( 'Remove Object Cache', 'wp-speed-booster' ); ?>
			</button>
		<?php endif; ?>
		
		<button type="button" class="button" id="wpspeed-flush-cache" <?php echo ! $is_installed ? 'disabled' : ''; ?>>
			<?php esc_html_e( 'Flush Object Cache', 'wp-speed-booster' ); ?>
		</button>
	</p>
	
	<div id="dropin-action-result" style="margin-top: 10px;"></div>
</div>

<!-- Installation Guide -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Server Installation Guide', 'wp-speed-booster' ); ?></h2>
	
	<p><?php esc_html_e( 'If a cache backend is not available, you need to install it on your server first:', 'wp-speed-booster' ); ?></p>
	
	<h3><?php esc_html_e( 'Redis', 'wp-speed-booster' ); ?></h3>
	<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code># Ubuntu/Debian
sudo apt-get update
sudo apt-get install redis-server php-redis

# CentOS/RHEL
sudo yum install redis php-redis</code></pre>
	
	<h3><?php esc_html_e( 'Memcached', 'wp-speed-booster' ); ?></h3>
	<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code># Ubuntu/Debian
sudo apt-get update
sudo apt-get install memcached php-memcached

# CentOS/RHEL
sudo yum install memcached php-memcached</code></pre>
	
	<h3><?php esc_html_e( 'APCu', 'wp-speed-booster' ); ?></h3>
	<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code># Ubuntu/Debian
sudo apt-get update
sudo apt-get install php-apcu

# CentOS/RHEL
sudo yum install php-apcu</code></pre>
	
	<p><strong><?php esc_html_e( 'Note:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'After installing, restart your web server (Apache/Nginx) and PHP-FPM for the changes to take effect.', 'wp-speed-booster' ); ?></p>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Show/hide settings based on cache type selection
	function toggleCacheSettings() {
		var cacheType = $('#wpspeed-cache-type').val();
		$('.wpspeed-cache-settings').hide();
		
		if (cacheType === 'redis') {
			$('#redis-settings').show();
		} else if (cacheType === 'memcached') {
			$('#memcached-settings').show();
		} else if (cacheType === 'apcu') {
			$('#apcu-settings').show();
		}
	}
	
	// Initialize on page load
	toggleCacheSettings();
	
	// Toggle on change
	$('#wpspeed-cache-type').on('change', toggleCacheSettings);
	
	// Test Redis connection
	$('#wpspeed-test-redis').on('click', function() {
		var $button = $(this);
		var $result = $('#redis-test-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'wp-speed-booster' ); ?>');
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_test_object_cache',
				backend: 'redis',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
				} else {
					$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection test failed', 'wp-speed-booster' ); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Test Redis Connection', 'wp-speed-booster' ); ?>');
			}
		});
	});
	
	// Test Memcached connection
	$('#wpspeed-test-memcached').on('click', function() {
		var $button = $(this);
		var $result = $('#memcached-test-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'wp-speed-booster' ); ?>');
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_test_object_cache',
				backend: 'memcached',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
				} else {
					$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection test failed', 'wp-speed-booster' ); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Test Memcached Connection', 'wp-speed-booster' ); ?>');
			}
		});
	});
	
	// Test APCu
	$('#wpspeed-test-apcu').on('click', function() {
		var $button = $(this);
		var $result = $('#apcu-test-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'wp-speed-booster' ); ?>');
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_test_object_cache',
				backend: 'apcu',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
				} else {
					$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Test failed', 'wp-speed-booster' ); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Test APCu', 'wp-speed-booster' ); ?>');
			}
		});
	});
	
	// Install drop-in
	$('#wpspeed-install-dropin').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will create wp-content/object-cache.php. Continue?', 'wp-speed-booster' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		var $result = $('#dropin-action-result');
		
		$button.prop('disabled', true);
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_install_dropin',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
					$button.prop('disabled', false);
				}
			},
			error: function() {
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to install drop-in', 'wp-speed-booster' ); ?></p></div>');
				$button.prop('disabled', false);
			}
		});
	});
	
	// Remove drop-in
	$('#wpspeed-remove-dropin').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will remove wp-content/object-cache.php. Continue?', 'wp-speed-booster' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		var $result = $('#dropin-action-result');
		
		$button.prop('disabled', true);
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_remove_dropin',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
					$button.prop('disabled', false);
				}
			},
			error: function() {
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to remove drop-in', 'wp-speed-booster' ); ?></p></div>');
				$button.prop('disabled', false);
			}
		});
	});
	
	// Flush cache
	$('#wpspeed-flush-cache').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will clear all cached data. Continue?', 'wp-speed-booster' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		var $result = $('#dropin-action-result');
		
		$button.prop('disabled', true);
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_flush_object_cache',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
				} else {
					$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
				}
				$button.prop('disabled', false);
			},
			error: function() {
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to flush cache', 'wp-speed-booster' ); ?></p></div>');
				$button.prop('disabled', false);
			}
		});
	});
	
	// Refresh stats
	$('#wpspeed-refresh-stats').on('click', function() {
		var $button = $(this);
		
		$button.prop('disabled', true);
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpsb_get_cache_stats',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					var stats = response.data;
					$('#cache-hits').text(stats.hits.toLocaleString());
					$('#cache-misses').text(stats.misses.toLocaleString());
					$('#cache-ratio').text(stats.ratio);
					$('#cache-memory').text(stats.memory || 'N/A');
					$('#cache-entries').text(stats.entries.toLocaleString());
					
					// Update ratio color
					var ratioColor = stats.ratio >= 90 ? '#28a745' : (stats.ratio >= 70 ? '#ffc107' : '#dc3545');
					$('#cache-ratio').parent().css('color', ratioColor);
				}
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});
});
</script>
