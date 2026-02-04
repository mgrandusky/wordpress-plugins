<?php
/**
 * Resource Hints Class
 *
 * Preload, preconnect, prefetch resource hints
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Resource_Hints class
 */
class WPSB_Resource_Hints {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_wpsb_detect_resources', array( $this, 'ajax_detect_resources' ) );
		add_action( 'wp_ajax_wpsb_test_hints', array( $this, 'ajax_test_hints' ) );
	}

	/**
	 * Initialize resource hints
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['resource_hints_enabled'] ) || is_admin() ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'inject_resource_hints' ), 1 );
		add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );
	}
	
	/**
	 * Check if resource hints are enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$options = get_option( 'wpsb_options', array() );
		return ! empty( $options['resource_hints_enabled'] );
	}

	/**
	 * Inject resource hints in head
	 */
	public function inject_resource_hints() {
		$options = get_option( 'wpsb_options', array() );

		// DNS Prefetch
		if ( ! empty( $options['dns_prefetch_enabled'] ) ) {
			$domains = $this->get_dns_prefetch_domains();
			if ( ! empty( $domains ) ) {
				$this->output_dns_prefetch( $domains );
			}
		}

		// Preconnect (must come before preload for optimal performance)
		if ( ! empty( $options['preconnect_enabled'] ) ) {
			$origins = $this->get_preconnect_origins();
			if ( ! empty( $origins ) ) {
				$this->output_preconnect( $origins );
			}
		}

		// Preload
		if ( ! empty( $options['preload_enabled'] ) ) {
			$resources = $this->get_preload_resources();
			if ( ! empty( $resources ) ) {
				$this->output_preload( $resources );
			}
		}

		// Prefetch
		if ( ! empty( $options['prefetch_enabled'] ) ) {
			$urls = $this->get_prefetch_urls();
			if ( ! empty( $urls ) ) {
				$this->output_prefetch( $urls );
			}
		}
	}

	/**
	 * Detect external domains from enqueued scripts and styles
	 *
	 * @return array External domains.
	 */
	public function detect_external_domains() {
		global $wp_scripts, $wp_styles;
		$domains = array();
		$site_url = parse_url( get_site_url(), PHP_URL_HOST );

		// Check enqueued scripts
		if ( ! empty( $wp_scripts->queue ) ) {
			foreach ( $wp_scripts->queue as $handle ) {
				if ( isset( $wp_scripts->registered[ $handle ] ) ) {
					$src = $wp_scripts->registered[ $handle ]->src;
					$domain = $this->extract_domain( $src );
					if ( $domain && $this->is_external( $domain, $site_url ) ) {
						$domains[] = $domain;
					}
				}
			}
		}

		// Check enqueued styles
		if ( ! empty( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( isset( $wp_styles->registered[ $handle ] ) ) {
					$src = $wp_styles->registered[ $handle ]->src;
					$domain = $this->extract_domain( $src );
					if ( $domain && $this->is_external( $domain, $site_url ) ) {
						$domains[] = $domain;
					}
				}
			}
		}

		return array_unique( $domains );
	}

	/**
	 * Extract domain from URL
	 *
	 * @param string $url URL to extract domain from.
	 * @return string|false Domain or false.
	 */
	public function extract_domain( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		// Handle protocol-relative URLs
		if ( strpos( $url, '//' ) === 0 ) {
			$url = 'https:' . $url;
		}

		$parsed = parse_url( $url );
		return isset( $parsed['host'] ) ? $parsed['host'] : false;
	}

	/**
	 * Check if domain is external
	 *
	 * @param string $domain Domain to check.
	 * @param string $site_domain Current site domain.
	 * @return bool
	 */
	public function is_external( $domain, $site_domain = '' ) {
		if ( empty( $site_domain ) ) {
			$site_domain = parse_url( get_site_url(), PHP_URL_HOST );
		}

		return $domain !== $site_domain && $domain !== 'localhost';
	}

	/**
	 * Detect font files from enqueued styles
	 *
	 * @return array Font URLs.
	 */
	public function detect_font_files() {
		global $wp_styles;
		$fonts = array();

		if ( ! empty( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( isset( $wp_styles->registered[ $handle ] ) ) {
					$src = $wp_styles->registered[ $handle ]->src;
					
					// Check if it's a Google Fonts URL
					if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
						$fonts[] = array(
							'url' => 'https://fonts.googleapis.com',
							'type' => 'google-fonts',
						);
						$fonts[] = array(
							'url' => 'https://fonts.gstatic.com',
							'type' => 'google-fonts',
						);
					}
				}
			}
		}

		return array_unique( $fonts, SORT_REGULAR );
	}

	/**
	 * Detect critical resources to preload
	 *
	 * @return array Critical resources.
	 */
	public function detect_critical_resources() {
		$resources = array();

		// Get theme stylesheet
		$theme_url = get_stylesheet_uri();
		if ( $theme_url ) {
			$resources[] = array(
				'url' => $theme_url,
				'as'  => 'style',
			);
		}

		return $resources;
	}

	/**
	 * Get DNS prefetch domains
	 *
	 * @return array Domains to prefetch.
	 */
	public function get_dns_prefetch_domains() {
		$options = get_option( 'wpsb_options', array() );
		$domains = array();

		// Auto-detect external domains
		if ( ! empty( $options['dns_prefetch_auto'] ) ) {
			$detected = $this->detect_external_domains();
			$domains = array_merge( $domains, $detected );
		}

		// Manual domains
		if ( ! empty( $options['dns_prefetch_domains'] ) ) {
			$manual = array_map( 'trim', explode( "\n", $options['dns_prefetch_domains'] ) );
			$manual = array_filter( $manual );
			$domains = array_merge( $domains, $manual );
		}

		return array_unique( $domains );
	}

	/**
	 * Output DNS prefetch tags
	 *
	 * @param array $domains Domains to prefetch.
	 */
	public function output_dns_prefetch( $domains ) {
		foreach ( $domains as $domain ) {
			// Ensure domain has protocol prefix
			if ( strpos( $domain, '//' ) !== 0 && strpos( $domain, 'http' ) !== 0 ) {
				$domain = '//' . $domain;
			}
			echo '<link rel="dns-prefetch" href="' . esc_attr( $domain ) . '">' . "\n";
		}
	}

	/**
	 * Get preconnect origins
	 *
	 * @return array Origins to preconnect.
	 */
	public function get_preconnect_origins() {
		$options = get_option( 'wpsb_options', array() );
		$origins = array();

		if ( ! empty( $options['preconnect_origins'] ) && is_array( $options['preconnect_origins'] ) ) {
			foreach ( $options['preconnect_origins'] as $origin ) {
				if ( ! empty( $origin['url'] ) ) {
					$origins[] = array(
						'url' => $origin['url'],
						'crossorigin' => ! empty( $origin['crossorigin'] ),
					);
				}
			}
		}

		// Limit to 6 preconnects (browser recommendation)
		$origins = array_slice( $origins, 0, 6 );

		return $origins;
	}

	/**
	 * Output preconnect hints
	 *
	 * @param array $origins Origins to preconnect.
	 */
	public function output_preconnect( $origins ) {
		foreach ( $origins as $origin ) {
			$crossorigin = ! empty( $origin['crossorigin'] ) ? ' crossorigin' : '';
			echo '<link rel="preconnect" href="' . esc_url( $origin['url'] ) . '"' . $crossorigin . '>' . "\n";
		}
	}

	/**
	 * Get preload resources
	 *
	 * @return array Resources to preload.
	 */
	public function get_preload_resources() {
		$options = get_option( 'wpsb_options', array() );
		$resources = array();

		if ( ! empty( $options['preload_resources'] ) && is_array( $options['preload_resources'] ) ) {
			foreach ( $options['preload_resources'] as $resource ) {
				if ( ! empty( $resource['url'] ) && ! empty( $resource['as'] ) ) {
					$res = array(
						'url' => $resource['url'],
						'as' => $resource['as'],
					);
					
					if ( ! empty( $resource['type'] ) ) {
						$res['type'] = $resource['type'];
					}
					
					if ( ! empty( $resource['crossorigin'] ) ) {
						$res['crossorigin'] = true;
					}
					
					if ( ! empty( $resource['fetchpriority'] ) ) {
						$res['fetchpriority'] = $resource['fetchpriority'];
					}
					
					$resources[] = $res;
				}
			}
		}

		return $resources;
	}

	/**
	 * Output preload hints
	 *
	 * @param array $resources Resources to preload.
	 */
	public function output_preload( $resources ) {
		foreach ( $resources as $resource ) {
			$as = $resource['as']; // font, style, script, image
			$type = isset( $resource['type'] ) ? ' type="' . esc_attr( $resource['type'] ) . '"' : '';
			$crossorigin = isset( $resource['crossorigin'] ) && $resource['crossorigin'] ? ' crossorigin' : '';
			$fetchpriority = isset( $resource['fetchpriority'] ) ? ' fetchpriority="' . esc_attr( $resource['fetchpriority'] ) . '"' : '';
			
			echo '<link rel="preload" href="' . esc_url( $resource['url'] ) . '" as="' . esc_attr( $as ) . '"' . $type . $crossorigin . $fetchpriority . '>' . "\n";
		}
	}

	/**
	 * Preload fonts
	 *
	 * @return array Font resources to preload.
	 */
	public function preload_fonts() {
		$detected = $this->detect_font_files();
		$resources = array();

		foreach ( $detected as $font ) {
			if ( $font['type'] === 'google-fonts' ) {
				$resources[] = array(
					'url' => $font['url'],
					'as' => 'font',
					'crossorigin' => true,
				);
			}
		}

		return $resources;
	}

	/**
	 * Preload critical CSS
	 *
	 * @return array CSS resources to preload.
	 */
	public function preload_critical_css() {
		$resources = array();
		
		$theme_url = get_stylesheet_uri();
		if ( $theme_url ) {
			$resources[] = array(
				'url' => $theme_url,
				'as' => 'style',
			);
		}

		return $resources;
	}

	/**
	 * Preload critical JS
	 *
	 * @return array JS resources to preload.
	 */
	public function preload_critical_js() {
		global $wp_scripts;
		$resources = array();

		// This could be extended to detect critical JS files
		return $resources;
	}

	/**
	 * Get prefetch URLs
	 *
	 * @return array URLs to prefetch.
	 */
	public function get_prefetch_urls() {
		$options = get_option( 'wpsb_options', array() );
		$urls = array();

		// Auto-prefetch next page
		if ( ! empty( $options['prefetch_next_page'] ) ) {
			$next_url = $this->detect_next_page();
			if ( $next_url ) {
				$urls[] = $next_url;
			}
		}

		// Manual URLs
		if ( ! empty( $options['prefetch_urls'] ) ) {
			$manual = array_map( 'trim', explode( "\n", $options['prefetch_urls'] ) );
			$manual = array_filter( $manual );
			$urls = array_merge( $urls, $manual );
		}

		return array_unique( $urls );
	}

	/**
	 * Output prefetch hints
	 *
	 * @param array $urls URLs to prefetch.
	 */
	public function output_prefetch( $urls ) {
		foreach ( $urls as $url ) {
			echo '<link rel="prefetch" href="' . esc_url( $url ) . '">' . "\n";
		}
	}

	/**
	 * Detect next page for pagination
	 *
	 * @return string|null Next page URL or null.
	 */
	public function detect_next_page() {
		if ( is_singular() ) {
			// Get next post in same category
			$next_post = get_next_post( true );
			if ( $next_post ) {
				return get_permalink( $next_post->ID );
			}
		}

		if ( is_archive() || is_home() ) {
			// Get next page of archive
			global $wp_query;
			if ( $wp_query->max_num_pages > 1 ) {
				$current_page = max( 1, get_query_var( 'paged' ) );
				if ( $current_page < $wp_query->max_num_pages ) {
					return get_pagenum_link( $current_page + 1 );
				}
			}
		}

		return null;
	}

	/**
	 * Add resource hints via WordPress filter
	 *
	 * @param array  $urls          URLs to hint.
	 * @param string $relation_type Type of hint.
	 * @return array Modified URLs.
	 */
	public function add_resource_hints( $urls, $relation_type ) {
		$options = get_option( 'wpsb_options', array() );

		switch ( $relation_type ) {
			case 'dns-prefetch':
				if ( ! empty( $options['dns_prefetch_enabled'] ) ) {
					$domains = $this->get_dns_prefetch_domains();
					$urls = array_merge( $urls, $domains );
				}
				break;

			case 'preconnect':
				if ( ! empty( $options['preconnect_enabled'] ) ) {
					$origins = $this->get_preconnect_origins();
					foreach ( $origins as $origin ) {
						$urls[] = $origin['url'];
					}
				}
				break;
		}

		return array_unique( $urls );
	}

	/**
	 * AJAX handler to detect resources
	 */
	public function ajax_detect_resources() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$domains = $this->detect_external_domains();
		$fonts = $this->detect_font_files();
		$critical = $this->detect_critical_resources();

		wp_send_json_success( array(
			'domains' => $domains,
			'fonts' => $fonts,
			'critical' => $critical,
		) );
	}

	/**
	 * AJAX handler to test hints
	 */
	public function ajax_test_hints() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$options = get_option( 'wpsb_options', array() );
		$hints = array();

		if ( ! empty( $options['dns_prefetch_enabled'] ) ) {
			$hints['dns_prefetch'] = $this->get_dns_prefetch_domains();
		}

		if ( ! empty( $options['preconnect_enabled'] ) ) {
			$hints['preconnect'] = $this->get_preconnect_origins();
		}

		if ( ! empty( $options['preload_enabled'] ) ) {
			$hints['preload'] = $this->get_preload_resources();
		}

		if ( ! empty( $options['prefetch_enabled'] ) ) {
			$hints['prefetch'] = $this->get_prefetch_urls();
		}

		wp_send_json_success( array( 'hints' => $hints ) );
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
}
