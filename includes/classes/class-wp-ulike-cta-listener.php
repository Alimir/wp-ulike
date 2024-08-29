<?php
/**
 * WP ULike CTA Listener
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
		$this->setInfoData();
		$this->updateButton();
	}

	/**
	 * Set form data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['id']             = isset( $_POST['id'] ) ? absint($_POST['id']) : NULL;
		$this->data['type']           = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : NULL;
		$this->data['nonce']          = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : NULL;
		$this->data['factor']         = isset( $_POST['factor'] ) ? sanitize_text_field( wp_unslash( $_POST['factor'] ) ) : NULL;
		$this->data['template']       = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'wpulike-default';
		$this->data['displayLikers']  = isset( $_POST['displayLikers'] ) ? sanitize_text_field( wp_unslash( $_POST['displayLikers'] ) ) : false;
		$this->data['likersTemplate'] = isset( $_POST['likersTemplate'] ) ? sanitize_text_field( wp_unslash( $_POST['likersTemplate'] ) ) : 'popover';
	}

	/**
	 * Set more info data
	 *
	 * @return void
	 */
	private function setInfoData(){
		$this->data['client_address'] = wp_ulike_get_user_ip();
		// make filter for data args
		$this->data = apply_filters( 'wp_ulike_listener_data', $this->data );
	}

	/**
	 * Update button
	 *
	 * @return void
	 */
	private function updateButton(){
		try {
			// start actions
			$this->beforeUpdateAction( $this->data );
			// Validate inputs
			$this->validates();
			// get settings info
			$this->settings_type = new wp_ulike_setting_type( $this->data['type'] );

			if( empty( $this->settings_type->getType() ) ){
				throw new \Exception( esc_html__( 'Invalid item type.', 'wp-ulike' ) );
			}

			// Acquire lock
			$fp_lock = wp_ulike_acquire_lock( $this->data['type'], $this->data['id'] );
			if ( $fp_lock === false ) {
				throw new \Exception( esc_html__( 'Unable to obtain lock for this request.', 'wp-ulike' ) );
			}

			$process  = new wp_ulike_cta_process( array(
				'item_id'       => $this->data['id'],
				'item_type'     => $this->settings_type->getType(),
				'item_factor'   => $this->data['factor'],
				'item_template' => $this->data['template'],
				'user_ip'       => $this->data['client_address']
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
					$this->response['btnText']     = wp_ulike_setting_repo::getButtonText( $this->settings_type->getType(), 'unlike' );
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
							$this->response['messageType'] = 'info';
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
							$this->response['btnText'] = wp_ulike_setting_repo::getButtonText( $this->settings_type->getType(), 'unlike' );
							$this->response['data'] = apply_filters( 'wp_ulike_respond_for_no_limit_data', $counter_value, $this->data['id'] );
							break;
					}
				}
			}

			// Display likers
			if( $this->data['displayLikers'] && ( ! wp_ulike_setting_repo::restrictLikersBox( $this->settings_type->getType() ) || $this->user ) && ! in_array( $this->response['status'], array(4,5) ) ){
				$template = wp_ulike_get_likers_template(
					$this->settings_type->getTableName(),
					$this->settings_type->getColumnName(),
					$this->data['id'],
					$this->settings_type->getSettingKey(),
					array(
                		'style' => $this->data['likersTemplate']
            		)
				);
				$this->response['likers'] = ! empty( $template ) ? array(
					'template' => $this->data['likersTemplate'] != 'popover' ? $template :  sprintf(
					'<div class="wp_ulike_likers_wrapper wp_%s_likers_%s">%s</div>', $this->settings_type->getType(), $this->data['id'], $template )
				) : array( 'template' => '' );
			}

			// Display toasts condition
			$this->response['hasToast'] = wp_ulike_setting_repo::hasToast( $this->settings_type->getType() );

			// Hide data when counter is not visible
			if( ! wp_ulike_setting_repo::isCounterBoxVisible( $this->settings_type->getType() ) ){
				$this->response['data'] = '';
			}

			$response = apply_filters( 'wp_ulike_ajax_respond', $this->response, $this->data['id'], $this->response['status'], $process->getAjaxProcessAtts() );

			$this->afterUpdateAction( $process->getActionAtts() );

			wp_ulike_release_lock( $fp_lock, $this->data['type'], $this->data['id'] );
			// success
			$this->response( $response );

		} catch ( \Exception $e ){
			return $this->sendError( array(
				'message'     => $e->getMessage(),
				'messageType' => 'error',
				'hasToast'    => wp_ulike_setting_repo::hasToast( $this->data['type'] )
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
		if( empty( $this->data['id'] ) || empty( $this->data['type'] ) ){
			throw new \Exception( wp_ulike_setting_repo::getValidationNotice() );
		}
		// check blacklist
		if( ! wp_ulike_blacklist_validator::isValid( array( $this->data['client_address'] ) ) ){
			throw new \Exception( wp_ulike_setting_repo::getValidationNotice() );
		}
	}
}