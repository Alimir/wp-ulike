<?php
/**
 * Query Controllers
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Popular Items
*******************************************************/

if ( ! function_exists( 'wp_ulike_normalize_vote_statuses' ) ) {
	/**
	 * Sanitize vote status filter values for SQL queries.
	 *
	 * @param string|array $status  Requested status(es).
	 * @param string|array $default Fallback when empty or invalid.
	 * @return string|array
	 */
	function wp_ulike_normalize_vote_statuses( $status, $default = array( 'like', 'dislike' ) ) {
		$allowed = array( 'like', 'dislike', 'unlike', 'undislike' );

		if ( is_array( $status ) ) {
			$status = array_values( array_intersect( array_map( 'strval', $status ), $allowed ) );
			if ( ! empty( $status ) ) {
				return $status;
			}
			return is_array( $default ) ? $default : array( $default );
		}

		if ( is_string( $status ) && in_array( $status, $allowed, true ) ) {
			return $status;
		}

		return $default;
	}
}

if ( ! function_exists( 'wp_ulike_get_log_table_names' ) ) {
	/**
	 * ULike log table names for cross-table aggregation queries.
	 *
	 * @return string[]
	 */
	function wp_ulike_get_log_table_names() {
		return WP_Ulike_Pulse_Registry::log_table_names();
	}
}

if( ! function_exists( 'wp_ulike_get_popular_items_info' ) ){
	/**
	 * Get popular items with their counter & ID
	 *
	 * @param array $args
	 * @return object|null
	 */
	function wp_ulike_get_popular_items_info( $args = array() ){
		// Global wordpress database object
		global $wpdb;
		//Main data
		$defaults = array(
			"type"       => 'post',
			"rel_type"   => 'post',
			"status"     => 'like',
			"user_id"    => '',
			"order"      => 'DESC',
			"is_popular" => true,
			"period"     => 'all',
			"offset"     => 1,
			"limit"      => 10
		);
		$parsed_args           = wp_parse_args( $args, $defaults );
		$parsed_args['status'] = wp_ulike_normalize_vote_statuses( $parsed_args['status'], $defaults['status'] );
		$info_args             = wp_ulike_get_table_info( $parsed_args['type'] );
		$period_limit          = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		$logical_key = 'items_' . md5( serialize( $parsed_args ) );
		$results     = WP_Ulike_Query_Cache::get( $logical_key );
		if( false !== $results ){
			return $results;
		}

		$limit_records = '';
		if( (int) $parsed_args['limit'] > 0 ){
			$offset = $parsed_args['offset'] > 0 ? ( $parsed_args['offset'] - 1 ) * $parsed_args['limit'] : 0;
			$limit_records = $wpdb->prepare( "LIMIT %d, %d", $offset, $parsed_args['limit'] );
		}

		$related_condition = '';
		switch ($parsed_args['type']) {
			case 'post':
			case 'topic':
				$post_type = '';
				if ( is_array( $parsed_args['rel_type'] ) && ! empty( $parsed_args['rel_type'] ) ) {
					$rel_types = array_map(function($rel_type) use ($wpdb) {
						return $wpdb->prepare('%s', $rel_type);
					}, $parsed_args['rel_type']);

					$post_type = " AND r.post_type IN (" . implode(',', $rel_types) . ")";
				} elseif( ! is_array( $parsed_args['rel_type'] ) && ! empty( $parsed_args['rel_type'] ) ) {
					$post_type = $wpdb->prepare( " AND r.post_type = %s", $parsed_args['rel_type'] );
				}

				$related_condition = "AND r.post_status IN ('publish', 'inherit', 'private')" . $post_type;
				break;
		}

		$user_condition = '';
		if( !empty( $parsed_args['user_id'] ) ){
			if( is_array( $parsed_args['user_id'] ) && ! empty( $parsed_args['user_id'] ) ){
				$user_ids = array_map(function($user_id) use ($wpdb) {
					return $wpdb->prepare('%s', $user_id);
				}, $parsed_args['user_id']);

				$user_condition = " AND t.user_id IN (" . implode(',', $user_ids) . ")";
			} elseif ( ! is_array( $parsed_args['user_id'] ) ) {
				$user_condition = $wpdb->prepare( " AND t.user_id = %s", $parsed_args['user_id'] );
			}
		}

		$order_by = $parsed_args['is_popular'] ? 'counter' : 'item_ID';

		$query = '';
		$status_type = '';
		/**
		 * If user id and period limit are not set, we use the meta table to get the information. This creates more optimization.
		 */
		if( empty( $period_limit ) && empty( $user_condition ) ){
			// create query condition from status
			$meta_prefix = wp_ulike_setting_repo::isDistinct( $parsed_args['type'] ) ? 'count_distinct_' : 'count_total_';
			if( is_array( $parsed_args['status'] ) ){
				$status_conditions = [];
				foreach ($parsed_args['status'] as $value) {
					// Use exact match instead of LIKE for better performance with millions of rows
					$status_conditions[] = $wpdb->prepare("t.meta_key = %s", $meta_prefix . $value);
				}
				$status_type = sprintf(" AND (%s)", implode(" OR ", $status_conditions));
			} else {
				// Use exact match instead of LIKE for better performance with millions of rows
				$status_type = $wpdb->prepare( " AND t.meta_key = %s",  $meta_prefix . $parsed_args['status'] );
			}

			// generate query string
			$meta_table = $wpdb->prefix . 'ulike_meta';
			$related_table = esc_sql( $info_args['related_table_prefix'] );
			$related_column = esc_sql( $info_args['related_column'] );
			$order_by_escaped = esc_sql( $order_by );
			$order_escaped = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			$query  = $wpdb->prepare( "
				SELECT t.item_id AS item_ID, MAX(CAST(t.meta_value AS UNSIGNED)) as counter
				FROM `{$meta_table}` t
				INNER JOIN `{$related_table}` r ON t.item_id = r.`{$related_column}` {$related_condition}
				WHERE t.meta_group = %s AND t.meta_value > 0 {$status_type}
				GROUP BY item_ID
				ORDER BY `{$order_by_escaped}` {$order_escaped} {$limit_records}",
				$parsed_args['type']
			);

		} else {
			$results = WP_Ulike_Pulse_Query::get_popular_items_from_logs(
				$parsed_args,
				$info_args,
				$period_limit,
				$user_condition,
				$related_condition,
				$limit_records
			);

			if ( ! empty( $results ) ) {
				WP_Ulike_Query_Cache::set( $logical_key, $results );
			}

			return $results;
		}

		$results = !empty( $query ) ? $wpdb->get_results( $query ): null;

		if( ! empty( $results ) ){
			WP_Ulike_Query_Cache::set( $logical_key, $results );
		}

		return $results;
	}
}

if( ! function_exists( 'wp_ulike_get_popular_items_ids' ) ){
	/**
	 * Get popular items with their IDs
	 *
	 * @param array $args
	 * @return array
	 */
	function wp_ulike_get_popular_items_ids( $args = array() ){
		//Main data
		$defaults = array(
			"type"       => 'post',
			"rel_type"   => 'post',
			"status"     => 'like',
			"user_id"    => '',
			"order"      => 'DESC',
			"is_popular" => true,
			"period"     => 'all',
			"offset"     => 1,
			"limit"      => 10
		);
		$parsed_args = wp_parse_args( $args, $defaults );
		$item_info   = wp_ulike_get_popular_items_info( $parsed_args );
		$ids_stack   = array();
		if( ! empty( $item_info ) ){
			foreach ($item_info as $key => $info) {
				$ids_stack[] = $info->item_ID;
			}
		}

		return $ids_stack;
	}
}

if( ! function_exists( 'wp_ulike_get_popular_items_total_number' ) ){
	/**
	 * Get popular items total number
	 *
	 * @param array $args
	 * @return string|null
	 */
	function wp_ulike_get_popular_items_total_number( $args = array() ){
		// Global wordpress database object
		global $wpdb;
		//Main data
		$defaults = array(
			"type"     => 'post',
			"status"   => 'like',
			"period"   => 'all',
			"user_id"  => '',
			"rel_type" => 'post'
		);
		$parsed_args           = wp_parse_args( $args, $defaults );
		$parsed_args['status'] = wp_ulike_normalize_vote_statuses( $parsed_args['status'], $defaults['status'] );
		$info_args             = wp_ulike_get_table_info( $parsed_args['type'] );
		$period_limit          = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		$related_condition = '';
		switch ($parsed_args['type']) {
			case 'post':
			case 'topic':
				$post_type = '';
				if ( is_array( $parsed_args['rel_type'] ) && ! empty( $parsed_args['rel_type'] ) ) {
					$rel_types = array_map(function($rel_type) use ($wpdb) {
						return $wpdb->prepare('%s', $rel_type);
					}, $parsed_args['rel_type']);

					$post_type = " AND r.post_type IN (" . implode(',', $rel_types) . ")";
				} elseif( ! is_array( $parsed_args['rel_type'] ) && ! empty( $parsed_args['rel_type'] ) ) {
					$post_type = $wpdb->prepare( " AND r.post_type = %s", $parsed_args['rel_type'] );
				}

				$related_condition = "AND r.post_status IN ('publish', 'inherit', 'private')" . $post_type;
				break;
		}


		$user_condition = '';
		if( !empty( $parsed_args['user_id'] ) ){
			if( is_array( $parsed_args['user_id'] ) && ! empty( $parsed_args['user_id'] ) ){
				$user_ids = array_map(function($user_id) use ($wpdb) {
					return $wpdb->prepare('%s', $user_id);
				}, $parsed_args['user_id']);

				$user_condition = " AND t.user_id IN (" . implode(',', $user_ids) . ")";
			} elseif ( ! is_array( $parsed_args['user_id'] ) ) {
				$user_condition = $wpdb->prepare( " AND t.user_id = %s", $parsed_args['user_id'] );
			}
		}

		$query = '';
		$status_type = '';
		/**
		 * If user id and period limit are not set, we use the meta table to get the information. This creates more optimization.
		 */
		if( empty( $period_limit ) && empty( $user_condition ) ){
			// create query condition from status
			$meta_prefix = wp_ulike_setting_repo::isDistinct( $parsed_args['type'] ) ? 'count_distinct_' : 'count_total_';
			if( is_array( $parsed_args['status'] ) ){
				$status_conditions = [];
				foreach ($parsed_args['status'] as $value) {
					// Use exact match instead of LIKE for better performance with millions of rows
					$status_conditions[] = $wpdb->prepare("t.meta_key = %s", $meta_prefix . $value);
				}
				$status_type = sprintf(" AND (%s)", implode(" OR ", $status_conditions));
			} else {
				// Use exact match instead of LIKE for better performance with millions of rows
				$status_type = $wpdb->prepare( " AND t.meta_key = %s",  $meta_prefix . $parsed_args['status'] );
			}

			// generate query string
			$meta_table = $wpdb->prefix . 'ulike_meta';
			$related_table = esc_sql( $info_args['related_table_prefix'] );
			$related_column = esc_sql( $info_args['related_column'] );

			$query  = $wpdb->prepare( "
				SELECT COUNT(DISTINCT t.item_id)
				FROM `{$meta_table}` t
				INNER JOIN `{$related_table}` r ON t.item_id = r.`{$related_column}` {$related_condition}
				WHERE t.meta_value > 0 AND t.meta_group = %s {$status_type}",
				$parsed_args['type']
			);

		} else {
			return WP_Ulike_Pulse_Query::count_popular_items_total(
				$parsed_args,
				$info_args,
				$period_limit,
				$user_condition,
				$related_condition
			);
		}

		return !empty( $query ) ? (int) $wpdb->get_var( $query ): null;
	}
}

/*******************************************************
  User Data
*******************************************************/

if( ! function_exists( 'wp_ulike_get_likers_list_per_post' ) ){
	/**
	 * Get likers list for a specific item
	 *
	 * @param string  $table_name  Table name (without prefix)
	 * @param string  $column_name Column name for item ID
	 * @param integer $item_ID     Item ID
	 * @param integer $limit        Number of likers to return (null = all)
	 * @return array Array of user IDs
	 */
	function wp_ulike_get_likers_list_per_post( $table_name, $column_name, $item_ID, $limit = 10 ){
		global $wpdb;

		// Sanitize inputs
		$item_ID = absint( $item_ID );
		$limit = is_null( $limit ) ? null : absint( $limit );

		if ( empty( $item_ID ) ) {
			return array();
		}

		$item_type = wp_ulike_get_type_by_table( $table_name );
		$item_opts = wp_ulike_get_post_settings_by_type( $item_type );

		// Try to get from meta cache first
		$get_likers = wp_ulike_get_meta_data( $item_ID, $item_type, 'likers_list', true );

		// If meta cache is empty, try object cache, then database
		if( empty( $get_likers ) && $get_likers !== '0' ){
			$logical_key = sprintf( '%s_%s_%d_likers_list', $table_name, $column_name, $item_ID );
			$get_likers  = WP_Ulike_Query_Cache::get( $logical_key );

			if( false === $get_likers ){
				$base_limit = is_null( $limit ) ? 100 : $limit;
				$max_likers = min( max( $base_limit * 10, 100 ), 1000 );

				$user_ids = WP_Ulike_Pulse_Query::rebuild_likers_list(
					$wpdb->prefix . $table_name,
					$column_name,
					$item_ID,
					$max_likers
				);

				$get_likers = ! empty( $user_ids ) ? implode( ',', $user_ids ) : '';

				WP_Ulike_Query_Cache::set( $logical_key, $get_likers );
			}

			// Update meta cache if we got data
			if( ! empty( $get_likers ) ){
				$get_likers = explode( ',', $get_likers );
				wp_ulike_update_meta_data( $item_ID, $item_type, 'likers_list', $get_likers );
			} else {
				$get_likers = array();
			}
		}

		// Ensure we have an array
		if( ! is_array( $get_likers ) ){
			$get_likers = ! empty( $get_likers ) ? explode( ',', $get_likers ) : array();
		}

		// Normalise: one entry per user. The likers_list meta is updated
		// incrementally on each vote and can accumulate duplicate IDs (concurrent
		// votes, mixed int/string keys, legacy comma strings). Dedupe here so the
		// list never shows the same user twice regardless of source.
		if ( ! empty( $get_likers ) ) {
			$get_likers = array_values( array_filter( array_unique( array_map( 'absint', (array) $get_likers ) ) ) );
		}

		// Apply ordering if needed
		if( ! empty( $get_likers ) && ! empty( $item_opts['setting'] ) ){
			$order = wp_ulike_get_option( $item_opts['setting'] . '|likers_order', 'desc' );
			if( $order === 'desc' ){
				$get_likers = array_reverse( $get_likers );
			}
		}

		// Apply limit if specified
		$output = ! empty( $get_likers ) && ! is_null( $limit )
			? array_slice( $get_likers, 0, $limit )
			: $get_likers;

		return apply_filters( 'wp_ulike_get_likers_list', $output, $item_type, $item_ID );
	}
}

if( ! function_exists( 'wp_ulike_is_user_liked' ) ) {
	/**
	 * A simple function to check if user has been liked post or not
	 *
	 * @param integer $item_ID
	 * @param integer $user_ID
	 * @param string $type
	 * @return bool
	 */
	function wp_ulike_is_user_liked( $item_ID, $user_ID,  $type = 'likeThis' ) {
		return WP_Ulike_Pulse_Query::is_user_liked( $item_ID, $user_ID, $type );
	}
}

if( ! function_exists( 'wp_ulike_user_item_history_store' ) ) {
	/**
	 * Shared request-level store behind wp_ulike_get_user_item_history().
	 * Returned by reference so reader and flusher share one array.
	 *
	 * @return array
	 */
	function &wp_ulike_user_item_history_store(){
		static $store = array();
		return $store;
	}
}

if( ! function_exists( 'wp_ulike_user_item_history_pending' ) ) {
	/**
	 * Buckets whose memo has entries not yet written to user meta.
	 * Shape: [ bucket => [ 'user' => string, 'meta_key' => string ] ]
	 *
	 * @return array
	 */
	function &wp_ulike_user_item_history_pending(){
		static $pending = array();
		return $pending;
	}
}

if( ! function_exists( 'wp_ulike_flush_user_state_cache' ) ) {
	/**
	 * Drop every request-level memo of "what has this user voted on".
	 *
	 * Runs after a vote is recorded so anything reading the status later in the
	 * same request (integrations on wp_ulike_after_process, a second button for
	 * the same item, wp_ulike_is_user_liked()) sees the new state rather than
	 * the value memoised before the write.
	 *
	 * Pending writes are dropped with it: updateUserMetaStatus() has just written
	 * the authoritative meta, so flushing stale memo content over it would undo
	 * the vote that was just recorded.
	 *
	 * @return void
	 */
	function wp_ulike_flush_user_state_cache(){
		$store = &wp_ulike_user_item_history_store();
		$store = array();

		$pending = &wp_ulike_user_item_history_pending();
		$pending = array();

		if( class_exists( 'WP_Ulike_Pulse_Reader' ) ){
			WP_Ulike_Pulse_Reader::flush_request_cache();
		}
	}
}

if( ! function_exists( 'wp_ulike_persist_user_item_history' ) ) {
	/**
	 * Write the request's discovered vote statuses to user meta, once.
	 *
	 * Called on shutdown. Writing inside wp_ulike_get_user_item_history() instead
	 * costs one UPDATE per item, so an archive of 20 items produced 20 writes --
	 * each rewriting the whole serialized array -- for a single pageview.
	 *
	 * Existing keys are never overwritten: the meta re-read here is authoritative
	 * (a vote recorded mid-request already wrote it), the memo only fills gaps.
	 *
	 * @return void
	 */
	function wp_ulike_persist_user_item_history(){
		$pending = &wp_ulike_user_item_history_pending();

		if( empty( $pending ) ){
			return;
		}

		$store   = &wp_ulike_user_item_history_store();
		$buckets = $pending;
		$pending = array();

		foreach( $buckets as $bucket => $info ){
			if( ! isset( $store[ $bucket ] ) ){
				continue;
			}

			$stored  = wp_ulike_get_meta_data( $info['user'], 'user', $info['meta_key'], true );
			$stored  = is_array( $stored ) ? $stored : array();
			$changed = false;

			foreach( $store[ $bucket ] as $item_id => $status ){
				if( ! array_key_exists( $item_id, $stored ) ){
					$stored[ $item_id ] = $status;
					$changed = true;
				}
			}

			if( $changed ){
				wp_ulike_update_meta_data( $info['user'], 'user', $info['meta_key'], $stored );
			}
		}
	}
	add_action( 'shutdown', 'wp_ulike_persist_user_item_history', 5 );
}

if( ! function_exists( 'wp_ulike_get_user_item_history' ) ) {
	/**
	 * A simple function to get user activity history
	 *
	 * Caches both votes and "never voted" for an item so multi-button pages
	 * (and later views) do not re-query Pulse for the same miss.
	 *
	 * @param array $args
	 * @return array
	 */
	function wp_ulike_get_user_item_history( $args ) {
		$defaults = array(
			"item_id"           => '',
			"item_type"         => '',
			"current_user"      => '',
			"settings"          => '',
			"is_user_logged_in" => ''
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$meta_key = sanitize_key( $parsed_args['item_type'] . '_status' );
		$item_id  = $parsed_args['item_id'];
		$bucket   = (string) $parsed_args['current_user'] . '|' . $meta_key;

		// Request-level memo: one meta load / Pulse lookup per user+type+item.
		// Held in a shared store rather than a function static so a vote written
		// later in the same request can invalidate it -- otherwise a read-after-
		// write returns the pre-vote status.
		$runtime = &wp_ulike_user_item_history_store();

		if ( ! isset( $runtime[ $bucket ] ) ) {
			$stored = wp_ulike_get_meta_data( $parsed_args['current_user'], 'user', $meta_key, true );
			$runtime[ $bucket ] = is_array( $stored ) ? $stored : array();
		}

		if ( ! array_key_exists( $item_id, $runtime[ $bucket ] ) ) {
			$user_status = WP_Ulike_Pulse_Reader::user_action(
				$item_id,
				$parsed_args['current_user'],
				$parsed_args['item_type']
			);

			// Empty string = known negative (never voted). array_key_exists skips re-query.
			$runtime[ $bucket ][ $item_id ] = ! empty( $user_status ) ? $user_status : '';

			// Persist real votes always. Persist "never voted" for logged-in users
			// so later pageviews skip Pulse; skip creating guest meta rows for
			// one-off anonymous views (request cache still covers multi-button pages).
			// Queued, not written here: one write per request instead of one per
			// item (see wp_ulike_persist_user_item_history).
			if ( ! empty( $user_status ) || ! empty( $parsed_args['is_user_logged_in'] ) ) {
				$pending = &wp_ulike_user_item_history_pending();
				$pending[ $bucket ] = array(
					'user'     => $parsed_args['current_user'],
					'meta_key' => $meta_key,
				);
			}
		}

		return $runtime[ $bucket ];
	}
}

if( ! function_exists( 'wp_ulike_get_user_latest_activity' ) ) {
	/**
	 * Get user latest activity details for each item
	 *
	 * @param integer $item_id
	 * @param integer $user_id
	 * @param string $type
	 * @return array|null
	 */
	function wp_ulike_get_user_latest_activity( $item_id, $user_id, $type ) {
		$row = WP_Ulike_Pulse_Query::get_user_latest_activity( $item_id, $user_id, $type );
		if ( ! $row ) {
			return null;
		}

		$result = array(
			'date_time' => $row->date_time,
			'status'    => isset( $row->status ) ? $row->status : '',
		);

		if ( ! empty( $result['date_time'] ) ) {
			$result['date_time'] = wp_ulike_date_i18n( $result['date_time'] );
		}

		if ( in_array( $result['status'], array( 'like', 'dislike', 'active', 'removed' ), true ) ) {
			if ( 'active' === $result['status'] || 'removed' === $result['status'] ) {
				$key = isset( $row->engagement_key ) ? $row->engagement_key : WP_Ulike_Pulse_Vote_Map::KEY_LIKE;
				$result['status'] = WP_Ulike_Pulse_Vote_Map::row_to_legacy( $key, $result['status'] );
			}
		}

		return $result;
	}
}

if( ! function_exists( 'wp_ulike_get_user_item_count_per_day' ) ) {
	/**
	 * A simple function to get user vote counter per day
	 *
	 * @param array $args
	 * @return array
	 */
	function wp_ulike_get_user_item_count_per_day( $args ) {
		return WP_Ulike_Pulse_Query::count_user_votes_today( $args );
	}
}

if( ! function_exists('wp_ulike_get_best_likers_info') ){
    /**
     * Get most liked users in query
     *
     * @param integer $limit
     * @param string $period
     * @param integer $offset
     * @param array   $status
     * @param string  $order    ASC|DESC — sort by total reactions in period.
     * @return object
     */
	function wp_ulike_get_best_likers_info( $limit, $period, $offset = 1, $status = array( 'like', 'dislike' ), $order = 'DESC' ) {
		$results = WP_Ulike_Pulse_Query::get_best_likers( $limit, $period, $offset, $status, $order );
		return is_array( $results ) ? $results : array();
	}
}


if( ! function_exists('wp_ulike_get_top_enagers_total_number') ){
    /**
	 * calculate the total number of unique users based on their engagement
	 *
	 * @param string|array $period
	 * @return integer
	 */
    function wp_ulike_get_top_enagers_total_number( $period, $status = [ 'like', 'dislike' ] ){
		return WP_Ulike_Pulse_Query::count_unique_engagers( $period, $status );
    }
}

if( ! function_exists('wp_ulike_get_user_data') ){
	/**
	 * Get user logs
	 *
	 * @param integer $user_ID
	 * @param array $args
	 * @return object|null
	 */
	function wp_ulike_get_user_data( $user_ID, $args = array() ){
		return WP_Ulike_Pulse_Query::get_user_data( $user_ID, $args );
	}

}

if( ! function_exists( 'wp_ulike_get_users' ) ){
	/**
	 * Retrieve list of users with their like activity
	 *
	 * @param array $args {
	 *     Optional. Arguments to retrieve users.
	 *     @type string $type     Item type (post, comment, etc.)
	 *     @type string $period   Time period filter
	 *     @type string $order    Sort order (ASC/DESC)
	 *     @type string|array $status Vote status(es) to filter
	 *     @type int    $page     Page number
	 *     @type int    $per_page Number of users per page
	 * }
	 * @return array|null Array of user objects with activity data
	 */
	function wp_ulike_get_users( $args = array() ){
		return WP_Ulike_Pulse_Query::get_users( $args );
	}
}

/*******************************************************
  General
*******************************************************/

if( ! function_exists( 'wp_ulike_get_rating_value' ) ){
	/**
	 * Calculate rating value by user logs & date_time
	 *
	 * @deprecated 5.2.0 No longer supported.
	 *
	 * @author       	Alimir
	 * @param           Integer $post_ID
	 * @param           Boolean $is_decimal
	 * @since           2.7
	 * @return          null
	 */
	function wp_ulike_get_rating_value( $post_ID, $is_decimal = true ) {
		_deprecated_function( __FUNCTION__, '5.2.0' );

		return apply_filters( 'wp_ulike_rating_value', null, $post_ID );
	}
}

if( ! function_exists('wp_ulike_count_all_logs') ){
    /**
     * Count logs from all tables
     *
     * @param string $period    Available values: all, today, yesterday
     * @return integer
     */
	function wp_ulike_count_all_logs( $period = 'all' ){
        // Convert array period
        if( is_array( $period ) ){
            $period = implode( '-', $period );
        }

        $logical_key = 'count_logs_period_' . $period;

        if( $period === 'all' ){
            $count_all_logs = WP_Ulike_Query_Cache::get_statistics_meta( $logical_key );
            if( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ){
                return absint($count_all_logs);
            }
        }

        $counter_value = WP_Ulike_Query_Cache::remember_stats(
            $logical_key,
            static function () use ( $period ) {
                return WP_Ulike_Pulse_Query::count_logs_for_mode( $period );
            }
        );

        if( $period === 'all' ){
            WP_Ulike_Query_Cache::set_statistics_meta( $logical_key, $counter_value );
        }

        return empty( $counter_value ) ? 0 : absint($counter_value);
    }
}

if( ! function_exists('wp_ulike_count_current_fingerprint') ){
	/**
	 * Check if user fingerprint has exceeded vote limit for the given item.
	 *
	 * Uses WordPress object caching to minimize DB queries.
	 *
	 * @param int $current_fingerprint
	 * @param int $item_id
	 * @param string $type
	 * @return integer
	 */
	function wp_ulike_count_current_fingerprint( $current_fingerprint, $item_id, $type ) {
		$logical_key = 'fingerprint_' . md5( $type . '_' . $item_id . '_' . $current_fingerprint );

		return (int) WP_Ulike_Query_Cache::remember(
			$logical_key,
			static function () use ( $current_fingerprint, $item_id, $type ) {
				return WP_Ulike_Pulse_Query::count_fingerprint_votes( $current_fingerprint, $item_id, $type );
			},
			WP_Ulike_Query_Cache::TTL_FINGERPRINT
		);
	}
}