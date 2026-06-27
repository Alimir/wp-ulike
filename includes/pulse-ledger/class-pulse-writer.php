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
			self::fire_inserted( $id, $payload, $row['legacy_status'] );
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
				$updated = $wpdb->update(
					self::table(),
					array(
						'engagement_key' => $data['engagement_key'],
						'status'         => $data['status'],
						'date_time'      => $data['date_time'],
						'ip'             => $data['ip'],
						'fingerprint'    => $data['fingerprint'],
					),
					array( 'id' => $existing_id ),
					array( '%s', '%s', '%s', '%s', '%s' ),
					array( '%d' )
				);

				if ( false === $updated ) {
					return false;
				}

				self::fire_updated( $existing_id, $payload, $row['legacy_status'] );
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
				'is_distinct'    => $is_distinct,
			);

			if ( $is_distinct ) {
				return self::upsert( $payload );
			}

			return self::insert( $payload );
		}

		/**
		 * Delete distinct-mode vote row (legacy deleteData equivalent).
		 *
		 * @param int|string $item_id   Item ID.
		 * @param string     $item_type Item type.
		 * @param string     $user_id   User ID.
		 * @return int|false Rows affected.
		 */
		public static function delete( $item_id, $item_type, $user_id ) {
			global $wpdb;

			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$token     = WP_Ulike_Pulse_Schema::dedupe_token( $item_id, $item_type, $user_id );

			if ( ! $token ) {
				return false;
			}

			$deleted = $wpdb->delete(
				self::table(),
				array( 'dedupe_token' => $token ),
				array( '%s' )
			);

			if ( $deleted ) {
				do_action(
					'wp_ulike_delete_vote_data',
					array(
						'item_id'   => $item_id,
						'item_type' => $item_type,
						'user_id'   => $user_id,
						'storage'   => 'pulse',
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

			$dedupe = null;
			if ( $distinct || ! empty( $payload['is_distinct'] ) ) {
				$dedupe = WP_Ulike_Pulse_Schema::dedupe_token(
					$item_id,
					$item_type,
					$user_id,
					$kind,
					$mapped['engagement_key']
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

			return array(
				'data'          => $data,
				'format'        => array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
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
