<?php
/**
 * Plugin Name: Facebook Page Feed
 * Description: Display a Facebook Page feed via shortcode and block (simple, cached server-side rendering).
 * Version: 0.1
 * Author: Generated
 * Text Domain: facebook-page-feed
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FBPF_DIR', plugin_dir_path( __FILE__ ) );
define( 'FBPF_URL', plugin_dir_url( __FILE__ ) );

class FB_Page_Feed {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'init', [ $this, 'register_block' ] );
        add_shortcode( 'facebook_feed', [ $this, 'render_shortcode' ] );
        // AJAX endpoint for load-more
        add_action( 'wp_ajax_fbpf_load_more', [ $this, 'ajax_load_more' ] );
        add_action( 'wp_ajax_nopriv_fbpf_load_more', [ $this, 'ajax_load_more' ] );
    }

    public function add_admin_menu() {
        add_options_page( 'Facebook Page Feed', 'Facebook Feed', 'manage_options', 'fb-page-feed', [ $this, 'settings_page' ] );
    }

    public function register_settings() {
        register_setting( 'fbpf_settings', 'fbpf_page_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'fbpf_settings', 'fbpf_access_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );

        add_settings_section( 'fbpf_main', 'Facebook API', function() {
            echo '<p>Enter a Page ID and a Page Access Token (or App token) to fetch public posts.</p>';
        }, 'fbpf_settings' );

        add_settings_field( 'fbpf_page_id', 'Page ID', [ $this, 'field_page_id' ], 'fbpf_settings', 'fbpf_main' );
        add_settings_field( 'fbpf_access_token', 'Access Token', [ $this, 'field_access_token' ], 'fbpf_settings', 'fbpf_main' );
    }

    public function field_page_id() {
        $val = esc_attr( get_option( 'fbpf_page_id', '' ) );
        printf( '<input type="text" name="fbpf_page_id" value="%s" class="regular-text" />', $val );
    }

    public function field_access_token() {
        $val = esc_attr( get_option( 'fbpf_access_token', '' ) );
        printf( '<input type="password" name="fbpf_access_token" value="%s" class="regular-text" />', $val );
        echo '<p class="description">Use a page access token or app access token. Keep this secret.</p>';
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Facebook Page Feed</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'fbpf_settings' ); ?>
                <?php do_settings_sections( 'fbpf_settings' ); ?>
                <?php submit_button(); ?>
            </form>
            <hr />
            <h2>How to Get a Page Access Token</h2>
            <ol>
                <li>Create a Facebook App at <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">developers.facebook.com</a>.</li>
                <li>In App Dashboard add the "Facebook Login" and follow setup (use "Client OAuth Login" if required).</li>
                <li>Get an App Access Token (<code>{app_id}|{app_secret}</code>) or generate a Page Access Token via the Graph API Explorer.</li>
                <li>For public page posts you can use an App Access Token or a Page token with proper permissions. Keep tokens secret.</li>
                <li>Paste the token into the <strong>Access Token</strong> field above and save.</li>
            </ol>
            <h2>Shortcode</h2>
            <p>Use <code>[facebook_feed]</code> to display the feed. Optional attributes: <code>page_id</code>, <code>limit</code> (default 5).</p>
        </div>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'fbpf-style', FBPF_URL . 'assets/fbpf.css', [], '0.1' );
        wp_enqueue_script( 'fbpf-js', FBPF_URL . 'assets/fbpf.js', [ 'jquery' ], '0.1', true );
        wp_localize_script( 'fbpf-js', 'fbpfAjax', array(
            'url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'fbpf_ajax' ),
        ) );
    }

    /**
     * Register Gutenberg block for the feed
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            'fbpf-block-editor',
            FBPF_URL . 'block/editor.js',
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-server-side-render' ],
            '0.1',
            true
        );

        wp_localize_script( 'fbpf-block-editor', 'fbpfConfig', array(
            'defaultPageId' => get_option( 'fbpf_page_id', '' ),
        ) );

        register_block_type( 'fbpf/feed', array(
            'editor_script' => 'fbpf-block-editor',
            'render_callback' => [ $this, 'render_block' ],
            'attributes' => array(
                'pageId' => array( 'type' => 'string', 'default' => get_option( 'fbpf_page_id', '' ) ),
                'limit'  => array( 'type' => 'number', 'default' => 5 ),
            ),
        ) );
    }

    /**
     * Block server render callback
     */
    public function render_block( $attributes ) {
        $atts = array();
        if ( ! empty( $attributes['pageId'] ) ) {
            $atts['page_id'] = sanitize_text_field( $attributes['pageId'] );
        }
        if ( ! empty( $attributes['limit'] ) ) {
            $atts['limit'] = (int) $attributes['limit'];
        }

        return $this->render_shortcode( $atts );
    }

    /**
     * AJAX handler for loading more posts
     */
    public function ajax_load_more() {
        check_ajax_referer( 'fbpf_ajax', 'nonce' );

        $page_id = isset( $_POST['page_id'] ) ? sanitize_text_field( wp_unslash( $_POST['page_id'] ) ) : '';
        $after = isset( $_POST['after'] ) ? sanitize_text_field( wp_unslash( $_POST['after'] ) ) : '';
        $limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 5;

        if ( empty( $page_id ) ) {
            wp_send_json_error( 'missing_page_id' );
        }

        $token = get_option( 'fbpf_access_token', '' );
        if ( empty( $token ) ) {
            wp_send_json_error( 'missing_token' );
        }

        $fields = 'message,created_time,full_picture,permalink_url';
        $args = array(
            'fields' => $fields,
            'limit'  => $limit,
            'access_token' => $token,
        );
        if ( $after ) {
            $args['after'] = $after;
        }

        $url = add_query_arg( $args, 'https://graph.facebook.com/v17.0/' . rawurlencode( $page_id ) . '/posts' );

        $resp = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $resp ) ) {
            error_log( '[fbpf] AJAX load_more API error: ' . $resp->get_error_message() );
            wp_send_json_error( 'api_error' );
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        $data = json_decode( $body, true );

        if ( $code !== 200 || ! isset( $data['data'] ) ) {
            error_log( '[fbpf] AJAX load_more unexpected response. HTTP ' . $code . ' Body: ' . $body );
            wp_send_json_error( 'api_bad_response' );
        }

        $html = '';
        foreach ( $data['data'] as $post ) {
            $msg = isset( $post['message'] ) ? wp_kses_post( wp_trim_words( $post['message'], 30 ) ) : '';
            $time = isset( $post['created_time'] ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $post['created_time'] ) ) ) : '';
            $thumb = isset( $post['full_picture'] ) ? esc_url( $post['full_picture'] ) : '';
            $link = isset( $post['permalink_url'] ) ? esc_url( $post['permalink_url'] ) : '';

            $html .= '<article class="fbpf-item">';
            if ( $thumb ) {
                $html .= '<a class="fbpf-thumb" href="' . $link . '" target="_blank" rel="noopener noreferrer"><img src="' . $thumb . '" alt=""/></a>';
            }
            $html .= '<div class="fbpf-body">';
            if ( $msg ) {
                $html .= '<div class="fbpf-message">' . $msg . '</div>';
            }
            if ( $time ) {
                $html .= '<div class="fbpf-time"><a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $time . '</a></div>';
            }
            $html .= '</div>';
            $html .= '</article>';
        }

        $next_after = '';
        if ( isset( $data['paging']['cursors']['after'] ) ) {
            $next_after = sanitize_text_field( $data['paging']['cursors']['after'] );
        }

        wp_send_json_success( array( 'html' => $html, 'after' => $next_after ) );
    }

    public function render_shortcode( $atts ) {
        $defaults = [
            'page_id' => get_option( 'fbpf_page_id', '' ),
            'limit'   => 5,
        ];

        $atts = shortcode_atts( $defaults, $atts, 'facebook_feed' );
        $page_id = sanitize_text_field( $atts['page_id'] );
        $limit = absint( $atts['limit'] );
        if ( $limit < 1 ) { $limit = 1; }
        if ( $limit > 20 ) { $limit = 20; }

        if ( empty( $page_id ) ) {
            return '<div class="fbpf-error">Please configure a Page ID in plugin settings or pass <code>page_id</code> to the shortcode.</div>';
        }

        $token = get_option( 'fbpf_access_token', '' );
        if ( empty( $token ) ) {
            return '<div class="fbpf-error">Missing Facebook access token. Configure it in plugin settings.</div>';
        }

        $cache_key = 'fbpf_feed_' . md5( $page_id . '|' . $limit );
        $cached = get_transient( $cache_key );
        if ( $cached !== false && is_array( $cached ) && isset( $cached['html'] ) ) {
            // cached contains html and maybe next cursor
            return $cached['html'];
        }

        $fields = 'message,created_time,full_picture,permalink_url';
        $url = add_query_arg( [
            'fields' => $fields,
            'limit'  => $limit,
            'access_token' => $token,
        ], 'https://graph.facebook.com/v17.0/' . rawurlencode( $page_id ) . '/posts' );

        $resp = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $resp ) ) {
            error_log( '[fbpf] API request failed: ' . $resp->get_error_message() );
            return '<div class="fbpf-error">Error fetching feed. Check your access token and network connection.</div>';
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        $data = json_decode( $body, true );

        if ( $code !== 200 || ! isset( $data['data'] ) ) {
            // log full response for debugging (do not expose token)
            error_log( '[fbpf] Unexpected API response. HTTP ' . $code . ' Body: ' . $body );
            $msg = 'Error fetching feed from Facebook API.';
            if ( isset( $data['error']['message'] ) ) {
                $msg .= ' ' . esc_html( $data['error']['message'] );
            }
            $msg .= ' Please verify your Page ID and Access Token in plugin settings.';
            return '<div class="fbpf-error">' . esc_html( $msg ) . '</div>';
        }

        $html = '<div class="fbpf-feed" data-page-id="' . esc_attr( $page_id ) . '">';
        foreach ( $data['data'] as $post ) {
            $msg = isset( $post['message'] ) ? wp_kses_post( wp_trim_words( $post['message'], 30 ) ) : '';
            $time = isset( $post['created_time'] ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $post['created_time'] ) ) ) : '';
            $thumb = isset( $post['full_picture'] ) ? esc_url( $post['full_picture'] ) : '';
            $link = isset( $post['permalink_url'] ) ? esc_url( $post['permalink_url'] ) : '';

            $html .= '<article class="fbpf-item">';
            if ( $thumb ) {
                $html .= '<a class="fbpf-thumb" href="' . $link . '" target="_blank" rel="noopener noreferrer"><img src="' . $thumb . '" alt=""/></a>';
            }
            $html .= '<div class="fbpf-body">';
            if ( $msg ) {
                $html .= '<div class="fbpf-message">' . $msg . '</div>';
            }
            if ( $time ) {
                $html .= '<div class="fbpf-time"><a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $time . '</a></div>';
            }
            $html .= '</div>';
            $html .= '</article>';
        }
        $html .= '</div>';

        // If paging cursor exists, include a Load More button with data-after
        $after = '';
        if ( isset( $data['paging']['cursors']['after'] ) ) {
            $after = sanitize_text_field( $data['paging']['cursors']['after'] );
            $html .= '<div class="fbpf-loadmore-wrap"><button class="fbpf-loadmore" data-page-id="' . esc_attr( $page_id ) . '" data-after="' . esc_attr( $after ) . '" data-limit="' . esc_attr( $limit ) . '">Load more</button></div>';
        }

        // cache both html and next cursor for 6 hours
        $cache_val = array( 'html' => $html, 'after' => $after );
        set_transient( $cache_key, $cache_val, 6 * HOUR_IN_SECONDS );

        return $html;
    }
}

new FB_Page_Feed();
