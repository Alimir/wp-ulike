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
		$notice_list[ 'wp_ulike_go_pro' ] = new wp_ulike_notices([
			'id'          => 'wp_ulike_go_pro',
			'title'       => __( 'WP Ulike Pro is Ready :))', WP_ULIKE_SLUG ),
			   'description' => __( "Finally, after a long time, the Premium version of the WP Ulike plugin has been released with some new features such as support for Dislike button, Professional stats, Elementor (Page Builder) Widgets, and some new templates. We intend to add more features to this extension every day and provide a full support for our users." , WP_ULIKE_SLUG ),
			'skin'        => 'default',
			'wrapper_extra_styles' => [
				'background-image'  => 'url(' . WP_ULIKE_ASSETS_URL . '/img/svg/banner-pro.svg)',
				'background-color'  => '#e1f5fe',
				'background-size'   => 'contain',
				'background-repeat' => 'no-repeat',
				'padding'           => '80px 180px 80px 340px',
			],
			'has_close'   => true,
			'buttons'     => array(
				array(
					'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
					'link'       => WP_ULIKE_PLUGIN_URI . '?utm_source=banner&utm_campaign=gopro&utm_medium=wp-dash'
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

function wp_ulike_hide_admin_notifications( $notice_list ){
	$screen = get_current_screen();
	$hide_admin_notice = wp_ulike_get_setting( 'wp_ulike_general', 'hide_admin_notice', false );
	return wp_ulike_is_true( $hide_admin_notice ) && strpos( $screen->base, WP_ULIKE_SLUG ) === false ? array() : $notice_list;
}
add_filter( 'wp_ulike_admin_notices_instances', 'wp_ulike_hide_admin_notifications', 1, 20 );