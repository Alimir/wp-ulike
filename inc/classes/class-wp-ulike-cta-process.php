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

		private $item_id;
		private $item_type;
		private $item_factor;
		private $item_template;
		private $item_settings;

		private $isDistinct;
		private $dataArgs;

		/**
		 * Constructor
		 */
		function __construct( $id, $type, $factor, $template, $settings ){
			parent::__construct();

			$this->item_id = $id;
			$this->item_type = $type;
			$this->item_factor = $factor;
			$this->item_template = $factor;
			$this->item_settings = $settings;

			$this->dataArgs = parent::getDataArgs( $type );

			$this->setIsDistinct();
		}

		private function setIsDistinct(){
			$this->isDistinct = parent::isDistinct( $this->item_settings->getMethod() );
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
			parent::setPrevStatus( $this->item_type, $this->item_id, $this->dataArgs['table'], $this->dataArgs['column'] );

			switch( $this->item_settings->getMethod() ){
				case 'do_not_log':
					parent::updateStatus( $this->item_factor, true );
					// Insert log data
					$this->insertData( $this->item_id, $this->dataArgs['table'], $this->dataArgs['column'] );
					break;
				case 'by_cookie':
					if( parent::hasPermission( array(
						'method' => $this->item_settings->getMethod(),
						'type'   => $this->item_settings->getCookieName(),
						'id'     => $this->item_id
					) ) ){
						parent::updateStatus( $this->item_factor, true );
						// Set cookie
						setcookie( $this->item_settings->getCookieName(). $this->item_id, time(), 2147483647, '/' );
						// Insert log data
						$this->insertData( $this->item_id, $this->dataArgs['table'], $this->dataArgs['column'] );
					}
					break;
				default:
					parent::updateStatus( $this->item_factor );
					if( parent::getPrevStatus() ){
						$this->insertData( $this->item_id, $this->dataArgs['table'], $this->dataArgs['column'] );
					} else {
						$this->updateData( $this->item_id, $this->dataArgs['table'], $this->dataArgs['column'] );
					}
					break;
			}

			$this->updateUserMetaStatus( $this->item_id, $this->item_type, parent::$currentStatus );

		}

		public function getAjaxProcessAtts(){
			return apply_filters( 'wp_ulike_ajax_process_atts', array(
					"id"                   => $this->item_id,
					"method"               => $this->item_type,
					"type"                 => 'process',
					"table"                => $this->dataArgs['table'],
					"column"               => $this->dataArgs['column'],
					"key"                  => $this->dataArgs['key'],
					"slug"                 => $this->dataArgs['slug'],
					"cookie"               => $this->dataArgs['cookie'],
					"factor"               => $this->item_factor,
					"style"                => $this->item_template,
					"logging_method"       => $this->item_settings->getMethod(),
					"only_logged_in_users" => $this->item_settings->requireLogin(),
					"logged_out_action"    => $this->item_settings->anonymousDisplay(),
				), $this->item_id, $this->dataArgs
			);
		}

		public function getActionAtts(){
			return array(
				'id'          => $this->item_id,
				'key'         => $this->dataArgs['key'],
				'user_id'     => parent::$currentUser,
				'status'      => parent::$currentStatus,
				'has_log'     => ! parent::$prevStatus ? 0 : 1,
				'slug'        => $this->item_type,
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
			$counter_val = $this->updateCounterValue( $this->item_id, $this->item_type, parent::$currentStatus, $this->isDistinct );
			$counter_val = wp_ulike_format_number( $counter_val, parent::$currentStatus );
			return apply_filters( 'wp_ulike_ajax_counter_value', $counter_val, $this->item_id, $this->item_type, parent::$currentStatus, $this->isDistinct );
		}

	}

}