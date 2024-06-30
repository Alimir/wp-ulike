<?php
/**
 * WP ULike myCred support Class
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'myCRED_Hook' ) ) :

	class wp_ulike_myCRED extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = 'mycred_default' ) {
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
						'log'    => '%plural% deduction for unliking a content',
						'limit'  => '0/x'
					),
					'get_unlike'  => array(
						'creds'  => -1,
						'log'    => '%plural% for getting Unliked from a content',
						'limit'  => '0/x'
					),
					'limits'   => array(
						'self_reply' => 0
					),
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run Actions
		 *
		 * @since		2.3
		 */
		public function run() {
			// Goto status function
			add_action( 'wp_ulike_after_process', array( $this, 'status' )	, 10, 4 );
		}

		/**
		 * Start functions by status
		 *
		 * @since		2.3
		 */
		public function status( $id , $key, $user_id, $status ) {
			$author_id = $this->get_author_ID( $id, $key );
			// Check status
			if( ! in_array( $status, array( 'like', 'unlike' ) ) ){
				return;
			}
			// Call function by user status
			call_user_func( array( $this, $status ), $id , $key, $user_id, $author_id );
		}

		/**
		 * Add Like
		 *
		 * @since		2.3
		 */
		public function like( $id , $key, $user_id, $author_id = 0 ) {
			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) || ! is_user_logged_in() ) return;

			if ( $user_id != $author_id || $this->prefs['limits']['self_reply'] ){

				// Award the user liking
				if ( $this->prefs['add_like']['creds'] ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'add_like', 'wp_add_like', $user_id ) ) {
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
				}

				// Award post author for being liked
				if ( $this->prefs['get_like']['creds'] && $author_id ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'get_like', 'wp_get_like', $author_id ) ) {
						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'wp_get_like', $id, $author_id, array( 'ref_type' => $key, 'by' => $user_id ) ) ) {
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

		}

		/**
		 * Remove Like
		 *
		 * @since		2.3
		 */
		public function unlike( $id , $key, $user_id, $author_id = 0 ) {

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) || ! is_user_logged_in() ) return;

			if ( $user_id != $author_id || $this->prefs['limits']['self_reply'] ){

				// Award the user liking
				if ( $this->prefs['add_unlike']['creds'] ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'add_unlike', 'wp_add_unlike', $user_id ) ) {
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
				}

				// Award post author for being liked
				if ( $this->prefs['get_unlike']['creds'] && $author_id ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'get_unlike', 'wp_get_unlike', $author_id ) ) {
						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'wp_get_unlike', $id, $author_id, array( 'ref_type' => $key, 'by' => $user_id ) ) ) {
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

		}

		/**
		 * Get buddpress user ID
		 *
		 * @param integer $activity_id
		 * @return integer
		 */
		public function bp_get_auhtor_id($activity_id) {
			$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id, 'display_comments'  => true ) );
			return $activity['activities'][0]->user_id;
		}

		/**
		 * Get author ID by it's type
		 *
		 * @param string $key
		 * @return integer
		 */
		protected function get_author_ID( $id, $key ){
			// Default value
			$author_id 	= 0;
			// Get author ID by it's type
			switch ( $key ) {
				case '_liked':
					$author_id 	= get_post_field( 'post_author', $id );
					break;
				case '_topicliked':
					$author_id 	= bbp_get_reply_author_id( $id );
					break;
				case '_commentliked':
					$comment_id = get_comment( $id );
					$author_id 	= $comment_id->user_id;
					break;
				case '_activityliked':
					$author_id 	= $this->bp_get_auhtor_id( $id );
					break;
			}
			return $author_id;
		}


		/**
		 * Preference for wp_ulike Hook
		 *
		 * @since		2.3
		 */
		public function preferences() {

			$prefs = $this->prefs;

		?>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Liking content', 'wp-ulike' ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_like' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_like' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_like' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['add_like']['creds'] ); ?>" class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', 'wp-ulike' ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_like', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'wp-ulike' ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'add_like', 'limit' ) ), $this->field_id( array( 'add_like', 'limit' ) ), $prefs['add_like']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_like' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_like' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_like' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', 'wp-ulike' ); ?>"
                    value="<?php echo esc_attr( $prefs['add_like']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Author Who Get Liked', 'wp-ulike' ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_like' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_like' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_like' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['get_like']['creds'] ); ?>" class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', 'wp-ulike' ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_like', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'wp-ulike' ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'get_like', 'limit' ) ), $this->field_id( array( 'get_like', 'limit' ) ), $prefs['get_like']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_like' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_like' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_like' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', 'wp-ulike' ); ?>"
                    value="<?php echo esc_attr( $prefs['get_like']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for unliking content', 'wp-ulike' ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_unlike' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_unlike' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_unlike' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['add_unlike']['creds'] ); ?>" class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', 'wp-ulike' ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_unlike', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'wp-ulike' ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'add_unlike', 'limit' ) ), $this->field_id( array( 'add_unlike', 'limit' ) ), $prefs['add_unlike']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_unlike' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_unlike' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_unlike' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', 'wp-ulike' ); ?>"
                    value="<?php echo esc_attr( $prefs['add_unlike']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Author Who Get Unliked', 'wp-ulike' ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_unlike' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_unlike' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_unlike' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['get_unlike']['creds'] ); ?>" class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', 'wp-ulike' ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_unlike', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'wp-ulike' ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'get_unlike', 'limit' ) ), $this->field_id( array( 'get_unlike', 'limit' ) ), $prefs['get_unlike']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_unlike' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', 'wp-ulike' ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_unlike' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_unlike' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', 'wp-ulike' ); ?>"
                    value="<?php echo esc_attr( $prefs['get_unlike']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Limits', 'wp-ulike' ); ?></h3>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="form-group">
                <div class="checkbox">
                    <label for="<?php echo $this->field_id( array( 'limits' => 'self_reply' ) ); ?>"><input
                            type="checkbox" name="<?php echo $this->field_name( array( 'limits' => 'self_reply' ) ); ?>"
                            id="<?php echo $this->field_id( array( 'limits' => 'self_reply' ) ); ?>"
                            <?php checked( $prefs['limits']['self_reply'], 1 ); ?> value="1" />
                        <?php echo $this->core->template_tags_general( esc_html__( '%plural% is to be awarded even when item authors Like/Unlike their own item.', 'wp-ulike' ) ); ?></label>
                </div>
            </div>
        </div>
    </div>
</div>
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

			if ( isset( $data['add_unlike']['limit'] ) && isset( $data['add_unlike']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['add_unlike']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['add_unlike']['limit'] = $limit . '/' . $data['add_unlike']['limit_by'];
				unset( $data['add_unlike']['limit_by'] );
			}

			if ( isset( $data['get_unlike']['limit'] ) && isset( $data['get_unlike']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['get_unlike']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['get_unlike']['limit'] = $limit . '/' . $data['get_unlike']['limit_by'];
				unset( $data['get_unlike']['limit_by'] );
			}

			return $data;

		}
	}

endif;