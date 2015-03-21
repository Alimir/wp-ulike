<?php 
// Creating the most liked posts widget 
class wp_ulike_widget extends WP_Widget {

	/**
	 * Constructor
	 */	
	function __construct() {
		parent::__construct(
			'wp_ulike', 
			__('WP Ulike Widget', 'alimir'), 
			array( 'description' => __( 'An advanced widget that gives you all most liked records with different types', 'alimir' ))
			);
	}

	/**
	 * Most Liked Posts Function
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @return			String
	 */		
	public function most_liked_posts($numberOf, $before, $after, $show_count) {
		global $wpdb;

		$request = "SELECT ID, post_title, meta_value FROM ".$wpdb->prefix."posts, ".$wpdb->prefix."postmeta";
		$request .= " WHERE ".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id";
		$request .= " AND post_status='publish' AND meta_key='_liked'";
		$request .= " ORDER BY ".$wpdb->prefix."postmeta.meta_value+0 DESC LIMIT $numberOf";
		$posts = $wpdb->get_results($request);

		foreach ($posts as $post) {
			$post_title = stripslashes($post->post_title);
			$permalink = get_permalink($post->ID);
			$post_count = $post->meta_value;
			
			echo $before.'<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">' . $post_title . '</a>';
			echo $show_count == '1' ? ' ('.wp_ulike_format_number($post_count).')' : '';
			echo $after;
		}
	}

	/**
	 * Last Posts Liked By Current User
	 *
	 * @author       	Alimir
	 * @since           2.0
	 * @updated         2.1
	 * @return			String
	 */		
	public function last_posts_liked_by_current_user($numberOf, $before, $after, $show_count) {
		global $wpdb,$user_ID,$wp_user_IP;

		$request = "SELECT U.post_id, P.meta_value AS counter
					FROM ".$wpdb->prefix."ulike AS U, ".$wpdb->prefix."postmeta AS P
					WHERE (U.ip LIKE '$wp_user_IP' OR U.user_id = $user_ID) AND U.post_id = P.post_id AND meta_key='_liked'
					GROUP BY U.post_id
					ORDER BY MAX(U.date_time) DESC LIMIT $numberOf
					";
		$likes = $wpdb->get_results($request);
		
		if($likes != 0){
			foreach ($likes as $like) {
				$permalink 			= get_permalink($like->post_id);
				$post_title 		= get_the_title($like->post_id);
				$post_count 		= $like->counter;
				echo $before.'<a href="' . $permalink . '" title="' . $post_title.'" rel="nofollow">' . $post_title . '</a>';
				echo $show_count == '1' ? ' ('.wp_ulike_format_number($post_count).')' : '';
				echo $after;
			}
		}
		else{
				echo $before;
				echo __('you haven\'t liked any post yet!','alimir');		
				echo $after;		
		}
			
	}

	/**
	 * Most Liked Comments Function
	 *
	 * @author       	Alimir
	 * @since           1.9
	 * @return			String
	 */		
	public function most_liked_comments($numberOf, $before, $after, $show_count) {
		global $wpdb;

		$request  = "SELECT * FROM ".$wpdb->prefix."comments, ".$wpdb->prefix."commentmeta";
		$request .= " WHERE ".$wpdb->prefix."comments.comment_ID = ".$wpdb->prefix."commentmeta.comment_id";
		$request .= " AND comment_approved='1' AND meta_key='_commentliked'";
		$request .= " ORDER BY ".$wpdb->prefix."commentmeta.meta_value+0 DESC LIMIT $numberOf";
		$comments = $wpdb->get_results($request);

		foreach ($comments as $comment) {
			$comment_author = stripslashes($comment->comment_author);
			$post_permalink = get_permalink($comment->comment_post_ID);
			$post_title = get_the_title($comment->comment_post_ID);
			$comment_permalink = get_permalink($comment->comment_ID);
			$comment_likes_count = $comment->meta_value;
			
			echo $before.'<span class="comment-author-link">' . $comment_author . '</span> ' . __('on','alimir');
			echo ' <a href="' . $post_permalink . '#comment-' . $comment->comment_ID . '" title="' . $post_title.'" rel="nofollow">' . $post_title . '</a>';
			echo $show_count == '1' ? ' ('.wp_ulike_format_number($comment_likes_count).')' : '';
			echo $after;
		}
	}

	/**
	 * Most Liked Activities Function
	 *
	 * @author       	Alimir
	 * @since           2.0
	 * @return			String
	 */		
	public function most_liked_activities($numberOf, $before, $after, $show_count) {
		global $wpdb;

		$request  = "SELECT * FROM ".$wpdb->prefix."bp_activity, ".$wpdb->prefix."bp_activity_meta";
		$request .= " WHERE ".$wpdb->prefix."bp_activity.id = ".$wpdb->prefix."bp_activity_meta.activity_id";
		$request .= " AND meta_key='_activityliked'";
		$request .= " ORDER BY ".$wpdb->prefix."bp_activity_meta.meta_value+0 DESC LIMIT $numberOf";
		$activities = $wpdb->get_results($request);

		foreach ($activities as $activity) {
			$activity_permalink = bp_activity_get_permalink( $activity->activity_id );
			$activity_action = $activity->action;
			
			echo $before;
			echo $activity_action;
			echo $after;
		}
	}

	/**
	 * Most Liked Activities Function
	 *
	 * @author       	Alimir
	 * @since           1.2
	 * @updated         2.0
	 * @return			String
	 */		
	public function most_liked_users($numberOf, $before, $after, $show_count, $sizeOf) {
		global $wpdb;
		
		$request = "SELECT T.user_id, SUM(T.CountUser) AS SumUser
					FROM(
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike
					WHERE user_id BETWEEN 1 AND 999999
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike_activities
					WHERE user_id BETWEEN 1 AND 999999
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser
					FROM ".$wpdb->prefix."ulike_comments
					WHERE user_id BETWEEN 1 AND 999999
					GROUP BY user_id
					) AS T
					GROUP BY T.user_id
					ORDER BY SumUser DESC LIMIT $numberOf
					";
		$likes = $wpdb->get_results($request);

		foreach ($likes as $like) {
			$get_user_id = stripslashes($like->user_id);
			$get_user_info = get_userdata($get_user_id);
			$get_likes_count = $like->SumUser;
			$echo_likes_count = $show_count == '1' ? ' ('.$get_likes_count . ' ' . __('Like','alimir').')' : '';
			if($get_user_info != ''){
				echo $before . '<a class="user-tooltip" title="'.$get_user_info->display_name . $echo_likes_count.'">'.get_avatar( $get_user_info->user_email, $sizeOf, '' , 'avatar').'</a>';
				echo $after;
			}
		}
	}	

	/**
	 * Display Widget
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.0
	 * @return			String
	 */		
	public function widget( $args, $instance ) {
		$title = apply_filters('widget_title', $instance['title'] );
		$numberOf = $instance['count'];
		$type = $instance['type'];
		$sizeOf = $instance['size'];
		$show_count = (isset($instance['show_count']) == true ) ? 1 : 0;
		
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];
		echo '<ul class="most_liked_'.$type.'">';
		if($type == "post")
		echo $this->most_liked_posts($numberOf, '<li>', '</li>', $show_count);
		else if($type == "comment")
		echo $this->most_liked_comments($numberOf, '<li>', '</li>', $show_count);
		else if($type == "activity")
		echo $this->most_liked_activities($numberOf, '<li>', '</li>', $show_count);
		else if($type == "users")
		echo $this->most_liked_users($numberOf, '<li>', '</li>', $show_count, $sizeOf);
		else if($type == "last_posts_liked")
		echo $this->last_posts_liked_by_current_user($numberOf, '<li>', '</li>', $show_count);
		echo '</ul>';
		echo $args['after_widget'];
	}
	
	/**
	 * Widget Options
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.0
	 * @return			String
	 */			
	public function form( $instance ) {
		//Set up some default widget settings.
		$defaults = array( 'title' => __('Most Liked', 'alimir'), 'count' => 10, 'size' => 32, 'show_count' => false, 'type' => 'post' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'alimir'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		 
		<p>
			<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e('Type:', 'alimir'); ?></label>
			<select name="<?php echo $this->get_field_name( 'type' ); ?>" style="width:100%;">
				<option value="post" <?php selected( $instance['type'], "post" ); ?>><?php echo _e('Most Liked Posts', 'alimir'); ?></option>
				<option value="comment" <?php selected( $instance['type'], "comment" ); ?>><?php echo _e('Most Liked Comments', 'alimir'); ?></option>
				<option value="activity" <?php selected( $instance['type'], "activity" ); ?>><?php echo _e('Most Liked Activities', 'alimir'); ?></option>
				<option value="users" <?php selected( $instance['type'], "users" ); ?>><?php echo _e('Most Liked Users', 'alimir'); ?></option>
				<option value="last_posts_liked" <?php selected( $instance['type'], "last_posts_liked" ); ?>><?php echo _e('Last Posts Liked By User', 'alimir'); ?></option>
			</select>			
		</p>
		 
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e('Number of items to show:', 'alimir'); ?><small> (max. 15)</small></label>
			<input id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>" style="width:100%;" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e('User avatar size:', 'alimir'); ?><small> (min. 32)</small></label>
			<input id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" value="<?php echo $instance['size']; ?>" style="width:100%;" /><em><small style="color:#f00;">Just In Most Liked Users Type</small></em>
		</p>		

		<p>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" <?php if($instance['show_count'] == true) echo 'checked="checked"'; ?> /> 
			<label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e('Activate Like Counter', 'alimir'); ?></label>
		</p>	
		<?php 
	}

	/**
	 * Widget Options Update
	 *
	 * @author       	Alimir
	 * @since           1.1
	 * @updated         2.0
	 * @return			String
	 */	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['count'] = strip_tags( $new_instance['count'] );
		$instance['type'] = strip_tags( $new_instance['type'] );
		$instance['size'] = strip_tags( $new_instance['size'] );
		$instance['show_count'] = $new_instance['show_count'];

		return $instance;
	}
}