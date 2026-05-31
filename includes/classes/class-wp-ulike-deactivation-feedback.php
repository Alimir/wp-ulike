<?php
/**
 * Deactivation feedback modal on the Plugins screen.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Deactivation_Feedback' ) ) {

	/**
	 * Quick feedback before deactivating WP ULike.
	 */
	class WP_Ulike_Deactivation_Feedback {

		const SCRIPT_HANDLE = 'wp-ulike-deactivation-feedback';

		/**
		 * Reason keys accepted by TWT_Deactivation_Tracker::is_valid_reason_key().
		 *
		 * @return array<string, array<string, string>>
		 */
		public static function get_reasons() {
			$reasons = array(
				'no_longer_need' => array(
					'title'       => __( 'I no longer need the plugin', 'wp-ulike' ),
					'placeholder' => '',
				),
				'found_better'   => array(
					'title'       => __( 'I found a better plugin', 'wp-ulike' ),
					'placeholder' => __( 'Which plugin?', 'wp-ulike' ),
				),
				'not_working'    => array(
					'title'       => __( "I couldn't get the plugin to work", 'wp-ulike' ),
					'placeholder' => '',
				),
				'temporary'      => array(
					'title'       => __( "It's a temporary deactivation", 'wp-ulike' ),
					'placeholder' => '',
				),
				'other'          => array(
					'title'       => __( 'Other', 'wp-ulike' ),
					'placeholder' => __( 'Tell us more (optional)', 'wp-ulike' ),
				),
			);

			return $reasons;
		}

		/**
		 * Deactivation feedback API (TWT audit endpoint).
		 *
		 * @return string
		 */
		public static function get_api_url() {
			return 'http://localhost/wordpress/edd/api/audit/v1/deactivation-feedback';
		}

		/**
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( __CLASS__, 'render_dialog' ) );
			add_action( 'wp_ajax_wp_ulike_deactivation_feedback', array( __CLASS__, 'ajax_send_feedback' ) );
		}

		/**
		 * @param string $hook Admin hook.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( ! in_array( $hook, array( 'plugins.php', 'plugins-network.php' ), true ) ) {
				return;
			}

			$js_path = WP_ULIKE_ADMIN_DIR . '/assets/js/deactivation-feedback.js';
			$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : WP_ULIKE_VERSION;

			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/js/deactivation-feedback.js',
				array(),
				$js_ver,
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'wpUlikeDeactivationFeedback',
				array(
					'slug'        => WP_ULIKE_SLUG,
					'pluginFile'  => WP_ULIKE_BASENAME,
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'pluginsUrl'  => admin_url( 'plugins.php' ),
					'nonce'       => wp_create_nonce( 'wp_ulike_deactivation_feedback' ),
					'i18n'        => array(
						'submit' => __( 'Submit & deactivate', 'wp-ulike' ),
						'skip'   => __( 'Skip & deactivate', 'wp-ulike' ),
					),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function render_dialog() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
				return;
			}

			$template = WP_ULIKE_ADMIN_DIR . '/includes/templates/deactivation-feedback-dialog.php';
			if ( is_readable( $template ) ) {
				include $template;
			}
		}

		/**
		 * @return void
		 */
		public static function ajax_send_feedback() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( null, 403 );
			}

			check_ajax_referer( 'wp_ulike_deactivation_feedback', 'nonce' );

			$reason_key = isset( $_POST['reason_key'] ) ? sanitize_key( wp_unslash( $_POST['reason_key'] ) ) : '';
			$allowed    = array_keys( self::get_reasons() );

			if ( ! in_array( $reason_key, $allowed, true ) ) {
				wp_send_json_error( null, 400 );
			}

			$details = '';
			if ( isset( $_POST['details'] ) ) {
				$details = sanitize_text_field( wp_unslash( $_POST['details'] ) );
			}

			self::send_to_api( $reason_key, $details );

			wp_send_json_success();
		}

		/**
		 * @param string $reason_key Reason.
		 * @param string $details    Details.
		 * @return void
		 */
		private static function send_to_api( $reason_key, $details ) {
			$body = array(
				'plugin_slug' => 'wp-ulike',
				'site_url'    => home_url(),
				'reason_key'  => $reason_key,
				'details'     => $details,
			);

			wp_remote_post(
				self::get_api_url(),
				array(
					'timeout' => 15,
					'headers' => array(
						'Content-Type' => 'application/json; charset=utf-8',
					),
					'body'    => wp_json_encode( $body ),
				)
			);
		}
	}

	WP_Ulike_Deactivation_Feedback::init();
}
