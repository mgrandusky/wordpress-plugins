<?php
/**
 * JavaScript Delay Class
 *
 * Smart JavaScript delay/defer with user interaction triggers
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_JS_Delay class
 */
class WPSB_JS_Delay {

	/**
	 * Delayed scripts
	 *
	 * @var array
	 */
	private $delayed_scripts = array();

	/**
	 * User interaction events
	 *
	 * @var array
	 */
	private $interaction_events = array( 'mouseover', 'keydown', 'touchstart', 'touchmove', 'wheel' );

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize JS delay
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['js_delay_enable'] ) || is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999 );
		add_filter( 'script_loader_tag', array( $this, 'delay_scripts' ), 10, 3 );
		add_action( 'wp_footer', array( $this, 'output_delay_handler' ), 999 );
	}

	/**
	 * Enqueue delay handler script
	 */
	public function enqueue_scripts() {
		wp_register_script(
			'wpsb-js-delay',
			'',
			array(),
			WPSB_VERSION,
			true
		);
	}

	/**
	 * Delay scripts based on configuration
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string Modified script tag.
	 */
	public function delay_scripts( $tag, $handle, $src ) {
		$options = get_option( 'wpsb_options', array() );

		// Skip if already deferred/async or excluded
		if ( strpos( $tag, 'defer' ) !== false || strpos( $tag, 'async' ) !== false ) {
			return $tag;
		}

		// Get exclusions
		$exclusions = $this->get_exclusions( $options );
		foreach ( $exclusions as $exclusion ) {
			if ( strpos( $handle, $exclusion ) !== false || strpos( $src, $exclusion ) !== false ) {
				return $tag;
			}
		}

		// Check for critical scripts that should not be delayed
		$critical_scripts = $this->get_critical_scripts();
		foreach ( $critical_scripts as $critical ) {
			if ( strpos( $handle, $critical ) !== false || strpos( $src, $critical ) !== false ) {
				return $tag;
			}
		}

		// Delay the script
		$delayed_tag = str_replace( ' src=', ' data-wpsb-delay-src=', $tag );
		$delayed_tag = str_replace( '<script', '<script type="wpsb-delayed"', $delayed_tag );

		$this->delayed_scripts[] = $handle;

		return apply_filters( 'wpsb_delayed_script_tag', $delayed_tag, $tag, $handle, $src );
	}

	/**
	 * Output JavaScript delay handler
	 */
	public function output_delay_handler() {
		if ( empty( $this->delayed_scripts ) ) {
			return;
		}

		$options = get_option( 'wpsb_options', array() );
		$delay_timeout = ! empty( $options['js_delay_timeout'] ) ? intval( $options['js_delay_timeout'] ) : 5000;
		$events = apply_filters( 'wpsb_js_delay_events', $this->interaction_events );

		?>
		<script id="wpsb-js-delay-handler">
		(function() {
			'use strict';
			
			var wpsbDelayedScripts = [];
			var wpsbUserInteracted = false;
			var wpsbTimeout;
			
			// Get all delayed scripts
			var delayedScripts = document.querySelectorAll('script[type="wpsb-delayed"]');
			
			// Function to load all delayed scripts
			function wpsbLoadDelayedScripts() {
				if (wpsbUserInteracted) {
					return;
				}
				
				wpsbUserInteracted = true;
				
				// Clear timeout
				if (wpsbTimeout) {
					clearTimeout(wpsbTimeout);
				}
				
				// Remove event listeners
				wpsbEvents.forEach(function(event) {
					window.removeEventListener(event, wpsbLoadDelayedScripts, {passive: true});
				});
				
				// Load scripts in order
				delayedScripts.forEach(function(script) {
					wpsbLoadScript(script);
				});
			}
			
			// Function to load individual script
			function wpsbLoadScript(script) {
				var newScript = document.createElement('script');
				
				// Copy attributes
				Array.from(script.attributes).forEach(function(attr) {
					if (attr.name === 'type') {
						newScript.type = 'text/javascript';
					} else if (attr.name === 'data-wpsb-delay-src') {
						newScript.src = attr.value;
					} else if (attr.name !== 'data-wpsb-delay-src') {
						newScript.setAttribute(attr.name, attr.value);
					}
				});
				
				// Copy inline content if no src
				if (!newScript.src && script.textContent) {
					newScript.textContent = script.textContent;
				}
				
				// Replace old script with new one
				script.parentNode.replaceChild(newScript, script);
			}
			
			// User interaction events
			var wpsbEvents = <?php echo wp_json_encode( $events ); ?>;
			
			// Add event listeners
			wpsbEvents.forEach(function(event) {
				window.addEventListener(event, wpsbLoadDelayedScripts, {passive: true});
			});
			
			// Fallback timeout
			wpsbTimeout = setTimeout(wpsbLoadDelayedScripts, <?php echo esc_js( $delay_timeout ); ?>);
			
			// Load on page show (back/forward cache)
			window.addEventListener('pageshow', function(event) {
				if (event.persisted) {
					wpsbLoadDelayedScripts();
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Get script exclusions
	 *
	 * @param array $options Plugin options.
	 * @return array Exclusions.
	 */
	private function get_exclusions( $options ) {
		$exclusions = array();

		if ( ! empty( $options['js_delay_exclusions'] ) ) {
			$exclusions = array_map( 'trim', explode( "\n", $options['js_delay_exclusions'] ) );
			$exclusions = array_filter( $exclusions );
		}

		return apply_filters( 'wpsb_js_delay_exclusions', $exclusions );
	}

	/**
	 * Get critical scripts that should never be delayed
	 *
	 * @return array Critical script identifiers.
	 */
	private function get_critical_scripts() {
		$critical = array(
			'jquery-core',
			'jquery-migrate',
			'wp-polyfill',
			'regenerator-runtime',
			'wp-i18n',
		);

		return apply_filters( 'wpsb_js_delay_critical_scripts', $critical );
	}

	/**
	 * Check if delay is enabled for current page
	 *
	 * @return bool Whether delay is enabled.
	 */
	private function is_delay_enabled() {
		// Skip for admin, login, and customizer
		if ( is_admin() || is_user_logged_in() || is_customize_preview() ) {
			return false;
		}

		return apply_filters( 'wpsb_js_delay_enabled', true );
	}
}
