<?php
/**
 * Wp ULike Admin Pages Class.
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_admin_pages' ) ) {
	/**
	 *  Class to register admin menus
	 */
	class wp_ulike_admin_pages {

		private $submenus, $views;

		/**
		 * __construct
		 */
		function __construct() {

			$this->submenus = apply_filters( 'wp_ulike_admin_pages', array(
				'statistics'      => array(
					'title'       => esc_html__( 'Statistics', 'wp-ulike' ),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => 'stats', // Store capability type, will be resolved in menus()
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/statistics.php',
					'menu_slug'   => 'wp-ulike-statistics',
					'load_screen' => false
				),
				'about'           => array(
					'title'       => sprintf( '<span class="wp-ulike-menu-icon"><span class="dashicons dashicons-info"></span> %s</span>', esc_html__( 'About', 'wp-ulike' ) ),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => 'stats', // Store capability type, will be resolved in menus()
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/about.php',
					'menu_slug'   => 'wp-ulike-about',
					'load_screen' => false
				)
			) );

			add_action( 'admin_menu', array( $this, 'menus' ) );

		}

		/**
		 * register admin menus
		 *
		 * @return void
		 */
		public function menus() {

			// Get capability - now safe to call as admin_menu fires after user is loaded
			$capability = function_exists( 'wp_ulike_get_user_access_capability' ) 
				? wp_ulike_get_user_access_capability('stats') 
				: 'manage_options';

			// Register parent menu
			$parent_hook = add_menu_page(
				esc_html__( 'WP ULike Settings', 'wp-ulike' ),
				esc_html__( 'WP ULike', 'wp-ulike' ),
				$capability,
				'wp-ulike-settings',
				array( &$this, 'load_template' ),
				'dashicons-heart',
				30
			);

			// Store parent menu template path
			$this->views[ $parent_hook ] = WP_ULIKE_ADMIN_DIR . '/includes/templates/optiwich.php';

			// Replace auto-generated first submenu with our own to avoid duplicate
			$settings_submenu_hook = add_submenu_page(
				'wp-ulike-settings',
				esc_html__( 'WP ULike Settings', 'wp-ulike' ),
				esc_html__( 'Settings', 'wp-ulike' ),
				$capability,
				'wp-ulike-settings',
				array( &$this, 'load_template' )
			);

			// Store settings submenu template path (same as parent)
			if ( $settings_submenu_hook ) {
				$this->views[ $settings_submenu_hook ] = WP_ULIKE_ADMIN_DIR . '/includes/templates/optiwich.php';
			}

			// Register submenus
			foreach ( $this->submenus as $key => $args) {
				// Extract variables explicitly per WordPress coding standards
				$parent_slug = isset( $args['parent_slug'] ) ? $args['parent_slug'] : '';
				$title = isset( $args['title'] ) ? $args['title'] : '';
				$menu_slug = isset( $args['menu_slug'] ) ? $args['menu_slug'] : '';
				// Resolve capability if it's a capability type string (like 'stats'), otherwise use as-is
				$capability_arg = isset( $args['capability'] ) ? $args['capability'] : 'manage_options';
				if ( is_string( $capability_arg ) && function_exists( 'wp_ulike_get_user_access_capability' ) && ! empty( $capability_arg ) ) {
					$capability = wp_ulike_get_user_access_capability( $capability_arg );
				} else {
					$capability = $capability_arg;
				}
				$load_screen = isset( $args['load_screen'] ) ? $args['load_screen'] : false;
				$path = isset( $args['path'] ) ? $args['path'] : '';

				$hook_suffix = add_submenu_page(
					$parent_slug,
					$title,
					apply_filters( 'wp_ulike_admin_sub_menu_title', $title, $menu_slug ),
					$capability,
					$menu_slug,
					array( &$this, 'load_template' )
				);

				$this->views[ $hook_suffix ] = $path;

				if( $load_screen ) {
					add_action( "load-$hook_suffix", array( $this, 'add_screen_option' ) );
				}
			}

			$this->menu_badge();
		}

		/**
		 * Add custom badges to a menu name
		 *
		 * @return void
		 */
		public function menu_badge(){
			global $menu;

			if( 0 !== ( $badge_count = apply_filters( 'wp_ulike_menu_badge_count', 0 ) ) ) {
				$menu[313][0] .= wp_ulike_badge_count_format( $badge_count );
			}
		}

		/**
		 * Add screen options
		 *
		 * @return void
		 */
		public function add_screen_option(){
			add_screen_option( 'per_page', array(
					'label'   => esc_html__('Logs','wp-ulike'),
					'default' => 30,
					'option'  => 'wp_ulike_logs_per_page'
				)
			);
		}

		/**
		 * Load admin templates
		 *
		 * @return void
		 */
		public function load_template(){
			load_template( $this->views[ current_filter() ] );
		}

	}

}