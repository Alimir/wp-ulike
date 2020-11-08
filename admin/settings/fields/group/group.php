<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: group
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ULF_Field_group' ) ) {
  class ULF_Field_group extends ULF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'max'                    => 0,
        'min'                    => 0,
        'fields'                 => array(),
        'button_title'           => esc_html__( 'Add New', 'ulf' ),
        'accordion_title_prefix' => '',
        'accordion_title_number' => false,
        'accordion_title_auto'   => true,
      ) );

      $title_prefix = ( ! empty( $args['accordion_title_prefix'] ) ) ? $args['accordion_title_prefix'] : '';
      $title_number = ( ! empty( $args['accordion_title_number'] ) ) ? true : false;
      $title_auto   = ( ! empty( $args['accordion_title_auto'] ) ) ? true : false;

      if ( preg_match( '/'. preg_quote( '['. $this->field['id'] .']' ) .'/', $this->unique ) ) {

        echo '<div class="ulf-notice ulf-notice-danger">'. esc_html__( 'Error: Field ID conflict.', 'ulf' ) .'</div>';

      } else {

        echo $this->field_before();

        echo '<div class="ulf-cloneable-item ulf-cloneable-hidden">';

          echo '<div class="ulf-cloneable-helper">';
          echo '<i class="ulf-cloneable-sort fas fa-arrows-alt"></i>';
          echo '<i class="ulf-cloneable-clone far fa-clone"></i>';
          echo '<i class="ulf-cloneable-remove ulf-confirm fas fa-times" data-confirm="'. esc_html__( 'Are you sure to delete this item?', 'ulf' ) .'"></i>';
          echo '</div>';

          echo '<h4 class="ulf-cloneable-title">';
          echo '<span class="ulf-cloneable-text">';
          echo ( $title_number ) ? '<span class="ulf-cloneable-title-number"></span>' : '';
          echo ( $title_prefix ) ? '<span class="ulf-cloneable-title-prefix">'. esc_attr( $title_prefix ) .'</span>' : '';
          echo ( $title_auto ) ? '<span class="ulf-cloneable-value"><span class="ulf-cloneable-placeholder"></span></span>' : '';
          echo '</span>';
          echo '</h4>';

          echo '<div class="ulf-cloneable-content">';
          foreach ( $this->field['fields'] as $field ) {

            $field_default = ( isset( $field['default'] ) ) ? $field['default'] : '';
            $field_unique  = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .'][0]' : $this->field['id'] .'[0]';

            ULF::field( $field, $field_default, '___'. $field_unique, 'field/group' );

          }
          echo '</div>';

        echo '</div>';

        echo '<div class="ulf-cloneable-wrapper ulf-data-wrapper" data-title-number="'. esc_attr( $title_number ) .'" data-field-id="['. esc_attr( $this->field['id'] ) .']" data-max="'. esc_attr( $args['max'] ) .'" data-min="'. esc_attr( $args['min'] ) .'">';

        if ( ! empty( $this->value ) ) {

          $num = 0;

          foreach ( $this->value as $value ) {

            $first_id    = ( isset( $this->field['fields'][0]['id'] ) ) ? $this->field['fields'][0]['id'] : '';
            $first_value = ( isset( $value[$first_id] ) ) ? $value[$first_id] : '';
            $first_value = ( is_array( $first_value ) ) ? reset( $first_value ) : $first_value;

            echo '<div class="ulf-cloneable-item">';

              echo '<div class="ulf-cloneable-helper">';
              echo '<i class="ulf-cloneable-sort fas fa-arrows-alt"></i>';
              echo '<i class="ulf-cloneable-clone far fa-clone"></i>';
              echo '<i class="ulf-cloneable-remove ulf-confirm fas fa-times" data-confirm="'. esc_html__( 'Are you sure to delete this item?', 'ulf' ) .'"></i>';
              echo '</div>';

              echo '<h4 class="ulf-cloneable-title">';
              echo '<span class="ulf-cloneable-text">';
              echo ( $title_number ) ? '<span class="ulf-cloneable-title-number">'. esc_attr( $num+1 ) .'.</span>' : '';
              echo ( $title_prefix ) ? '<span class="ulf-cloneable-title-prefix">'. esc_attr( $title_prefix ) .'</span>' : '';
              echo ( $title_auto ) ? '<span class="ulf-cloneable-value">' . esc_attr( $first_value ) .'</span>' : '';
              echo '</span>';
              echo '</h4>';

              echo '<div class="ulf-cloneable-content">';

              foreach ( $this->field['fields'] as $field ) {

                $field_unique = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .']['. $num .']' : $this->field['id'] .'['. $num .']';
                $field_value  = ( isset( $field['id'] ) && isset( $value[$field['id']] ) ) ? $value[$field['id']] : '';

                ULF::field( $field, $field_value, $field_unique, 'field/group' );

              }

              echo '</div>';

            echo '</div>';

            $num++;

          }

        }

        echo '</div>';

        echo '<div class="ulf-cloneable-alert ulf-cloneable-max">'. esc_html__( 'You cannot add more.', 'ulf' ) .'</div>';
        echo '<div class="ulf-cloneable-alert ulf-cloneable-min">'. esc_html__( 'You cannot remove more.', 'ulf' ) .'</div>';
        echo '<a href="#" class="button button-primary ulf-cloneable-add">'. $args['button_title'] .'</a>';

        echo $this->field_after();

      }

    }

    public function enqueue() {

      if ( ! wp_script_is( 'jquery-ui-accordion' ) ) {
        wp_enqueue_script( 'jquery-ui-accordion' );
      }

      if ( ! wp_script_is( 'jquery-ui-sortable' ) ) {
        wp_enqueue_script( 'jquery-ui-sortable' );
      }

    }

  }
}
