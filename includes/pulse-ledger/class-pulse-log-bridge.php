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
			$rows   = self::query_log_rows( $table_suffix, $sort, $search );
			$offset = max( 0, ( absint( $page ) - 1 ) * absint( $per_page ) );
			$limit  = absint( $per_page );

			return array_slice( $rows, $offset, $limit > 0 ? $limit : null );
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param array  $sort         Sort args.
		 * @return array<int,object>
		 */
		public static function get_all_log_rows( $table_suffix, $sort = array(), $search = '' ) {
			return self::query_log_rows( $table_suffix, $sort, $search );
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
		 * @param string $table_suffix Legacy table suffix.
		 * @param array  $sort         Sort args.
		 * @return array<int,object>
		 */
		private static function query_log_rows( $table_suffix, $sort = array(), $search = '' ) {
			$source = self::source_for_suffix( $table_suffix );
			if ( ! $source ) {
				return array();
			}

			$mode = WP_Ulike_Pulse_Query::read_mode();
			$rows = array();

			if ( 'pulse' === $mode || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$rows = self::fetch_pulse_rows( $source, '' );
			} elseif ( 'legacy' === $mode ) {
				$rows = self::fetch_legacy_rows( $source );
			} else {
				$rows = array_merge(
					self::fetch_legacy_rows( $source ),
					self::fetch_pulse_rows( $source, WP_Ulike_Pulse_Config::dual_since() )
				);
			}

			$rows = self::sort_rows( $rows, $sort );

			if ( '' !== $search ) {
				$rows = self::filter_rows_by_search( $rows, $search );
			}

			return $rows;
		}

		/**
		 * Case-insensitive substring match across user_id / ip / status.
		 *
		 * @param array<int,object> $rows   Rows.
		 * @param string            $search Search term.
		 * @return array<int,object>
		 */
		private static function filter_rows_by_search( array $rows, $search ) {
			$term = function_exists( 'mb_strtolower' )
				? mb_strtolower( (string) $search )
				: strtolower( (string) $search );

			if ( '' === $term ) {
				return $rows;
			}

			return array_values(
				array_filter(
					$rows,
					static function ( $row ) use ( $term ) {
						foreach ( array( 'user_id', 'ip', 'status' ) as $field ) {
							if ( isset( $row->{$field} ) ) {
								$value = function_exists( 'mb_strtolower' )
									? mb_strtolower( (string) $row->{$field} )
									: strtolower( (string) $row->{$field} );
								if ( false !== strpos( $value, $term ) ) {
									return true;
								}
							}
						}
						return false;
					}
				)
			);
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @return int
		 */
		public static function count_log_rows( $table_suffix, $search = '' ) {
			if ( wp_ulike_use_pulse_queries() ) {
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
				return count( self::query_log_rows( $table_suffix, array(), $search ) );
			}

		return count( self::query_log_rows( $table_suffix, array(), $search ) );
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
					$since_sql   = 'merged' === $mode
						? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() )
						: '';
					$union_parts[] = $wpdb->prepare(
						"SELECT date_time FROM `{$pulse_table}`
						WHERE item_type = %s AND engagement_kind = %s {$period_limit} {$since_sql}",
						$source['item_type'],
						WP_Ulike_Pulse_Registry::KIND_VOTE
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
			$since_sql   = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';
			$latest      = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(date_time) FROM `{$pulse_table}` WHERE item_type = %s AND engagement_kind = %s {$since_sql}",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
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
					WHERE item_type = %s AND engagement_kind = %s
					AND date_time >= %s AND date_time <= %s {$since_sql}
					GROUP BY labels ORDER BY labels ASC",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE,
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
					"SELECT %s AS src, id, date_time, status, ip, NULL AS _ek, {$geo_cols}
					FROM `{$table}` WHERE user_id = %s",
					$suffix,
					$user_id
				);
			}

			if ( ( 'pulse' === $mode || 'merged' === $mode ) && WP_Ulike_Pulse_Schema::table_exists() ) {
				$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$since_sql   = 'merged' === $mode
					? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() )
					: '';
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$union[] = $wpdb->prepare(
					"SELECT %s AS src, id, date_time, status, ip, engagement_key AS _ek,
						fingerprint, country_code, device, os, browser
					FROM `{$pulse_table}`
					WHERE user_id = %s AND item_type = %s AND engagement_kind = %s {$since_sql}",
					$suffix,
					$user_id,
					$source['item_type'],
					WP_Ulike_Pulse_Registry::KIND_VOTE
				);
			}
		}

		if ( empty( $union ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments are prepared individually.
		$sql = sprintf(
			'SELECT src, id, date_time, status, ip, _ek, fingerprint, country_code, device, os, browser
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
			if ( isset( $row['_ek'] ) && null !== $row['_ek'] ) {
				$status = WP_Ulike_Pulse_Vote_Map::row_to_legacy( $row['_ek'], $row['status'] );
			}
			$rows[] = array(
				'src'           => $row['src'],
				'id'            => (int) $row['id'],
				'date_time'     => $row['date_time'],
				'status'        => $status,
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
				$selects[]   = $wpdb->prepare(
					"SELECT MIN(`date_time`) AS dt FROM `{$pulse_table}` WHERE engagement_kind = %s",
					WP_Ulike_Pulse_Registry::KIND_VOTE
				);
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
		 * @return array<int,object>
		 */
		private static function fetch_legacy_rows( $source ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return array();
			}

			$table = esc_sql( $source['table'] );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results( "SELECT * FROM `{$table}`" );
		}

		/**
		 * @param array<string,mixed> $source Legacy source.
		 * @param string              $since  Optional datetime filter.
		 * @return array<int,object>
		 */
		private static function fetch_pulse_rows( $source, $since = '' ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return array();
			}

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$table}`
					WHERE item_type = %s AND engagement_kind = %s {$since_sql}
					ORDER BY date_time DESC, id DESC",
					$source['item_type'],
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			$output = array();
			foreach ( (array) $rows as $row ) {
				$output[] = self::map_pulse_row( $row, $source );
			}

			return $output;
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
			$row   = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$table}` WHERE id = %d AND item_type = %s AND engagement_kind = %s",
					$row_id,
					$source['item_type'],
					WP_Ulike_Pulse_Registry::KIND_VOTE
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
			$legacy->status     = WP_Ulike_Pulse_Vote_Map::row_to_legacy(
				$row->engagement_key,
				$row->status
			);

			$column              = $source['column'];
			$legacy->{$column}   = (int) $row->item_id;

			return $legacy;
		}

		/**
		 * @param array<int,object> $rows Rows.
		 * @param array             $sort Sort args.
		 * @return array<int,object>
		 */
		private static function sort_rows( array $rows, $sort ) {
			$allowed_fields = array( 'id', 'date_time', 'user_id', 'ip', 'status' );
			$field          = isset( $sort['field'] ) && in_array( $sort['field'], $allowed_fields, true )
				? $sort['field']
				: 'id';
			$direction      = isset( $sort['type'] ) && 'ASC' === strtoupper( $sort['type'] ) ? 'ASC' : 'DESC';

			usort(
				$rows,
				function ( $a, $b ) use ( $field, $direction ) {
					$av = isset( $a->{$field} ) ? $a->{$field} : '';
					$bv = isset( $b->{$field} ) ? $b->{$field} : '';
					$cmp = strcmp( (string) $av, (string) $bv );
					if ( 'id' === $field || is_numeric( $av ) ) {
						$cmp = (int) $av <=> (int) $bv;
					}
					return 'ASC' === $direction ? $cmp : -$cmp;
				}
			);

			return $rows;
		}
	}
}
