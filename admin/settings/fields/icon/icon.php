<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: icon
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ULF_Field_icon' ) ) {
  class ULF_Field_icon extends ULF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'button_title' => esc_html__( 'Add Icon', 'ulf' ),
        'remove_title' => esc_html__( 'Remove Icon', 'ulf' ),
      ) );

      echo $this->field_before();

      $nonce  = wp_create_nonce( 'ulf_icon_nonce' );
      $hidden = ( empty( $this->value ) ) ? ' hidden' : '';

      echo '<div class="ulf-icon-select">';
      echo '<span class="ulf-icon-preview'. esc_attr( $hidden ) .'"><i class="'. esc_attr( $this->value ) .'"></i></span>';
      echo '<a href="#" class="button button-primary ulf-icon-add" data-nonce="'. esc_attr( $nonce ) .'">'. $args['button_title'] .'</a>';
      echo '<a href="#" class="button ulf-warning-primary ulf-icon-remove'. esc_attr( $hidden ) .'">'. $args['remove_title'] .'</a>';
      echo '<input type="text" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'" class="ulf-icon-value"'. $this->field_attributes() .' />';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {
      add_action( 'admin_footer', array( &$this, 'add_footer_modal_icon' ) );
      add_action( 'customize_controls_print_footer_scripts', array( &$this, 'add_footer_modal_icon' ) );
    }

    public function add_footer_modal_icon() {
    ?>
      <div id="ulf-modal-icon" class="ulf-modal ulf-modal-icon hidden">
        <div class="ulf-modal-table">
          <div class="ulf-modal-table-cell">
            <div class="ulf-modal-overlay"></div>
            <div class="ulf-modal-inner">
              <div class="ulf-modal-title">
                <?php esc_html_e( 'Add Icon', 'ulf' ); ?>
                <div class="ulf-modal-close ulf-icon-close"></div>
              </div>
              <div class="ulf-modal-header">
                <input type="text" placeholder="<?php esc_html_e( 'Search...', 'ulf' ); ?>" class="ulf-icon-search" />
              </div>
              <div class="ulf-modal-content">
                <div class="ulf-modal-loading"><div class="ulf-loading"></div></div>
                <div class="ulf-modal-load"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
    }

  }
}
