<?php
/**
 * Plugin Name: Google Drive Photo Gallery
 * Plugin URI: https://example.com
 * Description: Integrate with Google Drive to create photo galleries from selected folders
 * Version: 1.0.0
 * Author: Mason Grandusky
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: google-drive-gallery
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'GDRIVE_GALLERY_VERSION', '1.0.0' );
define( 'GDRIVE_GALLERY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GDRIVE_GALLERY_URL', plugin_dir_url( __FILE__ ) );
define( 'GDRIVE_GALLERY_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Google Drive Gallery Plugin Class
 */
class Google_Drive_Gallery {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Gallery renderer instance
     */
    private $gallery;

    /**
     * Admin interface instance
     */
    private $admin;

    /**
     * Get singleton instance
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
        $this->admin = new GDrive_Admin();
        $this->gallery = new GDrive_Gallery();

        // Register hooks
        $this->register_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once GDRIVE_GALLERY_DIR . 'includes/class-gdrive-auth.php';
        require_once GDRIVE_GALLERY_DIR . 'includes/class-gdrive-api.php';
        require_once GDRIVE_GALLERY_DIR . 'includes/class-gdrive-gallery.php';
        require_once GDRIVE_GALLERY_DIR . 'includes/class-gdrive-cache.php';
        require_once GDRIVE_GALLERY_DIR . 'includes/class-gdrive-admin.php';
    }

    /**
     * Register plugin hooks
     */
    private function register_hooks() {
        // Register shortcode
        add_shortcode( 'gdrive_gallery', [ $this->gallery, 'render_shortcode' ] );

        // Register Gutenberg block
        add_action( 'init', [ $this, 'register_block' ] );

        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

        // Register activation/deactivation hooks
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
    }

    /**
     * Register Gutenberg block
     */
    public function register_block() {
        // Check if block editor is available
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $block_path = GDRIVE_GALLERY_DIR . 'blocks/gdrive-gallery-block';
        
        // Register block script
        wp_register_script(
            'gdrive-gallery-block',
            GDRIVE_GALLERY_URL . 'blocks/gdrive-gallery-block/block.js',
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ],
            GDRIVE_GALLERY_VERSION,
            true
        );

        // Register block editor style
        wp_register_style(
            'gdrive-gallery-block-editor',
            GDRIVE_GALLERY_URL . 'blocks/gdrive-gallery-block/editor.css',
            [ 'wp-edit-blocks' ],
            GDRIVE_GALLERY_VERSION
        );

        // Register block type
        register_block_type( 'gdrive-gallery/gallery', [
            'editor_script' => 'gdrive-gallery-block',
            'editor_style' => 'gdrive-gallery-block-editor',
            'render_callback' => [ $this->gallery, 'render_block' ],
            'attributes' => [
                'folderId' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'columns' => [
                    'type' => 'number',
                    'default' => 3,
                ],
                'spacing' => [
                    'type' => 'number',
                    'default' => 10,
                ],
                'lightbox' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'slideshow' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
                'showCaptions' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
                'includeSubfolders' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
                'thumbnailSize' => [
                    'type' => 'string',
                    'default' => 'medium',
                ],
                'title' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'customClass' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
        ] );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Enqueue gallery styles
        wp_enqueue_style(
            'gdrive-gallery-styles',
            GDRIVE_GALLERY_URL . 'public/css/gallery-styles.css',
            [],
            GDRIVE_GALLERY_VERSION
        );

        // Enqueue gallery scripts
        wp_enqueue_script(
            'gdrive-gallery-scripts',
            GDRIVE_GALLERY_URL . 'public/js/gallery-scripts.js',
            [ 'jquery' ],
            GDRIVE_GALLERY_VERSION,
            true
        );

        // Localize script with AJAX URL
        wp_localize_script( 'gdrive-gallery-scripts', 'gdriveGallery', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'gdrive-gallery-nonce' ),
        ] );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        add_option( 'gdrive_gallery_cache_duration', 3600 ); // 1 hour default
        add_option( 'gdrive_gallery_auth_type', 'oauth' ); // oauth or service_account
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up transients
        GDrive_Cache::clear_all_cache();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function gdrive_gallery_init() {
    return Google_Drive_Gallery::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'gdrive_gallery_init' );
