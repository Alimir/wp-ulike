<?php 
if ( ! class_exists( 'wp_ulike_stats' ) ) {

	class wp_ulike_stats{
		private $wpdb;
		
		/**
		 * Constructor
		 */	
		public function __construct()
		{
			global $wpdb;
			$this->wpdb = $wpdb;
			add_action('admin_enqueue_scripts', array($this,'enqueue_script'));
		}

		/**
		 * Add chart scripts files + Creating Localize Objects
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @updated         2.3
		 * @return			Void
		 */		
		public function enqueue_script($hook)
		{
			$currentScreen 	= get_current_screen();
			$get_option 	= get_option( 'wp_ulike_statistics_screen' );
			
			if ( $currentScreen->id != $hook ) return;
			
			if(is_rtl())
				wp_register_style( 'wp_ulike_stats_style', plugins_url( 'css/statistics-rtl.css' , __FILE__ ) );
			else
				wp_register_style( 'wp_ulike_stats_style', plugins_url( 'css/statistics.css' , __FILE__ ) );
			
			//wp_enqueue_style 
			wp_enqueue_style( 'wp_ulike_stats_style' );			
			
			wp_register_script('wp_ulike_chart', plugins_url( 'js/chart.min.js' , __FILE__ ), array('jquery'), null, true);
			wp_enqueue_script('wp_ulike_chart');
			wp_register_script('wp_ulike_vmap', plugins_url( 'js/jquery.vmap.min.js' , __FILE__ ), array('jquery'), null, true);
			wp_enqueue_script('wp_ulike_vmap');
			wp_register_script('wp_ulike_world', plugins_url( 'js/jquery.vmap.world.js' , __FILE__ ), array('jquery'), null, true);
			wp_enqueue_script('wp_ulike_world');
			wp_register_script('wp_ulike_stats', plugins_url( 'js/statistics.js' , __FILE__ ), array('jquery'), null, true);
			wp_enqueue_script('wp_ulike_stats');
			wp_localize_script( 'wp_ulike_stats', 'wp_ulike_statistics', array(
				'posts_date_labels' 		=> $this->posts_dataset('label'),
				'comments_date_labels' 		=> $this->comments_dataset('label'),
				'activities_date_labels' 	=> $this->activities_dataset('label'),
				'topics_date_labels' 		=> $this->topics_dataset('label'),
				'posts_dataset' 			=> $this->posts_dataset('dataset'),
				'comments_dataset' 			=> $this->comments_dataset('dataset'),
				'activities_dataset' 		=> $this->activities_dataset('dataset'),
				'topics_dataset' 			=> $this->topics_dataset('dataset'),
				'data_map' 					=> $get_option['likers_map'] == 1 ? $this->data_map() : null
			));			
			wp_enqueue_script('postbox');
		}
		
		/**
		 * Get The Posts Data Set
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @updated         2.2		 
		 * @return			JSON Array
		 */				
		public function posts_dataset($type){
			$newarray 		= array();
			$return_type 	= $type != 'dataset' ? 'new_date_time' : 'count_date_time';
			$return_val 	= $this->select_data('ulike');
			foreach($return_val as $val){
				if($return_type == 'new_date_time'){
				$newarray[] = date_i18n("M j, Y", strtotime($val->$return_type) );
				}
				else
				$newarray[] = $val->$return_type;
			}
			return json_encode($newarray);
		}
		
		/**
		 * Get The Comments Data Set
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @updated         2.2
		 * @return			JSON Array 
		 */			
		public function comments_dataset($type){
			$newarray 		= array();
			$return_type 	= $type != 'dataset' ? 'new_date_time' : 'count_date_time';
			$return_val 	= $this->select_data('ulike_comments');
			foreach($return_val as $val){
				if($return_type == 'new_date_time'){
				$newarray[] = date_i18n("M j, Y", strtotime($val->$return_type) );
				}
				else
				$newarray[] = $val->$return_type;
			}				
			return json_encode($newarray);
		}

		/**
		 * Get The Activities Data Set
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @updated         2.2
		 * @return			JSON Array 
		 */			
		public function activities_dataset($type){
			$newarray 		= array();
			$return_type 	= $type != 'dataset' ? 'new_date_time' : 'count_date_time';
			$return_val 	= $this->select_data('ulike_activities');
			foreach($return_val as $val){
				if($return_type == 'new_date_time'){
				$newarray[] = date_i18n("M j, Y", strtotime($val->$return_type) );
				}
				else
				$newarray[] = $val->$return_type;
			}				
			return json_encode($newarray);
		}
		
		/**
		 * Get The Activities Data Set
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @updated         2.2
		 * @return			JSON Array 
		 */			
		public function topics_dataset($type){
			$newarray 		= array();
			$return_type 	= $type != 'dataset' ? 'new_date_time' : 'count_date_time';
			$return_val 	= $this->select_data('ulike_forums');
			foreach($return_val as $val){
				if($return_type == 'new_date_time'){
				$newarray[] = date_i18n("M j, Y", strtotime($val->$return_type) );
				}
				else
				$newarray[] = $val->$return_type;
			}				
			return json_encode($newarray);
		}
		
		/**
		 * Get The Logs Data From Tables
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			String
		 */		
		public function select_data($table){
			$get_option = get_option( 'wp_ulike_statistics_screen' );
			$set_number = $get_option['days_number'] != null ? $get_option['days_number'] : 20;
			$return_val = $this->wpdb->get_results(
			"
			SELECT DATE(date_time) AS new_date_time, count(date_time) AS count_date_time
			FROM ".$this->wpdb->prefix."$table
			GROUP BY new_date_time DESC LIMIT $set_number
			");
			return $return_val;
		}
		
		/**
		 * Get The Summary Of Like Data
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			Integer
		 */			
		public function get_data_date($table,$time){
			if($time == 'today')
			$where_val = "DATE(date_time) = DATE(NOW())";
			else if($time == 'yesterday')
			$where_val = "DATE(date_time) = DATE(subdate(current_date, 1))";
			else if($time == 'week')
			$where_val = "week(DATE(date_time)) = week(DATE(NOW()))";
			else 
			$where_val = "month(DATE(date_time)) = month(DATE(NOW()))";
			
			$return_val = $this->wpdb->get_var(
			"
			SELECT COUNT(*)
			FROM ".$this->wpdb->prefix."$table
			WHERE $where_val
			");
			return $return_val;		
		}

		/**
		 * Get The Sum Of All Likes
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @updated         2.1
		 * @return			Integer
		 */					
		public function get_all_data_date($table,$name){
			$table_name = $this->wpdb->prefix . $table;
			if($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
				$return_val = $this->wpdb->get_var(
				"
				SELECT SUM(meta_value)
				FROM ".$this->wpdb->prefix."$table
				WHERE meta_key LIKE '$name'
				");
				return $return_val;
			}
			else
				return 0;
		}
		
		
		/**
		 * Get Data Map
		 *
		 * @author       	Alimir
		 * @since           2.3
		 * @return			String
		*/
		public function data_map(){
			$country_data = array();
			$return_val = $this->wpdb->get_results(
			"
			SELECT T.user_ip AS get_user_ip , SUM(T.count_user_ip) get_count_user_ip
			FROM(
			SELECT ip AS user_ip, count(ip) AS count_user_ip
			FROM ".$this->wpdb->prefix."ulike
			GROUP BY user_ip
			UNION ALL
			SELECT ip AS user_ip, count(ip) AS count_user_ip
			FROM ".$this->wpdb->prefix."ulike_activities
			GROUP BY user_ip
			UNION ALL
			SELECT ip AS user_ip, count(ip) AS count_user_ip
			FROM ".$this->wpdb->prefix."ulike_comments
			GROUP BY user_ip
			UNION ALL
			SELECT ip AS user_ip, count(ip) AS count_user_ip
			FROM ".$this->wpdb->prefix."ulike_forums
			GROUP BY user_ip
			) AS T
			GROUP BY get_user_ip
			");
			
			foreach($return_val as $return){
				$country_data[strtolower(getCountryFromIP($return->get_user_ip, "code"))] += $return->get_count_user_ip;
			}
		
			return json_encode($country_data);
		}		
		
		/**
		 * Top Likers Summary
		 *
		 * @author       	Alimir
		 * @since           2.3
		 * @return			Array
		 */					
		public function get_top_likers(){
		$request = "SELECT T.user_id, SUM(T.CountUser) AS SumUser, T.ip
					FROM(
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM ".$this->wpdb->prefix."ulike
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM ".$this->wpdb->prefix."ulike_activities
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM ".$this->wpdb->prefix."ulike_comments
					GROUP BY user_id
					UNION ALL
					SELECT user_id, count(user_id) AS CountUser, ip
					FROM ".$this->wpdb->prefix."ulike_forums
					GROUP BY user_id
					) AS T
					GROUP BY T.user_id
					ORDER BY SumUser DESC LIMIT 10
					";
		return $this->wpdb->get_results($request);
		}
		
	}
	
	//create global variable
	global $wp_ulike_stats;
	$wp_ulike_stats = new wp_ulike_stats();
	
}