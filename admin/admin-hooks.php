<?php
/**
 * Admin Hooks
 * // @echo HEADER
 */

/*******************************************************
  General Hooks
*******************************************************/

/**
 * Add WP ULike CopyRight in footer
 *
 * @author       	Alimir	 	
 * @param           String $content	 
 * @since           2.0
 * @return			String
 */		
function wp_ulike_copyright( $text ) {
	return sprintf( __( ' Thank you for choosing <a href="%s" title="Wordpress ULike" target="_blank">WP ULike</a>. Created by <a href="%s" title="Wordpress ULike" target="_blank">Ali Mirzaei</a>' ), 'http://wordpress.org/plugins/wp-ulike/', 'https://ir.linkedin.com/in/alimirir' );
}
//check for wp ulike page
if( isset($_GET["page"]) && stripos($_GET["page"], "wp-ulike") !== false ) {
	add_filter( 'admin_footer_text', 'wp_ulike_copyright');
}

/**
 * admin enqueue scripts
 *
 * @author       	Alimir	 	
 * @since           2.1
 * @return			Void
 */		
function wp_ulike_logs_enqueue_script( $hook ){

	// Enqueue admin styles
	wp_enqueue_style( 'wp-ulike-admin', WP_ULIKE_ADMIN_URL . '/assets/css/admin.css' );

	$currentScreen 	= get_current_screen();
	
	if ( $currentScreen->id !== $hook || ! preg_match( '/logs/', $currentScreen->id ) ) {
		return;
	}

	// Register Script
	wp_enqueue_script(
		'wp_ulike_stats',
		WP_ULIKE_ADMIN_URL . '/assets/js/statistics.js',
		array('jquery'),
		null,
		true
	);
	
	//localize script
	wp_localize_script( 'wp_ulike_stats', 'wp_ulike_logs', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'message' => __('Are you sure to remove this item?!',WP_ULIKE_SLUG)
	));
	
}
add_action('admin_enqueue_scripts', 'wp_ulike_logs_enqueue_script');

/**
 * Set the option of per_page
 *
 * @author       	Alimir	 	
 * @since           2.1
 * @return			String
 */		 
function wp_ulike_logs_per_page_set_option($status, $option, $value) {
	if ( 'wp_ulike_logs_per_page' == $option ) return $value;
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
 * Save screen options with "update_option" mehtod
 *
 * @author       	Alimir	 	
 * @since           2.1 
 * @return			Void
 */		
function wp_ulike_statistics_save_option(){
	if(isset($_POST['wp_ulike_statistics_screen']) AND wp_verify_nonce($_POST['wp_ulike_statistics_screen'], 'wp_ulike_statistics_nonce_field' ) ){
		$options = array(
		  'welcome_panel'			=> isset($_POST['wp_ulike_welcome']) 			? $_POST['wp_ulike_welcome'] 			: 0,
		  'summary_like_stats'		=> isset($_POST['wp_ulike_summary_stats']) 		? $_POST['wp_ulike_summary_stats'] 		: 0,
		  'posts_likes_stats'		=> isset($_POST['wp_ulike_posts_stats']) 		? $_POST['wp_ulike_posts_stats'] 		: 0,
		  'comments_likes_stats'	=> isset($_POST['wp_ulike_comments_stats']) 	? $_POST['wp_ulike_comments_stats'] 	: 0,
		  'activities_likes_stats'	=> isset($_POST['wp_ulike_activities_stats']) 	? $_POST['wp_ulike_activities_stats'] 	: 0,
		  'topics_likes_stats'		=> isset($_POST['wp_ulike_topics_stats']) 		? $_POST['wp_ulike_topics_stats'] 		: 0,
		  'most_liked_posts'		=> isset($_POST['wp_ulike_most_liked_posts']) 	? $_POST['wp_ulike_most_liked_posts'] 	: 0,
		  'most_liked_comments'		=> isset($_POST['wp_ulike_most_liked_cmt'])		? $_POST['wp_ulike_most_liked_cmt'] 	: 0,
		  'piechart_stats'			=> isset($_POST['wp_ulike_piechart_stats']) 	? $_POST['wp_ulike_piechart_stats'] 	: 0,
		  'likers_map'				=> isset($_POST['wp_ulike_likers_map']) 		? $_POST['wp_ulike_likers_map'] 		: 0,
		  'top_likers'				=> isset($_POST['wp_ulike_top_likers']) 		? $_POST['wp_ulike_top_likers'] 		: 0,
		  'top_posts'				=> isset($_POST['wp_ulike_top_posts']) 			? $_POST['wp_ulike_top_posts'] 			: 0,
		  'top_comments'			=> isset($_POST['wp_ulike_top_comments']) 		? $_POST['wp_ulike_top_comments'] 		: 0,
		  'top_activities'			=> isset($_POST['wp_ulike_top_activities']) 	? $_POST['wp_ulike_top_activities'] 	: 0,
		  'top_topics'				=> isset($_POST['wp_ulike_top_topics']) 		? $_POST['wp_ulike_top_topics'] 		: 0,
		  'days_number'				=> isset($_POST['wp_ulike_days_number']) 		? $_POST['wp_ulike_days_number'] 		: 20
		);
		update_option( 'wp_ulike_statistics_screen', $options );
	}
}
add_action('admin_init', 'wp_ulike_statistics_save_option');

/**
 * Add menu to admin
 *
 * @author       	Alimir	 	
 * @since           1.0
 * @updated         2.2
 * @updated         2.4.2
 * @return			String
 */
function wp_ulike_admin_menu() {
	
	global $menu;
	
	//Post Like Logs Menu
	$posts_screen 		= add_submenu_page(null, __( 'Post Likes Logs', WP_ULIKE_SLUG ), __( 'Post Likes Logs', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-post-logs', 'wp_ulike_post_likes_logs');
	add_action("load-$posts_screen",'wp_ulike_logs_per_page');
	
	//Comment Like Logs Menu
	$comments_screen 	= add_submenu_page(null, __( 'Comment Likes Logs', WP_ULIKE_SLUG ), __( 'Comment Likes Logs', WP_ULIKE_SLUG ), 'manage_options','wp-ulike-comment-logs', 'wp_ulike_comment_likes_logs');
	add_action("load-$comments_screen",'wp_ulike_logs_per_page');
	
	//Activity Like Logs Menu
	$activities_screen 	= add_submenu_page(null, __( 'Activity Likes Logs', WP_ULIKE_SLUG ), __( 'Activity Likes Logs', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-bp-logs', 'wp_ulike_buddypress_likes_logs');
	add_action("load-$activities_screen",'wp_ulike_logs_per_page');
	
	//Activity Like Logs Menu
	$topics_screen 		= add_submenu_page(null, __( 'Topics Likes Logs', WP_ULIKE_SLUG ), __( 'Topics Likes Logs', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-bbpress-logs', 'wp_ulike_bbpress_likes_logs');
	add_action("load-$topics_screen",'wp_ulike_logs_per_page');
	
	//Statistics Menu
	$statistics_screen 	= add_submenu_page('wp-ulike-settings', __( 'WP ULike Statistics', WP_ULIKE_SLUG ), __( 'WP ULike Statistics', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-statistics', 'wp_ulike_statistics');
	add_action("load-$statistics_screen",'wp_ulike_statistics_register_option');
	
	//WP ULike About Menu
	add_submenu_page('wp-ulike-settings', __( 'About WP ULike', WP_ULIKE_SLUG ), __( 'About WP ULike', WP_ULIKE_SLUG ), 'manage_options', 'wp-ulike-about', 'wp_ulike_about_page');
	
	$newvotes = wp_ulike_get_number_of_new_likes();
	$menu[313][0] .= $newvotes ? " <span class='update-plugins count-1'><span class='update-count'>". number_format_i18n($newvotes) ."</span></span> " : '';
	
}
add_action('admin_menu', 'wp_ulike_admin_menu');

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
		jQuery(document).on( 'click', '.wp-ulike-notice .notice-dismiss', function() {
		    jQuery.ajax({
		        url: ajaxurl,
		        data: {
		            action: 'wp_ulike_dismissed_notice'
		        }
		    })
		});			
	</script>
	<div class="notice wp-ulike-notice is-dismissible">
        <img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/wp-ulike-badge.png" alt="WP ULike Plugin">
        <div class="wp-ulike-notice-text">
            <p><?php echo _e( "It's great to see that you've been using the WP ULike plugin for a while now. Hopefully you're happy with it!&nbsp; If so, would you consider leaving a positive review? It really helps to support the plugin and helps others to discover it too!" , WP_ULIKE_SLUG ); ?> </p>
            <p class="links">
                <a href="https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5" target="_blank"><?php echo _e( "Sure, I'd love to!", WP_ULIKE_SLUG ); ?></a>
            </p>
        </div>
    </div>			
	<?php
}
add_action( 'admin_notices', 'wp_ulike_admin_notice', 999);

/**
 * Simple Ads
 *
 * @author       	Alimir	 	
 * @since           3.1
 * @return			String
 */ 
function wp_ulike_advertisement(){
?>
	<div class="welcome-panel wp-ulike-advertisement">
		<a href="http://averta.net/phlox/wordpress-theme/?utm_source=ulike&utm_medium=banner&utm_campaign=phlox" target="_blank" title="Phlox Theme">
			<img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/phlox-theme.png" alt="Phlox Theme">
		</a>
	</div>
<?php
}
add_filter( 'wp_ulike_advertisement', 'wp_ulike_advertisement', 99999999999999 );