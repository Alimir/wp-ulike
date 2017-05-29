<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	/*******************************************************
	  General Settings
	*******************************************************/
	
	$wp_ulike_general 		= array(
	  'title'  => '<i class="dashicons-before dashicons-admin-settings"></i>' . ' ' . __( 'General',WP_ULIKE_SLUG),
	  'fields' => array(
			'button_type'  	=> array(
				'type'    	=> 'radio',
				'label'   	=> __( 'Button Type', WP_ULIKE_SLUG),
				'default'	=> 'image',
				'options' 	=> array(
					'image' => __( 'Icon', WP_ULIKE_SLUG),
					'text'  => __( 'Text', WP_ULIKE_SLUG)
				)
			),
			'button_text'   => array(
			  'default'		=> __('Like',WP_ULIKE_SLUG),
			  'label' 		=> __( 'Button Text', WP_ULIKE_SLUG) . ' (' . __('Like', WP_ULIKE_SLUG) .')',
			),
			'button_text_u' => array(
			  'default'			=> __('Unlike',WP_ULIKE_SLUG),
			  'label' 			=> __( 'Button Text', WP_ULIKE_SLUG) . ' (' . __('Unlike', WP_ULIKE_SLUG) .')',
			),
			'button_url'    => array(
			  'type'  		=> 'media',
			  'label' 		=> __( 'Button Icon', WP_ULIKE_SLUG) . ' (' . __('Like', WP_ULIKE_SLUG) .')',
			  'description' => __( 'Best size: 16x16',WP_ULIKE_SLUG)
			),
			'button_url_u'  => array(
			  'type'  		=> 'media',
			  'label' 		=> __( 'Button Icon', WP_ULIKE_SLUG) . ' (' . __('Unlike', WP_ULIKE_SLUG) .')',
			  'description' => __( 'Best size: 16x16',WP_ULIKE_SLUG)
			),		
			'permission_text'   => array(
			  'default'		=> __('You have not permission to unlike',WP_ULIKE_SLUG),
			  'label' 		=> __( 'Permission Text', WP_ULIKE_SLUG)
			),
			'login_type'  		=> array(
			  'type'    	=> 'radio',
			  'label'   	=> __( 'Users Login Type',WP_ULIKE_SLUG),
			  'default'		=> 'alert',
			  'options' 	=> array(
				'alert'   	=> __('Alert Box', WP_ULIKE_SLUG),
				'button'	=> __('Like Button', WP_ULIKE_SLUG)
			  )
			),
			'login_text'    	=> array(
			  'default'		=> __('You Should Login To Submit Your Like',WP_ULIKE_SLUG),
			  'label' 		=> __( 'Users Login Text', WP_ULIKE_SLUG)
			),
			'format_number'    	=> array(
			  'type'			=> 'checkbox',
			  'default'			=> 0,
			  'label' 			=> __('Format Number', WP_ULIKE_SLUG),
			  'checkboxlabel' 	=> __('Activate', WP_ULIKE_SLUG),
			  'description' 	=> __('Convert numbers of Likes with string (kilobyte) format.', WP_ULIKE_SLUG) . '<strong> (WHEN? likes>=1000)</strong>'
			),
			'notifications'    	=> array(
			  'type'			=> 'checkbox',
			  'default'			=> 0,
			  'label' 			=> __('Notifications', WP_ULIKE_SLUG),
			  'checkboxlabel' 	=> __('Activate', WP_ULIKE_SLUG),
			  'description' 	=> __('Custom toast messages after each activity', WP_ULIKE_SLUG)
			),
			'like_notice'    	=> array(
			  'default'		=> __('Thanks! You Liked This.',WP_ULIKE_SLUG),
			  'label' 		=> __( 'Liked Notice Message', WP_ULIKE_SLUG)
			),
			'unlike_notice'    	=> array(
			  'default'		=> __('Sorry! You unliked this.',WP_ULIKE_SLUG),
			  'label' 		=> __( 'Unliked Notice Message', WP_ULIKE_SLUG)
			)		  
		)
	);//end wp_ulike_general
	
	/*******************************************************
	  Posts Settings
	*******************************************************/	
		
	$wp_ulike_posts 		= array(
		'title'  			=> '<i class="dashicons-before dashicons-admin-post"></i>' . ' ' . __( 'Posts',WP_ULIKE_SLUG),
		'fields' 			=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes',WP_ULIKE_SLUG),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', WP_ULIKE_SLUG),
			  'wpulike-heart'	=> __('Heart', WP_ULIKE_SLUG)
			)
		  ),
		  'auto_display'  	=> array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Automatic display', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG)
		  ),
		  'auto_display_position'  => array(
			'type'    		=> 'radio',
			'label'   		=> __( 'Auto Display Position',WP_ULIKE_SLUG),
			'default'		=> 'bottom',
			'options' 		=> array(
			  'top'   		=> __('Top of Content', WP_ULIKE_SLUG),
			  'bottom'   	=> __('Bottom of Content', WP_ULIKE_SLUG),
			  'top_bottom' 	=> __('Top and Bottom', WP_ULIKE_SLUG)
			)
		  ),		  
		  'auto_display_filter'  => array(
			'type'    		=> 'multi',
			'label'   		=> __( 'Auto Display Filter',WP_ULIKE_SLUG ),
			'options' 		=> array(
			  'home'   		=> __('Home', WP_ULIKE_SLUG),
			  'single'   	=> __('Single Posts', WP_ULIKE_SLUG),
			  'page'   		=> __('Pages', WP_ULIKE_SLUG),
			  'archive'   	=> __('Archives', WP_ULIKE_SLUG),
			  'category' 	=> __('Categories', WP_ULIKE_SLUG),
			  'search' 		=> __('Search Results', WP_ULIKE_SLUG),
			  'tag' 		=> __('Tags', WP_ULIKE_SLUG),
			  'author' 		=> __('Author Page', WP_ULIKE_SLUG)
			),
			'description' => __('You can filter theses pages on auto display option.', WP_ULIKE_SLUG)
		  ),
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like posts.', WP_ULIKE_SLUG)
		  ),
		  'logging_method' 	=> array(
			'type'    		=> 'select',
			'default'		=> 'by_username',
			'label'   		=> __( 'Logging Method',WP_ULIKE_SLUG),
			'options' 		=> array(
			  'do_not_log'  => __('Do Not Log', WP_ULIKE_SLUG),
			  'by_cookie'   => __('Logged By Cookie', WP_ULIKE_SLUG),
			  'by_ip' 		=> __('Logged By IP', WP_ULIKE_SLUG),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', WP_ULIKE_SLUG),
			  'by_username' => __('Logged By Username', WP_ULIKE_SLUG)
			)
		  ),
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', WP_ULIKE_SLUG),
			'description' 	=> __('Size of Gravatars to return (max is 512)', WP_ULIKE_SLUG)
		  ),
		  'number_of_users' => array(
			'type'			=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', WP_ULIKE_SLUG),
			'description' 	=> __('The number of users to show in the users liked box', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have LIKED this post:',WP_ULIKE_SLUG).'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', WP_ULIKE_SLUG),
			'description' 	=> __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )  
		)
	);//end wp_ulike_posts
	
	/*******************************************************
	  Comments Settings
	*******************************************************/	
		
	$wp_ulike_comments 		= 	array(
		'title'  			=> '<i class="dashicons-before dashicons-admin-comments"></i>' . ' ' . __( 'Comments',WP_ULIKE_SLUG),
		'fields' 			=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes',WP_ULIKE_SLUG),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', WP_ULIKE_SLUG),
			  'wpulike-heart'	=> __('Heart', WP_ULIKE_SLUG)
			)
		  ),		
		  'auto_display'  	=> array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Automatic display', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG)
		  ),
		  'auto_display_position'  => array(
			'type'   		=> 'radio',
			'label'   		=> __( 'Auto Display Position',WP_ULIKE_SLUG),
			'default'		=> 'bottom',
			'options' 	=> array(
			  'top'   		=> __('Top of Content', WP_ULIKE_SLUG),
			  'bottom'   	=> __('Bottom of Content', WP_ULIKE_SLUG),
			  'top_bottom' 	=> __('Top and Bottom', WP_ULIKE_SLUG)
			)
		  ),			  
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like comments.', WP_ULIKE_SLUG)
		  ),
		  'logging_method' => array(
			'type'    		=> 'select',
			'default'		=> 'by_username',
			'label'   		=> __( 'Logging Method',WP_ULIKE_SLUG),
			'options' => array(
			  'do_not_log'  => __('Do Not Log', WP_ULIKE_SLUG),
			  'by_cookie'   => __('Logged By Cookie', WP_ULIKE_SLUG),
			  'by_ip' 		=> __('Logged By IP', WP_ULIKE_SLUG),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', WP_ULIKE_SLUG),
			  'by_username' => __('Logged By Username', WP_ULIKE_SLUG)
			)
		  ),		  
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', WP_ULIKE_SLUG),
			'description' 	=> __('Size of Gravatars to return (max is 512)', WP_ULIKE_SLUG)
		  ),		  
		  'number_of_users' => array(
			'type'  		=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', WP_ULIKE_SLUG),
			'description'	=> __('The number of users to show in the users liked box', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have LIKED this comment:',WP_ULIKE_SLUG).'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', WP_ULIKE_SLUG),
			'description' 	=> __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )  
		)
	  );//end wp_ulike_comments

	/*******************************************************
	  Customize Settings
	*******************************************************/	  

	$wp_ulike_customize 	= 	array(
		'title'  => '<i class="dashicons-before dashicons-art"></i>' . ' ' . __( 'Customize',WP_ULIKE_SLUG),
		'fields' => array(
		  'custom_style'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Custom Style', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'attributes'  	=> array(
			  'class'   	=> 'wp_ulike_custom_style_activation'
			),		
			'description' 	=> __('Active this option to see the custom style settings.', WP_ULIKE_SLUG)
		  ),	
		  'btn_bg'  => array(
			'type'  		=> 'color',
			'label' 		=> __('Button style', WP_ULIKE_SLUG),
			'description' 	=> __('Background', WP_ULIKE_SLUG)
		  ),
		  'btn_border'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Border Color', WP_ULIKE_SLUG)
		  ),
		  'btn_color'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Text Color', WP_ULIKE_SLUG)
		  ),
		  'counter_bg'  => array(
			'type'  		=> 'color',
			'label' 		=> __( 'Counter Style', WP_ULIKE_SLUG),
			'description' 	=> __('Background', WP_ULIKE_SLUG)
		  ),
		  'counter_border'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Border Color', WP_ULIKE_SLUG)
		  ),
		  'counter_color'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Text Color', WP_ULIKE_SLUG)
		  ),
		  'loading_animation'    => array(
			'type'  		=> 'media',
			'label' 		=> __( 'Loading Animation', WP_ULIKE_SLUG) . ' (.GIF)',
			'description' 	=> __( 'Best size: 16x16',WP_ULIKE_SLUG)
		  ),
		  'custom_css'    	=> array(
		    'type'			=> 'textarea',
		    'label' 		=> __('Custom CSS', WP_ULIKE_SLUG),
		  )  
		)
	  );//end wp_ulike_customize
	
	/*******************************************************
	  BP Settings
	*******************************************************/
	
	$wp_ulike_buddypress 	= array();
	if ( function_exists('is_buddypress') ) {
	$wp_ulike_buddypress 	= 	array(
		'title'  			=> '<i class="dashicons-before dashicons-buddypress"></i>' . ' ' . __( 'BuddyPress',WP_ULIKE_SLUG),
		'fields' 	=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes',WP_ULIKE_SLUG),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', WP_ULIKE_SLUG),
			  'wpulike-heart'	=> __('Heart', WP_ULIKE_SLUG)
			)
		  ),
		  'auto_display'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Automatic display', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG)
		  ),
		  'auto_display_position'  => array(
			'type'    		=> 'radio',
			'label'   		=> __( 'Auto Display Position',WP_ULIKE_SLUG),
			'default'		=> 'bottom',
			'options' 		=> array(
			  'content'   	=> __('Activity Content', WP_ULIKE_SLUG),
			  'meta' 		=> __('Activity Meta', WP_ULIKE_SLUG)
			)
		  ),
		  'activity_comment'=> array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Activity Comment', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
            'description' 	=> __('Add the possibility to like Buddypress comments in the activity stream', WP_ULIKE_SLUG)
		  ),            
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like activities.', WP_ULIKE_SLUG)
		  ),
		  'logging_method' => array(
			'type'    		=> 'select',
			'default'		=> 'by_cookie_ip',
			'label'   		=> __( 'Logging Method',WP_ULIKE_SLUG),
			'options' => array(
			  'do_not_log'  => __('Do Not Log', WP_ULIKE_SLUG),
			  'by_cookie'   => __('Logged By Cookie', WP_ULIKE_SLUG),
			  'by_ip' 		=> __('Logged By IP', WP_ULIKE_SLUG),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', WP_ULIKE_SLUG),
			  'by_username' => __('Logged By Username', WP_ULIKE_SLUG)
			)
		  ),		    
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', WP_ULIKE_SLUG),
			'description'	=> __('Size of Gravatars to return (max is 512)', WP_ULIKE_SLUG)
		  ),
		  'number_of_users' => array(
			'type'  		=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', WP_ULIKE_SLUG),
			'description' 	=> __('The number of users to show in the users liked box', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have liked this activity:',WP_ULIKE_SLUG).'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', WP_ULIKE_SLUG),
			'description' 	=> __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'new_likes_activity'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('BuddyPress Activity', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('insert new likes in buddyPress activity page', WP_ULIKE_SLUG)
		  ),
		  'custom_notification'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('BuddyPress Custom Notification', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('Sends out notifications when you get a like from someone', WP_ULIKE_SLUG)
		  ),				
		  'bp_post_activity_add_header'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
			'label' 		=> __('Post Activity Text', WP_ULIKE_SLUG),
			'description' 	=> __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%POST_LIKER%</code> , <code>%POST_PERMALINK%</code> , <code>%POST_COUNT%</code> , <code>%POST_TITLE%</code>'
		  ),
		  'bp_comment_activity_add_header'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)',
			'label' 		=> __('Comment Activity Text', WP_ULIKE_SLUG),
			'description' 	=> __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%COMMENT_LIKER%</code> , <code>%COMMENT_AUTHOR%</code> , <code>%COMMENT_COUNT%</code>, <code>%COMMENT_PERMALINK%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )  
		)
	  );//end wp_ulike_buddypress
	}

	/*******************************************************
	  bbPress Settings
	*******************************************************/	
		
	$wp_ulike_bbpress	= array();
	if (function_exists('is_bbpress')){
	$wp_ulike_bbpress	= array(
		'title'  			=> '<i class="dashicons-before dashicons-bbpress"></i>' . ' ' . __( 'bbPress',WP_ULIKE_SLUG),
		'fields' 	=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes',WP_ULIKE_SLUG),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', WP_ULIKE_SLUG),
			  'wpulike-heart'	=> __('Heart', WP_ULIKE_SLUG)
			)
		  ),		
		  'auto_display'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Automatic display', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG)
		  ),
		  'auto_display_position'  => array(
			'type'   		=> 'radio',
			'label'   		=> __( 'Auto Display Position',WP_ULIKE_SLUG),
			'default'		=> 'bottom',
			'options' 	=> array(
			  'top'   		=> __('Top of Content', WP_ULIKE_SLUG),
			  'bottom'   	=> __('Bottom of Content', WP_ULIKE_SLUG)
			)
		  ),		  
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like Topics.', WP_ULIKE_SLUG)
		  ),
		  'logging_method' => array(
			'type'    		=> 'select',
			'default'		=> 'by_cookie_ip',
			'label'   		=> __( 'Logging Method',WP_ULIKE_SLUG),
			'options' => array(
			  'do_not_log'  => __('Do Not Log', WP_ULIKE_SLUG),
			  'by_cookie'   => __('Logged By Cookie', WP_ULIKE_SLUG),
			  'by_ip' 		=> __('Logged By IP', WP_ULIKE_SLUG),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', WP_ULIKE_SLUG),
			  'by_username' => __('Logged By Username', WP_ULIKE_SLUG)
			)
		  ),
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', WP_ULIKE_SLUG),
			'checkboxlabel' => __('Activate', WP_ULIKE_SLUG),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', WP_ULIKE_SLUG),
			'description'	=> __('Size of Gravatars to return (max is 512)', WP_ULIKE_SLUG)
		  ),
		  'number_of_users' => array(
			'type'  		=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', WP_ULIKE_SLUG),
			'description' 	=> __('The number of users to show in the users liked box', WP_ULIKE_SLUG)
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have liked this topic:',WP_ULIKE_SLUG).'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', WP_ULIKE_SLUG),
			'description' 	=> __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', WP_ULIKE_SLUG ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', WP_ULIKE_SLUG),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )	  
		)
	  );//end wp_ulike_buddypress
	}