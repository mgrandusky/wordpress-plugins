<?php
/**
 * Resource Hints Tab View
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$resource_hints = new VelocityWP_Resource_Hints();

$resource_hints_enabled = ! empty( $options['resource_hints_enabled'] );
$dns_prefetch_enabled   = ! empty( $options['dns_prefetch_enabled'] );
$dns_prefetch_auto      = ! empty( $options['dns_prefetch_auto'] );
$dns_prefetch_domains   = ! empty( $options['dns_prefetch_domains'] ) ? $options['dns_prefetch_domains'] : '';
$preconnect_enabled     = ! empty( $options['preconnect_enabled'] );
$preconnect_origins     = ! empty( $options['preconnect_origins'] ) ? $options['preconnect_origins'] : array();
$preload_enabled        = ! empty( $options['preload_enabled'] );
$preload_resources      = ! empty( $options['preload_resources'] ) ? $options['preload_resources'] : array();
$prefetch_enabled       = ! empty( $options['prefetch_enabled'] );
$prefetch_next_page     = ! empty( $options['prefetch_next_page'] );
$prefetch_urls          = ! empty( $options['prefetch_urls'] ) ? $options['prefetch_urls'] : '';
?>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Resource Hints Optimization', 'velocitywp' ); ?></h2>

	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'What are Resource Hints?', 'velocitywp' ); ?></strong></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong><?php esc_html_e( 'DNS Prefetch:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Resolves DNS early for faster resource loading (100-300ms faster)', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Preconnect:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Establishes connections early to critical origins (200-500ms faster fonts)', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Preload:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Loads critical resources immediately (better FCP and LCP)', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Prefetch:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Loads next-page resources in background (instant navigation)', 'velocitywp' ); ?></li>
		</ul>
	</div>
</div>

<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'General Settings', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Resource Hints', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[resource_hints_enabled]" value="1"
						<?php checked( $resource_hints_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable resource hints optimization', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Master toggle for all resource hints features', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- DNS Prefetch Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'DNS Prefetch', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable DNS Prefetch', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[dns_prefetch_enabled]" value="1"
						<?php checked( $dns_prefetch_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable DNS prefetch for external domains', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Resolves DNS early for third-party resources', 'velocitywp' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Auto-Detect Domains', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[dns_prefetch_auto]" value="1"
						<?php checked( $dns_prefetch_auto, 1 ); ?>>
					<?php esc_html_e( 'Automatically detect external domains from enqueued scripts and styles', 'velocitywp' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Manual Domains', 'velocitywp' ); ?></th>
			<td>
				<textarea name="velocitywp_options[dns_prefetch_domains]" rows="8" class="large-text"
					placeholder="fonts.googleapis.com&#10;www.google-analytics.com&#10;cdn.jsdelivr.net"><?php echo esc_textarea( $dns_prefetch_domains ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Enter one domain per line (without protocol).', 'velocitywp' ); ?><br>
					<strong><?php esc_html_e( 'Common domains:', 'velocitywp' ); ?></strong>
					fonts.googleapis.com, fonts.gstatic.com, www.google-analytics.com, www.googletagmanager.com, connect.facebook.net, platform.twitter.com, cdn.jsdelivr.net, cdnjs.cloudflare.com
				</p>
			</td>
		</tr>
	</table>
</div>

<!-- Preconnect Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Preconnect', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Preconnect', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[preconnect_enabled]" value="1"
						<?php checked( $preconnect_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable preconnect for critical origins', 'velocitywp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Establishes early connections to critical origins.', 'velocitywp' ); ?>
					<strong><?php esc_html_e( 'Limit to 4 most critical origins for best performance', 'velocitywp' ); ?></strong> <?php esc_html_e( '(browser best practice)', 'velocitywp' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<div class="velocitywp-resource-hints-table">
		<h3><?php esc_html_e( 'Preconnect Origins', 'velocitywp' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 60%;"><?php esc_html_e( 'URL', 'velocitywp' ); ?></th>
					<th style="width: 20%;"><?php esc_html_e( 'Crossorigin', 'velocitywp' ); ?></th>
					<th style="width: 20%;"><?php esc_html_e( 'Actions', 'velocitywp' ); ?></th>
				</tr>
			</thead>
			<tbody id="preconnect-origins-list">
				<?php
				if ( ! empty( $preconnect_origins ) ) {
					foreach ( $preconnect_origins as $index => $origin ) {
						?>
						<tr>
							<td>
								<input type="url" name="velocitywp_options[preconnect_origins][<?php echo esc_attr( $index ); ?>][url]"
									value="<?php echo esc_attr( $origin['url'] ); ?>" class="regular-text"
									placeholder="https://fonts.googleapis.com">
							</td>
							<td>
								<input type="checkbox" name="velocitywp_options[preconnect_origins][<?php echo esc_attr( $index ); ?>][crossorigin]"
									value="1" <?php checked( ! empty( $origin['crossorigin'] ), 1 ); ?>>
							</td>
							<td>
								<button type="button" class="button button-small remove-preconnect"><?php esc_html_e( 'Remove', 'velocitywp' ); ?></button>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<button type="button" class="button button-secondary" id="add-preconnect-origin">
			<?php esc_html_e( 'Add Preconnect Origin', 'velocitywp' ); ?>
		</button>

		<div style="margin-top: 15px;">
			<strong><?php esc_html_e( 'Quick Add:', 'velocitywp' ); ?></strong>
			<button type="button" class="button button-small quick-add-preconnect" data-url="https://fonts.googleapis.com"><?php esc_html_e( 'Google Fonts', 'velocitywp' ); ?></button>
			<button type="button" class="button button-small quick-add-preconnect" data-url="https://fonts.gstatic.com" data-crossorigin="1"><?php esc_html_e( 'Google Fonts Static', 'velocitywp' ); ?></button>
			<button type="button" class="button button-small quick-add-preconnect" data-url="https://www.google-analytics.com"><?php esc_html_e( 'Google Analytics', 'velocitywp' ); ?></button>
		</div>
	</div>
</div>

<!-- Preload Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Preload', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Preload', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[preload_enabled]" value="1"
						<?php checked( $preload_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable preload for critical resources', 'velocitywp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Loads critical resources immediately.', 'velocitywp' ); ?>
					<strong><?php esc_html_e( 'Only preload resources needed in first 3 seconds', 'velocitywp' ); ?></strong>
				</p>
			</td>
		</tr>
	</table>

	<div class="velocitywp-resource-hints-table">
		<h3><?php esc_html_e( 'Preload Resources', 'velocitywp' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 35%;"><?php esc_html_e( 'URL', 'velocitywp' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'As', 'velocitywp' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Type', 'velocitywp' ); ?></th>
					<th style="width: 10%;"><?php esc_html_e( 'Crossorigin', 'velocitywp' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Fetchpriority', 'velocitywp' ); ?></th>
					<th style="width: 10%;"><?php esc_html_e( 'Actions', 'velocitywp' ); ?></th>
				</tr>
			</thead>
			<tbody id="preload-resources-list">
				<?php
				if ( ! empty( $preload_resources ) ) {
					foreach ( $preload_resources as $index => $resource ) {
						?>
						<tr>
							<td>
								<input type="url" name="velocitywp_options[preload_resources][<?php echo esc_attr( $index ); ?>][url]"
									value="<?php echo esc_attr( $resource['url'] ); ?>" class="regular-text"
									placeholder="/fonts/myfont.woff2">
							</td>
							<td>
								<select name="velocitywp_options[preload_resources][<?php echo esc_attr( $index ); ?>][as]">
									<option value="font" <?php selected( $resource['as'], 'font' ); ?>><?php esc_html_e( 'Font', 'velocitywp' ); ?></option>
									<option value="style" <?php selected( $resource['as'], 'style' ); ?>><?php esc_html_e( 'Style', 'velocitywp' ); ?></option>
									<option value="script" <?php selected( $resource['as'], 'script' ); ?>><?php esc_html_e( 'Script', 'velocitywp' ); ?></option>
									<option value="image" <?php selected( $resource['as'], 'image' ); ?>><?php esc_html_e( 'Image', 'velocitywp' ); ?></option>
									<option value="fetch" <?php selected( $resource['as'], 'fetch' ); ?>><?php esc_html_e( 'Fetch', 'velocitywp' ); ?></option>
								</select>
							</td>
							<td>
								<input type="text" name="velocitywp_options[preload_resources][<?php echo esc_attr( $index ); ?>][type]"
									value="<?php echo esc_attr( ! empty( $resource['type'] ) ? $resource['type'] : '' ); ?>"
									placeholder="font/woff2" class="regular-text">
							</td>
							<td>
								<input type="checkbox" name="velocitywp_options[preload_resources][<?php echo esc_attr( $index ); ?>][crossorigin]"
									value="1" <?php checked( ! empty( $resource['crossorigin'] ), 1 ); ?>>
							</td>
							<td>
								<select name="velocitywp_options[preload_resources][<?php echo esc_attr( $index ); ?>][fetchpriority]">
									<option value="" <?php selected( empty( $resource['fetchpriority'] ), true ); ?>><?php esc_html_e( 'Auto', 'velocitywp' ); ?></option>
									<option value="high" <?php selected( ! empty( $resource['fetchpriority'] ) ? $resource['fetchpriority'] : '', 'high' ); ?>><?php esc_html_e( 'High', 'velocitywp' ); ?></option>
									<option value="low" <?php selected( ! empty( $resource['fetchpriority'] ) ? $resource['fetchpriority'] : '', 'low' ); ?>><?php esc_html_e( 'Low', 'velocitywp' ); ?></option>
								</select>
							</td>
							<td>
								<button type="button" class="button button-small remove-preload"><?php esc_html_e( 'Remove', 'velocitywp' ); ?></button>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<button type="button" class="button button-secondary" id="add-preload-resource">
			<?php esc_html_e( 'Add Preload Resource', 'velocitywp' ); ?>
		</button>
	</div>
</div>

<!-- Prefetch Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Prefetch', 'velocitywp' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Prefetch', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[prefetch_enabled]" value="1"
						<?php checked( $prefetch_enabled, 1 ); ?>>
					<?php esc_html_e( 'Enable prefetch for next-page resources', 'velocitywp' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Loads next-page resources in background for instant navigation', 'velocitywp' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Auto-Prefetch Next Page', 'velocitywp' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="velocitywp_options[prefetch_next_page]" value="1"
						<?php checked( $prefetch_next_page, 1 ); ?>>
					<?php esc_html_e( 'Automatically prefetch next page in pagination and next post', 'velocitywp' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Manual Prefetch URLs', 'velocitywp' ); ?></th>
			<td>
				<textarea name="velocitywp_options[prefetch_urls]" rows="6" class="large-text"
					placeholder="/about&#10;/contact&#10;/products"><?php echo esc_textarea( $prefetch_urls ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Enter one URL per line. Use relative URLs for same-site resources.', 'velocitywp' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<!-- Statistics Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Current Statistics', 'velocitywp' ); ?></h2>

	<div class="velocitywp-stats-grid">
		<div class="velocitywp-stat-box">
			<h3><?php esc_html_e( 'DNS Prefetch', 'velocitywp' ); ?></h3>
			<div class="stat-value">
				<?php
				$dns_count = 0;
				if ( $dns_prefetch_enabled ) {
					$domains = $resource_hints->get_dns_prefetch_domains();
					$dns_count = count( $domains );
				}
				echo esc_html( $dns_count );
				?>
			</div>
			<p><?php esc_html_e( 'domains', 'velocitywp' ); ?></p>
		</div>

		<div class="velocitywp-stat-box">
			<h3><?php esc_html_e( 'Preconnect', 'velocitywp' ); ?></h3>
			<div class="stat-value">
				<?php
				$preconnect_count = is_array( $preconnect_origins ) ? count( $preconnect_origins ) : 0;
				echo esc_html( $preconnect_count );
				?>
			</div>
			<p><?php esc_html_e( 'origins', 'velocitywp' ); ?></p>
		</div>

		<div class="velocitywp-stat-box">
			<h3><?php esc_html_e( 'Preload', 'velocitywp' ); ?></h3>
			<div class="stat-value">
				<?php
				$preload_count = is_array( $preload_resources ) ? count( $preload_resources ) : 0;
				echo esc_html( $preload_count );
				?>
			</div>
			<p><?php esc_html_e( 'resources', 'velocitywp' ); ?></p>
		</div>

		<div class="velocitywp-stat-box">
			<h3><?php esc_html_e( 'Prefetch', 'velocitywp' ); ?></h3>
			<div class="stat-value">
				<?php
				$prefetch_count = 0;
				if ( $prefetch_enabled ) {
					$urls = $resource_hints->get_prefetch_urls();
					$prefetch_count = count( $urls );
				}
				echo esc_html( $prefetch_count );
				?>
			</div>
			<p><?php esc_html_e( 'URLs', 'velocitywp' ); ?></p>
		</div>
	</div>
</div>

<!-- Best Practices Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Best Practices', 'velocitywp' ); ?></h2>

	<div class="notice notice-warning">
		<h3><?php esc_html_e( 'Tips for Optimal Use:', 'velocitywp' ); ?></h3>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong><?php esc_html_e( 'DNS Prefetch:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Use for domains you know will be needed. Great for analytics, fonts, and CDNs.', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Preconnect:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Limit to 4 most critical origins for optimal performance. Too many can slow down the page.', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Preload:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Only for resources needed in first 3 seconds. Use crossorigin for fonts.', 'velocitywp' ); ?></li>
			<li><strong><?php esc_html_e( 'Prefetch:', 'velocitywp' ); ?></strong> <?php esc_html_e( 'Use for high-probability next pages. Great for pagination and common navigation paths.', 'velocitywp' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Examples:', 'velocitywp' ); ?></h3>
		<p><strong><?php esc_html_e( 'Google Fonts:', 'velocitywp' ); ?></strong></p>
		<code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;">
			&lt;link rel="preconnect" href="https://fonts.googleapis.com"&gt;<br>
			&lt;link rel="preconnect" href="https://fonts.gstatic.com" crossorigin&gt;<br>
			&lt;link rel="preload" href="/fonts/myfont.woff2" as="font" type="font/woff2" crossorigin&gt;
		</code>

		<p><strong><?php esc_html_e( 'Next Page Prefetch:', 'velocitywp' ); ?></strong></p>
		<code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;">
			&lt;link rel="prefetch" href="/blog/page/2"&gt;
		</code>
	</div>
</div>

<!-- Browser Support Section -->
<div class="velocitywp-tab-section">
	<h2><?php esc_html_e( 'Browser Support', 'velocitywp' ); ?></h2>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Feature', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Chrome', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Firefox', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Safari', 'velocitywp' ); ?></th>
				<th><?php esc_html_e( 'Edge', 'velocitywp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'DNS Prefetch', 'velocitywp' ); ?></td>
				<td>✓ Chrome 46+</td>
				<td>✓ Firefox 39+</td>
				<td>✓ Safari 11.1+</td>
				<td>✓ Edge 79+</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Preconnect', 'velocitywp' ); ?></td>
				<td>✓ Chrome 46+</td>
				<td>✓ Firefox 39+</td>
				<td>✓ Safari 11.1+</td>
				<td>✓ Edge 79+</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Preload', 'velocitywp' ); ?></td>
				<td>✓ Chrome 50+</td>
				<td>✓ Firefox 85+</td>
				<td>✓ Safari 11.1+</td>
				<td>✓ Edge 79+</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Prefetch', 'velocitywp' ); ?></td>
				<td>✓ Chrome 46+</td>
				<td>✓ Firefox 39+</td>
				<td>✓ Safari 11.1+</td>
				<td>✓ Edge 79+</td>
			</tr>
		</tbody>
	</table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var preconnectIndex = <?php echo absint( count( $preconnect_origins ) ); ?>;
	var preloadIndex = <?php echo absint( count( $preload_resources ) ); ?>;

	// Add preconnect origin
	$('#add-preconnect-origin').on('click', function() {
		var row = '<tr>' +
			'<td><input type="url" name="velocitywp_options[preconnect_origins][' + preconnectIndex + '][url]" class="regular-text" placeholder="https://fonts.googleapis.com"></td>' +
			'<td><input type="checkbox" name="velocitywp_options[preconnect_origins][' + preconnectIndex + '][crossorigin]" value="1"></td>' +
			'<td><button type="button" class="button button-small remove-preconnect"><?php esc_html_e( 'Remove', 'velocitywp' ); ?></button></td>' +
			'</tr>';
		$('#preconnect-origins-list').append(row);
		preconnectIndex++;
	});

	// Remove preconnect origin
	$(document).on('click', '.remove-preconnect', function() {
		$(this).closest('tr').remove();
	});

	// Quick add preconnect
	$('.quick-add-preconnect').on('click', function() {
		var url = $(this).data('url');
		var crossorigin = $(this).data('crossorigin') ? 'checked' : '';
		var row = '<tr>' +
			'<td><input type="url" name="velocitywp_options[preconnect_origins][' + preconnectIndex + '][url]" value="' + url + '" class="regular-text"></td>' +
			'<td><input type="checkbox" name="velocitywp_options[preconnect_origins][' + preconnectIndex + '][crossorigin]" value="1" ' + crossorigin + '></td>' +
			'<td><button type="button" class="button button-small remove-preconnect"><?php esc_html_e( 'Remove', 'velocitywp' ); ?></button></td>' +
			'</tr>';
		$('#preconnect-origins-list').append(row);
		preconnectIndex++;
	});

	// Add preload resource
	$('#add-preload-resource').on('click', function() {
		var row = '<tr>' +
			'<td><input type="url" name="velocitywp_options[preload_resources][' + preloadIndex + '][url]" class="regular-text" placeholder="/fonts/myfont.woff2"></td>' +
			'<td><select name="velocitywp_options[preload_resources][' + preloadIndex + '][as]">' +
			'<option value="font"><?php esc_html_e( 'Font', 'velocitywp' ); ?></option>' +
			'<option value="style"><?php esc_html_e( 'Style', 'velocitywp' ); ?></option>' +
			'<option value="script"><?php esc_html_e( 'Script', 'velocitywp' ); ?></option>' +
			'<option value="image"><?php esc_html_e( 'Image', 'velocitywp' ); ?></option>' +
			'<option value="fetch"><?php esc_html_e( 'Fetch', 'velocitywp' ); ?></option>' +
			'</select></td>' +
			'<td><input type="text" name="velocitywp_options[preload_resources][' + preloadIndex + '][type]" placeholder="font/woff2" class="regular-text"></td>' +
			'<td><input type="checkbox" name="velocitywp_options[preload_resources][' + preloadIndex + '][crossorigin]" value="1"></td>' +
			'<td><select name="velocitywp_options[preload_resources][' + preloadIndex + '][fetchpriority]">' +
			'<option value=""><?php esc_html_e( 'Auto', 'velocitywp' ); ?></option>' +
			'<option value="high"><?php esc_html_e( 'High', 'velocitywp' ); ?></option>' +
			'<option value="low"><?php esc_html_e( 'Low', 'velocitywp' ); ?></option>' +
			'</select></td>' +
			'<td><button type="button" class="button button-small remove-preload"><?php esc_html_e( 'Remove', 'velocitywp' ); ?></button></td>' +
			'</tr>';
		$('#preload-resources-list').append(row);
		preloadIndex++;
	});

	// Remove preload resource
	$(document).on('click', '.remove-preload', function() {
		$(this).closest('tr').remove();
	});
});
</script>

<style>
.velocitywp-resource-hints-table {
	margin-top: 20px;
}

.velocitywp-resource-hints-table table {
	margin-bottom: 10px;
}

.velocitywp-resource-hints-table input[type="url"],
.velocitywp-resource-hints-table input[type="text"] {
	width: 100%;
}

.velocitywp-stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.velocitywp-stat-box {
	background: #fff;
	border: 1px solid #ccc;
	border-radius: 4px;
	padding: 20px;
	text-align: center;
}

.velocitywp-stat-box h3 {
	margin: 0 0 10px 0;
	font-size: 14px;
	color: #666;
}

.velocitywp-stat-box .stat-value {
	font-size: 32px;
	font-weight: bold;
	color: #2271b1;
	margin: 10px 0;
}

.velocitywp-stat-box p {
	margin: 0;
	color: #666;
	font-size: 12px;
}
</style>
