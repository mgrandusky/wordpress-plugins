<?php
/**
 * Debug Mode Class
 *
 * Frontend optimization indicators and debugging tools
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Debug_Mode class
 */
class WPSB_Debug_Mode {

	/**
	 * Debug log
	 *
	 * @var array
	 */
	private $debug_log = array();

	/**
	 * Start time
	 *
	 * @var float
	 */
	private $start_time = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->start_time = microtime( true );
		
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize debug mode
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['debug_mode'] ) ) {
			return;
		}

		// Only for admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add debug info to footer
		add_action( 'wp_footer', array( $this, 'output_debug_info' ), 9999 );
		add_action( 'admin_footer', array( $this, 'output_debug_info' ), 9999 );

		// Add debug bar
		if ( ! empty( $options['debug_bar'] ) ) {
			add_action( 'wp_footer', array( $this, 'output_debug_bar' ), 9999 );
		}

		// Log optimization actions
		$this->setup_logging();
	}

	/**
	 * Setup logging hooks
	 */
	private function setup_logging() {
		// Cache events
		add_action( 'wpsb_cache_hit', array( $this, 'log_cache_hit' ) );
		add_action( 'wpsb_cache_miss', array( $this, 'log_cache_miss' ) );
		add_action( 'wpsb_cache_created', array( $this, 'log_cache_created' ) );

		// Optimization events
		add_filter( 'wpsb_minified_html', array( $this, 'log_html_minified' ), 10, 2 );
		add_filter( 'wpsb_lazy_loaded_images', array( $this, 'log_lazy_load' ), 10, 1 );
	}

	/**
	 * Log cache hit
	 */
	public function log_cache_hit() {
		$this->log( 'Cache Hit', 'Page served from cache' );
	}

	/**
	 * Log cache miss
	 */
	public function log_cache_miss() {
		$this->log( 'Cache Miss', 'Page generated dynamically' );
	}

	/**
	 * Log cache created
	 */
	public function log_cache_created() {
		$this->log( 'Cache Created', 'New cache file created' );
	}

	/**
	 * Log HTML minification
	 *
	 * @param string $html     Minified HTML.
	 * @param string $original Original HTML.
	 * @return string HTML.
	 */
	public function log_html_minified( $html, $original ) {
		$original_size = strlen( $original );
		$minified_size = strlen( $html );
		$saved = $original_size - $minified_size;
		$percent = round( ( $saved / $original_size ) * 100, 2 );

		$this->log( 'HTML Minified', sprintf( 'Saved %s (%s%%)', size_format( $saved ), $percent ) );

		return $html;
	}

	/**
	 * Log lazy load
	 *
	 * @param int $count Number of images lazy loaded.
	 * @return int Count.
	 */
	public function log_lazy_load( $count ) {
		$this->log( 'Lazy Load', sprintf( '%d images lazy loaded', $count ) );
		return $count;
	}

	/**
	 * Add log entry
	 *
	 * @param string $title   Log title.
	 * @param string $message Log message.
	 */
	private function log( $title, $message ) {
		$this->debug_log[] = array(
			'title'   => $title,
			'message' => $message,
			'time'    => microtime( true ) - $this->start_time,
		);
	}

	/**
	 * Output debug information
	 */
	public function output_debug_info() {
		$generation_time = microtime( true ) - $this->start_time;
		
		// Get memory usage
		$memory_usage = memory_get_peak_usage( true );

		// Get query count
		global $wpdb;
		$query_count = count( $wpdb->queries );

		?>
		<!-- WP Speed Booster Debug Info -->
		<!--
		Generation Time: <?php echo number_format( $generation_time, 4 ); ?>s
		Memory Usage: <?php echo size_format( $memory_usage ); ?>
		Database Queries: <?php echo esc_html( $query_count ); ?>
		
		Optimization Log:
		<?php foreach ( $this->debug_log as $entry ) : ?>
		[<?php echo number_format( $entry['time'], 4 ); ?>s] <?php echo esc_html( $entry['title'] ); ?>: <?php echo esc_html( $entry['message'] ); ?>

		<?php endforeach; ?>
		-->
		<?php
	}

	/**
	 * Output debug bar
	 */
	public function output_debug_bar() {
		$generation_time = microtime( true ) - $this->start_time;
		$memory_usage = memory_get_peak_usage( true );
		
		global $wpdb;
		$query_count = isset( $wpdb->queries ) ? count( $wpdb->queries ) : 0;

		$cache_status = did_action( 'wpsb_cache_hit' ) > 0 ? 'HIT' : 'MISS';
		$cache_class = $cache_status === 'HIT' ? 'hit' : 'miss';

		?>
		<style>
		#wpsb-debug-bar {
			position: fixed;
			top: 32px;
			right: 0;
			background: #23282d;
			color: #fff;
			padding: 10px 15px;
			font-size: 12px;
			z-index: 99998;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			box-shadow: 0 2px 5px rgba(0,0,0,0.3);
			max-width: 300px;
		}
		#wpsb-debug-bar .wpsb-debug-item {
			margin: 5px 0;
			padding: 5px 0;
			border-bottom: 1px solid #32373c;
		}
		#wpsb-debug-bar .wpsb-debug-item:last-child {
			border-bottom: none;
		}
		#wpsb-debug-bar .wpsb-debug-label {
			font-weight: 600;
			color: #00a0d2;
		}
		#wpsb-debug-bar .wpsb-debug-value {
			float: right;
		}
		#wpsb-debug-bar .wpsb-cache-hit {
			color: #46b450;
		}
		#wpsb-debug-bar .wpsb-cache-miss {
			color: #f56e28;
		}
		#wpsb-debug-bar .wpsb-debug-toggle {
			cursor: pointer;
			text-align: center;
			padding: 5px;
			background: #32373c;
			margin: -10px -15px 10px;
		}
		#wpsb-debug-bar.collapsed .wpsb-debug-content {
			display: none;
		}
		</style>
		<div id="wpsb-debug-bar">
			<div class="wpsb-debug-toggle" onclick="this.parentElement.classList.toggle('collapsed')">
				⚡ WP Speed Booster Debug
			</div>
			<div class="wpsb-debug-content">
				<div class="wpsb-debug-item">
					<span class="wpsb-debug-label">Cache:</span>
					<span class="wpsb-debug-value wpsb-cache-<?php echo esc_attr( strtolower( $cache_status ) ); ?>">
						<?php echo esc_html( $cache_status ); ?>
					</span>
				</div>
				<div class="wpsb-debug-item">
					<span class="wpsb-debug-label">Time:</span>
					<span class="wpsb-debug-value"><?php echo number_format( $generation_time, 4 ); ?>s</span>
				</div>
				<div class="wpsb-debug-item">
					<span class="wpsb-debug-label">Memory:</span>
					<span class="wpsb-debug-value"><?php echo size_format( $memory_usage ); ?></span>
				</div>
				<div class="wpsb-debug-item">
					<span class="wpsb-debug-label">Queries:</span>
					<span class="wpsb-debug-value"><?php echo esc_html( $query_count ); ?></span>
				</div>
				<?php if ( ! empty( $this->debug_log ) ) : ?>
				<div class="wpsb-debug-item">
					<span class="wpsb-debug-label">Events:</span>
					<span class="wpsb-debug-value"><?php echo count( $this->debug_log ); ?></span>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<script>
		// Make debug bar draggable
		(function() {
			var bar = document.getElementById('wpsb-debug-bar');
			var isDragging = false;
			var currentX;
			var currentY;
			var initialX;
			var initialY;

			bar.addEventListener('mousedown', function(e) {
				if (e.target.classList.contains('wpsb-debug-toggle')) {
					initialX = e.clientX - bar.offsetLeft;
					initialY = e.clientY - bar.offsetTop;
					isDragging = true;
				}
			});

			document.addEventListener('mousemove', function(e) {
				if (isDragging) {
					e.preventDefault();
					currentX = e.clientX - initialX;
					currentY = e.clientY - initialY;
					bar.style.left = currentX + 'px';
					bar.style.top = currentY + 'px';
					bar.style.right = 'auto';
				}
			});

			document.addEventListener('mouseup', function() {
				isDragging = false;
			});
		})();
		</script>
		<?php
	}

	/**
	 * Get debug report
	 *
	 * @return array Debug report.
	 */
	public function get_debug_report() {
		global $wpdb;

		$report = array(
			'generation_time' => microtime( true ) - $this->start_time,
			'memory_usage'    => memory_get_peak_usage( true ),
			'query_count'     => isset( $wpdb->queries ) ? count( $wpdb->queries ) : 0,
			'cache_status'    => did_action( 'wpsb_cache_hit' ) > 0 ? 'hit' : 'miss',
			'log'             => $this->debug_log,
			'php_version'     => PHP_VERSION,
			'wp_version'      => get_bloginfo( 'version' ),
			'plugin_version'  => WPSB_VERSION,
		);

		return $report;
	}
}
