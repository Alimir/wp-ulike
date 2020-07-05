<?php
/**
 * All wp-ulike functionalities starting from here...
 *
 * // @echo HEADER
 *
 * Plugin Name:       WP ULike
 * Plugin URI:        https://wpulike.com/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description:       WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments AND buddypress activities. Its very simple to use and supports many options.
 * Version:           4.2.5
 * Author:            Ali Mirzaei
 * Author URI:        https://wpulike.com/?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * Text Domain:       wp-ulike
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:       languages
 * Tested up to: 	  5.3.1

 /------------------------------------------\
  _     __     _ _____      _  _  _   _
 | |   /  \   | | ___ \    | |(_)| | / /
 | |  / /\ \  | | |_/ /   _| || || |/ / ___
 | | / /  \ \ | |  __/ | | | || ||   | / _ \
 | |/ /    \ \| | |  | |_| | || || |\ \  __/
 \___/      \__/\_|   \__,_|_||_||_| \_\___|

 \--> Alimir, 2019 <--/

 Thanks for using WP ULike plugin!

 \------------------------------------------/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die('No Naughty Business Please !');
}

// Abort loading if WordPress is upgrading
if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
    return;
}

// Do not change these values
define( 'WP_ULIKE_PLUGIN_URI'   , 'https://wpulike.com/' 		 );
define( 'WP_ULIKE_VERSION'      , '4.2.5' 					 	 );
define( 'WP_ULIKE_DB_VERSION'   , '2.1' 					 	 );
define( 'WP_ULIKE_SLUG'         , 'wp-ulike' 					 );
define( 'WP_ULIKE_NAME'         , __( 'WP ULike', WP_ULIKE_SLUG ));

define( 'WP_ULIKE_DIR'          , plugin_dir_path( __FILE__ ) 	 );
define( 'WP_ULIKE_URL'          , plugins_url( '', __FILE__ ) 	 );
define( 'WP_ULIKE_BASENAME'     , plugin_basename( __FILE__ ) 	 );

define( 'WP_ULIKE_ADMIN_DIR'    , WP_ULIKE_DIR . '/admin' 		 );
define( 'WP_ULIKE_ADMIN_URL'    , WP_ULIKE_URL . '/admin' 		 );

define( 'WP_ULIKE_INC_DIR'      , WP_ULIKE_DIR . '/inc' 		 );
define( 'WP_ULIKE_INC_URL'      , WP_ULIKE_URL . '/inc' 		 );

define( 'WP_ULIKE_ASSETS_DIR'   , WP_ULIKE_DIR . '/assets' 		 );
define( 'WP_ULIKE_ASSETS_URL'   , WP_ULIKE_URL . '/assets' 		 );

/**
 * Initialize the plugin
 * ===========================================================================*/

if ( ! class_exists( 'WpUlikeInit' ) ) :

	class WpUlikeInit {

	  /**
	    * Instance of this class.
	    *
	    * @since    3.1
	    *
	    * @var      object
	    */
		protected static $instance = null;


	   /**
	    * Initialize the plugin
	    *
	    * @since     3.1
	    */
	    private function __construct() {
			// init plugin
			add_action( 'plugins_loaded', array( $this, 'init' ) );

	    	add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	    	add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );

			// Activate plugin when new blog is added
			add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
			add_action( 'activated_plugin', array( $this, 'after_activation' ) );

			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links",  array( $this, 'add_links' ), 10, 5 );
	    }

	   /**
	    * Init the plugin when WordPress Initialises.
	    *
	    * @return void
	    */
	    public function admin_assets( $hook ){
	    	new wp_ulike_admin_assets( $hook );
	    }

	   /**
	    * Init the plugin when WordPress Initialises.
	    *
	    * @return void
	    */
	    public function frontend_assets(){
			new wp_ulike_frontend_assets();
	    }

	   /**
	    * Init the plugin when WordPress Initialises.
	    *
	    * @return void
	    */
	    public function init(){
			// Check database upgrade if needed
			if ( version_compare( get_option( 'wp_ulike_dbVersion', '1.6' ), WP_ULIKE_DB_VERSION, '<' ) ) {
				$this->single_activate();
			}

			// Define constant values
			$this->define_constants();

	    	// Include Files
	    	$this->includes();

			// Load plugin text domain
			$this->load_plugin_textdomain();

			// Loaded action
			do_action( 'wp_ulike_loaded' );
		}

		private function define_constants(){
			// a custom directory in uploads directory for storing custom files. Default uploads/{TWT_DOMAIN}
			$uploads = wp_get_upload_dir();
			define( 'WP_ULIKE_CUSTOM_DIR' , $uploads['basedir'] . '/' . WP_ULIKE_SLUG );
		}

	    /**
	     * Add custom links too plugin info
	     *
	     * @since    3.1
	     *
	     * @return   Array
	    */
	    public function add_links( $actions, $plugin_file ) {

	        if (  $plugin_file === WP_ULIKE_BASENAME ) {
				$settings = array('settings'  => '<a href="admin.php?page=wp-ulike-settings">' . __('Settings', WP_ULIKE_SLUG) . '</a>');
				$stats    = array('stats'     => '<a href="admin.php?page=wp-ulike-statistics">' . __('Statistics', WP_ULIKE_SLUG) . '</a>');
				$about    = array('about'     => '<a href="admin.php?page=wp-ulike-about">' . __('About', WP_ULIKE_SLUG) . '</a>');
				// Merge on actions array
				$actions  = array_merge( $about, $actions );
				$actions  = array_merge( $stats, $actions );
				$actions  = array_merge( $settings, $actions );
	        }

	        return $actions;
	    }


	    /**
	     * Auto-load classes on demand to reduce memory consumption
	     *
	     * @param mixed $class
	     * @return void
	     */
	    public function autoload( $class ) {

	        $path  = null;
	        $class = strtolower( $class );
	        $file = 'class-' . str_replace( '_', '-', $class ) . '.php';

	        // the possible pathes containing classes
	        $possible_pathes = array(
	            WP_ULIKE_INC_DIR   . '/classes/',
	            WP_ULIKE_ADMIN_DIR . '/classes/'
	        );

	        foreach ( $possible_pathes as $path ) {
	            if( is_readable( $path . $file ) ){
	                include_once( $path . $file );
	                return;
	            }

	        }

	    }


	    /**
	     * Include Files
	     *
	     * @return void
	    */
	    private function includes() {

	    	// Global Variables
	    	global $wp_user_IP, $wp_ulike_class;

	        // Auto-load classes on demand
	        if ( function_exists( "__autoload" ) ) {
	            spl_autoload_register( "__autoload" );
	        }
	        spl_autoload_register( array( $this, 'autoload' ) );

			// load common functionalities
			include_once( WP_ULIKE_INC_DIR . '/index.php' );

			// global variable of user IP
			$wp_user_IP     = $this->get_ip();

			// global wp_ulike_class
			$wp_ulike_class = wp_ulike::get_instance();

	        // Dashboard and Administrative Functionality
	        if ( is_admin() ) {
	            // Load AJAX specific codes on demand
	            if ( defined('DOING_AJAX') && DOING_AJAX ){
					include( WP_ULIKE_INC_DIR . '/hooks/frontend-ajax.php' );
					include( WP_ULIKE_ADMIN_DIR . '/admin-ajax.php'  );
	            }

	            // Load admin specific codes
	            include( WP_ULIKE_ADMIN_DIR . '/index.php' );
	        }

	    }

	    /**
	     * Get Client IP address
	     *
	     * @since    3.1
	     *
	     * @return   String
	    */
		public function get_ip() {
			// Get user IP
			$user_ip = wp_ulike_get_user_ip();
			// Check GDPR anonymise
			if ( wp_ulike_is_true( wp_ulike_get_option( 'enable_anonymise_ip', false ) ) ) {
				return $this->anonymise_ip( $user_ip );
			} else {
				return $user_ip;
			}
		}

	    /**
	     * Anonymise IP address
	     *
	     * @since    3.3
	     *
	     * @return   String
	    */
		public function anonymise_ip( $ip_address ) {
			if ( strpos( $ip_address, "." ) == true ) {
				return preg_replace('~[0-9]+$~', '0', $ip_address);
			} else {
				return preg_replace('~[0-9]*:[0-9]+$~', '0000:0000', $ip_address);
			}
		}

	   /**
	    * Return an instance of this class.
	    *
	    * @since     3.1
	    *
	    * @return    object    A single instance of this class.
	    */
	    public static function get_instance() {

	        // If the single instance hasn't been set, set it now.
	        if ( null == self::$instance ) {
	          self::$instance = new self;
	        }

	        return self::$instance;
	    }


	   /**
	    * Fired when the plugin is activated.
	    *
	    * @since    3.1
	    *
	    * @param    boolean    $network_wide    True if WPMU superadmin uses
	    *                                       "Network Activate" action, false if
	    *                                       WPMU is disabled or plugin is
	    *                                       activated on an individual blog.
	    */
	    public static function activate( $network_wide ) {

	        if ( function_exists( 'is_multisite' ) && is_multisite() ) {

	          if ( $network_wide  ) {

	            // Get all blog ids
	            $blog_ids = self::get_blog_ids();

	            foreach ( $blog_ids as $blog_id ) {

	              switch_to_blog( $blog_id );
	              self::single_activate();
	            }

	            restore_current_blog();

	          } else {
	            self::single_activate();
	          }

	        } else {
	          self::single_activate();
	        }
	    }

	    public function after_activation( $plugin ) {
	        if( $plugin == WP_ULIKE_BASENAME ) {
	            // Redirect to the about page
	            if( wp_safe_redirect( admin_url( 'admin.php?page=wp-ulike-about' ) ) ) {
					exit;
	            }
	        }
	    }


	  /**
	   * Fired when the plugin is deactivated.
	   *
	   * @since    3.1
	   *
	   * @param    boolean    $network_wide    True if WPMU superadmin uses
	   *                                       "Network Deactivate" action, false if
	   *                                       WPMU is disabled or plugin is
	   *                                       deactivated on an individual blog.
	   */
	    public static function deactivate( $network_wide ) {

	        if ( function_exists( 'is_multisite' ) && is_multisite() ) {

	            if ( $network_wide ) {

	                // Get all blog ids
	                $blog_ids = self::get_blog_ids();

	                foreach ( $blog_ids as $blog_id ) {
	                    switch_to_blog( $blog_id );
	                    self::single_deactivate();
	                }

	                restore_current_blog();

	            } else {
	                self::single_deactivate();
	            }

	        } else {
	            self::single_deactivate();
	        }

	    }


	    /**
	     * Fired for each blog when the plugin is activated.
	     *
	     * @since    3.1
	     */
	    private static function single_activate() {

			global $wpdb;

			$max_index_length = 191;

			if ( ! empty( $wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}

			if( ! function_exists('maybe_create_table') ){
				// Add one library admin function for next function
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			}

			// Posts table
			$posts_table = $wpdb->prefix . "ulike";
			maybe_create_table( $posts_table, "CREATE TABLE IF NOT EXISTS `{$posts_table}` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`post_id` bigint(20) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(100) NOT NULL,
				`user_id` varchar(100) NOT NULL,
				`status` varchar(30) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `post_id` (`post_id`),
				KEY `date_time` (`date_time`),
				KEY `user_id` (`user_id`),
				KEY `status` (`status`)
			) $charset_collate AUTO_INCREMENT=1;" );

			// Comments table
			$comments_table = $wpdb->prefix . "ulike_comments";
			maybe_create_table( $comments_table, "CREATE TABLE IF NOT EXISTS `{$comments_table}` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`comment_id` bigint(20) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(100) NOT NULL,
				`user_id` varchar(100) NOT NULL,
				`status` varchar(30) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `comment_id` (`comment_id`),
				KEY `date_time` (`date_time`),
				KEY `user_id` (`user_id`),
				KEY `status` (`status`)
			) $charset_collate AUTO_INCREMENT=1;" );

			// Activities table
			$activities_table = $wpdb->prefix . "ulike_activities";
			maybe_create_table( $activities_table, "CREATE TABLE IF NOT EXISTS `{$activities_table}` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`activity_id` bigint(20) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(100) NOT NULL,
				`user_id` varchar(100) NOT NULL,
				`status` varchar(30) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `activity_id` (`activity_id`),
				KEY `date_time` (`date_time`),
				KEY `user_id` (`user_id`),
				KEY `status` (`status`)
			) $charset_collate AUTO_INCREMENT=1;" );

			// Forums table
			$forums_table = $wpdb->prefix . "ulike_forums";
			maybe_create_table( $forums_table, "CREATE TABLE IF NOT EXISTS `{$forums_table}` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`topic_id` bigint(20) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(100) NOT NULL,
				`user_id` varchar(100) NOT NULL,
				`status` varchar(30) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `topic_id` (`topic_id`),
				KEY `date_time` (`date_time`),
				KEY `user_id` (`user_id`),
				KEY `status` (`status`)
			) $charset_collate AUTO_INCREMENT=1;" );

			// Meta values table
			$meta_table = $wpdb->prefix . "ulike_meta";
			maybe_create_table( $meta_table, "CREATE TABLE IF NOT EXISTS `{$meta_table}` (
				`meta_id` bigint(20) unsigned NOT NULL auto_increment,
				`item_id` bigint(20) unsigned NOT NULL default '0',
				`meta_group` varchar(100) default NULL,
				`meta_key` varchar(255) default NULL,
				`meta_value` longtext,
				PRIMARY KEY  (`meta_id`),
				KEY `item_id` (`item_id`),
				KEY `meta_group` (`meta_group`),
				KEY `meta_key` (`meta_key`($max_index_length))
			) $charset_collate AUTO_INCREMENT=1;" );

			// Upgrade Tables
			if ( version_compare( get_option( 'wp_ulike_dbVersion', '1.6' ), WP_ULIKE_DB_VERSION, '<' ) ) {
				// Posts ugrades
				$wpdb->query( "
					ALTER TABLE $posts_table
					ADD INDEX( `post_id`, `date_time`, `user_id`, `status`),
					CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
					CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
				" );
				// Comments ugrades
				$wpdb->query( "
					ALTER TABLE $comments_table
					ADD INDEX( `comment_id`, `date_time`, `user_id`, `status`),
					CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
					CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
				" );
				// BuddyPress ugrades
				$wpdb->query( "
					ALTER TABLE $activities_table
					ADD INDEX( `activity_id`, `date_time`, `user_id`, `status`),
					CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
					CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
				" );
				// bbPress upgrades
				$wpdb->query( "
					ALTER TABLE $forums_table
					ADD INDEX( `topic_id`, `date_time`, `user_id`, `status`),
					CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
					CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
				" );
				// Update db version
				update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
			}

	        do_action( 'wp_ulike_activated', get_current_blog_id() );
	    }


	    /**
	     * Fired for each blog when the plugin is deactivated.
	     *
	     * @since    3.1
	     */
	    private static function single_deactivate() {
	        do_action( 'wp_ulike_deactivated' );
	    }


	   /**
	     * Fired when a new site is activated with a WPMU environment.
	     *
	     * @since    3.1
	     *
	     * @param    int    $blog_id    ID of the new blog.
	    */
	    public function activate_new_site( $blog_id ) {
	        if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
	          return;
	        }

	        switch_to_blog( $blog_id );
	        self::single_activate();
	        restore_current_blog();
	    }

	    /**
	     * Get all blog ids of blogs in the current network that are:
	     * - not archived
	     * - not spam
	     * - not deleted
	     *
	     * @since    3.1
	     *
	     * @return   array|false    The blog ids, false if no matches.
	     */
	    private static function get_blog_ids() {
	        global $wpdb;

	        // get an array of blog ids
	        $sql = "SELECT blog_id FROM $wpdb->blogs
	        WHERE archived = '0' AND spam = '0'
	        AND deleted = '0'";

	        return $wpdb->get_col( $sql );
	    }

	    /**
	     * Load the plugin text domain for translation.
	     *
	     * @since    3.1
	     */
	    public function load_plugin_textdomain() {
	        $locale = apply_filters( 'plugin_locale', get_locale(), WP_ULIKE_SLUG );
	        load_textdomain( WP_ULIKE_SLUG, trailingslashit( WP_LANG_DIR ) . WP_ULIKE_SLUG . '/' . WP_ULIKE_SLUG . '-' . $locale . '.mo' );
	        load_plugin_textdomain( WP_ULIKE_SLUG, FALSE, dirname( WP_ULIKE_BASENAME ) . '/languages' );
	    }

	}

	// Register hooks that are fired when the plugin is activated or deactivated.
	register_activation_hook  ( __FILE__, array( 'WpUlikeInit', 'activate'   ) );
	register_deactivation_hook( __FILE__, array( 'WpUlikeInit', 'deactivate' ) );

    /**
     * Open WP Ulike World :)
     *
     * @since    3.1
     */
	function RUN_WPULIKE(){
	    return WpUlikeInit::get_instance();
	}
	RUN_WPULIKE();

else :

	function wp_ulike_two_instances_error() {
		$class   = 'notice notice-error';
		$message = __( 'You are using two instances of WP ULike plugin at same time, please deactive one of them.', WP_ULIKE_SLUG );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
	add_action( 'admin_notices', 'wp_ulike_two_instances_error' );

endif;

/*============================================================================*/

