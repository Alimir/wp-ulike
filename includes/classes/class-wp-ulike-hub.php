<?php
/**
 * ULike Hub: the WP ULike icon in the admin bar with what matters right now.
 *
 * The panel shows one useful tip, at most one message from the WP ULike team,
 * and the latest likes. Built so it costs nothing on busy sites:
 * - Visitors and users who cannot see Statistics never run any of it.
 * - The badge is the existing site-wide vote counter minus a per-user "seen"
 *   mark: a vote adds no extra write, a page load reads two cached values.
 * - The panel reads stored snapshots only: the latest activity
 *   (WP_Ulike_Hub_Feed), numbers counted in the background
 *   (WP_Ulike_Hub_Insights), and downloaded messages (WP_Ulike_Hub_News).
 * - The browser keeps the last panel for the session and shows it at once.
 *
 * Other features add tips through the `wp_ulike_hub_hints` filter.
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
		const AJAX_SEEN      = 'wp_ulike_hub_seen';
		const AJAX_DISMISS   = 'wp_ulike_hub_dismiss_hint';
		const OPTION_KEY     = 'enable_hub';
		const SEEN_META_KEY  = 'wp_ulike_hub_seen';
		const HINTS_META_KEY = 'wp_ulike_hub_hints';

		/**
		 * Tips that stand in for an admin notice, and share its dismissal.
		 */
		const NOTICE_HINTS = array(
			'review' => 'wp_ulike_leave_a_review',
		);

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
			add_action( 'wp_ajax_' . self::AJAX_SEEN, array( __CLASS__, 'ajax_seen' ) );
			add_action( 'wp_ajax_' . self::AJAX_DISMISS, array( __CLASS__, 'ajax_dismiss_hint' ) );
			add_action( 'wp_ulike_new_vote_counted', array( __CLASS__, 'skip_own_vote' ) );
			add_filter( 'heartbeat_received', array( __CLASS__, 'heartbeat' ), 10, 2 );

			WP_Ulike_Hub_Feed::init();
			WP_Ulike_Hub_Insights::init();
			WP_Ulike_Hub_News::init();
		}

		/**
		 * Settings → General → ULike Hub. On unless turned off.
		 *
		 * @return bool
		 */
		public static function is_setting_on() {
			return wp_ulike_is_true( wp_ulike_get_option( self::OPTION_KEY, true ) );
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
		 * Mark votes as seen for one user, on this site only.
		 *
		 * @param int      $user_id User ID, current user when 0.
		 * @param int|null $upto    Counter value the user saw; everything when null.
		 * @return void
		 */
		public static function mark_seen( $user_id = 0, $upto = null ) {
			$user_id = $user_id ? (int) $user_id : get_current_user_id();
			if ( ! $user_id || ! self::get_unseen( $user_id ) ) {
				return;
			}

			$total = self::get_total();
			$upto  = null === $upto ? $total : min( max( 0, (int) $upto ), $total );
			$seen  = (int) get_user_option( self::SEEN_META_KEY, $user_id );

			if ( $upto > ( $seen > $total ? 0 : $seen ) ) {
				update_user_option( $user_id, self::SEEN_META_KEY, $upto );
			}
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
				$response['wp_ulike_hub'] = array(
					'unseen' => $unseen,
					'total'  => self::get_total(),
				) + self::badge_text( $unseen );
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
					'seenAction'    => self::AJAX_SEEN,
					'dismissAction' => self::AJAX_DISMISS,
					'nonce'         => wp_create_nonce( 'wp_ulike_hub' ),
					'total'         => self::get_total(),
					'unseen'        => self::get_unseen(),
					'screen'        => is_admin() ? 'admin' : 'site',
					// Keys the browser copy of the panel to this user, site, screen and language.
					'cacheKey'      => 'wpUlikeHub:' . get_current_blog_id() . ':' . get_current_user_id() . ':' . ( is_admin() ? 'admin' : 'site' ) . ':' . determine_locale() . ':' . WP_ULIKE_VERSION,
					'locale'        => str_replace( '_', '-', determine_locale() ),
					'i18n'          => array(
						'title'    => __( 'ULike Hub', 'wp-ulike' ),
						'loading'  => __( 'Loading…', 'wp-ulike' ),
						'empty'    => __( 'No likes yet. They will show up here as they come in.', 'wp-ulike' ),
						'error'    => __( 'Could not load. Try again in a moment.', 'wp-ulike' ),
						'logs'     => __( 'View logs', 'wp-ulike' ),
						'stats'    => __( 'Statistics', 'wp-ulike' ),
						'new'      => __( 'New', 'wp-ulike' ),
						'today'    => __( 'Today', 'wp-ulike' ),
						'week'     => __( 'This week', 'wp-ulike' ),
						'earlier'  => __( 'Earlier', 'wp-ulike' ),
						'justNow'  => __( 'just now', 'wp-ulike' ),
						'dismiss'  => __( 'Dismiss', 'wp-ulike' ),
						'team'     => __( 'WP ULike', 'wp-ulike' ),
						'fromTeam' => __( 'From the WP ULike team', 'wp-ulike' ),
					),
				)
			);
		}

		/* ------------------------------------------------------------------
		 * AJAX
		 * ------------------------------------------------------------------ */

		/**
		 * Panel data. Read-only, so the browser may fetch it ahead of a click.
		 *
		 * @return void
		 */
		public static function ajax_panel() {
			check_ajax_referer( 'wp_ulike_hub', 'nonce' );

			if ( ! self::is_setting_on() || ! self::current_user_can_view() ) {
				wp_send_json_error( null, 403 );
			}

			$unseen = self::get_unseen();
			$live   = WP_Ulike_Hub_Insights::live();
			$hints  = self::pick_hints( $live, self::on_admin_screen() );
			$items  = self::get_items( $unseen );

			wp_send_json_success(
				array(
					'total'    => self::get_total(),
					'hint'     => $hints['hint'],
					'news'     => $hints['news'],
					'items'    => $items,
					'badge'    => array( 'unseen' => $unseen ) + self::badge_text( $unseen ),
					'today'    => null === $live['today'] ? '' : sprintf(
						/* translators: %s: number of votes today */
						_n( '%s today', '%s today', $live['today'], 'wp-ulike' ),
						number_format_i18n( $live['today'] )
					),
					'quiet'    => 0 === $live['today'],
					'trend'    => self::day_trend( $live ),
					'logsUrl'  => self::logs_url( isset( $items[0]['type'] ) ? $items[0]['type'] : 'post' ),
					'statsUrl' => self::statistics_url(),
				)
			);
		}

		/**
		 * The panel was opened: mark what it showed as seen.
		 *
		 * @return void
		 */
		public static function ajax_seen() {
			check_ajax_referer( 'wp_ulike_hub', 'nonce' );

			if ( ! self::is_setting_on() || ! self::current_user_can_view() ) {
				wp_send_json_error( null, 403 );
			}

			self::mark_seen( 0, isset( $_POST['total'] ) ? absint( $_POST['total'] ) : null );

			$unseen = self::get_unseen();
			wp_send_json_success( array( 'badge' => array( 'unseen' => $unseen ) + self::badge_text( $unseen ) ) );
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

			if ( isset( self::NOTICE_HINTS[ $id ] ) ) {
				// Used the link: done for good. Closed it: ask again in two weeks,
				// like the notice's "Maybe later".
				if ( current_user_can( 'manage_options' ) && function_exists( 'wp_ulike_dismiss_notice' ) ) {
					wp_ulike_dismiss_notice( self::NOTICE_HINTS[ $id ], empty( $_POST['done'] ) ? 2 * WEEK_IN_SECONDS : 10 * YEAR_IN_SECONDS );
				}
			} else {
				$dismissed        = self::get_dismissed_hints();
				$dismissed[ $id ] = time();

				// Keep the list small: drop the oldest beyond 50 entries.
				if ( count( $dismissed ) > 50 ) {
					asort( $dismissed );
					$dismissed = array_slice( $dismissed, -50, null, true );
				}

				update_user_option( get_current_user_id(), self::HINTS_META_KEY, $dismissed );
			}

			wp_send_json_success( self::pick_hints( WP_Ulike_Hub_Insights::live(), self::on_admin_screen() ) );
		}

		/**
		 * Whether the panel asking is open on an admin screen. Every panel request
		 * goes through admin-ajax.php, so the page says where it is.
		 *
		 * @return bool
		 */
		protected static function on_admin_screen() {
			return isset( $_POST['screen'] ) && 'admin' === $_POST['screen']; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller.
		}

		/* ------------------------------------------------------------------
		 * Tips and messages
		 * ------------------------------------------------------------------ */

		/**
		 * @return array<string, int>
		 */
		protected static function get_dismissed_hints() {
			$dismissed = get_user_option( self::HINTS_META_KEY );

			return is_array( $dismissed ) ? $dismissed : array();
		}

		/**
		 * The most useful tip and the first team message this user has not
		 * dismissed. At most one of the two promotes something: a team message
		 * takes the place of a Pro tip. Neither shows on the front end.
		 *
		 * @param array<string, mixed> $live     Live numbers.
		 * @param bool                 $on_admin Whether the panel is open on an admin screen.
		 * @return array{hint:?array,news:?array}
		 */
		public static function pick_hints( $live, $on_admin = true ) {
			$dismissed = self::get_dismissed_hints();
			$news      = $on_admin && current_user_can( 'manage_options' ) ? WP_Ulike_Hub_News::pick( $dismissed, $live ) : null;
			$quiet     = ! $on_admin || $news || WP_Ulike_Hub_Insights::promo_cooling_down( $dismissed ) || wp_ulike_is_true( wp_ulike_get_option( 'disable_admin_notice', false ) );

			/**
			 * Tips for the ULike Hub panel. Lower priority shows first; one at a time.
			 *
			 * Each tip: id (unique; change it to show a tip again), priority, icon
			 * (tip, trend, star, milestone, warning, heart, pro), text, and an
			 * optional action with label and url. Set promo to true for tips that
			 * sell something, so they follow the Hub's limits on promotion.
			 *
			 * @param array<int, array<string, mixed>> $hints Tips.
			 */
			$hints = (array) apply_filters( 'wp_ulike_hub_hints', WP_Ulike_Hub_Insights::hints() );

			usort(
				$hints,
				static function ( $a, $b ) {
					return ( isset( $a['priority'] ) ? (int) $a['priority'] : 50 ) - ( isset( $b['priority'] ) ? (int) $b['priority'] : 50 );
				}
			);

			$picked = null;
			foreach ( $hints as $hint ) {
				if ( empty( $hint['id'] ) || empty( $hint['text'] ) || isset( $dismissed[ sanitize_key( $hint['id'] ) ] ) ) {
					continue;
				}
				if ( ! empty( $hint['promo'] ) && $quiet ) {
					continue;
				}

				$id     = sanitize_key( $hint['id'] );
				$picked = array(
					'id'     => $id,
					'icon'   => isset( $hint['icon'] ) ? sanitize_key( $hint['icon'] ) : 'tip',
					'text'   => self::plain_text( $hint['text'] ),
					'promo'  => ! empty( $hint['promo'] ),
					'done'   => isset( self::NOTICE_HINTS[ $id ] ),
					'action' => ! empty( $hint['action']['url'] ) && ! empty( $hint['action']['label'] )
						? array(
							'label' => self::plain_text( $hint['action']['label'] ),
							'url'   => esc_url_raw( $hint['action']['url'] ),
						)
						: null,
				);
				break;
			}

			return array(
				'hint' => $picked,
				'news' => $news,
			);
		}

		/**
		 * Today against yesterday, for the header chip.
		 *
		 * @param array<string, mixed> $live Live numbers.
		 * @return string up, down, or empty.
		 */
		protected static function day_trend( $live ) {
			if ( null === $live['today'] || null === $live['yesterday'] || $live['yesterday'] < 5 ) {
				return '';
			}

			// Today is unfinished; compare it with the same share of yesterday.
			$midnight = ( new DateTimeImmutable( 'today', wp_timezone() ) )->getTimestamp();
			$elapsed  = ( time() - $midnight ) / DAY_IN_SECONDS;
			$pace     = $live['yesterday'] * max( 0.05, $elapsed );

			if ( $live['today'] >= $pace * 1.2 ) {
				return 'up';
			}

			return $live['today'] <= $pace * 0.8 && $elapsed > 0.25 ? 'down' : '';
		}

		/* ------------------------------------------------------------------
		 * Latest activity
		 * ------------------------------------------------------------------ */

		/**
		 * Latest activity, one row per item and action ("Sara and 12 others liked X").
		 *
		 * @param int $unseen New votes for this user; rows holding one of them are marked new.
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_items( $unseen = 0 ) {
			$feed = WP_Ulike_Hub_Feed::get();
			$me   = get_current_user_id();

			// Votes newer than the snapshot are not in it.
			$unseen -= max( 0, self::get_total() - (int) $feed['total'] );

			// Your own votes are not in $unseen (skip_own_vote), so they take no rank.
			$first = array();
			$rank  = 0;
			foreach ( $feed['rows'] as $row ) {
				$row_rank = $row[0] === $me ? PHP_INT_MAX : $rank++;
				if ( $row[1] >= 0 && ( ! isset( $first[ $row[1] ] ) || $row_rank < $first[ $row[1] ] ) ) {
					$first[ $row[1] ] = $row_rank;
				}
			}

			$items = array();
			foreach ( $feed['groups'] as $index => $group ) {
				$people = $group['people'];
				$count  = count( $people );

				// Name the newest person who is not you; "Sara and 3 others" may include you.
				$shown = $people[0];
				if ( $count > 1 ) {
					foreach ( $people as $person ) {
						if ( $person !== $me ) {
							$shown = $person;
							break;
						}
					}
				}

				$others = $count - 1;
				if ( 'vote' === $group['kind'] ) {
					$others = max( $others, (int) $group['total'] - 1 );
				}

				$second = null;
				foreach ( $people as $person ) {
					if ( $person !== $shown ) {
						$second = $person;
						break;
					}
				}

				$is_you     = $shown && $shown === $me;
				$on_comment = 'comment' === $group['target']['type'];

				$items[] = array(
					'name'    => $is_you ? __( 'You', 'wp-ulike' ) : self::user_name( $feed, $shown ),
					'avatar'  => self::user_avatar( $feed, $shown ),
					'avatar2' => $others > 0 && null !== $second ? self::user_avatar( $feed, $second ) : '',
					'stack'   => $others > 0,
					'text'    => $is_you && ! $others
						? self::own_sentence( $group['kind'], $group['key'], $on_comment )
						: self::sentence( $group['kind'], $group['key'], $group['target']['type'], $others ),
					'badge'   => self::badge( $group ),
					'title'   => $group['target']['title'],
					'url'     => esc_url_raw( $group['target']['url'] ),
					'thumb'   => esc_url_raw( $group['target']['thumb'] ),
					'ts'      => (int) $group['ts'],
					'kind'    => 'dislike' === $group['key'] ? 'dislike' : 'like',
					'type'    => $group['type'],
					'isNew'   => isset( $first[ $index ] ) && $first[ $index ] < $unseen,
				);
			}

			return $items;
		}

		/**
		 * @param array<string, mixed> $feed    Activity snapshot.
		 * @param int                  $user_id User ID, 0 for a guest.
		 * @return string
		 */
		protected static function user_name( $feed, $user_id ) {
			return $user_id && isset( $feed['users'][ $user_id ] ) ? $feed['users'][ $user_id ]['name'] : __( 'A visitor', 'wp-ulike' );
		}

		/**
		 * @param array<string, mixed> $feed    Activity snapshot.
		 * @param int|null             $user_id User ID, 0 for a guest.
		 * @return string Empty for guests, who get the default avatar.
		 */
		protected static function user_avatar( $feed, $user_id ) {
			return $user_id && isset( $feed['users'][ $user_id ] ) ? esc_url_raw( $feed['users'][ $user_id ]['avatar'] ) : '';
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
		 * Small badge on the avatar: the reaction emoji, or the star score.
		 * Empty for likes and dislikes, which use the heart.
		 *
		 * @param array<string, mixed> $group Activity group.
		 * @return string
		 */
		protected static function badge( $group ) {
			if ( 'star' === $group['kind'] && ! empty( $group['value'] ) ) {
				return absint( $group['value'] ) . '★';
			}

			if ( 'emoji' === $group['kind'] ) {
				return self::reaction_emoji( (string) $group['type'], (string) $group['key'] );
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
		 * Plain text for the panel, which renders with textContent: titles come
		 * HTML-encoded ("&amp;", "&#8217;") and would show the entity as-is.
		 *
		 * @param string $text Text that may hold tags or entities.
		 * @return string
		 */
		public static function plain_text( $text ) {
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
