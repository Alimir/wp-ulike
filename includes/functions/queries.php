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
		$parsed_args  = wp_parse_args( $args, $defaults );
		$info_args    = wp_ulike_get_table_info( $parsed_args['type'] );
		$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		// Check object cache value
		$cache_key = sanitize_key( sprintf( 'items_%s', md5( serialize( $parsed_args ) ) ) );
		$results   = wp_cache_get( $cache_key, WP_ULIKE_SLUG );
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
				if( is_array( $parsed_args['rel_type'] ) ){
					$rel_types = array_map(function($rel_type) use ($wpdb) {
						return $wpdb->prepare('%s', $rel_type);
					}, $parsed_args['rel_type']);

					$post_type = " AND r.post_type IN (" . implode(',', $rel_types) . ")";
				} elseif( ! empty( $parsed_args['rel_type'] ) ) {
					$post_type = $wpdb->prepare( " AND r.post_type = %s", $parsed_args['rel_type'] );
				}

				$related_condition = "AND r.post_status IN ('publish', 'inherit', 'private')" . $post_type;
				break;
		}

		$user_condition = '';
		if( !empty( $parsed_args['user_id'] ) ){
			if( is_array( $parsed_args['user_id'] ) ){
				$user_ids = array_map(function($user_id) use ($wpdb) {
					return $wpdb->prepare('%s', $user_id);
				}, $parsed_args['user_id']);

				$user_condition = " AND t.user_id IN (" . implode(',', $user_ids) . ")";
			} else {
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
			// create query condition from status
			if( is_array( $parsed_args['status'] ) ){
				$status_values = array_map(function($status) use ($wpdb) {
					return $wpdb->prepare('%s', $status);
				}, $parsed_args['status']);

				$status_type = "t.status IN (" . implode(',', $status_values) . ")";
			} else {
				$status_type = $wpdb->prepare( "t.status = %s", $parsed_args['status'] );
			}

			// CRITICAL FIX: Escape all table/column names for security and performance
			$table_name = esc_sql( $wpdb->prefix . $info_args['table'] );
			$column_escaped = esc_sql( $info_args['column'] );
			$related_table_escaped = esc_sql( $info_args['related_table_prefix'] );
			$related_column_escaped = esc_sql( $info_args['related_column'] );
			$order_by_escaped = esc_sql( $order_by );
			$order_escaped = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			// generate query string - all identifiers properly escaped
			$query  = "
				SELECT COUNT(t.`{$column_escaped}`) AS counter,
				t.`{$column_escaped}` AS item_ID
				FROM `{$table_name}` t
				INNER JOIN `{$related_table_escaped}` r ON t.`{$column_escaped}` = r.`{$related_column_escaped}` {$related_condition}
				WHERE {$status_type} {$user_condition} {$period_limit}
				GROUP BY item_ID
				ORDER BY `{$order_by_escaped}` {$order_escaped} {$limit_records}";

		}

		$results = !empty( $query ) ? $wpdb->get_results( $query ): null;

		if( ! empty( $results ) ){
			wp_cache_add( $cache_key, $results, WP_ULIKE_SLUG, 300 );
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
		$parsed_args  = wp_parse_args( $args, $defaults );
		$info_args    = wp_ulike_get_table_info( $parsed_args['type'] );
		$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		$related_condition = '';
		switch ($parsed_args['type']) {
			case 'post':
			case 'topic':
				$post_type = '';
				if( is_array( $parsed_args['rel_type'] ) ){
					$rel_types = array_map(function($rel_type) use ($wpdb) {
						return $wpdb->prepare('%s', $rel_type);
					}, $parsed_args['rel_type']);

					$post_type = " AND r.post_type IN (" . implode(',', $rel_types) . ")";
				} elseif( ! empty( $parsed_args['rel_type'] ) ) {
					$post_type = $wpdb->prepare( " AND r.post_type = %s", $parsed_args['rel_type'] );
				}

				$related_condition = "AND r.post_status IN ('publish', 'inherit', 'private')" . $post_type;
				break;
		}


		$user_condition = '';
		if( !empty( $parsed_args['user_id'] ) ){
			if( is_array( $parsed_args['user_id'] ) ){
				$user_ids = array_map(function($user_id) use ($wpdb) {
					return $wpdb->prepare('%s', $user_id);
				}, $parsed_args['user_id']);

				$user_condition = " AND t.user_id IN (" . implode(',', $user_ids) . ")";
			} else {
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
			// create query condition from status
			$status_type  = '';
			if( is_array( $parsed_args['status'] ) ){
				$status_values = array_map(function($status) use ($wpdb) {
					return $wpdb->prepare('%s', $status);
				}, $parsed_args['status']);

				$status_type = "t.status IN (" . implode(',', $status_values) . ")";
			} else {
				$status_type = $wpdb->prepare( "t.status = %s", $parsed_args['status'] );
			}

			// generate query string
			$column = esc_sql( $info_args['column'] );
			$table_name = $wpdb->prefix . esc_sql( $info_args['table'] );
			$related_table = esc_sql( $info_args['related_table_prefix'] );
			$related_column = esc_sql( $info_args['related_column'] );

			$query  = "
				SELECT COUNT(DISTINCT t.`{$column}`)
				FROM `{$table_name}` t
				INNER JOIN `{$related_table}` r ON t.`{$column}` = r.`{$related_column}` {$related_condition}
				WHERE {$status_type} {$user_condition}
				{$period_limit}";
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
			$cache_key = sanitize_key( sprintf( '%s_%s_%d_likers_list', $table_name, $column_name, $item_ID ) );
			$get_likers = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

			if( false === $get_likers ){
				// Calculate max cache size based on limit parameter
				// Formula: max(limit * 10, 100, 1000) - ensures reasonable cache size
				$base_limit = is_null( $limit ) ? 100 : $limit;
				$max_likers = min( max( $base_limit * 10, 100 ), 1000 );

				$table_escaped = esc_sql( $wpdb->prefix . $table_name );
				$column_escaped = esc_sql( $column_name );

				// Get distinct user IDs - JOIN ensures only valid users (not guests) are included
				$user_ids = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT t.`user_id`
					FROM `{$table_escaped}` t
					INNER JOIN {$wpdb->users} u ON u.ID = t.`user_id`
					WHERE t.`status` IN ('like', 'dislike')
					AND t.`{$column_escaped}` = %d
					LIMIT %d",
					$item_ID,
					$max_likers
				) );

				// Convert to comma-separated string for caching
				$get_likers = ! empty( $user_ids ) ? implode( ',', $user_ids ) : '';

				// Cache for 5 minutes
				wp_cache_set( $cache_key, $get_likers, WP_ULIKE_SLUG, 300 );
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
		global $wpdb;
		// Get ULike settings
		$get_settings = wp_ulike_get_post_settings_by_type( $type );
		$table_name = $wpdb->prefix . $get_settings['table'];

		$query  =  $wpdb->prepare( "
			SELECT COUNT(*)
			FROM `{$table_name}`
			WHERE `{$get_settings['column']}` = %s
			AND `status` = 'like'
			And `user_id` = %d",
			$item_ID,
			$user_ID
		);

		return $wpdb->get_var( $query );
	}
}

if( ! function_exists( 'wp_ulike_get_user_item_history' ) ) {
	/**
	 * A simple function to get user activity history
	 *
	 * @param array $args
	 * @return array
	 */
	function wp_ulike_get_user_item_history( $args ) {
		global $wpdb;

		$defaults = array(
			"item_id"           => '',
			"item_type"         => '',
			"current_user"      => '',
			"settings"          => '',
			"is_user_logged_in" => ''
		);
		$parsed_args = wp_parse_args( $args, $defaults );
		// Meta key name
		$meta_key  = sanitize_key( $parsed_args['item_type'] . '_status' );
		// Get meta data
		$user_info = wp_ulike_get_meta_data( $parsed_args['current_user'], 'user', $meta_key, true );

		if( empty($user_info) || ! isset( $user_info[$parsed_args['item_id']] ) ){
			$table_name  = $wpdb->prefix . $parsed_args['settings']->getTableName();
			$column_name = $parsed_args['settings']->getColumnName();

			$query  = $wpdb->prepare( "
					SELECT `status`
					FROM `{$table_name}`
					WHERE `{$column_name}` = %s
					AND `user_id` = %d
					ORDER BY id DESC LIMIT 1
				",
				$parsed_args['item_id'],
				$parsed_args['current_user']
			);

			// Get results
			$user_status = $wpdb->get_var( $query );

			// Check user info value
			$user_info = empty( $user_info ) ? array() : $user_info;

			if( ! empty( $user_status ) ){
				$user_info[$parsed_args['item_id']] = $user_status;
				wp_ulike_update_meta_data( $parsed_args['current_user'], 'user', $meta_key, $user_info );
			}
		}

		return $user_info;
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
		global $wpdb;

		$settings    = wp_ulike_setting_type::get_instance( $type );
		$table_name  = $wpdb->prefix . $settings->getTableName();
		$column_name = $settings->getColumnName();

		$query  = $wpdb->prepare( "
				SELECT `date_time`, `status`
				FROM `{$table_name}`
				WHERE `{$column_name}` = %s
				AND `user_id` = %d
				ORDER BY id DESC LIMIT 1
			",
			$item_id,
			$user_id
		);

		$result = $wpdb->get_row( $query, ARRAY_A );

		if( ! empty( $result['date_time'] ) ){
			$result['date_time'] = wp_ulike_date_i18n( $result['date_time'] );
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
		global $wpdb;

		$defaults = array(
			"item_id"      => '',
			"current_user" => '',
			"settings"     => ''
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$table_name  = $wpdb->prefix . $parsed_args['settings']->getTableName();
		$column_name = $parsed_args['settings']->getColumnName();

		// Use index-friendly date range query instead of DATE() function
		// Pre-calculate today's date range for maximum index optimization
		$today = current_time( 'Y-m-d' );
		$today_start = $today . ' 00:00:00';
		$today_end = $today . ' 23:59:59';

		$query = $wpdb->prepare( "
			SELECT COUNT(*)
			FROM `$table_name`
			WHERE `$column_name` = %s
			AND `user_id` = %d
			AND date_time >= %s
			AND date_time <= %s",
			$parsed_args['item_id'],
			$parsed_args['current_user'],
			$today_start,
			$today_end
		);

		$count_votes = $wpdb->get_var( $query );

		return $count_votes ? $count_votes : 0;
	}
}

if( ! function_exists('wp_ulike_get_best_likers_info') ){
    /**
     * Get most liked users in query
     *
     * @param integer $limit
     * @param string $period
     * @param integer $offset
     * @return object
     */
	function wp_ulike_get_best_likers_info($limit, $period, $offset = 1, $status = ['like', 'dislike']) {
		global $wpdb;

		// Period limit SQL
		$period_limit = wp_ulike_get_period_limit_sql($period);

		// Limit clause
		$limit_records = '';
		if ((int)$limit > 0) {
			$offset = $offset > 0 ? (($offset - 1) * $limit) : 0;
			$limit_records = $wpdb->prepare("LIMIT %d, %d", $offset, $limit);
		}

		// Prepare status filter
		if (is_array($status)) {
			$status_values = array_map(function($status) use ($wpdb) {
				return $wpdb->prepare('%s', $status);
			}, $status);

			$status_type = "status IN (" . implode(',', $status_values) . ")";
		} else {
			$status_type = $wpdb->prepare("status = %s", $status);
		}

		// Dynamic SUM(CASE WHEN ...) clauses
		$dynamic_sums = [];
		$allowed_statuses = array( 'like', 'dislike', 'unlike', 'undislike' );
		foreach ($status as $stat) {
			// Validate status against allowed values
			if ( ! in_array( $stat, $allowed_statuses, true ) ) {
				continue;
			}
			$stat_escaped = esc_sql( $stat );
			$dynamic_sums[] = "SUM(CASE WHEN T.status = '{$stat_escaped}' THEN T.CountUser ELSE 0 END) AS `{$stat_escaped}Count`";
		}
		$dynamic_sums_sql = implode(', ', $dynamic_sums);

		// CRITICAL OPTIMIZATION: UNION ALL with millions of rows can be slow
		// Optimize by ensuring indexes are used and limiting subquery results where possible
		// The indexes on (user_id, status, date_time) should help, but we can optimize further

		// SQL Query - Optimized for large datasets
		// Note: Each subquery uses indexes on (user_id, status) and (user_id, status, date_time)
		$query = "
			SELECT
				T.user_id,
				{$dynamic_sums_sql},
				SUM(T.CountUser) AS SumUser
			FROM (
				SELECT user_id, status, COUNT(*) AS CountUser
				FROM `{$wpdb->prefix}ulike`
				INNER JOIN {$wpdb->users}
				ON {$wpdb->users}.ID = `{$wpdb->prefix}ulike`.user_id
				WHERE {$status_type}
				{$period_limit}
				GROUP BY user_id, status
				UNION ALL
				SELECT user_id, status, COUNT(*) AS CountUser
				FROM `{$wpdb->prefix}ulike_activities`
				INNER JOIN {$wpdb->users}
				ON {$wpdb->users}.ID = `{$wpdb->prefix}ulike_activities`.user_id
				WHERE {$status_type}
				{$period_limit}
				GROUP BY user_id, status
				UNION ALL
				SELECT user_id, status, COUNT(*) AS CountUser
				FROM `{$wpdb->prefix}ulike_comments`
				INNER JOIN {$wpdb->users}
				ON {$wpdb->users}.ID = `{$wpdb->prefix}ulike_comments`.user_id
				WHERE {$status_type}
				{$period_limit}
				GROUP BY user_id, status
				UNION ALL
				SELECT user_id, status, COUNT(*) AS CountUser
				FROM `{$wpdb->prefix}ulike_forums`
				INNER JOIN {$wpdb->users}
				ON {$wpdb->users}.ID = `{$wpdb->prefix}ulike_forums`.user_id
				WHERE {$status_type}
				{$period_limit}
				GROUP BY user_id, status
			) AS T
			GROUP BY T.user_id
			ORDER BY SumUser DESC
			{$limit_records}";

		// Execute query and return results
		return $wpdb->get_results($query);
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
        global $wpdb;
        // Period limit SQL
        $period_limit = wp_ulike_get_period_limit_sql( $period );

		$status_type = '';
		if( is_array( $status ) ){
			$status_values = array_map(function($status) use ($wpdb) {
				return $wpdb->prepare('%s', $status);
			}, $status);

			$status_type = "status IN (" . implode(',', $status_values) . ")";
		} else {
			$status_type = $wpdb->prepare( "status = %s", $status );
		}

		$query = "
        SELECT COUNT(DISTINCT user_id) AS total_users
        FROM (
            SELECT user_id
            FROM `{$wpdb->prefix}ulike`
            INNER JOIN {$wpdb->users}
			ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike.user_id )
            WHERE {$status_type}
            {$period_limit}
            GROUP BY user_id
            UNION
            SELECT user_id
            FROM `{$wpdb->prefix}ulike_activities`
            INNER JOIN {$wpdb->users}
			ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike_activities.user_id )
            WHERE {$status_type}
            {$period_limit}
            GROUP BY user_id
            UNION
            SELECT user_id
            FROM `{$wpdb->prefix}ulike_comments`
            INNER JOIN {$wpdb->users}
			ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike_comments.user_id )
            WHERE {$status_type}
            {$period_limit}
            GROUP BY user_id
            UNION
            SELECT user_id
            FROM `{$wpdb->prefix}ulike_forums`
            INNER JOIN {$wpdb->users}
			ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike_forums.user_id )
            WHERE {$status_type}
            {$period_limit}
            GROUP BY user_id
        ) AS combined_users";


		// Get the total count of distinct users
		$result = $wpdb->get_var($query);

		return (int) $result;
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
		global $wpdb;

		$defaults = array(
			'type'     => 'post',
			'period'   => 'all',
			'order'    => 'DESC',
			'status'   => 'like',
			'page'     => 1,
			'per_page' => 10
		);
		$parsed_args  = wp_parse_args( $args, $defaults );
		$parsed_args  = array_merge( wp_ulike_get_table_info( $parsed_args['type'] ), $parsed_args );
		$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		$status_type  = '';
		if( is_array( $parsed_args['status'] ) ){
			$status_values = array_map(function($status) use ($wpdb) {
				return $wpdb->prepare('%s', $status);
			}, $parsed_args['status']);

			$status_type = "`status` IN (" . implode(',', $status_values) . ")";
		} else {
			$status_type = $wpdb->prepare( "`status` = %s", $parsed_args['status'] );
		}

		$table_name = $wpdb->prefix . $parsed_args['table'];

		// generate query string
		$query  = $wpdb->prepare( "
			SELECT `{$parsed_args['column']}` AS itemID, max(`date_time`) AS datetime, max(`status`) AS lastStatus
			FROM `{$table_name}`
			WHERE `user_id` = %d
			AND {$status_type} {$period_limit}
			GROUP BY itemID
			ORDER BY datetime
			{$parsed_args['order']} LIMIT %d, %d",
			$user_ID,
			( $parsed_args['page'] - 1 ) * $parsed_args['per_page'],
			$parsed_args['per_page']
		);

		return $wpdb->get_results(  $query );
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
		global $wpdb;

		$defaults = array(
			'type'     => 'post',
			'period'   => 'all',
			'order'    => 'DESC',
			'status'   => 'like',
			'page'     => 1,
			'per_page' => 10
		);
		$parsed_args  = wp_parse_args( $args, $defaults );
		$parsed_args  = array_merge( wp_ulike_get_table_info( $parsed_args['type'] ), $parsed_args );
		$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		// Build status condition
		$status_type = '';
		if( is_array( $parsed_args['status'] ) ){
			$status_values = array_map(function($status) use ($wpdb) {
				return $wpdb->prepare('%s', $status);
			}, $parsed_args['status']);
			$status_type = "`status` IN (" . implode(',', $status_values) . ")";
		} else {
			$status_type = $wpdb->prepare( "`status` = %s", $parsed_args['status'] );
		}

		// Escape dynamic table/column names (user input)
		$table_name = esc_sql( $wpdb->prefix . $parsed_args['table'] );
		$column_name = esc_sql( $parsed_args['column'] );
		$order_escaped = strtoupper( $parsed_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Limit GROUP_CONCAT to prevent truncation and memory issues with large datasets
		$group_concat_limit = 500;

		// Calculate pagination
		$offset = ( $parsed_args['page'] - 1 ) * $parsed_args['per_page'];
		$limit = absint( $parsed_args['per_page'] );

		$query = "
			SELECT t.user_id AS userID,
			       COUNT(t.user_id) AS score,
			       MAX(t.date_time) AS datetime,
			       MAX(t.status) AS lastStatus,
			       SUBSTRING_INDEX(
			           GROUP_CONCAT(DISTINCT t.`{$column_name}` ORDER BY t.`{$column_name}` DESC SEPARATOR ','),
			           ',',
			           {$group_concat_limit}
			       ) AS itemsList
			FROM `{$table_name}` t
			INNER JOIN {$wpdb->users} u ON u.ID = t.user_id
			WHERE {$status_type} {$period_limit}
			GROUP BY t.user_id
			ORDER BY score {$order_escaped}
			LIMIT %d, %d";

		return $wpdb->get_results( $wpdb->prepare( $query, $offset, $limit ) );
	}
}

/*******************************************************
  General
*******************************************************/

if( ! function_exists( 'wp_ulike_get_rating_value' ) ){
	/**
	 * Calculate rating value by user logs & date_time
	 *
	 * @author       	Alimir
	 * @param           Integer $post_ID
	 * @param           Boolean $is_decimal
	 * @since           2.7
	 * @return          String
	 */
	function wp_ulike_get_rating_value($post_ID, $is_decimal = true){
		global $wpdb;
		$cache_key = 'get_rich_rating_value_' . $post_ID;
		$cache_group = 'wp_ulike';

		if (false === ($rating_value = wp_cache_get($cache_key, $cache_group))) {
			// CRITICAL OPTIMIZATION: The original query calculated AVG of ALL posts on every call
			// This was extremely inefficient. We now:
			// 1. Get the current post's like count directly
			// 2. Use a cached/transient value for global average (calculated less frequently)
			// 3. Only calculate global average if cache is empty

			$table_escaped = esc_sql( $wpdb->prefix . 'ulike' );
			$post_id_escaped = absint( $post_ID );

			// Get current post's like count (fast, indexed query)
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table_escaped}` WHERE post_id = %d",
				$post_id_escaped
			) );

			// Get global average from cache/transient (calculated once, reused many times)
			$avg = get_transient( 'wp_ulike_global_avg_likes' );
			if ( false === $avg ) {
				// Calculate global average only when cache is empty (expensive operation)
				// This query is expensive but only runs when transient expires
				$avg = $wpdb->get_var( "
					SELECT AVG(post_count)
					FROM (
						SELECT COUNT(*) AS post_count
						FROM `{$table_escaped}`
						GROUP BY post_id
					) AS counted
				" );
				// Cache for 1 hour (global average doesn't change frequently)
				set_transient( 'wp_ulike_global_avg_likes', $avg, HOUR_IN_SECONDS );
			}

			// Get post date (cached by WordPress)
			$post_date = get_post_field( 'post_date', $post_ID );
			$date = $post_date ? strtotime( $post_date ) : 0;

			// if there is no log data, set $rating_value = 5
			if( $count == 0 || $avg == 0 ){
				$rating_value = 5;
			} else {
				$decimal = 0;
				if( $is_decimal ){
					list( $whole, $decimal ) = explode( '.', number_format( ( $count*100 / ( $avg * 2 ) ), 1 ) );
					$decimal = (int)$decimal;
				}
				if( $date > strtotime('-1 month')) {
					if($count < $avg) $rating_value = 4 + ".$decimal";
					else $rating_value = 5;
				} else if(($date <= strtotime('-1 month')) && ($date > strtotime('-6 month'))) {
					if($count < $avg) $rating_value = 3 + ".$decimal";
					else if(($count >= $avg) && ($count < ($avg*3/2))) $rating_value = 4 + ".$decimal";
					else $rating_value = 5;
				} else {
					if($count < ($avg/2)) $rating_value = 1 + ".$decimal";
					else if(($count >= ($avg/2)) && ($count < $avg)) $rating_value = 2 + ".$decimal";
					else if(($count >= $avg) && ($count < ($avg*3/2))) $rating_value = 3 + ".$decimal";
					else if(($count >= ($avg*3/2)) && ($count < ($avg*2))) $rating_value = 4 + ".$decimal";
					else $rating_value = 5;
				}
			}

			wp_cache_add($cache_key, $rating_value, $cache_group, HOUR_IN_SECONDS);
		}

		return apply_filters( 'wp_ulike_rating_value', $rating_value, $post_ID );
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
        global $wpdb;

        // Convert array period
        if( is_array( $period ) ){
            $period = implode( '-', $period );
        }

        $cache_key = sanitize_key( sprintf( 'count_logs_period_%s', $period ) );

        if( $period === 'all' ){
            $count_all_logs = wp_ulike_get_meta_data( 1, 'statistics', 'count_logs_period_all', true );
            if( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ){
                return absint($count_all_logs);
            }
        }

        $counter_value = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

        // Make a cachable query to get new like count from all tables
        if( false === $counter_value ){
			$period_limit = wp_ulike_get_period_limit_sql( $period );

            $counter_value = $wpdb->get_var( "
			SELECT
			( SELECT COUNT(*) FROM `{$wpdb->prefix}ulike` WHERE 1=1 {$period_limit} ) +
			( SELECT COUNT(*) FROM `{$wpdb->prefix}ulike_activities` WHERE 1=1 {$period_limit} ) +
			( SELECT COUNT(*) FROM `{$wpdb->prefix}ulike_comments` WHERE 1=1 {$period_limit} ) +
			( SELECT COUNT(*) FROM `{$wpdb->prefix}ulike_forums` WHERE 1=1 {$period_limit} )
			" );

            wp_cache_add( $cache_key, $counter_value, WP_ULIKE_SLUG, 300 );
        }

        if( $period === 'all' ){
            wp_ulike_update_meta_data( 1, 'statistics', 'count_logs_period_all', $counter_value );
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
		global $wpdb;
		// Sanitize key & prepare cache key
		$cache_key = 'fingerprint_' . md5( $type . '_' . $item_id . '_' . $current_fingerprint );

		// Try to get from cache
		$existing_count = wp_cache_get( $cache_key, WP_ULIKE_SLUG );
		$settings       = wp_ulike_setting_type::get_instance( $type );

		if ( false === $existing_count ) {
			$table = $wpdb->prefix . $settings->getTableName();

			$existing_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$settings->getColumnName()} = %d AND fingerprint = %s",
				$item_id,
				$current_fingerprint
			) );

			// Store in cache to avoid repeated queries for same request
			wp_cache_add( $cache_key, $existing_count, WP_ULIKE_SLUG, 10 ); // TTL = 10 seconds
		}

		return (int) $existing_count;
	}
}