<?php
/**
 * Pulse Ledger — legacy vote strings ↔ pulse row fields.
 *
 * Pulse rows store engagement_key (like|dislike) + status (active|removed).
 * Hooks, meta, and Pro JS still use like|unlike|dislike|undislike.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Vote_Map' ) ) {

	final class WP_Ulike_Pulse_Vote_Map {

		const ROW_ACTIVE  = 'active';
		const ROW_REMOVED = 'removed';

		const KEY_LIKE    = 'like';
		const KEY_DISLIKE = 'dislike';

		const ACTION_LIKE      = 'like';
		const ACTION_UNLIKE    = 'unlike';
		const ACTION_DISLIKE   = 'dislike';
		const ACTION_UNDISLIKE = 'undislike';

		/**
		 * @param string $legacy_action like|unlike|dislike|undislike.
		 * @return array{engagement_key:string,status:string}
		 */
		public static function legacy_to_row( $legacy_action ) {
			$legacy_action = sanitize_key( (string) $legacy_action );

			switch ( $legacy_action ) {
				case self::ACTION_DISLIKE:
					return array(
						'engagement_key' => self::KEY_DISLIKE,
						'status'         => self::ROW_ACTIVE,
					);
				case self::ACTION_UNDISLIKE:
					return array(
						'engagement_key' => self::KEY_DISLIKE,
						'status'         => self::ROW_REMOVED,
					);
				case self::ACTION_UNLIKE:
					return array(
						'engagement_key' => self::KEY_LIKE,
						'status'         => self::ROW_REMOVED,
					);
				case self::ACTION_LIKE:
				default:
					return array(
						'engagement_key' => self::KEY_LIKE,
						'status'         => self::ROW_ACTIVE,
					);
			}
		}

		/**
		 * @param string $engagement_key like|dislike.
		 * @param string $row_status     active|removed.
		 * @return string Legacy action for hooks/meta.
		 */
		public static function row_to_legacy( $engagement_key, $row_status ) {
			$key    = sanitize_key( (string) $engagement_key );
			$active = self::ROW_ACTIVE === sanitize_key( (string) $row_status );

			if ( self::KEY_DISLIKE === $key ) {
				return $active ? self::ACTION_DISLIKE : self::ACTION_UNDISLIKE;
			}

			return $active ? self::ACTION_LIKE : self::ACTION_UNLIKE;
		}

		/**
		 * @param string $legacy_action Legacy action.
		 * @return bool
		 */
		public static function is_remove_action( $legacy_action ) {
			$legacy_action = sanitize_key( (string) $legacy_action );
			return in_array( $legacy_action, array( self::ACTION_UNLIKE, self::ACTION_UNDISLIKE ), true );
		}

		/**
		 * Active legacy statuses used in COUNT queries.
		 *
		 * @param string|array $status Requested filter.
		 * @return array
		 */
		public static function normalize_status_filter( $status, $default = array( self::ACTION_LIKE, self::ACTION_DISLIKE ) ) {
			$allowed = array(
				self::ACTION_LIKE,
				self::ACTION_DISLIKE,
				self::ACTION_UNLIKE,
				self::ACTION_UNDISLIKE,
			);

			if ( is_array( $status ) ) {
				$status = array_values( array_intersect( array_map( 'strval', $status ), $allowed ) );
				return ! empty( $status ) ? $status : (array) $default;
			}

			if ( is_string( $status ) && in_array( $status, $allowed, true ) ) {
				return array( $status );
			}

			return (array) $default;
		}

		/**
		 * Map legacy status strings to pulse WHERE fragments.
		 *
		 * @param string|array $statuses Legacy statuses.
		 * @return array{keys:array,active_only:bool,include_removed:bool}
		 */
		public static function pulse_filter_from_legacy_statuses( $statuses ) {
			$statuses = self::normalize_status_filter( $statuses );
			$keys     = array();
			$active   = false;
			$removed  = false;

			foreach ( $statuses as $status ) {
				$row = self::legacy_to_row( $status );
				$keys[] = $row['engagement_key'];
				if ( self::ROW_ACTIVE === $row['status'] ) {
					$active = true;
				} else {
					$removed = true;
				}
			}

			return array(
				'keys'            => array_values( array_unique( $keys ) ),
				'active_only'     => $active && ! $removed,
				'include_removed' => $removed && ! $active,
			);
		}
	}
}
