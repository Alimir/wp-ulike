<?php
/**
 * Counter Meta Functions
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
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

		wp_cache_delete( $object_id, sprintf( 'wp_ulike_%s_meta', $meta_group ) );

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

		wp_cache_delete( $object_id, sprintf( 'wp_ulike_%s_meta', $meta_group ) );

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
	function wp_ulike_update_meta_cache( $object_ids, $meta_group ) {
		global $wpdb;

		if ( ! $object_ids ) {
			return false;
		}

		$table     = $wpdb->prefix . 'ulike_meta';
		$column    = sanitize_key( 'item_id' );

		if ( ! is_array( $object_ids ) ) {
			$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
			$object_ids = explode( ',', $object_ids );
		}

		$object_ids = array_map( 'intval', $object_ids );

		$cache_key = sprintf( 'wp_ulike_%s_meta', $meta_group );
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
		$meta_list = $wpdb->get_results( "SELECT $column, meta_group, meta_key, meta_value FROM $table WHERE $column IN ($id_list) AND meta_group = '$meta_group' ORDER BY $id_column ASC", ARRAY_A );

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

		$meta_cache = wp_cache_get( $object_id, sprintf( 'wp_ulike_%s_meta', $meta_group ) );

		if ( ! $meta_cache ) {
			$meta_cache = wp_ulike_update_meta_cache( array( $object_id ), $meta_group );
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
		// Remove 'un' prefix from status
		$status = ltrim( $status, 'un');

		if( ( empty( $ID ) && !is_numeric($ID) ) || empty( $type ) ){
			return new WP_Error( 'broke', __( "Please enter some value for required variables.", WP_ULIKE_SLUG ) );
		}

		$counter_value = wp_ulike_meta_counter_value( $ID, $type, $status, $is_distinct );

		if( ( empty( $counter_value ) && ! is_numeric( $counter_value ) ) || ! empty( $date_range ) ){
			global $wpdb;

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