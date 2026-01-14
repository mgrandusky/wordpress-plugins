<?php
/**
 * Emergency Configuration for Google Drive Gallery v1.0.1
 * 
 * Add these lines to your wp-config.php file to enable debugging
 * and force cache clearing.
 * 
 * IMPORTANT: Add these BEFORE the line that says:
 * "That's all, stop editing! Happy publishing."
 */

// ============================================================================
// EMERGENCY CONFIGURATION - TEMPORARY
// Remove or disable these after confirming images load correctly
// ============================================================================

// Enable WordPress debugging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false ); // Don't show errors on screen
@ini_set( 'display_errors', 0 );

// Force Google Drive Gallery to clear caches on every load
// WARNING: This impacts performance - only use for testing
define( 'GDRIVE_FORCE_CACHE_CLEAR', true );

// ============================================================================
// AFTER TESTING SUCCESSFULLY
// Either remove the lines above or change to:
// ============================================================================
/*
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'GDRIVE_FORCE_CACHE_CLEAR', false );
*/
