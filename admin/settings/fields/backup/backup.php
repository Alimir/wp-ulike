<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: backup
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ULF_Field_backup' ) ) {
  class ULF_Field_backup extends ULF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $unique = $this->unique;
      $nonce  = wp_create_nonce( 'ulf_backup_nonce' );
      $export = add_query_arg( array( 'action' => 'ulf-export', 'unique' => $unique, 'nonce' => $nonce ), admin_url( 'admin-ajax.php' ) );

      echo $this->field_before();

      echo '<textarea name="ulf_import_data" class="ulf-import-data"></textarea>';
      echo '<button type="submit" class="button button-primary ulf-confirm ulf-import" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Import', 'ulf' ) .'</button>';
      echo '<hr />';
      echo '<textarea readonly="readonly" class="ulf-export-data">'. esc_attr( json_encode( get_option( $unique ) ) ) .'</textarea>';
      echo '<a href="'. esc_url( $export ) .'" class="button button-primary ulf-export" target="_blank">'. esc_html__( 'Export & Download', 'ulf' ) .'</a>';
      echo '<hr />';
      echo '<button type="submit" name="ulf_transient[reset]" value="reset" class="button ulf-warning-primary ulf-confirm ulf-reset" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Reset', 'ulf' ) .'</button>';

      echo $this->field_after();

    }

  }
}
