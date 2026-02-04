<?php
/**
 * Query Monitor Class
 *
 * Database query monitoring and optimization
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Query_Monitor class
 */
class WPSB_Query_Monitor {

	/**
	 * Queries log
	 *
	 * @var array
	 */
	private $queries = array();

	/**
	 * Query count
	 *
	 * @var int
	 */
	private $query_count = 0;

	/**
	 * Total query time
	 *
	 * @var float
	 */
	private $total_time = 0;

	/**
	 * Slow query threshold (seconds)
	 *
	 * @var float
	 */
	private $slow_threshold = 0.05;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_get_query_log', array( $this, 'ajax_get_query_log' ) );
		add_action( 'wp_ajax_wpsb_clear_query_log', array( $this, 'ajax_clear_query_log' ) );
	}

	/**
	 * Initialize query monitoring
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['query_monitor'] ) ) {
			return;
		}

		// Enable query logging
		if ( ! defined( 'SAVEQUERIES' ) ) {
			define( 'SAVEQUERIES', true );
		}

		// Set slow query threshold
		if ( ! empty( $options['slow_query_threshold'] ) ) {
			$this->slow_threshold = floatval( $options['slow_query_threshold'] );
		}

		// Monitor queries
		add_action( 'shutdown', array( $this, 'log_queries' ), 999 );

		// Show query info in footer for admins
		if ( ! empty( $options['show_query_info'] ) && current_user_can( 'manage_options' ) ) {
			add_action( 'wp_footer', array( $this, 'show_query_info' ), 999 );
			add_action( 'admin_footer', array( $this, 'show_query_info' ), 999 );
		}
	}

	/**
	 * Log queries at shutdown
	 */
	public function log_queries() {
		global $wpdb;

		if ( empty( $wpdb->queries ) ) {
			return;
		}

		$this->query_count = count( $wpdb->queries );

		foreach ( $wpdb->queries as $query ) {
			$sql = $query[0];
			$time = $query[1];
			$caller = $query[2];

			$this->total_time += $time;

			// Log slow queries
			if ( $time >= $this->slow_threshold ) {
				$this->log_slow_query( $sql, $time, $caller );
			}

			// Detect duplicate queries
			$this->detect_duplicate( $sql );

			// Detect queries without indexes
			$this->detect_missing_index( $sql );
		}

		// Store stats
		$this->save_query_stats();
	}

	/**
	 * Log slow query
	 *
	 * @param string $sql    SQL query.
	 * @param float  $time   Execution time.
	 * @param string $caller Query caller.
	 */
	private function log_slow_query( $sql, $time, $caller ) {
		$log_entry = array(
			'sql'       => $sql,
			'time'      => $time,
			'caller'    => $caller,
			'timestamp' => current_time( 'mysql' ),
			'url'       => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '',
		);

		$slow_queries = get_option( 'wpsb_slow_queries', array() );
		$slow_queries[] = $log_entry;

		// Keep only last 100 slow queries
		$slow_queries = array_slice( $slow_queries, -100 );

		update_option( 'wpsb_slow_queries', $slow_queries, false );
	}

	/**
	 * Detect duplicate queries
	 *
	 * @param string $sql SQL query.
	 */
	private function detect_duplicate( $sql ) {
		$normalized = $this->normalize_query( $sql );
		
		$duplicates = get_option( 'wpsb_duplicate_queries', array() );

		if ( ! isset( $duplicates[ $normalized ] ) ) {
			$duplicates[ $normalized ] = array(
				'sql'   => $sql,
				'count' => 0,
			);
		}

		$duplicates[ $normalized ]['count']++;

		update_option( 'wpsb_duplicate_queries', $duplicates, false );
	}

	/**
	 * Normalize query for comparison
	 *
	 * @param string $sql SQL query.
	 * @return string Normalized query.
	 */
	private function normalize_query( $sql ) {
		// Remove values to find structural duplicates
		$normalized = preg_replace( '/\d+/', 'N', $sql );
		$normalized = preg_replace( "/'[^']*'/", "'S'", $normalized );
		$normalized = preg_replace( '/\s+/', ' ', $normalized );
		return trim( $normalized );
	}

	/**
	 * Detect queries that might be missing indexes
	 *
	 * @param string $sql SQL query.
	 */
	private function detect_missing_index( $sql ) {
		global $wpdb;

		// Only analyze SELECT queries
		if ( stripos( $sql, 'SELECT' ) !== 0 ) {
			return;
		}

		// Skip if EXPLAIN is disabled
		$options = get_option( 'wpsb_options', array() );
		if ( empty( $options['query_explain'] ) ) {
			return;
		}

		// Run EXPLAIN
		$explain = $wpdb->get_results( 'EXPLAIN ' . $sql, ARRAY_A );

		if ( empty( $explain ) ) {
			return;
		}

		// Check for table scans or bad keys
		foreach ( $explain as $row ) {
			$possible_keys = isset( $row['possible_keys'] ) ? $row['possible_keys'] : null;
			$key = isset( $row['key'] ) ? $row['key'] : null;
			$rows = isset( $row['rows'] ) ? intval( $row['rows'] ) : 0;

			// No index used and scanning many rows
			if ( ( $possible_keys === null || $key === null ) && $rows > 100 ) {
				$this->log_missing_index( $sql, $explain );
				break;
			}
		}
	}

	/**
	 * Log query with missing index
	 *
	 * @param string $sql     SQL query.
	 * @param array  $explain EXPLAIN output.
	 */
	private function log_missing_index( $sql, $explain ) {
		$missing_indexes = get_option( 'wpsb_missing_indexes', array() );

		$query_hash = md5( $sql );

		if ( ! isset( $missing_indexes[ $query_hash ] ) ) {
			$missing_indexes[ $query_hash ] = array(
				'sql'     => $sql,
				'explain' => $explain,
				'count'   => 0,
			);
		}

		$missing_indexes[ $query_hash ]['count']++;

		update_option( 'wpsb_missing_indexes', $missing_indexes, false );
	}

	/**
	 * Save query statistics
	 */
	private function save_query_stats() {
		$stats = array(
			'query_count' => $this->query_count,
			'total_time'  => $this->total_time,
			'timestamp'   => time(),
			'url'         => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '',
		);

		$history = get_option( 'wpsb_query_history', array() );
		$history[] = $stats;

		// Keep only last 100 page loads
		$history = array_slice( $history, -100 );

		update_option( 'wpsb_query_history', $history, false );
	}

	/**
	 * Show query info in footer
	 */
	public function show_query_info() {
		global $wpdb;

		if ( empty( $wpdb->queries ) ) {
			return;
		}

		$query_count = count( $wpdb->queries );
		$total_time = 0;

		foreach ( $wpdb->queries as $query ) {
			$total_time += $query[1];
		}

		$slow_count = 0;
		foreach ( $wpdb->queries as $query ) {
			if ( $query[1] >= $this->slow_threshold ) {
				$slow_count++;
			}
		}

		?>
		<div id="wpsb-query-info" style="position: fixed; bottom: 0; right: 0; background: #23282d; color: #fff; padding: 10px 15px; font-size: 12px; z-index: 99999; border-top-left-radius: 3px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
			<strong>Database Queries:</strong> <?php echo esc_html( $query_count ); ?> |
			<strong>Time:</strong> <?php echo esc_html( number_format( $total_time, 4 ) ); ?>s
			<?php if ( $slow_count > 0 ) : ?>
				| <strong style="color: #f56e28;">Slow:</strong> <?php echo esc_html( $slow_count ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get query recommendations
	 *
	 * @return array Recommendations.
	 */
	public function get_recommendations() {
		$recommendations = array();

		// Check slow queries
		$slow_queries = get_option( 'wpsb_slow_queries', array() );
		if ( ! empty( $slow_queries ) ) {
			$recommendations[] = array(
				'type'    => 'slow_queries',
				'level'   => 'warning',
				'message' => sprintf( __( 'Found %d slow queries that need optimization', 'wp-speed-booster' ), count( $slow_queries ) ),
			);
		}

		// Check duplicate queries
		$duplicates = get_option( 'wpsb_duplicate_queries', array() );
		$high_duplicates = array_filter( $duplicates, function( $item ) {
			return $item['count'] > 5;
		} );

		if ( ! empty( $high_duplicates ) ) {
			$recommendations[] = array(
				'type'    => 'duplicate_queries',
				'level'   => 'warning',
				'message' => sprintf( __( 'Found %d queries that are executed multiple times', 'wp-speed-booster' ), count( $high_duplicates ) ),
			);
		}

		// Check missing indexes
		$missing_indexes = get_option( 'wpsb_missing_indexes', array() );
		if ( ! empty( $missing_indexes ) ) {
			$recommendations[] = array(
				'type'    => 'missing_indexes',
				'level'   => 'error',
				'message' => sprintf( __( 'Found %d queries that may benefit from database indexes', 'wp-speed-booster' ), count( $missing_indexes ) ),
			);
		}

		return $recommendations;
	}

	/**
	 * AJAX handler to get query log
	 */
	public function ajax_get_query_log() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'slow';

		switch ( $type ) {
			case 'slow':
				$data = get_option( 'wpsb_slow_queries', array() );
				break;
			case 'duplicate':
				$data = get_option( 'wpsb_duplicate_queries', array() );
				break;
			case 'missing_index':
				$data = get_option( 'wpsb_missing_indexes', array() );
				break;
			case 'history':
				$data = get_option( 'wpsb_query_history', array() );
				break;
			default:
				$data = array();
		}

		wp_send_json_success( array( 'data' => $data ) );
	}

	/**
	 * AJAX handler to clear query log
	 */
	public function ajax_clear_query_log() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		delete_option( 'wpsb_slow_queries' );
		delete_option( 'wpsb_duplicate_queries' );
		delete_option( 'wpsb_missing_indexes' );
		delete_option( 'wpsb_query_history' );

		wp_send_json_success( array( 'message' => __( 'Query log cleared', 'wp-speed-booster' ) ) );
	}
}
