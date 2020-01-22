<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Profile Options Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'CSF_Profile_Options' ) ) {
  class CSF_Profile_Options extends CSF_Abstract{

    // constans
    public $unique   = '';
    public $abstract = 'profile';
    public $sections = array();
    public $args     = array(
      'data_type'    => 'serialize',
      'defaults'     => array(),
      'class'        => '',
    );

    // run profile construct
    public function __construct( $key, $params ) {

      $this->unique   = $key;
      $this->args     = apply_filters( "csf_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
      $this->sections = apply_filters( "csf_{$this->unique}_sections", $params['sections'], $this );

      add_action( 'admin_init', array( &$this, 'add_profile_options' ) );

    }

    // instance
    public static function instance( $key, $params ) {
      return new self( $key, $params );
    }

    // add profile add/edit fields
    public function add_profile_options() {

      add_action( 'show_user_profile', array( &$this, 'render_profile_form_fields' ) );
      add_action( 'edit_user_profile', array( &$this, 'render_profile_form_fields' ) );

      add_action( 'personal_options_update', array( &$this, 'save_profile' ) );
      add_action( 'edit_user_profile_update', array( &$this, 'save_profile' ) );

    }

    // get default value
    public function get_default( $field ) {

      $default = ( isset( $field['id'] ) && isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : null;
      $default = ( isset( $field['default'] ) ) ? $field['default'] : $default;

      return $default;

    }

    // get default value
    public function get_meta_value( $user_id, $field ) {

      $value = null;

      if( ! empty( $user_id ) && ! empty( $field['id'] ) ) {

        if( $this->args['data_type'] !== 'serialize' ) {
          $meta  = get_user_meta( $user_id, $field['id'] );
          $value = ( isset( $meta[0] ) ) ? $meta[0] : null;
        } else {
          $meta  = get_user_meta( $user_id, $this->unique, true );
          $value = ( isset( $meta[$field['id']] ) ) ? $meta[$field['id']] : null;
        }

      }

      $default = $this->get_default( $field );
      $value   = ( isset( $value ) ) ? $value : $default;

      return $value;

    }

    // render profile add/edit form fields
    public function render_profile_form_fields( $profileuser ) {

      $is_profile = ( is_object( $profileuser ) && isset( $profileuser->ID ) ) ? true : false;
      $profile_id = ( $is_profile ) ? $profileuser->ID : 0;
      $errors     = ( ! empty( $profile_id ) ) ? get_user_meta( $profile_id, '_csf_errors', true ) : array();
      $errors     = ( ! empty( $errors ) ) ? $errors : array();
      $class      = ( $this->args['class'] ) ? ' '. $this->args['class'] : '';

      // clear errors
      if( ! empty( $errors ) ) {
        delete_user_meta( $profile_id, '_csf_errors' );
      }

      echo '<div class="csf csf-profile csf-onload'. $class .'">';

      wp_nonce_field( 'csf_profile_nonce', 'csf_profile_nonce'. $this->unique );

      foreach( $this->sections as $section ) {

        $section_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="csf-icon '. $section['icon'] .'"></i>' : '';
        $section_title = ( ! empty( $section['title'] ) ) ? $section['title'] : '';

        echo ( $section_title || $section_icon ) ? '<h2>'. $section_icon . $section_title .'</h2>' : '';

        if( ! empty( $section['fields'] ) ) {
          foreach( $section['fields'] as $field ) {

            if( ! empty( $field['id'] ) && ! empty( $errors[$field['id']] ) ) {
              $field['_error'] = $errors[$field['id']];
            }

            CSF::field( $field, $this->get_meta_value( $profile_id, $field ), $this->unique, 'profile' );

          }
        }

      }

      echo '</div>';

    }

    // save profile form fields
    public function save_profile( $user_id ) {

      if ( wp_verify_nonce( csf_get_var( 'csf_profile_nonce'. $this->unique ), 'csf_profile_nonce' ) ) {

        $errors = array();

        foreach ( $this->sections as $section ) {

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
                    $request[$field['id']] = $this->get_meta_value( $user_id, $field );

                  }

                }

                // auto sanitize
                if( ! isset( $request[$field['id']] ) || is_null( $request[$field['id']] ) ) {
                  $request[$field['id']] = '';
                }

              }

            }

          }

          $request = apply_filters( "csf_{$this->unique}_save", $request, $user_id, $this );

          do_action( "csf_{$this->unique}_save_before", $request, $user_id, $this );

          if( empty( $request ) ) {

            if( $this->args['data_type'] !== 'serialize' ) {
              foreach ( $request as $key => $value ) {
                delete_user_meta( $user_id, $key );
              }
            } else {
              delete_user_meta( $user_id, $this->unique );
            }

          } else {

            if( $this->args['data_type'] !== 'serialize' ) {
              foreach ( $request as $key => $value ) {
                update_user_meta( $user_id, $key, $value );
              }
            } else {
              update_user_meta( $user_id, $this->unique, $request );
            }

            if( ! empty( $errors ) ) {
              update_user_meta( $user_id, '_csf_errors', $errors );
            }

          }

          do_action( "csf_{$this->unique}_saved", $request, $user_id, $this );

          do_action( "csf_{$this->unique}_save_after", $request, $user_id, $this );

        }

      }

    }

  }
}
