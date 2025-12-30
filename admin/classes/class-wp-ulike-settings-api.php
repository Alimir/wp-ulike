<?php
/**
 * WP ULike Settings REST API
 *
 * Provides REST API endpoints for Optiwich integration
 * Extracts schema from admin panel without modifying source code
 *
 * @package WP_ULike
 * @since 4.6.0
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_settings_api' ) ) {
    class wp_ulike_settings_api {

        /**
         * Option domain
         */
        protected $option_domain = 'wp_ulike_settings';

        /**
         * Admin panel instance
         */
        protected $admin_panel = null;

        /**
         * Schema cache
         */
        protected $schema_cache = null;

        /**
         * Constructor
         */
        public function __construct() {
            // Capture admin panel instance when it's available
            add_action( 'wp_ulike_settings_loaded', array( $this, 'capture_admin_panel' ), 1 );
            // Also try to capture on init in case settings_loaded fires before this class
            add_action( 'init', array( $this, 'capture_admin_panel' ), 20 );
        }

        /**
         * Capture admin panel instance
         */
        public function capture_admin_panel() {
            global $wp_ulike_admin_panel;
            if ( isset( $wp_ulike_admin_panel ) && is_object( $wp_ulike_admin_panel ) ) {
                $this->admin_panel = $wp_ulike_admin_panel;
            }
        }

        /**
         * Clear schema cache
         */
        public function clear_schema_cache() {
            $this->schema_cache = null;
        }

        /**
         * Get settings values only
         */
        public function get_settings( $request = null ) {
            return $this->get_values();
        }

        /**
         * Get schema with defaults and dynamic options
         */
        public function get_schema( $request = null ) {
            // Return cached schema if available
            if ( $this->schema_cache !== null ) {
                return $this->schema_cache;
            }

            // Build schema from admin panel
            $schema = $this->build_schema_from_panel();
            
            // Apply default values from settings
            $values = $this->get_values();
            $schema = $this->apply_defaults_to_schema( $schema, $values );
            
            // Resolve dynamic options
            $schema = $this->resolve_dynamic_options_in_schema( $schema );
            
            // Decode HTML entities in titles and descriptions
            $schema = $this->decode_html_entities_in_schema( $schema );

            // Cache the schema
            $this->schema_cache = $schema;

            return apply_filters( 'wp_ulike_optiwich_schema', $schema );
        }

        /**
         * Build schema from admin panel structure
         */
        protected function build_schema_from_panel() {
            // Ensure admin panel is initialized
            if ( ! did_action( 'wp_ulike_settings_loaded' ) ) {
                do_action( 'wp_ulike_settings_loaded' );
            }

            // Get schema from admin panel
            global $wp_ulike_admin_panel;
            
            // If admin panel doesn't exist, try to create it
            if ( ! isset( $wp_ulike_admin_panel ) && class_exists( 'wp_ulike_admin_panel' ) ) {
                $wp_ulike_admin_panel = new wp_ulike_admin_panel();
            }
            
            if ( isset( $wp_ulike_admin_panel ) && method_exists( $wp_ulike_admin_panel, 'get_optiwich_schema' ) ) {
                $schema = $wp_ulike_admin_panel->get_optiwich_schema();
            } else {
                $schema = array( 'pages' => array() );
            }

            // Schema is already processed (callbacks rendered, IDs generated) by get_optiwich_schema()
            // No need to clean fields anymore - all fields are supported
            return $schema;
        }
        
        /**
         * Apply default values from settings to schema
         */
        protected function apply_defaults_to_schema( $schema, $values ) {
            if ( ! isset( $schema['pages'] ) || ! is_array( $schema['pages'] ) ) {
                return $schema;
            }

            foreach ( $schema['pages'] as &$page ) {
                if ( isset( $page['sections'] ) && is_array( $page['sections'] ) ) {
                    foreach ( $page['sections'] as &$section ) {
                        if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
                            $section['fields'] = $this->apply_defaults_to_fields( $section['fields'], $values );
                        }
                    }
                }
            }

            return $schema;
        }
        
        /**
         * Apply default values to fields recursively
         */
        protected function apply_defaults_to_fields( $fields, $values, $path = '' ) {
            foreach ( $fields as &$field ) {
                if ( ! isset( $field['id'] ) ) {
                    continue;
                }

                $field_path = $path ? $path . '.' . $field['id'] : $field['id'];
                
                // Get value from settings if exists, otherwise use default
                $current_value = $this->get_value_at_path( $values, $field_path );
                if ( $current_value !== null ) {
                    $field['default'] = $current_value;
                }

                // Handle nested fields
                if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                    $field['fields'] = $this->apply_defaults_to_fields( $field['fields'], $values, $field_path );
                }
                
                if ( isset( $field['tabs'] ) && is_array( $field['tabs'] ) ) {
                    foreach ( $field['tabs'] as &$tab ) {
                        if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
                            $tab['fields'] = $this->apply_defaults_to_fields( $tab['fields'], $values, $field_path );
                        }
                    }
                }
            }

            return $fields;
        }
        
        /**
         * Get value at path (dot notation)
         */
        protected function get_value_at_path( $array, $path ) {
            $keys = explode( '.', $path );
            $current = $array;

            foreach ( $keys as $key ) {
                if ( ! is_array( $current ) || ! isset( $current[ $key ] ) ) {
                    return null;
                }
                $current = $current[ $key ];
            }

            return $current;
        }
        
        /**
         * Resolve dynamic options in schema
         */
        protected function resolve_dynamic_options_in_schema( $schema ) {
            if ( ! isset( $schema['pages'] ) || ! is_array( $schema['pages'] ) ) {
                return $schema;
            }

            foreach ( $schema['pages'] as &$page ) {
                if ( isset( $page['sections'] ) && is_array( $page['sections'] ) ) {
                    foreach ( $page['sections'] as &$section ) {
                        if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
                            $section['fields'] = $this->resolve_dynamic_options_in_fields( $section['fields'] );
                        }
                    }
                }
            }

            return $schema;
        }
        
        /**
         * Resolve dynamic options in fields recursively
         */
        protected function resolve_dynamic_options_in_fields( $fields ) {
            foreach ( $fields as &$field ) {
                // Resolve dynamic options for select, radio, button_set, checkbox
                if ( in_array( $field['type'], array( 'select', 'radio', 'button_set', 'checkbox' ) ) ) {
                    if ( isset( $field['options'] ) && is_string( $field['options'] ) ) {
                        $resolved = $this->resolve_dynamic_options( $field['options'] );
                        if ( ! empty( $resolved ) ) {
                            $field['options'] = $resolved;
                        }
                    }
                }

                // Handle nested fields
                if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                    $field['fields'] = $this->resolve_dynamic_options_in_fields( $field['fields'] );
                }
                
                if ( isset( $field['tabs'] ) && is_array( $field['tabs'] ) ) {
                    foreach ( $field['tabs'] as &$tab ) {
                        if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
                            $tab['fields'] = $this->resolve_dynamic_options_in_fields( $tab['fields'] );
                        }
                    }
                }
            }

            return $fields;
        }



        /**
         * Decode HTML entities in schema (titles, descriptions, etc.)
         */
        protected function decode_html_entities_in_schema( $schema ) {
            if ( ! isset( $schema['pages'] ) || ! is_array( $schema['pages'] ) ) {
                return $schema;
            }

            foreach ( $schema['pages'] as &$page ) {
                if ( isset( $page['title'] ) ) {
                    $page['title'] = html_entity_decode( $page['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                }
                
                if ( isset( $page['sections'] ) && is_array( $page['sections'] ) ) {
                    foreach ( $page['sections'] as &$section ) {
                        if ( isset( $section['title'] ) ) {
                            $section['title'] = html_entity_decode( $section['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                        }
                        
                        if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
                            $section['fields'] = $this->decode_html_entities_in_fields( $section['fields'] );
                        }
                    }
                }
            }

            return $schema;
        }
        
        /**
         * Decode HTML entities in fields recursively
         */
        protected function decode_html_entities_in_fields( $fields ) {
            foreach ( $fields as &$field ) {
                if ( isset( $field['title'] ) ) {
                    $field['title'] = html_entity_decode( $field['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                }
                
                if ( isset( $field['desc'] ) ) {
                    // Don't decode desc as it may contain intentional HTML
                    // The React app will render it with dangerouslySetInnerHTML
                }

                // Handle nested fields
                if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                    $field['fields'] = $this->decode_html_entities_in_fields( $field['fields'] );
                }
                
                if ( isset( $field['tabs'] ) && is_array( $field['tabs'] ) ) {
                    foreach ( $field['tabs'] as &$tab ) {
                        if ( isset( $tab['title'] ) ) {
                            $tab['title'] = html_entity_decode( $tab['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                        }
                        
                        if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
                            $tab['fields'] = $this->decode_html_entities_in_fields( $tab['fields'] );
                        }
                    }
                }
            }

            return $fields;
        }

        /**
         * Resolve dynamic options (like 'post_types', 'roles', 'pages')
         * 
         * @param string $option_key The dynamic option key
         * @return array Resolved options array or empty array if not resolvable
         */
        protected function resolve_dynamic_options( $option_key ) {
            $options = array();

            switch ( $option_key ) {
                case 'post_types':
                    // Get all registered post types
                    $post_types = get_post_types( array( 'public' => true ), 'objects' );
                    foreach ( $post_types as $post_type ) {
                        $options[ $post_type->name ] = $post_type->label;
                    }
                    // Also include non-public post types that might be used
                    $all_post_types = get_post_types( array(), 'objects' );
                    foreach ( $all_post_types as $post_type ) {
                        if ( ! isset( $options[ $post_type->name ] ) ) {
                            $options[ $post_type->name ] = $post_type->label;
                        }
                    }
                    break;

                case 'roles':
                    // Get all WordPress user roles
                    global $wp_roles;
                    if ( ! isset( $wp_roles ) ) {
                        $wp_roles = new WP_Roles();
                    }
                    foreach ( $wp_roles->get_names() as $role_key => $role_name ) {
                        $options[ $role_key ] = translate_user_role( $role_name );
                    }
                    break;

                case 'pages':
                    // Get all WordPress pages
                    $pages = get_pages( array( 'number' => 1000 ) );
                    foreach ( $pages as $page ) {
                        $options[ $page->ID ] = $page->post_title;
                    }
                    break;

                default:
                    // Allow other plugins to provide dynamic options
                    $options = apply_filters( 'wp_ulike_resolve_dynamic_options_' . $option_key, array(), $option_key );
                    break;
            }

            return $options;
        }

        /**
         * Get current option values
         */
        protected function get_values() {
            $values = get_option( $this->option_domain, array() );

            // Ensure values is an array
            if ( ! is_array( $values ) ) {
                $values = array();
            }

            // Apply filter for value transformation
            return apply_filters( 'wp_ulike_optiwich_values', $values );
        }

        /**
         * Save settings values
         * Can accept either a REST API request object or direct values array
         */
        public function save_settings( $request_or_values = null ) {
            // Handle both REST API request object and direct values array
            if ( is_object( $request_or_values ) && method_exists( $request_or_values, 'get_json_params' ) ) {
                $values = $request_or_values->get_json_params();
            } elseif ( is_array( $request_or_values ) ) {
                $values = $request_or_values;
            } else {
                return new WP_Error(
                    'invalid_data',
                    esc_html__( 'Invalid request data. Expected an object with setting values.', 'wp-ulike' ),
                    array( 'status' => 400 )
                );
            }

            if ( ! is_array( $values ) ) {
                return new WP_Error(
                    'invalid_data',
                    esc_html__( 'Invalid request data. Expected an object with setting values.', 'wp-ulike' ),
                    array( 'status' => 400 )
                );
            }

            // Security: Recursively sanitize all string values to prevent XSS
            $values = $this->sanitize_settings_values( $values );

            // Apply filter before saving
            $values = apply_filters( 'wp_ulike_optiwich_save_values', $values );

            // Save as serialized array in option
            update_option( $this->option_domain, $values );

            // Clear schema cache
            $this->schema_cache = null;

            // Trigger saved action
            do_action( 'wp_ulike_settings_saved', $values );

            return array(
                'success' => true,
                'message' => esc_html__( 'Settings saved successfully.', 'wp-ulike' ),
            );
        }

        /**
         * Recursively sanitize settings values to prevent XSS
         * 
         * @param mixed $values Values to sanitize
         * @return mixed Sanitized values
         */
        protected function sanitize_settings_values( $values ) {
            if ( is_array( $values ) ) {
                $sanitized = array();
                foreach ( $values as $key => $value ) {
                    // Sanitize array keys
                    $sanitized_key = sanitize_key( $key );
                    // Recursively sanitize values
                    $sanitized[ $sanitized_key ] = $this->sanitize_settings_values( $value );
                }
                return $sanitized;
            } elseif ( is_string( $values ) ) {
                // Sanitize string values (allows HTML but escapes dangerous scripts)
                // For code editors and textareas that may contain HTML, use wp_kses_post
                // For simple text fields, you might want to use sanitize_text_field instead
                return wp_kses_post( $values );
            } elseif ( is_bool( $values ) || is_int( $values ) || is_float( $values ) ) {
                // Keep boolean and numeric values as-is
                return $values;
            } else {
                // For other types (null, objects, etc.), return as-is
                return $values;
            }
        }

    }

    // Initialize the API - only if not already initialized
    if ( ! isset( $GLOBALS['wp_ulike_settings_api'] ) ) {
        $GLOBALS['wp_ulike_settings_api'] = new wp_ulike_settings_api();
    }
}

