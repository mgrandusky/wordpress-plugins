<?php
/**
 * Google Drive Gallery Cache Handler
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GDrive_Cache
 * Handles caching for Google Drive API responses
 */
class GDrive_Cache {

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'gdrive_gallery_';

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed Cached data or false if not found
     */
    public static function get( $key ) {
        $cache_key = self::CACHE_PREFIX . $key;
        return get_transient( $cache_key );
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds (0 = use default)
     * @return bool True on success, false on failure
     */
    public static function set( $key, $data, $expiration = 0 ) {
        $cache_key = self::CACHE_PREFIX . $key;
        
        if ( 0 === $expiration ) {
            $expiration = self::get_cache_duration();
        }

        return set_transient( $cache_key, $data, $expiration );
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public static function delete( $key ) {
        $cache_key = self::CACHE_PREFIX . $key;
        return delete_transient( $cache_key );
    }

    /**
     * Clear all gallery cache
     *
     * @return int Number of cache entries cleared
     */
    public static function clear_all_cache() {
        global $wpdb;

        $prefix = self::CACHE_PREFIX;
        $transient_prefix = '_transient_' . $prefix;
        $timeout_prefix = '_transient_timeout_' . $prefix;

        // Delete all transients and their timeouts
        $count = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like( $transient_prefix ) . '%',
                $wpdb->esc_like( $timeout_prefix ) . '%'
            )
        );

        return $count;
    }

    /**
     * Clear ALL caches (emergency use)
     */
    public static function clear_all() {
        global $wpdb;
        
        // Clear all gdrive transients
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_gdrive%' 
                OR option_name LIKE '_transient_timeout_gdrive%'"
        );
        
        // Clear object cache if available
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
        
        return true;
    }

    /**
     * Get cache duration from settings
     *
     * @return int Cache duration in seconds
     */
    public static function get_cache_duration() {
        $duration = get_option( 'gdrive_gallery_cache_duration', 3600 );
        return absint( $duration );
    }

    /**
     * Clear cache for specific folder
     *
     * @param string $folder_id Folder ID
     * @return bool True on success
     */
    public static function clear_folder_cache( $folder_id ) {
        $folder_id = sanitize_text_field( $folder_id );
        
        // Clear both recursive and flat cache
        self::delete( 'gdrive_files_' . md5( $folder_id . '_recursive' ) );
        self::delete( 'gdrive_files_' . md5( $folder_id . '_flat' ) );

        return true;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public static function get_cache_stats() {
        global $wpdb;

        $prefix = self::CACHE_PREFIX;
        $transient_prefix = '_transient_' . $prefix;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like( $transient_prefix ) . '%'
            )
        );

        $size = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like( $transient_prefix ) . '%'
            )
        );

        return [
            'count' => (int) $count,
            'size' => (int) $size,
            'size_formatted' => size_format( $size ),
        ];
    }
}
