<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	/*******************************************************
	  General Settings
	*******************************************************/
	
	$wp_ulike_general 		= array(
	  'title'  => '<i class="dashicons-before dashicons-admin-settings"></i>' . ' ' . __( 'General','alimir'),
	  'fields' => array(
			'button_type'  	=> array(
				'type'    	=> 'radio',
				'label'   	=> __( 'Button Type', 'alimir'),
				'default'	=> 'image',
				'options' 	=> array(
					'image' => __( 'Icon', 'alimir'),
					'text'  => __( 'Text', 'alimir')
				)
			),
			'button_text'   => array(
			  'default'		=> __('Like','alimir'),
			  'label' 		=> __( 'Button Text', 'alimir') . ' (' . __('Like', 'alimir') .')',
			),
			'button_text_u' => array(
			  'default'			=> __('Unlike','alimir'),
			  'label' 			=> __( 'Button Text', 'alimir') . ' (' . __('Unlike', 'alimir') .')',
			),
			'button_url'    => array(
			  'type'  		=> 'media',
			  'label' 		=> __( 'Button Icon', 'alimir') . ' (' . __('Like', 'alimir') .')',
			  'description' => __( 'Best size: 16x16','alimir')
			),
			'button_url_u'  => array(
			  'type'  		=> 'media',
			  'label' 		=> __( 'Button Icon', 'alimir') . ' (' . __('Unlike', 'alimir') .')',
			  'description' => __( 'Best size: 16x16','alimir')
			),		
			'permission_text'   => array(
			  'default'		=> __('You have not permission to unlike','alimir'),
			  'label' 		=> __( 'Permission Text', 'alimir')
			),
			'login_type'  		=> array(
			  'type'    	=> 'radio',
			  'label'   	=> __( 'Users Login Type','alimir'),
			  'default'		=> 'alert',
			  'options' 	=> array(
				'alert'   	=> __('Alert Box', 'alimir'),
				'button'	=> __('Like Button', 'alimir')
			  )
			),
			'login_text'    	=> array(
			  'default'		=> __('You Should Login To Submit Your Like','alimir'),
			  'label' 		=> __( 'Users Login Text', 'alimir')
			),
			'format_number'    	=> array(
			  'type'			=> 'checkbox',
			  'default'			=> 0,
			  'label' 			=> __('Format Number', 'alimir'),
			  'checkboxlabel' 	=> __('Activate', 'alimir'),
			  'description' 	=> __('Convert numbers of Likes with string (kilobyte) format.', 'alimir') . '<strong> (WHEN? likes>=1000)</strong>'
			)
		)
	);//end wp_ulike_general
	
	/*******************************************************
	  Posts Settings
	*******************************************************/	
		
	$wp_ulike_posts 		= array(
		'title'  			=> '<i class="dashicons-before dashicons-admin-post"></i>' . ' ' . __( 'Posts','alimir'),
		'fields' 			=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes','alimir'),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', 'alimir'),
			  'wpulike-heart'	=> __('Heart', 'alimir')
			)
		  ),
		  'auto_display'  	=> array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Automatic display', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir')
		  ),
		  'auto_display_position'  => array(
			'type'    		=> 'radio',
			'label'   		=> __( 'Auto Display Position','alimir'),
			'default'		=> 'bottom',
			'options' 		=> array(
			  'top'   		=> __('Top of Content', 'alimir'),
			  'bottom'   	=> __('Bottom of Content', 'alimir'),
			  'top_bottom' 	=> __('Top and Bottom', 'alimir')
			)
		  ),		  
		  'auto_display_filter'  => array(
			'type'    		=> 'multi',
			'label'   		=> __( 'Auto Display Filter','alimir' ),
			'options' 		=> array(
			  'home'   		=> __('Home', 'alimir'),
			  'single'   	=> __('Single Posts', 'alimir'),
			  'page'   		=> __('Pages', 'alimir'),
			  'archive'   	=> __('Archives', 'alimir'),
			  'category' 	=> __('Categories', 'alimir'),
			  'search' 		=> __('Search Results', 'alimir'),
			  'tag' 		=> __('Tags', 'alimir'),
			  'author' 		=> __('Author Page', 'alimir')
			),
			'description' => __('You can filter theses pages on auto display option.', 'alimir')
		  ),
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like posts.', 'alimir')
		  ),
		  'logging_method' 	=> array(
			'type'    		=> 'select',
			'default'		=> 'by_username',
			'label'   		=> __( 'Logging Method','alimir'),
			'options' 		=> array(
			  'do_not_log'  => __('Do Not Log', 'alimir'),
			  'by_cookie'   => __('Logged By Cookie', 'alimir'),
			  'by_ip' 		=> __('Logged By IP', 'alimir'),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', 'alimir'),
			  'by_username' => __('Logged By Username', 'alimir')
			)
		  ),
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', 'alimir')
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', 'alimir'),
			'description' 	=> __('Size of Gravatars to return (max is 512)', 'alimir')
		  ),
		  'number_of_users' => array(
			'type'			=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', 'alimir'),
			'description' 	=> __('The number of users to show in the users liked box', 'alimir')
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have LIKED this post:','alimir').'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', 'alimir'),
			'description' 	=> __('Allowed Variables:', 'alimir') . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )  
		)
	);//end wp_ulike_posts
	
	/*******************************************************
	  Comments Settings
	*******************************************************/	
		
	$wp_ulike_comments 		= 	array(
		'title'  			=> '<i class="dashicons-before dashicons-admin-comments"></i>' . ' ' . __( 'Comments','alimir'),
		'fields' 			=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes','alimir'),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', 'alimir'),
			  'wpulike-heart'	=> __('Heart', 'alimir')
			)
		  ),		
		  'auto_display'  	=> array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Automatic display', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir')
		  ),
		  'auto_display_position'  => array(
			'type'   		=> 'radio',
			'label'   		=> __( 'Auto Display Position','alimir'),
			'default'		=> 'bottom',
			'options' 	=> array(
			  'top'   		=> __('Top of Content', 'alimir'),
			  'bottom'   	=> __('Bottom of Content', 'alimir'),
			  'top_bottom' 	=> __('Top and Bottom', 'alimir')
			)
		  ),			  
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like comments.', 'alimir')
		  ),
		  'logging_method' => array(
			'type'    		=> 'select',
			'default'		=> 'by_username',
			'label'   		=> __( 'Logging Method','alimir'),
			'options' => array(
			  'do_not_log'  => __('Do Not Log', 'alimir'),
			  'by_cookie'   => __('Logged By Cookie', 'alimir'),
			  'by_ip' 		=> __('Logged By IP', 'alimir'),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', 'alimir'),
			  'by_username' => __('Logged By Username', 'alimir')
			)
		  ),		  
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', 'alimir')
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', 'alimir'),
			'description' 	=> __('Size of Gravatars to return (max is 512)', 'alimir')
		  ),		  
		  'number_of_users' => array(
			'type'  		=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', 'alimir'),
			'description'	=> __('The number of users to show in the users liked box', 'alimir')
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have LIKED this comment:','alimir').'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', 'alimir'),
			'description' 	=> __('Allowed Variables:', 'alimir') . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )  
		)
	  );//end wp_ulike_comments

	/*******************************************************
	  Customize Settings
	*******************************************************/	  

	$wp_ulike_customize 	= 	array(
		'title'  => '<i class="dashicons-before dashicons-art"></i>' . ' ' . __( 'Customize','alimir'),
		'fields' => array(
		  'custom_style'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Custom Style', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'attributes'  	=> array(
			  'class'   	=> 'wp_ulike_custom_style_activation'
			),		
			'description' 	=> __('Active this option to see the custom style settings.', 'alimir')
		  ),	
		  'btn_bg'  => array(
			'type'  		=> 'color',
			'label' 		=> __('Button style', 'alimir'),
			'description' 	=> __('Background', 'alimir')
		  ),
		  'btn_border'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Border Color', 'alimir')
		  ),
		  'btn_color'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Text Color', 'alimir')
		  ),
		  'counter_bg'  => array(
			'type'  		=> 'color',
			'label' 		=> __( 'Counter Style', 'alimir'),
			'description' 	=> __('Background', 'alimir')
		  ),
		  'counter_border'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Border Color', 'alimir')
		  ),
		  'counter_color'  => array(
			'type'  		=> 'color',
			'description' 	=> __('Text Color', 'alimir')
		  ),
		  'loading_animation'    => array(
			'type'  		=> 'media',
			'label' 		=> __( 'Loading Animation', 'alimir') . ' (.GIF)',
			'description' 	=> __( 'Best size: 16x16','alimir')
		  ),
		  'custom_css'    	=> array(
		    'type'			=> 'textarea',
		    'label' 		=> __('Custom CSS', 'alimir'),
		  )  
		)
	  );//end wp_ulike_customize
	
	/*******************************************************
	  BP Settings
	*******************************************************/
	
	$wp_ulike_buddypress 	= array();
	if ( function_exists('is_buddypress') ) {
	$wp_ulike_buddypress 	= 	array(
		'title'  			=> '<i class="dashicons-before dashicons-buddypress"></i>' . ' ' . __( 'BuddyPress','alimir'),
		'fields' 	=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes','alimir'),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', 'alimir'),
			  'wpulike-heart'	=> __('Heart', 'alimir')
			)
		  ),		
		  'auto_display'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Automatic display', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir')
		  ),
		  'auto_display_position'  => array(
			'type'    		=> 'radio',
			'label'   		=> __( 'Auto Display Position','alimir'),
			'default'		=> 'bottom',
			'options' 		=> array(
			  'content'   	=> __('Activity Content', 'alimir'),
			  'meta' 		=> __('Activity Meta', 'alimir')
			)
		  ),		  
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like activities.', 'alimir')
		  ),
		  'logging_method' => array(
			'type'    		=> 'select',
			'default'		=> 'by_cookie_ip',
			'label'   		=> __( 'Logging Method','alimir'),
			'options' => array(
			  'do_not_log'  => __('Do Not Log', 'alimir'),
			  'by_cookie'   => __('Logged By Cookie', 'alimir'),
			  'by_ip' 		=> __('Logged By IP', 'alimir'),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', 'alimir'),
			  'by_username' => __('Logged By Username', 'alimir')
			)
		  ),		    
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', 'alimir')
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', 'alimir'),
			'description'	=> __('Size of Gravatars to return (max is 512)', 'alimir')
		  ),
		  'number_of_users' => array(
			'type'  		=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', 'alimir'),
			'description' 	=> __('The number of users to show in the users liked box', 'alimir')
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have liked this activity:','alimir').'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', 'alimir'),
			'description' 	=> __('Allowed Variables:', 'alimir') . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'new_likes_activity'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('BuddyPress Activity', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('insert new likes in buddyPress activity page', 'alimir')
		  ),		  
		  'bp_post_activity_add_header'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
			'label' 		=> __('Post Activity Text', 'alimir'),
			'description' 	=> __('Allowed Variables:', 'alimir') . ' <code>%POST_LIKER%</code> , <code>%POST_PERMALINK%</code> , <code>%POST_COUNT%</code> , <code>%POST_TITLE%</code>'
		  ),
		  'bp_comment_activity_add_header'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)',
			'label' 		=> __('Comment Activity Text', 'alimir'),
			'description' 	=> __('Allowed Variables:', 'alimir') . ' <code>%COMMENT_LIKER%</code> , <code>%COMMENT_AUTHOR%</code> , <code>%COMMENT_COUNT%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', 'alimir'),
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
		'title'  			=> '<i class="dashicons-before dashicons-bbpress"></i>' . ' ' . __( 'bbPress','alimir'),
		'fields' 	=> array(
		  'theme' 			=> array(
			'type'    		=> 'select',
			'default'		=> 'default',
			'label'   		=> __( 'Themes','alimir'),
			'options' 		=> array(
			  'wpulike-default'	=> __('Default', 'alimir'),
			  'wpulike-heart'	=> __('Heart', 'alimir')
			)
		  ),		
		  'auto_display'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Automatic display', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir')
		  ),
		  'auto_display_position'  => array(
			'type'   		=> 'radio',
			'label'   		=> __( 'Auto Display Position','alimir'),
			'default'		=> 'bottom',
			'options' 	=> array(
			  'top'   		=> __('Top of Content', 'alimir'),
			  'bottom'   	=> __('Bottom of Content', 'alimir')
			)
		  ),		  
		  'only_registered_users'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 0,
			'label' 		=> __('Only registered Users', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('<strong>Only</strong> registered users have permission to like Topics.', 'alimir')
		  ),
		  'logging_method' => array(
			'type'    		=> 'select',
			'default'		=> 'by_cookie_ip',
			'label'   		=> __( 'Logging Method','alimir'),
			'options' => array(
			  'do_not_log'  => __('Do Not Log', 'alimir'),
			  'by_cookie'   => __('Logged By Cookie', 'alimir'),
			  'by_ip' 		=> __('Logged By IP', 'alimir'),
			  'by_cookie_ip'=> __('Logged By Cookie & IP', 'alimir'),
			  'by_username' => __('Logged By Username', 'alimir')
			)
		  ),
		  'users_liked_box'  => array(
			'type'  		=> 'checkbox',
			'default'		=> 1,
			'label' 		=> __('Show Liked Users Box', 'alimir'),
			'checkboxlabel' => __('Activate', 'alimir'),
			'description' 	=> __('Active this option to show liked users avatars in the bottom of button like.', 'alimir')
		  ),
		  'users_liked_box_avatar_size' => array(
			'type'  		=> 'number',
			'default'		=> 32,
			'label' 		=> __( 'Size of Gravatars', 'alimir'),
			'description'	=> __('Size of Gravatars to return (max is 512)', 'alimir')
		  ),
		  'number_of_users' => array(
			'type'  		=> 'number',
			'default'		=> 10,
			'label' 		=> __( 'Number Of The Users', 'alimir'),
			'description' 	=> __('The number of users to show in the users liked box', 'alimir')
		  ),
		  'users_liked_box_template'  => array(
			'type'  		=> 'textarea',
			'default'		=> '<br /><p style="margin-top:5px"> '.__('Users who have liked this topic:','alimir').'</p> <ul class="tiles">%START_WHILE%<li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li>%END_WHILE%</ul>',
			'label' 		=> __('Users Like Box Template', 'alimir'),
			'description' 	=> __('Allowed Variables:', 'alimir') . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>'
		  ),
		  'delete_logs' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Logs', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_logs'
		  ),		  
		  'delete_data' 	=> array(
		    'type'        	=> 'action',
		    'label'       	=> __( 'Delete All Data', 'alimir' ),
		    'description' 	=> __( 'You Are About To Delete All Likes Data. This Action Is Not Reversible.', 'alimir'),
		    'action'      	=> 'wp_ulike_delete_all_data'
		  )	  
		)
	  );//end wp_ulike_buddypress
	}