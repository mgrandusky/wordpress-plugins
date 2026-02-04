<?php
/**
 * Multisite Support Class
 *
 * WordPress Multisite network support and management
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Multisite class
 */
class WPSB_Multisite {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_multisite() ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'network_admin_menu', array( $this, 'add_network_admin_menu' ) );
		add_action( 'wp_ajax_wpsb_network_purge_cache', array( $this, 'ajax_network_purge_cache' ) );
	}

	/**
	 * Initialize multisite support
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['multisite_support'] ) ) {
			return;
		}

		// Network-wide cache management
		if ( ! empty( $options['network_cache'] ) ) {
			add_action( 'wpsb_clear_cache', array( $this, 'clear_network_cache' ) );
		}

		// Sync settings across network
		if ( ! empty( $options['sync_network_settings'] ) ) {
			add_action( 'update_option_wpsb_options', array( $this, 'sync_to_network' ), 10, 2 );
		}
	}

	/**
	 * Add network admin menu
	 */
	public function add_network_admin_menu() {
		add_menu_page(
			__( 'WP Speed Booster Network', 'wp-speed-booster' ),
			__( 'Speed Booster', 'wp-speed-booster' ),
			'manage_network_options',
			'wpsb-network',
			array( $this, 'render_network_page' ),
			'dashicons-performance',
			80
		);
	}

	/**
	 * Render network admin page
	 */
	public function render_network_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Speed Booster - Network Settings', 'wp-speed-booster' ); ?></h1>
			
			<div class="wpsb-network-dashboard">
				<h2><?php esc_html_e( 'Network Overview', 'wp-speed-booster' ); ?></h2>
				<?php $this->display_network_stats(); ?>

				<h2><?php esc_html_e( 'Network Actions', 'wp-speed-booster' ); ?></h2>
				<button type="button" class="button button-primary" id="wpsb-network-purge-cache">
					<?php esc_html_e( 'Purge All Site Caches', 'wp-speed-booster' ); ?>
				</button>

				<h2><?php esc_html_e( 'Site Status', 'wp-speed-booster' ); ?></h2>
				<?php $this->display_sites_table(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display network statistics
	 */
	private function display_network_stats() {
		$sites = get_sites( array( 'number' => 1000 ) );
		$total_cache_size = 0;
		$active_sites = 0;

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			
			$options = get_option( 'wpsb_options', array() );
			if ( ! empty( $options['cache_enable'] ) ) {
				$active_sites++;
			}

			$cache_dir = WP_CONTENT_DIR . '/cache/wpsb/';
			if ( is_dir( $cache_dir ) ) {
				$total_cache_size += $this->get_directory_size( $cache_dir );
			}

			restore_current_blog();
		}

		?>
		<table class="widefat">
			<tr>
				<th><?php esc_html_e( 'Total Sites', 'wp-speed-booster' ); ?></th>
				<td><?php echo esc_html( count( $sites ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Sites with Cache Enabled', 'wp-speed-booster' ); ?></th>
				<td><?php echo esc_html( $active_sites ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Total Cache Size', 'wp-speed-booster' ); ?></th>
				<td><?php echo size_format( $total_cache_size ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Display sites table
	 */
	private function display_sites_table() {
		$sites = get_sites( array( 'number' => 100 ) );

		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Site', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Cache Status', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Cache Size', 'wp-speed-booster' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-speed-booster' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sites as $site ) : ?>
				<?php
					switch_to_blog( $site->blog_id );
					$options = get_option( 'wpsb_options', array() );
					$cache_enabled = ! empty( $options['cache_enable'] );
					
					$cache_dir = WP_CONTENT_DIR . '/cache/wpsb/';
					$cache_size = is_dir( $cache_dir ) ? $this->get_directory_size( $cache_dir ) : 0;
					
					restore_current_blog();
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( get_blog_option( $site->blog_id, 'blogname' ) ); ?></strong><br>
						<small><?php echo esc_url( get_site_url( $site->blog_id ) ); ?></small>
					</td>
					<td>
						<span class="wpsb-status <?php echo $cache_enabled ? 'enabled' : 'disabled'; ?>">
							<?php echo $cache_enabled ? esc_html__( 'Enabled', 'wp-speed-booster' ) : esc_html__( 'Disabled', 'wp-speed-booster' ); ?>
						</span>
					</td>
					<td><?php echo size_format( $cache_size ); ?></td>
					<td>
						<button type="button" class="button button-small wpsb-purge-site-cache" data-site-id="<?php echo esc_attr( $site->blog_id ); ?>">
							<?php esc_html_e( 'Purge Cache', 'wp-speed-booster' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Clear cache for all network sites
	 */
	public function clear_network_cache() {
		$sites = get_sites( array( 'number' => 1000 ) );

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			do_action( 'wpsb_clear_cache' );
			restore_current_blog();
		}
	}

	/**
	 * Sync settings to all network sites
	 *
	 * @param mixed $old_value Old option value.
	 * @param mixed $value     New option value.
	 */
	public function sync_to_network( $old_value, $value ) {
		if ( ! is_main_site() ) {
			return;
		}

		$sites = get_sites( array( 'number' => 1000 ) );

		foreach ( $sites as $site ) {
			if ( $site->blog_id === get_main_site_id() ) {
				continue;
			}

			switch_to_blog( $site->blog_id );
			update_option( 'wpsb_options', $value );
			restore_current_blog();
		}
	}

	/**
	 * Get directory size
	 *
	 * @param string $directory Directory path.
	 * @return int Size in bytes.
	 */
	private function get_directory_size( $directory ) {
		$size = 0;

		if ( ! is_dir( $directory ) ) {
			return 0;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $files as $file ) {
			if ( $file->isFile() ) {
				$size += $file->getSize();
			}
		}

		return $size;
	}

	/**
	 * AJAX handler to purge network cache
	 */
	public function ajax_network_purge_cache() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$this->clear_network_cache();

		wp_send_json_success( array( 'message' => __( 'Network cache cleared', 'wp-speed-booster' ) ) );
	}
}
