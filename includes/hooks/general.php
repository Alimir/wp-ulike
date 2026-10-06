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
	 * Uses standard WordPress conditional tags to scope the auto-insert to
	 * the main loop on the frontend, outside of feeds and embeds.
	 *
	 * @param string $content
	 * @since 1.0
	 * @return string
	 */
	function wp_ulike_put_posts( $content ) {
		// Auto-display is off, or we're outside the main frontend loop.
		// Query Loop blocks use their own query, so they are handled in
		// wp_ulike_render_block_auto_display() instead of here.
		if ( ! wp_ulike_auto_display_context_allows_button() || ! in_the_loop() || ! is_main_query() ) {
			return apply_filters( 'wp_ulike_the_content', $content, $content );
		}

		// Excerpts / list snippets follow their own setting, which defaults to on
		// (see wp_ulike_setting_repo::isAutoDisplayOnExcerpts).
		if ( 'the_excerpt' === current_filter() && ! wp_ulike_setting_repo::isAutoDisplayOnExcerpts() ) {
			return apply_filters( 'wp_ulike_the_content', $content, $content );
		}

		// Post type limits for the classic loop stay inside is_wp_ulike().
		$output = wp_ulike_place_auto_button( $content, wp_ulike( 'put' ) );

		return apply_filters( 'wp_ulike_the_content', $output, $content );
	}
	add_filter( 'the_content', 'wp_ulike_put_posts', 15 );
	add_filter( 'the_excerpt', 'wp_ulike_put_posts', 15 );
}

if ( ! function_exists( 'wp_ulike_auto_display_context_allows_button' ) ) {
	/**
	 * Shared front-end gates for automatic like buttons.
	 *
	 * @return bool
	 */
	function wp_ulike_auto_display_context_allows_button() {
		if ( ! WpUlikeInit::is_frontend() || is_feed() || is_embed() ) {
			return false;
		}

		// isAutoDisplayOn() runs wp_ulike_enable_auto_display, which Pro Display
		// Automation uses to turn the free button off when a rule replaces it.
		if ( ! wp_ulike_setting_repo::isAutoDisplayOn( 'post' ) ) {
			return false;
		}

		return (bool) is_wp_ulike( wp_ulike_setting_repo::getPostAutoDisplayFilters() );
	}
}

if ( ! function_exists( 'wp_ulike_auto_display_allows_post' ) ) {
	/**
	 * Whether this post type is allowed to receive an automatic button.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	function wp_ulike_auto_display_allows_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 ) {
			return false;
		}

		$post_types = wp_ulike_setting_repo::getPostTypesFilterList();
		if ( empty( $post_types ) ) {
			return true;
		}

		return in_array( (string) get_post_type( $post_id ), array_map( 'strval', $post_types ), true );
	}
}

if ( ! function_exists( 'wp_ulike_place_auto_button' ) ) {
	/**
	 * Place a button against content using the saved position.
	 *
	 * @param string $content Post or excerpt HTML.
	 * @param string $button  Button HTML.
	 * @return string
	 */
	function wp_ulike_place_auto_button( $content, $button ) {
		if ( ! is_string( $button ) || '' === $button ) {
			return $content;
		}

		switch ( wp_ulike_get_option( 'posts_group|auto_display_position', 'bottom' ) ) {
			case 'top':
				return $button . $content;

			case 'top_bottom':
				return $button . $content . $button;

			default:
				return $content . $button;
		}
	}
}

if ( ! function_exists( 'wp_ulike_markup_has_non_gallery_button' ) ) {
	/**
	 * Whether HTML already contains a like button that is not a gallery image button.
	 *
	 * Gallery buttons use wpulike-gallery and must not hide the post button.
	 *
	 * @param string $content HTML.
	 * @return bool
	 */
	function wp_ulike_markup_has_non_gallery_button( $content ) {
		if ( ! is_string( $content ) || ! preg_match_all( '/class=(["\'])([^"\']*\bwpulike\b[^"\']*)\1/', $content, $matches ) ) {
			return false;
		}

		foreach ( $matches[2] as $classes ) {
			if ( false === strpos( $classes, 'wpulike-gallery' ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'wp_ulike_render_block_auto_display' ) ) {
	/**
	 * Buttons for block lists and Gallery images.
	 *
	 * Classic loops still use the_content / the_excerpt. A Query Loop is not the
	 * main query, and the Excerpt block never calls the_excerpt, so those need
	 * this path. Gallery images are opt-in and keep one count per attachment.
	 *
	 * @param string        $content  Rendered block HTML.
	 * @param array         $block    Parsed block.
	 * @param WP_Block|null $instance Block instance, when WordPress provides it.
	 * @return string
	 */
	function wp_ulike_render_block_auto_display( $content, $block, $instance = null ) {
		if ( ! is_string( $content ) || ! is_array( $block ) || empty( $block['blockName'] ) ) {
			return $content;
		}

		if ( 'core/image' === $block['blockName'] ) {
			return wp_ulike_render_gallery_image_button( $content, $block, $instance );
		}

		if ( ! in_array( $block['blockName'], array( 'core/post-content', 'core/post-excerpt' ), true ) ) {
			return $content;
		}

		if ( ! wp_ulike_auto_display_context_allows_button() ) {
			return $content;
		}

		$post_id = (int) get_the_ID();
		if ( ! wp_ulike_auto_display_allows_post( $post_id ) ) {
			return $content;
		}

		// The main loop already inserted the button through the_content.
		if ( 'core/post-content' === $block['blockName'] && in_the_loop() && is_main_query() ) {
			return $content;
		}

		// Home, archives, and search are the list views people asked for.
		// A related-posts query on a single article is a different post, and
		// stays off so one page does not grow a button under every teaser.
		$is_list_context = is_front_page() || is_home() || is_archive() || is_search();
		if ( ! $is_list_context && is_singular() && (int) get_queried_object_id() !== $post_id ) {
			return $content;
		}

		if ( 'core/post-excerpt' === $block['blockName'] ) {
			// The article itself already gets a button from the content. Excerpt
			// blocks are for the list, where the_excerpt never runs.
			if ( ! $is_list_context || ! wp_ulike_setting_repo::isAutoDisplayOnExcerpts() ) {
				return $content;
			}
		}

		// Pro Display Automation can already have inserted its button through
		// the_content. A gallery button inside the post does not count.
		if ( wp_ulike_markup_has_non_gallery_button( $content ) ) {
			return $content;
		}

		$output = wp_ulike_place_auto_button( $content, wp_ulike( 'put', array( 'id' => $post_id ) ) );

		return apply_filters( 'wp_ulike_the_content', $output, $content );
	}
	add_filter( 'render_block', 'wp_ulike_render_block_auto_display', 20, 3 );
}

if ( ! function_exists( 'wp_ulike_render_gallery_image_button' ) ) {
	/**
	 * Append a like button to one image inside a Gallery block.
	 *
	 * The Gallery block sidebar stores this choice. Existing galleries have no
	 * attribute, so they render exactly as before. Pro's "Like Buttons on Images"
	 * uses wp_get_attachment_image and is left alone; if that markup is already
	 * in the image, this does not add a second button.
	 *
	 * @param string        $content  Rendered image HTML.
	 * @param array         $block    Parsed image block.
	 * @param WP_Block|null $instance Block instance.
	 * @return string
	 */
	function wp_ulike_render_gallery_image_button( $content, $block, $instance = null ) {
		if ( ! WpUlikeInit::is_frontend() || is_feed() || is_embed() || is_admin() ) {
			return $content;
		}

		$enabled = ( $instance instanceof WP_Block ) && ! empty( $instance->context['wpUlikeGallery'] );
		if ( ! $enabled ) {
			return $content;
		}

		// data-id is set only on images nested in a Gallery. A normal Image block never has it.
		$attachment_id = isset( $block['attrs']['data-id'] ) ? (int) $block['attrs']['data-id'] : 0;
		if ( $attachment_id < 1 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return $content;
		}

		// Pro appends its button through wp_get_attachment_image. Same image, one button.
		if ( false !== strpos( $content, 'wpulike' ) ) {
			return $content;
		}

		$button = wp_ulike(
			'put',
			array(
				'id'            => $attachment_id,
				'wrapper_class' => 'wpulike-gallery',
			)
		);

		if ( ! is_string( $button ) || '' === $button ) {
			return $content;
		}

		if ( preg_match( '/<\/figcaption>/i', $content ) ) {
			$updated = preg_replace_callback(
				'/<\/figcaption>/i',
				static function () use ( $button ) {
					return '</figcaption>' . $button;
				},
				$content,
				1
			);
		} elseif ( preg_match( '/<\/figure>/i', $content ) ) {
			$updated = preg_replace_callback(
				'/<\/figure>/i',
				static function () use ( $button ) {
					return $button . '</figure>';
				},
				$content,
				1
			);
		} else {
			$updated = $content . $button;
		}

		return is_string( $updated ) ? $updated : $content;
	}
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


if( ! function_exists( 'wp_ulike_load_deprecated_classes' ) ){
	/**
	 * Load deprecated classes for backward compatibility
	 *
	 * @return void
	 */
	function wp_ulike_load_deprecated_classes(){
		require_once( WP_ULIKE_ADMIN_DIR . '/includes/deprecated.class.php');
	}
	add_action( 'plugins_loaded', 'wp_ulike_load_deprecated_classes', 999 );
}

/**
 * Safety net for the request-level user-state memos.
 *
 * updateUserMetaStatus() already flushes on the normal vote path; this covers
 * callers that change a user's status by editing the meta directly (e.g. the
 * Pro REST endpoint removing a vote) and then fire wp_ulike_after_process.
 */
add_action( 'wp_ulike_after_process', 'wp_ulike_flush_user_state_cache', 1 );


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
		if ( wp_ulike_use_pulse_queries() ) {
			$comment_ids = get_comments(
				array(
					'post_id' => $ID,
					'fields'  => 'ids',
					'number'  => 0,
				)
			);
			if ( ! empty( $comment_ids ) ) {
				foreach ( $comment_ids as $comment_id ) {
					wp_ulike_delete_vote_data( $comment_id, 'comment' );
				}
			}
			return;
		}

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
