<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Comment Metabox Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'CSF_Comment_Metabox' ) ) {
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

      if( ! empty( $this->args['class'] ) ) {
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

      foreach( $sections as $key => $section ) {
        if( ! empty( $section['fields'] ) ) {
          foreach( $section['fields'] as $field ) {
            $result[] = $field;
          }
        }
      }

      return $result;
    }

    public function add_comment_metabox_classes( $classes ) {

      if( ! empty( $this->args['class'] ) ) {
        $classes[] = $this->args['class'];
      }

      return $classes;

    }

    // add comment metabox
    public function add_comment_meta_box( $post_type ) {

      add_meta_box( $this->unique, $this->args['title'], array( &$this, 'add_comment_meta_box_content' ), 'comment', 'normal', $this->args['priority'], $this->args );

    }

    // get default value
    public function get_default( $field ) {

      $default = ( isset( $field['id'] ) && isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : null;
      $default = ( isset( $field['default'] ) ) ? $field['default'] : $default;

      return $default;

    }

    // get meta value
    public function get_meta_value( $comment_id, $field ) {

      $value = null;

      if( ! empty( $comment_id ) && ! empty( $field['id'] ) ) {

        if( $this->args['data_type'] !== 'serialize' ) {
          $meta  = get_comment_meta( $comment_id, $field['id'] );
          $value = ( isset( $meta[0] ) ) ? $meta[0] : null;
        } else {
          $meta  = get_comment_meta( $comment_id, $this->unique, true );
          $value = ( isset( $meta[$field['id']] ) ) ? $meta[$field['id']] : null;
        }

      }

      $default = $this->get_default( $field );
      $value   = ( isset( $value ) ) ? $value : $default;

      return $value;

    }

    // add comment metabox content
    public function add_comment_meta_box_content( $comment, $callback ) {

      $has_nav  = ( count( $this->sections ) > 1 ) ? true : false;
      $show_all = ( ! $has_nav ) ? ' csf-show-all' : '';
      $errors   = ( is_object ( $comment ) ) ? get_comment_meta( $comment->comment_ID, '_csf_errors', true ) : array();
      $errors   = ( ! empty( $errors ) ) ? $errors : array();
      $theme    = ( $this->args['theme'] ) ? ' csf-theme-'. $this->args['theme'] : '';

      if( is_object ( $comment ) && ! empty( $errors ) ) {
        delete_comment_meta( $comment->comment_ID, '_csf_errors' );
      }

      wp_nonce_field( 'csf_comment_metabox_nonce', 'csf_comment_metabox_nonce'. $this->unique );

      echo '<div class="csf csf-comment-metabox'. $theme .'">';

        echo '<div class="csf-wrapper'. $show_all .'">';

          if( $has_nav ) {

            echo '<div class="csf-nav csf-nav-metabox" data-unique="'. $this->unique .'">';

              echo '<ul>';
              $tab_key = 1;
              foreach( $this->sections as $section ) {

                $tab_error = ( ! empty( $errors['sections'][$tab_key] ) ) ? '<i class="csf-label-error csf-error">!</i>' : '';
                $tab_icon = ( ! empty( $section['icon'] ) ) ? '<i class="csf-icon '. $section['icon'] .'"></i>' : '';

                echo '<li><a href="#" data-section="'. $this->unique .'_'. $tab_key .'">'. $tab_icon . $section['title'] . $tab_error .'</a></li>';

                $tab_key++;
              }
              echo '</ul>';

            echo '</div>';

          }

          echo '<div class="csf-content">';

            echo '<div class="csf-sections">';

            $section_key = 1;

            foreach( $this->sections as $section ) {

              $onload = ( ! $has_nav ) ? ' csf-onload' : '';

              echo '<div id="csf-section-'. $this->unique .'_'. $section_key .'" class="csf-section'. $onload .'">';

              $section_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="csf-icon '. $section['icon'] .'"></i>' : '';
              $section_title = ( ! empty( $section['title'] ) ) ? $section['title'] : '';

              echo ( $section_title || $section_icon ) ? '<div class="csf-section-title"><h3>'. $section_icon . $section_title .'</h3></div>' : '';

              if( ! empty( $section['fields'] ) ) {

                foreach ( $section['fields'] as $field ) {

                  if( ! empty( $field['id'] ) && ! empty( $errors['fields'][$field['id']] ) ) {
                    $field['_error'] = $errors['fields'][$field['id']];
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

            if( ! empty( $this->args['show_restore'] ) ) {

              echo '<div class="csf-restore-wrapper">';
              echo '<label>';
              echo '<input type="checkbox" name="'. $this->unique .'[_restore]" />';
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


      if( ! wp_verify_nonce( csf_get_var( 'csf_comment_metabox_nonce'. $this->unique ), 'csf_comment_metabox_nonce' ) ) {
        return $comment_id;
      }

      if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $comment_id;
      }

      $errors  = array();
      $request = csf_get_var( $this->unique );

      if( ! empty( $request ) ) {

        // ignore _nonce
        if( isset( $request['_nonce'] ) ) {
          unset( $request['_nonce'] );
        }

        // sanitize and validate
        $section_key = 1;
        foreach( $this->sections as $section ) {

          if( ! empty( $section['fields'] ) ) {

            foreach( $section['fields'] as $field ) {

              if( ! empty( $field['id'] ) ) {

                // sanitize
                if( ! empty( $field['sanitize'] ) ) {

                  $sanitize              = $field['sanitize'];
                  $value_sanitize        = isset( $request[$field['id']] ) ? $request[$field['id']] : '';
                  $request[$field['id']] = call_user_func( $sanitize, $value_sanitize );

                }

                // validate
                if( ! empty( $field['validate'] ) ) {

                  $validate       = $field['validate'];
                  $value_validate = isset( $request[$field['id']] ) ? $request[$field['id']] : '';
                  $has_validated  = call_user_func( $validate, $value_validate );

                  if( ! empty( $has_validated ) ) {

                    $errors['sections'][$section_key] = true;
                    $errors['fields'][$field['id']] = $has_validated;
                    $request[$field['id']] = $this->get_meta_value( $comment_id, $field );

                  }

                }

                // auto sanitize
                if( ! isset( $request[$field['id']] ) || is_null( $request[$field['id']] ) ) {
                  $request[$field['id']] = '';
                }

              }

            }

          }

          $section_key++;
        }

      }

      $request = apply_filters( "csf_{$this->unique}_save", $request, $comment_id, $this );

      do_action( "csf_{$this->unique}_save_before", $request, $comment_id, $this );

      if( empty( $request ) || ! empty( $request['_restore'] ) ) {

        if( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $request as $key => $value ) {
            delete_comment_meta( $comment_id, $key );
          }
        } else {
          delete_comment_meta( $comment_id, $this->unique );
        }

      } else {

        if( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $request as $key => $value ) {
            update_comment_meta( $comment_id, $key, $value );
          }
        } else {
          update_comment_meta( $comment_id, $this->unique, $request );
        }

        if( ! empty( $errors ) ) {
          update_comment_meta( $comment_id, '_csf_errors', $errors );
        }

      }

      do_action( "csf_{$this->unique}_saved", $request, $comment_id, $this );

      do_action( "csf_{$this->unique}_save_after", $request, $comment_id, $this );

    }
  }
}
