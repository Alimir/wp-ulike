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

			return array(
				'show_modals'             => array_key_exists( 'show_modals', $prefs )
					? (bool) $prefs['show_modals']
					: $defaults['show_modals'],
				'dismissed_notifications' => $dismissed,
				'sidebar_pro_minimized'   => array_key_exists( 'sidebar_pro_minimized', $prefs )
					? (bool) $prefs['sidebar_pro_minimized']
					: $defaults['sidebar_pro_minimized'],
			);
		}

		/**
		 * Payload for StatsAppConfig.
		 *
		 * @return array
		 */
		public static function get_app_config() {
			return self::get_prefs();
		}
	}
}
