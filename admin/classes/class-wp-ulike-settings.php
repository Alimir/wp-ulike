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

        public static function parse_multi( $result ) {
            // Check if the result was recorded as JSON, and if so, returns an array instead
            return ( is_string( $result ) && $array = json_decode( $result, true ) ) ? $array : $result;
        }

    }
}