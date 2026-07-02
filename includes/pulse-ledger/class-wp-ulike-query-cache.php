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

		const VERSION_OPTION = 'wp_ulike_pulse_cache_ver';
		const STATS_ITEM_ID    = 1;
		const STATS_META_GROUP = 'statistics';

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
			}
		}

		/**
		 * Persistent statistics meta (survives object-cache flush).
		 *
		 * @param string $logical_key Short stable identifier.
		 * @return mixed
		 */
		public static function get_statistics_meta( $logical_key ) {
			return wp_ulike_get_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, self::key( $logical_key ), true );
		}

		/**
		 * @param string $logical_key Short stable identifier.
		 * @param mixed  $value       Value to store.
		 * @return int|bool
		 */
		public static function set_statistics_meta( $logical_key, $value ) {
			return wp_ulike_update_meta_data( self::STATS_ITEM_ID, self::STATS_META_GROUP, self::key( $logical_key ), $value );
		}
	}
}
