<?php
/**
 * General Functions
 * // @echo HEADER
 */

/*******************************************************
  Settings
*******************************************************/

if( ! function_exists( 'wp_ulike_get_setting' ) ){
	/**
	 * Get Settings Value
	 *
	 * @author       	Alimir
	 * @since           1.0
	 * @return			Void
	 */
	function wp_ulike_get_setting( $setting, $option = false, $default = false ) {
		$setting = get_option( $setting );
		if ( is_array( $setting ) ) {
			if ( $option ) {
				return isset( $setting[$option] ) ? wp_ulike_settings::parse_multi( $setting[$option] ) : $default;
			}
			foreach ( $setting as $k => $v ) {
				$setting[$k] = wp_ulike_settings::parse_multi( $v );
			}

			return $setting;
		}
		return $option ? $default : $setting;
	}
}


if ( ! function_exists( 'wp_ulike_get_option' ) ) {
	/**
	 * Get options list values
	 *
	 * @param string $option
	 * @param array|string $default
	 * @return array|string|null
	 */
	function wp_ulike_get_option( $option = '', $default = null ) {
	  $global_settings = get_option( 'wp_ulike_settings' );

	  if( strpos( $option, '|' ) && is_array( $global_settings ) ){
		$option_name  = explode( "|", $option );
		$option_stack = array();
		foreach ($option_name as $key => $value) {
			if( isset( $global_settings[$value] ) ){
				$option_stack = $global_settings[$value];
				continue;
			}
			if( isset( $option_stack[$value] ) ){
				$option_stack = $option_stack[$value];
			} else {
				return $default;
			}
		}
		return $option_stack;
	  }

	  return ( isset( $global_settings[$option] ) ) ? $global_settings[$option] : $default;
	}
}

if( ! function_exists( 'wp_ulike_delete_all_logs' ) ){
	/**
	 * Delete all the users likes logs by ajax process.
	 *
	 * @author       	Alimir
	 * @since           2.2
	 * @return			Void
	 */
	function wp_ulike_delete_all_logs() {
		global $wpdb;
		$get_action = $_POST['action'];
		//$wpdb->hide_errors();

		if( !current_user_can( 'manage_options' ) ){
			wp_send_json_error( __( 'You\'ve not permission to remove all the logs. ', WP_ULIKE_SLUG ) );
		}

		if($get_action == 'wp_ulike_posts_delete_logs'){
			$logs_table = $wpdb->prefix."ulike";
		} else if($get_action == 'wp_ulike_comments_delete_logs'){
			$logs_table = $wpdb->prefix."ulike_comments";
		} else if($get_action == 'wp_ulike_buddypress_delete_logs'){
			$logs_table = $wpdb->prefix."ulike_activities";
		} else if($get_action == 'wp_ulike_bbpress_delete_logs'){
			$logs_table = $wpdb->prefix."ulike_forums";
		}

		if ($wpdb->query("TRUNCATE TABLE $logs_table") === FALSE) {
			wp_send_json_error( __( 'Failed! There was a problem in removing of logs.', WP_ULIKE_SLUG ) );
		} else {
			wp_send_json_success( __( 'Success! All rows has been deleted!', WP_ULIKE_SLUG ) );
		}
	}
}

if( ! function_exists( 'wp_ulike_delete_orphaned_rows' ) ){
	/**
	 * Delete all the users likes logs by ajax process.
	 *
	 * @author       	Alimir
	 * @since           2.2
	 * @return			Void
	 */
	function wp_ulike_delete_orphaned_rows() {
		global $wpdb;
		$get_action = $_POST['action'];
		//$wpdb->hide_errors();

		if( !current_user_can( 'manage_options' ) ){
			wp_send_json_error( __( 'You\'ve not permission to remove all the logs. ', WP_ULIKE_SLUG ) );
		}

		$type = '';
		switch ($get_action) {
			case 'wp_ulike_posts_delete_orphaned_rows':
				$type = 'post';
				break;

			case 'wp_ulike_comments_delete_orphaned_rows':
				$type = 'comment';
				break;

			case 'wp_ulike_buddypress_delete_orphaned_rows':
				$type = 'activity';
				break;

			case 'wp_ulike_bbpress_delete_orphaned_rows':
				$type = 'topic';
				break;
		}

		if( empty( $type ) ){
			wp_send_json_error( __( 'Bad request!', WP_ULIKE_SLUG ) );
		}

		// get table info
		$info_args = wp_ulike_get_table_info( $type );

		// Create query string
		$query  = sprintf( "
			DELETE FROM %s
			WHERE `%s`
			NOT IN (SELECT dt.`%s`
			FROM %s dt)",
			$wpdb->prefix . $info_args['table'],
			$info_args['column'],
			$info_args['related_column'],
			$wpdb->prefix . $info_args['related_table']
		);

		if ( $wpdb->query( $query ) === FALSE ) {
			wp_send_json_error( __( 'Failed! There was a problem in removing of orphaned rows.', WP_ULIKE_SLUG ) );
		} else {
			wp_send_json_success( __( 'Success! All orphaned rows has been deleted!', WP_ULIKE_SLUG ) );
		}
	}
}


if( ! function_exists( 'wp_ulike_generate_templates_list' ) ){
	/**
	 * Generate templates list
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Array
	 */
	function wp_ulike_generate_templates_list(){
		$default = array(
			'wpulike-default' => array(
				'name'            => __('Default', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_default_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/default.svg',
				'is_text_support' => true
			),
			'wpulike-heart' => array(
				'name'            => __('Heart', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_simple_heart_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/heart.svg',
				'is_text_support' => true
			),
			'wpulike-robeen' => array(
				'name'            => __('Twitter Heart', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_robeen_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/twitter.svg',
				'is_text_support' => false
			),
			'wpulike-animated-heart' => array(
				'name'            => __('Animated Heart', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_animated_heart_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/animated-heart.svg',
				'is_text_support' => false
			)
		);

		return apply_filters( 'wp_ulike_add_templates_list', $default );
	}
}

if( ! function_exists( 'wp_ulike_add_meta_data' ) ){
	/**
	 * Adds metadata for the specified object.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_group   Metadata group.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param bool   $unique     Optional. Whether the specified metadata key should be unique for the object.
	 *                           If true, and the object already has a value for the specified metadata key,
	 *                           no change will be made. Default false.
	 * @return int|false The meta ID on success, false on failure.
	 */
	function wp_ulike_add_meta_data( $object_id, $meta_group, $meta_key, $meta_value, $unique = false ) {
		global $wpdb;

		if ( ! $meta_group || ! $meta_key || ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$meta_type = 'wp_ulike';
		$table     = $wpdb->prefix . 'ulike_meta';
		$column    = sanitize_key( 'item_id' );
		$id_column = 'meta_id';

		// expected_slashed ($meta_key)
		$meta_group = wp_unslash( $meta_group );
		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wp_unslash( $meta_value );

		if ( $unique && $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE meta_group = %s AND meta_key = %s AND $column = %d",
				$meta_group,
				$meta_key,
				$object_id
			)
		) ) {
			return false;
		}

		$_meta_value = $meta_value;
		$meta_value  = maybe_serialize( $meta_value );

		$result = $wpdb->insert(
			$table,
			array(
				$column      => $object_id,
				'meta_group' => $meta_group,
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
			)
		);

		if ( ! $result ) {
			return false;
		}

		$mid = (int) $wpdb->insert_id;

		wp_cache_delete( $object_id, $meta_type . '_meta' );

		return $mid;
	}
}

if( ! function_exists( 'wp_ulike_update_meta_data' ) ){
	/**
	 * Updates metadata for the specified object. If no value already exists for the specified object
	 * ID and metadata key, the metadata will be added.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param string $meta_group   Metadata group.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. If specified, only update existing metadata entries
	 *                           with this value. Otherwise, update all entries.
	 * @return int|bool The new meta field ID if a field with the given key didn't exist and was
	 *                  therefore added, true on successful update, false on failure.
	 */
	function wp_ulike_update_meta_data( $object_id, $meta_group, $meta_key, $meta_value, $prev_value = '' ) {
		global $wpdb;

		if ( ! $meta_group || ! $meta_key || ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$meta_type = 'wp_ulike';
		$table     = $wpdb->prefix . 'ulike_meta';
		$column    = sanitize_key( 'item_id' );
		$id_column = 'meta_id';

		// expected_slashed ($meta_key)
		$raw_meta_group = $meta_group;
		$meta_group     = wp_unslash( $meta_group );
		$raw_meta_key   = $meta_key;
		$meta_key       = wp_unslash( $meta_key );
		$passed_value   = $meta_value;
		$meta_value     = wp_unslash( $meta_value );

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty( $prev_value ) ) {
			$old_value = wp_ulike_get_meta_data( $object_id, $meta_group, $meta_key );
			if ( count( $old_value ) == 1 ) {
				if ( $old_value[0] === $meta_value ) {
					return false;
				}
			}
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_group = %s AND meta_key = %s AND $column = %d", $meta_group, $meta_key, $object_id ) );
		if ( empty( $meta_ids ) ) {
			return wp_ulike_add_meta_data( $object_id, $raw_meta_group, $raw_meta_key, $passed_value );
		}

		$_meta_value = $meta_value;
		$meta_value  = maybe_serialize( $meta_value );

		$data  = compact( 'meta_value' );
		$where = array(
			$column      => $object_id,
			'meta_group' => $meta_group,
			'meta_key'   => $meta_key,
		);

		if ( ! empty( $prev_value ) ) {
			$prev_value          = maybe_serialize( $prev_value );
			$where['meta_value'] = $prev_value;
		}

		$result = $wpdb->update( $table, $data, $where );
		if ( ! $result ) {
			return false;
		}

		wp_cache_delete( $object_id, $meta_type . '_meta' );

		return true;
	}
}

if( ! function_exists( 'wp_ulike_update_meta_cache' ) ){
	/**
	 * Updates the metadata cache for the specified objects.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string|int[] $object_ids Array or comma delimited list of object IDs to update cache for.
	 * @return array|false Metadata cache for the specified objects, or false on failure.
	 */
	function wp_ulike_update_meta_cache( $object_ids ) {
		global $wpdb;

		if ( ! $object_ids ) {
			return false;
		}

		$meta_type = 'wp_ulike';
		$table     = $wpdb->prefix . 'ulike_meta';
		$column    = sanitize_key( 'item_id' );

		if ( ! is_array( $object_ids ) ) {
			$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
			$object_ids = explode( ',', $object_ids );
		}

		$object_ids = array_map( 'intval', $object_ids );

		$cache_key = $meta_type . '_meta';
		$ids       = array();
		$cache     = array();
		foreach ( $object_ids as $id ) {
			$cached_object = wp_cache_get( $id, $cache_key );
			if ( false === $cached_object ) {
				$ids[] = $id;
			} else {
				$cache[ $id ] = $cached_object;
			}
		}

		if ( empty( $ids ) ) {
			return $cache;
		}

		// Get meta info.
		$id_list   = join( ',', $ids );
		$id_column = 'meta_id';
		$meta_list = $wpdb->get_results( "SELECT $column, meta_group, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );

		if ( ! empty( $meta_list ) ) {
			foreach ( $meta_list as $metarow ) {
				$mpid = intval( $metarow[ $column ] );
				$mkey = $metarow['meta_group'] . '_' . $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				// Force subkeys to be array type.
				if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
					$cache[ $mpid ] = array();
				}
				if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
					$cache[ $mpid ][ $mkey ] = array();
				}

				// Add a value to the current pid/key.
				$cache[ $mpid ][ $mkey ][] = $mval;
			}
		}

		foreach ( $ids as $id ) {
			if ( ! isset( $cache[ $id ] ) ) {
				$cache[ $id ] = array();
			}
			wp_cache_add( $id, $cache[ $id ], $cache_key );
		}

		return $cache;
	}
}

if( ! function_exists( 'wp_ulike_get_meta_data' ) ){
	/**
	 * Retrieves metadata for the specified object.
	 *
	 *
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_group  Metadata group
	 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
	 *                          the specified object. Default empty.
	 * @param bool   $single    Optional. If true, return only the first value of the specified meta_key.
	 *                          This parameter has no effect if meta_key is not specified. Default false.
	 * @return mixed Single metadata value, or array of values
	 */
	function wp_ulike_get_meta_data( $object_id, $meta_group, $meta_key = '', $single = false ) {
		if ( ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$meta_cache = wp_cache_get( $object_id, 'wp_ulike_meta' );

		if ( ! $meta_cache ) {
			$meta_cache = wp_ulike_update_meta_cache( array( $object_id ) );
			if ( isset( $meta_cache[ $object_id ] ) ) {
				$meta_cache = $meta_cache[ $object_id ];
			} else {
				$meta_cache = null;
			}
		}

		if ( ! $meta_key || ! $meta_group ) {
			return $meta_cache;
		}

		if ( isset( $meta_cache[ $meta_group . '_' . $meta_key  ] ) ) {
			if ( $single ) {
				return maybe_unserialize( $meta_cache[ $meta_group . '_' . $meta_key ][0] );
			} else {
				return array_map( 'maybe_unserialize', $meta_cache[ $meta_group . '_' . $meta_key ] );
			}
		}

		if ( $single ) {
			return '';
		} else {
			return array();
		}
	}
}

if( ! function_exists( 'wp_ulike_update_meta_counter_value' ) ){
	/**
	 * Update meta counter value
	 *
	 * @param integer $ID
	 * @param string $value
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @return int|bool
	 */
	function wp_ulike_update_meta_counter_value( $ID, $value, $type, $status, $is_distinct = true ){
		$distinct_name = !$is_distinct ? 'total' : 'distinct';
		return wp_ulike_update_meta_data( $ID, $type, sprintf( 'count_%s_%s', $distinct_name, $status ), $value );
	}
}

if( ! function_exists( 'wp_ulike_meta_counter_value' ) ){
	/**
	 * Get meta counter value
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param atring $status
	 * @param boolean $is_distinct
	 * @return mixed Single metadata value, or array of values
	 */
	function wp_ulike_meta_counter_value( $ID, $type, $status, $is_distinct = true ){
		$distinct_name = ! $is_distinct ? 'total' : 'distinct';
		return wp_ulike_get_meta_data( $ID, $type, sprintf( 'count_%s_%s', $distinct_name, $status ), true );
	}
}

if( ! function_exists( 'wp_ulike_get_counter_value_info' ) ){
	/**
	 * Get counter value data
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @param string|array $date_range
	 * @return WP_Error[]|integer
	 */
	function wp_ulike_get_counter_value_info( $ID, $type, $status = 'like', $is_distinct = true, $date_range = NULL ){

		$status = ltrim( $status, 'un');

		if( ( empty( $ID ) && !is_numeric($ID) ) || empty( $type ) ){
			return new WP_Error( 'broke', __( "Please enter some value for required variables.", WP_ULIKE_SLUG ) );
		}

		$counter_value = wp_ulike_meta_counter_value( $ID, $type, $status, $is_distinct );

		if( ( empty( $counter_value ) && ! is_numeric( $counter_value ) ) || ! empty( $date_range ) ){
			global $wpdb;

			$cache_key     = sanitize_key( sprintf( 'counter-query-for-%s-%s-%s-status', $type, $ID, $status ) );
			$counter_value = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

			// Make a general query to get info from target table.
			if( false === $counter_value ){
				// Peroid limit SQL
				$period_limit = wp_ulike_get_period_limit_sql( $date_range );

				// get table info
				$table_info   = wp_ulike_get_table_info( $type );
				if( empty( $table_info ) ){
					return new WP_Error( 'broke', __( "Table info is empty.", WP_ULIKE_SLUG ) );
				}
				extract( $table_info );

				$query = sprintf(
					'SELECT COUNT(%1$s) FROM %2$s WHERE %3$s AND %4$s %5$s',
					esc_sql( $is_distinct ? "DISTINCT `user_id`" : "*" ),
					esc_sql( $wpdb->prefix . $table ),
					esc_sql( $status !== 'all' ? "`status` = '$status'" : "`status` NOT LIKE 'un%'" ),
					esc_sql( "`$column` = '$ID'" ),
					esc_sql( $period_limit )
				);

				$counter_value = $wpdb->get_var( stripslashes( $query ) );
				wp_cache_set( $cache_key, $counter_value, WP_ULIKE_SLUG, 300 );
			}

			// Add counter to meta value
			wp_ulike_update_meta_counter_value( $ID, $counter_value, $type, $status, $is_distinct );
		}

		// By checking this option, users who have upgraded to version +4 and deleted their old logs can add the number of old likes to the new figures.
		$enable_meta_values = wp_ulike_get_option( 'enable_meta_values', false );
		if( wp_ulike_is_true( $enable_meta_values ) && in_array( $status, array( 'like', 'all' ) ) ){
			$counter_value += wp_ulike_get_old_meta_value( $ID, $type );
		}

		// Create an action when counter value is ready.
		do_action('wp_ulike_counter_value_generated');

		return apply_filters( 'wp_ulike_counter_value' , $counter_value, $ID, $type, $status );
	}
}

if( ! function_exists( 'wp_ulike_get_counter_value' ) ){
	/**
	 * Get counter value
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param string $status
	 * @param boolean $is_distinct
	 * @param string|array $date_range
	 * @return integer
	 */
	function wp_ulike_get_counter_value( $ID, $type, $status = 'like', $is_distinct = true, $date_range = NULL ){
		$counter_info = wp_ulike_get_counter_value_info( $ID, $type, $status, $is_distinct, $date_range );
		return ! is_wp_error( $counter_info ) ? (int) $counter_info : 0;
	}
}

if( ! function_exists( 'wp_ulike_get_old_meta_value' ) ){
	/**
	 * Get the number of old meta values
	 *
	 * @param integer $ID
	 * @param string $type
	 * @return integer
	 */
	function wp_ulike_get_old_meta_value( $ID, $type ){
		$meta_value = 0;

		switch ( $type ) {
			case 'post':
				$meta_value = get_post_meta( $ID, '_liked', true );
				break;
			case 'comment':
				$meta_value = get_comment_meta( $ID, '_commentliked', true );
				break;
			case 'activity':
				$meta_value = function_exists( 'bp_activity_get_meta' ) ? bp_activity_get_meta( $ID, '_activityliked' ) : '';
				break;
			case 'topic':
				$meta_value = get_post_meta( $ID, '_topicliked', true );
				break;
		}

		return empty( $meta_value ) ? 0 : (int) $meta_value;
	}
}

if( ! function_exists( 'wp_ulike_get_table_info' ) ){
	/**
	 * Get table info
	 *
	 * @param string $type
	 * @return void
	 */
	function wp_ulike_get_table_info( $type = 'post' ){
		$output = array();

		switch ( $type ) {
			case 'comment':
				$output = array(
					'table'          => 'ulike_comments',
					'column'         => 'comment_id',
					'related_table'  => 'comments',
					'related_column' => 'comment_ID'
				);
				break;

			case 'activity':
				$output = array(
					'table'          => 'ulike_activities',
					'column'         => 'activity_id',
					'related_table'  => 'bp_activity',
					'related_column' => 'id'
				);
				break;

			case 'topic':
				$output = array(
					'table'          => 'ulike_forums',
					'column'         => 'topic_id',
					'related_table'  => 'posts',
					'related_column' => 'ID'
				);
				break;

			default:
				$output = array(
					'table'          => 'ulike',
					'column'         => 'post_id',
					'related_table'  => 'posts',
					'related_column' => 'ID'
				);
				break;
		}

		return $output;
	}
}

if( ! function_exists( 'wp_ulike_get_type_by_table' ) ){
	/**
	 * Get type by table name
	 *
	 * @param string $table
	 * @return void
	 */
	function wp_ulike_get_type_by_table( $table ){
		$output = NULL;

		switch ( $table ) {
			case 'ulike_comments':
				$output = 'comment';
				break;

			case 'ulike_activities':
				$output = 'activity';
				break;

			case 'ulike_forums':
				$output = 'topic';
				break;

			case 'ulike':
				$output = 'post';
				break;
		}

		return $output;
	}
}

/*******************************************************
  Posts
*******************************************************/

if( ! function_exists( 'wp_ulike' ) ){
	/**
	 * Display Like button for posts
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           1.0
	 * @return			String
	 */
	function wp_ulike( $type = 'get', $args = array() ) {
		//global variables
		global $post;

		$post_ID       = isset( $args['id'] ) ? $args['id'] : $post->ID;
		$attributes    = apply_filters( 'wp_ulike_posts_add_attr', null );
		$options       = wp_ulike_get_option( 'posts_group' );
		$post_settings = wp_ulike_get_post_settings_by_type( 'likeThis' );

		//Main data
		$defaults = array_merge( $post_settings, array(
			"id"                   => $post_ID,
			"method"               => 'likeThis',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"options_group"        => 'posts_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}


if( ! function_exists( 'wp_ulike_get_most_liked_posts' ) ){
	/**
	 * Get most liked posts in query
	 *
	 * @param integer $numberposts
	 * @param array|string $post_type
	 * @param string $method
	 * @param string $period
	 * @param string $status
	 * @param boolean $is_noraml
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	function wp_ulike_get_most_liked_posts( $numberposts = 10, $post_type = '', $method = '', $period = 'all', $status = 'like', $is_noraml = false ){
		$post__in = wp_ulike_get_popular_items_ids(array(
			'type'   => $method,
			'status' => $status,
			'period' => $period
		));

		$args = array(
			'numberposts' => $numberposts,
			'post_type'   => $post_type === '' ? get_post_types_by_support( array(
				'title',
				'editor',
				'thumbnail'
			) ) : $post_type
		);

		if( ! empty( $post__in ) ){
			$args['post__in'] = $post__in;
			$args['orderby'] = 'post__in';
		} elseif( empty( $post__in ) && ! $is_noraml ) {
			return false;
		}

		return get_posts( apply_filters( 'wp_ulike_get_top_posts_query', $args ) );
	}
}

if( ! function_exists( 'is_wp_ulike' ) ){
	/**
	 * Check wp ulike callback
	 *
	 * @author       	Alimir
	 * @param           Array 	$options
	 * @param           Array   $args
	 * @since           1.9
	 * @return			boolean
	 */
	function is_wp_ulike( $options, $args = array() ){

		if( empty( $options ) ){
			return true;
		}

		$defaults = apply_filters( 'wp_ulike_auto_diplay_filter_list' , array(
				'is_home'        => is_front_page() && is_home(),
				'is_single'      => is_singular(),
				'is_archive'     => is_archive(),
				'is_category'    => is_category(),
				'is_search'      => is_search(),
				'is_tag'         => is_tag(),
				'is_author'      => is_author(),
				'is_buddypress'  => function_exists('is_buddypress') && is_buddypress(),
				'is_bbpress'     => function_exists('is_bbpress') && is_bbpress(),
				'is_woocommerce' => function_exists('is_woocommerce') && is_woocommerce(),
			)
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		foreach ( $options as $key => $value ) {
			if( isset( $parsed_args[ 'is_' . $value ] ) && ! empty( $parsed_args[ 'is_' . $value ] ) ) {
				if( $value === 'single' ){
					$post_types = wp_ulike_get_option( 'posts_group|auto_display_filter_post_types' );
					if( ! empty( $post_types ) ){
						foreach ($post_types as $p_key => $p_value) {
							if( get_post_type() === $p_value && ! is_page() ){
								return true;
							}
						}
					}
				}
				return false;
			}
		}

		return true;
	}
}


if( ! function_exists( 'wp_ulike_get_post_likes' ) ){
	/**
	 * Get Single Post likes number
	 *
	 * @author       	Alimir
	 * @param           Integer $post_ID
	 * @since           1.7
	 * @return          String
	 */
	function wp_ulike_get_post_likes( $post_ID, $status = 'like' ){
		return wp_ulike_get_counter_value( $post_ID, 'post', $status );
	}
}


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


/*******************************************************
  Comments
*******************************************************/

if( ! function_exists( 'wp_ulike_comments' ) ){
	/**
	 * wp_ulike_comments function for comments like/unlike display
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           1.6
	 * @return			String
	 */
	function wp_ulike_comments( $type = 'get', $args = array() ) {

		$comment_ID       = isset( $args['id'] ) ? $args['id'] : get_comment_ID();
		$attributes       = apply_filters( 'wp_ulike_comments_add_attr', null );
		$options          = wp_ulike_get_option( 'comments_group' );
		$comment_settings = wp_ulike_get_post_settings_by_type( 'likeThisComment' );

		//Main data
		$defaults = array_merge( $comment_settings, array(
			"id"                   => $comment_ID,
			"method"               => 'likeThisComment',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"options_group"        => 'comments_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}

if( ! function_exists( 'wp_ulike_get_most_liked_comments' ) ){
	/**
	 * Get most liked comments in query
	 *
	 * @param integer $numbercomments
	 * @param string $post_type
	 * @param string $period
	 * @param string $status
	 * @return WP_Comment[]|int[] Array of post objects or post IDs.
	 */
	function wp_ulike_get_most_liked_comments( $numbercomments = 10, $post_type = '', $period = 'all', $status = 'like' ){
		$comment__in = wp_ulike_get_popular_items_ids(array(
			"type"   => 'comment',
			'period' => $period,
			'status' => $status
		));
		if( empty( $comment__in ) ){
			return false;
		}
		return get_comments( apply_filters( 'wp_ulike_get_top_comments_query', array(
			'comment__in' => $comment__in,
			'number'      => $numbercomments,
			'orderby'     => 'comment__in',
			'post_type'   => $post_type === '' ? get_post_types_by_support( array(
				'title',
				'editor',
				'thumbnail'
			) ) : $post_type
		) ) );
	}
}


if( ! function_exists( 'wp_ulike_get_comment_likes' ) ){
	/**
	 * Get the number of likes on a single comment
	 *
	 * @author          Alimir & Wacław Jacek
	 * @param           Integer $commentID
	 * @since           2.5
	 * @return          String
	 */
	function wp_ulike_get_comment_likes( $comment_ID ){
		return wp_ulike_get_counter_value( $comment_ID, 'comment' );
	}
}

/*******************************************************
  BuddyPress
*******************************************************/

if( ! function_exists( 'wp_ulike_buddypress' ) ){
	/**
	 * wp_ulike_buddypress function for activities like/unlike display
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           1.7
	 * @return			String
	 */
	function wp_ulike_buddypress( $type = 'get', $args = array() ) {

        if ( bp_get_activity_comment_id() != null ){
			$activityID 	= isset( $args['id'] ) ? $args['id'] : bp_get_activity_comment_id();
		} else {
			$activityID 	= isset( $args['id'] ) ? $args['id'] : bp_get_activity_id();
		}
		$attributes          = apply_filters( 'wp_ulike_activities_add_attr', null );
		$options             = wp_ulike_get_option( 'buddypress_group' );
		$buddypress_settings = wp_ulike_get_post_settings_by_type( 'likeThisActivity' );

		//Main data
		$defaults = array_merge( $buddypress_settings, array(
			"id"                   => $activityID,
			"method"               => 'likeThisActivity',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"options_group"        => 'buddypress_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}


if( ! function_exists( 'wp_ulike_get_auhtor_id' ) ){
	/**
	 * Get auther ID by the ulike types
	 *
	 * @author       	Alimir
	 * @param           Integer $cp_ID (Post/Comment/... ID)
	 * @param           String 	$type (Get ulike Type)
	 * @since           2.5
	 * @return          String
	 */
	function wp_ulike_get_auhtor_id($cp_ID,$type) {
		if($type == '_liked' || $type == '_topicliked'){
			$post_tmp = get_post($cp_ID);
			return $post_tmp->post_author;
		}
		else if($type == '_commentliked'){
			$comment = get_comment( $cp_ID );
			return $comment->user_id;
		}
		else if( $type == '_activityliked' ){
			$activity = bp_activity_get_specific( array( 'activity_ids' => $cp_ID, 'display_comments'  => true ) );
			return $activity['activities'][0]->user_id;
		}
		else return;
	}
}


if( ! function_exists( 'wp_ulike_bbp_format_buddypress_notifications' ) ) {
	/**
	 * Wrapper for bbp_format_buddypress_notifications function as it is not returning $action
	 *
	 * @author       	Alimir
	 * @since           2.5.1
	 * @return          String
	 */
	function wp_ulike_bbp_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

		if ( ! defined( 'BP_VERSION' ) ) {
			return;
		}

		$result = bbp_format_buddypress_notifications(
			$action,
			$item_id,
			$secondary_item_id,
			$total_items,
			$format
		);

		if ( ! $result ) {
			$result = $action;
		}

		return $result;
	}
}


if( ! function_exists( 'wp_ulike_bbp_is_component_exist' ) ) {
	/**
	 * Check the buddypress notification component existence
	 *
	 * @author       	Alimir
	 * @since           2.5.1
	 * @return          integer
	 */
	function wp_ulike_bbp_is_component_exist( $component_name ){
		global $wpdb;
		$bp = buddypress();

		return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$bp->notifications->table_name} WHERE component_action = %s",
					$component_name
				)
			);
	}
}

if( ! function_exists( 'wp_ulike_get_most_liked_activities' ) ) {
	/**
	 * Get most liked activities in array
	 *
	 * @param integer $number
	 * @param string $period
	 * @param string $status
	 * @return object
	 */
	function wp_ulike_get_most_liked_activities( $number = 10, $period = 'all', $status = 'like' ){
		global $wpdb;

		if ( is_multisite() ) {
			$bp_prefix = 'base_prefix';
		} else {
			$bp_prefix = 'prefix';
		}

		$activity_ids = wp_ulike_get_popular_items_ids(array(
			'type'   => 'activity',
			'status' => $status,
			'period' => $period
		));

		if( empty( $activity_ids ) ){
			return false;
		}

		// generate query string
		$query  = sprintf( '
			SELECT * FROM
			`%1$sbp_activity`
			WHERE `id` IN (%2$s)
			ORDER BY FIELD(`id`, %2$s)
			LIMIT %3$s',
			$wpdb->$bp_prefix,
			implode(',',$activity_ids),
			$number
		);

		return $wpdb->get_results( $query );
	}
}

/*******************************************************
  bbPress
*******************************************************/

if( ! function_exists( 'wp_ulike_bbpress' ) ){
	/**
	 * wp_ulike_bbpress function for topics like/unlike display
	 *
	 * @author       	Alimir
	 * @param           String 	$type
	 * @param           Array 	$args
	 * @since           2.2
	 * @return			String
	 */
	function wp_ulike_bbpress( $type = 'get', $args = array() ) {
		//global variables
		global $post;

        //Thanks to @Yehonal for this commit
		$replyID = bbp_get_reply_id();
		$post_ID = !$replyID ? $post->ID : $replyID;
		$post_ID = isset( $args['id'] ) ? $args['id'] : $post_ID;

		$attributes       = apply_filters( 'wp_ulike_topics_add_attr', null );
		$options          = wp_ulike_get_option( 'bbpress_group' );
		$bbpress_settings = wp_ulike_get_post_settings_by_type( 'likeThisTopic' );

		//Main data
		$defaults = array_merge( $bbpress_settings, array(
			"id"                   => $post_ID,
			"method"               => 'likeThisTopic',
			"type"                 => 'post',
			"wrapper_class"        => '',
			"options_group"        => 'bbpress_group',
			"attributes"           => $attributes,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"display_likers"       => isset( $options['enable_likers_box'] ) ? $options['enable_likers_box'] : 0,
			"disable_pophover"     => isset( $options['disable_likers_pophover'] ) ? $options['disable_likers_pophover'] : 0,
			"style"                => isset( $options['template'] ) ? $options['template'] : 'wpulike-default',
			"button_type"          => isset( $options['button_type'] ) ? $options['button_type'] : 'image',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button',
		) );

		$parsed_args = wp_parse_args( $args, $defaults );
		// Output templayte
		$output      = wp_ulike_display_button( $parsed_args );
		// Select retrun or print
        if( $type === 'put' ) {
        	return $output;
        } else {
        	echo $output;
        }

	}
}

/*******************************************************
  General
*******************************************************/

if( ! function_exists( 'wp_ulike_get_post_settings_by_type' ) ){
	/**
	 * Get post settings by its type
	 *
	 * @param string $post_type
	 * @param integer $post_ID (*deprecated)
	 * @return void
	 */
	function wp_ulike_get_post_settings_by_type( $post_type, $post_ID = NULL ){
		switch ( $post_type ) {
			case 'likeThis':
			case 'post':
				$settings = array(
					'setting'  => 'posts_group',
					'table'    => 'ulike',
					'column'   => 'post_id',
					'key'      => '_liked',
					'slug'     => 'post',
					'cookie'   => 'liked-'
				);
				break;

			case 'likeThisComment':
			case 'comment':
				$settings = array(
					'setting'  => 'comments_group',
					'table'    => 'ulike_comments',
					'column'   => 'comment_id',
					'key'      => '_commentliked',
					'slug'     => 'comment',
					'cookie'   => 'comment-liked-'
				);
				break;

			case 'likeThisActivity':
			case 'buddypress':
				$settings = array(
					'setting'  => 'buddypress_group',
					'table'    => 'ulike_activities',
					'column'   => 'activity_id',
					'key'      => '_activityliked',
					'slug'     => 'activity',
					'cookie'   => 'activity-liked-',
				);
				break;

			case 'likeThisTopic':
			case 'bbpress':
				$settings = array(
					'setting'  => 'bbpress_group',
					'table'    => 'ulike_forums',
					'column'   => 'topic_id',
					'key'      => '_topicliked',
					'slug'     => 'topic',
					'cookie'   => 'topic-liked-'
				);
				break;

			default:
				$settings = array();
		}

		return apply_filters( 'wp_ulike_get_post_settings_by_type', $settings, $post_ID );
	}
}

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
		$get_likers = wp_ulike_get_meta_data( $item_ID, $item_type, 'likers_list', true );

		if( empty( $get_likers ) ){
			// Get results
			$get_likers = $wpdb->get_var( "
				SELECT GROUP_CONCAT(DISTINCT(`user_id`) SEPARATOR ',')
				FROM {$wpdb->prefix}{$table_name}
				INNER JOIN {$wpdb->users}
				ON ( {$wpdb->users}.ID = {$wpdb->prefix}{$table_name}.user_id )
				WHERE {$wpdb->prefix}{$table_name}.status IN ('like', 'dislike')
				AND {$column_name} = {$item_ID}"
			);
			if( ! empty( $get_likers) ){
				$get_likers = explode( ',', $get_likers );
				wp_ulike_update_meta_data( $item_ID, $item_type, 'likers_list', $get_likers );
			}
		}

		return ! empty( $get_likers ) ? array_slice( $get_likers, 0, $limit ) : array();
	}
}

if( ! function_exists( 'wp_ulike_get_popular_items_info' ) ){
	/**
	 * Get popular items with their counter & ID
	 *
	 * @param array $args
	 * @return object
	 */
	function wp_ulike_get_popular_items_info( $args = array() ){
		// Global wordpress database object
		global $wpdb;
		//Main data
		$defaults = array(
			"type"   => 'post',
			"status" => 'like',
			"order"  => 'DESC',
			"period" => 'all',
			"limit"  => 0
		);
		$parsed_args  = wp_parse_args( $args, $defaults );
		$info_args    = wp_ulike_get_table_info( $parsed_args['type'] );
		$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

		$status_type  = '';
		if( is_array( $parsed_args['status'] ) ){
			$status_type = sprintf( "`status` IN ('%s')", implode ("','", $parsed_args['status'] ) );
		} else {
			$status_type = sprintf( "`status` = '%s'", $parsed_args['status'] );
		}

		$limit_records = '';
		if( (int) $parsed_args['limit'] > 0 ){
			$limit_records = sprintf( "LIMIT %d", $parsed_args['limit'] );
		}

		// generate query string
		$query  = sprintf( "
			SELECT COUNT(*) AS counter,
			`%s` AS item_ID
			FROM %s
			WHERE %s
			%s
			GROUP BY item_ID
			ORDER BY counter
			%s %s",
			$info_args['column'],
			$wpdb->prefix . $info_args['table'],
			$status_type,
			$period_limit,
			$parsed_args['order'],
			$limit_records
		);

		return $wpdb->get_results( $query );
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
			"type"   => 'post',
			"status" => 'like',
			"order"  => 'DESC',
			"period" => 'all',
			"limit"  => 0
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

if( ! function_exists( 'wp_ulike_get_user_access_capability' ) ){
	/**
	 * Check current user capabilities to access admin pages
	 *
	 * @param [type] $type
	 * @return void
	 */
	function wp_ulike_get_user_access_capability( $type ){
		$current_user  = wp_get_current_user();
		$allowed_roles = apply_filters( 'wp_ulike_display_capabilities', array('administrator'), $type );
		return ! empty( $allowed_roles ) && array_intersect( $allowed_roles, $current_user->roles ) ? key($current_user->allcaps) : 'manage_options';
	}
}

if( ! function_exists( 'wp_ulike_get_likers_template' ) ){
	/**
	 * Get likers box template info.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @param integer $post_ID
	 * @param string $setting_key
	 * @param array $args
	 * @return string
	 */
	function wp_ulike_get_likers_template( $table_name, $column_name, $post_ID, $setting_key, $args = array() ){

		$options  = wp_ulike_get_option( $setting_key );
		//Main data
		$defaults = array(
			"counter"     => isset( $options['likers_count'] ) ? $options['likers_count'] : 10,
			"template"    => isset( $options['likers_template'] ) ? $options['likers_template'] : null,
			"avatar_size" => isset( $options['likers_gravatar_size'] ) ? $options['likers_gravatar_size'] : 64,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		// Get likers list
		$get_users  = wp_ulike_get_likers_list_per_post( $table_name, $column_name, $post_ID, $parsed_args['counter'] );
		// Bulk user list
		$users_list = '';

		if( ! empty( $get_users ) ) {

			// Get likers html template
 			$get_template   = ! empty( $parsed_args['template'] ) ?  $parsed_args['template'] : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>' ;
 			$inner_template = wp_ulike_get_template_between( $get_template, "%START_WHILE%", "%END_WHILE%" );

			foreach ( $get_users as $user ) {
				$user_info	= get_user_by( 'id', $user );
				// Check user existence
				if( ! $user_info ){
					continue;
				}
				$out_template 	= $inner_template;
				if ( $user_info ):
					if( strpos( $out_template, '%USER_AVATAR%' ) !== false ) {
						$avatar_size 	= $parsed_args['avatar_size'];
						$USER_AVATAR 	= get_avatar( $user_info->user_email, $avatar_size, '' , 'avatar' );
						$out_template 	= str_replace( "%USER_AVATAR%", $USER_AVATAR, $out_template );
					}
					if( strpos( $out_template, '%USER_NAME%' ) !== false) {
						$USER_NAME 		= $user_info->display_name;
						$out_template 	= str_replace( "%USER_NAME%", $USER_NAME, $out_template );
					}
					if( strpos( $out_template, '%UM_PROFILE_URL%' ) !== false && function_exists('um_fetch_user') ) {
						global $ultimatemember;
						um_fetch_user( $user_info->ID );
						$UM_PROFILE_URL = um_user_profile_url();
						$out_template 	= str_replace( "%UM_PROFILE_URL%", $UM_PROFILE_URL, $out_template );
					}
					if( strpos( $out_template, '%BP_PROFILE_URL%' ) !== false && function_exists('bp_core_get_user_domain') ) {
						$BP_PROFILE_URL = bp_core_get_user_domain( $user_info->ID );
						$out_template 	= str_replace( "%BP_PROFILE_URL%", $BP_PROFILE_URL, $out_template );
					}
					$users_list .= $out_template;
				endif;
			}

			if( ! empty( $users_list ) ) {
				return wp_ulike_put_template_between( $get_template, $users_list, "%START_WHILE%", "%END_WHILE%" );
			}
		}

		return NULL;
	}
}

if( ! function_exists( 'wp_ulike_get_template_between' ) ){
	/**
	 * Get template between
	 *
	 * @author       	Alimir
	 * @param           String $string
	 * @param           String $start
	 * @param           String $end
	 * @since           2.0
	 * @return			String
	 */
	function wp_ulike_get_template_between( $string, $start, $end ){
		$string 	= " ".$string;
		$ini 		= strpos($string,$start);
		if ( $ini == 0 ){
			return "";
		}
		$ini 		+= strlen($start);
		$len 		= strpos($string,$end,$ini) - $ini;

		return substr( $string, $ini, $len );
	}
}


if( ! function_exists( 'wp_ulike_put_template_between' ) ){
	/**
	 * Put template between
	 *
	 * @author       	Alimir
	 * @param           String $string
	 * @param           String $inner_string
	 * @param           String $start
	 * @param           String $end
	 * @since           2.0
	 * @return			String
	 */
	function wp_ulike_put_template_between( $string, $inner_string, $start, $end ){
		$string 	= " ".$string;
		$ini 		= strpos($string,$start);
		if ($ini == 0){
			return "";
		}

		$ini 		+= strlen($start);
		$len		= strpos($string,$end,$ini) - $ini;
		$newstr 	= substr_replace($string,$inner_string,$ini,$len);

		return str_replace(
			array( "%START_WHILE%", "%END_WHILE%" ),
			array( "", "" ),
			$newstr
		);
	}
}

if( ! function_exists( 'wp_ulike_display_button' ) ){
	/**
	 * Convert numbers of Likes with string (kilobyte) format
	 *
	 * @author       	Alimir
	 * @param           Array  		$parsed_args
	 * @param           Integer 	$deprecated_value
	 * @since           3.4
	 * @return          String
	 */
	function wp_ulike_display_button( array $args, $deprecated_value = null ){
		global $wp_ulike_class;

		if( ! wp_ulike_is_true( $args['only_logged_in_users'] ) || is_user_logged_in() ) {
			// Return ulike template
			return $wp_ulike_class->wp_get_ulike( $args );
		} else {
			if( $args['logged_out_action'] === 'button' ){
				return $wp_ulike_class->get_template( $args, 0 );
			} else {
				$template = wp_ulike_get_option( $args['options_group'] . '|login_template', sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>', __('You need to login in order to like this post: ',WP_ULIKE_SLUG),
				wp_login_url( get_permalink() ),
				__('click here',WP_ULIKE_SLUG)
				) );
				return apply_filters( 'wp_ulike_login_alert_template', $template );
			}
		}
	}
}

if( ! function_exists( 'wp_ulike_get_custom_style' ) ){
	/**
	 * Get custom style setting from customize options
	 *
	 * @author       	Alimir
	 * @since           1.3
	 * @return          Void (Print new CSS styles)
	 */
	function wp_ulike_get_custom_style( $return_style = null ){

		// Display deprecated styles
		if( wp_ulike_get_setting( 'wp_ulike_customize', 'custom_style' ) && wp_ulike_get_option( 'enable_deprecated_options' ) ) {
			//get custom options
			$customstyle   = get_option( 'wp_ulike_customize' );
			$btn_style     = '';
			$counter_style = '';
			$before_style  = '';

			// Button Style
			if( isset( $customstyle['btn_bg'] ) && ! empty( $customstyle['btn_bg'] ) ) {
				$btn_style .= "background-color:".$customstyle['btn_bg'].";";
			}
			if( isset( $customstyle['btn_border'] ) && ! empty( $customstyle['btn_border'] ) ) {
				$btn_style .= "box-shadow: 0 0 0 1px ".$customstyle['btn_border']." inset; ";
			}
			if( isset( $customstyle['btn_color'] ) && ! empty( $customstyle['btn_color'] ) ) {
				$btn_style .= "color:".$customstyle['btn_color'].";";
			}

			if( $btn_style != '' ){
				$return_style .= '.wpulike-default .wp_ulike_btn, .wpulike-default .wp_ulike_btn:hover, #bbpress-forums .wpulike-default .wp_ulike_btn, #bbpress-forums .wpulike-default .wp_ulike_btn:hover{'.$btn_style.'}.wpulike-heart .wp_ulike_general_class{'.$btn_style.'}';
			}

			// Counter Style
			if( isset( $customstyle['counter_bg'] ) && ! empty( $customstyle['counter_bg'] ) ) {
				$counter_style .= "background-color:".$customstyle['counter_bg'].";";
			}
			if( isset( $customstyle['counter_border'] ) && ! empty( $customstyle['counter_border'] ) ) {
				$counter_style .= "box-shadow: 0 0 0 1px ".$customstyle['counter_border']." inset; ";
				$before_style  = "background-color:".$customstyle['counter_bg']."; border-color:transparent; border-bottom-color:".$customstyle['counter_border']."; border-left-color:".$customstyle['counter_border'].";";
			}
			if( isset( $customstyle['counter_color'] ) && ! empty( $customstyle['counter_color'] ) ) {
				$counter_style .= "color:".$customstyle['counter_color'].";";
			}

			if( $counter_style != '' ){
				$return_style .= '.wpulike-default .count-box,.wpulike-default .count-box{'.$counter_style.'}.wpulike-default .count-box:before{'.$before_style.'}';
			}
		}

		// Custom Spinner
		if( '' != ( $custom_spinner = wp_ulike_get_option( 'custom_spinner' ) ) ) {
			$return_style .= '.wpulike .wp_ulike_is_loading .wp_ulike_btn, #buddypress .activity-content .wpulike .wp_ulike_is_loading .wp_ulike_btn, #bbpress-forums .bbp-reply-content .wpulike .wp_ulike_is_loading .wp_ulike_btn {background-image: url('.$custom_spinner.') !important;}';
		}

		// Custom Styles
		if( '' != ( $custom_css = wp_ulike_get_option( 'custom_css' ) ) ) {
			$return_style .= $custom_css;
		}

		return apply_filters( 'wp_ulike_custom_css', $return_style );
	}

}

if( ! function_exists( 'wp_ulike_format_number' ) ){
	/**
	 * Convert numbers of Likes with string (kilobyte) format
	 *
	 * @author       	Alimir
	 * @param           Integer $num (get like number)
	 * @since           1.5
	 * @return          String
	 */
	function wp_ulike_format_number( $num, $status = 'like' ){
		$sign = $value = '';
		if( $num != 0 ){
			$sign = strpos( $status, 'dis' ) === false ? '+' : '-';
		}
		if ( $num >= 1000 &&  wp_ulike_is_true( wp_ulike_get_option( 'enable_kilobyte_format', false ) ) ){
			$value = round($num/1000, 2) . 'K' . $sign;
		} else {
			$value = $num . $sign;
		}

		return apply_filters( 'wp_ulike_format_number', $value, $num, $sign);
	}
}

if( ! function_exists( 'wp_ulike_date_i18n' ) ){
	/**
	 * Date in localized format
	 *
	 * @author       	Alimir
	 * @param           String (Date)
	 * @since           2.3
	 * @return          String
	 */
	function wp_ulike_date_i18n($date){
		return date_i18n(
			get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ),
			strtotime($date)
		);
	}
}

if( ! function_exists( 'wp_ulike_get_user_ip' ) ){
	/**
	 * Get user IP
	 *
	 * @return string
	 */
	function wp_ulike_get_user_ip(){
		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode(',', $_SERVER[$key]) as $ip ) {
					// trim for safety measures
					$ip = trim( $ip );
					// attempt to validate IP
					if ( wp_ulike_validate_ip( $ip ) ) {
						return $ip;
					}
				}
			}
		}

		return '127.0.0.1';
	}
}

if( ! function_exists( 'wp_ulike_validate_ip' ) ){
	/**
	 * Ensures an ip address is both a valid IP and does not fall within a private network range.
	 *
	 * @param string $ip
	 * @return boolean
	 */
	function wp_ulike_validate_ip( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ? false : true;
	}
}

if( ! function_exists( 'wp_ulike_generate_user_id' ) ){
	/**
	 * Convert IP to a integer value
	 *
	 * @author       	Alimir
	 * @param           String $user_ip
	 * @since           3.4
	 * @return          String
	 */
	function wp_ulike_generate_user_id( $user_ip ) {
		if( wp_ulike_validate_ip(  $user_ip  ) ) {
		    return ip2long( $user_ip );
		} else {
			// Get non-anonymise IP address
			$user_ip    = wp_ulike_get_user_ip();
			$binary_val = '';
		    foreach ( unpack( 'C*', inet_pton( $user_ip ) ) as $byte ) {
		        $binary_val .= decbin( $byte );
		    }
		    return base_convert( ltrim( $binary_val, '0' ), 2, 10 );
		}

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

if( ! function_exists( 'wp_ulike_set_transient' ) ) {
	/**
	 * Set/update the value of a transient.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 *
	 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
	 *                           172 characters or fewer in length.
	 * @param mixed  $value      Transient value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
	 * @return bool False if value was not set and true if value was set.
	 */
	function wp_ulike_set_transient( $transient, $value, $expiration = 0 ) {
		global $_wp_using_ext_object_cache;

		$current_using_cache = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		$result = set_transient( $transient, $value, $expiration );

		$_wp_using_ext_object_cache = $current_using_cache;

		return $result;
	}
}

if( ! function_exists( 'wp_ulike_get_transient' ) ) {
	/**
	 * Get the value of a transient.
	 *
	 * If the transient does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @return mixed Value of transient.
	 */
	function wp_ulike_get_transient( $transient ) {
		global $_wp_using_ext_object_cache;

		$current_using_cache = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		$result = get_transient( $transient );

		$_wp_using_ext_object_cache = $current_using_cache;

		return $result;
	}
}

if( ! function_exists( 'wp_ulike_delete_transient' ) ) {
	/**
	 * Delete a transient.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @return bool true if successful, false otherwise
	 */
	function wp_ulike_delete_transient( $transient ) {
		global $_wp_using_ext_object_cache;

		$current_using_cache = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		$result = delete_transient( $transient );

		$_wp_using_ext_object_cache = $current_using_cache;

		return $result;
	}
}


if( ! function_exists('wp_ulike_is_true') ){
	/**
	 * Check variable status
	 *
	 * @return void
	 */
    function wp_ulike_is_true( $var ) {
        if ( is_bool( $var ) ) {
            return $var;
        }
        if ( is_string( $var ) ){
            $var = strtolower( $var );
            if( in_array( $var, array( 'yes', 'on', 'true', 'checked' ) ) ){
                return true;
            }
        }
        if ( is_numeric( $var ) ) {
            return (bool) $var;
        }
        return false;
    }
}

if( ! function_exists('wp_ulike_is_cache_exist') ){
	/**
	 * Check cache existence
	 *
	 * @return void
	 */
	function wp_ulike_is_cache_exist(){
		return defined( 'WP_CACHE' ) && WP_CACHE === true;
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
		$count_all_logs = wp_ulike_get_meta_data( 1, 'statistics', 'count_logs_period_all', true );

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
			wp_cache_set( $cache_key, $counter_value, WP_ULIKE_SLUG, 300 );
		}

		if( $period === 'all' ){
			wp_ulike_update_meta_data( 1, 'statistics', 'count_logs_period_all', $counter_value );
		}

		return empty( $counter_value ) ? 0 : $counter_value;
	}
}

if( ! function_exists('wp_ulike_get_button_text') ){
	/**
	 * Get button text by option name
	 *
	 * @param string $option_name
	 * @return string
	 */
	function wp_ulike_get_button_text( $option_name, $setting_key = 'posts_group' ){
		$value = wp_ulike_get_option( $setting_key . '|text_group|' . $option_name );
		return apply_filters( 'wp_ulike_button_text', $value, $option_name, $setting_key );
	}
}

if( ! function_exists('wp_ulike_get_best_likers_info') ){
	/**
	 * Get most liked users in query
	 *
	 * @param integer $number
	 * @param string $peroid
	 * @return object
	 */
	function wp_ulike_get_best_likers_info( $number, $peroid ){
		global $wpdb;
		// Peroid limit SQL
		$period_limit = wp_ulike_get_period_limit_sql( $peroid );

		$query  = sprintf( 'SELECT T.user_id, SUM(T.CountUser) AS SumUser
		FROM(
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike`
		WHERE user_id BETWEEN 1 AND 999999
		%2$s
		GROUP BY user_id
		UNION ALL
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike_activities`
		WHERE user_id BETWEEN 1 AND 999999
		%2$s
		GROUP BY user_id
		UNION ALL
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike_comments`
		WHERE user_id BETWEEN 1 AND 999999
		%2$s
		GROUP BY user_id
		UNION ALL
		SELECT user_id, count(user_id) AS CountUser
		FROM `%1$sulike_forums`
		WHERE user_id BETWEEN 1 AND 999999
		%2$s
		GROUP BY user_id
		) AS T
		GROUP BY T.user_id
		ORDER BY SumUser DESC LIMIT %3$s', $wpdb->prefix, $period_limit, $number );

		// Make new sql request
		return $wpdb->get_results( $query );
	}
}

if( ! function_exists('wp_ulike_get_period_limit_sql') ){
	/**
	 * Get period limit as a sql string
	 *
	 * @param string|array $date_range
	 * @return string
	 */
	function wp_ulike_get_period_limit_sql( $date_range ){
		$period_limit = '';

		if( is_array( $date_range ) && isset( $date_range['start'] ) ){
			if( $date_range['start'] === $date_range['end'] ){
				$period_limit = sprintf( 'AND DATE(`date_time`) = \'%s\'', $date_range['start'] );
			} else {
				$period_limit = sprintf( 'AND DATE(`date_time`) >= \'%s\' AND DATE(`date_time`) <= \'%s\'', $date_range['start'], $date_range['end'] );
			}
		} elseif( !empty( $date_range )) {
			switch ($date_range) {
				case "today":
					$period_limit = " AND DATE(date_time) = DATE(NOW())";
					break;
				case "yesterday":
					$period_limit = " AND DATE(date_time) = DATE(subdate(current_date, 1))";
					break;
				case "week":
					$period_limit = " AND week(DATE(date_time)) = week(DATE(NOW()))";
					break;
				case "month":
					$period_limit = " AND month(DATE(date_time)) = month(DATE(NOW()))";
					break;
				case "year":
					$period_limit = " AND year(DATE(date_time)) = year(DATE(NOW()))";
					break;
			}
		}

		return $period_limit;
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
			( $parsed_args['page'] - 1 ) * $parsed_args['page'],
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
			( $parsed_args['page'] - 1 ) * $parsed_args['page'],
			$parsed_args['per_page']
		);

		return $wpdb->get_results( $query );
	}
}

/*******************************************************
  Templates
*******************************************************/

if( ! function_exists( 'wp_ulike_set_default_template' ) ){
	/**
	 * Create simple default template
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_default_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-default <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-disable-pophover="<?php echo $disable_pophover; ?>"
					class="<?php echo $button_class; ?>">
					<?php
						do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
						if($button_type == 'text'){
							echo '<span>' . $button_text . '</span>';
						}
					?>
				</button>
				<?php echo $counter; ?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}

if( ! function_exists( 'wp_ulike_set_simple_heart_template' ) ){
	/**
	 * Create simple heart template
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_simple_heart_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-heart <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-disable-pophover="<?php echo $disable_pophover; ?>"
					class="<?php echo $button_class; ?>">
					<?php
						do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
						if( $button_type == 'text' ){
							echo '<span>' . $button_text . '</span>';
						}
					?>
				</button>
				<?php echo $counter; ?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}

if( ! function_exists( 'wp_ulike_set_robeen_template' ) ){
	/**
	 * Create Robeen (Animated Heart) template
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_robeen_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-robeen <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
					<label title="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>">
					<input type="checkbox"
							data-ulike-id="<?php echo $ID; ?>"
							data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
							data-ulike-type="<?php echo $type; ?>"
							data-ulike-template="<?php echo $style; ?>"
							data-ulike-display-likers="<?php echo $display_likers; ?>"
							data-ulike-disable-pophover="<?php echo $disable_pophover; ?>"
							class="<?php echo $button_class; ?>"
							<?php echo in_array( $status, array( 2, 4 ) )  ? 'checked="checked"' : ''; ?> />
					<?php do_action( 'wp_ulike_inside_like_button', $wp_ulike_template ); ?>
					<svg class="heart-svg" viewBox="467 392 58 57" xmlns="http://www.w3.org/2000/svg">
					    <g class="Group" fill="none" fill-rule="evenodd" transform="translate(467 392)">
					        <path d="M29.144 20.773c-.063-.13-4.227-8.67-11.44-2.59C7.63 28.795 28.94 43.256 29.143 43.394c.204-.138 21.513-14.6 11.44-25.213-7.214-6.08-11.377 2.46-11.44 2.59z" class="heart" fill="#AAB8C2" />
					        <circle class="main-circ" fill="#E2264D" opacity="0" cx="29.5" cy="29.5" r="1.5" />
					        <g class="grp7" opacity="0" transform="translate(7 6)">
					            <circle class="oval1" fill="#9CD8C3" cx="2" cy="6" r="2" />
					            <circle class="oval2" fill="#8CE8C3" cx="5" cy="2" r="2" />
					        </g>
					        <g class="grp6" opacity="0" transform="translate(0 28)">
					            <circle class="oval1" fill="#CC8EF5" cx="2" cy="7" r="2" />
					            <circle class="oval2" fill="#91D2FA" cx="3" cy="2" r="2" />
					        </g>
					        <g class="grp3" opacity="0" transform="translate(52 28)">
					            <circle class="oval2" fill="#9CD8C3" cx="2" cy="7" r="2" />
					            <circle class="oval1" fill="#8CE8C3" cx="4" cy="2" r="2" />
					        </g>
					        <g class="grp2" opacity="0" transform="translate(44 6)" fill="#CC8EF5">
					            <circle class="oval2" transform="matrix(-1 0 0 1 10 0)" cx="5" cy="6" r="2" />
					            <circle class="oval1" transform="matrix(-1 0 0 1 4 0)" cx="2" cy="2" r="2" />
					        </g>
					        <g class="grp5" opacity="0" transform="translate(14 50)" fill="#91D2FA">
					            <circle class="oval1" transform="matrix(-1 0 0 1 12 0)" cx="6" cy="5" r="2" />
					            <circle class="oval2" transform="matrix(-1 0 0 1 4 0)" cx="2" cy="2" r="2" />
					        </g>
					        <g class="grp4" opacity="0" transform="translate(35 50)" fill="#F48EA7">
					            <circle class="oval1" transform="matrix(-1 0 0 1 12 0)" cx="6" cy="5" r="2" />
					            <circle class="oval2" transform="matrix(-1 0 0 1 4 0)" cx="2" cy="2" r="2" />
					        </g>
					        <g class="grp1" opacity="0" transform="translate(24)" fill="#9FC7FA">
					            <circle class="oval1" cx="2.5" cy="3" r="2" />
					            <circle class="oval2" cx="7.5" cy="2" r="2" />
					        </g>
					    </g>
					</svg>
					<?php echo $counter; ?>
					</label>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}


if( ! function_exists( 'wp_ulike_set_animated_heart_template' ) ){
	/**
	 * Create Animated Heart template
	 *
	 * @author       	Alimir
	 * @since           3.6.2
	 * @return			Void
	 */
	function wp_ulike_set_animated_heart_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-animated-heart <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-disable-pophover="<?php echo $disable_pophover; ?>"
					data-ulike-append="<?php echo htmlspecialchars( '<svg class="wpulike-svg-heart wpulike-svg-heart-pop one" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop two" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop three" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop four" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop five" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop six" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop seven" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop eight" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop nine" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg>' ); ?>"
					class="<?php echo $button_class; ?>">
					<?php do_action( 'wp_ulike_inside_like_button', $wp_ulike_template ); ?>
					<svg class="wpulike-svg-heart wpulike-svg-heart-icon" viewBox="0 -28 512.00002 512" xmlns="http://www.w3.org/2000/svg">
					<path
						d="m471.382812 44.578125c-26.503906-28.746094-62.871093-44.578125-102.410156-44.578125-29.554687 0-56.621094 9.34375-80.449218 27.769531-12.023438 9.300781-22.917969 20.679688-32.523438 33.960938-9.601562-13.277344-20.5-24.660157-32.527344-33.960938-23.824218-18.425781-50.890625-27.769531-80.445312-27.769531-39.539063 0-75.910156 15.832031-102.414063 44.578125-26.1875 28.410156-40.613281 67.222656-40.613281 109.292969 0 43.300781 16.136719 82.9375 50.78125 124.742187 30.992188 37.394531 75.535156 75.355469 127.117188 119.3125 17.613281 15.011719 37.578124 32.027344 58.308593 50.152344 5.476563 4.796875 12.503907 7.4375 19.792969 7.4375 7.285156 0 14.316406-2.640625 19.785156-7.429687 20.730469-18.128907 40.707032-35.152344 58.328125-50.171876 51.574219-43.949218 96.117188-81.90625 127.109375-119.304687 34.644532-41.800781 50.777344-81.4375 50.777344-124.742187 0-42.066407-14.425781-80.878907-40.617188-109.289063zm0 0" />
					</svg>
				</button>
				<?php echo $counter; ?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}