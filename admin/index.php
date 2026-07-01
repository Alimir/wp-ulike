<?php
/**
 * Include admin files
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

// Register admin pages
new wp_ulike_admin_pages();

// Include assets
new wp_ulike_admin_assets();

// Trust, onboarding, health checks (autoload + file-level init hooks).
WP_Ulike_Overview::class;
WP_Ulike_Deactivation_Feedback::class;
WP_Ulike_Activation_Pointer::class;

// include about menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-functions.php');
// include logs menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-hooks.php');