<?php
/**
 * Cache Preloading Class
 *
 * Handles cache preloading functionality
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Preload class
 */
class WPSB_Preload {

	/**
	 * URLs to preload
	 *
	 * @var array
	 */
	private $urls = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register AJAX handlers
		add_action( 'wp_ajax_wpsb_preload_cache', array( $this, 'ajax_preload_cache' ) );
	}

	/**
	 * Preload cache
	 *
	 * @return array Results.
	 */
	public function preload_cache() {
		$this->collect_urls();

		$results = array(
			'success' => 0,
			'failed'  => 0,
			'total'   => count( $this->urls ),
		);

		foreach ( $this->urls as $url ) {
			$response = wp_remote_get( $url, array(
				'timeout'    => 30,
				'sslverify'  => false,
				'user-agent' => 'WP Speed Booster Cache Preloader',
			) );

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				$results['success']++;
			} else {
				$results['failed']++;
			}

			// Avoid overwhelming the server
			usleep( 500000 ); // 0.5 second delay
		}

		return $results;
	}

	/**
	 * Collect URLs to preload
	 */
	private function collect_urls() {
		$this->urls = array();

		// Homepage
		$this->urls[] = home_url( '/' );

		// Recent posts
		$posts = get_posts( array(
			'numberposts' => 50,
			'post_status' => 'publish',
		) );

		foreach ( $posts as $post ) {
			$this->urls[] = get_permalink( $post->ID );
		}

		// Recent pages
		$pages = get_pages( array(
			'number'      => 20,
			'post_status' => 'publish',
		) );

		foreach ( $pages as $page ) {
			$this->urls[] = get_permalink( $page->ID );
		}

		// Categories
		$categories = get_categories( array(
			'number' => 10,
		) );

		foreach ( $categories as $category ) {
			$this->urls[] = get_category_link( $category->term_id );
		}

		// Remove duplicates
		$this->urls = array_unique( $this->urls );

		// Allow filtering
		$this->urls = apply_filters( 'wpsb_preload_urls', $this->urls );
	}

	/**
	 * AJAX handler for cache preloading
	 */
	public function ajax_preload_cache() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-speed-booster' ) ) );
		}

		$results = $this->preload_cache();

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: success count, 2: total count */
				__( 'Preloaded %1$d of %2$d URLs successfully.', 'wp-speed-booster' ),
				$results['success'],
				$results['total']
			),
			'results' => $results,
		) );
	}

	/**
	 * Get preload status
	 *
	 * @return array Status information.
	 */
	public function get_status() {
		$this->collect_urls();

		return array(
			'urls_count' => count( $this->urls ),
			'urls'       => array_slice( $this->urls, 0, 10 ), // First 10 URLs as preview
		);
	}
}
