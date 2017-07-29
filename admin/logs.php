<?php

	//include pagination class
	include( plugin_dir_path(__FILE__) . 'classes/class-pagination.php');
	
	
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
	 * Set the option of per_page
	 *
	 * @author       	Alimir	 	
	 * @since           2.1
	 * @return			String
	 */		 
	add_filter('set-screen-option', 'wp_ulike_logs_per_page_set_option', 10, 3);
	function wp_ulike_logs_per_page_set_option($status, $option, $value) {
		if ( 'wp_ulike_logs_per_page' == $option ) return $value;
		return $status;
	}
	
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
	 * admin enqueue scripts
	 *
	 * @author       	Alimir	 	
	 * @since           2.1
	 * @return			Void
	 */		
	add_action('admin_enqueue_scripts', 'wp_ulike_logs_enqueue_script');
	function wp_ulike_logs_enqueue_script($hook){
		$currentScreen 	= get_current_screen();
		
		if ( $currentScreen->id != $hook ) {
			return;
		}
		
		wp_enqueue_script( 'jquery' );
		
		wp_register_script('wp_ulike_logs', plugins_url( 'classes/js/logs.js' , __FILE__ ), array('jquery'), null, true);
		wp_enqueue_script('wp_ulike_logs');
		
		//localize script
		wp_localize_script( 'wp_ulike_logs', 'wp_ulike_logs', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'message' => __('Are you sure to remove this item?!',WP_ULIKE_SLUG)
		));
		
	}

	/**
	 * Remove logs from tables
	 *
	 * @author       	Alimir	 	
	 * @since           2.1
	 * @return			Void
	 */			
	add_action('wp_ajax_ulikelogs','wp_ulike_logs_process');
	function wp_ulike_logs_process(){
		global $wpdb;
		$id 	= $_POST['id'];
		$table 	= $_POST['table'];
		$wpdb->delete( $wpdb->prefix.$table ,array( 'id' => $id ));
		wp_die();
	}

	/*******************************************************
	  Post Logs Page
	*******************************************************/	
	/**
	 * Create WP ULike Post Logs page with separate pagination
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.1	
	 * @updated         2.4.2	
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
				echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/like.png" alt="like" width="24"/>';
				else
				echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/unlike.png" alt="unlike" width="24"/>';
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
				<button class="wp_ulike_delete button" type="button" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike"><i class="dashicons dashicons-trash"></i></button> 
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
	  Comment Logs Page
	*******************************************************/		
	/**
	 * Create WP ULike Comment Logs page with separate pagination
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.1	
	 * @updated         2.4.2	
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
			<tbody>
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
				echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/like.png" alt="like" width="24"/>';
				else
				echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/unlike.png" alt="unlike" width="24"/>';
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
				<button class="wp_ulike_delete button" type="button" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike_comments"><i class="dashicons dashicons-trash"></i></button> 
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
	  BuddyPress Logs Page
	*******************************************************/		
	/**
	 * Create WP ULike BuddyPress Logs page with separate pagination
	 *
	 * @author       	Alimir	 	
	 * @since           1.7
	 * @updated         2.1	
	 * @updated         2.4.2
	 * @return			String
	 */	
	function wp_ulike_buddypress_likes_logs(){
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
				<tbody>
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
					echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/like.png" alt="like" width="24"/>';
					else
					echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/unlike.png" alt="unlike" width="24"/>';
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
					<button class="wp_ulike_delete button" type="button" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike_activities"><i class="dashicons dashicons-trash"></i></button> 
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
	  Topics Logs Page
	*******************************************************/		
	/**
	 * Create WP ULike bbPress Logs page with separate pagination
	 *
	 * @author       	Alimir	 	
	 * @since           2.2
	 * @updated         2.4.2
	 * @return			String
	 */	
	function wp_ulike_bbpress_likes_logs(){
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
				echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/like.png" alt="like" width="24"/>';
				else
				echo '<img src="'.plugin_dir_url( __FILE__ ).'/classes/img/unlike.png" alt="unlike" width="24"/>';
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
				<button class="wp_ulike_delete button" type="button" data-id="<?php echo $get_ulike_log->id;?>" data-table="ulike_forums"><i class="dashicons dashicons-trash"></i></button> 
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
	
	