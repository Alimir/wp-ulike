<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Deprecated Class - Backward Compatibility Layer
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ULF' ) ) {
    class ULF {

        public function __construct() {}

        // Create options
        public static function createOptions( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create customize options
        public static function createCustomizeOptions( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create metabox options
        public static function createMetabox( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create menu options
        public static function createNavMenuOptions( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create shortcoder options
        public static function createShortcoder( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create taxonomy options
        public static function createTaxonomyOptions( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create profile options
        public static function createProfileOptions( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create widget
        public static function createWidget( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create comment metabox
        public static function createCommentMetabox( $id, $args = array() ) {
            // Deprecated - functionality removed
        }

        // Create section
        public static function createSection( $id, $sections ) {
            // Deprecated - functionality removed
        }
    }
}

if ( ! class_exists( 'CSF' ) && class_exists( 'ULF' ) ) {
    class CSF extends ULF {
        public function __construct() {}
    }
}