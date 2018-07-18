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
		public function __construct()
		{
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
		 * Return tables which has data inside
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
		 * Get The Posts Data Set
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			JSON Array
		 */
		public function dataset( $table ){
			$output  = array();
			// Get data
			$results = $this->select_data( $table );
			// Create chart dataset
			foreach( $results as $result ){
				$output['label'][] = date_i18n( "M j, Y", strtotime( $result->labels ) );
				$output['data'][]  = $result->counts;
			}
			// Add chart options
			if( ! empty( $output['data'] ) ){
				$output['options'] = $this->charts( $table );
			}

			return $output;
		}

		/**
		 * Get custom options for chartjs
		 *
		 * @author       	Alimir
		 * @since           3.5
		 * @return			Array
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
		 * @author       	Alimir
		 * @since           2.0
		 * @return			String
		 */
		public function select_data( $table ){

			$query  = sprintf( "
					SELECT DATE(date_time) AS labels,
					count(date_time) AS counts
					FROM %s
					GROUP BY labels
					DESC LIMIT %d",
					$this->wpdb->prefix . $table,
					30
				);

			$result = $this->wpdb->get_results( $query );

			if( empty( $result ) ) {
				$result->labels = $result->counts = NULL;
			}

			return $result;
		}

		/**
		 * Get The Summary Of Like Data
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			Integer
		 */
		public function get_data_date( $table, $date ){
			_deprecated_function( 'get_data_date', '3.5', 'count_logs' );
			return $this->count_logs( array( "table" => $table, "date" => $date ) );
		}

		/**
		 * Count all logs from our tables
		 *
		 * @author       	Alimir
		 * @since           3.5
		 * @return			Integer
		 */
		public function count_all_logs( $date = 'all' ){
			// Result
			$result = 0;

			foreach ( $this->tables as $key => $table ) {
				$result += $this->count_logs( array( "table" => $table, "date" => $date ) );
			}

			return $result;
		}

		/**
		 * Count logs by table
		 *
		 * @author       	Alimir
		 * @since           3.5
		 * @return			Integer
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

	        $query = sprintf( "SELECT COUNT(*) FROM %s WHERE 1=1", $this->wpdb->prefix . $table );

	        switch ( $date ) {
	        	case 'today':
	        		$query .= ' AND DATE( date_time ) = DATE( NOW() )';
	        		break;

	        	case 'yesterday':
	        		$query .= ' AND DATE( date_time ) = DATE( subdate( current_date, 1 ) )';
	        		break;

	        	case 'week':
	        		$query .= ' AND WEEK( DATE( date_time ) ) = WEEK( DATE( NOW() ) )';
	        		break;

	        	case 'month':
	        		$query .= ' AND MONTH( DATE( date_time ) ) = MONTH( DATE( NOW() ) )';
	        		break;

	        	case 'year':
	        		$query .= ' AND YEAR( DATE( date_time ) ) = YEAR( DATE( NOW() ) )';
	        		break;
	        }

	        $result = $this->wpdb->get_var( $query );

	        return  empty( $result ) ? 0 : $result;

		}

		public function display_top_likers(){
			$top_likers = $this->get_top_likers();
			$result     = '';
			$counter    = 1;
			foreach ( $top_likers as $user ) {
				$user_ID  = stripslashes( $user->user_id );
				$userdata = get_userdata( $user_ID );
				$username = empty( $userdata ) ? __('Guest User',WP_ULIKE_SLUG) : $userdata->display_name;
				
				$result  .= '	
	            <div class="wp-ulike-row wp-ulike-users-list">
	                <div class="col-3 wp-ulike-counter">
	                	<i class="wp-ulike-icons-trophy"></i>
	                	<span class="aux-wp-ulike-counter">'.$counter++.'th</span>
	                </div>
	                <div class="col-6 wp-ulike-info">
	                	<i class="wp-ulike-icons-profile-male"></i>
						<span class="wp-ulike-user-name">'.$username.'</span>
	                </div>
	                <div class="col-3 wp-ulike-total">
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
		 * @since           2.3
		 * @since           3.0
		 * @return			Array
		 */
		public function get_top_likers(){

			if ( false === ( $result = get_transient( 'wp_ulike_get_top_likers' ) ) ) {
				// Make new sql request
				$query  = sprintf( '
					SELECT T.user_id, SUM(T.CountUser) AS SumUser, T.ip
					FROM(
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM `%1$sulike`
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM `%1$sulike_activities`
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM `%1$sulike_comments`
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM `%1$sulike_forums`
					GROUP BY user_id
					) AS T
					GROUP BY T.user_id
					ORDER BY SumUser DESC LIMIT %2$d',
					$this->wpdb->prefix,
					5
				);
				$result = $this->wpdb->get_results( $query );

				if( !empty( $result ) ) {
					// Set transient
					set_transient( 'wp_ulike_get_top_likers', $result, 24 * HOUR_IN_SECONDS );
				}
			}

			return $result;
		}

		/**
		 * Tops Summaries
		 *
		 * @author       	Alimir
		 * @since           2.6
		 * @since           3.0
		 * @return			Array
		 */
		public function get_tops( $type ){
			switch( $type ){
				case 'top_posts':
					return parent::most_liked_posts();
					break;
				case 'top_comments':
					return parent::most_liked_comments();
				break;
				case 'top_activities':
					return parent::most_liked_activities();
				break;
				case 'top_topics':
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