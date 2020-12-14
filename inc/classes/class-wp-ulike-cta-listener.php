<?php

final class wp_ulike_cta_listener extends wp_ulike_ajax_listener_base {

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
		$this->updateButton();
	}

	/**
	 * Set Form Data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['id']              = isset( $_POST['id'] ) ? intval(sanitize_text_field($_POST['id'])) : NULL;
		$this->data['type']            = isset( $_POST['type'] ) ? sanitize_text_field($_POST['type']) : NULL;
		$this->data['nonce']           = isset( $_POST['nonce'] ) ? esc_html( $_POST['nonce'] ) : NULL;
		$this->data['factor']          = isset( $_POST['factor'] ) ? sanitize_text_field($_POST['factor']) : NULL;
		$this->data['template']        = isset( $_POST['template'] ) ? sanitize_text_field($_POST['template']) : 'wpulike-default';
		$this->data['displayLikers']   = isset( $_POST['displayLikers'] ) ? sanitize_text_field($_POST['displayLikers']) : false;
		$this->data['disablePophover'] = isset( $_POST['disablePophover'] ) ? sanitize_text_field($_POST['disablePophover']) : false;
	}

	/**
	 * Update button
	 *
	 * @return void
	 */
	private function updateButton(){
		try {
			$this->beforeUpdateAction( $this->data );

			$this->settings_type = new wp_ulike_setting_type( $this->data['type'] );

			if ( !$this->validates() ){
				throw new \Exception( __( 'permission denied.', WP_ULIKE_SLUG ) );
			}

			if( empty( $this->settings_type->getType() ) ){
				throw new \Exception( __( 'Invalid item type.', WP_ULIKE_SLUG ) );
			}

			$process  = new wp_ulike_cta_process( array(
				'item_id'       => $this->data['id'],
				'item_type'     => $this->settings_type->getType(),
				'item_factor'   => $this->data['factor'],
				'item_template' => $this->data['template']
			) );

			if( wp_ulike_setting_repo::requireLogin( $this->settings_type->getType() ) && ! $this->user ){
				$this->response['message']      = wp_ulike_setting_repo::getLoginNotice();
				$this->response['status']       = 4;
				$this->response['requireLogin'] = true;
			} else {
				// Start process
				$has_permission = $process->update();
				// Check permission
				if( ! $has_permission ){
					$this->response['message']     = wp_ulike_setting_repo::getPermissionNotice();
					$this->response['status']      = 5;
					$this->response['messageType'] = 'warning';
				} else {
					$this->response['status'] = $process->getStatusCode();
					$counter_value = $process->getCounterValue();

					switch ( $this->response['status'] ){
						case 1:
							$this->response['message']     = wp_ulike_setting_repo::getLikeNotice();
							$this->response['messageType'] = 'success';
							$this->response['btnText'] = wp_ulike_setting_repo::getButtonText( $this->settings_type->getType(), 'like' );
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter_value, $this->data['id'] );
							break;
						case 2:
							$this->response['message']     = wp_ulike_setting_repo::getUnLikeNotice();
							$this->response['messageType'] = 'error';
							$this->response['btnText'] = wp_ulike_setting_repo::getButtonText( $this->settings_type->getType(), 'like' );
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_unliked_data', $counter_value, $this->data['id'] );
							break;
						case 3:
							$this->response['message']     = wp_ulike_setting_repo::getLikeNotice();
							$this->response['messageType'] = 'success';
							$this->response['btnText'] = wp_ulike_setting_repo::getButtonText( $this->settings_type->getType(), 'unlike' );
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_liked_data', $counter_value, $this->data['id'] );
							break;
						case 4:
							$this->response['message']     = wp_ulike_setting_repo::getLikeNotice();
							$this->response['messageType'] = 'success';
							$this->response['btnText'] = wp_ulike_setting_repo::getButtonText( $this->settings_type->getType(), 'like' );
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter_value, $this->data['id'] );
							break;
					}
				}
			}

			// Display likers
			if( $this->data['displayLikers'] && $this->user && ! in_array( $this->response['status'], array(4,5) ) ){
				$likers_selectors = wp_ulike_is_true( $this->data['disablePophover'] ) ? 'wp_ulike_likers_wrapper wp_ulike_display_inline' : 'wp_ulike_likers_wrapper';
				$this->response['likers'] = array(
					'class'    => $likers_selectors . sprintf( ' wp_%s_likers_%d', $this->settings_type->getType(), $this->data['id'] ) ,
					'template' => wp_ulike_get_likers_template(
						$this->settings_type->getTableName(),
						$this->settings_type->getColumnName(),
						$this->data['id'],
						$this->settings_type->getSettingKey()
					)
				);
			}

			// Display toasts condition
			$this->response['hasToast'] = wp_ulike_setting_repo::hasToast( $this->settings_type->getType() );

			// Hide data when counter is not visible
			if( ! wp_ulike_setting_repo::isCounterBoxVisible( $this->settings_type->getType() ) ){
				$this->response['data'] = NULL;
			}

			$response = apply_filters( 'wp_ulike_ajax_respond', $this->response, $this->data['id'], $this->response['status'], $process->getAjaxProcessAtts() );

			$this->afterUpdateAction( $process->getActionAtts() );

			$this->response( $response );
		} catch ( \Exception $e ){
			return $this->sendError( array(
				'message'     => $e->getMessage(),
				'messageType' => 'error',
				'hasToast'    => wp_ulike_setting_repo::hasToast( $this->settings_type->getType() )
			) );
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