<?php

/**
* Base AJAX class
*/
abstract class wp_ulike_ajax_listener_base{

	/**
	* Form Data
	*/
	protected $data;

	/**
	* User info
	*/
	protected $user;

	/**
	* Settings Type
	*/
	protected $settings_type;

	public function __construct(){
		$this->setUser();
	}

	/**
	 * Set current user id if exist
	 *
	 * @return void
	 */
	protected function setUser(){
		$this->user = is_user_logged_in() ? get_current_user_id() : false;
	}

	/**
	* Send an Error Response
	*
	* @param string $error
	*/
	protected function sendError( $error = null ){
		$error = ! empty( $error ) ? $error : __( 'There was an error processing the request.', WP_ULIKE_SLUG );
		return wp_send_json_error( $error );
	}

	/**
	 * Send a response
	 *
	 * @param array $response
	 */
	protected function response( $response ){
		return wp_send_json_success( $response );
	}
}