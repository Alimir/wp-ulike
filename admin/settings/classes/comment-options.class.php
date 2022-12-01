<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Comment Metabox Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ULF_Comment_Metabox' ) ) {
  class ULF_Comment_Metabox extends ULF_Abstract{

    // constans
    public $unique     = '';
    public $abstract   = 'comment_metabox';
    public $sections   = array();
    public $pre_fields = array();
    public $args       = array(
      'title'          => '',
      'data_type'      => 'serialize',
      'priority'       => 'default',
      'show_reset'     => false,
      'show_restore'   => false,
      'nav'            => 'normal',
      'theme'          => 'dark',
      'class'          => '',
      'defaults'       => array(),
    );

    // run comment metabox construct
    public function __construct( $key, $params = array() ) {

      $this->unique     = $key;
      $this->args       = apply_filters( "ulf_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
      $this->sections   = apply_filters( "ulf_{$this->unique}_sections", $params['sections'], $this );
      $this->pre_fields = $this->pre_fields( $this->sections );

      add_action( 'add_meta_boxes_comment', array( $this, 'add_comment_meta_box' ) );
      add_action( 'edit_comment', array( $this, 'save_comment_meta_box' ) );

      if ( ! empty( $this->args['class'] ) ) {
        add_filter( 'postbox_classes_comment_'. $this->unique, array( $this, 'add_comment_metabox_classes' ) );
      }

    }

    // instance
    public static function instance( $key, $params = array() ) {
      return new self( $key, $params );
    }

    public function add_comment_metabox_classes( $classes ) {

      if ( ! empty( $this->args['class'] ) ) {
        $classes[] = $this->args['class'];
      }

      return $classes;

    }

    // add comment metabox
    public function add_comment_meta_box( $post_type ) {

      add_meta_box( $this->unique, $this->args['title'], array( $this, 'add_comment_meta_box_content' ), 'comment', 'normal', $this->args['priority'], $this->args );

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
      $show_all = ( ! $has_nav ) ? ' ulf-show-all' : '';
      $errors   = ( is_object ( $comment ) ) ? get_comment_meta( $comment->comment_ID, '_ulf_errors_'. $this->unique, true ) : array();
      $errors   = ( ! empty( $errors ) ) ? $errors : array();
      $theme    = ( $this->args['theme'] ) ? ' ulf-theme-'. $this->args['theme'] : '';
      $nav_type = ( $this->args['nav'] === 'inline' ) ? 'inline' : 'normal';

      if ( is_object( $comment ) && ! empty( $errors ) ) {
        delete_comment_meta( $comment->comment_ID, '_ulf_errors_'. $this->unique );
      }

      wp_nonce_field( 'ulf_comment_metabox_nonce', 'ulf_comment_metabox_nonce'. $this->unique );

      echo '<div class="ulf ulf-comment-metabox'. esc_attr( $theme ) .'">';

        echo '<div class="ulf-wrapper'. esc_attr( $show_all ) .'">';

          if ( $has_nav ) {

            echo '<div class="ulf-nav ulf-nav-'. esc_attr( $nav_type ) .' ulf-nav-metabox">';

              echo '<ul>';

              $tab_key = 1;

              foreach ( $this->sections as $section ) {

                $tab_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="ulf-tab-icon '. esc_attr( $section['icon'] ) .'"></i>' : '';
                $tab_error = ( ! empty( $errors['sections'][$tab_key] ) ) ? '<i class="ulf-label-error ulf-error">!</i>' : '';

                echo '<li><a href="#">'. $tab_icon . $section['title'] . $tab_error .'</a></li>';

                $tab_key++;

              }

              echo '</ul>';

            echo '</div>';

          }

          echo '<div class="ulf-content">';

            echo '<div class="ulf-sections">';

            $section_key = 1;

            foreach ( $this->sections as $section ) {

              $section_onload = ( ! $has_nav ) ? ' ulf-onload' : '';
              $section_class  = ( ! empty( $section['class'] ) ) ? ' '. $section['class'] : '';
              $section_title  = ( ! empty( $section['title'] ) ) ? $section['title'] : '';
              $section_icon   = ( ! empty( $section['icon'] ) ) ? '<i class="ulf-section-icon '. esc_attr( $section['icon'] ) .'"></i>' : '';

              echo '<div class="ulf-section hidden'. esc_attr( $section_onload . $section_class ) .'">';

              echo ( $section_title || $section_icon ) ? '<div class="ulf-section-title"><h3>'. $section_icon . $section_title .'</h3></div>' : '';
              echo ( ! empty( $section['description'] ) ) ? '<div class="ulf-field ulf-section-description">'. $section['description'] .'</div>' : '';

              if ( ! empty( $section['fields'] ) ) {

                foreach ( $section['fields'] as $field ) {

                  if ( ! empty( $field['id'] ) && ! empty( $errors['fields'][$field['id']] ) ) {
                    $field['_error'] = $errors['fields'][$field['id']];
                  }

                  if ( ! empty( $field['id'] ) ) {
                    $field['default'] = $this->get_default( $field );
                  }

                  ULF::field( $field, $this->get_meta_value( $comment->comment_ID, $field ), $this->unique, 'comment_metabox' );

                }

              } else {

                echo '<div class="ulf-no-option">'. esc_html__( 'No data available.', 'ulf' ) .'</div>';

              }

              echo '</div>';

              $section_key++;

            }

            echo '</div>';

            if ( ! empty( $this->args['show_restore'] ) || ! empty( $this->args['show_reset'] ) ) {

              echo '<div class="ulf-sections-reset">';
              echo '<label>';
              echo '<input type="checkbox" name="'. esc_attr( $this->unique ) .'[_reset]" />';
              echo '<span class="button ulf-button-reset">'. esc_html__( 'Reset', 'ulf' ) .'</span>';
              echo '<span class="button ulf-button-cancel">'. sprintf( '<small>( %s )</small> %s', esc_html__( 'update post', 'ulf' ), esc_html__( 'Cancel', 'ulf' ) ) .'</span>';
              echo '</label>';
              echo '</div>';

            }

          echo '</div>';

          echo ( $has_nav && $nav_type === 'normal' ) ? '<div class="ulf-nav-background"></div>' : '';

          echo '<div class="clear"></div>';

        echo '</div>';

      echo '</div>';

    }

    // save comment metabox
    public function save_comment_meta_box( $comment_id ) {

      $count    = 1;
      $data     = array();
      $errors   = array();
      $noncekey = 'ulf_comment_metabox_nonce'. $this->unique;
      $nonce    = ( ! empty( $_POST[ $noncekey ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $noncekey ] ) ) : '';

      if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $nonce, 'ulf_comment_metabox_nonce' ) ) {
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

                } else if( isset( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {

                  $data[$field_id] = call_user_func( $field['sanitize'], $field_value );

                } else {

                  $data[$field_id] = $field_value;

                }

                // Validate "post" request of field.
                if ( isset( $field['validate'] ) && is_callable( $field['validate'] ) ) {

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

      $data = apply_filters( "ulf_{$this->unique}_save", $data, $comment_id, $this );

      do_action( "ulf_{$this->unique}_save_before", $data, $comment_id, $this );

      if ( empty( $data ) || ! empty( $request['_reset'] ) ) {

        if ( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $this->pre_fields as $field ) {
            if ( ! empty( $field['id'] ) ) {
              delete_comment_meta( $comment_id, $field['id'] );
            }
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
          update_comment_meta( $comment_id, '_ulf_errors_'. $this->unique, $errors );
        }

      }

      do_action( "ulf_{$this->unique}_saved", $data, $comment_id, $this );

      do_action( "ulf_{$this->unique}_save_after", $data, $comment_id, $this );

    }
  }
}
