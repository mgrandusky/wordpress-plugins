<?php
/**
 * Multi-CDN Class
 *
 * Multi-CDN support and load balancing
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Multi_CDN class
 */
class VelocityWP_Multi_CDN {

	/**
	 * CDN providers
	 *
	 * @var array
	 */
	private $cdn_providers = array();

	/**
	 * Current CDN index
	 *
	 * @var int
	 */
	private $current_cdn = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize multi-CDN
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['multi_cdn'] ) ) {
			return;
		}

		// Load CDN providers
		$this->load_cdn_providers( $options );

		if ( empty( $this->cdn_providers ) ) {
			return;
		}

		// Replace URLs with CDN
		add_filter( 'wp_get_attachment_url', array( $this, 'rewrite_url' ), 10, 1 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'rewrite_srcset' ), 10, 1 );
		add_action( 'wp_head', array( $this, 'add_dns_prefetch' ), 1 );
	}

	/**
	 * Load CDN providers from settings
	 *
	 * @param array $options Plugin options.
	 */
	private function load_cdn_providers( $options ) {
		if ( empty( $options['cdn_providers'] ) ) {
			return;
		}

		$providers = explode( "\n", $options['cdn_providers'] );
		
		foreach ( $providers as $provider ) {
			$provider = trim( $provider );
			if ( ! empty( $provider ) ) {
				$this->cdn_providers[] = array(
					'url'    => $provider,
					'active' => true,
				);
			}
		}
	}

	/**
	 * Rewrite URL to use CDN
	 *
	 * @param string $url Original URL.
	 * @return string Rewritten URL.
	 */
	public function rewrite_url( $url ) {
		if ( empty( $this->cdn_providers ) ) {
			return $url;
		}

		// Skip if already a CDN URL
		foreach ( $this->cdn_providers as $provider ) {
			if ( strpos( $url, $provider['url'] ) !== false ) {
				return $url;
			}
		}

		// Check if URL should be rewritten
		if ( ! $this->should_rewrite_url( $url ) ) {
			return $url;
		}

		// Get CDN URL using load balancing
		$cdn_url = $this->get_cdn_url( $url );

		// Replace base URL
		$upload_dir = wp_upload_dir();
		$url = str_replace( $upload_dir['baseurl'], $cdn_url, $url );
		$url = str_replace( content_url(), $cdn_url, $url );

		return $url;
	}

	/**
	 * Rewrite srcset URLs
	 *
	 * @param array $sources Srcset sources.
	 * @return array Modified sources.
	 */
	public function rewrite_srcset( $sources ) {
		if ( empty( $sources ) || empty( $this->cdn_providers ) ) {
			return $sources;
		}

		foreach ( $sources as $width => $source ) {
			$sources[ $width ]['url'] = $this->rewrite_url( $source['url'] );
		}

		return $sources;
	}

	/**
	 * Get CDN URL using load balancing
	 *
	 * @param string $original_url Original URL.
	 * @return string CDN URL.
	 */
	private function get_cdn_url( $original_url ) {
		$options = get_option( 'velocitywp_options', array() );
		$method = ! empty( $options['cdn_load_balancing'] ) ? $options['cdn_load_balancing'] : 'round_robin';

		switch ( $method ) {
			case 'hash':
				$cdn = $this->get_cdn_by_hash( $original_url );
				break;

			case 'random':
				$cdn = $this->get_random_cdn();
				break;

			case 'round_robin':
			default:
				$cdn = $this->get_next_cdn();
				break;
		}

		return $cdn['url'];
	}

	/**
	 * Get next CDN using round-robin
	 *
	 * @return array CDN provider.
	 */
	private function get_next_cdn() {
		$cdn = $this->cdn_providers[ $this->current_cdn ];
		
		$this->current_cdn++;
		if ( $this->current_cdn >= count( $this->cdn_providers ) ) {
			$this->current_cdn = 0;
		}

		return $cdn;
	}

	/**
	 * Get random CDN
	 *
	 * @return array CDN provider.
	 */
	private function get_random_cdn() {
		$index = array_rand( $this->cdn_providers );
		return $this->cdn_providers[ $index ];
	}

	/**
	 * Get CDN by URL hash
	 *
	 * @param string $url URL to hash.
	 * @return array CDN provider.
	 */
	private function get_cdn_by_hash( $url ) {
		$hash = crc32( $url );
		$index = $hash % count( $this->cdn_providers );
		return $this->cdn_providers[ $index ];
	}

	/**
	 * Check if URL should be rewritten
	 *
	 * @param string $url URL to check.
	 * @return bool Whether to rewrite.
	 */
	private function should_rewrite_url( $url ) {
		$options = get_option( 'velocitywp_options', array() );

		// Check file type inclusion
		$included_types = ! empty( $options['cdn_file_types'] ) ? $options['cdn_file_types'] : 'jpg,jpeg,png,gif,webp,css,js';
		$types = array_map( 'trim', explode( ',', $included_types ) );

		$extension = pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
		
		if ( ! in_array( strtolower( $extension ), $types, true ) ) {
			return false;
		}

		// Check exclusions
		if ( ! empty( $options['cdn_exclusions'] ) ) {
			$exclusions = array_map( 'trim', explode( "\n", $options['cdn_exclusions'] ) );
			
			foreach ( $exclusions as $exclusion ) {
				if ( ! empty( $exclusion ) && strpos( $url, $exclusion ) !== false ) {
					return false;
				}
			}
		}

		return apply_filters( 'velocitywp_cdn_should_rewrite_url', true, $url );
	}

	/**
	 * Add DNS prefetch for CDNs
	 */
	public function add_dns_prefetch() {
		foreach ( $this->cdn_providers as $provider ) {
			$domain = parse_url( $provider['url'], PHP_URL_HOST );
			if ( $domain ) {
				echo '<link rel="dns-prefetch" href="//' . esc_attr( $domain ) . '">' . "\n";
			}
		}
	}

	/**
	 * Test CDN connectivity
	 *
	 * @param string $cdn_url CDN URL.
	 * @return bool|WP_Error Success or error.
	 */
	public function test_cdn( $cdn_url ) {
		$test_url = trailingslashit( $cdn_url ) . 'test.txt';

		$response = wp_remote_head( $test_url, array(
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		
		if ( $code >= 200 && $code < 400 ) {
			return true;
		}

		return new WP_Error( 'cdn_test_failed', sprintf( __( 'CDN test failed with status code: %d', 'velocitywp' ), $code ) );
	}
}
