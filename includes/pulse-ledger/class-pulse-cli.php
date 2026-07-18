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
		 * smoke    Read-only health checks for storage, stats, and dual mode.
		 *          Use --all-sites on multisite to run on every network blog.
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

				case 'smoke':
					if ( ! empty( $assoc_args['all-sites'] ) ) {
						self::run_smoke_tests_all_sites( ! empty( $assoc_args['deep'] ) );
					} else {
						self::run_smoke_tests( ! empty( $assoc_args['deep'] ) );
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

		/**
		 * Read-only smoke tests for pulse storage, stats meta, and dual-mode routing.
		 *
		 * @param bool $deep         When true, compare persisted statistics meta vs live COUNT totals.
		 * @param bool $exit_on_fail When true, WP-CLI exits with error if any check fails.
		 * @return int Number of failed checks.
		 */
		private static function run_smoke_tests( $deep = false, $exit_on_fail = true ) {
			$result = self::collect_smoke_results( $deep );
			self::print_smoke_results( $result );

			if ( $result['failed'] > 0 ) {
				if ( $exit_on_fail ) {
					WP_CLI::error( sprintf( '%d smoke check(s) failed.', $result['failed'] ) );
				}
				return (int) $result['failed'];
			}

			if ( $exit_on_fail ) {
				WP_CLI::success( 'All smoke checks passed.' );
			}

			return 0;
		}

		/**
		 * Run smoke tests on every site in the current multisite network.
		 *
		 * @param bool $deep When true, compare statistics meta vs live totals per site.
		 * @return void
		 */
		private static function run_smoke_tests_all_sites( $deep = false ) {
			if ( ! is_multisite() ) {
				WP_CLI::error( 'The --all-sites flag requires WordPress multisite.' );
			}

			$site_ids = get_sites(
				array(
					'number'   => 0,
					'archived' => 0,
					'spam'     => 0,
					'deleted'  => 0,
					'fields'   => 'ids',
				)
			);

			if ( empty( $site_ids ) ) {
				WP_CLI::warning( 'No sites found in the network.' );
				return;
			}

			$current_blog = get_current_blog_id();
			$failed_sites = 0;
			$summary      = array();

			foreach ( $site_ids as $blog_id ) {
				$blog_id = (int) $blog_id;
				switch_to_blog( $blog_id );

				$url = get_site_url( $blog_id, '/' );

				WP_CLI::log( '' );
				WP_CLI::log( str_repeat( '=', 72 ) );
				WP_CLI::log( sprintf( 'Site %d: %s', $blog_id, $url ) );
				WP_CLI::log( str_repeat( '=', 72 ) );

				$result = self::collect_smoke_results( $deep );
				self::print_smoke_results( $result );

				if ( $result['failed'] > 0 ) {
					++$failed_sites;
					$summary[] = sprintf(
						'FAIL  blog %d (%s) — %d check(s)',
						$blog_id,
						$url,
						$result['failed']
					);
				} else {
					$summary[] = sprintf( 'OK    blog %d (%s)', $blog_id, $url );
				}

				restore_current_blog();
			}

			if ( get_current_blog_id() !== $current_blog ) {
				switch_to_blog( $current_blog );
			}

			WP_CLI::log( '' );
			WP_CLI::log( 'Network summary (' . count( $site_ids ) . ' site(s)):' );
			foreach ( $summary as $line ) {
				WP_CLI::log( '  ' . $line );
			}

			if ( $failed_sites > 0 ) {
				WP_CLI::error(
					sprintf(
						'%d of %d site(s) failed smoke checks.',
						$failed_sites,
						count( $site_ids )
					)
				);
			}

			WP_CLI::success(
				sprintf( 'All %d site(s) passed smoke checks.', count( $site_ids ) )
			);
		}

		/**
		 * @param bool $deep When true, include meta vs live total comparison.
		 * @return array{failed:int,checks:array<int,array{label:string,ok:bool,detail:string}>,mode:string,read:string}
		 */
		private static function collect_smoke_results( $deep = false ) {
			$failed = 0;
			$mode   = WP_Ulike_Pulse_Config::mode();
			$read   = WP_Ulike_Pulse_Config::read_mode();

			$checks = array(
				self::smoke_check(
					'Storage mode is valid',
					in_array( $mode, array( WP_Ulike_Pulse_Config::MODE_LEGACY, WP_Ulike_Pulse_Config::MODE_DUAL, WP_Ulike_Pulse_Config::MODE_PULSE ), true ),
					$mode
				),
				self::smoke_check(
					'Read mode is valid',
					in_array( $read, array( WP_Ulike_Pulse_Config::READ_LEGACY, WP_Ulike_Pulse_Config::READ_MERGED, WP_Ulike_Pulse_Config::READ_PULSE ), true ),
					$read
				),
				self::smoke_check(
					'Pulse query router available',
					WP_Ulike_Pulse_Query::available(),
					WP_Ulike_Pulse_Query::available() ? 'yes' : 'no'
				),
			);

			if ( WP_Ulike_Pulse_Config::MODE_DUAL === $mode ) {
				$since = WP_Ulike_Pulse_Config::dual_since();
				$checks[] = self::smoke_check(
					'Dual mode has dual_since cutoff',
					! empty( $since ),
					$since ? $since : 'missing'
				);
				$checks[] = self::smoke_check(
					'Dual mode reads merged legacy + pulse',
					WP_Ulike_Pulse_Config::READ_MERGED === $read,
					$read
				);
			}

			if ( in_array( $mode, array( WP_Ulike_Pulse_Config::MODE_DUAL, WP_Ulike_Pulse_Config::MODE_PULSE ), true ) ) {
				$checks[] = self::smoke_check(
					'Pulse table exists',
					WP_Ulike_Pulse_Schema::table_exists(),
					WP_Ulike_Pulse_Schema::table()
				);
				$checks[] = self::smoke_check(
					'Writes route to pulse',
					wp_ulike_writes_pulse(),
					wp_ulike_writes_pulse() ? 'yes' : 'no'
				);
			}

			foreach ( array( 'post', 'comment' ) as $item_type ) {
				$profile = WP_Ulike_Pulse_Registry::setting_profile( $item_type );
				$checks[] = self::smoke_check(
					sprintf( 'Setting profile: %s', $item_type ),
					! empty( $profile['slug'] ) && ! empty( $profile['column'] ),
					wp_json_encode( $profile )
				);
			}

			$cache_key = WP_Ulike_Query_Cache::key( 'smoke_test' );
			$checks[]  = self::smoke_check(
				'Cache key is versioned and mode-scoped',
				0 === strpos( $cache_key, 'pv' ) && false !== strpos( $cache_key, $mode . '_' . $read ),
				$cache_key
			);

			$total_logs = null;

			try {
				$total_logs = WP_Ulike_Pulse_Query::count_logs_for_mode( 'all' );
				$checks[]   = self::smoke_check(
					'count_logs_for_mode(all)',
					is_numeric( $total_logs ),
					(string) $total_logs
				);
			} catch ( Exception $e ) {
				$checks[] = self::smoke_check( 'count_logs_for_mode(all)', false, $e->getMessage() );
			}

			foreach ( WP_Ulike_Pulse_Registry::stats_table_map() as $item_type ) {
				$count    = WP_Ulike_Pulse_Query::count_logs_for_type( $item_type, 'all' );
				$checks[] = self::smoke_check(
					sprintf( 'count_logs_for_type(%s, all)', $item_type ),
					is_numeric( $count ),
					(string) $count
				);
			}

			$meta_total = WP_Ulike_Query_Cache::get_statistics_meta( 'count_logs_period_all' );
			$checks[]   = self::smoke_check(
				'Statistics meta readable',
				is_numeric( $meta_total ) || false === $meta_total || '' === $meta_total,
				is_numeric( $meta_total ) ? (string) $meta_total : 'not seeded yet'
			);

			if ( $deep && is_numeric( $meta_total ) && null !== $total_logs ) {
				$checks[] = self::smoke_check(
					'Statistics meta matches live all-time total',
					(int) $meta_total === (int) $total_logs,
					sprintf( 'meta=%d live=%d', (int) $meta_total, (int) $total_logs )
				);
			}

			// Query path sanity (must not fatally error; results may be empty).
			try {
				$user_rows = WP_Ulike_Pulse_Query::get_user_data(
					1,
					array(
						'type'     => 'post',
						'per_page' => 5,
						'page'     => 1,
					)
				);
				$checks[] = self::smoke_check(
					'get_user_data(post) runs',
					is_array( $user_rows ) || null === $user_rows,
					is_array( $user_rows ) ? count( $user_rows ) . ' row(s)' : 'null'
				);
			} catch ( Exception $e ) {
				$checks[] = self::smoke_check( 'get_user_data(post) runs', false, $e->getMessage() );
			}

			try {
				$users = WP_Ulike_Pulse_Query::get_users(
					array(
						'type'     => 'post',
						'per_page' => 5,
						'page'     => 1,
					)
				);
				$checks[] = self::smoke_check(
					'get_users(post) runs',
					is_array( $users ) || null === $users,
					is_array( $users ) ? count( $users ) . ' row(s)' : 'null'
				);
			} catch ( Exception $e ) {
				$checks[] = self::smoke_check( 'get_users(post) runs', false, $e->getMessage() );
			}

			try {
				$likers = WP_Ulike_Pulse_Query::get_best_likers( 5, 'all', 1 );
				$checks[] = self::smoke_check(
					'get_best_likers runs',
					is_array( $likers ) || null === $likers,
					is_array( $likers ) ? count( $likers ) . ' row(s)' : 'null'
				);
			} catch ( Exception $e ) {
				$checks[] = self::smoke_check( 'get_best_likers runs', false, $e->getMessage() );
			}

			if ( class_exists( 'WP_Ulike_Pulse_Log_Bridge' ) ) {
				try {
					$logs = WP_Ulike_Pulse_Log_Bridge::get_log_rows( 'ulike', 1, 5 );
					$checks[] = self::smoke_check(
						'log bridge get_log_rows(ulike) paginates',
						is_array( $logs ),
						is_array( $logs ) ? count( $logs ) . ' row(s)' : 'fail'
					);
					$count = WP_Ulike_Pulse_Log_Bridge::count_log_rows( 'ulike' );
					$checks[] = self::smoke_check(
						'log bridge count_log_rows(ulike)',
						is_numeric( $count ),
						(string) $count
					);
				} catch ( Exception $e ) {
					$checks[] = self::smoke_check( 'log bridge pagination', false, $e->getMessage() );
				}
			}

			if ( WP_Ulike_Pulse_Config::MODE_DUAL === $mode ) {
				try {
					$posts = WP_Ulike_Pulse_Query::count_logs_for_type( 'post', 'all' );
					$checks[] = self::smoke_check(
						'Dual count_logs_for_type(post) is numeric',
						is_numeric( $posts ),
						(string) $posts
					);
				} catch ( Exception $e ) {
					$checks[] = self::smoke_check( 'Dual count_logs_for_type(post)', false, $e->getMessage() );
				}
			}

			foreach ( $checks as $check ) {
				if ( ! $check['ok'] ) {
					++$failed;
				}
			}

			return array(
				'failed' => $failed,
				'checks' => $checks,
				'mode'   => $mode,
				'read'   => $read,
			);
		}

		/**
		 * @param array{failed:int,checks:array<int,array{label:string,ok:bool,detail:string}>,mode:string,read:string} $result Smoke results.
		 * @return void
		 */
		private static function print_smoke_results( array $result ) {
			foreach ( $result['checks'] as $check ) {
				if ( $check['ok'] ) {
					WP_CLI::success( $check['label'] . ': ' . $check['detail'] );
				} else {
					WP_CLI::warning( $check['label'] . ': ' . $check['detail'] );
				}
			}

			if ( WP_Ulike_Pulse_Config::MODE_DUAL === $result['mode'] ) {
				WP_CLI::log( 'Dual-mode legacy tables:' );
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $source ) {
					$exists = WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ? 'present' : 'absent';
					WP_CLI::log( sprintf( '  - %s (%s): %s', $source['table'], $slug, $exists ) );
				}
			}
		}

		/**
		 * @param string $label  Check label.
		 * @param bool   $ok     Whether the check passed.
		 * @param string $detail Detail string.
		 * @return array{label:string,ok:bool,detail:string}
		 */
		private static function smoke_check( $label, $ok, $detail ) {
			return array(
				'label'  => $label,
				'ok'     => (bool) $ok,
				'detail' => (string) $detail,
			);
		}
	}
}
