<?php
/**
 * Plugin Name: YouTube Feed Display
 * Plugin URI: https://example.com
 * Description: Display your YouTube channel feed in a beautiful tile format
 * Version: 1.5
 * Author: Mason Grandusky
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: youtube-feed-display
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'YOUTUBE_FEED_DIR', plugin_dir_path( __FILE__ ) );
define( 'YOUTUBE_FEED_URL', plugin_dir_url( __FILE__ ) );
define( 'YOUTUBE_FEED_VERSION', '1.5' );

/**
 * YouTube Feed Display Plugin
 */
class YouTube_Feed_Display {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
        add_shortcode( 'youtube_feed', [ $this, 'render_youtube_feed_shortcode' ] );
        add_shortcode( 'youtube_playlists', [ $this, 'render_youtube_playlists_shortcode' ] );
        add_action( 'init', [ $this, 'register_block' ] );
        // AJAX endpoint to fetch YouTube comments
        add_action( 'wp_ajax_yfd_fetch_comments', [ $this, 'ajax_fetch_comments' ] );
        add_action( 'wp_ajax_nopriv_yfd_fetch_comments', [ $this, 'ajax_fetch_comments' ] );
        // AJAX endpoint to fetch YouTube playlists
        add_action( 'wp_ajax_yfd_fetch_playlists', [ $this, 'ajax_fetch_playlists' ] );
        add_action( 'wp_ajax_nopriv_yfd_fetch_playlists', [ $this, 'ajax_fetch_playlists' ] );
        // AJAX endpoint to fetch playlist videos
        add_action( 'wp_ajax_yfd_fetch_playlist_videos', [ $this, 'ajax_fetch_playlist_videos' ] );
        add_action( 'wp_ajax_nopriv_yfd_fetch_playlist_videos', [ $this, 'ajax_fetch_playlist_videos' ] );
    }

    /**
     * Render Lightbox settings in their own admin page
     */
    public function render_lightbox_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $yfd_mobile_breakpoint = get_option( 'yfd_mobile_breakpoint', 600 );
        $yfd_force_title_lightbox = get_option( 'yfd_force_title_lightbox', 0 );
        ?>
        <div class="wrap">
            <h1>Lightbox Settings</h1>
            <p class="description">Manage the lightbox behavior for the YouTube feed. <a href="admin.php?page=youtube-feed-settings">Return to main YouTube Feed settings</a>.</p>
            <form method="post" action="options.php">
                <?php settings_fields( 'youtube_feed_lightbox_settings' ); ?>
                <?php do_settings_sections( 'youtube_feed_lightbox' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'YouTube Feed Settings',
            'YouTube Feed',
            'manage_options',
            'youtube-feed-settings',
            [ $this, 'render_settings_page' ],
            'dashicons-video-alt3',
            30
        );
        // Submenu for Lightbox-specific settings
        add_submenu_page(
            'youtube-feed-settings',
            'Lightbox Settings',
            'Lightbox',
            'manage_options',
            'youtube-feed-lightbox',
            [ $this, 'render_lightbox_settings_page' ]
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'youtube_feed_settings', 'youtube_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'youtube_feed_settings', 'youtube_channel_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'youtube_feed_settings', 'youtube_video_count', [ 'sanitize_callback' => [ $this, 'sanitize_youtube_video_count' ] ] );
        register_setting( 'youtube_feed_settings', 'youtube_columns', [ 'sanitize_callback' => [ $this, 'sanitize_youtube_columns' ] ] );

        // Lightbox options are registered under their own settings group so they can be managed separately
        register_setting( 'youtube_feed_lightbox_settings', 'yfd_mobile_breakpoint', [ 'sanitize_callback' => [ $this, 'sanitize_yfd_mobile_breakpoint' ] ] );
        register_setting( 'youtube_feed_lightbox_settings', 'yfd_force_title_lightbox', [ 'sanitize_callback' => [ $this, 'sanitize_yfd_force_title_lightbox' ] ] );
        register_setting( 'youtube_feed_lightbox_settings', 'yfd_show_comments', [ 'sanitize_callback' => [ $this, 'sanitize_yfd_show_comments' ] ] );
        register_setting( 'youtube_feed_settings', 'yfd_show_playlists', [ 'sanitize_callback' => [ $this, 'sanitize_yfd_show_playlists' ] ] );

        // Register settings section and fields for Lightbox settings page
        add_settings_section(
            'yfd_lightbox_section',
            __( 'Lightbox Options', 'youtube-feed-display' ),
            [ $this, 'yfd_lightbox_section_cb' ],
            'youtube_feed_lightbox'
        );

        add_settings_field(
            'yfd_mobile_breakpoint',
            __( 'Mobile Breakpoint (px)', 'youtube-feed-display' ),
            [ $this, 'yfd_render_mobile_breakpoint_field' ],
            'youtube_feed_lightbox',
            'yfd_lightbox_section'
        );

        add_settings_field(
            'yfd_force_title_lightbox',
            __( 'Force Titles in Lightbox', 'youtube-feed-display' ),
            [ $this, 'yfd_render_force_title_field' ],
            'youtube_feed_lightbox',
            'yfd_lightbox_section'
        );

        add_settings_field(
            'yfd_show_comments',
            __( 'Show YouTube Comments', 'youtube-feed-display' ),
            [ $this, 'yfd_render_show_comments_field' ],
            'youtube_feed_lightbox',
            'yfd_lightbox_section'
        );

        // Playlist settings
        add_settings_section(
            'yfd_playlist_section',
            __( 'Playlist Options', 'youtube-feed-display' ),
            [ $this, 'yfd_playlist_section_cb' ],
            'youtube_feed_settings'
        );

        add_settings_field(
            'yfd_show_playlists',
            __( 'Show Channel Playlists', 'youtube-feed-display' ),
            [ $this, 'yfd_render_show_playlists_field' ],
            'youtube_feed_settings',
            'yfd_playlist_section'
        );
    }

    /**
     * Section description callback for Lightbox options
     */
    public function yfd_lightbox_section_cb() {
        echo '<p>' . esc_html__( 'Control how the lightbox behaves for video thumbnails and titles.', 'youtube-feed-display' ) . '</p>';
    }

    /**
     * Render mobile breakpoint field
     */
    public function yfd_render_mobile_breakpoint_field() {
        $val = esc_attr( get_option( 'yfd_mobile_breakpoint', 600 ) );
        printf( '<input type="number" name="yfd_mobile_breakpoint" id="yfd_mobile_breakpoint" value="%s" class="small-text" min="320" max="2000" />', $val );
        echo '<p class="description">' . esc_html__( 'Viewport width (in pixels) below which title links open in the lightbox when marked mobile-only.', 'youtube-feed-display' ) . '</p>';
    }

    /**
     * Render force title checkbox field
     */
    public function yfd_render_force_title_field() {
        $val = get_option( 'yfd_force_title_lightbox', 0 );
        printf( '<label><input type="checkbox" name="yfd_force_title_lightbox" id="yfd_force_title_lightbox" value="1" %s /> %s</label>', checked( 1, $val, false ), esc_html__( 'Always open titles in the lightbox', 'youtube-feed-display' ) );
        echo '<p class="description">' . esc_html__( 'When checked, clicking titles will open the video in the lightbox on all screen sizes.', 'youtube-feed-display' ) . '</p>';
    }

    /**
     * Render show comments checkbox field
     */
    public function yfd_render_show_comments_field() {
        $val = get_option( 'yfd_show_comments', 1 );
        printf( '<label><input type="checkbox" name="yfd_show_comments" id="yfd_show_comments" value="1" %s /> %s</label>', checked( 1, $val, false ), esc_html__( 'Display YouTube comments in the lightbox sidebar', 'youtube-feed-display' ) );
        echo '<p class="description">' . esc_html__( 'When checked, YouTube comments will appear to the right of the video (requires YouTube API key).', 'youtube-feed-display' ) . '</p>';
    }

    /**
     * Section description callback for Playlist options
     */
    public function yfd_playlist_section_cb() {
        echo '<p>' . esc_html__( 'Display playlists from your YouTube channel.', 'youtube-feed-display' ) . '</p>';
    }

    /**
     * Render show playlists checkbox field
     */
    public function yfd_render_show_playlists_field() {
        $val = get_option( 'yfd_show_playlists', 0 );
        printf( '<label><input type="checkbox" name="yfd_show_playlists" id="yfd_show_playlists" value="1" %s /> %s</label>', checked( 1, $val, false ), esc_html__( 'Enable playlist display', 'youtube-feed-display' ) );
        echo '<p class="description">' . esc_html__( 'When checked, you can use the [youtube_playlists] shortcode to display your channel playlists (requires YouTube API key).', 'youtube-feed-display' ) . '</p>';
    }

    /**
     * Sanitize number of videos
     */
    public function sanitize_youtube_video_count( $val ) {
        $v = absint( $val );
        if ( $v < 1 ) {
            $v = 1;
        }
        if ( $v > 50 ) {
            $v = 50;
        }
        return $v;
    }

    /**
     * Sanitize columns (allowed: 2,3,4)
     */
    public function sanitize_youtube_columns( $val ) {
        $v = absint( $val );
        $allowed = array( 2, 3, 4 );
        if ( ! in_array( $v, $allowed, true ) ) {
            $v = 3;
        }
        return $v;
    }

    /**
     * Sanitize mobile breakpoint
     */
    public function sanitize_yfd_mobile_breakpoint( $val ) {
        $v = absint( $val );
        if ( $v < 320 ) {
            $v = 320;
        }
        if ( $v > 2000 ) {
            $v = 2000;
        }
        return $v;
    }

    /**
     * Sanitize force title flag
     */
    public function sanitize_yfd_force_title_lightbox( $val ) {
        return ( $val ) ? 1 : 0;
    }

    /**
     * Sanitize show comments flag
     */
    public function sanitize_yfd_show_comments( $val ) {
        return ( $val ) ? 1 : 0;
    }

    /**
     * Sanitize show playlists flag
     */
    public function sanitize_yfd_show_playlists( $val ) {
        return ( $val ) ? 1 : 0;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $api_key = get_option( 'youtube_api_key', '' );
        $channel_id = get_option( 'youtube_channel_id', '' );
        $video_count = get_option( 'youtube_video_count', '12' );
        $columns = get_option( 'youtube_columns', '3' );
        $yfd_mobile_breakpoint = get_option( 'yfd_mobile_breakpoint', 600 );
        $yfd_force_title_lightbox = get_option( 'yfd_force_title_lightbox', 0 );
        ?>
        <div class="wrap">
            <h1>YouTube Feed Display Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'youtube_feed_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="youtube_api_key">YouTube API Key</label>
                        </th>
                        <td>
                            <input 
                                type="password" 
                                name="youtube_api_key" 
                                id="youtube_api_key" 
                                value="<?php echo esc_attr( $api_key ); ?>"
                                class="regular-text"
                                placeholder="Enter your YouTube Data API v3 key"
                            />
                            <p class="description">
                                Get your API key from <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="youtube_channel_id">YouTube Channel ID</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                name="youtube_channel_id" 
                                id="youtube_channel_id" 
                                value="<?php echo esc_attr( $channel_id ); ?>"
                                class="regular-text"
                                placeholder="e.g., UCddiUEpYJcSeBZX1IqULwUQ"
                            />
                            <p class="description">
                                Find your channel ID in your YouTube channel settings or use <a href="https://www.youtube.com/@YOUR_CHANNEL/about" target="_blank">your channel URL</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="youtube_video_count">Number of Videos to Display</label>
                        </th>
                        <td>
                            <input 
                                type="number" 
                                name="youtube_video_count" 
                                id="youtube_video_count" 
                                value="<?php echo esc_attr( $video_count ); ?>"
                                min="1"
                                max="50"
                                class="small-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="youtube_columns">Grid Columns</label>
                        </th>
                        <td>
                            <select name="youtube_columns" id="youtube_columns">
                                <option value="2" <?php selected( $columns, '2' ); ?>>2 Columns</option>
                                <option value="3" <?php selected( $columns, '3' ); ?>>3 Columns</option>
                                <option value="4" <?php selected( $columns, '4' ); ?>>4 Columns</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <!-- Playlist and other settings sections -->
                <?php do_settings_sections( 'youtube_feed_settings' ); ?>

                <?php submit_button(); ?>
            </form>

            <hr style="margin-top: 30px;">
            <h2>How to Use</h2>
            
            <h3>Display YouTube Videos</h3>
            <p>Add the following shortcode to any page or post to display your YouTube feed:</p>
            <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0;">[youtube_feed]</code>
            <p>Or use this code in your theme template:</p>
            <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0;">&lt;?php echo do_shortcode( '[youtube_feed]' ); ?&gt;</code>
            <p>Optional attributes:</p>
            <ul>
                <li><code>video_count</code> - Number of videos to display (default: 12)</li>
                <li><code>columns</code> - Grid columns (2, 3, or 4)</li>
                <li><code>channel_id</code> - Override the channel ID</li>
            </ul>

            <h3>Display Channel Playlists</h3>
            <p>If you've enabled the playlist feature in settings, use this shortcode to display your playlists:</p>
            <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0;">[youtube_playlists]</code>
            <p>This displays all playlists from your channel. Optional attributes:</p>
            <ul>
                <li><code>columns</code> - Grid columns (2, 3, or 4, default: 3)</li>
                <li><code>channel_id</code> - Override the channel ID</li>
            </ul>

            <h3>Display a Specific Playlist</h3>
            <p>To display a single playlist by ID, use:</p>
            <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0;">[youtube_playlists playlist_id="PLxxxxxxxxxxxxxx"]</code>
            <p>Replace <code>PLxxxxxxxxxxxxxx</code> with your playlist ID (found in YouTube playlist URLs as the <code>list</code> parameter). You can find your playlist ID by:</p>
            <ol>
                <li>Open the playlist on YouTube</li>
                <li>Copy the URL - it will look like: <code>https://www.youtube.com/playlist?list=PLxxxxxxxxxxxxxx</code></li>
                <li>Use the <code>list</code> parameter value as your <code>playlist_id</code></li>
            </ol>
            <p>Optional attributes when using playlist_id:</p>
            <ul>
                <li><code>columns</code> - Grid columns (2, 3, or 4, default: 3)</li>
            </ul>

        </div>
        <?php
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style( 'youtube-feed-style', YOUTUBE_FEED_URL . 'css/youtube-feed.css', [], YOUTUBE_FEED_VERSION );

        // Lightbox assets for opening videos in an overlay
        wp_enqueue_style( 'yfd-lightbox', YOUTUBE_FEED_URL . 'assets/yfd-lightbox.css', [], YOUTUBE_FEED_VERSION );
        wp_enqueue_script( 'yfd-lightbox', YOUTUBE_FEED_URL . 'assets/yfd-lightbox.js', [ 'jquery' ], YOUTUBE_FEED_VERSION, true );
        // Pass configuration to JS
        $cfg = array(
            'mobileBreakpoint' => (int) get_option( 'yfd_mobile_breakpoint', 600 ),
            'forceTitleLightbox' => (bool) get_option( 'yfd_force_title_lightbox', 0 ),
            'showComments' => (bool) get_option( 'yfd_show_comments', 1 ),
        );
        wp_localize_script( 'yfd-lightbox', 'yfdLightboxConfig', $cfg );

        // AJAX data for comments
        wp_localize_script( 'yfd-lightbox', 'yfdAjax', array(
            'url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'yfd_ajax' ),
        ) );
    }



    /**
     * AJAX: fetch YouTube comments for a video via YouTube API
     */
    public function ajax_fetch_comments() {
        $video_id = isset( $_REQUEST['video_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['video_id'] ) ) : '';
        if ( empty( $video_id ) ) {
            wp_send_json_error( 'missing_video_id' );
        }

        $api_key = get_option( 'youtube_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( 'missing_api_key' );
        }

        // Fetch comment threads from YouTube API
        $url = add_query_arg(
            [
                'part'           => 'snippet',
                'videoId'        => $video_id,
                'maxResults'     => 20,
                'textFormat'     => 'plainText',
                'order'          => 'relevance',
                'key'            => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/commentThreads'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'api_error' );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['items'] ) || empty( $data['items'] ) ) {
            wp_send_json_success( [] );
        }

        // Extract comment details from threads
        $comments = [];
        foreach ( $data['items'] as $item ) {
            if ( isset( $item['snippet']['topLevelComment'] ) ) {
                $comment = $item['snippet']['topLevelComment']['snippet'];
                $comments[] = [
                    'author' => $comment['authorDisplayName'] ?? 'Anonymous',
                    'text' => $comment['textDisplay'] ?? '',
                    'likeCount' => $comment['likeCount'] ?? 0,
                    'publishedAt' => $comment['publishedAt'] ?? '',
                    'authorProfileImageUrl' => $comment['authorProfileImageUrl'] ?? '',
                ];
            }
        }

        wp_send_json_success( $comments );
    }

    /**
     * AJAX: fetch videos from a YouTube playlist via YouTube API
     */
    public function ajax_fetch_playlist_videos() {
        $playlist_id = isset( $_REQUEST['playlist_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['playlist_id'] ) ) : '';
        if ( empty( $playlist_id ) ) {
            wp_send_json_error( 'missing_playlist_id' );
        }

        $api_key = get_option( 'youtube_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( 'missing_api_key' );
        }

        // Fetch playlist items from YouTube API
        $url = add_query_arg(
            [
                'part'           => 'snippet',
                'playlistId'     => $playlist_id,
                'maxResults'     => 50,
                'key'            => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/playlistItems'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'api_error' );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['items'] ) || empty( $data['items'] ) ) {
            wp_send_json_success( [] );
        }

        // Extract video details from playlist items
        $videos = [];
        foreach ( $data['items'] as $item ) {
            if ( isset( $item['snippet'] ) ) {
                $videos[] = [
                    'id'    => $item['snippet']['resourceId']['videoId'] ?? '',
                    'title' => $item['snippet']['title'] ?? '',
                    'thumb' => $item['snippet']['thumbnails']['default']['url'] ?? '',
                ];
            }
        }

        wp_send_json_success( $videos );
    }

    /**
     * Render YouTube playlists shortcode
     */
    public function render_youtube_playlists_shortcode( $atts ) {
        $show_playlists = get_option( 'yfd_show_playlists', 0 );
        if ( ! $show_playlists ) {
            return '<div class="notice notice-warning"><p>YouTube Playlists: Feature is not enabled in settings.</p></div>';
        }

        $defaults = [
            'columns'   => 3,
            'channel_id' => get_option( 'youtube_channel_id', '' ),
            'playlist_id' => '',
        ];

        $atts = shortcode_atts( $defaults, $atts, 'youtube_playlists' );

        $api_key = get_option( 'youtube_api_key', '' );
        $playlist_id = ! empty( $atts['playlist_id'] ) ? sanitize_text_field( $atts['playlist_id'] ) : '';
        $channel_id = ! empty( $atts['channel_id'] ) ? $atts['channel_id'] : get_option( 'youtube_channel_id', '' );
        $columns = (int) $atts['columns'];

        if ( empty( $api_key ) ) {
            return '<div class="notice notice-warning"><p>YouTube Feed: Please configure your API key in the plugin settings.</p></div>';
        }

        // If a specific playlist ID is provided, fetch that playlist
        if ( ! empty( $playlist_id ) ) {
            $playlist = $this->fetch_youtube_playlist_by_id( $api_key, $playlist_id );

            if ( is_wp_error( $playlist ) ) {
                return '<div class="notice notice-error"><p>Error fetching YouTube playlist: ' . esc_html( $playlist->get_error_message() ) . '</p></div>';
            }

            if ( empty( $playlist ) ) {
                return '<div class="notice notice-warning"><p>Playlist not found.</p></div>';
            }

            // Display single playlist in grid
            $playlists = [ $playlist ];
        } else {
            // If no specific playlist ID, fetch all from channel
            if ( empty( $channel_id ) ) {
                return '<div class="notice notice-warning"><p>YouTube Feed: Please configure your Channel ID in the plugin settings or specify a playlist_id.</p></div>';
            }

            $playlists = $this->fetch_youtube_playlists( $api_key, $channel_id );

            if ( is_wp_error( $playlists ) ) {
                return '<div class="notice notice-error"><p>Error fetching YouTube playlists: ' . esc_html( $playlists->get_error_message() ) . '</p></div>';
            }

            if ( empty( $playlists ) ) {
                return '<div class="notice notice-warning"><p>No playlists found.</p></div>';
            }
        }

        return $this->render_playlist_grid( $playlists, $columns );
    }

    /**
     * Render YouTube feed shortcode
     */
    public function render_youtube_feed_shortcode( $atts ) {
        // Allow shortcode attributes to override settings
        $defaults = [
            'video_count' => get_option( 'youtube_video_count', '12' ),
            'columns'     => get_option( 'youtube_columns', '3' ),
            'channel_id'  => get_option( 'youtube_channel_id', '' ),
        ];

        $atts = shortcode_atts( $defaults, $atts, 'youtube_feed' );

        $api_key = get_option( 'youtube_api_key', '' );
        $channel_id = ! empty( $atts['channel_id'] ) ? $atts['channel_id'] : get_option( 'youtube_channel_id', '' );
        $video_count = (int) $atts['video_count'];
        $columns = (int) $atts['columns'];

        if ( empty( $api_key ) || empty( $channel_id ) ) {
            return '<div class="notice notice-warning"><p>YouTube Feed: Please configure your API key and Channel ID in the plugin settings.</p></div>';
        }

        $videos = $this->fetch_youtube_videos( $api_key, $channel_id, $video_count );

        if ( is_wp_error( $videos ) ) {
            return '<div class="notice notice-error"><p>Error fetching YouTube videos: ' . esc_html( $videos->get_error_message() ) . '</p></div>';
        }

        if ( empty( $videos ) ) {
            return '<div class="notice notice-warning"><p>No videos found.</p></div>';
        }

        return $this->render_video_grid( $videos, $columns );
    }

    /**
     * Register Gutenberg block and block assets
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            'youtube-feed-block-editor',
            YOUTUBE_FEED_URL . 'block/editor.js',
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ],
            YOUTUBE_FEED_VERSION,
            true
        );

        // Pass plugin settings to the block editor script so the editor can use sensible defaults
        wp_localize_script( 'youtube-feed-block-editor', 'yfdBlockConfig', array(
            'defaultChannelId' => get_option( 'youtube_channel_id', '' ),
        ) );

        wp_register_style(
            'youtube-feed-block-editor',
            YOUTUBE_FEED_URL . 'block/editor.css',
            [ 'wp-edit-blocks' ],
            YOUTUBE_FEED_VERSION
        );

        register_block_type( 'youtube-feed/display', [
            'editor_script' => 'youtube-feed-block-editor',
            'editor_style'  => 'youtube-feed-block-editor',
            'render_callback' => [ $this, 'render_block' ],
            'attributes' => [
                'type'       => [ 'type' => 'string', 'default' => 'videos' ],
                'videoCount' => [ 'type' => 'number', 'default' => 12 ],
                'columns'    => [ 'type' => 'number', 'default' => 3 ],
                'channelId'  => [ 'type' => 'string', 'default' => get_option( 'youtube_channel_id', '' ) ],
                'playlistId' => [ 'type' => 'string', 'default' => '' ],
            ],
        ] );
    }

    /**
     * Server-side render callback for the block
     */
    public function render_block( $attributes ) {
        $type = isset( $attributes['type'] ) ? sanitize_text_field( $attributes['type'] ) : 'videos';

        if ( $type === 'playlists' ) {
            // Build youtube_playlists shortcode
            $atts = [];
            if ( ! empty( $attributes['playlistId'] ) ) {
                $atts['playlist_id'] = sanitize_text_field( $attributes['playlistId'] );
            }
            if ( ! empty( $attributes['columns'] ) ) {
                $atts['columns'] = (int) $attributes['columns'];
            }
            if ( ! empty( $attributes['channelId'] ) && empty( $attributes['playlistId'] ) ) {
                $atts['channel_id'] = sanitize_text_field( $attributes['channelId'] );
            }

            $short = '[youtube_playlists';
            foreach ( $atts as $k => $v ) {
                $short .= ' ' . $k . '="' . esc_attr( $v ) . '"';
            }
            $short .= ']';

            return do_shortcode( $short );
        } else {
            // Build youtube_feed shortcode
            $atts = [];
            if ( ! empty( $attributes['videoCount'] ) ) {
                $atts['video_count'] = (int) $attributes['videoCount'];
            }
            if ( ! empty( $attributes['columns'] ) ) {
                $atts['columns'] = (int) $attributes['columns'];
            }
            if ( ! empty( $attributes['channelId'] ) ) {
                $atts['channel_id'] = sanitize_text_field( $attributes['channelId'] );
            }

            $short = '[youtube_feed';
            foreach ( $atts as $k => $v ) {
                $short .= ' ' . $k . '="' . esc_attr( $v ) . '"';
            }
            $short .= ']';

            return do_shortcode( $short );
        }
    }

    /**
     * Fetch videos from YouTube API
     */
    private function fetch_youtube_videos( $api_key, $channel_id, $max_results = 12 ) {
        $cache_key = 'youtube_feed_videos_' . md5( $channel_id );
        $cached = get_transient( $cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'part'             => 'snippet',
                'channelId'        => $channel_id,
                'maxResults'       => $max_results,
                'order'            => 'date',
                'type'             => 'video',
                'key'              => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/search'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['items'] ) ) {
            return new WP_Error( 'no_videos', 'No videos found or invalid API response.' );
        }

        // Format videos
        $videos = [];
        foreach ( $data['items'] as $item ) {
            if ( isset( $item['id']['videoId'] ) && isset( $item['snippet'] ) ) {
                $videos[] = [
                    'id'          => $item['id']['videoId'],
                    'title'       => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'thumbnail'   => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['medium']['url'],
                    'published'   => $item['snippet']['publishedAt'],
                ];
            }
        }

        // Cache for 24 hours
        set_transient( $cache_key, $videos, 24 * HOUR_IN_SECONDS );

        return $videos;
    }

    /**
     * Fetch playlists from YouTube API
     */
    private function fetch_youtube_playlists( $api_key, $channel_id, $max_results = 50 ) {
        $cache_key = 'youtube_feed_playlists_' . md5( $channel_id );
        $cached = get_transient( $cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'part'             => 'snippet',
                'channelId'        => $channel_id,
                'maxResults'       => $max_results,
                'type'             => 'playlist',
                'key'              => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/search'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['items'] ) ) {
            return new WP_Error( 'no_playlists', 'No playlists found or invalid API response.' );
        }

        // Format playlists
        $playlists = [];
        foreach ( $data['items'] as $item ) {
            if ( isset( $item['id']['playlistId'] ) && isset( $item['snippet'] ) ) {
                $playlists[] = [
                    'id'          => $item['id']['playlistId'],
                    'title'       => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'thumbnail'   => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['medium']['url'],
                    'channelId'   => $item['snippet']['channelId'],
                ];
            }
        }

        // Cache for 24 hours
        set_transient( $cache_key, $playlists, 24 * HOUR_IN_SECONDS );

        return $playlists;
    }

    /**
     * Fetch a single playlist from YouTube API by ID
     */
    private function fetch_youtube_playlist_by_id( $api_key, $playlist_id ) {
        $cache_key = 'youtube_feed_playlist_' . md5( $playlist_id );
        $cached = get_transient( $cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'part'             => 'snippet',
                'id'               => $playlist_id,
                'key'              => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/playlists'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['items'] ) || empty( $data['items'] ) ) {
            return new WP_Error( 'playlist_not_found', 'Playlist not found or invalid API response.' );
        }

        // Format single playlist
        $item = $data['items'][0];
        $playlist = [
            'id'          => $item['id'],
            'title'       => $item['snippet']['title'] ?? '',
            'description' => $item['snippet']['description'] ?? '',
            'thumbnail'   => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['medium']['url'] ?? '',
            'channelId'   => $item['snippet']['channelId'] ?? '',
        ];

        // Cache for 24 hours
        set_transient( $cache_key, $playlist, 24 * HOUR_IN_SECONDS );

        return $playlist;
    }

    /**
     * Render video grid
     */
    private function render_video_grid( $videos, $columns ) {
        ob_start();
        ?>
        <div class="youtube-feed-grid youtube-feed-columns-<?php echo esc_attr( $columns ); ?>">
            <?php foreach ( $videos as $video ) : ?>
                <div class="youtube-feed-item">
                    <a href="#" class="youtube-feed-thumbnail yfd-video-link" data-yfd-id="<?php echo esc_attr( $video['id'] ); ?>" role="button" tabindex="0">
                        <img src="<?php echo esc_url( $video['thumbnail'] ); ?>" alt="<?php echo esc_attr( $video['title'] ); ?>" />
                        <div class="youtube-feed-play-icon">
                            <svg viewBox="0 0 24 24" width="48" height="48">
                                <path fill="#fff" d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </a>
                    <div class="youtube-feed-info">
                        <h3 class="youtube-feed-title">
                            <a href="#" class="yfd-title-link" data-yfd-id="<?php echo esc_attr( $video['id'] ); ?>" data-yfd-mobile-only="1" role="button" tabindex="0">
                                <?php echo esc_html( $video['title'] ); ?>
                            </a>
                        </h3>
                        <p class="youtube-feed-date">
                            <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $video['published'] ) ) ); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render playlist grid
     */
    private function render_playlist_grid( $playlists, $columns ) {
        ob_start();
        ?>
        <div class="youtube-playlist-grid youtube-playlist-columns-<?php echo esc_attr( $columns ); ?>">
            <?php foreach ( $playlists as $playlist ) : ?>
                <div class="youtube-playlist-item">
                    <a href="#" class="youtube-playlist-thumbnail yfd-playlist-link" data-yfd-playlist-id="<?php echo esc_attr( $playlist['id'] ); ?>" role="button" tabindex="0">
                        <img src="<?php echo esc_url( $playlist['thumbnail'] ); ?>" alt="<?php echo esc_attr( $playlist['title'] ); ?>" />
                        <div class="youtube-playlist-overlay">
                            <svg viewBox="0 0 24 24" width="48" height="48">
                                <path fill="#fff" d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.645-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z"/>
                            </svg>
                        </div>
                    </a>
                    <div class="youtube-playlist-info">
                        <h3 class="youtube-playlist-title">
                            <a href="#" class="yfd-playlist-title-link" data-yfd-playlist-id="<?php echo esc_attr( $playlist['id'] ); ?>" data-yfd-mobile-only="1" role="button" tabindex="0">
                                <?php echo esc_html( $playlist['title'] ); ?>
                            </a>
                        </h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize plugin
new YouTube_Feed_Display();
