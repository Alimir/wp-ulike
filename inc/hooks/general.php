<?php
/**
 * General Hooks
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Post Type Auto Display
*******************************************************/

if( ! function_exists( 'wp_ulike_put_posts' ) ){
	/**
	 * Auto insert wp_ulike function in the posts/pages content
	 *
	 * @param string $content
	 * @since 1.0
	 * @return string
	 */
	function wp_ulike_put_posts( $content ) {
		// Stack variable
		$output = $content;

		if ( in_the_loop() && is_main_query() && wp_ulike_is_true( wp_ulike_get_option( 'posts_group|enable_auto_display', 1 ) ) ) {
			//auto display position
			$position = wp_ulike_get_option( 'posts_group|auto_display_position', 'bottom' );

			if(	!is_feed() && is_wp_ulike( wp_ulike_get_option( 'posts_group|auto_display_filter' ) ) ){
				// Get button
				$button = wp_ulike('put');
				switch ($position) {
					case 'top':
						$output = $button . $content;
						break;

					case 'top_bottom':
						$output = $button . $content . $button;
						break;

					default:
						$output = $content . $button;
						break;
				}
			}
		}

		return apply_filters( 'wp_ulike_the_content', $output, $content );
	}
	add_filter( 'the_content', 'wp_ulike_put_posts', 15 );
}

/*******************************************************
  Comments Auto Display
*******************************************************/

if( ! function_exists( 'wp_ulike_put_comments' ) ){
	/**
	 * Auto insert wp_ulike_comments in the comments content
	 *
	 * @since 1.6
	 * @param string $content
	 * @return string
	 */
	function wp_ulike_put_comments( $content ) {
		// Stack variable
		$output = $content;

		if ( wp_ulike_is_true( wp_ulike_get_option( 'comments_group|enable_auto_display', 1 ) ) && is_singular() && comments_open() ) {
			//auto display position
			$position = wp_ulike_get_option( 'comments_group|auto_display_position', 'bottom' );
			//add wp_ulike function
			$button   = wp_ulike_comments('put');
			switch ($position) {
				case 'top':
					$output = $button . $content;
					break;

				case 'top_bottom':
					$output = $button . $content . $button;
					break;

				default:
					$output = $content . $button;
					break;
			}
		}

		return apply_filters( 'wp_ulike_comment_text', $output, $content );
	}
	add_filter( 'comment_text', 'wp_ulike_put_comments', 15 );
}

/*******************************************************
  Other
*******************************************************/

if( ! function_exists( 'wp_ulike_register_widget' ) ){
	/**
	 * Register WP ULike Widgets
	 *
	 * @author Alimir
	 * @since 1.2
	 * @return Void
	 */
	function wp_ulike_register_widget() {
		register_widget( 'wp_ulike_widget' );
	}
	add_action( 'widgets_init', 'wp_ulike_register_widget' );
}

if( ! function_exists( 'wp_ulike_generate_microdata' ) ){
	/**
	 * Generate rich snippet hooks
	 *
	 * @param array $args
	 * @return string
	 */
	function wp_ulike_generate_microdata( $args ){
		// Bulk output
		$output = '';

		// Check ulike type
		switch ( $args['type'] ) {
			case 'likeThis':
				$output = apply_filters( 'wp_ulike_posts_microdata', null );
				break;

			case 'likeThisComment':
				$output = apply_filters( 'wp_ulike_comments_microdata', null );
				break;

			case 'likeThisActivity':
				$output = apply_filters( 'wp_ulike_activities_microdata', null );
				break;

			case 'likeThisTopic':
				$output = apply_filters( 'wp_ulike_topics_microdata', null );
				break;
		}

		echo $output;
	}
	add_action( 'wp_ulike_inside_template', 'wp_ulike_generate_microdata' );
}

if( ! function_exists( 'wp_ulike_display_inline_likers_template' ) ){
	/**
	 * Display inline likers box without AJAX request
	 *
	 * @param array $args
	 * @since 3.5.1
	 * @return void
	 */
	function wp_ulike_display_inline_likers_template( $args ){
		// Get settings for current type
		$get_settings     = wp_ulike_get_post_settings_by_type( $args['type'] );
		// If method not exist, then return error message
		if( wp_ulike_setting_repo::restrictLikersBox( $args['type'] ) || empty( $get_settings ) || empty( $args['ID'] ) ) {
			return;
		}
		// Extract settings array
		extract( $get_settings );
		// Display likers box
		echo $args['disable_pophover'] && $args['display_likers'] ? sprintf(
			'<div class="wp_ulike_likers_wrapper wp_ulike_display_inline wp_%s_likers_%s">%s</div>',
			$args['type'], $args['ID'], wp_ulike_get_likers_template( $table, $column, $args['ID'], $setting )
		) : '';
	}
	add_action( 'wp_ulike_inside_template', 'wp_ulike_display_inline_likers_template' );
}

if( ! function_exists( 'wp_ulike_update_button_icon' ) ){
	/**
	 * Update button icons
	 *
	 * @param array $args
	 * @return void
	 */
	function wp_ulike_update_button_icon( $args ){
		$button_type  = wp_ulike_get_option( $args['setting'] . '|button_type' );
		$image_group  = wp_ulike_get_option( $args['setting'] . '|image_group' );
		$return_style = null;

		// Check value
		if( $button_type !== 'image' || empty( $image_group ) || ! in_array( $args['style'], array( 'wpulike-default', 'wp-ulike-pro-default', 'wpulike-heart' ) ) ){
			return;
		}

		if( isset( $image_group['like'] ) && ! empty( $image_group['like'] ) ) {
			$return_style .= '.wp_ulike_btn.wp_ulike_put_image:after { background-image: url('.$image_group['like'].') !important; }';
		}
		if( isset( $image_group['unlike'] ) && ! empty( $image_group['unlike'] ) ) {
			$return_style .= '.wp_ulike_btn.wp_ulike_put_image.wp_ulike_btn_is_active:after { background-image: url('.$image_group['unlike'].') !important; filter:none; }';
		}
		if( isset( $image_group['dislike'] ) && ! empty( $image_group['dislike'] ) ) {
			$return_style .= '.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image:after { background-image: url('.$image_group['dislike'].') !important; }';
		}
		if( isset( $image_group['undislike'] ) && ! empty( $image_group['undislike'] ) ) {
			$return_style .= '.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image.wp_ulike_btn_is_active:after { background-image: url('.$image_group['undislike'].') !important; filter:none; }';
		}

		echo !empty( $return_style ) ? sprintf( '<style>%s</style>', $return_style ) : '';
	}
	add_action( 'wp_ulike_inside_template', 'wp_ulike_update_button_icon', 1 );
}

/**
 * Hide ajax data when counter zero
 *
 * @param integer $counterValue
 * @param integer $id
 * @param string $slug
 * @param string $status
 * @param boolean $is_distinct
 * @return integer|string
 */
function wp_ulike_hide_couter_box_ajax_when_zero( $counterValue, $id, $slug, $status, $is_distinct ){
	// Check zero function
	if( wp_ulike_setting_repo::isCounterZeroVisible( $slug ) ){
		if( is_array( $counterValue ) ){
			$counterValue['sub']  = ($counterValue['up'] - $counterValue['down']) == 0 ? '' : $counterValue['up'] - $counterValue['down'];
			$counterValue['up']   = empty( $counterValue['up'] ) ? '' : $counterValue['up'];
			$counterValue['down'] = empty( $counterValue['down'] ) ? '' : $counterValue['down'];
		} elseif( empty( $counterValue ) ) {
			$counterValue = '';
		}
	}

	return $counterValue;
}

/**
 * Hide counter args
 *
 * @param array $args
 * @return array
 */
function wp_ulike_hide_couter_box_when_zero( $args ){
	// Check zero function
	if( wp_ulike_setting_repo::isCounterZeroVisible( $args['slug'] ) ){
		if( isset( $args['total_likes'] ) ){
			$args['total_likes']    = empty( $args['total_likes'] ) ? '' : $args['total_likes'];
		}
		if( isset( $args['total_dislikes'] ) ){
			$args['total_dislikes'] = empty( $args['total_dislikes'] ) ? '' : $args['total_dislikes'];
		}
	}

	return $args;
}

/**
 * Hide counter box template
 *
 * @param string $string
 * @param integer $counter
 * @param string $slug
 * @return string
 */
function wp_ulike_hide_count_box_template( $string, $counter, $slug ) {
	// Check zero function
	if( wp_ulike_setting_repo::isCounterZeroVisible( $slug ) ){
		// Check counter value
		$value = preg_replace("/[^0-9,.]/", "", $counter);
		if( ! $value ){
			return '<span class="count-box"></span>';
		}
	}

	return  $string;
}

/**
 * Init plugins loaded hook
 *
 * @return void
 */
function wp_ulike_init_plugins_loaded_hook(){
    add_filter( 'wp_ulike_ajax_counter_value', 'wp_ulike_hide_couter_box_ajax_when_zero', 5, 15 );
    add_filter( 'wp_ulike_add_templates_args', 'wp_ulike_hide_couter_box_when_zero', 10, 1);
	add_filter('wp_ulike_count_box_template', 'wp_ulike_hide_count_box_template', 10, 3);
}
add_action( 'plugins_loaded', 'wp_ulike_init_plugins_loaded_hook' );

// @if DEV
// function replace_core_jquery_version() {
//     wp_deregister_script( 'jquery' );
//     // Change the URL if you want to load a local copy of jQuery from your own server.
//     wp_register_script( 'jquery', "https://code.jquery.com/jquery-3.5.1.min.js", array(), false );
// }
// add_action( 'wp_enqueue_scripts', 'replace_core_jquery_version' );
// @endif