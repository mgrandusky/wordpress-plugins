/**
 * VelocityWP Admin JavaScript - Left-Handed Navigation
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Smooth scroll on navigation click (adds loading state)
        $('.velocitywp-nav-item').on('click', function(e) {
            // Add loading state
            $(this).addClass('loading');
        });
        
        // Sticky header on scroll
        var $header = $('.velocitywp-page-header');
        var $content = $('.velocitywp-content');
        
        $content.on('scroll', function() {
            if ($content.scrollTop() > 50) {
                $header.addClass('scrolled');
            } else {
                $header.removeClass('scrolled');
            }
        });
        
        // Collapsible sections in mobile
        function handleMobileNav() {
            if (window.matchMedia('(max-width: 960px)').matches) {
                $('.velocitywp-nav-section-title').off('click').on('click', function() {
                    $(this).next('.velocitywp-nav-items').slideToggle();
                });
            } else {
                $('.velocitywp-nav-section-title').off('click');
                $('.velocitywp-nav-items').show();
            }
        }
        
        // Initialize mobile nav
        handleMobileNav();
        
        // Handle window resize
        $(window).on('resize', function() {
            handleMobileNav();
        });

        // Clear Cache Button
        $('#velocitywp-clear-cache-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.text();

            // Disable button and show loading
            $btn.prop('disabled', true).addClass('velocitywp-processing');
            $btn.text(velocitywpAdmin.strings.clearing);

            // Send AJAX request
            $.ajax({
                url: velocitywpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'velocitywp_clear_cache',
                    nonce: velocitywpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || velocitywpAdmin.strings.error);
                    }
                },
                error: function() {
                    showNotice('error', velocitywpAdmin.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('velocitywp-processing');
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
            $btn.prop('disabled', true).addClass('velocitywp-processing');
            $btn.text(velocitywpAdmin.strings.optimizing);

            // Send AJAX request
            $.ajax({
                url: velocitywpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'velocitywp_optimize_database',
                    nonce: velocitywpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        // Reload page to update statistics
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotice('error', response.data.message || velocitywpAdmin.strings.error);
                    }
                },
                error: function() {
                    showNotice('error', velocitywpAdmin.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('velocitywp-processing');
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
            $btn.prop('disabled', true).addClass('velocitywp-processing');
            $btn.text(velocitywpAdmin.strings.preloading);

            // Send AJAX request
            $.ajax({
                url: velocitywpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'velocitywp_preload_cache',
                    nonce: velocitywpAdmin.nonce
                },
                timeout: 300000, // 5 minutes
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || velocitywpAdmin.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        showNotice('warning', 'Cache preloading is taking longer than expected. It may still be running in the background.');
                    } else {
                        showNotice('error', velocitywpAdmin.strings.error);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('velocitywp-processing');
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
        $('.velocitywp-form').on('change', 'input, select, textarea', function() {
            formChanged = true;
        });

        // Reset on form submit
        $('.velocitywp-form').on('submit', function() {
            formChanged = false;
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
