<?php
/**
 * WebP Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize WebP class to get stats and support info
$webp = new WPSB_WebP();
$support = function_exists('imagewebp') ? 'gd' : false;
if (!$support && extension_loaded('imagick')) {
	$imagick = new Imagick();
	if (in_array('WEBP', $imagick->queryFormats())) {
		$support = 'imagick';
	}
}

$webp_enabled = ! empty( $options['webp_enabled'] ) ? 1 : 0;
$webp_quality = ! empty( $options['webp_quality'] ) ? $options['webp_quality'] : 85;

// Get conversion statistics
global $wpdb;
$total_images = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png')");
$total_images = intval($total_images);

// Count converted images by checking for webp files
$args = array(
	'post_type' => 'attachment',
	'post_mime_type' => array('image/jpeg', 'image/jpg', 'image/png'),
	'posts_per_page' => -1,
	'fields' => 'ids',
	'post_status' => 'inherit'
);
$all_images = get_posts($args);
$converted = 0;
foreach ($all_images as $img_id) {
	$file = get_attached_file($img_id);
	if ($file) {
		$webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
		if (file_exists($webp_file)) {
			$converted++;
		}
	}
}

$stats = array(
	'total' => $total_images,
	'converted' => $converted,
	'remaining' => $total_images - $converted,
	'percentage' => $total_images > 0 ? round(($converted / $total_images) * 100) : 0
);
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'WebP Image Optimization', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is WebP?', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'WebP is a modern image format that provides superior compression for images on the web. WebP images are typically 25-35% smaller than JPEG/PNG.', 'wp-speed-booster' ); ?></p>
	</div>
	
	<?php if ( ! $support ): ?>
	<div class="notice notice-error">
		<p><strong><?php esc_html_e( 'WebP Not Supported!', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Your server does not support WebP conversion. Please contact your hosting provider or install GD/Imagick with WebP support.', 'wp-speed-booster' ); ?></p>
	</div>
	<?php else: ?>
	<div class="notice notice-success">
		<p><strong><?php esc_html_e( 'WebP Supported!', 'wp-speed-booster' ); ?></strong> <?php echo sprintf( esc_html__( 'Your server supports WebP conversion via %s.', 'wp-speed-booster' ), strtoupper( $support ) ); ?></p>
	</div>
	<?php endif; ?>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable WebP Conversion', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[webp_enabled]" value="1" <?php checked( $webp_enabled, 1 ); ?> <?php disabled( ! $support ); ?>>
					<?php esc_html_e( 'Automatically convert images to WebP format', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Convert new uploads and serve WebP to supported browsers', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'WebP Quality', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="range" name="wpsb_options[webp_quality]" min="60" max="100" value="<?php echo esc_attr( $webp_quality ); ?>" 
					oninput="this.nextElementSibling.value = this.value" <?php disabled( ! $support ); ?>>
				<output><?php echo esc_html( $webp_quality ); ?></output>%
				<p class="description"><?php esc_html_e( 'Higher quality = larger file size (recommended: 85)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Keep Original Images', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[webp_keep_original]" value="1" checked disabled>
					<?php esc_html_e( 'Always keep original images (recommended)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Original images are preserved for compatibility', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Bulk Conversion', 'wp-speed-booster' ); ?></h2>
	
	<div class="wpspeed-stats-box" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;margin-bottom:20px;">
		<h3 style="margin-top:0;"><?php esc_html_e( 'Conversion Statistics', 'wp-speed-booster' ); ?></h3>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
			<div>
				<strong><?php esc_html_e( 'Total Images:', 'wp-speed-booster' ); ?></strong><br>
				<span style="font-size:24px;color:#2271b1;"><?php echo number_format( $stats['total'] ); ?></span>
			</div>
			<div>
				<strong><?php esc_html_e( 'Converted:', 'wp-speed-booster' ); ?></strong><br>
				<span style="font-size:24px;color:#00a32a;"><?php echo number_format( $stats['converted'] ); ?></span>
			</div>
			<div>
				<strong><?php esc_html_e( 'Remaining:', 'wp-speed-booster' ); ?></strong><br>
				<span style="font-size:24px;color:#d63638;"><?php echo number_format( $stats['remaining'] ); ?></span>
			</div>
			<div>
				<strong><?php esc_html_e( 'Progress:', 'wp-speed-booster' ); ?></strong><br>
				<span style="font-size:24px;color:#2271b1;"><?php echo $stats['percentage']; ?>%</span>
			</div>
		</div>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Bulk Convert Existing Images', 'wp-speed-booster' ); ?></th>
			<td>
				<button type="button" class="button button-primary button-large" id="wpspeed-bulk-convert-webp" 
					<?php disabled( ! $support || $stats['remaining'] == 0 ); ?>>
					<span class="dashicons dashicons-images-alt2"></span>
					<?php echo sprintf( esc_html__( 'Convert %s Images to WebP', 'wp-speed-booster' ), number_format( $stats['remaining'] ) ); ?>
				</button>
				
				<div id="wpspeed-webp-progress" style="margin-top:15px;display:none;">
					<div style="background:#f0f0f1;border:1px solid #c3c4c7;border-radius:4px;height:30px;position:relative;overflow:hidden;">
						<div id="wpspeed-webp-progress-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;">
							<span id="wpspeed-webp-progress-text">0%</span>
						</div>
					</div>
					<p id="wpspeed-webp-status" style="margin-top:10px;color:#646970;"><?php esc_html_e( 'Starting conversion...', 'wp-speed-booster' ); ?></p>
				</div>
			</td>
		</tr>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	$('#wpspeed-bulk-convert-webp').on('click', function() {
		var $btn = $(this);
		var $progress = $('#wpspeed-webp-progress');
		var $bar = $('#wpspeed-webp-progress-bar');
		var $text = $('#wpspeed-webp-progress-text');
		var $status = $('#wpspeed-webp-status');
		
		$btn.prop('disabled', true);
		$progress.show();
		
		function convertBatch(offset) {
			$.post(ajaxurl, {
				action: 'wpsb_bulk_convert_webp',
				nonce: wpsbAdmin.nonce,
				offset: offset
			}, function(response) {
				if (response.success) {
					var data = response.data;
					var percentage = data.progress || 0;
					$bar.css('width', percentage + '%');
					$text.text(Math.round(percentage) + '%');
					$status.text('<?php esc_html_e( 'Converted', 'wp-speed-booster' ); ?> ' + data.converted + ' <?php esc_html_e( 'images...', 'wp-speed-booster' ); ?> (' + (offset + data.converted) + ' <?php esc_html_e( 'of', 'wp-speed-booster' ); ?> ' + data.total + ')');
					
					if (!data.complete) {
						convertBatch(offset + 10);
					} else {
						$status.html('<strong style="color:#00a32a;">✓ <?php esc_html_e( 'Conversion complete!', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'All images have been converted to WebP.', 'wp-speed-booster' ); ?>');
						$btn.text('<?php esc_html_e( 'Conversion Complete', 'wp-speed-booster' ); ?>');
						setTimeout(function() {
							location.reload();
						}, 2000);
					}
				} else {
					$status.html('<strong style="color:#d63638;"><?php esc_html_e( 'Error:', 'wp-speed-booster' ); ?></strong> ' + response.data.message);
					$btn.prop('disabled', false);
				}
			}).fail(function() {
				$status.html('<strong style="color:#d63638;"><?php esc_html_e( 'Error:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Network error occurred.', 'wp-speed-booster' ); ?>');
				$btn.prop('disabled', false);
			});
		}
		
		convertBatch(0);
	});
});
</script>
