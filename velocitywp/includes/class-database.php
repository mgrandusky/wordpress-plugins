<?php
/**
 * Database Optimization Class
 *
 * Handles database cleaning and optimization
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Database class
 */
class VelocityWP_Database {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register hooks
		add_action( 'velocitywp_auto_db_optimize', array( $this, 'auto_optimize' ) );
	}

	/**
	 * Run automatic optimization based on schedule
	 */
	public function auto_optimize() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['db_auto_optimize'] ) || $options['db_auto_optimize'] === 'disabled' ) {
			return;
		}

		// Run all enabled optimizations
		$this->optimize_database( $options );
	}

	/**
	 * Optimize database based on options
	 *
	 * @param array $options Plugin options.
	 * @return array Results.
	 */
	public function optimize_database( $options = array() ) {
		if ( empty( $options ) ) {
			$options = get_option( 'velocitywp_options', array() );
		}

		$results = array();

		// Clean revisions
		if ( ! empty( $options['db_clean_revisions'] ) ) {
			$results['revisions'] = $this->clean_revisions( $options );
		}

		// Clean auto-drafts
		if ( ! empty( $options['db_clean_autodrafts'] ) ) {
			$results['autodrafts'] = $this->clean_autodrafts();
		}

		// Clean trash
		if ( ! empty( $options['db_clean_trash'] ) ) {
			$results['trash'] = $this->clean_trash();
		}

		// Clean transients
		if ( ! empty( $options['db_clean_transients'] ) ) {
			$results['transients'] = $this->clean_transients();
		}

		// Clean spam comments
		if ( ! empty( $options['db_clean_spam'] ) ) {
			$results['spam'] = $this->clean_spam_comments();
		}

		// Optimize tables
		if ( ! empty( $options['db_optimize_tables'] ) ) {
			$results['tables'] = $this->optimize_tables();
		}

		return $results;
	}

	/**
	 * Clean post revisions
	 *
	 * @param array $options Plugin options.
	 * @return int Number of revisions deleted.
	 */
	private function clean_revisions( $options ) {
		global $wpdb;

		$keep = ! empty( $options['db_revisions_to_keep'] ) ? intval( $options['db_revisions_to_keep'] ) : 3;

		// Get all posts with revisions
		$posts = $wpdb->get_results(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type != 'revision'"
		);

		$deleted = 0;

		foreach ( $posts as $post ) {
			// Get revisions for this post
			$revisions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} 
					WHERE post_parent = %d 
					AND post_type = 'revision' 
					ORDER BY post_date DESC",
					$post->ID
				)
			);

			// Skip if we don't have more revisions than we want to keep
			if ( count( $revisions ) <= $keep ) {
				continue;
			}

			// Delete old revisions (keep the newest ones)
			$revisions_to_delete = array_slice( $revisions, $keep );

			foreach ( $revisions_to_delete as $revision ) {
				if ( wp_delete_post_revision( $revision->ID ) ) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Clean auto-drafts
	 *
	 * @return int Number of auto-drafts deleted.
	 */
	private function clean_autodrafts() {
		global $wpdb;

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->posts} 
				WHERE post_status = 'auto-draft' 
				AND DATE_SUB(NOW(), INTERVAL 7 DAY) > post_date"
			)
		);

		return intval( $deleted );
	}

	/**
	 * Clean trashed posts and comments
	 *
	 * @return array Number of items deleted.
	 */
	private function clean_trash() {
		global $wpdb;

		$deleted = array(
			'posts'    => 0,
			'comments' => 0,
		);

		// Delete trashed posts
		$trashed_posts = $wpdb->get_results(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);

		foreach ( $trashed_posts as $post ) {
			if ( wp_delete_post( $post->ID, true ) ) {
				$deleted['posts']++;
			}
		}

		// Delete trashed comments
		$deleted['comments'] = $wpdb->query(
			"DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
		);

		return $deleted;
	}

	/**
	 * Clean expired transients
	 *
	 * @return int Number of transients deleted.
	 */
	private function clean_transients() {
		global $wpdb;

		$time = time();

		// Clean expired transients
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%'
			)
		);

		// Clean orphaned transient options
		$deleted += $wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_%' 
			AND option_name NOT LIKE '_transient_timeout_%' 
			AND option_name NOT IN (
				SELECT REPLACE(option_name, '_timeout', '') 
				FROM {$wpdb->options} 
				WHERE option_name LIKE '_transient_timeout_%'
			)"
		);

		return intval( $deleted );
	}

	/**
	 * Clean spam comments
	 *
	 * @return int Number of spam comments deleted.
	 */
	private function clean_spam_comments() {
		global $wpdb;

		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);

		return intval( $deleted );
	}

	/**
	 * Optimize database tables
	 *
	 * @return array Optimization results.
	 */
	private function optimize_tables() {
		global $wpdb;

		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
		$optimized = 0;

		foreach ( $tables as $table ) {
			$table_name = $table[0];

			// Only optimize WordPress tables
			if ( strpos( $table_name, $wpdb->prefix ) === 0 ) {
				$wpdb->query( "OPTIMIZE TABLE {$table_name}" );
				$optimized++;
			}
		}

		return $optimized;
	}

	/**
	 * Get database size
	 *
	 * @return array Database size information.
	 */
	public function get_database_size() {
		global $wpdb;

		$size_query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					SUM(data_length + index_length) as size,
					SUM(data_free) as free
				FROM information_schema.TABLES 
				WHERE table_schema = %s 
				AND table_name LIKE %s",
				DB_NAME,
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			)
		);

		$size = 0;
		$free = 0;

		if ( ! empty( $size_query[0] ) ) {
			$size = ! empty( $size_query[0]->size ) ? intval( $size_query[0]->size ) : 0;
			$free = ! empty( $size_query[0]->free ) ? intval( $size_query[0]->free ) : 0;
		}

		return array(
			'size'      => $size,
			'free'      => $free,
			'total'     => $size + $free,
			'formatted' => size_format( $size ),
		);
	}

	/**
	 * Get database statistics
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array(
			'revisions'    => 0,
			'autodrafts'   => 0,
			'trash_posts'  => 0,
			'trash_comments' => 0,
			'spam_comments' => 0,
			'transients'   => 0,
		);

		// Count revisions
		$stats['revisions'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);

		// Count auto-drafts
		$stats['autodrafts'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);

		// Count trashed posts
		$stats['trash_posts'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);

		// Count trashed comments
		$stats['trash_comments'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
		);

		// Count spam comments
		$stats['spam_comments'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);

		// Count transients
		$stats['transients'] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_' ) . '%'
			)
		);

		return $stats;
	}
}
