/**
 * Google Drive Gallery - Admin Scripts
 * 
 * Additional admin JavaScript functionality
 * Main AJAX handlers are in settings-page.php inline script
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Auth type switcher - show/hide relevant fields
        $('input[name="gdrive_gallery_auth_type"]').on('change', function() {
            var authType = $(this).val();
            
            // This could be used to show/hide sections if needed
            // Currently handled by the form structure
        });
    });

})(jQuery);
