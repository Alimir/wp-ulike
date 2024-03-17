<?php
/**
 * Wp ULike Admin Notices
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_notices' ) ) {
    class wp_ulike_notices{

        protected $args        = array();
        protected $buttons     = '';

        function __construct( $args = array() ){
            $defaults   = array(
                'id'             => NULL,
                'title'          => '',
                'skin'           => 'default',
                'image'          => '',
                'screen_filter'  => array(),
                'description'    => '',
                'initial_snooze' => '',          // snooze time in milliseconds
                'has_close'      => true,        // Whether it has close button or not
                'buttons'        => array(),
                'dismissible'    => array(
                    'url_key'    => 'wp-ulike-hide-core-plugin-notice',
                    'action'     => 'wp_ulike_hide_notices_nonce',
                    'expiration' => YEAR_IN_SECONDS
                )
            );
            $this->args = wp_parse_args( $args, $defaults );

            if( empty( $this->args['id'] ) ){
                return new WP_Error( 'missing_id', esc_html__( "You need to enter a unique id for notice.", 'wp-ulike' ) );
            }

            if( is_array( $this->args['dismissible'] ) ){
                $this->flush_dismissible();
            }
        }

        /**
         * get image
         *
         * @param boolean $echo
         * @param string $before
         * @param string $after
         * @return void | string
         */
        private function get_image( $before = '<div class="wp-ulike-notice-image">', $after = '</div>' ){

            if ( empty( $this->args['image'] ) ) {
                return;
            }

            if( ! is_array( $this->args['image'] ) ){
                return $before . $this->args['image'] . $after;
            }

            $attrs = '';
            foreach ( $this->args['image'] as $attr_name => $attr_value ) {
                $attrs .= sprintf( ' %s="%s"', $attr_name, $attr_value );
            }

            return $before . '<img '. $attrs .' />' . $after;
        }

        /**
         * get title
         *
         * @param boolean $echo
         * @param string $before
         * @param string $after
         * @return void | string
         */
        private function get_title( $before = '<h3 class="wp-ulike-notice-title">', $after = '</h3>' ){

            if ( empty( $this->args['title'] ) ) {
                return;
            }

            return $before . $this->args['title'] . $after;
        }

        /**
         * get class skin
         *
         * @param boolean $echo
         * @param string $prefix
         * @return void | string
         */
        private function get_skin( $prefix = 'wp-ulike-notice-skin-' ){
            return $prefix . $this->args['skin'];
        }

        /**
         * get description
         *
         * @param boolean $echo
         * @param string $before
         * @param string $after
         * @return void | string
         */
        private function get_description( $before = '<p class="wp-ulike-notice-description">', $after = '</p>' ){

            if ( strlen( $this->args['description'] ) == 0 ) {
                return;
            }

            return $before . $this->args['description'] . $after;
        }

        /**
         * get buttons
         *
         * @param boolean $echo
         * @return void
         */
        private function get_buttons(){
            if( ! is_array( $this->args['buttons'] ) || empty( $this->args['buttons'] ) ) {
                return;
            }

            $default_args = [
                'target'        => '_blank',
                'type'          => 'link',
                'color_name'    => 'default',
                'link'          => '#',
                'expiration'    => '',
                'ajax_request'  => array(
                    'action'  => ''
                ),
                'extra_classes' => 'wp-ulike-notice-btn'
            ];

            foreach ( $this->args['buttons'] as $btn_args ) {

                $current_default_args = $default_args;

                if( !empty( $btn_args['type']  ) && 'skip' === $btn_args['type'] ){
                    $current_default_args['extra_classes'] .= ' wp-ulike-skip-notice';
                } else {
                    $current_default_args['extra_classes'] = 'wp-ulike-notice-cta-btn';
                }

                $btn_args = wp_parse_args( $btn_args, $current_default_args );

                // Maye add custom expiration to the btn
                if( $btn_args['expiration'] ){
                    $btn_args['btn_attrs'] = 'data-expiration{'. $btn_args['expiration'] .'}';
                }
                unset( $btn_args['expiration'] );

                $this->buttons .= wp_ulike_widget_button_callback( $btn_args );

                if( !empty( $btn_args['ajax_request']['action'] ) ){
                ob_start();
?>
                <script>
                    jQuery('.<?php echo esc_js( $this->get_unique_class() ); ?> .wp-ulike-notice-cta-btn').on( 'click' , function(e) {
                        e.preventDefault();
                        var $currentTargetElement = jQuery(event.currentTarget).addClass('wp-ulike-btn-is-loading');
                        jQuery.ajax({
                            url : ajaxurl,
                            type: 'post',
                            data: {
                                action : '<?php echo esc_js( $btn_args['ajax_request']['action'] ); ?>',
                                nonce  : '<?php echo esc_js( wp_create_nonce( '_notice_nonce' ) ); ?>',
                                id     : '<?php echo esc_js( $this->args['id'] ); ?>'
                            }
                        }).done(function( response ) {
                            $currentTargetElement.removeClass('wp-ulike-btn-is-loading');
                            jQuery(this).closest('.wp-ulike-notice-wrapper').fadeOut();
                            if(response.success) {
                                location.reload();
                            }
                        }.bind(this));
                    });
                </script>
<?php
                $this->buttons .= ob_get_clean();
                }
            }

            return $this->buttons;
        }

        /**
         * get dismissible button
         *
         * @param boolean $echo
         * @return void
         */
        private function get_dismissible(){

            if( $this->args['dismissible'] === false ){
                return;
            }

            ob_start();

            if( $this->args['has_close'] ){
    ?>
            <a href="<?php echo esc_url( $this->get_nonce_url() ); ?>" class="notice-dismiss wp-ulike-skip-notice wp-ulike-close-notice">
                <span class="screen-reader-text"><?php esc_html_e( 'Skip', 'wp-ulike' ); ?></span>
            </a>
    <?php } ?>
            <script>
                jQuery('.<?php echo esc_js( $this->get_unique_class() ); ?> .wp-ulike-skip-notice').on( 'click' , function(e) {
                    e.preventDefault();
                    var expiration = this.getAttribute('data-expiration') || '<?php echo esc_js( $this->args['dismissible']['expiration'] ); ?>'

                    jQuery.ajax({
                        url : ajaxurl,
                        type: 'post',
                        data: {
                            action    : 'wp_ulike_dismissed_notice',
                            id        : '<?php echo esc_js( $this->args['id'] ); ?>',
                            nonce     : '<?php echo esc_js( wp_create_nonce( '_notice_nonce' ) ); ?>',
                            expiration: expiration
                        }
                    }).done(function( response ) {
                        if(response.success) {
                            jQuery(this).closest('.wp-ulike-notice-wrapper').fadeOut();
                        }
                    }.bind(this));
                });
            </script>
    <?php
            return ob_get_clean();
        }

        /**
         * Update dismissible transient
         *
         * @return void
         */
        private function flush_dismissible(){
            if ( isset( $_GET[ $this->args['dismissible']['url_key'] ] ) && isset( $_GET[ '_notice_nonce' ] ) && $_GET[ $this->args['dismissible']['url_key'] ] === $this->args['id'] ) {
                if ( ! wp_verify_nonce( $_GET[ '_notice_nonce' ],  $this->args['dismissible']['action'] ) ) {
                    wp_die( esc_html__( 'Authorization failed. Please refresh the page and try again.', 'wp-ulike' ) );
                }
                wp_ulike_set_transient( $this->get_transient_key(), 1, $this->args['dismissible']['expiration'] );
                $this->args['dismissible'] = false;
            }
        }

        /**
         * Undocumented function
         *
         * @return void
         */
        private function get_nonce_url(){
            $actionurl = add_query_arg( $this->args['dismissible']['url_key'], $this->args['id'] );
            return wp_nonce_url( $actionurl, $this->args['dismissible']['action'], '_notice_nonce' );
        }

        /**
         * check dismissible
         *
         * @return boolean
         */
        private function is_dismissible(){
            if( ! is_array( $this->args['dismissible'] ) || wp_ulike_get_transient( $this->get_transient_key() ) ){
                return true;
            }
            return false;
        }

        /**
         * Check snooze time
         *
         * @return boolean
         */
        private function is_snoozed(){
            if( ! empty( $this->args['initial_snooze'] ) ){
                $transient_key = $this->get_transient_key() . '-snooze';
                $snooze_time   = wp_ulike_get_transient( $transient_key );
                if( $snooze_time && $snooze_time > strtotime( "now" ) ){
                    return true;
                } elseif( $snooze_time === false ) {
                    wp_ulike_set_transient( $transient_key, strtotime( $this->args['initial_snooze'] . " seconds" ) );
                    return true;
                }
            }
            return false;
        }

        /**
         * Check screen filter
         *
         * @return boolean
         */
        private function is_visible_screen(){
            $current_screen = get_current_screen();
            if( ! empty( $this->args['screen_filter'] ) && ! in_array(  $current_screen->id, $this->args['screen_filter'] ) ) {
                return true;
            }
            return false;
        }

        /**
         * Retrieves a transient key.
         */
        private function get_transient_key(){
            return 'wp-ulike-notice-' . $this->args['id'];
        }

        /**
         * Retrieves a unique id for main wrapper.
         */
        private function get_unique_class(){
            return 'wp-ulike-notice-id-' . $this->args['id'];
        }

        /**
         * Retrieves custom styles for main wrapper
         */
        private function get_custom_styles(){

            if ( ! isset( $this->args['wrapper_extra_styles'] ) || empty( $this->args['wrapper_extra_styles'] ) ) {
                return false;
            } else {
                $styles  = '';

                foreach( $this->args['wrapper_extra_styles'] as $property => $value ) {
                    if ( 'custom' === $property ) {
                        $styles .= $value;
                    } else {
                        $styles  .=  $property . ':' . $value . ';';
                    }
                }

                return 'style="'. $styles . '"';

            }

        }


        /**
         * render output
         *
         * @param boolean $echo
         * @return void
         */
        public function render(){

            if( $this->is_dismissible() || $this->is_visible_screen() || $this->is_snoozed() ) {
                return;
            }

            echo sprintf(
                '<div class="updated wp-ulike-message wp-ulike-notice-control wp-ulike-notice-wrapper %s %s" %s>%s <div class="wp-ulike-notice-info">%s %s <div class="wp-ulike-notice-submit submit">%s %s</div></div></div>',
                $this->get_unique_class(),
                $this->get_skin(),
                $this->get_custom_styles(),
                $this->get_image(),
                $this->get_title(),
                $this->get_description(),
                $this->get_buttons(),
                $this->get_dismissible()
            );

        }

    }
}