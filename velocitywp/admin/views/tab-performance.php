<?php
/**
 * Performance Monitoring Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$performance_monitoring_enabled = ! empty( $options['performance_monitoring_enabled'] ) ? 1 : 0;
$performance_track_rum          = ! empty( $options['performance_track_rum'] ) ? 1 : 0;
$performance_track_server       = ! empty( $options['performance_track_server'] ) ? 1 : 0;
$performance_data_retention     = ! empty( $options['performance_data_retention'] ) ? intval( $options['performance_data_retention'] ) : 30;
$performance_debug_comments     = ! empty( $options['performance_debug_comments'] ) ? 1 : 0;
$performance_sample_rate        = ! empty( $options['performance_sample_rate'] ) ? intval( $options['performance_sample_rate'] ) : 100;

// Get and validate period parameter
$valid_periods = array( 1, 7, 30, 90 );
$period        = isset( $_GET['period'] ) ? intval( $_GET['period'] ) : 7;
if ( ! in_array( $period, $valid_periods, true ) ) {
	$period = 7;
}

// Initialize performance monitor if enabled
if ( $performance_monitoring_enabled && class_exists( 'VelocityWP_Performance_Monitor' ) ) {
	$monitor   = new VelocityWP_Performance_Monitor();
	$analytics = $monitor->get_analytics( $period );
} else {
	$analytics = array(
		'averages'         => null,
		'device_breakdown' => array(),
		'slowest_pages'    => array(),
		'daily_trend'      => array(),
	);
}

$averages = $analytics['averages'];
?>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Performance Monitoring & Analytics', 'velocitywp' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is Performance Monitoring?', 'velocitywp' ); ?></strong></p>
		<p><?php esc_html_e( 'Track real user experience metrics (Core Web Vitals) and server-side performance data to understand how your site performs for actual visitors. Get actionable insights to improve speed and user experience.', 'velocitywp' ); ?></p>
		<p><strong><?php esc_html_e( 'Features:', 'velocitywp' ); ?></strong></p>
		<ul>
			<li>📊 <?php esc_html_e( 'Core Web Vitals tracking (LCP, FID, CLS, TTFB)', 'velocitywp' ); ?></li>
			<li>🔍 <?php esc_html_e( 'Real User Monitoring (RUM)', 'velocitywp' ); ?></li>
			<li>⚡ <?php esc_html_e( 'Server performance metrics', 'velocitywp' ); ?></li>
			<li>📈 <?php esc_html_e( 'Historical trends and analytics', 'velocitywp' ); ?></li>
			<li>📱 <?php esc_html_e( 'Device breakdown (Mobile vs Desktop)', 'velocitywp' ); ?></li>
		</ul>
	</div>
</div>

<!-- Master Settings -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Enable/Disable', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Performance Monitoring', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[performance_monitoring_enabled]" value="1" <?php checked( $performance_monitoring_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable performance monitoring and analytics', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Master switch for all performance monitoring features', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Track Real User Metrics', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[performance_track_rum]" value="1" <?php checked( $performance_track_rum, 1 ); ?>>
					<?php esc_html_e( 'Enable Real User Monitoring (RUM)', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Track actual user experience metrics like LCP, FID, CLS from real visitors', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Track Server Performance', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[performance_track_server]" value="1" <?php checked( $performance_track_server, 1 ); ?>>
					<?php esc_html_e( 'Track server-side performance metrics', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Monitor generation time, database queries, and memory usage', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Debug Comments', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[performance_debug_comments]" value="1" <?php checked( $performance_debug_comments, 1 ); ?>>
					<?php esc_html_e( 'Add debug comments to HTML', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Include performance info as HTML comments (visible in page source)', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Data Settings -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Data Management', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Data Retention', 'velocitywp' ); ?></th>
			<td>
				<input type="number" name="velocitywp_options[performance_data_retention]" value="<?php echo esc_attr( $performance_data_retention ); ?>" min="1" max="365" class="small-text">
				<?php esc_html_e( 'days', 'velocitywp' ); ?>
				<p class="description"><?php esc_html_e( 'Keep performance data for this many days (older data will be automatically deleted)', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Sample Rate', 'velocitywp' ); ?></th>
			<td>
				<input type="number" name="velocitywp_options[performance_sample_rate]" value="<?php echo esc_attr( $performance_sample_rate ); ?>" min="1" max="100" class="small-text">
				<?php esc_html_e( '%', 'velocitywp' ); ?>
				<p class="description"><?php esc_html_e( 'Track this percentage of pageviews (100% = track all, 50% = track half, reduces database load)', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Cleanup Data', 'velocitywp' ); ?></th>
			<td>
				<button type="button" class="button" id="velocitywp-cleanup-performance-data">
					<?php esc_html_e( 'Clean Up Old Data Now', 'velocitywp' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Manually remove performance data older than the retention period', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<?php if ( $performance_monitoring_enabled && $averages && $averages->total_samples > 0 ) : ?>

<!-- Time Period Selector -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Performance Dashboard', 'velocitywp' ); ?></h2>
	
	<div style="margin-bottom: 20px;">
		<label for="performance-period-selector"><strong><?php esc_html_e( 'Time Period:', 'velocitywp' ); ?></strong></label>
		<select id="performance-period-selector" onchange="window.location.href='<?php echo esc_url( admin_url( 'options-general.php?page=velocitywp&tab=performance&period=' ) ); ?>' + this.value;">
			<option value="1" <?php selected( $period, 1 ); ?>><?php esc_html_e( 'Last 24 Hours', 'velocitywp' ); ?></option>
			<option value="7" <?php selected( $period, 7 ); ?>><?php esc_html_e( 'Last 7 Days', 'velocitywp' ); ?></option>
			<option value="30" <?php selected( $period, 30 ); ?>><?php esc_html_e( 'Last 30 Days', 'velocitywp' ); ?></option>
			<option value="90" <?php selected( $period, 90 ); ?>><?php esc_html_e( 'Last 90 Days', 'velocitywp' ); ?></option>
		</select>
		<span style="margin-left: 20px;"><strong><?php esc_html_e( 'Total Samples:', 'velocitywp' ); ?></strong> <?php echo number_format( $averages->total_samples ); ?></span>
	</div>
</div>

<!-- Core Web Vitals Cards -->
<div class="velocitywp-tab-section">
	<h2>
		<?php
		/* translators: %d: Number of days for the time period */
		echo esc_html( sprintf( __( 'Core Web Vitals (Last %d Days)', 'velocitywp' ), $period ) );
		?>
	</h2>
	
	<div class="velocitywp-cwv-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
		
		<!-- LCP Card -->
		<?php
		$lcp_value  = round( $averages->avg_lcp );
		$lcp_rating = $monitor->get_cwv_rating( $lcp_value, 'lcp' );
		$lcp_class  = 'velocitywp-metric-' . $lcp_rating;
		?>
		<div class="velocitywp-metric-card <?php echo esc_attr( $lcp_class ); ?>" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid;">
			<h3 style="margin: 0 0 10px 0; color: #666;">LCP</h3>
			<div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format( $lcp_value ); ?>ms</div>
			<div style="text-transform: uppercase; font-weight: bold; font-size: 12px; letter-spacing: 1px;">
				<?php echo esc_html( str_replace( '-', ' ', $lcp_rating ) ); ?>
			</div>
			<p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">Largest Contentful Paint</p>
		</div>
		
		<!-- FID Card -->
		<?php
		$fid_value  = round( $averages->avg_fid );
		$fid_rating = $monitor->get_cwv_rating( $fid_value, 'fid' );
		$fid_class  = 'velocitywp-metric-' . $fid_rating;
		?>
		<div class="velocitywp-metric-card <?php echo esc_attr( $fid_class ); ?>" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid;">
			<h3 style="margin: 0 0 10px 0; color: #666;">FID</h3>
			<div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format( $fid_value ); ?>ms</div>
			<div style="text-transform: uppercase; font-weight: bold; font-size: 12px; letter-spacing: 1px;">
				<?php echo esc_html( str_replace( '-', ' ', $fid_rating ) ); ?>
			</div>
			<p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">First Input Delay</p>
		</div>
		
		<!-- CLS Card -->
		<?php
		$cls_value  = round( $averages->avg_cls, 3 );
		$cls_rating = $monitor->get_cwv_rating( $cls_value, 'cls' );
		$cls_class  = 'velocitywp-metric-' . $cls_rating;
		?>
		<div class="velocitywp-metric-card <?php echo esc_attr( $cls_class ); ?>" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid;">
			<h3 style="margin: 0 0 10px 0; color: #666;">CLS</h3>
			<div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format( $cls_value, 3 ); ?></div>
			<div style="text-transform: uppercase; font-weight: bold; font-size: 12px; letter-spacing: 1px;">
				<?php echo esc_html( str_replace( '-', ' ', $cls_rating ) ); ?>
			</div>
			<p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">Cumulative Layout Shift</p>
		</div>
		
		<!-- Overall Score Card -->
		<?php
		$overall_score = $monitor->get_cwv_score( $lcp_value, $fid_value, $cls_value );
		$score_rating  = $overall_score >= 80 ? 'good' : ( $overall_score >= 50 ? 'needs-improvement' : 'poor' );
		$score_class   = 'velocitywp-metric-' . $score_rating;
		?>
		<div class="velocitywp-metric-card <?php echo esc_attr( $score_class ); ?>" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid;">
			<h3 style="margin: 0 0 10px 0; color: #666;">Performance Score</h3>
			<div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo $overall_score; ?>/100</div>
			<div style="text-transform: uppercase; font-weight: bold; font-size: 12px; letter-spacing: 1px;">
				<?php echo esc_html( str_replace( '-', ' ', $score_rating ) ); ?>
			</div>
			<p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">Overall Score</p>
		</div>
		
	</div>
</div>

<!-- Quick Stats Grid -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Quick Stats', 'velocitywp' ); ?></h2>
	
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
		<div style="background: white; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="color: #666; font-size: 12px; margin-bottom: 5px;">Avg Page Load</div>
			<div style="font-size: 24px; font-weight: bold;"><?php echo round( $averages->avg_window_load / 1000, 2 ); ?>s</div>
		</div>
		<div style="background: white; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="color: #666; font-size: 12px; margin-bottom: 5px;">Avg TTFB</div>
			<div style="font-size: 24px; font-weight: bold;"><?php echo round( $averages->avg_ttfb ); ?>ms</div>
		</div>
		<?php if ( $averages->avg_query_count ) : ?>
		<div style="background: white; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="color: #666; font-size: 12px; margin-bottom: 5px;">Avg DB Queries</div>
			<div style="font-size: 24px; font-weight: bold;"><?php echo round( $averages->avg_query_count ); ?></div>
		</div>
		<?php endif; ?>
		<?php if ( $averages->avg_generation_time ) : ?>
		<div style="background: white; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="color: #666; font-size: 12px; margin-bottom: 5px;">Avg Generation Time</div>
			<div style="font-size: 24px; font-weight: bold;"><?php echo round( $averages->avg_generation_time * 1000 ); ?>ms</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- Performance Trend Chart -->
<?php if ( ! empty( $analytics['daily_trend'] ) ) : ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Performance Trend', 'velocitywp' ); ?></h2>
	
	<canvas id="velocitywp-performance-chart" width="400" height="150"></canvas>
	
	<script>
	(function() {
		var ctx = document.getElementById('velocitywp-performance-chart');
		if (!ctx) return;
		
		var dates = <?php echo wp_json_encode( array_column( $analytics['daily_trend'], 'date' ) ); ?>;
		var lcpData = <?php echo wp_json_encode( array_map( 'floatval', array_column( $analytics['daily_trend'], 'avg_lcp' ) ) ); ?>;
		var fidData = <?php echo wp_json_encode( array_map( 'floatval', array_column( $analytics['daily_trend'], 'avg_fid' ) ) ); ?>;
		<?php
		// Scale CLS values for better chart visibility
		$cls_scaled = array_map(
			function( $v ) {
				return floatval( $v ) * 1000;
			},
			array_column( $analytics['daily_trend'], 'avg_cls' )
		);
		?>
		var clsData = <?php echo wp_json_encode( $cls_scaled ); ?>;
		
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: dates,
				datasets: [
					{
						label: 'LCP (ms)',
						data: lcpData,
						borderColor: '#4285f4',
						backgroundColor: 'rgba(66, 133, 244, 0.1)',
						tension: 0.3
					},
					{
						label: 'FID (ms)',
						data: fidData,
						borderColor: '#34a853',
						backgroundColor: 'rgba(52, 168, 83, 0.1)',
						tension: 0.3
					},
					{
						label: 'CLS (×1000)',
						data: clsData,
						borderColor: '#fbbc04',
						backgroundColor: 'rgba(251, 188, 4, 0.1)',
						tension: 0.3
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: 'Core Web Vitals Over Time'
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						title: {
							display: true,
							text: 'Milliseconds'
						}
					}
				}
			}
		});
	})();
	</script>
</div>
<?php endif; ?>

<!-- Device Breakdown -->
<?php if ( ! empty( $analytics['device_breakdown'] ) ) : ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Device Breakdown', 'velocitywp' ); ?></h2>
	
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Device', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Samples', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Avg LCP', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Avg FID', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Percentage', 'velocitywp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $analytics['device_breakdown'] as $device ) : ?>
				<?php $percentage = ( $device->count / $averages->total_samples ) * 100; ?>
				<tr>
					<td><strong><?php echo esc_html( ucfirst( $device->device ) ); ?></strong></td>
					<td><?php echo number_format( $device->count ); ?></td>
					<td><?php echo round( $device->avg_lcp ); ?>ms</td>
					<td><?php echo round( $device->avg_fid ); ?>ms</td>
					<td><?php echo round( $percentage, 1 ); ?>%</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>

<!-- Slowest Pages -->
<?php if ( ! empty( $analytics['slowest_pages'] ) ) : ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Slowest Pages', 'velocitywp' ); ?></h2>
	
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'URL', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Samples', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Avg LCP', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Avg Load Time', 'velocitywp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $analytics['slowest_pages'] as $page ) : ?>
				<tr>
					<td><code><?php echo esc_html( $page->url ); ?></code></td>
					<td><?php echo number_format( $page->samples ); ?></td>
					<td><?php echo round( $page->avg_lcp ); ?>ms</td>
					<td><?php echo round( $page->avg_load_time / 1000, 2 ); ?>s</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>

<!-- Recommendations -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Recommendations', 'velocitywp' ); ?></h2>
	
	<?php
	$recommendations = array();
	
	if ( $lcp_value > 2500 ) {
		$recommendations[] = array(
			'icon'  => '⚠️',
			'title' => 'LCP is ' . ( $lcp_rating === 'poor' ? 'Poor' : 'Needs Improvement' ),
			'desc'  => 'Largest Contentful Paint is ' . number_format( $lcp_value ) . 'ms (target: < 2500ms)',
			'tips'  => array(
				'Enable image optimization and lazy loading',
				'Use Critical CSS to prioritize above-the-fold content',
				'Optimize your largest image or video',
				'Enable page caching',
				'Use a CDN for faster asset delivery',
			),
		);
	}
	
	if ( $fid_value > 100 ) {
		$recommendations[] = array(
			'icon'  => '⚠️',
			'title' => 'FID is ' . ( $fid_rating === 'poor' ? 'Poor' : 'Needs Improvement' ),
			'desc'  => 'First Input Delay is ' . number_format( $fid_value ) . 'ms (target: < 100ms)',
			'tips'  => array(
				'Defer or delay non-critical JavaScript',
				'Break up long JavaScript tasks',
				'Minimize main thread work',
				'Remove or optimize third-party scripts',
			),
		);
	}
	
	if ( $cls_value > 0.1 ) {
		$recommendations[] = array(
			'icon'  => '⚠️',
			'title' => 'CLS is ' . ( $cls_rating === 'poor' ? 'Poor' : 'Needs Improvement' ),
			'desc'  => 'Cumulative Layout Shift is ' . number_format( $cls_value, 3 ) . ' (target: < 0.1)',
			'tips'  => array(
				'Add width and height attributes to images and videos',
				'Reserve space for dynamic content and ads',
				'Avoid inserting content above existing content',
				'Use CSS aspect-ratio for responsive images',
			),
		);
	}
	
	if ( $averages->avg_query_count && $averages->avg_query_count > 50 ) {
		$recommendations[] = array(
			'icon'  => '💾',
			'title' => 'High Database Query Count',
			'desc'  => 'Average of ' . round( $averages->avg_query_count ) . ' queries per page',
			'tips'  => array(
				'Enable object caching (Redis/Memcached)',
				'Optimize slow database queries',
				'Enable fragment caching for widgets and menus',
				'Review and optimize plugins',
			),
		);
	}
	
	if ( empty( $recommendations ) ) {
		echo '<div class="notice notice-success"><p><strong>' . esc_html__( '✓ Excellent Performance!', 'velocitywp' ) . '</strong><br>';
		echo esc_html__( 'Your Core Web Vitals are in the good range. Keep monitoring to maintain optimal performance.', 'velocitywp' ) . '</p></div>';
	} else {
		foreach ( $recommendations as $rec ) {
			echo '<div class="notice notice-warning" style="padding: 15px;">';
			echo '<p><strong>' . esc_html( $rec['icon'] . ' ' . $rec['title'] ) . '</strong><br>';
			echo esc_html( $rec['desc'] ) . '</p>';
			echo '<ul style="margin-left: 20px;">';
			foreach ( $rec['tips'] as $tip ) {
				echo '<li>' . esc_html( $tip ) . '</li>';
			}
			echo '</ul>';
			echo '</div>';
		}
	}
	?>
</div>

<!-- Export Data -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Export Data', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Export Format', 'velocitywp' ); ?></th>
			<td>
				<button type="button" class="button" onclick="velocitywpExportPerformanceData('csv', <?php echo $period; ?>)">
					<?php esc_html_e( 'Export as CSV', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button" onclick="velocitywpExportPerformanceData('json', <?php echo $period; ?>)">
					<?php esc_html_e( 'Export as JSON', 'velocitywp' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Download performance data for external analysis', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<?php else : ?>

<div class="velocitywp-tab-section">
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'No Performance Data Yet', 'velocitywp' ); ?></strong></p>
		<p><?php esc_html_e( 'Enable performance monitoring above and visit your site to start collecting data. Data will appear here once visitors interact with your site.', 'velocitywp' ); ?></p>
	</div>
</div>

<?php endif; ?>

<style>
.velocitywp-metric-good {
	border-left-color: #0f9d58 !important;
}
.velocitywp-metric-good > div:nth-child(2) {
	color: #0f9d58;
}
.velocitywp-metric-good > div:nth-child(3) {
	color: #0f9d58;
}

.velocitywp-metric-needs-improvement {
	border-left-color: #ff9800 !important;
}
.velocitywp-metric-needs-improvement > div:nth-child(2) {
	color: #ff9800;
}
.velocitywp-metric-needs-improvement > div:nth-child(3) {
	color: #ff9800;
}

.velocitywp-metric-poor {
	border-left-color: #f44336 !important;
}
.velocitywp-metric-poor > div:nth-child(2) {
	color: #f44336;
}
.velocitywp-metric-poor > div:nth-child(3) {
	color: #f44336;
}
</style>

<script>
function velocitywpExportPerformanceData(format, period) {
	var data = new FormData();
	data.append('action', 'velocitywp_export_data');
	data.append('format', format);
	data.append('period', period);
	data.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'velocitywp_admin' ) ); ?>);
	
	fetch(<?php echo wp_json_encode( esc_url( admin_url( 'admin-ajax.php' ) ) ); ?>, {
		method: 'POST',
		body: data
	})
	.then(response => response.json())
	.then(result => {
		if (result.success) {
			var blob = new Blob([result.data.data], {type: format === 'json' ? 'application/json' : 'text/csv'});
			var url = window.URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = 'performance-data-' + period + 'days.' + format;
			document.body.appendChild(a);
			a.click();
			window.URL.revokeObjectURL(url);
			document.body.removeChild(a);
		} else {
			alert('Error exporting data: ' + result.data.message);
		}
	});
}

jQuery(document).ready(function($) {
	$('#velocitywp-cleanup-performance-data').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to delete old performance data? This cannot be undone.', 'velocitywp' ); ?>')) {
			return;
		}
		
		var button = $(this);
		button.prop('disabled', true).text('<?php esc_html_e( 'Cleaning up...', 'velocitywp' ); ?>');
		
		$.ajax({
			url: <?php echo wp_json_encode( esc_url( admin_url( 'admin-ajax.php' ) ) ); ?>,
			type: 'POST',
			data: {
				action: 'velocitywp_cleanup_data',
				nonce: <?php echo wp_json_encode( wp_create_nonce( 'velocitywp_admin' ) ); ?>
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Old performance data cleaned up successfully!', 'velocitywp' ); ?>');
					location.reload();
				} else {
					alert('<?php esc_html_e( 'Error cleaning up data', 'velocitywp' ); ?>');
				}
			},
			complete: function() {
				button.prop('disabled', false).text('<?php esc_html_e( 'Clean Up Old Data Now', 'velocitywp' ); ?>');
			}
		});
	});
});
</script>
