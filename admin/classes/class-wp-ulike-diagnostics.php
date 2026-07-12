<?php
/**
 * WP ULike — Pulse health check & diagnostics.
 *
 * One-click, read-only diagnostics for the Pulse storage stack: tables,
 * migration status, dual-mode consistency, frontend AJAX/asset wiring, and
 * environment. Rendered as a card on the Help page and run via AJAX.
 *
 * @package WP_ULike
 * @since   5.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Diagnostics' ) ) {

	class WP_Ulike_Diagnostics {

		const NONCE      = 'wp_ulike_tools';
		const SCRIPT     = 'wp-ulike-diagnostics';
		const ABOUT_SLUG = 'wp-ulike-about';
		const ANCHOR     = 'wp-ulike-diagnostics';

		/**
		 * Bootstrap hooks.
		 *
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_ajax_wp_ulike_run_diagnostics', array( __CLASS__, 'ajax_run_diagnostics' ) );
		}

		/**
		 * Help page URL with the health-check card anchor.
		 *
		 * Used as the canonical deep-link to the health-check card.
		 *
		 * @return string
		 */
		public static function get_health_check_url() {
			return admin_url( 'admin.php?page=' . self::ABOUT_SLUG ) . '#' . self::ANCHOR;
		}

		/**
		 * Enqueue the diagnostics runner only on the Help page.
		 *
		 * @param string $hook Current admin page hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( false === strpos( $hook, self::ABOUT_SLUG ) ) {
				return;
			}

			wp_enqueue_style(
				self::SCRIPT,
				WP_ULIKE_ADMIN_URL . '/assets/css/diagnostics.css',
				array(),
				WP_ULIKE_VERSION
			);

			wp_enqueue_script(
				self::SCRIPT,
				WP_ULIKE_ADMIN_URL . '/assets/js/diagnostics.js',
				array(),
				WP_ULIKE_VERSION,
				true
			);

			wp_localize_script(
				self::SCRIPT,
				'wpUlikeDiagnostics',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( self::NONCE ),
					'strings' => array(
						'run'         => esc_html__( 'Run health check', 'wp-ulike' ),
						'running'     => esc_html__( 'Checking…', 'wp-ulike' ),
						'rerun'       => esc_html__( 'Run again', 'wp-ulike' ),
						'copy'        => esc_html__( 'Copy report', 'wp-ulike' ),
						'copied'      => esc_html__( 'Report copied to clipboard', 'wp-ulike' ),
						'copyFailed'  => esc_html__( 'Could not copy — select the text below.', 'wp-ulike' ),
						'error'       => esc_html__( 'Something went wrong running the health check. Please refresh the page and try again.', 'wp-ulike' ),
						'summaryPass' => esc_html__( 'All checks passed', 'wp-ulike' ),
						'summaryWarn' => esc_html__( 'No failures, some warnings', 'wp-ulike' ),
						'summaryFail' => esc_html__( 'Issues found — see failed checks', 'wp-ulike' ),
					),
				)
			);
		}

		/**
		 * Render the health-check card for the Help page.
		 *
		 * @return void
		 */
		public static function render_health_check_card() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wp-ulike-about-card wp-ulike-diagnostics" id="<?php echo esc_attr( self::ANCHOR ); ?>">
				<div class="wp-ulike-about-card__header">
					<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Health check', 'wp-ulike' ); ?></h2>
				</div>
				<p class="wp-ulike-diagnostics__lead">
					<?php esc_html_e( 'One-click, read-only check of storage, migration, database tables, frontend wiring, and environment. Safe to run anytime. Copy the report to share with support.', 'wp-ulike' ); ?>
				</p>

				<div class="wp-ulike-diagnostics__actions">
					<button type="button" id="wp-ulike-diagnostics-run" class="button button-primary wp-ulike-diagnostics__run">
						<span class="wp-ulike-diagnostics__run-label"><?php esc_html_e( 'Run health check', 'wp-ulike' ); ?></span>
					</button>
					<button type="button" id="wp-ulike-diagnostics-copy" class="button wp-ulike-diagnostics__copy" disabled aria-disabled="true">
						<?php esc_html_e( 'Copy report', 'wp-ulike' ); ?>
					</button>
				</div>

				<div class="wp-ulike-diagnostics__summary" id="wp-ulike-diagnostics-summary" hidden>
					<span class="wp-ulike-diagnostics__summary-text"></span>
				</div>

				<div class="wp-ulike-diagnostics__results" id="wp-ulike-diagnostics-results" aria-live="polite" aria-busy="false">
					<p class="wp-ulike-diagnostics__placeholder"><?php esc_html_e( 'Click “Run health check” to start.', 'wp-ulike' ); ?></p>
				</div>

				<textarea id="wp-ulike-diagnostics-report" class="wp-ulike-diagnostics__report" readonly hidden aria-label="<?php esc_attr_e( 'Diagnostics report', 'wp-ulike' ); ?>"></textarea>
			</div>
			<?php
		}

		/**
		 * AJAX: run diagnostics and return the report.
		 *
		 * @return void
		 */
		public static function ajax_run_diagnostics() {
			self::verify_ajax();
			try {
				wp_send_json_success( self::run_checks() );
			} catch ( Throwable $e ) {
				wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
			}
		}

		/**
		 * Verify ajax nonce + capability.
		 *
		 * @return void
		 */
		private static function verify_ajax() {
			check_ajax_referer( self::NONCE, 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
			}
		}

		/**
		 * Run all diagnostic checks and return a structured report.
		 *
		 * @return array{
		 *   generated_at: string,
		 *   mode: string,
		 *   read_mode: string,
		 *   groups: array<string,array{label:string,checks:array<int,array{label:string,status:string,detail:string,hint:string}>}>,
		 *   summary: array{pass:int,warn:int,fail:int}
		 * }
		 */
		public static function run_checks() {
			$mode      = function_exists( 'wp_ulike_pulse_mode' ) ? wp_ulike_pulse_mode() : 'legacy';
			$read_mode = function_exists( 'wp_ulike_pulse_read_mode' ) ? wp_ulike_pulse_read_mode() : 'legacy';

			$group_defs = array(
				'storage'     => array(
					'label' => __( 'Storage & migration', 'wp-ulike' ),
					'fn'    => array( __CLASS__, 'storage_checks' ),
					'args'  => array( $mode, $read_mode ),
				),
				'tables'      => array(
					'label' => __( 'Database tables', 'wp-ulike' ),
					'fn'    => array( __CLASS__, 'tables_checks' ),
					'args'  => array(),
				),
				'consistency' => array(
					'label' => __( 'Dual-mode consistency', 'wp-ulike' ),
					'fn'    => array( __CLASS__, 'consistency_checks' ),
					'args'  => array( $mode ),
				),
				'frontend'    => array(
					'label' => __( 'Frontend & AJAX', 'wp-ulike' ),
					'fn'    => array( __CLASS__, 'frontend_checks' ),
					'args'  => array(),
				),
				'environment' => array(
					'label' => __( 'Environment', 'wp-ulike' ),
					'fn'    => array( __CLASS__, 'environment_checks' ),
					'args'  => array(),
				),
			);

			$groups = array();
			foreach ( $group_defs as $key => $def ) {
				$groups[ $key ] = array(
					'label'  => $def['label'],
					'checks' => self::safe_checks( $def['fn'], $def['args'], $def['label'] ),
				);
			}

			$summary = array( 'pass' => 0, 'warn' => 0, 'fail' => 0 );
			foreach ( $groups as $group ) {
				foreach ( $group['checks'] as $check ) {
					if ( isset( $summary[ $check['status'] ] ) ) {
						$summary[ $check['status'] ]++;
					}
				}
			}

			return array(
				'generated_at' => gmdate( 'c' ),
				'mode'         => $mode,
				'read_mode'    => $read_mode,
				'groups'       => $groups,
				'summary'      => $summary,
			);
		}

		/**
		 * Run a check group inside a guard so a single failing check can never
		 * take down the whole report. Exceptions become an explicit fail row.
		 *
		 * @param callable $fn    Check group callback.
		 * @param array    $args  Arguments for the callback.
		 * @param string   $label Group label (for the failure row).
		 * @return array<int,array{label:string,status:string,detail:string,hint:string}>
		 */
		private static function safe_checks( $fn, array $args, $label ) {
			try {
				$checks = call_user_func_array( $fn, $args );
				if ( ! is_array( $checks ) ) {
					return array( self::fail( $label, 'invalid return', __( 'Check group returned a non-array result. Please report this to support.', 'wp-ulike' ) ) );
				}
				return $checks;
			} catch ( Throwable $e ) {
				return array( self::fail( $label, $e->getMessage(), __( 'This check raised an error. Copy the report and share it with support so we can fix it.', 'wp-ulike' ) ) );
			}
		}

		/**
		 * Storage & migration checks.
		 *
		 * @param string $mode      Storage mode.
		 * @param string $read_mode Read mode.
		 * @return array<int,array{label:string,status:string,detail:string,hint:string}>
		 */
		private static function storage_checks( $mode, $read_mode ) {
			$checks = array();

			$valid_modes = array( 'legacy', 'dual', 'pulse' );
			$checks[]    = self::check(
				__( 'Storage mode is valid', 'wp-ulike' ),
				in_array( $mode, $valid_modes, true ),
				$mode,
				__( 'Storage mode is unrecognized. Reload WP ULike settings or contact support.', 'wp-ulike' )
			);

			$valid_reads = array( 'legacy', 'merged', 'pulse' );
			$checks[]    = self::check(
				__( 'Read mode is valid', 'wp-ulike' ),
				in_array( $read_mode, $valid_reads, true ),
				$read_mode,
				__( 'Read mode is unrecognized. Reload WP ULike settings or contact support.', 'wp-ulike' )
			);

			// Pulse table existence when relevant.
			if ( class_exists( 'WP_Ulike_Pulse_Schema' ) && 'legacy' !== $mode ) {
				$exists = WP_Ulike_Pulse_Schema::table_exists();
				$checks[] = self::check(
					__( 'Pulse table exists', 'wp-ulike' ),
					$exists,
					$exists ? WP_Ulike_Pulse_Schema::table() : 'missing',
					__( 'The ulike_pulse table is missing. Deactivate and reactivate WP ULike once to create it.', 'wp-ulike' )
				);

				if ( $exists ) {
					$columns_ok = self::pulse_columns_ok();
					$checks[]   = self::check(
						__( 'Pulse table schema is current', 'wp-ulike' ),
						$columns_ok['ok'],
						$columns_ok['detail'],
						__( 'Pulse table columns are out of date. Reinstall WP ULike or run the schema upgrade.', 'wp-ulike' )
					);
				}
			}

			// Writes route to pulse when in dual/pulse.
			if ( function_exists( 'wp_ulike_writes_pulse' ) && 'legacy' !== $mode ) {
				$writes = wp_ulike_writes_pulse();
				$checks[] = self::check(
					__( 'New votes write to Pulse storage', 'wp-ulike' ),
					$writes,
					$writes ? 'yes' : 'no',
					__( 'New votes are not routing to the Pulse table. Check the storage mode configuration.', 'wp-ulike' )
				);
			}

			// Migration status.
			if ( class_exists( 'WP_Ulike_Pulse_Config' ) ) {
				$config     = WP_Ulike_Pulse_Config::get();
				$status     = isset( $config['migration']['status'] ) ? $config['migration']['status'] : 'idle';
				$progress   = class_exists( 'WP_Ulike_Pulse_Sync' ) ? WP_Ulike_Pulse_Sync::get_progress() : array();
				$complete   = class_exists( 'WP_Ulike_Pulse_Sync' ) ? WP_Ulike_Pulse_Sync::is_sync_complete( $progress ) : ( 'done' === $status );
				$needs      = function_exists( 'wp_ulike_pulse_needs_migration' ) && wp_ulike_pulse_needs_migration();
				$label      = class_exists( 'WP_Ulike_Pulse_Sync' ) ? WP_Ulike_Pulse_Sync::progress_label( $progress ) : $status;

				$detail = $complete ? 'complete' : $label;

				if ( 'pulse' === $mode ) {
					$checks[] = self::pass( __( 'Migration status', 'wp-ulike' ), 'complete — pulse mode active' );
				} elseif ( $needs && ! $complete ) {
					$checks[] = self::warn(
						__( 'Migration status', 'wp-ulike' ),
						$detail,
						__( 'Migration is pending or in progress. Counts and buttons keep working — finish the upgrade when convenient for faster reads.', 'wp-ulike' )
					);
				} else {
					$checks[] = self::pass( __( 'Migration status', 'wp-ulike' ), $detail );
				}

				// dual_since sanity.
				if ( 'dual' === $mode ) {
					$since = WP_Ulike_Pulse_Config::dual_since();
					$checks[] = self::check(
						__( 'Dual-mode cutoff (dual_since) is set', 'wp-ulike' ),
						! empty( $since ),
						$since ? $since : 'missing',
						__( 'Dual mode is active but the cutoff timestamp is missing. Save the storage settings or contact support.', 'wp-ulike' )
					);
				}
			}

			return $checks;
		}

		/**
		 * Required-table existence checks (pulse + legacy, mode-aware).
		 *
		 * @return array<int,array{label:string,status:string,detail:string,hint:string}>
		 */
		private static function tables_checks() {
			$checks = array();

			if ( class_exists( 'WP_Ulike_Overview' ) ) {
				$required = WP_Ulike_Overview::get_required_tables();
			} elseif ( class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
				$required = WP_Ulike_Pulse_Registry::legacy_health_tables();
			} else {
				$required = array();
			}

			foreach ( $required as $label => $table_name ) {
				$exists   = WP_Ulike_Overview::table_exists( $table_name );
				$checks[] = self::check(
					sprintf( /* translators: %s: table label */ __( 'Table: %s', 'wp-ulike' ), $label ),
					$exists,
					$exists ? $table_name : 'missing',
					__( 'A required table is missing. Use “Repair database tables” on the Help page, or deactivate and reactivate WP ULike once.', 'wp-ulike' )
				);
			}

			// Legacy row counts (informational, approximate) when legacy reads are still active.
			// Uses information_schema.TABLE_ROWS (MySQL metadata) instead of COUNT(*) so this
			// stays instant even on tables with millions of rows.
			if ( class_exists( 'WP_Ulike_Pulse_Registry' ) && function_exists( 'wp_ulike_pulse_reads_legacy_votes' ) && wp_ulike_pulse_reads_legacy_votes() ) {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $source ) {
					if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						continue;
					}
					$count    = self::approx_table_rows( $source['table'] );
					$checks[] = self::pass(
						sprintf( /* translators: 1: legacy source slug */ __( 'Legacy rows: %s (approx.)', 'wp-ulike' ), $slug ),
						number_format_i18n( $count )
					);
				}
			}

			return $checks;
		}

		/**
		 * Dual-mode legacy-vs-pulse consistency checks.
		 *
		 * @param string $mode Storage mode.
		 * @return array<int,array{label:string,status:string,detail:string,hint:string}>
		 */
		private static function consistency_checks( $mode ) {
			$checks = array();

			if ( 'legacy' === $mode ) {
				$checks[] = self::pass( __( 'Mode', 'wp-ulike' ), __( 'legacy — single source, no merge needed', 'wp-ulike' ) );
				return $checks;
			}

			if ( ! class_exists( 'WP_Ulike_Pulse_Sync' ) ) {
				$checks[] = self::warn( __( 'Pulse Sync', 'wp-ulike' ), __( 'unavailable', 'wp-ulike' ), __( 'Pulse sync class not loaded. Update WP ULike to the latest version.', 'wp-ulike' ) );
				return $checks;
			}

			$progress = WP_Ulike_Pulse_Sync::get_progress();
			$complete = WP_Ulike_Pulse_Sync::is_sync_complete( $progress );

			if ( ! $complete ) {
				$checks[] = self::warn(
					__( 'Migration in progress', 'wp-ulike' ),
					__( 'not complete', 'wp-ulike' ),
					__( 'Legacy and Pulse counts are expected to differ until the migration finishes. Counts and buttons keep working meanwhile. Finish the upgrade from the storage upgrade page when convenient.', 'wp-ulike' )
				);
				return $checks;
			}

			$verify = WP_Ulike_Pulse_Sync::verify( false );
			$ok     = ! empty( $verify['ok'] );
			$issues = isset( $verify['issues'] ) ? (array) $verify['issues'] : array();

			if ( $ok ) {
				$checks[] = self::pass( __( 'Legacy vs Pulse row counts', 'wp-ulike' ), __( 'matched', 'wp-ulike' ) );
			} else {
				$detail = empty( $issues ) ? __( 'mismatch', 'wp-ulike' ) : wp_json_encode( $issues );
				$checks[] = self::fail(
					__( 'Legacy vs Pulse row counts', 'wp-ulike' ),
					$detail,
					__( 'Counts differ between legacy and Pulse tables. Run “wp ulike pulse verify --deep” from WP-CLI, or re-run the migration from the storage upgrade page.', 'wp-ulike' )
				);
			}

			return $checks;
		}

		/**
		 * Frontend AJAX endpoint + asset checks.
		 *
		 * @return array<int,array{label:string,status:string,detail:string,hint:string}>
		 */
		private static function frontend_checks() {
			$checks = array();

			// wp_ulike_process action registered (frontend vote handler).
			$registered = has_action( 'wp_ajax_wp_ulike_process' ) || has_action( 'wp_ajax_nopriv_wp_ulike_process' );
			$checks[]   = self::check(
				__( 'Vote AJAX endpoint registered', 'wp-ulike' ),
				$registered,
				$registered ? 'wp_ulike_process' : 'missing',
				__( 'The vote endpoint is not registered. Reinstall WP ULike or check for a conflicting mu-plugin.', 'wp-ulike' )
			);

			// Frontend script file exists on disk.
			$js_path = WP_ULIKE_ASSETS_DIR . '/js/wp-ulike.min.js';
			if ( ! file_exists( $js_path ) ) {
				$js_path = WP_ULIKE_ASSETS_DIR . '/js/wp-ulike.js';
			}
			$js_exists = file_exists( $js_path );
			$checks[]  = self::check(
				__( 'Frontend script file exists', 'wp-ulike' ),
				$js_exists,
				$js_exists ? basename( $js_path ) : 'missing',
				__( 'wp-ulike.js is missing from the assets folder. Reinstall WP ULike.', 'wp-ulike' )
			);

			// CSS file exists.
			$css_path  = WP_ULIKE_ASSETS_DIR . '/css/wp-ulike.min.css';
			if ( ! file_exists( $css_path ) ) {
				$css_path = WP_ULIKE_ASSETS_DIR . '/css/wp-ulike.css';
			}
			$css_exists = file_exists( $css_path );
			$checks[]   = self::check(
				__( 'Frontend stylesheet file exists', 'wp-ulike' ),
				$css_exists,
				$css_exists ? basename( $css_path ) : 'missing',
				__( 'wp-ulike.css is missing from the assets folder. Reinstall WP ULike.', 'wp-ulike' )
			);

			// Total + today counts (informational, confirms reads work).
			if ( function_exists( 'wp_ulike_count_all_logs' ) ) {
				try {
					$total = (int) wp_ulike_count_all_logs();
					$today = (int) wp_ulike_count_all_logs( 'today' );
					$checks[] = self::pass(
						__( 'Read path returns counts', 'wp-ulike' ),
						sprintf( /* translators: 1: total, 2: today */ __( 'total %1$s · today %2$s', 'wp-ulike' ), number_format_i18n( $total ), number_format_i18n( $today ) )
					);
				} catch ( Exception $e ) {
					$checks[] = self::fail( __( 'Read path returns counts', 'wp-ulike' ), $e->getMessage(), __( 'Reading counts raised an error. Check the error log and table integrity.', 'wp-ulike' ) );
				}
			}

			return $checks;
		}

		/**
		 * Environment + integrations checks.
		 *
		 * @return array<int,array{label:string,status:string,detail:string,hint:string}>
		 */
		private static function environment_checks() {
			global $wp_version;
			$checks = array();

			$checks[] = self::pass( __( 'WordPress version', 'wp-ulike' ), $wp_version );

			$php_ok = version_compare( PHP_VERSION, '7.4', '>=' );
			$checks[] = self::check(
				__( 'PHP version', 'wp-ulike' ),
				$php_ok,
				PHP_VERSION,
				__( 'WP ULike requires PHP 7.4 or higher. Upgrade PHP on your host for security and performance.', 'wp-ulike' )
			);

			// Memory limit.
			$limit = ini_get( 'memory_limit' );
			$bytes = wp_convert_hr_to_bytes( $limit );
			$mem_ok = $bytes >= ( 128 * 1024 * 1024 ) || -1 === $bytes;
			$checks[] = self::check(
				__( 'PHP memory limit', 'wp-ulike' ),
				$mem_ok,
				$limit ? $limit : 'unknown',
				__( 'Memory limit is below 128M. Increase to 256M on your host for safer operation on busy sites.', 'wp-ulike' )
			);

			// Active theme.
			$theme = wp_get_theme();
			$checks[] = self::pass( __( 'Active theme', 'wp-ulike' ), $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) );

			// Integrations.
			$integrations = array();
			if ( class_exists( 'WooCommerce' ) ) {
				$integrations[] = 'WooCommerce';
			}
			if ( function_exists( 'buddypress' ) ) {
				$integrations[] = 'BuddyPress';
			}
			if ( function_exists( 'is_bbpress' ) ) {
				$integrations[] = 'bbPress';
			}
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				$integrations[] = 'EDD';
			}
			$checks[] = self::pass( __( 'Detected integrations', 'wp-ulike' ), $integrations ? implode( ', ', $integrations ) : __( 'none', 'wp-ulike' ) );

			// Caching/optimization plugins (relevant for "button does nothing" cases).
			$cache_plugins = self::detect_cache_plugins();
			$checks[] = self::pass(
				__( 'Caching / optimization plugins', 'wp-ulike' ),
				$cache_plugins ? implode( ', ', $cache_plugins ) : __( 'none detected', 'wp-ulike' )
			);

			// Action Scheduler availability (used by migration background runner).
			if ( class_exists( 'ActionScheduler_Versions' ) || class_exists( 'ActionScheduler_Store' ) ) {
				$checks[] = self::pass( __( 'Action Scheduler', 'wp-ulike' ), __( 'available — background migration supported', 'wp-ulike' ) );
			} else {
				$checks[] = self::warn(
					__( 'Action Scheduler', 'wp-ulike' ),
					__( 'not available', 'wp-ulike' ),
					__( 'Action Scheduler is not present. Migration will use WP-Cron instead, which is slower on large sites. Install WooCommerce or another AS provider for faster background migration.', 'wp-ulike' )
				);
			}

			return $checks;
		}

		/**
		 * Detect common caching/optimization plugins and hosting-level caches.
		 *
		 * Primary signal: active plugin folder slugs (reliable, no autoload side
		 * effects). Secondary: well-known constants for hosting/mu-plugin caches
		 * that do not register in active_plugins (Kinsta, Endurance, WP Engine).
		 * Informational only — used to contextualize "button does nothing" cases.
		 *
		 * @return array<int,string>
		 */
		private static function detect_cache_plugins() {
			$found = array();

			// Plugin folder slug => display name. Extend this map to add more.
			$plugin_map = array(
				'litespeed-cache'          => 'LiteSpeed Cache',
				'w3-total-cache'           => 'W3 Total Cache',
				'wp-rocket'                => 'WP Rocket',
				'autoptimize'              => 'Autoptimize',
				'wp-optimize'              => 'WP Optimize',
				'breeze'                   => 'Breeze',
				'wp-fastest-cache'         => 'WP Fastest Cache',
				'wp-super-cache'           => 'WP Super Cache',
				'comet-cache'              => 'Comet Cache',
				'cache-enabler'            => 'Cache Enabler',
				'wp-cloudflare-page-cache' => 'Cloudflare APO',
				'cloudflare'               => 'Cloudflare',
				'nitropack'                => 'NitroPack',
				'hummingbird-performance'  => 'Hummingbird',
				'wp-hummingbird'           => 'Hummingbird',
				'borlabs-cache'            => 'Borlabs Cache',
				'borlabs-cache-pro'        => 'Borlabs Cache Pro',
				'swift-performance'        => 'Swift Performance',
				'swift-performance-lite'   => 'Swift Performance Lite',
				'flyingpress'              => 'FlyingPress',
				'perfmatters'              => 'Perfmatters',
				'wp-meteor'                => 'WP Meteor',
				'fast-velocity-minify'     => 'Fast Velocity Minify',
				'sg-cachepress'            => 'SiteGround Optimizer',
				'wp-asset-clean-up'        => 'Asset CleanUp',
				'wp-asset-clean-up-pro'    => 'Asset CleanUp Pro',
				'onecom-performance'       => 'One.com Performance',
				'endurance-page-cache'     => 'Endurance Page Cache',
			);

			$active = self::active_plugin_paths();
			foreach ( $active as $path ) {
				$slug = dirname( $path );
				if ( isset( $plugin_map[ $slug ] ) ) {
					$found[ $plugin_map[ $slug ] ] = true;
				}
			}

			// Hosting-level / mu-plugin caches (not listed in active_plugins).
			if ( defined( 'KINSTA_CACHE_VERSION' ) || class_exists( 'Kinsta\Cache' ) ) {
				$found['Kinsta'] = true;
			}
			if ( defined( 'EPC_VERSION' ) || class_exists( 'Endurance_Page_Cache' ) ) {
				$found['Endurance Page Cache'] = true;
			}
			if ( defined( 'PWP_NAME' ) || defined( 'WPE_PLUGIN_VERSION' ) ) {
				$found['WP Engine'] = true;
			}
			if ( defined( 'FLYWHEEL_CONFIG' ) || class_exists( 'Flywheel\Cache' ) ) {
				$found['Flywheel'] = true;
			}

			$names = array_keys( $found );
			sort( $names );
			return $names;
		}

		/**
		 * Collect active plugin paths across single site and multisite network.
		 *
		 * @return array<int,string>
		 */
		private static function active_plugin_paths() {
			$paths = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$network = (array) get_site_option( 'active_sitewide_plugins', array() );
				foreach ( array_keys( $network ) as $path ) {
					$paths[] = $path;
				}
			}

			// mu-plugins are autoloaded; include their files for detection.
			if ( defined( 'WPMU_PLUGIN_DIR' ) && is_dir( WPMU_PLUGIN_DIR ) ) {
				foreach ( (array) glob( WPMU_PLUGIN_DIR . '/*.php' ) as $file ) {
					$paths[] = basename( $file );
				}
			}

			return array_values( array_filter( $paths ) );
		}

		/**
		 * Fast approximate row count via MySQL metadata (no table scan).
		 *
		 * information_schema.TABLE_ROWS is maintained by MySQL and is instant
		 * regardless of table size; for InnoDB it is approximate (±10-40%),
		 * which is fine for an informational diagnostics row.
		 *
		 * @param string $table Full table name.
		 * @return int
		 */
		private static function approx_table_rows( $table ) {
			global $wpdb;
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
					$table
				)
			);
		}

		/**
		 * Check that the ulike_pulse table has the expected columns.
		 *
		 * @return array{ok:bool,detail:string}
		 */
		private static function pulse_columns_ok() {
			global $wpdb;

			$expected = array( 'item_id', 'item_type', 'engagement_kind', 'engagement_key', 'status', 'value', 'date_time', 'user_id' );
			$table    = WP_Ulike_Pulse_Schema::table();
			$columns  = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCron -- read-only diagnostics.

			if ( ! is_array( $columns ) ) {
				return array( 'ok' => false, 'detail' => 'unknown' );
			}

			$missing = array_diff( $expected, $columns );
			if ( empty( $missing ) ) {
				return array( 'ok' => true, 'detail' => count( $columns ) . ' columns' );
			}

			return array( 'ok' => false, 'detail' => 'missing: ' . implode( ', ', $missing ) );
		}

		/**
		 * Build a check result row.
		 *
		 * @param string $label  Check label.
		 * @param bool   $ok     Passed?
		 * @param string $detail Detail string.
		 * @param string $hint   Hint shown on warn/fail.
		 * @return array{label:string,status:string,detail:string,hint:string}
		 */
		private static function check( $label, $ok, $detail, $hint = '' ) {
			return array(
				'label'  => $label,
				'status' => $ok ? 'pass' : 'fail',
				'detail' => (string) $detail,
				'hint'   => $ok ? '' : (string) $hint,
			);
		}

		/**
		 * Explicit pass row.
		 *
		 * @param string $label  Label.
		 * @param string $detail Detail.
		 * @return array{label:string,status:string,detail:string,hint:string}
		 */
		private static function pass( $label, $detail ) {
			return array( 'label' => $label, 'status' => 'pass', 'detail' => (string) $detail, 'hint' => '' );
		}

		/**
		 * Explicit warn row.
		 *
		 * @param string $label  Label.
		 * @param string $detail Detail.
		 * @param string $hint   Hint.
		 * @return array{label:string,status:string,detail:string,hint:string}
		 */
		private static function warn( $label, $detail, $hint = '' ) {
			return array( 'label' => $label, 'status' => 'warn', 'detail' => (string) $detail, 'hint' => (string) $hint );
		}

		/**
		 * Explicit fail row.
		 *
		 * @param string $label  Label.
		 * @param string $detail Detail.
		 * @param string $hint   Hint.
		 * @return array{label:string,status:string,detail:string,hint:string}
		 */
		private static function fail( $label, $detail, $hint = '' ) {
			return array( 'label' => $label, 'status' => 'fail', 'detail' => (string) $detail, 'hint' => (string) $hint );
		}

		/**
		 * Plain-text report for "copy to clipboard" / support.
		 *
		 * @param array $report Result of run_checks().
		 * @return string
		 */
		public static function format_text_report( $report ) {
			$lines   = array();
			$lines[] = 'WP ULike diagnostics — ' . ( $report['generated_at'] ?? gmdate( 'c' ) );
			$lines[] = 'Mode: ' . ( $report['mode'] ?? '' ) . ' · Read: ' . ( $report['read_mode'] ?? '' );
			$summary = $report['summary'] ?? array( 'pass' => 0, 'warn' => 0, 'fail' => 0 );
			$lines[] = sprintf( 'Summary: %d pass, %d warn, %d fail', $summary['pass'], $summary['warn'], $summary['fail'] );
			$lines[] = '';

			foreach ( (array) ( $report['groups'] ?? array() ) as $group ) {
				$lines[] = '## ' . ( $group['label'] ?? '' );
				foreach ( (array) ( $group['checks'] ?? array() ) as $check ) {
					$tag    = strtoupper( substr( $check['status'], 0, 4 ) );
					$lines[] = sprintf( '[%s] %s — %s', $tag, $check['label'], $check['detail'] );
					if ( ! empty( $check['hint'] ) ) {
						$lines[] = '       hint: ' . $check['hint'];
					}
				}
				$lines[] = '';
			}

			return implode( "\n", $lines );
		}
	}
}
