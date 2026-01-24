<?php
/**
 * WP ULike CSS Generator
 *
 * Generates CSS from customizer values for front-end output
 * Includes caching to avoid multiple generations
 *
 * @package WP_ULike
 * @since 4.6.0
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_css_generator' ) ) {
    class wp_ulike_css_generator {

        /**
         * Option domain for customizer values
         */
        protected $option_domain = 'wp_ulike_customize';

        /**
         * Option name for cached CSS
         */
        protected $css_cache_option = 'wp_ulike_customizer_css_cache';

        /**
         * Option name for values hash (to detect changes)
         */
        protected $values_hash_option = 'wp_ulike_customizer_values_hash';

        /**
         * Constructor
         */
        public function __construct() {
            // Clear cache when customizer is saved
            // This ensures cache is cleared AFTER new values are saved
            add_action( 'wp_ulike_customizer_saved', array( $this, 'clear_cache' ), 10, 1 );
        }

        /**
         * Generate CSS from customizer values
         *
         * @return string Generated CSS
         */
        public function generate_css() {
            // Get customizer values
            $values = get_option( $this->option_domain, array() );
            if ( empty( $values ) || ! is_array( $values ) ) {
                return '';
            }

            // Calculate hash of current values to detect changes
            $current_hash = $this->calculate_values_hash( $values );

            // Get cached hash and CSS
            $cached_hash = get_option( $this->values_hash_option, '' );
            $cached_css = get_option( $this->css_cache_option, '' );

            // If values haven't changed and we have cached CSS, return it
            if ( $current_hash === $cached_hash && ! empty( $cached_css ) ) {
                return $cached_css;
            }

            // Values have changed or cache is empty - regenerate CSS
            // Get schema to understand field structure
            $schema = $this->get_schema();
            if ( empty( $schema ) || ! isset( $schema['pages'] ) ) {
                return '';
            }

            // Generate CSS from schema and values
            $css = $this->generate_css_from_schema( $schema, $values );

            // Cache the generated CSS and hash
            $this->set_cached_css( $css, $current_hash );

            return $css;
        }

        /**
         * Calculate hash of customizer values to detect changes
         * Uses fast hash algorithm optimized for large arrays
         *
         * @param array $values Customizer values
         * @return string Hash string
         */
        protected function calculate_values_hash( $values ) {
            // Sort values recursively to ensure consistent hashing
            $sorted_values = $this->recursive_ksort( $values );
            
            // Use wp_json_encode for better performance than serialize
            // and it's more compatible with modern PHP
            $json = wp_json_encode( $sorted_values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            
            // Use md5 for fast hashing (security not a concern here, just change detection)
            return md5( $json );
        }

        /**
         * Recursively sort array by keys
         *
         * @param array $array Array to sort
         * @return array Sorted array
         */
        protected function recursive_ksort( $array ) {
            if ( ! is_array( $array ) ) {
                return $array;
            }

            ksort( $array );
            foreach ( $array as $key => $value ) {
                if ( is_array( $value ) ) {
                    $array[ $key ] = $this->recursive_ksort( $value );
                }
            }

            return $array;
        }

        /**
         * Get schema from customizer API
         *
         * @return array Schema array
         */
        protected function get_schema() {
            if ( ! class_exists( 'wp_ulike_customizer_api' ) ) {
                return array();
            }

            try {
                global $wp_ulike_customizer_api;
                if ( ! isset( $wp_ulike_customizer_api ) ) {
                    $wp_ulike_customizer_api = new wp_ulike_customizer_api();
                }

                $schema = $wp_ulike_customizer_api->get_schema();
                
                // Validate schema structure
                if ( ! is_array( $schema ) || ! isset( $schema['pages'] ) ) {
                    return array();
                }

                return $schema;
            } catch ( Exception $e ) {
                // Log error in debug mode
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'WP ULike CSS Generator: Failed to get schema - ' . $e->getMessage() );
                }
                return array();
            }
        }

        /**
         * Generate CSS from schema and values
         *
         * @param array $schema Schema structure
         * @param array $values Customizer values
         * @return string Generated CSS
         */
        protected function generate_css_from_schema( $schema, $values ) {
            // Validate inputs
            if ( ! is_array( $schema ) || ! isset( $schema['pages'] ) || ! is_array( $schema['pages'] ) ) {
                return '';
            }

            if ( ! is_array( $values ) ) {
                return '';
            }

            $selector_map = array(); // Map of selectors to their CSS properties

            // Process all pages and sections
            foreach ( $schema['pages'] as $page ) {
                if ( ! isset( $page['sections'] ) || ! is_array( $page['sections'] ) ) {
                    continue;
                }

                foreach ( $page['sections'] as $section ) {
                    if ( ! isset( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
                        continue;
                    }

                    // Determine section path (for nested values)
                    $section_id = isset( $section['id'] ) ? $section['id'] : 'section';
                    $section_path = ( $section_id !== 'section' ) ? $section_id : '';

                    $this->process_fields( $section['fields'], $values, $section_path, $selector_map );
                }
            }

            // Convert map to CSS string
            $css_rules = array();
            foreach ( $selector_map as $selector => $properties ) {
                if ( empty( $properties ) || ! is_array( $properties ) ) {
                    continue;
                }

                $props = array();
                foreach ( $properties as $property => $value ) {
                    // Double-check that property and value are strings
                    if ( is_string( $property ) && is_string( $value ) ) {
                        $props[] = '  ' . $property . ': ' . $value . ';';
                    }
                }

                if ( ! empty( $props ) ) {
                    $css_rules[] = $selector . ' {' . "\n" . implode( "\n", $props ) . "\n" . '}';
                }
            }

            return implode( "\n\n", $css_rules );
        }

        /**
         * Process fields recursively
         *
         * @param array $fields Fields array
         * @param array $values Values array
         * @param string $path Current path prefix
         * @param array $selector_map Reference to selector map
         */
        protected function process_fields( $fields, $values, $path, &$selector_map ) {
            foreach ( $fields as $field ) {
                if ( ! isset( $field['id'] ) ) {
                    continue;
                }

                // Build field path
                $field_path = $path ? $path . '.' . $field['id'] : $field['id'];

                // Get value from values array
                $value = $this->get_nested_value( $values, $field_path );

                // Skip if no value
                if ( $value === null || $value === '' ) {
                    // Still process nested fields even if parent has no value
                    if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                        $this->process_fields( $field['fields'], $values, $field_path, $selector_map );
                    }
                    continue;
                }

                // Handle tabbed fields
                if ( isset( $field['type'] ) && $field['type'] === 'tabbed' && isset( $field['tabs'] ) ) {
                    foreach ( $field['tabs'] as $tab ) {
                        if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
                            $this->process_fields( $tab['fields'], $values, $field_path, $selector_map );
                        }
                    }
                    continue;
                }

                // Handle group fields
                if ( isset( $field['type'] ) && $field['type'] === 'group' && isset( $field['fields'] ) ) {
                    $group_value = is_array( $value ) ? $value : array();
                    foreach ( $group_value as $index => $item ) {
                        if ( is_array( $item ) ) {
                            $this->process_fields( $field['fields'], $item, $field_path . '[' . $index . ']', $selector_map );
                        }
                    }
                    continue;
                }

                // Generate CSS if field has output selector
                if ( isset( $field['output'] ) && ! empty( $field['output'] ) ) {
                    $outputs = $this->generate_css_from_field( $field, $value );
                    foreach ( $outputs as $output ) {
                        $selector = $this->sanitize_css_selector( $output['selector'] );
                        if ( empty( $selector ) ) {
                            continue;
                        }

                        if ( ! isset( $selector_map[ $selector ] ) ) {
                            $selector_map[ $selector ] = array();
                        }

                        $property = $this->sanitize_css_property( $output['property'] );
                        if ( $property ) {
                            $selector_map[ $selector ][ $property ] = $this->sanitize_css_value( $output['value'], $property );
                        }
                    }
                }

                // Process nested fields
                if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                    $this->process_fields( $field['fields'], $values, $field_path, $selector_map );
                }
            }
        }

        /**
         * Generate CSS from a single field
         *
         * @param array $field Field definition
         * @param mixed $value Field value
         * @return array Array of CSS outputs
         */
        protected function generate_css_from_field( $field, $value ) {
            $outputs = array();

            $selector = isset( $field['output'] ) ? $field['output'] : '';
            $output_mode = isset( $field['output_mode'] ) ? $field['output_mode'] : '';
            $output_important = isset( $field['output_important'] ) && $field['output_important'];

            if ( empty( $selector ) || $value === null || $value === '' ) {
                return $outputs;
            }

            $important = $output_important ? ' !important' : '';
            $field_type = isset( $field['type'] ) ? $field['type'] : '';

            switch ( $field_type ) {
                case 'color':
                    $property = $output_mode ? $output_mode : 'color';
                    $css_value = $this->sanitize_css_value( $value, $property );
                    if ( $css_value ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => $property,
                            'value' => $css_value . $important
                        );
                    }
                    break;

                case 'typography':
                    if ( is_array( $value ) ) {
                        $outputs = array_merge( $outputs, $this->generate_typography_css( $value, $selector, $important ) );
                    }
                    break;

                case 'border':
                    if ( is_array( $value ) ) {
                        $outputs = array_merge( $outputs, $this->generate_border_css( $value, $selector, $important ) );
                    }
                    break;

                case 'spacing':
                    if ( is_array( $value ) ) {
                        $mode = $output_mode ? $output_mode : 'padding';
                        $outputs = array_merge( $outputs, $this->generate_spacing_css( $value, $selector, $mode, $important ) );
                    }
                    break;

                case 'dimensions':
                    if ( is_array( $value ) ) {
                        $outputs = array_merge( $outputs, $this->generate_dimensions_css( $value, $selector, $important ) );
                    }
                    break;

                case 'background':
                    if ( is_array( $value ) ) {
                        $outputs = array_merge( $outputs, $this->generate_background_css( $value, $selector, $important ) );
                    }
                    break;

                case 'slider':
                case 'spinner':
                case 'number':
                    if ( $value !== '' && $value !== null ) {
                        $unit = isset( $field['unit'] ) ? $field['unit'] : '';
                        $property = $output_mode ? $output_mode : 'width';
                        $num_value = $value . $unit;
                        $css_value = $this->sanitize_css_value( $num_value, $property );
                        if ( $css_value ) {
                            $outputs[] = array(
                                'selector' => $selector,
                                'property' => $property,
                                'value' => $css_value . $important
                            );
                        }
                    }
                    break;

                default:
                    // For other field types, output as-is if output_mode is specified
                    if ( $output_mode && $value !== '' && $value !== null ) {
                        $css_value = $this->sanitize_css_value( $value, $output_mode );
                        if ( $css_value ) {
                            $outputs[] = array(
                                'selector' => $selector,
                                'property' => $output_mode,
                                'value' => $css_value . $important
                            );
                        }
                    }
                    break;
            }

            return $outputs;
        }

        /**
         * Generate typography CSS
         */
        protected function generate_typography_css( $value, $selector, $important ) {
            $outputs = array();
            $value = $this->normalize_typography_value( $value );

            $properties = array(
                'fontFamily' => 'font-family',
                'fontSize' => 'font-size',
                'fontWeight' => 'font-weight',
                'lineHeight' => 'line-height',
                'letterSpacing' => 'letter-spacing',
                'textAlign' => 'text-align',
                'textTransform' => 'text-transform',
                'textDecoration' => 'text-decoration',
                'color' => 'color'
            );

            foreach ( $properties as $key => $property ) {
                $camel_key = $key;
                $lower_key = strtolower( preg_replace( '/([A-Z])/', '_$1', $key ) );
                $lower_key = str_replace( '_', '', $lower_key );

                $prop_value = isset( $value[ $camel_key ] ) ? $value[ $camel_key ] : ( isset( $value[ $lower_key ] ) ? $value[ $lower_key ] : null );

                if ( $prop_value !== null && $prop_value !== '' ) {
                    // Skip 'none' values for text-align, text-transform, text-decoration
                    if ( in_array( $property, array( 'text-align', 'text-transform', 'text-decoration' ) ) && $prop_value === 'none' ) {
                        continue;
                    }

                    $css_value = $this->sanitize_css_value( $prop_value, $property );
                    if ( $css_value ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => $property,
                            'value' => $css_value . $important
                        );
                    }
                }
            }

            return $outputs;
        }

        /**
         * Generate border CSS
         */
        protected function generate_border_css( $value, $selector, $important ) {
            $outputs = array();

            if ( isset( $value['width'] ) && isset( $value['style'] ) && isset( $value['color'] ) ) {
                $width = $this->sanitize_css_value( $value['width'], 'border-width' );
                $style = $this->sanitize_css_value( $value['style'], 'border-style' );
                $color = $this->sanitize_css_value( $value['color'], 'border-color' );

                if ( $width && $style && $color ) {
                    $outputs[] = array(
                        'selector' => $selector,
                        'property' => 'border',
                        'value' => $width . ' ' . $style . ' ' . $color . $important
                    );
                }
            } else {
                if ( isset( $value['width'] ) ) {
                    $width = $this->sanitize_css_value( $value['width'], 'border-width' );
                    if ( $width ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => 'border-width',
                            'value' => $width . $important
                        );
                    }
                }
                if ( isset( $value['style'] ) ) {
                    $style = $this->sanitize_css_value( $value['style'], 'border-style' );
                    if ( $style ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => 'border-style',
                            'value' => $style . $important
                        );
                    }
                }
                if ( isset( $value['color'] ) ) {
                    $color = $this->sanitize_css_value( $value['color'], 'border-color' );
                    if ( $color ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => 'border-color',
                            'value' => $color . $important
                        );
                    }
                }
            }

            return $outputs;
        }

        /**
         * Generate spacing CSS
         */
        protected function generate_spacing_css( $value, $selector, $mode, $important ) {
            $outputs = array();
            $default_unit = isset( $value['unit'] ) ? $value['unit'] : 'px';

            $sides = array( 'top', 'right', 'bottom', 'left' );
            $values = array();

            foreach ( $sides as $side ) {
                if ( isset( $value[ $side ] ) && $value[ $side ] !== '' && $value[ $side ] !== null ) {
                    $val = $value[ $side ];
                    // Check if value already has unit
                    if ( preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|pt)$/', $val ) ) {
                        $values[ $side ] = $val;
                    } else {
                        $values[ $side ] = $val . $default_unit;
                    }
                }
            }

            if ( empty( $values ) ) {
                return $outputs;
            }

            // If all values are the same, use shorthand
            if ( count( $values ) === 4 && count( array_unique( $values ) ) === 1 ) {
                $css_value = $this->sanitize_css_value( reset( $values ), $mode );
                if ( $css_value ) {
                    $outputs[] = array(
                        'selector' => $selector,
                        'property' => $mode,
                        'value' => $css_value . $important
                    );
                }
            } else {
                // Individual properties
                foreach ( $values as $side => $val ) {
                    $css_value = $this->sanitize_css_value( $val, $mode . '-' . $side );
                    if ( $css_value ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => $mode . '-' . $side,
                            'value' => $css_value . $important
                        );
                    }
                }
            }

            return $outputs;
        }

        /**
         * Generate dimensions CSS
         */
        protected function generate_dimensions_css( $value, $selector, $important ) {
            $outputs = array();
            $default_unit = isset( $value['unit'] ) ? $value['unit'] : 'px';

            if ( isset( $value['width'] ) && $value['width'] !== '' && $value['width'] !== null ) {
                $width = $value['width'];
                if ( ! preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|pt)$/', $width ) ) {
                    $width = $width . $default_unit;
                }
                $css_value = $this->sanitize_css_value( $width, 'width' );
                if ( $css_value ) {
                    $outputs[] = array(
                        'selector' => $selector,
                        'property' => 'width',
                        'value' => $css_value . $important
                    );
                }
            }

            if ( isset( $value['height'] ) && $value['height'] !== '' && $value['height'] !== null ) {
                $height = $value['height'];
                if ( ! preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|pt)$/', $height ) ) {
                    $height = $height . $default_unit;
                }
                $css_value = $this->sanitize_css_value( $height, 'height' );
                if ( $css_value ) {
                    $outputs[] = array(
                        'selector' => $selector,
                        'property' => 'height',
                        'value' => $css_value . $important
                    );
                }
            }

            return $outputs;
        }

        /**
         * Generate background CSS
         */
        protected function generate_background_css( $value, $selector, $important ) {
            $outputs = array();

            // Normalize keys (handle both camelCase and lowercase)
            $bg_color = isset( $value['backgroundColor'] ) ? $value['backgroundColor'] : ( isset( $value['backgroundcolor'] ) ? $value['backgroundcolor'] : null );
            $bg_image = isset( $value['backgroundImage'] ) ? $value['backgroundImage'] : ( isset( $value['backgroundimage'] ) ? $value['backgroundimage'] : null );
            $bg_repeat = isset( $value['backgroundRepeat'] ) ? $value['backgroundRepeat'] : ( isset( $value['backgroundrepeat'] ) ? $value['backgroundrepeat'] : null );
            $bg_position = isset( $value['backgroundPosition'] ) ? $value['backgroundPosition'] : ( isset( $value['backgroundposition'] ) ? $value['backgroundposition'] : null );
            $bg_size = isset( $value['backgroundSize'] ) ? $value['backgroundSize'] : ( isset( $value['backgroundsize'] ) ? $value['backgroundsize'] : null );
            $bg_attachment = isset( $value['backgroundAttachment'] ) ? $value['backgroundAttachment'] : ( isset( $value['backgroundattachment'] ) ? $value['backgroundattachment'] : null );

            $properties = array(
                'background-color' => $bg_color,
                'background-image' => $bg_image,
                'background-repeat' => $bg_repeat,
                'background-position' => $bg_position,
                'background-size' => $bg_size,
                'background-attachment' => $bg_attachment
            );

            foreach ( $properties as $property => $prop_value ) {
                if ( $prop_value !== null && $prop_value !== '' ) {
                    // Handle background-image URL format
                    if ( $property === 'background-image' ) {
                        if ( strpos( $prop_value, 'url(' ) !== 0 ) {
                            $prop_value = 'url(' . $prop_value . ')';
                        }
                    }

                    $css_value = $this->sanitize_css_value( $prop_value, $property );
                    if ( $css_value ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => $property,
                            'value' => $css_value . $important
                        );
                    }
                }
            }

            return $outputs;
        }

        /**
         * Normalize typography value (convert lowercase to camelCase)
         */
        protected function normalize_typography_value( $value ) {
            if ( ! is_array( $value ) ) {
                return array();
            }

            $key_map = array(
                'fontfamily' => 'fontFamily',
                'fontsize' => 'fontSize',
                'fontweight' => 'fontWeight',
                'lineheight' => 'lineHeight',
                'letterspacing' => 'letterSpacing',
                'textalign' => 'textAlign',
                'texttransform' => 'textTransform',
                'textdecoration' => 'textDecoration'
            );

            $normalized = array();
            foreach ( $value as $key => $val ) {
                $lower_key = strtolower( $key );
                if ( isset( $key_map[ $lower_key ] ) ) {
                    $normalized[ $key_map[ $lower_key ] ] = $val;
                } else {
                    $normalized[ $key ] = $val;
                }
            }

            return $normalized;
        }

        /**
         * Get nested value from array using dot notation
         */
        protected function get_nested_value( $array, $path ) {
            if ( empty( $path ) ) {
                return null;
            }

            $keys = explode( '.', $path );
            $current = $array;

            foreach ( $keys as $key ) {
                // Handle array indices like "field[0]"
                if ( preg_match( '/^(.+)\[(\d+)\]$/', $key, $matches ) ) {
                    $array_key = $matches[1];
                    $index = intval( $matches[2] );
                    if ( is_array( $current ) && isset( $current[ $array_key ] ) && is_array( $current[ $array_key ] ) && isset( $current[ $array_key ][ $index ] ) ) {
                        $current = $current[ $array_key ][ $index ];
                    } else {
                        return null;
                    }
                } else {
                    if ( ! is_array( $current ) || ! isset( $current[ $key ] ) ) {
                        return null;
                    }
                    $current = $current[ $key ];
                }
            }

            return $current;
        }

        /**
         * Sanitize CSS selector
         */
        protected function sanitize_css_selector( $selector ) {
            if ( ! is_string( $selector ) ) {
                return '';
            }

            // Remove null bytes and control characters
            $sanitized = preg_replace( '/[\x00-\x1F\x7F]/', '', $selector );

            // Check for malicious patterns
            $malicious_patterns = array(
                '/<script/i',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/expression\s*\(/i',
                '/@import/i',
                '/url\s*\(\s*[\'"]?\s*javascript:/i'
            );

            foreach ( $malicious_patterns as $pattern ) {
                if ( preg_match( $pattern, $sanitized ) ) {
                    return '';
                }
            }

            // Only allow valid CSS selector characters
            $sanitized = preg_replace( '/[^a-zA-Z0-9\s,.:#\[\]()\-_>+~*="\'\\\\]/', '', $sanitized );

            // Limit length
            if ( strlen( $sanitized ) > 1000 ) {
                $sanitized = substr( $sanitized, 0, 1000 );
            }

            $trimmed = trim( $sanitized );

            // If empty or starts with invalid characters, return empty
            if ( empty( $trimmed ) || preg_match( '/^[^a-zA-Z0-9.#]/', $trimmed ) ) {
                return '';
            }

            return $trimmed;
        }

        /**
         * Sanitize CSS property name
         */
        protected function sanitize_css_property( $property ) {
            if ( ! is_string( $property ) ) {
                return '';
            }

            // Whitelist of valid CSS properties
            $valid_properties = array(
                'width', 'height', 'min-width', 'min-height', 'max-width', 'max-height',
                'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
                'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
                'border', 'border-width', 'border-style', 'border-color',
                'border-top', 'border-right', 'border-bottom', 'border-left',
                'border-radius', 'color', 'font-family', 'font-size', 'font-weight', 'font-style',
                'line-height', 'letter-spacing', 'text-align', 'text-transform', 'text-decoration',
                'background', 'background-color', 'background-image', 'background-repeat',
                'background-position', 'background-size', 'background-attachment',
                'display', 'position', 'top', 'right', 'bottom', 'left', 'z-index',
                'opacity', 'transform', 'transition'
            );

            $property_lower = strtolower( $property );
            if ( in_array( $property_lower, $valid_properties, true ) ) {
                return $property_lower;
            }

            // Allow vendor prefixes
            if ( preg_match( '/^-(webkit|moz|ms|o)-[a-z-]+$/', $property_lower ) ) {
                return $property;
            }

            return '';
        }

        /**
         * Sanitize CSS value
         * Validates and sanitizes CSS values based on property type
         */
        protected function sanitize_css_value( $value, $property = '' ) {
            if ( $value === null || $value === '' ) {
                return '';
            }

            $str_value = trim( (string) $value );

            // Remove null bytes and control characters (except newlines for multi-line values)
            $sanitized = preg_replace( '/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $str_value );

            // Limit length to prevent DoS
            if ( strlen( $sanitized ) > 10000 ) {
                $sanitized = substr( $sanitized, 0, 10000 );
            }

            if ( empty( $sanitized ) ) {
                return '';
            }

            // Property-specific validation
            if ( ! empty( $property ) ) {
                $prop_lower = strtolower( $property );

                // Color values (hex, rgb, rgba, hsl, hsla, named colors, CSS variables)
                if ( strpos( $prop_lower, 'color' ) !== false || $prop_lower === 'border-color' ) {
                    return $this->sanitize_color_value( $sanitized );
                }

                // Length values (width, height, margin, padding, etc.)
                if ( strpos( $prop_lower, 'width' ) !== false || 
                     strpos( $prop_lower, 'height' ) !== false ||
                     strpos( $prop_lower, 'margin' ) !== false ||
                     strpos( $prop_lower, 'padding' ) !== false ||
                     strpos( $prop_lower, 'top' ) !== false ||
                     strpos( $prop_lower, 'right' ) !== false ||
                     strpos( $prop_lower, 'bottom' ) !== false ||
                     strpos( $prop_lower, 'left' ) !== false ||
                     strpos( $prop_lower, 'size' ) !== false ||
                     strpos( $prop_lower, 'spacing' ) !== false ||
                     $prop_lower === 'line-height' ||
                     $prop_lower === 'letter-spacing' ) {
                    return $this->sanitize_length_value( $sanitized );
                }

                // Background image - validate URL format
                if ( $prop_lower === 'background-image' ) {
                    return $this->sanitize_background_image_value( $sanitized );
                }

                // Font family - allow quoted strings and fallbacks
                if ( $prop_lower === 'font-family' ) {
                    return $this->sanitize_font_family_value( $sanitized );
                }
            }

            // For other values, escape special characters but preserve CSS syntax
            // Don't use esc_html() as it escapes quotes which breaks CSS
            $sanitized = esc_attr( $sanitized );

            return $sanitized;
        }

        /**
         * Sanitize color value
         */
        protected function sanitize_color_value( $value ) {
            // Allow hex colors (#rgb, #rrggbb, #rrggbbaa)
            if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
                return $value;
            }

            // Allow rgb/rgba/hsl/hsla
            if ( preg_match( '/^(rgb|rgba|hsl|hsla)\([^)]+\)$/', $value ) ) {
                // Additional validation: ensure no javascript: or other dangerous content
                if ( ! preg_match( '/javascript:|expression\s*\(|@import/i', $value ) ) {
                    return esc_attr( $value );
                }
            }

            // Allow CSS variables (var(--name))
            if ( preg_match( '/^var\(--[a-zA-Z0-9_-]+\)$/', $value ) ) {
                return $value;
            }

            // Allow named colors (basic set)
            $named_colors = array( 'transparent', 'inherit', 'initial', 'unset', 'currentColor' );
            if ( in_array( strtolower( $value ), $named_colors, true ) ) {
                return $value;
            }

            // If not a valid color format, escape it safely
            return esc_attr( $value );
        }

        /**
         * Sanitize length value
         */
        protected function sanitize_length_value( $value ) {
            // Allow numbers with units (px, em, rem, %, vh, vw, etc.)
            if ( preg_match( '/^-?\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax|pt|pc|in|cm|mm|ex|ch|fr|deg|rad|grad|turn|s|ms|Hz|kHz)$/', $value ) ) {
                return $value;
            }

            // Allow '0' without unit
            if ( $value === '0' || $value === '0px' ) {
                return '0';
            }

            // Allow keywords
            $keywords = array( 'auto', 'inherit', 'initial', 'unset', 'none', 'normal' );
            if ( in_array( strtolower( $value ), $keywords, true ) ) {
                return strtolower( $value );
            }

            // Allow calc() and var()
            if ( preg_match( '/^(calc|var)\([^)]+\)$/', $value ) ) {
                // Additional validation: ensure no dangerous content
                if ( ! preg_match( '/javascript:|expression\s*\(|@import/i', $value ) ) {
                    return esc_attr( $value );
                }
            }

            // If not valid, escape it
            return esc_attr( $value );
        }

        /**
         * Sanitize background image value
         */
        protected function sanitize_background_image_value( $value ) {
            // Remove any existing url() wrapper to check the actual URL
            $url = preg_replace( '/^url\s*\(\s*[\'"]?|[\'"]?\s*\)$/', '', $value );

            // Validate URL format
            if ( filter_var( $url, FILTER_VALIDATE_URL ) || 
                 preg_match( '/^\/[^\/]/', $url ) || // Relative URL
                 preg_match( '/^data:image\/(png|jpg|jpeg|gif|svg|webp);base64,/', $url ) ) { // Data URI
                // Ensure no javascript: or other dangerous protocols
                if ( ! preg_match( '/javascript:|data:text\/html|expression\s*\(/i', $url ) ) {
                    // Re-wrap in url() if not already
                    if ( strpos( $value, 'url(' ) !== 0 ) {
                        return 'url(' . esc_url( $url ) . ')';
                    }
                    return esc_attr( $value );
                }
            }

            // Allow 'none' keyword
            if ( strtolower( $value ) === 'none' ) {
                return 'none';
            }

            // Allow CSS gradients
            if ( preg_match( '/^(linear|radial|conic|repeating-linear|repeating-radial|repeating-conic)-gradient\([^)]+\)$/', $value ) ) {
                if ( ! preg_match( '/javascript:|expression\s*\(/i', $value ) ) {
                    return esc_attr( $value );
                }
            }

            // If invalid, return empty
            return '';
        }

        /**
         * Sanitize font family value
         */
        protected function sanitize_font_family_value( $value ) {
            // Font families can contain quotes, commas, spaces, and hyphens
            // Allow: "Font Name", 'Font Name', Font Name, font-name, etc.
            // Remove any dangerous content
            $sanitized = preg_replace( '/javascript:|expression\s*\(|@import/i', '', $value );
            
            // Allow alphanumeric, spaces, hyphens, underscores, quotes, commas
            $sanitized = preg_replace( '/[^a-zA-Z0-9\s,\'":\-_]/', '', $sanitized );
            
            return esc_attr( trim( $sanitized ) );
        }

        /**
         * Set cached CSS and values hash
         *
         * @param string $css Generated CSS
         * @param string $hash Values hash
         */
        protected function set_cached_css( $css, $hash ) {
            // Store CSS and hash in options (persistent storage)
            // Set autoload to 'no' to prevent loading large CSS into memory on every page load
            // We only need it when generating CSS, so we'll fetch it on-demand
            update_option( $this->css_cache_option, $css, 'no' );
            update_option( $this->values_hash_option, $hash, 'no' );

            // Also cache in object cache for faster access (optional, non-persistent)
            if ( function_exists( 'wp_cache_set' ) ) {
                wp_cache_set( $this->css_cache_option, $css, 'wp_ulike_customizer', 0 );
                wp_cache_set( $this->values_hash_option, $hash, 'wp_ulike_customizer', 0 );
            }
        }

        /**
         * Clear CSS cache
         * This is called when customizer is saved to force regeneration on next page load
         * The cache will be regenerated automatically when generate_css() is called
         *
         * @param array $new_values Optional. New customizer values (not used, but available for future use)
         */
        public function clear_cache( $new_values = null ) {
            // Delete options (persistent cache)
            // This forces regeneration on next page load
            delete_option( $this->css_cache_option );
            delete_option( $this->values_hash_option );

            // Also clear object cache if available
            if ( function_exists( 'wp_cache_delete' ) ) {
                wp_cache_delete( $this->css_cache_option, 'wp_ulike_customizer' );
                wp_cache_delete( $this->values_hash_option, 'wp_ulike_customizer' );
            }
        }
    }

    // Initialize the CSS generator
    if ( ! isset( $GLOBALS['wp_ulike_css_generator'] ) ) {
        $GLOBALS['wp_ulike_css_generator'] = new wp_ulike_css_generator();
    }
}


