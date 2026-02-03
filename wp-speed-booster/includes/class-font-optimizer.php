<?php
/**
 * Font Optimizer Class
 *
 * Font optimization and local Google Fonts hosting
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Font_Optimizer class
 */
class WPSB_Font_Optimizer {

	/**
	 * Fonts directory path
	 *
	 * @var string
	 */
	private $fonts_dir;

	/**
	 * Fonts directory URL
	 *
	 * @var string
	 */
	private $fonts_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->fonts_dir = $upload_dir['basedir'] . '/wpsb-fonts/';
		$this->fonts_url = $upload_dir['baseurl'] . '/wpsb-fonts/';

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_download_fonts', array( $this, 'ajax_download_fonts' ) );
	}

	/**
	 * Initialize font optimization
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['font_optimize'] ) || is_admin() ) {
			return;
		}

		// Create fonts directory
		$this->maybe_create_fonts_dir();

		// Local Google Fonts
		if ( ! empty( $options['local_google_fonts'] ) ) {
			add_filter( 'style_loader_tag', array( $this, 'localize_google_fonts' ), 10, 4 );
			add_action( 'wp_head', array( $this, 'output_local_fonts' ), 1 );
		}

		// Font display swap
		if ( ! empty( $options['font_display_swap'] ) ) {
			add_filter( 'style_loader_tag', array( $this, 'add_font_display' ), 10, 4 );
		}

		// Preload fonts
		if ( ! empty( $options['preload_fonts'] ) ) {
			add_action( 'wp_head', array( $this, 'preload_fonts' ), 1 );
		}

		// Remove unused fonts
		if ( ! empty( $options['remove_unused_fonts'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'remove_unused_fonts' ), 999 );
		}
	}

	/**
	 * Create fonts directory
	 */
	private function maybe_create_fonts_dir() {
		if ( ! file_exists( $this->fonts_dir ) ) {
			wp_mkdir_p( $this->fonts_dir );
			
			// Add index.php for security
			$index_file = $this->fonts_dir . 'index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, '<?php // Silence is golden' );
			}
		}
	}

	/**
	 * Localize Google Fonts
	 *
	 * @param string $tag    Style tag.
	 * @param string $handle Style handle.
	 * @param string $href   Style URL.
	 * @param string $media  Style media.
	 * @return string Modified tag.
	 */
	public function localize_google_fonts( $tag, $handle, $href, $media ) {
		// Check if it's a Google Fonts URL
		if ( strpos( $href, 'fonts.googleapis.com' ) === false ) {
			return $tag;
		}

		// Get local font CSS
		$local_css = $this->get_local_font_css( $href );
		
		if ( empty( $local_css ) ) {
			return $tag;
		}

		// Replace with local font URL
		$local_url = $this->fonts_url . md5( $href ) . '.css';
		$tag = str_replace( $href, $local_url, $tag );

		return $tag;
	}

	/**
	 * Get local font CSS
	 *
	 * @param string $url Google Fonts URL.
	 * @return string Local CSS file path.
	 */
	private function get_local_font_css( $url ) {
		$css_file = $this->fonts_dir . md5( $url ) . '.css';

		// Return if already cached
		if ( file_exists( $css_file ) ) {
			return $css_file;
		}

		// Download Google Fonts CSS
		$response = wp_remote_get( $url, array(
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
			'timeout'    => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$css_content = wp_remote_retrieve_body( $response );

		if ( empty( $css_content ) ) {
			return '';
		}

		// Download font files and update CSS
		$css_content = $this->download_font_files( $css_content );

		// Save CSS file
		file_put_contents( $css_file, $css_content );

		return $css_file;
	}

	/**
	 * Download font files and update CSS
	 *
	 * @param string $css CSS content.
	 * @return string Updated CSS.
	 */
	private function download_font_files( $css ) {
		// Find all font URLs in CSS
		preg_match_all( '/url\((https:\/\/fonts\.gstatic\.com[^)]+)\)/', $css, $matches );

		if ( empty( $matches[1] ) ) {
			return $css;
		}

		foreach ( $matches[1] as $font_url ) {
			$font_file = $this->download_font_file( $font_url );
			
			if ( $font_file ) {
				$local_url = $this->fonts_url . basename( $font_file );
				$css = str_replace( $font_url, $local_url, $css );
			}
		}

		return $css;
	}

	/**
	 * Download individual font file
	 *
	 * @param string $url Font file URL.
	 * @return string|false Local font file path or false on failure.
	 */
	private function download_font_file( $url ) {
		$filename = basename( parse_url( $url, PHP_URL_PATH ) );
		$font_file = $this->fonts_dir . $filename;

		// Return if already exists
		if ( file_exists( $font_file ) ) {
			return $font_file;
		}

		// Download font file
		$response = wp_remote_get( $url, array(
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$font_data = wp_remote_retrieve_body( $response );

		if ( empty( $font_data ) ) {
			return false;
		}

		// Save font file
		file_put_contents( $font_file, $font_data );

		return $font_file;
	}

	/**
	 * Output local fonts in head
	 */
	public function output_local_fonts() {
		$options = get_option( 'wpsb_options', array() );
		
		if ( empty( $options['custom_local_fonts'] ) ) {
			return;
		}

		$fonts = explode( "\n", $options['custom_local_fonts'] );
		
		foreach ( $fonts as $font ) {
			$font = trim( $font );
			if ( empty( $font ) ) {
				continue;
			}

			$local_css = $this->get_local_font_css( $font );
			
			if ( $local_css ) {
				$local_url = $this->fonts_url . md5( $font ) . '.css';
				echo '<link rel="stylesheet" href="' . esc_url( $local_url ) . '" media="all">' . "\n";
			}
		}
	}

	/**
	 * Add font-display: swap to font-face declarations
	 *
	 * @param string $tag    Style tag.
	 * @param string $handle Style handle.
	 * @param string $href   Style URL.
	 * @param string $media  Style media.
	 * @return string Modified tag.
	 */
	public function add_font_display( $tag, $handle, $href, $media ) {
		if ( strpos( $href, 'fonts.googleapis.com' ) !== false ) {
			$href = add_query_arg( 'display', 'swap', $href );
			$tag = str_replace( $tag, '<link rel="stylesheet" href="' . esc_url( $href ) . '" media="' . esc_attr( $media ) . '">', $tag );
		}

		return $tag;
	}

	/**
	 * Preload critical fonts
	 */
	public function preload_fonts() {
		$options = get_option( 'wpsb_options', array() );
		
		if ( empty( $options['preload_fonts_list'] ) ) {
			return;
		}

		$fonts = explode( "\n", $options['preload_fonts_list'] );
		
		foreach ( $fonts as $font ) {
			$font = trim( $font );
			if ( empty( $font ) ) {
				continue;
			}

			// Determine font type
			$type = 'font/woff2';
			if ( strpos( $font, '.woff' ) !== false ) {
				$type = 'font/woff';
			} elseif ( strpos( $font, '.ttf' ) !== false ) {
				$type = 'font/ttf';
			} elseif ( strpos( $font, '.otf' ) !== false ) {
				$type = 'font/otf';
			}

			echo '<link rel="preload" href="' . esc_url( $font ) . '" as="font" type="' . esc_attr( $type ) . '" crossorigin>' . "\n";
		}
	}

	/**
	 * Remove unused fonts
	 */
	public function remove_unused_fonts() {
		$options = get_option( 'wpsb_options', array() );
		
		if ( empty( $options['remove_fonts_list'] ) ) {
			return;
		}

		$fonts = array_map( 'trim', explode( "\n", $options['remove_fonts_list'] ) );
		
		foreach ( $fonts as $handle ) {
			if ( ! empty( $handle ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	/**
	 * AJAX handler to download fonts manually
	 */
	public function ajax_download_fonts() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$url = isset( $_POST['url'] ) ? sanitize_text_field( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL', 'wp-speed-booster' ) ) );
		}

		$result = $this->get_local_font_css( $url );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Fonts downloaded successfully', 'wp-speed-booster' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to download fonts', 'wp-speed-booster' ) ) );
		}
	}

	/**
	 * Clear cached fonts
	 */
	public function clear_font_cache() {
		if ( ! file_exists( $this->fonts_dir ) ) {
			return true;
		}

		$files = glob( $this->fonts_dir . '*' );
		
		foreach ( $files as $file ) {
			if ( is_file( $file ) && basename( $file ) !== 'index.php' ) {
				unlink( $file );
			}
		}

		return true;
	}
}
