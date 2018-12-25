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
 * @param           String $content
 * @since           2.0
 * @return			String
 */
function wp_ulike_copyright( $text ) {
	if( isset($_GET["page"]) && stripos( $_GET["page"], "wp-ulike") !== false ) {
		return sprintf(
			__( ' Thank you for choosing <a href="%s" title="Wordpress ULike" target="_blank">WP ULike</a>. Created by <a href="%s" title="Wordpress ULike" target="_blank">Ali Mirzaei</a>', WP_ULIKE_SLUG ),
			'http://wordpress.org/plugins/wp-ulike/',
			'https://ir.linkedin.com/in/alimirir'
		);
	}

	return $text;
}
add_filter( 'admin_footer_text', 'wp_ulike_copyright');

/**
 * Set the option of per_page
 *
 * @author       	Alimir
 * @since           2.1
 * @return			String
 */
function wp_ulike_logs_per_page_set_option($status, $option, $value) {

	if ( 'wp_ulike_logs_per_page' == $option ) {
		return $value;
	}

	return $status;
}
add_filter('set-screen-option', 'wp_ulike_logs_per_page_set_option', 10, 3);

/**
 * remove photo class from gravatar
 *
 * @author       	Alimir
 * @since           1.7
 * @return			String
 */
function wp_ulike_remove_photo_class($avatar) {
	return str_replace(' photo', ' gravatar', $avatar);
}
add_filter('get_avatar', 'wp_ulike_remove_photo_class');


/**
 * Set the admin login time.
 *
 * @author       	Alimir
 * @since           2.4.2
 * @return			Void
 */
function wp_ulike_set_lastvisit() {
	if ( ! is_super_admin() ) {
		return;
	}
	update_option( 'wpulike_lastvisit', current_time( 'mysql' ) );
}
add_action('wp_logout', 'wp_ulike_set_lastvisit');

/**
 * Add rating us notification on wp-ulike admin pages
 *
 * @author       	Alimir
 * @since           2.7
 * @return			String
 */
function wp_ulike_admin_notice() {
	if( get_option( 'wp-ulike-notice-dismissed', FALSE ) ) return;
	?>
	<script>
		jQuery(document).on( 'click', '.wp-ulike-dismiss', function(e) {
			e.preventDefault();
		    jQuery.ajax({
				url : ajaxurl,
				type: 'post',
				data: {
					action: 'wp_ulike_dismissed_notice',
					nonce : jQuery(this).data('nonce')
		        }
		    }).done(function( response ) {
                jQuery(this).closest('.wp-ulike-notice').fadeOut();
            }.bind(this));
		});
	</script>
	<div class="wp-ulike-notice">
		<div class="wp-ulike-notice-image">
        	<img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/wp-ulike-badge.png" alt="WP ULike Plugin">
    	</div>
        <div class="wp-ulike-notice-text">
            <p><?php echo _e( "It's great to see that you've been using the WP ULike plugin. Hopefully you're happy with it!&nbsp; If so, would you consider leaving a positive review? It really helps to support the plugin and helps others to discover it too!" , WP_ULIKE_SLUG ); ?> </p>
            <div class="links">
                <a href="https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5" target="_blank"><?php echo _e( "Sure, I'd love to!", WP_ULIKE_SLUG ); ?></a>
                <a href="https://m.do.co/c/13ad5bc24738" target="_blank"><?php echo _e( "I also want to donate!", WP_ULIKE_SLUG ); ?></a>
                <a href="#" data-nonce="<?php echo wp_create_nonce( 'wp-ulike-notice-dismissed' ); ?>" class="wp-ulike-dismiss"><?php echo _e( "Maybe Later & Clear this message!", WP_ULIKE_SLUG ); ?></a>
            </div>
        </div>
    </div>
	<?php
}
add_action( 'admin_notices', 'wp_ulike_admin_notice', 25 );

/**
 *  Undocumented function
 *
 * @param integer $count
 * @return integer
 */
function wp_ulike_update_menu_badge_count( $count ) {
	if( 0 !== $count_new_likes = wp_ulike_get_number_of_new_likes() ){
		$count += $count_new_likes;
	}
	return $count;
}
add_filter( 'wp_ulike_menu_badge_count', 'wp_ulike_update_menu_badge_count' );

/**
 *  Undocumented function
 *
 * @param integer $count
 * @return integer
 */
function wp_ulike_update_admin_menu_title( $title, $menu_slug ) {
	if( ( 0 !== $count_new_likes = wp_ulike_get_number_of_new_likes() ) && $menu_slug === 'wp-ulike-statistics' ){
		$title .=  wp_ulike_badge_count_format( $count_new_likes );
	}
	return $title;
}
add_filter( 'wp_ulike_admin_sub_menu_title', 'wp_ulike_update_admin_menu_title', 10, 2 );
