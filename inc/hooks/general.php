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

// //show it on single media page
// add_action( 'mpp_media_meta', 'mpp_wpulike_show_button' );
// //show it in lightbox
// add_action( 'mpp_lightbox_media_meta', 'mpp_wpulike_show_button' );

// function mpp_wpulike_show_button() {
// 	echo wp_ulike('put');
// }

// function wp_ulike_pro_update_counter_value_text( $value, $id, $type, $status ){
//     return in_array( $status, array( 'like', 'liked') ) ?  $value . ' Likes' : $value . ' Dislikes' ;
// }
// add_filter( 'wp_ulike_counter_value', 'wp_ulike_pro_update_counter_value_text', 4, 20 );

/*
function wp_ulike_pro_activity_comment_filter( $content, $wp_ulike_query, $query_args ){
	global $wpdb;

	if ( is_multisite() ) {
		$bp_prefix = 'base_prefix';
	} else {
		$bp_prefix = 'prefix';
	}

	// generate query string
	$query  = sprintf( '
		SELECT r.*, COUNT(t.activity_id) AS likesCounter
		FROM %sulike_activities t
		INNER JOIN %sbp_activity r ON t.activity_id = r.id
		WHERE t.status = "like" AND r.type = "activity_comment"
		GROUP BY t.activity_id
		ORDER BY likesCounter
		DESC LIMIT 0, 10',
		$wpdb->prefix,
		$wpdb->$bp_prefix
	);

	$wp_ulike_query = $wpdb->get_results( $query );

	ob_start();
	// The Loop
	if ( $wp_ulike_query ) {
		echo '<div class="wp-ulike-pro-single-activity ulp-flex-row ulp-flex-middle-xs">';
		// Start Loop
		foreach ( $wp_ulike_query as $activity ) {
		$activity_permalink = bp_activity_get_permalink( $activity->id );
		$activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
		$activity_author    = get_user_by( 'id', $activity->user_id );
	?>
			<div class="wp-ulike-pro-item-container ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12 wp-ulike-pro-item-col">
				<div class="wp-ulike-pro-content-wrapper">
					<div class="wp-ulike-pro-item-desc">
						<a href="<?php echo esc_url( $activity_permalink ); ?>"><?php echo $activity_action; ?></a>
					</div>
					<div class="wp-ulike-pro-item-info">
						<div class="wp-ulike-entry-date">
							<i class="ulp-icon-clock"></i>
							<span><?php echo date_i18n( get_option( 'date_format', 'F j, Y' ), strtotime( $activity->date_recorded ) ); ?></span>
						</div>
						<div class="wp-ulike-entry-author">
							<i class="ulp-icon-torso"></i>
							<span><?php echo esc_html( $activity_author->display_name ); ?></span>
						</div>
						<div class="wp-ulike-entry-votes">
							<?php
							$is_distinct = wp_ulike_setting_repo::isDistinct('activity');
							$likes       = wp_ulike_get_counter_value( $activity->id, 'activity', 'like', $is_distinct  );
							$dislikes    = wp_ulike_get_counter_value( $activity->id, 'activity', 'dislike', $is_distinct );

							if( ! empty( $likes ) ){ ?>
							<span class="wp-ulike-up-votes">
								<i class="ulp-icon-like"></i>
								<span><?php echo $likes; ?></span>
							</span>
							<?php }
							if( ! empty( $dislikes ) ){ ?>
							<span class="wp-ulike-down-votes">
								<i class="ulp-icon-dislike"></i>
								<span><?php echo $dislikes;?></span>
							</span>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
	<?php
		}
		// End Loop
		echo '</div>';
	}

	return ob_get_clean();
}
add_filter( 'wp_ulike_pro_content_template_for_post_type', 'wp_ulike_pro_activity_comment_filter', 3, 20 );*/



// function wp_ulike_pro_custom_stop_listener(){
// 	date_default_timezone_set('Europe/Berlin');
// 	$current_time = date("h:i a");
// 	$begin = "09:00 am";
// 	$end   = "11:00 pm";

// 	$date1 = DateTime::createFromFormat('H:i a', $current_time);
// 	$date2 = DateTime::createFromFormat('H:i a', $begin);
// 	$date3 = DateTime::createFromFormat('H:i a', $end);

// 	if ( $date1 > $date2 && $date1 < $date3 ) {
// 		throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'invalidtime', 'sample permission message' ) );
// 	}
// }
// add_action( 'wp_ulike_before_process', 'wp_ulike_pro_custom_stop_listener' );

/*
function wpulike_custom_activity_shortcode ( $atts ) {
	global $wpdb;

	if ( is_multisite() ) {
		$bp_prefix = 'base_prefix';
	} else {
		$bp_prefix = 'prefix';
	}

	// Default Args
	$parsed_args   = shortcode_atts( array(
		"period"         => 'all'
	), $atts );

	$wp_ulike_query = NULL;
	$is_distinct	= wp_ulike_setting_repo::isDistinct('activity');

	$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );
	// generate query string
	$query  = sprintf( '
		SELECT r.*, COUNT(t.activity_id) AS likesCounter
		FROM %sulike_activities t
		INNER JOIN %sbp_activity r ON t.activity_id = r.id
		WHERE t.status = "like" AND r.type NOT LIKE "wp_like_group" %s
		GROUP BY t.activity_id
		ORDER BY likesCounter
		DESC LIMIT 0, 15',
		$wpdb->prefix,
		$wpdb->$bp_prefix,
		$period_limit
	);

	$wp_ulike_query = $wpdb->get_results( $query );

	ob_start();
	// The Loop
	if ( $wp_ulike_query ) {

		print '<ul class="nmwt-wpulike-custom-shortcode">';

	    foreach ( $wp_ulike_query as $activity ) {

		    $activity_permalink = bp_activity_get_permalink( $activity->id );

			if ($activity->type == 'new_blog_post') {
				list ($x, $activity_action)	= explode(",", $activity->action);
				$activity_action = ltrim ($activity_action);
			}
			else {
			    $activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
			}
			$activity_action	= strip_tags($activity_action);
			if (strlen($activity_action)>200) { $activity_action	= substr($activity_action,0,200)."…"; }

		    $activity_author    = get_user_by( 'id', $activity->user_id );
			$activity_author_	= $activity_author->first_name ." ".$activity_author->last_name;

			switch ($activity->type) {
				case 'activity_update': {
					if ($activity->component == 'groups') { $activity_type = 'Group Update'; }
					else { $activity_type = 'Activity Update'; }
					break;
				}
				case 'new_blog_post': $activity_type = 'New Post'; break;
				case 'activity_comment': $activity_type = 'Comment'; break;
				case 'new_blog_comment': $activity_type = 'Comment'; break;
				defaut: $activity_type = $activity->type;
			}
	?>
			<li>
				<a href="<?php echo esc_url( $activity_permalink ); ?>"><?php echo $activity_action; ?></a>
				<span class="wp_counter_span"><?php echo wp_ulike_get_counter_value( $activity->id, 'activity', 'like', $is_distinct, $parsed_args['period'] ); ?></span>
				<br>
				<span class="byline"><?php echo $activity_type; ?> by <?php echo $activity_author_; ?> on <?php echo date_i18n( get_option( 'date_format', 'F j, Y' ), strtotime( $activity->date_recorded ) ); ?></span>
			</li>

	<?php
		}
		print '</ul>';
	}

	return ob_get_clean();
}

add_shortcode ('wpulike_custom_activity_shortcode', 'wpulike_custom_activity_shortcode');

function wp_ulike_hide_couter_when_zero( $args ){

	if( empty( $args['total_likes'] ) && empty( $args['total_dislikes'] ) ){
		$args['counter'] = '';
	}

	return $args;
}
add_filter( 'wp_ulike_add_templates_args', 'wp_ulike_hide_couter_when_zero', 10, 1);
*/
// @endif