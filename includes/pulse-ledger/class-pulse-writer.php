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
		 * Upsert distinct-mode row (one row per user+item+kind).
		 *
		 * Uses INSERT … ON DUPLICATE KEY UPDATE so concurrent distinct votes
		 * cannot lose the second writer on a unique dedupe_token race.
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

			$values = array(
				self::sql_literal( $data['item_id'], 'int' ),
				self::sql_literal( $data['item_type'], 'string' ),
				self::sql_literal( $data['engagement_kind'], 'string' ),
				self::sql_literal( $data['engagement_key'], 'string' ),
				self::sql_literal( $data['value'], 'int' ),
				self::sql_literal( $data['status'], 'string' ),
				self::sql_literal( $data['date_time'], 'string' ),
				self::sql_literal( $data['ip'], 'string' ),
				self::sql_literal( $data['user_id'], 'string' ),
				self::sql_literal( $data['fingerprint'], 'string' ),
				self::sql_literal( $data['country_code'], 'string' ),
				self::sql_literal( $data['device'], 'string' ),
				self::sql_literal( $data['os'], 'string' ),
				self::sql_literal( $data['browser'], 'string' ),
				self::sql_literal( $data['dedupe_token'], 'binary' ),
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- literals escaped via sql_literal().
			$sql = "INSERT INTO `{$table}` (
					item_id, item_type, engagement_kind, engagement_key, value, status,
					date_time, ip, user_id, fingerprint, country_code, device, os, browser, dedupe_token
				) VALUES ( " . implode( ', ', $values ) . " )
				ON DUPLICATE KEY UPDATE
					engagement_key = VALUES(engagement_key),
					status = VALUES(status),
					date_time = VALUES(date_time),
					ip = VALUES(ip),
					fingerprint = VALUES(fingerprint),
					value = VALUES(value),
					country_code = IF(VALUES(country_code) IS NULL, country_code, VALUES(country_code)),
					device = IF(VALUES(device) IS NULL, device, VALUES(device)),
					os = IF(VALUES(os) IS NULL, os, VALUES(os)),
					browser = IF(VALUES(browser) IS NULL, browser, VALUES(browser)),
					id = LAST_INSERT_ID(id)";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->query( $sql );
			if ( false === $result ) {
				return false;
			}

			$id = (int) $wpdb->insert_id;
			if ( ! $id ) {
				return false;
			}

			// Drop legacy key-scoped sibling rows (pre kind-scoped token) so
			// like↔dislike / emoji switches cannot leave two active rows.
			$retired = self::retire_sibling_distinct_rows( $id, $data );

			if ( ! self::$migrating ) {
				// MySQL: 1 = inserted, 2 = updated existing unique row.
				// Retiring siblings means this is a logical update of an older
				// key-scoped row even when the new token caused an INSERT.
				if ( 1 === (int) $result && $retired < 1 ) {
					self::fire_inserted( $id, $payload, $row['legacy_status'] );
				} else {
					self::fire_updated( $id, $payload, $row['legacy_status'] );
				}
			}

			return $id;
		}

		/**
		 * Update an existing pulse row by primary key (Pro emoji/star toggles).
		 *
		 * Emoji keeps one row per user+item+kind and may change engagement_key;
		 * key-scoped upsert cannot express that switch safely.
		 *
		 * @param int                 $id      Row ID.
		 * @param array<string,mixed> $payload Engagement payload.
		 * @return int|false
		 */
		public static function update_by_id( $id, array $payload ) {
			global $wpdb;

			$id  = absint( $id );
			$row = self::normalize_payload( $payload, ! empty( $payload['is_distinct'] ) );
			if ( ! $id || ! $row ) {
				return false;
			}

			$data          = $row['data'];
			$update_data   = array(
				'engagement_key' => $data['engagement_key'],
				'status'         => $data['status'],
				'date_time'      => $data['date_time'],
				'ip'             => $data['ip'],
				'fingerprint'    => $data['fingerprint'],
				'value'          => $data['value'],
			);
			$update_format = array( '%s', '%s', '%s', '%s', '%s', '%d' );

			if ( null !== $data['dedupe_token'] ) {
				$update_data['dedupe_token'] = $data['dedupe_token'];
				$update_format[]            = '%s';
			}

			foreach ( array( 'country_code', 'device', 'os', 'browser' ) as $geo_col ) {
				if ( null !== $data[ $geo_col ] ) {
					$update_data[ $geo_col ] = $data[ $geo_col ];
					$update_format[]         = '%s';
				}
			}

			$updated = $wpdb->update(
				self::table(),
				$update_data,
				array( 'id' => $id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $updated ) {
				return false;
			}

			if ( ! self::$migrating ) {
				self::fire_updated( $id, $payload, $row['legacy_status'] );
			}

			return $id;
		}

		/**
		 * Escape a value for raw SQL (supports NULL).
		 *
		 * @param mixed  $value Value.
		 * @param string $type  int|string|binary.
		 * @return string
		 */
		private static function sql_literal( $value, $type ) {
			global $wpdb;

			if ( null === $value ) {
				return 'NULL';
			}

			if ( 'int' === $type ) {
				return (string) (int) $value;
			}

			if ( 'binary' === $type ) {
				return "X'" . bin2hex( (string) $value ) . "'";
			}

			return $wpdb->prepare( '%s', (string) $value );
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

		// Keep migrated historical rows out of the dual "live" pulse slice.
		// Legacy date_time may be site-local while dual_since is UTC; clamping
		// prevents those rows from also matching date_time >= dual_since and
		// double-counting in merged reads.
		$since = WP_Ulike_Pulse_Config::dual_since();
		if ( $since && isset( $payload['date_time'] ) && $payload['date_time'] >= $since ) {
			$payload['date_time'] = gmdate( 'Y-m-d H:i:s', strtotime( $since . ' UTC' ) - 1 );
		}

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
	 * Delete classic vote rows for an item (keeps emoji/star intact).
	 *
	 * @param int    $item_id       Item ID.
	 * @param string $setting_type  wp_ulike_setting_type slug.
	 * @return int Rows removed across pulse and legacy tables.
	 */
	public static function delete_item_votes( $item_id, $setting_type ) {
		return self::delete_item_rows( $item_id, $setting_type, WP_Ulike_Pulse_Registry::KIND_VOTE );
	}

	/**
	 * Delete all pulse engagement rows for an item (votes + emoji + star) and legacy votes.
	 *
	 * Used on content deletion so Pro engagements are not left orphaned.
	 *
	 * @param int    $item_id      Item ID.
	 * @param string $setting_type Setting type slug.
	 * @return int Rows removed.
	 */
	public static function delete_item_all( $item_id, $setting_type ) {
		return self::delete_item_rows( $item_id, $setting_type, null );
	}

	/**
	 * @param int         $item_id       Item ID.
	 * @param string      $setting_type  Setting type slug.
	 * @param string|null $kind          Pulse engagement_kind or null for all kinds.
	 * @return int
	 */
	private static function delete_item_rows( $item_id, $setting_type, $kind ) {
			global $wpdb;

			$deleted   = 0;
			$item_id   = absint( $item_id );
			$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $setting_type );

			if ( WP_Ulike_Pulse_Schema::table_exists() ) {
				$where = array(
					'item_id'   => $item_id,
					'item_type' => $item_type,
				);
				$format = array( '%d', '%s' );

				if ( null !== $kind ) {
					$where['engagement_kind'] = $kind;
					$format[]                 = '%s';
				}

				$deleted += (int) $wpdb->delete( self::table(), $where, $format );
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

		// Distinct rows get a kind-scoped dedupe token (one row per
		// user+item+kind). Append (multi-vote) rows get an explicit NULL —
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
		 * Remove other distinct rows for the same identity+item+kind.
		 *
		 * Covers sites that still have key-scoped tokens (like + dislike both
		 * active) after the kind-scoped token change.
		 *
		 * @param int                 $keep_id Winning row ID.
		 * @param array<string,mixed> $data    Normalized row data.
		 * @return int Rows deleted.
		 */
		private static function retire_sibling_distinct_rows( $keep_id, array $data ) {
			global $wpdb;

			$keep_id = absint( $keep_id );
			if ( ! $keep_id ) {
				return 0;
			}

			$table = esc_sql( self::table() );
			$user  = (string) $data['user_id'];

			if ( '' !== $user && '0' !== $user ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$deleted = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `{$table}`
						WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
						AND user_id = %s AND id != %d",
						(int) $data['item_id'],
						$data['item_type'],
						$data['engagement_kind'],
						$user,
						$keep_id
					)
				);
				return false === $deleted ? 0 : (int) $deleted;
			}

			$fingerprint = isset( $data['fingerprint'] ) ? (string) $data['fingerprint'] : '';
			if ( '' === $fingerprint ) {
				return 0;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `{$table}`
					WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
					AND fingerprint = %s AND user_id IN ('0','') AND id != %d",
					(int) $data['item_id'],
					$data['item_type'],
					$data['engagement_kind'],
					$fingerprint,
					$keep_id
				)
			);
			return false === $deleted ? 0 : (int) $deleted;
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
			$kind = isset( $payload['engagement_kind'] )
				? sanitize_key( $payload['engagement_kind'] )
				: WP_Ulike_Pulse_Registry::KIND_VOTE;

			// Non-vote rows are written for Pro; Pro owns engagement_* hooks.
			if ( WP_Ulike_Pulse_Registry::KIND_VOTE !== $kind ) {
				return;
			}

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
			$kind = isset( $payload['engagement_kind'] )
				? sanitize_key( $payload['engagement_kind'] )
				: WP_Ulike_Pulse_Registry::KIND_VOTE;

			// Non-vote rows are written for Pro; Pro owns engagement_* hooks.
			if ( WP_Ulike_Pulse_Registry::KIND_VOTE !== $kind ) {
				return;
			}

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
