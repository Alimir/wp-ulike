<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class wp_ulike_voters_listener extends wp_ulike_ajax_listener_base {

	public function __construct(){
		parent::__construct();
		$this->setFormData();
		$this->getList();
	}

	/**
	 * Set Form Data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['id']             = isset( $_POST['id'] ) ? absint($_POST['id']) : NULL;
		$this->data['type']           = isset( $_POST['type'] ) ? sanitize_text_field($_POST['type']) : NULL;
		$this->data['nonce']          = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : NULL;
		$this->data['displayLikers']  = isset( $_POST['displayLikers'] ) ? sanitize_text_field($_POST['displayLikers']) : false;
		$this->data['likersTemplate'] = isset( $_POST['likersTemplate'] ) ? sanitize_text_field($_POST['likersTemplate']) : 'popover';
	}

	/**
	 * Get likers list info
	 *
	 * @return void
	 */
	private function getList(){
		try {
			$this->beforeGetListAction();

			$this->settings_type = new wp_ulike_setting_type( $this->data['type'] );

			if ( !$this->validates() ){
				throw new \Exception( esc_html__( 'permission denied.', 'wp-ulike' ) );
			}

			if( empty( $this->settings_type->getType() ) ){
				throw new \Exception( esc_html__( 'Invalid item type.', 'wp-ulike' ) );
			}

			$template = wp_ulike_get_likers_template(
				$this->settings_type->getTableName(),
				$this->settings_type->getColumnName(),
				$this->data['id'],
				$this->settings_type->getSettingKey(),
				array(
                	'style' => $this->data['likersTemplate']
            	)
			);

			$this->afterGetListAction();

			$this->response( ! empty( $template ) ? array(
				'template' => $this->data['likersTemplate'] != 'popover' ? $template :  sprintf(
					'<div class="wp_ulike_likers_wrapper wp_%s_likers_%s">%s</div>', $this->settings_type->getType(), $this->data['id'], $template )
			) : array( 'template' => '' ) );

		} catch ( \Exception $e ){
			return $this->sendError($e->getMessage());
		}
	}

	/**
	* Before Update Action
	* Provides hook for performing actions before a voter process
	*/
	private function beforeGetListAction(){
		do_action_ref_array('wp_ulike_before_voters_process', $this->data );
	}

	/**
	* After Update Action
	* Provides hook for performing actions after a voter process
	*/
	private function afterGetListAction(){
		do_action_ref_array( 'wp_ulike_after_voters_process', $this->data );
	}

	/**
	* Validate the Favorite
	*/
	private function validates()
	{
		// Return false when ID not exist
		if( empty( $this->data['id'] ) ) return false;
		// Return false when anonymous display is off
		if( wp_ulike_setting_repo::restrictLikersBox( $this->settings_type->getType() ) && ! $this->user ) return false;
		// Return false display is off
		if( ! $this->data['displayLikers'] ) return false;

		return true;
	}
}