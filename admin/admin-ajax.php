<?php
/**
 * Back-end AJAX Functionalities
 * // @echo HEADER
 */

/**
 * AJAX handler to store the state of dismissible notices.
 *
 * @author       	Alimir	 	
 * @since           2.9
 * @return			Void
 */	
function wp_ulike_ajax_notice_handler() {
    // Store it in the options table
    update_option( 'wp-ulike-notice-dismissed', TRUE );
}
add_action( 'wp_ajax_wp_ulike_dismissed_notice', 'wp_ulike_ajax_notice_handler' );

/**
 * Remove logs from tables
 *
 * @author       	Alimir	 	
 * @since           2.1
 * @return			Void
 */
function wp_ulike_logs_process(){
	global $wpdb;
	$id    = $_POST['id'];
	$table = $_POST['table'];
	$wpdb->delete( $wpdb->prefix.$table ,array( 'id' => $id ));
	wp_die();
}
add_action('wp_ajax_ulikelogs','wp_ulike_logs_process');
