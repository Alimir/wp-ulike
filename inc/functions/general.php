<?php
/**
 * Global Functions
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'wp_ulike_get_setting' ) ){
	/**
	 * Get Settings Value
	 *
	 * @author       	Alimir
	 * @since           1.0
	 * @return			Void
	 */
	function wp_ulike_get_setting( $setting, $option = false, $default = false ) {
		$setting = get_option( $setting );
		if ( is_array( $setting ) ) {
			if ( $option ) {
				return isset( $setting[$option] ) ? wp_ulike_settings::parse_multi( $setting[$option] ) : $default;
			}
			foreach ( $setting as $k => $v ) {
				$setting[$k] = wp_ulike_settings::parse_multi( $v );
			}

			return $setting;
		}
		return $option ? $default : $setting;
	}
}

if ( ! function_exists( 'wp_ulike_get_option' ) ) {
	/**
	 * Get options list values
	 *
	 * @param string $option
	 * @param array|string $default
	 * @return array|string|null
	 */
	function wp_ulike_get_option( $option = '', $default = null ) {
	  $global_settings = get_option( 'wp_ulike_settings' );

	  if( strpos( $option, '|' ) && is_array( $global_settings ) ){
		$option_name  = explode( "|", $option );
		$option_stack = array();
		foreach ($option_name as $key => $value) {
			if( isset( $global_settings[$value] ) ){
				$option_stack = $global_settings[$value];
				continue;
			}
			if( isset( $option_stack[$value] ) ){
				$option_stack = $option_stack[$value];
			} else {
				return $default;
			}
		}
		return $option_stack;
	  }

	  return ( isset( $global_settings[$option] ) ) ? $global_settings[$option] : $default;
	}
}

if( ! function_exists( 'wp_ulike_get_table_info' ) ){
	/**
	 * Get table info
	 *
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_get_table_info( $type = 'post' ){
		$output = array();

		switch ( $type ) {
			case 'comment':
				$output = array(
					'table'          => 'ulike_comments',
					'column'         => 'comment_id',
					'related_table'  => 'comments',
					'related_column' => 'comment_ID'
				);
				break;

			case 'activity':
				$output = array(
					'table'          => 'ulike_activities',
					'column'         => 'activity_id',
					'related_table'  => 'bp_activity',
					'related_column' => 'id'
				);
				break;

			case 'topic':
				$output = array(
					'table'          => 'ulike_forums',
					'column'         => 'topic_id',
					'related_table'  => 'posts',
					'related_column' => 'ID'
				);
				break;

			default:
				$output = array(
					'table'          => 'ulike',
					'column'         => 'post_id',
					'related_table'  => 'posts',
					'related_column' => 'ID'
				);
				break;
		}

		return $output;
	}
}

if( ! function_exists( 'wp_ulike_get_type_by_table' ) ){
	/**
	 * Get type by table name
	 *
	 * @param string $table
	 * @return void
	 */
	function wp_ulike_get_type_by_table( $table ){
		$output = NULL;

		switch ( $table ) {
			case 'ulike_comments':
				$output = 'comment';
				break;

			case 'ulike_activities':
				$output = 'activity';
				break;

			case 'ulike_forums':
				$output = 'topic';
				break;

			case 'ulike':
				$output = 'post';
				break;
		}

		return $output;
	}
}

if( ! function_exists( 'is_wp_ulike' ) ){
	/**
	 * Check wp ulike callback
	 *
	 * @author       	Alimir
	 * @param           Array 	$options
	 * @param           Array   $args
	 * @since           1.9
	 * @return			boolean
	 */
	function is_wp_ulike( $options, $args = array(), $force_type = false ){

		if( empty( $options ) ){
			return true;
		}

		$defaults = apply_filters( 'wp_ulike_auto_diplay_filter_list' , array(
				'is_home'        => is_front_page() && is_home(),
				'is_single'      => is_singular(),
				'is_archive'     => is_archive(),
				'is_category'    => is_category(),
				'is_search'      => is_search(),
				'is_tag'         => is_tag(),
				'is_author'      => is_author(),
				'is_buddypress'  => function_exists('is_buddypress') && is_buddypress(),
				'is_bbpress'     => function_exists('is_bbpress') && is_bbpress(),
				'is_woocommerce' => function_exists('is_woocommerce') && is_woocommerce(),
			)
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		foreach ( $options as $key => $value ) {
			if( isset( $parsed_args[ 'is_' . $value ] ) && ! empty( $parsed_args[ 'is_' . $value ] ) ) {
				if( $value === 'single' && ! $force_type ){
					$post_types = wp_ulike_get_option( 'posts_group|auto_display_filter_post_types' );
					if( ! empty( $post_types ) ){
						foreach ($post_types as $p_key => $p_value) {
							if( get_post_type() === $p_value ){
								return true;
							}
						}
					}
				}
				return false;
			}
		}

		return true;
	}
}

if( ! function_exists( 'wp_ulike_get_auhtor_id' ) ){
	/**
	 * Get auther ID by the ulike types
	 *
	 * @author       	Alimir
	 * @param           Integer $cp_ID (Post/Comment/... ID)
	 * @param           String 	$type (Get ulike Type)
	 * @since           2.5
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
		else if( $type == '_activityliked' ){
			$activity = bp_activity_get_specific( array( 'activity_ids' => $cp_ID, 'display_comments'  => true ) );
			return $activity['activities'][0]->user_id;
		}
		else return;
	}
}

if( ! function_exists( 'wp_ulike_get_post_settings_by_type' ) ){
	/**
	 * Get post settings by its type
	 *
	 * @param string $post_type
	 * @param integer $post_ID (*deprecated)
	 * @return void
	 */
	function wp_ulike_get_post_settings_by_type( $post_type, $post_ID = NULL ){
		switch ( $post_type ) {
			case 'likeThis':
			case 'post':
				$settings = array(
					'setting'  => 'posts_group',
					'table'    => 'ulike',
					'column'   => 'post_id',
					'key'      => '_liked',
					'slug'     => 'post',
					'cookie'   => 'liked-'
				);
				break;

			case 'likeThisComment':
			case 'comment':
				$settings = array(
					'setting'  => 'comments_group',
					'table'    => 'ulike_comments',
					'column'   => 'comment_id',
					'key'      => '_commentliked',
					'slug'     => 'comment',
					'cookie'   => 'comment-liked-'
				);
				break;

			case 'likeThisActivity':
			case 'buddypress':
			case 'activity':
				$settings = array(
					'setting'  => 'buddypress_group',
					'table'    => 'ulike_activities',
					'column'   => 'activity_id',
					'key'      => '_activityliked',
					'slug'     => 'activity',
					'cookie'   => 'activity-liked-',
				);
				break;

			case 'likeThisTopic':
			case 'bbpress':
			case 'topic':
				$settings = array(
					'setting'  => 'bbpress_group',
					'table'    => 'ulike_forums',
					'column'   => 'topic_id',
					'key'      => '_topicliked',
					'slug'     => 'topic',
					'cookie'   => 'topic-liked-'
				);
				break;

			default:
				$settings = array();
		}

		return apply_filters( 'wp_ulike_get_post_settings_by_type', $settings, $post_ID );
	}
}

// @if DEV
// function wp_ulike_custom_author_counter( $user_id, $status = 'like' ){
// 	global $wpdb;

// 	$meta_key = wp_ulike_setting_repo::isDistinct( 'post' ) ? 'count_distinct_' . $status : 'count_total_' . $status;

// 	return $wpdb->get_var(
// 		$wpdb->prepare(
// 			"SELECT SUM(m.meta_value) FROM {$wpdb->prefix}ulike_meta m INNER JOIN $wpdb->posts p ON m.item_id = p.ID AND p.post_author = %s WHERE m.meta_group = 'post' AND m.meta_key = %s",
// 			$user_id,
// 			$meta_key
// 		)
// 	);
// }
// @endif

if( ! function_exists( 'wp_ulike_get_user_access_capability' ) ){
	/**
	 * Check current user capabilities to access admin pages
	 *
	 * @param [type] $type
	 * @return void
	 */
	function wp_ulike_get_user_access_capability( $type ){
		$current_user  = wp_get_current_user();
		$allowed_roles = apply_filters( 'wp_ulike_display_capabilities', array('administrator'), $type );
		return ! empty( $allowed_roles ) && array_intersect( $allowed_roles, $current_user->roles ) ? key($current_user->allcaps) : 'manage_options';
	}
}

if( ! function_exists( 'wp_ulike_get_likers_template' ) ){
	/**
	 * Get likers box template info.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @param integer $item_ID
	 * @param string $setting_key
	 * @param array $args
	 * @return string
	 */
	function wp_ulike_get_likers_template( $table_name, $column_name, $item_ID, $setting_key, $args = array() ){

		$options  = wp_ulike_get_option( $setting_key );

		if( empty( $options ) || empty( $item_ID ) ){
			return;
		}

		//Main data
		$defaults = array(
			"counter"     => ! empty( $options['likers_count'] ) ? $options['likers_count'] : 10,
			"template"    => ! empty( $options['likers_template'] ) ? $options['likers_template'] : null,
			"avatar_size" => ! empty( $options['likers_gravatar_size'] ) ? $options['likers_gravatar_size'] : 64
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		// Get likers list
		$get_users  = wp_ulike_get_likers_list_per_post( $table_name, $column_name, $item_ID, $parsed_args['counter'] );
		// Bulk user list
		$users_list = '';

		if( ! empty( $get_users ) ) {

			// Get likers html template
 			$get_template   = ! empty( $parsed_args['template'] ) ?  $parsed_args['template'] : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>' ;
 			$inner_template = wp_ulike_get_template_between( $get_template, "%START_WHILE%", "%END_WHILE%" );

			foreach ( $get_users as $user ) {
				$user_info	= get_user_by( 'id', $user );
				// Check user existence
				if( ! $user_info ){
					continue;
				}
				$out_template 	= $inner_template;
				if ( $user_info ):
					if( strpos( $out_template, '%USER_AVATAR%' ) !== false ) {
						$avatar_size 	= $parsed_args['avatar_size'];
						$USER_AVATAR 	= get_avatar( $user_info->user_email, $avatar_size, '' , 'avatar' );
						$out_template 	= str_replace( "%USER_AVATAR%", $USER_AVATAR, $out_template );
					}
					if( strpos( $out_template, '%USER_NAME%' ) !== false) {
						$USER_NAME 		= $user_info->display_name;
						$out_template 	= str_replace( "%USER_NAME%", $USER_NAME, $out_template );
					}
					if( strpos( $out_template, '%UM_PROFILE_URL%' ) !== false && function_exists('um_fetch_user') ) {
						global $ultimatemember;
						um_fetch_user( $user_info->ID );
						$UM_PROFILE_URL = um_user_profile_url();
						$out_template 	= str_replace( "%UM_PROFILE_URL%", $UM_PROFILE_URL, $out_template );
					}
					if( strpos( $out_template, '%BP_PROFILE_URL%' ) !== false && function_exists('bp_core_get_user_domain') ) {
						$BP_PROFILE_URL = bp_core_get_user_domain( $user_info->ID );
						$out_template 	= str_replace( "%BP_PROFILE_URL%", $BP_PROFILE_URL, $out_template );
					}
					$users_list .= $out_template;
				endif;
			}

			if( ! empty( $users_list ) ) {
				return wp_ulike_put_template_between( $get_template, $users_list, "%START_WHILE%", "%END_WHILE%" );
			}
		}

		return '';
	}
}

if( ! function_exists( 'wp_ulike_display_button' ) ){
	/**
	 * Convert numbers of Likes with string (kilobyte) format
	 *
	 * @author       	Alimir
	 * @param           Array  		$parsed_args
	 * @param           Integer 	$deprecated_value
	 * @since           3.4
	 * @return          String
	 */
	function wp_ulike_display_button( array $args, $deprecated_value = null ){
		$template = new wp_ulike_cta_template( $args );

		if( ! wp_ulike_is_true( $args['only_logged_in_users'] ) || is_user_logged_in() ) {
			// Return ulike template
			return $template->display();
		} else {
			if( $args['logged_out_action'] === 'alert' ){
				return apply_filters( 'wp_ulike_login_alert_template', wp_ulike_setting_repo::getRequireLoginTemplate( $args['options_group'] ) );
			} else {
				return $template->get_template( 0 );
			}
		}
	}
}

if( ! function_exists( 'wp_ulike_get_custom_style' ) ){
	/**
	 * Get custom style setting from customize options
	 *
	 * @author       	Alimir
	 * @since           1.3
	 * @return          Void (Print new CSS styles)
	 */
	function wp_ulike_get_custom_style( $return_style = null ){

		// Display deprecated styles
		if( wp_ulike_get_setting( 'wp_ulike_customize', 'custom_style' ) && wp_ulike_get_option( 'enable_deprecated_options' ) ) {
			//get custom options
			$customstyle   = get_option( 'wp_ulike_customize' );
			$btn_style     = '';
			$counter_style = '';
			$before_style  = '';

			// Button Style
			if( isset( $customstyle['btn_bg'] ) && ! empty( $customstyle['btn_bg'] ) ) {
				$btn_style .= "background-color:".$customstyle['btn_bg'].";";
			}
			if( isset( $customstyle['btn_border'] ) && ! empty( $customstyle['btn_border'] ) ) {
				$btn_style .= "box-shadow: 0 0 0 1px ".$customstyle['btn_border']." inset; ";
			}
			if( isset( $customstyle['btn_color'] ) && ! empty( $customstyle['btn_color'] ) ) {
				$btn_style .= "color:".$customstyle['btn_color'].";";
			}

			if( $btn_style != '' ){
				$return_style .= '.wpulike-default .wp_ulike_btn, .wpulike-default .wp_ulike_btn:hover, #bbpress-forums .wpulike-default .wp_ulike_btn, #bbpress-forums .wpulike-default .wp_ulike_btn:hover{'.$btn_style.'}.wpulike-heart .wp_ulike_general_class{'.$btn_style.'}';
			}

			// Counter Style
			if( isset( $customstyle['counter_bg'] ) && ! empty( $customstyle['counter_bg'] ) ) {
				$counter_style .= "background-color:".$customstyle['counter_bg'].";";
			}
			if( isset( $customstyle['counter_border'] ) && ! empty( $customstyle['counter_border'] ) ) {
				$counter_style .= "box-shadow: 0 0 0 1px ".$customstyle['counter_border']." inset; ";
				$before_style  = "background-color:".$customstyle['counter_bg']."; border-color:transparent; border-bottom-color:".$customstyle['counter_border']."; border-left-color:".$customstyle['counter_border'].";";
			}
			if( isset( $customstyle['counter_color'] ) && ! empty( $customstyle['counter_color'] ) ) {
				$counter_style .= "color:".$customstyle['counter_color'].";";
			}

			if( $counter_style != '' ){
				$return_style .= '.wpulike-default .count-box,.wpulike-default .count-box{'.$counter_style.'}.wpulike-default .count-box:before{'.$before_style.'}';
			}
		}

		// Custom Spinner
		if( '' != ( $custom_spinner = wp_ulike_get_option( 'custom_spinner' ) ) ) {
			$return_style .= '.wpulike .wp_ulike_is_loading .wp_ulike_btn, #buddypress .activity-content .wpulike .wp_ulike_is_loading .wp_ulike_btn, #bbpress-forums .bbp-reply-content .wpulike .wp_ulike_is_loading .wp_ulike_btn {background-image: url('.$custom_spinner.') !important;}';
		}

		// Custom Styles
		if( '' != ( $custom_css = wp_ulike_get_option( 'custom_css' ) ) ) {
			$return_style .= $custom_css;
		}

		return apply_filters( 'wp_ulike_custom_css', $return_style );
	}

}

if( ! function_exists( 'wp_ulike_format_number' ) ){
	/**
	 * Counter value formatter
	 *
	 * @param integer $num
	 * @param string $status
	 * @return void
	 */
	function wp_ulike_format_number( $number, $status = 'like' ){
		// Maybe filter value
		$value = wp_ulike_setting_repo::maybeFilterCounterValue( $number, $status );
		return apply_filters( 'wp_ulike_format_number', $value, $number, $status );
	}
}

if( ! function_exists('wp_ulike_get_button_text') ){
	/**
	 * Get button text by option name
	 *
	 * @param string $option_name
	 * @return string
	 */
	function wp_ulike_get_button_text( $option_name, $setting_key = 'posts_group' ){
		$value = wp_ulike_get_option( $setting_key . '|text_group|' . $option_name );
		return apply_filters( 'wp_ulike_button_text', $value, $option_name, $setting_key );
	}
}

if( ! function_exists('wp_ulike_maybe_convert_status') ){
	/**
	 * Get template status
	 *
	 * @param string $status
	 * @param string $type
	 * @return string
	 */
	function wp_ulike_maybe_convert_status( $status, $type ){
		if( $type === 'up' ){
			return in_array( $status, array( 'like', 'unlike' ) ) ? $status : 'like';
		} else {
			return in_array( $status, array( 'dislike', 'undislike' ) ) ? $status : 'dislike';
		}
	}
}
