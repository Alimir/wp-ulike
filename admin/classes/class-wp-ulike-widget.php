<?php
/**
 * Class for our widget support
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_widget' ) ) {

	class wp_ulike_widget extends WP_Widget {

		/**
		 * Constructor
		 */
		function __construct() {
			parent::__construct(
				'wp_ulike',
				esc_html__('WP Ulike Widget', 'wp-ulike'),
				array( 'description' => esc_html__( 'An advanced widget that gives you all most liked records with different types', 'wp-ulike' ))
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

			$posts = wp_ulike_get_most_liked_posts( $numberOf, '', 'post', $period );

			if( empty( $posts ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', 'wp-ulike' ), $period_info, esc_html__( 'period', 'wp-ulike' ) );
			}

			foreach ($posts as $post) {
				// Check post title existence
				if( empty( $post->post_title ) ){
					continue;
				}

				$post_title = stripslashes($post->post_title);
				$permalink  = get_permalink($post->ID);
				$post_count = $this->get_counter_value($post->ID, 'post', 'like', $period );

				$result .= sprintf(
					'%s %s<a href="%s">%s</a> %s %s',
					$before_item,
					$show_thumb ? $this->get_post_thumbnail( $post->ID, $sizeOf ) : '',
					$permalink,
					wp_trim_words( $post_title, $trim, '...' ),
					$show_count ? '<span class="wp_counter_span">' . wp_ulike_format_number( $post_count, 'like' ) . '</span>' : '',
					$after_item
				);
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

			 $comments = wp_ulike_get_most_liked_comments( $numberOf, '', $period );

			if( empty( $comments ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', 'wp-ulike' ), $period_info, esc_html__( 'period', 'wp-ulike' ) );
			}

			foreach ($comments as $comment) {
				$comment_author      = stripslashes($comment->comment_author);
				$post_title          = get_the_title($comment->comment_post_ID);
				$comment_permalink   = get_comment_link($comment->comment_ID);
				$comment_likes_count = $this->get_counter_value($comment->comment_ID, 'comment', 'like', $period);

				$result .= sprintf(
					'%s %s <span class="comment-info"><span class="comment-author-link">%s</span> %s <a href="%s">%s</a></span> %s %s',
					$before_item,
					$show_thumb ? get_avatar( $comment->comment_author_email, $sizeOf ) : '',
					$comment_author,
					esc_html__('on','wp-ulike'),
					$comment_permalink,
					wp_trim_words( $post_title, $trim, '...' ),
					$show_count ? '<span class="wp_counter_span">' . wp_ulike_format_number( $comment_likes_count, 'like' ) . '</span>' : '',
					$after_item
				);
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

			$currentUser = is_user_logged_in() ? get_current_user_id() : wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
			$getPosts    = NULL;


			if( empty( $period ) || $period == 'all' ){
				$pinnedItems = wp_ulike_get_meta_data( $currentUser, 'user', 'post_status', true );
				// Exclude like status
				$pinnedItems = ! empty( $pinnedItems ) ? array_filter($pinnedItems, function($v, $k) {
					return $v == 'like';
				}, ARRAY_FILTER_USE_BOTH) : NULL;

				if( ! empty( $pinnedItems ) ){
					$getPosts = get_posts( array(
						'post_type'      => get_post_types_by_support( array(
							'title',
							'editor',
							'thumbnail'
						) ),
						'post_status'    => array( 'publish', 'inherit' ),
						'posts_per_page' => $numberOf,
						'post__in'       => array_reverse( array_keys( $pinnedItems ) ),
						'orderby'        => 'post__in'
					) );
				}

			} else {
				$getPosts = wp_ulike_get_most_liked_posts( $numberOf, '', 'post', $period, array( 'like' ), false, 1, $currentUser );
			}

			$result = '';

			if( ! empty( $getPosts ) ){
				ob_start();
				foreach ( $getPosts as $post ) :
					echo $before_item;
					?>
					<a href="<?php echo get_the_permalink( $post->ID ); ?>"><?php echo get_the_title( $post->ID ); ?></a>
				<?php
					echo $show_count ? '<span class="wp_counter_span">' . wp_ulike_format_number( $this->get_counter_value($post->ID, 'post', 'like', $period ), 'like' ) . '</span>' : '';
					echo $after_item;
				endforeach;
				$result = ob_get_clean();
			} else {
				$result = $before_item .  esc_html__( 'you haven\'t liked any post yet!','wp-ulike' ) . $after_item;
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

			if( ! function_exists( 'is_bbpress' ) ) {
				return '<li>' . sprintf( esc_html__( '%s is Not Activated!', 'wp-ulike' ) ,esc_html__( 'bbPress', 'wp-ulike' ) ) .'</li>';
			}

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

			$posts = wp_ulike_get_most_liked_posts( $numberOf, array( 'topic', 'reply' ), 'topic', $period );

			if( empty( $posts ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', 'wp-ulike' ), $period_info, esc_html__( 'period', 'wp-ulike' ) );
			}

			foreach ($posts as $post) {
				$post_title = function_exists('bbp_get_forum_title') ? bbp_get_forum_title( $post->ID ) : $post->post_title;
				$permalink  = 'topic' === get_post_type( $post->ID ) ? bbp_get_topic_permalink( $post->ID ) : bbp_get_reply_url( $post->ID );
				$post_count = $this->get_counter_value($post->ID, 'topic', 'like', $period);

				$result .= sprintf(
					'%s <a href="%s">%s</a> %s %s',
					$before_item,
					$permalink,
					wp_trim_words( $post_title, $trim, '...' ),
					$show_count ? '<span class="wp_counter_span">' . wp_ulike_format_number( $post_count, 'like' ) . '</span>' : '',
					$after_item
				);
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

			if( ! defined( 'BP_VERSION' ) ) {
				return '<li>' . sprintf( esc_html__( '%s is Not Activated!', 'wp-ulike' ) ,esc_html__( 'BuddyPress', 'wp-ulike' ) ) . '</li>';
			}

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

			$activities = wp_ulike_get_most_liked_activities( $numberOf, $period );

			if( empty( $activities ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', 'wp-ulike' ), $period_info, esc_html__( 'period', 'wp-ulike' ) );
			}

			foreach ($activities as $activity) {
				$activity_permalink = function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '';
				$activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
				$post_count         = $this->get_counter_value( $activity->id, 'activity', 'like', $period );

				// Skip empty activities
				if( empty( $activity_action ) ){
					continue;
				}

				$result .= sprintf(
					'%s <a href="%s">%s</a> %s %s',
					$before_item,
					esc_url( $activity_permalink ),
					wp_trim_words( $activity_action, $trim, '...' ),
					$show_count ? '<span class="wp_counter_span">'.wp_ulike_format_number( $post_count, 'like' ).'</span>' : '',
					$after_item
				);
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

			$likers = wp_ulike_get_best_likers_info( $numberOf, $period );
			foreach ($likers as $liker) {
				$get_user_id        = stripslashes($liker->user_id);
				$get_user_info      = get_userdata($get_user_id);
				$get_likes_count    = $liker->SumUser;
				$return_profile_url = '#';
				$echo_likes_count   = $show_count ? ' ('.$get_likes_count . ' ' . esc_html__('Like','wp-ulike').')' : '';

				if( $profile_url == 'bp' && function_exists('bp_core_get_user_domain') ) {
					$return_profile_url = bp_core_get_user_domain( $liker->user_id );
				} elseif( $profile_url == 'um' && function_exists('um_fetch_user') ) {
					um_fetch_user( $liker->user_id );
					$return_profile_url = um_user_profile_url();
				}

				if( ! empty( $get_user_info ) ){
					$result .= $before_item;
					$result .= '<a href="'.$return_profile_url.'" class="user-tooltip" title="'.esc_attr( $get_user_info->display_name ) . $echo_likes_count.'">'.get_avatar( $get_user_info->user_email, $sizeOf, '' , 'avatar').'</a>';
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
				return '<img src="'.WP_ULIKE_ASSETS_URL.'/img/no-thumbnail.png" class="wp_ulike_thumbnail" alt="no-thumbnail" width="'.esc_attr($sizeOf).'"/>';
			}
		}

		/**
		 * Get counter value
		 *
		 * @param integer $id
		 * @param string $slug
		 * @param string $status
		 * @param bool $is_distinct
		 * @return integer
		 */
		private function get_counter_value( $id, $slug, $status, $date_range = NULL ){
			$is_distinct = wp_ulike_setting_repo::isDistinct( $slug );
			return wp_ulike_get_counter_value( $id, $slug, $status, $is_distinct, $date_range );
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
					'title'       => esc_html__('Most Liked', 'wp-ulike'),
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
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e('Title:', 'wp-ulike'); ?></label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" type="text">
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php esc_html_e('Type:', 'wp-ulike'); ?></label>
				<select name="<?php echo $this->get_field_name( 'type' ); ?>" style="width:100%;">
					<option value="post" <?php selected( $instance['type'], "post" ); ?>><?php esc_html_e('Most Liked Posts', 'wp-ulike'); ?></option>
					<option value="comment" <?php selected( $instance['type'], "comment" ); ?>><?php esc_html_e('Most Liked Comments', 'wp-ulike'); ?></option>
					<option value="activity" <?php selected( $instance['type'], "activity" ); ?>><?php esc_html_e('Most Liked Activities', 'wp-ulike'); ?></option>
					<option value="topic" <?php selected( $instance['type'], "topic" ); ?>><?php esc_html_e('Most Liked Topics', 'wp-ulike'); ?></option>
					<option value="users" <?php selected( $instance['type'], "users" ); ?>><?php esc_html_e('Most Liked Users', 'wp-ulike'); ?></option>
					<option value="last_posts_liked" <?php selected( $instance['type'], "last_posts_liked" ); ?>><?php esc_html_e('Last Posts Liked By User', 'wp-ulike'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php esc_html_e('Number of items to show:', 'wp-ulike'); ?></label>
				<input id="<?php echo $this->get_field_id( 'count' ); ?>" class="tiny-text" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>"  step="1" min="1" size="3" type="number">
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'period' ); ?>"><?php esc_html_e('Period:', 'wp-ulike'); ?></label>
				<select name="<?php echo $this->get_field_name( 'period' ); ?>" style="width:100%;">
					<option value="all" <?php selected( $instance['period'], "all" ); ?>><?php esc_html_e('All The Times', 'wp-ulike'); ?></option>
					<option value="year" <?php selected( $instance['period'], "year" ); ?>><?php esc_html_e('Year', 'wp-ulike'); ?></option>
					<option value="month" <?php selected( $instance['period'], "month" ); ?>><?php esc_html_e('Month', 'wp-ulike'); ?></option>
					<option value="week" <?php selected( $instance['period'], "week" ); ?>><?php esc_html_e('Week', 'wp-ulike'); ?></option>
					<option value="yesterday" <?php selected( $instance['period'], "yesterday" ); ?>><?php esc_html_e('Yesterday', 'wp-ulike'); ?></option>
					<option value="today" <?php selected( $instance['period'], "today" ); ?>><?php esc_html_e('Today', 'wp-ulike'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php esc_html_e('Style:', 'wp-ulike'); ?></label>
				<select name="<?php echo $this->get_field_name( 'style' ); ?>" style="width:100%;">
					<option value="simple" <?php selected( $instance['style'], "simple" ); ?>><?php esc_html_e('Simple', 'wp-ulike'); ?></option>
					<option value="love" <?php selected( $instance['style'], "love" ); ?>><?php esc_html_e('Heart', 'wp-ulike'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'trim' ); ?>"><?php esc_html_e('Title Trim (Length):', 'wp-ulike'); ?></label>
				<input id="<?php echo $this->get_field_name( 'trim' ); ?>" class="tiny-text" name="<?php echo $this->get_field_name( 'trim' ); ?>" value="<?php echo $instance['trim']; ?>"  step="1" min="1" size="3" type="number">
			</p>


			<p>
				<label for="<?php echo $this->get_field_id( 'profile_url' ); ?>"><?php esc_html_e('Profile URL:', 'wp-ulike'); ?></label>
				<select name="<?php echo $this->get_field_name( 'profile_url' ); ?>" style="width:100%;">
					<option value="bp" <?php selected( $instance['profile_url'], "bp" ); ?>><?php esc_html_e('BuddyPress', 'wp-ulike'); ?></option>
					<option value="um" <?php selected( $instance['profile_url'], "um" ); ?>><?php esc_html_e('UltimateMember', 'wp-ulike'); ?></option>
				</select>
			</p>

			<p>
				<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" <?php if($instance['show_count'] == true) echo 'checked="checked"'; ?> />
				<label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php esc_html_e('Activate Like Counter', 'wp-ulike'); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_thumb' ); ?>" name="<?php echo $this->get_field_name( 'show_thumb' ); ?>" <?php if($instance['show_thumb'] == true) echo 'checked="checked"'; ?> />
				<label for="<?php echo $this->get_field_id( 'show_thumb' ); ?>"><?php esc_html_e('Activate Thumbnail/Avatar', 'wp-ulike'); ?></label>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php esc_html_e('Thumbnail/Avatar size:', 'wp-ulike'); ?><small> (min. 8)</small></label>
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

			$instance['title']       = wp_strip_all_tags( $new_instance['title'] );
			$instance['count']       = wp_strip_all_tags( $new_instance['count'] );
			$instance['type']        = wp_strip_all_tags( $new_instance['type'] );
			$instance['period']      = wp_strip_all_tags( $new_instance['period'] );
			$instance['style']       = wp_strip_all_tags( $new_instance['style'] );
			$instance['size']        = wp_strip_all_tags( $new_instance['size'] );
			$instance['trim']        = wp_strip_all_tags( $new_instance['trim'] );
			$instance['profile_url'] = wp_strip_all_tags( $new_instance['profile_url'] );
			$instance['show_count']  = isset($new_instance['show_count']) ? true : false;
			$instance['show_thumb']  = isset($new_instance['show_thumb']) ? true : false;

			return $instance;
		}

	}
}