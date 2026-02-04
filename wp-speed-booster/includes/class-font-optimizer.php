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
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'wpsb_options', array() );

		$upload_dir = wp_upload_dir();
		$this->fonts_dir = $upload_dir['basedir'] . '/wpsb-fonts/';
		$this->fonts_url = $upload_dir['baseurl'] . '/wpsb-fonts/';

		// Create fonts directory
		if ( ! file_exists( $this->fonts_dir ) ) {
			wp_mkdir_p( $this->fonts_dir );
		}

		// Hooks
		if ( $this->is_enabled() && ! is_admin() ) {
			add_action( 'wp_head', array( $this, 'inject_font_optimizations' ), 1 );
			add_filter( 'style_loader_tag', array( $this, 'optimize_font_stylesheets' ), 10, 4 );
		}

		// Local Google Fonts
		if ( $this->is_local_fonts_enabled() && ! is_admin() ) {
			add_filter( 'style_loader_src', array( $this, 'replace_google_fonts_url' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_local_fonts' ), 999 );
		}

		// AJAX handlers
		add_action( 'wp_ajax_wpsb_download_google_fonts', array( $this, 'ajax_download_google_fonts' ) );
		add_action( 'wp_ajax_wpsb_detect_google_fonts', array( $this, 'ajax_detect_google_fonts' ) );
		add_action( 'wp_ajax_wpsb_clear_local_fonts', array( $this, 'ajax_clear_local_fonts' ) );
	}

	/**
	 * Check if font optimization is enabled
	 */
	public function is_enabled() {
		return ! empty( $this->settings['font_optimization_enabled'] );
	}

	/**
	 * Check if local Google Fonts is enabled
	 */
	public function is_local_fonts_enabled() {
		return ! empty( $this->settings['local_google_fonts'] );
	}

	/**
	 * Inject font optimizations in head
	 */
	public function inject_font_optimizations() {
		$this->add_dns_prefetch();
		$this->add_preconnect();
		$this->add_font_preload();
		$this->add_font_display_css();
	}

	/**
	 * Add DNS prefetch for font domains
	 */
	private function add_dns_prefetch() {
		if ( empty( $this->settings['font_dns_prefetch'] ) ) {
			return;
		}

		echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
		echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "\n";
	}

	/**
	 * Add preconnect for font domains
	 */
	private function add_preconnect() {
		if ( empty( $this->settings['font_preconnect'] ) ) {
			return;
		}

		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}

	/**
	 * Add font preload
	 */
	private function add_font_preload() {
		$preload_fonts = ! empty( $this->settings['font_preload_urls'] ) ?
			explode( "\n", $this->settings['font_preload_urls'] ) : array();

		foreach ( $preload_fonts as $font_url ) {
			$font_url = trim( $font_url );
			if ( empty( $font_url ) ) {
				continue;
			}

			$format = $this->get_font_format( $font_url );
			echo '<link rel="preload" href="' . esc_url( $font_url ) . '" as="font" type="font/' . esc_attr( $format ) . '" crossorigin>' . "\n";
		}
	}

	/**
	 * Add font-display CSS
	 */
	private function add_font_display_css() {
		$font_display = ! empty( $this->settings['font_display'] ) ? $this->settings['font_display'] : 'swap';

		if ( 'auto' === $font_display ) {
			return;
		}

		?>
		<style id="wpsb-font-display">
		@font-face {
			font-display: <?php echo esc_attr( $font_display ); ?>;
		}
		</style>
		<?php
	}

	/**
	 * Get font format from URL
	 */
	private function get_font_format( $url ) {
		if ( strpos( $url, '.woff2' ) !== false ) {
			return 'woff2';
		}
		if ( strpos( $url, '.woff' ) !== false ) {
			return 'woff';
		}
		if ( strpos( $url, '.ttf' ) !== false ) {
			return 'truetype';
		}
		if ( strpos( $url, '.otf' ) !== false ) {
			return 'opentype';
		}
		return 'woff2'; // Default
	}

	/**
	 * Optimize font stylesheets
	 */
	public function optimize_font_stylesheets( $html, $handle, $href, $media ) {
		// Check if it's a Google Fonts stylesheet
		if ( strpos( $href, 'fonts.googleapis.com' ) === false ) {
			return $html;
		}

		// Add font-display parameter if not present
		if ( strpos( $href, 'display=' ) === false ) {
			$font_display = ! empty( $this->settings['font_display'] ) ? $this->settings['font_display'] : 'swap';
			$href = add_query_arg( 'display', $font_display, $href );
			$html = str_replace( $href, $href, $html );
		}

		return $html;
	}

	/**
	 * Detect Google Fonts in use
	 */
	public function detect_google_fonts() {
		global $wp_styles;

		$google_fonts = array();

		if ( ! is_object( $wp_styles ) ) {
			return $google_fonts;
		}

		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( strpos( $style->src, 'fonts.googleapis.com' ) !== false ) {
				$google_fonts[] = array(
					'handle'   => $handle,
					'url'      => $style->src,
					'families' => $this->parse_google_fonts_url( $style->src ),
				);
			}
		}

		return $google_fonts;
	}

	/**
	 * Parse Google Fonts URL to get families
	 */
	private function parse_google_fonts_url( $url ) {
		$families = array();

		// Parse URL
		$parsed = wp_parse_url( $url );
		if ( isset( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $params );

			if ( isset( $params['family'] ) ) {
				// Can be single or multiple families
				$family_string = $params['family'];
				$family_parts  = explode( '|', $family_string );

				foreach ( $family_parts as $family ) {
					// Extract font name and weights
					if ( strpos( $family, ':' ) !== false ) {
						list($name, $weights) = explode( ':', $family, 2 );
						$families[]           = array(
							'name'    => str_replace( '+', ' ', $name ),
							'weights' => explode( ',', $weights ),
						);
					} else {
						$families[] = array(
							'name'    => str_replace( '+', ' ', $family ),
							'weights' => array( '400' ),
						);
					}
				}
			}
		}

		return $families;
	}

	/**
	 * Download Google Fonts locally
	 */
	public function download_google_fonts( $url ) {
		// Get CSS from Google Fonts
		$response = wp_remote_get(
			$url,
			array(
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$css = wp_remote_retrieve_body( $response );

		// Extract font URLs from CSS
		preg_match_all( '/url\((https?:\/\/[^)]+)\)/', $css, $matches );

		$font_files = array();

		foreach ( $matches[1] as $font_url ) {
			$font_url = trim( $font_url );

			// Download font file
			$font_data = wp_remote_get( $font_url );
			if ( is_wp_error( $font_data ) ) {
				continue;
			}

			$font_content = wp_remote_retrieve_body( $font_data );

			// Generate local filename
			$filename   = basename( wp_parse_url( $font_url, PHP_URL_PATH ) );
			$local_path = $this->fonts_dir . $filename;

			// Save font file
			file_put_contents( $local_path, $font_content );

			// Replace URL in CSS
			$local_url = $this->fonts_url . $filename;
			$css       = str_replace( $font_url, $local_url, $css );

			$font_files[] = array(
				'original' => $font_url,
				'local'    => $local_url,
				'path'     => $local_path,
			);
		}

		// Save CSS file
		$css_filename = 'google-fonts-' . md5( $url ) . '.css';
		$css_path     = $this->fonts_dir . $css_filename;
		file_put_contents( $css_path, $css );

		return array(
			'css_url'  => $this->fonts_url . $css_filename,
			'css_path' => $css_path,
			'fonts'    => $font_files,
		);
	}

	/**
	 * Replace Google Fonts URL with local version
	 */
	public function replace_google_fonts_url( $src, $handle ) {
		if ( strpos( $src, 'fonts.googleapis.com' ) === false ) {
			return $src;
		}

		$local_css = $this->get_local_fonts_css( $src );

		if ( $local_css ) {
			return $local_css;
		}

		return $src;
	}

	/**
	 * Get local fonts CSS URL
	 */
	private function get_local_fonts_css( $original_url ) {
		$css_filename = 'google-fonts-' . md5( $original_url ) . '.css';
		$css_path     = $this->fonts_dir . $css_filename;

		if ( file_exists( $css_path ) ) {
			return $this->fonts_url . $css_filename;
		}

		return false;
	}

	/**
	 * Enqueue local fonts
	 */
	public function enqueue_local_fonts() {
		$google_fonts = $this->detect_google_fonts();

		foreach ( $google_fonts as $font ) {
			$local_css = $this->get_local_fonts_css( $font['url'] );
			if ( $local_css ) {
				wp_deregister_style( $font['handle'] );
				wp_enqueue_style( $font['handle'] . '-local', $local_css, array(), null );
			}
		}
	}

	/**
	 * AJAX: Download Google Fonts
	 */
	public function ajax_download_google_fonts() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => 'URL is required' ) );
		}

		$result = $this->download_google_fonts( $url );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message'     => 'Fonts downloaded successfully',
					'css_url'     => $result['css_url'],
					'fonts_count' => count( $result['fonts'] ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => 'Failed to download fonts' ) );
		}
	}

	/**
	 * AJAX: Detect Google Fonts
	 */
	public function ajax_detect_google_fonts() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$fonts = $this->detect_google_fonts();

		wp_send_json_success( array( 'fonts' => $fonts ) );
	}

	/**
	 * AJAX: Clear local fonts
	 */
	public function ajax_clear_local_fonts() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$result = $this->clear_font_cache();

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Local fonts cleared successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to clear local fonts' ) );
		}
	}

	/**
	 * Get statistics
	 */
	public function get_stats() {
		$font_files = glob( $this->fonts_dir . '*' );
		$total_size = 0;

		foreach ( $font_files as $file ) {
			if ( is_file( $file ) ) {
				$total_size += filesize( $file );
			}
		}

		return array(
			'files'           => count( $font_files ),
			'size'            => $total_size,
			'size_formatted'  => size_format( $total_size ),
		);
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
