<?php
/**
 * Pulse Ledger admin UI.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Admin' ) ) {

	final class WP_Ulike_Pulse_Admin {

		const PAGE_SLUG = 'wp-ulike-pulse';

		/**
		 * Admin hook suffix from add_submenu_page().
		 *
		 * @var string
		 */
		private static $page_hook = '';

		/**
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'register_page' ), 30 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_ajax_wp_ulike_pulse_sync_status', array( __CLASS__, 'ajax_status' ) );
			add_action( 'wp_ajax_wp_ulike_pulse_sync_action', array( __CLASS__, 'ajax_action' ) );
			add_action( 'admin_notices', array( __CLASS__, 'storage_upgrade_notice' ) );
		}

		/**
		 * User-facing title for the storage upgrade task page.
		 *
		 * @return string
		 */
		public static function get_page_title() {
			return 'Upgrade like storage';
		}

		/**
		 * @return string
		 */
		public static function get_page_url() {
			return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		}

		/**
		 * @return string
		 */
		public static function get_help_url() {
			return admin_url( 'admin.php?page=wp-ulike-about' );
		}

		/**
		 * Hidden task page (null parent — reachable by URL only).
		 *
		 * @return void
		 */
		public static function register_page() {
			if ( ! WP_Ulike_Pulse_Config::should_show_storage_upgrade_ui() ) {
				return;
			}

			self::$page_hook = add_submenu_page(
				null,
				self::get_page_title(),
				'',
				'manage_options',
				self::PAGE_SLUG,
				array( __CLASS__, 'render_page' )
			);
		}

		/**
		 * Whether the global admin notice should appear.
		 *
		 * @return bool
		 */
		public static function should_show_notice() {
			return current_user_can( 'manage_options' ) && wp_ulike_pulse_needs_migration();
		}

		/**
		 * View-model for the Help page storage-upgrade card.
		 *
		 * @return array<string,mixed>|null
		 */
		public static function get_help_card_data() {
			if ( ! current_user_can( 'manage_options' ) || ! WP_Ulike_Pulse_Config::should_show_storage_upgrade_ui() ) {
				return null;
			}

			$config        = WP_Ulike_Pulse_Config::get();
			$sync_status   = $config['migration']['status'] ?? 'idle';
			$progress      = WP_Ulike_Pulse_Sync::get_progress();
			$sync_complete = WP_Ulike_Pulse_Sync::is_sync_complete( $progress ) || 'done' === $sync_status;
			$is_pulse      = WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode();
			$status_label  = self::status_label( $sync_status, $sync_complete );
			$progress_label = WP_Ulike_Pulse_Sync::progress_label( $progress );

			if ( $is_pulse ) {
				return array(
					'phase'       => 'cleanup',
					'title'       => 'Free up disk space',
					'intro'       => 'Like records already use the faster storage. Remove the old log tables when you are ready to reclaim disk space.',
					'reassurance' => array(
						'WP ULike is fully working with the new storage.',
						'Old tables stay until you remove them.',
						'Back up your database before deleting anything.',
					),
					'status'      => 'Upgrade complete',
					'progress'    => '',
					'state'       => 'good',
					'cta_label'   => 'Review cleanup',
					'url'         => self::get_page_url(),
				);
			}

			$cta_label = 'Get started';
			if ( 'running' === $sync_status && ! $sync_complete ) {
				$cta_label = 'Continue';
			} elseif ( $sync_complete ) {
				$cta_label = 'Finish upgrade';
			}

			$state = 'neutral';
			if ( $sync_complete ) {
				$state = 'good';
			} elseif ( 'running' === $sync_status ) {
				$state = 'warn';
			} elseif ( 'paused' === $sync_status ) {
				$state = 'warn';
			}

			return array(
				'phase'       => 'migrate',
				'title'       => self::get_page_title(),
				'intro'       => 'We recommend moving your existing like records to a faster table — especially on busy sites. Counts and buttons keep working exactly as they do now.',
				'reassurance' => array(
					'Nothing is deleted — old log tables stay until you choose otherwise.',
					'Records move in the background; your site stays online.',
					'Meta counts and buttons are unchanged throughout.',
				),
				'status'      => $status_label,
				'progress'    => $progress_label,
				'state'       => $state,
				'cta_label'   => $cta_label,
				'url'         => self::get_page_url(),
			);
		}

		/**
		 * @param string $hook Hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( self::$page_hook && self::$page_hook !== $hook ) {
				return;
			}

			if ( ! self::$page_hook && false === strpos( $hook, self::PAGE_SLUG ) ) {
				return;
			}

			wp_enqueue_script(
				'wp-ulike-pulse-admin',
				WP_ULIKE_ADMIN_URL . '/assets/js/pulse-admin.js',
				array( 'jquery' ),
				WP_ULIKE_VERSION,
				true
			);

			wp_localize_script(
				'wp-ulike-pulse-admin',
				'wpUlikePulse',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'wp_ulike_pulse_admin' ),
					'isRunning'     => self::should_run_browser_batches(),
					'syncComplete'  => WP_Ulike_Pulse_Sync::is_sync_complete(),
					'isPulse'       => WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode(),
				'confirmEnable' => 'Switch to the faster storage for all reads? Your old tables are kept — nothing is deleted.',
				'confirmDrop'   => 'Permanently delete old log tables? This cannot be undone. Make sure you have a database backup.',
				'redirectUrl'   => self::get_help_url(),
				'strings'       => array(
					'started'                 => 'Upgrade started. You can leave this page — records will keep moving in the background.',
					'syncComplete'            => 'Records moved. Click “Finish upgrade” below to start using the faster storage for all reads.',
					'finished'                => 'All done. Like records now use the faster storage.',
					'dropped'                 => 'Old tables removed. Redirecting…',
					'dismissed'               => 'Done. Redirecting…',
					'dropFailed'              => 'Could not remove old tables. Please try again or use WP-CLI.',
					'enableFailed'            => 'Could not finish the upgrade yet. Please wait until all records are moved.',
					'enableVerifyFailed'      => 'Move finished but verification failed. Run “wp ulike pulse verify” for details, or contact support if failed rows are reported.',
					'enableSyncIncomplete'    => 'Not finished yet. Wait until status shows Complete, or run “wp ulike pulse sync”.',
					'actionFailed'            => 'Something went wrong. Please refresh the page and try again.',
					'progressWaiting'         => 'Waiting to start…',
					'progressCopied'          => '%1$s records moved',
					'progressCopiedSkipped'   => '%1$s records moved (%2$s skipped)',
					'progressComplete'        => '%1$s records moved · complete',
					'progressCompleteSkipped' => '%1$s records moved (%2$s skipped) · complete',
					'progressEstimated'       => ' · ~%s%% estimated',
				),
				)
			);
		}

		/**
		 * Whether the browser should actively run sync batches on page load.
		 *
		 * @return bool
		 */
		private static function should_run_browser_batches() {
			return WP_Ulike_Pulse_Config::migration_running() && ! WP_Ulike_Pulse_Sync::is_sync_complete();
		}

		/**
		 * Human-readable sync status for the admin UI.
		 *
		 * @param string $status Raw status slug.
		 * @param bool   $sync_complete Whether all records are moved.
		 * @return string
		 */
		public static function status_label( $status, $sync_complete ) {
			if ( $sync_complete ) {
				return 'Complete';
			}

			switch ( $status ) {
				case 'running':
					return 'Moving records…';
				case 'paused':
					return 'Paused';
				default:
					return 'Not started';
			}
		}

		/**
		 * @return void
		 */
		public static function storage_upgrade_notice() {
			if ( ! self::should_show_notice() ) {
				return;
			}

			$url = self::get_page_url();
			?>
			<div class="notice wp-ulike-notice wp-ulike-notice-control wp-ulike-notice-wrapper wp-ulike-notice-id-wp_ulike_storage_upgrade wp-ulike-notice-skin-upgrade">
				<div class="wp-ulike-notice-info">
					<h3 class="wp-ulike-notice-title">
						<?php echo esc_html( 'WP ULike: faster like storage is ready' ); ?>
					</h3>
					<p class="wp-ulike-notice-description">
						<?php echo esc_html( 'Move your existing like records to a faster table for better performance on busy sites. Counts and buttons keep working — nothing is deleted.' ); ?>
					</p>
					<div class="wp-ulike-notice-submit">
						<a class="wp-ulike-btn wp-ulike-btn-default wp-ulike-notice-btn wp-ulike-notice-cta-btn" href="<?php echo esc_url( $url ); ?>">
							<span class="wp-ulike-text"><?php echo esc_html( 'Begin upgrade' ); ?></span>
						</a>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * @return void
		 */
		public static function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! WP_Ulike_Pulse_Config::should_show_storage_upgrade_ui() ) {
				wp_safe_redirect( self::get_help_url() );
				exit;
			}

			$progress        = WP_Ulike_Pulse_Sync::get_progress();
			$config          = WP_Ulike_Pulse_Config::get();
			$sync_status     = $config['migration']['status'] ?? 'idle';
			$sync_complete   = WP_Ulike_Pulse_Sync::is_sync_complete( $progress ) || 'done' === $sync_status;
			$is_running      = 'running' === $sync_status && ! $sync_complete;
			$is_pulse        = WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode();
			$status_label    = self::status_label( $sync_status, $sync_complete );
			$legacy_tables   = WP_Ulike_Pulse_Legacy_Cleanup::existing_legacy_tables();
			$show_cleanup    = $is_pulse && ! empty( $legacy_tables );
			$can_drop_legacy = $show_cleanup && WP_Ulike_Pulse_Legacy_Cleanup::can_drop_legacy();
			$percent         = $sync_complete ? 100 : (float) ( $progress['percent_estimate'] ?? 0 );
			$progress_label  = WP_Ulike_Pulse_Sync::progress_label( $progress );
			$page_title      = self::get_page_title();

			$cli_commands = self::cli_commands();

			include __DIR__ . '/templates/pulse-storage.php';
		}

		/**
		 * Optional WP-CLI commands for the admin accordion.
		 *
		 * @return array<int,array{cmd:string,desc:string}>
		 */
		public static function cli_commands() {
			return array(
				array(
					'cmd'  => 'wp ulike pulse status',
					'desc' => 'Check progress',
				),
				array(
					'cmd'  => 'wp ulike pulse start',
					'desc' => 'Start sync',
				),
				array(
					'cmd'  => 'wp ulike pulse sync',
					'desc' => 'Run one batch',
				),
				array(
					'cmd'  => 'wp ulike pulse verify',
					'desc' => 'Verify records (add --deep for COUNT scans)',
				),
			array(
				'cmd'  => 'wp ulike pulse enable',
				'desc' => 'Finish upgrade',
			),
		);
		}

		/**
		 * @return void
		 */
		public static function ajax_status() {
			self::verify_ajax();
			$progress      = WP_Ulike_Pulse_Sync::get_progress();
			$config        = WP_Ulike_Pulse_Config::get();
			$sync_status   = $config['migration']['status'] ?? 'idle';
			$sync_complete = WP_Ulike_Pulse_Sync::is_sync_complete( $progress );

			wp_send_json_success(
				array(
					'mode'              => WP_Ulike_Pulse_Config::mode(),
					'read'              => WP_Ulike_Pulse_Config::read_mode(),
					'migration_status'  => $sync_status,
					'sync_complete'     => $sync_complete,
					'status_label'      => self::status_label( $sync_status, $sync_complete ),
					'is_pulse'          => WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode(),
					'progress'          => $progress,
					'progress_label'    => WP_Ulike_Pulse_Sync::progress_label( $progress ),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function ajax_action() {
			self::verify_ajax();

			$action = isset( $_POST['pulse_action'] ) ? sanitize_key( wp_unslash( $_POST['pulse_action'] ) ) : '';

			switch ( $action ) {
				case 'start':
					if ( WP_Ulike_Pulse_Sync::is_sync_complete() ) {
						wp_send_json_error( array( 'message' => 'already_complete' ) );
					}
					WP_Ulike_Pulse_Sync::start();
					wp_send_json_success( array( 'message' => 'started' ) );
					break;

				case 'pause':
					WP_Ulike_Pulse_Sync::pause();
					wp_send_json_success( array( 'message' => 'paused' ) );
					break;

				case 'batch':
					wp_send_json_success( WP_Ulike_Pulse_Sync::run_batch() );
					break;

				case 'enable':
					if ( ! WP_Ulike_Pulse_Sync::is_sync_complete() ) {
						wp_send_json_error(
							array(
								'message' => 'sync_incomplete',
								'reason'  => 'sync_incomplete',
							)
						);
					}
					$verify = WP_Ulike_Pulse_Sync::verify();
					if ( ! $verify['ok'] ) {
						wp_send_json_error(
							array_merge(
								$verify,
								array(
									'message' => 'verify_failed',
									'reason'  => 'verify_failed',
								)
							)
						);
					}
					WP_Ulike_Pulse_Config::switch_to_pulse();
					wp_send_json_success(
						array(
							'message'       => 'pulse_enabled',
							'show_cleanup'  => WP_Ulike_Pulse_Legacy_Cleanup::legacy_tables_exist(),
						)
					);
					break;

				case 'dismiss':
					WP_Ulike_Pulse_Config::mark_admin_dismissed();
					wp_send_json_success(
						array(
							'redirect' => self::get_help_url(),
						)
					);
					break;

				case 'drop_legacy':
					$result = WP_Ulike_Pulse_Legacy_Cleanup::drop_legacy_tables();
					if ( empty( $result['ok'] ) ) {
						wp_send_json_error( $result );
					}
					wp_send_json_success(
						array(
							'redirect' => self::get_help_url(),
							'dropped'  => $result['dropped'],
						)
					);
					break;

				default:
					wp_send_json_error( array( 'message' => 'invalid_action' ) );
			}
		}

		/**
		 * @return void
		 */
		private static function verify_ajax() {
			check_ajax_referer( 'wp_ulike_pulse_admin', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
			}
		}
	}
}
