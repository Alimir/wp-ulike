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
			// only load on settings menu page or customizer
			if ( strpos( $this->hook, WP_ULIKE_SLUG ) !== false && preg_match("/(settings|customize)/i", $this->hook )  ) {
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

				// Get translations from settings API
				$translations = array();
				if ( class_exists( 'wp_ulike_settings_api' ) ) {
					$settings_api = new wp_ulike_settings_api();
					$translations = $settings_api->get_translations();
				}

				// Pass the app config to the frontend
				wp_localize_script( 'wp-ulike-optiwich', 'OptiwichConfig', array(
					'nonce'     => wp_create_nonce( WP_ULIKE_SLUG ),
					'title'     => WP_ULIKE_NAME,
					'logo'      => WP_ULIKE_ASSETS_URL . '/img/wp-ulike-logo.svg',
					'slug'      => WP_ULIKE_SLUG,
					'loaderSvg' => '<svg width="386" height="204" viewBox="0 0 386 204" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M368.646 20.3446C345.509 -2.76261 308.086 -2.76261 285.149 20.3446L261.609 43.8543L282.735 64.9524L305.671 41.8451C311.104 36.4198 318.347 33.2045 325.993 32.8027C334.242 32.6018 342.289 35.817 347.923 41.8451C358.989 53.6998 358.587 71.985 346.917 83.2376L331.827 98.3075C324.785 105.34 313.518 105.34 306.476 98.3075C292.794 84.644 228.008 19.9428 228.008 19.9428C216.943 8.89117 202.054 2.66214 186.159 2.66214C170.465 2.66214 155.577 8.89117 144.31 19.9428C139.682 24.5645 135.658 29.9898 132.842 36.0179L131.634 38.6299L155.979 62.9432L157.589 55.5087C161.009 39.6345 176.501 29.588 192.396 33.0036C198.03 34.2091 203.06 36.8216 207.084 40.8399L297.623 131.261L256.177 172.654L195.414 112.172C194.207 110.967 189.177 105.742 163.021 79.4196L100.851 17.3309C77.713 -5.77696 40.2899 -5.77696 17.3535 17.3309C-5.78451 40.4381 -5.78451 77.8122 17.3535 100.719L110.106 193.35C115.136 198.374 121.977 201.187 129.019 201.187C136.262 201.187 142.901 198.374 148.133 193.35L186.763 154.771L165.637 133.673L129.22 170.041L38.6804 79.6205C27.4131 67.9667 27.8155 49.4806 39.4852 38.228C50.7524 27.3773 68.6593 27.3773 79.926 38.228L141.292 99.5131C142.298 100.719 143.505 102.126 144.712 103.331L237.264 195.761C242.294 200.986 249.135 204 256.579 204C256.78 204 256.981 204 256.981 204C264.224 204 270.864 201.187 276.095 196.164L368.646 103.733C391.785 80.8266 391.785 43.2515 368.646 20.3446Z" fill="#FF6D6F"/> </svg>',
					'actions' => array(
						'schema'            => 'wp_ulike_schema_api',
						'settings'          => 'wp_ulike_settings_api',
						'save'              => 'wp_ulike_save_settings_api',
						'customizerSchema'  => 'wp_ulike_customizer_schema_api',
						'customizerValues'  => 'wp_ulike_customizer_values_api',
						'customizerSave'    => 'wp_ulike_save_customizer_api',
						'customizerPreview' => 'wp_ulike_customizer_preview_api'
					),
					'translations' => $translations
				));
			}
		}

	}

}