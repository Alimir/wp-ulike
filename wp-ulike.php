<?php
/**
 * All wp-ulike functionalities starting from here...
 *
 * // @echo HEADER
 *
 * Plugin Name:       WP ULike
 * Plugin URI:        https://wpulike.com/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description:       WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments AND buddypress activities. Its very simple to use and supports many options.
 * Version:           4.4.8
 * Author:            TechnoWich
 * Author URI:        https://technowich.com/?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * Text Domain:       wp-ulike
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:       /languages/
 * Tested up to: 	  5.7.1

 /------------------------------------------\
  _     __     _ _____      _  _  _   _
 | |   /  \   | | ___ \    | |(_)| | / /
 | |  / /\ \  | | |_/ /   _| || || |/ / ___
 | | / /  \ \ | |  __/ | | | || ||   | / _ \
 | |/ /    \ \| | |  | |_| | || || |\ \  __/
 \___/      \__/\_|   \__,_|_||_||_| \_\___|

 \--> Alimir, 2021 <--/

 Thanks for using WP ULike plugin!

 \------------------------------------------/
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Do not change these values
define( 'WP_ULIKE_PLUGIN_URI'   , 'https://wpulike.com/' 		 );
define( 'WP_ULIKE_VERSION'      , '4.4.8' 					 	 );
define( 'WP_ULIKE_DB_VERSION'   , '2.1' 					 	 );
define( 'WP_ULIKE_SLUG'         , 'wp-ulike' 					 );
define( 'WP_ULIKE_NAME'         , __( 'WP ULike', WP_ULIKE_SLUG ));

define( 'WP_ULIKE_DIR'          , plugin_dir_path( __FILE__ ) 	 );
define( 'WP_ULIKE_URL'          , plugins_url( '', __FILE__ ) 	 );
define( 'WP_ULIKE_BASENAME'     , plugin_basename( __FILE__ ) 	 );

define( 'WP_ULIKE_ADMIN_DIR'    , WP_ULIKE_DIR . 'admin' 		 );
define( 'WP_ULIKE_ADMIN_URL'    , WP_ULIKE_URL . '/admin' 		 );

define( 'WP_ULIKE_INC_DIR'      , WP_ULIKE_DIR . 'includes' 	 );
define( 'WP_ULIKE_INC_URL'      , WP_ULIKE_URL . '/includes'     );

define( 'WP_ULIKE_ASSETS_DIR'   , WP_ULIKE_DIR . 'assets' 		 );
define( 'WP_ULIKE_ASSETS_URL'   , WP_ULIKE_URL . '/assets' 		 );

/**
 * Initialize the plugin
 * ===========================================================================*/

require WP_ULIKE_INC_DIR . '/action.php';
// Register hooks that are fired when the plugin is activated or deactivated.
register_activation_hook  ( __FILE__, array( 'wp_ulike_register_action_hook', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'wp_ulike_register_action_hook', 'deactivate' ) );

if ( ! class_exists( 'WpUlikeInit' ) ) {
	// Include plugin starter
	require WP_ULIKE_INC_DIR . '/plugin.php';

} else {

	function wp_ulike_two_instances_error() {
		$class   = 'notice notice-error';
		$message = __( 'You are using two instances of WP ULike plugin at same time, please deactive one of them.', WP_ULIKE_SLUG );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
	add_action( 'admin_notices', 'wp_ulike_two_instances_error' );

}
/*============================================================================*/