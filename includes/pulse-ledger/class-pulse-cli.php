<?php
/**
 * Pulse Ledger — WP-CLI commands.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_CLI' ) ) {

	final class WP_Ulike_Pulse_CLI {

		/**
		 * @return void
		 */
		public static function register() {
			if ( ! class_exists( 'WP_CLI' ) ) {
				return;
			}

			WP_CLI::add_command(
				'ulike pulse',
				array( __CLASS__, 'handle' )
			);
		}

		/**
		 * Manage Pulse Ledger storage.
		 *
		 * ## SUBCOMMANDS
		 *
		 * status   Show mode and migration progress.
		 * sync     Run one migration batch (or start background sync).
		 * start    Start background migration.
		 * pause    Pause migration.
		 * verify   Compare legacy vs pulse counts (--deep for COUNT scans).
		 * enable   Switch reads to pulse table (after migration).
		 *
		 * @param array $args       Positional args.
		 * @param array $assoc_args Associative args.
		 * @return void
		 */
		public static function handle( $args, $assoc_args ) {
			$sub = isset( $args[0] ) ? $args[0] : 'status';

			switch ( $sub ) {
				case 'start':
					WP_Ulike_Pulse_Sync::start();
					WP_CLI::success( 'Pulse sync started.' );
					break;

				case 'pause':
					WP_Ulike_Pulse_Sync::pause();
					WP_CLI::success( 'Pulse sync paused.' );
					break;

				case 'sync':
					$size   = isset( $assoc_args['batch-size'] ) ? absint( $assoc_args['batch-size'] ) : 0;
					$result = WP_Ulike_Pulse_Sync::run_batch( $size );
					WP_CLI::log( wp_json_encode( $result ) );
					break;

				case 'verify':
					$deep   = isset( $assoc_args['deep'] );
					$result = WP_Ulike_Pulse_Sync::verify( $deep );
					if ( $result['ok'] ) {
						WP_CLI::success( 'Verification passed.' );
					} else {
						WP_CLI::warning( wp_json_encode( $result['issues'] ) );
					}
					break;

				case 'enable':
					if ( ! WP_Ulike_Pulse_Sync::is_sync_complete() ) {
						WP_CLI::error( 'Sync is not complete yet. Run `wp ulike pulse status` and wait until all sources finish.' );
					}
					$verify = WP_Ulike_Pulse_Sync::verify();
					if ( ! $verify['ok'] ) {
						WP_CLI::warning( wp_json_encode( $verify['issues'] ) );
						WP_CLI::error( 'Verification failed. Fix issues before enabling Pulse reads.' );
					}
					WP_Ulike_Pulse_Config::switch_to_pulse();
					WP_CLI::success( 'Pulse mode enabled (reads + writes on pulse table).' );
					break;

				case 'drop-legacy':
					$result = WP_Ulike_Pulse_Legacy_Cleanup::drop_legacy_tables();
					if ( empty( $result['ok'] ) ) {
						WP_CLI::error( 'Could not drop legacy tables: ' . ( $result['message'] ?? 'unknown' ) );
					}
					WP_CLI::success( 'Dropped: ' . implode( ', ', $result['dropped'] ) );
					break;

				case 'dismiss':
					WP_Ulike_Pulse_Config::mark_admin_dismissed();
					WP_CLI::success( 'Storage upgrade admin UI hidden.' );
					break;

				case 'status':
				default:
					$config   = WP_Ulike_Pulse_Config::get();
					$progress = WP_Ulike_Pulse_Sync::get_progress();
					WP_CLI::log( 'Mode: ' . WP_Ulike_Pulse_Config::mode() );
					WP_CLI::log( 'Read: ' . WP_Ulike_Pulse_Config::read_mode() );
					WP_CLI::log( 'Migration: ' . ( $config['migration']['status'] ?? 'idle' ) );
					WP_CLI::log( 'Progress: ' . WP_Ulike_Pulse_Sync::progress_label( $progress ) );
					WP_CLI::log( wp_json_encode( $progress, JSON_PRETTY_PRINT ) );
					break;
			}
		}
	}
}
