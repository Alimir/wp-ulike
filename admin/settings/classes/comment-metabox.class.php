<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Comment Metabox Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Comment_Metabox' ) ) {
  class CSF_Comment_Metabox extends CSF_Abstract{

    // constans
    public $unique     = '';
    public $abstract   = 'comment_metabox';
    public $pre_fields = array();
    public $sections   = array();
    public $args       = array(
      'title'          => '',
      'data_type'      => 'serialize',
      'priority'       => 'default',
      'show_restore'   => false,
      'theme'          => 'dark',
      'class'          => '',
      'defaults'       => array(),
    );

    // run comment metabox construct
    public function __construct( $key, $params = array() ) {

      $this->unique     = $key;
      $this->args       = apply_filters( "csf_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
      $this->sections   = apply_filters( "csf_{$this->unique}_sections", $params['sections'], $this );
      $this->pre_fields = $this->pre_fields( $this->sections );

      add_action( 'add_meta_boxes_comment', array( &$this, 'add_comment_meta_box' ) );
      add_action( 'edit_comment', array( &$this, 'save_comment_meta_box' ) );

      if ( ! empty( $this->args['class'] ) ) {
        add_filter( 'postbox_classes_comment_'. $this->unique, array( &$this, 'add_comment_metabox_classes' ) );
      }

      // wp enqeueu for typography and output css
      parent::__construct();

    }

    // instance
    public static function instance( $key, $params = array() ) {
      return new self( $key, $params );
    }

    public function pre_fields( $sections ) {

      $result  = array();

      foreach ( $sections as $key => $section ) {
        if ( ! empty( $section['fields'] ) ) {
          foreach ( $section['fields'] as $field ) {
            $result[] = $field;
          }
        }
      }

      return $result;
    }

    public function add_comment_metabox_classes( $classes ) {

      if ( ! empty( $this->args['class'] ) ) {
        $classes[] = $this->args['class'];
      }

      return $classes;

    }

    // add comment metabox
    public function add_comment_meta_box( $post_type ) {

      add_meta_box( $this->unique, wp_kses_post( $this->args['title'] ), array( &$this, 'add_comment_meta_box_content' ), 'comment', 'normal', $this->args['priority'], $this->args );

    }

    // get default value
    public function get_default( $field ) {

      $default = ( isset( $field['default'] ) ) ? $field['default'] : '';
      $default = ( isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : $default;

      return $default;

    }

    // get meta value
    public function get_meta_value( $comment_id, $field ) {

      $value = null;

      if ( ! empty( $comment_id ) && ! empty( $field['id'] ) ) {

        if ( $this->args['data_type'] !== 'serialize' ) {
          $meta  = get_comment_meta( $comment_id, $field['id'] );
          $value = ( isset( $meta[0] ) ) ? $meta[0] : null;
        } else {
          $meta  = get_comment_meta( $comment_id, $this->unique, true );
          $value = ( isset( $meta[$field['id']] ) ) ? $meta[$field['id']] : null;
        }

      }

      $default = ( isset( $field['id'] ) ) ? $this->get_default( $field ) : '';
      $value   = ( isset( $value ) ) ? $value : $default;

      return $value;

    }

    // add comment metabox content
    public function add_comment_meta_box_content( $comment, $callback ) {

      $has_nav  = ( count( $this->sections ) > 1 ) ? true : false;
      $show_all = ( ! $has_nav ) ? ' csf-show-all' : '';
      $errors   = ( is_object ( $comment ) ) ? get_comment_meta( $comment->comment_ID, '_csf_errors_'. $this->unique, true ) : array();
      $errors   = ( ! empty( $errors ) ) ? $errors : array();
      $theme    = ( $this->args['theme'] ) ? ' csf-theme-'. $this->args['theme'] : '';

      if ( is_object( $comment ) && ! empty( $errors ) ) {
        delete_comment_meta( $comment->comment_ID, '_csf_errors_'. $this->unique );
      }

      wp_nonce_field( 'csf_comment_metabox_nonce', 'csf_comment_metabox_nonce'. $this->unique );

      echo '<div class="csf csf-comment-metabox'. esc_attr( $theme ) .'">';

        echo '<div class="csf-wrapper'. esc_attr( $show_all ) .'">';

          if ( $has_nav ) {

            echo '<div class="csf-nav csf-nav-metabox" data-unique="'. esc_attr( $this->unique ) .'">';

              echo '<ul>';

              $tab_key = 1;

              foreach ( $this->sections as $section ) {

                $tab_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="csf-tab-icon '. esc_attr( $section['icon'] ) .'"></i>' : '';
                $tab_error = ( ! empty( $errors['sections'][$tab_key] ) ) ? '<i class="csf-label-error csf-error">!</i>' : '';

                echo '<li><a href="#" data-section="'. esc_attr( $this->unique .'_'. $tab_key ) .'">'. wp_kses_post( $tab_icon . $section['title'] . $tab_error ) .'</a></li>';

                $tab_key++;

              }

              echo '</ul>';

            echo '</div>';

          }

          echo '<div class="csf-content">';

            echo '<div class="csf-sections">';

            $section_key = 1;

            foreach ( $this->sections as $section ) {

              $onload = ( ! $has_nav ) ? ' csf-onload' : '';

              echo '<div id="csf-section-'. esc_attr( $this->unique .'_'. $section_key ) .'" class="csf-section'. esc_attr( $onload ) .'">';

              $section_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="csf-section-icon '. esc_attr( $section['icon'] ) .'"></i>' : '';
              $section_title = ( ! empty( $section['title'] ) ) ? $section['title'] : '';

              echo ( $section_title || $section_icon ) ? '<div class="csf-section-title"><h3>'. wp_kses_post( $section_icon . $section_title ) .'</h3></div>' : '';

              if ( ! empty( $section['fields'] ) ) {

                foreach ( $section['fields'] as $field ) {

                  if ( ! empty( $field['id'] ) && ! empty( $errors['fields'][$field['id']] ) ) {
                    $field['_error'] = $errors['fields'][$field['id']];
                  }

                  if ( ! empty( $field['id'] ) ) {
                    $field['default'] = $this->get_default( $field );
                  }

                  CSF::field( $field, $this->get_meta_value( $comment->comment_ID, $field ), $this->unique, 'comment_metabox' );

                }

              } else {

                echo '<div class="csf-no-option csf-text-muted">'. esc_html__( 'No option provided by developer.', 'csf' ) .'</div>';

              }

              echo '</div>';

              $section_key++;

            }

            echo '</div>';

            echo '<div class="clear"></div>';

            if ( ! empty( $this->args['show_restore'] ) ) {

              echo '<div class="csf-restore-wrapper">';
              echo '<label>';
              echo '<input type="checkbox" name="'. esc_attr( $this->unique ) .'[_restore]" />';
              echo '<span class="button csf-button-restore">'. esc_html__( 'Restore', 'csf' ) .'</span>';
              echo '<span class="button csf-button-cancel">'. sprintf( '<small>( %s )</small> %s', esc_html__( 'update post for restore ', 'csf' ), esc_html__( 'Cancel', 'csf' ) ) .'</span>';
              echo '</label>';
              echo '</div>';

            }

          echo '</div>';

          echo ( $has_nav ) ? '<div class="csf-nav-background"></div>' : '';

          echo '<div class="clear"></div>';

        echo '</div>';

      echo '</div>';

    }

    // save comment metabox
    public function save_comment_meta_box( $comment_id ) {

      $count    = 1;
      $data     = array();
      $errors   = array();
      $noncekey = 'csf_comment_metabox_nonce'. $this->unique;
      $nonce    = ( ! empty( $_POST[ $noncekey ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $noncekey ] ) ) : '';

      if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $nonce, 'csf_comment_metabox_nonce' ) ) {
        return $comment_id;
      }

      // XSS ok.
      // No worries, This "POST" requests is sanitizing in the below foreach.
      $request = ( ! empty( $_POST[ $this->unique ] ) ) ? $_POST[ $this->unique ] : array();

      if ( ! empty( $request ) ) {

        foreach ( $this->sections as $section ) {

          if ( ! empty( $section['fields'] ) ) {

            foreach ( $section['fields'] as $field ) {

              if ( ! empty( $field['id'] ) ) {

                $field_id    = $field['id'];
                $field_value = isset( $request[$field_id] ) ? $request[$field_id] : '';

                // Sanitize "post" request of field.
                if ( ! isset( $field['sanitize'] ) ) {

                  if( is_array( $field_value ) ) {
                    $data[$field_id] = wp_kses_post_deep( $field_value );
                  } else {
                    $data[$field_id] = wp_kses_post( $field_value );
                  }

                } else if( isset( $field['sanitize'] ) && function_exists( $field['sanitize'] ) ) {

                  $data[$field_id] = call_user_func( $field['sanitize'], $field_value );

                } else {

                  $data[$field_id] = $field_value;

                }

                // Validate "post" request of field.
                if ( isset( $field['validate'] ) && function_exists( $field['validate'] ) ) {

                  $has_validated = call_user_func( $field['validate'], $field_value );

                  if ( ! empty( $has_validated ) ) {

                    $errors['sections'][$count] = true;
                    $errors['fields'][$field_id] = $has_validated;
                    $data[$field_id] = $this->get_meta_value( $comment_id, $field );

                  }

                }

              }

            }

          }

          $count++;

        }

      }

      $data = apply_filters( "csf_{$this->unique}_save", $data, $comment_id, $this );

      do_action( "csf_{$this->unique}_save_before", $data, $comment_id, $this );

      if ( empty( $data ) || ! empty( $request['_restore'] ) ) {

        if ( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $data as $key => $value ) {
            delete_comment_meta( $comment_id, $key );
          }
        } else {
          delete_comment_meta( $comment_id, $this->unique );
        }

      } else {

        if ( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $data as $key => $value ) {
            update_comment_meta( $comment_id, $key, $value );
          }
        } else {
          update_comment_meta( $comment_id, $this->unique, $data );
        }

        if ( ! empty( $errors ) ) {
          update_comment_meta( $comment_id, '_csf_errors_'. $this->unique, $errors );
        }

      }

      do_action( "csf_{$this->unique}_saved", $data, $comment_id, $this );

      do_action( "csf_{$this->unique}_save_after", $data, $comment_id, $this );

    }
  }
}
