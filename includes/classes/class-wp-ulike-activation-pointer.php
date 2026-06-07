<?php
/**
 * Welcome pointer on the WP ULike admin menu after activation.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Activation_Pointer' ) ) {

	/**
	 * Shows a one-time getting-started pointer on the site admin menu.
	 */
	class WP_Ulike_Activation_Pointer {

		const USER_META_KEY   = 'wp_ulike_show_activation_pointer';
		const AJAX_ACTION     = 'wp_ulike_dismiss_activation_pointer';
		const SCRIPT_HANDLE   = 'wp-ulike-activation-pointer';
		const STYLE_HANDLE    = 'wp-ulike-activation-pointer';
		const MENU_SELECTOR   = '#toplevel_page_wp-ulike-settings';

		/**
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( __CLASS__, 'render_template' ) );
			add_action( 'network_admin_notices', array( __CLASS__, 'maybe_network_notice' ) );
			add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_dismiss' ) );
		}

		/**
		 * Flag the activating user to see the welcome pointer on their next site admin visit.
		 *
		 * @return void
		 */
		public static function flag_for_current_user() {
			$user_id = get_current_user_id();

			if ( ! $user_id || ! self::user_has_menu_capability( $user_id ) ) {
				return;
			}

			update_user_meta( $user_id, self::USER_META_KEY, '1' );
		}

		/**
		 * @param int $user_id User ID.
		 * @return bool
		 */
		protected static function user_has_menu_capability( $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( ! $user_id ) {
				return false;
			}

			return user_can( $user_id, 'manage_options' ) || user_can( $user_id, wp_ulike_get_user_access_capability( 'stats' ) );
		}

		/**
		 * @param int $user_id User ID.
		 * @return bool
		 */
		protected static function is_pointer_pending( $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( ! $user_id ) {
				return false;
			}

			return '1' === get_user_meta( $user_id, self::USER_META_KEY, true );
		}

		/**
		 * @return bool
		 */
		protected static function should_show_on_screen() {
			if ( wp_doing_ajax() || is_network_admin() ) {
				return false;
			}

			if ( ! self::is_pointer_pending() || ! self::user_has_menu_capability() ) {
				return false;
			}

			return true;
		}

		/**
		 * @param string $hook Admin hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( ! self::should_show_on_screen() ) {
				return;
			}

			$css_path = WP_ULIKE_ADMIN_DIR . '/assets/css/activation-pointer.css';
			$js_path  = WP_ULIKE_ADMIN_DIR . '/assets/js/activation-pointer.js';
			$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : WP_ULIKE_VERSION;
			$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : WP_ULIKE_VERSION;

			wp_enqueue_style(
				self::STYLE_HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/css/activation-pointer.css',
				array(),
				$css_ver
			);

			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/js/activation-pointer.js',
				array(),
				$js_ver,
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'wpUlikeActivationPointer',
				array(
					'menuSelector' => self::MENU_SELECTOR,
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'action'       => self::AJAX_ACTION,
					'nonce'        => wp_create_nonce( self::AJAX_ACTION ),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function render_template() {
			if ( ! self::should_show_on_screen() ) {
				return;
			}

			$template = WP_ULIKE_ADMIN_DIR . '/includes/templates/activation-pointer-dialog.php';

			if ( is_readable( $template ) ) {
				include $template;
			}
		}

		/**
		 * Network admin has no WP ULike menu — guide users to a site dashboard.
		 *
		 * @return void
		 */
		public static function maybe_network_notice() {
			if ( ! is_multisite() || ! is_network_admin() || ! self::is_pointer_pending() || ! self::user_has_menu_capability() ) {
				return;
			}

			$dashboard_url = self::get_site_dashboard_url();

			printf(
				'<div class="notice notice-info is-dismissible"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
				esc_html__( 'WP ULike is active on your network.', 'wp-ulike' ),
				esc_html__( 'Open a site dashboard to see the getting-started guide next to the WP ULike menu.', 'wp-ulike' ),
				esc_url( $dashboard_url ),
				esc_html__( 'Go to site dashboard', 'wp-ulike' )
			);
		}

		/**
		 * @return string
		 */
		protected static function get_site_dashboard_url() {
			$user_id = get_current_user_id();

			if ( $user_id && function_exists( 'get_dashboard_url' ) ) {
				return get_dashboard_url( $user_id );
			}

			if ( is_multisite() ) {
				return get_admin_url( get_main_site_id() );
			}

			return admin_url( 'index.php' );
		}

		/**
		 * @return void
		 */
		public static function ajax_dismiss() {
			check_ajax_referer( self::AJAX_ACTION, 'nonce' );

			if ( ! self::user_has_menu_capability() ) {
				wp_send_json_error();
			}

			delete_user_meta( get_current_user_id(), self::USER_META_KEY );

			wp_send_json_success();
		}
	}

	WP_Ulike_Activation_Pointer::init();
}
