<?php
/**
 * Admin Hooks
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Add WP ULike CopyRight in footer
 *
 * @author       	Alimir
 * @param           string $text
 * @since           2.0
 * @return			string
 */
function wp_ulike_copyright( $text ) {
	if( isset($_GET["page"]) && stripos( $_GET["page"], "wp-ulike") !== false ) {
		return sprintf(
			__( ' Thank you for choosing <a href="%s" title="Wordpress ULike" target="_blank">WP ULike</a>.', WP_ULIKE_SLUG ),
			WP_ULIKE_PLUGIN_URI . '?utm_source=footer-link&utm_campaign=plugin-uri&utm_medium=wp-dash'
		);
	}

	return $text;
}
add_filter( 'admin_footer_text', 'wp_ulike_copyright');


/**
 * Filters a screen option value before it is set.
 *
 * @param string $status
 * @param string $option The option name.
 * @param string $value The number of rows to use.
 * @return string
 */
function wp_ulike_logs_per_page_set_option( $status, $option, $value ) {

	if ( 'wp_ulike_logs_per_page' == $option ) {
		return $value;
	}

	return $status;
}
add_filter( 'set-screen-option', 'wp_ulike_logs_per_page_set_option', 10, 3 );

 /**
  * The Filter is used at the very end of the get_avatar() function
  *
  * @param string $avatar Image tag for the user's avatar.
  * @return string $avatar
  */
function wp_ulike_remove_photo_class($avatar) {
	return str_replace(' photo', ' gravatar', $avatar);
}
add_filter('get_avatar', 'wp_ulike_remove_photo_class');


/**
 * Set the admin login time.
 *
 * @author       	Alimir
 * @since           2.4.2
 * @return			Void
 */
function wp_ulike_set_lastvisit() {
	if ( ! is_super_admin() ) {
		return;
	}
	update_option( 'wpulike_lastvisit', current_time( 'mysql' ) );
}
add_action('wp_logout', 'wp_ulike_set_lastvisit');

/**
 *  Undocumented function
 *
 * @since 3.6.0
 * @param integer $count
 * @return integer $count
 */
function wp_ulike_update_menu_badge_count( $count ) {
	if( 0 !== $count_new_likes = wp_ulike_get_number_of_new_likes() ){
		$count += $count_new_likes;
	}
	return $count;
}
add_filter( 'wp_ulike_menu_badge_count', 'wp_ulike_update_menu_badge_count' );


/**
 * Update the admin sub menu title
 *
 * @since 3.6.0
 * @param string $title
 * @param string $menu_slug
 * @return string $title
 */
function wp_ulike_update_admin_sub_menu_title( $title, $menu_slug ) {
	if( ( 0 !== $count_new_likes = wp_ulike_get_number_of_new_likes() ) && $menu_slug === 'wp-ulike-statistics' ){
		$title .=  wp_ulike_badge_count_format( $count_new_likes );
	}
	return $title;
}
add_filter( 'wp_ulike_admin_sub_menu_title', 'wp_ulike_update_admin_sub_menu_title', 10, 2 );

/**
 * Admin notice controller
 *
 * @return void
 */
function wp_ulike_notice_manager(){

	// Display notices only for admin users
	if( !current_user_can( 'manage_options' ) ){
		return;
	}

	$count_logs  = wp_ulike_count_all_logs();
	$screen      = get_current_screen();
	$notice_list = [];

	if( $count_logs > 1000 ){
		$notice_list[ 'wp_ulike_leave_a_review' ] = new wp_ulike_notices([
			'id'          => 'wp_ulike_leave_a_review',
			'title'       => __( 'Wow! You\'ve earned over a thousand likes', WP_ULIKE_SLUG ) . ' :)',
			'description' => __( "It's great to see that you've been using the WP ULike plugin. Hopefully you're happy with it!&nbsp; If so, would you consider leaving a positive review? It really helps to support the plugin and helps others to discover it too!" , WP_ULIKE_SLUG ),
			'skin'        => 'info',
			'has_close'   => true,
			'buttons'     => array(
				array(
					'label'      => __( "Sure, I'd love to!", WP_ULIKE_SLUG ),
					'link'       => 'https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5'
				),
				array(
					'label'      => __('Not Now', WP_ULIKE_SLUG),
					'type'       => 'skip',
					'color_name' => 'info',
					'expiration' => WEEK_IN_SECONDS * 2
				),
				array(
					'label'      => __('No thanks and never ask me again', WP_ULIKE_SLUG),
					'type'       => 'skip',
					'color_name' => 'error',
					'expiration' => YEAR_IN_SECONDS * 10
				)
			),
			'image'     => array(
				'width' => '150',
				'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/rating.svg'
			)
		]);
	}

	if( ! defined( 'WP_ULIKE_PRO_VERSION' ) && strpos( $screen->base, WP_ULIKE_SLUG ) !== false ){
		$notice_list[ 'wp_ulike_pro_user_profiles_banner' ] = new wp_ulike_notices([
			'id'          => 'wp_ulike_pro_user_profiles_banner',
			'title'       => __( 'How to Create Ultimate User Profiles with WP ULike?', WP_ULIKE_SLUG ),
			'description' => __( "The simplest way to create your own WordPress user profile page is by using the WP ULike Profile builder. This way, you can create professional profiles and display it on the front-end of your website without the need for coding knowledge or the use of advanced functions." , WP_ULIKE_SLUG ),
			'skin'        => 'default',
			'has_close'   => true,
			'buttons'     => array(
				array(
					'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
					'link'       => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ultimate-profile-builder/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
				),
				array(
					'label'      => __('No thanks and never ask me again', WP_ULIKE_SLUG),
					'type'       => 'skip',
					'color_name' => 'error',
					'expiration' => YEAR_IN_SECONDS * 10
				)
			),
			'image'     => array(
				'width' => '140',
				'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/hero.svg'
			)
		]);
		$notice_list[ 'wp_ulike_seo_tools_banner' ] = new wp_ulike_notices([
			'id'          => 'wp_ulike_seo_tools_banner',
			'title'       => __( 'Boost Your SEO by Using Schema Rich Snippets', WP_ULIKE_SLUG ),
			'description' => __( "WP ULike Pro in its latest update evolved to an innovative and powerful SEO Plugin which can manage +13 types of Schema Markups to make a better connection between your webpages and search engines. Now you can talk in search engine language and tell them which type of content you are promoting." , WP_ULIKE_SLUG ),
			'skin'        => 'default',
			'has_close'   => true,
			'buttons'     => array(
				array(
					'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
					'link'       => WP_ULIKE_PLUGIN_URI . '?utm_source=seo-tools-banner&utm_campaign=gopro&utm_medium=wp-dash'
				),
				array(
					'label'      => __('No thanks and never ask me again', WP_ULIKE_SLUG),
					'type'       => 'skip',
					'color_name' => 'error',
					'expiration' => YEAR_IN_SECONDS * 10
				)
			),
			'image'     => array(
				'width' => '140',
				'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/seo.svg'
			)
		]);
	}


    $notice_list = apply_filters( 'wp_ulike_admin_notices_instances', $notice_list );

    foreach ( $notice_list as $notice ) {
        if( $notice instanceof wp_ulike_notices ){
            $notice->render();
        }
    }
}
add_action( 'admin_notices', 'wp_ulike_notice_manager' );

/**
 * This makes a new admin menu page to introduce premium version
 *
 * @param array $submenus
 * @return array
 */
function wp_ulike_go_pro_admin_menu( $submenus ){
	if( is_array( $submenus ) && ! defined( 'WP_ULIKE_PRO_VERSION' ) ){
		$submenus['go_pro'] = array(
			'title'       =>  sprintf( '<span class="wp-ulike-gopro-menu-link"><strong>%s</strong></span>', __( 'Go Pro', WP_ULIKE_SLUG )),
			'parent_slug' => 'wp-ulike-settings',
 			'capability'  => 'manage_options',
			'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/go-pro.php',
			'menu_slug'   => 'wp-ulike-go-pro',
			'load_screen' => false
		);
	}

	return $submenus;
}
add_filter( 'wp_ulike_admin_pages', 'wp_ulike_go_pro_admin_menu', 1, 10 );

/**
 * Disable admin notices
 * @param array $notice_list
 * @return array|null
 */
function wp_ulike_hide_admin_notifications( $notice_list ){
	$screen = get_current_screen();
	$hide_admin_notice = wp_ulike_get_option( 'disable_admin_notice', false );
	return wp_ulike_is_true( $hide_admin_notice ) && strpos( $screen->base, WP_ULIKE_SLUG ) === false ? array() : $notice_list;
}
add_filter( 'wp_ulike_admin_notices_instances', 'wp_ulike_hide_admin_notifications', 1, 20 );


/**
 * Upgarde old option values
 *
 * @return void
 */
function wp_ulike_upgrade_deprecated_options_value(){

	$is_deprecated_enabled     = wp_ulike_get_option( 'enable_deprecated_options' );
	$deprecated_options_status = get_option( 'wp_ulike_deprecated_options_status', false );

	if( ! wp_ulike_is_true( $is_deprecated_enabled ) || $deprecated_options_status ){
		return;
	}

	$get_general_options    = get_option( 'wp_ulike_general', array() );
	$get_posts_options      = get_option( 'wp_ulike_posts', array() );
	$get_comments_options   = get_option( 'wp_ulike_comments', array() );
	$get_buddypress_options = get_option( 'wp_ulike_buddypress', array() );
	$get_bbpress_options    = get_option( 'wp_ulike_bbpress', array() );
	$get_customize_options  = get_option( 'wp_ulike_customize', array() );

	$final_options_stack    = array();

	// Update general options
	if( !empty( $get_posts_options ) ){
		$final_options_stack = array (
			'enable_kilobyte_format'    => !empty($get_general_options['format_number']) ? $get_general_options['format_number'] : false,
			'enable_toast_notice'       => !empty($get_general_options['notifications']) ? $get_general_options['notifications'] : true,
			'enable_anonymise_ip'       => !empty($get_general_options['anonymise']) ? $get_general_options['anonymise'] : false,
			'disable_admin_notice'      => !empty($get_general_options['hide_admin_notice']) ? $get_general_options['hide_admin_notice'] : true,
			'enable_meta_values'        => !empty($get_general_options['enable_meta_values']) ? $get_general_options['enable_meta_values'] : false,
			'already_registered_notice' => !empty($get_general_options['permission_text']) ? $get_general_options['permission_text'] : 'You have already registered a vote.',
			'login_required_notice'     => !empty($get_general_options['login_text']) ? $get_general_options['permission_text'] : 'You Should Login To Submit Your Like',
			'like_notice'               => !empty($get_general_options['like_notice']) ? $get_general_options['like_notice'] : 'Thanks! You liked This.',
			'unlike_notice'             => !empty($get_general_options['unlike_notice']) ? $get_general_options['unlike_notice'] : 'Sorry! You unliked this.',
			'dislike_notice'            => !empty($get_general_options['dislike_notice']) ? $get_general_options['dislike_notice'] : 'Sorry! You disliked this.',
			'undislike_notice'          => !empty($get_general_options['undislike_notice']) ? $get_general_options['undislike_notice'] : 'Thanks! You undisliked This.',
			'custom_css'                => !empty($get_customize_options['custom_css']) ? $get_customize_options['custom_css'] : '',
			'enable_deprecated_options' => true,
		);
	}

	// Update posts options
	if( !empty( $get_posts_options ) ){
		$final_options_stack['posts_group'] = array (
			'template'    => !empty($get_posts_options['theme']) ? $get_posts_options['theme'] : 'wpulike-default',
			'button_type' => !empty($get_general_options['button_type']) ? $get_general_options['button_type'] : 'image',
			'text_group'  => array (
				'like'      => !empty($get_general_options['button_text']) ? $get_general_options['button_text'] : 'Like',
				'unlike'    => !empty($get_general_options['button_text_u']) ? $get_general_options['button_text_u'] : 'Liked',
				'dislike'   => !empty($get_general_options['dislike_text']) ? $get_general_options['dislike_text'] : 'Dislike',
				'undislike' => !empty($get_general_options['undislike_text']) ? $get_general_options['undislike_text'] : 'Disliked'
			),
			'image_group' => array (
				'like'      => !empty($get_general_options['button_url']) ? $get_general_options['button_url'] : '',
				'unlike'    => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
				'dislike'   => !empty($get_general_options['button_texbutton_urlt']) ? $get_general_options['button_url'] : '',
				'undislike' => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
			),
			'enable_auto_display'         => !empty($get_posts_options['auto_display']) ? $get_posts_options['auto_display'] : false,
			'auto_display_position'       => !empty($get_posts_options['auto_display_position']) ? $get_posts_options['auto_display_position'] : 'bottom',
			'auto_display_filter'         => !empty($get_posts_options['auto_display_filter']) ? wp_ulike_convert_old_options_array( $get_posts_options['auto_display_filter'] ) : '',
			'logging_method'              => !empty($get_posts_options['logging_method']) ? $get_posts_options['logging_method'] : 'by_username',
			'enable_only_logged_in_users' => !empty($get_posts_options['only_registered_users']) ? $get_posts_options['only_registered_users'] : false,
			'logged_out_display_type'     => !empty($get_general_options['login_type']) ? $get_general_options['login_type'] : 'button',
			'enable_likers_box'           => !empty($get_posts_options['users_liked_box']) ? $get_posts_options['users_liked_box'] : false,
			'disable_likers_pophover'     => !empty($get_posts_options['disable_likers_pophover']) ? $get_posts_options['disable_likers_pophover'] : false,
			'likers_gravatar_size'        => !empty($get_posts_options['users_liked_box_avatar_size']) ? $get_posts_options['users_liked_box_avatar_size'] : 64,
			'likers_count'                => !empty($get_posts_options['number_of_users']) ? $get_posts_options['number_of_users'] : 10,
			'likers_template'             => !empty($get_posts_options['likers_template']) ? $get_posts_options['likers_template'] : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>',
		);
	}
	// Update comments options
	if( !empty( $get_comments_options ) ){
		$final_options_stack['comments_group'] = array (
			'template'    => !empty($get_comments_options['theme']) ? $get_comments_options['theme'] : 'wpulike-default',
			'button_type' => !empty($get_general_options['button_type']) ? $get_general_options['button_type'] : 'image',
			'text_group'  => array (
				'like'      => !empty($get_general_options['button_text']) ? $get_general_options['button_text'] : 'Like',
				'unlike'    => !empty($get_general_options['button_text_u']) ? $get_general_options['button_text_u'] : 'Liked',
				'dislike'   => !empty($get_general_options['dislike_text']) ? $get_general_options['dislike_text'] : 'Dislike',
				'undislike' => !empty($get_general_options['undislike_text']) ? $get_general_options['undislike_text'] : 'Disliked'
			),
			'image_group' => array (
				'like'      => !empty($get_general_options['button_url']) ? $get_general_options['button_url'] : '',
				'unlike'    => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
				'dislike'   => !empty($get_general_options['button_texbutton_urlt']) ? $get_general_options['button_url'] : '',
				'undislike' => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
			),
			'enable_auto_display'         => !empty($get_comments_options['auto_display']) ? $get_comments_options['auto_display'] : false,
			'auto_display_position'       => !empty($get_comments_options['auto_display_position']) ? $get_comments_options['auto_display_position'] : 'bottom',
			'logging_method'              => !empty($get_comments_options['logging_method']) ? $get_comments_options['logging_method'] : 'by_username',
			'enable_only_logged_in_users' => !empty($get_comments_options['only_registered_users']) ? $get_comments_options['only_registered_users'] : false,
			'logged_out_display_type'     => !empty($get_general_options['login_type']) ? $get_general_options['login_type'] : 'button',
			'enable_likers_box'           => !empty($get_comments_options['users_liked_box']) ? $get_comments_options['users_liked_box'] : false,
			'disable_likers_pophover'     => !empty($get_comments_options['disable_likers_pophover']) ? $get_comments_options['disable_likers_pophover'] : false,
			'likers_gravatar_size'        => !empty($get_comments_options['users_liked_box_avatar_size']) ? $get_comments_options['users_liked_box_avatar_size'] : 64,
			'likers_count'                => !empty($get_comments_options['number_of_users']) ? $get_comments_options['number_of_users'] : 10,
			'likers_template'             => !empty($get_comments_options['likers_template']) ? $get_comments_options['likers_template'] : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>',
		);
	}
	// Update buddyPress options
	if( !empty( $get_buddypress_options ) ){
		$final_options_stack['buddypress_group'] = array (
			'template'    => !empty($get_buddypress_options['theme']) ? $get_buddypress_options['theme'] : 'wpulike-default',
			'button_type' => !empty($get_general_options['button_type']) ? $get_general_options['button_type'] : 'image',
			'text_group'  => array (
				'like'      => !empty($get_general_options['button_text']) ? $get_general_options['button_text'] : 'Like',
				'unlike'    => !empty($get_general_options['button_text_u']) ? $get_general_options['button_text_u'] : 'Liked',
				'dislike'   => !empty($get_general_options['dislike_text']) ? $get_general_options['dislike_text'] : 'Dislike',
				'undislike' => !empty($get_general_options['undislike_text']) ? $get_general_options['undislike_text'] : 'Disliked'
			),
			'image_group' => array (
				'like'      => !empty($get_general_options['button_url']) ? $get_general_options['button_url'] : '',
				'unlike'    => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
				'dislike'   => !empty($get_general_options['button_texbutton_urlt']) ? $get_general_options['button_url'] : '',
				'undislike' => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
			),
			'enable_auto_display'            => !empty($get_buddypress_options['auto_display']) ? $get_buddypress_options['auto_display'] : false,
			'auto_display_position'          => !empty($get_buddypress_options['auto_display_position']) ? $get_buddypress_options['auto_display_position'] : 'content',
			'logging_method'                 => !empty($get_buddypress_options['logging_method']) ? $get_buddypress_options['logging_method'] : 'by_username',
			'enable_only_logged_in_users'    => !empty($get_buddypress_options['only_registered_users']) ? $get_buddypress_options['only_registered_users'] : false,
			'logged_out_display_type'        => !empty($get_general_options['login_type']) ? $get_general_options['login_type'] : 'button',
			'enable_likers_box'              => !empty($get_buddypress_options['users_liked_box']) ? $get_buddypress_options['users_liked_box'] : false,
			'disable_likers_pophover'        => !empty($get_buddypress_options['disable_likers_pophover']) ? $get_buddypress_options['disable_likers_pophover'] : false,
			'likers_gravatar_size'           => !empty($get_buddypress_options['users_liked_box_avatar_size']) ? $get_buddypress_options['users_liked_box_avatar_size'] : 64,
			'likers_count'                   => !empty($get_buddypress_options['number_of_users']) ? $get_buddypress_options['number_of_users'] : 10,
			'likers_template'                => !empty($get_buddypress_options['likers_template']) ? $get_buddypress_options['likers_template'] : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>',
			'enable_comments'                => !empty($get_buddypress_options['activity_comment']) ? $get_buddypress_options['activity_comment'] : false,
			'enable_add_bp_activity'         => !empty($get_buddypress_options['new_likes_activity']) ? $get_buddypress_options['new_likes_activity'] : false,
			'posts_notification_template'    => !empty($get_buddypress_options['bp_post_activity_add_header']) ? $get_buddypress_options['bp_post_activity_add_header'] : '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
			'comments_notification_template' => !empty($get_buddypress_options['bp_comment_activity_add_header']) ? $get_buddypress_options['bp_comment_activity_add_header'] : '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
			'enable_add_notification'        => !empty($get_buddypress_options['custom_notification']) ? $get_buddypress_options['custom_notification'] : false
		);
	}
	// Update bbPress options
	if( !empty( $get_bbpress_options ) ){
		$final_options_stack['bbpress_group'] = array (
			'template'    => !empty($get_bbpress_options['theme']) ? $get_bbpress_options['theme'] : 'wpulike-default',
			'button_type' => !empty($get_general_options['button_type']) ? $get_general_options['button_type'] : 'image',
			'text_group'  => array (
				'like'      => !empty($get_general_options['button_text']) ? $get_general_options['button_text'] : 'Like',
				'unlike'    => !empty($get_general_options['button_text_u']) ? $get_general_options['button_text_u'] : 'Liked',
				'dislike'   => !empty($get_general_options['dislike_text']) ? $get_general_options['dislike_text'] : 'Dislike',
				'undislike' => !empty($get_general_options['undislike_text']) ? $get_general_options['undislike_text'] : 'Disliked'
			),
			'image_group' => array (
				'like'      => !empty($get_general_options['button_url']) ? $get_general_options['button_url'] : '',
				'unlike'    => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
				'dislike'   => !empty($get_general_options['button_texbutton_urlt']) ? $get_general_options['button_url'] : '',
				'undislike' => !empty($get_general_options['button_url_u']) ? $get_general_options['button_url_u'] : '',
			),
			'enable_auto_display'         => !empty($get_bbpress_options['auto_display']) ? $get_bbpress_options['auto_display'] : false,
			'auto_display_position'       => !empty($get_bbpress_options['auto_display_position']) ? $get_bbpress_options['auto_display_position'] : 'bottom',
			'logging_method'              => !empty($get_bbpress_options['logging_method']) ? $get_bbpress_options['logging_method'] : 'by_username',
			'enable_only_logged_in_users' => !empty($get_bbpress_options['only_registered_users']) ? $get_bbpress_options['only_registered_users'] : false,
			'logged_out_display_type'     => !empty($get_general_options['login_type']) ? $get_general_options['login_type'] : 'button',
			'enable_likers_box'           => !empty($get_bbpress_options['users_liked_box']) ? $get_bbpress_options['users_liked_box'] : false,
			'disable_likers_pophover'     => !empty($get_bbpress_options['disable_likers_pophover']) ? $get_bbpress_options['disable_likers_pophover'] : false,
			'likers_gravatar_size'        => !empty($get_bbpress_options['users_liked_box_avatar_size']) ? $get_bbpress_options['users_liked_box_avatar_size'] : 64,
			'likers_count'                => !empty($get_bbpress_options['number_of_users']) ? $get_bbpress_options['number_of_users'] : 10,
			'likers_template'             => !empty($get_bbpress_options['likers_template']) ? $get_bbpress_options['likers_template'] : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>'
		);
	}

	// Update flag option
	update_option( 'wp_ulike_deprecated_options_status', true );
	// Update option values
	update_option( 'wp_ulike_settings', $final_options_stack  );
}
add_filter( 'admin_init', 'wp_ulike_upgrade_deprecated_options_value' );

/**
 * Admin init controller
 *
 * @return void
 */
function wp_ulike_admin_init_controller() {
	global $pagenow;

	$disable_panel_files = wp_ulike_get_option( 'disable_panel_files', true );

	if( ! wp_ulike_is_true( $disable_panel_files ) ){
		return;
	}

	$has_ulike  = isset( $_GET['page'] ) && strpos( $_GET['page'], WP_ULIKE_SLUG ) !== false;
	$has_assets = defined( 'WP_ULIKE_PRO_DOMAIN' ) && in_array( $pagenow, array( 'post.php', 'post-new.php' ) );

	if( ! $has_ulike &&  ! $has_assets ) {
	  remove_action( 'admin_enqueue_scripts', 'CSF::add_admin_enqueue_scripts', 20 );
	  remove_action( 'admin_footer', 'csf_set_icons' );
	  remove_action( 'customize_controls_print_footer_scripts', 'csf_set_icons' );
	}
}
// add_action( 'admin_init', 'wp_ulike_admin_init_controller' );