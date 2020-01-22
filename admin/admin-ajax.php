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

	$nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp-ulike-ajax-nonce' ) || ! current_user_can( wp_ulike_get_user_access_capability('stats') ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	$instance = wp_ulike_stats::get_instance();
	$output   = $instance->get_all_data();

	wp_send_json_success( json_encode( $output ) );

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
	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_notice_nonce' ) ) {
		wp_send_json_error(  __( 'Token Error.', WP_ULIKE_SLUG ) );
	} else {
		wp_ulike_set_transient( 'wp-ulike-notice-' . $_POST['id'], 1, $_POST['expiration'] );
		wp_send_json_success( __( 'It\'s OK.', WP_ULIKE_SLUG ) );
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
	$id    = isset( $_POST['id'] ) ? $_POST['id'] : '';
	$table = isset( $_POST['table'] ) ? $_POST['table'] : '';
	$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

	if( $id == '' || ! wp_verify_nonce( $nonce, $table . $id ) || ! current_user_can( wp_ulike_get_user_access_capability('logs') ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	if( $wpdb->delete( $wpdb->prefix.$table, array( 'id' => $id ) ) ) {
		wp_send_json_success( __( 'It\'s Ok!', WP_ULIKE_SLUG ) );
	} else {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

}
add_action('wp_ajax_ulikelogs','wp_ulike_logs_process');

/**
 * Upgarde old option values
 *
 * @return void
 */
function wp_ulike_upgrade_option_panel(){
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_notice_nonce' ) && current_user_can( 'manage_options' ) ) {
		wp_send_json_error(  __( 'Token Error.', WP_ULIKE_SLUG ) );
	}

	// get old values
	$old_data = array (
		'enable_kilobyte_format' => wp_ulike_get_setting( 'wp_ulike_general', 'format_number' ),
		'enable_toast_notice'    => wp_ulike_get_setting( 'wp_ulike_general', 'notifications' ),
		'enable_anonymise_ip'    => wp_ulike_get_setting( 'wp_ulike_general', 'anonymise' ),
		'disable_admin_notice'   => wp_ulike_get_setting( 'wp_ulike_general', 'hide_admin_notice' ),
		'enable_meta_values'     => wp_ulike_get_setting( 'wp_ulike_general', 'enable_meta_values' ),
		'posts_group'            => array (
			'template'                    => wp_ulike_get_setting( 'wp_ulike_posts', 'theme' ),
			'enable_auto_display'         => wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display' ),
			'auto_display_position'       => wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display_position' ),
			'logging_method'              => wp_ulike_get_setting( 'wp_ulike_posts', 'logging_method' ),
			'enable_only_logged_in_users' => wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users' ),
			'logged_out_display_type'     => wp_ulike_get_setting( 'wp_ulike_general', 'login_type' ),
			'enable_likers_box'           => wp_ulike_get_setting( 'wp_ulike_posts', 'users_liked_box' ),
			'disable_likers_pophover'     => wp_ulike_get_setting( 'wp_ulike_posts', 'disable_likers_pophover' ),
			'likers_gravatar_size'        => wp_ulike_get_setting( 'wp_ulike_posts', 'users_liked_box_avatar_size' ),
			'likers_count'                => wp_ulike_get_setting( 'wp_ulike_posts', 'number_of_users' ),
			'likers_template'             => wp_ulike_get_setting( 'wp_ulike_posts', 'users_liked_box_template' )
		),
 		'comments_group' => array (
			'template'                    => wp_ulike_get_setting( 'wp_ulike_comments', 'theme' ),
			'enable_auto_display'         => wp_ulike_get_setting( 'wp_ulike_comments', 'auto_display' ),
			'auto_display_position'       => wp_ulike_get_setting( 'wp_ulike_comments', 'auto_display_position' ),
			'logging_method'              => wp_ulike_get_setting( 'wp_ulike_comments', 'logging_method' ),
			'enable_only_logged_in_users' => wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users' ),
			'logged_out_display_type'     => wp_ulike_get_setting( 'wp_ulike_general', 'login_type' ),
			'enable_likers_box'           => wp_ulike_get_setting( 'wp_ulike_comments', 'users_liked_box' ),
			'disable_likers_pophover'     => wp_ulike_get_setting( 'wp_ulike_comments', 'disable_likers_pophover' ),
			'likers_gravatar_size'        => wp_ulike_get_setting( 'wp_ulike_comments', 'users_liked_box_avatar_size' ),
			'likers_count'                => wp_ulike_get_setting( 'wp_ulike_comments', 'number_of_users' ),
			'likers_template'             => wp_ulike_get_setting( 'wp_ulike_comments', 'users_liked_box_template' )
		),
		'buddypress_group' => array (
			'template'                       => wp_ulike_get_setting( 'wp_ulike_buddypress', 'theme' ),
			'enable_auto_display'            => wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display' ),
			'auto_display_position'          => wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display_position' ),
			'logging_method'                 => wp_ulike_get_setting( 'wp_ulike_buddypress', 'logging_method' ),
			'enable_only_logged_in_users'    => wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users' ),
			'logged_out_display_type'        => wp_ulike_get_setting( 'wp_ulike_general', 'login_type' ),
			'enable_likers_box'              => wp_ulike_get_setting( 'wp_ulike_buddypress', 'users_liked_box' ),
			'disable_likers_pophover'        => wp_ulike_get_setting( 'wp_ulike_buddypress', 'disable_likers_pophover' ),
			'likers_gravatar_size'           => wp_ulike_get_setting( 'wp_ulike_buddypress', 'users_liked_box_avatar_size' ),
			'likers_count'                   => wp_ulike_get_setting( 'wp_ulike_buddypress', 'number_of_users' ),
			'likers_template'                => wp_ulike_get_setting( 'wp_ulike_buddypress', 'users_liked_box_template' ),
			'enable_comments'                => wp_ulike_get_setting( 'wp_ulike_buddypress', 'activity_comment' ),
			'enable_add_bp_activity'         => wp_ulike_get_setting( 'wp_ulike_buddypress', 'new_likes_activity' ),
			'posts_notification_template'    => wp_ulike_get_setting( 'wp_ulike_buddypress', 'bp_post_activity_add_header' ),
			'comments_notification_template' => wp_ulike_get_setting( 'wp_ulike_buddypress', 'bp_comment_activity_add_header' ),
			'enable_add_notification'        => wp_ulike_get_setting( 'wp_ulike_buddypress', 'custom_notification' ),
		),
		'bbpress_group' => array (
			'template'                    => wp_ulike_get_setting( 'wp_ulike_bbpress', 'theme' ),
			'enable_auto_display'         => wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display' ),
			'auto_display_position'       => wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display_position' ),
			'logging_method'              => wp_ulike_get_setting( 'wp_ulike_bbpress', 'logging_method' ),
			'enable_only_logged_in_users' => wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users' ),
			'logged_out_display_type'     => wp_ulike_get_setting( 'wp_ulike_general', 'login_type' ),
			'enable_likers_box'           => wp_ulike_get_setting( 'wp_ulike_bbpress', 'users_liked_box' ),
			'disable_likers_pophover'     => wp_ulike_get_setting( 'wp_ulike_bbpress', 'disable_likers_pophover' ),
			'likers_gravatar_size'        => wp_ulike_get_setting( 'wp_ulike_bbpress', 'users_liked_box_avatar_size' ),
			'likers_count'                => wp_ulike_get_setting( 'wp_ulike_bbpress', 'number_of_users' ),
			'likers_template'             => wp_ulike_get_setting( 'wp_ulike_bbpress', 'users_liked_box_template' )
		),
		'already_registered_notice' => wp_ulike_get_setting( 'wp_ulike_general', 'permission_text' ),
		'login_required_notice'     => wp_ulike_get_setting( 'wp_ulike_general', 'login_text' ),
		'like_notice'               => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice' ),
		'unlike_notice'             => wp_ulike_get_setting( 'wp_ulike_general', 'unlike_notice' ),
		'dislike_notice'            => wp_ulike_get_setting( 'wp_ulike_general', 'dislike_notice' ),
		'undislike_notice'          => wp_ulike_get_setting( 'wp_ulike_general', 'undislike_notice' ),
		'custom_css'                => wp_ulike_get_setting( 'wp_ulike_customize', 'custom_css' )
	);

	$update_status = update_option( 'wp_ulike_settings', $old_data  );
	// Update flag option
	update_option( 'wp_ulike_upgrade_option_panel_status', true );

	if( !$update_status ){
		wp_send_json_error(  __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	wp_send_json_success(  __( 'It\'s Ok!', WP_ULIKE_SLUG ) );
}
add_action('wp_ajax_wp_ulike_upgrade_option_panel','wp_ulike_upgrade_option_panel');