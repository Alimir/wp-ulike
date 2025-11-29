<?php
/**
 * Plugin Name:       WP ULike
 * Plugin URI:        https://wpulike.com/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description:       Looking to increase user engagement on your WordPress site? WP ULike plugin lets you easily add voting buttons to your content. With customizable settings and detailed analytics, you can track user engagement, optimize your content, and build a loyal following.
 * Version:           4.8.0
 * Author:            TechnoWich
 * Author URI:        https://technowich.com/?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * Text Domain:       wp-ulike
 * Domain Path:       /languages
 * Tested up to: 	  6.8
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * WP ULike is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * WP ULike is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Do not change these values
define( 'WP_ULIKE_PLUGIN_URI'   , 'https://wpulike.com/' 		 			);
define( 'WP_ULIKE_VERSION'      , '4.8.0' 					 		    	);
define( 'WP_ULIKE_DB_VERSION'   , '2.3' 					 	 			);
define( 'WP_ULIKE_SLUG'         , 'wp-ulike' 					 			);
define( 'WP_ULIKE_NAME'         , 'WP ULike'	    						);

define( 'WP_ULIKE_DIR'          , plugin_dir_path( __FILE__ ) 	 			);
define( 'WP_ULIKE_URL'          , plugins_url( '', __FILE__ ) 	 			);
define( 'WP_ULIKE_BASENAME'     , plugin_basename( __FILE__ ) 	 			);

define( 'WP_ULIKE_ADMIN_DIR'    , WP_ULIKE_DIR . 'admin' 		 			);
define( 'WP_ULIKE_ADMIN_URL'    , WP_ULIKE_URL . '/admin' 		 			);

define( 'WP_ULIKE_INC_DIR'      , WP_ULIKE_DIR . 'includes' 	 			);
define( 'WP_ULIKE_INC_URL'      , WP_ULIKE_URL . '/includes'     			);

define( 'WP_ULIKE_ASSETS_DIR'   , WP_ULIKE_DIR . 'assets' 					);
define( 'WP_ULIKE_ASSETS_URL'   , WP_ULIKE_URL . '/assets' 		 			);

/**
 * Initialize the plugin
 * ===========================================================================*/

require WP_ULIKE_INC_DIR . '/action.php';
// Register hooks that are fired when the plugin is activated or deactivated.
register_activation_hook  ( __FILE__, array( 'wp_ulike_register_action_hook', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'wp_ulike_register_action_hook', 'deactivate' ) );

// Load Pro license validator
require_once WP_ULIKE_INC_DIR . '/pro.php';

if ( ! version_compare( PHP_VERSION, '7.2.5', '>=' ) ) {
	add_action( 'admin_notices', 'wp_ulike_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '6.0', '>=' ) ) {
	add_action( 'admin_notices', 'wp_ulike_fail_wp_version' );
} elseif ( WP_Ulike_Pro_Validator::check_license_validity() === false ) {
	// Stop plugin initialization if Pro version is nulled/invalid
	add_action( 'admin_notices', 'wp_ulike_fail_pro_license' );
} elseif( ! class_exists( 'WpUlikeInit' ) ) {
	require WP_ULIKE_INC_DIR . '/plugin.php';
}

/**
 * WP ULike admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @return void
 */
function wp_ulike_fail_php_version() {
	/* translators: %s: PHP version */
	$message = sprintf( esc_html__( 'WP ULike requires PHP version %s+, plugin is currently NOT RUNNING.', 'wp-ulike' ), '7.2.5' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * WP ULike admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @return void
 */
function wp_ulike_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message = sprintf( esc_html__( 'WP ULike requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'wp-ulike' ), '6.0' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}


/**
 * WP ULike admin notice for invalid/nulled Pro license.
 *
 * Warning when Pro version is detected with nulled/invalid license.
 *
 * @return void
 */
function wp_ulike_fail_pro_license() {
	$message = '<h3>' . esc_html__( '🛡️ Security Protection Active', 'wp-ulike' ) . '</h3>';
	$message .= '<p><strong>' . esc_html__( 'Hello! We\'ve temporarily paused WP ULike to keep your site safe.', 'wp-ulike' ) . '</strong></p>';
	$message .= '<p>' . esc_html__( 'We noticed an issue with your WP ULike Pro license. This automatic protection helps safeguard your website from potential security risks that can come with unauthorized software versions.', 'wp-ulike' ) . '</p>';
	$message .= '<p>' . esc_html__( 'Here\'s why this matters for your site\'s safety:', 'wp-ulike' ) . '</p>';
	$message .= '<ul style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">';
	$message .= '<li>' . esc_html__( '🔒 Protects your site from security vulnerabilities', 'wp-ulike' ) . '</li>';
	$message .= '<li>' . esc_html__( '✅ Ensures stable functionality and compatibility', 'wp-ulike' ) . '</li>';
	$message .= '<li>' . esc_html__( '🛡️ Keeps your data safe and secure', 'wp-ulike' ) . '</li>';
	$message .= '<li>' . esc_html__( '⚡ Prevents conflicts with other plugins', 'wp-ulike' ) . '</li>';
	$message .= '</ul>';
	$message .= '<p style="margin-top: 15px;"><strong>' . esc_html__( 'The good news?', 'wp-ulike' ) . '</strong> ' . esc_html__( 'Getting a valid license is quick and easy! You\'ll unlock all premium features, receive regular updates, security patches, and get priority support from our team.', 'wp-ulike' ) . '</p>';
	$message .= '<p style="margin-top: 10px;">' . sprintf(
		/* translators: 1: Link opening tag, 2: Link closing tag */
		esc_html__( '%1$sGet Your Valid License Here%2$s — We\'re here to help you get back up and running! 🚀', 'wp-ulike' ),
		'<a href="https://wpulike.com/pricing/?utm_source=license-check&utm_campaign=invalid-license&utm_medium=wp-dash" target="_blank" style="font-weight: 600; color: #2271b1;">',
		'</a>'
	) . '</p>';
	$message .= '<p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">' . sprintf(
		/* translators: 1: Link opening tag, 2: Email address (not translatable), 3: Link closing tag */
		esc_html__( '💬 Have questions or think this might be a mistake? Our friendly support team is ready to help! Reach out to us at %1$s%2$s%3$s — we\'re just an email away!', 'wp-ulike' ),
		'<a href="mailto:info@wpulike.com" style="color: #2271b1;">',
		'info@wpulike.com',
		'</a>'
	) . '</p>';

	$html_message = sprintf( '<div class="error" style="padding: 20px; margin: 15px 0; border-left: 4px solid #dc3232; background: #fff;">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/*============================================================================*/