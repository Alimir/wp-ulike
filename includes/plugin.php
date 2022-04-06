<?php
/**
 * WP ULIKE BASE CLASS
 *
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
    $this->plugin();

    // This hook is called once any activated plugins have been loaded.
    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

    // Activate plugin when new blog is added
    add_action( 'activated_plugin', array( $this, 'after_activation' ) );

    // init action
    add_action( 'init', array( $this, 'init' ) );

    $prefix = is_network_admin() ? 'network_admin_' : '';
    add_filter( "{$prefix}plugin_action_links",  array( $this, 'add_links' ), 10, 5 );
  }

  /**
   * init method
   *
   * @return void
   */
  public function init(){
    if( self::is_frontend() || self::is_ajax() ){
      $this->initialize_session();
    }
  }

  /**
   * Plugins loaded hook
   *
   * @return void
   */
  public function plugins_loaded(){
    // Upgrade database
    if ( self::is_admin_backend() ) {
      $this->maybe_upgrade_database();
    }
    // Load plugin text domain
    $this->load_plugin_textdomain();
  }

  private function maybe_upgrade_database(){
    $current_version = get_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
    // Check database upgrade if needed
    if ( version_compare( $current_version, '2.1', '<' ) ) {
      wp_ulike_activator::get_instance()->upgrade_0();
    }
    if ( version_compare( $current_version, '2.2', '<' ) ) {
      wp_ulike_activator::get_instance()->upgrade_1();
    }
    if ( version_compare( $current_version, '2.3', '<' ) ) {
      wp_ulike_activator::get_instance()->upgrade_2();
    }
    if ( version_compare( $current_version, '2.4', '<' ) ) {
      wp_ulike_activator::get_instance()->upgrade_3();
    }
  }

  /**
  * Init the plugin when WordPress Initialises.
  *
  * @return void
  */
  public function plugin(){
    // Define constant values
    $this->define_constants();

    // Include Files
    $this->includes();

    // Loaded action
    do_action( 'wp_ulike_loaded' );
  }

  /**
   * Define constants
   *
   * @return void
   */
  private function define_constants(){
    // a custom directory in uploads directory for storing custom files. Default uploads/{TWT_DOMAIN}
    $uploads = wp_get_upload_dir();
    define( 'WP_ULIKE_CUSTOM_DIR' , $uploads['basedir'] . '/' . WP_ULIKE_SLUG );
  }

  /**
   * Add admin links
   *
   * @param array $actions
   * @param string $plugin_file
   * @return array
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
    // Auto-load classes on demand
    if ( function_exists( "__autoload" ) ) {
      spl_autoload_register( "__autoload" );
    }
    spl_autoload_register( array( $this, 'autoload' ) );

    // load packages
    include_once( WP_ULIKE_DIR . '/vendor/autoload.php' );

    // load common functionalities
    include_once( WP_ULIKE_INC_DIR . '/index.php' );

    // Dashboard and Administrative Functionality
    if ( self::is_admin_backend() ) {
      // Load admin specific codes
      include( WP_ULIKE_ADMIN_DIR . '/index.php' );

      // Load AJAX specific codes on demand
      if ( self::is_ajax() ){
        include( WP_ULIKE_INC_DIR . '/hooks/frontend-ajax.php' );
        include( WP_ULIKE_ADMIN_DIR . '/admin-ajax.php'  );
      }
    }

    // Load Frontend Functionality
    if( self::is_frontend() ){
      include( WP_ULIKE_INC_DIR . '/public/index.php' );
    }
  }

  /**
   * Is ajax
   *
   * @return bool
   */
  public static function is_ajax() {
    return ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' );
  }

  /**
   * Is admin
   *
   * @return bool
   */
  public static function is_admin_backend() {
    return is_admin();
  }

  /**
   * Is cron
   *
   * @return bool
   */
  public static function is_cron() {
    return ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || defined( 'DOING_CRON' );
  }

  /**
   * Is rest
   *
   * @return bool
   */
  public static function is_rest() {
    return defined( 'REST_REQUEST' );
  }

  /**
   * Is frontend
   *
   * @return bool
   */
  public static function is_frontend() {
    return ( ! self::is_admin_backend() || ! self::is_ajax() ) && ! self::is_cron() && ! self::is_rest();
  }

	/**
	 * Initialize the session class.
	 *
	 * @return void
	 */
	public function initialize_session() {
    global $wp_ulike_session;
		// Session class, handles session data for users - can be overwritten if custom handler is needed.
		$session_class = apply_filters( 'wp_ulike_session_handler', 'wp_ulike_session_handler' );
		if ( is_null( $wp_ulike_session ) || ! $wp_ulike_session instanceof $session_class ) {
			$wp_ulike_session = new $session_class();
			$wp_ulike_session->init();
		}
	}

  /**
   * Get Client IP address
   *
   * @return   String
  */
  public function get_ip() {
    _deprecated_function( 'get_ip', '4.2.7', 'wp_ulike_get_user_ip' );
    // Get user IP
    return wp_ulike_get_user_ip();
  }

  /**
   * Plugin redirect after activation
   *
   * @param string $plugin
   * @return void
   */
  public function after_activation( $plugin ) {
    if( $plugin == WP_ULIKE_BASENAME ) {
      // Redirect to the about page
      if( wp_safe_redirect( admin_url( 'admin.php?page=wp-ulike-about' ) ) ) exit;
    }
  }

  /**
   * Load the plugin text domain for translation.
   *
   * @return void
   */
  public function load_plugin_textdomain() {
    // Set filter for language directory
    $lang_dir = WP_ULIKE_DIR . 'languages/';
    $lang_dir = apply_filters( 'wp_ulike_languages_directory', $lang_dir );

    // Traditional WordPress plugin locale filter
    $locale = apply_filters( 'plugin_locale', get_locale(), WP_ULIKE_SLUG );
    $mofile = sprintf( '%1$s-%2$s.mo', WP_ULIKE_SLUG, $locale );

    // Setup paths to current locale file
    $mofile_local   = $lang_dir . $mofile;
    $mofile_global  = WP_LANG_DIR . '/plugins/' . WP_ULIKE_SLUG . '/' . $mofile;

    if( file_exists( $mofile_global ) ) {
      // Look in global /wp-content/languages/plugins/wp-ulike/ folder
      load_textdomain( WP_ULIKE_SLUG, $mofile_global );
    } elseif( file_exists( $mofile_local ) ) {
      // Look in local /wp-content/plugins/wp-ulike/languages/ folder
      load_textdomain( WP_ULIKE_SLUG, $mofile_local );
    } else {
      // Load the default language files
      load_plugin_textdomain( WP_ULIKE_SLUG, false, $lang_dir );
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

}

/**
 * Start WP ULike service
 *
 * @return void
 */
function RUN_WPULIKE(){
  WpUlikeInit::get_instance();
}
RUN_WPULIKE();