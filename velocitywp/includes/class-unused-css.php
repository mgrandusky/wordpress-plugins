<?php
/**
 * Unused CSS Class
 *
 * Detect and remove unused CSS
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Unused_CSS class
 */
class VelocityWP_Unused_CSS {

	/**
	 * Used selectors
	 *
	 * @var array
	 */
	private $used_selectors = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_analyze_css', array( $this, 'ajax_analyze_css' ) );
	}

	/**
	 * Initialize unused CSS removal
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['remove_unused_css'] ) ) {
			return;
		}

		add_filter( 'style_loader_tag', array( $this, 'optimize_css' ), 10, 4 );
	}

	/**
	 * Optimize CSS by removing unused rules
	 *
	 * @param string $tag    Style tag.
	 * @param string $handle Style handle.
	 * @param string $href   Style URL.
	 * @param string $media  Media attribute.
	 * @return string Modified tag.
	 */
	public function optimize_css( $tag, $handle, $href, $media ) {
		$options = get_option( 'velocitywp_options', array() );

		// Skip if handle is excluded
		if ( ! empty( $options['unused_css_exclusions'] ) ) {
			$exclusions = array_map( 'trim', explode( "\n", $options['unused_css_exclusions'] ) );
			
			foreach ( $exclusions as $exclusion ) {
				if ( strpos( $handle, $exclusion ) !== false ) {
					return $tag;
				}
			}
		}

		// Get optimized CSS
		$optimized_css = $this->get_optimized_css( $href, $handle );

		if ( empty( $optimized_css ) ) {
			return $tag;
		}

		// Replace with inline CSS
		return '<style id="' . esc_attr( $handle ) . '-inline-css">' . $optimized_css . '</style>' . "\n";
	}

	/**
	 * Get optimized CSS for a stylesheet
	 *
	 * @param string $href   Stylesheet URL.
	 * @param string $handle Stylesheet handle.
	 * @return string Optimized CSS.
	 */
	private function get_optimized_css( $href, $handle ) {
		$cache_key = 'velocitywp_optimized_css_' . md5( $href );
		
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		// Get CSS content
		$css = $this->get_css_content( $href );

		if ( empty( $css ) ) {
			return '';
		}

		// Analyze and remove unused CSS
		$optimized = $this->remove_unused_rules( $css );

		// Cache result
		set_transient( $cache_key, $optimized, DAY_IN_SECONDS );

		return $optimized;
	}

	/**
	 * Get CSS content from URL
	 *
	 * @param string $url CSS URL.
	 * @return string CSS content.
	 */
	private function get_css_content( $url ) {
		// Convert URL to file path
		$file_path = $this->url_to_path( $url );

		if ( $file_path && file_exists( $file_path ) ) {
			return file_get_contents( $file_path );
		}

		// Try to fetch remotely
		$response = wp_remote_get( $url, array(
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Convert URL to file path
	 *
	 * @param string $url URL.
	 * @return string|false File path or false.
	 */
	private function url_to_path( $url ) {
		$url = strtok( $url, '?' );

		$replacements = array(
			content_url()                  => WP_CONTENT_DIR,
			includes_url()                 => ABSPATH . WPINC,
			get_stylesheet_directory_uri() => get_stylesheet_directory(),
			get_template_directory_uri()   => get_template_directory(),
		);

		foreach ( $replacements as $base_url => $base_path ) {
			if ( strpos( $url, $base_url ) === 0 ) {
				return str_replace( $base_url, $base_path, $url );
			}
		}

		return false;
	}

	/**
	 * Remove unused CSS rules
	 *
	 * @param string $css CSS content.
	 * @return string Optimized CSS.
	 */
	private function remove_unused_rules( $css ) {
		// Get used selectors from current page
		$this->collect_used_selectors();

		// Parse CSS
		$rules = $this->parse_css( $css );

		// Filter unused rules
		$used_rules = array();
		foreach ( $rules as $rule ) {
			if ( $this->is_rule_used( $rule ) ) {
				$used_rules[] = $rule;
			}
		}

		// Rebuild CSS
		return $this->rebuild_css( $used_rules );
	}

	/**
	 * Parse CSS into rules
	 *
	 * @param string $css CSS content.
	 * @return array CSS rules.
	 */
	private function parse_css( $css ) {
		$rules = array();

		// Remove comments
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );

		// Match CSS rules
		preg_match_all( '/([^{]+)\{([^}]+)\}/', $css, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$selectors = array_map( 'trim', explode( ',', $match[1] ) );
			$declarations = trim( $match[2] );

			$rules[] = array(
				'selectors'    => $selectors,
				'declarations' => $declarations,
			);
		}

		return $rules;
	}

	/**
	 * Check if CSS rule is used
	 *
	 * @param array $rule CSS rule.
	 * @return bool Whether rule is used.
	 */
	private function is_rule_used( $rule ) {
		foreach ( $rule['selectors'] as $selector ) {
			// Always keep critical selectors
			if ( $this->is_critical_selector( $selector ) ) {
				return true;
			}

			// Check if selector is in used list
			if ( in_array( $selector, $this->used_selectors, true ) ) {
				return true;
			}

			// Check for partial matches
			foreach ( $this->used_selectors as $used ) {
				if ( strpos( $selector, $used ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if selector is critical
	 *
	 * @param string $selector CSS selector.
	 * @return bool Whether selector is critical.
	 */
	private function is_critical_selector( $selector ) {
		$critical = array(
			'body',
			'html',
			'*',
			'@media',
			'@keyframes',
			'@font-face',
		);

		foreach ( $critical as $pattern ) {
			if ( strpos( $selector, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Collect used selectors from current page
	 */
	private function collect_used_selectors() {
		// This would ideally use JavaScript to detect used selectors
		// For now, we'll use a conservative approach
		$this->used_selectors = $this->get_common_selectors();
	}

	/**
	 * Get common selectors that should always be kept
	 *
	 * @return array Common selectors.
	 */
	private function get_common_selectors() {
		return array(
			'body',
			'html',
			'a',
			'p',
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
			'div',
			'span',
			'img',
			'header',
			'footer',
			'nav',
			'main',
			'article',
			'section',
		);
	}

	/**
	 * Rebuild CSS from rules
	 *
	 * @param array $rules CSS rules.
	 * @return string Rebuilt CSS.
	 */
	private function rebuild_css( $rules ) {
		$css = '';

		foreach ( $rules as $rule ) {
			$selectors = implode( ', ', $rule['selectors'] );
			$css .= $selectors . ' { ' . $rule['declarations'] . ' }' . "\n";
		}

		return $css;
	}

	/**
	 * AJAX handler to analyze CSS
	 */
	public function ajax_analyze_css() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL', 'velocitywp' ) ) );
		}

		// Analyze CSS
		$result = $this->analyze_page_css( $url );

		wp_send_json_success( array( 'data' => $result ) );
	}

	/**
	 * Analyze CSS usage on a page
	 *
	 * @param string $url Page URL.
	 * @return array Analysis results.
	 */
	private function analyze_page_css( $url ) {
		// This would require headless browser analysis
		// Placeholder for future implementation
		return array(
			'total_css'   => 0,
			'used_css'    => 0,
			'unused_css'  => 0,
			'savings'     => 0,
		);
	}
}
