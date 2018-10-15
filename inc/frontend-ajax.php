<?php
/**
 * Front-end AJAX Functionalities
 * // @echo HEADER
 */

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * AJAX function for all like/unlike process
 *
 * @author       	Alimir
 * @since           1.0
 * @return			String
 */
function wp_ulike_process(){
	// Global variables
	global $wp_ulike_class;

	$post_ID     = $_POST['id'];
	$post_type   = $_POST['type'];
	$like_status = $_POST['status'];
	$nonce_token = $_POST['nonce'];
	$response    = array();

	if( $post_ID == null || ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	// Get post type settings
	$get_settings = wp_ulike_get_post_settings_by_type( $post_type, $post_ID );

	// If method not exist, then return error message
	if( empty( $get_settings ) ) {
		wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}

	// Extract post type settings
	extract( $get_settings );

	$get_like      = $get_meta_data != '' ? $get_meta_data : 0;

	$args = apply_filters( 'wp_ulike_ajax_process_atts', array(
			"id"       => $post_ID,				//Post ID
			"get_like" => $get_like,			//Number Of Likes
			"method"   => $post_type,			//JavaScript method
			"setting"  => $setting_key,			//Setting Key
			"type"     => 'process',			//Function type (post/process)
			"table"    => $table_name,			//posts table
			"column"   => $column_name,			//ulike table column name
			"key"      => $meta_key,			//meta key
			"cookie"   => $cookie_name			//Cookie Name
		), $post_ID
	);

	switch ( $like_status ){
		case 0:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'login_text', __( 'You Should Login To Submit Your Like', WP_ULIKE_SLUG ) ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text', __( 'Like', WP_ULIKE_SLUG ) ) ),
						'data'    => NULL
					);
			break;
		case 1:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text', __( 'Like', WP_ULIKE_SLUG ) ) ),
						'data'    => apply_filters( 'wp_ulike_respond_for_not_liked_data', $wp_ulike_class->wp_get_ulike( $args ), $post_ID )
					);
			break;
		case 2:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'unlike_notice', __( 'Sorry! You unliked this.', WP_ULIKE_SLUG ) ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text', __( 'Like', WP_ULIKE_SLUG ) ) ),
						'data'    => apply_filters( 'wp_ulike_respond_for_unliked_data', $wp_ulike_class->wp_get_ulike( $args ), $post_ID )
					);
			break;
		case 3:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText' => html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'button_text_u', __( 'Liked', WP_ULIKE_SLUG ) ) ),
						'data'    => apply_filters( 'wp_ulike_respond_for_liked_data', $wp_ulike_class->wp_get_ulike( $args ), $post_ID )
					);
			break;
		default:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'permission_text', __( 'You have not permission to unlike', WP_ULIKE_SLUG ) ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text', __( 'Liked', WP_ULIKE_SLUG ) ) ),
						'data'    => NULL
					);
	}

	wp_send_json_success( $response );
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_process'			, 'wp_ulike_process' );
add_action( 'wp_ajax_nopriv_wp_ulike_process'	, 'wp_ulike_process' );


/**
 * AJAX function for all like/unlike process
 *
 * @author       	Alimir
 * @since           1.0
 * @return			String
 */
function wp_ulike_get_likers(){

	$post_ID     = $_POST['id'];
	$post_type   = $_POST['type'];
	$nonce_token = $_POST['nonce'];
	$is_refresh  = $_POST['refresh'];

	// Check security nonce field
	if( $post_ID == null || ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	// Don't refresh likers data, when user is not logged in.
	if( $is_refresh && ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Notice: The likers box is refreshed only for logged in users!', WP_ULIKE_SLUG ) );
	}

	// Get post type settings
	$get_settings = wp_ulike_get_post_settings_by_type( $post_type, $post_ID );

	// If method not exist, then return error message
	if( empty( $get_settings ) ) {
		wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}

	// Extract settings array
	extract( $get_settings );

	// If likers box has been disabled
	if ( ! wp_ulike_get_setting( $setting_key, 'users_liked_box' ) ) {
		wp_send_json_error( __( 'Notice: The likers box is not activated!', WP_ULIKE_SLUG ) );
	}

	if( NULL !== ( $users_list = wp_ulike_get_likers_template( $table_name, $column_name, $post_ID, $setting_key ) ) ) {
		// Add specific class name with popover checkup
		$class_names = wp_ulike_get_setting( $setting_key, 'disable_likers_pophover', 0 ) ? 'wp_ulike_likers_wrapper wp_ulike_display_inline' : 'wp_ulike_likers_wrapper';
		// Return the list of users
		wp_send_json_success( array( 'template' => $users_list, 'class' => $class_names ) );
	}

	// Send blank success when the users list are empty
	wp_send_json_success();

}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_get_likers'		 , 'wp_ulike_get_likers' );
add_action( 'wp_ajax_nopriv_wp_ulike_get_likers' , 'wp_ulike_get_likers' );