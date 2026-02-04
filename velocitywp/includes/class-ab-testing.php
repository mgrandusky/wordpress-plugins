<?php
/**
 * A/B Testing Class
 *
 * A/B testing framework for performance optimizations
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_AB_Testing class
 */
class WPSB_AB_Testing {

	/**
	 * Test ID
	 *
	 * @var string
	 */
	private $test_id = '';

	/**
	 * Variant
	 *
	 * @var string
	 */
	private $variant = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_wpsb_get_ab_tests', array( $this, 'ajax_get_tests' ) );
		add_action( 'wp_ajax_wpsb_create_ab_test', array( $this, 'ajax_create_test' ) );
		add_action( 'wp_ajax_wpsb_end_ab_test', array( $this, 'ajax_end_test' ) );
	}

	/**
	 * Initialize A/B testing
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['ab_testing'] ) ) {
			return;
		}

		// Assign variant to visitor
		$this->assign_variant();

		// Track test results
		add_action( 'wp_footer', array( $this, 'track_results' ), 9999 );
	}

	/**
	 * Assign variant to visitor
	 */
	private function assign_variant() {
		// Check if visitor already has a variant
		if ( isset( $_COOKIE['wpsb_ab_variant'] ) ) {
			$this->variant = sanitize_text_field( $_COOKIE['wpsb_ab_variant'] );
			return;
		}

		// Get active tests
		$tests = $this->get_active_tests();

		if ( empty( $tests ) ) {
			return;
		}

		// Get first active test
		$test = $tests[0];
		$this->test_id = $test['id'];

		// Randomly assign variant
		$variants = $test['variants'];
		$this->variant = $variants[ array_rand( $variants ) ];

		// Set cookie
		setcookie( 'wpsb_ab_variant', $this->variant, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'wpsb_ab_test_id', $this->test_id, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

		// Apply variant settings
		$this->apply_variant_settings( $test, $this->variant );
	}

	/**
	 * Apply variant settings
	 *
	 * @param array  $test    Test configuration.
	 * @param string $variant Variant name.
	 */
	private function apply_variant_settings( $test, $variant ) {
		if ( empty( $test['settings'][ $variant ] ) ) {
			return;
		}

		$settings = $test['settings'][ $variant ];

		// Override plugin options with variant settings
		add_filter( 'option_wpsb_options', function( $options ) use ( $settings ) {
			return array_merge( $options, $settings );
		} );
	}

	/**
	 * Track test results
	 */
	public function track_results() {
		if ( empty( $this->test_id ) || empty( $this->variant ) ) {
			return;
		}

		// Get page load time
		$load_time = microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'];

		// Record result
		$this->record_result( array(
			'test_id'   => $this->test_id,
			'variant'   => $this->variant,
			'load_time' => $load_time,
			'url'       => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '',
			'timestamp' => current_time( 'mysql' ),
		) );
	}

	/**
	 * Record test result
	 *
	 * @param array $data Result data.
	 */
	private function record_result( $data ) {
		$results = get_option( 'wpsb_ab_results', array() );

		if ( ! isset( $results[ $data['test_id'] ] ) ) {
			$results[ $data['test_id'] ] = array();
		}

		if ( ! isset( $results[ $data['test_id'] ][ $data['variant'] ] ) ) {
			$results[ $data['test_id'] ][ $data['variant'] ] = array(
				'count'         => 0,
				'total_time'    => 0,
				'avg_load_time' => 0,
			);
		}

		$variant_results = &$results[ $data['test_id'] ][ $data['variant'] ];
		
		$variant_results['count']++;
		$variant_results['total_time'] += $data['load_time'];
		$variant_results['avg_load_time'] = $variant_results['total_time'] / $variant_results['count'];

		update_option( 'wpsb_ab_results', $results, false );
	}

	/**
	 * Get active tests
	 *
	 * @return array Active tests.
	 */
	private function get_active_tests() {
		$tests = get_option( 'wpsb_ab_tests', array() );
		
		$active = array();
		foreach ( $tests as $test ) {
			if ( ! empty( $test['active'] ) ) {
				$active[] = $test;
			}
		}

		return $active;
	}

	/**
	 * Create new A/B test
	 *
	 * @param array $config Test configuration.
	 * @return string Test ID.
	 */
	public function create_test( $config ) {
		$test_id = uniqid( 'test_' );

		$test = array(
			'id'          => $test_id,
			'name'        => $config['name'],
			'description' => ! empty( $config['description'] ) ? $config['description'] : '',
			'variants'    => $config['variants'],
			'settings'    => $config['settings'],
			'active'      => true,
			'created'     => current_time( 'mysql' ),
		);

		$tests = get_option( 'wpsb_ab_tests', array() );
		$tests[] = $test;
		update_option( 'wpsb_ab_tests', $tests );

		return $test_id;
	}

	/**
	 * End A/B test
	 *
	 * @param string $test_id Test ID.
	 */
	public function end_test( $test_id ) {
		$tests = get_option( 'wpsb_ab_tests', array() );

		foreach ( $tests as &$test ) {
			if ( $test['id'] === $test_id ) {
				$test['active'] = false;
				$test['ended'] = current_time( 'mysql' );
				break;
			}
		}

		update_option( 'wpsb_ab_tests', $tests );
	}

	/**
	 * Get test results
	 *
	 * @param string $test_id Test ID.
	 * @return array Test results.
	 */
	public function get_test_results( $test_id ) {
		$results = get_option( 'wpsb_ab_results', array() );

		if ( ! isset( $results[ $test_id ] ) ) {
			return array();
		}

		// Calculate winner
		$winner = null;
		$best_time = PHP_FLOAT_MAX;

		foreach ( $results[ $test_id ] as $variant => $data ) {
			if ( $data['avg_load_time'] < $best_time ) {
				$best_time = $data['avg_load_time'];
				$winner = $variant;
			}
		}

		return array(
			'variants' => $results[ $test_id ],
			'winner'   => $winner,
		);
	}

	/**
	 * AJAX handler to get tests
	 */
	public function ajax_get_tests() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$tests = get_option( 'wpsb_ab_tests', array() );
		$results = get_option( 'wpsb_ab_results', array() );

		// Attach results to tests
		foreach ( $tests as &$test ) {
			$test['results'] = $this->get_test_results( $test['id'] );
		}

		wp_send_json_success( array( 'tests' => $tests ) );
	}

	/**
	 * AJAX handler to create test
	 */
	public function ajax_create_test() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$config = isset( $_POST['config'] ) ? json_decode( stripslashes( $_POST['config'] ), true ) : array();

		if ( empty( $config ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid configuration', 'wp-speed-booster' ) ) );
		}

		$test_id = $this->create_test( $config );

		wp_send_json_success( array(
			'message' => __( 'Test created successfully', 'wp-speed-booster' ),
			'test_id' => $test_id,
		) );
	}

	/**
	 * AJAX handler to end test
	 */
	public function ajax_end_test() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$test_id = isset( $_POST['test_id'] ) ? sanitize_text_field( $_POST['test_id'] ) : '';

		if ( empty( $test_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid test ID', 'wp-speed-booster' ) ) );
		}

		$this->end_test( $test_id );

		$results = $this->get_test_results( $test_id );

		wp_send_json_success( array(
			'message' => __( 'Test ended successfully', 'wp-speed-booster' ),
			'results' => $results,
		) );
	}
}
