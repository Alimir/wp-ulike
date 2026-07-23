<?php
/**
 * Pulse Ledger — optional legacy table removal after migration.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Legacy_Cleanup' ) ) {

	final class WP_Ulike_Pulse_Legacy_Cleanup {

		const OPTION_DROPPED_AT = 'wp_ulike_pulse_legacy_dropped_at';
		const VERIFY_CACHE_TRANSIENT = 'wp_ulike_pulse_can_drop_legacy';
		const VERIFY_CACHE_TTL       = 60;

		/**
		 * @return bool
		 */
		public static function legacy_tables_exist() {
			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * @return string[]
		 */
		public static function existing_legacy_tables() {
			$tables = array();

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$tables[] = $source['table'];
				}
			}

			return $tables;
		}

		/**
		 * Whether the cleanup UI may offer the drop button (cheap — no COUNT scans).
		 *
		 * Uses progress-only verification. Deep COUNT(*) runs only at drop time
		 * via can_drop_legacy( true ).
		 *
		 * @return bool
		 */
		public static function can_offer_drop() {
			if ( WP_Ulike_Pulse_Config::MODE_PULSE !== WP_Ulike_Pulse_Config::mode() ) {
				return false;
			}

			if ( ! self::legacy_tables_exist() ) {
				return false;
			}

			$verify = WP_Ulike_Pulse_Sync::verify( false );

			return ! empty( $verify['ok'] );
		}

		/**
		 * Whether it is safe to permanently drop legacy tables.
		 *
		 * Forces a deep COUNT(*)/COUNT(DISTINCT) verification — slow on huge
		 * tables. Call only at drop time (or CLI), never on every page view.
		 * drop_legacy_tables() always passes $bypass_cache=true so the
		 * irreversible DROP never trusts a stale cached "ok".
		 *
		 * @param bool $bypass_cache Force a fresh deep verify.
		 * @return bool
		 */
		public static function can_drop_legacy( $bypass_cache = false ) {
			if ( WP_Ulike_Pulse_Config::MODE_PULSE !== WP_Ulike_Pulse_Config::mode() ) {
				return false;
			}

			if ( ! self::legacy_tables_exist() ) {
				return false;
			}

			if ( ! $bypass_cache ) {
				$cached = get_transient( self::VERIFY_CACHE_TRANSIENT );
				if ( false !== $cached ) {
					return (bool) $cached;
				}
			}

			$verify = WP_Ulike_Pulse_Sync::verify( true );
			$ok     = ! empty( $verify['ok'] );

			set_transient( self::VERIFY_CACHE_TRANSIENT, $ok, self::VERIFY_CACHE_TTL );

			return $ok;
		}

		/**
		 * @return array{ok:bool,dropped:string[],message:string}
		 */
		public static function drop_legacy_tables() {
			global $wpdb;

			// Bypass the cache used for admin-page rendering: this action is
			// irreversible, so it must re-verify against current data, not a
			// result that may be up to VERIFY_CACHE_TTL seconds stale.
			if ( ! self::can_drop_legacy( true ) ) {
				return array(
					'ok'      => false,
					'dropped' => array(),
					'message' => 'not_allowed',
				);
			}

			$dropped = array();

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					continue;
				}

				$table = esc_sql( $source['table'] );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result = $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

				if ( false === $result ) {
					return array(
						'ok'      => false,
						'dropped' => $dropped,
						'message' => 'drop_failed',
					);
				}

				$dropped[] = $source['table'];
			}

			update_option( self::OPTION_DROPPED_AT, current_time( 'mysql' ), false );
			WP_Ulike_Pulse_Config::mark_admin_dismissed();

			return array(
				'ok'      => true,
				'dropped' => $dropped,
				'message' => 'dropped',
			);
		}
	}
}
