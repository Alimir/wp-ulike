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
		 * Constructor
		 */
		function __construct( $atts = array() ){
			global $wpdb;

			// Defining default attributes
			$default_atts = array(
				'user_id' => NULL,
				'user_ip' => NULL
			);
			$parsed_args = wp_parse_args( $atts, $default_atts );

			$this->wpdb = $wpdb;
			self::setCurrentIP( $parsed_args['user_ip'] );
			self::setIsUserLoggedIn( $parsed_args['user_id'] );
			self::setCurrentUser( $parsed_args['user_id'] );
		}

		/**
		 * Set current user IP
		 *
		 * @return void
		 */
		private static function setCurrentIP( $user_ip ){
			self::$currentIP = $user_ip === NULL ? wp_ulike_get_user_ip() : $user_ip;
		}

		/**
		 * Set is user logged in status
		 *
		 * @return void
		 */
		private static function setIsUserLoggedIn( $user_id ){
			self::$isUserLoggedIn = $user_id === NULL ? is_user_logged_in() : true;
		}

		/**
		 * Set current user ID
		 *
		 * @return void
		 */
		private static function setCurrentUser( $user_id ){
			if( $user_id === NULL ){
				self::$currentUser = self::$isUserLoggedIn ? get_current_user_id() : wp_ulike_generate_user_id( self::$currentIP );
			} else {
				self::$currentUser = $user_id;
			}
		}

		/**
		 * Get current user id
		 *
		 * @return string
		 */
		public function getCurrentUser(){
			return self::$currentUser;
		}

		/**
		 * Get user previous status
		 *
		 * @return string
		 */
		public function getPrevStatus(){
			return self::$prevStatus;
		}

		/**
		 * Get user current status
		 *
		 * @return string
		 */
		public function getCurrentStatus(){
			return self::$currentStatus;
		}

		/**
		 * Update current status
		 *
		 * @param string $factor
		 * @param boolean $keep_status
		 * @return void
		 */
		public static function updateStatus( $factor = 'up', $keep_status = false ){
			if( $factor === 'down' ){
				self::$currentStatus = self::$prevStatus !== 'dislike' || $keep_status ? 'dislike' : 'undislike';
			} else {
				self::$currentStatus = self::$prevStatus !== 'like' || $keep_status ? 'like' : 'unlike';
			}
		}

		/**
		 * Set user previous status
		 *
		 * @param int $item_type
		 * @param string $item_id
		 * @param string $table
		 * @param string $column
		 * @return void
		 */
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

		/**
		 * Check permission access
		 *
		 * @param array $args
		 * @return boolean
		 */
		public static function hasPermission( $args ){
			switch ( $args['method'] ) {
				case 'by_cookie':
					return ! isset( $_COOKIE[ $args['type'] . $args['id'] ] );

				default:
					return true;
			}
		}

		/**
		 * Check distinct status by logging method
		 *
		 * @param string $method
		 * @return boolean
		 */
		public static function isDistinct( $method ){
			switch ( $method ) {
				case 'by_cookie':
				case 'do_not_log':
					return false;

				default:
					return true;
			}
		}

		/**
		 * Get data args
		 *
		 * @param string $type
		 * @return array
		 */
		public static function getDataArgs( $type ){
			return wp_ulike_get_post_settings_by_type( $type );
		}

		/**
		 * Inset log data
		 *
		 * @param integer $item_id
		 * @param string $table
		 * @param string $column
		 * @return void
		 */
		public function insertData( $item_id, $table, $column ){
			$this->wpdb->insert(
				$this->wpdb->prefix . $table,
				array(
					$column     => esc_sql( $item_id ),
					'date_time' => current_time( 'mysql' ),
					'ip'        => $this->maybeAnonymiseIp( self::$currentIP ),
					'user_id'   => esc_sql( self::$currentUser ),
					'status'    => esc_sql( self::$currentStatus )
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
		}

		/**
		 * Anonymise IP address if option enabled.
		 *
		 * @param string $ip
		 * @return string
		 */
		private function maybeAnonymiseIp( $ip ){
			// Check anonymise enable
			if( wp_ulike_get_option( 'enable_anonymise_ip' ) ){
				if ( strpos( $ip, "." ) == true ) {
					$ip = preg_replace('~[0-9]+$~', '0', $ip );
				} else {
					$ip = preg_replace('~[0-9]*:[0-9]+$~', '0000:0000', $ip );
				}
			}

			return esc_sql( $ip );
		}

		/**
		 * Update log data
		 *
		 * @param integer $item_id
		 * @param string $table
		 * @param string $column
		 * @return void
		 */
		public function updateData( $item_id, $table, $column ){
			$this->wpdb->update(
				$this->wpdb->prefix . $table,
				array(
					'status' => esc_sql( self::$currentStatus )
				),
				array( $column => $item_id, 'user_id' => self::$currentUser )
			);
		}

		/**
		 * Update and return counter value
		 *
		 * @param integer $item_id
		 * @param string $item_type
		 * @param boolean $is_distinct
		 * @return integer
		 */
		public function updateCounterValue( $item_id, $item_type, $is_distinct ){
			// Get current value
			$value = wp_ulike_get_counter_value( $item_id, $item_type, self::$currentStatus, $is_distinct );

			// Remove 'un' prefix from status.
			$status  = ltrim( self::$currentStatus, 'un');

			// Update meta value
			if( ! empty( $value ) || is_numeric( $value ) ){
				$value  = strpos( self::$currentStatus, 'un') === false ? $value + 1 : $value - 1;
			}
			wp_ulike_update_meta_counter_value( $item_id, max( $value, 0 ), $item_type, $status, $is_distinct );

			// Decrease reverse meta value
			if( $is_distinct ){
				$reverse_key = strpos( $status, 'dis') === false ? 'dislike' : 'like';
				$reverse_val = wp_ulike_meta_counter_value( $item_id, $item_type, $reverse_key, $is_distinct );
				if( ! empty( $reverse_val ) || is_numeric( $reverse_val ) ){
					if( strpos( self::$currentStatus, 'un') === false && strpos( self::getPrevStatus(), 'un') === false  ){
						wp_ulike_update_meta_counter_value( $item_id, max( $reverse_val - 1, 0 ), $item_type, $reverse_key, $is_distinct );
					}
				}
			}

			return $value;
		}

		/**
		 * Update user meta status
		 *
		 * @param integer $item_id
		 * @param string $item_type
		 * @return void
		 */
		public function updateUserMetaStatus( $item_id, $item_type ){
			// Update object cache (memcached issue)
			$meta_key  = sanitize_key( $item_type . '_status' );
			$user_info = wp_ulike_get_meta_data( self::$currentUser, 'user', $meta_key, true );

			if( empty( $user_info ) ){
				$user_info = array( $item_id => self::$currentStatus );
			} else {
				$user_info[$item_id] = self::$currentStatus;
			}

			// Update meta value
			wp_ulike_update_meta_data( self::$currentUser, 'user', $meta_key, $user_info );
		}

		/**
		 * Update user meta status
		 *
		 * @param integer $item_id
		 * @param string $item_type
		 * @param string $table
		 * @param boolean $is_distinct
		 * @return void
		 */
		public function updateMetaData( $item_id, $item_type, $table, $is_distinct ){
			// Update total stats
			if( ( ! self::$prevStatus || ! $is_distinct ) && strpos( self::$currentStatus, 'un') === false ){
				// update all logs period
				$this->wpdb->query( "
						UPDATE `{$this->wpdb->prefix}ulike_meta`
						SET `meta_value` = (`meta_value` + 1)
						WHERE `meta_group` = 'statistics' AND `meta_key` = 'count_logs_period_all'
				" );
				$this->wpdb->query( "
						UPDATE `{$this->wpdb->prefix}ulike_meta`
						SET `meta_value` = (`meta_value` + 1)
						WHERE `meta_group` = 'statistics' AND `meta_key` = 'count_logs_for_{$table}_table_in_all_daterange'
				" );
			}

			// Update likers list
			$get_likers = wp_ulike_get_meta_data( $item_id, $item_type, 'likers_list', true );
			if( ! empty( $get_likers ) ){
				$get_user   = get_userdata( self::$currentUser );
				$is_updated = false;
				if( $get_user ){
					if( in_array( $get_user->ID, $get_likers ) ){
						if( strpos( self::$currentStatus, 'un') !== false ){
							$get_likers = array_diff( $get_likers, array( $get_user->ID ) );
							$is_updated = true;
						}
					} else {
						if( strpos( self::$currentStatus, 'un') === false ){
							array_push( $get_likers, $get_user->ID );
							$is_updated = true;
						}
					}
					// If array list has been changed, then update meta data.
					if( $is_updated ){
						wp_ulike_update_meta_data( $item_id, $item_type, 'likers_list', $get_likers );
					}
				}
			}

			// Delete object cache
			if( wp_ulike_is_cache_exist() ){
				wp_cache_delete( 'calculate_new_votes', WP_ULIKE_SLUG );
				wp_cache_delete( 'count_logs_period_all', WP_ULIKE_SLUG );
				wp_cache_delete( 1, 'wp_ulike_statistics_meta' );
			}
		}

	}

}