<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Setup Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF' ) && class_exists( 'ULF' ) ) {
    class CSF extends ULF{
        public function __construct() {}
    }
}