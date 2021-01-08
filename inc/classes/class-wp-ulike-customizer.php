<?php
/**
 * Wp ULike Admin Customize
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
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
                'title'  => __( 'Button Templates', WP_ULIKE_SLUG ),
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => __( 'Template Wrapper', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => __( 'In this section, you can customize the template wrapper styles.', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'               => 'template_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => __( 'Typography', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class, .wpulike .wp_ulike_put_text, .wpulike .wp_ulike_general_class .count-box',
                    ),
                    array(
                        'id'            => 'template_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => __( 'Normal', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike .wp_ulike_general_class',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Hover', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode'      => 'background-color',
                                        'output'           => '.wpulike .wp_ulike_general_class:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => __( 'Border', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class:hover',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Active', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked, .wpulike .wp_ulike_general_class.wp_ulike_is_liked',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
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
                        'title'            => __( 'Padding', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class',
                    ),
                    array(
                        'id'               => 'template_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => __( 'Margin', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class',
                    ),

                    // Start button section
                    array(
                        'type'    => 'heading',
                        'content' => __( 'Button', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => __( 'In this section, you can customize the styles related to the buttons. Please note that some buttons have different structures (such as SVG based) and therefore you should be more careful in setting them.', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'            => 'button_group',
                        'type'          => 'tabbed',
                        'tabs'          => apply_filters( 'wp_ulike_customizer_button_group_options',  array(
                            array(
                                'title'     => __( 'Normal', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'               => 'button_image_dimensions',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => __( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                    ),
                                    array(
                                        'id'               => 'normal_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => __( 'Like Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                    )
                                )
                            ),
                            array(
                                'title'     => __( 'Hover', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_color',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_mode'      => 'background-color',
                                        'title'            => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_important' => true,
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => __( 'Border', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'output_important' => true,
                                        'title'            => __( 'Like Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image:hover::after',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Active', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'               => 'active_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => __( 'Like Image', WP_ULIKE_SLUG ),
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
                        'title'            => __( 'Button Dimensions', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'               => 'button_padding',
                        'type'             => 'spacing',
                        'output_important' => true,
                        'title'            => __( 'Padding', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    array(
                        'id'               => 'button_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => __( 'Margin', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike .wp_ulike_general_class .wp_ulike_btn',
                    ),
                    // Start Counter Section
                    array(
                        'type'    => 'heading',
                        'content' => __( 'Counter', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => __( 'In this section, you can customize the template counter styles.', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'            => 'counter_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => __( 'Normal', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike .wp_ulike_general_class .count-box, .wpulike .wp_ulike_general_class .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Active', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output'      => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box::before, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                )
                            ),
                        )
                    ),
                    array(
                        'id'     => 'counter_padding',
                        'type'   => 'spacing',
                        'title'  => __( 'Padding', WP_ULIKE_SLUG ),
                        'output' => '.wpulike .wp_ulike_general_class .count-box',
                    ),
                    array(
                        'id'          => 'counter_margin',
                        'type'        => 'spacing',
                        'output_mode' => 'margin',
                        'title'       => __( 'Margin', WP_ULIKE_SLUG ),
                        'output'      => '.wpulike .wp_ulike_general_class .count-box',
                    )
                )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'title'  => __( 'Toast Messages', WP_ULIKE_SLUG ),
                'fields' => array(
                    array(
                        'id'               => 'toast_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => __( 'Typography', WP_ULIKE_SLUG ),
                        'output'           => '.wpulike-notification .wpulike-message',
                    ),
                    array(
                        'id'            => 'toast_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => __( 'Info', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'info_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'          => 'info_bg',
                                        'type'        => 'color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'     => 'info_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message',
                                    ),
                                    array(
                                        'id'               => 'info_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => __( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message::before',
                                    ),
                                    array(
                                        'id'               => 'info_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => __( 'Icon Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Success', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'success_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'          => 'success_bg',
                                        'type'        => 'color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'     => 'success_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-success',
                                    ),
                                    array(
                                        'id'               => 'success_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => __( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                    ),
                                    array(
                                        'id'               => 'success_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => __( 'Icon Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Error', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'error_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'          => 'error_bg',
                                        'type'        => 'color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'     => 'error_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-error',
                                    ),
                                    array(
                                        'id'               => 'error_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => __( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                    ),
                                    array(
                                        'id'               => 'error_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => __( 'Icon Image', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => __( 'Warning', WP_ULIKE_SLUG ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'warning_color',
                                        'type'   => 'color',
                                        'title'  => __( 'Text Color', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'          => 'warning_bg',
                                        'type'        => 'color',
                                        'title'       => __( 'Background', WP_ULIKE_SLUG ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'     => 'warning_border',
                                        'type'   => 'border',
                                        'title'  => __( 'Border', WP_ULIKE_SLUG ),
                                        'output' => '.wpulike-notification .wpulike-message.wpulike-warning',
                                    ),
                                    array(
                                        'id'               => 'warning_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => __( 'Image Dimensions', WP_ULIKE_SLUG ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-warning::before',
                                    ),
                                    array(
                                        'id'               => 'warning_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => __( 'Icon Image', WP_ULIKE_SLUG ),
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
                'title'  => __( 'Likers Box', WP_ULIKE_SLUG ),
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => __( 'Popover', WP_ULIKE_SLUG ),
                    ),
                    array(
                        'id'               => 'likers_popover_bg',
                        'type'             => 'background',
                        'output_important' => true,
                        'title'            => __( 'Background', WP_ULIKE_SLUG ),
                        'output'           => '.ulf-tooltip',
                    ),
                    array(
                        'id'               => 'likers_popover_border',
                        'type'             => 'border',
                        'output_important' => true,
                        'title'            => __( 'Border', WP_ULIKE_SLUG ),
                        'output'           => '.ulf-tooltip',
                    ),
                    array(
                        'id'               => 'likers_popover_arrow_color',
                        'type'             => 'color',
                        'output_important' => true,
                        'title'            => __( 'Arrow Color', WP_ULIKE_SLUG ),
                        'output'           => '.ulf-tooltip .ulf-arrow',
                        'output_mode'      => 'border-top-color'
                    ),
                )
            ));

            do_action( 'wp_ulike_customize_ended' );

        }

    }
}