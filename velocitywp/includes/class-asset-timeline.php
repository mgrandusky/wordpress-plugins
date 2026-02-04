<?php
/**
 * Asset Timeline Class
 *
 * Resource loading waterfall and performance timeline
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Asset_Timeline class
 */
class VelocityWP_Asset_Timeline {

	/**
	 * Assets data
	 *
	 * @var array
	 */
	private $assets = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_get_asset_timeline', array( $this, 'ajax_get_timeline' ) );
	}

	/**
	 * Initialize asset timeline
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['asset_timeline'] ) ) {
			return;
		}

		// Capture asset information
		add_action( 'wp_enqueue_scripts', array( $this, 'capture_assets' ), 9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'capture_assets' ), 9999 );

		// Add performance timing script
		if ( ! empty( $options['show_timeline'] ) && current_user_can( 'manage_options' ) ) {
			add_action( 'wp_footer', array( $this, 'output_timing_script' ), 9999 );
			add_action( 'admin_footer', array( $this, 'output_timing_script' ), 9999 );
		}
	}

	/**
	 * Capture enqueued assets
	 */
	public function capture_assets() {
		global $wp_scripts, $wp_styles;

		// Capture scripts
		if ( ! empty( $wp_scripts->queue ) ) {
			foreach ( $wp_scripts->queue as $handle ) {
				if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
					continue;
				}

				$script = $wp_scripts->registered[ $handle ];
				
				$this->assets['scripts'][ $handle ] = array(
					'handle'   => $handle,
					'src'      => $script->src,
					'deps'     => $script->deps,
					'ver'      => $script->ver,
					'in_footer' => ! empty( $script->extra['group'] ),
					'size'     => $this->get_asset_size( $script->src ),
				);
			}
		}

		// Capture styles
		if ( ! empty( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
					continue;
				}

				$style = $wp_styles->registered[ $handle ];
				
				$this->assets['styles'][ $handle ] = array(
					'handle' => $handle,
					'src'    => $style->src,
					'deps'   => $style->deps,
					'ver'    => $style->ver,
					'media'  => $style->args,
					'size'   => $this->get_asset_size( $style->src ),
				);
			}
		}
	}

	/**
	 * Get asset file size
	 *
	 * @param string $src Asset URL.
	 * @return int File size in bytes.
	 */
	private function get_asset_size( $src ) {
		if ( empty( $src ) || strpos( $src, '//' ) === 0 ) {
			return 0;
		}

		// Convert URL to file path
		$file_path = $this->url_to_path( $src );

		if ( $file_path && file_exists( $file_path ) ) {
			return filesize( $file_path );
		}

		return 0;
	}

	/**
	 * Convert URL to file path
	 *
	 * @param string $url Asset URL.
	 * @return string|false File path or false.
	 */
	private function url_to_path( $url ) {
		// Remove query string
		$url = strtok( $url, '?' );

		// Get WordPress uploads directory
		$upload_dir = wp_upload_dir();

		// Try different base URLs
		$replacements = array(
			content_url()              => WP_CONTENT_DIR,
			includes_url()             => ABSPATH . WPINC,
			$upload_dir['baseurl']     => $upload_dir['basedir'],
			get_stylesheet_directory_uri() => get_stylesheet_directory(),
			get_template_directory_uri()   => get_template_directory(),
		);

		foreach ( $replacements as $base_url => $base_path ) {
			if ( strpos( $url, $base_url ) === 0 ) {
				return str_replace( $base_url, $base_path, $url );
			}
		}

		return false;
	}

	/**
	 * Output performance timing script
	 */
	public function output_timing_script() {
		?>
		<script id="velocitywp-asset-timeline">
		(function() {
			if (!window.performance || !window.performance.getEntriesByType) {
				return;
			}

			// Wait for page to fully load
			window.addEventListener('load', function() {
				setTimeout(function() {
					var resources = window.performance.getEntriesByType('resource');
					var navigation = window.performance.getEntriesByType('navigation')[0];
					
					var timeline = {
						navigation: {
							dns: navigation ? navigation.domainLookupEnd - navigation.domainLookupStart : 0,
							tcp: navigation ? navigation.connectEnd - navigation.connectStart : 0,
							request: navigation ? navigation.responseStart - navigation.requestStart : 0,
							response: navigation ? navigation.responseEnd - navigation.responseStart : 0,
							dom: navigation ? navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart : 0,
							load: navigation ? navigation.loadEventEnd - navigation.loadEventStart : 0,
							total: navigation ? navigation.loadEventEnd - navigation.fetchStart : 0
						},
						resources: []
					};

					// Group resources by type
					var resourceTypes = {
						script: [],
						css: [],
						img: [],
						font: [],
						other: []
					};

					resources.forEach(function(resource) {
						var type = 'other';
						var name = resource.name.split('?')[0];
						
						if (resource.initiatorType === 'script' || name.match(/\.js$/)) {
							type = 'script';
						} else if (resource.initiatorType === 'link' || name.match(/\.css$/)) {
							type = 'css';
						} else if (resource.initiatorType === 'img' || name.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) {
							type = 'img';
						} else if (name.match(/\.(woff|woff2|ttf|otf|eot)$/)) {
							type = 'font';
						}

						var data = {
							name: resource.name,
							type: type,
							duration: resource.duration,
							size: resource.transferSize || resource.encodedBodySize || 0,
							startTime: resource.startTime,
							dns: resource.domainLookupEnd - resource.domainLookupStart,
							tcp: resource.connectEnd - resource.connectStart,
							request: resource.responseStart - resource.requestStart,
							response: resource.responseEnd - resource.responseStart
						};

						resourceTypes[type].push(data);
						timeline.resources.push(data);
					});

					// Calculate statistics
					var stats = {
						totalResources: resources.length,
						totalSize: timeline.resources.reduce(function(sum, r) { return sum + r.size; }, 0),
						totalDuration: navigation ? navigation.loadEventEnd : 0,
						byType: {}
					};

					for (var type in resourceTypes) {
						stats.byType[type] = {
							count: resourceTypes[type].length,
							size: resourceTypes[type].reduce(function(sum, r) { return sum + r.size; }, 0),
							duration: resourceTypes[type].reduce(function(sum, r) { return sum + r.duration; }, 0)
						};
					}

					timeline.stats = stats;

					// Store in sessionStorage for admin panel
					try {
						sessionStorage.setItem('velocitywp_asset_timeline', JSON.stringify(timeline));
					} catch(e) {}

					// Log to console for debugging
					console.log('WPSB Asset Timeline:', timeline);

					// Send to admin if enabled
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
					if (window.velocitywpAdminAjax) {
						velocitywpAdminAjax.saveTimeline(timeline);
					}
					<?php endif; ?>
				}, 1000);
			});
		})();
		</script>
		<?php
	}

	/**
	 * Get asset timeline data
	 *
	 * @return array Timeline data.
	 */
	public function get_timeline() {
		return array(
			'assets' => $this->assets,
			'stats'  => $this->calculate_stats(),
		);
	}

	/**
	 * Calculate asset statistics
	 *
	 * @return array Statistics.
	 */
	private function calculate_stats() {
		$stats = array(
			'total_scripts' => 0,
			'total_styles'  => 0,
			'total_size'    => 0,
			'header_scripts' => 0,
			'footer_scripts' => 0,
		);

		if ( ! empty( $this->assets['scripts'] ) ) {
			$stats['total_scripts'] = count( $this->assets['scripts'] );
			
			foreach ( $this->assets['scripts'] as $script ) {
				$stats['total_size'] += $script['size'];
				
				if ( $script['in_footer'] ) {
					$stats['footer_scripts']++;
				} else {
					$stats['header_scripts']++;
				}
			}
		}

		if ( ! empty( $this->assets['styles'] ) ) {
			$stats['total_styles'] = count( $this->assets['styles'] );
			
			foreach ( $this->assets['styles'] as $style ) {
				$stats['total_size'] += $style['size'];
			}
		}

		return $stats;
	}

	/**
	 * Analyze dependencies
	 *
	 * @return array Dependency chain analysis.
	 */
	public function analyze_dependencies() {
		$analysis = array(
			'longest_chain' => 0,
			'chains' => array(),
			'bottlenecks' => array(),
		);

		// Analyze script dependencies
		if ( ! empty( $this->assets['scripts'] ) ) {
			foreach ( $this->assets['scripts'] as $handle => $script ) {
				$chain = $this->get_dependency_chain( $handle, $this->assets['scripts'] );
				$chain_length = count( $chain );
				
				if ( $chain_length > $analysis['longest_chain'] ) {
					$analysis['longest_chain'] = $chain_length;
				}

				if ( $chain_length > 3 ) {
					$analysis['chains'][ $handle ] = $chain;
				}

				// Detect bottlenecks (scripts with many dependents)
				$dependents = $this->get_dependents( $handle, $this->assets['scripts'] );
				if ( count( $dependents ) > 3 ) {
					$analysis['bottlenecks'][ $handle ] = $dependents;
				}
			}
		}

		return $analysis;
	}

	/**
	 * Get dependency chain for a handle
	 *
	 * @param string $handle Handle to trace.
	 * @param array  $assets Assets array.
	 * @return array Dependency chain.
	 */
	private function get_dependency_chain( $handle, $assets ) {
		$chain = array( $handle );

		if ( empty( $assets[ $handle ]['deps'] ) ) {
			return $chain;
		}

		foreach ( $assets[ $handle ]['deps'] as $dep ) {
			if ( isset( $assets[ $dep ] ) ) {
				$dep_chain = $this->get_dependency_chain( $dep, $assets );
				$chain = array_merge( $dep_chain, $chain );
			}
		}

		return array_unique( $chain );
	}

	/**
	 * Get dependents of a handle
	 *
	 * @param string $handle Handle to check.
	 * @param array  $assets Assets array.
	 * @return array Dependent handles.
	 */
	private function get_dependents( $handle, $assets ) {
		$dependents = array();

		foreach ( $assets as $asset_handle => $asset ) {
			if ( in_array( $handle, $asset['deps'], true ) ) {
				$dependents[] = $asset_handle;
			}
		}

		return $dependents;
	}

	/**
	 * AJAX handler to get timeline
	 */
	public function ajax_get_timeline() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$this->capture_assets();
		$timeline = $this->get_timeline();
		$analysis = $this->analyze_dependencies();

		wp_send_json_success( array(
			'timeline' => $timeline,
			'analysis' => $analysis,
		) );
	}
}
