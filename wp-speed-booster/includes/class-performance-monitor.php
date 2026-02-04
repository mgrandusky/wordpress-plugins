<?php
/**
 * Performance Monitor Class
 *
 * Handles Real User Monitoring (RUM), Core Web Vitals tracking, and performance analytics
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Speed_Booster_Performance_Monitor class
 */
class WP_Speed_Booster_Performance_Monitor {

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Start time for server-side tracking
	 *
	 * @var float
	 */
	private $start_time;

	/**
	 * Start query count for server-side tracking
	 *
	 * @var int
	 */
	private $start_queries;

	/**
	 * Start memory for server-side tracking
	 *
	 * @var int
	 */
	private $start_memory;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'wpsb_options', array() );

		// Register hooks
		add_action( 'wp_footer', array( $this, 'inject_rum_script' ), 999 );
		add_action( 'wp_ajax_wpspeed_track_performance', array( $this, 'ajax_track_performance' ) );
		add_action( 'wp_ajax_nopriv_wpspeed_track_performance', array( $this, 'ajax_track_performance' ) );
		add_action( 'wp_ajax_wpspeed_get_analytics', array( $this, 'ajax_get_analytics' ) );
		add_action( 'wp_ajax_wpspeed_export_data', array( $this, 'ajax_export_data' ) );
		add_action( 'wp_ajax_wpspeed_cleanup_data', array( $this, 'ajax_cleanup_data' ) );

		// Server-side tracking hooks
		add_action( 'init', array( $this, 'start_tracking' ), 1 );
		add_action( 'wp_footer', array( $this, 'end_tracking' ), 9999 );
		add_action( 'admin_footer', array( $this, 'end_tracking' ), 9999 );

		// Scheduled cleanup
		add_action( 'wpspeed_cleanup_performance_data', array( $this, 'cleanup_old_data' ) );
	}

	/**
	 * Check if performance monitoring is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['performance_monitoring_enabled'] );
	}

	/**
	 * Create performance tracking database table
	 */
	public static function create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url varchar(255) NOT NULL,
			lcp int(11) DEFAULT NULL,
			fid int(11) DEFAULT NULL,
			cls float DEFAULT NULL,
			ttfb int(11) DEFAULT NULL,
			fcp int(11) DEFAULT NULL,
			dom_load int(11) DEFAULT NULL,
			window_load int(11) DEFAULT NULL,
			resource_count int(11) DEFAULT NULL,
			total_size bigint(20) DEFAULT NULL,
			device varchar(20) DEFAULT NULL,
			connection varchar(20) DEFAULT NULL,
			generation_time float DEFAULT NULL,
			query_count int(11) DEFAULT NULL,
			memory_used bigint(20) DEFAULT NULL,
			timestamp datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY url (url),
			KEY timestamp (timestamp),
			KEY device (device)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Inject Real User Monitoring (RUM) script
	 */
	public function inject_rum_script() {
		if ( is_admin() || ! $this->is_enabled() || empty( $this->settings['performance_track_rum'] ) ) {
			return;
		}

		// Sample rate check
		$sample_rate = ! empty( $this->settings['performance_sample_rate'] ) ? intval( $this->settings['performance_sample_rate'] ) : 100;
		if ( $sample_rate < 100 && mt_rand( 1, 100 ) > $sample_rate ) {
			return;
		}

		?>
		<script>
		(function() {
			'use strict';
			
			// Check for PerformanceObserver support
			if (!('PerformanceObserver' in window)) {
				return;
			}
			
			var metrics = {
				url: window.location.pathname,
				device: /mobile/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
				connection: navigator.connection ? navigator.connection.effectiveType : 'unknown'
			};
			
			var metricsSent = false;
			
			// Largest Contentful Paint (LCP)
			var lcpObserver = new PerformanceObserver(function(list) {
				var entries = list.getEntries();
				var lastEntry = entries[entries.length - 1];
				metrics.lcp = Math.round(lastEntry.renderTime || lastEntry.loadTime);
			});
			try {
				lcpObserver.observe({entryTypes: ['largest-contentful-paint']});
			} catch(e) {}
			
			// First Input Delay (FID)
			var fidObserver = new PerformanceObserver(function(list) {
				var entries = list.getEntries();
				entries.forEach(function(entry) {
					metrics.fid = Math.round(entry.processingStart - entry.startTime);
				});
			});
			try {
				fidObserver.observe({entryTypes: ['first-input']});
			} catch(e) {}
			
			// Cumulative Layout Shift (CLS)
			var clsValue = 0;
			var clsObserver = new PerformanceObserver(function(list) {
				list.getEntries().forEach(function(entry) {
					if (!entry.hadRecentInput) {
						clsValue += entry.value;
					}
				});
				metrics.cls = Math.round(clsValue * 1000) / 1000;
			});
			try {
				clsObserver.observe({entryTypes: ['layout-shift']});
			} catch(e) {}
			
			// Navigation Timing
			window.addEventListener('load', function() {
				setTimeout(function() {
					var navigation = performance.getEntriesByType('navigation')[0];
					
					if (navigation) {
						metrics.ttfb = Math.round(navigation.responseStart - navigation.requestStart);
						metrics.fcp = Math.round(navigation.responseEnd - navigation.requestStart);
						metrics.domLoad = Math.round(navigation.domContentLoadedEventEnd - navigation.fetchStart);
						metrics.windowLoad = Math.round(navigation.loadEventEnd - navigation.fetchStart);
						
						// Resource timing
						var resources = performance.getEntriesByType('resource');
						metrics.resourceCount = resources.length;
						
						var totalSize = 0;
						resources.forEach(function(resource) {
							totalSize += resource.transferSize || 0;
						});
						metrics.totalSize = totalSize;
					}
					
					// Send metrics to server
					sendMetrics();
				}, 2000);
			});
			
			function sendMetrics() {
				// Only send if we have meaningful data and haven't sent already
				if (metricsSent || (!metrics.lcp && !metrics.fid && !metrics.ttfb)) {
					return;
				}
				
				metricsSent = true;
				
				// Use sendBeacon for reliability
				if (navigator.sendBeacon) {
					var data = new FormData();
					data.append('action', 'wpspeed_track_performance');
					data.append('metrics', JSON.stringify(metrics));
					data.append('nonce', '<?php echo wp_create_nonce( 'wpspeed_track_performance' ); ?>');
					
					navigator.sendBeacon('<?php echo admin_url( 'admin-ajax.php' ); ?>', data);
				}
			}
			
			// Send on page unload if not sent yet
			window.addEventListener('visibilitychange', function() {
				if (document.visibilityState === 'hidden') {
					sendMetrics();
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * AJAX handler for tracking performance metrics
	 */
	public function ajax_track_performance() {
		check_ajax_referer( 'wpspeed_track_performance', 'nonce' );

		$metrics = isset( $_POST['metrics'] ) ? json_decode( stripslashes( $_POST['metrics'] ), true ) : array();

		if ( empty( $metrics ) ) {
			wp_send_json_error( array( 'message' => 'No metrics provided' ) );
		}

		// Store metrics
		$this->store_metrics( $metrics );

		wp_send_json_success();
	}

	/**
	 * Store performance metrics in database
	 *
	 * @param array $metrics Performance metrics.
	 */
	public function store_metrics( $metrics ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';

		$wpdb->insert(
			$table_name,
			array(
				'url'            => sanitize_text_field( $metrics['url'] ),
				'lcp'            => isset( $metrics['lcp'] ) ? intval( $metrics['lcp'] ) : null,
				'fid'            => isset( $metrics['fid'] ) ? intval( $metrics['fid'] ) : null,
				'cls'            => isset( $metrics['cls'] ) ? floatval( $metrics['cls'] ) : null,
				'ttfb'           => isset( $metrics['ttfb'] ) ? intval( $metrics['ttfb'] ) : null,
				'fcp'            => isset( $metrics['fcp'] ) ? intval( $metrics['fcp'] ) : null,
				'dom_load'       => isset( $metrics['domLoad'] ) ? intval( $metrics['domLoad'] ) : null,
				'window_load'    => isset( $metrics['windowLoad'] ) ? intval( $metrics['windowLoad'] ) : null,
				'resource_count' => isset( $metrics['resourceCount'] ) ? intval( $metrics['resourceCount'] ) : null,
				'total_size'     => isset( $metrics['totalSize'] ) ? intval( $metrics['totalSize'] ) : null,
				'device'         => sanitize_text_field( $metrics['device'] ),
				'connection'     => sanitize_text_field( $metrics['connection'] ),
				'timestamp'      => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Start server-side performance tracking
	 */
	public function start_tracking() {
		if ( ! $this->is_enabled() || empty( $this->settings['performance_track_server'] ) ) {
			return;
		}

		$this->start_time = microtime( true );
		$this->start_queries = get_num_queries();
		$this->start_memory = memory_get_usage();
	}

	/**
	 * End server-side performance tracking
	 */
	public function end_tracking() {
		if ( ! $this->is_enabled() || empty( $this->settings['performance_track_server'] ) ) {
			return;
		}

		if ( ! isset( $this->start_time ) ) {
			return;
		}

		$generation_time = microtime( true ) - $this->start_time;
		$query_count = get_num_queries() - $this->start_queries;
		$memory_used = memory_get_usage() - $this->start_memory;

		// Store server-side metrics
		$this->store_server_metrics(
			array(
				'generation_time' => $generation_time,
				'query_count'     => $query_count,
				'memory_used'     => $memory_used,
			)
		);

		// Add HTML comment with debug info
		if ( ! empty( $this->settings['performance_debug_comments'] ) ) {
			echo "\n<!-- WP Speed Booster Performance:\n";
			echo 'Generation Time: ' . round( $generation_time * 1000, 2 ) . "ms\n";
			echo 'Database Queries: ' . $query_count . "\n";
			echo 'Memory Used: ' . size_format( $memory_used ) . "\n";
			echo "-->\n";
		}
	}

	/**
	 * Store server-side performance metrics
	 *
	 * @param array $metrics Server metrics.
	 */
	public function store_server_metrics( $metrics ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';

		// Get current URL
		$url = $_SERVER['REQUEST_URI'];

		$wpdb->insert(
			$table_name,
			array(
				'url'              => sanitize_text_field( $url ),
				'generation_time'  => floatval( $metrics['generation_time'] ),
				'query_count'      => intval( $metrics['query_count'] ),
				'memory_used'      => intval( $metrics['memory_used'] ),
				'device'           => wp_is_mobile() ? 'mobile' : 'desktop',
				'timestamp'        => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get analytics data
	 *
	 * @param int $period Number of days to analyze.
	 * @return array Analytics data.
	 */
	public function get_analytics( $period = 7 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		// Average metrics
		$averages = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					AVG(lcp) as avg_lcp,
					AVG(fid) as avg_fid,
					AVG(cls) as avg_cls,
					AVG(ttfb) as avg_ttfb,
					AVG(fcp) as avg_fcp,
					AVG(dom_load) as avg_dom_load,
					AVG(window_load) as avg_window_load,
					AVG(generation_time) as avg_generation_time,
					AVG(query_count) as avg_query_count,
					AVG(memory_used) as avg_memory_used,
					COUNT(*) as total_samples
				FROM $table_name
				WHERE timestamp >= %s",
				$date_from
			)
		);

		// Device breakdown
		$device_breakdown = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					device,
					COUNT(*) as count,
					AVG(lcp) as avg_lcp,
					AVG(fid) as avg_fid
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY device",
				$date_from
			)
		);

		// Top slowest pages
		$slowest_pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					url,
					COUNT(*) as samples,
					AVG(lcp) as avg_lcp,
					AVG(window_load) as avg_load_time
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY url
				ORDER BY avg_load_time DESC
				LIMIT 10",
				$date_from
			)
		);

		// Daily trend
		$daily_trend = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					DATE(timestamp) as date,
					AVG(lcp) as avg_lcp,
					AVG(fid) as avg_fid,
					AVG(cls) as avg_cls,
					AVG(window_load) as avg_load_time
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY DATE(timestamp)
				ORDER BY date ASC",
				$date_from
			)
		);

		return array(
			'averages'         => $averages,
			'device_breakdown' => $device_breakdown,
			'slowest_pages'    => $slowest_pages,
			'daily_trend'      => $daily_trend,
		);
	}

	/**
	 * Get Core Web Vitals score
	 *
	 * @param float $lcp Largest Contentful Paint.
	 * @param float $fid First Input Delay.
	 * @param float $cls Cumulative Layout Shift.
	 * @return int Score (0-100).
	 */
	public function get_cwv_score( $lcp, $fid, $cls ) {
		$score = 0;

		// LCP scoring (0-40 points)
		if ( $lcp <= 2500 ) {
			$score += 40;
		} elseif ( $lcp <= 4000 ) {
			$score += 20;
		}

		// FID scoring (0-30 points)
		if ( $fid <= 100 ) {
			$score += 30;
		} elseif ( $fid <= 300 ) {
			$score += 15;
		}

		// CLS scoring (0-30 points)
		if ( $cls <= 0.1 ) {
			$score += 30;
		} elseif ( $cls <= 0.25 ) {
			$score += 15;
		}

		return $score;
	}

	/**
	 * Get Core Web Vitals rating
	 *
	 * @param float  $value Metric value.
	 * @param string $metric Metric type.
	 * @return string Rating (good|needs-improvement|poor).
	 */
	public function get_cwv_rating( $value, $metric ) {
		switch ( $metric ) {
			case 'lcp':
				if ( $value <= 2500 ) {
					return 'good';
				}
				if ( $value <= 4000 ) {
					return 'needs-improvement';
				}
				return 'poor';

			case 'fid':
				if ( $value <= 100 ) {
					return 'good';
				}
				if ( $value <= 300 ) {
					return 'needs-improvement';
				}
				return 'poor';

			case 'cls':
				if ( $value <= 0.1 ) {
					return 'good';
				}
				if ( $value <= 0.25 ) {
					return 'needs-improvement';
				}
				return 'poor';

			case 'ttfb':
				if ( $value <= 800 ) {
					return 'good';
				}
				if ( $value <= 1800 ) {
					return 'needs-improvement';
				}
				return 'poor';
		}

		return 'unknown';
	}

	/**
	 * Get Core Web Vitals distribution
	 *
	 * @param int $period Number of days.
	 * @return array Distribution data.
	 */
	public function get_cwv_distribution( $period = 7 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		$distribution = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					CASE 
						WHEN lcp <= 2500 THEN 'good'
						WHEN lcp <= 4000 THEN 'needs-improvement'
						ELSE 'poor'
					END as lcp_rating,
					COUNT(*) as count
				FROM $table_name
				WHERE timestamp >= %s AND lcp IS NOT NULL
				GROUP BY lcp_rating",
				$date_from
			)
		);

		return $distribution;
	}

	/**
	 * Get daily trend data
	 *
	 * @param int $period Number of days.
	 * @return array Daily trend data.
	 */
	public function get_daily_trend( $period = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					DATE(timestamp) as date,
					AVG(lcp) as avg_lcp,
					AVG(fid) as avg_fid,
					AVG(cls) as avg_cls,
					AVG(ttfb) as avg_ttfb,
					AVG(window_load) as avg_load_time,
					COUNT(*) as samples
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY DATE(timestamp)
				ORDER BY date ASC",
				$date_from
			)
		);
	}

	/**
	 * Get device breakdown
	 *
	 * @param int $period Number of days.
	 * @return array Device breakdown data.
	 */
	public function get_device_breakdown( $period = 7 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					device,
					COUNT(*) as count,
					AVG(lcp) as avg_lcp,
					AVG(fid) as avg_fid,
					AVG(cls) as avg_cls,
					AVG(window_load) as avg_load_time
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY device",
				$date_from
			)
		);
	}

	/**
	 * Get slowest pages
	 *
	 * @param int $period Number of days.
	 * @param int $limit Number of results.
	 * @return array Slowest pages data.
	 */
	public function get_slowest_pages( $period = 7, $limit = 10 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					url,
					COUNT(*) as samples,
					AVG(lcp) as avg_lcp,
					AVG(window_load) as avg_load_time,
					AVG(generation_time) as avg_generation_time,
					AVG(query_count) as avg_query_count
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY url
				ORDER BY avg_load_time DESC
				LIMIT %d",
				$date_from,
				$limit
			)
		);
	}

	/**
	 * Get fastest pages
	 *
	 * @param int $period Number of days.
	 * @param int $limit Number of results.
	 * @return array Fastest pages data.
	 */
	public function get_fastest_pages( $period = 7, $limit = 10 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					url,
					COUNT(*) as samples,
					AVG(lcp) as avg_lcp,
					AVG(window_load) as avg_load_time
				FROM $table_name
				WHERE timestamp >= %s
				GROUP BY url
				ORDER BY avg_load_time ASC
				LIMIT %d",
				$date_from,
				$limit
			)
		);
	}

	/**
	 * Cleanup old performance data
	 */
	public function cleanup_old_data() {
		global $wpdb;

		$retention_days = ! empty( $this->settings['performance_data_retention'] ) ? intval( $this->settings['performance_data_retention'] ) : 30;
		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE timestamp < %s",
				$date_threshold
			)
		);
	}

	/**
	 * Export performance data
	 *
	 * @param string $format Export format (csv|json).
	 * @param int    $period Number of days.
	 * @return string Exported data.
	 */
	public function export_data( $format = 'csv', $period = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpspeed_performance';
		$date_from = date( 'Y-m-d H:i:s', strtotime( "-{$period} days" ) );

		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE timestamp >= %s ORDER BY timestamp DESC",
				$date_from
			),
			ARRAY_A
		);

		if ( 'json' === $format ) {
			return wp_json_encode( $data );
		}

		// CSV format
		$csv = '';
		if ( ! empty( $data ) ) {
			// Headers
			$csv .= implode( ',', array_keys( $data[0] ) ) . "\n";

			// Data rows
			foreach ( $data as $row ) {
				$csv .= implode( ',', $row ) . "\n";
			}
		}

		return $csv;
	}

	/**
	 * AJAX handler for getting analytics data
	 */
	public function ajax_get_analytics() {
		check_ajax_referer( 'wpspeed_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$period = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 7;
		$analytics = $this->get_analytics( $period );

		wp_send_json_success( $analytics );
	}

	/**
	 * AJAX handler for exporting data
	 */
	public function ajax_export_data() {
		check_ajax_referer( 'wpspeed_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'csv';
		$period = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 30;

		$data = $this->export_data( $format, $period );

		wp_send_json_success(
			array(
				'data'   => $data,
				'format' => $format,
			)
		);
	}

	/**
	 * AJAX handler for cleaning up old data
	 */
	public function ajax_cleanup_data() {
		check_ajax_referer( 'wpspeed_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$this->cleanup_old_data();

		wp_send_json_success( array( 'message' => 'Old performance data cleaned up successfully' ) );
	}
}
