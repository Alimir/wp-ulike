<?php
/**
 * Pulse Ledger — optional purge of non-essential analytics metadata.
 *
 * Nulls device / os / browser on old pulse rows. Never touches vote identity
 * (user_id, fingerprint, dedupe_token), engagement fields, IP, or country.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Meta_Purge' ) ) {

	final class WP_Ulike_Pulse_Meta_Purge {

		const HOOK         = 'wp_ulike_pulse_purge_meta';
		const DAYS_DEFAULT = 90;
		const DAYS_MIN     = 30;
		const DAYS_MAX     = 3650;
		const CRON_BATCHES = 20;

		/**
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'run_scheduled' ) );
			add_action( 'admin_init', array( __CLASS__, 'sync_schedule' ) );
			add_action( 'wp_ulike_settings_saved', array( __CLASS__, 'sync_schedule' ) );
		}

		/**
		 * @return bool
		 */
		public static function is_enabled() {
			return defined( 'WP_ULIKE_PRO_VERSION' )
				&& function_exists( 'wp_ulike_is_true' )
				&& wp_ulike_is_true( wp_ulike_get_option( 'enable_pulse_meta_purge', false ) );
		}

		/**
		 * @param int $days Optional override. 0 = setting / default.
		 * @return int
		 */
		public static function days( $days = 0 ) {
			$days = absint( $days );
			if ( $days < 1 && function_exists( 'wp_ulike_get_option' ) ) {
				$days = absint( wp_ulike_get_option( 'pulse_meta_purge_days', self::DAYS_DEFAULT ) );
			}
			if ( $days < 1 ) {
				$days = self::DAYS_DEFAULT;
			}

			return max( self::DAYS_MIN, min( self::DAYS_MAX, $days ) );
		}

		/**
		 * UTC cutoff matching pulse `date_time` writes.
		 *
		 * @param int $days Retention days.
		 * @return string
		 */
		public static function cutoff( $days = 0 ) {
			return gmdate( 'Y-m-d H:i:s', time() - ( self::days( $days ) * DAY_IN_SECONDS ) );
		}

		/**
		 * @return void
		 */
		public static function sync_schedule() {
			if ( ! self::is_enabled() ) {
				if ( wp_next_scheduled( self::HOOK ) ) {
					wp_clear_scheduled_hook( self::HOOK );
				}
				return;
			}

			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
			}
		}

		/**
		 * Daily nibble only. Use WP-CLI for a full catch-up on large tables.
		 *
		 * @return array<string,mixed>
		 */
		public static function run_scheduled() {
			if ( ! self::is_enabled() ) {
				wp_clear_scheduled_hook( self::HOOK );
				return array(
					'ok'      => false,
					'message' => 'disabled',
				);
			}

			if ( class_exists( 'WP_Ulike_Pulse_Config' ) && WP_Ulike_Pulse_Config::migration_running() ) {
				return array(
					'ok'      => false,
					'message' => 'migrating',
				);
			}

			return self::run(
				array(
					'dry_run'     => false,
					'max_batches' => self::CRON_BATCHES,
				)
			);
		}

		/**
		 * Whether at least one old row still has a device label.
		 *
		 * Keys off `device` (written with os/browser) so `idx_device_date`
		 * can be used. LIMIT 1 — never COUNT(*) a large table.
		 *
		 * @param int $days Retention days.
		 * @return bool
		 */
		public static function has_work( $days = 0 ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return false;
			}

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$table}` WHERE device IS NOT NULL AND date_time < %s LIMIT 1",
					self::cutoff( $days )
				)
			);

			return ! empty( $id );
		}

		/**
		 * NULL analytics columns on old rows, in small UPDATE … LIMIT batches.
		 *
		 * @param array<string,mixed> $args {
		 *     @type bool $dry_run     Do not write.
		 *     @type int  $days        Retention days (0 = setting / default).
		 *     @type int  $batch_size  Rows per UPDATE.
		 *     @type int  $max_batches 0 = until done.
		 * }
		 * @return array<string,mixed>
		 */
		public static function run( array $args = array() ) {
			global $wpdb;

			$args = wp_parse_args(
				$args,
				array(
					'dry_run'     => true,
					'days'        => 0,
					'batch_size'  => WP_Ulike_Pulse_Schema::BATCH_SIZE_DEFAULT,
					'max_batches' => 0,
				)
			);

			$days       = self::days( $args['days'] );
			$cutoff     = self::cutoff( $days );
			$batch_size = absint( $args['batch_size'] );
			$batch_size = max( WP_Ulike_Pulse_Schema::BATCH_SIZE_MIN, min( WP_Ulike_Pulse_Schema::BATCH_SIZE_MAX, $batch_size ) );
			$dry_run    = ! empty( $args['dry_run'] );
			$max        = absint( $args['max_batches'] );

			$base = array(
				'days'    => $days,
				'cutoff'  => $cutoff,
				'dry_run' => $dry_run,
				'updated' => 0,
			);

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return array_merge(
					$base,
					array(
						'ok'      => false,
						'done'    => true,
						'message' => 'no_table',
					)
				);
			}

			if ( $dry_run ) {
				$has = self::has_work( $days );
				return array_merge(
					$base,
					array(
						'ok'      => true,
						'done'    => ! $has,
						'message' => $has ? 'pending' : 'complete',
					)
				);
			}

			$lock = self::lock_name();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) {
				return array_merge(
					$base,
					array(
						'ok'      => false,
						'done'    => false,
						'message' => 'locked',
					)
				);
			}

			$table   = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$updated = 0;
			$batches = 0;

			try {
				while ( 0 === $max || $batches < $max ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$hit = $wpdb->query(
						$wpdb->prepare(
							"UPDATE `{$table}` SET device = NULL, os = NULL, browser = NULL WHERE device IS NOT NULL AND date_time < %s LIMIT %d",
							$cutoff,
							$batch_size
						)
					);

					if ( false === $hit ) {
						return array_merge(
							$base,
							array(
								'ok'      => false,
								'done'    => false,
								'updated' => $updated,
								'message' => $wpdb->last_error ? $wpdb->last_error : 'query_failed',
							)
						);
					}

					$hit = (int) $hit;
					$updated += $hit;
					++$batches;

					if ( $hit < $batch_size ) {
						return array_merge(
							$base,
							array(
								'ok'      => true,
								'done'    => true,
								'updated' => $updated,
								'message' => 'complete',
							)
						);
					}
				}
			} finally {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
			}

			return array_merge(
				$base,
				array(
					'ok'      => true,
					'done'    => false,
					'updated' => $updated,
					'message' => 'partial',
				)
			);
		}

		/**
		 * @return string
		 */
		private static function lock_name() {
			global $wpdb;
			return 'wp_ulike_pulse_purge_' . md5( (string) $wpdb->prefix );
		}
	}
}
