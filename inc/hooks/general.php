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

		if ( is_singular() && in_the_loop() && is_main_query() && wp_ulike_is_true( wp_ulike_get_option( 'posts_group|enable_auto_display', 1 ) ) ) {
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
	add_filter('the_content', 'wp_ulike_put_posts');
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
	add_filter( 'comment_text', 'wp_ulike_put_comments' );
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
		if( empty( $get_settings ) ) {
			return;
		}
		// Extract settings array
		extract( $get_settings );
		// Display likers box
		echo $args['disable_pophover'] && $args['display_likers'] ? sprintf(
			'<div class="wp_ulike_likers_wrapper wp_ulike_display_inline">%s</div>',
			wp_ulike_get_likers_template( $table, $column, $args['ID'], $setting )
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

if( ! function_exists( 'wp_ulike_update_meta_data_info' ) ){
	/**
	 * Upgrade meta data
	 *
	 * @param integer $ID
	 * @param string $type
	 * @param integer $user_ID
	 * @param string $status
	 * @param boolean $has_log
	 * @param string $slug
	 * @param string $table
	 * @return void
	 */
	function wp_ulike_update_meta_data_info( $ID, $type, $user_ID, $status, $has_log, $slug, $table, $is_distinct ){

		// Update total stats
		if( ( ! $has_log || ! $is_distinct ) && strpos( $status, 'un') === false ){
			global $wpdb;
			// update all logs period
			$wpdb->query( "
					UPDATE `{$wpdb->prefix}ulike_meta`
					SET `meta_value` = (`meta_value` + 1)
					WHERE `meta_group` = 'statistics' AND `meta_key` = 'count_logs_period_all'
			" );
			$wpdb->query( "
					UPDATE `{$wpdb->prefix}ulike_meta`
					SET `meta_value` = (`meta_value` + 1)
					WHERE `meta_group` = 'statistics' AND `meta_key` = 'count_logs_for_{$table}_table_in_all_daterange'
			" );
		}

		// Update likers list
		$get_likers = wp_ulike_get_meta_data( $ID, $slug, 'likers_list', true );
		if( ! empty( $get_likers ) ){
			$get_user   = get_userdata( $user_ID );
			$is_updated = false;
			if( $get_user ){
				if( in_array( $get_user->ID, $get_likers ) ){
					if( strpos( $status, 'un') !== false ){
						$get_likers = array_diff( $get_likers, array( $get_user->ID ) );
						$is_updated = true;
					}
				} else {
					if( strpos( $status, 'un') === false ){
						array_push( $get_likers, $get_user->ID );
						$is_updated = true;
					}
				}
				// If array list has been changed, then update meta data.
				if( $is_updated ){
					wp_ulike_update_meta_data( $ID, $slug, 'likers_list', $get_likers );
				}
			}
		}

		// Delete object cache
		if( wp_ulike_is_cache_exist() ){
			wp_cache_delete( 'calculate_new_votes', WP_ULIKE_SLUG );
			wp_cache_delete( 'count_logs_period_all', WP_ULIKE_SLUG );
			wp_cache_delete( 1, 'wp_ulike_statistics_meta' );
		}

	}
	add_action( 'wp_ulike_after_process', 'wp_ulike_update_meta_data_info'	, 10, 8 );
}

// @if DEV
// function wp_ulike_pro_custom_hreview( $content ){
// 	global $post;

// 	if( is_singular() && 0 != ( $counter = wp_ulike_get_post_likes( $post->ID ) ) ){
// 			$content .= sprintf('<div style="display:none" class="hreview-aggregate">
// 							<div class=item>
// 							<p class="fn">%s</p>
// 							</div>
// 							<span class=rating>%s</span>
// 							<span class=count>%s</span>
// 					</div>',
// 					$post->post_title,
// 					wp_ulike_get_rating_value( $post->ID ),
// 					$counter
// 			);
// 	}

// 	return $content;
// }
// add_filter('the_content', 'wp_ulike_pro_custom_hreview');
// @endif
