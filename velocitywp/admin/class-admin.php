<?php
/**
 * Admin Interface Class
 *
 * Handles admin dashboard and settings
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Admin class
 */
class VelocityWP_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_velocitywp_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_velocitywp_optimize_database', array( $this, 'ajax_optimize_database' ) );
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
		$settings = get_option( 'velocitywp_options', array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'VelocityWP', 'velocitywp' ),
			__( 'VelocityWP', 'velocitywp' ),
			'manage_options',
			'velocitywp',
			array( $this, 'render_admin_page' ),
			'dashicons-performance',
			59
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'velocitywp_options', 'velocitywp_options', array(
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
			'lazy_load_enabled', 'lazy_load_native', 'lazy_load_images', 'lazy_load_iframes',
			'lazy_load_videos', 'lazy_load_backgrounds', 'lazy_load_youtube', 'lazy_load_vimeo',
			'lazy_load_maps', 'lazy_load_fade_in',
			'db_clean_revisions', 'db_clean_autodrafts', 'db_clean_trash',
			'db_optimize_tables', 'db_clean_transients', 'db_clean_spam',
			'db_optimization_enabled', 'db_optimize_email_report',
			'cdn_enabled', 'disable_emojis', 'disable_embeds', 'disable_jquery_migrate',
			'remove_wp_version', 'remove_rsd_links',
			'critical_css_enabled', 'critical_css_defer', 'critical_css_desktop', 'critical_css_mobile',
			'webp_enabled',
			'js_delay_enabled', 'js_defer_enabled',
			'font_optimization_enabled', 'local_google_fonts', 'font_preconnect', 'font_dns_prefetch',
			'fragment_cache_enabled', 'cache_widgets', 'cache_sidebars', 'cache_menus', 'cache_shortcodes', 'fragment_cache_logged_in',
			'resource_hints_enabled', 'dns_prefetch_enabled', 'dns_prefetch_auto', 'preconnect_enabled', 'preload_enabled', 'prefetch_enabled', 'prefetch_next_page',
			'cloudflare_enabled', 'cloudflare_purge_on_update', 'cloudflare_purge_on_comment', 'cloudflare_restore_ip',
			'heartbeat_control_enabled', 'heartbeat_disable_completely', 'heartbeat_disable_frontend', 'heartbeat_disable_admin', 'heartbeat_disable_editor',
			'heartbeat_allow_post_locking', 'heartbeat_allow_autosave', 'heartbeat_track_activity',
			'performance_monitoring_enabled', 'performance_track_rum', 'performance_track_server', 'performance_debug_comments',
			// WooCommerce options
			'woo_optimization_enabled', 'woo_disable_cart_fragments', 'woo_remove_scripts', 'woo_load_everywhere',
			'woo_optimize_checkout', 'woo_disable_password_strength', 'woo_disable_blocks', 'woo_disable_reviews',
			'woo_remove_generator', 'woo_disable_admin_bar_cart', 'woo_optimize_widgets', 'woo_optimize_transients',
			'woo_optimize_sessions', 'woo_disable_geolocation',
		);

		foreach ( $boolean_options as $option ) {
			$sanitized[ $option ] = ! empty( $input[ $option ] ) ? 1 : 0;
		}

		// Integer options
		$sanitized['cache_lifespan'] = ! empty( $input['cache_lifespan'] ) ? absint( $input['cache_lifespan'] ) : 36000;
		$sanitized['db_revisions_to_keep'] = ! empty( $input['db_revisions_to_keep'] ) ? absint( $input['db_revisions_to_keep'] ) : 3;
		$sanitized['keep_revisions'] = ! empty( $input['keep_revisions'] ) ? absint( $input['keep_revisions'] ) : 0;
		$sanitized['lazy_load_skip_images'] = ! empty( $input['lazy_load_skip_images'] ) ? absint( $input['lazy_load_skip_images'] ) : 0;
		$sanitized['lazy_load_skip_first'] = ! empty( $input['lazy_load_skip_first'] ) ? absint( $input['lazy_load_skip_first'] ) : 0;
		$sanitized['lazy_load_threshold'] = ! empty( $input['lazy_load_threshold'] ) ? absint( $input['lazy_load_threshold'] ) : 200;
		$sanitized['lazy_load_fade_duration'] = ! empty( $input['lazy_load_fade_duration'] ) ? absint( $input['lazy_load_fade_duration'] ) : 300;
		$sanitized['webp_quality'] = ! empty( $input['webp_quality'] ) ? absint( $input['webp_quality'] ) : 85;
		$sanitized['js_delay_timeout'] = ! empty( $input['js_delay_timeout'] ) ? absint( $input['js_delay_timeout'] ) : 5;
		$sanitized['heartbeat_frontend_frequency'] = ! empty( $input['heartbeat_frontend_frequency'] ) ? absint( $input['heartbeat_frontend_frequency'] ) : 60;
		$sanitized['heartbeat_admin_frequency'] = ! empty( $input['heartbeat_admin_frequency'] ) ? absint( $input['heartbeat_admin_frequency'] ) : 60;
		$sanitized['heartbeat_editor_frequency'] = ! empty( $input['heartbeat_editor_frequency'] ) ? absint( $input['heartbeat_editor_frequency'] ) : 15;
		$sanitized['performance_data_retention'] = ! empty( $input['performance_data_retention'] ) ? absint( $input['performance_data_retention'] ) : 30;
		$sanitized['performance_sample_rate'] = ! empty( $input['performance_sample_rate'] ) ? absint( $input['performance_sample_rate'] ) : 100;

		// WooCommerce integer options
		$sanitized['woo_cart_fragment_lifetime'] = ! empty( $input['woo_cart_fragment_lifetime'] ) ? absint( $input['woo_cart_fragment_lifetime'] ) : 86400;

		// Text options
		$sanitized['cache_exclude_urls'] = ! empty( $input['cache_exclude_urls'] ) ? sanitize_textarea_field( $input['cache_exclude_urls'] ) : '';
		$sanitized['minify_exclude_files'] = ! empty( $input['minify_exclude_files'] ) ? sanitize_textarea_field( $input['minify_exclude_files'] ) : '';
		$sanitized['lazy_load_exclude_class'] = ! empty( $input['lazy_load_exclude_class'] ) ? sanitize_text_field( $input['lazy_load_exclude_class'] ) : '';
		$sanitized['lazy_load_exclude_classes'] = ! empty( $input['lazy_load_exclude_classes'] ) ? sanitize_textarea_field( $input['lazy_load_exclude_classes'] ) : '';
		$sanitized['lazy_load_placeholder'] = ! empty( $input['lazy_load_placeholder'] ) ? sanitize_text_field( $input['lazy_load_placeholder'] ) : 'transparent';
		$sanitized['cdn_url'] = ! empty( $input['cdn_url'] ) ? esc_url_raw( $input['cdn_url'] ) : '';
		$sanitized['dns_prefetch'] = ! empty( $input['dns_prefetch'] ) ? sanitize_textarea_field( $input['dns_prefetch'] ) : '';
		$sanitized['db_auto_optimize'] = ! empty( $input['db_auto_optimize'] ) ? sanitize_text_field( $input['db_auto_optimize'] ) : 'disabled';
		$sanitized['db_optimize_schedule'] = ! empty( $input['db_optimize_schedule'] ) ? sanitize_text_field( $input['db_optimize_schedule'] ) : 'weekly';
		$sanitized['critical_css_mode'] = ! empty( $input['critical_css_mode'] ) ? sanitize_text_field( $input['critical_css_mode'] ) : 'auto';
		$sanitized['critical_css_exclude'] = ! empty( $input['critical_css_exclude'] ) ? sanitize_textarea_field( $input['critical_css_exclude'] ) : '';
		$sanitized['critical_css_api_key'] = ! empty( $input['critical_css_api_key'] ) ? sanitize_text_field( $input['critical_css_api_key'] ) : '';
		$sanitized['critical_css_api_provider'] = ! empty( $input['critical_css_api_provider'] ) ? sanitize_text_field( $input['critical_css_api_provider'] ) : 'criticalcss';
		$sanitized['critical_css_exclude_handles'] = ! empty( $input['critical_css_exclude_handles'] ) ? sanitize_text_field( $input['critical_css_exclude_handles'] ) : '';
		$sanitized['critical_css_defer_method'] = ! empty( $input['critical_css_defer_method'] ) ? sanitize_text_field( $input['critical_css_defer_method'] ) : 'media-print';
		$sanitized['critical_css_mobile_separate'] = ! empty( $input['critical_css_mobile_separate'] ) ? 1 : 0;
		// Sanitize CSS while preserving newlines
		$sanitized['critical_css_manual'] = ! empty( $input['critical_css_manual'] ) ? wp_kses( $input['critical_css_manual'], array() ) : '';
		
		// Database optimization array options
		$sanitized['db_optimize_operations'] = ! empty( $input['db_optimize_operations'] ) && is_array( $input['db_optimize_operations'] ) ? 
			array_map( 'sanitize_text_field', $input['db_optimize_operations'] ) : array();

		// JavaScript Delay options
		$sanitized['js_delay_exclude'] = ! empty( $input['js_delay_exclude'] ) ? sanitize_textarea_field( $input['js_delay_exclude'] ) : '';
		$sanitized['js_delay_events'] = ! empty( $input['js_delay_events'] ) ? sanitize_text_field( $input['js_delay_events'] ) : 'mousemove,scroll,touchstart,click,keydown';

		// WooCommerce text options
		$sanitized['woo_disable_cart_fragments_on'] = ! empty( $input['woo_disable_cart_fragments_on'] ) ? sanitize_text_field( $input['woo_disable_cart_fragments_on'] ) : '';

		// Performance Metrics options
		$sanitized['pagespeed_api_key'] = ! empty( $input['pagespeed_api_key'] ) ? sanitize_text_field( $input['pagespeed_api_key'] ) : '';
		$sanitized['performance_monitor_urls'] = ! empty( $input['performance_monitor_urls'] ) ? sanitize_textarea_field( $input['performance_monitor_urls'] ) : '';
		$sanitized['performance_check_frequency'] = ! empty( $input['performance_check_frequency'] ) ? sanitize_text_field( $input['performance_check_frequency'] ) : 'weekly';
		$sanitized['performance_alert_threshold'] = ! empty( $input['performance_alert_threshold'] ) ? absint( $input['performance_alert_threshold'] ) : 70;

		// Font optimization options
		$sanitized['font_display'] = ! empty( $input['font_display'] ) ? sanitize_text_field( $input['font_display'] ) : 'swap';
		$sanitized['font_preload_urls'] = ! empty( $input['font_preload_urls'] ) ? sanitize_textarea_field( $input['font_preload_urls'] ) : '';

		// Object Cache options
		$sanitized['object_cache_backend'] = ! empty( $input['object_cache_backend'] ) ? sanitize_text_field( $input['object_cache_backend'] ) : 'auto';
		$sanitized['redis_host'] = ! empty( $input['redis_host'] ) ? sanitize_text_field( $input['redis_host'] ) : '127.0.0.1';
		$sanitized['redis_port'] = ! empty( $input['redis_port'] ) ? absint( $input['redis_port'] ) : 6379;
		$sanitized['redis_password'] = ! empty( $input['redis_password'] ) ? sanitize_text_field( $input['redis_password'] ) : '';
		$sanitized['redis_database'] = ! empty( $input['redis_database'] ) ? absint( $input['redis_database'] ) : 0;
		$sanitized['memcached_servers'] = ! empty( $input['memcached_servers'] ) ? sanitize_textarea_field( $input['memcached_servers'] ) : '127.0.0.1:11211';

		// Fragment Cache options
		$sanitized['fragment_cache_time'] = ! empty( $input['fragment_cache_time'] ) ? absint( $input['fragment_cache_time'] ) : 3600;
		$sanitized['cached_widget_list'] = ! empty( $input['cached_widget_list'] ) && is_array( $input['cached_widget_list'] ) ? array_map( 'sanitize_text_field', $input['cached_widget_list'] ) : array();
		$sanitized['cached_sidebar_list'] = ! empty( $input['cached_sidebar_list'] ) && is_array( $input['cached_sidebar_list'] ) ? array_map( 'sanitize_text_field', $input['cached_sidebar_list'] ) : array();
		$sanitized['cached_menu_list'] = ! empty( $input['cached_menu_list'] ) && is_array( $input['cached_menu_list'] ) ? array_map( 'sanitize_text_field', $input['cached_menu_list'] ) : array();
		$sanitized['cached_shortcode_list'] = ! empty( $input['cached_shortcode_list'] ) ? sanitize_textarea_field( $input['cached_shortcode_list'] ) : '';

		// Resource Hints options
		$sanitized['dns_prefetch_domains'] = ! empty( $input['dns_prefetch_domains'] ) ? sanitize_textarea_field( $input['dns_prefetch_domains'] ) : '';
		$sanitized['prefetch_urls'] = ! empty( $input['prefetch_urls'] ) ? sanitize_textarea_field( $input['prefetch_urls'] ) : '';

		// Cloudflare options
		$sanitized['cloudflare_auth_type'] = ! empty( $input['cloudflare_auth_type'] ) ? sanitize_text_field( $input['cloudflare_auth_type'] ) : 'token';
		$sanitized['cloudflare_api_token'] = ! empty( $input['cloudflare_api_token'] ) ? sanitize_text_field( $input['cloudflare_api_token'] ) : '';
		$sanitized['cloudflare_email'] = ! empty( $input['cloudflare_email'] ) ? sanitize_email( $input['cloudflare_email'] ) : '';
		$sanitized['cloudflare_api_key'] = ! empty( $input['cloudflare_api_key'] ) ? sanitize_text_field( $input['cloudflare_api_key'] ) : '';
		$sanitized['cloudflare_zone_id'] = ! empty( $input['cloudflare_zone_id'] ) ? sanitize_text_field( $input['cloudflare_zone_id'] ) : '';
		
		// Preconnect origins (array)
		$sanitized['preconnect_origins'] = array();
		if ( ! empty( $input['preconnect_origins'] ) && is_array( $input['preconnect_origins'] ) ) {
			foreach ( $input['preconnect_origins'] as $origin ) {
				if ( ! empty( $origin['url'] ) ) {
					$sanitized['preconnect_origins'][] = array(
						'url' => esc_url_raw( $origin['url'] ),
						'crossorigin' => ! empty( $origin['crossorigin'] ) ? 1 : 0,
					);
				}
			}
		}
		
		// Preload resources (array)
		$sanitized['preload_resources'] = array();
		if ( ! empty( $input['preload_resources'] ) && is_array( $input['preload_resources'] ) ) {
			foreach ( $input['preload_resources'] as $resource ) {
				if ( ! empty( $resource['url'] ) && ! empty( $resource['as'] ) ) {
					$res = array(
						'url' => esc_url_raw( $resource['url'] ),
						'as' => sanitize_text_field( $resource['as'] ),
					);
					if ( ! empty( $resource['type'] ) ) {
						$res['type'] = sanitize_text_field( $resource['type'] );
					}
					if ( ! empty( $resource['crossorigin'] ) ) {
						$res['crossorigin'] = 1;
					}
					if ( ! empty( $resource['fetchpriority'] ) ) {
						$res['fetchpriority'] = sanitize_text_field( $resource['fetchpriority'] );
					}
					$sanitized['preload_resources'][] = $res;
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_velocitywp' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'velocitywp-admin',
			WPSB_URL . 'admin/admin.css',
			array(),
			WPSB_VERSION
		);

		wp_enqueue_script(
			'velocitywp-admin',
			WPSB_URL . 'admin/admin.js',
			array( 'jquery' ),
			WPSB_VERSION,
			true
		);

		// Enqueue Chart.js for performance metrics dashboard
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js',
			array(),
			'3.9.1',
			true
		);

		wp_localize_script( 'velocitywp-admin', 'velocitywpAdmin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'velocitywp_admin_nonce' ),
			'performance_nonce' => wp_create_nonce( 'velocitywp_performance' ),
			'strings'  => array(
				'clearing'   => __( 'Clearing cache...', 'velocitywp' ),
				'optimizing' => __( 'Optimizing database...', 'velocitywp' ),
				'preloading' => __( 'Preloading cache...', 'velocitywp' ),
				'success'    => __( 'Success!', 'velocitywp' ),
				'error'      => __( 'Error occurred.', 'velocitywp' ),
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

		$options = get_option( 'velocitywp_options', array() );
		$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'fonts';
		?>
		<div class="wrap velocitywp-wrap">
			<div class="velocitywp-container">
				<!-- Left Navigation Sidebar -->
				<?php include VELOCITYWP_PLUGIN_DIR . 'admin/partials/admin-navigation.php'; ?>
				
				<!-- Main Content Area -->
				<div class="velocitywp-content">
					<form method="post" action="options.php" class="velocitywp-form">
						<?php settings_fields( 'velocitywp_options' ); ?>
						
						<!-- Page Header -->
						<?php include VELOCITYWP_PLUGIN_DIR . 'admin/partials/admin-header.php'; ?>
						
						<!-- Tab Content -->
						<div class="velocitywp-tab-content">
							<?php $this->render_tab_content($current_tab, $options); ?>
						</div>
						
						<!-- Save Button (Bottom) -->
						<div class="velocitywp-form-footer">
							<?php submit_button( __( 'Save Changes', 'velocitywp' ), 'primary large', 'submit' ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render tab content based on current tab
	 *
	 * @param string $tab Current tab.
	 * @param array $options Plugin options.
	 */
	private function render_tab_content($tab, $options) {
		switch ($tab) {
			case 'fonts':
				$this->render_fonts_tab($options);
				break;
			case 'object-cache':
				$this->render_object_cache_tab($options);
				break;
			case 'fragment-cache':
				$this->render_fragment_cache_tab($options);
				break;
			case 'resource-hints':
				$this->render_resource_hints_tab($options);
				break;
			case 'cloudflare':
				$this->render_cloudflare_tab($options);
				break;
			case 'database':
				$this->render_database_tab($options);
				break;
			case 'heartbeat':
				$this->render_heartbeat_tab($options);
				break;
			case 'lazy-load':
				$this->render_lazy_load_tab($options);
				break;
			case 'performance':
				$this->render_performance_tab($options);
				break;
			case 'performance-metrics':
				$this->render_performance_metrics_tab($options);
				break;
			case 'woocommerce':
				$this->render_woocommerce_tab($options);
				break;
			case 'critical-css':
				$this->render_critical_css_tab($options);
				break;
			case 'webp':
				$this->render_webp_tab($options);
				break;
			default:
				$this->render_fonts_tab($options);
				break;
		}
	}

	/**
	 * Render dashboard tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_dashboard_tab( $options ) {
		$cache = new VelocityWP_Cache();
		$cache_stats = $cache->get_cache_stats();
		$database = new VelocityWP_Database();
		$db_size = $database->get_database_size();
		?>
		<div class="velocitywp-dashboard">
			<div class="velocitywp-welcome">
				<h2><?php esc_html_e( 'Welcome to VelocityWP', 'velocitywp' ); ?></h2>
				<p><?php esc_html_e( 'Optimize your WordPress website for better performance and faster loading times.', 'velocitywp' ); ?></p>
			</div>

			<div class="velocitywp-stats-grid">
				<div class="velocitywp-stat-box">
					<h3><?php esc_html_e( 'Cache Status', 'velocitywp' ); ?></h3>
					<div class="stat-value"><?php echo ! empty( $options['cache_enabled'] ) ? '<span class="status-enabled">✓ Enabled</span>' : '<span class="status-disabled">✗ Disabled</span>'; ?></div>
					<p><?php echo esc_html( sprintf( __( '%d cached files (%s)', 'velocitywp' ), $cache_stats['files'], size_format( $cache_stats['size'] ) ) ); ?></p>
				</div>

				<div class="velocitywp-stat-box">
					<h3><?php esc_html_e( 'Database', 'velocitywp' ); ?></h3>
					<div class="stat-value"><?php echo esc_html( $db_size['formatted'] ); ?></div>
					<p><?php esc_html_e( 'Total database size', 'velocitywp' ); ?></p>
				</div>

				<div class="velocitywp-stat-box">
					<h3><?php esc_html_e( 'Optimization', 'velocitywp' ); ?></h3>
					<div class="stat-value">
						<?php
						$active_features = 0;
						$features = array( 'html_minify', 'css_minify', 'js_minify', 'lazy_load_images' );
						foreach ( $features as $feature ) {
							if ( ! empty( $options[ $feature ] ) ) {
								$active_features++;
							}
						}
						echo esc_html( sprintf( __( '%d/%d', 'velocitywp' ), $active_features, count( $features ) ) );
						?>
					</div>
					<p><?php esc_html_e( 'Active optimizations', 'velocitywp' ); ?></p>
				</div>
			</div>

			<div class="velocitywp-quick-actions">
				<h3><?php esc_html_e( 'Quick Actions', 'velocitywp' ); ?></h3>
				<button type="button" class="button button-primary" id="velocitywp-clear-cache-btn">
					<?php esc_html_e( 'Clear Cache', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="velocitywp-optimize-db-btn">
					<?php esc_html_e( 'Optimize Database', 'velocitywp' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="velocitywp-preload-cache-btn">
					<?php esc_html_e( 'Preload Cache', 'velocitywp' ); ?>
				</button>
			</div>

			<div id="velocitywp-ajax-result" class="notice" style="display:none;"></div>
		</div>
		<?php
	}

	/**
	 * Render cache tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_cache_tab( $options ) {
		$cache = new VelocityWP_Cache();
		$cache_stats = $cache->get_cache_stats();
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Page Caching', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[cache_enabled]" value="1" <?php checked( 1, ! empty( $options['cache_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable file-based page caching', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Cache Lifespan', 'velocitywp' ); ?></th>
				<td>
					<input type="number" name="velocitywp_options[cache_lifespan]" value="<?php echo esc_attr( ! empty( $options['cache_lifespan'] ) ? $options['cache_lifespan'] : 36000 ); ?>" min="0" />
					<p class="description"><?php esc_html_e( 'Cache lifespan in seconds (default: 36000 = 10 hours)', 'velocitywp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Mobile Cache', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[mobile_cache]" value="1" <?php checked( 1, ! empty( $options['mobile_cache'] ) ); ?> />
						<?php esc_html_e( 'Create separate cache for mobile devices', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude URLs', 'velocitywp' ); ?></th>
				<td>
					<textarea name="velocitywp_options[cache_exclude_urls]" rows="5" class="large-text"><?php echo esc_textarea( ! empty( $options['cache_exclude_urls'] ) ? $options['cache_exclude_urls'] : '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter URL patterns to exclude from caching, one per line. Example: /cart/, /checkout/', 'velocitywp' ); ?></p>
				</td>
			</tr>
		</table>

		<div class="velocitywp-cache-stats">
			<h3><?php esc_html_e( 'Cache Statistics', 'velocitywp' ); ?></h3>
			<p><?php echo esc_html( sprintf( __( 'Cached files: %d', 'velocitywp' ), $cache_stats['files'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Cache size: %s', 'velocitywp' ), size_format( $cache_stats['size'] ) ) ); ?></p>
		</div>

		<div class="velocitywp-quick-actions">
			<button type="button" class="button" id="velocitywp-clear-cache-btn">
				<?php esc_html_e( 'Clear Cache', 'velocitywp' ); ?>
			</button>
			<button type="button" class="button" id="velocitywp-preload-cache-btn" style="margin-left: 10px;">
				<?php esc_html_e( 'Preload Cache', 'velocitywp' ); ?>
			</button>
		</div>

		<div id="velocitywp-ajax-result" class="notice" style="display:none;"></div>
		<?php
	}

	/**
	 * Render optimization tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_optimization_tab( $options ) {
		?>
		<h2><?php esc_html_e( 'HTML Minification', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minify HTML', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[html_minify]" value="1" <?php checked( 1, ! empty( $options['html_minify'] ) ); ?> />
						<?php esc_html_e( 'Remove whitespace and comments from HTML', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'CSS Optimization', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minify CSS', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[css_minify]" value="1" <?php checked( 1, ! empty( $options['css_minify'] ) ); ?> />
						<?php esc_html_e( 'Minify CSS files', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Combine CSS', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[css_combine]" value="1" <?php checked( 1, ! empty( $options['css_combine'] ) ); ?> />
						<?php esc_html_e( 'Combine CSS files into one (use with caution)', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'JavaScript Optimization', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minify JavaScript', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[js_minify]" value="1" <?php checked( 1, ! empty( $options['js_minify'] ) ); ?> />
						<?php esc_html_e( 'Minify JavaScript files', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Combine JavaScript', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[js_combine]" value="1" <?php checked( 1, ! empty( $options['js_combine'] ) ); ?> />
						<?php esc_html_e( 'Combine JavaScript files (use with caution)', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Defer JavaScript', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[js_defer]" value="1" <?php checked( 1, ! empty( $options['js_defer'] ) ); ?> />
						<?php esc_html_e( 'Defer non-critical JavaScript loading', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Other Optimizations', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove Query Strings', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[remove_query_strings]" value="1" <?php checked( 1, ! empty( $options['remove_query_strings'] ) ); ?> />
						<?php esc_html_e( 'Remove version query strings from static resources', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude Files', 'velocitywp' ); ?></th>
				<td>
					<textarea name="velocitywp_options[minify_exclude_files]" rows="5" class="large-text"><?php echo esc_textarea( ! empty( $options['minify_exclude_files'] ) ? $options['minify_exclude_files'] : '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter file paths or names to exclude from minification/optimization, one per line', 'velocitywp' ); ?></p>
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
		<h2><?php esc_html_e( 'Lazy Loading', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load Images', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[lazy_load_images]" value="1" <?php checked( 1, ! empty( $options['lazy_load_images'] ) ); ?> />
						<?php esc_html_e( 'Enable lazy loading for images', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load iframes', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[lazy_load_iframes]" value="1" <?php checked( 1, ! empty( $options['lazy_load_iframes'] ) ); ?> />
						<?php esc_html_e( 'Enable lazy loading for iframes (YouTube, etc.)', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude by Class', 'velocitywp' ); ?></th>
				<td>
					<input type="text" name="velocitywp_options[lazy_load_exclude_class]" value="<?php echo esc_attr( ! empty( $options['lazy_load_exclude_class'] ) ? $options['lazy_load_exclude_class'] : '' ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Comma-separated list of CSS classes to exclude from lazy loading', 'velocitywp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Skip Images', 'velocitywp' ); ?></th>
				<td>
					<input type="number" name="velocitywp_options[lazy_load_skip_images]" value="<?php echo esc_attr( ! empty( $options['lazy_load_skip_images'] ) ? $options['lazy_load_skip_images'] : 0 ); ?>" min="0" max="10" />
					<p class="description"><?php esc_html_e( 'Number of images to skip from lazy loading (above the fold)', 'velocitywp' ); ?></p>
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
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-webp.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-webp.php';
		}
	}

	/**
	 * Render Lazy Load tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_lazy_load_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-lazy-load.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-lazy-load.php';
		}
	}

	/**
	 * Render critical CSS tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_critical_css_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-critical-css.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-critical-css.php';
		}
	}

	/**
	 * Render fonts tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_fonts_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-fonts.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-fonts.php';
		}
	}

	/**
	 * Render resource hints tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_resource_hints_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-resource-hints.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-resource-hints.php';
		}
	}

	/**
	 * Render fragment cache tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_fragment_cache_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-fragment-cache.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-fragment-cache.php';
		}
	}

	/**
	 * Render JavaScript tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_javascript_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-javascript.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-javascript.php';
		}
	}

	/**
	 * Render performance metrics tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_performance_metrics_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-performance-metrics.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-performance-metrics.php';
		}
	}

	/**
	 * Render Performance Monitor tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_performance_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-performance.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-performance.php';
		}
	}

	/**
	 * Render object cache tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_object_cache_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-object-cache.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-object-cache.php';
		}
	}

	/**
	 * Render Cloudflare tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_cloudflare_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-cloudflare.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-cloudflare.php';
		}
	}

	/**
	 * Render heartbeat tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_heartbeat_tab( $options ) {
		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-heartbeat.php' ) ) {
			include VelocityWP_DIR . 'admin/views/tab-heartbeat.php';
		}
	}

	/**
	 * Render WooCommerce tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_woocommerce_tab( $options ) {
		// Check if WooCommerce is installed
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce is not installed. This tab is only available when WooCommerce is active.', 'velocitywp' ) . '</p>';
			return;
		}

		// Include the tab view file
		if ( file_exists( VelocityWP_DIR . 'admin/views/tab-woocommerce.php' ) ) {
			require VelocityWP_DIR . 'admin/views/tab-woocommerce.php';
		} else {
			echo '<p>' . esc_html__( 'WooCommerce tab content not found.', 'velocitywp' ) . '</p>';
		}
	}

	/**
	 * Render database tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_database_tab( $options ) {
		require VelocityWP_DIR . 'admin/views/tab-database.php';
	}

	/**
	 * Render advanced tab
	 *
	 * @param array $options Plugin options.
	 */
	private function render_advanced_tab( $options ) {
		?>
		<h2><?php esc_html_e( 'CDN Configuration', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable CDN', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[cdn_enabled]" value="1" <?php checked( 1, ! empty( $options['cdn_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable CDN for static assets', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'CDN URL', 'velocitywp' ); ?></th>
				<td>
					<input type="url" name="velocitywp_options[cdn_url]" value="<?php echo esc_url( ! empty( $options['cdn_url'] ) ? $options['cdn_url'] : '' ); ?>" class="regular-text" placeholder="https://cdn.example.com" />
					<p class="description"><?php esc_html_e( 'Enter your CDN URL (e.g., https://cdn.example.com)', 'velocitywp' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'DNS Prefetch', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'DNS Prefetch Domains', 'velocitywp' ); ?></th>
				<td>
					<textarea name="velocitywp_options[dns_prefetch]" rows="5" class="large-text"><?php echo esc_textarea( ! empty( $options['dns_prefetch'] ) ? $options['dns_prefetch'] : '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter external domains for DNS prefetch, one per line (e.g., //fonts.googleapis.com)', 'velocitywp' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Advanced Features', 'velocitywp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Emojis', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[disable_emojis]" value="1" <?php checked( 1, ! empty( $options['disable_emojis'] ) ); ?> />
						<?php esc_html_e( 'Remove emoji scripts and styles', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Embeds', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[disable_embeds]" value="1" <?php checked( 1, ! empty( $options['disable_embeds'] ) ); ?> />
						<?php esc_html_e( 'Disable WordPress embed functionality', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove jQuery Migrate', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[disable_jquery_migrate]" value="1" <?php checked( 1, ! empty( $options['disable_jquery_migrate'] ) ); ?> />
						<?php esc_html_e( 'Remove jQuery Migrate script', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove WP Version', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[remove_wp_version]" value="1" <?php checked( 1, ! empty( $options['remove_wp_version'] ) ); ?> />
						<?php esc_html_e( 'Remove WordPress version from head', 'velocitywp' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove RSD Links', 'velocitywp' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="velocitywp_options[remove_rsd_links]" value="1" <?php checked( 1, ! empty( $options['remove_rsd_links'] ) ); ?> />
						<?php esc_html_e( 'Remove RSD and WLW manifest links', 'velocitywp' ); ?>
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
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'velocitywp' ) ) );
		}

		$cache = new VelocityWP_Cache();
		$cache->clear_all_cache();

		wp_send_json_success( array( 'message' => __( 'Cache cleared successfully!', 'velocitywp' ) ) );
	}

	/**
	 * AJAX handler for database optimization
	 */
	public function ajax_optimize_database() {
		check_ajax_referer( 'velocitywp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'velocitywp' ) ) );
		}

		$database = new VelocityWP_Database();
		$results = $database->optimize_database();

		$message = __( 'Database optimized successfully!', 'velocitywp' );
		if ( ! empty( $results ) ) {
			$details = array();
			if ( isset( $results['revisions'] ) ) {
				$details[] = sprintf( __( 'Revisions cleaned: %d', 'velocitywp' ), $results['revisions'] );
			}
			if ( isset( $results['autodrafts'] ) ) {
				$details[] = sprintf( __( 'Auto-drafts removed: %d', 'velocitywp' ), $results['autodrafts'] );
			}
			if ( isset( $results['transients'] ) ) {
				$details[] = sprintf( __( 'Transients cleaned: %d', 'velocitywp' ), $results['transients'] );
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
		if ( $screen && $screen->id !== 'settings_page_velocitywp' ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings saved successfully!', 'velocitywp' ); ?></p>
			</div>
			<?php
		}
	}
}
