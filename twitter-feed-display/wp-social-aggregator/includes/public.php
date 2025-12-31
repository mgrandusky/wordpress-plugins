<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'social_aggregator', 'wsa_shortcode_render' );

function wsa_shortcode_render( $atts ) {
    $atts = shortcode_atts( array( 'limit' => 10, 'cache_minutes' => 10 ), $atts, 'social_aggregator' );
    $limit = intval( $atts['limit'] );
    $cache_key = 'wsa_aggregated_' . md5( serialize( $atts ) );
    $cached = get_transient( $cache_key );
    if ( $cached ) return $cached;

    $settings = get_option( 'wsa_settings', array() );

    $items = array();
    $items = array_merge( $items, wsa_fetch_for_network( 'twitter', isset($settings['twitter_sources']) ? $settings['twitter_sources'] : '', isset($settings['twitter_embed']) ? $settings['twitter_embed'] : '', 3 ) );
    $items = array_merge( $items, wsa_fetch_for_network( 'instagram', isset($settings['instagram_sources']) ? $settings['instagram_sources'] : '', isset($settings['instagram_embed']) ? $settings['instagram_embed'] : '', 3 ) );
    $items = array_merge( $items, wsa_fetch_for_network( 'facebook', isset($settings['facebook_sources']) ? $settings['facebook_sources'] : '', isset($settings['facebook_embed']) ? $settings['facebook_embed'] : '', 3 ) );
    $items = array_merge( $items, wsa_fetch_for_network( 'linkedin', isset($settings['linkedin_sources']) ? $settings['linkedin_sources'] : '', isset($settings['linkedin_embed']) ? $settings['linkedin_embed'] : '', 3 ) );

    ob_start();
    ?>
    <div class="wsa-aggregator">
        <?php foreach ( $items as $it ): ?>
            <div class="wsa-item wsa-<?php echo esc_attr( $it['network'] ); ?>">
                <?php echo $it['content']; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    $html = ob_get_clean();
    set_transient( $cache_key, $html, intval($atts['cache_minutes']) * MINUTE_IN_SECONDS );
    return $html;
}
