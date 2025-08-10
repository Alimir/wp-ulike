<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'wp_ulike_entities_process' ) ) {

	class wp_ulike_entities_process {

		protected $wpdb;
		protected $currentStatus = 'like';
		protected $isUserLoggedIn;
		protected $prevStatus;
		protected $currentIP;
		protected $currentFingerPrint;
		protected $currentUser;
		protected $typeSettings;
		protected $itemType;
		protected $itemMethod;

		/**
		 * Constructor
		 */
		function __construct( $atts = array() ){
			global $wpdb;

			// Defining default attributes
			$default_atts = array(
				'user_id'     => NULL,
				'user_ip'     => NULL,
				'item_type'   => 'post',
				'item_method' => 'by_username'
			);
			$parsed_args = wp_parse_args( $atts, $default_atts );

			$this->wpdb = $wpdb;
			$this->setItemType( $parsed_args['item_type'] );
			$this->setCurrentIP( $parsed_args['user_ip'] );
			$this->setIsUserLoggedIn( $parsed_args['user_id'] );
			$this->setCurrentUser( $parsed_args['user_id'] );
			$this->setItemMethod( $parsed_args['item_method'] );
			$this->setCurrentFingerPrint();

			// Set type settings
			$this->setTypeSettings();
		}

		/**
		 * Set current user IP
		 *
		 * @return void
		 */
		protected function setCurrentIP( $user_ip ){
			$this->currentIP = $user_ip === NULL ? wp_ulike_get_user_ip() : $user_ip;
		}

		/**
		 * Set current user IP
		 *
		 * @return void
		 */
		protected function setCurrentFingerPrint(){
			$this->currentFingerPrint = wp_ulike_generate_fingerprint();
		}

		/**
		 * Set current user IP
		 *
		 * @return void
		 */
		protected function setItemType( $item_type ){
			$this->itemType = $item_type;
		}

		/**
		 * Set current user IP
		 *
		 * @return void
		 */
		protected function setItemMethod( $item_method ){
			$this->itemMethod = $item_method;
		}

		/**
		 * Set current user IP
		 *
		 * @return void
		 */
		protected function setTypeSettings(){
			$this->typeSettings = new wp_ulike_setting_type( $this->itemType );
		}

		/**
		 * Set is user logged in status
		 *
		 * @return void
		 */
		protected function setIsUserLoggedIn( $user_id ){
			$this->isUserLoggedIn = $user_id === NULL ? is_user_logged_in() : true;
		}

		/**
		 * Set current user ID
		 *
		 * @return void
		 */
		protected function setCurrentUser( $user_id ){
			if( $user_id === NULL ){
				$this->currentUser = $this->isUserLoggedIn ? get_current_user_id() : wp_ulike_generate_user_id( $this->currentIP );
			} else {
				$this->currentUser = $user_id;
			}
		}

		/**
		 * Get current user finger print
		 *
		 * @return string
		 */
		public function getCurrentFingerPrint(){
			return $this->currentFingerPrint;
		}

		/**
		 * Get current user id
		 *
		 * @return string
		 */
		public function getCurrentUser(){
			return $this->currentUser;
		}

		/**
		 * Get user previous status
		 *
		 * @return string
		 */
		public function getPrevStatus(){
			return $this->prevStatus;
		}

		/**
		 * Get user current status
		 *
		 * @return string
		 */
		public function getCurrentStatus(){
			return $this->currentStatus;
		}

		/**
		 * Get data info
		 *
		 * @return array
		 */
		public function getSettings(){
			return $this->typeSettings;
		}


		/**
		 * Update current status
		 *
		 * @param string $factor
		 * @param boolean $keep_status
		 * @return void
		 */
		public function setCurrentStatus( $args, $keep_status = false, $force_status = false ){
			if( $force_status ){
				$this->currentStatus = $force_status;
				return;
			}

			if( $args['item_factor'] === 'down' ){
				$this->currentStatus = $this->prevStatus !== 'dislike' || $keep_status ? 'dislike' : 'undislike';
			} else {
				$this->currentStatus = $this->prevStatus !== 'like' || $keep_status ? 'like' : 'unlike';
			}

			$this->currentStatus = apply_filters( 'wp_ulike_user_current_status', $this->currentStatus, $this->prevStatus, $args );
		}

		/**
		 * Set user previous status
		 *
		 * @param string $item_id
		 * @return void
		 */
		public function setPrevStatus( $item_id ){
			// delete cache to get fresh data
			// if( wp_ulike_is_cache_exist() ){
			// 	wp_cache_delete( $this->currentUser, 'wp_ulike_user_meta' );
			// }

			$args = array(
				"item_id"           => $item_id,
				"item_type"         => $this->itemType,
				"current_user"      => $this->currentUser,
				"settings"          => $this->typeSettings,
				"is_user_logged_in" => $this->isUserLoggedIn
			);

			// get user log array
			$get_user_history = wp_ulike_get_user_item_history( $args );
			// set prev status
			$this->prevStatus = apply_filters( 'wp_ulike_user_prev_status', ! empty( $get_user_history[$item_id] ) ? $get_user_history[$item_id] : false, $args );
		}

		/**
		 * Check permission access with bot + fingerprint protection
		 *
		 * @param array $args
		 * @param object $settings
		 * @return boolean
		 */
		public static function hasPermission( $args, $settings ){
			// Default status
			$status = true;

			// Check bot status first
			if ( wp_ulike_is_bot_request() ) {
				return false;
			}

			// Check for logging method
			$method = wp_ulike_setting_repo::getMethod( $args['type'] );

			// check cookie existense
			$has_cookie   = false;

			if ( in_array( $method, array( 'by_cookie', 'by_user_ip_cookie' ) ) ) {
				$cookie_key   = sanitize_key( 'wp_ulike_' . md5( $args['type'] . '_logs' ) );
				$cookie_data  = self::getDecodedCookieData( $cookie_key );
				$user_hash    = md5( $args['current_user'] );
				$current_time = current_time( 'timestamp' );

				if ( isset( $cookie_data[ $user_hash ][ $args['item_id'] ] ) ) {
					if ( is_numeric( $cookie_data[ $user_hash ][ $args['item_id'] ] ) && $current_time >= $cookie_data[ $user_hash ][ $args['item_id'] ] ) {
						$status = true;
					} else {
						$status = false;
						$has_cookie = true;
					}
				} elseif ( isset( $_COOKIE[ $settings->getCookieName() . $args['item_id'] ] ) ) {
					$status = false;
					$has_cookie = true;
				}

				if ( $method === 'by_user_ip_cookie' ) {
					$cookie_hash = array_keys( $cookie_data );
					foreach ( $cookie_hash as $value ) {
						if ( ! empty( $cookie_data[ $value ][ $args['item_id'] ] ) ) {
							if ( is_numeric( $cookie_data[ $value ][ $args['item_id'] ] ) && $current_time >= $cookie_data[ $value ][ $args['item_id'] ] ) {
								$status = true;
							} else {
								$status = $user_hash !== $value ? false : true;
								if ( $user_hash !== $value ) {
									$has_cookie = true;
								}
							}
						}
					}
				}

				if ( ! $has_cookie && $args['method'] === 'process' ) {
					$cookie_expire = wp_ulike_setting_repo::getCookieExpiration( $args['type'] );

					if ( empty( $cookie_data ) ) {
						$cookie_data = array( $user_hash => array(
							$args['item_id'] => $cookie_expire
						) );
					} else {
						foreach ( $cookie_data as $hash => $info ) {
							if ( ! isset( $info[$args['item_id']] ) && $hash != $user_hash ) {
								$cookie_data[ $user_hash ][ $args['item_id'] ] = $cookie_expire;
							} elseif ( $hash == $user_hash ) {
								$cookie_data[ $hash ][ $args['item_id'] ] = $cookie_expire;
							}
						}
					}

					wp_ulike_setcookie( $cookie_key, wp_json_encode( $cookie_data ), time() + 2147483647 );
				}
			}

			// Fingerprint check for guests or requests without cookies
			if ( ! is_user_logged_in() && $args['method'] === 'process' && in_array( $method, ['do_not_log', 'by_cookie'] ) ) {

				$fingerprint_count = wp_ulike_count_current_fingerprint(
					$args['current_finger_print'],
					$args['item_id'],
					$args['type']
				);

				if ( ! empty( $fingerprint_count ) ) {
					if ( $method === 'do_not_log' ) {
						if ( $fingerprint_count >= wp_ulike_setting_repo::getVoteLimitNumber( $args['type'] ) ) {
							$status = false;
						}
					} elseif ( ! $has_cookie && $method === 'by_cookie' ) {
						if ( $fingerprint_count >= 1 ) {
							$status = false;
						}
					}
				}
			}


			return apply_filters( 'wp_ulike_permission_status', $status, $args, $settings );
		}


		/**
		 * Get decoded cookie data
		 *
		 * @param string $cookie_key
		 * @return void
		 */
		private static function getDecodedCookieData( $cookie_key ) {
			$cookie_data = isset( $_COOKIE[ $cookie_key ] ) ? json_decode( wp_unslash( $_COOKIE[ $cookie_key ] ), true ) : array();
			return is_array( $cookie_data ) ? $cookie_data : array();
		}

		/**
		 * Check distinct status by logging method
		 *
		 * @return boolean
		 */
		public function isDistinct(){
			return wp_ulike_setting_repo::isDistinct( $this->itemType );
		}

		/**
		 * Inset log data
		 *
		 * @param integer $item_id
		 * @return integer|false
		 */
		public function insertData( $item_id ){
			$table = $this->wpdb->prefix . $this->typeSettings->getTableName();
			$data  = array(
				$this->typeSettings->getColumnName() => $item_id,
				'date_time'                          => current_time( 'mysql' ),
				'ip'                                 => $this->maybeAnonymiseIp( $this->currentIP ),
				'user_id'                            => $this->currentUser,
				'fingerprint'                        => $this->currentFingerPrint,
				'status'                             => $this->currentStatus
			);
			$format = array( '%d', '%s', '%s', '%s', '%s', '%s'  ); // Adjust format specifiers

			$row = $this->wpdb->insert( $table, $data, $format );

			if ( false !== $row ) {
				do_action( 'wp_ulike_data_inserted', [
					'id'             => $this->wpdb->insert_id,  // New insert ID
					'item_id'        => $item_id,                // Item ID for the inserted data
					'table'          => $table,                  // Table name where the insert occurred
					'related_column' => $this->typeSettings->getColumnName(), // Column name related to the insert
					'type'           => $this->typeSettings->getType(),        // Type of the item
					'user_id'        => $this->currentUser,      // User who performed the action
					'status'         => $this->currentStatus,    // Status of the action
					'ip'             => $this->currentIP         // IP address of the user
				] );
			}

			return $row;
		}

		/**
		 * Anonymise IP address if option enabled.
		 *
		 * @param string $ip
		 * @return string
		 */
		protected function maybeAnonymiseIp( $ip ){
			// Check anonymise enable
			if( wp_ulike_setting_repo::isAnonymiseIpOn() ){
				if( wp_ulike_setting_repo::isIpLoggingOff() ){
					$ip = '0.0.0.0';
				} else {
					if ( strpos( $ip, "." ) == true ) {
						$ip = preg_replace('~[0-9]+$~', '0', $ip );
					} else {
						$ip = preg_replace('~[0-9]*:[0-9]+$~', '0000:0000', $ip );
					}
				}
			}

			return apply_filters( 'wp_ulike_database_user_ip', esc_sql( $ip ) );
		}

		/**
		 * Update log data
		 *
		 * @param integer $item_id
		 * @return integer|false
		 */
		public function updateData( $item_id ){
			$table  = $this->wpdb->prefix . $this->typeSettings->getTableName();
			$data   = array( 'status' => $this->currentStatus, 'date_time' => current_time( 'mysql' ) ); // No need for esc_sql
			$where  = array(
				$this->typeSettings->getColumnName() => $item_id,
				'user_id'   => $this->currentUser
			);
			$format = array( '%s', '%s' ); // Format for 'status'
			$where_format = array( '%d', '%s' ); // Ensure proper format for WHERE clause

			$row = $this->wpdb->update( $table, $data, $where, $format, $where_format );

			if ( false !== $row ) {
				do_action( 'wp_ulike_data_updated', [
					'item_id'        => $item_id,                // Item ID for the inserted data
					'table'          => $table,                  // Table name where the insert occurred
					'related_column' => $this->typeSettings->getColumnName(), // Column name related to the insert
					'type'           => $this->typeSettings->getType(),        // Type of the item
					'user_id'        => $this->currentUser,      // User who performed the action
					'status'         => $this->currentStatus,    // Status of the action
					'ip'             => $this->currentIP         // IP address of the user
				] );
			}

			return $row;
		}

		/**
		 * Delete log data
		 *
		 * @param integer $item_id
		 * @return integer|false
		 */
		public function deleteData( $item_id ){
			return $this->wpdb->delete(
				$this->wpdb->prefix . $this->typeSettings->getTableName(),
				array( $this->typeSettings->getColumnName() => $item_id, 'user_id' => $this->currentUser )
			);
		}

		/**
		 * Update and return counter value
		 *
		 * @param integer $item_id
		 * @return integer
		 */
		public function updateCounterMeta( $item_id ){
			// delete cache to get fresh data
			if( wp_ulike_is_cache_exist() && $item_id ){
				wp_cache_delete( $item_id, sprintf( 'wp_ulike_%s_meta', $this->itemType ) );
			}

			// Remove 'un' prefix from status.
			$status  = ltrim( $this->currentStatus, 'un');
			// Get current value
			$pre_val = wp_ulike_meta_counter_value( $item_id, $this->itemType, $status, $this->isDistinct() );
			// If metadata exist update it
			if( ! is_null( $pre_val ) ){
				// Update new val
				if( strpos( $this->currentStatus, 'un') === false  ){
					++$pre_val;
				} else {
					--$pre_val;
				}
				// Abvoid neg values
				$pre_val = max( $pre_val, 0 );
				// Update meta
				wp_ulike_update_meta_counter_value( $item_id, $pre_val, $this->itemType, $status, $this->isDistinct() );
			}

			// Decrease reverse meta value
			if( $this->isDistinct() && $this->prevStatus ){
				// Check user conditions
				if( ltrim( $this->prevStatus, 'un') !== $status &&
					strpos( $this->currentStatus, 'un') === false &&
					strpos( $this->prevStatus, 'un') === false ){
					// Get reverse key
					$reverse_key = strpos( $status, 'dis') === false ? 'dislike' : 'like';
					// Get reverse counter value
					$reverse_val = wp_ulike_meta_counter_value( $item_id, $this->itemType, $reverse_key, $this->isDistinct() );
					// Update meta if exist
					if( ! is_null( $reverse_val ) ){
						wp_ulike_update_meta_counter_value( $item_id, max( $reverse_val - 1, 0 ), $this->itemType, $reverse_key, $this->isDistinct() );
					}
				}
			}
		}

		/**
		 * Update user meta status
		 *
		 * @param integer $item_id
		 * @return void
		 */
		public function updateUserMetaStatus( $item_id ){
			// Update object cache (memcached issue)
			$meta_key  = sanitize_key( $this->itemType . '_status' );
			// delete cache to get fresh data
			if( wp_ulike_is_cache_exist() && $this->currentUser ){
				wp_cache_delete( $this->currentUser, 'wp_ulike_user_meta' );
			}
			// Get meta data
			$user_info = wp_ulike_get_meta_data( $this->currentUser, 'user', $meta_key, true );

			if( empty( $user_info ) ){
				$user_info = array( $item_id => $this->currentStatus );
			} else {
				$user_info[$item_id] = $this->currentStatus;
			}

			// Update meta value
			wp_ulike_update_meta_data( $this->currentUser, 'user', $meta_key, $user_info );
		}

		/**
		 * Update likers meta list
		 *
		 * @param integer $item_id
		 * @return void
		 */
		public function updateLikerMetaList( $item_id ){
			// Only for logged in users
			if(! $this->isUserLoggedIn){
				return;
			}

			// delete cache to get fresh data
			if( wp_ulike_is_cache_exist() && $item_id ){
				wp_cache_delete( $item_id, sprintf( 'wp_ulike_%s_meta', $this->itemType ) );
			}
			// Get meta data
			$get_likers = wp_ulike_get_meta_data( $item_id, $this->itemType, 'likers_list', true );

			// Check empty array
			if( empty( $get_likers ) ){
				$get_likers = array();
			}

			$get_user   = get_userdata( $this->currentUser );
			$is_updated = false;
			if( $get_user ){
				if( in_array( $get_user->ID, $get_likers ) ){
					if( strpos( $this->currentStatus, 'un') !== false ){
						$get_likers = array_diff( $get_likers, array( $get_user->ID ) );
						$is_updated = true;
					}
				} else {
					if( strpos( $this->currentStatus, 'un') === false ){
						array_push( $get_likers, $get_user->ID );
						$is_updated = true;
					}
				}
				// If array list has been changed, then update meta data.
				if( $is_updated ){
					wp_ulike_update_meta_data( $item_id, $this->itemType, 'likers_list', $get_likers );
				}
			}
		}

		/**
		 * Update stats meta data
		 *
		 * @param integer $item_id
		 * @return void
		 */
		public function updateStatsMetaData( $item_id ){
			// Update total stats
			if( ( ! $this->prevStatus || ! $this->isDistinct() ) ){
				if( strpos( $this->currentStatus, 'un') === false  ){
					// update all logs period
					$this->wpdb->query( "
						UPDATE `{$this->wpdb->prefix}ulike_meta`
						SET `meta_value` = (`meta_value` + 1)
						WHERE `meta_group` = 'statistics' AND `meta_key` = 'count_logs_period_all'
					" );
					// update new votes
					$this->wpdb->query( "
						UPDATE `{$this->wpdb->prefix}ulike_meta`
						SET `meta_value` = (`meta_value` + 1)
						WHERE `meta_group` = 'statistics' AND `meta_key` = 'calculate_new_votes'
					" );
					$table = $this->typeSettings->getTableName();
					$this->wpdb->query( "
						UPDATE `{$this->wpdb->prefix}ulike_meta`
						SET `meta_value` = (`meta_value` + 1)
						WHERE `meta_group` = 'statistics' AND `meta_key` = 'count_logs_for_{$table}_table_in_all_daterange'
					" );
				}

				// Save daily stats
				// $current_time  = current_time( 'Ymd' );
				// $current_key   = sanitize_key( $this->itemType . '_' . $this->currentStatus );
				// $current_count = wp_ulike_get_meta_data( $current_time, 'statistics', $current_key, true );

				// if( empty( $current_count ) ){
				// 	wp_ulike_update_meta_data( $current_time, 'statistics', $current_key, 1 );
				// } else {
				// 	$this->wpdb->query( "
				// 		UPDATE `{$this->wpdb->prefix}ulike_meta`
				// 		SET `meta_value` = (`meta_value` + 1)
				// 		WHERE `meta_group` = 'statistics' AND `meta_key` = '{$current_key}' AND `item_id` = {$current_time}
				// 	" );
				// }
			}
			// Delete object cache
			if( wp_ulike_is_cache_exist() ){
				wp_cache_delete( 'calculate_new_votes', WP_ULIKE_SLUG );
				wp_cache_delete( 'count_logs_period_all', WP_ULIKE_SLUG );
				wp_cache_delete( 1, 'wp_ulike_statistics_meta' );
			}
		}

		/**
		 * Update meta data
		 *
		 * @param integer $item_id
		 * @return void
		 */
		public function updateMetaData( $item_id ){
			// Update meta data
			$this->updateCounterMeta( $item_id );
			// Update user status
			$this->updateUserMetaStatus( $item_id );
			// Update likers list
			$this->updateLikerMetaList( $item_id );
			// Update stats meta data
			$this->updateStatsMetaData( $item_id );
		}

	}

}