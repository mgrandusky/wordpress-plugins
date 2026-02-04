<?php
/**
 * JavaScript Delay Intelligence Class
 * Smart defer/delay for JavaScript with user interaction detection
 */

if (!defined('ABSPATH')) exit;

class VelocityWP_JS_Delay {
    
    private $settings;
    private $excluded_scripts = array();
    private $delay_timeout = 5000; // 5 seconds default
    
    public function __construct() {
        $this->settings = get_option('velocitywp_options', array());
        
        if ($this->is_enabled()) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 3);
            add_action('wp_footer', array($this, 'inject_delay_script'), 999);
            
            // Load excluded scripts list
            $this->load_excluded_scripts();
        }
    }
    
    /**
     * Check if JS delay is enabled
     */
    public function is_enabled() {
        return !empty($this->settings['js_delay_enabled']) || !empty($this->settings['js_defer_enabled']);
    }
    
    /**
     * Load excluded scripts from settings
     */
    private function load_excluded_scripts() {
        // Default excluded scripts
        $default_excluded = array(
            'jquery-core',
            'jquery',
            'velocitywp-', // Our own scripts
        );
        
        // User-defined excluded scripts
        $user_excluded = !empty($this->settings['js_delay_exclude']) ? 
            explode("\n", $this->settings['js_delay_exclude']) : array();
        
        $user_excluded = array_map('trim', $user_excluded);
        
        $this->excluded_scripts = array_merge($default_excluded, $user_excluded);
        $this->delay_timeout = !empty($this->settings['js_delay_timeout']) ? 
            intval($this->settings['js_delay_timeout']) * 1000 : 5000;
    }
    
    /**
     * Check if script should be excluded from delay
     */
    private function is_excluded($handle, $src) {
        // Check by handle
        foreach ($this->excluded_scripts as $excluded) {
            if (empty($excluded)) continue;
            
            // Wildcard matching
            if (strpos($excluded, '*') !== false) {
                $pattern = '/' . str_replace('*', '.*', preg_quote($excluded, '/')) . '/i';
                if (preg_match($pattern, $handle) || preg_match($pattern, $src)) {
                    return true;
                }
            } else {
                if (stripos($handle, $excluded) !== false || stripos($src, $excluded) !== false) {
                    return true;
                }
            }
        }
        
        // Always exclude admin scripts
        if (strpos($src, '/wp-admin/') !== false || strpos($src, '/wp-includes/') !== false) {
            return false; // Don't exclude WP core scripts unless specified
        }
        
        return false;
    }
    
    /**
     * Add defer attribute to scripts
     */
    public function add_defer_attribute($tag, $handle, $src) {
        // Skip if already has defer or async
        if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
            return $tag;
        }
        
        // Skip excluded scripts
        if ($this->is_excluded($handle, $src)) {
            return $tag;
        }
        
        // Defer mode
        if (!empty($this->settings['js_defer_enabled'])) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        // Delay mode - add special data attribute
        if (!empty($this->settings['js_delay_enabled'])) {
            // Replace src with data-velocitywp-src for delayed loading
            $tag = str_replace(' src=', ' data-velocitywp-src=', $tag);
            $tag = str_replace('<script ', '<script type="velocitywp-delayed" ', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Inject delay script in footer
     */
    public function inject_delay_script() {
        $delay_timeout = $this->delay_timeout;
        $trigger_events = $this->get_trigger_events();
        
        ?>
        <script id="velocitywp-delay-script">
        (function() {
            'use strict';
            
            // Configuration
            var delayTimeout = <?php echo $delay_timeout; ?>;
            var triggered = false;
            var delayedScripts = {
                normal: [],
                async: [],
                defer: []
            };
            
            // Collect all delayed scripts
            function collectDelayedScripts() {
                var scripts = document.querySelectorAll('script[type="velocitywp-delayed"]');
                scripts.forEach(function(script) {
                    var copy = {
                        src: script.getAttribute('data-velocitywp-src'),
                        code: script.textContent,
                        async: script.hasAttribute('async'),
                        defer: script.hasAttribute('defer')
                    };
                    
                    if (copy.async) {
                        delayedScripts.async.push(copy);
                    } else if (copy.defer) {
                        delayedScripts.defer.push(copy);
                    } else {
                        delayedScripts.normal.push(copy);
                    }
                });
            }
            
            // Load a script
            function loadScript(scriptData, callback) {
                var script = document.createElement('script');
                
                if (scriptData.src) {
                    script.src = scriptData.src;
                } else if (scriptData.code) {
                    script.textContent = scriptData.code;
                }
                
                if (scriptData.async) script.async = true;
                if (scriptData.defer) script.defer = true;
                
                if (callback) {
                    script.onload = callback;
                    script.onerror = callback;
                }
                
                document.body.appendChild(script);
            }
            
            // Load all delayed scripts
            function loadDelayedScripts() {
                if (triggered) return;
                triggered = true;
                
                console.log('[VelocityWP] Loading delayed scripts...');
                
                // Load normal scripts sequentially
                var normalIndex = 0;
                function loadNextNormal() {
                    if (normalIndex >= delayedScripts.normal.length) {
                        // After normal scripts, load async/defer
                        delayedScripts.async.forEach(function(script) {
                            loadScript(script);
                        });
                        delayedScripts.defer.forEach(function(script) {
                            loadScript(script);
                        });
                        return;
                    }
                    
                    loadScript(delayedScripts.normal[normalIndex], function() {
                        normalIndex++;
                        loadNextNormal();
                    });
                }
                
                loadNextNormal();
            }
            
            // User interaction events
            var events = [<?php echo implode(',', array_map(function($e) { return "'" . $e . "'"; }, $trigger_events)); ?>];
            
            events.forEach(function(event) {
                window.addEventListener(event, function() {
                    loadDelayedScripts();
                }, { passive: true, once: true });
            });
            
            // Fallback timeout
            setTimeout(function() {
                if (!triggered) {
                    console.log('[VelocityWP] Loading scripts after timeout');
                    loadDelayedScripts();
                }
            }, delayTimeout);
            
            // Page load
            if (document.readyState === 'complete') {
                collectDelayedScripts();
            } else {
                window.addEventListener('load', collectDelayedScripts);
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Get trigger events
     */
    private function get_trigger_events() {
        $default_events = array('mousemove', 'scroll', 'touchstart', 'click', 'keydown');
        
        if (!empty($this->settings['js_delay_events'])) {
            $custom_events = explode(',', $this->settings['js_delay_events']);
            return array_map('trim', $custom_events);
        }
        
        return $default_events;
    }
    
    /**
     * Get statistics
     */
    public function get_stats() {
        global $wp_scripts;
        
        if (!is_object($wp_scripts)) {
            return array('total' => 0, 'delayed' => 0, 'excluded' => 0);
        }
        
        $total = count($wp_scripts->queue);
        $excluded = 0;
        
        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $src = $wp_scripts->registered[$handle]->src;
                if ($this->is_excluded($handle, $src)) {
                    $excluded++;
                }
            }
        }
        
        return array(
            'total' => $total,
            'delayed' => $total - $excluded,
            'excluded' => $excluded
        );
    }
}

// Initialize
new VelocityWP_JS_Delay();
