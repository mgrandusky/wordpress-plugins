<?php
/**
 * Dynamic Content Class
 *
 * AJAX cart and personalization handling
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Dynamic_Content class
 */
class VelocityWP_Dynamic_Content {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_get_dynamic_content', array( $this, 'ajax_get_dynamic_content' ) );
		add_action( 'wp_ajax_nopriv_velocitywp_get_dynamic_content', array( $this, 'ajax_get_dynamic_content' ) );
	}

	/**
	 * Initialize dynamic content
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['dynamic_content'] ) ) {
			return;
		}

		// Add dynamic content placeholders
		if ( ! empty( $options['ajax_cart'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'the_content', array( $this, 'add_placeholders' ), 999 );
		}

		// Handle user-specific content
		if ( ! empty( $options['personalized_content'] ) ) {
			add_shortcode( 'velocitywp_dynamic', array( $this, 'dynamic_shortcode' ) );
		}
	}

	/**
	 * Enqueue dynamic content scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'velocitywp-dynamic-content',
			WPSB_URL . 'assets/dynamic-content.js',
			array( 'jquery' ),
			WPSB_VERSION,
			true
		);

		wp_localize_script( 'velocitywp-dynamic-content', 'velocitywpDynamic', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'velocitywp-dynamic-nonce' ),
		) );
	}

	/**
	 * Add dynamic content placeholders
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function add_placeholders( $content ) {
		// Replace dynamic elements with placeholders
		$content = $this->replace_cart_widget( $content );
		$content = $this->replace_user_content( $content );

		return $content;
	}

	/**
	 * Replace cart widget with placeholder
	 *
	 * @param string $content Content.
	 * @return string Modified content.
	 */
	private function replace_cart_widget( $content ) {
		// Look for cart widgets
		if ( strpos( $content, 'woocommerce-mini-cart' ) !== false ) {
			$placeholder = '<div class="velocitywp-dynamic-cart" data-velocitywp-type="cart"></div>';
			$content = preg_replace( '/<div[^>]*woocommerce-mini-cart[^>]*>.*?<\/div>/s', $placeholder, $content );
		}

		return $content;
	}

	/**
	 * Replace user-specific content with placeholder
	 *
	 * @param string $content Content.
	 * @return string Modified content.
	 */
	private function replace_user_content( $content ) {
		// Replace logged-in user elements
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$placeholder = '<span class="velocitywp-dynamic-user" data-velocitywp-type="username" data-velocitywp-user="' . esc_attr( $user->ID ) . '"></span>';
			
			// This is a simplified example
			$content = str_replace( $user->display_name, $placeholder, $content );
		}

		return $content;
	}

	/**
	 * Dynamic content shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function dynamic_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'type'    => 'user',
			'content' => '',
		), $atts );

		$cache_key = 'velocitywp_dynamic_' . md5( serialize( $atts ) . get_current_user_id() );
		
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$output = $this->generate_dynamic_content( $atts );
		
		set_transient( $cache_key, $output, 300 ); // 5 minutes

		return $output;
	}

	/**
	 * Generate dynamic content
	 *
	 * @param array $atts Attributes.
	 * @return string Generated content.
	 */
	private function generate_dynamic_content( $atts ) {
		$type = $atts['type'];
		$output = '';

		switch ( $type ) {
			case 'user':
				if ( is_user_logged_in() ) {
					$user = wp_get_current_user();
					$output = esc_html( $user->display_name );
				}
				break;

			case 'cart':
				if ( class_exists( 'WooCommerce' ) ) {
					$cart_count = WC()->cart->get_cart_contents_count();
					$output = sprintf( __( '%d items', 'velocitywp' ), $cart_count );
				}
				break;

			case 'custom':
				$output = $this->get_custom_dynamic_content( $atts );
				break;
		}

		return apply_filters( 'velocitywp_dynamic_content_output', $output, $atts );
	}

	/**
	 * Get custom dynamic content
	 *
	 * @param array $atts Attributes.
	 * @return string Content.
	 */
	private function get_custom_dynamic_content( $atts ) {
		$content = ! empty( $atts['content'] ) ? $atts['content'] : '';

		// Replace variables
		$replacements = array(
			'{user_name}'  => is_user_logged_in() ? wp_get_current_user()->display_name : '',
			'{user_email}' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
			'{cart_count}' => class_exists( 'WooCommerce' ) ? WC()->cart->get_cart_contents_count() : 0,
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	}

	/**
	 * AJAX handler to get dynamic content
	 */
	public function ajax_get_dynamic_content() {
		check_ajax_referer( 'velocitywp-dynamic-nonce', 'nonce' );

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$data = array();

		switch ( $type ) {
			case 'cart':
				$data = $this->get_cart_data();
				break;

			case 'user':
				$data = $this->get_user_data();
				break;

			case 'all':
				$data = array(
					'cart' => $this->get_cart_data(),
					'user' => $this->get_user_data(),
				);
				break;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Get cart data
	 *
	 * @return array Cart data.
	 */
	private function get_cart_data() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		return array(
			'count'    => WC()->cart->get_cart_contents_count(),
			'subtotal' => WC()->cart->get_cart_subtotal(),
			'total'    => WC()->cart->get_total(),
		);
	}

	/**
	 * Get user data
	 *
	 * @return array User data.
	 */
	private function get_user_data() {
		if ( ! is_user_logged_in() ) {
			return array(
				'logged_in' => false,
			);
		}

		$user = wp_get_current_user();

		return array(
			'logged_in'    => true,
			'display_name' => $user->display_name,
			'user_email'   => $user->user_email,
		);
	}
}
