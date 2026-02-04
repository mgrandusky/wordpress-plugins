<?php
/**
 * Advanced Exclusions Class
 *
 * Role/cookie/user-agent based cache exclusions
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Advanced_Exclusions class
 */
class VelocityWP_Advanced_Exclusions {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'velocitywp_should_cache', array( $this, 'check_exclusions' ), 10, 1 );
	}

	/**
	 * Initialize advanced exclusions
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['advanced_exclusions'] ) ) {
			return;
		}

		// Early exclusion checks
		add_action( 'template_redirect', array( $this, 'apply_exclusions' ), 1 );
	}

	/**
	 * Apply exclusion rules
	 */
	public function apply_exclusions() {
		if ( ! $this->should_apply_optimizations() ) {
			// Disable cache and optimizations
			add_filter( 'velocitywp_cache_enabled', '__return_false' );
			add_filter( 'velocitywp_minify_enabled', '__return_false' );
			add_filter( 'velocitywp_lazy_load_enabled', '__return_false' );
		}
	}

	/**
	 * Check if optimizations should apply
	 *
	 * @return bool Whether to apply optimizations.
	 */
	private function should_apply_optimizations() {
		$options = get_option( 'velocitywp_options', array() );

		// Check user role exclusions
		if ( ! empty( $options['exclude_roles'] ) && $this->is_excluded_role() ) {
			return false;
		}

		// Check cookie exclusions
		if ( ! empty( $options['exclude_cookies'] ) && $this->has_excluded_cookie() ) {
			return false;
		}

		// Check user agent exclusions
		if ( ! empty( $options['exclude_user_agents'] ) && $this->is_excluded_user_agent() ) {
			return false;
		}

		// Check URL parameter exclusions
		if ( ! empty( $options['exclude_url_params'] ) && $this->has_excluded_param() ) {
			return false;
		}

		// Check IP exclusions
		if ( ! empty( $options['exclude_ips'] ) && $this->is_excluded_ip() ) {
			return false;
		}

		// Check referrer exclusions
		if ( ! empty( $options['exclude_referrers'] ) && $this->is_excluded_referrer() ) {
			return false;
		}

		return apply_filters( 'velocitywp_should_apply_optimizations', true );
	}

	/**
	 * Check if current user has excluded role
	 *
	 * @return bool Whether role is excluded.
	 */
	private function is_excluded_role() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$options = get_option( 'velocitywp_options', array() );
		$excluded_roles = array();

		if ( ! empty( $options['excluded_roles_list'] ) ) {
			$excluded_roles = array_map( 'trim', explode( "\n", $options['excluded_roles_list'] ) );
		}

		// Default: exclude administrators
		if ( empty( $excluded_roles ) ) {
			$excluded_roles = array( 'administrator' );
		}

		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;

		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $excluded_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if request has excluded cookie
	 *
	 * @return bool Whether cookie is excluded.
	 */
	private function has_excluded_cookie() {
		$options = get_option( 'velocitywp_options', array() );
		$excluded_cookies = array();

		if ( ! empty( $options['excluded_cookies_list'] ) ) {
			$excluded_cookies = array_map( 'trim', explode( "\n", $options['excluded_cookies_list'] ) );
		}

		// Default WordPress cookies to check
		$default_cookies = array(
			'wordpress_logged_in_',
			'wp-postpass_',
			'comment_author_',
		);

		$excluded_cookies = array_merge( $default_cookies, $excluded_cookies );

		foreach ( $_COOKIE as $cookie_name => $cookie_value ) {
			foreach ( $excluded_cookies as $pattern ) {
				if ( empty( $pattern ) ) {
					continue;
				}

				// Support wildcard matching
				if ( strpos( $pattern, '*' ) !== false ) {
					$regex = '/^' . str_replace( '*', '.*', preg_quote( $pattern, '/' ) ) . '/';
					if ( preg_match( $regex, $cookie_name ) ) {
						return true;
					}
				} elseif ( strpos( $cookie_name, $pattern ) === 0 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if user agent is excluded
	 *
	 * @return bool Whether user agent is excluded.
	 */
	private function is_excluded_user_agent() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';

		if ( empty( $user_agent ) ) {
			return false;
		}

		$options = get_option( 'velocitywp_options', array() );
		$excluded_agents = array();

		if ( ! empty( $options['excluded_user_agents_list'] ) ) {
			$excluded_agents = array_map( 'trim', explode( "\n", $options['excluded_user_agents_list'] ) );
		}

		// Default bots to exclude
		$default_agents = array(
			'googlebot',
			'bingbot',
			'slurp',
			'duckduckbot',
			'baiduspider',
			'yandexbot',
			'facebookexternalhit',
		);

		$excluded_agents = array_merge( $default_agents, $excluded_agents );

		foreach ( $excluded_agents as $agent ) {
			if ( empty( $agent ) ) {
				continue;
			}

			if ( stripos( $user_agent, $agent ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if URL has excluded parameter
	 *
	 * @return bool Whether parameter is excluded.
	 */
	private function has_excluded_param() {
		$options = get_option( 'velocitywp_options', array() );
		$excluded_params = array();

		if ( ! empty( $options['excluded_params_list'] ) ) {
			$excluded_params = array_map( 'trim', explode( "\n", $options['excluded_params_list'] ) );
		}

		// Default parameters to exclude
		$default_params = array(
			'preview',
			'page_id',
			'preview_id',
			's',
		);

		$excluded_params = array_merge( $default_params, $excluded_params );

		foreach ( $excluded_params as $param ) {
			if ( empty( $param ) ) {
				continue;
			}

			if ( isset( $_GET[ $param ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if IP is excluded
	 *
	 * @return bool Whether IP is excluded.
	 */
	private function is_excluded_ip() {
		$visitor_ip = $this->get_visitor_ip();

		if ( empty( $visitor_ip ) ) {
			return false;
		}

		$options = get_option( 'velocitywp_options', array() );
		$excluded_ips = array();

		if ( ! empty( $options['excluded_ips_list'] ) ) {
			$excluded_ips = array_map( 'trim', explode( "\n", $options['excluded_ips_list'] ) );
		}

		foreach ( $excluded_ips as $ip ) {
			if ( empty( $ip ) ) {
				continue;
			}

			// Support CIDR notation
			if ( strpos( $ip, '/' ) !== false ) {
				if ( $this->ip_in_range( $visitor_ip, $ip ) ) {
					return true;
				}
			} elseif ( $visitor_ip === $ip ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if referrer is excluded
	 *
	 * @return bool Whether referrer is excluded.
	 */
	private function is_excluded_referrer() {
		$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';

		if ( empty( $referrer ) ) {
			return false;
		}

		$options = get_option( 'velocitywp_options', array() );
		$excluded_referrers = array();

		if ( ! empty( $options['excluded_referrers_list'] ) ) {
			$excluded_referrers = array_map( 'trim', explode( "\n", $options['excluded_referrers_list'] ) );
		}

		foreach ( $excluded_referrers as $pattern ) {
			if ( empty( $pattern ) ) {
				continue;
			}

			if ( strpos( $referrer, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get visitor IP address
	 *
	 * @return string IP address.
	 */
	private function get_visitor_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( $_SERVER[ $key ] );
				
				// Handle multiple IPs (X-Forwarded-For)
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
	 * Check if IP is in CIDR range
	 *
	 * @param string $ip    IP address to check.
	 * @param string $range CIDR range.
	 * @return bool Whether IP is in range.
	 */
	private function ip_in_range( $ip, $range ) {
		list( $subnet, $mask ) = explode( '/', $range );
		
		$ip_long = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		$mask_long = -1 << ( 32 - (int) $mask );
		
		return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
	}

	/**
	 * Check exclusions filter callback
	 *
	 * @param bool $should_cache Whether to cache.
	 * @return bool Modified value.
	 */
	public function check_exclusions( $should_cache ) {
		if ( ! $should_cache ) {
			return false;
		}

		return $this->should_apply_optimizations();
	}

	/**
	 * Get exclusion statistics
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		$stats = get_option( 'velocitywp_exclusion_stats', array(
			'role_exclusions'       => 0,
			'cookie_exclusions'     => 0,
			'user_agent_exclusions' => 0,
			'param_exclusions'      => 0,
			'ip_exclusions'         => 0,
			'referrer_exclusions'   => 0,
		) );

		return $stats;
	}

	/**
	 * Track exclusion
	 *
	 * @param string $type Exclusion type.
	 */
	private function track_exclusion( $type ) {
		$stats = $this->get_statistics();
		
		$key = $type . '_exclusions';
		if ( isset( $stats[ $key ] ) ) {
			$stats[ $key ]++;
			update_option( 'velocitywp_exclusion_stats', $stats, false );
		}
	}
}
