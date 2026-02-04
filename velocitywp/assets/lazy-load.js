/**
 * VelocityWP Lazy Loading Script
 */

(function() {
    'use strict';

    // Check if IntersectionObserver is supported
    var supportsIntersectionObserver = 'IntersectionObserver' in window;

    /**
     * Lazy load images
     */
    function lazyLoadImages() {
        var lazyImages = document.querySelectorAll('img.velocitywp-lazy');

        if (lazyImages.length === 0) {
            return;
        }

        if (supportsIntersectionObserver) {
            // Use IntersectionObserver for modern browsers
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        loadImage(img);
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            lazyImages.forEach(function(img) {
                loadImage(img);
            });
        }
    }

    /**
     * Load image
     */
    function loadImage(img) {
        var src = img.getAttribute('data-src');
        var srcset = img.getAttribute('data-srcset');
        var sizes = img.getAttribute('data-sizes');

        if (!src) {
            return;
        }

        // Add loading class
        img.classList.add('wpsb-loading');

        // Load image
        img.onload = function() {
            img.classList.remove('wpsb-loading');
            img.classList.add('wpsb-loaded');
        };

        img.onerror = function() {
            img.classList.remove('wpsb-loading');
            img.classList.add('wpsb-error');
        };

        // Set src
        img.src = src;

        // Set srcset and sizes if available
        if (srcset) {
            img.srcset = srcset;
        }
        if (sizes) {
            img.sizes = sizes;
        }

        // Remove data attributes
        img.removeAttribute('data-src');
        img.removeAttribute('data-srcset');
        img.removeAttribute('data-sizes');
    }

    /**
     * Lazy load iframes
     */
    function lazyLoadIframes() {
        var lazyIframes = document.querySelectorAll('iframe.velocitywp-lazy-iframe');

        if (lazyIframes.length === 0) {
            return;
        }

        if (supportsIntersectionObserver) {
            // Use IntersectionObserver for modern browsers
            var iframeObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var iframe = entry.target;
                        loadIframe(iframe);
                        observer.unobserve(iframe);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            lazyIframes.forEach(function(iframe) {
                iframeObserver.observe(iframe);
            });
        } else {
            // Fallback for older browsers
            lazyIframes.forEach(function(iframe) {
                loadIframe(iframe);
            });
        }
    }

    /**
     * Load iframe
     */
    function loadIframe(iframe) {
        var src = iframe.getAttribute('data-src');

        if (!src) {
            return;
        }

        // Add loading class
        iframe.classList.add('wpsb-loading');

        // Set src
        iframe.src = src;

        // Remove data attribute
        iframe.removeAttribute('data-src');

        // Remove loading class after load
        iframe.onload = function() {
            iframe.classList.remove('wpsb-loading');
            iframe.classList.add('wpsb-loaded');
        };
    }

    /**
     * Initialize lazy loading
     */
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                lazyLoadImages();
                lazyLoadIframes();
            });
        } else {
            lazyLoadImages();
            lazyLoadIframes();
        }

        // Also check on window load (for dynamically added content)
        window.addEventListener('load', function() {
            lazyLoadImages();
            lazyLoadIframes();
        });
    }

    // Initialize
    init();

    // Expose function for manual triggering (for dynamic content)
    window.wpsbLazyLoad = {
        loadImages: lazyLoadImages,
        loadIframes: lazyLoadIframes,
        init: init
    };

})();
