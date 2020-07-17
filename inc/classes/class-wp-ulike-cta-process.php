<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_cta_process' ) ) {

	class wp_ulike_cta_process extends wp_ulike_entities_process {

		protected $parsedArgs;
		protected $settings;

		/**
		 * Constructor
		 */
		function __construct( $atts ){
			// Defining default attributes
			$default_atts = array(
				'item_id'           => NULL,
				'item_type'         => 'post',
				'item_factor'       => NULL,
				'item_template'     => NULL,
				'user_id'           => NULL,
				'user_ip'           => NULL,
				'is_user_logged_in' => NULL
			);

			$this->parsedArgs = wp_parse_args( $atts, $default_atts );

			// Get settings type
			$this->settings   = new wp_ulike_setting_type( $this->parsedArgs['item_type'] );

			parent::__construct( array(
				'user_id'     => $this->parsedArgs['user_id'],
				'user_ip'     => $this->parsedArgs['user_ip'],
				'item_type'   => $this->parsedArgs['item_type'],
				'item_method' => wp_ulike_setting_repo::getMethod( $this->parsedArgs['item_type'] )
			) );
		}

		/**
		 * Get status code
		 *
		 * @return integer
		 */
		public function getStatusCode(){
			if( ! $this->getCurrentStatus() ){
				return 1;
			} elseif( ! $this->isDistinct() ){
				return 4;
			} elseif( strpos( $this->getCurrentStatus(), 'un') === 0 ){
				return 2;
			} else {
				return 3;
			}
		}

		/**
		 * Update button info
		 *
		 * @return void
		 */
		public function update(){
			$this->setPrevStatus( $this->parsedArgs['item_id'] );

			switch( wp_ulike_setting_repo::getMethod( $this->parsedArgs['item_type'] ) ){
				case 'do_not_log':
					$this->setCurrentStatus( $this->parsedArgs['item_factor'], true );
					// Insert log data
					$this->insertData( $this->parsedArgs['item_id'] );
					break;
				case 'by_cookie':
					if( $this->hasPermission( array(
						'method' => wp_ulike_setting_repo::getMethod( $this->parsedArgs['item_type'] ),
						'type'   => $this->settings->getCookieName(),
						'id'     => $this->parsedArgs['item_id']
					) ) ){
						$this->setCurrentStatus( $this->parsedArgs['item_factor'], true );
						// Set cookie
						setcookie( $this->settings->getCookieName(). $this->parsedArgs['item_id'], time(), 2147483647, '/' );
						// Insert log data
						$this->insertData( $this->parsedArgs['item_id'] );
					}
					break;
				default:
					$this->setCurrentStatus( $this->parsedArgs['item_factor'] );
					if( $this->getPrevStatus() ){
						$this->updateData( $this->parsedArgs['item_id'] );
					} else {
						$this->insertData( $this->parsedArgs['item_id'] );
					}
					break;
			}

			$this->updateMetaData( $this->parsedArgs['item_id'] );
		}

		/**
		 * Get deprecated ajax process atts
		 *
		 * @return array
		 */
		public function getAjaxProcessAtts(){
			return apply_filters( 'wp_ulike_ajax_process_atts', array(
					"id"                   => $this->parsedArgs['item_id'],
					"method"               => $this->parsedArgs['item_type'],
					"type"                 => 'process',
					"table"                => $this->settings->getTableName(),
					"column"               => $this->settings->getColumnName(),
					"key"                  => $this->settings->getKey(),
					"slug"                 => $this->settings->getType(),
					"cookie"               => $this->settings->getCookieName(),
					"factor"               => $this->parsedArgs['item_factor'],
					"style"                => $this->parsedArgs['item_template'],
					"logging_method"       => wp_ulike_setting_repo::getMethod( $this->parsedArgs['item_type'] ),
					"only_logged_in_users" => wp_ulike_setting_repo::requireLogin( $this->parsedArgs['item_type'] ),
					"logged_out_action"    => wp_ulike_setting_repo::anonymousDisplay( $this->parsedArgs['item_type'] ),
				), $this->parsedArgs['item_id']
			);
		}

		/**
		 * Get action atts
		 *
		 * @return array
		 */
		public function getActionAtts(){
			return array(
				'id'          => $this->parsedArgs['item_id'],
				'key'         => $this->settings->getKey(),
				'user_id'     => $this->getCurrentUser(),
				'status'      => $this->getCurrentStatus(),
				'has_log'     => ! $this->getPrevStatus() ? 0 : 1,
				'slug'        => $this->parsedArgs['item_type'],
				'table'       => $this->settings->getTableName(),
				'is_distinct' => $this->isDistinct()
			);
		}

		/**
		 * Get counter value for ajax responses
		 *
		 * @return integer
		 */
		public function getCounterValue(){
			$counter_val = $this->updateCounterMeta( $this->parsedArgs['item_id'] );
			$counter_val = wp_ulike_format_number( $counter_val, $this->getCurrentStatus() );
			return apply_filters( 'wp_ulike_ajax_counter_value', $counter_val, $this->parsedArgs['item_id'], $this->parsedArgs['item_type'], $this->getCurrentStatus(), $this->isDistinct() );
		}

	}

}