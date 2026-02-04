<?php
/**
 * Critical CSS Tab View - Enhanced Version
 *
 * @package VelocityWP
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
$critical_css_data = get_option( 'velocitywp_critical_css', array() );
?>

<!-- Overview Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'What is Critical CSS?', 'velocitywp' ); ?></h2>
	
	<div style="background: #f9f9f9; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0;">
		<p><strong><?php esc_html_e( 'Critical CSS = CSS needed to render above-the-fold content', 'velocitywp' ); ?></strong></p>
		
		<div style="margin: 20px 0;">
			<p><strong><?php esc_html_e( 'Problem:', 'velocitywp' ); ?></strong></p>
			<p><?php esc_html_e( '[CSS loads] → [Blocks rendering] → [Page displays]', 'velocitywp' ); ?><br>
			<?php esc_html_e( 'Traditional CSS blocks page rendering', 'velocitywp' ); ?></p>
			
			<p><strong><?php esc_html_e( 'Solution:', 'velocitywp' ); ?></strong></p>
			<p><?php esc_html_e( '[Inline critical CSS] → [Page displays] → [Full CSS loads async]', 'velocitywp' ); ?><br>
			<?php esc_html_e( 'Critical CSS allows instant rendering', 'velocitywp' ); ?></p>
		</div>
		
		<p><strong><?php esc_html_e( 'Expected Performance Impact:', 'velocitywp' ); ?></strong></p>
		<ul style="margin-left: 20px;">
			<li>⚡ <?php esc_html_e( '70-85% faster First Contentful Paint', 'velocitywp' ); ?></li>
			<li>🚀 <?php esc_html_e( 'Eliminates render-blocking CSS', 'velocitywp' ); ?></li>
			<li>📊 <?php esc_html_e( 'Better FCP/LCP scores', 'velocitywp' ); ?></li>
			<li>✅ <?php esc_html_e( 'Sub-500ms initial render', 'velocitywp' ); ?></li>
		</ul>
	</div>
</div>

<!-- Settings Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Critical CSS Settings', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Critical CSS', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[critical_css_enabled]" value="1" <?php checked( $critical_css_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable critical CSS generation and inlining', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Improves First Contentful Paint by inlining above-the-fold CSS', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Generation Method', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="radio" name="velocitywp_options[critical_css_mode]" value="auto" <?php checked( $critical_css_mode, 'auto' ); ?>>
					<?php esc_html_e( 'Automatic (via API - recommended)', 'velocitywp' ); ?>
				</label><br>
				<label>
					<input type="radio" name="velocitywp_options[critical_css_mode]" value="manual" <?php checked( $critical_css_mode, 'manual' ); ?>>
					<?php esc_html_e( 'Manual upload', 'velocitywp' ); ?>
				</label><br>
				<label>
					<input type="radio" name="velocitywp_options[critical_css_mode]" value="disabled" <?php checked( $critical_css_mode, 'disabled' ); ?>>
					<?php esc_html_e( 'Disabled', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Automatic mode generates critical CSS automatically. Manual mode requires you to provide the CSS.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- API Configuration Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'API Configuration', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'API Provider', 'velocitywp' ); ?></th>
			<td>
				<select name="velocitywp_options[critical_css_api_provider]">
					<option value="criticalcss" <?php selected( $critical_css_api_provider, 'criticalcss' ); ?>><?php esc_html_e( 'CriticalCSS.com (recommended)', 'velocitywp' ); ?></option>
					<option value="custom" <?php selected( $critical_css_api_provider, 'custom' ); ?>><?php esc_html_e( 'Custom endpoint', 'velocitywp' ); ?></option>
				</select>
				<p class="description">
					<?php esc_html_e( 'CriticalCSS.com: Free (5 generations/month) | Premium ($10/month unlimited)', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'API Key', 'velocitywp' ); ?></th>
			<td>
				<input type="text" name="velocitywp_options[critical_css_api_key]" value="<?php echo esc_attr( $critical_css_api_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your API key', 'velocitywp' ); ?>">
				<p class="description">
					<?php esc_html_e( 'Leave empty to use local generation (fallback). Get your API key from', 'velocitywp' ); ?> 
					<a href="https://criticalcss.com" target="_blank">CriticalCSS.com</a>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Template Generation Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Template Generation', 'velocitywp' ); ?></h2>
	
	<p><?php esc_html_e( 'Generate critical CSS for different page templates. Each template type will have its own optimized critical CSS.', 'velocitywp' ); ?></p>
	
	<table class="widefat" style="margin: 20px 0;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Template', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Status', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Size', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Last Generated', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'velocitywp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$templates = array(
				'home' => __( 'Homepage', 'velocitywp' ),
				'single-post' => __( 'Single Post', 'velocitywp' ),
				'single-page' => __( 'Single Page', 'velocitywp' ),
				'archive' => __( 'Archive', 'velocitywp' ),
				'search' => __( 'Search', 'velocitywp' ),
				'404' => __( '404 Page', 'velocitywp' ),
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
							<?php esc_html_e( 'Generated', 'velocitywp' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Not set', 'velocitywp' ); ?>
						<?php endif; ?>
						<?php if ( $has_mobile ) : ?>
							<br><small><?php esc_html_e( '(+Mobile)', 'velocitywp' ); ?></small>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $size ); ?></td>
					<td><?php echo esc_html( $date ); ?></td>
					<td>
						<button type="button" class="button button-small velocitywp-generate-template" data-template="<?php echo esc_attr( $template_key ); ?>">
							<?php echo $has_desktop ? esc_html__( 'Regenerate', 'velocitywp' ) : esc_html__( 'Generate', 'velocitywp' ); ?>
						</button>
						<?php if ( $has_desktop ) : ?>
							<button type="button" class="button button-small velocitywp-view-template-css" data-template="<?php echo esc_attr( $template_key ); ?>">
								<?php esc_html_e( 'View', 'velocitywp' ); ?>
							</button>
							<button type="button" class="button button-small button-link-delete velocitywp-delete-template" data-template="<?php echo esc_attr( $template_key ); ?>">
								<?php esc_html_e( 'Delete', 'velocitywp' ); ?>
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
		<button type="button" class="button button-primary" id="velocitywp-generate-all-templates">
			<?php esc_html_e( 'Generate All Templates', 'velocitywp' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="velocitywp-regenerate-all-templates">
			<?php esc_html_e( 'Regenerate All', 'velocitywp' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="velocitywp-delete-all-templates">
			<?php esc_html_e( 'Delete All', 'velocitywp' ); ?>
		</button>
	</div>
	
	<div id="velocitywp-generation-progress" style="display:none; margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2196F3;">
		<p><strong><?php esc_html_e( 'Generating Critical CSS...', 'velocitywp' ); ?></strong></p>
		<div style="background: #fff; height: 30px; border-radius: 5px; overflow: hidden; margin: 10px 0;">
			<div id="velocitywp-progress-bar" style="background: #2196F3; height: 100%; width: 0%; transition: width 0.3s;"></div>
		</div>
		<p id="velocitywp-progress-text">0% <?php esc_html_e( 'Complete', 'velocitywp' ); ?></p>
		<p id="velocitywp-progress-status"><?php esc_html_e( 'Currently processing: Homepage', 'velocitywp' ); ?></p>
	</div>
</div>

<!-- CSS Delivery Settings -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'CSS Delivery Settings', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Defer Non-Critical CSS', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[critical_css_defer]" value="1" <?php checked( $critical_css_defer, 1 ); ?>>
					<?php esc_html_e( 'Defer loading of non-critical CSS files', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Load full CSS files asynchronously after page render', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Defer Method', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="radio" name="velocitywp_options[critical_css_defer_method]" value="media-print" <?php checked( $critical_css_defer_method, 'media-print' ); ?>>
					<?php esc_html_e( 'Media print method (compatible)', 'velocitywp' ); ?>
				</label><br>
				<label>
					<input type="radio" name="velocitywp_options[critical_css_defer_method]" value="preload" <?php checked( $critical_css_defer_method, 'preload' ); ?>>
					<?php esc_html_e( 'Preload method (modern)', 'velocitywp' ); ?>
				</label>
				
				<div style="margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;">
					<p><strong><?php esc_html_e( 'Media Print Method:', 'velocitywp' ); ?></strong></p>
					<code>&lt;link rel="stylesheet" href="style.css" media="print" onload="this.media='all'"&gt;</code>
					
					<p style="margin-top: 10px;"><strong><?php esc_html_e( 'Preload Method:', 'velocitywp' ); ?></strong></p>
					<code>&lt;link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'"&gt;</code>
				</div>
				
				<p class="description"><?php esc_html_e( 'Both methods include noscript fallback for users without JavaScript.', 'velocitywp' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Exclude Stylesheets', 'velocitywp' ); ?></th>
			<td>
				<input type="text" name="velocitywp_options[critical_css_exclude_handles]" value="<?php echo esc_attr( $critical_css_exclude_handles ); ?>" class="large-text" placeholder="admin-bar, dashicons, custom-critical">
				<p class="description">
					<?php esc_html_e( "Don't defer these stylesheet handles (comma-separated). These will load normally (not deferred).", 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Exclude URLs', 'velocitywp' ); ?></th>
			<td>
				<textarea name="velocitywp_options[critical_css_exclude]" rows="5" class="large-text"><?php echo esc_textarea( $critical_css_exclude ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'One URL per line. Use * as wildcard. Example: /checkout/* or /cart/*', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Mobile Optimization -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Mobile Optimization', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Separate Mobile Critical CSS', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[critical_css_mobile_separate]" value="1" <?php checked( $critical_css_mobile_separate, 1 ); ?>>
					<?php esc_html_e( 'Generate mobile-specific critical CSS', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Different viewport for mobile (375x667). Recommended for mobile-first sites.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Manual Upload Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Manual Critical CSS Upload', 'velocitywp' ); ?></h2>
	
	<p><?php esc_html_e( 'Paste manually generated critical CSS here. This will override automatic generation globally.', 'velocitywp' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Global Manual CSS', 'velocitywp' ); ?></th>
			<td>
				<textarea name="velocitywp_options[critical_css_manual]" rows="15" class="large-text code" placeholder="/* Paste critical CSS here */"><?php echo esc_textarea( $critical_css_manual ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'This CSS will be used for all pages if set. Leave empty to use template-based critical CSS.', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>
	
	<p>
		<strong><?php esc_html_e( 'Per-Page Critical CSS:', 'velocitywp' ); ?></strong><br>
		<?php esc_html_e( 'Edit any post/page to add custom critical CSS. Look for the "Critical CSS" meta box on the post edit screen.', 'velocitywp' ); ?>
	</p>
</div>

<!-- Preview Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'View Critical CSS', 'velocitywp' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Select Template', 'velocitywp' ); ?></th>
			<td>
				<select id="velocitywp-preview-template">
					<?php foreach ( $templates as $template_key => $template_name ) : ?>
						<option value="<?php echo esc_attr( $template_key ); ?>"><?php echo esc_html( $template_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="velocitywp-load-preview">
					<?php esc_html_e( 'Load Preview', 'velocitywp' ); ?>
				</button>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Critical CSS Preview', 'velocitywp' ); ?></th>
			<td>
				<textarea id="velocitywp-css-preview" rows="15" class="large-text code" readonly placeholder="<?php esc_attr_e( 'Select a template and click Load Preview', 'velocitywp' ); ?>"></textarea>
				<p id="velocitywp-css-stats" style="margin-top: 10px; font-weight: bold;"></p>
			</td>
		</tr>
	</table>
</div>

<!-- Recommendations Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Best Practices & Recommendations', 'velocitywp' ); ?></h2>
	
	<div style="background: #e7f7e7; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0;">
		<p><strong>✓ <?php esc_html_e( 'Do:', 'velocitywp' ); ?></strong></p>
		<ul style="margin-left: 20px;">
			<li><?php esc_html_e( 'Generate critical CSS for main templates', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Regenerate after theme changes', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Keep critical CSS under 14KB (recommended)', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Test on actual devices after generation', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Use separate mobile critical CSS if needed', 'velocitywp' ); ?></li>
		</ul>
	</div>
	
	<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0;">
		<p><strong>⚠️ <?php esc_html_e( 'Avoid:', 'velocitywp' ); ?></strong></p>
		<ul style="margin-left: 20px;">
			<li><?php esc_html_e( 'Including full CSS in critical CSS', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Forgetting to regenerate after updates', 'velocitywp' ); ?></li>
			<li><?php esc_html_e( 'Deferring critical stylesheets', 'velocitywp' ); ?></li>
		</ul>
	</div>
</div>

<!-- Common Issues Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Common Issues & Solutions', 'velocitywp' ); ?></h2>
	
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Issue', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Solution', 'velocitywp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Page looks unstyled briefly', 'velocitywp' ); ?></td>
				<td><?php esc_html_e( 'Critical CSS not generated for this template → Generate critical CSS for current template', 'velocitywp' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Fonts flash/change', 'velocitywp' ); ?></td>
				<td><?php esc_html_e( 'Font-face rules not in critical CSS → Add font preload + include font-face in critical CSS', 'velocitywp' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Layout shifts (CLS)', 'velocitywp' ); ?></td>
				<td><?php esc_html_e( 'Missing width/height in critical CSS → Include layout-critical dimensions', 'velocitywp' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	// Generate single template
	$('.velocitywp-generate-template').on('click', function() {
		var $btn = $(this);
		var template = $btn.data('template');
		
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'velocitywp' ); ?>');
		
		// Get template URL
		$.post(ajaxurl, {
			action: 'velocitywp_generate_critical_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp_admin_nonce' ) ); ?>',
			url: '<?php echo esc_js( home_url( '/' ) ); ?>',
			template: template
		}, function(response) {
			if (response.success) {
				alert('<?php esc_html_e( 'Critical CSS generated successfully!', 'velocitywp' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to generate critical CSS', 'velocitywp' ); ?>');
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate', 'velocitywp' ); ?>');
			}
		}).fail(function() {
			alert('<?php esc_html_e( 'An error occurred', 'velocitywp' ); ?>');
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate', 'velocitywp' ); ?>');
		});
	});
	
	// View template CSS
	$('.velocitywp-view-template-css').on('click', function() {
		var template = $(this).data('template');
		$('#velocitywp-preview-template').val(template);
		$('#velocitywp-load-preview').click();
	});
	
	// Delete template CSS
	$('.velocitywp-delete-template').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Delete critical CSS for this template?', 'velocitywp' ); ?>')) return;
		
		var $btn = $(this);
		var template = $btn.data('template');
		
		$btn.prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'velocitywp_delete_template_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp_admin_nonce' ) ); ?>',
			template: template,
			viewport: 'desktop'
		}, function(response) {
			if (response.success) {
				alert('<?php esc_html_e( 'Critical CSS deleted successfully!', 'velocitywp' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to delete critical CSS', 'velocitywp' ); ?>');
				$btn.prop('disabled', false);
			}
		}).fail(function() {
			alert('<?php esc_html_e( 'An error occurred', 'velocitywp' ); ?>');
			$btn.prop('disabled', false);
		});
	});
	
	// Generate all templates
	$('#velocitywp-generate-all-templates, #velocitywp-regenerate-all-templates').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Generate critical CSS for all templates? This may take several minutes.', 'velocitywp' ); ?>')) return;
		
		var $btn = $(this);
		var $progress = $('#velocitywp-generation-progress');
		var $progressBar = $('#velocitywp-progress-bar');
		var $progressText = $('#velocitywp-progress-text');
		
		$btn.prop('disabled', true);
		$progress.show();
		
		$.post(ajaxurl, {
			action: 'velocitywp_regenerate_all_critical_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp_admin_nonce' ) ); ?>'
		}, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				$progressBar.css('width', '100%');
				$progressText.text('100% <?php esc_html_e( 'Complete', 'velocitywp' ); ?>');
				alert(response.data.message || '<?php esc_html_e( 'Critical CSS regenerated for all templates!', 'velocitywp' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to regenerate critical CSS', 'velocitywp' ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			alert('<?php esc_html_e( 'An error occurred', 'velocitywp' ); ?>');
		});
	});
	
	// Delete all templates
	$('#velocitywp-delete-all-templates').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Delete ALL critical CSS? This cannot be undone.', 'velocitywp' ); ?>')) return;
		
		var $btn = $(this);
		$btn.prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'velocitywp_clear_critical_css',
			nonce: '<?php echo esc_js( wp_create_nonce( 'velocitywp_admin_nonce' ) ); ?>'
		}, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				alert('<?php esc_html_e( 'All critical CSS deleted successfully!', 'velocitywp' ); ?>');
				location.reload();
			} else {
				alert(response.data.message || '<?php esc_html_e( 'Failed to delete critical CSS', 'velocitywp' ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			alert('<?php esc_html_e( 'An error occurred', 'velocitywp' ); ?>');
		});
	});
	
	// Load CSS preview
	$('#velocitywp-load-preview').on('click', function() {
		var template = $('#velocitywp-preview-template').val();
		var $preview = $('#velocitywp-css-preview');
		var $stats = $('#velocitywp-css-stats');
		
		$preview.val('<?php esc_html_e( 'Loading...', 'velocitywp' ); ?>');
		$stats.text('');
		
		// Load from stored data
		var cssData = <?php echo wp_json_encode( $critical_css_data, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
		var templateKey = template + '_desktop';
		
		if (cssData[templateKey]) {
			var css = cssData[templateKey].css;
			var size = cssData[templateKey].size;
			var generated = cssData[templateKey].generated;
			
			$preview.val(css);
			$stats.html('<?php esc_html_e( 'Size:', 'velocitywp' ); ?> ' + size + ' <?php esc_html_e( 'bytes', 'velocitywp' ); ?> | <?php esc_html_e( 'Generated:', 'velocitywp' ); ?> ' + generated);
		} else {
			$preview.val('<?php esc_html_e( 'No critical CSS found for this template', 'velocitywp' ); ?>');
			$stats.text('');
		}
	});
});
</script>
