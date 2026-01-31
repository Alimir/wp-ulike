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
	 * WordPress automatically caches get_option() per request
	 *
	 * @param string $option
	 * @param array|string $default
	 * @return array|string|null
	 */
	function wp_ulike_get_option( $option = '', $default = null ) {
		// WordPress automatically caches get_option() per request
		// No need for custom static caching - WordPress handles it
		$settings = get_option( 'wp_ulike_settings' );

		// Return all settings if no option specified
		// If settings don't exist (false) or are empty, return default
		if ( empty( $option ) ) {
			return ( $settings !== false && ! empty( $settings ) ) ? $settings : $default;
		}

		// Handle nested options with pipe separator (e.g., "posts_group|template")
		if ( strpos( $option, '|' ) && is_array( $settings ) ) {
			$option_path = explode( "|", $option );
			$value = $settings;

			foreach ( $option_path as $key ) {
				if ( isset( $value[ $key ] ) ) {
					$value = $value[ $key ];
				} else {
					return $default;
				}
			}

			return $value;
		}

		// Simple option lookup
		return isset( $settings[ $option ] ) ? $settings[ $option ] : $default;
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
		global $wpdb;
		$output = array();

		switch ( $type ) {
			case 'likeThisComment':
			case 'comment':
			case 'comments':
				$output = array(
					'table'                => 'ulike_comments',
					'column'               => 'comment_id',
					'related_table'        => 'comments',
					'related_table_prefix' => $wpdb->comments,
					'related_column'       => 'comment_ID'
				);
				break;

			case 'likeThisActivity':
			case 'buddypress':
			case 'activity':
			case 'activities':
				$output = array(
					'table'                => 'ulike_activities',
					'column'               => 'activity_id',
					'related_table'        => 'bp_activity',
					'related_table_prefix' => is_multisite() ? $wpdb->base_prefix . 'bp_activity' : $wpdb->prefix . 'bp_activity',
					'related_column'       => 'id'
				);
				break;

			case 'likeThisTopic':
			case 'bbpress':
			case 'topic':
			case 'topics':
				$output = array(
					'table'                => 'ulike_forums',
					'column'               => 'topic_id',
					'related_table'        => 'posts',
					'related_table_prefix' => $wpdb->posts,
					'related_column'       => 'ID'
				);
				break;

			default:
				$output = array(
					'table'                => 'ulike',
					'column'               => 'post_id',
					'related_table'        => 'posts',
					'related_table_prefix' => $wpdb->posts,
					'related_column'       => 'ID'
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
				'is_home'        => is_front_page() || is_home(),
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
					$post_types = wp_ulike_setting_repo::getPostTypesFilterList();
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

if( ! function_exists( 'wp_ulike_get_user_access_capability' ) ){
	/**
	 * Check current user capabilities to access admin pages
	 *
	 * @param array $type
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
			"counter"     => ! empty( $options['likers_count'] ) ? absint( $options['likers_count'] ) : 10,
			"template"    => ! empty( $options['likers_template'] ) ? wp_kses_post( $options['likers_template'] ) : null,
			"style"       => ! empty( $options['likers_style'] ) ? esc_attr( $options['likers_style'] ) : 'popover',
			"avatar_size" => ! empty( $options['likers_gravatar_size'] ) ? absint( $options['likers_gravatar_size'] ) : 64
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		// Get likers list
		$get_users  = wp_ulike_get_likers_list_per_post( $table_name, $column_name, $item_ID, NULL );
		// Bulk user list
		$users_list = '';

		// Create custom template
		$custom_template = apply_filters( 'wp_ulike_get_likers_template', false, $get_users, $item_ID, $parsed_args, $table_name, $column_name, $options );
		if( $custom_template !== false ){
			return wp_kses_post( $custom_template );
		}

		if( ! empty( $get_users ) ) {
			// Limit array content
			$get_users  = array_slice( $get_users, 0, $parsed_args['counter'] );

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
						$USER_NAME 		= esc_attr( $user_info->display_name );
						$out_template 	= str_replace( "%USER_NAME%", $USER_NAME, $out_template );
					}
					if( strpos( $out_template, '%UM_PROFILE_URL%' ) !== false && function_exists('um_fetch_user') ) {
						global $ultimatemember;
						um_fetch_user( $user_info->ID );
						$UM_PROFILE_URL = um_user_profile_url();
						$out_template 	= str_replace( "%UM_PROFILE_URL%", $UM_PROFILE_URL, $out_template );
					}
					if( strpos( $out_template, '%BP_PROFILE_URL%' ) !== false && function_exists('bp_members_get_user_url') ) {
						$BP_PROFILE_URL = bp_members_get_user_url( $user_info->ID );
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

if( ! function_exists( 'wp_ulike_get_customizer_css' ) ){
	/**
	 * Get CSS from the customizer CSS generator
	 *
	 * @return string Generated CSS from customizer
	 */
	function wp_ulike_get_customizer_css() {
		if ( ! class_exists( 'wp_ulike_css_generator' ) ) {
			return '';
		}

		$css_generator = new wp_ulike_css_generator();
		return $css_generator->generate_css();
	}
}

if( ! function_exists( 'wp_ulike_get_custom_style' ) ){
	/**
	 * Get custom css styles including customizer-generated CSS
	 *
	 * @return string Combined CSS styles
	 */
	function wp_ulike_get_custom_style(){

		$return_style = '';

		// Add customizer-generated CSS first (highest priority)
		$customizer_css = wp_ulike_get_customizer_css();
		if( ! empty( $customizer_css ) ) {
			$return_style .= $customizer_css;
		}

		// Display deprecated styles (for backward compatibility)
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
				$return_style .= '.wpulike-default button.wp_ulike_btn, .wpulike-default button.wp_ulike_btn:hover, #bbpress-forums .wpulike-default button.wp_ulike_btn, #bbpress-forums .wpulike-default button.wp_ulike_btn:hover{'.$btn_style.'}.wpulike-heart .wp_ulike_general_class{'.$btn_style.'}';
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
		} else {
			$customizer_options = get_option( 'wp_ulike_customize' );

			if( ! empty($customizer_options['button_align']) ){
				$return_style .= sprintf( '.wpulike{text-align:%1$s !important; justify-content: %1$s !important;}', $customizer_options['button_align'] );
			}
		}

		// Custom Spinner
		if( '' != ( $custom_spinner = wp_ulike_get_option( 'custom_spinner' ) ) ) {
			$return_style .= '.wpulike .wp_ulike_is_loading button.wp_ulike_btn, #buddypress .activity-content .wpulike .wp_ulike_is_loading button.wp_ulike_btn, #bbpress-forums .bbp-reply-content .wpulike .wp_ulike_is_loading button.wp_ulike_btn {background-image: url('.esc_url($custom_spinner).') !important;}';
		}

		// Custom Styles from settings (lowest priority)
		if( '' != ( $custom_css = wp_ulike_get_option( 'custom_css' ) ) ) {
			$return_style .= $custom_css;
		}

		return apply_filters( 'wp_ulike_custom_css', wp_strip_all_tags( $return_style ) );
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

if( ! function_exists('wp_ulike_html_entity_decode') ){
	/**
	 * Convert HTML entities to characters:
	 *
	 * @param string $value
	 * @return string
	 */
	function wp_ulike_html_entity_decode( $value ){
		return html_entity_decode( $value );
	}
}

if( ! function_exists('wp_ulike_is_wpml_active') ){
	/**
	 * Check if WPML is active
	 *
	 * @return bool|mixed
	 */
	function wp_ulike_is_wpml_active() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			global $sitepress;

			return $sitepress->get_setting( 'setup_complete' );
		}

		return false;
	}
}

if( ! function_exists('wp_ulike_get_the_id') ){
	/**
	 * get post id
	 *
	 * @return bool|mixed
	 */
	function wp_ulike_get_the_id( $post_id = '' ) {
		$post_id = empty( $post_id ) ? get_the_ID() : $post_id;

		// Check if WPML synchronization is active
		if ( wp_ulike_is_wpml_active() && wp_ulike_setting_repo::isWpmlSynchronizationOn() ) {
			$current_language = apply_filters( 'wpml_current_language', null );
			$post_type        = get_post_type( $post_id );
			$post_id          = apply_filters( 'wpml_object_id', $post_id, $post_type, false, $current_language );
		}

		// Return the filtered post ID
		return apply_filters( 'wp_ulike_get_the_id', $post_id );
	}
}

if( ! function_exists('wp_ulike_acquire_lock') ){
	/**
	 *  Use mutex lock to prevent race condition.
	 *
	 * @param string $item_type
	 * @param integer $item_id
	 * @return resource
	 */
    function wp_ulike_acquire_lock( $item_type, $item_id ) {
        $lock_file = wp_ulike_lock_file( $item_type, $item_id );
        $fp = fopen( $lock_file, 'w+' );

        if ( ! $fp || ! flock( $fp, LOCK_EX | LOCK_NB ) ) {
            if ($fp) {
                fclose($fp);
            }
            return false;
        }

        ftruncate( $fp, 0 );
        fwrite( $fp, microtime( true ) );

        return $fp;
    }
}

if( ! function_exists('wp_ulike_release_lock') ){
	/**
	 * release mutex
	 *
	 * @param resource $fp
	 * @param string $item_type
	 * @param integer $item_id
	 * @return boolean
	 */
    function wp_ulike_release_lock( $fp, $item_type, $item_id ) {
        if ( is_resource( $fp ) ) {
            fflush( $fp );
            flock( $fp, LOCK_UN );
            fclose( $fp );

            $lock_file = wp_ulike_lock_file( $item_type, $item_id );

            // Use WordPress core function for file deletion (available since WP 4.2.0)
            if ( function_exists( 'wp_delete_file' ) ) {
                wp_delete_file( $lock_file );
            } elseif ( file_exists( $lock_file ) ) {
                // Fallback for older WordPress versions (though plugin requires 6.0+)
                @unlink( $lock_file );
            }

            return true;
        }

        return false;
    }
}

if( ! function_exists('wp_ulike_lock_file') ){
	/**
	 * get lock file
	 *
	 * @param string $item_type
	 * @param integer $item_id
	 * @return string
	 */
	function wp_ulike_lock_file( $item_type, $item_id ) {
		return apply_filters( 'wp_ulike_lock_file', get_temp_dir() . 'wp-ulike-' . $item_type . '-' . $item_id . '.lock', $item_type, $item_id );
	}
}


if( ! function_exists('wp_ulike_kses') ){
	/**
	 * Filters text content and strips out disallowed HTML.
	 * Extends WordPress's safe CSS properties to support modern CSS like display: flex
	 *
	 * @param string $value
	 * @return string
	 */
	function wp_ulike_kses( $value ) {
		$allowedtags = array(
			'a' => array(
				'href'   => true,
				'rel'    => true,
				'rev'    => true,
				'name'   => true,
				'target' => true
			),
			'img'        => array(
				'alt'      => true,
				'align'    => true,
				'border'   => true,
				'height'   => true,
				'hspace'   => true,
				'loading'  => true,
				'longdesc' => true,
				'vspace'   => true,
				'src'      => true,
				'usemap'   => true,
				'width'    => true,
			),
			'span'       => array(
				'align' => true,
			),
			'div'  => array(
				'align' => true,
			),
			'u'      => array(),
			'p'      => array(),
			'b'      => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array()
		);

		$allowedtags = array_map( 'wp_ulike_global_attributes', $allowedtags );

		// Decode HTML entities (in case the input is encoded)
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		// Temporarily extend safe_style_css filter to allow modern CSS properties
		add_filter( 'safe_style_css', 'wp_ulike_extend_safe_css_properties', 10, 1 );

		$sanitized = wp_kses( $value, $allowedtags );

		// Remove filter to avoid affecting other parts of WordPress
		remove_filter( 'safe_style_css', 'wp_ulike_extend_safe_css_properties', 10 );

		return $sanitized;
	}
}

if( ! function_exists('wp_ulike_extend_safe_css_properties') ){
	/**
	 * Extend WordPress's safe CSS properties to include only missing essential properties
	 * WordPress already includes most CSS properties (flexbox, grid, box model, etc.)
	 * This adds only the critical missing ones, primarily 'display' for display: flex
	 *
	 * Performance: This is very safe and fast:
	 * - Filter add/remove is O(1) operation
	 * - Array merge is fast (tiny array)
	 * - Only runs during wp_kses() calls (already happening)
	 * - Filter is scoped to only this function call
	 *
	 * @param array $styles WordPress's default safe CSS properties
	 * @return array Extended list of safe CSS properties
	 */
	function wp_ulike_extend_safe_css_properties( $styles ) {
		// Only add properties that WordPress doesn't already include by default
		// The main one is 'display' which is critical for display: flex, display: grid, etc.
		$additional_styles = array(
			'display',           // Critical: needed for display: flex, display: grid, display: none, etc.
			'overflow-x',         // Useful: directional overflow (WordPress has 'overflow' but not directional)
			'overflow-y',         // Useful: directional overflow
			'text-shadow',       // Useful: text shadow effects
			'box-sizing',        // Useful: box-sizing: border-box
			'visibility',        // Useful: visibility: hidden/visible
		);

		return array_merge( $styles, $additional_styles );
	}
}


if( ! function_exists('wp_ulike_global_attributes') ){
	/**
	 * Helper function to add global attributes to a tag in the allowed HTML list.
	 *
	 * @param array $value An array of attributes.
	 * @return array The array of attributes with global attributes added.
	 */
	function wp_ulike_global_attributes( $value ) {
		$global_attributes = array(
			'aria-controls'    => true,
			'aria-current'     => true,
			'aria-describedby' => true,
			'aria-details'     => true,
			'aria-expanded'    => true,
			'aria-hidden'      => true,
			'aria-label'       => true,
			'aria-labelledby'  => true,
			'aria-live'        => true,
			'class'            => true,
			'data-*'           => true,
			'dir'              => true,
			'hidden'           => true,
			'id'               => true,
			'lang'             => true,
			'style'            => true,
			'title'            => true,
			'role'             => true,
			'xml:lang'         => true,
		);

		if ( true === $value ) {
			$value = array();
		}

		if ( is_array( $value ) ) {
			return array_merge( $value, $global_attributes );
		}

		return $value;
	}
}

if( ! function_exists('wp_ulike_put_contents') ){
	/**
	 * Creates and stores content in a file (#admin)
	 *
	 * @param  string $content    The content for writing in the file
	 * @param  string $file_location  The address that we plan to create the file in.
	 *
	 * @return boolean            Returns true if the file is created and updated successfully, false on failure
	 */
	function wp_ulike_put_contents( $content, $file_location = '', $chmode = 0644 ){

		if( empty( $file_location ) ){
			return false;
		}

		/**
		 * Initialize the WP_Filesystem
		 */
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ( ABSPATH.'/wp-admin/includes/file.php' );
			WP_Filesystem();
		}

		// Write the content, if possible
		if ( wp_mkdir_p( dirname( $file_location ) ) && ! $wp_filesystem->put_contents( $file_location, $content, $chmode ) ) {
			// If writing the content in the file was not successful
			return false;
		} else {
			return true;
		}

	}
}

if( ! function_exists('wp_ulike_put_contents_dir') ){
	/**
	 * Creates and stores content in a file (#admin)
	 *
	 * @param  string $content    The content for writing in the file
	 * @param  string $file_name  The name of the file that we plan to store the content in. Default value is 'customfile'
	 * @param  string $file_dir   The directory that we plan to store the file in. Default dir is wp-contents/uploads/{THEME_ID}
	 *
	 * @return boolean            Returns true if the file is created and updated successfully, false on failure
	 */
	function wp_ulike_put_contents_dir( $content, $file_name = '', $file_dir = null, $chmode = 0644 ){

		// Check if the fucntion for writing the files is enabled
		if( ! function_exists('wp_ulike_put_contents') ){
			return false;
		}

		if( is_null( $file_dir ) ){
			$file_dir  = WP_ULIKE_CUSTOM_DIR;
		}
		$file_dir = trailingslashit( $file_dir );


		if( empty( $file_name ) ){
			$file_name = 'customfile';
		}

		$file_location = $file_dir . $file_name;

		return wp_ulike_put_contents( $content, $file_location, $chmode );
	}
}