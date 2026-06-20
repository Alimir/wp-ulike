<?php
/**
 * Admin Hooks
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Add WP ULike CopyRight in footer
 *
 * @author       	Alimir
 * @param           string $text
 * @since           2.0
 * @return			string
 */
function wp_ulike_copyright( $text ) {
	if ( ! wp_ulike_is_plugin_screen() ) {
		return $text;
	}

	$url = add_query_arg(
		array(
			'utm_source'   => 'footer-link',
			'utm_campaign' => 'wp-ulike',
			'utm_medium'   => 'wp-dash',
		),
		WP_ULIKE_PLUGIN_URI
	);

	$link = sprintf(
		'<a href="%1$s" title="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
		esc_url( $url ),
		esc_attr( WP_ULIKE_NAME ),
		esc_html( WP_ULIKE_NAME )
	);

	return sprintf(
		'<span id="footer-thankyou">%1$s %2$s</span>',
		esc_html__( 'Proudly Powered By', 'wp-ulike' ),
		$link
	);
}
add_filter( 'admin_footer_text', 'wp_ulike_copyright' );


 /**
  * The Filter is used at the very end of the get_avatar() function
  *
  * @param string $avatar Image tag for the user's avatar.
  * @return string $avatar
  */
function wp_ulike_remove_photo_class($avatar) {
	return str_replace(' photo', ' gravatar', $avatar);
}
add_filter('get_avatar', 'wp_ulike_remove_photo_class');

/**
 * On user logged out
 *
 * @return void
 */
function wp_ulike_on_logout_hook() {
	if ( ! is_super_admin() ) {
		return;
	}
	// Refresh new votes
	wp_ulike_update_meta_data( 1, 'statistics', 'calculate_new_votes', 0 );
}
add_action('wp_logout', 'wp_ulike_on_logout_hook');

/**
 *  Undocumented function
 *
 * @since 3.6.0
 * @param integer $count
 * @return integer $count
 */
function wp_ulike_update_menu_badge_count( $count ) {
	if( 0 !== ( $count_new_likes = wp_ulike_get_number_of_new_likes() ) ){
		$count += $count_new_likes;
	}
	return $count;
}
add_filter( 'wp_ulike_menu_badge_count', 'wp_ulike_update_menu_badge_count' );


/**
 * Update the admin sub menu title
 *
 * @since 3.6.0
 * @param string $title
 * @param string $menu_slug
 * @return string $title
 */
function wp_ulike_update_admin_sub_menu_title( $title, $menu_slug ) {
	if( ( 0 !== ( $count_new_likes = wp_ulike_get_number_of_new_likes() ) ) && $menu_slug === 'wp-ulike-statistics' ){
		$title .=  wp_ulike_badge_count_format( $count_new_likes );
	}
	return $title;
}
add_filter( 'wp_ulike_admin_sub_menu_title', 'wp_ulike_update_admin_sub_menu_title', 10, 2 );

/**
 * Admin notice controller
 *
 * @return void
 */
function wp_ulike_notice_manager(){

	// Display notices only for admin users
	if( !current_user_can( 'manage_options' ) ){
		return;
	}

	$notice_list = [];

	// Show review notice once the site has meaningful engagement (likes only).
	if ( ! wp_ulike_get_transient( 'wp-ulike-notice-wp_ulike_leave_a_review' ) ) {
		$cached_count = wp_ulike_get_meta_data( 1, 'statistics', 'count_logs_period_all', true );
		$count_logs   = is_numeric( $cached_count ) ? absint( $cached_count ) : wp_ulike_count_all_logs();

		if ( $count_logs >= 25 ) {
			$notice_list['wp_ulike_leave_a_review'] = new wp_ulike_notices(
				array(
					'id'          => 'wp_ulike_leave_a_review',
					'title'       => esc_html__( 'Congrats!', 'wp-ulike' ),
					'description' => sprintf(
						/* translators: %s: total like count */
						esc_html__( 'You\'ve logged %s likes with WP ULike so far. Nice work! If the plugin\'s been good to you, we\'d love a 5-star review on WordPress.org. It helps us keep improving and helps others discover WP ULike too.', 'wp-ulike' ),
						'<strong>' . number_format_i18n( $count_logs ) . '</strong>'
					),
					'skin'        => 'default',
					'has_close'   => true,
					'buttons'     => array(
						array(
							'label'      => esc_html__( 'Happy to help', 'wp-ulike' ),
							'link'       => 'https://wordpress.org/support/plugin/wp-ulike/reviews/#new-post',
							'color_name' => 'default',
						),
						array(
							'label'      => esc_html__( 'Maybe later', 'wp-ulike' ),
							'type'       => 'skip',
							'color_name' => 'default',
							'expiration' => WEEK_IN_SECONDS * 2,
						),
						array(
							'label'      => esc_html__( 'Don\'t ask again', 'wp-ulike' ),
							'type'       => 'skip',
							'color_name' => 'default',
							'expiration' => YEAR_IN_SECONDS * 10,
						),
					),
				)
			);
		}
	}

	// Show discount notice for Pro users with invalid/expired/disabled licenses
	// Only show if user has entered a license key (don't show for users who haven't activated yet)
	if( defined( 'WP_ULIKE_PRO_VERSION' ) && class_exists( 'WP_Ulike_Pro_Validator' ) && class_exists( 'WP_Ulike_Pro_API' ) ){
		// Check if user has entered a license key
		$license_key = get_option( 'wp_ulike_pro_license_key', '' );

		// Only show notice if license key exists (user has tried to activate)
		if( ! empty( $license_key ) ){
			// Get license status using the validator class
			$license_status = WP_Ulike_Pro_Validator::get_license_status();

			// Show notice for users with invalid, expired, disabled, deactivated, or missing licenses
			$invalid_license_statuses = [
				WP_Ulike_Pro_API::STATUS_INVALID,
				WP_Ulike_Pro_API::STATUS_EXPIRED,
				WP_Ulike_Pro_API::STATUS_DISABLED,
				WP_Ulike_Pro_API::STATUS_DEACTIVATED,
				WP_Ulike_Pro_API::STATUS_SITE_INACTIVE,
				WP_Ulike_Pro_API::STATUS_MISSING,
				WP_Ulike_Pro_API::STATUS_HTTP_ERROR,
			];

			// Show notice if license status is invalid, expired, or nulled
			if( $license_status !== false && $license_status !== null && in_array( $license_status, $invalid_license_statuses, true ) ){
				$status_message = esc_html__( 'Your license needs attention', 'wp-ulike' );

				if( $license_status === WP_Ulike_Pro_API::STATUS_EXPIRED ){
					$status_message = esc_html__( 'Your license has expired', 'wp-ulike' );
				} elseif( $license_status === WP_Ulike_Pro_API::STATUS_DISABLED ){
					$status_message = esc_html__( 'Your license has been disabled', 'wp-ulike' );
				} elseif( $license_status === WP_Ulike_Pro_API::STATUS_INVALID || $license_status === WP_Ulike_Pro_API::STATUS_MISSING ){
					$status_message = esc_html__( 'Your license needs attention', 'wp-ulike' );
				} elseif( $license_status === WP_Ulike_Pro_API::STATUS_HTTP_ERROR ){
					$status_message = esc_html__( 'Unable to verify your license', 'wp-ulike' );
				}

				$notice_list['wp_ulike_pro_license_discount'] = new wp_ulike_notices(
					array(
						'id'          => 'wp_ulike_pro_license_discount',
						'title'       => sprintf(
							esc_html__( 'Special offer: save %s on WP ULike Pro', 'wp-ulike' ),
							'30%'
						),
						'description' => sprintf(
							'<strong>%s</strong> ' . esc_html__( 'We noticed your license needs a quick update. Renew with code %s to save %s on premium features, updates, and support.', 'wp-ulike' ),
							$status_message,
							'<code style="background: #fff3cd; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px;">GET30OFF</code>',
							'30%'
						),
						'has_close'   => false,
						'skin'        => 'default',
						'buttons'     => array(
							array(
								'label'      => sprintf(
									esc_html__( 'Claim %s discount', 'wp-ulike' ),
									'30%'
								),
								'link'       => add_query_arg(
									array(
										'utm_source'   => 'license-discount-notice',
										'utm_campaign' => '30off',
										'utm_medium'   => 'wp-dash',
										'discount'     => '30OFF',
									),
									WP_ULIKE_PLUGIN_URI . 'pricing/'
								),
								'color_name' => 'default',
							),
							array(
								'label'      => esc_html__( 'Maybe Later', 'wp-ulike' ),
								'type'       => 'skip',
								'color_name' => 'info',
								'expiration' => WEEK_IN_SECONDS,
							),
						),
					)
				);
			}
		}
	}

    $notice_list = apply_filters( 'wp_ulike_admin_notices_instances', $notice_list );

    foreach ( $notice_list as $notice ) {
        if( $notice instanceof wp_ulike_notices ){
            $notice->render();
        }
    }
}
add_action( 'admin_notices', 'wp_ulike_notice_manager' );

/**
 * Register Go Pro submenu (free installs only).
 * Primary always-on upgrade path — in-dashboard upsells appear after 50 total engagements.
 *
 * @return void
 */
function wp_ulike_register_go_pro_submenu() {
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) ) {
		return;
	}

	add_submenu_page(
		'wp-ulike-settings',
		esc_html__( 'Go Pro', 'wp-ulike' ),
		esc_html__( 'Go Pro', 'wp-ulike' ),
		'manage_options',
		'wp-ulike-go-pro',
		'__return_null'
	);
}
add_action( 'admin_menu', 'wp_ulike_register_go_pro_submenu', 100 );

/**
 * Redirect Go Pro admin page to pricing (Elementor uses wp_redirect, not wp_safe_redirect).
 *
 * @return void
 */
function wp_ulike_go_pro_menu_redirect() {
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( $_GET['page'] ) || 'wp-ulike-go-pro' !== $_GET['page'] ) {
		return;
	}

	wp_redirect( add_query_arg(
		array(
			'utm_source'   => 'wp-menu',
			'utm_campaign' => 'gopro',
			'utm_medium'   => 'wp-dash',
		),
		WP_ULIKE_PLUGIN_URI . 'upgrade/'
	) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	exit;
}
add_action( 'admin_init', 'wp_ulike_go_pro_menu_redirect' );

/**
 * Go Pro menu: open upgrade page in a new tab (same pattern as Elementor admin.js).
 *
 * @return void
 */
function wp_ulike_go_pro_submenu_scripts() {
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) ) {
		return;
	}

	$upgrade_url = add_query_arg(
		array(
			'utm_source'   => 'wp-menu',
			'utm_campaign' => 'gopro',
			'utm_medium'   => 'wp-dash',
		),
		WP_ULIKE_PLUGIN_URI . 'upgrade/'
	);
	?>
	<script>
	(function () {
		var upgradeUrl = <?php echo wp_json_encode( $upgrade_url ); ?>;
		var links = document.querySelectorAll('#adminmenu a[href*="page=wp-ulike-go-pro"]');

		links.forEach(function (link) {
			link.setAttribute('target', '_blank');
			link.setAttribute('rel', 'noopener noreferrer');
			link.addEventListener('click', function (event) {
				event.preventDefault();
				window.open(upgradeUrl, '_blank', 'noopener,noreferrer');
			});
		});
	})();
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'wp_ulike_go_pro_submenu_scripts' );

/**
 * Disable admin notices
 * @param array $notice_list
 * @return array|null
 */
function wp_ulike_hide_admin_notifications( $notice_list ){
	$screen = get_current_screen();

	if ( ! $screen ) {
		return $notice_list;
	}

	$hide_admin_notice = wp_ulike_get_option( 'disable_admin_notice', false );
	return wp_ulike_is_true( $hide_admin_notice ) && strpos( $screen->base, WP_ULIKE_SLUG ) === false ? array() : $notice_list;
}
add_filter( 'wp_ulike_admin_notices_instances', 'wp_ulike_hide_admin_notifications', 20, 1 );


/**
 * Display custom column content
 *
 * @param   array  		$column
 * @param   integer  	$post_id
 *
 * @return  void
 */
function wp_ulike_manage_posts_custom_column( $column, $post_id ) {
    if ( $column === 'wp-ulike-thumbs-up' ){
		$is_distinct = wp_ulike_setting_repo::isDistinct('post');
		$post_id     = wp_ulike_get_the_id( $post_id );
        echo sprintf( '<span class="wp-ulike-counter-box">%d</span>',  wp_ulike_get_counter_value( $post_id, 'post', 'like', $is_distinct ) );
    }
}
add_action( 'manage_posts_custom_column' , 'wp_ulike_manage_posts_custom_column', 10, 2 );
add_action( 'manage_pages_custom_column' , 'wp_ulike_manage_posts_custom_column', 10, 2 );

/**
 * Add custom column to post list
 *
 * @param   array  $columns
 *
 * @return  array
 */
function wp_ulike_manage_posts_columns( $columns ) {
	// Get settings list
	$post_types = wp_ulike_get_option( 'enable_admin_posts_columns', array() );
	// Get current post type
	$current_post_type = isset( $_GET['post_type'] ) && sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) === 'page' ? 'page' : get_post_type( wp_ulike_get_the_id() );

	if( ! empty( $post_types ) && false !== $current_post_type ){
		if( in_array( $current_post_type, $post_types ) ){
			$columns = apply_filters( 'wp_ulike_manage_posts_columns', array_merge( $columns,
			array( 'wp-ulike-thumbs-up' => '<i class="dashicons dashicons-thumbs-up"></i> ' . esc_html__('Like','wp-ulike') ) ), $current_post_type );
			// add sortable columns
			add_filter( 'manage_edit-' . $current_post_type . '_sortable_columns', function( $columns ){
				$columns['wp-ulike-thumbs-up'] = 'likes';
				return $columns;
			} );
		}
    }

    return $columns;
}
add_filter( 'manage_posts_columns' , 'wp_ulike_manage_posts_columns', 10 );
add_filter( 'manage_pages_columns' , 'wp_ulike_manage_posts_columns', 10 );


/**
 * Manage the query of sortable columns
 *
 * @param object $query
 * @return void
 */
function wp_ulike_manage_sortable_columns_order( $query ) {
	if ( ! is_admin() ){
		return;
	}

	if ( ! empty( $query->query['orderby'] ) && 'likes' == $query->query['orderby'] ) {
		$post__in = wp_ulike_get_popular_items_ids(array(
			'rel_type' => $query->get('post_type'),
			'status'   => 'like',
			"order"    => $query->get('order'),
			"offset"   => $query->get('paged'),
			"limit"    => $query->get('posts_per_page')
		));

		$query->set( 'offset', 0 );
		$query->set( 'post__in', $post__in );
		$query->set( 'orderby', 'post__in' );
	}

	do_action( 'wp_ulike_manage_sortable_columns_order', $query );
}
add_action( 'pre_get_posts', 'wp_ulike_manage_sortable_columns_order', 10, 1 );

/**
 * Count founded posts on manage columns
 *
 * @param integer $found_posts
 * @param object $query
 * @return integer
 */
function wp_ulike_manage_columns_found_posts( $found_posts, $query ){
	if ( ! is_admin() ){
		return $found_posts;
	}

	if ( ! empty( $query->query['orderby'] ) && 'likes' == $query->query['orderby'] ) {
		$found_posts = wp_ulike_get_popular_items_total_number(array(
			"rel_type" => $query->get('post_type'),
			"status"   => 'like'
		));
	}

	return $found_posts;
}
add_filter( 'found_posts', 'wp_ulike_manage_columns_found_posts', 10, 2 );

/**
 * Add comment columns
 *
 * @param array $columns
 * @return array
 */
function wp_ulike_comment_columns( $columns ) {
	if( wp_ulike_get_option( 'comments_group|enable_admin_columns', false ) ){
		$columns['wp-ulike-thumbs-up'] = '<i class="dashicons dashicons-thumbs-up"></i> ' . esc_html__('Like','wp-ulike');
	}

	return $columns;
}
add_filter( 'manage_edit-comments_columns', 'wp_ulike_comment_columns' );

/**
 * Set sortable columns for comments
 *
 * @param array $columns
 * @return array
 */
function wp_ulike_comments_sortable_columns( $columns ) {
	if( wp_ulike_get_option( 'comments_group|enable_admin_columns', false ) ){
    	$columns['wp-ulike-thumbs-up'] = 'likes';
	}

    return $columns;
}
add_filter( 'manage_edit-comments_sortable_columns', 'wp_ulike_comments_sortable_columns' );

/**
 * Display column content for comment
 *
 * @param string $column
 * @param integer $comment_ID
 * @return void
 */
function wp_ulike_comment_column_content( $column, $comment_ID ) {
    if ( $column === 'wp-ulike-thumbs-up' ){
		$is_distinct = wp_ulike_setting_repo::isDistinct('comment');
        echo sprintf( '<span class="wp-ulike-counter-box">%d</span>',  wp_ulike_get_counter_value( $comment_ID, 'comment', 'like', $is_distinct ) );
    }
}
add_filter( 'manage_comments_custom_column', 'wp_ulike_comment_column_content', 10, 2 );

/**
 * Manage the query of sortable columns for comments
 *
 * @param object $query
 * @return void
 */
function wp_ulike_manage_comment_sortable_columns_order( $query ) {
	if ( ! is_admin() ){
		return;
	}

	if ( ! empty( $query->query_vars['orderby'] ) && 'likes' == $query->query_vars['orderby'] ) {
		$comment__in = wp_ulike_get_popular_items_ids(array(
			'type'     => 'comment',
			'status'   => 'like',
			"order"    => $query->query_vars['order'],
			"offset"   => $query->query_vars['paged'],
			"limit"    => $query->query_vars['number']
		));

		$query->query_vars['comment__in'] = $comment__in;
		$query->query_vars['orderby']     = 'comment__in';
	}

	do_action( 'wp_ulike_manage_comment_sortable_columns_order', $query );
}
add_action( 'pre_get_comments', 'wp_ulike_manage_comment_sortable_columns_order', 10, 1 );


function wp_ulike_panel_customization_section( $options ) {
	if( wp_ulike_setting_repo::isCodeSnippetsDisabled() ){
		return $options;
	}

	$options[] = array(
		'id'    => 'php_snippets',
		'type'  => 'code_editor',
		'settings' => array(
			'theme'  => 'mbo',
			'mode'   => 'php',
		),
		'title'    => esc_html__('PHP Snippets','wp-ulike'),
		'sanitize' => 'wp_ulike_html_entity_decode',
		'desc'     => esc_html__('Add PHP snippets without opening and closing tags (&lt;?php and ?&gt;). If you have lots of snippets, you may want to consider using Code Snippets plugin.', 'wp-ulike')
	);
	$options[] = array(
		'id'    => 'js_snippets',
		'type'  => 'code_editor',
		'settings' => array(
			'theme'  => 'mbo',
			'mode'   => 'javascript',
		),
		'title'    => esc_html__('Javascript Snippets','wp-ulike'),
		'sanitize' => 'wp_ulike_html_entity_decode',
		'desc'     => esc_html__('This code will output immediately before the closing &lt;/body&gt; tag in the document source. (Scripts must not be property wrapped in &lt;script&gt; tag.)', 'wp-ulike')
	);

	return $options;
}
add_filter( 'wp_ulike_panel_customization', 'wp_ulike_panel_customization_section', 10, 1 );

/**
 * Stores css content in custom css file when settings or customizer are saved
 * Always tries to generate the file regardless of user preference (for debugging/inspection)
 * User preference only controls the delivery method (file vs inline)
 *
 * @param array $values The saved values (not used but available)
 * @return boolean Returns true if the file is created and updated successfully, false on failure
 */
function wp_ulike_save_custom_css( $values = null ){
    $css_string = wp_ulike_get_custom_style();
    $css_string = wp_ulike_minify_css( $css_string );

    if ( ! empty( $css_string ) && wp_ulike_put_contents_dir( $css_string, 'custom.css' ) ) {
        // File created successfully - directory is writable
        update_option( 'wp_ulike_use_inline_custom_css' , 0 );
        return true;
    } else {
        // File creation failed - directory not writable, must use inline fallback
        update_option( 'wp_ulike_use_inline_custom_css' , 1 );
        return false;
    }
}
// Hook to save CSS file when settings are saved
add_action( 'wp_ulike_settings_saved', 'wp_ulike_save_custom_css', 15, 1 );
// Hook to save CSS file when customizer is saved
add_action( 'wp_ulike_customizer_saved', 'wp_ulike_save_custom_css', 15, 1 );

/**
 * Clear CSS generator cache when customizer is saved
 * This ensures cache is cleared AFTER new values are saved
 *
 * @param array $new_values Optional. New customizer values
 * @return void
 */
function wp_ulike_clear_css_generator_cache( $new_values = null ) {
	if ( class_exists( 'wp_ulike_css_generator' ) ) {
		$css_generator = new wp_ulike_css_generator();
		if ( method_exists( $css_generator, 'clear_cache' ) ) {
			$css_generator->clear_cache( $new_values );
		}
	}
}
// Hook to clear CSS cache when customizer is saved
add_action( 'wp_ulike_customizer_saved', 'wp_ulike_clear_css_generator_cache', 10, 1 );