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
                'title'  => esc_html__( 'Button Templates', WP_ULIKE_SLUG ),
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Template Wrapper', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => esc_html__( 'In this section, you can customize the template wrapper styles.', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'               => 'template_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class, .wpulike .wp_ulike_put_text, .wpulike .wp_ulike_general_class .count-box',
                    ),
                    array(
                        'id'            => 'template_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Normal', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike .wp_ulike_general_class',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Hover', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode'      => 'background-color',
                                        'output'           => '.wpulike .wp_ulike_general_class:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class:hover',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked, .wpulike .wp_ulike_general_class.wp_ulike_is_liked',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
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
                        'title'            => esc_html__( 'Padding', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class',
                    ),
                    array(
                        'id'               => 'template_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class',
                    ),

                    // Start button section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Button', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => esc_html__( 'In this section, you can customize the styles related to the buttons. Please note that some buttons have different structures (such as SVG based) and therefore you should be more careful in setting them.', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'            => 'button_group',
                        'type'          => 'tabbed',
                        'tabs'          => apply_filters( 'wp_ulike_customizer_button_group_options',  array(
                            array(
                                'title'     => esc_html__( 'Normal', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'               => 'button_image_dimensions',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                    ),
                                    array(
                                        'id'               => 'normal_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                    )
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Hover', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_color',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_mode'      => 'background-color',
                                        'title'            => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_important' => true,
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Like Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image:hover::after',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'               => 'active_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', WP_ULIKE_SLUG ),
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
                        'title'            => esc_html__( 'Button Dimensions', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'               => 'button_padding',
                        'type'             => 'spacing',
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'               => 'button_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'         => 'button_align',
                        'type'       => 'button_set',
                        'title'      => esc_html__( 'Button Alignment', WP_ULIKE_SLUG ),
                        'options'    => array(
                            'left'   => esc_html__( 'Left', WP_ULIKE_SLUG ),
                            'center' => esc_html__( 'Center', WP_ULIKE_SLUG ),
                            'right'  => esc_html__( 'Right', WP_ULIKE_SLUG )
                        ),
                        'default'    => ''
                    ),
                    // Start Counter Section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Counter', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => esc_html__( 'In this section, you can customize the template counter styles.', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'            => 'counter_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Normal', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike .wp_ulike_general_class .count-box, .wpulike .wp_ulike_general_class .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box::before, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                )
                            ),
                        )
                    ),
                    array(
                        'id'     => 'counter_padding',
                        'type'   => 'spacing',
                        'title'  => esc_html__( 'Padding', WP_ULIKE_SLUG ),
                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                    ),
                    array(
                        'id'          => 'counter_margin',
                        'type'        => 'spacing',
                        'output_mode' => 'margin',
                        'title'       => esc_html__( 'Margin', WP_ULIKE_SLUG ),
                        'output'      => '.wpulike .wp_ulike_general_class .count-box',
                    )
                )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'title'  => esc_html__( 'Toast Messages', WP_ULIKE_SLUG ),
                'fields' => array(
                    array(
                        'id'               => 'toast_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike-notification .wpulike-message',
                    ),
                    array(
                        'id'            => 'toast_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Info', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'info_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'          => 'info_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'     => 'info_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'               => 'info_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message::before',
                                    ),
                                    array(
                                        'id'               => 'info_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Success', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'success_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'          => 'success_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'     => 'success_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'               => 'success_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                    ),
                                    array(
                                        'id'               => 'success_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Error', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'error_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'          => 'error_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'     => 'error_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'               => 'error_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                    ),
                                    array(
                                        'id'               => 'error_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Warning', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'warning_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'          => 'warning_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'     => 'warning_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'               => 'warning_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-warning::before',
                                    ),
                                    array(
                                        'id'               => 'warning_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', WP_ULIKE_SLUG ),
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
                'title'  => esc_html__( 'Likers Box', WP_ULIKE_SLUG ),
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Popover', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'               => 'likers_popover_bg',
                        'type'             => 'background',
                        'output_important' => true,
                        'title'            => esc_html__( 'Background', WP_ULIKE_SLUG ),
                        'output'           => '.ulf-tooltip',
                    ),
                    array(
                        'id'               => 'likers_popover_border',
                        'type'             => 'border',
                        'output_important' => true,
                        'title'            => esc_html__( 'Border', WP_ULIKE_SLUG ),
                        'output'           => '.ulf-tooltip',
                    ),
                    array(
                        'id'               => 'likers_popover_arrow_color',
                        'type'             => 'color',
                        'output_important' => true,
                        'title'            => esc_html__( 'Arrow Color', WP_ULIKE_SLUG ),
                        'output'           => '.ulf-tooltip .ulf-arrow',
                        'output_mode'      => 'border-top-color'
                    ),
                )
            ));

            do_action( 'wp_ulike_customize_ended' );

        }

    }
}