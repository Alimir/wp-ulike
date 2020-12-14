<?php

class wp_ulike_setting_repo {

	protected static function getOption( $key, $default = NULL ){
		$option = wp_ulike_get_option( $key );
		return ! empty( $option ) ? $option : $default;
	}

	protected static function getSettingKey( $type ){
		$settings = new wp_ulike_setting_type( $type );
		return $settings->getSettingKey();
	}

	/**
	 * Get require login message
	 *
	 * @return string
	 */
	public static function getLoginNotice(){
		return self::getOption( 'login_required_notice', __( 'You Should Login To Submit Your Like', WP_ULIKE_SLUG ) );
	}

	/**
	 * Get permission message
	 *
	 * @return string
	 */
	public static function getPermissionNotice(){
		return self::getOption( 'already_registered_notice', __( 'You have already registered a vote.', WP_ULIKE_SLUG ) );
	}

	/**
	 * Get like notice message
	 *
	 * @return string
	 */
	public static function getLikeNotice(){
		return self::getOption( 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) );
	}

	/**
	 * Get unlike notice message
	 *
	 * @return string
	 */
	public static function getUnLikeNotice(){
		return self::getOption( 'unlike_notice', __( 'Sorry! You unliked this.', WP_ULIKE_SLUG ) );
	}

	/**
	 * Require Login?
	 *
	 * @return boolean
	 */
	public static function requireLogin( $typeName ){
		return wp_ulike_is_true( self::getOption( self::getSettingKey( $typeName ) . '|enable_only_logged_in_users', false ) );
	}

	/**
	 * Get button text
	 *
	 * @return string
	 */
	public static function getButtonText( $typeName, $status ){
		$text_group = self::getOption( self::getSettingKey( $typeName ) . '|text_group' );
		$text_value = ! empty( $text_group[$status] ) ? $text_group[$status] : ucfirst( $status );
		return apply_filters( 'wp_ulike_button_text', $text_value, $status, self::getSettingKey( $typeName ) );
	}

	/**
	 * Get logging method value
	 *
	 * @return string
	 */
	public static function getMethod( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|logging_method', 'by_username' );
	}

	/**
	 * Get anonymous user display type
	 *
	 * @return string
	 */
	public static function anonymousDisplay( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|logged_out_display_type', 'button' );
	}

	/**
	 * Get anonymous user display type
	 *
	 * @return string
	 */
	public static function restrictLikersBox( $typeName ){
		return wp_ulike_is_true(  self::getOption( self::getSettingKey( $typeName ) . '|hide_likers_for_anonymous_users', false ) );
	}

	/**
	 * Check counter value visibility
	 *
	 * @return boolean
	 */
	public static function isCounterBoxVisible( $typeName ){
		$display_condition = self::getOption( self::getSettingKey( $typeName ) . '|counter_display_condition', 'visible' );
		switch ( $display_condition ) {
			case 'hidden':
				return false;

			case 'logged_in_users':
				return is_user_logged_in();

			default:
				return true;
		}
	}

	/**
	 * Check toast display condition
	 *
	 * @return boolean
	 */
	public static function hasToast( $typeName ){
		$enable_toast = self::getOption( 'enable_toast_notice', true );
		$filter_toast = self::getOption( 'filter_toast_types', array() );
		return $enable_toast && ! in_array( $typeName, $filter_toast );
	}

	/**
	 * Get require login template
	 *
	 * @return boolean
	 */
	public static function getRequireLoginTemplate( $typeName ){
		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		// Default template
		$default = sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>', __('You need to login in order to like this post: ',WP_ULIKE_SLUG),
		wp_login_url( $current_url ),
		__('click here',WP_ULIKE_SLUG)
		);
		// Setting template
		$template = self::getOption( self::getSettingKey( $typeName ) . '|login_template', $default );

		$template = str_replace( "%CURRENT_PAGE_URL%", $current_url, $template );

		return $template;
	}

	/**
	 * Check counter zero visibility
	 *
	 * @return boolean
	 */
	public static function isCounterZeroVisible( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|hide_zero_counter', false );
	}


	/**
	 * Check distinct status by logging method
	 *
	 * @return boolean
	 */
	public static function isDistinct( $typeName ){
		switch ( self::getMethod( $typeName ) ) {
			case 'by_cookie':
			case 'do_not_log':
				return false;

			default:
				return true;
		}
	}

}