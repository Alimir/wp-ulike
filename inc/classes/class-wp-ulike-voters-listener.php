<?php

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
		$this->data['id']              = isset( $_POST['id'] ) ? intval(sanitize_text_field($_POST['id'])) : NULL;
		$this->data['type']            = isset( $_POST['type'] ) ? sanitize_text_field($_POST['type']) : NULL;
		$this->data['nonce']           = isset( $_POST['nonce'] ) ? esc_html( $_POST['nonce'] ) : NULL;
		$this->data['displayLikers']   = isset( $_POST['displayLikers'] ) ? sanitize_text_field($_POST['displayLikers']) : false;
		$this->data['disablePophover'] = isset( $_POST['disablePophover'] ) ? sanitize_text_field($_POST['disablePophover']) : false;
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
				throw new \Exception( __( 'permission denied.', WP_ULIKE_SLUG ) );
			}

			if( empty( $this->settings_type->getType() ) ){
				throw new \Exception( __( 'Invalid item type.', WP_ULIKE_SLUG ) );
			}

			// Add specific class name with popover checkup
			$class_names   = wp_ulike_is_true( $this->data['disablePophover'] ) ? 'wp_ulike_likers_wrapper wp_ulike_display_inline' : 'wp_ulike_likers_wrapper';
			$template_name = wp_ulike_get_likers_template(
				$this->settings_type->getTableName(),
				$this->settings_type->getColumnName(),
				$this->data['id'],
				$this->settings_type->getSettingKey()
			);

			$this->afterGetListAction();

			$this->response( array(
				'template' => $template_name,
				'class'    => $class_names . sprintf( ' wp_%s_likers_%d', $this->settings_type->getType(), $this->data['id'] )
			) );

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
		// Return false when nonce invalid
		if( ! wp_verify_nonce( $this->data['nonce'], $this->data['type'] . $this->data['id'] ) && wp_ulike_is_cache_exist() ) return false;

		return true;
	}
}