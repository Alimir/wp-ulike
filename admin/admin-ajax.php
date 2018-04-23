<?php
/**
 * Back-end AJAX Functionalities
 * // @echo HEADER
 */

/*******************************************************
  Start AJAX From Here
*******************************************************/

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
	$nonce = $_POST['nonce'];

	if( $id == null || ! wp_verify_nonce( $nonce, $table . $id ) || ! current_user_can( 'delete_posts' ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	if( $wpdb->delete( $wpdb->prefix.$table, array( 'id' => $id ) ) ) {
		wp_send_json_success( __( 'It\'s Ok!', WP_ULIKE_SLUG ) );
	} else {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

}
add_action('wp_ajax_ulikelogs','wp_ulike_logs_process');
