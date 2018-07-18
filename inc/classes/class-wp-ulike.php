<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

if ( ! class_exists( 'wp_ulike' ) ) {

	class wp_ulike{

		private $wpdb, $status, $user_id, $user_ip;

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
			$this->wpdb    = $wpdb;
			$this->status  = 'like';
			$this->user_ip = $wp_user_IP;
			$this->user_id = $this->get_reutrn_id();
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
			$loggin_method = wp_ulike_get_setting( $data['setting'], 'logging_method');

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
					return $this->loggedby_other_methods( $data );
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

			if( $type == 'post' ){
				$output = $this->get_template( $data, 1 );

			} elseif( $type == 'process' ){
				// Increment like counter
				++$get_like;
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
				$output = wp_ulike_format_number( $this->update_meta_data( $id, $key, $get_like ) );
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

			if( $type == 'post' ){

				if( ! isset( $_COOKIE[ $cookie . $id ] ) ){
					$output = $this->get_template( $data, 1 );
				}
				else{
					$output = $this->get_template( $data, 4 );
				}

			} elseif( $type == 'process' ) {

				if( ! isset( $_COOKIE[ $cookie . $id ] ) ){
					// Increment like counter
					++$get_like;
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
				$output = wp_ulike_format_number( $this->update_meta_data( $id, $key, $get_like ) );
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
			$output     = '';
			// method column value
			$method_val = $method_col === 'ip' ? $this->user_ip : $this->user_id;
			// Check user log history
			$has_log    = $this->get_user_status( $table, $column, $method_col, $id, $method_val );

			if( $type == 'post' ){

				if( empty( $has_log ) ){
					$output 	= $this->get_template( $data, 3 );

				} else {

					if( $has_log  == "like" ) {
						$output = $this->get_template( $data, 2 );

					} else {
						$output = $this->get_template( $data, 3 );
					}

				}

			} elseif( $type == 'process' ) {

				if( empty( $has_log ) ){
					// Increment like counter
					++$get_like;
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

					if( $has_log == "like" ) {
						// Decrement like counter
						--$get_like;
						$this->status = 'unlike';
					} else {
						// Increment like counter
						++$get_like;
					}
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
				$output = wp_ulike_format_number( $this->update_meta_data( $id, $key, $get_like ) );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'      => $id,
						'key'     => $key,
						'user_id' => $this->user_id,
						'status'  => $this->status,
						'has_log' => empty( $has_log ) ? 0 : 1
					)
				);

			}

			return $output;
		}

		/**
		 * Update meta data
		 *
		 * @author       	Alimir
		 * @param           Integer $id
		 * @param           String $key
		 * @param           Integer $data
		 * @since           2.0
		 * @return			Void
		 */
		public function update_meta_data( $id, $key, $data ){
			// Update Values
			switch ( $key ) {
				case '_liked'		 :
					update_post_meta( $id, $key, $data );
					update_postmeta_cache( array( $id ) );
					delete_transient( 'wp_ulike_get_most_liked_posts' );
					break;
				case '_topicliked'	 :
					update_post_meta( $id, $key, $data );
					update_postmeta_cache( array( $id ) );
					delete_transient( 'wp_ulike_get_most_liked_topics' );
					break;
				case '_commentliked' :
					update_comment_meta( $id, $key, $data );
					update_meta_cache( 'comment', array( $id ) );
					delete_transient( 'wp_ulike_get_most_liked_comments' );
					break;
				case '_activityliked':
					bp_activity_update_meta( $id, $key, $data );
					delete_transient( 'wp_ulike_get_most_liked_activities' );
					break;
			}

			return $data;
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
		public function get_template( array $args, $status ){

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
				if($status == 2){
					$button_text = html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text_u' ) );
				} else {
					$button_text = html_entity_decode( wp_ulike_get_setting( 'wp_ulike_general', 'button_text' ) );
				}
			}

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

			$counter = apply_filters( 'wp_ulike_count_box_template', '<span class="count-box">'. wp_ulike_format_number( $args['get_like'] ) .'</span>' , $args['get_like'] );

			$wp_ulike_template 	= apply_filters( 'wp_ulike_add_templates_args', array(
					"ID"             => $args['id'],
					"wrapper_class"  => $args['wrapper_class'],
					"slug"           => $args['slug'],
					"counter"        => $counter,
					"type"           => $args['method'],
					"status"         => $status,
					"attributes"     => $args['attributes'],
					"style"          => $args['style'],
					"button_type"    => $args['button_type'],
					"button_text"    => $button_text,
					"general_class"  => $general_class_name,
					"button_class"   => $button_class_name
				)
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
			// This will return like|unlike
			return $this->wpdb->get_var( "
				SELECT status
				FROM ".$this->wpdb->prefix."$table
				WHERE $first_column = '$first_val'
				AND $second_column = '$second_val'
			" );
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
			extract( $args );
			// Check the user's likes history
			return $this->wpdb->get_var( "
				SELECT COUNT(*)
				FROM ".$this->wpdb->prefix.$table."
				WHERE $column = '$id'
				AND ( user_id = ".$this->user_id." OR ip = ".$this->user_ip." )
			" );
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