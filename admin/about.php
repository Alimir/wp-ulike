<?php 

	/**
	 * remove photo class from gravatar
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @return			String
	 */ 
	add_filter('get_avatar', 'remove_photo_class');
	function remove_photo_class($avatar) {
		return str_replace(' photo', ' gravatar', $avatar);
	}

	/**
	 * Create WP ULike About page
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.1
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
	.boxstyle {
		padding: 1px 12px;
		background-color: #FFF;
		box-shadow: 0px 1px 1px 0px rgba(0, 0, 0, 0.1);
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
	<?php if(is_rtl()): ?>
	.about-wrap .ulike-badge {
		position: absolute;
		top: 0px;
		left: 0px;
	}
	<?php else: ?>
	.about-wrap .ulike-badge {
		position: absolute;
		top: 0px;
		right: 0px;
	}
	<?php endif; ?>
	</style>

	<div class="wrap about-wrap">

		<h1><?php echo _e('Welcome to WP ULike','alimir') . ' ' . wp_ulike_get_version(); ?></h1>

		<div class="about-text"><?php echo _e('Thank you for choosing WP ULike! This version is our leanest and most powerful version yet.', 'alimir') ; ?><br />
		<a target="_blank" href="http://preview.alimir.ir/developer/wp-ulike/"> <?php _e('Visit our homepage','alimir'); ?></a>
		</div>
		<div class="ulike-badge"><?php echo _e('Version','alimir') . ' ' . wp_ulike_get_version(); ?></div>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if(!isset($_GET["credit"])) echo 'nav-tab-active'; ?>" href="admin.php?page=wp-ulike-about"><?php echo _e('Getting Started','alimir'); ?></a> 
			<a class="nav-tab <?php if(isset($_GET["credit"])) echo 'nav-tab-active'; ?>" href="admin.php?page=wp-ulike-about&credit=true"><?php echo _e('Credits','alimir'); ?></a> 
			<a target="_blank" class="nav-tab" href="https://wordpress.org/support/plugin/wp-ulike"><?php echo _e('Support','alimir'); ?></a> 
			<a target="_blank" class="nav-tab" href="https://wordpress.org/plugins/wp-ulike/faq/"><?php echo _e('FAQ','alimir'); ?></a> 
			<a target="_blank" class="nav-tab" href="https://wordpress.org/support/view/plugin-reviews/wp-ulike"><?php echo _e('Reviews','alimir'); ?></a> 
			<a target="_blank" class="nav-tab" href="http://preview.alimir.ir/contact/"><?php echo _e('Contact','alimir'); ?></a> 
		</h2>
		
		<?php if(!isset($_GET["credit"])): ?>
		
		<div class="changelog">
			<img class="about-overview-img" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/wp-ulike-banner.png" alt="WP ULike" />
		</div>
		
		<hr />
		
		<div class="changelog">
			<h2 class="about-headline-callout"><?php echo _e('Novelty Of WP ULike','alimir'); ?></h2>

			<div class="feature-section col two-col">
				<div>
					<h4><?php echo _e('bbPress Likes Support + Options & Statistics Tools','alimir'); ?> <small class="wp_ulike_version">WP ULike 2.2</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/bbpress-support.png" alt="WP ULike" />
				</div>

				<div class="last-feature">
					<h4><?php echo _e('Added More Than 20 Language Files','alimir'); ?> <small class="wp_ulike_version">WP ULike 2.2</small></h4>
					<a target="_blank" href="https://www.transifex.com/projects/p/wp-ulike/" title"WP-Translations"><img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/wp-translations.png" alt="WP ULike" /></a>
				</div>
			</div>	
			
			
			<div class="feature-section col two-col">
				<div>
					<h4><?php echo _e('New Statistics Design + Screen Option','alimir'); ?> <small class="wp_ulike_version">WP ULike 2.1</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/new-statistics.jpg" alt="WP ULike" />
				</div>

				<div class="last-feature">
					<h4><?php echo _e('New Statistics Page','alimir'); ?> <small class="wp_ulike_version">WP ULike 2.0</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/ulike-stats.png" alt="WP ULike" />
				</div>
			</div>	
			
			
			<div class="feature-section col two-col">
				<div>
					<h4><?php echo _e('New Class-based programming','alimir'); ?> <small class="wp_ulike_version">WP ULike 2.0</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/new-classes.jpg" alt="WP ULike" />
				</div>
				
				<div class="last-feature">
					<h4><?php echo _e('Most Liked Comments','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.9</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/most-liked-comments-widget.jpg" alt="WP ULike" />
				</div>
			</div>
			
			<div class="feature-section col three-col">
				<div>
					<h4><?php echo _e('Logging Method','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.9</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/loggin-method-option.jpg" alt="WP ULike" />
				</div>	
			
				<div>
					<h4><?php echo _e('New options in logs pages','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.9</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/logs-pages-options.jpg" alt="WP ULike" />
				</div>
				
				<div class="last-feature">
					<h4><?php echo _e('New setting panel','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.8</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/new-setting.png" alt="WP ULike" />
				</div>
			</div>			
			
			<div class="feature-section col three-col">
				<div>
					<h4><?php echo _e('Better coding on plugin files','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.8</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/new-coding.png" alt="WP ULike" />
				</div>		
			
				<div>
					<h4><?php echo _e('Buddypress likes support','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.7</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/activity-likes.png" alt="WP ULike" />
				</div>

				<div class="last-feature">
					<h4><?php echo _e('Likes logs support','alimir'); ?> <small class="wp_ulike_version">WP ULike 1.7</small></h4>
					<img style="width:100%" src="<?php echo plugins_url('/assets', dirname(__FILE__)); ?>/img/new-tools/likes-logs.png" alt="WP ULike" />
				</div>
			</div>

		</div>
		
		<?php else: ?>
		
		<p class="about-description"><?php echo _e('WP ULike is created by many love and time. Enjoy it :)','alimir'); ?></p>	
		<h4 class="wp-people-group"><?php echo _e('Project Leaders','alimir'); ?></h4>
		<ul class="wp-people-group">
			<li class="wp-person" id="wp-person-alimirzaei">
				<a href="http://about.alimir.ir"><?php echo get_avatar( 'info@alimir.ir', 64 ); ?></a>
				<a class="web" href="https://profiles.wordpress.org/alimir/">Ali Mirzaei</a>
				<span class="title"><?php echo _e('Project Lead & Developer','alimir'); ?></span>
			</li>					
		</ul>
			
		<h4 class="wp-people-group"><?php _e('Translations','alimir'); ?></h4>
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
		
		<p class="about-description"><?php _e('Would you like to help translate the plugin into more languages?','alimir'); ?> <a target="_blank" href="https://www.transifex.com/projects/p/wp-ulike/" title"WP-Translations">[<?php _e('Join our WP-Translations Community','alimir'); ?>]</a></p>	
		
		<h4 class="wp-people-group"><?php echo _e('Other Plugins','alimir'); ?></h4>
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
		
		<h4 class="wp-people-group"><?php _e('Like this plugin?','alimir'); ?></h4>
		<div class="boxstyle"><p><strong><?php _e('Show your support by Rating 5 Star in <a href="http://wordpress.org/plugins/wp-ulike"> Plugin Directory reviews</a>','alimir'); ?></strong></p></div>
		
		<?php endif; ?>
							
	</div>
	<?php
	}