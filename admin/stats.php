<?php
	//include wp_ulike_stats class & geoIPloc functions
	if(isset($_GET["page"]) && stripos($_GET["page"], "wp-ulike-statistics") !== false){
		//include class charts
		include( plugin_dir_path(__FILE__) . 'classes/class-charts.php');
		//include PHP GeoIPLocation Library
		include( plugin_dir_path(__FILE__) . 'classes/tmp/geoiploc.php');
	} else return;
	
	/**
	 * Create WP ULike statistics with wp_ulike_stats class
	 *
	 * @author       	Alimir	 	
	 * @since           2.0	
	 * @updated         2.3	
	 * @return			String
	 */	
	function wp_ulike_statistics(){
	global $wp_ulike_stats;	
	$get_option  = get_option( 'wp_ulike_statistics_screen' );
	
	echo '<div class="wrap">';
	echo '<h2>' . __( 'WP ULike Statistics', WP_ULIKE_SLUG ) . '</h2>';
	
	/*******************************************************
	  Welcome Panel
	*******************************************************/
	if($get_option['welcome_panel'] == 1){
	echo '<div id="welcome-panel" class="welcome-panel"><div class="welcome-panel-content">';
	echo '<h3>' . __('Welcome to WP ULike Statistics!',WP_ULIKE_SLUG) . '</h3>';
	echo '<p class="about-description">' . __('We have provided some useful statistics tools in this page:',WP_ULIKE_SLUG) . '</p>';
	echo '<div class="welcome-panel-column-container">';
	echo '
		<div class="welcome-panel-column">
			<h4>'.__('Get Started').'</h4>
			<a class="button button-primary button-hero" href="admin.php?page=wp-ulike-about">'.__( 'About WP ULike', WP_ULIKE_SLUG ).'</a>
			<p class="hide-if-no-customize">'.__('or',WP_ULIKE_SLUG).', <a href="http://preview.alimir.ir/developer/wp-ulike/">'.__( 'Visit our homepage', WP_ULIKE_SLUG ).'</a></p>
		</div>
		<div class="welcome-panel-column">
			<h4>'.__('Other Tools',WP_ULIKE_SLUG).'</h4>
			<ul>
				<li><a target="_blank" href="admin.php?page=wp-ulike-post-logs" class="welcome-icon welcome-view-site">'.__('Post Likes Logs',WP_ULIKE_SLUG).'</a></li>
				<li><a target="_blank" href="admin.php?page=wp-ulike-comment-logs" class="welcome-icon welcome-view-site">'.__('Comment Likes Logs',WP_ULIKE_SLUG).'</a></li>
				<li><a target="_blank" href="admin.php?page=wp-ulike-bp-logs" class="welcome-icon welcome-view-site">'.__('Activity Likes Logs',WP_ULIKE_SLUG).'</a></li>
				<li><a target="_blank" href="admin.php?page=wp-ulike-bbpress-logs" class="welcome-icon welcome-view-site">'.__('Topics Likes Logs',WP_ULIKE_SLUG).'</a></li>
			</ul>
		</div>
		<div class="welcome-panel-column welcome-panel-last">
			<h4>'.__('Documentation').'</h4>
			<ul>
				<li><a  target="_blank" href="https://wordpress.org/support/plugin/wp-ulike" class="welcome-icon welcome-learn-more">'.__('Support',WP_ULIKE_SLUG).'</a></li>
				<li><a  target="_blank" href="https://wordpress.org/plugins/wp-ulike/faq/" class="welcome-icon welcome-learn-more">'.__('FAQ',WP_ULIKE_SLUG).'</a></li>
				<li><a  target="_blank" href="http://preview.alimir.ir/contact/" class="welcome-icon welcome-learn-more">'.__('Contact',WP_ULIKE_SLUG).'</a></li>
				<li><a  target="_blank" href="https://github.com/Alimir/wp-ulike" class="welcome-icon welcome-learn-more">'.__('GitHub Repository',WP_ULIKE_SLUG).'</a></li>
			</ul>
		</div>
	';
	echo '</div></div></div>';
	}
	
	/*******************************************************
	  First Column
	*******************************************************/
	$total_likes = 0;
	
	echo '
		<div class="postbox-container" id="right-log">
		<div class="metabox-holder">
		<div class="meta-box-sortables ui-sortable">';
	
	if( isset($get_option) && $get_option['summary_like_stats'] == 1){
		
		$SummaryArr = array(
			"posts" => array(
				"id" 		=> "posts_likes_stats",
				"type" 		=> "ulike",
				"table" 	=> "postmeta",
				"key" 		=> "_liked",
				"dashicons" => "dashicons-admin-post",
				"title" 	=> __('Posts Likes Summary',WP_ULIKE_SLUG)
			),
			"comments" => array(
				"id" 		=> "comments_likes_stats",
				"type" 		=> "ulike_comments",
				"table" 	=> "commentmeta",
				"key" 		=> "_commentliked",
				"dashicons" => "dashicons-admin-comments",
				"title" 	=> __('Comments Likes Summary',WP_ULIKE_SLUG)
			),
			"activities" 	=> array(
				"id" 		=> "activities_likes_stats",
				"type" 		=> "ulike_activities",
				"table" 	=> "bp_activity_meta",
				"key" 		=> "_activityliked",
				"dashicons" => "dashicons-groups",
				"title" 	=> __('Activities Likes Summary',WP_ULIKE_SLUG)
			),
			"topics" 		=> array(
				"id" 		=> "topics_likes_stats",
				"type" 		=> "ulike_forums",
				"table" 	=> "postmeta",
				"key" 		=> "_topicliked",
				"dashicons" => "dashicons-admin-post",
				"title" 	=> __('Topics Likes Summary',WP_ULIKE_SLUG)
			)
		);	
		
		foreach ($SummaryArr as $SummaryTotal) {
			$total_likes	+=	$wp_ulike_stats->get_all_data_date($SummaryTotal["table"],$SummaryTotal["key"]);
		}
		echo'
			<div style="display: block;" class="postbox">
			<div class="handlediv" title="Click to toggle"><br></div>
			<h3 class="hndle"><span><i class="dashicons dashicons-chart-bar"></i> '.__('Summary',WP_ULIKE_SLUG).'</span></h3>
			<div class="inside">';		
		
		foreach ($SummaryArr as $SummaryVal) {
		
			echo'<table class="widefat table-stats" id="summary-stats" width="100%"><tbody>';
			
			if($get_option[$SummaryVal['id']] == 1){
			
			if($SummaryVal["id"] == 'posts_likes_stats'){
				echo'
				<tr>
					<th><i class="dashicons dashicons-pressthis"></i> '.__('Total Likes',WP_ULIKE_SLUG).':</th>
					<th colspan="2" id="th-colspan"><span>'.$total_likes.'</span></th>
				</tr>';
			}	
			
			echo'

			<tr>
				<th colspan="3" style="text-align: center; font-weight:bold;"><br><hr>'.$SummaryVal["title"].'<hr><br></th>
			</tr>			
			
			<tr>
				<th><i class="dashicons dashicons-star-filled"></i> '. __('Today',WP_ULIKE_SLUG) .':</th>
				<th class="th-center"><span>'. $wp_ulike_stats->get_data_date($SummaryVal["type"],'today').'</span></th>
			</tr>
			
			<tr>
				<th><i class="dashicons dashicons-star-empty"></i> '. __('Yesterday',WP_ULIKE_SLUG) .':</th>
				<th class="th-center"><span>'. $wp_ulike_stats->get_data_date($SummaryVal["type"],'yesterday').'</span></th>
			</tr>
			
			<tr>
				<th><i class="dashicons dashicons-calendar"></i> '. __('Week',WP_ULIKE_SLUG) .':</th>
				<th class="th-center"><span>'. $wp_ulike_stats->get_data_date($SummaryVal["type"],'week').'</span></th>
			</tr>
			
			<tr>
				<th><i class="dashicons dashicons-flag"></i> '. __('Month',WP_ULIKE_SLUG) .':</th>
				<th class="th-center"><span>'. $wp_ulike_stats->get_data_date($SummaryVal["type"],'month').'</span></th>
			</tr>
			
			<tr>
				<th><i class="dashicons dashicons-chart-area"></i> '. __('Total',WP_ULIKE_SLUG) .':</th>
				<th class="th-center"><span>'. $wp_ulike_stats->get_all_data_date($SummaryVal["table"],$SummaryVal["key"]).'</span></th>
			</tr>';
			
			}
			
			echo '</tbody></table>';
			
		}
		
		echo '</div></div>';
	}
	
	if($get_option['likers_map'] == 1){
		echo '
		<div id="world_map" class="postbox">
			<div class="handlediv" title="Click to toggle"><br></div>
			<h3 class="hndle"><span><i class="dashicons dashicons-location-alt"></i> '.__('Likers World Map',WP_ULIKE_SLUG) . '</span></h3>
			<div class="inside">
				<div class="main">
				<div>
					<div id="vmap" style="width: 100%; min-height: 250px;"></div>
				</div>
				</div>
			</div>
		</div>';
	}
	
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
			<div class="right-div badge"><strong>'.$top_liker->SumUser.'</strong> '.__('Like',WP_ULIKE_SLUG) . '</div>
			<div class="left-div"><i class="dashicons dashicons-location"></i> <em dir="ltr">'.$top_liker->ip.'</em> | '.getCountryFromIP($top_liker->ip, "NamE").'</div>
			</div>
			</div>
			';
		}
		echo '</div></div>';
	}
	
	echo '</div></div></div>';
	
	

	/*******************************************************
	  Second Column
	*******************************************************/
	
	if(isset($get_option)){
	
		$ChartsArr = array(
			"posts" => array(
				"id" 		=> "posts_likes_stats",
				"view_logs" => ' <a style="text-decoration:none;" href="?page=wp-ulike-post-logs" target="_blank"><i class="dashicons dashicons-visibility"></i> '. __('View Logs',WP_ULIKE_SLUG) .'</a>',
				"title" 	=> __('Posts Likes Stats',WP_ULIKE_SLUG) . ' - ' . sprintf(__('In The Last %s Days',WP_ULIKE_SLUG), $get_option['days_number']),
				"chart" 	=> "chart1"
			),
			"comments" => array(
				"id" 		=> "comments_likes_stats",
				"view_logs" => ' <a style="text-decoration:none;" href="?page=wp-ulike-comment-logs" target="_blank"><i class="dashicons dashicons-visibility"></i> '. __('View Logs',WP_ULIKE_SLUG) .'</a>',
				"title" 	=> __('Comments Likes Stats',WP_ULIKE_SLUG) . ' - ' . sprintf(__('In The Last %s Days',WP_ULIKE_SLUG), $get_option['days_number']),
				"chart" 	=> "chart2"
			),			
			"activities" => array(
				"id" 		=> "activities_likes_stats",
				"view_logs" => ' <a style="text-decoration:none;" href="?page=wp-ulike-bp-logs" target="_blank"><i class="dashicons dashicons-visibility"></i> '. __('View Logs',WP_ULIKE_SLUG) .'</a>',
				"title" 	=> __('Activities Likes Stats',WP_ULIKE_SLUG) . ' - ' . sprintf(__('In The Last %s Days',WP_ULIKE_SLUG), $get_option['days_number']),
				"chart" 	=> "chart3"
			),
			"topics" 		=> array(
				"id" 		=> "topics_likes_stats",
				"view_logs" => ' <a style="text-decoration:none;" href="?page=wp-ulike-bbpress-logs" target="_blank"><i class="dashicons dashicons-visibility"></i> '. __('View Logs',WP_ULIKE_SLUG) .'</a>',
				"title" 	=> __('Topics Likes Stats',WP_ULIKE_SLUG) . ' - ' . sprintf(__('In The Last %s Days',WP_ULIKE_SLUG), $get_option['days_number']),
				"chart" 	=> "chart4"
			)
		);
		
		echo '
			<div class="postbox-container" id="left-log">
			<div class="metabox-holder">
			<div class="meta-box-sortables ui-sortable">';	
		
		foreach ($ChartsArr as $ChartArr) {
			if($get_option[$ChartArr['id']] == 1){
				echo '
				<div id="'.$ChartArr['id'].'" class="postbox">
					<div class="handlediv" title="Click to toggle"><br></div>
					<h3 class="hndle"><span><i class="dashicons dashicons-chart-line"></i> '.$ChartArr['title'] . $ChartArr['view_logs'].' </span></h3>
					<div class="inside">
						<div class="main">
						<div>
							<canvas id="'.$ChartArr['chart'].'"></canvas>
						</div>
						</div>
					</div>
				</div>';
			}
		}
		
		if($get_option['piechart_stats'] == 1){
			echo '
			<div id="piechart_stats" class="postbox">
				<div class="handlediv" title="Click to toggle"><br></div>
				<h3 class="hndle"><span><i class="dashicons dashicons-chart-pie"></i> '.__('Likes Percent',WP_ULIKE_SLUG) . ' - ' . sprintf(__('In The Last %s Days',WP_ULIKE_SLUG), $get_option['days_number']).' </span></h3>
				<div class="inside">
					<div class="main">
					<div>
						<canvas id="piechart"></canvas>
					</div>
					</div>
				</div>
			</div>';		
		}
		
		echo '</div></div></div>';
	}
	
	echo '</div>'; //end wrap class
	
	}
	
	/**
	 * Register Screen Options
	 *
	 * @author       	Alimir	 	
	 * @since           2.1	
	 * @return			Void
	 */
	function wp_ulike_statistics_register_option(){
		$screen = get_current_screen();
		add_filter('screen_layout_columns', 'wp_ulike_statistics_display_option');
		$screen->add_option('wp_ulike_statistics_screen');
	}


	/**
	 * Display Screen Options
	 *
	 * @author       	Alimir	 	
	 * @since           2.1	
	 * @updated         2.3	
	 * @return			Void
	 */	
	function wp_ulike_statistics_display_option(){
	$get_option = get_option( 'wp_ulike_statistics_screen' );
	
	if(!$get_option){
		$options = array(
		  'welcome_panel'			=> 1,
		  'summary_like_stats'		=> 1,
		  'posts_likes_stats'		=> 1,
		  'comments_likes_stats'	=> 1,
		  'activities_likes_stats'	=> 0,
		  'topics_likes_stats'		=> 0,
		  'most_liked_posts'		=> 1,
		  'most_liked_comments'		=> 1,
		  'piechart_stats'			=> 1,
		  'likers_map'				=> 1,
		  'top_likers'				=> 1,
		  'days_number'				=> 20
		);
		update_option('wp_ulike_statistics_screen',$options);	
	}

	?>
	<div style="display: block;" id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab">
		<form name="wp_ulike_statistics_screen_form" method="post">
			<h5><?php echo _e('Show on screen'); ?></h5>
			<div class="metabox-prefs">
				<label><input class="hide-postbox-tog" name="wp_ulike_welcome" type="checkbox" value="1" <?php checked( '1', $get_option['welcome_panel'] ); ?>><?php echo _e('Welcome',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_summary_stats" type="checkbox" value="1" <?php checked( '1', $get_option['summary_like_stats'] ); ?>><?php echo _e('Summary',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_posts_stats" type="checkbox" value="1" <?php checked( '1', $get_option['posts_likes_stats'] ); ?>><?php echo _e('Posts Likes Stats',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_comments_stats" type="checkbox" value="1" <?php checked( '1', $get_option['comments_likes_stats'] ); ?>><?php echo _e('Comments Likes Stats',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_activities_stats" type="checkbox" value="1" <?php checked( '1', $get_option['activities_likes_stats'] ); ?>><?php echo _e('Activities Likes Stats',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_topics_stats" type="checkbox" value="1" <?php checked( '1', $get_option['topics_likes_stats'] ); ?>><?php echo _e('Topics Likes Stats',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_piechart_stats" type="checkbox" value="1" <?php checked( '1', $get_option['piechart_stats'] ); ?>><?php echo _e('Likes Percent',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_likers_map" type="checkbox" value="1" <?php checked( '1', $get_option['likers_map'] ); ?>><?php echo _e('Likers World Map',WP_ULIKE_SLUG); ?></label>
				<label><input class="hide-postbox-tog" name="wp_ulike_top_likers" type="checkbox" value="1" <?php checked( '1', $get_option['top_likers'] ); ?>><?php echo _e('Top Likers',WP_ULIKE_SLUG); ?></label>
				<br class="clear">
				<input step="1" min="5" max="60" class="screen-per-page" name="wp_ulike_days_number" maxlength="3" value="<?php echo $get_option['days_number']; ?>" type="number">
				<label><?php echo _e('Days',WP_ULIKE_SLUG); ?></label>
				<input name="screen-options-apply" class="button button-primary" value="<?php echo _e('Save Settings',WP_ULIKE_SLUG); ?>" type="submit">
				<?php wp_nonce_field( 'wp_ulike_statistics_nonce_field', 'wp_ulike_statistics_screen' ); ?>
			</div>
		</form>
	</div>
	<?php

	}

	/**
	 * Save screen options with "update_option" mehtod
	 *
	 * @author       	Alimir	 	
	 * @since           2.1
	 * @updated         2.3		 
	 * @return			Void
	 */	
	add_action('admin_init', 'wp_ulike_statistics_save_option');
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
			  'days_number'				=> isset($_POST['wp_ulike_days_number']) 		? $_POST['wp_ulike_days_number'] 		: 20
			);
			update_option( 'wp_ulike_statistics_screen', $options );
		}
	}