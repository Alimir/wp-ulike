<?php
/*
Plugin Name: WP ULike
Plugin URI: http://wp-ulike.alimir.ir/
Description: WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments AND buddypress activities. Its very simple to use and supports many options.
Version: 3.0
Author: Ali Mirzaei
Author URI: http://about.alimir.ir
Text Domain: wp-ulike
Domain Path: /lang/
License: GPL2

/------------------------------------------\
 _     __     _ _____      _  _  _   _ 
| |   /  \   | | ___ \    | |(_)| | / /
| |  / /\ \  | | |_/ /   _| || || |/ / ___
| | / /  \ \ | |  __/ | | | || ||   | / _ \
| |/ /    \ \| | |  | |_| | || || |\ \  __/
\___/      \__/\_|   \__,_|_||_||_| \_\___|

\--> Alimir, 2017 <--/

Thanks for using WP ULike plugin!

\------------------------------------------/

*/

//Do not change this value
define( 'WP_ULIKE_PLUGIN_URI'   , 'http://wp-ulike.alimir.ir' 	);
define( 'WP_ULIKE_VERSION'      , '3.0' 						);
define( 'WP_ULIKE_SLUG'         , 'wp-ulike' 					);
define( 'WP_ULIKE_DB_VERSION'   , '1.3' 						);

define( 'WP_ULIKE_DIR'          , plugin_dir_path( __FILE__ ) 	);
define( 'WP_ULIKE_URL'          , plugins_url( '', __FILE__ ) 	);
define( 'WP_ULIKE_BASENAME'     , plugin_basename( __FILE__ ) 	);

define( 'WP_ULIKE_ADMIN_DIR'    , WP_ULIKE_DIR . '/admin' 		);
define( 'WP_ULIKE_ADMIN_URL'    , WP_ULIKE_URL . '/admin' 		);

define( 'WP_ULIKE_INC_DIR'      , WP_ULIKE_DIR . '/inc' 		);
define( 'WP_ULIKE_INC_URL'      , WP_ULIKE_URL . '/inc' 		);

define( 'WP_ULIKE_ASSETS_DIR'   , WP_ULIKE_DIR . '/assets' 		);
define( 'WP_ULIKE_ASSETS_URL'   , WP_ULIKE_URL . '/assets' 		);
  
// Load Translations 
load_plugin_textdomain( WP_ULIKE_SLUG, false, dirname( WP_ULIKE_BASENAME ) .'/lang/' );

/**
 * When the plugin is activated, This function will install wp_ulike tables in database (If not exist table)
 *
 * @author        	Alimir
 * @since           1.1
 * @updated         1.7
 * @return   		Void
 */
register_activation_hook( __FILE__, 'wp_ulike_install' );
function wp_ulike_install() {
	global $wpdb;

	$table_name = $wpdb->prefix . "ulike";
	if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE " . $table_name . " (
				`id` bigint(11) NOT NULL AUTO_INCREMENT,
				`post_id` int(11) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(30) NOT NULL,
				`user_id` int(11) UNSIGNED NOT NULL,
				`status` varchar(15) NOT NULL,
				PRIMARY KEY (`id`)
			);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
	}

	$table_name_2 = $wpdb->prefix . "ulike_comments";
	if ( $wpdb->get_var( "show tables like '$table_name_2'" ) != $table_name_2 ) {
		$sql = "CREATE TABLE " . $table_name_2 . " (
				`id` bigint(11) NOT NULL AUTO_INCREMENT,
				`comment_id` int(11) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(30) NOT NULL,
				`user_id` int(11) UNSIGNED NOT NULL,
				`status` varchar(15) NOT NULL,
				PRIMARY KEY (`id`)
			);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
	}

	$table_name_3 = $wpdb->prefix . "ulike_activities";
	if ( $wpdb->get_var( "show tables like '$table_name_3'" ) != $table_name_3 ) {
		$sql = "CREATE TABLE " . $table_name_3 . " (
				`id` bigint(11) NOT NULL AUTO_INCREMENT,
				`activity_id` int(11) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(30) NOT NULL,
				`user_id` int(11) UNSIGNED NOT NULL,
				`status` varchar(15) NOT NULL,
				PRIMARY KEY (`id`)
			);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
	}

	$table_name_4 = $wpdb->prefix . "ulike_forums";
	if ( $wpdb->get_var( "show tables like '$table_name_4'" ) != $table_name_4 ) {
		$sql = "CREATE TABLE " . $table_name_4 . " (
				`id` bigint(11) NOT NULL AUTO_INCREMENT,
				`topic_id` int(11) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(30) NOT NULL,
				`user_id` int(11) UNSIGNED NOT NULL,
				`status` varchar(15) NOT NULL,
				PRIMARY KEY (`id`)
			);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
	}
	
}


/**
 * Applied to the list of links to display on the plugins page 
 *
 * @author        	Alimir
 * @since           2.3
 * @return   		String
 */
$prefix = is_network_admin() ? 'network_admin_' : '';
add_filter( "{$prefix}plugin_action_links", 'wp_ulike_add_plugin_links', 10, 5 );
function wp_ulike_add_plugin_links( $actions, $plugin_file ) {

	if (  $plugin_file === WP_ULIKE_BASENAME ) {
		$settings 	= array('settings' 	=> '<a href="admin.php?page=wp-ulike-settings">' . __('Settings', WP_ULIKE_SLUG) . '</a>');
		$stats	 	= array('stats' 	=> '<a href="admin.php?page=wp-ulike-statistics">' . __('Statistics', WP_ULIKE_SLUG) . '</a>');
		$about	 	= array('about' 	=> '<a href="admin.php?page=wp-ulike-about">' . __('About', WP_ULIKE_SLUG) . '</a>');
		// Merge on actions array
		$actions	= array_merge( $about, $actions );
		$actions 	= array_merge( $stats, $actions );
		$actions 	= array_merge( $settings, $actions );		
	}
		
	return $actions;
}

/**
 * Redirect to the "About WP ULike" page after plugin activation.
 *
 * @author        	Alimir
 * @since         	2.3
 * @return   		Void
 */
add_action( 'activated_plugin', 'wp_ulike_activation_redirect' );
function wp_ulike_activation_redirect( $plugin ) {
    if( $plugin == WP_ULIKE_BASENAME ) {
        // Display WP ULike Notification
        update_option( 'wp-ulike-notice-dismissed', FALSE );
        // Redirect to the about page
        exit( wp_redirect( admin_url( 'admin.php?page=wp-ulike-about' ) ) );
    }
}

/**
 * This hook is called once any activated plugins have been loaded.
 *
 * @author        	Alimir
 * @since         	1.7
 * @updated         2.8 //Added 'call_user_func' for better callbacks 
 * @updated         3.0
 * @return   		Void
 */
add_action( 'plugins_loaded', 'wp_ulike_plugins_loaded' );
function wp_ulike_plugins_loaded() {
	// Update database
	if ( get_site_option( 'wp_ulike_dbVersion' ) != WP_ULIKE_DB_VERSION ) {
		call_user_func('wp_ulike_install');
	}
}

/**
 * Initialize the plugin
 * ===========================================================================*/

//Include plugin setting file
require_once( WP_ULIKE_ADMIN_DIR 	. '/admin.php' 			);
//Include general functions
require_once( WP_ULIKE_INC_DIR 		. '/wp-functions.php' 	);
//Load WP ULike functions
require_once( WP_ULIKE_INC_DIR 		. '/wp-ulike.php' 		);

/*============================================================================*/
