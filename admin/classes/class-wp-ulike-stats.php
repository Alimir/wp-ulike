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
		 * Set all data info for ajax requests
		 *
		 * @author       	Alimir
		 * @since           3.5.1
		 * @return			Array
		 */
		public function get_all_data(){

			$tables = $this->get_tables();

			$output = array(
				'count_all_logs_all'       => $this->count_all_logs('all'),
				'count_all_logs_today'     => $this->count_all_logs('today'),
				'count_all_logs_yesterday' => $this->count_all_logs('yesterday'),
				'display_top_likers' 	   => $this->display_top_likers(),
			);

			foreach ( $tables as $type => $table ) {
				$output[ 'dataset_' . $table ]              = $this->dataset( $table );
				$output[ 'get_top_' . $type ]               = $this->get_top( $type );
				$output[ 'count_logs_' . $table . '_week' ] = $this->count_logs( array( "table" => $table, "date" => 'week' ) );
				$output[ 'count_logs_' . $table . '_month'] = $this->count_logs( array( "table" => $table, "date" => 'month' ) );
				$output[ 'count_logs_' . $table . '_year' ] = $this->count_logs( array( "table" => $table, "date" => 'year' ) );
				$output[ 'count_logs_' . $table . '_all' ]  = $this->count_logs( array( "table" => $table, "date" => 'all' ) );
			}

			return $output;
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
				$output['label'][] = !empty( $result->labels ) ? date_i18n( "Y-m-d", strtotime( $result->labels ) ) : array();
				$output['data'][]  = !empty( $result->counts ) ? $result->counts : array();
			}
			// Add chart options
			if( ! empty( $output['data'] ) ){
				$output['options'] = $this->charts( $table );
			}

			return $output;
		}

		/**
		 * Set custom options for charts
		 *
		 * @since 3.5
		 * @param string $table
		 * @param array $options
		 * @return void
		 */
		public function charts( $table, $options = array() ){

			switch ( $table ) {
				case 'ulike':
					$options = array(
						'label'                => __( "Posts Stats", WP_ULIKE_SLUG ),
						'backgroundColor'      => "rgba(66, 165, 245,0.8)",
						'borderColor'          => "rgba(21, 101, 192,1)",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'borderWidth'          => 1
					);
					break;

				case 'ulike_comments':
					$options = array(
						'label'                => __( "Comments Stats", WP_ULIKE_SLUG ),
						'backgroundColor'      => "rgba(255, 202, 40,0.8)",
						'borderColor'          => "rgba(255, 143, 0,1)",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'borderWidth'          => 1
					);
					break;

				case 'ulike_activities':
					$options = array(
						'label'                => __( "Activities Stats", WP_ULIKE_SLUG ),
						'backgroundColor'      => "rgba(239, 83, 80,0.8)",
						'borderColor'          => "rgba(198, 40, 40,1)",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'borderWidth'          => 1
					);
					break;

				case 'ulike_forums':
					$options = array(
						'label'                => __( "Topics Stats", WP_ULIKE_SLUG ),
						'backgroundColor'      => "rgba(102, 187, 106,0.8)",
						'borderColor'          => "rgba(27, 94, 32,1)",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'borderWidth'          => 1
					);
					break;
			}

			return $options;
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

			$query  = sprintf( "
					SELECT DATE(date_time) AS labels,
					count(date_time) AS counts
					FROM %s
					WHERE TO_DAYS(NOW()) - TO_DAYS(date_time) <= 30
					GROUP BY labels
					ASC LIMIT %d",
					$this->wpdb->prefix . $table,
					30
				);

			$result = $this->wpdb->get_results( $query );

			if( empty( $result ) ) {
				$result =  new stdClass();
				$result->labels = $result->counts = NULL;
			}

			return $result;
		}

		/**
		 * Get The Summary Of Like Data
		 *
		 * @param string $table
		 * @param string $date
		 * @return integer
		 */
		public function get_data_date( $table, $date ){
			_deprecated_function( 'get_data_date', '3.5', 'count_logs' );
			return $this->count_logs( array( "table" => $table, "date" => $date ) );
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
			extract( $parsed_args );

			$cache_key = sanitize_key( sprintf( 'count_logs_for_%s_table_in_%s_daterange', $table, $date ) );

			if( $date === 'all' ){
				$count_all_logs = wp_ulike_get_meta_data( 1, 'statistics', $cache_key, true );
				if( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ){
					return $count_all_logs;
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

	        return  empty( $counter_value ) ? 0 : $counter_value;
		}

		/**
		 * Display top likers in html format
		 *
		 * @return string
		 */
		public function display_top_likers(){
			$top_likers = $this->get_top_likers();
			$result     = '';
			$counter    = 1;
			foreach ( $top_likers as $user ) {
				$user_ID  = stripslashes( $user->user_id );
				$userdata = get_userdata( $user_ID );
				$username = empty( $userdata ) ? __('Guest User',WP_ULIKE_SLUG) : $userdata->display_name;

				$result  .= '
	            <div class="wp-ulike-flex wp-ulike-users-list">
	                <div class="wp-ulike-counter">
	                	<i class="wp-ulike-icons-trophy"></i>
	                	<span class="wp-ulike-counter">'.$counter++.'th</span>
	                </div>
	                <div class="wp-ulike-info">
	                	<i class="wp-ulike-icons-profile-male"></i>
						<span class="wp-ulike-user-name">'.$username.'</span>
	                </div>
	                <div class="wp-ulike-total">
	                	<i class="wp-ulike-icons-heart"></i>
						<span class="wp-ulike-user-name">'.$user->SumUser.'</span>
	                </div>
	            </div>';
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
			return wp_ulike_get_best_likers_info( 5, NULL );
		}

		/**
		 * Deprecated get top likers function
		 *
		 * @param string $type
		 * @return void
		 */
		public function get_tops( $type ){
			_deprecated_function( 'get_tops', '3.5', 'get_top' );
			return $this->get_top( $type );
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
					return parent::most_liked_posts();
					break;
				case 'comments':
					return parent::most_liked_comments();
				break;
				case 'activities':
					return parent::most_liked_activities();
				break;
				case 'topics':
					return parent::most_liked_topics();
				break;
				default:
					return;
			}
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