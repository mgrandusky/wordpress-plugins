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
	}

	/**
	 * Initialize resource hints
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['resource_hints'] ) || is_admin() ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'output_resource_hints' ), 1 );
		add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );
	}

	/**
	 * Output resource hints in head
	 */
	public function output_resource_hints() {
		$options = get_option( 'wpsb_options', array() );

		// DNS Prefetch
		if ( ! empty( $options['dns_prefetch'] ) ) {
			$this->output_dns_prefetch( $options );
		}

		// Preconnect
		if ( ! empty( $options['preconnect'] ) ) {
			$this->output_preconnect( $options );
		}

		// Preload
		if ( ! empty( $options['preload_resources'] ) ) {
			$this->output_preload( $options );
		}

		// Prefetch
		if ( ! empty( $options['prefetch_resources'] ) ) {
			$this->output_prefetch( $options );
		}

		// Prerender
		if ( ! empty( $options['prerender'] ) ) {
			$this->output_prerender( $options );
		}
	}

	/**
	 * Output DNS prefetch hints
	 *
	 * @param array $options Plugin options.
	 */
	private function output_dns_prefetch( $options ) {
		$domains = $this->get_list_from_option( $options, 'dns_prefetch_urls' );

		// Add common third-party domains
		$default_domains = array(
			'//fonts.googleapis.com',
			'//fonts.gstatic.com',
			'//ajax.googleapis.com',
			'//stats.wp.com',
			'//www.google-analytics.com',
			'//www.googletagmanager.com',
		);

		$domains = array_merge( $default_domains, $domains );
		$domains = array_unique( $domains );
		$domains = apply_filters( 'wpsb_dns_prefetch_domains', $domains );

		foreach ( $domains as $domain ) {
			echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
		}
	}

	/**
	 * Output preconnect hints
	 *
	 * @param array $options Plugin options.
	 */
	private function output_preconnect( $options ) {
		$urls = $this->get_list_from_option( $options, 'preconnect_urls' );

		// Add CDN if configured
		if ( ! empty( $options['cdn_url'] ) ) {
			$urls[] = $options['cdn_url'];
		}

		$urls = array_unique( $urls );
		$urls = apply_filters( 'wpsb_preconnect_urls', $urls );

		foreach ( $urls as $url ) {
			echo '<link rel="preconnect" href="' . esc_url( $url ) . '" crossorigin>' . "\n";
		}
	}

	/**
	 * Output preload hints
	 *
	 * @param array $options Plugin options.
	 */
	private function output_preload( $options ) {
		$resources = $this->get_preload_resources( $options );

		foreach ( $resources as $resource ) {
			$attributes = array(
				'rel'  => 'preload',
				'href' => esc_url( $resource['url'] ),
				'as'   => esc_attr( $resource['as'] ),
			);

			// Add type for fonts
			if ( $resource['as'] === 'font' ) {
				$attributes['type'] = $this->get_font_type( $resource['url'] );
				$attributes['crossorigin'] = 'crossorigin';
			}

			// Add media query if specified
			if ( ! empty( $resource['media'] ) ) {
				$attributes['media'] = esc_attr( $resource['media'] );
			}

			$tag = '<link';
			foreach ( $attributes as $key => $value ) {
				$tag .= ' ' . $key . '="' . $value . '"';
			}
			$tag .= '>' . "\n";

			echo $tag;
		}
	}

	/**
	 * Get preload resources
	 *
	 * @param array $options Plugin options.
	 * @return array Resources to preload.
	 */
	private function get_preload_resources( $options ) {
		$resources = array();

		// Parse custom preload resources
		if ( ! empty( $options['preload_resources_list'] ) ) {
			$lines = explode( "\n", $options['preload_resources_list'] );
			
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( empty( $line ) ) {
					continue;
				}

				// Parse: URL|type|media (media is optional)
				$parts = array_map( 'trim', explode( '|', $line ) );
				
				if ( count( $parts ) >= 2 ) {
					$resource = array(
						'url' => $parts[0],
						'as'  => $parts[1],
					);

					if ( ! empty( $parts[2] ) ) {
						$resource['media'] = $parts[2];
					}

					$resources[] = $resource;
				}
			}
		}

		// Auto-detect critical resources
		if ( ! empty( $options['auto_preload_critical'] ) ) {
			$critical = $this->get_critical_resources();
			$resources = array_merge( $resources, $critical );
		}

		return apply_filters( 'wpsb_preload_resources', $resources );
	}

	/**
	 * Get critical resources to preload
	 *
	 * @return array Critical resources.
	 */
	private function get_critical_resources() {
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
	 * Output prefetch hints
	 *
	 * @param array $options Plugin options.
	 */
	private function output_prefetch( $options ) {
		$urls = $this->get_list_from_option( $options, 'prefetch_urls' );

		// Auto-prefetch next pages
		if ( ! empty( $options['auto_prefetch_next'] ) ) {
			$next_urls = $this->get_next_page_urls();
			$urls = array_merge( $urls, $next_urls );
		}

		$urls = array_unique( $urls );
		$urls = apply_filters( 'wpsb_prefetch_urls', $urls );

		foreach ( $urls as $url ) {
			echo '<link rel="prefetch" href="' . esc_url( $url ) . '">' . "\n";
		}
	}

	/**
	 * Output prerender hints
	 *
	 * @param array $options Plugin options.
	 */
	private function output_prerender( $options ) {
		$urls = $this->get_list_from_option( $options, 'prerender_urls' );

		// Limit to prevent excessive resource usage
		$urls = array_slice( $urls, 0, 2 );
		$urls = apply_filters( 'wpsb_prerender_urls', $urls );

		foreach ( $urls as $url ) {
			echo '<link rel="prerender" href="' . esc_url( $url ) . '">' . "\n";
		}
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
				if ( ! empty( $options['dns_prefetch'] ) ) {
					$domains = $this->get_list_from_option( $options, 'dns_prefetch_urls' );
					$urls = array_merge( $urls, $domains );
				}
				break;

			case 'preconnect':
				if ( ! empty( $options['preconnect'] ) ) {
					$preconnect = $this->get_list_from_option( $options, 'preconnect_urls' );
					$urls = array_merge( $urls, $preconnect );
				}
				break;
		}

		return array_unique( $urls );
	}

	/**
	 * Get list from option
	 *
	 * @param array  $options Plugin options.
	 * @param string $key     Option key.
	 * @return array List items.
	 */
	private function get_list_from_option( $options, $key ) {
		if ( empty( $options[ $key ] ) ) {
			return array();
		}

		$list = array_map( 'trim', explode( "\n", $options[ $key ] ) );
		return array_filter( $list );
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

	/**
	 * Get URLs of next pages to prefetch
	 *
	 * @return array Next page URLs.
	 */
	private function get_next_page_urls() {
		$urls = array();

		// Get next post
		if ( is_single() ) {
			$next_post = get_next_post();
			if ( $next_post ) {
				$urls[] = get_permalink( $next_post );
			}
		}

		// Get next page in pagination
		if ( is_paged() ) {
			$next_page = get_next_posts_page_link();
			if ( $next_page ) {
				$urls[] = $next_page;
			}
		}

		return $urls;
	}
}
