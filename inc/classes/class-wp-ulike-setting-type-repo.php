<?php

class wp_ulike_setting_type_repo {

	private $key, $options, $typeSettings;

	function __construct( $type ){
		$this->setTypeSettings( $type );
		$this->setKey();
		$this->setOptions();
	}

	private function setTypeSettings( $type ){
		$this->typeSettings = wp_ulike_get_post_settings_by_type( $type );
	}

	private function setKey(){
		$this->key = !empty( $this->typeSettings['setting'] ) ? $this->typeSettings['setting'] : NULL;
	}

	public function getType(){
		return ! empty( $this->typeSettings['slug'] ) ? $this->typeSettings['slug'] : NULL;
	}

	public function getCookieName(){
		return ! empty( $this->typeSettings['cookie'] ) ? $this->typeSettings['cookie'] : NULL;
	}

	private function setOptions(){
		$this->options = wp_ulike_get_option( $this->key );
	}

	private function getOption( $key, $default = NULL ){
		return ! empty( $this->options[$key] ) ? $this->options[$key] : $default;
	}

	/**
	* Require Login?
	*
	* @return boolean
	*/
	public function requireLogin(){
		return wp_ulike_is_true( $this->getOption( 'only_logged_in_users', false ) );
	}

	public function getLoginNotice(){
		return $this->getOption( 'login_required_notice', _( 'You Should Login To Submit Your Like', WP_ULIKE_SLUG ) );
	}

	public function getPermissionNotice(){
		return $this->getOption( 'already_registered_notice', __( 'You have already registered a vote.', WP_ULIKE_SLUG ) );
	}

	public function getLikeNotice(){
		return $this->getOption( 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) );
	}

	public function getUnLikeNotice(){
		return $this->getOption( 'unlike_notice', __( 'Sorry! You unliked this.', WP_ULIKE_SLUG ) );
	}

	public function getButtonText( $status ){
		$text_group = $this->getOption( 'text_group' );
		return apply_filters( 'wp_ulike_button_text', $text_group[$status], $status, $this->key );
	}

	public function getMethod(){
		return $this->getOption( 'logging_method', 'by_username' );
	}

	public function anonymousDisplay(){
		return $this->getOption( 'logged_out_display_type', 'button' );
	}

}