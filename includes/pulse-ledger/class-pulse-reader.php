<?php
/**
 * Pulse Ledger — per-user vote state reader.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Reader' ) ) {

	final class WP_Ulike_Pulse_Reader {

		/**
		 * Resolve user's latest legacy action for an item.
		 *
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical or setting type.
		 * @return string|false like|dislike|unlike|undislike|false
		 */
		public static function user_action( $item_id, $user_id, $item_type ) {
			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$cache_key = (string) $user_id . '|' . $item_type . '|' . (string) $item_id;

			static $request_cache = array();
			if ( array_key_exists( $cache_key, $request_cache ) ) {
				return $request_cache[ $cache_key ];
			}

			$read_mode = WP_Ulike_Pulse_Config::read_mode();

			if ( WP_Ulike_Pulse_Config::READ_PULSE === $read_mode ) {
				$result = self::from_pulse( $item_id, $user_id, $item_type );
			} elseif ( WP_Ulike_Pulse_Config::READ_LEGACY === $read_mode ) {
				$result = self::from_legacy( $item_id, $user_id, $item_type );
			} else {
				$result = self::from_merged( $item_id, $user_id, $item_type );
			}

			$request_cache[ $cache_key ] = $result;
			return $result;
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return string|false
		 */
		private static function from_pulse( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			$row   = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT engagement_key, status, date_time
					FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND user_id = %s AND engagement_kind = %s
					ORDER BY date_time DESC, id DESC
					LIMIT 1",
					absint( $item_id ),
					$item_type,
					(string) $user_id,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			if ( ! $row ) {
				return false;
			}

			return WP_Ulike_Pulse_Vote_Map::row_to_legacy( $row->engagement_key, $row->status );
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return string|false
		 */
		private static function from_legacy( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return false;
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			$status = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT status FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s ORDER BY id DESC LIMIT 1",
					absint( $item_id ),
					(string) $user_id
				)
			);

			return $status ? (string) $status : false;
		}

		/**
		 * Newest row wins between legacy snapshot and pulse delta.
		 *
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return string|false
		 */
		private static function from_merged( $item_id, $user_id, $item_type ) {
			$pulse = self::pulse_latest_row( $item_id, $user_id, $item_type );

			// A pulse row always wins when one exists. In dual/merged mode the
			// pulse table is the ONLY write target (wp_ulike_writes_pulse() is
			// true), so any pulse vote row for this user+item is by definition
			// their latest action -- including rows copied over by migration.
			//
			// Do NOT compare legacy vs pulse timestamps. The two columns are not
			// on the same clock: older plugin versions wrote legacy date_time in
			// SITE-LOCAL time, while pulse always writes UTC. On a site with a
			// positive UTC offset a legacy row therefore compares as "newer" than
			// a vote cast seconds ago, so the fresh vote was discarded on page
			// load -- the button flipped back to inactive after a refresh even
			// though the AJAX response and the ledger were both correct.
			if ( $pulse ) {
				return WP_Ulike_Pulse_Vote_Map::row_to_legacy( $pulse->engagement_key, $pulse->status );
			}

			// No post-cutover activity: fall back to the pre-migration snapshot.
			$legacy = self::legacy_latest_row( $item_id, $user_id, $item_type );

			return $legacy ? (string) $legacy->status : false;
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return object|null
		 */
		private static function legacy_latest_row( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return null;
			}

			$table  = esc_sql( $source['table'] );
			$column = esc_sql( $source['column'] );

			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT status, date_time FROM `{$table}` WHERE `{$column}` = %d AND user_id = %s ORDER BY id DESC LIMIT 1",
					absint( $item_id ),
					(string) $user_id
				)
			);
		}

		/**
		 * @param int|string $item_id   Item ID.
		 * @param string     $user_id   User identity.
		 * @param string     $item_type Canonical type.
		 * @return object|null
		 */
		private static function pulse_latest_row( $item_id, $user_id, $item_type ) {
			global $wpdb;

			$table = esc_sql( WP_Ulike_Pulse_Schema::table() );
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT engagement_key, status, date_time FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND user_id = %s AND engagement_kind = %s
					ORDER BY date_time DESC, id DESC LIMIT 1",
					absint( $item_id ),
					$item_type,
					(string) $user_id,
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);
		}
	}
}
