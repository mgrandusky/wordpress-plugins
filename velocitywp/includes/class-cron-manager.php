<?php
/**
 * Cron Manager Class
 *
 * Scheduled tasks dashboard and management
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Cron_Manager class
 */
class VelocityWP_Cron_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_get_cron_jobs', array( $this, 'ajax_get_cron_jobs' ) );
		add_action( 'wp_ajax_velocitywp_run_cron_job', array( $this, 'ajax_run_cron_job' ) );
		add_action( 'wp_ajax_velocitywp_delete_cron_job', array( $this, 'ajax_delete_cron_job' ) );
	}

	/**
	 * Initialize cron manager
	 */
	public function init() {
		// Register custom schedules
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );

		// Setup default cron jobs
		$this->setup_default_cron_jobs();
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_custom_schedules( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'velocitywp' ),
		);

		$schedules['fifteen_minutes'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 Minutes', 'velocitywp' ),
		);

		$schedules['thirty_minutes'] = array(
			'interval' => 1800,
			'display'  => __( 'Every 30 Minutes', 'velocitywp' ),
		);

		return $schedules;
	}

	/**
	 * Setup default cron jobs
	 */
	private function setup_default_cron_jobs() {
		$options = get_option( 'velocitywp_options', array() );

		// Cache cleanup
		if ( ! empty( $options['cache_cleanup_schedule'] ) ) {
			if ( ! wp_next_scheduled( 'velocitywp_cache_cleanup' ) ) {
				wp_schedule_event( time(), 'daily', 'velocitywp_cache_cleanup' );
			}
		}

		// Database optimization
		if ( ! empty( $options['db_optimize_schedule'] ) ) {
			if ( ! wp_next_scheduled( 'velocitywp_database_optimize' ) ) {
				wp_schedule_event( time(), 'weekly', 'velocitywp_database_optimize' );
			}
		}

		// Image optimization
		if ( ! empty( $options['image_optimize_schedule'] ) ) {
			if ( ! wp_next_scheduled( 'velocitywp_image_optimize' ) ) {
				wp_schedule_event( time(), 'daily', 'velocitywp_image_optimize' );
			}
		}
	}

	/**
	 * Get all cron jobs
	 *
	 * @return array Cron jobs.
	 */
	public function get_cron_jobs() {
		$crons = _get_cron_array();
		$jobs = array();

		if ( empty( $crons ) ) {
			return $jobs;
		}

		foreach ( $crons as $timestamp => $cron ) {
			foreach ( $cron as $hook => $dings ) {
				// Filter to only WPSB hooks
				if ( strpos( $hook, 'velocitywp_' ) !== 0 ) {
					continue;
				}

				foreach ( $dings as $sig => $data ) {
					$jobs[] = array(
						'hook'      => $hook,
						'timestamp' => $timestamp,
						'signature' => $sig,
						'schedule'  => isset( $data['schedule'] ) ? $data['schedule'] : 'single',
						'interval'  => isset( $data['interval'] ) ? $data['interval'] : 0,
						'args'      => isset( $data['args'] ) ? $data['args'] : array(),
						'next_run'  => $timestamp,
					);
				}
			}
		}

		return $jobs;
	}

	/**
	 * Run cron job manually
	 *
	 * @param string $hook Cron hook name.
	 * @param array  $args Cron arguments.
	 * @return bool Success.
	 */
	public function run_cron_job( $hook, $args = array() ) {
		if ( empty( $hook ) ) {
			return false;
		}

		do_action( $hook, ...$args );

		return true;
	}

	/**
	 * Delete cron job
	 *
	 * @param string $hook      Cron hook name.
	 * @param int    $timestamp Scheduled timestamp.
	 * @param array  $args      Cron arguments.
	 * @return bool Success.
	 */
	public function delete_cron_job( $hook, $timestamp, $args = array() ) {
		return wp_unschedule_event( $timestamp, $hook, $args );
	}

	/**
	 * Get cron job status
	 *
	 * @param string $hook Cron hook name.
	 * @return array Status information.
	 */
	public function get_cron_status( $hook ) {
		$next_run = wp_next_scheduled( $hook );

		return array(
			'scheduled' => $next_run !== false,
			'next_run'  => $next_run ? $next_run : null,
			'next_run_human' => $next_run ? human_time_diff( $next_run ) : null,
		);
	}

	/**
	 * AJAX handler to get cron jobs
	 */
	public function ajax_get_cron_jobs() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$jobs = $this->get_cron_jobs();

		wp_send_json_success( array( 'jobs' => $jobs ) );
	}

	/**
	 * AJAX handler to run cron job
	 */
	public function ajax_run_cron_job() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$hook = isset( $_POST['hook'] ) ? sanitize_text_field( $_POST['hook'] ) : '';

		if ( empty( $hook ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid hook', 'velocitywp' ) ) );
		}

		$result = $this->run_cron_job( $hook );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Cron job executed', 'velocitywp' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to execute cron job', 'velocitywp' ) ) );
		}
	}

	/**
	 * AJAX handler to delete cron job
	 */
	public function ajax_delete_cron_job() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$hook = isset( $_POST['hook'] ) ? sanitize_text_field( $_POST['hook'] ) : '';
		$timestamp = isset( $_POST['timestamp'] ) ? intval( $_POST['timestamp'] ) : 0;

		if ( empty( $hook ) || empty( $timestamp ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'velocitywp' ) ) );
		}

		$result = $this->delete_cron_job( $hook, $timestamp );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Cron job deleted', 'velocitywp' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete cron job', 'velocitywp' ) ) );
		}
	}
}
