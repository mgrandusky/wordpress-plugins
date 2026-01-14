/**
 * Google Drive Gallery - Frontend Scripts
 * Handles lightbox and slideshow functionality
 */

(function($) {
    'use strict';

    /**
     * Gallery Lightbox
     */
    class GDriveLightbox {
        constructor(gallery) {
            this.gallery = gallery;
            this.galleryId = gallery.attr('id');
            this.lightbox = $('#lightbox-' + this.galleryId);
            this.images = [];
            this.currentIndex = 0;
            
            this.init();
        }

        init() {
            // Gather all images
            this.gallery.find('.gdrive-gallery-link').each((index, link) => {
                const $link = $(link);
                this.images.push({
                    url: $link.attr('href'),
                    title: $link.data('title') || '',
                    caption: $link.data('caption') || ''
                });
            });

            // Bind events
            this.bindEvents();
        }

        bindEvents() {
            const self = this;

            // Open lightbox on image click
            this.gallery.on('click', '.gdrive-gallery-link', function(e) {
                e.preventDefault();
                const index = $(this).closest('.gdrive-gallery-item').data('index');
                self.open(index);
            });

            // Close lightbox
            this.lightbox.find('.gdrive-lightbox-close, .gdrive-lightbox-overlay').on('click', function() {
                self.close();
            });

            // Navigation
            this.lightbox.find('.gdrive-lightbox-prev').on('click', function() {
                self.prev();
            });

            this.lightbox.find('.gdrive-lightbox-next').on('click', function() {
                self.next();
            });

            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if (!self.lightbox.is(':visible')) return;

                if (e.key === 'Escape') {
                    self.close();
                } else if (e.key === 'ArrowLeft') {
                    self.prev();
                } else if (e.key === 'ArrowRight') {
                    self.next();
                }
            });
        }

        open(index) {
            this.currentIndex = index;
            this.show();
            this.lightbox.fadeIn(300);
        }

        close() {
            this.lightbox.fadeOut(300);
        }

        show() {
            const image = this.images[this.currentIndex];
            const $image = this.lightbox.find('.gdrive-lightbox-image');
            const $caption = this.lightbox.find('.gdrive-lightbox-caption');

            $image.attr('src', image.url);
            $image.attr('alt', image.title);

            if (image.caption) {
                $caption.text(image.caption).show();
            } else {
                $caption.hide();
            }
        }

        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.show();
        }

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
            this.show();
        }
    }

    /**
     * Gallery Slideshow
     */
    class GDriveSlideshow {
        constructor(gallery) {
            this.gallery = gallery;
            this.items = gallery.find('.gdrive-gallery-item');
            this.currentIndex = 0;
            this.isPlaying = false;
            this.interval = null;
            this.duration = 3000; // 3 seconds per slide
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.showSlide(0);
        }

        bindEvents() {
            const self = this;

            this.gallery.find('.gdrive-slideshow-play').on('click', function() {
                self.toggle();
            });

            this.gallery.find('.gdrive-slideshow-prev').on('click', function() {
                self.prev();
            });

            this.gallery.find('.gdrive-slideshow-next').on('click', function() {
                self.next();
            });
        }

        showSlide(index) {
            this.currentIndex = index % this.items.length;
            
            this.items.each((i, item) => {
                $(item).css('opacity', i === this.currentIndex ? '1' : '0.3');
            });
        }

        play() {
            const self = this;
            this.isPlaying = true;
            this.updatePlayButton();
            
            this.interval = setInterval(() => {
                self.next();
            }, this.duration);
        }

        pause() {
            this.isPlaying = false;
            this.updatePlayButton();
            
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        }

        toggle() {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        }

        prev() {
            this.showSlide(this.currentIndex - 1 + this.items.length);
        }

        next() {
            this.showSlide(this.currentIndex + 1);
        }

        updatePlayButton() {
            const $button = this.gallery.find('.gdrive-slideshow-play');
            $button.html(this.isPlaying ? '&#10074;&#10074;' : '&#9658;');
        }
    }

    /**
     * Initialize galleries
     */
    $(document).ready(function() {
        $('.gdrive-gallery').each(function() {
            const $gallery = $(this);
            
            // Initialize lightbox if enabled
            if ($gallery.data('lightbox') === 1) {
                new GDriveLightbox($gallery);
            }
            
            // Initialize slideshow if enabled
            if ($gallery.data('slideshow') === 1) {
                new GDriveSlideshow($gallery);
            }
        });
    });

    /**
     * Handle lazy loading (if needed in future)
     */
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    if (src) {
                        img.setAttribute('src', src);
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        // Observe images with data-src attribute
        $(document).ready(function() {
            $('.gdrive-gallery-image[data-src]').each(function() {
                imageObserver.observe(this);
            });
        });
    }

})(jQuery);

// Folder card functionality (vanilla JS)
document.addEventListener('DOMContentLoaded', function() {
    // Handle folder card clicks
    const folderCards = document.querySelectorAll('.gdrive-folder-card');
    
    folderCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const folderName = this.dataset.folderName;
            const images = JSON.parse(this.dataset.images);
            const galleryId = this.closest('.gdrive-gallery').id;
            
            openFolderLightbox(galleryId, folderName, images);
        });
    });
});

function openFolderLightbox(galleryId, folderName, images) {
    const lightboxId = galleryId + '-lightbox';
    const lightbox = document.getElementById(lightboxId);
    
    if (!lightbox) return;
    
    let currentIndex = 0;
    let keydownHandler = null; // Declare at function scope
    let imageLoadHandler = null; // For cleanup
    let imageErrorHandler = null; // For cleanup
    
    // Add CSS animation to head if not already present
    if (!document.getElementById('gdrive-lightbox-spinner-style')) {
        const style = document.createElement('style');
        style.id = 'gdrive-lightbox-spinner-style';
        style.textContent = '@keyframes gdrive-spin { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }';
        document.head.appendChild(style);
    }
    
    function getImageUrl(file) {
        if (file && file.id) {
            return window.location.origin + '/gdrive-image/' + file.id + '?size=large';
        }
        return '';
    }
    
    let html = '<div class="gdrive-lightbox-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;">';
    html += '<div class="gdrive-lightbox-content" style="position: relative; max-width: 90%; max-height: 90%; text-align: center;">';
    html += '<div class="gdrive-lightbox-header" style="color: white; margin-bottom: 20px;"><h2 style="margin: 0; font-size: 24px;">' + escapeHtml(folderName) + '</h2></div>';
    
    // Image container with loading spinner
    html += '<div class="gdrive-lightbox-image-wrapper" style="position: relative; display: inline-block;">';
    
    // Loading spinner
    html += '<div class="gdrive-lightbox-loader" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 50px; height: 50px; border: 5px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: gdrive-spin 1s linear infinite; z-index: 1;"></div>';
    
    // Image
    html += '<div class="gdrive-lightbox-image-container" style="position: relative;">';
    html += '<img id="gdrive-lightbox-current-image" src="' + getImageUrl(images[0]) + '" style="max-width: 90vw; max-height: 80vh; display: block; margin: 0 auto; opacity: 0; transition: opacity 0.3s ease;" />';
    
    // Close button on IMAGE (not screen corner)
    html += '<button class="gdrive-lightbox-close" style="position: absolute; top: -15px; right: -15px; background: white; border: none; font-size: 24px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; z-index: 10000; color: black; box-shadow: 0 2px 10px rgba(0,0,0,0.5); line-height: 1; font-weight: bold;">&times;</button>';
    
    html += '</div>'; // image-container
    html += '</div>'; // image-wrapper
    
    if (images.length > 1) {
        html += '<button class="gdrive-lightbox-prev" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: white; border: none; font-size: 30px; cursor: pointer; width: 50px; height: 50px; border-radius: 50%; color: black; box-shadow: 0 2px 10px rgba(0,0,0,0.5);">&#8249;</button>';
        html += '<button class="gdrive-lightbox-next" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: white; border: none; font-size: 30px; cursor: pointer; width: 50px; height: 50px; border-radius: 50%; color: black; box-shadow: 0 2px 10px rgba(0,0,0,0.5);">&#8250;</button>';
        html += '<div class="gdrive-lightbox-counter" style="color: white; margin-top: 10px; font-size: 16px;">1 / ' + images.length + '</div>';
    }
    
    html += '</div></div>';
    
    lightbox.innerHTML = html;
    lightbox.style.display = 'block';
    
    // Get elements
    const img = document.getElementById('gdrive-lightbox-current-image');
    const loader = lightbox.querySelector('.gdrive-lightbox-loader');
    const closeBtn = lightbox.querySelector('.gdrive-lightbox-close');
    const overlay = lightbox.querySelector('.gdrive-lightbox-overlay');
    
    // Show image when loaded, hide spinner
    function handleImageLoad() {
        if (loader) loader.style.display = 'none';
        if (img) img.style.opacity = '1';
    }
    
    // Show spinner when loading new image
    function handleImageLoadStart() {
        if (loader) loader.style.display = 'block';
        if (img) img.style.opacity = '0';
    }
    
    // Initial load - store handlers for cleanup
    imageLoadHandler = handleImageLoad;
    imageErrorHandler = function() {
        if (loader) loader.style.display = 'none';
        console.error('Failed to load image');
    };
    
    img.addEventListener('load', imageLoadHandler);
    img.addEventListener('error', imageErrorHandler);
    
    // Close button
    function closeLightbox() {
        lightbox.style.display = 'none';
        // Clean up keyboard listener to prevent memory leak
        if (keydownHandler !== null) {
            document.removeEventListener('keydown', keydownHandler);
            keydownHandler = null;
        }
        // Clean up image event listeners to prevent memory leak
        if (img && imageLoadHandler) {
            img.removeEventListener('load', imageLoadHandler);
        }
        if (img && imageErrorHandler) {
            img.removeEventListener('error', imageErrorHandler);
        }
    }
    closeBtn.addEventListener('click', function(e) {
        e.stopPropagation(); // Prevent event bubbling
        closeLightbox();
    });
    
    // Click anywhere outside image to close
    overlay.addEventListener('click', function(e) {
        // Only close if clicking the overlay itself (dark background)
        // Not when clicking the content area
        if (e.target === overlay) {
            closeLightbox();
        }
    });
    
    // Update the updateImage function to show/hide loader
    function updateImage(index) {
        if (img && images[index]) {
            handleImageLoadStart();
            // No need to set img.onload since we already have addEventListener('load')
            // The existing handler will be called when src changes
            img.src = getImageUrl(images[index]);
        }
    }
    
    // Navigation
    if (images.length > 1) {
        const prevBtn = lightbox.querySelector('.gdrive-lightbox-prev');
        const nextBtn = lightbox.querySelector('.gdrive-lightbox-next');
        const counter = lightbox.querySelector('.gdrive-lightbox-counter');
        
        prevBtn.addEventListener('click', function() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateImage(currentIndex);
            if (counter) counter.textContent = (currentIndex + 1) + ' / ' + images.length;
        });
        
        nextBtn.addEventListener('click', function() {
            currentIndex = (currentIndex + 1) % images.length;
            updateImage(currentIndex);
            if (counter) counter.textContent = (currentIndex + 1) + ' / ' + images.length;
        });
        
        // Keyboard navigation with proper cleanup
        keydownHandler = function(e) {
            if (lightbox.style.display === 'block') {
                if (e.key === 'ArrowLeft') {
                    prevBtn.click();
                } else if (e.key === 'ArrowRight') {
                    nextBtn.click();
                } else if (e.key === 'Escape') {
                    closeBtn.click();
                }
            }
        };
        
        document.addEventListener('keydown', keydownHandler);
    } else {
        // Even with single image, need keyboard handler for Escape
        keydownHandler = function(e) {
            if (lightbox.style.display === 'block' && e.key === 'Escape') {
                closeBtn.click();
            }
        };
        
        document.addEventListener('keydown', keydownHandler);
    }
}

function escapeHtml(text) {
    // Server-side escaping via esc_attr() in PHP already protects against XSS
    // This function provides additional client-side protection for folder names
    // displayed in dynamically generated lightbox HTML
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}
