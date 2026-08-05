<?php
/**
 * WP ULike — Site Health tests and Info dump.
 *
 * Single general home for plugin health: Tools → Site Health (Status + Info).
 * Help keeps repair / migrate / cleanup actions and links here.
 *
 * @package WP_ULike
 * @since   5.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Health' ) ) {

	/**
	 * Site Health integration and shared snapshot builders.
	 */
	class WP_Ulike_Health {

		/**
		 * Bootstrap hooks.
		 *
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_filter( 'site_status_tests', array( __CLASS__, 'register_tests' ) );
			add_filter( 'debug_information', array( __CLASS__, 'register_debug_information' ) );
		}

		/**
		 * Tools → Site Health URL (Status tab).
		 *
		 * @return string
		 */
		public static function get_site_health_url() {
			return admin_url( 'site-health.php' );
		}

		/**
		 * Tools → Site Health Info tab URL.
		 *
		 * @return string
		 */
		public static function get_site_health_info_url() {
			return admin_url( 'site-health.php?tab=debug' );
		}

		/**
		 * Pulse storage / cleanup page URL when available.
		 *
		 * @return string
		 */
		public static function get_storage_url() {
			if ( class_exists( 'WP_Ulike_Pulse_Admin' ) && method_exists( 'WP_Ulike_Pulse_Admin', 'get_page_url' ) ) {
				return WP_Ulike_Pulse_Admin::get_page_url();
			}

			return class_exists( 'WP_Ulike_Overview' )
				? WP_Ulike_Overview::get_about_url()
				: admin_url( 'admin.php?page=wp-ulike-about' );
		}

		/**
		 * Help page URL (repair / migrate actions).
		 *
		 * @return string
		 */
		public static function get_help_url() {
			return class_exists( 'WP_Ulike_Overview' )
				? WP_Ulike_Overview::get_about_url()
				: admin_url( 'admin.php?page=wp-ulike-about' );
		}

		/**
		 * Register Site Health Status tests.
		 *
		 * @param array $tests Tests.
		 * @return array
		 */
		public static function register_tests( $tests ) {
			$tests['direct']['wp_ulike_database_tables'] = array(
				'label' => __( 'WP ULike database tables', 'wp-ulike' ),
				'test'  => array( __CLASS__, 'test_database_tables' ),
			);

			$tests['direct']['wp_ulike_storage'] = array(
				'label' => __( 'WP ULike storage & migration', 'wp-ulike' ),
				'test'  => array( __CLASS__, 'test_storage' ),
			);

			$tests['direct']['wp_ulike_legacy_cleanup'] = array(
				'label' => __( 'WP ULike old log tables', 'wp-ulike' ),
				'test'  => array( __CLASS__, 'test_legacy_cleanup' ),
			);

			return $tests;
		}

		/**
		 * Critical: required tables present.
		 *
		 * @return array
		 */
		public static function test_database_tables() {
			$report = class_exists( 'WP_Ulike_Overview' )
				? WP_Ulike_Overview::get_tables_health()
				: array( 'tables_ok' => true, 'missing_tables' => array() );

			$badge = array(
				'label' => __( 'WP ULike', 'wp-ulike' ),
				'color' => 'blue',
			);

			if ( ! empty( $report['tables_ok'] ) ) {
				return array(
					'label'       => __( 'WP ULike database tables are installed', 'wp-ulike' ),
					'status'      => 'good',
					'badge'       => $badge,
					'description' => '<p>' . esc_html__( 'All database tables required by WP ULike are present.', 'wp-ulike' ) . '</p>',
					'actions'     => '',
					'test'        => 'wp_ulike_database_tables',
				);
			}

			$missing = ! empty( $report['missing_tables'] )
				? implode( ', ', array_map( 'esc_html', (array) $report['missing_tables'] ) )
				: '';

			$description = '<p>' . esc_html__( 'Some WP ULike tables need repair. Use Repair on the Overview page, or deactivate and reactivate the plugin once.', 'wp-ulike' ) . '</p>';
			if ( $missing ) {
				$description .= '<p>' . sprintf(
					/* translators: %s: comma-separated table labels */
					esc_html__( 'Missing: %s', 'wp-ulike' ),
					$missing
				) . '</p>';
			}

			return array(
				'label'       => __( 'WP ULike database tables are missing', 'wp-ulike' ),
				'status'      => 'critical',
				'badge'       => array(
					'label' => __( 'WP ULike', 'wp-ulike' ),
					'color' => 'red',
				),
				'description' => $description,
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( self::get_help_url() ),
					esc_html__( 'Open Overview', 'wp-ulike' )
				),
				'test'        => 'wp_ulike_database_tables',
			);
		}

		/**
		 * Recommended: storage mode, migration, dual consistency.
		 *
		 * @return array
		 */
		public static function test_storage() {
			$badge = array(
				'label' => __( 'WP ULike', 'wp-ulike' ),
				'color' => 'blue',
			);

			$snapshot = self::get_storage_snapshot();
			$issues   = array();

			if ( empty( $snapshot['mode_valid'] ) ) {
				$issues[] = __( 'Storage mode is unrecognized.', 'wp-ulike' );
			}

			if ( empty( $snapshot['read_mode_valid'] ) ) {
				$issues[] = __( 'Read mode is unrecognized.', 'wp-ulike' );
			}

			if ( 'legacy' !== $snapshot['mode'] ) {
				if ( empty( $snapshot['pulse_table_exists'] ) ) {
					$issues[] = __( 'The Pulse table is missing. Deactivate and reactivate WP ULike once to create it.', 'wp-ulike' );
				} elseif ( empty( $snapshot['pulse_schema_ok'] ) ) {
					$issues[] = __( 'Pulse table columns are out of date.', 'wp-ulike' );
				}

				if ( empty( $snapshot['writes_pulse'] ) ) {
					$issues[] = __( 'New votes are not routing to Pulse storage.', 'wp-ulike' );
				}
			}

			if ( ! empty( $snapshot['migration_pending'] ) ) {
				$issues[] = __( 'Storage migration is pending or in progress. Counts and buttons keep working — finish the upgrade when convenient.', 'wp-ulike' );
			}

			if ( 'dual' === $snapshot['mode'] && empty( $snapshot['dual_since'] ) ) {
				$issues[] = __( 'Dual mode is active but the cutoff timestamp (dual_since) is missing.', 'wp-ulike' );
			}

			if ( ! empty( $snapshot['consistency_mismatch'] ) ) {
				$issues[] = __( 'Legacy and Pulse row counts do not match after migration. Re-run verification or the storage upgrade.', 'wp-ulike' );
			}

			if ( empty( $issues ) ) {
				$detail = sprintf(
					/* translators: 1: storage mode, 2: read mode */
					__( 'Storage mode: %1$s · Read mode: %2$s.', 'wp-ulike' ),
					$snapshot['mode'],
					$snapshot['read_mode']
				);

				return array(
					'label'       => __( 'WP ULike storage looks healthy', 'wp-ulike' ),
					'status'      => 'good',
					'badge'       => $badge,
					'description' => '<p>' . esc_html( $detail ) . '</p>',
					'actions'     => '',
					'test'        => 'wp_ulike_storage',
				);
			}

			$list = '<ul>';
			foreach ( $issues as $issue ) {
				$list .= '<li>' . esc_html( $issue ) . '</li>';
			}
			$list .= '</ul>';

			$actions = sprintf(
				'<p><a href="%1$s">%2$s</a> · <a href="%3$s">%4$s</a></p>',
				esc_url( self::get_storage_url() ),
				esc_html__( 'Open storage upgrade', 'wp-ulike' ),
				esc_url( self::get_help_url() ),
				esc_html__( 'Open Overview', 'wp-ulike' )
			);

			return array(
				'label'       => __( 'WP ULike storage needs attention', 'wp-ulike' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'WP ULike', 'wp-ulike' ),
					'color' => 'orange',
				),
				'description' => $list,
				'actions'     => $actions,
				'test'        => 'wp_ulike_storage',
			);
		}

		/**
		 * Recommended: leftover legacy tables after Pulse cutover.
		 *
		 * @return array
		 */
		public static function test_legacy_cleanup() {
			$badge = array(
				'label' => __( 'WP ULike', 'wp-ulike' ),
				'color' => 'blue',
			);

			$mode    = function_exists( 'wp_ulike_pulse_mode' ) ? wp_ulike_pulse_mode() : 'legacy';
			$legacy  = class_exists( 'WP_Ulike_Pulse_Legacy_Cleanup' )
				? WP_Ulike_Pulse_Legacy_Cleanup::existing_legacy_tables()
				: array();
			$has_old = ! empty( $legacy );

			if ( 'pulse' !== $mode || ! $has_old ) {
				$description = ( 'pulse' === $mode )
					? __( 'No leftover classic like tables were found. Storage cleanup is complete.', 'wp-ulike' )
					: __( 'Classic like tables are still in use (or migration has not finished). Nothing to remove yet.', 'wp-ulike' );

				return array(
					'label'       => __( 'WP ULike has no leftover log tables to remove', 'wp-ulike' ),
					'status'      => 'good',
					'badge'       => $badge,
					'description' => '<p>' . esc_html( $description ) . '</p>',
					'actions'     => '',
					'test'        => 'wp_ulike_legacy_cleanup',
				);
			}

			$legacy_count = count( $legacy );

			return array(
				'label'       => __( 'WP ULike can free disk space by removing old log tables', 'wp-ulike' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'WP ULike', 'wp-ulike' ),
					'color' => 'orange',
				),
				'description' => '<p>' . esc_html__( 'Like records already use the faster storage. Remove the old log tables when you are ready to reclaim disk space. Back up your database first.', 'wp-ulike' ) . '</p>'
					. '<p>' . sprintf(
						/* translators: %d: number of leftover log tables */
						esc_html( _n( '%d old log table is still present.', '%d old log tables are still present.', $legacy_count, 'wp-ulike' ) ),
						$legacy_count
					) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( self::get_storage_url() ),
					esc_html__( 'Review cleanup', 'wp-ulike' )
				),
				'test'        => 'wp_ulike_legacy_cleanup',
			);
		}

		/**
		 * Site Health → Info section.
		 *
		 * @param array $info Debug information.
		 * @return array
		 */
		public static function register_debug_information( $info ) {
			// Section title stays translated (Site Health UI). Field labels below
			// are English-only support dump strings (same approach as Pulse Storage).
			$info['wp-ulike'] = array(
				'label'  => __( 'WP ULike', 'wp-ulike' ),
				'fields' => self::get_debug_fields(),
			);

			return $info;
		}

		/**
		 * Fields for Site Health Info (and Pro filter extension).
		 *
		 * Labels/values are English-only so they are not pulled into the
		 * translation catalog — hosts paste this dump for support.
		 *
		 * @return array<string,array<string,mixed>>
		 */
		public static function get_debug_fields() {
			global $wp_version;

			$storage  = self::get_storage_snapshot();
			$frontend = self::get_frontend_snapshot();
			$tables   = self::get_tables_snapshot();
			$theme    = wp_get_theme();

			$fields = array(
				'version' => array(
					'label' => 'Plugin version',
					'value' => WP_ULIKE_VERSION,
				),
				'wp_version' => array(
					'label' => 'WordPress version',
					'value' => $wp_version,
				),
				'php_version' => array(
					'label' => 'PHP version',
					'value' => PHP_VERSION,
				),
				'storage_mode' => array(
					'label' => 'Storage mode',
					'value' => $storage['mode'],
				),
				'read_mode' => array(
					'label' => 'Read mode',
					'value' => $storage['read_mode'],
				),
				'migration_status' => array(
					'label' => 'Migration status',
					'value' => $storage['migration_label'],
				),
				'dual_since' => array(
					'label' => 'Dual-mode cutoff (dual_since)',
					'value' => $storage['dual_since'] ? $storage['dual_since'] : 'n/a',
				),
				'pulse_table' => array(
					'label' => 'Pulse storage',
					'value' => $storage['pulse_table_detail'],
				),
				'writes_pulse' => array(
					'label' => 'Writes to Pulse',
					'value' => ! empty( $storage['writes_pulse'] ) ? 'yes' : 'no',
				),
				'legacy_tables' => array(
					'label' => 'Legacy log tables',
					'value' => ! empty( $tables['legacy_count'] )
						? (int) $tables['legacy_count'] . ' present'
						: 'none',
				),
				'required_tables' => array(
					'label' => 'Required tables',
					'value' => ! empty( $tables['missing'] )
						? count( (array) $tables['missing'] ) . ' need repair (' . implode( ', ', array_map( 'strval', (array) $tables['missing'] ) ) . ')'
						: 'all present',
				),
				'approx_legacy_rows' => array(
					'label' => 'Legacy rows (approx.)',
					'value' => ! empty( $tables['approx_legacy_rows'] )
						? $tables['approx_legacy_rows']
						: 'n/a',
				),
				'vote_ajax' => array(
					'label' => 'Vote AJAX endpoint',
					'value' => ! empty( $frontend['ajax_registered'] ) ? 'ready' : 'unavailable',
				),
				'frontend_assets' => array(
					'label' => 'Frontend assets on disk',
					'value' => ! empty( $frontend['assets_ok'] ) ? 'yes' : 'JS or CSS not found',
				),
				'active_theme' => array(
					'label' => 'Active theme',
					'value' => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
				),
				'cache_plugins' => array(
					'label' => 'Caching / optimization plugins',
					'value' => ! empty( $frontend['cache_plugins'] )
						? implode( ', ', $frontend['cache_plugins'] )
						: 'none detected',
				),
				'integrations' => array(
					'label' => 'Detected integrations',
					'value' => ! empty( $frontend['integrations'] )
						? implode( ', ', $frontend['integrations'] )
						: 'none',
				),
			);

			/**
			 * Filter Site Health Info fields for WP ULike.
			 *
			 * @param array $fields Field definitions for debug_information.
			 */
			return apply_filters( 'wp_ulike_site_health_info_fields', $fields );
		}

		/**
		 * Compact status for Overview → At a glance (English-only hints to keep PO small).
		 *
		 * @return array{value:string,state:string,hint:string}|array{}
		 */
		public static function get_overview_glance_status() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return array();
			}

			$tables = class_exists( 'WP_Ulike_Overview' )
				? WP_Ulike_Overview::get_tables_health()
				: array( 'tables_ok' => true );

			// Database row already covers missing tables — skip here.
			if ( empty( $tables['tables_ok'] ) ) {
				return array();
			}

			$storage = self::get_storage_snapshot();
			$legacy  = ( 'pulse' === $storage['mode'] && class_exists( 'WP_Ulike_Pulse_Legacy_Cleanup' ) )
				? WP_Ulike_Pulse_Legacy_Cleanup::legacy_tables_exist()
				: false;

			if ( ! empty( $storage['migration_pending'] ) || ! empty( $storage['consistency_mismatch'] ) ) {
				return array(
					'value' => __( 'Needs attention', 'wp-ulike' ),
					'state' => 'warn',
					'hint'  => 'Storage migration still in progress — see Site Health.',
				);
			}

			if ( $legacy ) {
				return array(
					'value' => __( 'Ready', 'wp-ulike' ),
					'state' => 'warn',
					'hint'  => 'Old log tables can be removed when you are ready.',
				);
			}

			// All clear: Site Health link in the card header is enough (no extra PO strings).
			return array();
		}

		/**
		 * Storage / migration snapshot for tests and Info.
		 *
		 * @return array<string,mixed>
		 */
		public static function get_storage_snapshot() {
			$mode      = function_exists( 'wp_ulike_pulse_mode' ) ? wp_ulike_pulse_mode() : 'legacy';
			$read_mode = function_exists( 'wp_ulike_pulse_read_mode' ) ? wp_ulike_pulse_read_mode() : 'legacy';

			$snapshot = array(
				'mode'                  => $mode,
				'read_mode'             => $read_mode,
				'mode_valid'            => in_array( $mode, array( 'legacy', 'dual', 'pulse' ), true ),
				'read_mode_valid'       => in_array( $read_mode, array( 'legacy', 'merged', 'pulse' ), true ),
				'pulse_table_exists'    => false,
				'pulse_schema_ok'       => true,
				'pulse_table_detail'    => 'n/a',
				'writes_pulse'          => 'legacy' === $mode ? false : ( function_exists( 'wp_ulike_writes_pulse' ) ? wp_ulike_writes_pulse() : false ),
				'migration_pending'     => false,
				'migration_label'       => 'idle',
				'dual_since'            => '',
				'consistency_mismatch'  => false,
			);

			if ( class_exists( 'WP_Ulike_Pulse_Schema' ) && 'legacy' !== $mode ) {
				$exists = WP_Ulike_Pulse_Schema::table_exists();
				$snapshot['pulse_table_exists'] = $exists;
				if ( $exists ) {
					$columns_ok = self::pulse_columns_ok();
					$snapshot['pulse_schema_ok'] = ! empty( $columns_ok['ok'] );
					// Never expose raw table names (esp. multisite prefixes) in Site Health Info.
					$snapshot['pulse_table_detail'] = ! empty( $columns_ok['ok'] )
						? 'ready'
						: 'needs schema update';
				} else {
					$snapshot['pulse_table_detail'] = 'not created';
					$snapshot['pulse_schema_ok']    = false;
				}
			} elseif ( class_exists( 'WP_Ulike_Pulse_Schema' ) ) {
				$snapshot['pulse_table_detail'] = WP_Ulike_Pulse_Schema::table_exists()
					? 'present (legacy mode)'
					: 'not required in legacy mode';
			}

			if ( class_exists( 'WP_Ulike_Pulse_Config' ) ) {
				$config   = WP_Ulike_Pulse_Config::get();
				$status   = isset( $config['migration']['status'] ) ? $config['migration']['status'] : 'idle';
				$progress = class_exists( 'WP_Ulike_Pulse_Sync' ) ? WP_Ulike_Pulse_Sync::get_progress() : array();
				$complete = class_exists( 'WP_Ulike_Pulse_Sync' )
					? WP_Ulike_Pulse_Sync::is_sync_complete( $progress )
					: ( 'done' === $status );
				$needs    = function_exists( 'wp_ulike_pulse_needs_migration' ) && wp_ulike_pulse_needs_migration();
				$label    = class_exists( 'WP_Ulike_Pulse_Sync' )
					? WP_Ulike_Pulse_Sync::progress_label( $progress )
					: $status;

				$snapshot['dual_since'] = (string) WP_Ulike_Pulse_Config::dual_since();

				if ( 'pulse' === $mode ) {
					$snapshot['migration_label']   = 'complete — pulse mode active';
					$snapshot['migration_pending'] = false;
				} elseif ( $needs && ! $complete ) {
					$snapshot['migration_label']   = $label ? $label : $status;
					$snapshot['migration_pending'] = true;
				} else {
					$snapshot['migration_label'] = $complete ? 'complete' : ( $label ? $label : $status );
				}

				if ( 'legacy' !== $mode && class_exists( 'WP_Ulike_Pulse_Sync' ) && $complete ) {
					$verify = WP_Ulike_Pulse_Sync::verify( false );
					$snapshot['consistency_mismatch'] = empty( $verify['ok'] );
				} elseif ( 'legacy' !== $mode && class_exists( 'WP_Ulike_Pulse_Sync' ) && ! $complete ) {
					// Expected mismatch while migrating — surfaced via migration_pending.
					$snapshot['consistency_mismatch'] = false;
				}
			}

			return $snapshot;
		}

		/**
		 * Table presence snapshot for Info.
		 *
		 * @return array{missing:string[],legacy_present:string[],approx_legacy_rows:string}
		 */
		public static function get_tables_snapshot() {
			$missing = array();
			if ( class_exists( 'WP_Ulike_Overview' ) ) {
				$health  = WP_Ulike_Overview::get_tables_health();
				$missing = (array) ( $health['missing_tables'] ?? array() );
			}

			$legacy_present = class_exists( 'WP_Ulike_Pulse_Legacy_Cleanup' )
				? WP_Ulike_Pulse_Legacy_Cleanup::existing_legacy_tables()
				: array();

			$approx_parts = array();
			if ( class_exists( 'WP_Ulike_Pulse_Registry' ) && function_exists( 'wp_ulike_pulse_reads_legacy_votes' ) && wp_ulike_pulse_reads_legacy_votes() ) {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $source ) {
					if ( ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
						continue;
					}
					$approx_parts[] = $slug . '=' . number_format_i18n( self::approx_table_rows( $source['table'] ) );
				}
			} elseif ( ! empty( $legacy_present ) && class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
				foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $slug => $source ) {
					if ( ! in_array( $source['table'], $legacy_present, true ) ) {
						continue;
					}
					$approx_parts[] = $slug . '=' . number_format_i18n( self::approx_table_rows( $source['table'] ) );
				}
			}

			return array(
				'missing'            => $missing, // Human labels only (Posts, Comments, …) — never raw DB names.
				'legacy_present'     => $legacy_present, // Internal; Info UI uses legacy_count.
				'legacy_count'       => count( $legacy_present ),
				'approx_legacy_rows' => $approx_parts ? implode( ', ', $approx_parts ) : '',
			);
		}

		/**
		 * Frontend / env snapshot for Info.
		 *
		 * @return array<string,mixed>
		 */
		public static function get_frontend_snapshot() {
			$js_path = WP_ULIKE_ASSETS_DIR . '/js/wp-ulike.min.js';
			if ( ! file_exists( $js_path ) ) {
				$js_path = WP_ULIKE_ASSETS_DIR . '/js/wp-ulike.js';
			}
			$css_path = WP_ULIKE_ASSETS_DIR . '/css/wp-ulike.min.css';
			if ( ! file_exists( $css_path ) ) {
				$css_path = WP_ULIKE_ASSETS_DIR . '/css/wp-ulike.css';
			}

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

			return array(
				'ajax_registered' => self::is_vote_ajax_ready(),
				'assets_ok'       => file_exists( $js_path ) && file_exists( $css_path ),
				'integrations'    => $integrations,
				'cache_plugins'   => self::detect_cache_plugins(),
			);
		}

		/**
		 * Vote AJAX hooks load only when admin-ajax runs (see WP_Ulike::includes).
		 * Site Health Info is a normal admin screen, so has_action() alone always false-negatives.
		 *
		 * @return bool
		 */
		private static function is_vote_ajax_ready() {
			if ( has_action( 'wp_ajax_wp_ulike_process' ) || has_action( 'wp_ajax_nopriv_wp_ulike_process' ) ) {
				return true;
			}

			return is_readable( WP_ULIKE_INC_DIR . '/hooks/frontend-ajax.php' );
		}

		/**
		 * @return array{ok:bool,detail:string}
		 */
		private static function pulse_columns_ok() {
			global $wpdb;

			if ( ! class_exists( 'WP_Ulike_Pulse_Schema' ) || ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return array( 'ok' => false, 'detail' => 'not created' );
			}

			$expected = array( 'item_id', 'item_type', 'engagement_kind', 'engagement_key', 'status', 'value', 'date_time', 'user_id' );
			$table    = WP_Ulike_Pulse_Schema::table();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- read-only health.
			$columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );

			if ( ! is_array( $columns ) ) {
				return array( 'ok' => false, 'detail' => 'unknown' );
			}

			$missing = array_diff( $expected, $columns );
			if ( empty( $missing ) ) {
				return array( 'ok' => true, 'detail' => 'ready' );
			}

			return array( 'ok' => false, 'detail' => count( $missing ) . ' columns need update' );
		}

		/**
		 * @param string $table Full table name.
		 * @return int
		 */
		private static function approx_table_rows( $table ) {
			global $wpdb;

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
					$table
				)
			);
		}

		/**
		 * @return array<int,string>
		 */
		private static function detect_cache_plugins() {
			$found = array();

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
				'sg-cachepress'            => 'SiteGround Optimizer',
				'perfmatters'              => 'Perfmatters',
				'flyingpress'              => 'FlyingPress',
			);

			$paths = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				foreach ( array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) as $path ) {
					$paths[] = $path;
				}
			}

			foreach ( $paths as $path ) {
				$slug = dirname( (string) $path );
				if ( isset( $plugin_map[ $slug ] ) ) {
					$found[ $plugin_map[ $slug ] ] = true;
				}
			}

			if ( defined( 'KINSTA_CACHE_ZONE' ) || defined( 'KINSTA_CACHE_VERSION' ) || class_exists( 'Kinsta\Cache' ) ) {
				$found['Kinsta'] = true;
			}
			if ( defined( 'PWP_NAME' ) || defined( 'WPE_PLUGIN_VERSION' ) ) {
				$found['WP Engine'] = true;
			}

			$names = array_keys( $found );
			sort( $names );

			return $names;
		}
	}
}
