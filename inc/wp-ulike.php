<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed
	
	/**
	 * wp_ulike function for posts like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           1.0
	 * @updated         2.3
	 * @updated         2.7 //added 'wp_ulike_posts_add_attr', 'wp_ulike_posts_microdata' & 'wp_ulike_login_alert_template' filters
	 * @updated         2.8 //Removed some old functions & added new filters support.
	 * @updated         2.9 // Modify get_liked_users functionality
	 * @return			String
	 */
	function wp_ulike( $type = 'get', $args = array() ) {
		//global variables
		global $post, $wp_ulike_class, $wp_user_IP;
		
		$post_ID       = isset( $args['id'] ) ? $args['id'] : $post->ID;
		$get_post_meta = get_post_meta( $post_ID, '_liked', true );
		$get_like      = empty( $get_post_meta ) ? 0 : $get_post_meta;
		$return_userID = $wp_ulike_class->get_reutrn_id();
		$attributes    = apply_filters( 'wp_ulike_posts_add_attr', null );
		$microdata     = apply_filters( 'wp_ulike_posts_microdata', null );
		$style         = wp_ulike_get_setting( 'wp_ulike_posts', 'theme' );
		
		//Main data
		$defaults      = array(
			"id"         => $post_ID,				//Post ID
			"user_id"    => $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip"    => $wp_user_IP,			//User IP
			"get_like"   => $get_like,				//Number Of Likes
			"method"     => 'likeThis',				//JavaScript method
			"setting"    => 'wp_ulike_posts',		//Setting Key
			"type"       => 'post',					//Function type (post/process)
			"table"      => 'ulike',				//posts table
			"column"     => 'post_id',				//ulike table column name
			"key"        => '_liked',				//meta key
			"cookie"     => 'liked-',				//Cookie Name
			"slug"       => 'post',					//Slug Name
			"style"      => $style,					//Get Default Theme
			"microdata"  => $microdata,				//Get Microdata Filter
			"attributes" => $attributes				//Get Attributes Filter
		);
		
        $parsed_args = wp_parse_args( $args, $defaults );		
		
		if( ( wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users') != '1' ) or ( wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users' ) == '1' && is_user_logged_in() ) ) {
			//call wp_get_ulike function from wp_ulike class
			$wp_ulike = $wp_ulike_class->wp_get_ulike( $parsed_args );
			$wp_ulike .= $wp_ulike_class->get_liked_users( $parsed_args );

			if ($type == 'put') {
				return $wp_ulike;
			}
			else {
				echo $wp_ulike;
			}
		
		}//end !only_registered_users condition
		elseif ( wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users') == '1' && ! is_user_logged_in() ) {
			if(wp_ulike_get_setting( 'wp_ulike_general', 'login_type') == "button") {
				return $wp_ulike_class->get_template( $parsed_args, 0 ) . $wp_ulike_class->get_liked_users( $parsed_args );		
			} else {
				return apply_filters('wp_ulike_login_alert_template', '<p class="alert alert-info fade in" role="alert">'.__('You need to login in order to like this post: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>');
			}
		}//end only_registered_users condition
		
	}
	
	/**
	 * wp_ulike_comments function for comments like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           1.6
	 * @updated         2.3
	 * @updated         2.7 // added 'wp_ulike_login_alert_template' & 'wp_ulike_comments_add_attr' filters
	 * @updated         2.8 // Removed some old functions & added new filters support.
	 * @updated         2.9 // Modify get_liked_users functionality
	 * @return			String
	 */
	function wp_ulike_comments( $type = 'get', $args = array() ) {
		//global variables
		global $wp_ulike_class, $wp_user_IP;
		
		$comment_ID    = isset( $args['id'] ) ? $args['id'] : get_comment_ID();
		$comment_meta  = get_comment_meta( $comment_ID, '_commentliked', true );
		$get_like      = empty( $comment_meta ) ? 0 : $comment_meta;
		$return_userID = $wp_ulike_class->get_reutrn_id();
		$attributes    = apply_filters( 'wp_ulike_comments_add_attr', null );
		$microdata     = apply_filters( 'wp_ulike_comments_microdata', null );
		$style         = wp_ulike_get_setting( 'wp_ulike_comments', 'theme' );	
		
		//Main Data
		$defaults      = array(
			"id"         => $comment_ID,			//Comment ID
			"user_id"    => $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip"    => $wp_user_IP,			//User IP
			"get_like"   => $get_like,				//Number Of Likes
			"method"     => 'likeThisComment',		//JavaScript method
			"setting"    => 'wp_ulike_comments',	//Setting Key
			"type"       => 'post',					//Function type (post/process)
			"table"      => 'ulike_comments',		//Comments table
			"column"     => 'comment_id',			//ulike_comments table column name
			"key"        => '_commentliked',		//meta key
			"cookie"     => 'comment-liked-',		//Cookie Name
			"slug"       => 'comment',				//Slug Name
			"style"      => $style,					//Get Default Theme
			"microdata"  => $microdata,				//Get Microdata Filter
			"attributes" => $attributes				//Get Attributes Filter
		);
		
		$parsed_args = wp_parse_args( $args, $defaults );
		
		if( ( wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users' ) != '1' ) or ( wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users' ) == '1' && is_user_logged_in() ) ) {	
			//call wp_get_ulike function from wp_ulike class
			$wp_ulike = $wp_ulike_class->wp_get_ulike( $parsed_args );		
			$wp_ulike .= $wp_ulike_class->get_liked_users( $parsed_args );

			if ($type == 'put') {
				return $wp_ulike;
			}
			else {
				echo $wp_ulike;
			}
		
		}//end !only_registered_users condition
		elseif (wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users') == '1' && ! is_user_logged_in()){
			if( wp_ulike_get_setting( 'wp_ulike_general', 'login_type' ) == "button" ){
				return $wp_ulike_class->get_template( $parsed_args, 0 ) . $wp_ulike_class->get_liked_users( $parsed_args );	
			} else {
				return apply_filters( 'wp_ulike_login_alert_template', '<p class="alert alert-info fade in" role="alert">'.__('You need to login in order to like this comment: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>' );
			}
		}//end only_registered_users condition
		
	}	
	
	/**
	 * wp_ulike_buddypress function for activities like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.3
	 * @updated         2.4
	 * @updated         2.7 //added 'wp_ulike_login_alert_template' & 'wp_ulike_activities_add_attr' filters
	 * @updated         2.8 //Removed some old functions & added new filters support.
	 * @updated         2.9 // Modify get_liked_users functionality
	 * @return			String
	 */
	function wp_ulike_buddypress( $type = 'get', $args = array() ) {
		//global variables
		global $wp_ulike_class, $wp_user_IP;
        
        if ( bp_get_activity_comment_id() != null ){
			$activityID 	= isset( $args['id'] ) ? $args['id'] : bp_get_activity_comment_id();
		} else {
			$activityID 	= isset( $args['id'] ) ? $args['id'] : bp_get_activity_id(); 
		}
		
		$bp_get_meta   = bp_activity_get_meta($activityID, '_activityliked');
		$get_like      = empty( $bp_get_meta ) ? 0 : $bp_get_meta;
		$return_userID = $wp_ulike_class->get_reutrn_id();
		$attributes    = apply_filters( 'wp_ulike_activities_add_attr', null );
		$microdata     = apply_filters( 'wp_ulike_activities_microdata', null );
		$style         = wp_ulike_get_setting( 'wp_ulike_buddypress', 'theme' );
		
		//Main Data
		$defaults      = array(
			"id"         => $activityID,			//Activity ID
			"user_id"    => $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip"    => $wp_user_IP,			//User IP
			"get_like"   => $get_like,				//Number Of Likes
			"method"     => 'likeThisActivity',		//JavaScript method
			"setting"    => 'wp_ulike_buddypress',	//Setting Key
			"type"       => 'post',					//Function type (post/process)
			"table"      => 'ulike_activities',		//Activities table
			"column"     => 'activity_id',			//ulike_activities table column name			
			"key"        => '_activityliked',		//meta key
			"cookie"     => 'activity-liked-',		//Cookie Name
			"slug"       => 'activity',				//Slug Name
			"style"      => $style,					//Get Default Theme
			"microdata"  => $microdata,				//Get Microdata Filter
			"attributes" => $attributes				//Get Attributes Filter
		);
		
		$parsed_args = wp_parse_args( $args, $defaults );
		
		if( ( wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users') != '1' ) or ( wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users' ) == '1' && is_user_logged_in() ) ) {
			//call wp_get_ulike function from wp_ulike class
			$wp_ulike 		= $wp_ulike_class->wp_get_ulike( $parsed_args );
			$wp_ulike  		.= $wp_ulike_class->get_liked_users( $parsed_args );

			if ($type == 'put') {
				return $wp_ulike;
			}
			else {
				echo $wp_ulike;
			}
			
		}//end !only_registered_users condition
		elseif ( wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users') == '1' && ! is_user_logged_in() ) {
			if( wp_ulike_get_setting( 'wp_ulike_general', 'login_type') == "button" ){
				return $wp_ulike_class->get_template( $parsed_args, 0 ) . $wp_ulike_class->get_liked_users( $parsed_args );
			}
			else{
				return apply_filters('wp_ulike_login_alert_template', '<p class="alert alert-info fade in" role="alert">'.__('You need to login in order to like this activity: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>');
			}	
		}//end only_registered_users condition
		
	}	
	
	/**
	 * wp_ulike_bbpress function for topics like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           2.2
	 * @updated         2.3
	 * @updated         2.4.1
	 * @updated         2.7 //added 'wp_ulike_login_alert_template' & 'wp_ulike_topics_add_attr' filters
	 * @updated         2.8 //Removed some old functions & added new filters support.
	 * @updated         2.9 // Modify get_liked_users functionality
	 * @return			String
	 */
	function wp_ulike_bbpress( $type = 'get', $args = array() ) {
		//global variables
		global $post,$wp_ulike_class,$wp_user_IP;
        
        //Thanks to @Yehonal for this commit
		$replyID       = bbp_get_reply_id();
		$post_ID       = !$replyID ? $post->ID : $replyID;
		$post_ID       = isset( $args['id'] ) ? $args['id'] : $post_ID;
		
		$get_post_meta = get_post_meta( $post_ID, '_topicliked', true );
		$get_like      = empty( $get_post_meta ) ? 0 : $get_post_meta;
		$return_userID = $wp_ulike_class->get_reutrn_id();	
		$attributes    = apply_filters( 'wp_ulike_topics_add_attr', null );
		$microdata     = apply_filters( 'wp_ulike_topics_microdata', null );
		$style         = wp_ulike_get_setting( 'wp_ulike_bbpress', 'theme' );	
		
		//Main Data
		$defaults      = array(
			"id"         => $post_ID,				//Post ID
			"user_id"    => $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip"    => $wp_user_IP,			//User IP
			"get_like"   => $get_like,				//Number Of Likes
			"method"     => 'likeThisTopic',		//JavaScript method
			"setting"    => 'wp_ulike_bbpress',		//Setting Key
			"type"       => 'post',					//Function type (post/process)
			"table"      => 'ulike_forums',			//posts table
			"column"     => 'topic_id',				//ulike table column name
			"key"        => '_topicliked',			//meta key
			"cookie"     => 'topic-liked-',			//Cookie Name
			"slug"       => 'topic',				//Slug Name
			"style"      => $style,					//Get Default Theme
			"microdata"  => $microdata,				//Get Microdata Filter
			"attributes" => $attributes				//Get Attributes Filter
		);
		
		$parsed_args = wp_parse_args( $args, $defaults );
		
		if( ( wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users' ) != '1' ) or ( wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users' ) == '1' && is_user_logged_in() ) ) {
			//call wp_get_ulike function from wp_ulike class
			$wp_ulike = $wp_ulike_class->wp_get_ulike( $parsed_args );
			$wp_ulike .= $wp_ulike_class->get_liked_users( $parsed_args );

			if ($type == 'put') {
				return $wp_ulike;
			}
			else {
				echo $wp_ulike;
			}
		
		}//end !only_registered_users condition
		
		else if ( wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users' ) == '1' && !is_user_logged_in()) {
			if( wp_ulike_get_setting( 'wp_ulike_general', 'login_type') ){
				return $wp_ulike_class->get_template( $parsed_args, 0 ) . $wp_ulike_class->get_liked_users( $parsed_args );	
			}
			else {
				return apply_filters('wp_ulike_login_alert_template', '<p class="alert alert-info fade in" role="alert">'.__('You need to login in order to like this post: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>');
			}
		}//end only_registered_users condition
		
	}	

	/**
	 * wp_ulike_process function for all like/unlike process
	 *
	 * @author       	Alimir	 	
	 * @since           1.0
	 * @updated         2.2
	 * @updated         2.4.1
	 * @updated         2.8 //Replaced 'WP_Ajax_Response' class with 'wp_send_json' function + Added message respond
	 * @updated         2.8.1 //Added 'wp_ulike_respond_for_not_liked_data' & 'wp_ulike_respond_for_unliked_data' & 'wp_ulike_respond_for_liked_data' filters
	 * @updated         3.0
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
			wp_die( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
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
				wp_die( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
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

		wp_send_json($response);
	}
	//	wp_ajax hooks for the custom AJAX requests 
	add_action( 'wp_ajax_wp_ulike_process'			, 'wp_ulike_process' );
	add_action( 'wp_ajax_nopriv_wp_ulike_process'	, 'wp_ulike_process' );		