<?php
/**
 * Google Drive API Wrapper
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GDrive_API
 * Wrapper for Google Drive API v3
 */
class GDrive_API {

    /**
     * Google Drive API base URL
     */
    const API_BASE_URL = 'https://www.googleapis.com/drive/v3';

    /**
     * Supported image MIME types
     */
    const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Get access token for API requests
     *
     * @return string|WP_Error Access token or error
     */
    public static function get_access_token() {
        $auth_type = GDrive_Auth::get_auth_type();

        if ( 'oauth' === $auth_type ) {
            // Ensure token is valid
            GDrive_Auth::ensure_valid_token();
            return GDrive_Auth::get_access_token();
        } elseif ( 'service_account' === $auth_type ) {
            return GDrive_Auth::get_service_account_token();
        }

        return new WP_Error( 'no_auth', __( 'Authentication not configured', 'google-drive-gallery' ) );
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|WP_Error Response data or error
     */
    private static function make_request( $endpoint, $params = [] ) {
        $access_token = self::get_access_token();
        
        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $url = self::API_BASE_URL . $endpoint;
        if ( ! empty( $params ) ) {
            $url .= '?' . http_build_query( $params );
        }

        $response = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            $error_message = $body['error']['message'] ?? __( 'API request failed', 'google-drive-gallery' );
            return new WP_Error( 'api_error', $error_message, [ 'code' => $code ] );
        }

        return $body;
    }

    /**
     * Get files in a folder
     *
     * @param string $folder_id Folder ID
     * @param bool $recursive Include subfolders
     * @return array|WP_Error Array of files or error
     */
    public static function get_folder_files( $folder_id, $recursive = false ) {
        // Sanitize folder ID
        $folder_id = sanitize_text_field( $folder_id );

        // Check cache first
        $cache_key = 'gdrive_files_' . md5( $folder_id . '_' . ( $recursive ? 'recursive' : 'flat' ) );
        $cached = GDrive_Cache::get( $cache_key );
        
        if ( false !== $cached ) {
            return $cached;
        }

        $all_files = [];
        
        // Get files in this folder
        $files = self::get_files_in_single_folder( $folder_id );
        
        if ( is_wp_error( $files ) ) {
            return $files;
        }

        $all_files = array_merge( $all_files, $files );

        // If recursive, get subfolders
        if ( $recursive ) {
            $subfolders = self::get_subfolders( $folder_id );
            
            if ( ! is_wp_error( $subfolders ) ) {
                foreach ( $subfolders as $subfolder ) {
                    $subfolder_files = self::get_folder_files( $subfolder['id'], true );
                    if ( ! is_wp_error( $subfolder_files ) ) {
                        $all_files = array_merge( $all_files, $subfolder_files );
                    }
                }
            }
        }

        // Cache the results
        GDrive_Cache::set( $cache_key, $all_files );

        return $all_files;
    }

    /**
     * Get files in a single folder (non-recursive)
     *
     * @param string $folder_id Folder ID
     * @return array|WP_Error Array of files or error
     */
    public static function get_files_in_single_folder( $folder_id ) {
        $mime_types = implode( ' or ', array_map( function( $type ) {
            return "mimeType='{$type}'";
        }, self::IMAGE_MIME_TYPES ) );

        $query = "'{$folder_id}' in parents and ({$mime_types}) and trashed=false";

        $params = [
            'q' => $query,
            'fields' => 'files(id,name,mimeType,thumbnailLink,webContentLink,description,imageMediaMetadata,modifiedTime)',
            'pageSize' => 1000,
        ];

        $files = [];
        $page_token = null;

        do {
            if ( $page_token ) {
                $params['pageToken'] = $page_token;
            }

            $response = self::make_request( '/files', $params );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            if ( isset( $response['files'] ) ) {
                $files = array_merge( $files, $response['files'] );
            }

            $page_token = $response['nextPageToken'] ?? null;
        } while ( $page_token );

        return $files;
    }

    /**
     * Get subfolders in a folder
     *
     * @param string $folder_id Folder ID
     * @return array|WP_Error Array of subfolders or error
     */
    public static function get_subfolders( $folder_id ) {
        $query = "'{$folder_id}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false";

        $params = [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1000,
        ];

        $response = self::make_request( '/files', $params );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $response['files'] ?? [];
    }

    /**
     * Get subfolders with preview data
     *
     * @param string $folder_id Parent folder ID
     * @return array|WP_Error Array of subfolders with metadata
     */
    public static function get_subfolders_with_preview( $folder_id ) {
        // Sanitize folder ID
        $folder_id = sanitize_text_field( $folder_id );
        
        // Check cache first
        $cache_key = 'gdrive_subfolders_preview_' . md5( $folder_id );
        $cached = GDrive_Cache::get( $cache_key );
        
        if ( false !== $cached ) {
            return $cached;
        }
        
        $subfolders = self::get_subfolders( $folder_id );
        
        if ( is_wp_error( $subfolders ) ) {
            return $subfolders;
        }
        
        $result = [];
        
        foreach ( $subfolders as $folder ) {
            // Get images in this subfolder
            $files = self::get_files_in_single_folder( $folder['id'] );
            
            if ( ! is_wp_error( $files ) && ! empty( $files ) ) {
                $result[] = [
                    'id' => $folder['id'],
                    'name' => $folder['name'],
                    'preview_image' => $files[0], // First image
                    'image_count' => count( $files ),
                    'images' => $files, // All images for lightbox
                ];
            }
        }
        
        // Cache the results
        GDrive_Cache::set( $cache_key, $result );
        
        return $result;
    }

    /**
     * Get file thumbnail URL
     *
     * @param array $file File data from API
     * @param string $size Thumbnail size (small, medium, large)
     * @return string Thumbnail URL
     */
    public static function get_thumbnail_url( $file, $size = 'medium' ) {
        if ( isset( $file['id'] ) ) {
            // Use proxy endpoint with size parameter
            $url = home_url( '/gdrive-image/' . $file['id'] . '?size=' . urlencode( $size ) );
            
            // TEMPORARY DEBUG - Remove after testing
            // Uses error_log which is the standard PHP logging function
            // that respects WP_DEBUG_LOG setting
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'GDrive Thumbnail URL generated: ' . $url );
            }
            
            return $url;
        }

        return '';
    }

    /**
     * Get full-size image URL
     *
     * @param array $file File data from API
     * @return string Image URL
     */
    public static function get_image_url( $file ) {
        if ( isset( $file['id'] ) ) {
            return home_url( '/gdrive-image/' . $file['id'] );
        }

        return '';
    }

    /**
     * Test API connection
     *
     * @return array|WP_Error Connection test result
     */
    public static function test_connection() {
        $response = self::make_request( '/about', [
            'fields' => 'user(displayName,emailAddress)',
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Successfully connected to Google Drive', 'google-drive-gallery' ),
            'user' => $response['user'] ?? null,
        ];
    }

    /**
     * Validate folder ID
     *
     * @param string $folder_id Folder ID to validate
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    public static function validate_folder_id( $folder_id ) {
        $folder_id = sanitize_text_field( $folder_id );

        $response = self::make_request( '/files/' . $folder_id, [
            'fields' => 'id,name,mimeType',
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $response['mimeType'] ) && 'application/vnd.google-apps.folder' !== $response['mimeType'] ) {
            return new WP_Error( 'not_folder', __( 'The specified ID is not a folder', 'google-drive-gallery' ) );
        }

        return true;
    }
}
