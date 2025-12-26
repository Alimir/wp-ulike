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


    $prefix = is_network_admin() ? 'network_admin_' : '';
    add_filter( "{$prefix}plugin_action_links",  array( $this, 'add_links' ), 10, 5 );
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
  }

  private function maybe_upgrade_database(){
    $current_version = get_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
    $activator = wp_ulike_activator::get_instance();

    // Define upgrade path with version and method mapping
    $upgrades = array(
      '2.1' => 'upgrade_0',
      '2.2' => 'upgrade_1',
      '2.3' => 'upgrade_2',
      '2.4' => 'upgrade_3',
    );

    // Execute upgrades sequentially, stopping on failure
    foreach ( $upgrades as $version => $method ) {
      if ( version_compare( $current_version, $version, '<' ) ) {
        $result = $activator->$method();
        if ( false === $result ) {
          if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( 'WP ULike: Database upgrade to version %s failed. Current version: %s', $version, $current_version ) );
          }
          break; // Stop on failure to prevent partial upgrades
        }
        // Update current version after successful upgrade
        $current_version = $version;
      }
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

    // load trasnlations
    $this->load_plugin_textdomain();

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
    // a custom directory in uploads directory for storing custom files. Default uploads/{WP_ULIKE_SLUG}
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
      $settings = array('settings'  => '<a href="admin.php?page=wp-ulike-settings">' . esc_html__('Settings', 'wp-ulike') . '</a>');
      $stats    = array('stats'     => '<a href="admin.php?page=wp-ulike-statistics">' . esc_html__('Statistics', 'wp-ulike') . '</a>');
      $about    = array('about'     => '<a href="admin.php?page=wp-ulike-about">' . esc_html__('About', 'wp-ulike') . '</a>');
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
    return defined( 'REST_REQUEST' ) && REST_REQUEST;
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
		$lang_dir = WP_ULIKE_SLUG . '/languages';
    $lang_dir = apply_filters( 'wp_ulike_languages_directory', $lang_dir );

		$locale   = determine_locale();
    /**
     * Filter to adjust the wp ulike locale to use for translations.
     */
    $locale = apply_filters( 'plugin_locale', $locale, WP_ULIKE_SLUG );

    load_textdomain( WP_ULIKE_SLUG, WP_LANG_DIR . '/' . WP_ULIKE_SLUG . '/' . WP_ULIKE_SLUG . '-' . $locale . '.mo' );
    load_plugin_textdomain( WP_ULIKE_SLUG, false, $lang_dir );
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