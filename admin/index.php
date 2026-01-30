<?php
/**
 * Include admin files
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

// include settings panel core
require_once( WP_ULIKE_ADMIN_DIR . '/settings/classes/setup.class.php');

// Register admin pages
new wp_ulike_admin_pages();

// Include assets
new wp_ulike_admin_assets();

// include about menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-functions.php');
// include logs menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-hooks.php');