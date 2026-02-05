<?php
/**
 * Dashboard Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get statistics
$cache = new VelocityWP_Cache();
$cache_stats = $cache->get_cache_stats();

$db_optimizer = new VelocityWP_Database_Optimizer();
$db_stats = $db_optimizer->get_stats();
$db_size = $db_optimizer->get_database_size();

// WebP stats - safe defaults
$webp_stats = array(
	'total' => 0,
	'converted' => 0,
	'savings' => 0,
	'savings_formatted' => '0 B'
);

// Performance metrics - safe defaults
$metrics = array(
	'avg_load_time' => 0,
	'avg_lcp' => 0,
	'avg_fid' => 0,
	'avg_cls' => 0
);

// Try to get performance analytics if enabled
if ( VelocityWP_Admin::get_setting( 'performance_monitoring_enabled' ) ) {
	try {
		$performance_monitor = new VelocityWP_Performance_Monitor();
		$analytics = $performance_monitor->get_analytics( 7 );
		if ( ! empty( $analytics ) ) {
			$metrics = array(
				'avg_load_time' => isset( $analytics['avg_page_load'] ) ? $analytics['avg_page_load'] / 1000 : 0,
				'avg_lcp' => isset( $analytics['avg_lcp'] ) ? $analytics['avg_lcp'] / 1000 : 0,
				'avg_fid' => isset( $analytics['avg_fid'] ) ? $analytics['avg_fid'] : 0,
				'avg_cls' => isset( $analytics['avg_cls'] ) ? $analytics['avg_cls'] : 0
			);
		}
	} catch ( Exception $e ) {
		// Silently fail if performance monitor is not available
	}
}

// Object cache status
$object_cache_status = array(
	'connected' => false,
	'backend' => __( 'None', 'velocitywp' )
);

try {
	$object_cache = new VelocityWP_Object_Cache();
	$cache_type = $object_cache->detect_cache_type();
	if ( $cache_type && $cache_type !== 'none' ) {
		$object_cache_status = array(
			'connected' => true,
			'backend' => ucfirst( $cache_type )
		);
	}
} catch ( Exception $e ) {
	// Silently fail
}

// WebP support check - use simple PHP check
$webp_support = function_exists( 'imagewebp' );

// Get recent activity
$recent_activity = VelocityWP_Activity_Logger::get_recent( 10 );
?>

<div class="velocitywp-dashboard">
	<!-- Welcome Header -->
	<div class="velocitywp-dashboard-header">
		<h1><?php esc_html_e( 'VelocityWP Dashboard', 'velocitywp' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Monitor your site performance and manage optimizations from one place.', 'velocitywp' ); ?></p>
	</div>

	<!-- Stats Grid -->
	<div class="velocitywp-stats-grid">
		<!-- Cache Stats Card -->
		<div class="velocitywp-stat-card">
			<div class="stat-card-header">
				<span class="stat-icon">🗄️</span>
				<h3><?php esc_html_e( 'Page Cache', 'velocitywp' ); ?></h3>
			</div>
			<div class="stat-card-body">
				<div class="stat-primary">
					<span class="stat-value"><?php echo esc_html( $cache_stats['files'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Cached Pages', 'velocitywp' ); ?></span>
				</div>
				<div class="stat-secondary">
					<div class="stat-item">
						<span class="stat-label"><?php esc_html_e( 'Cache Size:', 'velocitywp' ); ?></span>
						<span class="stat-value"><?php echo esc_html( size_format( $cache_stats['size'] ) ); ?></span>
					</div>
					<div class="stat-item">
						<span class="stat-label"><?php esc_html_e( 'Status:', 'velocitywp' ); ?></span>
						<span class="stat-value"><?php echo VelocityWP_Admin::get_setting( 'cache_enabled' ) ? esc_html__( 'Active', 'velocitywp' ) : esc_html__( 'Inactive', 'velocitywp' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Performance Card -->
		<div class="velocitywp-stat-card">
			<div class="stat-card-header">
				<span class="stat-icon">⚡</span>
				<h3><?php esc_html_e( 'Performance', 'velocitywp' ); ?></h3>
			</div>
			<div class="stat-card-body">
				<div class="stat-primary">
					<span class="stat-value"><?php echo esc_html( number_format( $metrics['avg_load_time'], 2 ) ); ?>s</span>
					<span class="stat-label"><?php esc_html_e( 'Avg Load Time', 'velocitywp' ); ?></span>
				</div>
				<div class="stat-secondary">
					<div class="stat-item">
						<span class="stat-label">LCP:</span>
						<span class="stat-value <?php echo $metrics['avg_lcp'] > 0 && $metrics['avg_lcp'] < 2.5 ? 'good' : 'needs-improvement'; ?>">
							<?php echo esc_html( number_format( $metrics['avg_lcp'], 2 ) ); ?>s
						</span>
					</div>
					<div class="stat-item">
						<span class="stat-label">CLS:</span>
						<span class="stat-value <?php echo $metrics['avg_cls'] < 0.1 ? 'good' : 'needs-improvement'; ?>">
							<?php echo esc_html( number_format( $metrics['avg_cls'], 3 ) ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>

		<!-- Database Card -->
		<div class="velocitywp-stat-card">
			<div class="stat-card-header">
				<span class="stat-icon">💾</span>
				<h3><?php esc_html_e( 'Database', 'velocitywp' ); ?></h3>
			</div>
			<div class="stat-card-body">
				<div class="stat-primary">
					<span class="stat-value"><?php echo esc_html( $db_size['size_formatted'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Database Size', 'velocitywp' ); ?></span>
				</div>
				<div class="stat-secondary">
					<div class="stat-item">
						<span class="stat-label"><?php esc_html_e( 'Revisions:', 'velocitywp' ); ?></span>
						<span class="stat-value"><?php echo esc_html( $db_stats['revisions'] ); ?></span>
					</div>
					<div class="stat-item">
						<span class="stat-label"><?php esc_html_e( 'Transients:', 'velocitywp' ); ?></span>
						<span class="stat-value"><?php echo esc_html( $db_stats['expired_transients'] ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Images Card -->
		<div class="velocitywp-stat-card">
			<div class="stat-card-header">
				<span class="stat-icon">🖼️</span>
				<h3><?php esc_html_e( 'Images', 'velocitywp' ); ?></h3>
			</div>
			<div class="stat-card-body">
				<div class="stat-primary">
					<span class="stat-value"><?php echo esc_html( $webp_stats['converted'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'WebP Images', 'velocitywp' ); ?></span>
				</div>
				<div class="stat-secondary">
					<div class="stat-item">
						<span class="stat-label"><?php esc_html_e( 'WebP:', 'velocitywp' ); ?></span>
						<span class="stat-value"><?php echo $webp_support ? esc_html__( 'Supported', 'velocitywp' ) : esc_html__( 'Not Supported', 'velocitywp' ); ?></span>
					</div>
					<div class="stat-item">
						<span class="stat-label"><?php esc_html_e( 'Savings:', 'velocitywp' ); ?></span>
						<span class="stat-value"><?php echo esc_html( $webp_stats['savings_formatted'] ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Actions Section -->
	<div class="velocitywp-quick-actions">
		<h2><?php esc_html_e( 'Quick Actions', 'velocitywp' ); ?></h2>
		
		<div class="quick-actions-grid">
			<button type="button" class="velocitywp-action-btn velocitywp-action-primary" id="clear-page-cache">
				<span class="action-icon">🗑️</span>
				<span class="action-text">
					<strong><?php esc_html_e( 'Clear Page Cache', 'velocitywp' ); ?></strong>
					<small><?php esc_html_e( 'Clear all cached HTML pages', 'velocitywp' ); ?></small>
				</span>
			</button>

			<button type="button" class="velocitywp-action-btn" id="clear-object-cache">
				<span class="action-icon">💨</span>
				<span class="action-text">
					<strong><?php esc_html_e( 'Flush Object Cache', 'velocitywp' ); ?></strong>
					<small><?php esc_html_e( 'Clear Redis/Memcached', 'velocitywp' ); ?></small>
				</span>
			</button>

			<button type="button" class="velocitywp-action-btn" id="clear-fragment-cache">
				<span class="action-icon">🧩</span>
				<span class="action-text">
					<strong><?php esc_html_e( 'Clear Fragment Cache', 'velocitywp' ); ?></strong>
					<small><?php esc_html_e( 'Clear widgets & menus', 'velocitywp' ); ?></small>
				</span>
			</button>

			<button type="button" class="velocitywp-action-btn" id="optimize-database">
				<span class="action-icon">⚙️</span>
				<span class="action-text">
					<strong><?php esc_html_e( 'Optimize Database', 'velocitywp' ); ?></strong>
					<small><?php esc_html_e( 'Clean & optimize tables', 'velocitywp' ); ?></small>
				</span>
			</button>

			<button type="button" class="velocitywp-action-btn" id="regenerate-critical-css">
				<span class="action-icon">🎨</span>
				<span class="action-text">
					<strong><?php esc_html_e( 'Regenerate Critical CSS', 'velocitywp' ); ?></strong>
					<small><?php esc_html_e( 'Rebuild critical CSS', 'velocitywp' ); ?></small>
				</span>
			</button>

			<?php if ( VelocityWP_Admin::get_setting( 'cloudflare_enabled' ) ): ?>
			<button type="button" class="velocitywp-action-btn" id="purge-cloudflare">
				<span class="action-icon">☁️</span>
				<span class="action-text">
					<strong><?php esc_html_e( 'Purge Cloudflare', 'velocitywp' ); ?></strong>
					<small><?php esc_html_e( 'Clear CDN cache', 'velocitywp' ); ?></small>
				</span>
			</button>
			<?php endif; ?>
		</div>

		<div id="action-result"></div>
	</div>

	<!-- System Status Section -->
	<div class="velocitywp-system-status">
		<h2><?php esc_html_e( 'System Status', 'velocitywp' ); ?></h2>
		
		<div class="status-grid">
			<div class="status-item <?php echo VelocityWP_Admin::get_setting( 'cache_enabled' ) ? 'status-active' : 'status-inactive'; ?>">
				<span class="status-indicator"></span>
				<span class="status-label"><?php esc_html_e( 'Page Cache', 'velocitywp' ); ?></span>
				<span class="status-value"><?php echo VelocityWP_Admin::get_setting( 'cache_enabled' ) ? esc_html__( 'Active', 'velocitywp' ) : esc_html__( 'Inactive', 'velocitywp' ); ?></span>
			</div>

			<div class="status-item <?php echo $object_cache_status['connected'] ? 'status-active' : 'status-inactive'; ?>">
				<span class="status-indicator"></span>
				<span class="status-label"><?php esc_html_e( 'Object Cache', 'velocitywp' ); ?></span>
				<span class="status-value"><?php echo esc_html( $object_cache_status['backend'] ); ?></span>
			</div>

			<div class="status-item <?php echo $webp_support ? 'status-active' : 'status-inactive'; ?>">
				<span class="status-indicator"></span>
				<span class="status-label"><?php esc_html_e( 'WebP Support', 'velocitywp' ); ?></span>
				<span class="status-value"><?php echo $webp_support ? esc_html__( 'Available', 'velocitywp' ) : esc_html__( 'Not Available', 'velocitywp' ); ?></span>
			</div>

			<div class="status-item <?php echo VelocityWP_Admin::get_setting( 'fragment_cache_enabled' ) ? 'status-active' : 'status-inactive'; ?>">
				<span class="status-indicator"></span>
				<span class="status-label"><?php esc_html_e( 'Fragment Cache', 'velocitywp' ); ?></span>
				<span class="status-value"><?php echo VelocityWP_Admin::get_setting( 'fragment_cache_enabled' ) ? esc_html__( 'Active', 'velocitywp' ) : esc_html__( 'Inactive', 'velocitywp' ); ?></span>
			</div>

			<div class="status-item <?php echo VelocityWP_Admin::get_setting( 'cloudflare_enabled' ) ? 'status-active' : 'status-inactive'; ?>">
				<span class="status-indicator"></span>
				<span class="status-label"><?php esc_html_e( 'Cloudflare', 'velocitywp' ); ?></span>
				<span class="status-value"><?php echo VelocityWP_Admin::get_setting( 'cloudflare_enabled' ) ? esc_html__( 'Connected', 'velocitywp' ) : esc_html__( 'Not Connected', 'velocitywp' ); ?></span>
			</div>

			<div class="status-item <?php echo VelocityWP_Admin::get_setting( 'performance_monitoring_enabled' ) ? 'status-active' : 'status-inactive'; ?>">
				<span class="status-indicator"></span>
				<span class="status-label"><?php esc_html_e( 'Performance Monitoring', 'velocitywp' ); ?></span>
				<span class="status-value"><?php echo VelocityWP_Admin::get_setting( 'performance_monitoring_enabled' ) ? esc_html__( 'Active', 'velocitywp' ) : esc_html__( 'Inactive', 'velocitywp' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Recent Activity Section -->
	<?php if ( ! empty( $recent_activity ) ): ?>
	<div class="velocitywp-recent-activity">
		<h2><?php esc_html_e( 'Recent Activity', 'velocitywp' ); ?></h2>
		
		<ul class="activity-list">
			<?php foreach ( array_reverse( $recent_activity ) as $activity ): ?>
			<li class="activity-item">
				<span class="activity-icon"><?php echo esc_html( $activity['icon'] ); ?></span>
				<span class="activity-text"><?php echo esc_html( $activity['message'] ); ?></span>
				<span class="activity-time"><?php echo esc_html( human_time_diff( $activity['timestamp'], current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'velocitywp' ); ?></span>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var nonce = '<?php echo esc_js( wp_create_nonce( 'velocitywp_admin_nonce' ) ); ?>';
	
	function performAction(action, buttonId, message) {
		var $button = $('#' + buttonId);
		var $result = $('#action-result');
		
		$button.prop('disabled', true).addClass('loading');
		$result.html('<div class="notice notice-info inline"><p>' + message + '</p></div>');
		
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: action,
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					$result.html('<div class="notice notice-error inline"><p>' + (response.data.message || '<?php esc_html_e( 'Action failed', 'velocitywp' ); ?>') + '</p></div>');
				}
			},
			error: function() {
				$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Action failed', 'velocitywp' ); ?></p></div>');
			},
			complete: function() {
				$button.prop('disabled', false).removeClass('loading');
			}
		});
	}
	
	$('#clear-page-cache').on('click', function() {
		performAction('velocitywp_clear_cache', 'clear-page-cache', '<?php esc_html_e( 'Clearing page cache...', 'velocitywp' ); ?>');
	});
	
	$('#clear-object-cache').on('click', function() {
		performAction('velocitywp_flush_object_cache', 'clear-object-cache', '<?php esc_html_e( 'Flushing object cache...', 'velocitywp' ); ?>');
	});
	
	$('#clear-fragment-cache').on('click', function() {
		performAction('velocitywp_clear_fragment_cache', 'clear-fragment-cache', '<?php esc_html_e( 'Clearing fragment cache...', 'velocitywp' ); ?>');
	});
	
	$('#optimize-database').on('click', function() {
		if (confirm('<?php esc_html_e( 'Are you sure you want to optimize the database?', 'velocitywp' ); ?>')) {
			performAction('velocitywp_optimize_database', 'optimize-database', '<?php esc_html_e( 'Optimizing database...', 'velocitywp' ); ?>');
		}
	});
	
	$('#regenerate-critical-css').on('click', function() {
		performAction('velocitywp_regenerate_critical_css', 'regenerate-critical-css', '<?php esc_html_e( 'Regenerating critical CSS...', 'velocitywp' ); ?>');
	});
	
	$('#purge-cloudflare').on('click', function() {
		performAction('velocitywp_purge_cloudflare', 'purge-cloudflare', '<?php esc_html_e( 'Purging Cloudflare cache...', 'velocitywp' ); ?>');
	});
});
</script>
