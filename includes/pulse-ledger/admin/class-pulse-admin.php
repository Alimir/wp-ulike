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
			add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) );
			add_action( 'admin_post_wp_ulike_dismiss_storage_upgrade', array( __CLASS__, 'handle_dismiss_request' ) );
		}

		/**
		 * User-facing title for the storage upgrade task page.
		 *
		 * @return string
		 */
		public static function get_page_title() {
			return __( 'Copy likes to faster storage', 'wp-ulike' );
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
		public static function get_dismiss_url() {
			return wp_nonce_url(
				admin_url( 'admin-post.php?action=wp_ulike_dismiss_storage_upgrade' ),
				'wp_ulike_dismiss_storage_upgrade'
			);
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
			return current_user_can( 'manage_options' )
				&& wp_ulike_pulse_needs_migration()
				&& ! WP_Ulike_Pulse_Config::is_notice_dismissed();
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
					'phase'         => 'cleanup',
					'title'         => __( 'Optional cleanup', 'wp-ulike' ),
					'intro'         => __( 'Your likes already use the faster storage. You can optionally remove the old like tables to free disk space — only if you want to.', 'wp-ulike' ),
					'reassurance'   => array(
						__( 'Nothing else is required for WP ULike to work.', 'wp-ulike' ),
						__( 'Old tables are kept until you explicitly remove them.', 'wp-ulike' ),
						__( 'Make a database backup before deleting anything.', 'wp-ulike' ),
					),
					'status'        => __( 'Upgrade complete', 'wp-ulike' ),
					'progress'      => '',
					'state'         => 'good',
					'cta_label'     => __( 'Review cleanup options', 'wp-ulike' ),
					'url'           => self::get_page_url(),
				);
			}

			$cta_label = __( 'Open migration', 'wp-ulike' );
			if ( 'running' === $sync_status && ! $sync_complete ) {
				$cta_label = __( 'Continue migration', 'wp-ulike' );
			} elseif ( $sync_complete ) {
				$cta_label = __( 'Finish upgrade', 'wp-ulike' );
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
				'intro'       => __( 'WP ULike can copy your existing likes into a faster table. Your like buttons and counts keep working exactly as they do now.', 'wp-ulike' ),
				'reassurance' => array(
					__( 'Nothing is deleted — old data stays until you choose otherwise.', 'wp-ulike' ),
					__( 'The copy runs in the background; your site stays online.', 'wp-ulike' ),
					__( 'You can skip this entirely with no impact on likes.', 'wp-ulike' ),
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
					'confirmEnable' => esc_html__( 'Switch to the faster storage for all reads? Your old tables are kept — nothing is deleted.', 'wp-ulike' ),
					'confirmDrop'   => esc_html__( 'Permanently delete old like tables? This cannot be undone. Make sure you have a database backup.', 'wp-ulike' ),
					'redirectUrl'   => self::get_help_url(),
					'strings'       => array(
						'started'                 => esc_html__( 'Copy started. You can leave this page — it will continue in the background.', 'wp-ulike' ),
						'syncComplete'            => esc_html__( 'Copy complete. Click “Finish upgrade” below to start using the faster storage for all reads.', 'wp-ulike' ),
						'finished'                => esc_html__( 'All done. Your likes now use the faster storage.', 'wp-ulike' ),
						'dropped'                 => esc_html__( 'Old tables removed. Redirecting…', 'wp-ulike' ),
						'dismissed'               => esc_html__( 'Done. Redirecting…', 'wp-ulike' ),
						'dropFailed'              => esc_html__( 'Could not remove old tables. Please try again or use WP-CLI.', 'wp-ulike' ),
						'enableFailed'            => esc_html__( 'Could not finish the upgrade yet. Please wait until the copy is complete.', 'wp-ulike' ),
						'enableVerifyFailed'      => esc_html__( 'Copy finished but verification failed. Run “wp ulike pulse verify” for details, or contact support if failed rows are reported.', 'wp-ulike' ),
						'enableSyncIncomplete'    => esc_html__( 'Copy is not finished yet. Wait until status shows Complete, or run “wp ulike pulse sync”.', 'wp-ulike' ),
						'actionFailed'            => esc_html__( 'Something went wrong. Please refresh the page and try again.', 'wp-ulike' ),
						'progressWaiting'         => esc_html__( 'Waiting to start…', 'wp-ulike' ),
						'progressCopied'          => esc_html__( '%1$s rows copied', 'wp-ulike' ),
						'progressCopiedSkipped'   => esc_html__( '%1$s rows copied (%2$s skipped)', 'wp-ulike' ),
						'progressComplete'        => esc_html__( '%1$s rows copied · complete', 'wp-ulike' ),
						'progressCompleteSkipped' => esc_html__( '%1$s rows copied (%2$s skipped) · complete', 'wp-ulike' ),
						'progressEstimated'       => esc_html__( ' · ~%s%% estimated', 'wp-ulike' ),
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
		 * Human-readable migration status for the admin UI.
		 *
		 * @param string $status Raw status slug.
		 * @param bool   $sync_complete Whether copy finished.
		 * @return string
		 */
		public static function status_label( $status, $sync_complete ) {
			if ( $sync_complete ) {
				return esc_html__( 'Complete', 'wp-ulike' );
			}

			switch ( $status ) {
				case 'running':
					return esc_html__( 'Copying…', 'wp-ulike' );
				case 'paused':
					return esc_html__( 'Paused', 'wp-ulike' );
				default:
					return esc_html__( 'Not started', 'wp-ulike' );
			}
		}

		/**
		 * @return void
		 */
		public static function migration_notice() {
			if ( ! self::should_show_notice() ) {
				return;
			}

			$url         = self::get_page_url();
			$dismiss_url = self::get_dismiss_url();
			?>
			<div class="notice wp-ulike-notice wp-ulike-notice-control wp-ulike-notice-wrapper wp-ulike-notice-id-wp_ulike_storage_upgrade wp-ulike-notice-skin-upgrade">
				<div class="wp-ulike-notice-info">
					<h3 class="wp-ulike-notice-title">
						<?php esc_html_e( 'WP ULike: optional storage upgrade available', 'wp-ulike' ); ?>
					</h3>
					<p class="wp-ulike-notice-description">
						<?php esc_html_e( 'You can copy your existing likes to a faster table for better performance on large sites. Your like buttons and counts keep working as usual — nothing is deleted.', 'wp-ulike' ); ?>
					</p>
					<div class="wp-ulike-notice-submit">
						<a class="wp-ulike-btn wp-ulike-btn-default wp-ulike-notice-btn wp-ulike-notice-cta-btn" href="<?php echo esc_url( $url ); ?>">
							<span class="wp-ulike-text"><?php esc_html_e( 'View upgrade', 'wp-ulike' ); ?></span>
						</a>
						<a class="wp-ulike-btn wp-ulike-btn-default wp-ulike-notice-btn wp-ulike-btn-ghost" href="<?php echo esc_url( $dismiss_url ); ?>">
							<span class="wp-ulike-text"><?php esc_html_e( 'Not now', 'wp-ulike' ); ?></span>
						</a>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * @return void
		 */
		public static function handle_dismiss_request() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to dismiss this notice.', 'wp-ulike' ) );
			}

			check_admin_referer( 'wp_ulike_dismiss_storage_upgrade' );

			WP_Ulike_Pulse_Config::mark_notice_dismissed();

			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : self::get_help_url() );
			exit;
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
					'desc' => __( 'Check progress', 'wp-ulike' ),
				),
				array(
					'cmd'  => 'wp ulike pulse start',
					'desc' => __( 'Start sync', 'wp-ulike' ),
				),
				array(
					'cmd'  => 'wp ulike pulse sync',
					'desc' => __( 'Run one batch', 'wp-ulike' ),
				),
				array(
					'cmd'  => 'wp ulike pulse verify',
					'desc' => __( 'Verify migration (add --deep for COUNT scans)', 'wp-ulike' ),
				),
				array(
					'cmd'  => 'wp ulike pulse enable',
					'desc' => __( 'Finish upgrade', 'wp-ulike' ),
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
