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

		private $parsedArgs;

		private $isDistinct;
		private $dataArgs;

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
				'item_settings'     => array(),
				'user_id'           => NULL,
				'user_ip'           => NULL,
				'is_user_logged_in' => NULL
			);

			$this->parsedArgs = wp_parse_args( $atts, $default_atts );

			parent::__construct( array(
				'user_id' => $this->parsedArgs['user_id'],
				'user_ip' => $this->parsedArgs['user_ip']
			) );

			$this->dataArgs = parent::getDataArgs( $this->parsedArgs['item_type'] );

			$this->setIsDistinct();
		}

		private function setIsDistinct(){
			$this->isDistinct = parent::isDistinct( $this->parsedArgs['item_settings']->getMethod() );
		}

		public function getStatusCode(){
			if( ! self::$currentStatus ){
				return 1;
			} elseif( ! $this->isDistinct ){
				return 4;
			} elseif( strpos( self::$currentStatus, 'un') === 0 ){
				return 2;
			} else {
				return 3;
			}
		}

		public function update(){
			parent::setPrevStatus( $this->parsedArgs['item_type'], $this->parsedArgs['item_id'], $this->dataArgs['table'], $this->dataArgs['column'] );

			switch( $this->parsedArgs['item_settings']->getMethod() ){
				case 'do_not_log':
					parent::updateStatus( $this->parsedArgs['item_factor'], true );
					// Insert log data
					$this->insertData( $this->parsedArgs['item_id'], $this->dataArgs['table'], $this->dataArgs['column'] );
					break;
				case 'by_cookie':
					if( parent::hasPermission( array(
						'method' => $this->parsedArgs['item_settings']->getMethod(),
						'type'   => $this->parsedArgs['item_settings']->getCookieName(),
						'id'     => $this->parsedArgs['item_id']
					) ) ){
						parent::updateStatus( $this->parsedArgs['item_factor'], true );
						// Set cookie
						setcookie( $this->parsedArgs['item_settings']->getCookieName(). $this->parsedArgs['item_id'], time(), 2147483647, '/' );
						// Insert log data
						$this->insertData( $this->parsedArgs['item_id'], $this->dataArgs['table'], $this->dataArgs['column'] );
					}
					break;
				default:
					parent::updateStatus( $this->parsedArgs['item_factor'] );
					if( parent::getPrevStatus() ){
						$this->updateData( $this->parsedArgs['item_id'], $this->dataArgs['table'], $this->dataArgs['column'] );
					} else {
						$this->insertData( $this->parsedArgs['item_id'], $this->dataArgs['table'], $this->dataArgs['column'] );
					}
					break;
			}

			$this->updateUserMetaStatus( $this->parsedArgs['item_id'], $this->parsedArgs['item_type'], parent::$currentStatus );

		}

		public function getAjaxProcessAtts(){
			return apply_filters( 'wp_ulike_ajax_process_atts', array(
					"id"                   => $this->parsedArgs['item_id'],
					"method"               => $this->parsedArgs['item_type'],
					"type"                 => 'process',
					"table"                => $this->dataArgs['table'],
					"column"               => $this->dataArgs['column'],
					"key"                  => $this->dataArgs['key'],
					"slug"                 => $this->dataArgs['slug'],
					"cookie"               => $this->dataArgs['cookie'],
					"factor"               => $this->parsedArgs['item_factor'],
					"style"                => $this->parsedArgs['item_template'],
					"logging_method"       => $this->parsedArgs['item_settings']->getMethod(),
					"only_logged_in_users" => $this->parsedArgs['item_settings']->requireLogin(),
					"logged_out_action"    => $this->parsedArgs['item_settings']->anonymousDisplay(),
				), $this->parsedArgs['item_id'], $this->dataArgs
			);
		}

		public function getActionAtts(){
			return array(
				'id'          => $this->parsedArgs['item_id'],
				'key'         => $this->dataArgs['key'],
				'user_id'     => parent::$currentUser,
				'status'      => parent::$currentStatus,
				'has_log'     => ! parent::$prevStatus ? 0 : 1,
				'slug'        => $this->parsedArgs['item_type'],
				'table'       => $this->dataArgs['table'],
				'is_distinct' => $this->isDistinct
			);
		}

		/**
		 * Get counter value for ajax responses
		 *
		 * @param integer $id
		 * @param string $slug
		 * @return integer
		 */
		public function getCounterValue(){
			$counter_val = $this->updateCounterValue( $this->parsedArgs['item_id'], $this->parsedArgs['item_type'], parent::$currentStatus, $this->isDistinct );
			$counter_val = wp_ulike_format_number( $counter_val, parent::$currentStatus );
			return apply_filters( 'wp_ulike_ajax_counter_value', $counter_val, $this->parsedArgs['item_id'], $this->parsedArgs['item_type'], parent::$currentStatus, $this->isDistinct );
		}

	}

}