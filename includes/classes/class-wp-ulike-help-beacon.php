<?php
/**
 * WP ULike support chat (HelpWich beacon) on WP ULike admin pages.
 *
 * WordPress.org plugins may not load remote scripts or contact other servers
 * without the admin's consent, so the beacon is opt-in per user:
 * - Before consent, a launcher styled like the beacon's own is rendered locally.
 *   Nothing is requested from HelpWich on page load.
 * - The first click is the consent. It is saved for that user, and the beacon
 *   then loads normally on WP ULike pages for them.
 * The button only exists on WP ULike's own screens, never on the front end.
 * The service is listed under "External services" in readme.txt.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Help_Beacon' ) ) {

	/**
	 * Opt-in HelpWich support widget.
	 */
	class WP_Ulike_Help_Beacon {

		const SCRIPT_HANDLE = 'wp-ulike-help-beacon';
		const AJAX_ACTION   = 'wp_ulike_help_beacon_optin';
		const NONCE_ACTION  = 'wp_ulike_help_beacon_optin';

		/**
		 * Per-user consent. Removed on uninstall with the other wp_ulike_ user meta.
		 */
		const USER_META_KEY = 'wp_ulike_help_beacon_optin';

		/**
		 * Public HelpWich beacon key for WP ULike support (not a secret).
		 */
		const BEACON_KEY = 'hwb_KRqodE5n3nVCbrjG';

		const BEACON_SRC = 'https://app.helpwich.com/beacon.js';


		/**
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( __CLASS__, 'render_button' ) );
			add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_opt_in' ) );
		}

		/**
		 * Beacon key. An empty value turns the feature off.
		 *
		 * @return string
		 */
		public static function get_key() {
			return (string) apply_filters( 'wp_ulike_help_beacon_key', self::BEACON_KEY );
		}

		/**
		 * Opened instead when the widget cannot load (offline, blocked by a firewall).
		 *
		 * @return string
		 */
		public static function get_fallback_url() {
			return (string) apply_filters( 'wp_ulike_help_beacon_fallback_url', 'https://wordpress.org/support/plugin/' . WP_ULIKE_SLUG . '/' );
		}

		/**
		 * Whether support chat is available on the current screen.
		 *
		 * Off with define( 'WP_ULIKE_DISABLE_HELP_BEACON', true ) or the
		 * wp_ulike_help_beacon_enabled filter, e.g. for agencies handing a site over.
		 *
		 * @return bool
		 */
		public static function is_enabled() {
			if ( defined( 'WP_ULIKE_DISABLE_HELP_BEACON' ) && WP_ULIKE_DISABLE_HELP_BEACON ) {
				return false;
			}

			if ( '' === self::get_key() || ! self::is_plugin_screen() ) {
				return false;
			}

			return (bool) apply_filters( 'wp_ulike_help_beacon_enabled', true );
		}

		/**
		 * Whether the current user already agreed to load the support chat.
		 *
		 * @return bool
		 */
		public static function has_opted_in() {
			$user_id = get_current_user_id();

			return $user_id && '1' === (string) get_user_meta( $user_id, self::USER_META_KEY, true );
		}

		/**
		 * WP ULike's own admin pages only (Settings, Customize, Statistics, Overview,
		 * and Pro pages under the same menu). Not the post editor or other screens.
		 *
		 * @return bool
		 */
		protected static function is_plugin_screen() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			return $screen && ! empty( $screen->id ) && false !== strpos( $screen->id, WP_ULIKE_SLUG );
		}

		/**
		 * @return void
		 */
		public static function enqueue_assets() {
			if ( ! self::is_enabled() ) {
				return;
			}

			$js_path = WP_ULIKE_ADMIN_DIR . '/assets/js/help-beacon.js';

			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/js/help-beacon.js',
				array(),
				file_exists( $js_path ) ? (string) filemtime( $js_path ) : WP_ULIKE_VERSION,
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'wpUlikeHelpBeacon',
				array(
					'key'         => self::get_key(),
					'src'         => esc_url_raw( apply_filters( 'wp_ulike_help_beacon_src', self::BEACON_SRC ) ),
					'fallbackUrl' => esc_url_raw( self::get_fallback_url() ),
					'optedIn'     => self::has_opted_in(),
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'action'      => self::AJAX_ACTION,
					'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				)
			);
		}

		/**
		 * Local launcher shown until the user opts in. After that the beacon draws its own.
		 *
		 * @return void
		 */
		public static function render_button() {
			if ( ! self::is_enabled() || self::has_opted_in() ) {
				return;
			}
			?>
			<button type="button" id="wp-ulike-help-beacon" class="wp-ulike-help-beacon" aria-label="<?php esc_attr_e( 'Need help? Chat with WP ULike support', 'wp-ulike' ); ?>" aria-describedby="wp-ulike-help-beacon-note" title="<?php esc_attr_e( 'Need help?', 'wp-ulike' ); ?>">
				<svg class="wp-ulike-help-beacon__icon" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false">
					<path fill="currentColor" d="M12 3C6.48 3 2 6.92 2 11.75c0 2.35 1.07 4.48 2.8 6.05L4 21.5l4.13-1.9c1.18.38 2.48.6 3.87.6 5.52 0 10-3.92 10-8.75S17.52 3 12 3Z"/>
				</svg>
			</button>
			<p id="wp-ulike-help-beacon-note" class="screen-reader-text">
				<?php esc_html_e( 'Opens the support chat. It loads the HelpWich widget from app.helpwich.com, only after you click.', 'wp-ulike' ); ?>
			</p>
			<?php
		}

		/**
		 * Save the current user's consent to load the support chat.
		 *
		 * @return void
		 */
		public static function ajax_opt_in() {
			check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				wp_send_json_error( null, 403 );
			}

			update_user_meta( $user_id, self::USER_META_KEY, '1' );
			wp_send_json_success();
		}
	}
}
