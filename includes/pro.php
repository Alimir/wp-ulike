<?php
/**
 * WP ULike Pro License Validation
 *
 * Handles validation of WP ULike Pro licenses and prevents nulled/cracked versions
 * from running the free version.
 *
 * @package    wp-ulike
 * @author     TechnoWich
 * @since      4.8.0
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

			// Check license status using cached API data (no force request for performance)
			$license_data = WP_Ulike_Pro_API::get_license_data( false );

			// Invalid license statuses that should stop the free version
			$invalid_statuses = [
				WP_Ulike_Pro_API::STATUS_INVALID,
				WP_Ulike_Pro_API::STATUS_DISABLED,
				WP_Ulike_Pro_API::STATUS_MISSING,
			];

			// Get and clean license key
			$license_key = get_option( 'wp_ulike_pro_license_key', '' );
			if ( empty( $license_key ) ) {
				// No license key - check API status only
				return self::check_license_status( $license_data, $invalid_statuses );
			}

			$license_key_clean = trim( $license_key );

			// STEP 1: Validate format first - whitelist valid formats immediately
			if ( self::is_valid_license_format( $license_key_clean ) ) {
				// Valid format - check API status only (don't check for nulled patterns)
				return self::check_license_status( $license_data, $invalid_statuses );
			}

			// STEP 2: Invalid format - check for nulled patterns
			if ( self::is_nulled_license( $license_key_clean ) ) {
				return false; // Nulled license detected
			}

			// STEP 3: Check API status for invalid formats
			return self::check_license_status( $license_data, $invalid_statuses, true );
		}

		/**
		 * Check if license key matches valid format patterns.
		 * Valid formats: UUID (with/without dashes), long hex strings (30-40 chars)
		 *
		 * @param string $license_key Cleaned license key
		 * @return bool True if valid format, false otherwise
		 */
		private static function is_valid_license_format( $license_key ) {
			if ( empty( $license_key ) ) {
				return false;
			}

			// Must contain only hex characters (0-9, a-f) and dashes
			if ( ! preg_match( '/^[0-9a-f-]+$/i', $license_key ) ) {
				return false;
			}

			// Remove dashes to count hex characters
			$hex_only = str_replace( '-', '', $license_key );
			$hex_length = strlen( $hex_only );

			// Valid length range: 30-40 hex characters
			if ( $hex_length < 30 || $hex_length > 40 ) {
				return false;
			}

			// Check specific valid patterns
			$valid_patterns = [
				// Standard UUID: 8-4-4-4-12
				'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
				// UUID with flexible last segment: 8-4-4-4-10-14
				'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{10,14}$/i',
				// UUID without dashes: exactly 32 hex chars
				'/^[0-9a-f]{32}$/i',
				// Long hex string without dashes: 30-40 hex chars
				'/^[0-9a-f]{30,40}$/i',
			];

			foreach ( $valid_patterns as $pattern ) {
				if ( preg_match( $pattern, $license_key ) ) {
					return true;
				}
			}

			// Hex with dashes but flexible format: 30-40 hex chars, reasonable dash count
			if ( $hex_length >= 30 && $hex_length <= 40 && preg_match( '/-/', $license_key ) ) {
				$dash_count = substr_count( $license_key, '-' );
				if ( $dash_count <= 5 ) {
					return true; // Flexible format with reasonable dash count
				}
			}

			return false;
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
		 * @param bool $is_nulled Whether nulled pattern was detected
		 * @return bool True if valid, false if invalid
		 */
		private static function check_license_status( $license_data, $invalid_statuses, $is_nulled = false ) {
			// If nulled pattern detected, always return false
			if ( $is_nulled ) {
				return false;
			}

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

