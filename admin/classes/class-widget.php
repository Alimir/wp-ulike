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
	public function most_liked_posts(array $settings) {
		global $wpdb;
        
        $request =  "SELECT posts.ID, posts.post_title, posts.post_content, meta.meta_value
                    FROM
                    ".$wpdb->prefix."posts AS posts,
                    ".$wpdb->prefix."postmeta AS meta,
                    ".$wpdb->prefix."ulike AS likes
                    WHERE
                    posts.ID = meta.post_ID
                    AND meta.post_ID = likes.post_id
                    AND posts.post_status = 'publish'
                    AND meta.meta_key = '_liked'
                    ".$this->period($settings['period'])."
                    GROUP BY posts.ID
                    ORDER BY CAST( meta.meta_value AS SIGNED ) DESC LIMIT ".$settings['numberOf']."
                    ";
        
		$posts = $wpdb->get_results($request);

		foreach ($posts as $post) {
			$post_title = stripslashes($post->post_title);
			$permalink = get_permalink($post->ID);
			$post_count = $post->meta_value;
			
			echo $settings['before_item'];
			echo $settings['show_thumb'] == '1' ? $this->get_post_thumbnail($post->ID, $settings['sizeOf']) : '';
			echo '<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">'. wp_trim_words( $post_title, $num_words = $settings['trim'], $more = null ) . '</a>';
			echo $settings['show_count'] == '1' ? ' <span class="wp_counter_span">'.wp_ulike_format_number($post_count).'</span>' : '';
			echo $settings['after_item'];
		}
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
	public function most_liked_comments(array $settings) {
		global $wpdb;
        
        $request =  "SELECT *
                    FROM
                    ".$wpdb->prefix."comments AS comments,
                    ".$wpdb->prefix."commentmeta AS meta,
                    ".$wpdb->prefix."ulike_comments AS likes
                    WHERE
                    comments.comment_ID = meta.comment_id
                    AND meta.comment_id = likes.comment_id
                    AND comments.comment_approved = '1'
                    AND meta.meta_key = '_commentliked'
                    ".$this->period($settings['period'])."
                    GROUP BY comments.comment_ID
                    ORDER BY CAST( meta.meta_value AS SIGNED ) DESC LIMIT ".$settings['numberOf']."
                    ";           
        
		$comments = $wpdb->get_results($request);

		foreach ($comments as $comment) {
			$comment_author = stripslashes($comment->comment_author);
			$post_permalink = get_permalink($comment->comment_post_ID);
			$post_title = get_the_title($comment->comment_post_ID);
			$comment_permalink = get_permalink($comment->comment_ID);
			$comment_likes_count = $comment->meta_value;
			
			echo $settings['before_item'];
			echo $settings['show_thumb'] == '1' ? get_avatar( $comment->comment_author_email, $settings['sizeOf'] ) : '';
			echo '<span class="comment-author-link">' . $comment_author . '</span> ' . __('on',WP_ULIKE_SLUG);
			echo ' <a href="' . $post_permalink . '#comment-' . $comment->comment_ID . '" title="' . $post_title.'" rel="nofollow">' . wp_trim_words( $post_title, $num_words = $settings['trim'], $more = null ) . '</a>';
			echo $settings['show_count'] == '1' ? ' <span class="wp_counter_span">'.wp_ulike_format_number($comment_likes_count).'</span>' : '';
			echo $settings['after_item'];
		}
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
	public function last_posts_liked_by_current_user(array $settings) {
		global $wpdb,$user_ID,$wp_user_IP;

		$request = "SELECT U.post_id, P.meta_value AS counter
					FROM ".$wpdb->prefix."ulike AS U,
                    ".$wpdb->prefix."postmeta AS P
					WHERE (U.ip LIKE '$wp_user_IP' OR U.user_id = $user_ID)
                    AND U.post_id = P.post_id AND meta_key='_liked'
					GROUP BY U.post_id
					ORDER BY MAX(U.date_time) DESC LIMIT ".$settings['numberOf']."
					";
		$likes = $wpdb->get_results($request);
		
		if($likes != 0){
			foreach ($likes as $like) {
				$permalink 			= get_permalink($like->post_id);
				$post_title 		= get_the_title($like->post_id);
				$post_count 		= $like->counter;
				echo  $settings['before_item'];
				echo $settings['show_thumb'] == '1' ? $this->get_post_thumbnail($like->post_id, $settings['sizeOf']) : '';
				echo '<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">' . wp_trim_words( $post_title, $num_words = $settings['trim'], $more = null ) . '</a>';
				echo $settings['show_count'] == '1' ? ' <span class="wp_counter_span">'.wp_ulike_format_number($post_count).'</span>' : '';
				echo $settings['after_item'];
			}
		}
		else{
				echo $settings['before_item'];
				echo __('you haven\'t liked any post yet!',WP_ULIKE_SLUG);		
				echo $settings['after_item'];		
		}
			
	}
	
	/**
	 * Most Liked Topics Function
	 *
	 * @author       	Alimir
	 * @since           2.3
	 * @updated         2.4
	 * @return			String
	 */		
	public function most_liked_topics(array $settings) {
		global $wpdb;
        
        $request =  "SELECT posts.ID, posts.post_title, posts.post_content, meta.meta_value
                    FROM
                    ".$wpdb->prefix."posts AS posts,
                    ".$wpdb->prefix."postmeta AS meta,
                    ".$wpdb->prefix."ulike_forums AS likes
                    WHERE
                    posts.ID = meta.post_ID
                    AND meta.post_ID = likes.topic_id
                    AND posts.post_status = 'publish'
                    AND meta.meta_key = '_topicliked'
                    ".$this->period($settings['period'])."
                    GROUP BY posts.ID
                    ORDER BY CAST( meta.meta_value AS SIGNED ) DESC LIMIT ".$settings['numberOf']."
                    ";
        
		$posts    = $wpdb->get_results($request);

		foreach ($posts as $post) {
			$post_title  = empty($post->post_title) ? $post->post_content : stripslashes($post->post_title);
			$permalink   = get_permalink($post->ID);
			$post_count  = $post->meta_value;
			
			echo $settings['before_item'];
			echo '<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">'. wp_trim_words( $post_title, $num_words = $settings['trim'], $more = null ) . '</a>';
			echo $settings['show_count'] == '1' ? ' <span class="wp_counter_span">'.wp_ulike_format_number($post_count).'</span>' : '';
			echo $settings['after_item'];
		}
	}	

	/**
	 * Most Liked Activities Function
	 *
	 * @author       	Alimir
	 * @since           2.0
	 * @updated         2.4
	 * @return			String
	 */		
	public function most_liked_activities(array $settings) {
		global $wpdb;
        
        if ( is_multisite() )
            $bp_prefix = 'base_prefix';
        else
            $bp_prefix = 'prefix';        
        
        $request =  "SELECT * FROM
                    ".$wpdb->$bp_prefix."bp_activity AS posts,
                    ".$wpdb->$bp_prefix."bp_activity_meta AS meta,
                    ".$wpdb->prefix."ulike_activities AS likes
                    WHERE posts.id = meta.activity_id
                    AND meta.activity_id = likes.activity_id
                    AND meta.meta_key = '_activityliked'
                    ".$this->period($settings['period'])."
                    GROUP BY posts.id
                    ORDER BY CAST( meta.meta_value AS SIGNED ) DESC LIMIT ".$settings['numberOf']."
                    ";
        
		$activities   = $wpdb->get_results($request);

		foreach ($activities as $activity) {
			$activity_permalink  = bp_activity_get_permalink( $activity->activity_id );
			$activity_action     = $activity->content;
            if (empty($activity_action))
            $activity_action     = $activity->action;
			
			echo $settings['before_item'];
			//echo strip_tags($activity_action);
            echo '<a href="' . $activity_permalink . '" rel="nofollow">';
            echo wp_trim_words( $activity_action, $num_words = $settings['trim'], $more = null );
            echo '</a>';
			echo $settings['after_item'];
		}
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
	public function most_liked_users(array $settings) {
		global $wpdb;
		
		$request = "SELECT T.user_id, SUM(T.CountUser) AS SumUser
					FROM(
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike
					WHERE user_id BETWEEN 1 AND 999999
                    ".$this->period($settings['period'])."
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike_activities
					WHERE user_id BETWEEN 1 AND 999999
                    ".$this->period($settings['period'])."
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike_comments
					WHERE user_id BETWEEN 1 AND 999999
                    ".$this->period($settings['period'])."
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike_forums
					WHERE user_id BETWEEN 1 AND 999999
                    ".$this->period($settings['period'])."
					GROUP BY user_id
					) AS T
					GROUP BY T.user_id
					ORDER BY SumUser DESC LIMIT ".$settings['numberOf']."
					";
		$likes = $wpdb->get_results($request);

		foreach ($likes as $like) {
			$get_user_id 		= stripslashes($like->user_id);
			$get_user_info 		= get_userdata($get_user_id);
			$get_likes_count 	= $like->SumUser;
			$return_profile_url	= '#';
			$echo_likes_count 	= $settings['show_count'] == '1' ? ' ('.$get_likes_count . ' ' . __('Like',WP_ULIKE_SLUG).')' : '';
			
			if($settings['profile_url'] == 'bp' && function_exists('bp_core_get_user_domain')){
				$return_profile_url = bp_core_get_user_domain($like->user_id);			
			}
			else if($settings['profile_url'] == 'um' && function_exists('um_fetch_user')){
				um_fetch_user($like->user_id);
				$return_profile_url = um_user_profile_url();
			}
			
			if($get_user_info != ''){
				echo $settings['before_item'];
                echo '<a href="'.$return_profile_url.'" class="user-tooltip" title="'.$get_user_info->display_name . $echo_likes_count.'">'.get_avatar( $get_user_info->user_email, $settings['sizeOf'], '' , 'avatar').'</a>';
				echo $settings['after_item'];
			}
		}
	}
    
	/**
	 * Get The Post Thumbnail
	 *
	 * @author       	Alimir
	 * @since           2.3
	 * @return			String
	 */		
	public function get_post_thumbnail($id,$sizeOf){
		$thumbnail = get_the_post_thumbnail( $id, array( $sizeOf, $sizeOf), array( 'class' => 'wp_ulike_thumbnail' ) );
		if($thumbnail != '')
		return $thumbnail;
		else
		return '<img src="'.plugin_dir_url( __FILE__ ).'/img/no-thumbnail.png" class="wp_ulike_thumbnail" alt="no-thumbnail" width="'.$sizeOf.'"/>';
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
		$title 		= apply_filters('widget_title', $instance['title'] );
		$type 		= $instance['type'];   
		$style 		= $instance['style']; 
        
		$settings = array(
			"numberOf" 	    => $instance['count'],
            "period" 	    => $instance['period'],
			"sizeOf" 	    => $instance['size'],
			"trim" 		    => $instance['trim'],
			"profile_url" 	=> $instance['profile_url'],
			"show_count"	=> (isset($instance['show_count']) == true ) ? 1 : 0,		
			"show_thumb" 	=> (isset($instance['show_thumb']) == true ) ? 1 : 0,
			"before_item" 	=> '<li>',
			"after_item" 	=> '</li>'
		);		        
		
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];
		echo '<ul class="most_liked_'.$type.' wp_ulike_style_'.$style.'">';
		if($type == "post")
		echo $this->most_liked_posts($settings);
		else if($type == "comment")
		echo $this->most_liked_comments($settings);
		else if($type == "activity")
		echo $this->most_liked_activities($settings);
		else if($type == "topic")
		echo $this->most_liked_topics($settings);
		else if($type == "users")
		echo $this->most_liked_users($settings);
		else if($type == "last_posts_liked")
		echo $this->last_posts_liked_by_current_user($settings);
		echo '</ul>';
		echo $args['after_widget'];
	}
	
	/**
	 * Widget Options
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.3
	 * @return			String
	 */			
	public function form( $instance ) {
		//Set up some default widget settings.
		$defaults = array( 'title' => __('Most Liked', WP_ULIKE_SLUG), 'count' => 10, 'size' => 32, 'trim' => 10, 'profile_url' => 'bp', 'show_count' => false, 'show_thumb' => false, 'type' => 'post', 'style' => 'simple', 'period' => 'all' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', WP_ULIKE_SLUG); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
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
			<input type="range" min="1" max="20" class="input-range" id="<?php echo $this->get_field_name( 'trim' ); ?>" name="<?php echo $this->get_field_name( 'trim' ); ?>" value="<?php echo $instance['trim']; ?>" style="width:100%;" />
			<span class="range-value" style="display: block; margin-top:5px; text-align:center; font-size:18px;"><?php echo $instance['trim']; ?></span>		
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e('Number of items to show:', WP_ULIKE_SLUG); ?></label>
			<input type="range" min="1" max="100" class="input-range" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>" style="width:100%;" />
			<span class="range-value" style="display: block; margin-top:5px; text-align:center; font-size:18px;"><?php echo $instance['count']; ?></span>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e('Thumbnail/Avatar size:', WP_ULIKE_SLUG); ?><small> (min. 8)</small></label>
			<input type="range" min="8" max="512" step="2" class="input-range" id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" value="<?php echo $instance['size']; ?>" style="width:100%;" />
			<span class="range-value" style="display: block; margin-top:5px; text-align:center; font-size:18px;"><?php echo $instance['size']; ?></span>
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
		
		<script>
		jQuery(document).ready(function($) {
			$('.input-range').on('input', function() {
			  $(this).next('.range-value').html(this.value);
			});
		});
		</script>
		
		<?php 
	}

	/**
	 * Widget Options Update
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.3
	 * @return			String
	 */	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['count'] = strip_tags( $new_instance['count'] );
		$instance['type'] = strip_tags( $new_instance['type'] );
		$instance['period'] = strip_tags( $new_instance['period'] );
		$instance['style'] = strip_tags( $new_instance['style'] );
		$instance['size'] = strip_tags( $new_instance['size'] );
		$instance['trim'] = strip_tags( $new_instance['trim'] );
		$instance['profile_url'] = strip_tags( $new_instance['profile_url'] );
		$instance['show_count'] = $new_instance['show_count'];
		$instance['show_thumb'] = $new_instance['show_thumb'];

		return $instance;
	}
}