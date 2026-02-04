<?php
/**
 * Object Cache Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize Object Cache class
$object_cache = new VelocityWP_Object_Cache();

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

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Object Caching Integration', 'velocitywp' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is Object Caching?', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Object caching stores database query results in memory (RAM) instead of repeatedly querying the database. This is one of the most impactful performance optimizations for WordPress sites, typically reducing database queries by 50-80% and dramatically improving page load times.', 'velocitywp' ); ?></p>
	</div>
</div>

<!-- Status Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Status', 'velocitywp' ); ?></h2>
	
	<div class="velocitywp-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
		<div class="velocitywp-status-box" style="padding: 15px; background: <?php echo $is_installed ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'Drop-in Status', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 24px; font-weight: bold;">
				<?php echo $is_installed ? '✅ ' . esc_html__( 'Installed', 'velocitywp' ) : '❌ ' . esc_html__( 'Not Installed', 'velocitywp' ); ?>
			</p>
		</div>
		
		<div class="velocitywp-status-box" style="padding: 15px; background: <?php echo $detected_type ? '#d4edda' : '#fff3cd'; ?>; border-radius: 5px;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'Cache Type', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 24px; font-weight: bold;">
				<?php echo $detected_type ? esc_html( ucfirst( $detected_type ) ) : esc_html__( 'None', 'velocitywp' ); ?>
			</p>
		</div>
	</div>
</div>

<!-- Statistics Dashboard -->
<?php if ( $is_installed ): ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Cache Statistics', 'velocitywp' ); ?> 
		<button type="button" class="button" id="velocitywp-refresh-stats" style="margin-left: 10px;">
			<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> <?php esc_html_e( 'Refresh', 'velocitywp' ); ?>
		</button>
	</h2>
	
	<div class="velocitywp-stats-grid" id="velocitywp-cache-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Cache Hits', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #28a745;">
				<span id="cache-hits"><?php echo esc_html( number_format( $stats['hits'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Cache Misses', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #dc3545;">
				<span id="cache-misses"><?php echo esc_html( number_format( $stats['misses'] ) ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Hit Ratio', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: <?php echo $stats['ratio'] >= 90 ? '#28a745' : ( $stats['ratio'] >= 70 ? '#ffc107' : '#dc3545' ); ?>;">
				<span id="cache-ratio"><?php echo esc_html( $stats['ratio'] ); ?></span>%
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Memory Used', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #007bff;">
				<span id="cache-memory"><?php echo $stats['memory'] ? esc_html( $stats['memory'] ) : esc_html__( 'N/A', 'velocitywp' ); ?></span>
			</p>
		</div>
		
		<div class="velocitywp-stat-box" style="padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
			<h4 style="margin-top: 0; color: #666;"><?php esc_html_e( 'Total Entries', 'velocitywp' ); ?></h4>
			<p style="margin: 0; font-size: 28px; font-weight: bold; color: #6c757d;">
				<span id="cache-entries"><?php echo esc_html( number_format( $stats['entries'] ) ); ?></span>
			</p>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Configuration Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Configuration', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Cache Type', 'velocitywp' ); ?></th>
			<td>
				<select name="velocitywp_options[object_cache_backend]" id="velocitywp-cache-type">
					<option value="auto" <?php selected( $cache_type, 'auto' ); ?>><?php esc_html_e( 'Auto-detect', 'velocitywp' ); ?></option>
					<option value="redis" <?php selected( $cache_type, 'redis' ); ?> <?php disabled( ! $redis_available ); ?>><?php esc_html_e( 'Redis', 'velocitywp' ); ?><?php echo ! $redis_available ? ' (' . esc_html__( 'Not Available', 'velocitywp' ) . ')' : ''; ?></option>
					<option value="memcached" <?php selected( $cache_type, 'memcached' ); ?> <?php disabled( ! $memcached_available ); ?>><?php esc_html_e( 'Memcached', 'velocitywp' ); ?><?php echo ! $memcached_available ? ' (' . esc_html__( 'Not Available', 'velocitywp' ) . ')' : ''; ?></option>
					<option value="apcu" <?php selected( $cache_type, 'apcu' ); ?> <?php disabled( ! $apcu_available ); ?>><?php esc_html_e( 'APCu', 'velocitywp' ); ?><?php echo ! $apcu_available ? ' (' . esc_html__( 'Not Available', 'velocitywp' ) . ')' : ''; ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Select the object caching backend to use', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
	
	<!-- Redis Settings -->
	<div id="redis-settings" class="velocitywp-cache-settings" style="display: none;">
		<h3><?php esc_html_e( 'Redis Settings', 'velocitywp' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Host', 'velocitywp' ); ?></th>
				<td>
					<input type="text" name="velocitywp_options[redis_host]" value="<?php echo esc_attr( $redis_host ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Redis server hostname or IP address (default: 127.0.0.1)', 'velocitywp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Port', 'velocitywp' ); ?></th>
				<td>
					<input type="number" name="velocitywp_options[redis_port]" value="<?php echo esc_attr( $redis_port ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'Redis server port (default: 6379)', 'velocitywp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Password', 'velocitywp' ); ?></th>
				<td>
					<input type="password" name="velocitywp_options[redis_password]" value="<?php echo esc_attr( $redis_password ); ?>" class="regular-text" autocomplete="off">
					<p class="description"><?php esc_html_e( 'Redis password (leave empty if not required)', 'velocitywp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Database', 'velocitywp' ); ?></th>
				<td>
					<input type="number" name="velocitywp_options[redis_database]" value="<?php echo esc_attr( $redis_database ); ?>" min="0" max="15" class="small-text">
					<p class="description"><?php esc_html_e( 'Database number (0-15, default: 0)', 'velocitywp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<button type="button" class="button" id="velocitywp-test-redis">
						<?php esc_html_e( 'Test Redis Connection', 'velocitywp' ); ?>
					</button>
					<span id="redis-test-result" style="margin-left: 10px;"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<!-- Memcached Settings -->
	<div id="memcached-settings" class="velocitywp-cache-settings" style="display: none;">
		<h3><?php esc_html_e( 'Memcached Settings', 'velocitywp' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Servers', 'velocitywp' ); ?></th>
				<td>
					<textarea name="velocitywp_options[memcached_servers]" rows="5" class="large-text code"><?php echo esc_textarea( $memcached_servers ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Enter one server per line in format host:port', 'velocitywp' ); ?><br>
						<?php esc_html_e( 'Example: 127.0.0.1:11211', 'velocitywp' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<button type="button" class="button" id="velocitywp-test-memcached">
						<?php esc_html_e( 'Test Memcached Connection', 'velocitywp' ); ?>
					</button>
					<span id="memcached-test-result" style="margin-left: 10px;"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<!-- APCu Settings -->
	<div id="apcu-settings" class="velocitywp-cache-settings" style="display: none;">
		<h3><?php esc_html_e( 'APCu Settings', 'velocitywp' ); ?></h3>
		<p><?php esc_html_e( 'APCu does not require any configuration. Just ensure it is installed and enabled on your server.', 'velocitywp' ); ?></p>
		<button type="button" class="button" id="velocitywp-test-apcu">
			<?php esc_html_e( 'Test APCu', 'velocitywp' ); ?>
		</button>
		<span id="apcu-test-result" style="margin-left: 10px;"></span>
	</div>
</div>

<!-- Installation Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Drop-in Management', 'velocitywp' ); ?></h2>
	
	<p class="description">
		<?php esc_html_e( 'The object cache drop-in file (wp-content/object-cache.php) needs to be installed to enable object caching.', 'velocitywp' ); ?>
	</p>
	
	<p>
		<?php if ( ! $is_installed ): ?>
			<button type="button" class="button button-primary" id="velocitywp-install-dropin">
				<?php esc_html_e( 'Install Object Cache', 'velocitywp' ); ?>
			</button>
		<?php else: ?>
			<button type="button" class="button button-secondary" id="velocitywp-remove-dropin">
				<?php esc_html_e( 'Remove Object Cache', 'velocitywp' ); ?>
			</button>
		<?php endif; ?>
		
		<button type="button" class="button" id="velocitywp-flush-cache" <?php echo ! $is_installed ? 'disabled' : ''; ?>>
			<?php esc_html_e( 'Flush Object Cache', 'velocitywp' ); ?>
		</button>
	</p>
	
	<div id="dropin-action-result" style="margin-top: 10px;"></div>
</div>

<!-- Installation Guide -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Server Installation Guide', 'velocitywp' ); ?></h2>
	
	<p><?php esc_html_e( 'If a cache backend is not available, you need to install it on your server first:', 'velocitywp' ); ?></p>
	
	<h3><?php esc_html_e( 'Redis', 'velocitywp' ); ?></h3>
	<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code># Ubuntu/Debian
sudo apt-get update
sudo apt-get install redis-server php-redis

# CentOS/RHEL
sudo yum install redis php-redis</code></pre>
	
	<h3><?php esc_html_e( 'Memcached', 'velocitywp' ); ?></h3>
	<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code># Ubuntu/Debian
sudo apt-get update
sudo apt-get install memcached php-memcached

# CentOS/RHEL
sudo yum install memcached php-memcached</code></pre>
	
	<h3><?php esc_html_e( 'APCu', 'velocitywp' ); ?></h3>
	<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code># Ubuntu/Debian
sudo apt-get update
sudo apt-get install php-apcu

# CentOS/RHEL
sudo yum install php-apcu</code></pre>
	
	<p><strong><?php esc_html_e( 'Note:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'After installing, restart your web server (Apache/Nginx) and PHP-FPM for the changes to take effect.', 'velocitywp' ); ?></p>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Show/hide settings based on cache type selection
	function toggleCacheSettings() {
		var cacheType = $('#velocitywp-cache-type').val();
		$('.velocitywp-cache-settings').hide();
		
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
	$('#velocitywp-cache-type').on('change', toggleCacheSettings);
	
	// Test Redis connection
	$('#velocitywp-test-redis').on('click', function() {
		var $button = $(this);
		var $result = $('#redis-test-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'velocitywp' ); ?>');
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'velocitywp_test_object_cache',
				backend: 'redis',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
				} else {
					$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection test failed', 'velocitywp' ); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Test Redis Connection', 'velocitywp' ); ?>');
			}
		});
	});
	
	// Test Memcached connection
	$('#velocitywp-test-memcached').on('click', function() {
		var $button = $(this);
		var $result = $('#memcached-test-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'velocitywp' ); ?>');
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'velocitywp_test_object_cache',
				backend: 'memcached',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
				} else {
					$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection test failed', 'velocitywp' ); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Test Memcached Connection', 'velocitywp' ); ?>');
			}
		});
	});
	
	// Test APCu
	$('#velocitywp-test-apcu').on('click', function() {
		var $button = $(this);
		var $result = $('#apcu-test-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'velocitywp' ); ?>');
		$result.html('');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'velocitywp_test_object_cache',
				backend: 'apcu',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
				} else {
					$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Test failed', 'velocitywp' ); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Test APCu', 'velocitywp' ); ?>');
			}
		});
	});
	
	// Install drop-in
	$('#velocitywp-install-dropin').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will create wp-content/object-cache.php. Continue?', 'velocitywp' ); ?>')) {
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
				action: 'velocitywp_install_dropin',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
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
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to install drop-in', 'velocitywp' ); ?></p></div>');
				$button.prop('disabled', false);
			}
		});
	});
	
	// Remove drop-in
	$('#velocitywp-remove-dropin').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will remove wp-content/object-cache.php. Continue?', 'velocitywp' ); ?>')) {
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
				action: 'velocitywp_remove_dropin',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
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
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to remove drop-in', 'velocitywp' ); ?></p></div>');
				$button.prop('disabled', false);
			}
		});
	});
	
	// Flush cache
	$('#velocitywp-flush-cache').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will clear all cached data. Continue?', 'velocitywp' ); ?>')) {
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
				action: 'velocitywp_flush_object_cache',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
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
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Failed to flush cache', 'velocitywp' ); ?></p></div>');
				$button.prop('disabled', false);
			}
		});
	});
	
	// Refresh stats
	$('#velocitywp-refresh-stats').on('click', function() {
		var $button = $(this);
		
		$button.prop('disabled', true);
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'velocitywp_get_cache_stats',
				nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp-admin-nonce' ) ); ?>'
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
