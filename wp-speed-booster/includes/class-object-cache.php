<?php
/**
 * Object Cache Class
 *
 * Redis/Memcached/APCu integration
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Object_Cache class
 */
class WPSB_Object_Cache {

	/**
	 * Cache backend
	 *
	 * @var string
	 */
	private $backend = '';

	/**
	 * Cache status
	 *
	 * @var bool
	 */
	private $is_active = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_test_object_cache', array( $this, 'ajax_test_cache' ) );
		add_action( 'wp_ajax_wpsb_flush_object_cache', array( $this, 'ajax_flush_cache' ) );
	}

	/**
	 * Initialize object cache
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['object_cache'] ) ) {
			return;
		}

		$this->detect_backend();
		$this->is_active = $this->is_cache_active();

		// Add admin notices
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * Detect available cache backend
	 */
	private function detect_backend() {
		$options = get_option( 'wpsb_options', array() );
		$preferred = ! empty( $options['object_cache_backend'] ) ? $options['object_cache_backend'] : 'auto';

		if ( $preferred === 'auto' ) {
			// Auto-detect available backend
			if ( class_exists( 'Redis' ) && $this->test_redis() ) {
				$this->backend = 'redis';
			} elseif ( class_exists( 'Memcached' ) && $this->test_memcached() ) {
				$this->backend = 'memcached';
			} elseif ( function_exists( 'apcu_fetch' ) && $this->test_apcu() ) {
				$this->backend = 'apcu';
			}
		} else {
			$this->backend = $preferred;
		}

		return $this->backend;
	}

	/**
	 * Test Redis connection
	 *
	 * @return bool Whether Redis is available.
	 */
	private function test_redis() {
		if ( ! class_exists( 'Redis' ) ) {
			return false;
		}

		try {
			$redis = new Redis();
			$options = get_option( 'wpsb_options', array() );
			
			$host = ! empty( $options['redis_host'] ) ? $options['redis_host'] : '127.0.0.1';
			$port = ! empty( $options['redis_port'] ) ? intval( $options['redis_port'] ) : 6379;
			
			$connected = $redis->connect( $host, $port, 1 );
			
			if ( $connected && ! empty( $options['redis_password'] ) ) {
				$redis->auth( $options['redis_password'] );
			}

			if ( $connected ) {
				$redis->ping();
				$redis->close();
				return true;
			}
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Test Memcached connection
	 *
	 * @return bool Whether Memcached is available.
	 */
	private function test_memcached() {
		if ( ! class_exists( 'Memcached' ) ) {
			return false;
		}

		try {
			$memcached = new Memcached();
			$options = get_option( 'wpsb_options', array() );
			
			$host = ! empty( $options['memcached_host'] ) ? $options['memcached_host'] : '127.0.0.1';
			$port = ! empty( $options['memcached_port'] ) ? intval( $options['memcached_port'] ) : 11211;
			
			$memcached->addServer( $host, $port );
			$memcached->set( 'wpsb_test', 'test', 10 );
			$result = $memcached->get( 'wpsb_test' );
			
			return $result === 'test';
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Test APCu availability
	 *
	 * @return bool Whether APCu is available.
	 */
	private function test_apcu() {
		if ( ! function_exists( 'apcu_fetch' ) ) {
			return false;
		}

		try {
			apcu_store( 'wpsb_test', 'test', 10 );
			$result = apcu_fetch( 'wpsb_test' );
			return $result === 'test';
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Check if object cache is active
	 *
	 * @return bool Whether cache is active.
	 */
	private function is_cache_active() {
		// Check if object-cache.php drop-in exists
		$object_cache_file = WP_CONTENT_DIR . '/object-cache.php';
		return file_exists( $object_cache_file );
	}

	/**
	 * Install object cache drop-in
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function install_dropin() {
		if ( empty( $this->backend ) ) {
			return new WP_Error( 'no_backend', __( 'No cache backend available', 'wp-speed-booster' ) );
		}

		$dropin_source = WPSB_PATH . 'includes/dropins/object-cache-' . $this->backend . '.php';
		$dropin_dest = WP_CONTENT_DIR . '/object-cache.php';

		if ( ! file_exists( $dropin_source ) ) {
			return new WP_Error( 'no_dropin', __( 'Drop-in file not found', 'wp-speed-booster' ) );
		}

		// Backup existing drop-in
		if ( file_exists( $dropin_dest ) ) {
			$backup = $dropin_dest . '.bak.' . time();
			copy( $dropin_dest, $backup );
		}

		// Copy drop-in
		$result = copy( $dropin_source, $dropin_dest );

		if ( ! $result ) {
			return new WP_Error( 'copy_failed', __( 'Failed to copy drop-in file', 'wp-speed-booster' ) );
		}

		$this->is_active = true;

		return true;
	}

	/**
	 * Remove object cache drop-in
	 *
	 * @return bool Success.
	 */
	public function remove_dropin() {
		$dropin_file = WP_CONTENT_DIR . '/object-cache.php';

		if ( ! file_exists( $dropin_file ) ) {
			return true;
		}

		// Check if it's our drop-in
		$content = file_get_contents( $dropin_file );
		if ( strpos( $content, 'WP Speed Booster' ) === false ) {
			return false;
		}

		$result = unlink( $dropin_file );
		
		if ( $result ) {
			$this->is_active = false;
		}

		return $result;
	}

	/**
	 * Flush object cache
	 *
	 * @return bool Success.
	 */
	public function flush_cache() {
		return wp_cache_flush();
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats.
	 */
	public function get_statistics() {
		global $wp_object_cache;

		$stats = array(
			'backend'  => $this->backend,
			'active'   => $this->is_active,
			'hits'     => 0,
			'misses'   => 0,
			'ratio'    => 0,
		);

		if ( isset( $wp_object_cache->cache_hits ) ) {
			$stats['hits'] = $wp_object_cache->cache_hits;
		}

		if ( isset( $wp_object_cache->cache_misses ) ) {
			$stats['misses'] = $wp_object_cache->cache_misses;
		}

		$total = $stats['hits'] + $stats['misses'];
		if ( $total > 0 ) {
			$stats['ratio'] = round( ( $stats['hits'] / $total ) * 100, 2 );
		}

		return $stats;
	}

	/**
	 * Admin notices
	 */
	public function admin_notices() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['object_cache'] ) ) {
			return;
		}

		if ( ! $this->is_active ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'WP Speed Booster: Object cache is enabled but the drop-in file is not installed.', 'wp-speed-booster' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsb-settings&tab=advanced' ) ); ?>">
						<?php esc_html_e( 'Install now', 'wp-speed-booster' ); ?>
					</a>
				</p>
			</div>
			<?php
		}

		if ( empty( $this->backend ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php esc_html_e( 'WP Speed Booster: No object cache backend available. Please install Redis, Memcached, or APCu.', 'wp-speed-booster' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX handler to test cache connection
	 */
	public function ajax_test_cache() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$backend = isset( $_POST['backend'] ) ? sanitize_text_field( $_POST['backend'] ) : '';

		$available = false;
		switch ( $backend ) {
			case 'redis':
				$available = $this->test_redis();
				break;
			case 'memcached':
				$available = $this->test_memcached();
				break;
			case 'apcu':
				$available = $this->test_apcu();
				break;
		}

		if ( $available ) {
			wp_send_json_success( array( 'message' => sprintf( __( '%s is available and working', 'wp-speed-booster' ), ucfirst( $backend ) ) ) );
		} else {
			wp_send_json_error( array( 'message' => sprintf( __( '%s is not available or not working', 'wp-speed-booster' ), ucfirst( $backend ) ) ) );
		}
	}

	/**
	 * AJAX handler to flush cache
	 */
	public function ajax_flush_cache() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$result = $this->flush_cache();

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Object cache flushed', 'wp-speed-booster' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to flush cache', 'wp-speed-booster' ) ) );
		}
	}
}
