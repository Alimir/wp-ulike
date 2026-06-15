<?php
/**
 * Overview screen data, health checks, and settings import/export.
 *
 * @package WP_ULike
 * @since   5.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Overview' ) ) {

	/**
	 * Overview page and related admin helpers.
	 */
	class WP_Ulike_Overview {

		/**
		 * About admin screen URL.
		 *
		 * @return string
		 */
		public static function get_about_url() {
			return admin_url( 'admin.php?page=wp-ulike-about' );
		}

		/**
		 * Settings screen URL with Optiwich tab slug.
		 *
		 * @param string $tab Section id (e.g. general, content-types).
		 * @return string
		 */
		public static function get_settings_url( $tab = 'general' ) {
			return admin_url(
				'admin.php?' . http_build_query(
					array(
						'page'           => 'wp-ulike-settings',
						'settings-page'  => sanitize_key( $tab ),
					),
					'',
					'&'
				)
			);
		}

		/**
		 * Value-based Pro upsell copy for Overview (free only).
		 *
		 * @param array $health Health report.
		 * @return array
		 */
		public static function get_pro_upsell_content( $health ) {
			$today_votes = (int) ( $health['today_votes'] ?? 0 );
			$log_count   = (int) ( $health['log_count'] ?? 0 );

			if ( $today_votes > 0 ) {
				$intro = sprintf(
					/* translators: %s: votes logged today */
					esc_html__( '%s votes today—great momentum. You already have likes and stats here; Pro adds richer dashboards, smart placement rules, and SEO markup when you want them.', 'wp-ulike' ),
					number_format_i18n( $today_votes )
				);
			} elseif ( $log_count > 0 ) {
				$intro = sprintf(
					/* translators: %s: total stored votes */
					esc_html__( '%s votes and counting. Free covers your day-to-day likes and statistics—Pro is the optional layer for automation, deep reports, and search visibility.', 'wp-ulike' ),
					number_format_i18n( $log_count )
				);
			} else {
				$intro = esc_html__( 'Likes, stats, and button styles are ready to go. Pro is there when you want automation, a full analytics dashboard, or star ratings in search results.', 'wp-ulike' );
			}

			$content = array(
				'headline'  => esc_html__( 'WP ULike Pro', 'wp-ulike' ),
				'intro'     => $intro,
				'footnote'  => esc_html__( 'Free stays fully capable for likes, stats, and customization—Pro is an add-on for the extras below.', 'wp-ulike' ),
				'cta_label' => esc_html__( 'See what Pro includes', 'wp-ulike' ),
				'features'  => array(
					array(
						'icon'        => 'chart-area',
						'title'       => esc_html__( 'Pro Statistics', 'wp-ulike' ),
						'description' => esc_html__( 'Interactive charts, world map, device breakdowns, and CSV exports—far beyond this page’s snapshot.', 'wp-ulike' ),
						'highlight'   => true,
					),
					array(
						'icon'        => 'layout',
						'title'       => esc_html__( 'Display Automation', 'wp-ulike' ),
						'description' => esc_html__( 'Rule-based placement on posts, WooCommerce, EDD, and custom hooks—no shortcode hunting.', 'wp-ulike' ),
					),
					array(
						'icon'        => 'star-filled',
						'title'       => esc_html__( 'Schema star ratings', 'wp-ulike' ),
						'description' => esc_html__( 'Turn like counts into star ratings in Google with built-in Schema.org and FAQ markup.', 'wp-ulike' ),
						'highlight'   => true,
					),
				),
			);

			return apply_filters( 'wp_ulike_about_pro_upsell', $content, $health );
		}

		/**
		 * View-model for the About admin template (free + Pro via filters).
		 *
		 * @return array
		 */
		public static function get_about_view_data() {
			$health     = self::get_health_report();
			$health     = self::apply_live_tables_health( $health );
			$is_pro     = defined( 'WP_ULIKE_PRO_VERSION' );
			$pro_label  = $is_pro && defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : '';

			$quick_actions = array(
				array(
					'label'  => esc_html__( 'Settings', 'wp-ulike' ),
					'url'    => self::get_settings_url( 'content-types' ),
					'icon'   => 'admin-settings',
					'primary'=> false,
				),
				array(
					'label'  => esc_html__( 'Customize buttons', 'wp-ulike' ),
					'url'    => admin_url( 'admin.php?page=wp-ulike-customize' ),
					'icon'   => 'admin-appearance',
					'primary'=> false,
				),
				array(
					'label'  => esc_html__( 'Statistics', 'wp-ulike' ),
					'url'    => admin_url( 'admin.php?page=wp-ulike-statistics' ),
					'icon'   => 'chart-bar',
					'primary'=> false,
				),
			);

			if ( ! empty( $health['preview_url'] ) ) {
				$quick_actions[] = array(
					'label'   => esc_html__( 'View on site', 'wp-ulike' ),
					'url'     => $health['preview_url'],
					'icon'    => 'visibility',
					'primary' => true,
					'external'=> true,
				);
			}

			if ( $is_pro ) {
				$quick_actions[] = array(
					'label'   => esc_html__( 'Pro tools', 'wp-ulike' ),
					'url'     => admin_url( 'admin.php?page=wp-ulike-pro-tools' ),
					'icon'    => 'admin-tools',
					'primary' => false,
				);
			}

			$quick_actions = apply_filters( 'wp_ulike_about_quick_actions', $quick_actions, $health );

			// Pro can inject optional module cards via filter; none by default.
			$pro_modules = apply_filters( 'wp_ulike_about_pro_modules', array(), $health );

			$status_rows = array(
				array(
					'group'  => 'engagement',
					'label'  => esc_html__( 'Votes today', 'wp-ulike' ),
					'value'  => number_format_i18n( (int) ( $health['today_votes'] ?? 0 ) ),
					'state'  => ( (int) ( $health['today_votes'] ?? 0 ) ) > 0 ? 'good' : 'neutral',
				),
				array(
					'group'  => 'engagement',
					'label'  => esc_html__( 'New since last visit', 'wp-ulike' ),
					'value'  => number_format_i18n( (int) ( $health['new_votes'] ?? 0 ) ),
					'state'  => ( (int) ( $health['new_votes'] ?? 0 ) ) > 0 ? 'good' : 'neutral',
				),
				array(
					'group'  => 'engagement',
					'label'  => esc_html__( 'Total votes', 'wp-ulike' ),
					'value'  => number_format_i18n( (int) ( $health['log_count'] ?? 0 ) ),
					'state'  => ( (int) ( $health['log_count'] ?? 0 ) ) > 0 ? 'good' : 'neutral',
					'hint'   => esc_html__( 'Snapshot only—open Statistics for charts.', 'wp-ulike' ),
				),
				array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Posts', 'wp-ulike' ),
					'value'  => ! empty( $health['auto_display'] ) ? esc_html__( 'Auto-display on', 'wp-ulike' ) : esc_html__( 'Off / manual', 'wp-ulike' ),
					'state'  => ! empty( $health['auto_display'] ) ? 'good' : 'neutral',
				),
				array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Comments', 'wp-ulike' ),
					'value'  => ! empty( $health['comments_auto_display'] ) ? esc_html__( 'Auto-display on', 'wp-ulike' ) : esc_html__( 'Off', 'wp-ulike' ),
					'state'  => ! empty( $health['comments_auto_display'] ) ? 'good' : 'neutral',
				),
				array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Database', 'wp-ulike' ),
					'value'  => ! empty( $health['tables_ok'] ) ? esc_html__( 'Ready', 'wp-ulike' ) : esc_html__( 'Needs attention', 'wp-ulike' ),
					'state'  => ! empty( $health['tables_ok'] ) ? 'good' : 'bad',
					'hint'   => ! empty( $health['missing_tables'] )
						? sprintf(
							/* translators: %s: comma-separated table labels */
							esc_html__( 'Missing: %s', 'wp-ulike' ),
							esc_html( implode( ', ', (array) $health['missing_tables'] ) )
						)
						: '',
				),
			);

			if ( ! empty( $health['cache_enabled'] ) ) {
				$status_rows[] = array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Caching', 'wp-ulike' ),
					'value'  => esc_html__( 'Compatibility mode on', 'wp-ulike' ),
					'state'  => 'good',
				);
			}

			$status_rows = apply_filters( 'wp_ulike_about_status_rows', $status_rows, $health );

			// Supplementary setup hint last (free installs with no votes yet).
			if ( empty( $health['is_pro'] ) && (int) ( $health['log_count'] ?? 0 ) === 0 ) {
				$status_rows[] = array(
					'group'  => 'setup',
					'label'  => esc_html__( 'Quick check', 'wp-ulike' ),
					'value'  => esc_html__( 'Test on a single post, not the homepage', 'wp-ulike' ),
					'state'  => 'neutral',
					'hint'   => ! empty( $health['post_template_name'] )
						? sprintf(
							/* translators: 1: template name, 2: button position */
							esc_html__( 'Template: %1$s · Position: %2$s', 'wp-ulike' ),
							$health['post_template_name'],
							$health['post_button_position'] ?? ''
						)
						: '',
				);
			}

			$help_links = array(
				array(
					'title' => esc_html__( 'Documentation', 'wp-ulike' ),
					'desc'  => esc_html__( 'Setup, shortcodes, and developer hooks.', 'wp-ulike' ),
					'url'   => 'https://docs.wpulike.com/?utm_source=about-page&utm_medium=wp-dash',
					'icon'  => 'book',
				),
				array(
					'title' => esc_html__( 'Free vs Pro breakdown', 'wp-ulike' ),
					'desc'  => esc_html__( 'Compare free and Pro features before you upgrade.', 'wp-ulike' ),
					'url'   => add_query_arg(
						array(
							'utm_source' => 'about-page',
							'utm_medium' => 'wp-dash',
						),
						WP_ULIKE_PLUGIN_URI . 'upgrade/'
					),
					'icon'  => 'star-filled',
				),
				array(
					'title' => esc_html__( 'Support', 'wp-ulike' ),
					'desc'  => esc_html__( 'Get help from the WP ULike team.', 'wp-ulike' ),
					'url'   => WP_ULIKE_PLUGIN_URI . 'support/?utm_source=about-page&utm_medium=wp-dash',
					'icon'  => 'sos',
				),
				array(
					'title' => esc_html__( 'Leave a review', 'wp-ulike' ),
					'desc'  => esc_html__( 'Share feedback on WordPress.org.', 'wp-ulike' ),
					'url'   => 'https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5',
					'icon'  => 'star-filled',
				),
			);

			$help_links = apply_filters( 'wp_ulike_about_help_links', $help_links );

			$upsell = ! $is_pro ? self::get_pro_upsell_content( $health ) : array();

			$summary = apply_filters( 'wp_ulike_about_summary', self::get_overview_summary( $health ), $health );

			return array(
				'health'                 => $health,
				'is_pro'                 => $is_pro,
				'pro_version'            => $pro_label,
				'summary'                => $summary,
				'status_groups'          => self::get_status_group_labels(),
				'quick_actions'          => $quick_actions,
				'pro_modules'            => $pro_modules,
				'status_rows'            => $status_rows,
				'help_links'             => $help_links,
				'troubleshooting'        => self::get_troubleshooting_tips( $health ),
				'sidebar_meta'           => apply_filters( 'wp_ulike_about_sidebar_meta', self::get_default_sidebar_meta( $health ), $health ),
				'wp_version'             => get_bloginfo( 'version' ),
				'import_nonce'           => wp_create_nonce( 'wp_ulike_import_settings' ),
				'export_url'             => wp_nonce_url( admin_url( 'admin-ajax.php?action=wp_ulike_export_settings' ), 'wp_ulike_export_settings' ),
				'show_pro_upsell'        => apply_filters( 'wp_ulike_about_show_pro_upsell', ! $is_pro ),
				'pro_upsell'             => $upsell,
				'upgrade_url'            => add_query_arg(
					array(
						'utm_source' => 'overview',
						'utm_medium' => 'wp-dash',
					),
					WP_ULIKE_PLUGIN_URI . 'upgrade/'
				),
				'repair_tables_url'      => wp_nonce_url(
					admin_url( 'admin-post.php?action=wp_ulike_repair_tables' ),
					'wp_ulike_repair_tables'
				),
			);
		}

		/**
		 * Short troubleshooting tips for the Overview advanced panel.
		 *
		 * @param array $health Health report.
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_troubleshooting_tips( $health ) {
			$content_types_url = self::get_settings_url( 'content-types' );
			$general_url       = self::get_settings_url( 'general' );

			$tips = array(
				array(
					'text' => esc_html__( 'No votes yet? Open a published post (not the homepage) and click the like button once to confirm everything works.', 'wp-ulike' ),
					'url'  => ! empty( $health['preview_url'] ) ? $health['preview_url'] : '',
					'link' => esc_html__( 'View sample post', 'wp-ulike' ),
				),
				array(
					'text' => esc_html__( 'Likes usually show on single posts, not on the homepage or archives. Test on a post or change display in Settings.', 'wp-ulike' ),
					'url'  => $content_types_url,
					'link' => esc_html__( 'Content Types', 'wp-ulike' ),
				),
				array(
					'text' => sprintf(
						/* translators: %s: Automatic Display setting label */
						esc_html__( 'No button on the page? Add [wp_ulike] or the ULike block, or enable “%s” under Settings → Posts.', 'wp-ulike' ),
						esc_html__( 'Automatic Display', 'wp-ulike' )
					),
					'url'  => $content_types_url,
					'link' => esc_html__( 'Content Types', 'wp-ulike' ),
				),
				array(
					'text' => sprintf(
						/* translators: %s: Site Uses Caching setting label */
						esc_html__( 'Stale vote counts with a cache plugin? Enable “%s” in Settings → General, then purge cache.', 'wp-ulike' ),
						esc_html__( 'Site Uses Caching', 'wp-ulike' )
					),
					'url'  => $general_url,
					'link' => esc_html__( 'General', 'wp-ulike' ),
				),
				array(
					'text' => sprintf(
						/* translators: %s: Hide Plugin Admin Notices setting label */
						esc_html__( 'Too many admin notices? Turn on “%s” in Settings → General.', 'wp-ulike' ),
						esc_html__( 'Hide Plugin Admin Notices', 'wp-ulike' )
					),
					'url'  => $general_url,
					'link' => esc_html__( 'General', 'wp-ulike' ),
				),
			);

			if ( empty( $health['tables_ok'] ) ) {
				$tips[] = array(
					'text' => esc_html__( 'Database tables may be incomplete. Use “Repair database tables” on Help, or deactivate and reactivate WP ULike once.', 'wp-ulike' ),
					'url'  => self::get_about_url(),
					'link' => esc_html__( 'Open Help', 'wp-ulike' ),
				);
			}

			return apply_filters( 'wp_ulike_overview_troubleshooting_tips', $tips, $health );
		}

		/**
		 * Bootstrap hooks.
		 *
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_filter( 'site_status_tests', array( __CLASS__, 'register_site_health_tests' ) );
			add_action( 'wp_ajax_wp_ulike_export_settings', array( __CLASS__, 'handle_export_settings' ) );
			add_action( 'admin_post_wp_ulike_import_settings', array( __CLASS__, 'handle_import_settings' ) );
			add_action( 'admin_post_wp_ulike_repair_tables', array( __CLASS__, 'handle_repair_tables' ) );
		}

		/**
		 * AJAX: download settings JSON.
		 *
		 * @return void
		 */
		public static function handle_export_settings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'wp-ulike' ) );
			}

			check_admin_referer( 'wp_ulike_export_settings' );

			$filename = 'wp-ulike-settings-' . gmdate( 'Y-m-d' ) . '.json';

			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

			echo self::export_settings_json(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON export
			exit;
		}

		/**
		 * Import settings from uploaded JSON.
		 *
		 * @return void
		 */
		public static function handle_import_settings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'wp-ulike' ) );
			}

			check_admin_referer( 'wp_ulike_import_settings' );

			$redirect = admin_url( 'admin.php?page=wp-ulike-about' );

			if (
				empty( $_FILES['settings_file']['tmp_name'] )
				|| ! is_uploaded_file( $_FILES['settings_file']['tmp_name'] )
				|| ( isset( $_FILES['settings_file']['error'] ) && UPLOAD_ERR_OK !== (int) $_FILES['settings_file']['error'] )
			) {
				wp_safe_redirect( add_query_arg( 'wp_ulike_import', 'error_upload', $redirect ) );
				exit;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- user upload.
			$raw     = file_get_contents( $_FILES['settings_file']['tmp_name'] );
			$payload = json_decode( $raw, true );

			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
				wp_safe_redirect( add_query_arg( 'wp_ulike_import', 'error_json', $redirect ) );
				exit;
			}

			$result = self::import_settings( $payload );

			wp_safe_redirect(
				add_query_arg(
					'wp_ulike_import',
					is_wp_error( $result ) ? 'error_payload' : 'success',
					$redirect
				)
			);
			exit;
		}

		/**
		 * Repair missing database tables from Help.
		 *
		 * @return void
		 */
		public static function handle_repair_tables() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'wp-ulike' ) );
			}

			check_admin_referer( 'wp_ulike_repair_tables' );

			$report = self::repair_database_tables();
			$status = ! empty( $report['tables_ok'] ) ? 'success' : 'failed';

			wp_safe_redirect(
				add_query_arg(
					'wp_ulike_repair',
					$status,
					self::get_about_url()
				)
			);
			exit;
		}

		/**
		 * Create any missing WP ULike database tables.
		 *
		 * @return array{tables_ok: bool, missing_tables: string[]}
		 */
		public static function repair_database_tables() {
			if ( ! class_exists( 'wp_ulike_activator' ) ) {
				require_once WP_ULIKE_INC_DIR . '/classes/class-wp-ulike-activator.php';
			}

			wp_ulike_activator::get_instance()->install_tables();
			delete_transient( self::get_health_report_cache_key() );

			return self::get_tables_health();
		}

		/**
		 * Required database tables (health, repair, Site Health).
		 *
		 * @return array<string, string> Label => full table name.
		 */
		public static function get_required_tables() {
			global $wpdb;

			return array(
				'posts'      => $wpdb->prefix . 'ulike',
				'comments'   => $wpdb->prefix . 'ulike_comments',
				'activities' => $wpdb->prefix . 'ulike_activities',
				'forums'     => $wpdb->prefix . 'ulike_forums',
				'meta'       => $wpdb->prefix . 'ulike_meta',
			);
		}

		/**
		 * Check whether a table exists.
		 *
		 * @param string $table_name Full table name.
		 * @return bool
		 */
		public static function table_exists( $table_name ) {
			global $wpdb;

			$result = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
			);

			return $result === $table_name;
		}

		/**
		 * Plain-language Overview summary (may contain safe HTML links).
		 *
		 * @param array $health Health report.
		 * @return string
		 */
		public static function get_overview_summary( $health ) {
			$today     = (int) ( $health['today_votes'] ?? 0 );
			$new       = (int) ( $health['new_votes'] ?? 0 );
			$total     = (int) ( $health['log_count'] ?? 0 );
			$stats_url = esc_url( $health['statistics_url'] ?? admin_url( 'admin.php?page=wp-ulike-statistics' ) );

			if ( $new > 0 ) {
				return wp_kses_post(
					sprintf(
						/* translators: 1: vote count, 2: statistics admin URL */
						__( 'You have <strong>%1$s</strong> new votes since your last visit to Statistics. <a href="%2$s">Open Statistics</a> for charts and filters.', 'wp-ulike' ),
						number_format_i18n( $new ),
						$stats_url
					)
				);
			}

			if ( $today > 0 ) {
				return wp_kses_post(
					sprintf(
						/* translators: 1: votes today, 2: statistics admin URL */
						__( '<strong>%1$s</strong> votes today on your site. Numbers below are a quick snapshot—<a href="%2$s">Statistics</a> has the full breakdown.', 'wp-ulike' ),
						number_format_i18n( $today ),
						$stats_url
					)
				);
			}

			if ( ! empty( $health['auto_display'] ) && $total > 0 ) {
				return sprintf(
					/* translators: %s: total vote count */
					esc_html__( 'Buttons are active on posts and you have %s total votes stored. Use Statistics when you need date ranges and detailed reports.', 'wp-ulike' ),
					number_format_i18n( $total )
				);
			}

			if ( empty( $health['auto_display'] ) ) {
				return esc_html__( 'Like buttons are not on posts automatically yet. Turn on auto-display in Settings, or add the ULike block / shortcode where you want votes.', 'wp-ulike' );
			}

			if ( (int) ( $health['log_count'] ?? 0 ) === 0 ) {
				$preview          = ! empty( $health['preview_url'] ) ? $health['preview_url'] : '';
				$content_types_url = esc_url( self::get_settings_url( 'content-types' ) );

				if ( $preview ) {
					return wp_kses_post(
						sprintf(
							/* translators: 1: sample post URL, 2: Content Types settings URL */
							__( 'No votes recorded yet. Test the button on a <a href="%1$s" target="_blank" rel="noopener noreferrer">single post</a> (not the homepage). Adjust display under <a href="%2$s">Settings → Content Types → Posts</a>.', 'wp-ulike' ),
							esc_url( $preview ),
							$content_types_url
						)
					);
				}

				return wp_kses_post(
					sprintf(
						/* translators: %s: Content Types settings URL */
						__( 'No votes recorded yet. Buttons show on single posts by default, not on the homepage. Check display under <a href="%s">Settings → Content Types → Posts</a>.', 'wp-ulike' ),
						$content_types_url
					)
				);
			}

			return esc_html__( 'Your setup looks ready. Configure buttons below or open Statistics when you start receiving votes.', 'wp-ulike' );
		}

		/**
		 * Status row group labels for the Overview grid.
		 *
		 * @return array<string, string>
		 */
		public static function get_status_group_labels() {
			return apply_filters(
				'wp_ulike_about_status_group_labels',
				array(
					'engagement' => esc_html__( 'Engagement snapshot', 'wp-ulike' ),
					'setup'      => esc_html__( 'Site setup', 'wp-ulike' ),
					'pro'        => esc_html__( 'WP ULike Pro', 'wp-ulike' ),
				)
			);
		}

		/**
		 * Plugins / features detected on this site (for sidebar).
		 *
		 * @return array<int, string>
		 */
		public static function get_detected_integrations() {
			$active = array(
				esc_html__( 'Posts', 'wp-ulike' ),
				esc_html__( 'Comments', 'wp-ulike' ),
			);

			if ( class_exists( 'WooCommerce' ) ) {
				$active[] = 'WooCommerce';
			}
			if ( function_exists( 'buddypress' ) ) {
				$active[] = 'BuddyPress';
			}
			if ( function_exists( 'is_bbpress' ) ) {
				$active[] = 'bbPress';
			}
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				$active[] = 'EDD';
			}

			return apply_filters( 'wp_ulike_about_detected_integrations', $active );
		}

		/**
		 * Default sidebar meta rows (before Pro filter).
		 *
		 * @param array $health Health report.
		 * @return array<int, array<string, string>>
		 */
		public static function get_default_sidebar_meta( $health ) {
			$meta = array(
				array(
					'label' => esc_html__( 'PHP', 'wp-ulike' ),
					'value' => PHP_VERSION,
				),
			);

			$integrations = self::get_detected_integrations();
			if ( ! empty( $integrations ) ) {
				$meta[] = array(
					'label' => esc_html__( 'Detected on site', 'wp-ulike' ),
					'value' => implode( ', ', $integrations ),
				);
			}

			if ( empty( $health['is_pro'] ) && ! empty( $health['active_theme'] ) ) {
				$meta[] = array(
					'label' => esc_html__( 'Active theme', 'wp-ulike' ),
					'value' => $health['active_theme'],
				);
			}

			if ( ! empty( $health['cache_enabled'] ) ) {
				$meta[] = array(
					'label' => esc_html__( 'Caching helper', 'wp-ulike' ),
					'value' => esc_html__( 'On (Settings → General)', 'wp-ulike' ),
					'url'   => self::get_settings_url( 'general' ),
				);
			}

			$meta[] = array(
				'label' => esc_html__( 'Documentation', 'wp-ulike' ),
				'value' => esc_html__( 'Setup & hooks', 'wp-ulike' ),
				'url'   => 'https://docs.wpulike.com/?utm_source=overview-sidebar&utm_medium=wp-dash',
			);

			return $meta;
		}

		/**
		 * Group status rows for template rendering.
		 *
		 * @param array $rows Status rows.
		 * @return array<string, array<int, array>>
		 */
		public static function group_status_rows( $rows ) {
			$grouped = array();

			foreach ( (array) $rows as $row ) {
				$key = isset( $row['group'] ) ? $row['group'] : 'setup';
				if ( ! isset( $grouped[ $key ] ) ) {
					$grouped[ $key ] = array();
				}
				$grouped[ $key ][] = $row;
			}

			return $grouped;
		}

		/**
		 * Label for posts logging method.
		 *
		 * @param string $method Raw method key.
		 * @return string
		 */
		public static function format_logging_method_label( $method ) {
			return wp_ulike_get_logging_method_label( $method );
		}

		/**
		 * Human-readable post button placement summary for Help.
		 *
		 * @return string
		 */
		public static function get_post_display_summary() {
			if ( ! wp_ulike_setting_repo::isAutoDisplayOn( 'post' ) ) {
				return esc_html__( 'Manual (shortcode or block)', 'wp-ulike' );
			}

			$parts   = array( esc_html__( 'Auto on posts', 'wp-ulike' ) );
			$hidden  = wp_ulike_setting_repo::getPostAutoDisplayFilters();
			$hide_map = array(
				'home'     => esc_html__( 'homepage hidden', 'wp-ulike' ),
				'single'   => esc_html__( 'singular views filtered', 'wp-ulike' ),
				'archive'  => esc_html__( 'archives hidden', 'wp-ulike' ),
				'category' => esc_html__( 'categories hidden', 'wp-ulike' ),
				'search'   => esc_html__( 'search hidden', 'wp-ulike' ),
				'tag'      => esc_html__( 'tags hidden', 'wp-ulike' ),
				'author'   => esc_html__( 'author pages hidden', 'wp-ulike' ),
			);

			foreach ( (array) $hidden as $key ) {
				if ( isset( $hide_map[ $key ] ) ) {
					$parts[] = $hide_map[ $key ];
				}
			}

			$post_types = wp_ulike_setting_repo::getPostTypesFilterList();
			if ( ! empty( $post_types ) && is_array( $post_types ) ) {
				$parts[] = sprintf(
					/* translators: %s: comma-separated post type slugs */
					esc_html__( 'exceptions: %s', 'wp-ulike' ),
					implode( ', ', array_map( 'sanitize_key', $post_types ) )
				);
			}

			return implode( ' · ', $parts );
		}

		/**
		 * Label for posts auto-display position setting.
		 *
		 * @return string
		 */
		public static function get_post_button_position_label() {
			return wp_ulike_get_post_auto_display_position_label(
				wp_ulike_get_option( 'posts_group|auto_display_position', 'bottom' )
			);
		}

		/**
		 * Active post template display name.
		 *
		 * @return string
		 */
		public static function get_post_template_label() {
			$key       = wp_ulike_get_option( 'posts_group|template', 'wpulike-default' );
			$templates = function_exists( 'wp_ulike_generate_templates_list' ) ? wp_ulike_generate_templates_list() : array();

			if ( isset( $templates[ $key ]['name'] ) ) {
				return $templates[ $key ]['name'];
			}

			return $key;
		}

		/**
		 * Merge a live table check into a health report (Help / Site Health must not use stale cache).
		 *
		 * @param array $health Health report.
		 * @return array
		 */
		public static function apply_live_tables_health( $health ) {
			$tables_health = self::get_tables_health();

			$health['tables_ok']      = $tables_health['tables_ok'];
			$health['missing_tables'] = $tables_health['missing_tables'];

			return $health;
		}

		/**
		 * Transient key for cached Help page health report.
		 *
		 * @return string
		 */
		private static function get_health_report_cache_key() {
			return 'wp_ulike_health_report_v1';
		}

		/**
		 * Lightweight database table check (Site Health and similar).
		 *
		 * @return array{tables_ok: bool, missing_tables: string[]}
		 */
		public static function get_tables_health() {
			$tables_ok = true;
			$missing   = array();

			foreach ( self::get_required_tables() as $label => $table_name ) {
				if ( ! self::table_exists( $table_name ) ) {
					$tables_ok = false;
					$missing[] = $label;
				}
			}

			return array(
				'tables_ok'      => $tables_ok,
				'missing_tables' => $missing,
			);
		}

		/**
		 * Run health diagnostics for the About page Quick Start section.
		 *
		 * @param bool $force_refresh Bypass cached report.
		 * @return array
		 */
		public static function get_health_report( $force_refresh = false ) {
			if ( ! $force_refresh ) {
				$cached = get_transient( self::get_health_report_cache_key() );
				if ( is_array( $cached ) ) {
					return $cached;
				}
			}

			$tables_health = self::get_tables_health();

			$auto_display = wp_ulike_setting_repo::isAutoDisplayOn( 'post' );
			$sample_post  = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'no_found_rows'  => true,
				)
			);
			$preview_url  = ! empty( $sample_post[0] ) ? get_permalink( $sample_post[0]->ID ) : '';

			$new_votes = 0;
			if ( function_exists( 'wp_ulike_get_number_of_new_likes' ) ) {
				$new_votes = (int) wp_ulike_get_number_of_new_likes();
			}

			$cache_enabled = false;
			if ( function_exists( 'wp_ulike_get_option' ) ) {
				$cache_enabled = wp_ulike_is_true( wp_ulike_get_option( 'cache_exist', false ) );
			}

			$theme = wp_get_theme();

			$report = array(
				'tables_ok'              => $tables_health['tables_ok'],
				'missing_tables'         => $tables_health['missing_tables'],
				'is_pro'                 => defined( 'WP_ULIKE_PRO_VERSION' ),
				'auto_display'           => $auto_display,
				'comments_auto_display'  => wp_ulike_setting_repo::isAutoDisplayOn( 'comment' ),
				'preview_url'            => $preview_url,
				'plugin_version'         => WP_ULIKE_VERSION,
				'db_version'             => get_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION ),
				'log_count'              => wp_ulike_count_all_logs(),
				'today_votes'            => wp_ulike_count_all_logs( 'today' ),
				'new_votes'              => $new_votes,
				'cache_enabled'          => $cache_enabled,
				'statistics_url'         => admin_url( 'admin.php?page=wp-ulike-statistics' ),
				'post_display_summary'   => self::get_post_display_summary(),
				'post_template_name'     => self::get_post_template_label(),
				'post_button_position'   => self::get_post_button_position_label(),
				'logging_method'         => wp_ulike_setting_repo::getMethod( 'post' ),
				'toast_notices'          => wp_ulike_is_true( wp_ulike_get_option( 'enable_toast_notice', true ) ),
				'active_theme'           => $theme->get( 'Name' ),
				'content_types_settings_url' => self::get_settings_url( 'content-types' ),
				'general_settings_url'       => self::get_settings_url( 'general' ),
			);

			set_transient( self::get_health_report_cache_key(), $report, 5 * MINUTE_IN_SECONDS );

			return $report;
		}

		/**
		 * Register Site Health tests.
		 *
		 * @param array $tests Tests.
		 * @return array
		 */
		public static function register_site_health_tests( $tests ) {
			$tests['direct']['wp_ulike_database_tables'] = array(
				'label' => esc_html__( 'WP ULike database tables', 'wp-ulike' ),
				'test'  => array( __CLASS__, 'site_health_tables_test' ),
			);

			return $tests;
		}

		/**
		 * Site Health: tables test.
		 *
		 * @return array
		 */
		public static function site_health_tables_test() {
			$report = self::get_tables_health();

			if ( $report['tables_ok'] ) {
				return array(
					'label'       => esc_html__( 'WP ULike database tables are installed', 'wp-ulike' ),
					'status'      => 'good',
					'badge'       => array(
						'label' => esc_html__( 'WP ULike', 'wp-ulike' ),
						'color' => 'blue',
					),
					'description' => sprintf(
						'<p>%s</p>',
						esc_html__( 'All database tables required by WP ULike are present.', 'wp-ulike' )
					),
					'actions'     => '',
					'test'        => 'wp_ulike_database_tables',
				);
			}

			return array(
				'label'       => esc_html__( 'WP ULike database tables are missing', 'wp-ulike' ),
				'status'      => 'critical',
				'badge'       => array(
					'label' => esc_html__( 'WP ULike', 'wp-ulike' ),
					'color' => 'red',
				),
				'description' => sprintf(
					'<p>%s</p>',
					esc_html__( 'Some WP ULike tables are missing. Deactivate and reactivate the plugin, or open Help for details.', 'wp-ulike' )
				),
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( self::get_about_url() ),
					esc_html__( 'Open Help', 'wp-ulike' )
				),
				'test'        => 'wp_ulike_database_tables',
			);
		}

		/**
		 * Export plugin settings as JSON (settings API option blob).
		 *
		 * @return string
		 */
		public static function export_settings_json() {
			$settings  = get_option( 'wp_ulike_settings', array() );
			$customize = get_option( 'wp_ulike_customize', array() );

			if ( class_exists( 'wp_ulike_settings_api' ) ) {
				$settings_api = new wp_ulike_settings_api();
				$settings     = $settings_api->get_settings();
			}

			if ( class_exists( 'wp_ulike_customizer_api' ) ) {
				$customizer_api = new wp_ulike_customizer_api();
				$customize      = $customizer_api->get_values();
			}

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			if ( ! is_array( $customize ) ) {
				$customize = array();
			}

			$data = array(
				'version'   => WP_ULIKE_VERSION,
				'exported'  => gmdate( 'c' ),
				'settings'  => $settings,
				'customize' => $customize,
			);

			return wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		/**
		 * Import settings from decoded JSON array.
		 *
		 * @param array $payload Import payload.
		 * @return true|WP_Error
		 */
		public static function import_settings( $payload ) {
			if ( ! array_key_exists( 'settings', $payload ) || ! is_array( $payload['settings'] ) ) {
				return new WP_Error( 'invalid_payload', esc_html__( 'Invalid settings file. Expected a JSON export from Help → Settings backup.', 'wp-ulike' ) );
			}

			$settings = $payload['settings'];

			if ( class_exists( 'wp_ulike_settings_api' ) ) {
				$settings_api = new wp_ulike_settings_api();
				$settings     = $settings_api->sanitize_import_values( $settings );
			}

			update_option( 'wp_ulike_settings', $settings );

			if ( ! empty( $payload['customize'] ) && is_array( $payload['customize'] ) ) {
				$customize = $payload['customize'];

				if ( class_exists( 'wp_ulike_customizer_api' ) ) {
					$customizer_api = new wp_ulike_customizer_api();
					$customize      = $customizer_api->sanitize_import_values( $customize );
				}

				update_option( 'wp_ulike_customize', $customize );
			}

			delete_transient( self::get_health_report_cache_key() );

			return true;
		}
	}

	WP_Ulike_Overview::init();
}
