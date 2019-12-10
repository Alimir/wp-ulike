<?php
/**
 * General Hooks
 * // @echo HEADER
 */

/*******************************************************
  General Hooks
*******************************************************/

if( ! function_exists( 'wp_ulike_register_widget' ) ){
	/**
	 * Register WP ULike Widgets
	 *
	 * @author Alimir
	 * @since 1.2
	 * @return Void
	 */
	function wp_ulike_register_widget() {
		register_widget( 'wp_ulike_widget' );
	}
	add_action( 'widgets_init', 'wp_ulike_register_widget' );
}

if( ! function_exists( 'wp_ulike_shortcode' ) ){
	/**
	 * Create shortcode: [wp_ulike]
	 *
	 * @author Alimir
	 * @param array $atts
	 * @param string $content
	 * @since 1.4
	 * @return void
	 */
	function  wp_ulike_shortcode( $atts, $content = null ){
		// Final result
		$result = '';
		// Default Args
		$args   = shortcode_atts( array(
					"for"           => 'post',	// shortcode Type (post, comment, activity, topic)
					"id"            => '',		// Post ID
					"slug"          => 'post',	// Slug Name
					"style"         => '',		// Get Default Theme
					"button_type"   => '',		// Set Button Type ('image' || 'text')
					"attributes"    => '',		// Get Attributes Filter
					"wrapper_class" => ''		// Extra Wrapper class
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
	add_shortcode( 'wp_ulike', 'wp_ulike_shortcode' );
}

if( ! function_exists( 'wp_ulike_generate_microdata' ) ){
	/**
	 * Generate rich snippet hooks
	 *
	 * @param array $args
	 * @return string
	 */
	function wp_ulike_generate_microdata( $args ){
		// Bulk output
		$output = '';

		// Check ulike type
		switch ( $args['type'] ) {
			case 'likeThis':
				$output = apply_filters( 'wp_ulike_posts_microdata', null );
				break;

			case 'likeThisComment':
				$output = apply_filters( 'wp_ulike_comments_microdata', null );
				break;

			case 'likeThisActivity':
				$output = apply_filters( 'wp_ulike_activities_microdata', null );
				break;

			case 'likeThisTopic':
				$output = apply_filters( 'wp_ulike_topics_microdata', null );
				break;
		}

		echo $output;
	}
	add_action( 'wp_ulike_inside_template', 'wp_ulike_generate_microdata' );
}

if( ! function_exists( 'wp_ulike_display_inline_likers_template' ) ){
	/**
	 * Display inline likers box without AJAX request
	 *
	 * @param array $args
	 * @since 3.5.1
	 * @return void
	 */
	function wp_ulike_display_inline_likers_template( $args ){
		// Get settings for current type
		$get_settings     = wp_ulike_get_post_settings_by_type( $args['type'] );
		// If method not exist, then return error message
		if( empty( $get_settings ) ) {
			return;
		}
		// Extract settings array
		extract( $get_settings );
		// Display likers box
		echo $args['disable_pophover'] && $args['display_likers'] ? sprintf(
			'<div class="wp_ulike_likers_wrapper wp_ulike_display_inline">%s</div>',
			wp_ulike_get_likers_template( $table, $column, $args['ID'], $setting )
		) : '';
	}
	add_action( 'wp_ulike_inside_template', 'wp_ulike_display_inline_likers_template' );
}




/*******************************************************
  Posts
*******************************************************/

if( ! function_exists( 'wp_ulike_put_posts' ) ){
	/**
	 * Auto insert wp_ulike function in the posts/pages content
	 *
	 * @param string $content
	 * @since 1.0
	 * @return string
	 */
	function wp_ulike_put_posts($content) {
		//auto display position
		$position = wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display_position');
		$button = '';

		//add wp_ulike function
		if(	!is_feed() && is_wp_ulike( wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display_filter') ) ){
			$button = wp_ulike('put');
		}

		//return by position
		if( $position=='bottom' ) {
			return $content . $button;
		} elseif( $position=='top' ) {
			return $button . $content;
		} elseif( $position=='top_bottom' ) {
			return $button . $content . $button;
		} else {
			return $content . $button;
		}
	}
	if (wp_ulike_get_setting( 'wp_ulike_posts', 'auto_display' ) == '1') {
		add_filter('the_content', 'wp_ulike_put_posts');
	}
}

if( ! function_exists( 'wp_ulike_get_posts_microdata_itemtype' ) ){
	/**
	 * Add itemtype to wp_ulike_posts_add_attr filter
	 *
	 * @return mixed
	 */
	function wp_ulike_get_posts_microdata_itemtype(){
		$get_ulike_count = wp_ulike_get_post_likes( get_the_ID() );
		if(!is_singular() || !wp_ulike_get_setting( 'wp_ulike_posts', 'google_rich_snippets') || $get_ulike_count == 0) return;
		return 'itemscope itemtype="http://schema.org/CreativeWork"';
	}
	add_filter('wp_ulike_posts_add_attr', 'wp_ulike_get_posts_microdata_itemtype');
}

if( ! function_exists( 'wp_ulike_get_posts_microdata' ) ){
	/**
	 * Add rich snippet for ratings in form of schema.org
	 *
	 * @since 2.7
	 * @return string
	 */
	function wp_ulike_get_posts_microdata(){
		global $post;
  		$get_ulike_count = wp_ulike_get_post_likes( $post->ID );
		// Check data output
		if( !is_singular() || !wp_ulike_get_setting( 'wp_ulike_posts', 'google_rich_snippets') || $get_ulike_count == 0 ) {
			return;
		}
		// Post meta structure
		$post_meta  = '<meta itemprop="name" content="' . the_title_attribute( 'echo=0' ) . '" />';
		$post_meta .= apply_filters( 'wp_ulike_extra_structured_data', NULL );
		$post_meta .= '<span itemprop="author" itemscope itemtype="http://schema.org/Person"><meta itemprop="name" content="' . esc_attr( get_the_author() ) . '" /></span>';
		$post_meta .= '<meta itemprop="datePublished" content="' . esc_attr( get_post_time('c') ) . '" />';
		// Rating meta structure
		$ratings_meta  = '<span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
		$ratings_meta	.= '<meta itemprop="bestRating" content="5" />';
		$ratings_meta .= '<meta itemprop="worstRating" content="1" />';
		$ratings_meta .= '<meta itemprop="ratingValue" content="'. wp_ulike_get_rating_value( $post->ID ) .'" />';
		$ratings_meta .= '<meta itemprop="ratingCount" content="' . $get_ulike_count . '" />';
		$ratings_meta .= '</span>';
		// Return value
		$itemtype  = apply_filters( 'wp_ulike_remove_microdata_post_meta', false );
		return apply_filters( 'wp_ulike_generate_google_structured_data', ( $itemtype ? $ratings_meta : ( $post_meta . $ratings_meta ) ) );
	}
	add_filter( 'wp_ulike_posts_microdata', 'wp_ulike_get_posts_microdata');
}

/*******************************************************
  Comments
*******************************************************/

if( ! function_exists( 'wp_ulike_put_comments' ) ){
	/**
	 * Auto insert wp_ulike_comments in the comments content
	 *
	 * @since 1.6
	 * @param string $content
	 * @return string
	 */
	function wp_ulike_put_comments( $content ) {
		//auto display position
		$position = wp_ulike_get_setting( 'wp_ulike_comments', 'auto_display_position');
		//add wp_ulike_comments function
		$button = wp_ulike_comments('put');
		//return by position
		if( $position=='bottom' ){
			return $content . $button;
		} elseif( $position=='top' ){
			return $button . $content;
		} elseif( $position=='top_bottom' ){
			return $button . $content . $button;
		} else {
			return $content . $button;
		}
	}

	if ( wp_ulike_get_setting( 'wp_ulike_comments', 'auto_display' ) == '1'  && ! is_admin() ) {
		add_filter('comment_text', 'wp_ulike_put_comments');
	}
}


/*******************************************************
  BuddyPress
*******************************************************/

if( defined( 'BP_VERSION' ) ) {

	if( ! function_exists( 'wp_ulike_put_buddypress' ) ){
		/**
		 * Auto insert wp_ulike_buddypress in the activities content
		 *
		 * @since 1.7
		 * @return void
		 */
		function wp_ulike_put_buddypress() {
			wp_ulike_buddypress('get');
		}
		if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display' ) == '1') {
			// Check display ulike in buddypress comments
			$display_comments = wp_ulike_get_setting( 'wp_ulike_buddypress', 'activity_comment', 1 );

			if (wp_ulike_get_setting( 'wp_ulike_buddypress', 'auto_display_position' ) == 'meta'){
				add_action( 'bp_activity_entry_meta', 'wp_ulike_put_buddypress' );
				// Add wp ulike in buddpress comments
				if( $display_comments == '1' ) {
					add_action( 'bp_activity_comment_options', 'wp_ulike_put_buddypress' );
				}
	        } else	{
	        	add_action( 'bp_activity_entry_content', 'wp_ulike_put_buddypress' );
	        	// Add wp ulike in buddpress comments
				if( $display_comments == '1' ) {
					add_filter( 'bp_get_activity_content', function( $content ) {
						// We've changed thhe 'bp_activity_comment_content' hook for making some ajax issues on inserting activity
						// If doing ajax, do not update it value
						// if( wp_doing_ajax() ) {
						// 	return $content;
						// }
						return $content . wp_ulike_buddypress('put');
					} );
				}
	        }
		}
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
				__( 'WP ULike Activity', WP_ULIKE_SLUG )
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
			echo "<option value='wp_like_group'>". __( 'Votes', WP_ULIKE_SLUG ) ."</option>";
		}
		add_action( 'bp_activity_filter_options', 'wp_ulike_bp_activity_filter_options' ); // Activity Directory
		add_action( 'bp_member_activity_filter_options', 'wp_ulike_bp_activity_filter_options' ); // Member's profile activity
		add_action( 'bp_group_activity_filter_options', 'wp_ulike_bp_activity_filter_options' ); // Group's activity
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
		function wp_ulike_add_bp_notifications( $cp_ID, $type, $user_ID, $status, $has_log  ){

			// Return if user not logged in or an older data log exist
			if( ! is_user_logged_in() || $has_log > 1 || ! function_exists( 'bp_is_active' ) ) return;

			//Create a new activity when an user likes something
			if (  wp_ulike_get_setting( 'wp_ulike_buddypress', 'new_likes_activity' ) == '1' ) {

				switch ( $type ) {
					case '_liked':
						// Replace the post variables
						$post_template = wp_ulike_get_setting( 'wp_ulike_buddypress', 'bp_post_activity_add_header', '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)' );

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
						bp_activity_add( array(
							'user_id'   => $user_ID,
							'action'    => $post_template,
							'component' => 'activity',
							'type'      => 'wp_like_group',
							'item_id'   => $cp_ID
						));
						break;

					case '_commentliked':
						// Replace the comment variables
						$comment_template = wp_ulike_get_setting( 'wp_ulike_buddypress', 'bp_comment_activity_add_header', '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)' );

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
						bp_activity_add( array(
							'user_id'   => $user_ID,
							'action'    => $comment_template,
							'component' => 'activity',
							'type'      => 'wp_like_group',
							'item_id'   => $cp_ID
						));
						break;

					default:
						break;
				}

			}

			//Sends out notifications when you get a like from someone
			if ( wp_ulike_get_setting( 'wp_ulike_buddypress', 'custom_notification' ) == '1' ) {
				// No notifications from Anonymous
				if ( ! $user_ID || false === get_userdata( $user_ID ) ) {
					return false;
				}
				$author_ID = wp_ulike_get_auhtor_id( $cp_ID, $type );
				if ( ! $author_ID || $author_ID == $user_ID ) {
					return false;
				}
				bp_notifications_add_notification( array(
						'user_id'           => $author_ID,
						'item_id'           => $cp_ID,
						'secondary_item_id' => $user_ID,
						'component_name'    => 'wp_ulike',
						'component_action'  => 'wp_ulike' . $type . '_action',
						'date_notified'     => bp_core_current_time(),
						'is_new'            => 1,
					)
				);
			}

		}
		add_action( 'wp_ulike_after_process', 'wp_ulike_add_bp_notifications', 10, 5 );
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
		function wp_ulike_format_buddypress_notifications( $content, $item_id, $secondary_item_id, $total_items, $format = 'string', $action, $component, $id ) {
			global $wp_filter,$wp_version;

			if ( strpos( $action, 'wp_ulike_' ) !== false ) {
				//Extracting ulike type from the action value.
				preg_match('/wp_ulike_(.*?)_action/', $action, $type);
			    //Extracting user id from old action name values.
			    preg_match('/action_([0-9]+)/', $action, $user_ID);
				//Get user info
				$user_ID     = isset( $user_ID[1] ) ? $user_ID[1] : $secondary_item_id;
				$action_type = __( 'posts' , WP_ULIKE_SLUG );
				$custom_link = '';

				// Check the the ulike types
				switch ( $type[1] ) {
					case 'commentliked':
						$custom_link = get_comment_link( $item_id );
						$action_type = __( 'comments' , WP_ULIKE_SLUG );
						break;

					case 'activityliked':
						$custom_link = bp_activity_get_permalink( $item_id );
						$action_type = __( 'activities' , WP_ULIKE_SLUG );
						break;

					default:
						$custom_link = get_permalink( $item_id );
						break;
				}

				// Setup the output strings
				if ( (int) $total_items > 1 ) {
					$custom_text = sprintf( __( 'You have %d new %s likes', WP_ULIKE_SLUG ), (int) $total_items, $action_type );
					$custom_link = add_query_arg( 'type', $action, bp_get_notifications_permalink() );
				} else {
					$user_fullname = bp_core_get_user_displayname( $user_ID );
					$custom_text   = sprintf( __( '%s liked one of your %s', WP_ULIKE_SLUG ), $user_fullname, $action_type );
					$custom_link   = add_query_arg( 'read_ulike_notification', (int) $id, $custom_link );
				}

				// WordPress Toolbar
				if ( 'string' === $format ) {
					$content = apply_filters( 'wp_ulike_bp_notifications_template', '<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_text ) . '">' . esc_html( $custom_text ) . '</a>', $custom_link, (int) $total_items, $item_id, $user_ID );
				// Deprecated BuddyBar
				} else {
					$content = apply_filters( 'wp_ulike_bp_notifications_template', array(
						'text' => $custom_text,
						'link' => $custom_link
					), $custom_link, (int) $total_items, $item_id, $user_ID );
				}

 				if ( function_exists('bbp_get_version') && version_compare( bbp_get_version(), '2.6.0' , '<') ) {
					// global wp_filter to call bbPress wrapper function
					if( isset( $wp_filter['bp_notifications_get_notifications_for_user'][10]['bbp_format_buddypress_notifications'] ) ) {
						if( version_compare( $wp_version, '4.7', '>=' ) ) {
							$wp_filter['bp_notifications_get_notifications_for_user']->callbacks[10]['bbp_format_buddypress_notifications']['function'] = 'wp_ulike_bbp_format_buddypress_notifications';
						} else {
							$wp_filter['bp_notifications_get_notifications_for_user'][10]['bbp_format_buddypress_notifications']['function'] = 'wp_ulike_bbp_format_buddypress_notifications';
						}
					}
				}

				return $content;
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
					'label'    => __( 'New activity liked', WP_ULIKE_SLUG ),
					'position' => 340,
				),
				array(
					'id'       => 'wp_ulike_commentliked_action',
					'label'    => __( 'New comment liked', WP_ULIKE_SLUG ),
					'position' => 345,
				),
				array(
					'id'       => 'wp_ulike_liked_action',
					'label'    => __( 'New post liked', WP_ULIKE_SLUG ),
					'position' => 355,
				),
				array(
					'id'       => 'wp_ulike_topicliked_action',
					'label'    => __( 'New topic liked', WP_ULIKE_SLUG ),
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
			if ( ! is_user_logged_in() ) {
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
}

/*******************************************************
  bbPress
*******************************************************/

if( ! function_exists( 'wp_ulike_put_bbpress' ) && function_exists( 'is_bbpress' ) ){
	/**
	 * Auto insert wp_ulike_bbpress in the topics content
	 *
	 * @author       	Alimir
	 * @since           2.2
	 * @return          filter on bbpPress hooks
	 */
	function wp_ulike_put_bbpress() {
		 wp_ulike_bbpress('get');
	}
	if (wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display' ) == '1') {
		if (wp_ulike_get_setting( 'wp_ulike_bbpress', 'auto_display_position' ) == 'top') {
			add_action( 'bbp_theme_before_reply_content', 'wp_ulike_put_bbpress' );
		} else {
			add_action( 'bbp_theme_after_reply_content', 'wp_ulike_put_bbpress' );
		}
	}
}

/*******************************************************
  Other Plugins
*******************************************************/

// My Cred Plugin
if( defined( 'myCRED_VERSION' ) ){
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
				'title'       => WP_ULIKE_NAME,
				'description' => __( 'This hook award / deducts points from users who Like/Unlike any content of WordPress, bbPress, BuddyPress & ...', WP_ULIKE_SLUG ),
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
			$list['wp_add_like'] 	= __( 'Liking Content', WP_ULIKE_SLUG );
			$list['wp_get_like'] 	= __( 'Liked Content', WP_ULIKE_SLUG );
			$list['wp_add_unlike'] = __( 'Unliking Content', WP_ULIKE_SLUG );
			$list['wp_get_unlike'] = __( 'Unliked Content', WP_ULIKE_SLUG );
			return $list;
		}
		add_filter( 'mycred_all_references', 'wp_ulike_myCRED_references' );
	}
}

// Ultimate Member plugin
if ( defined( 'ultimatemember_version' ) ) {
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
				'name' => __('Recent Posts Liked',WP_ULIKE_SLUG),
				'icon' => 'um-faicon-thumbs-up',
			);

			$tabs['wp-ulike-comments'] = array(
				'name' => __('Recent Comments Liked',WP_ULIKE_SLUG),
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
						  <span class="badge"><i class="um-faicon-thumbs-o-up"></i> '.wp_ulike_get_post_likes( $get_post->ID ).'</span>
						  </div>';
					echo '</div>';
				}
			} else echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. __('This user has not made any likes.',WP_ULIKE_SLUG).'</span></div>';
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
						  <span class="badge"><i class="um-faicon-thumbs-o-up"></i> '.wp_ulike_get_comment_likes( $comment->comment_ID ).'</span>
						  </div>';
					echo '</div>';
				}
			} else echo '<div style="display: block;" class="um-profile-note"><i class="um-faicon-frown-o"></i><span>'. __('This user has not made any likes.',WP_ULIKE_SLUG).'</span></div>';
		}
		add_action('um_profile_content_wp-ulike-comments_default', 'wp_ulike_comments_um_profile_content');
	}
}

// Litespeed cache plugin
if( ! function_exists( 'wp_ulike_purge_litespeed_cache' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_post' ) ){
	/**
	 * Purge litespeed post cache
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_purge_litespeed_cache( $ID, $type ){
		if( $type === '_liked' ){
			LiteSpeed_Cache_API::purge_post( $ID ) ;
		}
	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_purge_litespeed_cache'	, 10, 2 );
}

// w3 total cache plugin
if( ! function_exists( 'wp_ulike_purge_w3_total_cache' ) && function_exists( 'w3tc_pgcache_flush_post' ) ){
	/**
	 * Purge w3 total post cache
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_purge_w3_total_cache( $ID, $type ){
		if( $type === '_liked' ){
			w3tc_pgcache_flush_post( $ID );
		} elseif( $type === '_commentliked' ){
			$comment = get_comment( $ID );
			if( isset( $comment->comment_post_ID ) ){
				w3tc_pgcache_flush_post( $comment->comment_post_ID );
			}
		} else {
			w3tc_pgcache_flush();
		}
	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_purge_w3_total_cache'	, 10, 2 );
}

// wp fastest cache plugin
if( ! function_exists( 'wp_ulike_purge_wp_fastest_cache' ) && class_exists( 'WpFastestCache' ) ){
	/**
	 * Purge wp fastest cache
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_purge_wp_fastest_cache( $ID, $type ){
		if( !isset( $GLOBALS["wp_fastest_cache"] ) ){
			return;
		}
		$cache_interface = $GLOBALS["wp_fastest_cache"];

		// to remove cache if vote is from homepage or category page or tag
		if( isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] ){
			$url =  parse_url( $_SERVER["HTTP_REFERER"] );

			$url["path"] = isset($url["path"]) ? $url["path"] : "/index.html";

			$paths = array();

			array_push($paths, $cache_interface->getWpContentDir("/cache/all").$url["path"]);

			if(class_exists("WpFcMobileCache")){
				$wpfc_mobile = new WpFcMobileCache();
				array_push($paths, $cache_interface->getWpContentDir("/cache/wpfc-mobile-cache").$url["path"]);
			}

			foreach ($paths as $key => $value){
				if(file_exists($value)){
					if(preg_match("/\/(all|wpfc-mobile-cache)\/index\.html$/i", $value)){
						@unlink($value);
					}else{
						$cache_interface->rm_folder_recursively($value);
					}
				}
			}
		} elseif( in_array( $type, array( '_liked', '_commentliked' ) ) ){
			$comment_id = false;
			$post_id    = $ID;
			if( $type === '_commentliked' ){
				$comment = get_comment( $ID );
				if( isset( $comment->comment_post_ID ) ){
					$comment_id = $ID;
					$post_id = $comment->comment_post_ID;
				} else {
					$post_id = NULL;
				}
			}
			if( ! empty( $post_id ) ){
				$cache_interface->singleDeleteCache( $comment_id, $post_id );
			}
		}

	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_purge_wp_fastest_cache'	, 10, 2 );
}

// wp super cache plugin
if( ! function_exists( 'wp_ulike_purge_wp_super_cache' ) && function_exists( 'wpsc_delete_post_cache' ) ){
	/**
	 * Purge super post cache
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_purge_wp_super_cache( $ID, $type ){
		if( $type === '_liked' ){
			wpsc_delete_post_cache( $ID );
		} elseif( $type === '_commentliked' ){
			$comment = get_comment( $ID );
			if( isset( $comment->comment_post_ID ) ){
				wpsc_delete_post_cache( $comment->comment_post_ID );
			}
		}
	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_purge_wp_super_cache'	, 10, 2 );
}

// wp rocket cache plugin
if( ! function_exists( 'wp_ulike_purge_rocket_cache' ) && function_exists( 'rocket_clean_post' ) ){
	/**
  	 * Purge wp rocket cache
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_purge_rocket_cache( $ID, $type ){
		if( $type === '_liked' ){
			rocket_clean_post( $ID );
		} elseif( $type === '_commentliked' ){
			$comment = get_comment( $ID );
			if( isset( $comment->comment_post_ID ) ){
				rocket_clean_post( $comment->comment_post_ID );
			}
		}
	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_purge_rocket_cache'	, 10, 2 );
}