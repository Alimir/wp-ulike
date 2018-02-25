<?php
/**
 * Include admin files
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}
//include about menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-functions.php');
//include logs menu functions
require_once( WP_ULIKE_ADMIN_DIR . '/admin-hooks.php');
