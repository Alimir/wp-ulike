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
				'posts_logs'      => array(
					'title'       => esc_html__( 'Post Likes Logs', 'wp-ulike' ),
					'parent_slug' => '',
					'capability'  => wp_ulike_get_user_access_capability('logs'),
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/posts-logs.php',
					'menu_slug'   => 'wp-ulike-posts-logs',
					'load_screen' => true
				),
				'comments_logs'   => array(
					'title'       => esc_html__( 'Comment Likes Logs', 'wp-ulike' ),
					'parent_slug' => '',
					'capability'  => wp_ulike_get_user_access_capability('logs'),
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/comments-logs.php',
					'menu_slug'   => 'wp-ulike-comments-logs',
					'load_screen' => true
				),
				'activities_logs' =>  array(
					'title'       => esc_html__( 'Activity Likes Logs', 'wp-ulike' ),
					'parent_slug' => '',
					'capability'  => wp_ulike_get_user_access_capability('logs'),
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/activities-logs.php',
					'menu_slug'   => 'wp-ulike-activities-logs',
					'load_screen' => true
				),
				'topics_logs'     => array(
					'title'       => esc_html__( 'Topics Likes Logs', 'wp-ulike' ),
					'parent_slug' =>  '',
					'capability'  => wp_ulike_get_user_access_capability('logs'),
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/topics-logs.php',
					'menu_slug'   => 'wp-ulike-topics-logs',
					'load_screen' => true
				),
				'statistics'      => array(
					'title'       => esc_html__( 'Statistics', 'wp-ulike' ),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => wp_ulike_get_user_access_capability('stats'),
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/statistics.php',
					'menu_slug'   => 'wp-ulike-statistics',
					'load_screen' => false
				),
				'about'           => array(
					'title'       => esc_html__( 'About', 'wp-ulike' ),
					'parent_slug' => 'wp-ulike-settings',
					'capability'  => wp_ulike_get_user_access_capability('stats'),
					'path'        => WP_ULIKE_ADMIN_DIR . '/includes/templates/about.php',
					'menu_slug'   => 'wp-ulike-about',
					'load_screen' => false
				)
			) );

			add_action( 'wp_ulike_settings_loaded', function(){
				add_action( 'admin_menu', array( $this, 'menus' ) );
			} );

		}

		/**
		 * register admin menus
		 *
		 * @return void
		 */
		public function menus() {

			// Register submenus
			foreach ( $this->submenus as $key => $args) {
				// extract variables
				extract( $args );

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