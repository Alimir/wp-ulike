<?php
/**
 * Pulse Ledger — consolidated read/query router.
 *
 * One class replaces the old five-trait Read Gateway. Mode branching is centralized
 * via branch() instead of triplicating every query path.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Query' ) ) {

	final class WP_Ulike_Pulse_Query {

		const CACHE_TTL = 300;

		/**
		 * @return string legacy|merged|pulse
		 */
		public static function read_mode() {
			return WP_Ulike_Pulse_Config::read_mode();
		}

		/**
		 * @return string[]
		 */
		public static function log_table_names() {
			global $wpdb;
			return array(
				$wpdb->prefix . 'ulike',
				$wpdb->prefix . 'ulike_comments',
				$wpdb->prefix . 'ulike_activities',
				$wpdb->prefix . 'ulike_forums',
			);
		}

		/**
		 * Count votes on one item (counter cold-path).
		 *
		 * @param int    $item_id     Item ID.
		 * @param string $type        Setting type.
		 * @param string $status      like|dislike|all.
		 * @param bool   $is_distinct Distinct users.
		 * @param mixed  $date_range  Period.
		 * @return int
		 */
		public static function count_item_votes( $item_id, $type, $status = 'like', $is_distinct = true, $date_range = null ) {
			global $wpdb;

			$item_id      = absint( $item_id );
			$period_limit = wp_ulike_get_period_limit_sql( $date_range );
			$table_info   = wp_ulike_get_table_info( $type );

			if ( empty( $table_info['table'] ) ) {
				return 0;
			}

			$mode = self::read_mode();

			if ( 'pulse' === $mode ) {
				return self::count_pulse_item_votes( $item_id, $type, $status, $is_distinct, $period_limit, '' );
			}

			$table  = esc_sql( $wpdb->prefix . $table_info['table'] );
			$column = esc_sql( $table_info['column'] );
			$count  = $is_distinct ? 'DISTINCT `user_id`' : '*';
			$where  = 'all' === $status
				? self::legacy_active_status_sql( 'status' )
				: $wpdb->prepare( '`status` = %s', $status );

			$legacy = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT({$count}) FROM `{$table}` WHERE {$where} AND `{$column}` = %d {$period_limit}",
					$item_id
				)
			);

			if ( 'legacy' === $mode ) {
				return $legacy;
			}

			if ( $is_distinct ) {
				return self::count_merged_distinct_item( $item_id, $type, $status, $period_limit, $table_info );
			}

			return $legacy + self::count_pulse_item_votes(
				$item_id,
				$type,
				$status,
				false,
				$period_limit,
				WP_Ulike_Pulse_Config::dual_since()
			);
		}

		/**
		 * @param int    $fingerprint Fingerprint.
		 * @param int    $item_id     Item ID.
		 * @param string $type        Setting type.
		 * @return int
		 */
		public static function count_fingerprint_votes( $fingerprint, $item_id, $type ) {
			global $wpdb;

			$settings = wp_ulike_setting_type::get_instance( $type );
			$table    = esc_sql( $wpdb->prefix . $settings->getTableName() );
			$column   = esc_sql( $settings->getColumnName() );

			$legacy = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = %d AND fingerprint = %s",
					absint( $item_id ),
					$fingerprint
				)
			);

			if ( 'legacy' === self::read_mode() ) {
				return $legacy;
			}

			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type   = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$since_sql   = 'merged' === self::read_mode()
				? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() )
				: '';

			$pulse = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$pulse_table}`
					WHERE item_id = %d AND item_type = %s AND fingerprint = %s AND engagement_kind = %s {$since_sql}",
					absint( $item_id ),
					$item_type,
					$fingerprint,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			return $legacy + $pulse;
		}

		/**
		 * @param string $period Period key.
		 * @return int
		 */
		public static function count_logs_for_mode( $period = 'all' ) {
			$mode = self::read_mode();

			if ( 'pulse' === $mode ) {
				return self::count_pulse_logs( $period );
			}

			$legacy = self::count_all_legacy_logs( $period );

			if ( 'legacy' === $mode ) {
				return $legacy;
			}

			return $legacy + self::count_pulse_logs( $period, WP_Ulike_Pulse_Config::dual_since() );
		}

		/**
		 * @param array  $parsed_args       Query args.
		 * @param array  $info_args         Table info.
		 * @param string $period_limit      SQL period.
		 * @param string $user_condition    SQL user filter.
		 * @param string $related_condition SQL related filter.
		 * @param string $limit_records     SQL LIMIT.
		 * @return array|null
		 */
		public static function get_popular_items_from_logs( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records ) {
			if ( 'pulse' === self::read_mode() ) {
				return self::popular_from_pulse( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records );
			}

			if ( 'legacy' === self::read_mode() ) {
				return self::popular_from_legacy( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records );
			}

			return self::popular_from_merged( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records );
		}

		/**
		 * @param array  $parsed_args       Query args.
		 * @param array  $info_args         Table info.
		 * @param string $period_limit      SQL period.
		 * @param string $user_condition    SQL user filter.
		 * @param string $related_condition SQL related filter.
		 * @return int
		 */
		public static function count_popular_items_total( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition ) {
			$items = self::get_popular_items_from_logs(
				array_merge( $parsed_args, array( 'limit' => 0 ) ),
				$info_args,
				$period_limit,
				$user_condition,
				$related_condition,
				''
			);

			return is_array( $items ) ? count( $items ) : 0;
		}

		/**
		 * @param int    $item_id Item ID.
		 * @param string $user_id User ID.
		 * @param string $type    Setting type.
		 * @return object|null
		 */
		public static function get_user_latest_activity( $item_id, $user_id, $type ) {
			global $wpdb;

			$settings = wp_ulike_setting_type::get_instance( $type );
			$mode     = self::read_mode();

			if ( 'pulse' === $mode ) {
				$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
				return $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id, item_id, user_id, date_time,
						engagement_key AS status, ip, fingerprint
						FROM `{$table}`
						WHERE item_id = %d AND item_type = %s AND user_id = %s
						ORDER BY date_time DESC, id DESC LIMIT 1",
						absint( $item_id ),
						WP_Ulike_Pulse_Registry::from_setting_type( $type ),
						(string) $user_id
					)
				);
			}

			$table  = esc_sql( $wpdb->prefix . $settings->getTableName() );
			$column = esc_sql( $settings->getColumnName() );

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, `{$column}` AS item_id, user_id, date_time, status, ip, fingerprint
					FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s ORDER BY id DESC LIMIT 1",
					absint( $item_id ),
					(string) $user_id
				)
			);

			if ( 'merged' !== $mode || ! $row ) {
				return $row;
			}

			$pulse = self::get_user_latest_activity( $item_id, $user_id, $type );
			if ( ! $pulse ) {
				return $row;
			}

			return strtotime( $pulse->date_time ) >= strtotime( $row->date_time ) ? $pulse : $row;
		}

		/**
		 * @param int          $limit  Limit.
		 * @param string       $period Period.
		 * @param int          $offset Offset page.
		 * @param string|array $status Status filter.
		 * @param string       $order  Order direction.
		 * @return array|null
		 */
		public static function get_best_likers( $limit, $period, $offset = 1, $status = array( 'like', 'dislike' ), $order = 'DESC' ) {
			global $wpdb;

			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$statuses     = WP_Ulike_Pulse_Vote_Map::normalize_status_filter( $status );
			$status_in    = implode(
				',',
				array_map(
					function ( $s ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $s );
					},
					$statuses
				)
			);

			$union = array();
			foreach ( wp_ulike_get_log_table_names() as $table ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $table ) ) {
					continue;
				}
				$t = esc_sql( $table );
				$union[] = "SELECT user_id FROM `{$t}` WHERE status IN ({$status_in}) {$period_limit}";
			}

			if ( 'pulse' === self::read_mode() || 'merged' === self::read_mode() ) {
				$pulse = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$since = 'merged' === self::read_mode() ? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() ) : '';
				$filter = WP_Ulike_Pulse_Vote_Map::pulse_filter_from_legacy_statuses( $statuses );
				$key_in = implode(
					',',
					array_map(
						function ( $k ) use ( $wpdb ) {
							return $wpdb->prepare( '%s', $k );
						},
						$filter['keys']
					)
				);
				$status_sql = $filter['active_only'] ? "status = 'active'" : "status IN ('active','removed')";
				$union[] = "SELECT user_id FROM `{$pulse}` WHERE engagement_kind = 'vote' AND engagement_key IN ({$key_in}) AND {$status_sql} {$since} {$period_limit}";
			}

			if ( empty( $union ) ) {
				return null;
			}

			$offset_sql = '';
			if ( (int) $limit > 0 ) {
				$off = $offset > 0 ? ( $offset - 1 ) * $limit : 0;
				$offset_sql = $wpdb->prepare( ' LIMIT %d, %d', $off, $limit );
			}

			$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				"SELECT user_id, COUNT(*) AS counter FROM (" . implode( ' UNION ALL ', $union ) . ") AS votes
				GROUP BY user_id ORDER BY counter {$order} {$offset_sql}"
			);
		}

		/**
		 * @param string       $period Period.
		 * @param string|array $status Status filter.
		 * @return int
		 */
		public static function count_unique_engagers( $period, $status = array( 'like', 'dislike' ) ) {
			$likers = self::get_best_likers( 0, $period, 1, $status );
			return is_array( $likers ) ? count( $likers ) : 0;
		}

		/**
		 * @param string $table_name  Legacy table suffix.
		 * @param string $column_name Item column.
		 * @param int    $item_id     Item ID.
		 * @param int    $limit       Limit.
		 * @return array|null
		 */
		public static function rebuild_likers_list( $table_name, $column_name, $item_id, $limit = 10 ) {
			global $wpdb;

			$item_id = absint( $item_id );
			$type    = wp_ulike_get_type_by_table( str_replace( $wpdb->prefix, '', $table_name ) );
			$mode    = self::read_mode();

			if ( 'pulse' === $mode ) {
				$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
				return $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT user_id FROM `{$table}` WHERE item_id = %d AND item_type = %s
						AND engagement_kind = %s AND status = %s AND engagement_key = %s
						ORDER BY date_time DESC LIMIT %d",
						$item_id,
						WP_Ulike_Pulse_Registry::from_setting_type( $type ),
						WP_Ulike_Pulse_Registry::KIND_VOTE,
						WP_Ulike_Pulse_Vote_Map::ROW_ACTIVE,
						WP_Ulike_Pulse_Vote_Map::KEY_LIKE,
						absint( $limit )
					)
				);
			}

			$table  = esc_sql( $table_name );
			$column = esc_sql( $column_name );
			$users  = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT user_id FROM `{$table}` WHERE `{$column}` = %d AND status = %s ORDER BY date_time DESC LIMIT %d",
					$item_id,
					WP_Ulike_Pulse_Vote_Map::ACTION_LIKE,
					absint( $limit )
				)
			);

			if ( 'legacy' === $mode ) {
				return $users;
			}

			$pulse_users = self::rebuild_likers_list( $table_name, $column_name, $item_id, $limit );
			return array_values( array_unique( array_merge( (array) $users, (array) $pulse_users ) ) );
		}

		/**
		 * @param int $post_id Post ID.
		 * @return array{sum:float,count:int,average:float}
		 */
		public static function get_post_rating_stats( $post_id ) {
			return array(
				'sum'     => 0,
				'count'   => 0,
				'average' => 0,
			);
		}

		/* ---------- Internal SQL helpers ---------- */

		/**
		 * @param string $column Column name.
		 * @return string
		 */
		private static function legacy_active_status_sql( $column ) {
			$column = esc_sql( $column );
			return "`{$column}` IN ('like','dislike')";
		}

		/**
		 * @param int    $item_id      Item ID.
		 * @param string $type         Setting type.
		 * @param string $status       Status filter.
		 * @param bool   $is_distinct  Distinct count.
		 * @param string $period_limit Period SQL.
		 * @param string $since        Optional since datetime.
		 * @return int
		 */
		private static function count_pulse_item_votes( $item_id, $type, $status, $is_distinct, $period_limit, $since ) {
			global $wpdb;

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$count     = $is_distinct ? 'DISTINCT user_id' : '*';

			if ( 'all' === $status ) {
				$status_sql = "engagement_key IN ('like','dislike') AND status = 'active'";
			} else {
				$mapped     = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $status );
				$status_sql = $wpdb->prepare(
					'engagement_key = %s AND status = %s',
					$mapped['engagement_key'],
					$mapped['status']
				);
			}

			$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT({$count}) FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
					AND {$status_sql} {$since_sql} {$period_limit}",
					absint( $item_id ),
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}

		/**
		 * @param int    $item_id      Item ID.
		 * @param string $type         Setting type.
		 * @param string $status       Status.
		 * @param string $period_limit Period SQL.
		 * @param array  $table_info   Table info.
		 * @return int
		 */
		private static function count_merged_distinct_item( $item_id, $type, $status, $period_limit, $table_info ) {
			global $wpdb;

			$legacy_table = esc_sql( $wpdb->prefix . $table_info['table'] );
			$column       = esc_sql( $table_info['column'] );
			$pulse_table  = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type    = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$since        = WP_Ulike_Pulse_Config::dual_since();

			if ( 'all' === $status ) {
				$legacy_status = self::legacy_active_status_sql( 'l.status' );
				$eng_status    = "e.engagement_key IN ('like','dislike') AND e.status = 'active'";
			} else {
				$legacy_status = $wpdb->prepare( 'l.status = %s', $status );
				$mapped        = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $status );
				$eng_status    = $wpdb->prepare( 'e.engagement_key = %s AND e.status = %s', $mapped['engagement_key'], $mapped['status'] );
			}

			$legacy_period = str_replace( 'date_time', 'l.date_time', $period_limit );
			$eng_period    = str_replace( 'date_time', 'e.date_time', $period_limit );

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM (
						SELECT l.user_id FROM `{$legacy_table}` l
						WHERE {$legacy_status} AND l.`{$column}` = %d {$legacy_period}
						UNION
						SELECT e.user_id FROM `{$pulse_table}` e
						WHERE e.item_id = %d AND e.item_type = %s AND e.engagement_kind = %s
						AND e.date_time >= %s AND {$eng_status} {$eng_period}
					) AS combined",
					$item_id,
					$item_id,
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$since
				)
			);
		}

		/**
		 * @param string $period Period key.
		 * @param string $since  Optional since.
		 * @return int
		 */
		private static function count_pulse_logs( $period, $since = '' ) {
			global $wpdb;

			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$table        = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$since_sql    = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$table}` WHERE engagement_kind = 'vote' {$since_sql} {$period_limit}"
			);
		}

		/**
		 * @param string $period Period key.
		 * @return int
		 */
		private static function count_all_legacy_logs( $period ) {
			global $wpdb;

			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$total        = 0;

			foreach ( self::log_table_names() as $table ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $table ) ) {
					continue;
				}
				$t = esc_sql( $table );
				$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$t}` WHERE 1=1 {$period_limit}" );
			}

			return $total;
		}

		/**
		 * @param array  $parsed_args       Args.
		 * @param array  $info_args         Table info.
		 * @param string $period_limit      Period SQL.
		 * @param string $user_condition    User SQL.
		 * @param string $related_condition Related SQL.
		 * @param string $limit_records     LIMIT SQL.
		 * @return array|null
		 */
		private static function popular_from_legacy( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records ) {
			global $wpdb;

			$statuses = WP_Ulike_Pulse_Vote_Map::normalize_status_filter( $parsed_args['status'] );
			$status_in = implode(
				',',
				array_map(
					function ( $s ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $s );
					},
					$statuses
				)
			);

			$table   = esc_sql( $wpdb->prefix . $info_args['table'] );
			$column  = esc_sql( $info_args['column'] );
			$count   = wp_ulike_setting_repo::isDistinct( $parsed_args['type'] ) ? 'COUNT(DISTINCT t.user_id)' : "COUNT(t.`{$column}`)";
			$order_by = esc_sql( $parsed_args['is_popular'] ? 'counter' : 'item_ID' );
			$order    = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
			$join     = self::popular_content_join( $parsed_args, $info_args, $related_condition, 't', $column );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				"SELECT {$count} AS counter, t.`{$column}` AS item_ID FROM `{$table}` t {$join}
				WHERE t.status IN ({$status_in}) {$user_condition} {$period_limit}
				GROUP BY t.`{$column}` ORDER BY `{$order_by}` {$order} {$limit_records}"
			);
		}

		/**
		 * @param array  $parsed_args       Args.
		 * @param array  $info_args         Table info.
		 * @param string $period_limit      Period SQL.
		 * @param string $user_condition    User SQL.
		 * @param string $related_condition Related SQL.
		 * @param string $limit_records     LIMIT SQL.
		 * @return array|null
		 */
		private static function popular_from_pulse( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records ) {
			global $wpdb;

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $parsed_args['type'] );
			$filter    = WP_Ulike_Pulse_Vote_Map::pulse_filter_from_legacy_statuses( $parsed_args['status'] );
			$key_in    = implode(
				',',
				array_map(
					function ( $k ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $k );
					},
					$filter['keys']
				)
			);
			$status_sql = $filter['active_only'] ? "t.status = 'active'" : "t.status IN ('active','removed')";
			$count      = wp_ulike_setting_repo::isDistinct( $parsed_args['type'] ) ? 'COUNT(DISTINCT t.user_id)' : 'COUNT(t.item_id)';
			$order_by   = esc_sql( $parsed_args['is_popular'] ? 'counter' : 'item_ID' );
			$order      = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
			$join       = self::popular_content_join( $parsed_args, $info_args, $related_condition, 't', 'item_id', true );
			$period_sql = str_replace( 'date_time', 't.date_time', $period_limit );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$count} AS counter, t.item_id AS item_ID FROM `{$table}` t {$join}
					WHERE t.item_type = %s AND t.engagement_kind = %s AND t.engagement_key IN ({$key_in})
					AND {$status_sql} {$user_condition} {$period_sql}
					GROUP BY t.item_id ORDER BY `{$order_by}` {$order} {$limit_records}",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}

		/**
		 * Merged popular items — simplified: aggregate legacy + pulse delta in PHP for small limits.
		 * For large sites, run migration to pulse mode for optimal SQL.
		 *
		 * @param array  $parsed_args       Args.
		 * @param array  $info_args         Table info.
		 * @param string $period_limit      Period SQL.
		 * @param string $user_condition    User SQL.
		 * @param string $related_condition Related SQL.
		 * @param string $limit_records     LIMIT SQL.
		 * @return array|null
		 */
		private static function popular_from_merged( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records ) {
			$legacy = self::popular_from_legacy( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, '' );
			$delta  = self::popular_from_pulse( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, '' );

			$merged = array();
			foreach ( array_merge( (array) $legacy, (array) $delta ) as $row ) {
				$id = (int) $row->item_ID;
				if ( ! isset( $merged[ $id ] ) ) {
					$merged[ $id ] = (int) $row->counter;
				} else {
					$merged[ $id ] += (int) $row->counter;
				}
			}

			arsort( $merged, SORT_NUMERIC );
			if ( strtoupper( $parsed_args['order'] ) === 'ASC' ) {
				asort( $merged, SORT_NUMERIC );
			}

			$results = array();
			foreach ( $merged as $id => $counter ) {
				$results[] = (object) array(
					'item_ID'  => $id,
					'counter'  => $counter,
				);
			}

			if ( preg_match( '/LIMIT\s+(\d+)\s*,\s*(\d+)/i', $limit_records, $m ) ) {
				$results = array_slice( $results, (int) $m[1], (int) $m[2] );
			}

			return $results;
		}

		/**
		 * @param array  $parsed_args       Args.
		 * @param array  $info_args         Table info.
		 * @param string $related_condition Related SQL.
		 * @param string $alias             Table alias.
		 * @param string $id_column         ID column on vote table.
		 * @param bool   $pulse             Pulse table shape.
		 * @return string
		 */
		private static function popular_content_join( $parsed_args, $info_args, $related_condition, $alias, $id_column, $pulse = false ) {
			global $wpdb;

			$col = $pulse ? "{$alias}.item_id" : "{$alias}.`{$id_column}`";

			switch ( $parsed_args['type'] ) {
				case 'post':
				case 'topic':
					return "INNER JOIN {$wpdb->posts} r ON r.ID = {$col} {$related_condition}";
				case 'comment':
					return "INNER JOIN {$wpdb->comments} r ON r.comment_ID = {$col} {$related_condition}";
				case 'activity':
				case 'activities':
					$bp = is_multisite() ? $wpdb->base_prefix . 'bp_activity' : $wpdb->prefix . 'bp_activity';
					return "INNER JOIN {$bp} r ON r.id = {$col} {$related_condition}";
			}

			return '';
		}
	}
}
