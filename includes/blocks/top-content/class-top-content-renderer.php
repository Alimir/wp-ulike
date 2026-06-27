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
		 * Whether WP ULike Pro is active.
		 *
		 * @return bool
		 */
		public static function is_pro_active() {
			return defined( 'WP_ULIKE_PRO_VERSION' );
		}

		/**
		 * Normalize block sortBy attribute (string legacy value or array).
		 *
		 * @param mixed $raw Raw attribute value.
		 * @return string[]
		 */
		private static function sanitize_sort_by( $raw ) {
			$allowed = self::get_allowed_statuses();

			if ( is_string( $raw ) && '' !== $raw ) {
				$raw = array( $raw );
			}

			if ( ! is_array( $raw ) ) {
				$raw = array( 'like' );
			}

			$sort_by = array();

			foreach ( $raw as $status ) {
				$status = sanitize_key( $status );
				if ( in_array( $status, $allowed, true ) && ! in_array( $status, $sort_by, true ) ) {
					$sort_by[] = $status;
				}
			}

			if ( empty( $sort_by ) ) {
				$sort_by[] = 'like';
			}

			return $sort_by;
		}

		/**
		 * Vote types for this block: Like (free) and Dislike (Pro).
		 *
		 * @return string[]
		 */
		private static function get_allowed_statuses() {
			$statuses = array( 'like' );

			if ( self::is_pro_active() ) {
				$statuses[] = 'dislike';
			}

			return $statuses;
		}

		/**
		 * Status filter options for the block editor.
		 *
		 * @return array<int, array{value: string, label: string}>
		 */
		private static function get_sort_options() {
			$options = array(
				array( 'value' => 'like', 'label' => esc_html__( 'Like', 'wp-ulike' ) ),
			);

			if ( self::is_pro_active() ) {
				$options[] = array( 'value' => 'dislike', 'label' => esc_html__( 'Dislike', 'wp-ulike' ) );
			}

			return apply_filters( 'wp_ulike_top_content_block_sort_options', $options );
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
			$heading        = $args['showHeading'] ? self::get_heading( $args ) : '';
			$empty          = self::get_empty_message( $args );
			$heading_id     = $heading ? 'wp-ulike-top-content-heading-' . wp_unique_id() : '';
			$section_a11y   = $heading_id
				? 'aria-labelledby="' . esc_attr( $heading_id ) . '"'
				: 'aria-label="' . esc_attr( self::get_top_type_label( $args['contentType'] ) ) . '"';

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
			<section <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo $section_a11y; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( $heading ) : ?>
					<h3 id="<?php echo esc_attr( $heading_id ); ?>" class="wp-ulike-top-content__heading"><?php echo esc_html( $heading ); ?></h3>
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

			$sort_by = self::sanitize_sort_by( isset( $attributes['sortBy'] ) ? $attributes['sortBy'] : array( 'like' ) );

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

			$interval_unit   = isset( $attributes['intervalUnit'] ) ? strtoupper( sanitize_key( $attributes['intervalUnit'] ) ) : 'DAY';
			$allowed_intervals = array_column( self::get_interval_units(), 'value' );
			if ( ! in_array( $interval_unit, $allowed_intervals, true ) ) {
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
			// Multiple statuses: rank by combined votes (likes + dislikes) for that item, then View By ASC/DESC.
			return array(
				'status' => $args['sortBy'],
				'period' => $args['period'],
				'order'  => $args['sortOrder'],
				'offset' => 0,
			);
		}

		/**
		 * @param string              $type Content type slug for wp_ulike_get_popular_items_info.
		 * @param string|array        $rel_type Related type filter.
		 * @param array               $args Sanitized block args.
		 * @param int|null            $fetch_limit Optional override for candidate pool size.
		 * @return array<string, mixed>
		 */
		private static function build_popular_query( $type, $rel_type, $args, $fetch_limit = null ) {
			// Same shape as wp_ulike_pro_get_posts_query() / Stats V2 top_posts() defaults.
			$query_args = array_merge(
				self::get_popular_query_base( $args ),
				array(
					'type'       => $type,
					'rel_type'   => $rel_type,
					'is_popular' => true,
					'limit'      => null !== $fetch_limit ? (int) $fetch_limit : self::get_fetch_limit( $args ),
				)
			);

			/**
			 * Filter popular-query arguments before wp_ulike_get_popular_items_info().
			 *
			 * @param array<string, mixed> $query_args Arguments for the popular items query.
			 * @param string               $type       Content type slug (post, comment, etc.).
			 * @param array                $args       Sanitized block attributes.
			 */
			return apply_filters( 'wp_ulike_top_content_popular_query_args', $query_args, $type, $args );
		}

		/**
		 * Item ID => vote count from wp_ulike_get_popular_items_info().
		 *
		 * @param array<string, mixed> $query_args Arguments for wp_ulike_get_popular_items_info().
		 * @return array<int, int>
		 */
		private static function query_popular_counters( $query_args ) {
			if ( ! function_exists( 'wp_ulike_get_popular_items_info' ) ) {
				return array();
			}

			$query_args = self::prepare_popular_query_args( $query_args );
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
		 * Ranking map for one or more statuses (multi-status sums per item; core meta query uses MAX otherwise).
		 *
		 * @param array<string, mixed> $query_args Query arguments.
		 * @return array<int, int>
		 */
		private static function get_popular_counters( $query_args ) {
			$statuses = isset( $query_args['status'] ) ? $query_args['status'] : 'like';
			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			if ( count( $statuses ) <= 1 ) {
				$query_args['status'] = $statuses[0];
				return self::query_popular_counters( $query_args );
			}

			$merged     = array();
			$pool_limit = max( (int) ( $query_args['limit'] ?? 10 ) * 3, 50 );

			foreach ( $statuses as $status ) {
				$single_args           = $query_args;
				$single_args['status'] = $status;
				$single_args['limit']  = $pool_limit;

				foreach ( self::query_popular_counters( $single_args ) as $item_id => $count ) {
					if ( ! isset( $merged[ $item_id ] ) ) {
						$merged[ $item_id ] = 0;
					}
					$merged[ $item_id ] += $count;
				}
			}

			$order = strtoupper( $query_args['order'] ?? 'DESC' );
			if ( 'ASC' === $order ) {
				asort( $merged, SORT_NUMERIC );
			} else {
				arsort( $merged, SORT_NUMERIC );
			}

			$limit = (int) ( $query_args['limit'] ?? 0 );
			if ( $limit > 0 ) {
				$merged = array_slice( $merged, 0, $limit, true );
			}

			return $merged;
		}

		/**
		 * Date range for wp_ulike_get_counter_value() when rendering list counters.
		 *
		 * Matches wp-ulike-pro frontend/templates (post.php, Elementor widgets): no period arg for
		 * all-time so core reads meta counters — same as the public like button.
		 *
		 * WP ULike Pro Stats (class-stats-v2.php) passes $settings['period'] including "all", which
		 * forces a log recount in core; that is intended for period-filtered dashboards, not for
		 * matching button totals on the frontend.
		 *
		 * Pro metabox "counter quantity" is added automatically via the wp_ulike_counter_value filter.
		 *
		 * @param string|array $period Resolved block period.
		 * @return string|array|null
		 */
		private static function get_counter_date_range_for_display( $period ) {
			if ( empty( $period ) || 'all' === $period ) {
				return null;
			}

			return $period;
		}

		/**
		 * Single status count (like / dislike) for one list item.
		 *
		 * Same API as wp-ulike-pro stats-v2 top_posts() and public templates; see
		 * wp_ulike_pro_update_counter_value for Pro quantity adjustments on post/comment.
		 *
		 * @param int          $item_id     Item ID.
		 * @param string       $type        Counter type slug.
		 * @param string       $status      like|dislike.
		 * @param bool         $is_distinct Distinct setting.
		 * @param string|array $period      Resolved block period.
		 * @return int
		 */
		private static function get_item_status_count( $item_id, $type, $status, $is_distinct, $period ) {
			return (int) wp_ulike_get_counter_value(
				$item_id,
				$type,
				$status,
				$is_distinct,
				self::get_counter_date_range_for_display( $period )
			);
		}

		/**
		 * wp_ulike_get_popular_items_info() with period "all" uses the meta table (fast path).
		 * Dislike rankings are often missing there; a date range forces the vote-log query instead.
		 *
		 * @param array<string, mixed> $query_args Query arguments.
		 * @return array<string, mixed>
		 */
		private static function prepare_popular_query_args( $query_args ) {
			$status = isset( $query_args['status'] ) ? $query_args['status'] : 'like';
			if ( is_array( $status ) || 'dislike' !== $status ) {
				return $query_args;
			}

			$period = isset( $query_args['period'] ) ? $query_args['period'] : 'all';
			if ( ! empty( $period ) && 'all' !== $period ) {
				return $query_args;
			}

			// Cannot pass "all" — core maps that to empty period_limit and reads ulike_meta, not vote logs.
			$query_args['period'] = array(
				'start' => '1970-01-01',
				'end'   => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ),
			);

			return $query_args;
		}

		/**
		 * Accurate per-status counts + list rules (matches like button via wp_ulike_get_counter_value).
		 *
		 * @param int    $item_id     Item ID.
		 * @param string $type        Counter type slug.
		 * @param array  $args        Sanitized args.
		 * @param bool   $is_distinct Distinct setting.
		 * @return array{count: int, status_counts: array<string, int>}|null Null when item should be skipped.
		 */
		private static function build_item_vote_data( $item_id, $type, $args, $is_distinct ) {
			$item_id = (int) $item_id;
			if ( $item_id <= 0 ) {
				return null;
			}

			$status_counts = array();
			foreach ( $args['sortBy'] as $status ) {
				$status_counts[ $status ] = self::get_item_status_count(
					$item_id,
					$type,
					$status,
					$is_distinct,
					$args['period']
				);
			}

			$total = 0;
			foreach ( $args['sortBy'] as $status ) {
				$total += (int) ( $status_counts[ $status ] ?? 0 );
			}

			if ( $total <= 0 ) {
				return null;
			}

			return array(
				'count'         => $total,
				'status_counts' => $status_counts,
			);
		}

		/**
		 * @param string[] $statuses Status slugs.
		 * @return string
		 */
		private static function get_status_labels_text( $statuses ) {
			$labels = array_map( array( __CLASS__, 'get_status_label' ), $statuses );

			return implode( ', ', $labels );
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
			$counters    = self::get_popular_counters( self::build_popular_query( 'post', $rel_type, $args, $fetch_limit ) );

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

				$post_id   = (int) $post->ID;
				$vote_data = self::build_item_vote_data( $post_id, 'post', $args, $is_distinct );

				if ( null === $vote_data ) {
					continue;
				}

				$display_id = wp_ulike_get_the_id( $post_id );

				$items[] = array(
					'item_id'       => $post_id,
					'title'         => wp_trim_words( self::get_post_title_for_item( $post ), $args['titleTrim'], '…' ),
					'subtitle'      => '',
					'url'           => get_permalink( $display_id ),
					'count'         => $vote_data['count'],
					'status_counts' => $vote_data['status_counts'],
					'thumbnail'     => $args['showThumbnail'] ? self::get_post_thumbnail_html( $display_id, $args['thumbnailSize'] ) : '',
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
			$counters    = self::get_popular_counters( self::build_popular_query( 'comment', $rel_type, $args, $fetch_limit ) );

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
				$vote_data  = self::build_item_vote_data( $comment_id, 'comment', $args, $is_distinct );

				if ( null === $vote_data ) {
					continue;
				}

				$items[] = array(
					'item_id'       => $comment_id,
					'title'         => wp_trim_words( get_the_title( $comment->comment_post_ID ), $args['titleTrim'], '…' ),
					'subtitle'      => sprintf(
						'%s %s',
						esc_html( stripslashes( $comment->comment_author ) ),
						esc_html__( 'on', 'wp-ulike' )
					),
					'url'           => get_comment_link( $comment_id ),
					'count'         => $vote_data['count'],
					'status_counts' => $vote_data['status_counts'],
					'thumbnail'     => $args['showThumbnail'] ? get_avatar( $comment->comment_author_email, $args['thumbnailSize'], '', '', array( 'class' => 'wp-ulike-top-content__avatar' ) ) : '',
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

			$likers = wp_ulike_get_best_likers_info( $args['limit'], $args['period'], 0, $args['sortBy'] );

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

				$status_counts = array();
				$count         = 0;

				foreach ( $args['sortBy'] as $status ) {
					$count_key = $status . 'Count';
					$value     = isset( $liker->$count_key ) ? absint( $liker->$count_key ) : 0;
					if ( $value > 0 ) {
						$status_counts[ $status ] = $value;
						$count                   += $value;
					}
				}

				if ( $count <= 0 && isset( $liker->SumUser ) ) {
					$count = absint( $liker->SumUser );
				}

				if ( $count <= 0 ) {
					continue;
				}

				$items[] = array(
					'item_id'   => $user_id,
					'title'     => $userdata->display_name,
					'status_counts' => $status_counts,
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
			$counters    = self::get_popular_counters( self::build_popular_query( 'activity', '', $args, $fetch_limit ) );

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
				$vote_data   = self::build_item_vote_data( $activity_id, 'activity', $args, $is_distinct );

				if ( null === $vote_data ) {
					continue;
				}

				$items[] = array(
					'item_id'       => $activity_id,
					'title'         => wp_trim_words( wp_strip_all_tags( $activity_action ), $args['titleTrim'], '…' ),
					'subtitle'      => '',
					'url'           => function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity_id ) : '',
					'count'         => $vote_data['count'],
					'status_counts' => $vote_data['status_counts'],
					'thumbnail'     => '',
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
			$counters    = self::get_popular_counters( self::build_popular_query( 'topic', array( 'topic', 'reply' ), $args, $fetch_limit ) );

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

				$post_id   = (int) $post->ID;
				$vote_data = self::build_item_vote_data( $post_id, 'topic', $args, $is_distinct );

				if ( null === $vote_data ) {
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
					'item_id'       => $post_id,
					'title'         => wp_trim_words( $post_title, $args['titleTrim'], '…' ),
					'subtitle'      => '',
					'url'           => $permalink,
					'count'         => $vote_data['count'],
					'status_counts' => $vote_data['status_counts'],
					'thumbnail'     => '',
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
			$post_types = self::get_post_type_filter( $args );
			$posts      = get_posts(
				array(
					'fields'         => 'ids',
					'posts_per_page' => 500,
					'post_type'      => $post_types ? $post_types : 'any',
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
			$aside_html = '';

			if ( $args['showCount'] && isset( $item['count'] ) ) {
				$aside_html = self::render_item_counts( $item, $args );
			}

			$rank_html = $args['showRank']
				? sprintf(
					'<span class="wp-ulike-top-content__rank"><span class="screen-reader-text">%1$s </span>%2$d</span>',
					esc_html__( 'Rank number', 'wp-ulike' ),
					(int) $rank
				)
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

			$link_aria = $item['title'];
			if ( $args['showCount'] && isset( $item['count'] ) ) {
				$primary_status = $args['sortBy'][0];
				$link_aria      = sprintf(
					/* translators: 1: item title, 2: vote count, 3: vote type label (Like, Unlike, etc.). */
					__( '%1$s — %2$s %3$s', 'wp-ulike' ),
					$item['title'],
					wp_ulike_format_number( $item['count'], $primary_status ),
					self::get_status_labels_text( $args['sortBy'] )
				);
			}

			$title_html = $url
				? sprintf( '<a class="wp-ulike-top-content__link" href="%s" aria-label="%s">%s</a>', esc_url( $url ), esc_attr( $link_aria ), $title_inner )
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
			return WP_Ulike_Pulse_Registry::likers_list_config( $content_type );
		}

		/**
		 * Counter column markup (single or per-status rows).
		 *
		 * @param array<string, mixed> $item Item.
		 * @param array                $args Args.
		 * @return string
		 */
		private static function render_item_counts( $item, $args ) {
			$statuses        = $args['sortBy'];
			$aria_label      = self::get_status_labels_text( $statuses );
			$primary_status  = $statuses[0];
			$status_counts   = ! empty( $item['status_counts'] ) && is_array( $item['status_counts'] )
				? $item['status_counts']
				: array();

			if ( count( $statuses ) > 1 ) {
				$rows         = '';
				$shown_labels = array();

				foreach ( $statuses as $status ) {
					$value = isset( $status_counts[ $status ] ) ? (int) $status_counts[ $status ] : 0;
					if ( $value <= 0 ) {
						continue;
					}

					$label          = self::get_status_label( $status );
					$shown_labels[] = $label;
					$rows          .= sprintf(
						'<div class="wp-ulike-top-content__count-row"><span class="wp-ulike-top-content__count-value">%1$s</span><span class="wp-ulike-top-content__count-label">%2$s</span></div>',
						esc_html( wp_ulike_format_number( $value, $status ) ),
						esc_html( $label )
					);
				}

				if ( '' !== $rows ) {
					return sprintf(
						'<div class="wp-ulike-top-content__aside" aria-label="%1$s"><div class="wp-ulike-top-content__count-stack">%2$s</div></div>',
						esc_attr( implode( ', ', $shown_labels ) ),
						$rows
					);
				}

				return '';
			}

			$count_label = 1 === count( $statuses )
				? self::get_status_label( $primary_status )
				: $aria_label;

			$single_value = (int) ( $status_counts[ $primary_status ] ?? $item['count'] ?? 0 );
			if ( $single_value <= 0 ) {
				return '';
			}

			return sprintf(
				'<div class="wp-ulike-top-content__aside" aria-label="%1$s"><span class="wp-ulike-top-content__count-value">%2$s</span><span class="wp-ulike-top-content__count-label">%3$s</span></div>',
				esc_attr( $count_label ),
				esc_html( wp_ulike_format_number( $single_value, $primary_status ) ),
				esc_html( $count_label )
			);
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
				'<span class="wp-ulike-top-content__likers" aria-label="%1$s"><span class="wp-ulike-top-content__likers-avatars">%2$s%3$s</span></span>',
				esc_attr__( 'Engaged Users', 'wp-ulike' ),
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
				'like'    => esc_html__( 'Like', 'wp-ulike' ),
				'dislike' => esc_html__( 'Dislike', 'wp-ulike' ),
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

			if ( ! ( 1 === count( $args['sortBy'] ) && 'like' === $args['sortBy'][0] ) ) {
				$heading .= ' (' . self::get_status_labels_text( $args['sortBy'] ) . ')';
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
				array( 'value' => 'day_before_yesterday', 'label' => esc_html__( 'Day Before Yesterday', 'wp-ulike' ) ),
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

			$sort_options = self::get_sort_options();

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
				'hasPro'         => self::is_pro_active(),
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
			);
		}
	}
}
