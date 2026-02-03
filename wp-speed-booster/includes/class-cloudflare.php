<?php
/**
 * Cloudflare Integration Class
 *
 * Cloudflare API integration and cache management
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Cloudflare class
 */
class WPSB_Cloudflare {

	/**
	 * API endpoint
	 *
	 * @var string
	 */
	private $api_endpoint = 'https://api.cloudflare.com/client/v4/';

	/**
	 * API email
	 *
	 * @var string
	 */
	private $api_email = '';

	/**
	 * API key
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Zone ID
	 *
	 * @var string
	 */
	private $zone_id = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_cloudflare_purge', array( $this, 'ajax_purge_cache' ) );
		add_action( 'wp_ajax_wpsb_cloudflare_test', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Initialize Cloudflare integration
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['cloudflare_enable'] ) ) {
			return;
		}

		// Set API credentials
		$this->api_email = ! empty( $options['cloudflare_email'] ) ? $options['cloudflare_email'] : '';
		$this->api_key = ! empty( $options['cloudflare_api_key'] ) ? $options['cloudflare_api_key'] : '';
		$this->zone_id = ! empty( $options['cloudflare_zone_id'] ) ? $options['cloudflare_zone_id'] : '';

		// Auto-purge on content update
		if ( ! empty( $options['cloudflare_auto_purge'] ) ) {
			add_action( 'save_post', array( $this, 'auto_purge_post' ), 10, 2 );
			add_action( 'deleted_post', array( $this, 'auto_purge_cache' ) );
			add_action( 'comment_post', array( $this, 'auto_purge_comment' ) );
		}

		// Restore real visitor IP
		if ( ! empty( $options['cloudflare_restore_ip'] ) ) {
			add_action( 'init', array( $this, 'restore_visitor_ip' ), 1 );
		}
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method   HTTP method.
	 * @param array  $data     Request data.
	 * @return array|WP_Error Response or error.
	 */
	private function api_request( $endpoint, $method = 'GET', $data = array() ) {
		if ( empty( $this->api_email ) || empty( $this->api_key ) ) {
			return new WP_Error( 'missing_credentials', __( 'Cloudflare API credentials not configured', 'wp-speed-booster' ) );
		}

		$url = $this->api_endpoint . $endpoint;

		$args = array(
			'method'  => $method,
			'headers' => array(
				'X-Auth-Email' => $this->api_email,
				'X-Auth-Key'   => $this->api_key,
				'Content-Type' => 'application/json',
			),
			'timeout' => 30,
		);

		if ( ! empty( $data ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( ! empty( $result['success'] ) ) {
			return $result;
		}

		$error_message = ! empty( $result['errors'][0]['message'] ) ? $result['errors'][0]['message'] : __( 'Unknown error', 'wp-speed-booster' );
		return new WP_Error( 'api_error', $error_message );
	}

	/**
	 * Purge entire cache
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function purge_everything() {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'wp-speed-booster' ) );
		}

		$result = $this->api_request(
			'zones/' . $this->zone_id . '/purge_cache',
			'POST',
			array( 'purge_everything' => true )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		do_action( 'wpsb_cloudflare_purged_everything' );

		return true;
	}

	/**
	 * Purge specific URLs
	 *
	 * @param array $urls URLs to purge.
	 * @return bool|WP_Error Success or error.
	 */
	public function purge_urls( $urls ) {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'wp-speed-booster' ) );
		}

		if ( empty( $urls ) ) {
			return true;
		}

		// Cloudflare allows max 30 URLs per request
		$chunks = array_chunk( $urls, 30 );

		foreach ( $chunks as $chunk ) {
			$result = $this->api_request(
				'zones/' . $this->zone_id . '/purge_cache',
				'POST',
				array( 'files' => $chunk )
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		do_action( 'wpsb_cloudflare_purged_urls', $urls );

		return true;
	}

	/**
	 * Purge cache by tags
	 *
	 * @param array $tags Cache tags to purge.
	 * @return bool|WP_Error Success or error.
	 */
	public function purge_by_tags( $tags ) {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'wp-speed-booster' ) );
		}

		$result = $this->api_request(
			'zones/' . $this->zone_id . '/purge_cache',
			'POST',
			array( 'tags' => $tags )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Auto purge post cache
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function auto_purge_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( $post->post_status !== 'publish' ) {
			return;
		}

		$urls = $this->get_post_urls( $post_id );
		$this->purge_urls( $urls );
	}

	/**
	 * Auto purge cache on comment
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function auto_purge_comment( $comment_id ) {
		$comment = get_comment( $comment_id );
		
		if ( ! $comment || $comment->comment_approved !== '1' ) {
			return;
		}

		$urls = $this->get_post_urls( $comment->comment_post_ID );
		$this->purge_urls( $urls );
	}

	/**
	 * Auto purge entire cache
	 */
	public function auto_purge_cache() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['cloudflare_purge_everything'] ) ) {
			return;
		}

		$this->purge_everything();
	}

	/**
	 * Get URLs related to a post
	 *
	 * @param int $post_id Post ID.
	 * @return array URLs to purge.
	 */
	private function get_post_urls( $post_id ) {
		$urls = array();

		// Post URL
		$post_url = get_permalink( $post_id );
		if ( $post_url ) {
			$urls[] = $post_url;
		}

		// Home page
		$urls[] = home_url( '/' );

		// Post type archive
		$post_type = get_post_type( $post_id );
		$archive_url = get_post_type_archive_link( $post_type );
		if ( $archive_url ) {
			$urls[] = $archive_url;
		}

		// Category/tag archives
		$taxonomies = get_object_taxonomies( $post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_url = get_term_link( $term );
					if ( ! is_wp_error( $term_url ) ) {
						$urls[] = $term_url;
					}
				}
			}
		}

		// Author archive
		$author_id = get_post_field( 'post_author', $post_id );
		$urls[] = get_author_posts_url( $author_id );

		return apply_filters( 'wpsb_cloudflare_purge_urls', array_unique( $urls ), $post_id );
	}

	/**
	 * Restore real visitor IP from Cloudflare headers
	 */
	public function restore_visitor_ip() {
		if ( ! isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return;
		}

		$cf_ip = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );

		// Validate IP
		if ( filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) {
			$_SERVER['REMOTE_ADDR'] = $cf_ip;
		}
	}

	/**
	 * Get zone details
	 *
	 * @return array|WP_Error Zone details or error.
	 */
	public function get_zone_details() {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'wp-speed-booster' ) );
		}

		return $this->api_request( 'zones/' . $this->zone_id );
	}

	/**
	 * Test API connection
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function test_connection() {
		$result = $this->api_request( 'user' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * AJAX handler to purge cache
	 */
	public function ajax_purge_cache() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$result = $this->purge_everything();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Cloudflare cache purged successfully', 'wp-speed-booster' ) ) );
	}

	/**
	 * AJAX handler to test connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$result = $this->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Cloudflare connection successful', 'wp-speed-booster' ) ) );
	}
}
