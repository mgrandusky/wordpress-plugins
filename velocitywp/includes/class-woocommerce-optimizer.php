<?php
/**
 * WooCommerce Optimizer Class
 *
 * Comprehensive WooCommerce-specific performance optimizations
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_WooCommerce_Optimizer class
 */
class VelocityWP_WooCommerce_Optimizer {

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_woo_get_stats', array( $this, 'ajax_get_stats' ) );
	}

	/**
	 * Initialize WooCommerce optimizations
	 */
	public function init() {
		// Check if WooCommerce is active
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		// Load settings
		$options = get_option( 'velocitywp_options', array() );
		$this->settings = $options;

		// Check if optimization is enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Cart fragment management
		if ( ! empty( $this->settings['woo_disable_cart_fragments'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'disable_cart_fragments' ), 999 );
		}

		if ( ! empty( $this->settings['woo_disable_cart_fragments_on'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'disable_cart_fragments_on_pages' ), 999 );
		}

		if ( ! empty( $this->settings['woo_cart_fragment_lifetime'] ) ) {
			add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'modify_cart_fragment_cache' ) );
		}

		// Script management
		if ( ! empty( $this->settings['woo_remove_scripts'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'remove_woocommerce_assets' ), 999 );
		}

		// Password strength meter
		if ( ! empty( $this->settings['woo_disable_password_strength'] ) ) {
			add_action( 'wp_print_scripts', array( $this, 'disable_password_strength_meter' ), 100 );
		}

		// WooCommerce blocks
		if ( ! empty( $this->settings['woo_disable_blocks'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'disable_woocommerce_blocks' ), 999 );
		}

		// Checkout optimization
		if ( ! empty( $this->settings['woo_optimize_checkout'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'optimize_checkout_scripts' ), 999 );
		}

		// Widget optimization
		if ( ! empty( $this->settings['woo_optimize_widgets'] ) ) {
			add_filter( 'woocommerce_products_widget_query_args', array( $this, 'optimize_product_queries' ) );
		}

		// Reviews
		if ( ! empty( $this->settings['woo_disable_reviews'] ) ) {
			add_action( 'init', array( $this, 'disable_reviews' ) );
		}

		// Admin bar cart
		if ( ! empty( $this->settings['woo_disable_admin_bar_cart'] ) ) {
			add_action( 'init', array( $this, 'disable_admin_bar_cart' ) );
		}

		// Generator tag
		if ( ! empty( $this->settings['woo_remove_generator'] ) ) {
			add_action( 'init', array( $this, 'remove_generator_tag' ) );
		}

		// Transients
		if ( ! empty( $this->settings['woo_optimize_transients'] ) ) {
			add_action( 'init', array( $this, 'optimize_transients' ) );
		}

		// Sessions
		if ( ! empty( $this->settings['woo_optimize_sessions'] ) ) {
			add_action( 'init', array( $this, 'optimize_sessions' ) );
		}

		// Geolocation
		if ( ! empty( $this->settings['woo_disable_geolocation'] ) ) {
			add_action( 'init', array( $this, 'optimize_geolocation' ) );
		}
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @return bool
	 */
	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if optimization is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['woo_optimization_enabled'] );
	}

	/**
	 * Check if WooCommerce assets should be loaded
	 *
	 * @return bool
	 */
	public function should_load_woocommerce_assets() {
		// Always load on WooCommerce pages
		if ( is_cart() || is_checkout() || is_account_page() ) {
			return true;
		}

		// Load on shop/product pages
		if ( is_shop() || is_product() || is_product_category() || is_product_tag() ) {
			return true;
		}

		// Check if cart widget is in use
		if ( is_active_widget( false, false, 'woocommerce_widget_cart', true ) ) {
			return true;
		}

		// Check user preference
		if ( ! empty( $this->settings['woo_load_everywhere'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Disable cart fragments
	 */
	public function disable_cart_fragments() {
		if ( ! $this->should_load_woocommerce_assets() ) {
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	}

	/**
	 * Disable cart fragments on specific pages
	 */
	public function disable_cart_fragments_on_pages() {
		$disabled_pages = ! empty( $this->settings['woo_disable_cart_fragments_on'] ) ?
			explode( ',', $this->settings['woo_disable_cart_fragments_on'] ) : array();

		$current_page = get_post_type();

		if ( in_array( $current_page, $disabled_pages, true ) ) {
			wp_dequeue_script( 'wc-cart-fragments' );

			// Remove inline script
			wp_add_inline_script( 'jquery', '
				jQuery(document).ready(function($) {
					if (typeof wc_cart_fragments_params !== "undefined") {
						wc_cart_fragments_params = null;
					}
				});
			' );
		}
	}

	/**
	 * Modify cart fragment cache lifetime
	 *
	 * @param array $fragment Fragment data.
	 * @return array
	 */
	public function modify_cart_fragment_cache( $fragment ) {
		$lifetime = ! empty( $this->settings['woo_cart_fragment_lifetime'] ) ?
			intval( $this->settings['woo_cart_fragment_lifetime'] ) : DAY_IN_SECONDS;

		add_filter( 'wc_session_expiring', function() use ( $lifetime ) {
			return time() + $lifetime;
		});

		add_filter( 'wc_session_expiration', function() use ( $lifetime ) {
			return time() + $lifetime;
		});

		return $fragment;
	}

	/**
	 * Remove WooCommerce assets on non-shop pages
	 */
	public function remove_woocommerce_assets() {
		if ( ! $this->should_load_woocommerce_assets() ) {
			// Remove scripts
			wp_dequeue_script( 'wc-add-to-cart' );
			wp_dequeue_script( 'wc-cart-fragments' );
			wp_dequeue_script( 'woocommerce' );
			wp_dequeue_script( 'wc-add-to-cart-variation' );

			// Remove styles
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-smallscreen' );
		}
	}

	/**
	 * Disable password strength meter
	 */
	public function disable_password_strength_meter() {
		if ( ! empty( $this->settings['woo_disable_password_strength'] ) ) {
			wp_dequeue_script( 'wc-password-strength-meter' );
			wp_dequeue_script( 'zxcvbn-async' );
		}
	}

	/**
	 * Disable WooCommerce blocks
	 */
	public function disable_woocommerce_blocks() {
		if ( ! empty( $this->settings['woo_disable_blocks'] ) ) {
			// Remove block styles
			wp_dequeue_style( 'wc-blocks-style' );
			wp_dequeue_style( 'wc-blocks-vendors-style' );

			// Remove block scripts
			wp_dequeue_script( 'wc-blocks-registry' );
			wp_dequeue_script( 'wc-blocks-data-store' );
			wp_dequeue_script( 'wc-blocks-checkout' );
		}
	}

	/**
	 * Optimize checkout scripts
	 */
	public function optimize_checkout_scripts() {
		if ( ! is_checkout() || empty( $this->settings['woo_optimize_checkout'] ) ) {
			return;
		}

		// Remove unnecessary scripts
		$scripts_to_remove = array(
			'wc-add-to-cart',
			'wc-cart-fragments',
			'jquery-blockui',
			'select2',
		);

		foreach ( $scripts_to_remove as $script ) {
			if ( ! in_array( $script, array( 'jquery', 'wc-checkout' ), true ) ) {
				wp_dequeue_script( $script );
			}
		}
	}

	/**
	 * Optimize product widget queries
	 *
	 * @param array $query_args Query arguments.
	 * @return array
	 */
	public function optimize_product_queries( $query_args ) {
		if ( ! empty( $this->settings['woo_optimize_widgets'] ) ) {
			// Limit posts per page
			$query_args['posts_per_page'] = 5;

			// Don't count total posts (faster)
			$query_args['no_found_rows'] = true;

			// Only get necessary fields
			$query_args['fields'] = 'ids';
		}

		return $query_args;
	}

	/**
	 * Disable reviews
	 */
	public function disable_reviews() {
		if ( ! empty( $this->settings['woo_disable_reviews'] ) ) {
			// Remove review tab
			add_filter( 'woocommerce_product_tabs', function( $tabs ) {
				unset( $tabs['reviews'] );
				return $tabs;
			}, 98 );

			// Disable reviews functionality
			add_filter( 'woocommerce_product_review_comment_form_args', '__return_empty_array' );
		}
	}

	/**
	 * Disable admin bar cart
	 */
	public function disable_admin_bar_cart() {
		if ( ! empty( $this->settings['woo_disable_admin_bar_cart'] ) ) {
			remove_action( 'admin_bar_menu', 'wc_admin_bar_cart_menu', 99 );
		}
	}

	/**
	 * Remove WooCommerce generator tag
	 */
	public function remove_generator_tag() {
		if ( ! empty( $this->settings['woo_remove_generator'] ) ) {
			remove_action( 'wp_head', 'wc_generator_tag' );
		}
	}

	/**
	 * Optimize transients
	 */
	public function optimize_transients() {
		if ( ! empty( $this->settings['woo_optimize_transients'] ) ) {
			// Reduce transient checks
			add_filter( 'woocommerce_product_get_related_posts_limit', function( $limit ) {
				return 4; // Default is 5
			});

			// Increase transient lifetime
			add_filter( 'wc_session_expiration', function() {
				return WEEK_IN_SECONDS; // Default is 48 hours
			});
		}
	}

	/**
	 * Optimize sessions
	 */
	public function optimize_sessions() {
		if ( ! empty( $this->settings['woo_optimize_sessions'] ) ) {
			// Don't create sessions for non-logged-in users on non-shop pages
			add_action( 'wp', function() {
				if ( ! is_user_logged_in() && ! $this->should_load_woocommerce_assets() ) {
					remove_action( 'wp_loaded', array( WC()->session, 'init' ), 10 );
				}
			}, 5 );
		}
	}

	/**
	 * Optimize geolocation
	 */
	public function optimize_geolocation() {
		if ( ! empty( $this->settings['woo_disable_geolocation'] ) ) {
			add_filter( 'woocommerce_geolocate_ip', '__return_false' );
			add_filter( 'pre_option_woocommerce_default_customer_address', function() {
				return 'base';
			});
		}
	}

	/**
	 * Get statistics
	 *
	 * @return array
	 */
	public function get_stats() {
		$stats = array(
			'cart_fragments_disabled' => ! empty( $this->settings['woo_disable_cart_fragments'] ),
			'scripts_optimized' => ! empty( $this->settings['woo_remove_scripts'] ),
			'password_meter_disabled' => ! empty( $this->settings['woo_disable_password_strength'] ),
			'blocks_disabled' => ! empty( $this->settings['woo_disable_blocks'] ),
			'estimated_savings' => 0,
		);

		// Calculate estimated savings
		if ( $stats['cart_fragments_disabled'] ) {
			$stats['estimated_savings'] += 50; // ~50KB
		}

		if ( $stats['password_meter_disabled'] ) {
			$stats['estimated_savings'] += 800; // ~800KB (zxcvbn.js)
		}

		if ( $stats['scripts_optimized'] ) {
			$stats['estimated_savings'] += 100; // ~100KB
		}

		if ( $stats['blocks_disabled'] ) {
			$stats['estimated_savings'] += 350; // ~350KB
		}

		return $stats;
	}

	/**
	 * AJAX handler for getting stats
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$stats = $this->get_stats();
		wp_send_json_success( $stats );
	}
}
