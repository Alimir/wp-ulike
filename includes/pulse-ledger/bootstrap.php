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

if ( is_admin() && class_exists( 'WP_Ulike_Pulse_Admin' ) ) {
	WP_Ulike_Pulse_Admin::init();
}

add_action( 'wp_ulike_data_inserted', array( 'WP_Ulike_Query_Cache', 'bump' ), 10, 1 );
add_action( 'wp_ulike_data_updated', array( 'WP_Ulike_Query_Cache', 'bump' ), 10, 1 );
add_action( 'wp_ulike_delete_vote_data', array( 'WP_Ulike_Query_Cache', 'bump' ), 10, 1 );
add_action( 'wp_ulike_data_inserted', 'wp_ulike_track_admin_new_vote', 5, 1 );

/**
 * Bump admin statistics badge on new vote inserts (not toggles/unlikes).
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
