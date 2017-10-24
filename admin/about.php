<?php 

	/**
	 * remove photo class from gravatar
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @since           2.7 //added wp_ulike prefix to function name
	 * @return			String
	 */ 
	add_filter('get_avatar', 'wp_ulike_remove_photo_class');
	function wp_ulike_remove_photo_class($avatar) {
		return str_replace(' photo', ' gravatar', $avatar);
	}

	/**
	 * Create WP ULike About page
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.1
	 * @updated         2.7 //Removed svg-source & updated some elements
	 * @updated         2.9
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
            <img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/wp-ulike-badge.png" alt="Instagram Feed">
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