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
	 * Sidebar output buffer storage
	 *
	 * @var array
	 */
	private $sidebar_buffers = array();

	/**
	 * Statistics storage
	 *
	 * @var array
	 */
	private $stats = array(
		'widget_hits'     => 0,
		'widget_misses'   => 0,
		'sidebar_hits'    => 0,
		'sidebar_misses'  => 0,
		'menu_hits'       => 0,
		'menu_misses'     => 0,
		'shortcode_hits'  => 0,
		'shortcode_misses' => 0,
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_clear_fragment_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_wpsb_get_fragment_stats', array( $this, 'ajax_get_fragment_stats' ) );
		add_action( 'wp_ajax_wpsb_clear_fragment_type', array( $this, 'ajax_clear_fragment_type' ) );
	}

	/**
	 * Initialize fragment caching
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['fragment_cache_enabled'] ) || is_admin() ) {
			return;
		}

		// Set cache duration
		if ( ! empty( $options['fragment_cache_time'] ) ) {
			$this->cache_duration = intval( $options['fragment_cache_time'] );
		}

		// Cache widgets
		if ( ! empty( $options['cache_widgets'] ) ) {
			add_filter( 'widget_display_callback', array( $this, 'cache_widget' ), 10, 3 );
		}

		// Cache sidebars
		if ( ! empty( $options['cache_sidebars'] ) ) {
			add_action( 'dynamic_sidebar_before', array( $this, 'start_sidebar_cache' ), 10, 2 );
			add_action( 'dynamic_sidebar_after', array( $this, 'end_sidebar_cache' ), 10, 2 );
		}

		// Cache menus
		if ( ! empty( $options['cache_menus'] ) ) {
			add_filter( 'pre_wp_nav_menu', array( $this, 'get_cached_menu' ), 10, 2 );
			add_filter( 'wp_nav_menu', array( $this, 'cache_menu' ), 10, 2 );
		}

		// Cache shortcodes
		if ( ! empty( $options['cache_shortcodes'] ) ) {
			add_filter( 'do_shortcode_tag', array( $this, 'cache_shortcode' ), 10, 4 );
		}

		// Clear cache on content updates
		add_action( 'save_post', array( $this, 'clear_cache_on_update' ) );
		add_action( 'deleted_post', array( $this, 'clear_cache_on_update' ) );
		add_action( 'switch_theme', array( $this, 'clear_all_fragments' ) );
		add_action( 'wp_update_nav_menu', array( $this, 'clear_menu_cache' ) );
		add_action( 'activated_plugin', array( $this, 'clear_all_fragments' ) );
		add_action( 'deactivated_plugin', array( $this, 'clear_all_fragments' ) );
	}

	/**
	 * Check if caching is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$options = get_option( 'wpsb_options', array() );
		return ! empty( $options['fragment_cache_enabled'] );
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
		if ( ! $this->should_cache_widget( $widget ) ) {
			return $instance;
		}

		$widget_id = $widget->id;
		$cache_key = $this->get_widget_cache_key( $widget_id, $args );

		// Try to get cached output
		$cached_output = $this->get_fragment( $cache_key );

		if ( $cached_output !== false ) {
			$this->increment_hit( 'widget' );
			echo $cached_output;
			return false;
		}

		$this->increment_miss( 'widget' );

		// Start output buffering
		ob_start();
		
		// Store widget data for later use in shutdown
		add_action( 'shutdown', function() use ( $cache_key ) {
			$output = ob_get_contents();
			if ( ! empty( $output ) ) {
				$this->set_fragment( $cache_key, $output, $this->cache_duration );
			}
		}, 999 );

		return $instance;
	}

	/**
	 * Get widget cache key
	 *
	 * @param string $widget_id Widget ID.
	 * @param array  $args      Widget arguments.
	 * @return string Cache key.
	 */
	public function get_widget_cache_key( $widget_id, $args ) {
		$hash = md5( serialize( $args ) );
		return $this->get_cache_key( 'widget', $widget_id . '_' . $hash );
	}

	/**
	 * Clear widget cache
	 *
	 * @param string|null $widget_id Widget ID or null for all widgets.
	 */
	public function clear_widget_cache( $widget_id = null ) {
		if ( $widget_id ) {
			$this->delete_fragment( $this->get_cache_key( 'widget', $widget_id ) );
		} else {
			$this->clear_fragment_type( 'widget' );
		}
	}

	/**
	 * Check if widget should be cached
	 *
	 * @param WP_Widget $widget Widget object.
	 * @return bool
	 */
	private function should_cache_widget( $widget ) {
		if ( ! $this->should_cache_for_user() ) {
			return false;
		}

		$options = get_option( 'wpsb_options', array() );
		
		// Check if this widget is in the cached list
		if ( ! empty( $options['cached_widget_list'] ) ) {
			$cached_widgets = $options['cached_widget_list'];
			if ( ! in_array( $widget->id_base, $cached_widgets ) ) {
				return false;
			}
		}

		return apply_filters( 'wpsb_should_cache_widget', true, $widget );
	}

	/**
	 * Start sidebar caching
	 *
	 * @param int|string $index Sidebar index.
	 * @param bool|string $name Sidebar name.
	 */
	public function start_sidebar_cache( $index, $name ) {
		if ( ! $this->should_cache_sidebar( $index ) ) {
			return;
		}

		$cache_key = $this->get_sidebar_cache_key( $index );

		// Try to get cached output
		$cached_output = $this->get_fragment( $cache_key );

		if ( $cached_output !== false ) {
			$this->increment_hit( 'sidebar' );
			$this->sidebar_buffers[ $index ] = 'cached';
			echo $cached_output;
			return;
		}

		$this->increment_miss( 'sidebar' );
		$this->sidebar_buffers[ $index ] = 'buffering';
		
		// Start output buffering
		ob_start();
	}

	/**
	 * End sidebar caching
	 *
	 * @param int|string $index Sidebar index.
	 * @param bool|string $name Sidebar name.
	 */
	public function end_sidebar_cache( $index, $name ) {
		// Check if we started buffering for this sidebar
		if ( ! isset( $this->sidebar_buffers[ $index ] ) ) {
			return;
		}

		// If cached output was already displayed, just clean up
		if ( $this->sidebar_buffers[ $index ] === 'cached' ) {
			unset( $this->sidebar_buffers[ $index ] );
			return;
		}

		// Get and cache output
		$output = ob_get_clean();
		
		if ( ! empty( $output ) ) {
			$cache_key = $this->get_sidebar_cache_key( $index );
			$this->set_fragment( $cache_key, $output, $this->cache_duration );
			echo $output;
		}

		unset( $this->sidebar_buffers[ $index ] );
	}

	/**
	 * Get sidebar cache key
	 *
	 * @param string $sidebar_id Sidebar ID.
	 * @return string Cache key.
	 */
	public function get_sidebar_cache_key( $sidebar_id ) {
		return $this->get_cache_key( 'sidebar', $sidebar_id );
	}

	/**
	 * Clear sidebar cache
	 *
	 * @param string|null $sidebar_id Sidebar ID or null for all sidebars.
	 */
	public function clear_sidebar_cache( $sidebar_id = null ) {
		if ( $sidebar_id ) {
			$this->delete_fragment( $this->get_cache_key( 'sidebar', $sidebar_id ) );
		} else {
			$this->clear_fragment_type( 'sidebar' );
		}
	}

	/**
	 * Check if sidebar should be cached
	 *
	 * @param string $sidebar_id Sidebar ID.
	 * @return bool
	 */
	private function should_cache_sidebar( $sidebar_id ) {
		if ( ! $this->should_cache_for_user() ) {
			return false;
		}

		$options = get_option( 'wpsb_options', array() );
		
		// Check if this sidebar is in the cached list
		if ( ! empty( $options['cached_sidebar_list'] ) ) {
			$cached_sidebars = $options['cached_sidebar_list'];
			if ( ! in_array( $sidebar_id, $cached_sidebars ) ) {
				return false;
			}
		}

		return apply_filters( 'wpsb_should_cache_sidebar', true, $sidebar_id );
	}

	/**
	 * Get cached navigation menu
	 *
	 * @param string|null $menu_output Menu output.
	 * @param object      $args        Menu args.
	 * @return string|null Cached menu or null.
	 */
	public function get_cached_menu( $menu_output, $args ) {
		if ( ! $this->should_cache_menu( $args ) ) {
			return null;
		}

		$cache_key = $this->get_menu_cache_key( $args );

		// Try to get cached output
		$cached_output = $this->get_fragment( $cache_key );

		if ( $cached_output !== false ) {
			$this->increment_hit( 'menu' );
			return $cached_output;
		}

		$this->increment_miss( 'menu' );
		return null;
	}

	/**
	 * Cache navigation menu output
	 *
	 * @param string $menu_html Menu HTML output.
	 * @param object $args      Menu args.
	 * @return string Menu HTML.
	 */
	public function cache_menu( $menu_html, $args ) {
		if ( ! $this->should_cache_menu( $args ) ) {
			return $menu_html;
		}

		$cache_key = $this->get_menu_cache_key( $args );
		$this->set_fragment( $cache_key, $menu_html, $this->cache_duration );
		
		return $menu_html;
	}

	/**
	 * Get menu cache key
	 *
	 * @param object $args Menu args.
	 * @return string Cache key.
	 */
	public function get_menu_cache_key( $args ) {
		$identifier = '';
		
		if ( ! empty( $args->theme_location ) ) {
			$identifier = $args->theme_location;
		} elseif ( ! empty( $args->menu ) ) {
			$identifier = is_object( $args->menu ) ? $args->menu->term_id : $args->menu;
		}
		
		$hash = md5( serialize( $args ) );
		return $this->get_cache_key( 'menu', $identifier . '_' . $hash );
	}

	/**
	 * Clear menu cache
	 *
	 * @param string|null $location Theme location or null for all menus.
	 */
	public function clear_menu_cache( $location = null ) {
		if ( $location ) {
			$this->delete_fragment( $this->get_cache_key( 'menu', $location ) );
		} else {
			$this->clear_fragment_type( 'menu' );
		}
	}

	/**
	 * Check if menu should be cached
	 *
	 * @param object $args Menu args.
	 * @return bool
	 */
	private function should_cache_menu( $args ) {
		if ( ! $this->should_cache_for_user() ) {
			return false;
		}

		$options = get_option( 'wpsb_options', array() );
		
		// Check if this menu location is in the cached list
		if ( ! empty( $options['cached_menu_list'] ) && ! empty( $args->theme_location ) ) {
			$cached_menus = $options['cached_menu_list'];
			if ( ! in_array( $args->theme_location, $cached_menus ) ) {
				return false;
			}
		}

		return apply_filters( 'wpsb_should_cache_menu', true, $args );
	}

	/**
	 * Cache shortcode output
	 *
	 * @param string|mixed $output Shortcode output.
	 * @param string       $tag    Shortcode tag.
	 * @param array|string $attr   Shortcode attributes.
	 * @param array        $m      Shortcode match array.
	 * @return string|mixed Shortcode output.
	 */
	public function cache_shortcode( $output, $tag, $attr, $m ) {
		if ( ! $this->should_cache_shortcode( $tag ) ) {
			return $output;
		}

		$cache_key = $this->get_shortcode_cache_key( $tag, $attr );

		// Try to get cached output
		$cached_output = $this->get_fragment( $cache_key );

		if ( $cached_output !== false ) {
			$this->increment_hit( 'shortcode' );
			return $cached_output;
		}

		$this->increment_miss( 'shortcode' );

		// Cache the output after it's generated
		if ( ! empty( $output ) && is_string( $output ) ) {
			$this->set_fragment( $cache_key, $output, $this->cache_duration );
		}

		return $output;
	}

	/**
	 * Get shortcode cache key
	 *
	 * @param string       $tag  Shortcode tag.
	 * @param array|string $attr Shortcode attributes.
	 * @return string Cache key.
	 */
	public function get_shortcode_cache_key( $tag, $attr ) {
		$hash = md5( serialize( $attr ) );
		return $this->get_cache_key( 'shortcode', $tag . '_' . $hash );
	}

	/**
	 * Clear shortcode cache
	 *
	 * @param string|null $tag Shortcode tag or null for all shortcodes.
	 */
	public function clear_shortcode_cache( $tag = null ) {
		if ( $tag ) {
			$this->delete_fragment( $this->get_cache_key( 'shortcode', $tag ) );
		} else {
			$this->clear_fragment_type( 'shortcode' );
		}
	}

	/**
	 * Check if shortcode should be cached
	 *
	 * @param string $tag Shortcode tag.
	 * @return bool
	 */
	private function should_cache_shortcode( $tag ) {
		if ( ! $this->should_cache_for_user() ) {
			return false;
		}

		$options = get_option( 'wpsb_options', array() );
		
		// Check if this shortcode is in the cached list
		if ( ! empty( $options['cached_shortcode_list'] ) ) {
			$cached_shortcodes = array_map( 'trim', explode( "\n", $options['cached_shortcode_list'] ) );
			if ( ! in_array( $tag, $cached_shortcodes ) ) {
				return false;
			}
		}

		return apply_filters( 'wpsb_should_cache_shortcode', true, $tag );
	}

	/**
	 * Set cached fragment
	 *
	 * @param string $key        Cache key.
	 * @param string $content    Content to cache.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool Success.
	 */
	public function set_fragment( $key, $content, $expiration ) {
		return set_transient( $key, $content, $expiration );
	}

	/**
	 * Get cached fragment
	 *
	 * @param string $key Cache key.
	 * @return string|false Cached content or false.
	 */
	public function get_fragment( $key ) {
		return get_transient( $key );
	}

	/**
	 * Delete cached fragment
	 *
	 * @param string $key Cache key.
	 * @return bool Success.
	 */
	public function delete_fragment( $key ) {
		return delete_transient( $key );
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
			$options = get_option( 'wpsb_options', array() );
			if ( empty( $options['fragment_cache_logged_in'] ) ) {
				$key_parts[] = 'logged_in';
			}
		}

		// Add mobile suffix if needed
		if ( wp_is_mobile() ) {
			$key_parts[] = 'mobile';
		}

		$key = implode( '_', $key_parts );
		
		return apply_filters( 'wpsb_fragment_cache_key', $key, $type, $id );
	}

	/**
	 * Check if caching should be done for current user
	 *
	 * @return bool
	 */
	public function should_cache_for_user() {
		$options = get_option( 'wpsb_options', array() );

		// Check if we should skip caching for logged-in users
		if ( ! empty( $options['fragment_cache_logged_in'] ) && is_user_logged_in() ) {
			return false;
		}

		return apply_filters( 'wpsb_fragment_should_cache_for_user', true );
	}

	/**
	 * Clear fragment cache type
	 *
	 * @param string $type Fragment type.
	 */
	private function clear_fragment_type( $type ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				'_transient_wpsb_fragment_' . $type . '%',
				'_transient_timeout_wpsb_fragment_' . $type . '%'
			)
		);
	}

	/**
	 * Clear all fragment cache
	 */
	public function clear_all_fragments() {
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
	 * Clear fragment cache on content update
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_update( $post_id ) {
		// Auto-clear on content updates
		$this->clear_all_fragments();
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Statistics.
	 */
	public function get_stats() {
		global $wpdb;

		$total = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_wpsb_fragment_%'"
		);

		$size = $wpdb->get_var(
			"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_wpsb_fragment_%'"
		);

		// Get persistent stats from options
		$persistent_stats = get_option( 'wpsb_fragment_stats', $this->stats );

		// Calculate hit ratio
		$total_hits = array_sum( array_filter( $persistent_stats, function( $key ) {
			return strpos( $key, '_hits' ) !== false;
		}, ARRAY_FILTER_USE_KEY ) );

		$total_misses = array_sum( array_filter( $persistent_stats, function( $key ) {
			return strpos( $key, '_misses' ) !== false;
		}, ARRAY_FILTER_USE_KEY ) );

		$total_requests = $total_hits + $total_misses;
		$hit_ratio = $total_requests > 0 ? round( ( $total_hits / $total_requests ) * 100, 2 ) : 0;

		return array(
			'total_fragments'       => intval( $total ),
			'total_size'            => intval( $size ),
			'total_size_formatted'  => size_format( intval( $size ) ),
			'widget_hits'           => intval( $persistent_stats['widget_hits'] ),
			'widget_misses'         => intval( $persistent_stats['widget_misses'] ),
			'sidebar_hits'          => intval( $persistent_stats['sidebar_hits'] ),
			'sidebar_misses'        => intval( $persistent_stats['sidebar_misses'] ),
			'menu_hits'             => intval( $persistent_stats['menu_hits'] ),
			'menu_misses'           => intval( $persistent_stats['menu_misses'] ),
			'shortcode_hits'        => intval( $persistent_stats['shortcode_hits'] ),
			'shortcode_misses'      => intval( $persistent_stats['shortcode_misses'] ),
			'overall_hit_ratio'     => $hit_ratio,
			'average_fragment_size' => $total > 0 ? intval( $size / $total ) : 0,
		);
	}

	/**
	 * Increment hit counter
	 *
	 * @param string $type Fragment type.
	 */
	public function increment_hit( $type ) {
		$stats = get_option( 'wpsb_fragment_stats', $this->stats );
		$key = $type . '_hits';
		if ( isset( $stats[ $key ] ) ) {
			$stats[ $key ]++;
		} else {
			$stats[ $key ] = 1;
		}
		update_option( 'wpsb_fragment_stats', $stats, false );
	}

	/**
	 * Increment miss counter
	 *
	 * @param string $type Fragment type.
	 */
	public function increment_miss( $type ) {
		$stats = get_option( 'wpsb_fragment_stats', $this->stats );
		$key = $type . '_misses';
		if ( isset( $stats[ $key ] ) ) {
			$stats[ $key ]++;
		} else {
			$stats[ $key ] = 1;
		}
		update_option( 'wpsb_fragment_stats', $stats, false );
	}

	/**
	 * AJAX handler to clear fragment cache
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$this->clear_all_fragments();

		wp_send_json_success( array( 'message' => __( 'Fragment cache cleared successfully', 'wp-speed-booster' ) ) );
	}

	/**
	 * AJAX handler to get fragment statistics
	 */
	public function ajax_get_fragment_stats() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$stats = $this->get_stats();

		wp_send_json_success( $stats );
	}

	/**
	 * AJAX handler to clear specific fragment type
	 */
	public function ajax_clear_fragment_type() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';

		if ( empty( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid fragment type', 'wp-speed-booster' ) ) );
		}

		switch ( $type ) {
			case 'widget':
				$this->clear_widget_cache();
				break;
			case 'sidebar':
				$this->clear_sidebar_cache();
				break;
			case 'menu':
				$this->clear_menu_cache();
				break;
			case 'shortcode':
				$this->clear_shortcode_cache();
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid fragment type', 'wp-speed-booster' ) ) );
		}

		wp_send_json_success( array( 'message' => ucfirst( $type ) . __( ' cache cleared successfully', 'wp-speed-booster' ) ) );
	}
}
