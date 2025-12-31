<?php
/**
 * Plugin Name: Twitter Feed Display
 * Description: Display tweets from a Twitter account via shortcode and block using Twitter API v2.
 * Version: 0.1
 * Author: Generated
 * Text Domain: twitter-feed-display
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TFD_DIR', plugin_dir_path( __FILE__ ) );
define( 'TFD_URL', plugin_dir_url( __FILE__ ) );

class Twitter_Feed_Display {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'init', [ $this, 'register_block' ] );
        add_shortcode( 'twitter_feed', [ $this, 'render_shortcode' ] );
        // AJAX endpoints
        add_action( 'wp_ajax_tfd_load_more', [ $this, 'ajax_load_more' ] );
        add_action( 'wp_ajax_nopriv_tfd_load_more', [ $this, 'ajax_load_more' ] );
    }

    public function add_admin_menu() {
        add_options_page( 'Twitter Feed Display', 'Twitter Feed', 'manage_options', 'twitter-feed-settings', [ $this, 'settings_page' ] );
    }

    public function register_settings() {
        register_setting( 'tfd_settings', 'tfd_bearer_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'tfd_settings', 'tfd_username', [ 'sanitize_callback' => 'sanitize_text_field' ] );

        add_settings_section( 'tfd_main', 'Twitter API v2', function() {
            echo '<p>Enter your Twitter API v2 Bearer Token and the username of the account whose tweets you want to display.</p>';
        }, 'tfd_settings' );

        add_settings_field( 'tfd_bearer_token', 'Bearer Token', [ $this, 'field_bearer_token' ], 'tfd_settings', 'tfd_main' );
        add_settings_field( 'tfd_username', 'Twitter Username', [ $this, 'field_username' ], 'tfd_settings', 'tfd_main' );
    }

    public function field_bearer_token() {
        $val = esc_attr( get_option( 'tfd_bearer_token', '' ) );
        printf( '<input type="password" name="tfd_bearer_token" value="%s" class="regular-text" />', $val );
    }

    public function field_username() {
        $val = esc_attr( get_option( 'tfd_username', '' ) );
        printf( '<input type="text" name="tfd_username" value="%s" class="regular-text" placeholder="e.g., @twitter" />', $val );
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Twitter Feed Display</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'tfd_settings' ); ?>
                <?php do_settings_sections( 'tfd_settings' ); ?>
                <?php submit_button(); ?>
            </form>

            <hr />
            <h2>How to Get a Twitter API v2 Bearer Token</h2>
            <ol>
                <li>Go to <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank" rel="noopener">Twitter Developer Portal</a> and sign in.</li>
                <li>Create a new app (or use an existing one) and go to the <strong>Keys and tokens</strong> tab.</li>
                <li>Under <strong>Authentication Tokens and Keys</strong>, copy your <strong>Bearer Token</strong> (it starts with <code>AAAA...</code>).</li>
                <li>Make sure your app has <strong>Read-only</strong> permission or higher.</li>
                <li>Paste the Bearer Token into the <strong>Bearer Token</strong> field above and save.</li>
                <li>Enter the Twitter username (without @) in the <strong>Twitter Username</strong> field.</li>
            </ol>

            <h2>Shortcode</h2>
            <p>Use <code>[twitter_feed]</code> to display the feed. Optional attributes: <code>username</code>, <code>limit</code> (default 5, max 100).</p>
            <p>Example: <code>[twitter_feed username="twitter" limit="10"]</code></p>
        </div>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'tfd-style', TFD_URL . 'assets/tfd.css', [], '0.1' );
        wp_enqueue_script( 'tfd-js', TFD_URL . 'assets/tfd.js', [ 'jquery' ], '0.1', true );
        wp_localize_script( 'tfd-js', 'tfdAjax', array(
            'url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'tfd_ajax' ),
        ) );
    }

    /**
     * Register Gutenberg block
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            'tfd-block-editor',
            TFD_URL . 'block/editor.js',
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-server-side-render' ],
            '0.1',
            true
        );

        wp_localize_script( 'tfd-block-editor', 'tfdConfig', array(
            'defaultUsername' => get_option( 'tfd_username', '' ),
        ) );

        register_block_type( 'tfd/feed', array(
            'editor_script' => 'tfd-block-editor',
            'render_callback' => [ $this, 'render_block' ],
            'attributes' => array(
                'username' => array( 'type' => 'string', 'default' => get_option( 'tfd_username', '' ) ),
                'limit'    => array( 'type' => 'number', 'default' => 5 ),
            ),
        ) );
    }

    /**
     * Block server render callback
     */
    public function render_block( $attributes ) {
        $atts = array();
        if ( ! empty( $attributes['username'] ) ) {
            $atts['username'] = sanitize_text_field( $attributes['username'] );
        }
        if ( ! empty( $attributes['limit'] ) ) {
            $atts['limit'] = (int) $attributes['limit'];
        }

        return $this->render_shortcode( $atts );
    }

    /**
     * Render shortcode
     */
    public function render_shortcode( $atts ) {
        $defaults = [
            'username' => get_option( 'tfd_username', '' ),
            'limit'    => 5,
        ];

        $atts = shortcode_atts( $defaults, $atts, 'twitter_feed' );
        $username = sanitize_text_field( $atts['username'] );
        $limit = absint( $atts['limit'] );
        if ( $limit < 1 ) { $limit = 1; }
        if ( $limit > 100 ) { $limit = 100; }

        if ( empty( $username ) ) {
            return '<div class="tfd-error">Please configure a Twitter username in plugin settings or pass <code>username</code> to the shortcode.</div>';
        }

        $bearer_token = get_option( 'tfd_bearer_token', '' );
        if ( empty( $bearer_token ) ) {
            return '<div class="tfd-error">Missing Twitter API Bearer Token. Configure it in plugin settings.</div>';
        }

        // Remove @ if present
        $username = ltrim( $username, '@' );

        $cache_key = 'tfd_feed_' . md5( $username . '|' . $limit );
        $cached = get_transient( $cache_key );
        if ( $cached !== false && is_array( $cached ) && isset( $cached['html'] ) ) {
            return $cached['html'];
        }

        // Step 1: Get user ID from username
        $user_id = $this->get_user_id_by_username( $username, $bearer_token );
        if ( is_wp_error( $user_id ) ) {
            error_log( '[tfd] User lookup failed: ' . $user_id->get_error_message() );
            return '<div class="tfd-error">Error: Could not find Twitter user. Check the username and verify your API credentials.</div>';
        }

        // Step 2: Get tweets from user
        $tweets_response = $this->fetch_tweets( $user_id, $limit, '', $bearer_token );
        if ( is_wp_error( $tweets_response ) ) {
            error_log( '[tfd] Tweet fetch failed: ' . $tweets_response->get_error_message() );
            return '<div class="tfd-error">Error fetching tweets. Please check your API credentials.</div>';
        }

        $tweets = $tweets_response['tweets'];
        $next_token = $tweets_response['next_token'];

        if ( empty( $tweets ) ) {
            return '<div class="tfd-empty">No tweets found.</div>';
        }

        $html = $this->render_tweets_html( $tweets, $username, $next_token );

        // cache for 1 hour
        $cache_val = array( 'html' => $html, 'next_token' => $next_token );
        set_transient( $cache_key, $cache_val, HOUR_IN_SECONDS );

        return $html;
    }

    /**
     * Get Twitter user ID from username
     */
    private function get_user_id_by_username( $username, $bearer_token ) {
        $url = add_query_arg( [
            'usernames' => $username,
            'user.fields' => 'id,name,username,public_metrics'
        ], 'https://api.twitter.com/2/users/by' );

        $resp = wp_remote_get( $url, [
            'headers' => [ 'Authorization' => 'Bearer ' . $bearer_token ],
            'timeout' => 10
        ] );

        if ( is_wp_error( $resp ) ) {
            return $resp;
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        $data = json_decode( $body, true );

        if ( $code !== 200 || ! isset( $data['data'][0]['id'] ) ) {
            error_log( '[tfd] User API response: HTTP ' . $code . ' Body: ' . $body );
            return new WP_Error( 'user_not_found', 'Could not retrieve user ID.' );
        }

        return $data['data'][0]['id'];
    }

    /**
     * Fetch tweets for a user
     */
    private function fetch_tweets( $user_id, $limit, $pagination_token, $bearer_token ) {
        $args = [
            'max_results' => min( $limit, 100 ),
            'tweet.fields' => 'created_at,public_metrics,author_id',
            'expansions' => 'author_id',
            'user.fields' => 'username,name,profile_image_url'
        ];

        if ( $pagination_token ) {
            $args['pagination_token'] = $pagination_token;
        }

        $url = add_query_arg( $args, 'https://api.twitter.com/2/tweets/' . rawurlencode( $user_id ));

        $resp = wp_remote_get( $url, [
            'headers' => [ 'Authorization' => 'Bearer ' . $bearer_token ],
            'timeout' => 10
        ] );

        if ( is_wp_error( $resp ) ) {
            return $resp;
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        $data = json_decode( $body, true );

        if ( $code !== 200 || ! isset( $data['data'] ) ) {
            error_log( '[tfd] Tweets API response: HTTP ' . $code . ' Body: ' . $body );
            return new WP_Error( 'tweets_error', 'Could not fetch tweets.' );
        }

        $next_token = isset( $data['meta']['next_token'] ) ? sanitize_text_field( $data['meta']['next_token'] ) : '';

        return [
            'tweets' => $data['data'],
            'includes' => $data['includes'] ?? [],
            'next_token' => $next_token
        ];
    }

    /**
     * Render tweets as HTML
     */
    private function render_tweets_html( $tweets, $username, $next_token ) {
        $html = '<div class="tfd-feed" data-username="' . esc_attr( $username ) . '">';

        foreach ( $tweets as $tweet ) {
            $id = $tweet['id'] ?? '';
            $text = $tweet['text'] ?? '';
            $created_at = $tweet['created_at'] ?? '';
            $retweets = $tweet['public_metrics']['retweet_count'] ?? 0;
            $likes = $tweet['public_metrics']['like_count'] ?? 0;

            // Format date
            $date_formatted = esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $created_at ) ) );

            // Tweet link
            $tweet_link = 'https://twitter.com/' . esc_attr( $username ) . '/status/' . esc_attr( $id );

            // Basic tweet text truncation and linkification
            $text = wp_kses_post( wp_trim_words( $text, 30 ) );

            $html .= '<article class="tfd-tweet">';
            $html .= '<div class="tfd-tweet-header">';
            $html .= '<span class="tfd-username">@' . esc_html( $username ) . '</span>';
            $html .= '<span class="tfd-date"><a href="' . esc_url( $tweet_link ) . '" target="_blank" rel="noopener noreferrer">' . $date_formatted . '</a></span>';
            $html .= '</div>';
            $html .= '<div class="tfd-tweet-text">' . $text . '</div>';
            $html .= '<div class="tfd-tweet-stats">';
            $html .= '<span class="tfd-stat">❤️ ' . absint( $likes ) . '</span>';
            $html .= '<span class="tfd-stat">🔄 ' . absint( $retweets ) . '</span>';
            $html .= '</div>';
            $html .= '</article>';
        }

        $html .= '</div>';

        // Load more button
        if ( $next_token ) {
            $html .= '<div class="tfd-loadmore-wrap"><button class="tfd-loadmore" data-username="' . esc_attr( $username ) . '" data-next-token="' . esc_attr( $next_token ) . '" data-limit="' . esc_attr( count( $tweets ) ) . '">Load more tweets</button></div>';
        }

        return $html;
    }

    /**
     * AJAX load more handler
     */
    public function ajax_load_more() {
        check_ajax_referer( 'tfd_ajax', 'nonce' );

        $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
        $next_token = isset( $_POST['next_token'] ) ? sanitize_text_field( wp_unslash( $_POST['next_token'] ) ) : '';
        $limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 5;

        if ( empty( $username ) || empty( $next_token ) ) {
            wp_send_json_error( 'missing_params' );
        }

        $bearer_token = get_option( 'tfd_bearer_token', '' );
        if ( empty( $bearer_token ) ) {
            wp_send_json_error( 'missing_token' );
        }

        // Remove @ if present
        $username = ltrim( $username, '@' );

        // Get user ID
        $user_id = $this->get_user_id_by_username( $username, $bearer_token );
        if ( is_wp_error( $user_id ) ) {
            error_log( '[tfd] AJAX user lookup failed: ' . $user_id->get_error_message() );
            wp_send_json_error( 'user_error' );
        }

        // Fetch tweets
        $tweets_response = $this->fetch_tweets( $user_id, $limit, $next_token, $bearer_token );
        if ( is_wp_error( $tweets_response ) ) {
            error_log( '[tfd] AJAX tweet fetch failed: ' . $tweets_response->get_error_message() );
            wp_send_json_error( 'tweets_error' );
        }

        $tweets = $tweets_response['tweets'];
        $new_next_token = $tweets_response['next_token'];

        if ( empty( $tweets ) ) {
            wp_send_json_success( [ 'html' => '', 'next_token' => '' ] );
        }

        $html = '';
        foreach ( $tweets as $tweet ) {
            $id = $tweet['id'] ?? '';
            $text = $tweet['text'] ?? '';
            $created_at = $tweet['created_at'] ?? '';
            $retweets = $tweet['public_metrics']['retweet_count'] ?? 0;
            $likes = $tweet['public_metrics']['like_count'] ?? 0;

            $date_formatted = esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $created_at ) ) );
            $tweet_link = 'https://twitter.com/' . esc_attr( $username ) . '/status/' . esc_attr( $id );
            $text = wp_kses_post( wp_trim_words( $text, 30 ) );

            $html .= '<article class="tfd-tweet">';
            $html .= '<div class="tfd-tweet-header">';
            $html .= '<span class="tfd-username">@' . esc_html( $username ) . '</span>';
            $html .= '<span class="tfd-date"><a href="' . esc_url( $tweet_link ) . '" target="_blank" rel="noopener noreferrer">' . $date_formatted . '</a></span>';
            $html .= '</div>';
            $html .= '<div class="tfd-tweet-text">' . $text . '</div>';
            $html .= '<div class="tfd-tweet-stats">';
            $html .= '<span class="tfd-stat">❤️ ' . absint( $likes ) . '</span>';
            $html .= '<span class="tfd-stat">🔄 ' . absint( $retweets ) . '</span>';
            $html .= '</div>';
            $html .= '</article>';
        }

        wp_send_json_success( [ 'html' => $html, 'next_token' => $new_next_token ] );
    }
}

new Twitter_Feed_Display();
