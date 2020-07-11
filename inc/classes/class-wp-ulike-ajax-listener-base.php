<?php

/**
* Base AJAX class
*/
abstract class wp_ulike_ajax_listener_base{

	public function __construct(){}

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