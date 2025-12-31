<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'wsa_add_admin_menu' );
add_action( 'admin_init', 'wsa_settings_init' );

function wsa_add_admin_menu() {
    add_options_page( 'WP Social Aggregator', 'Social Aggregator', 'manage_options', 'wsa_settings', 'wsa_options_page' );
}

function wsa_settings_init() {
    register_setting( 'wsa_plugin', 'wsa_settings' );

    add_settings_section(
        'wsa_section_main',
        'Feed Sources and Keys',
        function(){ echo '<p>Enter comma-separated source URLs or handles. Paste full embed HTML into the Embed field.</p>'; },
        'wsa_plugin'
    );

    $fields = array(
        'twitter_sources' => 'Twitter Sources (URLs or handles)',
        'twitter_embed'   => 'Twitter Embed HTML',
        'twitter_api_key' => 'Twitter API Key (optional)',
        'instagram_sources' => 'Instagram Sources (URLs)',
        'instagram_embed'   => 'Instagram Embed HTML',
        'instagram_api_key' => 'Instagram API Key (optional)',
        'facebook_sources' => 'Facebook Sources (URLs or page IDs)',
        'facebook_embed'   => 'Facebook Embed HTML',
        'facebook_api_key' => 'Facebook API Key (optional)',
        'linkedin_sources' => 'LinkedIn Sources (URLs)',
        'linkedin_embed'   => 'LinkedIn Embed HTML',
        'linkedin_api_key' => 'LinkedIn API Key (optional)',
    );

    foreach ( $fields as $key => $label ) {
        add_settings_field(
            $key,
            $label,
            function($args){
                $opt = get_option( 'wsa_settings', array() );
                $name = $args['key'];
                $val = isset($opt[$name]) ? $opt[$name] : '';
                if ( strpos($name, 'embed') !== false ) {
                    printf('<textarea rows="6" cols="60" name="wsa_settings[%s]">%s</textarea>', esc_attr($name), esc_textarea($val));
                } else {
                    printf('<input class="regular-text" name="wsa_settings[%s]" value="%s" />', esc_attr($name), esc_attr($val));
                }
            },
            'wsa_plugin',
            'wsa_section_main',
            array('key' => $key)
        );
    }
}

function wsa_options_page() {
    ?>
    <div class="wrap">
        <h1>WP Social Aggregator</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'wsa_plugin' );
            do_settings_sections( 'wsa_plugin' );
            submit_button();
            ?>
        </form>
        <h2>Quick examples</h2>
        <ul>
            <li>Twitter sources: https://twitter.com/username or username</li>
            <li>Instagram sources: https://www.instagram.com/username/</li>
            <li>Paste widget embed HTML into the Embed HTML field for a richer presentation.</li>
        </ul>
    </div>
    <?php
}
