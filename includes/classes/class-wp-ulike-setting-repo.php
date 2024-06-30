<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class wp_ulike_setting_repo {

	protected static function getOption( $key, $default = NULL ){
		$option = wp_ulike_get_option( $key );
		return $option != '' ? $option : $default;
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
		return wp_ulike_kses( self::getOption( 'login_required_notice', esc_html__( 'You Should Login To Submit Your Like', 'wp-ulike' ) ) );
	}

	/**
	 * Get validation message
	 *
	 * @return string
	 */
	public static function getValidationNotice(){
		return wp_ulike_kses( self::getOption( 'validate_notice', esc_html__( 'Your vote cannot be submitted at this time.', 'wp-ulike' ) ) );
	}

	/**
	 * Get permission message
	 *
	 * @return string
	 */
	public static function getPermissionNotice(){
		return wp_ulike_kses( self::getOption( 'already_registered_notice', esc_html__( 'You have already registered a vote.', 'wp-ulike' ) ) );
	}

	/**
	 * Get like notice message
	 *
	 * @return string
	 */
	public static function getLikeNotice(){
		return wp_ulike_kses( self::getOption( 'like_notice', esc_html__( 'Thanks! You Liked This.', 'wp-ulike' ) ) );
	}

	/**
	 * Get unlike notice message
	 *
	 * @return string
	 */
	public static function getUnLikeNotice(){
		return wp_ulike_kses( self::getOption( 'unlike_notice', esc_html__( 'Sorry! You unliked this.', 'wp-ulike' ) ) );
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
		return apply_filters( 'wp_ulike_button_text', wp_ulike_kses( $text_value ), $status, self::getSettingKey( $typeName ) );
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
	 * Get cookie expire time
	 *
	 * @return integer
	 */
	public static function getCookieExpiration( $typeName ){
		$expires = self::getOption( self::getSettingKey( $typeName ) . '|cookie_expires', 31536000 );
		return current_time( 'timestamp' ) + $expires;
	}

	/**
	 * Get the number of votes that can be submitted to one-per-person
	 *
	 * @return integer
	 */
	public static function getVoteLimitNumber( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|vote_limit_number', 50 );
	}

	/**
	 * Get anonymous user display type
	 *
	 * @return string
	 */
	public static function restrictLikersBox( $typeName ){
		$hide_anonymous_users = self::getOption( self::getSettingKey( $typeName ) . '|hide_likers_for_anonymous_users', false );
		return wp_ulike_is_true( $hide_anonymous_users ) && ! is_user_logged_in();
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
	 * Check toast display condition
	 *
	 * @return boolean
	 */
	public static function hasNotification( $typeName ){
		$enable_notice = self::getOption( 'buddypress_group|enable_add_notification', false );
		$filter_notice = self::getOption( 'buddypress_group|filter_user_notification_types', array() );
		return $enable_notice && ! in_array( $typeName, $filter_notice );
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
		$default = sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>', esc_html__('You need to login in order to like this post: ','wp-ulike'),
		wp_login_url( $current_url ),
		esc_html__('click here','wp-ulike')
		);
		// Setting template
		$template = self::getOption( self::getSettingKey( $typeName ) . '|login_template', $default );

		$template = str_replace( "%CURRENT_PAGE_URL%", $current_url, $template );

		return $template;
	}

	/**
	 * Deprecated function
	 *
	 * @return boolean
	 */
	public static function isCounterZeroVisible( $typeName ){
		return self::isCounterZeroHidden( $typeName );
	}

	/**
	 * Check counter zero visibility
	 *
	 * @return boolean
	 */
	public static function isCounterZeroHidden( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|hide_zero_counter', false );
	}

	/**
	 * Check auto display
	 *
	 * @return boolean
	 */
	public static function isAutoDisplayOn( $typeName ){
		$auto_display = self::getOption( self::getSettingKey( $typeName ) . '|enable_auto_display', true );
		return apply_filters( 'wp_ulike_enable_auto_display', $auto_display, $typeName );
	}

	/**
	 * Check activity comment display
	 *
	 * @return boolean
	 */
	public static function isActivityCommentAutoDisplayOn(){
		$auto_display = self::getOption( 'buddypress_group|enable_comments', false );
		return apply_filters( 'wp_ulike_enable_auto_display', $auto_display, 'activity_comment' );
	}

	/**
	 * Check WPML Synchronization
	 *
	 * @return boolean
	 */
	public static function isWpmlSynchronizationOn(){
		return self::getOption( 'posts_group|enable_wpml_synchronization', false );
	}

	/**
	 * Check counter zero visibility
	 *
	 * @return boolean
	 */
	public static function maybeHasUnitFormat( $number, $precision = 1 ){
		// Check for option enable
		if( self::getOption( 'enable_kilobyte_format', false ) && ( ! empty( $number ) || is_numeric( $number ) ) ){
			// Setup default $divisors if not provided
			$divisors = array(
				pow(1000, 0) => '', // 1000^0 == 1
				pow(1000, 1) => 'K', // Thousand
				pow(1000, 2) => 'M', // Million
				pow(1000, 3) => 'B'
			);

			// Loop through each $divisor and find the
			// lowest amount that matches
			foreach ($divisors as $divisor => $shorthand) {
				if (abs($number) < ($divisor * 1000)) {
					// We found a match!
					break;
				}
			}

			// We found our match, or there were no matches.
			// Either way, use the last defined value for $divisor.
			$number = round( $number / $divisor, $precision ) . $shorthand;
		}

		return $number;
	}


	/**
	 * Check distinct status by logging method
	 *
	 * @return boolean
	 */
	public static function maybeFilterCounterValue( $number, $status ){
		// retund if empty or not number
		if( empty( $number ) || ! is_numeric( $number ) ){
			return $number;
		}

		// Create filter args
		$filter_args = self::getOption( 'filter_counter_value', array(
			'like_prefix'       => '+',
			'dislike_prefix'    => '-',
			'undislike_prefix'  => '-',
			'like_postfix'      => '',
			'unlike_postfix'    => '',
			'dislike_postfix'   => '',
			'undislike_postfix' => ''
		) );

		// Maybe convert to unit format
		$number = self::maybeHasUnitFormat( $number );

		// If has no unit, format integer number
		if( is_numeric( $number ) ){
			$number = number_format_i18n( $number );
		}

		// Add prefix
		if( ! empty( $filter_args[ $status . '_prefix' ] ) ){
			$number = $filter_args[ $status . '_prefix' ] . $number;
		}
		// Add postfix
		if( ! empty( $filter_args[ $status . '_postfix' ] ) ){
			$number = $number . $filter_args[ $status . '_postfix' ];
		}

		return wp_ulike_kses( $number );
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

	/**
	 * get sanitized php code
	 *
	 * @return boolean
	 */
	public static function getPhpSnippets(){
		$code = self::getOption( 'php_snippets', '' );

		if( empty( $code ) ){
			return;
		}

		/* Remove <?php and <? from beginning of snippet */
		$code = preg_replace( '|^[\s]*<\?(php)?|', '', $code );

		/* Remove ?> from end of snippet */
		$code = preg_replace( '|\?>[\s]*$|', '', $code );

		return strval( $code );
	}

	/**
	 * get sanitized php code
	 *
	 * @return boolean
	 */
	public static function getJsSnippets(){
		$code = self::getOption( 'js_snippets', '' );

		if( empty( $code ) ){
			return;
		}

		/* Remove script from beginning of snippet */
		$code = preg_replace( '|^[\s]*<?(script)?>|', '', $code );

		/* Remove ?> from end of snippet */
		$code = preg_replace( '|\</script>[\s]*$|', '', $code );

		return strval( $code );
	}

	/**
	 * check code snippets availability status
	 *
	 * @return boolean
	 */
	public static function isCodeSnippetsDisabled(){
		return is_multisite() || ( defined('WP_ULIKE_DISABLE_CODE_SNIPPETS') && WP_ULIKE_DISABLE_CODE_SNIPPETS === true );
	}

	/**
	 * Check Anonymise Ip option enabled
	 *
	 * @return string
	 */
	public static function isAnonymiseIpOn(){
		return self::getOption( 'enable_anonymise_ip', false );
	}

	/**
	 * Check Anonymise Ip option enabled
	 *
	 * @return string
	 */
	public static function isIpLoggingOff(){
		return self::getOption( 'disable_ip_logging', false );
	}

	/**
	 * Check auto display filter
	 *
	 * @return array
	 */
	public static function getPostAutoDisplayFilters(){
		return self::getOption( 'posts_group|auto_display_filter', array( 'single', 'home' ) );
	}

	/**
	 * Check get post
	 *
	 * @return array
	 */
	public static function getPostTypesFilterList(){
		return self::getOption( 'posts_group|auto_display_filter_post_types', array( 'post' ) );
	}

}