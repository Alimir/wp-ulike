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