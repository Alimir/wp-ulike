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
         * Schema cache (static for request-level caching)
         */
        protected static $schema_cache = null;

        /**
         * Field types that are informational only (not form fields)
         */
        const INFORMATIONAL_FIELD_TYPES = array( 'submessage', 'content', 'heading', 'subheading', 'callback' );

        /**
         * Field types that contain nested fields
         */
        const NESTED_FIELD_TYPES = array( 'group', 'repeater', 'fieldset', 'tabbed' );

        /**
         * Constructor
         */
        public function __construct() {
            // Admin-ajax handlers are registered in admin-ajax.php
            // This class provides the methods that admin-ajax.php calls
        }

        /**
         * Get customizer schema
         * Returns schema structure extracted from ULF customizer sections
         */
        public function get_schema( $request = null ) {
            // Return cached schema if available (static cache for request-level sharing)
            if ( self::$schema_cache !== null ) {
                return self::$schema_cache;
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

            // Cache the schema (static for request-level sharing)
            self::$schema_cache = $schema;

            return apply_filters( 'wp_ulike_optiwich_customizer_schema', $schema );
        }

        /**
         * Get plugin assets URLs (CSS/JS)
         *
         * @return array Assets URLs (arrays of URLs)
         */
        protected function get_plugin_assets() {
            $assets = array(
                'css' => array(),
                'js' => array(),
                'localized_scripts' => array()
            );

            // Add minified CSS/JS (always use minified versions) - Free plugin assets
            $assets['css'][] = array(
                'url' => WP_ULIKE_ASSETS_URL . '/css/wp-ulike.min.css',
                'source' => 'free'
            );
            $assets['js'][] = array(
                'url' => WP_ULIKE_ASSETS_URL . '/js/wp-ulike.min.js',
                'source' => 'free'
            );

            // Add free plugin localized script
            $assets['localized_scripts']['wp_ulike_params'] = array(
                'data' => array(
                    'ajax_url'      => admin_url( 'admin-ajax.php' ),
                    'notifications' => wp_ulike_get_option( 'enable_toast_notice' )
                ),
                'source' => 'free'
            );

            // Allow pro version and other extensions to add their assets
            $assets = apply_filters( 'wp_ulike_customizer_assets', $assets );

            return $assets;
        }

        /**
         * Extract URLs from asset structure
         * Supports both old format (string URLs) and new format (array with 'url' and 'source')
         *
         * @param array|string $assets Asset URLs or asset objects
         * @return array Array of URL strings
         */
        protected function extract_asset_urls( $assets ) {
            if ( empty( $assets ) ) {
                return array();
            }

            // Normalize to array
            if ( ! is_array( $assets ) ) {
                $assets = array( $assets );
            }

            $urls = array();
            foreach ( $assets as $asset ) {
                if ( is_array( $asset ) && isset( $asset['url'] ) ) {
                    // New format: array with 'url' and 'source'
                    $urls[] = $asset['url'];
                } elseif ( is_string( $asset ) ) {
                    // Old format: direct URL string
                    $urls[] = $asset;
                }
            }

            return $urls;
        }

        /**
         * Build schema from customizer sections
         */
        protected function build_schema_from_customizer() {
            // Get sections from customizer class directly
            $sections = $this->get_customizer_sections();

            // Build pages structure from sections
            $pages = $this->build_pages_structure( $sections );

            return array( 'pages' => apply_filters( 'wp_ulike_optiwich_customizer_pages', $pages ) );
        }

        /**
         * Get customizer sections
         * Gets sections directly from customizer class
         *
         * @return array Sections array
         */
        protected function get_customizer_sections() {
            // Use static cache
            static $cached_sections = null;

            if ( $cached_sections !== null ) {
                return $cached_sections;
            }

            // Ensure customizer class is loaded
            if ( ! class_exists( 'wp_ulike_customizer' ) ) {
                $cached_sections = array();
                return $cached_sections;
            }

            // Get sections directly from customizer class
            $customizer = new wp_ulike_customizer();
            $cached_sections = $customizer->register_sections();

            return $cached_sections;
        }

        /**
         * Build pages structure from customizer sections
         * Converts customizer sections to pages structure for React consumption
         *
         * @param array $sections Sections array from customizer
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

            // If no parent found, use default parent ID
            if ( ! $parent_section_id ) {
                $parent_section_id = WP_ULIKE_SLUG;
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

                    // Add template and icon to page level (for template selector)
                    if ( isset( $section['template'] ) ) {
                        $page['template'] = $section['template'];
                    }
                    if ( isset( $section['icon'] ) ) {
                        $page['icon'] = $section['icon'];
                    }

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

            // Generate ID if not present (use wp_json_encode for safer serialization)
            $field_id = $field['id'] ?? 'callback_' . md5( wp_json_encode( array( $field['function'], $args ) ) );

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
            return $field_type . '_' . md5( wp_json_encode( array( $field_type, $field_content ) ) );
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

            // Save as serialized array in option (autoload = 'no' to prevent loading on every page)
            update_option( $this->option_domain, $values, 'no' );

            // Clear schema cache
            self::$schema_cache = null;

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
            // Get template type from request (prefer GET, fallback to POST)
            $template_type = 'button';
            if ( isset( $_GET['template'] ) ) {
                $template_type = sanitize_text_field( wp_unslash( $_GET['template'] ) );
            } elseif ( isset( $_POST['template'] ) ) {
                $template_type = sanitize_text_field( wp_unslash( $_POST['template'] ) );
            }

            // Add preview parameter to detect preview mode in pro version
            $_GET['preview'] = true;

            // Render template preview
            $preview_html = $this->render_template_preview( $template_type );

            // Get WP ULike front-end assets URLs using the same method as schema
            $plugin_assets = $this->get_plugin_assets();
            
            // Extract URLs from asset structure (support both old string format and new array format)
            $css_urls = isset( $plugin_assets['css'] ) ? $plugin_assets['css'] : array();
            $js_urls = isset( $plugin_assets['js'] ) ? $plugin_assets['js'] : array();

            // Normalize to arrays and extract URLs
            $css_urls = $this->extract_asset_urls( $css_urls );
            $js_urls = $this->extract_asset_urls( $js_urls );

            // Build full HTML with styles and WP ULike assets
            $html = $this->build_preview_html( $preview_html, $css_urls, $js_urls, $plugin_assets );

            wp_send_json_success( array(
                'html' => $html,
            ) );
        }

        /**
         * Build preview HTML document
         *
         * @param string $preview_html Preview content HTML
         * @param array  $css_urls CSS file URLs
         * @param array  $js_urls JS file URLs
         * @param array  $plugin_assets Plugin assets array
         * @return string Complete HTML document
         */
        protected function build_preview_html( $preview_html, $css_urls, $js_urls, $plugin_assets ) {
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html__( 'WP ULike Customizer Preview', 'wp-ulike' ) . '</title>
    <base href="' . esc_url( site_url() ) . '/">';

            // Include CSS files
            if ( ! empty( $css_urls ) && is_array( $css_urls ) ) {
                foreach ( $css_urls as $css_url ) {
                    if ( ! empty( $css_url ) ) {
                        $css_url = $this->normalize_url( $css_url );
                        $html .= '<link rel="stylesheet" href="' . esc_url( $css_url ) . '">';
                    }
                }
            }

            // Add base styles
            $html .= $this->get_preview_base_styles();

            $html .= '</head>
<body' . ( is_rtl() ? ' dir="rtl" class="rtl"' : '' ) . '>
    ' . $preview_html;

            // Include JS files
            if ( ! empty( $js_urls ) && is_array( $js_urls ) ) {
                foreach ( $js_urls as $js_url ) {
                    if ( ! empty( $js_url ) ) {
                        $js_url = $this->normalize_url( $js_url );
                        $html .= '<script src="' . esc_url( $js_url ) . '"></script>';
                    }
                }
            }

            // Add localized scripts from assets
            if ( isset( $plugin_assets['localized_scripts'] ) && is_array( $plugin_assets['localized_scripts'] ) ) {
                foreach ( $plugin_assets['localized_scripts'] as $var_name => $script_data ) {
                    // Support both old format (direct array) and new format (with 'data' and 'source')
                    $var_data = isset( $script_data['data'] ) ? $script_data['data'] : $script_data;
                    $html .= '<script>
                        window.' . esc_js( $var_name ) . ' = ' . wp_json_encode( $var_data ) . ';
                    </script>';
                }
            }

            $html .= '</body>
</html>';

            return $html;
        }

        /**
         * Normalize URL (convert relative to absolute if needed)
         *
         * @param string $url URL to normalize
         * @return string Normalized URL
         */
        protected function normalize_url( $url ) {
            if ( ! empty( $url ) && strpos( $url, 'http' ) !== 0 ) {
                return site_url( $url );
            }
            return $url;
        }

        /**
         * Get base styles for preview iframe
         *
         * @return string CSS styles
         */
        protected function get_preview_base_styles() {
            return '<style id="optiwich-preview-base-styles">
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
        .wp-ulike-preview-not-found {
            padding: 2rem;
            text-align: center;
            color: #666;
            font-size: 1rem;
        }
        #optiwich-customizer-styles {
            display: block !important;
        }
    </style>';
        }

        /**
         * Render template preview
         *
         * @param string $template_type Template type (button, toast, etc.)
         * @return string Rendered HTML
         */
        protected function render_template_preview( $template_type ) {
            // Allow extensions to handle their own template previews
            $preview_html = apply_filters( 'wp_ulike_customizer_template_preview', '', $template_type );

            // If filter returned content, use it
            if ( ! empty( $preview_html ) ) {
                return $preview_html;
            }

            ob_start();

            switch ( $template_type ) {
                case 'button':
                    // Use shortcode for button preview
                    echo do_shortcode( '[wp_ulike id="1"]' );
                    break;

                case 'toast':
                    // Render toast notification types
                    $this->render_toast_preview();
                    break;

                default:
                    // Template not found
                    echo '<p class="wp-ulike-preview-not-found">' . esc_html__( 'Template preview not found.', 'wp-ulike' ) . '</p>';
            }

            return ob_get_clean();
        }

        /**
         * Render toast notification preview
         *
         * @return void
         */
        protected function render_toast_preview() {
            $notification_classes = array(
                'wpulike-notification',
            );
            ?>
            <div class="<?php echo esc_attr( implode( ' ', $notification_classes ) ); ?>">
                <div class="wpulike-message">
                    <strong><?php esc_html_e( 'Info:', 'wp-ulike' ); ?></strong> <?php esc_html_e( 'Please wait...', 'wp-ulike' ); ?>
                </div>
                <div class="wpulike-message wpulike-success">
                    <strong><?php esc_html_e( 'Success!', 'wp-ulike' ); ?></strong> <?php esc_html_e( 'You liked this post.', 'wp-ulike' ); ?>
                </div>
                <div class="wpulike-message wpulike-error">
                    <strong><?php esc_html_e( 'Error!', 'wp-ulike' ); ?></strong> <?php esc_html_e( 'Something went wrong.', 'wp-ulike' ); ?>
                </div>
                <div class="wpulike-message wpulike-warning">
                    <strong><?php esc_html_e( 'Warning!', 'wp-ulike' ); ?></strong> <?php esc_html_e( 'Please check your settings.', 'wp-ulike' ); ?>
                </div>
            </div>
            <?php
        }

        /**
         * Clear schema cache
         */
        public function clear_schema_cache() {
            self::$schema_cache = null;
        }
    }
}

