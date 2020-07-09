<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_entities_process' ) ) {

	class wp_ulike_entities_process {

		private $wpdb;
		public static $currentStatus = 'like';
		public static $isUserLoggedIn;
		public static $prevStatus;
		public static $currentIP;
		public static $currentUser;

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

			$this->wpdb = $wpdb;
			self::setCurrentIP();
			self::setIsUserLoggedIn();
			self::setCurrentUser();
		}

		private static function setCurrentIP(){
			self::$currentIP = wp_ulike_get_user_ip();
		}

		private static function setIsUserLoggedIn(){
			self::$isUserLoggedIn = is_user_logged_in();
		}

		private static function setCurrentUser(){
			self::$currentUser = self::$isUserLoggedIn ? get_current_user_id() : wp_ulike_generate_user_id( self::$currentIP );
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
			// Remove 'un' prefix from status.
			$status  = ltrim( $this->status, 'un');

			// Update meta value
			if( ! empty( $value ) || is_numeric( $value ) ){
				$value  = strpos( $this->status, 'un') === false ? $value + 1 : $value - 1;
			}
			wp_ulike_update_meta_counter_value( $id, max( $value, 0 ), $slug, $status, $this->is_distinct );

			// Decrease reverse meta value
			if( $this->is_distinct ){
				$reverse_key = strpos( $status, 'dis') === false ? 'dislike' : 'like';
				$reverse_val = wp_ulike_meta_counter_value( $id, $slug, $reverse_key, $this->is_distinct );
				if( ! empty( $reverse_val ) || is_numeric( $reverse_val ) ){
					if( strpos( $this->status, 'un') === false && strpos( $this->prev_status, 'un') === false  ){
						wp_ulike_update_meta_counter_value( $id, max( $reverse_val - 1, 0 ), $slug, $reverse_key, $this->is_distinct );
					}
				}
			}

			return $value;
		}

		public static function updateStatus( $factor = 'up', $keep_status = false ){
			if( $factor === 'down' ){
				self::$currentStatus = self::$prevStatus !== 'dislike' || $keep_status ? 'dislike' : 'undislike';
			} else {
				self::$currentStatus = self::$prevStatus !== 'like' || $keep_status ? 'like' : 'unlike';
			}
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

			// Update meta value
			wp_ulike_update_meta_data( $this->user_id, 'user', $meta_key, $user_info );
		}


		public function setPrevStatus( $item_type, $item_id, $table, $column ){
			$meta_key  = sanitize_key( $item_type . '_status' );
			$user_info = wp_ulike_get_meta_data( self::$currentUser, 'user', $meta_key, true );

			if( empty( $user_info ) || ! isset( $user_info[$item_id] ) ){
				$query  = sprintf( '
						SELECT `status`
						FROM %s
						WHERE `%s` = \'%s\'
						AND `user_id` = \'%s\'
						ORDER BY id DESC LIMIT 1
					',
					esc_sql( $this->wpdb->prefix . $table ),
					esc_sql( $column ),
					esc_sql( $item_id ),
					esc_sql( self::$currentUser ),
				);

				// Get results
				$user_status = $this->wpdb->get_var( stripslashes( $query ) );

				// Check user info value
				$user_info = empty( $user_info ) ? array() : $user_info;

				if( $user_status !== NULL || self::$isUserLoggedIn ){
					$user_info[$item_id] =  self::$isUserLoggedIn && $user_status === NULL ? NULL : $user_status;
					wp_ulike_update_meta_data( self::$currentUser, 'user', $meta_key, $user_info );
				}
			} elseif( empty( $user_info[$item_id] ) ) {
				self::$prevStatus = false;
				return;
			}

			self::$prevStatus = isset( $user_info[ $item_id ] ) ? $user_info[ $item_id ] : NULL;
		}

		public function getPrevStatus(){
			return self::$prevStatus;
		}

		public static function hasPermission( $args ){
			switch ( $args['method'] ) {
				case 'by_cookie':
					return ! isset( $_COOKIE[ $args['type'] . $args['id'] ] );

				default:
					return true;
			}
		}

		public static function isDistinct( $method ){
			switch ( $method ) {
				case 'by_cookie':
				case 'do_not_log':
					return false;

				default:
					return true;
			}
		}

		public static function getDataArgs( $type ){
			return wp_ulike_get_post_settings_by_type( $type );
		}

		public function insertData( $item_id, $table, $column ){
			$this->wpdb->insert(
				$this->wpdb->prefix . $table,
				array(
					$column     => $item_id,
					'date_time' => current_time( 'mysql' ),
					'ip'        => self::$currentIP,
					'user_id'   => self::$currentUser,
					'status'    => self::$currentStatus
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
		}

		public function updateData( $item_id, $table, $column ){
			$this->wpdb->update(
				$this->wpdb->prefix . $table,
				array(
					'status' 	=> self::$currentStatus
				),
				array( $column => $item_id, 'user_id' => self::$currentUser )
			);
		}


		public function updateCounterValue( $item_id, $item_type, $item_status, $is_distinct ){
			// Get current value
			$value = wp_ulike_get_counter_value( $item_id, $item_type, $item_status, $is_distinct );

			// Remove 'un' prefix from status.
			$status  = ltrim( $item_status, 'un');

			// Update meta value
			if( ! empty( $value ) || is_numeric( $value ) ){
				$value  = strpos( $item_status, 'un') === false ? $value + 1 : $value - 1;
			}
			wp_ulike_update_meta_counter_value( $item_id, max( $value, 0 ), $item_type, $status, $is_distinct );

			// Decrease reverse meta value
			if( $is_distinct ){
				$reverse_key = strpos( $status, 'dis') === false ? 'dislike' : 'like';
				$reverse_val = wp_ulike_meta_counter_value( $item_id, $item_type, $reverse_key, $is_distinct );
				if( ! empty( $reverse_val ) || is_numeric( $reverse_val ) ){
					if( strpos( $item_status, 'un') === false && strpos( self::getPrevStatus(), 'un') === false  ){
						wp_ulike_update_meta_counter_value( $item_id, max( $reverse_val - 1, 0 ), $item_type, $reverse_key, $is_distinct );
					}
				}
			}

			return $value;
		}

		public function updateUserMetaStatus( $item_id, $item_type, $item_status ){
			// Update object cache (memcached issue)
			$meta_key  = sanitize_key( $item_type . '_status' );
			$user_info = wp_ulike_get_meta_data( self::$currentUser, 'user', $meta_key, true );

			if( empty( $user_info ) ){
				$user_info = array( $item_id => $item_status );
			} else {
				$user_info[$item_id] = $item_status;
			}

			// Update meta value
			wp_ulike_update_meta_data( self::$currentUser, 'user', $meta_key, $user_info );
		}

	}

}