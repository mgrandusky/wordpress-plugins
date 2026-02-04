<?php
/**
 * AMP Support Class
 *
 * Accelerated Mobile Pages (AMP) optimizations
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_AMP class
 */
class VelocityWP_AMP {

	/**
	 * Is AMP request
	 *
	 * @var bool
	 */
	private $is_amp = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize AMP support
	 */
	public function init() {
		$this->is_amp = $this->is_amp_request();

		if ( ! $this->is_amp ) {
			return;
		}

		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['amp_support'] ) ) {
			return;
		}

		// Separate cache for AMP pages
		if ( ! empty( $options['amp_separate_cache'] ) ) {
			add_filter( 'velocitywp_cache_key', array( $this, 'add_amp_cache_key' ) );
		}

		// Optimize AMP pages
		add_action( 'amp_post_template_head', array( $this, 'optimize_amp_head' ) );
		add_filter( 'amp_post_template_data', array( $this, 'optimize_amp_data' ) );

		// Disable features not needed for AMP
		$this->disable_non_amp_features();
	}

	/**
	 * Check if current request is AMP
	 *
	 * @return bool Whether it's an AMP request.
	 */
	private function is_amp_request() {
		// Check for AMP plugin
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return true;
		}

		// Check URL parameter
		if ( isset( $_GET['amp'] ) || strpos( $_SERVER['REQUEST_URI'], '/amp/' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Add AMP suffix to cache key
	 *
	 * @param string $key Cache key.
	 * @return string Modified key.
	 */
	public function add_amp_cache_key( $key ) {
		return $key . '_amp';
	}

	/**
	 * Optimize AMP head
	 */
	public function optimize_amp_head() {
		// Remove unnecessary meta tags
		remove_action( 'amp_post_template_head', 'amp_post_template_add_schemaorg_metadata' );
		
		// Add critical CSS inline
		$this->add_amp_critical_css();
	}

	/**
	 * Add critical CSS for AMP
	 */
	private function add_amp_critical_css() {
		$options = get_option( 'velocitywp_options', array() );
		
		if ( empty( $options['amp_critical_css'] ) ) {
			return;
		}

		echo '<style amp-custom>';
		echo $options['amp_critical_css'];
		echo '</style>';
	}

	/**
	 * Optimize AMP data
	 *
	 * @param array $data AMP template data.
	 * @return array Modified data.
	 */
	public function optimize_amp_data( $data ) {
		// Remove unnecessary data
		unset( $data['comments'] );
		
		return $data;
	}

	/**
	 * Disable non-AMP features
	 */
	private function disable_non_amp_features() {
		// Disable features that conflict with AMP
		add_filter( 'velocitywp_js_delay_enabled', '__return_false' );
		add_filter( 'velocitywp_lazy_load_enabled', '__return_false' );
	}

	/**
	 * Convert image to AMP format
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function convert_images_to_amp( $content ) {
		// Convert img tags to amp-img
		$content = preg_replace_callback(
			'/<img([^>]+)>/',
			array( $this, 'replace_image_tag' ),
			$content
		);

		return $content;
	}

	/**
	 * Replace image tag with amp-img
	 *
	 * @param array $matches Regex matches.
	 * @return string Replacement tag.
	 */
	private function replace_image_tag( $matches ) {
		$attributes = $matches[1];

		// Extract width and height
		preg_match( '/width=["\']?(\d+)["\']?/', $attributes, $width_match );
		preg_match( '/height=["\']?(\d+)["\']?/', $attributes, $height_match );

		$width = ! empty( $width_match[1] ) ? $width_match[1] : 600;
		$height = ! empty( $height_match[1] ) ? $height_match[1] : 400;

		return '<amp-img' . $attributes . ' width="' . $width . '" height="' . $height . '" layout="responsive"></amp-img>';
	}

	/**
	 * Validate AMP page
	 *
	 * @param string $url URL to validate.
	 * @return bool|WP_Error Validation result.
	 */
	public function validate_amp_page( $url ) {
		$validator_url = 'https://validator.ampproject.org/v1/#url=' . urlencode( $url );

		$response = wp_remote_get( $validator_url, array(
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['status'] ) && $data['status'] === 'PASS' ) {
			return true;
		}

		$errors = ! empty( $data['errors'] ) ? $data['errors'] : array();
		return new WP_Error( 'amp_validation_failed', __( 'AMP validation failed', 'velocitywp' ), $errors );
	}
}
