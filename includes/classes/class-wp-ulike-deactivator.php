<?php
/**
 * WP ULike Deactivator
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class wp_ulike_deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'wp_ulike_pulse_sync_batch' );
		wp_clear_scheduled_hook( 'wp_ulike_pulse_purge_meta' );
		wp_clear_scheduled_hook( 'wp_ulike_purge_guest_cache' );
	}

}