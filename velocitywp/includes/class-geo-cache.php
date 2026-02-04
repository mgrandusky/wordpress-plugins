<?php
/**
 * Geo Cache Class
 *
 * Geolocation-based caching and content delivery
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Geo_Cache class
 */
class WPSB_Geo_Cache {

	/**
	 * Visitor location
	 *
	 * @var array
	 */
	private $location = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize geo cache
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['geo_cache'] ) ) {
			return;
		}

		// Detect visitor location
		$this->detect_location();

		// Modify cache key based on location
		add_filter( 'wpsb_cache_key', array( $this, 'add_geo_cache_key' ) );

		// Serve geo-specific content
		if ( ! empty( $options['geo_content'] ) ) {
			add_filter( 'the_content', array( $this, 'filter_geo_content' ), 999 );
		}
	}

	/**
	 * Detect visitor location
	 */
	private function detect_location() {
		// Check if already detected
		if ( isset( $_COOKIE['wpsb_geo_location'] ) ) {
			$this->location = json_decode( stripslashes( $_COOKIE['wpsb_geo_location'] ), true );
			return;
		}

		$options = get_option( 'wpsb_options', array() );
		$method = ! empty( $options['geo_detection_method'] ) ? $options['geo_detection_method'] : 'cloudflare';

		switch ( $method ) {
			case 'cloudflare':
				$this->location = $this->detect_cloudflare();
				break;

			case 'header':
				$this->location = $this->detect_from_headers();
				break;

			case 'ip_api':
				$this->location = $this->detect_ip_api();
				break;

			default:
				$this->location = array( 'country' => 'XX' );
		}

		// Store in cookie
		setcookie( 'wpsb_geo_location', wp_json_encode( $this->location ), time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Detect location from Cloudflare headers
	 *
	 * @return array Location data.
	 */
	private function detect_cloudflare() {
		$location = array();

		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$location['country'] = sanitize_text_field( $_SERVER['HTTP_CF_IPCOUNTRY'] );
		}

		if ( isset( $_SERVER['HTTP_CF_IPCITY'] ) ) {
			$location['city'] = sanitize_text_field( $_SERVER['HTTP_CF_IPCITY'] );
		}

		return $location;
	}

	/**
	 * Detect location from HTTP headers
	 *
	 * @return array Location data.
	 */
	private function detect_from_headers() {
		$location = array();

		// Check for common geolocation headers
		$headers = array(
			'HTTP_X_COUNTRY_CODE',
			'HTTP_X_GEO_COUNTRY',
			'HTTP_GEOIP_COUNTRY_CODE',
		);

		foreach ( $headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				$location['country'] = sanitize_text_field( $_SERVER[ $header ] );
				break;
			}
		}

		return $location;
	}

	/**
	 * Detect location using IP-API service
	 *
	 * @return array Location data.
	 */
	private function detect_ip_api() {
		$ip = $this->get_visitor_ip();

		if ( empty( $ip ) ) {
			return array( 'country' => 'XX' );
		}

		// Use IP-API free service
		$response = wp_remote_get( 'http://ip-api.com/json/' . $ip, array(
			'timeout' => 3,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'country' => 'XX' );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['status'] ) || $data['status'] !== 'success' ) {
			return array( 'country' => 'XX' );
		}

		return array(
			'country' => $data['countryCode'],
			'city'    => $data['city'],
			'region'  => $data['region'],
		);
	}

	/**
	 * Get visitor IP address
	 *
	 * @return string IP address.
	 */
	private function get_visitor_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( $_SERVER[ $key ] );
				
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Add geo suffix to cache key
	 *
	 * @param string $key Cache key.
	 * @return string Modified key.
	 */
	public function add_geo_cache_key( $key ) {
		if ( ! empty( $this->location['country'] ) ) {
			$key .= '_' . strtolower( $this->location['country'] );
		}

		return $key;
	}

	/**
	 * Filter content based on geolocation
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function filter_geo_content( $content ) {
		// Process geo shortcodes
		$content = $this->process_geo_shortcodes( $content );

		return $content;
	}

	/**
	 * Process geo shortcodes
	 *
	 * @param string $content Content.
	 * @return string Processed content.
	 */
	private function process_geo_shortcodes( $content ) {
		// Pattern: [geo country="US"]Content for US[/geo]
		$pattern = '/\[geo\s+country="([^"]+)"\](.*?)\[\/geo\]/s';

		$content = preg_replace_callback( $pattern, array( $this, 'replace_geo_shortcode' ), $content );

		return $content;
	}

	/**
	 * Replace geo shortcode callback
	 *
	 * @param array $matches Regex matches.
	 * @return string Replacement content.
	 */
	private function replace_geo_shortcode( $matches ) {
		$target_country = strtoupper( $matches[1] );
		$content = $matches[2];

		$visitor_country = ! empty( $this->location['country'] ) ? strtoupper( $this->location['country'] ) : '';

		// Show content only if country matches
		if ( $visitor_country === $target_country ) {
			return $content;
		}

		return '';
	}

	/**
	 * Get visitor location
	 *
	 * @return array Location data.
	 */
	public function get_location() {
		return $this->location;
	}

	/**
	 * Get location statistics
	 *
	 * @return array Statistics by country.
	 */
	public function get_statistics() {
		$stats = get_option( 'wpsb_geo_stats', array() );
		
		// Track current visit
		$country = ! empty( $this->location['country'] ) ? $this->location['country'] : 'XX';
		
		if ( ! isset( $stats[ $country ] ) ) {
			$stats[ $country ] = 0;
		}
		
		$stats[ $country ]++;
		
		update_option( 'wpsb_geo_stats', $stats, false );

		return $stats;
	}
}
