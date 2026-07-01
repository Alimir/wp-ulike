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
      add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
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

    /**
     * Fired when the plugin is deactivated.
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
     */
    private static function single_activate() {
      wp_ulike_activator::get_instance()->activate();

      if ( ! get_option( 'wp_ulike_first_activated_at', false ) ) {
        update_option( 'wp_ulike_first_activated_at', time(), false );
      }

      WP_Ulike_Activation_Pointer::flag_for_current_user();

      // Fire action
      do_action( 'wp_ulike_activated', get_current_blog_id() );
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
     * Fired when a new site is activated with a WPMU environment.
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