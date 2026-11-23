<?php
/**
 * ULike Hub: a heart in the admin bar with what matters right now.
 *
 * The panel shows one useful hint (a setup fix, today's top post, the weekly
 * trend) and the latest likes. Built so it costs nothing on busy sites:
 * - Visitors and users who cannot see Statistics never run any of it.
 * - The badge is the existing site-wide vote counter minus a per-user "seen"
 *   mark: a vote adds no extra write, a page load reads two cached values.
 * - Hints and likes load only when the panel opens, from cached statistics
 *   and a primary-key read with a small LIMIT.
 *
 * Other features add hints through the `wp_ulike_hub_hints` filter.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Hub' ) ) {

	/**
	 * ULike Hub.
	 */
	class WP_Ulike_Hub {

		const NODE_ID        = 'wp-ulike-hub';
		const HANDLE         = 'wp-ulike-hub';
		const AJAX_PANEL     = 'wp_ulike_hub_panel';
		const AJAX_DISMISS   = 'wp_ulike_hub_dismiss_hint';
		const OPTION_KEY     = 'enable_hub';
		const SEEN_META_KEY  = 'wp_ulike_hub_seen';
		const HINTS_META_KEY = 'wp_ulike_hub_hints';
		const CACHE_GROUP    = 'wp_ulike';
		const ITEMS          = 6;

		/**
		 * Recent votes read to build the ITEMS grouped rows.
		 */
		const SCAN_ROWS      = 60;

		/**
		 * Like totals worth celebrating.
		 */
		const MILESTONES = array( 10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000 );

		/**
		 * @var bool|null Per-request answer of is_enabled().
		 */
		private static $enabled = null;

		/**
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_bar_menu', array( __CLASS__, 'add_node' ), 85 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_ajax_' . self::AJAX_PANEL, array( __CLASS__, 'ajax_panel' ) );
			add_action( 'wp_ajax_' . self::AJAX_DISMISS, array( __CLASS__, 'ajax_dismiss_hint' ) );
			add_action( 'wp_ulike_new_vote_counted', array( __CLASS__, 'skip_own_vote' ) );
			add_filter( 'heartbeat_received', array( __CLASS__, 'heartbeat' ), 10, 2 );
		}

		/**
		 * Settings → General → ULike Hub. On unless turned off.
		 *
		 * @return bool
		 */
		public static function is_setting_on() {
			$value = wp_ulike_get_option( self::OPTION_KEY, true );

			return function_exists( 'wp_ulike_is_true' ) ? wp_ulike_is_true( $value ) : (bool) $value;
		}

		/**
		 * @return bool
		 */
		public static function current_user_can_view() {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			if ( function_exists( 'wp_ulike_is_feature_enabled' ) && ! wp_ulike_is_feature_enabled( 'statistics' ) ) {
				return false;
			}

			$capability = function_exists( 'wp_ulike_get_user_access_capability' ) ? wp_ulike_get_user_access_capability( 'stats' ) : '';

			return current_user_can( $capability ? $capability : 'manage_options' );
		}

		/**
		 * @return bool
		 */
		public static function is_enabled() {
			if ( null === self::$enabled ) {
				self::$enabled = is_admin_bar_showing() && self::is_setting_on() && self::current_user_can_view();
			}

			return self::$enabled;
		}

		/* ------------------------------------------------------------------
		 * New votes count (per user)
		 * ------------------------------------------------------------------ */

		/**
		 * Site-wide counter of new votes (likes, dislikes and Pro reactions).
		 *
		 * @return int
		 */
		public static function get_total() {
			$total = wp_ulike_get_meta_data( WP_Ulike_Query_Cache::STATS_ITEM_ID, WP_Ulike_Query_Cache::STATS_META_GROUP, WP_Ulike_Query_Cache::ADMIN_NEW_VOTES_KEY, true );

			return is_numeric( $total ) ? (int) $total : 0;
		}

		/**
		 * New votes since this user last looked.
		 *
		 * @param int $user_id User ID, current user when 0.
		 * @return int
		 */
		public static function get_unseen( $user_id = 0 ) {
			$user_id = $user_id ? (int) $user_id : get_current_user_id();
			if ( ! $user_id ) {
				return 0;
			}

			$total = self::get_total();
			$seen  = (int) get_user_option( self::SEEN_META_KEY, $user_id );

			// The counter was reset (older versions, or a storage rebuild): start over.
			if ( $seen > $total ) {
				$seen = 0;
			}

			return max( 0, $total - $seen );
		}

		/**
		 * Mark everything up to now as seen for one user, on this site only.
		 *
		 * @param int $user_id User ID, current user when 0.
		 * @return void
		 */
		public static function mark_seen( $user_id = 0 ) {
			$user_id = $user_id ? (int) $user_id : get_current_user_id();
			if ( ! $user_id || ! self::get_unseen( $user_id ) ) {
				return;
			}

			update_user_option( $user_id, self::SEEN_META_KEY, self::get_total() );
		}

		/**
		 * The current user's own vote is not news to them: move their seen
		 * mark past it. Only users who see the Hub get the write.
		 *
		 * @return void
		 */
		public static function skip_own_vote() {
			$user_id = get_current_user_id();
			if ( ! $user_id || ! self::is_setting_on() || ! self::current_user_can_view() ) {
				return;
			}

			$total = self::get_total();
			$seen  = (int) get_user_option( self::SEEN_META_KEY, $user_id );

			// Same reset rule as get_unseen(), applied to the count before this vote.
			if ( $seen > $total - 1 ) {
				$seen = 0;
			}

			update_user_option( $user_id, self::SEEN_META_KEY, min( $seen + 1, $total ) );
		}

		/**
		 * Badge text for a number of new votes.
		 *
		 * @param int $unseen New votes.
		 * @return array{count:string,label:string}
		 */
		protected static function badge_text( $unseen ) {
			return array(
				'count' => $unseen > 99 ? '99+' : number_format_i18n( $unseen ),
				'label' => sprintf(
					/* translators: %s: number of new likes */
					_n( 'ULike Hub: %s new like', 'ULike Hub: %s new likes', $unseen, 'wp-ulike' ),
					number_format_i18n( $unseen )
				),
			);
		}

		/**
		 * Live badge on admin screens, riding WordPress's own Heartbeat request.
		 *
		 * @param array<string, mixed> $response Heartbeat response.
		 * @param array<string, mixed> $data     Data sent by the browser.
		 * @return array<string, mixed>
		 */
		public static function heartbeat( $response, $data ) {
			if ( ! empty( $data['wp_ulike_hub'] ) && self::is_setting_on() && self::current_user_can_view() ) {
				$unseen                   = self::get_unseen();
				$response['wp_ulike_hub'] = array( 'unseen' => $unseen ) + self::badge_text( $unseen );
			}

			return $response;
		}

		/* ------------------------------------------------------------------
		 * Admin bar
		 * ------------------------------------------------------------------ */

		/**
		 * @param WP_Admin_Bar $admin_bar Admin bar.
		 * @return void
		 */
		public static function add_node( $admin_bar ) {
			if ( ! self::is_enabled() ) {
				return;
			}

			$unseen = self::get_unseen();
			$badge  = self::badge_text( $unseen );

			$title  = '<span class="ab-icon wp-ulike-hub__icon" aria-hidden="true"></span>';
			$title .= sprintf(
				'<span class="ab-label wp-ulike-hub__count%1$s" aria-hidden="true">%2$s</span>',
				$unseen ? '' : ' is-empty',
				esc_html( $badge['count'] )
			);
			$title .= '<span class="screen-reader-text wp-ulike-hub__label">' . esc_html( $badge['label'] ) . '</span>';

			$admin_bar->add_node(
				array(
					'id'    => self::NODE_ID,
					'title' => $title,
					'href'  => self::statistics_url(),
					'meta'  => array(
						'class' => 'wp-ulike-hub' . ( $unseen ? ' has-new' : '' ),
					),
				)
			);

			// Filled on first open, so a page load never queries votes.
			$admin_bar->add_node(
				array(
					'parent' => self::NODE_ID,
					'id'     => self::NODE_ID . '-panel',
					'title'  => '<div class="wp-ulike-hub-panel" aria-live="polite"></div>',
				)
			);
		}

		/**
		 * @return void
		 */
		public static function enqueue_assets() {
			if ( ! self::is_enabled() ) {
				return;
			}

			$css = WP_ULIKE_ADMIN_DIR . '/assets/css/hub.css';
			$js  = WP_ULIKE_ADMIN_DIR . '/assets/js/hub.js';

			wp_enqueue_style(
				self::HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/css/hub.css',
				array( 'admin-bar' ),
				file_exists( $css ) ? (string) filemtime( $css ) : WP_ULIKE_VERSION
			);

			wp_enqueue_script(
				self::HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/js/hub.js',
				array(),
				file_exists( $js ) ? (string) filemtime( $js ) : WP_ULIKE_VERSION,
				true
			);

			wp_localize_script(
				self::HANDLE,
				'wpUlikeHub',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'panelAction'   => self::AJAX_PANEL,
					'dismissAction' => self::AJAX_DISMISS,
					'nonce'         => wp_create_nonce( 'wp_ulike_hub' ),
					'i18n'          => array(
						'title'   => __( 'ULike Hub', 'wp-ulike' ),
						'latest'  => __( 'Latest activity', 'wp-ulike' ),
						'loading' => __( 'Loading…', 'wp-ulike' ),
						'empty'   => __( 'No likes yet. They will show up here as they come in.', 'wp-ulike' ),
						'error'   => __( 'Could not load. Try again in a moment.', 'wp-ulike' ),
						'logs'    => __( 'View logs', 'wp-ulike' ),
						'stats'   => __( 'Statistics', 'wp-ulike' ),
						'new'     => __( 'New', 'wp-ulike' ),
						'dismiss' => __( 'Dismiss this tip', 'wp-ulike' ),
					),
				)
			);
		}

		/* ------------------------------------------------------------------
		 * AJAX
		 * ------------------------------------------------------------------ */

		/**
		 * Panel data. Opening the panel marks likes as seen for this user.
		 *
		 * @return void
		 */
		public static function ajax_panel() {
			check_ajax_referer( 'wp_ulike_hub', 'nonce' );

			if ( ! self::is_setting_on() || ! self::current_user_can_view() ) {
				wp_send_json_error( null, 403 );
			}

			$unseen = self::get_unseen();
			$items  = self::get_items( $unseen );

			self::mark_seen();

			$today = self::get_today_count();

			wp_send_json_success(
				array(
					'hint'     => self::get_hint(),
					'items'    => $items,
					'badge'    => array( 'unseen' => 0 ) + self::badge_text( 0 ),
					'today'    => sprintf(
						/* translators: %s: number of votes today */
						_n( '%s today', '%s today', $today, 'wp-ulike' ),
						number_format_i18n( $today )
					),
					'logsUrl'  => self::logs_url( isset( $items[0]['type'] ) ? $items[0]['type'] : 'post' ),
					'statsUrl' => self::statistics_url(),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function ajax_dismiss_hint() {
			check_ajax_referer( 'wp_ulike_hub', 'nonce' );

			if ( ! self::current_user_can_view() ) {
				wp_send_json_error( null, 403 );
			}

			$id = isset( $_POST['hint'] ) ? sanitize_key( wp_unslash( $_POST['hint'] ) ) : '';
			if ( '' === $id ) {
				wp_send_json_error( null, 400 );
			}

			$dismissed        = self::get_dismissed_hints();
			$dismissed[ $id ] = time();

			// Keep the list small: drop the oldest beyond 50 entries.
			if ( count( $dismissed ) > 50 ) {
				asort( $dismissed );
				$dismissed = array_slice( $dismissed, -50, null, true );
			}

			update_user_option( get_current_user_id(), self::HINTS_META_KEY, $dismissed );

			wp_send_json_success( array( 'hint' => self::get_hint() ) );
		}

		/* ------------------------------------------------------------------
		 * Hints
		 * ------------------------------------------------------------------ */

		/**
		 * @return array<string, int>
		 */
		protected static function get_dismissed_hints() {
			$dismissed = get_user_option( self::HINTS_META_KEY );

			return is_array( $dismissed ) ? $dismissed : array();
		}

		/**
		 * The most useful hint this user has not dismissed, or null.
		 *
		 * @return array<string, mixed>|null
		 */
		public static function get_hint() {
			/**
			 * Hints for the ULike Hub panel. Lower priority shows first; one at a time.
			 *
			 * Each hint: id (unique; change it to show a hint again), priority, icon
			 * (tip, trend, star, milestone, warning), text, and an optional action with label and url.
			 *
			 * @param array<int, array<string, mixed>> $hints Hints.
			 */
			$hints     = (array) apply_filters( 'wp_ulike_hub_hints', self::get_cached_core_hints() );
			$dismissed = self::get_dismissed_hints();

			usort(
				$hints,
				static function ( $a, $b ) {
					return ( isset( $a['priority'] ) ? (int) $a['priority'] : 50 ) - ( isset( $b['priority'] ) ? (int) $b['priority'] : 50 );
				}
			);

			foreach ( $hints as $hint ) {
				if ( empty( $hint['id'] ) || empty( $hint['text'] ) || isset( $dismissed[ sanitize_key( $hint['id'] ) ] ) ) {
					continue;
				}

				return array(
					'id'     => sanitize_key( $hint['id'] ),
					'icon'   => isset( $hint['icon'] ) ? sanitize_key( $hint['icon'] ) : 'tip',
					'text'   => self::plain_text( $hint['text'] ),
					'action' => ! empty( $hint['action']['url'] ) && ! empty( $hint['action']['label'] )
						? array(
							'label' => self::plain_text( $hint['action']['label'] ),
							'url'   => esc_url_raw( $hint['action']['url'] ),
						)
						: null,
				);
			}

			return null;
		}

		/**
		 * Built-in hints, shared by all users with the same access for 10 minutes.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		protected static function get_cached_core_hints() {
			$key   = 'wp_ulike_hub_hints_' . ( current_user_can( 'manage_options' ) ? 'admin' : 'viewer' );
			$hints = get_transient( $key );

			if ( ! is_array( $hints ) ) {
				$hints = self::get_core_hints();
				set_transient( $key, $hints, 10 * MINUTE_IN_SECONDS );
			}

			return $hints;
		}

		/**
		 * Built-in hints. Each one reads settings or cached statistics only.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		protected static function get_core_hints() {
			$hints        = array();
			$all_time     = function_exists( 'wp_ulike_count_all_logs' ) ? (int) wp_ulike_count_all_logs() : 0;
			$settings_url = admin_url( 'admin.php?page=wp-ulike-settings&settings-page=content-types&settings-section=posts_group' );

			// Setup hints are for free sites run by an admin. Pro places buttons in
			// other ways too (Display Automation, widgets), so it skips them.
			$setup_hints = current_user_can( 'manage_options' ) && ! defined( 'WP_ULIKE_PRO_VERSION' );

			if ( $setup_hints && ! wp_ulike_setting_repo::isAutoDisplayOn( 'post' ) ) {
				// Votes exist, so buttons are placed some other way (block, shortcode).
				if ( 0 === $all_time ) {
					$hints[] = array(
						'id'       => 'setup_display_off',
						'priority' => 10,
						'icon'     => 'warning',
						'text'     => __( 'Like buttons are not added to your posts automatically.', 'wp-ulike' ),
						'action'   => array(
							'label' => __( 'Turn on', 'wp-ulike' ),
							'url'   => $settings_url,
						),
					);
				}
			} elseif ( $setup_hints ) {
				// Buttons only on single posts, not on the blog lists.
				$hidden = (array) wp_ulike_setting_repo::getPostAutoDisplayFilters();
				if ( in_array( 'home', $hidden, true ) && in_array( 'archive', $hidden, true ) ) {
					$hints[] = array(
						'id'       => 'setup_lists_hidden',
						'priority' => 20,
						'icon'     => 'tip',
						'text'     => __( 'Buttons show on single posts only. Visitors browsing your homepage and archives can like too.', 'wp-ulike' ),
						'action'   => array(
							'label' => __( 'Show on lists', 'wp-ulike' ),
							'url'   => $settings_url,
						),
					);
				}
			}

			// No votes yet, a few days in: make sure the button is visible.
			if ( 0 === $all_time ) {
				$days = function_exists( 'wp_ulike_get_days_since_activation' ) ? wp_ulike_get_days_since_activation() : null;
				$post = get_posts(
					array(
						'numberposts'      => 1,
						'post_status'      => 'publish',
						'fields'           => 'ids',
						'suppress_filters' => false,
					)
				);

				if ( null !== $days && $days >= 2 && ! empty( $post ) ) {
					$hints[] = array(
						'id'       => 'setup_no_votes',
						'priority' => 30,
						'icon'     => 'tip',
						'text'     => __( 'No likes yet. Open a post and check that the like button shows below the content.', 'wp-ulike' ),
						'action'   => array(
							'label' => __( 'Open a post', 'wp-ulike' ),
							'url'   => get_permalink( $post[0] ),
						),
					);
				}

				return $hints;
			}

			$milestone = self::get_milestone_hint();
			if ( $milestone ) {
				$hints[] = $milestone;
			}

			// Today's most liked post.
			if ( function_exists( 'wp_ulike_get_popular_items_info' ) ) {
				$top = wp_ulike_get_popular_items_info(
					array(
						'type'     => 'post',
						'rel_type' => '',
						'period'   => 'today',
						'limit'    => 1,
					)
				);

				if ( ! empty( $top[0]->item_ID ) && ! empty( $top[0]->counter ) && (int) $top[0]->counter >= 3 ) {
					$post_id = (int) $top[0]->item_ID;
					$title   = wp_strip_all_tags( get_the_title( $post_id ) );

					if ( '' !== $title ) {
						$hints[] = array(
							'id'       => 'top_today_' . current_time( 'Ymd' ) . '_' . $post_id,
							'priority' => 40,
							'icon'     => 'star',
							'text'     => sprintf(
								/* translators: 1: post title, 2: number of likes */
								_n( '“%1$s” is today’s most liked post, with %2$s like.', '“%1$s” is today’s most liked post, with %2$s likes.', (int) $top[0]->counter, 'wp-ulike' ),
								$title,
								number_format_i18n( (int) $top[0]->counter )
							),
							'action'   => array(
								'label' => __( 'View', 'wp-ulike' ),
								'url'   => get_permalink( $post_id ),
							),
						);
					}
				}
			}

			// This week against last week, once there is enough to compare.
			$week      = (int) wp_ulike_count_all_logs( 'week' );
			$last_week = (int) wp_ulike_count_all_logs( 'last_week' );

			if ( $week >= 10 && $last_week >= 10 ) {
				$change = (int) round( ( $week - $last_week ) / $last_week * 100 );

				if ( abs( $change ) >= 10 ) {
					$hints[] = array(
						'id'       => 'trend_' . current_time( 'oW' ),
						'priority' => 50,
						'icon'     => 'trend',
						'text'     => $change > 0
							? sprintf(
								/* translators: 1: votes this week, 2: percent */
								__( '%1$s votes this week, up %2$s%% on last week.', 'wp-ulike' ),
								number_format_i18n( $week ),
								number_format_i18n( $change )
							)
							: sprintf(
								/* translators: 1: votes this week, 2: percent */
								__( '%1$s votes this week, down %2$s%% on last week.', 'wp-ulike' ),
								number_format_i18n( $week ),
								number_format_i18n( abs( $change ) )
							),
						'action'   => array(
							'label' => __( 'See why', 'wp-ulike' ),
							'url'   => self::statistics_url(),
						),
					);
				}
			}

			return $hints;
		}

		/**
		 * A post in the latest activity that just passed a like milestone.
		 *
		 * "Just" means its total is within 10% above the milestone, so an old
		 * popular post does not announce a milestone it reached long ago.
		 * Reads cached counters for at most a handful of posts.
		 *
		 * @return array<string, mixed>|null
		 */
		protected static function get_milestone_hint() {
			if ( ! function_exists( 'wp_ulike_get_counter_value' ) ) {
				return null;
			}

			$checked = array();
			foreach ( self::get_recent_rows() as $row ) {
				$post_id = (int) $row['item_id'];
				if ( 'post' !== $row['item_type'] || isset( $checked[ $post_id ] ) ) {
					continue;
				}
				if ( count( $checked ) >= self::ITEMS ) {
					break;
				}
				$checked[ $post_id ] = true;

				$count   = (int) wp_ulike_get_counter_value( $post_id, 'post', 'like', wp_ulike_setting_repo::isDistinct( 'post' ) );
				$reached = 0;
				foreach ( self::MILESTONES as $milestone ) {
					if ( $count >= $milestone ) {
						$reached = $milestone;
					}
				}

				if ( ! $reached || $count > $reached * 1.1 ) {
					continue;
				}

				$title = wp_strip_all_tags( get_the_title( $post_id ) );
				if ( '' === $title ) {
					continue;
				}

				return array(
					'id'       => 'milestone_' . $post_id . '_' . $reached,
					'priority' => 35,
					'icon'     => 'milestone',
					'text'     => sprintf(
						/* translators: 1: post title, 2: number of likes */
						__( '“%1$s” just passed %2$s likes. Nice work!', 'wp-ulike' ),
						$title,
						number_format_i18n( $reached )
					),
					'action'   => array(
						'label' => __( 'View', 'wp-ulike' ),
						'url'   => get_permalink( $post_id ),
					),
				);
			}

			return null;
		}

		/* ------------------------------------------------------------------
		 * Latest activity
		 * ------------------------------------------------------------------ */

		/**
		 * Latest raw engagement rows. Cached until the next vote.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		protected static function get_recent_rows() {
			if ( ! class_exists( 'WP_Ulike_Pulse_Query' ) ) {
				return array();
			}

			$cache_key = 'hub_rows_' . self::get_total();
			$rows      = wp_cache_get( $cache_key, self::CACHE_GROUP );

			if ( false === $rows ) {
				$rows = WP_Ulike_Pulse_Query::recent_engagements( self::SCAN_ROWS );
				wp_cache_set( $cache_key, $rows, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
			}

			return (array) $rows;
		}

		/**
		 * Votes cast today. The pulse table has no date-only index, so the count
		 * is kept for 5 minutes instead of running on every panel open.
		 *
		 * @return int
		 */
		protected static function get_today_count() {
			if ( ! function_exists( 'wp_ulike_count_all_logs' ) ) {
				return 0;
			}

			$key   = 'wp_ulike_hub_today_' . current_time( 'Ymd' );
			$count = get_transient( $key );

			if ( false === $count ) {
				$count = (int) wp_ulike_count_all_logs( 'today' );
				set_transient( $key, $count, 5 * MINUTE_IN_SECONDS );
			}

			return (int) $count;
		}

		/**
		 * Latest activity, one row per item and action ("Sara and 12 others liked X").
		 *
		 * @param int $unseen New votes for this user; rows holding one of them are marked new.
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_items( $unseen = 0 ) {
			$groups = array();
			$me     = (string) get_current_user_id();
			$rank   = 0;

			foreach ( self::get_recent_rows() as $index => $row ) {
				$key     = $row['item_type'] . '|' . $row['item_id'] . '|' . $row['engagement_kind'] . '|' . ( 'vote' === $row['engagement_kind'] ? $row['engagement_key'] : '' );
				$is_mine = (string) $row['user_id'] === $me;

				// Your own votes are not in $unseen (skip_own_vote), so they take no rank.
				$row_rank = $is_mine ? PHP_INT_MAX : $rank++;

				if ( ! isset( $groups[ $key ] ) ) {
					if ( count( array_filter( $groups ) ) >= self::ITEMS ) {
						continue;
					}

					$target         = self::describe_item( (string) $row['item_type'], (int) $row['item_id'] );
					$groups[ $key ] = $target ? array(
						'row'    => $row,
						'named'  => null,
						'target' => $target,
						'first'  => PHP_INT_MAX,
						'people' => array(),
					) : false;
				}

				if ( ! $groups[ $key ] ) {
					continue;
				}

				$groups[ $key ]['first'] = min( $groups[ $key ]['first'], $row_rank );

				// Name the newest person who is not you; "Sara and 3 others" may include you.
				if ( null === $groups[ $key ]['named'] && ! $is_mine ) {
					$groups[ $key ]['named'] = $row;
				}

				// Guests share user ID 0, so each guest vote counts as one person.
				$person = is_numeric( $row['user_id'] ) && (int) $row['user_id'] < 1 ? 'guest-' . $index : (string) $row['user_id'];
				$groups[ $key ]['people'][ $person ] = true;
			}

			$items = array();
			foreach ( array_filter( $groups ) as $group ) {
				$row = $group['row'];
				if ( $group['named'] && count( $group['people'] ) > 1 ) {
					$row              = $group['named'];
					$row['date_time'] = $group['row']['date_time'];
				}

				$item          = self::format_row( $row, $group['target'], self::others_count( $row, count( $group['people'] ) ) );
				$item['isNew'] = $group['first'] < $unseen;
				$items[]       = $item;
			}

			return $items;
		}

		/**
		 * People besides the newest one. Likes and dislikes use the item's total,
		 * so 1,000 likes read "and 999 others"; reactions and ratings count the
		 * recent votes read here.
		 *
		 * @param array<string, mixed> $row    Newest engagement row of the group.
		 * @param int                  $people Distinct people in the recent rows.
		 * @return int
		 */
		protected static function others_count( $row, $people ) {
			$others = $people - 1;

			if ( 'vote' === $row['engagement_kind'] && function_exists( 'wp_ulike_get_counter_value' ) ) {
				$status = 'dislike' === $row['engagement_key'] ? 'dislike' : 'like';
				$total  = (int) wp_ulike_get_counter_value( (int) $row['item_id'], (string) $row['item_type'], $status, wp_ulike_setting_repo::isDistinct( (string) $row['item_type'] ) );
				$others = max( $others, $total - 1 );
			}

			return max( 0, $others );
		}

		/**
		 * Statistics → Logs tab for an item type.
		 *
		 * @param string $item_type post, comment, activity or topic.
		 * @return string
		 */
		public static function logs_url( $item_type = 'post' ) {
			$tabs = array(
				'post'     => 'posts',
				'comment'  => 'comments',
				'activity' => 'activities',
				'topic'    => 'topics',
			);

			return self::statistics_url() . '#/history/' . ( isset( $tabs[ $item_type ] ) ? $tabs[ $item_type ] : 'posts' );
		}

		/**
		 * @param array<string, mixed>  $row    Newest engagement row of the group.
		 * @param array<string, string> $target Item from describe_item().
		 * @param int                   $others People besides the newest one.
		 * @return array<string, string>
		 */
		protected static function format_row( $row, $target, $others = 0 ) {
			$user_id = is_numeric( $row['user_id'] ) ? (int) $row['user_id'] : 0;
			$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
			$is_you  = $user && get_current_user_id() === $user_id;

			$timestamp = (int) mysql2date( 'U', (string) $row['date_time'] );
			$now       = (int) current_time( 'timestamp' );

			if ( $is_you ) {
				$name = __( 'You', 'wp-ulike' );
			} else {
				$name = $user ? $user->display_name : __( 'A visitor', 'wp-ulike' );
			}

			return array(
				'name'   => $name,
				'avatar' => $user ? (string) get_avatar_url( $user->ID, array( 'size' => 64 ) ) : '',
				'text'   => $is_you && ! $others
					? self::own_sentence( (string) $row['engagement_kind'], (string) $row['engagement_key'], 'comment' === $target['type'] )
					: self::sentence( (string) $row['engagement_kind'], (string) $row['engagement_key'], $target['type'], $others ),
				'badge'  => self::badge( $row ),
				'title'  => $target['title'],
				'url'    => $target['url'],
				'time'   => $timestamp && $timestamp < $now - MINUTE_IN_SECONDS
					/* translators: %s: human time difference, e.g. "3 mins" */
					? sprintf( __( '%s ago', 'wp-ulike' ), human_time_diff( $timestamp, $now ) )
					: __( 'just now', 'wp-ulike' ),
				'kind'   => 'dislike' === $row['engagement_key'] ? 'dislike' : 'like',
				'type'   => (string) $row['item_type'],
			);
		}

		/**
		 * Small badge on the avatar: the reaction emoji, or the star score.
		 * Empty for likes and dislikes, which use the heart.
		 *
		 * @param array<string, mixed> $row Engagement row.
		 * @return string
		 */
		protected static function badge( $row ) {
			if ( 'star' === $row['engagement_kind'] && ! empty( $row['value'] ) ) {
				return absint( $row['value'] ) . '★';
			}

			if ( 'emoji' === $row['engagement_kind'] ) {
				return self::reaction_emoji( (string) $row['item_type'], (string) $row['engagement_key'] );
			}

			return '';
		}

		/**
		 * Emoji for a Pro reaction slug, from Pro's own reaction settings.
		 *
		 * @param string $item_type Content type.
		 * @param string $slug      Reaction slug.
		 * @return string
		 */
		protected static function reaction_emoji( $item_type, $slug ) {
			static $map = array();

			if ( ! method_exists( 'WP_Ulike_Pro_Engagement_Settings', 'get_configured_reactions' ) ) {
				return '';
			}

			if ( ! isset( $map[ $item_type ] ) ) {
				$map[ $item_type ] = array();
				foreach ( (array) WP_Ulike_Pro_Engagement_Settings::get_configured_reactions( $item_type ) as $reaction ) {
					if ( ! empty( $reaction['slug'] ) && ! empty( $reaction['emoji'] ) ) {
						$map[ $item_type ][ $reaction['slug'] ] = (string) $reaction['emoji'];
					}
				}
			}

			return isset( $map[ $item_type ][ $slug ] ) ? $map[ $item_type ][ $slug ] : '';
		}

		/**
		 * Full sentence pattern, so each language can order the words its own way.
		 * %1$s is the person, %2$s the item; the panel fills both in.
		 *
		 * @param string $kind   vote, emoji or star.
		 * @param string $key    like, dislike, or a reaction key.
		 * @param string $type   post, comment or activity.
		 * @param int    $others People besides the named one.
		 * @return string
		 */
		protected static function sentence( $kind, $key, $type, $others = 0 ) {
			$on_comment = 'comment' === $type;

			if ( $others > 0 ) {
				return str_replace( '%3$s', number_format_i18n( $others ), self::group_sentence( $kind, $key, $on_comment, $others ) );
			}

			if ( 'star' === $kind ) {
				/* translators: 1: person, 2: post title */
				return $on_comment ? __( '%1$s rated a comment on %2$s', 'wp-ulike' ) : __( '%1$s rated %2$s', 'wp-ulike' );
			}

			if ( 'emoji' === $kind ) {
				/* translators: 1: person, 2: post title */
				return $on_comment ? __( '%1$s reacted to a comment on %2$s', 'wp-ulike' ) : __( '%1$s reacted to %2$s', 'wp-ulike' );
			}

			if ( 'dislike' === $key ) {
				/* translators: 1: person, 2: post title */
				return $on_comment ? __( '%1$s disliked a comment on %2$s', 'wp-ulike' ) : __( '%1$s disliked %2$s', 'wp-ulike' );
			}

			/* translators: 1: person, 2: post title */
			return $on_comment ? __( '%1$s liked a comment on %2$s', 'wp-ulike' ) : __( '%1$s liked %2$s', 'wp-ulike' );
		}

		/**
		 * Sentence for the current user's own vote. Separate strings, since many
		 * languages change the verb for "you".
		 *
		 * @param string $kind       vote, emoji or star.
		 * @param string $key        like, dislike, or a reaction key.
		 * @param bool   $on_comment Whether the item is a comment.
		 * @return string
		 */
		protected static function own_sentence( $kind, $key, $on_comment ) {
			if ( 'star' === $kind ) {
				/* translators: 1: "You", 2: post title */
				return $on_comment ? _x( '%1$s rated a comment on %2$s', 'current user', 'wp-ulike' ) : _x( '%1$s rated %2$s', 'current user', 'wp-ulike' );
			}

			if ( 'emoji' === $kind ) {
				/* translators: 1: "You", 2: post title */
				return $on_comment ? _x( '%1$s reacted to a comment on %2$s', 'current user', 'wp-ulike' ) : _x( '%1$s reacted to %2$s', 'current user', 'wp-ulike' );
			}

			if ( 'dislike' === $key ) {
				/* translators: 1: "You", 2: post title */
				return $on_comment ? _x( '%1$s disliked a comment on %2$s', 'current user', 'wp-ulike' ) : _x( '%1$s disliked %2$s', 'current user', 'wp-ulike' );
			}

			/* translators: 1: "You", 2: post title */
			return $on_comment ? _x( '%1$s liked a comment on %2$s', 'current user', 'wp-ulike' ) : _x( '%1$s liked %2$s', 'current user', 'wp-ulike' );
		}

		/**
		 * Sentence for several people, e.g. "%1$s and %3$s others liked %2$s".
		 *
		 * @param string $kind       vote, emoji or star.
		 * @param string $key        like, dislike, or a reaction key.
		 * @param bool   $on_comment Whether the item is a comment.
		 * @param int    $others     People besides the named one.
		 * @return string
		 */
		protected static function group_sentence( $kind, $key, $on_comment, $others ) {
			if ( 'star' === $kind ) {
				return $on_comment
					/* translators: 1: person, 2: post title, 3: number of other people */
					? _n( '%1$s and %3$s other rated a comment on %2$s', '%1$s and %3$s others rated a comment on %2$s', $others, 'wp-ulike' )
					/* translators: 1: person, 2: post title, 3: number of other people */
					: _n( '%1$s and %3$s other rated %2$s', '%1$s and %3$s others rated %2$s', $others, 'wp-ulike' );
			}

			if ( 'emoji' === $kind ) {
				return $on_comment
					/* translators: 1: person, 2: post title, 3: number of other people */
					? _n( '%1$s and %3$s other reacted to a comment on %2$s', '%1$s and %3$s others reacted to a comment on %2$s', $others, 'wp-ulike' )
					/* translators: 1: person, 2: post title, 3: number of other people */
					: _n( '%1$s and %3$s other reacted to %2$s', '%1$s and %3$s others reacted to %2$s', $others, 'wp-ulike' );
			}

			if ( 'dislike' === $key ) {
				return $on_comment
					/* translators: 1: person, 2: post title, 3: number of other people */
					? _n( '%1$s and %3$s other disliked a comment on %2$s', '%1$s and %3$s others disliked a comment on %2$s', $others, 'wp-ulike' )
					/* translators: 1: person, 2: post title, 3: number of other people */
					: _n( '%1$s and %3$s other disliked %2$s', '%1$s and %3$s others disliked %2$s', $others, 'wp-ulike' );
			}

			return $on_comment
				/* translators: 1: person, 2: post title, 3: number of other people */
				? _n( '%1$s and %3$s other liked a comment on %2$s', '%1$s and %3$s others liked a comment on %2$s', $others, 'wp-ulike' )
				/* translators: 1: person, 2: post title, 3: number of other people */
				: _n( '%1$s and %3$s other liked %2$s', '%1$s and %3$s others liked %2$s', $others, 'wp-ulike' );
		}

		/**
		 * Title and link for an item, or null when it no longer exists.
		 *
		 * @param string $item_type post, comment, activity or topic.
		 * @param int    $item_id   Item ID.
		 * @return array{type:string,title:string,url:string}|null
		 */
		protected static function describe_item( $item_type, $item_id ) {
			if ( $item_id < 1 ) {
				return null;
			}

			switch ( $item_type ) {
				case 'comment':
					$comment = get_comment( $item_id );
					if ( ! $comment ) {
						return null;
					}

					return array(
						'type'  => 'comment',
						'title' => self::post_title( (int) $comment->comment_post_ID ),
						'url'   => (string) get_comment_link( $comment ),
					);

				case 'activity':
					return array(
						'type'  => 'activity',
						'title' => __( 'an activity update', 'wp-ulike' ),
						'url'   => function_exists( 'bp_activity_get_permalink' ) ? (string) bp_activity_get_permalink( $item_id ) : '',
					);

				default:
					if ( ! get_post( $item_id ) ) {
						return null;
					}

					return array(
						'type'  => 'post',
						'title' => self::post_title( $item_id ),
						'url'   => (string) get_permalink( $item_id ),
					);
			}
		}

		/**
		 * @param int $post_id Post ID.
		 * @return string
		 */
		protected static function post_title( $post_id ) {
			$title = self::plain_text( get_the_title( $post_id ) );

			return '' !== $title ? $title : __( '(no title)', 'wp-ulike' );
		}

		/**
		 * Plain text for the panel, which renders with textContent: titles come
		 * HTML-encoded ("&amp;", "&#8217;") and would show the entity as-is.
		 *
		 * @param string $text Text that may hold tags or entities.
		 * @return string
		 */
		protected static function plain_text( $text ) {
			return html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
		}

		/**
		 * @return string
		 */
		public static function statistics_url() {
			return admin_url( 'admin.php?page=wp-ulike-statistics' );
		}
	}
}
