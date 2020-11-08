<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Abstract Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ULF_Abstract' ) ) {
  abstract class ULF_Abstract {

    public $abstract   = '';
    public $output_css = '';

    public function __construct() {

      // Collect output css and typography
      if ( ! empty( $this->args['output_css'] ) || ! empty( $this->args['enqueue_webfont'] ) ) {
        add_action( 'wp_enqueue_scripts', array( &$this, 'collect_output_css_and_typography' ), 10 );
      }

    }

    public function collect_output_css_and_typography() {
      $this->recursive_output_css( $this->pre_fields );
    }

    public function recursive_output_css( $fields = array(), $combine_field = array() ) {

      if ( ! empty( $fields ) ) {

        foreach ( $fields as $field ) {

          $field_id     = ( ! empty( $field['id'] ) ) ? $field['id'] : '';
          $field_type   = ( ! empty( $field['type'] ) ) ? $field['type'] : '';
          $field_output = ( ! empty( $field['output'] ) ) ? $field['output'] : '';
          $field_check  = ( $field_type === 'typography' || $field_output ) ? true : false;

          if ( $field_type && $field_id ) {

            ULF::maybe_include_field( $field_type );

            $class_name = 'ULF_Field_' . $field_type;

            if( $field_type === 'fieldset' ) {
              if ( ! empty( $field['fields'] ) ) {
                $this->recursive_output_css( $field['fields'], $field );
              }
            }

            if( $field_type === 'accordion' ) {
              if ( ! empty( $field['accordions'] ) ) {
                foreach ( $field['accordions'] as $accordion ) {
                  $this->recursive_output_css( $accordion['fields'], $field );
                }
              }
            }

            if( $field_type === 'tabbed' ) {
              if ( ! empty( $field['tabs'] ) ) {
                foreach ( $field['tabs'] as $accordion ) {
                  $this->recursive_output_css( $accordion['fields'], $field );
                }
              }
            }

            if ( class_exists( $class_name ) ) {

              if ( method_exists( $class_name, 'output' ) || method_exists( $class_name, 'enqueue_google_fonts' ) ) {

                $field_value = '';

                if ( $field_check && ( $this->abstract === 'options' || $this->abstract === 'customize' ) ) {

                  if( ! empty( $combine_field ) ) {

                    $field_value = ( isset( $this->options[$combine_field['id']][$field_id] ) ) ? $this->options[$combine_field['id']][$field_id] : '';

                  } else {

                    $field_value = ( isset( $this->options[$field_id] ) ) ? $this->options[$field_id] : '';

                  }

                } else if ( $field_check && ( $this->abstract === 'metabox' && is_singular() || $this->abstract === 'taxonomy' && is_archive() ) ) {

                  if( ! empty( $combine_field ) ) {

                    $meta_value  = $this->get_meta_value( $combine_field );
                    $field_value = ( isset( $meta_value[$field_id] ) ) ? $meta_value[$field_id] : '';

                  } else {

                    $meta_value  = $this->get_meta_value( $field );
                    $field_value = ( isset( $meta_value ) ) ? $meta_value : '';

                  }

                }

                $instance = new $class_name( $field, $field_value, $this->unique, 'wp/enqueue', $this );

                // typography enqueue and embed google web fonts
                if ( $field_type === 'typography' && $this->args['enqueue_webfont'] && ! empty( $field_value['font-family'] ) ) {

                  $method = ( ! empty( $this->args['async_webfont'] ) ) ? 'async' : 'enqueue';

                  $instance->enqueue_google_fonts( $method );

                }

                // output css
                if ( $field_output && $this->args['output_css'] ) {
                  ULF::$css .= $instance->output();
                }

                unset( $instance );

              }

            }

          }

        }

      }

    }

  }
}
