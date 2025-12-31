<?php
/**
 * Plugin Name: WP Security Kit - Core Integration
 * Plugin URI: https://example.com
 * Description: Integrated login security for WordPress with rate limiting, 2FA, custom login URLs, CAPTCHA, and password policies
 * Version: 1.0.0
 * Author: Security Team
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPSEC_VERSION', '1.0.0' );
define( 'WPSEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSEC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load WAF module
require_once dirname( __FILE__ ) . '/wp-security-waf.php';

// ==================== INITIALIZATION ====================
register_activation_hook( __FILE__, 'wpsec_activate_plugin' );
register_deactivation_hook( __FILE__, 'wpsec_deactivate_plugin' );

function wpsec_activate_plugin() {
    // Create necessary database tables and options
    global $wpdb;
    
    // Create login attempts table
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'login_attempts';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_address VARCHAR(45) NOT NULL,
        user_login VARCHAR(60),
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY ip_address (ip_address),
        KEY attempt_time (attempt_time)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    // Create 2FA codes table
    $table_2fa = $wpdb->prefix . 'two_factor_codes';
    $sql_2fa = "CREATE TABLE IF NOT EXISTS $table_2fa (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY expires_at (expires_at)
    ) $charset_collate;";
    
    dbDelta( $sql_2fa );
    
    // Create password history table
    $table_pwd = $wpdb->prefix . 'password_history';
    $sql_pwd = "CREATE TABLE IF NOT EXISTS $table_pwd (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    dbDelta( $sql_pwd );
    
    // Create WAF tables
    wpsec_waf_create_tables();
    
    // Initialize default options
    if ( ! get_option( 'wpsec_options' ) ) {
        add_option( 'wpsec_options', [
            'limit_login_attempts' => 0,
            'login_attempts_max' => 5,
            'login_attempts_lockout' => 30,
            'enable_2fa' => 0,
            'custom_login_url' => '',
            'add_captcha' => 0,
            'enforce_password_policy' => 0,
            'enforce_password_expiry' => 0,
            'password_expiry_days' => 90,
        ] );
    }
}

function wpsec_deactivate_plugin() {
    // Clean up temporary data
    wp_clear_scheduled_hook( 'wpsec_cleanup_expired_2fa_codes' );
    wp_clear_scheduled_hook( 'wpsec_cleanup_old_login_attempts' );
}

// ==================== ADMIN MENU & SETTINGS ====================
add_action( 'admin_menu', 'wpsec_add_admin_menu' );
function wpsec_add_admin_menu() {
    add_options_page(
        'WP Security Kit',
        'WP Security Kit',
        'manage_options',
        'wpsec-settings',
        'wpsec_render_settings_page'
    );
}

add_action( 'admin_init', 'wpsec_register_settings' );
function wpsec_register_settings() {
    register_setting( 'wpsec_settings_group', 'wpsec_options', [
        'sanitize_callback' => 'wpsec_sanitize_options'
    ] );
}

function wpsec_sanitize_options( $input ) {
    $output = [];
    $output['limit_login_attempts'] = ! empty( $input['limit_login_attempts'] ) ? 1 : 0;
    $output['login_attempts_max'] = ! empty( $input['login_attempts_max'] ) ? intval( $input['login_attempts_max'] ) : 5;
    $output['login_attempts_lockout'] = ! empty( $input['login_attempts_lockout'] ) ? intval( $input['login_attempts_lockout'] ) : 30;
    $output['enable_2fa'] = ! empty( $input['enable_2fa'] ) ? 1 : 0;
    $output['custom_login_url'] = ! empty( $input['custom_login_url'] ) ? sanitize_text_field( $input['custom_login_url'] ) : '';
    $output['add_captcha'] = ! empty( $input['add_captcha'] ) ? 1 : 0;
    $output['enforce_password_policy'] = ! empty( $input['enforce_password_policy'] ) ? 1 : 0;
    $output['enforce_password_expiry'] = ! empty( $input['enforce_password_expiry'] ) ? 1 : 0;
    $output['password_expiry_days'] = ! empty( $input['password_expiry_days'] ) ? intval( $input['password_expiry_days'] ) : 90;
    $output['enable_waf'] = ! empty( $input['enable_waf'] ) ? 1 : 0;
    $output['waf_check_uploads'] = ! empty( $input['waf_check_uploads'] ) ? 1 : 0;
    $output['waf_block_after_threats'] = ! empty( $input['waf_block_after_threats'] ) ? intval( $input['waf_block_after_threats'] ) : 10;
    return $output;
}

function wpsec_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $options = get_option( 'wpsec_options', [] );
    ?>
    <div class="wrap">
        <h1>WP Security Kit - Core Security Integration</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'wpsec_settings_group' ); ?>
            
            <h2>Login Security</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="limit_login_attempts">Limit Login Attempts</label></th>
                    <td>
                        <input type="checkbox" id="limit_login_attempts" name="wpsec_options[limit_login_attempts]" value="1" <?php checked( 1, $options['limit_login_attempts'] ?? 0 ); ?>>
                        <label for="limit_login_attempts">Enable login attempt limiting</label>
                        <p class="description">Prevent brute force attacks by limiting failed login attempts.</p>
                        <div style="margin-left: 20px; margin-top: 10px;">
                            <label for="login_attempts_max">Maximum Attempts: <input type="number" id="login_attempts_max" name="wpsec_options[login_attempts_max]" value="<?php echo esc_attr( $options['login_attempts_max'] ?? 5 ); ?>" min="1" max="20"></label>
                            <p class="description">Number of failed attempts before lockout</p>
                        </div>
                        <div style="margin-left: 20px; margin-top: 10px;">
                            <label for="login_attempts_lockout">Lockout Duration (minutes): <input type="number" id="login_attempts_lockout" name="wpsec_options[login_attempts_lockout]" value="<?php echo esc_attr( $options['login_attempts_lockout'] ?? 30 ); ?>" min="5" max="240"></label>
                            <p class="description">How long to lock out the IP after max attempts</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="enable_2fa">Two-Factor Authentication (2FA)</label></th>
                    <td>
                        <input type="checkbox" id="enable_2fa" name="wpsec_options[enable_2fa]" value="1" <?php checked( 1, $options['enable_2fa'] ?? 0 ); ?>>
                        <label for="enable_2fa">Require 2FA for all user logins</label>
                        <p class="description">Users must verify login with a code sent to their email.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="custom_login_url">Custom Login URL</label></th>
                    <td>
                        <label for="custom_login_url">Login path: /<input type="text" id="custom_login_url" name="wpsec_options[custom_login_url]" value="<?php echo esc_attr( $options['custom_login_url'] ?? '' ); ?>" placeholder="e.g., secure-login"></label>
                        <p class="description">Leave blank to use default /wp-login.php. Example: "secure-login" creates /secure-login</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="add_captcha">Add CAPTCHA to Login</label></th>
                    <td>
                        <input type="checkbox" id="add_captcha" name="wpsec_options[add_captcha]" value="1" <?php checked( 1, $options['add_captcha'] ?? 0 ); ?>>
                        <label for="add_captcha">Add simple math CAPTCHA to login form</label>
                        <p class="description">Prevents automated bot attacks on login page.</p>
                    </td>
                </tr>
            </table>

            <h2>Password Security</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="enforce_password_policy">Enforce Strong Password Policy</label></th>
                    <td>
                        <input type="checkbox" id="enforce_password_policy" name="wpsec_options[enforce_password_policy]" value="1" <?php checked( 1, $options['enforce_password_policy'] ?? 0 ); ?>>
                        <label for="enforce_password_policy">Require strong passwords</label>
                        <p class="description">Passwords must be at least 8 characters with letters, numbers, and special characters.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="enforce_password_expiry">Enforce Password Expiration</label></th>
                    <td>
                        <input type="checkbox" id="enforce_password_expiry" name="wpsec_options[enforce_password_expiry]" value="1" <?php checked( 1, $options['enforce_password_expiry'] ?? 0 ); ?>>
                        <label for="enforce_password_expiry">Require password changes periodically</label>
                        <p class="description">Force users to update their password after a set number of days.</p>
                        <div style="margin-left: 20px; margin-top: 10px;">
                            <label for="password_expiry_days">Password Expiry (days): <input type="number" id="password_expiry_days" name="wpsec_options[password_expiry_days]" value="<?php echo esc_attr( $options['password_expiry_days'] ?? 90 ); ?>" min="30" max="365"></label>
                            <p class="description">Days before user password expires and must be changed</p>
                        </div>
                    </td>
                </tr>
            </table>

            <h2>Web Application Firewall (WAF)</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="enable_waf">Enable Web Application Firewall</label></th>
                    <td>
                        <input type="checkbox" id="enable_waf" name="wpsec_options[enable_waf]" value="1" <?php checked( 1, $options['enable_waf'] ?? 0 ); ?>>
                        <label for="enable_waf">Enable WAF protection</label>
                        <p class="description">Protects against SQL injection, XSS, file inclusion, command injection, and other web attacks.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="waf_check_uploads">Validate File Uploads</label></th>
                    <td>
                        <input type="checkbox" id="waf_check_uploads" name="wpsec_options[waf_check_uploads]" value="1" <?php checked( 1, $options['waf_check_uploads'] ?? 0 ); ?>>
                        <label for="waf_check_uploads">Check uploaded files for dangerous file types</label>
                        <p class="description">Blocks executable files and validates file MIME types.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="waf_block_after_threats">Block IP After Threats</label></th>
                    <td>
                        <label for="waf_block_after_threats">Auto-block IP after: <input type="number" id="waf_block_after_threats" name="wpsec_options[waf_block_after_threats]" value="<?php echo esc_attr( $options['waf_block_after_threats'] ?? 10 ); ?>" min="1" max="100"></label> threats in 1 hour
                        <p class="description">IP address will be automatically blacklisted after exceeding this number of threats.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ==================== LOGIN ATTEMPT LIMITING ====================
add_action( 'wp_login_failed', 'wpsec_record_login_failure' );
function wpsec_record_login_failure( $username ) {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['limit_login_attempts'] ) ) {
        return;
    }
    
    global $wpdb;
    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    $wpdb->insert(
        $wpdb->prefix . 'login_attempts',
        [
            'ip_address' => $ip,
            'user_login' => $username,
            'success' => 0,
        ]
    );
}

add_filter( 'wp_authenticate_user', 'wpsec_check_login_attempts', 10, 2 );
function wpsec_check_login_attempts( $user, $password ) {
    if ( is_wp_error( $user ) ) {
        return $user;
    }
    
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['limit_login_attempts'] ) ) {
        return $user;
    }
    
    global $wpdb;
    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    $max_attempts = intval( $options['login_attempts_max'] );
    $lockout_time = intval( $options['login_attempts_lockout'] ) * 60;
    
    // Check for recent failed attempts
    $recent_failures = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM " . $wpdb->prefix . "login_attempts 
         WHERE ip_address = %s AND success = 0 
         AND attempt_time > DATE_SUB(NOW(), INTERVAL %d SECOND)",
        $ip,
        $lockout_time
    ) );
    
    if ( $recent_failures >= $max_attempts ) {
        return new WP_Error( 'too_many_attempts', 'Too many login attempts. Please try again later.' );
    }
    
    // Record successful login
    $wpdb->insert(
        $wpdb->prefix . 'login_attempts',
        [
            'ip_address' => $ip,
            'user_login' => $user->user_login,
            'success' => 1,
        ]
    );
    
    return $user;
}

// ==================== TWO-FACTOR AUTHENTICATION ====================
add_action( 'login_form', 'wpsec_2fa_code_field' );
function wpsec_2fa_code_field() {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['enable_2fa'] ) ) {
        return;
    }
    
    if ( ! isset( $_REQUEST['wpsec_2fa'] ) ) {
        return;
    }
    ?>
    <p>
        <label for="wpsec_2fa_code"><?php esc_html_e( 'Verification Code', 'default' ); ?><br />
        <input type="text" name="wpsec_2fa_code" id="wpsec_2fa_code" class="input" value="" size="20" autocomplete="off" /></label>
    </p>
    <p class="description">Check your email for the verification code.</p>
    <?php
}

add_filter( 'wp_authenticate_user', 'wpsec_handle_2fa_verification', 20, 2 );
function wpsec_handle_2fa_verification( $user, $password ) {
    if ( is_wp_error( $user ) ) {
        return $user;
    }
    
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['enable_2fa'] ) ) {
        return $user;
    }
    
    // If 2FA code provided, verify it
    if ( isset( $_POST['wpsec_2fa_code'] ) ) {
        return wpsec_verify_2fa_code_submission( $user );
    }
    
    // Generate and send 2FA code
    return wpsec_generate_and_send_2fa_code( $user );
}

function wpsec_generate_and_send_2fa_code( $user ) {
    global $wpdb;
    
    // Generate random 6-digit code
    $code = str_pad( random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
    $expires = date( 'Y-m-d H:i:s', time() + ( 15 * 60 ) ); // 15 minutes
    
    $wpdb->insert(
        $wpdb->prefix . 'two_factor_codes',
        [
            'user_id' => $user->ID,
            'code' => $code,
            'expires_at' => $expires,
        ]
    );
    
    // Send email with code
    $to = $user->user_email;
    $subject = 'Your WordPress Login Verification Code';
    $message = "Your verification code is: " . $code . "\n\nThis code expires in 15 minutes.";
    wp_mail( $to, $subject, $message );
    
    // Redirect to 2FA form
    $_REQUEST['wpsec_2fa'] = 1;
    wp_safe_redirect( add_query_arg( 'wpsec_2fa', 1, wp_login_url() ) );
    exit;
}

function wpsec_verify_2fa_code_submission( $user ) {
    global $wpdb;
    
    $code = sanitize_text_field( $_POST['wpsec_2fa_code'] );
    
    // Check if code matches and hasn't expired
    $valid_code = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM " . $wpdb->prefix . "two_factor_codes 
         WHERE user_id = %d AND code = %s AND expires_at > NOW()
         ORDER BY created_at DESC LIMIT 1",
        $user->ID,
        $code
    ) );
    
    if ( ! $valid_code ) {
        return new WP_Error( 'invalid_2fa_code', 'Invalid or expired verification code.' );
    }
    
    // Delete used code
    $wpdb->delete(
        $wpdb->prefix . 'two_factor_codes',
        [ 'user_id' => $user->ID ]
    );
    
    return $user;
}

// ==================== CUSTOM LOGIN URL ====================
add_action( 'init', 'wpsec_custom_login_redirect' );
function wpsec_custom_login_redirect() {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['custom_login_url'] ) ) {
        return;
    }
    
    $custom_url = trim( $options['custom_login_url'], '/' );
    
    // If accessing old wp-login.php, redirect
    if ( strpos( $_SERVER['REQUEST_URI'], '/wp-login.php' ) !== false ) {
        wp_safe_redirect( home_url( '/' . $custom_url ) );
        exit;
    }
    
    // If accessing custom URL, load login form
    if ( strpos( $_SERVER['REQUEST_URI'], '/' . $custom_url ) !== false ) {
        $_SERVER['REQUEST_URI'] = '/wp-login.php';
        require( ABSPATH . 'wp-login.php' );
        exit;
    }
}

add_filter( 'site_url', 'wpsec_replace_wp_login_url', 10, 2 );
add_filter( 'network_site_url', 'wpsec_replace_wp_login_url', 10, 2 );
function wpsec_replace_wp_login_url( $url, $path = '' ) {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['custom_login_url'] ) ) {
        return $url;
    }
    
    $custom_url = trim( $options['custom_login_url'], '/' );
    
    if ( strpos( $url, 'wp-login.php' ) !== false ) {
        $url = str_replace( 'wp-login.php', $custom_url, $url );
    }
    
    return $url;
}

// ==================== LOGIN CAPTCHA ====================
add_action( 'login_form', 'wpsec_add_login_captcha' );
function wpsec_add_login_captcha() {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['add_captcha'] ) ) {
        return;
    }
    
    $num1 = rand( 1, 10 );
    $num2 = rand( 1, 10 );
    $answer = $num1 + $num2;
    
    // Store answer in session
    if ( ! session_id() ) {
        session_start();
    }
    $_SESSION['wpsec_captcha_answer'] = $answer;
    ?>
    <p>
        <label for="wpsec_captcha"><?php echo $num1; ?> + <?php echo $num2; ?> = ?<br />
        <input type="text" name="wpsec_captcha" id="wpsec_captcha" class="input" value="" size="20" /></label>
    </p>
    <?php
}

add_filter( 'wp_authenticate_user', 'wpsec_verify_login_captcha', 5, 2 );
function wpsec_verify_login_captcha( $user, $password ) {
    if ( is_wp_error( $user ) ) {
        return $user;
    }
    
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['add_captcha'] ) ) {
        return $user;
    }
    
    if ( ! session_id() ) {
        session_start();
    }
    
    $user_answer = ! empty( $_POST['wpsec_captcha'] ) ? intval( $_POST['wpsec_captcha'] ) : 0;
    $correct_answer = $_SESSION['wpsec_captcha_answer'] ?? 0;
    
    if ( $user_answer !== $correct_answer ) {
        return new WP_Error( 'captcha_failed', 'CAPTCHA verification failed. Please try again.' );
    }
    
    return $user;
}

// ==================== PASSWORD POLICY ====================
add_filter( 'user_profile_update_errors', 'wpsec_validate_user_password', 10, 3 );
add_filter( 'registration_errors', 'wpsec_validate_registration_password', 10, 3 );
function wpsec_validate_user_password( $errors, $update, $user ) {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['enforce_password_policy'] ) ) {
        return $errors;
    }
    
    if ( isset( $_POST['pass1'] ) && $_POST['pass1'] !== '' ) {
        $pass = $_POST['pass1'];
        
        // Check: 8+ characters, letters, numbers, special characters
        if ( strlen( $pass ) < 8 ) {
            $errors->add( 'pass', 'Password must be at least 8 characters long.' );
        }
        if ( ! preg_match( '/[0-9]/', $pass ) ) {
            $errors->add( 'pass', 'Password must include at least one number.' );
        }
        if ( ! preg_match( '/[A-Za-z]/', $pass ) ) {
            $errors->add( 'pass', 'Password must include at least one letter.' );
        }
        if ( ! preg_match( '/[!@#$%^&*(),.?":{}|<>]/', $pass ) ) {
            $errors->add( 'pass', 'Password must include at least one special character (!@#$%^&*...).' );
        }
    }
    
    return $errors;
}

function wpsec_validate_registration_password( $errors ) {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['enforce_password_policy'] ) ) {
        return $errors;
    }
    
    if ( isset( $_POST['user_pass'] ) && $_POST['user_pass'] !== '' ) {
        $pass = $_POST['user_pass'];
        
        if ( strlen( $pass ) < 8 ) {
            $errors->add( 'pass', 'Password must be at least 8 characters long.' );
        }
        if ( ! preg_match( '/[0-9]/', $pass ) ) {
            $errors->add( 'pass', 'Password must include at least one number.' );
        }
        if ( ! preg_match( '/[A-Za-z]/', $pass ) ) {
            $errors->add( 'pass', 'Password must include at least one letter.' );
        }
        if ( ! preg_match( '/[!@#$%^&*(),.?":{}|<>]/', $pass ) ) {
            $errors->add( 'pass', 'Password must include at least one special character (!@#$%^&*...).' );
        }
    }
    
    return $errors;
}

// ==================== PASSWORD EXPIRATION ====================
add_action( 'admin_init', 'wpsec_check_password_expiry' );
function wpsec_check_password_expiry() {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['enforce_password_expiry'] ) ) {
        return;
    }
    
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $user = wp_get_current_user();
    $expiry_days = intval( $options['password_expiry_days'] );
    
    $last_changed = get_user_meta( $user->ID, 'wpsec_password_changed', true );
    
    if ( ! $last_changed ) {
        update_user_meta( $user->ID, 'wpsec_password_changed', time() );
        return;
    }
    
    $time_since_change = time() - $last_changed;
    $days_since_change = $time_since_change / ( 60 * 60 * 24 );
    
    if ( $days_since_change > $expiry_days ) {
        wp_safe_redirect( admin_url( 'profile.php?password_expired=1' ) );
        exit;
    }
}

// Show password expiry notice
add_action( 'admin_notices', 'wpsec_password_expiry_notice' );
function wpsec_password_expiry_notice() {
    $options = get_option( 'wpsec_options', [] );
    if ( empty( $options['enforce_password_expiry'] ) ) {
        return;
    }
    
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    if ( ! isset( $_GET['password_expired'] ) ) {
        return;
    }
    
    echo '<div class="notice notice-warning is-dismissible"><p>';
    echo 'Your password has expired. Please <a href="' . esc_url( admin_url( 'profile.php' ) ) . '">update your password</a> immediately.';
    echo '</p></div>';
}

// Update password changed time when user changes password
add_action( 'profile_update', 'wpsec_update_password_change_time', 10, 2 );
function wpsec_update_password_change_time( $user_id, $old_userdata ) {
    $user = get_user_by( 'id', $user_id );
    
    if ( $user && wp_check_password( $_POST['pass1'] ?? '', $user->user_pass, $user_id ) === false ) {
        // Password was changed
        update_user_meta( $user_id, 'wpsec_password_changed', time() );
    }
}

// ==================== CLEANUP SCHEDULED TASKS ====================
add_action( 'wp_scheduled_delete', 'wpsec_cleanup_old_data' );
function wpsec_cleanup_old_data() {
    global $wpdb;
    
    // Clean up old login attempts (older than 90 days)
    $wpdb->query(
        "DELETE FROM " . $wpdb->prefix . "login_attempts 
         WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 90 DAY)"
    );
    
    // Clean up expired 2FA codes
    $wpdb->query(
        "DELETE FROM " . $wpdb->prefix . "two_factor_codes 
         WHERE expires_at < NOW()"
    );
}
?>
