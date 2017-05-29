<?php
if ( class_exists( 'myCRED_Hook' ) ) :
	class wp_ulike_myCRED extends myCRED_Hook {
 
		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = 'mycred_default' ) {
 			global $wpdb;
			$this->wpdb = $wpdb;
			parent::__construct( array(
				'id'       => 'wp_ulike',
				'defaults' => array(
					'add_like'    => array(
						'creds'  => 1,
						'log'    => '%plural% for liking a content',
						'limit'  => '0/x'
					),
					'get_like'  => array(
						'creds'  => 1,
						'log'    => '%plural% for getting liked from a content',
						'limit'  => '0/x'
					),
					'add_unlike'  => array(
						'creds'  => -1,
						'log'    => '%plural% deduction for unliking a content'
					),
					'get_unlike'  => array(
						'creds'  => -1,
						'log'    => '%plural% for getting Unliked from a content'
					)
				)
			), $hook_prefs, $type );
 
		}
 
		/**
		 * Run Actions
		 *
		 * @since		2.3
		 */
		public function run() {
 
			if ( $this->prefs['add_like']['creds'] != 0 || $this->prefs['get_like']['creds'] != 0)
				add_action( 'wp_ulike_mycred_like', array( $this, 'like' ), 10, 2 );

			if ( $this->prefs['add_unlike']['creds'] != 0 || $this->prefs['get_unlike']['creds'] != 0 )
				add_action( 'wp_ulike_mycred_unlike', array( $this, 'unlike' ), 10, 2 );
 
		}
		
		
		public function bp_get_auhtor_id($activity_id) {
			$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
			return $activity['activities'][0]->user_id;
		}
		
		/**
		 * Add Like
		 *
		 * @since		2.3
		 */
		public function like( $id , $key ) {
			
			$author_id 	= 0;
			$user_id 	= get_current_user_id();
			
			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) return;			
			
			if($key == '_liked' || $key == '_topicliked' ){
				$author_id 	= get_post_field( 'post_author', $id );
			}
			else if($key == '_commentliked'){
				$comment_id = get_comment( $id ); 
				$author_id 	= $comment_id->user_id;						
			}
			else if($key == '_activityliked'){
				$author_id 	= $this->bp_get_auhtor_id($id);
			}
						
			
			if ( $user_id != $author_id ){

				// Award the user liking
				if ( $this->prefs['add_like']['creds'] != 0) {
					// Limit
					if ( $this->over_hook_limit( 'add_like', 'wp_add_like', $user_id ) ) return;
					// Make sure this is unique event
					if ( ! $this->core->has_entry( 'wp_add_like', $id, $user_id ) ) {
						// Execute
						$this->core->add_creds(
							'wp_add_like',
							$user_id,
							$this->prefs['add_like']['creds'],
							$this->prefs['add_like']['log'],
							$id,
							array( 'ref_type' => $key ),
							$this->mycred_type
						);
					}
				}

				// Award post author for being liked
				if ( $this->prefs['get_like']['creds'] != 0 && $author_id != 0 ) {
					// Make sure this is unique event
					if ( $this->over_hook_limit( 'get_like', 'wp_get_like', $author_id ) ) return;
					// Make sure this is unique event
					if ( ! $this->core->has_entry( 'wp_get_like', $id, $author_id ) ) {
						// Execute
						$this->core->add_creds(
							'wp_get_like',
							$author_id,
							$this->prefs['get_like']['creds'],
							$this->prefs['get_like']['log'],
							$id,
							array( 'ref_type' => $key, 'by' => $user_id ),
							$this->mycred_type
						);
					}
				}
				
			}

		}

		/**
		 * Remove Like
		 *
		 * @since		2.3
		 */
		public function unlike( $id , $key  ) {
		
			$author_id 	= 0;
			$user_id 	= get_current_user_id();		
		
			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) return;
			
			if($key == '_liked' || $key == '_topicliked' ){
				$author_id 	= get_post_field( 'post_author', $id );
			}
			else if($key == '_commentliked'){
				$comment_id = get_comment( $id ); 
				$author_id 	= $comment_id->user_id;						
			}
			else if($key == '_activityliked'){
				$author_id 	= $this->bp_get_auhtor_id($id);
			}
						
			
			if ( $user_id != $author_id ){

				// Award the user liking
				if ( $this->prefs['add_unlike']['creds'] != 0) {
					// Make sure this is unique event
					if ( ! $this->core->has_entry( 'wp_add_unlike', $id, $user_id ) ) {
						// Execute
						$this->core->add_creds(
							'wp_add_unlike',
							$user_id,
							$this->prefs['add_unlike']['creds'],
							$this->prefs['add_unlike']['log'],
							$id,
							array( 'ref_type' => $key ),
							$this->mycred_type
						);
					}
				}

				// Award post author for being liked
				if ( $this->prefs['get_unlike']['creds'] != 0 && $author_id != 0 ) {
					// Make sure this is unique event
					if ( ! $this->core->has_entry( 'wp_get_unlike', $id, $author_id ) ) {
						// Execute
						$this->core->add_creds(
							'wp_get_unlike',
							$author_id,
							$this->prefs['get_unlike']['creds'],
							$this->prefs['get_unlike']['log'],
							$id,
							array( 'ref_type' => $key, 'by' => $user_id ),
							$this->mycred_type
						);
					}
				}
				
			}

		}
		
 
		/**
		 * Preference for wp_ulike Hook
		 *
		 * @since		2.3
		 */
		public function preferences() {
 
			$prefs = $this->prefs;
 
?>
<label class="subheader"><?php echo _e( 'Points for Liking content', WP_ULIKE_SLUG ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'add_like' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'add_like' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['add_like']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty"></li>
	<li>
		<label for="<?php echo $this->field_id( array( 'add_like' => 'limit' ) ); ?>"><?php _e( 'Limit', WP_ULIKE_SLUG ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'add_like' => 'limit' ) ), $this->field_id( array( 'add_like' => 'limit' ) ), $prefs['add_like']['limit'] ); ?>
	</li>	
	<li class="empty"></li>
	<li>
		<label for="<?php echo $this->field_id( array( 'add_like' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'add_like' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'add_like' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['add_like']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<label class="subheader"><?php _e( 'Points for Author Who Get Liked', WP_ULIKE_SLUG ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'get_like' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'get_like' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['get_like']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty"></li>
	<li>
		<label for="<?php echo $this->field_id( array( 'get_like' => 'limit' ) ); ?>"><?php _e( 'Limit', WP_ULIKE_SLUG ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'get_like' => 'limit' ) ), $this->field_id( array( 'get_like' => 'limit' ) ), $prefs['get_like']['limit'] ); ?>
	</li>		
	<li class="empty"></li>
	<li>
		<label for="<?php echo $this->field_id( array( 'get_like' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'get_like' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'get_like' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['get_like']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<label class="subheader"><?php echo _e( 'Points for unliking content', WP_ULIKE_SLUG ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'add_unlike' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'add_unlike' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['add_unlike']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty"></li>
	<li>
		<label for="<?php echo $this->field_id( array( 'add_unlike' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'add_unlike' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'add_unlike' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['add_unlike']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<label class="subheader"><?php _e( 'Points for Author Who Get Unliked', WP_ULIKE_SLUG ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'get_unlike' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'get_unlike' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['get_unlike']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty"></li>
	<li>
		<label for="<?php echo $this->field_id( array( 'get_unlike' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'get_unlike' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'get_unlike' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['get_unlike']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<?php
 
		}
		
		/**
		 * Sanitise Preferences
		 *
		 * @since 		2.3
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['add_like']['limit'] ) && isset( $data['add_like']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['add_like']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['add_like']['limit'] = $limit . '/' . $data['add_like']['limit_by'];
				unset( $data['add_like']['limit_by'] );
			}

			if ( isset( $data['get_like']['limit'] ) && isset( $data['get_like']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['get_like']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['get_like']['limit'] = $limit . '/' . $data['get_like']['limit_by'];
				unset( $data['get_like']['limit_by'] );
			}

			return $data;

		}
	}
endif;