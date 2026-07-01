<?php
/**
 * One-time patches for pre-2.4 sites that still have legacy vote tables.
 *
 * No-op when no legacy tables exist (pulse-only / fresh installs).
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Legacy_Upgrade' ) ) {

	final class WP_Ulike_Legacy_Upgrade {

		/**
		 * @return bool
		 */
		public static function run() {
			if ( ! class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
				require_once WP_ULIKE_INC_DIR . '/pulse-ledger/class-pulse-registry.php';
			}

			if ( ! self::has_legacy_tables() ) {
				return true;
			}

			if ( ! WP_Ulike_Meta_Schema::install() ) {
				return false;
			}

			if ( ! self::ensure_meta_item_id_column() ) {
				return false;
			}

			$alter = 'CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL, CHANGE `ip` `ip` VARCHAR(100) NOT NULL';

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				$table = $source['table'];
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $table ) ) {
					continue;
				}

				$table_escaped = esc_sql( $table );
				if ( ! self::run_query( "ALTER TABLE `{$table_escaped}` {$alter}" ) ) {
					return false;
				}

				if ( ! self::column_exists( $table, 'fingerprint' )
					&& ! self::run_query( "ALTER TABLE `{$table_escaped}` ADD COLUMN `fingerprint` VARCHAR(64) DEFAULT NULL AFTER `user_id`" ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * @return bool
		 */
		private static function has_legacy_tables() {
			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * @return bool
		 */
		private static function ensure_meta_item_id_column() {
			if ( ! WP_Ulike_Meta_Schema::table_exists() || ! self::column_exists( WP_Ulike_Meta_Schema::table(), 'item_id' ) ) {
				return true;
			}

			$meta = esc_sql( WP_Ulike_Meta_Schema::table() );

			return self::run_query(
				"ALTER TABLE `{$meta}` CHANGE `item_id` `item_id` bigint(20) unsigned NOT NULL"
			);
		}

		/**
		 * @param string $table  Full table name.
		 * @param string $column Column name.
		 * @return bool
		 */
		private static function column_exists( $table, $column ) {
			global $wpdb;

			return (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
					WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
					DB_NAME,
					$table,
					$column
				)
			);
		}

		/**
		 * @param string $query SQL.
		 * @return bool
		 */
		private static function run_query( $query ) {
			global $wpdb;

			if ( false !== $wpdb->query( $query ) ) {
				return true;
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WP ULike: Legacy upgrade query failed - ' . $wpdb->last_error . ' | Query: ' . $query );
			}

			return false;
		}
	}
}
