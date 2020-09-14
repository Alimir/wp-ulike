<?php
/**
 * Shortcodes
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'wp_ulike_shortcode' ) ){
	/**
	 * Create shortcode: [wp_ulike]
	 *
	 * @param array $atts
	 * @param string $content
	 * @return void
	 */
	function wp_ulike_shortcode( $atts, $content = null ){
		// Final result
		$result = '';
		// Default Args
		$args   = shortcode_atts( array(
			"for"           => 'post',	// shortcode Type (post, comment, activity, topic)
			"id"            => '',		// Item ID
			"slug"          => 'post',	// Slug Name
			"style"         => '',		// Get Default Theme
			"button_type"   => '',		// Set Button Type ('image' || 'text')
			"attributes"    => '',		// Get Attributes Filter
			"wrapper_class" => ''		// Extra Wrapper class
		), $atts );

		switch ( $args['for'] ) {
			case 'comment':
				$result = $content . wp_ulike_comments( 'put', array_filter( $args ) );
				break;

			case 'activity':
				$result = $content . wp_ulike_buddypress( 'put', array_filter( $args ) );
				break;

			case 'topic':
				$result = $content . wp_ulike_bbpress( 'put', array_filter( $args ) );
				break;

			default:
				$result = $content . wp_ulike( 'put', array_filter( $args ) );
		}

		return $result;
	}
	add_shortcode( 'wp_ulike', 'wp_ulike_shortcode' );
}

if( ! function_exists( 'wp_ulike_counter_shortcode' ) ){
	/**
	 * Create shortcode: [wp_ulike_counter]
	 *
	 * @param   array	$atts
	 * @param   string	$content
	 *
	 * @return  string
	 */
	function wp_ulike_counter_shortcode( $atts, $content = null ){
		// Final result
		$result = '';
		// Default Args
		$args   = shortcode_atts( array(
			"id"          => '',
			"type"        => 'post',
			"status"      => 'like',
			"date_range"  => ''
		), $atts );

		if( empty( $args['id'] ) ){
			switch ( $args['type'] ) {
				case 'comment':
					$args['id'] = get_comment_ID();
					break;

				case 'activity':
					if( function_exists( 'bp_get_activity_comment_id' ) ){
						$args['id'] = bp_get_activity_comment_id() !== NULL ? bp_get_activity_comment_id() : bp_get_activity_id();
					}
					break;

				default:
					$args['id'] = get_the_ID();
					break;
			}
		}

		$is_distinct = wp_ulike_setting_repo::isDistinct( $args['type'] );

		return wp_ulike_get_counter_value( $args['id'], $args['type'], $args['status'], $is_distinct, $args['date_range'] );
	}
	add_shortcode( 'wp_ulike_counter', 'wp_ulike_counter_shortcode' );
}

if( ! function_exists( 'wp_ulike_likers_shortcode' ) ){
	/**
	 * Create shortcode: [wp_ulike_likers_shortcode]
	 *
	 * @param array $atts
	 * @param string $content
	 * @return void
	 */
	function wp_ulike_likers_shortcode( $atts, $content = null ){
		// Final result
		$result = '';
		// Default Args
		$args   = shortcode_atts( array(
			"id"          => '',
			"type"        => 'post',
			"counter"     => 10,
			"template"    => '',
			"avatar_size" => 64
		), $atts );

		if( empty( $args['id'] ) ){
			switch ( $args['type'] ) {
				case 'comment':
					$args['id'] = get_comment_ID();
					break;

				case 'activity':
					if( function_exists( 'bp_get_activity_comment_id' ) ){
						$args['id'] = bp_get_activity_comment_id() !== NULL ? bp_get_activity_comment_id() : bp_get_activity_id();
					}
					break;

				default:
					$args['id'] = get_the_ID();
					break;
			}
		}

		$get_settings = wp_ulike_get_post_settings_by_type( $args['type'] );

		// If method not exist, then return error message
		if( empty( $get_settings ) || empty( $args['id'] ) ) {
			return __( 'Error receiving input parameters', WP_ULIKE_SLUG );
		}

		if( ! empty( $args['template']  ) ){
			$args['template'] = html_entity_decode( $args['template']  );
		}

		return apply_filters( 'wp_ulike_likers_shortcode', sprintf( '<div class="wp_ulike_manual_likers_wrapper wp_%s_likers_%d">%s</div>', $args['type'], $args['id'],
		wp_ulike_get_likers_template( $get_settings['table'], $get_settings['column'], $args['id'], $get_settings['setting'], $args ) ), $args['id'], $args['type'] );
	}
	add_shortcode( 'wp_ulike_likers', 'wp_ulike_likers_shortcode' );
}