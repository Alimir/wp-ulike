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

			foreach ( $get_tables as $type => $table) {
				if ( 'activities' === $type && ! defined( 'BP_VERSION' ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

				if ( 'topics' === $type && ! function_exists( 'is_bbpress' ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

				// If this table has no data, then unset it and continue...
				if( ! $this->count_logs( array ( "table" => $table ) ) ) {
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
		public function get_tops_api_data( $type, $limit = 8 ) {
			$limit = max( 1, min( 20, absint( $limit ) ) );

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

			$table = $tables[ $type ];

			return array(
				'chart'   => $this->dataset( $table ),
				'metrics' => $this->get_type_count_logs( $table ),
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
		 * Count logs for a single table across standard time ranges.
		 *
		 * @param string $table Log table name (without prefix).
		 * @return array
		 */
		private function get_type_count_logs( $table ) {
			return array(
				'week'  => $this->count_logs( array( 'table' => $table, 'date' => 'week' ) ),
				'month' => $this->count_logs( array( 'table' => $table, 'date' => 'month' ) ),
				'year'  => $this->count_logs( array( 'table' => $table, 'date' => 'year' ) ),
				'all'   => $this->count_logs( array( 'table' => $table, 'date' => 'all' ) ),
			);
		}

		/**
		 * Get posts dataset
		 *
		 * @since 2.0
		 * @param string $table
		 * @return void
		 */
		public function dataset( $table ){
			$output  = array();
			$results = $this->select_data( $table );

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
		 * @param string $table
		 * @since 2.0
		 * @return String
		 */
	public function select_data( $table ){

		$data_limit = apply_filters( 'wp_ulike_stats_data_limit', 30 );
		
		// Ensure data_limit is a positive integer for safety
		$data_limit = max( 1, absint( $data_limit ) );

		if ( wp_ulike_use_pulse_queries() ) {
			return WP_Ulike_Pulse_Log_Bridge::get_chart_dataset( $table, $data_limit );
		}

		// Fetch the most recent date_time from the table
		// MAX() query uses the date_time index efficiently (reverse index scan)
		$table_escaped = esc_sql( $this->wpdb->prefix . $table );
		$latest_date = $this->wpdb->get_var( "
			SELECT MAX(date_time) FROM `{$table_escaped}`
		" );

		// If no data exists, return empty result set for chart consumers.
		if( empty( $latest_date ) ) {
			return array();
		}

		// Calculate start date in PHP for maximum index optimization
		// Use $data_limit for date range to match the data limit filter
		// This ensures MySQL gets a constant value to compare against (most index-friendly)
		$latest_timestamp = strtotime( $latest_date );
		
		// Safety check: ensure timestamp is valid
		if( false === $latest_timestamp ) {
			return array();
		}
		
		// DAY_IN_SECONDS is a WordPress core constant (defined since WP 3.5)
		$start_date = date( 'Y-m-d H:i:s', $latest_timestamp - ( $data_limit * DAY_IN_SECONDS ) );

		// Use index-friendly date range query with pre-calculated dates
		// Direct comparison allows MySQL to use date_time index efficiently
		// No LIMIT needed since date range naturally limits results to $data_limit days
		// (GROUP BY DATE() returns at most one row per day)
		$query = $this->wpdb->prepare( "
			SELECT DATE(date_time) AS labels,
			count(date_time) AS counts
			FROM `{$table_escaped}`
			WHERE date_time >= %s
			AND date_time <= %s
			GROUP BY labels
			ORDER BY labels ASC",
			$start_date,
			$latest_date
		);

		$result = $this->wpdb->get_results( $query );

		if( empty( $result ) ) {
			return array();
		}

		return $result;
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
		 * Count logs by table
		 *
		 * @since 3.5
		 * @param array $args
		 * @return void
		 */
		public function count_logs( $args = array() ){

			//Main Data
			$defaults  = array(
				"table" => 'ulike',
				"date"  => 'all'
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			// Extract variables
			$table = isset( $parsed_args['table'] ) ? $parsed_args['table'] : 'ulike';
			$date = isset( $parsed_args['date'] ) ? $parsed_args['date'] : 'all';

			$cache_key = sanitize_key( sprintf( 'count_logs_for_%s_table_in_%s_daterange', $table, is_array($date) ? implode('_', $date) : $date ) );

			if( $date === 'all' ){
				$count_all_logs = wp_ulike_get_meta_data( 1, 'statistics', $cache_key, true );
				if( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ){
					return absint($count_all_logs);
				}
			}

			$counter_value = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

			// Make a cachable query to get new like count from all tables
			if( false === $counter_value ){
				if ( wp_ulike_use_pulse_queries() ) {
					$counter_value = WP_Ulike_Pulse_Query::count_logs_for_table( $table, $date );
				} else {
					$table_escaped = esc_sql( $this->wpdb->prefix . $table );
					$query = "SELECT COUNT(*) FROM `{$table_escaped}` WHERE 1=1";
					$query .= wp_ulike_get_period_limit_sql( $date );

					$counter_value = $this->wpdb->get_var( $query );
				}
				wp_cache_add( $cache_key, $counter_value, WP_ULIKE_SLUG, 300 );
			}

			if( $date === 'all' ){
				wp_ulike_update_meta_data( 1, 'statistics', $cache_key, $counter_value );
			}

	        return  empty( $counter_value ) ? 0 : absint( $counter_value );
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

			$cache_key = sanitize_key( 'stats_peak_hours_' . md5( implode( ',', array_values( $tables ) ) ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

			if ( false !== $cached ) {
				return $cached;
			}

			$union_parts = array();

			if ( wp_ulike_use_pulse_queries() ) {
				$results = WP_Ulike_Pulse_Log_Bridge::get_peak_hours_rows( array_values( $tables ) );
			} else {
				foreach ( $tables as $table ) {
					$table_escaped = esc_sql( $this->wpdb->prefix . $table );
					$union_parts[] = "SELECT date_time FROM `{$table_escaped}` WHERE date_time >= NOW() - INTERVAL 30 DAY";
				}

				$query = sprintf(
					"SELECT HOUR(date_time) AS hour_slot, COUNT(*) AS total_count
					FROM ( %s ) AS combined
					GROUP BY hour_slot
					ORDER BY hour_slot ASC",
					implode( ' UNION ALL ', $union_parts )
				);

				$results = $this->wpdb->get_results( $query );
			}
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

			wp_cache_set( $cache_key, $data, WP_ULIKE_SLUG, 15 * MINUTE_IN_SECONDS );

			return $data;
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