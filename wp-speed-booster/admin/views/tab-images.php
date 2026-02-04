<?php
/**
 * Image Optimization Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize image optimizer to get stats
$image_optimizer = new WP_Speed_Booster_Image_Optimizer();
$stats = $image_optimizer->get_stats();

// Check system support
$imagick_support = extension_loaded( 'imagick' );
$gd_support = function_exists( 'imagewebp' );
$webp_support = $imagick_support || $gd_support;

// Get settings
$image_optimization_enabled = ! empty( $options['image_optimization_enabled'] ) ? 1 : 0;
$image_optimization_method = ! empty( $options['image_optimization_method'] ) ? $options['image_optimization_method'] : 'gd';
$image_quality = ! empty( $options['image_quality'] ) ? intval( $options['image_quality'] ) : 85;
$image_preserve_exif = ! empty( $options['image_preserve_exif'] ) ? 1 : 0;
$image_webp_enabled = ! empty( $options['image_webp_enabled'] ) ? 1 : 0;
$image_webp_quality = ! empty( $options['image_webp_quality'] ) ? intval( $options['image_webp_quality'] ) : 85;
$image_webp_skip_existing = ! empty( $options['image_webp_skip_existing'] ) ? 1 : 0;
$image_use_picture = ! empty( $options['image_use_picture'] ) ? 1 : 0;
$image_max_width = ! empty( $options['image_max_width'] ) ? intval( $options['image_max_width'] ) : 2000;
$image_max_height = ! empty( $options['image_max_height'] ) ? intval( $options['image_max_height'] ) : 2000;
$image_resize_on_upload = ! empty( $options['image_resize_on_upload'] ) ? 1 : 0;
$image_api_key = ! empty( $options['image_api_key'] ) ? $options['image_api_key'] : '';
$image_api_provider = ! empty( $options['image_api_provider'] ) ? $options['image_api_provider'] : 'tinypng';
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Image Optimization & WebP Conversion', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info">
		<p>
			<strong><?php esc_html_e( 'About Image Optimization:', 'wp-speed-booster' ); ?></strong>
			<?php esc_html_e( 'Automatically optimize images on upload, convert to WebP format, and dramatically reduce image file sizes without visible quality loss. Images are typically 30-60% smaller after optimization!', 'wp-speed-booster' ); ?>
		</p>
	</div>
</div>

<!-- Overview Statistics -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Current Status', 'wp-speed-booster' ); ?></h2>
	
	<div class="wpspeed-stats-box" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;margin-bottom:20px;">
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
			<div style="text-align:center;">
				<strong style="display:block;margin-bottom:8px;color:#666;"><?php esc_html_e( 'Total Images', 'wp-speed-booster' ); ?></strong>
				<span style="font-size:32px;font-weight:bold;color:#2271b1;"><?php echo number_format( $stats['total_images'] + $stats['unoptimized_images'] ); ?></span>
			</div>
			<div style="text-align:center;">
				<strong style="display:block;margin-bottom:8px;color:#666;"><?php esc_html_e( 'Optimized', 'wp-speed-booster' ); ?></strong>
				<span style="font-size:32px;font-weight:bold;color:#00a32a;"><?php echo number_format( $stats['total_images'] ); ?></span>
			</div>
			<div style="text-align:center;">
				<strong style="display:block;margin-bottom:8px;color:#666;"><?php esc_html_e( 'Remaining', 'wp-speed-booster' ); ?></strong>
				<span style="font-size:32px;font-weight:bold;color:#d63638;"><?php echo number_format( $stats['unoptimized_images'] ); ?></span>
			</div>
			<div style="text-align:center;">
				<strong style="display:block;margin-bottom:8px;color:#666;"><?php esc_html_e( 'Total Savings', 'wp-speed-booster' ); ?></strong>
				<span style="font-size:32px;font-weight:bold;color:#2271b1;"><?php echo esc_html( $stats['total_savings_formatted'] ); ?></span>
			</div>
			<div style="text-align:center;">
				<strong style="display:block;margin-bottom:8px;color:#666;"><?php esc_html_e( 'Average Savings', 'wp-speed-booster' ); ?></strong>
				<span style="font-size:32px;font-weight:bold;color:#2271b1;"><?php echo esc_html( $stats['average_savings_percent'] ); ?>%</span>
			</div>
		</div>
	</div>
</div>

<!-- System Support -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'System Support', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Imagick Extension', 'wp-speed-booster' ); ?></th>
			<td>
				<?php if ( $imagick_support ) : ?>
					<span style="color:#00a32a;">✓ <?php esc_html_e( 'Available (Recommended)', 'wp-speed-booster' ); ?></span>
				<?php else : ?>
					<span style="color:#d63638;">✗ <?php esc_html_e( 'Not Available', 'wp-speed-booster' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'GD Library', 'wp-speed-booster' ); ?></th>
			<td>
				<?php if ( $gd_support ) : ?>
					<span style="color:#00a32a;">✓ <?php esc_html_e( 'Available', 'wp-speed-booster' ); ?></span>
				<?php else : ?>
					<span style="color:#d63638;">✗ <?php esc_html_e( 'Not Available', 'wp-speed-booster' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'WebP Support', 'wp-speed-booster' ); ?></th>
			<td>
				<?php if ( $webp_support ) : ?>
					<span style="color:#00a32a;">✓ <?php esc_html_e( 'Available', 'wp-speed-booster' ); ?></span>
					<p class="description">
						<?php esc_html_e( 'Supported browsers: Chrome 32+, Firefox 65+, Safari 14+, Edge 18+', 'wp-speed-booster' ); ?>
					</p>
				<?php else : ?>
					<span style="color:#d63638;">✗ <?php esc_html_e( 'Not Available', 'wp-speed-booster' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<!-- Automatic Optimization Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Automatic Optimization', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Image Optimization', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[image_optimization_enabled]" value="1" <?php checked( $image_optimization_enabled, 1 ); ?>>
					<?php esc_html_e( 'Optimize images automatically on upload', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Automatically optimize all images when uploaded to media library', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Optimization Method', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="radio" name="wpsb_options[image_optimization_method]" value="imagick" <?php checked( $image_optimization_method, 'imagick' ); ?> <?php disabled( ! $imagick_support ); ?>>
					<?php esc_html_e( 'Imagick (recommended if available)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" name="wpsb_options[image_optimization_method]" value="gd" <?php checked( $image_optimization_method, 'gd' ); ?> <?php disabled( ! $gd_support ); ?>>
					<?php esc_html_e( 'GD Library', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" name="wpsb_options[image_optimization_method]" value="api" <?php checked( $image_optimization_method, 'api' ); ?>>
					<?php esc_html_e( 'External API (TinyPNG, Kraken, etc.)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Choose the method to use for image optimization', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Image Quality', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="range" name="wpsb_options[image_quality]" min="50" max="100" value="<?php echo esc_attr( $image_quality ); ?>" 
					oninput="this.nextElementSibling.value = this.value" style="width:300px;">
				<output><?php echo esc_html( $image_quality ); ?></output>
				<p class="description">
					<?php esc_html_e( '100 = Lossless (largest), 85 = High quality (recommended), 70 = Good quality (smaller), 50 = Medium quality (much smaller)', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Advanced Options', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[image_preserve_exif]" value="1" <?php checked( $image_preserve_exif, 1 ); ?>>
					<?php esc_html_e( 'Preserve EXIF data (camera info, location, etc.)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Keep image metadata. Unchecking this will reduce file size further.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- WebP Conversion Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'WebP Conversion', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-success" style="margin:15px 0;">
		<p>
			<strong><?php esc_html_e( 'WebP Savings:', 'wp-speed-booster' ); ?></strong>
			<?php esc_html_e( 'WebP typically saves 25-35% over JPEG and 25-50% over PNG with the same visual quality!', 'wp-speed-booster' ); ?>
		</p>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable WebP Conversion', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[image_webp_enabled]" value="1" <?php checked( $image_webp_enabled, 1 ); ?> <?php disabled( ! $webp_support ); ?>>
					<?php esc_html_e( 'Convert images to WebP format', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Create WebP versions alongside original images', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'WebP Quality', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="range" name="wpsb_options[image_webp_quality]" min="60" max="100" value="<?php echo esc_attr( $image_webp_quality ); ?>" 
					oninput="this.nextElementSibling.value = this.value" style="width:300px;">
				<output><?php echo esc_html( $image_webp_quality ); ?></output>
				<p class="description"><?php esc_html_e( 'WebP quality setting (recommended: 85)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'WebP Options', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[image_webp_skip_existing]" value="1" <?php checked( $image_webp_skip_existing, 1 ); ?>>
					<?php esc_html_e( 'Skip if WebP already exists', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="checkbox" name="wpsb_options[image_use_picture]" value="1" <?php checked( $image_use_picture, 1 ); ?>>
					<?php esc_html_e( 'Use &lt;picture&gt; element (automatic fallback for older browsers)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Picture element provides better browser compatibility', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Image Resizing -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Image Resizing', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info" style="margin:15px 0;">
		<p>
			<strong><?php esc_html_e( 'Why Resize?', 'wp-speed-booster' ); ?></strong>
			<?php esc_html_e( 'Users often upload 5000x3000px images that display at 800px width. Resizing saves storage space, upload time, page load time, and CDN costs.', 'wp-speed-booster' ); ?>
		</p>
	</div>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Automatic Resizing', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[image_resize_on_upload]" value="1" <?php checked( $image_resize_on_upload, 1 ); ?>>
					<?php esc_html_e( 'Automatically resize large images on upload', 'wp-speed-booster' ); ?>
				</label>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Maximum Dimensions', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<?php esc_html_e( 'Max Width:', 'wp-speed-booster' ); ?>
					<input type="number" name="wpsb_options[image_max_width]" value="<?php echo esc_attr( $image_max_width ); ?>" min="500" max="10000" style="width:100px;"> px
				</label>
				<br>
				<label>
					<?php esc_html_e( 'Max Height:', 'wp-speed-booster' ); ?>
					<input type="number" name="wpsb_options[image_max_height]" value="<?php echo esc_attr( $image_max_height ); ?>" min="500" max="10000" style="width:100px;"> px
				</label>
				<p class="description"><?php esc_html_e( 'Images larger than these dimensions will be resized (recommended: 2000x2000)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Bulk Optimization -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Bulk Optimization', 'wp-speed-booster' ); ?></h2>
	
	<p><?php esc_html_e( 'Optimize all existing images in your media library that have not been optimized yet.', 'wp-speed-booster' ); ?></p>
	
	<div class="wpspeed-bulk-optimize-container" style="margin-top:20px;">
		<?php if ( $stats['unoptimized_images'] > 0 ) : ?>
			<p>
				<strong><?php echo sprintf( esc_html__( 'Images to optimize: %s', 'wp-speed-booster' ), number_format( $stats['unoptimized_images'] ) ); ?></strong>
			</p>
			<button type="button" class="button button-primary button-large" id="wpspeed-bulk-optimize-start">
				<span class="dashicons dashicons-images-alt2"></span>
				<?php esc_html_e( 'Start Bulk Optimization', 'wp-speed-booster' ); ?>
			</button>
		<?php else : ?>
			<div class="notice notice-success inline">
				<p><?php esc_html_e( 'All images are already optimized!', 'wp-speed-booster' ); ?></p>
			</div>
		<?php endif; ?>
		
		<div id="wpspeed-bulk-optimize-progress" style="margin-top:20px;display:none;">
			<div style="background:#f0f0f0;border-radius:4px;padding:15px;">
				<div style="background:#2271b1;height:30px;border-radius:4px;transition:width 0.3s;width:0%;" id="wpspeed-progress-bar">
					<span style="color:#fff;line-height:30px;padding-left:10px;font-weight:bold;" id="wpspeed-progress-text">0%</span>
				</div>
				<p style="margin-top:10px;" id="wpspeed-progress-status"><?php esc_html_e( 'Starting optimization...', 'wp-speed-booster' ); ?></p>
			</div>
		</div>
	</div>
</div>

<!-- External API Configuration -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'External API Configuration', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'API Provider', 'wp-speed-booster' ); ?></th>
			<td>
				<select name="wpsb_options[image_api_provider]">
					<option value="tinypng" <?php selected( $image_api_provider, 'tinypng' ); ?>>TinyPNG</option>
					<option value="kraken" <?php selected( $image_api_provider, 'kraken' ); ?>>Kraken.io</option>
					<option value="shortpixel" <?php selected( $image_api_provider, 'shortpixel' ); ?>>ShortPixel</option>
					<option value="imagify" <?php selected( $image_api_provider, 'imagify' ); ?>>Imagify</option>
				</select>
				<p class="description"><?php esc_html_e( 'Select external API provider for optimization', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'API Key', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="text" name="wpsb_options[image_api_key]" value="<?php echo esc_attr( $image_api_key ); ?>" 
					class="regular-text" placeholder="<?php esc_attr_e( 'Enter your API key', 'wp-speed-booster' ); ?>">
				<p class="description">
					<?php esc_html_e( 'Required when using External API optimization method', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
	
	<div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:4px;margin-top:15px;">
		<h3 style="margin-top:0;"><?php esc_html_e( 'API Pricing Information', 'wp-speed-booster' ); ?></h3>
		<p><strong>TinyPNG:</strong></p>
		<ul style="margin-left:20px;">
			<li><?php esc_html_e( 'Free: 500 images/month', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Paid: $0.009 per image ($25 for 2,800)', 'wp-speed-booster' ); ?></li>
		</ul>
		<p><strong>Kraken.io:</strong></p>
		<ul style="margin-left:20px;">
			<li><?php esc_html_e( 'Free: 100 MB/month', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Micro: $5/month (1GB)', 'wp-speed-booster' ); ?></li>
			<li><?php esc_html_e( 'Pro: $29/month (10GB)', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<!-- Best Practices & Recommendations -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Best Practices & Recommendations', 'wp-speed-booster' ); ?></h2>
	
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
		<div style="background:#d4edda;padding:15px;border:1px solid #c3e6cb;border-radius:4px;">
			<h3 style="margin-top:0;color:#155724;"><?php esc_html_e( '✓ Recommended Settings', 'wp-speed-booster' ); ?></h3>
			<ul style="margin-left:20px;color:#155724;">
				<li><?php esc_html_e( 'Use 85 quality for photos (barely noticeable difference)', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Enable WebP conversion (25-35% smaller)', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Set max dimensions to 2000x2000px', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Enable optimization on upload', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Run bulk optimization on existing images', 'wp-speed-booster' ); ?></li>
			</ul>
		</div>
		
		<div style="background:#f8d7da;padding:15px;border:1px solid #f5c6cb;border-radius:4px;">
			<h3 style="margin-top:0;color:#721c24;"><?php esc_html_e( '⚠ Avoid', 'wp-speed-booster' ); ?></h3>
			<ul style="margin-left:20px;color:#721c24;">
				<li><?php esc_html_e( 'Quality below 70 for important images', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Uploading unnecessarily large images', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Skipping WebP conversion', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Not setting maximum dimensions', 'wp-speed-booster' ); ?></li>
			</ul>
		</div>
	</div>
</div>

<!-- Expected Performance Impact -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Expected Performance Impact', 'wp-speed-booster' ); ?></h2>
	
	<div style="background:#e7f3ff;padding:20px;border:1px solid #b3d9ff;border-radius:4px;">
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;text-align:center;">
			<div>
				<div style="font-size:36px;margin-bottom:10px;">📊</div>
				<strong><?php esc_html_e( '30-60% smaller', 'wp-speed-booster' ); ?></strong>
				<p style="margin:5px 0 0 0;font-size:12px;"><?php esc_html_e( 'Image size reduction', 'wp-speed-booster' ); ?></p>
			</div>
			<div>
				<div style="font-size:36px;margin-bottom:10px;">⚡</div>
				<strong><?php esc_html_e( 'Faster loads', 'wp-speed-booster' ); ?></strong>
				<p style="margin:5px 0 0 0;font-size:12px;"><?php esc_html_e( 'Less data to download', 'wp-speed-booster' ); ?></p>
			</div>
			<div>
				<div style="font-size:36px;margin-bottom:10px;">💾</div>
				<strong><?php esc_html_e( 'Storage savings', 'wp-speed-booster' ); ?></strong>
				<p style="margin:5px 0 0 0;font-size:12px;"><?php esc_html_e( 'Smaller files', 'wp-speed-booster' ); ?></p>
			</div>
			<div>
				<div style="font-size:36px;margin-bottom:10px;">🚀</div>
				<strong><?php esc_html_e( 'Better Core Web Vitals', 'wp-speed-booster' ); ?></strong>
				<p style="margin:5px 0 0 0;font-size:12px;"><?php esc_html_e( 'Faster LCP scores', 'wp-speed-booster' ); ?></p>
			</div>
		</div>
		
		<div style="margin-top:20px;padding-top:20px;border-top:1px solid #b3d9ff;">
			<h4 style="margin-top:0;"><?php esc_html_e( 'Typical Results:', 'wp-speed-booster' ); ?></h4>
			<ul style="margin-left:20px;">
				<li><?php esc_html_e( 'JPEG 2.4MB → 1.1MB (54% smaller)', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'PNG 1.8MB → 980KB (46% smaller)', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'WebP saves additional 25-35%', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Page with 10 images: 15MB → 5MB', 'wp-speed-booster' ); ?></li>
			</ul>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Handle bulk optimization
	$('#wpspeed-bulk-optimize-start').on('click', function() {
		var $button = $(this);
		var $progress = $('#wpspeed-bulk-optimize-progress');
		
		$button.prop('disabled', true);
		$progress.show();
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpspeed_bulk_optimize',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpspeed-image-optimizer' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$('#wpspeed-progress-status').text('<?php esc_html_e( 'Images queued for optimization. Processing in background...', 'wp-speed-booster' ); ?>');
					// Show indeterminate progress since optimization runs in background
					$('#wpspeed-progress-bar').css('width', '100%');
					$('#wpspeed-progress-text').text('<?php esc_html_e( 'Processing...', 'wp-speed-booster' ); ?>');
					
					setTimeout(function() {
						$('#wpspeed-progress-status').html(
							'<strong style="color:#00a32a;">✓ <?php esc_html_e( 'Optimization Started!', 'wp-speed-booster' ); ?></strong><br>' +
							'<?php esc_html_e( 'Images are being optimized in the background. You can leave this page. Refresh to see updated statistics.', 'wp-speed-booster' ); ?>'
						);
						$button.prop('disabled', false).text('<?php esc_html_e( 'Refresh Statistics', 'wp-speed-booster' ); ?>');
						$button.off('click').on('click', function() {
							location.reload();
						});
					}, 2000);
				} else {
					alert(response.data.message);
					$button.prop('disabled', false);
					$progress.hide();
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'An error occurred. Please try again.', 'wp-speed-booster' ); ?>');
				$button.prop('disabled', false);
				$progress.hide();
			}
		});
	});
});
</script>
