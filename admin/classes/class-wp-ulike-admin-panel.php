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
        protected $sections_cache = null;

		/**
		 * __construct
		 */
		function __construct() {
            // No framework dependencies - just initialize
        }

        /**
         * Register setting sections
         * Returns array structure for API consumption
         *
         * @return array Sections structure
         */
        public function register_sections(){
            // Return cached sections if available
            if ( $this->sections_cache !== null ) {
                return $this->sections_cache;
            }

            do_action( 'wp_ulike_panel_sections_started' );

            $sections = array();

            /**
             * Configuration Section
             */
            $sections[] = array(
                'id'    => 'configuration',
                'title' => esc_html__( 'Configuration','wp-ulike'),
                'icon'  => 'cog-6-tooth',
            );

            // General
            $sections[] = array(
                'id'     => 'general',
                'parent' => 'configuration',
                'title'  => esc_html__( 'General','wp-ulike'),
                'icon'   => 'adjustments-horizontal',
                'fields' => apply_filters( 'wp_ulike_panel_general', array(
                    array(
                        'id'      => 'enable_kilobyte_format',
                        'type'    => 'switcher',
                        'title'   => esc_html__('Compact Counter (1k, 1.2k)', 'wp-ulike'),
                        'default' => false,
                        'desc'    => esc_html__('Show large like counts in a short format. Example: 1250 → 1.2k (applies at 1,000+).', 'wp-ulike')
                    ),
                    array(
                        'id'            => 'filter_counter_value',
                        'type'          => 'tabbed',
                        'desc'          => esc_html__( 'Add text before or after the counter value.', 'wp-ulike'),
                        'title'         => esc_html__( 'Counter Prefix & Suffix', 'wp-ulike'),
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__('Prefix','wp-ulike'),
                                'fields'    => apply_filters( 'wp_ulike_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_prefix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Like Prefix','wp-ulike'),
                                        'desc'    => esc_html__('Text shown before the count (e.g., "+" displays as "+125").', 'wp-ulike'),
                                        'default' => '+'
                                    ),
                                    array(
                                        'id'      => 'unlike_prefix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Unlike Prefix','wp-ulike'),
                                        'desc'    => esc_html__('Text shown before the count (e.g., "+" displays as "+125").', 'wp-ulike'),
                                        'default' => '+'
                                    ),
                                ), 'prefix' )
                            ),
                            array(
                                'title'     => esc_html__('Suffix','wp-ulike'),
                                'fields'    => apply_filters( 'wp_ulike_filter_counter_options', array(
                                    array(
                                        'id'      => 'like_postfix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Like Suffix','wp-ulike'),
                                        'desc'    => esc_html__('Text shown after the count (e.g., " likes" displays as "125 likes").', 'wp-ulike')
                                    ),
                                    array(
                                        'id'      => 'unlike_postfix',
                                        'type'    => 'text',
                                        'title'   => esc_html__('Unlike Suffix','wp-ulike'),
                                        'desc'    => esc_html__('Text shown after the count (e.g., " likes" displays as "125 likes").', 'wp-ulike')
                                    ),
                                ), 'postfix' )
                            ),
                        )
                    ),
                    array(
                        'id'      => 'enable_toast_notice',
                        'type'    => 'switcher',
                        'title'   => esc_html__('In-app Notifications', 'wp-ulike'),
                        'default' => true,
                        'desc'    => esc_html__('Show a brief confirmation message after users like/unlike.', 'wp-ulike')
                    ),
                    array(
                        'id'          => 'filter_toast_types',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Disable Notifications On','wp-ulike' ),
                        'desc'        => esc_html__('Choose where notifications are disabled.', 'wp-ulike'),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'post'     => esc_html__('Posts', 'wp-ulike'),
                            'comment'  => esc_html__('Comments', 'wp-ulike'),
                            'activity' => esc_html__('Activities', 'wp-ulike'),
                            'topic'    => esc_html__('Topics', 'wp-ulike')
                        ),
                        'dependency'=> array( 'enable_toast_notice', '==', 'true' ),
                    ),
                    array(
                        'id'    => 'enable_anonymise_ip',
                        'type'  => 'switcher',
                        'title' => esc_html__('Anonymize IP Addresses', 'wp-ulike'),
                        'desc'  => esc_html__('Mask part of each IP address so votes cannot be traced to individual users (last octet in IPv4, last 80 bits in IPv6).', 'wp-ulike')
                    ),
                    array(
                        'id'         => 'disable_ip_logging',
                        'type'       => 'switcher',
                        'title'      => esc_html__('Do Not Store IPs', 'wp-ulike'),
                        'desc'       => esc_html__('Stop saving user IP addresses in your database.', 'wp-ulike'),
                        'dependency' => array( 'enable_anonymise_ip', '==', 'true' ),
                    ),
                    array(
                        'id'    => 'cache_exist',
                        'type'  => 'switcher',
                        'title' => esc_html__('Site Uses Caching', 'wp-ulike'),
                        'desc'  => esc_html__('Turn this on if your site uses a caching plugin or service.', 'wp-ulike')
                    ),
                    array(
                        'id'    => 'disable_admin_notice',
                        'type'  => 'switcher',
                        'title' => esc_html__('Hide Plugin Admin Notices', 'wp-ulike'),
                        'desc'  => esc_html__('Completely hide WP ULike admin notices for all users.', 'wp-ulike')
                    ),
                    array(
                        'id'          => 'disable_plugin_files',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Disable Assets On','wp-ulike' ),
                        'desc'        => esc_html__('Prevent loading WP ULike CSS/JS on selected page types.', 'wp-ulike'),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'home'        => esc_html__('Home', 'wp-ulike'),
                            'single'      => esc_html__('Singular', 'wp-ulike'),
                            'archive'     => esc_html__('Archives', 'wp-ulike'),
                            'category'    => esc_html__('Categories', 'wp-ulike'),
                            'search'      => esc_html__('Search Results', 'wp-ulike'),
                            'tag'         => esc_html__('Tags', 'wp-ulike'),
                            'author'      => esc_html__('Author Page', 'wp-ulike'),
                            'buddypress'  => esc_html__('BuddyPress Pages', 'wp-ulike'),
                            'bbpress'     => esc_html__('bbPress Pages', 'wp-ulike'),
                            'woocommerce' => esc_html__('WooCommerce Pages', 'wp-ulike')
                        )
                    ),
                    array(
                        'id'          => 'enable_admin_posts_columns',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Show Admin Columns','wp-ulike' ),
                        'desc'        => esc_html__('Add a likes counter column to the admin list.', 'wp-ulike'),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => 'post_types'
                    ),
                    array(
                        'id'          => 'blacklist_integration',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Blacklist','wp-ulike'),
                        'options'     => array(
                            'default'  => esc_html__('Use the WP Ulike Blacklist', 'wp-ulike'),
                            'comments' => esc_html__('Use the WordPress Disallowed Comment Keys', 'wp-ulike')
                        ),
                        'default'     => 'default',
                        'desc'        => sprintf(
                            /* translators: %s: Link to options-discussion.php */
                            esc_html__('Choose which blacklist to use for voting. The %s setting is available on the WordPress Discussion Settings page.', 'wp-ulike'),
                            '<a href="'.admin_url('options-discussion.php').'">'.esc_html__('Disallowed Comment Keys', 'wp-ulike').'</a>'
                        )
                    ),
                    array(
                        'id'         => 'blacklist_entries',
                        'type'       => 'textarea',
                        'title'      => esc_html__( 'Blacklist Entries', 'wp-ulike'),
                        'desc'       => esc_html__('Enter one IP address per line. Votes from matching IPs will be rejected.', 'wp-ulike'),
                        'dependency' => array( 'blacklist_integration', 'any', 'default' )
                    )
                ) )
            );

            // Get all content options
            $get_content_options = apply_filters( 'wp_ulike_panel_content_options', $this->get_content_options() );
            $get_content_fields  = array();

            // Generate posts fields
            $get_content_fields['posts']    = $get_content_options;

            if( wp_ulike_is_wpml_active() ){
                $get_content_fields['posts']['enable_wpml_synchronization'] = array(
                    'id'    => 'enable_wpml_synchronization',
                    'type'  => 'switcher',
                    'title' => esc_html__('Enable WPML Synchronization', 'wp-ulike'),
                    'desc'  => esc_html__('Sync likes for translated post types when using the WPML plugin.', 'wp-ulike')
                );
            }


            // Generate comment fields
            $get_content_fields['comments'] = $get_content_options;
            unset( $get_content_fields['comments']['auto_display_filter'] );
            unset( $get_content_fields['comments']['auto_display_filter_post_types'] );
            $get_content_fields['comments']['enable_admin_columns'] = array(
                'id'         => 'enable_admin_columns',
                'type'       => 'switcher',
                'title'      => esc_html__('Show Admin Columns', 'wp-ulike'),
                'desc'       => esc_html__('Add a likes counter column to the admin list.', 'wp-ulike')
            );

            // Generate buddypress fields (only if plugin is active)
            $buddypress_options = array();
            if ( function_exists('is_buddypress') ) {
                $get_content_fields['buddypress'] = $get_content_options;
                unset( $get_content_fields['buddypress']['auto_display_filter'] );
                unset( $get_content_fields['buddypress']['auto_display_filter_post_types'] );
                $get_content_fields['buddypress']['auto_display_position']['options'] = array(
                    'content' => esc_html__('Activity Content', 'wp-ulike'),
                    'meta'    => esc_html__('Activity Meta', 'wp-ulike')
                );
                $get_content_fields['buddypress']['auto_display_position']['default'] = 'content';
                $get_content_fields['buddypress']['enable_comments'] = array(
                    'id'         => 'enable_comments',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Enable Activity Comment Likes', 'wp-ulike'),
                    'desc'       => esc_html__('Allow liking BuddyPress comments in the activity stream.', 'wp-ulike')
                );
                $get_content_fields['buddypress']['enable_add_bp_activity'] = array(
                    'id'         => 'enable_add_bp_activity',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Add Activity Entries for Likes', 'wp-ulike'),
                    'desc'       => esc_html__('Create a BuddyPress activity item when someone likes content.', 'wp-ulike'),
                );
                $get_content_fields['buddypress']['posts_notification_template'] = array(
                    'id'       => 'posts_notification_template',
                    'type'     => 'code_editor',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => '<strong>%POST_LIKER%</strong> liked <a href="%POST_PERMALINK%" title="%POST_TITLE%">%POST_TITLE%</a>. (So far, This post has <span class="badge">%POST_COUNT%</span> likes)',
                    'title'    => esc_html__('Post Activity Text', 'wp-ulike'),
                    'desc'     => esc_html__('Allowed Variables:', 'wp-ulike') . ' <code>%POST_LIKER%</code> , <code>%POST_PERMALINK%</code> , <code>%POST_COUNT%</code> , <code>%POST_TITLE%</code>',
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
                    'title'    => esc_html__('Comment Activity Text', 'wp-ulike'),
                    'desc'     => esc_html__('Allowed Variables:', 'wp-ulike') . ' <code>%COMMENT_LIKER%</code> , <code>%COMMENT_AUTHOR%</code> , <code>%COMMENT_COUNT%</code>, <code>%COMMENT_PERMALINK%</code>',
                    'dependency'=> array( 'enable_add_bp_activity', '==', 'true' ),
                );
                $get_content_fields['buddypress']['enable_add_notification'] = array(
                    'id'         => 'enable_add_notification',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Enable User Notifications', 'wp-ulike'),
                    'desc'       => esc_html__('Send a notification when your content receives a like.', 'wp-ulike'),
                );
                $get_content_fields['buddypress']['filter_user_notification_types'] = array(
                    'id'          => 'filter_user_notification_types',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Disable Notifications On','wp-ulike' ),
                    'desc'        => esc_html__('Choose where notifications are disabled.', 'wp-ulike'),
                    'chosen'      => true,
                    'multiple'    => true,
                    'options'     => array(
                        'post'     => esc_html__('Posts', 'wp-ulike'),
                        'comment'  => esc_html__('Comments', 'wp-ulike'),
                        'activity' => esc_html__('Activities', 'wp-ulike'),
                        'topic'    => esc_html__('Topics', 'wp-ulike')
                    ),
                    'dependency'=> array( 'enable_add_notification', '==', 'true' ),
                );
                $buddypress_options = array_values( apply_filters( 'wp_ulike_panel_buddypress_type_options', $get_content_fields['buddypress'] ) );
            }

            // Generate bbPress fields (only if plugin is active)
            $bbPress_options = array();
            if ( function_exists('is_bbpress') ) {
                $get_content_fields['bbpress'] = $get_content_options;
                unset( $get_content_fields['bbpress']['auto_display_filter'] );
                unset( $get_content_fields['bbpress']['auto_display_filter_post_types'] );
                $bbPress_options = array_values( apply_filters( 'wp_ulike_panel_bbpress_type_options', $get_content_fields['bbpress'] ) );
            }

            // Content Groups
            $content_types_fields = array(
                // Posts
                array(
                    'id'         => 'posts_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__('Posts'),
                    'fields'     => array_values( apply_filters( 'wp_ulike_panel_post_type_options', $get_content_fields['posts'] ) ),
                    'sanitize'   => 'wp_ulike_sanitize_multiple_select',
                    'display_as' => 'section' // Mark as section menu
                ),
                // Comments
                array(
                    'id'         => 'comments_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__('Comments'),
                    'fields'     => array_values( apply_filters( 'wp_ulike_panel_comment_type_options', $get_content_fields['comments'] ) ),
                    'display_as' => 'section' // Mark as section menu
                ),
            );

            // Only add BuddyPress if plugin is active
            if ( function_exists('is_buddypress') ) {
                $content_types_fields[] = array(
                    'id'         => 'buddypress_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__('BuddyPress'),
                    'fields'     => $buddypress_options,
                    'display_as' => 'section' // Mark as section menu
                );
            }

            // Only add bbPress if plugin is active
            if ( function_exists('is_bbpress') ) {
                $content_types_fields[] = array(
                    'id'         => 'bbpress_group',
                    'type'       => 'fieldset',
                    'title'      => esc_html__('bbPress'),
                    'fields'     => $bbPress_options,
                    'display_as' => 'section' // Mark as section menu
                );
            }

            $sections[] = array(
                'id'     => 'content-types',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Content Types','wp-ulike'),
                'icon'   => 'squares-2x2',
                'fields' => $content_types_fields
            );

            // Integrations
            $sections[] = array(
                'id'     => 'integrations',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Integrations','wp-ulike'),
                'icon'   => 'puzzle-piece',
                'fields' => apply_filters( 'wp_ulike_panel_integrations', array(
                    array(
                        'id'    => 'enable_meta_values',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Old Meta Values', 'wp-ulike'),
                        'desc'  => sprintf( '%s<br><strong>* %s</strong>', esc_html__('By activating this option, users who have upgraded to version +4 and deleted their old logs can add the number of old likes to the new figures.', 'wp-ulike'), esc_html__('Attention: If you have been using WP ULike +v4 from the beginning Or you haven\'t deleted any logs yet, do not enable this option.', 'wp-ulike') )
                    ),
                   array(
                        'id'    => 'enable_deprecated_options',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Deprecated Options', 'wp-ulike'),
                        'desc'  => sprintf( '%s<br><strong>* %s</strong>', esc_html__('By activating this option, users who have upgraded to version +4.1 and lost their old options can restore and enable previous settings.', 'wp-ulike'), esc_html__('Attention: If you have been using WP ULike +v4.1 from the beginning, do not enable this option.', 'wp-ulike') )
                    )
                ) )
            );

            // Profiles
            $sections[] = array(
                'id'     => 'profiles',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Profiles','wp-ulike'),
                'icon'   => 'user',
                'is_pro' => true,
                'fields' => apply_filters( 'wp_ulike_panel_profiles', $this->get_pro_lock_field( 'profiles' ) )
            );

            // Login & Signup
            $sections[] = array(
                'id'     => 'login-signup',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Login & Signup','wp-ulike'),
                'icon'   => 'key',
                'is_pro' => true,
                'fields' => apply_filters( 'wp_ulike_panel_forms', $this->get_pro_lock_field( 'forms' ) )
            );

            // Social login integration
            $sections[] = array(
                'id'     => 'social-logins',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Social Logins','wp-ulike'),
                'icon'   => 'user-group',
                'is_pro' => true,
                'fields' => apply_filters( 'wp_ulike_panel_social_logins', $this->get_pro_lock_field( 'social_logins' ) )
            );

            // Share buttons
            $sections[] = array(
                'id'     => 'share-buttons',
                'parent' => 'configuration',
                'title'  => esc_html__( 'Share Buttons','wp-ulike'),
                'icon'   => 'share',
                'is_pro' => true,
                'fields' => apply_filters( 'wp_ulike_panel_share_buttons', $this->get_pro_lock_field( 'share_buttons' ) )
            );

            /**
             * Translations Section
             */
            $sections[] = array(
                'id'    => 'translations',
                'title' => esc_html__( 'Translations','wp-ulike'),
                'icon'  => 'language',
            );

            $sections[] = array(
                'id'     => 'strings',
                'title'  => esc_html__( 'Strings','wp-ulike'),
                'parent' => 'translations',
                'icon'   => 'document-text',
                'fields' => apply_filters( 'wp_ulike_panel_translations', array(
                    array(
                        'id'      => 'validate_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'Your vote cannot be submitted at this time.','wp-ulike'),
                        'title'   => esc_html__( 'Validation Notice Message', 'wp-ulike'),
                        'desc'    => esc_html__( 'Message shown when a vote cannot be processed due to validation errors.', 'wp-ulike')
                    ),
                    array(
                        'id'      => 'already_registered_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'You have already registered a vote.','wp-ulike'),
                        'title'   => esc_html__( 'Already Voted Message', 'wp-ulike'),
                        'desc'    => esc_html__( 'Message shown when a user tries to vote again after already voting.', 'wp-ulike')
                    ),
                    array(
                        'id'      => 'login_required_notice',
                        'type'    => 'text',
                        'default' => esc_html__( 'You Should Login To Submit Your Like','wp-ulike'),
                        'title'   => esc_html__( 'Login Required Message', 'wp-ulike'),
                        'desc'    => esc_html__( 'Message shown to visitors who need to log in before they can vote.', 'wp-ulike')
                    ),
                    array(
                        'id'      => 'like_notice',
                        'type'    => 'text',
                        'default' => esc_html__('Thanks! You Liked This.','wp-ulike'),
                        'title'   => esc_html__( 'Liked Notice Message', 'wp-ulike'),
                        'desc'    => esc_html__( 'Confirmation message shown after a user successfully likes content.', 'wp-ulike')
                    ),
                    array(
                        'id'      => 'unlike_notice',
                        'type'    => 'text',
                        'default' => esc_html__('Sorry! You unliked this.','wp-ulike'),
                        'title'   => esc_html__( 'Unliked Notice Message', 'wp-ulike'),
                        'desc'    => esc_html__( 'Confirmation message shown after a user removes their like.', 'wp-ulike')
                    ),
                    array(
                        'id'      => 'like_button_aria_label',
                        'type'    => 'text',
                        'default' => esc_html__( 'Like Button','wp-ulike'),
                        'title'   => esc_html__( 'Like Button Aria Label', 'wp-ulike'),
                        'desc'    => esc_html__( 'Accessibility label for screen readers. Helps visually impaired users understand what the button does.', 'wp-ulike')
                    )
                ) )
            );

            // Emails
            $sections[] = array(
                'id'     => 'emails',
                'parent' => 'translations',
                'title'  => esc_html__( 'Emails','wp-ulike'),
                'icon'   => 'envelope',
                'is_pro' => true,
                'fields' => apply_filters( 'wp_ulike_panel_emails', $this->get_pro_lock_field( 'emails' ) )
            );

            /**
             * Customization Section
             */
            $sections[] = array(
                'id'    => 'customization',
                'title' => esc_html__( 'Developer Tools','wp-ulike'),
                'icon'  => 'code-bracket',
            );

            $sections[] = array(
                'id'     => 'scripts',
                'parent' => 'customization',
                'title'  => esc_html__( 'Scripts','wp-ulike'),
                'icon'   => 'document-text',
                'fields' => apply_filters( 'wp_ulike_panel_customization', array(
                    array(
                        'id'    => 'custom_css',
                        'type'  => 'code_editor',
                        'settings' => array(
                            'theme'  => 'mbo',
                            'mode'   => 'css',
                        ),
                        'title' => esc_html__('Custom CSS','wp-ulike'),
                        'desc'  => esc_html__('Add custom CSS to style the like button and related elements. This CSS will be loaded on all pages.', 'wp-ulike'),
                    ),
                    array(
                        'id'           => 'custom_spinner',
                        'type'         => 'upload',
                        'title'        => esc_html__('Custom Spinner','wp-ulike'),
                        'desc'         => esc_html__('Upload a custom loading animation image that appears while processing votes.', 'wp-ulike'),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                    array(
                        'id'    => 'enable_inline_custom_css',
                        'type'  => 'switcher',
                        'title' => esc_html__('Enable Inline Custom CSS', 'wp-ulike'),
                        'desc'  => esc_html__('Output styles inline on the page instead of loading custom.css file. The CSS file will still be generated for debugging and inspection purposes.', 'wp-ulike')
                    )
                ) )
            );

            $sections[] = array(
                'id'     => 'rest-api',
                'parent' => 'customization',
                'title'  => esc_html__( 'REST API','wp-ulike' ),
                'icon'   => 'server',
                'is_pro' => true,
                'fields' => apply_filters( 'wp_ulike_panel_rest_api', $this->get_pro_lock_field( 'rest_api' ) )
            );

            /**
             * Backup & Restore Section
             */
            $sections[] = array(
                'id'    => 'backup',
                'title' => esc_html__( 'Backup & Restore', 'wp-ulike' ),
                'icon'  => 'arrow-down-tray',
                'fields' => array(
                    array(
                        'id'    => 'backup_restore',
                        'type'  => 'backup',
                        'desc'  => esc_html__( 'Import settings from a JSON file or export your current settings for backup purposes.', 'wp-ulike' ),
                        'importTitle' => esc_html__( 'Import Settings', 'wp-ulike' ),
                        'importDesc' => esc_html__( 'Paste your exported settings JSON below and click Import to restore your configuration. The import should contain only setting values (not schema structure).', 'wp-ulike' ),
                        'importPlaceholder' => esc_html__( 'Paste your JSON settings here...', 'wp-ulike' ),
                        'importButtonText' => esc_html__( 'Import', 'wp-ulike' ),
                        'importingText' => esc_html__( 'Importing...', 'wp-ulike' ),
                        'exportTitle' => esc_html__( 'Export Settings', 'wp-ulike' ),
                        'exportDesc' => esc_html__( 'Copy the JSON below or download it as a file to backup your current settings. The export contains only your setting values (not the schema structure).', 'wp-ulike' ),
                        'exportButtonText' => esc_html__( 'Export & Download', 'wp-ulike' ),
                        'importSuccessMessage' => esc_html__( 'Settings imported and saved successfully!', 'wp-ulike' ),
                    ),
                ),
            );


            do_action( 'wp_ulike_panel_sections_ended' );

            // Cache sections
            $this->sections_cache = $sections;

            return $sections;
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
                    'title'   => esc_html__( 'Select a Template','wp-ulike'),
                    'desc'    => sprintf( '%s <a target="_blank" href="%s" title="Click">%s</a>', esc_html__( 'Preview templates online','wp-ulike'),  WP_ULIKE_PLUGIN_URI . 'templates/?utm_source=settings-page&utm_campaign=plugin-uri&utm_medium=wp-dash',esc_html__( 'Open preview','wp-ulike') ),
                    'options' => $this->get_templates_option_array(),
                    'default' => 'wpulike-default',
                ),
                'button_type' => array(
                    'id'         => 'button_type',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Button Type', 'wp-ulike'),
                    'desc'       => esc_html__('Choose whether to display an image icon or text label on the button.', 'wp-ulike'),
                    'default'    => 'image',
                    'options'    => array(
                        'image' => esc_html__('Image', 'wp-ulike'),
                        'text'  => esc_html__('Text', 'wp-ulike')
                    ),
                    'dependency' => array( 'template', 'any', 'wpulike-default,wp-ulike-pro-default,wpulike-heart' ),
                ),
                'text_group' => array(
                    'id'            => 'text_group',
                    'type'          => 'tabbed',
                    'desc'          => esc_html__( 'Set custom button text. HTML is allowed.', 'wp-ulike'),
                    'title'         => esc_html__( 'Button Text', 'wp-ulike'),
                    'tabs'          => array(
                        array(
                            'title'     => esc_html__('Like','wp-ulike'),
                            'fields'    => array(
                                array(
                                    'id'      => 'like',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Button Label','wp-ulike'),
                                    'desc'    => esc_html__('Text displayed on the like button (e.g., "Like", "👍", "Love").', 'wp-ulike'),
                                    'default' => esc_html__('Like', 'wp-ulike')
                                ),
                            )
                        ),
                        array(
                            'title'     => esc_html__('Unlike','wp-ulike'),
                            'fields'    => array(
                                array(
                                    'id'      => 'unlike',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Button Label','wp-ulike'),
                                    'desc'    => esc_html__('Text displayed on the button after liking (e.g., "Liked", "❤️", "Unlike").', 'wp-ulike'),
                                    'default' => esc_html__('Liked', 'wp-ulike')
                                ),
                            )
                        ),
                    ),
                    'dependency' => array( 'button_type|template', 'any|any', 'text|wpulike-default,wp-ulike-pro-default,wpulike-heart' ),
                ),
                'image_group' => array(
                    'id'            => 'image_group',
                    'type'          => 'tabbed',
                    'title'         => esc_html__( 'Button Image', 'wp-ulike'),
                    'desc'          => esc_html__( 'Upload custom images for the like and unlike button states.', 'wp-ulike'),
                    'tabs'          => array(
                        array(
                            'title'     => esc_html__('Like','wp-ulike'),
                            'fields'    => array(
                                array(
                                    'id'           => 'like',
                                    'type'         => 'upload',
                                    'title'        => esc_html__('Button Image','wp-ulike'),
                                    'desc'         => esc_html__('Upload an image icon for the button state.', 'wp-ulike'),
                                    'library'      => 'image',
                                    'placeholder'  => 'http://'
                                ),
                            )
                        ),
                        array(
                            'title'     => esc_html__('Unlike','wp-ulike'),
                            'fields'    => array(
                                array(
                                    'id'           => 'unlike',
                                    'type'         => 'upload',
                                    'title'        => esc_html__('Button Image','wp-ulike'),
                                    'desc'         => esc_html__('Upload an image icon for the button state.', 'wp-ulike'),
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
                    'title'   => esc_html__('Automatic Display', 'wp-ulike'),
                    'desc'    => esc_html__('Automatically show the like button on your content without manually adding shortcodes.', 'wp-ulike'),
                ),
                'auto_display_position' => array(
                    'id'      => 'auto_display_position',
                    'type'    => 'radio',
                    'title'   => esc_html__( 'Button Position','wp-ulike' ),
                    'desc'    => esc_html__('Choose where the like button appears relative to your content.', 'wp-ulike'),
                    'default' => 'bottom',
                    'options' => array(
                        'top'        => esc_html__('Top of Content', 'wp-ulike'),
                        'bottom'     => esc_html__('Bottom of Content', 'wp-ulike'),
                        'top_bottom' => esc_html__('Top and Bottom', 'wp-ulike')
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter' => array(
                    'id'          => 'auto_display_filter',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Hide Automatic Display On','wp-ulike' ),
                    'desc'        => esc_html__('Hide the button on selected page types.', 'wp-ulike'),
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'single', 'home' ),
                    'options'     => array(
                        'home'     => esc_html__('Home', 'wp-ulike'),
                        'single'   => esc_html__('Singular', 'wp-ulike'),
                        'archive'  => esc_html__('Archives', 'wp-ulike'),
                        'category' => esc_html__('Categories', 'wp-ulike'),
                        'search'   => esc_html__('Search Results', 'wp-ulike'),
                        'tag'      => esc_html__('Tags', 'wp-ulike'),
                        'author'   => esc_html__('Author Page', 'wp-ulike')
                    ),
                    'dependency' => array( 'enable_auto_display', '==', 'true' ),
                ),
                'auto_display_filter_post_types' => array(
                    'id'          => 'auto_display_filter_post_types',
                    'type'        => 'select',
                    'title'       => esc_html__( 'Post Type Exceptions','wp-ulike' ),
                    'placeholder' => esc_html__( 'Select post types','wp-ulike' ),
                    'desc'        => esc_html__( 'Always show the button on these post types, even if disabled elsewhere.','wp-ulike' ),
                    'chosen'      => true,
                    'multiple'    => true,
                    'default'     => array( 'post' ),
                    'options'     => 'post_types',
                    'dependency'  => array( 'auto_display_filter|enable_auto_display', 'any|==', 'single|true' ),
                ),
                'counter_display_condition' => array(
                    'id'         => 'counter_display_condition',
                    'type'       => 'button_set',
                    'desc'       => esc_html__( 'Control when the vote counter is shown next to the button.','wp-ulike' ),
                    'title'      => esc_html__( 'Counter Display', 'wp-ulike'),
                    'default'    => 'visible',
                    'options'    => array(
                        'visible'         => esc_html__('Always Visible', 'wp-ulike'),
                        'hidden'          => esc_html__('Hidden', 'wp-ulike'),
                        'logged_in_users' => esc_html__('Restrict to Logged-in Users', 'wp-ulike')
                    )
                ),
                'hide_zero_counter' => array(
                    'id'         => 'hide_zero_counter',
                    'type'       => 'switcher',
                    'title'      => esc_html__('Hide Zero Counter', 'wp-ulike'),
                    'desc'       => esc_html__( 'Hide the vote counter when no votes have been submitted.','wp-ulike' ),
                    'dependency' => array( 'counter_display_condition', '!=', 'hidden' )
                ),
                'logging_method' => array(
                    'id'          => 'logging_method',
                    'type'        => 'select',
                    'desc'        => esc_html__( 'Select how votes are tracked. You can allow unlimited votes, or restrict users using cookies, their username/IP, or both to prevent duplicate or repeated votes.','wp-ulike' ),
                    'title'       => esc_html__( 'Logging Method','wp-ulike'),
                    'options'     => array(
                        'do_not_log'        => esc_html__('No Limit', 'wp-ulike'),
                        'by_cookie'         => esc_html__('Cookie', 'wp-ulike'),
                        'by_username'       => esc_html__('Username/IP', 'wp-ulike'),
                        'by_user_ip_cookie' => esc_html__('Username/IP + Cookie', 'wp-ulike')
                    ),
                    'default'     => 'by_username',
                    'help'        => sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p>', esc_html__( '"No Limit": There will be no restrictions and users can submit their points each time they refresh the page. In this option, it will not be possible to resubmit reverse points (un-like/un-dislike).', 'wp-ulike' ), esc_html__( '"Cookie": By saving users\' cookies, it is possible to submit points only once per user and in case of re-clicking, the appropriate message will be displayed.', 'wp-ulike' ), esc_html__( 'Username/IP: By saving the username/IP of users, It supports the reverse feature  (un-like and un-dislike) and users can change their reactions and are only allowed to have a specific point type.', 'wp-ulike' ), esc_html__( 'Username/IP + Cookie: Same as username/IP description, However, if the user IP or username changes and the cookie is set, it does not allow the user to like /dislike.', 'wp-ulike' )  )
                ),
                'cookie_expires' => array(
                    'id'         => 'cookie_expires',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Cookie Expiration', 'wp-ulike'),
                    'desc'       => esc_html__('Specify how long, in seconds, the cookie expires. Default: 31536000 (1 year).', 'wp-ulike'),
                    'default'    => 31536000,
                    'dependency' => array( 'logging_method', 'any', 'by_cookie,by_user_ip_cookie' ),
                ),
                'vote_limit_number' => array(
                    'id'         => 'vote_limit_number',
                    'type'       => 'spinner',
                    'title'      => esc_html__( 'Maximum Votes Allowed', 'wp-ulike'),
                    'desc'       => esc_html__('Sets a maximum number of votes each user can submit on an item.', 'wp-ulike'),
                    'default'    => 10,
                    'min'        => 1,
                    'max'        => 1000,
                    'dependency' => array( 'logging_method', '==', 'do_not_log' ),
                ),
                'enable_only_logged_in_users' => array(
                    'id'      => 'enable_only_logged_in_users',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => esc_html__('Restrict to Logged-in Users', 'wp-ulike'),
                    'desc'    => esc_html__('We recommend enabling this to prevent fake votes that can be generated by changing IP addresses.', 'wp-ulike'),
                ),
                'logged_out_display_type' => array(
                    'id'         => 'logged_out_display_type',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Logged-out Button Display', 'wp-ulike'),
                    'desc'       => esc_html__('Choose how the vote button is shown to users who aren\'t logged in—either as a standard button or a template message prompting login.', 'wp-ulike'),
                    'options'    => array(
                        'alert'  => esc_html__('Template', 'wp-ulike'),
                        'button' => esc_html__('Button', 'wp-ulike')
                    ),
                    'default'    => 'button',
                    'dependency' => array( 'enable_only_logged_in_users', '==', 'true' ),
                ),
                'login_template' => array(
                    'id'       => 'login_template',
                    'type'     => 'code_editor',
                    'desc'     => esc_html__('Allowed Variables:', 'wp-ulike') . ' <code>%CURRENT_PAGE_URL%</code>',
                    'settings' => array(
                        'theme' => 'shadowfox',
                        'mode'  => 'htmlmixed',
                    ),
                    'default'  => sprintf( '<p class="alert alert-info fade in" role="alert">%s<a href="%s">%s</a></p>',
                        esc_html__('You need to login in order to like this post: ','wp-ulike'),
                        wp_login_url(),
                        esc_html__('click here','wp-ulike')
                    ),
                    'title'    => esc_html__('Custom HTML Template', 'wp-ulike'),
                    'dependency'=> array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'alert|true' ),
                ),
                'enable_likers_box' => array(
                    'id'    => 'enable_likers_box',
                    'type'  => 'switcher',
                    'desc'  => esc_html__( 'Display a list of users who have voted, allowing you to see who engaged with each item.','wp-ulike' ),
                    'title' => esc_html__('Display Likers Box', 'wp-ulike'),
                ),
                'likers_order' => array(
                    'id'         => 'likers_order',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Likers List Order', 'wp-ulike'),
                    'desc'       => esc_html__('Sort users in the likers box by newest first (descending) or oldest first (ascending).', 'wp-ulike'),
                    'default'    => 'desc',
                    'options'    => array(
                        'asc'  => esc_html__('Ascending', 'wp-ulike'),
                        'desc' => esc_html__('Descending', 'wp-ulike')
                    ),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'hide_likers_for_anonymous_users' => array(
                    'id'    => 'hide_likers_for_anonymous_users',
                    'type'  => 'switcher',
                    'default' => false,
                    'title' => esc_html__('Hide For Anonymous Users', 'wp-ulike'),
                    'desc'  => esc_html__('Hide the likers box from visitors who are not logged in.', 'wp-ulike'),
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_style' => array(
                    'id'         => 'likers_style',
                    'type'       => 'button_set',
                    'title'      => esc_html__( 'Likers Box Layout', 'wp-ulike'),
                    'desc'       => esc_html__('Inline: Show avatars directly below the button. Popover: Show avatars in a popup when hovering over the counter.', 'wp-ulike'),
                    'default'    => 'popover',
                    'options'    => array(
                        'default' => esc_html__('Inline', 'wp-ulike'),
                        'popover' => esc_html__('Popover', 'wp-ulike')
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
                    'title'    => esc_html__('Custom HTML Template', 'wp-ulike'),
                    'desc'     => esc_html__('Allowed Variables:', 'wp-ulike') . ' <code>%USER_AVATAR%</code> , <code>%BP_PROFILE_URL%</code> , <code>%UM_PROFILE_URL%</code> , <code>%USER_NAME%</code> , <code>%START_WHILE%</code> , <code>%END_WHILE%</code>',
                    'dependency'=> array( 'enable_likers_box|likers_style', '==|any', 'true|default,popover'  ),
                ),
                // 'disable_likers_pophover' => array(
                //     'id'         => 'disable_likers_pophover',
                //     'type'       => 'switcher',
                //     'title'      => esc_html__('Disable Pophover', 'wp-ulike'),
                //     'dependency' => array( 'enable_likers_box', '==', 'true' ),
                //     'desc'       => esc_html__('Active this option to show liked users avatars in the bottom of button like.', 'wp-ulike')
                // ),
                'likers_gravatar_size' => array(
                    'id'         => 'likers_gravatar_size',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Avatar Size', 'wp-ulike'),
                    'desc'       => esc_html__('Set the size of user avatars displayed in the likers box.', 'wp-ulike'),
                    'default'    => 32,
                    'unit'       => 'px',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                ),
                'likers_count' => array(
                    'id'         => 'likers_count',
                    'type'       => 'number',
                    'title'      => esc_html__( 'Likers to Show', 'wp-ulike'),
                    'desc'       => esc_html__('Number of users displayed in the likers box', 'wp-ulike'),
                    'default'    => 10,
                    'unit'       => 'users',
                    'dependency' => array( 'enable_likers_box', '==', 'true' ),
                )
            );
        }

        /**
         * Get templates option array
         * Returns structured data with symbol and is_locked flag for React app
         *
         * @return array
         */
        public function get_templates_option_array(){
            $options = wp_ulike_generate_templates_list();
            $output  = array();

            if( !empty( $options ) ){
                foreach ($options as $key => $args) {
                    // Return structured data: symbol URL and is_locked flag
                    // React app can use is_locked, PHP field renderer can use symbol
                    $output[$key] = array(
                        'symbol'    => isset( $args['symbol'] ) ? $args['symbol'] : '',
                        'is_locked' => isset( $args['is_locked'] ) ? $args['is_locked'] : false
                    );
                }
            }

            return $output;
        }

        /**
         * Get pro_lock field with minimal schema and field patterns
         *
         * @param string $section Section name (profiles, forms, social_logins, share_buttons, rest_api)
         * @return array Pro lock field array
         */
        public function get_pro_lock_field( $section = 'profiles' ) {
            $configs = array(
                'profiles' => array(
                    'id'          => 'wp_ulike_pro_profiles_lock',
                    'title'       => esc_html__( 'Build Stronger Community Engagement', 'wp-ulike' ),
                    'description' => esc_html__( 'Create Instagram-style user profiles that showcase user activity, likes, and engagement history. Transform anonymous visitors into recognized community members who return for more interactions.', 'wp-ulike' ),
                    'features'    => array(
                        esc_html__( 'Display user like history and engagement stats', 'wp-ulike' ),
                        esc_html__( 'Customizable profile layouts and permalinks', 'wp-ulike' ),
                        esc_html__( 'User badges and achievement systems', 'wp-ulike' ),
                        esc_html__( 'Avatar management and profile customization', 'wp-ulike' ),
                        esc_html__( 'Activity feeds showing user interactions', 'wp-ulike' )
                    ),
                    'field_pattern' => array(
                        array(
                            'type'  => 'switcher',
                            'title' => sprintf( esc_html__('Enable %s', 'wp-ulike'), esc_html__('User Profiles', 'wp-ulike') ),
                            'desc'  => esc_html__('Create custom user profile pages where users can view and manage their information, activity, and preferences.', 'wp-ulike'),
                        ),
                        array(
                            'type'  => 'select',
                            'title' => sprintf( esc_html__('Select %s Page', 'wp-ulike'), esc_html__('Profile', 'wp-ulike') ),
                        ),
                    ), // First 2 fields from profiles section
                    'upgrade_url' => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=profiles-section',
                    'read_more_url' => WP_ULIKE_PLUGIN_URI . 'blog/best-wordpress-profile-builder-plugin/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=profiles-section',
                ),
                'forms' => array(
                    'id'          => 'wp_ulike_pro_forms_lock',
                    'title'       => esc_html__( 'Convert More Visitors Into Active Users', 'wp-ulike' ),
                    'description' => esc_html__( 'Replace clunky WordPress login forms with seamless AJAX-powered authentication. Reduce bounce rates and turn casual browsers into engaged community members who can like, share, and interact.', 'wp-ulike' ),
                    'features'    => array(
                        esc_html__( 'Zero-page-reload login and registration', 'wp-ulike' ),
                        esc_html__( 'Custom branded login pages', 'wp-ulike' ),
                        esc_html__( 'Email verification and security features', 'wp-ulike' ),
                        esc_html__( 'Two-factor authentication support', 'wp-ulike' ),
                        esc_html__( 'Spam protection with reCAPTCHA', 'wp-ulike' )
                    ),
                    'field_pattern' => array(
                        array(
                            'type'  => 'select',
                            'title' => sprintf( esc_html__('Select %s Page', 'wp-ulike'), esc_html__('Login', 'wp-ulike') ),
                            'desc'  => esc_html__('Choose the page that contains your login form shortcode. This page will be used for user authentication.', 'wp-ulike'),
                        ),
                        array(
                            'type'  => 'text',
                            'title' => esc_html__( 'Login Redirect URL', 'wp-ulike'),
                        ),
                    ), // First 2 fields from forms section
                    'upgrade_url' => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=forms-section',
                    'read_more_url' => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ajax-login-registration-plugin/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=forms-section',
                ),
                'social_logins' => array(
                    'id'          => 'wp_ulike_pro_social_logins_lock',
                    'title'       => esc_html__( 'Remove Registration Friction & Boost Signups', 'wp-ulike' ),
                    'description' => esc_html__( 'Eliminate password fatigue with one-click social authentication. Users sign up faster, reducing abandonment rates and increasing your engaged user base ready to like and interact.', 'wp-ulike' ),
                    'features'    => array(
                        esc_html__( '14+ social networks including Google, Facebook, GitHub', 'wp-ulike' ),
                        esc_html__( 'Automatic integration with login forms', 'wp-ulike' ),
                        esc_html__( 'Customizable button designs and layouts', 'wp-ulike' ),
                        esc_html__( 'Flexible positioning and display options', 'wp-ulike' ),
                        esc_html__( 'One-click setup and configuration', 'wp-ulike' )
                    ),
                    'field_pattern' => array(
                        array(
                            'type'  => 'switcher',
                            'title' => esc_html__('Enable Social Logins', 'wp-ulike'),
                            'desc'  => esc_html__('Allow users to log in or register using their social media accounts instead of creating a new account.', 'wp-ulike'),
                        ),
                        array(
                            'type'  => 'group',
                            'title' => esc_html__('Social networks', 'wp-ulike'),
                        ),
                    ), // First 2 fields from social_logins section (matches Pro exactly)
                    'upgrade_url' => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=social-logins-section',
                    'read_more_url' => WP_ULIKE_PLUGIN_URI . 'blog/social-login/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=social-logins-section',
                ),
                'share_buttons' => array(
                    'id'          => 'wp_ulike_pro_share_buttons_lock',
                    'title'       => esc_html__( 'Turn Engagement Into Organic Traffic Growth', 'wp-ulike' ),
                    'description' => esc_html__( 'Multiply your content reach by making every like button a potential share. When users engage with your content, they can instantly share it across 23+ social platforms, driving free organic traffic back to your site.', 'wp-ulike' ),
                    'features'    => array(
                        esc_html__( '23+ social networks including WhatsApp, Telegram, Reddit', 'wp-ulike' ),
                        esc_html__( 'Auto-display alongside like buttons', 'wp-ulike' ),
                        esc_html__( 'Customizable designs matching your brand', 'wp-ulike' ),
                        esc_html__( 'Multiple button sets for different content types', 'wp-ulike' ),
                        esc_html__( 'Shortcode placement anywhere on your site', 'wp-ulike' )
                    ),
                    'field_pattern' => array(
                        array(
                            'type'  => 'group',
                            'title' => esc_html__('Add Share Items', 'wp-ulike'),
                        ),
                    ), // Share buttons uses group repeater style (matches Pro exactly - shows actual repeater)
                    'upgrade_url' => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=share-buttons-section',
                    'read_more_url' => WP_ULIKE_PLUGIN_URI . 'blog/wordpress-ultimate-social-share-buttons/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=share-buttons-section',
                ),
                'rest_api' => array(
                    'id'          => 'wp_ulike_pro_rest_api_lock',
                    'title'       => esc_html__( 'Scale Engagement Data Across Your Ecosystem', 'wp-ulike' ),
                    'description' => esc_html__( 'Integrate WP ULike engagement data with mobile apps, custom dashboards, analytics tools, and third-party services. Build powerful integrations that leverage your community\'s like and interaction data.', 'wp-ulike' ),
                    'features'    => array(
                        esc_html__( 'Complete REST API for all engagement data', 'wp-ulike' ),
                        esc_html__( 'Custom endpoints for like counts and statistics', 'wp-ulike' ),
                        esc_html__( 'Secure authentication with user login or API tokens', 'wp-ulike' ),
                        esc_html__( 'Role-based access control for data security', 'wp-ulike' ),
                        esc_html__( 'Automatic user identification and tracking', 'wp-ulike' )
                    ),
                    'field_pattern' => array(
                        array(
                            'type'  => 'switcher',
                            'title' => esc_html__('Enable REST API', 'wp-ulike'),
                            'desc'  => esc_html__('Expose WP ULike data through WordPress REST API endpoints, allowing external applications to access like counts, user votes, and statistics.', 'wp-ulike'),
                        ),
                        array(
                            'type'  => 'button_set',
                            'title' => esc_html__( 'Authentication Type', 'wp-ulike'),
                            'options' => array(
                                'login' => esc_html__('User Login', 'wp-ulike'),
                                'token' => esc_html__('Custom Keys', 'wp-ulike'),
                            ),
                        ),
                    ), // First 2 fields from rest_api section
                    'upgrade_url' => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=rest-api-section',
                    'read_more_url' => WP_ULIKE_PLUGIN_URI . 'blog/how-to-get-started-with-wp-ulike-rest-api/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=rest-api-section',
                ),
                'emails' => array(
                    'id'          => 'wp_ulike_pro_emails_lock',
                    'title'       => esc_html__( 'Professional Email Communication System', 'wp-ulike' ),
                    'description' => esc_html__( 'Customize all email templates sent by WP ULike Pro including welcome emails, password resets, account verification, and notifications. Create branded, professional communications that match your site\'s style and improve user engagement.', 'wp-ulike' ),
                    'features'    => array(
                        esc_html__( 'Customizable email templates for all user actions', 'wp-ulike' ),
                        esc_html__( 'HTML email support with rich formatting', 'wp-ulike' ),
                        esc_html__( 'Variable system for dynamic content', 'wp-ulike' ),
                        esc_html__( 'Welcome, password reset, and verification emails', 'wp-ulike' ),
                        esc_html__( 'Custom sender name and email address', 'wp-ulike' )
                    ),
                    'field_pattern' => array(
                        array(
                            'type'  => 'text',
                            'title' => esc_html__( 'Subject Line', 'wp-ulike'),
                        ),
                        array(
                            'type'  => 'text',
                            'title' => esc_html__( 'Admin E-mail Address', 'wp-ulike'),
                        ),
                    ), // First 2 fields from emails section
                    'upgrade_url' => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=emails-section',
                    'read_more_url' => WP_ULIKE_PLUGIN_URI . 'blog/?utm_source=settings-page&utm_campaign=gopro&utm_medium=wp-dash&utm_content=emails-section',
                )
            );

            $config = isset( $configs[$section] ) ? $configs[$section] : $configs['profiles'];

            return array(
                array(
                    'id'          => $config['id'],
                    'type'        => 'pro_lock',
                    'title'       => $config['title'],
                    'desc'        => $config['description'],
                    'preview'     => array(
                        'title'       => $config['title'],
                        'description' => $config['description'],
                        'features'    => $config['features'],
                    ),
                    'fieldPattern' => $config['field_pattern'],
                    'upgradeUrl'  => $config['upgrade_url'],
                    'readMoreUrl' => $config['read_more_url'],
                    'upgradeText' => esc_html__( 'Upgrade to Pro', 'wp-ulike' ),
                    'readMoreText' => esc_html__( 'Read More', 'wp-ulike' ),
                    'variant'     => 'default',
                )
            );
        }


    }
}