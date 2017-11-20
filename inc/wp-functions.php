<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed
	
/*******************************************************
  Posts Likes Functions
*******************************************************/

	/**
	 * Create ShortCode: 	[wp_ulike]
	 *
	 * @author       	Alimir
	 * @since           1.4
	 * @updated         3.0
	 * @return			wp ulike button
	 */
	add_shortcode( 'wp_ulike', 'wp_ulike_shortcode' );	
	function  wp_ulike_shortcode( $atts, $content = null ){
		// Final result
		$result = '';
		// Default Args
		$args   = shortcode_atts( array(
					"for"        => 'post',	//shortcode Type (post, comment, activity, topic)
					"id"         => '',		//Post ID
					"slug"       => 'post',	//Slug Name
					"style"      => '',		//Get Default Theme
					"attributes" => ''		//Get Attributes Filter
			    ), $atts );

	    switch ( $args['for'] ) {
	    	case 'comment':
	    		$result = $content . wp_ulike_comments( 'put', array_filter( $args ) );
	    		break;
	    	
	    	case 'activity':
	    		$result = $content . wp_ulike_buddypress( 'put', array_filter( $args ) );
	    		break;
	    	
	    	case 'topic':
	    		$result = $content . wp_ulike_bbpress( 'put', array_filter( $args ) );
	    		break;
	    	
	    	default:
	    		$result = $content . wp_ulike( 'put', array_filter( $args ) );
	    }

		return $result;
	}
		
	/**
	 * Auto insert wp_ulike function in the posts/pages content
	 *
	 * @author       	Alimir	 	
	 * @param           String $content	 
	 * @since           1.0	 
	 * @updated         1.9		  
	 * @return			filter on "the_content"
	 */		
	if (wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display' ) == '1') {
		function wp_ulike_put_posts($content) {
			//auto display position
			$position = wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display_position');
			$button = '';
			
			//add wp_ulike function
			if(	!is_feed() && is_wp_ulike( wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display_filter') ) ){
				$button = wp_ulike('put');
			}
			
			//return by position
			if($position=='bottom')
			return $content . $button;
			else if($position=='top')
			return $button . $content;
			else if($position=='top_bottom')
			return $button . $content . $button;
			else
			return $content . $button;
		}

		add_filter('the_content', 'wp_ulike_put_posts');
	}
	
	/**
	 * Check wp ulike callback
	 *
	 * @author       	Alimir
	 * @since           1.9
	 * @since           1.9
	 * @updated         3.0
	 * @return			boolean
	 */		
	function is_wp_ulike( $options, $args = array() ){

		$defaults = array(
			'is_home'     => is_home() && $options['home'] == '1',
			'is_single'   => is_single() && $options['single'] == '1',
			'is_page'     => is_page() && $options['page'] == '1',
			'is_archive'  => is_archive() && $options['archive'] == '1',
			'is_category' => is_category() && $options['category'] == '1',
			'is_search'   => is_search() && $options['search'] == '1',
			'is_tag'      => is_tag() && $options['tag'] == '1',
			'is_author'   => is_author() && $options['author'] == '1',
			'is_bp'       => function_exists('is_buddypress') && is_buddypress() && isset( $options['buddypress'] ) && $options['buddypress'] == '1',
			'is_bbpress'  => function_exists('is_bbpress') && is_bbpress() && isset( $options['bbpress'] ) && $options['bbpress'] == '1',
			'is_wc'       => function_exists('is_woocommerce') && is_woocommerce() && isset( $options['woocommerce'] ) && $options['woocommerce'] == '1',
		);

		$parsed_args = wp_parse_args( $args, $defaults );

		foreach ( $parsed_args as $key => $value ) {
			if( $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get The Post like number
	 *
	 * @author       	Alimir
	 * @param           Integer $post_ID	 
	 * @since           1.7 
	 * @return          String
	 */
	function wp_ulike_get_post_likes($post_ID){
		$val = get_post_meta($post_ID, '_liked', true);
		return wp_ulike_format_number($val);
	}

	/**
	 * Add itemtype to wp_ulike_posts_add_attr filter
	 *
	 * @author       	Alimir
	 * @since           2.7 
	 * @return          String
	 */
	add_filter('wp_ulike_posts_add_attr', 'wp_ulike_get_posts_microdata_itemtype');
	function wp_ulike_get_posts_microdata_itemtype(){
		$get_ulike_count = get_post_meta(get_the_ID(), '_liked', true);
		if(!is_singular() || !wp_ulike_get_setting( 'wp_ulike_posts', 'google_rich_snippets') || $get_ulike_count == 0) return;
		return 'itemscope itemtype="http://schema.org/CreativeWork"';
	}
	
	/**
	 * Add rich snippet for ratings in form of schema.org
	 *
	 * @author       	Alimir
	 * @since           2.7 
	 * @updated         2.8 // Replaced 'mysql2date' with 'get_post_time' function
	 * @return          String
	 */
	add_filter( 'wp_ulike_posts_microdata', 'wp_ulike_get_posts_microdata');
	function wp_ulike_get_posts_microdata(){
		$get_ulike_count = get_post_meta(get_the_ID(), '_liked', true);
		if(!is_singular() || !wp_ulike_get_setting( 'wp_ulike_posts', 'google_rich_snippets') || $get_ulike_count == 0) return;
        $post_meta 		= '<meta itemprop="name" content="' . get_the_title() . '" />';
        $post_meta 		.= apply_filters( 'wp_ulike_extra_structured_data', NULL );
		$post_meta 		.= '<span itemprop="author" itemscope itemtype="http://schema.org/Person"><meta itemprop="name" content="' . get_the_author() . '" /></span>';
        $post_meta 		.= '<meta itemprop="datePublished" content="' . get_post_time('c') . '" />';
		$ratings_meta 	= '<span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
		$ratings_meta	.= '<meta itemprop="bestRating" content="5" />';
		$ratings_meta 	.= '<meta itemprop="worstRating" content="1" />';
		$ratings_meta 	.= '<meta itemprop="ratingValue" content="'. wp_ulike_get_rating_value(get_the_ID()) .'" />';
		$ratings_meta 	.= '<meta itemprop="ratingCount" content="' . $get_ulike_count . '" />';
		$ratings_meta 	.= '</span>';
		$itemtype 		= apply_filters( 'wp_ulike_remove_microdata_post_meta', false );
        return apply_filters( 'wp_ulike_generate_google_structured_data', ( $itemtype ? $ratings_meta : ( $post_meta . $ratings_meta )));
	}

	/**
	 * Calculate rating value by user logs & date_time
	 *
	 * @author       	Alimir
	 * @since           2.7 
	 * @return          String
	 */
	function wp_ulike_get_rating_value($post_ID, $is_decimal = true){
		global $wpdb;
		if (false === ($rating_value = wp_cache_get($cache_key = 'get_rich_rating_value_' . $post_ID, $cache_group = 'wp_ulike'))) {
			//get the average, likes count & date_time columns by $post_ID
			$request =  "SELECT
							FORMAT(
								(
								SELECT
									AVG(counted.total)
								FROM
									(
									SELECT
										COUNT(*) AS total
									FROM
										".$wpdb->prefix."ulike AS ulike
									GROUP BY
										ulike.post_id
								) AS counted
							),
							0
							) AS average,
							COUNT(ulike.post_id) AS counter,
							posts.post_date AS post_date
						FROM
							".$wpdb->prefix."ulike AS ulike
						JOIN
							".$wpdb->prefix."posts AS posts
						ON
							ulike.post_id = ".$post_ID." AND posts.ID = ulike.post_id;";
			//get columns in a row
			$likes 	= $wpdb->get_row($request);
			$avg 	= $likes->average;
			$count 	= $likes->counter;
			$date 	= strtotime($likes->post_date);
			//if there is no log data, set $rating_value = 4
			if($count == 0 || $avg == 0){
				$rating_value = 4;
				return $rating_value;
			}
			$decimal = 0;
			if($is_decimal){
				list($whole, $decimal) = explode('.', number_format(($count*100/($avg*2)), 1));
				$decimal = (int)$decimal;
			}
			if( $date > strtotime('-1 month')) {
				if($count < $avg) $rating_value = 4 + ".$decimal";
				else $rating_value = 5;
			} else if(($date <= strtotime('-1 month')) && ($date > strtotime('-6 month'))) {
				if($count < $avg) $rating_value = 3 + ".$decimal";
				else if(($count >= $avg) && ($count < ($avg*3/2))) $rating_value = 4 + ".$decimal";
				else $rating_value = 5;
			} else {
				if($count < ($avg/2)) $rating_value = 1 + ".$decimal";
				else if(($count >= ($avg/2)) && ($count < $avg)) $rating_value = 2 + ".$decimal";
				else if(($count >= $avg) && ($count < ($avg*3/2))) $rating_value = 3 + ".$decimal";
				else if(($count >= ($avg*3/2)) && ($count < ($avg*2))) $rating_value = 4 + ".$decimal";
				else $rating_value = 5;
			}
			wp_cache_add($cache_key, $rating_value, $cache_group, HOUR_IN_SECONDS);
		}
		return $rating_value;
	}
	
	
/*******************************************************
  Comments Likes Functions
*******************************************************/	
	
	/**
	 * Auto insert wp_ulike_comments in the comments content
	 *
	 * @author       	Alimir
	 * @param           String $content		 
	 * @since           1.6		 
	 * @updated         1.9	
	 * @return          filter on "comment_text"
	 */		
	if (wp_ulike_get_setting( 'wp_ulike_comments', 'auto_display' ) == '1'  && !is_admin()) {
		function wp_ulike_put_comments($content) {
			//auto display position
			$position = wp_ulike_get_setting( 'wp_ulike_comments', 'auto_display_position');
			
			//add wp_ulike_comments function
			$button = wp_ulike_comments('put');
			
			//return by position
			if($position=='bottom')
			return $content . $button;
			else if($position=='top')
			return $button . $content;
			else if($position=='top_bottom')
			return $button . $content . $button;
			else
			return $content . $button;
		}
		
		add_filter('comment_text', 'wp_ulike_put_comments');
	}
	

	/**
	 * Get the number of likes on a single comment
	 *
	 * @author          Alimir & Wacław Jacek
	 * @param           Integer $commentID
	 * @since           2.5
	 * @return          String
	 */
	function wp_ulike_get_comment_likes($comment_ID){
		$val = get_comment_meta($comment_ID, '_commentliked', true);
		return wp_ulike_format_number($val);
	}	
	
/*******************************************************
  BuddyPress Likes Functions
*******************************************************/	
	
	/**
	 * Auto insert wp_ulike_buddypress in the comments content
	 *
	 * @author       	Alimir	 
	 * @param           String $content	 
	 * @since           1.7		 
	 * @updated         2.1	 
	 * @updated         2.4 
	 * @return          filter on "bp_get_activity_action"
	 */
	if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display' ) == '1') {
		function wp_ulike_put_buddypress() {
			wp_ulike_buddypress('get');
		}
		
		if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display_position' ) == 'meta'){
		add_action( 'bp_activity_entry_meta', 'wp_ulike_put_buddypress' );
        }
		else	
		add_action( 'bp_activity_entry_content', 'wp_ulike_put_buddypress' );
        
        if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'activity_comment' ) == '1')
        add_action( 'bp_activity_comment_options', 'wp_ulike_put_buddypress' );        

	}
	
	/**
	 * Register "WP ULike Activity" action
	 *
	 * @author       	Alimir
	 * @since           1.7	 
	 * @return          Add action on "bp_register_activity_actions"
	 */
	add_action( 'bp_register_activity_actions', 'wp_ulike_register_activity_actions' );	
	function wp_ulike_register_activity_actions() {
		global $bp;
		bp_activity_set_action(
			$bp->activity->id,
			'wp_like_group',
			__( 'WP ULike Activity', WP_ULIKE_SLUG )
		);
	}
	
	/**
	 * Add new buddypress activities on each like.
	 *
	 * @author       	Alimir	 
	 * @param           Integer $user_ID (User ID)	 
	 * @param           Integer $cp_ID (Post/Comment ID)
	 * @param           String $type (Simple Key for separate posts by comments) 
	 * @since           1.6
	 * @updated         2.0
	 * @updated         2.5 -> added : buddypress notifications support
	 * @updated         2.5.1 -> added : %COMMENT_PERMALINK% variable
	 * @return          Void
	 */
	function wp_ulike_bp_activity_add($user_ID,$cp_ID,$type){
		//Create a new activity when an user likes something
		if (function_exists('bp_is_active') && wp_ulike_get_setting( 'wp_ulike_buddypress', 'new_likes_activity' ) == '1') {
			// Replace the post variables
			$post_template = wp_ulike_get_setting( 'wp_ulike_buddypress', 'bp_post_activity_add_header' );
			if($post_template == '')
			$post_template = '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)';
			
			if (strpos($post_template, '%POST_LIKER%') !== false) {
				$POST_LIKER = bp_core_get_userlink($user_ID);
				$post_template = str_replace("%POST_LIKER%", $POST_LIKER, $post_template);
			}
			if (strpos($post_template, '%POST_PERMALINK%') !== false) {
				$POST_PERMALINK = get_permalink($cp_ID);
				$post_template = str_replace("%POST_PERMALINK%", $POST_PERMALINK, $post_template);
			}
			if (strpos($post_template, '%POST_COUNT%') !== false) {
				$POST_COUNT = get_post_meta($cp_ID, '_liked', true);
				$post_template = str_replace("%POST_COUNT%", $POST_COUNT, $post_template);
			}
			if (strpos($post_template, '%POST_TITLE%') !== false) {
				$POST_TITLE = get_the_title($cp_ID);
				$post_template = str_replace("%POST_TITLE%", $POST_TITLE, $post_template);
			}
			
			// Replace the comment variables
			$comment_template = wp_ulike_get_setting( 'wp_ulike_buddypress', 'bp_comment_activity_add_header' );
			if($comment_template == '')
			$comment_template = '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)';
			
			if (strpos($comment_template, '%COMMENT_LIKER%') !== false) {
				$COMMENT_LIKER = bp_core_get_userlink($user_ID);
				$comment_template = str_replace("%COMMENT_LIKER%", $COMMENT_LIKER, $comment_template);
			}
			if (strpos($comment_template, '%COMMENT_PERMALINK%') !== false) {
				$COMMENT_PERMALINK = get_comment_link($cp_ID);
				$comment_template = str_replace("%COMMENT_PERMALINK%", $COMMENT_PERMALINK, $comment_template);
			}			
			if (strpos($comment_template, '%COMMENT_AUTHOR%') !== false) {
				$COMMENT_AUTHOR = get_comment_author($cp_ID);
				$comment_template = str_replace("%COMMENT_AUTHOR%", $COMMENT_AUTHOR, $comment_template);
			}
			if (strpos($comment_template, '%COMMENT_COUNT%') !== false) {
				$COMMENT_COUNT = get_comment_meta($cp_ID, '_commentliked', true);
				$comment_template = str_replace("%COMMENT_COUNT%", $COMMENT_COUNT, $comment_template);
			}
			
			//create bp_activity_add
			if($type=='_commentliked'){
				bp_activity_add( array(
					'user_id' => $user_ID,
					'action' => $comment_template,
					'component' => 'activity',
					'type' => 'wp_like_group',
					'item_id' => $cp_ID
				));
			}
			else if($type=='_liked'){
				bp_activity_add( array(
					'user_id' => $user_ID,
					'action' => $post_template,
					'component' => 'activity',
					'type' => 'wp_like_group',
					'item_id' => $cp_ID
				));
			}
		}
		//Sends out notifications when you get a like from someone
		if (function_exists('bp_is_active') && wp_ulike_get_setting( 'wp_ulike_buddypress', 'custom_notification' ) == '1') {
			// No notifications from Anonymous
			if (!$user_ID) {
				return false;
			}
			$author_ID = wp_ulike_get_auhtor_id($cp_ID,$type);
			if (!$author_ID || $author_ID == $user_ID) {
				return false;
			}			
			bp_notifications_add_notification( array(
				'user_id'           => $author_ID,
				'item_id'           => $cp_ID,
				'secondary_item_id' => '',
				'component_name'    => 'wp_ulike',
				'component_action'  => 'wp_ulike' . $type . '_action_' . $user_ID,
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
			) );			
		}
		else{
			return;
		}
	}

	/**
	 * Display likes option in BuddyPress activity filter
	 *
	 * @author       	Alimir	 
	 * @since           2.5.1
	 * @return          Void
	 */
	add_action( 'bp_activity_filter_options', 'wp_ulike_bp_activity_filter_options' ); // Activity Directory
	add_action( 'bp_member_activity_filter_options', 'wp_ulike_bp_activity_filter_options' ); // Member's profile activity
	add_action( 'bp_group_activity_filter_options', 'wp_ulike_bp_activity_filter_options' ); // Group's activity
	function wp_ulike_bp_activity_filter_options() {
		echo "<option value='wp_like_group'>". __('Likes') ."</option>";
	}

	/**
	 * Get auther ID by the ulike types
	 *
	 * @author       	Alimir	 
	 * @param           Integer $cp_ID (Post/Comment/... ID)	 
	 * @param           String $type (Get ulike Type)
	 * @since           2.5
	 * @updated         2.8.1
	 * @return          String
	 */
	function wp_ulike_get_auhtor_id($cp_ID,$type) {
		if($type == '_liked' || $type == '_topicliked'){
			$post_tmp = get_post($cp_ID);
			return $post_tmp->post_author;			
		}
		else if($type == '_commentliked'){
			$comment = get_comment( $cp_ID );
			return $comment->user_id;					
		}
		else if($type == '_activityliked'){
			$activity = bp_activity_get_specific( array( 'activity_ids' => $cp_ID, 'display_comments'  => true ) );
			return $activity['activities'][0]->user_id;				
		}
		else return;
	}

	/**
	 * Register 'wp_ulike' to BuddyPress component. 
	 *
	 * @author       	Alimir	 
	 * @param           Array $component_names	 
	 * @since           2.5
	 * @return          String
	 */
	add_filter( 'bp_notifications_get_registered_components', 'wp_ulike_filter_notifications_get_registered_components', 10 );
	function wp_ulike_filter_notifications_get_registered_components( $component_names = array() ) {
		// Force $component_names to be an array
		if ( ! is_array( $component_names ) ) {
			$component_names = array();
		}
		// Add 'wp_ulike' component to registered components array
		array_push( $component_names, 'wp_ulike' );
		// Return component's with 'wp_ulike' appended
		return $component_names;
	}

	/**
	 * Add custom format for 'wp_ulike' notifications.
	 *
	 * @author       	Alimir	 
	 * @since           2.5
	 * @updated         2.5.1
	 * @return          String
	 */
	add_filter( 'bp_notifications_get_notifications_for_user', 'wp_ulike_format_buddypress_notifications', 5, 5 );
	function wp_ulike_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
		global $wp_filter,$wp_version;	
			if (strpos($action, 'wp_ulike_') !== false) {
				$custom_link	= '';
				//Extracting ulike type from the action value.
				preg_match('/wp_ulike_(.*?)_action/', $action, $type);
				//Extracting user id from the action value.
				preg_match('/action_([0-9]+)/', $action, $user_ID);
				$user_info 		= get_userdata($user_ID[1]);
				$custom_text 	= __('You have a new like from', WP_ULIKE_SLUG ) . ' "' . $user_info->display_name . '"';
				//checking the ulike types
				if($type[1] == 'liked'){
					$custom_link  	= get_permalink($item_id);
				}
				else if($type[1] == 'topicliked'){
					$custom_link  	= get_permalink($item_id);
				}
				else if($type[1] == 'commentliked'){
					$custom_link  	= get_comment_link( $item_id );
				}
				else if($type[1] == 'activityliked'){
					$custom_link  	= bp_activity_get_permalink( $item_id );
				}
				// WordPress Toolbar
				if ( 'string' === $format ) {
					$return = apply_filters( 'wp_ulike_bp_notifications_template', '<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_text ) . '">' . esc_html( $custom_text ) . '</a>', $custom_text, $custom_link );
				// Deprecated BuddyBar
				} else {
					$return = apply_filters( 'wp_ulike_bp_notifications_template', array(
						'text' => $custom_text,
						'link' => $custom_link
					), $custom_link, (int) $total_items, $custom_text, $custom_text );
				}
				// global wp_filter to call bbPress wrapper function
				if (isset($wp_filter['bp_notifications_get_notifications_for_user'][10]['bbp_format_buddypress_notifications'])) {
					if (version_compare($wp_version, '4.7', '>=' )) {
						// https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
						$wp_filter['bp_notifications_get_notifications_for_user']->callbacks[10]['bbp_format_buddypress_notifications']['function'] = 'wp_ulike_bbp_format_buddypress_notifications';
					} else {
						$wp_filter['bp_notifications_get_notifications_for_user'][10]['bbp_format_buddypress_notifications']['function'] = 'wp_ulike_bbp_format_buddypress_notifications';
					}
				}
				return $return;
		}
		return $action;
	}

	/**
	 * Wrapper for bbp_format_buddypress_notifications function as it is not returning $action
	 *
	 * @author       	Alimir	 
	 * @since           2.5.1
	 * @return          String
	 */
	function wp_ulike_bbp_format_buddypress_notifications($action, $item_id, $secondary_item_id, $total_items, $format = 'string')
	{
		$result = bbp_format_buddypress_notifications($action, $item_id, $secondary_item_id, $total_items, $format);
		if (!$result) {
			$result = $action;
		}
		return $result;
	}

/*******************************************************
  bbPress Likes Functions
*******************************************************/	
	
	/**
	 * Auto insert wp_ulike_bbpress in the topcis content
	 *
	 * @author       	Alimir	 
	 * @param           String $content	 
	 * @since           2.2	 
	 * @return          filter on bbpPress hooks
	 */
	if (wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display' ) == '1') {	 
		
		function wp_ulike_put_bbpress() {
			 wp_ulike_bbpress('get');
		}
		
		if (wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display_position' ) == 'top')
		add_action( 'bbp_theme_before_reply_content', 'wp_ulike_put_bbpress' );	
		else	
		add_action( 'bbp_theme_after_reply_content', 'wp_ulike_put_bbpress' );
	}
	
	
/*******************************************************
  myCRED Points Functions
*******************************************************/

	/**
	 * MyCred Hooks
	 *
	 * @author       	Gabriel Lemarie & Alimir
	 * @since          	2.3
	 */
	if ( defined( 'myCRED_VERSION' ) ) {
		require_once( WP_ULIKE_INC_DIR . '/classes/class-mycred.php');	
		/**
		 * Register Hooks
		 *
		 * @since 		2.3
		 */
		add_filter( 'mycred_setup_hooks', 'wp_ulike_register_myCRED_hook' );
		function wp_ulike_register_myCRED_hook( $installed ) {
			$installed['wp_ulike'] = array(
				'title'       => __( 'WP ULike', WP_ULIKE_SLUG ),
				'description' => __( 'This hook award / deducts points from users who Like/Unlike any content of WordPress, bbPress, BuddyPress & ...', WP_ULIKE_SLUG ),
				'callback'    => array( 'wp_ulike_myCRED' )
			);
			return $installed;
		}
		
		add_filter( 'mycred_all_references', 'wp_ulike_myCRED_references' );
		function wp_ulike_myCRED_references( $hooks ) {
			$hooks['wp_add_like'] 	= __( 'Liking Content', WP_ULIKE_SLUG );
			$hooks['wp_get_like'] 	= __( 'Liked Content', WP_ULIKE_SLUG );
			$hooks['wp_add_unlike'] = __( 'Unliking Content', WP_ULIKE_SLUG );
			$hooks['wp_get_unlike'] = __( 'Unliked Content', WP_ULIKE_SLUG );
			return $hooks;
		}
	}
	
	
/*******************************************************
  UltimateMember Functions
*******************************************************/	

	if ( defined( 'ultimatemember_version' ) ) {
		/**
		 * Add custom tabs in the UltimateMember profiles.
		 *
		 * @author       	Alimir
		 * @since           2.3
		 * @return          Array
		 */
		add_filter('um_profile_tabs', 'wp_ulike_add_custom_profile_tab', 1000 );
		function wp_ulike_add_custom_profile_tab( $tabs ) {
			
			$tabs['wp-ulike-posts'] = array(
				'name' => __('Recent Posts Liked',WP_ULIKE_SLUG),
				'icon' => 'um-faicon-thumbs-up',
			);
				
			$tabs['wp-ulike-comments'] = array(
				'name' => __('Recent Comments Liked',WP_ULIKE_SLUG),
				'icon' => 'um-faicon-thumbs-o-up',
			);
				
			return $tabs;
				
		}

		/**
		 * Add content to the wp-ulike-posts tab
		 *
		 * @author       	Alimir
		 * @since           2.3
		 * @return          Void
		 */
		add_action('um_profile_content_wp-ulike-posts_default', 'wp_ulike_posts_um_profile_content');
		function wp_ulike_posts_um_profile_content( $args ) {
			global $wp_ulike_class,$ultimatemember;
			
			$args = array(
				"user_id" 	=> um_profile_id(),			//User ID
				"col" 		=> 'post_id',				//Table Column (post_id,comment_id,activity_id,topic_id)
				"table" 	=> 'ulike',					//Table Name
				"limit" 	=> 10,						//limit Number
			);	
			
			$user_logs = $wp_ulike_class->get_current_user_likes($args);
			
			if($user_logs != null){
				echo '<div class="um-profile-note"><span>'. __('Recent Posts Liked',WP_ULIKE_SLUG).'</span></div>';
				foreach ($user_logs as $user_log) {
					$get_post 	= get_post(stripslashes($user_log->post_id));
					$get_date 	= $user_log->date_time;
					
					echo '<div class="um-item">';
					echo '<div class="um-item-link">
						  <i class="um-icon-ios-paper"></i>
						  <a href="'.get_permalink($get_post->ID).'">'.$get_post->post_title.'</a>
						  </div>';
					echo '<div class="um-item-meta">
						  <span>'.wp_ulike_date_i18n($get_date).'</span>
						  <span class="badge"><i class="um-faicon-thumbs-o-up"></i> '.get_post_meta( $get_post->ID, '_liked', true ).'</span>
						  </div>';
					echo '</div>';
				}
			} else echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. __('This user has not made any likes.',WP_ULIKE_SLUG).'</span></div>';
		}	

		/**
		 * Add content to the wp-ulike-comments tab
		 *
		 * @author       	Alimir
		 * @since           2.3
		 * @return          Void
		 */	
		add_action('um_profile_content_wp-ulike-comments_default', 'wp_ulike_comments_um_profile_content');
		function wp_ulike_comments_um_profile_content( $args ) {
			global $wp_ulike_class,$ultimatemember;
			
			$args = array(
				"user_id" 	=> um_profile_id(),			//User ID
				"col" 		=> 'comment_id',			//Table Column (post_id,comment_id,activity_id,topic_id)
				"table" 	=> 'ulike_comments',		//Table Name
				"limit" 	=> 10,						//limit Number
			);	
			
			$user_logs = $wp_ulike_class->get_current_user_likes($args);
			
			if($user_logs != null){
				echo '<div class="um-profile-note"><span>'. __('Recent Comments Liked',WP_ULIKE_SLUG).'</span></div>';
				foreach ($user_logs as $user_log) {
					$comment 	= get_comment(stripslashes($user_log->comment_id));
					$get_date 	= $user_log->date_time;
					
					echo '<div class="um-item">';
					echo '<div class="um-item-link">
						  <i class="um-icon-ios-chatboxes"></i>
						  <a href="'.get_comment_link($comment->comment_ID).'">'.$comment->comment_content .'</a>
						  <em style="font-size:.7em;padding:0 10px;"><span class="um-faicon-quote-left"></span> '.$comment->comment_author.' <span class="um-faicon-quote-right"></span></em>
						  </div>';
					echo '<div class="um-item-meta">
						  <span>'.wp_ulike_date_i18n($get_date).'</span>
						  <span class="badge"><i class="um-faicon-thumbs-o-up"></i> '.get_comment_meta( $comment->comment_ID, '_commentliked', true ).'</span>
						  </div>';
					echo '</div>';
				}
			} else echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. __('This user has not made any likes.',WP_ULIKE_SLUG).'</span></div>';
		}
	}

	
/*******************************************************
  General Functions
*******************************************************/

	//Create global variable of user IP
	global $wp_user_IP;
	$wp_user_IP = wp_ulike_get_real_ip();
	
	/**
	 * Get the real IP
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @return          Void
	 */
	function wp_ulike_get_real_ip() {
		if (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_X_FORWARDED')) {
			$ip = getenv('HTTP_X_FORWARDED');
		} elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip = getenv('HTTP_FORWARDED_FOR');
		} elseif (getenv('HTTP_FORWARDED')) {
			$ip = getenv('HTTP_FORWARDED');
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		return $ip;
	}
	
	/**
	 * Get custom style setting from customize options
	 *
	 * @author       	Alimir
	 * @since           1.3
	 * @updated         2.3
	 * @updated         2.4
	 * @updated         2.8 //Added new element names
	 * @return          Void (Print new CSS styles)
	 */
	function wp_ulike_get_custom_style(){
		$btn_style 		= '';
		$counter_style 	= '';
		$customstyle 	= '';
		$customloading 	= '';
		$return_style	= '';
		
		//get custom icons
		$getlikeicon 	= wp_ulike_get_setting( 'wp_ulike_general', 'button_url' );
		$getlikeurl 	= wp_get_attachment_url( $getlikeicon );
				
		$getunlikeicon 	= wp_ulike_get_setting( 'wp_ulike_general', 'button_url_u' );
		$getunlikeurl 	= wp_get_attachment_url( $getunlikeicon );
				
		if(wp_ulike_get_setting( 'wp_ulike_customize', 'custom_style') == '1'){
		
			//get custom options
			$customstyle 	= get_option( 'wp_ulike_customize' );

			//button style
			$btn_bg 		= $customstyle['btn_bg'];
			$btn_border		= $customstyle['btn_border'];
			$btn_color 		= $customstyle['btn_color'];

			//counter style
			$counter_bg 	= $customstyle['counter_bg'];
			$counter_border = $customstyle['counter_border'];
			$counter_color 	= $customstyle['counter_color'];

			//Loading animation
			$customloading 	= $customstyle['loading_animation'];
			$loadingurl 	= wp_get_attachment_url( $customloading );

			$custom_css 	= $customstyle['custom_css'];


			if($btn_bg != ''){
				$btn_style .= "background-color:$btn_bg;";
			}			
			if($btn_border != ''){
				$btn_style .= "border-color:$btn_border; ";
			}			
			if($btn_color != ''){
				$btn_style .= "color:$btn_color;text-shadow: 0px 1px 0px rgba(0, 0, 0, 0.3);";
			}

			if($counter_bg != ''){
				$counter_style .= "background-color:$counter_bg; ";
			}			
			if($counter_border != ''){
				$counter_style .= "border-color:$counter_border; ";
				$before_style  = "border-color:transparent; border-bottom-color:$counter_border; border-left-color:$counter_border;";
			}			
			if($counter_color != ''){
				$counter_style .= "color:$counter_color;";
			}
		
		}
		
		if($getlikeicon != '' || $getunlikeicon != '' || $customstyle != ''){
				
			if($getlikeicon != ''){
				$return_style .= '
					.wp_ulike_btn.wp_ulike_put_image {
						background-image: url('.$getlikeurl.') !important;
					}
				';
			}

			if($getunlikeicon != ''){
				$return_style .= '
					.wp_ulike_btn.wp_ulike_put_image.image-unlike {
						background-image: url('.$getunlikeurl.') !important;
					}
				 ';
			}

			if($customloading != ''){
				$return_style .= '
					.wpulike .wp_ulike_is_loading .wp_ulike_btn,
	#buddypress .activity-content .wpulike .wp_ulike_is_loading .wp_ulike_btn,
	#bbpress-forums .bbp-reply-content .wpulike .wp_ulike_is_loading .wp_ulike_btn {
						background-image: url('.$loadingurl.') !important;
					}
				 ';
			}

			if($btn_style != ''){
				$return_style .= '
					.wpulike-default .wp_ulike_btn, .wpulike-default .wp_ulike_btn:hover, #bbpress-forums .wpulike-default .wp_ulike_btn, #bbpress-forums .wpulike-default .wp_ulike_btn:hover{
						'.$btn_style.'	
					}
					.wpulike-heart .wp_ulike_general_class{
						'.$btn_style.'	
					}
				';
			}

			if($counter_style != ''){
				$return_style .= '
					.wpulike-default .count-box,.wpulike-default .count-box:before{
						'.$counter_style.'
					}
					.wpulike-default .count-box:before{
						'.$before_style.'
					}
				';
			}	

			if($custom_css != ''){
				$return_style .= $custom_css;
			}
			
		}
		
		return $return_style;
	}
	
	/**
	 * Convert numbers of Likes with string (kilobyte) format
	 *
	 * @author       	Alimir
	 * @param           Integer $num (get like number)	 
	 * @since           1.5
	 * @updated         2.0
	 * @return          String
	 */
	function wp_ulike_format_number($num){
		$plus = $num != 0 ? '+' : '';
		if ($num >= 1000 && wp_ulike_get_setting( 'wp_ulike_general', 'format_number' ) == '1')
		$value = round($num/1000, 2) . 'K' . $plus;
		else
		$value = $num . $plus;
		$value = apply_filters( 'wp_ulike_format_number', $value, $num, $plus);
		return $value;
	}
	
	
	/**
	 * Date in localized format
	 *
	 * @author       	Alimir
	 * @param           String (Date)
	 * @since           2.3
	 * @return          String
	 */
	function wp_ulike_date_i18n($date){
		return date_i18n(get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime($date) );	
	}
	
	
/*******************************************************
  WP ULike Class & Templates
*******************************************************/
	//Include wp_ulike class
	require_once( plugin_dir_path( __FILE__ ) . 'classes/class-ulike.php' );	
	//Include templates functions
	require_once( plugin_dir_path( __FILE__ ) . 'wp-templates.php' );	