<?php
/**
 * Include admin files
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

// include _deprecated settings panel
include( WP_ULIKE_ADMIN_DIR . '/settings/_deprecated/classes/setup.class.php');

// include settings panel
require_once( WP_ULIKE_ADMIN_DIR . '/settings/classes/setup.class.php');
// Register admin menus
new wp_ulike_admin_panel();

// include about menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-functions.php');
// include logs menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-hooks.php');