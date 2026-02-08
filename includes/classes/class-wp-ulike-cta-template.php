<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'wp_ulike_cta_template' ) ) {

	class wp_ulike_cta_template extends wp_ulike_entities_process{

		protected $settings;
		protected $method_id;
		protected $args;
		protected $cached_method;
		protected $cached_counter_visible;

		/**
		 * Constructor
		 */
		function __construct( $args ){
			$this->args = $args;
			
			// Use singleton pattern instead of manual caching
			$slug = $this->args['slug'];
			$this->settings = wp_ulike_setting_type::get_instance( $slug );
			
			static $cached_methods = array();
			if ( ! isset( $cached_methods[ $slug ] ) ) {
				$cached_methods[ $slug ] = wp_ulike_setting_repo::getMethod( $slug );
			}
			$this->cached_method = $cached_methods[ $slug ];
			
			static $cached_counter_visible = array();
			if ( ! isset( $cached_counter_visible[ $slug ] ) ) {
				$cached_counter_visible[ $slug ] = wp_ulike_setting_repo::isCounterBoxVisible( $slug );
			}
			$this->cached_counter_visible = $cached_counter_visible[ $slug ];
			
			parent::__construct(array(
				'item_type'   => $this->args['slug'],
				'item_method' => $this->cached_method
			));
		}

		/**
		 * Display button template
		 *
		 * @return string
		 */
		public function display(){
			$this->setPrevStatus( $this->args['id'] );
			return $this->get_template( $this->get_method_id() );
		}

		/**
		 * Get method ID number
		 *
		 * @return integer
		 */
		public function get_method_id(){
			$method_id = 0;

			if( ! $this->hasPermission( array(
				'item_id'              => $this->args['id'],
				'type'                 => $this->args['slug'],
				'current_user'         => $this->getCurrentUser(),
				'prev_status'          => $this->getPrevStatus(),
				'current_finger_print' => '',
				'method'               => 'lookup'
			), $this->settings ) ){
				// If has prev status
				$method_id = 0;

				if( $this->getPrevStatus() ){
					$method_id = substr( $this->getPrevStatus(), 0, 2 ) !== "un" ? 4 : 5;
				}

			} else {
				switch( $this->cached_method ){
					case 'do_not_log':
						$method_id = $this->getPrevStatus() ? 4 : 1;
						break;
					case 'by_cookie':
						$method_id = 1;
						break;

					default:
						if( ! $this->getPrevStatus() ){
							$method_id = 1;
						} else {
							$method_id = substr( $this->getPrevStatus(), 0, 2 ) !== "un" ? 2 : 3;
						}
						break;
				}
			}

			return apply_filters( 'wp_ulike_method_id', $method_id, $this );
		}

		/**
		 * Get final template
		 *
		 * @param integer $method_id
		 * @return string
		 */
		public function get_template( $method_id ){

			//Primary button class name
			$button_class_name	= str_replace( ".", "", apply_filters( 'wp_ulike_button_selector', 'wp_ulike_btn' ) );
			//Button text value
			$button_text		= '';

			// Get all template callback list
			$temp_list = call_user_func( 'wp_ulike_generate_templates_list' );
			$func_name = isset( $temp_list[ $this->args['style'] ]['callback'] ) ? $temp_list[ $this->args['style'] ]['callback'] : 'wp_ulike_set_default_template';

			if( $this->args['button_type'] == 'image' || ( isset( $temp_list[$this->args['style']]['is_text_support'] ) && ! $temp_list[$this->args['style']]['is_text_support'] ) ){
				$button_class_name .= ' wp_ulike_put_image';
 				if( in_array( $method_id, array( 2, 4 ) ) ){
					$button_class_name .= ' image-unlike wp_ulike_btn_is_active';
				}
			} else {
				$button_class_name .= ' wp_ulike_put_text';
 				if( in_array( $method_id, array( 2, 4 ) ) && strpos( $this->getPrevStatus(), 'dis') !== 0){
					$button_text = wp_ulike_setting_repo::getButtonText( $this->args['type'], 'unlike' );
				} else {
					$button_text = wp_ulike_setting_repo::getButtonText( $this->args['type'], 'like' );
				}
			}

			// Add unique class name for each button
			$button_class_name .= strtolower( ' wp_' . $this->args['slug'] . '_btn_' . $this->args['id'] );

			$total_likes = wp_ulike_get_counter_value( $this->args['id'], $this->args['slug'], 'like', $this->isDistinct() );

			// Hide on zero value
			if( wp_ulike_setting_repo::isCounterZeroHidden( $this->args['slug'] ) && $total_likes == 0 ){
				$total_likes = '';
			}

			// Deprecated formatted_val, Don't use it
			$formatted_val = apply_filters( 'wp_ulike_count_box_template', '<span class="count-box">'. wp_ulike_format_number( $total_likes ) .'</span>' , $total_likes, $this->args['slug'] );
			$this->args['is_distinct'] = $this->isDistinct();

			$formatted_value = '';
			if( $this->cached_counter_visible ){
				$formatted_value = wp_ulike_format_number( $total_likes, wp_ulike_maybe_convert_status( $this->getPrevStatus(), 'up' ) );
			}

			$wp_ulike_template 	= apply_filters( 'wp_ulike_add_templates_args', array(
					"ID"                    => esc_attr( $this->args['id'] ),
					"wrapper_class"         => esc_attr( $this->args['wrapper_class'] ),
					"slug"                  => esc_attr( $this->args['slug'] ),
					"counter"               => $this->cached_counter_visible ?  wp_ulike_kses( $formatted_val ) : '',
					"total_likes"           => esc_attr( $total_likes ),
					"formatted_total_likes" => esc_attr( $formatted_value ),
					"type"                  => esc_attr( $this->args['slug'] ),
					"status"                => esc_attr( $method_id ),
					"user_status"           => esc_attr( $this->getPrevStatus() ),
					"setting"               => esc_attr( $this->args['setting'] ),
					"attributes"            => ! is_array($this->args['attributes']) ? esc_attr( $this->args['attributes'] ) : '',
					"up_vote_inner_text"    => $this->args['up_vote_inner_text'] ? wp_ulike_kses( $this->args['up_vote_inner_text'] ) : '',
					"down_vote_inner_text"  => $this->args['down_vote_inner_text'] ? wp_ulike_kses( $this->args['down_vote_inner_text'] ) : '',
					"style"                 => esc_html( $this->args['style'] ),
					"button_type"           => esc_html( $this->args['button_type'] ),
					"display_likers"        => esc_attr( $this->args['display_likers'] ),
					"display_counters"      => $this->cached_counter_visible,
					"disable_pophover"      => esc_attr( $this->args['disable_pophover'] ),
					"likers_style"          => esc_attr( $this->args['likers_style'] ),
					"button_text"           => $button_text,
					"general_class"         => $this->get_general_selectors( $method_id ),
					"button_class"          => esc_attr( $button_class_name )
				), $this->args, $temp_list
			);

			$final_template = call_user_func( $func_name, $wp_ulike_template );

			return apply_filters( 'wp_ulike_return_final_templates', preg_replace( '~>\s*\n\s*<~', '><', $final_template ), $wp_ulike_template );

		}

		/**
		 * Get general class selectors
		 *
		 * @param integer $method_id
		 * @return string
		 */
		public function get_general_selectors( $method_id ){
			$selectors	= str_replace( ".", "", apply_filters( 'wp_ulike_general_selector', 'wp_ulike_general_class' ) );

			switch ( $method_id ){
				case 0:
					$selectors .= ' wp_ulike_is_restricted';
					break;
				case 1:
					$selectors .= ' wp_ulike_is_not_liked';
					break;
				case 2:
					$selectors .= ' wp_ulike_is_liked';
					break;
				case 3:
					$selectors .= ' wp_ulike_is_unliked';
					break;
				case 4:
					$selectors .= ' wp_ulike_is_already_liked';
					break;
				case 5:
					$selectors .= ' wp_ulike_is_already_unliked';
					break;
			}

			return esc_attr( $selectors );
		}

	}

}