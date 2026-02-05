<?php
/**
 * Activity Logger Class
 *
 * Logs optimization activities for the dashboard
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Activity_Logger class
 */
class VelocityWP_Activity_Logger {
	
	/**
	 * Log an activity
	 *
	 * @param string $icon Activity icon (emoji).
	 * @param string $message Activity message.
	 */
	public static function log( $icon, $message ) {
		$log = get_option( 'velocitywp_activity_log', array() );
		
		$log[] = array(
			'icon' => $icon,
			'message' => $message,
			'timestamp' => current_time( 'timestamp' )
		);
		
		// Keep only last 50 activities
		if ( count( $log ) > 50 ) {
			$log = array_slice( $log, -50 );
		}
		
		update_option( 'velocitywp_activity_log', $log );
	}

	/**
	 * Get recent activities
	 *
	 * @param int $limit Number of activities to retrieve.
	 * @return array Recent activities (newest first).
	 */
	public static function get_recent( $limit = 10 ) {
		$log = get_option( 'velocitywp_activity_log', array() );
		return array_reverse( array_slice( $log, -$limit ) );
	}

	/**
	 * Clear activity log
	 */
	public static function clear() {
		delete_option( 'velocitywp_activity_log' );
	}
}
