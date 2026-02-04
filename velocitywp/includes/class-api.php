<?php
/**
 * API Class
 *
 * REST API and webhooks for external integrations
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_API class
 */
class VelocityWP_API {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'wpsb/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Clear cache endpoint
		register_rest_route( $this->namespace, '/cache/clear', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'clear_cache' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get statistics endpoint
		register_rest_route( $this->namespace, '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_statistics' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Preload cache endpoint
		register_rest_route( $this->namespace, '/cache/preload', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'preload_cache' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get settings endpoint
		register_rest_route( $this->namespace, '/settings', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_settings' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Update settings endpoint
		register_rest_route( $this->namespace, '/settings', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'update_settings' ),
			'permission_callback' => array( $this, 'check_permissions' ),
			'args'                => array(
				'settings' => array(
					'required' => true,
					'type'     => 'object',
				),
			),
		) );

		// Optimize image endpoint
		register_rest_route( $this->namespace, '/image/optimize/(?P<id>\d+)', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'optimize_image' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Health check endpoint
		register_rest_route( $this->namespace, '/health', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'health_check' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Check API permissions
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool Whether user has permission.
	 */
	public function check_permissions( $request ) {
		// Check for API key
		$api_key = $request->get_header( 'X-WPSB-API-Key' );
		
		if ( ! empty( $api_key ) ) {
			$options = get_option( 'velocitywp_options', array() );
			$stored_key = ! empty( $options['api_key'] ) ? $options['api_key'] : '';
			
			if ( ! empty( $stored_key ) && hash_equals( $stored_key, $api_key ) ) {
				return true;
			}
		}

		// Fall back to WordPress capabilities
		return current_user_can( 'manage_options' );
	}

	/**
	 * Clear cache endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function clear_cache( $request ) {
		$type = $request->get_param( 'type' );

		switch ( $type ) {
			case 'page':
				$url = $request->get_param( 'url' );
				$result = $this->clear_page_cache( $url );
				break;

			case 'all':
			default:
				$result = $this->clear_all_cache();
				break;
		}

		if ( $result ) {
			return new WP_REST_Response( array(
				'success' => true,
				'message' => __( 'Cache cleared successfully', 'velocitywp' ),
			), 200 );
		}

		return new WP_REST_Response( array(
			'success' => false,
			'message' => __( 'Failed to clear cache', 'velocitywp' ),
		), 500 );
	}

	/**
	 * Get statistics endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_statistics( $request ) {
		$stats = array(
			'cache'       => $this->get_cache_stats(),
			'performance' => $this->get_performance_stats(),
			'images'      => $this->get_image_stats(),
		);

		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $stats,
		), 200 );
	}

	/**
	 * Preload cache endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function preload_cache( $request ) {
		$urls = $request->get_param( 'urls' );

		if ( empty( $urls ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'No URLs provided', 'velocitywp' ),
			), 400 );
		}

		// Start preload in background
		wp_schedule_single_event( time(), 'velocitywp_preload_cache' );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Preload started', 'velocitywp' ),
		), 200 );
	}

	/**
	 * Get settings endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_settings( $request ) {
		$options = get_option( 'velocitywp_options', array() );

		// Remove sensitive data
		unset( $options['api_key'] );
		unset( $options['cloudflare_api_key'] );

		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $options,
		), 200 );
	}

	/**
	 * Update settings endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function update_settings( $request ) {
		$settings = $request->get_param( 'settings' );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'Invalid settings provided', 'velocitywp' ),
			), 400 );
		}

		// Merge with existing settings
		$current_settings = get_option( 'velocitywp_options', array() );
		$new_settings = array_merge( $current_settings, $settings );

		// Update settings
		$result = update_option( 'velocitywp_options', $new_settings );

		if ( $result ) {
			return new WP_REST_Response( array(
				'success' => true,
				'message' => __( 'Settings updated successfully', 'velocitywp' ),
			), 200 );
		}

		return new WP_REST_Response( array(
			'success' => false,
			'message' => __( 'Failed to update settings', 'velocitywp' ),
		), 500 );
	}

	/**
	 * Optimize image endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function optimize_image( $request ) {
		$image_id = $request->get_param( 'id' );

		if ( empty( $image_id ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'Invalid image ID', 'velocitywp' ),
			), 400 );
		}

		// Trigger image optimization
		do_action( 'velocitywp_optimize_image', $image_id );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Image optimization started', 'velocitywp' ),
		), 200 );
	}

	/**
	 * Health check endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function health_check( $request ) {
		$health = array(
			'status'  => 'healthy',
			'version' => VelocityWP_VERSION,
			'php'     => PHP_VERSION,
			'wp'      => get_bloginfo( 'version' ),
			'time'    => current_time( 'mysql' ),
		);

		return new WP_REST_Response( $health, 200 );
	}

	/**
	 * Clear all cache
	 *
	 * @return bool Success.
	 */
	private function clear_all_cache() {
		do_action( 'velocitywp_clear_cache' );
		return true;
	}

	/**
	 * Clear page cache
	 *
	 * @param string $url URL to clear.
	 * @return bool Success.
	 */
	private function clear_page_cache( $url ) {
		do_action( 'velocitywp_clear_page_cache', $url );
		return true;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats.
	 */
	private function get_cache_stats() {
		$analytics = get_option( 'velocitywp_cache_analytics', array() );
		
		return array(
			'hits'      => ! empty( $analytics['total_hits'] ) ? $analytics['total_hits'] : 0,
			'misses'    => ! empty( $analytics['total_misses'] ) ? $analytics['total_misses'] : 0,
			'hit_ratio' => ! empty( $analytics['hit_ratio'] ) ? $analytics['hit_ratio'] : 0,
		);
	}

	/**
	 * Get performance statistics
	 *
	 * @return array Performance stats.
	 */
	private function get_performance_stats() {
		$analytics = get_option( 'velocitywp_cache_analytics', array() );
		
		return array(
			'avg_generation_time' => ! empty( $analytics['avg_generation_time'] ) ? $analytics['avg_generation_time'] : 0,
			'total_pages'         => ! empty( $analytics['total_pages'] ) ? $analytics['total_pages'] : 0,
		);
	}

	/**
	 * Get image statistics
	 *
	 * @return array Image stats.
	 */
	private function get_image_stats() {
		$stats = get_option( 'velocitywp_image_stats', array() );
		
		return array(
			'optimized' => ! empty( $stats['optimized'] ) ? $stats['optimized'] : 0,
			'saved'     => ! empty( $stats['saved_bytes'] ) ? $stats['saved_bytes'] : 0,
		);
	}

	/**
	 * Generate API key
	 *
	 * @return string API key.
	 */
	public function generate_api_key() {
		return wp_generate_password( 32, false );
	}
}
