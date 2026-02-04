<?php
/**
 * White Label Class
 *
 * Plugin rebranding and white label options
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_White_Label class
 */
class VelocityWP_White_Label {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name = 'VelocityWP';

	/**
	 * Plugin description
	 *
	 * @var string
	 */
	private $plugin_description = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize white label
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['white_label'] ) ) {
			return;
		}

		// Load custom branding
		$this->plugin_name = ! empty( $options['white_label_name'] ) ? $options['white_label_name'] : $this->plugin_name;
		$this->plugin_description = ! empty( $options['white_label_description'] ) ? $options['white_label_description'] : '';

		// Apply white label changes
		add_filter( 'all_plugins', array( $this, 'modify_plugin_info' ) );
		add_filter( 'gettext', array( $this, 'modify_plugin_text' ), 10, 3 );
		
		// Hide from non-admins
		if ( ! empty( $options['white_label_hide'] ) && ! current_user_can( 'manage_options' ) ) {
			add_filter( 'all_plugins', array( $this, 'hide_plugin' ) );
		}

		// Custom admin branding
		if ( ! empty( $options['white_label_admin'] ) ) {
			add_action( 'admin_head', array( $this, 'add_custom_branding' ) );
		}
	}

	/**
	 * Modify plugin information
	 *
	 * @param array $plugins Plugins array.
	 * @return array Modified plugins.
	 */
	public function modify_plugin_info( $plugins ) {
		$plugin_file = plugin_basename( VelocityWP_FILE );

		if ( isset( $plugins[ $plugin_file ] ) ) {
			$plugins[ $plugin_file ]['Name'] = $this->plugin_name;
			
			if ( ! empty( $this->plugin_description ) ) {
				$plugins[ $plugin_file ]['Description'] = $this->plugin_description;
			}

			$options = get_option( 'velocitywp_options', array() );

			// Custom author
			if ( ! empty( $options['white_label_author'] ) ) {
				$plugins[ $plugin_file ]['Author'] = $options['white_label_author'];
			}

			// Custom author URI
			if ( ! empty( $options['white_label_author_uri'] ) ) {
				$plugins[ $plugin_file ]['AuthorURI'] = $options['white_label_author_uri'];
			}

			// Hide plugin URI
			if ( ! empty( $options['white_label_hide_uri'] ) ) {
				$plugins[ $plugin_file ]['PluginURI'] = '';
			}
		}

		return $plugins;
	}

	/**
	 * Modify plugin text strings
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @return string Modified translation.
	 */
	public function modify_plugin_text( $translation, $text, $domain ) {
		if ( $domain !== 'velocitywp' ) {
			return $translation;
		}

		// Replace plugin name in translations
		if ( strpos( $text, 'VelocityWP' ) !== false ) {
			return str_replace( 'VelocityWP', $this->plugin_name, $text );
		}

		return $translation;
	}

	/**
	 * Hide plugin from plugins list
	 *
	 * @param array $plugins Plugins array.
	 * @return array Modified plugins.
	 */
	public function hide_plugin( $plugins ) {
		$plugin_file = plugin_basename( VelocityWP_FILE );

		if ( isset( $plugins[ $plugin_file ] ) ) {
			unset( $plugins[ $plugin_file ] );
		}

		return $plugins;
	}

	/**
	 * Add custom admin branding
	 */
	public function add_custom_branding() {
		$options = get_option( 'velocitywp_options', array() );

		// Custom logo
		if ( ! empty( $options['white_label_logo'] ) ) {
			?>
			<style>
			#velocitywp-admin-header .velocitywp-logo {
				background-image: url('<?php echo esc_url( $options['white_label_logo'] ); ?>');
			}
			</style>
			<?php
		}

		// Custom colors
		if ( ! empty( $options['white_label_colors'] ) ) {
			$colors = $options['white_label_colors'];
			?>
			<style>
			:root {
				--wpsb-primary-color: <?php echo esc_attr( $colors['primary'] ); ?>;
				--wpsb-secondary-color: <?php echo esc_attr( $colors['secondary'] ); ?>;
			}
			</style>
			<?php
		}

		// Hide branding elements
		if ( ! empty( $options['white_label_hide_footer'] ) ) {
			?>
			<style>
			.velocitywp-footer-branding {
				display: none !important;
			}
			</style>
			<?php
		}
	}

	/**
	 * Get plugin name
	 *
	 * @return string Plugin name.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get plugin description
	 *
	 * @return string Plugin description.
	 */
	public function get_plugin_description() {
		return $this->plugin_description;
	}
}
