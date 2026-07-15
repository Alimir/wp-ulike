<?php
/**
 * Pulse Ledger — vote write path.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Writer' ) ) {

	final class WP_Ulike_Pulse_Writer {

		/**
		 * True while bulk migration import is running (suppresses hooks + cache bumps).
		 *
		 * @var bool
		 */
		private static $migrating = false;

		/**
		 * @return bool
		 */
		public static function is_migrating() {
			return self::$migrating;
		}

		/**
		 * Insert append-only vote row.
		 *
		 * @param array<string,mixed> $payload Vote data.
		 * @return int|false Insert ID or false.
		 */
		public static function insert( array $payload ) {
			global $wpdb;

			$row = self::normalize_payload( $payload, false );
			if ( ! $row ) {
				return false;
			}

			$result = $wpdb->insert( self::table(), $row['data'], $row['format'] );
			if ( false === $result ) {
				return false;
			}

			$id = (int) $wpdb->insert_id;
			if ( ! self::$migrating ) {
				self::fire_inserted( $id, $payload, $row['legacy_status'] );
			}
			return $id;
		}

		/**
		 * Upsert distinct-mode vote (one row per user+item+key).
		 *
		 * @param array<string,mixed> $payload Vote data.
		 * @return int|false Row ID.
		 */
		public static function upsert( array $payload ) {
			global $wpdb;

			$row = self::normalize_payload( $payload, true );
			if ( ! $row || empty( $row['dedupe_token'] ) ) {
				return false;
			}

			$table = esc_sql( self::table() );
			$data  = $row['data'];
			$token = $row['dedupe_token'];

			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$table}` WHERE dedupe_token = %s LIMIT 1",
					$token
				)
			);

		if ( $existing_id ) {
			$update_data   = array(
				'engagement_key' => $data['engagement_key'],
				'status'         => $data['status'],
				'date_time'      => $data['date_time'],
				'ip'             => $data['ip'],
				'fingerprint'    => $data['fingerprint'],
			);
			$update_format = array( '%s', '%s', '%s', '%s', '%s' );

			// Backfill geo/device columns only when explicitly provided (e.g.
			// migration re-run). Live re-votes leave them null in the payload
			// so we do NOT wipe geo data that Pro's hook wrote on the prior vote.
			foreach ( array( 'country_code', 'device', 'os', 'browser' ) as $geo_col ) {
				if ( null !== $data[ $geo_col ] ) {
					$update_data[ $geo_col ]   = $data[ $geo_col ];
					$update_format[]           = '%s';
				}
			}

			$updated = $wpdb->update(
				self::table(),
				$update_data,
				array( 'id' => $existing_id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $updated ) {
				return false;
			}

			if ( ! self::$migrating ) {
				self::fire_updated( $existing_id, $payload, $row['legacy_status'] );
			}
			return $existing_id;
		}

			return self::insert( $payload );
		}

		/**
		 * Import legacy row during migration (idempotent via dedupe when possible).
		 *
		 * @param array<string,mixed> $source Legacy source config.
		 * @param object              $legacy_row Legacy DB row.
		 * @param bool                $is_distinct Site logging mode for type.
		 * @return int|false|string 'skipped' when row cannot map.
		 */
		public static function import_legacy_row( array $source, $legacy_row, $is_distinct ) {
			$column = $source['column'];
			if ( ! isset( $legacy_row->{$column} ) ) {
				return 'skipped';
			}

		$legacy_status = isset( $legacy_row->status ) ? (string) $legacy_row->status : WP_Ulike_Pulse_Vote_Map::ACTION_LIKE;
		$mapped        = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $legacy_status );

		// Early Pro builds stored device as a `device_type` ENUM (D/M/T) before
		// it was renamed to `device` (full UA-derived label). Fall back to it so
		// those sites do not lose device data on migration.
		$device = null;
		if ( isset( $legacy_row->device ) ) {
			$device = (string) $legacy_row->device;
		} elseif ( isset( $legacy_row->device_type ) ) {
			$device = (string) $legacy_row->device_type;
		}

		$payload = array(
			'item_id'        => (int) $legacy_row->{$column},
			'item_type'      => $source['item_type'],
			'legacy_status'  => $legacy_status,
			'engagement_key' => $mapped['engagement_key'],
			'status'         => $mapped['status'],
			'date_time'      => isset( $legacy_row->date_time ) ? (string) $legacy_row->date_time : current_time( 'mysql', true ),
			'ip'             => isset( $legacy_row->ip ) ? (string) $legacy_row->ip : '',
			'user_id'        => isset( $legacy_row->user_id ) ? (string) $legacy_row->user_id : '0',
			'fingerprint'    => isset( $legacy_row->fingerprint ) ? (string) $legacy_row->fingerprint : null,
			'country_code'   => isset( $legacy_row->country_code ) ? (string) $legacy_row->country_code : null,
			'device'         => $device,
			'os'             => isset( $legacy_row->os ) ? (string) $legacy_row->os : null,
			'browser'        => isset( $legacy_row->browser ) ? (string) $legacy_row->browser : null,
			'is_distinct'    => $is_distinct,
		);

			if ( $is_distinct ) {
				self::$migrating = true;
				try {
					return self::upsert( $payload );
				} finally {
					self::$migrating = false;
				}
			}

			self::$migrating = true;
			try {
				return self::insert( $payload );
		} finally {
			self::$migrating = false;
		}
	}

	/**
	 * Delete distinct-mode vote row(s) for a user (REST API delete_item).
	 *
	 * Matches by item + identity (user_id for logged-in, fingerprint for
	 * guests) + kind=vote, so it removes whatever vote (like or dislike)
	 * the user has on the item. Optionally scope to a specific engagement_key.
	 *
	 * @param int|string $item_id        Item ID.
	 * @param string     $item_type      Item type.
	 * @param string     $user_id        User ID.
	 * @param string     $engagement_key Optional engagement key to scope ('like'|'dislike'). Empty = all vote rows.
	 * @param string     $fingerprint    Guest fingerprint (used when user_id is 0/empty).
	 * @return int|false Rows affected.
	 */
	public static function delete( $item_id, $item_type, $user_id, $engagement_key = '', $fingerprint = '' ) {
		global $wpdb;

		$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
		$user_id   = (string) $user_id;

		$where  = array(
			'item_id'         => absint( $item_id ),
			'item_type'       => $item_type,
			'engagement_kind' => WP_Ulike_Pulse_Registry::KIND_VOTE,
		);
		$format = array( '%d', '%s', '%s' );

		if ( '' !== $user_id && '0' !== $user_id ) {
			$where['user_id'] = $user_id;
			$format[]         = '%s';
		} else {
			$fingerprint = (string) $fingerprint;
			if ( '' === $fingerprint ) {
				return false;
			}
			$where['fingerprint'] = $fingerprint;
			$format[]             = '%s';
		}

		if ( $engagement_key ) {
			$where['engagement_key'] = sanitize_key( $engagement_key );
			$format[]                = '%s';
		}

		$deleted = $wpdb->delete( self::table(), $where, $format );

		if ( $deleted ) {
			do_action(
				'wp_ulike_delete_vote_data',
				absint( $item_id ),
				$item_type,
				array( 'storage' => 'pulse', 'user_id' => $user_id ),
				(int) $deleted
			);
		}

		return $deleted;
	}

	/**
	 * Delete all vote rows for an item (post/comment cleanup).
	 *
	 * @param int    $item_id       Item ID.
	 * @param string $setting_type  wp_ulike_setting_type slug.
	 * @return int Rows removed across pulse and legacy tables.
	 */
	public static function delete_item_votes( $item_id, $setting_type ) {
			global $wpdb;

			$deleted   = 0;
			$item_id   = absint( $item_id );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $setting_type );

			if ( WP_Ulike_Pulse_Schema::table_exists() ) {
				$deleted += (int) $wpdb->delete(
					self::table(),
					array(
						'item_id'   => $item_id,
						'item_type' => $item_type,
					),
					array( '%d', '%s' )
				);
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$table  = esc_sql( $source['table'] );
				$column = esc_sql( $source['column'] );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$deleted += (int) $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `{$table}` WHERE `{$column}` = %d",
						$item_id
					)
				);
			}

			return $deleted;
		}

		/**
		 * @param array<string,mixed> $payload Raw payload.
		 * @param bool                $distinct Distinct logging mode.
		 * @return array<string,mixed>|null
		 */
		private static function normalize_payload( array $payload, $distinct ) {
			$item_id = isset( $payload['item_id'] ) ? absint( $payload['item_id'] ) : 0;
			if ( ! $item_id ) {
				return null;
			}

			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type(
				isset( $payload['item_type'] ) ? $payload['item_type'] : 'post'
			);

			if ( isset( $payload['legacy_status'] ) ) {
				$mapped = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $payload['legacy_status'] );
			} else {
				$mapped = array(
					'engagement_key' => isset( $payload['engagement_key'] ) ? sanitize_key( $payload['engagement_key'] ) : WP_Ulike_Pulse_Vote_Map::KEY_LIKE,
					'status'         => isset( $payload['status'] ) ? sanitize_key( $payload['status'] ) : WP_Ulike_Pulse_Vote_Map::ROW_ACTIVE,
				);
			}

			$user_id = isset( $payload['user_id'] ) ? (string) $payload['user_id'] : '0';
			$kind    = isset( $payload['engagement_kind'] ) ? sanitize_key( $payload['engagement_kind'] ) : WP_Ulike_Pulse_Registry::KIND_VOTE;

		// Distinct rows get a kind-scoped dedupe token enforcing one-row-per-
		// user+item+key. Append (multi-vote) rows get an explicit NULL —
		// wpdb::insert() emits literal NULL for null values (WP 4.4+), and the
		// idx_dedupe UNIQUE index allows multiple NULLs, so append rows never
		// collide. Explicit NULL is used (rather than omitting the column) so
		// the insert does not depend on the column DEFAULT being NULL.
	$dedupe = null;
	if ( $distinct || ! empty( $payload['is_distinct'] ) ) {
		$dedupe = WP_Ulike_Pulse_Schema::dedupe_token(
			$item_id,
			$item_type,
			$user_id,
			$kind,
			$mapped['engagement_key'],
			isset( $payload['fingerprint'] ) ? (string) $payload['fingerprint'] : ''
		);
	}

		$data = array(
			'item_id'          => $item_id,
			'item_type'        => $item_type,
			'engagement_kind'  => $kind,
			'engagement_key'   => $mapped['engagement_key'],
			'value'            => isset( $payload['value'] ) ? absint( $payload['value'] ) : null,
			'status'           => $mapped['status'],
			'date_time'        => isset( $payload['date_time'] ) ? $payload['date_time'] : current_time( 'mysql', true ),
			'ip'               => isset( $payload['ip'] ) ? (string) $payload['ip'] : '',
			'user_id'          => $user_id,
			'fingerprint'      => isset( $payload['fingerprint'] ) ? (string) $payload['fingerprint'] : null,
			'country_code'     => isset( $payload['country_code'] ) ? substr( sanitize_text_field( $payload['country_code'] ), 0, 2 ) : null,
			'device'           => isset( $payload['device'] ) ? substr( sanitize_text_field( $payload['device'] ), 0, 50 ) : null,
			'os'               => isset( $payload['os'] ) ? substr( sanitize_text_field( $payload['os'] ), 0, 50 ) : null,
			'browser'          => isset( $payload['browser'] ) ? substr( sanitize_text_field( $payload['browser'] ), 0, 50 ) : null,
			'dedupe_token'     => $dedupe,
		);

		$format = array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		return array(
			'data'          => $data,
			'format'        => $format,
			'dedupe_token'  => $dedupe,
			'legacy_status' => isset( $payload['legacy_status'] ) ? $payload['legacy_status'] : WP_Ulike_Pulse_Vote_Map::row_to_legacy( $mapped['engagement_key'], $mapped['status'] ),
		);
	}

		/**
		 * @return string
		 */
		private static function table() {
			return WP_Ulike_Pulse_Schema::table();
		}

		/**
		 * @param int                   $id Row ID.
		 * @param array<string,mixed>   $payload Original payload.
		 * @param string                $legacy_status Hook status.
		 * @return void
		 */
		private static function fire_inserted( $id, array $payload, $legacy_status ) {
			do_action(
				'wp_ulike_data_inserted',
				array(
					'id'             => $id,
					'item_id'        => $payload['item_id'],
					'table'          => self::table(),
					'related_column' => 'item_id',
					'type'           => isset( $payload['setting_type'] ) ? $payload['setting_type'] : $payload['item_type'],
					'user_id'        => $payload['user_id'],
					'status'         => $legacy_status,
					'ip'             => isset( $payload['ip'] ) ? $payload['ip'] : '',
					'storage'        => 'pulse',
				)
			);
		}

		/**
		 * @param int                   $id Row ID.
		 * @param array<string,mixed>   $payload Original payload.
		 * @param string                $legacy_status Hook status.
		 * @return void
		 */
		private static function fire_updated( $id, array $payload, $legacy_status ) {
			do_action(
				'wp_ulike_data_updated',
				array(
					'id'             => $id,
					'item_id'        => $payload['item_id'],
					'table'          => self::table(),
					'related_column' => 'item_id',
					'type'           => isset( $payload['setting_type'] ) ? $payload['setting_type'] : $payload['item_type'],
					'user_id'        => $payload['user_id'],
					'status'         => $legacy_status,
					'ip'             => isset( $payload['ip'] ) ? $payload['ip'] : '',
					'storage'        => 'pulse',
				)
			);
		}
	}
}
