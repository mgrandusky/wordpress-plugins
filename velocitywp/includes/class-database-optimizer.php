<?php
/**
 * Database Optimizer Class
 *
 * Comprehensive database optimization and cleanup system with 
 * automated maintenance, table optimization, and revision management.
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Database_Optimizer class
 */
class VelocityWP_Database_Optimizer {

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'velocitywp_options', array() );
		
		// Register hooks
		add_action( 'velocitywp_database_optimization', array( $this, 'run_scheduled_optimization' ) );
		
		// Register AJAX handlers
		add_action( 'wp_ajax_velocitywp_db_get_stats', array( $this, 'ajax_get_stats' ) );
		add_action( 'wp_ajax_velocitywp_db_cleanup', array( $this, 'ajax_cleanup' ) );
		add_action( 'wp_ajax_velocitywp_db_optimize_tables', array( $this, 'ajax_optimize_tables' ) );
		add_action( 'wp_ajax_velocitywp_db_get_table_info', array( $this, 'ajax_get_table_info' ) );
	}

	/**
	 * Check if database optimization is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->settings['db_optimization_enabled'] );
	}

	// ========================================================================
	// POST CLEANUP METHODS
	// ========================================================================

	/**
	 * Delete post revisions
	 *
	 * @param int $keep Number of revisions to keep per post (0 = delete all).
	 * @return int Number of revisions deleted.
	 */
	public function delete_post_revisions( $keep = 0 ) {
		global $wpdb;
		
		$deleted = 0;
		
		if ( $keep > 0 ) {
			// Keep X most recent revisions per post
			$posts = $wpdb->get_results(
				"SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent > 0"
			);
			
			foreach ( $posts as $post ) {
				$revisions = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} 
						WHERE post_parent = %d AND post_type = 'revision' 
						ORDER BY post_date DESC",
						$post->post_parent
					)
				);
				
				// Delete old revisions beyond the keep limit
				if ( count( $revisions ) > $keep ) {
					$revisions_to_delete = array_slice( $revisions, $keep );
					foreach ( $revisions_to_delete as $revision ) {
						if ( wp_delete_post_revision( $revision->ID ) ) {
							$deleted++;
						}
					}
				}
			}
		} else {
			// Delete all revisions
			$revisions = $wpdb->get_results(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision'"
			);
			
			foreach ( $revisions as $revision ) {
				if ( wp_delete_post_revision( $revision->ID ) ) {
					$deleted++;
				}
			}
		}
		
		$this->delete_orphaned_post_meta();
		
		return $deleted;
	}

	/**
	 * Delete auto-drafts older than 7 days
	 *
	 * @return int Number of auto-drafts deleted.
	 */
	public function delete_auto_drafts() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->posts} 
			WHERE post_status = 'auto-draft' 
			AND DATE_SUB(NOW(), INTERVAL 7 DAY) > post_date"
		);
		
		$this->delete_orphaned_post_meta();
		
		return intval( $deleted );
	}

	/**
	 * Empty trash (delete trashed posts permanently)
	 *
	 * @return int Number of posts deleted.
	 */
	public function empty_trash() {
		global $wpdb;
		
		$trashed_posts = $wpdb->get_results(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);
		
		$deleted = 0;
		foreach ( $trashed_posts as $post ) {
			if ( wp_delete_post( $post->ID, true ) ) {
				$deleted++;
			}
		}
		
		$this->delete_orphaned_post_meta();
		
		return $deleted;
	}

	/**
	 * Delete orphaned post meta
	 *
	 * @return int Number of orphaned meta deleted.
	 */
	public function delete_orphaned_post_meta() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.ID IS NULL"
		);
		
		return intval( $deleted );
	}

	// ========================================================================
	// COMMENT CLEANUP METHODS
	// ========================================================================

	/**
	 * Delete spam comments
	 *
	 * @return int Number of spam comments deleted.
	 */
	public function delete_spam_comments() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);
		
		$this->delete_orphaned_comment_meta();
		
		return intval( $deleted );
	}

	/**
	 * Delete trashed comments
	 *
	 * @return int Number of trashed comments deleted.
	 */
	public function delete_trashed_comments() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
		);
		
		$this->delete_orphaned_comment_meta();
		
		return intval( $deleted );
	}

	/**
	 * Delete pending comments older than X days
	 *
	 * @param int $days Number of days (default 30).
	 * @return int Number of pending comments deleted.
	 */
	public function delete_pending_comments( $days = 30 ) {
		global $wpdb;
		
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->comments} 
				WHERE comment_approved = '0' 
				AND DATE_SUB(NOW(), INTERVAL %d DAY) > comment_date",
				$days
			)
		);
		
		$this->delete_orphaned_comment_meta();
		
		return intval( $deleted );
	}

	/**
	 * Delete pingbacks and trackbacks
	 *
	 * @return int Number of pingbacks/trackbacks deleted.
	 */
	public function delete_pingbacks_trackbacks() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->comments} 
			WHERE comment_type IN ('pingback', 'trackback')"
		);
		
		$this->delete_orphaned_comment_meta();
		
		return intval( $deleted );
	}

	/**
	 * Delete orphaned comment meta
	 *
	 * @return int Number of orphaned comment meta deleted.
	 */
	public function delete_orphaned_comment_meta() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE cm FROM {$wpdb->commentmeta} cm
			LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
			WHERE c.comment_ID IS NULL"
		);
		
		return intval( $deleted );
	}

	// ========================================================================
	// TRANSIENT CLEANUP METHODS
	// ========================================================================

	/**
	 * Delete expired transients
	 *
	 * @return int Number of transients deleted.
	 */
	public function delete_expired_transients() {
		global $wpdb;
		
		$time = time();
		
		// Delete expired transient timeouts
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE '_transient_timeout_%'
				AND option_value < %d",
				$time
			)
		);
		
		// Delete corresponding transient values
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_%'
			AND option_name NOT LIKE '_transient_timeout_%'
			AND option_name NOT IN (
				SELECT CONCAT('_transient_', SUBSTRING(option_name, 20))
				FROM (SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%') AS temp
			)"
		);
		
		return intval( $deleted );
	}

	/**
	 * Delete all transients
	 *
	 * @return int Number of transients deleted.
	 */
	public function delete_all_transients() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_%'"
		);
		
		return intval( $deleted );
	}

	/**
	 * Count expired transients
	 *
	 * @return int Number of expired transients.
	 */
	public function count_expired_transients() {
		global $wpdb;
		
		$time = time();
		return intval( $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
				WHERE option_name LIKE '_transient_timeout_%'
				AND option_value < %d",
				$time
			)
		) );
	}

	// ========================================================================
	// TERM CLEANUP METHODS
	// ========================================================================

	/**
	 * Delete unused terms
	 *
	 * @return int Number of unused terms deleted.
	 */
	public function delete_unused_terms() {
		global $wpdb;
		
		// Find terms not used in any relationships
		$unused_terms = $wpdb->get_results(
			"SELECT t.term_id FROM {$wpdb->terms} t
			LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.term_id IS NULL OR tt.count = 0"
		);
		
		$deleted = 0;
		foreach ( $unused_terms as $term ) {
			if ( wp_delete_term( $term->term_id, 'category' ) || wp_delete_term( $term->term_id, 'post_tag' ) ) {
				$deleted++;
			}
		}
		
		return $deleted;
	}

	/**
	 * Delete orphaned term relationships
	 *
	 * @return int Number of orphaned relationships deleted.
	 */
	public function delete_orphaned_term_relationships() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE tr FROM {$wpdb->term_relationships} tr
			LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id
			WHERE p.ID IS NULL"
		);
		
		return intval( $deleted );
	}

	/**
	 * Delete orphaned term meta
	 *
	 * @return int Number of orphaned term meta deleted.
	 */
	public function delete_orphaned_term_meta() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE tm FROM {$wpdb->termmeta} tm
			LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
			WHERE t.term_id IS NULL"
		);
		
		return intval( $deleted );
	}

	// ========================================================================
	// USER CLEANUP METHODS
	// ========================================================================

	/**
	 * Delete orphaned user meta
	 *
	 * @return int Number of orphaned user meta deleted.
	 */
	public function delete_orphaned_user_meta() {
		global $wpdb;
		
		$deleted = $wpdb->query(
			"DELETE um FROM {$wpdb->usermeta} um
			LEFT JOIN {$wpdb->users} u ON u.ID = um.user_id
			WHERE u.ID IS NULL"
		);
		
		return intval( $deleted );
	}

	// ========================================================================
	// TABLE OPERATIONS
	// ========================================================================

	/**
	 * Get all database tables
	 *
	 * @return array List of table names.
	 */
	public function get_all_tables() {
		global $wpdb;
		
		$tables = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			),
			ARRAY_N
		);
		
		$table_names = array();
		foreach ( $tables as $table ) {
			$table_names[] = $table[0];
		}
		
		return $table_names;
	}

	/**
	 * Optimize database tables
	 *
	 * @param array|null $tables Tables to optimize (null = all tables).
	 * @return array Optimization results.
	 */
	public function optimize_tables( $tables = null ) {
		global $wpdb;
		
		if ( $tables === null ) {
			$tables = $this->get_all_tables();
		}
		
		$results = array();
		
		foreach ( $tables as $table ) {
			$result = $wpdb->query( "OPTIMIZE TABLE `{$table}`" );
			$results[ $table ] = $result !== false;
		}
		
		return $results;
	}

	/**
	 * Repair database tables
	 *
	 * @param array|null $tables Tables to repair (null = all tables).
	 * @return array Repair results.
	 */
	public function repair_tables( $tables = null ) {
		global $wpdb;
		
		if ( $tables === null ) {
			$tables = $this->get_all_tables();
		}
		
		$results = array();
		
		foreach ( $tables as $table ) {
			$result = $wpdb->query( "REPAIR TABLE `{$table}`" );
			$results[ $table ] = $result !== false;
		}
		
		return $results;
	}

	/**
	 * Analyze database tables
	 *
	 * @param array|null $tables Tables to analyze (null = all tables).
	 * @return array Analysis results.
	 */
	public function analyze_tables( $tables = null ) {
		global $wpdb;
		
		if ( $tables === null ) {
			$tables = $this->get_all_tables();
		}
		
		$results = array();
		
		foreach ( $tables as $table ) {
			$result = $wpdb->query( "ANALYZE TABLE `{$table}`" );
			$results[ $table ] = $result !== false;
		}
		
		return $results;
	}

	/**
	 * Get detailed table information
	 *
	 * @return array Table information including sizes and overhead.
	 */
	public function get_table_info() {
		global $wpdb;
		
		$tables = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW TABLE STATUS LIKE %s",
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			)
		);
		
		$info = array();
		$total_size = 0;
		$total_overhead = 0;
		
		foreach ( $tables as $table ) {
			$size = $table->Data_length + $table->Index_length;
			$overhead = isset( $table->Data_free ) ? $table->Data_free : 0;
			
			$info[] = array(
				'name' => $table->Name,
				'engine' => $table->Engine,
				'rows' => number_format( $table->Rows ),
				'size' => size_format( $size ),
				'size_bytes' => $size,
				'overhead' => size_format( $overhead ),
				'overhead_bytes' => $overhead,
				'collation' => isset( $table->Collation ) ? $table->Collation : 'N/A',
			);
			
			$total_size += $size;
			$total_overhead += $overhead;
		}
		
		return array(
			'tables' => $info,
			'total_size' => size_format( $total_size ),
			'total_size_bytes' => $total_size,
			'total_overhead' => size_format( $total_overhead ),
			'total_overhead_bytes' => $total_overhead,
			'table_count' => count( $info ),
		);
	}

	// ========================================================================
	// STATISTICS METHODS
	// ========================================================================

	/**
	 * Get comprehensive database statistics
	 *
	 * @return array Database statistics.
	 */
	public function get_stats() {
		global $wpdb;
		
		return array(
			'revisions' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" ) ),
			'auto_drafts' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" ) ),
			'trashed_posts' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" ) ),
			'spam_comments' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ) ),
			'trashed_comments' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" ) ),
			'pending_comments' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '0'" ) ),
			'pingbacks' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'pingback'" ) ),
			'trackbacks' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'trackback'" ) ),
			'expired_transients' => $this->count_expired_transients(),
			'orphaned_postmeta' => $this->count_orphaned_postmeta(),
			'orphaned_commentmeta' => $this->count_orphaned_commentmeta(),
			'orphaned_termmeta' => $this->count_orphaned_termmeta(),
			'orphaned_usermeta' => $this->count_orphaned_usermeta(),
		);
	}

	/**
	 * Count orphaned post meta
	 *
	 * @return int Number of orphaned post meta.
	 */
	private function count_orphaned_postmeta() {
		global $wpdb;
		
		return intval( $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.ID IS NULL"
		) );
	}

	/**
	 * Count orphaned comment meta
	 *
	 * @return int Number of orphaned comment meta.
	 */
	private function count_orphaned_commentmeta() {
		global $wpdb;
		
		return intval( $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm
			LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
			WHERE c.comment_ID IS NULL"
		) );
	}

	/**
	 * Count orphaned term meta
	 *
	 * @return int Number of orphaned term meta.
	 */
	private function count_orphaned_termmeta() {
		global $wpdb;
		
		return intval( $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
			LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
			WHERE t.term_id IS NULL"
		) );
	}

	/**
	 * Count orphaned user meta
	 *
	 * @return int Number of orphaned user meta.
	 */
	private function count_orphaned_usermeta() {
		global $wpdb;
		
		return intval( $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} um
			LEFT JOIN {$wpdb->users} u ON u.ID = um.user_id
			WHERE u.ID IS NULL"
		) );
	}

	/**
	 * Get total database size
	 *
	 * @return array Database size information.
	 */
	public function get_database_size() {
		global $wpdb;
		
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					SUM(data_length + index_length) as size,
					SUM(data_free) as overhead
				FROM information_schema.TABLES 
				WHERE table_schema = %s 
				AND table_name LIKE %s",
				DB_NAME,
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			)
		);
		
		$size = isset( $result->size ) ? intval( $result->size ) : 0;
		$overhead = isset( $result->overhead ) ? intval( $result->overhead ) : 0;
		
		return array(
			'size' => $size,
			'size_formatted' => size_format( $size ),
			'overhead' => $overhead,
			'overhead_formatted' => size_format( $overhead ),
		);
	}

	// ========================================================================
	// SCHEDULED OPTIMIZATION
	// ========================================================================

	/**
	 * Setup scheduled optimization
	 */
	public function setup_schedule() {
		$frequency = ! empty( $this->settings['db_optimize_schedule'] ) ? 
			$this->settings['db_optimize_schedule'] : 'weekly';
		
		// Clear existing schedule
		wp_clear_scheduled_hook( 'velocitywp_database_optimization' );
		
		// Only schedule if optimization is enabled
		if ( $this->is_enabled() && $frequency !== 'disabled' ) {
			wp_schedule_event( time(), $frequency, 'velocitywp_database_optimization' );
		}
	}

	/**
	 * Run scheduled optimization
	 */
	public function run_scheduled_optimization() {
		$operations = ! empty( $this->settings['db_optimize_operations'] ) ? 
			$this->settings['db_optimize_operations'] : array();
		
		$results = array();
		
		if ( in_array( 'revisions', $operations ) ) {
			$keep = ! empty( $this->settings['keep_revisions'] ) ? intval( $this->settings['keep_revisions'] ) : 0;
			$results['revisions'] = $this->delete_post_revisions( $keep );
		}
		
		if ( in_array( 'auto_drafts', $operations ) ) {
			$results['auto_drafts'] = $this->delete_auto_drafts();
		}
		
		if ( in_array( 'trashed_posts', $operations ) ) {
			$results['trashed_posts'] = $this->empty_trash();
		}
		
		if ( in_array( 'spam_comments', $operations ) ) {
			$results['spam_comments'] = $this->delete_spam_comments();
		}
		
		if ( in_array( 'expired_transients', $operations ) ) {
			$results['expired_transients'] = $this->delete_expired_transients();
		}
		
		if ( in_array( 'optimize_tables', $operations ) ) {
			$results['optimize_tables'] = $this->optimize_tables();
		}
		
		// Send email report if enabled
		if ( ! empty( $this->settings['db_optimize_email_report'] ) ) {
			$this->send_optimization_report( $results );
		}
		
		return $results;
	}

	/**
	 * Send optimization report email
	 *
	 * @param array $results Optimization results.
	 */
	public function send_optimization_report( $results ) {
		$admin_email = get_option( 'admin_email' );
		$site_name = get_bloginfo( 'name' );
		
		$subject = sprintf( __( '[%s] Database Optimization Report', 'velocitywp' ), $site_name );
		
		$message = sprintf( __( 'Database optimization completed on %s', 'velocitywp' ), date( 'Y-m-d H:i:s' ) ) . "\n\n";
		$message .= __( 'Results:', 'velocitywp' ) . "\n";
		$message .= str_repeat( '-', 50 ) . "\n";
		
		foreach ( $results as $operation => $result ) {
			if ( is_array( $result ) ) {
				$message .= sprintf( "%s: %d items\n", ucwords( str_replace( '_', ' ', $operation ) ), count( $result ) );
			} else {
				$message .= sprintf( "%s: %d items\n", ucwords( str_replace( '_', ' ', $operation ) ), $result );
			}
		}
		
		$message .= "\n" . sprintf( __( 'Site: %s', 'velocitywp' ), home_url() ) . "\n";
		
		wp_mail( $admin_email, $subject, $message );
	}

	// ========================================================================
	// AJAX HANDLERS
	// ========================================================================

	/**
	 * AJAX handler: Get database statistics
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'velocitywp_db_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'velocitywp' ) ) );
		}
		
		$stats = $this->get_stats();
		$db_size = $this->get_database_size();
		$table_info = $this->get_table_info();
		
		wp_send_json_success( array(
			'stats' => $stats,
			'db_size' => $db_size,
			'table_info' => $table_info,
		) );
	}

	/**
	 * AJAX handler: Run cleanup operation
	 */
	public function ajax_cleanup() {
		check_ajax_referer( 'velocitywp_db_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'velocitywp' ) ) );
		}
		
		$operation = isset( $_POST['operation'] ) ? sanitize_text_field( $_POST['operation'] ) : '';
		$params = isset( $_POST['params'] ) ? $_POST['params'] : array();
		
		$result = 0;
		
		switch ( $operation ) {
			case 'revisions':
				$keep = isset( $params['keep_revisions'] ) ? intval( $params['keep_revisions'] ) : 0;
				$result = $this->delete_post_revisions( $keep );
				break;
			
			case 'auto_drafts':
				$result = $this->delete_auto_drafts();
				break;
			
			case 'trashed_posts':
				$result = $this->empty_trash();
				break;
			
			case 'orphaned_postmeta':
				$result = $this->delete_orphaned_post_meta();
				break;
			
			case 'spam_comments':
				$result = $this->delete_spam_comments();
				break;
			
			case 'trashed_comments':
				$result = $this->delete_trashed_comments();
				break;
			
			case 'pending_comments':
				$days = isset( $params['days'] ) ? intval( $params['days'] ) : 30;
				$result = $this->delete_pending_comments( $days );
				break;
			
			case 'pingbacks_trackbacks':
				$result = $this->delete_pingbacks_trackbacks();
				break;
			
			case 'orphaned_commentmeta':
				$result = $this->delete_orphaned_comment_meta();
				break;
			
			case 'expired_transients':
				$result = $this->delete_expired_transients();
				break;
			
			case 'all_transients':
				$result = $this->delete_all_transients();
				break;
			
			case 'unused_terms':
				$result = $this->delete_unused_terms();
				break;
			
			case 'orphaned_term_relationships':
				$result = $this->delete_orphaned_term_relationships();
				break;
			
			case 'orphaned_termmeta':
				$result = $this->delete_orphaned_term_meta();
				break;
			
			case 'orphaned_usermeta':
				$result = $this->delete_orphaned_user_meta();
				break;
			
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid operation', 'velocitywp' ) ) );
		}
		
		wp_send_json_success( array(
			'operation' => $operation,
			'deleted' => $result,
			'message' => sprintf( __( 'Deleted %d items', 'velocitywp' ), $result ),
		) );
	}

	/**
	 * AJAX handler: Optimize tables
	 */
	public function ajax_optimize_tables() {
		check_ajax_referer( 'velocitywp_db_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'velocitywp' ) ) );
		}
		
		$action = isset( $_POST['table_action'] ) ? sanitize_text_field( $_POST['table_action'] ) : 'optimize';
		$tables = isset( $_POST['tables'] ) ? array_map( 'sanitize_text_field', $_POST['tables'] ) : null;
		
		$results = array();
		
		switch ( $action ) {
			case 'optimize':
				$results = $this->optimize_tables( $tables );
				break;
			
			case 'repair':
				$results = $this->repair_tables( $tables );
				break;
			
			case 'analyze':
				$results = $this->analyze_tables( $tables );
				break;
			
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid table action', 'velocitywp' ) ) );
		}
		
		$success_count = count( array_filter( $results ) );
		$total_count = count( $results );
		
		wp_send_json_success( array(
			'action' => $action,
			'results' => $results,
			'success_count' => $success_count,
			'total_count' => $total_count,
			'message' => sprintf( __( '%s: %d of %d tables completed successfully', 'velocitywp' ), ucfirst( $action ), $success_count, $total_count ),
		) );
	}

	/**
	 * AJAX handler: Get table information
	 */
	public function ajax_get_table_info() {
		check_ajax_referer( 'velocitywp_db_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'velocitywp' ) ) );
		}
		
		$table_info = $this->get_table_info();
		
		wp_send_json_success( $table_info );
	}
}
