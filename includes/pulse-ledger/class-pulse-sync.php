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
				'started_at'        => '',
				'updated_at'        => '',
				'total_imported'    => 0,
				'total_skipped'     => 0,
				'total_processed'   => 0,
				'total_max_id'      => 0,
				'percent_estimate'  => 0,
				'complete'          => false,
				'sources'           => array(),
			);

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $config ) {
				$progress['sources'][ $slug ] = array(
					'cursor'   => 0,
					'max_id'   => 0,
					'imported' => 0,
					'skipped'  => 0,
					'failed'   => 0,
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
				return self::default_progress();
			}

			$progress = wp_parse_args( $stored, self::default_progress() );
			self::normalize_progress_sources( $progress );
			self::ensure_bounds( $progress );
			self::aggregate_totals( $progress );

			return self::finalize_if_complete( $progress );
		}

		/**
		 * Merge stored source progress with the current registry shape.
		 *
		 * @param array<string,mixed> $progress Progress.
		 * @return void
		 */
		private static function normalize_progress_sources( array &$progress ) {
			$defaults = self::default_progress()['sources'];

			foreach ( $defaults as $slug => $default_source ) {
				if ( ! isset( $progress['sources'][ $slug ] ) || ! is_array( $progress['sources'][ $slug ] ) ) {
					$progress['sources'][ $slug ] = $default_source;
					continue;
				}

				$progress['sources'][ $slug ] = wp_parse_args( $progress['sources'][ $slug ], $default_source );
			}
		}

		/**
		 * Whether every legacy row has been copied (or there was nothing to copy).
		 *
		 * @param array<string,mixed>|null $progress Optional progress snapshot.
		 * @return bool
		 */
		public static function is_sync_complete( $progress = null ) {
			if ( 'done' === ( WP_Ulike_Pulse_Config::get()['migration']['status'] ?? '' ) ) {
				return true;
			}

			if ( null === $progress ) {
				$progress = self::get_progress();
			} else {
				self::normalize_progress_sources( $progress );
			}

			if ( ! empty( $progress['complete'] ) ) {
				return true;
			}

			return self::is_complete( $progress );
		}

		/**
		 * Mark migration done when all sources are finished but status was left on running.
		 *
		 * @param array<string,mixed> $progress Progress.
		 * @return array<string,mixed>
		 */
		public static function finalize_if_complete( array $progress ) {
			if ( ! self::is_complete( $progress ) ) {
				return $progress;
			}

			$progress['complete']          = true;
			$progress['percent_estimate']    = 100;

			if ( WP_Ulike_Pulse_Config::migration_running() ) {
				WP_Ulike_Pulse_Config::update(
					array(
						'migration' => array(
							'status' => 'done',
						),
					)
				);
				WP_Ulike_Pulse_Sync_Scheduler::unschedule();
				wp_ulike_pulse_flush_cache();
			}

			self::save_progress( $progress );

			return $progress;
		}

		/**
		 * Reset migration counters and capture per-table MAX(id) bounds (no COUNT scans).
		 *
		 * @return array<string,mixed>
		 */
		public static function reset_progress() {
			$progress               = self::default_progress();
			$progress['started_at'] = current_time( 'mysql' );

			foreach ( $progress['sources'] as $slug => &$source ) {
				$config = WP_Ulike_Pulse_Registry::legacy_sources()[ $slug ];
				if ( WP_Ulike_Pulse_Registry::table_exists( $config['table'] ) ) {
					$source['max_id'] = self::max_source_id( $config['table'] );
					if ( 0 === (int) $source['max_id'] ) {
						$source['complete'] = true;
					}
				} else {
					$source['complete'] = true;
				}
			}
			unset( $source );

			self::aggregate_totals( $progress );
			self::save_progress( $progress );

			return $progress;
		}

		/**
		 * @return void
		 */
		public static function start() {
			if ( self::is_sync_complete() ) {
				return;
			}

			$stored = get_option( self::OPTION_PROGRESS, array() );

			if ( ! self::has_started( is_array( $stored ) ? $stored : array() ) ) {
				self::reset_progress();
			} else {
				$progress = self::get_progress();
				self::ensure_bounds( $progress );
				self::aggregate_totals( $progress );
				self::save_progress( $progress );
			}

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
						} else {
							++$progress['sources'][ $slug ]['failed'];
						}

						$cursor = max( $cursor, (int) $row->id );
					}

					$progress['sources'][ $slug ]['cursor'] = $cursor;
				}

				self::aggregate_totals( $progress );
				$progress['complete'] = self::is_complete( $progress );

				if ( $progress['complete'] ) {
					$progress['percent_estimate'] = 100;
					WP_Ulike_Pulse_Config::update(
						array(
							'migration' => array(
								'status' => 'done',
							),
						)
					);
					WP_Ulike_Pulse_Sync_Scheduler::unschedule();
					wp_ulike_pulse_flush_cache();
				}

				self::save_progress( $progress );
			} finally {
				delete_transient( self::LOCK_TRANSIENT );
			}

			return array(
				'processed'        => $processed,
				'done'             => ! empty( $progress['complete'] ),
				'progress'         => $progress,
				'migration_status' => WP_Ulike_Pulse_Config::get()['migration']['status'] ?? 'idle',
			);
		}

		/**
		 * Compare legacy vs pulse row counts (explicit check — may scan large tables).
		 *
		 * @param bool $deep When true, run COUNT(*) checks (slow on huge tables). Default: progress-only.
		 * @return array<string,mixed>
		 */
		public static function verify( $deep = false ) {
			$deep = $deep || (bool) apply_filters( 'wp_ulike_pulse_verify_deep_counts', false );

			$progress = self::get_progress();

			if ( ! self::is_sync_complete( $progress ) ) {
				return array(
					'ok'     => false,
					'issues' => array(
						'_sync' => array(
							'reason' => 'sync_incomplete',
						),
					),
				);
			}

			if ( ! $deep ) {
				return self::verify_progress_only( $progress );
			}

			return self::verify_with_counts( $progress );
		}

		/**
		 * Fast verification using sync progress only (no table scans).
		 *
		 * @param array<string,mixed> $progress Progress snapshot.
		 * @return array<string,mixed>
		 */
		private static function verify_progress_only( array $progress ) {
			$issues = array();

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $config ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $config['table'] ) ) {
					continue;
				}

				$source = $progress['sources'][ $slug ] ?? array();

				if ( empty( $source['complete'] ) ) {
					$issues[ $slug ] = array(
						'reason' => 'source_incomplete',
					);
					continue;
				}

				if ( (int) ( $source['failed'] ?? 0 ) > 0 ) {
					$issues[ $slug ] = array(
						'reason' => 'failed_rows',
						'failed' => (int) $source['failed'],
					);
				}
			}

			return array(
				'ok'     => empty( $issues ),
				'issues' => $issues,
			);
		}

		/**
		 * Deep verification with COUNT(*) (WP-CLI --deep or filter).
		 *
		 * @param array<string,mixed> $progress Progress snapshot.
		 * @return array<string,mixed>
		 */
		private static function verify_with_counts( array $progress ) {
			$issues = array();

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $config ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $config['table'] ) ) {
					continue;
				}

				$source   = $progress['sources'][ $slug ] ?? array();
				$imported = (int) ( $source['imported'] ?? 0 );
				$skipped  = (int) ( $source['skipped'] ?? 0 );
				$failed   = (int) ( $source['failed'] ?? 0 );

				if ( empty( $source['complete'] ) ) {
					$issues[ $slug ] = array(
						'reason' => 'source_incomplete',
					);
					continue;
				}

				if ( $failed > 0 ) {
					$issues[ $slug ] = array(
						'reason' => 'failed_rows',
						'failed' => $failed,
					);
					continue;
				}

				$legacy = self::count_source_rows( $config['table'] );
				if ( $legacy <= 0 ) {
					continue;
				}

				if ( wp_ulike_setting_repo::isDistinct( $config['item_type'] ) ) {
					if ( $imported + $skipped < 1 ) {
						$issues[ $slug ] = array(
							'reason' => 'nothing_imported',
							'legacy' => $legacy,
						);
					}
					continue;
				}

				$pulse   = self::count_pulse_rows( $config['item_type'] );
				$minimum = max( 0, $legacy - $skipped );

				if ( $pulse < $minimum ) {
					$issues[ $slug ] = array(
						'reason'  => 'count_mismatch',
						'legacy'  => $legacy,
						'pulse'   => $pulse,
						'skipped' => $skipped,
					);
				}
			}

			return array(
				'ok'     => empty( $issues ),
				'issues' => $issues,
			);
		}

		/**
		 * Human-readable progress line for admin / CLI.
		 *
		 * @param array<string,mixed> $progress Progress snapshot.
		 * @return string
		 */
		public static function progress_label( array $progress ) {
			$imported  = (int) ( $progress['total_imported'] ?? 0 );
			$skipped   = (int) ( $progress['total_skipped'] ?? 0 );
			$failed    = (int) ( $progress['total_failed'] ?? 0 );
			$processed = (int) ( $progress['total_processed'] ?? ( $imported + $skipped + $failed ) );
			$percent   = (float) ( $progress['percent_estimate'] ?? 0 );
			$complete  = ! empty( $progress['complete'] ) || self::is_complete( $progress );

			if ( $complete ) {
				$parts = array(
					sprintf(
						/* translators: %s: number of rows copied */
						__( '%s rows copied', 'wp-ulike' ),
						number_format_i18n( $imported )
					),
				);

				if ( $skipped > 0 ) {
					$parts[] = sprintf(
						/* translators: %s: number of skipped rows */
						__( '%s skipped', 'wp-ulike' ),
						number_format_i18n( $skipped )
					);
				}

				if ( $failed > 0 ) {
					$parts[] = sprintf(
						/* translators: %s: number of rows that could not be imported */
						__( '%s failed', 'wp-ulike' ),
						number_format_i18n( $failed )
					);
				}

				return implode( ', ', $parts ) . ' · ' . __( 'complete', 'wp-ulike' );
			}

			if ( $skipped > 0 ) {
				$label = sprintf(
					/* translators: 1: imported rows, 2: skipped rows */
					__( '%1$s rows copied (%2$s skipped)', 'wp-ulike' ),
					number_format_i18n( $imported ),
					number_format_i18n( $skipped )
				);
			} elseif ( $processed > 0 || $imported > 0 ) {
				$label = sprintf(
					/* translators: %s: number of rows copied */
					__( '%s rows copied', 'wp-ulike' ),
					number_format_i18n( $imported )
				);
			} else {
				$label = __( 'Waiting to start…', 'wp-ulike' );
			}

			if ( $percent > 0 && $percent < 100 ) {
				$label .= sprintf(
					/* translators: %s: estimated completion percentage */
					__( ' · ~%s%% estimated', 'wp-ulike' ),
					number_format_i18n( $percent, 1 )
				);
			}

			return $label;
		}

		/**
		 * @param array<string,mixed> $progress Progress.
		 * @return void
		 */
		private static function aggregate_totals( array &$progress ) {
			$imported   = 0;
			$skipped    = 0;
			$failed     = 0;
			$cursor_sum = 0;
			$max_id_sum = 0;

			foreach ( $progress['sources'] as $source ) {
				$imported += (int) ( $source['imported'] ?? 0 );
				$skipped  += (int) ( $source['skipped'] ?? 0 );
				$failed   += (int) ( $source['failed'] ?? 0 );
				$max_id   = (int) ( $source['max_id'] ?? 0 );
				$cursor   = (int) ( $source['cursor'] ?? 0 );

				if ( ! empty( $source['complete'] ) ) {
					$cursor_sum += $max_id;
				} else {
					$cursor_sum += $cursor;
				}

				$max_id_sum += $max_id;
			}

			$progress['total_imported']   = $imported;
			$progress['total_skipped']    = $skipped;
			$progress['total_failed']     = $failed;
			$progress['total_processed']  = $imported + $skipped + $failed;
			$progress['total_max_id']     = $max_id_sum;
			$progress['percent_estimate'] = self::estimate_percent( $progress, $cursor_sum, $max_id_sum );
		}

		/**
		 * @param array<string,mixed> $progress     Progress.
		 * @param int                 $cursor_sum   Sum of cursor positions.
		 * @param int                 $max_id_sum   Sum of max id bounds.
		 * @return float
		 */
		private static function estimate_percent( array $progress, $cursor_sum, $max_id_sum ) {
			if ( ! empty( $progress['complete'] ) || self::is_complete( $progress ) ) {
				return 100.0;
			}

			if ( $max_id_sum <= 0 ) {
				return 0.0;
			}

			return min( 100, round( ( $cursor_sum / $max_id_sum ) * 100, 1 ) );
		}

		/**
		 * Ensure each source has a max_id bound (lazy MAX(id), no COUNT).
		 *
		 * @param array<string,mixed> $progress Progress.
		 * @return void
		 */
		private static function ensure_bounds( array &$progress ) {
			foreach ( $progress['sources'] as $slug => &$source ) {
				if ( isset( $source['max_id'] ) && (int) $source['max_id'] > 0 ) {
					continue;
				}

				if ( ! empty( $source['complete'] ) ) {
					continue;
				}

				$config = WP_Ulike_Pulse_Registry::legacy_sources()[ $slug ] ?? null;
				if ( ! $config || ! WP_Ulike_Pulse_Registry::table_exists( $config['table'] ) ) {
					$source['max_id']   = 0;
					$source['complete'] = true;
					continue;
				}

				$source['max_id'] = self::max_source_id( $config['table'] );
				if ( 0 === (int) $source['max_id'] ) {
					$source['complete'] = true;
				}
			}
			unset( $source );
		}

		/**
		 * @param array<string,mixed> $progress Stored progress.
		 * @return bool
		 */
		private static function has_started( array $progress ) {
			if ( ! empty( $progress['started_at'] ) ) {
				return true;
			}

			if ( empty( $progress['sources'] ) || ! is_array( $progress['sources'] ) ) {
				return false;
			}

			foreach ( $progress['sources'] as $source ) {
				if ( (int) ( $source['cursor'] ?? 0 ) > 0 ) {
					return true;
				}
				if ( (int) ( $source['imported'] ?? 0 ) > 0 ) {
					return true;
				}
				if ( (int) ( $source['skipped'] ?? 0 ) > 0 ) {
					return true;
				}
			}

			return false;
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
		private static function max_source_id( $table ) {
			global $wpdb;
			$table = esc_sql( $table );
			return (int) $wpdb->get_var( "SELECT MAX(id) FROM `{$table}`" );
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
