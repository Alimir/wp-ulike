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
                'sub_menu_title'     => esc_html__( 'Settings', WP_ULIKE_SLUG ),
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
                    esc_html__( 'Proudly Powered By', WP_ULIKE_SLUG ),
                    'https://technowich.com/?utm_source=footer-link&utm_campaign=wp-ulike&utm_medium=wp-dash',
                    esc_html__( 'TechnoWich', WP_ULIKE_SLUG )
                ),
                'footer_after'       => '',
                'footer_text'        => sprintf(
                    '<a href="%s" title="Documents" target="_blank">%s</a>',
                    'https://docs.wpulike.com/category/8-settings/',
                    esc_html__( 'Explore Settings', WP_ULIKE_SLUG )
                ),
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
                'title' => esc_html__( 'Configuration',WP_ULIKE_SLUG),
                'icon'  => 'fa fa-home',
            ) );
            // General
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'General',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_general', array(
                    array(
                        'id'      => 'enable_kilobyte_format',
                        'type'    => 'switcher',
                        'title'   => esc_html__('Enable Convertor', WP_ULIKE_SLUG),
                        'default' => false,
                        'desc'    => esc_html__('Convert numbers of Likes with string (kilobyte) format.', WP_ULIKE_SLUG) . '<strong> (WHEN? likes>=1000)</strong>'
                    ),
                    array(
                        'id'            => 'filter_counter_value',
                        'type'          => 'tabbed',
                        'desc'          => esc_html__( 'Set your custom prefix/postfix on counter value', WP_ULIKE_SLUG),
                        'title'         => esc_html__( 'Filter Counter Value', WP_ULIKE_SLUG),
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__('Prefix',WP_ULIKE_SLUG),
                                'fields'    => apply_filters( 'wp_ulike_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_prefix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Like',WP_ULIKE_SLUG),
                                        'default' => '+'
                                    ),
                                    array(
                                        'id'      => 'unlike_prefix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Unlike',WP_ULIKE_SLUG),
                                        'default' => '+'
                                    ),
                                ), 'prefix' )
                            ),
                            array(
                                'title'     => esc_html__('Suffix',WP_ULIKE_SLUG),
                                'fields'    => apply_filters( 'wp_ulike_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_postfix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Like',WP_ULIKE_SLUG)
                                    ),
                                    array(
                                        'id'      => 'unlike_postfix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Unlike',WP_ULIKE_SLUG)
                                    ),
                                ), 'postfix' )
                            ),
                        )
                    ),
                    array(
                        'id'      => 'enable_toast_notice',
                        'type'    => 'switcher',
                        'title'   => esc_html__('Enable Notifications', WP_ULIKE_SLUG),
                        'default' => true,
                        'desc'    => esc_html__('Custom toast messages after each activity', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'          => 'filter_toast_types',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Disable Toast Types',WP_ULIKE_SLUG ),
                        'desc'        => esc_html__('With this option, you can disable toasts messages on content types.', WP_ULIKE_SLUG),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'post'     => esc_html__('Posts', WP_ULIKE_SLUG),
                            'comment'  => esc_html__('Comments', WP_ULIKE_SLUG),
                            'activity' => esc_html__('Activities', WP_ULIKE_SLUG),
                            'topic'    => esc_html__('Topics', WP_ULIKE_SLUG)
                        ),
                        'dependency'=> array( 'enable_toast_notice', '==', 'true' ),
                    ),
                    array(
                        'id'    => 'enable_anonymise_ip',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Anonymize IP', WP_ULIKE_SLUG),
                        'desc'  => esc_html__('IP anonymization, also known as IP masking, is a method of replacing the original IP address with one that cannot be associated with or traced back to an individual user. This can be done by setting the last octet of IPV4 addresses or the last 80 bits of IPv6 addresses to zeros.', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'         => 'disable_ip_logging',
                        'type'       => 'switcher',
                        'title'      => esc_html__('Disable IP logging', WP_ULIKE_SLUG),
                        'desc'       => esc_html__('Use this option to disable saving user IP in your database.', WP_ULIKE_SLUG),
                        'dependency' => array( 'enable_anonymise_ip', '==', 'true' ),
                    ),
                    array(
                        'id'    => 'cache_exist',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Cache Exist', WP_ULIKE_SLUG),
                        'desc'  => esc_html__('Please enable this option, If you have any cache service on your website.', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'    => 'disable_admin_notice',
                        'type'  => 'switcher',
                        'title' => esc_html__('Hide Admin Notices', WP_ULIKE_SLUG),
                        'desc'  => esc_html__('Enabling this option will completely disable all admin notices.', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'          => 'disable_plugin_files',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Disable Plugin Files',WP_ULIKE_SLUG ),
                        'desc'        => esc_html__('With this option, you can disable all plugin assets on these pages.', WP_ULIKE_SLUG),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'home'        => esc_html__('Home', WP_ULIKE_SLUG),
                            'single'      => esc_html__('Singular', WP_ULIKE_SLUG),
                            'archive'     => esc_html__('Archives', WP_ULIKE_SLUG),
                            'category'    => esc_html__('Categories', WP_ULIKE_SLUG),
                            'search'      => esc_html__('Search Results', WP_ULIKE_SLUG),
                            'tag'         => esc_html__('Tags', WP_ULIKE_SLUG),
                            'author'      => esc_html__('Author Page', WP_ULIKE_SLUG),
                            'buddypress'  => esc_html__('BuddyPress Pages', WP_ULIKE_SLUG),
                            'bbpress'     => esc_html__('bbPress Pages', WP_ULIKE_SLUG),
                            'woocommerce' => esc_html__('WooCommerce Pages', WP_ULIKE_SLUG)
                        )
                    ),
                    array(
                        'id'          => 'enable_admin_posts_columns',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Enable Admin Columns',WP_ULIKE_SLUG ),
                        'desc'        => esc_html__('Add counter stats column to the selected post types', WP_ULIKE_SLUG),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => 'post_types'
                    ),
                    array(
                        'id'          => 'blacklist_integration',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Blacklist',WP_ULIKE_SLUG),
                        'options'     => array(
                            'default'  => esc_html__('Use the WP Ulike Blacklist', WP_ULIKE_SLUG),
                            'comments' => esc_html__('Use the WordPress Disallowed Comment Keys', WP_ULIKE_SLUG)
                        ),
                        'default'     => 'default',
                        'desc'        => sprintf(esc_html__('Choose which Blacklist you would prefer to use for voting buttons. The %s option can be found in the WordPress Discussion Settings page.', WP_ULIKE_SLUG),
                            '<a href="'.admin_url('options-discussion.php').'">'.esc_html__('Disallowed Comment Keys', WP_ULIKE_SLUG).'</a>'
                        )
                    ),
                    array(
                        'id'         => 'blacklist_entries',
                        'type'       => 'textarea',
                        'title'      => esc_html__( 'Blacklist Entries', WP_ULIKE_SLUG),
                        'desc'       => esc_html__('One IP address per line. When a vote contains any of these entries in its IP address, it will be rejected.', WP_ULIKE_SLUG),
                        'dependency' => array( 'blacklist_integration', 'any', 'default' )
                    )
                ) )
            ) );

            // Get all content options
            $get_content_options = apply_filters( 'wp_ulike_panel_content_options', $this->get_content_options() );
            $get_content_fields  = array();

            // Generate posts fields
            $get_content_fields['posts']    = $get_content_options;

            if( wp_ulike_is_wpml_active() ){
                $get_content_fields['posts']['enable_wpml_synchronization'] = array(
                    'id'    => 'enable_wpml_synchronization',
                    'type'  => 'switcher',
                    'title' => esc_html__('Enable WPML Synchronization', WP_ULIKE_SLUG),
                    'desc'  => esc_html__('Synchronize likes of post types, which are translated with WPML plugin.', WP_ULIKE_SLUG)
                );
            }


            // Generate comment fields
            $get_content_fields['comments'] = $get_content_options;
            unset( $get_content_fields['comments']['auto_display_filter'] );
            unset( $get_content_fields['comments']['auto_display_filter_post_types'] );
            $get_content_fields['comments']['enable_admin_columns'] = array(
                'id'         => 'enable_admin_columns',
                'type'       => 'switcher',
                'title'      => esc_html__('Enable Admin Columns', WP_ULIKE_SLUG),
                'desc'       => esc_html__('Add counter stats column to the admin comments list.', WP_ULIKE_SLUG)
            );

            // Generate buddypress fields
            $get_content_fields['buddypress'] = $get_content_options;
            unset( $get_content_fields['buddypress']['auto_display_filter'] );
            unset( $get_content_fields['buddypress']['auto_display_filter_post_types'] );
            $get_content_fields['buddypress']['auto_display_position']['options'] = array(
                'content' => esc_html__('Activity Content', WP_ULIKE_SLUG),
                'meta'    => esc_html__('Activity Meta', WP_ULIKE_SLUG)
            );
            $get_content_fields['buddypress']['auto_display_position']['default'] = 'content';
            $get_content_fields['buddypress']['enable_comments'] = array(
                'id'         => 'enable_comments',
                'type'       => 'switcher',
                'title'      => esc_html__('Enable Activity Comment', WP_ULIKE_SLUG),
                'desc'       => esc_html__('Add the possibility to like Buddypress comments in the activity stream', WP_ULIKE_SLUG)
            );
            $get_content_fields['buddypress']['enable_add_bp_activity'] = array(
                'id'         => 'enable_add_bp_activity',
                'type'       => 'switcher',
                'title'      => esc_html__('Enable Activity Notification', WP_ULIKE_SLUG),
                'desc'       => esc_html__('Insert new likes in buddyPress activity page', WP_ULIKE_SLUG),
            );
            $get_content_fields['buddypress']['posts_notification_template'] = array(
                'id'       => 'posts_notification_template',
                'type'     => 'code_editor',
                'settings' => array(
                    'theme' => 'shadowfox',
                    'mode'  => 'htmlmixed',
                ),
                'default'  => '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
                'title'    => esc_html__('Post Activity Text', WP_ULIKE_SLUG),
                'desc'     => esc_html__('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%POST_LIKER%</code> , <code>%POST_PERMALINK%</code> , <code>%POST_COUNT%</code> , <code>%POST_TITLE%</code>',
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
                'title'    => esc_html__('Comment Activity Text', WP_ULIKE_SLUG),
                'desc'     => esc_html__('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%COMMENT_LIKER%</code> , <code>%COMMENT_AUTHOR%</code> , <code>%COMMENT_COUNT%</code>, <code>%COMMENT_PERMALINK%</code>',
                'dependency'=> array( 'enable_add_bp_activity', '==', 'true' ),
            );
            $get_content_fields['buddypress']['enable_add_notification'] = array(
                'id'         => 'enable_add_notification',
                'type'       => 'switcher',
                'title'      => esc_html__('Enable User Notification', WP_ULIKE_SLUG),
                'desc'       => esc_html__('Sends out notifications when you get a like from someone', WP_ULIKE_SLUG),
            );
            $get_content_fields['buddypress']['filter_user_notification_types'] = array(
                'id'          => 'filter_user_notification_types',
                'type'        => 'select',
                'title'       => esc_html__( 'Disable Notification Types',WP_ULIKE_SLUG ),
                'desc'        => esc_html__('With this option, you can disable user notification on content types.', WP_ULIKE_SLUG),
                'chosen'      => true,
                'multiple'    => true,
                'options'     => array(
                    'post'     => esc_html__('Posts', WP_ULIKE_SLUG),
                    'comment'  => esc_html__('Comments', WP_ULIKE_SLUG),
                    'activity' => esc_html__('Activities', WP_ULIKE_SLUG),
                    'topic'    => esc_html__('Topics', WP_ULIKE_SLUG)
                ),
                'dependency'=> array( 'enable_add_notification', '==', 'true' ),
            );
            $buddypress_options = array( array(
                'type'    => 'content',
                'content' => sprintf( '<strong>%s</strong> %s', esc_html__( 'BuddyPress', WP_ULIKE_SLUG ), esc_html__( 'plugin is not installed or activated', WP_ULIKE_SLUG ) ),
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
                'content' => sprintf( '<strong>%s</strong> %s', esc_html__( 'bbPress', WP_ULIKE_SLUG ), esc_html__( 'plugin is not installed or activated', WP_ULIKE_SLUG ) ),
            ) );
            if( function_exists('is_bbpress') ){
                $bbPress_options = array_values( apply_filters( 'wp_ulike_panel_bbpress_type_options', $get_content_fields['bbpress'] ) );
            }

            // Content Groups
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'Content Types',WP_ULIKE_SLUG),
                'fields' => array(
                    array(
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => 'In this section, you have access to the 4 types of contents in WordPress and you can specify your config for each of them:<br><br>
                        <strong>Posts (Including all standard and custom post types + WooCommerce products)</strong><br>
                        <strong>Comments (Including comments for all post types)</strong><br>
                        <strong>BuddyPress (Including BuddyPress activities & comments with supporting of user notifications)</strong><br>
                        <strong>bbPress (Including bbPress topics & replies)</strong><br><br>
                        ' . sprintf(
                            '<a href="%s" title="Documents" target="_blank">%s</a>',
                            'https://docs.wpulike.com/article/14-content-types-settings',
                            esc_html__( 'Read More', WP_ULIKE_SLUG )
                        ),
                    ),
                    // Posts
                    array(
                        'id'       => 'posts_group',
                        'type'     => 'fieldset',
                        'title'    => esc_html__('Posts'),
                        'fields'   => array_values( apply_filters( 'wp_ulike_panel_post_type_options', $get_content_fields['posts'] ) ),
                        'sanitize' => 'wp_ulike_sanitize_multiple_select'
                    ),
                    // Comments
                    array(
                        'id'     => 'comments_group',
                        'type'   => 'fieldset',
                        'title'  => esc_html__('Comments'),
                        'fields' => array_values( apply_filters( 'wp_ulike_panel_comment_type_options', $get_content_fields['comments'] ) )
                    ),
                    // BuddyPress
                    array(
                        'id'     => 'buddypress_group',
                        'type'   => 'fieldset',
                        'title'  => esc_html__('BuddyPress'),
                        'fields' => $buddypress_options
                    ),
                    // Posts
                    array(
                        'id'     => 'bbpress_group',
                        'type'   => 'fieldset',
                        'title'  => esc_html__('bbPress'),
                        'fields' => $bbPress_options
                    )
                    // End
                )
            ) );
            // Integrations
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'Integrations',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_integrations', array(
                    array(
                        'id'    => 'enable_meta_values',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Old Meta Values', WP_ULIKE_SLUG),
                        'desc'  => sprintf( '%s<br><strong>* %s</strong>', esc_html__('By activating this option, users who have upgraded to version +4 and deleted their old logs can add the number of old likes to the new figures.', WP_ULIKE_SLUG), esc_html__('Attention: If you have been using WP ULike +v4 from the beginning Or you haven\'t deleted any logs yet, do not enable this option.', WP_ULIKE_SLUG) )
                    ),
                   array(
                        'id'    => 'enable_deprecated_options',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Deprecated Options', WP_ULIKE_SLUG),
                        'desc'  => sprintf( '%s<br><strong>* %s</strong>', esc_html__('By activating this option, users who have upgraded to version +4.1 and lost their old options can restore and enable previous settings.', WP_ULIKE_SLUG), esc_html__('Attention: If you have been using WP ULike +v4.1 from the beginning, do not enable this option.', WP_ULIKE_SLUG) )
                    )
                ) )
            ) );

            // Profiles
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'Profiles',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_profiles', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_ultimate_user_profiles_banner',
                            'title'       => esc_html__( 'How to Create Ultimate User Profiles with WP ULike?', WP_ULIKE_SLUG ),
                            'description' => esc_html__( "The simplest way to create your own WordPress user profile page is by using the WP ULike Profile builder. This way, you can create professional profiles and display it on the front-end of your website without the need for coding knowledge or the use of advanced functions." , WP_ULIKE_SLUG ),
                            'skin'        => 'default',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => esc_html__( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'default',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ultimate-profile-builder/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '120',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/profiles.svg'
                            )
                        )
                    )
                ) )
            ) );

            // Login & Signup
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'Login & Signup',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_forms', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_user_login_register_banner',
                            'title'       => esc_html__( 'How to make AJAX Based Login/Registration system?', WP_ULIKE_SLUG ),
                            'description' => esc_html__( "Transform your default WordPress login, registration, and reset password forms with the new WP ULike Pro features. In this section, we provide you with tools that you can use to make modern & ajax based forms on your pages with just a few simple clicks." , WP_ULIKE_SLUG ),
                            'skin'        => 'default',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => esc_html__( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'default',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ajax-login-registration-plugin/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '120',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/login.svg'
                            )
                        )
                    )
                ) )
            ) );

            // Social login integration
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'Social Logins',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_social_logins', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_social_logins_banner',
                            'title'       => esc_html__( 'Social Logins Integration with Your WordPress Site', WP_ULIKE_SLUG ),
                            'description' => esc_html__( "In the fast-evolving world of website development, staying ahead is crucial. WP ULike Pro's latest update introduces a potent social logins integration that's set to redefine user interactions. With robust support for Google, Facebook, GitHub, and more, this plugin offers unparalleled convenience. In this article, we delve into the groundbreaking features that are reshaping WordPress user experiences." , WP_ULIKE_SLUG ),
                            'skin'        => 'default',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => esc_html__( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'default',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/social-login/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '120',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/social.svg'
                            )
                        )
                    )
                ) )
            ) );


            // Share buttons
            ULF::createSection( $this->option_domain, array(
                'parent' => 'configuration',
                'title'  => esc_html__( 'Share Buttons',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_share_buttons', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_share_buttons_banner',
                            'title'       => esc_html__( 'Easy Social Share Buttons for WordPress', WP_ULIKE_SLUG ),
                            'description' => esc_html__( "WP ULike Share buttons enables your website users to share the content over Facebook, Twitter, Google, Linkedin, Whatsapp, Tumblr, Pinterest, Reddit and over 23 more social sharing services. This is the Simplest and Smoothest Social Sharing service with optimized and great looking vector icons." , WP_ULIKE_SLUG ),
                            'skin'        => 'default',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => esc_html__( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'default',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ultimate-social-share-buttons/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '120',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/share.svg'
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
                'title' => esc_html__( 'Translations',WP_ULIKE_SLUG),
                'icon'  => 'fa fa-language',
            ) );


            /**
             * Translations Section
             */
            ULF::createSection( $this->option_domain, array(
                'title'  => esc_html__( 'Strings',WP_ULIKE_SLUG),
                'parent' => 'translations',
                'fields' => apply_filters( 'wp_ulike_panel_translations', array(
                    array(
                        'id'      => 'validate_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'Your vote cannot be submitted at this time.',WP_ULIKE_SLUG),
                        'title'   => esc_html__( 'Validation Notice Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'already_registered_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'You have already registered a vote.',WP_ULIKE_SLUG),
                        'title'   => esc_html__( 'Already Voted Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'login_required_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'You Should Login To Submit Your Like',WP_ULIKE_SLUG),
                        'title'   => esc_html__( 'Login Required Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'like_notice',
                        'type'    => 'text',
                        'default' => esc_html__('Thanks! You Liked This.',WP_ULIKE_SLUG),
                        'title'   => esc_html__( 'Liked Notice Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'unlike_notice',
                        'type'    => 'text',
                        'default' => esc_html__('Sorry! You unliked this.',WP_ULIKE_SLUG),
                        'title'   => esc_html__( 'Unliked Notice Message', WP_ULIKE_SLUG)
                    ),
                    array(
                        'id'      => 'like_button_aria_label',
                        'type'    => 'text',
                        'default' => esc_html__( 'Like Button',WP_ULIKE_SLUG),
                        'title'   => esc_html__( 'Like Button Aria Label', WP_ULIKE_SLUG)
                    )
                ) )
            ) );

            /**
             * Customization Section
             */
            ULF::createSection( $this->option_domain, array(
                'id'    => 'customization',
                'title' => esc_html__( 'Developer Tools',WP_ULIKE_SLUG),
                'icon'  => 'fa fa-code',
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => 'customization',
                'title'  => esc_html__( 'Scripts',WP_ULIKE_SLUG),
                'fields' => apply_filters( 'wp_ulike_panel_customization', array(
                    array(
                        'id'    => 'custom_css',
                        'type'  => 'code_editor',
                        'settings' => array(
                            'theme'  => 'mbo',
                            'mode'   => 'css',
                        ),
                        'title' => esc_html__('Custom CSS',WP_ULIKE_SLUG),
                    ),
                    array(
                        'id'           => 'custom_spinner',
                        'type'         => 'upload',
                        'title'        => esc_html__('Custom Spinner',WP_ULIKE_SLUG),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                    array(
                        'id'    => 'enable_inline_custom_css',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Inline Custom CSS', WP_ULIKE_SLUG),
                        'desc'  => esc_html__('If you don\'t want to use "custom.css" file for any reason, by activating this option, the styles will be added to the page as inline.', WP_ULIKE_SLUG)
                    )
                ) )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => 'customization',
                'title'  => esc_html__( 'REST API',WP_ULIKE_SLUG ),
                'fields' => apply_filters( 'wp_ulike_panel_rest_api', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_rest_api_banner',
                            'title'       => esc_html__( 'How to Get Started with WP ULike REST API?', WP_ULIKE_SLUG ),
                            'description' => esc_html__( "Have you ever tried to get data from online sources like WP ULike logs and use them in your Application or website? the solution is Rest API!" , WP_ULIKE_SLUG ),
                            'skin'        => 'default',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => esc_html__( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'default',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/how-to-get-started-with-wp-ulike-rest-api/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '120',
                                'src'   => WP_ULIKE_ASSETS_URL . '/img/svg/api.svg'
                            )
                        )
                    )
                ) )
            ) );

            ULF::createSection( $this->option_domain, array(
                'parent' => 'customization',
                'title'  => esc_html__( 'Optimization',WP_ULIKE_SLUG ),
                'fields' => apply_filters( 'wp_ulike_panel_optimization', array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_get_notice_render',
                        'args'     => array(
                            'id'          => 'wp_ulike_pro_optimization_banner',
                            'title'       => esc_html__( 'How to Optimize or Repair WP ULike Database Tables?', WP_ULIKE_SLUG ),
                            'description' => esc_html__( "Have you ever optimized your WP ULike database? Optimizing your database cleans up unwanted data which reduces database size and improves performance." , WP_ULIKE_SLUG ),
                            'skin'        => 'default',
                            'has_close'   => false,
                            'buttons'     => array(
                                array(
                                    'label'      => esc_html__( "Get More Information", WP_ULIKE_SLUG ),
                                    'color_name' => 'default',
                                    'link'       => WP_ULIKE_PLUGIN_URI . 'blog/database-optimization/?utm_source=settings-page-banner&utm_campaign=gopro&utm_medium=wp-dash'
                                )
                            ),
                            'image'     => array(
                                'width' => '120',
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
                    'title'   => esc_html__( 'Select a Template',WP_ULIKE_SLUG),
                    'desc'    => sprintf( '%s <a target="_blank" href="%s" title="Click">%s</a>', esc_html__( 'Display online preview',WP_ULIKE_SLUG),  WP_ULIKE_PLUGIN_URI . 'templates/?utm_source=settings-page&utm_campaign=plugin-uri&utm_medium=wp-dash',esc_html__( 'Here',WP_ULIKE_SLUG) ),
                    'options' => $this->get_templates_option_array(),
                    'default' => 'wpulike-default',
                    'class'   => 'wp-ulike-visual-select',
                ),
                'button_type' => array(
                    'id'         => 'button_type',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Button Type', WP_ULIKE_SLUG),
                    'default'    => 'image',
                    'options'    => array(
                        'image' => esc_html__('Image', WP_ULIKE_SLUG),
                        'text'  => esc_html__('Text', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'template', 'any', 'wpulike-default,wp-ulike-pro-default,wpulike-heart' ),
                ),
                'text_group' => array(
                    'id'            => 'text_group',
                    'type'          => 'tabbed',
                    'desc'          => esc_html__( 'Enter your custom button text in the fields above. You can also use HTML tags in these fields.', WP_ULIKE_SLUG),
                    'title'         => esc_html__( 'Button Text', WP_ULIKE_SLUG),
                    'tabs'          => array(
                        array(
                            'title'     => esc_html__('Like',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'      => 'like',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Button Text',WP_ULIKE_SLUG),
                                    'default' => 'Like'
                                ),
                            )
                        ),
                        array(
                            'title'     => esc_html__('Unlike',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'      => 'unlike',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Button Text',WP_ULIKE_SLUG),
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
                    'title'         => esc_html__( 'Button Image', WP_ULIKE_SLUG),
                    'tabs'          => array(
                        array(
                            'title'     => esc_html__('Like',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'           => 'like',
                                    'type'         => 'upload',
                                    'title'        => esc_html__('Button Image',WP_ULIKE_SLUG),
                                    'library'      => 'image',
                                    'placeholder'  => 'http://'
                                ),
                            )
                        ),
                        array(
                            'title'     => esc_html__('Unlike',WP_ULIKE_SLUG),
                            'fields'    => array(
                                array(
                                    'id'           => 'unlike',
                                    'type'         => 'upload',
                                    'title'        => esc_html__('Button Image',WP_ULIKE_SLUG),
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
                    'title'   => esc_html__('Automatic display', WP_ULIKE_SLUG),
                ),
                'auto_display_position' => array(
                    'id'      => 'auto_display_position',
                    'type'    => 'radio',
                    'title'   => esc_html__( 'Button Position',WP_ULIKE_SLUG ),
                    'default' => 'bottom',
                    'options' => array(
                        'top'        => esc_html__('Top of Content', WP_ULIKE_SLUG),
                        'bottom'     => esc_html__('Bottom of Content', WP_ULIKE_SLUG),
                        'top_bottom' => esc_html__('Top and Bottom', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter' => array(
                    'id'          => 'auto_display_filter',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Automatic Display Restriction',WP_ULIKE_SLUG ),
                    'desc'        => esc_html__('With this option, you can disable automatic display on these pages.', WP_ULIKE_SLUG),
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'single', 'home' ),
                    'options'     => array(
                        'home'     => esc_html__('Home', WP_ULIKE_SLUG),
                        'single'   => esc_html__('Singular', WP_ULIKE_SLUG),
                        'archive'  => esc_html__('Archives', WP_ULIKE_SLUG),
                        'category' => esc_html__('Categories', WP_ULIKE_SLUG),
                        'search'   => esc_html__('Search Results', WP_ULIKE_SLUG),
                        'tag'      => esc_html__('Tags', WP_ULIKE_SLUG),
                        'author'   => esc_html__('Author Page', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter_post_types' => array(
                    'id'          => 'auto_display_filter_post_types',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Post Types Filter',WP_ULIKE_SLUG ),
                    'placeholder' => esc_html__( 'Select a post type',WP_ULIKE_SLUG ),
                    'desc'        => esc_html__( 'Make these post types an exception and display the button on them.',WP_ULIKE_SLUG ),
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'post' ),
                    'options'     => 'post_types',
                    'dependency'  => array( 'auto_display_filter|enable_auto_display', 'any|==', 'single|true' ),
                ),
                'counter_display_condition' => array(
                    'id'         => 'counter_display_condition',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Display Counter Value Condition', WP_ULIKE_SLUG),
                    'default'    => 'visible',
                    'options'    => array(
                        'visible'         => esc_html__('Visible', WP_ULIKE_SLUG),
                        'hidden'          => esc_html__('Hidden', WP_ULIKE_SLUG),
                        'logged_in_users' => esc_html__('Only Logged In Users', WP_ULIKE_SLUG)
                    )
                ),
                'hide_zero_counter' => array(
                    'id'         => 'hide_zero_counter',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Hide Zero Counter Box', WP_ULIKE_SLUG),
                    'dependency' => array( 'counter_display_condition', '!=', 'hidden' )
                ),
                'logging_method' => array(
                    'id'          => 'logging_method',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Logging Method',WP_ULIKE_SLUG),
                    'options'     => array(
                        'do_not_log'        => esc_html__('No Limit', WP_ULIKE_SLUG),
                        'by_cookie'         => esc_html__('Cookie', WP_ULIKE_SLUG),
                        'by_username'       => esc_html__('Username/IP', WP_ULIKE_SLUG),
                        'by_user_ip_cookie' => esc_html__('Username/IP + Cookie', WP_ULIKE_SLUG)
                    ),
                    'default'     => 'by_username',
                    'help'        => sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p>', esc_html__( '"No Limit": There will be no restrictions and users can submit their points each time they refresh the page. In this option, it will not be possible to resubmit reverse points (un-like/un-dislike).', WP_ULIKE_SLUG ), esc_html__( '"Cookie": By saving users\' cookies, it is possible to submit points only once per user and in case of re-clicking, the appropriate message will be displayed.', WP_ULIKE_SLUG ), esc_html__( 'Username/IP: By saving the username/IP of users, It supports the reverse feature  (un-like and un-dislike) and users can change their reactions and are only allowed to have a specific point type.', WP_ULIKE_SLUG ), esc_html__( 'Username/IP + Cookie: Same as username/IP description, However, if the user IP or username changes and the cookie is set, it does not allow the user to like /dislike.', WP_ULIKE_SLUG )  )
                ),
                'cookie_expires' => array(
                    'id'         => 'cookie_expires',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Cookie Expiration', WP_ULIKE_SLUG),
                    'desc'       => esc_html__('Specify how long, in seconds, cookie expires. Default value: 31536000', WP_ULIKE_SLUG),
                    'default'    => 31536000,
                    'dependency' => array( 'logging_method', 'any', 'by_cookie,by_user_ip_cookie' ),
                ),
                'vote_limit_number' => array(
                    'id'         => 'vote_limit_number',
                    'type'       => 'spinner',
                    'title'      => esc_html__( 'Vote Limit Per Day', WP_ULIKE_SLUG),
                    'desc'       => esc_html__('Limits the number of votes that can be submitted by user on each item per day.', WP_ULIKE_SLUG),
                    'default'    => 10,
                    'min'        => 1,
                    'max'        => 1000,
                    'dependency' => array( 'logging_method', '==', 'do_not_log' ),
                ),
                'enable_only_logged_in_users' => array(
                    'id'    => 'enable_only_logged_in_users',
                    'type'  => 'switcher',
                    'title' => esc_html__('Only logged in users', WP_ULIKE_SLUG),
                ),
                'logged_out_display_type' => array(
                    'id'         => 'logged_out_display_type',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Display Type', WP_ULIKE_SLUG),
                    'options'    => array(
                        'alert'  => esc_html__('Template', WP_ULIKE_SLUG),
                        'button' => esc_html__('Button', WP_ULIKE_SLUG)
                    ),
                    'default'    => 'button',
                    'dependency' => array( 'enable_only_logged_in_users', '==', 'true' ),
                ),
                'login_template' => array(
                    'id'       => 'login_template',
                    'type'     => 'code_editor',
                    'desc'     => esc_html__('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%CURRENT_PAGE_URL%</code>',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>',
                        esc_html__('You need to login in order to like this post: ',WP_ULIKE_SLUG),
                        wp_login_url(),
                        esc_html__('click here',WP_ULIKE_SLUG)
                    ),
                    'title'    => esc_html__('Custom HTML Template', WP_ULIKE_SLUG),
                    'dependency'=> array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'alert|true' ),
                ),
                'enable_likers_box' => array(
                    'id'    => 'enable_likers_box',
                    'type'  => 'switcher',
                    'title' => esc_html__('Display Likers Box', WP_ULIKE_SLUG),
                ),
                'likers_order' => array(
                    'id'         => 'likers_order',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'User List Arrange', WP_ULIKE_SLUG),
                    'default'    => 'desc',
                    'options'    => array(
                        'asc'  => esc_html__('Ascending', WP_ULIKE_SLUG),
                        'desc' => esc_html__('Descending', WP_ULIKE_SLUG)
                    ),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'hide_likers_for_anonymous_users' => array(
                    'id'    => 'hide_likers_for_anonymous_users',
                    'type'  => 'switcher',
                    'default' => false,
                    'title' => esc_html__('Hide For Anonymous Users', WP_ULIKE_SLUG),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_style' => array(
                    'id'         => 'likers_style',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Likers Box Display', WP_ULIKE_SLUG),
                    'default'    => 'popover',
                    'options'    => array(
                        'default' => esc_html__('Inline', WP_ULIKE_SLUG),
                        'popover' => esc_html__('Popover', WP_ULIKE_SLUG)
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
                    'title'    => esc_html__('Custom HTML Template', WP_ULIKE_SLUG),
                    'desc'     => esc_html__('Allowed Variables:', WP_ULIKE_SLUG) . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>',
                    'dependency'=> array( 'enable_likers_box|likers_style', '==|any', 'true|default,popover'  ),
                ),
                // 'disable_likers_pophover' => array(
                //     'id'         => 'disable_likers_pophover',
                //     'type'       => 'switcher',
                //     'title'      => esc_html__('Disable Pophover', WP_ULIKE_SLUG),
                //     'dependency' => array( 'enable_likers_box', '==', 'true' ),
                //     'desc'       => esc_html__('Active this option to show liked users avatars in the bottom of button like.', WP_ULIKE_SLUG)
                // ),
                'likers_gravatar_size' => array(
                    'id'         => 'likers_gravatar_size',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Size of Gravatars', WP_ULIKE_SLUG),
                    'default'    => 64,
                    'unit'       => 'px',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_count' => array(
                    'id'         => 'likers_count',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Likers Count', WP_ULIKE_SLUG),
                    'desc'       => esc_html__('The number of users to show in the users liked box', WP_ULIKE_SLUG),
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