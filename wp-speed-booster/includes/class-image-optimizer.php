<?php
/**
 * Image Optimizer Class
 *
 * Handles image optimization, WebP conversion, and bulk processing
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Speed_Booster_Image_Optimizer class
 */
class WP_Speed_Booster_Image_Optimizer {

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'wpsb_options', array() );
		$this->register_hooks();
	}

	/**
	 * Register hooks
	 */
	private function register_hooks() {
		// Optimization on upload
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'optimize_on_upload' ), 10, 2 );
		
		// WebP serving
		add_filter( 'wp_get_attachment_url', array( $this, 'serve_webp_images' ) );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'serve_webp_images' ) );
		
		// Picture element
		add_filter( 'post_thumbnail_html', array( $this, 'add_picture_element' ), 10, 3 );
		
		// Image resizing
		add_filter( 'wp_handle_upload', array( $this, 'resize_large_images' ) );
		
		// Bulk optimization
		add_action( 'wpspeed_optimize_image', array( $this, 'process_bulk_optimization' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_wpspeed_optimize_image', array( $this, 'ajax_optimize_image' ) );
		add_action( 'wp_ajax_wpspeed_bulk_optimize', array( $this, 'ajax_bulk_optimize' ) );
		add_action( 'wp_ajax_wpspeed_get_image_stats', array( $this, 'ajax_get_stats' ) );
	}

	/**
	 * Check if image optimization is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['image_optimization_enabled'] );
	}

	/**
	 * Check if file is an image
	 *
	 * @param string $file_path File path.
	 * @return bool
	 */
	public function is_image( $file_path ) {
		$info = getimagesize( $file_path );
		return $info !== false;
	}

	/**
	 * Optimize image on upload
	 *
	 * @param array $metadata Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Modified metadata.
	 */
	public function optimize_on_upload( $metadata, $attachment_id ) {
		if ( ! $this->is_enabled() ) {
			return $metadata;
		}

		$file_path = get_attached_file( $attachment_id );

		if ( ! $this->is_image( $file_path ) ) {
			return $metadata;
		}

		// Get original size
		$original_size = filesize( $file_path );

		// Optimize original
		$this->optimize_image( $file_path );

		// Generate WebP version
		if ( ! empty( $this->settings['image_webp_enabled'] ) ) {
			$this->generate_webp( $file_path );
		}

		// Optimize all sizes
		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$size_path = path_join( dirname( $file_path ), $size_data['file'] );

				if ( file_exists( $size_path ) ) {
					$this->optimize_image( $size_path );

					if ( ! empty( $this->settings['image_webp_enabled'] ) ) {
						$this->generate_webp( $size_path );
					}
				}
			}
		}

		// Store optimization data
		$optimized_size = filesize( $file_path );
		$savings = $original_size - $optimized_size;
		$savings_percent = $original_size > 0 ? round( ( $savings / $original_size ) * 100, 2 ) : 0;

		update_post_meta(
			$attachment_id,
			'_wpspeed_optimized',
			array(
				'original_size'   => $original_size,
				'optimized_size'  => $optimized_size,
				'savings'         => $savings,
				'savings_percent' => $savings_percent,
				'date'            => current_time( 'mysql' ),
			)
		);

		return $metadata;
	}

	/**
	 * Optimize image
	 *
	 * @param string $file_path File path.
	 * @return bool Success status.
	 */
	public function optimize_image( $file_path ) {
		$method = ! empty( $this->settings['image_optimization_method'] ) ?
			$this->settings['image_optimization_method'] : 'gd';

		switch ( $method ) {
			case 'imagick':
				return $this->optimize_with_imagick( $file_path );

			case 'gd':
				return $this->optimize_with_gd( $file_path );

			case 'api':
				return $this->optimize_with_api( $file_path );

			default:
				return false;
		}
	}

	/**
	 * Optimize image with Imagick
	 *
	 * @param string $file_path File path.
	 * @return bool Success status.
	 */
	private function optimize_with_imagick( $file_path ) {
		if ( ! extension_loaded( 'imagick' ) ) {
			return false;
		}

		try {
			$image = new Imagick( $file_path );

			// Get quality setting
			$quality = ! empty( $this->settings['image_quality'] ) ?
				intval( $this->settings['image_quality'] ) : 85;

			// Set compression
			$image->setImageCompressionQuality( $quality );

			// Strip metadata (unless preserve is enabled)
			if ( empty( $this->settings['image_preserve_exif'] ) ) {
				$image->stripImage();
			}

			// Write optimized image
			$image->writeImage( $file_path );
			$image->destroy();

			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Optimize image with GD
	 *
	 * @param string $file_path File path.
	 * @return bool Success status.
	 */
	private function optimize_with_gd( $file_path ) {
		$info = getimagesize( $file_path );

		if ( ! $info ) {
			return false;
		}

		$mime = $info['mime'];

		switch ( $mime ) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg( $file_path );
				break;

			case 'image/png':
				$image = imagecreatefrompng( $file_path );
				break;

			case 'image/gif':
				$image = imagecreatefromgif( $file_path );
				break;

			default:
				return false;
		}

		if ( ! $image ) {
			return false;
		}

		// Get quality
		$quality = ! empty( $this->settings['image_quality'] ) ?
			intval( $this->settings['image_quality'] ) : 85;

		// Save optimized
		switch ( $mime ) {
			case 'image/jpeg':
				imagejpeg( $image, $file_path, $quality );
				break;

			case 'image/png':
				// PNG quality is 0-9 (0 = no compression, 9 = max)
				$png_quality = floor( ( 100 - $quality ) / 11 );
				imagepng( $image, $file_path, $png_quality );
				break;

			case 'image/gif':
				imagegif( $image, $file_path );
				break;
		}

		imagedestroy( $image );

		return true;
	}

	/**
	 * Optimize image with external API
	 *
	 * @param string $file_path File path.
	 * @return bool Success status.
	 */
	private function optimize_with_api( $file_path ) {
		// Use external API like TinyPNG, Kraken, etc.
		$api_key = ! empty( $this->settings['image_api_key'] ) ?
			$this->settings['image_api_key'] : '';

		if ( empty( $api_key ) ) {
			return false;
		}

		// Example: TinyPNG API
		$response = wp_remote_post(
			'https://api.tinify.com/shrink',
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
					'Content-Type'  => 'application/json',
				),
				'body'    => file_get_contents( $file_path ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['output']['url'] ) ) {
			// Download optimized image
			$optimized = wp_remote_get( $body['output']['url'] );

			if ( ! is_wp_error( $optimized ) ) {
				file_put_contents( $file_path, wp_remote_retrieve_body( $optimized ) );
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate WebP version of image
	 *
	 * @param string $file_path File path.
	 * @return bool Success status.
	 */
	public function generate_webp( $file_path ) {
		$webp_path = $this->get_webp_path( $file_path );

		// Check if WebP already exists
		if ( file_exists( $webp_path ) && ! empty( $this->settings['image_webp_skip_existing'] ) ) {
			return true;
		}

		// Use Imagick if available
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			return $this->generate_webp_imagick( $file_path, $webp_path );
		}

		// Use GD if WebP support is available
		if ( function_exists( 'imagewebp' ) ) {
			return $this->generate_webp_gd( $file_path, $webp_path );
		}

		return false;
	}

	/**
	 * Generate WebP with Imagick
	 *
	 * @param string $source Source file path.
	 * @param string $destination Destination file path.
	 * @return bool Success status.
	 */
	private function generate_webp_imagick( $source, $destination ) {
		try {
			$image = new Imagick( $source );
			$image->setImageFormat( 'webp' );

			$quality = ! empty( $this->settings['image_webp_quality'] ) ?
				intval( $this->settings['image_webp_quality'] ) : 85;

			$image->setImageCompressionQuality( $quality );
			$image->writeImage( $destination );
			$image->destroy();

			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Generate WebP with GD
	 *
	 * @param string $source Source file path.
	 * @param string $destination Destination file path.
	 * @return bool Success status.
	 */
	private function generate_webp_gd( $source, $destination ) {
		$info = getimagesize( $source );

		if ( ! $info ) {
			return false;
		}

		$mime = $info['mime'];

		switch ( $mime ) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg( $source );
				break;

			case 'image/png':
				$image = imagecreatefrompng( $source );
				break;

			default:
				return false;
		}

		if ( ! $image ) {
			return false;
		}

		// Enable alpha channel for PNG transparency
		if ( $mime === 'image/png' ) {
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		}

		$quality = ! empty( $this->settings['image_webp_quality'] ) ?
			intval( $this->settings['image_webp_quality'] ) : 85;

		$result = imagewebp( $image, $destination, $quality );
		imagedestroy( $image );

		return $result;
	}

	/**
	 * Get WebP path for a given file path
	 *
	 * @param string $file_path File path.
	 * @return string WebP file path.
	 */
	private function get_webp_path( $file_path ) {
		return preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file_path );
	}

	/**
	 * Get WebP URL for a given URL
	 *
	 * @param string $url Image URL.
	 * @return string WebP URL.
	 */
	private function get_webp_url( $url ) {
		return preg_replace( '/\.(jpe?g|png)$/i', '.webp', $url );
	}

	/**
	 * Serve WebP images to supported browsers
	 *
	 * @param string $image_url Image URL.
	 * @return string Modified URL.
	 */
	public function serve_webp_images( $image_url ) {
		if ( ! $this->is_enabled() || empty( $this->settings['image_webp_enabled'] ) ) {
			return $image_url;
		}

		// Check if browser supports WebP
		if ( ! $this->browser_supports_webp() ) {
			return $image_url;
		}

		// Get WebP URL
		$webp_url = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $image_url );

		// Check if WebP version exists
		$upload_dir = wp_get_upload_dir();
		$webp_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_url );

		if ( file_exists( $webp_path ) ) {
			return $webp_url;
		}

		return $image_url;
	}

	/**
	 * Check if browser supports WebP
	 *
	 * @return bool
	 */
	private function browser_supports_webp() {
		static $supports = null;

		if ( $supports !== null ) {
			return $supports;
		}

		if ( isset( $_SERVER['HTTP_ACCEPT'] ) &&
			strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ) {
			$supports = true;
		} else {
			$supports = false;
		}

		return $supports;
	}

	/**
	 * Add picture element with WebP fallback
	 *
	 * @param string $html Image HTML.
	 * @param int    $post_id Post ID.
	 * @param int    $post_thumbnail_id Thumbnail attachment ID.
	 * @return string Modified HTML.
	 */
	public function add_picture_element( $html, $post_id, $post_thumbnail_id ) {
		if ( ! $this->is_enabled() || empty( $this->settings['image_use_picture'] ) ) {
			return $html;
		}

		// Extract src from img tag
		preg_match( '/src="([^"]+)"/', $html, $src_match );

		if ( empty( $src_match[1] ) ) {
			return $html;
		}

		$original_src = $src_match[1];
		$webp_src     = $this->get_webp_url( $original_src );

		// Check if WebP exists
		$upload_dir = wp_get_upload_dir();
		$webp_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_src );

		if ( ! file_exists( $webp_path ) ) {
			return $html;
		}

		// Generate picture element
		$picture  = '<picture>';
		$picture .= '<source type="image/webp" srcset="' . esc_url( $webp_src ) . '">';
		$picture .= $html;
		$picture .= '</picture>';

		return $picture;
	}

	/**
	 * Resize large images on upload
	 *
	 * @param array $file File data.
	 * @return array Modified file data.
	 */
	public function resize_large_images( $file ) {
		if ( ! $this->is_enabled() || empty( $this->settings['image_max_width'] ) ) {
			return $file;
		}

		$max_width  = intval( $this->settings['image_max_width'] );
		$max_height = ! empty( $this->settings['image_max_height'] ) ?
			intval( $this->settings['image_max_height'] ) : $max_width;

		list($width, $height, $type) = getimagesize( $file['file'] );

		if ( $width <= $max_width && $height <= $max_height ) {
			return $file;
		}

		// Calculate new dimensions
		$ratio      = min( $max_width / $width, $max_height / $height );
		$new_width  = round( $width * $ratio );
		$new_height = round( $height * $ratio );

		// Create image resource
		switch ( $type ) {
			case IMAGETYPE_JPEG:
				$source = imagecreatefromjpeg( $file['file'] );
				break;
			case IMAGETYPE_PNG:
				$source = imagecreatefrompng( $file['file'] );
				break;
			case IMAGETYPE_GIF:
				$source = imagecreatefromgif( $file['file'] );
				break;
			default:
				return $file;
		}

		// Create resized image
		$destination = imagecreatetruecolor( $new_width, $new_height );

		// Preserve transparency for PNG
		if ( $type === IMAGETYPE_PNG ) {
			imagealphablending( $destination, false );
			imagesavealpha( $destination, true );
		}

		imagecopyresampled( $destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

		// Save resized image
		switch ( $type ) {
			case IMAGETYPE_JPEG:
				imagejpeg( $destination, $file['file'], 90 );
				break;
			case IMAGETYPE_PNG:
				imagepng( $destination, $file['file'], 9 );
				break;
			case IMAGETYPE_GIF:
				imagegif( $destination, $file['file'] );
				break;
		}

		imagedestroy( $source );
		imagedestroy( $destination );

		return $file;
	}

	/**
	 * Start bulk optimization
	 *
	 * @return array Result array.
	 */
	public function bulk_optimize_start() {
		// Get all unoptimized images
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_wpspeed_optimized',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$images = get_posts( $args );

		if ( empty( $images ) ) {
			return array(
				'success' => false,
				'message' => 'No images to optimize',
			);
		}

		// Queue images for optimization
		foreach ( $images as $image ) {
			wp_schedule_single_event( time() + 10, 'wpspeed_optimize_image', array( $image->ID ) );
		}

		return array(
			'success' => true,
			'total'   => count( $images ),
			'message' => sprintf( '%d images queued for optimization', count( $images ) ),
		);
	}

	/**
	 * Process bulk optimization for a single image
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function process_bulk_optimization( $attachment_id ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! $metadata ) {
			return;
		}

		$this->optimize_on_upload( $metadata, $attachment_id );
	}

	/**
	 * Get optimization statistics
	 *
	 * @return array Statistics array.
	 */
	public function get_stats() {
		global $wpdb;

		// Get all optimized images
		$optimized = $wpdb->get_results(
			"SELECT meta_value 
			FROM {$wpdb->postmeta} 
			WHERE meta_key = '_wpspeed_optimized'"
		);

		$total_original  = 0;
		$total_optimized = 0;
		$total_images    = 0;

		foreach ( $optimized as $meta ) {
			$data = maybe_unserialize( $meta->meta_value );

			if ( is_array( $data ) ) {
				$total_original  += $data['original_size'];
				$total_optimized += $data['optimized_size'];
				$total_images++;
			}
		}

		$total_savings    = $total_original - $total_optimized;
		$average_savings  = $total_images > 0 && $total_original > 0 ?
			round( ( $total_savings / $total_original ) * 100, 2 ) : 0;

		// Get unoptimized count
		$unoptimized = $wpdb->get_var(
			"SELECT COUNT(*) 
			FROM {$wpdb->posts} 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image%'
			AND ID NOT IN (
				SELECT post_id 
				FROM {$wpdb->postmeta} 
				WHERE meta_key = '_wpspeed_optimized'
			)"
		);

		return array(
			'total_images'              => $total_images,
			'unoptimized_images'        => intval( $unoptimized ),
			'total_original_size'       => $total_original,
			'total_optimized_size'      => $total_optimized,
			'total_savings'             => $total_savings,
			'total_savings_formatted'   => size_format( $total_savings ),
			'average_savings_percent'   => $average_savings,
		);
	}

	/**
	 * AJAX handler to optimize single image
	 */
	public function ajax_optimize_image() {
		check_ajax_referer( 'wpspeed-image-optimizer', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;

		if ( ! $attachment_id ) {
			wp_send_json_error( array( 'message' => 'Invalid attachment ID' ) );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! $metadata ) {
			wp_send_json_error( array( 'message' => 'Image not found' ) );
		}

		$this->optimize_on_upload( $metadata, $attachment_id );

		wp_send_json_success( array( 'message' => 'Image optimized successfully' ) );
	}

	/**
	 * AJAX handler to start bulk optimization
	 */
	public function ajax_bulk_optimize() {
		check_ajax_referer( 'wpspeed-image-optimizer', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$result = $this->bulk_optimize_start();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler to get statistics
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'wpspeed-image-optimizer', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$stats = $this->get_stats();

		wp_send_json_success( $stats );
	}
}
