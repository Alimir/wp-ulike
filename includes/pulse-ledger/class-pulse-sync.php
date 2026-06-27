<?php
/**
 * Pulse Ledger — resumable legacy → pulse migration.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Sync' ) ) {

	final class WP_Ulike_Pulse_Sync {

		const OPTION_PROGRESS = 'wp_ulike_pulse_sync_progress';
		const LOCK_TRANSIENT  = 'wp_ulike_pulse_sync_lock';
		const TIME_LIMIT      = 20;

		/**
		 * @return array<string,mixed>
		 */
		public static function default_progress() {
			$progress = array(
				'started_at'     => '',
				'updated_at'     => '',
				'total_legacy'   => 0,
				'total_imported' => 0,
				'complete'       => false,
				'sources'        => array(),
			);

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $config ) {
				$progress['sources'][ $slug ] = array(
					'cursor'   => 0,
					'total'    => 0,
					'imported' => 0,
					'skipped'  => 0,
					'complete' => false,
				);
			}

			return $progress;
		}

		/**
		 * @return array<string,mixed>
		 */
		public static function get_progress() {
			$stored = get_option( self::OPTION_PROGRESS, array() );
			if ( ! is_array( $stored ) || empty( $stored['sources'] ) ) {
				return self::reset_counts();
			}

			return wp_parse_args( $stored, self::default_progress() );
		}

		/**
		 * @return array<string,mixed>
		 */
		public static function reset_counts() {
			$progress = self::default_progress();
			$progress['started_at'] = current_time( 'mysql' );

			foreach ( $progress['sources'] as $slug => &$source ) {
				$config         = WP_Ulike_Pulse_Registry::legacy_sources()[ $slug ];
				$source['total'] = WP_Ulike_Pulse_Registry::table_exists( $config['table'] )
					? self::count_source_rows( $config['table'] )
					: 0;
				$progress['total_legacy'] += $source['total'];
			}
			unset( $source );

			self::save_progress( $progress );
			return $progress;
		}

		/**
		 * @return void
		 */
		public static function start() {
			self::reset_counts();
			WP_Ulike_Pulse_Config::update(
				array(
					'migration' => array(
						'status' => 'running',
					),
				)
			);
			WP_Ulike_Pulse_Sync_Scheduler::schedule();
		}

		/**
		 * @return void
		 */
		public static function pause() {
			WP_Ulike_Pulse_Config::update(
				array(
					'migration' => array(
						'status' => 'paused',
					),
				)
			);
			WP_Ulike_Pulse_Sync_Scheduler::unschedule();
		}

		/**
		 * @param int $batch_size Rows per source per batch.
		 * @return array<string,mixed>
		 */
		public static function run_batch( $batch_size = 0 ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Config::migration_running() ) {
				return array( 'processed' => 0, 'done' => true, 'message' => 'inactive' );
			}

			if ( get_transient( self::LOCK_TRANSIENT ) ) {
				return array( 'processed' => 0, 'done' => false, 'message' => 'locked' );
			}

			set_transient( self::LOCK_TRANSIENT, 1, 90 );

			$batch_size = $batch_size > 0 ? absint( $batch_size ) : WP_Ulike_Pulse_Schema::BATCH_SIZE_DEFAULT;
			$deadline   = microtime( true ) + self::TIME_LIMIT;
			$progress   = self::get_progress();
			$processed  = 0;

			try {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $config ) {
					if ( microtime( true ) >= $deadline ) {
						break;
					}

					if ( ! empty( $progress['sources'][ $slug ]['complete'] ) ) {
						continue;
					}

					if ( ! WP_Ulike_Pulse_Registry::table_exists( $config['table'] ) ) {
						$progress['sources'][ $slug ]['complete'] = true;
						continue;
					}

					$cursor = absint( $progress['sources'][ $slug ]['cursor'] );
					$table  = esc_sql( $config['table'] );

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$rows = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM `{$table}` WHERE id > %d ORDER BY id ASC LIMIT %d",
							$cursor,
							$batch_size
						)
					);

					if ( empty( $rows ) ) {
						$progress['sources'][ $slug ]['complete'] = true;
						continue;
					}

					$is_distinct = wp_ulike_setting_repo::isDistinct( $config['item_type'] );

					foreach ( $rows as $row ) {
						$result = WP_Ulike_Pulse_Writer::import_legacy_row( $config, $row, $is_distinct );
						if ( 'skipped' === $result ) {
							++$progress['sources'][ $slug ]['skipped'];
						} elseif ( false !== $result ) {
							++$progress['sources'][ $slug ]['imported'];
							++$processed;
						}

						$cursor = max( $cursor, (int) $row->id );
					}

					$progress['sources'][ $slug ]['cursor'] = $cursor;
				}

				self::aggregate_totals( $progress );
				$progress['complete'] = self::is_complete( $progress );

				if ( $progress['complete'] ) {
					WP_Ulike_Pulse_Config::update(
						array(
							'migration' => array(
								'status' => 'done',
							),
						)
					);
					WP_Ulike_Pulse_Sync_Scheduler::unschedule();
				}

				self::save_progress( $progress );
			} finally {
				delete_transient( self::LOCK_TRANSIENT );
			}

			return array(
				'processed' => $processed,
				'done'      => ! empty( $progress['complete'] ),
				'progress'  => $progress,
			);
		}

		/**
		 * @return array<string,mixed>
		 */
		public static function verify() {
			$progress = self::get_progress();
			$issues   = array();

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $config ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $config['table'] ) ) {
					continue;
				}

				$legacy = self::count_source_rows( $config['table'] );
				$pulse  = self::count_pulse_rows( $config['item_type'] );
				$source = $progress['sources'][ $slug ] ?? array();

				if ( $legacy > 0 && $pulse < ( $legacy - (int) ( $source['skipped'] ?? 0 ) ) ) {
					$issues[ $slug ] = array(
						'legacy' => $legacy,
						'pulse'  => $pulse,
					);
				}
			}

			return array(
				'ok'     => empty( $issues ),
				'issues' => $issues,
			);
		}

		/**
		 * @param array<string,mixed> $progress Progress.
		 * @return void
		 */
		private static function aggregate_totals( array &$progress ) {
			$imported = 0;
			$legacy   = 0;

			foreach ( $progress['sources'] as $source ) {
				$imported += (int) $source['imported'];
				$legacy   += (int) $source['total'];
			}

			$progress['total_legacy']   = $legacy;
			$progress['total_imported'] = $imported;
		}

		/**
		 * @param array<string,mixed> $progress Progress.
		 * @return bool
		 */
		private static function is_complete( array $progress ) {
			foreach ( $progress['sources'] as $source ) {
				if ( empty( $source['complete'] ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * @param array<string,mixed> $progress Progress.
		 * @return void
		 */
		private static function save_progress( array $progress ) {
			$progress['updated_at'] = current_time( 'mysql' );
			update_option( self::OPTION_PROGRESS, $progress, false );
		}

		/**
		 * @param string $table Full table name.
		 * @return int
		 */
		private static function count_source_rows( $table ) {
			global $wpdb;
			$table = esc_sql( $table );
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}

		/**
		 * @param string $item_type Item type.
		 * @return int
		 */
		private static function count_pulse_rows( $item_type ) {
			global $wpdb;
			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE item_type = %s AND engagement_kind = %s",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}
	}
}
