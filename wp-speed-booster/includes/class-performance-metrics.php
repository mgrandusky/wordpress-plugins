<?php
/**
 * Performance Metrics Dashboard Class
 *
 * Google PageSpeed Insights API integration and Core Web Vitals tracking
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Performance_Metrics class
 */
class WPSB_Performance_Metrics {

	/**
	 * PageSpeed API endpoint
	 */
	const API_ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

	/**
	 * Constructor
	 */
	public function __construct() {
		// AJAX handlers
		add_action( 'wp_ajax_wpsb_run_pagespeed_test', array( $this, 'ajax_run_pagespeed_test' ) );
		add_action( 'wp_ajax_wpsb_get_performance_history', array( $this, 'ajax_get_performance_history' ) );
		
		// Schedule automatic checks
		add_action( 'wpsb_scheduled_performance_check', array( $this, 'scheduled_performance_check' ) );
		
		$this->maybe_schedule_checks();
	}

	/**
	 * Maybe schedule performance checks
	 */
	private function maybe_schedule_checks() {
		$options = get_option( 'wpsb_options', array() );
		
		if ( ! empty( $options['perf_metrics_auto_check'] ) ) {
			$frequency = isset( $options['perf_metrics_frequency'] ) ? $options['perf_metrics_frequency'] : 'daily';
			
			if ( ! wp_next_scheduled( 'wpsb_scheduled_performance_check' ) ) {
				wp_schedule_event( time(), $frequency, 'wpsb_scheduled_performance_check' );
			}
		} else {
			wp_clear_scheduled_hook( 'wpsb_scheduled_performance_check' );
		}
	}

	/**
	 * Run PageSpeed test via AJAX
	 */
	public function ajax_run_pagespeed_test() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : home_url();
		$strategy = isset( $_POST['strategy'] ) ? sanitize_text_field( $_POST['strategy'] ) : 'mobile';
		
		$results = $this->run_pagespeed_test( $url, $strategy );
		
		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
		}
		
		// Save to history
		$this->save_performance_history( $url, $strategy, $results );
		
		wp_send_json_success( $results );
	}

	/**
	 * Run PageSpeed Insights test
	 *
	 * @param string $url      URL to test.
	 * @param string $strategy Strategy (mobile or desktop).
	 * @return array|WP_Error Test results or error
	 */
	private function run_pagespeed_test( $url, $strategy = 'mobile' ) {
		$options = get_option( 'wpsb_options', array() );
		$api_key = isset( $options['pagespeed_api_key'] ) ? $options['pagespeed_api_key'] : '';
		
		$api_url = add_query_arg( array(
			'url'      => rawurlencode( $url ),
			'strategy' => $strategy,
			'category' => 'performance',
		), self::API_ENDPOINT );
		
		if ( ! empty( $api_key ) ) {
			$api_url = add_query_arg( 'key', $api_key, $api_url );
		}
		
		$response = wp_remote_get( $api_url, array(
			'timeout' => 60,
		) );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( empty( $data['lighthouseResult'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid PageSpeed API response', 'wp-speed-booster' ) );
		}
		
		$lighthouse = $data['lighthouseResult'];
		
		// Extract metrics
		$metrics = array(
			'score'        => isset( $lighthouse['categories']['performance']['score'] ) ? round( $lighthouse['categories']['performance']['score'] * 100 ) : 0,
			'fcp'          => $this->extract_metric( $lighthouse, 'first-contentful-paint' ),
			'lcp'          => $this->extract_metric( $lighthouse, 'largest-contentful-paint' ),
			'cls'          => $this->extract_metric( $lighthouse, 'cumulative-layout-shift' ),
			'fid'          => $this->extract_metric( $lighthouse, 'max-potential-fid' ),
			'ttfb'         => $this->extract_metric( $lighthouse, 'server-response-time' ),
			'tti'          => $this->extract_metric( $lighthouse, 'interactive' ),
			'tbt'          => $this->extract_metric( $lighthouse, 'total-blocking-time' ),
			'si'           => $this->extract_metric( $lighthouse, 'speed-index' ),
			'timestamp'    => current_time( 'mysql' ),
			'url'          => $url,
			'strategy'     => $strategy,
		);
		
		// Extract opportunities
		$opportunities = array();
		if ( ! empty( $lighthouse['audits'] ) ) {
			foreach ( $lighthouse['audits'] as $audit_id => $audit ) {
				if ( ! empty( $audit['details']['type'] ) && $audit['details']['type'] === 'opportunity' ) {
					$opportunities[] = array(
						'id'          => $audit_id,
						'title'       => $audit['title'],
						'description' => isset( $audit['description'] ) ? $audit['description'] : '',
						'savings'     => isset( $audit['details']['overallSavingsMs'] ) ? $audit['details']['overallSavingsMs'] : 0,
					);
				}
			}
		}
		
		$metrics['opportunities'] = $opportunities;
		
		return $metrics;
	}

	/**
	 * Extract metric from Lighthouse result
	 *
	 * @param array  $lighthouse Lighthouse result.
	 * @param string $metric_id  Metric ID.
	 * @return array Metric data
	 */
	private function extract_metric( $lighthouse, $metric_id ) {
		if ( empty( $lighthouse['audits'][ $metric_id ] ) ) {
			return array( 'value' => 0, 'displayValue' => 'N/A' );
		}
		
		$audit = $lighthouse['audits'][ $metric_id ];
		
		return array(
			'value'        => isset( $audit['numericValue'] ) ? $audit['numericValue'] : 0,
			'displayValue' => isset( $audit['displayValue'] ) ? $audit['displayValue'] : 'N/A',
			'score'        => isset( $audit['score'] ) ? round( $audit['score'] * 100 ) : 0,
		);
	}

	/**
	 * Save performance history
	 *
	 * @param string $url      URL tested.
	 * @param string $strategy Strategy used.
	 * @param array  $results  Test results.
	 */
	private function save_performance_history( $url, $strategy, $results ) {
		$history = get_option( 'wpspeed_performance_history', array() );
		
		if ( ! isset( $history[ $url ] ) ) {
			$history[ $url ] = array();
		}
		
		if ( ! isset( $history[ $url ][ $strategy ] ) ) {
			$history[ $url ][ $strategy ] = array();
		}
		
		// Keep last 30 entries
		$history[ $url ][ $strategy ][] = $results;
		$history[ $url ][ $strategy ] = array_slice( $history[ $url ][ $strategy ], -30 );
		
		update_option( 'wpspeed_performance_history', $history );
	}

	/**
	 * Get performance history via AJAX
	 */
	public function ajax_get_performance_history() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'wp-speed-booster' ) ) );
		}
		
		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : home_url();
		$strategy = isset( $_POST['strategy'] ) ? sanitize_text_field( $_POST['strategy'] ) : 'mobile';
		
		$history = get_option( 'wpspeed_performance_history', array() );
		
		$data = array();
		if ( isset( $history[ $url ][ $strategy ] ) ) {
			$data = $history[ $url ][ $strategy ];
		}
		
		wp_send_json_success( $data );
	}

	/**
	 * Scheduled performance check
	 */
	public function scheduled_performance_check() {
		$options = get_option( 'wpsb_options', array() );
		
		if ( empty( $options['perf_metrics_urls'] ) ) {
			$urls = array( home_url() );
		} else {
			$urls = array_map( 'trim', explode( "\n", $options['perf_metrics_urls'] ) );
		}
		
		foreach ( $urls as $url ) {
			if ( empty( $url ) ) {
				continue;
			}
			
			// Test both mobile and desktop
			$mobile_results = $this->run_pagespeed_test( $url, 'mobile' );
			if ( ! is_wp_error( $mobile_results ) ) {
				$this->save_performance_history( $url, 'mobile', $mobile_results );
				$this->check_score_threshold( $url, 'mobile', $mobile_results );
			}
			
			$desktop_results = $this->run_pagespeed_test( $url, 'desktop' );
			if ( ! is_wp_error( $desktop_results ) ) {
				$this->save_performance_history( $url, 'desktop', $desktop_results );
				$this->check_score_threshold( $url, 'desktop', $desktop_results );
			}
			
			// Rate limiting - don't hammer the API
			sleep( 5 );
		}
	}

	/**
	 * Check if score is below threshold and send alert
	 *
	 * @param string $url      URL tested.
	 * @param string $strategy Strategy used.
	 * @param array  $results  Test results.
	 */
	private function check_score_threshold( $url, $strategy, $results ) {
		$options = get_option( 'wpsb_options', array() );
		
		if ( empty( $options['perf_metrics_alert_threshold'] ) ) {
			return;
		}
		
		$threshold = absint( $options['perf_metrics_alert_threshold'] );
		$score = isset( $results['score'] ) ? $results['score'] : 0;
		
		if ( $score < $threshold ) {
			// Send email alert
			$admin_email = get_option( 'admin_email' );
			$subject = sprintf( __( 'Performance Alert: %s', 'wp-speed-booster' ), get_bloginfo( 'name' ) );
			$message = sprintf(
				__( "Performance score for %s (%s) has dropped to %d (threshold: %d)\n\nURL: %s\nTimestamp: %s", 'wp-speed-booster' ),
				get_bloginfo( 'name' ),
				$strategy,
				$score,
				$threshold,
				$url,
				current_time( 'mysql' )
			);
			
			wp_mail( $admin_email, $subject, $message );
		}
	}
}
