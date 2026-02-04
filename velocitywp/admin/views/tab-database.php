<?php
/**
 * Database Optimization Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize Database Optimizer
$db_optimizer = new WP_Speed_Booster_Database_Optimizer();
$stats = $db_optimizer->get_stats();
$db_size = $db_optimizer->get_database_size();
$table_info = $db_optimizer->get_table_info();

// Get settings
$db_optimization_enabled = ! empty( $options['db_optimization_enabled'] ) ? 1 : 0;
$keep_revisions = ! empty( $options['keep_revisions'] ) ? intval( $options['keep_revisions'] ) : 0;
$db_optimize_schedule = ! empty( $options['db_optimize_schedule'] ) ? $options['db_optimize_schedule'] : 'weekly';
$db_optimize_operations = ! empty( $options['db_optimize_operations'] ) ? $options['db_optimize_operations'] : array();
$db_optimize_email_report = ! empty( $options['db_optimize_email_report'] ) ? 1 : 0;
?>

<style>
.wpspeed-db-stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.wpspeed-db-stat-box {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 15px;
	text-align: center;
}

.wpspeed-db-stat-box h3 {
	margin: 0 0 10px 0;
	font-size: 14px;
	color: #646970;
}

.wpspeed-db-stat-value {
	font-size: 32px;
	font-weight: bold;
	color: #2271b1;
	margin: 10px 0;
}

.wpspeed-db-stat-label {
	font-size: 12px;
	color: #646970;
}

.wpspeed-cleanup-section {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
	margin: 20px 0;
}

.wpspeed-cleanup-section h3 {
	margin-top: 0;
	border-bottom: 1px solid #c3c4c7;
	padding-bottom: 10px;
}

.wpspeed-cleanup-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 15px 0;
	border-bottom: 1px solid #f0f0f1;
}

.wpspeed-cleanup-item:last-child {
	border-bottom: none;
}

.wpspeed-cleanup-info {
	flex: 1;
}

.wpspeed-cleanup-info h4 {
	margin: 0 0 5px 0;
	font-size: 14px;
}

.wpspeed-cleanup-count {
	color: #d63638;
	font-weight: bold;
}

.wpspeed-cleanup-actions {
	display: flex;
	gap: 10px;
	align-items: center;
}

.wpspeed-table-list {
	width: 100%;
	border-collapse: collapse;
	margin: 20px 0;
}

.wpspeed-table-list th,
.wpspeed-table-list td {
	padding: 10px;
	text-align: left;
	border-bottom: 1px solid #c3c4c7;
}

.wpspeed-table-list th {
	background: #f6f7f7;
	font-weight: 600;
}

.wpspeed-table-list tr:hover {
	background: #f6f7f7;
}

.wpspeed-progress {
	display: none;
	margin: 20px 0;
	padding: 15px;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
}

.wpspeed-progress-bar {
	width: 100%;
	height: 30px;
	background: #f0f0f1;
	border-radius: 4px;
	overflow: hidden;
	margin: 10px 0;
}

.wpspeed-progress-fill {
	height: 100%;
	background: #2271b1;
	transition: width 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-weight: bold;
}

.wpspeed-results {
	display: none;
	margin: 20px 0;
	padding: 15px;
	background: #d7f8e4;
	border-left: 4px solid #00a32a;
	border-radius: 4px;
}

.wpspeed-results h3 {
	margin-top: 0;
	color: #00a32a;
}

.wpspeed-results ul {
	margin: 10px 0;
	padding-left: 20px;
}

.wpspeed-warning {
	background: #fcf9e8;
	border-left: 4px solid #dba617;
	padding: 15px;
	margin: 20px 0;
	border-radius: 4px;
}

.wpspeed-warning strong {
	color: #dba617;
}

.wpspeed-one-click {
	text-align: center;
	padding: 30px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
	border-radius: 4px;
	margin: 20px 0;
}

.wpspeed-one-click h3 {
	color: #fff;
	margin-top: 0;
}

.wpspeed-one-click .button {
	background: #fff;
	color: #667eea;
	border: none;
	padding: 12px 30px;
	font-size: 16px;
	font-weight: bold;
	cursor: pointer;
	border-radius: 4px;
	transition: all 0.3s ease;
}

.wpspeed-one-click .button:hover {
	transform: scale(1.05);
	box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Database Optimization', 'wp-speed-booster' ); ?></h2>
	
	<!-- Backup Warning -->
	<div class="wpspeed-warning">
		<p><strong>⚠️ <?php esc_html_e( 'Important:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Always backup your database before optimization! Database operations cannot be undone.', 'wp-speed-booster' ); ?></p>
		<p><?php esc_html_e( 'Recommended backup plugins:', 'wp-speed-booster' ); ?> UpdraftPlus, BackupBuddy, Duplicator</p>
	</div>
	
	<!-- Database Statistics Dashboard -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Database Statistics', 'wp-speed-booster' ); ?></h3>
		<div class="wpspeed-db-stats-grid">
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Database Size', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo esc_html( $db_size['size_formatted'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php echo esc_html( $table_info['table_count'] ); ?> <?php esc_html_e( 'tables', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Overhead', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo esc_html( $db_size['overhead_formatted'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Space reclaimable', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Revisions', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo number_format( $stats['revisions'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Post revisions', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Auto-Drafts', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo number_format( $stats['auto_drafts'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Auto-draft posts', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Spam Comments', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo number_format( $stats['spam_comments'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Spam comments', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Expired Transients', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo number_format( $stats['expired_transients'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Expired transients', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Orphaned Meta', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo number_format( $stats['orphaned_postmeta'] + $stats['orphaned_commentmeta'] + $stats['orphaned_termmeta'] + $stats['orphaned_usermeta'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Orphaned records', 'wp-speed-booster' ); ?></div>
			</div>
			
			<div class="wpspeed-db-stat-box">
				<h3><?php esc_html_e( 'Trashed Posts', 'wp-speed-booster' ); ?></h3>
				<div class="wpspeed-db-stat-value"><?php echo number_format( $stats['trashed_posts'] ); ?></div>
				<div class="wpspeed-db-stat-label"><?php esc_html_e( 'Trashed posts', 'wp-speed-booster' ); ?></div>
			</div>
		</div>
	</div>
	
	<!-- One-Click Cleanup -->
	<div class="wpspeed-one-click">
		<h3><?php esc_html_e( 'Quick Cleanup', 'wp-speed-booster' ); ?></h3>
		<p><?php esc_html_e( 'Run all safe cleanup operations in one click', 'wp-speed-booster' ); ?></p>
		<button type="button" class="button button-large" id="wpspeed-one-click-cleanup">
			<?php esc_html_e( 'Clean Everything (Safe)', 'wp-speed-booster' ); ?>
		</button>
		<p style="margin-top: 15px; font-size: 12px;"><?php esc_html_e( 'Includes: Expired transients, Auto-drafts, Spam comments, Orphaned meta, Table optimization', 'wp-speed-booster' ); ?></p>
	</div>
	
	<!-- Progress Indicator -->
	<div class="wpspeed-progress" id="wpspeed-progress">
		<h3><?php esc_html_e( 'Optimization in Progress...', 'wp-speed-booster' ); ?></h3>
		<div class="wpspeed-progress-bar">
			<div class="wpspeed-progress-fill" id="wpspeed-progress-fill" style="width: 0%;">0%</div>
		</div>
		<p id="wpspeed-progress-text"><?php esc_html_e( 'Starting...', 'wp-speed-booster' ); ?></p>
	</div>
	
	<!-- Results Display -->
	<div class="wpspeed-results" id="wpspeed-results">
		<h3>✓ <?php esc_html_e( 'Cleanup Complete!', 'wp-speed-booster' ); ?></h3>
		<div id="wpspeed-results-content"></div>
	</div>
	
	<!-- Post Cleanup Section -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Post Cleanup', 'wp-speed-booster' ); ?></h3>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Post Revisions', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove old post revisions to save space', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['revisions'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
				<div style="margin-top: 10px;">
					<label>
						<input type="radio" name="revision_mode" value="all" checked> <?php esc_html_e( 'Delete all', 'wp-speed-booster' ); ?>
					</label>
					<label style="margin-left: 15px;">
						<input type="radio" name="revision_mode" value="keep"> <?php esc_html_e( 'Keep', 'wp-speed-booster' ); ?>
						<input type="number" id="keep-revisions" min="0" max="100" value="<?php echo esc_attr( $keep_revisions ); ?>" style="width: 60px;">
						<?php esc_html_e( 'most recent per post', 'wp-speed-booster' ); ?>
					</label>
				</div>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="revisions">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Auto-Drafts', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove auto-draft posts older than 7 days', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['auto_drafts'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="auto_drafts">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Empty Trash', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Permanently delete trashed posts', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['trashed_posts'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="trashed_posts">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Orphaned Post Meta', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove post meta for deleted posts', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['orphaned_postmeta'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="orphaned_postmeta">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
	</div>
	
	<!-- Comment Cleanup Section -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Comment Cleanup', 'wp-speed-booster' ); ?></h3>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Spam Comments', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove spam comments', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['spam_comments'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="spam_comments">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Trashed Comments', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove trashed comments', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['trashed_comments'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="trashed_comments">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Pending Comments', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove pending comments older than', 'wp-speed-booster' ); ?> 
					<input type="number" id="pending-comment-days" min="1" max="365" value="30" style="width: 60px;"> 
					<?php esc_html_e( 'days', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['pending_comments'] ); ?> <?php esc_html_e( 'pending', 'wp-speed-booster' ); ?></span>
				</p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="pending_comments">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Pingbacks & Trackbacks', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove all pingbacks and trackbacks', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['pingbacks'] + $stats['trackbacks'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="pingbacks_trackbacks">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Orphaned Comment Meta', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove comment meta for deleted comments', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['orphaned_commentmeta'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="orphaned_commentmeta">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
	</div>
	
	<!-- Transient Cleanup Section -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Transient Cleanup', 'wp-speed-booster' ); ?></h3>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Expired Transients', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove expired transient options (safe)', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['expired_transients'] ); ?> <?php esc_html_e( 'expired', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="expired_transients">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete ALL Transients', 'wp-speed-booster' ); ?></h4>
				<p style="color: #d63638;"><?php esc_html_e( '⚠️ Warning: This will delete all transients including active ones. They will be regenerated as needed.', 'wp-speed-booster' ); ?></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-secondary wpspeed-cleanup-btn" data-operation="all_transients" data-confirm="<?php esc_attr_e( 'Are you sure you want to delete ALL transients? This cannot be undone!', 'wp-speed-booster' ); ?>">
					<?php esc_html_e( 'Clean All', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
	</div>
	
	<!-- Term & User Cleanup Section -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Term & User Cleanup', 'wp-speed-booster' ); ?></h3>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Orphaned Term Meta', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove term meta for deleted terms', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['orphaned_termmeta'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="orphaned_termmeta">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
		
		<div class="wpspeed-cleanup-item">
			<div class="wpspeed-cleanup-info">
				<h4><?php esc_html_e( 'Delete Orphaned User Meta', 'wp-speed-booster' ); ?></h4>
				<p><?php esc_html_e( 'Remove user meta for deleted users', 'wp-speed-booster' ); ?> - <span class="wpspeed-cleanup-count"><?php echo number_format( $stats['orphaned_usermeta'] ); ?> <?php esc_html_e( 'found', 'wp-speed-booster' ); ?></span></p>
			</div>
			<div class="wpspeed-cleanup-actions">
				<button type="button" class="button button-primary wpspeed-cleanup-btn" data-operation="orphaned_usermeta">
					<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>
				</button>
			</div>
		</div>
	</div>
	
	<!-- Table Optimization Section -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Table Optimization', 'wp-speed-booster' ); ?></h3>
		
		<div style="margin-bottom: 20px;">
			<button type="button" class="button button-primary" id="wpspeed-optimize-all-tables">
				<?php esc_html_e( 'Optimize All Tables', 'wp-speed-booster' ); ?>
			</button>
			<button type="button" class="button button-secondary" id="wpspeed-repair-all-tables">
				<?php esc_html_e( 'Repair All Tables', 'wp-speed-booster' ); ?>
			</button>
			<button type="button" class="button button-secondary" id="wpspeed-analyze-all-tables">
				<?php esc_html_e( 'Analyze All Tables', 'wp-speed-booster' ); ?>
			</button>
			<span style="margin-left: 15px; color: #d63638; font-weight: bold;">
				<?php echo esc_html( sprintf( __( 'Total overhead: %s', 'wp-speed-booster' ), $table_info['total_overhead'] ) ); ?>
			</span>
		</div>
		
		<table class="wpspeed-table-list">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Table Name', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Engine', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Rows', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Size', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Overhead', 'wp-speed-booster' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $table_info['tables'] as $table ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $table['name'] ); ?></strong></td>
					<td><?php echo esc_html( $table['engine'] ); ?></td>
					<td><?php echo esc_html( $table['rows'] ); ?></td>
					<td><?php echo esc_html( $table['size'] ); ?></td>
					<td><?php echo esc_html( $table['overhead'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr style="background: #f6f7f7; font-weight: bold;">
					<td><?php esc_html_e( 'Total', 'wp-speed-booster' ); ?></td>
					<td colspan="2"><?php echo esc_html( $table_info['table_count'] ); ?> <?php esc_html_e( 'tables', 'wp-speed-booster' ); ?></td>
					<td><?php echo esc_html( $table_info['total_size'] ); ?></td>
					<td><?php echo esc_html( $table_info['total_overhead'] ); ?></td>
				</tr>
			</tfoot>
		</table>
	</div>
	
	<!-- Scheduled Optimization Section -->
	<div class="wpspeed-cleanup-section">
		<h3><?php esc_html_e( 'Scheduled Optimization', 'wp-speed-booster' ); ?></h3>
		
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Scheduled Optimization', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_optimization_enabled]" value="1" <?php checked( 1, $db_optimization_enabled ); ?> />
						<?php esc_html_e( 'Run optimization automatically', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php esc_html_e( 'Schedule Frequency', 'wp-speed-booster' ); ?></th>
				<td>
					<select name="wpsb_options[db_optimize_schedule]">
						<option value="disabled" <?php selected( 'disabled', $db_optimize_schedule ); ?>><?php esc_html_e( 'Disabled', 'wp-speed-booster' ); ?></option>
						<option value="daily" <?php selected( 'daily', $db_optimize_schedule ); ?>><?php esc_html_e( 'Daily', 'wp-speed-booster' ); ?></option>
						<option value="weekly" <?php selected( 'weekly', $db_optimize_schedule ); ?>><?php esc_html_e( 'Weekly', 'wp-speed-booster' ); ?></option>
						<option value="monthly" <?php selected( 'monthly', $db_optimize_schedule ); ?>><?php esc_html_e( 'Monthly', 'wp-speed-booster' ); ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php esc_html_e( 'Operations to Run', 'wp-speed-booster' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="wpsb_options[db_optimize_operations][]" value="revisions" <?php checked( in_array( 'revisions', $db_optimize_operations ) ); ?> />
							<?php esc_html_e( 'Delete revisions (keep', 'wp-speed-booster' ); ?>
							<input type="number" name="wpsb_options[keep_revisions]" value="<?php echo esc_attr( $keep_revisions ); ?>" min="0" max="100" style="width: 60px;">
							<?php esc_html_e( 'per post)', 'wp-speed-booster' ); ?>
						</label><br>
						
						<label>
							<input type="checkbox" name="wpsb_options[db_optimize_operations][]" value="auto_drafts" <?php checked( in_array( 'auto_drafts', $db_optimize_operations ) ); ?> />
							<?php esc_html_e( 'Delete auto-drafts', 'wp-speed-booster' ); ?>
						</label><br>
						
						<label>
							<input type="checkbox" name="wpsb_options[db_optimize_operations][]" value="trashed_posts" <?php checked( in_array( 'trashed_posts', $db_optimize_operations ) ); ?> />
							<?php esc_html_e( 'Empty trash', 'wp-speed-booster' ); ?>
						</label><br>
						
						<label>
							<input type="checkbox" name="wpsb_options[db_optimize_operations][]" value="spam_comments" <?php checked( in_array( 'spam_comments', $db_optimize_operations ) ); ?> />
							<?php esc_html_e( 'Delete spam comments', 'wp-speed-booster' ); ?>
						</label><br>
						
						<label>
							<input type="checkbox" name="wpsb_options[db_optimize_operations][]" value="expired_transients" <?php checked( in_array( 'expired_transients', $db_optimize_operations ) ); ?> />
							<?php esc_html_e( 'Delete expired transients', 'wp-speed-booster' ); ?>
						</label><br>
						
						<label>
							<input type="checkbox" name="wpsb_options[db_optimize_operations][]" value="optimize_tables" <?php checked( in_array( 'optimize_tables', $db_optimize_operations ) ); ?> />
							<?php esc_html_e( 'Optimize tables', 'wp-speed-booster' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php esc_html_e( 'Email Report', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_optimize_email_report]" value="1" <?php checked( 1, $db_optimize_email_report ); ?> />
						<?php esc_html_e( 'Send email report after optimization', 'wp-speed-booster' ); ?>
					</label>
					<p class="description"><?php echo sprintf( esc_html__( 'Reports will be sent to: %s', 'wp-speed-booster' ), get_option( 'admin_email' ) ); ?></p>
				</td>
			</tr>
		</table>
		
		<?php
		$next_scheduled = wp_next_scheduled( 'wpspeed_database_optimization' );
		if ( $next_scheduled ) {
			echo '<p><strong>' . esc_html__( 'Next scheduled run:', 'wp-speed-booster' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) ) . '</p>';
		}
		?>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var nonce = '<?php echo wp_create_nonce( 'wpspeed_db_nonce' ); ?>';
	
	// Cleanup button click handler
	$('.wpspeed-cleanup-btn').on('click', function() {
		var $btn = $(this);
		var operation = $btn.data('operation');
		var confirmMsg = $btn.data('confirm');
		
		// Confirm if needed
		if (confirmMsg && !confirm(confirmMsg)) {
			return;
		}
		
		// Check for specific confirmation
		if (operation === 'trashed_posts' || operation === 'all_transients') {
			if (!confirm('<?php esc_html_e( 'Are you sure? This action cannot be undone!', 'wp-speed-booster' ); ?>')) {
				return;
			}
		}
		
		// Get parameters
		var params = {};
		if (operation === 'revisions') {
			var mode = $('input[name="revision_mode"]:checked').val();
			params.keep_revisions = mode === 'keep' ? $('#keep-revisions').val() : 0;
		} else if (operation === 'pending_comments') {
			params.days = $('#pending-comment-days').val();
		}
		
		// Disable button and show loading
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Cleaning...', 'wp-speed-booster' ); ?>');
		
		// Run cleanup
		$.post(ajaxurl, {
			action: 'wpspeed_db_cleanup',
			nonce: nonce,
			operation: operation,
			params: params
		}, function(response) {
			if (response.success) {
				alert('<?php esc_html_e( 'Success!', 'wp-speed-booster' ); ?> ' + response.data.message);
				// Reload to update stats
				location.reload();
			} else {
				alert('<?php esc_html_e( 'Error:', 'wp-speed-booster' ); ?> ' + response.data.message);
			}
		}).fail(function() {
			alert('<?php esc_html_e( 'An error occurred. Please try again.', 'wp-speed-booster' ); ?>');
		}).always(function() {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Clean', 'wp-speed-booster' ); ?>');
		});
	});
	
	// Table optimization handlers
	$('#wpspeed-optimize-all-tables').on('click', function() {
		runTableOperation('optimize', '<?php esc_html_e( 'Optimizing tables...', 'wp-speed-booster' ); ?>');
	});
	
	$('#wpspeed-repair-all-tables').on('click', function() {
		if (confirm('<?php esc_html_e( 'Repair tables? This should only be done if tables are corrupted.', 'wp-speed-booster' ); ?>')) {
			runTableOperation('repair', '<?php esc_html_e( 'Repairing tables...', 'wp-speed-booster' ); ?>');
		}
	});
	
	$('#wpspeed-analyze-all-tables').on('click', function() {
		runTableOperation('analyze', '<?php esc_html_e( 'Analyzing tables...', 'wp-speed-booster' ); ?>');
	});
	
	function runTableOperation(action, message) {
		$('#wpspeed-progress').show();
		$('#wpspeed-progress-text').text(message);
		$('#wpspeed-progress-fill').css('width', '50%').text('50%');
		
		$.post(ajaxurl, {
			action: 'wpspeed_db_optimize_tables',
			nonce: nonce,
			table_action: action
		}, function(response) {
			if (response.success) {
				$('#wpspeed-progress-fill').css('width', '100%').text('100%');
				setTimeout(function() {
					$('#wpspeed-progress').hide();
					alert(response.data.message);
					location.reload();
				}, 500);
			} else {
				$('#wpspeed-progress').hide();
				alert('<?php esc_html_e( 'Error:', 'wp-speed-booster' ); ?> ' + response.data.message);
			}
		}).fail(function() {
			$('#wpspeed-progress').hide();
			alert('<?php esc_html_e( 'An error occurred. Please try again.', 'wp-speed-booster' ); ?>');
		});
	}
	
	// One-click cleanup
	$('#wpspeed-one-click-cleanup').on('click', function() {
		if (!confirm('<?php esc_html_e( 'This will run all safe cleanup operations. Continue?', 'wp-speed-booster' ); ?>')) {
			return;
		}
		
		var operations = ['expired_transients', 'auto_drafts', 'spam_comments', 'orphaned_postmeta', 'orphaned_commentmeta'];
		var total = operations.length + 1; // +1 for table optimization
		var completed = 0;
		var results = [];
		
		$('#wpspeed-progress').show();
		$('#wpspeed-results').hide();
		
		function runNextOperation(index) {
			if (index >= operations.length) {
				// Run table optimization
				completed++;
				var progress = Math.round((completed / total) * 100);
				$('#wpspeed-progress-fill').css('width', progress + '%').text(progress + '%');
				$('#wpspeed-progress-text').text('<?php esc_html_e( 'Optimizing tables...', 'wp-speed-booster' ); ?>');
				
				$.post(ajaxurl, {
					action: 'wpspeed_db_optimize_tables',
					nonce: nonce,
					table_action: 'optimize'
				}, function(response) {
					if (response.success) {
						results.push('Optimized ' + response.data.success_count + ' tables');
					}
					showResults();
				});
				return;
			}
			
			var operation = operations[index];
			completed++;
			var progress = Math.round((completed / total) * 100);
			$('#wpspeed-progress-fill').css('width', progress + '%').text(progress + '%');
			$('#wpspeed-progress-text').text('<?php esc_html_e( 'Cleaning', 'wp-speed-booster' ); ?> ' + operation + '...');
			
			$.post(ajaxurl, {
				action: 'wpspeed_db_cleanup',
				nonce: nonce,
				operation: operation,
				params: {}
			}, function(response) {
				if (response.success) {
					results.push(response.data.message);
				}
				runNextOperation(index + 1);
			}).fail(function() {
				runNextOperation(index + 1);
			});
		}
		
		function showResults() {
			$('#wpspeed-progress').hide();
			var html = '<ul>';
			results.forEach(function(result) {
				html += '<li>' + result + '</li>';
			});
			html += '</ul>';
			$('#wpspeed-results-content').html(html);
			$('#wpspeed-results').show();
			
			setTimeout(function() {
				location.reload();
			}, 3000);
		}
		
		runNextOperation(0);
	});
});
</script>
