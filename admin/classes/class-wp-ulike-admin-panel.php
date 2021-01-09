<?php
/**
 * Wp ULike Admin Panel
 * // @echo HEADER
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_admin_panel' ) ) {
    class wp_ulike_admin_panel{

        protected $option_domain = 'wp_ulike_settings';

		/**
		 * __construct
		 */
		function __construct() {
            add_action( 'ulf_loaded', array( $this, 'register_panel' ) );
            add_action( 'wp_ulike_settings_loaded', array( $this, 'register_sections' ) );
            add_action( 'wp_ulike_settings_loaded', array( $this, 'register_pages' ) );

            add_action( 'ulf_'.$this->option_domain.'_saved', array( $this, 'options_saved' ) );
        }

        public function options_saved(){
            // Update custom css
            wp_ulike_save_custom_css();
        }

        /**
         * Register setting panel
         *
         * @return void
         */
        public function register_panel(){
            // Create options
            ULF::createOptions( $this->option_domain, array(
                'framework_title'    => apply_filters( 'wp_ulike_plugin_name', WP_ULIKE_NAME ),
                'menu_title'         => apply_filters( 'wp_ulike_plugin_name', WP_ULIKE_NAME ),
                'sub_menu_title'     => __( 'Settings', WP_ULIKE_SLUG ),
                'menu_slug'          => 'wp-ulike-settings',
                'menu_capability'    => 'manage_options',
                'menu_icon'          => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMjUgMjUiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDI1IDI1OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PHBhdGggY2xhc3M9InN0MCIgZD0iTTIzLjksNy4xTDIzLjksNy4xYy0xLjUtMS41LTMuOS0xLjUtNS40LDBsLTEuNSwxLjVsMS40LDEuNGwxLjUtMS41YzAuNC0wLjQsMC44LTAuNiwxLjMtMC42YzAuNSwwLDEuMSwwLjIsMS40LDAuNmMwLjcsMC44LDAuNywyLTAuMSwyLjdsLTEsMWMtMC41LDAuNS0xLjIsMC41LTEuNiwwYy0wLjktMC45LTUuMS01LjEtNS4xLTUuMWMtMC43LTAuNy0xLjctMS4xLTIuNy0xLjFsMCwwYy0xLDAtMiwwLjQtMi43LDEuMUM5LDcuNCw4LjgsNy43LDguNiw4LjFMOC41LDguM2wxLjYsMS42bDAuMS0wLjVjMC4yLTEsMS4yLTEuNywyLjMtMS41YzAuNCwwLjEsMC43LDAuMiwxLDAuNWw1LjksNS45TDE2LjYsMTdMMTIuNywxM2wwLDBjLTAuMS0wLjEtMC40LTAuNC0yLjEtMi4xbC00LTRDNSw1LjQsMi42LDUuNCwxLjEsNi45Yy0xLjUsMS41LTEuNSwzLjksMCw1LjRsNiw2YzAuMywwLjMsMC44LDAuNSwxLjIsMC41bDAsMGMwLjUsMCwwLjktMC4yLDEuMi0wLjVsMi41LTIuNWwtMS40LTEuNGwtMi40LDIuNGwtNS45LTUuOWMtMC43LTAuOC0wLjctMiwwLjEtMi43YzAuNy0wLjcsMS45LTAuNywyLjYsMGw0LDRjMC4xLDAuMSwwLjEsMC4yLDAuMiwwLjJsNiw2YzAuMywwLjMsMC44LDAuNSwxLjMsMC41YzAsMCwwLDAsMCwwYzAuNSwwLDAuOS0wLjIsMS4yLTAuNWw2LTZDMjUuNCwxMSwyNS40LDguNiwyMy45LDcuMXoiLz48L3N2Zz4=',
                'menu_position'      => 313,
                'show_bar_menu'      => false,
                'show_sub_menu'      => true,
                'show_search'        => true,
                'show_reset_all'     => true,
                'show_reset_section' => true,
                'show_footer'        => true,
                'show_all_options'   => true,
                'show_form_warning'  => true,
                'sticky_header'      => true,
                'save_defaults'      => true,
                'ajax_save'          => true,
                'footer_credit'      => sprintf(
                    '%s <a href="%s" title="TechnoWich" target="_blank">%s</a>',
                    __( 'Proudly Powered By', WP_ULIKE_SLUG ),
                    'https://technowich.com/?utm_source=footer-link&utm_campaign=wp-ulike&utm_medium=wp-dash',
                    __( 'TechnoWich', WP_ULIKE_SLUG )
                ),
                'footer_after'       => '',
                'enqueue_webfont'    => true,
                'async_webfont'      => false,
                'output_css'         => true,
                'theme'              => 'light wp-ulike-settings-panel'
            ) );

            do_action( 'wp_ulike_settings_loaded' );
        }

        /**
         * Register admin page
         *
         * @return void
         */
        public function register_pages(){
            new wp_ulike_admin_pages();
        }

        /**
         * Register setting sections
         *
         * @return void
         */
        public function register_sections(){

            do_action( 'wp_ulike_panel_sections_started' );

            /**
             * Configuration Section
             */
            ULF::createSection( $this->option_domain, array(
                'id'    => 'configuration',
                'title' => __( 'Configuration',WP_ULIKE_SLUG),
                'icon'  => 'fa fa-home',
            ) );
            // General
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => __( 'General',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_general', array(
                    array(
                        'id'      => 'enable_kilobyte_format',
                        'type'    => 'switcher',
                        'title'   => __('Enable Convertor', WP_ULIKE_SLUG),
                        'default' => false,
                        'desc'    => __('Convert numbers of Likes with string (kilobyte) format.', WP_ULIKE_SLUG) . '<strong> (WHEN? likes>=1000)</strong>'
                    ),
                    array(
                        'id'            => 'filter_counter_value',
                        'type'          => 'tabbed',
                        'desc'          => __( 'Set your custom prefix/postfix on counter value', WP_ULIKE_SLUG),
                        'title'         => __( 'Filter Counter Value', WP_ULIKE_SLUG),
                        'tabs'          => array(
                            array(
                                'title'     => __('Prefix',WP_ULIKE_SLUG),
                                'fields'    => apply_filters( 'wp_ulike_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_prefix',
                                        'type'    => 'text',
                                        'title'   => __('Like',WP_ULIKE_SLUG),
                                        'default' => '+'
                                    ),
                                    array(
                                        'id'      => 'unlike_prefix',
                                        'type'    => 'text',
                                        'title'   => __('Unlike',WP_ULIKE_SLUG),
                                        'default' => '+'
                                    ),
                                ), 'prefix' )
                            ),
                            array(
                                'title'     => __('Postfix',WP_ULIKE_SLUG),
                                'fields'    => apply_filters( 'wp_ulike_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_postfix',
                                        'type'    => 'text',
                                        'title'   => __('Like',WP_ULIKE_SLUG)
                                    ),
                                    array(
                                        'id'      => 'unlike_postfix',
                                        'type'    => 'text',
                                        'title'   => __('Unlike',WP_ULIKE_SLUG)
                                    ),
                                ), 'postfix' )
                            ),
                        )
                    ),
                    array(
                        'id'      => 'enable_toast_notice',
                        'type'    => 'switcher',
                        'title'   => __('Enable Notifications', WP_ULIKE_SLUG),
                        'default' => true,
                        'desc'    => __('Custom toast messages after each activity', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'          => 'filter_toast_types',
                        'type'        => 'select',
                        'title'       => __( 'Disable Toast Types',WP_ULIKE_SLUG ),
                        'desc'        => __('With this option, you can disable toasts messages on content types.', WP_ULIKE_SLUG),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'post'     => __('Posts', WP_ULIKE_SLUG),
                            'comment'  => __('Comments', WP_ULIKE_SLUG),
                            'activity' => __('Activities', WP_ULIKE_SLUG),
                            'topic'    => __('Topics', WP_ULIKE_SLUG)
                        ),
                        'dependency'=> array( 'enable_toast_notice', '==', 'true' ),
                    ),
                    array(
                        'id'    => 'enable_anonymise_ip',
                        'type'  => 'switcher',
                        'title' => __('Enable Anonymize IP', WP_ULIKE_SLUG),
                        'desc'  => __('Anonymize the IP address for GDPR Compliance', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'    => 'cache_exist',
                        'type'  => 'switcher',
                        'title' => __('Enable Cache Exist', WP_ULIKE_SLUG),
                        'desc'  => __('Please enable this option, If you have any cache service on your website.', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'    => 'disable_admin_notice',
                        'type'  => 'switcher',
                        'title' => __('Hide Admin Notices', WP_ULIKE_SLUG),
                        'desc'  => __('Enabling this option will completely disable all admin notices.', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'          => 'disable_plugin_files',
                        'type'        => 'select',
                        'title'       => __( 'Disable Plugin Files',WP_ULIKE_SLUG ),
                        'desc'        => __('With this option, you can disable all plugin assets on these pages.', WP_ULIKE_SLUG),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'home'        => __('Home', WP_ULIKE_SLUG),
                            'single'      => __('Singular', WP_ULIKE_SLUG),
                            'archive'     => __('Archives', WP_ULIKE_SLUG),
                            'category'    => __('Categories', WP_ULIKE_SLUG),
                            'search'      => __('Search Results', WP_ULIKE_SLUG),
                            'tag'         => __('Tags', WP_ULIKE_SLUG),
                            'author'      => __('Author Page', WP_ULIKE_SLUG),
                            'buddypress'  => __('BuddyPress Pages', WP_ULIKE_SLUG),
                            'bbpress'     => __('bbPress Pages', WP_ULIKE_SLUG),
                            'woocommerce' => __('WooCommerce Pages', WP_ULIKE_SLUG)
                        )
                    ),
                    array(
                        'id'          => 'enable_admin_posts_columns',
                        'type'        => 'select',
                        'title'       => __( 'Enable Admin Columns',WP_ULIKE_SLUG ),
                        'desc'        => __('Add counter stats column to the selected post types', WP_ULIKE_SLUG),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => 'post_types'
                    )
                ) )
            ) );

            // Get all content options
            $get_content_options = apply_filters( 'wp_ulike_panel_content_options', $this->get_content_options() );
            $get_content_fields  = array();

            // Generate posts fields
            $get_content_fields['posts']    = $get_content_options;

            // Generate comment fields
            $get_content_fields['comments'] = $get_content_options;
            unset( $get_content_fields['comments']['auto_display_filter'] );
            unset( $get_content_fields['comments']['auto_display_filter_post_types'] );

            // Generate buddypress fields
            $get_content_fields['buddypress'] = $get_content_options;
            unset( $get_content_fields['buddypress']['auto_display_filter'] );
            unset( $get_content_fields['buddypress']['auto_display_filter_post_types'] );
            $get_content_fields['buddypress']['auto_display_position']['options'] = array(
                'content' => __('Activity Content', WP_ULIKE_SLUG),
                'meta'    => __('Activity Meta', WP_ULIKE_SLUG)
            );
            $get_content_fields['buddypress']['auto_display_position']['default'] = 'content';
            $get_content_fields['buddypress']['enable_comments'] = array(
                'id'         => 'enable_comments',
                'type'       => 'switcher',
                'title'      => __('Activity Comment', WP_ULIKE_SLUG),
                'desc'       => __('Add the possibility to like Buddypress comments in the activity stream', WP_ULIKE_SLUG),
                'dependency' => array( 'enable_auto_display', '==', 'true' )
            );
            $get_content_fields['buddypress']['enable_add_bp_activity'] = array(
                'id'         => 'enable_add_bp_activity',
                'type'       => 'switcher',
                'title'      => __('Enable Activity Notification', WP_ULIKE_SLUG),
                'desc'       => __('Insert new likes in buddyPress activity page', WP_ULIKE_SLUG),
            );
            $get_content_fields['buddypress']['posts_notification_template'] = array(
                'id'       => 'posts_notification_template',
                'type'     => 'code_editor',
                'settings' => array(
                    'theme' => 'shadowfox',
                    'mode'  => 'htmlmixed',
                ),
                'default'  => '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
                'title'    => __('Post Activity Text', WP_ULIKE_SLUG),
                'desc'     => __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%POST_LIKER%</code> , <code>%POST_PERMALINK%</code> , <code>%POST_COUNT%</code> , <code>%POST_TITLE%</code>',
                'dependency'=> array( 'enable_add_bp_activity', '==', 'true' ),
            );
            $get_content_fields['buddypress']['comments_notification_template'] = array(
                'id'       => 'comments_notification_template',
                'type'     => 'code_editor',
                'settings' => array(
                    'theme' => 'shadowfox',
                    'mode'  => 'htmlmixed',
                ),
                'default'  => '<strong>%COMMENT_LIKER%</strong> liked <strong>%COMMENT_AUTHOR%</strong> comment. (So far, %COMMENT_AUTHOR% has <span class="badge">%COMMENT_COUNT%</span> likes for this comment)',
                'title'    => __('Comment Activity Text', WP_ULIKE_SLUG),
                'desc'     => __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%COMMENT_LIKER%</code> , <code>%COMMENT_AUTHOR%</code> , <code>%COMMENT_COUNT%</code>, <code>%COMMENT_PERMALINK%</code>',
                'dependency'=> array( 'enable_add_bp_activity', '==', 'true' ),
            );
            $get_content_fields['buddypress']['enable_add_notification'] = array(
                'id'         => 'enable_add_notification',
                'type'       => 'switcher',
                'title'      => __('Enable User Notification', WP_ULIKE_SLUG),
                'desc'       => __('Sends out notifications when you get a like from someone', WP_ULIKE_SLUG),
            );
            $buddypress_options = array( array(
                'type'    => 'content',
                'content' => sprintf( '<strong>%s</strong> %s', __( 'BuddyPress', WP_ULIKE_SLUG ), __( 'plugin is not installed or activated', WP_ULIKE_SLUG ) ),
            ) );
            if( function_exists('is_buddypress') ){
                $buddypress_options = array_values( apply_filters( 'wp_ulike_panel_buddypress_type_options', $get_content_fields['buddypress'] ) );
            }

            // Generate bbPress fields
            $get_content_fields['bbpress'] = $get_content_options;
            unset( $get_content_fields['bbpress']['auto_display_filter'] );
            unset( $get_content_fields['bbpress']['auto_display_filter_post_types'] );

            $bbPress_options = array( array(
                'type'    => 'content',
                'content' => sprintf( '<strong>%s</strong> %s', __( 'bbPress', WP_ULIKE_SLUG ), __( 'plugin is not installed or activated', WP_ULIKE_SLUG ) ),
            ) );
            if( function_exists('is_bbpress') ){
                $bbPress_options = array_values( apply_filters( 'wp_ulike_panel_bbpress_type_options', $get_content_fields['bbpress'] ) );
            }

            // Content Groups
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => __( 'Content Types',WP_ULIKE_SLUG),
                'fields' => array(
                    // Posts
                    array(
                        'id'       => 'posts_group',
                        'type'     => 'fieldset',
                        'title'    => __('Posts'),
                        'fields'   => array_values( apply_filters( 'wp_ulike_panel_post_type_options', $get_content_fields['posts'] ) ),
                        'sanitize' => 'wp_ulike_sanitize_multiple_select'
                    ),
                    // Comments
                    array(
                        'id'     => 'comments_group',
                        'type'   => 'fieldset',
                        'title'  => __('Comments'),
                        'fields' => array_values( apply_filters( 'wp_ulike_panel_comment_type_options', $get_content_fields['comments'] ) )
                    ),
                    // BuddyPress
                    array(
                        'id'     => 'buddypress_group',
                        'type'   => 'fieldset',
                        'title'  => __('BuddyPress'),
                        'fields' => $buddypress_options
                    ),
                    // Posts
                    array(
                        'id'     => 'bbpress_group',
                        'type'   => 'fieldset',
                        'title'  => __('bbPress'),
                        'fields' => $bbPress_options
                    )
                    // End
                )
            ) );
            // Integrations
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => __( 'Integrations',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_integrations', array(
                    array(
                        'id'    => 'enable_meta_values',
                        'type'  => 'switcher',
                        'title' => __('Enable Old Meta Values', WP_ULIKE_SLUG),
                        'desc'  => sprintf( '%s<br><strong>* %s</strong>', __('By activating this option, users who have upgraded to version +4 and deleted their old logs can add the number of old likes to the new figures.', WP_ULIKE_SLUG), __('Attention: If you have been using WP ULike +v4 from the beginning Or you haven\'t deleted any logs yet, do not enable this option.', WP_ULIKE_SLUG) )
                    ),
                   array(
                        'id'    => 'enable_deprecated_options',
                        'type'  => 'switcher',
                        'title' => __('Enable Deprecated Options', WP_ULIKE_SLUG),
                        'desc'  => sprintf( '%s<br><strong>* %s</strong>', __('By activating this option, users who have upgraded to version +4.1 and lost their old options can restore and enable previous settings.', WP_ULIKE_SLUG), __('Attention: If you have been using WP ULike +v4.1 from the beginning, do not enable this option.', WP_ULIKE_SLUG) )
                    )
                ) )
            ) );

            // Profiles
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => __( 'Profiles',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_profiles', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_user_profiles_banner',
                            'title'       => __( 'How to Create Ultimate User Profiles with WP ULike?', WP_ULIKE_SLUG ),
                            'description' => __( "The simplest way to create your own WordPress user profile page is by using the WP ULike Profile builder. This way, you can create professional profiles and display it on the front-end of your website without the need for coding knowledge or the use of advanced functions." , WP_ULIKE_SLUG ),
                            'skin'        => 'info',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'info',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ultimate-profile-builder/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '200',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/profiles.svg'
                            )
                        )
                    )
                ) )
            ) );

            // Profiles
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => __( 'Login & Signup',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_forms', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_user_login_register_banner',
                            'title'       => __( 'How to make AJAX Based Login/Registration system?', WP_ULIKE_SLUG ),
                            'description' => __( "Transform your default WordPress login, registration, and reset password forms with the new WP ULike Pro features. In this section, we provide you with tools that you can use to make modern & ajax based forms on your pages with just a few simple clicks." , WP_ULIKE_SLUG ),
                            'skin'        => 'info',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'info',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ajax-login-registration-plugin/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '200',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/login.svg'
                            )
                        )
                    )
                ) )
            ) );


            /**
             * Customization Section
             */
            ULF::createSection( $this->option_domain, array(
                'id'    => 'translations',
                'title' => __( 'Translations',WP_ULIKE_SLUG),
                'icon'  => 'fa fa-language',
            ) );


            /**
             * Translations Section
             */
            ULF::createSection( $this->option_domain, array(
                'title'  => __( 'Strings',WP_ULIKE_SLUG),
                'parent' => 'translations',
                'fields' => apply_filters( 'wp_ulike_panel_translations', array(
                    array(
                        'id'      => 'already_registered_notice',
                        'type'    => 'text',
                        'default' => __( 'You have already registered a vote.',WP_ULIKE_SLUG),
                        'title'   => __( 'Already Voted Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'login_required_notice',
                        'type'    => 'text',
                        'default' => __( 'You Should Login To Submit Your Like',WP_ULIKE_SLUG),
                        'title'   => __( 'Login Required Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'like_notice',
                        'type'    => 'text',
                        'default' => __('Thanks! You Liked This.',WP_ULIKE_SLUG),
                        'title'   => __( 'Liked Notice Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'unlike_notice',
                        'type'    => 'text',
                        'default' => __('Sorry! You unliked this.',WP_ULIKE_SLUG),
                        'title'   => __( 'Unliked Notice Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'like_button_aria_label',
                        'type'    => 'text',
                        'default' => __( 'Like Button',WP_ULIKE_SLUG),
                        'title'   => __( 'Like Button Aria Label', WP_ULIKE_SLUG)
                    )
                ) )
            ) );

            /**
             * Customization Section
             */
            ULF::createSection( $this->option_domain, array(
                'id'    => 'customization',
                'title' => __( 'Developer Tools',WP_ULIKE_SLUG),
                'icon'  => 'fa fa-code',
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => 'customization',
                'title'  => __( 'Custom Style',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_customization', array(
                    array(
                        'id'    => 'custom_css',
                        'type'  => 'code_editor',
                        'settings' => array(
                            'theme'  => 'mbo',
                            'mode'   => 'css',
                        ),
                        'title' => 'Custom CSS',
                    ),
                    array(
                        'id'           => 'custom_spinner',
                        'type'         => 'upload',
                        'title'        => __('Custom Spinner',WP_ULIKE_SLUG),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                    array(
                        'id'    => 'enable_inline_custom_css',
                        'type'  => 'switcher',
                        'title' => __('Enable Inline Custom CSS', WP_ULIKE_SLUG),
                        'desc'  => __('If you don\'t want to use "custom.css" file for any reason, by activating this option, the styles will be added to the page as inline.', WP_ULIKE_SLUG)
                    )
                ) )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => 'customization',
                'title'  => __( 'REST API',WP_ULIKE_SLUG ),
                'fields' => apply_filters( 'wp_ulike_panel_rest_api', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_rest_api_banner',
                            'title'       => __( 'How to Get Started with WP ULike REST API?', WP_ULIKE_SLUG ),
                            'description' => __( "Have you ever tried to get data from online sources like WP ULike logs and use them in your Application or website? the solution is Rest API!" , WP_ULIKE_SLUG ),
                            'skin'        => 'info',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'info',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/how-to-get-started-with-wp-ulike-rest-api/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '200',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/api.svg'
                            )
                        )
                    )
                ) )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => 'customization',
                'title'  => __( 'Optimization',WP_ULIKE_SLUG ),
                'fields' => apply_filters( 'wp_ulike_panel_optimization', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_optimization_banner',
                            'title'       => __( 'How to Optimize or Repair WP ULike Database Tables?', WP_ULIKE_SLUG ),
                            'description' => __( "Have you ever optimized your WP ULike database? Optimizing your database cleans up unwanted data which reduces database size and improves performance." , WP_ULIKE_SLUG ),
                            'skin'        => 'info',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => __( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'info',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/database-optimization/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '200',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/database.svg'
                            )
                        )
                    )
                ) )
            ) );

            do_action( 'wp_ulike_panel_sections_ended' );
        }

        /**
         * Generate general content options
         *
         * @return void
         */
        public function get_content_options(){
            return array(
                'template' => array(
                    'id'      => 'template',
                    'type'    => 'image_select',
                    'title'   => __( 'Select a Template',WP_ULIKE_SLUG),
                    'desc'    => sprintf( '%s <a target="_blank" href="%s" title="Click">%s</a>', __( 'Display online preview',WP_ULIKE_SLUG),  WP_ULIKE_PLUGIN_URI . 'templates/?utm_source=settings-page&utm_campaign=plugin-uri&utm_medium=wp-dash',__( 'Here',WP_ULIKE_SLUG) ),
                    'options' => $this->get_templates_option_array(),
                    'default' => 'wpulike-default',
                    'class'   => 'wp-ulike-visual-select',
                ),
                'button_type' => array(
                    'id'         => 'button_type',
                    'type'       => 'button_set',
                    'title'      => __( 'Button Type', WP_ULIKE_SLUG),
                    'default'    => 'image',
                    'options'    => array(
                        'image' => __('Image', WP_ULIKE_SLUG),
                        'text'  => __('Text', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'template', 'any', 'wpulike-default,wp-ulike-pro-default,wpulike-heart' ),
                ),
                'text_group' => array(
                    'id'            => 'text_group',
                    'type'          => 'tabbed',
                    'desc'          => __( 'Enter your custom button text in the fields above. You can also use HTML tags in these fields.', WP_ULIKE_SLUG),
                    'title'         => __( 'Button Text', WP_ULIKE_SLUG),
                    'tabs'          => array(
                        array(
                            'title'     => __('Like',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'      => 'like',
                                    'type'    => 'text',
                                    'title'   => __('Button Text',WP_ULIKE_SLUG),
                                    'default' => 'Like'
                                ),
                            )
                        ),
                        array(
                            'title'     => __('Unlike',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'      => 'unlike',
                                    'type'    => 'text',
                                    'title'   => __('Button Text',WP_ULIKE_SLUG),
                                    'default' => 'Liked'
                                ),
                            )
                        ),
                    ),
                    'dependency' => array( 'button_type|template', 'any|any', 'text|wpulike-default,wp-ulike-pro-default,wpulike-heart' ),
                ),
                'image_group' => array(
                    'id'            => 'image_group',
                    'type'          => 'tabbed',
                    'title'         => __( 'Button Image', WP_ULIKE_SLUG),
                    'tabs'          => array(
                        array(
                            'title'     => __('Like',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'           => 'like',
                                    'type'         => 'upload',
                                    'title'        => __('Button Image',WP_ULIKE_SLUG),
                                    'library'      => 'image',
                                    'placeholder'  => 'http://'
                                ),
                            )
                        ),
                        array(
                            'title'     => __('Unlike',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'           => 'unlike',
                                    'type'         => 'upload',
                                    'title'        => __('Button Image',WP_ULIKE_SLUG),
                                    'library'      => 'image',
                                    'placeholder'  => 'http://'
                                ),
                            )
                        ),
                    ),
                    'dependency' => array( 'button_type|template', 'any|any', 'image|wpulike-default,wp-ulike-pro-default,wpulike-heart' ),
                ),
                'enable_auto_display' => array(
                    'id'      => 'enable_auto_display',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => __('Automatic display', WP_ULIKE_SLUG),
                ),
                'auto_display_position' => array(
                    'id'      => 'auto_display_position',
                    'type'    => 'radio',
                    'title'   => __( 'Button Position',WP_ULIKE_SLUG ),
                    'default' => 'bottom',
                    'options' => array(
                        'top'        => __('Top of Content', WP_ULIKE_SLUG),
                        'bottom'     => __('Bottom of Content', WP_ULIKE_SLUG),
                        'top_bottom' => __('Top and Bottom', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter' => array(
                    'id'          => 'auto_display_filter',
                    'type'        => 'select',
                    'title'       => __( 'Automatic Display Restriction',WP_ULIKE_SLUG ),
                    'desc'        => __('With this option, you can disable automatic display on these pages.', WP_ULIKE_SLUG),
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'single', 'home' ),
                    'options'     => array(
                        'home'     => __('Home', WP_ULIKE_SLUG),
                        'single'   => __('Singular', WP_ULIKE_SLUG),
                        'archive'  => __('Archives', WP_ULIKE_SLUG),
                        'category' => __('Categories', WP_ULIKE_SLUG),
                        'search'   => __('Search Results', WP_ULIKE_SLUG),
                        'tag'      => __('Tags', WP_ULIKE_SLUG),
                        'author'   => __('Author Page', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter_post_types' => array(
                    'id'          => 'auto_display_filter_post_types',
                    'type'        => 'select',
                    'title'       => __( 'Post Types Filter',WP_ULIKE_SLUG ),
                    'placeholder' => __( 'Select a post type',WP_ULIKE_SLUG ),
                    'desc'        => __( 'Make these post types an exception and display the button on them.',WP_ULIKE_SLUG ),
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => 'post',
                    'options'     => 'post_types',
                    'dependency'  => array( 'auto_display_filter|enable_auto_display', 'any|==', 'single|true' ),
                ),
                'counter_display_condition' => array(
                    'id'         => 'counter_display_condition',
                    'type'       => 'button_set',
                    'title'      => __( 'Display Counter Value Condition', WP_ULIKE_SLUG),
                    'default'    => 'visible',
                    'options'    => array(
                        'visible'         => __('Visible', WP_ULIKE_SLUG),
                        'hidden'          => __('Hidden', WP_ULIKE_SLUG),
                        'logged_in_users' => __('Only Logged In Users', WP_ULIKE_SLUG)
                    )
                ),
                'hide_zero_counter' => array(
                    'id'         => 'hide_zero_counter',
                    'type'       => 'switcher',
                    'title'      => __('Hide Zero Counter Box', WP_ULIKE_SLUG),
                    'dependency' => array( 'counter_display_condition', '!=', 'hidden' )
                ),
                'logging_method' => array(
                    'id'          => 'logging_method',
                    'type'        => 'select',
                    'title'       => __( 'Logging Method',WP_ULIKE_SLUG),
                    'options'     => array(
                        'do_not_log'        => __('No Limit', WP_ULIKE_SLUG),
                        'by_cookie'         => __('Cookie', WP_ULIKE_SLUG),
                        'by_username'       => __('Username/IP', WP_ULIKE_SLUG),
                        'by_user_ip_cookie' => __('Username/IP + Cookie', WP_ULIKE_SLUG)
                    ),
                    'default'     => 'by_username',
                    'help'        => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', __( '"No Limit": There will be no restrictions and users can submit their points each time they refresh the page. In this option, it will not be possible to resubmit reverse points (un-like/un-dislike).', WP_ULIKE_SLUG ), __( '"Cookie": By saving users\' cookies, it is possible to submit points only once per user and in case of re-clicking, the appropriate message will be displayed.', WP_ULIKE_SLUG ), __( 'Username/IP: By saving the username/IP of users, It supports the reverse feature  (un-like and un-dislike) and users can change their reactions and are only allowed to have a specific point type.', WP_ULIKE_SLUG ) )
                ),
                'enable_only_logged_in_users' => array(
                    'id'    => 'enable_only_logged_in_users',
                    'type'  => 'switcher',
                    'title' => __('Only logged in users', WP_ULIKE_SLUG),
                ),
                'logged_out_display_type' => array(
                    'id'         => 'logged_out_display_type',
                    'type'       => 'button_set',
                    'title'      => __( 'Display Type', WP_ULIKE_SLUG),
                    'options'    => array(
                        'alert'  => __('Template', WP_ULIKE_SLUG),
                        'button' => __('Button', WP_ULIKE_SLUG)
                    ),
                    'default'    => 'button',
                    'dependency' => array( 'enable_only_logged_in_users', '==', 'true' ),
                ),
                'login_template' => array(
                    'id'       => 'login_template',
                    'type'     => 'code_editor',
                    'desc'     => __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%CURRENT_PAGE_URL%</code>',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>',
                        __('You need to login in order to like this post: ',WP_ULIKE_SLUG),
                        wp_login_url(),
                        __('click here',WP_ULIKE_SLUG)
                    ),
                    'title'    => __('Custom HTML Template', WP_ULIKE_SLUG),
                    'dependency'=> array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'alert|true' ),
                ),
                'enable_likers_box' => array(
                    'id'    => 'enable_likers_box',
                    'type'  => 'switcher',
                    'title' => __('Display Likers Box', WP_ULIKE_SLUG),
                ),
                'likers_order' => array(
                    'id'         => 'likers_order',
                    'type'       => 'button_set',
                    'title'      => __( 'User List Arrange', WP_ULIKE_SLUG),
                    'default'    => 'desc',
                    'options'    => array(
                        'asc'  => __('Ascending', WP_ULIKE_SLUG),
                        'desc' => __('Descending', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'hide_likers_for_anonymous_users' => array(
                    'id'    => 'hide_likers_for_anonymous_users',
                    'type'  => 'switcher',
                    'default' => false,
                    'title' => __('Hide For Anonymous Users', WP_ULIKE_SLUG),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_style' => array(
                    'id'         => 'likers_style',
                    'type'       => 'button_set',
                    'title'      => __( 'Likers Box Display', WP_ULIKE_SLUG),
                    'default'    => 'popover',
                    'options'    => array(
                        'default' => __('Inline', WP_ULIKE_SLUG),
                        'popover' => __('Popover', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_template' => array(
                    'id'       => 'likers_template',
                    'type'     => 'code_editor',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>',
                    'title'    => __('Custom HTML Template', WP_ULIKE_SLUG),
                    'desc'     => __('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>',
                    'dependency'=> array( 'enable_likers_box|likers_style', '==|any', 'true|default,popover'  ),
                ),
                // 'disable_likers_pophover' => array(
                //     'id'         => 'disable_likers_pophover',
                //     'type'       => 'switcher',
                //     'title'      => __('Disable Pophover', WP_ULIKE_SLUG),
                //     'dependency' => array( 'enable_likers_box', '==', 'true' ),
                //     'desc'       => __('Active this option to show liked users avatars in the bottom of button like.', WP_ULIKE_SLUG)
                // ),
                'likers_gravatar_size' => array(
                    'id'         => 'likers_gravatar_size',
                    'type'       => 'number',
                    'title'      => __( 'Size of Gravatars', WP_ULIKE_SLUG),
                    'default'    => 64,
                    'unit'       => 'px',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_count' => array(
                    'id'         => 'likers_count',
                    'type'       => 'number',
                    'title'      => __( 'Likers Count', WP_ULIKE_SLUG),
                    'desc'       => __('The number of users to show in the users liked box', WP_ULIKE_SLUG),
                    'default'    => 10,
                    'unit'       => 'users',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                )
            );
        }

        /**
         * Get templates option array
         *
         * @return array
         */
        public function get_templates_option_array(){
            $options = wp_ulike_generate_templates_list();
            $output  = array();

            if( !empty( $options ) ){
                foreach ($options as $key => $args) {
                    $output[$key] = $args['symbol'];
                }
            }

            return $output;
        }

    }
}