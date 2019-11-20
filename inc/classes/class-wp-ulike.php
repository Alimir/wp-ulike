<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

if ( ! class_exists( 'wp_ulike' ) ) {

	class wp_ulike{

		private $wpdb, $status, $user_id, $user_ip, $is_distinct;

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
			// Init core
			add_action( 'wp_ulike_loaded', array( $this, 'init' ) );
		}

		/**
		 * Init function when plugin loaded
		 *
		 * @author          Alimir
		 * @since           3.0
		 * @return          Void
		 */
		public function init(){
			global $wpdb, $wp_user_IP;
			$this->wpdb        = $wpdb;
			$this->status      = 'like';
			$this->user_ip     = $wp_user_IP;
			$this->user_id     = $this->get_reutrn_id();
			$this->is_distinct = true;
		}

		/**
		 * Select the logging type
		 *
		 * @author       	Alimir
		 * @param           Array $data
		 * @since           2.0
		 * @return			String
		 */
		public function wp_get_ulike( array $data ){
			//get loggin method option
			$loggin_method = wp_ulike_get_setting( $data['setting'], 'logging_method' );

			//Select the logging functionality
			switch( $loggin_method ){
				case 'do_not_log':
					return $this->do_not_log_method( $data );
					break;
				case 'by_cookie':
					return $this->loggedby_cookie_method( $data );
					break;
				case 'by_ip':
					return $this->loggedby_other_methods( $data, 'ip' );
					break;
				default:
					return $this->loggedby_other_methods( $data, 'user_id' );
			}
		}

		/**
		 * Do not log method
		 *
		 * @author       	Alimir
		 * @param           Array 	$data
		 * @param           String 	$output
		 * @since           2.0
		 * @return			String
		 */
		public function do_not_log_method( array $data ){
			// Extract data
			extract( $data );
			// output value
			$output = '';

			// Check user log history
			$user_status = $this->get_user_status( $table, $column, 'ip', $id, $this->user_ip );
			$user_status = !$user_status ? $this->status : $user_status;
			$this->is_distinct = false;

			if( $type == 'post' ){
				$output = $this->get_template( $data, 1 );
			} elseif( $type == 'process' ){
				$this->update_status( $factor, $user_status, true );
				// Insert log data
				$this->wpdb->insert(
					$this->wpdb->prefix . $table,
					array(
						$column     => $id,
						'date_time' => current_time( 'mysql' ),
						'ip'        => $this->user_ip,
						'user_id'   => $this->user_id,
						'status'    => $this->status
					),
					array( '%d', '%s', '%s', '%s', '%s' )
				);
				// Formatting the output
				$output = $this->get_counter_value( $id, $slug );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'      => $id,
						'key'     => $key,
						'user_id' => $this->user_id,
						'status'  => $this->status,
						'has_log' => $this->has_log( $data )
					)
				);

			}

			return $output;
		}

		/**
		 * Logged by cookie method
		 *
		 * @author       	Alimir
		 * @param           Array 	$data
		 * @param           String 	$output
		 * @since           2.0
		 * @return			String
		 */
		public function loggedby_cookie_method( array $data ){
			// Extract data
			extract( $data );
			// output value
			$output = '';

			// Check user log history
			$user_status = $this->get_user_status( $table, $column, 'ip', $id, $this->user_ip );
			$user_status = !$user_status ? $this->status : $user_status;

			if( $type == 'post' ){

				if( $this->has_permission( $data, 'by_cookie' ) ){
					$output = $this->get_template( $data, 1 );
				}
				else{
					$output = $this->get_template( $data, 4 );
				}

			} elseif( $type == 'process' ) {

				if( $this->has_permission( $data, 'by_cookie' ) ){
					$this->update_status( $factor, $user_status, true );
					// Set cookie
					setcookie( $cookie . $id, time(), 2147483647, '/' );
					// Insert log data
					$this->wpdb->insert(
						$this->wpdb->prefix . $table,
						array(
							$column     => $id,
							'date_time' => current_time( 'mysql' ),
							'ip'        => $this->user_ip,
							'user_id'   => $this->user_id,
							'status'    => $this->status
						),
						array( '%d', '%s', '%s', '%s', '%s' )
					);

				}
				// Formatting the output
				$output = $this->get_counter_value( $id, $slug );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'      => $id,
						'key'     => $key,
						'user_id' => $this->user_id,
						'status'  => $this->status,
						'has_log' => $this->has_log( $data )
					)
				);

			}

			return $output;
		}

		/**
		 * Logged by IP/UserName method
		 *
		 * @author       	Alimir
		 * @param           Array 	$data
		 * @param           String 	$method_col
		 * @since           2.0
		 * @return			String
		 */
		public function loggedby_other_methods( array $data, $method_col = 'user_id' ){
			// Extract data
			extract( $data );
			// Check the user's likes history
			$output      = '';
			// method column value
			$method_val  = $method_col === 'ip' ? $this->user_ip : $this->user_id;
			// Check user log history
			$user_status = $this->get_user_status( $table, $column, $method_col, $id, $method_val );

			if( $type == 'post' ){
				if( ! $user_status ){
					$output 	= $this->get_template( $data, 1 );
				} else {
					if( substr( $user_status, 0, 2 ) !== "un" ) {
						$output = $this->get_template( $data, 2, $user_status );
					} else {
						$output = $this->get_template( $data, 3, $user_status );
					}
				}

			} elseif( $type == 'process' ) {
				if( ! $user_status ){
					$this->update_status( $factor, 'unlike' );
					// Insert log data
					$this->wpdb->insert(
						$this->wpdb->prefix . $table,
						array(
							$column     => $id,
							'date_time' => current_time( 'mysql' ),
							'ip'        => $this->user_ip,
							'user_id'   => $this->user_id,
							'status'    => $this->status
						),
						array( '%d', '%s', '%s', '%s', '%s' )
					);

				} else {
					$this->update_status( $factor, $user_status );
					// Update status
					$this->wpdb->update(
						$this->wpdb->prefix . $table,
						array(
							'status' 	=> $this->status
						),
						array( $column => $id, $method_col => $method_val )
					);
				}

				// Formatting the output
				$output = $this->get_counter_value( $id, $slug );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'      => $id,
						'key'     => $key,
						'user_id' => $this->user_id,
						'status'  => $this->status,
						'has_log' => ! $user_status ? 0 : 1
					)
				);

			}

			return $output;
		}

		/**
		 * Get counter value for ajax responses
		 *
		 * @param integer $id
		 * @param string $slug
		 * @return integer
		 */
		private function get_counter_value( $id, $slug ){
			$counter = wp_ulike_format_number( wp_ulike_get_counter_value( $id, $slug, $this->status, $this->is_distinct ), $this->status );
			return apply_filters( 'wp_ulike_ajax_counter_value', $counter, $id, $slug, $this->status, $this->is_distinct );
		}

		/**
		 * Update user status base on database changes
		 *
		 * @param string $factor
		 * @param string $old_status
		 * @param boolean $keep_status
		 * @return void
		 */
		private function update_status( $factor = 'up', $old_status = 'like', $keep_status = false ){
			if( $factor === 'down' ){
				$this->status = $old_status !== 'dislike' || $keep_status ? 'dislike' : 'undislike';
			} else {
				$this->status = $old_status !== 'like' || $keep_status ? 'like' : 'unlike';
			}
		}

		/**
		 * Check user permission by logging methods
		 *
		 * @param array $args
		 * @param string $method
		 * @return boolean
		 */
		public function has_permission( $args, $logging_method ){
			// Extract data
			extract( $args );

			switch ( $logging_method ) {
				case 'by_cookie':
					return ! isset( $_COOKIE[ $cookie . $id ] );

				default:
					return true;
			}
		}

		/**
		 * Get user status code
		 *
		 * @return integer ( 0 = Is not logged, 1 = Is not liked, 2 = Is liked in the past, 3 = Is unliked, 4 = Is already liked )
		 */
		public function get_status(){
			if( ! $this->status ){
				return 1;
			} elseif( ! $this->is_distinct ){
				return 4;
			} elseif( strpos( $this->status, 'un') === 0 ){
				return 2;
			} else {
				return 3;
			}
		}

		/**
		 * Get template
		 *
		 * @author       	Alimir
		 * @param           Array $args
		 * @param           Integer $status ( 0 = Is not logged, 1 = Is not liked, 2 = Is liked in the past, 3 = Is unliked, 4 = Is already liked )
		 * @since           2.0
		 * @return			String
		 */
		public function get_template( array $args, $status, $user_status = 'like' ){

			//Primary button class name
			$button_class_name	= str_replace( ".", "", apply_filters( 'wp_ulike_button_selector', 'wp_ulike_btn' ) );
			//Button text value
			$button_text		= '';

			if( $args['button_type'] == 'image' ){
				$button_class_name .= ' wp_ulike_put_image';
				if($status == 2){
					$button_class_name .= ' image-unlike';
				}
			} else {
				$button_class_name .= ' wp_ulike_put_text';
				if($status == 2 && strpos( $user_status, 'dis') !== 0){
					$button_text = wp_ulike_get_button_text( 'button_text_u' );
				} else {
					$button_text = wp_ulike_get_button_text( 'button_text' );
				}
			}
			// Add unique class name for each button
			$button_class_name .= strtolower( ' wp_' . $args['method'] . '_' . $args['id'] );

			$general_class_name	= str_replace( ".", "", apply_filters( 'wp_ulike_general_selector', 'wp_ulike_general_class' ) );

			switch ($status){
				case 0:
					$general_class_name .= ' wp_ulike_is_not_logged';
					break;
				case 1:
					$general_class_name .= ' wp_ulike_is_not_liked';
					break;
				case 2:
					$general_class_name .= ' wp_ulike_is_liked';
					break;
				case 3:
					$general_class_name .= ' wp_ulike_is_unliked';
					break;
				case 4:
					$general_class_name .= ' wp_ulike_is_already_liked';
			}

			$total_likes = wp_ulike_get_counter_value( $args['id'], $args['slug'], 'like', $this->is_distinct );
			$counter = apply_filters( 'wp_ulike_count_box_template', '<span class="count-box">'. wp_ulike_format_number( $total_likes ) .'</span>' , $total_likes );
			$args['is_distinct'] = $this->is_distinct;

			$wp_ulike_template 	= apply_filters( 'wp_ulike_add_templates_args', array(
					"ID"               => esc_attr( $args['id'] ),
					"wrapper_class"    => esc_attr( $args['wrapper_class'] ),
					"slug"             => esc_attr( $args['slug'] ),
					"counter"          => $counter,
					"total_likes"      => $total_likes,
					"type"             => esc_attr( $args['method'] ),
					"status"           => esc_attr( $status ),
					"user_status"      => esc_attr( $user_status ),
					"attributes"       => esc_attr( $args['attributes'] ),
					"style"            => esc_html( $args['style'] ),
					"button_type"      => esc_html( $args['button_type'] ),
					"display_likers"   => esc_attr( $args['display_likers'] ),
					"disable_pophover" => esc_attr( $args['disable_pophover'] ),
					"button_text"      => $button_text,
					"general_class"    => esc_attr( $general_class_name ),
					"button_class"     => esc_attr( $button_class_name )
				), $args
			);


			$wp_ulike_callback = call_user_func( 'wp_ulike_generate_templates_list' );

			$output			= '';

			foreach( $wp_ulike_callback as $key => $value ){
			   if ( $key === $args['style'] ) {
				   $output = call_user_func( $value['callback'], $wp_ulike_template );
				   break;
			   }
			}

			return apply_filters( 'wp_ulike_return_final_templates', preg_replace( '~>\s*\n\s*<~', '><', $output ), $wp_ulike_template );

		}

		/**
		 * Get User Status (like/dislike)
		 *
		 * @author       	Alimir
		 * @param           String $table
		 * @param           String $first_column
		 * @param           String $second_column
		 * @param           String $first_val
		 * @param           String $second_val
		 * @since           2.0
		 * @return			String
		 */
		public function get_user_status( $table, $first_column, $second_column, $first_val, $second_val ){

			// Check the user's likes history
			$query  = sprintf( "
					SELECT `status`
					FROM %s
					WHERE `%s` = '%s'
					AND `%s` = '%s'",
					esc_sql( $this->wpdb->prefix . $table ),
					esc_sql( $first_column ),
					esc_sql( $first_val ),
					esc_sql( $second_column ),
					esc_sql( $second_val )
				);

			$result = $this->wpdb->get_var( $query );

			return empty( $result ) ? false : $result;
		}

		/**
		 * Get Current User Likes List
		 *
		 * @author       	Alimir
		 * @param           Array $args
		 * @since           2.3
		 * @return			Array
		 */
		public function has_log( array $args ){
			// Extract args
			extract( $args );

			// Check the user's likes history
			$query  = sprintf( "
					SELECT COUNT(*)
					FROM %s
					WHERE `%s` = %s
					AND ( `user_id` = '%s' OR `ip` = '%s' )",
					esc_sql( $this->wpdb->prefix . $table ),
					esc_sql( $column ),
					esc_sql( $id ),
					esc_sql( $this->user_id ),
					esc_sql( $this->user_ip )
				);

			$result = $this->wpdb->get_var( $query );

			return empty( $result ) ? 0 : $result;
		}

		/**
		 * Get Current User Likes List
		 *
		 * @author       	Alimir
		 * @param           Array $args
		 * @since           2.3
		 * @return			Array
		 */
		public function get_current_user_likes( array $args ){
			extract( $args );
			// Get user likes
			return $this->wpdb->get_results( "
				SELECT $col, date_time
				FROM ".$this->wpdb->prefix.$table."
				WHERE user_id = ".$this->user_id."
				AND status = 'like'
				ORDER BY date_time
				DESC
				LIMIT $limit
			" );
		}

		/**
		 * Return user ID
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			String
		 */
		public function get_reutrn_id(){

			if( ! ( $user_ID = get_current_user_id() ) ){
				return wp_ulike_generate_user_id( $this->user_ip );
			} else {
				return $user_ID;
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