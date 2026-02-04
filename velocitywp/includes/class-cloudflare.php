<?php
/**
 * Cloudflare Integration Class
 *
 * Cloudflare API integration and cache management
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Cloudflare class
 */
class VelocityWP_Cloudflare {

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
	 * API token (Bearer auth)
	 *
	 * @var string
	 */
	private $api_token = '';

	/**
	 * Authentication type (token or key)
	 *
	 * @var string
	 */
	private $auth_type = 'token';

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
		add_action( 'wp_ajax_velocitywp_cloudflare_purge', array( $this, 'ajax_purge_cache' ) );
		add_action( 'wp_ajax_velocitywp_cloudflare_test', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_velocitywp_cf_test_connection', array( $this, 'ajax_cf_test_connection' ) );
		add_action( 'wp_ajax_velocitywp_cf_get_zones', array( $this, 'ajax_cf_get_zones' ) );
		add_action( 'wp_ajax_velocitywp_cf_purge_cache', array( $this, 'ajax_cf_purge_cache' ) );
		add_action( 'wp_ajax_velocitywp_cf_toggle_dev_mode', array( $this, 'ajax_cf_toggle_dev_mode' ) );
		add_action( 'wp_ajax_velocitywp_cf_update_setting', array( $this, 'ajax_cf_update_setting' ) );
		add_action( 'wp_ajax_velocitywp_cf_get_analytics', array( $this, 'ajax_cf_get_analytics' ) );
	}

	/**
	 * Initialize Cloudflare integration
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['cloudflare_enabled'] ) ) {
			return;
		}

		// Set authentication type
		$this->auth_type = ! empty( $options['cloudflare_auth_type'] ) ? $options['cloudflare_auth_type'] : 'key';

		// Set API credentials based on auth type
		if ( $this->auth_type === 'token' ) {
			$this->api_token = ! empty( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';
		} else {
			$this->api_email = ! empty( $options['cloudflare_email'] ) ? $options['cloudflare_email'] : '';
			$this->api_key = ! empty( $options['cloudflare_api_key'] ) ? $options['cloudflare_api_key'] : '';
		}

		$this->zone_id = ! empty( $options['cloudflare_zone_id'] ) ? $options['cloudflare_zone_id'] : '';

		// Auto-purge on content update
		if ( ! empty( $options['cloudflare_purge_on_update'] ) ) {
			add_action( 'save_post', array( $this, 'auto_purge_post' ), 10, 2 );
			add_action( 'deleted_post', array( $this, 'auto_purge_cache' ) );
		}

		// Auto-purge on comments
		if ( ! empty( $options['cloudflare_purge_on_comment'] ) ) {
			add_action( 'comment_post', array( $this, 'auto_purge_comment' ) );
			add_action( 'edit_comment', array( $this, 'auto_purge_comment' ) );
			add_action( 'transition_comment_status', array( $this, 'auto_purge_comment_status' ), 10, 3 );
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
		// Check credentials based on auth type
		if ( $this->auth_type === 'token' ) {
			if ( empty( $this->api_token ) ) {
				return new WP_Error( 'missing_credentials', __( 'Cloudflare API token not configured', 'velocitywp' ) );
			}
		} else {
			if ( empty( $this->api_email ) || empty( $this->api_key ) ) {
				return new WP_Error( 'missing_credentials', __( 'Cloudflare API credentials not configured', 'velocitywp' ) );
			}
		}

		$url = $this->api_endpoint . $endpoint;

		$headers = array(
			'Content-Type' => 'application/json',
		);

		// Add authentication headers based on type
		if ( $this->auth_type === 'token' ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_token;
		} else {
			$headers['X-Auth-Email'] = $this->api_email;
			$headers['X-Auth-Key'] = $this->api_key;
		}

		$args = array(
			'method'  => $method,
			'headers' => $headers,
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

		$error_message = ! empty( $result['errors'][0]['message'] ) ? $result['errors'][0]['message'] : __( 'Unknown error', 'velocitywp' );
		return new WP_Error( 'api_error', $error_message );
	}

	/**
	 * Purge entire cache
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function purge_everything() {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
		}

		$result = $this->api_request(
			'zones/' . $this->zone_id . '/purge_cache',
			'POST',
			array( 'purge_everything' => true )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		do_action( 'velocitywp_cloudflare_purged_everything' );

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
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
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

		do_action( 'velocitywp_cloudflare_purged_urls', $urls );

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
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
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
	 * Auto purge cache on comment status change
	 *
	 * @param string $new_status New comment status.
	 * @param string $old_status Old comment status.
	 * @param object $comment Comment object.
	 */
	public function auto_purge_comment_status( $new_status, $old_status, $comment ) {
		if ( $new_status === 'approved' ) {
			$urls = $this->get_post_urls( $comment->comment_post_ID );
			$this->purge_urls( $urls );
		}
	}

	/**
	 * Auto purge entire cache
	 */
	public function auto_purge_cache() {
		$options = get_option( 'velocitywp_options', array() );

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

		return apply_filters( 'velocitywp_cloudflare_purge_urls', array_unique( $urls ), $post_id );
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
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
		}

		return $this->api_request( 'zones/' . $this->zone_id );
	}

	/**
	 * Test API connection
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function test_connection() {
		// Use appropriate endpoint based on auth type
		$endpoint = ( $this->auth_type === 'token' ) ? 'user/tokens/verify' : 'user';
		$result = $this->api_request( $endpoint );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Get list of zones
	 *
	 * @return array|WP_Error Zones or error.
	 */
	public function get_zones() {
		return $this->api_request( 'zones' );
	}

	/**
	 * Get zone settings
	 *
	 * @return array|WP_Error Settings or error.
	 */
	public function get_zone_settings() {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
		}

		return $this->api_request( "zones/{$this->zone_id}/settings" );
	}

	/**
	 * Update zone setting
	 *
	 * @param string $setting Setting ID.
	 * @param mixed  $value   Setting value.
	 * @return array|WP_Error Result or error.
	 */
	public function update_zone_setting( $setting, $value ) {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
		}

		return $this->api_request( "zones/{$this->zone_id}/settings/{$setting}", 'PATCH', $value );
	}

	/**
	 * Enable development mode
	 *
	 * @return array|WP_Error Result or error.
	 */
	public function enable_dev_mode() {
		return $this->update_zone_setting( 'development_mode', array( 'value' => 'on' ) );
	}

	/**
	 * Disable development mode
	 *
	 * @return array|WP_Error Result or error.
	 */
	public function disable_dev_mode() {
		return $this->update_zone_setting( 'development_mode', array( 'value' => 'off' ) );
	}

	/**
	 * Get development mode status
	 *
	 * @return array|WP_Error Status or error.
	 */
	public function get_dev_mode_status() {
		$settings = $this->get_zone_settings();

		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		if ( ! empty( $settings['result'] ) ) {
			foreach ( $settings['result'] as $setting ) {
				if ( $setting['id'] === 'development_mode' ) {
					return array(
						'enabled'        => $setting['value'] === 'on',
						'time_remaining' => ! empty( $setting['time_remaining'] ) ? $setting['time_remaining'] : 0,
					);
				}
			}
		}

		return new WP_Error( 'setting_not_found', __( 'Development mode setting not found', 'velocitywp' ) );
	}

	/**
	 * Check if APO is enabled
	 *
	 * @return bool
	 */
	public function is_apo_enabled() {
		// Check for Cloudflare APO plugin
		if ( defined( 'CLOUDFLARE_APO_VERSION' ) ) {
			return true;
		}

		// Check via API
		$settings = $this->get_zone_settings();
		if ( ! is_wp_error( $settings ) && ! empty( $settings['result'] ) ) {
			foreach ( $settings['result'] as $setting ) {
				if ( $setting['id'] === 'automatic_platform_optimization' ) {
					return ! empty( $setting['value']['enabled'] );
				}
			}
		}

		return false;
	}

	/**
	 * Get analytics
	 *
	 * @param int $period Number of days.
	 * @return array|WP_Error Analytics or error.
	 */
	public function get_analytics( $period = 30 ) {
		if ( empty( $this->zone_id ) ) {
			return new WP_Error( 'missing_zone_id', __( 'Cloudflare Zone ID not configured', 'velocitywp' ) );
		}

		$since = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( "-{$period} days" ) );
		$until = gmdate( 'Y-m-d\TH:i:s\Z' );

		return $this->api_request( "zones/{$this->zone_id}/analytics/dashboard?since={$since}&until={$until}" );
	}

	/**
	 * AJAX handler to purge cache
	 */
	public function ajax_purge_cache() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$result = $this->purge_everything();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Cloudflare cache purged successfully', 'velocitywp' ) ) );
	}

	/**
	 * AJAX handler to test connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$result = $this->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Cloudflare connection successful', 'velocitywp' ) ) );
	}

	/**
	 * AJAX: Test connection with provided credentials
	 */
	public function ajax_cf_test_connection() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}

		// Temporarily set credentials from POST
		$post_data = wp_unslash( $_POST );
		$auth_type = sanitize_text_field( $post_data['auth_type'] ?? 'token' );
		$saved_auth_type = $this->auth_type;
		$saved_token = $this->api_token;
		$saved_email = $this->api_email;
		$saved_key = $this->api_key;

		$this->auth_type = $auth_type;

		if ( $auth_type === 'token' ) {
			$this->api_token = sanitize_text_field( $post_data['api_token'] ?? '' );
		} else {
			$this->api_email = sanitize_email( $post_data['email'] ?? '' );
			$this->api_key = sanitize_text_field( $post_data['api_key'] ?? '' );
		}

		$result = $this->test_connection();

		// Restore original credentials
		$this->auth_type = $saved_auth_type;
		$this->api_token = $saved_token;
		$this->api_email = $saved_email;
		$this->api_key = $saved_key;

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connection successful!', 'velocitywp' ) ) );
	}

	/**
	 * AJAX: Get zones
	 */
	public function ajax_cf_get_zones() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}

		$result = $this->get_zones();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Purge cache with options
	 */
	public function ajax_cf_purge_cache() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}

		$post_data = wp_unslash( $_POST );
		$type = sanitize_text_field( $post_data['type'] ?? 'everything' );
		$result = null;

		switch ( $type ) {
			case 'everything':
				$result = $this->purge_everything();
				break;

			case 'urls':
				$urls_text = sanitize_textarea_field( $post_data['urls'] ?? '' );
				$urls = array_filter( array_map( 'trim', explode( "\n", $urls_text ) ) );
				$result = $this->purge_urls( $urls );
				break;

			case 'tags':
				$tags_text = sanitize_textarea_field( $post_data['tags'] ?? '' );
				$tags = array_filter( array_map( 'trim', explode( "\n", $tags_text ) ) );
				$result = $this->purge_by_tags( $tags );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid purge type', 'velocitywp' ) ) );
				return;
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Update last purge time
		update_option( 'velocitywp_cf_last_purge', current_time( 'timestamp' ) );

		wp_send_json_success( array( 'message' => __( 'Cache purged successfully', 'velocitywp' ) ) );
	}

	/**
	 * AJAX: Toggle development mode
	 */
	public function ajax_cf_toggle_dev_mode() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}

		$post_data = wp_unslash( $_POST );
		$action = sanitize_text_field( $post_data['action_type'] ?? 'enable' );

		if ( $action === 'enable' ) {
			$result = $this->enable_dev_mode();
		} else {
			$result = $this->disable_dev_mode();
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Development mode updated', 'velocitywp' ) ) );
	}

	/**
	 * AJAX: Update zone setting
	 */
	public function ajax_cf_update_setting() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}

		$post_data = wp_unslash( $_POST );
		$setting_id = sanitize_text_field( $post_data['setting_id'] ?? '' );
		$value = isset( $post_data['value'] ) ? sanitize_text_field( $post_data['value'] ) : '';

		if ( empty( $setting_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Setting ID required', 'velocitywp' ) ) );
		}

		$result = $this->update_zone_setting( $setting_id, array( 'value' => $value ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Setting updated successfully', 'velocitywp' ) ) );
	}

	/**
	 * AJAX: Get analytics
	 */
	public function ajax_cf_get_analytics() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}

		$post_data = wp_unslash( $_POST );
		$period = absint( $post_data['period'] ?? 30 );
		$result = $this->get_analytics( $period );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}
}
