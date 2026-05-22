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

$attributes    = isset( $attributes ) && is_array( $attributes ) ? $attributes : array();
$wrapper_class = isset( $wrapperClass ) ? $wrapperClass : '';

echo WP_Ulike_Top_Content_Renderer::render( $attributes, $wrapper_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
