<?php

final class wp_ulike_voters_listener extends wp_ulike_ajax_listener_base {

	private $user;
	private $response = array(
		'message'     => NULL,
		'btnText'     => NULL,
		'messageType' => 'info',
		'status'      => 0,
		'data'        => NULL
	);

	public function __construct()
	{
		parent::__construct();
		$this->setFormData();
		$this->setUser();
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
		$this->data['refresh']         = isset( $_POST['refresh'] ) ? sanitize_text_field($_POST['refresh']) : false;
		$this->data['displayLikers']   = isset( $_POST['displayLikers'] ) ? sanitize_text_field($_POST['displayLikers']) : false;
		$this->data['disablePophover'] = isset( $_POST['disablePophover'] ) ? sanitize_text_field($_POST['disablePophover']) : false;
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
	 * Get likers list info
	 *
	 * @return void
	 */
	private function getList(){
		try {
			$this->beforeGetListAction();

			if ( !$this->validates() ){
				throw new \Exception( __( 'Invalid format.', WP_ULIKE_SLUG ) );
			}

			$settings = new wp_ulike_setting_type_repo( $this->data['type'] );

			if( empty( $settings->getType() ) ){
				throw new \Exception( __( 'Invalid item type.', WP_ULIKE_SLUG ) );
			}

			// Add specific class name with popover checkup
			$class_names   = wp_ulike_is_true( $this->data['disablePophover'] ) ? 'wp_ulike_likers_wrapper wp_ulike_display_inline' : 'wp_ulike_likers_wrapper';
			$template_name = wp_ulike_get_likers_template(
				$settings->getTableName(),
				$settings->getColumnName(),
				$this->data['id'],
				$settings->getOptionKey()
			);

			$this->afterGetListAction();

			$this->response( array(
				'template' => $template_name,
				'class'    => $class_names
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
		// Return false display is off
		if( ! $this->data['displayLikers'] ) return false;
		// Return false when nonce invalid
		if( ! wp_verify_nonce( $this->data['nonce'], $this->data['type'] . $this->data['id'] ) && wp_ulike_is_cache_exist() ) return false;
		// Don't refresh likers data, when user is not logged in.
		if( $this->data['refresh'] && ! $this->user ) return false;

		return true;
	}
}