<?php
/**
 * Enhanced Lazy Loading Class
 *
 * Comprehensive lazy loading system for images, iframes, and videos with native browser support,
 * fallback, and advanced configuration options.
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Lazy_Load class
 */
class VelocityWP_Lazy_Load {

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Image counter for skip first N images
	 *
	 * @var int
	 */
	private $image_count = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'velocitywp_options', array() );

		// Only initialize if lazy loading is enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Register hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'inject_lazy_load_script' ), 999 );
		add_action( 'wp_footer', array( $this, 'inject_lazy_load_styles' ), 998 );
		
		// Content filters
		add_filter( 'the_content', array( $this, 'add_lazy_loading' ), 999 );
		add_filter( 'post_thumbnail_html', array( $this, 'add_lazy_loading' ), 999 );
		add_filter( 'get_avatar', array( $this, 'add_lazy_loading' ), 999 );
		add_filter( 'widget_text', array( $this, 'add_lazy_loading' ), 999 );

		// Background images
		if ( ! empty( $this->settings['lazy_load_backgrounds'] ) ) {
			add_action( 'wp_footer', array( $this, 'lazy_load_backgrounds' ), 1000 );
		}

		// AJAX handler for blur placeholder
		add_action( 'wp_ajax_velocitywp_blur_placeholder', array( $this, 'ajax_blur_placeholder' ) );
		add_action( 'wp_ajax_nopriv_velocitywp_blur_placeholder', array( $this, 'ajax_blur_placeholder' ) );
	}

	/**
	 * Check if lazy loading is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['lazy_load_enabled'] ) ||
		       ! empty( $this->settings['lazy_load_images'] ) ||
		       ! empty( $this->settings['lazy_load_iframes'] );
	}

	/**
	 * Enqueue lazy load scripts
	 */
	public function enqueue_scripts() {
		if ( is_admin() || is_feed() ) {
			return;
		}

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
	public function add_lazy_loading( $content ) {
		if ( is_admin() || is_feed() || wp_doing_ajax() || empty( $content ) ) {
			return $content;
		}

		// Check if native lazy loading is preferred
		if ( ! empty( $this->settings['lazy_load_native'] ) ) {
			$content = $this->add_native_lazy_loading( $content );
		} else {
			// Use JavaScript fallback
			$content = $this->add_js_lazy_loading( $content );
		}

		// Process iframes
		if ( ! empty( $this->settings['lazy_load_iframes'] ) ||
		     ! empty( $this->settings['lazy_load_youtube'] ) ||
		     ! empty( $this->settings['lazy_load_vimeo'] ) ||
		     ! empty( $this->settings['lazy_load_maps'] ) ) {
			$content = $this->lazy_load_iframes( $content );
		}

		// Process videos
		if ( ! empty( $this->settings['lazy_load_videos'] ) ) {
			$content = $this->lazy_load_videos( $content );
		}

		return $content;
	}

	/**
	 * Add native lazy loading
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	public function add_native_lazy_loading( $content ) {
		// Add loading="lazy" to images
		$content = preg_replace_callback(
			'/<img([^>]+?)>/i',
			array( $this, 'add_native_loading_attribute' ),
			$content
		);

		// Handle responsive images (srcset)
		if ( ! empty( $this->settings['lazy_load_images'] ) ) {
			$content = $this->add_lazy_loading_responsive( $content );
		}

		return $content;
	}

	/**
	 * Add native loading attribute callback
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified img tag.
	 */
	private function add_native_loading_attribute( $matches ) {
		$img_tag = $matches[0];

		if ( ! $this->should_lazy_load( $img_tag ) ) {
			return $img_tag;
		}

		// Don't add if already present
		if ( strpos( $matches[1], 'loading=' ) === false ) {
			return '<img' . $matches[1] . ' loading="lazy">';
		}

		return $img_tag;
	}

	/**
	 * Add JavaScript lazy loading
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	public function add_js_lazy_loading( $content ) {
		// Replace src with data-src for JavaScript lazy load
		$content = preg_replace_callback(
			'/<img([^>]+?)src=["\']([^"\']+)["\']([^>]*?)>/i',
			array( $this, 'replace_img_with_data_src' ),
			$content
		);

		// Handle responsive images (srcset)
		$content = $this->add_lazy_loading_responsive( $content );

		return $content;
	}

	/**
	 * Replace img src with data-src callback
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified img tag.
	 */
	private function replace_img_with_data_src( $matches ) {
		$img_tag = $matches[0];

		if ( ! $this->should_lazy_load( $img_tag ) ) {
			return $img_tag;
		}

		// Don't lazy load data URIs
		if ( strpos( $matches[2], 'data:' ) === 0 ) {
			return $img_tag;
		}

		$before_src = $matches[1];
		$src = $matches[2];
		$after_src = $matches[3];

		// Build new img tag
		$new_img = '<img' . $before_src;
		$new_img .= 'data-src="' . esc_attr( $src ) . '"';
		$new_img .= ' src="' . esc_attr( $this->get_placeholder() ) . '"';
		
		// Add class
		if ( strpos( $after_src, 'class=' ) !== false ) {
			$after_src = preg_replace( '/class=["\']([^"\']*)["\']/i', 'class="$1 wpspeed-lazy"', $after_src );
		} else {
			$new_img .= ' class="velocitywp-lazy"';
		}
		
		$new_img .= $after_src . '>';

		return $new_img;
	}

	/**
	 * Add lazy loading for responsive images
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	public function add_lazy_loading_responsive( $content ) {
		// Handle srcset
		$content = preg_replace_callback(
			'/<img([^>]+?)srcset=["\']([^"\']+)["\']([^>]*?)>/i',
			array( $this, 'replace_srcset_with_data_srcset' ),
			$content
		);

		return $content;
	}

	/**
	 * Replace srcset with data-srcset callback
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified img tag.
	 */
	private function replace_srcset_with_data_srcset( $matches ) {
		$img_tag = $matches[0];

		if ( ! $this->should_lazy_load( $img_tag ) ) {
			return $img_tag;
		}

		// Check if using native lazy loading
		if ( ! empty( $this->settings['lazy_load_native'] ) ) {
			return $img_tag;
		}

		// Replace srcset with data-srcset
		$before_srcset = $matches[1];
		$srcset = $matches[2];
		$after_srcset = $matches[3];

		$new_img = '<img' . $before_srcset;
		$new_img .= 'data-srcset="' . esc_attr( $srcset ) . '"';
		
		// Add class
		if ( strpos( $after_srcset, 'class=' ) !== false ) {
			$after_srcset = preg_replace( '/class=["\']([^"\']*)["\']/i', 'class="$1 wpspeed-lazy"', $after_srcset );
		} else {
			$new_img .= ' class="velocitywp-lazy"';
		}
		
		$new_img .= $after_srcset . '>';

		return $new_img;
	}

	/**
	 * Check if image should be lazy loaded
	 *
	 * @param string $img_tag Image tag.
	 * @return bool
	 */
	public function should_lazy_load( $img_tag ) {
		// Check exclusion classes
		$exclude_classes = ! empty( $this->settings['lazy_load_exclude_classes'] ) ?
			explode( ',', $this->settings['lazy_load_exclude_classes'] ) : array();

		foreach ( $exclude_classes as $class ) {
			$class = trim( $class );
			if ( ! empty( $class ) && ( strpos( $img_tag, 'class="' . $class ) !== false ||
			                             strpos( $img_tag, "class='" . $class ) !== false ||
			                             strpos( $img_tag, $class ) !== false ) ) {
				return false;
			}
		}

		// Check if skip first N images
		if ( ! empty( $this->settings['lazy_load_skip_first'] ) ) {
			$this->image_count++;

			if ( $this->image_count <= intval( $this->settings['lazy_load_skip_first'] ) ) {
				return false;
			}
		}

		// Check if already has loading attribute
		if ( strpos( $img_tag, 'loading=' ) !== false ) {
			return false;
		}

		// Check data-src (already lazy loaded)
		if ( strpos( $img_tag, 'data-src=' ) !== false ) {
			return false;
		}

		return true;
	}

	/**
	 * Lazy load iframes
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	public function lazy_load_iframes( $content ) {
		// YouTube
		if ( ! empty( $this->settings['lazy_load_youtube'] ) ) {
			$content = preg_replace_callback(
				'/<iframe([^>]+?)src=["\']([^"\']*youtube[^"\']+)["\']([^>]*?)>/i',
				array( $this, 'replace_youtube_iframe' ),
				$content
			);
		}

		// Vimeo
		if ( ! empty( $this->settings['lazy_load_vimeo'] ) ) {
			$content = preg_replace_callback(
				'/<iframe([^>]+?)src=["\']([^"\']*vimeo[^"\']+)["\']([^>]*?)>/i',
				array( $this, 'replace_vimeo_iframe' ),
				$content
			);
		}

		// Google Maps
		if ( ! empty( $this->settings['lazy_load_maps'] ) ) {
			$content = preg_replace_callback(
				'/<iframe([^>]+?)src=["\']([^"\']*google\.com\/maps[^"\']+)["\']([^>]*?)>/i',
				array( $this, 'replace_maps_iframe' ),
				$content
			);
		}

		// Generic iframes
		if ( ! empty( $this->settings['lazy_load_iframes'] ) ) {
			$content = preg_replace_callback(
				'/<iframe([^>]+?)src=["\']([^"\']+)["\']([^>]*?)>/i',
				array( $this, 'replace_generic_iframe' ),
				$content
			);
		}

		return $content;
	}

	/**
	 * Replace YouTube iframe
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified iframe or placeholder.
	 */
	public function replace_youtube_iframe( $matches ) {
		// Extract video ID
		preg_match( '/youtube\.com\/embed\/([^?&]+)/', $matches[2], $video_id );

		if ( empty( $video_id[1] ) ) {
			// Try alternative pattern
			preg_match( '/youtube\.com\/v\/([^?&]+)/', $matches[2], $video_id );
			if ( empty( $video_id[1] ) ) {
				return $matches[0];
			}
		}

		$video_id = $video_id[1];

		// Create thumbnail preview with play button
		$thumbnail = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';

		$html = '<div class="velocitywp-lazy-youtube" data-id="' . esc_attr( $video_id ) . '" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;background:#000;">';
		$html .= '<img src="' . esc_url( $thumbnail ) . '" alt="YouTube video" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;">';
		$html .= '<button class="velocitywp-play-button" aria-label="Play video" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);border:0;background:transparent;cursor:pointer;padding:0;">';
		$html .= '<svg width="68" height="48" viewBox="0 0 68 48"><path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg>';
		$html .= '</button>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Replace Vimeo iframe
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified iframe or placeholder.
	 */
	public function replace_vimeo_iframe( $matches ) {
		// Extract video ID
		preg_match( '/vimeo\.com\/video\/([0-9]+)/', $matches[2], $video_id );

		if ( empty( $video_id[1] ) ) {
			return $matches[0];
		}

		$video_id = $video_id[1];

		$html = '<div class="velocitywp-lazy-vimeo" data-id="' . esc_attr( $video_id ) . '" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;background:#000;">';
		$html .= '<button class="velocitywp-play-button" aria-label="Play video" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);border:0;background:#00adef;color:#fff;font-size:16px;padding:10px 20px;border-radius:3px;cursor:pointer;">Play Vimeo Video</button>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Replace Google Maps iframe
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified iframe or placeholder.
	 */
	public function replace_maps_iframe( $matches ) {
		$src = $matches[2];

		$html = '<div class="velocitywp-lazy-maps" data-src="' . esc_attr( $src ) . '" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;background:#f0f0f0;">';
		$html .= '<button class="velocitywp-load-map" aria-label="Load map" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);border:0;background:#4285f4;color:#fff;font-size:16px;padding:10px 20px;border-radius:3px;cursor:pointer;">Load Google Map</button>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Replace generic iframe
	 *
	 * @param array $matches Regex matches.
	 * @return string Modified iframe.
	 */
	private function replace_generic_iframe( $matches ) {
		$iframe_tag = $matches[0];

		if ( ! $this->should_lazy_load_iframe( $iframe_tag ) ) {
			return $iframe_tag;
		}

		$src = $matches[2];

		// Skip if already processed (YouTube, Vimeo, Maps)
		if ( strpos( $src, 'youtube.com' ) !== false ||
		     strpos( $src, 'vimeo.com' ) !== false ||
		     strpos( $src, 'google.com/maps' ) !== false ) {
			return $iframe_tag;
		}

		// Add data-src and lazy class
		$new_iframe = '<iframe' . $matches[1];
		$new_iframe .= 'data-src="' . esc_attr( $src ) . '"';
		$new_iframe .= ' src="about:blank"';

		// Add class
		if ( strpos( $matches[3], 'class=' ) !== false ) {
			$matches[3] = preg_replace( '/class=["\']([^"\']*)["\']/i', 'class="$1 wpspeed-lazy-iframe"', $matches[3] );
		} else {
			$new_iframe .= ' class="velocitywp-lazy-iframe"';
		}

		$new_iframe .= $matches[3] . '>';

		return $new_iframe;
	}

	/**
	 * Check if iframe should be lazy loaded
	 *
	 * @param string $iframe_tag Iframe tag.
	 * @return bool
	 */
	public function should_lazy_load_iframe( $iframe_tag ) {
		// Check if already has loading attribute or data-src
		if ( strpos( $iframe_tag, 'loading=' ) !== false ||
		     strpos( $iframe_tag, 'data-src=' ) !== false ) {
			return false;
		}

		return true;
	}

	/**
	 * Lazy load videos
	 *
	 * @param string $content Content to process.
	 * @return string Modified content.
	 */
	public function lazy_load_videos( $content ) {
		// Set preload="none" for video elements
		$content = preg_replace_callback(
			'/<video([^>]*?)>/i',
			function( $matches ) {
				$video_tag = $matches[0];

				// Skip if already has preload attribute
				if ( strpos( $video_tag, 'preload=' ) !== false ) {
					return $video_tag;
				}

				return '<video' . $matches[1] . ' preload="none">';
			},
			$content
		);

		return $content;
	}

	/**
	 * Background image lazy loading script
	 */
	public function lazy_load_backgrounds() {
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var lazyBackgrounds = document.querySelectorAll('[data-bg]');
			
			if ('IntersectionObserver' in window) {
				var lazyBackgroundObserver = new IntersectionObserver(function(entries) {
					entries.forEach(function(entry) {
						if (entry.isIntersecting) {
							var element = entry.target;
							element.style.backgroundImage = 'url(' + element.dataset.bg + ')';
							element.classList.add('wpspeed-bg-loaded');
							lazyBackgroundObserver.unobserve(element);
						}
					});
				});
				
				lazyBackgrounds.forEach(function(lazyBackground) {
					lazyBackgroundObserver.observe(lazyBackground);
				});
			} else {
				// Fallback: load all backgrounds immediately
				lazyBackgrounds.forEach(function(lazyBackground) {
					lazyBackground.style.backgroundImage = 'url(' + lazyBackground.dataset.bg + ')';
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Get lazy load placeholder image
	 *
	 * @return string Placeholder image data URI.
	 */
	public function get_placeholder() {
		$type = ! empty( $this->settings['lazy_load_placeholder'] ) ?
			$this->settings['lazy_load_placeholder'] : 'transparent';

		switch ( $type ) {
			case 'transparent':
				return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

			case 'grey':
				return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3Crect fill="%23f0f0f0" width="1" height="1"/%3E%3C/svg%3E';

			case 'blur':
				// Return URL to blur-up placeholder script
				return admin_url( 'admin-ajax.php?action=velocitywp_blur_placeholder' );

			default:
				return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
		}
	}

	/**
	 * Generate blur placeholder
	 *
	 * @param string $image_url Image URL.
	 * @return string Blur placeholder data URI.
	 */
	public function generate_blur_placeholder( $image_url ) {
		// This is a simplified version - in production, you would generate a real blurred thumbnail
		return $this->get_placeholder();
	}

	/**
	 * Inject lazy load script
	 */
	public function inject_lazy_load_script() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		// Don't inject if using native lazy loading only
		if ( ! empty( $this->settings['lazy_load_native'] ) &&
		     empty( $this->settings['lazy_load_youtube'] ) &&
		     empty( $this->settings['lazy_load_vimeo'] ) &&
		     empty( $this->settings['lazy_load_maps'] ) ) {
			return;
		}

		$threshold = ! empty( $this->settings['lazy_load_threshold'] ) ?
			intval( $this->settings['lazy_load_threshold'] ) : 200;
		?>
		<script>
		(function() {
			'use strict';
			
			// Check for IntersectionObserver support
			if (!('IntersectionObserver' in window)) {
				// Load all images immediately
				var lazyImages = document.querySelectorAll('.wpspeed-lazy');
				lazyImages.forEach(function(img) {
					if (img.dataset.src) {
						img.src = img.dataset.src;
					}
					if (img.dataset.srcset) {
						img.srcset = img.dataset.srcset;
					}
				});
				
				var lazyIframes = document.querySelectorAll('.wpspeed-lazy-iframe');
				lazyIframes.forEach(function(iframe) {
					if (iframe.dataset.src) {
						iframe.src = iframe.dataset.src;
					}
				});
				return;
			}
			
			// Lazy load images with IntersectionObserver
			var lazyImageObserver = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						var lazyImage = entry.target;
						
						if (lazyImage.dataset.src) {
							lazyImage.src = lazyImage.dataset.src;
						}
						
						if (lazyImage.dataset.srcset) {
							lazyImage.srcset = lazyImage.dataset.srcset;
						}
						
						lazyImage.classList.remove('wpspeed-lazy');
						lazyImage.classList.add('wpspeed-lazy-loaded');
						lazyImageObserver.unobserve(lazyImage);
					}
				});
			}, {
				rootMargin: '<?php echo intval( $threshold ); ?>px'
			});
			
			var lazyImages = document.querySelectorAll('.wpspeed-lazy');
			lazyImages.forEach(function(lazyImage) {
				lazyImageObserver.observe(lazyImage);
			});
			
			// Lazy load iframes
			var lazyIframeObserver = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						var lazyIframe = entry.target;
						
						if (lazyIframe.dataset.src) {
							lazyIframe.src = lazyIframe.dataset.src;
						}
						
						lazyIframe.classList.remove('wpspeed-lazy-iframe');
						lazyIframe.classList.add('wpspeed-lazy-loaded');
						lazyIframeObserver.unobserve(lazyIframe);
					}
				});
			}, {
				rootMargin: '<?php echo intval( $threshold ); ?>px'
			});
			
			var lazyIframes = document.querySelectorAll('.wpspeed-lazy-iframe');
			lazyIframes.forEach(function(lazyIframe) {
				lazyIframeObserver.observe(lazyIframe);
			});
			
			// YouTube lazy load
			var lazyYouTubes = document.querySelectorAll('.wpspeed-lazy-youtube');
			lazyYouTubes.forEach(function(element) {
				var playButton = element.querySelector('.wpspeed-play-button');
				if (playButton) {
					playButton.addEventListener('click', function() {
						var iframe = document.createElement('iframe');
						iframe.setAttribute('frameborder', '0');
						iframe.setAttribute('allowfullscreen', '');
						iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
						iframe.setAttribute('src', 'https://www.youtube.com/embed/' + element.dataset.id + '?autoplay=1');
						iframe.style.position = 'absolute';
						iframe.style.top = '0';
						iframe.style.left = '0';
						iframe.style.width = '100%';
						iframe.style.height = '100%';
						
						element.innerHTML = '';
						element.appendChild(iframe);
					});
				}
			});
			
			// Vimeo lazy load
			var lazyVimeos = document.querySelectorAll('.wpspeed-lazy-vimeo');
			lazyVimeos.forEach(function(element) {
				var playButton = element.querySelector('.wpspeed-play-button');
				if (playButton) {
					playButton.addEventListener('click', function() {
						var iframe = document.createElement('iframe');
						iframe.setAttribute('frameborder', '0');
						iframe.setAttribute('allowfullscreen', '');
						iframe.setAttribute('src', 'https://player.vimeo.com/video/' + element.dataset.id + '?autoplay=1');
						iframe.style.position = 'absolute';
						iframe.style.top = '0';
						iframe.style.left = '0';
						iframe.style.width = '100%';
						iframe.style.height = '100%';
						
						element.innerHTML = '';
						element.appendChild(iframe);
					});
				}
			});
			
			// Google Maps lazy load
			var lazyMaps = document.querySelectorAll('.wpspeed-lazy-maps');
			lazyMaps.forEach(function(element) {
				var loadButton = element.querySelector('.wpspeed-load-map');
				if (loadButton) {
					loadButton.addEventListener('click', function() {
						var iframe = document.createElement('iframe');
						iframe.setAttribute('frameborder', '0');
						iframe.setAttribute('src', element.dataset.src);
						iframe.style.position = 'absolute';
						iframe.style.top = '0';
						iframe.style.left = '0';
						iframe.style.width = '100%';
						iframe.style.height = '100%';
						
						element.innerHTML = '';
						element.appendChild(iframe);
					});
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Inject lazy load styles
	 */
	public function inject_lazy_load_styles() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		$fade_in = ! empty( $this->settings['lazy_load_fade_in'] );
		$fade_duration = ! empty( $this->settings['lazy_load_fade_duration'] ) ?
			intval( $this->settings['lazy_load_fade_duration'] ) : 300;
		?>
		<style>
		.wpspeed-lazy {
			opacity: 0;
			transition: opacity <?php echo $fade_in ? $fade_duration : 0; ?>ms ease-in;
		}
		.wpspeed-lazy-loaded {
			opacity: 1;
		}
		.wpspeed-play-button:hover {
			opacity: 0.9;
		}
		.wpspeed-load-map:hover {
			background: #3367d6;
		}
		</style>
		<?php
	}

	/**
	 * AJAX handler for blur placeholder
	 */
	public function ajax_blur_placeholder() {
		// For now, return a simple placeholder
		// In a full implementation, you would generate a blurred version
		header( 'Content-Type: image/gif' );
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}
}
