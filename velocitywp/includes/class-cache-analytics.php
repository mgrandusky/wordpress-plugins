<?php
/**
 * Cache Analytics Class
 *
 * Cache hit/miss tracking and analytics
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Cache_Analytics class
 */
class VelocityWP_Cache_Analytics {

	/**
	 * Analytics data
	 *
	 * @var array
	 */
	private $analytics = array();

	/**
	 * Current page load start time
	 *
	 * @var float
	 */
	private $page_load_start = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->page_load_start = microtime( true );
		
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_get_cache_stats', array( $this, 'ajax_get_cache_stats' ) );
		add_action( 'wp_ajax_velocitywp_reset_cache_stats', array( $this, 'ajax_reset_cache_stats' ) );
	}

	/**
	 * Initialize cache analytics
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['cache_analytics'] ) ) {
			return;
		}

		// Track cache hits/misses
		add_action( 'velocitywp_cache_hit', array( $this, 'track_cache_hit' ) );
		add_action( 'velocitywp_cache_miss', array( $this, 'track_cache_miss' ) );

		// Track page generation time
		add_action( 'shutdown', array( $this, 'track_page_generation' ), 999 );

		// Show analytics badge for admins
		if ( ! empty( $options['show_cache_badge'] ) && current_user_can( 'manage_options' ) ) {
			add_action( 'wp_footer', array( $this, 'show_cache_badge' ), 999 );
		}

		// Periodic cleanup
		if ( wp_doing_cron() ) {
			add_action( 'velocitywp_cleanup_analytics', array( $this, 'cleanup_old_data' ) );
		}
	}

	/**
	 * Track cache hit
	 */
	public function track_cache_hit() {
		$this->record_event( 'hit' );
	}

	/**
	 * Track cache miss
	 */
	public function track_cache_miss() {
		$this->record_event( 'miss' );
	}

	/**
	 * Record cache event
	 *
	 * @param string $type Event type (hit/miss).
	 */
	private function record_event( $type ) {
		$stats = $this->get_stats();

		$today = current_time( 'Y-m-d' );

		if ( ! isset( $stats['daily'][ $today ] ) ) {
			$stats['daily'][ $today ] = array(
				'hits'   => 0,
				'misses' => 0,
			);
		}

		if ( $type === 'hit' ) {
			$stats['total_hits']++;
			$stats['daily'][ $today ]['hits']++;
		} else {
			$stats['total_misses']++;
			$stats['daily'][ $today ]['misses']++;
		}

		// Update ratio
		$total = $stats['total_hits'] + $stats['total_misses'];
		$stats['hit_ratio'] = $total > 0 ? round( ( $stats['total_hits'] / $total ) * 100, 2 ) : 0;

		$this->save_stats( $stats );
	}

	/**
	 * Track page generation time
	 */
	public function track_page_generation() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		$generation_time = microtime( true ) - $this->page_load_start;
		
		$stats = $this->get_stats();

		// Update average generation time
		if ( ! isset( $stats['total_pages'] ) ) {
			$stats['total_pages'] = 0;
			$stats['avg_generation_time'] = 0;
		}

		$total_time = $stats['avg_generation_time'] * $stats['total_pages'];
		$stats['total_pages']++;
		$stats['avg_generation_time'] = ( $total_time + $generation_time ) / $stats['total_pages'];

		// Track by URL type
		$url_type = $this->get_url_type();
		if ( ! isset( $stats['by_type'][ $url_type ] ) ) {
			$stats['by_type'][ $url_type ] = array(
				'count' => 0,
				'avg_time' => 0,
			);
		}

		$type_total = $stats['by_type'][ $url_type ]['avg_time'] * $stats['by_type'][ $url_type ]['count'];
		$stats['by_type'][ $url_type ]['count']++;
		$stats['by_type'][ $url_type ]['avg_time'] = ( $type_total + $generation_time ) / $stats['by_type'][ $url_type ]['count'];

		$this->save_stats( $stats );
	}

	/**
	 * Get URL type
	 *
	 * @return string URL type.
	 */
	private function get_url_type() {
		if ( is_front_page() ) {
			return 'home';
		} elseif ( is_single() ) {
			return 'single';
		} elseif ( is_page() ) {
			return 'page';
		} elseif ( is_archive() ) {
			return 'archive';
		} elseif ( is_search() ) {
			return 'search';
		} else {
			return 'other';
		}
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Statistics.
	 */
	public function get_stats() {
		$default = array(
			'total_hits'           => 0,
			'total_misses'         => 0,
			'hit_ratio'            => 0,
			'total_pages'          => 0,
			'avg_generation_time'  => 0,
			'daily'                => array(),
			'by_type'              => array(),
			'cache_size'           => 0,
			'last_updated'         => current_time( 'mysql' ),
		);

		$stats = get_option( 'velocitywp_cache_analytics', $default );

		// Calculate cache size
		$stats['cache_size'] = $this->get_cache_size();

		return $stats;
	}

	/**
	 * Save cache statistics
	 *
	 * @param array $stats Statistics to save.
	 */
	private function save_stats( $stats ) {
		$stats['last_updated'] = current_time( 'mysql' );
		update_option( 'velocitywp_cache_analytics', $stats, false );
	}

	/**
	 * Get cache directory size
	 *
	 * @return int Size in bytes.
	 */
	private function get_cache_size() {
		$cache_dir = WP_CONTENT_DIR . '/cache/wpsb/';

		if ( ! file_exists( $cache_dir ) ) {
			return 0;
		}

		$size = 0;
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $cache_dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $files as $file ) {
			if ( $file->isFile() ) {
				$size += $file->getSize();
			}
		}

		return $size;
	}

	/**
	 * Format bytes
	 *
	 * @param int $bytes Bytes.
	 * @return string Formatted size.
	 */
	private function format_bytes( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
		return sprintf( "%.2f %s", $bytes / pow( 1024, $factor ), $units[ $factor ] );
	}

	/**
	 * Show cache badge in footer
	 */
	public function show_cache_badge() {
		$stats = $this->get_stats();
		$generation_time = microtime( true ) - $this->page_load_start;
		
		$cached = did_action( 'velocitywp_cache_hit' ) > 0;
		$badge_color = $cached ? '#00a32a' : '#f56e28';

		?>
		<div id="velocitywp-cache-badge" style="position: fixed; bottom: 0; left: 0; background: <?php echo esc_attr( $badge_color ); ?>; color: #fff; padding: 8px 12px; font-size: 11px; z-index: 99999; border-top-right-radius: 3px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; line-height: 1.4;">
			<div style="font-weight: 600; margin-bottom: 3px;">
				<?php echo $cached ? '⚡ Cached' : '🔄 Not Cached'; ?>
			</div>
			<div style="opacity: 0.9; font-size: 10px;">
				Generated in <?php echo esc_html( number_format( $generation_time, 4 ) ); ?>s
				<?php if ( ! empty( $stats['hit_ratio'] ) ) : ?>
					<br>Hit Rate: <?php echo esc_html( $stats['hit_ratio'] ); ?>%
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get analytics report
	 *
	 * @param int $days Number of days.
	 * @return array Report data.
	 */
	public function get_report( $days = 30 ) {
		$stats = $this->get_stats();
		$report = array();

		// Get daily stats for specified period
		$end_date = current_time( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		for ( $i = 0; $i < $days; $i++ ) {
			$date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
			
			if ( isset( $stats['daily'][ $date ] ) ) {
				$report[ $date ] = $stats['daily'][ $date ];
			} else {
				$report[ $date ] = array(
					'hits'   => 0,
					'misses' => 0,
				);
			}
		}

		return array_reverse( $report );
	}

	/**
	 * Cleanup old analytics data
	 */
	public function cleanup_old_data() {
		$stats = $this->get_stats();
		$cutoff = date( 'Y-m-d', strtotime( '-90 days' ) );

		// Remove old daily stats
		foreach ( $stats['daily'] as $date => $data ) {
			if ( $date < $cutoff ) {
				unset( $stats['daily'][ $date ] );
			}
		}

		$this->save_stats( $stats );
	}

	/**
	 * Reset statistics
	 */
	public function reset_stats() {
		delete_option( 'velocitywp_cache_analytics' );
	}

	/**
	 * AJAX handler to get cache stats
	 */
	public function ajax_get_cache_stats() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;
		$stats = $this->get_stats();
		$report = $this->get_report( $days );

		wp_send_json_success( array(
			'stats'  => $stats,
			'report' => $report,
		) );
	}

	/**
	 * AJAX handler to reset cache stats
	 */
	public function ajax_reset_cache_stats() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$this->reset_stats();

		wp_send_json_success( array( 'message' => __( 'Cache statistics reset', 'velocitywp' ) ) );
	}
}
