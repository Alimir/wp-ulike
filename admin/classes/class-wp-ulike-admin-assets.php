<?php
/**
 * Wp ULike Admin Scripts Class.
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_admin_assets' ) ) {
	/**
	 *  Class to load and print the admin panel scripts
	 */
	class wp_ulike_admin_assets {

		private $hook;

		/**
		 * __construct
		 */
		function __construct( $hook ) {
			$this->hook = $hook;
			// general assets
			$this->load_styles();
			$this->load_scripts();
		 }


		/**
		 * Styles for admin
		 *
		 * @return void
		 */
		public function load_styles() {
			// Enqueue admin styles
			wp_enqueue_style(
				'wp-ulike-admin',
				WP_ULIKE_ADMIN_URL . '/assets/css/admin.css'
			);

			// Scripts is only can be load on ulike pages.
			if ( strpos( $this->hook, WP_ULIKE_SLUG ) === false ) {
				return;
			}

			// Enqueue third-party styles
			wp_enqueue_style(
				'wp-ulike-admin-plugins',
				WP_ULIKE_ADMIN_URL . '/assets/css/plugins.css'
			);

		}

	    /**
	     * Scripts for admin
	     *
	     * @return void
	     */
		public function load_scripts() {

			// Scripts is only can be load on ulike pages.
			if ( strpos( $this->hook, WP_ULIKE_SLUG ) === false ) {
				return;
			}

			// Remove all notices in wp ulike pages.
			// remove_all_actions( 'admin_notices' );

			// Enqueue vueJS
	        // @if DEV
	        /*
	        // @endif
			wp_enqueue_script(
				'wp_ulike_vuejs',
				WP_ULIKE_ADMIN_URL . '/assets/js/solo/vue/vue.min.js',
				array(),
				null,
				false
			);
	        // @if DEV
	        */
	        // @endif
	        // @if DEV
			wp_enqueue_script(
				'wp_ulike_vuejs',
				WP_ULIKE_ADMIN_URL . '/assets/js/solo/vue/vue.js',
				array(),
				false,
				false
			);
	        // @endif

			// Enqueue admin plugins
			wp_enqueue_script(
				'wp_ulike_admin_plugins',
				WP_ULIKE_ADMIN_URL . '/assets/js/plugins.js',
				array( 'jquery' ),
				false,
				true
			);

			// Enqueue admin scripts
			wp_enqueue_script(
				'wp_ulike_admin_scripts',
				WP_ULIKE_ADMIN_URL . '/assets/js/scripts.js',
				array( 'wp_ulike_admin_plugins', 'wp_ulike_vuejs'),
				false,
				true
			);

			// Localize scripts
			wp_localize_script( 'wp_ulike_admin_scripts', 'wp_ulike_admin', array(
				'hook_address'    => esc_html( $this->hook ),
				'nonce_field'     => wp_create_nonce( 'wp-ulike-ajax-nonce' ),
				'logs_notif'      => __('Are you sure to remove this item?!',WP_ULIKE_SLUG),
				'not_found_notif' => __('No information was found in this database!',WP_ULIKE_SLUG),
				'spinner'         => admin_url( 'images/spinner.gif' )
			));

		}

	}

}