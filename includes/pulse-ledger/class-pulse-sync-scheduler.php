<?php
/**
 * Pulse Ledger — background sync scheduler.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Sync_Scheduler' ) ) {

	final class WP_Ulike_Pulse_Sync_Scheduler {

		const HOOK = 'wp_ulike_pulse_sync_batch';
		const GROUP = 'wp_ulike_pulse';

		/**
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'run_scheduled_batch' ) );
		}

		/**
		 * @return void
		 */
		public static function schedule() {
			if ( self::has_action_scheduler() ) {
				if ( ! as_has_scheduled_action( self::HOOK, array(), self::GROUP ) ) {
					as_schedule_single_action( time() + 5, self::HOOK, array(), self::GROUP );
				}
				return;
			}

			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_single_event( time() + 5, self::HOOK );
			}
		}

		/**
		 * @return void
		 */
		public static function unschedule() {
			if ( self::has_action_scheduler() ) {
				as_unschedule_all_actions( self::HOOK, array(), self::GROUP );
				return;
			}

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

		/**
		 * @return bool
		 */
		private static function has_action_scheduler() {
			return function_exists( 'as_schedule_single_action' ) && function_exists( 'as_has_scheduled_action' );
		}

		/**
		 * Human-readable background engine name.
		 *
		 * @return string
		 */
		public static function engine_label() {
			if ( self::has_action_scheduler() ) {
				return 'Action Scheduler';
			}

			return 'WP-Cron';
		}

		/**
		 * @return bool
		 */
		public static function is_scheduled() {
			if ( self::has_action_scheduler() ) {
				return as_has_scheduled_action( self::HOOK, array(), self::GROUP );
			}

			return (bool) wp_next_scheduled( self::HOOK );
		}
	}
}
