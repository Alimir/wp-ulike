<?php
/**
 * ULike Hub: the numbers behind the tips, and the tips themselves.
 *
 * Date-range counts scan the vote tables, which is slow on large sites, so
 * they never run while someone waits for the panel. A background task stores
 * them once an hour (yesterday and last week once a day), and the panel adds
 * the votes counted since then, so "today" stays live at no cost.
 *
 * Tips are built from those stored numbers on each open, in the language of
 * the person reading them.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Hub_Insights' ) ) {

	/**
	 * Stored numbers and tips.
	 */
	class WP_Ulike_Hub_Insights {

		const OPTION  = 'wp_ulike_hub_facts';
		const LOCK    = 'wp_ulike_hub_facts_lock';
		const HOOK    = 'wp_ulike_hub_refresh_facts';
		const VERSION = 3;

		/**
		 * Stored numbers older than this are refreshed in the background.
		 */
		const MAX_AGE = HOUR_IN_SECONDS;

		/**
		 * Past this age the background task is clearly not running; refresh in place.
		 */
		const HARD_AGE = 6 * HOUR_IN_SECONDS;

		/**
		 * Like totals worth celebrating, per post.
		 */
		const POST_MILESTONES = array( 10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000 );

		/**
		 * Like totals worth celebrating, site-wide.
		 */
		const SITE_MILESTONES = array( 100, 500, 1000, 5000, 10000, 50000, 100000, 500000, 1000000 );

		/**
		 * A post milestone needs a vote on the post within this window.
		 */
		const MILESTONE_FRESH = WEEK_IN_SECONDS;

		/**
		 * After someone dismisses a Pro tip, no other one shows for this long.
		 */
		const PROMO_COOLDOWN = 21 * DAY_IN_SECONDS;

		/**
		 * @var array<string, mixed>|null Per-request copy.
		 */
		private static $facts = null;

		/**
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'refresh' ) );
		}

		/* ------------------------------------------------------------------
		 * Stored numbers
		 * ------------------------------------------------------------------ */

		/**
		 * Stored numbers, refreshing them in the background when they are old.
		 *
		 * @return array<string, mixed>
		 */
		public static function facts() {
			if ( null !== self::$facts ) {
				return self::$facts;
			}

			$facts = get_option( self::OPTION );

			if ( ! is_array( $facts ) || ! isset( $facts['v'] ) || self::VERSION !== $facts['v'] ) {
				// Nothing stored yet. Counting can take a while on a large site, so
				// leave it to the background task, unless that has not run in ten minutes.
				$waiting = (int) get_transient( self::LOCK . '_since' );
				if ( $waiting && time() - $waiting > 10 * MINUTE_IN_SECONDS ) {
					$facts = self::refresh();
				} else {
					if ( ! $waiting ) {
						set_transient( self::LOCK . '_since', time(), HOUR_IN_SECONDS );
					}
					self::schedule();
					$facts = self::placeholder();
				}
			} else {
				$age = time() - (int) $facts['built'];

				if ( $age > self::HARD_AGE ) {
					$facts = self::refresh();
				} elseif ( $age > self::MAX_AGE || current_time( 'Ymd' ) !== $facts['day'] ) {
					self::schedule();
				}
			}

			return self::$facts = $facts;
		}

		/**
		 * Live numbers: the stored ones plus votes counted since they were stored.
		 *
		 * Values that belong to a day or week that has ended are null until the
		 * background task catches up.
		 *
		 * @return array{all_time:int,today:?int,yesterday:?int,week_done:?int,last_week_same:?int,days_done:int,top:?array}
		 */
		public static function live() {
			$facts    = self::facts();
			$total    = WP_Ulike_Hub::get_total();
			$same_day = current_time( 'Ymd' ) === $facts['day'];

			// The counter can be reset (storage rebuild); then nothing is added.
			$delta = $total >= (int) $facts['total'] ? $total - (int) $facts['total'] : 0;

			return array(
				// Kept up to date on every vote, so it is read as it stands.
				'all_time'       => function_exists( 'wp_ulike_count_all_logs' ) ? (int) wp_ulike_count_all_logs() : 0,
				'today'          => $same_day ? (int) $facts['today'] + $delta : null,
				'yesterday'      => $same_day ? (int) $facts['yesterday'] : null,
				'week_done'      => $same_day ? (int) $facts['week_done'] : null,
				'last_week_same' => $same_day ? (int) $facts['last_week_same'] : null,
				'days_done'      => (int) $facts['days_done'],
				'top'            => $same_day && ! empty( $facts['top'] ) ? $facts['top'] : null,
			);
		}

		/**
		 * Stand-in until the first count is stored: no day-bound numbers.
		 *
		 * @return array<string, mixed>
		 */
		protected static function placeholder() {
			return array(
				'v'              => self::VERSION,
				'built'          => time(),
				'day'            => '',
				'total'          => WP_Ulike_Hub::get_total(),
				'today'          => 0,
				'yesterday'      => 0,
				'days_done'      => 0,
				'week_done'      => 0,
				'last_week_same' => 0,
				'top'            => null,
			);
		}

		/**
		 * @return void
		 */
		public static function schedule() {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_single_event( time(), self::HOOK );
			}
		}

		/**
		 * Count and store. Runs in the background, or once in place on a new site.
		 *
		 * @return array<string, mixed>
		 */
		public static function refresh() {
			$previous = get_option( self::OPTION );
			$previous = is_array( $previous ) && isset( $previous['v'] ) && self::VERSION === $previous['v'] ? $previous : array();

			if ( $previous && get_transient( self::LOCK ) ) {
				return $previous;
			}
			set_transient( self::LOCK, 1, 5 * MINUTE_IN_SECONDS );

			// Read the counter first: votes cast while counting then show up in
			// the counts, not twice.
			$total = WP_Ulike_Hub::get_total();
			$day   = current_time( 'Ymd' );
			// Straight to the query class: wp_ulike_count_all_logs() flattens date
			// ranges into a cache key and then counts everything.
			$count = static function ( $period ) {
				return class_exists( 'WP_Ulike_Pulse_Query' ) ? (int) WP_Ulike_Pulse_Query::count_logs_for_mode( $period ) : 0;
			};

			$facts = array(
				'v'     => self::VERSION,
				'built' => time(),
				'day'   => $day,
				'total' => $total,
				'today' => $count( 'today' ),
				'top'   => self::top_today(),
			);

			// These only change when the day changes.
			if ( isset( $previous['day'] ) && $day === $previous['day'] ) {
				$facts['yesterday']      = (int) $previous['yesterday'];
				$facts['days_done']      = (int) $previous['days_done'];
				$facts['week_done']      = (int) $previous['week_done'];
				$facts['last_week_same'] = (int) $previous['last_week_same'];
			} else {
				$facts['yesterday'] = $count( 'yesterday' );
				$facts             += self::week_so_far( $count );
			}

			update_option( self::OPTION, $facts, false );
			delete_transient( self::LOCK );
			delete_transient( self::LOCK . '_since' );
			self::$facts = $facts;

			return $facts;
		}

		/**
		 * Complete days of this week against the same days of last week.
		 *
		 * Comparing an unfinished week with a whole one always reads as a drop
		 * early in the week, so today is left out on both sides.
		 *
		 * @param callable $count Period counter.
		 * @return array{days_done:int,week_done:int,last_week_same:int}
		 */
		protected static function week_so_far( $count ) {
			$today     = new DateTimeImmutable( 'today', wp_timezone() );
			$days_done = ( (int) $today->format( 'w' ) - (int) get_option( 'start_of_week', 1 ) + 7 ) % 7;

			if ( $days_done < 1 ) {
				return array(
					'days_done'      => 0,
					'week_done'      => 0,
					'last_week_same' => 0,
				);
			}

			$start = $today->modify( "-{$days_done} days" );
			$end   = $today->modify( '-1 day' );
			$range = static function ( $from, $to ) use ( $count ) {
				return $count(
					array(
						'start' => $from->format( 'Y-m-d' ),
						'end'   => $to->format( 'Y-m-d' ),
					)
				);
			};

			return array(
				'days_done'      => $days_done,
				'week_done'      => $range( $start, $end ),
				'last_week_same' => $range( $start->modify( '-7 days' ), $end->modify( '-7 days' ) ),
			);
		}

		/**
		 * Today's most liked post.
		 *
		 * @return array{id:int,count:int,post_type:string}|null
		 */
		protected static function top_today() {
			if ( ! function_exists( 'wp_ulike_get_popular_items_info' ) ) {
				return null;
			}

			$top = wp_ulike_get_popular_items_info(
				array(
					'type'     => 'post',
					'rel_type' => '',
					'period'   => 'today',
					'limit'    => 1,
				)
			);

			if ( empty( $top[0]->item_ID ) || empty( $top[0]->counter ) ) {
				return null;
			}

			return array(
				'id'        => (int) $top[0]->item_ID,
				'count'     => (int) $top[0]->counter,
				'post_type' => (string) get_post_type( (int) $top[0]->item_ID ),
			);
		}

		/* ------------------------------------------------------------------
		 * Tips
		 * ------------------------------------------------------------------ */

		/**
		 * Built-in tips for the current user. Lower priority shows first.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function hints() {
			$live     = self::live();
			$is_admin = current_user_can( 'manage_options' );
			$is_pro   = defined( 'WP_ULIKE_PRO_VERSION' );
			$hints    = array();

			if ( $is_admin && $is_pro ) {
				$hints[] = self::license_hint();
			}

			if ( 0 === $live['all_time'] ) {
				return array_values( array_filter( array_merge( $hints, self::setup_hints( $is_admin && ! $is_pro, true ) ) ) );
			}

			$hints = array_merge( $hints, self::setup_hints( $is_admin && ! $is_pro, false ) );

			$feed    = WP_Ulike_Hub_Feed::get();
			$trend   = self::trend_change( $live );
			$hints[] = self::first_likes_hint( $live );
			$hints[] = self::dislike_hint( $feed );
			$hints[] = self::site_milestone_hint( $live );
			$hints[] = self::post_milestone_hint( $feed );
			$hints[] = self::trending_hint( $feed );
			$hints[] = self::top_today_hint( $live );
			$hints[] = self::trend_hint( $live, $trend );

			if ( $is_admin ) {
				$hints[] = self::review_hint( $live );
			}

			if ( $is_admin && ! $is_pro ) {
				$hints[] = self::pro_hint( $live, $feed, $trend );
			}

			return array_values( array_filter( $hints ) );
		}

		/**
		 * Whether this user dismissed a Pro tip recently.
		 *
		 * @param array<string, int> $dismissed Dismissed tips and when.
		 * @return bool
		 */
		public static function promo_cooling_down( $dismissed ) {
			foreach ( $dismissed as $id => $time ) {
				if ( 0 === strpos( (string) $id, 'pro_' ) && (int) $time > time() - self::PROMO_COOLDOWN ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Free sites run by an admin: help the button get seen.
		 *
		 * @param bool $applies  Whether setup tips apply to this user.
		 * @param bool $no_votes Whether the site has no votes yet.
		 * @return array<int, array<string, mixed>>
		 */
		protected static function setup_hints( $applies, $no_votes ) {
			$hints        = array();
			$settings_url = admin_url( 'admin.php?page=wp-ulike-settings&settings-page=content-types&settings-section=posts_group' );

			// Pro places buttons in other ways too (Display Automation, widgets), so it skips these.
			if ( $applies && ! wp_ulike_setting_repo::isAutoDisplayOn( 'post' ) ) {
				// Votes exist, so buttons are placed some other way (block, shortcode).
				if ( $no_votes ) {
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
			} elseif ( $applies ) {
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

			if ( $no_votes ) {
				$days = function_exists( 'wp_ulike_get_days_since_activation' ) ? wp_ulike_get_days_since_activation() : null;

				if ( null !== $days && $days >= 2 ) {
					$post = get_posts(
						array(
							'numberposts'      => 1,
							'post_status'      => 'publish',
							'fields'           => 'ids',
							'suppress_filters' => false,
						)
					);

					if ( ! empty( $post ) ) {
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
				}
			}

			return $hints;
		}

		/**
		 * Pro license that needs attention, as the admin notice reports it.
		 * Dismissing it hides it for the rest of the week.
		 *
		 * @return array<string, mixed>|null
		 */
		protected static function license_hint() {
			if ( ! class_exists( 'WP_Ulike_Pro_Validator' ) || '' === (string) get_option( 'wp_ulike_pro_license_key', '' ) ) {
				return null;
			}

			$status = WP_Ulike_Pro_Validator::get_license_status();
			$texts  = array(
				'STATUS_EXPIRED'       => __( 'Your WP ULike Pro license has expired. Renew to keep updates and support.', 'wp-ulike' ),
				'STATUS_DISABLED'      => __( 'Your WP ULike Pro license has been disabled. Renew to keep updates and support.', 'wp-ulike' ),
				'STATUS_INVALID'       => __( 'Your WP ULike Pro license needs attention. Renew to keep updates and support.', 'wp-ulike' ),
				'STATUS_DEACTIVATED'   => __( 'Your WP ULike Pro license needs attention. Renew to keep updates and support.', 'wp-ulike' ),
				'STATUS_SITE_INACTIVE' => __( 'Your WP ULike Pro license needs attention. Renew to keep updates and support.', 'wp-ulike' ),
				'STATUS_MISSING'       => __( 'Your WP ULike Pro license needs attention. Renew to keep updates and support.', 'wp-ulike' ),
			);

			foreach ( $texts as $constant => $text ) {
				// Older Pro builds may not define every status.
				$name = 'WP_Ulike_Pro_Validator::' . $constant;
				if ( ! defined( $name ) || constant( $name ) !== $status ) {
					continue;
				}

				return array(
					'id'       => 'license_' . strtolower( $constant ) . '_' . current_time( 'oW' ),
					'priority' => 5,
					'icon'     => 'warning',
					'text'     => $text,
					'action'   => array(
						/* translators: %s: discount percentage, for example 30%. */
						'label' => sprintf( __( 'Renew with %s off', 'wp-ulike' ), '30%' ),
						'url'   => add_query_arg(
							array(
								'utm_source'   => 'hub',
								'utm_campaign' => '30off',
								'utm_medium'   => 'wp-dash',
								'discount'     => '30OFF',
							),
							WP_ULIKE_PLUGIN_URI . 'pricing/'
						),
					),
				);
			}

			return null;
		}

		/**
		 * The very first likes on a site.
		 *
		 * @param array<string, mixed> $live Live numbers.
		 * @return array<string, mixed>|null
		 */
		protected static function first_likes_hint( $live ) {
			if ( $live['all_time'] > 5 ) {
				return null;
			}

			return array(
				'id'       => 'first_likes',
				'priority' => 12,
				'icon'     => 'milestone',
				'text'     => __( 'Your first likes are in! Every new one shows up here as it happens.', 'wp-ulike' ),
				'action'   => array(
					'label' => __( 'See statistics', 'wp-ulike' ),
					'url'   => WP_Ulike_Hub::statistics_url(),
				),
			);
		}

		/**
		 * A post collecting dislikes fast is worth a look.
		 *
		 * @param array<string, mixed> $feed Activity snapshot.
		 * @return array<string, mixed>|null
		 */
		protected static function dislike_hint( $feed ) {
			$since = time() - DAY_IN_SECONDS;
			$best  = self::busiest_group( $feed, $since, static function ( $group ) {
				return 'vote' === $group['kind'] && 'dislike' === $group['key'];
			} );

			if ( ! $best || $best['count'] < 5 ) {
				return null;
			}

			return array(
				'id'       => 'dislikes_' . $best['group']['id'] . '_' . current_time( 'Ymd' ),
				'priority' => 25,
				'icon'     => 'warning',
				'text'     => sprintf(
					/* translators: 1: post title, 2: number of dislikes */
					__( '“%1$s” got %2$s dislikes in the last day. Worth a look.', 'wp-ulike' ),
					$best['group']['target']['title'],
					number_format_i18n( $best['count'] )
				),
				'action'   => self::view_action( $best['group']['target']['url'] ),
			);
		}

		/**
		 * The site just passed a round number of likes.
		 *
		 * @param array<string, mixed> $live Live numbers.
		 * @return array<string, mixed>|null
		 */
		protected static function site_milestone_hint( $live ) {
			$reached = self::reached( $live['all_time'], self::SITE_MILESTONES );
			if ( ! $reached ) {
				return null;
			}

			return array(
				'id'       => 'site_milestone_' . $reached,
				'priority' => 32,
				'icon'     => 'milestone',
				'text'     => sprintf(
					/* translators: %s: number of likes, for example 1,000 */
					__( 'Your site just passed %s likes. Congratulations!', 'wp-ulike' ),
					number_format_i18n( $reached )
				),
				'action'   => array(
					'label' => __( 'See what worked', 'wp-ulike' ),
					'url'   => WP_Ulike_Hub::statistics_url(),
				),
			);
		}

		/**
		 * A post in the latest activity that just passed a like milestone.
		 *
		 * "Just" means its total is within 10% above the milestone and it had a
		 * vote this week, so an old popular post does not announce a milestone it
		 * reached long ago.
		 *
		 * @param array<string, mixed> $feed Activity snapshot.
		 * @return array<string, mixed>|null
		 */
		protected static function post_milestone_hint( $feed ) {
			foreach ( $feed['groups'] as $group ) {
				if ( 'post' !== $group['target']['type'] || 'vote' !== $group['kind'] || 'dislike' === $group['key'] ) {
					continue;
				}
				if ( $group['ts'] < time() - self::MILESTONE_FRESH ) {
					continue;
				}

				$reached = self::reached( (int) $group['total'], self::POST_MILESTONES );
				if ( ! $reached ) {
					continue;
				}

				return array(
					'id'       => 'milestone_' . $group['id'] . '_' . $reached,
					'priority' => 35,
					'icon'     => 'milestone',
					'text'     => sprintf(
						/* translators: 1: post title, 2: number of likes */
						__( '“%1$s” just passed %2$s likes. Nice work!', 'wp-ulike' ),
						$group['target']['title'],
						number_format_i18n( $reached )
					),
					'action'   => self::view_action( $group['target']['url'] ),
				);
			}

			return null;
		}

		/**
		 * The item collecting the most votes in the last hour.
		 *
		 * @param array<string, mixed> $feed Activity snapshot.
		 * @return array<string, mixed>|null
		 */
		protected static function trending_hint( $feed ) {
			$best = self::busiest_group( $feed, time() - HOUR_IN_SECONDS, static function ( $group ) {
				return 'dislike' !== $group['key'];
			} );

			if ( ! $best || $best['count'] < 5 ) {
				return null;
			}

			return array(
				'id'       => 'trending_' . $best['group']['type'] . $best['group']['id'] . '_' . current_time( 'YmdH' ),
				'priority' => 36,
				'icon'     => 'trend',
				'text'     => sprintf(
					/* translators: 1: post title, 2: number of votes */
					__( '“%1$s” is on a roll: %2$s votes in the last hour.', 'wp-ulike' ),
					$best['group']['target']['title'],
					number_format_i18n( $best['count'] )
				),
				'action'   => self::view_action( $best['group']['target']['url'] ),
			);
		}

		/**
		 * @param array<string, mixed> $live Live numbers.
		 * @return array<string, mixed>|null
		 */
		protected static function top_today_hint( $live ) {
			if ( empty( $live['top'] ) || $live['top']['count'] < 3 ) {
				return null;
			}

			$title = WP_Ulike_Hub::plain_text( get_the_title( $live['top']['id'] ) );
			if ( '' === $title ) {
				return null;
			}

			return array(
				'id'       => 'top_today_' . current_time( 'Ymd' ) . '_' . $live['top']['id'],
				'priority' => 40,
				'icon'     => 'star',
				'text'     => sprintf(
					/* translators: 1: post title, 2: number of likes */
					_n( '“%1$s” is today’s most liked post, with %2$s like.', '“%1$s” is today’s most liked post, with %2$s likes.', $live['top']['count'], 'wp-ulike' ),
					$title,
					number_format_i18n( $live['top']['count'] )
				),
				'action'   => self::view_action( (string) get_permalink( $live['top']['id'] ) ),
			);
		}

		/**
		 * Percent change of this week's complete days against the same days of
		 * last week, or null when there is too little to compare.
		 *
		 * @param array<string, mixed> $live Live numbers.
		 * @return int|null
		 */
		protected static function trend_change( $live ) {
			if ( null === $live['week_done'] || $live['week_done'] < 10 || $live['last_week_same'] < 10 ) {
				return null;
			}

			return (int) round( ( $live['week_done'] - $live['last_week_same'] ) / $live['last_week_same'] * 100 );
		}

		/**
		 * @param array<string, mixed> $live   Live numbers.
		 * @param int|null             $change Percent change.
		 * @return array<string, mixed>|null
		 */
		protected static function trend_hint( $live, $change ) {
			if ( null === $change || abs( $change ) < 10 ) {
				return null;
			}

			return array(
				'id'       => 'trend_' . current_time( 'oW' ) . '_' . ( $change > 0 ? 'up' : 'down' ),
				'priority' => 50,
				'icon'     => 'trend',
				'text'     => $change > 0
					? sprintf(
						/* translators: 1: votes this week, 2: percent */
						__( '%1$s votes so far this week, up %2$s%% on the same days last week.', 'wp-ulike' ),
						number_format_i18n( $live['week_done'] ),
						number_format_i18n( $change )
					)
					: sprintf(
						/* translators: 1: votes this week, 2: percent */
						__( '%1$s votes so far this week, down %2$s%% on the same days last week.', 'wp-ulike' ),
						number_format_i18n( $live['week_done'] ),
						number_format_i18n( abs( $change ) )
					),
				'action'   => array(
					'label' => __( 'See why', 'wp-ulike' ),
					'url'   => WP_Ulike_Hub::statistics_url(),
				),
			);
		}

		/**
		 * The review request, which used to be a dashboard notice. It shares that
		 * notice's dismissal, so "Maybe later" and "Happy to help" carry over.
		 *
		 * @param array<string, mixed> $live Live numbers.
		 * @return array<string, mixed>|null
		 */
		protected static function review_hint( $live ) {
			if ( $live['all_time'] < 25 || ! function_exists( 'wp_ulike_is_notice_dismissed' ) || wp_ulike_is_notice_dismissed( 'wp_ulike_leave_a_review' ) ) {
				return null;
			}

			return array(
				'id'       => 'review',
				'priority' => 60,
				'icon'     => 'heart',
				'promo'    => true,
				'text'     => sprintf(
					/* translators: %s: total like count */
					__( 'You’ve collected %s likes with WP ULike. If it’s been good to you, a quick 5-star review helps a lot.', 'wp-ulike' ),
					number_format_i18n( $live['all_time'] )
				),
				'action'   => array(
					'label' => __( 'Leave a review', 'wp-ulike' ),
					'url'   => 'https://wordpress.org/support/plugin/wp-ulike/reviews/#new-post',
				),
			);
		}

		/**
		 * One Pro suggestion that fits what is happening on the site. Shows only
		 * on free sites with real engagement, never in the badge, and rests for
		 * three weeks after someone dismisses one.
		 *
		 * @param array<string, mixed> $live   Live numbers.
		 * @param array<string, mixed> $feed   Activity snapshot.
		 * @param int|null             $change Weekly percent change.
		 * @return array<string, mixed>|null
		 */
		protected static function pro_hint( $live, $feed, $change ) {
			if ( ! function_exists( 'wp_ulike_should_show_pro_upsells' ) || ! wp_ulike_should_show_pro_upsells() ) {
				return null;
			}

			$product_in_focus = ( ! empty( $live['top']['post_type'] ) && 'product' === $live['top']['post_type'] )
				|| self::recent_share( $feed, static function ( $group ) {
					return 'post' === $group['target']['type'] && 'product' === get_post_type( $group['id'] );
				} ) >= 0.5;

			if ( $product_in_focus && class_exists( 'WooCommerce' ) ) {
				return self::pro_offer( 'pro_woo', __( 'Your products are getting likes. WP ULike Pro puts likes next to sales, so you can see which ones turn into orders.', 'wp-ulike' ) );
			}

			if ( null !== $change && $change >= 20 ) {
				return self::pro_offer(
					'pro_insights',
					sprintf(
						/* translators: %s: percent */
						__( 'Engagement is up %s%% this week. WP ULike Pro shows likes per page view, so you know which posts really connect.', 'wp-ulike' ),
						number_format_i18n( $change )
					)
				);
			}

			$members = self::recent_share( $feed, null, true );
			if ( null !== $members && $members >= 0.5 ) {
				return self::pro_offer( 'pro_profiles', __( 'Most likes come from signed-in members. WP ULike Pro shows who liked each post and gives members profiles to show theirs.', 'wp-ulike' ) );
			}

			return self::pro_offer(
				'pro_reactions',
				sprintf(
					/* translators: %s: total like count */
					__( '%s likes so far. Let visitors say more with emoji reactions and star ratings in WP ULike Pro.', 'wp-ulike' ),
					number_format_i18n( $live['all_time'] )
				)
			);
		}

		/**
		 * @param string $id   Tip ID, starting with "pro_".
		 * @param string $text Tip text.
		 * @return array<string, mixed>
		 */
		protected static function pro_offer( $id, $text ) {
			return array(
				'id'       => $id,
				'priority' => 70,
				'icon'     => 'pro',
				'text'     => $text,
				'promo'    => true,
				'action'   => array(
					'label' => __( 'See what Pro adds', 'wp-ulike' ),
					'url'   => add_query_arg(
						array(
							'utm_source'   => 'hub',
							'utm_campaign' => $id,
							'utm_medium'   => 'wp-dash',
						),
						WP_ULIKE_PLUGIN_URI . 'upgrade/'
					),
				),
			);
		}

		/* ------------------------------------------------------------------
		 * Helpers
		 * ------------------------------------------------------------------ */

		/**
		 * The milestone a count just passed (within 10% above it), or 0.
		 *
		 * @param int        $count      Count.
		 * @param array<int> $milestones Milestones, ascending.
		 * @return int
		 */
		protected static function reached( $count, $milestones ) {
			$reached = 0;
			foreach ( $milestones as $milestone ) {
				if ( $count >= $milestone ) {
					$reached = $milestone;
				}
			}

			return $reached && $count <= $reached * 1.1 ? $reached : 0;
		}

		/**
		 * The group with the most votes since a time, among those a test accepts.
		 *
		 * @param array<string, mixed> $feed   Activity snapshot.
		 * @param int                  $since  Unix time.
		 * @param callable             $accept Group filter.
		 * @return array{group:array,count:int}|null
		 */
		protected static function busiest_group( $feed, $since, $accept ) {
			$counts = array();
			foreach ( $feed['rows'] as $row ) {
				if ( $row[1] >= 0 && $row[2] >= $since ) {
					$counts[ $row[1] ] = ( isset( $counts[ $row[1] ] ) ? $counts[ $row[1] ] : 0 ) + 1;
				}
			}

			arsort( $counts );
			foreach ( $counts as $index => $count ) {
				if ( isset( $feed['groups'][ $index ] ) && $accept( $feed['groups'][ $index ] ) ) {
					return array(
						'group' => $feed['groups'][ $index ],
						'count' => $count,
					);
				}
			}

			return null;
		}

		/**
		 * Share of the recent votes (on shown items) that a test accepts, or that
		 * came from signed-in users. Null when there are too few votes to tell.
		 *
		 * @param array<string, mixed> $feed    Activity snapshot.
		 * @param callable|null        $accept  Group filter.
		 * @param bool                 $members Count signed-in voters instead.
		 * @return float|null
		 */
		protected static function recent_share( $feed, $accept, $members = false ) {
			$all  = 0;
			$hits = 0;
			foreach ( $feed['rows'] as $row ) {
				if ( $row[1] < 0 ) {
					continue;
				}
				$all++;
				if ( $members ? $row[0] > 0 : $accept( $feed['groups'][ $row[1] ] ) ) {
					$hits++;
				}
			}

			return $all >= 20 ? $hits / $all : null;
		}

		/**
		 * @param string $url Link.
		 * @return array{label:string,url:string}|null
		 */
		protected static function view_action( $url ) {
			return $url ? array(
				'label' => __( 'View', 'wp-ulike' ),
				'url'   => $url,
			) : null;
		}
	}
}
