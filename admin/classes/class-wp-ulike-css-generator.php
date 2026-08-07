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
         * Bump when schema/output rules change so cached CSS regenerates
         * even if saved customizer values are unchanged.
         */
        const SCHEMA_REVISION = '3';

        /**
         * Constructor
         */
        public function __construct() {
            // No initialization needed - hooks are registered in admin-hooks.php
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

            // Calculate hash of current values + schema revision to detect changes
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

            // Use md5 for fast hashing (security not a concern here, just change detection).
            // SCHEMA_REVISION invalidates cache when output rules change without value edits.
            return md5( $json . '|schema:' . self::SCHEMA_REVISION );
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
                if ( ! class_exists( 'wp_ulike_customizer_api' ) ) {
                    return array();
                }

                $customizer_api = new wp_ulike_customizer_api();
                $schema = $customizer_api->get_schema();

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

            // media_key '' = desktop/base; otherwise full @media (...) string
            $media_maps = array();

            // Process all pages and sections
            foreach ( $schema['pages'] as $page ) {
                if ( ! isset( $page['sections'] ) || ! is_array( $page['sections'] ) ) {
                    continue;
                }

                foreach ( $page['sections'] as $section ) {
                    if ( ! isset( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
                        continue;
                    }

                    // Customizer uses flattened structure (no section prefixes)
                    // All fields are at root level for compatibility with old user data
                    $section_path = '';

                    $this->process_fields( $section['fields'], $values, $section_path, $media_maps );
                }
            }

            return $this->media_maps_to_css( $media_maps );
        }

        /**
         * Convert media → selector → property maps to a CSS string.
         * Order: desktop (base), then tablet, then mobile — matches Optiwich.
         *
         * @param array $media_maps Nested maps keyed by media query ('' for base).
         * @return string
         */
        protected function media_maps_to_css( $media_maps ) {
            $media_order = array(
                '',
                '@media (min-width: 768px) and (max-width: 1024px)',
                '@media (max-width: 767px)',
            );

            $blocks = array();

            foreach ( $media_order as $media_key ) {
                if ( empty( $media_maps[ $media_key ] ) || ! is_array( $media_maps[ $media_key ] ) ) {
                    continue;
                }

                $css_rules = $this->selector_map_to_rules( $media_maps[ $media_key ], $media_key ? '  ' : '' );
                if ( empty( $css_rules ) ) {
                    continue;
                }

                if ( $media_key === '' ) {
                    $blocks[] = implode( "\n\n", $css_rules );
                } else {
                    $blocks[] = $media_key . " {\n" . implode( "\n\n", $css_rules ) . "\n}";
                }
            }

            // Any unexpected media keys (future breakpoints) after the standard order.
            foreach ( $media_maps as $media_key => $selector_map ) {
                if ( in_array( $media_key, $media_order, true ) ) {
                    continue;
                }
                if ( empty( $selector_map ) || ! is_array( $selector_map ) ) {
                    continue;
                }
                $css_rules = $this->selector_map_to_rules( $selector_map, '  ' );
                if ( ! empty( $css_rules ) ) {
                    $blocks[] = $media_key . " {\n" . implode( "\n\n", $css_rules ) . "\n}";
                }
            }

            return implode( "\n\n", $blocks );
        }

        /**
         * @param array  $selector_map Selector => property => value
         * @param string $indent       Indent for nested media blocks
         * @return array List of CSS rule strings
         */
        protected function selector_map_to_rules( $selector_map, $indent = '' ) {
            $css_rules = array();

            foreach ( $selector_map as $selector => $properties ) {
                if ( empty( $properties ) || ! is_array( $properties ) ) {
                    continue;
                }

                $props = array();
                foreach ( $properties as $property => $value ) {
                    if ( is_string( $property ) && is_string( $value ) ) {
                        $props[] = $indent . '  ' . $property . ': ' . $value . ';';
                    }
                }

                if ( ! empty( $props ) ) {
                    $css_rules[] = $indent . $selector . ' {' . "\n" . implode( "\n", $props ) . "\n" . $indent . '}';
                }
            }

            return $css_rules;
        }

        /**
         * Process fields recursively
         *
         * @param array  $fields     Fields array
         * @param array  $values     Values array
         * @param string $path       Current path prefix
         * @param array  $media_maps Reference to media → selector → properties map
         */
        protected function process_fields( $fields, $values, $path, &$media_maps ) {
            foreach ( $fields as $field ) {
                if ( ! isset( $field['id'] ) ) {
                    continue;
                }

                // Build field path
                $field_path = $path ? $path . '.' . $field['id'] : $field['id'];

                // Get value from values array
                $value = $this->get_nested_value( $values, $field_path );

                // Skip if no value (including false for disabled fields)
                if ( $value === null || $value === '' || $value === false ) {
                    // Still process nested fields even if parent has no value
                    if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                        $this->process_fields( $field['fields'], $values, $field_path, $media_maps );
                    }
                    continue;
                }

                // Handle tabbed fields
                if ( isset( $field['type'] ) && $field['type'] === 'tabbed' && isset( $field['tabs'] ) ) {
                    foreach ( $field['tabs'] as $tab ) {
                        if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
                            $this->process_fields( $tab['fields'], $values, $field_path, $media_maps );
                        }
                    }
                    continue;
                }

                // Handle group fields
                if ( isset( $field['type'] ) && $field['type'] === 'group' && isset( $field['fields'] ) ) {
                    $group_value = is_array( $value ) ? $value : array();
                    foreach ( $group_value as $index => $item ) {
                        if ( is_array( $item ) ) {
                            $this->process_fields( $field['fields'], $item, $field_path . '[' . $index . ']', $media_maps );
                        }
                    }
                    continue;
                }

                // Generate CSS if field has output selector or mapped output_css (button_set/select).
                if ( ( isset( $field['output'] ) && ! empty( $field['output'] ) ) || ! empty( $field['output_css'] ) ) {
                    $outputs = $this->generate_css_from_field( $field, $value );
                    foreach ( $outputs as $output ) {
                        $selector = $this->sanitize_css_selector( $output['selector'] );
                        if ( empty( $selector ) ) {
                            continue;
                        }

                        $media_key = isset( $output['media'] ) && is_string( $output['media'] ) ? $output['media'] : '';
                        if ( ! isset( $media_maps[ $media_key ] ) ) {
                            $media_maps[ $media_key ] = array();
                        }
                        if ( ! isset( $media_maps[ $media_key ][ $selector ] ) ) {
                            $media_maps[ $media_key ][ $selector ] = array();
                        }

                        $property = $this->sanitize_css_property( $output['property'] );
                        if ( $property ) {
                            $media_maps[ $media_key ][ $selector ][ $property ] = $this->sanitize_css_value( $output['value'], $property );
                        }
                    }
                }

                // Process nested fields
                if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
                    $this->process_fields( $field['fields'], $values, $field_path, $media_maps );
                }
            }
        }

        /**
         * Whether a stored value is a responsive envelope { desktop, tablet?, mobile? }.
         * Legacy flat spacing/dimensions/typography objects are NOT envelopes.
         *
         * @param mixed $value Field value
         * @return bool
         */
        protected function is_responsive_envelope( $value ) {
            if ( ! is_array( $value ) ) {
                return false;
            }

            $has_device = isset( $value['desktop'] ) || isset( $value['tablet'] ) || isset( $value['mobile'] );
            if ( ! $has_device ) {
                return false;
            }

            $flat_keys = array(
                'top', 'right', 'bottom', 'left', 'unit',
                'width', 'height', 'style',
                'fontfamily', 'fontsize', 'fontweight', 'lineheight', 'letterspacing',
                'textalign', 'texttransform', 'textdecoration', 'color',
                'backgroundcolor', 'background-color', 'backgroundimage',
                'backgroundrepeat', 'backgroundposition', 'backgroundsize', 'backgroundattachment',
            );

            foreach ( array_keys( $value ) as $key ) {
                if ( in_array( $key, array( 'desktop', 'tablet', 'mobile' ), true ) ) {
                    continue;
                }
                if ( in_array( strtolower( (string) $key ), $flat_keys, true ) ) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Responsive breakpoints (Elementor-style). Desktop = base (no media query).
         *
         * @return array device => media query string|null
         */
        protected function get_responsive_breakpoints() {
            return array(
                'desktop' => null,
                // Exclusive mid-range — must not overlap mobile (max-width: 767px).
                'tablet'  => '@media (min-width: 768px) and (max-width: 1024px)',
                'mobile'  => '@media (max-width: 767px)',
            );
        }

        /**
         * Generate CSS from a single field
         *
         * Supports legacy flat values and responsive envelopes when field.responsive is true.
         *
         * @param array $field Field definition
         * @param mixed $value Field value
         * @return array Array of CSS outputs (optional `media` key for tablet/mobile)
         */
        protected function generate_css_from_field( $field, $value ) {
            // Responsive envelope → per-device flat generation + media wrappers
            if ( ! empty( $field['responsive'] ) && $this->is_responsive_envelope( $value ) ) {
                $all_outputs = array();
                $breakpoints = $this->get_responsive_breakpoints();

                foreach ( $breakpoints as $device => $media ) {
                    if ( ! isset( $value[ $device ] ) || $value[ $device ] === null || $value[ $device ] === '' ) {
                        continue;
                    }
                    $device_outputs = $this->generate_css_from_flat_field( $field, $value[ $device ] );
                    foreach ( $device_outputs as $output ) {
                        if ( $media ) {
                            $output['media'] = $media;
                        }
                        $all_outputs[] = $output;
                    }
                }

                return $all_outputs;
            }

            return $this->generate_css_from_flat_field( $field, $value );
        }

        /**
         * Generate CSS from a flat (non-envelope) field value.
         *
         * @param array $field Field definition
         * @param mixed $value Flat field value
         * @return array Array of CSS outputs
         */
        protected function generate_css_from_flat_field( $field, $value ) {
            $outputs = array();

            $selector = isset( $field['output'] ) ? $field['output'] : '';
            $output_mode = isset( $field['output_mode'] ) ? $field['output_mode'] : '';
            $output_important = isset( $field['output_important'] ) && $field['output_important'];
            $field_type = isset( $field['type'] ) ? $field['type'] : '';
            $important = $output_important ? ' !important' : '';

            // button_set / select with per-value CSS maps (e.g. toast corner position).
            if ( in_array( $field_type, array( 'button_set', 'select' ), true ) && ! empty( $field['output_css'] ) && is_array( $field['output_css'] ) ) {
                if ( $value === null || $value === '' || $value === false ) {
                    return $outputs;
                }
                $key = is_scalar( $value ) ? (string) $value : '';
                if ( '' === $key || empty( $field['output_css'][ $key ] ) || ! is_array( $field['output_css'][ $key ] ) ) {
                    return $outputs;
                }
                foreach ( $field['output_css'][ $key ] as $rule ) {
                    if ( empty( $rule['selector'] ) || empty( $rule['property'] ) || ! isset( $rule['value'] ) ) {
                        continue;
                    }
                    $prop = (string) $rule['property'];
                    $css_value = $this->sanitize_css_value( $rule['value'], $prop );
                    if ( ! $css_value && 'auto' !== $rule['value'] ) {
                        // Allow CSS keywords / var() that sanitizer may pass through empty.
                        $raw = trim( (string) $rule['value'] );
                        if ( '' === $raw || ! preg_match( '/^(auto|inherit|initial|unset|var\(.+\))$/i', $raw ) ) {
                            continue;
                        }
                        $css_value = $raw;
                    }
                    if ( ! $css_value ) {
                        continue;
                    }
                    $outputs[] = array(
                        'selector' => (string) $rule['selector'],
                        'property' => $prop,
                        'value'    => $css_value . $important,
                    );
                }
                return $outputs;
            }

            // Skip if no selector or empty/false value
            if ( empty( $selector ) || $value === null || $value === '' || $value === false ) {
                return $outputs;
            }

            switch ( $field_type ) {
                case 'color':
                    $property = $output_mode ? $output_mode : 'color';

                    // Icon colors on background-image SVGs → CSS filter chain.
                    if ( 'filter' === $property ) {
                        $filter_value = class_exists( 'wp_ulike_color_filter' )
                            ? wp_ulike_color_filter::from_color( $value )
                            : '';
                        if ( $filter_value ) {
                            $outputs[] = array(
                                'selector' => $selector,
                                'property' => 'filter',
                                'value'    => $filter_value . $important,
                            );
                        }

                        // Inline SVGs (Animated Heart) still take a real fill color.
                        if ( ! empty( $field['output_also'] ) && is_array( $field['output_also'] ) ) {
                            foreach ( $field['output_also'] as $extra ) {
                                if ( empty( $extra['selector'] ) || ! is_string( $extra['selector'] ) ) {
                                    continue;
                                }
                                $extra_prop = ! empty( $extra['property'] ) ? $extra['property'] : 'fill';
                                $extra_val  = $this->sanitize_css_value( $value, $extra_prop );
                                if ( $extra_val ) {
                                    $outputs[] = array(
                                        'selector' => $extra['selector'],
                                        'property' => $extra_prop,
                                        'value'    => $extra_val . $important,
                                    );
                                }
                            }
                        }
                        break;
                    }

                    $css_value = $this->sanitize_css_value( $value, $property );
                    if ( $css_value ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => $property,
                            'value' => $css_value . $important
                        );

                        // Optional soft focus ring from the same color (forms error/success/focus).
                        if ( ! empty( $field['focus_ring'] ) ) {
                            $alpha = isset( $field['focus_ring_alpha'] ) ? (float) $field['focus_ring_alpha'] : 0.15;
                            $ring  = $this->color_to_focus_ring_shadow( $css_value, $alpha );
                            if ( $ring ) {
                                $ring_selector = ! empty( $field['focus_ring_selector'] ) ? $field['focus_ring_selector'] : $selector;
                                $outputs[]     = array(
                                    'selector' => $ring_selector,
                                    'property' => 'box-shadow',
                                    'value'    => $ring . $important,
                                );
                            }
                        }

                        // Optional extra selectors/properties using the same color value.
                        if ( ! empty( $field['output_also'] ) && is_array( $field['output_also'] ) ) {
                            foreach ( $field['output_also'] as $extra ) {
                                if ( empty( $extra['selector'] ) || ! is_string( $extra['selector'] ) ) {
                                    continue;
                                }
                                $extra_prop = ! empty( $extra['property'] ) ? $extra['property'] : 'color';
                                $extra_val  = $this->sanitize_css_value( $value, $extra_prop );
                                if ( $extra_val ) {
                                    $outputs[] = array(
                                        'selector' => $extra['selector'],
                                        'property' => $extra_prop,
                                        'value'    => $extra_val . $important,
                                    );
                                }
                            }
                        }
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
                        $output_prefix = ! empty( $field['output_prefix'] ) ? (string) $field['output_prefix'] : '';
                        $outputs       = array_merge(
                            $outputs,
                            $this->generate_dimensions_css( $value, $selector, $important, $output_mode, $output_prefix )
                        );
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
                    // Allow false (disabled), but skip CSS generation for false/empty values.
                    // Coerce legacy dimensions-shaped values (width/height) to a single number.
                    if ( is_array( $value ) ) {
                        $legacy = '';
                        if ( isset( $value['width'] ) && $value['width'] !== '' && $value['width'] !== null ) {
                            $legacy = $value['width'];
                        } elseif ( isset( $value['height'] ) && $value['height'] !== '' && $value['height'] !== null ) {
                            $legacy = $value['height'];
                        }
                        if ( $legacy === '' || $legacy === null ) {
                            break;
                        }
                        if ( is_string( $legacy ) && preg_match( '/^(\d+(?:\.\d+)?)/', $legacy, $matches ) ) {
                            $value = $matches[1];
                        } else {
                            $value = $legacy;
                        }
                    }

                    if ( $value !== '' && $value !== null && $value !== false ) {
                        $unit     = isset( $field['unit'] ) ? $field['unit'] : '';
                        $property = $output_mode ? $output_mode : 'width';
                        $str      = trim( (string) $value );
                        // Avoid double units when spinner/legacy values already include one (e.g. "24px").
                        if ( preg_match( '/^-?\d+(?:\.\d+)?(px|em|rem|%|vh|vw|pt)$/i', $str ) ) {
                            $num_value = $str;
                        } else {
                            $num_value = $str . $unit;
                        }
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
         *
         * @param array  $value         Dimensions value.
         * @param string $selector      CSS selector.
         * @param string $important     Optional !important suffix.
         * @param string $output_mode   Optional custom property (e.g. --var).
         * @param string $output_prefix Optional property prefix (e.g. "max" → max-width / max-height).
         * @return array
         */
        protected function generate_dimensions_css( $value, $selector, $important, $output_mode = '', $output_prefix = '' ) {
            $outputs = array();
            $default_unit = isset( $value['unit'] ) ? $value['unit'] : 'px';
            $prefix       = is_string( $output_prefix ) ? trim( $output_prefix ) : '';
            $width_prop   = $prefix ? $prefix . '-width' : 'width';
            $height_prop  = $prefix ? $prefix . '-height' : 'height';

            $resolve_dimension = function( $raw ) use ( $default_unit ) {
                if ( $raw === '' || $raw === null ) {
                    return '';
                }

                if ( ! preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|pt)$/', (string) $raw ) ) {
                    return $raw . $default_unit;
                }

                return (string) $raw;
            };

            if ( $output_mode && strpos( $output_mode, '--' ) === 0 ) {
                $size = '';

                if ( isset( $value['width'] ) && $value['width'] !== '' && $value['width'] !== null ) {
                    $size = $resolve_dimension( $value['width'] );
                } elseif ( isset( $value['height'] ) && $value['height'] !== '' && $value['height'] !== null ) {
                    $size = $resolve_dimension( $value['height'] );
                }

                if ( $size ) {
                    $css_value = $this->sanitize_css_value( $size, $output_mode );
                    if ( $css_value ) {
                        $outputs[] = array(
                            'selector' => $selector,
                            'property' => $output_mode,
                            'value'    => $css_value . $important,
                        );
                    }
                }

                return $outputs;
            }

            if ( isset( $value['width'] ) && $value['width'] !== '' && $value['width'] !== null ) {
                $width = $resolve_dimension( $value['width'] );
                $css_value = $this->sanitize_css_value( $width, $width_prop );
                if ( $css_value ) {
                    $outputs[] = array(
                        'selector' => $selector,
                        'property' => $width_prop,
                        'value'    => $css_value . $important,
                    );
                }
            }

            if ( isset( $value['height'] ) && $value['height'] !== '' && $value['height'] !== null ) {
                $height = $resolve_dimension( $value['height'] );
                $css_value = $this->sanitize_css_value( $height, $height_prop );
                if ( $css_value ) {
                    $outputs[] = array(
                        'selector' => $selector,
                        'property' => $height_prop,
                        'value'    => $css_value . $important,
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

            // Base CSS properties - extensible via filter
            $base_properties = array(
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
                'opacity', 'transform', 'transition', 'box-shadow', 'text-shadow', 'filter', 'fill', 'stroke',
                'border-top-left-radius', 'border-top-right-radius', 'border-bottom-left-radius', 'border-bottom-right-radius',
                'flex', 'flex-direction', 'flex-wrap', 'justify-content', 'align-items', 'align-content',
                'grid', 'grid-template-columns', 'grid-template-rows', 'grid-gap', 'gap'
            );

            /**
             * Filter valid CSS properties for customizer output
             * Allows themes and plugins to extend supported CSS properties
             *
             * @param array $base_properties Base CSS properties
             * @return array Extended CSS properties
             */
            $valid_properties = apply_filters( 'wp_ulike_css_valid_properties', $base_properties );

            $property_lower = strtolower( $property );

            // Allow CSS custom properties (e.g. --ulp-eng-reaction-size)
            if ( preg_match( '/^--[a-z0-9_-]+$/', $property_lower ) ) {
                return $property;
            }

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

                // CSS custom properties (e.g. --ulp-eng-reaction-size)
                if ( strpos( $prop_lower, '--' ) === 0 ) {
                    if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $sanitized ) ||
                         preg_match( '/^(rgb|rgba|hsl|hsla)\([^)]+\)$/', $sanitized ) ||
                         preg_match( '/^var\(--[a-zA-Z0-9_-]+\)$/', $sanitized ) ) {
                        return $this->sanitize_color_value( $sanitized );
                    }

                    if ( preg_match( '/^(\d+(\.\d+)?(px|em|rem|%|vh|vw|pt|ch|ex|cm|mm|in|pc)|0|auto|calc\([^)]+\))$/i', $sanitized ) ) {
                        return $this->sanitize_length_value( $sanitized );
                    }

                    return esc_attr( $sanitized );
                }

                // Color values (hex, rgb, rgba, hsl, hsla, named colors, CSS variables, SVG fill/stroke)
                if ( strpos( $prop_lower, 'color' ) !== false || $prop_lower === 'border-color' || $prop_lower === 'fill' || $prop_lower === 'stroke' ) {
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
         * Build a soft focus/validation ring from a color value.
         *
         * @param string $color Sanitized CSS color.
         * @param float  $alpha Ring opacity 0–1.
         * @return string Empty when color cannot be parsed.
         */
        protected function color_to_focus_ring_shadow( $color, $alpha = 0.15 ) {
            $rgb = $this->parse_color_to_rgb( $color );
            if ( ! $rgb ) {
                return '';
            }

            $alpha = max( 0, min( 1, (float) $alpha ) );
            $alpha_str = rtrim( rtrim( number_format( $alpha, 3, '.', '' ), '0' ), '.' );
            if ( '' === $alpha_str ) {
                $alpha_str = '0';
            }

            return sprintf(
                '0 0 0 3px rgba(%d, %d, %d, %s)',
                (int) $rgb[0],
                (int) $rgb[1],
                (int) $rgb[2],
                $alpha_str
            );
        }

        /**
         * Parse hex/rgb/rgba into [r,g,b].
         *
         * @param string $color Color string.
         * @return int[]|null
         */
        protected function parse_color_to_rgb( $color ) {
            $color = trim( (string) $color );

            if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color, $m ) ) {
                $hex = $m[1];
                if ( 3 === strlen( $hex ) ) {
                    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                }
                return array(
                    hexdec( substr( $hex, 0, 2 ) ),
                    hexdec( substr( $hex, 2, 2 ) ),
                    hexdec( substr( $hex, 4, 2 ) ),
                );
            }

            if ( preg_match( '/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})/i', $color, $m ) ) {
                return array(
                    max( 0, min( 255, (int) $m[1] ) ),
                    max( 0, min( 255, (int) $m[2] ) ),
                    max( 0, min( 255, (int) $m[3] ) ),
                );
            }

            return null;
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
}


