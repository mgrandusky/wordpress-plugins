<?php
/**
 * Fragment Cache Class
 *
 * Widget and sidebar caching for partial page caching
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Fragment_Cache class
 */
class WPSB_Fragment_Cache {

	/**
	 * Cache duration in seconds
	 *
	 * @var int
	 */
	private $cache_duration = 3600;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_clear_fragment_cache', array( $this, 'ajax_clear_cache' ) );
	}

	/**
	 * Initialize fragment caching
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['fragment_cache'] ) || is_admin() ) {
			return;
		}

		// Set cache duration
		if ( ! empty( $options['fragment_cache_duration'] ) ) {
			$this->cache_duration = intval( $options['fragment_cache_duration'] );
		}

		// Cache widgets
		if ( ! empty( $options['cache_widgets'] ) ) {
			add_filter( 'widget_display_callback', array( $this, 'cache_widget' ), 10, 3 );
		}

		// Cache sidebars
		if ( ! empty( $options['cache_sidebars'] ) ) {
			add_filter( 'dynamic_sidebar_before', array( $this, 'cache_sidebar_start' ), 10, 2 );
			add_filter( 'dynamic_sidebar_after', array( $this, 'cache_sidebar_end' ), 10, 2 );
		}

		// Cache menu
		if ( ! empty( $options['cache_menu'] ) ) {
			add_filter( 'pre_wp_nav_menu', array( $this, 'cache_menu' ), 10, 2 );
		}

		// Clear cache on content updates
		add_action( 'save_post', array( $this, 'clear_cache_on_update' ) );
		add_action( 'deleted_post', array( $this, 'clear_cache_on_update' ) );
		add_action( 'switch_theme', array( $this, 'clear_all_cache' ) );
	}

	/**
	 * Cache widget output
	 *
	 * @param array     $instance Widget instance.
	 * @param WP_Widget $widget   Widget object.
	 * @param array     $args     Widget args.
	 * @return array|false Instance or false to skip widget.
	 */
	public function cache_widget( $instance, $widget, $args ) {
		if ( $this->should_skip_cache( $widget ) ) {
			return $instance;
		}

		$widget_id = $widget->id;
		$cache_key = $this->get_cache_key( 'widget', $widget_id );

		// Try to get cached output
		$cached_output = $this->get_cache( $cache_key );

		if ( $cached_output !== false ) {
			echo $cached_output;
			return false;
		}

		// Start output buffering
		ob_start();
		
		// Store widget data for later use in shutdown
		add_action( 'shutdown', function() use ( $cache_key ) {
			$output = ob_get_contents();
			if ( ! empty( $output ) ) {
				$this->set_cache( $cache_key, $output );
			}
		}, 999 );

		return $instance;
	}

	/**
	 * Start sidebar caching
	 *
	 * @param int    $index Sidebar index.
	 * @param string $name  Sidebar name.
	 */
	public function cache_sidebar_start( $index, $name ) {
		$cache_key = $this->get_cache_key( 'sidebar', $index );

		// Try to get cached output
		$cached_output = $this->get_cache( $cache_key );

		if ( $cached_output !== false ) {
			echo $cached_output;
			return false;
		}

		// Start output buffering
		ob_start();
	}

	/**
	 * End sidebar caching
	 *
	 * @param int    $index Sidebar index.
	 * @param string $name  Sidebar name.
	 */
	public function cache_sidebar_end( $index, $name ) {
		$cache_key = $this->get_cache_key( 'sidebar', $index );
		
		// Check if we already returned cached content
		$cached_output = $this->get_cache( $cache_key );
		if ( $cached_output !== false ) {
			return;
		}

		// Get and cache output
		$output = ob_get_clean();
		
		if ( ! empty( $output ) ) {
			$this->set_cache( $cache_key, $output );
			echo $output;
		}
	}

	/**
	 * Cache navigation menu
	 *
	 * @param string|null $output Menu output.
	 * @param object      $args   Menu args.
	 * @return string|null Cached menu or null.
	 */
	public function cache_menu( $output, $args ) {
		$cache_key = $this->get_cache_key( 'menu', $args->menu->term_id );

		// Try to get cached output
		$cached_output = $this->get_cache( $cache_key );

		if ( $cached_output !== false ) {
			return $cached_output;
		}

		// Return null to let WordPress generate the menu
		// Then cache it using a filter
		add_filter( 'wp_nav_menu', function( $nav_menu ) use ( $cache_key ) {
			$this->set_cache( $cache_key, $nav_menu );
			return $nav_menu;
		}, 999 );

		return null;
	}

	/**
	 * Get cached fragment
	 *
	 * @param string $key Cache key.
	 * @return string|false Cached content or false.
	 */
	private function get_cache( $key ) {
		// Try transient first
		$cached = get_transient( $key );
		
		if ( $cached !== false ) {
			do_action( 'wpsb_fragment_cache_hit', $key );
			return $cached;
		}

		do_action( 'wpsb_fragment_cache_miss', $key );
		return false;
	}

	/**
	 * Set cached fragment
	 *
	 * @param string $key     Cache key.
	 * @param string $content Content to cache.
	 * @return bool Success.
	 */
	private function set_cache( $key, $content ) {
		return set_transient( $key, $content, $this->cache_duration );
	}

	/**
	 * Generate cache key
	 *
	 * @param string $type Type of fragment.
	 * @param string $id   Fragment ID.
	 * @return string Cache key.
	 */
	private function get_cache_key( $type, $id ) {
		$key_parts = array(
			'wpsb_fragment',
			$type,
			$id,
		);

		// Add user-specific key if needed
		if ( is_user_logged_in() ) {
			$key_parts[] = get_current_user_id();
		}

		// Add mobile suffix if needed
		if ( wp_is_mobile() ) {
			$key_parts[] = 'mobile';
		}

		$key = implode( '_', $key_parts );
		
		return apply_filters( 'wpsb_fragment_cache_key', $key, $type, $id );
	}

	/**
	 * Check if widget should skip caching
	 *
	 * @param WP_Widget $widget Widget object.
	 * @return bool Whether to skip caching.
	 */
	private function should_skip_cache( $widget ) {
		$options = get_option( 'wpsb_options', array() );

		// Skip for logged in users if configured
		if ( ! empty( $options['fragment_skip_logged_in'] ) && is_user_logged_in() ) {
			return true;
		}

		// Get excluded widget types
		$excluded_widgets = array();
		if ( ! empty( $options['fragment_excluded_widgets'] ) ) {
			$excluded_widgets = array_map( 'trim', explode( "\n", $options['fragment_excluded_widgets'] ) );
		}

		// Check if widget type is excluded
		foreach ( $excluded_widgets as $excluded ) {
			if ( strpos( $widget->id_base, $excluded ) !== false ) {
				return true;
			}
		}

		return apply_filters( 'wpsb_fragment_skip_cache', false, $widget );
	}

	/**
	 * Clear fragment cache on content update
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_update( $post_id ) {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['fragment_clear_on_update'] ) ) {
			return;
		}

		$this->clear_all_cache();
	}

	/**
	 * Clear all fragment cache
	 */
	public function clear_all_cache() {
		global $wpdb;

		// Delete all fragment cache transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_wpsb_fragment_%' 
			OR option_name LIKE '_transient_timeout_wpsb_fragment_%'"
		);

		do_action( 'wpsb_fragment_cache_cleared' );
	}

	/**
	 * Clear specific fragment cache
	 *
	 * @param string $type Fragment type.
	 * @param string $id   Fragment ID.
	 */
	public function clear_fragment( $type, $id ) {
		$cache_key = $this->get_cache_key( $type, $id );
		delete_transient( $cache_key );
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		global $wpdb;

		$total = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_wpsb_fragment_%'"
		);

		$size = $wpdb->get_var(
			"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_wpsb_fragment_%'"
		);

		return array(
			'total_fragments' => intval( $total ),
			'total_size'      => intval( $size ),
		);
	}

	/**
	 * AJAX handler to clear fragment cache
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$this->clear_all_cache();

		wp_send_json_success( array( 'message' => __( 'Fragment cache cleared', 'wp-speed-booster' ) ) );
	}
}
