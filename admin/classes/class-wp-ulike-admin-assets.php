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
			$this->load_optiwich_app();
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

				// Pass the app config to the frontend
				wp_localize_script( 'wp_ulike_admin_react', 'StatsAppConfig', array(
					'nonce' => wp_create_nonce( WP_ULIKE_SLUG )
				));
			}
		}

		/**
		 * Load Optiwich settings app
		 *
		 * @return void
		 */
		function load_optiwich_app(){
			// only load on settings menu page
			if ( strpos( $this->hook, WP_ULIKE_SLUG ) !== false && preg_match("/(settings)/i", $this->hook )  ) {
				// Enqueue WordPress media library (required for upload fields)
				wp_enqueue_media();

				// Enqueue Optiwich CSS
				wp_enqueue_style(
					'wp-ulike-optiwich',
					WP_ULIKE_ADMIN_URL . '/includes/optiwich/style.css',
					array(),
					WP_ULIKE_VERSION
				);

				// Enqueue Optiwich JS
				wp_enqueue_script(
					'wp-ulike-optiwich',
					WP_ULIKE_ADMIN_URL . '/includes/optiwich/optiwich.umd.js',
					array(),
					WP_ULIKE_VERSION,
					true
				);

				// Pass the app config to the frontend
				wp_localize_script( 'wp-ulike-optiwich', 'OptiwichConfig', array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( WP_ULIKE_SLUG ),
					'title'   => WP_ULIKE_NAME,
					'logo'    => WP_ULIKE_ASSETS_URL . '/img/wp-ulike-logo.svg',
					'slug'    => WP_ULIKE_SLUG,
					'actions' => array(
						'schema'  => 'wp_ulike_schema_api',
						'settings' => 'wp_ulike_settings_api',
						'save'    => 'wp_ulike_save_settings_api'
					)
				));
			}
		}

	}

}