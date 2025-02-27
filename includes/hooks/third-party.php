<?php
/**
 * Third-Party Plugins
 * // @echo HEADER
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  BuddyPress
*******************************************************/

if( ! function_exists( 'wp_ulike_put_buddypress' ) ){
	/**
	 * Auto insert wp_ulike_buddypress in the activities content
	 *
	 * @since 1.7
	 * @return void
	 */
	function wp_ulike_put_buddypress() {
		$options = wp_ulike_get_option( 'buddypress_group' );
		$action  = current_action();

		/**
		 * Don't implement on admin section
		 */
		if( WpUlikeInit::is_admin_backend() && ! WpUlikeInit::is_ajax() ){
			return;
		}

		if ( wp_ulike_setting_repo::isAutoDisplayOn('activity') ) {

			switch ( $action ) {
				case 'bp_activity_entry_meta':
					if ( isset( $options['auto_display_position'] ) && $options['auto_display_position'] === 'meta' ){
						echo wp_ulike_buddypress('put', array(
							'id' => bp_get_activity_id()
						));
					}
					break;

				case 'bp_activity_entry_content':
					if ( isset( $options['auto_display_position'] ) && $options['auto_display_position'] === 'content' ){
						echo wp_ulike_buddypress('put', array(
							'id' => bp_get_activity_id()
						));
					}
					break;
			}
		}
	}
	add_action( 'bp_activity_entry_meta', 'wp_ulike_put_buddypress', 15 );
	add_action( 'bp_activity_entry_content', 'wp_ulike_put_buddypress', 15 );
}

if( ! function_exists( 'wp_ulike_buddypress_activity_content_ajax_display' ) ){
	/**
	 * BuddyPress activity content display for ajax load more
	 *
	 * @param string $content
	 * @return string
	 */
	function wp_ulike_buddypress_activity_content_ajax_display( &$activity ) {
		$activityID = $activity->id;

		add_filter( 'bp_get_activity_content_body', function( $content ) use ( $activityID ){
			$options = wp_ulike_get_option( 'buddypress_group' );
			if ( wp_ulike_setting_repo::isAutoDisplayOn('activity') ) {
				if ( isset( $options['auto_display_position'] ) && $options['auto_display_position'] === 'content' ){
						return $content . wp_ulike_buddypress( 'put', array( 'id' => $activityID  ) );
				}
			}
			return $content;
		} );

	}
	add_action( 'bp_nouveau_get_single_activity_content', 'wp_ulike_buddypress_activity_content_ajax_display', 15, 1 );
}

if( ! function_exists( 'wp_ulike_buddypress_comment_content_display' ) ){
	/**
	 * BuddyPress Comment Content auto display hook
	 *
	 * @param string $content
	 * @param string $context
	 * @return string
	 */
	function wp_ulike_buddypress_comment_content_display( $content, $context ) {
		if( $context === 'get' && wp_ulike_setting_repo::isActivityCommentAutoDisplayOn() ) {
			if ( wp_ulike_get_option( 'buddypress_group|auto_display_position', 'content' ) === 'content' ){
					return $content . wp_ulike_buddypress('put', array(
						'id' => bp_get_activity_comment_id()
					));
			}
		}
		return $content;
	}
	add_filter( 'bp_activity_comment_content', 'wp_ulike_buddypress_comment_content_display', 15, 2 );
}

if( ! function_exists( 'wp_ulike_buddypress_comment_options_display' ) ){
	/**
	 * Buddypress comment meta auto display
	 *
	 * @return string
	 */
	function wp_ulike_buddypress_comment_options_display(){
		if( wp_ulike_setting_repo::isActivityCommentAutoDisplayOn() ){
			if ( wp_ulike_get_option( 'buddypress_group|auto_display_position', 'content' ) === 'meta' ){
				echo wp_ulike_buddypress('put', array(
					'id' => bp_get_activity_comment_id()
				));
			}
		}
	}
	add_action( 'bp_activity_comment_options', 'wp_ulike_buddypress_comment_options_display', 15 );
}

if( ! function_exists( 'wp_ulike_register_activity_actions' ) ){
	/**
	 * Register "WP ULike Activity" action
	 *
	 * @since 1.7
	 * @return void
	 */
	function wp_ulike_register_activity_actions() {
		global $bp;
		bp_activity_set_action(
			$bp->activity->id,
			'wp_like_group',
			esc_html__( 'WP ULike Activity', 'wp-ulike' )
		);
	}
	add_action( 'bp_register_activity_actions', 'wp_ulike_register_activity_actions' );
}

if( ! function_exists( 'wp_ulike_bp_activity_filter_options' ) ){
	/**
	 * Display likes option in BuddyPress activity filter
	 *
	 * @since 2.5.1
	 * @return void
	 */
	function wp_ulike_bp_activity_filter_options() {
		// Display Vote Notifications
		echo sprintf( '<option value="wp_like_group">%s</option>', esc_html__( 'Votes', 'wp-ulike' ) );
	}
	add_action( 'bp_activity_filter_options', 'wp_ulike_bp_activity_filter_options', 20 ); // Activity Directory
	add_action( 'bp_member_activity_filter_options', 'wp_ulike_bp_activity_filter_options', 20 ); // Member's profile activity
	add_action( 'bp_group_activity_filter_options', 'wp_ulike_bp_activity_filter_options', 20 ); // Group's activity
}

if( ! function_exists( 'wp_ulike_activity_querystring_filter' ) ){
	/**
	 * Builds an Activity Meta Query
	 *
	 * @param string $query_string
	 * @param string $object
	 * @return array
	 */
	function wp_ulike_activity_querystring_filter( $query_string = '', $object = '' ) {
		if( $object != 'activity' ){
			return $query_string;
		}

		// You can easily manipulate the query string
		// by transforming it into an array and merging
		// arguments with these default ones
		$args = wp_parse_args( $query_string, array(
			'action'  => false,
			'type'    => false,
			'user_id' => false,
			'page'    => 1
		) );

		if( $args['type'] === 'wp_like_group' ) {
			if( empty( $args['action'] ) ){
				$args['action'] = 'wp_like_group';
			}
			// on user's profile, shows the most favorited activities for displayed user
			if( bp_is_user() ){
				$args['user_id'] = bp_displayed_user_id();
			}

			$query_string = empty( $args ) ? $query_string : $args;
		}

		return $query_string;
	}
	add_filter( 'bp_ajax_querystring', 'wp_ulike_activity_querystring_filter', 20, 2 );
}

if( ! function_exists( 'wp_ulike_filter_notifications_get_registered_components' ) ){
	/**
	 * Register 'wp_ulike' to BuddyPress component
	 *
	 * @since 2.5
	 * @param array $component_names
	 * @return string
	 */
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
	add_filter( 'bp_notifications_get_registered_components', 'wp_ulike_filter_notifications_get_registered_components', 10 );
}


if( ! function_exists( 'wp_ulike_add_bp_notifications' ) ){
	/**
	 * Add new buddypress activities on each like
	 *
	 * @since 1.6
	 * @param integer $cp_ID
	 * @param string $type
	 * @param integer $user_ID
	 * @param string $status
	 * @param boolean $has_log
	 * @return void
	 */
	function wp_ulike_add_bp_notifications( $cp_ID, $type, $user_ID, $status, $has_log, $slug  ){

		// Return if user not logged in or an older data log exist
		if( ! is_user_logged_in() || $has_log > 0 || ! defined( 'BP_VERSION' ) ){
			return;
		}

		$options = wp_ulike_get_option( 'buddypress_group' );

		//Create a new activity when an user likes something
		if ( isset( $options['enable_add_bp_activity'] ) && wp_ulike_is_true( $options['enable_add_bp_activity'] ) ) {

			switch ( $slug ) {
				case 'post':
					// Replace the post variables
					$post_template = ! empty( $options['posts_notification_template'] ) ? $options['posts_notification_template'] : '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)';

					if ( strpos( $post_template, '%POST_LIKER%' ) !== false ) {
						$POST_LIKER    = bp_core_get_userlink( $user_ID );
						$post_template = str_replace( "%POST_LIKER%", $POST_LIKER, $post_template );
					}
					if ( strpos( $post_template, '%POST_PERMALINK%' ) !== false ) {
						$POST_PERMALINK = get_permalink($cp_ID);
						$post_template  = str_replace( "%POST_PERMALINK%", $POST_PERMALINK, $post_template );
					}
					if ( strpos( $post_template, '%POST_COUNT%' ) !== false ) {
						$POST_COUNT    = wp_ulike_get_post_likes( $cp_ID );
						$post_template = str_replace( "%POST_COUNT%", $POST_COUNT, $post_template );
					}
					if ( strpos( $post_template, '%POST_TITLE%' ) !== false ) {
						$POST_TITLE    = get_the_title( $cp_ID );
						$post_template = str_replace( "%POST_TITLE%", $POST_TITLE, $post_template );
					}
					bp_activity_add( apply_filters( 'wp_ulike_post_bp_notification_args',  array(
						'user_id'   => $user_ID,
						'action'    => wp_kses_post( $post_template ),
						'component' => 'activity',
						'type'      => 'wp_like_group',
						'item_id'   => $cp_ID
					) ) );
					break;

				case 'comment':
					// Replace the comment variables
					$comment_template = ! empty( $options['comments_notification_template'] ) ? $options['comments_notification_template'] : '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)';

					if ( strpos( $comment_template, '%COMMENT_LIKER%' ) !== false ) {
						$COMMENT_LIKER    = bp_core_get_userlink( $user_ID );
						$comment_template = str_replace("%COMMENT_LIKER%", $COMMENT_LIKER, $comment_template );
					}
					if ( strpos( $comment_template, '%COMMENT_PERMALINK%' ) !== false ) {
						$COMMENT_PERMALINK = get_comment_link( $cp_ID );
						$comment_template  = str_replace( "%COMMENT_PERMALINK%", $COMMENT_PERMALINK, $comment_template );
					}
					if ( strpos( $comment_template, '%COMMENT_AUTHOR%' ) !== false ) {
						$COMMENT_AUTHOR   = get_comment_author( $cp_ID );
						$comment_template = str_replace( "%COMMENT_AUTHOR%", $COMMENT_AUTHOR, $comment_template );
					}
					if ( strpos( $comment_template, '%COMMENT_COUNT%' ) !== false ) {
						$COMMENT_COUNT    = wp_ulike_get_comment_likes( $cp_ID );
						$comment_template = str_replace( "%COMMENT_COUNT%", $COMMENT_COUNT, $comment_template );
					}

					bp_activity_add( apply_filters( 'wp_ulike_comment_bp_notification_args', array(
						'user_id'   => $user_ID,
						'action'    => wp_kses_post( $comment_template ),
						'component' => 'activity',
						'type'      => 'wp_like_group',
						'item_id'   => $cp_ID
					) ) );
					break;

				default:
					break;
			}

		}

		// Sends out notifications when you get a like from someone
		if ( wp_ulike_setting_repo::hasNotification( $slug ) ) {
			// No notifications from Anonymous
			if ( ! $user_ID || false === get_userdata( $user_ID ) ) {
				return false;
			}
			$author_ID = wp_ulike_get_auhtor_id( $cp_ID, $type );
			if ( ! $author_ID || $author_ID == $user_ID ) {
				return false;
			}
			bp_notifications_add_notification( apply_filters( 'wp_ulike_bp_add_notification_args', array(
					'user_id'           => $author_ID,
					'item_id'           => $cp_ID,
					'secondary_item_id' => $user_ID,
					'component_name'    => 'wp_ulike',
					'component_action'  => 'wp_ulike' . $type . '_action',
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			) );
		}

	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_add_bp_notifications', 10, 6 );
}

if( ! function_exists( 'wp_ulike_format_buddypress_notifications' ) ){
	/**
	 * Format notifications related to activity.
	 *
	 * @param string $content               Component action. Deprecated. Do not do checks against this! Use
	 *                                      the 6th parameter instead - $component_action_name.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items     		Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $action 				Canonical notification action.
	 * @param string $component        		Notification component ID.
	 * @param int    $id                    Notification ID.
	 * @return string $return Formatted notification.
	 */
	function wp_ulike_format_buddypress_notifications( $content, $item_id, $secondary_item_id, $total_items, $format, $action, $component, $id ) {
		// check for ulike notifications
		if ( strpos( $action, 'wp_ulike_' ) !== false ) {
			//Extracting ulike type from the action value.
			preg_match('/wp_ulike_(.*?)_action/', $action, $type);
				//Extracting user id from old action name values.
				preg_match('/action_([0-9]+)/', $action, $user_ID);
			//Get user info
			$user_ID     = isset( $user_ID[1] ) ? $user_ID[1] : $secondary_item_id;
			$action_type = esc_html__( 'posts' , 'wp-ulike' );
			$custom_link = '';

			// Check the the ulike types
			switch ( $type[1] ) {
				case 'commentliked':
					$custom_link = get_comment_link( $item_id );
					$action_type = esc_html__( 'comments' , 'wp-ulike' );
					break;

				case 'activityliked':
					if( function_exists('bp_activity_get_permalink') ){
						$custom_link = bp_activity_get_permalink( $item_id );
						$action_type = esc_html__( 'activities' , 'wp-ulike' );
					}
					break;

				case 'topicliked':
					if( function_exists('bbp_get_topic_permalink') ){
						if( 'topic' === get_post_type( $item_id) ){
							$custom_link = bbp_get_topic_permalink( $item_id );
							$action_type = esc_html__( 'topics' , 'wp-ulike' );
						} else {
							$custom_link = bbp_get_reply_url( $item_id );
							$action_type = esc_html__( 'replies' , 'wp-ulike' );
						}
					}
					break;

				default:
					$custom_link = get_permalink( $item_id );
					break;
			}

			// Setup the output strings
			if ( (int) $total_items > 1 ) {
				$custom_text = sprintf(
					/* translators: 1: Total items, 2: Item type. */
					esc_html__( 'You have %1$d new %2$s likes', 'wp-ulike' ),
					(int) $total_items, $action_type );
				$custom_link = add_query_arg( 'type', $action, bp_get_notifications_permalink() );
			} else {
				$user_fullname = bp_core_get_user_displayname( $user_ID );
				$custom_text   = sprintf(
					/* translators: 1: User full name, 2: Item type. */
					esc_html__( '%1$s liked one of your %2$s', 'wp-ulike' ),
					$user_fullname, $action_type );
				$custom_link   = add_query_arg( 'read_ulike_notification', (int) $id, $custom_link );
			}

			// WordPress Toolbar
			if ( 'string' === $format ) {
				$content = apply_filters( 'wp_ulike_bp_notifications_template', '<a href="' . esc_url( $custom_link ) . '" title="' . wp_strip_all_tags( $custom_text ) . '">' . esc_html( $custom_text ) . '</a>', $custom_link, (int) $total_items, $item_id, $user_ID );
			// Deprecated BuddyBar
			} else {
				$content = apply_filters( 'wp_ulike_bp_notifications_template', array(
					'text' => $custom_text,
					'link' => $custom_link
				), $custom_link, (int) $total_items, $item_id, $user_ID );
			}
		}

		return $content;
	}
	add_filter( 'bp_notifications_get_notifications_for_user', 'wp_ulike_format_buddypress_notifications', 25, 8 );
}

if( ! function_exists( 'wp_ulike_notification_filters' ) ){
	/**
	 * Add ulike notifications to initial buddyPress filters
	 *
	 * @return void
	 */
	function wp_ulike_notification_filters(){
		$notifications = array(
			array(
				'id'       => 'wp_ulike_activityliked_action',
				'label'    => esc_html__( 'New activity liked', 'wp-ulike' ),
				'position' => 340,
			),
			array(
				'id'       => 'wp_ulike_commentliked_action',
				'label'    => esc_html__( 'New comment liked', 'wp-ulike' ),
				'position' => 345,
			),
			array(
				'id'       => 'wp_ulike_liked_action',
				'label'    => esc_html__( 'New post liked', 'wp-ulike' ),
				'position' => 355,
			),
			array(
				'id'       => 'wp_ulike_topicliked_action',
				'label'    => esc_html__( 'New topic liked', 'wp-ulike' ),
				'position' => 365,
			)
		);

		foreach ( $notifications as $notification ) {
			if( ! wp_ulike_bbp_is_component_exist( $notification['id'] ) ){
				continue;
			}
			bp_nouveau_notifications_register_filter( $notification );
		}
	}
	add_action( 'bp_nouveau_notifications_init_filters', 'wp_ulike_notification_filters' );
}

if( ! function_exists( 'wp_ulike_seen_bp_notifications' ) ){
	/**
	 * Mark notifications as read when a user visits an activity permalink.
	 *
	 * @since 3.6.0
	 */
	function wp_ulike_seen_bp_notifications() {
		if ( ! is_user_logged_in() || ! defined( 'BP_VERSION' ) ) {
			return;
		}

		$comment_id = 0;
		// For replies to a parent update.
		if ( isset( $_GET['read_ulike_notification'] ) && ! empty( $_GET['read_ulike_notification'] ) ) {
			$comment_id = (int) $_GET['read_ulike_notification'];
		}

		// Mark individual activity reply notification as read.
		if ( $comment_id ) {
			BP_Notifications_Notification::update(
				array(
					'is_new' => false
				),
				array(
					'user_id' => bp_loggedin_user_id(),
					'id'      => $comment_id
				)
			);
		}
	}
	add_action( 'wp_loaded', 'wp_ulike_seen_bp_notifications' );
}

/*******************************************************
  bbPress
*******************************************************/

if( ! function_exists( 'wp_ulike_put_bbpress' ) ){
	/**
	 * Auto insert wp_ulike_bbpress in the topics content
	 *
	 * @author       	Alimir
	 * @since           2.2
	 * @return          filter on bbpPress hooks
	 */
	function wp_ulike_put_bbpress() {
		/**
		 * Don't implement on admin section
		 */
		if( WpUlikeInit::is_admin_backend() && ! WpUlikeInit::is_ajax() ){
			return;
		}

		if ( wp_ulike_setting_repo::isAutoDisplayOn('topic') ) {
			$action   = current_action();
			$position = wp_ulike_get_option( 'bbpress_group|auto_display_position', 'bottom' );

			if( $position === 'top_bottom' ){
				echo wp_ulike_bbpress('put');
				return;
			}

			switch ( $action ) {
				case 'bbp_theme_before_reply_content':
					if( $position === 'top' ){
						echo wp_ulike_bbpress('put');
					}
					return;

				case 'bbp_theme_after_reply_content':
					if( $position === 'bottom' ){
						echo wp_ulike_bbpress('put');
					}
					return;

				case 'bbp_theme_before_topic_content':
					if( $position === 'top' && ! ( bbp_is_single_topic() && bbp_show_lead_topic() ) ){
						echo wp_ulike_bbpress('put');
					}
					return;

				case 'bbp_theme_after_topic_content':
					if( $position === 'bottom' && ! ( bbp_is_single_topic() && bbp_show_lead_topic() ) ){
						echo wp_ulike_bbpress('put');
					}
					return;
			}
		}
	}
	add_action( 'bbp_theme_before_reply_content', 'wp_ulike_put_bbpress', 15 );
	add_action( 'bbp_theme_after_reply_content', 'wp_ulike_put_bbpress', 15 );
	add_action( 'bbp_theme_before_topic_content', 'wp_ulike_put_bbpress', 15 );
	add_action( 'bbp_theme_after_topic_content', 'wp_ulike_put_bbpress', 15 );
}

if( ! function_exists( 'wp_ulike_put_bbpress_topic_content' ) ){
	/**
	 * display like button in bbpress topic
	 *
	 * @param string $content
	 * @param integer $topic_id
	 * @return string
	 */
	function wp_ulike_put_bbpress_topic_content( $content, $topic_id ) {
		// Stack variable
		$output = $content;

		/**
		 * Don't implement on admin section
		 */
		if( WpUlikeInit::is_admin_backend() && ! WpUlikeInit::is_ajax() ){
			return $content;
		}

		if ( wp_ulike_setting_repo::isAutoDisplayOn('topic') ) {
			// Get button
			$button = wp_ulike_bbpress('put', array(
				'id' => $topic_id
			));
			switch ( wp_ulike_get_option( 'bbpress_group|auto_display_position', 'bottom' ) ) {
				case 'top':
					$output = $button . $content;
					break;

				case 'top_bottom':
					$output = $button . $content . $button;
					break;

				default:
					$output = $content . $button;
					break;
			}
		}

		return $output;
	}
	add_filter( 'bbp_get_topic_content', 'wp_ulike_put_bbpress_topic_content', 15, 2 );
}

/*******************************************************
  Cache Plugins
*******************************************************/


if( ! function_exists( 'wp_ulike_purge_cache' ) ){
	/**
     * Purge third-party plugins cache
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_purge_cache( $ID, $type ){
		$controller = new wp_ulike_purge_cache();
		$reffer_url = wp_get_referer();

		if( $type === '_liked' ){
			$controller->purgeForPost( array( $ID ), $reffer_url );
		} elseif( $type === '_commentliked' ){
			$comment = get_comment( $ID );
			if( isset( $comment->comment_post_ID ) ){
				$controller->purgeForPost( array( $comment->comment_post_ID ), $reffer_url );
			}
		}
	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_purge_cache', 10, 2 );
}

/*******************************************************
  Other Plugins
*******************************************************/

// My Cred Plugin
if( ! function_exists( 'wp_ulike_register_myCRED_hook' ) ){
	/**
	 * register wp_ulike in mycred setup
	 *
	 * @since 2.3
	 * @param array $installed
	 * @return void
	 */
	function wp_ulike_register_myCRED_hook( $installed ) {
		$installed['wp_ulike'] = array(
			'title'       => esc_html__( 'WP ULike', 'wp-ulike' ) . ' : ' .  esc_html__( 'Points for liking content', 'wp-ulike' ),
			'description' => esc_html__( 'This hook award / deducts points from users who Like/Unlike any content of WordPress, bbPress, BuddyPress & ...', 'wp-ulike' ),
			'callback'    => array( 'wp_ulike_myCRED' )
		);
		return $installed;
	}
	add_filter( 'mycred_setup_hooks', 'wp_ulike_register_myCRED_hook' );
}
if( ! function_exists( 'wp_ulike_myCRED_references' ) ){
	/**
	 * Add ulike references
	 *
	 * @since 2.3
	 * @param array $list
	 * @return void
	 */
	function wp_ulike_myCRED_references( $list ) {
		$list['wp_add_like'] 	= esc_html__( 'Liking Content', 'wp-ulike' );
		$list['wp_get_like'] 	= esc_html__( 'Liked Content', 'wp-ulike' );
		$list['wp_add_unlike'] = esc_html__( 'Unliking Content', 'wp-ulike' );
		$list['wp_get_unlike'] = esc_html__( 'Unliked Content', 'wp-ulike' );
		return $list;
	}
	add_filter( 'mycred_all_references', 'wp_ulike_myCRED_references' );
}

// Ultimate Member plugin
if( ! function_exists( 'wp_ulike_add_custom_profile_tab' ) ){
	/**
	 * Add custom tabs in the UltimateMember profiles.
	 *
	 * @since 2.3
	 * @param array $tabs
	 * @return array $tabs
	 */
	function wp_ulike_add_custom_profile_tab( $tabs ) {

		$tabs['wp-ulike-posts'] = array(
			'name' => esc_html__('Recent Posts Liked','wp-ulike'),
			'icon' => 'um-faicon-thumbs-up',
		);

		$tabs['wp-ulike-comments'] = array(
			'name' => esc_html__('Recent Comments Liked','wp-ulike'),
			'icon' => 'um-faicon-thumbs-o-up',
		);

		return $tabs;
	}
	add_filter('um_profile_tabs', 'wp_ulike_add_custom_profile_tab', 1000 );
}

if( ! function_exists( 'wp_ulike_posts_um_profile_content' ) ){
	/**
	 * Add content to the wp-ulike-posts tab
	 *
	 * @since 2.3
	 * @param array $args
	 * @return void
	 */
	function wp_ulike_posts_um_profile_content() {
		//Main data
		$args = array(
			"type"       => 'post',
			"rel_type"   => 'post',
			"status"     => 'like',
			"user_id"    => um_profile_id(),
			"is_popular" => false,
			"limit"      => 10
		);

		$get_items = wp_ulike_get_popular_items_ids( $args );

		if( empty( $get_items ) ){
			echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. esc_html__('This user has not made any likes.','wp-ulike').'</span></div>';
			return;
		}

		$query_args = array(
			'post__in'       => $get_items,
			'orderby'        => 'post__in',
			'posts_per_page' => $args['limit']
		);

		$query = new WP_Query( $query_args );

		if( $query->have_posts() ):
				while( $query->have_posts() ): $query->the_post();
					echo '<div class="um-item">';
					echo '<div class="um-item-link">
							<i class="um-icon-ios-paper"></i>
							<a href="'.get_permalink().'">'.get_the_title().'</a>
							</div>';
					echo '<div class="um-item-meta">
							<span>'.get_the_date().'</span>
							<span class="badge"><i class="um-faicon-thumbs-o-up"></i> '.wp_ulike_get_post_likes( wp_ulike_get_the_id() ).'</span>
							</div>';
					echo '</div>';
				endwhile;
		else:
			echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. esc_html__('This user has not made any likes.','wp-ulike').'</span></div>';
		endif;
		wp_reset_postdata();

	}
	add_action('um_profile_content_wp-ulike-posts_default', 'wp_ulike_posts_um_profile_content');
}

if( ! function_exists( 'wp_ulike_comments_um_profile_content' ) ){
	/**
	 * Add content to the wp-ulike-comments tab
	 *
	 * @since 2.3
	 * @param array $args
	 * @return void
	 */
	function wp_ulike_comments_um_profile_content() {
		//Main data
		$args = array(
			"type"       => 'comment',
			"rel_type"   => '',
			"status"     => 'like',
			"user_id"    => um_profile_id(),
			"is_popular" => false,
			"limit"      => 10
		);

		$get_items = wp_ulike_get_popular_items_ids( $args );

		if( empty( $get_items ) ){
			echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. esc_html__('This user has not made any likes.','wp-ulike').'</span></div>';
			return;
		}

		$query_args = array(
			'comment__in'    => $get_items,
			'orderby'        => 'comment__in',
			'posts_per_page' => $args['limit']
		);

		// The Query
		$comments_query = new WP_Comment_Query;
		$comments = $comments_query->query( $query_args );

		// Comment Loop
		if ( $comments ) {
				foreach ( $comments as $comment ) {
					echo '<div class="um-item">';
					echo '<div class="um-item-link">
							<i class="um-icon-ios-chatboxes"></i>
							<a href="'.get_comment_link($comment->comment_ID).'">'.$comment->comment_content .'</a>
							<em style="font-size:.7em;padding:0 10px;"><span class="um-faicon-quote-left"></span> '.$comment->comment_author.' <span class="um-faicon-quote-right"></span></em>
							</div>';
					echo '<div class="um-item-meta">
							<span>'.get_comment_date( '', $comment->comment_ID ).'</span>
							<span class="badge"><i class="um-faicon-thumbs-o-up"></i> '.wp_ulike_get_comment_likes( $comment->comment_ID ).'</span>
							</div>';
					echo '</div>';
				}
		} else {
			echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. esc_html__('This user has not made any likes.','wp-ulike').'</span></div>';
		}
	}
	add_action('um_profile_content_wp-ulike-comments_default', 'wp_ulike_comments_um_profile_content');
}