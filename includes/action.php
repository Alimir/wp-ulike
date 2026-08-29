<?php
/**
 * WP ULIKE Register Hook CLASS
 *
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'wp_ulike_register_action_hook' ) ) :

  class wp_ulike_register_action_hook {

    /**
     * Instance of this class.
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin
     *
     * @since     1.0.0
     */
    private function __construct() {
      add_action( 'wp_initialize_site', array( $this, 'activate_initialized_site' ), 20, 1 );
    }


    /**
    * Fired when the plugin is activated.
    *
    * @param    boolean    $network_wide    True if WPMU superadmin uses
    *                                       "Network Activate" action, false if
    *                                       WPMU is disabled or plugin is
    *                                       activated on an individual blog.
    */
    public static function activate( $network_wide ) {
      $wizard_file = WP_ULIKE_INC_DIR . '/classes/class-wp-ulike-setup-wizard.php';
      if ( is_readable( $wizard_file ) ) {
        require_once $wizard_file;
      }

      if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
        self::for_each_site( array( __CLASS__, 'single_activate' ) );
      } else {
        self::single_activate();
      }
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate( $network_wide ) {
      if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
        self::for_each_site( array( __CLASS__, 'single_deactivate' ) );
      } else {
        self::single_deactivate();
      }
    }

    /**
     * Fired for each blog when the plugin is activated.
     */
    private static function single_activate() {
      wp_ulike_activator::get_instance()->activate();

      $is_fresh_install = false;

      if ( ! get_option( 'wp_ulike_first_activated_at', false ) ) {
        $had_settings = ( false !== get_option( 'wp_ulike_settings', false ) );
        self::seed_fresh_install_settings();
        update_option( 'wp_ulike_first_activated_at', time(), false );
        $is_fresh_install = ! $had_settings;
      }

      if ( $is_fresh_install && class_exists( 'WP_Ulike_Setup_Wizard' ) ) {
        WP_Ulike_Setup_Wizard::mark_pending();
      }

      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        WP_Ulike_Activation_Pointer::flag_for_current_user();
      }

      // Fire action
      do_action( 'wp_ulike_activated', get_current_blog_id() );
    }

    /**
     * Defaults that should apply only to brand-new installs.
     * Existing sites keep historical unlike (unlimited toggle) until they change it.
     *
     * @return void
     */
    private static function seed_fresh_install_settings() {
      if ( false !== get_option( 'wp_ulike_settings', false ) ) {
        return;
      }

      $unlike_once = array( 'unlike_rule' => 'once' );

      // Not autoloaded, deliberately, and matching every other write to this
      // option (settings save, import, setup wizard). A configured site stores
      // 17-30 KB here, which is far too much to unserialize on every request
      // -- including the many that never render a button. wp_ulike_get_option()
      // memoises it, so a request that does need it still pays one lookup.
      add_option(
        'wp_ulike_settings',
        array(
          'posts_group'      => $unlike_once,
          'comments_group'   => $unlike_once,
          'buddypress_group' => $unlike_once,
          'bbpress_group'    => $unlike_once,
        ),
        '',
        'no'
      );
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     */
    private static function single_deactivate() {
      wp_ulike_deactivator::deactivate();

      // Fire action
      do_action( 'wp_ulike_deactivated' );
    }

    /**
     * New network site. Requires WordPress 6.0+ (`wp_initialize_site`).
     *
     * @param WP_Site $new_site New site.
     * @return void
     */
    public function activate_initialized_site( $new_site ) {
      if ( ! is_multisite() || ! self::is_network_active() ) {
        return;
      }

      $blog_id = ( $new_site instanceof WP_Site ) ? (int) $new_site->blog_id : 0;

      if ( $blog_id < 1 ) {
        return;
      }

      switch_to_blog( $blog_id );
      try {
        if ( false === get_option( 'wp_ulike_dbVersion', false ) ) {
          self::single_activate();
        }
      } finally {
        restore_current_blog();
      }
    }

    /**
     * Whether this plugin is network-activated.
     *
     * New-site activation must not run when WP ULike is only on one blog.
     *
     * @return bool
     */
    private static function is_network_active() {
      if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }

      return is_plugin_active_for_network( WP_ULIKE_BASENAME );
    }

    /**
     * Run a callback on every live site in the current network.
     *
     * Uses get_sites() (WP 4.6+), scoped to this network, paged, ordered by
     * id. switch_to_blog() is always paired with restore_current_blog() —
     * never "switch back" with a second switch_to_blog().
     *
     * @param callable $callback No-arg callback, runs in that site's context.
     * @return void
     */
    private static function for_each_site( $callback ) {
      $page     = 0;
      $per_page = 100;
      $network  = get_current_network_id();

      do {
        $site_ids = get_sites(
          array(
            'number'     => $per_page,
            'offset'     => $page * $per_page,
            'network_id' => $network,
            'archived'   => 0,
            'spam'       => 0,
            'deleted'    => 0,
            'fields'     => 'ids',
            'orderby'    => 'id',
            'order'      => 'ASC',
          )
        );

        if ( empty( $site_ids ) ) {
          break;
        }

        foreach ( $site_ids as $blog_id ) {
          switch_to_blog( (int) $blog_id );
          try {
            call_user_func( $callback );
          } finally {
            restore_current_blog();
          }
        }

        ++$page;
      } while ( count( $site_ids ) === $per_page );
    }

    /**
    * Return an instance of this class.
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

endif;

wp_ulike_register_action_hook::get_instance();