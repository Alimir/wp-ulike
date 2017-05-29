<?php
	
	/**
	 * wp_ulike function for posts like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           1.0
	 * @updated         2.3
	 * @return			String
	 */
	function wp_ulike($arg) {
		//global variables
		global $post,$wp_ulike_class,$wp_user_IP;
		
		$post_ID 		= $post->ID;
		$get_post_meta 	= get_post_meta($post_ID, '_liked', true);
		$get_like 		= $get_post_meta != '' ? $get_post_meta : 0;
		$return_userID 	= $wp_ulike_class->get_reutrn_id();
		$theme_class 	= wp_ulike_get_setting( 'wp_ulike_posts', 'theme');		
		
		if(
			(wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users') != '1')
		or
			(wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users') == '1' && is_user_logged_in())
		){
		
		$data = array(
			"id" 		=> $post_ID,				//Post ID
			"user_id" 	=> $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip" 	=> $wp_user_IP,				//User IP
			"get_like" 	=> $get_like,				//Number Of Likes
			"method" 	=> 'likeThis',				//JavaScript method
			"setting" 	=> 'wp_ulike_posts',		//Setting Key
			"type" 		=> 'post',					//Function type (post/process)
			"table" 	=> 'ulike',					//posts table
			"column" 	=> 'post_id',				//ulike table column name			
			"key" 		=> '_liked',				//meta key
			"cookie" 	=> 'liked-'					//Cookie Name
		);		
		
		//call wp_get_ulike function from class-ulike calss
		$counter 		= $wp_ulike_class->wp_get_ulike($data);
		
		$wp_ulike 		= '<div id="wp-ulike-'.$post_ID.'" class="wpulike '.$theme_class.'">';
		$wp_ulike  		.= '<div class="counter">'.$counter.'</div>';
		$wp_ulike  		.= '</div>';
		$wp_ulike  		.= $wp_ulike_class->get_liked_users($post_ID,'ulike','post_id','wp_ulike_posts');
		
		if ($arg == 'put') {
			return $wp_ulike;
		}
		else {
			echo $wp_ulike;
		}
		
		}//end !only_registered_users condition
		
		else if (wp_ulike_get_setting( 'wp_ulike_posts', 'only_registered_users') == '1' && !is_user_logged_in()){
			$login_type = wp_ulike_get_setting( 'wp_ulike_general', 'login_type');
			if($login_type == "button"){
				$template = $wp_ulike_class->get_template($post_ID,'likeThis',$get_like,1,0);
				if (wp_ulike_get_setting( 'wp_ulike_general', 'button_type') == 'image') {
					return '<div id="wp-ulike-'.$post_ID.'" class="wpulike '.$theme_class.'"><div class="counter">' . $template['login_img'] . '</div></div>';		
				}
				else {
					return '<div id="wp-ulike-'.$post_ID.'" class="wpulike '.$theme_class.'"><div class="counter">' . $template['login_text'] . '</div></div>';	
				}
			}
			else
				return '<p class="alert alert-info fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>'.__('You need to login in order to like this post: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>';
		}//end only_registered_users condition
		
	}
	
	/**
	 * wp_ulike_comments function for comments like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           1.6
	 * @updated         2.3
	 * @return			String
	 */
	function wp_ulike_comments($arg) {
		//global variables
		global $wp_ulike_class,$wp_user_IP;
		
		$CommentID 		= get_comment_ID();
		$comment_meta 	= get_comment_meta($CommentID, '_commentliked', true);
		$get_like 		= $comment_meta != '' ? $comment_meta : 0;
		$return_userID 	= $wp_ulike_class->get_reutrn_id();
		$theme_class 	= wp_ulike_get_setting( 'wp_ulike_comments', 'theme');
		
		if(
		(wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users') != '1')
		or
		(wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users') == '1' && is_user_logged_in())
		){
		
		$data = array(
			"id" 		=> $CommentID,				//Comment ID
			"user_id" 	=> $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip" 	=> $wp_user_IP,				//User IP
			"get_like" 	=> $get_like,				//Number Of Likes
			"method" 	=> 'likeThisComment',		//JavaScript method
			"setting" 	=> 'wp_ulike_comments',		//Setting Key
			"type" 		=> 'post',					//Function type (post/process)
			"table" 	=> 'ulike_comments',		//Comments table
			"column"	=> 'comment_id',			//ulike_comments table column name			
			"key" 		=> '_commentliked',			//meta key
			"cookie" 	=> 'comment-liked-'			//Cookie Name
		);		
		
		//call wp_get_ulike function from class-ulike calss
		$counter 		= $wp_ulike_class->wp_get_ulike($data);		
		
		$wp_ulike 		= '<div id="wp-ulike-comment-'.$CommentID.'" class="wpulike '.$theme_class.'">';
		$wp_ulike 		.= '<div class="counter">'.$counter.'</div>';
		$wp_ulike 		.= '</div>';
		$wp_ulike  		.= $wp_ulike_class->get_liked_users($CommentID,'ulike_comments','comment_id','wp_ulike_comments');
		
		if ($arg == 'put') {
			return $wp_ulike;
		}
		else {
			echo $wp_ulike;
		}
		
		}//end !only_registered_users condition
		
		else if (wp_ulike_get_setting( 'wp_ulike_comments', 'only_registered_users') == '1' && !is_user_logged_in()){
			$login_type = wp_ulike_get_setting( 'wp_ulike_general', 'login_type');
			if($login_type == "button"){
				$template = $wp_ulike_class->get_template($CommentID,'likeThisComment',$get_like,1,0);
				if (wp_ulike_get_setting( 'wp_ulike_general', 'button_type') == 'image') {
					return '<div id="wp-ulike-comment-'.$CommentID.'" class="wpulike '.$theme_class.'"><div class="counter">' . $template['login_img'] . '</div></div>';		
				}
				else {
					return '<div id="wp-ulike-comment-'.$CommentID.'" class="wpulike '.$theme_class.'"><div class="counter">' . $template['login_text'] . '</div></div>';	
				}
			}
			else
				return '<p class="alert alert-info fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>'.__('You need to login in order to like this comment: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>';	
		}//end only_registered_users condition
		
	}	
	
	/**
	 * wp_ulike_buddypress function for activities like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.3
	 * @updated         2.4
	 * @return			String
	 */
	function wp_ulike_buddypress($arg) {
		//global variables
		global $wp_ulike_class,$wp_user_IP;
        
        if ( bp_get_activity_comment_id() != null )
            $activityID 	= bp_get_activity_comment_id();
        else
            $activityID 	= bp_get_activity_id();            

		$bp_get_meta	= bp_activity_get_meta($activityID, '_activityliked');
		$get_like 		= $bp_get_meta != '' ? $bp_get_meta : 0;
		$return_userID 	= $wp_ulike_class->get_reutrn_id();
		$theme_class 	= wp_ulike_get_setting( 'wp_ulike_buddypress', 'theme');
		
		if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display_position' ) == 'meta')
		$html_tag = 'span';
		else
		$html_tag = 'div';		
	
		if(
		(wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users') != '1')
		or
		(wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users') == '1' && is_user_logged_in())
		){
		
		$data = array(
			"id" 		=> $activityID,				//Activity ID
			"user_id" 	=> $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip" 	=> $wp_user_IP,				//User IP
			"get_like" 	=> $get_like,				//Number Of Likes
			"method" 	=> 'likeThisActivity',		//JavaScript method
			"setting" 	=> 'wp_ulike_buddypress',	//Setting Key
			"type" 		=> 'post',					//Function type (post/process)
			"table" 	=> 'ulike_activities',		//Activities table
			"column"	=> 'activity_id',			//ulike_activities table column name			
			"key" 		=> '_activityliked',		//meta key
			"cookie" 	=> 'activity-liked-'		//Cookie Name
		);	
	
		//call wp_get_ulike function from class-ulike calss
		$counter 		= $wp_ulike_class->wp_get_ulike($data);
		
		$wp_ulike 		= '<'.$html_tag.' id="wp-ulike-activity-'.$activityID.'" class="wpulike '.$theme_class.'">';
		$wp_ulike 		.= '<'.$html_tag.' class="counter">'.$counter.'</'.$html_tag.'>';
		$wp_ulike 		.= '</'.$html_tag.'>';
		$wp_ulike  		.= $wp_ulike_class->get_liked_users($activityID,'ulike_activities','activity_id','wp_ulike_buddypress');
		
		if ($arg == 'put') {
			return $wp_ulike;
		}
		else {
			echo $wp_ulike;
		}
		
		}//end !only_registered_users condition
		
		else if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'only_registered_users') == '1' && !is_user_logged_in()){
			$login_type = wp_ulike_get_setting( 'wp_ulike_general', 'login_type');
			if($login_type == "button"){
				$template = $wp_ulike_class->get_template($activityID,'likeThisActivity',$get_like,1,0);
				if (wp_ulike_get_setting( 'wp_ulike_general', 'button_type') == 'image') {
					return '<'.$html_tag.' id="wp-ulike-activity-'.$activityID.'" class="wpulike '.$theme_class.'"><'.$html_tag.' class="counter">' . $template['login_img'] . '</'.$html_tag.'></'.$html_tag.'>';		
				}
				else {
					return '<'.$html_tag.' id="wp-ulike-activity-'.$activityID.'" class="wpulike '.$theme_class.'"><'.$html_tag.' class="counter">' . $template['login_text'] . '</'.$html_tag.'></'.$html_tag.'>';	
				}
			}
			else		
				return '<p class="alert alert-info fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>'.__('You need to login in order to like this activity: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>';
		}//end only_registered_users condition
		
	}	
	
	/**
	 * wp_ulike_bbpress function for topics like/unlike display
	 *
	 * @author       	Alimir	 	
	 * @since           2.2
	 * @updated         2.3
	 * @updated         2.4.1
	 * @return			String
	 */
	function wp_ulike_bbpress($arg) {
		//global variables
		global $post,$wp_ulike_class,$wp_user_IP;
        
        //Thanks to @Yehonal for this commit
        $replyID        = bbp_get_reply_id();
        $post_ID 		= !$replyID ? $post->ID : $replyID;

		$get_post_meta 	= get_post_meta($post_ID, '_topicliked', true);
		$get_like 		= $get_post_meta != '' ? $get_post_meta : 0;
		$return_userID 	= $wp_ulike_class->get_reutrn_id();
		$theme_class 	= wp_ulike_get_setting( 'wp_ulike_bbpress', 'theme');		
		
		if(
			(wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users') != '1')
		or
			(wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users') == '1' && is_user_logged_in())
		){
		
		$data = array(
			"id" 		=> $post_ID,				//Post ID
			"user_id" 	=> $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip" 	=> $wp_user_IP,				//User IP
			"get_like" 	=> $get_like,				//Number Of Likes
			"method" 	=> 'likeThisTopic',			//JavaScript method
			"setting" 	=> 'wp_ulike_bbpress',		//Setting Key
			"type" 		=> 'post',					//Function type (post/process)
			"table" 	=> 'ulike_forums',			//posts table
			"column" 	=> 'topic_id',				//ulike table column name			
			"key" 		=> '_topicliked',			//meta key
			"cookie" 	=> 'topic-liked-'			//Cookie Name
		);		
		
		//call wp_get_ulike function from class-ulike calss
		$counter 		= $wp_ulike_class->wp_get_ulike($data);
		
		$wp_ulike 		= '<div id="wp-ulike-'.$post_ID.'" class="wpulike '.$theme_class.'">';
		$wp_ulike  		.= '<div class="counter">'.$counter.'</div>';
		$wp_ulike  		.= '</div>';
		$wp_ulike  		.= $wp_ulike_class->get_liked_users($post_ID,'ulike_forums','topic_id','wp_ulike_bbpress');
		
		if ($arg == 'put') {
			return $wp_ulike;
		}
		else {
			echo $wp_ulike;
		}
		
		}//end !only_registered_users condition
		
		else if (wp_ulike_get_setting( 'wp_ulike_bbpress', 'only_registered_users') == '1' && !is_user_logged_in()){
			$login_type = wp_ulike_get_setting( 'wp_ulike_general', 'login_type');
			if($login_type == "button"){
				$template = $wp_ulike_class->get_template($post_ID,'likeThisTopic',$get_like,1,0);
				if (wp_ulike_get_setting( 'wp_ulike_general', 'button_type') == 'image') {
					return '<div id="wp-ulike-'.$post_ID.'" class="wpulike '.$theme_class.'"><div class="counter">' . $template['login_img'] . '</div></div>';		
				}
				else {
					return '<div id="wp-ulike-'.$post_ID.'" class="wpulike '.$theme_class.'"><div class="counter">' . $template['login_text'] . '</div></div>';	
				}
			}
			else
				return '<p class="alert alert-info fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>'.__('You need to login in order to like this post: ',WP_ULIKE_SLUG).'<a href="'.wp_login_url( get_permalink() ).'"> '.__('click here',WP_ULIKE_SLUG).' </a></p>';
		}//end only_registered_users condition
		
	}	

	/**
	 * wp_ulike_process function for all like/unlike process
	 *
	 * @author       	Alimir	 	
	 * @since           1.0
	 * @updated         2.2
	 * @updated         2.4.1
	 * @return			String
	 */
	function wp_ulike_process(){
	
		global $wp_ulike_class,$wp_user_IP;
		$post_ID 		= $_POST['id'];
		$post_type 		= $_POST['type'];
		
		if($post_type == 'likeThis'){
			$get_meta_data 	= get_post_meta($post_ID, '_liked', true);
			$setting_key	= 'wp_ulike_posts';
			$table_name		= 'ulike';
			$column_name	= 'post_id';
			$meta_key		= '_liked';
			$cookie_name	= 'liked-';
		}
		else if($post_type == 'likeThisComment'){
			$get_meta_data 	= get_comment_meta($post_ID, '_commentliked', true);
			$setting_key	= 'wp_ulike_comments';
			$table_name		= 'ulike_comments';
			$column_name	= 'comment_id';
			$meta_key		= '_commentliked';
			$cookie_name	= 'comment-liked-';		
		}
		else if($post_type == 'likeThisActivity'){
			$get_meta_data 	= bp_activity_get_meta($post_ID, '_activityliked');
			$setting_key	= 'wp_ulike_buddypress';
			$table_name		= 'ulike_activities';
			$column_name	= 'activity_id';
			$meta_key		= '_activityliked';
			$cookie_name	= 'activity-liked-';		
		}
		else if($post_type == 'likeThisTopic'){
			$get_meta_data 	= get_post_meta($post_ID, '_topicliked', true);
			$setting_key	= 'wp_ulike_bbpress';
			$table_name		= 'ulike_forums';
			$column_name	= 'topic_id';
			$meta_key		= '_topicliked';
			$cookie_name	= 'topic-liked-';		
		}
		else{
			wp_die(__('Error: This Method Is Not Exist!',WP_ULIKE_SLUG));
		}
		
		
		$get_like 		= $get_meta_data != '' ? $get_meta_data : 0;
		$return_userID 	= $wp_ulike_class->get_reutrn_id();
		
		$data = array(
			"id" 			=> $post_ID,				//Post ID
			"user_id" 	=> $return_userID,			//User ID (if the user is guest, we save ip as user_id with "ip2long" function)
			"user_ip" 	=> $wp_user_IP,				//User IP
			"get_like" 	=> $get_like,				//Number Of Likes
			"method" 	=> $post_type,				//JavaScript method
			"setting" 	=> $setting_key,			//Setting Key
			"type" 		=> 'process',				//Function type (post/process)
			"table" 		=> $table_name,				//posts table
			"column" 	=> $column_name,			//ulike table column name			
			"key" 		=> $meta_key,				//meta key
			"cookie" 	=> $cookie_name				//Cookie Name
		);
		
		$response = new WP_Ajax_Response;
		
		if($post_ID != null) {
			$response->add(
				array(
					'what'	=>'wpulike',
					'action'	=>'wp_ulike_process',
					'id'		=> $post_ID,
					'data'		=> $wp_ulike_class->wp_get_ulike($data)
				)
			);
		}
		
		// Whatever the outcome, send the Response back
		$response->send();

		// Always exit when doing Ajax
		exit();
	}