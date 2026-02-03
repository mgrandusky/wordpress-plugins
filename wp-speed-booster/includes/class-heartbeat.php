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
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize heartbeat control
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['heartbeat_control'] ) ) {
			return;
		}

		$mode = ! empty( $options['heartbeat_mode'] ) ? $options['heartbeat_mode'] : 'modify';

		switch ( $mode ) {
			case 'disable':
				$this->disable_heartbeat();
				break;
			
			case 'modify':
				$this->modify_heartbeat( $options );
				break;
			
			case 'allow_posts':
				$this->allow_posts_only();
				break;
		}

		// Modify heartbeat frequency
		if ( ! empty( $options['heartbeat_frequency'] ) ) {
			add_filter( 'heartbeat_settings', array( $this, 'modify_frequency' ) );
		}
	}

	/**
	 * Disable heartbeat completely
	 */
	private function disable_heartbeat() {
		add_action( 'init', function() {
			wp_deregister_script( 'heartbeat' );
		}, 1 );
	}

	/**
	 * Modify heartbeat based on context
	 *
	 * @param array $options Plugin options.
	 */
	private function modify_heartbeat( $options ) {
		// Disable on frontend
		if ( ! empty( $options['heartbeat_disable_frontend'] ) ) {
			add_action( 'wp_enqueue_scripts', function() {
				wp_deregister_script( 'heartbeat' );
			} );
		}

		// Disable in admin
		if ( ! empty( $options['heartbeat_disable_admin'] ) ) {
			add_action( 'admin_enqueue_scripts', function() {
				global $pagenow;
				
				// Allow on post edit pages
				if ( $pagenow !== 'post.php' && $pagenow !== 'post-new.php' ) {
					wp_deregister_script( 'heartbeat' );
				}
			} );
		}

		// Disable for dashboard
		if ( ! empty( $options['heartbeat_disable_dashboard'] ) ) {
			add_action( 'admin_enqueue_scripts', function() {
				global $pagenow;
				
				if ( $pagenow === 'index.php' ) {
					wp_deregister_script( 'heartbeat' );
				}
			} );
		}
	}

	/**
	 * Allow heartbeat only on post edit pages
	 */
	private function allow_posts_only() {
		add_action( 'admin_enqueue_scripts', function() {
			global $pagenow;
			
			if ( $pagenow !== 'post.php' && $pagenow !== 'post-new.php' ) {
				wp_deregister_script( 'heartbeat' );
			}
		} );

		add_action( 'wp_enqueue_scripts', function() {
			wp_deregister_script( 'heartbeat' );
		} );
	}

	/**
	 * Modify heartbeat frequency
	 *
	 * @param array $settings Heartbeat settings.
	 * @return array Modified settings.
	 */
	public function modify_frequency( $settings ) {
		$options = get_option( 'wpsb_options', array() );

		// Set different intervals for different contexts
		if ( is_admin() ) {
			$interval = ! empty( $options['heartbeat_admin_interval'] ) ? intval( $options['heartbeat_admin_interval'] ) : 60;
			$settings['interval'] = $interval;
		} else {
			$interval = ! empty( $options['heartbeat_frontend_interval'] ) ? intval( $options['heartbeat_frontend_interval'] ) : 120;
			$settings['interval'] = $interval;
		}

		return $settings;
	}

	/**
	 * Get heartbeat statistics
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		$stats = get_option( 'wpsb_heartbeat_stats', array(
			'total_requests' => 0,
			'last_request'   => '',
			'avg_response_time' => 0,
		) );

		return $stats;
	}

	/**
	 * Track heartbeat request
	 *
	 * @param array $response Heartbeat response.
	 * @param array $data     Heartbeat data.
	 * @return array Response.
	 */
	public function track_heartbeat( $response, $data ) {
		$stats = $this->get_statistics();
		
		$stats['total_requests']++;
		$stats['last_request'] = current_time( 'mysql' );

		update_option( 'wpsb_heartbeat_stats', $stats, false );

		return $response;
	}
}
