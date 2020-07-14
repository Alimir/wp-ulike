<?php

final class wp_ulike_cta_listener extends wp_ulike_ajax_listener_base {

	private $user;
	private $response = array(
		'message'     => NULL,
		'btnText'     => NULL,
		'messageType' => 'info',
		'status'      => 0,
		'data'        => NULL
	);

	public function __construct(){
		parent::__construct();
		$this->setFormData();
		$this->setUser();
		$this->updateButton();
	}

	/**
	 * Set Form Data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['id']       = isset( $_POST['id'] ) ? intval(sanitize_text_field($_POST['id'])) : NULL;
		$this->data['type']     = isset( $_POST['type'] ) ? sanitize_text_field($_POST['type']) : NULL;
		$this->data['nonce']    = isset( $_POST['nonce'] ) ? esc_html( $_POST['nonce'] ) : NULL;
		$this->data['factor']   = isset( $_POST['factor'] ) ? sanitize_text_field($_POST['factor']) : NULL;
		$this->data['template'] = isset( $_POST['template'] ) ? sanitize_text_field($_POST['template']) : 'wpulike-default';
	}

	/**
	 * Set current user id if exist
	 *
	 * @return void
	 */
	private function setUser(){
		$this->user = is_user_logged_in() ? get_current_user_id() : false;
	}

	/**
	 * Update button
	 *
	 * @return void
	 */
	private function updateButton(){
		try {
			$this->beforeUpdateAction( $this->data );

			if ( !$this->validates() ){
				throw new \Exception( __( 'Invalid format.', WP_ULIKE_SLUG ) );
			}

			$settings = new wp_ulike_setting_type_repo( $this->data['type'] );

			if( empty( $settings->getType() ) ){
				throw new \Exception( __( 'Invalid item type.', WP_ULIKE_SLUG ) );
			}

			$process  = new wp_ulike_cta_process( array(
				'item_id'           => $this->data['id'],
				'item_type'         => $settings->getType(),
				'item_factor'       => $this->data['factor'],
				'item_template'     => $this->data['template'],
				'item_settings'     => $settings
			) );

			if( $settings->requireLogin() && ! $this->user ){
				$this->response['message'] = $settings->getLoginNotice();
				$this->response['status']  = 4;
			} else {
				if( ! wp_ulike_entities_process::hasPermission( array(
					'method' => $settings->getMethod(),
					'type'   => $settings->getCookieName(),
					'id'     => $this->data['id']
				) ) ){
					$this->response['message']     = $settings->getPermissionNotice();
					$this->response['status']      = 5;
					$this->response['messageType'] = 'warning';
				} else {
					$process->update();
					$this->response['status'] = $process->getStatusCode();
					$counter_value = $process->getCounterValue();

					switch ( $this->response['status'] ){
						case 1:
							$this->response['message']     = $settings->getLikeNotice();
							$this->response['messageType'] = 'success';
							$this->response['btnText'] = $settings->getButtonText('like');
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter_value, $this->data['id'] );
							break;
						case 2:
							$this->response['message']     = $settings->getUnLikeNotice();
							$this->response['messageType'] = 'error';
							$this->response['btnText'] = $settings->getButtonText('like');
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_unliked_data', $counter_value, $this->data['id'] );
							break;
						case 3:
							$this->response['message']     = $settings->getLikeNotice();
							$this->response['messageType'] = 'success';
							$this->response['btnText'] = $settings->getButtonText('unlike');
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_liked_data', $counter_value, $this->data['id'] );
							break;
						case 4:
							$this->response['message']     = $settings->getLikeNotice();
							$this->response['messageType'] = 'success';
							$this->response['btnText'] = $settings->getButtonText('like');
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter_value, $this->data['id'] );
							break;
					}
				}
			}

			$response = apply_filters( 'wp_ulike_ajax_respond', $this->response, $this->data['id'], $this->response['status'], $process->getAjaxProcessAtts() );

			$this->afterUpdateAction( $process->getActionAtts() );

			$this->response( $response );
		} catch ( \Exception $e ){
			return $this->sendError($e->getMessage());
		}
	}

	/**
	* Before Update Action
	* Provides hook for performing actions before a like/dislike
	*/
	private function beforeUpdateAction( $args = array() ){
		do_action_ref_array('wp_ulike_before_process', $args );
	}

	/**
	* After Update Action
	* Provides hook for performing actions after a like/dislike
	*/
	private function afterUpdateAction( $args = array() ){
		do_action_ref_array( 'wp_ulike_after_process', $args );
	}

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false when ID not exist
		if( empty( $this->data['id'] ) ) return false;
		// Return false when nonce invalid
		if( ! wp_verify_nonce( $this->data['nonce'], $this->data['type'] . $this->data['id'] ) && wp_ulike_is_cache_exist() ) return false;

		return true;
	}
}