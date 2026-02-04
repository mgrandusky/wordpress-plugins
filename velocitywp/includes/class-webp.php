<?php
/**
 * WebP Image Conversion Class
 *
 * Automatic WebP conversion and smart serving
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_WebP class
 */
class VelocityWP_WebP {

	/**
	 * Constructor
	 */
	public function __construct() {
		$options = get_option( 'velocitywp_options', array() );
		
		if ( ! empty( $options['webp_enabled'] ) ) {
			// Auto-convert on upload
			if ( ! empty( $options['webp_auto_convert'] ) ) {
				add_filter( 'wp_generate_attachment_metadata', array( $this, 'convert_on_upload' ), 10, 2 );
			}
			
			// Serve WebP images
			add_filter( 'wp_get_attachment_url', array( $this, 'serve_webp' ), 10, 2 );
			add_filter( 'the_content', array( $this, 'replace_images_with_picture' ), 999 );
			
			// Delete WebP on image deletion
			add_action( 'delete_attachment', array( $this, 'delete_webp_on_delete' ) );
		}
		
		// AJAX handlers
		add_action( 'wp_ajax_velocitywp_bulk_convert_webp', array( $this, 'ajax_bulk_convert' ) );
		add_action( 'wp_ajax_velocitywp_get_conversion_progress', array( $this, 'ajax_get_progress' ) );
	}

	/**
	 * Convert image to WebP on upload
	 *
	 * @param array $metadata      Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Modified metadata
	 */
	public function convert_on_upload( $metadata, $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		
		if ( $this->is_convertible_image( $file ) ) {
			$this->convert_to_webp( $file );
			
			// Convert all sizes
			if ( ! empty( $metadata['sizes'] ) ) {
				$upload_dir = wp_upload_dir();
				$base_dir = dirname( $file );
				
				foreach ( $metadata['sizes'] as $size => $size_data ) {
					$size_file = $base_dir . '/' . $size_data['file'];
					if ( file_exists( $size_file ) ) {
						$this->convert_to_webp( $size_file );
					}
				}
			}
		}
		
		return $metadata;
	}

	/**
	 * Check if image can be converted
	 *
	 * @param string $file File path.
	 * @return bool True if convertible
	 */
	private function is_convertible_image( $file ) {
		$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		$allowed = array( 'jpg', 'jpeg', 'png' );
		
		return in_array( $extension, $allowed, true ) && file_exists( $file );
	}

	/**
	 * Convert image to WebP
	 *
	 * @param string $file File path.
	 * @return bool True on success
	 */
	private function convert_to_webp( $file ) {
		if ( ! function_exists( 'imagewebp' ) ) {
			return false;
		}
		
		$options = get_option( 'velocitywp_options', array() );
		$quality = isset( $options['webp_quality'] ) ? absint( $options['webp_quality'] ) : 85;
		
		$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		$webp_file = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file );
		
		// Don't reconvert if WebP exists and is newer
		if ( file_exists( $webp_file ) && filemtime( $webp_file ) >= filemtime( $file ) ) {
			return true;
		}
		
		// Load image
		$image = null;
		switch ( $extension ) {
			case 'jpg':
			case 'jpeg':
				$image = @imagecreatefromjpeg( $file );
				break;
			case 'png':
				$image = @imagecreatefrompng( $file );
				// Preserve transparency
				imagealphablending( $image, false );
				imagesavealpha( $image, true );
				break;
		}
		
		if ( ! $image ) {
			return false;
		}
		
		// Convert to WebP
		$result = imagewebp( $image, $webp_file, $quality );
		imagedestroy( $image );
		
		return $result;
	}

	/**
	 * Serve WebP images when supported
	 *
	 * @param string $url           Image URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string Modified URL
	 */
	public function serve_webp( $url, $attachment_id ) {
		// Check if browser supports WebP
		if ( ! $this->browser_supports_webp() ) {
			return $url;
		}
		
		$options = get_option( 'velocitywp_options', array() );
		if ( empty( $options['webp_delivery'] ) || $options['webp_delivery'] !== 'url_rewrite' ) {
			return $url;
		}
		
		// Check if WebP version exists
		$webp_url = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $url );
		$file = get_attached_file( $attachment_id );
		$webp_file = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file );
		
		if ( file_exists( $webp_file ) ) {
			return $webp_url;
		}
		
		return $url;
	}

	/**
	 * Replace images with picture tags
	 *
	 * @param string $content Post content.
	 * @return string Modified content
	 */
	public function replace_images_with_picture( $content ) {
		$options = get_option( 'velocitywp_options', array() );
		
		if ( empty( $options['webp_delivery'] ) || $options['webp_delivery'] !== 'picture_tag' ) {
			return $content;
		}
		
		if ( ! $this->browser_supports_webp() ) {
			return $content;
		}
		
		// Find all img tags
		preg_match_all( '/<img[^>]+>/i', $content, $matches );
		
		if ( empty( $matches[0] ) ) {
			return $content;
		}
		
		foreach ( $matches[0] as $img_tag ) {
			// Extract src
			if ( preg_match( '/src=["\']([^"\']+)["\']/', $img_tag, $src_match ) ) {
				$src = $src_match[1];
				$webp_src = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $src );
				
				// Check if WebP exists
				$upload_dir = wp_upload_dir();
				$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_src );
				
				if ( file_exists( $file_path ) ) {
					$picture = '<picture>';
					$picture .= '<source srcset="' . esc_url( $webp_src ) . '" type="image/webp">';
					$picture .= $img_tag;
					$picture .= '</picture>';
					
					$content = str_replace( $img_tag, $picture, $content );
				}
			}
		}
		
		return $content;
	}

	/**
	 * Check if browser supports WebP
	 *
	 * @return bool True if supported
	 */
	private function browser_supports_webp() {
		if ( ! isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			return false;
		}
		
		return strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false;
	}

	/**
	 * Delete WebP files when image is deleted
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function delete_webp_on_delete( $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		
		if ( $this->is_convertible_image( $file ) ) {
			$webp_file = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file );
			if ( file_exists( $webp_file ) ) {
				@unlink( $webp_file );
			}
			
			// Delete size variants
			$metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $metadata['sizes'] ) ) {
				$base_dir = dirname( $file );
				foreach ( $metadata['sizes'] as $size_data ) {
					$size_file = $base_dir . '/' . $size_data['file'];
					$webp_size = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $size_file );
					if ( file_exists( $webp_size ) ) {
						@unlink( $webp_size );
					}
				}
			}
		}
	}

	/**
	 * Bulk convert images via AJAX
	 */
	public function ajax_bulk_convert() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$limit = 10; // Process 10 images per batch
		
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/jpg', 'image/png' ),
			'post_status'    => 'inherit',
			'posts_per_page' => $limit,
			'offset'         => $offset,
		);
		
		$attachments = get_posts( $args );
		$converted = 0;
		
		foreach ( $attachments as $attachment ) {
			$file = get_attached_file( $attachment->ID );
			if ( $this->convert_to_webp( $file ) ) {
				$converted++;
			}
			
			// Convert sizes
			$metadata = wp_get_attachment_metadata( $attachment->ID );
			if ( ! empty( $metadata['sizes'] ) ) {
				$base_dir = dirname( $file );
				foreach ( $metadata['sizes'] as $size_data ) {
					$size_file = $base_dir . '/' . $size_data['file'];
					if ( file_exists( $size_file ) ) {
						$this->convert_to_webp( $size_file );
					}
				}
			}
		}
		
		// Get total count
		$total_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/jpg', 'image/png' ),
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		$total = count( get_posts( $total_args ) );
		
		$progress = $total > 0 ? min( 100, ( ( $offset + $converted ) / $total ) * 100 ) : 100;
		
		wp_send_json_success( array(
			'converted' => $converted,
			'total'     => $total,
			'progress'  => round( $progress, 2 ),
			'complete'  => ( $offset + $limit ) >= $total,
		) );
	}

	/**
	 * Get conversion progress via AJAX
	 */
	public function ajax_get_progress() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		$progress = get_transient( 'velocitywp_webp_conversion_progress' );
		
		wp_send_json_success( array( 'progress' => $progress ? $progress : 0 ) );
	}
}
