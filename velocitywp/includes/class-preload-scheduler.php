<?php
/**
 * Preload Scheduler Class
 *
 * Sitemap-based cache warming and preloading scheduler
 *
 * @package WP_Speed_Booster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPSB_Preload_Scheduler class
 */
class WPSB_Preload_Scheduler {

	/**
	 * Cron hook name
	 *
	 * @var string
	 */
	private $cron_hook = 'wpsb_preload_cache';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( $this->cron_hook, array( $this, 'run_preload' ) );
		add_action( 'wp_ajax_wpsb_start_preload', array( $this, 'ajax_start_preload' ) );
		add_action( 'wp_ajax_wpsb_stop_preload', array( $this, 'ajax_stop_preload' ) );
		add_action( 'wp_ajax_wpsb_preload_status', array( $this, 'ajax_preload_status' ) );
	}

	/**
	 * Initialize preload scheduler
	 */
	public function init() {
		$options = get_option( 'wpsb_options', array() );

		if ( empty( $options['preload_scheduler'] ) ) {
			// Remove scheduled event if disabled
			$this->unschedule_preload();
			return;
		}

		// Schedule preload
		if ( ! empty( $options['preload_schedule'] ) ) {
			$this->schedule_preload( $options );
		}
	}

	/**
	 * Schedule preload task
	 *
	 * @param array $options Plugin options.
	 */
	private function schedule_preload( $options ) {
		$recurrence = ! empty( $options['preload_frequency'] ) ? $options['preload_frequency'] : 'daily';

		if ( ! wp_next_scheduled( $this->cron_hook ) ) {
			wp_schedule_event( time(), $recurrence, $this->cron_hook );
		}
	}

	/**
	 * Unschedule preload task
	 */
	private function unschedule_preload() {
		$timestamp = wp_next_scheduled( $this->cron_hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_hook );
		}
	}

	/**
	 * Run preload process
	 */
	public function run_preload() {
		// Set high timeout
		set_time_limit( 0 );

		$urls = $this->get_urls_to_preload();
		
		if ( empty( $urls ) ) {
			return;
		}

		$options = get_option( 'wpsb_options', array() );
		$batch_size = ! empty( $options['preload_batch_size'] ) ? intval( $options['preload_batch_size'] ) : 10;
		$delay = ! empty( $options['preload_delay'] ) ? intval( $options['preload_delay'] ) : 500;

		// Get current progress
		$progress = get_option( 'wpsb_preload_progress', array(
			'total'     => 0,
			'processed' => 0,
			'running'   => false,
		) );

		if ( $progress['running'] ) {
			return; // Already running
		}

		// Start preload
		$progress['total'] = count( $urls );
		$progress['processed'] = 0;
		$progress['running'] = true;
		$progress['started'] = current_time( 'mysql' );
		update_option( 'wpsb_preload_progress', $progress, false );

		// Process URLs in batches
		foreach ( array_chunk( $urls, $batch_size ) as $batch ) {
			$this->preload_batch( $batch );
			
			$progress['processed'] += count( $batch );
			update_option( 'wpsb_preload_progress', $progress, false );

			// Delay between batches
			usleep( $delay * 1000 );
		}

		// Complete preload
		$progress['running'] = false;
		$progress['completed'] = current_time( 'mysql' );
		update_option( 'wpsb_preload_progress', $progress, false );

		do_action( 'wpsb_preload_completed', $urls );
	}

	/**
	 * Preload a batch of URLs
	 *
	 * @param array $urls URLs to preload.
	 */
	private function preload_batch( $urls ) {
		$user_agent = 'WP Speed Booster/Cache Preloader';

		foreach ( $urls as $url ) {
			wp_remote_get( $url, array(
				'timeout'    => 30,
				'user-agent' => $user_agent,
				'sslverify'  => false,
				'blocking'   => false, // Non-blocking for faster execution
			) );
		}
	}

	/**
	 * Get URLs to preload
	 *
	 * @return array URLs.
	 */
	private function get_urls_to_preload() {
		$options = get_option( 'wpsb_options', array() );
		$urls = array();

		// Get from sitemaps
		if ( ! empty( $options['preload_from_sitemap'] ) ) {
			$urls = array_merge( $urls, $this->get_urls_from_sitemap() );
		}

		// Get from database
		if ( ! empty( $options['preload_posts'] ) ) {
			$urls = array_merge( $urls, $this->get_post_urls() );
		}

		if ( ! empty( $options['preload_pages'] ) ) {
			$urls = array_merge( $urls, $this->get_page_urls() );
		}

		if ( ! empty( $options['preload_archives'] ) ) {
			$urls = array_merge( $urls, $this->get_archive_urls() );
		}

		// Add home page
		$urls[] = home_url( '/' );

		// Add custom URLs
		if ( ! empty( $options['preload_custom_urls'] ) ) {
			$custom = array_map( 'trim', explode( "\n", $options['preload_custom_urls'] ) );
			$urls = array_merge( $urls, array_filter( $custom ) );
		}

		return array_unique( apply_filters( 'wpsb_preload_urls', $urls ) );
	}

	/**
	 * Get URLs from sitemap
	 *
	 * @return array URLs.
	 */
	private function get_urls_from_sitemap() {
		$urls = array();
		$options = get_option( 'wpsb_options', array() );

		$sitemap_url = ! empty( $options['preload_sitemap_url'] ) ? $options['preload_sitemap_url'] : home_url( '/sitemap.xml' );

		$response = wp_remote_get( $sitemap_url, array(
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $urls;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return $urls;
		}

		// Parse XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body );

		if ( ! $xml ) {
			return $urls;
		}

		// Check if it's a sitemap index
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				$sub_urls = $this->parse_sitemap_url( (string) $sitemap->loc );
				$urls = array_merge( $urls, $sub_urls );
			}
		} else {
			// Regular sitemap
			$urls = $this->parse_sitemap_xml( $xml );
		}

		return $urls;
	}

	/**
	 * Parse sitemap URL
	 *
	 * @param string $url Sitemap URL.
	 * @return array URLs.
	 */
	private function parse_sitemap_url( $url ) {
		$response = wp_remote_get( $url, array(
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$xml = simplexml_load_string( $body );

		if ( ! $xml ) {
			return array();
		}

		return $this->parse_sitemap_xml( $xml );
	}

	/**
	 * Parse sitemap XML
	 *
	 * @param SimpleXMLElement $xml XML object.
	 * @return array URLs.
	 */
	private function parse_sitemap_xml( $xml ) {
		$urls = array();

		if ( isset( $xml->url ) ) {
			foreach ( $xml->url as $url ) {
				$urls[] = (string) $url->loc;
			}
		}

		return $urls;
	}

	/**
	 * Get post URLs
	 *
	 * @return array URLs.
	 */
	private function get_post_urls() {
		$urls = array();
		$options = get_option( 'wpsb_options', array() );

		$limit = ! empty( $options['preload_post_limit'] ) ? intval( $options['preload_post_limit'] ) : 100;

		$posts = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		) );

		foreach ( $posts as $post ) {
			$urls[] = get_permalink( $post );
		}

		return $urls;
	}

	/**
	 * Get page URLs
	 *
	 * @return array URLs.
	 */
	private function get_page_urls() {
		$urls = array();

		$pages = get_pages( array(
			'post_status' => 'publish',
		) );

		foreach ( $pages as $page ) {
			$urls[] = get_permalink( $page );
		}

		return $urls;
	}

	/**
	 * Get archive URLs
	 *
	 * @return array URLs.
	 */
	private function get_archive_urls() {
		$urls = array();

		// Categories
		$categories = get_categories();
		foreach ( $categories as $category ) {
			$urls[] = get_category_link( $category );
		}

		// Tags
		$tags = get_tags();
		foreach ( $tags as $tag ) {
			$urls[] = get_tag_link( $tag );
		}

		// Custom taxonomies
		$taxonomies = get_taxonomies( array(
			'public'   => true,
			'_builtin' => false,
		) );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			) );

			foreach ( $terms as $term ) {
				$urls[] = get_term_link( $term );
			}
		}

		return $urls;
	}

	/**
	 * Get preload progress
	 *
	 * @return array Progress data.
	 */
	public function get_progress() {
		return get_option( 'wpsb_preload_progress', array(
			'total'     => 0,
			'processed' => 0,
			'running'   => false,
		) );
	}

	/**
	 * AJAX handler to start preload
	 */
	public function ajax_start_preload() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		// Run in background
		wp_schedule_single_event( time(), $this->cron_hook );
		spawn_cron();

		wp_send_json_success( array( 'message' => __( 'Preload started', 'wp-speed-booster' ) ) );
	}

	/**
	 * AJAX handler to stop preload
	 */
	public function ajax_stop_preload() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$progress = $this->get_progress();
		$progress['running'] = false;
		update_option( 'wpsb_preload_progress', $progress, false );

		wp_send_json_success( array( 'message' => __( 'Preload stopped', 'wp-speed-booster' ) ) );
	}

	/**
	 * AJAX handler to get preload status
	 */
	public function ajax_preload_status() {
		check_ajax_referer( 'wpsb-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-speed-booster' ) ) );
		}

		$progress = $this->get_progress();

		wp_send_json_success( array( 'progress' => $progress ) );
	}
}
