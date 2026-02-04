<?php
/**
 * Heartbeat Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heartbeat = new WPSB_Heartbeat();
$stats     = $heartbeat->get_stats();

// Get current settings
$heartbeat_control_enabled      = ! empty( $options['heartbeat_control_enabled'] );
$heartbeat_disable_completely   = ! empty( $options['heartbeat_disable_completely'] );
$heartbeat_disable_frontend     = ! empty( $options['heartbeat_disable_frontend'] );
$heartbeat_disable_admin        = ! empty( $options['heartbeat_disable_admin'] );
$heartbeat_disable_editor       = ! empty( $options['heartbeat_disable_editor'] );
$heartbeat_frontend_frequency   = ! empty( $options['heartbeat_frontend_frequency'] ) ? intval( $options['heartbeat_frontend_frequency'] ) : 60;
$heartbeat_admin_frequency      = ! empty( $options['heartbeat_admin_frequency'] ) ? intval( $options['heartbeat_admin_frequency'] ) : 60;
$heartbeat_editor_frequency     = ! empty( $options['heartbeat_editor_frequency'] ) ? intval( $options['heartbeat_editor_frequency'] ) : 15;
$heartbeat_allow_post_locking   = ! empty( $options['heartbeat_allow_post_locking'] ) ? 1 : 0;
$heartbeat_allow_autosave       = ! empty( $options['heartbeat_allow_autosave'] ) ? 1 : 0;
$heartbeat_track_activity       = ! empty( $options['heartbeat_track_activity'] );

// Calculate stats
$total_requests = ! empty( $stats['total_requests'] ) ? intval( $stats['total_requests'] ) : 0;
$last_request   = ! empty( $stats['last_request'] ) ? $stats['last_request'] : null;
$breakdown      = ! empty( $stats['location_breakdown'] ) ? $stats['location_breakdown'] : array();
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'WordPress Heartbeat Control', 'wp-speed-booster' ); ?></h2>

	<div class="notice notice-info">
		<h3><?php esc_html_e( 'What is WordPress Heartbeat?', 'wp-speed-booster' ); ?></h3>
		<p><?php esc_html_e( 'The WordPress Heartbeat API makes automatic AJAX requests to the server every 15 seconds by default. It\'s used for:', 'wp-speed-booster' ); ?></p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Post locking (prevents multiple users from editing the same post)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Autosave functionality in the post editor', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Admin notifications and updates', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Plugin and theme update notifications', 'wp-speed-booster' ); ?></li>
		</ul>
		
		<h4><?php esc_html_e( 'Why Control It?', 'wp-speed-booster' ); ?></h4>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><strong><?php esc_html_e( 'Reduces server load:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Each request = 1 HTTP call to admin-ajax.php', 'wp-speed-booster' ); ?></li>
			<li><strong><?php esc_html_e( 'Saves bandwidth:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Default settings = 5,760 requests per day per user', 'wp-speed-booster' ); ?></li>
			<li><strong><?php esc_html_e( 'Better hosting performance:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Especially important on shared hosting', 'wp-speed-booster' ); ?></li>
			<li><strong><?php esc_html_e( 'Prevents timeouts:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Reduces resource limit issues', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Global Settings', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Heartbeat Control', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_control_enabled]" value="1"
						<?php checked( $heartbeat_control_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable heartbeat control features', 'wp-speed-booster' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Disable Completely', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_disable_completely]" value="1"
						<?php checked( $heartbeat_disable_completely, 1 ); ?>>
					<?php esc_html_e( 'Disable heartbeat completely everywhere', 'wp-speed-booster' ); ?>
				</label>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Warning:', 'wp-speed-booster' ); ?></strong> 
					<?php esc_html_e( 'This will disable autosave and post locking! Only use if you understand the implications. Location-specific control below is recommended instead.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Location-Based Settings', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-success">
		<p><strong><?php esc_html_e( '✓ Recommended Configuration:', 'wp-speed-booster' ); ?></strong></p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Frontend: Disabled (rarely needed)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Admin Dashboard: 60 seconds', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Post Editor: 15-30 seconds (needed for autosave)', 'wp-speed-booster' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'This provides 70-80% reduction in Heartbeat requests while maintaining essential functionality.', 'wp-speed-booster' ); ?></p>
	</div>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Frontend', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_disable_frontend]" value="1"
						<?php checked( $heartbeat_disable_frontend, 1 ); ?>>
					<?php esc_html_e( 'Disable on frontend', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Recommended: Heartbeat is rarely needed on the frontend.', 'wp-speed-booster' ); ?></p>
				
				<?php if ( ! $heartbeat_disable_frontend ) : ?>
				<p>
					<label>
						<?php esc_html_e( 'Frequency (seconds):', 'wp-speed-booster' ); ?>
						<input type="number" name="wpsb_options[heartbeat_frontend_frequency]" 
							value="<?php echo esc_attr( $heartbeat_frontend_frequency ); ?>" 
							min="15" max="300" step="5" style="width: 80px;">
					</label>
					<span class="description"><?php esc_html_e( '(15-300 seconds, default: 60)', 'wp-speed-booster' ); ?></span>
				</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Admin Dashboard', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_disable_admin]" value="1"
						<?php checked( $heartbeat_disable_admin, 1 ); ?>>
					<?php esc_html_e( 'Disable in admin (except post editor)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'May affect admin notifications but generally safe to disable or reduce.', 'wp-speed-booster' ); ?></p>
				
				<?php if ( ! $heartbeat_disable_admin ) : ?>
				<p>
					<label>
						<?php esc_html_e( 'Frequency (seconds):', 'wp-speed-booster' ); ?>
						<input type="number" name="wpsb_options[heartbeat_admin_frequency]" 
							value="<?php echo esc_attr( $heartbeat_admin_frequency ); ?>" 
							min="15" max="300" step="5" style="width: 80px;">
					</label>
					<span class="description"><?php esc_html_e( '(15-300 seconds, default: 60)', 'wp-speed-booster' ); ?></span>
				</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Post Editor', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_disable_editor]" value="1"
						<?php checked( $heartbeat_disable_editor, 1 ); ?>>
					<?php esc_html_e( 'Disable in post editor', 'wp-speed-booster' ); ?>
				</label>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Not recommended:', 'wp-speed-booster' ); ?></strong> 
					<?php esc_html_e( 'Disabling will break autosave and post locking features.', 'wp-speed-booster' ); ?>
				</p>
				
				<?php if ( ! $heartbeat_disable_editor ) : ?>
				<p>
					<label>
						<?php esc_html_e( 'Frequency (seconds):', 'wp-speed-booster' ); ?>
						<input type="number" name="wpsb_options[heartbeat_editor_frequency]" 
							value="<?php echo esc_attr( $heartbeat_editor_frequency ); ?>" 
							min="15" max="300" step="5" style="width: 80px;">
					</label>
					<span class="description"><?php esc_html_e( '(15-300 seconds, default: 15)', 'wp-speed-booster' ); ?></span>
				</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Feature Control', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Allowed Features', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_allow_post_locking]" value="1"
						<?php checked( $heartbeat_allow_post_locking, 1 ); ?>>
					<?php esc_html_e( 'Allow post locking', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Prevents simultaneous editing of the same post by multiple users. Recommended to keep enabled.', 'wp-speed-booster' ); ?></p>
				
				<br>
				
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_allow_autosave]" value="1"
						<?php checked( $heartbeat_allow_autosave, 1 ); ?>>
					<?php esc_html_e( 'Allow autosave', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Automatically saves drafts while editing. Recommended to keep enabled to prevent content loss.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Statistics & Monitoring', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Track Activity', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[heartbeat_track_activity]" value="1"
						<?php checked( $heartbeat_track_activity, 1 ); ?>>
					<?php esc_html_e( 'Track heartbeat requests', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to see statistics about heartbeat usage.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>

	<?php if ( $heartbeat_track_activity && $total_requests > 0 ) : ?>
	<div class="wpspeed-stats-box" style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px;">
		<h3><?php esc_html_e( 'Heartbeat Statistics', 'wp-speed-booster' ); ?></h3>
		
		<table class="widefat" style="margin-top: 10px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Total Requests:', 'wp-speed-booster' ); ?></strong></td>
					<td><?php echo number_format( $total_requests ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Last Request:', 'wp-speed-booster' ); ?></strong></td>
					<td>
						<?php
						if ( $last_request ) {
							echo esc_html( human_time_diff( strtotime( $last_request ), current_time( 'timestamp' ) ) . ' ago' );
						} else {
							esc_html_e( 'Never', 'wp-speed-booster' );
						}
						?>
					</td>
				</tr>
				<?php if ( ! empty( $breakdown ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Location Breakdown:', 'wp-speed-booster' ); ?></strong></td>
					<td>
						<?php
						foreach ( $breakdown as $location => $count ) {
							$percentage = ( $count / $total_requests ) * 100;
							echo esc_html( ucfirst( $location ) . ': ' . number_format( $count ) . ' (' . round( $percentage, 1 ) . '%)' ) . '<br>';
						}
						?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<p style="margin-top: 15px;">
			<button type="button" class="button" id="wpspeed-reset-heartbeat-stats">
				<?php esc_html_e( 'Reset Statistics', 'wp-speed-booster' ); ?>
			</button>
		</p>
	</div>
	<?php endif; ?>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Common Scenarios', 'wp-speed-booster' ); ?></h2>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
		<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
			<h3><?php esc_html_e( 'Blog / Magazine', 'wp-speed-booster' ); ?></h3>
			<ul style="list-style-type: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Frontend: Disabled', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Admin: 60 seconds', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Editor: 15 seconds', 'wp-speed-booster' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'Impact:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( '~85% reduction', 'wp-speed-booster' ); ?></p>
		</div>

		<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
			<h3><?php esc_html_e( 'E-commerce (WooCommerce)', 'wp-speed-booster' ); ?></h3>
			<ul style="list-style-type: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Frontend: Disabled', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Admin: 60 seconds', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Editor: 30 seconds', 'wp-speed-booster' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'Impact:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( '~75% reduction', 'wp-speed-booster' ); ?></p>
		</div>

		<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
			<h3><?php esc_html_e( 'Multi-author Site', 'wp-speed-booster' ); ?></h3>
			<ul style="list-style-type: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Frontend: Disabled', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Admin: 60 seconds', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Editor: 15 seconds (for post locking)', 'wp-speed-booster' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'Impact:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( '~70% reduction', 'wp-speed-booster' ); ?></p>
		</div>
	</div>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Frequently Asked Questions', 'wp-speed-booster' ); ?></h2>

	<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
		<h4><?php esc_html_e( 'Q: Will this break my site?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: No. The recommended settings preserve essential features like autosave and post locking while reducing unnecessary requests.', 'wp-speed-booster' ); ?></p>

		<h4><?php esc_html_e( 'Q: What happens if I disable it completely?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: You lose autosave and post locking features. This is not recommended. Use location-specific control instead.', 'wp-speed-booster' ); ?></p>

		<h4><?php esc_html_e( 'Q: Can I disable on frontend only?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Yes! This is the safest and most recommended option. Heartbeat is rarely needed on the frontend.', 'wp-speed-booster' ); ?></p>

		<h4><?php esc_html_e( 'Q: Will this affect real-time notifications?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Admin notifications may be delayed by the frequency setting, but they will still work.', 'wp-speed-booster' ); ?></p>

		<h4><?php esc_html_e( 'Q: Does this work with plugins?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Yes, but some plugins may use Heartbeat for their own features. Test carefully after making changes.', 'wp-speed-booster' ); ?></p>

		<h4><?php esc_html_e( 'Q: How much will this improve performance?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: With recommended settings, you can reduce Heartbeat requests by 70-80%, significantly lowering server load and bandwidth usage.', 'wp-speed-booster' ); ?></p>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Reset statistics
	$('#wpspeed-reset-heartbeat-stats').on('click', function(e) {
		e.preventDefault();
		
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reset heartbeat statistics?', 'wp-speed-booster' ) ); ?>')) {
			return;
		}
		
		$.post(ajaxurl, {
			action: 'wpspeed_heartbeat_reset_stats',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb_nonce' ) ); ?>'
		}, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('<?php echo esc_js( __( 'Error resetting statistics', 'wp-speed-booster' ) ); ?>');
			}
		});
	});
});
</script>
