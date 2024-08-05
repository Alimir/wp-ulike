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
	  	function __construct() {
			// general assets
        	add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	  	}

		public function enqueue( $hook ){
			$this->hook = $hook;
			// general assets
			$this->load_styles();
			$this->load_statistics_app();
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
				WP_ULIKE_ADMIN_URL . '/assets/css/admin.css',
				array(),
				WP_ULIKE_VERSION
			);

			// Scripts is only can be load on ulike pages.
			if ( strpos( $this->hook, WP_ULIKE_SLUG ) === false ) {
				return;
			}

			// Enqueue third-party styles
			wp_enqueue_style(
				'wp-ulike-admin-plugins',
				WP_ULIKE_ADMIN_URL . '/assets/css/plugins.css',
				array(),
				WP_ULIKE_VERSION
			);
		}


		function load_statistics_app(){
			// only load on stats menu
			if ( ! defined( 'WP_ULIKE_PRO_DOMAIN' ) &&  strpos( $this->hook, WP_ULIKE_SLUG ) !== false && preg_match("/(statistics)/i", $this->hook ) ) {
				$manifest_path = WP_ULIKE_ADMIN_DIR . '/includes/statistics/asset-manifest.json';

				if (!file_exists($manifest_path)) {
					return;
				}

				$manifest = json_decode(file_get_contents($manifest_path), true);

				if (!$manifest) {
					return;
				}

				// Enqueue the CSS file
				if (isset($manifest['files']['main.css'])) {
					$css_file = WP_ULIKE_ADMIN_URL . '/includes/statistics' . $manifest['files']['main.css'];
					wp_enqueue_style('wp_ulike_admin_react', $css_file);
				}

				// Enqueue the JS file
				if (isset($manifest['files']['main.js'])) {
					$js_file = WP_ULIKE_ADMIN_URL . '/includes/statistics' . $manifest['files']['main.js'];
					wp_enqueue_script('wp_ulike_admin_react', $js_file, array(), null, true);
				}
			}
		}

	}

}