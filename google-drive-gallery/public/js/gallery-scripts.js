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
