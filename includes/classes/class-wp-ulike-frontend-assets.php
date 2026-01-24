<?php
/**
 * Wp ULike FrontEnd Scripts Class.
 * // @echo HEADER
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'wp_ulike_frontend_assets' ) ) {
	/**
	 *  Class to load and print the front-end scripts
	 */
	class wp_ulike_frontend_assets {

	  	private $hook;

	  	/**
	   	 * __construct
	   	 */
	  	function __construct() {
			// general assets
        	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	  	}

		public function enqueue(){
	    	// If user has been disabled this page in options, then return.
			if( ! is_wp_ulike( wp_ulike_get_option( 'disable_plugin_files' ), array(), true ) ) {
				return;
			}
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

	        // @if DEV
	        /*
	        // @endif
	        wp_enqueue_style( WP_ULIKE_SLUG, WP_ULIKE_ASSETS_URL . '/css/wp-ulike.min.css', array(), WP_ULIKE_VERSION );
	        // @if DEV
	        */
	        // @endif
	        // @if DEV
			wp_enqueue_style( WP_ULIKE_SLUG, WP_ULIKE_ASSETS_URL . '/css/wp-ulike.css', array(), WP_ULIKE_VERSION );
			// @endif

		// Check user preference for CSS delivery method
		$user_prefers_inline = wp_ulike_is_true( wp_ulike_get_option( 'enable_inline_custom_css', false ) );
		$directory_not_writable = wp_ulike_is_true( get_option( 'wp_ulike_use_inline_custom_css', true ) );
		error_log($user_prefers_inline);
		error_log($directory_not_writable);
		// Use inline CSS if user prefers it OR directory is not writable (fallback)
		if( $user_prefers_inline || $directory_not_writable ){
			//add your custom style from setting panel (now includes customizer CSS).
			wp_add_inline_style( WP_ULIKE_SLUG, wp_ulike_get_custom_style() );
		} else {
			wp_enqueue_style( WP_ULIKE_SLUG . '-custom', WP_ULIKE_CUSTOM_URL . '/custom.css', array( WP_ULIKE_SLUG ), WP_ULIKE_VERSION );
		}

	  	}

	    /**
	     * Scripts for admin
	     *
	     * @return void
	     */
	  	public function load_scripts() {
			// Return if pro assets exist (Pro >= 1.5.3 includes free scripts, so don't load free version)
			if( defined( 'WP_ULIKE_PRO_VERSION' ) && version_compare( WP_ULIKE_PRO_VERSION, '1.5.3', '>=' ) ){
				return;
			}

			// @if DEV
			/*
			// @endif
			//Add wp_ulike script file with special functions.
			wp_enqueue_script( 'wp_ulike', WP_ULIKE_ASSETS_URL . '/js/wp-ulike.min.js', array(), WP_ULIKE_VERSION, true );
			// @if DEV
			*/
			// @endif
			// @if DEV
			//Add wp_ulike script file with special functions.
			wp_enqueue_script( 'wp_ulike', WP_ULIKE_ASSETS_URL . '/js/wp-ulike.js', array(), WP_ULIKE_VERSION, true );
			// @endif

			//localize script
			wp_localize_script( 'wp_ulike', 'wp_ulike_params', array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'notifications' => wp_ulike_get_option( 'enable_toast_notice' )
			));
	  	}


	}

}