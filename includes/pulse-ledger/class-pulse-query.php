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
			return WP_Ulike_Pulse_Registry::log_table_names();
		}

		/**
		 * Whether Pulse read routing is loaded (all vote reads should go through this class).
		 *
		 * @return bool
		 */
		public static function available() {
			return class_exists( 'WP_Ulike_Pulse_Config' );
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
		 * Count logs in one legacy table suffix (ulike, ulike_comments, …).
		 *
		 * @param string $table_suffix Table name without prefix.
		 * @param mixed  $period       Period filter.
		 * @return int
		 */
		public static function count_logs_for_table( $table_suffix, $period = 'all' ) {
			global $wpdb;

			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$mode         = self::read_mode();
			$item_type    = null;

			foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
				if ( str_replace( $wpdb->prefix, '', $source['table'] ) === $table_suffix ) {
					$item_type = $source['item_type'];
					break;
				}
			}

			if ( 'pulse' === $mode ) {
				if ( ! $item_type ) {
					return 0;
				}
				return self::count_pulse_logs_for_type( $item_type, $period );
			}

			$table = esc_sql( $wpdb->prefix . $table_suffix );
			if ( ! WP_Ulike_Pulse_Registry::table_exists( $table ) ) {
				$legacy = 0;
			} else {
				$legacy = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE 1=1 {$period_limit}" );
			}

			if ( 'legacy' === $mode || ! $item_type ) {
				return $legacy;
			}

			return $legacy + self::count_pulse_logs_for_type( $item_type, $period, WP_Ulike_Pulse_Config::dual_since() );
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

			$pulse = self::fetch_pulse_activity_row( $item_id, $user_id, $type );
			if ( ! $pulse ) {
				return $row;
			}

			return strtotime( $pulse->date_time ) >= strtotime( $row->date_time ) ? $pulse : $row;
		}

		/**
		 * @param int    $item_id Item ID.
		 * @param int    $user_id User ID.
		 * @param string $type    Setting type.
		 * @return bool
		 */
		public static function is_user_liked( $item_id, $user_id, $type = 'likeThis' ) {
			if ( ! class_exists( 'WP_Ulike_Pulse_Reader' ) ) {
				return false;
			}

			$action = WP_Ulike_Pulse_Reader::user_action( $item_id, $user_id, $type );
			return 'like' === $action;
		}

		/**
		 * @param array<string,mixed> $args item_id, current_user, settings (wp_ulike_setting_type).
		 * @return int
		 */
		public static function count_user_votes_today( array $args ) {
			global $wpdb;

			$item_id      = isset( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
			$user_id      = isset( $args['current_user'] ) ? (string) $args['current_user'] : '';
			$settings     = isset( $args['settings'] ) ? $args['settings'] : null;
			$today        = current_time( 'Y-m-d' );
			$today_start  = $today . ' 00:00:00';
			$today_end    = $today . ' 23:59:59';
			$mode         = self::read_mode();

			if ( ! $item_id || '' === $user_id || ! is_object( $settings ) ) {
				return 0;
			}

			if ( 'pulse' === $mode ) {
				return self::count_pulse_votes_in_range( $item_id, $settings->getType(), $user_id, $today_start, $today_end, '' );
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $settings->getType() );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return self::count_pulse_votes_in_range( $item_id, $settings->getType(), $user_id, $today_start, $today_end, '' );
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );
			$legacy = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s AND date_time >= %s AND date_time <= %s",
					$item_id,
					$user_id,
					$today_start,
					$today_end
				)
			);

			if ( 'legacy' === $mode ) {
				return $legacy;
			}

			return $legacy + self::count_pulse_votes_in_range(
				$item_id,
				$settings->getType(),
				$user_id,
				$today_start,
				$today_end,
				WP_Ulike_Pulse_Config::dual_since()
			);
		}

		/**
		 * @param int   $user_id User ID.
		 * @param array $args    Query args (type, period, order, status, page, per_page).
		 * @return array|null
		 */
		public static function get_user_data( $user_id, $args = array() ) {
			global $wpdb;

			$defaults = array(
				'type'     => 'post',
				'period'   => 'all',
				'order'    => 'DESC',
				'status'   => 'like',
				'page'     => 1,
				'per_page' => 10,
			);
			$parsed_args  = wp_parse_args( $args, $defaults );
			$parsed_args  = array_merge( wp_ulike_get_table_info( $parsed_args['type'] ), $parsed_args );
			$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );
			$status_sql   = self::legacy_status_where( 'status', $parsed_args['status'] );
			$mode         = self::read_mode();
			$offset       = ( (int) $parsed_args['page'] - 1 ) * (int) $parsed_args['per_page'];
			$limit        = absint( $parsed_args['per_page'] );
			$order        = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			if ( 'pulse' === $mode ) {
				return self::query_user_items_pulse( $user_id, $parsed_args['type'], $status_sql, $period_limit, $order, $offset, $limit );
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $parsed_args['type'] );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return self::query_user_items_pulse( $user_id, $parsed_args['type'], $status_sql, $period_limit, $order, $offset, $limit );
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			$legacy_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `{$column}` AS itemID, MAX(date_time) AS datetime, MAX(status) AS lastStatus
					FROM `{$table}`
					WHERE user_id = %d AND {$status_sql} {$period_limit}
					GROUP BY itemID
					ORDER BY datetime {$order}",
					$user_id
				)
			);

			if ( 'legacy' === $mode ) {
				return array_slice( (array) $legacy_rows, $offset, $limit );
			}

			$pulse_rows = self::query_user_items_pulse( $user_id, $parsed_args['type'], $status_sql, $period_limit, $order, 0, 0 );
			return self::merge_user_item_rows( (array) $legacy_rows, (array) $pulse_rows, $order, $offset, $limit );
		}

		/**
		 * @param array $args Query args.
		 * @return array|null
		 */
		public static function get_users( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'type'     => 'post',
				'period'   => 'all',
				'order'    => 'DESC',
				'status'   => 'like',
				'page'     => 1,
				'per_page' => 10,
			);
			$parsed_args  = wp_parse_args( $args, $defaults );
			$parsed_args  = array_merge( wp_ulike_get_table_info( $parsed_args['type'] ), $parsed_args );
			$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );
			$status_sql   = self::legacy_status_where( 'status', $parsed_args['status'] );
			$mode         = self::read_mode();
			$offset       = ( (int) $parsed_args['page'] - 1 ) * (int) $parsed_args['per_page'];
			$limit        = absint( $parsed_args['per_page'] );
			$order        = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			if ( 'pulse' === $mode ) {
				return self::query_users_pulse( $parsed_args['type'], $status_sql, $period_limit, $order, $offset, $limit );
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $parsed_args['type'] );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return self::query_users_pulse( $parsed_args['type'], $status_sql, $period_limit, $order, $offset, $limit );
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			$legacy_rows = $wpdb->get_results(
				"SELECT t.user_id AS userID, COUNT(t.user_id) AS score, MAX(t.date_time) AS datetime,
				MAX(t.status) AS lastStatus,
				SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT t.`{$column}` ORDER BY t.`{$column}` DESC SEPARATOR ','), ',', 500) AS itemsList
				FROM `{$table}` t
				INNER JOIN {$wpdb->users} u ON u.ID = t.user_id
				WHERE {$status_sql} {$period_limit}
				GROUP BY t.user_id
				ORDER BY score {$order}"
			);

			if ( 'legacy' === $mode ) {
				return array_slice( (array) $legacy_rows, $offset, $limit );
			}

			$pulse_rows = self::query_users_pulse( $parsed_args['type'], $status_sql, $period_limit, $order, 0, 0 );
			return self::merge_user_score_rows( (array) $legacy_rows, (array) $pulse_rows, $order, $offset, $limit );
		}

		/**
		 * @param int  $post_id     Post ID.
		 * @param bool $is_decimal  Decimal rating.
		 * @return string|float
		 */
		public static function get_rating_value( $post_id, $is_decimal = true ) {
			$count = self::count_item_votes( $post_id, 'post', 'like', false, null );
			$avg   = get_transient( 'wp_ulike_global_avg_likes' );

			if ( false === $avg ) {
				global $wpdb;
				$mode = self::read_mode();

				if ( 'pulse' === $mode ) {
					$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
					$avg   = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT AVG(item_count) FROM (
								SELECT COUNT(*) AS item_count FROM `{$table}`
								WHERE item_type = %s AND engagement_kind = %s
								GROUP BY item_id
							) AS counted",
							WP_Ulike_Pulse_Registry::ITEM_POST,
							WP_Ulike_Pulse_Registry::KIND_VOTE
						)
					);
				} else {
					$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( 'post' );
					if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						$table = esc_sql( $source['table'] );
						$col   = esc_sql( $source['column'] );
						$avg   = $wpdb->get_var(
							"SELECT AVG(post_count) FROM (
								SELECT COUNT(*) AS post_count FROM `{$table}` GROUP BY `{$col}`
							) AS counted"
						);
					} else {
						$avg = 0;
					}
				}

				set_transient( 'wp_ulike_global_avg_likes', $avg, HOUR_IN_SECONDS );
			}

			$post_date = get_post_field( 'post_date', $post_id );
			$date      = $post_date ? strtotime( $post_date ) : 0;
			$count     = (int) $count;
			$avg       = (float) $avg;

			if ( 0 === $count || 0.0 === $avg ) {
				return 5;
			}

			$decimal = 0;
			if ( $is_decimal ) {
				list( , $decimal ) = explode( '.', number_format( ( $count * 100 / ( $avg * 2 ) ), 1 ) );
				$decimal = (int) $decimal;
			}

			if ( $date > strtotime( '-1 month' ) ) {
				if ( $count < $avg ) {
					$rating_value = 4 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
				} else {
					$rating_value = 5;
				}
			} elseif ( ( $date <= strtotime( '-1 month' ) ) && ( $date > strtotime( '-6 month' ) ) ) {
				if ( $count < $avg ) {
					$rating_value = 4 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
				} elseif ( ( $count >= $avg ) && ( $count < ( $avg * 3 / 2 ) ) ) {
					$rating_value = 4 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
				} else {
					$rating_value = 5;
				}
			} elseif ( $count < ( $avg / 2 ) ) {
				$rating_value = 1 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
			} elseif ( ( $count >= ( $avg / 2 ) ) && ( $count < $avg ) ) {
				$rating_value = 2 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
			} elseif ( ( $count >= $avg ) && ( $count < ( $avg * 3 / 2 ) ) ) {
				$rating_value = 3 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
			} elseif ( ( $count >= ( $avg * 3 / 2 ) ) && ( $count < ( $avg * 2 ) ) ) {
				$rating_value = 4 + ( $is_decimal ? (float) ( '0.' . $decimal ) : 0 );
			} else {
				$rating_value = 5;
			}

			return $rating_value;
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
			foreach ( self::log_table_names() as $table ) {
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
				"SELECT user_id, COUNT(*) AS SumUser FROM (" . implode( ' UNION ALL ', $union ) . ") AS votes
				GROUP BY user_id ORDER BY SumUser {$order} {$offset_sql}"
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

			$pulse_users = self::fetch_pulse_likers( $item_id, $type, $limit );
			return array_values( array_unique( array_merge( (array) $users, (array) $pulse_users ) ) );
		}

		/**
		 * @param int $post_id Post ID.
		 * @return array{sum:float,count:int,average:float}
		 */
		public static function get_post_rating_stats( $post_id ) {
			$count = self::count_item_votes( $post_id, 'post', 'like', false, null );
			return array(
				'sum'     => (float) $count,
				'count'   => (int) $count,
				'average' => (float) self::get_rating_value( $post_id, true ),
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
		 * @param string $item_type Canonical item type.
		 * @param mixed  $period    Period filter.
		 * @param string $since     Optional since datetime.
		 * @return int
		 */
		private static function count_pulse_logs_for_type( $item_type, $period, $since = '' ) {
			global $wpdb;

			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$table        = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$since_sql    = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE item_type = %s AND engagement_kind = %s {$since_sql} {$period_limit}",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
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

		/**
		 * @param int    $item_id Item ID.
		 * @param string $user_id User ID.
		 * @param string $type    Setting type.
		 * @return object|null
		 */
		private static function fetch_pulse_activity_row( $item_id, $user_id, $type ) {
			global $wpdb;

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, item_id, user_id, date_time,
					engagement_key, status, ip, fingerprint
					FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND user_id = %s AND engagement_kind = %s
					ORDER BY date_time DESC, id DESC LIMIT 1",
					absint( $item_id ),
					WP_Ulike_Pulse_Registry::from_setting_type( $type ),
					(string) $user_id,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}

		/**
		 * @param int    $item_id     Item ID.
		 * @param string $type        Setting type.
		 * @param string $user_id     User ID.
		 * @param string $start       Range start.
		 * @param string $end         Range end.
		 * @param string $since       Dual-mode cutoff.
		 * @return int
		 */
		private static function count_pulse_votes_in_range( $item_id, $type, $user_id, $start, $end, $since ) {
			global $wpdb;

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND user_id = %s AND engagement_kind = %s
					AND date_time >= %s AND date_time <= %s {$since_sql}",
					absint( $item_id ),
					WP_Ulike_Pulse_Registry::from_setting_type( $type ),
					(string) $user_id,
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$start,
					$end
				)
			);
		}

		/**
		 * @param string       $column Column name.
		 * @param string|array $status Status filter.
		 * @return string
		 */
		private static function legacy_status_where( $column, $status ) {
			global $wpdb;

			$status = wp_ulike_normalize_vote_statuses( $status );
			if ( is_array( $status ) ) {
				$values = array_map(
					function ( $s ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $s );
					},
					$status
				);
				return '`' . esc_sql( $column ) . '` IN (' . implode( ',', $values ) . ')';
			}

			return $wpdb->prepare( '`' . esc_sql( $column ) . '` = %s', $status );
		}

		/**
		 * @param string|int $user_id      User ID.
		 * @param string     $type         Setting type.
		 * @param string     $status_sql   Legacy status SQL.
		 * @param string     $period_limit Period SQL.
		 * @param string     $order        ASC|DESC.
		 * @param int        $offset       Offset.
		 * @param int        $limit        Limit (0 = all).
		 * @return array
		 */
		private static function query_user_items_pulse( $user_id, $type, $status_sql, $period_limit, $order, $offset, $limit ) {
			global $wpdb;

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$period    = str_replace( 'date_time', 'date_time', $period_limit );
			$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT item_id AS itemID, MAX(date_time) AS datetime,
					MAX(CASE WHEN status = 'active' THEN engagement_key ELSE status END) AS lastStatus
					FROM `{$table}`
					WHERE user_id = %s AND item_type = %s AND engagement_kind = %s {$period}
					GROUP BY item_id
					ORDER BY datetime {$order} {$limit_sql}",
					(string) $user_id,
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			return is_array( $rows ) ? $rows : array();
		}

		/**
		 * @param string $type         Setting type.
		 * @param string $status_sql   Legacy status SQL (unused for pulse shape).
		 * @param string $period_limit Period SQL.
		 * @param string $order        ASC|DESC.
		 * @param int    $offset       Offset.
		 * @param int    $limit        Limit.
		 * @return array
		 */
		private static function query_users_pulse( $type, $status_sql, $period_limit, $order, $offset, $limit ) {
			global $wpdb;

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$period    = str_replace( 'date_time', 'date_time', $period_limit );
			$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.user_id AS userID, COUNT(*) AS score, MAX(t.date_time) AS datetime,
					MAX(t.engagement_key) AS lastStatus,
					SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT t.item_id ORDER BY t.item_id DESC SEPARATOR ','), ',', 500) AS itemsList
					FROM `{$table}` t
					INNER JOIN {$wpdb->users} u ON u.ID = t.user_id
					WHERE t.item_type = %s AND t.engagement_kind = %s {$period}
					GROUP BY t.user_id
					ORDER BY score {$order} {$limit_sql}",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			return is_array( $rows ) ? $rows : array();
		}

		/**
		 * @param array  $legacy Legacy rows.
		 * @param array  $pulse  Pulse rows.
		 * @param string $order  ASC|DESC.
		 * @param int    $offset Offset.
		 * @param int    $limit  Limit.
		 * @return array
		 */
		private static function merge_user_item_rows( array $legacy, array $pulse, $order, $offset, $limit ) {
			$merged = array();

			foreach ( array_merge( $legacy, $pulse ) as $row ) {
				$id = (int) $row->itemID;
				if ( ! isset( $merged[ $id ] ) || strtotime( $row->datetime ) > strtotime( $merged[ $id ]->datetime ) ) {
					$merged[ $id ] = $row;
				}
			}

			$rows = array_values( $merged );
			usort(
				$rows,
				function ( $a, $b ) use ( $order ) {
					$cmp = strtotime( $a->datetime ) <=> strtotime( $b->datetime );
					return 'ASC' === $order ? $cmp : -$cmp;
				}
			);

			return $limit > 0 ? array_slice( $rows, $offset, $limit ) : $rows;
		}

		/**
		 * @param array  $legacy Legacy rows.
		 * @param array  $pulse  Pulse rows.
		 * @param string $order  ASC|DESC.
		 * @param int    $offset Offset.
		 * @param int    $limit  Limit.
		 * @return array
		 */
		private static function merge_user_score_rows( array $legacy, array $pulse, $order, $offset, $limit ) {
			$merged = array();

			foreach ( array_merge( $legacy, $pulse ) as $row ) {
				$id = (int) $row->userID;
				if ( ! isset( $merged[ $id ] ) ) {
					$merged[ $id ] = $row;
					continue;
				}
				$merged[ $id ]->score     = (int) $merged[ $id ]->score + (int) $row->score;
				$merged[ $id ]->datetime  = max( $merged[ $id ]->datetime, $row->datetime );
				$merged[ $id ]->itemsList = trim( $merged[ $id ]->itemsList . ',' . $row->itemsList, ',' );
			}

			$rows = array_values( $merged );
			usort(
				$rows,
				function ( $a, $b ) use ( $order ) {
					$cmp = (int) $a->score <=> (int) $b->score;
					return 'ASC' === $order ? $cmp : -$cmp;
				}
			);

			return $limit > 0 ? array_slice( $rows, $offset, $limit ) : $rows;
		}

		/**
		 * @param int    $item_id Item ID.
		 * @param string $type    Setting type.
		 * @param int    $limit   Limit.
		 * @return array|null
		 */
		private static function fetch_pulse_likers( $item_id, $type, $limit ) {
			global $wpdb;

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			return $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT user_id FROM `{$table}` WHERE item_id = %d AND item_type = %s
					AND engagement_kind = %s AND status = %s AND engagement_key = %s
					ORDER BY date_time DESC LIMIT %d",
					absint( $item_id ),
					WP_Ulike_Pulse_Registry::from_setting_type( $type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					WP_Ulike_Pulse_Vote_Map::ROW_ACTIVE,
					WP_Ulike_Pulse_Vote_Map::KEY_LIKE,
					absint( $limit )
				)
			);
		}
	}
}
