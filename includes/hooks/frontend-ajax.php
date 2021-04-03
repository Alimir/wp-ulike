<?php
/**
 * Front-end AJAX Hooks
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * AJAX function for all votings process
 *
 * @return void
 */
function wp_ulike_process(){
	new wp_ulike_cta_listener;
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_process'			, 'wp_ulike_process' );
add_action( 'wp_ajax_nopriv_wp_ulike_process'	, 'wp_ulike_process' );

/**
 * AJAX function for voters process
 */
function wp_ulike_get_likers(){
	new wp_ulike_voters_listener;
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_get_likers'		 , 'wp_ulike_get_likers' );
add_action( 'wp_ajax_nopriv_wp_ulike_get_likers' , 'wp_ulike_get_likers' );