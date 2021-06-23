<?php
/**
 * Supported Item Types
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Post Types
*******************************************************/

if( ! function_exists( 'wp_ulike' ) ){
	/**
	 * Display Like button for posts
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           1.0
	 * @return			String
	 */
	function wp_ulike( $type = 'get', $args = array() ) {
		// Get item ID
		$post_ID = ! empty( $args['id'] ) ? $args['id'] : NULL;

		if( empty( $post_ID ) ){
			//global variables
			global $post;
			$post_ID = isset( $post->ID ) ? $post->ID : NULL;
		}

		// Return if post ID not exist
		if( empty( $post_ID ) ){
			return;
		}

		$attributes    = apply_filters( 'wp_ulike_posts_add_attr', null );
		$options       = wp_ulike_get_option( 'posts_group' );
		$post_settings = wp_ulike_get_post_settings_by_type( 'likeThis' );

		// Check deprecated option name
		if( ! empty( $options['disable_likers_pophover'] ) && ! isset( $options['likers_style'] ) ){
			$options['likers_style'] = 'default';
		}

		//Main data
		$defaults = array_merge( $post_settings, array(
			"id"                   => $post_ID,
			"method"               => 'likeThis',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"up_vote_inner_text"   => '',
			"down_vote_inner_text" => '',
			"options_group"        => 'posts_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"likers_style"         => isset( $options['likers_style'] ) ? $options['likers_style'] : 'popover',
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
		if( $type === 'put' ) {
			return $output;
		} else {
			echo $output;
		}
	}
}


if( ! function_exists( 'wp_ulike_get_most_liked_posts' ) ){
	/**
	 * Get most liked posts in query
	 *
	 * @param integer $numberposts
	 * @param array|string $post_type
	 * @param string $method
	 * @param string $period
	 * @param string $status
	 * @param boolean $is_noraml
	 * @param integer $offset
	 * @param string $user_id
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	function wp_ulike_get_most_liked_posts( $numberposts = 10, $post_type = '', $method = 'post', $period = 'all', $status = 'like', $is_noraml = false, $offset = 1, $user_id = '' ){
		// Get post types
		$post_type =  empty( $post_type ) ? get_post_types_by_support( array(
			'title',
			'editor',
			'thumbnail'
		) ) : $post_type;

		$post__in = wp_ulike_get_popular_items_ids(array(
			'type'     => $method,
			'rel_type' => $post_type,
			'status'   => $status,
			'period'   => $period,
			"offset"   => $offset,
			"user_id"  => $user_id,
			"limit"    => $numberposts
		));

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => array('publish', 'inherit'),
			'posts_per_page' => $numberposts
		);

		if( ! empty( $post__in ) ){
			$args['post__in'] = $post__in;
			$args['orderby'] = 'post__in';
		} elseif( empty( $post__in ) && ! $is_noraml ) {
			return false;
		}

		return get_posts( apply_filters( 'wp_ulike_get_top_posts_query', $args ) );
	}
}

if( ! function_exists( 'wp_ulike_get_post_likes' ) ){
	/**
	 * Get Single Post likes number
	 *
	 * @author       	Alimir
	 * @param           Integer $post_ID
	 * @since           1.7
	 * @return          String
	 */
	function wp_ulike_get_post_likes( $post_ID, $status = 'like' ){
		$is_distinct = wp_ulike_setting_repo::isDistinct( 'post' );
		return wp_ulike_get_counter_value( $post_ID, 'post', $status, $is_distinct );
	}
}

/*******************************************************
  Comments
*******************************************************/

if( ! function_exists( 'wp_ulike_comments' ) ){
	/**
	 * wp_ulike_comments function for comments like/unlike display
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           1.6
	 * @return			String
	 */
	function wp_ulike_comments( $type = 'get', $args = array() ) {

		$comment_ID       = isset( $args['id'] ) ? $args['id'] : get_comment_ID();
		$attributes       = apply_filters( 'wp_ulike_comments_add_attr', null );
		$options          = wp_ulike_get_option( 'comments_group' );
		$comment_settings = wp_ulike_get_post_settings_by_type( 'likeThisComment' );

		// Check deprecated option name
		if( ! empty( $options['disable_likers_pophover'] ) && ! isset( $options['likers_style'] ) ){
			$options['likers_style'] = 'default';
		}

		//Main data
		$defaults = array_merge( $comment_settings, array(
			"id"                   => $comment_ID,
			"method"               => 'likeThisComment',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"up_vote_inner_text"   => '',
			"down_vote_inner_text" => '',
			"options_group"        => 'comments_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"likers_style"         => isset( $options['likers_style'] ) ? $options['likers_style'] : 'popover',
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}

if( ! function_exists( 'wp_ulike_get_most_liked_comments' ) ){
	/**
	 * Get most liked comments in query
	 *
	 * @param integer $numbercomments
	 * @param string $post_type
	 * @param string $period
	 * @param string $status
	 * @return WP_Comment[]|int[] Array of post objects or post IDs.
	 */
	function wp_ulike_get_most_liked_comments( $numbercomments = 10, $post_type = '', $period = 'all', $status = 'like', $offset = 1, $user_id = ''){
		// Get post types
		$post_type =  empty( $post_type ) ? get_post_types_by_support( array(
			'title',
			'editor',
			'thumbnail'
		) ) : $post_type;

		// Get popular comments
		$comment__in = wp_ulike_get_popular_items_ids(array(
			"type"     => 'comment',
			'period'   => $period,
			'rel_type' => '',
			'status'   => $status,
			"user_id"  => $user_id,
			"offset"   => $offset,
			"limit"    => $numbercomments
		));

		if( empty( $comment__in ) ){
			return false;
		}

		return get_comments( apply_filters( 'wp_ulike_get_top_comments_query', array(
			'comment__in' => $comment__in,
			'orderby'     => 'comment__in',
			'post_type'   => $post_type
		) ) );

	}
}

if( ! function_exists( 'wp_ulike_get_comment_likes' ) ){
	/**
	 * Get the number of likes on a single comment
	 *
	 * @author          Alimir & Wacław Jacek
	 * @param           Integer $commentID
	 * @since           2.5
	 * @return          String
	 */
	function wp_ulike_get_comment_likes( $comment_ID, $status = 'like' ){
		$is_distinct = wp_ulike_setting_repo::isDistinct( 'comment' );
		return wp_ulike_get_counter_value( $comment_ID, 'comment', $status, $is_distinct );
	}
}

/*******************************************************
  BuddyPress
*******************************************************/

if( ! function_exists( 'wp_ulike_buddypress' ) ){
	/**
	 * wp_ulike_buddypress function for activities like/unlike display
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           1.7
	 * @return			String
	 */
	function wp_ulike_buddypress( $type = 'get', $args = array() ) {
		// Set activity ID
		if( isset( $args['id'] ) ){
			$activityID = $args['id'];
		} else {
			// Get activity comment id
			$commentID  = bp_get_activity_comment_id();
			$activityID = ! empty( $commentID ) ? $commentID : bp_get_activity_id();
		}

		$attributes          = apply_filters( 'wp_ulike_activities_add_attr', null );
		$options             = wp_ulike_get_option( 'buddypress_group' );
		$buddypress_settings = wp_ulike_get_post_settings_by_type( 'likeThisActivity' );

		// Check deprecated option name
		if( ! empty( $options['disable_likers_pophover'] ) && ! isset( $options['likers_style'] ) ){
			$options['likers_style'] = 'default';
		}

		//Main data
		$defaults = array_merge( $buddypress_settings, array(
			"id"                   => $activityID,
			"method"               => 'likeThisActivity',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"up_vote_inner_text"   => '',
			"down_vote_inner_text" => '',
			"options_group"        => 'buddypress_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"likers_style"         => isset( $options['likers_style'] ) ? $options['likers_style'] : 'popover',
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}

if( ! function_exists( 'wp_ulike_bbp_format_buddypress_notifications' ) ) {
	/**
	 * Wrapper for bbp_format_buddypress_notifications function as it is not returning $action
	 *
	 * @author       	Alimir
	 * @since           2.5.1
	 * @return          String
	 */
	function wp_ulike_bbp_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

		if ( ! defined( 'BP_VERSION' ) ) {
			return;
		}

		$result = bbp_format_buddypress_notifications(
			$action,
			$item_id,
			$secondary_item_id,
			$total_items,
			$format
		);

		if ( ! $result ) {
			$result = $action;
		}

		return $result;
	}
}

if( ! function_exists( 'wp_ulike_bbp_is_component_exist' ) ) {
	/**
	 * Check the buddypress notification component existence
	 *
	 * @author       	Alimir
	 * @since           2.5.1
	 * @return          integer
	 */
	function wp_ulike_bbp_is_component_exist( $component_name ){
		global $wpdb;
		$bp = buddypress();

		return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$bp->notifications->table_name} WHERE component_action = %s",
					$component_name
				)
			);
	}
}

if( ! function_exists( 'wp_ulike_get_most_liked_activities' ) ) {
	/**
	 * Get most liked activities in array
	 *
	 * @param integer $number
	 * @param string $period
	 * @param string $status
	 * @return object
	 */
	function wp_ulike_get_most_liked_activities( $number = 10, $period = 'all', $status = 'like', $offset = 1, $user_id = '' ){
		global $wpdb;

		if ( is_multisite() ) {
			$bp_prefix = 'base_prefix';
		} else {
			$bp_prefix = 'prefix';
		}

		$activity_ids = wp_ulike_get_popular_items_ids(array(
			'type'     => 'activity',
			'rel_type' => '',
			'status'   => $status,
			'period'   => $period,
			"user_id"  => $user_id,
			"offset"   => $offset,
			"limit"    => $number
		));

		if( empty( $activity_ids ) ){
			return false;
		}

		// generate query string
		$query  = sprintf( '
			SELECT * FROM
			`%1$sbp_activity`
			WHERE `id` IN (%2$s)
			ORDER BY FIELD(`id`, %2$s)',
			$wpdb->$bp_prefix,
			implode(',',$activity_ids)
		);

		return $wpdb->get_results( $query );
	}
}

/*******************************************************
  bbPress
*******************************************************/

if( ! function_exists( 'wp_ulike_bbpress' ) ){
	/**
	 * wp_ulike_bbpress function for topics like/unlike display
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           2.2
	 * @return			String
	 */
	function wp_ulike_bbpress( $type = 'get', $args = array() ) {

		// Check reply type
		$post_ID = bbp_get_reply_id();
		// If not exist, Then it's a topic
		if ( ! $post_ID ) {
			$post_ID = bbp_get_topic_id();
		}
		// Update post id for manual usage
		$post_ID = isset( $args['id'] ) ? $args['id'] : $post_ID;

		$attributes       = apply_filters( 'wp_ulike_topics_add_attr', null );
		$options          = wp_ulike_get_option( 'bbpress_group' );
		$bbpress_settings = wp_ulike_get_post_settings_by_type( 'likeThisTopic' );

		// Check deprecated option name
		if( ! empty( $options['disable_likers_pophover'] ) && ! isset( $options['likers_style'] ) ){
			$options['likers_style'] = 'default';
		}

		//Main data
		$defaults = array_merge( $bbpress_settings, array(
			"id"                   => $post_ID,
			"method"               => 'likeThisTopic',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"up_vote_inner_text"   => '',
			"down_vote_inner_text" => '',
			"options_group"        => 'bbpress_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"likers_style"         => isset( $options['likers_style'] ) ? $options['likers_style'] : 'popover',
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}