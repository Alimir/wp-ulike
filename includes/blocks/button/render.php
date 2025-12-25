<?php
/**
 * WP ULike Block Server-Side Render
 * 
 * This file handles the server-side rendering of the WP ULike block
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Block attributes
 * Variables are extracted from $attributes array in the render callback
 */
// Set defaults if not provided
$for           = isset( $for ) ? $for : 'post';
$itemId        = isset( $itemId ) ? $itemId : '';
$useCurrentPostId = isset( $useCurrentPostId ) ? (bool) $useCurrentPostId : true;
$template      = isset( $template ) ? $template : '';
$buttonType    = isset( $buttonType ) ? $buttonType : '';
$wrapperClass  = isset( $wrapperClass ) ? $wrapperClass : '';

// Map to shortcode parameter names
$item_id = $itemId;
$use_current = $useCurrentPostId;
$button_type = $buttonType;
$wrapper_class = $wrapperClass;

// Prepare the attributes array for the shortcode function
$shortcode_atts = array(
	'for'           => $for,
	'slug'          => $for,
	'wrapper_class' => $wrapper_class
);

// Set item ID - use current post ID if enabled and no custom ID provided
if ( ! empty( $item_id ) ) {
	$shortcode_atts['id'] = absint( $item_id );
} elseif ( $use_current && function_exists( 'wp_ulike_get_the_id' ) ) {
	// Try to get current post ID dynamically
	$current_id = wp_ulike_get_the_id();
	if ( ! empty( $current_id ) ) {
		$shortcode_atts['id'] = $current_id;
	}
}

// Add template/style if provided
if ( ! empty( $template ) ) {
	$shortcode_atts['style'] = sanitize_text_field( $template );
}

// Add button type if provided
if ( ! empty( $button_type ) ) {
	$shortcode_atts['button_type'] = sanitize_text_field( $button_type );
}

// Build attributes string for shortcode
$shortcode_args = array();
foreach ( $shortcode_atts as $key => $value ) {
	if ( ! empty( $value ) || $value === 0 ) {
		$shortcode_args[] = $key . '="' . esc_attr( $value ) . '"';
	}
}

// Generate shortcode
$shortcode = '[wp_ulike ' . implode( ' ', $shortcode_args ) . ']';

// Output the shortcode (it will be processed by WordPress)
echo do_shortcode( $shortcode );
