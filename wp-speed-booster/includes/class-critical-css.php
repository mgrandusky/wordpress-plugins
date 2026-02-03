<?php
/**
 * Critical CSS Generation Class
 *
 * Automatically extracts and inlines above-the-fold CSS
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Critical_CSS class
 */
class WPSB_Critical_CSS {

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
		$this->settings = get_option( 'wpsb_options', array() );
		
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
		add_action( 'wp_ajax_wpsb_generate_critical_css', array( $this, 'ajax_generate_critical_css' ) );
		add_action( 'wp_ajax_wpsb_clear_critical_css', array( $this, 'ajax_clear_critical_css' ) );
		add_action( 'wp_ajax_wpsb_regenerate_all_critical_css', array( $this, 'ajax_regenerate_all_critical_css' ) );
	}

	/**
	 * Check if Critical CSS is enabled
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return ! empty( $this->settings['critical_css_enabled'] );
	}

	/**
	 * Inject critical CSS in head
	 */
	public function inject_critical_css() {
		$critical_css = $this->get_critical_css();
		
		if ( ! empty( $critical_css ) ) {
			echo '<style id="wpsb-critical-css">' . $critical_css . '</style>' . "\n";
			
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
		
		$page_type = $this->get_page_type();
		
		// Check for per-page manual critical CSS in post meta
		if ( $post_id ) {
			$manual_css = get_post_meta( $post_id, '_wpsb_critical_css', true );
			if ( ! empty( $manual_css ) ) {
				return $manual_css;
			}
		}
		
		// Check for global manual critical CSS
		if ( ! empty( $this->settings['critical_css_manual'] ) ) {
			return $this->settings['critical_css_manual'];
		}
		
		// Check cache for auto-generated critical CSS
		$cache_key = 'wpsb_critical_css_' . $page_type . '_' . $post_id;
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Auto-generate if enabled
		if ( ! empty( $this->settings['critical_css_mode'] ) && 'auto' === $this->settings['critical_css_mode'] ) {
			$url = $post_id ? get_permalink( $post_id ) : home_url();
			$critical_css = $this->generate_critical_css( $url );
			
			if ( $critical_css ) {
				$this->save_critical_css( $post_id, $critical_css );
				return $critical_css;
			}
		}
		
		return '';
	}

	/**
	 * Get page type for critical CSS caching
	 *
	 * @return string Page type
	 */
	private function get_page_type() {
		if ( is_front_page() ) {
			return 'front_page';
		} elseif ( is_home() ) {
			return 'blog';
		} elseif ( is_single() ) {
			return 'single_' . get_post_type();
		} elseif ( is_page() ) {
			return 'page';
		} elseif ( is_category() ) {
			return 'category';
		} elseif ( is_archive() ) {
			return 'archive';
		}
		return 'default';
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
		if ( in_array( $handle, $critical_handles, true ) ) {
			return $tag;
		}
		
		// Defer CSS using preload with media swap technique
		$tag = str_replace( "media='$media'", "media='print' onload=\"this.media='$media'\"", $tag );
		$tag = str_replace( 'media="' . $media . '"', 'media="print" onload="this.media=\'' . $media . '\'"', $tag );
		
		// Add noscript fallback
		$noscript = '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '" media="' . esc_attr( $media ) . '"></noscript>';
		$tag .= "\n" . $noscript;
		
		return $tag;
	}

	/**
	 * Generate critical CSS for a URL
	 *
	 * @param string $url URL to generate critical CSS for.
	 * @param string $viewport Viewport size (desktop or mobile).
	 * @return string|false Critical CSS or false on failure
	 */
	public function generate_critical_css( $url, $viewport = 'desktop' ) {
		$response = wp_remote_get( $url, array( 
			'timeout' => 30,
			'user-agent' => $viewport === 'mobile' ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)' : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
		) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$html = wp_remote_retrieve_body( $response );
		
		// Get all CSS content
		$css = $this->extract_css_from_page( $html );
		
		// Extract above-the-fold CSS
		$critical_css = $this->extract_above_fold_css( $html, $css, $viewport );
		
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
		// Parse HTML into DOM
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		
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
		
		// Get all elements using DOM
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		
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
	 * @param int    $post_id Post ID.
	 * @param string $css CSS content.
	 * @return bool Success status
	 */
	public function save_critical_css( $post_id, $css ) {
		$page_type = $this->get_page_type();
		$cache_key = 'wpsb_critical_css_' . $page_type . '_' . $post_id;
		
		return set_transient( $cache_key, $css, WEEK_IN_SECONDS );
	}

	/**
	 * Clear critical CSS cache
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_critical_css_cache( $post_id ) {
		$page_type = $this->get_page_type();
		$cache_key = 'wpsb_critical_css_' . $page_type . '_' . $post_id;
		
		delete_transient( $cache_key );
	}

	/**
	 * Clear all critical CSS
	 */
	public function clear_all_critical_css() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpsb_critical_css_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wpsb_critical_css_%'" );
	}

	/**
	 * Add critical CSS meta box
	 */
	public function add_critical_css_meta_box() {
		$post_types = array( 'post', 'page' );
		
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wpsb_critical_css',
				__( 'Critical CSS', 'wp-speed-booster' ),
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
		wp_nonce_field( 'wpsb_critical_css_meta', 'wpsb_critical_css_nonce' );
		
		$critical_css = get_post_meta( $post->ID, '_wpsb_critical_css', true );
		?>
		<p>
			<label for="wpsb_critical_css_input"><?php esc_html_e( 'Enter custom critical CSS for this page:', 'wp-speed-booster' ); ?></label>
		</p>
		<textarea id="wpsb_critical_css_input" name="wpsb_critical_css" rows="10" class="large-text code"><?php echo esc_textarea( $critical_css ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Leave empty to use automatically generated critical CSS.', 'wp-speed-booster' ); ?>
		</p>
		<p>
			<button type="button" class="button" id="wpsb-generate-page-critical-css" data-url="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
				<?php esc_html_e( 'Generate Critical CSS', 'wp-speed-booster' ); ?>
			</button>
		</p>
		<script>
		jQuery(document).ready(function($) {
			$('#wpsb-generate-page-critical-css').on('click', function() {
				var $btn = $(this);
				var url = $btn.data('url');
				
				$btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'wp-speed-booster' ); ?>');
				
				$.post(ajaxurl, {
					action: 'wpsb_generate_critical_css',
					nonce: '<?php echo wp_create_nonce( 'wpsb_admin_nonce' ); ?>',
					url: url
				}, function(response) {
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate Critical CSS', 'wp-speed-booster' ); ?>');
					if (response.success) {
						$('#wpsb_critical_css_input').val(response.data.css);
						alert('<?php esc_html_e( 'Critical CSS generated successfully!', 'wp-speed-booster' ); ?>');
					} else {
						alert('<?php esc_html_e( 'Failed to generate critical CSS.', 'wp-speed-booster' ); ?>');
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
		if ( ! isset( $_POST['wpsb_critical_css_nonce'] ) || ! wp_verify_nonce( $_POST['wpsb_critical_css_nonce'], 'wpsb_critical_css_meta' ) ) {
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
		if ( isset( $_POST['wpsb_critical_css'] ) ) {
			$critical_css = wp_strip_all_tags( $_POST['wpsb_critical_css'] );
			update_post_meta( $post_id, '_wpsb_critical_css', $critical_css );
		}
	}

	/**
	 * Generate critical CSS via AJAX
	 */
	public function ajax_generate_critical_css() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : home_url();
		$viewport = isset( $_POST['viewport'] ) ? sanitize_text_field( $_POST['viewport'] ) : 'desktop';
		
		$critical_css = $this->generate_critical_css( $url, $viewport );
		
		if ( $critical_css ) {
			$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
			
			if ( $page_id ) {
				$this->save_critical_css( $page_id, $critical_css );
			}
			
			wp_send_json_success( array(
				'message' => __( 'Critical CSS generated successfully', 'wp-speed-booster' ),
				'css' => $critical_css,
				'size' => strlen( $critical_css )
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to generate critical CSS', 'wp-speed-booster' ) ) );
		}
	}

	/**
	 * Clear critical CSS cache via AJAX
	 */
	public function ajax_clear_critical_css() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		$this->clear_all_critical_css();
		
		wp_send_json_success( array( 'message' => __( 'Critical CSS cache cleared', 'wp-speed-booster' ) ) );
	}

	/**
	 * Regenerate all critical CSS via AJAX
	 */
	public function ajax_regenerate_all_critical_css() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		// Get all published posts and pages
		$args = array(
			'post_type' => array( 'post', 'page' ),
			'post_status' => 'publish',
			'posts_per_page' => 10,
			'paged' => isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1
		);
		
		$query = new WP_Query( $args );
		$generated = 0;
		
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$url = get_permalink();
				$critical_css = $this->generate_critical_css( $url );
				
				if ( $critical_css ) {
					$this->save_critical_css( get_the_ID(), $critical_css );
					$generated++;
				}
			}
			wp_reset_postdata();
		}
		
		wp_send_json_success( array(
			'message' => sprintf( __( 'Generated critical CSS for %d pages', 'wp-speed-booster' ), $generated ),
			'generated' => $generated,
			'total' => $query->found_posts,
			'has_more' => $query->max_num_pages > $args['paged']
		) );
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
