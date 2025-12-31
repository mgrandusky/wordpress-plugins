(function($){
    'use strict';

    var videosPerPage = 3;

    function closeLightbox($overlay) {
        var $iframe = $overlay.find('iframe');
        if ( $iframe.length ) {
            $iframe.attr('src', '');
        }
        $overlay.remove();
        $(document).off('keyup.yfd');
    }

    function openLightbox(videoId, startSeconds) {
        // Create overlay without comments sidebar
        var $overlay = $(
            '<div id="yfd-lightbox-overlay" class="yfd-overlay" role="dialog" aria-modal="true">' +
                '<div class="yfd-lightbox-inner">' +
                    '<button class="yfd-lightbox-close" aria-label="Close">&times;</button>' +
                    '<div class="yfd-lightbox-main">' +
                        '<div class="yfd-lightbox-iframe-wrap"><iframe src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-
media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>' +                                                                                                     '</div>' +
                '</div>' +
            '</div>'
        );

        $('body').append($overlay);

        var $iframe = $overlay.find('iframe');
        var src = 'https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?rel=0&autoplay=1';
        if ( startSeconds ) {
            src += '&start=' + parseInt(startSeconds, 10);
        }
        $iframe.attr('src', src);

        // Close handlers
        $overlay.on('click', function(e){
            if ( $(e.target).is('#yfd-lightbox-overlay') ) {
                closeLightbox($overlay);
            }
        });

        $overlay.find('.yfd-lightbox-close').on('click', function(){
            closeLightbox($overlay);
        });

        $(document).on('keyup.yfd', function(e){
            if ( e.key === 'Escape' || e.keyCode === 27 ) {
                closeLightbox($overlay);
            }
        });
    }

    function openPlaylistLightbox(playlistId) {
        // Create overlay for playlist with video list sidebar
        var $overlay = $(
            '<div id="yfd-lightbox-overlay" class="yfd-overlay yfd-overlay-playlist" role="dialog" aria-modal="true">' +
                '<div class="yfd-lightbox-inner">' +
                    '<button class="yfd-lightbox-close" aria-label="Close">&times;</button>' +
                    '<div class="yfd-lightbox-main yfd-lightbox-main-playlist">' +
                        '<div class="yfd-lightbox-iframe-wrap"><iframe src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-
media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>' +                                                                                                         '<aside class="yfd-playlist-sidebar" role="region" aria-label="Playlist Videos">' +
                            '<div class="yfd-playlist-header"><h3>Playlist Videos</h3></div>' +
                            '<div class="yfd-playlist-list"></div>' +
                            '<div class="yfd-playlist-pagination"></div>' +
                        '</aside>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        $('body').append($overlay);

        var $iframe = $overlay.find('iframe');
        var src = 'https://www.youtube.com/embed/videoseries?list=' + encodeURIComponent(playlistId);
        $iframe.attr('src', src);

        // Close handlers
        $overlay.on('click', function(e){
            if ( $(e.target).is('#yfd-lightbox-overlay') ) {
                closeLightbox($overlay);
            }
        });

        $overlay.find('.yfd-lightbox-close').on('click', function(){
            closeLightbox($overlay);
        });

        $(document).on('keyup.yfd', function(e){
            if ( e.key === 'Escape' || e.keyCode === 27 ) {
                closeLightbox($overlay);
            }
        });

        // Load playlist videos
        loadPlaylistVideos(playlistId, $overlay.find('.yfd-playlist-list'), $overlay.find('.yfd-playlist-pagination'));
    }

    function parseStartFromUrl(href) {
        if ( ! href ) { return null; }
        var m = href.match(/[?&](?:start|t)=([^&]+)/);
        if ( ! m ) { return null; }
        var val = decodeURIComponent(m[1]);
        // formats: 90, 90s, 1m30s
        var total = 0;
        var mm = val.match(/(\d+)m(\d+)s/);
        if ( mm ) {
            total = parseInt(mm[1],10) * 60 + parseInt(mm[2],10);
            return total;
        }
        var ss = val.match(/(\d+)s$/);
        if ( ss ) {
            return parseInt(ss[1],10);
        }
        if ( /^\d+$/.test(val) ) {
            return parseInt(val,10);
        }
        return null;
    }

    // Configuration from PHP
    var yfdCfg = window.yfdLightboxConfig || {};
    var yfdBreakpoint = parseInt(yfdCfg.mobileBreakpoint, 10) || 600;
    var yfdForceTitle = !!yfdCfg.forceTitleLightbox;
    var yfdShowComments = yfdCfg.showComments !== false;

    $(document).ready(function(){
        // Delegate click handler for thumbnails and title links (videos)
        $(document).on('click', '.yfd-video-link, .yfd-title-link', function(e){
            var $el = $(this);
            var mobileOnly = $el.data('yfd-mobile-only');

            // If titles are forced to lightbox globally, ignore mobile-only behavior
            if ( mobileOnly && ! yfdForceTitle && window.innerWidth > yfdBreakpoint ) {
                return; // let the link navigate normally on larger viewports
            }

            var videoId = $el.attr('data-yfd-id');
            var start = $el.attr('data-yfd-start') || parseStartFromUrl($el.attr('href'));

            e.preventDefault();
            if ( videoId ) {
                openLightbox(videoId, start);
            }
        });

        // Delegate click handler for playlist links
        $(document).on('click', '.yfd-playlist-link, .yfd-playlist-title-link', function(e){
            var $el = $(this);
            var mobileOnly = $el.data('yfd-mobile-only');

            // If titles are forced to lightbox globally, ignore mobile-only behavior
            if ( mobileOnly && ! yfdForceTitle && window.innerWidth > yfdBreakpoint ) {
                return; // let the link navigate normally on larger viewports
            }

            var playlistId = $el.attr('data-yfd-playlist-id');

            e.preventDefault();
            if ( playlistId ) {
                openPlaylistLightbox(playlistId);
            }
        });
    });

    // Load comments for a video id into $container
    function loadComments(videoId, $container) {
        var $sidebar = $container.closest('.yfd-comments-sidebar');
        $container.html('<div class="yfd-comments-loading">Loading comments...</div>');
        $.get(yfdAjax.url, { action: 'yfd_fetch_comments', video_id: videoId }, function(resp){
            if ( resp && resp.success && resp.data && resp.data.length > 0 ) {
                renderComments(resp.data, $container);
            } else {
                // No comments: hide the entire sidebar
                $sidebar.hide();
            }
        }, 'json').fail(function(){
            // API error: hide the sidebar
            $sidebar.hide();
        });
    }

    function renderComments(items, $container) {
        $container.empty();
        if ( ! items || items.length === 0 ) {
            $container.closest('.yfd-comments-sidebar').hide();
            return;
        }
        items.forEach(function(c){
            var html = '<div class="yfd-comment">'
                + '<div class="yfd-comment-header">';
            if ( c.authorProfileImageUrl ) {
                html += '<img class="yfd-comment-avatar" src="' + c.authorProfileImageUrl + '" alt="" />';
            }
            html += '<div class="yfd-comment-author-info">'
                + '<strong>' + escapeHtml(c.author || 'Anonymous') + '</strong>'
                + '<span class="yfd-comment-date">' + formatDate(c.publishedAt) + '</span>'
                + '</div>'
                + '</div>'
                + '<div class="yfd-comment-text">' + escapeHtml(c.text) + '</div>'
                + '<div class="yfd-comment-likes">❤️ ' + (c.likeCount || 0) + ' likes</div>'
                + '</div>';
            $container.append(html);
        });
    }

    function prependComment(c, $container) {
        var html = '<div class="yfd-comment">'
            + '<div class="yfd-comment-header">';
        if ( c.authorProfileImageUrl ) {
            html += '<img class="yfd-comment-avatar" src="' + c.authorProfileImageUrl + '" alt="" />';
        }
        html += '<div class="yfd-comment-author-info">'
            + '<strong>' + escapeHtml(c.author || 'Anonymous') + '</strong>'
            + '<span class="yfd-comment-date">' + formatDate(c.publishedAt) + '</span>'
            + '</div>'
            + '</div>'
            + '<div class="yfd-comment-text">' + escapeHtml(c.text) + '</div>'
            + '<div class="yfd-comment-likes">❤️ ' + (c.likeCount || 0) + ' likes</div>'
            + '</div>';
        $container.prepend(html);
    }

    function formatDate(dateStr) {
        if ( ! dateStr ) return '';
        var d = new Date(dateStr);
        var now = new Date();
        var diff = Math.floor((now - d) / 1000);
        
        if ( diff < 60 ) return 'now';
        if ( diff < 3600 ) return Math.floor(diff / 60) + 'm ago';
        if ( diff < 86400 ) return Math.floor(diff / 3600) + 'h ago';
        if ( diff < 604800 ) return Math.floor(diff / 86400) + 'd ago';
        
        return d.toLocaleDateString();
    }

    function loadPlaylistVideos(playlistId, $container, $paginationContainer) {
        var $sidebar = $container.closest('.yfd-playlist-sidebar');
        $container.html('<div class="yfd-playlist-loading">Loading playlist...</div>');
        $.get(yfdAjax.url, { action: 'yfd_fetch_playlist_videos', playlist_id: playlistId }, function(resp){
            if ( resp && resp.success && resp.data && resp.data.length > 0 ) {
                renderPlaylistVideos(resp.data, $container, $paginationContainer);
            } else {
                // No videos: hide the sidebar
                $sidebar.hide();
            }
        }, 'json').fail(function(){
            // API error: hide the sidebar
            $sidebar.hide();
        });
    }

    function renderPlaylistVideos(items, $container, $paginationContainer) {
        $container.empty();
        if ( ! items || items.length === 0 ) {
            $container.closest('.yfd-playlist-sidebar').hide();
            return;
        }

        var totalPages = Math.ceil(items.length / videosPerPage);
        var currentPage = 1;

        function renderPage(page) {
            $container.empty();
            var startIdx = (page - 1) * videosPerPage;
            var endIdx = startIdx + videosPerPage;
            var pageItems = items.slice(startIdx, endIdx);

            pageItems.forEach(function(v){
                var html = '<div class="yfd-playlist-video" data-yfd-video-id="' + escapeAttr(v.id) + '">'
                    + '<div class="yfd-playlist-video-thumb">';
                if ( v.thumb ) {
                    html += '<img src="' + v.thumb + '" alt="" />';
                }
                html += '</div>'
                    + '<div class="yfd-playlist-video-info">'
                    + '<div class="yfd-playlist-video-title">' + escapeHtml(v.title) + '</div>'
                    + '</div>'
                    + '</div>';
                $container.append(html);
            });

            // Attach event delegation on the container for video clicks
            $container.off('click.playlist').on('click.playlist', '.yfd-playlist-video', function(e){
                e.preventDefault();
                e.stopPropagation();
                var videoId = $(this).data('yfd-video-id');
                if ( videoId ) {
                    var $overlay = $container.closest('#yfd-lightbox-overlay');
                    playPlaylistVideo(videoId, $overlay);
                }
            });
        }

        function renderPagination() {
            $paginationContainer.empty();
            
            if ( totalPages <= 1 ) {
                return; // No pagination needed
            }

            var $pagination = $('<div class="yfd-playlist-pagination-controls"></div>');
            
            // Previous button
            var $prevBtn = $('<button class="yfd-pagination-btn yfd-pagination-prev" aria-label="Previous page">&lt;</button>');
            $prevBtn.on('click', function(){
                if ( currentPage > 1 ) {
                    currentPage--;
                    renderPage(currentPage);
                    updatePaginationState();
                }
            });
            $pagination.append($prevBtn);

            // Page indicators
            var $indicators = $('<div class="yfd-pagination-dots"></div>');
            for ( var i = 1; i <= totalPages; i++ ) {
                var $dot = $('<button class="yfd-pagination-dot" data-page="' + i + '" aria-label="Page ' + i + '"></button>');
                if ( i === currentPage ) {
                    $dot.addClass('yfd-active');
                }
                $dot.on('click', function(){
                    currentPage = parseInt($(this).data('page'), 10);
                    renderPage(currentPage);
                    updatePaginationState();
                });
                $indicators.append($dot);
            }
            $pagination.append($indicators);

            // Next button
            var $nextBtn = $('<button class="yfd-pagination-btn yfd-pagination-next" aria-label="Next page">&gt;</button>');
            $nextBtn.on('click', function(){
                if ( currentPage < totalPages ) {
                    currentPage++;
                    renderPage(currentPage);
                    updatePaginationState();
                }
            });
            $pagination.append($nextBtn);

            $paginationContainer.append($pagination);
        }

        function updatePaginationState() {
            $paginationContainer.find('.yfd-pagination-dot').removeClass('yfd-active');
            $paginationContainer.find('[data-page="' + currentPage + '"]').addClass('yfd-active');
            
            var $prevBtn = $paginationContainer.find('.yfd-pagination-prev');
            var $nextBtn = $paginationContainer.find('.yfd-pagination-next');
            
            $prevBtn.prop('disabled', currentPage === 1);
            $nextBtn.prop('disabled', currentPage === totalPages);
        }

        // Initial render
        renderPage(1);
        renderPagination();
        updatePaginationState();
    }

    function playPlaylistVideo(videoId, $overlay) {
        var $iframe = $overlay.find('iframe');
        var src = 'https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?rel=0&autoplay=1';
        $iframe.attr('src', src);

        // Update active state in the video list
        var $container = $overlay.find('.yfd-playlist-list');
        $container.find('.yfd-playlist-video').removeClass('yfd-active');
        $container.find('[data-yfd-video-id="' + escapeAttr(videoId) + '"]').addClass('yfd-active');
    }

    function escapeAttr(s) {
        if ( ! s ) return '';
        return String(s).replace(/[&"'<>]/g, function(c) { return {'&':'&amp;','"':'&quot;',"'":"&#39;","<":"&lt;",">":"&gt;"}[c]; });
    }

    function escapeHtml(s) {
        if ( ! s ) return '';
        return s.replace(/[&"'<>]/g, function(c) { return {'&':'&amp;','"':'&quot;',"'":"&#39;","<":"&lt;",">":"&gt;"}[c]; });
    }

})(jQuery);
