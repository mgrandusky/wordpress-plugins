<?php
/**
 * Fonts Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$font_optimizer = new WPSB_Font_Optimizer();
$stats          = $font_optimizer->get_stats();
$detected_fonts = $font_optimizer->detect_google_fonts();

$font_optimization_enabled = ! empty( $options['font_optimization_enabled'] );
$font_display              = ! empty( $options['font_display'] ) ? $options['font_display'] : 'swap';
$local_google_fonts        = ! empty( $options['local_google_fonts'] );
$font_preconnect           = ! empty( $options['font_preconnect'] );
$font_dns_prefetch         = ! empty( $options['font_dns_prefetch'] );
$font_preload_urls         = ! empty( $options['font_preload_urls'] ) ? $options['font_preload_urls'] : '';
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Font Optimization', 'wp-speed-booster' ); ?></h2>

	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Why optimize fonts?', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Web fonts can significantly impact page load time. Proper optimization ensures fonts load quickly without blocking page rendering.', 'wp-speed-booster' ); ?></p>
	</div>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'General Settings', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Font Optimization', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[font_optimization_enabled]" value="1"
						<?php checked( $font_optimization_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable all font optimization features', 'wp-speed-booster' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Font Display Strategy', 'wp-speed-booster' ); ?></th>
			<td>
				<select name="wpsb_options[font_display]">
					<option value="auto" <?php selected( $font_display, 'auto' ); ?>><?php esc_html_e( 'Auto (browser default)', 'wp-speed-booster' ); ?></option>
					<option value="swap" <?php selected( $font_display, 'swap' ); ?>><?php esc_html_e( 'Swap (recommended)', 'wp-speed-booster' ); ?></option>
					<option value="block" <?php selected( $font_display, 'block' ); ?>><?php esc_html_e( 'Block', 'wp-speed-booster' ); ?></option>
					<option value="fallback" <?php selected( $font_display, 'fallback' ); ?>><?php esc_html_e( 'Fallback', 'wp-speed-booster' ); ?></option>
					<option value="optional" <?php selected( $font_display, 'optional' ); ?>><?php esc_html_e( 'Optional', 'wp-speed-booster' ); ?></option>
				</select>
				<p class="description">
					<strong><?php esc_html_e( 'Swap:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Shows fallback font immediately, swaps to web font when loaded (no FOIT)', 'wp-speed-booster' ); ?><br>
					<strong><?php esc_html_e( 'Block:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Brief invisible period, then shows web font', 'wp-speed-booster' ); ?><br>
					<strong><?php esc_html_e( 'Fallback:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Very brief invisible period, then fallback, then swap', 'wp-speed-booster' ); ?><br>
					<strong><?php esc_html_e( 'Optional:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Only use web font if already cached', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Resource Hints', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'DNS Prefetch', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[font_dns_prefetch]" value="1"
						<?php checked( $font_dns_prefetch, 1 ); ?>>
					<?php esc_html_e( 'Add DNS prefetch for font domains', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Resolves DNS early for fonts.googleapis.com and fonts.gstatic.com', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Preconnect', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[font_preconnect]" value="1"
						<?php checked( $font_preconnect, 1 ); ?>>
					<?php esc_html_e( 'Add preconnect for font domains', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Establishes early connection to font servers (stronger than DNS prefetch)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Font Preloading', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Preload Fonts', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[font_preload_urls]" rows="6" class="large-text code"><?php echo esc_textarea( $font_preload_urls ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Enter font URLs to preload (one per line). Preloading critical fonts ensures they load immediately.', 'wp-speed-booster' ); ?><br>
					<strong><?php esc_html_e( 'Example:', 'wp-speed-booster' ); ?></strong> <code>/wp-content/themes/your-theme/fonts/my-font.woff2</code>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Local Google Fonts', 'wp-speed-booster' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Host Google Fonts Locally', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[local_google_fonts]" value="1"
						<?php checked( $local_google_fonts, 1 ); ?>>
					<?php esc_html_e( 'Download and serve Google Fonts from your server', 'wp-speed-booster' ); ?>
				</label>
				<div style="margin-top:10px;padding:10px;background:#f0f6fc;border-left:4px solid #2271b1;">
					<strong><?php esc_html_e( 'Benefits:', 'wp-speed-booster' ); ?></strong><br>
					✅ <?php esc_html_e( 'Eliminates external HTTP requests', 'wp-speed-booster' ); ?><br>
					✅ <?php esc_html_e( 'Better control over caching', 'wp-speed-booster' ); ?><br>
					✅ <?php esc_html_e( 'GDPR compliance (no data sent to Google)', 'wp-speed-booster' ); ?><br>
					✅ <?php esc_html_e( 'Works offline/on intranets', 'wp-speed-booster' ); ?>
				</div>
			</td>
		</tr>
	</table>
</div>

<?php if ( ! empty( $detected_fonts ) ) : ?>
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Detected Google Fonts', 'wp-speed-booster' ); ?></h2>

	<p><?php esc_html_e( 'We found the following Google Fonts loaded on your site:', 'wp-speed-booster' ); ?></p>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Font Family', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Weights', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Handle', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wp-speed-booster' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $detected_fonts as $font ) : ?>
				<?php foreach ( $font['families'] as $family ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $family['name'] ); ?></strong></td>
					<td><?php echo esc_html( implode( ', ', $family['weights'] ) ); ?></td>
					<td><code><?php echo esc_html( $font['handle'] ); ?></code></td>
					<td>
						<?php
						$css_filename = 'google-fonts-' . md5( $font['url'] ) . '.css';
						$upload_dir   = wp_upload_dir();
						$fonts_dir    = $upload_dir['basedir'] . '/wpsb-fonts/';
						$local_css    = $fonts_dir . $css_filename;
						if ( file_exists( $local_css ) ) :
							?>
							<span style="color:#00a32a;">✓ <?php esc_html_e( 'Local', 'wp-speed-booster' ); ?></span>
						<?php else : ?>
							<span style="color:#d63638;">✗ <?php esc_html_e( 'Remote', 'wp-speed-booster' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<button type="button" class="button button-small wpsb-download-font"
							data-url="<?php echo esc_attr( $font['url'] ); ?>"
							data-handle="<?php echo esc_attr( $font['handle'] ); ?>">
							<?php esc_html_e( 'Download Locally', 'wp-speed-booster' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p style="margin-top:15px;">
		<button type="button" class="button button-primary" id="wpsb-download-all-fonts">
			<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download All Fonts', 'wp-speed-booster' ); ?>
		</button>
	</p>
</div>
<?php else : ?>
<div class="wpspeed-tab-section">
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'No Google Fonts detected on your site. If you\'re using Google Fonts, make sure they\'re enqueued properly.', 'wp-speed-booster' ); ?></p>
	</div>
</div>
<?php endif; ?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Local Fonts Statistics', 'wp-speed-booster' ); ?></h2>

	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
		<div style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
			<div style="font-size:36px;font-weight:bold;color:#2271b1;"><?php echo esc_html( $stats['files'] ); ?></div>
			<div><?php esc_html_e( 'Local Font Files', 'wp-speed-booster' ); ?></div>
		</div>

		<div style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
			<div style="font-size:36px;font-weight:bold;color:#00a32a;"><?php echo esc_html( $stats['size_formatted'] ); ?></div>
			<div><?php esc_html_e( 'Total Size', 'wp-speed-booster' ); ?></div>
		</div>
	</div>

	<?php if ( $stats['files'] > 0 ) : ?>
	<p style="margin-top:15px;">
		<button type="button" class="button button-secondary" id="wpsb-clear-local-fonts">
			<?php esc_html_e( 'Clear All Local Fonts', 'wp-speed-booster' ); ?>
		</button>
	</p>
	<?php endif; ?>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Font Subsetting (Advanced)', 'wp-speed-booster' ); ?></h2>

	<div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:4px;">
		<p><strong><?php esc_html_e( 'Tip:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Reduce font file size by including only the characters you need.', 'wp-speed-booster' ); ?></p>
		<p><?php esc_html_e( 'When requesting Google Fonts, add the', 'wp-speed-booster' ); ?> <code>&subset=</code> <?php esc_html_e( 'parameter:', 'wp-speed-booster' ); ?></p>
		<ul style="margin-left:20px;">
			<li><code>&subset=latin</code> - <?php esc_html_e( 'Basic Latin characters (default)', 'wp-speed-booster' ); ?></li>
			<li><code>&subset=latin-ext</code> - <?php esc_html_e( 'Extended Latin (includes accented characters)', 'wp-speed-booster' ); ?></li>
			<li><code>&subset=cyrillic</code> - <?php esc_html_e( 'Cyrillic characters', 'wp-speed-booster' ); ?></li>
			<li><code>&subset=greek</code> - <?php esc_html_e( 'Greek characters', 'wp-speed-booster' ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( 'Example:', 'wp-speed-booster' ); ?></strong><br>
		<code>https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&subset=latin&display=swap</code></p>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Download single font
	$('.wpsb-download-font').on('click', function() {
		var $btn = $(this);
		var url = $btn.data('url');
		var handle = $btn.data('handle');

		$btn.prop('disabled', true).text('<?php esc_html_e( 'Downloading...', 'wp-speed-booster' ); ?>');

		$.post(ajaxurl, {
			action: 'wpsb_download_google_fonts',
			nonce: wpsbAdmin.nonce,
			url: url
		}, function(response) {
			if (response.success) {
				$btn.text('✓ <?php esc_html_e( 'Downloaded', 'wp-speed-booster' ); ?>').css('color', '#00a32a');
				setTimeout(function() {
					location.reload();
				}, 1000);
			} else {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Download Failed', 'wp-speed-booster' ); ?>');
				alert('<?php esc_html_e( 'Error:', 'wp-speed-booster' ); ?> ' + response.data.message);
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Network Error', 'wp-speed-booster' ); ?>');
		});
	});

	// Download all fonts
	$('#wpsb-download-all-fonts').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Download all detected Google Fonts locally?', 'wp-speed-booster' ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;"></span> <?php esc_html_e( 'Downloading...', 'wp-speed-booster' ); ?>');

		var $fontButtons = $('.wpsb-download-font');
		var total = $fontButtons.length;
		var completed = 0;

		$fontButtons.each(function() {
			var $fontBtn = $(this);
			var url = $fontBtn.data('url');

			$.post(ajaxurl, {
				action: 'wpsb_download_google_fonts',
				nonce: wpsbAdmin.nonce,
				url: url
			}, function(response) {
				completed++;
				$btn.html('<span class="spinner is-active" style="float:none;"></span> <?php esc_html_e( 'Downloading...', 'wp-speed-booster' ); ?> (' + completed + '/' + total + ')');

				if (completed === total) {
					$btn.html('✓ <?php esc_html_e( 'All Fonts Downloaded', 'wp-speed-booster' ); ?>');
					setTimeout(function() {
						location.reload();
					}, 1500);
				}
			});
		});
	});

	// Clear local fonts
	$('#wpsb-clear-local-fonts').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Delete all locally hosted fonts? They can be re-downloaded later.', 'wp-speed-booster' ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Clearing...', 'wp-speed-booster' ); ?>');

		$.post(ajaxurl, {
			action: 'wpsb_clear_local_fonts',
			nonce: wpsbAdmin.nonce
		}, function(response) {
			if (response.success) {
				alert(response.data.message);
				location.reload();
			} else {
				alert('<?php esc_html_e( 'Error:', 'wp-speed-booster' ); ?> ' + response.data.message);
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Clear All Local Fonts', 'wp-speed-booster' ); ?>');
			}
		});
	});
});
</script>
