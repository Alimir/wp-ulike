<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Metabox Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'CSF_Metabox' ) ) {
  class CSF_Metabox extends CSF_Abstract{

    // constans
    public $unique         = '';
    public $abstract       = 'metabox';
    public $pre_fields     = array();
    public $sections       = array();
    public $post_type      = array();
    public $args           = array(
      'title'              => '',
      'post_type'          => 'post',
      'data_type'          => 'serialize',
      'context'            => 'advanced',
      'priority'           => 'default',
      'exclude_post_types' => array(),
      'page_templates'     => '',
      'post_formats'       => '',
      'show_restore'       => false,
      'enqueue_webfont'    => true,
      'async_webfont'      => false,
      'output_css'         => true,
      'theme'              => 'dark',
      'class'              => '',
      'defaults'           => array(),
    );

    // run metabox construct
    public function __construct( $key, $params = array() ) {

      $this->unique         = $key;
      $this->args           = apply_filters( "csf_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
      $this->sections       = apply_filters( "csf_{$this->unique}_sections", $params['sections'], $this );
      $this->post_type      = ( is_array( $this->args['post_type'] ) ) ? $this->args['post_type'] : array_filter( (array) $this->args['post_type'] );
      $this->post_formats   = ( is_array( $this->args['post_formats'] ) ) ? $this->args['post_formats'] : array_filter( (array) $this->args['post_formats'] );
      $this->page_templates = ( is_array( $this->args['page_templates'] ) ) ? $this->args['page_templates'] : array_filter( (array) $this->args['page_templates'] );
      $this->pre_fields     = $this->pre_fields( $this->sections );

      add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
      add_action( 'save_post', array( &$this, 'save_meta_box' ) );
      add_action( 'edit_attachment', array( &$this, 'save_meta_box' ) );

      if( ! empty( $this->page_templates ) || ! empty( $this->post_formats ) || ! empty( $this->args['class'] ) ) {
        foreach( $this->post_type as $post_type ) {
          add_filter( 'postbox_classes_'. $post_type .'_'. $this->unique, array( &$this, 'add_metabox_classes' ) );
        }
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

    public function add_metabox_classes( $classes ) {

      global $post;

      if( ! empty( $this->post_formats ) ) {

        $saved_post_format = ( is_object( $post ) ) ? get_post_format( $post ) : false;
        $saved_post_format = ( ! empty( $saved_post_format ) ) ? $saved_post_format : 'default';

        $classes[] = 'csf-post-formats';

        // Sanitize post format for standard to default
        if( ( $key = array_search( 'standard', $this->post_formats ) ) !== false ) {
          $this->post_formats[$key] = 'default';
        }

        foreach( $this->post_formats as $format ) {
          $classes[] = 'csf-post-format-'. $format;
        }

        if( ! in_array( $saved_post_format, $this->post_formats ) ) {
          $classes[] = 'csf-hide';
        } else {
          $classes[] = 'csf-show';
        }

      }

      if( ! empty( $this->page_templates ) ) {

        $saved_template = ( is_object( $post ) && ! empty( $post->page_template ) ) ? $post->page_template : 'default';

        $classes[] = 'csf-page-templates';

        foreach( $this->page_templates as $template ) {
          $classes[] = 'csf-page-'. preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $template ) );
        }

        if( ! in_array( $saved_template, $this->page_templates ) ) {
          $classes[] = 'csf-hide';
        } else {
          $classes[] = 'csf-show';
        }

      }

      if( ! empty( $this->args['class'] ) ) {
        $classes[] = $this->args['class'];
      }

      return $classes;

    }

    // add metabox
    public function add_meta_box( $post_type ) {

      if( ! in_array( $post_type, $this->args['exclude_post_types'] ) ) {
        add_meta_box( $this->unique, $this->args['title'], array( &$this, 'add_meta_box_content' ), $this->post_type, $this->args['context'], $this->args['priority'], $this->args );
      }

    }

    // get default value
    public function get_default( $field ) {

      $default = ( isset( $field['id'] ) && isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : null;
      $default = ( isset( $field['default'] ) ) ? $field['default'] : $default;

      return $default;

    }

    // get meta value
    public function get_meta_value( $field ) {

      global $post;

      $value = null;

      if( is_object( $post ) && ! empty( $field['id'] ) ) {

        if( $this->args['data_type'] !== 'serialize' ) {
          $meta  = get_post_meta( $post->ID, $field['id'] );
          $value = ( isset( $meta[0] ) ) ? $meta[0] : null;
        } else {
          $meta  = get_post_meta( $post->ID, $this->unique, true );
          $value = ( isset( $meta[$field['id']] ) ) ? $meta[$field['id']] : null;
        }

      }

      $default = $this->get_default( $field );
      $value   = ( isset( $value ) ) ? $value : $default;

      return $value;

    }

    // add metabox content
    public function add_meta_box_content( $post, $callback ) {

      global $post;

      $has_nav  = ( count( $this->sections ) > 1 && $this->args['context'] !== 'side' ) ? true : false;
      $show_all = ( ! $has_nav ) ? ' csf-show-all' : '';
      $errors   = ( is_object ( $post ) ) ? get_post_meta( $post->ID, '_csf_errors', true ) : array();
      $errors   = ( ! empty( $errors ) ) ? $errors : array();
      $theme    = ( $this->args['theme'] ) ? ' csf-theme-'. $this->args['theme'] : '';

      if( is_object ( $post ) && ! empty( $errors ) ) {
        delete_post_meta( $post->ID, '_csf_errors' );
      }

      wp_nonce_field( 'csf_metabox_nonce', 'csf_metabox_nonce'. $this->unique );

      echo '<div class="csf csf-metabox'. $theme .'">';

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

                  CSF::field( $field, $this->get_meta_value( $field ), $this->unique, 'metabox' );

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

    // save metabox
    public function save_meta_box( $post_id ) {

      if( ! wp_verify_nonce( csf_get_var( 'csf_metabox_nonce'. $this->unique ), 'csf_metabox_nonce' ) ) {
        return $post_id;
      }

      if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
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
                    $request[$field['id']] = $this->get_meta_value( $field );

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

      $request = apply_filters( "csf_{$this->unique}_save", $request, $post_id, $this );

      do_action( "csf_{$this->unique}_save_before", $request, $post_id, $this );

      if( empty( $request ) || ! empty( $request['_restore'] ) ) {

        if( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $request as $key => $value ) {
            delete_post_meta( $post_id, $key );
          }
        } else {
          delete_post_meta( $post_id, $this->unique );
        }

      } else {

        if( $this->args['data_type'] !== 'serialize' ) {
          foreach ( $request as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
          }
        } else {
          update_post_meta( $post_id, $this->unique, $request );
        }

        if( ! empty( $errors ) ) {
          update_post_meta( $post_id, '_csf_errors', $errors );
        }

      }

      do_action( "csf_{$this->unique}_saved", $request, $post_id, $this );

      do_action( "csf_{$this->unique}_save_after", $request, $post_id, $this );

    }
  }
}
