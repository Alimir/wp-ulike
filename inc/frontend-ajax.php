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

	$post_ID     = isset( $_POST['id'] ) ? $_POST['id'] : NULL;
	$post_type   = isset( $_POST['type'] ) ? $_POST['type'] : NULL;
	$nonce_token = isset( $_POST['nonce'] ) ? $_POST['nonce'] : NULL;
	$factor      = isset( $_POST['factor'] ) ? $_POST['factor'] : NULL;
	$response    = array();

	if( $post_ID == null || ( ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) && wp_ulike_is_cache_exist() ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	// Get post type settings
	$get_settings = wp_ulike_get_post_settings_by_type( $post_type );

	// If method not exist, then return error message
	if( empty( $get_settings ) ) {
		wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}

	// Extract post type settings
	extract( $get_settings );

	$args = apply_filters( 'wp_ulike_ajax_process_atts', array(
			"id"       => $post_ID,                          //Post ID
			"method"   => $post_type,                        //JavaScript method
			"setting"  => $setting,                          //Setting Key
			"type"     => 'process',                         //Function type (post/process)
			"table"    => $table,                            //posts table
			"column"   => $column,                           //ulike table column name
			"key"      => $key,                              //meta key
			"slug"     => $slug,                             //meta key
			"cookie"   => $cookie,                           //Cookie Name
			"factor"   => $factor,                           //Factor type
		), $post_ID, $get_settings
	);

	if( wp_ulike_is_true( wp_ulike_get_setting( $setting, 'only_registered_users' ) ) && ! is_user_logged_in() ) {
		$response = array(
			'message'     => wp_ulike_get_setting( 'wp_ulike_general', 'login_text', __( 'You Should Login To Submit Your Like', WP_ULIKE_SLUG ) ),
			'btnText'     => NULL,
			'messageType' => 'info',
			'status'      => 4,
			'data'        => NULL
		);
	} else{
		$logging_method = wp_ulike_get_setting( $setting, 'logging_method' );

		if( ! $wp_ulike_class->has_permission( $args, $logging_method ) ){
			$response = array(
				'message'     => wp_ulike_get_setting( 'wp_ulike_general', 'permission_text', __( 'You have already registered a vote.', WP_ULIKE_SLUG ) ),
				'btnText'     => NULL,
				'messageType' => 'warning',
				'status'      => 5,
				'data'        => NULL
			);
		} else {
			$counter = $wp_ulike_class->wp_get_ulike( $args );
			$status  = $wp_ulike_class->get_status();

			switch ( $status ){
				case 1:
					$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText' => wp_ulike_get_button_text( 'button_text' ),
						'messageType' => 'success',
						'status'  => $status,
						'data'    => apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter, $post_ID )
					);
					break;
				case 2:
					$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'unlike_notice', __( 'Sorry! You unliked this.', WP_ULIKE_SLUG ) ),
						'btnText' => wp_ulike_get_button_text( 'button_text' ),
						'messageType' => 'error',
						'status'  => $status,
						'data'    => apply_filters( 'wp_ulike_respond_for_unliked_data', $counter, $post_ID )
					);
					break;
				case 3:
					$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
 						'btnText' => wp_ulike_get_button_text( 'button_text_u' ),
						'messageType' => 'success',
						'status'  => $status,
						'data'    => apply_filters( 'wp_ulike_respond_for_liked_data', $counter, $post_ID )
					);
					break;
				case 4:
					$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText' => wp_ulike_get_button_text( 'button_text' ),
						'messageType' => 'success',
						'status'  => $status,
						'data'    => apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter, $post_ID )
					);
					break;
			}
		}
	}

	wp_send_json_success( apply_filters( 'wp_ulike_ajax_respond', $response, $post_ID, $status, $args ) );
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

	$post_ID          = $_POST['id'];
	$post_type        = $_POST['type'];
	$nonce_token      = $_POST['nonce'];
	$is_refresh       = $_POST['refresh'];
	$disable_pophover = $_POST['disablePophover'];

	// Check security nonce field
	if( $post_ID == null || ( ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) && wp_ulike_is_cache_exist() ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	// Don't refresh likers data, when user is not logged in.
	if( $is_refresh && ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Notice: The likers box is refreshed only for logged in users!', WP_ULIKE_SLUG ) );
	}

	// Get post type settings
	$get_settings = wp_ulike_get_post_settings_by_type( $post_type );

	// If method not exist, then return error message
	if( empty( $get_settings ) ) {
		wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}

	// Extract settings array
	extract( $get_settings );

	// If likers box has been disabled
	if ( ! wp_ulike_get_setting( $setting, 'users_liked_box' ) ) {
		wp_send_json_error( __( 'Notice: The likers box is not activated!', WP_ULIKE_SLUG ) );
	}

	// Add specific class name with popover checkup
	$class_names = wp_ulike_is_true( $disable_pophover ) ? 'wp_ulike_likers_wrapper wp_ulike_display_inline' : 'wp_ulike_likers_wrapper';
	$users_list  = wp_ulike_get_likers_template( $table, $column, $post_ID, $setting );

	wp_send_json_success( array( 'template' => $users_list, 'class' => $class_names ) );
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_get_likers'		 , 'wp_ulike_get_likers' );
add_action( 'wp_ajax_nopriv_wp_ulike_get_likers' , 'wp_ulike_get_likers' );