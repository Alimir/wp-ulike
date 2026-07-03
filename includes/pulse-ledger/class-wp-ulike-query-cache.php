<?php
/**
 * Versioned object cache for vote/query results.
 *
 * Not for item meta rows (wp_ulike_{type}_meta) or third-party page-cache purge.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Query_Cache' ) ) {

	final class WP_Ulike_Query_Cache {

		const VERSION_OPTION            = 'wp_ulike_pulse_cache_ver';
		const STATS_ITEM_ID             = 1;
		const STATS_META_GROUP          = 'statistics';
		const ADMIN_NEW_VOTES_KEY       = 'calculate_new_votes';
		const STATS_GROUP               = 'wp-ulike-stats';

		const TTL_DEFAULT     = 300;
		const TTL_PEAK_HOURS  = 900;
		const TTL_FINGERPRINT = 10;

		/**
		 * @return string
		 */
		public static function group() {
			return WP_ULIKE_SLUG;
		}

		/**
		 * Object-cache group for admin statistics aggregates.
		 *
		 * @return string
		 */
		public static function stats_group() {
			return self::STATS_GROUP;
		}

		/**
		 * @return int
		 */
		public static function version() {
			return (int) get_option( self::VERSION_OPTION, 1 );
		}

		/**
		 * Build a versioned, mode-scoped cache key.
		 *
		 * @param string $logical_key Short stable identifier.
		 * @return string
		 */
		public static function key( $logical_key ) {
			$logical_key = sanitize_key( (string) $logical_key );
			$scope       = wp_ulike_pulse_mode() . '_' . wp_ulike_pulse_read_mode();

			if ( WP_Ulike_Pulse_Config::MODE_DUAL === wp_ulike_pulse_mode() ) {
				$since = WP_Ulike_Pulse_Config::dual_since();
				if ( $since ) {
					$scope .= '_' . substr( md5( $since ), 0, 8 );
				}
			}

			return 'pv' . self::version() . '_' . $scope . '_' . $logical_key;
		}

		/**
		 * @param string $logical_key Short stable identifier.
		 * @return mixed|false
		 */
		public static function get( $logical_key ) {
			return wp_cache_get( self::key( $logical_key ), self::group() );
		}

		/**
		 * @param string $logical_key Short stable identifier.
		 * @param mixed  $value       Value to store.
		 * @param int    $ttl         Expiration in seconds.
		 * @return bool
		 */
		public static function set( $logical_key, $value, $ttl = self::TTL_DEFAULT ) {
			return wp_cache_set( self::key( $logical_key ), $value, self::group(), (int) $ttl );
		}

		/**
		 * @param string   $logical_key Short stable identifier.
		 * @param callable $callback    Produces the value on cache miss.
		 * @param int      $ttl         Expiration in seconds.
		 * @param bool     $cache_empty When false, skip set() for empty non-numeric values.
		 * @return mixed
		 */
		public static function remember( $logical_key, $callback, $ttl = self::TTL_DEFAULT, $cache_empty = true ) {
			$cached = self::get( $logical_key );

			if ( false !== $cached ) {
				return $cached;
			}

			$value = call_user_func( $callback );

			if ( $cache_empty || ! empty( $value ) || is_numeric( $value ) ) {
				self::set( $logical_key, $value, $ttl );
			}

			return $value;
		}

		/**
		 * @param string $logical_key Short stable identifier.
		 * @return mixed|false
		 */
		public static function get_stats( $logical_key ) {
			return wp_cache_get( self::key( $logical_key ), self::stats_group() );
		}

		/**
		 * @param string $logical_key Short stable identifier.
		 * @param mixed  $value       Value to store.
		 * @param int    $ttl         Expiration in seconds.
		 * @return bool
		 */
		public static function set_stats( $logical_key, $value, $ttl = self::TTL_DEFAULT ) {
			return wp_cache_set( self::key( $logical_key ), $value, self::stats_group(), (int) $ttl );
		}

		/**
		 * @param string   $logical_key Short stable identifier.
		 * @param callable $callback    Produces the value on cache miss.
		 * @param int      $ttl         Expiration in seconds.
		 * @param bool     $cache_empty When false, skip set() for empty non-numeric values.
		 * @return mixed
		 */
		public static function remember_stats( $logical_key, $callback, $ttl = self::TTL_DEFAULT, $cache_empty = true ) {
			$cached = self::get_stats( $logical_key );

			if ( false !== $cached ) {
				return $cached;
			}

			$value = call_user_func( $callback );

			if ( $cache_empty || ! empty( $value ) || is_numeric( $value ) ) {
				self::set_stats( $logical_key, $value, $ttl );
			}

			return $value;
		}

		/**
		 * @return bool
		 */
		public static function should_defer_bump() {
			if ( class_exists( 'WP_Ulike_Pulse_Writer' ) && WP_Ulike_Pulse_Writer::is_migrating() ) {
				return true;
			}

			return WP_Ulike_Pulse_Config::migration_running();
		}

		/**
		 * Invalidate query caches after a live vote change.
		 *
		 * All-time statistics meta is adjusted incrementally on write — not cleared here.
		 *
		 * @return void
		 */
		public static function bump() {
			if ( self::should_defer_bump() ) {
				return;
			}

			update_option( self::VERSION_OPTION, self::version() + 1, false );
		}

		/**
		 * Full invalidation on sync completion or storage mode cutover.
		 *
		 * @return void
		 */
		public static function flush() {
			update_option( self::VERSION_OPTION, self::version() + 1, false );

			if ( function_exists( 'wp_cache_flush_group' ) ) {
				wp_cache_flush_group( self::group() );
				wp_cache_flush_group( self::stats_group() );
			}

			self::purge_stale_statistics_meta();
			self::rebuild_statistics_meta();
		}

		/**
		 * Recompute persisted all-time statistics from the active read path.
		 *
		 * Used after migration/cutover and Help → Refresh statistics cache.
		 * Respects legacy, dual (merged), and pulse modes via Pulse_Query.
		 *
		 * @return void
		 */
		public static function rebuild_statistics_meta() {
			if ( ! class_exists( 'WP_Ulike_Pulse_Query' ) || ! WP_Ulike_Pulse_Query::available() ) {
				return;
			}

			self::set_statistics_meta(
				'count_logs_period_all',
				WP_Ulike_Pulse_Query::count_logs_for_mode( 'all' )
			);

			foreach ( WP_Ulike_Pulse_Registry::stats_table_map() as $item_type ) {
				self::set_statistics_meta(
					sprintf( 'count_logs_for_%s_in_all_daterange', $item_type ),
					WP_Ulike_Pulse_Query::count_logs_for_type( $item_type, 'all' )
				);
			}
		}

		/**
		 * Increment or decrement persisted all-time statistics (O(1) on vote writes).
		 *
		 * @param int         $delta     Rows added (+) or removed (−).
		 * @param string|null $item_type Canonical item type (post, comment, …).
		 * @return void
		 */
		public static function adjust_statistics_meta( $delta, $item_type = null ) {
			if ( self::should_defer_bump() || 0 === (int) $delta ) {
				return;
			}

			$delta = (int) $delta;

			$total_key = 'count_logs_period_all';
			$total     = self::get_statistics_meta( $total_key );

			if ( ! is_numeric( $total ) ) {
				return;
			}

			self::set_statistics_meta( $total_key, max( 0, (int) $total + $delta ) );

			if ( empty( $item_type ) ) {
				return;
			}

			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$type_key  = sprintf( 'count_logs_for_%s_in_all_daterange', $item_type );
			$type_val  = self::get_statistics_meta( $type_key );

			if ( is_numeric( $type_val ) ) {
				self::set_statistics_meta( $type_key, max( 0, (int) $type_val + $delta ) );
			}
		}

		/**
		 * Invalidate statistics object cache and rebuild persisted all-time totals.
		 *
		 * @return void
		 */
		public static function flush_stats() {
			update_option( self::VERSION_OPTION, self::version() + 1, false );
			self::purge_stale_statistics_meta();
			self::rebuild_statistics_meta();
		}

		/**
		 * Drop stale statistics aggregate rows (legacy versioned keys, recount targets).
		 *
		 * @return void
		 */
		public static function purge_stale_statistics_meta() {
			if ( ! class_exists( 'WP_Ulike_Meta_Schema' ) || ! WP_Ulike_Meta_Schema::table_exists() ) {
				return;
			}

			global $wpdb;

			$table = esc_sql( WP_Ulike_Meta_Schema::table() );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `{$table}`
					WHERE `meta_group` = %s AND `item_id` = %d AND `meta_key` != %s
					AND (
						`meta_key` LIKE %s
						OR `meta_key` LIKE %s
					)",
					self::STATS_META_GROUP,
					self::STATS_ITEM_ID,
					self::ADMIN_NEW_VOTES_KEY,
					'pv%',
					$wpdb->esc_like( 'count_logs_' ) . '%'
				)
			);
		}

		/**
		 * Persistent statistics meta (survives object-cache flush).
		 *
		 * Uses stable logical keys — not versioned — so one row per aggregate.
		 *
		 * @param string $logical_key Short stable identifier.
		 * @return mixed
		 */
		public static function get_statistics_meta( $logical_key ) {
			return wp_ulike_get_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, sanitize_key( (string) $logical_key ), true );
		}

		/**
		 * @param string $logical_key Short stable identifier.
		 * @param mixed  $value       Value to store.
		 * @return int|bool
		 */
		public static function set_statistics_meta( $logical_key, $value ) {
			return wp_ulike_update_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, sanitize_key( (string) $logical_key ), $value );
		}

		/**
		 * Admin menu badge: votes since last statistics visit (fixed key, not versioned).
		 *
		 * @return void
		 */
		public static function increment_admin_new_votes() {
			if ( ! apply_filters( 'wp_ulike_display_admin_new_likes', true ) ) {
				return;
			}

			if ( self::should_defer_bump() ) {
				return;
			}

			$current = wp_ulike_get_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, self::ADMIN_NEW_VOTES_KEY, true );
			if ( ! is_numeric( $current ) ) {
				$current = 0;
			}

			wp_ulike_update_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, self::ADMIN_NEW_VOTES_KEY, (int) $current + 1 );
		}

		/**
		 * @return void
		 */
		public static function reset_admin_new_votes() {
			wp_ulike_update_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, self::ADMIN_NEW_VOTES_KEY, 0 );
		}
	}
}
