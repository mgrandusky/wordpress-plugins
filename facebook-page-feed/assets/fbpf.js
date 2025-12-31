(function($){
    'use strict';

    $(document).on('click', '.fbpf-loadmore', function(e){
        e.preventDefault();
        var $btn = $(this);
        if ( $btn.data('loading') ) return;
        var pageId = $btn.data('page-id');
        var after = $btn.data('after');
        var limit = $btn.data('limit') || 5;

        $btn.data('loading', true).prop('disabled', true).text('Loading...');

        $.post( fbpfAjax.url, {
            action: 'fbpf_load_more',
            nonce: fbpfAjax.nonce,
            page_id: pageId,
            after: after,
            limit: limit
        }, function(resp){
            $btn.data('loading', false);
            if ( resp && resp.success && resp.data ) {
                var $wrap = $btn.closest('.fbpf-loadmore-wrap');
                // append items before the loadmore button
                $wrap.before( resp.data.html );
                if ( resp.data.after ) {
                    $btn.data('after', resp.data.after);
                    $btn.prop('disabled', false).text('Load more');
                } else {
                    // no more pages
                    $wrap.remove();
                }
            } else {
                $btn.prop('disabled', false).text('Load more');
                console.error('FBPF load_more error', resp);
                alert('Error loading more posts.');
            }
        }, 'json').fail(function(){
            $btn.data('loading', false);
            $btn.prop('disabled', false).text('Load more');
            alert('Network error while loading more posts.');
        });
    });

})(jQuery);
