<?php
/**
 * Wp ULike Admin Customize
 * // @echo HEADER
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'wp_ulike_customizer' ) ) {
    class wp_ulike_customizer{

        protected $option_domain = 'wp_ulike_customize';

		/**
		 * __construct
		 */
		function __construct() {
            add_action( 'ulf_loaded', array( $this, 'register_panel' ) );
        }

        /**
         * Register setting panel
         *
         * @return void
         */
        public function register_panel(){
            // Create customize options
            ULF::createCustomizeOptions( $this->option_domain, array(
                'database'        => 'option',
                'transport'       => 'refresh',
                'save_defaults'   => true,
                'enqueue_webfont' => true,
                'async_webfont'   => false,
                'output_css'      => true,
            ) );

            do_action( 'wp_ulike_customize_loaded' );

            ULF::createSection( $this->option_domain, array(
                'id'    => WP_ULIKE_SLUG,   // Set a unique slug-like ID
                'title' => WP_ULIKE_NAME
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'title'  => esc_html__( 'Button Templates', 'wp-ulike' ),
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Template Wrapper', 'wp-ulike' ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => esc_html__( 'In this section, you can customize the template wrapper styles.', 'wp-ulike' ),
                    ),
                    array(
                        'id'               => 'template_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_general_class, .wpulike .wp_ulike_put_text, .wpulike .wp_ulike_general_class .count-box',
                    ),
                    array(
                        'id'            => 'template_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Normal', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike .wp_ulike_general_class',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Hover', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode'      => 'background-color',
                                        'output'           => '.wpulike .wp_ulike_general_class:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class:hover',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked, .wpulike .wp_ulike_general_class.wp_ulike_is_liked',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked, .wpulike .wp_ulike_general_class.wp_ulike_is_liked',
                                    ),
                                )
                            ),
                        )
                    ),
                    array(
                        'id'               => 'template_padding',
                        'type'             => 'spacing',
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_general_class',
                    ),
                    array(
                        'id'               => 'template_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_general_class',
                    ),

                    // Start button section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Button', 'wp-ulike' ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => esc_html__( 'In this section, you can customize the styles related to the buttons. Please note that some buttons have different structures (such as SVG based) and therefore you should be more careful in setting them.', 'wp-ulike' ),
                    ),
                    array(
                        'id'            => 'button_group',
                        'type'          => 'tabbed',
                        'tabs'          => apply_filters( 'wp_ulike_customizer_button_group_options',  array(
                            array(
                                'title'     => esc_html__( 'Normal', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'               => 'button_image_dimensions',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                    ),
                                    array(
                                        'id'               => 'normal_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                    )
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Hover', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_color',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_mode'      => 'background-color',
                                        'title'            => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_important' => true,
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Like Image', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image:hover::after',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'               => 'active_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', 'wp-ulike' ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active.wp_ulike_put_image::after',
                                    ),
                                )
                            ),
                        )
                    ) ),
                    array(
                        'id'               => 'button_dimensions',
                        'type'             => 'dimensions',
                        'output_important' => true,
                        'title'            => esc_html__( 'Button Dimensions', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'               => 'button_padding',
                        'type'             => 'spacing',
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'               => 'button_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'         => 'button_align',
                        'type'       => 'button_set',
                        'title'      => esc_html__( 'Button Alignment', 'wp-ulike' ),
                        'options'    => array(
                            'left'   => esc_html__( 'Left', 'wp-ulike' ),
                            'center' => esc_html__( 'Center', 'wp-ulike' ),
                            'right'  => esc_html__( 'Right', 'wp-ulike' )
                        ),
                        'default'    => ''
                    ),
                    // Start Counter Section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Counter', 'wp-ulike' ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => esc_html__( 'In this section, you can customize the template counter styles.', 'wp-ulike' ),
                    ),
                    array(
                        'id'            => 'counter_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Normal', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike .wp_ulike_general_class .count-box, .wpulike .wp_ulike_general_class .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box::before, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                )
                            ),
                        )
                    ),
                    array(
                        'id'     => 'counter_padding',
                        'type'   => 'spacing',
                        'title'  => esc_html__( 'Padding', 'wp-ulike' ),
                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                    ),
                    array(
                        'id'          => 'counter_margin',
                        'type'        => 'spacing',
                        'output_mode' => 'margin',
                        'title'       => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'      => '.wpulike .wp_ulike_general_class .count-box',
                    )
                )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'title'  => esc_html__( 'Toast Messages', 'wp-ulike' ),
                'fields' => array(
                    array(
                        'id'               => 'toast_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'wp-ulike' ),
                        'output'           => '.wpulike-notification .wpulike-message',
                    ),
                    array(
                        'id'            => 'toast_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Info', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'info_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'          => 'info_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'     => 'info_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'               => 'info_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message::before',
                                    ),
                                    array(
                                        'id'               => 'info_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Success', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'success_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'          => 'success_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'     => 'success_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'               => 'success_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                    ),
                                    array(
                                        'id'               => 'success_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Error', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'error_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'          => 'error_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'     => 'error_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'               => 'error_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                    ),
                                    array(
                                        'id'               => 'error_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Warning', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'warning_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'          => 'warning_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'     => 'warning_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'               => 'warning_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-warning::before',
                                    ),
                                    array(
                                        'id'               => 'warning_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-warning::before',
                                    ),
                                )
                            ),
                        )
                    ),
                )
            ));

            ULF::createSection( $this->option_domain, array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'title'  => esc_html__( 'Likers Box', 'wp-ulike' ),
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Popover', 'wp-ulike' ),
                    ),
                    array(
                        'id'               => 'likers_popover_bg',
                        'type'             => 'background',
                        'output_important' => true,
                        'title'            => esc_html__( 'Background', 'wp-ulike' ),
                        'output'           => '.ulf-tooltip',
                    ),
                    array(
                        'id'               => 'likers_popover_border',
                        'type'             => 'border',
                        'output_important' => true,
                        'title'            => esc_html__( 'Border', 'wp-ulike' ),
                        'output'           => '.ulf-tooltip',
                    ),
                    array(
                        'id'               => 'likers_popover_arrow_color',
                        'type'             => 'color',
                        'output_important' => true,
                        'title'            => esc_html__( 'Arrow Color', 'wp-ulike' ),
                        'output'           => '.ulf-tooltip .ulf-arrow',
                        'output_mode'      => 'border-top-color'
                    ),
                )
            ));

            do_action( 'wp_ulike_customize_ended' );

        }

    }
}