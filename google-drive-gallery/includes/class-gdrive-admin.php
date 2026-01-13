<?php
/**
 * Google Drive Gallery Admin Interface
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GDrive_Admin
 * Handles admin interface and settings
 */
class GDrive_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_init', [ $this, 'handle_oauth_callback' ] );
        add_action( 'wp_ajax_gdrive_test_connection', [ $this, 'ajax_test_connection' ] );
        add_action( 'wp_ajax_gdrive_clear_cache', [ $this, 'ajax_clear_cache' ] );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Google Drive Gallery', 'google-drive-gallery' ),
            __( 'Google Drive Gallery', 'google-drive-gallery' ),
            'manage_options',
            'gdrive-gallery-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Authentication settings
        register_setting( 'gdrive_gallery_auth', 'gdrive_gallery_auth_type', [
            'sanitize_callback' => [ $this, 'sanitize_auth_type' ],
        ] );
        register_setting( 'gdrive_gallery_auth', 'gdrive_gallery_oauth_client_id', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        register_setting( 'gdrive_gallery_auth', 'gdrive_gallery_oauth_client_secret', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        register_setting( 'gdrive_gallery_auth', 'gdrive_gallery_service_account_json', [
            'sanitize_callback' => [ $this, 'sanitize_service_account_json' ],
        ] );

        // General settings
        register_setting( 'gdrive_gallery_general', 'gdrive_gallery_cache_duration', [
            'sanitize_callback' => 'absint',
            'default' => 3600,
        ] );
        register_setting( 'gdrive_gallery_general', 'gdrive_gallery_default_columns', [
            'sanitize_callback' => 'absint',
            'default' => 3,
        ] );
        register_setting( 'gdrive_gallery_general', 'gdrive_gallery_default_spacing', [
            'sanitize_callback' => 'absint',
            'default' => 10,
        ] );
        register_setting( 'gdrive_gallery_general', 'gdrive_gallery_default_thumbnail_size', [
            'sanitize_callback' => [ $this, 'sanitize_thumbnail_size' ],
            'default' => 'medium',
        ] );

        // Authentication section
        add_settings_section(
            'gdrive_auth_section',
            __( 'Authentication', 'google-drive-gallery' ),
            [ $this, 'render_auth_section_info' ],
            'gdrive_gallery_auth'
        );

        add_settings_field(
            'gdrive_auth_type',
            __( 'Authentication Type', 'google-drive-gallery' ),
            [ $this, 'render_auth_type_field' ],
            'gdrive_gallery_auth',
            'gdrive_auth_section'
        );

        add_settings_field(
            'gdrive_oauth_credentials',
            __( 'OAuth 2.0 Credentials', 'google-drive-gallery' ),
            [ $this, 'render_oauth_credentials_field' ],
            'gdrive_gallery_auth',
            'gdrive_auth_section'
        );

        add_settings_field(
            'gdrive_service_account',
            __( 'Service Account JSON', 'google-drive-gallery' ),
            [ $this, 'render_service_account_field' ],
            'gdrive_gallery_auth',
            'gdrive_auth_section'
        );

        // General settings section
        add_settings_section(
            'gdrive_general_section',
            __( 'General Settings', 'google-drive-gallery' ),
            [ $this, 'render_general_section_info' ],
            'gdrive_gallery_general'
        );

        add_settings_field(
            'gdrive_cache_duration',
            __( 'Cache Duration', 'google-drive-gallery' ),
            [ $this, 'render_cache_duration_field' ],
            'gdrive_gallery_general',
            'gdrive_general_section'
        );

        add_settings_field(
            'gdrive_default_columns',
            __( 'Default Columns', 'google-drive-gallery' ),
            [ $this, 'render_default_columns_field' ],
            'gdrive_gallery_general',
            'gdrive_general_section'
        );

        add_settings_field(
            'gdrive_default_spacing',
            __( 'Default Spacing', 'google-drive-gallery' ),
            [ $this, 'render_default_spacing_field' ],
            'gdrive_gallery_general',
            'gdrive_general_section'
        );

        add_settings_field(
            'gdrive_default_thumbnail_size',
            __( 'Default Thumbnail Size', 'google-drive-gallery' ),
            [ $this, 'render_default_thumbnail_size_field' ],
            'gdrive_gallery_general',
            'gdrive_general_section'
        );
    }

    /**
     * Sanitize authentication type
     */
    public function sanitize_auth_type( $value ) {
        $valid_types = [ 'oauth', 'service_account' ];
        return in_array( $value, $valid_types, true ) ? $value : 'oauth';
    }

    /**
     * Sanitize service account JSON
     */
    public function sanitize_service_account_json( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $decoded = json_decode( $value, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            add_settings_error(
                'gdrive_gallery_service_account_json',
                'invalid_json',
                __( 'Invalid JSON format for service account credentials', 'google-drive-gallery' )
            );
            return get_option( 'gdrive_gallery_service_account_json', '' );
        }

        // Validate required fields
        $required_fields = [ 'type', 'project_id', 'private_key', 'client_email' ];
        foreach ( $required_fields as $field ) {
            if ( ! isset( $decoded[ $field ] ) ) {
                add_settings_error(
                    'gdrive_gallery_service_account_json',
                    'missing_field',
                    sprintf( __( 'Missing required field: %s', 'google-drive-gallery' ), $field )
                );
                return get_option( 'gdrive_gallery_service_account_json', '' );
            }
        }

        return $value;
    }

    /**
     * Sanitize thumbnail size
     */
    public function sanitize_thumbnail_size( $value ) {
        $valid_sizes = [ 'small', 'medium', 'large' ];
        return in_array( $value, $valid_sizes, true ) ? $value : 'medium';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'auth';

        include GDRIVE_GALLERY_DIR . 'admin/settings-page.php';
    }

    /**
     * Render authentication section info
     */
    public function render_auth_section_info() {
        echo '<p>' . esc_html__( 'Configure authentication to connect to Google Drive.', 'google-drive-gallery' ) . '</p>';
    }

    /**
     * Render general section info
     */
    public function render_general_section_info() {
        echo '<p>' . esc_html__( 'Configure default settings for galleries.', 'google-drive-gallery' ) . '</p>';
    }

    /**
     * Render authentication type field
     */
    public function render_auth_type_field() {
        $value = get_option( 'gdrive_gallery_auth_type', 'oauth' );
        ?>
        <label>
            <input type="radio" name="gdrive_gallery_auth_type" value="oauth" <?php checked( $value, 'oauth' ); ?> />
            <?php esc_html_e( 'OAuth 2.0', 'google-drive-gallery' ); ?>
        </label><br>
        <label>
            <input type="radio" name="gdrive_gallery_auth_type" value="service_account" <?php checked( $value, 'service_account' ); ?> />
            <?php esc_html_e( 'Service Account', 'google-drive-gallery' ); ?>
        </label>
        <?php
    }

    /**
     * Render OAuth credentials field
     */
    public function render_oauth_credentials_field() {
        $client_id = get_option( 'gdrive_gallery_oauth_client_id', '' );
        $client_secret = get_option( 'gdrive_gallery_oauth_client_secret', '' );
        $is_authenticated = GDrive_Auth::is_authenticated() && 'oauth' === GDrive_Auth::get_auth_type();
        ?>
        <p>
            <label for="gdrive_gallery_oauth_client_id"><?php esc_html_e( 'Client ID:', 'google-drive-gallery' ); ?></label><br>
            <input type="text" id="gdrive_gallery_oauth_client_id" name="gdrive_gallery_oauth_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text" />
        </p>
        <p>
            <label for="gdrive_gallery_oauth_client_secret"><?php esc_html_e( 'Client Secret:', 'google-drive-gallery' ); ?></label><br>
            <input type="text" id="gdrive_gallery_oauth_client_secret" name="gdrive_gallery_oauth_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text" />
        </p>
        <?php if ( ! empty( $client_id ) && ! empty( $client_secret ) ) : ?>
            <p>
                <?php if ( $is_authenticated ) : ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    <?php esc_html_e( 'Authenticated', 'google-drive-gallery' ); ?>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gdrive-gallery-settings&action=disconnect_oauth' ), 'gdrive_disconnect_oauth' ) ); ?>" class="button">
                        <?php esc_html_e( 'Disconnect', 'google-drive-gallery' ); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url( GDrive_Auth::get_authorization_url() ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Connect to Google Drive', 'google-drive-gallery' ); ?>
                    </a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: Redirect URI */
                esc_html__( 'Redirect URI: %s', 'google-drive-gallery' ),
                '<code>' . esc_html( admin_url( 'admin.php?page=gdrive-gallery-settings&oauth_callback=1' ) ) . '</code>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render service account field
     */
    public function render_service_account_field() {
        $json = get_option( 'gdrive_gallery_service_account_json', '' );
        $is_authenticated = GDrive_Auth::is_authenticated() && 'service_account' === GDrive_Auth::get_auth_type();
        ?>
        <p>
            <label for="gdrive_gallery_service_account_json"><?php esc_html_e( 'Service Account JSON:', 'google-drive-gallery' ); ?></label><br>
            <textarea id="gdrive_gallery_service_account_json" name="gdrive_gallery_service_account_json" rows="10" class="large-text code"><?php echo esc_textarea( $json ); ?></textarea>
        </p>
        <?php if ( $is_authenticated ) : ?>
            <p>
                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                <?php esc_html_e( 'Service account configured', 'google-drive-gallery' ); ?>
            </p>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e( 'Paste the contents of your service account JSON key file here.', 'google-drive-gallery' ); ?>
        </p>
        <?php
    }

    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $value = get_option( 'gdrive_gallery_cache_duration', 3600 );
        ?>
        <input type="number" name="gdrive_gallery_cache_duration" value="<?php echo esc_attr( $value ); ?>" min="0" step="60" class="small-text" />
        <span><?php esc_html_e( 'seconds', 'google-drive-gallery' ); ?></span>
        <p class="description"><?php esc_html_e( 'How long to cache folder contents (3600 = 1 hour)', 'google-drive-gallery' ); ?></p>
        <?php
    }

    /**
     * Render default columns field
     */
    public function render_default_columns_field() {
        $value = get_option( 'gdrive_gallery_default_columns', 3 );
        ?>
        <input type="number" name="gdrive_gallery_default_columns" value="<?php echo esc_attr( $value ); ?>" min="1" max="6" class="small-text" />
        <?php
    }

    /**
     * Render default spacing field
     */
    public function render_default_spacing_field() {
        $value = get_option( 'gdrive_gallery_default_spacing', 10 );
        ?>
        <input type="number" name="gdrive_gallery_default_spacing" value="<?php echo esc_attr( $value ); ?>" min="0" max="50" class="small-text" />
        <span><?php esc_html_e( 'pixels', 'google-drive-gallery' ); ?></span>
        <?php
    }

    /**
     * Render default thumbnail size field
     */
    public function render_default_thumbnail_size_field() {
        $value = get_option( 'gdrive_gallery_default_thumbnail_size', 'medium' );
        ?>
        <select name="gdrive_gallery_default_thumbnail_size">
            <option value="small" <?php selected( $value, 'small' ); ?>><?php esc_html_e( 'Small', 'google-drive-gallery' ); ?></option>
            <option value="medium" <?php selected( $value, 'medium' ); ?>><?php esc_html_e( 'Medium', 'google-drive-gallery' ); ?></option>
            <option value="large" <?php selected( $value, 'large' ); ?>><?php esc_html_e( 'Large', 'google-drive-gallery' ); ?></option>
        </select>
        <?php
    }

    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        if ( ! isset( $_GET['oauth_callback'] ) || ! isset( $_GET['code'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Verify state
        if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'gdrive-oauth-state' ) ) {
            wp_die( esc_html__( 'Invalid state parameter', 'google-drive-gallery' ) );
        }

        $code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
        $result = GDrive_Auth::exchange_code_for_tokens( $code );

        if ( is_wp_error( $result ) ) {
            add_settings_error(
                'gdrive_gallery_oauth',
                'oauth_error',
                $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'gdrive_gallery_oauth',
                'oauth_success',
                __( 'Successfully connected to Google Drive', 'google-drive-gallery' ),
                'success'
            );
        }

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        
        $redirect_url = admin_url( 'admin.php?page=gdrive-gallery-settings&settings-updated=true' );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_gdrive-gallery-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'gdrive-gallery-admin',
            GDRIVE_GALLERY_URL . 'admin/css/admin-styles.css',
            [],
            GDRIVE_GALLERY_VERSION
        );

        wp_enqueue_script(
            'gdrive-gallery-admin',
            GDRIVE_GALLERY_URL . 'admin/js/admin-scripts.js',
            [ 'jquery' ],
            GDRIVE_GALLERY_VERSION,
            true
        );

        wp_localize_script( 'gdrive-gallery-admin', 'gdriveAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'gdrive-admin-nonce' ),
        ] );
    }

    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'gdrive-admin-nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'google-drive-gallery' ) ] );
        }

        $result = GDrive_API::test_connection();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'gdrive-admin-nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'google-drive-gallery' ) ] );
        }

        $count = GDrive_Cache::clear_all_cache();

        wp_send_json_success( [
            'message' => sprintf(
                /* translators: %d: number of cache entries */
                _n( 'Cleared %d cache entry', 'Cleared %d cache entries', $count, 'google-drive-gallery' ),
                $count
            ),
        ] );
    }
}
