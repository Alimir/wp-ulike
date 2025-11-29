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
	}
}

