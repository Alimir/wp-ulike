<?php
/**
 * Per-user preferences for the React statistics dashboard.
 *
 * @package WP_Ulike
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Stats_User_Prefs' ) ) {
	/**
	 * Stores dismissed notifications and lightweight stats UI prefs per user.
	 */
	final class WP_Ulike_Stats_User_Prefs {

		const META_KEY = 'wp_ulike_stats_user_prefs';

		/**
		 * Default preference shape.
		 *
		 * @return array
		 */
		public static function get_defaults() {
			return array(
				'show_modals'              => true,
				'dismissed_notifications'  => array(),
				'sidebar_pro_minimized'    => false,
				'theme'                    => 'system',
			);
		}

		/**
		 * Read preferences for a user.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public static function get_prefs( $user_id = 0 ) {
			$user_id = $user_id ? (int) $user_id : get_current_user_id();
			$stored  = $user_id ? get_user_meta( $user_id, self::META_KEY, true ) : array();

			return self::normalize( $stored );
		}

		/**
		 * Persist preferences for a user.
		 *
		 * @param array $prefs   Preference payload.
		 * @param int   $user_id User ID.
		 * @return bool
		 */
		public static function save_prefs( $prefs, $user_id = 0 ) {
			$user_id = $user_id ? (int) $user_id : get_current_user_id();

			if ( ! $user_id ) {
				return false;
			}

			update_user_meta( $user_id, self::META_KEY, self::normalize( $prefs ) );

			return true;
		}

		/**
		 * Sanitize preference payload.
		 *
		 * @param mixed $prefs Raw payload.
		 * @return array
		 */
		public static function normalize( $prefs ) {
			$defaults = self::get_defaults();
			$prefs    = is_array( $prefs ) ? $prefs : array();

			$dismissed = array();
			if ( ! empty( $prefs['dismissed_notifications'] ) && is_array( $prefs['dismissed_notifications'] ) ) {
				foreach ( $prefs['dismissed_notifications'] as $id => $value ) {
					$key = absint( $id );
					if ( $key > 0 && $value ) {
						$dismissed[ (string) $key ] = true;
					}
				}
			}

			$theme = isset( $prefs['theme'] ) ? sanitize_key( $prefs['theme'] ) : $defaults['theme'];
			if ( ! in_array( $theme, array( 'light', 'dark', 'system' ), true ) ) {
				$theme = $defaults['theme'];
			}

			return array(
				'show_modals'             => array_key_exists( 'show_modals', $prefs )
					? (bool) $prefs['show_modals']
					: $defaults['show_modals'],
				'dismissed_notifications' => $dismissed,
				'sidebar_pro_minimized'   => array_key_exists( 'sidebar_pro_minimized', $prefs )
					? (bool) $prefs['sidebar_pro_minimized']
					: $defaults['sidebar_pro_minimized'],
				'theme'                   => $theme,
			);
		}

		/**
		 * Payload for StatsAppConfig.
		 *
		 * @return array
		 */
		public static function get_app_config() {
			$prefs   = self::get_prefs();
			$user_id = get_current_user_id();
			$stored  = $user_id ? get_user_meta( $user_id, self::META_KEY, true ) : array();

			// Keep theme unset until the user has saved one, so the React app
			// can migrate a legacy localStorage preference into user meta.
			if ( ! is_array( $stored ) || ! array_key_exists( 'theme', $stored ) ) {
				unset( $prefs['theme'] );
			}

			return $prefs;
		}

		/**
		 * Inline script that applies the saved color scheme before React boots.
		 *
		 * @return string
		 */
		public static function get_theme_boot_script() {
			return '(function(){var p=(window.StatsAppConfig&&window.StatsAppConfig.userPrefs)||{};var t=p.theme;if(t!=="light"&&t!=="dark"&&t!=="system"){try{var s=JSON.parse(localStorage.getItem("ulikeStatsUserPrefs")||"null");t=s&&s.theme?s.theme:localStorage.getItem("theme");}catch(e){t=localStorage.getItem("theme");}}if(t!=="light"&&t!=="dark"&&t!=="system"){t="system";}var dark=t==="dark"||(t==="system"&&window.matchMedia("(prefers-color-scheme: dark)").matches);document.documentElement.classList.toggle("dark",dark);document.documentElement.setAttribute("data-theme",dark?"dark":"light");})();';
		}

		/**
		 * Attach the theme boot script after StatsAppConfig is printed.
		 *
		 * @param string $handle Script handle used for the stats bundle.
		 * @return void
		 */
		public static function enqueue_theme_boot_script( $handle ) {
			if ( ! $handle || ( ! wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle, 'enqueued' ) ) ) {
				return;
			}

			wp_add_inline_script( $handle, self::get_theme_boot_script(), 'before' );
		}
	}
}
