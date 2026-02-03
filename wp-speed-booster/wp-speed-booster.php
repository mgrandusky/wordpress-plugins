<?php
/**
 * Plugin Name: WP Speed Booster
 * Plugin URI: https://github.com/mgrandusky/wordpress-plugins
 * Description: Comprehensive page speed optimization plugin with caching, minification, lazy loading, database optimization, and more.
 * Version: 1.0.0
 * Author: mgrandusky
 * Author URI: https://github.com/mgrandusky
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-speed-booster
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
define( 'WPSB_VERSION', '1.0.0' );
define( 'WPSB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSB_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSB_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPSB_CACHE_DIR', WP_CONTENT_DIR . '/cache/wp-speed-booster/' );

/**
 * Main WP Speed Booster Plugin Class
 */
class WP_Speed_Booster {

	/**
	 * Instance of this class
	 *
	 * @var WP_Speed_Booster
	 */
	private static $instance = null;

	/**
	 * Cache instance
	 *
	 * @var WPSB_Cache
	 */
	private $cache;

	/**
	 * Minify instance
	 *
	 * @var WPSB_Minify
	 */
	private $minify;

	/**
	 * Lazy load instance
	 *
	 * @var WPSB_Lazy_Load
	 */
	private $lazy_load;

	/**
	 * Database optimization instance
	 *
	 * @var WPSB_Database
	 */
	private $database;

	/**
	 * CDN instance
	 *
	 * @var WPSB_CDN
	 */
	private $cdn;

	/**
	 * Preload instance
	 *
	 * @var WPSB_Preload
	 */
	private $preload;

	/**
	 * Critical CSS instance
	 *
	 * @var WPSB_Critical_CSS
	 */
	private $critical_css;

	/**
	 * Admin instance
	 *
	 * @var WPSB_Admin
	 */
	private $admin;

	/**
	 * WebP instance
	 *
	 * @var WPSB_WebP
	 */
	private $webp;

	/**
	 * Get singleton instance
	 *
	 * @return WP_Speed_Booster
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
		$this->cache     = new WPSB_Cache();
		$this->minify    = new WPSB_Minify();
		$this->lazy_load = new WPSB_Lazy_Load();
		$this->database  = new WPSB_Database();
		$this->cdn       = new WPSB_CDN();
		$this->preload   = new WPSB_Preload();
		$this->critical_css = new WPSB_Critical_CSS();
		$this->webp      = new WPSB_WebP();

		// Initialize admin interface
		if ( is_admin() ) {
			$this->admin = new WPSB_Admin();
		}

		// Register hooks
		$this->register_hooks();
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once WPSB_DIR . 'includes/class-cache.php';
		require_once WPSB_DIR . 'includes/class-minify.php';
		require_once WPSB_DIR . 'includes/class-lazy-load.php';
		require_once WPSB_DIR . 'includes/class-database.php';
		require_once WPSB_DIR . 'includes/class-cdn.php';
		require_once WPSB_DIR . 'includes/class-preload.php';
		require_once WPSB_DIR . 'includes/class-critical-css.php';
		require_once WPSB_DIR . 'includes/class-webp.php';
		require_once WPSB_DIR . 'includes/class-performance-metrics.php';
		require_once WPSB_DIR . 'includes/class-js-delay.php';

		if ( is_admin() ) {
			require_once WPSB_DIR . 'admin/class-admin.php';
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
		// Create cache directory
		if ( ! file_exists( WPSB_CACHE_DIR ) ) {
			wp_mkdir_p( WPSB_CACHE_DIR );
			// Add .htaccess to protect cache directory
			file_put_contents(
				WPSB_CACHE_DIR . '.htaccess',
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
			'lazy_load_images'           => 1,
			'lazy_load_iframes'          => 1,
			'lazy_load_exclude_class'    => '',
			'lazy_load_skip_images'      => 0,
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
			'webp_enabled'               => 0,
			'webp_quality'               => 85,
			'js_delay_enabled'           => 0,
			'js_defer_enabled'           => 0,
			'js_delay_timeout'           => 5,
			'js_delay_exclude'           => '',
			'js_delay_events'            => 'mousemove,scroll,touchstart,click,keydown',
		);

		// Don't override existing options
		if ( ! get_option( 'wpsb_options' ) ) {
			add_option( 'wpsb_options', $default_options );
		}

		// Backup .htaccess before modifications
		$htaccess = ABSPATH . '.htaccess';
		if ( file_exists( $htaccess ) && ! file_exists( ABSPATH . '.htaccess.wpsb.backup' ) ) {
			copy( $htaccess, ABSPATH . '.htaccess.wpsb.backup' );
		}

		// Schedule automatic database optimization
		if ( ! wp_next_scheduled( 'wpsb_auto_db_optimize' ) ) {
			wp_schedule_event( time(), 'daily', 'wpsb_auto_db_optimize' );
		}

		// Setup performance metrics scheduled checks
		WP_Speed_Booster_Performance_Metrics::activate();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Unschedule events
		wp_clear_scheduled_hook( 'wpsb_auto_db_optimize' );
		WP_Speed_Booster_Performance_Metrics::deactivate();
	}

	/**
	 * Disable emojis
	 */
	public function disable_emojis() {
		$options = get_option( 'wpsb_options', array() );
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
		$options = get_option( 'wpsb_options', array() );
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
		$options = get_option( 'wpsb_options', array() );
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
		$options = get_option( 'wpsb_options', array() );
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
		$options = get_option( 'wpsb_options', array() );
		if ( ! empty( $options['remove_query_strings'] ) ) {
			if ( strpos( $src, '?ver=' ) !== false ) {
				$src = remove_query_arg( 'ver', $src );
			}
		}
		return $src;
	}
}

/**
 * Initialize the plugin
 */
function wpsb_init() {
	return WP_Speed_Booster::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'wpsb_init' );

/**
 * Uninstall hook
 */
register_uninstall_hook( __FILE__, 'wpsb_uninstall' );

/**
 * Plugin uninstall
 */
function wpsb_uninstall() {
	// Remove options
	delete_option( 'wpsb_options' );

	// Remove cache directory
	if ( file_exists( WPSB_CACHE_DIR ) ) {
		$files = glob( WPSB_CACHE_DIR . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		rmdir( WPSB_CACHE_DIR );
	}

	// Restore .htaccess backup
	if ( file_exists( ABSPATH . '.htaccess.wpsb.backup' ) ) {
		copy( ABSPATH . '.htaccess.wpsb.backup', ABSPATH . '.htaccess' );
		unlink( ABSPATH . '.htaccess.wpsb.backup' );
	}

	// Clear scheduled events
	wp_clear_scheduled_hook( 'wpsb_auto_db_optimize' );
}
