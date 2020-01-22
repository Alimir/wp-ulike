<?php if ( ! defined( 'ABSPATH' )  ) { die; } // Cannot access directly.

//
// Create a widget 1
//
CSF::createWidget( 'csf_widget_example_1', array(
  'title'       => 'Codestar Widget Example 1',
  'classname'   => 'csf-widget-classname',
  'description' => 'A description for widget example 1',
  'fields'      => array(

    array(
      'id'      => 'title',
      'type'    => 'text',
      'title'   => 'Title',
    ),

    array(
      'id'      => 'opt-text',
      'type'    => 'text',
      'title'   => 'Text',
      'default' => 'Default text value'
    ),

    array(
      'id'      => 'opt-color',
      'type'    => 'color',
      'title'   => 'Color',
    ),

    array(
      'id'      => 'opt-upload',
      'type'    => 'upload',
      'title'   => 'Upload',
    ),

    array(
      'id'      => 'opt-textarea',
      'type'    => 'textarea',
      'title'   => 'Textarea',
      'help'    => 'The help text of the field.',
    ),

  )
) );

//
// Front-end display of widget example 1
// Attention: This function named considering above widget base id.
//
if( ! function_exists( 'csf_widget_example_1' ) ) {
  function csf_widget_example_1( $args, $instance ) {

    echo $args['before_widget'];

    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
    }

    // var_dump( $args ); // Widget arguments
    // var_dump( $instance ); // Saved values from database
    echo $instance['title'];
    echo $instance['opt-text'];
    echo $instance['opt-color'];
    echo $instance['opt-upload'];
    echo $instance['opt-textarea'];

    echo $args['after_widget'];

  }
}

//
// Create a widget 2
//
CSF::createWidget( 'csf_widget_example_2', array(
  'title'       => 'Codestar Widget Example 2',
  'classname'   => 'csf-widget-classname',
  'description' => 'A description for widget example 2',
  'fields'      => array(

    array(
      'id'      => 'title',
      'type'    => 'text',
      'title'   => 'Title',
    ),

    array(
      'id'      => 'opt-text',
      'type'    => 'text',
      'title'   => 'Text',
      'default' => 'Default text value'
    ),

    array(
      'id'      => 'opt-color',
      'type'    => 'color',
      'title'   => 'Color',
    ),

    array(
      'id'      => 'opt-switcher',
      'type'    => 'switcher',
      'title'   => 'Switcher',
      'label'   => 'The label text of the switcher.',
    ),

    array(
      'id'      => 'opt-checkbox',
      'type'    => 'checkbox',
      'title'   => 'Checkbox',
      'label'   => 'The label text of the checkbox.',
    ),

    array(
      'id'          => 'opt-select',
      'type'        => 'select',
      'title'       => 'Select',
      'placeholder' => 'Select an option',
      'options'     => array(
        'opt-1'     => 'Option 1',
        'opt-2'     => 'Option 2',
        'opt-3'     => 'Option 3',
      ),
    ),

    array(
      'id'      => 'opt-radio',
      'type'    => 'radio',
      'title'   => 'Radio',
      'options' => array(
        'yes'   => 'Yes, Please.',
        'no'    => 'No, Thank you.',
      ),
      'default' => 'yes',
    ),
    array(
      'type'    => 'notice',
      'style'   => 'success',
      'content' => 'A <strong>notice</strong> field with <strong>success</strong> style.',
    ),

    array(
      'id'      => 'opt-textarea',
      'type'    => 'textarea',
      'title'   => 'Textarea',
      'help'    => 'The help text of the field.',
    ),

  )
) );

//
// Front-end display of widget example 2
// Attention: This function named considering above widget base id.
//
if( ! function_exists( 'csf_widget_example_2' ) ) {
  function csf_widget_example_2( $args, $instance ) {

    echo $args['before_widget'];

    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
    }

    // var_dump( $args ); // Widget arguments
    // var_dump( $instance ); // Saved values from database
    echo $instance['title'];
    echo $instance['opt-text'];

    echo $args['after_widget'];

  }
}
