<?php
/**
 * Admin Interface Class
 *
 * Handles admin dashboard and settings
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Admin class
 */
class WPSB_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_wpsb_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_wpsb_optimize_database', array( $this, 'ajax_optimize_database' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Get plugin setting with default fallback
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public static function get_setting( $key, $default = false ) {
		$settings = get_option( 'wpsb_options', array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'WP Speed Booster', 'wp-speed-booster' ),
			__( 'WP Speed Booster', 'wp-speed-booster' ),
			'manage_options',
			'wp-speed-booster',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'wpsb_options', 'wpsb_options', array(
			'sanitize_callback' => array( $this, 'sanitize_options' ),
		) );
	}

	/**
	 * Sanitize options
	 *
	 * @param array $input Input options.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		// Boolean options
		$boolean_options = array(
			'cache_enabled', 'mobile_cache', 'html_minify', 'css_minify', 'css_combine',
			'js_minify', 'js_combine', 'js_defer', 'remove_query_strings',
			'lazy_load_images', 'lazy_load_iframes',
			'db_clean_revisions', 'db_clean_autodrafts', 'db_clean_trash',
			'db_optimize_tables', 'db_clean_transients', 'db_clean_spam',
			'cdn_enabled', 'disable_emojis', 'disable_embeds', 'disable_jquery_migrate',
			'remove_wp_version', 'remove_rsd_links',
			'critical_css_enabled', 'critical_css_defer', 'critical_css_desktop', 'critical_css_mobile',
			'webp_enabled',
		);

		foreach ( $boolean_options as $option ) {
			$sanitized[ $option ] = ! empty( $input[ $option ] ) ? 1 : 0;
		}

		// Integer options
		$sanitized['cache_lifespan'] = ! empty( $input['cache_lifespan'] ) ? absint( $input['cache_lifespan'] ) : 36000;
		$sanitized['db_revisions_to_keep'] = ! empty( $input['db_revisions_to_keep'] ) ? absint( $input['db_revisions_to_keep'] ) : 3;
		$sanitized['lazy_load_skip_images'] = ! empty( $input['lazy_load_skip_images'] ) ? absint( $input['lazy_load_skip_images'] ) : 0;
		$sanitized['webp_quality'] = ! empty( $input['webp_quality'] ) ? absint( $input['webp_quality'] ) : 85;

		// Text options
		$sanitized['cache_exclude_urls'] = ! empty( $input['cache_exclude_urls'] ) ? sanitize_textarea_field( $input['cache_exclude_urls'] ) : '';
		$sanitized['minify_exclude_files'] = ! empty( $input['minify_exclude_files'] ) ? sanitize_textarea_field( $input['minify_exclude_files'] ) : '';
		$sanitized['lazy_load_exclude_class'] = ! empty( $input['lazy_load_exclude_class'] ) ? sanitize_text_field( $input['lazy_load_exclude_class'] ) : '';
		$sanitized['cdn_url'] = ! empty( $input['cdn_url'] ) ? esc_url_raw( $input['cdn_url'] ) : '';
		$sanitized['dns_prefetch'] = ! empty( $input['dns_prefetch'] ) ? sanitize_textarea_field( $input['dns_prefetch'] ) : '';
		$sanitized['db_auto_optimize'] = ! empty( $input['db_auto_optimize'] ) ? sanitize_text_field( $input['db_auto_optimize'] ) : 'disabled';
		$sanitized['critical_css_mode'] = ! empty( $input['critical_css_mode'] ) ? sanitize_text_field( $input['critical_css_mode'] ) : 'auto';
		$sanitized['critical_css_exclude'] = ! empty( $input['critical_css_exclude'] ) ? sanitize_textarea_field( $input['critical_css_exclude'] ) : '';
		// Sanitize CSS while preserving newlines
		$sanitized['critical_css_manual'] = ! empty( $input['critical_css_manual'] ) ? wp_kses( $input['critical_css_manual'], array() ) : '';

		return $sanitized;
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_wp-speed-booster' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpsb-admin',
			WPSB_URL . 'admin/admin.css',
			array(),
			WPSB_VERSION
		);

		wp_enqueue_script(
			'wpsb-admin',
			WPSB_URL . 'admin/admin.js',
			array( 'jquery' ),
			WPSB_VERSION,
			true
		);

		wp_localize_script( 'wpsb-admin', 'wpsbAdmin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wpsb_admin_nonce' ),
			'strings'  => array(
				'clearing'   => __( 'Clearing cache...', 'wp-speed-booster' ),
				'optimizing' => __( 'Optimizing database...', 'wp-speed-booster' ),
				'preloading' => __( 'Preloading cache...', 'wp-speed-booster' ),
				'success'    => __( 'Success!', 'wp-speed-booster' ),
				'error'      => __( 'Error occurred.', 'wp-speed-booster' ),
			),
		) );
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = get_option( 'wpsb_options', array() );
		?>
		<div class="wrap wpsb-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Single form wrapping ALL tabs -->
			<form method="post" action="options.php" class="wpspeed-settings-form" id="wpspeed-settings-form">
				<?php settings_fields( 'wpsb_options' ); ?>

				<!-- Tab Navigation -->
				<nav class="nav-tab-wrapper wpspeed-nav-tab-wrapper">
					<a href="#tab-dashboard" class="nav-tab wpspeed-nav-tab" data-tab="dashboard">
						<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-cache" class="nav-tab wpspeed-nav-tab" data-tab="cache">
						<span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Cache', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-optimization" class="nav-tab wpspeed-nav-tab" data-tab="optimization">
						<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Optimization', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-media" class="nav-tab wpspeed-nav-tab" data-tab="media">
						<span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Media', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-webp" class="nav-tab wpspeed-nav-tab" data-tab="webp">
						<span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'WebP Images', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-critical-css" class="nav-tab wpspeed-nav-tab" data-tab="critical-css">
						<span class="dashicons dashicons-media-code"></span> <?php esc_html_e( 'Critical CSS', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-database" class="nav-tab wpspeed-nav-tab" data-tab="database">
						<span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Database', 'wp-speed-booster' ); ?>
					</a>
					<a href="#tab-advanced" class="nav-tab wpspeed-nav-tab" data-tab="advanced">
						<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Advanced', 'wp-speed-booster' ); ?>
					</a>
				</nav>

				<!-- Tab Contents -->
				<div id="wpspeed-tab-dashboard" class="wpspeed-tab-content">
					<?php $this->render_dashboard_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-cache" class="wpspeed-tab-content">
					<?php $this->render_cache_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-optimization" class="wpspeed-tab-content">
					<?php $this->render_optimization_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-media" class="wpspeed-tab-content">
					<?php $this->render_media_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-webp" class="wpspeed-tab-content">
					<?php $this->render_webp_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-critical-css" class="wpspeed-tab-content">
					<?php $this->render_critical_css_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-database" class="wpspeed-tab-content">
					<?php $this->render_database_tab( $options ); ?>
				</div>

				<div id="wpspeed-tab-advanced" class="wpspeed-tab-content">
					<?php $this->render_advanced_tab( $options ); ?>
				</div>

				<!-- Single Save Button for All Settings -->
				<div class="wpspeed-save-settings">
					<?php submit_button( __( 'Save All Settings', 'wp-speed-booster' ), 'primary large', 'submit' ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render dashboard tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_dashboard_tab( $options ) {
		$cache = new WPSB_Cache();
		$cache_stats = $cache->get_cache_stats();
		$database = new WPSB_Database();
		$db_size = $database->get_database_size();
		?>
		<div class="wpsb-dashboard">
			<div class="wpsb-welcome">
				<h2><?php esc_html_e( 'Welcome to WP Speed Booster', 'wp-speed-booster' ); ?></h2>
				<p><?php esc_html_e( 'Optimize your WordPress website for better performance and faster loading times.', 'wp-speed-booster' ); ?></p>
			</div>

			<div class="wpsb-stats-grid">
				<div class="wpsb-stat-box">
					<h3><?php esc_html_e( 'Cache Status', 'wp-speed-booster' ); ?></h3>
					<div class="stat-value"><?php echo ! empty( $options['cache_enabled'] ) ? '<span class="status-enabled">✓ Enabled</span>' : '<span class="status-disabled">✗ Disabled</span>'; ?></div>
					<p><?php echo esc_html( sprintf( __( '%d cached files (%s)', 'wp-speed-booster' ), $cache_stats['files'], size_format( $cache_stats['size'] ) ) ); ?></p>
				</div>

				<div class="wpsb-stat-box">
					<h3><?php esc_html_e( 'Database', 'wp-speed-booster' ); ?></h3>
					<div class="stat-value"><?php echo esc_html( $db_size['formatted'] ); ?></div>
					<p><?php esc_html_e( 'Total database size', 'wp-speed-booster' ); ?></p>
				</div>

				<div class="wpsb-stat-box">
					<h3><?php esc_html_e( 'Optimization', 'wp-speed-booster' ); ?></h3>
					<div class="stat-value">
						<?php
						$active_features = 0;
						$features = array( 'html_minify', 'css_minify', 'js_minify', 'lazy_load_images' );
						foreach ( $features as $feature ) {
							if ( ! empty( $options[ $feature ] ) ) {
								$active_features++;
							}
						}
						echo esc_html( sprintf( __( '%d/%d', 'wp-speed-booster' ), $active_features, count( $features ) ) );
						?>
					</div>
					<p><?php esc_html_e( 'Active optimizations', 'wp-speed-booster' ); ?></p>
				</div>
			</div>

			<div class="wpsb-quick-actions">
				<h3><?php esc_html_e( 'Quick Actions', 'wp-speed-booster' ); ?></h3>
				<button type="button" class="button button-primary" id="wpsb-clear-cache-btn">
					<?php esc_html_e( 'Clear Cache', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="wpsb-optimize-db-btn">
					<?php esc_html_e( 'Optimize Database', 'wp-speed-booster' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="wpsb-preload-cache-btn">
					<?php esc_html_e( 'Preload Cache', 'wp-speed-booster' ); ?>
				</button>
			</div>

			<div id="wpsb-ajax-result" class="notice" style="display:none;"></div>
		</div>
		<?php
	}

	/**
	 * Render cache tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_cache_tab( $options ) {
		$cache = new WPSB_Cache();
		$cache_stats = $cache->get_cache_stats();
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Page Caching', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[cache_enabled]" value="1" <?php checked( 1, ! empty( $options['cache_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable file-based page caching', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Cache Lifespan', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="number" name="wpsb_options[cache_lifespan]" value="<?php echo esc_attr( ! empty( $options['cache_lifespan'] ) ? $options['cache_lifespan'] : 36000 ); ?>" min="0" />
					<p class="description"><?php esc_html_e( 'Cache lifespan in seconds (default: 36000 = 10 hours)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Mobile Cache', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[mobile_cache]" value="1" <?php checked( 1, ! empty( $options['mobile_cache'] ) ); ?> />
						<?php esc_html_e( 'Create separate cache for mobile devices', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude URLs', 'wp-speed-booster' ); ?></th>
				<td>
					<textarea name="wpsb_options[cache_exclude_urls]" rows="5" class="large-text"><?php echo esc_textarea( ! empty( $options['cache_exclude_urls'] ) ? $options['cache_exclude_urls'] : '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter URL patterns to exclude from caching, one per line. Example: /cart/, /checkout/', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
		</table>

		<div class="wpsb-cache-stats">
			<h3><?php esc_html_e( 'Cache Statistics', 'wp-speed-booster' ); ?></h3>
			<p><?php echo esc_html( sprintf( __( 'Cached files: %d', 'wp-speed-booster' ), $cache_stats['files'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Cache size: %s', 'wp-speed-booster' ), size_format( $cache_stats['size'] ) ) ); ?></p>
		</div>

		<div class="wpsb-quick-actions">
			<button type="button" class="button" id="wpsb-clear-cache-btn">
				<?php esc_html_e( 'Clear Cache', 'wp-speed-booster' ); ?>
			</button>
			<button type="button" class="button" id="wpsb-preload-cache-btn" style="margin-left: 10px;">
				<?php esc_html_e( 'Preload Cache', 'wp-speed-booster' ); ?>
			</button>
		</div>

		<div id="wpsb-ajax-result" class="notice" style="display:none;"></div>
		<?php
	}

	/**
	 * Render optimization tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_optimization_tab( $options ) {
		?>
		<h2><?php esc_html_e( 'HTML Minification', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minify HTML', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[html_minify]" value="1" <?php checked( 1, ! empty( $options['html_minify'] ) ); ?> />
						<?php esc_html_e( 'Remove whitespace and comments from HTML', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'CSS Optimization', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minify CSS', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[css_minify]" value="1" <?php checked( 1, ! empty( $options['css_minify'] ) ); ?> />
						<?php esc_html_e( 'Minify CSS files', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Combine CSS', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[css_combine]" value="1" <?php checked( 1, ! empty( $options['css_combine'] ) ); ?> />
						<?php esc_html_e( 'Combine CSS files into one (use with caution)', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'JavaScript Optimization', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minify JavaScript', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[js_minify]" value="1" <?php checked( 1, ! empty( $options['js_minify'] ) ); ?> />
						<?php esc_html_e( 'Minify JavaScript files', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Combine JavaScript', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[js_combine]" value="1" <?php checked( 1, ! empty( $options['js_combine'] ) ); ?> />
						<?php esc_html_e( 'Combine JavaScript files (use with caution)', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Defer JavaScript', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[js_defer]" value="1" <?php checked( 1, ! empty( $options['js_defer'] ) ); ?> />
						<?php esc_html_e( 'Defer non-critical JavaScript loading', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Other Optimizations', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove Query Strings', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[remove_query_strings]" value="1" <?php checked( 1, ! empty( $options['remove_query_strings'] ) ); ?> />
						<?php esc_html_e( 'Remove version query strings from static resources', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude Files', 'wp-speed-booster' ); ?></th>
				<td>
					<textarea name="wpsb_options[minify_exclude_files]" rows="5" class="large-text"><?php echo esc_textarea( ! empty( $options['minify_exclude_files'] ) ? $options['minify_exclude_files'] : '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter file paths or names to exclude from minification/optimization, one per line', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render media tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_media_tab( $options ) {
		?>
		<h2><?php esc_html_e( 'Lazy Loading', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load Images', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[lazy_load_images]" value="1" <?php checked( 1, ! empty( $options['lazy_load_images'] ) ); ?> />
						<?php esc_html_e( 'Enable lazy loading for images', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load iframes', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[lazy_load_iframes]" value="1" <?php checked( 1, ! empty( $options['lazy_load_iframes'] ) ); ?> />
						<?php esc_html_e( 'Enable lazy loading for iframes (YouTube, etc.)', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude by Class', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="text" name="wpsb_options[lazy_load_exclude_class]" value="<?php echo esc_attr( ! empty( $options['lazy_load_exclude_class'] ) ? $options['lazy_load_exclude_class'] : '' ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Comma-separated list of CSS classes to exclude from lazy loading', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Skip Images', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="number" name="wpsb_options[lazy_load_skip_images]" value="<?php echo esc_attr( ! empty( $options['lazy_load_skip_images'] ) ? $options['lazy_load_skip_images'] : 0 ); ?>" min="0" max="10" />
					<p class="description"><?php esc_html_e( 'Number of images to skip from lazy loading (above the fold)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render WebP tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_webp_tab( $options ) {
		// Include the tab view file
		if ( file_exists( WPSB_DIR . 'admin/views/tab-webp.php' ) ) {
			include WPSB_DIR . 'admin/views/tab-webp.php';
		}
	}

	/**
	 * Render critical CSS tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_critical_css_tab( $options ) {
		// Include the tab view file
		if ( file_exists( WPSB_DIR . 'admin/views/tab-critical-css.php' ) ) {
			include WPSB_DIR . 'admin/views/tab-critical-css.php';
		}
	}

	/**
	 * Render database tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_database_tab( $options ) {
		$database = new WPSB_Database();
		$stats = $database->get_statistics();
		$db_size = $database->get_database_size();
		?>
		<h2><?php esc_html_e( 'Database Optimization', 'wp-speed-booster' ); ?></h2>

		<div class="wpsb-db-stats">
			<h3><?php esc_html_e( 'Database Statistics', 'wp-speed-booster' ); ?></h3>
			<p><?php echo esc_html( sprintf( __( 'Database size: %s', 'wp-speed-booster' ), $db_size['formatted'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Post revisions: %d', 'wp-speed-booster' ), $stats['revisions'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Auto-drafts: %d', 'wp-speed-booster' ), $stats['autodrafts'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Trashed posts: %d', 'wp-speed-booster' ), $stats['trash_posts'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Spam comments: %d', 'wp-speed-booster' ), $stats['spam_comments'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Transients: %d', 'wp-speed-booster' ), $stats['transients'] ) ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Clean Revisions', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_clean_revisions]" value="1" <?php checked( 1, ! empty( $options['db_clean_revisions'] ) ); ?> />
						<?php esc_html_e( 'Clean old post revisions', 'wp-speed-booster' ); ?>
					</label>
					<br>
					<label>
						<?php esc_html_e( 'Keep last', 'wp-speed-booster' ); ?>
						<input type="number" name="wpsb_options[db_revisions_to_keep]" value="<?php echo esc_attr( ! empty( $options['db_revisions_to_keep'] ) ? $options['db_revisions_to_keep'] : 3 ); ?>" min="0" max="100" style="width: 60px;" />
						<?php esc_html_e( 'revisions per post', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Clean Auto-drafts', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_clean_autodrafts]" value="1" <?php checked( 1, ! empty( $options['db_clean_autodrafts'] ) ); ?> />
						<?php esc_html_e( 'Remove auto-draft posts', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Clean Trash', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_clean_trash]" value="1" <?php checked( 1, ! empty( $options['db_clean_trash'] ) ); ?> />
						<?php esc_html_e( 'Empty trash (posts and comments)', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Clean Transients', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_clean_transients]" value="1" <?php checked( 1, ! empty( $options['db_clean_transients'] ) ); ?> />
						<?php esc_html_e( 'Remove expired transient options', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Clean Spam Comments', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_clean_spam]" value="1" <?php checked( 1, ! empty( $options['db_clean_spam'] ) ); ?> />
						<?php esc_html_e( 'Remove spam comments', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Optimize Tables', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[db_optimize_tables]" value="1" <?php checked( 1, ! empty( $options['db_optimize_tables'] ) ); ?> />
						<?php esc_html_e( 'Optimize database tables', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Automatic Optimization', 'wp-speed-booster' ); ?></th>
				<td>
					<select name="wpsb_options[db_auto_optimize]">
						<option value="disabled" <?php selected( 'disabled', ! empty( $options['db_auto_optimize'] ) ? $options['db_auto_optimize'] : 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'wp-speed-booster' ); ?></option>
						<option value="daily" <?php selected( 'daily', ! empty( $options['db_auto_optimize'] ) ? $options['db_auto_optimize'] : '' ); ?>><?php esc_html_e( 'Daily', 'wp-speed-booster' ); ?></option>
						<option value="weekly" <?php selected( 'weekly', ! empty( $options['db_auto_optimize'] ) ? $options['db_auto_optimize'] : '' ); ?>><?php esc_html_e( 'Weekly', 'wp-speed-booster' ); ?></option>
						<option value="monthly" <?php selected( 'monthly', ! empty( $options['db_auto_optimize'] ) ? $options['db_auto_optimize'] : '' ); ?>><?php esc_html_e( 'Monthly', 'wp-speed-booster' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<div class="wpsb-quick-actions">
			<button type="button" class="button button-secondary" id="wpsb-optimize-db-btn">
				<?php esc_html_e( 'Run Optimization Now', 'wp-speed-booster' ); ?>
			</button>
		</div>

		<div id="wpsb-ajax-result" class="notice" style="display:none;"></div>
		<?php
	}

	/**
	 * Render advanced tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_advanced_tab( $options ) {
		?>
		<h2><?php esc_html_e( 'CDN Configuration', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable CDN', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[cdn_enabled]" value="1" <?php checked( 1, ! empty( $options['cdn_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable CDN for static assets', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'CDN URL', 'wp-speed-booster' ); ?></th>
				<td>
					<input type="url" name="wpsb_options[cdn_url]" value="<?php echo esc_url( ! empty( $options['cdn_url'] ) ? $options['cdn_url'] : '' ); ?>" class="regular-text" placeholder="https://cdn.example.com" />
					<p class="description"><?php esc_html_e( 'Enter your CDN URL (e.g., https://cdn.example.com)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'DNS Prefetch', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'DNS Prefetch Domains', 'wp-speed-booster' ); ?></th>
				<td>
					<textarea name="wpsb_options[dns_prefetch]" rows="5" class="large-text"><?php echo esc_textarea( ! empty( $options['dns_prefetch'] ) ? $options['dns_prefetch'] : '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter external domains for DNS prefetch, one per line (e.g., //fonts.googleapis.com)', 'wp-speed-booster' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Advanced Features', 'wp-speed-booster' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Emojis', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[disable_emojis]" value="1" <?php checked( 1, ! empty( $options['disable_emojis'] ) ); ?> />
						<?php esc_html_e( 'Remove emoji scripts and styles', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Embeds', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[disable_embeds]" value="1" <?php checked( 1, ! empty( $options['disable_embeds'] ) ); ?> />
						<?php esc_html_e( 'Disable WordPress embed functionality', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove jQuery Migrate', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[disable_jquery_migrate]" value="1" <?php checked( 1, ! empty( $options['disable_jquery_migrate'] ) ); ?> />
						<?php esc_html_e( 'Remove jQuery Migrate script', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove WP Version', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[remove_wp_version]" value="1" <?php checked( 1, ! empty( $options['remove_wp_version'] ) ); ?> />
						<?php esc_html_e( 'Remove WordPress version from head', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove RSD Links', 'wp-speed-booster' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpsb_options[remove_rsd_links]" value="1" <?php checked( 1, ! empty( $options['remove_rsd_links'] ) ); ?> />
						<?php esc_html_e( 'Remove RSD and WLW manifest links', 'wp-speed-booster' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * AJAX handler for clearing cache
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-speed-booster' ) ) );
		}

		$cache = new WPSB_Cache();
		$cache->clear_all_cache();

		wp_send_json_success( array( 'message' => __( 'Cache cleared successfully!', 'wp-speed-booster' ) ) );
	}

	/**
	 * AJAX handler for database optimization
	 */
	public function ajax_optimize_database() {
		check_ajax_referer( 'wpsb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-speed-booster' ) ) );
		}

		$database = new WPSB_Database();
		$results = $database->optimize_database();

		$message = __( 'Database optimized successfully!', 'wp-speed-booster' );
		if ( ! empty( $results ) ) {
			$details = array();
			if ( isset( $results['revisions'] ) ) {
				$details[] = sprintf( __( 'Revisions cleaned: %d', 'wp-speed-booster' ), $results['revisions'] );
			}
			if ( isset( $results['autodrafts'] ) ) {
				$details[] = sprintf( __( 'Auto-drafts removed: %d', 'wp-speed-booster' ), $results['autodrafts'] );
			}
			if ( isset( $results['transients'] ) ) {
				$details[] = sprintf( __( 'Transients cleaned: %d', 'wp-speed-booster' ), $results['transients'] );
			}
			if ( ! empty( $details ) ) {
				$message .= ' ' . implode( ', ', $details );
			}
		}

		wp_send_json_success( array( 'message' => $message, 'results' => $results ) );
	}

	/**
	 * Display admin notices
	 */
	public function admin_notices() {
		$screen = get_current_screen();
		if ( $screen && $screen->id !== 'settings_page_wp-speed-booster' ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings saved successfully!', 'wp-speed-booster' ); ?></p>
			</div>
			<?php
		}
	}
}
