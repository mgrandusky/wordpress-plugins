<?php
/**
 * Cache System Class
 *
 * Handles page caching with file-based storage
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Cache class
 */
class VelocityWP_Cache {

	/**
	 * Cache directory path
	 *
	 * @var string
	 */
	private $cache_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->cache_dir = VELOCITYWP_CACHE_DIR;

		// Register hooks
		add_action( 'template_redirect', array( $this, 'maybe_serve_cache' ), 1 );
		add_action( 'shutdown', array( $this, 'maybe_cache_page' ) );
		add_action( 'save_post', array( $this, 'clear_post_cache' ) );
		add_action( 'delete_post', array( $this, 'clear_post_cache' ) );
		add_action( 'switch_theme', array( $this, 'clear_all_cache' ) );
		add_action( 'activated_plugin', array( $this, 'clear_all_cache' ) );
		add_action( 'deactivated_plugin', array( $this, 'clear_all_cache' ) );
	}

	/**
	 * Check if caching is enabled
	 *
	 * @return bool
	 */
	private function is_cache_enabled() {
		$options = get_option( 'velocitywp_options', array() );
		return ! empty( $options['cache_enabled'] );
	}

	/**
	 * Check if current page should be cached
	 *
	 * @return bool
	 */
	private function should_cache_page() {
		// Don't cache if disabled
		if ( ! $this->is_cache_enabled() ) {
			return false;
		}

		// Don't cache if user is logged in
		if ( is_user_logged_in() ) {
			return false;
		}

		// Don't cache POST requests
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return false;
		}

		// Don't cache if query string exists (except some allowed)
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$allowed_params = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );
			parse_str( $_SERVER['QUERY_STRING'], $params );
			foreach ( array_keys( $params ) as $param ) {
				if ( ! in_array( $param, $allowed_params, true ) ) {
					return false;
				}
			}
		}

		// Don't cache search, 404, feed, admin, ajax
		if ( is_search() || is_404() || is_feed() || is_admin() || wp_doing_ajax() ) {
			return false;
		}

		// Don't cache WooCommerce pages
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return false;
		}

		// Check excluded URLs
		$options = get_option( 'velocitywp_options', array() );
		if ( ! empty( $options['cache_exclude_urls'] ) ) {
			$excluded = explode( "\n", $options['cache_exclude_urls'] );
			$current_url = $_SERVER['REQUEST_URI'];
			foreach ( $excluded as $pattern ) {
				$pattern = trim( $pattern );
				if ( ! empty( $pattern ) && strpos( $current_url, $pattern ) !== false ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get cache file path for current request
	 *
	 * @return string
	 */
	private function get_cache_file_path() {
		$options = get_option( 'velocitywp_options', array() );
		$uri = $_SERVER['REQUEST_URI'];
		$host = $_SERVER['HTTP_HOST'];

		// Create hash from URI and host
		$hash = md5( $host . $uri );

		// Check if mobile cache is enabled
		$suffix = '';
		if ( ! empty( $options['mobile_cache'] ) && wp_is_mobile() ) {
			$suffix = '-mobile';
		}

		// Create subdirectory based on first 2 characters of hash
		$subdir = substr( $hash, 0, 2 );
		$cache_dir = $this->cache_dir . $subdir . '/';

		if ( ! file_exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		return $cache_dir . $hash . $suffix . '.html';
	}

	/**
	 * Serve cached page if available
	 */
	public function maybe_serve_cache() {
		if ( ! $this->should_cache_page() ) {
			return;
		}

		$cache_file = $this->get_cache_file_path();

		if ( ! file_exists( $cache_file ) ) {
			return;
		}

		// Check if cache is expired
		$options = get_option( 'velocitywp_options', array() );
		$cache_lifespan = ! empty( $options['cache_lifespan'] ) ? intval( $options['cache_lifespan'] ) : 36000;

		if ( ( time() - filemtime( $cache_file ) ) > $cache_lifespan ) {
			unlink( $cache_file );
			return;
		}

		// Serve cached content
		$cached_content = file_get_contents( $cache_file );
		if ( $cached_content ) {
			header( 'X-WP-Speed-Booster-Cache: HIT' );
			echo $cached_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}

	/**
	 * Cache current page
	 */
	public function maybe_cache_page() {
		if ( ! $this->should_cache_page() ) {
			return;
		}

		// Get output buffer
		$content = '';
		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			$content = ob_get_contents();
			if ( $content ) {
				break;
			}
		}

		if ( empty( $content ) || strlen( $content ) < 255 ) {
			return;
		}

		// Don't cache if there's a 404 or redirect
		if ( is_404() || ( function_exists( 'http_response_code' ) && http_response_code() !== 200 ) ) {
			return;
		}

		// Save cache file
		$cache_file = $this->get_cache_file_path();

		// Add cache signature
		$signature = "\n<!-- Cached by VelocityWP on " . gmdate( 'Y-m-d H:i:s' ) . " UTC -->";
		$content .= $signature;

		// Hook before saving
		do_action( 'velocitywp_before_cache_save', $cache_file, $content );

		// Save file
		file_put_contents( $cache_file, $content, LOCK_EX );

		// Hook after saving
		do_action( 'velocitywp_after_cache_save', $cache_file, $content );
	}

	/**
	 * Clear cache for a specific post
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_post_cache( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Clear homepage cache
		$this->clear_url_cache( home_url( '/' ) );

		// Clear post cache
		$post_url = get_permalink( $post_id );
		if ( $post_url ) {
			$this->clear_url_cache( $post_url );
		}

		// Clear archives
		$post = get_post( $post_id );
		if ( $post ) {
			// Category archives
			$categories = get_the_category( $post_id );
			foreach ( $categories as $category ) {
				$this->clear_url_cache( get_category_link( $category->term_id ) );
			}

			// Tag archives
			$tags = get_the_tags( $post_id );
			if ( $tags ) {
				foreach ( $tags as $tag ) {
					$this->clear_url_cache( get_tag_link( $tag->term_id ) );
				}
			}
		}
	}

	/**
	 * Clear cache for specific URL
	 *
	 * @param string $url URL to clear cache for.
	 */
	public function clear_url_cache( $url ) {
		$parsed = wp_parse_url( $url );
		$uri = $parsed['path'];
		$host = $parsed['host'];
		$hash = md5( $host . $uri );
		$subdir = substr( $hash, 0, 2 );

		// Clear desktop version
		$cache_file = $this->cache_dir . $subdir . '/' . $hash . '.html';
		if ( file_exists( $cache_file ) ) {
			unlink( $cache_file );
		}

		// Clear mobile version
		$cache_file_mobile = $this->cache_dir . $subdir . '/' . $hash . '-mobile.html';
		if ( file_exists( $cache_file_mobile ) ) {
			unlink( $cache_file_mobile );
		}
	}

	/**
	 * Clear all cache
	 */
	public function clear_all_cache() {
		do_action( 'velocitywp_before_cache_clear' );

		if ( ! file_exists( $this->cache_dir ) ) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->cache_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getRealPath() );
			} else {
				unlink( $file->getRealPath() );
			}
		}

		do_action( 'velocitywp_after_cache_clear' );
	}

	/**
	 * Get cache statistics
	 *
	 * @return array
	 */
	public function get_cache_stats() {
		$stats = array(
			'files' => 0,
			'size'  => 0,
		);

		if ( ! file_exists( $this->cache_dir ) ) {
			return $stats;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->cache_dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $files as $file ) {
			if ( $file->isFile() && pathinfo( $file, PATHINFO_EXTENSION ) === 'html' ) {
				$stats['files']++;
				$stats['size'] += $file->getSize();
			}
		}

		return $stats;
	}
}
