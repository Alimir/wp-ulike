<?php
/**
 * deprecated class for settings panel
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( !class_exists( 'wp_ulike_settings' ) ) {
    class wp_ulike_settings {
        public function __construct() {}
    }
}