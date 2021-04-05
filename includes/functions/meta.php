<?php
/**
 * Meta Functions
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
		$column    = 'item_id';
		$id_column = 'meta_id';

		// expected_slashed ($meta_key)
		$meta_group = wp_unslash( $meta_group );
		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wp_unslash( $meta_value );

		$check = apply_filters( "wp_ulike_add_{$meta_group}_metadata", null, $object_id, $meta_key, $meta_value, $unique  );
		if ( null !== $check ) {
			return $check;
		}

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

		do_action( "wp_ulike_add_{$meta_group}_meta", $object_id, $meta_key, $_meta_value );

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

		do_action( "wp_ulike_added_{$meta_group}_meta", $mid, $object_id, $meta_key, $_meta_value );

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
		$column    = 'item_id';
		$id_column = 'meta_id';

		// expected_slashed ($meta_key)
		$raw_meta_group = $meta_group;
		$meta_group     = wp_unslash( $meta_group );
		$raw_meta_key   = $meta_key;
		$meta_key       = wp_unslash( $meta_key );
		$passed_value   = $meta_value;
		$meta_value     = wp_unslash( $meta_value );

		$check = apply_filters( "wp_ulike_update_{$meta_group}_metadata", null, $object_id, $meta_key, $meta_value, $prev_value );
		if ( null !== $check ) {
			return (bool) $check;
		}

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty( $prev_value ) ) {
			$old_value = wp_ulike_get_meta_data_raw( $object_id, $meta_group, $meta_key );
			if ( is_countable( $old_value ) && count( $old_value ) == 1 ) {
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
			'meta_key'   => $meta_key
		);

		if ( ! empty( $prev_value ) ) {
			$prev_value          = maybe_serialize( $prev_value );
			$where['meta_value'] = $prev_value;
		}

		foreach ( $meta_ids as $meta_id ) {
			do_action( "wp_ulike_update_{$meta_group}_meta", $meta_id, $object_id, $meta_key, $_meta_value );
		}

		$result = $wpdb->update( $table, $data, $where );
		if ( ! $result ) {
			return false;
		}

		wp_cache_delete( $object_id, sprintf( 'wp_ulike_%s_meta', $meta_group ) );

		foreach ( $meta_ids as $meta_id ) {
			do_action( "wp_ulike_updated_{$meta_group}_meta", $meta_id, $object_id, $meta_key, $_meta_value );
		}

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

		if ( ! $object_ids || ! $meta_group ) {
			return false;
		}

		$table     = $wpdb->prefix . 'ulike_meta';
		$column    = 'item_id';

		if ( ! is_array( $object_ids ) ) {
			$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
			$object_ids = explode( ',', $object_ids );
		}

		$object_ids = array_map( 'intval', $object_ids );

		$check = apply_filters( "wp_ulike_update_{$meta_group}_metadata_cache", null, $object_ids );
		if ( null !== $check ) {
			return (bool) $check;
		}

		$cache_key      = sprintf( 'wp_ulike_%s_meta', $meta_group );
		$non_cached_ids = array();
		$cache          = array();

		// wp_cache_get_multiple function is exist only on wp +5.5
		if( function_exists( 'wp_cache_get_multiple' ) ){
			$cache_values   = wp_cache_get_multiple( $object_ids, $cache_key );
			foreach ( $cache_values as $id => $cached_object ) {
				if ( false === $cached_object ) {
					$non_cached_ids[] = $id;
				} else {
					$cache[ $id ] = $cached_object;
				}
			}
		} else {
			foreach ( $object_ids as $id ) {
				$cached_object = wp_cache_get( $id, $cache_key );
				if ( false === $cached_object ) {
					$non_cached_ids[] = $id;
				} else {
					$cache[ $id ] = $cached_object;
				}
			}
		}

		if ( empty( $non_cached_ids ) ) {
			return $cache;
		}

		// Get meta info.
		$id_list   = join( ',', $non_cached_ids );
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

		foreach ( $non_cached_ids as $id ) {
			if ( ! isset( $cache[ $id ] ) ) {
				$cache[ $id ] = array();
			}
			wp_cache_add( $id, $cache[ $id ], $cache_key );
		}

		return $cache;
	}
}

if( ! function_exists( 'wp_ulike_get_meta_data_raw' ) ){
	/**
	 * Retrieves raw metadata value for the specified object.
	 *
	 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 *                          or any other object type with an associated meta table.
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
	 *                          the specified object. Default empty.
	 * @param bool   $single    Optional. If true, return only the first value of the specified meta_key.
	 *                          This parameter has no effect if meta_key is not specified. Default false.
	 * @return mixed Single metadata value, or array of values. Null if the value does not exist.
	 *               False if there's a problem with the parameters passed to the function.
	 */
	function wp_ulike_get_meta_data_raw( $object_id, $meta_group, $meta_key = '', $single = false ) {
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

if( ! function_exists( 'wp_ulike_get_meta_data' ) ){
	/**
	 * Retrieves the value of a metadata field for the specified object type and ID.
	 *
	 * If the meta field exists, a single value is returned if `$single` is true,
	 * or an array of values if it's false.
	 *
	 * If the meta field does not exist, the result depends on get_metadata_default().
	 * By default, an empty string is returned if `$single` is true, or an empty array
	 * if it's false.
	 *
	 *
	 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 *                          or any other object type with an associated meta table.
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
	 *                          the specified object. Default empty.
	 * @param bool   $single    Optional. If true, return only the first value of the specified meta_key.
	 *                          This parameter has no effect if meta_key is not specified. Default false.
	 * @return mixed Single metadata value, or array of values.
	 *               False if there's a problem with the parameters passed to the function.
	 */
	function wp_ulike_get_meta_data( $object_id, $meta_group, $meta_key = '', $single = false ) {
		$value = wp_ulike_get_meta_data_raw( $object_id, $meta_group, $meta_key, $single );
		if ( ! is_null( $value ) ) {
			return $value;
		}

		return wp_ulike_get_meta_data_default( $object_id, $meta_group, $meta_key, $single );
	}
}

if( ! function_exists( 'wp_ulike_get_meta_data_default' ) ){
	/**
	 * Retrieves default metadata value for the specified meta key and object.
	 *
	 * By default, an empty string is returned if `$single` is true, or an empty array
	 * if it's false.
	 *
	 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 *                          or any other object type with an associated meta table.
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Metadata key.
	 * @param bool   $single    Optional. If true, return only the first value of the specified meta_key.
	 *                          This parameter has no effect if meta_key is not specified. Default false.
	 * @return mixed Single metadata value, or array of values.
	 */
	function wp_ulike_get_meta_data_default( $object_id, $meta_group, $meta_key, $single = false ) {
		if ( $single ) {
			$value = '';
		} else {
			$value = array();
		}

		$value = apply_filters( "wp_ulike_default_{$meta_group}_metadata", $value, $object_id, $meta_key, $single, $meta_group );

		if ( ! $single && ! wp_is_numeric_array( $value ) ) {
			$value = array( $value );
		}

		return $value;
	}
}

if( ! function_exists( 'wp_ulike_delete_meta_data' ) ){
	/**
	 * Deletes metadata for the specified object.
	 *
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 *                           or any other object type with an associated meta table.
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if non-scalar.
	 *                           If specified, only delete metadata entries with this value.
	 *                           Otherwise, delete all entries with the specified meta_key.
	 *                           Pass `null`, `false`, or an empty string to skip this check.
	 *                           (For backward compatibility, it is not possible to pass an empty string
	 *                           to delete those entries with an empty string for a value.)
	 * @param bool   $delete_all Optional. If true, delete matching metadata entries for all objects,
	 *                           ignoring the specified object_id. Otherwise, only delete
	 *                           matching metadata entries for the specified object_id. Default false.
	 * @return bool True on successful delete, false on failure.
	 */
	function wp_ulike_delete_meta_data( $meta_group, $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
		global $wpdb;

		if ( ! $meta_group || ! $meta_key || ! is_numeric( $object_id ) && ! $delete_all ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id && ! $delete_all ) {
			return false;
		}

		$table       = $wpdb->prefix . 'ulike_meta';
		$type_column = 'item_id';
		$id_column   = 'meta_id';

		// expected_slashed ($meta_key)
		$meta_group = wp_unslash( $meta_group );
		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wp_unslash( $meta_value );

		$check = apply_filters( "wp_ulike_delete_{$meta_group}_metadata", null, $object_id, $meta_key, $meta_value, $delete_all );
		if ( null !== $check ) {
			return (bool) $check;
		}

		$_meta_value = $meta_value;
		$meta_value  = maybe_serialize( $meta_value );

		$query = $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_group = %s AND meta_key = %s", $meta_group, $meta_key );

		if ( ! $delete_all ) {
			$query .= $wpdb->prepare( " AND $type_column = %d", $object_id );
		}

		if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
			$query .= $wpdb->prepare( ' AND meta_value = %s', $meta_value );
		}

		$meta_ids = $wpdb->get_col( $query );
		if ( ! count( $meta_ids ) ) {
			return false;
		}

		if ( $delete_all ) {
			if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
				$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_group = %s AND meta_key = %s AND meta_value = %s", $meta_group, $meta_key, $meta_value ) );
			} else {
				$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_group = %s AND meta_key = %s", $meta_group, $meta_key ) );
			}
		}

		do_action( "wp_ulike_delete_{$meta_group}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

		$query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . ' )';

		$count = $wpdb->query( $query );

		if ( ! $count ) {
			return false;
		}

		if ( $delete_all ) {
			foreach ( (array) $object_ids as $o_id ) {
				wp_cache_delete( $o_id, sprintf( 'wp_ulike_%s_meta', $meta_group ) );
			}
		} else {
			wp_cache_delete( $object_id, sprintf( 'wp_ulike_%s_meta', $meta_group ) );
		}

		do_action( "wp_ulike_deleted_{$meta_group}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

		return true;
	}
}