<?php
/**
 * WP Security Kit - functions.php
 * Theme integration with core security plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPSEC_VERSION', '1.0.0' );

add_action( 'after_setup_theme', 'wpsec_setup' );
function wpsec_setup() {
    // Load core security plugin if not already loaded
    if ( ! function_exists( 'wpsec_check_login_attempts' ) ) {
        if ( file_exists( dirname( __FILE__ ) . '/wp-security-core.php' ) ) {
            require_once dirname( __FILE__ ) . '/wp-security-core.php';
        }
    }
}

// Admin menu redirects to the security plugin settings
add_action( 'admin_menu', 'wpsec_admin_menu' );
function wpsec_admin_menu() {
    // The security plugin handles its own menu in Settings
}

?>
