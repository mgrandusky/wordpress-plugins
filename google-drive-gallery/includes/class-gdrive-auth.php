<?php
/**
 * Google Drive Authentication Handler
 *
 * @package Google_Drive_Gallery
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GDrive_Auth
 * Handles OAuth 2.0 and Service Account authentication
 */
class GDrive_Auth {

    /**
     * OAuth 2.0 authorization URL
     */
    const OAUTH_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * OAuth 2.0 token URL
     */
    const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * OAuth 2.0 scopes
     */
    const OAUTH_SCOPES = [
        'https://www.googleapis.com/auth/drive.readonly',
        'https://www.googleapis.com/auth/drive.metadata.readonly',
    ];

    /**
     * Get authentication type
     *
     * @return string 'oauth' or 'service_account'
     */
    public static function get_auth_type() {
        return get_option( 'gdrive_gallery_auth_type', 'oauth' );
    }

    /**
     * Get OAuth client ID
     *
     * @return string
     */
    public static function get_client_id() {
        return get_option( 'gdrive_gallery_oauth_client_id', '' );
    }

    /**
     * Get OAuth client secret
     *
     * @return string
     */
    public static function get_client_secret() {
        return get_option( 'gdrive_gallery_oauth_client_secret', '' );
    }

    /**
     * Get OAuth access token
     *
     * @return string
     */
    public static function get_access_token() {
        return get_option( 'gdrive_gallery_oauth_access_token', '' );
    }

    /**
     * Get OAuth refresh token
     *
     * @return string
     */
    public static function get_refresh_token() {
        return get_option( 'gdrive_gallery_oauth_refresh_token', '' );
    }

    /**
     * Get service account credentials
     *
     * @return array|false
     */
    public static function get_service_account_credentials() {
        $json = get_option( 'gdrive_gallery_service_account_json', '' );
        if ( empty( $json ) ) {
            return false;
        }
        return json_decode( $json, true );
    }

    /**
     * Check if authentication is configured
     *
     * @return bool
     */
    public static function is_authenticated() {
        $auth_type = self::get_auth_type();
        
        if ( 'oauth' === $auth_type ) {
            return ! empty( self::get_access_token() );
        } elseif ( 'service_account' === $auth_type ) {
            $credentials = self::get_service_account_credentials();
            return ! empty( $credentials ) && isset( $credentials['private_key'] );
        }
        
        return false;
    }

    /**
     * Get OAuth authorization URL
     *
     * @return string
     */
    public static function get_authorization_url() {
        $client_id = self::get_client_id();
        $redirect_uri = admin_url( 'admin.php?page=gdrive-gallery-settings&oauth_callback=1' );
        $state = wp_create_nonce( 'gdrive-oauth-state' );

        $params = [
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => implode( ' ', self::OAUTH_SCOPES ),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::OAUTH_AUTH_URL . '?' . http_build_query( $params );
    }

    /**
     * Exchange authorization code for tokens
     *
     * @param string $code Authorization code
     * @return array|WP_Error Token data or error
     */
    public static function exchange_code_for_tokens( $code ) {
        $client_id = self::get_client_id();
        $client_secret = self::get_client_secret();
        $redirect_uri = admin_url( 'admin.php?page=gdrive-gallery-settings&oauth_callback=1' );

        $response = wp_remote_post( self::OAUTH_TOKEN_URL, [
            'body' => [
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'oauth_error', $body['error_description'] ?? $body['error'] );
        }

        // Store tokens
        if ( isset( $body['access_token'] ) ) {
            update_option( 'gdrive_gallery_oauth_access_token', $body['access_token'] );
        }
        if ( isset( $body['refresh_token'] ) ) {
            update_option( 'gdrive_gallery_oauth_refresh_token', $body['refresh_token'] );
        }
        if ( isset( $body['expires_in'] ) ) {
            update_option( 'gdrive_gallery_oauth_token_expires', time() + $body['expires_in'] );
        }

        return $body;
    }

    /**
     * Refresh access token
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function refresh_access_token() {
        $refresh_token = self::get_refresh_token();
        if ( empty( $refresh_token ) ) {
            return new WP_Error( 'no_refresh_token', __( 'No refresh token available', 'google-drive-gallery' ) );
        }

        $client_id = self::get_client_id();
        $client_secret = self::get_client_secret();

        $response = wp_remote_post( self::OAUTH_TOKEN_URL, [
            'body' => [
                'refresh_token' => $refresh_token,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'refresh_token',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'oauth_error', $body['error_description'] ?? $body['error'] );
        }

        // Update access token
        if ( isset( $body['access_token'] ) ) {
            update_option( 'gdrive_gallery_oauth_access_token', $body['access_token'] );
        }
        if ( isset( $body['expires_in'] ) ) {
            update_option( 'gdrive_gallery_oauth_token_expires', time() + $body['expires_in'] );
        }

        return true;
    }

    /**
     * Check if token is expired and refresh if needed
     *
     * @return bool True if token is valid, false otherwise
     */
    public static function ensure_valid_token() {
        $auth_type = self::get_auth_type();
        
        if ( 'service_account' === $auth_type ) {
            // Service account tokens are generated on demand
            return true;
        }

        $expires = get_option( 'gdrive_gallery_oauth_token_expires', 0 );
        
        // If token expires in less than 5 minutes, refresh it
        if ( $expires < ( time() + 300 ) ) {
            $result = self::refresh_access_token();
            return ! is_wp_error( $result );
        }

        return true;
    }

    /**
     * Get service account JWT token
     *
     * @return string|WP_Error JWT token or error
     */
    public static function get_service_account_token() {
        $credentials = self::get_service_account_credentials();
        if ( ! $credentials ) {
            return new WP_Error( 'no_credentials', __( 'No service account credentials configured', 'google-drive-gallery' ) );
        }

        // Create JWT
        $now = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claim_set = [
            'iss' => $credentials['client_email'],
            'scope' => implode( ' ', self::OAUTH_SCOPES ),
            'aud' => self::OAUTH_TOKEN_URL,
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $header_encoded = self::base64url_encode( wp_json_encode( $header ) );
        $claim_set_encoded = self::base64url_encode( wp_json_encode( $claim_set ) );
        $signature_input = $header_encoded . '.' . $claim_set_encoded;

        // Sign with private key
        $private_key = $credentials['private_key'];
        openssl_sign( $signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256 );
        $signature_encoded = self::base64url_encode( $signature );

        $jwt = $signature_input . '.' . $signature_encoded;

        // Exchange JWT for access token
        $response = wp_remote_post( self::OAUTH_TOKEN_URL, [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'oauth_error', $body['error_description'] ?? $body['error'] );
        }

        return $body['access_token'] ?? '';
    }

    /**
     * Base64 URL encode
     *
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private static function base64url_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    /**
     * Clear all authentication data
     */
    public static function clear_auth() {
        delete_option( 'gdrive_gallery_oauth_access_token' );
        delete_option( 'gdrive_gallery_oauth_refresh_token' );
        delete_option( 'gdrive_gallery_oauth_token_expires' );
    }
}
