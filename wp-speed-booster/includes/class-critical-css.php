<?php
/**
 * Critical CSS Generation Class
 *
 * Automatically extracts and inlines above-the-fold CSS
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Critical_CSS class
 */
class WPSB_Critical_CSS {

	/**
	 * Constructor
	 */
	public function __construct() {
		$options = get_option( 'wpsb_options', array() );
		
		if ( ! empty( $options['critical_css_enabled'] ) ) {
			add_action( 'wp_head', array( $this, 'inline_critical_css' ), 1 );
			add_filter( 'style_loader_tag', array( $this, 'defer_non_critical_css' ), 10, 4 );
		}

		// AJAX handlers
		add_action( 'wp_ajax_wpsb_generate_critical_css', array( $this, 'ajax_generate_critical_css' ) );
		add_action( 'wp_ajax_wpsb_clear_critical_css', array( $this, 'ajax_clear_critical_css' ) );
	}

	/**
	 * Inline critical CSS in head
	 */
	public function inline_critical_css() {
		$critical_css = $this->get_critical_css();
		
		if ( ! empty( $critical_css ) ) {
			echo '<style id="wpsb-critical-css">' . $critical_css . '</style>' . "\n";
		}
	}

	/**
	 * Get critical CSS for current page
	 *
	 * @return string Critical CSS content
	 */
	private function get_critical_css() {
		$options = get_option( 'wpsb_options', array() );
		$page_id = get_queried_object_id();
		$page_type = $this->get_page_type();
		
		// Check for manual critical CSS first
		if ( ! empty( $options['critical_css_manual'] ) ) {
			return $options['critical_css_manual'];
		}
		
		// Check cache for auto-generated critical CSS
		$cache_key = 'wpsb_critical_css_' . $page_type . '_' . $page_id;
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		return '';
	}

	/**
	 * Get page type for critical CSS caching
	 *
	 * @return string Page type
	 */
	private function get_page_type() {
		if ( is_front_page() ) {
			return 'front_page';
		} elseif ( is_home() ) {
			return 'blog';
		} elseif ( is_single() ) {
			return 'single_' . get_post_type();
		} elseif ( is_page() ) {
			return 'page';
		} elseif ( is_category() ) {
			return 'category';
		} elseif ( is_archive() ) {
			return 'archive';
		}
		return 'default';
	}

	/**
	 * Defer non-critical CSS
	 *
	 * @param string $tag    The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @param string $href   The stylesheet's source URL.
	 * @param string $media  The stylesheet's media attribute.
	 * @return string Modified tag
	 */
	public function defer_non_critical_css( $tag, $handle, $href, $media ) {
		$options = get_option( 'wpsb_options', array() );
		
		if ( empty( $options['critical_css_defer'] ) ) {
			return $tag;
		}
		
		// Don't defer critical stylesheets
		$critical_handles = array( 'admin-bar' );
		if ( in_array( $handle, $critical_handles, true ) ) {
			return $tag;
		}
		
		// Defer CSS using media swap technique
		$tag = str_replace( "media='$media'", "media='print' onload=\"this.media='$media'\"", $tag );
		$tag = str_replace( 'media="' . $media . '"', 'media="print" onload="this.media=\'' . $media . '\'"', $tag );
		
		return $tag;
	}

	/**
	 * Generate critical CSS via AJAX
	 */
	public function ajax_generate_critical_css() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : home_url();
		
		// In a real implementation, this would use headless browser
		// For now, we'll use a simplified approach
		$critical_css = $this->extract_critical_css( $url );
		
		if ( $critical_css ) {
			$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
			$page_type = isset( $_POST['page_type'] ) ? sanitize_text_field( $_POST['page_type'] ) : 'default';
			
			$cache_key = 'wpsb_critical_css_' . $page_type . '_' . $page_id;
			set_transient( $cache_key, $critical_css, WEEK_IN_SECONDS );
			
			wp_send_json_success( array(
				'message' => __( 'Critical CSS generated successfully', 'wp-speed-booster' ),
				'css' => $critical_css,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to generate critical CSS', 'wp-speed-booster' ) ) );
		}
	}

	/**
	 * Extract critical CSS from URL
	 *
	 * @param string $url URL to extract critical CSS from.
	 * @return string|false Critical CSS or false on failure
	 */
	private function extract_critical_css( $url ) {
		// This is a simplified implementation
		// In production, you would use a headless browser or external service
		
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$html = wp_remote_retrieve_body( $response );
		
		// Extract inline styles and linked stylesheets
		// This is a basic implementation - real critical CSS extraction is complex
		preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $inline_styles );
		
		$critical_css = '';
		if ( ! empty( $inline_styles[1] ) ) {
			$critical_css = implode( "\n", $inline_styles[1] );
		}
		
		// Minify
		$critical_css = $this->minify_css( $critical_css );
		
		return $critical_css;
	}

	/**
	 * Minify CSS
	 *
	 * @param string $css CSS to minify.
	 * @return string Minified CSS
	 */
	private function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s?([\{\};:,])\s?/', '$1', $css );
		
		return trim( $css );
	}

	/**
	 * Clear critical CSS cache via AJAX
	 */
	public function ajax_clear_critical_css() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpsb_critical_css_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wpsb_critical_css_%'" );
		
		wp_send_json_success( array( 'message' => __( 'Critical CSS cache cleared', 'wp-speed-booster' ) ) );
	}
}
