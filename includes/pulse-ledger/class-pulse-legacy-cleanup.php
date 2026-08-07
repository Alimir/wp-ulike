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
		 * Whether the cleanup UI may offer the drop button.
		 *
		 * Same gate as can_drop_legacy() so the button never promises a drop
		 * that the action will refuse.
		 *
		 * @return bool
		 */
		public static function can_offer_drop() {
			return self::can_drop_legacy();
		}

		/**
		 * Whether it is safe to permanently drop legacy tables.
		 *
		 * Requires Pulse mode + migration marked complete (progress-only).
		 * Deep COUNT(*) scans are intentionally not required: once reads/writes
		 * are on Pulse, legacy tables are unused leftovers. Re-scanning huge
		 * tables timed out or false-negatived many sites at drop time.
		 *
		 * @param bool $bypass_cache Unused; kept for call-site BC.
		 * @return bool
		 */
		public static function can_drop_legacy( $bypass_cache = false ) {
			unset( $bypass_cache );

			if ( WP_Ulike_Pulse_Config::MODE_PULSE !== WP_Ulike_Pulse_Config::mode() ) {
				return false;
			}

			if ( ! self::legacy_tables_exist() ) {
				return false;
			}

			// Same progress-only check used when enabling Pulse — no COUNT scans.
			$verify = WP_Ulike_Pulse_Sync::verify( false );

			return ! empty( $verify['ok'] );
		}

		/**
		 * @return array{ok:bool,dropped:string[],message:string}
		 */
		public static function drop_legacy_tables() {
			global $wpdb;

			if ( ! self::can_drop_legacy() ) {
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
