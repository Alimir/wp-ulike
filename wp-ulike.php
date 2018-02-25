<?php
/*
Plugin Name: WP ULike
Plugin URI: https://wpulike.com/
Description: WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments AND buddypress activities. Its very simple to use and supports many options.
Version: 3.1
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

\--> Alimir, 2018 <--/

Thanks for using WP ULike plugin!

\------------------------------------------/

*/

// Do not change these values
define( 'WP_ULIKE_PLUGIN_URI'   , 'https://wpulike.com/' 		);
define( 'WP_ULIKE_VERSION'      , '3.1' 						);
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

/**
 * Initialize the plugin
 * ===========================================================================*/

if ( ! class_exists( 'WPULIKE' ) ) :

	class WPULIKE {

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

	    	add_action( 'init', array( $this, 'init' ) );	
			// Include Files
			$this->includes();
			
			// Activate plugin when new blog is added
			add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
			
			add_action( 'activated_plugin', array( $this, 'after_activation' ) );
			
			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links",  array( $this, 'add_links' ), 10, 5 );        
			
			// Loaded action
			do_action( 'wp_ulike_loaded' );
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
	     * Create settings page
	     *
	     * @since    3.1
	     *
	     * @return   Array
	    */
	    public function settings() {

			$wp_ulike_setting = new wp_ulike_settings(
				'wp-ulike-settings',
				__( 'WP ULike Settings', WP_ULIKE_SLUG ),
				array(
					'parent'   => false,
					'title'    =>  __( 'WP ULike', WP_ULIKE_SLUG ),
					'position' => 313,
					'icon_url' => 'dashicons-wp-ulike'
				),
				array(
					'wp_ulike_general' => wp_ulike_get_options_info('general')
				),
				array(
					'tabs'    => true,
					'updated' => __('Settings saved.',WP_ULIKE_SLUG)
				)
			);

			//activate other settings panels
			$wp_ulike_setting->apply_settings( array(
					'wp_ulike_posts'      => apply_filters( 'wp_ulike_posts_settings'		, wp_ulike_get_options_info('posts') 		),
					'wp_ulike_comments'   => apply_filters( 'wp_ulike_comments_settings'	, wp_ulike_get_options_info('comments') 	),
					'wp_ulike_buddypress' => apply_filters( 'wp_ulike_buddypress_settings'	, wp_ulike_get_options_info('buddypress') 	),
					'wp_ulike_bbpress'    => apply_filters( 'wp_ulike_bbpress_settings'		, wp_ulike_get_options_info('bbpress') 		),
					'wp_ulike_customize'  => apply_filters( 'wp_ulike_customize_settings'	, wp_ulike_get_options_info('customizer') 	)
				)
			);		
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

			// global variable of user IP
			$wp_user_IP     = $this->get_ip();	        
			
			// load common functionalities
			include_once( WP_ULIKE_INC_DIR . '/index.php' );
			
			// global wp_ulike_class
			$wp_ulike_class = wp_ulike::get_instance();	        

	        // Dashboard and Administrative Functionality
	        if ( is_admin() ) {
	            // Load AJAX spesific codes on demand
	            if ( defined('DOING_AJAX') && DOING_AJAX ){
					include( WP_ULIKE_INC_DIR . '/frontend-ajax.php' );
					include( WP_ULIKE_ADMIN_DIR . '/admin-ajax.php'  );
	            }
		       	// Add Settings Page
		        $this->settings();	
		        
				//include wp_ulike_stats class & geoIPloc functions
				if( isset( $_GET["page"] ) && stripos( $_GET["page"], "wp-ulike-statistics" ) !== false ){
					//include PHP GeoIPLocation Library
					require_once( WP_ULIKE_ADMIN_DIR . '/includes/geoiploc.php');
					// global variable
					global $wp_ulike_stats;
					$wp_ulike_stats = wp_ulike_stats::get_instance();			
				};	

	            // Load admin spesific codes
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

			if ( getenv( 'HTTP_CLIENT_IP' ) ) {
				return getenv( 'HTTP_CLIENT_IP' );
			} elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
				return getenv( 'HTTP_X_FORWARDED_FOR' );
			} elseif ( getenv( 'HTTP_X_FORWARDED' ) ) {
				return getenv( 'HTTP_X_FORWARDED' );
			} elseif ( getenv( 'HTTP_FORWARDED_FOR' ) ) {
				return getenv( 'HTTP_FORWARDED_FOR' );
			} elseif ( getenv( 'HTTP_FORWARDED' ) ) {
				return getenv( 'HTTP_FORWARDED' );
			} else {
				return $_SERVER['REMOTE_ADDR'];
			}
		}	    

	   /**
	    * Init the plugin when WordPress Initialises.
	    *
	    * @return void
	    */
	    public function init(){

	        // @deprecate version 5.0
	        global $wp_version;
	        if ( version_compare( $wp_version, '4.6', '<' ) ) {
	            // Load plugin text domain
	            $this->load_plugin_textdomain();
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
	            // Display WP ULike Notification
	            update_option( 'wp-ulike-notice-dismissed', FALSE );
	            // Redirect to the about page
	            if( ! wp_doing_ajax() ) {
	                exit( wp_redirect( admin_url( 'admin.php?page=wp-ulike-about' ) ) );
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

	        if ( get_site_option( 'wp_ulike_dbVersion' ) != WP_ULIKE_DB_VERSION ) {

	            $posts_table = $wpdb->prefix . "ulike";
	            if ( $wpdb->get_var( "show tables like '$posts_table'" ) != $posts_table ) {
	                $sql = "CREATE TABLE " . $posts_table . " (
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

	            $comments_table = $wpdb->prefix . "ulike_comments";
	            if ( $wpdb->get_var( "show tables like '$comments_table'" ) != $comments_table ) {
	                $sql = "CREATE TABLE " . $comments_table . " (
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

	            $activities_table = $wpdb->prefix . "ulike_activities";
	            if ( $wpdb->get_var( "show tables like '$activities_table'" ) != $activities_table ) {
	                $sql = "CREATE TABLE " . $activities_table . " (
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

	            $forums_table = $wpdb->prefix . "ulike_forums";
	            if ( $wpdb->get_var( "show tables like '$forums_table'" ) != $forums_table ) {
	                $sql = "CREATE TABLE " . $forums_table . " (
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
	        load_plugin_textdomain( WP_ULIKE_SLUG, FALSE, dirname( WP_ULIKE_BASENAME ) . '/lang/' );
	    }

	}

    /**
     * Open WP Ulike World :)
     *
     * @since    3.1
     */
	function RUN_WPULIKE(){ 
	    return WPULIKE::get_instance(); 
	}
	RUN_WPULIKE();

	// Register hooks that are fired when the plugin is activated or deactivated.
	register_activation_hook  ( __FILE__, array( 'WPULIKE', 'activate'   ) );
	register_deactivation_hook( __FILE__, array( 'WPULIKE', 'deactivate' ) );

else : 

	function wp_ulike_two_instances_error() {
		$class   = 'notice notice-error';
		$message = __( 'You are using two instances of WP ULike plugin at same time, please deactive one of them.', WP_ULIKE_SLUG );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}
	add_action( 'admin_notices', 'wp_ulike_two_instances_error' );                

endif;

/*============================================================================*/

