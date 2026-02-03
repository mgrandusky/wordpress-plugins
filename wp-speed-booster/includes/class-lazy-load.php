<?php
/**
 * Lazy Loading Class
 *
 * Handles lazy loading for images and iframes
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Lazy_Load class
 */
class WPSB_Lazy_Load {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'the_content', array( $this, 'add_lazy_load' ), 999 );
		add_filter( 'post_thumbnail_html', array( $this, 'add_lazy_load' ), 999 );
		add_filter( 'get_avatar', array( $this, 'add_lazy_load' ), 999 );
		add_filter( 'widget_text', array( $this, 'add_lazy_load' ), 999 );
	}

	/**
	 * Enqueue lazy load scripts
	 */
	public function enqueue_scripts() {
		$options = get_option( 'wpsb_options', array() );

		if ( is_admin() || ( empty( $options['lazy_load_images'] ) && empty( $options['lazy_load_iframes'] ) ) ) {
			return;
		}

		wp_enqueue_script(
			'wpsb-lazy-load',
			WPSB_URL . 'assets/lazy-load.js',
			array(),
			WPSB_VERSION,
			true
		);

		wp_enqueue_style(
			'wpsb-frontend',
			WPSB_URL . 'assets/frontend.css',
			array(),
			WPSB_VERSION
		);
	}

	/**
	 * Add lazy loading to content
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	public function add_lazy_load( $content ) {
		if ( is_admin() || is_feed() || wp_doing_ajax() ) {
			return $content;
		}

		$options = get_option( 'wpsb_options', array() );

		// Process images
		if ( ! empty( $options['lazy_load_images'] ) ) {
			$content = $this->process_images( $content, $options );
		}

		// Process iframes
		if ( ! empty( $options['lazy_load_iframes'] ) ) {
			$content = $this->process_iframes( $content );
		}

		return $content;
	}

	/**
	 * Process images for lazy loading
	 *
	 * @param string $content Content to process.
	 * @param array  $options Plugin options.
	 * @return string Modified content.
	 */
	private function process_images( $content, $options ) {
		// Find all img tags
		preg_match_all( '/<img[^>]+>/i', $content, $matches );

		if ( empty( $matches[0] ) ) {
			return $content;
		}

		$skip_count = 0;
		$skip_images = ! empty( $options['lazy_load_skip_images'] ) ? intval( $options['lazy_load_skip_images'] ) : 0;
		$exclude_class = ! empty( $options['lazy_load_exclude_class'] ) ? $options['lazy_load_exclude_class'] : '';

		foreach ( $matches[0] as $img_tag ) {
			// Skip if already has loading attribute
			if ( strpos( $img_tag, 'loading=' ) !== false ) {
				continue;
			}

			// Skip above-the-fold images
			if ( $skip_images > 0 && $skip_count < $skip_images ) {
				$skip_count++;
				continue;
			}

			// Skip excluded classes
			if ( ! empty( $exclude_class ) && strpos( $img_tag, 'class=' ) !== false ) {
				$excluded_classes = explode( ',', $exclude_class );
				foreach ( $excluded_classes as $class ) {
					$class = trim( $class );
					if ( ! empty( $class ) && strpos( $img_tag, $class ) !== false ) {
						continue 2;
					}
				}
			}

			// Add loading="lazy" attribute
			$new_img_tag = $img_tag;

			// Add native lazy loading
			if ( strpos( $new_img_tag, 'loading=' ) === false ) {
				$new_img_tag = str_replace( '<img ', '<img loading="lazy" ', $new_img_tag );
			}

			// Add data-src for JavaScript fallback
			if ( strpos( $new_img_tag, 'src=' ) !== false ) {
				preg_match( '/src=["\']([^"\']+)["\']/', $new_img_tag, $src_match );
				if ( ! empty( $src_match[1] ) ) {
					// Don't lazy load if already data URL or very small
					if ( strpos( $src_match[1], 'data:' ) === 0 ) {
						continue;
					}

					// Add wpsb-lazy class
					if ( strpos( $new_img_tag, 'class=' ) !== false ) {
						$new_img_tag = preg_replace( '/class=["\']([^"\']*)["\']/', 'class="$1 wpsb-lazy"', $new_img_tag );
					} else {
						$new_img_tag = str_replace( '<img ', '<img class="wpsb-lazy" ', $new_img_tag );
					}

					// Add data-src
					$new_img_tag = str_replace( 'src=', 'data-src=', $new_img_tag );

					// Add placeholder
					$placeholder = $this->get_placeholder();
					$new_img_tag = str_replace( '<img ', '<img src="' . esc_attr( $placeholder ) . '" ', $new_img_tag );
				}
			}

			$content = str_replace( $img_tag, apply_filters( 'wpsb_lazy_load_image', $new_img_tag ), $content );
		}

		return $content;
	}

	/**
	 * Process iframes for lazy loading
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	private function process_iframes( $content ) {
		// Find all iframe tags
		preg_match_all( '/<iframe[^>]+>/i', $content, $matches );

		if ( empty( $matches[0] ) ) {
			return $content;
		}

		foreach ( $matches[0] as $iframe_tag ) {
			// Skip if already has loading attribute
			if ( strpos( $iframe_tag, 'loading=' ) !== false ) {
				continue;
			}

			$new_iframe_tag = $iframe_tag;

			// Add native lazy loading
			$new_iframe_tag = str_replace( '<iframe ', '<iframe loading="lazy" ', $new_iframe_tag );

			// Add data-src for JavaScript fallback
			if ( strpos( $new_iframe_tag, 'src=' ) !== false ) {
				preg_match( '/src=["\']([^"\']+)["\']/', $new_iframe_tag, $src_match );
				if ( ! empty( $src_match[1] ) ) {
					// Add wpsb-lazy-iframe class
					if ( strpos( $new_iframe_tag, 'class=' ) !== false ) {
						$new_iframe_tag = preg_replace( '/class=["\']([^"\']*)["\']/', 'class="$1 wpsb-lazy-iframe"', $new_iframe_tag );
					} else {
						$new_iframe_tag = str_replace( '<iframe ', '<iframe class="wpsb-lazy-iframe" ', $new_iframe_tag );
					}

					// Replace src with data-src
					$new_iframe_tag = str_replace( 'src=', 'data-src=', $new_iframe_tag );

					// Add empty src
					$new_iframe_tag = str_replace( '<iframe ', '<iframe src="about:blank" ', $new_iframe_tag );
				}
			}

			$content = str_replace( $iframe_tag, apply_filters( 'wpsb_lazy_load_iframe', $new_iframe_tag ), $content );
		}

		return $content;
	}

	/**
	 * Get lazy load placeholder image
	 *
	 * @return string Placeholder image data URI.
	 */
	private function get_placeholder() {
		// 1x1 transparent PNG
		$placeholder = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
		return apply_filters( 'wpsb_lazy_load_placeholder', $placeholder );
	}
}
