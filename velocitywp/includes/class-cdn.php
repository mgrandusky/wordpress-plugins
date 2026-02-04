<?php
/**
 * CDN Integration Class
 *
 * Handles CDN URL replacement for static assets
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_CDN class
 */
class WPSB_CDN {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register hooks
		add_action( 'template_redirect', array( $this, 'init' ) );
	}

	/**
	 * Initialize CDN rewriting
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( is_admin() || empty( $options['cdn_enabled'] ) || empty( $options['cdn_url'] ) ) {
			return;
		}

		// Start output buffering to rewrite URLs
		ob_start( array( $this, 'rewrite_urls' ) );
	}

	/**
	 * Rewrite URLs to CDN
	 *
	 * @param string $content Page content.
	 * @return string Modified content.
	 */
	public function rewrite_urls( $content ) {
		$options = get_option( 'wpsb_options', array() );
		$cdn_url = rtrim( $options['cdn_url'], '/' );
		$site_url = rtrim( site_url(), '/' );

		// File extensions to rewrite
		$extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'otf' );
		$extensions_regex = implode( '|', $extensions );

		// Rewrite URLs in content
		$content = preg_replace_callback(
			'#(' . preg_quote( $site_url, '#' ) . '/[^"\'>\s]+\.(' . $extensions_regex . ')(["\'\s>]))#i',
			function ( $matches ) use ( $cdn_url, $site_url ) {
				return str_replace( $site_url, $cdn_url, $matches[0] );
			},
			$content
		);

		return $content;
	}

	/**
	 * Get CDN URL for a file
	 *
	 * @param string $url Original URL.
	 * @return string CDN URL.
	 */
	public function get_cdn_url( $url ) {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['cdn_enabled'] ) || empty( $options['cdn_url'] ) ) {
			return $url;
		}

		$cdn_url = rtrim( $options['cdn_url'], '/' );
		$site_url = rtrim( site_url(), '/' );

		return str_replace( $site_url, $cdn_url, $url );
	}
}
