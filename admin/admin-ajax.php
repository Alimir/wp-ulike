<?php
/**
 * Back-end AJAX Functionalities
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
 * AJAX handler to get statistics data
 *
 * @author       	Alimir
 * @since           3.4
 * @return			Void
 */
function wp_ulike_ajax_stats() {

	$nonce  = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp-ulike-ajax-nonce' ) || ! current_user_can( wp_ulike_get_user_access_capability('stats') ) ) {
		wp_send_json_error( esc_html__( 'Error: Something Wrong Happened!', 'wp-ulike' ) );
	}

	$instance = wp_ulike_stats::get_instance();
	$output   = $instance->get_all_data();

	wp_send_json_success( wp_json_encode( $output ) );

}
add_action( 'wp_ajax_wp_ulike_ajax_stats', 'wp_ulike_ajax_stats' );

/**
 * AJAX handler to store the state of dismissible notices.
 *
 * @author       	Alimir
 * @since           2.9
 * @return			Void
 */
function wp_ulike_ajax_notice_handler() {
    // Store it in the options table
	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_notice_nonce' ) ) {
		wp_send_json_error(  esc_html__( 'Token Error.', 'wp-ulike' ) );
	} else {
		wp_ulike_set_transient( 'wp-ulike-notice-' . sanitize_text_field( $_POST['id' ] ), 1, absint( $_POST['expiration'] ) );
		wp_send_json_success( esc_html__( 'It\'s OK.', 'wp-ulike' ) );
	}
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
	// Global wpdb calss
	global $wpdb;
	// Variables
	$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : '';
	$table = isset( $_POST['table'] ) ? esc_sql( $_POST['table'] ) : '';
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

	if( $id == '' || ! wp_verify_nonce( $nonce, $table . $id ) || ! current_user_can( wp_ulike_get_user_access_capability('logs') ) ) {
		wp_send_json_error( esc_html__( 'Error: Something Wrong Happened!', 'wp-ulike' ) );
	}

	if( $wpdb->delete( $wpdb->prefix.$table, array( 'id' => $id ) ) ) {
		wp_send_json_success( esc_html__( 'It\'s Ok!', 'wp-ulike' ) );
	} else {
		wp_send_json_error( esc_html__( 'Error: Something Wrong Happened!', 'wp-ulike' ) );
	}

}
add_action('wp_ajax_ulikelogs','wp_ulike_logs_process');