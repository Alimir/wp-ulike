<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Taxonomy Options Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'CSF_Taxonomy_Options' ) ) {
  class CSF_Taxonomy_Options extends CSF_Abstract{

    // constans
    public $unique     = '';
    public $taxonomy   = '';
    public $abstract   = 'taxonomy';
    public $sections   = array();
    public $taxonomies = array();
    public $args       = array(
      'taxonomy'       => '',
      'data_type'      => 'serialize',
      'defaults'       => array(),
      'class'          => '',
    );

    // run taxonomy construct
    public function __construct( $key, $params ) {

      $this->unique     = $key;
      $this->args       = apply_filters( "csf_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
      $this->sections   = apply_filters( "csf_{$this->unique}_sections", $params['sections'], $this );
      $this->taxonomies = ( is_array( $this->args['taxonomy'] ) ) ? $this->args['taxonomy'] : array_filter( (array) $this->args['taxonomy'] );
      $this->taxonomy   = csf_get_var( 'taxonomy' );

      if( ! empty( $this->taxonomies ) && in_array( $this->taxonomy, $this->taxonomies ) ) {
        add_action( 'admin_init', array( &$this, 'add_taxonomy_options' ) );
      }

    }

    // instance
    public static function instance( $key, $params ) {
      return new self( $key, $params );
    }

    // add taxonomy add/edit fields
    public function add_taxonomy_options() {

      add_action( $this->taxonomy .'_add_form_fields', array( &$this, 'render_taxonomy_form_fields' ) );
      add_action( $this->taxonomy .'_edit_form', array( &$this, 'render_taxonomy_form_fields' ) );

      add_action( 'created_'. $this->taxonomy, array( &$this, 'save_taxonomy' ) );
      add_action( 'edited_'. $this->taxonomy, array( &$this, 'save_taxonomy' ) );

    }

    // get default value
    public function get_default( $field ) {

      $default = ( isset( $field['id'] ) && isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : null;
      $default = ( isset( $field['default'] ) ) ? $field['default'] : $default;

      return $default;

    }

    // get default value
    public function get_meta_value( $term_id, $field ) {

      $value = null;

      if( ! empty( $term_id ) && ! empty( $field['id'] ) ) {

        if( $this->args['data_type'] !== 'serialize' ) {
          $meta  = get_term_meta( $term_id, $field['id'] );
          $value = ( isset( $meta[0] ) ) ? $meta[0] : null;
        } else {
          $meta  = get_term_meta( $term_id, $this->unique, true );
          $value = ( isset( $meta[$field['id']] ) ) ? $meta[$field['id']] : null;
        }

      }

      $default = $this->get_default( $field );
      $value   = ( isset( $value ) ) ? $value : $default;

      return $value;

    }

    // render taxonomy add/edit form fields
    public function render_taxonomy_form_fields( $term ) {

      $is_term   = ( is_object( $term ) && isset( $term->taxonomy ) ) ? true : false;
      $term_id   = ( $is_term ) ? $term->term_id : 0;
      $taxonomy  = ( $is_term ) ? $term->taxonomy : $term;
      $classname = ( $is_term ) ? 'edit' : 'add';
      $errors    = ( ! empty( $term_id ) ) ? get_term_meta( $term_id, '_csf_errors', true ) : array();
      $errors    = ( ! empty( $errors ) ) ? $errors : array();
      $class     = ( $this->args['class'] ) ? ' '. $this->args['class'] : '';

      // clear errors
      if( ! empty( $errors ) ) {
        delete_term_meta( $term_id, '_csf_errors' );
      }

      wp_nonce_field( 'csf_taxonomy_nonce', 'csf_taxonomy_nonce'. $this->unique );

      echo '<div class="csf csf-taxonomy csf-show-all csf-onload csf-taxonomy-'. $classname .'-fields'. $class .'">';

      foreach( $this->sections as $section ) {

        if( $taxonomy === $this->taxonomy ) {

          $section_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="csf-icon '. $section['icon'] .'"></i>' : '';
          $section_title = ( ! empty( $section['title'] ) ) ? $section['title'] : '';

          echo ( $section_title || $section_icon ) ? '<div class="csf-section-title"><h3>'. $section_icon . $section_title .'</h3></div>' : '';

          if( ! empty( $section['fields'] ) ) {
            foreach( $section['fields'] as $field ) {

              if( ! empty( $field['id'] ) && ! empty( $errors[$field['id']] ) ) {
                $field['_error'] = $errors[$field['id']];
              }

              CSF::field( $field, $this->get_meta_value( $term_id, $field ), $this->unique, 'taxonomy' );

            }
          }
        }

      }

      echo '</div>';

    }

    // save taxonomy form fields
    public function save_taxonomy( $term_id ) {

      if ( wp_verify_nonce( csf_get_var( 'csf_taxonomy_nonce'. $this->unique ), 'csf_taxonomy_nonce' ) ) {

        $errors = array();
        $taxonomy = csf_get_var( 'taxonomy' );

        foreach ( $this->sections as $section ) {

          if( $taxonomy == $this->taxonomy ) {

            $request = csf_get_var( $this->unique, array() );

            // ignore _nonce
            if( isset( $request['_nonce'] ) ) {
              unset( $request['_nonce'] );
            }

            // sanitize and validate
            if( ! empty( $section['fields'] ) ) {

              foreach( $section['fields'] as $field ) {

                if( ! empty( $field['id'] ) ) {

                  // sanitize
                  if( ! empty( $field['sanitize'] ) ) {

                    $sanitize              = $field['sanitize'];
                    $value_sanitize        = csf_get_vars( $this->unique, $field['id'] );
                    $request[$field['id']] = call_user_func( $sanitize, $value_sanitize );

                  }

                  // validate
                  if( ! empty( $field['validate'] ) ) {

                    $validate = $field['validate'];
                    $value_validate = csf_get_vars( $this->unique, $field['id'] );
                    $has_validated = call_user_func( $validate, $value_validate );

                    if( ! empty( $has_validated ) ) {

                      $errors[$field['id']]  = $has_validated;
                      $request[$field['id']] = $this->get_meta_value( $term_id, $field );

                    }

                  }

                  // auto sanitize
                  if( ! isset( $request[$field['id']] ) || is_null( $request[$field['id']] ) ) {
                    $request[$field['id']] = '';
                  }

                }

              }

            }

            $request = apply_filters( "csf_{$this->unique}_save", $request, $term_id, $this );

            do_action( "csf_{$this->unique}_save_before", $request, $term_id, $this );

            if( empty( $request ) ) {

              if( $this->args['data_type'] !== 'serialize' ) {
                foreach ( $request as $key => $value ) {
                  delete_term_meta( $term_id, $key );
                }
              } else {
                delete_term_meta( $term_id, $this->unique );
              }

            } else {

              if( $this->args['data_type'] !== 'serialize' ) {
                foreach ( $request as $key => $value ) {
                  update_term_meta( $term_id, $key, $value );
                }
              } else {
                update_term_meta( $term_id, $this->unique, $request );
              }

              if( ! empty( $errors ) ) {
                update_term_meta( $term_id, '_csf_errors', $errors );
              }

            }

            do_action( "csf_{$this->unique}_saved", $request, $term_id, $this );

            do_action( "csf_{$this->unique}_save_after", $request, $term_id, $this );

          }

        }

      }

    }

  }
}
