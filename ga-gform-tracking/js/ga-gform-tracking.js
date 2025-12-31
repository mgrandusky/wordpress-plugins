jQuery(document).on('gform_confirmation_loaded', function(event, formId){
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({
        'event': 'generate_lead',
        'language': ga_gf_tracking_object.lang, 
        'label': ga_gf_tracking_object.forms[formId]
    });
});

jQuery(document).ready(function() {

    const formSubmissionMarkerParam = 'from_form_submission';
 
    const url = new URL(window.location.href)
    const searchParams = url.searchParams
    const [
        isFromFormSubmission,
        formId
    ] = [
        searchParams.has(formSubmissionMarkerParam),
        decodeURI(searchParams.get('form_id'))
    ];
 
    if (!isFromFormSubmission) {
        return;
    }
    // Fire GTM event
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({
        'event': 'generate_lead',
        'language': ga_gf_tracking_object.lang,
        'label': ga_gf_tracking_object.forms[formId]
    });
 
    // Remove URL params to prevent multiple event firing on page reload or carrying it over
    searchParams.delete(formSubmissionMarkerParam);
    searchParams.delete('form_id');
    // Update browser URL without reloading
    window.history.pushState(null, null, url.toString());
})