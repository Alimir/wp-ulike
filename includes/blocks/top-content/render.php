<?php
/**
 * Top Content block server-side render.
 *
 * @package WP_ULike
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die( 'No Naughty Business Please !' );
}

require_once dirname( __FILE__ ) . '/class-top-content-renderer.php';

$context = isset( $context ) && is_array( $context ) ? $context : array();

$attributes    = isset( $context['attributes'] ) && is_array( $context['attributes'] ) ? $context['attributes'] : array();
$wrapper_class = isset( $context['wrapperClass'] ) ? $context['wrapperClass'] : '';

echo WP_Ulike_Top_Content_Renderer::render( $attributes, $wrapper_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
