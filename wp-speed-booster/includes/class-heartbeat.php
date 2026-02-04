<?php
/**
 * Heartbeat Control Class
 *
 * WordPress heartbeat API control and optimization
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Heartbeat class
 */
class WPSB_Heartbeat {

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'wpsb_options', array() );
		add_action( 'init', array( $this, 'init' ), 1 );
		
		// AJAX handlers
		add_action( 'wp_ajax_wpspeed_heartbeat_get_stats', array( $this, 'ajax_get_stats' ) );
		add_action( 'wp_ajax_wpspeed_heartbeat_reset_stats', array( $this, 'ajax_reset_stats' ) );
		add_action( 'wp_ajax_wpspeed_heartbeat_test', array( $this, 'ajax_test_heartbeat' ) );
	}

	/**
	 * Check if heartbeat control is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['heartbeat_control_enabled'] );
	}

	/**
	 * Initialize heartbeat control
	 */
	public function init() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Complete disable
		if ( ! empty( $this->settings['heartbeat_disable_completely'] ) ) {
			$this->disable_heartbeat();
			return;
		}

		// Location-specific disable
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_disable_heartbeat' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_disable_heartbeat' ), 99 );

		// Modify frequency
		add_filter( 'heartbeat_settings', array( $this, 'modify_heartbeat_settings' ) );

		// Filter received data
		add_filter( 'heartbeat_received', array( $this, 'filter_heartbeat_received' ), 10, 3 );

		// Track activity
		if ( ! empty( $this->settings['heartbeat_track_activity'] ) ) {
			add_filter( 'heartbeat_received', array( $this, 'track_heartbeat' ), 10, 2 );
		}
	}

	/**
	 * Disable heartbeat completely
	 */
	public function disable_heartbeat() {
		wp_deregister_script( 'heartbeat' );
	}

	/**
	 * Maybe disable heartbeat based on location
	 */
	public function maybe_disable_heartbeat() {
		$location = $this->get_current_location();

		if ( $location === 'frontend' && ! empty( $this->settings['heartbeat_disable_frontend'] ) ) {
			wp_deregister_script( 'heartbeat' );
		} elseif ( $location === 'admin' && ! empty( $this->settings['heartbeat_disable_admin'] ) ) {
			wp_deregister_script( 'heartbeat' );
		} elseif ( $location === 'editor' && ! empty( $this->settings['heartbeat_disable_editor'] ) ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Modify heartbeat settings/frequency
	 *
	 * @param array $settings Heartbeat settings.
	 * @return array Modified settings.
	 */
	public function modify_heartbeat_settings( $settings ) {
		$location = $this->get_current_location();

		if ( $location === 'frontend' ) {
			$frequency = ! empty( $this->settings['heartbeat_frontend_frequency'] ) ?
				intval( $this->settings['heartbeat_frontend_frequency'] ) : 60;
		} elseif ( $location === 'admin' ) {
			$frequency = ! empty( $this->settings['heartbeat_admin_frequency'] ) ?
				intval( $this->settings['heartbeat_admin_frequency'] ) : 60;
		} elseif ( $location === 'editor' ) {
			$frequency = ! empty( $this->settings['heartbeat_editor_frequency'] ) ?
				intval( $this->settings['heartbeat_editor_frequency'] ) : 15;
		} else {
			$frequency = 60;
		}

		$settings['interval'] = $frequency;

		return $settings;
	}

	/**
	 * Get current location (frontend, admin, or editor)
	 *
	 * @return string
	 */
	public function get_current_location() {
		if ( is_admin() ) {
			global $pagenow;

			// Post editor
			if ( $this->is_post_editor() ) {
				return 'editor';
			}

			// Admin dashboard
			return 'admin';
		}

		// Frontend
		return 'frontend';
	}

	/**
	 * Check if we're in the post editor
	 *
	 * @return bool
	 */
	public function is_post_editor() {
		global $pagenow;
		return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	}

	/**
	 * Check if we're in admin dashboard
	 *
	 * @return bool
	 */
	public function is_admin_dashboard() {
		global $pagenow;
		return is_admin() && ! $this->is_post_editor();
	}

	/**
	 * Filter heartbeat received data
	 *
	 * @param array  $response  Heartbeat response.
	 * @param array  $data      Heartbeat data.
	 * @param string $screen_id Screen ID.
	 * @return array
	 */
	public function filter_heartbeat_received( $response, $data, $screen_id ) {
		// If both features are disabled, filter out all responses
		if ( empty( $this->settings['heartbeat_allow_post_locking'] ) && 
		     empty( $this->settings['heartbeat_allow_autosave'] ) ) {
			return array();
		}

		// If post locking is disabled, remove post locking data
		if ( empty( $this->settings['heartbeat_allow_post_locking'] ) && 
		     isset( $data['wp-refresh-post-lock'] ) ) {
			unset( $response['wp-refresh-post-lock'] );
		}

		// If autosave is disabled, remove autosave data
		if ( empty( $this->settings['heartbeat_allow_autosave'] ) && 
		     isset( $data['wp_autosave'] ) ) {
			unset( $response['wp_autosave'] );
		}

		return $response;
	}

	/**
	 * Track heartbeat activity
	 *
	 * @param array $response Heartbeat response.
	 * @param array $data     Heartbeat data.
	 * @return array
	 */
	public function track_heartbeat( $response, $data ) {
		$stats = get_option( 'wpspeed_heartbeat_stats', array(
			'total_requests'      => 0,
			'last_request'        => null,
			'location_breakdown'  => array(),
		) );

		$stats['total_requests']++;
		$stats['last_request'] = current_time( 'mysql' );

		$location = $this->get_current_location();
		if ( ! isset( $stats['location_breakdown'][ $location ] ) ) {
			$stats['location_breakdown'][ $location ] = 0;
		}
		$stats['location_breakdown'][ $location ]++;

		update_option( 'wpspeed_heartbeat_stats', $stats, false );

		return $response;
	}

	/**
	 * Get statistics
	 *
	 * @return array
	 */
	public function get_stats() {
		return get_option( 'wpspeed_heartbeat_stats', array(
			'total_requests'      => 0,
			'last_request'        => null,
			'location_breakdown'  => array(),
		) );
	}

	/**
	 * Reset statistics
	 */
	public function reset_stats() {
		update_option( 'wpspeed_heartbeat_stats', array(
			'total_requests'      => 0,
			'last_request'        => null,
			'location_breakdown'  => array(),
		), false );
	}

	/**
	 * AJAX handler to get stats
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'wpsb_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$stats = $this->get_stats();

		// Calculate additional metrics
		$stats['requests_per_minute'] = 0;
		if ( ! empty( $stats['last_request'] ) ) {
			$last_time = strtotime( $stats['last_request'] );
			$minutes_since = ( current_time( 'timestamp' ) - $last_time ) / 60;
			if ( $minutes_since > 0 && $stats['total_requests'] > 0 ) {
				$stats['requests_per_minute'] = round( $stats['total_requests'] / $minutes_since, 2 );
			}
		}

		wp_send_json_success( $stats );
	}

	/**
	 * AJAX handler to reset stats
	 */
	public function ajax_reset_stats() {
		check_ajax_referer( 'wpsb_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$this->reset_stats();
		wp_send_json_success( array( 'message' => 'Statistics reset successfully' ) );
	}

	/**
	 * AJAX handler to test heartbeat
	 */
	public function ajax_test_heartbeat() {
		check_ajax_referer( 'wpsb_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$location = $this->get_current_location();
		$frequency = 60;

		if ( $location === 'editor' && ! empty( $this->settings['heartbeat_editor_frequency'] ) ) {
			$frequency = intval( $this->settings['heartbeat_editor_frequency'] );
		} elseif ( $location === 'admin' && ! empty( $this->settings['heartbeat_admin_frequency'] ) ) {
			$frequency = intval( $this->settings['heartbeat_admin_frequency'] );
		} elseif ( $location === 'frontend' && ! empty( $this->settings['heartbeat_frontend_frequency'] ) ) {
			$frequency = intval( $this->settings['heartbeat_frontend_frequency'] );
		}

		wp_send_json_success( array(
			'location'   => $location,
			'frequency'  => $frequency,
			'enabled'    => $this->is_enabled(),
		) );
	}
}
