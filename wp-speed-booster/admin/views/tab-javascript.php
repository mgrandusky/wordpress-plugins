<?php
if (!defined('ABSPATH')) exit;

$js_delay = new WP_Speed_Booster_JS_Delay();
$stats = $js_delay->get_stats();

$js_delay_enabled = !empty($options['js_delay_enabled']);
$js_defer_enabled = !empty($options['js_defer_enabled']);
$js_delay_timeout = !empty($options['js_delay_timeout']) ? $options['js_delay_timeout'] : 5;
$js_delay_exclude = !empty($options['js_delay_exclude']) ? $options['js_delay_exclude'] : '';
$js_delay_events = !empty($options['js_delay_events']) ? $options['js_delay_events'] : 'mousemove,scroll,touchstart,click,keydown';
?>

<div class="wpspeed-tab-section">
    <h2>JavaScript Optimization</h2>
    
    <div class="notice notice-info">
        <p><strong>How it works:</strong> This feature delays non-critical JavaScript execution until user interaction (scroll, click, touch) or after a timeout. This significantly improves Time to Interactive (TTI) and reduces JavaScript execution time.</p>
    </div>
</div>

<div class="wpspeed-tab-section">
    <h2>Optimization Mode</h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">Defer JavaScript</th>
            <td>
                <label>
                    <input type="checkbox" name="wpsb_options[js_defer_enabled]" value="1" 
                        <?php checked($js_defer_enabled, 1); ?>
                        onchange="document.getElementById('defer-info').style.display = this.checked ? 'block' : 'none'">
                    Add defer attribute to all scripts
                </label>
                <div id="defer-info" style="<?php echo $js_defer_enabled ? '' : 'display:none;'; ?>margin-top:10px;padding:10px;background:#f0f6fc;border-left:4px solid #2271b1;">
                    <strong>Defer:</strong> Scripts are downloaded in parallel but executed after HTML parsing completes. Maintains execution order.
                    <br><strong>Impact:</strong> Moderate improvement in page load speed.
                </div>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Delay JavaScript (Recommended)</th>
            <td>
                <label>
                    <input type="checkbox" name="wpsb_options[js_delay_enabled]" value="1" 
                        <?php checked($js_delay_enabled, 1); ?>
                        onchange="document.getElementById('delay-options').style.display = this.checked ? 'table-row-group' : 'none'">
                    Delay JavaScript execution until user interaction
                </label>
                <div style="margin-top:10px;padding:10px;background:#f0f6fc;border-left:4px solid #2271b1;">
                    <strong>Delay:</strong> Scripts don't load until user interacts with the page (scroll, click, touch) or after a timeout.
                    <br><strong>Impact:</strong> Maximum improvement in TTI and JavaScript execution time.
                    <br><strong>⚠️ Note:</strong> May affect scripts that need to run immediately on page load.
                </div>
            </td>
        </tr>
    </table>
</div>

<tbody id="delay-options" style="<?php echo $js_delay_enabled ? '' : 'display:none;'; ?>">
    <tr>
        <td colspan="2">
            <div class="wpspeed-tab-section" style="margin:0;padding-top:0;">
                <h3>Delay Settings</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Delay Timeout</th>
                        <td>
                            <input type="number" name="wpsb_options[js_delay_timeout]" 
                                value="<?php echo esc_attr($js_delay_timeout); ?>" 
                                min="0" max="30" step="1" style="width:80px;"> seconds
                            <p class="description">Maximum time to wait before loading scripts (even without user interaction). Set to 0 to wait indefinitely.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Trigger Events</th>
                        <td>
                            <input type="text" name="wpsb_options[js_delay_events]" 
                                value="<?php echo esc_attr($js_delay_events); ?>" 
                                class="regular-text">
                            <p class="description">Comma-separated list of events that trigger script loading. Default: mousemove,scroll,touchstart,click,keydown</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Excluded Scripts</th>
                        <td>
                            <textarea name="wpsb_options[js_delay_exclude]" rows="10" class="large-text code"><?php echo esc_textarea($js_delay_exclude); ?></textarea>
                            <p class="description">
                                One script handle or URL pattern per line. Use * as wildcard.<br>
                                <strong>Examples:</strong><br>
                                - <code>google-analytics</code> (exclude by handle)<br>
                                - <code>gtag*</code> (exclude all handles starting with "gtag")<br>
                                - <code>*/analytics.js</code> (exclude by URL pattern)<br>
                                <br>
                                <strong>Automatically excluded:</strong> jQuery core, admin scripts, our plugin scripts
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</tbody>

<div class="wpspeed-tab-section">
    <h2>Common Scripts to Exclude</h2>
    
    <div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:4px;">
        <p><strong>Analytics & Tracking:</strong> Usually safe to delay, but exclude if you need immediate tracking.</p>
        <ul style="margin-left:20px;">
            <li><code>google-analytics</code></li>
            <li><code>gtag*</code></li>
            <li><code>facebook-pixel</code></li>
            <li><code>*analytics*</code></li>
        </ul>
        
        <p><strong>Ads:</strong> Consider excluding if ads need immediate display.</p>
        <ul style="margin-left:20px;">
            <li><code>*adsense*</code></li>
            <li><code>*doubleclick*</code></li>
        </ul>
        
        <p><strong>Social Widgets:</strong> Usually safe to delay.</p>
        <ul style="margin-left:20px;">
            <li><code>*twitter*</code></li>
            <li><code>*facebook*</code></li>
        </ul>
        
        <p><strong>Chat Widgets:</strong> Exclude if you want immediate availability.</p>
        <ul style="margin-left:20px;">
            <li><code>*livechat*</code></li>
            <li><code>*intercom*</code></li>
            <li><code>*drift*</code></li>
        </ul>
        
        <p><strong>Forms & Validation:</strong> Exclude if needed immediately.</p>
        <ul style="margin-left:20px;">
            <li><code>contact-form*</code></li>
            <li><code>*recaptcha*</code></li>
        </ul>
    </div>
</div>

<div class="wpspeed-tab-section">
    <h2>Current Statistics</h2>
    
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
        <div class="wpspeed-stat-box" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
            <div style="font-size:36px;font-weight:bold;color:#2271b1;"><?php echo $stats['total']; ?></div>
            <div>Total Scripts</div>
        </div>
        
        <div class="wpspeed-stat-box" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
            <div style="font-size:36px;font-weight:bold;color:#00a32a;"><?php echo $stats['delayed']; ?></div>
            <div>Will Be Delayed</div>
        </div>
        
        <div class="wpspeed-stat-box" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:4px;text-align:center;">
            <div style="font-size:36px;font-weight:bold;color:#646970;"><?php echo $stats['excluded']; ?></div>
            <div>Excluded</div>
        </div>
    </div>
</div>

<div class="wpspeed-tab-section">
    <h2>Testing & Debugging</h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">Browser Console</th>
            <td>
                <p>Open your browser's Developer Console (F12) and look for these messages:</p>
                <ul style="margin-left:20px;">
                    <li><code>[WP Speed Booster] Loading delayed scripts...</code> - Scripts triggered</li>
                    <li><code>[WP Speed Booster] Loading scripts after timeout</code> - Timeout reached</li>
                </ul>
                <p>This helps you verify when scripts are loading.</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Test Your Site</th>
            <td>
                <p>After enabling, test your site thoroughly:</p>
                <ol style="margin-left:20px;">
                    <li>Load your homepage and scroll down</li>
                    <li>Check all interactive elements (forms, buttons, sliders)</li>
                    <li>Test contact forms and checkout process</li>
                    <li>Verify analytics tracking in real-time reports</li>
                </ol>
                <p>If something breaks, add that script to the exclusion list.</p>
            </td>
        </tr>
    </table>
</div>

<div class="wpspeed-tab-section">
    <h2>Expected Results</h2>
    
    <div style="background:#f0f6fc;padding:15px;border-left:4px solid #2271b1;border-radius:4px;">
        <h4 style="margin-top:0;">With JavaScript Delay enabled, you should see:</h4>
        <ul style="margin-left:20px;">
            <li>✅ <strong>Faster Time to Interactive (TTI)</strong> - 30-50% improvement</li>
            <li>✅ <strong>Reduced JavaScript execution time</strong> - Shown in PageSpeed Insights</li>
            <li>✅ <strong>Lower Total Blocking Time (TBT)</strong></li>
            <li>✅ <strong>Improved First Input Delay (FID)</strong></li>
            <li>✅ <strong>Higher PageSpeed score</strong> - +5 to +15 points</li>
        </ul>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle defer/delay mutual exclusivity warning
    $('input[name="wpsb_options[js_defer_enabled]"], input[name="wpsb_options[js_delay_enabled]"]').on('change', function() {
        var defer = $('input[name="wpsb_options[js_defer_enabled]"]').is(':checked');
        var delay = $('input[name="wpsb_options[js_delay_enabled]"]').is(':checked');
        
        if (defer && delay) {
            if (!$('#js-mode-warning').length) {
                $('<div id="js-mode-warning" class="notice notice-warning" style="margin:15px 0;"><p><strong>Note:</strong> Both Defer and Delay are enabled. Delay takes precedence and includes defer behavior.</p></div>')
                    .insertAfter('h2:contains("Optimization Mode")');
            }
        } else {
            $('#js-mode-warning').remove();
        }
    });
});
</script>
