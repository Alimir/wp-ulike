<?php
/**
 * WP ULike Admin Pages Class.
 *
 * Handles registration of admin menus and submenus for WP ULike plugin.
 *
 * @package WP_ULike
 * @since 4.6.0
 */

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'wp_ulike_admin_pages' ) ) {
	/**
	 * Class to register admin menus and submenus.
	 *
	 * @since 4.6.0
	 */
	class wp_ulike_admin_pages {

		/**
		 * Menu position constant.
		 *
		 * @since 4.6.0
		 * @var int
		 */
		const MENU_POSITION = 90;

		/**
		 * Submenus array.
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private $submenus = array();

		/**
		 * Views array mapping hook suffixes to template paths.
		 *
		 * @since 4.6.0
		 * @var array
		 */
		private $views = array();

		/**
		 * Constructor.
		 *
		 * @since 4.6.0
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'menus' ) );
		}

		/**
		 * Register admin menus and submenus.
		 *
		 * @since 4.6.0
		 * @return void
		 */
		public function menus() {
			// Apply filter to get submenus - moved here so filters registered in admin-hooks.php are available.
			$this->submenus = apply_filters(
				'wp_ulike_admin_pages',
				$this->get_default_submenus()
			);

			// Register parent menu.
			$parent_hook = $this->register_parent_menu();

			// Register settings submenu.
			$this->register_settings_submenu( $parent_hook );

			// Register filtered submenus.
			$this->register_submenus();

			// Add menu badge if needed.
			$this->menu_badge();
		}

		/**
		 * Get default submenus configuration.
		 *
		 * @since 4.6.0
		 * @return array Default submenus array.
		 */
		private function get_default_submenus() {
			return array(
				'customize'  => array(
					'title'       => esc_html__( 'Customize', 'wp-ulike' ),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => 'manage_options', // Same as settings, requires manage_options.
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/optiwich.php',
					'menu_slug'   => 'wp-ulike-customize',
				),
				'statistics' => array(
					'title'       => esc_html__( 'Statistics', 'wp-ulike' ),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => 'stats', // Store capability type, will be resolved in register_submenus().
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/statistics.php',
					'menu_slug'   => 'wp-ulike-statistics',
				),
				'about'      => array(
					'title'       => sprintf(
						'<span class="wp-ulike-menu-icon"><span class="dashicons dashicons-info"></span> %s</span>',
						esc_html__( 'About', 'wp-ulike' )
					),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => '', // Empty means always visible, no capability check needed.
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/about.php',
					'menu_slug'   => 'wp-ulike-about',
				),
			);
		}

		/**
		 * Register parent menu page.
		 *
		 * @since 4.6.0
		 * @return string|false The resulting page's hook_suffix, or false on failure.
		 */
		private function register_parent_menu() {
			$capability = $this->get_menu_capability( 'stats' );
			$page_title = apply_filters( 'wp_ulike_plugin_name', esc_html__( 'WP ULike', 'wp-ulike' ) );
			$menu_title = apply_filters( 'wp_ulike_plugin_name', esc_html__( 'WP ULike', 'wp-ulike' ) );
			$menu_icon  = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMjUgMjUiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDI1IDI1OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PHBhdGggY2xhc3M9InN0MCIgZD0iTTIzLjksNy4xTDIzLjksNy4xYy0xLjUtMS41LTMuOS0xLjUtNS40LDBsLTEuNSwxLjVsMS40LDEuNGwxLjUtMS41YzAuNC0wLjQsMC44LTAuNiwxLjMtMC42YzAuNSwwLDEuMSwwLjIsMS40LDAuNmMwLjcsMC44LDAuNywyLTAuMSwyLjdsLTEsMWMtMC41LDAuNS0xLjIsMC41LTEuNiwwYy0wLjktMC45LTUuMS01LjEtNS4xLTUuMWMtMC43LTAuNy0xLjctMS4xLTIuNy0xLjFsMCwwYy0xLDAtMiwwLjQtMi43LDEuMUM5LDcuNCw4LjgsNy43LDguNiw4LjFMOC41LDguM2wxLjYsMS42bDAuMS0wLjVjMC4yLTEsMS4yLTEuNywyLjMtMS41YzAuNCwwLjEsMC43LDAuMiwxLDAuNWw1LjksNS45TDE2LjYsMTdMMTIuNywxM2wwLDBjLTAuMS0wLjEtMC40LTAuNC0yLjEtMi4xbC00LTRDNSw1LjQsMi42LDUuNCwxLjEsNi45Yy0xLjUsMS41LTEuNSwzLjksMCw1LjRsNiw2YzAuMywwLjMsMC44LDAuNSwxLjIsMC41bDAsMGMwLjUsMCwwLjktMC4yLDEuMi0wLjVsMi41LTIuNWwtMS40LTEuNGwtMi40LDIuNGwtNS45LTUuOWMtMC43LTAuOC0wLjctMiwwLjEtMi43YzAuNy0wLjcsMS45LTAuNywyLjYsMGw0LDRjMC4xLDAuMSwwLjEsMC4yLDAuMiwwLjJsNiw2YzAuMywwLjMsMC44LDAuNSwxLjMsMC41YzAsMCwwLDAsMCwwYzAuNSwwLDAuOS0wLjIsMS4yLTAuNWw2LTZDMjUuNCwxMSwyNS40LDguNiwyMy45LDcuMXoiLz48L3N2Zz4=';

			$parent_hook = add_menu_page(
				$page_title,
				$menu_title,
				$capability,
				'wp-ulike-settings',
				array( $this, 'load_template' ),
				$menu_icon,
				self::MENU_POSITION
			);

			// Store parent menu template path.
			if ( $parent_hook ) {
				$this->views[ $parent_hook ] = WP_ULIKE_ADMIN_DIR . '/includes/templates/optiwich.php';
			}

			return $parent_hook;
		}

		/**
		 * Register settings submenu page.
		 *
		 * Settings panel requires manage_options capability for security.
		 *
		 * @since 4.6.0
		 * @param string|false $parent_hook Parent menu hook suffix.
		 * @return void
		 */
		private function register_settings_submenu( $parent_hook ) {
			if ( ! $parent_hook ) {
				return;
			}

			// Settings panel requires manage_options capability for security.
			$capability = 'manage_options';

			// Replace auto-generated first submenu with our own to avoid duplicate.
			$settings_submenu_hook = add_submenu_page(
				'wp-ulike-settings',
				esc_html__( 'WP ULike Settings', 'wp-ulike' ),
				esc_html__( 'Settings', 'wp-ulike' ),
				$capability,
				'wp-ulike-settings',
				array( $this, 'load_template' )
			);

			// Store settings submenu template path (same as parent).
			if ( $settings_submenu_hook ) {
				$this->views[ $settings_submenu_hook ] = WP_ULIKE_ADMIN_DIR . '/includes/templates/optiwich.php';
			}
		}

		/**
		 * Register filtered submenus.
		 *
		 * @since 4.6.0
		 * @return void
		 */
		private function register_submenus() {
			foreach ( $this->submenus as $key => $args ) {
				$this->register_single_submenu( $args );
			}
		}

		/**
		 * Register a single submenu page.
		 *
		 * @since 4.6.0
		 * @param array $args Submenu arguments.
		 * @return string|false The resulting page's hook_suffix, or false on failure.
		 */
		private function register_single_submenu( $args ) {
			// Extract variables explicitly per WordPress coding standards.
			$parent_slug = isset( $args['parent_slug'] ) ? $args['parent_slug'] : '';
			$title       = isset( $args['title'] ) ? $args['title'] : '';
			$menu_slug   = isset( $args['menu_slug'] ) ? $args['menu_slug'] : '';
			$path        = isset( $args['path'] ) ? $args['path'] : '';

			// Resolve capability.
			$capability = $this->resolve_capability( $args );

			// Validate required arguments.
			if ( empty( $parent_slug ) || empty( $menu_slug ) ) {
				return false;
			}

			$hook_suffix = add_submenu_page(
				$parent_slug,
				$title,
				apply_filters( 'wp_ulike_admin_sub_menu_title', $title, $menu_slug ),
				$capability,
				$menu_slug,
				array( $this, 'load_template' )
			);

			// Store template path.
			if ( $hook_suffix && ! empty( $path ) ) {
				$this->views[ $hook_suffix ] = $path;
			}

			return $hook_suffix;
		}

		/**
		 * Resolve capability from arguments.
		 *
		 * Only resolves custom capability types we recognize (like 'stats', 'logs').
		 * Empty string means always visible (no capability check).
		 * Otherwise uses the capability as-is.
		 *
		 * @since 4.6.0
		 * @param array $args Menu arguments.
		 * @return string Resolved capability.
		 */
		private function resolve_capability( $args ) {
			$capability_arg = isset( $args['capability'] ) ? $args['capability'] : 'manage_options';

			// Empty capability means always visible.
			if ( empty( $capability_arg ) ) {
				return 'read'; // Use 'read' capability which all logged-in users have.
			}

			// Recognized custom capability types that should use wp_ulike_get_user_access_capability.
			$recognized_types = array( 'stats', 'logs' );

			// If it's a recognized capability type, resolve it using our method.
			if ( is_string( $capability_arg ) && in_array( $capability_arg, $recognized_types, true ) && function_exists( 'wp_ulike_get_user_access_capability' ) ) {
				return wp_ulike_get_user_access_capability( $capability_arg );
			}

			// Otherwise use the capability as-is (like 'manage_options').
			return $capability_arg;
		}

		/**
		 * Get menu capability.
		 *
		 * Resolves capability based on type using wp_ulike_get_user_access_capability.
		 * Only for recognized custom types like 'stats'.
		 *
		 * @since 4.6.0
		 * @param string $capability_type Capability type ('stats', 'logs', etc.).
		 * @return string Resolved capability string for WordPress menu system.
		 */
		private function get_menu_capability( $capability_type ) {
			// Recognized custom capability types.
			$recognized_types = array( 'stats', 'logs' );

			if ( in_array( $capability_type, $recognized_types, true ) && function_exists( 'wp_ulike_get_user_access_capability' ) ) {
				$capability = wp_ulike_get_user_access_capability( $capability_type );
				// Ensure we always return a valid capability string.
				if ( ! empty( $capability ) && is_string( $capability ) ) {
					return $capability;
				}
			}

			// Fallback to manage_options for security (requires administrator role).
			return 'manage_options';
		}

		/**
		 * Add custom badges to a menu name.
		 *
		 * Finds the menu item by menu slug instead of position number for better reliability.
		 *
		 * @since 4.6.0
		 * @return void
		 */
		public function menu_badge() {
			global $menu;

			if ( ! isset( $menu ) || ! is_array( $menu ) || ! function_exists( 'wp_ulike_badge_count_format' ) ) {
				return;
			}

			$badge_count = apply_filters( 'wp_ulike_menu_badge_count', 0 );

			if ( 0 === $badge_count ) {
				return;
			}

			// Find menu item by slug instead of position number.
			foreach ( $menu as $key => $menu_item ) {
				if ( isset( $menu_item[2] ) && 'wp-ulike-settings' === $menu_item[2] ) {
					$menu[ $key ][0] .= wp_ulike_badge_count_format( $badge_count );
					break;
				}
			}
		}

		/**
		 * Load admin templates.
		 *
		 * @since 4.6.0
		 * @return void
		 */
		public function load_template() {
			$current_filter = current_filter();

			if ( empty( $current_filter ) || ! isset( $this->views[ $current_filter ] ) ) {
				return;
			}

			$template_path = $this->views[ $current_filter ];

			// Validate template file exists.
			if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
				load_template( $template_path );
			}
		}

	}

}