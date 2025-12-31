<?php 

/**
 * Plugin Name: GA Gravity Form Tracking
 * Description: GA Form submission tracking for Gravity Forms
 * Version: 1.0
 * Author: Mason Grandusky
 */

/**
 * Enqueue script
 */
function gagft_scripts() {
    $current_lang = apply_filters( 'wpml_current_language', NULL );

    // Get all forms
    if ( false === ( $all_forms = get_transient( 'gforms_ids_and_titles' ) ) ) {
        // We need to get form titles in English
        if($current_lang != 'en') {
            do_action( 'wpml_switch_language', "en" );
        }
        $all_forms = [];
        $gforms = RGFormsModel::get_forms();
        foreach ($gforms as $form) {
            if (!$form->is_active) continue;
            $all_forms[$form->id] = $form->title;
        }
        // Reset to current lang
        if($current_lang != 'en') {
            do_action( "wpml_switch_language", $current_lang );
        }
        set_transient( 'gforms_ids_and_titles', $all_forms, 12 * HOUR_IN_SECONDS );
    }

	wp_enqueue_script( 'ga-gf-tracking-script', plugin_dir_url( __FILE__ ) . '/js/ga-gform-tracking.js', array( 'jquery' ) );
    wp_localize_script( 'ga-gf-tracking-script', 'ga_gf_tracking_object',
		array( 
			'lang' => strtoupper($current_lang ?? 'en'),
            'forms' => $all_forms ?? []
		)
	);
}
add_action( 'wp_enqueue_scripts', 'gagft_scripts' );

/**
 * Add Query params on form submit
 */
add_filter( 'gform_confirmation', function ( $confirmation, $form, $entry ) {
    if ( ! is_array( $confirmation ) || empty( $confirmation['redirect'] ) ) {
        return $confirmation;
    }

    $confirmation['redirect'] = add_query_arg( array( 
        'from_form_submission' => 1, 
        'form_id' => $form['id'] ?? 0
    ), $confirmation['redirect'] );

    return $confirmation;
}, 11, 3 );