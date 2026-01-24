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
         * Field types that are informational only (not form fields)
         */
        const INFORMATIONAL_FIELD_TYPES = array( 'submessage', 'content', 'heading', 'subheading', 'callback' );

        /**
         * Field types that contain nested fields
         */
        const NESTED_FIELD_TYPES = array( 'group', 'repeater', 'fieldset' );

        /**
         * Field properties to preserve when converting callbacks to content
         */
        const PRESERVE_PROPERTIES = array( 'dependency', 'title', 'desc', 'help', 'class', 'attributes' );

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

            // Get schema using local method
            $schema = $this->get_optiwich_schema();

            // Schema is already processed (callbacks rendered, IDs generated) by get_optiwich_schema()
            // No need to clean fields anymore - all fields are supported
            return $schema;
        }

        /**
         * Get Optiwich schema structure
         * Converts sections to pages structure for React consumption
         *
         * @return array Schema structure with pages and sections
         */
        public function get_optiwich_schema() {
            // Get sections from admin panel
            global $wp_ulike_admin_panel;

            // If admin panel doesn't exist, try to create it
            if ( ! isset( $wp_ulike_admin_panel ) && class_exists( 'wp_ulike_admin_panel' ) ) {
                $wp_ulike_admin_panel = new wp_ulike_admin_panel();
            }

            // Get sections from admin panel
            if ( isset( $wp_ulike_admin_panel ) && method_exists( $wp_ulike_admin_panel, 'register_sections' ) ) {
                $sections = $wp_ulike_admin_panel->register_sections();
            } else {
                $sections = array();
            }

            // Build pages structure from sections
            $pages = $this->build_pages_structure( $sections );

            return array( 'pages' => apply_filters( 'wp_ulike_optiwich_pages', $pages ) );
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

        /**
         * Build pages structure from sections (single-pass optimization)
         * Converts sections to pages structure for React consumption
         *
         * @param array $sections Sections array from register_sections()
         * @return array Pages structure
         */
        public function build_pages_structure( $sections ) {
            $pages = array();
            $pages_map = array();

            // Single pass: build pages and process sections simultaneously
            foreach ( $sections as $section ) {
                // Top-level page (has id, no parent)
                if ( isset( $section['id'] ) && ! isset( $section['parent'] ) ) {
                    $page = $this->create_page( $section );
                    $pages[] = $page;
                    // Store reference to last element for adding fields later
                    $pages_map[ $section['id'] ] = &$pages[ count( $pages ) - 1 ];

                    // If this top-level page has fields, add them immediately
                    if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
                        $processed_fields = $this->process_field_callbacks( $section['fields'] );
                        if ( ! empty( $processed_fields ) ) {
                            $section_data = array(
                                'id'     => 'section',
                                'title'  => $section['title'] ?? '',
                                'fields' => array_values( $processed_fields ),
                            );
                            
                            // Preserve icon and is_pro if present
                            if ( isset( $section['icon'] ) ) {
                                $section_data['icon'] = $section['icon'];
                            }
                            if ( isset( $section['is_pro'] ) ) {
                                $section_data['is_pro'] = $section['is_pro'];
                            }
                            
                            $pages_map[ $section['id'] ]['sections'][] = $section_data;
                        }
                    }
                }
                // Child page (has parent)
                elseif ( isset( $section['parent'] ) ) {
                    $pages[] = $this->build_child_page( $section );
                }
            }

            return $pages;
        }

        /**
         * Create a page structure from section
         *
         * @param array $section Section data
         * @return array Page structure
         */
        protected function create_page( $section ) {
            $page = array(
                'id'       => $section['id'] ?? '',
                'title'    => $section['title'] ?? '',
                'sections' => array(),
            );
            
            // Preserve icon if present
            if ( isset( $section['icon'] ) ) {
                $page['icon'] = $section['icon'];
            }
            
            return $page;
        }

        /**
         * Build child page with sections from fieldsets
         *
         * @param array $section Section data with parent
         * @return array Child page structure
         */
        protected function build_child_page( $section ) {
            // Use explicit ID if provided, otherwise generate from title (fallback)
            // Note: Explicit IDs should always be provided in admin panel for URL-friendly, stable identifiers
            if ( ! isset( $section['id'] ) || empty( $section['id'] ) ) {
                // Fallback: generate ID from title (may create non-ASCII IDs for non-English titles)
                $generated_id = sanitize_title( $section['title'] ?? '' );
                if ( empty( $generated_id ) ) {
                    $generated_id = 'section';
                }
                // Log warning in development to encourage explicit IDs
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( sprintf( 
                        '[WP ULike] Section "%s" is missing explicit "id" field. Generated ID: "%s". Please add explicit ASCII ID in admin panel.',
                        $section['title'] ?? 'Unknown',
                        $generated_id
                    ) );
                }
                $section['id'] = $generated_id;
            }
            
            $child_page = array(
                'id'       => $section['id'],
                'title'    => $section['title'] ?? '',
                'parent'   => $section['parent'],
                'sections' => array(),
            );
            
            // Preserve icon if present
            if ( isset( $section['icon'] ) ) {
                $child_page['icon'] = $section['icon'];
            }
            
            // Preserve is_pro if present
            if ( isset( $section['is_pro'] ) ) {
                $child_page['is_pro'] = $section['is_pro'];
            }

            if ( ! isset( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
                return $child_page;
            }

            // Extract section fieldsets and regular fields
            $extracted = $this->extract_section_fieldsets( $section['fields'] );

            // Add extracted fieldset sections
            foreach ( $extracted['fieldset_sections'] as $fieldset_section ) {
                // Mark sections derived from fieldsets as grouping sections
                $fieldset_section['is_grouping_section'] = true;
                $child_page['sections'][] = $fieldset_section;
            }

            // Add regular fields section if needed
            if ( $extracted['should_create_regular_section'] && ! empty( $extracted['regular_fields'] ) ) {
                $processed_fields = $this->process_field_callbacks( $extracted['regular_fields'] );
                if ( ! empty( $processed_fields ) ) {
                    $section_data = array(
                        'id'     => $section['id'] ?? 'section',
                        'title'  => $section['title'] ?? '',
                        'fields' => array_values( $processed_fields ),
                    );
                    
                    // Preserve icon and is_pro if present
                    if ( isset( $section['icon'] ) ) {
                        $section_data['icon'] = $section['icon'];
                    }
                    if ( isset( $section['is_pro'] ) ) {
                        $section_data['is_pro'] = $section['is_pro'];
                    }
                    
                    $child_page['sections'][] = $section_data;
                }
            }

            return $child_page;
        }

        /**
         * Extract fieldsets marked as sections from fields array
         *
         * @param array $fields Fields array
         * @return array Array with 'fieldset_sections', 'regular_fields', and 'should_create_regular_section'
         */
        protected function extract_section_fieldsets( $fields ) {
            $regular_fields = array();
            $fieldset_sections = array();
            $has_section_fieldsets = false;

            foreach ( $fields as $field ) {
                // Check if fieldset is marked to display as a section
                if ( $this->is_fieldset_section( $field ) ) {
                    $has_section_fieldsets = true;
                    $processed_fields = $this->process_field_callbacks( $field['fields'] );
                    $fieldset_section = array(
                        'id'     => $field['id'],
                        'title'  => $field['title'] ?? '',
                        'fields' => array_values( $processed_fields ),
                    );
                    
                    // Preserve icon and is_pro if present
                    if ( isset( $field['icon'] ) ) {
                        $fieldset_section['icon'] = $field['icon'];
                    }
                    if ( isset( $field['is_pro'] ) ) {
                        $fieldset_section['is_pro'] = $field['is_pro'];
                    }
                    
                    $fieldset_sections[] = $fieldset_section;
                } else {
                    // All other fields go to regular fields
                    $regular_fields[] = $field;
                }
            }

            // Determine if regular section should be created
            $should_create_regular_section = false;
            if ( ! $has_section_fieldsets ) {
                // No section-fieldsets: always create regular fields section if fields exist
                $should_create_regular_section = ! empty( $regular_fields );
            } else {
                // Section-fieldsets exist: only create regular section if there are form fields
                $should_create_regular_section = $this->has_form_fields( $regular_fields );
            }

            return array(
                'fieldset_sections'            => $fieldset_sections,
                'regular_fields'               => $regular_fields,
                'should_create_regular_section' => $should_create_regular_section,
            );
        }

        /**
         * Check if fieldset should be displayed as a section
         *
         * @param array $field Field data
         * @return bool True if fieldset marked as section
         */
        protected function is_fieldset_section( $field ) {
            return isset( $field['type'], $field['fields'], $field['id'] )
                && $field['type'] === 'fieldset'
                && isset( $field['display_as'] )
                && $field['display_as'] === 'section';
        }

        /**
         * Check if fields array contains actual form fields (not just informational)
         *
         * @param array $fields Fields array
         * @return bool True if contains form fields
         */
        protected function has_form_fields( $fields ) {
            foreach ( $fields as $field ) {
                $field_type = $field['type'] ?? '';
                // Skip only informational fields - fieldsets and other form fields count
                if ( ! in_array( $field_type, self::INFORMATIONAL_FIELD_TYPES, true ) ) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Process fields: convert callbacks to content, ensure IDs, handle nested structures
         *
         * @param array $fields Fields array
         * @return array Processed fields array
         */
        protected function process_field_callbacks( $fields ) {
            if ( ! is_array( $fields ) ) {
                return array();
            }

            return array_map( array( $this, 'process_single_field' ), $fields );
        }

        /**
         * Process a single field: handle callbacks, nested structures, and ID generation
         *
         * @param array $field Field data
         * @return array Processed field data
         */
        protected function process_single_field( $field ) {
            // Convert callback fields to content fields
            if ( $this->is_callback_field( $field ) ) {
                return $this->convert_callback_to_content( $field );
            }

            // Process nested field structures
            $field = $this->process_nested_fields( $field );

            // Ensure field has an ID
            if ( empty( $field['id'] ?? '' ) ) {
                $field['id'] = $this->generate_field_id( $field );
            }

            return $field;
        }

        /**
         * Check if field is a callback field
         *
         * @param array $field Field data
         * @return bool True if callback field
         */
        protected function is_callback_field( $field ) {
            return isset( $field['type'], $field['function'] )
                && $field['type'] === 'callback'
                && function_exists( $field['function'] );
        }

        /**
         * Convert callback field to content field
         *
         * @param array $field Callback field data
         * @return array Content field data
         */
        protected function convert_callback_to_content( $field ) {
            $args = $field['args'] ?? array();
            if ( ! is_array( $args ) ) {
                $args = array();
            }

            // Use output buffering to capture callback output (handles both echo and return)
            ob_start();
            try {
                $returned = call_user_func( $field['function'], $args );
                $output = ob_get_clean();
            } catch ( Exception $e ) {
                ob_end_clean();
                $output = '';
                $returned = '';
            }

            // Use output if callback echoed, otherwise use returned value
            $rendered = ! empty( $output ) ? $output : ( is_string( $returned ) ? $returned : '' );

            // Generate ID if not present
            $field_id = $field['id'] ?? 'callback_' . md5( serialize( array( $field['function'], $args ) ) );

            // Build converted field
            $converted_field = array(
                'id'      => $field_id,
                'type'    => 'content',
                'content' => $rendered,
            );

            // Preserve important field properties
            foreach ( self::PRESERVE_PROPERTIES as $prop ) {
                if ( isset( $field[ $prop ] ) ) {
                    $converted_field[ $prop ] = $field[ $prop ];
                }
            }

            return $converted_field;
        }

        /**
         * Process nested field structures (groups, fieldsets, tabbed fields)
         *
         * @param array $field Field data
         * @return array Field with processed nested structures
         */
        protected function process_nested_fields( $field ) {
            $field_type = $field['type'] ?? '';

            // Process nested fields in groups, repeaters, fieldsets
            if ( in_array( $field_type, self::NESTED_FIELD_TYPES, true ) && isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                $field['fields'] = $this->process_field_callbacks( $field['fields'] );
            }

            // Process tabbed fields (nested tabs with fields)
            if ( $field_type === 'tabbed' && isset( $field['tabs'] ) && is_array( $field['tabs'] ) ) {
                foreach ( $field['tabs'] as $tab_key => $tab ) {
                    if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
                        $field['tabs'][ $tab_key ]['fields'] = $this->process_field_callbacks( $tab['fields'] );
                    }
                }
            }

            return $field;
        }

        /**
         * Generate field ID for fields without one
         *
         * @param array $field Field data
         * @return string Generated field ID
         */
        protected function generate_field_id( $field ) {
            $field_type = $field['type'] ?? 'field';
            $field_content = $field['content'] ?? '';
            // Use simpler ID generation (md5 of type + content hash)
            return $field_type . '_' . md5( serialize( array( $field_type, $field_content ) ) );
        }

        /**
         * Get all translations for Optiwich
         * Returns all translatable strings used in the React app
         * Uses WordPress translation functions for .po/.pot file support
         * Converts WordPress sprintf format (%s, %d) to i18next format ({variable}) for React compatibility
         *
         * @return array Translations array with keys and translated values (in i18next format)
         */
        public function get_translations() {
            $translations = array(
                // Generic Errors
                'errors.generic' => esc_html__( 'An error occurred', 'wp-ulike' ),
                'errors.generic_refresh' => esc_html__( 'An error occurred. Please try refreshing the page.', 'wp-ulike' ),
                'errors.try_again' => esc_html__( 'Try Again', 'wp-ulike' ),
                'errors.failed' => esc_html__( 'Failed to %s', 'wp-ulike' ),

                // Actions
                'actions.save' => esc_html__( 'Save Changes', 'wp-ulike' ),
                'actions.saving' => esc_html__( 'Saving...', 'wp-ulike' ),
                'actions.reset' => esc_html__( 'Reset to Defaults', 'wp-ulike' ),
                'actions.resetting' => esc_html__( 'Resetting...', 'wp-ulike' ),
                'actions.remove' => esc_html__( 'Remove', 'wp-ulike' ),
                'actions.upload' => esc_html__( 'Upload', 'wp-ulike' ),
                'actions.import' => esc_html__( 'Import', 'wp-ulike' ),
                'actions.importing' => esc_html__( 'Importing...', 'wp-ulike' ),
                'actions.export' => esc_html__( 'Export & Download', 'wp-ulike' ),
                'actions.duplicate' => esc_html__( 'Duplicate', 'wp-ulike' ),
                'actions.move' => esc_html__( 'Move %s', 'wp-ulike' ),
                'actions.add' => esc_html__( 'Add %s', 'wp-ulike' ),
                'actions.menu_toggle' => esc_html__( 'Toggle menu', 'wp-ulike' ),

                // Media Library
                'media.library_unavailable' => esc_html__( 'WordPress media library is not available. Please ensure wp_enqueue_media() is called.', 'wp-ulike' ),
                'media.select' => esc_html__( 'Select %s', 'wp-ulike' ),
                'media.use' => esc_html__( 'Use this %s', 'wp-ulike' ),
                'media.no_url' => esc_html__( 'Selected %s has no URL', 'wp-ulike' ),
                'media.no_selection' => esc_html__( 'No file selected', 'wp-ulike' ),
                'media.preview' => esc_html__( 'Preview', 'wp-ulike' ),
                'media.url_format_error' => esc_html__( 'Invalid URL format from media library', 'wp-ulike' ),

                // Settings
                'settings.success' => esc_html__( 'Settings %s successfully!', 'wp-ulike' ),
                'settings.reset_success' => esc_html__( 'Settings have been reset to default values and saved successfully.', 'wp-ulike' ),
                'settings.reset_confirm' => esc_html__( 'Are you sure you want to reset all settings to their default values? This action cannot be undone.', 'wp-ulike' ),
                'settings.unsaved_warning' => esc_html__( 'You have unsaved changes. Are you sure you want to leave?', 'wp-ulike' ),
                'settings.validation_before_save' => esc_html__( 'Please fix the following errors before saving:%s', 'wp-ulike' ),
                'settings.import_save_failed' => esc_html__( 'Settings imported locally but failed to save to server. Please try saving again.', 'wp-ulike' ),

                // Validation
                'validation.invalid' => esc_html__( 'Invalid %s format', 'wp-ulike' ),
                'validation.invalid_example' => esc_html__( 'Invalid %1$s format. Please enter a valid %1$s (e.g., %2$s)', 'wp-ulike' ),
                'validation.url_protocol' => esc_html__( 'Invalid URL protocol. Only http, https, and data URLs are allowed.', 'wp-ulike' ),
                'validation.text_maxlength' => esc_html__( 'Text must be no more than %d characters', 'wp-ulike' ),
                'validation.number_min' => esc_html__( 'Value must be at least %d', 'wp-ulike' ),
                'validation.number_max' => esc_html__( 'Value must be at most %d', 'wp-ulike' ),
                'validation.upload_url_required' => esc_html__( 'Upload value must be a URL string', 'wp-ulike' ),
                'validation.media_format' => esc_html__( 'Media value must be an object with a url property or a valid URL string', 'wp-ulike' ),

                // Backup/Import
                'backup.import_title' => esc_html__( 'Import Settings', 'wp-ulike' ),
                'backup.import_desc' => esc_html__( 'Paste your exported settings JSON below and click Import to restore your configuration. The import should contain only setting values (not schema structure).', 'wp-ulike' ),
                'backup.import_placeholder' => esc_html__( 'Paste your JSON settings here...', 'wp-ulike' ),
                'backup.export_title' => esc_html__( 'Export Settings', 'wp-ulike' ),
                'backup.export_desc' => esc_html__( 'Copy the JSON below or download it as a file to backup your current settings. The export contains only your setting values (not the schema structure).', 'wp-ulike' ),
                'backup.json_invalid_syntax' => esc_html__( 'Invalid JSON format. Please check your JSON syntax.', 'wp-ulike' ),
                'backup.json_invalid_object' => esc_html__( 'Invalid JSON format. Expected an object with setting values.', 'wp-ulike' ),
                'backup.json_invalid_format' => esc_html__( 'Invalid format. Expected a JSON object with setting values only.', 'wp-ulike' ),
                'backup.no_values' => esc_html__( 'No values found in the import data.', 'wp-ulike' ),
                'backup.security_threat' => esc_html__( 'Security threat detected! The import contains potentially dangerous content:%s%sImport blocked for security reasons.', 'wp-ulike' ),
                'backup.validation_failed' => esc_html__( 'Validation failed! The imported data contains invalid values:%s%sPlease fix these errors and try again.', 'wp-ulike' ),

                // Code Editor
                'code_editor.tip' => esc_html__( 'Tip: Select text and click a tag button to wrap it, or click to insert at cursor', 'wp-ulike' ),

                // Select Field
                'select.placeholder_multiple' => esc_html__( 'Select options...', 'wp-ulike' ),
                'select.placeholder_single' => esc_html__( '-- Select --', 'wp-ulike' ),

                // Field Errors
                'field.no_options' => esc_html__( 'No options available for this field. Please check the schema definition.', 'wp-ulike' ),
                'field.options_unresolved' => esc_html__( 'Options not available. The dynamic options "%s" could not be resolved. Please check that the PHP backend properly resolves dynamic options in the schema.', 'wp-ulike' ),

                // Pro Lock
                'pro.feature' => esc_html__( 'Pro Feature', 'wp-ulike' ),
                'pro.description' => esc_html__( 'This feature is available in WP ULike Pro', 'wp-ulike' ),
                'pro.upgrade' => esc_html__( 'Upgrade to Pro', 'wp-ulike' ),
                'pro.read_more' => esc_html__( 'Read More', 'wp-ulike' ),
                'pro.tag' => esc_html__( 'Pro', 'wp-ulike' ),

                // Security
                'security.sql_injection' => esc_html__( 'SQL Injection', 'wp-ulike' ),
                'security.xss' => esc_html__( 'XSS', 'wp-ulike' ),
                'security.command_injection' => esc_html__( 'Command Injection', 'wp-ulike' ),
            );

            // Convert WordPress sprintf format to i18next format
            return $this->convert_sprintf_to_i18next( $translations );
        }

        /**
         * Convert WordPress sprintf format (%s, %d, %1$s) to i18next format ({variable})
         * This allows WordPress translations to work with i18next's named interpolation
         * Standard practice: Server prepares data in the format the client expects
         *
         * @param array $translations Translations array in WordPress sprintf format
         * @return array Converted translations array in i18next format
         */
        protected function convert_sprintf_to_i18next( $translations ) {
            // Mapping of WordPress sprintf patterns to i18next variable names
            $variable_mapping = array(
                'errors.failed' => array( '%s' => '{action}' ),
                'actions.move' => array( '%s' => '{direction}' ),
                'actions.add' => array( '%s' => '{type}' ),
                'media.select' => array( '%s' => '{type}' ),
                'media.use' => array( '%s' => '{type}' ),
                'media.no_url' => array( '%s' => '{type}' ),
                'settings.success' => array( '%s' => '{action}' ),
                'settings.validation_before_save' => array( '%s' => '{errorMessages}' ),
                'validation.invalid' => array( '%s' => '{type}' ),
                'validation.invalid_example' => array( '%1$s' => '{type}', '%2$s' => '{example}' ),
                'validation.text_maxlength' => array( '%d' => '{maxlength}' ),
                'validation.number_min' => array( '%d' => '{min}' ),
                'validation.number_max' => array( '%d' => '{max}' ),
                'backup.security_threat' => array( '%1$s' => '{threatList}', '%2$s' => '\n\n' ),
                'backup.validation_failed' => array( '%1$s' => '{errorList}', '%2$s' => '\n\n' ),
                'field.options_unresolved' => array( '%s' => '{options}' ),
            );

            foreach ( $translations as $key => $translation ) {
                if ( isset( $variable_mapping[ $key ] ) ) {
                    $mapping = $variable_mapping[ $key ];
                    foreach ( $mapping as $sprintf_pattern => $i18next_var ) {
                        // Use preg_quote to properly escape regex special characters
                        // Only escape $ (for %1$s format), % is not a regex special character
                        $pattern = preg_quote( $sprintf_pattern, '/' );
                        $translations[ $key ] = preg_replace( '/' . $pattern . '/', $i18next_var, $translations[ $key ] );
                    }
                }
            }

            return $translations;
        }

    }

    // Initialize the API - only if not already initialized
    if ( ! isset( $GLOBALS['wp_ulike_settings_api'] ) ) {
        $GLOBALS['wp_ulike_settings_api'] = new wp_ulike_settings_api();
    }
}

