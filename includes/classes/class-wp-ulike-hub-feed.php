<?php
/**
 * ULike Hub: the latest activity, shared by everyone who sees the Hub.
 *
 * One snapshot per site holds the newest votes grouped by item, with titles,
 * links, thumbnails, names and avatars already resolved. Opening the panel
 * then reads one stored value and does the per-user part ("You", "New") in
 * memory. The snapshot is rebuilt when the vote counter moves; a busy site
 * reuses it for a few seconds so a vote every second never means a rebuild
 * on every open.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Hub_Feed' ) ) {

	/**
	 * Shared activity snapshot.
	 */
	class WP_Ulike_Hub_Feed {

		const TRANSIENT = 'wp_ulike_hub_feed';
		const LOCK      = 'wp_ulike_hub_feed_lock';
		const VERSION   = 2;

		/**
		 * Grouped rows the panel shows.
		 */
		const ITEMS = 6;

		/**
		 * Recent votes read to build the groups.
		 */
		const SCAN_ROWS = 60;

		/**
		 * Seconds a snapshot stays in use after new votes arrive.
		 */
		const THROTTLE = 15;

		/**
		 * @var array<string, mixed>|false|null Per-request copy; false once flushed.
		 */
		private static $snapshot = null;

		/**
		 * @return void
		 */
		public static function init() {
			foreach ( array( 'trashed_post', 'deleted_post', 'trashed_comment', 'deleted_comment', 'deleted_user' ) as $hook ) {
				add_action( $hook, array( __CLASS__, 'flush' ) );
			}
		}

		/**
		 * Forget the snapshot, so a removed post or user disappears from the panel.
		 *
		 * @return void
		 */
		public static function flush() {
			// Bulk deletes fire this per item; once per request is enough.
			if ( false === self::$snapshot ) {
				return;
			}

			self::$snapshot = false;
			delete_transient( self::TRANSIENT );
		}

		/**
		 * The current snapshot.
		 *
		 * @return array{v:int,total:int,built:int,rows:array,groups:array,users:array}
		 */
		public static function get() {
			if ( is_array( self::$snapshot ) ) {
				return self::$snapshot;
			}

			$total    = WP_Ulike_Hub::get_total();
			$snapshot = get_transient( self::TRANSIENT );
			$usable   = is_array( $snapshot ) && isset( $snapshot['v'] ) && self::VERSION === $snapshot['v'];

			if ( $usable && ( (int) $snapshot['total'] === $total || time() - (int) $snapshot['built'] < self::THROTTLE ) ) {
				return self::$snapshot = $snapshot;
			}

			// Another request is rebuilding it right now: the previous one is close enough.
			if ( $usable && get_transient( self::LOCK ) ) {
				return self::$snapshot = $snapshot;
			}

			set_transient( self::LOCK, 1, 30 );
			$snapshot = self::build( $total );
			set_transient( self::TRANSIENT, $snapshot, HOUR_IN_SECONDS );
			delete_transient( self::LOCK );

			return self::$snapshot = $snapshot;
		}

		/**
		 * Read the latest votes and resolve everything the panel needs.
		 *
		 * About ten queries however large the vote tables are: one indexed read
		 * of the newest rows, then batched cache priming for posts, comments,
		 * thumbnails and users.
		 *
		 * @param int $total Vote counter this snapshot belongs to.
		 * @return array<string, mixed>
		 */
		protected static function build( $total ) {
			$raw = class_exists( 'WP_Ulike_Pulse_Query' ) ? (array) WP_Ulike_Pulse_Query::recent_engagements( self::SCAN_ROWS ) : array();

			self::prime_caches( $raw );

			$rows    = array();
			$groups  = array();
			$index   = array();
			$targets = array();
			$users   = array();

			foreach ( $raw as $position => $row ) {
				$type = (string) $row['item_type'];
				$id   = (int) $row['item_id'];
				$kind = (string) $row['engagement_kind'];
				$key  = (string) $row['engagement_key'];
				$uid  = is_numeric( $row['user_id'] ) && (int) $row['user_id'] > 0 ? (int) $row['user_id'] : 0;
				$ts   = (int) mysql2date( 'U', (string) $row['date_time'] );

				$group_key = $type . '|' . $id . '|' . $kind . '|' . ( 'vote' === $kind ? $key : '' );

				if ( ! isset( $index[ $group_key ] ) ) {
					$target_key = $type . '|' . $id;
					if ( count( $groups ) < self::ITEMS && ! array_key_exists( $target_key, $targets ) ) {
						$targets[ $target_key ] = self::describe_item( $type, $id );
					}

					if ( empty( $targets[ $target_key ] ) || count( $groups ) >= self::ITEMS ) {
						$index[ $group_key ] = -1;
					} else {
						$index[ $group_key ] = count( $groups );
						$groups[]            = array(
							'type'   => $type,
							'id'     => $id,
							'kind'   => $kind,
							'key'    => $key,
							'value'  => isset( $row['value'] ) ? (int) $row['value'] : 0,
							'ts'     => $ts,
							'target' => $targets[ $target_key ],
							'people' => array(),
							'total'  => 0,
						);
					}
				}

				$group = $index[ $group_key ];
				$rows[] = array( $uid, $group, $ts );

				if ( $group < 0 ) {
					continue;
				}

				// Guests share user ID 0, so each guest vote counts as one person.
				$person = $uid ? 'u' . $uid : 'g' . $position;
				if ( ! isset( $groups[ $group ]['people'][ $person ] ) ) {
					$groups[ $group ]['people'][ $person ] = $uid;
				}

				if ( $uid && ! isset( $users[ $uid ] ) ) {
					$users[ $uid ] = self::describe_user( $uid );
				}
			}

			foreach ( $groups as $i => $group ) {
				$groups[ $i ]['people'] = array_values( $group['people'] );

				// Likes and dislikes use the item's total, so 1,000 likes read
				// "and 999 others"; reactions and ratings count the recent votes.
				if ( 'vote' === $group['kind'] && function_exists( 'wp_ulike_get_counter_value' ) ) {
					$status                = 'dislike' === $group['key'] ? 'dislike' : 'like';
					$groups[ $i ]['total'] = (int) wp_ulike_get_counter_value( $group['id'], $group['type'], $status, wp_ulike_setting_repo::isDistinct( $group['type'] ) );
				}
			}

			return array(
				'v'      => self::VERSION,
				'total'  => (int) $total,
				'built'  => time(),
				'rows'   => $rows,
				'groups' => $groups,
				'users'  => array_filter( $users ),
			);
		}

		/**
		 * Load every post, comment, thumbnail and user the rows point to in a
		 * few batched queries instead of one per row.
		 *
		 * @param array<int, array<string, mixed>> $raw Engagement rows.
		 * @return void
		 */
		protected static function prime_caches( $raw ) {
			$posts    = array();
			$comments = array();
			$users    = array();

			foreach ( $raw as $row ) {
				$id = (int) $row['item_id'];
				if ( 'comment' === $row['item_type'] ) {
					$comments[] = $id;
				} elseif ( 'activity' !== $row['item_type'] ) {
					$posts[] = $id;
				}
				if ( is_numeric( $row['user_id'] ) && (int) $row['user_id'] > 0 ) {
					$users[] = (int) $row['user_id'];
				}
			}

			if ( $comments && function_exists( '_prime_comment_caches' ) ) {
				_prime_comment_caches( array_unique( $comments ), false );
				foreach ( array_unique( $comments ) as $comment_id ) {
					$comment = get_comment( $comment_id );
					if ( $comment ) {
						$posts[] = (int) $comment->comment_post_ID;
					}
				}
			}

			if ( $posts && function_exists( '_prime_post_caches' ) ) {
				$posts = array_unique( array_filter( $posts ) );
				_prime_post_caches( $posts, false, true );

				$thumbnails = array();
				foreach ( $posts as $post_id ) {
					$thumbnail = (int) get_post_meta( $post_id, '_thumbnail_id', true );
					if ( $thumbnail ) {
						$thumbnails[] = $thumbnail;
					}
				}
				if ( $thumbnails ) {
					_prime_post_caches( array_unique( $thumbnails ), false, true );
				}
			}

			if ( $users && function_exists( 'cache_users' ) ) {
				cache_users( array_unique( $users ) );
			}
		}

		/**
		 * Title, link and thumbnail for an item, or null when it is gone or not public.
		 *
		 * @param string $item_type post, comment, activity or topic.
		 * @param int    $item_id   Item ID.
		 * @return array{type:string,title:string,url:string,thumb:string}|null
		 */
		protected static function describe_item( $item_type, $item_id ) {
			if ( $item_id < 1 ) {
				return null;
			}

			switch ( $item_type ) {
				case 'comment':
					$comment = get_comment( $item_id );
					if ( ! $comment || '1' !== (string) $comment->comment_approved ) {
						return null;
					}

					return array(
						'type'  => 'comment',
						'title' => self::post_title( (int) $comment->comment_post_ID ),
						'url'   => (string) get_comment_link( $comment ),
						'thumb' => (string) get_the_post_thumbnail_url( (int) $comment->comment_post_ID, 'thumbnail' ),
					);

				case 'activity':
					return array(
						'type'  => 'activity',
						'title' => __( 'an activity update', 'wp-ulike' ),
						'url'   => function_exists( 'bp_activity_get_permalink' ) ? (string) bp_activity_get_permalink( $item_id ) : '',
						'thumb' => '',
					);

				default:
					$post = get_post( $item_id );
					if ( ! $post || 'trash' === $post->post_status ) {
						return null;
					}

					return array(
						'type'  => 'post',
						'title' => self::post_title( $item_id ),
						'url'   => (string) get_permalink( $item_id ),
						'thumb' => (string) get_the_post_thumbnail_url( $item_id, 'thumbnail' ),
					);
			}
		}

		/**
		 * @param int $user_id User ID.
		 * @return array{name:string,avatar:string}|null
		 */
		protected static function describe_user( $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return null;
			}

			return array(
				'name'   => WP_Ulike_Hub::plain_text( $user->display_name ),
				'avatar' => (string) get_avatar_url( $user->ID, array( 'size' => 64 ) ),
			);
		}

		/**
		 * @param int $post_id Post ID.
		 * @return string
		 */
		protected static function post_title( $post_id ) {
			$title = WP_Ulike_Hub::plain_text( get_the_title( $post_id ) );

			return '' !== $title ? $title : __( '(no title)', 'wp-ulike' );
		}
	}
}
