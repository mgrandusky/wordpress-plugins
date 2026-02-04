<?php
/**
 * Critical CSS Generation Class
 *
 * Automatically extracts and inlines above-the-fold CSS
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Critical_CSS class
 */
class VelocityWP_Critical_CSS {

	/**
	 * Settings array
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'velocitywp_options', array() );
		
		if ( $this->is_enabled() ) {
			add_action( 'wp_head', array( $this, 'inject_critical_css' ), 1 );
			add_filter( 'style_loader_tag', array( $this, 'defer_non_critical_css' ), 10, 4 );
			add_action( 'save_post', array( $this, 'clear_critical_css_cache' ) );
			add_action( 'switch_theme', array( $this, 'clear_all_critical_css' ) );
		}

		// Admin hooks
		add_action( 'add_meta_boxes', array( $this, 'add_critical_css_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_critical_css_meta' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_velocitywp_generate_critical_css', array( $this, 'ajax_generate_critical_css' ) );
		add_action( 'wp_ajax_velocitywp_clear_critical_css', array( $this, 'ajax_clear_critical_css' ) );
		add_action( 'wp_ajax_velocitywp_regenerate_all_critical_css', array( $this, 'ajax_regenerate_all_critical_css' ) );
		add_action( 'wp_ajax_velocitywp_save_manual_css', array( $this, 'ajax_save_manual_css' ) );
		add_action( 'wp_ajax_velocitywp_delete_template_css', array( $this, 'ajax_delete_template_css' ) );
		
		// Cron hook for background generation
		add_action( 'velocitywp_generate_critical_css', array( $this, 'process_critical_css_generation' ) );
	}

	/**
	 * Check if Critical CSS is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['critical_css_enabled'] );
	}

	/**
	 * Check if current URL should be excluded from critical CSS
	 *
	 * @return bool
	 */
	private function is_excluded() {
		if ( empty( $this->settings['critical_css_exclude'] ) ) {
			return false;
		}

		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
		if ( empty( $current_url ) ) {
			return false;
		}

		$exclude_patterns = explode( "\n", $this->settings['critical_css_exclude'] );

		foreach ( $exclude_patterns as $pattern ) {
			$pattern = trim( $pattern );
			if ( empty( $pattern ) ) {
				continue;
			}

			// Convert wildcard pattern to regex
			$regex = str_replace( '\*', '.*', preg_quote( $pattern, '/' ) );
			if ( preg_match( '/^' . $regex . '$/i', $current_url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Inject critical CSS in head
	 */
	public function inject_critical_css() {
		// Check if current URL is excluded
		if ( $this->is_excluded() ) {
			return;
		}

		$critical_css = $this->get_critical_css();
		
		if ( ! empty( $critical_css ) ) {
			echo '<style id="velocitywp-critical-css">' . $critical_css . '</style>' . "\n";
			
			// Add noscript fallback for full CSS
			echo '<noscript><link rel="stylesheet" href="' . esc_url( get_stylesheet_uri() ) . '"></noscript>' . "\n";
		}
	}

	/**
	 * Get critical CSS for current page
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return string Critical CSS content
	 */
	private function get_critical_css( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_queried_object_id();
		}
		
		// Check for per-page manual critical CSS in post meta
		if ( $post_id ) {
			$manual_css = get_post_meta( $post_id, '_velocitywp_critical_css', true );
			if ( ! empty( $manual_css ) ) {
				return $manual_css;
			}
		}
		
		// Check for global manual critical CSS
		if ( ! empty( $this->settings['critical_css_manual'] ) ) {
			return $this->settings['critical_css_manual'];
		}
		
		// Get current template type
		$template = $this->get_current_template();
		
		// Determine viewport
		$viewport = 'desktop';
		if ( ! empty( $this->settings['critical_css_mobile_separate'] ) && wp_is_mobile() ) {
			$viewport = 'mobile';
		}
		
		// Get template critical CSS from database
		$critical_css_data = get_option( 'velocitywp_critical_css', array() );
		$template_key = $template . '_' . $viewport;
		
		if ( isset( $critical_css_data[ $template_key ]['css'] ) ) {
			return $critical_css_data[ $template_key ]['css'];
		}
		
		// Fallback to desktop if mobile not found
		if ( 'mobile' === $viewport && isset( $critical_css_data[ $template . '_desktop' ]['css'] ) ) {
			return $critical_css_data[ $template . '_desktop' ]['css'];
		}
		
		// Fallback to general template
		if ( isset( $critical_css_data['general_desktop']['css'] ) ) {
			return $critical_css_data['general_desktop']['css'];
		}
		
		// Auto-generate if enabled
		if ( ! empty( $this->settings['critical_css_mode'] ) && 'auto' === $this->settings['critical_css_mode'] ) {
			$url = $this->get_template_url( $template );
			if ( $url ) {
				$critical_css = $this->generate_critical_css( $url, $template );
				
				if ( $critical_css ) {
					return $critical_css;
				}
			}
		}
		
		return '';
	}

	/**
	 * Get current template type
	 *
	 * @return string Template type
	 */
	public function get_current_template() {
		if ( is_front_page() ) {
			return 'home';
		} elseif ( is_singular( 'post' ) ) {
			return 'single-post';
		} elseif ( is_singular( 'page' ) ) {
			return 'single-page';
		} elseif ( is_singular() ) {
			return 'single-' . get_post_type();
		} elseif ( is_archive() ) {
			return 'archive';
		} elseif ( is_search() ) {
			return 'search';
		} elseif ( is_404() ) {
			return '404';
		}
		
		return 'general';
	}

	/**
	 * Get all available templates
	 *
	 * @return array Array of template names
	 */
	private function get_all_templates() {
		return array(
			'home',
			'single-post',
			'single-page',
			'archive',
			'search',
			'404'
		);
	}

	/**
	 * Get template URL for generation
	 *
	 * @param string $template Template type.
	 * @return string|false URL or false
	 */
	private function get_template_url( $template ) {
		switch ( $template ) {
			case 'home':
				return home_url( '/' );
			
			case 'single-post':
				$post = get_posts( array( 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish' ) );
				return ! empty( $post ) ? get_permalink( $post[0] ) : false;
			
			case 'single-page':
				$page = get_posts( array( 'numberposts' => 1, 'post_type' => 'page', 'post_status' => 'publish' ) );
				return ! empty( $page ) ? get_permalink( $page[0] ) : false;
			
			case 'archive':
				$categories = get_categories( array( 'number' => 1 ) );
				return ! empty( $categories ) ? get_category_link( $categories[0]->term_id ) : false;
			
			case 'search':
				// Use a generic search term that represents typical usage
				return home_url( '/?s=wordpress' );
			
			case '404':
				// Generate unique 404 path to avoid conflicts
				return home_url( '/404-page-not-found-' . time() );
			
			default:
				return home_url( '/' );
		}
	}

	/**
	 * Defer non-critical CSS
	 *
	 * @param string $tag    The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @param string $href   The stylesheet's source URL.
	 * @param string $media  The stylesheet's media attribute.
	 * @return string Modified tag
	 */
	public function defer_non_critical_css( $tag, $handle, $href, $media ) {
		if ( empty( $this->settings['critical_css_defer'] ) ) {
			return $tag;
		}
		
		// Don't defer critical stylesheets
		$critical_handles = array( 'admin-bar', 'dashicons' );
		
		// Add user-defined exclusions
		if ( ! empty( $this->settings['critical_css_exclude_handles'] ) ) {
			$exclude_handles = array_map( 'trim', explode( ',', $this->settings['critical_css_exclude_handles'] ) );
			$critical_handles = array_merge( $critical_handles, $exclude_handles );
		}
		
		if ( in_array( $handle, $critical_handles, true ) ) {
			return $tag;
		}
		
		// Get defer method
		$defer_method = ! empty( $this->settings['critical_css_defer_method'] ) ? 
			$this->settings['critical_css_defer_method'] : 'media-print';
		
		if ( 'preload' === $defer_method ) {
			// Preload method (modern browsers)
			$deferred_html = '<link rel="preload" as="style" href="' . esc_url( $href ) . '" onload="this.onload=null;this.rel=\'stylesheet\'">';
			$deferred_html .= '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"></noscript>';
			return $deferred_html;
		} else {
			// Media print method (compatible)
			$tag = str_replace( "media='$media'", "media='print' onload=\"this.media='$media'\"", $tag );
			$tag = str_replace( 'media="' . $media . '"', 'media="print" onload="this.media=\'' . $media . '\'"', $tag );
			
			// Add noscript fallback
			$noscript = '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '" media="' . esc_attr( $media ) . '"></noscript>';
			$tag .= "\n" . $noscript;
			
			return $tag;
		}
	}

	/**
	 * Generate critical CSS for a URL
	 *
	 * @param string $url URL to generate critical CSS for.
	 * @param string $template Template type (home, single-post, etc.).
	 * @return string|false Critical CSS or false on failure
	 */
	public function generate_critical_css( $url, $template = 'home' ) {
		// Check if API key is configured
		$api_key = ! empty( $this->settings['critical_css_api_key'] ) ? 
			$this->settings['critical_css_api_key'] : '';
		
		if ( ! empty( $api_key ) ) {
			// Use external API
			$critical_css = $this->generate_via_api( $url, $api_key );
			if ( $critical_css ) {
				$this->save_critical_css( $template, $critical_css );
				return $critical_css;
			}
		}
		
		// Fallback to local generation
		return $this->generate_local_critical_css( $url, $template );
	}

	/**
	 * Generate critical CSS via external API
	 *
	 * @param string $url URL to generate critical CSS for.
	 * @param string $api_key API key.
	 * @return string|false Critical CSS or false on failure
	 */
	private function generate_via_api( $url, $api_key ) {
		$api_provider = ! empty( $this->settings['critical_css_api_provider'] ) ? 
			$this->settings['critical_css_api_provider'] : 'criticalcss';
		
		if ( 'criticalcss' === $api_provider ) {
			// Use CriticalCSS.com API
			$response = wp_remote_post( 'https://criticalcss.com/api/premium', array(
				'body' => wp_json_encode( array(
					'url' => $url,
					'apiKey' => $api_key,
					'width' => 1300,
					'height' => 900,
					'timeout' => 30000
				) ),
				'headers' => array(
					'Content-Type' => 'application/json'
				),
				'timeout' => 60
			) );
			
			if ( is_wp_error( $response ) ) {
				error_log( 'WPSB Critical CSS API Error: ' . $response->get_error_message() );
				return false;
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['css'] ) ) {
				return $body['css'];
			}
		}
		
		return false;
	}

	/**
	 * Generate critical CSS locally (fallback)
	 *
	 * @param string $url URL to generate critical CSS for.
	 * @param string $template Template type.
	 * @return string|false Critical CSS or false on failure
	 */
	private function generate_local_critical_css( $url, $template = 'home' ) {
		// Determine viewport based on settings
		$viewport = 'desktop';
		if ( ! empty( $this->settings['critical_css_mobile_separate'] ) && wp_is_mobile() ) {
			$viewport = 'mobile';
		}
		
		// Use current user agent strings
		$user_agent = $viewport === 'mobile' 
			? 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
			: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
		
		$response = wp_remote_get( $url, array( 
			'timeout' => 30,
			'user-agent' => $user_agent
		) );
		
		if ( is_wp_error( $response ) ) {
			error_log( 'WPSB Critical CSS Generation Error: ' . $response->get_error_message() );
			return false;
		}
		
		$html = wp_remote_retrieve_body( $response );
		
		// Get all CSS content
		$css = $this->extract_css_from_page( $html );
		
		// Extract above-the-fold CSS
		$critical_css = $this->extract_above_fold_css( $html, $css, $viewport );
		
		// Save to template
		if ( $critical_css ) {
			$this->save_critical_css( $template, $critical_css, $viewport );
		}
		
		return $critical_css;
	}

	/**
	 * Extract CSS from page
	 *
	 * @param string $html HTML content.
	 * @return string Combined CSS
	 */
	private function extract_css_from_page( $html ) {
		$css = '';
		
		// Extract inline styles
		preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $inline_styles );
		if ( ! empty( $inline_styles[1] ) ) {
			$css .= implode( "\n", $inline_styles[1] );
		}
		
		return $css;
	}

	/**
	 * Extract above-the-fold CSS
	 *
	 * @param string $html HTML content.
	 * @param string $css CSS content.
	 * @param string $viewport Viewport size.
	 * @return string Critical CSS
	 */
	private function extract_above_fold_css( $html, $css, $viewport ) {
		// Parse HTML into DOM with error handling
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( $html );
		libxml_clear_errors();
		
		// Get viewport dimensions
		$viewport_height = ( $viewport === 'mobile' ) ? 667 : 1080;
		
		// Calculate which elements are above the fold
		$above_fold_selectors = $this->parse_html_for_selectors( $html, $viewport );
		
		// Filter CSS rules by selectors
		$critical_css = $this->filter_css_by_selectors( $css, $above_fold_selectors );
		
		// Add critical rules (fonts, reset, etc.)
		$critical_css = $this->add_critical_rules( $critical_css );
		
		return $this->minify_css( $critical_css );
	}

	/**
	 * Parse HTML for selectors
	 *
	 * @param string $html HTML content.
	 * @param string $viewport Viewport size.
	 * @return array Array of selectors
	 */
	private function parse_html_for_selectors( $html, $viewport ) {
		$selectors = array();
		
		// Get all elements using DOM with error handling
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( $html );
		libxml_clear_errors();
		
		$xpath = new DOMXPath( $dom );
		
		// Get common above-the-fold elements
		$above_fold_queries = array(
			'//header',
			'//nav',
			'//h1',
			'//h2',
			'//*[contains(@class, "hero")]',
			'//*[contains(@class, "header")]',
			'//*[contains(@class, "banner")]',
			'//*[contains(@id, "header")]',
			'//body',
			'//html'
		);
		
		foreach ( $above_fold_queries as $query ) {
			$elements = $xpath->query( $query );
			foreach ( $elements as $element ) {
				$selector = $this->get_css_selector( $element );
				if ( $selector ) {
					$selectors[] = $selector;
				}
			}
		}
		
		return array_unique( $selectors );
	}

	/**
	 * Get CSS selector for element
	 *
	 * @param DOMElement $element DOM element.
	 * @return string|false CSS selector or false
	 */
	private function get_css_selector( $element ) {
		if ( ! $element || ! $element->tagName ) {
			return false;
		}
		
		$selector = $element->tagName;
		
		// Add ID if present
		if ( $element->hasAttribute( 'id' ) ) {
			$id = $element->getAttribute( 'id' );
			if ( ! empty( $id ) ) {
				$selector = '#' . $id;
			}
		}
		
		// Add classes if present
		if ( $element->hasAttribute( 'class' ) ) {
			$classes = $element->getAttribute( 'class' );
			if ( ! empty( $classes ) ) {
				$class_array = explode( ' ', $classes );
				foreach ( $class_array as $class ) {
					if ( ! empty( $class ) ) {
						$selector .= '.' . $class;
					}
				}
			}
		}
		
		return $selector;
	}

	/**
	 * Filter CSS by selectors
	 *
	 * Note: This is a simplified CSS parser that may not handle all edge cases.
	 * Known limitations:
	 * - Nested braces (media queries with complex rules)
	 * - Advanced selectors with special characters
	 * - Multi-line CSS rules with complex formatting
	 * 
	 * For production use with complex stylesheets, consider using a dedicated
	 * CSS parser library or external critical CSS generation service.
	 *
	 * @param string $css CSS content.
	 * @param array  $selectors Array of selectors.
	 * @return string Filtered CSS
	 */
	private function filter_css_by_selectors( $css, $selectors ) {
		if ( empty( $selectors ) ) {
			return $css;
		}
		
		// Simple filtering - in production, use a proper CSS parser
		$filtered = '';
		
		foreach ( $selectors as $selector ) {
			// Match CSS rules containing the selector
			$pattern = '/' . preg_quote( $selector, '/' ) . '\s*\{[^}]+\}/';
			preg_match_all( $pattern, $css, $matches );
			
			if ( ! empty( $matches[0] ) ) {
				$filtered .= implode( "\n", $matches[0] ) . "\n";
			}
		}
		
		return $filtered;
	}

	/**
	 * Add critical CSS rules
	 *
	 * @param string $css CSS content.
	 * @return string CSS with critical rules
	 */
	private function add_critical_rules( $css ) {
		// Add essential resets and font rules
		$critical_rules = "html,body{margin:0;padding:0;}" . 
						  "body{font-family:system-ui,-apple-system,sans-serif;}" .
						  "*{box-sizing:border-box;}";
		
		return $critical_rules . $css;
	}

	/**
	 * Save critical CSS
	 *
	 * @param string $template Template type.
	 * @param string $css CSS content.
	 * @param string $viewport Viewport type (desktop or mobile).
	 * @return bool Success status
	 */
	public function save_critical_css( $template, $css, $viewport = 'desktop' ) {
		// Save to database
		$critical_css_data = get_option( 'velocitywp_critical_css', array() );
		
		$template_key = $template . '_' . $viewport;
		$critical_css_data[ $template_key ] = array(
			'css' => $css,
			'generated' => current_time( 'mysql' ),
			'size' => strlen( $css ),
			'template' => $template,
			'viewport' => $viewport
		);
		
		update_option( 'velocitywp_critical_css', $critical_css_data );
		
		// Also save to file for faster access
		$upload_dir = wp_upload_dir();
		$css_dir = $upload_dir['basedir'] . '/velocitywp-critical-css/';
		
		if ( ! file_exists( $css_dir ) ) {
			wp_mkdir_p( $css_dir );
		}
		
		$file_path = $css_dir . $template_key . '.css';
		$result = file_put_contents( $file_path, $css );
		
		if ( false === $result ) {
			error_log( 'WPSB Critical CSS: Failed to write CSS file: ' . $file_path );
		}
		
		return true;
	}

	/**
	 * Delete critical CSS for a template
	 *
	 * @param string $template Template type.
	 * @param string $viewport Viewport type.
	 * @return bool Success status
	 */
	public function delete_critical_css( $template, $viewport = 'desktop' ) {
		$critical_css_data = get_option( 'velocitywp_critical_css', array() );
		$template_key = $template . '_' . $viewport;
		
		if ( isset( $critical_css_data[ $template_key ] ) ) {
			unset( $critical_css_data[ $template_key ] );
			update_option( 'velocitywp_critical_css', $critical_css_data );
			
			// Delete file
			$upload_dir = wp_upload_dir();
			$css_file = $upload_dir['basedir'] . '/velocitywp-critical-css/' . $template_key . '.css';
			
			// Verify file is in expected directory before deletion
			$expected_dir = $upload_dir['basedir'] . '/velocitywp-critical-css/';
			if ( file_exists( $css_file ) && strpos( realpath( $css_file ), realpath( $expected_dir ) ) === 0 ) {
				if ( ! unlink( $css_file ) ) {
					error_log( 'WPSB Critical CSS: Failed to delete CSS file: ' . $css_file );
				}
			}
			
			return true;
		}
		
		return false;
	}

	/**
	 * Clear critical CSS cache (legacy method kept for compatibility)
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_critical_css_cache( $post_id ) {
		// Clear old transient-based cache
		$page_type = is_front_page() ? 'front_page' : 'default';
		$cache_key = 'velocitywp_critical_css_' . $page_type . '_' . $post_id;
		
		delete_transient( $cache_key );
	}

	/**
	 * Clear all critical CSS
	 */
	public function clear_all_critical_css() {
		global $wpdb;
		$prefix = $wpdb->esc_like( '_transient_velocitywp_critical_css_' );
		$timeout_prefix = $wpdb->esc_like( '_transient_timeout_velocitywp_critical_css_' );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $timeout_prefix . '%' ) );
	}

	/**
	 * Add critical CSS meta box
	 */
	public function add_critical_css_meta_box() {
		$post_types = array( 'post', 'page' );
		
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'velocitywp_critical_css',
				__( 'Critical CSS', 'velocitywp' ),
				array( $this, 'render_critical_css_meta_box' ),
				$post_type,
				'normal',
				'low'
			);
		}
	}

	/**
	 * Render critical CSS meta box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_critical_css_meta_box( $post ) {
		wp_nonce_field( 'velocitywp_critical_css_meta', 'velocitywp_critical_css_nonce' );
		
		$critical_css = get_post_meta( $post->ID, '_velocitywp_critical_css', true );
		?>
		<p>
			<label for="velocitywp_critical_css_input"><?php esc_html_e( 'Enter custom critical CSS for this page:', 'velocitywp' ); ?></label>
		</p>
		<textarea id="velocitywp_critical_css_input" name="velocitywp_critical_css" rows="10" class="large-text code"><?php echo esc_textarea( $critical_css ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Leave empty to use automatically generated critical CSS.', 'velocitywp' ); ?>
		</p>
		<p>
			<button type="button" class="button" id="velocitywp-generate-page-critical-css" data-url="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
				<?php esc_html_e( 'Generate Critical CSS', 'velocitywp' ); ?>
			</button>
		</p>
		<script>
		jQuery(document).ready(function($) {
			$('#velocitywp-generate-page-critical-css').on('click', function() {
				var $btn = $(this);
				var url = $btn.data('url');
				
				$btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'velocitywp' ); ?>');
				
				$.post(ajaxurl, {
					action: 'velocitywp_generate_critical_css',
					nonce: '<?php echo wp_create_nonce( 'velocitywp_admin_nonce' ); ?>',
					url: url
				}, function(response) {
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate Critical CSS', 'velocitywp' ); ?>');
					if (response.success) {
						$('#velocitywp_critical_css_input').val(response.data.css);
						alert('<?php esc_html_e( 'Critical CSS generated successfully!', 'velocitywp' ); ?>');
					} else {
						alert('<?php esc_html_e( 'Failed to generate critical CSS.', 'velocitywp' ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Save critical CSS meta
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_critical_css_meta( $post_id ) {
		// Check nonce
		if ( ! isset( $_POST['velocitywp_critical_css_nonce'] ) || ! wp_verify_nonce( $_POST['velocitywp_critical_css_nonce'], 'velocitywp_critical_css_meta' ) ) {
			return;
		}
		
		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		// Save critical CSS
		if ( isset( $_POST['velocitywp_critical_css'] ) ) {
			// Sanitize CSS - preserve valid CSS syntax while removing any potential scripts
			$critical_css = wp_strip_all_tags( $_POST['velocitywp_critical_css'] );
			update_post_meta( $post_id, '_velocitywp_critical_css', $critical_css );
		}
	}

	/**
	 * Generate critical CSS via AJAX
	 */
	public function ajax_generate_critical_css() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : home_url();
		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : 'home';
		
		$critical_css = $this->generate_critical_css( $url, $template );
		
		if ( $critical_css ) {
			wp_send_json_success( array(
				'message' => __( 'Critical CSS generated successfully', 'velocitywp' ),
				'css' => $critical_css,
				'size' => strlen( $critical_css ),
				'template' => $template
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to generate critical CSS', 'velocitywp' ) ) );
		}
	}

	/**
	 * Clear critical CSS cache via AJAX
	 */
	public function ajax_clear_critical_css() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		$this->clear_all_critical_css();
		
		wp_send_json_success( array( 'message' => __( 'Critical CSS cache cleared', 'velocitywp' ) ) );
	}

	/**
	 * Regenerate all critical CSS via AJAX
	 */
	public function ajax_regenerate_all_critical_css() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		// Regenerate all templates
		$templates = $this->get_all_templates();
		$generated = 0;
		$errors = array();
		
		foreach ( $templates as $template ) {
			$url = $this->get_template_url( $template );
			
			if ( $url ) {
				$critical_css = $this->generate_critical_css( $url, $template );
				
				if ( $critical_css ) {
					$generated++;
				} else {
					$errors[] = $template;
				}
			}
		}
		
		wp_send_json_success( array(
			'message' => sprintf( __( 'Generated critical CSS for %d templates', 'velocitywp' ), $generated ),
			'generated' => $generated,
			'total' => count( $templates ),
			'errors' => $errors
		) );
	}

	/**
	 * Regenerate all critical CSS
	 *
	 * @return int Number of templates generated
	 */
	public function regenerate_all_critical_css() {
		$templates = $this->get_all_templates();
		$generated = 0;
		
		foreach ( $templates as $template ) {
			$url = $this->get_template_url( $template );
			
			if ( $url ) {
				$critical_css = $this->generate_critical_css( $url, $template );
				if ( $critical_css ) {
					$generated++;
				}
			}
		}
		
		return $generated;
	}

	/**
	 * Queue critical CSS generation for templates
	 *
	 * @param array $templates Array of template names to generate.
	 */
	public function queue_critical_css_generation( $templates = array() ) {
		if ( empty( $templates ) ) {
			$templates = $this->get_all_templates();
		}
		
		foreach ( $templates as $template ) {
			wp_schedule_single_event( time() + 60, 'velocitywp_generate_critical_css', array( $template ) );
		}
	}

	/**
	 * Process critical CSS generation (cron handler)
	 *
	 * @param string $template Template type.
	 */
	public function process_critical_css_generation( $template ) {
		$url = $this->get_template_url( $template );
		
		if ( empty( $url ) ) {
			return;
		}
		
		$this->generate_critical_css( $url, $template );
	}

	/**
	 * Save manual CSS via AJAX
	 */
	public function ajax_save_manual_css() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
		$css = isset( $_POST['css'] ) ? wp_strip_all_tags( $_POST['css'] ) : '';
		$viewport = isset( $_POST['viewport'] ) ? sanitize_text_field( $_POST['viewport'] ) : 'desktop';
		
		if ( empty( $template ) || empty( $css ) ) {
			wp_send_json_error( array( 'message' => __( 'Template and CSS are required', 'velocitywp' ) ) );
		}
		
		$this->save_critical_css( $template, $css, $viewport );
		
		wp_send_json_success( array(
			'message' => __( 'Critical CSS saved successfully', 'velocitywp' ),
			'size' => strlen( $css )
		) );
	}

	/**
	 * Delete template CSS via AJAX
	 */
	public function ajax_delete_template_css() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'velocitywp' ) ) );
		}
		
		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
		$viewport = isset( $_POST['viewport'] ) ? sanitize_text_field( $_POST['viewport'] ) : 'desktop';
		
		if ( empty( $template ) ) {
			wp_send_json_error( array( 'message' => __( 'Template is required', 'velocitywp' ) ) );
		}
		
		$this->delete_critical_css( $template, $viewport );
		
		wp_send_json_success( array( 'message' => __( 'Critical CSS deleted successfully', 'velocitywp' ) ) );
	}

	/**
	 * Minify CSS
	 *
	 * @param string $css CSS to minify.
	 * @return string Minified CSS
	 */
	private function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s?([\{\};:,])\s?/', '$1', $css );
		
		return trim( $css );
	}
}
