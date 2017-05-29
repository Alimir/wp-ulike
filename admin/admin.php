<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) exit;

	/*******************************************************
	  Widget
	*******************************************************/
	include( plugin_dir_path(__FILE__) . 'classes/class-widget.php');
	
	/**
	 * Register WP ULike Widgets
	 *
	 * @author       	Alimir
	 * @since           1.2 
	 * @updated         2.0	  
	 * @return			Void
	 */
	function wp_ulike_load_widget() {
		register_widget( 'wp_ulike_widget' );
	}
	add_action( 'widgets_init', 'wp_ulike_load_widget' );

	/*******************************************************
	  WP ULike CopyRight
	*******************************************************/
	//check for wp ulike page
	if(isset($_GET["page"]) && stripos($_GET["page"], "wp-ulike") !== false)
	add_filter( 'admin_footer_text', 'wp_ulike_copyright');
	/**
	 * Add WP ULike CopyRight in footer
	 *
	 * @author       	Alimir	 	
	 * @param           String $content	 
	 * @since           2.0
	 * @return			String
	 */		
	function wp_ulike_copyright( $text ) {
		return sprintf( __( ' Thank you for choosing <a href="%s" title="Wordpress ULike" target="_blank">WP ULike</a>. Created by <a href="%s" title="Wordpress ULike" target="_blank">Ali Mirzaei</a>' ), 'http://wordpress.org/plugins/wp-ulike/', 'https://ir.linkedin.com/in/alimirir' );
	}

	/*******************************************************
	  Plugin Dashboard Menu Settings
	*******************************************************/

	//include about menu functions
	include( plugin_dir_path(__FILE__) . 'about.php');

	//include logs menu functions
	include( plugin_dir_path(__FILE__) . 'logs.php');

	//include statistics menu functions
	include( plugin_dir_path(__FILE__) . 'stats.php');

	/**
	 * Start Setting Class Options
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.0
	 * @updated         2.4.2
	 * @return			String
	 */
	 
	//include setting class
	include( plugin_dir_path(__FILE__) . 'classes/class-settings.php' );
	//include setting templates
	include( plugin_dir_path(__FILE__) . 'classes/tmp/settings.php' );		

	//activate general setting panel
	$wp_ulike_setting = wp_ulike_create_settings_page(
	  'wp-ulike-settings',
	  __( 'WP ULike Settings', WP_ULIKE_SLUG ),
	  array(
		'parent'   => false,
		'title'    =>  __( 'WP ULike', WP_ULIKE_SLUG ),
		'position' =>  313,
		'icon_url' => 'dashicons-smiley'
	  ),
	  array(
		'wp_ulike_general' 	=> $wp_ulike_general
	  ),
	  array(
		'tabs'        		=> true,
		'updated'     		=> __('Settings saved.',WP_ULIKE_SLUG)
	  )
	);
	
	//activate other settings panels
	$wp_ulike_setting->apply_settings( array(
	  'wp_ulike_posts' 		=> $wp_ulike_posts,
	  'wp_ulike_comments' 	=> $wp_ulike_comments,
	  'wp_ulike_buddypress' => $wp_ulike_buddypress,
	  'wp_ulike_bbpress' 	=> $wp_ulike_bbpress,
	  'wp_ulike_customize' 	=> $wp_ulike_customize
	) );

	/**
	 * Delete all the users likes logs by ajax process. 
	 *
	 * @author       	Alimir	 	
	 * @since           2.2
	 * @return			Void
	 */	
	function wp_ulike_delete_all_logs() {
		global $wpdb;
		$get_action = $_POST['action'];
		//$wpdb->hide_errors();
		
		if($get_action == 'wp_ulike_posts_delete_logs'){
			$logs_table = $wpdb->prefix."ulike";
		} else if($get_action == 'wp_ulike_comments_delete_logs'){
			$logs_table = $wpdb->prefix."ulike_comments";
		} else if($get_action == 'wp_ulike_buddypress_delete_logs'){
			$logs_table = $wpdb->prefix."ulike_activities";
		} else if($get_action == 'wp_ulike_bbpress_delete_logs'){
			$logs_table = $wpdb->prefix."ulike_forums";
		}		
		
		if ($wpdb->query("TRUNCATE TABLE $logs_table") === FALSE) {
			wp_send_json_error( __( 'Failed! An Error Has Occurred While Deleting All ULike Logs/Data', WP_ULIKE_SLUG ) );
		} else {
			wp_send_json_success( __( 'Success! All ULike Logs/Data Have Been Deleted', WP_ULIKE_SLUG ) );
		}		 
	}

	/**
	 * Delete all likes number by ajax process. 
	 *
	 * @author       	Alimir	 	
	 * @since           2.2
	 * @return			Void
	 */
	function wp_ulike_delete_all_data() {
		global $wpdb;
		$get_action = $_POST['action'];
		//$wpdb->hide_errors();
		
		if($get_action == 'wp_ulike_posts_delete_data'){
			$meta_table = $wpdb->prefix."postmeta";
			$meta_key   = '_liked';
		} else if($get_action == 'wp_ulike_comments_delete_data'){
			$meta_table = $wpdb->prefix."commentmeta";
			$meta_key   = '_commentliked';
		} else if($get_action == 'wp_ulike_buddypress_delete_data'){
			$meta_table = $wpdb->prefix."bp_activity_meta";
			$meta_key   = '_activityliked';
		} else if($get_action == 'wp_ulike_bbpress_delete_data'){
			$meta_table = $wpdb->prefix."postmeta";
			$meta_key   = '_topicliked';
		}
			
		$do_action 		= $wpdb->delete($meta_table, array( 'meta_key' => $meta_key ));
			
		if ($do_action === FALSE) {
			wp_send_json_error( __( 'Failed! An Error Has Occurred While Deleting All ULike Logs/Data', WP_ULIKE_SLUG ));
		} else {
			wp_send_json_success( __( 'Success! All ULike Logs/Data Have Been Deleted', WP_ULIKE_SLUG ) );
		}		 
	}
	
	/**
	 * Add menu to admin
	 *
	 * @author       	Alimir	 	
	 * @since           1.0
	 * @updated         2.2
	 * @updated         2.4.2
	 * @return			String
	 */
	add_action('admin_menu', 'wp_ulike_admin_menu');
	function wp_ulike_admin_menu() {
		
		global $menu;
		
		//Post Like Logs Menu
		$posts_screen 		= add_submenu_page(null, __( 'Post Likes Logs', WP_ULIKE_SLUG ), __( 'Post Likes Logs', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-post-logs', 'wp_ulike_post_likes_logs');
		add_action("load-$posts_screen",'wp_ulike_logs_per_page');
		
		//Comment Like Logs Menu
		$comments_screen 	= add_submenu_page(null, __( 'Comment Likes Logs', WP_ULIKE_SLUG ), __( 'Comment Likes Logs', WP_ULIKE_SLUG ), 'manage_options','wp-ulike-comment-logs', 'wp_ulike_comment_likes_logs');
		add_action("load-$comments_screen",'wp_ulike_logs_per_page');
		
		//Activity Like Logs Menu
		$activities_screen 	= add_submenu_page(null, __( 'Activity Likes Logs', WP_ULIKE_SLUG ), __( 'Activity Likes Logs', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-bp-logs', 'wp_ulike_buddypress_likes_logs');
		add_action("load-$activities_screen",'wp_ulike_logs_per_page');
		
		//Activity Like Logs Menu
		$topics_screen 		= add_submenu_page(null, __( 'Topics Likes Logs', WP_ULIKE_SLUG ), __( 'Topics Likes Logs', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-bbpress-logs', 'wp_ulike_bbpress_likes_logs');
		add_action("load-$topics_screen",'wp_ulike_logs_per_page');
		
		//Statistics Menu
		$statistics_screen 	= add_submenu_page('wp-ulike-settings', __( 'WP ULike Statistics', WP_ULIKE_SLUG ), __( 'WP ULike Statistics', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-statistics', 'wp_ulike_statistics');
		add_action("load-$statistics_screen",'wp_ulike_statistics_register_option');
		
		//WP ULike About Menu
		add_submenu_page('wp-ulike-settings', __( 'About WP ULike', WP_ULIKE_SLUG ), __( 'About WP ULike', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-about', 'wp_ulike_about_page');
		
		$newvotes = wp_ulike_get_number_of_new_likes();
		$menu[313][0] .= $newvotes ? " <span class='update-plugins count-1'><span class='update-count'>". number_format_i18n($newvotes) ."</span></span> " : '';
		
	}

	/**
	 * The counter of last likes by the admin last login time.
	 *
	 * @author       	Alimir	 	
	 * @since           2.4.2
	 * @return			String
	 */
	function wp_ulike_get_number_of_new_likes()
	{
		global $wpdb;
		
		if(isset($_GET["page"]) && stripos($_GET["page"], "wp-ulike-statistics") !== false && is_super_admin())
			update_option('wpulike_lastvisit', current_time('mysql',0));
		
        $request =  "SELECT
					(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'))
					+
					(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_activities WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'))
					+
					(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_comments WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'))
					+
					(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_forums WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'));";		
	
		return $wpdb->get_var($request);
	}

	/**
	 * Set the admin login time.
	 *
	 * @author       	Alimir	 	
	 * @since           2.4.2
	 * @return			Void
	 */
	add_action('wp_logout', 'wp_ulike_set_lastvisit');
	function wp_ulike_set_lastvisit() {
		if (is_super_admin())
		update_option('wpulike_lastvisit', current_time('mysql',0));
		else
		return;
	}