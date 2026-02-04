<?php
/**
 * Minification Class
 *
 * Handles HTML/CSS/JS minification
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Minify class
 */
class VelocityWP_Minify {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register hooks
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize minification
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		// HTML minification
		if ( ! empty( $options['html_minify'] ) && ! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'start_buffer' ), 2 );
		}

		// JavaScript defer/async
		if ( ! empty( $options['js_defer'] ) && ! is_admin() ) {
			add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );
		}

		// CSS/JS minification and combining
		if ( ( ! empty( $options['css_minify'] ) || ! empty( $options['js_minify'] ) ) && ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'process_assets' ), 999 );
		}
	}

	/**
	 * Start output buffering for HTML minification
	 */
	public function start_buffer() {
		ob_start( array( $this, 'minify_html' ) );
	}

	/**
	 * Minify HTML
	 *
	 * @param string $html HTML content.
	 * @return string Minified HTML.
	 */
	public function minify_html( $html ) {
		// Don't minify if already cached or AJAX
		if ( wp_doing_ajax() || empty( $html ) ) {
			return $html;
		}

		// Protect pre, textarea, script, and style tags
		$protect = array();
		$protect_tags = array( 'pre', 'textarea', 'script', 'style' );

		foreach ( $protect_tags as $tag ) {
			preg_match_all( '/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is', $html, $matches );
			foreach ( $matches[0] as $i => $match ) {
				$placeholder = '%%WPSB_PROTECTED_' . $tag . '_' . $i . '%%';
				$protect[ $placeholder ] = $match;
				$html = str_replace( $match, $placeholder, $html );
			}
		}

		// Remove HTML comments (except IE conditionals)
		$html = preg_replace( '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html );

		// Remove whitespace between HTML tags
		$html = preg_replace( '/>\s+</', '><', $html );

		// Remove multiple spaces
		$html = preg_replace( '/\s+/', ' ', $html );

		// Restore protected content
		$html = str_replace( array_keys( $protect ), array_values( $protect ), $html );

		return apply_filters( 'velocitywp_minify_html', $html );
	}

	/**
	 * Defer JavaScript loading
	 *
	 * @param string $tag Script tag.
	 * @param string $handle Script handle.
	 * @param string $src Script source.
	 * @return string Modified script tag.
	 */
	public function defer_scripts( $tag, $handle, $src ) {
		// Skip jQuery and some critical scripts
		$exclude = array( 'jquery', 'jquery-core', 'jquery-migrate' );
		if ( in_array( $handle, $exclude, true ) ) {
			return $tag;
		}

		// Check excluded files
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['minify_exclude_files'] ) ) {
			$excluded = explode( "\n", $options['minify_exclude_files'] );
			foreach ( $excluded as $pattern ) {
				$pattern = trim( $pattern );
				if ( ! empty( $pattern ) && strpos( $src, $pattern ) !== false ) {
					return $tag;
				}
			}
		}

		// Add defer attribute
		if ( strpos( $tag, 'defer' ) === false && strpos( $tag, 'async' ) === false ) {
			return str_replace( ' src=', ' defer src=', $tag );
		}

		return $tag;
	}

	/**
	 * Process CSS and JS assets for minification and combining
	 */
	public function process_assets() {
		global $wp_styles, $wp_scripts;

		$options = get_option( 'velocitywp_options', array() );

		// Process CSS
		if ( ! empty( $options['css_minify'] ) && isset( $wp_styles->queue ) ) {
			$this->process_styles( $wp_styles, $options );
		}

		// Process JS
		if ( ! empty( $options['js_minify'] ) && isset( $wp_scripts->queue ) ) {
			$this->process_scripts( $wp_scripts, $options );
		}
	}

	/**
	 * Process stylesheets
	 *
	 * @param WP_Styles $wp_styles WP_Styles object.
	 * @param array     $options Plugin options.
	 */
	private function process_styles( $wp_styles, $options ) {
		// This is a simplified version. Full implementation would:
		// 1. Collect all CSS files
		// 2. Minify each one
		// 3. Optionally combine them
		// 4. Save to cache directory
		// 5. Replace original enqueues with minified versions

		// For now, we'll just apply inline minification to inline styles
		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( ! empty( $style->extra['after'] ) ) {
				foreach ( $style->extra['after'] as $key => $data ) {
					$wp_styles->registered[ $handle ]->extra['after'][ $key ] = $this->minify_css( $data );
				}
			}
		}
	}

	/**
	 * Process scripts
	 *
	 * @param WP_Scripts $wp_scripts WP_Scripts object.
	 * @param array      $options Plugin options.
	 */
	private function process_scripts( $wp_scripts, $options ) {
		// Similar to CSS processing
		// This is simplified - full implementation would handle file combining, etc.

		foreach ( $wp_scripts->registered as $handle => $script ) {
			if ( ! empty( $script->extra['after'] ) ) {
				foreach ( $script->extra['after'] as $key => $data ) {
					$wp_scripts->registered[ $handle ]->extra['after'][ $key ] = $this->minify_js( $data );
				}
			}
		}
	}

	/**
	 * Minify CSS
	 *
	 * @param string $css CSS content.
	 * @return string Minified CSS.
	 */
	private function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );
		$css = preg_replace( '/\s+/', ' ', $css );

		// Remove spaces around colons, semicolons, braces
		$css = preg_replace( '/\s*([{}|:;,])\s*/', '$1', $css );

		// Remove trailing semicolons
		$css = str_replace( ';}', '}', $css );

		return apply_filters( 'velocitywp_minify_css', trim( $css ) );
	}

	/**
	 * Minify JavaScript
	 *
	 * @param string $js JavaScript content.
	 * @return string Minified JavaScript.
	 */
	private function minify_js( $js ) {
		// Remove single-line comments (but not URLs)
		$js = preg_replace( '~//[^\n]*~', '', $js );

		// Remove multi-line comments
		$js = preg_replace( '~/\*.*?\*/~s', '', $js );

		// Remove whitespace
		$js = preg_replace( '/\s+/', ' ', $js );

		// Remove spaces around operators
		$js = preg_replace( '/\s*([{}|:;,=()[\]])\s*/', '$1', $js );

		return apply_filters( 'velocitywp_minify_js', trim( $js ) );
	}

	/**
	 * Check if file should be excluded from minification
	 *
	 * @param string $src File source URL.
	 * @return bool
	 */
	private function is_excluded( $src ) {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['minify_exclude_files'] ) ) {
			return false;
		}

		$excluded = explode( "\n", $options['minify_exclude_files'] );
		foreach ( $excluded as $pattern ) {
			$pattern = trim( $pattern );
			if ( ! empty( $pattern ) && strpos( $src, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
