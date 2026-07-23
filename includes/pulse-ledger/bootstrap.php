<?php
/**
 * Pulse Ledger bootstrap.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-pulse-vote-map.php';
require_once __DIR__ . '/class-pulse-registry.php';
require_once __DIR__ . '/class-pulse-config.php';
require_once __DIR__ . '/class-wp-ulike-query-cache.php';
require_once __DIR__ . '/class-pulse-schema.php';
require_once __DIR__ . '/class-pulse-writer.php';
require_once __DIR__ . '/class-pulse-reader.php';
require_once __DIR__ . '/class-pulse-query.php';
require_once __DIR__ . '/class-pulse-sync.php';
require_once __DIR__ . '/class-pulse-sync-scheduler.php';
require_once __DIR__ . '/class-pulse-legacy-cleanup.php';
require_once __DIR__ . '/class-pulse-log-bridge.php';
require_once __DIR__ . '/class-pulse-cli.php';

if ( is_admin() && file_exists( __DIR__ . '/admin/class-pulse-admin.php' ) ) {
	require_once __DIR__ . '/admin/class-pulse-admin.php';
}

WP_Ulike_Pulse_Sync_Scheduler::init();
WP_Ulike_Pulse_CLI::register();

add_action(
	'plugins_loaded',
	static function () {
		if ( function_exists( 'wp_cache_add_global_groups' ) ) {
			wp_cache_add_global_groups(
				array(
					WP_ULIKE_SLUG,
					WP_Ulike_Query_Cache::STATS_GROUP,
				)
			);
		}
	},
	1
);

add_action( 'admin_post_wp_ulike_flush_stats_cache', 'wp_ulike_admin_post_flush_stats_cache' );

if ( is_admin() && class_exists( 'WP_Ulike_Pulse_Admin' ) ) {
	WP_Ulike_Pulse_Admin::init();
}

add_action( 'wp_ulike_data_inserted', 'wp_ulike_track_admin_new_vote', 5, 1 );
add_action( 'wp_ulike_data_updated', 'wp_ulike_track_admin_new_vote', 5, 1 );
add_action( 'wp_ulike_data_inserted', 'wp_ulike_adjust_statistics_on_vote_insert', 8, 1 );
add_action( 'wp_ulike_delete_vote_data', 'wp_ulike_adjust_statistics_on_vote_delete', 8, 4 );
add_action( 'wp_ulike_data_inserted', array( 'WP_Ulike_Query_Cache', 'bump' ), 10, 1 );
add_action( 'wp_ulike_data_updated', array( 'WP_Ulike_Query_Cache', 'bump' ), 10, 1 );
add_action( 'wp_ulike_delete_vote_data', array( 'WP_Ulike_Query_Cache', 'bump' ), 10, 4 );

/**
 * Bump admin statistics badge on active vote inserts/updates (not unlikes).
 *
 * Distinct-mode re-likes fire wp_ulike_data_updated (same row) — still count
 * as new admin activity so the menu badge stays useful.
 *
 * @param array<string,mixed> $args Hook payload.
 * @return void
 */
function wp_ulike_track_admin_new_vote( $args ) {
	if ( empty( $args['status'] ) || false !== strpos( (string) $args['status'], 'un' ) ) {
		return;
	}

	WP_Ulike_Query_Cache::increment_admin_new_votes();
}

/**
 * +1 site statistics when a new vote log row is inserted.
 *
 * Distinct-mode status toggles fire wp_ulike_data_updated instead (row count unchanged).
 *
 * @param array<string,mixed> $args Hook payload.
 * @return void
 */
function wp_ulike_adjust_statistics_on_vote_insert( $args ) {
	if ( ! is_array( $args ) || empty( $args['type'] ) ) {
		return;
	}

	$item_type = WP_Ulike_Pulse_Registry::from_setting_type( $args['type'] );
	WP_Ulike_Query_Cache::adjust_statistics_meta( 1, $item_type );
}

/**
 * −N site statistics when vote rows are removed.
 *
 * @param int|string          $arg1 Item ID or pulse payload array.
 * @param string|array|null   $arg2 Setting type slug or unused.
 * @param mixed               $arg3 Unused settings object.
 * @param int|null            $arg4 Rows deleted (bulk item cleanup).
 * @return void
 */
function wp_ulike_adjust_statistics_on_vote_delete( $arg1, $arg2 = null, $arg3 = null, $arg4 = null ) {
	if ( is_array( $arg1 ) ) {
		if ( empty( $arg1['item_type'] ) ) {
			return;
		}

		WP_Ulike_Query_Cache::adjust_statistics_meta(
			-1,
			WP_Ulike_Pulse_Registry::normalize_item_type( $arg1['item_type'] )
		);
		return;
	}

	$deleted_count = is_numeric( $arg4 ) ? (int) $arg4 : 0;
	if ( $deleted_count <= 0 || ! is_string( $arg2 ) ) {
		return;
	}

	WP_Ulike_Query_Cache::adjust_statistics_meta(
		-$deleted_count,
		WP_Ulike_Pulse_Registry::from_setting_type( $arg2 )
	);
}

/**
 * Current query-cache generation (incremented on live writes and cutover).
 *
 * @return int
 */
function wp_ulike_pulse_cache_version() {
	return WP_Ulike_Query_Cache::version();
}

/**
 * Build a versioned, mode-scoped object-cache key for vote query results.
 *
 * @param string $key Logical cache key.
 * @return string
 */
function wp_ulike_query_cache_key( $key ) {
	return WP_Ulike_Query_Cache::key( $key );
}

/**
 * Whether cache bumps should be deferred (migration bulk import).
 *
 * @return bool
 */
function wp_ulike_pulse_defer_cache_bump() {
	return WP_Ulike_Query_Cache::should_defer_bump();
}

/**
 * Invalidate query caches after a live vote change.
 *
 * @return void
 */
function wp_ulike_pulse_bump_cache() {
	WP_Ulike_Query_Cache::bump();
}

/**
 * Full cache invalidation on sync completion or storage mode cutover.
 *
 * @return void
 */
function wp_ulike_pulse_flush_cache() {
	WP_Ulike_Query_Cache::flush();
}

/**
 * Invalidate statistics object cache only (admin aggregates).
 *
 * @return void
 */
function wp_ulike_flush_stats_cache() {
	WP_Ulike_Query_Cache::flush_stats();
}

/**
 * Admin-post handler for Help → Refresh statistics cache.
 *
 * Registered here (not only in Overview::init) so admin-post.php always has the hook.
 *
 * @return void
 */
function wp_ulike_admin_post_flush_stats_cache() {
	if ( ! class_exists( 'WP_Ulike_Overview' ) ) {
		wp_die( esc_html__( 'Plugin not loaded.', 'wp-ulike' ), '', array( 'response' => 500 ) );
	}

	WP_Ulike_Overview::handle_flush_stats_cache();
}

/**
 * @return string legacy|dual|pulse
 */
function wp_ulike_pulse_mode() {
	return WP_Ulike_Pulse_Config::mode();
}

/**
 * @return string legacy|merged|pulse
 */
function wp_ulike_pulse_read_mode() {
	return WP_Ulike_Pulse_Config::read_mode();
}

/**
 * @return bool
 */
function wp_ulike_writes_pulse() {
	return WP_Ulike_Pulse_Config::writes_pulse();
}

/**
 * @return bool
 */
function wp_ulike_pulse_needs_migration() {
	return WP_Ulike_Pulse_Config::needs_migration_ui();
}

/**
 * Route vote read queries through Pulse Ledger when loaded.
 *
 * @return bool
 */
function wp_ulike_use_pulse_queries() {
	return WP_Ulike_Pulse_Query::available();
}

/**
 * Whether vote reads may still include legacy log tables (legacy or dual/merged mode).
 *
 * @return bool
 */
function wp_ulike_pulse_reads_legacy_votes() {
	$mode = wp_ulike_pulse_read_mode();
	return in_array( $mode, array( 'legacy', 'merged' ), true );
}
