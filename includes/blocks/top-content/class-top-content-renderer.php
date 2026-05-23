<?php
/**
 * Top Content block — data layer and markup.
 *
 * @package WP_ULike
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die( 'No Naughty Business Please !' );
}

if ( ! class_exists( 'WP_Ulike_Top_Content_Renderer' ) ) {

	/**
	 * Fetches and renders popular posts, comments, users, etc.
	 */
	class WP_Ulike_Top_Content_Renderer {

		/** @var string[] */
		private static $content_types = array( 'post', 'comment', 'users', 'activity', 'topic' );

		/** @var string[] */
		private static $preset_periods = array(
			'all',
			'today',
			'yesterday',
			'day_before_yesterday',
			'week',
			'last_week',
			'month',
			'last_month',
			'year',
			'last_year',
		);

		/** @var string[] */
		private static $interval_units = array( 'HOUR', 'DAY', 'WEEK', 'MONTH' );

		/**
		 * Whether bbPress is installed.
		 *
		 * @return bool
		 */
		public static function is_bbpress_active() {
			return class_exists( 'bbPress' );
		}

		/**
		 * Whether BuddyPress is installed.
		 *
		 * @return bool
		 */
		public static function is_buddypress_active() {
			return defined( 'BP_VERSION' );
		}

		/**
		 * Default heading label per content type (Top + type name).
		 *
		 * @param string $content_type Content type slug.
		 * @return string
		 */
		public static function get_top_type_label( $content_type ) {
			$suffixes = array(
				'post'     => esc_html__( 'Posts', 'wp-ulike' ),
				'comment'  => esc_html__( 'Comments', 'wp-ulike' ),
				'users'    => esc_html__( 'User(s)', 'wp-ulike' ),
				'activity' => esc_html__( 'Activities', 'wp-ulike' ),
				'topic'    => esc_html__( 'Topics', 'wp-ulike' ),
			);

			if ( ! isset( $suffixes[ $content_type ] ) ) {
				return esc_html__( 'Top', 'wp-ulike' );
			}

			return sprintf(
				'%s %s',
				esc_html__( 'Top', 'wp-ulike' ),
				$suffixes[ $content_type ]
			);
		}

		/**
		 * @param array  $attributes Block attributes.
		 * @param string $wrapper_class Extra classes.
		 * @return string
		 */
		public static function render( $attributes, $wrapper_class = '' ) {
			$args    = self::sanitize_attributes( $attributes );
			$items   = self::get_items( $args );
			$items   = apply_filters( 'wp_ulike_top_content_block_items', $items, $args );
			$heading = $args['showHeading'] ? self::get_heading( $args ) : '';
			$empty   = self::get_empty_message( $args );

			$classes = array(
				'wp-ulike-top-content',
				'wp-ulike-top-content--' . sanitize_html_class( $args['contentType'] ),
			);

			if ( ! empty( $wrapper_class ) ) {
				$classes[] = $wrapper_class;
			}

			$wrapper_attrs = get_block_wrapper_attributes(
				array(
					'class' => implode( ' ', array_filter( $classes ) ),
				)
			);

			ob_start();
			?>
			<section <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( $heading ) : ?>
					<h3 class="wp-ulike-top-content__heading"><?php echo esc_html( $heading ); ?></h3>
				<?php endif; ?>
				<?php if ( empty( $items ) ) : ?>
					<p class="wp-ulike-top-content__empty" role="status"><?php echo esc_html( $empty ); ?></p>
				<?php else : ?>
					<ol class="wp-ulike-top-content__list" start="1">
						<?php foreach ( $items as $index => $item ) : ?>
							<?php echo self::render_item( $item, $index + 1, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</section>
			<?php
			return ob_get_clean();
		}

		/**
		 * @param array $attributes Raw attributes.
		 * @return array
		 */
		public static function sanitize_attributes( $attributes ) {
			$content_type = isset( $attributes['contentType'] ) ? sanitize_key( $attributes['contentType'] ) : 'post';
			if ( ! in_array( $content_type, self::$content_types, true ) ) {
				$content_type = 'post';
			}
			if ( 'activity' === $content_type && ! self::is_buddypress_active() ) {
				$content_type = 'post';
			}
			if ( 'topic' === $content_type && ! self::is_bbpress_active() ) {
				$content_type = 'post';
			}

			$allowed_status = array( 'like', 'unlike', 'undislike' );

			$sort_by = isset( $attributes['sortBy'] ) ? sanitize_key( $attributes['sortBy'] ) : 'like';
			if ( ! in_array( $sort_by, $allowed_status, true ) ) {
				$sort_by = 'like';
			}

			$sort_order = isset( $attributes['sortOrder'] ) ? strtoupper( sanitize_key( $attributes['sortOrder'] ) ) : 'DESC';
			if ( ! in_array( $sort_order, array( 'ASC', 'DESC' ), true ) ) {
				$sort_order = 'DESC';
			}

			$period_mode = isset( $attributes['periodMode'] ) ? sanitize_key( $attributes['periodMode'] ) : 'preset';
			if ( ! in_array( $period_mode, array( 'preset', 'interval', 'range' ), true ) ) {
				$period_mode = 'preset';
			}

			$period_preset = isset( $attributes['period'] ) ? sanitize_key( $attributes['period'] ) : 'all';
			if ( ! in_array( $period_preset, self::$preset_periods, true ) ) {
				$period_preset = 'all';
			}

			$interval_unit = isset( $attributes['intervalUnit'] ) ? strtoupper( sanitize_key( $attributes['intervalUnit'] ) ) : 'DAY';
			if ( ! in_array( $interval_unit, self::$interval_units, true ) ) {
				$interval_unit = 'DAY';
			}

			$interval_value = isset( $attributes['intervalValue'] ) ? absint( $attributes['intervalValue'] ) : 30;
			$interval_value   = min( 3650, max( 1, $interval_value ) );

			$date_start = isset( $attributes['dateStart'] ) ? sanitize_text_field( $attributes['dateStart'] ) : '';
			$date_end   = isset( $attributes['dateEnd'] ) ? sanitize_text_field( $attributes['dateEnd'] ) : '';

			$post_types = array();
			if ( ! empty( $attributes['postTypes'] ) && is_array( $attributes['postTypes'] ) ) {
				$post_types = array_values( array_filter( array_map( 'sanitize_key', $attributes['postTypes'] ) ) );
			}

			$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : '';
			if ( $taxonomy && ! taxonomy_exists( $taxonomy ) ) {
				$taxonomy = '';
			}

			$taxonomy_terms = array();
			if ( ! empty( $attributes['taxonomyTerms'] ) && is_array( $attributes['taxonomyTerms'] ) ) {
				$taxonomy_terms = array_values( array_filter( array_map( 'absint', $attributes['taxonomyTerms'] ) ) );
			}

			$limit = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 5;
			$limit = min( 20, max( 1, $limit ) );

			$title_trim = isset( $attributes['titleTrim'] ) ? absint( $attributes['titleTrim'] ) : 12;
			$title_trim = min( 50, max( 3, $title_trim ) );

			$thumb_size = isset( $attributes['thumbnailSize'] ) ? absint( $attributes['thumbnailSize'] ) : 40;
			$thumb_size = min( 96, max( 24, $thumb_size ) );

			$profile_url = isset( $attributes['profileUrl'] ) ? sanitize_key( $attributes['profileUrl'] ) : 'wp';
			if ( ! in_array( $profile_url, array( 'wp', 'bp', 'um' ), true ) ) {
				$profile_url = 'wp';
			}

			$show_engaged = ! empty( $attributes['showEngagedUsers'] ) && 'users' !== $content_type;

			$sanitized = array(
				'contentType'    => $content_type,
				'sortBy'         => $sort_by,
				'sortOrder'      => $sort_order,
				'periodMode'     => $period_mode,
				'periodPreset'   => $period_preset,
				'period'         => self::resolve_period( $period_mode, $period_preset, $interval_value, $interval_unit, $date_start, $date_end ),
				'intervalValue'  => $interval_value,
				'intervalUnit'   => $interval_unit,
				'dateStart'      => $date_start,
				'dateEnd'        => $date_end,
				'postTypes'      => $post_types,
				'taxonomy'       => $taxonomy,
				'taxonomyTerms'  => $taxonomy_terms,
				'limit'          => $limit,
				'showCount'      => ! empty( $attributes['showCount'] ),
				'showThumbnail'  => ! empty( $attributes['showThumbnail'] ),
				'showRank'         => ! isset( $attributes['showRank'] ) || ! empty( $attributes['showRank'] ),
				'showHeading'      => ! isset( $attributes['showHeading'] ) || ! empty( $attributes['showHeading'] ),
				'showEngagedUsers' => $show_engaged,
				'titleTrim'        => $title_trim,
				'thumbnailSize'    => $thumb_size,
				'heading'          => isset( $attributes['heading'] ) ? sanitize_text_field( $attributes['heading'] ) : '',
				'profileUrl'       => $profile_url,
			);

			return apply_filters( 'wp_ulike_top_content_block_attributes', $sanitized, $attributes );
		}

		/**
		 * Build period argument for query helpers.
		 *
		 * @param string $mode preset|interval|range.
		 * @param string $preset Preset key.
		 * @param int    $interval_value Interval amount.
		 * @param string $interval_unit DAY|WEEK|etc.
		 * @param string $date_start Y-m-d.
		 * @param string $date_end Y-m-d.
		 * @return string|array
		 */
		public static function resolve_period( $mode, $preset, $interval_value, $interval_unit, $date_start, $date_end ) {
			if ( 'interval' === $mode ) {
				return array(
					'interval_value' => $interval_value,
					'interval_unit'  => $interval_unit,
				);
			}

			if ( 'range' === $mode && $date_start ) {
				return array(
					'start' => $date_start,
					'end'   => $date_end ? $date_end : $date_start,
				);
			}

			return $preset;
		}

		/**
		 * How many candidates to load before filtering (skipped titles, taxonomy, etc.).
		 *
		 * @param array $args Sanitized args.
		 * @return int
		 */
		private static function get_fetch_limit( $args ) {
			if ( self::needs_taxonomy_filter( $args ) ) {
				return min( 100, $args['limit'] * 5 );
			}

			return $args['limit'] + 10;
		}

		/**
		 * Shared popular-query arguments (status, period, sort order).
		 *
		 * @param array $args Sanitized args.
		 * @return array<string, mixed>
		 */
		private static function get_popular_query_base( $args ) {
			return array(
				'status' => $args['sortBy'],
				'period' => $args['period'],
				'order'  => $args['sortOrder'],
				'offset' => 0,
			);
		}

		/**
		 * Item ID => vote count from the same query used for ranking.
		 *
		 * @param array<string, mixed> $query_args Arguments for wp_ulike_get_popular_items_info().
		 * @return array<int, int>
		 */
		private static function get_popular_counters( $query_args ) {
			if ( ! function_exists( 'wp_ulike_get_popular_items_info' ) ) {
				return array();
			}

			$query_args['offset'] = 0;
			$info                 = wp_ulike_get_popular_items_info( $query_args );

			if ( empty( $info ) ) {
				return array();
			}

			$counters = array();

			foreach ( $info as $row ) {
				$counters[ (int) $row->item_ID ] = (int) $row->counter;
			}

			return $counters;
		}

		/**
		 * Counter for display: prefer ranking-query value, then live lookup.
		 *
		 * @param int                 $item_id      Item ID.
		 * @param string              $type         Button type.
		 * @param array               $args         Sanitized args.
		 * @param array<int, int>     $counter_map  ID => count from popular query.
		 * @param bool                $is_distinct  Distinct setting.
		 * @return int
		 */
		private static function resolve_item_count( $item_id, $type, $args, $counter_map, $is_distinct ) {
			if ( isset( $counter_map[ $item_id ] ) ) {
				return (int) $counter_map[ $item_id ];
			}

			return wp_ulike_get_counter_value( $item_id, $type, $args['sortBy'], $is_distinct, $args['period'] );
		}

		/**
		 * Post title with fallback when empty.
		 *
		 * @param WP_Post $post Post object.
		 * @return string
		 */
		private static function get_post_title_for_item( $post ) {
			$title = trim( (string) get_the_title( $post ) );

			if ( '' !== $title ) {
				return stripslashes( $title );
			}

			return '#' . (int) $post->ID;
		}

		/**
		 * @param array $args Sanitized args.
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_items( $args ) {
			switch ( $args['contentType'] ) {
				case 'comment':
					return self::get_comment_items( $args );
				case 'users':
					return self::get_user_items( $args );
				case 'activity':
					return self::get_activity_items( $args );
				case 'topic':
					return self::get_topic_items( $args );
				default:
					return self::get_post_items( $args );
			}
		}

		/**
		 * @param array $args Args.
		 * @return array<int, array<string, mixed>>
		 */
		private static function get_post_items( $args ) {
			$rel_type    = self::get_post_type_filter( $args );
			$fetch_limit = self::get_fetch_limit( $args );
			$counters    = self::get_popular_counters(
				array_merge(
					self::get_popular_query_base( $args ),
					array(
						'type'     => 'post',
						'rel_type' => $rel_type,
						'limit'    => $fetch_limit,
					)
				)
			);

			if ( empty( $counters ) ) {
				return array();
			}

			$item_ids   = array_keys( $counters );
			$query_args = array(
				'post_type'      => is_array( $rel_type ) ? $rel_type : ( $rel_type ? array( $rel_type ) : get_post_types_by_support( array( 'title', 'editor', 'thumbnail' ) ) ),
				'post_status'    => array( 'publish', 'inherit' ),
				'post__in'       => $item_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => $fetch_limit,
			);

			if ( self::needs_taxonomy_filter( $args ) ) {
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $args['taxonomy'],
						'field'    => 'term_id',
						'terms'    => $args['taxonomyTerms'],
					),
				);
			}

			$posts = get_posts( apply_filters( 'wp_ulike_top_content_posts_query', $query_args, $args ) );

			if ( empty( $posts ) ) {
				return array();
			}

			$is_distinct = wp_ulike_setting_repo::isDistinct( 'post' );
			$items       = array();

			foreach ( $posts as $post ) {
				if ( count( $items ) >= $args['limit'] ) {
					break;
				}

				$post_id = (int) $post->ID;
				$count   = self::resolve_item_count( $post_id, 'post', $args, $counters, $is_distinct );

				if ( $count <= 0 ) {
					continue;
				}

				$display_id = wp_ulike_get_the_id( $post_id );

				$items[] = array(
					'item_id'   => $post_id,
					'title'     => wp_trim_words( self::get_post_title_for_item( $post ), $args['titleTrim'], '…' ),
					'subtitle'  => '',
					'url'       => get_permalink( $display_id ),
					'count'     => $count,
					'thumbnail' => $args['showThumbnail'] ? self::get_post_thumbnail_html( $display_id, $args['thumbnailSize'] ) : '',
				);
			}

			return $items;
		}

		/**
		 * @param array $args Args.
		 * @return array<int, array<string, mixed>>
		 */
		private static function get_comment_items( $args ) {
			$rel_type    = self::get_post_type_filter( $args );
			$fetch_limit = self::get_fetch_limit( $args );
			$counters    = self::get_popular_counters(
				array_merge(
					self::get_popular_query_base( $args ),
					array(
						'type'     => 'comment',
						'rel_type' => $rel_type,
						'limit'    => $fetch_limit,
					)
				)
			);

			if ( empty( $counters ) ) {
				return array();
			}

			$comments = get_comments(
				apply_filters(
					'wp_ulike_top_content_comments_query',
					array(
						'comment__in' => array_keys( $counters ),
						'orderby'     => 'comment__in',
						'number'      => $fetch_limit,
						'post_type'   => is_array( $rel_type ) ? $rel_type : ( $rel_type ? $rel_type : '' ),
					),
					$args
				)
			);

			if ( empty( $comments ) ) {
				return array();
			}

			$allowed_post_ids = self::needs_taxonomy_filter( $args ) ? self::get_taxonomy_post_ids( $args ) : null;
			$is_distinct      = wp_ulike_setting_repo::isDistinct( 'comment' );
			$items            = array();

			foreach ( $comments as $comment ) {
				if ( count( $items ) >= $args['limit'] ) {
					break;
				}

				if ( null !== $allowed_post_ids && ! in_array( (int) $comment->comment_post_ID, $allowed_post_ids, true ) ) {
					continue;
				}

				$comment_id = (int) $comment->comment_ID;
				$count      = self::resolve_item_count( $comment_id, 'comment', $args, $counters, $is_distinct );

				if ( $count <= 0 ) {
					continue;
				}

				$items[] = array(
					'item_id'   => $comment_id,
					'title'     => wp_trim_words( get_the_title( $comment->comment_post_ID ), $args['titleTrim'], '…' ),
					'subtitle'  => sprintf(
						'%s %s',
						esc_html( stripslashes( $comment->comment_author ) ),
						esc_html__( 'on', 'wp-ulike' )
					),
					'url'       => get_comment_link( $comment_id ),
					'count'     => $count,
					'thumbnail' => $args['showThumbnail'] ? get_avatar( $comment->comment_author_email, $args['thumbnailSize'], '', '', array( 'class' => 'wp-ulike-top-content__avatar' ) ) : '',
				);
			}

			return $items;
		}

		/**
		 * @param array $args Args.
		 * @return array<int, array<string, mixed>>
		 */
		private static function get_user_items( $args ) {
			if ( ! function_exists( 'wp_ulike_get_best_likers_info' ) ) {
				return array();
			}

			// API expects an array of statuses; a string breaks the query builder.
			$status = array( $args['sortBy'] );
			$likers = wp_ulike_get_best_likers_info( $args['limit'], $args['period'], 0, $status );

			if ( empty( $likers ) || ! is_array( $likers ) ) {
				return array();
			}

			$items = array();

			foreach ( $likers as $liker ) {
				$user_id  = absint( $liker->user_id );
				$userdata = get_userdata( $user_id );

				if ( empty( $userdata ) ) {
					continue;
				}

				$count_key = $args['sortBy'] . 'Count';
				if ( isset( $liker->$count_key ) ) {
					$count = absint( $liker->$count_key );
				} else {
					$count = isset( $liker->SumUser ) ? absint( $liker->SumUser ) : 0;
				}

				if ( $count <= 0 ) {
					continue;
				}

				$items[] = array(
					'title'     => $userdata->display_name,
					'subtitle'  => '',
					'url'       => self::get_user_profile_url( $user_id, $args['profileUrl'] ),
					'count'     => $count,
					'thumbnail' => $args['showThumbnail'] ? get_avatar( $userdata->user_email, $args['thumbnailSize'], '', $userdata->display_name, array( 'class' => 'wp-ulike-top-content__avatar' ) ) : '',
				);
			}

			return $items;
		}

		/**
		 * @param array $args Args.
		 * @return array<int, array<string, mixed>>
		 */
		private static function get_activity_items( $args ) {
			if ( ! self::is_buddypress_active() ) {
				return array();
			}

			$fetch_limit = self::get_fetch_limit( $args );
			$counters    = self::get_popular_counters(
				array_merge(
					self::get_popular_query_base( $args ),
					array(
						'type'     => 'activity',
						'rel_type' => '',
						'limit'    => $fetch_limit,
					)
				)
			);

			if ( empty( $counters ) ) {
				return array();
			}

			$item_ids = array_keys( $counters );

			global $wpdb;
			$bp_prefix    = is_multisite() ? 'base_prefix' : 'prefix';
			$table_name   = esc_sql( $wpdb->$bp_prefix . 'bp_activity' );
			$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );
			$activities   = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$table_name}` WHERE `id` IN ({$placeholders}) ORDER BY FIELD(`id`, {$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					array_merge( $item_ids, $item_ids )
				)
			);

			if ( empty( $activities ) ) {
				return array();
			}

			$is_distinct = wp_ulike_setting_repo::isDistinct( 'activity' );
			$items       = array();

			foreach ( $activities as $activity ) {
				if ( count( $items ) >= $args['limit'] ) {
					break;
				}

				$activity_action = ! empty( $activity->content ) ? $activity->content : $activity->action;
				if ( empty( $activity_action ) ) {
					continue;
				}

				$activity_id = (int) $activity->id;
				$count       = self::resolve_item_count( $activity_id, 'activity', $args, $counters, $is_distinct );

				if ( $count <= 0 ) {
					continue;
				}

				$items[] = array(
					'item_id'   => $activity_id,
					'title'     => wp_trim_words( wp_strip_all_tags( $activity_action ), $args['titleTrim'], '…' ),
					'subtitle'  => '',
					'url'       => function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity_id ) : '',
					'count'     => $count,
					'thumbnail' => '',
				);
			}

			return $items;
		}

		/**
		 * @param array $args Args.
		 * @return array<int, array<string, mixed>>
		 */
		private static function get_topic_items( $args ) {
			if ( ! self::is_bbpress_active() ) {
				return array();
			}

			$fetch_limit = self::get_fetch_limit( $args );
			$counters    = self::get_popular_counters(
				array_merge(
					self::get_popular_query_base( $args ),
					array(
						'type'     => 'topic',
						'rel_type' => array( 'topic', 'reply' ),
						'limit'    => $fetch_limit,
					)
				)
			);

			if ( empty( $counters ) ) {
				return array();
			}

			$posts = get_posts(
				array(
					'post_type'      => array( 'topic', 'reply' ),
					'post__in'       => array_keys( $counters ),
					'orderby'        => 'post__in',
					'posts_per_page' => $fetch_limit,
					'post_status'    => array( 'publish', 'inherit' ),
				)
			);

			if ( empty( $posts ) ) {
				return array();
			}

			$is_distinct = wp_ulike_setting_repo::isDistinct( 'topic' );
			$items       = array();

			foreach ( $posts as $post ) {
				if ( count( $items ) >= $args['limit'] ) {
					break;
				}

				$post_type = get_post_type( $post->ID );
				if ( ! in_array( $post_type, array( 'topic', 'reply' ), true ) ) {
					continue;
				}

				$post_id = (int) $post->ID;
				$count   = self::resolve_item_count( $post_id, 'topic', $args, $counters, $is_distinct );

				if ( $count <= 0 ) {
					continue;
				}

				if ( 'topic' === $post_type ) {
					$post_title = function_exists( 'bbp_get_topic_title' )
						? bbp_get_topic_title( $post_id )
						: $post->post_title;
					$permalink  = function_exists( 'bbp_get_topic_permalink' )
						? bbp_get_topic_permalink( $post_id )
						: get_permalink( $post_id );
				} else {
					$post_title = function_exists( 'bbp_get_reply_title' )
						? bbp_get_reply_title( $post_id )
						: $post->post_title;
					$permalink  = function_exists( 'bbp_get_reply_url' )
						? bbp_get_reply_url( $post_id )
						: get_permalink( $post_id );
				}

				$items[] = array(
					'item_id'   => $post_id,
					'title'     => wp_trim_words( $post_title, $args['titleTrim'], '…' ),
					'subtitle'  => '',
					'url'       => $permalink,
					'count'     => $count,
					'thumbnail' => '',
				);
			}

			return $items;
		}

		/**
		 * Post type filter for popular queries.
		 *
		 * @param array $args Args.
		 * @return string|array
		 */
		private static function get_post_type_filter( $args ) {
			if ( ! empty( $args['postTypes'] ) ) {
				return $args['postTypes'];
			}

			return '';
		}

		/**
		 * @param array $args Args.
		 * @return bool
		 */
		private static function needs_taxonomy_filter( $args ) {
			return ! empty( $args['taxonomy'] ) && ! empty( $args['taxonomyTerms'] ) && in_array( $args['contentType'], array( 'post', 'comment' ), true );
		}

		/**
		 * @param array $args Args.
		 * @return int[]
		 */
		private static function get_taxonomy_post_ids( $args ) {
			$posts = get_posts(
				array(
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'post_type'      => self::get_post_type_filter( $args ) ? self::get_post_type_filter( $args ) : 'any',
					'post_status'    => 'publish',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => $args['taxonomy'],
							'field'    => 'term_id',
							'terms'    => $args['taxonomyTerms'],
						),
					),
				)
			);

			return array_map( 'absint', $posts );
		}

		/**
		 * @param array<string, mixed> $item Item.
		 * @param int                  $rank Rank.
		 * @param array                $args Args.
		 * @return string
		 */
		private static function render_item( $item, $rank, $args ) {
			$count_label = self::get_status_label( $args['sortBy'] );
			$aside_html  = '';

			if ( $args['showCount'] && isset( $item['count'] ) ) {
				$aside_html = sprintf(
					'<div class="wp-ulike-top-content__aside" aria-label="%1$s"><span class="wp-ulike-top-content__count-value">%2$s</span><span class="wp-ulike-top-content__count-label">%3$s</span></div>',
					esc_attr( $count_label ),
					esc_html( wp_ulike_format_number( $item['count'], $args['sortBy'] ) ),
					esc_html( $count_label )
				);
			}

			$rank_html = $args['showRank']
				? sprintf( '<span class="wp-ulike-top-content__rank" aria-hidden="true">%d</span>', (int) $rank )
				: '';

			$subtitle_html = ! empty( $item['subtitle'] )
				? sprintf( '<span class="wp-ulike-top-content__subtitle">%s</span>', wp_kses_post( $item['subtitle'] ) )
				: '';

			$media_html = ! empty( $item['thumbnail'] ) ? '<span class="wp-ulike-top-content__media">' . $item['thumbnail'] . '</span>' : '';

			$url = ! empty( $item['url'] ) ? $item['url'] : '';

			$title_inner = sprintf(
				'<span class="wp-ulike-top-content__title">%s</span>%s',
				esc_html( $item['title'] ),
				$subtitle_html
			);

			$title_html = $url
				? sprintf( '<a class="wp-ulike-top-content__link" href="%s">%s</a>', esc_url( $url ), $title_inner )
				: sprintf( '<span class="wp-ulike-top-content__link">%s</span>', $title_inner );

			$extras    = array();
			$likers_html = self::render_item_likers( $item, $args );
			if ( $likers_html ) {
				$extras[] = $likers_html;
			}

			$extras_html = ! empty( $extras )
				? '<div class="wp-ulike-top-content__extras">' . implode( '', $extras ) . '</div>'
				: '';

			return sprintf(
				'<li class="wp-ulike-top-content__item">%s%s<div class="wp-ulike-top-content__main">%s%s</div>%s</li>',
				$rank_html,
				$media_html,
				$title_html,
				$extras_html,
				$aside_html
			);
		}

		/**
		 * Likers table config per content type.
		 *
		 * @param string $content_type Content type.
		 * @return array{0: string, 1: string, 2: string}|null
		 */
		private static function get_likers_table_config( $content_type ) {
			$map = array(
				'post'     => array( 'ulike', 'post_id', 'post' ),
				'comment'  => array( 'ulike_comments', 'comment_id', 'comment' ),
				'topic'    => array( 'ulike_forums', 'topic_id', 'topic' ),
				'activity' => array( 'ulike_activities', 'activity_id', 'activity' ),
			);

			return isset( $map[ $content_type ] ) ? $map[ $content_type ] : null;
		}

		/**
		 * @param array $item Item.
		 * @param array $args Args.
		 * @return string
		 */
		private static function render_item_likers( $item, $args ) {
			if ( empty( $args['showEngagedUsers'] ) || empty( $item['item_id'] ) ) {
				return '';
			}

			$config = self::get_likers_table_config( $args['contentType'] );
			if ( ! $config || ! function_exists( 'wp_ulike_get_likers_list_per_post' ) ) {
				return '';
			}

			$max_visible = 4;
			$user_ids  = wp_ulike_get_likers_list_per_post( $config[0], $config[1], (int) $item['item_id'], $max_visible + 1 );

			if ( empty( $user_ids ) ) {
				return '';
			}

			$has_more = count( $user_ids ) > $max_visible;
			if ( $has_more ) {
				$user_ids = array_slice( $user_ids, 0, $max_visible );
			}

			$avatars = '';
			foreach ( $user_ids as $user_id ) {
				$user = get_userdata( absint( $user_id ) );
				if ( ! $user ) {
					continue;
				}
				$avatars .= get_avatar(
					$user->user_email,
					22,
					'',
					$user->display_name,
					array( 'class' => 'wp-ulike-top-content__liker-avatar' )
				);
			}

			if ( empty( $avatars ) ) {
				return '';
			}

			$more_html = $has_more
				? '<span class="wp-ulike-top-content__likers-more" aria-hidden="true"><span class="wp-ulike-top-content__likers-more-dots" aria-hidden="true">...</span></span>'
				: '';

			return sprintf(
				'<span class="wp-ulike-top-content__likers"><span class="wp-ulike-top-content__likers-avatars">%s%s</span></span>',
				$avatars,
				$more_html
			);
		}

		/**
		 * @param int    $post_id Post ID.
		 * @param int    $size Size.
		 * @return string
		 */
		private static function get_post_thumbnail_html( $post_id, $size ) {
			$thumbnail = get_the_post_thumbnail(
				$post_id,
				array( $size, $size ),
				array(
					'class' => 'wp-ulike-top-content__thumb wp_ulike_thumbnail',
					'alt'   => '',
				)
			);

			if ( $thumbnail ) {
				return $thumbnail;
			}

			return sprintf(
				'<img src="%s" class="wp-ulike-top-content__thumb wp_ulike_thumbnail" alt="" width="%d" height="%d" loading="lazy" decoding="async" />',
				esc_url( WP_ULIKE_ASSETS_URL . '/img/no-thumbnail.png' ),
				(int) $size,
				(int) $size
			);
		}

		/**
		 * @param int    $user_id User ID.
		 * @param string $profile_url Profile type.
		 * @return string
		 */
		private static function get_user_profile_url( $user_id, $profile_url ) {
			if ( 'bp' === $profile_url && function_exists( 'bp_members_get_user_url' ) ) {
				return bp_members_get_user_url( $user_id );
			}

			if ( 'um' === $profile_url && function_exists( 'um_fetch_user' ) && function_exists( 'um_user_profile_url' ) ) {
				um_fetch_user( $user_id );
				return um_user_profile_url();
			}

			return get_author_posts_url( $user_id );
		}

		/**
		 * @param string $status Vote status.
		 * @return string
		 */
		private static function get_status_label( $status ) {
			$labels = array(
				'like'      => esc_html__( 'Like', 'wp-ulike' ),
				'dislike'   => esc_html__( 'Dislike', 'wp-ulike' ),
				'unlike'    => esc_html__( 'Unlike', 'wp-ulike' ),
				'undislike' => esc_html__( 'Undislike', 'wp-ulike' ),
			);

			return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['like'];
		}

		/**
		 * @param array $args Args.
		 * @return string
		 */
		private static function get_heading( $args ) {
			if ( ! empty( $args['heading'] ) ) {
				return $args['heading'];
			}

			$heading = self::get_top_type_label( $args['contentType'] );

			if ( 'like' !== $args['sortBy'] ) {
				$heading .= ' (' . self::get_status_label( $args['sortBy'] ) . ')';
			}

			return $heading;
		}

		/**
		 * @param array $args Args.
		 * @return string
		 */
		private static function get_empty_message( $args ) {
			$period_label = self::get_period_label( $args );

			return sprintf(
				'%s "%s" %s',
				esc_html__( 'No results were found in', 'wp-ulike' ),
				$period_label,
				esc_html__( 'period', 'wp-ulike' )
			);
		}

		/**
		 * Human-readable period for empty state.
		 *
		 * @param array $args Args.
		 * @return string
		 */
		private static function get_period_label( $args ) {
			if ( 'interval' === $args['periodMode'] ) {
				if ( 'DAY' === $args['intervalUnit'] ) {
					return self::format_last_days_label( $args['intervalValue'] );
				}

				$unit_labels = array(
					'HOUR'  => esc_html__( 'hour', 'wp-ulike' ),
					'DAY'   => esc_html__( 'day', 'wp-ulike' ),
					'WEEK'  => esc_html__( 'week', 'wp-ulike' ),
					'MONTH' => esc_html__( 'month', 'wp-ulike' ),
				);
				$unit = isset( $unit_labels[ $args['intervalUnit'] ] ) ? $unit_labels[ $args['intervalUnit'] ] : $unit_labels['DAY'];

				return trim( $args['intervalValue'] . ' ' . $unit );
			}

			if ( 'range' === $args['periodMode'] && $args['dateStart'] ) {
				return $args['dateStart'] . ( $args['dateEnd'] && $args['dateEnd'] !== $args['dateStart'] ? ' – ' . $args['dateEnd'] : '' );
			}

			$presets = self::get_period_presets();
			foreach ( $presets as $preset ) {
				if ( $preset['value'] === $args['periodPreset'] ) {
					return $preset['label'];
				}
			}

			return esc_html__( 'All The Times', 'wp-ulike' );
		}

		/**
		 * "Last {{days}} Days" with a numeric placeholder (editor + empty state).
		 *
		 * @param int $days Number of days.
		 * @return string
		 */
		public static function format_last_days_label( $days ) {
			$days = max( 1, absint( $days ) );

			return str_replace(
				'{{days}}',
				(string) $days,
				esc_html__( 'Last {{days}} Days', 'wp-ulike' )
			);
		}

		/**
		 * Preset period options (widget + stats aligned).
		 *
		 * @return array<int, array{value: string, label: string}>
		 */
		public static function get_period_presets() {
			return array(
				array( 'value' => 'all', 'label' => esc_html__( 'All The Times', 'wp-ulike' ) ),
				array( 'value' => 'year', 'label' => esc_html__( 'This Year', 'wp-ulike' ) ),
				array( 'value' => 'last_year', 'label' => esc_html__( 'Last Year', 'wp-ulike' ) ),
				array( 'value' => 'month', 'label' => esc_html__( 'Month', 'wp-ulike' ) ),
				array( 'value' => 'last_month', 'label' => esc_html__( 'Last Month', 'wp-ulike' ) ),
				array( 'value' => 'week', 'label' => esc_html__( 'This Week', 'wp-ulike' ) ),
				array( 'value' => 'last_week', 'label' => esc_html__( 'Last Week', 'wp-ulike' ) ),
				array( 'value' => 'today', 'label' => esc_html__( 'Today', 'wp-ulike' ) ),
				array( 'value' => 'yesterday', 'label' => esc_html__( 'Yesterday', 'wp-ulike' ) ),
			);
		}

		/**
		 * Interval unit options.
		 *
		 * @return array<int, array{value: string, label: string}>
		 */
		public static function get_interval_units() {
			return array(
				array( 'value' => 'DAY', 'label' => esc_html__( 'day', 'wp-ulike' ) ),
				array( 'value' => 'WEEK', 'label' => esc_html__( 'week', 'wp-ulike' ) ),
				array( 'value' => 'MONTH', 'label' => esc_html__( 'month', 'wp-ulike' ) ),
				array( 'value' => 'HOUR', 'label' => esc_html__( 'hour', 'wp-ulike' ) ),
			);
		}

		/**
		 * Post types for editor filters.
		 *
		 * @return array<int, array{value: string, label: string}>
		 */
		public static function get_post_type_options() {
			$types   = get_post_types( array( 'public' => true ), 'objects' );
			$options = array();

			foreach ( $types as $type ) {
				if ( in_array( $type->name, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
					continue;
				}
				$options[] = array(
					'value' => $type->name,
					'label' => $type->labels->singular_name,
				);
			}

			return $options;
		}

		/**
		 * Editor configuration (labels from existing plugin strings).
		 *
		 * @return array
		 */
		public static function get_editor_config() {
			$content_types = array(
				array( 'value' => 'post', 'label' => self::get_top_type_label( 'post' ) ),
				array( 'value' => 'comment', 'label' => self::get_top_type_label( 'comment' ) ),
				array( 'value' => 'users', 'label' => self::get_top_type_label( 'users' ) ),
			);

			if ( self::is_buddypress_active() ) {
				$content_types[] = array( 'value' => 'activity', 'label' => self::get_top_type_label( 'activity' ) );
			}
			if ( self::is_bbpress_active() ) {
				$content_types[] = array( 'value' => 'topic', 'label' => self::get_top_type_label( 'topic' ) );
			}

			$sort_options = apply_filters(
				'wp_ulike_top_content_block_sort_options',
				array(
					array( 'value' => 'like', 'label' => esc_html__( 'Like', 'wp-ulike' ) ),
					array( 'value' => 'unlike', 'label' => esc_html__( 'Unlike', 'wp-ulike' ) ),
					array( 'value' => 'undislike', 'label' => esc_html__( 'Undislike', 'wp-ulike' ) ),
				)
			);

			$profile_urls = array(
				array( 'value' => 'wp', 'label' => esc_html__( 'Profile', 'wp-ulike' ) ),
			);
			if ( function_exists( 'bp_members_get_user_url' ) ) {
				$profile_urls[] = array( 'value' => 'bp', 'label' => esc_html__( 'BuddyPress', 'wp-ulike' ) );
			}
			if ( function_exists( 'um_user_profile_url' ) ) {
				$profile_urls[] = array( 'value' => 'um', 'label' => esc_html__( 'UltimateMember', 'wp-ulike' ) );
			}

			return array(
				'hasBuddyPress'  => self::is_buddypress_active(),
				'hasBbPress'     => self::is_bbpress_active(),
				'contentTypes'   => $content_types,
				'sortOptions'    => $sort_options,
				'sortOrders'     => array(
					array( 'value' => 'DESC', 'label' => esc_html__( 'Descending', 'wp-ulike' ) ),
					array( 'value' => 'ASC', 'label' => esc_html__( 'Ascending', 'wp-ulike' ) ),
				),
				'periodPresets'  => self::get_period_presets(),
				'intervalUnits'  => self::get_interval_units(),
				'postTypes'      => self::get_post_type_options(),
				'profileUrls'    => $profile_urls,
				'i18n'           => array(
					'contentPanel'         => esc_html__( 'Content Types', 'wp-ulike' ),
					'contentType'          => esc_html__( 'Type:', 'wp-ulike' ),
					'statusFilter'         => esc_html__( 'Status Filter', 'wp-ulike' ),
					'sortOrder'            => esc_html__( 'Likers List Order', 'wp-ulike' ),
					'filtersPanel'         => esc_html__( 'Post Types', 'wp-ulike' ),
					'postTypes'            => esc_html__( 'Post Types', 'wp-ulike' ),
					'selectPostTypes'      => esc_html__( 'Select post types', 'wp-ulike' ),
					'taxonomy'             => esc_html__( 'Post Types', 'wp-ulike' ),
					'taxonomyTerms'        => esc_html__( 'Select options...', 'wp-ulike' ),
					'settingsPanel'        => esc_html__( 'Settings', 'wp-ulike' ),
					'numberOf'             => esc_html__( 'Number of items to show:', 'wp-ulike' ),
					'titleTrim'            => esc_html__( 'Title Trim (Length):', 'wp-ulike' ),
					'profileUrl'           => esc_html__( 'Profile URL:', 'wp-ulike' ),
					'showCount'            => esc_html__( 'Activate Like Counter', 'wp-ulike' ),
					'showThumb'            => esc_html__( 'Activate Thumbnail/Avatar', 'wp-ulike' ),
					'thumbSize'            => esc_html__( 'Thumbnail/Avatar size:', 'wp-ulike' ),
					'showRank'             => esc_html__( 'Rank number', 'wp-ulike' ),
					'showHeading'          => esc_html__( 'Title:', 'wp-ulike' ),
					'customTitle'          => esc_html__( 'Title:', 'wp-ulike' ),
					'showEngagedUsers'     => esc_html__( 'Engaged Users', 'wp-ulike' ),
					'period'               => esc_html__( 'Period:', 'wp-ulike' ),
					'periodInterval'       => esc_html__( 'Last {{days}} Days', 'wp-ulike' ),
					'periodRange'          => esc_html__( 'Date Range', 'wp-ulike' ),
					'periodUnit'           => esc_html__( 'day', 'wp-ulike' ),
					'dateStart'            => esc_html__( 'Date Range', 'wp-ulike' ),
					'dateEnd'              => esc_html__( 'Date Range', 'wp-ulike' ),
					'loading'              => esc_html__( 'Loading...', 'wp-ulike' ),
					'noData'               => esc_html__( 'No data to display', 'wp-ulike' ),
					'allPostTypes'         => esc_html__( 'Select...', 'wp-ulike' ),
				),
			);
		}
	}
}
