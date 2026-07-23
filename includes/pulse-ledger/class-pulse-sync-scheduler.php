<?php
/**
 * Pulse Ledger — background sync scheduler.
 *
 * Uses WordPress core WP-Cron only. We intentionally do not depend on
 * Action Scheduler (WooCommerce et al.) — WP ULike is not an e-commerce
 * plugin and should not require one for background migration.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Sync_Scheduler' ) ) {

	final class WP_Ulike_Pulse_Sync_Scheduler {

		const HOOK = 'wp_ulike_pulse_sync_batch';

		/**
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'run_scheduled_batch' ) );
		}

		/**
		 * Schedule the next background batch via WP-Cron.
		 *
		 * @return void
		 */
		public static function schedule() {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_single_event( time() + 5, self::HOOK );
			}
		}

		/**
		 * @return void
		 */
		public static function unschedule() {
			wp_clear_scheduled_hook( self::HOOK );
		}

		/**
		 * @return void
		 */
		public static function run_scheduled_batch() {
			$result = WP_Ulike_Pulse_Sync::run_batch();

			if ( empty( $result['done'] ) && WP_Ulike_Pulse_Config::migration_running() ) {
				self::schedule();
			}
		}
	}
}
