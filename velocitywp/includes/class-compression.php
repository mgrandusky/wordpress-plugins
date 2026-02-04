<?php
/**
 * Compression Class
 *
 * Brotli and advanced compression handling
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Compression class
 */
class VelocityWP_Compression {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize compression
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['compression'] ) ) {
			return;
		}

		// Enable output compression
		if ( ! empty( $options['enable_gzip'] ) ) {
			$this->enable_gzip();
		}

		// Enable Brotli compression
		if ( ! empty( $options['enable_brotli'] ) && $this->is_brotli_supported() ) {
			$this->enable_brotli();
		}

		// Pre-compress static files
		if ( ! empty( $options['precompress_static'] ) ) {
			add_action( 'velocitywp_precompress_files', array( $this, 'precompress_static_files' ) );
		}
	}

	/**
	 * Enable GZIP compression
	 */
	private function enable_gzip() {
		if ( ! headers_sent() && ! ob_get_level() ) {
			ob_start( 'ob_gzhandler' );
		}
	}

	/**
	 * Enable Brotli compression
	 */
	private function enable_brotli() {
		// Check if client supports Brotli
		$accept_encoding = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( $_SERVER['HTTP_ACCEPT_ENCODING'] ) : '';
		
		if ( strpos( $accept_encoding, 'br' ) === false ) {
			return;
		}

		add_filter( 'velocitywp_cache_file_content', array( $this, 'compress_with_brotli' ), 10, 1 );
	}

	/**
	 * Check if Brotli is supported
	 *
	 * @return bool Whether Brotli is supported.
	 */
	private function is_brotli_supported() {
		return function_exists( 'brotli_compress' );
	}

	/**
	 * Compress content with Brotli
	 *
	 * @param string $content Content to compress.
	 * @return string Compressed content.
	 */
	public function compress_with_brotli( $content ) {
		if ( ! $this->is_brotli_supported() ) {
			return $content;
		}

		$options = get_option( 'velocitywp_options', array() );
		$quality = ! empty( $options['brotli_quality'] ) ? intval( $options['brotli_quality'] ) : 4;

		$compressed = brotli_compress( $content, $quality );

		if ( $compressed !== false ) {
			header( 'Content-Encoding: br' );
			return $compressed;
		}

		return $content;
	}

	/**
	 * Precompress static files
	 */
	public function precompress_static_files() {
		$options = get_option( 'velocitywp_options', array() );
		
		$directories = array(
			get_template_directory(),
			WP_CONTENT_DIR . '/plugins',
		);

		$file_types = array( 'css', 'js', 'svg', 'html' );

		foreach ( $directories as $directory ) {
			$this->compress_directory( $directory, $file_types );
		}
	}

	/**
	 * Compress files in directory
	 *
	 * @param string $directory  Directory path.
	 * @param array  $file_types File types to compress.
	 */
	private function compress_directory( $directory, $file_types ) {
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			$extension = $file->getExtension();
			
			if ( ! in_array( $extension, $file_types, true ) ) {
				continue;
			}

			$this->compress_file( $file->getPathname() );
		}
	}

	/**
	 * Compress individual file
	 *
	 * @param string $file_path File path.
	 */
	private function compress_file( $file_path ) {
		// Create gzip version
		$gz_path = $file_path . '.gz';
		if ( ! file_exists( $gz_path ) || filemtime( $file_path ) > filemtime( $gz_path ) ) {
			$content = file_get_contents( $file_path );
			$compressed = gzencode( $content, 9 );
			file_put_contents( $gz_path, $compressed );
		}

		// Create Brotli version if supported
		if ( $this->is_brotli_supported() ) {
			$br_path = $file_path . '.br';
			if ( ! file_exists( $br_path ) || filemtime( $file_path ) > filemtime( $br_path ) ) {
				$content = file_get_contents( $file_path );
				$compressed = brotli_compress( $content, 11 );
				if ( $compressed !== false ) {
					file_put_contents( $br_path, $compressed );
				}
			}
		}
	}

	/**
	 * Get compression statistics
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		$stats = array(
			'gzip_enabled'   => $this->is_gzip_enabled(),
			'brotli_enabled' => $this->is_brotli_supported(),
			'compressed_files' => $this->count_compressed_files(),
		);

		return $stats;
	}

	/**
	 * Check if GZIP is enabled
	 *
	 * @return bool Whether GZIP is enabled.
	 */
	private function is_gzip_enabled() {
		return extension_loaded( 'zlib' );
	}

	/**
	 * Count compressed files
	 *
	 * @return int Number of compressed files.
	 */
	private function count_compressed_files() {
		$count = 0;
		$directories = array(
			get_template_directory(),
			WP_CONTENT_DIR . '/plugins',
		);

		foreach ( $directories as $directory ) {
			if ( is_dir( $directory ) ) {
				$gz_files = glob( $directory . '/*.gz' );
				$br_files = glob( $directory . '/*.br' );
				$count += count( $gz_files ) + count( $br_files );
			}
		}

		return $count;
	}
}
