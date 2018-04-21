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
	global $wp_ulike_class,$wp_user_IP;

	$post_ID     = $_POST['id'];
	$post_type   = $_POST['type'];
	$like_status = $_POST['status'];
	$nonce_token = $_POST['nonce'];
	$response    = array();

	if( $post_ID == null || ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}	

	switch ( $post_type ) {
		case 'likeThis':
			$get_meta_data = get_post_meta($post_ID, '_liked', true);
			$setting_key   = 'wp_ulike_posts';
			$table_name    = 'ulike';
			$column_name   = 'post_id';
			$meta_key      = '_liked';
			$cookie_name   = 'liked-';
			break;
		
		case 'likeThisComment':
			$get_meta_data = get_comment_meta($post_ID, '_commentliked', true);
			$setting_key   = 'wp_ulike_comments';
			$table_name    = 'ulike_comments';
			$column_name   = 'comment_id';
			$meta_key      = '_commentliked';
			$cookie_name   = 'comment-liked-';	
			break;
		
		case 'likeThisActivity':
			$get_meta_data = bp_activity_get_meta($post_ID, '_activityliked');
			$setting_key   = 'wp_ulike_buddypress';
			$table_name    = 'ulike_activities';
			$column_name   = 'activity_id';
			$meta_key      = '_activityliked';
			$cookie_name   = 'activity-liked-';
			break;
		
		case 'likeThisTopic':
			$get_meta_data = get_post_meta($post_ID, '_topicliked', true);
			$setting_key   = 'wp_ulike_bbpress';
			$table_name    = 'ulike_forums';
			$column_name   = 'topic_id';
			$meta_key      = '_topicliked';
			$cookie_name   = 'topic-liked-';
			break;
		
		default:
			wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}
	
	$get_like      = $get_meta_data != '' ? $get_meta_data : 0;
	$return_userID = $wp_ulike_class->get_reutrn_id();
	
	$args = apply_filters( 'wp_ulike_ajax_process_atts', array(
			"id"       => $post_ID,				//Post ID
			"user_id"  => $return_userID,		//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip"  => $wp_user_IP,			//User IP
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
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'login_text' ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text' ) ),
						'data'    => NULL
					);
			break;				
		case 1:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice' ),
						'btnText' => html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'button_text' ) ),
						'data'    => apply_filters( 'wp_ulike_respond_for_not_liked_data', $wp_ulike_class->wp_get_ulike( $args ), $post_ID )
					);
			break;
		case 2:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'unlike_notice' ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text' ) ),
						'data'    => apply_filters( 'wp_ulike_respond_for_unliked_data', $wp_ulike_class->wp_get_ulike( $args ), $post_ID )
					);
			break;				
		case 3:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'like_notice'),
						'btnText' => html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'button_text_u' ) ),
						'data'    => apply_filters( 'wp_ulike_respond_for_liked_data', $wp_ulike_class->wp_get_ulike( $args ), $post_ID )
					);
			break;
		default:
			$response = array(
						'message' => wp_ulike_get_setting( 'wp_ulike_general', 'permission_text' ),
						'btnText' => html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text' ) ),
						'data'    => NULL
					);
	}

	wp_send_json_success( $response );
}
//	wp_ajax hooks for the custom AJAX requests 
add_action( 'wp_ajax_wp_ulike_process'			, 'wp_ulike_process' );
add_action( 'wp_ajax_nopriv_wp_ulike_process'	, 'wp_ulike_process' );		