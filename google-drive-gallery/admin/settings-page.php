<?php
/**
 * Admin Settings Page Template
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php
    // Handle disconnect action
    if ( isset( $_GET['action'] ) && 'disconnect_oauth' === $_GET['action'] && isset( $_GET['_wpnonce'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gdrive_disconnect_oauth' ) ) {
            GDrive_Auth::clear_auth();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Disconnected from Google Drive', 'google-drive-gallery' ) . '</p></div>';
        }
    }

    settings_errors();
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=gdrive-gallery-settings&tab=auth" class="nav-tab <?php echo 'auth' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Authentication', 'google-drive-gallery' ); ?>
        </a>
        <a href="?page=gdrive-gallery-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'General Settings', 'google-drive-gallery' ); ?>
        </a>
        <a href="?page=gdrive-gallery-settings&tab=tools" class="nav-tab <?php echo 'tools' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Tools', 'google-drive-gallery' ); ?>
        </a>
        <a href="?page=gdrive-gallery-settings&tab=usage" class="nav-tab <?php echo 'usage' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Usage', 'google-drive-gallery' ); ?>
        </a>
    </h2>

    <?php if ( 'auth' === $active_tab ) : ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'gdrive_gallery_auth' );
            do_settings_sections( 'gdrive_gallery_auth' );
            submit_button();
            ?>
        </form>

    <?php elseif ( 'general' === $active_tab ) : ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'gdrive_gallery_general' );
            do_settings_sections( 'gdrive_gallery_general' );
            submit_button();
            ?>
        </form>

    <?php elseif ( 'tools' === $active_tab ) : ?>
        <div class="gdrive-tools">
            <h2><?php esc_html_e( 'Connection Test', 'google-drive-gallery' ); ?></h2>
            <p><?php esc_html_e( 'Test your connection to Google Drive API.', 'google-drive-gallery' ); ?></p>
            <button type="button" class="button" id="gdrive-test-connection">
                <?php esc_html_e( 'Test Connection', 'google-drive-gallery' ); ?>
            </button>
            <div id="gdrive-connection-result"></div>

            <hr>

            <h2><?php esc_html_e( 'Cache Management', 'google-drive-gallery' ); ?></h2>
            <p><?php esc_html_e( 'Clear all cached Google Drive data.', 'google-drive-gallery' ); ?></p>
            <?php
            $stats = GDrive_Cache::get_cache_stats();
            printf(
                /* translators: 1: cache count, 2: cache size */
                '<p>' . esc_html__( 'Current cache: %1$d entries (%2$s)', 'google-drive-gallery' ) . '</p>',
                esc_html( $stats['count'] ),
                esc_html( $stats['size_formatted'] )
            );
            ?>
            <button type="button" class="button" id="gdrive-clear-cache">
                <?php esc_html_e( 'Clear Cache', 'google-drive-gallery' ); ?>
            </button>
            <div id="gdrive-cache-result"></div>
        </div>

    <?php elseif ( 'usage' === $active_tab ) : ?>
        <div class="gdrive-usage">
            <h2><?php esc_html_e( 'How to Use', 'google-drive-gallery' ); ?></h2>
            
            <h3><?php esc_html_e( 'Shortcode Usage', 'google-drive-gallery' ); ?></h3>
            <p><?php esc_html_e( 'Use the following shortcode to display a gallery:', 'google-drive-gallery' ); ?></p>
            <pre>[gdrive_gallery folder_id="YOUR_FOLDER_ID" columns="3" lightbox="true"]</pre>

            <h4><?php esc_html_e( 'Shortcode Attributes', 'google-drive-gallery' ); ?></h4>
            <ul>
                <li><code>folder_id</code> (required): <?php esc_html_e( 'Google Drive folder ID', 'google-drive-gallery' ); ?></li>
                <li><code>columns</code>: <?php esc_html_e( 'Number of columns (1-6, default: 3)', 'google-drive-gallery' ); ?></li>
                <li><code>spacing</code>: <?php esc_html_e( 'Gap between images in pixels (default: 10)', 'google-drive-gallery' ); ?></li>
                <li><code>lightbox</code>: <?php esc_html_e( 'Enable lightbox (true/false, default: true)', 'google-drive-gallery' ); ?></li>
                <li><code>slideshow</code>: <?php esc_html_e( 'Enable slideshow (true/false, default: false)', 'google-drive-gallery' ); ?></li>
                <li><code>show_captions</code>: <?php esc_html_e( 'Display image captions (true/false, default: false)', 'google-drive-gallery' ); ?></li>
                <li><code>include_subfolders</code>: <?php esc_html_e( 'Include subfolder images (true/false, default: false)', 'google-drive-gallery' ); ?></li>
                <li><code>thumbnail_size</code>: <?php esc_html_e( 'Thumbnail size (small/medium/large, default: medium)', 'google-drive-gallery' ); ?></li>
                <li><code>title</code>: <?php esc_html_e( 'Gallery title', 'google-drive-gallery' ); ?></li>
                <li><code>custom_class</code>: <?php esc_html_e( 'Custom CSS class', 'google-drive-gallery' ); ?></li>
            </ul>

            <h3><?php esc_html_e( 'Finding Your Folder ID', 'google-drive-gallery' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Open Google Drive and navigate to the folder you want to display', 'google-drive-gallery' ); ?></li>
                <li><?php esc_html_e( 'The folder ID is in the URL after "/folders/"', 'google-drive-gallery' ); ?></li>
                <li><?php esc_html_e( 'Example: https://drive.google.com/drive/folders/ABC123xyz - the ID is ABC123xyz', 'google-drive-gallery' ); ?></li>
            </ol>

            <h3><?php esc_html_e( 'Gutenberg Block', 'google-drive-gallery' ); ?></h3>
            <p><?php esc_html_e( 'Search for "Google Drive Gallery" in the block inserter to add a gallery using the block editor.', 'google-drive-gallery' ); ?></p>

            <h3><?php esc_html_e( 'Example Gallery', 'google-drive-gallery' ); ?></h3>
            <pre>[gdrive_gallery folder_id="1a2b3c4d5e" columns="4" spacing="15" lightbox="true" slideshow="true" title="My Photo Gallery"]</pre>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Test connection
    $('#gdrive-test-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#gdrive-connection-result');
        
        $button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'google-drive-gallery' ); ?>');
        $result.html('');

        $.ajax({
            url: gdriveAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gdrive_test_connection',
                nonce: gdriveAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Connection test failed', 'google-drive-gallery' ); ?></p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'google-drive-gallery' ); ?>');
            }
        });
    });

    // Clear cache
    $('#gdrive-clear-cache').on('click', function() {
        var $button = $(this);
        var $result = $('#gdrive-cache-result');
        
        $button.prop('disabled', true).text('<?php esc_html_e( 'Clearing...', 'google-drive-gallery' ); ?>');
        $result.html('');

        $.ajax({
            url: gdriveAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gdrive_clear_cache',
                nonce: gdriveAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    location.reload();
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Cache clear failed', 'google-drive-gallery' ); ?></p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e( 'Clear Cache', 'google-drive-gallery' ); ?>');
            }
        });
    });
});
</script>
