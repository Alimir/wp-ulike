<?php
/**
 * Admin Functions
 * // @echo HEADER
 */

/*******************************************************
  About Page
*******************************************************/

/**
 * Create WP ULike About page
 *
 * @author       	Alimir
 * @since           1.7
 * @return			String
 */
function wp_ulike_about_page() {
?>
<div class="wrap about-wrap wp-ulike-about-page">

	<h1><?php echo _e( 'Welcome to WP ULike', WP_ULIKE_SLUG ) . ' ' . WP_ULIKE_VERSION; ?></h1>
	<div class="about-text"><?php echo _e('Thank you for choosing WP ULike! This version is our leanest and most powerful version yet.', WP_ULIKE_SLUG) ; ?><br />

	<?php add_thickbox(); ?>
	<a target="_blank" href="<?php echo WP_ULIKE_PLUGIN_URI . '?TB_iframe=true&amp;width=800&amp;height=600'; ?>" class="thickbox"> <?php _e('Visit our homepage',WP_ULIKE_SLUG); ?></a>
	</div>
	<div class="ulike-badge"><?php echo _e('Version',WP_ULIKE_SLUG) . ' ' . WP_ULIKE_VERSION; ?></div>
	<h2 class="nav-tab-wrapper">
		<a class="nav-tab <?php if(!isset($_GET["credit"])) echo 'nav-tab-active'; ?>" href="admin.php?page=wp-ulike-about"><?php echo _e('Getting Started',WP_ULIKE_SLUG); ?></a>
		<a class="nav-tab <?php if(isset($_GET["credit"])) echo 'nav-tab-active'; ?>" href="admin.php?page=wp-ulike-about&credit=true"><?php echo _e('Credits',WP_ULIKE_SLUG); ?></a>
		<a target="_blank" class="nav-tab" href="https://wordpress.org/support/plugin/wp-ulike"><?php echo _e('Support',WP_ULIKE_SLUG); ?></a>
		<a target="_blank" class="nav-tab" href="https://wordpress.org/plugins/wp-ulike/faq/"><?php echo _e('FAQ',WP_ULIKE_SLUG); ?></a>
		<a target="_blank" class="nav-tab" href="https://wordpress.org/support/view/plugin-reviews/wp-ulike"><?php echo _e('Reviews',WP_ULIKE_SLUG); ?></a>
	</h2>

	<?php if(!isset($_GET["credit"])): ?>

    <div class="changelog headline-feature">
        <h2><?php echo _e('Introducing WP ULike',WP_ULIKE_SLUG); ?> <img draggable="false" class="emoji" alt="emoji" src="https://s.w.org/images/core/emoji/2.2.1/svg/1f60a.svg"></h2>
        <div class="featured-image">
            <img alt="wp ulike intro" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/wp-ulike-intro.png">
        </div>

        <div class="feature-section">
            <div class="col">
                <h2><?php echo _e('About WP ULike',WP_ULIKE_SLUG); ?></h2>
                <div style="text-align:center">
                <?php $args = array(
                   'rating' => 5,
                   'type' => 'rating',
                   'number' => 43,
                );
                wp_star_rating( $args ); ?>
                </div>
                <p class="lead-description"><?php echo _e('WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments AND buddypress activities. Its very simple to use and supports many options.',WP_ULIKE_SLUG); ?></p>
            </div>
        </div>

        <div class="clear"></div>
    </div>
    <hr>
    <div class="changelog feature-section three-col">
        <div class="col">
            <div class="icon-container">
                <i class="wp-ulike-icons-hotairballoon"></i>
            </div>
            <h3><?php echo _e('WP Ulike Extension',WP_ULIKE_SLUG); ?></h3>
            <p><?php echo _e('Right now, WP ULike support wordpress posts / comments, BuddyPress activities & bbPress Topics.',WP_ULIKE_SLUG); ?></p>
        </div>
        <div class="col">
            <div class="icon-container">
                <i class="wp-ulike-icons-globe"></i>
            </div>
            <h3><?php echo _e('Added More Than 20 Language Files',WP_ULIKE_SLUG); ?></h3>
            <p><?php echo _e('WP ULike is already translated into +20 languages, with more always in progress.',WP_ULIKE_SLUG); ?></p>
        </div>
        <div class="col">
            <div class="icon-container">
                <i class="wp-ulike-icons-profile-male"></i>
            </div>
            <h3><?php echo _e('User Profile Links',WP_ULIKE_SLUG); ?></h3>
            <p><?php echo _e('Since WP ULike 2.3, We have synced the likers profile with BuddyPress & UltimateMember plugins.',WP_ULIKE_SLUG); ?></p>
        </div>
        <div class="col">
            <div class="icon-container">
                <i class="wp-ulike-icons-paintbrush"></i>
            </div>
            <h3><?php echo _e('New Themes And Styles',WP_ULIKE_SLUG); ?></h3>
            <p><?php echo _e('Since WP ULike 2.3, We have made some new styles and themes and you can customize them by your taste.',WP_ULIKE_SLUG); ?></p>
        </div>
        <div class="col">
            <div class="icon-container">
                <i class="wp-ulike-icons-trophy"></i>
            </div>
            <h3><?php echo _e('myCRED Points Support',WP_ULIKE_SLUG); ?></h3>
           <p><?php echo _e('myCRED is an adaptive points management system that lets you award / charge your users for interacting with your WordPress.',WP_ULIKE_SLUG); ?></p>
        </div>
        <div class="col">
            <div class="icon-container">
                <i class="wp-ulike-icons-map"></i>
            </div>
            <h3><?php echo _e('Likers World Map',WP_ULIKE_SLUG); ?></h3>
            <p><?php echo _e('Since WP ULike 2.3, We have made a new ability that you can track your likers by their country in the world map & Top Liker widget.',WP_ULIKE_SLUG); ?></p>
        </div>
    </div>

	<div class="changelog feature-list">
		<div class="return-to-dashboard">
			<a href="admin.php?page=wp-ulike-statistics"><?php echo _e('WP ULike Statistics',WP_ULIKE_SLUG); ?> &rarr; <?php echo _e('Home',WP_ULIKE_SLUG); ?></a> <?php echo _e('OR',WP_ULIKE_SLUG); ?> <a href="admin.php?page=wp-ulike-settings"><?php echo _e('WP ULike Settings',WP_ULIKE_SLUG); ?></a>
		</div>
	</div>

	<?php else: ?>

	<p class="about-description"><?php echo _e('WP ULike is created by many love and time. Enjoy it :)',WP_ULIKE_SLUG); ?></p>
	<h3 class="wp-people-group"><?php echo _e('Project Leaders',WP_ULIKE_SLUG); ?></h3>
	<ul id="wp-people-group-project-leaders" class="wp-people-group">
		<li class="wp-person" id="wp-person-alimir">
			<a href="https://profiles.wordpress.org/alimir/"><?php echo get_avatar( 'info@alimir.ir', 64 ); ?></a>
			<a class="web" target="_blank" href="https://ir.linkedin.com/in/alimirir/">Ali Mirzaei</a>
			<span class="title"><?php echo _e('Project Lead & Developer',WP_ULIKE_SLUG); ?></span>
		</li>
	</ul>

	<h3 class="wp-people-group"><?php _e('Translations',WP_ULIKE_SLUG); ?></h3>
	<ul>
		<li>English (United States)</li>
		<li>Persian (Iran)</li>
		<li>French (France)</li>
		<li>Chinese (China)</li>
		<li>Chinese (Taiwan)</li>
		<li>Dutch (Netherlands) </li>
		<li>Arabic</li>
		<li>Portuguese (Brazil)</li>
		<li>Turkish (Turkey)</li>
		<li>Greek</li>
		<li>Russian (Russia)</li>
		<li>Spanish (Spain)</li>
		<li>German (Germany)</li>
		<li>Japanese</li>
		<li>Romanian (Romania)</li>
		<li>Slovak (Slovakia)</li>
		<li>Czech (Czech Republic)</li>
		<li>Hebrew (Israel)</li>
		<li>Italian (Italy)</li>
		<li>Polish (Poland)</li>
		<li>Finnish</li>
		<li>Hungarian (Hungary)</li>
		<li>Lithuanian (Lithuania)</li>
		<li>Indonesian (Indonesia)</li>
		<li>Khmer</li>
		<li>Norwegian Bokmal (Norway)</li>
		<li>Portuguese (Portugal)</li>
		<li>Swedish (Sweden)</li>
		<li>Danish (Denmark)</li>
		<li>Estonian</li>
		<li>Korean (Korea)</li>
		<li>Vietnamese</li>
		<li>Basque</li>
		<li>Bosnian (Bosnia and Herzegovina)</li>
		<li>English (United Kingdom)</li>
	</ul>

	<p class="about-description"><?php _e('Would you like to help translate the plugin into more languages?',WP_ULIKE_SLUG); ?> <a target="_blank" href="https://www.transifex.com/projects/p/wp-ulike/" title"WP-Translations">[<?php _e('Join our WP-Translations Community',WP_ULIKE_SLUG); ?>]</a></p>

	<h3 class="wp-people-group"><?php echo _e('Other Plugins',WP_ULIKE_SLUG); ?></h3>
	<ul class="wp-people-group">
		<li class="wp-person" id="wp-person-alimirzaei">
			<a target="_blank" href="https://wordpress.org/plugins/blue-login-style"><img class="gravatar" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/blue-login-themes.jpg" alt="Blue Login Themes" /></a>
			<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
			<span class="title">Blue Login Themes</span>
		</li>
		<li class="wp-person" id="wp-person-alimirzaei">
			<a target="_blank" href="https://wordpress.org/plugins/custom-fields-notifications/"><img class="gravatar" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/custom-fileds-notifications.png" alt="Custom Fields Notifications" /></a>
			<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
			<span class="title">Custom Fields Notifications</span>
		</li>
		<li class="wp-person" id="wp-person-alimirzaei">
			<a target="_blank" href="http://wordpress.org/plugins/ajax-bootmodal-login/"><img class="gravatar" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/ajax-bootmodal-login.jpg" alt="Ajax BootModal Login" /></a>
			<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
			<span class="title">Ajax BootModal Login</span>
		</li>
	</ul>

	<h3 class="wp-people-group"><?php _e('Like this plugin?',WP_ULIKE_SLUG); ?></h3>
	<div class="wp-ulike-notice">
        <img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/wp-ulike-badge.png" alt="WP ULike Plugin">
        <div class="wp-ulike-notice-text">
            <p><?php echo _e( "It's great to see that you've been using the WP ULike plugin for a while now. Hopefully you're happy with it!&nbsp; If so, would you consider leaving a positive review? It really helps to support the plugin and helps others to discover it too!" , WP_ULIKE_SLUG ); ?> </p>
            <p class="links">
                <a href="https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5" target="_blank"><?php echo _e( "Sure, I'd love to!", WP_ULIKE_SLUG ); ?></a>
            </p>
        </div>
    </div>

	<?php endif; ?>

</div>
<?php
}

/*******************************************************
  Logs Page
*******************************************************/
/**
 * Return per_page option value
 *
 * @author       	Alimir
 * @since           2.1
 * @return			Integer
 */
function wp_ulike_logs_return_per_page(){
	$user 	= get_current_user_id();
	$screen = get_current_screen();
	$option = $screen->get_option('per_page', 'option');
	$per_page = get_user_meta($user, $option, true);

	if ( empty ( $per_page) || $per_page < 1 ) {
		return 20;
	}
	else
		return $per_page;
}

/**
 * Add screen option for per_page value
 *
 * @author       	Alimir
 * @since           2.1
 * @return			String
 */
function wp_ulike_logs_per_page() {
	$option = 'per_page';
	$args = array(
		'label' => __('Logs',WP_ULIKE_SLUG),
		'default' => 20,
		'option' => 'wp_ulike_logs_per_page'
	);
	add_screen_option( $option, $args );
}

/**
 * Create WP ULike Post Logs page with separate pagination
 *
 * @author       	Alimir
 * @since           1.7
 * @return			String
 */
function wp_ulike_post_likes_logs(){
	global $wpdb;
	$alternate 	= true;
	$items 		= $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."ulike");
	if($items > 0) {
			$p = new wp_ulike_pagination;
			$p->items($items);
			$p->limit(wp_ulike_logs_return_per_page()); // Limit entries per page
			$p->target("admin.php?page=wp-ulike-post-logs");
			$p->calculate(); // Calculates what to show
			$p->parameterName('page_number');
			$p->adjacents(1); //No. of page away from the current page

			if(!isset($_GET['page_number'])) {
				$p->page = 1;
			} else {
				$p->page = $_GET['page_number'];
			}

			//Query for limit page_number
			$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;

	$get_ulike_logs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulike ORDER BY id DESC ".$limit."");

?>
<div class="wrap">
	<h2><?php _e('WP ULike Logs', WP_ULIKE_SLUG); ?></h2>
	<h3><?php _e('Post Likes Logs', WP_ULIKE_SLUG); ?></h3>
	<div class="tablenav">
		<div class='tablenav-pages'>
			<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
			<?php echo $p->show();  // Echo out the list of paging. ?>
		</div>
	</div>
	<table class="widefat">
		<thead>
			<tr>
				<th width="2%"><?php _e('ID', WP_ULIKE_SLUG); ?></th>
				<th width="10%"><?php _e('Username', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Status', WP_ULIKE_SLUG); ?></th>
				<th width="6%"><?php _e('Post ID', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Post Title', WP_ULIKE_SLUG); ?></th>
				<th width="20%"><?php _e('Date / Time', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('IP', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Actions', WP_ULIKE_SLUG); ?></th>
			</tr>
		</thead>
		<tbody class="wp_ulike_logs">
			<?php
			foreach ( $get_ulike_logs as $get_ulike_log )
			{
			?>
			<tr <?php if ($alternate == true) echo 'class="alternate"';?>>
			<td>
			<?php echo $get_ulike_log->id; ?>
			</td>
			<td>
			<?php
			$user_info = get_userdata($get_ulike_log->user_id);
			if($user_info)
			echo get_avatar( $user_info->user_email, 16, '' , 'avatar') . '<em> @' . $user_info->user_login . '</em>';
			else
			echo '<em> #'. __('Guest User',WP_ULIKE_SLUG) .'</em>';
			?>
			</td>
			<td>
			<?php
			$get_the_status = $get_ulike_log->status;
			if($get_the_status == 'like')
				echo '<i class="wp-ulike-icons-thumb_up"></i>';
			else
				echo '<i class="wp-ulike-icons-thumb_down"></i>';
			?>
			</td>
			<td>
			<?php echo $get_ulike_log->post_id; ?>
			</td>
			<td>
			<?php echo '<a href="'.get_permalink($get_ulike_log->post_id).'" title="'.get_the_title($get_ulike_log->post_id).'">'.get_the_title($get_ulike_log->post_id).'</a>'; ?>
			</td>
			<td>
			<?php
			echo wp_ulike_date_i18n($get_ulike_log->date_time);
			?>
			</td>
			<td>
			<?php echo $get_ulike_log->ip; ?>
			</td>
			<td>
			<button class="wp_ulike_delete button" type="button" data-nonce="<?php echo wp_create_nonce( 'ulike' . $get_ulike_log->id ); ?>" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike"><i class="dashicons dashicons-trash"></i></button>
			</td>
			<?php
			$alternate = !$alternate;
			}
			?>
			</tr>
		</tbody>
	</table>
	<div class="tablenav">
		<div class='tablenav-pages'>
			<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
			<?php echo $p->show();  // Echo out the list of paging. ?>
		</div>
	</div>
</div>
<?php
	} else {
		echo "<div class='error'><p>" . __('<strong>ERROR:</strong> No Record Found. (This problem is created because you don\'t have any data on this table)',WP_ULIKE_SLUG) . "</p></div>";
	}
}

/**
 * Create WP ULike Comment Logs page with separate pagination
 *
 * @author       	Alimir
 * @since           1.7
 * @return			String
 */
function wp_ulike_comment_likes_logs(){
	global $wpdb;
	$alternate 	= true;
	$items 		= $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_comments");
	if($items > 0) {
			$p = new wp_ulike_pagination;
			$p->items($items);
			$p->limit(wp_ulike_logs_return_per_page()); // Limit entries per page
			$p->target("admin.php?page=wp-ulike-comment-logs");
			$p->calculate(); // Calculates what to show
			$p->parameterName('page_number');
			$p->adjacents(1); //No. of page away from the current page

			if(!isset($_GET['page_number'])) {
				$p->page = 1;
			} else {
				$p->page = $_GET['page_number'];
			}

			//Query for limit page_number
			$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;

	$get_ulike_logs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulike_comments ORDER BY id DESC ".$limit."");
?>
<div class="wrap">
	<h2><?php _e('WP ULike Logs', WP_ULIKE_SLUG); ?></h2>
	<h3><?php _e('Comment Likes Logs', WP_ULIKE_SLUG); ?></h3>
	<div class="tablenav">
		<div class='tablenav-pages'>
			<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
			<?php echo $p->show();  // Echo out the list of paging. ?>
		</div>
	</div>
	<table class="widefat">
		<thead>
			<tr>
				<th width="2%"><?php _e('ID', WP_ULIKE_SLUG); ?></th>
				<th width="10%"><?php _e('Username', WP_ULIKE_SLUG); ?></th>
				<th width="5%"><?php _e('Status', WP_ULIKE_SLUG); ?></th>
				<th width="6%"><?php _e('Comment ID', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Comment Author', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Comment Text', WP_ULIKE_SLUG); ?></th>
				<th width="20%"><?php _e('Date / Time', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('IP', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Actions', WP_ULIKE_SLUG); ?></th>
			</tr>
		</thead>
		<tbody class="wp_ulike_logs">
			<?php
			foreach ( $get_ulike_logs as $get_ulike_log )
			{
			?>
			<tr <?php if ($alternate == true) echo 'class="alternate"';?>>
			<td>
			<?php echo $get_ulike_log->id; ?>
			</td>
			<td>
			<?php
			$user_info = get_userdata($get_ulike_log->user_id);
			if($user_info)
			echo get_avatar( $user_info->user_email, 16, '' , 'avatar') . '<em> @' . $user_info->user_login . '</em>';
			else
			echo '<em> #'. __('Guest User',WP_ULIKE_SLUG) .'</em>';
			?>
			</td>
			<td>
			<?php
			$get_the_status = $get_ulike_log->status;
			if($get_the_status == 'like')
				echo '<i class="wp-ulike-icons-thumb_up"></i>';
			else
				echo '<i class="wp-ulike-icons-thumb_down"></i>';
			?>
			</td>
			<td>
			<?php echo $get_ulike_log->comment_id; ?>
			</td>
			<td>
			<?php echo get_comment_author($get_ulike_log->comment_id) ?>
			</td>
			<td>
			<?php echo get_comment_text($get_ulike_log->comment_id) ?>
			</td>
			<td>
			<?php
			echo wp_ulike_date_i18n($get_ulike_log->date_time);
			?>
			</td>
			<td>
			<?php echo $get_ulike_log->ip; ?>
			</td>
			<td>
			<button class="wp_ulike_delete button" type="button" data-nonce="<?php echo wp_create_nonce( 'ulike_comments' . $get_ulike_log->id ); ?>" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike_comments"><i class="dashicons dashicons-trash"></i></button>
			</td>
			<?php
			$alternate = !$alternate;
			}
			?>
			</tr>
		</tbody>
	</table>
	<div class="tablenav">
		<div class='tablenav-pages'>
			<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
			<?php echo $p->show();  // Echo out the list of paging. ?>
		</div>
	</div>
</div>

<?php
	} else {
		echo "<div class='error'><p>" . __('<strong>ERROR:</strong> No Record Found. (This problem is created because you don\'t have any data on this table)',WP_ULIKE_SLUG) . "</p></div>";
	}
}

/**
 * Create WP ULike BuddyPress Logs page with separate pagination
 *
 * @author       	Alimir
 * @since           1.7
 * @return			String
 */
function wp_ulike_buddypress_likes_logs(){

	if( ! defined( 'BP_VERSION' ) ) {
		echo sprintf( '<div class="wrap">' . __( '%s is Not Activated!', WP_ULIKE_SLUG ) . '</div>' ,__( 'BuddyPress', WP_ULIKE_SLUG ) );
		return;
	}

	global $wpdb;
	$alternate 	= true;
	$items 		= $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_activities");
	if($items > 0) {
			$p = new wp_ulike_pagination;
			$p->items($items);
			$p->limit(wp_ulike_logs_return_per_page()); // Limit entries per page
			$p->target("admin.php?page=wp-ulike-bp-logs");
			$p->calculate(); // Calculates what to show
			$p->parameterName('page_number');
			$p->adjacents(1); //No. of page away from the current page

			if(!isset($_GET['page_number'])) {
				$p->page = 1;
			} else {
				$p->page = $_GET['page_number'];
			}

			//Query for limit page_number
			$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;

	$get_ulike_logs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulike_activities ORDER BY id DESC ".$limit."");

?>
	<div class="wrap">
		<h2><?php _e('WP ULike Logs', WP_ULIKE_SLUG); ?></h2>
		<h3><?php _e('Activity Likes Logs', WP_ULIKE_SLUG); ?></h3>
		<div class="tablenav">
			<div class='tablenav-pages'>
				<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
				<?php echo $p->show();  // Echo out the list of paging. ?>
			</div>
		</div>
		<table class="widefat">
			<thead>
				<tr>
					<th width="3%"><?php _e('ID', WP_ULIKE_SLUG); ?></th>
					<th width="13%"><?php _e('Username', WP_ULIKE_SLUG); ?></th>
					<th><?php _e('Status', WP_ULIKE_SLUG); ?></th>
					<th width="6%"><?php _e('Activity ID', WP_ULIKE_SLUG); ?></th>
					<th><?php _e('Permalink', WP_ULIKE_SLUG); ?></th>
					<th><?php _e('Date / Time', WP_ULIKE_SLUG); ?></th>
					<th><?php _e('IP', WP_ULIKE_SLUG); ?></th>
					<th><?php _e('Actions', WP_ULIKE_SLUG); ?></th>
				</tr>
			</thead>
			<tbody class="wp_ulike_logs">
				<?php
				foreach ( $get_ulike_logs as $get_ulike_log )
				{
				?>
				<tr <?php if ($alternate == true) echo 'class="alternate"';?>>
				<td>
				<?php echo $get_ulike_log->id; ?>
				</td>
				<td>
				<?php
				$user_info = get_userdata($get_ulike_log->user_id);
				if($user_info)
				echo get_avatar( $user_info->user_email, 16, '' , 'avatar') . '<em> @' . $user_info->user_login . '</em>';
				else
				echo '<em> #'. __('Guest User',WP_ULIKE_SLUG) .'</em>';
				?>
				</td>
				<td>
				<?php
				$get_the_status = $get_ulike_log->status;
				if($get_the_status == 'like')
					echo '<i class="wp-ulike-icons-thumb_up"></i>';
				else
					echo '<i class="wp-ulike-icons-thumb_down"></i>';
				?>
				</td>
				<td>
				<?php echo $get_ulike_log->activity_id; ?>
				</td>
				<td>
				<?php printf( __( '<a href="%1$s">Activity Permalink</a>', WP_ULIKE_SLUG ), bp_activity_get_permalink( $get_ulike_log->activity_id ) ); ?>
				</td>
				<td>
				<?php
				echo wp_ulike_date_i18n($get_ulike_log->date_time);
				?>
				</td>
				<td>
				<?php echo $get_ulike_log->ip; ?>
				</td>
				<td>
				<button class="wp_ulike_delete button" type="button" data-nonce="<?php echo wp_create_nonce( 'ulike_activities' . $get_ulike_log->id ); ?>" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike_activities"><i class="dashicons dashicons-trash"></i></button>
				</td>
				<?php
				$alternate = !$alternate;
				}
				?>
				</tr>
			</tbody>
		</table>
		<div class="tablenav">
			<div class='tablenav-pages'>
				<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
				<?php echo $p->show();  // Echo out the list of paging. ?>
			</div>
		</div>
	</div>

<?php
	} else {
		echo "<div class='error'><p>" . __('<strong>ERROR:</strong> No Record Found. (This problem is created because you don\'t have any data on this table)',WP_ULIKE_SLUG) . "</p></div>";
	}
}

/**
 * Create WP ULike bbPress Logs page with separate pagination
 *
 * @author       	Alimir
 * @since           2.2
 * @updated         2.4.2
 * @return			String
 */
function wp_ulike_bbpress_likes_logs(){

	if( ! function_exists( 'is_bbpress' ) ) {
		echo sprintf( '<div class="wrap">' . __( '%s is Not Activated!', WP_ULIKE_SLUG ) . '</div>' ,__( 'bbPress', WP_ULIKE_SLUG ) );
		return;
	}

	global $wpdb;
	$alternate 	= true;
	$items 		= $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_forums");
	if($items > 0) {
			$p = new wp_ulike_pagination;
			$p->items($items);
			$p->limit(wp_ulike_logs_return_per_page()); // Limit entries per page
			$p->target("admin.php?page=wp-ulike-bbpress-logs");
			$p->calculate(); // Calculates what to show
			$p->parameterName('page_number');
			$p->adjacents(1); //No. of page away from the current page

			if(!isset($_GET['page_number'])) {
				$p->page = 1;
			} else {
				$p->page = $_GET['page_number'];
			}

			//Query for limit page_number
			$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;

	$get_ulike_logs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulike_forums ORDER BY id DESC ".$limit."");

?>
<div class="wrap">
	<h2><?php _e('WP ULike Logs', WP_ULIKE_SLUG); ?></h2>
	<h3><?php _e('Topics Likes Logs', WP_ULIKE_SLUG); ?></h3>
	<div class="tablenav">
		<div class='tablenav-pages'>
			<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
			<?php echo $p->show();  // Echo out the list of paging. ?>
		</div>
	</div>
	<table class="widefat">
		<thead>
			<tr>
				<th width="2%"><?php _e('ID', WP_ULIKE_SLUG); ?></th>
				<th width="10%"><?php _e('Username', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Status', WP_ULIKE_SLUG); ?></th>
				<th width="6%"><?php _e('Topic ID', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Topic Title', WP_ULIKE_SLUG); ?></th>
				<th width="20%"><?php _e('Date / Time', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('IP', WP_ULIKE_SLUG); ?></th>
				<th><?php _e('Actions', WP_ULIKE_SLUG); ?></th>
			</tr>
		</thead>
		<tbody class="wp_ulike_logs">
			<?php
			foreach ( $get_ulike_logs as $get_ulike_log )
			{
			?>
			<tr <?php if ($alternate == true) echo 'class="alternate"';?>>
			<td>
			<?php echo $get_ulike_log->id; ?>
			</td>
			<td>
			<?php
			$user_info = get_userdata($get_ulike_log->user_id);
			if($user_info)
			echo get_avatar( $user_info->user_email, 16, '' , 'avatar') . '<em> @' . $user_info->user_login . '</em>';
			else
			echo '<em> #'. __('Guest User',WP_ULIKE_SLUG) .'</em>';
			?>
			</td>
			<td>
			<?php
			$get_the_status = $get_ulike_log->status;
			if($get_the_status == 'like')
				echo '<i class="wp-ulike-icons-thumb_up"></i>';
			else
				echo '<i class="wp-ulike-icons-thumb_down"></i>';
			?>
			</td>
			<td>
			<?php echo $get_ulike_log->topic_id; ?>
			</td>
			<td>
			<?php echo '<a href="'.get_permalink($get_ulike_log->topic_id).'" title="'.get_the_title($get_ulike_log->topic_id).'">'.get_the_title($get_ulike_log->topic_id).'</a>'; ?>
			</td>
			<td>
			<?php
			echo wp_ulike_date_i18n($get_ulike_log->date_time);
			?>
			</td>
			<td>
			<?php echo $get_ulike_log->ip; ?>
			</td>
			<td>
			<button class="wp_ulike_delete button" type="button" data-nonce="<?php echo wp_create_nonce( 'ulike_forums' . $get_ulike_log->id ); ?>" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike_forums"><i class="dashicons dashicons-trash"></i></button>
			</td>
			<?php
			$alternate = !$alternate;
			}
			?>
			</tr>
		</tbody>
	</table>
	<div class="tablenav">
		<div class='tablenav-pages'>
			<span class="displaying-num"><?php echo $items . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
			<?php echo $p->show();  // Echo out the list of paging. ?>
		</div>
	</div>
</div>

<?php
	} else {
		echo "<div class='error'><p>" . __('<strong>ERROR:</strong> No Record Found. (This problem is created because you don\'t have any data on this table)',WP_ULIKE_SLUG) . "</p></div>";
	}
}

/*******************************************************
  Statistics Page
*******************************************************/

/**
 * Create WP ULike statistics with wp_ulike_stats class
 *
 * @author       	Alimir
 * @since           2.0
 * @return			String
 */
function wp_ulike_statistics(){

	$wp_ulike_stats = wp_ulike_stats::get_instance();

	$get_option  = get_option( 'wp_ulike_statistics_screen' );

	echo '<div class="wrap wp-ulike-container">';

	echo '<h2>' . __( 'WP ULike Statistics', WP_ULIKE_SLUG ) . '</h2>';

	/*******************************************************
	  Welcome Panel
	*******************************************************/
	// if($get_option['welcome_panel'] == 1){
	// 	echo '<div id="welcome-panel" class="welcome-panel"><div class="welcome-panel-content">';
	// 	echo '<h3>' . __('Welcome to WP ULike Statistics!',WP_ULIKE_SLUG) . '</h3>';
	// 	echo '<p class="about-description">' . __('We have provided some useful statistics tools in this page:',WP_ULIKE_SLUG) . '</p>';
	// 	echo '<div class="welcome-panel-column-container">';
	// 	echo '
	// 		<div class="welcome-panel-column">
	// 			<h4>'.__('Get Started').'</h4>
	// 			<a class="button button-primary button-hero" href="admin.php?page=wp-ulike-about">'.__( 'About WP ULike', WP_ULIKE_SLUG ).'</a>
	// 			<p class="hide-if-no-customize">'.__('or',WP_ULIKE_SLUG).', <a target="_blank" href="'.WP_ULIKE_PLUGIN_URI.'">'.__( 'Visit our homepage', WP_ULIKE_SLUG ).'</a></p>
	// 		</div>
	// 		<div class="welcome-panel-column">
	// 			<h4>'.__('Other Tools',WP_ULIKE_SLUG).'</h4>
	// 			<ul>
	// 				<li><a target="_blank" href="admin.php?page=wp-ulike-post-logs" class="welcome-icon welcome-view-site">'.__('Post Likes Logs',WP_ULIKE_SLUG).'</a></li>
	// 				<li><a target="_blank" href="admin.php?page=wp-ulike-comment-logs" class="welcome-icon welcome-view-site">'.__('Comment Likes Logs',WP_ULIKE_SLUG).'</a></li>
	// 				<li><a target="_blank" href="admin.php?page=wp-ulike-bp-logs" class="welcome-icon welcome-view-site">'.__('Activity Likes Logs',WP_ULIKE_SLUG).'</a></li>
	// 				<li><a target="_blank" href="admin.php?page=wp-ulike-bbpress-logs" class="welcome-icon welcome-view-site">'.__('Topics Likes Logs',WP_ULIKE_SLUG).'</a></li>
	// 			</ul>
	// 		</div>
	// 		<div class="welcome-panel-column welcome-panel-last">
	// 			<h4>'.__('Documentation').'</h4>
	// 			<ul>
	// 				<li><a  target="_blank" href="https://wordpress.org/support/plugin/wp-ulike" class="welcome-icon welcome-learn-more">'.__('Support',WP_ULIKE_SLUG).'</a></li>
	// 				<li><a  target="_blank" href="https://wordpress.org/plugins/wp-ulike/faq/" class="welcome-icon welcome-learn-more">'.__('FAQ',WP_ULIKE_SLUG).'</a></li>
	// 				<li><a  target="_blank" href="http://preview.alimir.ir/contact/" class="welcome-icon welcome-learn-more">'.__('Contact',WP_ULIKE_SLUG).'</a></li>
	// 				<li><a  target="_blank" href="https://github.com/Alimir/wp-ulike" class="welcome-icon welcome-learn-more">'.__('GitHub Repository',WP_ULIKE_SLUG).'</a></li>
	// 			</ul>
	// 		</div>
	// 	';
	// 	echo '</div></div></div>';
	// }

	echo '
      <div class="wp-ulike-row wp-ulike-logs-count">
        <div class="col-4">
			<div class="wp-ulike-inner wp-ulike-is-loading">
				<div class="wp-ulike-ajax-get-var" data-callback="count_all_logs" data-args="all">
					<div class="wp-ulike-row wp-ulike-flex">
						<div class="col-5">
							<div class="wp-ulike-icon">
								<i class="wp-ulike-icons-linegraph"></i>
							</div>
						</div>
						<div class="col-7">
							<span class="wp-ulike-var">...</span>
							<span class="wp-ulike-text">'.__('Total',WP_ULIKE_SLUG).'</span>
						</div>
					</div>
				</div>
			</div>
        </div>
        <div class="col-4">
			<div class="wp-ulike-inner wp-ulike-is-loading">
				<div class="wp-ulike-ajax-get-var" data-callback="count_all_logs" data-args="today">
					<div class="wp-ulike-row wp-ulike-flex">
						<div class="col-5">
							<div class="wp-ulike-icon">
								<i class="wp-ulike-icons-hourglass"></i>
							</div>
						</div>
						<div class="col-7">
							<span class="wp-ulike-var">...</span>
							<span class="wp-ulike-text">'.__('Today',WP_ULIKE_SLUG).'</span>
						</div>
					</div>
				</div>
			</div>
        </div>
        <div class="col-4">
			<div class="wp-ulike-inner wp-ulike-is-loading">
				<div class="wp-ulike-ajax-get-var" data-callback="count_all_logs" data-args="yesterday">
					<div class="wp-ulike-row wp-ulike-flex">
						<div class="col-5">
							<div class="wp-ulike-icon">
								<i class="wp-ulike-icons-bargraph"></i>
							</div>
						</div>
						<div class="col-7">
							<span class="wp-ulike-var">...</span>
							<span class="wp-ulike-text">'.__('Yesterday',WP_ULIKE_SLUG).'</span>
						</div>
					</div>
				</div>
			</div>
        </div>

      </div>
	';


	$charts_output = '';
	foreach ( $wp_ulike_stats->get_tables() as $type => $table) {
		// If this table has no data, then continue...
		if( ! $wp_ulike_stats->count_logs( array ( "table" => $table ) ) ) {
			continue;
		}
		// Else draw the summary section
		$charts_output .= '
			<div class="wp-ulike-row wp-ulike-summary-charts">
			    <div class="col-12">
			        <div class="wp-ulike-inner">
			            <div class="wp-ulike-row wp-ulike-flex">
			                <div class="col-8 wp-ulike-is-loading">
			                    <div class="wp-ulike-ajax-get-chart" data-callback="dataset" data-args="'.$table.'" data-args="yesterday">
			                        <canvas id="wp-ulike-'.$type.'-chart"></canvas>

			                    </div>
			                </div>
			                <div class="col-4 wp-ulike-is-loading">
			                    <div class="wp-ulike-ajax-get-var wp-ulike-flex" data-callback="count_logs" data-args="'.esc_attr( json_encode( array( "table" => $table, "date" => "week" ) ) ).'">
			                        <div class="wp-ulike-icon">
										<i class="wp-ulike-icons-magnifying-glass"></i>
									</div>
									<div class="wp-ulike-info">
				                        <span class="wp-ulike-var">...</span>
				                        <span class="wp-ulike-text">'.__('Weekly',WP_ULIKE_SLUG).'</span>
			                       	</div>
			                    </div>
			                    <div class="wp-ulike-ajax-get-var wp-ulike-flex" data-callback="count_logs" data-args="'.esc_attr( json_encode( array( "table" => $table, "date" => "month" ) ) ).'">
			                        <div class="wp-ulike-icon">
										<i class="wp-ulike-icons-bargraph"></i>
									</div>
									<div class="wp-ulike-info">
				                        <span class="wp-ulike-var">...</span>
				                        <span class="wp-ulike-text">'.__('Monthly',WP_ULIKE_SLUG).'</span>
			                       	</div>
			                    </div>
			                    <div class="wp-ulike-ajax-get-var wp-ulike-flex" data-callback="count_logs" data-args="'.esc_attr( json_encode( array( "table" => $table, "date" => "year" ) ) ).'">
			                        <div class="wp-ulike-icon">
										<i class="wp-ulike-icons-linegraph"></i>
									</div>
									<div class="wp-ulike-info">
				                        <span class="wp-ulike-var">...</span>
				                        <span class="wp-ulike-text">'.__('Yearly',WP_ULIKE_SLUG).'</span>
			                       	</div>
			                    </div>
			                    <div class="wp-ulike-ajax-get-var wp-ulike-flex" data-callback="count_logs" data-args="'.esc_attr( json_encode( array( "table" => $table, "date" => "all" ) ) ).'">
			                        <div class="wp-ulike-icon">
										<i class="wp-ulike-icons-global"></i>
									</div>
									<div class="wp-ulike-info">
				                        <span class="wp-ulike-var">...</span>
				                        <span class="wp-ulike-text">'.__('Totally',WP_ULIKE_SLUG).'</span>
			                       	</div>
			                    </div>
			                </div>
			            </div>
			        </div>
			    </div>
			</div>
		';
	}


	if( !empty( $charts_output ) ) {
		// Else draw the summary section
		$charts_output .= '
			<div class="wp-ulike-row wp-ulike-percent-charts">
			    <div class="col-12">
			        <div class="wp-ulike-inner">
			            <div class="wp-ulike-row wp-ulike-flex">
			                <div class="col-7 wp-ulike-is-loading">
			                    <div class="wp-ulike-draw-chart">
			                        <canvas id="wp-ulike-percent-chart"></canvas>
			                    </div>
			                </div>
			                <div class="col-5 wp-ulike-is-loading">
								<div class="wp-ulike-top-likers wp-ulike-ajax-get-var" data-callback="display_top_likers" data-args="">
									<div class="wp-ulike-var"></div>
			                	</div>
			                </div>
			            </div>
			        </div>
			    </div>
			</div>
		';
	}

	echo $charts_output;





		// if($get_option['piechart_stats'] == 1){
		// 	echo '
		// 	<div id="piechart_stats" class="postbox">
		// 		<div class="handlediv" title="Click to toggle"><br></div>
		// 		<h3 class="hndle"><span><i class="dashicons dashicons-chart-pie"></i> '.__('Likes Percent',WP_ULIKE_SLUG) . ' - ' . sprintf(__('In The Last %s Days',WP_ULIKE_SLUG), $get_option['days_number']).' </span></h3>
		// 		<div class="inside wp-ulike-is-loading">
		// 			<div class="main">
		// 			<div>
		// 				<canvas id="wp-ulike-percent-stats"></canvas>
		// 			</div>
		// 			</div>
		// 		</div>
		// 	</div>';
		// }

	echo '
		<div class="postbox-container" id="right-log">
		<div class="metabox-holder">
		<div class="meta-box-sortables ui-sortable">';


	if($get_option['top_likers'] == 1){
		$get_top_likers		= $wp_ulike_stats->get_top_likers();
		$top_users_counter  = 1;
		echo'
		<div class="postbox">
		<div class="handlediv" title="Click to toggle"><br></div>
		<h3 class="hndle"><span><i class="dashicons dashicons-awards"></i> '.__('Top Likers',WP_ULIKE_SLUG) . '</span></h3>
		<div class="inside">';

		foreach ($get_top_likers as $top_liker) {
		$get_top_user_id 	= stripslashes($top_liker->user_id);
		$get_top_user_info 	= get_userdata($get_top_user_id);
		$final_user_name	= __('Guest User',WP_ULIKE_SLUG);
		if($get_top_user_info != '')
		$final_user_name	= $get_top_user_info->display_name;
		echo'
			<div class="log-latest">
			<div class="log-item">
			<div class="log-page-title">'. $top_users_counter++ . ' - ' .$final_user_name.'</div>
			<div class="badge"><strong>'.$top_liker->SumUser.'</strong> '.__('Like',WP_ULIKE_SLUG) . '</div>
			<div class="left-div"><i class="dashicons dashicons-location"></i> <em dir="ltr">'.$top_liker->ip.'</em></div>
			</div>
			</div>
			';
		}
		echo '</div></div>';
	}

	if($get_option['top_posts'] == 1){
		echo'
		<div class="postbox">
		<div class="handlediv" title="Click to toggle"><br></div>
		<h3 class="hndle"><span><i class="dashicons dashicons-admin-post"></i> '.__('Most Liked Posts',WP_ULIKE_SLUG) . '</span></h3>
		<div class="inside"><div class="top-widget"><ol>';
		echo $wp_ulike_stats->get_tops('top_posts');
		echo '</ol></div></div></div>';
	}

	if($get_option['top_comments'] == 1){
		echo'
		<div class="postbox">
		<div class="handlediv" title="Click to toggle"><br></div>
		<h3 class="hndle"><span><i class="dashicons dashicons-admin-comments"></i> '.__('Most Liked Comments',WP_ULIKE_SLUG) . '</span></h3>
		<div class="inside"><div class="top-widget"><ol>';
		echo $wp_ulike_stats->get_tops('top_comments');
		echo '</ol></div></div></div>';
	}

	if($get_option['top_activities'] == 1){
		echo'
		<div class="postbox">
		<div class="handlediv" title="Click to toggle"><br></div>
		<h3 class="hndle"><span><i class="dashicons dashicons-groups"></i> '.__('Most Liked Activities',WP_ULIKE_SLUG) . '</span></h3>
		<div class="inside"><div class="top-widget"><ol>';
		echo $wp_ulike_stats->get_tops('top_activities');
		echo '</ol></div></div></div>';
	}

	if($get_option['top_topics'] == 1){
		echo'
		<div class="postbox">
		<div class="handlediv" title="Click to toggle"><br></div>
		<h3 class="hndle"><span><i class="dashicons dashicons-index-card"></i> '.__('Most Liked Topics',WP_ULIKE_SLUG) . '</span></h3>
		<div class="inside"><div class="top-widget"><ol>';
		echo $wp_ulike_stats->get_tops('top_topics');
		echo '</ol></div></div></div>';
	}

	echo '</div></div></div>';



	/*******************************************************
	  Second Column
	*******************************************************/


	echo '</div>'; //end wrap class

}

/**
 * The counter of last likes by the admin last login time.
 *
 * @author       	Alimir
 * @since           2.4.2
 * @return			String
 */
function wp_ulike_get_number_of_new_likes() {
	global $wpdb;

	if( isset( $_GET["page"] ) && stripos( $_GET["page"], "wp-ulike-statistics" ) !== false && is_super_admin() ) {
		update_option( 'wpulike_lastvisit', current_time( 'mysql' ) );
	}

    $request =  "SELECT
				(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'))
				+
				(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_activities WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'))
				+
				(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_comments WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'))
				+
				(SELECT COUNT(*) FROM ".$wpdb->prefix."ulike_forums WHERE (date_time<=NOW() AND date_time>='".get_option( 'wpulike_lastvisit')."'));";

	return $wpdb->get_var($request);
}
