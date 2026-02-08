<?php
/**
 * WP ULike IP Detector
 *
 * Native PHP implementation for accurate client IP detection.
 * Handles Cloudflare, proxy headers, and direct connections.
 *
 * @package WP_ULike
 * @since 5.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_Ulike_Ip_Detector' ) ) {

	/**
	 * IP Detector Class
	 *
	 * Replaces the deprecated Whip library with a native PHP implementation.
	 * Provides accurate IP detection for Cloudflare, proxies, and direct connections.
	 */
	class WP_Ulike_Ip_Detector {

		/**
		 * Cached Cloudflare IP ranges
		 *
		 * @var array|null
		 */
		private static $cloudflare_ips = null;

		/**
		 * Cached user IP address
		 *
		 * @var string|null
		 */
		private static $cached_ip = null;

		/**
		 * Cached Cloudflare IP validation results
		 *
		 * @var array
		 */
		private static $cloudflare_ip_cache = array();

		/**
		 * Cached private IP validation results
		 *
		 * @var array
		 */
		private static $private_ip_cache = array();

		/**
		 * Get user IP address
		 *
		 * Handles Cloudflare, proxy headers, and direct connections.
		 * Results are cached per request for performance.
		 *
		 * Security Note: For non-security-critical use cases (like tracking likes),
		 * this implementation prioritizes functionality over strict security.
		 * Proxy headers are checked but not validated against a trusted proxy whitelist.
		 * This is acceptable for engagement tracking but should not be used for authentication.
		 *
		 * @return string
		 */
		public static function get_ip() {
			// Return cached IP if available
			if ( self::$cached_ip !== null ) {
				return self::$cached_ip;
			}

			$ip = '127.0.0.1';

			// Check Cloudflare header first (most reliable when behind Cloudflare)
			// Cloudflare is trusted because we validate REMOTE_ADDR against their IP ranges
			$cf_ip = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '';
			if ( ! empty( $cf_ip ) ) {
				// Validate that request is actually from Cloudflare
				$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
				if ( ! empty( $remote_addr ) && self::is_cloudflare_ip( $remote_addr ) ) {
					if ( self::validate_ip( $cf_ip ) ) {
						$ip = $cf_ip;
						self::$cached_ip = apply_filters( 'wp_ulike_get_user_ip', $ip );
						return self::$cached_ip;
					}
				}
			}

			// Check other proxy headers (in order of reliability)
			// Note: These headers can be spoofed, but for engagement tracking this is acceptable
			$proxy_headers = array(
				'HTTP_X_REAL_IP',           // Nginx, Apache with mod_remoteip
				'HTTP_X_FORWARDED_FOR',      // Most common proxy header
				'HTTP_X_FORWARDED',          // Standard Forwarded header (RFC 7239)
				'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster/load balancer
				'HTTP_FORWARDED_FOR',        // Legacy
				'HTTP_FORWARDED',            // Legacy
				'HTTP_CLIENT_IP',            // Legacy
			);

			foreach ( $proxy_headers as $header ) {
				if ( ! isset( $_SERVER[ $header ] ) ) {
					continue;
				}
				$header_value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( ! empty( $header_value ) ) {
					// X-Forwarded-For can contain multiple IPs (client, proxy1, proxy2)
					// Take the first (leftmost) IP which is the original client
					if ( strpos( $header_value, ',' ) !== false ) {
						$ips = array_map( 'trim', explode( ',', $header_value ) );
						$header_value = ! empty( $ips[0] ) ? $ips[0] : '';
					}

					// Validate IP and ensure it's not a private/local IP (unless from localhost)
					if ( ! empty( $header_value ) && self::validate_ip( $header_value ) ) {
						// Additional check: reject private IPs from proxy headers (potential spoofing)
						// But allow if REMOTE_ADDR is also private (local development)
						$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
						$is_remote_private = self::is_private_ip( $remote_addr );

						if ( self::is_private_ip( $header_value ) && ! $is_remote_private ) {
							// Private IP in header but not from localhost - likely spoofed, skip
							continue;
						}

						$ip = $header_value;
						break;
					}
				}
			}

			// Fallback to REMOTE_ADDR (most reliable for direct connections)
			if ( $ip === '127.0.0.1' || ! self::validate_ip( $ip ) ) {
				$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
				if ( ! empty( $remote_addr ) && self::validate_ip( $remote_addr ) ) {
					$ip = $remote_addr;
				}
			}

			// Final validation - ensure we have a valid IP
			if ( ! self::validate_ip( $ip ) ) {
				$ip = '127.0.0.1';
			}

			self::$cached_ip = apply_filters( 'wp_ulike_get_user_ip', $ip );
			return self::$cached_ip;
		}

		/**
		 * Validate IP address
		 *
		 * Ensures an IP address is a valid IPv4 or IPv6 address.
		 *
		 * @param string $ip IP address to validate
		 * @return bool
		 */
		public static function validate_ip( $ip ) {
			if ( empty( $ip ) || ! is_string( $ip ) ) {
				return false;
			}
			return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
		}

		/**
		 * Check if an IP address is a private/local IP
		 *
		 * Results are cached per request for performance.
		 *
		 * @param string $ip IP address to check
		 * @return bool
		 */
		private static function is_private_ip( $ip ) {
			// Return cached result if available
			if ( isset( self::$private_ip_cache[ $ip ] ) ) {
				return self::$private_ip_cache[ $ip ];
			}

			$result = false;

			if ( ! self::validate_ip( $ip ) ) {
				self::$private_ip_cache[ $ip ] = false;
				return false;
			}

			// IPv4 private ranges - use native PHP filter (fastest method)
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$result = ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
			}
			// IPv6 private ranges (link-local, unique-local, etc.)
			elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				// Fast check for loopback first
				if ( $ip === '::1' || $ip === '0:0:0:0:0:0:0:1' ) {
					$result = true;
				} else {
					// Check for link-local (fe80::/10) and unique-local (fc00::/7)
					$result = self::ip_in_range( $ip, 'fe80::/10' ) || self::ip_in_range( $ip, 'fc00::/7' );
				}
			}

			// Cache result
			self::$private_ip_cache[ $ip ] = $result;
			return $result;
		}

		/**
		 * Check if an IP address is within a CIDR range
		 *
		 * Supports both IPv4 and IPv6 CIDR notation.
		 *
		 * @param string $ip IP address to check
		 * @param string $range CIDR range (e.g., '192.168.1.0/24' or '2001:db8::/32')
		 * @return bool
		 */
		public static function ip_in_range( $ip, $range ) {
			if ( strpos( $range, '/' ) === false ) {
				// Single IP comparison
				return $ip === $range;
			}

			list( $subnet, $mask ) = explode( '/', $range, 2 );
			$mask = (int) $mask;

			// IPv4
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				if ( ! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
					return false;
				}
				$ip_long = ip2long( $ip );
				$subnet_long = ip2long( $subnet );
				$mask_long = -1 << ( 32 - $mask );
				return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
			}

			// IPv6
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				if ( ! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					return false;
				}
				$ip_bin = inet_pton( $ip );
				$subnet_bin = inet_pton( $subnet );
				$mask_bytes = (int) ( $mask / 8 );
				$mask_bits = $mask % 8;

				// Compare full bytes
				if ( $mask_bytes > 0 && substr( $ip_bin, 0, $mask_bytes ) !== substr( $subnet_bin, 0, $mask_bytes ) ) {
					return false;
				}

				// Compare partial byte if needed
				if ( $mask_bits > 0 && $mask_bytes < strlen( $ip_bin ) ) {
					$mask_byte = 0xFF << ( 8 - $mask_bits );
					$ip_byte = ord( $ip_bin[ $mask_bytes ] ) & $mask_byte;
					$subnet_byte = ord( $subnet_bin[ $mask_bytes ] ) & $mask_byte;
					return $ip_byte === $subnet_byte;
				}

				return true;
			}

			return false;
		}

		/**
		 * Get Cloudflare IP ranges
		 *
		 * Fetches and caches Cloudflare's official IP ranges (IPv4 and IPv6).
		 * Results are cached for 1 week via WordPress transients.
		 *
		 * @return array Array with 'v4' and 'v6' keys containing IP ranges
		 */
		public static function get_cloudflare_ips() {
			// Return cached IPs if available
			if ( self::$cloudflare_ips !== null ) {
				return self::$cloudflare_ips;
			}

			$transient_key = 'wp_ulike_cloudflare_ips';
			$ip_addresses = get_transient( $transient_key );

			if ( false === $ip_addresses ) {
				$ip_addresses = array_fill_keys( array( 'v4', 'v6' ), array() );

				foreach ( array_keys( $ip_addresses ) as $version ) {
					$url = 'https://www.cloudflare.com/ips-' . $version;
					$response = wp_remote_get( $url, array( 'sslverify' => true, 'timeout' => 10 ) );

					if ( is_wp_error( $response ) ) {
						continue;
					}

					$status_code = wp_remote_retrieve_response_code( $response );
					if ( '200' !== (string) $status_code ) {
						continue;
					}

					$body = wp_remote_retrieve_body( $response );
					$ranges = array_filter(
						(array) preg_split( '/\R/', $body )
					);

					$ip_addresses[ $version ] = $ranges;
				}

				// Cache for 1 week
				set_transient( $transient_key, $ip_addresses, WEEK_IN_SECONDS );
			}

			self::$cloudflare_ips = $ip_addresses;
			return self::$cloudflare_ips;
		}

		/**
		 * Check if the current request is from Cloudflare
		 *
		 * Validates that the REMOTE_ADDR is within Cloudflare's official IP ranges.
		 * Results are cached per request for performance.
		 *
		 * @param string|null $ip IP address to check (optional, defaults to REMOTE_ADDR)
		 * @return bool
		 */
		public static function is_cloudflare_ip( $ip = null ) {
			if ( $ip === null ) {
				$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			}

			if ( empty( $ip ) || ! self::validate_ip( $ip ) ) {
				return false;
			}

			// Return cached result if available
			if ( isset( self::$cloudflare_ip_cache[ $ip ] ) ) {
				return self::$cloudflare_ip_cache[ $ip ];
			}

			$result = false;
			$cloudflare_ips = self::get_cloudflare_ips();
			$is_ipv6 = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );

			$ranges = $is_ipv6 ? $cloudflare_ips['v6'] : $cloudflare_ips['v4'];

			// Early exit optimization: most IPs won't be Cloudflare
			// Check a few common ranges first if available, then full loop
			foreach ( $ranges as $range ) {
				if ( self::ip_in_range( $ip, $range ) ) {
					$result = true;
					break;
				}
			}

			// Cache result
			self::$cloudflare_ip_cache[ $ip ] = $result;
			return $result;
		}
	}
}
