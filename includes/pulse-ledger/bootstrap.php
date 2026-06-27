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
require_once __DIR__ . '/class-pulse-schema.php';
require_once __DIR__ . '/class-pulse-writer.php';
require_once __DIR__ . '/class-pulse-reader.php';
require_once __DIR__ . '/class-pulse-query.php';
require_once __DIR__ . '/class-pulse-sync.php';
require_once __DIR__ . '/class-pulse-sync-scheduler.php';
require_once __DIR__ . '/class-pulse-cli.php';

if ( is_admin() && file_exists( __DIR__ . '/admin/class-pulse-admin.php' ) ) {
	require_once __DIR__ . '/admin/class-pulse-admin.php';
}

WP_Ulike_Pulse_Sync_Scheduler::init();
WP_Ulike_Pulse_CLI::register();

// Self-heal: create ulike_pulse on first load if upgrade hook did not run yet.
add_action( 'init', array( 'WP_Ulike_Pulse_Schema', 'ensure_installed' ), 1 );

if ( is_admin() && class_exists( 'WP_Ulike_Pulse_Admin' ) ) {
	WP_Ulike_Pulse_Admin::init();
}

add_action( 'wp_ulike_data_inserted', 'wp_ulike_pulse_bump_cache', 10, 1 );
add_action( 'wp_ulike_data_updated', 'wp_ulike_pulse_bump_cache', 10, 1 );
add_action( 'wp_ulike_delete_vote_data', 'wp_ulike_pulse_bump_cache', 10, 1 );

/**
 * @return void
 */
function wp_ulike_pulse_bump_cache() {
	$version = (int) get_option( 'wp_ulike_pulse_cache_ver', 1 );
	update_option( 'wp_ulike_pulse_cache_ver', $version + 1, false );
}

/**
 * @return string
 */
function wp_ulike_pulse_table() {
	return WP_Ulike_Pulse_Schema::table();
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
function wp_ulike_pulse_authoritative() {
	return WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode();
}

/**
 * @return bool
 */
function wp_ulike_pulse_needs_migration() {
	return WP_Ulike_Pulse_Config::needs_migration_ui();
}
