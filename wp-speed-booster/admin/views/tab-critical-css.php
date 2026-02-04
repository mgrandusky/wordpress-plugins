<?php
/**
 * Critical CSS Tab View - Enhanced Version
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get settings
$critical_css_enabled = ! empty( $options['critical_css_enabled'] ) ? 1 : 0;
$critical_css_mode = ! empty( $options['critical_css_mode'] ) ? $options['critical_css_mode'] : 'auto';
$critical_css_defer = ! empty( $options['critical_css_defer'] ) ? 1 : 0;
$critical_css_api_key = ! empty( $options['critical_css_api_key'] ) ? $options['critical_css_api_key'] : '';
$critical_css_api_provider = ! empty( $options['critical_css_api_provider'] ) ? $options['critical_css_api_provider'] : 'criticalcss';
$critical_css_exclude = ! empty( $options['critical_css_exclude'] ) ? $options['critical_css_exclude'] : '';
$critical_css_exclude_handles = ! empty( $options['critical_css_exclude_handles'] ) ? $options['critical_css_exclude_handles'] : '';
$critical_css_defer_method = ! empty( $options['critical_css_defer_method'] ) ? $options['critical_css_defer_method'] : 'media-print';
$critical_css_mobile_separate = ! empty( $options['critical_css_mobile_separate'] ) ? 1 : 0;
$critical_css_manual = ! empty( $options['critical_css_manual'] ) ? $options['critical_css_manual'] : '';

// Get stored critical CSS data
$critical_css_data = get_option( 'wpspeed_critical_css', array() );
?>

<!-- Overview Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'What is Critical CSS?', 'wp-speed-booster' ); ?></h2>
	
	<div style="background: #f9f9f9; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0;">
		<p><strong><?php esc_html_e( 'Critical CSS = CSS needed to render above-the-fold content', 'wp-speed-booster' ); ?></strong></p>
		
		<div style="margin: 20px 0;">
			<p><strong><?php esc_html_e( 'Problem:', 'wp-speed-booster' ); ?></strong></p>
			<p><?php esc_html_e( '[CSS loads] → [Blocks rendering] → [Page displays]', 'wp-speed-booster' ); ?><br>
			<?php esc_html_e( 'Traditional CSS blocks page rendering', 'wp-speed-booster' ); ?></p>
			
			<p><strong><?php esc_html_e( 'Solution:', 'wp-speed-booster' ); ?></strong></p>
			<p><?php esc_html_e( '[Inline critical CSS] → [Page displays] → [Full CSS loads async]', 'wp-speed-booster' ); ?><br>
			<?php esc_html_e( 'Critical CSS allows instant rendering', 'wp-speed-booster' ); ?></p>
		</div>
		
		<p><strong><?php esc_html_e( 'Expected Performance Impact:', 'wp-speed-booster' ); ?></strong></p>
		<ul style="margin-left: 20px;">
			<li>⚡ <?php esc_html_e( '70-85% faster First Contentful Paint', 'wp-speed-booster' ); ?></li>
			<li>🚀 <?php esc_html_e( 'Eliminates render-blocking CSS', 'wp-speed-booster' ); ?></li>
			<li>📊 <?php esc_html_e( 'Better FCP/LCP scores', 'wp-speed-booster' ); ?></li>
			<li>✅ <?php esc_html_e( 'Sub-500ms initial render', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<!-- Settings Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Critical CSS Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Critical CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_enabled]" value="1" <?php checked( $critical_css_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable critical CSS generation and inlining', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Improves First Contentful Paint by inlining above-the-fold CSS', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Generation Method', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="radio" name="wpsb_options[critical_css_mode]" value="auto" <?php checked( $critical_css_mode, 'auto' ); ?>>
					<?php esc_html_e( 'Automatic (via API - recommended)', 'wp-speed-booster' ); ?>
				</label><br>
				<label>
					<input type="radio" name="wpsb_options[critical_css_mode]" value="manual" <?php checked( $critical_css_mode, 'manual' ); ?>>
					<?php esc_html_e( 'Manual upload', 'wp-speed-booster' ); ?>
				</label><br>
				<label>
					<input type="radio" name="wpsb_options[critical_css_mode]" value="disabled" <?php checked( $critical_css_mode, 'disabled' ); ?>>
					<?php esc_html_e( 'Disabled', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Automatic mode generates critical CSS automatically. Manual mode requires you to provide the CSS.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- API Configuration Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'API Configuration', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'API Provider', 'wp-speed-booster' ); ?></th>
			<td>
				<select name="wpsb_options[critical_css_api_provider]">
					<option value="criticalcss" <?php selected( $critical_css_api_provider, 'criticalcss' ); ?>><?php esc_html_e( 'CriticalCSS.com (recommended)', 'wp-speed-booster' ); ?></option>
					<option value="custom" <?php selected( $critical_css_api_provider, 'custom' ); ?>><?php esc_html_e( 'Custom endpoint', 'wp-speed-booster' ); ?></option>
				</select>
				<p class="description">
					<?php esc_html_e( 'CriticalCSS.com: Free (5 generations/month) | Premium ($10/month unlimited)', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'API Key', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="text" name="wpsb_options[critical_css_api_key]" value="<?php echo esc_attr( $critical_css_api_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your API key', 'wp-speed-booster' ); ?>">
				<p class="description">
					<?php esc_html_e( 'Leave empty to use local generation (fallback). Get your API key from', 'wp-speed-booster' ); ?> 
					<a href="https://criticalcss.com" target="_blank">CriticalCSS.com</a>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Template Generation Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Template Generation', 'wp-speed-booster' ); ?></h2>
	
	<p><?php esc_html_e( 'Generate critical CSS for different page templates. Each template type will have its own optimized critical CSS.', 'wp-speed-booster' ); ?></p>
	
	<table class="widefat" style="margin: 20px 0;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Template', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Size', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Last Generated', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-speed-booster' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$templates = array(
				'home' => __( 'Homepage', 'wp-speed-booster' ),
				'single-post' => __( 'Single Post', 'wp-speed-booster' ),
				'single-page' => __( 'Single Page', 'wp-speed-booster' ),
				'archive' => __( 'Archive', 'wp-speed-booster' ),
				'search' => __( 'Search', 'wp-speed-booster' ),
				'404' => __( '404 Page', 'wp-speed-booster' ),
			);
			
			foreach ( $templates as $template_key => $template_name ) {
				$desktop_key = $template_key . '_desktop';
				$mobile_key = $template_key . '_mobile';
				
				$has_desktop = isset( $critical_css_data[ $desktop_key ] );
				$has_mobile = isset( $critical_css_data[ $mobile_key ] );
				
				$desktop_data = $has_desktop ? $critical_css_data[ $desktop_key ] : array();
				$mobile_data = $has_mobile ? $critical_css_data[ $mobile_key ] : array();
				
				$status_icon = $has_desktop ? '✓' : '✗';
				$status_color = $has_desktop ? 'green' : 'gray';
				$size = $has_desktop ? size_format( $desktop_data['size'] ) : '-';
				$date = $has_desktop ? esc_html( $desktop_data['generated'] ) : '-';
				?>
				<tr>
					<td><strong><?php echo esc_html( $template_name ); ?></strong></td>
					<td>
						<span style="color:<?php echo esc_attr( $status_color ); ?>;"><?php echo esc_html( $status_icon ); ?></span>
						<?php if ( $has_desktop ) : ?>
							<?php esc_html_e( 'Generated', 'wp-speed-booster' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Not set', 'wp-speed-booster' ); ?>
						<?php endif; ?>
						<?php if ( $has_mobile ) : ?>
							<br><small><?php esc_html_e( '(+Mobile)', 'wp-speed-booster' ); ?></small>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $size ); ?></td>
					<td><?php echo esc_html( $date ); ?></td>
					<td>
						<button type="button" class="button button-small wpspeed-generate-template" data-template="<?php echo esc_attr( $template_key ); ?>">
							<?php echo $has_desktop ? esc_html__( 'Regenerate', 'wp-speed-booster' ) : esc_html__( 'Generate', 'wp-speed-booster' ); ?>
						</button>
						<?php if ( $has_desktop ) : ?>
							<button type="button" class="button button-small wpspeed-view-template-css" data-template="<?php echo esc_attr( $template_key ); ?>">
								<?php esc_html_e( 'View', 'wp-speed-booster' ); ?>
							</button>
							<button type="button" class="button button-small button-link-delete wpspeed-delete-template" data-template="<?php echo esc_attr( $template_key ); ?>">
								<?php esc_html_e( 'Delete', 'wp-speed-booster' ); ?>
							</button>
						<?php endif; ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	
	<div style="margin: 20px 0;">
		<button type="button" class="button button-primary" id="wpspeed-generate-all-templates">
			<?php esc_html_e( 'Generate All Templates', 'wp-speed-booster' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="wpspeed-regenerate-all-templates">
			<?php esc_html_e( 'Regenerate All', 'wp-speed-booster' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="wpspeed-delete-all-templates">
			<?php esc_html_e( 'Delete All', 'wp-speed-booster' ); ?>
		</button>
	</div>
	
	<div id="wpspeed-generation-progress" style="display:none; margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2196F3;">
		<p><strong><?php esc_html_e( 'Generating Critical CSS...', 'wp-speed-booster' ); ?></strong></p>
		<div style="background: #fff; height: 30px; border-radius: 5px; overflow: hidden; margin: 10px 0;">
			<div id="wpspeed-progress-bar" style="background: #2196F3; height: 100%; width: 0%; transition: width 0.3s;"></div>
		</div>
		<p id="wpspeed-progress-text">0% <?php esc_html_e( 'Complete', 'wp-speed-booster' ); ?></p>
		<p id="wpspeed-progress-status"><?php esc_html_e( 'Currently processing: Homepage', 'wp-speed-booster' ); ?></p>
	</div>
</div>

<!-- CSS Delivery Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'CSS Delivery Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Defer Non-Critical CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_defer]" value="1" <?php checked( $critical_css_defer, 1 ); ?>>
					<?php esc_html_e( 'Defer loading of non-critical CSS files', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Load full CSS files asynchronously after page render', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Defer Method', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="radio" name="wpsb_options[critical_css_defer_method]" value="media-print" <?php checked( $critical_css_defer_method, 'media-print' ); ?>>
					<?php esc_html_e( 'Media print method (compatible)', 'wp-speed-booster' ); ?>
				</label><br>
				<label>
					<input type="radio" name="wpsb_options[critical_css_defer_method]" value="preload" <?php checked( $critical_css_defer_method, 'preload' ); ?>>
					<?php esc_html_e( 'Preload method (modern)', 'wp-speed-booster' ); ?>
				</label>
				
				<div style="margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;">
					<p><strong><?php esc_html_e( 'Media Print Method:', 'wp-speed-booster' ); ?></strong></p>
					<code>&lt;link rel="stylesheet" href="style.css" media="print" onload="this.media='all'"&gt;</code>
					
					<p style="margin-top: 10px;"><strong><?php esc_html_e( 'Preload Method:', 'wp-speed-booster' ); ?></strong></p>
					<code>&lt;link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'"&gt;</code>
				</div>
				
				<p class="description"><?php esc_html_e( 'Both methods include noscript fallback for users without JavaScript.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Exclude Stylesheets', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="text" name="wpsb_options[critical_css_exclude_handles]" value="<?php echo esc_attr( $critical_css_exclude_handles ); ?>" class="large-text" placeholder="admin-bar, dashicons, custom-critical">
				<p class="description">
					<?php esc_html_e( "Don't defer these stylesheet handles (comma-separated). These will load normally (not deferred).", 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Exclude URLs', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[critical_css_exclude]" rows="5" class="large-text"><?php echo esc_textarea( $critical_css_exclude ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'One URL per line. Use * as wildcard. Example: /checkout/* or /cart/*', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Mobile Optimization -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Mobile Optimization', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Separate Mobile Critical CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_mobile_separate]" value="1" <?php checked( $critical_css_mobile_separate, 1 ); ?>>
					<?php esc_html_e( 'Generate mobile-specific critical CSS', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Different viewport for mobile (375x667). Recommended for mobile-first sites.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Manual Upload Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Manual Critical CSS Upload', 'wp-speed-booster' ); ?></h2>
	
	<p><?php esc_html_e( 'Paste manually generated critical CSS here. This will override automatic generation globally.', 'wp-speed-booster' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Global Manual CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[critical_css_manual]" rows="15" class="large-text code" placeholder="/* Paste critical CSS here */"><?php echo esc_textarea( $critical_css_manual ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'This CSS will be used for all pages if set. Leave empty to use template-based critical CSS.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
	
	<p>
		<strong><?php esc_html_e( 'Per-Page Critical CSS:', 'wp-speed-booster' ); ?></strong><br>
		<?php esc_html_e( 'Edit any post/page to add custom critical CSS. Look for the "Critical CSS" meta box on the post edit screen.', 'wp-speed-booster' ); ?>
	</p>
</div>

<!-- Preview Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'View Critical CSS', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Select Template', 'wp-speed-booster' ); ?></th>
			<td>
				<select id="wpspeed-preview-template">
					<?php foreach ( $templates as $template_key => $template_name ) : ?>
						<option value="<?php echo esc_attr( $template_key ); ?>"><?php echo esc_html( $template_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="wpspeed-load-preview">
					<?php esc_html_e( 'Load Preview', 'wp-speed-booster' ); ?>
				</button>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Critical CSS Preview', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea id="wpspeed-css-preview" rows="15" class="large-text code" readonly placeholder="<?php esc_attr_e( 'Select a template and click Load Preview', 'wp-speed-booster' ); ?>"></textarea>
				<p id="wpspeed-css-stats" style="margin-top: 10px; font-weight: bold;"></p>
			</td>
		</tr>
	</table>
</div>

<!-- Recommendations Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Best Practices & Recommendations', 'wp-speed-booster' ); ?></h2>
	
	<div style="background: #e7f7e7; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0;">
		<p><strong>✓ <?php esc_html_e( 'Do:', 'wp-speed-booster' ); ?></strong></p>
		<ul style="margin-left: 20px;">
			<li><?php esc_html_e( 'Generate critical CSS for main templates', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Regenerate after theme changes', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Keep critical CSS under 14KB (recommended)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Test on actual devices after generation', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Use separate mobile critical CSS if needed', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
	
	<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0;">
		<p><strong>⚠️ <?php esc_html_e( 'Avoid:', 'wp-speed-booster' ); ?></strong></p>
		<ul style="margin-left: 20px;">
			<li><?php esc_html_e( 'Including full CSS in critical CSS', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Forgetting to regenerate after updates', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Deferring critical stylesheets', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<!-- Common Issues Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Common Issues & Solutions', 'wp-speed-booster' ); ?></h2>
	
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Issue', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Solution', 'wp-speed-booster' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Page looks unstyled briefly', 'wp-speed-booster' ); ?></td>
				<td><?php esc_html_e( 'Critical CSS not generated for this template → Generate critical CSS for current template', 'wp-speed-booster' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Fonts flash/change', 'wp-speed-booster' ); ?></td>
				<td><?php esc_html_e( 'Font-face rules not in critical CSS → Add font preload + include font-face in critical CSS', 'wp-speed-booster' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Layout shifts (CLS)', 'wp-speed-booster' ); ?></td>
				<td><?php esc_html_e( 'Missing width/height in critical CSS → Include layout-critical dimensions', 'wp-speed-booster' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	// Generate single template
	$('.wpspeed-generate-template').on('click', function() {
		var $btn = $(this);
		var template = $btn.data('template');
		
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'wp-speed-booster' ); ?>');
		
		// Get template URL
		$.post(ajaxurl, {
			action: 'wpsb_generate_critical_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb_admin_nonce' ) ); ?>',
			url: '<?php echo esc_js( home_url( '/' ) ); ?>',
			template: template
		}, function(response) {
			if (response.success) {
				alert('<?php esc_html_e( 'Critical CSS generated successfully!', 'wp-speed-booster' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to generate critical CSS', 'wp-speed-booster' ); ?>');
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate', 'wp-speed-booster' ); ?>');
			}
		}).fail(function() {
			alert('<?php esc_html_e( 'An error occurred', 'wp-speed-booster' ); ?>');
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate', 'wp-speed-booster' ); ?>');
		});
	});
	
	// View template CSS
	$('.wpspeed-view-template-css').on('click', function() {
		var template = $(this).data('template');
		$('#wpspeed-preview-template').val(template);
		$('#wpspeed-load-preview').click();
	});
	
	// Delete template CSS
	$('.wpspeed-delete-template').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Delete critical CSS for this template?', 'wp-speed-booster' ); ?>')) return;
		
		var $btn = $(this);
		var template = $btn.data('template');
		
		$btn.prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'wpsb_delete_template_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb_admin_nonce' ) ); ?>',
			template: template,
			viewport: 'desktop'
		}, function(response) {
			if (response.success) {
				alert('<?php esc_html_e( 'Critical CSS deleted successfully!', 'wp-speed-booster' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to delete critical CSS', 'wp-speed-booster' ); ?>');
				$btn.prop('disabled', false);
			}
		}).fail(function() {
			alert('<?php esc_html_e( 'An error occurred', 'wp-speed-booster' ); ?>');
			$btn.prop('disabled', false);
		});
	});
	
	// Generate all templates
	$('#wpspeed-generate-all-templates, #wpspeed-regenerate-all-templates').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Generate critical CSS for all templates? This may take several minutes.', 'wp-speed-booster' ); ?>')) return;
		
		var $btn = $(this);
		var $progress = $('#wpspeed-generation-progress');
		var $progressBar = $('#wpspeed-progress-bar');
		var $progressText = $('#wpspeed-progress-text');
		
		$btn.prop('disabled', true);
		$progress.show();
		
		$.post(ajaxurl, {
			action: 'wpsb_regenerate_all_critical_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb_admin_nonce' ) ); ?>'
		}, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				$progressBar.css('width', '100%');
				$progressText.text('100% <?php esc_html_e( 'Complete', 'wp-speed-booster' ); ?>');
				alert(response.data.message || '<?php esc_html_e( 'Critical CSS regenerated for all templates!', 'wp-speed-booster' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to regenerate critical CSS', 'wp-speed-booster' ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			alert('<?php esc_html_e( 'An error occurred', 'wp-speed-booster' ); ?>');
		});
	});
	
	// Delete all templates
	$('#wpspeed-delete-all-templates').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Delete ALL critical CSS? This cannot be undone.', 'wp-speed-booster' ); ?>')) return;
		
		var $btn = $(this);
		$btn.prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'wpsb_clear_critical_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wpsb_admin_nonce' ) ); ?>'
		}, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				alert('<?php esc_html_e( 'All critical CSS deleted successfully!', 'wp-speed-booster' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to delete critical CSS', 'wp-speed-booster' ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			alert('<?php esc_html_e( 'An error occurred', 'wp-speed-booster' ); ?>');
		});
	});
	
	// Load CSS preview
	$('#wpspeed-load-preview').on('click', function() {
		var template = $('#wpspeed-preview-template').val();
		var $preview = $('#wpspeed-css-preview');
		var $stats = $('#wpspeed-css-stats');
		
		$preview.val('<?php esc_html_e( 'Loading...', 'wp-speed-booster' ); ?>');
		$stats.text('');
		
		// Load from stored data
		var cssData = <?php echo wp_json_encode( $critical_css_data ); ?>;
		var templateKey = template + '_desktop';
		
		if (cssData[templateKey]) {
			var css = cssData[templateKey].css;
			var size = cssData[templateKey].size;
			var generated = cssData[templateKey].generated;
			
			$preview.val(css);
			$stats.html('<?php esc_html_e( 'Size:', 'wp-speed-booster' ); ?> ' + size + ' <?php esc_html_e( 'bytes', 'wp-speed-booster' ); ?> | <?php esc_html_e( 'Generated:', 'wp-speed-booster' ); ?> ' + generated);
		} else {
			$preview.val('<?php esc_html_e( 'No critical CSS found for this template', 'wp-speed-booster' ); ?>');
			$stats.text('');
		}
	});
});
</script>
