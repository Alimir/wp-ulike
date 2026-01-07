<?php
/**
 * WP ULike Customizer REST API
 *
 * Provides REST API endpoints for Optiwich Customizer integration
 * Extracts schema from ULF customizer sections without modifying source code
 *
 * @package WP_ULike
 * @since 4.6.0
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_customizer_api' ) ) {
    class wp_ulike_customizer_api {

        /**
         * Option domain
         */
        protected $option_domain = 'wp_ulike_customize';

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
        const NESTED_FIELD_TYPES = array( 'group', 'repeater', 'fieldset', 'tabbed' );

        /**
         * Collected sections storage
         */
        private static $collected_sections = array();

        /**
         * Constructor
         */
        public function __construct() {
            // Admin-ajax handlers are registered in admin-ajax.php
            // This class provides the methods that admin-ajax.php calls
            
            // Hook early to collect sections as they're registered
            add_filter( 'wp_ulike_optiwich_customizer_section', array( $this, 'collect_section' ), 10, 2 );
        }

        /**
         * Collect section via filter hook
         *
         * @param array $section Section data
         * @param string $option_domain Option domain
         * @return array Section data (unchanged)
         */
        public function collect_section( $section, $option_domain ) {
            if ( $option_domain === $this->option_domain ) {
                self::$collected_sections[] = $section;
            }
            return $section;
        }

        /**
         * Get customizer schema
         * Returns schema structure extracted from ULF customizer sections
         */
        public function get_schema( $request = null ) {
            // Return cached schema if available
            if ( $this->schema_cache !== null ) {
                return $this->schema_cache;
            }

            // Build schema from ULF customizer sections
            $schema = $this->build_schema_from_customizer();

            // Apply default values from saved customizer options
            $values = $this->get_customizer_values();
            $schema = $this->apply_defaults_to_schema( $schema, $values );

            // Decode HTML entities in titles and descriptions
            $schema = $this->decode_html_entities_in_schema( $schema );

            // Add assets URLs to schema
            $schema['assets'] = $this->get_plugin_assets();

            // Cache the schema
            $this->schema_cache = $schema;

            return apply_filters( 'wp_ulike_optiwich_customizer_schema', $schema );
        }

        /**
         * Get plugin assets URLs (CSS/JS)
         *
         * @return array Assets URLs
         */
        protected function get_plugin_assets() {
            $assets = array(
                'css' => '',
                'js' => ''
            );

            // Try to get WP ULike front-end CSS/JS URLs
            if ( function_exists( 'wp_ulike_get_frontend_assets' ) ) {
                $plugin_assets = wp_ulike_get_frontend_assets();
                $assets['css'] = isset( $plugin_assets['css'] ) ? $plugin_assets['css'] : '';
                $assets['js'] = isset( $plugin_assets['js'] ) ? $plugin_assets['js'] : '';
            } else {
                // Fallback: use WP_ULIKE_ASSETS_URL constant
                if ( defined( 'WP_ULIKE_ASSETS_URL' ) ) {
                    $assets['css'] = WP_ULIKE_ASSETS_URL . '/css/wp-ulike.min.css';
                    $assets['js'] = WP_ULIKE_ASSETS_URL . '/js/wp-ulike.min.js';
                } else {
                    // Last resort: try to construct from plugin URL
                    $plugin_url = plugins_url( '', WP_ULIKE_PLUGIN_DIR . '/wp-ulike.php' );
                    $assets['css'] = $plugin_url . '/assets/css/wp-ulike.min.css';
                    $assets['js'] = $plugin_url . '/assets/js/wp-ulike.min.js';
                }
            }

            // Convert relative URLs to absolute
            if ( ! empty( $assets['css'] ) && strpos( $assets['css'], 'http' ) !== 0 ) {
                $assets['css'] = site_url( $assets['css'] );
            }
            if ( ! empty( $assets['js'] ) && strpos( $assets['js'], 'http' ) !== 0 ) {
                $assets['js'] = site_url( $assets['js'] );
            }

            return $assets;
        }

        /**
         * Build schema from ULF customizer sections
         */
        protected function build_schema_from_customizer() {
            // Ensure customizer is loaded
            if ( ! did_action( 'wp_ulike_customize_loaded' ) ) {
                do_action( 'wp_ulike_customize_loaded' );
            }

            // Get sections from ULF customizer
            $sections = $this->get_ulf_customizer_sections();

            // Build pages structure from sections
            $pages = $this->build_pages_structure( $sections );

            return array( 'pages' => apply_filters( 'wp_ulike_optiwich_customizer_pages', $pages ) );
        }

        /**
         * Get ULF customizer sections
         * Extracts sections from ULF framework's customizer structure
         *
         * @return array Sections array
         */
        protected function get_ulf_customizer_sections() {
            // Use static cache
            static $cached_sections = null;

            if ( $cached_sections !== null ) {
                return $cached_sections;
            }

            // Ensure API class instance exists and filter hook is active
            // This ensures sections will be collected when customizer registers them
            global $wp_ulike_customizer_api;
            if ( ! isset( $wp_ulike_customizer_api ) && class_exists( 'wp_ulike_customizer_api' ) ) {
                $wp_ulike_customizer_api = new wp_ulike_customizer_api();
            }

            // If sections were already collected (from normal WordPress load), use them
            if ( ! empty( self::$collected_sections ) && did_action( 'wp_ulike_customize_loaded' ) ) {
                $cached_sections = self::$collected_sections;
                return $cached_sections;
            }

            // Clear collected sections before triggering registration
            self::$collected_sections = array();

            // Ensure customizer class is loaded
            if ( ! class_exists( 'wp_ulike_customizer' ) ) {
                // Customizer class should be loaded via includes/index.php
                // If not, return empty array
                $cached_sections = array();
                return $cached_sections;
            }

            // Ensure customizer is loaded and sections are registered
            // Trigger customizer registration if not already done
            if ( ! did_action( 'wp_ulike_customize_loaded' ) ) {
                // ULF framework should be loaded first
                if ( ! did_action( 'ulf_loaded' ) ) {
                    do_action( 'ulf_loaded' );
                } else {
                    // If ulf_loaded already fired, we need to manually trigger register_panel
                    // by calling ulf_loaded again (WordPress allows multiple calls)
                    // This ensures register_panel runs with our filter hook active
                    do_action( 'ulf_loaded' );
                }
            } else {
                // If wp_ulike_customize_loaded already fired but we don't have sections,
                // it means sections were registered before our filter hook was active
                // Try to trigger registration again
                do_action( 'ulf_loaded' );
            }

            // Wait for customizer to finish registering sections
            if ( ! did_action( 'wp_ulike_customize_ended' ) ) {
                do_action( 'wp_ulike_customize_ended' );
            }

            // Use collected sections
            $cached_sections = is_array( self::$collected_sections ) ? self::$collected_sections : array();

            return $cached_sections;
        }

        /**
         * Build pages structure from customizer sections
         * Converts ULF customizer sections to pages structure for React consumption
         *
         * @param array $sections Sections array from ULF
         * @return array Pages structure
         */
        public function build_pages_structure( $sections ) {
            $pages = array();
            $parent_section_id = null;

            // Find parent section (the one without parent)
            foreach ( $sections as $section ) {
                if ( isset( $section['id'] ) && ! isset( $section['parent'] ) ) {
                    $parent_section_id = $section['id'];
                    break;
                }
            }

            // If no parent found, create a default parent
            if ( ! $parent_section_id ) {
                $parent_section_id = 'wp_ulike';
            }

            // Create parent page
            $parent_page = array(
                'id'       => $parent_section_id,
                'title'    => esc_html__( 'WP ULike', 'wp-ulike' ),
                'sections' => array(),
            );

            // Process child sections (those with parent)
            foreach ( $sections as $section ) {
                if ( isset( $section['parent'] ) && $section['parent'] === $parent_section_id ) {
                    // This is a child section - create a page for it
                    $page = array(
                        'id'       => $section['id'] ?? sanitize_title( $section['title'] ?? 'section' ),
                        'title'    => $section['title'] ?? '',
                        'parent'   => $parent_section_id,
                        'sections' => array(),
                    );

                    // Process fields in this section
                    if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
                        $processed_fields = $this->process_field_callbacks( $section['fields'] );
                        if ( ! empty( $processed_fields ) ) {
                            $section_data = array(
                                'id'     => $section['id'] ?? 'section',
                                'title'  => $section['title'] ?? '',
                                'fields' => array_values( $processed_fields ),
                            );

                            // Add template property if section has one (for customizer preview)
                            if ( isset( $section['template'] ) ) {
                                $section_data['template'] = $section['template'];
                            }
                            // Also check for icon
                            if ( isset( $section['icon'] ) ) {
                                $section_data['icon'] = $section['icon'];
                            }

                            $page['sections'][] = $section_data;
                        }
                    }

                    $pages[] = $page;
                } elseif ( ! isset( $section['parent'] ) && isset( $section['fields'] ) ) {
                    // Parent section with fields - add to parent page
                    $processed_fields = $this->process_field_callbacks( $section['fields'] );
                    if ( ! empty( $processed_fields ) ) {
                        $parent_page['sections'][] = array(
                            'id'     => 'section',
                            'title'  => $section['title'] ?? '',
                            'fields' => array_values( $processed_fields ),
                        );
                    }
                }
            }

            // Add parent page first if it has sections
            if ( ! empty( $parent_page['sections'] ) ) {
                array_unshift( $pages, $parent_page );
            }

            return $pages;
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

            // Use output buffering to capture callback output
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
            $preserve_props = array( 'dependency', 'title', 'desc', 'help', 'class', 'attributes' );
            foreach ( $preserve_props as $prop ) {
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
            $field_content = $field['content'] ?? $field['title'] ?? '';
            return $field_type . '_' . md5( serialize( array( $field_type, $field_content ) ) );
        }

        /**
         * Apply default values from customizer options to schema
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

                // Get value from customizer options if exists, otherwise use default
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
         * Decode HTML entities in schema
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
         * Get customizer values
         */
        public function get_values( $request = null ) {
            $values = get_option( $this->option_domain, array() );

            // Ensure values is an array
            if ( ! is_array( $values ) ) {
                $values = array();
            }

            // Apply filter for value transformation
            return apply_filters( 'wp_ulike_optiwich_customizer_values', $values );
        }

        /**
         * Get customizer values (internal method)
         */
        protected function get_customizer_values() {
            return $this->get_values();
        }

        /**
         * Save customizer values
         */
        public function save_values( $request_or_values = null ) {
            // Handle both REST API request object and direct values array
            if ( is_object( $request_or_values ) && method_exists( $request_or_values, 'get_json_params' ) ) {
                $values = $request_or_values->get_json_params();
            } elseif ( is_array( $request_or_values ) ) {
                $values = $request_or_values;
            } else {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Invalid request data. Expected an object with customizer values.', 'wp-ulike' ),
                ), 400 );
                return;
            }

            if ( ! is_array( $values ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Invalid request data. Expected an object with customizer values.', 'wp-ulike' ),
                ), 400 );
                return;
            }

            // Security: Recursively sanitize all string values to prevent XSS
            $values = $this->sanitize_customizer_values( $values );

            // Apply filter before saving
            $values = apply_filters( 'wp_ulike_optiwich_save_customizer_values', $values );

            // Save as serialized array in option
            update_option( $this->option_domain, $values );

            // Clear schema cache
            $this->schema_cache = null;

            // Trigger saved action
            do_action( 'wp_ulike_customizer_saved', $values );

            wp_send_json_success( array(
                'message' => esc_html__( 'Customizer settings saved successfully.', 'wp-ulike' ),
            ) );
        }

        /**
         * Recursively sanitize customizer values to prevent XSS
         *
         * @param mixed $values Values to sanitize
         * @return mixed Sanitized values
         */
        protected function sanitize_customizer_values( $values ) {
            if ( is_array( $values ) ) {
                $sanitized = array();
                foreach ( $values as $key => $value ) {
                    // Sanitize array keys
                    $sanitized_key = sanitize_key( $key );
                    // Recursively sanitize values
                    $sanitized[ $sanitized_key ] = $this->sanitize_customizer_values( $value );
                }
                return $sanitized;
            } elseif ( is_string( $values ) ) {
                // Sanitize string values (allows HTML but escapes dangerous scripts)
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
         * Get preview HTML for customizer
         * Returns rendered WP ULike templates with current customizer styles
         */
        public function get_preview( $request = null ) {
            // Get template type from request
            $template_type = isset( $_REQUEST['template'] ) ? sanitize_text_field( $_REQUEST['template'] ) : 'button';

            // Get current customizer values
            $customizer_values = $this->get_customizer_values();

            // Generate CSS from customizer values
            $css = $this->generate_css_from_values( $customizer_values );

            // Render template preview
            $preview_html = $this->render_template_preview( $template_type );

            // Get WP ULike front-end assets URLs using the same method as schema
            $plugin_assets = $this->get_plugin_assets();
            $css_url = isset( $plugin_assets['css'] ) ? $plugin_assets['css'] : '';
            $js_url = isset( $plugin_assets['js'] ) ? $plugin_assets['js'] : '';

            // Build full HTML with styles and WP ULike assets
            // Use absolute URLs and ensure proper isolation
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP ULike Customizer Preview</title>
    <base href="' . esc_url( site_url() ) . '/">';
            
            // Include WP ULike CSS if available
            if ( ! empty( $css_url ) ) {
                // Convert relative URL to absolute if needed
                if ( strpos( $css_url, 'http' ) !== 0 ) {
                    $css_url = site_url( $css_url );
                }
                $html .= '<link rel="stylesheet" href="' . esc_url( $css_url ) . '" id="wp-ulike-styles">';
            }
            
            $html .= '<style id="optiwich-preview-base-styles">
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .wpulike {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #optiwich-customizer-styles {
            display: block !important;
        }
    </style>
</head>
<body>
    ' . $preview_html;
            
            // Include WP ULike JS if available
            if ( ! empty( $js_url ) ) {
                // Convert relative URL to absolute if needed
                if ( strpos( $js_url, 'http' ) !== 0 ) {
                    $js_url = site_url( $js_url );
                }
                $html .= '<script src="' . esc_url( $js_url ) . '" id="wp-ulike-scripts"></script>';
            }
            
            // Add wp_ulike_params for JS functionality
            $wp_ulike_params = array(
                'ajax_url'      => admin_url( 'admin-ajax.php' ),
                'notifications' => wp_ulike_get_option( 'enable_toast_notice' ) ? true : false
            );
            $html .= '<script>
                window.wp_ulike_params = ' . wp_json_encode( $wp_ulike_params ) . ';
            </script>';
            
            $html .= '</body>
</html>';

            wp_send_json_success( array(
                'html' => $html,
            ) );
        }

        /**
         * Generate CSS from customizer values
         * This should match the CSS generation logic used on front-end
         *
         * @param array $values Customizer values
         * @return string Generated CSS
         */
        protected function generate_css_from_values( $values ) {
            // This is a placeholder - actual CSS generation should match front-end logic
            // For now, return empty string - React app will generate CSS client-side
            return '';
        }

        /**
         * Render template preview
         *
         * @param string $template_type Template type (button, toast, etc.)
         * @return string Rendered HTML
         */
        protected function render_template_preview( $template_type ) {
            // Render WP ULike template based on type
            ob_start();

            switch ( $template_type ) {
                case 'button':
                    // Render button template using WP ULike functions if available
                    if ( function_exists( 'wp_ulike' ) ) {
                        // Use wp_ulike function to render actual button
                        // Get a sample post ID for preview
                        $sample_post_id = get_posts( array(
                            'numberposts' => 1,
                            'post_status' => 'publish',
                            'fields' => 'ids'
                        ) );
                        
                        if ( ! empty( $sample_post_id ) ) {
                            echo wp_ulike( 'put', array( 'id' => $sample_post_id[0] ) );
                        } else {
                            // Fallback: render basic button structure
                            $this->render_button_preview();
                        }
                    } else {
                        // Fallback: render basic button structure
                        $this->render_button_preview();
                    }
                    break;

                case 'toast':
                    // Render all 4 toast notification types with exact WP ULike HTML structure
                    // Ensure no inline styles that conflict with customizer styles
                    echo '<div class="wpulike-notification" style="position: relative; min-height: 200px; padding: 20px; width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 10px;">';
                    
                    // Info message (default)
                    echo '<div class="wpulike-message">';
                    echo '<strong>Info:</strong> Please wait...';
                    echo '</div>';
                    
                    // Success message
                    echo '<div class="wpulike-message wpulike-success">';
                    echo '<strong>Success!</strong> You liked this post.';
                    echo '</div>';
                    
                    // Error message
                    echo '<div class="wpulike-message wpulike-error">';
                    echo '<strong>Error!</strong> Something went wrong.';
                    echo '</div>';
                    
                    // Warning message
                    echo '<div class="wpulike-message wpulike-warning">';
                    echo '<strong>Warning!</strong> Please check your settings.';
                    echo '</div>';
                    
                    echo '</div>';
                    break;

                case 'likers':
                    // Render likers list using actual WP ULike function with dummy data for preview
                    if ( function_exists( 'wp_ulike_get_likers_template' ) ) {
                        // Get a sample post ID for preview
                        $sample_post_id = get_posts( array(
                            'numberposts' => 1,
                            'post_status' => 'publish',
                            'fields' => 'ids'
                        ) );
                        
                        // Get settings for likers template
                        $setting_key = 'posts_group';
                        $table_name = 'ulike';
                        $column_name = 'post_id';
                        $item_id = ! empty( $sample_post_id ) ? $sample_post_id[0] : 1;
                        
                        // Get options to determine template structure
                        $options = wp_ulike_get_option( $setting_key );
                        $counter = ! empty( $options['likers_count'] ) ? absint( $options['likers_count'] ) : 10;
                        $template = ! empty( $options['likers_template'] ) ? wp_kses_post( $options['likers_template'] ) : '<div class="wp-ulike-likers-list">%START_WHILE%<span class="wp-ulike-liker"><a href="#" title="%USER_NAME%">%USER_AVATAR%</a></span>%END_WHILE%</div>';
                        $avatar_size = ! empty( $options['likers_gravatar_size'] ) ? absint( $options['likers_gravatar_size'] ) : 64;
                        
                        // Try to get actual likers
                        $get_users = wp_ulike_get_likers_list_per_post( $table_name, $column_name, $item_id, NULL );
                        
                        // If no likers, create dummy data for preview
                        if ( empty( $get_users ) ) {
                            // Create dummy user IDs for preview
                            $dummy_users = array();
                            $dummy_count = min( $counter, 5 ); // Show max 5 dummy users
                            for ( $i = 1; $i <= $dummy_count; $i++ ) {
                                // Try to get real users first
                                $users = get_users( array( 'number' => $dummy_count, 'offset' => $i - 1 ) );
                                if ( ! empty( $users ) && isset( $users[ $i - 1 ] ) ) {
                                    $dummy_users[] = $users[ $i - 1 ]->ID;
                                } else {
                                    // Fallback: use admin user or create dummy IDs
                                    $admin_user = get_user_by( 'login', 'admin' );
                                    if ( $admin_user ) {
                                        $dummy_users[] = $admin_user->ID;
                                    }
                                }
                            }
                            // If still no users, use admin or first available user
                            if ( empty( $dummy_users ) ) {
                                $all_users = get_users( array( 'number' => 1 ) );
                                if ( ! empty( $all_users ) ) {
                                    $dummy_users = array( $all_users[0]->ID );
                                }
                            }
                            $get_users = $dummy_users;
                        }
                        
                        // Render likers template with data (real or dummy)
                        echo '<div style="position: relative; padding: 40px; display: flex; align-items: center; justify-content: center; min-height: 200px;">';
                        
                        if ( ! empty( $get_users ) ) {
                            // Build template manually to ensure it renders
                            $inner_template = wp_ulike_get_template_between( $template, "%START_WHILE%", "%END_WHILE%" );
                            $users_list = '';
                            
                            foreach ( array_slice( $get_users, 0, $counter ) as $user_id ) {
                                $user_info = get_user_by( 'id', $user_id );
                                if ( ! $user_info ) {
                                    continue;
                                }
                                
                                $out_template = $inner_template;
                                if ( strpos( $out_template, '%USER_AVATAR%' ) !== false ) {
                                    $USER_AVATAR = get_avatar( $user_info->user_email, $avatar_size, '' , 'avatar' );
                                    $out_template = str_replace( "%USER_AVATAR%", $USER_AVATAR, $out_template );
                                }
                                if ( strpos( $out_template, '%USER_NAME%' ) !== false ) {
                                    $USER_NAME = esc_attr( $user_info->display_name );
                                    $out_template = str_replace( "%USER_NAME%", $USER_NAME, $out_template );
                                }
                                $users_list .= $out_template;
                            }
                            
                            if ( ! empty( $users_list ) ) {
                                echo wp_ulike_put_template_between( $template, $users_list, "%START_WHILE%", "%END_WHILE%" );
                            } else {
                                // Fallback structure
                                echo '<div class="ulf-tooltip" style="position: relative; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 16px; min-width: 250px; max-width: 300px;">';
                                echo '<div class="ulf-arrow" style="position: absolute; bottom: 100%; left: 20px; width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid #ddd;"></div>';
                                echo '<div class="ulf-tooltip-content">';
                                echo '<h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #333;">People who liked this</h4>';
                                echo '<div class="wp-ulike-likers-list">';
                                echo '<span class="wp-ulike-liker"><a href="#" title="Sample User">' . get_avatar( 'sample@example.com', $avatar_size ) . '</a></span>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            // Fallback structure
                            echo '<div class="ulf-tooltip" style="position: relative; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 16px; min-width: 250px; max-width: 300px;">';
                            echo '<div class="ulf-arrow" style="position: absolute; bottom: 100%; left: 20px; width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid #ddd;"></div>';
                            echo '<div class="ulf-tooltip-content">';
                            echo '<h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #333;">People who liked this</h4>';
                            echo '<div class="wp-ulike-likers-list">';
                            echo '<span class="wp-ulike-liker"><a href="#" title="Sample User">' . get_avatar( 'sample@example.com', $avatar_size ) . '</a></span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    } else {
                        // Fallback if function doesn't exist
                        echo '<div style="position: relative; padding: 40px; display: flex; align-items: center; justify-content: center; min-height: 200px;">';
                        echo '<p style="color: #666;">Likers template function not available</p>';
                        echo '</div>';
                    }
                    break;

                default:
                    // Default: render button preview
                    $this->render_button_preview();
            }

            return ob_get_clean();
        }

        /**
         * Render button preview (fallback)
         *
         * @return void
         */
        protected function render_button_preview() {
            echo '<div class="wpulike">';
            echo '<div class="wp_ulike_general_class">';
            echo '<button class="wp_ulike_btn wp_ulike_put_text">Like</button>';
            echo '<span class="count-box">42</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="wpulike" style="margin-top: 20px;">';
            echo '<div class="wp_ulike_general_class wp_ulike_is_already_liked">';
            echo '<button class="wp_ulike_btn wp_ulike_put_text wp_ulike_btn_is_active">Liked</button>';
            echo '<span class="count-box">43</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="wpulike" style="margin-top: 20px;">';
            echo '<div class="wp_ulike_general_class">';
            echo '<button class="wp_ulike_btn wp_ulike_put_image"></button>';
            echo '<span class="count-box">42</span>';
            echo '</div>';
            echo '</div>';
        }

        /**
         * Clear schema cache
         */
        public function clear_schema_cache() {
            $this->schema_cache = null;
        }
    }

    // Initialize the API - only if not already initialized
    if ( ! isset( $GLOBALS['wp_ulike_customizer_api'] ) ) {
        $GLOBALS['wp_ulike_customizer_api'] = new wp_ulike_customizer_api();
    }
}

