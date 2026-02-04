<?php
/**
 * Performance Metrics Class
 * Integrates with Google PageSpeed Insights API and tracks Core Web Vitals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Speed_Booster_Performance_Metrics {
    
    private $settings;
    private $api_endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    
    public function __construct() {
        $this->settings = get_option('wpsb_options', array());
        
        // AJAX handlers
        add_action('wp_ajax_wpspeed_run_pagespeed', array($this, 'ajax_run_pagespeed'));
        add_action('wp_ajax_wpspeed_get_metrics_history', array($this, 'ajax_get_history'));
        
        // Scheduled checks
        add_action('wpspeed_scheduled_performance_check', array($this, 'scheduled_check'));
    }
    
    /**
     * Run PageSpeed Insights test
     */
    public function run_pagespeed_test($url, $strategy = 'mobile') {
        $api_key = !empty($this->settings['pagespeed_api_key']) ? $this->settings['pagespeed_api_key'] : '';
        
        if (empty($api_key)) {
            return array(
                'error' => true,
                'message' => 'PageSpeed API key is required. Get one at https://developers.google.com/speed/docs/insights/v5/get-started'
            );
        }
        
        $params = array(
            'url' => $url,
            'key' => $api_key,
            'strategy' => $strategy,
            'category' => 'performance'
        );
        
        $request_url = add_query_arg($params, $this->api_endpoint);
        
        $response = wp_remote_get($request_url, array(
            'timeout' => 60,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array(
                'error' => true,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || isset($data['error'])) {
            return array(
                'error' => true,
                'message' => isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error'
            );
        }
        
        return $this->parse_pagespeed_results($data, $url, $strategy);
    }
    
    /**
     * Parse PageSpeed results
     */
    private function parse_pagespeed_results($data, $url, $strategy) {
        $lighthouse = $data['lighthouseResult'];
        $categories = $lighthouse['categories'];
        $audits = $lighthouse['audits'];
        
        $result = array(
            'url' => $url,
            'strategy' => $strategy,
            'timestamp' => current_time('mysql'),
            'score' => round($categories['performance']['score'] * 100),
            'core_web_vitals' => array(
                'lcp' => array(
                    'value' => isset($audits['largest-contentful-paint']) ? $audits['largest-contentful-paint']['numericValue'] : 0,
                    'displayValue' => isset($audits['largest-contentful-paint']) ? $audits['largest-contentful-paint']['displayValue'] : 'N/A',
                    'score' => isset($audits['largest-contentful-paint']) ? $audits['largest-contentful-paint']['score'] : 0,
                    'rating' => $this->get_lcp_rating(isset($audits['largest-contentful-paint']) ? $audits['largest-contentful-paint']['numericValue'] : 0)
                ),
                'fid' => array(
                    'value' => isset($audits['max-potential-fid']) ? $audits['max-potential-fid']['numericValue'] : 0,
                    'displayValue' => isset($audits['max-potential-fid']) ? $audits['max-potential-fid']['displayValue'] : 'N/A',
                    'score' => isset($audits['max-potential-fid']) ? $audits['max-potential-fid']['score'] : 0,
                    'rating' => $this->get_fid_rating(isset($audits['max-potential-fid']) ? $audits['max-potential-fid']['numericValue'] : 0)
                ),
                'cls' => array(
                    'value' => isset($audits['cumulative-layout-shift']) ? $audits['cumulative-layout-shift']['numericValue'] : 0,
                    'displayValue' => isset($audits['cumulative-layout-shift']) ? $audits['cumulative-layout-shift']['displayValue'] : 'N/A',
                    'score' => isset($audits['cumulative-layout-shift']) ? $audits['cumulative-layout-shift']['score'] : 0,
                    'rating' => $this->get_cls_rating(isset($audits['cumulative-layout-shift']) ? $audits['cumulative-layout-shift']['numericValue'] : 0)
                ),
                'fcp' => array(
                    'value' => isset($audits['first-contentful-paint']) ? $audits['first-contentful-paint']['numericValue'] : 0,
                    'displayValue' => isset($audits['first-contentful-paint']) ? $audits['first-contentful-paint']['displayValue'] : 'N/A',
                    'score' => isset($audits['first-contentful-paint']) ? $audits['first-contentful-paint']['score'] : 0
                ),
                'ttfb' => array(
                    'value' => isset($audits['server-response-time']) ? $audits['server-response-time']['numericValue'] : 0,
                    'displayValue' => isset($audits['server-response-time']) ? $audits['server-response-time']['displayValue'] : 'N/A',
                    'score' => isset($audits['server-response-time']) ? $audits['server-response-time']['score'] : 0
                ),
                'tti' => array(
                    'value' => isset($audits['interactive']) ? $audits['interactive']['numericValue'] : 0,
                    'displayValue' => isset($audits['interactive']) ? $audits['interactive']['displayValue'] : 'N/A',
                    'score' => isset($audits['interactive']) ? $audits['interactive']['score'] : 0
                )
            ),
            'opportunities' => array(),
            'diagnostics' => array()
        );
        
        // Extract opportunities
        foreach ($audits as $key => $audit) {
            if (isset($audit['details']['type']) && $audit['details']['type'] === 'opportunity') {
                $result['opportunities'][] = array(
                    'title' => $audit['title'],
                    'description' => $audit['description'],
                    'score' => $audit['score'],
                    'numericValue' => isset($audit['numericValue']) ? $audit['numericValue'] : 0,
                    'displayValue' => isset($audit['displayValue']) ? $audit['displayValue'] : ''
                );
            }
        }
        
        // Save to history
        $this->save_to_history($result);
        
        return $result;
    }
    
    /**
     * Get LCP rating
     */
    private function get_lcp_rating($value_ms) {
        $value_s = $value_ms / 1000;
        if ($value_s <= 2.5) return 'good';
        if ($value_s <= 4.0) return 'needs-improvement';
        return 'poor';
    }
    
    /**
     * Get FID rating
     */
    private function get_fid_rating($value_ms) {
        if ($value_ms <= 100) return 'good';
        if ($value_ms <= 300) return 'needs-improvement';
        return 'poor';
    }
    
    /**
     * Get CLS rating
     */
    private function get_cls_rating($value) {
        if ($value <= 0.1) return 'good';
        if ($value <= 0.25) return 'needs-improvement';
        return 'poor';
    }
    
    /**
     * Save result to history
     */
    private function save_to_history($result) {
        $history = get_option('wpspeed_performance_history', array());
        
        $history[] = array(
            'timestamp' => $result['timestamp'],
            'url' => $result['url'],
            'strategy' => $result['strategy'],
            'score' => $result['score'],
            'lcp' => $result['core_web_vitals']['lcp']['value'],
            'fid' => $result['core_web_vitals']['fid']['value'],
            'cls' => $result['core_web_vitals']['cls']['value'],
            'fcp' => $result['core_web_vitals']['fcp']['value'],
            'ttfb' => $result['core_web_vitals']['ttfb']['value']
        );
        
        // Keep only last 30 days
        $cutoff = strtotime('-30 days');
        $history = array_filter($history, function($item) use ($cutoff) {
            return strtotime($item['timestamp']) > $cutoff;
        });
        
        update_option('wpspeed_performance_history', array_values($history));
    }
    
    /**
     * Get performance history
     */
    public function get_history($days = 30, $url = null, $strategy = null) {
        $history = get_option('wpspeed_performance_history', array());
        
        if ($url) {
            $history = array_filter($history, function($item) use ($url) {
                return $item['url'] === $url;
            });
        }
        
        if ($strategy) {
            $history = array_filter($history, function($item) use ($strategy) {
                return $item['strategy'] === $strategy;
            });
        }
        
        return array_values($history);
    }
    
    /**
     * Scheduled performance check
     */
    public function scheduled_check() {
        $urls = !empty($this->settings['performance_monitor_urls']) ? 
            explode("\n", $this->settings['performance_monitor_urls']) : 
            array(home_url());
        
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) continue;
            
            // Run mobile test
            $this->run_pagespeed_test($url, 'mobile');
            
            // Run desktop test
            $this->run_pagespeed_test($url, 'desktop');
        }
        
        // Check for score degradation
        $this->check_score_alerts();
    }
    
    /**
     * Check for score alerts
     */
    private function check_score_alerts() {
        $threshold = !empty($this->settings['performance_alert_threshold']) ? 
            intval($this->settings['performance_alert_threshold']) : 70;
        
        $history = $this->get_history(7); // Last 7 days
        
        if (count($history) < 2) return;
        
        $latest = end($history);
        
        if ($latest['score'] < $threshold) {
            $this->send_alert_email($latest);
        }
    }
    
    /**
     * Send alert email
     */
    private function send_alert_email($result) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] Performance Alert: Score dropped to %d', $site_name, $result['score']);
        
        $message = sprintf(
            "Performance score for %s has dropped to %d.\n\n" .
            "Core Web Vitals:\n" .
            "- LCP: %s ms\n" .
            "- FID: %s ms\n" .
            "- CLS: %s\n\n" .
            "Please review your site's performance settings.",
            $result['url'],
            $result['score'],
            number_format($result['lcp']),
            number_format($result['fid']),
            number_format($result['cls'], 3)
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * AJAX: Run PageSpeed test
     */
    public function ajax_run_pagespeed() {
        check_ajax_referer('wpspeed_performance', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url();
        $strategy = isset($_POST['strategy']) ? sanitize_text_field($_POST['strategy']) : 'mobile';
        
        $result = $this->run_pagespeed_test($url, $strategy);
        
        if (isset($result['error'])) {
            wp_send_json_error($result);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get metrics history
     */
    public function ajax_get_history() {
        check_ajax_referer('wpspeed_performance', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : null;
        $strategy = isset($_POST['strategy']) ? sanitize_text_field($_POST['strategy']) : null;
        
        $history = $this->get_history($days, $url, $strategy);
        
        wp_send_json_success(array('history' => $history));
    }

    /**
     * Setup scheduled events on activation
     */
    public static function activate() {
        $options = get_option('wpsb_options', array());
        if ( ! wp_next_scheduled('wpspeed_scheduled_performance_check') ) {
            $frequency = ! empty( $options['performance_check_frequency'] ) ? $options['performance_check_frequency'] : 'weekly';
            wp_schedule_event( time(), $frequency, 'wpspeed_scheduled_performance_check' );
        }
    }

    /**
     * Clear scheduled events on deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('wpspeed_scheduled_performance_check');
    }
}

// Initialize singleton
if ( ! function_exists( 'wpsb_performance_metrics' ) ) {
    function wpsb_performance_metrics() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new WP_Speed_Booster_Performance_Metrics();
        }
        return $instance;
    }
    wpsb_performance_metrics();
}
