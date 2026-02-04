<?php
/**
 * WooCommerce Optimizer Class
 *
 * WooCommerce-specific performance optimizations
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_WooCommerce class
 */
class VelocityWP_WooCommerce {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize WooCommerce optimizations
	 */
	public function init() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['woocommerce_optimize'] ) ) {
			return;
		}

		// Disable cart fragmentation
		if ( ! empty( $options['wc_disable_cart_fragments'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'disable_cart_fragments' ), 999 );
		}

		// Optimize script loading
		if ( ! empty( $options['wc_optimize_scripts'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'optimize_scripts' ), 999 );
		}

		// Disable features on non-WC pages
		if ( ! empty( $options['wc_disable_non_wc_pages'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'disable_on_non_wc_pages' ), 999 );
		}

		// Geolocation optimization
		if ( ! empty( $options['wc_optimize_geolocation'] ) ) {
			add_filter( 'woocommerce_geolocation_ajax_get_location_hash', '__return_false' );
		}

		// Disable password strength meter
		if ( ! empty( $options['wc_disable_password_meter'] ) ) {
			add_action( 'wp_print_scripts', array( $this, 'disable_password_meter' ), 100 );
		}

		// Optimize product images
		if ( ! empty( $options['wc_optimize_images'] ) ) {
			add_filter( 'woocommerce_gallery_image_size', array( $this, 'optimize_gallery_images' ) );
		}
	}

	/**
	 * Disable cart fragments
	 */
	public function disable_cart_fragments() {
		wp_dequeue_script( 'wc-cart-fragments' );
	}

	/**
	 * Optimize WooCommerce scripts
	 */
	public function optimize_scripts() {
		// Remove scripts from non-WC pages
		if ( ! $this->is_woocommerce_page() ) {
			return;
		}

		// Defer WooCommerce scripts
		add_filter( 'script_loader_tag', array( $this, 'defer_wc_scripts' ), 10, 3 );
	}

	/**
	 * Defer WooCommerce scripts
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string Modified tag.
	 */
	public function defer_wc_scripts( $tag, $handle, $src ) {
		$wc_scripts = array(
			'woocommerce',
			'wc-add-to-cart',
			'wc-cart',
			'wc-checkout',
		);

		foreach ( $wc_scripts as $wc_script ) {
			if ( strpos( $handle, $wc_script ) !== false ) {
				return str_replace( ' src', ' defer src', $tag );
			}
		}

		return $tag;
	}

	/**
	 * Disable WooCommerce assets on non-WC pages
	 */
	public function disable_on_non_wc_pages() {
		if ( $this->is_woocommerce_page() ) {
			return;
		}

		// Dequeue scripts
		wp_dequeue_script( 'wc-cart-fragments' );
		wp_dequeue_script( 'woocommerce' );
		wp_dequeue_script( 'wc-add-to-cart' );

		// Dequeue styles
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
	}

	/**
	 * Check if current page is WooCommerce related
	 *
	 * @return bool Whether it's a WooCommerce page.
	 */
	private function is_woocommerce_page() {
		if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
			return true;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}

		return false;
	}

	/**
	 * Disable password strength meter
	 */
	public function disable_password_meter() {
		if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
			wp_dequeue_script( 'wc-password-strength-meter' );
		}
	}

	/**
	 * Optimize product gallery images
	 *
	 * @param string $size Image size.
	 * @return string Modified size.
	 */
	public function optimize_gallery_images( $size ) {
		if ( wp_is_mobile() ) {
			return 'medium';
		}

		return $size;
	}

	/**
	 * Cache product data
	 *
	 * @param int $product_id Product ID.
	 */
	public function cache_product_data( $product_id ) {
		$cache_key = 'velocitywp_product_' . $product_id;
		
		$cached = wp_cache_get( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$product = wc_get_product( $product_id );
		$data = array(
			'name'  => $product->get_name(),
			'price' => $product->get_price(),
			'stock' => $product->get_stock_status(),
		);

		wp_cache_set( $cache_key, $data, '', 3600 );

		return $data;
	}
}
