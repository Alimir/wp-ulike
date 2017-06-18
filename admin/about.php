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
	 * Admin enqueue scripts for font-icons stylesheet
	 *
	 * @author       	Alimir	 	
	 * @since           2.7
	 * @return			Action
	 */ 
	add_action('admin_enqueue_scripts', 'wp_ulike_enqueue_font_icons');
	function wp_ulike_enqueue_font_icons($avatar) {
		wp_enqueue_style( 'wp_ulike_font_icons', plugins_url( 'classes/css/font-icons.css' , __FILE__ ) );
	}

	/**
	 * Add rating us notification on wp-ulike admin pages
	 *
	 * @author       	Alimir	 	
	 * @since           2.7
	 * @return			String
	 */ 
	add_action( 'admin_notices', 'wp_ulike_add_rating_star_notice', 999);
	function wp_ulike_add_rating_star_notice() {
		if(isset($_GET["page"]) && stripos($_GET["page"], "wp-ulike") !== false){
			?>
			<div class="notice notice-success" style="margin:15px 0">
				<p style="font-size:16px"><strong><?php _e('Like this plugin?',WP_ULIKE_SLUG); ?></strong> <?php _e('Show your support by Rating 5 Star in <a href="http://wordpress.org/plugins/wp-ulike"> Plugin Directory reviews</a>',WP_ULIKE_SLUG); ?></p>
			</div>
			<?php
		} else return;
	}

	/**
	 * Create WP ULike About page
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.1
	 * @updated         2.7 //Removed svg-source & updated some elements
	 * @return			String
	 */ 	
	function wp_ulike_about_page() {
	?>
	<style>
	.ulike-badge{
		background: url('<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/wp-ulike-badge.png') no-repeat scroll center 24px / 95px 95px #F3643A;
		color: #fff;
		font-size: 14px;
		text-align: center;
		font-weight: 600;
		margin: 5px 0px 0px;
		padding-top: 120px;
		height: 40px;
		display: inline-block;
		width: 150px;
		text-rendering: optimizelegibility;
		box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
	}
	.wp_ulike_version {
		display: inline-block;
		position: absolute;
		top: 54px;
		left:0;
		padding: 5px 10px;
		background: #e74c3c;
		color: #FFF;
		font-size: 13px;
		font-weight: normal;
	}
	.about-wrap .headline-feature h2 {
		margin: 1.1em 0px 0.2em;
		font-size: 2.4em;
		font-weight: 300;
		line-height: 1.3;
		text-align: center;
	}
	.about-wrap .icon-container {
		text-align: center;
		font-size: 6vw;
		border: 1px solid #757575;
		padding: 35px 5px;
	}	
	.about-wrap .featured-image img {
		border: none;
	}	
	<?php if(is_rtl()): ?>
	.about-wrap .ulike-badge {
		position: absolute;
		top: 0px;
		left: 0px;
	}
	.boxstyle {
		padding: 1px 12px;
		background-color: #FFF;
		border-right: 3px solid #757575;
	}		
	<?php else: ?>
	.about-wrap .ulike-badge {
		position: absolute;
		top: 0px;
		right: 0px;
	}
	.boxstyle {
		padding: 1px 12px;
		background-color: #FFF;
		border-left: 3px solid #757575;
	}		
	<?php endif; ?>
	</style>

	<div class="wrap about-wrap">

		<h1><?php echo _e('Welcome to WP ULike',WP_ULIKE_SLUG) . ' ' . WP_ULIKE_VERSION; ?></h1>

		<div class="about-text"><?php echo _e('Thank you for choosing WP ULike! This version is our leanest and most powerful version yet.', WP_ULIKE_SLUG) ; ?><br />
		<a target="_blank" href="<?php echo WP_ULIKE_PLUGIN_URI; ?>"> <?php _e('Visit our homepage',WP_ULIKE_SLUG); ?></a>
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
                <img alt="wp ulike intro" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/wp-ulike-intro.png">
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
				<a target="_blank" href="https://wordpress.org/plugins/blue-login-style"><img class="gravatar" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/blue-login-themes.jpg" alt="Blue Login Themes" /></a>
				<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
				<span class="title">Blue Login Themes</span>
			</li>									
			<li class="wp-person" id="wp-person-alimirzaei">
				<a target="_blank" href="https://wordpress.org/plugins/custom-fields-notifications/"><img class="gravatar" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/custom-fileds-notifications.png" alt="Custom Fields Notifications" /></a>
				<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
				<span class="title">Custom Fields Notifications</span>
			</li>									
			<li class="wp-person" id="wp-person-alimirzaei">
				<a target="_blank" href="http://wordpress.org/plugins/ajax-bootmodal-login/"><img class="gravatar" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/ajax-bootmodal-login.jpg" alt="Ajax BootModal Login" /></a>
				<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
				<span class="title">Ajax BootModal Login</span>
			</li>									
		</ul>	
		
		<h3 class="wp-people-group"><?php _e('Like this plugin?',WP_ULIKE_SLUG); ?></h3>
		<div class="boxstyle"><p><strong><?php _e('Show your support by Rating 5 Star in <a href="http://wordpress.org/plugins/wp-ulike"> Plugin Directory reviews</a>',WP_ULIKE_SLUG); ?></strong></p></div>
		
		<?php endif; ?>
							
	</div>
	<?php
	}