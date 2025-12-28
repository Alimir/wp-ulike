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
		private $wpdb, $tables;

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
			$this->tables = array(
				'posts'      => 'ulike',
				'comments'   => 'ulike_comments',
				'activities' => 'ulike_activities',
				'topics'     => 'ulike_forums',
			);
		}

		/**
		 * Return tables which has any data inside
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			Array
		 */
		public function get_tables(){
			// Tables buffer
			$get_tables = $this->tables;

			foreach ( $get_tables as $type => $table) {
				// If this table has no data, then unset it and continue...
				if( ! $this->count_logs( array ( "table" => $table ) ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

			}

			return $get_tables;
		}

		/**
		 * Get all data for api
		 *
		 * @return array
		 */
		public function get_all_data() {
			$output = array(
				'overview' => $this->get_overview(),
				'charts'   => $this->get_datasets(),
				'items'    => $this->get_top_items(),
				'metrics'  => $this->get_count_logs()
			);

			return $output;
		}

		/**
		 * Get basic statistics
		 *
		 * @return array
		 */
		private function get_overview() {
			return array(
				'total'                => $this->count_all_logs('all'),
				'today'                => $this->count_all_logs('today'),
				'yesterday'            => $this->count_all_logs('yesterday')
			);
		}

		// Get datasets for each table
		private function get_datasets() {
			$tables = $this->get_tables();
			$datasets = array();

			foreach ($tables as $type => $table) {
				$datasets[$type] = $this->dataset($table);
			}

			return $datasets;
		}

		// Get top items for each type
		private function get_top_items() {
			$tables = $this->get_tables();
			$top_items = array();

			$top_items['posts']      = $this->get_top( 'posts' );
			$top_items['comments']   = $this->get_top( 'comments' );
			$top_items['activities'] = $this->get_top( 'activities' );
			$top_items['topics']     = $this->get_top( 'topics' );
			$top_items['engagers']   = $this->display_top_likers();

			return $top_items;
		}

		// Get count logs for each table with different time ranges
		private function get_count_logs() {
			$tables = $this->get_tables();
			$count_logs = array();

			foreach ($tables as $type => $table) {
				$count_logs[$type] = array(
					'week'       => $this->count_logs(array("table" => $table, "date" => 'week')),
					'last_week'  => $this->count_logs(array("table" => $table, "date" => 'last_week')),
					'month'      => $this->count_logs(array("table" => $table, "date" => 'month')),
					'last_month' => $this->count_logs(array("table" => $table, "date" => 'last_month')),
					'year'       => $this->count_logs(array("table" => $table, "date" => 'year')),
					'last_year'  => $this->count_logs(array("table" => $table, "date" => 'last_year')),
					'all'        => $this->count_logs(array("table" => $table, "date" => 'all'))
				);
			}

			return $count_logs;
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
			// Get data
			$results = $this->select_data( $table );

			// Create chart dataset
			foreach( $results as $result ){
				if( isset( $result->labels ) & isset( $result->counts ) ){
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

		// Fetch the most recent date_time from the table
		// MAX() query uses the date_time index efficiently (reverse index scan)
		$table_escaped = esc_sql( $this->wpdb->prefix . $table );
		$latest_date = $this->wpdb->get_var( "
			SELECT MAX(date_time) FROM `{$table_escaped}`
		" );

		// If no data exists, return empty result
		if( empty( $latest_date ) ) {
			$result = new stdClass();
			$result->labels = $result->counts = NULL;
			return $result;
		}

		// Calculate start date in PHP for maximum index optimization
		// Use $data_limit for date range to match the data limit filter
		// This ensures MySQL gets a constant value to compare against (most index-friendly)
		$latest_timestamp = strtotime( $latest_date );
		
		// Safety check: ensure timestamp is valid
		if( false === $latest_timestamp ) {
			$result = new stdClass();
			$result->labels = $result->counts = NULL;
			return $result;
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
			$result = new stdClass();
			$result->labels = $result->counts = NULL;
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
				$query = sprintf( "SELECT COUNT(*) FROM %s WHERE 1=1", $this->wpdb->prefix . $table );
				$query .= wp_ulike_get_period_limit_sql( $date );

				$counter_value = $this->wpdb->get_var( $query );
				wp_cache_add( $cache_key, $counter_value, WP_ULIKE_SLUG, 300 );
			}

			if( $date === 'all' ){
				wp_ulike_update_meta_data( 1, 'statistics', $cache_key, $counter_value );
			}

	        return  empty( $counter_value ) ? 0 : absint( $counter_value );
		}

		/**
		 * Display top likers in html format
		 *
		 * @return string
		 */
		public function display_top_likers(){
			$top_likers = $this->get_top_likers();
			$result     = [];

			foreach ( $top_likers as $user ) {
				$user_ID  = stripslashes( $user->user_id );
				$userdata = get_userdata( $user_ID );
				$username = empty( $userdata ) ? esc_html__('Guest User','wp-ulike') : esc_attr( $userdata->display_name );

				$result[] = [
					'permalink'   => get_edit_profile_url( $user_ID ),
					'title'       => $username,
					'likes_count' => absint($user->SumUser)
				];
			}

			return $result;
		}

		/**
		 * Top Likers Summary
		 *
		 * @author       	Alimir
		 * @since           3.0
		 * @return			Array
		 */
		public function get_top_likers(){
			return wp_ulike_get_best_likers_info( 10, NULL );
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

			$posts       = wp_ulike_get_most_liked_posts( 10, '', 'post',  'all' );
			$result      = [];
			$is_distinct = wp_ulike_setting_repo::isDistinct( 'post' );

			if( empty( $posts ) ){
				return $result;
			}

			foreach ($posts as $post) {
				// Check post title existence
				if( empty( $post->post_title ) ){
					continue;
				}

				$post_id       = wp_ulike_get_the_id( $post->ID );
				$counter_value = wp_ulike_get_counter_value( $post_id, 'post', 'like', $is_distinct );

				$result[] = [
					'title'       => stripslashes($post->post_title),
					'permalink'   => get_permalink($post_id),
					'likes_count' => $counter_value
				];
			}

			return $result;
		}

		/**
		 * Top comments
		 *
		 * @return array
		 */
		public function top_comments() {

			$comments    = wp_ulike_get_most_liked_comments( 10, '', 'all' );
			$result      = [];
			$is_distinct = wp_ulike_setting_repo::isDistinct( 'comment' );

			if( empty( $comments ) ){
				return $result;
			}

			foreach ($comments as $comment) {
				$comment_author    = stripslashes($comment->comment_author);
				$post_title        = get_the_title($comment->comment_post_ID);
				$comment_permalink = get_comment_link($comment->comment_ID);
				$counter_value     = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'like', $is_distinct );

				$result[] = [
					'author'      => $comment_author,
					'title'       => $post_title,
					'permalink'   => $comment_permalink,
					'likes_count' => $counter_value
				];
			}

			return $result;
		}

		/**
		 * Top buddypress activities
		 *
		 * @return void
		 */
		public function top_activities() {

			if( ! defined( 'BP_VERSION' ) ) {
				return [];
			}

			$activities  = wp_ulike_get_most_liked_activities( 10, 'all' );
			$result      = [];
			$is_distinct = wp_ulike_setting_repo::isDistinct( 'activity' );

			if( empty( $activities ) ){
				return $result;
			}

			foreach ($activities as $activity) {
				$activity_permalink = function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '';
				$activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
				$counter_value      = wp_ulike_get_counter_value( $activity->id, 'activity', 'like', $is_distinct );

				// Skip empty activities
				if( empty( $activity_action ) ){
					continue;
				}

				$result[] = [
					'permalink'   => $activity_permalink,
					'title'       => wp_strip_all_tags( $activity_action ),
					'likes_count' => $counter_value
				];
			}

			return $result;
		}

		/**
		 * Top bbpress topics
		 *
		 * @return void
		 */
		public function top_topics() {

			if( ! function_exists( 'is_bbpress' ) ) {
				return [];
			}

			$posts       = wp_ulike_get_most_liked_posts( 10, array( 'topic', 'reply' ), 'topic', 'all' );
			$result      = [];
			$is_distinct = wp_ulike_setting_repo::isDistinct( 'topic' );

			if( empty( $posts ) ){
				return $result;
			}

			foreach ($posts as $post) {
				$post_title    = function_exists('bbp_get_forum_title') ? bbp_get_forum_title( $post->ID ) : $post->post_title;
				$permalink     = 'topic' === get_post_type( $post->ID ) ? bbp_get_topic_permalink( $post->ID ) : bbp_get_reply_url( $post->ID );
				$counter_value = wp_ulike_get_counter_value( $post->ID, 'topic', 'like', $is_distinct );

				$result[] = [
					'title'       => $post_title,
					'permalink'   => $permalink,
					'likes_count' => $counter_value
				];
			}

			return $result;
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