<?php
/**
 * WP Security Kit - Web Application Firewall (WAF)
 * 
 * Advanced request filtering and threat detection
 * Integrated into wp-security-core.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==================== WAF INITIALIZATION ====================

add_action( 'init', 'wpsec_waf_init', 1 );
function wpsec_waf_init() {
    $options = get_option( 'wpsec_options', [] );
    
    if ( empty( $options['enable_waf'] ) ) {
        return;
    }
    
    // Initialize WAF checks
    wpsec_waf_check_request();
}

// ==================== DATABASE SETUP ====================

function wpsec_waf_create_tables() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // WAF Blocked Requests Log
    $table_waf_log = $wpdb->prefix . 'waf_blocked_requests';
    $sql_waf_log = "CREATE TABLE IF NOT EXISTS $table_waf_log (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_address VARCHAR(45) NOT NULL,
        request_method VARCHAR(10) NOT NULL,
        request_uri TEXT NOT NULL,
        threat_type VARCHAR(50) NOT NULL,
        threat_details TEXT,
        user_agent TEXT,
        blocked_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ip_address (ip_address),
        KEY threat_type (threat_type),
        KEY blocked_time (blocked_time)
    ) ENGINE=InnoDB $charset_collate;";
    
    dbDelta( $sql_waf_log );
    
    // IP Blacklist/Whitelist
    $table_ip_list = $wpdb->prefix . 'ip_list';
    $sql_ip_list = "CREATE TABLE IF NOT EXISTS $table_ip_list (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        list_type ENUM('whitelist', 'blacklist') NOT NULL,
        reason TEXT,
        added_by VARCHAR(60),
        added_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ip_address (ip_address),
        KEY list_type (list_type)
    ) ENGINE=InnoDB $charset_collate;";
    
    dbDelta( $sql_ip_list );
    
    // WAF Rules
    $table_waf_rules = $wpdb->prefix . 'waf_rules';
    $sql_waf_rules = "CREATE TABLE IF NOT EXISTS $table_waf_rules (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        rule_name VARCHAR(100) NOT NULL,
        rule_pattern TEXT NOT NULL,
        rule_type VARCHAR(50) NOT NULL,
        rule_action VARCHAR(20) NOT NULL,
        enabled TINYINT(1) DEFAULT 1,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        created_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY rule_type (rule_type),
        KEY enabled (enabled)
    ) ENGINE=InnoDB $charset_collate;";
    
    dbDelta( $sql_waf_rules );
    
    // Initialize default WAF rules
    wpsec_waf_init_default_rules();
}

// ==================== DEFAULT WAF RULES ====================

function wpsec_waf_init_default_rules() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'waf_rules';
    
    // Check if rules already exist
    $existing = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    if ( $existing > 0 ) {
        return;
    }
    
    $default_rules = [
        // SQL Injection Detection
        [
            'rule_name' => 'SQL Injection - Common Patterns',
            'rule_pattern' => "/(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onload|onclick)\s*(.*?)\s*(from|into|where|values|set)/i",
            'rule_type' => 'sql_injection',
            'rule_action' => 'block',
            'severity' => 'critical',
        ],
        // XSS Detection
        [
            'rule_name' => 'XSS - Script Tags',
            'rule_pattern' => '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            'rule_type' => 'xss',
            'rule_action' => 'block',
            'severity' => 'critical',
        ],
        [
            'rule_name' => 'XSS - Event Handlers',
            'rule_pattern' => '/(onerror|onload|onclick|onmouseover|onkeydown|onchange)\s*=/i',
            'rule_type' => 'xss',
            'rule_action' => 'block',
            'severity' => 'high',
        ],
        // File Inclusion
        [
            'rule_name' => 'Local File Inclusion',
            'rule_pattern' => '/(\.\.\/|\.\.\\\\|file:\/\/|php:\/\/)/i',
            'rule_type' => 'file_inclusion',
            'rule_action' => 'block',
            'severity' => 'critical',
        ],
        // Command Injection
        [
            'rule_name' => 'Command Injection - Shell Commands',
            'rule_pattern' => '/(;|\||`|\$\(|&{2}|<|>)\s*(cat|ls|rm|curl|wget|nc|bash|sh)/i',
            'rule_type' => 'command_injection',
            'rule_action' => 'block',
            'severity' => 'critical',
        ],
        // Path Traversal
        [
            'rule_name' => 'Path Traversal Attack',
            'rule_pattern' => '/(\.\.\/){2,}|\.\.\\\\|%2e%2e|%252e/i',
            'rule_type' => 'path_traversal',
            'rule_action' => 'block',
            'severity' => 'high',
        ],
        // LDAP Injection
        [
            'rule_name' => 'LDAP Injection',
            'rule_pattern' => '/(\*|\(|\)|\\\\)/i',
            'rule_type' => 'ldap_injection',
            'rule_action' => 'log',
            'severity' => 'medium',
        ],
        // Null Byte Injection
        [
            'rule_name' => 'Null Byte Injection',
            'rule_pattern' => '/%00|\x00/',
            'rule_type' => 'null_byte',
            'rule_action' => 'block',
            'severity' => 'high',
        ],
    ];
    
    foreach ( $default_rules as $rule ) {
        $wpdb->insert(
            $table,
            [
                'rule_name' => $rule['rule_name'],
                'rule_pattern' => $rule['rule_pattern'],
                'rule_type' => $rule['rule_type'],
                'rule_action' => $rule['rule_action'],
                'severity' => $rule['severity'],
                'enabled' => 1,
            ]
        );
    }
}

// ==================== WAF REQUEST CHECKING ====================

function wpsec_waf_check_request() {
    $options = get_option( 'wpsec_options', [] );
    
    if ( empty( $options['enable_waf'] ) ) {
        return;
    }
    
    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    
    // Check IP whitelist/blacklist
    if ( ! wpsec_waf_check_ip_list( $ip ) ) {
        wpsec_waf_block_request( 'ip_blacklist', 'IP address is blacklisted' );
        return;
    }
    
    // Check request payload
    $threat = wpsec_waf_scan_request();
    
    if ( $threat ) {
        wpsec_waf_log_blocked_request( $threat );
        
        if ( $threat['action'] === 'block' ) {
            wpsec_waf_block_request( $threat['type'], $threat['details'] );
        }
    }
}

// ==================== IP WHITELIST/BLACKLIST CHECK ====================

function wpsec_waf_check_ip_list( $ip ) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ip_list';
    
    // Check blacklist first
    $blacklisted = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE ip_address = %s AND list_type = 'blacklist'",
        $ip
    ) );
    
    if ( $blacklisted ) {
        return false;
    }
    
    return true;
}

// ==================== REQUEST SCANNING ====================

function wpsec_waf_scan_request() {
    global $wpdb;
    
    // Combine all request data to check
    $data_to_scan = [];
    
    // GET parameters
    if ( ! empty( $_GET ) ) {
        $data_to_scan[] = http_build_query( $_GET );
    }
    
    // POST parameters (but not wp_nonce for performance)
    if ( ! empty( $_POST ) ) {
        $post_data = $_POST;
        unset( $post_data['_wpnonce'], $post_data['_wp_http_referer'] );
        if ( ! empty( $post_data ) ) {
            $data_to_scan[] = http_build_query( $post_data );
        }
    }
    
    // Request URI
    $data_to_scan[] = $_SERVER['REQUEST_URI'];
    
    // User agent
    $data_to_scan[] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Combine all data
    $request_payload = implode( ' ', $data_to_scan );
    
    // Get enabled WAF rules
    $table = $wpdb->prefix . 'waf_rules';
    $rules = $wpdb->get_results(
        "SELECT * FROM $table WHERE enabled = 1",
        ARRAY_A
    );
    
    foreach ( $rules as $rule ) {
        try {
            // Test regex pattern against payload
            if ( @preg_match( $rule['rule_pattern'], $request_payload ) ) {
                return [
                    'type' => $rule['rule_type'],
                    'details' => 'Malicious pattern detected: ' . $rule['rule_name'],
                    'action' => $rule['rule_action'],
                    'severity' => $rule['severity'],
                ];
            }
        } catch ( Exception $e ) {
            // Skip invalid regex patterns
            continue;
        }
    }
    
    // Additional security checks
    $threat = wpsec_waf_check_common_attacks();
    if ( $threat ) {
        return $threat;
    }
    
    return null;
}

// ==================== COMMON ATTACK DETECTION ====================

function wpsec_waf_check_common_attacks() {
    $options = get_option( 'wpsec_options', [] );
    
    // Check for malicious file uploads
    if ( ! empty( $_FILES ) ) {
        $threat = wpsec_waf_check_file_upload();
        if ( $threat ) {
            return $threat;
        }
    }
    
    // Check for directory traversal in file names
    foreach ( $_REQUEST as $key => $value ) {
        if ( is_string( $value ) ) {
            if ( preg_match( '/\.\.\/|\.\.\\\\/', $value ) ) {
                return [
                    'type' => 'path_traversal',
                    'details' => 'Directory traversal attempt detected',
                    'action' => 'block',
                    'severity' => 'high',
                ];
            }
        }
    }
    
    // Check for excessively long requests (possible buffer overflow)
    $request_length = strlen( http_build_query( $_REQUEST ) );
    if ( $request_length > 100000 ) { // 100KB threshold
        return [
            'type' => 'buffer_overflow',
            'details' => 'Request payload exceeds maximum allowed size',
            'action' => 'block',
            'severity' => 'high',
        ];
    }
    
    return null;
}

// ==================== FILE UPLOAD VALIDATION ====================

function wpsec_waf_check_file_upload() {
    $options = get_option( 'wpsec_options', [] );
    
    if ( empty( $options['waf_check_uploads'] ) ) {
        return null;
    }
    
    $dangerous_extensions = [ 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'php', 'phtml' ];
    $max_file_size = 100 * 1024 * 1024; // 100MB default
    
    foreach ( $_FILES as $field_name => $file_data ) {
        if ( is_array( $file_data['name'] ) ) {
            // Multiple files
            foreach ( $file_data['name'] as $index => $filename ) {
                $threat = wpsec_waf_validate_single_file(
                    $filename,
                    $file_data['tmp_name'][$index],
                    $file_data['size'][$index] ?? 0,
                    $dangerous_extensions,
                    $max_file_size
                );
                if ( $threat ) {
                    return $threat;
                }
            }
        } else {
            // Single file
            $threat = wpsec_waf_validate_single_file(
                $file_data['name'],
                $file_data['tmp_name'],
                $file_data['size'],
                $dangerous_extensions,
                $max_file_size
            );
            if ( $threat ) {
                return $threat;
            }
        }
    }
    
    return null;
}

function wpsec_waf_validate_single_file( $filename, $tmp_path, $file_size, $dangerous_extensions, $max_size ) {
    // Check file extension
    $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    
    if ( in_array( $extension, $dangerous_extensions, true ) ) {
        return [
            'type' => 'dangerous_file_upload',
            'details' => 'File type not allowed: ' . $extension,
            'action' => 'block',
            'severity' => 'critical',
        ];
    }
    
    // Check file size
    if ( $file_size > $max_size ) {
        return [
            'type' => 'file_too_large',
            'details' => 'File size exceeds maximum allowed',
            'action' => 'block',
            'severity' => 'high',
        ];
    }
    
    // Check for embedded scripts
    if ( function_exists( 'finfo_file' ) && file_exists( $tmp_path ) ) {
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime_type = finfo_file( $finfo, $tmp_path );
        finfo_close( $finfo );
        
        // Whitelist common safe MIME types
        $safe_mimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        
        if ( ! in_array( $mime_type, $safe_mimes, true ) && strpos( $mime_type, 'image/' ) !== 0 ) {
            return [
                'type' => 'suspicious_file_type',
                'details' => 'File MIME type not allowed: ' . $mime_type,
                'action' => 'block',
                'severity' => 'high',
            ];
        }
    }
    
    return null;
}

// ==================== REQUEST BLOCKING & LOGGING ====================

function wpsec_waf_log_blocked_request( $threat ) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'waf_blocked_requests';
    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    
    $wpdb->insert(
        $table,
        [
            'ip_address' => $ip,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'threat_type' => $threat['type'],
            'threat_details' => $threat['details'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]
    );
    
    // Update IP reputation (block after X threats)
    $options = get_option( 'wpsec_options', [] );
    $block_after = intval( $options['waf_block_after_threats'] ?? 10 );
    
    $threat_count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE ip_address = %s AND blocked_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        $ip
    ) );
    
    if ( $threat_count >= $block_after ) {
        wpsec_waf_add_to_blacklist( $ip, 'Auto-blocked after ' . $threat_count . ' threats' );
    }
}

function wpsec_waf_block_request( $threat_type, $message ) {
    // Don't block admin pages if user is admin
    if ( is_admin() && current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Log the block
    wp_safe_remote_post(
        admin_url( 'admin-ajax.php' ),
        [
            'blocking' => false,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        ]
    );
    
    // Display block message
    wp_die(
        'Access Denied: ' . esc_html( $message ) . '<br>Your IP address has been flagged as suspicious.',
        'Security Block',
        [ 'response' => 403 ]
    );
}

function wpsec_waf_add_to_blacklist( $ip, $reason ) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ip_list';
    
    $wpdb->insert(
        $table,
        [
            'ip_address' => $ip,
            'list_type' => 'blacklist',
            'reason' => $reason,
            'added_by' => 'waf_auto',
        ]
    );
}

// ==================== ADMIN SETTINGS ====================

add_action( 'admin_init', 'wpsec_waf_register_settings' );
function wpsec_waf_register_settings() {
    // Settings are registered in main plugin
}

// Add WAF settings to main settings page
add_filter( 'wpsec_sanitize_options', 'wpsec_waf_sanitize_options' );
function wpsec_waf_sanitize_options( $input ) {
    $input['enable_waf'] = ! empty( $input['enable_waf'] ) ? 1 : 0;
    $input['waf_check_uploads'] = ! empty( $input['waf_check_uploads'] ) ? 1 : 0;
    $input['waf_block_after_threats'] = ! empty( $input['waf_block_after_threats'] ) ? intval( $input['waf_block_after_threats'] ) : 10;
    return $input;
}

// ==================== ADMIN PANEL - WAF MANAGEMENT ====================

add_action( 'admin_menu', 'wpsec_waf_add_menu' );
function wpsec_waf_add_menu() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    add_submenu_page(
        'options-general.php',
        'WAF Dashboard',
        'WAF Dashboard',
        'manage_options',
        'wpsec-waf-dashboard',
        'wpsec_waf_render_dashboard'
    );
    
    add_submenu_page(
        'options-general.php',
        'IP Management',
        'IP Management',
        'manage_options',
        'wpsec-ip-management',
        'wpsec_waf_render_ip_management'
    );
    
    add_submenu_page(
        'options-general.php',
        'WAF Rules',
        'WAF Rules',
        'manage_options',
        'wpsec-waf-rules',
        'wpsec_waf_render_rules_page'
    );
}

function wpsec_waf_render_dashboard() {
    global $wpdb;
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $table = $wpdb->prefix . 'waf_blocked_requests';
    
    // Get statistics
    $total_blocked = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    $blocked_24h = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE blocked_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
    $unique_ips = $wpdb->get_var( "SELECT COUNT(DISTINCT ip_address) FROM $table WHERE blocked_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
    
    // Get top threats
    $top_threats = $wpdb->get_results(
        "SELECT threat_type, COUNT(*) as count FROM $table WHERE blocked_time > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY threat_type ORDER BY count DESC LIMIT 10"
    );
    
    // Get recent blocks
    $recent = $wpdb->get_results(
        "SELECT * FROM $table ORDER BY blocked_time DESC LIMIT 20"
    );
    ?>
    <div class="wrap">
        <h1>WAF Dashboard</h1>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
            <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                <h3>Total Blocked Requests</h3>
                <p style="font-size: 28px; font-weight: bold;"><?php echo intval( $total_blocked ); ?></p>
            </div>
            <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                <h3>Last 24 Hours</h3>
                <p style="font-size: 28px; font-weight: bold; color: #d32f2f;"><?php echo intval( $blocked_24h ); ?></p>
            </div>
            <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                <h3>Unique IPs (24h)</h3>
                <p style="font-size: 28px; font-weight: bold; color: #f57c00;"><?php echo intval( $unique_ips ); ?></p>
            </div>
        </div>
        
        <h2>Top Threats (Last 7 Days)</h2>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Threat Type</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $top_threats as $threat ) : ?>
                <tr>
                    <td><?php echo esc_html( $threat->threat_type ); ?></td>
                    <td><?php echo intval( $threat->count ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Recent Blocked Requests</h2>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Threat Type</th>
                    <th>Request URI</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $recent as $block ) : ?>
                <tr>
                    <td><?php echo esc_html( $block->ip_address ); ?></td>
                    <td><?php echo esc_html( $block->threat_type ); ?></td>
                    <td><?php echo esc_html( substr( $block->request_uri, 0, 50 ) ); ?></td>
                    <td><?php echo esc_html( $block->blocked_time ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function wpsec_waf_render_ip_management() {
    global $wpdb;
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    // Handle form submission
    if ( ! empty( $_POST['wpsec_waf_action'] ) && check_admin_referer( 'wpsec_ip_management' ) ) {
        $action = sanitize_text_field( $_POST['wpsec_waf_action'] );
        $ip = sanitize_text_field( $_POST['ip_address'] ?? '' );
        $list_type = sanitize_text_field( $_POST['list_type'] ?? 'blacklist' );
        $reason = sanitize_text_field( $_POST['reason'] ?? '' );
        
        $table = $wpdb->prefix . 'ip_list';
        
        if ( $action === 'add' && ! empty( $ip ) ) {
            $wpdb->insert(
                $table,
                [
                    'ip_address' => $ip,
                    'list_type' => $list_type,
                    'reason' => $reason,
                    'added_by' => wp_get_current_user()->user_login,
                ]
            );
            echo '<div class="notice notice-success"><p>IP address added.</p></div>';
        } elseif ( $action === 'delete' && ! empty( $_POST['delete_id'] ) ) {
            $wpdb->delete( $table, [ 'id' => intval( $_POST['delete_id'] ) ] );
            echo '<div class="notice notice-success"><p>IP address removed.</p></div>';
        }
    }
    
    // Get current lists
    $table = $wpdb->prefix . 'ip_list';
    $blacklist = $wpdb->get_results( "SELECT * FROM $table WHERE list_type = 'blacklist' ORDER BY added_time DESC" );
    $whitelist = $wpdb->get_results( "SELECT * FROM $table WHERE list_type = 'whitelist' ORDER BY added_time DESC" );
    ?>
    <div class="wrap">
        <h1>IP Management</h1>
        
        <h2>Add IP Address</h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'wpsec_ip_management' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ip_address">IP Address</label></th>
                    <td><input type="text" id="ip_address" name="ip_address" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="list_type">List Type</label></th>
                    <td>
                        <select id="list_type" name="list_type">
                            <option value="blacklist">Blacklist</option>
                            <option value="whitelist">Whitelist</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="reason">Reason</label></th>
                    <td><textarea id="reason" name="reason" rows="3"></textarea></td>
                </tr>
            </table>
            <input type="hidden" name="wpsec_waf_action" value="add">
            <?php submit_button( 'Add IP Address' ); ?>
        </form>
        
        <h2>Blacklist</h2>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Reason</th>
                    <th>Added By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $blacklist as $item ) : ?>
                <tr>
                    <td><?php echo esc_html( $item->ip_address ); ?></td>
                    <td><?php echo esc_html( $item->reason ); ?></td>
                    <td><?php echo esc_html( $item->added_by ); ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field( 'wpsec_ip_management' ); ?>
                            <input type="hidden" name="wpsec_waf_action" value="delete">
                            <input type="hidden" name="delete_id" value="<?php echo intval( $item->id ); ?>">
                            <button type="submit" class="button button-small">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Whitelist</h2>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Reason</th>
                    <th>Added By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $whitelist as $item ) : ?>
                <tr>
                    <td><?php echo esc_html( $item->ip_address ); ?></td>
                    <td><?php echo esc_html( $item->reason ); ?></td>
                    <td><?php echo esc_html( $item->added_by ); ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field( 'wpsec_ip_management' ); ?>
                            <input type="hidden" name="wpsec_waf_action" value="delete">
                            <input type="hidden" name="delete_id" value="<?php echo intval( $item->id ); ?>">
                            <button type="submit" class="button button-small">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function wpsec_waf_render_rules_page() {
    global $wpdb;
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    // Handle enable/disable
    if ( ! empty( $_POST['wpsec_rule_action'] ) && check_admin_referer( 'wpsec_waf_rules' ) ) {
        $rule_id = intval( $_POST['rule_id'] );
        $enabled = intval( $_POST['enabled'] ?? 0 );
        
        $table = $wpdb->prefix . 'waf_rules';
        $wpdb->update( $table, [ 'enabled' => $enabled ], [ 'id' => $rule_id ] );
    }
    
    $table = $wpdb->prefix . 'waf_rules';
    $rules = $wpdb->get_results( "SELECT * FROM $table ORDER BY severity DESC, rule_name ASC" );
    ?>
    <div class="wrap">
        <h1>WAF Rules Management</h1>
        
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Rule Name</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Toggle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rules as $rule ) : ?>
                <tr>
                    <td><?php echo esc_html( $rule->rule_name ); ?></td>
                    <td><?php echo esc_html( $rule->rule_type ); ?></td>
                    <td>
                        <span style="color: <?php echo $rule->severity === 'critical' ? 'red' : 'orange'; ?>;">
                            <?php echo strtoupper( esc_html( $rule->severity ) ); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html( $rule->rule_action ); ?></td>
                    <td><?php echo $rule->enabled ? '✓ Enabled' : '✗ Disabled'; ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field( 'wpsec_waf_rules' ); ?>
                            <input type="hidden" name="wpsec_rule_action" value="toggle">
                            <input type="hidden" name="rule_id" value="<?php echo intval( $rule->id ); ?>">
                            <input type="hidden" name="enabled" value="<?php echo $rule->enabled ? '0' : '1'; ?>">
                            <button type="submit" class="button button-small">
                                <?php echo $rule->enabled ? 'Disable' : 'Enable'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ==================== CLEANUP ====================

add_action( 'wp_scheduled_delete', 'wpsec_waf_cleanup' );
function wpsec_waf_cleanup() {
    global $wpdb;
    
    // Clean old logs (older than 30 days)
    $table = $wpdb->prefix . 'waf_blocked_requests';
    $wpdb->query(
        "DELETE FROM $table WHERE blocked_time < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
}

?>
