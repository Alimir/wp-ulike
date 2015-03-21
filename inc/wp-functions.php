<?php
	
/*******************************************************
  Posts Likes Functions
*******************************************************/

	/**
	 * Create ShortCode: 	[wp_ulike]
	 *
	 * @author       	Alimir
	 * @since           1.4
	 * @return			wp_ulike button
	 */
	add_shortcode( 'wp_ulike', 'wp_ulike_shortcode' );	
	function  wp_ulike_shortcode(){
		return wp_ulike('put');
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
			if(	!is_feed() && wp_ulike_post_auto_display_filters()){
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
	 * Auto display filtering on posts/pages content
	 *
	 * @author       	Alimir
	 * @since           1.9
	 * @return			boolean
	 */		
	function wp_ulike_post_auto_display_filters(){
		$filter = wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display_filter');
		if(is_home() && $filter['home'] == '1')
		return 0;
		else if(is_single() && $filter['single'] == '1')
		return 0;
		else if(is_page() && $filter['page'] == '1')
		return 0;
		else if(is_archive() && $filter['archive'] == '1')
		return 0;
		else if(is_category() && $filter['category'] == '1')
		return 0;
		else if( is_search() && $filter['search'] == '1')
		return 0;
		else if(is_tag() && $filter['tag'] == '1')
		return 0;
		else if(is_author() && $filter['author'] == '1')
		return 0;
		else
		return 1;
	}

	/**
	 * Get The Post like number
	 *
	 * @author       	Alimir
	 * @param           Integer $postID	 
	 * @since           1.7 
	 * @return          String
	 */
	function wp_ulike_get_post_likes($postID){
		$val = get_post_meta($postID, '_liked', true);
		return wp_ulike_format_number($val);
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
	 * @return          filter on "bp_get_activity_action"
	 */
	if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display' ) == '1') {
		function wp_ulike_put_buddypress() {
			echo wp_ulike_buddypress('put');
		}
		
		if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display_position' ) == 'meta')
		add_action( 'bp_activity_entry_meta', 'wp_ulike_put_buddypress' );
		else	
		add_action( 'bp_activity_entry_content', 'wp_ulike_put_buddypress' );

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
			__( 'WP ULike Activity', 'alimir' )
		);
	}
	
	/**
	 * Add new buddypress activities on each like. (Post/Comments)
	 *
	 * @author       	Alimir	 
	 * @param           Integer $user_ID (User ID)	 
	 * @param           Integer $cp_ID (Post/Comment ID)
	 * @param           String $type (Simple Key for separate posts by comments) 
	 * @since           1.6
	 * @updated         2.0
	 * @return          Void
	 */
	function wp_ulike_bp_activity_add($user_ID,$cp_ID,$type){
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
		else{
			return '';
		}
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
			 echo wp_ulike_bbpress('put');
		}
		
		if (wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display_position' ) == 'top')
		add_action( 'bbp_theme_before_reply_content', 'wp_ulike_put_bbpress' );	
		else	
		add_action( 'bbp_theme_after_reply_content', 'wp_ulike_put_bbpress' );
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
	 * @updated         1.8
	 * @return          Void (Print new CSS styles)
	 */
	function wp_ulike_get_custom_style(){
		$btn_style = '';
		$counter_style = '';
		$customstyle = '';
		$customloading = '';
		
		//get custom icon
		$customicon = wp_ulike_get_setting( 'wp_ulike_general', 'button_url' );
		$iconurl = wp_get_attachment_url( $customicon );
				
		if(wp_ulike_get_setting( 'wp_ulike_customize', 'custom_style') == '1'){
		
		//get custom options
		$customstyle = get_option( 'wp_ulike_customize' );
		
		//button style
		$btn_bg = $customstyle['btn_bg'];
		$btn_border = $customstyle['btn_border'];
		$btn_color = $customstyle['btn_color'];
		
		//counter style
		$counter_bg = $customstyle['counter_bg'];
		$counter_border = $customstyle['counter_border'];
		$counter_color = $customstyle['counter_color'];
		
		//Loading animation
		$customloading = $customstyle['loading_animation'];
		$loadingurl = wp_get_attachment_url( $customloading );
		
		
		if($btn_bg != ''){
			$btn_style .= "background-color:$btn_bg !important; ";
		}			
		if($btn_border != ''){
			$btn_style .= "border-color:$btn_border !important; ";
		}			
		if($btn_color != ''){
			$btn_style .= "color:$btn_color !important;";
		}

		if($counter_bg != ''){
			$counter_style .= "background-color:$counter_bg !important; ";
		}			
		if($counter_border != ''){
			$counter_style .= "border-color:$counter_border !important; ";
		}			
		if($counter_color != ''){
			$counter_style .= "color:$counter_color !important;";
		}
		
		}
		
		if($customicon != '' || $customstyle != ''){
		
		echo "<style>";
		
		if($customicon != ''){
		echo '
		.wpulike .counter a.image {
			background-image: url('.$iconurl.') !important;
		}
		';
		}
		
		if($customloading != ''){
		echo '
		.wpulike .counter a.loading {
			background-image: url('.$loadingurl.') !important;
		}
		';
		}
		
		if($btn_style != ''){
		echo "
		.wpulike .counter a{
			$btn_style	
		}";
		}
		
		if($counter_style != ''){
		echo"
		.wpulike .count-box,.wpulike .count-box:before{
			$counter_style
		}";
		}
		
		echo "</style>";
		}
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
	
/*******************************************************
  WP ULike Class
*******************************************************/
	
	include( plugin_dir_path(__FILE__) . 'classes/class-ulike.php');	