<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

if ( ! class_exists( 'wp_ulike' ) ) {

	class wp_ulike{

		private $wpdb, $status, $user_id, $user_ip, $is_distinct, $prev_status;

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
			$this->wpdb         = $wpdb;
			$this->status       = 'like';
			$this->user_ip      = $wp_user_IP;
			$this->current_user = get_current_user_id();
			$this->user_id      = $this->get_reutrn_id();
			$this->is_distinct  = true;
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
			//Select the logging functionality
			switch( $data['logging_method'] ){
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
			$check_user_status = $this->get_user_status( $table, $column, 'ip', $id, $this->user_ip );
			$this->prev_status = !$check_user_status ? $this->status : $check_user_status;
			$this->is_distinct = false;

			if( $type == 'post' ){
				$output = $this->get_template( $data, 1 );
			} elseif( $type == 'process' ){
				$this->update_status( $factor, $this->prev_status, true );
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
				$output = $this->get_ajax_counter_value( $id, $slug );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'          => $id,
						'key'         => $key,
						'user_id'     => $this->user_id,
						'status'      => $this->status,
						'has_log'     => ! $check_user_status ? 0 : 1,
						'slug'        => $slug,
						'table'       => $table,
						'is_distinct' => $this->is_distinct
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
			$this->is_distinct = false;

			// Check user log history
			$check_user_status = $this->get_user_status( $table, $column, 'ip', $id, $this->user_ip );
			$this->prev_status = !$check_user_status ? $this->status : $check_user_status;

			if( $type == 'post' ){

				if( $this->has_permission( $data ) ){
					$output = $this->get_template( $data, 1 );
				}
				else{
					$output = $this->get_template( $data, 4, $this->prev_status );
				}

			} elseif( $type == 'process' ) {

				if( $this->has_permission( $data ) ){
					$this->update_status( $factor, $this->prev_status, true );
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
				$output = $this->get_ajax_counter_value( $id, $slug );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'          => $id,
						'key'         => $key,
						'user_id'     => $this->user_id,
						'status'      => $this->status,
						'has_log'     => ! $check_user_status ? 0 : 1,
						'slug'        => $slug,
						'table'       => $table,
						'is_distinct' => $this->is_distinct
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
			$this->prev_status = $this->get_user_status( $table, $column, $method_col, $id, $method_val );

			$this->is_distinct = true;

			if( $type == 'post' ){
				if( ! $this->prev_status ){
					$output 	= $this->get_template( $data, 1 );
				} else {
					if( substr( $this->prev_status, 0, 2 ) !== "un" ) {
						$output = $this->get_template( $data, 2, $this->prev_status );
					} else {
						$output = $this->get_template( $data, 3, $this->prev_status );
					}
				}

			} elseif( $type == 'process' ) {
				if( ! $this->prev_status ){
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
					$this->update_status( $factor, $this->prev_status );
					$this->update_user_meta_status( $id, $slug, $this->status );
					// Update status
					$this->wpdb->update(
						$this->wpdb->prefix . $table,
						array(
							'status' 	=> $this->status
						),
						array( $column => $id, $method_col => $method_val )
					);
					// $this->wpdb->query( sprintf( '
					// 		UPDATE `%s`
					// 		SET `status` = \'%s\'
					// 		WHERE `%s` = \'%s\'
					// 		AND `%s` = \'%s\'
					// 		ORDER BY id DESC LIMIT 1
					// 	',
					// 	esc_sql( $this->wpdb->prefix . $table ),
					// 	$this->status,
					// 	esc_sql( $column ),
					// 	esc_sql( $id ),
					// 	esc_sql( $method_col ),
					// 	esc_sql( $method_val ),
					// ) );
				}

				// Formatting the output
				$output = $this->get_ajax_counter_value( $id, $slug );
				// After process hook
				do_action_ref_array( 'wp_ulike_after_process',
					array(
						'id'          => $id,
						'key'         => $key,
						'user_id'     => $this->user_id,
						'status'      => $this->status,
						'has_log'     => ! $this->prev_status ? 0 : 1,
						'slug'        => $slug,
						'table'       => $table,
						'is_distinct' => $this->is_distinct
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
		private function get_ajax_counter_value( $id, $slug ){
			$counter_val   = $this->get_counter_value( $id, $slug, $this->status, $this->is_distinct );
			// Update counter value
			$counter_val   = $this->update_counter_value( $id, $counter_val, $slug );
			// Format value
			$formatted_val = wp_ulike_format_number( $counter_val, $this->status );
			return apply_filters( 'wp_ulike_ajax_counter_value', $formatted_val, $id, $slug, $this->status, $this->is_distinct );
		}

		/**
		 * Update counter value in meta table
		 *
		 * @param integer $id
		 * @param string $value
		 * @param string $slug
		 * @return integer
		 */
		private function update_counter_value( $id, $value, $slug ){
			$status  = $this->status;
			$status  = ltrim( $status, 'un');
			$old_val = $value;

			// Update meta value
			$primary_val = wp_ulike_meta_counter_value( $id, $slug, $status, $this->is_distinct );
			if( ! empty( $primary_val ) || is_numeric( $primary_val ) ){
				$value  = strpos( $this->status, 'un') === false ? $value + 1 : $value - 1;
			}
			wp_ulike_update_meta_counter_value( $id, max( $value, 0 ), $slug, $status, $this->is_distinct );

			// Decrease reverse meta value
			if( $this->is_distinct ){
				$reverse_key = strpos( $status, 'dis') === false ? 'dislike' : 'like';
				$reverse_val = wp_ulike_meta_counter_value( $id, $slug, $reverse_key, $this->is_distinct );
				if( ! empty( $reverse_val ) || is_numeric( $reverse_val ) ){
					if( strpos( $this->status, 'un') === false && strpos( $this->prev_status, 'un') === false  ){
						$reverse_val = $reverse_val - 1;
					}
					wp_ulike_update_meta_counter_value( $id, max( $reverse_val, 0 ), $slug, $reverse_key, $this->is_distinct );
				}
			}

			return $value;
		}

		/**
		 * Get counter value
		 *
		 * @param integer $id
		 * @param string $slug
		 * @param string $status
		 * @param bool $is_distinct
		 * @return integer
		 */
		private function get_counter_value( $id, $slug, $status, $is_distinct ){
			$counter_val = wp_ulike_get_counter_value_info( $id, $slug, $status, $is_distinct );
			return ! is_wp_error( $counter_val ) ? $counter_val : 0;
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
		public function has_permission( $args ){
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

			// Get all template callback list
			$temp_list = call_user_func( 'wp_ulike_generate_templates_list' );
			$func_name = isset( $temp_list[ $args['style'] ]['callback'] ) ? $temp_list[ $args['style'] ]['callback'] : 'wp_ulike_set_default_template';

			if( $args['button_type'] == 'image' || ( isset( $temp_list[$args['style']]['is_text_support'] ) && ! $temp_list[$args['style']]['is_text_support'] ) ){
				$button_class_name .= ' wp_ulike_put_image';
 				if( in_array( $status, array( 2, 4 ) ) ){
					$button_class_name .= ' image-unlike wp_ulike_btn_is_active';
				}
			} else {
				$button_class_name .= ' wp_ulike_put_text';
 				if( in_array( $status, array( 2, 4 ) ) && strpos( $user_status, 'dis') !== 0){
					$button_text = wp_ulike_get_button_text( 'unlike', $args['setting'] );
				} else {
					$button_text = wp_ulike_get_button_text( 'like', $args['setting'] );
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

			$total_likes   = $this->get_counter_value( $args['id'], $args['slug'], 'like', $this->is_distinct );
			$formatted_val = apply_filters( 'wp_ulike_count_box_template', '<span class="count-box">'. wp_ulike_format_number( $total_likes ) .'</span>' , $total_likes );
			$args['is_distinct'] = $this->is_distinct;

			$wp_ulike_template 	= apply_filters( 'wp_ulike_add_templates_args', array(
					"ID"               => esc_attr( $args['id'] ),
					"wrapper_class"    => esc_attr( $args['wrapper_class'] ),
					"slug"             => esc_attr( $args['slug'] ),
					"counter"          => $formatted_val,
					"total_likes"      => $total_likes,
					"type"             => esc_attr( $args['method'] ),
					"status"           => esc_attr( $status ),
					"user_status"      => esc_attr( $user_status ),
					"setting"      	   => esc_attr( $args['setting'] ),
					"attributes"       => $args['attributes'],
					"style"            => esc_html( $args['style'] ),
					"button_type"      => esc_html( $args['button_type'] ),
					"display_likers"   => esc_attr( $args['display_likers'] ),
					"disable_pophover" => esc_attr( $args['disable_pophover'] ),
					"button_text"      => $button_text,
					"general_class"    => esc_attr( $general_class_name ),
					"button_class"     => esc_attr( $button_class_name )
				), $args, $temp_list
			);

			$final_template = call_user_func( $func_name, $wp_ulike_template );

			return apply_filters( 'wp_ulike_return_final_templates', preg_replace( '~>\s*\n\s*<~', '><', $final_template ), $wp_ulike_template );

		}

		/**
		 * Update user meta status
		 *
		 * @param integer $id
		 * @param string $slug
		 * @param string $status
		 * @return void
		 */
		public function update_user_meta_status( $id, $slug, $status ){
			// Update object cache (memcached issue)
			$meta_key  = sanitize_key( $slug . '_status' );
			$user_info = wp_ulike_get_meta_data( $this->user_id, 'user', $meta_key, true );

			if( empty( $user_info ) ){
				$user_info = array( $id => $status );
			} else {
				$user_info[$id] = $status;
			}

			wp_ulike_update_meta_data( $this->user_id, 'user', $meta_key, $user_info );
		}

		/**
		 * Get User Status (like/dislike)
		 *
		 * @param           string $table
		 * @param           string $item_type_col
		 * @param           string $item_conditional_col
		 * @param           string $item_type_val
		 * @param           string $item_conditional_val
		 * @return			string
		 */
		public function get_user_status( $table, $item_type_col, $item_conditional_col, $item_type_val, $item_conditional_val ){

			$item_type = wp_ulike_get_type_by_table( $table );
			$meta_key  = sanitize_key( $item_type . '_status' );
			$user_info = wp_ulike_get_meta_data( $this->user_id, 'user', $meta_key, true );

			if( empty( $user_info ) || ! isset( $user_info[$item_type_val] ) ){
				$cache_key   = sanitize_key( sprintf( '%s-%s-user-%s-status', $item_type, $item_type_val, $item_conditional_val ) );
				$user_status = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

				// Make a cachable query to get user status
				if( false === $user_status ){
					// Create query string
					$query  = sprintf( '
							SELECT `status`
							FROM %s
							WHERE `%s` = \'%s\'
							AND `%s` = \'%s\'
							ORDER BY id DESC LIMIT 1
						',
						esc_sql( $this->wpdb->prefix . $table ),
						esc_sql( $item_conditional_col ),
						esc_sql( $item_conditional_val ),
						esc_sql( $item_type_col ),
						esc_sql( $item_type_val )
					);

					// Get results
					$user_status = $this->wpdb->get_var( stripslashes( $query ) );
					// Check user info value
					$user_info = empty( $user_info ) ? array() : $user_info;

					if( $user_status !== NULL || $this->current_user ){
						$user_info[$item_type_val] = $this->current_user && $user_status === NULL ? NULL : $user_status;
						wp_ulike_update_meta_data( $this->user_id, 'user', $meta_key, $user_info );
					}

					wp_cache_set( $cache_key, $user_status, WP_ULIKE_SLUG, 300 );
				}

			} elseif( empty( $user_info[$item_type_val] ) ) {
				return false;
			}

			return isset( $user_info[ $item_type_val ] ) ? $user_info[ $item_type_val ] : false;
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
		 * Get current user ID
		 *
		 * @return void
		 */
		public function get_reutrn_id(){
			return ! $this->current_user ? wp_ulike_generate_user_id( $this->user_ip ) : $this->current_user;
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