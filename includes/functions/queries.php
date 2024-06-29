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
					return $wpdb->prepare('%d', $user_id);
				}, $parsed_args['user_id']);

				$user_condition = " AND t.user_id IN (" . implode(',', $user_ids) . ")";
			} else {
				$user_condition = $wpdb->prepare( " AND t.user_id = %d", $parsed_args['user_id'] );
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
				foreach ($parsed_args['status'] as $key => $value) {
					$status_type .= $key === 0 ? $wpdb->prepare( "t.meta_key LIKE %s", $meta_prefix . $value ) : $wpdb->prepare( " OR t.meta_key LIKE %s", $meta_prefix . $value );
				}
				$status_type = sprintf( " AND (%s)",  $status_type );
			} else {
				$status_type = $wpdb->prepare( " AND t.meta_key LIKE %s",  $meta_prefix . $parsed_args['status'] );
			}


			// generate query string
			$query  = $wpdb->prepare( "
				SELECT t.item_id AS item_ID, MAX(CAST(t.meta_value AS UNSIGNED)) as counter
				FROM {$wpdb->prefix}ulike_meta t
				INNER JOIN {$info_args['related_table_prefix']} r ON t.item_id = r.{$info_args['related_column']} {$related_condition}
				WHERE t.meta_group = %s AND t.meta_value > 0 {$status_type}
				GROUP BY item_ID
				ORDER BY {$order_by}
				{$parsed_args['order']} {$limit_records}",
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

			$table_name = $wpdb->prefix . $info_args['table'];

			// generate query string
			$query  = sprintf( "
				SELECT COUNT(t.{$info_args['column']}) AS counter,
				t.{$info_args['column']} AS item_ID
				FROM {$table_name} t
				INNER JOIN {$info_args['related_table_prefix']} r ON t.{$info_args['column']} = r.{$info_args['related_column']} {$related_condition}
				WHERE {$status_type} {$user_condition} {$period_limit}
				GROUP BY item_ID
				ORDER BY {$order_by}
				{$parsed_args['order']} {$limit_records}"
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
					return $wpdb->prepare('%d', $user_id);
				}, $parsed_args['user_id']);

				$user_condition = " AND t.user_id IN (" . implode(',', $user_ids) . ")";
			} else {
				$user_condition = $wpdb->prepare( " AND t.user_id = %d", $parsed_args['user_id'] );
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
					$status_type .= $key === 0 ? $wpdb->prepare( "t.meta_key LIKE %s", $meta_prefix . $value ) : $wpdb->prepare( " OR t.meta_key LIKE %s", $meta_prefix . $value );
				}
				$status_type = sprintf( " AND (%s)",  $status_type );
			} else {
				$status_type = $wpdb->prepare( " AND t.meta_key LIKE %s",  $meta_prefix . $parsed_args['status'] );
			}

			// generate query string
			$query  = sprintf( '
				SELECT COUNT(DISTINCT t.item_id)
				FROM %1$s t
				INNER JOIN %2$s r ON t.item_id = r.%3$s %4$s
				WHERE t.meta_value > 0 AND t.meta_group = "%5$s" %6$s',
				$wpdb->prefix . 'ulike_meta',
				$info_args['related_table_prefix'],
				$info_args['related_column'],
				$related_condition,
				$parsed_args['type'],
				$status_type
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
			$query  = sprintf( '
				SELECT COUNT(DISTINCT t.%1$s)
				FROM %2$s t
				INNER JOIN %3$s r ON t.%1$s = r.%4$s %5$s
				WHERE %6$s %7$s
				%8$s',
				$info_args['column'],
				$wpdb->prefix . $info_args['table'],
				$info_args['related_table_prefix'],
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

		if( empty( $get_likers ) && $get_likers !== '0' ){
			// Cache data
			$cache_key  = sanitize_key( sprintf( '%s_%s_%s_likers_list', $table_name, $column_name, $item_ID ) );
			$get_likers = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

			if( false === $get_likers ){
				// Get results
				$get_likers = $wpdb->get_var( $wpdb->prepare("
					SELECT GROUP_CONCAT(DISTINCT(`user_id`) SEPARATOR ',')
					FROM {$wpdb->prefix}{$table_name}
					INNER JOIN {$wpdb->users}
					ON ( {$wpdb->users}.ID = {$wpdb->prefix}{$table_name}.user_id )
					WHERE {$wpdb->prefix}{$table_name}.status IN ('like', 'dislike')
					AND `{$column_name}` = %d", $item_ID
				) );

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

		$query = $wpdb->prepare( "
			SELECT COUNT(*)
			FROM `$table_name`
			WHERE `$column_name` = %s
			AND `user_id` = %d
			AND DATE(date_time) = CURDATE()",
			$parsed_args['item_id'],
			$parsed_args['current_user']
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
    function wp_ulike_get_best_likers_info( $limit, $period, $offset = 1 ){
        global $wpdb;
        // Period limit SQL
        $period_limit = wp_ulike_get_period_limit_sql( $period );

        $limit_records = '';
        if( (int) $limit > 0 ){
            $offset = $offset > 0 ? ( $offset - 1 ) * $limit : 0;
            $limit_records = $wpdb->prepare( "LIMIT %d, %d", $offset, $limit );
        }

        $query  = "
            SELECT T.user_id, SUM(T.CountUser) AS SumUser
            FROM(
                SELECT user_id, count(user_id) AS CountUser
                FROM `{$wpdb->prefix}ulike`
                INNER JOIN {$wpdb->users}
                ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike.user_id )
                WHERE status IN ('like', 'dislike')
                {$period_limit}
                GROUP BY user_id
                UNION ALL
                SELECT user_id, count(user_id) AS CountUser
                FROM `{$wpdb->prefix}ulike_activities`
                INNER JOIN {$wpdb->users}
                ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike_activities.user_id )
                WHERE status IN ('like', 'dislike')
                {$period_limit}
                GROUP BY user_id
                UNION ALL
                SELECT user_id, count(user_id) AS CountUser
                FROM `{$wpdb->prefix}ulike_comments`
                INNER JOIN {$wpdb->users}
                ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike_comments.user_id )
                WHERE status IN ('like', 'dislike')
                {$period_limit}
                GROUP BY user_id
                UNION ALL
                SELECT user_id, count(user_id) AS CountUser
                FROM `{$wpdb->prefix}ulike_forums`
                INNER JOIN {$wpdb->users}
                ON ( {$wpdb->users}.ID = {$wpdb->prefix}ulike_forums.user_id )
                WHERE status IN ('like', 'dislike')
                {$period_limit}
                GROUP BY user_id
            ) AS T
            GROUP BY T.user_id
            ORDER BY SumUser DESC {$limit_records}";

        // Make new SQL request
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
			$status_list = implode ("','", $parsed_args['status'] );
            $status_type = $wpdb->prepare( "`status` IN ('{$status_list}')" );
        } else {
            $status_type = $wpdb->prepare( "`status` = %s", $parsed_args['status'] );
        }

        // generate query string
        $query  = "
            SELECT {$wpdb->prefix}{$parsed_args['table']}.user_id AS userID, count({$wpdb->prefix}{$parsed_args['table']}.user_id) AS score,
            max({$wpdb->prefix}{$parsed_args['table']}.date_time) AS datetime, max({$wpdb->prefix}{$parsed_args['table']}.status) AS lastStatus,
            GROUP_CONCAT(DISTINCT({$wpdb->prefix}{$parsed_args['table']}.{$parsed_args['column']}) SEPARATOR ',') AS itemsList
            FROM {$wpdb->prefix}{$parsed_args['table']}
            INNER JOIN {$wpdb->users}
            ON ( {$wpdb->users}.ID = {$wpdb->prefix}{$parsed_args['table']}.user_id )
            WHERE {$status_type} {$period_limit}
            GROUP BY user_id
            ORDER BY score {$parsed_args['order']}
            LIMIT %d, %d";


        return $wpdb->get_results(  $wpdb->prepare( $query, ( $parsed_args['page'] - 1 ) * $parsed_args['per_page'], $parsed_args['per_page'] ) );
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
			$request = "SELECT
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
							ulike.post_id = %d AND posts.ID = ulike.post_id;";

			//get columns in a row
			$likes 	= $wpdb->get_row( $wpdb->prepare( $request, $post_ID ) );
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
                return $count_all_logs;
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

        return empty( $counter_value ) ? 0 : number_format_i18n( $counter_value );
    }
}