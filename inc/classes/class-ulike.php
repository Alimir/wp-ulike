<?php 
if ( ! class_exists( 'wp_ulike' ) ) {

	class wp_ulike{
		private $wpdb;

		/**
		 * Constructor
		 */			
		public function __construct()
		{
			global $wpdb;
			$this->wpdb = $wpdb;
		}
		
		/**
		 * Select logging method
		 *
		 * @author       	Alimir
		 * @param           Array $data
		 * @since           2.0
		 * @return			String
		 */				
		public function wp_get_ulike(array $data){
			//get loggin method option
			$loggin_method = wp_ulike_get_setting( $data['setting'], 'logging_method');
			
			//select function from logging method
			if($loggin_method == 'do_not_log')
			return $this->do_not_log_method($data);
			
			else if($loggin_method == 'by_cookie')
			return $this->loggedby_cookie_method($data);
			
			else if($loggin_method == 'by_ip')
			return $this->loggedby_ip_method($data);
			
			else
			return $this->loggedby_other_ways($data);
		}

		/**
		 * Do not log method
		 *
		 * @author       	Alimir
		 * @param           Array $data
		 * @since           2.0
		 * @updated         2.3
		 * @return			String
		 */			
		public function do_not_log_method(array $data){
			$liked 		= wp_ulike_format_number($data["get_like"]);
			$template 	= $this->get_template($data["id"],$data["method"],$liked,2,2);
			$counter 	= '';
			
			if($data["type"] == 'post'){
				if (wp_ulike_get_setting( 'wp_ulike_general', 'button_type') == 'image') {
					$counter = $template['like_img'];
				}
				else {
					$counter = $template['like_text'];
				}
			}//end post button
			else if($data["type"] == 'process'){
				$newLike = $data["get_like"] + 1;
				$this->update_meta_data($data["id"], $data["key"], $newLike);
				$this->wpdb->query("INSERT INTO ".$this->wpdb->prefix.$data['table']." VALUES ('', '".$data['id']."', NOW(), '".$data['user_ip']."', '".$data['user_id']."', 'like')");
				if(is_user_logged_in()){
					wp_ulike_bp_activity_add($data['user_id'],$data['id'],$data['key']);
				}
				do_action('wp_ulike_mycred_like', $data['id'], $data['key']);
				$counter = wp_ulike_format_number($newLike);
			}//end post process
			return $counter;			
		}

		/**
		 * Logged by cookie method
		 *
		 * @author       	Alimir
		 * @param           Array $data
		 * @since           2.0
		 * @updated         2.3
		 * @return			String
		 */		
		public function loggedby_cookie_method(array $data){
			$liked 			= wp_ulike_format_number($data["get_like"]);
			$template 		= $this->get_template($data["id"],$data["method"],$liked,1,2);
			$condition 		= isset($_COOKIE[$data["cookie"].$data["id"]]);
			$button_type 	= wp_ulike_get_setting( 'wp_ulike_general', 'button_type');
			$counter 		= '';
			
			if($data["type"] == 'post'){
				if(!$condition){
					if ($button_type == 'image') {
						$counter = $template['like_img'];
					}
					else {
						$counter = $template['like_text'];
					}
				}
				else{
					if ($button_type == 'image') {
						$counter = $template['permission_img'];
					}
					else {
						$counter = $template['permission_text'];
					}				
				}
			}//end post button
			else if($data["type"] == 'process'){
				if(!$condition){
					$newLike = $data["get_like"] + 1;
					$this->update_meta_data($data["id"], $data["key"], $newLike);
					setcookie($data["cookie"].$data["id"], time(), time()+3600*24*365, '/');
					$this->wpdb->query("INSERT INTO ".$this->wpdb->prefix.$data['table']." VALUES ('', '".$data['id']."', NOW(), '".$data['user_ip']."', '".$data['user_id']."', 'like')");
					if(is_user_logged_in()){
						wp_ulike_bp_activity_add($data['user_id'],$data['id'],$data['key']);
					}
					do_action('wp_ulike_mycred_like', $data['id'], $data['key']);
					$counter = wp_ulike_format_number($newLike);
				}
				else{
					$counter = wp_ulike_format_number($data["get_like"]);
				}
			}//end post process
			return $counter;				
		}
		
		/**
		 * Logged by IP method
		 *
		 * @author       	Alimir
		 * @param           Array $data
		 * @since           2.0
		 * @updated         2.3		 
		 * @return			String
		 */		
		public function loggedby_ip_method(array $data){
			$liked 			= wp_ulike_format_number($data["get_like"]);
			$tmp1			= $this->get_template($data["id"],$data["method"],$liked,1,0);
			$tmp2 			= $this->get_template($data["id"],$data["method"],$liked,1,1);
			$button_type 	= wp_ulike_get_setting( 'wp_ulike_general', 'button_type');
			$condition 		= $this->wpdb->get_var("SELECT COUNT(*) FROM ".$this->wpdb->prefix.$data['table']." WHERE ".$data['column']." = '".$data['id']."' AND ip = '".$data['user_ip']."'");
			
			if($data["type"] == 'post'){
				if($condition == 0){
					if ($button_type == 'image') {
						$counter = $tmp1['like_img'];
					}
					else {
						$counter = $tmp1['like_text'];
					}
				}
				else{
					if($this->get_user_status($data['table'],$data['column'],'ip',$data['id'],$data['user_ip']) == "like"){
						if ($button_type == 'image') {
							$counter = $tmp2['unlike_img'];	
						}
						else {
							$counter = $tmp2['unlike_text'];
						}						
					}
					else{
						if ($button_type == 'image') {
							$counter = $tmp1['like_img'];		
						}
						else {
							$counter = $tmp1['like_text'];
						}
					}			
				}
			}//end post button
			else if($data["type"] == 'process'){
				if($condition == 0){
					$newLike = $data["get_like"] + 1;
					$this->update_meta_data($data["id"], $data["key"], $newLike);
					$this->wpdb->query("INSERT INTO ".$this->wpdb->prefix.$data['table']." VALUES ('', '".$data['id']."', NOW(), '".$data['user_ip']."', '".$data['user_id']."', 'like')");
					if(is_user_logged_in()){
						wp_ulike_bp_activity_add($data['user_id'],$data['id'],$data['key']);
					}
					do_action('wp_ulike_mycred_like', $data['id'], $data['key']);
					$counter = wp_ulike_format_number($newLike);
				}
				else{
					if($this->get_user_status($data['table'],$data['column'],'ip',$data['id'],$data['user_ip']) == "like"){
						$newLike = $data["get_like"] - 1;
						$this->update_meta_data($data["id"], $data["key"], $newLike);
						
						$this->wpdb->query("
							UPDATE ".$this->wpdb->prefix.$data['table']."
							SET status = 'unlike'
							WHERE ".$data['column']." = '".$data['id']."' AND ip = '".$data['user_ip']."'
						");
						do_action('wp_ulike_mycred_unlike', $data['id'], $data['key']);
						$counter = wp_ulike_format_number($newLike);				
					}
					else{
						$newLike = $data["get_like"] + 1;
						$this->update_meta_data($data["id"], $data["key"], $newLike);
						
						$this->wpdb->query("
							UPDATE ".$this->wpdb->prefix.$data['table']."
							SET status = 'like'
							WHERE ".$data['column']." = '".$data['id']."' AND ip = '".$data['user_ip']."'
						");
						do_action('wp_ulike_mycred_like', $data['id'], $data['key']);
						$counter = wp_ulike_format_number($newLike);
					}
				}
			}//end post process
			return $counter;			
		}
		
		/**
		 * Logged by IP/UserName method
		 *
		 * @author       	Alimir
		 * @param           Array $data
		 * @since           2.0
		 * @updated         2.3		 
		 * @updated         2.4.2		 
		 * @return			String
		 */	
		public function loggedby_other_ways(array $data){
			$liked 				= wp_ulike_format_number($data["get_like"]);
			$tmp1 				= $this->get_template($data["id"],$data["method"],$liked,1,0);
			$tmp2 				= $this->get_template($data["id"],$data["method"],$liked,1,1);
			$loggin_method 		= wp_ulike_get_setting( $data['setting'], 'logging_method');
			$button_type 		= wp_ulike_get_setting( 'wp_ulike_general', 'button_type');
			$second_condition 	= true; //check for by_username login method
			
			/* I removed this section (by_cookie_ip method) for some tests on v2.4.2
			if($loggin_method		== 'by_cookie_ip'){
				$condition 		= $this->wpdb->get_var("SELECT COUNT(*) FROM ".$this->wpdb->prefix.$data['table']." WHERE ".$data['column']." = '".$data['id']."' AND ip = '".$data['user_ip']."'");
				$second_column 	= 'ip';
				$second_val 	= $data['user_ip'];
			}*/
			//else if($loggin_method 	== 'by_username'){
				$condition 		= $this->wpdb->get_var("SELECT COUNT(*) FROM ".$this->wpdb->prefix.$data['table']." WHERE ".$data['column']." = '".$data['id']."' AND user_id = '".$data['user_id']."'");
				$user_info 		= get_userdata($data['user_id']);// check for user data
				if(!$user_info) $second_condition = false;// if user not exist, condition will be false
				$second_column 	= 'user_id';
				$second_val 	= $data['user_id'];
				if(!is_user_logged_in())
				$tmp1 			= $this->get_template($data["id"],$data["method"],$liked,1,2);
			//}
			
			
			if($data["type"] == 'post'){
				if($condition == 0 /*&& !isset($_COOKIE[$data["cookie"].$data["id"]])*/){
					if ($button_type == 'image') {
						$counter = $tmp1['like_img'];
					}
					else {
						$counter = $tmp1['like_text'];
					}
				}
				else if($condition != 0 /*&& isset($_COOKIE[$data["cookie"].$data["id"]])*/ && $second_condition){
					if($this->get_user_status($data['table'],$data['column'],$second_column,$data['id'],$second_val) == "like"){
						if ($button_type == 'image') {
							$counter = $tmp2['unlike_img'];	
						}
						else {
							$counter = $tmp2['unlike_text'];
						}
					}
					else{
						if ($button_type == 'image') {
							$counter = $tmp1['like_img'];		
						}
						else {
							$counter = $tmp1['like_text'];
						}
					}
				}
				else{
					if ($button_type == 'image') {
						$counter = $tmp1['permission_img'];
					}
					else {
						$counter = $tmp1['permission_text'];
					}						}
			}//end post button
			else if($data["type"] == 'process'){
				if($condition == 0 /*&& !isset($_COOKIE[$data["cookie"].$data["id"]])*/){
					$newLike = $data["get_like"] + 1;
					$this->update_meta_data($data["id"], $data["key"], $newLike);
					$this->wpdb->query("INSERT INTO ".$this->wpdb->prefix.$data['table']." VALUES ('', '".$data['id']."', NOW(), '".$data['user_ip']."', '".$data['user_id']."', 'like')");
					if(is_user_logged_in()){
						wp_ulike_bp_activity_add($data['user_id'],$data['id'],$data['key']);
					}	
					//setcookie($data["cookie"].$data["id"], time(), time()+3600*24*365, '/');
					do_action('wp_ulike_mycred_like', $data['id'], $data['key']);	
					$counter = wp_ulike_format_number($newLike);
				}
				else if($condition != 0  /*&&isset($_COOKIE[$data["cookie"].$data["id"]])*/ && $second_condition){
					if($this->get_user_status($data['table'],$data['column'],$second_column,$data['id'],$second_val) == "like"){
						$newLike = $data["get_like"] - 1;
						$this->update_meta_data($data["id"], $data["key"], $newLike);
						
						$this->wpdb->query("
							UPDATE ".$this->wpdb->prefix.$data['table']."
							SET status = 'unlike'
							WHERE ".$data['column']." = '".$data['id']."' AND $second_column = '$second_val'
						");
						do_action('wp_ulike_mycred_unlike', $data['id'], $data['key']);
						$counter = wp_ulike_format_number($newLike);				
					}
					else{
						$newLike = $data["get_like"] + 1;
						$this->update_meta_data($data["id"], $data["key"], $newLike);
						
						$this->wpdb->query("
							UPDATE ".$this->wpdb->prefix.$data['table']."
							SET status = 'like'
							WHERE ".$data['column']." = '".$data['id']."' AND $second_column = '$second_val'
						");
						do_action('wp_ulike_mycred_like', $data['id'], $data['key']);
						$counter = wp_ulike_format_number($newLike);
					}
				}
				else{
					$counter = wp_ulike_format_number($data["get_like"]);
				}
			}//end post process
			return $counter;				
		}

		/**
		 * Update meta data
		 *
		 * @author       	Alimir
		 * @param           Integer $id
		 * @param           String $key
		 * @param           Integer $data
		 * @since           2.0
		 * @updated         2.2
		 * @return			Void
		 */			
		public function update_meta_data($id, $key, $data){
			if($key == "_liked" || $key == "_topicliked")
				update_post_meta($id, $key, $data);
			else if($key == "_commentliked")
				update_comment_meta($id, $key, $data);
			else if($key == "_activityliked")
				bp_activity_update_meta($id, $key, $data);
			else
				return 0;
		}

		/**
		 * Get template
		 *
		 * @author       	Alimir
		 * @param           Integer $id
		 * @param           String $method
		 * @param           Integer $liked
		 * @param           Integer $num1
		 * @param           Integer $num2
		 * @since           2.0
		 * @updated         2.3
		 * @return			String
		 */		
		public function get_template($id,$method,$liked,$num1,$num2){
		
			$counter 			= '<span class="count-box">'.$liked.'</span>';
			$button_text 		= html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'button_text'));
			$button_text_u	 	= html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'button_text_u'));			
			$permission_text 	= html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'permission_text'));
			$login_text 		= html_entity_decode(wp_ulike_get_setting( 'wp_ulike_general', 'login_text'));
			$status 			= $num1 + $num2;
			
			return array(
			"like_img"			=> '<a data-ulike-id="'.$id.'" data-ulike-type="'.$method.'" data-ulike-status="'.$status.'" class="wp_ulike_btn image"></a>'.$counter.'',
			"like_text" 		=> '<a data-ulike-id="'.$id.'" data-ulike-type="'.$method.'" data-ulike-status="'.$status.'" class="wp_ulike_btn text">'.$button_text.'</a>'.$counter.'',
			"unlike_img" 		=> '<a data-ulike-id="'.$id.'" data-ulike-type="'.$method.'" data-ulike-status="'.$status.'" class="wp_ulike_btn image-unlike"></a>'.$counter.'',
			"unlike_text" 		=> '<a data-ulike-id="'.$id.'" data-ulike-type="'.$method.'" data-ulike-status="'.$status.'" class="wp_ulike_btn text">'.$button_text_u.'</a>'.$counter.'',
			"permission_text"	=> '<a title="'.$permission_text.'" class="text user-tooltip">'.$button_text_u.'</a>'.$counter.'',
			"permission_img" 	=> '<a title="'.$permission_text.'" class="image-unlike user-tooltip"></a>'.$counter.'',
			"login_img" 		=> '<a title="'.$login_text.'" class="image user-tooltip"></a>'.$counter.'',				
			"login_text" 		=> '<a title="'.$login_text.'" class="text user-tooltip">'.$button_text.'</a>'.$counter.''				
			);
		}		
		
		/**
		 * Get User Status (like/dislike)
		 *
		 * @author       	Alimir
		 * @param           String $table
		 * @param           String $first_column
		 * @param           String $second_column
		 * @param           String $first_val
		 * @param           String $second_val
		 * @since           2.0
		 * @return			String
		 */
		public function get_user_status($table,$first_column,$second_column,$first_val,$second_val){
			$like_status = $this->wpdb->get_var("SELECT status FROM ".$this->wpdb->prefix."$table WHERE $first_column = '$first_val' AND $second_column = '$second_val'");
			if ($like_status == "like")
			return "like";
			else
			return "unlike";
		}
		
		/**
		 * Get Liked User
		 *
		 * @author       	Alimir
		 * @param           Integer $id
		 * @param           String $table
		 * @param           String $column_id
		 * @param           String $setting_key
		 * @since           2.0
		 * @updated         2.3
		 * @return			String
		 */
		public function get_liked_users($id,$table,$column_id,$setting_key){
			$users_list = '';
			$limit_num 	= wp_ulike_get_setting( $setting_key, 'number_of_users');
			if($limit_num == 0) $limit_num = 10;
			$get_users 	= $this->wpdb->get_results("SELECT user_id FROM ".$this->wpdb->prefix."$table WHERE $column_id = '$id' AND status = 'like' AND user_id BETWEEN 1 AND 999999 GROUP BY user_id LIMIT $limit_num");
			if(wp_ulike_get_setting( $setting_key, 'users_liked_box') == '1' && !$get_users == ''){
				$get_template = wp_ulike_get_setting( $setting_key, 'users_liked_box_template' );
				if($get_template == '')
				$get_template = '<br /><p style="margin-top:5px"> '.__('Users who have LIKED this post:',WP_ULIKE_SLUG).'</p> <ul class="tiles"> %START_WHILE% <li><a class="user-tooltip" title="%USER_NAME%">%USER_AVATAR%</a></li> %END_WHILE%</ul>';
				$inner_template = $this->get_template_between($get_template,"%START_WHILE%","%END_WHILE%");
				foreach ( $get_users as $get_user ) 
				{
					$user_info = get_userdata($get_user->user_id);
					$out_template = $inner_template;
					if ($user_info):
						if (strpos($out_template, '%USER_AVATAR%') !== false) {
							$avatar_size = wp_ulike_get_setting( $setting_key, 'users_liked_box_avatar_size');
							$USER_AVATAR = get_avatar( $user_info->user_email, $avatar_size, '' , 'avatar');
							$out_template = str_replace("%USER_AVATAR%", $USER_AVATAR, $out_template);
						}
						if (strpos($out_template, '%USER_NAME%') !== false) {
							$USER_NAME = $user_info->display_name;
							$out_template = str_replace("%USER_NAME%", $USER_NAME, $out_template);
						}
						if (strpos($out_template, '%UM_PROFILE_URL%') !== false && function_exists('um_fetch_user')) {
							global $ultimatemember;
							um_fetch_user($user_info->ID);
							$UM_PROFILE_URL = um_user_profile_url();
							$out_template = str_replace("%UM_PROFILE_URL%", $UM_PROFILE_URL, $out_template);
						}
						if (strpos($out_template, '%BP_PROFILE_URL%') !== false && function_exists('bp_core_get_user_domain')) {
							$BP_PROFILE_URL = bp_core_get_user_domain( $user_info->ID );
							$out_template = str_replace("%BP_PROFILE_URL%", $BP_PROFILE_URL, $out_template);
						}
						$users_list .= $out_template;
					endif;
				}
				if($users_list!='')
				$users_list = $this->put_template_between($get_template,$users_list,"%START_WHILE%","%END_WHILE%");
			}
			return $users_list;
		}
		
		/**
		 * Get Current User Likes List
		 *
		 * @author       	Alimir
		 * @param           Array $args
		 * @since           2.3
		 * @return			Array
		 */		
		public function get_current_user_likes(array $args){
			return $this->wpdb->get_results("SELECT ".$args['col'].", date_time FROM ".$this->wpdb->prefix.$args['table']." WHERE user_id = ".$args['user_id']." AND status = 'like' ORDER BY date_time DESC LIMIT ".$args['limit']."");
		}

		
		/**
		 * Get template between
		 *
		 * @author       	Alimir
		 * @param           String $string
		 * @param           String $start
		 * @param           String $end
		 * @since           2.0
		 * @return			String
		 */
		public function get_template_between($string, $start, $end){
			$string = " ".$string;
			$ini = strpos($string,$start);
			if ($ini == 0) return "";
			$ini += strlen($start);
			$len = strpos($string,$end,$ini) - $ini;
			return substr($string,$ini,$len);
		}

		/**
		 * Put template between
		 *
		 * @author       	Alimir
		 * @param           String $string
		 * @param           String $inner_string
		 * @param           String $start
		 * @param           String $end
		 * @since           2.0
		 * @return			String
		 */		
		public function put_template_between($string,$inner_string, $start, $end){
			$string = " ".$string;
			$ini = strpos($string,$start);
			if ($ini == 0) return "";
			$ini += strlen($start);
			$len = strpos($string,$end,$ini) - $ini;
			$newstr = substr_replace($string,$inner_string,$ini,$len);
			return str_replace(array("%START_WHILE%", "%END_WHILE%"),array("", ""), $newstr);
		}

		/**
		 * Return user ID
		 *
		 * @author       	Alimir
		 * @since           2.0
		 * @return			String
		 */			
		function get_reutrn_id(){
			global $user_ID,$wp_user_IP;
			if(!is_user_logged_in()){
				return ip2long($wp_user_IP);
			}
			else
				return $user_ID;
		}
		
		
	}
	
	//global variables
	global $wp_ulike_class;
	$wp_ulike_class = new wp_ulike();
	
}