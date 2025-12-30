<?php
/**
 * WP ULike Settings Deprecated Classes Handler
 * 
 * Provides backward compatibility for deprecated settings classes
 * when migrating from old settings directory to new Optiwich-based system
 * 
 * @package WP_ULike
 * @since 4.6.0
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_settings_deprecated' ) ) {
    class wp_ulike_settings_deprecated {

        /**
         * Constructor
         */
        public function __construct() {
            // Load deprecated classes if they exist
            $this->load_deprecated_classes();
            
            // Add compatibility hooks
            add_action( 'init', array( $this, 'add_compatibility_hooks' ) );
        }

        /**
         * Load deprecated classes from old settings directory
         */
        protected function load_deprecated_classes() {
            $deprecated_dir = WP_ULIKE_ADMIN_DIR . 'settings/classes/';
            
            if ( ! is_dir( $deprecated_dir ) ) {
                return;
            }

            // List of deprecated classes that might be used by other plugins/themes
            $deprecated_classes = array(
                'admin-options.class.php'     => 'wp_ulike_admin_options',
                'comment-options.class.php'    => 'wp_ulike_comment_options',
                'customize-options.class.php'  => 'wp_ulike_customize_options',
                'metabox-options.class.php'    => 'wp_ulike_metabox_options',
                'nav-menu-options.class.php'   => 'wp_ulike_nav_menu_options',
                'profile-options.class.php'    => 'wp_ulike_profile_options',
                'setup.class.php'              => 'wp_ulike_setup',
                'shortcode-options.class.php'  => 'wp_ulike_shortcode_options',
                'taxonomy-options.class.php'   => 'wp_ulike_taxonomy_options',
                'widget-options.class.php'     => 'wp_ulike_widget_options',
            );

            foreach ( $deprecated_classes as $file => $class_name ) {
                $file_path = $deprecated_dir . $file;
                
                if ( file_exists( $file_path ) && ! class_exists( $class_name ) ) {
                    require_once $file_path;
                }
            }
        }

        /**
         * Add compatibility hooks for deprecated functionality
         */
        public function add_compatibility_hooks() {
            // Ensure old filter hooks still work
            add_filter( 'wp_ulike_get_option', array( $this, 'get_option_compat' ), 10, 2 );
            add_filter( 'wp_ulike_update_option', array( $this, 'update_option_compat' ), 10, 2 );
            
            // Map old function calls to new API
            if ( ! function_exists( 'wp_ulike_get_admin_option' ) ) {
                function wp_ulike_get_admin_option( $option_name, $default = false ) {
                    $options = get_option( 'wp_ulike_settings', array() );
                    return isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default;
                }
            }

            if ( ! function_exists( 'wp_ulike_update_admin_option' ) ) {
                function wp_ulike_update_admin_option( $option_name, $value ) {
                    $options = get_option( 'wp_ulike_settings', array() );
                    $options[ $option_name ] = $value;
                    return update_option( 'wp_ulike_settings', $options );
                }
            }
        }

        /**
         * Compatibility wrapper for get_option
         */
        public function get_option_compat( $value, $option_name ) {
            $options = get_option( 'wp_ulike_settings', array() );
            return isset( $options[ $option_name ] ) ? $options[ $option_name ] : $value;
        }

        /**
         * Compatibility wrapper for update_option
         */
        public function update_option_compat( $result, $data ) {
            if ( is_array( $data ) && isset( $data['option_name'] ) ) {
                $options = get_option( 'wp_ulike_settings', array() );
                $options[ $data['option_name'] ] = isset( $data['option_value'] ) ? $data['option_value'] : '';
                return update_option( 'wp_ulike_settings', $options );
            }
            return $result;
        }
    }

    // Initialize deprecated handler
    new wp_ulike_settings_deprecated();
}





