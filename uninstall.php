<?php
/**
 * Fired when the plugin is uninstalled.
 *
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit( 'No Naughty Business Please!' );
}
