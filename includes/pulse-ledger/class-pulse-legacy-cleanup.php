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
		 * Whether it is safe to permanently drop legacy tables.
		 *
		 * Forces a deep COUNT(*)/COUNT(DISTINCT) verification -- slow on huge
		 * (multi-million-row) tables. The admin page renders this on every
		 * view, so the result is cached briefly; drop_legacy_tables() always
		 * passes $bypass_cache=true to force a fresh check immediately before
		 * the irreversible DROP, never trusting a possibly-stale cached "ok".
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
