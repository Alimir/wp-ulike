<?php
/**
 * Class for statistics process
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_stats' ) ) {

	class wp_ulike_stats extends wp_ulike_widget{

		// Private variables
		private $wpdb, $tables, $active_tables = null;

		/**
		 * Instance of this class.
		 *
		 * @var      object
		 */
		protected static $instance  = null;

		/**
		 * Constructor
		 */
		function __construct(){
			global $wpdb;
			$this->wpdb   = $wpdb;
			$this->tables = WP_Ulike_Pulse_Registry::stats_table_map();
		}

		/**
		 * Return tables which has any data inside
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			Array
		 */
		public function get_tables(){
			if ( null !== $this->active_tables ) {
				return $this->active_tables;
			}

			// Tables buffer
			$get_tables = $this->tables;

			foreach ( $get_tables as $type => $item_type ) {
				if ( 'activities' === $type && ! defined( 'BP_VERSION' ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

				if ( 'topics' === $type && ! function_exists( 'is_bbpress' ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

				// If this type has no data, then unset it and continue...
				if ( ! $this->count_logs( array( 'type' => $item_type ) ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

			}

			$this->active_tables = $get_tables;

			return $this->active_tables;
		}

		/**
		 * Get all data for api
		 *
		 * @return array
		 */
		public function get_all_data() {
			$tables = $this->get_tables();
			$meta   = wp_ulike_get_site_stats_meta( array_keys( $tables ) );

			$output = array(
				'overview' => $this->get_overview(),
				'meta'     => array_merge(
					array(
						'build'         => 'free',
						'content_types' => array_keys( $tables ),
						'woocommerce'   => array(
							'active'           => class_exists( 'WooCommerce' ),
							'report_available' => class_exists( 'WooCommerce' ),
							'product_likes'    => false,
							'review_likes'     => false,
						),
					),
					$meta
				),
			);

			return $output;
		}

		/**
		 * Overview page data — charts, items, metrics loaded separately.
		 *
		 * @return array
		 */
		public function get_overview_api_data() {
			return array(
				'overview'   => $this->get_overview(),
				'peak_hours' => $this->get_peak_hours(),
			);
		}

		/**
		 * Top content for a single type (free — no filters).
		 *
		 * @param string $type Content type key.
		 * @param int    $limit Max items.
		 * @return array|null
		 */
		public function get_tops_api_data( $type, $limit = 10 ) {
			$limit = max( 1, min( 100, absint( $limit ) ) );

			$tables = $this->get_tables();
			if ( ! isset( $tables[ $type ] ) ) {
				return null;
			}

			$items = $this->normalize_top_items( $this->get_top( $type ), $type );
			return array(
				'items' => array_slice( $items, 0, $limit ),
				'total' => count( $items ),
			);
		}

		/**
		 * Normalize top rows for the React admin.
		 *
		 * @param array  $items Raw items.
		 * @param string $type  Optional type hint.
		 * @return array
		 */
		private function normalize_top_items( $items, $type = 'posts' ) {
			if ( empty( $items ) || ! is_array( $items ) ) {
				return array();
			}

			$normalized = array();

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$title = isset( $item['title'] ) ? $item['title'] : '';
				if ( 'comments' === $type && empty( $title ) && ! empty( $item['author'] ) ) {
					$title = $item['author'];
				}

				$normalized[] = array(
					'title'       => $title,
					'permalink'   => isset( $item['permalink'] ) ? $item['permalink'] : '',
					'likes_count' => isset( $item['likes_count'] ) ? absint( $item['likes_count'] ) : 0,
				);
			}

			return $normalized;
		}

		/**
		 * Engagement data for a single content type.
		 *
		 * @param string $type Content type.
		 * @return array|null
		 */
		public function get_engagement_api_data( $type ) {
			$tables = $this->get_tables();

			if ( ! isset( $tables[ $type ] ) ) {
				return null;
			}

			$item_type = $tables[ $type ];

			return array(
				'chart'   => $this->dataset( $item_type ),
				'metrics' => $this->get_type_count_logs( $item_type ),
			);
		}

		/**
		 * Get basic statistics
		 *
		 * @return array
		 */
		private function get_overview() {
			return array(
				'total'     => $this->count_all_logs( 'all' ),
				'today'     => $this->count_all_logs( 'today' ),
				'yesterday' => $this->count_all_logs( 'yesterday' ),
				'week'      => $this->count_all_logs( 'week' ),
				'last_week' => $this->count_all_logs( 'last_week' ),
			);
		}

		/**
		 * Count logs for a single content type across standard time ranges.
		 *
		 * @param string $item_type Canonical item type (post, comment, …).
		 * @return array
		 */
		private function get_type_count_logs( $item_type ) {
			return array(
				'week'  => $this->count_logs( array( 'type' => $item_type, 'date' => 'week' ) ),
				'month' => $this->count_logs( array( 'type' => $item_type, 'date' => 'month' ) ),
				'year'  => $this->count_logs( array( 'type' => $item_type, 'date' => 'year' ) ),
				'all'   => $this->count_logs( array( 'type' => $item_type, 'date' => 'all' ) ),
			);
		}

		/**
		 * Get posts dataset
		 *
		 * @since 2.0
		 * @param string $item_type Canonical item type.
		 * @return void
		 */
		public function dataset( $item_type ){
			$output  = array();
			$results = $this->select_data( $item_type );

			foreach( $results as $result ){
				if( isset( $result->labels ) && isset( $result->counts ) ){
					$output[]= [
						'date'  => wp_date( "Y-m-d", strtotime( $result->labels ) ),
						'total' => (int) $result->counts
					];
				}
			}

			return $output;
		}
		/**
		 * Get The Logs Data From Tables
		 *
		 * @author Alimir
		 * @param string $item_type Canonical item type.
		 * @since 2.0
		 * @return String
		 */
	public function select_data( $item_type ) {
		$data_limit  = max( 1, absint( apply_filters( 'wp_ulike_stats_data_limit', 30 ) ) );
		$logical_key = sprintf( 'stats_chart_%s_%d', sanitize_key( $item_type ), $data_limit );

		return WP_Ulike_Query_Cache::remember_stats(
			$logical_key,
			static function () use ( $item_type, $data_limit ) {
				return WP_Ulike_Pulse_Log_Bridge::get_chart_dataset( $item_type, $data_limit );
			}
		);
	}

		/**
		 * Count all logs from the tables
		 *
		 * @since 3.5
		 * @param string $date
		 * @return integer
		 */
		public function count_all_logs( $date = 'all' ){
			return wp_ulike_count_all_logs( $date );
		}

		/**
		 * Count logs by content type.
		 *
		 * @since 3.5
		 * @param array $args
		 * @return void
		 */
		public function count_logs( $args = array() ){

			//Main Data
			$defaults  = array(
				'type' => 'post',
				'date' => 'all',
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			// Backward compat: callers may still pass legacy `table` (suffix or item type).
			if ( empty( $parsed_args['type'] ) && ! empty( $parsed_args['table'] ) ) {
				$parsed_args['type'] = $parsed_args['table'];
			}

			$item_type = ! empty( $parsed_args['type'] ) ? $parsed_args['type'] : 'post';
			$resolved_type = WP_Ulike_Pulse_Registry::type_by_table_suffix( $item_type );
			$item_type     = $resolved_type ? $resolved_type : WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$date          = isset( $parsed_args['date'] ) ? $parsed_args['date'] : 'all';
			$logical_key   = sprintf(
				'count_logs_for_%s_in_%s_daterange',
				$item_type,
				is_array( $date ) ? implode( '_', $date ) : $date
			);

			if ( 'all' === $date ) {
				$count_all_logs = WP_Ulike_Query_Cache::get_statistics_meta( $logical_key );
				if ( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ) {
					return absint( $count_all_logs );
				}
			}

			$counter_value = WP_Ulike_Query_Cache::remember_stats(
				$logical_key,
				static function () use ( $item_type, $date ) {
					return WP_Ulike_Pulse_Query::count_logs_for_type( $item_type, $date );
				}
			);

			if ( 'all' === $date ) {
				WP_Ulike_Query_Cache::set_statistics_meta( $logical_key, $counter_value );
			}

			return empty( $counter_value ) ? 0 : absint( $counter_value );
		}

		/**
		 * Tops Summaries
		 *
		 * @param string $type
		 * @since 3.5
		 * @return array
		 */
		public function get_top( $type ){
			switch( $type ){
				case 'posts':
					return $this->top_posts();
					break;
				case 'comments':
					return $this->top_comments();
				break;
				case 'activities':
					return $this->top_activities();
				break;
				case 'topics':
					return $this->top_topics();
				break;
				default:
					return;
			}
		}

		/**
		 * Top posts
		 *
		 * @return array
		 */
		public function top_posts() {
			$post_type = get_post_types_by_support( array( 'title', 'editor', 'thumbnail' ) );
			$post_type = apply_filters( 'wp_ulike_supported_post_types_for_top_posts_list', $post_type );

			$item_info = wp_ulike_get_popular_items_info( array(
				'type'     => 'post',
				'rel_type' => $post_type,
				'status'   => 'like',
				'period'   => 'all',
				'limit'    => 10,
			) );

			if ( empty( $item_info ) ) {
				return array();
			}

			$ids       = array();
			$counters  = array();
			foreach ( $item_info as $row ) {
				$id              = (int) $row->item_ID;
				$ids[]           = $id;
				$counters[ $id ] = (int) $row->counter;
			}

			$posts = get_posts( apply_filters( 'wp_ulike_get_top_posts_query', array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'inherit' ),
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 10,
			) ) );

			$result = array();
			foreach ( $posts as $post ) {
				if ( empty( $post->post_title ) ) {
					continue;
				}

				$post_id = wp_ulike_get_the_id( $post->ID );
				$result[] = array(
					'title'       => stripslashes( $post->post_title ),
					'permalink'   => get_permalink( $post_id ),
					'likes_count' => isset( $counters[ $post->ID ] ) ? $counters[ $post->ID ] : 0,
				);
			}

			return $result;
		}

		/**
		 * Top comments
		 *
		 * @return array
		 */
		public function top_comments() {
			$post_type = get_post_types_by_support( array( 'title', 'editor', 'thumbnail' ) );

			$item_info = wp_ulike_get_popular_items_info( array(
				'type'     => 'comment',
				'rel_type' => '',
				'status'   => 'like',
				'period'   => 'all',
				'limit'    => 10,
			) );

			if ( empty( $item_info ) ) {
				return array();
			}

			$ids      = array();
			$counters = array();
			foreach ( $item_info as $row ) {
				$id              = (int) $row->item_ID;
				$ids[]           = $id;
				$counters[ $id ] = (int) $row->counter;
			}

			$comments = get_comments( apply_filters( 'wp_ulike_get_top_comments_query', array(
				'comment__in' => $ids,
				'orderby'     => 'comment__in',
				'post_type'   => $post_type,
			) ) );

			$result = array();
			foreach ( $comments as $comment ) {
				$author       = stripslashes( $comment->comment_author );
				$post_title   = get_the_title( $comment->comment_post_ID );
				$comment_text = wp_strip_all_tags( $comment->comment_content );
				$excerpt      = wp_trim_words( $comment_text, 10, '…' );
				$context      = sprintf(
					/* translators: 1: comment author, 2: post title */
					__( '%1$s on %2$s', 'wp-ulike' ),
					$author,
					$post_title ? $post_title : __( '(Untitled post)', 'wp-ulike' )
				);

				if ( empty( $excerpt ) ) {
					$title = sprintf(
						/* translators: %s: comment author and post context, e.g. "John on Hello World" */
						__( 'Comment by %s', 'wp-ulike' ),
						$context
					);
				} else {
					$title = $excerpt . ' — ' . $context;
				}

				$result[] = array(
					'title'       => $title,
					'permalink'   => get_comment_link( $comment->comment_ID ),
					'likes_count' => isset( $counters[ $comment->comment_ID ] ) ? $counters[ (int) $comment->comment_ID ] : 0,
				);
			}

			return $result;
		}

		/**
		 * Top buddypress activities
		 *
		 * @return void
		 */
		public function top_activities() {
			if ( ! defined( 'BP_VERSION' ) ) {
				return array();
			}

			global $wpdb;

			$item_info = wp_ulike_get_popular_items_info( array(
				'type'     => 'activity',
				'rel_type' => '',
				'status'   => 'like',
				'period'   => 'all',
				'limit'    => 10,
			) );

			if ( empty( $item_info ) ) {
				return array();
			}

			$ids      = array();
			$counters = array();
			foreach ( $item_info as $row ) {
				$id              = (int) $row->item_ID;
				$ids[]           = $id;
				$counters[ $id ] = (int) $row->counter;
			}

			$bp_prefix    = is_multisite() ? 'base_prefix' : 'prefix';
			$table_name   = esc_sql( $wpdb->$bp_prefix . 'bp_activity' );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$activities   = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM `{$table_name}` WHERE `id` IN ({$placeholders}) ORDER BY FIELD(`id`, {$placeholders})",
				array_merge( $ids, $ids )
			) );

			$result = array();
			foreach ( (array) $activities as $activity ) {
				$activity_action = ! empty( $activity->content ) ? $activity->content : $activity->action;
				if ( empty( $activity_action ) ) {
					continue;
				}

				$result[] = array(
					'permalink'   => function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity->id ) : '',
					'title'       => wp_strip_all_tags( $activity_action ),
					'likes_count' => isset( $counters[ (int) $activity->id ] ) ? $counters[ (int) $activity->id ] : 0,
				);
			}

			return $result;
		}

		/**
		 * Top bbpress topics
		 *
		 * @return void
		 */
		public function top_topics() {
			if ( ! function_exists( 'is_bbpress' ) ) {
				return array();
			}

			$post_types = array( 'topic', 'reply' );

			$item_info = wp_ulike_get_popular_items_info( array(
				'type'     => 'topic',
				'rel_type' => $post_types,
				'status'   => 'like',
				'period'   => 'all',
				'limit'    => 10,
			) );

			if ( empty( $item_info ) ) {
				return array();
			}

			$ids      = array();
			$counters = array();
			foreach ( $item_info as $row ) {
				$id              = (int) $row->item_ID;
				$ids[]           = $id;
				$counters[ $id ] = (int) $row->counter;
			}

			$posts = get_posts( apply_filters( 'wp_ulike_get_top_posts_query', array(
				'post_type'      => $post_types,
				'post_status'    => array( 'publish', 'inherit' ),
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 10,
			) ) );

			$result = array();
			foreach ( $posts as $post ) {
				$post_title = function_exists( 'bbp_get_forum_title' ) ? bbp_get_forum_title( $post->ID ) : $post->post_title;
				$permalink  = 'topic' === get_post_type( $post->ID ) ? bbp_get_topic_permalink( $post->ID ) : bbp_get_reply_url( $post->ID );

				$result[] = array(
					'title'       => $post_title,
					'permalink'   => $permalink,
					'likes_count' => isset( $counters[ $post->ID ] ) ? $counters[ $post->ID ] : 0,
				);
			}

			return $result;
		}

		/**
		 * Hour-of-day engagement distribution (last 30 days).
		 *
		 * @return array
		 */
		public function get_peak_hours() {
			$tables = $this->get_tables();

			if ( empty( $tables ) ) {
				return array();
			}

			$logical_key = 'stats_peak_hours_' . md5( implode( ',', array_values( $tables ) ) );

			return WP_Ulike_Query_Cache::remember_stats(
				$logical_key,
				static function () use ( $tables ) {
					$results = WP_Ulike_Pulse_Log_Bridge::get_peak_hours_rows( array_values( $tables ) );
					$hours   = array_fill( 0, 24, 0 );

					if ( ! empty( $results ) ) {
						foreach ( $results as $row ) {
							$slot = (int) $row->hour_slot;
							if ( $slot >= 0 && $slot <= 23 ) {
								$hours[ $slot ] = absint( $row->total_count );
							}
						}
					}

					$data = array();
					for ( $h = 0; $h < 24; $h++ ) {
						$data[] = array(
							'hour'  => $h,
							'label' => wp_date( 'g A', strtotime( sprintf( 'today %02d:00', $h ) ) ),
							'count' => $hours[ $h ],
						);
					}

					return $data;
				},
				WP_Ulike_Query_Cache::TTL_PEAK_HOURS
			);
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return    object    A single instance of this class.
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

	}

}