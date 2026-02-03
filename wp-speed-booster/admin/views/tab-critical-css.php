<?php
/**
 * Critical CSS Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$critical_css_enabled = ! empty( $options['critical_css_enabled'] ) ? 1 : 0;
$critical_css_mode = ! empty( $options['critical_css_mode'] ) ? $options['critical_css_mode'] : 'auto';
$critical_css_defer = ! empty( $options['critical_css_defer'] ) ? 1 : 0;
$critical_css_desktop = ! empty( $options['critical_css_desktop'] ) ? 1 : 0;
$critical_css_mobile = ! empty( $options['critical_css_mobile'] ) ? 1 : 0;
$critical_css_exclude = ! empty( $options['critical_css_exclude'] ) ? $options['critical_css_exclude'] : '';
$critical_css_manual = ! empty( $options['critical_css_manual'] ) ? $options['critical_css_manual'] : '';
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Critical CSS Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Critical CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_enabled]" value="1" <?php checked( $critical_css_enabled, 1 ); ?>>
					<?php esc_html_e( 'Automatically generate and inline critical CSS', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Improves First Contentful Paint by inlining above-the-fold CSS', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Defer Non-Critical CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_defer]" value="1" <?php checked( $critical_css_defer, 1 ); ?>>
					<?php esc_html_e( 'Defer loading of non-critical CSS files', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Load full CSS files after page render using preload technique', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Generation Mode', 'wp-speed-booster' ); ?></th>
			<td>
				<select name="wpsb_options[critical_css_mode]">
					<option value="auto" <?php selected( $critical_css_mode, 'auto' ); ?>><?php esc_html_e( 'Automatic (recommended)', 'wp-speed-booster' ); ?></option>
					<option value="manual" <?php selected( $critical_css_mode, 'manual' ); ?>><?php esc_html_e( 'Manual only', 'wp-speed-booster' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Automatic mode generates critical CSS on first page load. Manual mode requires manual generation.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Viewport Size', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_desktop]" value="1" <?php checked( $critical_css_desktop, 1 ); ?>>
					<?php esc_html_e( 'Desktop (1920x1080)', 'wp-speed-booster' ); ?>
				</label><br>
				<label>
					<input type="checkbox" name="wpsb_options[critical_css_mobile]" value="1" <?php checked( $critical_css_mobile, 1 ); ?>>
					<?php esc_html_e( 'Mobile (375x667)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Generate separate critical CSS for different viewport sizes', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Exclude URLs', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[critical_css_exclude]" rows="5" class="large-text"><?php echo esc_textarea( $critical_css_exclude ); ?></textarea>
				<p class="description"><?php esc_html_e( 'One URL per line. Use * as wildcard. Example: /checkout/*', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Generate Critical CSS', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Test URL', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="text" id="wpspeed-test-url" class="regular-text" 
					value="<?php echo esc_url( home_url() ); ?>" placeholder="<?php esc_attr_e( 'Enter URL to test', 'wp-speed-booster' ); ?>">
				<button type="button" class="button" id="wpspeed-generate-critical-css">
					<?php esc_html_e( 'Generate Critical CSS', 'wp-speed-booster' ); ?>
				</button>
				<div id="wpspeed-critical-css-result" style="margin-top:10px;"></div>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Manual Critical CSS', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[critical_css_manual]" id="wpspeed-manual-critical-css" rows="10" class="large-text code"><?php echo esc_textarea( $critical_css_manual ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Paste manually generated critical CSS here. This will override automatic generation globally.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Bulk Actions', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Clear Cache', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" class="button button-secondary" id="wpspeed-clear-critical-css">
					<?php esc_html_e( 'Clear All Critical CSS Cache', 'wp-speed-booster' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Remove all cached critical CSS. Will be regenerated on next page load.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Regenerate All', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" class="button button-primary" id="wpspeed-regenerate-critical-css">
					<?php esc_html_e( 'Regenerate Critical CSS for All Pages', 'wp-speed-booster' ); ?>
				</button>
				<div id="wpspeed-regenerate-progress" style="margin-top:10px;display:none;">
					<progress max="100" value="0" style="width:100%;"></progress>
					<p><span id="wpspeed-progress-text">0%</span> <?php esc_html_e( 'complete', 'wp-speed-booster' ); ?></p>
				</div>
				<p class="description"><?php esc_html_e( 'Generate critical CSS for all published posts and pages. This may take several minutes.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Critical CSS Status', 'wp-speed-booster' ); ?></h2>
	
	<p><?php esc_html_e( 'View and manage critical CSS for individual posts and pages using the Critical CSS meta box on the post edit screen.', 'wp-speed-booster' ); ?></p>
	
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Page/Post', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Generated', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Size', 'wp-speed-booster' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-speed-booster' ); ?></th>
			</tr>
		</thead>
		<tbody id="wpspeed-critical-css-list">
			<?php
			// Get recent posts/pages with critical CSS
			$args = array(
				'post_type' => array( 'post', 'page' ),
				'post_status' => 'publish',
				'posts_per_page' => 10,
				'orderby' => 'modified',
				'order' => 'DESC'
			);
			
			$query = new WP_Query( $args );
			
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$post_id = get_the_ID();
					$manual_css = get_post_meta( $post_id, '_wpsb_critical_css', true );
					
					// Check if critical CSS exists in cache
					$page_type = is_front_page() ? 'front_page' : ( is_page() ? 'page' : 'single_' . get_post_type() );
					$cache_key = 'wpsb_critical_css_' . $page_type . '_' . $post_id;
					$cached_css = get_transient( $cache_key );
					
					$has_css = ! empty( $manual_css ) || ! empty( $cached_css );
					$css_size = $has_css ? strlen( $manual_css ? $manual_css : $cached_css ) : 0;
					$status = $has_css ? '<span style="color:green;">✓</span>' : '<span style="color:gray;">—</span>';
					$date = $has_css ? get_the_modified_date() : '—';
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( get_edit_post_link() ); ?>"><?php the_title(); ?></a></strong>
							<br><small><?php echo esc_html( get_post_type() ); ?></small>
						</td>
						<td><?php echo $status; ?></td>
						<td><?php echo esc_html( $date ); ?></td>
						<td><?php echo $css_size > 0 ? esc_html( size_format( $css_size ) ) : '—'; ?></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'wp-speed-booster' ); ?>
							</a>
						</td>
					</tr>
					<?php
				}
				wp_reset_postdata();
			} else {
				?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No posts or pages found.', 'wp-speed-booster' ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	// Generate critical CSS
	$('#wpspeed-generate-critical-css').on('click', function() {
		var url = $('#wpspeed-test-url').val();
		var $btn = $(this);
		var $result = $('#wpspeed-critical-css-result');
		
		if (!url) {
			$result.html('<div class="notice notice-error"><p><?php esc_html_e( 'Please enter a URL', 'wp-speed-booster' ); ?></p></div>');
			return;
		}
		
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'wp-speed-booster' ); ?>');
		$result.html('<p><?php esc_html_e( 'Analyzing page...', 'wp-speed-booster' ); ?></p>');
		
		$.post(ajaxurl, {
			action: 'wpsb_generate_critical_css',
			nonce: '<?php echo wp_create_nonce( 'wpsb_admin_nonce' ); ?>',
			url: url
		}, function(response) {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate Critical CSS', 'wp-speed-booster' ); ?>');
			if (response.success) {
				$result.html('<div class="notice notice-success"><p><?php esc_html_e( 'Critical CSS generated successfully!', 'wp-speed-booster' ); ?> (' + response.data.size + ' <?php esc_html_e( 'bytes', 'wp-speed-booster' ); ?>)</p></div>');
				$('#wpspeed-manual-critical-css').val(response.data.css);
			} else {
				$result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Generate Critical CSS', 'wp-speed-booster' ); ?>');
			$result.html('<div class="notice notice-error"><p><?php esc_html_e( 'An error occurred. Please try again.', 'wp-speed-booster' ); ?></p></div>');
		});
	});
	
	// Clear critical CSS cache
	$('#wpspeed-clear-critical-css').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Clear all critical CSS cache?', 'wp-speed-booster' ); ?>')) return;
		
		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Clearing...', 'wp-speed-booster' ); ?>');
		
		$.post(ajaxurl, {
			action: 'wpsb_clear_critical_css',
			nonce: '<?php echo wp_create_nonce( 'wpsb_admin_nonce' ); ?>'
		}, function(response) {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Clear All Critical CSS Cache', 'wp-speed-booster' ); ?>');
			if (response.success) {
				alert('<?php esc_html_e( 'Critical CSS cache cleared successfully!', 'wp-speed-booster' ); ?>');
				location.reload();
			} else {
				alert('<?php esc_html_e( 'Failed to clear cache.', 'wp-speed-booster' ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Clear All Critical CSS Cache', 'wp-speed-booster' ); ?>');
			alert('<?php esc_html_e( 'An error occurred. Please try again.', 'wp-speed-booster' ); ?>');
		});
	});
	
	// Regenerate all critical CSS
	$('#wpspeed-regenerate-critical-css').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Regenerate critical CSS for all pages? This may take several minutes.', 'wp-speed-booster' ); ?>')) return;
		
		var $btn = $(this);
		var $progress = $('#wpspeed-regenerate-progress');
		var $progressBar = $progress.find('progress');
		var $progressText = $('#wpspeed-progress-text');
		var page = 1;
		var totalGenerated = 0;
		
		$btn.prop('disabled', true);
		$progress.show();
		
		function regeneratePage() {
			$.post(ajaxurl, {
				action: 'wpsb_regenerate_all_critical_css',
				nonce: '<?php echo wp_create_nonce( 'wpsb_admin_nonce' ); ?>',
				page: page
			}, function(response) {
				if (response.success) {
					totalGenerated += response.data.generated;
					var percent = response.data.total > 0 ? Math.round((totalGenerated / response.data.total) * 100) : 100;
					$progressBar.val(percent);
					$progressText.text(percent + '%');
					
					if (response.data.has_more) {
						page++;
						regeneratePage();
					} else {
						$btn.prop('disabled', false);
						alert('<?php esc_html_e( 'Critical CSS regenerated for all pages!', 'wp-speed-booster' ); ?>');
						location.reload();
					}
				} else {
					$btn.prop('disabled', false);
					$progress.hide();
					alert('<?php esc_html_e( 'Failed to regenerate critical CSS.', 'wp-speed-booster' ); ?>');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$progress.hide();
				alert('<?php esc_html_e( 'An error occurred. Please try again.', 'wp-speed-booster' ); ?>');
			});
		}
		
		regeneratePage();
	});
});
</script>
