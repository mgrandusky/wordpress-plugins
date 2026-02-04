/**
 * VelocityWP Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab switching without page reload
        $('.velocitywp-nav-tab').on('click', function(e) {
            e.preventDefault();
            
            var targetTab = $(this).data('tab');
            
            // Update active tab styling
            $('.velocitywp-nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Hide all tab contents
            $('.velocitywp-tab-content').removeClass('active').hide();
            
            // Show target tab with fade effect
            $('#velocitywp-tab-' + targetTab).addClass('active').fadeIn(200);
            
            // Update URL hash without page reload
            if (history.pushState) {
                history.pushState(null, null, '#tab-' + targetTab);
            } else {
                window.location.hash = '#tab-' + targetTab;
            }
            
            // Store active tab in session storage
            sessionStorage.setItem('velocitywp_active_tab', targetTab);
        });
        
        // Load tab from URL hash or session storage on page load
        function loadActiveTab() {
            var hash = window.location.hash.replace('#tab-', '');
            var sessionTab = sessionStorage.getItem('velocitywp_active_tab');
            var activeTab = hash || sessionTab || 'dashboard';
            
            // Trigger click on the active tab
            var $tab = $('.velocitywp-nav-tab[data-tab="' + activeTab + '"]');
            if ($tab.length) {
                $tab.trigger('click');
            } else {
                // Fallback to dashboard if tab not found
                $('.velocitywp-nav-tab[data-tab="dashboard"]').trigger('click');
            }
        }
        
        // Handle browser back/forward buttons
        $(window).on('hashchange', function() {
            var hash = window.location.hash.replace('#tab-', '');
            if (hash) {
                $('.velocitywp-nav-tab[data-tab="' + hash + '"]').trigger('click');
            }
        });
        
        // Initialize on page load
        loadActiveTab();

        // Clear Cache Button
        $('#velocitywp-clear-cache-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.text();

            // Disable button and show loading
            $btn.prop('disabled', true).addClass('wpsb-processing');
            $btn.text(wpsbAdmin.strings.clearing);

            // Send AJAX request
            $.ajax({
                url: wpsbAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'velocitywp_clear_cache',
                    nonce: wpsbAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || wpsbAdmin.strings.error);
                    }
                },
                error: function() {
                    showNotice('error', wpsbAdmin.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wpsb-processing');
                    $btn.text(originalText);
                }
            });
        });

        // Optimize Database Button
        $('#velocitywp-optimize-db-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.text();

            if (!confirm('Are you sure you want to optimize the database? This action cannot be undone.')) {
                return;
            }

            // Disable button and show loading
            $btn.prop('disabled', true).addClass('wpsb-processing');
            $btn.text(wpsbAdmin.strings.optimizing);

            // Send AJAX request
            $.ajax({
                url: wpsbAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'velocitywp_optimize_database',
                    nonce: wpsbAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        // Reload page to update statistics
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotice('error', response.data.message || wpsbAdmin.strings.error);
                    }
                },
                error: function() {
                    showNotice('error', wpsbAdmin.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wpsb-processing');
                    $btn.text(originalText);
                }
            });
        });

        // Preload Cache Button
        $('#velocitywp-preload-cache-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.text();

            if (!confirm('This will preload cache for all pages. It may take a few minutes. Continue?')) {
                return;
            }

            // Disable button and show loading
            $btn.prop('disabled', true).addClass('wpsb-processing');
            $btn.text(wpsbAdmin.strings.preloading);

            // Send AJAX request
            $.ajax({
                url: wpsbAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'velocitywp_preload_cache',
                    nonce: wpsbAdmin.nonce
                },
                timeout: 300000, // 5 minutes
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || wpsbAdmin.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        showNotice('warning', 'Cache preloading is taking longer than expected. It may still be running in the background.');
                    } else {
                        showNotice('error', wpsbAdmin.strings.error);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wpsb-processing');
                    $btn.text(originalText);
                }
            });
        });

        // Show notice helper function
        function showNotice(type, message) {
            var $notice = $('#velocitywp-ajax-result');
            
            // Remove existing classes
            $notice.removeClass('notice-success notice-error notice-warning');
            
            // Add appropriate class
            $notice.addClass('notice-' + type);
            
            // Set message
            $notice.html('<p>' + message + '</p>');
            
            // Show notice
            $notice.slideDown();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.slideUp();
            }, 5000);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 500);
        }

        // Toggle sections (if needed for future enhancements)
        $('.velocitywp-toggle-header').on('click', function() {
            $(this).next('.velocitywp-toggle-content').slideToggle();
        });

        // Confirm before leaving page with unsaved changes
        var formChanged = false;
        $('#velocitywp-settings-form').on('change', 'input, select, textarea', function() {
            formChanged = true;
            
            // Add indicator to tabs with changes
            var tabId = $(this).closest('.velocitywp-tab-content').attr('id');
            if (tabId) {
                var tabName = tabId.replace('velocitywp-tab-', '');
                $('.velocitywp-nav-tab[data-tab="' + tabName + '"]').addClass('has-changes');
            }
        });

        // Reset on form submit
        $('#velocitywp-settings-form').on('submit', function() {
            formChanged = false;
            $('.velocitywp-nav-tab').removeClass('has-changes');
        });

        // Warn before leaving with unsaved changes
        $(window).on('beforeunload', function() {
            if (formChanged) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Enable/disable related fields based on checkboxes
        $('input[name="velocitywp_options[cache_enabled]"]').on('change', function() {
            var $relatedFields = $('input[name="velocitywp_options[cache_lifespan]"], input[name="velocitywp_options[mobile_cache]"]');
            if ($(this).is(':checked')) {
                $relatedFields.prop('disabled', false);
            } else {
                $relatedFields.prop('disabled', true);
            }
        }).trigger('change');

        $('input[name="velocitywp_options[cdn_enabled]"]').on('change', function() {
            var $cdnUrl = $('input[name="velocitywp_options[cdn_url]"]');
            if ($(this).is(':checked')) {
                $cdnUrl.prop('disabled', false);
            } else {
                $cdnUrl.prop('disabled', true);
            }
        }).trigger('change');

        $('input[name="velocitywp_options[db_clean_revisions]"]').on('change', function() {
            var $revisionsToKeep = $('input[name="velocitywp_options[db_revisions_to_keep]"]');
            if ($(this).is(':checked')) {
                $revisionsToKeep.prop('disabled', false);
            } else {
                $revisionsToKeep.prop('disabled', true);
            }
        }).trigger('change');

        // Tooltips (if WordPress admin tooltips are available)
        if (typeof $.fn.tooltip !== 'undefined') {
            $('.description').tooltip();
        }
    });

})(jQuery);
