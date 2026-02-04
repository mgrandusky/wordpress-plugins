<?php
/**
 * Mobile Optimizer Class
 *
 * Mobile-specific optimizations and responsive handling
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Mobile_Optimizer class
 */
class VelocityWP_Mobile_Optimizer {

	/**
	 * Is mobile device
	 *
	 * @var bool
	 */
	private $is_mobile = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->is_mobile = wp_is_mobile();
		
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize mobile optimization
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['mobile_optimize'] ) ) {
			return;
		}

		// Separate mobile cache
		if ( ! empty( $options['mobile_separate_cache'] ) ) {
			add_filter( 'velocitywp_cache_key', array( $this, 'add_mobile_cache_key' ) );
		}

		// Disable features on mobile
		if ( $this->is_mobile ) {
			$this->apply_mobile_optimizations( $options );
		}
	}

	/**
	 * Apply mobile-specific optimizations
	 *
	 * @param array $options Plugin options.
	 */
	private function apply_mobile_optimizations( $options ) {
		// Disable animations
		if ( ! empty( $options['mobile_disable_animations'] ) ) {
			add_action( 'wp_head', array( $this, 'disable_animations' ), 999 );
		}

		// Lazy load more aggressively
		if ( ! empty( $options['mobile_aggressive_lazy'] ) ) {
			add_filter( 'velocitywp_lazy_load_skip_images', '__return_zero' );
		}

		// Remove unnecessary scripts
		if ( ! empty( $options['mobile_remove_scripts'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'remove_scripts' ), 999 );
		}

		// Serve smaller images
		if ( ! empty( $options['mobile_small_images'] ) ) {
			add_filter( 'wp_get_attachment_image_src', array( $this, 'get_smaller_image' ), 10, 4 );
		}

		// Disable embeds
		if ( ! empty( $options['mobile_disable_embeds'] ) ) {
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		}

		// Reduce max image size
		if ( ! empty( $options['mobile_max_image_width'] ) ) {
			add_filter( 'the_content', array( $this, 'resize_content_images' ), 999 );
		}
	}

	/**
	 * Add mobile suffix to cache key
	 *
	 * @param string $key Cache key.
	 * @return string Modified key.
	 */
	public function add_mobile_cache_key( $key ) {
		if ( $this->is_mobile ) {
			$key .= '_mobile';
		}

		return $key;
	}

	/**
	 * Disable CSS animations on mobile
	 */
	public function disable_animations() {
		?>
		<style id="velocitywp-mobile-no-animations">
		* {
			animation-duration: 0s !important;
			transition-duration: 0s !important;
		}
		</style>
		<?php
	}

	/**
	 * Remove unnecessary scripts on mobile
	 */
	public function remove_scripts() {
		$options = get_option( 'velocitywp_options', array() );
		
		$scripts_to_remove = array();
		
		if ( ! empty( $options['mobile_scripts_to_remove'] ) ) {
			$scripts_to_remove = array_map( 'trim', explode( "\n", $options['mobile_scripts_to_remove'] ) );
		}

		// Default scripts to remove on mobile
		$default_remove = array(
			'comment-reply',
			'wp-embed',
		);

		$scripts_to_remove = array_merge( $default_remove, $scripts_to_remove );
		$scripts_to_remove = apply_filters( 'velocitywp_mobile_remove_scripts', $scripts_to_remove );

		foreach ( $scripts_to_remove as $handle ) {
			if ( ! empty( $handle ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}

	/**
	 * Get smaller image size for mobile
	 *
	 * @param array        $image         Image data.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size          Image size.
	 * @param bool         $icon          Whether to use icon.
	 * @return array Modified image data.
	 */
	public function get_smaller_image( $image, $attachment_id, $size, $icon ) {
		if ( ! $this->is_mobile || ! is_array( $image ) ) {
			return $image;
		}

		// Skip if already a smaller size
		if ( is_string( $size ) && in_array( $size, array( 'thumbnail', 'medium' ), true ) ) {
			return $image;
		}

		// Get medium size instead
		$medium_image = wp_get_attachment_image_src( $attachment_id, 'medium', $icon );
		
		if ( $medium_image ) {
			return $medium_image;
		}

		return $image;
	}

	/**
	 * Resize images in content for mobile
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function resize_content_images( $content ) {
		$options = get_option( 'velocitywp_options', array() );
		$max_width = ! empty( $options['mobile_max_image_width'] ) ? intval( $options['mobile_max_image_width'] ) : 480;

		// Find all img tags
		preg_match_all( '/<img[^>]+>/i', $content, $matches );

		if ( empty( $matches[0] ) ) {
			return $content;
		}

		foreach ( $matches[0] as $img_tag ) {
			$new_img_tag = $img_tag;

			// Check if width is already set and smaller
			if ( preg_match( '/width=["\']?(\d+)/i', $img_tag, $width_match ) ) {
				if ( intval( $width_match[1] ) <= $max_width ) {
					continue;
				}
			}

			// Add or update max-width style
			if ( preg_match( '/style=["\']([^"\']*)["\']/', $img_tag, $style_match ) ) {
				$style = $style_match[1];
				$style .= '; max-width: ' . $max_width . 'px; height: auto;';
				$new_img_tag = str_replace( $style_match[0], 'style="' . esc_attr( $style ) . '"', $img_tag );
			} else {
				$new_img_tag = str_replace( '<img', '<img style="max-width: ' . $max_width . 'px; height: auto;"', $img_tag );
			}

			$content = str_replace( $img_tag, $new_img_tag, $content );
		}

		return $content;
	}

	/**
	 * Detect device type
	 *
	 * @return string Device type (mobile, tablet, desktop).
	 */
	public function detect_device_type() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';

		// Tablet detection
		if ( preg_match( '/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent ) ) {
			return 'tablet';
		}

		// Mobile detection
		if ( wp_is_mobile() ) {
			return 'mobile';
		}

		return 'desktop';
	}

	/**
	 * Get mobile cache statistics
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		$stats = get_option( 'velocitywp_mobile_stats', array(
			'mobile_visits'  => 0,
			'tablet_visits'  => 0,
			'desktop_visits' => 0,
		) );

		return $stats;
	}

	/**
	 * Track mobile visit
	 */
	public function track_visit() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		$device_type = $this->detect_device_type();
		$stats = $this->get_statistics();

		$stats[ $device_type . '_visits' ]++;

		update_option( 'velocitywp_mobile_stats', $stats, false );
	}
}
