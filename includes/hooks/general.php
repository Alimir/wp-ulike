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
		if ( WpUlikeInit::is_frontend() && in_the_loop() && is_main_query() && wp_ulike_setting_repo::isAutoDisplayOn('post') ) {
			if(	is_wp_ulike( wp_ulike_setting_repo::getPostAutoDisplayFilters() ) ){
				// Get button
				$button = wp_ulike('put');
				switch ( wp_ulike_get_option( 'posts_group|auto_display_position', 'bottom' ) ) {
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
	add_filter( 'the_excerpt', 'wp_ulike_put_posts', 15 );
}

/*******************************************************
  Comments Auto Display
*******************************************************/

if( ! function_exists( 'wp_ulike_put_comments' ) ){
	/**
	 * Auto insert wp_ulike_comments in the comments content
	 *
	 * @param string $content
	 * @param object $com
	 * @return string
	 */
	function wp_ulike_put_comments( $content, $comment = null ) {
		// Stack variable
		$output = $content;

		/**
		 * Don't append like dislike when links are being checked
		 */
		if( isset($_REQUEST['comment']) ){
			return $content;
		}

		/**
		 * Don't implement on admin section
		 */
		if( WpUlikeInit::is_admin_backend() && ! WpUlikeInit::is_ajax() ){
			return $content;
		}

		if ( wp_ulike_setting_repo::isAutoDisplayOn('comment') && WpUlikeInit::is_frontend() && isset( $comment->comment_ID ) ) {
			//auto display position
			$position = wp_ulike_get_option( 'comments_group|auto_display_position', 'bottom' );
			//add wp_ulike function
			$button   = wp_ulike_comments( 'put', array(
				'id' => $comment->comment_ID
			) );
			// Check position
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

		return apply_filters( 'wp_ulike_comment_text', $output, $content, $comment );
	}
	add_filter( 'comment_text', 'wp_ulike_put_comments', 15, 2 );
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
		// Return if likers is hidden
		if( empty( $args['display_likers'] ) ){
			return;
		}
		// Get settings for current type
		$get_settings = wp_ulike_get_post_settings_by_type( $args['type'] );
		// If method not exist, then return error message
		if( wp_ulike_setting_repo::restrictLikersBox( $args['type'] ) || empty( $get_settings ) || empty( $args['ID'] ) ) {
			return;
		}
		// Extract settings array - assign explicitly per WordPress coding standards
		$table = isset( $get_settings['table'] ) ? $get_settings['table'] : '';
		$column = isset( $get_settings['column'] ) ? $get_settings['column'] : '';
		$setting = isset( $get_settings['setting'] ) ? $get_settings['setting'] : '';

		if( $args['disable_pophover'] || $args['likers_style'] == 'default' ){
			echo sprintf(
			'<div class="wp_ulike_likers_wrapper wp_%s_likers_%s">%s</div>',
			esc_attr($args['type']), esc_attr( $args['ID'] ), wp_ulike_get_likers_template( $table, $column, $args['ID'], $setting, array( 'style' => 'default' ) ) );
		}

		do_action( 'wp_ulike_inline_display_likers_box', $args, $get_settings );
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
			$return_style .= '.wp_ulike_btn.wp_ulike_put_image:after { background-image: url('.esc_url($image_group['like']).') !important; }';
		}
		if( isset( $image_group['unlike'] ) && ! empty( $image_group['unlike'] ) ) {
			$return_style .= '.wp_ulike_btn.wp_ulike_put_image.wp_ulike_btn_is_active:after { background-image: url('.esc_url($image_group['unlike']).') !important; filter:none; }';
		}
		if( isset( $image_group['dislike'] ) && ! empty( $image_group['dislike'] ) ) {
			$return_style .= '.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image:after { background-image: url('.esc_url($image_group['dislike']).') !important; }';
		}
		if( isset( $image_group['undislike'] ) && ! empty( $image_group['undislike'] ) ) {
			$return_style .= '.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image.wp_ulike_btn_is_active:after { background-image: url('.esc_url($image_group['undislike']).') !important; filter:none; }';
		}

		echo !empty( $return_style ) ? sprintf( '<style>%s</style>', wp_strip_all_tags( $return_style ) ) : '';
	}
	add_action( 'wp_ulike_inside_template', 'wp_ulike_update_button_icon', 1 );
}

if( ! function_exists( 'wp_ulike_deprecated_csf_class' ) ){
	/**
	 * Require deprecated CSF class
	 *
	 * @return void
	 */
	function wp_ulike_deprecated_csf_class(){
		// include _deprecated settings panel
		require_once( WP_ULIKE_ADMIN_DIR . '/includes/deprecated.class.php');
	}
	add_action( 'plugins_loaded', 'wp_ulike_deprecated_csf_class' );
}


if( ! function_exists( 'wp_ulike_run_php_snippets' ) ){
	/**
	 * Run php snippets
	 *
	 * @return void
	 */
	function wp_ulike_run_php_snippets(){
		if( wp_ulike_setting_repo::isCodeSnippetsDisabled() ){
			return;
		}

		$php_snippets = wp_ulike_setting_repo::getPhpSnippets();

		if( empty( $php_snippets ) ){
			return;
		}

		if ( class_exists( '\\ParseError' ) ) {
			try {
				eval( $php_snippets ); // phpcs:ignore
			} catch( \ParseError $e ) { // phpcs:ignore
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'WP ULike PHP Snippet Error: ' . $e->getMessage() );
				}
			}
		} else {
			eval( $php_snippets ); // phpcs:ignore
		}
	}
	add_action( 'wp_ulike_loaded', 'wp_ulike_run_php_snippets' );
}

if( ! function_exists( 'wp_ulike_run_javascript_snippets' ) ){
	/**
	 * Run js snippets
	 *
	 * @return void
	 */
	function wp_ulike_run_javascript_snippets(){
		if( wp_ulike_setting_repo::isCodeSnippetsDisabled() ){
			return;
		}

		$js_snippets = wp_ulike_setting_repo::getJsSnippets();

		if( empty( $js_snippets ) ){
			return;
		}

		$js_snippets = trim( $js_snippets, "\n" );

		printf( "<script type='text/javascript' id='%s'>\n%s\n</script>\n", 'wp_ulike_js_snippets', $js_snippets );

	}
	add_action( 'wp_footer', 'wp_ulike_run_javascript_snippets', 100 );
}

if( ! function_exists( 'wp_ulike_delete_post_votes' ) ){
	/**
	 * Fires after the activity item has been deleted.
	 *
	 * @param array $args
	 * @return void
	 */
	function wp_ulike_delete_post_votes( $ID ) {
		global $wpdb, $post_type;
		$type = in_array( $post_type, array('forum','topic','reply') ) ? 'topic' : 'post';

		// delete post votes
		wp_ulike_delete_vote_data( $ID, $type );

		// don't check comments for bbpress
		if( $type == 'topic' ){
			return;
		}

		// delete comments if exist
		$comments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				c.comment_ID
				FROM
					$wpdb->comments c
					INNER JOIN {$wpdb->prefix}ulike_comments uc ON c.comment_ID = uc.comment_id
				WHERE
					c.comment_post_ID = %d
					GROUP BY c.comment_ID",
				$ID
			)
		);

		if( ! empty( $comments ) ){
			foreach ($comments as $comment_ID) {
				wp_ulike_delete_vote_data( $comment_ID, 'comment' );
			}
		}
	}
	add_action( 'before_delete_post', 'wp_ulike_delete_post_votes', 1, 10 );
}

if( ! function_exists( 'wp_ulike_delete_comment_votes' ) ){
	/**
	 * Fires after the comment item has been deleted.
	 *
	 * @param integer $ID
	 * @return void
	 */
	function wp_ulike_delete_comment_votes( $ID ) {
		wp_ulike_delete_vote_data( $ID, 'comment' );
	}
	add_action( 'deleted_comment', 'wp_ulike_delete_comment_votes', 1, 10 );
}


if( ! function_exists( 'wp_ulike_delete_activity_votes' ) ){
	/**
	 * Fires after the activity item has been deleted.
	 *
	 * @param array $args
	 * @return void
	 */
	function wp_ulike_delete_activity_votes( $args ){
		if( ! empty( $args['id'] ) ){
			wp_ulike_delete_vote_data( $args['id'], 'activity' );
		}
	}
	add_action( 'bp_activity_delete', 'wp_ulike_delete_activity_votes', 1, 10 );
}
// @if DEV
function wp_ulike_add_cors_http_header() {
    header("Access-Control-Allow-Origin: *"); // Allow all origins
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow specific methods
    header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers
}
add_action('init', 'wp_ulike_add_cors_http_header');
// @endif

// @if DEV
// function replace_core_jquery_version() {
//     wp_deregister_script( 'jquery' );
//     // Change the URL if you want to load a local copy of jQuery from your own server.
//     wp_register_script( 'jquery', "https://code.jquery.com/jquery-3.5.1.min.js", array(), false );
// }
// add_action( 'wp_enqueue_scripts', 'replace_core_jquery_version' );

// function wp_ulike_pro_custom_pre_archive( $query ) {

//     /* only proceed on the front end */
//     if( is_admin() ) {
// 	    return;
//     }

//     /* only on the person post archive for the main query */
//     if ( ( is_category() || is_tag() || is_archive() ) && $query->is_main_query() ) {
// 		$post__in = wp_ulike_get_popular_items_ids(array(
// 			'rel_type' => $query->get('post_type'),
// 			'status'   => 'like',
// 			"order"    => $query->get('order'),
// 			"offset"   => $query->get('paged'),
// 			"limit"    => $query->get('posts_per_page')
// 		));

// 		$query->set( 'post__in', $post__in );
// 		$query->set( 'orderby', 'post__in' );
//     }

// }
// add_action( 'pre_get_posts', 'wp_ulike_pro_custom_pre_archive' );
// @endif