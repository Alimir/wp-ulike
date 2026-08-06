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
        protected $sections_cache = null;

		/**
		 * __construct
		 */
		function __construct() {
            // No framework dependencies - just initialize
        }

        /**
         * Register customizer sections
         * Returns array structure for API consumption
         *
         * @return array Sections structure
         */
        public function register_sections(){
            // Return cached sections if available
            if ( $this->sections_cache !== null ) {
                return $this->sections_cache;
            }

            do_action( 'wp_ulike_customize_started' );

            $sections = array();

            $parent_section = array(
                'id'    => WP_ULIKE_SLUG,   // Set a unique slug-like ID
                'title' => esc_html__( 'WP ULike', 'wp-ulike' )
            );

            // Expose section via filter for API access
            apply_filters( 'wp_ulike_optiwich_customizer_section', $parent_section, $this->option_domain );

            $sections[] = $parent_section;

            $sections[] = array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'id'     => 'button_templates',
                'title'  => esc_html__( 'Button', 'wp-ulike' ),
                'template' => 'button',                              // Template ID for customizer preview
                'icon'   => 'cursor-arrow-rays',                     // Icon for template selector
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Wrapper', 'wp-ulike' ),
                    ),
                    array(
                        'id'               => 'template_typography',
                        'type'             => 'typography',
                        'responsive'       => true,
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'wp-ulike' ),
                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class, .wpulike:not(.wpulike-engagement-template) .wp_ulike_put_text, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box',
                        'units'            => array('px', 'em', 'rem')
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
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class',
                                        'units'  => array('px', 'em', 'rem')
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
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class:hover',
                                        'units'            => array('px', 'em', 'rem', '%')
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
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_liked, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_liked',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_liked, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_liked',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                )
                            ),
                            // Removed (vote cleared) — additive IDs; Active (liked) configs untouched.
                            array(
                                'title'     => esc_html__( 'Removed', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'removed_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked',
                                    ),
                                    array(
                                        'id'     => 'removed_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked',
                                        'units'  => array( 'px', 'em', 'rem' ),
                                    ),
                                ),
                            ),
                        )
                    ),
                    array(
                        'id'               => 'template_padding',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'template_margin',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),

                    // Start button section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Button', 'wp-ulike' ),
                    ),
                    array(
                        'id'               => 'button_dimensions',
                        'type'             => 'dimensions',
                        'responsive'       => true,
                        'output_important' => true,
                        'title'            => esc_html__( 'Dimensions', 'wp-ulike' ),
                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'button_padding',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'button_margin',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'button_align',
                        'type'             => 'button_set',
                        'title'            => esc_html__( 'Alignment', 'wp-ulike' ),
                        'options'          => array(
                            'left'   => esc_html__( 'Left', 'wp-ulike' ),
                            'center' => esc_html__( 'Center', 'wp-ulike' ),
                            'right'  => esc_html__( 'Right', 'wp-ulike' )
                        ),
                        'output_css'       => array(
                            'left'   => array(
                                array( 'selector' => '.wpulike:not(.wpulike-engagement-template)', 'property' => 'text-align', 'value' => 'left' ),
                                array( 'selector' => '.wpulike:not(.wpulike-engagement-template)', 'property' => 'justify-content', 'value' => 'flex-start' ),
                            ),
                            'center' => array(
                                array( 'selector' => '.wpulike:not(.wpulike-engagement-template)', 'property' => 'text-align', 'value' => 'center' ),
                                array( 'selector' => '.wpulike:not(.wpulike-engagement-template)', 'property' => 'justify-content', 'value' => 'center' ),
                            ),
                            'right'  => array(
                                array( 'selector' => '.wpulike:not(.wpulike-engagement-template)', 'property' => 'text-align', 'value' => 'right' ),
                                array( 'selector' => '.wpulike:not(.wpulike-engagement-template)', 'property' => 'justify-content', 'value' => 'flex-end' ),
                            ),
                        ),
                        'output_important' => true,
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
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'button_image_dimensions',
                                        'type'             => 'dimensions',
                                        'responsive'       => true,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'normal_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::after',
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
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_mode'      => 'background-color',
                                        'title'            => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_important' => true,
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn:hover',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'hover_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Like Image', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image:hover::after',
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
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'active_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active.wp_ulike_put_image::after',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Removed', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'removed_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .wp_ulike_btn, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'          => 'removed_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .wp_ulike_btn, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .wp_ulike_btn',
                                    ),
                                    array(
                                        'id'     => 'removed_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .wp_ulike_btn, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .wp_ulike_btn',
                                        'units'  => array( 'px', 'em', 'rem' ),
                                    ),
                                    array(
                                        'id'               => 'removed_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Image', 'wp-ulike' ),
                                        'output'           => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .wp_ulike_btn.wp_ulike_put_image::after, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .wp_ulike_btn.wp_ulike_put_image::after',
                                    ),
                                ),
                            ),
                        )
                    ) ),
                    // Start Counter Section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Counter', 'wp-ulike' ),
                    ),
                    array(
                        'id'         => 'counter_padding',
                        'type'       => 'spacing',
                        'responsive' => true,
                        'title'      => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'     => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box',
                        'units'      => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'          => 'counter_margin',
                        'type'        => 'spacing',
                        'responsive'  => true,
                        'output_mode' => 'margin',
                        'title'       => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box',
                        'units'       => array('px', 'em', 'rem', '%')
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
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class .count-box',
                                        'units'  => array('px', 'em', 'rem')
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
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_liked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_liked .count-box::before, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_liked .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_liked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_liked .count-box',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Removed', 'wp-ulike' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'removed_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .count-box',
                                    ),
                                    array(
                                        'id'          => 'removed_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output'      => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .count-box::before, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'removed_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_unliked .count-box, .wpulike:not(.wpulike-engagement-template) .wp_ulike_general_class.wp_ulike_is_already_unliked .count-box',
                                        'units'  => array( 'px', 'em', 'rem' ),
                                    ),
                                ),
                            ),
                        )
                    ),
                                                        )
            );

            // Likers Box — styles Inline + Popover (extensions may add layouts via preview action).
            $sections[] = array(
                'parent'   => WP_ULIKE_SLUG,
                'id'       => 'likers_box',
                'title'    => esc_html__( 'Likers', 'wp-ulike' ),
                'template' => 'likers',
                'icon'     => 'user-group',
                'fields'   => array(
                    array(
                        'id'      => 'likers_box_style_heading',
                        'type'    => 'heading',
                        'content' => esc_html__( 'General', 'wp-ulike' ),
                    ),
                    array(
                        'id'               => 'likers_typography',
                        'type'             => 'typography',
                        'responsive'       => true,
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'wp-ulike' ),
                        // Exclude Pro pile chrome — pile has no box on the frontend.
                        'output'           => '.wp_ulike_likers_wrapper:not(.wp_ulike_pile_list_container), .ulf-tooltip .wp_ulike_likers_wrapper',
                        'units'            => array( 'px', 'em', 'rem' ),
                    ),
                    array(
                        'id'               => 'likers_avatar_dimensions',
                        'type'             => 'dimensions',
                        'responsive'       => true,
                        'output_important' => true,
                        'title'            => esc_html__( 'Avatar Size', 'wp-ulike' ),
                        'output'           => '.wp_ulike_likers_wrapper .wp-ulike-likers-list .wp-ulike-liker a > img, .wp_ulike_likers_wrapper .wp-ulike-likers-list li a > img, .ulf-tooltip .wp_ulike_likers_wrapper img',
                        'units'            => array( 'px', 'em', 'rem' ),
                    ),
                    array(
                        'id'          => 'likers_bg',
                        'type'        => 'color',
                        'output_mode' => 'background-color',
                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                        // Theme class is on the tooltip root (tooltip.js), not a nested child.
                        'output'      => '.wp_ulike_likers_wrapper:not(.wp_ulike_pile_list_container), .ulf-tooltip .wp_ulike_likers_wrapper, .ulf-tooltip.ulf-white-theme, .ulf-tooltip.ulf-light-theme',
                    ),
                    array(
                        'id'     => 'likers_border',
                        'type'   => 'border',
                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                        'output' => '.wp_ulike_likers_wrapper:not(.wp_ulike_pile_list_container), .ulf-tooltip .wp_ulike_likers_wrapper, .ulf-tooltip.ulf-white-theme, .ulf-tooltip.ulf-light-theme',
                        'units'  => array( 'px', 'em', 'rem' ),
                    ),
                    array(
                        'id'               => 'likers_padding',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'title'            => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'           => '.wp_ulike_likers_wrapper:not(.wp_ulike_pile_list_container), .ulf-tooltip.ulf-white-theme .ulf-content, .ulf-tooltip.ulf-light-theme .ulf-content',
                        'units'            => array( 'px', 'em', 'rem', '%' ),
                    ),
                    array(
                        'id'               => 'likers_margin',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'output_mode'      => 'margin',
                        'title'            => esc_html__( 'Margin', 'wp-ulike' ),
                        'output'           => '.wpulike .wp_ulike_likers_wrapper',
                        'units'            => array( 'px', 'em', 'rem', '%' ),
                    ),
                ),
            );

            $sections[] = array(
                'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
                'id'     => 'toast_messages',
                'title'  => esc_html__( 'Toast', 'wp-ulike' ),
                'template' => 'toast',                               // Template ID for customizer preview
                'icon'   => 'bell',                                  // Icon for template selector
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'General', 'wp-ulike' ),
                    ),
                    array(
                        'id'               => 'toast_typography',
                        'type'             => 'typography',
                        'responsive'       => true,
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'wp-ulike' ),
                        'output'           => '.wpulike-notification .wpulike-message',
                        'units'            => array('px', 'em', 'rem')
                    ),
                    array(
                        'id'               => 'toast_message_width',
                        'type'             => 'slider',
                        'responsive'       => true,
                        'title'            => esc_html__( 'Width', 'wp-ulike' ),
                        'output'           => '.wpulike-notification .wpulike-message',
                        'output_mode'      => 'width',
                        'min'              => 200,
                        'max'              => 480,
                        'unit'             => 'px',
                        'output_important' => true,
                    ),
                    array(
                        'id'               => 'toast_message_radius',
                        'type'             => 'slider',
                        'title'            => esc_html__( 'Border Radius', 'wp-ulike' ),
                        'output'           => '.wpulike-notification .wpulike-message',
                        'output_mode'      => 'border-radius',
                        'min'              => 0,
                        'max'              => 40,
                        'unit'             => 'px',
                        'output_important' => true,
                    ),
                    array(
                        'id'               => 'toast_message_padding',
                        'type'             => 'spacing',
                        'responsive'       => true,
                        'title'            => esc_html__( 'Padding', 'wp-ulike' ),
                        'output'           => '.wpulike-notification .wpulike-message',
                        'units'            => array( 'px', 'em', 'rem' ),
                        'output_important' => true,
                    ),
                    array(
                        'id'      => 'toast_layout_heading',
                        'type'    => 'heading',
                        'content' => esc_html__( 'Layout', 'wp-ulike' ),
                    ),
                    array(
                        'id'      => 'toast_position',
                        'type'    => 'button_set',
                        'title'   => esc_html__( 'Position', 'wp-ulike' ),
                        'options' => array(
                            'bottom-right' => esc_html__( 'Bottom Right', 'wp-ulike' ),
                            'bottom-left'  => esc_html__( 'Bottom Left', 'wp-ulike' ),
                            'top-right'    => esc_html__( 'Top Right', 'wp-ulike' ),
                            'top-left'     => esc_html__( 'Top Left', 'wp-ulike' ),
                        ),
                        'default' => 'bottom-right',
                        // Set box edges directly (preview loads min.css; must not rely on unset inset vars).
                        'output_css' => array(
                            'bottom-right' => array(
                                array( 'selector' => '.wpulike-notification', 'property' => 'top', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'left', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'right', 'value' => 'var(--ulp-toast-offset-x)' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'bottom', 'value' => 'var(--ulp-toast-offset-y)' ),
                            ),
                            'bottom-left'  => array(
                                array( 'selector' => '.wpulike-notification', 'property' => 'top', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'right', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'left', 'value' => 'var(--ulp-toast-offset-x)' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'bottom', 'value' => 'var(--ulp-toast-offset-y)' ),
                            ),
                            'top-right'    => array(
                                array( 'selector' => '.wpulike-notification', 'property' => 'bottom', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'left', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'right', 'value' => 'var(--ulp-toast-offset-x)' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'top', 'value' => 'var(--ulp-toast-offset-y)' ),
                            ),
                            'top-left'     => array(
                                array( 'selector' => '.wpulike-notification', 'property' => 'bottom', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'right', 'value' => 'auto' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'left', 'value' => 'var(--ulp-toast-offset-x)' ),
                                array( 'selector' => '.wpulike-notification', 'property' => 'top', 'value' => 'var(--ulp-toast-offset-y)' ),
                            ),
                        ),
                        'output_important' => true,
                    ),
                    array(
                        'id'               => 'toast_offset_y',
                        'type'             => 'slider',
                        'responsive'       => true,
                        'title'            => esc_html__( 'Vertical Offset', 'wp-ulike' ),
                        'output'           => '.wpulike-notification',
                        'output_mode'      => '--ulp-toast-offset-y',
                        'min'              => 0,
                        'max'              => 120,
                        'unit'             => 'px',
                        'output_important' => true,
                    ),
                    array(
                        'id'               => 'toast_offset_x',
                        'type'             => 'slider',
                        'responsive'       => true,
                        'title'            => esc_html__( 'Horizontal Offset', 'wp-ulike' ),
                        'output'           => '.wpulike-notification',
                        'output_mode'      => '--ulp-toast-offset-x',
                        'min'              => 0,
                        'max'              => 120,
                        'unit'             => 'px',
                        'output_important' => true,
                    ),
                    array(
                        'id'      => 'toast_styles_heading',
                        'type'    => 'heading',
                        'content' => esc_html__( 'Styles', 'wp-ulike' ),
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
                                        // Info is the base toast (no status class) — exclude success/error/warning.
                                        'output' => '.wpulike-notification .wpulike-message:not(.wpulike-success):not(.wpulike-error):not(.wpulike-warning)',
                                    ),
                                    array(
                                        'id'          => 'info_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'wp-ulike' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.wpulike-notification .wpulike-message:not(.wpulike-success):not(.wpulike-error):not(.wpulike-warning)',
                                    ),
                                    array(
                                        'id'     => 'info_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'wp-ulike' ),
                                        'output' => '.wpulike-notification .wpulike-message:not(.wpulike-success):not(.wpulike-error):not(.wpulike-warning)',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'info_icon_size',
                                        'type'             => 'dimensions',
                                        'responsive'       => true,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message:not(.wpulike-success):not(.wpulike-error):not(.wpulike-warning)::before',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'info_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message:not(.wpulike-success):not(.wpulike-error):not(.wpulike-warning)::before',
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
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'success_icon_size',
                                        'type'             => 'dimensions',
                                        'responsive'       => true,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-success::before',
                                        'units'            => array('px', 'em', 'rem', '%')
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
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'error_icon_size',
                                        'type'             => 'dimensions',
                                        'responsive'       => true,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-error::before',
                                        'units'            => array('px', 'em', 'rem', '%')
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
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'warning_icon_size',
                                        'type'             => 'dimensions',
                                        'responsive'       => true,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'wp-ulike' ),
                                        'output'           => '.wpulike-notification .wpulike-message.wpulike-warning::before',
                                        'units'            => array('px', 'em', 'rem', '%')
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
                ),
            );

            do_action( 'wp_ulike_customize_ended' );

            // Allow pro version and other extensions to add/modify sections
            $sections = apply_filters( 'wp_ulike_customizer_sections', $sections );

            // Cache sections
            $this->sections_cache = $sections;

            return $sections;
        }

    }
}