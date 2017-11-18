<?php 
// Creating the most liked posts widget 
class wp_ulike_widget extends WP_Widget {

	/**
	 * Constructor
	 */	
	function __construct() {
		parent::__construct(
			'wp_ulike', 
			__('WP Ulike Widget', WP_ULIKE_SLUG), 
			array( 'description' => __( 'An advanced widget that gives you all most liked records with different types', WP_ULIKE_SLUG ))
			);
	}

	/**
	 * Most Liked Posts Function
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.3	 
	 * @updated         2.4	 
	 * @return			String
	 */		
	public function most_liked_posts( $args = array(), $result = '' ) {
		global $wpdb;

		$defaults = array(
			"numberOf"    => 10,
			"period"      => 'all',
			"sizeOf"      => 32,
			"trim"        => 10,
			"profile_url" => '',
			"show_count"  => true,		
			"show_thumb"  => false,
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		// Parse args
		$settings = wp_parse_args( $args, $defaults );		
		// Extract settings
		extract($settings);

		if ( false === ( $posts = get_transient( 'wp_ulike_get_most_liked_posts' ) ) ) {
			// Make new sql request
	        $posts 		= $wpdb->get_results( "
							SELECT p.ID, p.post_title, p.post_content, m.meta_value 
							FROM  $wpdb->posts AS p, $wpdb->postmeta AS m, ".$wpdb->prefix."ulike AS l  
							WHERE p.ID        = m.post_ID 
							AND m.post_ID     = l.post_id 
							AND p.post_status = 'publish' 
							AND m.meta_key    = '_liked' 
							".$this->period($period)." 
							GROUP BY p.ID 
							ORDER BY CAST( m.meta_value AS SIGNED ) DESC LIMIT $numberOf
                    	" );

	        set_transient( 'wp_ulike_get_most_liked_posts', $posts, 6 * HOUR_IN_SECONDS );
		}

		foreach ($posts as $post) {
			$post_title = stripslashes($post->post_title);
			$permalink  = get_permalink($post->ID);
			$post_count = $post->meta_value;
			
			$result     .= $before_item;
			$result     .= $show_thumb ? $this->get_post_thumbnail( $post->ID, $sizeOf ) : '';
			$result     .= '<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">'. wp_trim_words( $post_title, $num_words = $trim, $more = null ) . '</a>';
			$result     .= $show_count ? ' <span class="wp_counter_span">'.wp_ulike_format_number($post_count).'</span>' : '';
			$result     .= $after_item;
		}

		return $result;
	}
    
	/**
	 * Most Liked Comments Function
	 *
	 * @author       	Alimir
	 * @since           1.9
	 * @updated         2.3	 
	 * @updated         2.4	 
	 * @return			String
	 */		
	public function most_liked_comments( $args = array(), $result = '' ) {
		global $wpdb;

		$defaults = array(
			"numberOf"    => 10,
			"period"      => 'all',
			"sizeOf"      => 32,
			"trim"        => 10,
			"profile_url" => '',
			"show_count"  => true,		
			"show_thumb"  => false,
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		// Parse args
		$settings 		= wp_parse_args( $args, $defaults );		
		// Extract settings
		extract($settings);

		if ( false === ( $comments = get_transient( 'wp_ulike_get_most_liked_comments' ) ) ) {
			// Make new sql request
	        $comments 	= $wpdb->get_results( "
							SELECT *
							FROM  $wpdb->comments AS c, $wpdb->commentmeta AS m, ".$wpdb->prefix."ulike_comments AS l  
							WHERE c.comment_ID     = m.comment_id
							AND m.comment_id       = l.comment_id
							AND c.comment_approved = '1' 
							AND m.meta_key         = '_commentliked' 
							".$this->period($period)." 
							GROUP BY c.comment_ID
							ORDER BY CAST( m.meta_value AS SIGNED ) DESC LIMIT $numberOf
                    	" );

	        set_transient( 'wp_ulike_get_most_liked_comments', $comments, 6 * HOUR_IN_SECONDS );
		}		

		foreach ($comments as $comment) {
			$comment_author      = stripslashes($comment->comment_author);
			$post_permalink      = get_permalink($comment->comment_post_ID);
			$post_title          = get_the_title($comment->comment_post_ID);
			$comment_permalink   = get_permalink($comment->comment_ID);
			$comment_likes_count = $comment->meta_value;
			
			$result .= $before_item;
			$result .= $show_thumb ? get_avatar( $comment->comment_author_email, $sizeOf ) : '';
			$result .= '<span class="comment-author-link">' . $comment_author . '</span> ' . __('on',WP_ULIKE_SLUG);
			$result .= ' <a href="' . $post_permalink . '#comment-' . $comment->comment_ID . '" title="' . $post_title.'" rel="nofollow">' . wp_trim_words( $post_title, $num_words = $trim, $more = null ) . '</a>';
			$result .= $show_count ? ' <span class="wp_counter_span">'.wp_ulike_format_number($comment_likes_count).'</span>' : '';
			$result .= $after_item;
		}

		return $result;
	}    

	/**
	 * Last Posts Liked By Current User
	 *
	 * @author       	Alimir
	 * @since           2.0
	 * @updated         2.3
	 * @updated         2.4
	 * @return			String
	 */		
	public function last_posts_liked_by_current_user( $args = array(), $result = '' ) {
		global $wpdb,$user_ID,$wp_user_IP;

		$defaults = array(
			"numberOf"    => 10,
			"period"      => 'all',
			"sizeOf"      => 32,
			"trim"        => 10,
			"profile_url" => '',
			"show_count"  => true,		
			"show_thumb"  => false,
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		// Parse args
		$settings 		= wp_parse_args( $args, $defaults );
		// Extract settings
		extract($settings);	

		$likes = $wpdb->get_results( "
						SELECT U.post_id, P.meta_value AS counter
						FROM ".$wpdb->prefix."ulike AS U,
	                    $wpdb->postmeta AS P
						WHERE ( U.ip LIKE '$wp_user_IP' OR U.user_id = $user_ID )
	                    AND U.post_id = P.post_id AND meta_key='_liked'
						GROUP BY U.post_id
						ORDER BY MAX(U.date_time) DESC LIMIT $numberOf
					" );
		
		if( $likes !== 0 ){
			foreach ($likes as $like) {
				$permalink  = get_permalink($like->post_id);
				$post_title = get_the_title($like->post_id);
				$post_count = $like->counter;
				$result .= $before_item;
				$result .= $show_thumb ? $this->get_post_thumbnail( $like->post_id, $sizeOf ) : '';
				$result .= '<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">' . wp_trim_words( $post_title, $num_words = $trim, $more = null ) . '</a>';
				$result .= $show_count ? ' <span class="wp_counter_span">'.wp_ulike_format_number( $post_count ).'</span>' : '';
				$result .= $after_item;
			}
		}
		else{
				$result .= $before_item;
				$result .= __( 'you haven\'t liked any post yet!',WP_ULIKE_SLUG );		
				$result .= $after_item;
		}
		
		return $result;
	}
	
	/**
	 * Most Liked Topics Function
	 *
	 * @author       	Alimir
	 * @since           2.3
	 * @updated         2.4
	 * @return			String
	 */		
	public function most_liked_topics( $args = array(), $result = '' ) {
		global $wpdb;

		$defaults = array(
			"numberOf"    => 10,
			"period"      => 'all',
			"sizeOf"      => 32,
			"trim"        => 10,
			"profile_url" => '',
			"show_count"  => true,		
			"show_thumb"  => false,
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		// Parse args
		$settings 		= wp_parse_args( $args, $defaults );		
		// Extract settings
		extract($settings);

		if ( false === ( $posts = get_transient( 'wp_ulike_get_most_liked_topics' ) ) ) {
			// Make new sql request
	        $posts 		= $wpdb->get_results( "
							SELECT p.ID, p.post_title, p.post_content, m.meta_value 
							FROM  $wpdb->posts AS p, $wpdb->postmeta AS m, ".$wpdb->prefix."ulike_forums AS l  
							WHERE p.ID        = m.post_ID 
							AND m.post_ID     = l.topic_id 
							AND p.post_status = 'publish' 
							AND m.meta_key    = '_topicliked' 
							".$this->period($period)." 
							GROUP BY p.ID 
							ORDER BY CAST( m.meta_value AS SIGNED ) DESC LIMIT $numberOf
                    	" );

	        set_transient( 'wp_ulike_get_most_liked_topics', $posts, 6 * HOUR_IN_SECONDS );
		}
        
		foreach ($posts as $post) {
			$post_title = empty($post->post_title) ? $post->post_content : stripslashes($post->post_title);
			$permalink  = get_permalink($post->ID);
			$post_count = $post->meta_value;

			$result .= $before_item;
			$result .= '<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">'. wp_trim_words( $post_title, $num_words = $trim, $more = null ) . '</a>';
			$result .= $show_count ? ' <span class="wp_counter_span">'.wp_ulike_format_number($post_count).'</span>' : '';
			$result .= $after_item;			
		}

		return $result;
	}	

	/**
	 * Most Liked Activities Function
	 *
	 * @author       	Alimir
	 * @since           2.0
	 * @updated         2.4
	 * @updated         2.6 //added post counter value
	 * @return			String
	 */		
	public function most_liked_activities( $args = array(), $result = '' ) {
		global $wpdb;

		$defaults = array(
			"numberOf"    => 10,
			"period"      => 'all',
			"sizeOf"      => 32,
			"trim"        => 18,
			"profile_url" => '',
			"show_count"  => true,		
			"show_thumb"  => false,
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		// Parse args
		$settings 		= wp_parse_args( $args, $defaults );		
		// Extract settings
		extract($settings);

        if ( is_multisite() ) {
            $bp_prefix = 'base_prefix';
        } else {
            $bp_prefix = 'prefix';   
        }

		if ( false === ( $activities = get_transient( 'wp_ulike_get_most_liked_activities' ) ) ) {
			// Make new sql request
	        $activities = $wpdb->get_results( "
							SELECT * FROM 
							".$wpdb->$bp_prefix."bp_activity AS b, 
							".$wpdb->$bp_prefix."bp_activity_meta AS m, 
							".$wpdb->prefix."ulike_activities AS l 
							WHERE b.id        = m.activity_id
							AND m.activity_id = l.activity_id
							AND m.meta_key    = '_activityliked'
							".$this->period($period)." 
							GROUP BY b.id
							ORDER BY CAST( m.meta_value AS SIGNED ) DESC LIMIT $numberOf
                    	" );

	        set_transient( 'wp_ulike_get_most_liked_activities', $activities, 6 * HOUR_IN_SECONDS );
		}				

		foreach ($activities as $activity) {
			$activity_permalink = bp_activity_get_permalink( $activity->activity_id );
			$activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
			$post_count         = $activity->meta_value;
			
			$result .= $before_item;
			$result .= '<a href="' . $activity_permalink . '" rel="nofollow">';
			$result .= wp_trim_words( $activity_action, $num_words = $trim, $more = null );
			$result .= '</a>';
			$result .= $show_count ? ' <span class="wp_counter_span">'.wp_ulike_format_number($post_count).'</span>' : '';
			$result .= $after_item;
		}

		return $result;
	}

	/**
	 * Most Liked Activities Function
	 *
	 * @author       	Alimir
	 * @since           1.2
	 * @updated         2.3
	 * @updated         2.4
	 * @return			String
	 */		
	public function most_liked_users( $args = array(), $result = '' ) {
		global $wpdb;

		$defaults = array(
			"numberOf"    => 10,
			"period"      => 'all',
			"sizeOf"      => 32,
			"trim"        => 10,
			"profile_url" => 'bp',
			"show_count"  => true,		
			"show_thumb"  => false,
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		// Parse args
		$settings 		= wp_parse_args( $args, $defaults );
		// Extract settings
		extract($settings);	

		if ( false === ( $likers = get_transient( 'wp_ulike_get_most_likers' ) ) ) {
			// Make new sql request
	        $likers = $wpdb->get_results( "
							SELECT T.user_id, SUM(T.CountUser) AS SumUser
							FROM(
							SELECT user_id, count(user_id) AS CountUser
							FROM ".$wpdb->prefix."ulike
							WHERE user_id BETWEEN 1 AND 999999
							".$this->period($period)."
							GROUP BY user_id
							UNION ALL
							SELECT user_id, count(user_id) AS CountUser
							FROM ".$wpdb->prefix."ulike_activities
							WHERE user_id BETWEEN 1 AND 999999
							".$this->period($period)."
							GROUP BY user_id
							UNION ALL
							SELECT user_id, count(user_id) AS CountUser
							FROM ".$wpdb->prefix."ulike_comments
							WHERE user_id BETWEEN 1 AND 999999
							".$this->period($period)."
							GROUP BY user_id
							UNION ALL
							SELECT user_id, count(user_id) AS CountUser
							FROM ".$wpdb->prefix."ulike_forums
							WHERE user_id BETWEEN 1 AND 999999
							".$this->period($period)."
							GROUP BY user_id
							) AS T
							GROUP BY T.user_id
							ORDER BY SumUser DESC LIMIT $numberOf
                    	" );

	        set_transient( 'wp_ulike_get_most_likers', $likers, 6 * HOUR_IN_SECONDS );
		}				

		foreach ($likers as $liker) {
			$get_user_id        = stripslashes($liker->user_id);
			$get_user_info      = get_userdata($get_user_id);
			$get_likes_count    = $liker->SumUser;
			$return_profile_url = '#';
			$echo_likes_count   = $show_count ? ' ('.$get_likes_count . ' ' . __('Like',WP_ULIKE_SLUG).')' : '';
			
			if( $profile_url == 'bp' && function_exists('bp_core_get_user_domain') ) {
				$return_profile_url = bp_core_get_user_domain( $liker->user_id );			
			} elseif( $profile_url == 'um' && function_exists('um_fetch_user') ) {
				um_fetch_user( $liker->user_id );
				$return_profile_url = um_user_profile_url();
			}
			
			if( ! empty( $get_user_info ) ){
				$result .= $before_item;
				$result .= '<a href="'.$return_profile_url.'" class="user-tooltip" title="'.$get_user_info->display_name . $echo_likes_count.'">'.get_avatar( $get_user_info->user_email, $sizeOf, '' , 'avatar').'</a>';
				$result .= $after_item;
			}
		}

		return $result;
	}
    
	/**
	 * Get The Post Thumbnail
	 *
	 * @author       	Alimir
	 * @since           2.3
	 * @return			String
	 */		
	public function get_post_thumbnail( $id, $sizeOf ){
		$thumbnail = get_the_post_thumbnail( $id, array( $sizeOf, $sizeOf), array( 'class' => 'wp_ulike_thumbnail' ) );
		if($thumbnail != ''){
			return $thumbnail;
		} else {
			return '<img src="'.WP_ULIKE_ASSETS_URL.'/img/no-thumbnail.png" class="wp_ulike_thumbnail" alt="no-thumbnail" width="'.$sizeOf.'"/>';
		}
	}
    
	/**
	 * Set Period 
	 *
	 * @author       	Alimir
	 * @since           2.4
	 * @return			String
	 */	    
    public function period($period){
        switch ($period) {
			case "today":
				return "AND DATE(date_time) = DATE(NOW())";
			case "yesterday":
				return "AND DATE(date_time) = DATE(subdate(current_date, 1))";
			case "week":
				return "AND week(DATE(date_time)) = week(DATE(NOW()))";
			case "month":
				return "AND month(DATE(date_time)) = month(DATE(NOW()))";
			case "year":
				return "AND year(DATE(date_time)) = year(DATE(NOW()))";
			default:
				return "";
        }
    }

	/**
	 * Display Widget
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.3
	 * @updated         2.4
	 * @return			String
	 */		
	public function widget( $args, $instance ) {
		$title = apply_filters('widget_title', $instance['title'] );
		$type  = $instance['type'];   
		$style = $instance['style']; 
        
		$settings = array(
			"numberOf"    => $instance['count'],
			"period"      => $instance['period'],
			"sizeOf"      => $instance['size'],
			"trim"        => $instance['trim'],
			"profile_url" => $instance['profile_url'],
			"show_count"  => $instance['show_count'],
			"show_thumb"  => $instance['show_thumb'],
			"before_item" => '<li>',
			"after_item"  => '</li>'
		);
		
		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo '<ul class="most_liked_'.$type.' wp_ulike_style_'.$style.'">';
		if( $type == "post" ){
			echo $this->most_liked_posts( $settings );
		} elseif( $type == "comment" ){
			echo $this->most_liked_comments( $settings );
		} elseif( $type == "activity" ){
			echo $this->most_liked_activities( $settings );
		} elseif( $type == "topic" ){
			echo $this->most_liked_topics( $settings );
		} elseif( $type == "users" ){
			echo $this->most_liked_users( $settings );
		} elseif( $type == "last_posts_liked" ){
			echo $this->last_posts_liked_by_current_user( $settings );
		}
		echo '</ul>';

		echo $args['after_widget'];
	}
	
	/**
	 * Widget Options
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.3
	 * @updated         3.0
	 * @return			String
	 */			
	public function form( $instance ) {
		//Set up some default widget settings.
		$defaults = array( 
				'title'       => __('Most Liked', WP_ULIKE_SLUG),
				'count'       => 10,
				'size'        => 32,
				'trim'        => 10,
				'profile_url' => 'bp',
				'show_count'  => false,
				'show_thumb'  => false,
				'type'        => 'post',
				'style'       => 'simple',
				'period'      => 'all'
			);
		// Make instance array
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', WP_ULIKE_SLUG); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" type="text">
		</p>
		 
		<p>
			<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e('Type:', WP_ULIKE_SLUG); ?></label>
			<select name="<?php echo $this->get_field_name( 'type' ); ?>" style="width:100%;">
				<option value="post" <?php selected( $instance['type'], "post" ); ?>><?php echo _e('Most Liked Posts', WP_ULIKE_SLUG); ?></option>
				<option value="comment" <?php selected( $instance['type'], "comment" ); ?>><?php echo _e('Most Liked Comments', WP_ULIKE_SLUG); ?></option>
				<option value="activity" <?php selected( $instance['type'], "activity" ); ?>><?php echo _e('Most Liked Activities', WP_ULIKE_SLUG); ?></option>
				<option value="topic" <?php selected( $instance['type'], "topic" ); ?>><?php echo _e('Most Liked Topics', WP_ULIKE_SLUG); ?></option>
				<option value="users" <?php selected( $instance['type'], "users" ); ?>><?php echo _e('Most Liked Users', WP_ULIKE_SLUG); ?></option>
				<option value="last_posts_liked" <?php selected( $instance['type'], "last_posts_liked" ); ?>><?php echo _e('Last Posts Liked By User', WP_ULIKE_SLUG); ?></option>
			</select>			
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e('Number of items to show:', WP_ULIKE_SLUG); ?></label>
			<input id="<?php echo $this->get_field_id( 'count' ); ?>" class="tiny-text" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>"  step="1" min="1" size="3" type="number">
		</p>		

		<p>
			<label for="<?php echo $this->get_field_id( 'period' ); ?>"><?php _e('Period:', WP_ULIKE_SLUG); ?></label>
			<select name="<?php echo $this->get_field_name( 'period' ); ?>" style="width:100%;">
				<option value="all" <?php selected( $instance['period'], "all" ); ?>><?php echo _e('All The Times', WP_ULIKE_SLUG); ?></option>
				<option value="year" <?php selected( $instance['period'], "year" ); ?>><?php echo _e('Year', WP_ULIKE_SLUG); ?></option>
				<option value="month" <?php selected( $instance['period'], "month" ); ?>><?php echo _e('Month', WP_ULIKE_SLUG); ?></option>
				<option value="week" <?php selected( $instance['period'], "week" ); ?>><?php echo _e('Week', WP_ULIKE_SLUG); ?></option>
				<option value="yesterday" <?php selected( $instance['period'], "yesterday" ); ?>><?php echo _e('Yesterday', WP_ULIKE_SLUG); ?></option>
				<option value="today" <?php selected( $instance['period'], "today" ); ?>><?php echo _e('Today', WP_ULIKE_SLUG); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php _e('Style:', WP_ULIKE_SLUG); ?></label>
			<select name="<?php echo $this->get_field_name( 'style' ); ?>" style="width:100%;">
				<option value="simple" <?php selected( $instance['style'], "simple" ); ?>><?php echo _e('Simple', WP_ULIKE_SLUG); ?></option>
				<option value="love" <?php selected( $instance['style'], "love" ); ?>><?php echo _e('Heart', WP_ULIKE_SLUG); ?></option>
			</select>			
		</p>		
		 
		<p>
			<label for="<?php echo $this->get_field_id( 'trim' ); ?>"><?php _e('Title Trim (Length):', WP_ULIKE_SLUG); ?></label>
			<input id="<?php echo $this->get_field_name( 'trim' ); ?>" class="tiny-text" name="<?php echo $this->get_field_name( 'trim' ); ?>" value="<?php echo $instance['trim']; ?>"  step="1" min="1" size="3" type="number">
		</p>
	
		
		<p>
			<label for="<?php echo $this->get_field_id( 'profile_url' ); ?>"><?php _e('Profile URL:', WP_ULIKE_SLUG); ?></label>
			<select name="<?php echo $this->get_field_name( 'profile_url' ); ?>" style="width:100%;">
				<option value="bp" <?php selected( $instance['profile_url'], "bp" ); ?>><?php echo _e('BuddyPress', WP_ULIKE_SLUG); ?></option>
				<option value="um" <?php selected( $instance['profile_url'], "um" ); ?>><?php echo _e('UltimateMember', WP_ULIKE_SLUG); ?></option>
			</select>			
		</p>		

		<p>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" <?php if($instance['show_count'] == true) echo 'checked="checked"'; ?> /> 
			<label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e('Activate Like Counter', WP_ULIKE_SLUG); ?></label>
		</p>
		
		<p>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_thumb' ); ?>" name="<?php echo $this->get_field_name( 'show_thumb' ); ?>" <?php if($instance['show_thumb'] == true) echo 'checked="checked"'; ?> /> 
			<label for="<?php echo $this->get_field_id( 'show_thumb' ); ?>"><?php _e('Activate Thumbnail/Avatar', WP_ULIKE_SLUG); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e('Thumbnail/Avatar size:', WP_ULIKE_SLUG); ?><small> (min. 8)</small></label>
			<input id="<?php echo $this->get_field_id( 'size' ); ?>" class="tiny-text" name="<?php echo $this->get_field_name( 'size' ); ?>" value="<?php echo $instance['size']; ?>" step="1" min="8" size="3" type="number">
		</p>		
		
		<?php 
	}

	/**
	 * Widget Options Update
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.3
	 * @updated         3.0
	 * @return			String
	 */	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// Delete widgets transient
		switch ( strip_tags( $new_instance['type'] ) ) {
			case 'post':
				delete_transient( 'wp_ulike_get_most_liked_posts' );
				break;
			
			case 'comment':
				delete_transient( 'wp_ulike_get_most_liked_comments' );
				break;
			
			case 'activity':
				delete_transient( 'wp_ulike_get_most_liked_activities' );
				break;
			
			case 'topic':
				delete_transient( 'wp_ulike_get_most_liked_topics' );
				break;

			case 'users':
				delete_transient( 'wp_ulike_get_most_likers' );
				break;
		}

		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['count']       = strip_tags( $new_instance['count'] );
		$instance['type']        = strip_tags( $new_instance['type'] );
		$instance['period']      = strip_tags( $new_instance['period'] );
		$instance['style']       = strip_tags( $new_instance['style'] );
		$instance['size']        = strip_tags( $new_instance['size'] );
		$instance['trim']        = strip_tags( $new_instance['trim'] );
		$instance['profile_url'] = strip_tags( $new_instance['profile_url'] );
		$instance['show_count']  = isset($new_instance['show_count']) ? true : false;
		$instance['show_thumb']  = isset($new_instance['show_thumb']) ? true : false;

		return $instance;
	}

}