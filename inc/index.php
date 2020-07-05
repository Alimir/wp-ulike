<?php
/**
 * Include Files
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

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