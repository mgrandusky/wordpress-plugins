<?php
/**
 * Preload Requests Class
 *
 * Critical resource preloading and priority hints
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Preload_Requests class
 */
class VelocityWP_Preload_Requests {

	/**
	 * Preload resources
	 *
	 * @var array
	 */
	private $preload_resources = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize preload requests
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['preload_requests'] ) ) {
			return;
		}

		// Auto-detect critical resources
		if ( ! empty( $options['auto_detect_critical'] ) ) {
			add_action( 'wp_head', array( $this, 'preload_critical_resources' ), 1 );
		}

		// Manual preload configuration
		if ( ! empty( $options['manual_preload'] ) ) {
			add_action( 'wp_head', array( $this, 'preload_manual_resources' ), 1 );
		}

		// Preload featured images
		if ( ! empty( $options['preload_featured_images'] ) ) {
			add_action( 'wp_head', array( $this, 'preload_featured_image' ), 1 );
		}
	}

	/**
	 * Preload critical resources
	 */
	public function preload_critical_resources() {
		// Preload critical CSS
		$this->preload_critical_css();

		// Preload critical JavaScript
		$this->preload_critical_js();

		// Preload critical fonts
		$this->preload_critical_fonts();
	}

	/**
	 * Preload critical CSS
	 */
	private function preload_critical_css() {
		global $wp_styles;

		if ( empty( $wp_styles->queue ) ) {
			return;
		}

		// Preload first few stylesheets
		$count = 0;
		$max_preload = 3;

		foreach ( $wp_styles->queue as $handle ) {
			if ( $count >= $max_preload ) {
				break;
			}

			if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}

			$style = $wp_styles->registered[ $handle ];
			$src = $style->src;

			if ( empty( $src ) ) {
				continue;
			}

			// Make URL absolute
			if ( strpos( $src, '//' ) === 0 ) {
				$src = 'https:' . $src;
			} elseif ( strpos( $src, 'http' ) !== 0 ) {
				$src = site_url( $src );
			}

			echo '<link rel="preload" href="' . esc_url( $src ) . '" as="style">' . "\n";
			$count++;
		}
	}

	/**
	 * Preload critical JavaScript
	 */
	private function preload_critical_js() {
		global $wp_scripts;

		if ( empty( $wp_scripts->queue ) ) {
			return;
		}

		$critical_scripts = array( 'jquery-core', 'jquery' );

		foreach ( $critical_scripts as $handle ) {
			if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
				continue;
			}

			$script = $wp_scripts->registered[ $handle ];
			$src = $script->src;

			if ( empty( $src ) ) {
				continue;
			}

			// Make URL absolute
			if ( strpos( $src, '//' ) === 0 ) {
				$src = 'https:' . $src;
			} elseif ( strpos( $src, 'http' ) !== 0 ) {
				$src = site_url( $src );
			}

			echo '<link rel="preload" href="' . esc_url( $src ) . '" as="script">' . "\n";
		}
	}

	/**
	 * Preload critical fonts
	 */
	private function preload_critical_fonts() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['critical_fonts'] ) ) {
			return;
		}

		$fonts = array_map( 'trim', explode( "\n", $options['critical_fonts'] ) );

		foreach ( $fonts as $font_url ) {
			if ( empty( $font_url ) ) {
				continue;
			}

			$type = $this->get_font_type( $font_url );

			echo '<link rel="preload" href="' . esc_url( $font_url ) . '" as="font" type="' . esc_attr( $type ) . '" crossorigin>' . "\n";
		}
	}

	/**
	 * Preload manual resources
	 */
	public function preload_manual_resources() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['manual_preload_resources'] ) ) {
			return;
		}

		$resources = array_map( 'trim', explode( "\n", $options['manual_preload_resources'] ) );

		foreach ( $resources as $resource ) {
			if ( empty( $resource ) ) {
				continue;
			}

			// Parse: URL|type|crossorigin
			$parts = array_map( 'trim', explode( '|', $resource ) );
			
			if ( count( $parts ) < 2 ) {
				continue;
			}

			$url = $parts[0];
			$as = $parts[1];
			$crossorigin = ! empty( $parts[2] ) && $parts[2] === 'yes';

			$attributes = 'rel="preload" href="' . esc_url( $url ) . '" as="' . esc_attr( $as ) . '"';
			
			if ( $crossorigin ) {
				$attributes .= ' crossorigin';
			}

			echo '<link ' . $attributes . '>' . "\n";
		}
	}

	/**
	 * Preload featured image
	 */
	public function preload_featured_image() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! $thumbnail_id ) {
			return;
		}

		$image = wp_get_attachment_image_src( $thumbnail_id, 'large' );

		if ( ! $image ) {
			return;
		}

		$url = $image[0];
		$type = $this->get_image_mime_type( $url );

		echo '<link rel="preload" href="' . esc_url( $url ) . '" as="image"' . ( $type ? ' type="' . esc_attr( $type ) . '"' : '' ) . '>' . "\n";
	}

	/**
	 * Get font MIME type
	 *
	 * @param string $url Font URL.
	 * @return string MIME type.
	 */
	private function get_font_type( $url ) {
		if ( strpos( $url, '.woff2' ) !== false ) {
			return 'font/woff2';
		} elseif ( strpos( $url, '.woff' ) !== false ) {
			return 'font/woff';
		} elseif ( strpos( $url, '.ttf' ) !== false ) {
			return 'font/ttf';
		} elseif ( strpos( $url, '.otf' ) !== false ) {
			return 'font/otf';
		}

		return 'font/woff2';
	}

	/**
	 * Get image MIME type
	 *
	 * @param string $url Image URL.
	 * @return string|false MIME type or false.
	 */
	private function get_image_mime_type( $url ) {
		if ( strpos( $url, '.webp' ) !== false ) {
			return 'image/webp';
		} elseif ( strpos( $url, '.jpg' ) !== false || strpos( $url, '.jpeg' ) !== false ) {
			return 'image/jpeg';
		} elseif ( strpos( $url, '.png' ) !== false ) {
			return 'image/png';
		} elseif ( strpos( $url, '.gif' ) !== false ) {
			return 'image/gif';
		} elseif ( strpos( $url, '.svg' ) !== false ) {
			return 'image/svg+xml';
		}

		return false;
	}

	/**
	 * Add resource to preload queue
	 *
	 * @param string $url  Resource URL.
	 * @param string $type Resource type.
	 * @param array  $args Additional arguments.
	 */
	public function add_preload_resource( $url, $type, $args = array() ) {
		$this->preload_resources[] = array_merge( array(
			'url'  => $url,
			'type' => $type,
		), $args );
	}

	/**
	 * Get preload resources
	 *
	 * @return array Preload resources.
	 */
	public function get_preload_resources() {
		return $this->preload_resources;
	}
}
