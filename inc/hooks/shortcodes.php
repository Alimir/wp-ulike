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
	 * @author Alimir
	 * @param array $atts
	 * @param string $content
	 * @since 1.4
	 * @return void
	 */
	function wp_ulike_shortcode( $atts, $content = null ){
		// Final result
		$result = '';
		// Default Args
		$args   = shortcode_atts( array(
			"for"           => 'post',	// shortcode Type (post, comment, activity, topic)
			"id"            => '',		// Post ID
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