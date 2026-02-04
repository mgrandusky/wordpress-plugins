<?php
/**
 * Cloudflare Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get Cloudflare settings
$cf_enabled = ! empty( $options['cloudflare_enabled'] ) ? 1 : 0;
$cf_auth_type = ! empty( $options['cloudflare_auth_type'] ) ? $options['cloudflare_auth_type'] : 'token';
$cf_api_token = ! empty( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';
$cf_email = ! empty( $options['cloudflare_email'] ) ? $options['cloudflare_email'] : '';
$cf_api_key = ! empty( $options['cloudflare_api_key'] ) ? $options['cloudflare_api_key'] : '';
$cf_zone_id = ! empty( $options['cloudflare_zone_id'] ) ? $options['cloudflare_zone_id'] : '';
$cf_purge_on_update = ! empty( $options['cloudflare_purge_on_update'] ) ? 1 : 0;
$cf_purge_on_comment = ! empty( $options['cloudflare_purge_on_comment'] ) ? 1 : 0;
$cf_restore_ip = ! empty( $options['cloudflare_restore_ip'] ) ? 1 : 0;
$cf_last_purge = get_option( 'wpsb_cf_last_purge', 0 );
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Cloudflare Integration', 'wp-speed-booster' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Integrate with Cloudflare CDN for advanced caching, security, and performance optimization.', 'wp-speed-booster' ); ?>
	</p>

	<!-- Enable Cloudflare -->
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Cloudflare Integration', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cloudflare_enabled]" value="1" <?php checked( $cf_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable Cloudflare API integration', 'wp-speed-booster' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Connect to Cloudflare to manage cache purging and settings directly from WordPress.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- Connection Status -->
	<?php if ( $cf_enabled && ! empty( $cf_zone_id ) ) : ?>
		<div class="wpsb-cf-status notice notice-success inline">
			<p>
				<span class="dashicons dashicons-yes-alt"></span>
				<strong><?php esc_html_e( 'Connected to Cloudflare', 'wp-speed-booster' ); ?></strong>
				<?php if ( ! empty( $cf_zone_id ) ) : ?>
					<br><small><?php echo esc_html( sprintf( __( 'Zone ID: %s', 'wp-speed-booster' ), $cf_zone_id ) ); ?></small>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- API Authentication -->
	<h3><?php esc_html_e( 'API Authentication', 'wp-speed-booster' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Authentication Type', 'wp-speed-booster' ); ?></th>
			<td>
				<fieldset>
					<label>
						<input type="radio" name="wpsb_options[cloudflare_auth_type]" value="token" <?php checked( $cf_auth_type, 'token' ); ?>>
						<?php esc_html_e( 'API Token (Recommended)', 'wp-speed-booster' ); ?>
					</label><br>
					<label>
						<input type="radio" name="wpsb_options[cloudflare_auth_type]" value="key" <?php checked( $cf_auth_type, 'key' ); ?>>
						<?php esc_html_e( 'Global API Key + Email', 'wp-speed-booster' ); ?>
					</label>
				</fieldset>
				<p class="description">
					<?php esc_html_e( 'API Token is more secure as it can have limited permissions.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>

		<!-- API Token -->
		<tr class="wpsb-cf-token-field" style="<?php echo $cf_auth_type === 'key' ? 'display:none;' : ''; ?>">
			<th scope="row">
				<label for="cf-api-token"><?php esc_html_e( 'API Token', 'wp-speed-booster' ); ?></label>
			</th>
			<td>
				<input type="password" id="cf-api-token" name="wpsb_options[cloudflare_api_token]" 
					value="<?php echo esc_attr( $cf_api_token ); ?>" class="regular-text" autocomplete="off">
				<p class="description">
					<?php
					printf(
						/* translators: %s: Link to Cloudflare API tokens page */
						esc_html__( 'Get your API Token from %s. Create a token with "Zone.Cache Purge" and "Zone.Zone Settings:Read" permissions.', 'wp-speed-booster' ),
						'<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare Dashboard</a>'
					);
					?>
				</p>
			</td>
		</tr>

		<!-- API Key + Email -->
		<tr class="wpsb-cf-key-field" style="<?php echo $cf_auth_type === 'token' ? 'display:none;' : ''; ?>">
			<th scope="row">
				<label for="cf-email"><?php esc_html_e( 'Cloudflare Email', 'wp-speed-booster' ); ?></label>
			</th>
			<td>
				<input type="email" id="cf-email" name="wpsb_options[cloudflare_email]" 
					value="<?php echo esc_attr( $cf_email ); ?>" class="regular-text">
				<p class="description"><?php esc_html_e( 'The email address associated with your Cloudflare account.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>

		<tr class="wpsb-cf-key-field" style="<?php echo $cf_auth_type === 'token' ? 'display:none;' : ''; ?>">
			<th scope="row">
				<label for="cf-api-key"><?php esc_html_e( 'Global API Key', 'wp-speed-booster' ); ?></label>
			</th>
			<td>
				<input type="password" id="cf-api-key" name="wpsb_options[cloudflare_api_key]" 
					value="<?php echo esc_attr( $cf_api_key ); ?>" class="regular-text" autocomplete="off">
				<p class="description">
					<?php
					printf(
						/* translators: %s: Link to Cloudflare API keys page */
						esc_html__( 'Find your Global API Key in %s', 'wp-speed-booster' ),
						'<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare Dashboard</a>'
					);
					?>
				</p>
			</td>
		</tr>

		<!-- Zone ID -->
		<tr>
			<th scope="row">
				<label for="cf-zone-id"><?php esc_html_e( 'Zone ID', 'wp-speed-booster' ); ?></label>
			</th>
			<td>
				<input type="text" id="cf-zone-id" name="wpsb_options[cloudflare_zone_id]" 
					value="<?php echo esc_attr( $cf_zone_id ); ?>" class="regular-text">
				<button type="button" id="wpsb-cf-get-zones" class="button button-secondary">
					<?php esc_html_e( 'Auto-Detect Zones', 'wp-speed-booster' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'Your Cloudflare Zone ID. Click "Auto-Detect" to fetch available zones.', 'wp-speed-booster' ); ?>
				</p>
				<div id="wpsb-cf-zones-list" style="display:none; margin-top:10px;">
					<select id="wpsb-cf-zone-select" class="regular-text">
						<option value=""><?php esc_html_e( 'Select a zone...', 'wp-speed-booster' ); ?></option>
					</select>
				</div>
			</td>
		</tr>

		<!-- Test Connection -->
		<tr>
			<th scope="row"><?php esc_html_e( 'Connection Test', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" id="wpsb-cf-test-connection" class="button button-secondary">
					<?php esc_html_e( 'Test Connection', 'wp-speed-booster' ); ?>
				</button>
				<span id="wpsb-cf-test-result"></span>
				<p class="description"><?php esc_html_e( 'Verify that your API credentials are working.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>

	<!-- Cache Management -->
	<h3><?php esc_html_e( 'Cache Management', 'wp-speed-booster' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Manual Cache Purge', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" id="wpsb-cf-purge-everything" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Purge Everything', 'wp-speed-booster' ); ?>
				</button>
				<?php if ( $cf_last_purge > 0 ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: Time since last purge */
							esc_html__( 'Last purged: %s ago', 'wp-speed-booster' ),
							esc_html( human_time_diff( $cf_last_purge, current_time( 'timestamp' ) ) )
						);
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Purge Specific URLs', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea id="wpsb-cf-purge-urls" rows="5" class="large-text" placeholder="https://example.com/page1&#10;https://example.com/page2"></textarea>
				<br>
				<button type="button" id="wpsb-cf-purge-urls-btn" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Purge URLs', 'wp-speed-booster' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Enter one URL per line to purge specific pages.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Purge by Cache Tags', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea id="wpsb-cf-purge-tags" rows="3" class="large-text" placeholder="tag1&#10;tag2"></textarea>
				<br>
				<button type="button" id="wpsb-cf-purge-tags-btn" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Purge Tags', 'wp-speed-booster' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Enter cache tags (one per line). Requires Cloudflare Enterprise.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>

	<!-- Automatic Purge Settings -->
	<h3><?php esc_html_e( 'Automatic Cache Purging', 'wp-speed-booster' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Purge on Post Update', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cloudflare_purge_on_update]" value="1" <?php checked( $cf_purge_on_update, 1 ); ?>>
					<?php esc_html_e( 'Automatically purge cache when posts are published or updated', 'wp-speed-booster' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Purges: Post URL, homepage, post archive, category archives, tag archives, and author archive.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Purge on Comment', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cloudflare_purge_on_comment]" value="1" <?php checked( $cf_purge_on_comment, 1 ); ?>>
					<?php esc_html_e( 'Automatically purge cache when comments are approved', 'wp-speed-booster' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Purges the post URL and homepage when comments are approved.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- Development Mode -->
	<h3><?php esc_html_e( 'Development Mode', 'wp-speed-booster' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Development Mode', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" id="wpsb-cf-enable-dev-mode" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Enable for 3 Hours', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" id="wpsb-cf-disable-dev-mode" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Disable Now', 'wp-speed-booster' ); ?>
				</button>
				<span id="wpsb-cf-dev-mode-status"></span>
				<p class="description">
					<?php esc_html_e( 'Bypass Cloudflare cache for testing. Automatically disables after 3 hours.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- Advanced Settings -->
	<h3><?php esc_html_e( 'Advanced Settings', 'wp-speed-booster' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Restore Visitor IP', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[cloudflare_restore_ip]" value="1" <?php checked( $cf_restore_ip, 1 ); ?>>
					<?php esc_html_e( 'Restore real visitor IP from Cloudflare headers', 'wp-speed-booster' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Uses CF-Connecting-IP header to get real visitor IP addresses.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- APO Information -->
	<div class="notice notice-info inline" style="margin-top:20px;">
		<h3><?php esc_html_e( 'Cloudflare APO (Automatic Platform Optimization)', 'wp-speed-booster' ); ?></h3>
		<p>
			<?php esc_html_e( 'Cloudflare APO provides full HTML caching at the edge for WordPress sites. It can significantly improve performance for visitors worldwide.', 'wp-speed-booster' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Pricing:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( '$5/month per domain', 'wp-speed-booster' ); ?><br>
			<strong><?php esc_html_e( 'Benefits:', 'wp-speed-booster' ); ?></strong>
		</p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Full HTML caching at 200+ Cloudflare edge locations', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Smart cache bypass for logged-in users and WooCommerce carts', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Automatic cache invalidation on content updates', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( '90%+ cache hit ratio typical', 'wp-speed-booster' ); ?></li>
		</ul>
		<p>
			<a href="https://www.cloudflare.com/automatic-platform-optimization/" target="_blank" class="button button-primary">
				<?php esc_html_e( 'Learn More About APO', 'wp-speed-booster' ); ?>
			</a>
			<a href="https://dash.cloudflare.com/" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Enable in Cloudflare Dashboard', 'wp-speed-booster' ); ?>
			</a>
		</p>
	</div>
</div>

<style>
.wpsb-cf-status {
	padding: 10px 15px;
	margin: 15px 0;
}
.wpsb-cf-status .dashicons {
	color: #46b450;
	margin-right: 5px;
}
#wpsb-cf-test-result,
#wpsb-cf-dev-mode-status {
	margin-left: 10px;
	font-weight: bold;
}
#wpsb-cf-test-result.success,
#wpsb-cf-dev-mode-status.success {
	color: #46b450;
}
#wpsb-cf-test-result.error,
#wpsb-cf-dev-mode-status.error {
	color: #dc3232;
}
.wpsb-cf-loading {
	display: inline-block;
	width: 16px;
	height: 16px;
	border: 2px solid #f3f3f3;
	border-top: 2px solid #3498db;
	border-radius: 50%;
	animation: spin 1s linear infinite;
	vertical-align: middle;
	margin-left: 10px;
}
@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
	'use strict';

	// Toggle auth fields
	$('input[name="wpsb_options[cloudflare_auth_type]"]').on('change', function() {
		var authType = $(this).val();
		if (authType === 'token') {
			$('.wpsb-cf-token-field').show();
			$('.wpsb-cf-key-field').hide();
		} else {
			$('.wpsb-cf-token-field').hide();
			$('.wpsb-cf-key-field').show();
		}
	});

	// Test connection
	$('#wpsb-cf-test-connection').on('click', function() {
		var $btn = $(this);
		var $result = $('#wpsb-cf-test-result');
		var authType = $('input[name="wpsb_options[cloudflare_auth_type]"]:checked').val();
		var apiToken = $('#cf-api-token').val();
		var email = $('#cf-email').val();
		var apiKey = $('#cf-api-key').val();

		$btn.prop('disabled', true);
		$result.html('<span class="wpsb-cf-loading"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_test_connection',
				nonce: wpsbAdmin.nonce,
				auth_type: authType,
				api_token: apiToken,
				email: email,
				api_key: apiKey
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span class="dashicons dashicons-yes-alt success"></span> ' + response.data.message).addClass('success').removeClass('error');
				} else {
					$result.html('<span class="dashicons dashicons-dismiss error"></span> ' + response.data.message).addClass('error').removeClass('success');
				}
			},
			error: function() {
				$result.html('<span class="dashicons dashicons-dismiss error"></span> Connection failed').addClass('error').removeClass('success');
			},
			complete: function() {
				$btn.prop('disabled', false);
			}
		});
	});

	// Get zones
	$('#wpsb-cf-get-zones').on('click', function() {
		var $btn = $(this);
		var $zonesList = $('#wpsb-cf-zones-list');
		var $zoneSelect = $('#wpsb-cf-zone-select');

		$btn.prop('disabled', true).text('<?php esc_html_e( 'Loading...', 'wp-speed-booster' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_get_zones',
				nonce: wpsbAdmin.nonce
			},
			success: function(response) {
				if (response.success && response.data.result) {
					$zoneSelect.empty().append('<option value=""><?php esc_html_e( 'Select a zone...', 'wp-speed-booster' ); ?></option>');
					
					$.each(response.data.result, function(i, zone) {
						$zoneSelect.append(
							$('<option></option>')
								.val(zone.id)
								.text(zone.name + ' (' + zone.status + ')')
						);
					});
					
					$zonesList.show();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Failed to fetch zones', 'wp-speed-booster' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Failed to fetch zones', 'wp-speed-booster' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Auto-Detect Zones', 'wp-speed-booster' ); ?>');
			}
		});
	});

	// Select zone
	$('#wpsb-cf-zone-select').on('change', function() {
		$('#cf-zone-id').val($(this).val());
	});

	// Purge everything
	$('#wpsb-cf-purge-everything').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to purge the entire Cloudflare cache?', 'wp-speed-booster' ); ?>')) {
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Purging...', 'wp-speed-booster' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_purge_cache',
				nonce: wpsbAdmin.nonce,
				type: 'everything'
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Cache purged successfully!', 'wp-speed-booster' ); ?>');
					location.reload();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Purge failed', 'wp-speed-booster' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Purge failed', 'wp-speed-booster' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Purge Everything', 'wp-speed-booster' ); ?>');
			}
		});
	});

	// Purge URLs
	$('#wpsb-cf-purge-urls-btn').on('click', function() {
		var urls = $('#wpsb-cf-purge-urls').val().trim();
		if (!urls) {
			alert('<?php esc_html_e( 'Please enter at least one URL', 'wp-speed-booster' ); ?>');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Purging...', 'wp-speed-booster' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_purge_cache',
				nonce: wpsbAdmin.nonce,
				type: 'urls',
				urls: urls
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'URLs purged successfully!', 'wp-speed-booster' ); ?>');
					$('#wpsb-cf-purge-urls').val('');
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Purge failed', 'wp-speed-booster' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Purge failed', 'wp-speed-booster' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Purge URLs', 'wp-speed-booster' ); ?>');
			}
		});
	});

	// Purge tags
	$('#wpsb-cf-purge-tags-btn').on('click', function() {
		var tags = $('#wpsb-cf-purge-tags').val().trim();
		if (!tags) {
			alert('<?php esc_html_e( 'Please enter at least one tag', 'wp-speed-booster' ); ?>');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Purging...', 'wp-speed-booster' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_purge_cache',
				nonce: wpsbAdmin.nonce,
				type: 'tags',
				tags: tags
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Tags purged successfully!', 'wp-speed-booster' ); ?>');
					$('#wpsb-cf-purge-tags').val('');
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Purge failed', 'wp-speed-booster' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Purge failed', 'wp-speed-booster' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Purge Tags', 'wp-speed-booster' ); ?>');
			}
		});
	});

	// Enable development mode
	$('#wpsb-cf-enable-dev-mode').on('click', function() {
		var $btn = $(this);
		var $status = $('#wpsb-cf-dev-mode-status');

		$btn.prop('disabled', true);
		$status.html('<span class="wpsb-cf-loading"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_toggle_dev_mode',
				nonce: wpsbAdmin.nonce,
				action_type: 'enable'
			},
			success: function(response) {
				if (response.success) {
					$status.html('<span class="dashicons dashicons-yes-alt success"></span> <?php esc_html_e( 'Enabled for 3 hours', 'wp-speed-booster' ); ?>').addClass('success').removeClass('error');
				} else {
					$status.html('<span class="dashicons dashicons-dismiss error"></span> ' + response.data.message).addClass('error').removeClass('success');
				}
			},
			error: function() {
				$status.html('<span class="dashicons dashicons-dismiss error"></span> <?php esc_html_e( 'Failed', 'wp-speed-booster' ); ?>').addClass('error').removeClass('success');
			},
			complete: function() {
				$btn.prop('disabled', false);
			}
		});
	});

	// Disable development mode
	$('#wpsb-cf-disable-dev-mode').on('click', function() {
		var $btn = $(this);
		var $status = $('#wpsb-cf-dev-mode-status');

		$btn.prop('disabled', true);
		$status.html('<span class="wpsb-cf-loading"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpsb_cf_toggle_dev_mode',
				nonce: wpsbAdmin.nonce,
				action_type: 'disable'
			},
			success: function(response) {
				if (response.success) {
					$status.html('<span class="dashicons dashicons-yes-alt success"></span> <?php esc_html_e( 'Disabled', 'wp-speed-booster' ); ?>').addClass('success').removeClass('error');
				} else {
					$status.html('<span class="dashicons dashicons-dismiss error"></span> ' + response.data.message).addClass('error').removeClass('success');
				}
			},
			error: function() {
				$status.html('<span class="dashicons dashicons-dismiss error"></span> <?php esc_html_e( 'Failed', 'wp-speed-booster' ); ?>').addClass('error').removeClass('success');
			},
			complete: function() {
				$btn.prop('disabled', false);
			}
		});
	});
});
</script>
