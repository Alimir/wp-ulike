<?php
/**
 * WP ULike Pro License Validation
 *
 * Handles validation of WP ULike Pro licenses and prevents nulled/cracked versions
 * from running the free version.
 *
 * @package    wp-ulike
 * @author     TechnoWich
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_Ulike_Pro_Validator' ) ) {

	class WP_Ulike_Pro_Validator {

		/**
		 * Initialize the validator class.
		 * Registers WordPress hooks for license clearing functionality.
		 *
		 * @return void
		 */
		public static function init() {
			// Handle license clearing action (must be before license check)
			add_action( 'admin_init', array( __CLASS__, 'handle_clear_license' ) );
			// Show success notice after clearing license (must be before license check)
			add_action( 'admin_notices', array( __CLASS__, 'show_license_cleared_notice' ) );
		}

		/**
		 * Check if WP ULike Pro license is valid.
		 * Stops free version if Pro is installed with nulled/invalid license.
		 *
		 * @return bool|null Returns false if Pro is nulled/invalid, true if valid, null if Pro not installed
		 */
		public static function check_license_validity() {
			// Only check if Pro version is installed
			if ( ! defined( 'WP_ULIKE_PRO_VERSION' ) || ! class_exists( 'WP_Ulike_Pro_API' ) ) {
				return null; // Pro not installed, allow free version to run
			}

			// Get and clean license key
			$license_key = get_option( 'wp_ulike_pro_license_key', '' );
			if ( empty( $license_key ) ) {
				// No license key entered - allow it (user hasn't activated yet)
				return true;
			}

			// Check license status using cached API data (no force request for performance)
			$license_data = WP_Ulike_Pro_API::get_license_data( false );

			// Invalid license statuses that should stop the free version
			$invalid_statuses = [
				WP_Ulike_Pro_API::STATUS_INVALID,
				WP_Ulike_Pro_API::STATUS_DISABLED,
				WP_Ulike_Pro_API::STATUS_MISSING,
			];

			$license_key_clean = trim( $license_key );

			// Check for nulled/cracked patterns first
			if ( self::is_nulled_license( $license_key_clean ) ) {
				return false; // Nulled license detected - stop immediately
			}

			// Not nulled - check API status (API is the final arbiter)
			return self::check_license_status( $license_data, $invalid_statuses );
		}

		/**
		 * Check if license key matches nulled/cracked patterns.
		 *
		 * @param string $license_key Cleaned license key
		 * @return bool True if nulled pattern detected, false otherwise
		 */
		private static function is_nulled_license( $license_key ) {
			if ( empty( $license_key ) ) {
				return false;
			}

			$license_key_lower = strtolower( $license_key );
			$key_length = strlen( $license_key );

			// Check for invalid format/length first
			// Valid licenses are typically 25-40 hex characters (with or without dashes)
			// Be lenient with total length (15-60 chars) to account for dashes and variations
			if ( $key_length < 15 || $key_length > 60 ) {
				// Too short or too long - likely invalid/nulled
				return true;
			}

			// Remove dashes to count actual hex characters
			$hex_only = str_replace( '-', '', $license_key );
			$hex_length = strlen( $hex_only );

			// Valid hex length should be 25-40 characters (supports standard and shorter variants)
			// Standard UUID: 32 chars, Short variants: 25-31 chars, Long: 33-40 chars
			if ( $hex_length < 25 || $hex_length > 40 ) {
				// Invalid hex length - likely nulled
				return true;
			}

			// Check for non-hex characters (valid licenses should only contain 0-9, a-f, and dashes)
			// This catches obvious invalid formats like "ABC123XYZ" or "license-key-123"
			if ( ! preg_match( '/^[0-9a-f-]+$/i', $license_key ) ) {
				// Contains invalid characters (non-hex) - likely nulled
				return true;
			}

			// Check for nulled keywords (case-insensitive via lowercase conversion)
			$nulled_keywords = [
				'nulled',
				'cracked',
				'fake',
				'weadown',
				'test',
				'gpl',
				'demo',
				'trial',
				'placeholder',
				'example',
				'sample',
				'invalid',
				'disabled',
				'default',
				'temp',
				'temporary',
				'xxx',
				'12345',
				'password',
				'secret',
				'admin',
				'root',
				'license',
				'activation',
				'serial',
				'code',
			];

			foreach ( $nulled_keywords as $keyword ) {
				if ( strpos( $license_key_lower, $keyword ) !== false ) {
					return true;
				}
			}

			// Check for placeholder patterns (asterisks, x's, dashes)
			if ( preg_match( '/^\*+$|^x+$|^-+$|\*{3,}|x{5,}/i', $license_key ) ) {
				return true;
			}

			// Check for asterisks with suspicious affixes at end (com/net/org)
			if ( preg_match( '/\*/', $license_key ) &&
				 preg_match( '/\*.*(com|net|org)$/i', $license_key ) &&
				 strlen( $license_key ) > 20 ) {
				return true;
			}

			// Check for suspicious affixes at the end (com, net, org) - but not valid hex
			$suspicious_affixes = [ 'com', 'net', 'org' ];
			foreach ( $suspicious_affixes as $affix ) {
				if ( substr( $license_key_lower, -strlen( $affix ) ) === $affix &&
					 ! preg_match( '/^[0-9a-f]{32,40}$/i', $license_key ) ) {
					return true;
				}
			}

			// Check for all uppercase with non-hex characters (G-Z)
			if ( preg_match( '/^[A-Z0-9]{20,}$/', $license_key ) &&
				 $license_key === strtoupper( $license_key ) &&
				 preg_match( '/[G-Z]/', $license_key ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check license status from API data.
		 *
		 * @param array|\WP_Error $license_data License data from API
		 * @param array $invalid_statuses Array of invalid status constants
		 * @return bool True if valid, false if invalid
		 */
		private static function check_license_status( $license_data, $invalid_statuses ) {
			// Handle API errors
			if ( is_wp_error( $license_data ) || empty( $license_data ) ) {
				// Network issue - allow it (don't block on connection problems)
				return true;
			}

			// Check license status
			if ( ! is_array( $license_data ) || ! isset( $license_data['license'] ) ) {
				return true; // Unknown status - allow it
			}

			$license_status = $license_data['license'];

			// Stop if license is invalid, disabled, or missing
			if ( in_array( $license_status, $invalid_statuses, true ) ) {
				return false;
			}

			// License is valid or expired (expired gets grace period)
			return true;
		}

		/**
		 * Get license status for display purposes (notices, etc.)
		 * Returns the actual license status from API, including expired status.
		 * Only returns status if license key exists (don't show notices for users who haven't entered license yet).
		 *
		 * @return string|false|null License status constant, false if no license key entered, null if Pro not installed
		 */
		public static function get_license_status() {
			// Only check if Pro version is installed
			if ( ! defined( 'WP_ULIKE_PRO_VERSION' ) || ! class_exists( 'WP_Ulike_Pro_API' ) ) {
				return null; // Pro not installed
			}

			// Check if license key exists - don't show notices if user hasn't entered license yet
			$license_key = get_option( 'wp_ulike_pro_license_key', '' );
			if ( empty( $license_key ) ) {
				return false; // No license key entered - don't show notice
			}

			// Check if it's nulled first (before API call)
			$license_key_clean = trim( $license_key );
			if ( self::is_nulled_license( $license_key_clean ) ) {
				return WP_Ulike_Pro_API::STATUS_INVALID;
			}

			// Get license data using cached API data
			$license_data = WP_Ulike_Pro_API::get_license_data( false );

			// Return status if available
			if ( is_array( $license_data ) && isset( $license_data['license'] ) ) {
				return $license_data['license'];
			}

			// If API error or empty, but we have a license key, return invalid
			// (user entered a key but API can't verify it - likely invalid/nulled)
			return WP_Ulike_Pro_API::STATUS_INVALID;
		}

		/**
		 * Clear all license-related data.
		 * Removes license key, checksums, signatures, cached data, and request locks.
		 * Allows users to remove old/invalid licenses to enter a new valid one.
		 *
		 * @return bool True on success, false on failure
		 */
		public static function clear_license() {
			// Clear the main license key
			delete_option( 'wp_ulike_pro_license_key' );

			// Clear license checksum and signature
			delete_option( 'wp_ulike_pro_license_checksum' );
			delete_option( 'wp_ulike_pro_license_signature' );

			// Clear license data (stored as options via set_transient/update_option)
			delete_option( 'wp_ulike_pro_license_data' );
			delete_option( 'wp_ulike_pro_license_data_fallback' );

			// Clear transients (in case they're used)
			delete_transient( 'wp_ulike_pro_license_data' );
			delete_transient( 'wp_ulike_pro_license_status' );

			// Clear request lock if API class exists
			if ( class_exists( 'WP_Ulike_Pro_API' ) && method_exists( 'WP_Ulike_Pro_API', 'clear_request_lock' ) ) {
				WP_Ulike_Pro_API::clear_request_lock( 'get_license_data' );
			}

			return true;
		}

		/**
		 * Handle clearing invalid license key.
		 * Allows users to remove old/nulled license to enter a new valid one.
		 * Hooked to admin_init action.
		 *
		 * @return void
		 */
		public static function handle_clear_license() {
			// Check if user has permission and is requesting to clear license
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Check if this is a clear license request
			if ( ! isset( $_GET['wp_ulike_clear_license'] ) || $_GET['wp_ulike_clear_license'] !== '1' ) {
				return;
			}

			// Verify nonce for security
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wp_ulike_clear_license' ) ) {
				wp_die( esc_html__( 'Security check failed. Please try again.', 'wp-ulike' ), esc_html__( 'Error', 'wp-ulike' ), array( 'back_link' => true ) );
				return;
			}

			// Clear all license-related data
			self::clear_license();

			// Redirect back to plugins page or admin with success message
			$redirect_url = add_query_arg(
				array(
					'wp_ulike_license_cleared' => '1',
				),
				admin_url( 'plugins.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Show success notice after clearing license.
		 * Hooked to admin_notices action.
		 *
		 * @return void
		 */
		public static function show_license_cleared_notice() {
			if ( ! isset( $_GET['wp_ulike_license_cleared'] ) || $_GET['wp_ulike_license_cleared'] !== '1' ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$message = '<p>' . esc_html__( 'Invalid license has been cleared successfully. You can now enter your new valid license key in the WP ULike Pro settings.', 'wp-ulike' ) . '</p>';
			$html_message = sprintf( '<div class="notice notice-success is-dismissible" style="padding: 15px; margin: 15px 0;">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}

		/**
		 * WP ULike admin notice for invalid/nulled Pro license.
		 * Warning when Pro version is detected with nulled/invalid license.
		 * Hooked to admin_notices action.
		 *
		 * @return void
		 */
		public static function fail_pro_license_notice() {
			// Generate clear license URL with nonce
			$clear_license_url = wp_nonce_url(
				add_query_arg(
					array( 'wp_ulike_clear_license' => '1' ),
					admin_url( 'plugins.php' )
				),
				'wp_ulike_clear_license',
				'_wpnonce'
			);

			$message = '<h3>' . esc_html__( 'Important Update Notice', 'wp-ulike' ) . '</h3>';
			$message .= '<p>' . esc_html__( 'We noticed you\'re using a version of WP ULike Pro that may have been obtained from outside our official channels. Unfortunately, versions from these sources sometimes contain modified or injected code that could affect your site\'s performance and security. We strongly recommend updating to the official version to keep your site safe.', 'wp-ulike' ) . '</p>';
			$message .= '<p>' . esc_html__( 'Here\'s what to do: Clear your current license key using the button below, uninstall the current Pro version, then download and install the latest version from your account after purchasing. Once you install it, just enter your new license key in the settings.', 'wp-ulike' ) . '</p>';
			$message .= '<p style="margin-top: 10px;">';
			$message .= '<a href="' . esc_url( $clear_license_url ) . '" class="button button-primary" style="margin-right: 10px; font-weight: 600;">' . esc_html__( 'Clear License Key', 'wp-ulike' ) . '</a>';
			$message .= '<a href="https://wpulike.com/pricing/?utm_source=license-check&utm_campaign=invalid-license&utm_medium=wp-dash" target="_blank" class="button button-secondary" style="margin-right: 10px; font-weight: 600;">' . esc_html__( 'View Pricing', 'wp-ulike' ) . '</a>';
			$message .= '</p>';
			$message .= '<p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">';
			$message .= esc_html__( 'We\'re currently offering a special price for users upgrading to the latest version. Questions? We\'re here to help—just reach out at', 'wp-ulike' ) . ' <a href="mailto:info@wpulike.com" style="color: #2271b1; font-weight: 600;">info@wpulike.com</a>';
			$message .= '</p>';

			$html_message = sprintf( '<div class="notice notice-warning" style="padding: 20px; margin: 15px 0; border-left: 4px solid #f0b849; background: #fff;">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}
	}
}

// Initialize the validator class and register hooks
WP_Ulike_Pro_Validator::init();

