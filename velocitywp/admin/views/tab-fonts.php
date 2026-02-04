<?php
/**
 * Fonts Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$font_optimizer = new VelocityWP_Font_Optimizer();
$stats          = $font_optimizer->get_stats();
$detected_fonts = $font_optimizer->detect_google_fonts();

$font_optimization_enabled = ! empty( $options['font_optimization_enabled'] );
$font_display              = ! empty( $options['font_display'] ) ? $options['font_display'] : 'swap';
$local_google_fonts        = ! empty( $options['local_google_fonts'] );
$font_preconnect           = ! empty( $options['font_preconnect'] );
$font_dns_prefetch         = ! empty( $options['font_dns_prefetch'] );
$font_preload_urls         = ! empty( $options['font_preload_urls'] ) ? $options['font_preload_urls'] : '';
?>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Font Optimization', 'velocitywp' ); ?></h2>

	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Why optimize fonts?', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Web fonts can significantly impact page load time. Proper optimization ensures fonts load quickly without blocking page rendering.', 'velocitywp' ); ?></p>
	</div>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'General Settings', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Font Optimization', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[font_optimization_enabled]" value="1"
						<?php checked( $font_optimization_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable all font optimization features', 'velocitywp' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Font Display Strategy', 'velocitywp' ); ?></th>
			<td>
				<select name="velocitywp_options[font_display]">
					<option value="auto" <?php selected( $font_display, 'auto' ); ?>><?php esc_html_e( 'Auto (browser default)', 'velocitywp' ); ?></option>
					<option value="swap" <?php selected( $font_display, 'swap' ); ?>><?php esc_html_e( 'Swap (recommended)', 'velocitywp' ); ?></option>
					<option value="block" <?php selected( $font_display, 'block' ); ?>><?php esc_html_e( 'Block', 'velocitywp' ); ?></option>
					<option value="fallback" <?php selected( $font_display, 'fallback' ); ?>><?php esc_html_e( 'Fallback', 'velocitywp' ); ?></option>
					<option value="optional" <?php selected( $font_display, 'optional' ); ?>><?php esc_html_e( 'Optional', 'velocitywp' ); ?></option>
				</select>
				<p class="description">
					<strong><?php esc_html_e( 'Swap:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Shows fallback font immediately, swaps to web font when loaded (no FOIT)', 'velocitywp' ); ?><br>
					<strong><?php esc_html_e( 'Block:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Brief invisible period, then shows web font', 'velocitywp' ); ?><br>
					<strong><?php esc_html_e( 'Fallback:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Very brief invisible period, then fallback, then swap', 'velocitywp' ); ?><br>
					<strong><?php esc_html_e( 'Optional:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Only use web font if already cached', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Resource Hints', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'DNS Prefetch', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[font_dns_prefetch]" value="1"
						<?php checked( $font_dns_prefetch, 1 ); ?>>
					<?php esc_html_e( 'Add DNS prefetch for font domains', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Resolves DNS early for fonts.googleapis.com and fonts.gstatic.com', 'velocitywp' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Preconnect', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[font_preconnect]" value="1"
						<?php checked( $font_preconnect, 1 ); ?>>
					<?php esc_html_e( 'Add preconnect for font domains', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Establishes early connection to font servers (stronger than DNS prefetch)', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Font Preloading', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Preload Fonts', 'velocitywp' ); ?></th>
			<td>
				<textarea name="velocitywp_options[font_preload_urls]" rows="6" class="large-text code"><?php echo esc_textarea( $font_preload_urls ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Enter font URLs to preload (one per line). Preloading critical fonts ensures they load immediately.', 'velocitywp' ); ?><br>
					<strong><?php esc_html_e( 'Example:', 'velocitywp' ); ?></strong> <code>/wp-content/themes/your-theme/fonts/my-font.woff2</code>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Local Google Fonts', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Host Google Fonts Locally', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[local_google_fonts]" value="1"
						<?php checked( $local_google_fonts, 1 ); ?>>
					<?php esc_html_e( 'Download and serve Google Fonts from your server', 'velocitywp' ); ?>
				</label>
				<div style="margin-top:10px;padding:10px;background:#f0f6fc;border-left:4px solid #2271b1;">
					<strong><?php esc_html_e( 'Benefits:', 'velocitywp' ); ?></strong><br>
					✅ <?php esc_html_e( 'Eliminates external HTTP requests', 'velocitywp' ); ?><br>
					✅ <?php esc_html_e( 'Better control over caching', 'velocitywp' ); ?><br>
					✅ <?php esc_html_e( 'GDPR compliance (no data sent to Google)', 'velocitywp' ); ?><br>
					✅ <?php esc_html_e( 'Works offline/on intranets', 'velocitywp' ); ?>
				</div>
			</td>
		</tr>
	</table>
</div>

<?php if ( ! empty( $detected_fonts ) ) : ?>
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Detected Google Fonts', 'velocitywp' ); ?></h2>

	<p><?php esc_html_e( 'We found the following Google Fonts loaded on your site:', 'velocitywp' ); ?></p>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Font Family', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Weights', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Handle', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Status', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Action', 'velocitywp' ); ?></th>
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
						$fonts_dir    = $upload_dir['basedir'] . '/velocitywp-fonts/';
						$local_css    = $fonts_dir . $css_filename;
						if ( file_exists( $local_css ) ) :
							?>
							<span style="color:#00a32a;">✓ <?php esc_html_e( 'Local', 'velocitywp' ); ?></span>
						<?php else : ?>
							<span style="color:#d63638;">✗ <?php esc_html_e( 'Remote', 'velocitywp' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<button type="button" class="button button-small velocitywp-download-font"
							data-url="<?php echo esc_attr( $font['url'] ); ?>"
							data-handle="<?php echo esc_attr( $font['handle'] ); ?>">
							<?php esc_html_e( 'Download Locally', 'velocitywp' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p style="margin-top:15px;">
		<button type="button" class="button button-primary" id="velocitywp-download-all-fonts">
			<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download All Fonts', 'velocitywp' ); ?>
		</button>
	</p>
</div>
<?php else : ?>
<div class="velocitywp-tab-section">
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'No Google Fonts detected on your site. If you\'re using Google Fonts, make sure they\'re enqueued properly.', 'velocitywp' ); ?></p>
	</div>
</div>
<?php endif; ?>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Local Fonts Statistics', 'velocitywp' ); ?></h2>

	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
		<div style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
			<div style="font-size:36px;font-weight:bold;color:#2271b1;"><?php echo esc_html( $stats['files'] ); ?></div>
			<div><?php esc_html_e( 'Local Font Files', 'velocitywp' ); ?></div>
		</div>

		<div style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
			<div style="font-size:36px;font-weight:bold;color:#00a32a;"><?php echo esc_html( $stats['size_formatted'] ); ?></div>
			<div><?php esc_html_e( 'Total Size', 'velocitywp' ); ?></div>
		</div>
	</div>

	<?php if ( $stats['files'] > 0 ) : ?>
	<p style="margin-top:15px;">
		<button type="button" class="button button-secondary" id="velocitywp-clear-local-fonts">
			<?php esc_html_e( 'Clear All Local Fonts', 'velocitywp' ); ?>
		</button>
	</p>
	<?php endif; ?>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Font Subsetting (Advanced)', 'velocitywp' ); ?></h2>

	<div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:4px;">
		<p><strong><?php esc_html_e( 'Tip:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Reduce font file size by including only the characters you need.', 'velocitywp' ); ?></p>
		<p><?php esc_html_e( 'When requesting Google Fonts, add the', 'velocitywp' ); ?> <code>&subset=</code> <?php esc_html_e( 'parameter:', 'velocitywp' ); ?></p>
		<ul style="margin-left:20px;">
			<li><code>&subset=latin</code> - <?php esc_html_e( 'Basic Latin characters (default)', 'velocitywp' ); ?></li>
			<li><code>&subset=latin-ext</code> - <?php esc_html_e( 'Extended Latin (includes accented characters)', 'velocitywp' ); ?></li>
			<li><code>&subset=cyrillic</code> - <?php esc_html_e( 'Cyrillic characters', 'velocitywp' ); ?></li>
			<li><code>&subset=greek</code> - <?php esc_html_e( 'Greek characters', 'velocitywp' ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( 'Example:', 'velocitywp' ); ?></strong><br>
		<code>https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&subset=latin&display=swap</code></p>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Download single font
	$('.velocitywp-download-font').on('click', function() {
		var $btn = $(this);
		var url = $btn.data('url');
		var handle = $btn.data('handle');

		$btn.prop('disabled', true).text('<?php esc_html_e( 'Downloading...', 'velocitywp' ); ?>');

		$.post(ajaxurl, {
			action: 'velocitywp_download_google_fonts',
			nonce: velocitywpAdmin.nonce,
			url: url
		}, function(response) {
			if (response.success) {
				$btn.text('✓ <?php esc_html_e( 'Downloaded', 'velocitywp' ); ?>').css('color', '#00a32a');
				setTimeout(function() {
					location.reload();
				}, 1000);
			} else {
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Download Failed', 'velocitywp' ); ?>');
				alert('<?php esc_html_e( 'Error:', 'velocitywp' ); ?> ' + response.data.message);
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Network Error', 'velocitywp' ); ?>');
		});
	});

	// Download all fonts
	$('#velocitywp-download-all-fonts').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Download all detected Google Fonts locally?', 'velocitywp' ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;"></span> <?php esc_html_e( 'Downloading...', 'velocitywp' ); ?>');

		var $fontButtons = $('.velocitywp-download-font');
		var total = $fontButtons.length;
		var completed = 0;

		$fontButtons.each(function() {
			var $fontBtn = $(this);
			var url = $fontBtn.data('url');

			$.post(ajaxurl, {
				action: 'velocitywp_download_google_fonts',
				nonce: velocitywpAdmin.nonce,
				url: url
			}, function(response) {
				completed++;
				$btn.html('<span class="spinner is-active" style="float:none;"></span> <?php esc_html_e( 'Downloading...', 'velocitywp' ); ?> (' + completed + '/' + total + ')');

				if (completed === total) {
					$btn.html('✓ <?php esc_html_e( 'All Fonts Downloaded', 'velocitywp' ); ?>');
					setTimeout(function() {
						location.reload();
					}, 1500);
				}
			});
		});
	});

	// Clear local fonts
	$('#velocitywp-clear-local-fonts').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Delete all locally hosted fonts? They can be re-downloaded later.', 'velocitywp' ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Clearing...', 'velocitywp' ); ?>');

		$.post(ajaxurl, {
			action: 'velocitywp_clear_local_fonts',
			nonce: velocitywpAdmin.nonce
		}, function(response) {
			if (response.success) {
				alert(response.data.message);
				location.reload();
			} else {
				alert('<?php esc_html_e( 'Error:', 'velocitywp' ); ?> ' + response.data.message);
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Clear All Local Fonts', 'velocitywp' ); ?>');
			}
		});
	});
});
</script>
