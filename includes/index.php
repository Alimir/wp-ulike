<?php
/**
 * Include Files
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

// include settings panel core
require_once( WP_ULIKE_ADMIN_DIR . '/settings/classes/setup.class.php');

// Load REST API for Optiwich integration
require_once WP_ULIKE_ADMIN_DIR . '/classes/class-wp-ulike-settings-api.php';

// Register customizer options
new wp_ulike_customizer();

// Functions
include_once( 'functions/utilities.php' );
include_once( 'functions/general.php' );
include_once( 'functions/meta.php' );
include_once( 'functions/templates.php' );
include_once( 'functions/counter.php' );
include_once( 'functions/content-types.php' );
include_once( 'functions/queries.php' );

// Hooks
include_once( 'hooks/general.php' );
include_once( 'hooks/shortcodes.php' );
include_once( 'hooks/third-party.php' );

// Blocks
include_once( 'blocks/index.php' );