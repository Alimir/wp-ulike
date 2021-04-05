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
			$limit_records = sprintf( "LIMIT %d, %d", $offset, $parsed_args['limit'] );
		}

		$related_condition = '';
		switch ($parsed_args['type']) {
			case 'post':
			case 'topic':
				$post_type = '';
				if( is_array( $parsed_args['rel_type'] ) ){
					$post_type = sprintf( " AND r.post_type IN ('%s')", implode ("','", $parsed_args['rel_type'] ) );
				} elseif( ! empty( $parsed_args['rel_type'] ) ) {
					$post_type = sprintf( " AND r.post_type = '%s'", $parsed_args['rel_type'] );
				}
				$related_condition = 'AND r.post_status IN (\'publish\', \'inherit\', \'private\')' . $post_type;
				break;
		}

		$user_condition = '';
		if( !empty( $parsed_args['user_id'] ) ){
			if( is_array( $parsed_args['user_id'] ) ){
				$user_condition = sprintf( " AND t.user_id IN ('%s')", implode ("','", $parsed_args['user_id'] ) );
			} else {
				$user_condition = sprintf( " AND t.user_id = '%s'", $parsed_args['user_id'] );
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
				foreach ($parsed_args['status'] as $key => $value) {
					$status_type .= $key === 0 ? sprintf( "t.meta_key LIKE '%s'", $meta_prefix . $value ) : sprintf( " OR t.meta_key LIKE '%s'", $meta_prefix . $value );
				}
				$status_type = sprintf( " AND (%s)",  $status_type );
			} else {
				$status_type = sprintf( " AND t.meta_key LIKE '%s'",  $meta_prefix . $parsed_args['status'] );
			}

			// generate query string
			$query  = sprintf( '
				SELECT t.item_id AS item_ID, MAX(CAST(t.meta_value AS UNSIGNED)) as counter
				FROM %1$s t
				INNER JOIN %2$s r ON t.item_id = r.%3$s %4$s
				WHERE t.meta_group = "%5$s" AND t.meta_value > 0 %6$s
				GROUP BY item_ID
				ORDER BY %7$s
				%8$s %9$s',
				$wpdb->prefix . 'ulike_meta',
				$wpdb->prefix . $info_args['related_table'],
				$info_args['related_column'],
				$related_condition,
				$parsed_args['type'],
				$status_type,
				$parsed_args['is_popular'] ? 'counter' : 'item_ID',
				$parsed_args['order'],
				$limit_records
			);

		} else {
			// create query condition from status
			if( is_array( $parsed_args['status'] ) ){
				$status_type = sprintf( "t.status IN ('%s')", implode ("','", $parsed_args['status'] ) );
			} else {
				$status_type = sprintf( "t.status = '%s'", $parsed_args['status'] );
			}

			// generate query string
			$query  = sprintf( '
				SELECT COUNT(t.%1$s) AS counter,
				t.%1$s AS item_ID
				FROM %2$s t
				INNER JOIN %3$s r ON t.%1$s = r.%4$s
				WHERE %5$s %6$s
				%7$s
				GROUP BY item_ID
				ORDER BY %8$s
				%9$s %10$s',
				$info_args['column'],
				$wpdb->prefix . $info_args['table'],
				$wpdb->prefix . $info_args['related_table'],
				$info_args['related_column'],
				$status_type,
				$user_condition,
				$period_limit,
				$parsed_args['is_popular'] ? 'counter' : 'item_ID',
				$parsed_args['order'],
				$limit_records
			);

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
					$post_type = sprintf( " AND r.post_type IN ('%s')", implode ("','", $parsed_args['rel_type'] ) );
				} elseif( ! empty( $parsed_args['rel_type'] ) ) {
					$post_type = sprintf( " AND r.post_type = '%s'", $parsed_args['rel_type'] );
				}
				$related_condition = 'AND r.post_status IN (\'publish\', \'inherit\', \'private\')' . $post_type;
				break;
		}


		$user_condition = '';
		if( !empty( $parsed_args['user_id'] ) ){
			if( is_array( $parsed_args['user_id'] ) ){
				$user_condition = sprintf( " AND t.user_id IN ('%s')", implode ("','", $parsed_args['user_id'] ) );
			} else {
				$user_condition = sprintf( " AND t.user_id = '%s'", $parsed_args['user_id'] );
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
				foreach ($parsed_args['status'] as $key => $value) {
					$status_type .= $key === 0 ? sprintf( "t.meta_key LIKE '%s'", $meta_prefix . $value ) : sprintf( " OR t.meta_key LIKE '%s'", $meta_prefix . $value );
				}
				$status_type = sprintf( " AND (%s)",  $status_type );
			} else {
				$status_type = sprintf( " AND t.meta_key LIKE '%s'",  $meta_prefix . $parsed_args['status'] );
			}
			// generate query string
			$query  = sprintf( '
				SELECT COUNT(DISTINCT t.item_id)
				FROM %1$s t
				INNER JOIN %2$s r ON t.item_id = r.%3$s %4$s
				WHERE t.meta_value > 0 AND t.meta_group = "%5$s" %6$s',
				$wpdb->prefix . 'ulike_meta',
				$wpdb->prefix . $info_args['related_table'],
				$info_args['related_column'],
				$related_condition,
				$parsed_args['type'],
				$status_type
			);

		} else {
			// create query condition from status
			$status_type  = '';
			if( is_array( $parsed_args['status'] ) ){
				$status_type = sprintf( "t.status IN ('%s')", implode ("','", $parsed_args['status'] ) );
			} else {
				$status_type = sprintf( "t.status = '%s'", $parsed_args['status'] );
			}

			// generate query string
			$query  = sprintf( '
				SELECT COUNT(DISTINCT t.%1$s)
				FROM %2$s t
				INNER JOIN %3$s r ON t.%1$s = r.%4$s %5$s
				WHERE %6$s %7$s
				%8$s',
				$info_args['column'],
				$wpdb->prefix . $info_args['table'],
				$wpdb->prefix . $info_args['related_table'],
				$info_args['related_column'],
				$related_condition,
				$status_type,
				$user_condition,
				$period_limit
			);
		}

		return !empty( $query ) ? $wpdb->get_var( $query ): null;
	}
}

/*******************************************************
  User Data
*******************************************************/

if( ! function_exists( 'wp_ulike_get_likers_list_per_post' ) ){
	/**
	 * Get likers list
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @param integer $item_ID
	 * @param integer $limit
	 * @return array
	 */
	function wp_ulike_get_likers_list_per_post( $table_name, $column_name, $item_ID, $limit = 10 ){
		// Global wordpress database object
		global $wpdb;

		$item_type  = wp_ulike_get_type_by_table( $table_name );
		$item_opts  = wp_ulike_get_post_settings_by_type( $item_type );
		$get_likers = wp_ulike_get_meta_data( $item_ID, $item_type, 'likers_list', true );

		if( empty( $get_likers ) ){
			// Cache data
			$cache_key  = sanitize_key( sprintf( '%s_%s_%s_likers_list', $table_name, $column_name, $item_ID ) );
			$get_likers = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

			if( false === $get_likers ){
				// Get results
				$get_likers = $wpdb->get_var( "
					SELECT GROUP_CONCAT(DISTINCT(`user_id`) SEPARATOR ',')
					FROM {$wpdb->prefix}{$table_name}
					INNER JOIN {$wpdb->users}
					ON ( {$wpdb->users}.ID = {$wpdb->prefix}{$table_name}.user_id )
					WHERE {$wpdb->prefix}{$table_name}.status IN ('like', 'dislike')
					AND {$column_name} = {$item_ID}"
				);

				wp_cache_set( $cache_key, $get_likers, WP_ULIKE_SLUG, 300 );
			}

			if( ! empty( $get_likers) ){
				$get_likers = explode( ',', $get_likers );
				wp_ulike_update_meta_data( $item_ID, $item_type, 'likers_list', $get_likers );
			}
		}

		// Change array arrange
		if( ! empty( $get_likers ) && !empty( $item_opts['setting'] ) && wp_ulike_get_option( $item_opts['setting'] . '|likers_order', 'desc' ) === 'desc' ){
			$get_likers = array_reverse( $get_likers );
		}

		$output = ! empty( $get_likers ) ? array_slice( $get_likers, 0, $limit ) : array();

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

		$query  = sprintf( "
			SELECT COUNT(*)
			FROM %s
			WHERE `%s` = %s
			AND `status` = 'like'
			And `user_id` = %s",
			esc_sql( $wpdb->prefix . $get_settings['table'] ),
			esc_html( $get_settings['column'] ),
			esc_html( $item_ID ),
			esc_html( $user_ID )
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

		if( empty( $user_info ) || ! isset( $user_info[$parsed_args['item_id']] ) ){
			$query  = sprintf( '
					SELECT `status`
					FROM %s
					WHERE `%s` = \'%s\'
					AND `user_id` = \'%s\'
					ORDER BY id DESC LIMIT 1
				',
				esc_sql( $wpdb->prefix . $parsed_args['settings']->getTableName() ),
				esc_sql( $parsed_args['settings']->getColumnName() ),
				esc_sql( $parsed_args['item_id'] ),
				esc_sql( $parsed_args['current_user'] )
			);

			// Get results
			$user_status = $wpdb->get_var( stripslashes( $query ) );

			// Check user info value
			$user_info = empty( $user_info ) ? array() : $user_info;

			if( $user_status !== NULL || $parsed_args['is_user_logged_in'] ){
				$user_info[$parsed_args['item_id']] =  $parsed_args['is_user_logged_in'] && $user_status === NULL ? NULL : $user_status;
				wp_ulike_update_meta_data( $parsed_args['current_user'], 'user', $meta_key, $user_info );
			}
		}

		return $user_info;
	}
}

if( ! function_exists('wp_ulike_get_best_likers_info') ){
	/**
	 * Get most liked users in query
	 *
	 * @param integer $limit
	 * @param string $peroid
	 * @param integer $offset
	 * @return object
	 */
	function wp_ulike_get_best_likers_info( $limit, $peroid, $offset = 1 ){
		global $wpdb;
		// Peroid limit SQL
		$period_limit = wp_ulike_get_period_limit_sql( $peroid );

		$limit_records = '';
		if( (int) $limit > 0 ){
			$offset = $offset > 0 ? ( $offset - 1 ) * $limit : 0;
			$limit_records = sprintf( "LIMIT %d, %d", $offset, $limit );
		}

		$query  = sprintf( 'SELECT T.user_id, SUM(T.CountUser) AS SumUser
		FROM(
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike`
		INNER JOIN %4$s
		ON ( %4$s.ID = %1$sulike.user_id )
		WHERE status IN (\'like\', \'dislike\')
		%2$s
		GROUP BY user_id
		UNION ALL
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike_activities`
		INNER JOIN %4$s
		ON ( %4$s.ID = %1$sulike_activities.user_id )
		WHERE status IN (\'like\', \'dislike\')
		%2$s
		GROUP BY user_id
		UNION ALL
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike_comments`
		INNER JOIN %4$s
		ON ( %4$s.ID = %1$sulike_comments.user_id )
		WHERE status IN (\'like\', \'dislike\')
		%2$s
		GROUP BY user_id
		UNION ALL
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike_forums`
		INNER JOIN %4$s
		ON ( %4$s.ID = %1$sulike_forums.user_id )
		WHERE status IN (\'like\', \'dislike\')
		%2$s
		GROUP BY user_id
		) AS T
		GROUP BY T.user_id
		ORDER BY SumUser DESC %3$s', $wpdb->prefix, $period_limit, $limit_records, $wpdb->users );

		// Make new sql request
		return $wpdb->get_results( $query );
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
			$status_type = sprintf( "`status` IN ('%s')", implode ("','", $parsed_args['status'] ) );
		} else {
			$status_type = sprintf( "`status` = '%s'", $parsed_args['status'] );
		}

		// generate query string
		$query  = sprintf( "
			SELECT `%s` AS itemID, max(`date_time`) AS datetime, max(`status`) AS lastStatus
			FROM %s
			WHERE `user_id` = '%s'
			AND %s %s
			GROUP BY itemID
			ORDER BY datetime
			%s LIMIT %s, %s",
			$parsed_args['column'],
			$wpdb->prefix . $parsed_args['table'],
			$user_ID,
			$status_type,
			$period_limit,
			$parsed_args['order'],
			( $parsed_args['page'] - 1 ) * $parsed_args['per_page'],
			$parsed_args['per_page']
		);

		return $wpdb->get_results( $query );
	}

}

if( ! function_exists( 'wp_ulike_get_users' ) ){
	/**
	 * Retrieve list of users
	 *
	 * @param array $args
	 * @return object|null
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

		$status_type  = '';
		if( is_array( $parsed_args['status'] ) ){
			$status_type = sprintf( "`status` IN ('%s')", implode ("','", $parsed_args['status'] ) );
		} else {
			$status_type = sprintf( "`status` = '%s'", $parsed_args['status'] );
		}

		// generate query string
		$query  = sprintf( '
			SELECT %1$s.user_id AS userID, count(%1$s.user_id) AS score,
			max(%1$s.date_time) AS datetime, max(%1$s.status) AS lastStatus,
			GROUP_CONCAT(DISTINCT(%1$s.%3$s) SEPARATOR ",") AS itemsList
			FROM %1$s
			INNER JOIN %2$s
			ON ( %2$s.ID = %1$s.user_id )
			WHERE %4$s %5$s
			GROUP BY user_id
			ORDER BY score
			%6$s LIMIT %7$s, %8$s',
			$wpdb->prefix . $parsed_args['table'],
			$wpdb->users,
			$parsed_args['column'],
			$status_type,
			$period_limit,
			$parsed_args['order'],
			( $parsed_args['page'] - 1 ) * $parsed_args['per_page'],
			$parsed_args['per_page']
		);

		return $wpdb->get_results( $query );
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
		if (false === ($rating_value = wp_cache_get($cache_key = 'get_rich_rating_value_' . $post_ID, $cache_group = 'wp_ulike'))) {
			// get the average, likes count & date_time columns by $post_ID
			$request =  "SELECT
							FORMAT(
								(
								SELECT
									AVG(counted.total)
								FROM
									(
									SELECT
										COUNT(*) AS total
									FROM
										".$wpdb->prefix."ulike AS ulike
									GROUP BY
										ulike.post_id
								) AS counted
							),
							0
							) AS average,
							COUNT(ulike.post_id) AS counter,
							posts.post_date AS post_date
						FROM
							".$wpdb->prefix."ulike AS ulike
						JOIN
							".$wpdb->prefix."posts AS posts
						ON
							ulike.post_id = ".$post_ID." AND posts.ID = ulike.post_id;";
			//get columns in a row
			$likes 	= $wpdb->get_row($request);
			$avg 	= $likes->average;
			$count 	= $likes->counter;
			$date 	= strtotime($likes->post_date);

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
	 * @param string $period		Availabe values: all, today, yesterday
	 * @return integer
	 */
	function wp_ulike_count_all_logs( $period = 'all' ){
		global $wpdb;

		// Convert array period
		if( is_array( $period ) ){
			$period = implode( '-', $period );
		}

		$cache_key      = sanitize_key( sprintf( 'count_logs_period_%s', $period ) );

		if( $period === 'all' ){
			$count_all_logs = wp_ulike_get_meta_data( 1, 'statistics', 'count_logs_period_all', true );
			if( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ){
				return $count_all_logs;
			}
		}

		$counter_value = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

		// Make a cachable query to get new like count from all tables
		if( false === $counter_value ){

			$query = sprintf( '
				SELECT
				( SELECT COUNT(*) FROM `%1$sulike` WHERE 1=1 %2$s ) +
				( SELECT COUNT(*) FROM `%1$sulike_activities` WHERE 1=1 %2$s ) +
				( SELECT COUNT(*) FROM `%1$sulike_comments` WHERE 1=1 %2$s ) +
				( SELECT COUNT(*) FROM `%1$sulike_forums` WHERE 1=1 %2$s )
				',
				$wpdb->prefix,
				wp_ulike_get_period_limit_sql( $period )
			);

			$counter_value = $wpdb->get_var( $query );
			wp_cache_add( $cache_key, $counter_value, WP_ULIKE_SLUG, 300 );
		}

		if( $period === 'all' ){
			wp_ulike_update_meta_data( 1, 'statistics', 'count_logs_period_all', $counter_value );
		}

		return empty( $counter_value ) ? 0 : number_format_i18n( $counter_value );
	}
}