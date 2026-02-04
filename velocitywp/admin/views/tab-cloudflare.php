<?php
/**
 * Cloudflare Tab View
 *
 * @package VelocityWP
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
$cf_last_purge = get_option( 'velocitywp_cf_last_purge', 0 );
?>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Cloudflare Integration', 'velocitywp' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Integrate with Cloudflare CDN for advanced caching, security, and performance optimization.', 'velocitywp' ); ?>
	</p>

	<!-- Enable Cloudflare -->
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Cloudflare Integration', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cloudflare_enabled]" value="1" <?php checked( $cf_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable Cloudflare API integration', 'velocitywp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Connect to Cloudflare to manage cache purging and settings directly from WordPress.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- Connection Status -->
	<?php if ( $cf_enabled && ! empty( $cf_zone_id ) ) : ?>
		<div class="velocitywp-cf-status notice notice-success inline">
			<p>
				<span class="dashicons dashicons-yes-alt"></span>
				<strong><?php esc_html_e( 'Connected to Cloudflare', 'velocitywp' ); ?></strong>
				<?php if ( ! empty( $cf_zone_id ) ) : ?>
					<br><small><?php echo esc_html( sprintf( __( 'Zone ID: %s', 'velocitywp' ), $cf_zone_id ) ); ?></small>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- API Authentication -->
	<h3><?php esc_html_e( 'API Authentication', 'velocitywp' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Authentication Type', 'velocitywp' ); ?></th>
			<td>
				<fieldset>
					<label>
						<input type="radio" name="velocitywp_options[cloudflare_auth_type]" value="token" <?php checked( $cf_auth_type, 'token' ); ?>>
						<?php esc_html_e( 'API Token (Recommended)', 'velocitywp' ); ?>
					</label><br>
					<label>
						<input type="radio" name="velocitywp_options[cloudflare_auth_type]" value="key" <?php checked( $cf_auth_type, 'key' ); ?>>
						<?php esc_html_e( 'Global API Key + Email', 'velocitywp' ); ?>
					</label>
				</fieldset>
				<p class="description">
					<?php esc_html_e( 'API Token is more secure as it can have limited permissions.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>

		<!-- API Token -->
		<tr class="velocitywp-cf-token-field" style="<?php echo $cf_auth_type === 'key' ? 'display:none;' : ''; ?>">
			<th scope="row">
				<label for="cf-api-token"><?php esc_html_e( 'API Token', 'velocitywp' ); ?></label>
			</th>
			<td>
				<input type="password" id="cf-api-token" name="velocitywp_options[cloudflare_api_token]" 
					value="<?php echo esc_attr( $cf_api_token ); ?>" class="regular-text" autocomplete="off">
				<p class="description">
					<?php
					printf(
						/* translators: %s: Link to Cloudflare API tokens page */
						esc_html__( 'Get your API Token from %s. Create a token with "Zone.Cache Purge" and "Zone.Zone Settings:Read" permissions.', 'velocitywp' ),
						'<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare Dashboard</a>'
					);
					?>
				</p>
			</td>
		</tr>

		<!-- API Key + Email -->
		<tr class="velocitywp-cf-key-field" style="<?php echo $cf_auth_type === 'token' ? 'display:none;' : ''; ?>">
			<th scope="row">
				<label for="cf-email"><?php esc_html_e( 'Cloudflare Email', 'velocitywp' ); ?></label>
			</th>
			<td>
				<input type="email" id="cf-email" name="velocitywp_options[cloudflare_email]" 
					value="<?php echo esc_attr( $cf_email ); ?>" class="regular-text">
				<p class="description"><?php esc_html_e( 'The email address associated with your Cloudflare account.', 'velocitywp' ); ?></p>
			</td>
		</tr>

		<tr class="velocitywp-cf-key-field" style="<?php echo $cf_auth_type === 'token' ? 'display:none;' : ''; ?>">
			<th scope="row">
				<label for="cf-api-key"><?php esc_html_e( 'Global API Key', 'velocitywp' ); ?></label>
			</th>
			<td>
				<input type="password" id="cf-api-key" name="velocitywp_options[cloudflare_api_key]" 
					value="<?php echo esc_attr( $cf_api_key ); ?>" class="regular-text" autocomplete="off">
				<p class="description">
					<?php
					printf(
						/* translators: %s: Link to Cloudflare API keys page */
						esc_html__( 'Find your Global API Key in %s', 'velocitywp' ),
						'<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare Dashboard</a>'
					);
					?>
				</p>
			</td>
		</tr>

		<!-- Zone ID -->
		<tr>
			<th scope="row">
				<label for="cf-zone-id"><?php esc_html_e( 'Zone ID', 'velocitywp' ); ?></label>
			</th>
			<td>
				<input type="text" id="cf-zone-id" name="velocitywp_options[cloudflare_zone_id]" 
					value="<?php echo esc_attr( $cf_zone_id ); ?>" class="regular-text">
				<button type="button" id="velocitywp-cf-get-zones" class="button button-secondary">
					<?php esc_html_e( 'Auto-Detect Zones', 'velocitywp' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'Your Cloudflare Zone ID. Click "Auto-Detect" to fetch available zones.', 'velocitywp' ); ?>
				</p>
				<div id="velocitywp-cf-zones-list" style="display:none; margin-top:10px;">
					<select id="velocitywp-cf-zone-select" class="regular-text">
						<option value=""><?php esc_html_e( 'Select a zone...', 'velocitywp' ); ?></option>
					</select>
				</div>
			</td>
		</tr>

		<!-- Test Connection -->
		<tr>
			<th scope="row"><?php esc_html_e( 'Connection Test', 'velocitywp' ); ?></th>
			<td>
				<button type="button" id="velocitywp-cf-test-connection" class="button button-secondary">
					<?php esc_html_e( 'Test Connection', 'velocitywp' ); ?>
				</button>
				<span id="velocitywp-cf-test-result"></span>
				<p class="description"><?php esc_html_e( 'Verify that your API credentials are working.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>

	<!-- Cache Management -->
	<h3><?php esc_html_e( 'Cache Management', 'velocitywp' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Manual Cache Purge', 'velocitywp' ); ?></th>
			<td>
				<button type="button" id="velocitywp-cf-purge-everything" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Purge Everything', 'velocitywp' ); ?>
				</button>
				<?php if ( $cf_last_purge > 0 ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: Time since last purge */
							esc_html__( 'Last purged: %s ago', 'velocitywp' ),
							esc_html( human_time_diff( $cf_last_purge, current_time( 'timestamp' ) ) )
						);
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wpsb-cf-purge-urls"><?php esc_html_e( 'Purge Specific URLs', 'velocitywp' ); ?></label>
			</th>
			<td>
				<textarea id="velocitywp-cf-purge-urls" rows="5" class="large-text" placeholder="https://example.com/page1&#10;https://example.com/page2" aria-describedby="wpsb-cf-purge-urls-desc"></textarea>
				<br>
				<button type="button" id="velocitywp-cf-purge-urls-btn" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Purge URLs', 'velocitywp' ); ?>
				</button>
				<p class="description" id="velocitywp-cf-purge-urls-desc"><?php esc_html_e( 'Enter one URL per line to purge specific pages.', 'velocitywp' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wpsb-cf-purge-tags"><?php esc_html_e( 'Purge by Cache Tags', 'velocitywp' ); ?></label>
			</th>
			<td>
				<textarea id="velocitywp-cf-purge-tags" rows="3" class="large-text" placeholder="tag1&#10;tag2" aria-describedby="wpsb-cf-purge-tags-desc"></textarea>
				<br>
				<button type="button" id="velocitywp-cf-purge-tags-btn" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Purge Tags', 'velocitywp' ); ?>
				</button>
				<p class="description" id="velocitywp-cf-purge-tags-desc"><?php esc_html_e( 'Enter cache tags (one per line). Requires Cloudflare Enterprise.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>

	<!-- Automatic Purge Settings -->
	<h3><?php esc_html_e( 'Automatic Cache Purging', 'velocitywp' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Purge on Post Update', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cloudflare_purge_on_update]" value="1" <?php checked( $cf_purge_on_update, 1 ); ?>>
					<?php esc_html_e( 'Automatically purge cache when posts are published or updated', 'velocitywp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Purges: Post URL, homepage, post archive, category archives, tag archives, and author archive.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Purge on Comment', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cloudflare_purge_on_comment]" value="1" <?php checked( $cf_purge_on_comment, 1 ); ?>>
					<?php esc_html_e( 'Automatically purge cache when comments are approved', 'velocitywp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Purges the post URL and homepage when comments are approved.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- Development Mode -->
	<h3><?php esc_html_e( 'Development Mode', 'velocitywp' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Development Mode', 'velocitywp' ); ?></th>
			<td>
				<button type="button" id="velocitywp-cf-enable-dev-mode" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Enable for 3 Hours', 'velocitywp' ); ?>
				</button>
				<button type="button" id="velocitywp-cf-disable-dev-mode" class="button button-secondary" <?php disabled( empty( $cf_zone_id ) ); ?>>
					<?php esc_html_e( 'Disable Now', 'velocitywp' ); ?>
				</button>
				<span id="velocitywp-cf-dev-mode-status"></span>
				<p class="description">
					<?php esc_html_e( 'Bypass Cloudflare cache for testing. Automatically disables after 3 hours.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- Advanced Settings -->
	<h3><?php esc_html_e( 'Advanced Settings', 'velocitywp' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Restore Visitor IP', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[cloudflare_restore_ip]" value="1" <?php checked( $cf_restore_ip, 1 ); ?>>
					<?php esc_html_e( 'Restore real visitor IP from Cloudflare headers', 'velocitywp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Uses CF-Connecting-IP header to get real visitor IP addresses.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<!-- APO Information -->
	<div class="notice notice-info inline" style="margin-top:20px;">
		<h3><?php esc_html_e( 'Cloudflare APO (Automatic Platform Optimization)', 'velocitywp' ); ?></h3>
		<p>
			<?php esc_html_e( 'Cloudflare APO provides full HTML caching at the edge for WordPress sites. It can significantly improve performance for visitors worldwide.', 'velocitywp' ); ?>
		</p>
		<p>
			<?php
			// Note: Pricing verified as of 2024. Check Cloudflare website for current pricing.
			?>
			<strong><?php esc_html_e( 'Pricing:', 'velocitywp' ); ?></strong> <?php esc_html_e( '$5/month per domain', 'velocitywp' ); ?><br>
			<strong><?php esc_html_e( 'Benefits:', 'velocitywp' ); ?></strong>
		</p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Full HTML caching at 200+ Cloudflare edge locations', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Smart cache bypass for logged-in users and WooCommerce carts', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Automatic cache invalidation on content updates', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( '90%+ cache hit ratio typical', 'velocitywp' ); ?></li>
		</ul>
		<p>
			<a href="https://www.cloudflare.com/automatic-platform-optimization/" target="_blank" class="button button-primary">
				<?php esc_html_e( 'Learn More About APO', 'velocitywp' ); ?>
			</a>
			<a href="https://dash.cloudflare.com/" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Enable in Cloudflare Dashboard', 'velocitywp' ); ?>
			</a>
		</p>
	</div>
</div>

<style>
.velocitywp-cf-status {
	padding: 10px 15px;
	margin: 15px 0;
}
.velocitywp-cf-status .dashicons {
	color: #46b450;
	margin-right: 5px;
}
#velocitywp-cf-test-result,
#velocitywp-cf-dev-mode-status {
	margin-left: 10px;
	font-weight: bold;
}
#velocitywp-cf-test-result.success,
#velocitywp-cf-dev-mode-status.success {
	color: #46b450;
}
#velocitywp-cf-test-result.error,
#velocitywp-cf-dev-mode-status.error {
	color: #dc3232;
}
.velocitywp-cf-loading {
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
	$('input[name="velocitywp_options[cloudflare_auth_type]"]').on('change', function() {
		var authType = $(this).val();
		if (authType === 'token') {
			$('.velocitywp-cf-token-field').show();
			$('.velocitywp-cf-key-field').hide();
		} else {
			$('.velocitywp-cf-token-field').hide();
			$('.velocitywp-cf-key-field').show();
		}
	});

	// Test connection
	$('#velocitywp-cf-test-connection').on('click', function() {
		var $btn = $(this);
		var $result = $('#velocitywp-cf-test-result');
		var authType = $('input[name="velocitywp_options[cloudflare_auth_type]"]:checked').val();
		var apiToken = $('#cf-api-token').val();
		var email = $('#cf-email').val();
		var apiKey = $('#cf-api-key').val();

		$btn.prop('disabled', true);
		$result.html('<span class="velocitywp-cf-loading"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_test_connection',
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
	$('#velocitywp-cf-get-zones').on('click', function() {
		var $btn = $(this);
		var $zonesList = $('#velocitywp-cf-zones-list');
		var $zoneSelect = $('#velocitywp-cf-zone-select');

		$btn.prop('disabled', true).text('<?php esc_html_e( 'Loading...', 'velocitywp' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_get_zones',
				nonce: wpsbAdmin.nonce
			},
			success: function(response) {
				if (response.success && response.data.result) {
					$zoneSelect.empty().append('<option value=""><?php esc_html_e( 'Select a zone...', 'velocitywp' ); ?></option>');
					
					$.each(response.data.result, function(i, zone) {
						$zoneSelect.append(
							$('<option></option>')
								.val(zone.id)
								.text(zone.name + ' (' + zone.status + ')')
						);
					});
					
					$zonesList.show();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Failed to fetch zones', 'velocitywp' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Failed to fetch zones', 'velocitywp' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Auto-Detect Zones', 'velocitywp' ); ?>');
			}
		});
	});

	// Select zone
	$('#velocitywp-cf-zone-select').on('change', function() {
		$('#cf-zone-id').val($(this).val());
	});

	// Purge everything
	$('#velocitywp-cf-purge-everything').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to purge the entire Cloudflare cache?', 'velocitywp' ); ?>')) {
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Purging...', 'velocitywp' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_purge_cache',
				nonce: wpsbAdmin.nonce,
				type: 'everything'
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Cache purged successfully!', 'velocitywp' ); ?>');
					location.reload();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Purge failed', 'velocitywp' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Purge failed', 'velocitywp' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Purge Everything', 'velocitywp' ); ?>');
			}
		});
	});

	// Purge URLs
	$('#velocitywp-cf-purge-urls-btn').on('click', function() {
		var urls = $('#velocitywp-cf-purge-urls').val().trim();
		if (!urls) {
			alert('<?php esc_html_e( 'Please enter at least one URL', 'velocitywp' ); ?>');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Purging...', 'velocitywp' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_purge_cache',
				nonce: wpsbAdmin.nonce,
				type: 'urls',
				urls: urls
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'URLs purged successfully!', 'velocitywp' ); ?>');
					$('#velocitywp-cf-purge-urls').val('');
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Purge failed', 'velocitywp' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Purge failed', 'velocitywp' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Purge URLs', 'velocitywp' ); ?>');
			}
		});
	});

	// Purge tags
	$('#velocitywp-cf-purge-tags-btn').on('click', function() {
		var tags = $('#velocitywp-cf-purge-tags').val().trim();
		if (!tags) {
			alert('<?php esc_html_e( 'Please enter at least one tag', 'velocitywp' ); ?>');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Purging...', 'velocitywp' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_purge_cache',
				nonce: wpsbAdmin.nonce,
				type: 'tags',
				tags: tags
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Tags purged successfully!', 'velocitywp' ); ?>');
					$('#velocitywp-cf-purge-tags').val('');
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Purge failed', 'velocitywp' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Purge failed', 'velocitywp' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Purge Tags', 'velocitywp' ); ?>');
			}
		});
	});

	// Enable development mode
	$('#velocitywp-cf-enable-dev-mode').on('click', function() {
		var $btn = $(this);
		var $status = $('#velocitywp-cf-dev-mode-status');

		$btn.prop('disabled', true);
		$status.html('<span class="velocitywp-cf-loading"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_toggle_dev_mode',
				nonce: wpsbAdmin.nonce,
				action_type: 'enable'
			},
			success: function(response) {
				if (response.success) {
					$status.html('<span class="dashicons dashicons-yes-alt success"></span> <?php esc_html_e( 'Enabled for 3 hours', 'velocitywp' ); ?>').addClass('success').removeClass('error');
				} else {
					$status.html('<span class="dashicons dashicons-dismiss error"></span> ' + response.data.message).addClass('error').removeClass('success');
				}
			},
			error: function() {
				$status.html('<span class="dashicons dashicons-dismiss error"></span> <?php esc_html_e( 'Failed', 'velocitywp' ); ?>').addClass('error').removeClass('success');
			},
			complete: function() {
				$btn.prop('disabled', false);
			}
		});
	});

	// Disable development mode
	$('#velocitywp-cf-disable-dev-mode').on('click', function() {
		var $btn = $(this);
		var $status = $('#velocitywp-cf-dev-mode-status');

		$btn.prop('disabled', true);
		$status.html('<span class="velocitywp-cf-loading"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'velocitywp_cf_toggle_dev_mode',
				nonce: wpsbAdmin.nonce,
				action_type: 'disable'
			},
			success: function(response) {
				if (response.success) {
					$status.html('<span class="dashicons dashicons-yes-alt success"></span> <?php esc_html_e( 'Disabled', 'velocitywp' ); ?>').addClass('success').removeClass('error');
				} else {
					$status.html('<span class="dashicons dashicons-dismiss error"></span> ' + response.data.message).addClass('error').removeClass('success');
				}
			},
			error: function() {
				$status.html('<span class="dashicons dashicons-dismiss error"></span> <?php esc_html_e( 'Failed', 'velocitywp' ); ?>').addClass('error').removeClass('success');
			},
			complete: function() {
				$btn.prop('disabled', false);
			}
		});
	});
});
</script>
