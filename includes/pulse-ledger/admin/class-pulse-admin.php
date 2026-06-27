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

		/**
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 30 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_ajax_wp_ulike_pulse_sync_status', array( __CLASS__, 'ajax_status' ) );
			add_action( 'wp_ajax_wp_ulike_pulse_sync_action', array( __CLASS__, 'ajax_action' ) );
			add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) );
		}

		/**
		 * @return void
		 */
		public static function register_menu() {
			add_submenu_page(
				'wp-ulike-stats',
				esc_html__( 'Pulse Storage', 'wp-ulike' ),
				esc_html__( 'Pulse Storage', 'wp-ulike' ),
				'manage_options',
				'wp-ulike-pulse',
				array( __CLASS__, 'render_page' )
			);
		}

		/**
		 * @param string $hook Hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( 'wp-ulike_page_wp-ulike-pulse' !== $hook ) {
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
					'isRunning'     => WP_Ulike_Pulse_Config::migration_running(),
					'confirmEnable' => esc_html__( 'Switch to Pulse for all reads? Only do this after sync reaches 100%.', 'wp-ulike' ),
					'strings'       => array(
						'started'      => esc_html__( 'Sync started. You can leave this page — it will continue in the background.', 'wp-ulike' ),
						'finished'     => esc_html__( 'Migration complete. Pulse is now used for all reads.', 'wp-ulike' ),
						'enableFailed' => esc_html__( 'Could not finish migration yet. Please wait until sync reaches 100%.', 'wp-ulike' ),
					),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function migration_notice() {
			if ( ! current_user_can( 'manage_options' ) || ! wp_ulike_pulse_needs_migration() ) {
				return;
			}

			$url = admin_url( 'admin.php?page=wp-ulike-pulse' );
			echo '<div class="notice notice-info"><p>';
			printf(
				/* translators: %s: admin page URL */
				esc_html__( 'Optional: copy your existing likes to the new Pulse storage. %s', 'wp-ulike' ),
				'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Open Pulse Storage', 'wp-ulike' ) . '</a>'
			);
			echo '</p></div>';
		}

		/**
		 * @return void
		 */
		public static function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$config   = WP_Ulike_Pulse_Config::get();
			$progress = WP_Ulike_Pulse_Sync::get_progress();
			$percent  = ! empty( $progress['total_legacy'] )
				? min( 100, round( ( $progress['total_imported'] / $progress['total_legacy'] ) * 100, 1 ) )
				: 100;

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
					'desc' => __( 'Verify counts', 'wp-ulike' ),
				),
				array(
					'cmd'  => 'wp ulike pulse enable',
					'desc' => __( 'Finish migration', 'wp-ulike' ),
				),
			);
		}

		/**
		 * @return void
		 */
		public static function ajax_status() {
			self::verify_ajax();
			$config = WP_Ulike_Pulse_Config::get();
			wp_send_json_success(
				array(
					'mode'              => WP_Ulike_Pulse_Config::mode(),
					'read'              => WP_Ulike_Pulse_Config::read_mode(),
					'migration_status'  => $config['migration']['status'] ?? 'idle',
					'progress'          => WP_Ulike_Pulse_Sync::get_progress(),
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
					$verify = WP_Ulike_Pulse_Sync::verify();
					if ( ! $verify['ok'] ) {
						wp_send_json_error( $verify );
					}
					WP_Ulike_Pulse_Config::switch_to_pulse();
					wp_send_json_success( array( 'message' => 'pulse_enabled' ) );
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
