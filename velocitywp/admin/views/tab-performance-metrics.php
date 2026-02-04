<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$metrics = new VelocityWP_Performance_Metrics();
$history = $metrics->get_history(30);
$latest_mobile = null;
$latest_desktop = null;

foreach (array_reverse($history) as $item) {
    if (!$latest_mobile && $item['strategy'] === 'mobile') {
        $latest_mobile = $item;
    }
    if (!$latest_desktop && $item['strategy'] === 'desktop') {
        $latest_desktop = $item;
    }
    if ($latest_mobile && $latest_desktop) break;
}

$api_key = !empty($options['pagespeed_api_key']) ? $options['pagespeed_api_key'] : '';
$monitor_urls = !empty($options['performance_monitor_urls']) ? $options['performance_monitor_urls'] : home_url();
$check_frequency = !empty($options['performance_check_frequency']) ? $options['performance_check_frequency'] : 'weekly';
$alert_threshold = !empty($options['performance_alert_threshold']) ? $options['performance_alert_threshold'] : 70;
?>

<div class="velocitywp-tab-section">
    <h2>Performance Metrics Dashboard</h2>
    
    <?php if (empty($api_key)): ?>
    <div class="notice notice-warning">
        <p><strong>PageSpeed API Key Required!</strong> Get a free API key at 
        <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">Google PageSpeed Insights API</a></p>
    </div>
    <?php endif; ?>
</div>

<div class="velocitywp-tab-section">
    <h2>Settings</h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">PageSpeed API Key</th>
            <td>
                <input type="text" name="velocitywp_options[pagespeed_api_key]" value="<?php echo esc_attr($api_key); ?>" 
                    class="regular-text" placeholder="AIzaSy...">
                <p class="description">Get your free API key from 
                    <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">Google Cloud Console</a>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">URLs to Monitor</th>
            <td>
                <textarea name="velocitywp_options[performance_monitor_urls]" rows="5" class="large-text"><?php echo esc_textarea($monitor_urls); ?></textarea>
                <p class="description">One URL per line. These will be checked automatically.</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Check Frequency</th>
            <td>
                <select name="velocitywp_options[performance_check_frequency]">
                    <option value="daily" <?php selected($check_frequency, 'daily'); ?>>Daily</option>
                    <option value="weekly" <?php selected($check_frequency, 'weekly'); ?>>Weekly</option>
                    <option value="monthly" <?php selected($check_frequency, 'monthly'); ?>>Monthly</option>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Alert Threshold</th>
            <td>
                <input type="number" name="velocitywp_options[performance_alert_threshold]" 
                    value="<?php echo esc_attr($alert_threshold); ?>" min="0" max="100" step="1">
                <p class="description">Send email alert if score drops below this value</p>
            </td>
        </tr>
    </table>
</div>

<div class="velocitywp-tab-section">
    <h2>Run Performance Test</h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">Test URL</th>
            <td>
                <input type="url" id="velocitywp-test-url" value="<?php echo esc_url(home_url()); ?>" class="regular-text">
                <select id="velocitywp-test-strategy">
                    <option value="mobile">Mobile</option>
                    <option value="desktop">Desktop</option>
                </select>
                <button type="button" class="button button-primary" id="velocitywp-run-test" <?php disabled(empty($api_key)); ?>>
                    <span class="dashicons dashicons-chart-line"></span> Run Test
                </button>
                <span id="velocitywp-test-loading" style="display:none;">
                    <span class="spinner is-active" style="float:none;"></span> Running test...
                </span>
            </td>
        </tr>
    </table>
</div>

<div id="velocitywp-performance-results" style="display:none;">
    <div class="velocitywp-tab-section">
        <h2>Performance Score</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:20px;margin-bottom:20px;">
            <div class="velocitywp-metric-box" style="text-align:center;padding:20px;background:#fff;border:2px solid #ddd;border-radius:8px;">
                <div id="velocitywp-score-circle" style="font-size:48px;font-weight:bold;margin-bottom:10px;">--</div>
                <div style="color:#646970;">Overall Score</div>
            </div>
        </div>
    </div>
    
    <div class="velocitywp-tab-section">
        <h2>Core Web Vitals</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
            <div class="velocitywp-cwv-box">
                <strong>LCP</strong> (Largest Contentful Paint)
                <div id="velocitywp-lcp-value" style="font-size:24px;margin:10px 0;">--</div>
                <div id="velocitywp-lcp-rating" class="velocitywp-rating"></div>
                <p class="description">Good: ≤ 2.5s</p>
            </div>
            
            <div class="velocitywp-cwv-box">
                <strong>FID</strong> (First Input Delay)
                <div id="velocitywp-fid-value" style="font-size:24px;margin:10px 0;">--</div>
                <div id="velocitywp-fid-rating" class="velocitywp-rating"></div>
                <p class="description">Good: ≤ 100ms</p>
            </div>
            
            <div class="velocitywp-cwv-box">
                <strong>CLS</strong> (Cumulative Layout Shift)
                <div id="velocitywp-cls-value" style="font-size:24px;margin:10px 0;">--</div>
                <div id="velocitywp-cls-rating" class="velocitywp-rating"></div>
                <p class="description">Good: ≤ 0.1</p>
            </div>
            
            <div class="velocitywp-cwv-box">
                <strong>FCP</strong> (First Contentful Paint)
                <div id="velocitywp-fcp-value" style="font-size:24px;margin:10px 0;">--</div>
                <p class="description">Good: ≤ 1.8s</p>
            </div>
            
            <div class="velocitywp-cwv-box">
                <strong>TTFB</strong> (Time to First Byte)
                <div id="velocitywp-ttfb-value" style="font-size:24px;margin:10px 0;">--</div>
                <p class="description">Good: ≤ 600ms</p>
            </div>
            
            <div class="velocitywp-cwv-box">
                <strong>TTI</strong> (Time to Interactive)
                <div id="velocitywp-tti-value" style="font-size:24px;margin:10px 0;">--</div>
                <p class="description">Good: ≤ 3.8s</p>
            </div>
        </div>
    </div>
    
    <div class="velocitywp-tab-section">
        <h2>Opportunities</h2>
        <div id="velocitywp-opportunities"></div>
    </div>
</div>

<div class="velocitywp-tab-section">
    <h2>Performance History (Last 30 Days)</h2>
    <canvas id="velocitywp-performance-chart" width="400" height="100"></canvas>
</div>

<style>
.wpspeed-metric-box { background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:15px; }
.wpspeed-cwv-box { background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:15px; }
.wpspeed-rating { padding:5px 10px;border-radius:3px;display:inline-block;font-weight:bold;margin-top:5px; }
.wpspeed-rating.good { background:#00a32a;color:#fff; }
.wpspeed-rating.needs-improvement { background:#f0a600;color:#fff; }
.wpspeed-rating.poor { background:#d63638;color:#fff; }
.wpspeed-score-good { color:#00a32a; }
.wpspeed-score-average { color:#f0a600; }
.wpspeed-score-poor { color:#d63638; }
</style>

<script>
jQuery(document).ready(function($) {
    // Run performance test
    $('#velocitywp-run-test').on('click', function() {
        var url = $('#velocitywp-test-url').val();
        var strategy = $('#velocitywp-test-strategy').val();
        var $btn = $(this);
        var $loading = $('#velocitywp-test-loading');
        var $results = $('#velocitywp-performance-results');
        
        $btn.prop('disabled', true);
        $loading.show();
        $results.hide();
        
        $.post(ajaxurl, {
            action: 'velocitywp_run_pagespeed',
            nonce: wpsbAdmin.performance_nonce,
            url: url,
            strategy: strategy
        }, function(response) {
            $btn.prop('disabled', false);
            $loading.hide();
            
            if (response.success) {
                displayResults(response.data);
                $results.fadeIn();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $loading.hide();
            alert('Network error occurred');
        });
    });
    
    function displayResults(data) {
        // Score
        var score = data.score;
        var scoreClass = score >= 90 ? 'wpspeed-score-good' : (score >= 50 ? 'wpspeed-score-average' : 'wpspeed-score-poor');
        $('#velocitywp-score-circle').text(score).attr('class', scoreClass);
        
        // Core Web Vitals
        var cwv = data.core_web_vitals;
        
        $('#velocitywp-lcp-value').text(cwv.lcp.displayValue);
        $('#velocitywp-lcp-rating').text(cwv.lcp.rating).attr('class', 'wpspeed-rating ' + cwv.lcp.rating);
        
        $('#velocitywp-fid-value').text(cwv.fid.displayValue);
        $('#velocitywp-fid-rating').text(cwv.fid.rating).attr('class', 'wpspeed-rating ' + cwv.fid.rating);
        
        $('#velocitywp-cls-value').text(cwv.cls.displayValue);
        $('#velocitywp-cls-rating').text(cwv.cls.rating).attr('class', 'wpspeed-rating ' + cwv.cls.rating);
        
        $('#velocitywp-fcp-value').text(cwv.fcp.displayValue);
        $('#velocitywp-ttfb-value').text(cwv.ttfb.displayValue);
        $('#velocitywp-tti-value').text(cwv.tti.displayValue);
        
        // Opportunities
        var oppHtml = '<ul>';
        data.opportunities.forEach(function(opp) {
            oppHtml += '<li><strong>' + opp.title + '</strong> - ' + opp.displayValue + '<br><small>' + opp.description + '</small></li>';
        });
        oppHtml += '</ul>';
        $('#velocitywp-opportunities').html(oppHtml);
    }
    
    // Load history chart
    <?php if (count($history) > 1): ?>
    var historyData = <?php echo json_encode($history); ?>;
    var labels = historyData.map(function(item) {
        return new Date(item.timestamp).toLocaleDateString();
    });
    var scores = historyData.map(function(item) {
        return item.score;
    });
    
    var ctx = document.getElementById('wpspeed-performance-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Performance Score',
                data: scores,
                borderColor: '#2271b1',
                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
