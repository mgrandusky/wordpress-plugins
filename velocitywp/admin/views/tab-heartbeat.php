<?php
/**
 * Heartbeat Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heartbeat = new VelocityWP_Heartbeat();
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

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'WordPress Heartbeat Control', 'velocitywp' ); ?></h2>

	<div class="notice notice-info">
		<h3><?php esc_html_e( 'What is WordPress Heartbeat?', 'velocitywp' ); ?></h3>
		<p><?php esc_html_e( 'The WordPress Heartbeat API makes automatic AJAX requests to the server every 15 seconds by default. It\'s used for:', 'velocitywp' ); ?></p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Post locking (prevents multiple users from editing the same post)', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Autosave functionality in the post editor', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Admin notifications and updates', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Plugin and theme update notifications', 'velocitywp' ); ?></li>
		</ul>
		
		<h4><?php esc_html_e( 'Why Control It?', 'velocitywp' ); ?></h4>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><strong><?php esc_html_e( 'Reduces server load:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Each request = 1 HTTP call to admin-ajax.php', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Saves bandwidth:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Default settings = 5,760 requests per day per user', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Better hosting performance:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Especially important on shared hosting', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Prevents timeouts:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Reduces resource limit issues', 'velocitywp' ); ?></li>
		</ul>
	</div>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Global Settings', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Heartbeat Control', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_control_enabled]" value="1"
						<?php checked( $heartbeat_control_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable heartbeat control features', 'velocitywp' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Disable Completely', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_disable_completely]" value="1"
						<?php checked( $heartbeat_disable_completely, 1 ); ?>>
					<?php esc_html_e( 'Disable heartbeat completely everywhere', 'velocitywp' ); ?>
				</label>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Warning:', 'velocitywp' ); ?></strong> 
					<?php esc_html_e( 'This will disable autosave and post locking! Only use if you understand the implications. Location-specific control below is recommended instead.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Location-Based Settings', 'velocitywp' ); ?></h2>
	
	<div class="notice notice-success">
		<p><strong><?php esc_html_e( '✓ Recommended Configuration:', 'velocitywp' ); ?></strong></p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Frontend: Disabled (rarely needed)', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Admin Dashboard: 60 seconds', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Post Editor: 15-30 seconds (needed for autosave)', 'velocitywp' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'This provides 70-80% reduction in Heartbeat requests while maintaining essential functionality.', 'velocitywp' ); ?></p>
	</div>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Frontend', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_disable_frontend]" value="1"
						<?php checked( $heartbeat_disable_frontend, 1 ); ?>>
					<?php esc_html_e( 'Disable on frontend', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Recommended: Heartbeat is rarely needed on the frontend.', 'velocitywp' ); ?></p>
				
				<?php if ( ! $heartbeat_disable_frontend ) : ?>
				<p>
					<label>
						<?php esc_html_e( 'Frequency (seconds):', 'velocitywp' ); ?>
						<input type="number" name="velocitywp_options[heartbeat_frontend_frequency]" 
							value="<?php echo esc_attr( $heartbeat_frontend_frequency ); ?>" 
							min="15" max="300" step="5" style="width: 80px;">
					</label>
					<span class="description"><?php esc_html_e( '(15-300 seconds, default: 60)', 'velocitywp' ); ?></span>
				</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Admin Dashboard', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_disable_admin]" value="1"
						<?php checked( $heartbeat_disable_admin, 1 ); ?>>
					<?php esc_html_e( 'Disable in admin (except post editor)', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'May affect admin notifications but generally safe to disable or reduce.', 'velocitywp' ); ?></p>
				
				<?php if ( ! $heartbeat_disable_admin ) : ?>
				<p>
					<label>
						<?php esc_html_e( 'Frequency (seconds):', 'velocitywp' ); ?>
						<input type="number" name="velocitywp_options[heartbeat_admin_frequency]" 
							value="<?php echo esc_attr( $heartbeat_admin_frequency ); ?>" 
							min="15" max="300" step="5" style="width: 80px;">
					</label>
					<span class="description"><?php esc_html_e( '(15-300 seconds, default: 60)', 'velocitywp' ); ?></span>
				</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Post Editor', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_disable_editor]" value="1"
						<?php checked( $heartbeat_disable_editor, 1 ); ?>>
					<?php esc_html_e( 'Disable in post editor', 'velocitywp' ); ?>
				</label>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Not recommended:', 'velocitywp' ); ?></strong> 
					<?php esc_html_e( 'Disabling will break autosave and post locking features.', 'velocitywp' ); ?>
				</p>
				
				<?php if ( ! $heartbeat_disable_editor ) : ?>
				<p>
					<label>
						<?php esc_html_e( 'Frequency (seconds):', 'velocitywp' ); ?>
						<input type="number" name="velocitywp_options[heartbeat_editor_frequency]" 
							value="<?php echo esc_attr( $heartbeat_editor_frequency ); ?>" 
							min="15" max="300" step="5" style="width: 80px;">
					</label>
					<span class="description"><?php esc_html_e( '(15-300 seconds, default: 15)', 'velocitywp' ); ?></span>
				</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Feature Control', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Allowed Features', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_allow_post_locking]" value="1"
						<?php checked( $heartbeat_allow_post_locking, 1 ); ?>>
					<?php esc_html_e( 'Allow post locking', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Prevents simultaneous editing of the same post by multiple users. Recommended to keep enabled.', 'velocitywp' ); ?></p>
				
				<br>
				
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_allow_autosave]" value="1"
						<?php checked( $heartbeat_allow_autosave, 1 ); ?>>
					<?php esc_html_e( 'Allow autosave', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Automatically saves drafts while editing. Recommended to keep enabled to prevent content loss.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Statistics & Monitoring', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Track Activity', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[heartbeat_track_activity]" value="1"
						<?php checked( $heartbeat_track_activity, 1 ); ?>>
					<?php esc_html_e( 'Track heartbeat requests', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to see statistics about heartbeat usage.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>

	<?php if ( $heartbeat_track_activity && $total_requests > 0 ) : ?>
	<div class="velocitywp-stats-box" style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px;">
		<h3><?php esc_html_e( 'Heartbeat Statistics', 'velocitywp' ); ?></h3>
		
		<table class="widefat" style="margin-top: 10px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Total Requests:', 'velocitywp' ); ?></strong></td>
					<td><?php echo number_format( $total_requests ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Last Request:', 'velocitywp' ); ?></strong></td>
					<td>
						<?php
						if ( $last_request ) {
							$time_ago = human_time_diff( strtotime( $last_request ), current_time( 'timestamp' ) );
							/* translators: %s: time difference */
							echo esc_html( sprintf( __( '%s ago', 'velocitywp' ), $time_ago ) );
						} else {
							esc_html_e( 'Never', 'velocitywp' );
						}
						?>
					</td>
				</tr>
				<?php if ( ! empty( $breakdown ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Location Breakdown:', 'velocitywp' ); ?></strong></td>
					<td>
						<?php
						foreach ( $breakdown as $location => $count ) {
							$percentage = ( $count / $total_requests ) * 100;
							/* translators: 1: location name, 2: count, 3: percentage */
							printf(
								'%s: %s (%s%%)<br>',
								esc_html( ucfirst( $location ) ),
								esc_html( number_format( $count ) ),
								esc_html( round( $percentage, 1 ) )
							);
						}
						?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<p style="margin-top: 15px;">
			<button type="button" class="button" id="velocitywp-reset-heartbeat-stats">
				<?php esc_html_e( 'Reset Statistics', 'velocitywp' ); ?>
			</button>
		</p>
	</div>
	<?php endif; ?>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Common Scenarios', 'velocitywp' ); ?></h2>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
		<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
			<h3><?php esc_html_e( 'Blog / Magazine', 'velocitywp' ); ?></h3>
			<ul style="list-style-type: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Frontend: Disabled', 'velocitywp' ); ?></li>
				<li><?php esc_html_e( 'Admin: 60 seconds', 'velocitywp' ); ?></li>
				<li><?php esc_html_e( 'Editor: 15 seconds', 'velocitywp' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'Impact:', 'velocitywp' ); ?></strong> <?php esc_html_e( '~85% reduction', 'velocitywp' ); ?></p>
		</div>

		<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
			<h3><?php esc_html_e( 'E-commerce (WooCommerce)', 'velocitywp' ); ?></h3>
			<ul style="list-style-type: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Frontend: Disabled', 'velocitywp' ); ?></li>
				<li><?php esc_html_e( 'Admin: 60 seconds', 'velocitywp' ); ?></li>
				<li><?php esc_html_e( 'Editor: 30 seconds', 'velocitywp' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'Impact:', 'velocitywp' ); ?></strong> <?php esc_html_e( '~75% reduction', 'velocitywp' ); ?></p>
		</div>

		<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
			<h3><?php esc_html_e( 'Multi-author Site', 'velocitywp' ); ?></h3>
			<ul style="list-style-type: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Frontend: Disabled', 'velocitywp' ); ?></li>
				<li><?php esc_html_e( 'Admin: 60 seconds', 'velocitywp' ); ?></li>
				<li><?php esc_html_e( 'Editor: 15 seconds (for post locking)', 'velocitywp' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'Impact:', 'velocitywp' ); ?></strong> <?php esc_html_e( '~70% reduction', 'velocitywp' ); ?></p>
		</div>
	</div>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Frequently Asked Questions', 'velocitywp' ); ?></h2>

	<div style="background: #fff; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
		<h4><?php esc_html_e( 'Q: Will this break my site?', 'velocitywp' ); ?></h4>
		<p><?php esc_html_e( 'A: No. The recommended settings preserve essential features like autosave and post locking while reducing unnecessary requests.', 'velocitywp' ); ?></p>

		<h4><?php esc_html_e( 'Q: What happens if I disable it completely?', 'velocitywp' ); ?></h4>
		<p><?php esc_html_e( 'A: You lose autosave and post locking features. This is not recommended. Use location-specific control instead.', 'velocitywp' ); ?></p>

		<h4><?php esc_html_e( 'Q: Can I disable on frontend only?', 'velocitywp' ); ?></h4>
		<p><?php esc_html_e( 'A: Yes! This is the safest and most recommended option. Heartbeat is rarely needed on the frontend.', 'velocitywp' ); ?></p>

		<h4><?php esc_html_e( 'Q: Will this affect real-time notifications?', 'velocitywp' ); ?></h4>
		<p><?php esc_html_e( 'A: Admin notifications may be delayed by the frequency setting, but they will still work.', 'velocitywp' ); ?></p>

		<h4><?php esc_html_e( 'Q: Does this work with plugins?', 'velocitywp' ); ?></h4>
		<p><?php esc_html_e( 'A: Yes, but some plugins may use Heartbeat for their own features. Test carefully after making changes.', 'velocitywp' ); ?></p>

		<h4><?php esc_html_e( 'Q: How much will this improve performance?', 'velocitywp' ); ?></h4>
		<p><?php esc_html_e( 'A: With recommended settings, you can reduce Heartbeat requests by 70-80%, significantly lowering server load and bandwidth usage.', 'velocitywp' ); ?></p>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Reset statistics
	$('#velocitywp-reset-heartbeat-stats').on('click', function(e) {
		e.preventDefault();
		
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reset heartbeat statistics?', 'velocitywp' ) ); ?>')) {
			return;
		}
		
		$.post(ajaxurl, {
			action: 'velocitywp_heartbeat_reset_stats',
			nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp_nonce' ) ); ?>'
		}, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('<?php echo esc_js( __( 'Error resetting statistics', 'velocitywp' ) ); ?>');
			}
		});
	});
});
</script>
