<?php
/**
 * Prunes dormant guest rows from the meta cache table.
 *
 * Every voter gets one `ulike_meta` row per content type holding a map of
 * "which items this identity voted on". For logged-in users that row is worth
 * keeping forever. For guests it is keyed by a hash of their IP address, so a
 * busy site accumulates one row per IP that ever voted and never removes any of
 * them — the one unbounded growth path left in the schema.
 *
 * These rows are only a cache in front of the Pulse ledger: on a miss,
 * wp_ulike_get_user_item_history() falls through to
 * WP_Ulike_Pulse_Reader::user_action(), which is the authoritative lookup. So a
 * dropped row costs one extra read on that guest's next view and nothing else —
 * no vote is lost and nobody gets to vote twice.
 *
 * Nothing here runs on its own. Deleting rows from somebody else's database
 * without being asked is not ours to decide, however safe the rows are, so the
 * pass stays dormant until the site owner turns it on. Overview offers it only
 * when there is a real amount to reclaim, states the number, and explains what
 * goes and what stays.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Guest_Cache_Purge' ) ) {

	final class WP_Ulike_Guest_Cache_Purge {

		const HOOK          = 'wp_ulike_purge_guest_cache';
		const CURSOR_OPTION = 'wp_ulike_guest_cache_cursor';

		const ENABLED_OPTION   = 'wp_ulike_guest_cache_auto';
		const DISMISSED_OPTION = 'wp_ulike_guest_cache_dismissed';
		const ESTIMATE_KEY     = 'wp_ulike_guest_cache_estimate';

		const DAYS_DEFAULT = 90;
		const DAYS_MIN     = 30;
		const DAYS_MAX     = 3650;

		const BATCH_DEFAULT = 200;
		const BATCH_MAX     = 2000;
		const CRON_BATCHES  = 10;

		// Below this there is nothing worth interrupting anyone about.
		const SUGGEST_THRESHOLD = 20000;

		// Hard ceiling on how many rows the estimate will scan. The whole point of
		// the number is to answer "is there enough here to be worth offering", and
		// that answer does not improve past this — while an unbounded COUNT over a
		// multi-million row table would stall the page it is drawn on.
		const ESTIMATE_CAP = 250000;

		/**
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'run_scheduled' ) );
			add_action( 'admin_init', array( __CLASS__, 'sync_schedule' ) );
		}

		/**
		 * Whether this site is in a state where the pass would be correct.
		 *
		 * Requires pulse to be the read source. In legacy or dual mode the pulse
		 * table does not hold the full history yet, so "last seen" would read as
		 * far older than it is and the pass would clear rows for guests who are
		 * still active — safe, but pointless churn.
		 *
		 * @return bool
		 */
		public static function can_run() {
			if ( ! class_exists( 'WP_Ulike_Pulse_Config' ) || ! class_exists( 'WP_Ulike_Meta_Schema' ) ) {
				return false;
			}

			if ( WP_Ulike_Pulse_Config::READ_PULSE !== WP_Ulike_Pulse_Config::read_mode() ) {
				return false;
			}

			if ( WP_Ulike_Pulse_Config::migration_running() ) {
				return false;
			}

			return true;
		}

		/**
		 * Whether the site owner has asked for this to happen on a schedule.
		 *
		 * Defaults to off. A site that never visits Overview, never reads the
		 * changelog, and never opts in keeps every row it has today.
		 *
		 * @return bool
		 */
		public static function is_enabled() {
			if ( ! self::can_run() ) {
				return false;
			}

			$enabled = (bool) get_option( self::ENABLED_OPTION, false );

			/**
			 * Filters whether dormant guest cache rows are pruned on a schedule.
			 *
			 * Lets a host or agency turn this on fleet-wide without visiting each
			 * dashboard. Default follows the site's own choice.
			 *
			 * @param bool $enabled Stored preference.
			 */
			return (bool) apply_filters( 'wp_ulike_purge_guest_cache_enabled', $enabled );
		}

		/**
		 * Turn the scheduled pass on or off.
		 *
		 * @param bool $enabled Whether to run daily.
		 * @return void
		 */
		public static function set_enabled( $enabled ) {
			update_option( self::ENABLED_OPTION, (bool) $enabled ? 1 : 0, false );
			self::sync_schedule();
		}

		/**
		 * Roughly how many guest cache rows exist.
		 *
		 * Counting the *dormant* ones exactly means joining the pulse table per
		 * identity, which is far too expensive to do on a page load. This counts
		 * guest rows instead — an honest upper bound on what a pass could remove,
		 * and enough to answer "is there anything here worth doing".
		 *
		 * Stops counting at ESTIMATE_CAP, so the cost is the same on a site with
		 * 300,000 rows as on one with 30 million. Cached for a day on top of that.
		 *
		 * @param bool $force Recount now, ignoring the cache.
		 * @return int
		 */
		public static function estimate( $force = false ) {
			global $wpdb;

			if ( ! $force ) {
				$cached = get_transient( self::ESTIMATE_KEY );

				if ( false !== $cached ) {
					return (int) $cached;
				}
			}

			if ( ! self::can_run() || ! WP_Ulike_Meta_Schema::table_exists() ) {
				return 0;
			}

			$table = WP_Ulike_Meta_Schema::table();

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM (
						SELECT 1
						FROM `{$table}` m
						LEFT JOIN `{$wpdb->users}` u ON u.ID = m.item_id
						WHERE m.meta_group = 'user'
						  AND u.ID IS NULL
						LIMIT %d
					) capped",
					self::ESTIMATE_CAP + 1
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			set_transient( self::ESTIMATE_KEY, $count, DAY_IN_SECONDS );

			return $count;
		}

		/**
		 * Whether estimate() stopped counting before reaching the end.
		 *
		 * @return bool
		 */
		public static function estimate_is_capped() {
			return self::estimate() > self::ESTIMATE_CAP;
		}

		/**
		 * @return void
		 */
		public static function flush_estimate() {
			delete_transient( self::ESTIMATE_KEY );
		}

		/**
		 * Whether to offer the cleanup on Overview.
		 *
		 * Only when it would actually reclaim something, the site has not already
		 * opted in, and nobody has waved it away.
		 *
		 * @return bool
		 */
		public static function should_suggest() {
			if ( ! self::can_run() || self::is_enabled() ) {
				return false;
			}

			if ( get_option( self::DISMISSED_OPTION, false ) ) {
				return false;
			}

			return self::estimate() >= self::SUGGEST_THRESHOLD;
		}

		/**
		 * @return void
		 */
		public static function dismiss() {
			update_option( self::DISMISSED_OPTION, 1, false );
		}

		/**
		 * Retention window in days.
		 *
		 * @param int $days Optional override. 0 = default.
		 * @return int
		 */
		public static function days( $days = 0 ) {
			$days = absint( $days );

			if ( $days < 1 ) {
				/**
				 * Filters how long a guest's cached vote map is kept after their
				 * last recorded vote.
				 *
				 * @param int $days Default 90.
				 */
				$days = absint( apply_filters( 'wp_ulike_guest_cache_retention_days', self::DAYS_DEFAULT ) );
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
				wp_schedule_event( time() + ( 2 * HOUR_IN_SECONDS ), 'daily', self::HOOK );
			}
		}

		/**
		 * Daily nibble. A full pass across a large table spans several days,
		 * which is fine: the cursor picks up where the last run stopped.
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

			return self::run(
				array(
					'dry_run'     => false,
					'max_batches' => self::CRON_BATCHES,
				)
			);
		}

		/**
		 * Walk the meta table by primary key and drop dormant guest rows.
		 *
		 * @param array<string,mixed> $args dry_run, days, batch_size, max_batches.
		 * @return array<string,mixed>
		 */
		public static function run( $args = array() ) {
			global $wpdb;

			$args = wp_parse_args(
				$args,
				array(
					'dry_run'     => true,
					'days'        => 0,
					'batch_size'  => 0,
					'max_batches' => 0,
				)
			);

			if ( ! class_exists( 'WP_Ulike_Meta_Schema' ) || ! WP_Ulike_Meta_Schema::table_exists() ) {
				return array(
					'ok'      => false,
					'message' => 'meta table missing',
				);
			}

			if ( ! class_exists( 'WP_Ulike_Pulse_Schema' ) || ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return array(
					'ok'      => false,
					'message' => 'pulse table missing',
				);
			}

			$meta_keys = self::status_meta_keys();

			if ( empty( $meta_keys ) ) {
				return array(
					'ok'      => true,
					'deleted' => 0,
					'scanned' => 0,
					'message' => 'nothing to scan',
				);
			}

			$dry_run     = ! empty( $args['dry_run'] );
			$days        = self::days( $args['days'] );
			$cutoff      = self::cutoff( $days );
			$batch_size  = absint( $args['batch_size'] );
			$batch_size  = $batch_size > 0 ? min( $batch_size, self::BATCH_MAX ) : self::BATCH_DEFAULT;
			$max_batches = absint( $args['max_batches'] );

			$meta_table  = WP_Ulike_Meta_Schema::table();
			$pulse_table = WP_Ulike_Pulse_Schema::table();
			$cursor      = $dry_run ? 0 : absint( get_option( self::CURSOR_OPTION, 0 ) );

			$key_placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );

			$deleted = 0;
			$scanned = 0;
			$batches = 0;

			while ( true ) {
				if ( $max_batches > 0 && $batches >= $max_batches ) {
					break;
				}

				// Rows whose item_id matches no real user are guest identities.
				// A guest hash that happens to collide with a user ID is kept —
				// erring toward keeping a row costs nothing.
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT m.meta_id, m.item_id
						 FROM `{$meta_table}` m
						 LEFT JOIN `{$wpdb->users}` u ON u.ID = m.item_id
						 WHERE m.meta_group = 'user'
						   AND m.meta_key IN ( {$key_placeholders} )
						   AND m.meta_id > %d
						   AND u.ID IS NULL
						 ORDER BY m.meta_id ASC
						 LIMIT %d",
						array_merge( $meta_keys, array( $cursor, $batch_size ) )
					)
				);

				if ( empty( $rows ) ) {
					// End of table: next run starts a fresh pass.
					if ( ! $dry_run ) {
						update_option( self::CURSOR_OPTION, 0, false );
					}
					$cursor = 0;
					break;
				}

				++$batches;
				$scanned += count( $rows );

				$by_identity = array();
				foreach ( $rows as $row ) {
					$by_identity[ (string) $row->item_id ][] = (int) $row->meta_id;
					$cursor                                  = max( $cursor, (int) $row->meta_id );
				}

				$identities = array_keys( $by_identity );
				$active     = self::active_identities( $pulse_table, $identities, $cutoff );
				$dormant    = array_diff( $identities, $active );

				if ( ! empty( $dormant ) ) {
					$meta_ids = array();
					foreach ( $dormant as $identity ) {
						foreach ( $by_identity[ $identity ] as $meta_id ) {
							$meta_ids[] = $meta_id;
						}
					}

					if ( $dry_run ) {
						$deleted += count( $meta_ids );
					} else {
						$id_placeholders = implode( ', ', array_fill( 0, count( $meta_ids ), '%d' ) );
						$removed         = $wpdb->query(
							$wpdb->prepare(
								"DELETE FROM `{$meta_table}` WHERE meta_id IN ( {$id_placeholders} )",
								$meta_ids
							)
						);

						$deleted += absint( $removed );

						// Drop the object cache for those identities so a
						// persistent cache does not keep serving removed rows.
						foreach ( $dormant as $identity ) {
							wp_cache_delete( absint( $identity ), 'wp_ulike_user_meta' );
						}
					}
				}
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				if ( ! $dry_run ) {
					update_option( self::CURSOR_OPTION, $cursor, false );
				}
			}

			if ( ! $dry_run && $deleted > 0 ) {
				self::flush_estimate();
			}

			return array(
				'ok'      => true,
				'deleted' => $deleted,
				'scanned' => $scanned,
				'batches' => $batches,
				'days'    => $days,
				'cutoff'  => $cutoff,
				'cursor'  => $cursor,
				'dry_run' => $dry_run,
			);
		}

		/**
		 * Which of these identities voted since the cutoff.
		 *
		 * @param string   $pulse_table Pulse table name.
		 * @param string[] $identities  Identity strings (pulse stores user_id as varchar).
		 * @param string   $cutoff      UTC datetime.
		 * @return string[]
		 */
		private static function active_identities( $pulse_table, $identities, $cutoff ) {
			global $wpdb;

			if ( empty( $identities ) ) {
				return array();
			}

			$placeholders = implode( ', ', array_fill( 0, count( $identities ), '%s' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$found = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT user_id
					 FROM `{$pulse_table}`
					 WHERE user_id IN ( {$placeholders} )
					   AND date_time >= %s",
					array_merge( $identities, array( $cutoff ) )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return is_array( $found ) ? array_map( 'strval', $found ) : array();
		}

		/**
		 * Status meta keys present in the `user` group.
		 *
		 * Read from the table rather than hardcoded so add-on content types are
		 * covered, and so the scan can filter on an indexed `IN` list instead of
		 * a `LIKE '%_status'` that cannot use the index.
		 *
		 * @return string[]
		 */
		private static function status_meta_keys() {
			global $wpdb;

			$table = WP_Ulike_Meta_Schema::table();

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$keys = $wpdb->get_col(
				"SELECT DISTINCT meta_key FROM `{$table}` WHERE meta_group = 'user'"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! is_array( $keys ) ) {
				return array();
			}

			$status_keys = array();
			foreach ( $keys as $key ) {
				$key = (string) $key;
				if ( '_status' === substr( $key, -7 ) ) {
					$status_keys[] = $key;
				}
			}

			return $status_keys;
		}
	}
}
