<?php
/**
 * Lazy Load Tab View
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$lazy_load_enabled = ! empty( $options['lazy_load_enabled'] ) ? 1 : 0;
$lazy_load_native = ! empty( $options['lazy_load_native'] ) ? 1 : 0;
$lazy_load_images = ! empty( $options['lazy_load_images'] ) ? 1 : 0;
$lazy_load_iframes = ! empty( $options['lazy_load_iframes'] ) ? 1 : 0;
$lazy_load_videos = ! empty( $options['lazy_load_videos'] ) ? 1 : 0;
$lazy_load_backgrounds = ! empty( $options['lazy_load_backgrounds'] ) ? 1 : 0;
$lazy_load_youtube = ! empty( $options['lazy_load_youtube'] ) ? 1 : 0;
$lazy_load_vimeo = ! empty( $options['lazy_load_vimeo'] ) ? 1 : 0;
$lazy_load_maps = ! empty( $options['lazy_load_maps'] ) ? 1 : 0;
$lazy_load_skip_first = ! empty( $options['lazy_load_skip_first'] ) ? intval( $options['lazy_load_skip_first'] ) : 0;
$lazy_load_exclude_classes = ! empty( $options['lazy_load_exclude_classes'] ) ? $options['lazy_load_exclude_classes'] : '';
$lazy_load_placeholder = ! empty( $options['lazy_load_placeholder'] ) ? $options['lazy_load_placeholder'] : 'transparent';
$lazy_load_fade_in = ! empty( $options['lazy_load_fade_in'] ) ? 1 : 0;
$lazy_load_fade_duration = ! empty( $options['lazy_load_fade_duration'] ) ? intval( $options['lazy_load_fade_duration'] ) : 300;
$lazy_load_threshold = ! empty( $options['lazy_load_threshold'] ) ? intval( $options['lazy_load_threshold'] ) : 200;
?>

<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Lazy Loading Enhancement', 'wp-speed-booster' ); ?></h2>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What is Lazy Loading?', 'wp-speed-booster' ); ?></strong></p>
		<p><?php esc_html_e( 'Lazy loading defers loading of images, iframes, and videos until they are needed (when they enter the viewport). This can significantly improve page load times, especially for pages with many images or embedded videos.', 'wp-speed-booster' ); ?></p>
		<p><strong><?php esc_html_e( 'Expected Impact:', 'wp-speed-booster' ); ?></strong></p>
		<ul>
			<li>⚡ <?php esc_html_e( '50-80% faster initial page load', 'wp-speed-booster' ); ?></li>
			<li>📊 <?php esc_html_e( '1-5 MB data savings per page', 'wp-speed-booster' ); ?></li>
			<li>🚀 <?php esc_html_e( 'Better LCP (if first images skipped)', 'wp-speed-booster' ); ?></li>
			<li>💾 <?php esc_html_e( '500KB+ saved per YouTube embed', 'wp-speed-booster' ); ?></li>
		</ul>
	</div>
</div>

<!-- Master Toggle -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Enable/Disable', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Lazy Loading', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_enabled]" value="1" <?php checked( $lazy_load_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable lazy loading features', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Master switch for all lazy loading features', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Implementation Method -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Implementation Method', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Method', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="radio" name="wpsb_options[lazy_load_native]" value="1" <?php checked( $lazy_load_native, 1 ); ?>>
					<?php esc_html_e( 'Native browser lazy loading (recommended)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" name="wpsb_options[lazy_load_native]" value="0" <?php checked( $lazy_load_native, 0 ); ?>>
					<?php esc_html_e( 'JavaScript with IntersectionObserver (fallback)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description">
					<strong><?php esc_html_e( 'Native:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Uses browser\'s built-in loading="lazy" - Faster, no JavaScript needed, but less control', 'wp-speed-booster' ); ?><br>
					<strong><?php esc_html_e( 'JavaScript:', 'wp-speed-booster' ); ?></strong> <?php esc_html_e( 'Uses IntersectionObserver API - More control, custom placeholders, animations, slightly more overhead', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Content Type Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'What to Lazy Load', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Content Types', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_images]" value="1" <?php checked( $lazy_load_images, 1 ); ?>>
					<?php esc_html_e( 'Images (recommended)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_iframes]" value="1" <?php checked( $lazy_load_iframes, 1 ); ?>>
					<?php esc_html_e( 'Iframes (recommended)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_videos]" value="1" <?php checked( $lazy_load_videos, 1 ); ?>>
					<?php esc_html_e( 'Videos (HTML5 video elements)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_backgrounds]" value="1" <?php checked( $lazy_load_backgrounds, 1 ); ?>>
					<?php esc_html_e( 'Background images (CSS)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Select which types of content to lazy load', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Image Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Image Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Skip First Images', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="number" name="wpsb_options[lazy_load_skip_first]" value="<?php echo esc_attr( $lazy_load_skip_first ); ?>" min="0" max="20" style="width: 80px;">
				<?php esc_html_e( 'images', 'wp-speed-booster' ); ?>
				<p class="description"><?php esc_html_e( 'Don\'t lazy load the first N images (recommended: 1-3 for better LCP)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Exclude Classes', 'wp-speed-booster' ); ?></th>
			<td>
				<textarea name="wpsb_options[lazy_load_exclude_classes]" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $lazy_load_exclude_classes ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'CSS classes to exclude from lazy loading (one per line)', 'wp-speed-booster' ); ?><br>
					<?php esc_html_e( 'Common examples: no-lazy, skip-lazy, eager-load, logo, header-image, wp-smiley, avatar', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Placeholder Type', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="radio" name="wpsb_options[lazy_load_placeholder]" value="transparent" <?php checked( $lazy_load_placeholder, 'transparent' ); ?>>
					<?php esc_html_e( 'Transparent (1x1 transparent GIF)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" name="wpsb_options[lazy_load_placeholder]" value="grey" <?php checked( $lazy_load_placeholder, 'grey' ); ?>>
					<?php esc_html_e( 'Grey (#f0f0f0 solid color)', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" name="wpsb_options[lazy_load_placeholder]" value="blur" <?php checked( $lazy_load_placeholder, 'blur' ); ?>>
					<?php esc_html_e( 'Blur-up (low-quality image placeholder)', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Placeholder shown while images load (JavaScript method only)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Effects', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_fade_in]" value="1" <?php checked( $lazy_load_fade_in, 1 ); ?>>
					<?php esc_html_e( 'Fade in on load', 'wp-speed-booster' ); ?>
				</label>
				<br>
				<label style="margin-left: 25px; display: inline-block; margin-top: 5px;">
					<?php esc_html_e( 'Fade duration:', 'wp-speed-booster' ); ?>
					<input type="number" name="wpsb_options[lazy_load_fade_duration]" value="<?php echo esc_attr( $lazy_load_fade_duration ); ?>" min="0" max="2000" step="100" style="width: 80px;">
					<?php esc_html_e( 'ms', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Smooth fade-in animation when images load (recommended: 300ms)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Load Threshold', 'wp-speed-booster' ); ?></th>
			<td>
				<input type="number" name="wpsb_options[lazy_load_threshold]" value="<?php echo esc_attr( $lazy_load_threshold ); ?>" min="0" max="1000" step="50" style="width: 80px;">
				<?php esc_html_e( 'pixels', 'wp-speed-booster' ); ?>
				<p class="description"><?php esc_html_e( 'Start loading images this many pixels before they enter viewport (recommended: 200px)', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Iframe Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Iframe Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'YouTube Embeds', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_youtube]" value="1" <?php checked( $lazy_load_youtube, 1 ); ?>>
					<?php esc_html_e( 'Lazy load YouTube videos with thumbnail preview', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Saves ~500KB per video embed! Shows thumbnail with play button instead of full iframe.', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Vimeo Embeds', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_vimeo]" value="1" <?php checked( $lazy_load_vimeo, 1 ); ?>>
					<?php esc_html_e( 'Lazy load Vimeo videos with placeholder', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Shows placeholder with play button instead of full iframe', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><?php esc_html_e( 'Google Maps', 'wp-speed-booster' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpsb_options[lazy_load_maps]" value="1" <?php checked( $lazy_load_maps, 1 ); ?>>
					<?php esc_html_e( 'Lazy load Google Maps with "Load Map" button', 'wp-speed-booster' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Shows placeholder with button instead of loading the full map immediately', 'wp-speed-booster' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Video Settings -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Video Settings', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'HTML5 Videos', 'wp-speed-booster' ); ?></th>
			<td>
				<p class="description">
					<?php esc_html_e( 'When video lazy loading is enabled, the plugin sets preload="none" on video elements to prevent them from downloading until played.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Background Images -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Background Images', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'CSS Backgrounds', 'wp-speed-booster' ); ?></th>
			<td>
				<p class="description">
					<?php esc_html_e( 'When background lazy loading is enabled, use the data-bg attribute instead of inline styles:', 'wp-speed-booster' ); ?>
				</p>
				<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; margin-top: 10px;">
<?php esc_html_e( 'Instead of:', 'wp-speed-booster' ); ?>
&lt;div style="background-image: url(image.jpg)"&gt;&lt;/div&gt;

<?php esc_html_e( 'Use:', 'wp-speed-booster' ); ?>
&lt;div data-bg="image.jpg"&gt;&lt;/div&gt;
				</pre>
			</td>
		</tr>
	</table>
</div>

<!-- Browser Compatibility -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Browser Compatibility', 'wp-speed-booster' ); ?></h2>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Native Lazy Loading Support', 'wp-speed-booster' ); ?></th>
			<td>
				<table style="border-collapse: collapse; width: 100%; max-width: 600px;">
					<thead>
						<tr style="background: #f5f5f5;">
							<th style="padding: 8px; text-align: left; border: 1px solid #ddd;"><?php esc_html_e( 'Browser', 'wp-speed-booster' ); ?></th>
							<th style="padding: 8px; text-align: left; border: 1px solid #ddd;"><?php esc_html_e( 'Version', 'wp-speed-booster' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="padding: 8px; border: 1px solid #ddd;">Chrome</td>
							<td style="padding: 8px; border: 1px solid #ddd;">76+</td>
						</tr>
						<tr>
							<td style="padding: 8px; border: 1px solid #ddd;">Firefox</td>
							<td style="padding: 8px; border: 1px solid #ddd;">75+</td>
						</tr>
						<tr>
							<td style="padding: 8px; border: 1px solid #ddd;">Safari</td>
							<td style="padding: 8px; border: 1px solid #ddd;">15.4+</td>
						</tr>
						<tr>
							<td style="padding: 8px; border: 1px solid #ddd;">Edge</td>
							<td style="padding: 8px; border: 1px solid #ddd;">79+</td>
						</tr>
					</tbody>
				</table>
				<p class="description" style="margin-top: 10px;">
					<?php esc_html_e( 'JavaScript method works in all modern browsers with IntersectionObserver support (95%+ coverage). Older browsers will load all images immediately as fallback.', 'wp-speed-booster' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Common Scenarios -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Recommended Settings by Use Case', 'wp-speed-booster' ); ?></h2>
	
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
		<!-- Blog Post -->
		<div style="border: 1px solid #ddd; padding: 15px; border-radius: 3px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( '📝 Blog Post', 'wp-speed-booster' ); ?></h3>
			<ul style="margin-left: 20px;">
				<li><?php esc_html_e( 'Skip first: 2', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Lazy load: All others', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'YouTube: Yes', 'wp-speed-booster' ); ?></li>
			</ul>
			<p style="color: #0073aa; margin-bottom: 0;"><strong><?php esc_html_e( 'Impact: 75% faster initial load', 'wp-speed-booster' ); ?></strong></p>
		</div>
		
		<!-- Gallery Page -->
		<div style="border: 1px solid #ddd; padding: 15px; border-radius: 3px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( '🖼️ Gallery Page', 'wp-speed-booster' ); ?></h3>
			<ul style="margin-left: 20px;">
				<li><?php esc_html_e( 'Skip first: 4', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Lazy load: Gallery images', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Threshold: 400px', 'wp-speed-booster' ); ?></li>
			</ul>
			<p style="color: #0073aa; margin-bottom: 0;"><strong><?php esc_html_e( 'Impact: 90% data savings on load', 'wp-speed-booster' ); ?></strong></p>
		</div>
		
		<!-- Landing Page -->
		<div style="border: 1px solid #ddd; padding: 15px; border-radius: 3px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( '🎯 Landing Page', 'wp-speed-booster' ); ?></h3>
			<ul style="margin-left: 20px;">
				<li><?php esc_html_e( 'Skip first: 1', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Lazy load: Below-fold', 'wp-speed-booster' ); ?></li>
				<li><?php esc_html_e( 'Fade in: Yes', 'wp-speed-booster' ); ?></li>
			</ul>
			<p style="color: #0073aa; margin-bottom: 0;"><strong><?php esc_html_e( 'Impact: 60% faster initial load', 'wp-speed-booster' ); ?></strong></p>
		</div>
	</div>
</div>

<!-- FAQ Section -->
<div class="wpspeed-tab-section">
	<h2><?php esc_html_e( 'Frequently Asked Questions', 'wp-speed-booster' ); ?></h2>
	
	<div style="background: #f9f9f9; padding: 20px; border-radius: 3px;">
		<h4 style="margin-top: 0;"><?php esc_html_e( 'Q: Will this hurt my SEO?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: No. Search engines can read lazy-loaded images. In fact, faster page loads can improve SEO rankings.', 'wp-speed-booster' ); ?></p>
		
		<h4><?php esc_html_e( 'Q: What about Largest Contentful Paint (LCP)?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Skip the first 1-3 images to ensure your hero image and above-the-fold content loads immediately. This prevents negative LCP impact.', 'wp-speed-booster' ); ?></p>
		
		<h4><?php esc_html_e( 'Q: Does it work with page builders?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Yes, works with Elementor, Divi, WPBakery, Beaver Builder, and other popular page builders.', 'wp-speed-booster' ); ?></p>
		
		<h4><?php esc_html_e( 'Q: Will it break my theme?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Rarely, but if you encounter issues, use the exclusion classes to skip specific images or elements.', 'wp-speed-booster' ); ?></p>
		
		<h4><?php esc_html_e( 'Q: What about Cumulative Layout Shift (CLS)?', 'wp-speed-booster' ); ?></h4>
		<p><?php esc_html_e( 'A: Make sure your images have width and height attributes set. This prevents layout shift when images load.', 'wp-speed-booster' ); ?></p>
	</div>
</div>
