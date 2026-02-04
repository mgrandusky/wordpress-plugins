<?php
/**
 * Plugin Name: VelocityWP
 * Plugin URI: https://velocitywp.com
 * Description: The ultimate WordPress performance optimization plugin. Boost speed with object caching, lazy loading, critical CSS, image optimization, and more.
 * Version: 1.0.0
 * Author: mgrandusky
 * Author URI: https://github.com/mgrandusky
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: velocitywp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'VELOCITYWP_VERSION', '1.0.0' );
define( 'VELOCITYWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VELOCITYWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VELOCITYWP_PLUGIN_FILE', __FILE__ );
define( 'VELOCITYWP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'VELOCITYWP_CACHE_DIR', WP_CONTENT_DIR . '/cache/velocitywp/' );

// Legacy constants for backward compatibility
define( 'WPSB_VERSION', VELOCITYWP_VERSION );
define( 'WPSB_DIR', VELOCITYWP_PLUGIN_DIR );
define( 'WPSB_URL', VELOCITYWP_PLUGIN_URL );
define( 'WPSB_BASENAME', VELOCITYWP_PLUGIN_BASENAME );
define( 'VELOCITYWP_CACHE_DIR', VELOCITYWP_CACHE_DIR );

/**
 * Main VelocityWP Plugin Class
 */
class VelocityWP {

	/**
	 * Instance of this class
	 *
	 * @var VelocityWP
	 */
	private static $instance = null;

	/**
	 * Cache instance
	 *
	 * @var VelocityWP_Cache
	 */
	private $cache;

	/**
	 * Minify instance
	 *
	 * @var VelocityWP_Minify
	 */
	private $minify;

	/**
	 * Lazy load instance
	 *
	 * @var VelocityWP_Lazy_Load
	 */
	private $lazy_load;

	/**
	 * Database optimization instance
	 *
	 * @var VelocityWP_Database
	 */
	private $database;

	/**
	 * Database optimizer instance
	 *
	 * @var VelocityWP_Database_Optimizer
	 */
	private $database_optimizer;

	/**
	 * CDN instance
	 *
	 * @var VelocityWP_CDN
	 */
	private $cdn;

	/**
	 * Preload instance
	 *
	 * @var VelocityWP_Preload
	 */
	private $preload;

	/**
	 * Critical CSS instance
	 *
	 * @var VelocityWP_Critical_CSS
	 */
	private $critical_css;

	/**
	 * Admin instance
	 *
	 * @var VelocityWP_Admin
	 */
	private $admin;

	/**
	 * WebP instance
	 *
	 * @var VelocityWP_WebP
	 */
	private $webp;

	/**
	 * Font Optimizer instance
	 *
	 * @var VelocityWP_Font_Optimizer
	 */
	private $font_optimizer;

	/**
	 * Object Cache instance
	 *
	 * @var VelocityWP_Object_Cache
	 */
	private $object_cache;

	/**
	 * Fragment Cache instance
	 *
	 * @var VelocityWP_Fragment_Cache
	 */
	private $fragment_cache;

	/**
	 * Resource Hints instance
	 *
	 * @var VelocityWP_Resource_Hints
	 */
	private $resource_hints;

	/**
	 * Cloudflare instance
	 *
	 * @var VelocityWP_Cloudflare
	 */
	private $cloudflare;

	/**
	 * Heartbeat instance
	 *
	 * @var VelocityWP_Heartbeat
	 */
	private $heartbeat;

	/**
	 * Performance Monitor instance
	 *
	 * @var VelocityWP_Performance_Monitor
	 */
	private $performance_monitor;

	/**
	 * WooCommerce Optimizer instance
	 *
	 * @var VelocityWP_WooCommerce_Optimizer
	 */
	private $woocommerce_optimizer;

	/**
	 * Get singleton instance
	 *
	 * @return VelocityWP
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Load dependencies
		$this->load_dependencies();

		// Initialize components
		$this->cache     = new VelocityWP_Cache();
		$this->minify    = new VelocityWP_Minify();
		$this->lazy_load = new VelocityWP_Lazy_Load();
		$this->database  = new VelocityWP_Database();
		$this->database_optimizer = new VelocityWP_Database_Optimizer();
		$this->cdn       = new VelocityWP_CDN();
		$this->preload   = new VelocityWP_Preload();
		$this->critical_css = new VelocityWP_Critical_CSS();
		$this->webp      = new VelocityWP_WebP();
		$this->font_optimizer = new VelocityWP_Font_Optimizer();
		$this->object_cache = new VelocityWP_Object_Cache();
		$this->fragment_cache = new VelocityWP_Fragment_Cache();
		$this->resource_hints = new VelocityWP_Resource_Hints();
		$this->cloudflare = new VelocityWP_Cloudflare();
		$this->heartbeat = new VelocityWP_Heartbeat();
		$this->performance_monitor = new VelocityWP_Performance_Monitor();

		// Initialize WooCommerce optimizer
		if ( class_exists( 'WooCommerce' ) ) {
			$this->woocommerce_optimizer = new VelocityWP_WooCommerce_Optimizer();
		}

		// Initialize admin interface
		if ( is_admin() ) {
			$this->admin = new VelocityWP_Admin();
		}

		// Register hooks
		$this->register_hooks();
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-cache.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-minify.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-lazy-load.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-database.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-database-optimizer.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-cdn.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-preload.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-critical-css.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-webp.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-performance-metrics.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-js-delay.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-font-optimizer.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-object-cache.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-fragment-cache.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-resource-hints.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-cloudflare.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-heartbeat.php';
		require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-performance-monitor.php';

		// Load WooCommerce optimizer if WooCommerce is active
		if ( class_exists( 'WooCommerce' ) ) {
			require_once VELOCITYWP_PLUGIN_DIR . 'includes/class-woocommerce-optimizer.php';
		}

		if ( is_admin() ) {
			require_once VELOCITYWP_PLUGIN_DIR . 'admin/class-admin.php';
		}
	}

	/**
	 * Register plugin hooks
	 */
	private function register_hooks() {
		// Activation/Deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Performance optimizations
		add_action( 'init', array( $this, 'disable_emojis' ) );
		add_action( 'init', array( $this, 'disable_embeds' ) );
		add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
		add_action( 'wp_head', array( $this, 'add_dns_prefetch' ), 0 );
		add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 15, 1 );
		add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 15, 1 );

		// Remove unnecessary headers
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Migrate old options and tables first
		velocitywp_migrate_options();
		velocitywp_migrate_tables();
		
		// Create cache directory
		if ( ! file_exists( VELOCITYWP_CACHE_DIR ) ) {
			wp_mkdir_p( VELOCITYWP_CACHE_DIR );
			// Add .htaccess to protect cache directory
			file_put_contents(
				VELOCITYWP_CACHE_DIR . '.htaccess',
				"<IfModule mod_autoindex.c>\n\tOptions -Indexes\n</IfModule>"
			);
		}

		// Set default options
		$default_options = array(
			'cache_enabled'              => 1,
			'cache_lifespan'             => 36000, // 10 hours
			'mobile_cache'               => 1,
			'cache_exclude_urls'         => '',
			'html_minify'                => 1,
			'css_minify'                 => 1,
			'css_combine'                => 0,
			'js_minify'                  => 1,
			'js_combine'                 => 0,
			'js_defer'                   => 1,
			'remove_query_strings'       => 1,
			'minify_exclude_files'       => '',
			'lazy_load_enabled'          => 1,
			'lazy_load_native'           => 0,
			'lazy_load_images'           => 1,
			'lazy_load_iframes'          => 1,
			'lazy_load_videos'           => 0,
			'lazy_load_backgrounds'      => 0,
			'lazy_load_youtube'          => 0,
			'lazy_load_vimeo'            => 0,
			'lazy_load_maps'             => 0,
			'lazy_load_exclude_class'    => '',
			'lazy_load_exclude_classes'  => '',
			'lazy_load_skip_images'      => 0,
			'lazy_load_skip_first'       => 0,
			'lazy_load_placeholder'      => 'transparent',
			'lazy_load_fade_in'          => 0,
			'lazy_load_fade_duration'    => 300,
			'lazy_load_threshold'        => 200,
			'db_clean_revisions'         => 0,
			'db_revisions_to_keep'       => 3,
			'db_clean_autodrafts'        => 0,
			'db_clean_trash'             => 0,
			'db_optimize_tables'         => 0,
			'db_clean_transients'        => 0,
			'db_clean_spam'              => 0,
			'db_auto_optimize'           => 'disabled',
			'cdn_enabled'                => 0,
			'cdn_url'                    => '',
			'dns_prefetch'               => '',
			'disable_emojis'             => 1,
			'disable_embeds'             => 0,
			'disable_jquery_migrate'     => 0,
			'remove_wp_version'          => 1,
			'remove_rsd_links'           => 1,
			'critical_css_enabled'       => 0,
			'critical_css_mode'          => 'auto',
			'critical_css_defer'         => 0,
			'critical_css_desktop'       => 1,
			'critical_css_mobile'        => 1,
			'critical_css_exclude'       => '',
			'critical_css_manual'        => '',
			'critical_css_api_key'       => '',
			'critical_css_api_provider'  => 'criticalcss',
			'critical_css_exclude_handles' => '',
			'critical_css_defer_method'  => 'media-print',
			'critical_css_mobile_separate' => 0,
			'webp_enabled'               => 0,
			'webp_quality'               => 85,
			'js_delay_enabled'           => 0,
			'js_defer_enabled'           => 0,
			'js_delay_timeout'           => 5,
			'js_delay_exclude'           => '',
			'js_delay_events'            => 'mousemove,scroll,touchstart,click,keydown',
			'object_cache_backend'       => 'auto',
			'redis_host'                 => '127.0.0.1',
			'redis_port'                 => 6379,
			'redis_password'             => '',
			'redis_database'             => 0,
			'memcached_servers'          => '127.0.0.1:11211',
			'fragment_cache_enabled'     => 0,
			'cache_widgets'              => 0,
			'cache_sidebars'             => 0,
			'cache_menus'                => 0,
			'cache_shortcodes'           => 0,
			'fragment_cache_time'        => 3600, // 1 hour
			'fragment_cache_logged_in'   => 0,
			'cached_widget_list'         => array(),
			'cached_sidebar_list'        => array(),
			'cached_menu_list'           => array(),
			'cached_shortcode_list'      => '',
			'resource_hints_enabled'     => 0,
			'dns_prefetch_enabled'       => 0,
			'dns_prefetch_auto'          => 0,
			'dns_prefetch_domains'       => '',
			'preconnect_enabled'         => 0,
			'preconnect_origins'         => array(),
			'preload_enabled'            => 0,
			'preload_resources'          => array(),
			'prefetch_enabled'           => 0,
			'prefetch_next_page'         => 0,
			'prefetch_urls'              => '',
			'cloudflare_enabled'         => 0,
			'cloudflare_auth_type'       => 'token',
			'cloudflare_api_token'       => '',
			'cloudflare_email'           => '',
			'cloudflare_api_key'         => '',
			'cloudflare_zone_id'         => '',
			'cloudflare_purge_on_update' => 0,
			'cloudflare_purge_on_comment' => 0,
			'cloudflare_restore_ip'      => 0,
			'heartbeat_control_enabled'  => 0,
			'heartbeat_disable_completely' => 0,
			'heartbeat_disable_frontend' => 0,
			'heartbeat_disable_admin'    => 0,
			'heartbeat_disable_editor'   => 0,
			'heartbeat_frontend_frequency' => 60,
			'heartbeat_admin_frequency'  => 60,
			'heartbeat_editor_frequency' => 15,
			'heartbeat_allow_post_locking' => 1,
			'heartbeat_allow_autosave'   => 1,
			'heartbeat_track_activity'   => 0,
			'performance_monitoring_enabled' => 0,
			'performance_track_rum'      => 1,
			'performance_track_server'   => 1,
			'performance_data_retention' => 30,
			'performance_debug_comments' => 0,
			'performance_sample_rate'    => 100,
		);

		// Don't override existing options
		if ( ! get_option( 'velocitywp_options' ) ) {
			add_option( 'velocitywp_options', $default_options );
		}

		// Backup .htaccess before modifications
		$htaccess = ABSPATH . '.htaccess';
		if ( file_exists( $htaccess ) && ! file_exists( ABSPATH . '.htaccess.velocitywp.backup' ) ) {
			copy( $htaccess, ABSPATH . '.htaccess.velocitywp.backup' );
		}

		// Create performance monitoring table
		VelocityWP_Performance_Monitor::create_table();

		// Schedule automatic database optimization
		if ( ! wp_next_scheduled( 'velocitywp_auto_db_optimize' ) ) {
			wp_schedule_event( time(), 'daily', 'velocitywp_auto_db_optimize' );
		}

		// Schedule performance data cleanup
		if ( ! wp_next_scheduled( 'velocitywp_cleanup_performance_data' ) ) {
			wp_schedule_event( time(), 'daily', 'velocitywp_cleanup_performance_data' );
		}

		// Setup performance metrics scheduled checks
		VelocityWP_Performance_Metrics::activate();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Unschedule events
		wp_clear_scheduled_hook( 'velocitywp_auto_db_optimize' );
		wp_clear_scheduled_hook( 'velocitywp_cleanup_performance_data' );
		VelocityWP_Performance_Metrics::deactivate();
	}

	/**
	 * Disable emojis
	 */
	public function disable_emojis() {
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['disable_emojis'] ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}
	}

	/**
	 * Disable embeds
	 */
	public function disable_embeds() {
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['disable_embeds'] ) ) {
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		}
	}

	/**
	 * Remove jQuery migrate
	 *
	 * @param WP_Scripts $scripts WP_Scripts object.
	 */
	public function remove_jquery_migrate( $scripts ) {
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['disable_jquery_migrate'] ) && ! is_admin() ) {
			if ( isset( $scripts->registered['jquery'] ) ) {
				$script = $scripts->registered['jquery'];
				if ( $script->deps ) {
					$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
				}
			}
		}
	}

	/**
	 * Add DNS prefetch
	 */
	public function add_dns_prefetch() {
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['dns_prefetch'] ) ) {
			$domains = explode( "\n", $options['dns_prefetch'] );
			foreach ( $domains as $domain ) {
				$domain = trim( $domain );
				if ( ! empty( $domain ) ) {
					echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
				}
			}
		}
	}

	/**
	 * Remove query strings from static resources
	 *
	 * @param string $src Source URL.
	 * @return string
	 */
	public function remove_query_strings( $src ) {
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['remove_query_strings'] ) ) {
			if ( strpos( $src, '?ver=' ) !== false ) {
				$src = remove_query_arg( 'ver', $src );
			}
		}
		return $src;
	}
}

/**
 * Migrate old options to new names for backward compatibility
 */
function velocitywp_migrate_options() {
	// Migrate main settings option
	$old_settings = get_option( 'velocitywp_options' );
	if ( $old_settings && ! get_option( 'velocitywp_options' ) ) {
		update_option( 'velocitywp_options', $old_settings );
	}
	
	// Migrate other old options
	$options_map = array(
		'velocitywp_critical_css' => 'velocitywp_critical_css',
		'velocitywp_heartbeat_stats' => 'velocitywp_heartbeat_stats',
		'velocitywp_performance_history' => 'velocitywp_performance_history',
		'velocitywp_fragment_stats' => 'velocitywp_fragment_stats',
		'velocitywp_geo_stats' => 'velocitywp_geo_stats',
		'velocitywp_slow_queries' => 'velocitywp_slow_queries',
		'velocitywp_duplicate_queries' => 'velocitywp_duplicate_queries',
		'velocitywp_missing_indexes' => 'velocitywp_missing_indexes',
		'velocitywp_query_history' => 'velocitywp_query_history',
		'velocitywp_preload_progress' => 'velocitywp_preload_progress',
		'velocitywp_ab_results' => 'velocitywp_ab_results',
		'velocitywp_ab_tests' => 'velocitywp_ab_tests',
		'velocitywp_exclusion_stats' => 'velocitywp_exclusion_stats',
		'velocitywp_cache_analytics' => 'velocitywp_cache_analytics',
		'velocitywp_mobile_stats' => 'velocitywp_mobile_stats',
		'velocitywp_cf_last_purge' => 'velocitywp_cf_last_purge',
	);
	
	foreach ( $options_map as $old_key => $new_key ) {
		$old_value = get_option( $old_key );
		if ( $old_value && ! get_option( $new_key ) ) {
			update_option( $new_key, $old_value );
		}
	}
}

/**
 * Migrate database tables
 */
function velocitywp_migrate_tables() {
	global $wpdb;
	
	// Migrate performance table
	$old_table = $wpdb->prefix . 'velocitywp_performance';
	$new_table = $wpdb->prefix . 'velocitywp_performance';
	
	// Check if old table exists and new doesn't
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) ) === $old_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );
		if ( ! $new_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $old_table, $new_table ) );
		}
	}
}

/**
 * Initialize the plugin
 */
function velocitywp_init() {
	return VelocityWP::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'velocitywp_init' );

/**
 * Uninstall hook
 */
register_uninstall_hook( __FILE__, 'velocitywp_uninstall' );

/**
 * Plugin uninstall
 */
function velocitywp_uninstall() {
	global $wpdb;

	// Remove options
	delete_option( 'velocitywp_options' );

	// Remove performance monitoring table
	$table_name = $wpdb->prefix . 'velocitywp_performance';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );

	// Remove cache directory
	if ( file_exists( VELOCITYWP_CACHE_DIR ) ) {
		$files = glob( VELOCITYWP_CACHE_DIR . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		rmdir( VELOCITYWP_CACHE_DIR );
	}

	// Restore .htaccess backup
	if ( file_exists( ABSPATH . '.htaccess.velocitywp.backup' ) ) {
		copy( ABSPATH . '.htaccess.velocitywp.backup', ABSPATH . '.htaccess' );
		unlink( ABSPATH . '.htaccess.velocitywp.backup' );
	}

	// Clear scheduled events
	wp_clear_scheduled_hook( 'velocitywp_auto_db_optimize' );
	wp_clear_scheduled_hook( 'velocitywp_cleanup_performance_data' );
}
