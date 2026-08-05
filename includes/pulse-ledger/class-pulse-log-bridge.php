<?php
/**
 * Pulse Ledger — admin logs, charts, and privacy data bridge.
 *
 * Returns legacy-shaped rows so existing admin formatters keep working.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Log_Bridge' ) ) {

	final class WP_Ulike_Pulse_Log_Bridge {

		/**
		 * Resolve a legacy table suffix or canonical item type to a source config.
		 *
		 * @param string $identifier Legacy suffix (ulike, ulike_comments) or item type (post, comment).
		 * @return array<string,mixed>|null
		 */
		public static function source_for_suffix( $identifier ) {
			global $wpdb;

			$identifier = str_replace( $wpdb->prefix, '', (string) $identifier );

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				if ( str_replace( $wpdb->prefix, '', $source['table'] ) === $identifier ) {
					return $source;
				}
			}

			$item_type = WP_Ulike_Pulse_Registry::type_by_table_suffix( $identifier );
			if ( ! $item_type ) {
				$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $identifier );
			}

			return WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param int    $page         Page number (1-based).
		 * @param int    $per_page     Rows per page.
		 * @param array  $sort         field + type.
		 * @return array<int,object>
		 */
		public static function get_log_rows( $table_suffix, $page = 1, $per_page = 15, $sort = array(), $search = '' ) {
			$per_page = max( 1, absint( $per_page ) );
			$page     = max( 1, absint( $page ) );
			$offset   = ( $page - 1 ) * $per_page;

			return self::query_log_rows( $table_suffix, $sort, $search, $per_page, $offset );
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param array  $sort         Sort args.
		 * @return array<int,object>
		 */
		public static function get_all_log_rows( $table_suffix, $sort = array(), $search = '' ) {
			$max = (int) apply_filters( 'wp_ulike_logs_export_max', 5000 );
			return self::query_log_rows( $table_suffix, $sort, $search, max( 1, $max ), 0 );
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param int    $row_id       Row ID.
		 * @return object|null
		 */
		public static function get_log_row( $table_suffix, $row_id ) {
			$row_id = absint( $row_id );
			if ( ! $row_id ) {
				return null;
			}

			$source = self::source_for_suffix( $table_suffix );
			if ( ! $source ) {
				return null;
			}

			$mode = WP_Ulike_Pulse_Query::read_mode();

			if ( 'pulse' === $mode || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return self::get_pulse_row_by_id( $source, $row_id );
			}

			if ( 'legacy' === $mode ) {
				return self::get_legacy_row_by_id( $source, $row_id );
			}

			$row = self::get_pulse_row_by_id( $source, $row_id );
			return $row ? $row : self::get_legacy_row_by_id( $source, $row_id );
		}

		/**
		 * Paginated log rows via SQL (never loads the full table into PHP).
		 *
		 * @param string   $table_suffix Legacy table suffix.
		 * @param array    $sort         Sort args.
		 * @param string   $search       Search term.
		 * @param int|null $limit        Max rows.
		 * @param int      $offset       Offset.
		 * @return array<int,object>
		 */
		private static function query_log_rows( $table_suffix, $sort = array(), $search = '', $limit = 15, $offset = 0 ) {
			global $wpdb;

			$source = self::source_for_suffix( $table_suffix );
			if ( ! $source ) {
				return array();
			}

			$allowed  = array( 'id', 'date_time', 'user_id', 'ip', 'status' );
			$order_by = isset( $sort['field'] ) && in_array( $sort['field'], $allowed, true )
				? esc_sql( $sort['field'] )
				: 'date_time';
			$order_dir = ( isset( $sort['type'] ) && 'ASC' === strtoupper( $sort['type'] ) ) ? 'ASC' : 'DESC';

			$search_sql = '';
			if ( '' !== $search ) {
				$search_sql = self::search_sql_for_source( $source, $search );
			}

			// Push the same ORDER BY + a bounding LIMIT (offset+limit) into
			// every UNION arm, so each arm materializes at most that many
			// rows instead of its entire per-type history before the outer
			// query re-sorts/paginates. Safe because the true top-N across a
			// UNION is always contained within each arm's own top-N taken
			// independently (any row ranked <= N globally can only rank
			// <= N within its own arm too) -- standard top-K-from-union
			// technique.
			//
			// Only valid when NO search is active. The search fragment filters
			// on the legacy item column (e.g. post_id), which in the pulse arm
			// is only an output alias of item_id -- and SQL cannot reference a
			// SELECT alias from WHERE. Bounding the arms while filtering only
			// on the outside would also drop legitimate matches ranked below
			// each arm's top-N. So when searching, leave the arms unbounded and
			// let the outer query do the filtering, ordering and pagination.
			$arm_order_sql = '';
			if ( null !== $limit && '' === $search_sql ) {
				$bound         = max( 1, absint( $offset ) + absint( $limit ) );
				$arm_order_sql = $wpdb->prepare( " ORDER BY `{$order_by}` {$order_dir}, id DESC LIMIT %d", $bound );
			}

			$parts = self::log_union_parts( $source, $arm_order_sql );
			if ( empty( $parts ) ) {
				return array();
			}

			$limit_sql = '';
			if ( null !== $limit ) {
				$limit_sql = $wpdb->prepare( ' LIMIT %d OFFSET %d', absint( $limit ), absint( $offset ) );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = 'SELECT * FROM ( ' . implode( ' UNION ALL ', $parts ) . " ) AS logs WHERE 1=1 {$search_sql} ORDER BY `{$order_by}` {$order_dir}, id DESC {$limit_sql}";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $sql );
			if ( empty( $rows ) ) {
				return array();
			}

			$output = array();
			foreach ( $rows as $row ) {
				if ( isset( $row->_kind ) && 'vote' !== $row->_kind ) {
					if ( isset( $row->_val ) && null !== $row->_val && '' !== $row->_val ) {
						$row->value = (int) $row->_val;
					}
				}
				unset( $row->_kind, $row->_val );
				$output[] = $row;
			}

			return $output;
		}

		/**
		 * Build UNION ALL arms for admin log lists (legacy-shaped columns).
		 *
		 * Search filtering is deliberately NOT done here -- it references the
		 * legacy item column, which the pulse arm only exposes as an output
		 * alias, so it must stay on the outer query (see query_log_rows()).
		 *
		 * @param array<string,mixed> $source        Legacy source.
		 * @param string              $arm_order_sql Fully-prepared " ORDER BY ... LIMIT n" fragment,
		 *                                            or ''. Pushed into every arm so each materializes
		 *                                            at most that many rows (see query_log_rows()).
		 * @return array<int,string> Each element is a parenthesized SELECT, ready for `UNION ALL`.
		 */
		private static function log_union_parts( array $source, $arm_order_sql = '' ) {
			global $wpdb;

			$mode   = WP_Ulike_Pulse_Query::read_mode();
			$column = esc_sql( $source['column'] );
			$parts  = array();

			// $arm_order_sql is already fully prepared by the caller -- appended
			// via string concatenation, never fed back through $wpdb->prepare(),
			// so it cannot be re-interpreted as a format placeholder.

			if ( 'pulse' === $mode || 'merged' === $mode || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				if ( WP_Ulike_Pulse_Schema::table_exists() ) {
					$pulse = esc_sql( WP_Ulike_Pulse_Schema::table() );
					$since = ( 'merged' === $mode ) ? WP_Ulike_Pulse_Config::dual_since() : '';
					if ( 'merged' === $mode && $since ) {
						$kind_sql = $wpdb->prepare(
							" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) )",
							WP_Ulike_Pulse_Registry::KIND_VOTE,
							$since
						);
					} else {
						$kind_sql = '';
					}

					$base = $wpdb->prepare(
						"SELECT id, date_time,
							CONVERT(user_id USING utf8mb4) AS user_id,
							CONVERT(ip USING utf8mb4) AS ip,
							CONVERT(fingerprint USING utf8mb4) AS fingerprint,
							CONVERT(CASE
								WHEN engagement_kind <> 'vote' THEN engagement_key
								WHEN engagement_key = 'dislike' AND status = 'active' THEN 'dislike'
								WHEN engagement_key = 'dislike' THEN 'undislike'
								WHEN status = 'active' THEN 'like'
								ELSE 'unlike'
							END USING utf8mb4) AS status,
							item_id AS `{$column}`,
							CONVERT(engagement_kind USING utf8mb4) AS _kind,
							value AS _val
						FROM `{$pulse}`
						WHERE item_type = %s {$kind_sql}",
						$source['item_type']
					);
					$parts[] = '(' . $base . $arm_order_sql . ')';
				}
			} elseif ( 'legacy' === $mode && WP_Ulike_Pulse_Schema::table_exists() ) {
				$pulse = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$base  = $wpdb->prepare(
					"SELECT id, date_time,
						CONVERT(user_id USING utf8mb4) AS user_id,
						CONVERT(ip USING utf8mb4) AS ip,
						CONVERT(fingerprint USING utf8mb4) AS fingerprint,
						CONVERT(engagement_key USING utf8mb4) AS status,
						item_id AS `{$column}`,
						CONVERT(engagement_kind USING utf8mb4) AS _kind,
						value AS _val
					FROM `{$pulse}`
					WHERE item_type = %s AND engagement_kind IN ('emoji','star')",
					$source['item_type']
				);
				$parts[] = '(' . $base . $arm_order_sql . ')';
			}

			if ( ( 'legacy' === $mode || 'merged' === $mode )
				&& WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table = esc_sql( $source['table'] );
				// CONVERT every string column: legacy tables from older WordPress
				// installs often carry a different collation than the newer pulse
				// table, and UNION-ing mismatched collations fails outright with
				// "Illegal mix of collations" -- which made the admin Logs screen
				// return zero rows in dual/merged mode.
				$base  = "SELECT id, date_time,
					CONVERT(user_id USING utf8mb4) AS user_id,
					CONVERT(ip USING utf8mb4) AS ip,
					CONVERT(fingerprint USING utf8mb4) AS fingerprint,
					CONVERT(status USING utf8mb4) AS status,
					`{$column}`,
					CONVERT('vote' USING utf8mb4) AS _kind,
					NULL AS _val
					FROM `{$table}`";
				// This arm has no WHERE clause of its own (the legacy table is
				// already scoped to one item type), so start one when a search
				// filter needs appending.
				$parts[] = '(' . $base . $arm_order_sql . ')';
			}

			return $parts;
		}

		/**
		 * Search fragment for union log lists (user, IP, status, content title).
		 *
		 * @param array  $source Legacy source config.
		 * @param string $search Search term.
		 * @return string Prepared AND (...) SQL.
		 */
		private static function search_sql_for_source( array $source, $search ) {
			global $wpdb;

			$search = trim( (string) $search );
			if ( '' === $search ) {
				return '';
			}

			$like      = '%' . $wpdb->esc_like( $search ) . '%';
			$column    = esc_sql( $source['column'] );
			$item_type = isset( $source['item_type'] ) ? $source['item_type'] : '';
			$users     = esc_sql( $wpdb->users );
			$posts     = esc_sql( $wpdb->posts );
			$comments  = esc_sql( $wpdb->comments );

			$user_match = $wpdb->prepare(
				"user_id IN (SELECT ID FROM `{$users}` WHERE user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s)",
				$like,
				$like,
				$like
			);

			$content_match = '';
			switch ( $item_type ) {
				case WP_Ulike_Pulse_Registry::ITEM_COMMENT:
					$content_match = $wpdb->prepare(
						"`{$column}` IN (SELECT comment_ID FROM `{$comments}` WHERE comment_content LIKE %s OR comment_author LIKE %s)",
						$like,
						$like
					);
					break;

				case WP_Ulike_Pulse_Registry::ITEM_ACTIVITY:
					if ( function_exists( 'buddypress' ) || function_exists( 'bp_is_active' ) ) {
						$bp_prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
						$bp_table  = esc_sql( $bp_prefix . 'bp_activity' );
						$content_match = $wpdb->prepare(
							"`{$column}` IN (SELECT id FROM `{$bp_table}` WHERE content LIKE %s OR action LIKE %s)",
							$like,
							$like
						);
					}
					break;

				case WP_Ulike_Pulse_Registry::ITEM_TOPIC:
				case WP_Ulike_Pulse_Registry::ITEM_POST:
				default:
					$content_match = $wpdb->prepare(
						"`{$column}` IN (SELECT ID FROM `{$posts}` WHERE post_title LIKE %s)",
						$like
					);
					break;
			}

			$parts = array(
				$wpdb->prepare( 'CAST(user_id AS CHAR) LIKE %s', $like ),
				$wpdb->prepare( 'ip LIKE %s', $like ),
				$wpdb->prepare( 'CAST(status AS CHAR) LIKE %s', $like ),
				$user_match,
			);
			if ( $content_match ) {
				$parts[] = $content_match;
			}

			return ' AND ( ' . implode( ' OR ', $parts ) . ' )';
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @return int
		 */
		public static function count_log_rows( $table_suffix, $search = '' ) {
			global $wpdb;

			if ( '' === $search ) {
				$item_type = WP_Ulike_Pulse_Registry::type_by_table_suffix( $table_suffix );
				if ( ! $item_type ) {
					$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $table_suffix );
				}
				if ( ! $item_type ) {
					return 0;
				}
				return WP_Ulike_Pulse_Query::count_logs_for_type( $item_type, 'all' );
			}

			$source = self::source_for_suffix( $table_suffix );
			if ( ! $source ) {
				return 0;
			}

			// Reuse list SQL without LIMIT by counting the union. The search
			// filter is applied on the OUTER query only: it references the
			// legacy item column, which inside the pulse arm exists only as an
			// output alias (see query_log_rows()).
			$search_sql = self::search_sql_for_source( $source, $search );

			$parts = self::log_union_parts( $source );
			if ( empty( $parts ) ) {
				return 0;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var(
				'SELECT COUNT(*) FROM ( ' . implode( ' UNION ALL ', $parts ) . " ) AS logs WHERE 1=1 {$search_sql}"
			);
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param int[]  $row_ids      Row IDs.
		 * @return int Rows deleted.
		 */
		public static function delete_log_rows( $table_suffix, array $row_ids ) {
			$deleted = 0;

			foreach ( $row_ids as $row_id ) {
				if ( self::delete_log_row( $table_suffix, $row_id ) ) {
					++$deleted;
				}
			}

			return $deleted;
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param int    $row_id       Row ID.
		 * @return int|false
		 */
		public static function delete_log_row( $table_suffix, $row_id ) {
			global $wpdb;

			$row_id = absint( $row_id );
			$source = self::source_for_suffix( $table_suffix );
			if ( ! $row_id || ! $source ) {
				return false;
			}

			if ( WP_Ulike_Pulse_Schema::table_exists() ) {
				$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$removed     = $wpdb->delete(
					WP_Ulike_Pulse_Schema::table(),
					array(
						'id'        => $row_id,
						'item_type' => $source['item_type'],
					),
					array( '%d', '%s' )
				);
				if ( $removed ) {
					return $removed;
				}
			}

			if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table = esc_sql( $source['table'] );
				return $wpdb->delete( $source['table'], array( 'id' => $row_id ), array( '%d' ) );
			}

			return false;
		}

		/**
		 * Daily chart dataset for statistics admin.
		 *
		 * @param string $table_suffix Legacy table suffix.
		 * @param int    $data_limit   Number of days.
		 * @return array<int,object>
		 */
		public static function get_chart_dataset( $table_suffix, $data_limit = 30 ) {
			global $wpdb;

			$source = self::source_for_suffix( $table_suffix );
			if ( ! $source ) {
				return array();
			}

			$data_limit = max( 1, absint( $data_limit ) );
			$mode       = WP_Ulike_Pulse_Query::read_mode();
			$counts     = array();

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$counts = self::legacy_daily_counts( $source['table'], $data_limit );
				}
			}

		if ( 'pulse' === $mode || 'merged' === $mode ) {
			$since  = 'merged' === $mode ? WP_Ulike_Pulse_Config::dual_since() : '';
			$pulse  = self::pulse_daily_counts( $source['item_type'], $data_limit, $since );
			foreach ( $pulse as $date => $count ) {
				if ( ! isset( $counts[ $date ] ) ) {
					$counts[ $date ] = 0;
				}
				$counts[ $date ] += $count;
			}
		} elseif ( 'legacy' === $mode ) {
			// Emoji/star live in pulse even in legacy read mode; merge their
			// daily counts so the chart reflects all interaction kinds.
			$pulse = self::pulse_daily_counts( $source['item_type'], $data_limit, '' );
			foreach ( $pulse as $date => $count ) {
				if ( ! isset( $counts[ $date ] ) ) {
					$counts[ $date ] = 0;
				}
				$counts[ $date ] += $count;
			}
		}

			return self::counts_to_chart_rows( $counts );
		}

		/**
		 * Hour-of-day distribution for stats (last 30 days).
		 *
		 * @param string[] $table_suffixes Active log table suffixes.
		 * @return array<int,object>
		 */
		public static function get_peak_hours_rows( array $table_suffixes ) {
			global $wpdb;

			$mode         = WP_Ulike_Pulse_Query::read_mode();
			$union_parts  = array();
			$period_limit = ' AND date_time >= DATE_SUB( NOW(), INTERVAL 30 DAY )';

			foreach ( $table_suffixes as $suffix ) {
				$source = self::source_for_suffix( $suffix );
				if ( ! $source ) {
					continue;
				}

				if ( 'legacy' === $mode || 'merged' === $mode ) {
					if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						$table         = esc_sql( $source['table'] );
						$union_parts[] = "SELECT date_time FROM `{$table}` WHERE 1=1 {$period_limit}";
					}
				}

		if ( ( 'pulse' === $mode || 'merged' === $mode ) && WP_Ulike_Pulse_Schema::table_exists() ) {
			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			// Scope only vote rows to dual_since; emoji/star have no legacy
			// counterpart and must never be since-filtered in merged mode.
			$since_sql = '';
			if ( 'merged' === $mode && WP_Ulike_Pulse_Config::dual_since() ) {
				$since_sql = $wpdb->prepare(
					" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) )",
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					WP_Ulike_Pulse_Config::dual_since()
				);
			}
			// All engagement kinds so peak hours reflect total activity.
			$union_parts[] = $wpdb->prepare(
				"SELECT date_time FROM `{$pulse_table}`
				WHERE item_type = %s {$period_limit} {$since_sql}",
				$source['item_type']
			);
		} elseif ( 'legacy' === $mode && WP_Ulike_Pulse_Schema::table_exists() ) {
				// Legacy mode: votes come from legacy tables; add pulse emoji/star.
				$pulse_table   = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$union_parts[] = $wpdb->prepare(
					"SELECT date_time FROM `{$pulse_table}`
					WHERE item_type = %s AND engagement_kind IN ('emoji','star') {$period_limit}",
					$source['item_type']
				);
			}
			}

			if ( empty( $union_parts ) ) {
				return array();
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments use registered table names only.
			return $wpdb->get_results(
				sprintf(
					'SELECT HOUR(date_time) AS hour_slot, COUNT(*) AS total_count
					FROM ( %s ) AS combined
					GROUP BY hour_slot
					ORDER BY hour_slot ASC',
					implode( ' UNION ALL ', $union_parts )
				)
			);
		}

		/**
		 * @param string $table_name Full legacy table name.
		 * @param int    $data_limit Days.
		 * @return array<string,int>
		 */
		private static function legacy_daily_counts( $table_name, $data_limit ) {
			global $wpdb;

			$table  = esc_sql( $table_name );
			$latest = $wpdb->get_var( "SELECT MAX(date_time) FROM `{$table}`" );
			if ( ! $latest ) {
				return array();
			}

			$start = date( 'Y-m-d H:i:s', strtotime( $latest ) - ( $data_limit * DAY_IN_SECONDS ) );
			$counts = array();

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(date_time) AS labels, COUNT(date_time) AS counts
					FROM `{$table}` WHERE date_time >= %s AND date_time <= %s
					GROUP BY labels ORDER BY labels ASC",
					$start,
					$latest
				)
			);

			foreach ( (array) $rows as $row ) {
				$counts[ $row->labels ] = (int) $row->counts;
			}

			return $counts;
		}

		/**
		 * @param string $item_type  Pulse item type.
		 * @param int    $data_limit Days.
		 * @param string $since      Optional datetime floor (merged mode).
		 * @return array<string,int>
		 */
		private static function pulse_daily_counts( $item_type, $data_limit, $since = '' ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return array();
			}

	$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
	// Scope only vote rows to $since (dual_since); emoji/star have no legacy
	// counterpart and must never be since-filtered in merged mode.
	$since_sql   = '';
	if ( $since ) {
		$since_sql = $wpdb->prepare(
			" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) )",
			WP_Ulike_Pulse_Registry::KIND_VOTE,
			$since
		);
	}
	$latest      = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT MAX(date_time) FROM `{$pulse_table}` WHERE item_type = %s {$since_sql}",
			$item_type
		)
	);

		if ( ! $latest ) {
			return array();
		}

		$start  = date( 'Y-m-d H:i:s', strtotime( $latest ) - ( $data_limit * DAY_IN_SECONDS ) );
		$counts = array();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(date_time) AS labels, COUNT(date_time) AS counts
				FROM `{$pulse_table}`
				WHERE item_type = %s
				AND date_time >= %s AND date_time <= %s {$since_sql}
				GROUP BY labels ORDER BY labels ASC",
				$item_type,
				$start,
				$latest
			)
		);

			foreach ( (array) $rows as $row ) {
				$counts[ $row->labels ] = (int) $row->counts;
			}

			return $counts;
		}

		/**
		 * @param array<string,int> $counts Date => count.
		 * @return array<int,object>
		 */
		private static function counts_to_chart_rows( array $counts ) {
			if ( empty( $counts ) ) {
				return array();
			}

			ksort( $counts );
			$rows = array();
			foreach ( $counts as $date => $count ) {
				$rows[] = (object) array(
					'labels' => $date,
					'counts' => $count,
				);
			}

			return $rows;
		}

		/**
		 * GDPR export rows for one user.
		 *
		 * Uses a single UNION ALL query across legacy + pulse sources so
		 * pagination happens in SQL — not in PHP memory. This avoids blowing
		 * the memory limit on power users with very large vote histories.
		 *
		 * @param string $user_id  WordPress user ID as string.
		 * @param int    $page     Page (1-based).
		 * @param int    $per_page Rows per page.
		 * @return array<int,array<string,mixed>>
		 */
	public static function get_privacy_rows( $user_id, $page = 1, $per_page = 100 ) {
		global $wpdb;

		$user_id  = (string) $user_id;
		$page     = max( 1, (int) $page );
		$per_page = max( 1, (int) $per_page );
		$offset   = ( $page - 1 ) * $per_page;
		$mode     = WP_Ulike_Pulse_Query::read_mode();
		$union    = array();

		foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $source ) {
			$suffix = str_replace( $wpdb->prefix, '', $source['table'] );

		if ( ( 'legacy' === $mode || 'merged' === $mode )
			&& WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
			$table     = esc_sql( $source['table'] );
			$geo_cols  = self::legacy_personal_columns_sql( $source['table'] );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$union[] = $wpdb->prepare(
				"SELECT %s AS src, id, date_time, status, ip, NULL AS _ek, 'vote' AS _kind, NULL AS _val, {$geo_cols}
				FROM `{$table}` WHERE user_id = %s",
				$suffix,
				$user_id
			);
		}

		// Emoji/star rows live in pulse in EVERY storage mode, and pulse-mode
		// votes live there too. Always include pulse so the personal data
		// export is complete; in merged mode scope only vote rows to
		// dual_since (emoji/star have no legacy equivalent to double-count).
		if ( WP_Ulike_Pulse_Schema::table_exists() ) {
			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$dual_since  = WP_Ulike_Pulse_Config::dual_since();

			if ( 'legacy' === $mode ) {
				// Votes come from legacy tables; pulse contributes emoji/star only.
				$kind_clause = "AND engagement_kind IN ('emoji','star')";
				$since_sql   = '';
			} elseif ( 'merged' === $mode && $dual_since ) {
				// Emoji/star: all rows. Votes: since dual_since only.
				$kind_clause = '';
				$since_sql   = $wpdb->prepare(
					" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = 'vote' AND date_time >= %s ) )",
					$dual_since
				);
			} else {
				// pulse mode: every row, every kind.
				$kind_clause = '';
				$since_sql   = '';
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$union[] = $wpdb->prepare(
				"SELECT %s AS src, id, date_time, status, ip, engagement_key AS _ek,
					engagement_kind AS _kind, value AS _val,
					fingerprint, country_code, device, os, browser
				FROM `{$pulse_table}`
				WHERE user_id = %s AND item_type = %s {$kind_clause}{$since_sql}",
				$suffix,
				$user_id,
				$source['item_type']
			);
		}
		}

		if ( empty( $union ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments are prepared individually.
		$sql = sprintf(
			'SELECT src, id, date_time, status, ip, _ek, _kind, _val, fingerprint, country_code, device, os, browser
			FROM (%s) AS combined
			ORDER BY date_time DESC, id DESC
			LIMIT %d OFFSET %d',
			implode( ' UNION ALL ', $union ),
			$per_page,
			$offset
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$rows = array();
		foreach ( (array) $results as $row ) {
			$status = $row['status'];
			$kind   = isset( $row['_kind'] ) ? $row['_kind'] : 'vote';
			// Only classic vote rows carry a legacy-compatible status mapping.
			// Emoji/star rows keep their native status and surface their key/value.
			if ( 'vote' === $kind && isset( $row['_ek'] ) && null !== $row['_ek'] ) {
				$status = WP_Ulike_Pulse_Vote_Map::row_to_legacy( $row['_ek'], $row['status'] );
			}
			$rows[] = array(
				'src'           => $row['src'],
				'id'            => (int) $row['id'],
				'date_time'     => $row['date_time'],
				'status'        => $status,
				'engagement_kind' => $kind,
				'engagement_key'  => isset( $row['_ek'] ) ? $row['_ek'] : null,
				'value'          => isset( $row['_val'] ) ? $row['_val'] : null,
				'ip'            => $row['ip'],
				'fingerprint'   => isset( $row['fingerprint'] ) ? $row['fingerprint'] : null,
				'country_code'  => isset( $row['country_code'] ) ? $row['country_code'] : null,
				'device'        => isset( $row['device'] ) ? $row['device'] : null,
				'os'            => isset( $row['os'] ) ? $row['os'] : null,
				'browser'       => isset( $row['browser'] ) ? $row['browser'] : null,
			);
		}

		return $rows;
	}

	/**
	 * Build the optional personal-data column list for a legacy vote table.
	 *
	 * Legacy tables always have `fingerprint` after the legacy upgrade, but the
	 * geo/device columns (`country_code`, `device`, `os`, `browser`) are only
	 * present when Pro ensured them. Missing columns are projected as NULL so
	 * the UNION shape stays stable without SQL errors.
	 *
	 * @param string $table Full legacy table name.
	 * @return string SQL fragment, e.g. "`fingerprint` AS fingerprint, NULL AS country_code, ...".
	 */
	private static $legacy_personal_columns_cache = array();

	public static function legacy_personal_columns_sql( $table ) {
		if ( ! isset( self::$legacy_personal_columns_cache[ $table ] ) ) {
			global $wpdb;
			$present = array();
			$cols    = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
					WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s
					AND COLUMN_NAME IN ('fingerprint','country_code','device','os','browser')",
					DB_NAME,
					$table
				),
				ARRAY_A
			);
			foreach ( (array) $cols as $c ) {
				$present[ $c['COLUMN_NAME'] ] = true;
			}
			self::$legacy_personal_columns_cache[ $table ] = $present;
		}

		$present = self::$legacy_personal_columns_cache[ $table ];
		$names   = array( 'fingerprint', 'country_code', 'device', 'os', 'browser' );
		$parts   = array();
		foreach ( $names as $name ) {
			$parts[] = isset( $present[ $name ] ) ? "`{$name}` AS {$name}" : "NULL AS {$name}";
		}
		return implode( ', ', $parts );
	}

		/**
		 * GDPR erase — remove all vote rows for a user.
		 *
		 * @param string $user_id WordPress user ID as string.
		 * @return int
		 */
	public static function erase_user_logs( $user_id ) {
		global $wpdb;

		$user_id = (string) $user_id;
		$total   = 0;

		// Pulse: remove ALL engagement kinds for this user (vote, emoji, star)
		// whenever the table exists — regardless of read mode. A site can be in
		// legacy read mode while a pulse table exists (partial migration), and a
		// GDPR erase must not leave personal data behind in either store.
		if ( WP_Ulike_Pulse_Schema::table_exists() ) {
			$result = $wpdb->delete(
				WP_Ulike_Pulse_Schema::table(),
				array( 'user_id' => $user_id ),
				array( '%s' )
			);
			if ( false !== $result ) {
				$total += (int) $result;
			}
		}

		// Legacy: always delete when the tables exist, regardless of read mode.
		// Legacy rows survive cutover until "Drop legacy tables" runs, and a
		// privacy erase must never leave personal data behind in frozen tables.
		foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
			if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				continue;
			}
			$table  = esc_sql( $source['table'] );
			$result = $wpdb->query(
				$wpdb->prepare( "DELETE FROM `{$table}` WHERE user_id = %s", $user_id )
			);
			if ( false !== $result ) {
				$total += (int) $result;
			}
		}

		return $total;
	}

		/**
		 * Earliest vote timestamp across active storage.
		 *
		 * @return int|null
		 */
		public static function get_earliest_log_timestamp() {
			global $wpdb;

			$selects = array();
			$mode = WP_Ulike_Pulse_Query::read_mode();

			if ( ( 'legacy' === $mode || 'merged' === $mode ) ) {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
					if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						continue;
					}
					$table     = esc_sql( $source['table'] );
					$selects[] = "SELECT MIN(`date_time`) AS dt FROM `{$table}`";
				}
			}

		if ( ( 'pulse' === $mode || 'merged' === $mode ) && WP_Ulike_Pulse_Schema::table_exists() ) {
			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$selects[]   = "SELECT MIN(`date_time`) AS dt FROM `{$pulse_table}`";
		}

		// In legacy read mode, emoji/star live in pulse too — include them so
		// the earliest-activity timestamp reflects every interaction kind.
		if ( 'legacy' === $mode && WP_Ulike_Pulse_Schema::table_exists() ) {
			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$selects[]   = "SELECT MIN(`date_time`) AS dt FROM `{$pulse_table}` WHERE engagement_kind IN ('emoji','star')";
		}

			if ( empty( $selects ) ) {
				return null;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- subqueries use registered table names only.
			$value = $wpdb->get_var(
				'SELECT MIN(dt) FROM (' . implode( ' UNION ALL ', $selects ) . ') AS ulike_earliest WHERE dt IS NOT NULL'
			);

			if ( empty( $value ) ) {
				return null;
			}

			$timestamp = strtotime( $value );

			return false === $timestamp ? null : $timestamp;
		}

		/**
		 * Tables that store vote logs (for health checks and earliest timestamp).
		 *
		 * @return array<string, string> label => full table name.
		 */
		public static function get_storage_tables() {
			global $wpdb;

			$tables = array(
				'meta'  => $wpdb->prefix . 'ulike_meta',
				'pulse' => WP_Ulike_Pulse_Schema::table(),
			);

			if ( WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode()
				&& ! WP_Ulike_Pulse_Legacy_Cleanup::legacy_tables_exist() ) {
				return $tables;
			}

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $source ) {
				if ( WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$tables[ $slug ] = $source['table'];
				}
			}

			return $tables;
		}

		/**
		 * @param array<string,mixed> $source Legacy source.
		 * @param int                   $row_id Row ID.
		 * @return object|null
		 */
		private static function get_legacy_row_by_id( $source, $row_id ) {
			global $wpdb;

			$table = esc_sql( $source['table'] );
			return $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $row_id )
			);
		}

		/**
		 * @param array<string,mixed> $source Legacy source.
		 * @param int                   $row_id Row ID.
		 * @return object|null
		 */
		private static function get_pulse_row_by_id( $source, $row_id ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return null;
			}

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			// No engagement_kind filter so emoji/star single-row views resolve.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$table}` WHERE id = %d AND item_type = %s",
					$row_id,
					$source['item_type']
				)
			);

			return $row ? self::map_pulse_row( $row, $source ) : null;
		}

		/**
		 * @param object              $row    Pulse row.
		 * @param array<string,mixed> $source Legacy source config.
		 * @return object
		 */
		private static function map_pulse_row( $row, $source ) {
			$legacy           = new stdClass();
			$legacy->id         = (int) $row->id;
			$legacy->date_time  = $row->date_time;
			$legacy->user_id    = $row->user_id;
			$legacy->ip         = isset( $row->ip ) ? $row->ip : '';
			$legacy->fingerprint = isset( $row->fingerprint ) ? $row->fingerprint : '';

			// Vote rows map to legacy like/dislike/unlike/undislike labels;
			// emoji/star rows keep their engagement_key as the status label so
			// the logs page shows the actual reaction / rating instead of "like".
			if ( isset( $row->engagement_kind ) && WP_Ulike_Pulse_Registry::KIND_VOTE !== $row->engagement_kind ) {
				$legacy->status = (string) $row->engagement_key;
			} else {
				$legacy->status = WP_Ulike_Pulse_Vote_Map::row_to_legacy(
					$row->engagement_key,
					$row->status
				);
			}

			$column              = $source['column'];
			$legacy->{$column}   = (int) $row->item_id;

			return $legacy;
		}
	}
}
