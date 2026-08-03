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
			return true;
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

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type(
				WP_Ulike_Pulse_Registry::from_setting_type( $type )
			);
			$legacy = 0;

			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$column = esc_sql( $source['column'] );
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
			}

			if ( 'legacy' === $mode ) {
				return $legacy;
			}

			if ( $is_distinct ) {
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					return self::count_merged_distinct_item( $item_id, $type, $status, $period_limit, $table_info );
				}

				return self::count_pulse_item_votes(
					$item_id,
					$type,
					$status,
					true,
					$period_limit,
					WP_Ulike_Pulse_Config::dual_since()
				);
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

			$mode   = self::read_mode();
			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type(
				WP_Ulike_Pulse_Registry::from_setting_type( $type )
			);

			if ( 'pulse' === $mode ) {
				return self::count_pulse_fingerprint_votes( $fingerprint, $item_id, $type );
			}

			$legacy = 0;
			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$column = esc_sql( $source['column'] );
				$legacy = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = %d AND fingerprint = %s",
						absint( $item_id ),
						$fingerprint
					)
				);
			}

			if ( 'legacy' === $mode || ! $source ) {
				return $legacy;
			}

			return $legacy + self::count_pulse_fingerprint_votes(
				$fingerprint,
				$item_id,
				$type,
				WP_Ulike_Pulse_Config::dual_since()
			);
		}

		/**
		 * @param int|string $fingerprint Fingerprint.
		 * @param int        $item_id     Item ID.
		 * @param string     $type        Setting type.
		 * @param string     $since       Optional datetime floor (merged mode).
		 * @return int
		 */
		private static function count_pulse_fingerprint_votes( $fingerprint, $item_id, $type, $since = '' ) {
			global $wpdb;

			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type   = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$since_sql   = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$pulse_table}`
					WHERE item_id = %d AND item_type = %s AND fingerprint = %s AND engagement_kind = %s {$since_sql}",
					absint( $item_id ),
					$item_type,
					$fingerprint,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
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
			// Legacy tables hold classic votes only; emoji/star live in pulse
			// regardless of storage mode. Add them so legacy-mode sites using
			// Pro engagement still see emoji/star activity in totals.
			return $legacy + self::count_pulse_non_vote_logs( $period );
		}

		return $legacy + self::count_pulse_logs( $period, WP_Ulike_Pulse_Config::dual_since() );
	}

		/**
		 * Count logs for one content type (post, comment, activity, topic).
		 *
		 * @param string $item_type Canonical item type.
		 * @param mixed  $period    Period filter.
		 * @return int
		 */
		public static function count_logs_for_type( $item_type, $period = 'all' ) {
			global $wpdb;

			$item_type    = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$mode         = self::read_mode();
			$source       = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );

			if ( 'pulse' === $mode ) {
				if ( ! $source ) {
					return 0;
				}
				return self::count_pulse_logs_for_type( $item_type, $period );
			}

			$legacy = 0;
			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$legacy = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE 1=1 {$period_limit}" );
			}

		if ( 'legacy' === $mode || ! $source ) {
			// Emoji/star rows live in pulse even in legacy read mode and for
			// types without a legacy table. Add them so per-type totals match.
			return $legacy + self::count_pulse_non_vote_logs( $period, '', $item_type );
		}

		return $legacy + self::count_pulse_logs_for_type( $item_type, $period, WP_Ulike_Pulse_Config::dual_since() );
	}

		/**
		 * Count classic vote rows (engagement_kind = vote) for one content
		 * type across all statuses. Mode-aware (legacy + pulse-since-dual).
		 *
		 * Pro stats use this for the vote slice of "vote + emoji + star"
		 * totals, because count_logs_for_type() already counts all kinds and
		 * would double-count emoji/star if Pro added them on top.
		 *
		 * @param string $item_type Canonical item type.
		 * @param mixed  $period    Period filter.
		 * @return int
		 */
		public static function count_vote_logs_for_type( $item_type, $period = 'all' ) {
			global $wpdb;

			$item_type    = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$mode         = self::read_mode();
			$source       = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );

			if ( 'pulse' === $mode ) {
				if ( ! $source ) {
					return 0;
				}
				return self::count_pulse_vote_logs_for_type( $item_type, $period );
			}

			$legacy = 0;
			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$legacy = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE 1=1 {$period_limit}" );
			}

			if ( 'legacy' === $mode || ! $source ) {
				return $legacy;
			}

			return $legacy + self::count_pulse_vote_logs_for_type( $item_type, $period, WP_Ulike_Pulse_Config::dual_since() );
		}

	/**
	 * Count logs by legacy table suffix (ulike, ulike_comments, …).
		 *
		 * @param string $table_suffix Table name without prefix.
		 * @param mixed  $period       Period filter.
		 * @return int
		 */
		public static function count_logs_for_table( $table_suffix, $period = 'all' ) {
			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );

			if ( ! $item_type ) {
				return 0;
			}

			return self::count_logs_for_type( $item_type, $period );
		}

		/**
		 * Count vote rows for one legacy status (like|dislike|unlike|undislike) by table suffix.
		 *
		 * @param string $table_suffix    ulike|ulike_comments|...
		 * @param string $legacy_status   Legacy status string.
		 * @param mixed  $period          Period filter.
		 * @return int
		 */
		public static function count_status_for_table( $table_suffix, $legacy_status, $period = 'all' ) {
			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );

			if ( ! $item_type ) {
				return 0;
			}

			return self::count_status_for_type( $item_type, $legacy_status, $period );
		}

		/**
		 * Count distinct voters by legacy table suffix.
		 *
		 * @param string $table_suffix ulike|ulike_comments|...
		 * @param mixed  $period       Period filter.
		 * @return int
		 */
		public static function count_unique_voters_for_table( $table_suffix, $period = 'all' ) {
			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );

			if ( ! $item_type ) {
				return 0;
			}

			return self::count_unique_voters_for_type( $item_type, $period );
		}

		/**
		 * Distinct item IDs that have vote rows for a content type (all read modes).
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @return int[]
		 */
		public static function distinct_voted_item_ids( $item_type ) {
			global $wpdb;

			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$mode      = self::read_mode();
			$source    = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			$ids       = array();

			if ( ( 'legacy' === $mode || 'merged' === $mode ) && $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$column = esc_sql( $source['column'] );
				$table  = esc_sql( $source['table'] );
				$ids    = array_map( 'absint', (array) $wpdb->get_col( "SELECT DISTINCT `{$column}` FROM `{$table}`" ) );
			}

			if ( 'pulse' === $mode || 'merged' === $mode ) {
				$pulse     = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$since_sql = 'merged' === $mode ? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() ) : '';
				$pulse_ids = array_map(
					'absint',
					(array) $wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT item_id FROM `{$pulse}` WHERE item_type = %s AND engagement_kind = %s {$since_sql}",
							$item_type,
							WP_Ulike_Pulse_Registry::KIND_VOTE
						)
					)
				);
				$ids = array_values( array_unique( array_merge( $ids, $pulse_ids ) ) );
			}

			return array_values( array_filter( array_map( 'absint', $ids ) ) );
		}

		/**
		 * @param string $item_type     Canonical item type.
		 * @param string $legacy_status Legacy status.
		 * @param mixed  $period        Period filter.
		 * @return int
		 */
		public static function count_status_for_type( $item_type, $legacy_status, $period = 'all' ) {
			global $wpdb;

			$item_type    = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$mode         = self::read_mode();
			$source       = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );

			if ( 'pulse' === $mode ) {
				return self::count_pulse_status_for_type( $item_type, $legacy_status, $period_limit, '' );
			}

			$legacy = 0;
			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$legacy = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `{$table}` WHERE status = %s {$period_limit}",
						$legacy_status
					)
				);
			}

			if ( 'legacy' === $mode || ! $source ) {
				return $legacy;
			}

			return $legacy + self::count_pulse_status_for_type(
				$item_type,
				$legacy_status,
				$period_limit,
				WP_Ulike_Pulse_Config::dual_since()
			);
		}

		/**
		 * @param string $item_type Canonical item type.
		 * @param mixed  $period    Period filter.
		 * @return int
		 */
		public static function count_unique_voters_for_type( $item_type, $period = 'all' ) {
			global $wpdb;

			$item_type    = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$mode         = self::read_mode();
			$source       = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );

			if ( 'pulse' === $mode ) {
				return self::count_pulse_unique_voters_for_type( $item_type, $period_limit, '' );
			}

			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				// No legacy store: fall back to pulse reads since the cutover.
				if ( 'legacy' === $mode ) {
					return 0;
				}
				return self::count_pulse_unique_voters_for_type(
					$item_type,
					$period_limit,
					WP_Ulike_Pulse_Config::dual_since()
				);
			}

			$table = esc_sql( $source['table'] );

			if ( 'legacy' === $mode ) {
				return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM `{$table}` WHERE 1=1 {$period_limit}" );
			}

			// Merged mode: dedup across legacy + pulse via UNION so a voter who
			// appears in both stores is counted exactly once.
		return self::count_merged_unique_voters_for_type( $item_type, $table, $source['column'], $period_limit );
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
				array_merge( $parsed_args, array( 'limit' => 0, 'is_popular' => ! empty( $parsed_args['is_popular'] ) ) ),
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
				engagement_kind, engagement_key, status, value,
				ip, fingerprint, country_code, device
				FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND user_id = %s
				AND engagement_kind = %s AND status = %s
				ORDER BY date_time DESC, id DESC LIMIT 1",
				absint( $item_id ),
				WP_Ulike_Pulse_Registry::from_setting_type( $type ),
				(string) $user_id,
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				'active'
			)
		);
	}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type(
				WP_Ulike_Pulse_Registry::from_setting_type( $settings->getType() )
			);
			$row    = null;

			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$column = esc_sql( $source['column'] );

				$row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id, `{$column}` AS item_id, user_id, date_time, status, ip, fingerprint
						FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s ORDER BY id DESC LIMIT 1",
						absint( $item_id ),
						(string) $user_id
					)
				);
			}

			if ( 'legacy' === $mode ) {
				return $row;
			}

			$pulse = self::fetch_pulse_activity_row( $item_id, $user_id, $type );

			if ( ! $row ) {
				return $pulse;
			}

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
				return self::query_user_items_pulse( $user_id, $parsed_args['type'], $parsed_args['status'], $period_limit, $order, $offset, $limit );
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $parsed_args['type'] );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return self::query_user_items_pulse( $user_id, $parsed_args['type'], $parsed_args['status'], $period_limit, $order, $offset, $limit );
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			if ( 'legacy' === $mode ) {
				$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return $wpdb->get_results(
					$wpdb->prepare(
						"SELECT `{$column}` AS itemID, MAX(date_time) AS datetime, MAX(status) AS lastStatus
						FROM `{$table}`
						WHERE user_id = %d AND {$status_sql} {$period_limit}
						GROUP BY itemID
						ORDER BY datetime {$order}{$limit_sql}",
						$user_id
					)
				);
			}

			// Merged: newest row wins per item (incl. pulse removed), then filter.
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
			$pulse     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $parsed_args['type'] );
			$since     = WP_Ulike_Pulse_Config::dual_since();
			$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';
			$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';
			$active_w  = $filter['active_only'] ? " WHERE lastStatus <> 'removed'" : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT itemID, datetime, lastStatus FROM (
						SELECT itemID, MAX(datetime) AS datetime,
							SUBSTRING_INDEX( GROUP_CONCAT( lastStatus ORDER BY datetime DESC SEPARATOR '\\0' ), '\\0', 1 ) AS lastStatus
						FROM (
							SELECT `{$column}` AS itemID, date_time AS datetime, status AS lastStatus
							FROM `{$table}`
							WHERE user_id = %d AND {$status_sql} {$period_limit}
							UNION ALL
							SELECT item_id AS itemID, date_time AS datetime,
								CASE WHEN status = 'active' THEN engagement_key ELSE status END AS lastStatus
							FROM `{$pulse}`
							WHERE user_id = %s AND item_type = %s AND engagement_kind = %s
							AND engagement_key IN ({$key_in}) AND status IN ('active','removed'){$since_sql} {$period_limit}
						) AS combined
						GROUP BY itemID
					) AS ranked{$active_w}
					ORDER BY datetime {$order}{$limit_sql}",
					$user_id,
					(string) $user_id,
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
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
				return self::query_users_pulse( $parsed_args['type'], $parsed_args['status'], $period_limit, $order, $offset, $limit );
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $parsed_args['type'] );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return self::query_users_pulse( $parsed_args['type'], $parsed_args['status'], $period_limit, $order, $offset, $limit );
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			if ( 'legacy' === $mode ) {
				$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return $wpdb->get_results(
					"SELECT t.user_id AS userID, COUNT(t.user_id) AS score, MAX(t.date_time) AS datetime,
					MAX(t.status) AS lastStatus,
					SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT t.`{$column}` ORDER BY t.`{$column}` DESC SEPARATOR ','), ',', 500) AS itemsList
					FROM `{$table}` t
					INNER JOIN {$wpdb->users} u ON u.ID = t.user_id
					WHERE {$status_sql} {$period_limit}
					GROUP BY t.user_id
					ORDER BY score {$order}{$limit_sql}"
				);
			}

			// Merged: collapse to one row per (user, item); drop superseded unlikes.
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
			$pulse     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $parsed_args['type'] );
			$since     = WP_Ulike_Pulse_Config::dual_since();
			$since_sql = $since ? $wpdb->prepare( ' AND p.date_time >= %s', $since ) : '';
			$period_p  = str_replace( 'date_time', 'p.date_time', $period_limit );
			$period_t  = str_replace( 'date_time', 't.date_time', $period_limit );
			$status_t  = self::legacy_status_where( 't`.`status', $parsed_args['status'] );
			$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';
			$active_w  = $filter['active_only'] ? " WHERE lastStatus <> 'removed'" : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT userID, COUNT(*) AS score, MAX(datetime) AS datetime,
						SUBSTRING_INDEX( GROUP_CONCAT( lastStatus ORDER BY datetime DESC SEPARATOR '\\0' ), '\\0', 1 ) AS lastStatus,
						SUBSTRING_INDEX( GROUP_CONCAT( item_id ORDER BY item_id DESC SEPARATOR ',' ), ',', 500 ) AS itemsList
					FROM (
						SELECT userID, item_id, datetime, lastStatus FROM (
							SELECT userID, item_id, MAX(datetime) AS datetime,
								SUBSTRING_INDEX( GROUP_CONCAT( lastStatus ORDER BY datetime DESC SEPARATOR '\\0' ), '\\0', 1 ) AS lastStatus
							FROM (
								SELECT CAST(t.user_id AS CHAR) AS userID, t.`{$column}` AS item_id, t.date_time AS datetime, t.status AS lastStatus
								FROM `{$table}` t
								INNER JOIN {$wpdb->users} u ON u.ID = t.user_id
								WHERE {$status_t} {$period_t}
								UNION ALL
								SELECT CAST(p.user_id AS CHAR) AS userID, p.item_id AS item_id, p.date_time AS datetime,
									CASE WHEN p.status = 'active' THEN p.engagement_key ELSE p.status END AS lastStatus
								FROM `{$pulse}` p
								INNER JOIN {$wpdb->users} u ON u.ID = p.user_id
								WHERE p.item_type = %s AND p.engagement_kind = %s
								AND p.engagement_key IN ({$key_in}) AND p.status IN ('active','removed'){$since_sql} {$period_p}
							) AS raw
							GROUP BY userID, item_id
						) AS collapsed{$active_w}
					) AS per_item
					GROUP BY userID
					ORDER BY score {$order}{$limit_sql}",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
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

			$inner = self::vote_events_sql( $period, $status );
			if ( null === $inner ) {
				return null;
			}

			$offset_sql = '';
			if ( (int) $limit > 0 ) {
				$off        = $offset > 0 ? ( $offset - 1 ) * $limit : 0;
				$offset_sql = $wpdb->prepare( ' LIMIT %d, %d', $off, $limit );
			}

			$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

			// Count only registered WordPress users. Guest votes store ip2long /
			// fingerprint values in user_id — including them inflates Top Engagers
			// pagination far beyond rows the UI can resolve.
			$users = $wpdb->users;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				"SELECT votes.user_id, COUNT(*) AS SumUser FROM ( {$inner} ) AS votes
				INNER JOIN `{$users}` u ON u.ID = CAST(votes.user_id AS UNSIGNED)
				GROUP BY votes.user_id ORDER BY SumUser {$order} {$offset_sql}"
			);
		}

		/**
		 * One deduped "vote occurred" row per (user, item_type, item_id), across
		 * whichever legacy/pulse sources the current read mode uses. Shared by
		 * get_best_likers()/count_unique_engagers() and by addon plugins that
		 * need to combine vote counts with their own engagement data in one
		 * exact SQL ranking, instead of approximating via separate top-N pools
		 * merged in PHP (which can miss users on deep pages).
		 *
		 * @param mixed        $period Period filter.
		 * @param string|array $status Legacy status filter (e.g. like/dislike).
		 * @return string|null Parenthesized-safe SQL producing at least a
		 *                     `user_id` column, or null if no source is available.
		 */
		public static function vote_events_sql( $period, $status = array( 'like', 'dislike' ) ) {
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

			$mode  = self::read_mode();
			$union = array();

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
					if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						continue;
					}
					$t         = esc_sql( $source['table'] );
					$col       = esc_sql( $source['column'] );
					$item_type = esc_sql( $source['item_type'] );
					$union[]   = "SELECT CAST(user_id AS CHAR) AS user_id, '{$item_type}' AS item_type, `{$col}` AS item_id FROM `{$t}` WHERE status IN ({$status_in}) {$period_limit}";
				}
			}

			if ( 'pulse' === $mode || 'merged' === $mode ) {
				$pulse  = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$since  = 'merged' === $mode ? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() ) : '';
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
				$union[]    = "SELECT CAST(user_id AS CHAR) AS user_id, item_type, item_id FROM `{$pulse}` WHERE engagement_kind = 'vote' AND engagement_key IN ({$key_in}) AND {$status_sql} {$since} {$period_limit}";
			}

			if ( empty( $union ) ) {
				return null;
			}

			// Merged mode: count distinct (user, item_type, item) so post#5 and
			// comment#5 do not collapse, and dual stores do not double-count.
			$inner = implode( ' UNION ALL ', $union );
			if ( 'merged' === $mode ) {
				$inner = "SELECT DISTINCT user_id, item_type, item_id FROM ( {$inner} ) AS raw_votes";
			}

			return $inner;
		}

		/**
		 * @param string       $period Period.
		 * @param string|array $status Status filter.
		 * @return int
		 */
		public static function count_unique_engagers( $period, $status = array( 'like', 'dislike' ) ) {
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

			$mode  = self::read_mode();
			$union = array();

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
					if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						continue;
					}
					$t = esc_sql( $source['table'] );
					$union[] = "SELECT CAST(user_id AS CHAR) AS user_id FROM `{$t}` WHERE status IN ({$status_in}) {$period_limit}";
				}
			}

			if ( 'pulse' === $mode || 'merged' === $mode ) {
				$pulse  = esc_sql( WP_Ulike_Pulse_Schema::table() );
				$since  = 'merged' === $mode ? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() ) : '';
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
				$union[]    = "SELECT CAST(user_id AS CHAR) AS user_id FROM `{$pulse}` WHERE engagement_kind = 'vote' AND engagement_key IN ({$key_in}) AND {$status_sql} {$since} {$period_limit}";
			}

			if ( empty( $union ) ) {
				return 0;
			}

			$users = $wpdb->users;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var(
				'SELECT COUNT(DISTINCT engagers.user_id) FROM ( ' . implode( ' UNION ', $union ) . " ) AS engagers
				INNER JOIN `{$users}` u ON u.ID = CAST(engagers.user_id AS UNSIGNED)"
			);
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
			// Latest row per user, keep any ACTIVE vote — like or dislike.
			// Pre-Pulse this list was `status IN ('like','dislike')`, i.e. every
			// voter whose latest action was not an un-vote. Filtering to
			// engagement_key='like' silently dropped dislikers from the box.
			return $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.user_id
					FROM `{$table}` p
					INNER JOIN (
						SELECT MAX(id) AS max_id
						FROM `{$table}`
						WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
						GROUP BY user_id
					) latest ON p.id = latest.max_id
					WHERE p.status = %s
					ORDER BY p.date_time DESC LIMIT %d",
					$item_id,
					WP_Ulike_Pulse_Registry::from_setting_type( $type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					WP_Ulike_Pulse_Vote_Map::ROW_ACTIVE,
					absint( $limit )
				)
			);
		}

			$users  = array();
			$source = $type ? WP_Ulike_Pulse_Registry::legacy_source_for_type( $type ) : null;

		if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );
			$active_status_sql = self::legacy_active_status_sql( 'p`.`status' );
			// Latest row per user whose latest action is an active vote — mirrors
			// fetch_pulse_likers() so append-mode (one row per vote) and
			// distinct-mode both surface each user at most once, with their
			// most recent action deciding presence.
			$users = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.user_id
					FROM `{$table}` p
					INNER JOIN (
						SELECT MAX(id) AS max_id
						FROM `{$table}`
						WHERE `{$column}` = %d
						GROUP BY user_id
					) latest ON p.id = latest.max_id
					WHERE {$active_status_sql}
					ORDER BY p.date_time DESC
					LIMIT %d",
					$item_id,
					absint( $limit )
				)
			);
		}

		if ( 'legacy' === $mode ) {
			return $users;
		}

		$pulse_users = self::fetch_pulse_likers(
			$item_id,
			$type,
			$limit,
			WP_Ulike_Pulse_Config::dual_since()
		);

		// Merged mode: a user may have liked pre-cutover (legacy status=like)
		// then unliked post-cutover (pulse status=removed). The legacy list
		// would still include them, so subtract pulse "removed" users for this
		// item before merging. Pulse active likers are already in $pulse_users.
		// $users is already capped at $limit above, so only that bounded set of
		// candidate user IDs needs checking -- not every unlike ever recorded
		// for the item (unbounded on a viral post with heavy like/unlike churn).
		if ( ! empty( $users ) ) {
			$removed_users = self::fetch_pulse_unlikers( $item_id, $type, WP_Ulike_Pulse_Config::dual_since(), (array) $users );
			if ( ! empty( $removed_users ) ) {
				$users = array_diff( (array) $users, $removed_users );
			}
		}

		$merged = array_values( array_unique( array_merge( (array) $users, (array) $pulse_users ) ) );
		return $limit > 0 ? array_slice( $merged, 0, absint( $limit ) ) : $merged;
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
			$item_id      = absint( $item_id );

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

			// Drop legacy users whose latest post-cutover pulse vote is an unlike
			// (status=removed). Do not use broad "NOT matching status" — that was
			// under-counting valid legacy likes when pulse held any non-matching row.
			$unliked_sql = '';
			if ( $since ) {
				$unliked_sql = $wpdb->prepare(
					" AND CAST(l.user_id AS CHAR) NOT IN (
						SELECT CAST(p.user_id AS CHAR)
						FROM `{$pulse_table}` p
						INNER JOIN (
							SELECT MAX(id) AS max_id
							FROM `{$pulse_table}`
							WHERE item_id = %d AND item_type = %s AND engagement_kind = %s AND date_time >= %s
							GROUP BY user_id
						) latest ON p.id = latest.max_id
						WHERE p.status = %s
					)",
					$item_id,
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$since,
					WP_Ulike_Pulse_Vote_Map::ROW_REMOVED
				);
			}

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM (
						SELECT CAST(l.user_id AS CHAR) AS user_id FROM `{$legacy_table}` l
						WHERE {$legacy_status} AND l.`{$column}` = %d {$legacy_period}{$unliked_sql}
						UNION
						SELECT CAST(e.user_id AS CHAR) AS user_id FROM `{$pulse_table}` e
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
		// In merged mode $since is dual_since. Vote rows are duplicated in
		// legacy tables before the cutover, so scope only vote rows to
		// $since. Emoji/star have no legacy counterpart — never since-filter.
		$since_sql = '';
		if ( $since ) {
			$since_sql = $wpdb->prepare(
				" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) )",
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				$since
			);
		}

		// Count all engagement kinds (vote + emoji + star) so the "all logs"
		// total reflects every interaction, matching count_all_legacy_logs()
		// which counts every legacy row regardless of status.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$table}` WHERE 1=1 {$since_sql} {$period_limit}"
		);
	}

	/**
	 * Count pulse emoji/star rows (non-vote engagement) for legacy read mode,
	 * where classic votes come from legacy tables but emoji/star live in pulse.
	 *
	 * @param mixed  $period    Period filter.
	 * @param string $since     Optional since datetime.
	 * @param string $item_type Optional canonical item type to scope the count.
	 * @return int
	 */
	private static function count_pulse_non_vote_logs( $period, $since = '', $item_type = '' ) {
		global $wpdb;

		$period_limit = wp_ulike_get_period_limit_sql( $period );
		$table        = esc_sql( WP_Ulike_Pulse_Schema::table() );
		$since_sql    = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';
		$type_sql     = '';
		if ( $item_type ) {
			$type_sql = $wpdb->prepare(
				' AND item_type = %s',
				WP_Ulike_Pulse_Registry::normalize_item_type( $item_type )
			);
		}

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$table}` WHERE engagement_kind IN ('emoji','star'){$type_sql}{$since_sql}{$period_limit}"
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
		// Scope only vote rows to $since (dual_since); emoji/star have no
		// legacy counterpart and must never be since-filtered in merged mode.
		$since_sql = '';
		if ( $since ) {
			$since_sql = $wpdb->prepare(
				" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) )",
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				$since
			);
		}

		// Count all engagement kinds (vote + emoji + star) so per-type "all
		// logs" totals match the global count_pulse_logs() behavior. Per-item
		// vote counters use count_item_votes()/count_pulse_item_votes().
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE item_type = %s{$since_sql}{$period_limit}",
				WP_Ulike_Pulse_Registry::normalize_item_type( $item_type )
			)
		);
	}

		/**
		 * Count classic vote rows (engagement_kind = vote) for one content
		 * type across all statuses. Used by count_vote_logs_for_type() so the
		 * Pro "vote + emoji + star" sum does not double-count emoji/star that
		 * count_pulse_logs_for_type() already includes.
		 *
		 * @param string $item_type Canonical item type.
		 * @param mixed  $period    Period filter.
		 * @param string $since     Optional since datetime.
		 * @return int
		 */
		private static function count_pulse_vote_logs_for_type( $item_type, $period, $since = '' ) {
			global $wpdb;

			$period_limit = wp_ulike_get_period_limit_sql( $period );
			$table        = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$since_sql    = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE item_type = %s AND engagement_kind = %s{$since_sql}{$period_limit}",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}

		/**
		 * @param string $item_type       Canonical item type.
		 * @param string $legacy_status   Legacy status string.
		 * @param string $period_limit    Period SQL fragment.
		 * @param string $since           Optional since datetime.
		 * @return int
		 */
		private static function count_pulse_status_for_type( $item_type, $legacy_status, $period_limit, $since ) {
			global $wpdb;

			$mapped    = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $legacy_status );
			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}`
					WHERE item_type = %s AND engagement_kind = %s
					AND engagement_key = %s AND status = %s {$since_sql} {$period_limit}",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$mapped['engagement_key'],
					$mapped['status']
				)
			);
		}

		/**
		 * Merged-mode distinct voters for one type — UNION dedup across legacy
		 * and pulse so voters active in both stores are counted once.
		 *
		 * A legacy (user, item) pair is excluded when that user's latest
		 * post-cutover pulse action on the SAME item is an unlike/removal —
		 * mirrors the per-item exclusion in count_merged_distinct_item() so a
		 * user who liked pre-cutover then unliked post-cutover isn't still
		 * counted as a unique voter for the type.
		 *
		 * @param string $item_type    Canonical item type.
		 * @param string $legacy_table Escaped legacy table name.
		 * @param string $legacy_column Legacy item-id column name (e.g. post_id).
		 * @param string $period_limit Period SQL fragment.
		 * @return int
		 */
		private static function count_merged_unique_voters_for_type( $item_type, $legacy_table, $legacy_column, $period_limit ) {
			global $wpdb;

			$pulse_table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$column      = esc_sql( $legacy_column );
			$since       = WP_Ulike_Pulse_Config::dual_since();
			$legacy_per  = str_replace( 'date_time', 'l.date_time', $period_limit );
			$eng_per     = str_replace( 'date_time', 'e.date_time', $period_limit );

			$unliked_sql = '';
			if ( $since ) {
				$unliked_sql = $wpdb->prepare(
					" AND NOT EXISTS (
						SELECT 1 FROM `{$pulse_table}` p
						INNER JOIN (
							SELECT item_id, user_id, MAX(id) AS max_id
							FROM `{$pulse_table}`
							WHERE item_type = %s AND engagement_kind = %s AND date_time >= %s
							GROUP BY item_id, user_id
						) latest ON p.id = latest.max_id
						WHERE p.item_id = l.`{$column}` AND CAST(p.user_id AS CHAR) = CAST(l.user_id AS CHAR) AND p.status = %s
					)",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$since,
					WP_Ulike_Pulse_Vote_Map::ROW_REMOVED
				);
			}

			$legacy_actor = self::distinct_actor_sql( 'l' );
			$pulse_actor  = self::distinct_actor_sql( 'e' );

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT actor) FROM (
						SELECT {$legacy_actor} AS actor FROM `{$legacy_table}` l
						WHERE l.status IN ('like','dislike') {$legacy_per}{$unliked_sql}
						UNION
						SELECT {$pulse_actor} AS actor FROM `{$pulse_table}` e
						WHERE e.item_type = %s AND e.engagement_kind = %s AND e.status = 'active'
						AND e.date_time >= %s {$eng_per}
					) AS combined WHERE actor IS NOT NULL",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$since
				)
			);
		}

		/**
		 * @param string $item_type    Canonical item type.
		 * @param string $period_limit Period SQL fragment.
		 * @param string $since        Optional since datetime.
		 * @return int
		 */
	/**
	 * Distinct actor expression for unique-voter counts.
	 *
	 * Registered users key by user_id; guests (user_id 0/empty) key by fingerprint
	 * so they are not collapsed into a single bucket.
	 *
	 * Wrapped in CONVERT(... USING utf8mb4): CONCAT() inherits the *column's*
	 * collation, and legacy tables created by older WordPress versions often use
	 * a different collation than the newer pulse table (e.g. utf8mb4_unicode_ci
	 * vs utf8mb4_unicode_520_ci). UNION-ing the two arms then fails outright with
	 * "Illegal mix of collations", which silently reported 0 unique voters in
	 * dual/merged mode. CONVERT normalizes both arms to one collation. Do NOT use
	 * an explicit `COLLATE utf8mb4_*` here -- that breaks on utf8mb3 legacy tables.
	 *
	 * @param string $alias Optional table alias.
	 * @return string
	 */
	private static function distinct_actor_sql( $alias = '' ) {
		$prefix = $alias ? $alias . '.' : '';

		return "CONVERT(CASE
			WHEN {$prefix}user_id IS NOT NULL AND CAST({$prefix}user_id AS CHAR) NOT IN ('', '0') THEN CONCAT('u:', {$prefix}user_id)
			WHEN {$prefix}fingerprint IS NOT NULL AND CAST({$prefix}fingerprint AS CHAR) NOT IN ('', '0') THEN CONCAT('f:', {$prefix}fingerprint)
			ELSE NULL
		END USING utf8mb4)";
	}

	private static function count_pulse_unique_voters_for_type( $item_type, $period_limit, $since ) {
		global $wpdb;

		$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
		$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';
		$actor_sql = self::distinct_actor_sql();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT actor) FROM (
					SELECT {$actor_sql} AS actor FROM `{$table}`
					WHERE item_type = %s AND engagement_kind = %s {$since_sql} {$period_limit}
				) AS voters WHERE actor IS NOT NULL",
				WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
				WP_Ulike_Pulse_Registry::KIND_VOTE
			)
		);
	}

	/**
	 * Count distinct users who interacted with a type across ALL engagement
	 * kinds (vote + emoji + star), mode-aware. Used by Pro "unique
	 * voters/engagers" cards so a user is counted once whether they voted,
	 * reacted with emoji, or rated with stars.
	 *
	 * @param string $item_type Canonical item type.
	 * @param mixed  $period    Period filter.
	 * @return int
	 */
	public static function count_unique_interactors_for_type( $item_type, $period = 'all' ) {
		global $wpdb;

		$item_type    = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
		$period_limit = wp_ulike_get_period_limit_sql( $period );
		$mode         = self::read_mode();
		$source       = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
		$pulse_table  = esc_sql( WP_Ulike_Pulse_Schema::table() );
		$actor_sql    = self::distinct_actor_sql();

		// Pulse slice: all engagement kinds. In merged mode, scope vote rows to
		// dual_since (legacy holds pre-cutover votes); emoji/star have no legacy
		// counterpart so they are never since-filtered. Apply the period window.
		$pulse_per = $period_limit;
		if ( 'merged' === $mode && WP_Ulike_Pulse_Config::dual_since() ) {
			$since     = WP_Ulike_Pulse_Config::dual_since();
			$pulse_sql = $wpdb->prepare(
				"SELECT {$actor_sql} AS actor FROM `{$pulse_table}`
				WHERE item_type = %s AND status = 'active'
				AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) ) {$pulse_per}",
				$item_type,
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				$since
			);
		} else {
			$pulse_sql = $wpdb->prepare(
				"SELECT {$actor_sql} AS actor FROM `{$pulse_table}`
				WHERE item_type = %s AND status = 'active' {$pulse_per}",
				$item_type
			);
		}

		// Legacy slice: classic votes (legacy tables have no emoji/star).
		$union = array( $pulse_sql );
		if ( ( 'legacy' === $mode || 'merged' === $mode ) && $source
			&& WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
			$legacy_table = esc_sql( $source['table'] );
			$legacy_per   = str_replace( 'date_time', 'l.date_time', $period_limit );
			$legacy_actor = self::distinct_actor_sql( 'l' );
			$union[]      = "SELECT {$legacy_actor} AS actor FROM `{$legacy_table}` l WHERE l.status IN ('like','dislike') {$legacy_per}";
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT actor) FROM (" . implode( ' UNION ', $union ) . ") AS combined WHERE actor IS NOT NULL"
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

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $parsed_args['type'] );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return array();
			}

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

			$table   = esc_sql( $source['table'] );
			$column  = esc_sql( $source['column'] );
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
		 * @param string $since             Optional datetime floor (merged mode).
		 * @return array|null
		 */
		private static function popular_from_pulse( $parsed_args, $info_args, $period_limit, $user_condition, $related_condition, $limit_records, $since = '' ) {
			global $wpdb;

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return array();
			}

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
			$since_sql  = $since ? $wpdb->prepare( ' AND t.date_time >= %s', $since ) : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$count} AS counter, t.item_id AS item_ID FROM `{$table}` t {$join}
					WHERE t.item_type = %s AND t.engagement_kind = %s AND t.engagement_key IN ({$key_in})
					AND {$status_sql} {$user_condition} {$period_sql}{$since_sql}
					GROUP BY t.item_id ORDER BY `{$order_by}` {$order} {$limit_records}",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}

		/**
		 * Merged popular items — legacy totals plus pulse votes since dual_since.
		 *
		 * Aggregates in SQL and applies LIMIT there (never loads full rankings into PHP).
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
			global $wpdb;

			$since  = WP_Ulike_Pulse_Config::dual_since();
			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $parsed_args['type'] );
			$parts  = array();

			$statuses  = WP_Ulike_Pulse_Vote_Map::normalize_status_filter( $parsed_args['status'] );
			$status_in = implode(
				',',
				array_map(
					function ( $s ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $s );
					},
					$statuses
				)
			);
			$is_distinct = wp_ulike_setting_repo::isDistinct( $parsed_args['type'] );
			$order_by    = esc_sql( $parsed_args['is_popular'] ? 'counter' : 'item_ID' );
			$order       = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
			$item_type   = WP_Ulike_Pulse_Registry::from_setting_type( $parsed_args['type'] );
			$pulse       = esc_sql( WP_Ulike_Pulse_Schema::table() );

			// Distinct: union (item, user) once, then count — never SUM of two DISTINCT counts.
			if ( $is_distinct ) {
				$user_parts = array();

				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$table  = esc_sql( $source['table'] );
					$column = esc_sql( $source['column'] );
					$join   = self::popular_content_join( $parsed_args, $info_args, $related_condition, 't', $column );
					$period = str_replace( 'date_time', 't.date_time', $period_limit );
					// Scope the inner MAX(id) subquery — that table has no alias `p`.
					$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

					$user_parts[] = "SELECT t.`{$column}` AS item_ID, CAST(t.user_id AS CHAR) AS user_id
						FROM `{$table}` t {$join}
						WHERE t.status IN ({$status_in}) {$user_condition} {$period}
						AND CAST(t.user_id AS CHAR) NOT IN (
							SELECT CAST(p.user_id AS CHAR)
							FROM `{$pulse}` p
							INNER JOIN (
								SELECT MAX(id) AS max_id FROM `{$pulse}`
								WHERE item_type = '" . esc_sql( $item_type ) . "' AND engagement_kind = 'vote'{$since_sql}
								GROUP BY user_id, item_id
							) latest ON p.id = latest.max_id
							WHERE p.status = 'removed' AND p.item_id = t.`{$column}`
						)";
				}

				if ( WP_Ulike_Pulse_Schema::table_exists() ) {
					$filter = WP_Ulike_Pulse_Vote_Map::pulse_filter_from_legacy_statuses( $parsed_args['status'] );
					$key_in = implode(
						',',
						array_map(
							function ( $k ) use ( $wpdb ) {
								return $wpdb->prepare( '%s', $k );
							},
							$filter['keys']
						)
					);
					$status_sql = $filter['active_only'] ? "t.status = 'active'" : "t.status IN ('active','removed')";
					$join       = self::popular_content_join( $parsed_args, $info_args, $related_condition, 't', 'item_id', true );
					$period_sql = str_replace( 'date_time', 't.date_time', $period_limit );
					$since_sql  = $since ? $wpdb->prepare( ' AND t.date_time >= %s', $since ) : '';

					$user_parts[] = $wpdb->prepare(
						"SELECT t.item_id AS item_ID, CAST(t.user_id AS CHAR) AS user_id
						FROM `{$pulse}` t {$join}
						WHERE t.item_type = %s AND t.engagement_kind = %s AND t.engagement_key IN ({$key_in})
						AND {$status_sql} {$user_condition} {$period_sql}{$since_sql}",
						$item_type,
						WP_Ulike_Pulse_Registry::KIND_VOTE
					);
				}

				if ( empty( $user_parts ) ) {
					return array();
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return $wpdb->get_results(
					'SELECT item_ID, COUNT(DISTINCT user_id) AS counter FROM ( ' . implode( ' UNION ALL ', $user_parts ) . " ) AS combined
					GROUP BY item_ID ORDER BY `{$order_by}` {$order} {$limit_records}"
				);
			}

			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$column = esc_sql( $source['column'] );
				$count  = "COUNT(t.`{$column}`)";
				$join   = self::popular_content_join( $parsed_args, $info_args, $related_condition, 't', $column );
				$parts[] = "SELECT t.`{$column}` AS item_ID, {$count} AS counter FROM `{$table}` t {$join}
					WHERE t.status IN ({$status_in}) {$user_condition} {$period_limit}
					GROUP BY t.`{$column}`";
			}

			if ( WP_Ulike_Pulse_Schema::table_exists() ) {
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
				$count      = 'COUNT(t.item_id)';
				$join       = self::popular_content_join( $parsed_args, $info_args, $related_condition, 't', 'item_id', true );
				$period_sql = str_replace( 'date_time', 't.date_time', $period_limit );
				$since_sql  = $since ? $wpdb->prepare( ' AND t.date_time >= %s', $since ) : '';

				$parts[] = $wpdb->prepare(
					"SELECT t.item_id AS item_ID, {$count} AS counter FROM `{$pulse}` t {$join}
					WHERE t.item_type = %s AND t.engagement_kind = %s AND t.engagement_key IN ({$key_in})
					AND {$status_sql} {$user_condition} {$period_sql}{$since_sql}
					GROUP BY t.item_id",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				);
			}

			if ( empty( $parts ) ) {
				return array();
			}

			// Non-distinct: row counts from each store are additive (append logging).
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				'SELECT item_ID, SUM(counter) AS counter FROM ( ' . implode( ' UNION ALL ', $parts ) . " ) AS combined
				GROUP BY item_ID ORDER BY `{$order_by}` {$order} {$limit_records}"
			);
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
				engagement_kind, engagement_key, status, value, ip, fingerprint, country_code, device
				FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND user_id = %s
				AND engagement_kind = %s AND status = %s
				ORDER BY date_time DESC, id DESC LIMIT 1",
				absint( $item_id ),
				WP_Ulike_Pulse_Registry::from_setting_type( $type ),
				(string) $user_id,
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				'active'
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
		 * @param string|array $status     Legacy status filter (raw).
		 * @param string     $period_limit Period SQL.
		 * @param string     $order        ASC|DESC.
		 * @param int        $offset       Offset.
		 * @param int        $limit        Limit (0 = all).
		 * @param string     $since        Optional dual-since cutoff (empty = no filter).
		 * @return array
		 */
		private static function query_user_items_pulse( $user_id, $type, $status, $period_limit, $order, $offset, $limit, $since = '' ) {
			global $wpdb;

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$period    = $period_limit;
			$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';

			$filter     = WP_Ulike_Pulse_Vote_Map::pulse_filter_from_legacy_statuses( $status );
			$key_in     = implode(
				',',
				array_map(
					function ( $k ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $k );
					},
					$filter['keys']
				)
			);
			$status_sql = $filter['active_only'] ? "status = 'active'" : "status IN ('active','removed')";
			$since_sql  = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT item_id AS itemID, MAX(date_time) AS datetime,
					MAX(CASE WHEN status = 'active' THEN engagement_key ELSE status END) AS lastStatus
					FROM `{$table}`
					WHERE user_id = %s AND item_type = %s AND engagement_kind = %s
					AND engagement_key IN ({$key_in}) AND {$status_sql}{$since_sql} {$period}
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
		 * @param string       $type         Setting type.
		 * @param string|array $status       Legacy status filter (raw).
		 * @param string       $period_limit Period SQL.
		 * @param string       $order        ASC|DESC.
		 * @param int          $offset       Offset.
		 * @param int          $limit        Limit.
		 * @param string       $since        Optional dual-since cutoff (empty = no filter).
		 * @return array
		 */
		private static function query_users_pulse( $type, $status, $period_limit, $order, $offset, $limit, $since = '' ) {
			global $wpdb;

			$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $type );
			$period    = $period_limit;
			$limit_sql = $limit > 0 ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '';

			$filter     = WP_Ulike_Pulse_Vote_Map::pulse_filter_from_legacy_statuses( $status );
			$key_in     = implode(
				',',
				array_map(
					function ( $k ) use ( $wpdb ) {
						return $wpdb->prepare( '%s', $k );
					},
					$filter['keys']
				)
			);
			$status_sql = $filter['active_only'] ? "status = 'active'" : "status IN ('active','removed')";
			$since_sql  = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.user_id AS userID, COUNT(*) AS score, MAX(t.date_time) AS datetime,
					MAX(t.engagement_key) AS lastStatus,
					SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT t.item_id ORDER BY t.item_id DESC SEPARATOR ','), ',', 500) AS itemsList
					FROM `{$table}` t
					INNER JOIN {$wpdb->users} u ON u.ID = t.user_id
					WHERE t.item_type = %s AND t.engagement_kind = %s
					AND t.engagement_key IN ({$key_in}) AND {$status_sql}{$since_sql} {$period}
					GROUP BY t.user_id
					ORDER BY score {$order} {$limit_sql}",
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			return is_array( $rows ) ? $rows : array();
		}

	/**
	 * @param int    $item_id Item ID.
	 * @param string $type    Setting type.
	 * @param int    $limit   Limit.
	 * @param string $since   Optional datetime floor (merged mode).
	 * @return array
	 */
	private static function fetch_pulse_likers( $item_id, $type, $limit, $since = '' ) {
		global $wpdb;

		$table     = esc_sql( WP_Ulike_Pulse_Schema::table() );
		$since_sql = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';

		// One entry per user, latest action wins: take the newest row per
		// (user) within the candidate window, then keep only those whose
		// latest row is an active like. Standard SQL — avoids the
		// `SELECT DISTINCT ... ORDER BY` non-determinism that could surface
		// duplicate user IDs and stale likers (liked then unliked).
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.user_id
				FROM `{$table}` p
				INNER JOIN (
					SELECT MAX(id) AS max_id
					FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND engagement_kind = %s{$since_sql}
					GROUP BY user_id
				) latest ON p.id = latest.max_id
				WHERE p.status = %s
				ORDER BY p.date_time DESC
				LIMIT %d",
				absint( $item_id ),
				WP_Ulike_Pulse_Registry::from_setting_type( $type ),
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				WP_Ulike_Pulse_Vote_Map::ROW_ACTIVE,
				absint( $limit )
			)
		);
	}

	/**
	 * Users who removed their like for an item on pulse since the dual cutoff.
	 * Used by rebuild_likers_list() to exclude stale legacy likers in merged mode.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $type    Setting type.
	 * @param string $since   Dual-since cutoff (mysql datetime).
	 * @return array<int,string>
	 */
	private static function fetch_pulse_unlikers( $item_id, $type, $since = '', $candidate_users = array() ) {
		global $wpdb;

		// Nothing to check against -- avoid an unbounded scan of every unlike
		// ever recorded for the item when the caller only cares whether a
		// small, already-limited set of candidate user IDs is in it.
		if ( empty( $candidate_users ) ) {
			return array();
		}

		$table        = esc_sql( WP_Ulike_Pulse_Schema::table() );
		$since_sql    = $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '';
		$placeholders = implode( ',', array_fill( 0, count( $candidate_users ), '%s' ) );

		// Users whose LATEST action on this item is an unlike (status=removed).
		// A user who unliked then re-liked has a newer active row, so they are
		// NOT included here — keeping them in the likers list where they belong.
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.user_id
				FROM `{$table}` p
				INNER JOIN (
					SELECT MAX(id) AS max_id
					FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND engagement_kind = %s AND user_id IN ({$placeholders}){$since_sql}
					GROUP BY user_id
				) latest ON p.id = latest.max_id
				WHERE p.status = %s",
				array_merge(
					array(
						absint( $item_id ),
						WP_Ulike_Pulse_Registry::from_setting_type( $type ),
						WP_Ulike_Pulse_Registry::KIND_VOTE,
					),
					array_map( 'strval', $candidate_users ),
					array(
						WP_Ulike_Pulse_Vote_Map::ROW_REMOVED,
					)
				)
			)
		);
	}
	}
}
