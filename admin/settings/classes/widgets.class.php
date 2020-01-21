<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Widgets Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'CSF_Widget' ) ) {
  class CSF_Widget extends WP_Widget {

    // constans
    public $unique  = '';
    public $args    = array(
      'title'       => '',
      'classname'   => '',
      'description' => '',
      'width'       => '',
      'defaults'    => array(),
      'fields'      => array(),
      'class'       => '',
    );

    public function __construct( $key, $params ) {

      $widget_ops  = array();
      $control_ops = array();

      $this->unique = $key;
      $this->args   = apply_filters( "csf_{$this->unique}_args", wp_parse_args( $params, $this->args ), $this );

      // Set control options
      if( ! empty( $this->args['width'] ) ) {
        $control_ops['width'] = $this->args['width'];
      }

      // Set widget options
      if( ! empty( $this->args['description'] ) ) {
        $widget_ops['description'] = $this->args['description'];
      }

      if( ! empty( $this->args['classname'] ) ) {
        $widget_ops['classname'] = $this->args['classname'];
      }

      // Set filters
      $widget_ops  = apply_filters( "csf_{$this->unique}_widget_ops", $widget_ops, $this );
      $control_ops = apply_filters( "csf_{$this->unique}_control_ops", $control_ops, $this );

      parent::__construct( $this->unique, $this->args['title'], $widget_ops, $control_ops );

    }

    // Register widget with WordPress
    public static function instance( $key, $params = array() ) {
      return new self( $key, $params );
    }

    // Front-end display of widget.
    public function widget( $args, $instance ) {
      call_user_func( $this->unique, $args, $instance );
    }

    // get default value
    public function get_default( $field, $options = array() ) {

      $default = ( isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : null;
      $default = ( isset( $field['default'] ) ) ? $field['default'] : $default;
      $default = ( isset( $options[$field['id']] ) ) ? $options[$field['id']] : $default;

      return $default;

    }

    // Back-end widget form.
    public function form( $instance ) {

      if( ! empty( $this->args['fields'] ) ) {

        $class = ( $this->args['class'] ) ? ' '. $this->args['class'] : '';

        echo '<div class="csf csf-widgets csf-fields'. $class .'">';

        foreach( $this->args['fields'] as $field ) {

          $field_value  = '';
          $field_unique = '';

          if( ! empty( $field['id'] ) ) {

            $field_value  = $this->get_default( $field, $instance );
            $field_unique = 'widget-' . $this->unique . '[' . $this->number . ']';

            if( $field['id'] === 'title' ) {
              $field['attributes']['id'] = 'widget-'. $this->unique .'-'. $this->number .'-title';
            }

          }

          CSF::field( $field, $field_value, $field_unique );

        }

        echo '</div>';

      }

    }

    // Sanitize widget form values as they are saved.
    public function update( $new_instance, $old_instance ) {

      // auto sanitize
      foreach( $this->args['fields'] as $field ) {
        if( ! empty( $field['id'] ) && ( ! isset( $new_instance[$field['id']] ) || is_null( $new_instance[$field['id']] ) ) ) {
          $new_instance[$field['id']] = '';
        }
      }

      $new_instance = apply_filters( "csf_{$this->unique}_save", $new_instance, $this->args, $this );

      do_action( "csf_{$this->unique}_save_before", $new_instance, $this->args, $this );

      return $new_instance;

    }
  }
}
