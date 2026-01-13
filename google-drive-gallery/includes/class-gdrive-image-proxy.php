<?php
/**
 * Image Proxy for Google Drive
 * Handles authenticated image requests
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GDrive_Image_Proxy
 * Proxies image requests through WordPress to handle Google Drive authentication
 */
class GDrive_Image_Proxy {

	/**
	 * Initialize the proxy
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_endpoint' ] );
		add_action( 'template_redirect', [ __CLASS__, 'handle_request' ] );
	}

	/**
	 * Register rewrite endpoint for image proxy
	 */
	public static function register_endpoint() {
		add_rewrite_rule(
			'^gdrive-image/([^/]+)/?$',
			'index.php?gdrive_image_id=$matches[1]',
			'top'
		);
		add_rewrite_tag( '%gdrive_image_id%', '([^&]+)' );
	}

	/**
	 * Handle image proxy request
	 */
	public static function handle_request() {
		$file_id = get_query_var( 'gdrive_image_id' );

		if ( empty( $file_id ) ) {
			return;
		}

		// Sanitize file ID
		$file_id = sanitize_text_field( $file_id );

		// Get access token
		$access_token = GDrive_API::get_access_token();

		if ( is_wp_error( $access_token ) ) {
			status_header( 403 );
			wp_die( esc_html__( 'Access forbidden', 'google-drive-gallery' ), 403 );
		}

		// Check cache first
		$cache_key = 'gdrive_image_' . md5( $file_id );
		$cached_image = get_transient( $cache_key );

		if ( false !== $cached_image && is_array( $cached_image ) ) {
			// Serve from cache
			header( 'Content-Type: ' . $cached_image['content_type'] );
			header( 'Cache-Control: public, max-age=31536000' );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 31536000 ) . ' GMT' );
			echo $cached_image['body'];
			exit;
		}

		// Fetch image from Google Drive
		$url = 'https://www.googleapis.com/drive/v3/files/' . $file_id . '?alt=media';

		$response = wp_remote_get( $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
			],
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			status_header( 404 );
			wp_die( esc_html__( 'Image not found', 'google-drive-gallery' ), 404 );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			status_header( $code );
			wp_die( esc_html__( 'Failed to fetch image', 'google-drive-gallery' ), $code );
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$body = wp_remote_retrieve_body( $response );

		// Cache the image for 1 hour
		set_transient( $cache_key, [
			'content_type' => $content_type,
			'body' => $body,
		], HOUR_IN_SECONDS );

		// Serve the image
		header( 'Content-Type: ' . $content_type );
		header( 'Cache-Control: public, max-age=31536000' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 31536000 ) . ' GMT' );
		echo $body;
		exit;
	}
}
