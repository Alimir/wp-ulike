<?php
/**
 * Pulse Ledger — rebuild cached per-item vote counters from the ledger.
 *
 * Every item keeps its like/dislike totals in `ulike_meta` so a page render is
 * one meta read instead of a COUNT over the ledger. Those rows are a cache:
 * they are seeded on first read and then adjusted by each vote.
 *
 * An adjustment can be missed — a restored backup, a row deleted straight from
 * the database, an interrupted migration, a crash between the ledger write and
 * the counter update. Nothing recomputed them afterwards, so once a number was
 * wrong it stayed wrong: votes only ever adjust it further from the truth, and
 * the visible like count is the one thing a visitor actually reads.
 *
 * This pass compares each cached counter with the ledger and rewrites the ones
 * that disagree. It only ever touches `count_*` cache rows, and only rows that
 * already exist — a counter that was never materialised stays absent so it is
 * still created lazily on first read.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Counter_Repair' ) ) {

	final class WP_Ulike_Pulse_Counter_Repair {

		const BATCH_DEFAULT = 200;
		const BATCH_MIN     = 20;
		const BATCH_MAX     = 2000;

		/**
		 * Cache keys this pass understands, mapped to how they are counted.
		 *
		 * @return array<string,array{key:string,distinct:bool}>
		 */
		private static function counter_map() {
			return array(
				'count_distinct_like'    => array( 'key' => 'like',    'distinct' => true ),
				'count_distinct_dislike' => array( 'key' => 'dislike', 'distinct' => true ),
				'count_total_like'       => array( 'key' => 'like',    'distinct' => false ),
				'count_total_dislike'    => array( 'key' => 'dislike', 'distinct' => false ),
			);
		}

		/**
		 * Whether the ledger is the source of truth right now.
		 *
		 * In legacy or dual mode the pulse table does not hold the whole history,
		 * so "recomputing" from it would replace correct numbers with low ones.
		 *
		 * @return bool
		 */
		public static function can_run() {
			if ( ! class_exists( 'WP_Ulike_Pulse_Config' ) || ! class_exists( 'WP_Ulike_Pulse_Schema' ) ) {
				return false;
			}

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return false;
			}

			if ( WP_Ulike_Pulse_Config::READ_PULSE !== WP_Ulike_Pulse_Config::read_mode() ) {
				return false;
			}

			return ! WP_Ulike_Pulse_Config::migration_running();
		}

		/**
		 * Compare cached counters against the ledger, fixing the ones that differ.
		 *
		 * @param array<string,mixed> $args {
		 *     @type bool $dry_run     Report without writing. Default true.
		 *     @type int  $batch_size  Cache rows per pass.
		 *     @type int  $max_batches 0 = until done.
		 *     @type int  $cursor      Resume from this meta_id.
		 * }
		 * @return array<string,mixed>
		 */
		public static function run( array $args = array() ) {
			global $wpdb;

			$args = wp_parse_args(
				$args,
				array(
					'dry_run'     => true,
					'batch_size'  => self::BATCH_DEFAULT,
					'max_batches' => 0,
					'cursor'      => 0,
				)
			);

			$dry_run    = ! empty( $args['dry_run'] );
			$batch_size = max( self::BATCH_MIN, min( self::BATCH_MAX, absint( $args['batch_size'] ) ) );
			$max        = absint( $args['max_batches'] );
			$cursor     = absint( $args['cursor'] );

			$base = array(
				'dry_run' => $dry_run,
				'scanned' => 0,
				'fixed'   => 0,
				'cursor'  => $cursor,
			);

			if ( ! self::can_run() ) {
				return array_merge( $base, array( 'ok' => false, 'done' => true, 'message' => 'not_applicable' ) );
			}

			$map        = self::counter_map();
			$meta_table = esc_sql( WP_Ulike_Meta_Schema::table() );
			$pulse      = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$keys       = array_keys( $map );
			$in         = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );

			$scanned = 0;
			$fixed   = 0;
			$batches = 0;
			$samples = array();

			while ( 0 === $max || $batches < $max ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT meta_id, item_id, meta_group, meta_key, meta_value
						 FROM `{$meta_table}`
						 WHERE meta_key IN ( {$in} ) AND meta_id > %d
						 ORDER BY meta_id ASC
						 LIMIT %d",
						array_merge( $keys, array( $cursor, $batch_size ) )
					)
				);

				if ( empty( $rows ) ) {
					return array_merge(
						$base,
						array(
							'ok'      => true,
							'done'    => true,
							'scanned' => $scanned,
							'fixed'   => $fixed,
							'cursor'  => 0,
							'samples' => $samples,
							'message' => 'complete',
						)
					);
				}

				++$batches;

				foreach ( $rows as $row ) {
					$cursor = max( $cursor, (int) $row->meta_id );
					++$scanned;

					if ( ! isset( $map[ $row->meta_key ] ) ) {
						continue;
					}

					$spec    = $map[ $row->meta_key ];
					$count   = $spec['distinct'] ? 'COUNT(DISTINCT user_id)' : 'COUNT(*)';
					$item_id = absint( $row->item_id );

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$truth = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT {$count} FROM `{$pulse}`
							 WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
							   AND engagement_key = %s AND status = 'active'",
							$item_id,
							(string) $row->meta_group,
							WP_Ulike_Pulse_Registry::KIND_VOTE,
							$spec['key']
						)
					);

					if ( (int) $row->meta_value === $truth ) {
						continue;
					}

					++$fixed;

					if ( count( $samples ) < 10 ) {
						$samples[] = sprintf(
							'%s %d %s: %d -> %d',
							$row->meta_group,
							$item_id,
							$row->meta_key,
							(int) $row->meta_value,
							$truth
						);
					}

					if ( ! $dry_run ) {
						wp_ulike_update_meta_data( $item_id, $row->meta_group, $row->meta_key, $truth );
					}
				}
			}

			return array_merge(
				$base,
				array(
					'ok'      => true,
					'done'    => false,
					'scanned' => $scanned,
					'fixed'   => $fixed,
					'cursor'  => $cursor,
					'samples' => $samples,
					'message' => 'partial',
				)
			);
		}
	}
}
