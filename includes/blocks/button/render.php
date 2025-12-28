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

// Detect if we're in a template builder context (REST API block rendering)
$is_template_context = WpUlikeInit::is_rest();

// Get current item ID dynamically based on type (if needed)
$current_id = false;
if ( $use_current ) {
	switch ( $for ) {
		case 'comment':
			// Use block context (Gutenberg-native approach, same as core comment blocks)
			// This is the ONLY reliable method in block templates
			if ( isset( $block ) && $block instanceof WP_Block ) {
				$comment_id = (int) ( $block->context['commentId'] ?? 0 );
				if ( $comment_id > 0 ) {
					$current_id = $comment_id;
				}
			}

			// Fallback: Try global $comment (for legacy themes/outside block context)
			if ( empty( $current_id ) && isset( $GLOBALS['comment'] ) && is_object( $GLOBALS['comment'] ) && isset( $GLOBALS['comment']->comment_ID ) ) {
				$comment_id = (int) $GLOBALS['comment']->comment_ID;
				if ( $comment_id > 0 ) {
					$current_id = $comment_id;
				}
			}

			// Last resort: get_comment_ID() (only works in traditional comment loops)
			if ( empty( $current_id ) && function_exists( 'get_comment_ID' ) ) {
				$comment_id = get_comment_ID();
				if ( $comment_id > 0 ) {
					$current_id = (int) $comment_id;
				}
			}
			break;

		default: // 'post'
			// Use block context (Gutenberg-native approach, same as core post blocks)
			// This is the most reliable method in block templates
			if ( isset( $block ) && $block instanceof WP_Block ) {
				$post_id = (int) ( $block->context['postId'] ?? 0 );
				if ( $post_id > 0 ) {
					$current_id = $post_id;
				}
			}

			// Fallback: Use wp_ulike_get_the_id() (works in traditional post loops)
			if ( empty( $current_id ) && function_exists( 'wp_ulike_get_the_id' ) ) {
				$post_id = wp_ulike_get_the_id();
				if ( ! empty( $post_id ) ) {
					$current_id = $post_id;
				}
			}
			break;
	}

	// If no current ID found and in template context, use placeholder
	if ( empty( $current_id ) && $is_template_context ) {
		$current_id = 1; // Static placeholder for template builder previews
	}
}

// Set final item ID: mix dynamic ID with custom ID if both exist, otherwise use one or the other
if ( $use_current && ! empty( $current_id ) ) {
	if ( ! empty( $item_id ) ) {
		// Mix: combine dynamic ID with custom ID (e.g., 12 + 123 = 12123)
		$shortcode_atts['id'] = (int) ( $current_id . $item_id );
	} else {
		// Use only dynamic ID
		$shortcode_atts['id'] = $current_id;
	}
} elseif ( ! empty( $item_id ) ) {
	// Use only custom ID
	$shortcode_atts['id'] = absint( $item_id );
} elseif ( $use_current && $is_template_context ) {
	// Fallback: use placeholder ID for template builder (only in editor context)
	$shortcode_atts['id'] = 1; // Static placeholder for template builder previews
}
// Note: If $use_current is true but $current_id is empty on frontend,
// we don't set 'id' attribute, letting the shortcode function handle it via get_comment_ID()

// Add template/style if provided
if ( ! empty( $template ) ) {
	$shortcode_atts['style'] = sanitize_text_field( $template );
}

// Add button type if provided
if ( ! empty( $button_type ) ) {
	$shortcode_atts['button_type'] = sanitize_text_field( $button_type );
}

// Build attributes string for shortcode
// Important: Don't include 'id' if it's 0 or empty (let shortcode handle it)
$shortcode_args = array();
foreach ( $shortcode_atts as $key => $value ) {
	// Skip 'id' attribute if it's 0, empty, or false (let shortcode function handle detection)
	if ( $key === 'id' && ( empty( $value ) || $value === 0 || $value === false ) ) {
		continue;
	}
	// Include attribute if it has a non-empty value
	if ( ! empty( $value ) ) {
		$shortcode_args[] = $key . '="' . esc_attr( $value ) . '"';
	}
}

// Generate shortcode
$shortcode = '[wp_ulike ' . implode( ' ', $shortcode_args ) . ']';

// Output the shortcode (it will be processed by WordPress)
echo do_shortcode( $shortcode );
