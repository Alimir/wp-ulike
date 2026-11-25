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
		 * Get user IP address
		 *
		 * Handles Cloudflare, proxy headers, and direct connections.
		 * Results are cached per request for performance.
		 *
		 * Forwarded client headers (X-Forwarded-For and similar) are read only when
		 * REMOTE_ADDR is Cloudflare or a proxy listed in WP_ULIKE_TRUSTED_PROXIES
		 * / the `wp_ulike_trusted_proxies` filter. Otherwise the header is
		 * client-controlled and one visitor can present a new address on every
		 * vote. Direct connections use REMOTE_ADDR.
		 *
		 * @return string
		 */
		public static function get_ip() {
			// Return cached IP if available
			if ( self::$cached_ip !== null ) {
				return self::$cached_ip;
			}

			$remote_addr = self::get_remote_addr();
			$ip          = '' !== $remote_addr ? $remote_addr : '127.0.0.1';

			// Cloudflare sets CF-Connecting-IP and overwrites a client-supplied value.
			// Trust it only when the TCP peer is actually on Cloudflare's network.
			$cf_ip = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '';
			if ( '' !== $remote_addr && self::is_cloudflare_ip( $remote_addr ) && self::validate_ip( $cf_ip ) && ! self::is_configured_proxy( $cf_ip ) ) {
				$ip = $cf_ip;
			} elseif ( '' !== $remote_addr && self::is_trusted_proxy( $remote_addr ) ) {
				$forwarded = self::get_forwarded_client_ip();
				if ( '' !== $forwarded ) {
					$ip = $forwarded;
				}
			} elseif ( '' !== $remote_addr && wp_doing_ajax() ) {
				self::remember_untrusted_proxy( $remote_addr );
			}

			if ( ! self::validate_ip( $ip ) ) {
				$ip = '127.0.0.1';
			}

			self::$cached_ip = apply_filters( 'wp_ulike_get_user_ip', $ip );
			return self::$cached_ip;
		}

		/**
		 * Note a private-network peer that forwards visitor addresses, so the
		 * admin can be told which proxy to trust. A visitor on the public
		 * internet cannot connect from a private address.
		 *
		 * @param string $remote_addr TCP peer address.
		 * @return void
		 */
		private static function remember_untrusted_proxy( $remote_addr ) {
			$is_private = false === filter_var( $remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
			if ( ! $is_private || '' === self::get_forwarded_client_ip() ) {
				return;
			}

			if ( get_transient( 'wp_ulike_untrusted_proxy' ) !== $remote_addr ) {
				set_transient( 'wp_ulike_untrusted_proxy', $remote_addr, DAY_IN_SECONDS );
			}
		}

		/**
		 * TCP peer address, ignoring every client-supplied forwarding header.
		 *
		 * @return string Empty when REMOTE_ADDR is missing or not an IP.
		 */
		private static function get_remote_addr() {
			$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			return self::validate_ip( $remote_addr ) ? $remote_addr : '';
		}

		/**
		 * Proxy addresses allowed to supply a forwarded client IP.
		 *
		 * Cloudflare ranges are trusted separately, after REMOTE_ADDR is checked
		 * against them. Add a reverse proxy here when it does not write the
		 * real client address into REMOTE_ADDR.
		 *
		 *     define( 'WP_ULIKE_TRUSTED_PROXIES', '10.0.0.0/8,127.0.0.1' );
		 *
		 * @return string[] IP addresses or CIDR ranges.
		 */
		public static function get_trusted_proxies() {
			static $cached = null;

			if ( null !== $cached ) {
				return $cached;
			}

			$proxies = array();
			if ( defined( 'WP_ULIKE_TRUSTED_PROXIES' ) ) {
				$raw = WP_ULIKE_TRUSTED_PROXIES;
				if ( is_string( $raw ) ) {
					$proxies = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
				} elseif ( is_array( $raw ) ) {
					$proxies = $raw;
				}
			}

			/**
			 * Trusted reverse proxies.
			 *
			 * Forwarded headers are ignored unless REMOTE_ADDR matches one of
			 * these values or a Cloudflare range.
			 *
			 * @param string[] $proxies IP addresses or CIDR ranges.
			 */
			$proxies = apply_filters( 'wp_ulike_trusted_proxies', $proxies );
			if ( ! is_array( $proxies ) ) {
				$proxies = array();
			}

			$clean = array();
			foreach ( $proxies as $proxy ) {
				$proxy = trim( (string) $proxy );
				if ( '' !== $proxy ) {
					$clean[] = $proxy;
				}
			}

			$cached = $clean;
			return $cached;
		}

		/**
		 * Whether an address is a proxy this site has chosen to trust.
		 *
		 * Does not include Cloudflare. Callers that already know the peer is
		 * Cloudflare should use is_cloudflare_ip() so a client IP is not rejected
		 * just because it also appears in a configured range.
		 *
		 * @param string $ip IP address.
		 * @return bool
		 */
		private static function is_configured_proxy( $ip ) {
			if ( ! self::validate_ip( $ip ) ) {
				return false;
			}

			foreach ( self::get_trusted_proxies() as $proxy ) {
				if ( self::ip_in_range( $ip, $proxy ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Whether REMOTE_ADDR is allowed to assert a forwarded client address.
		 *
		 * @param string $ip IP address.
		 * @return bool
		 */
		public static function is_trusted_proxy( $ip ) {
			if ( ! self::validate_ip( $ip ) ) {
				return false;
			}

			return self::is_cloudflare_ip( $ip ) || self::is_configured_proxy( $ip );
		}

		/**
		 * Client address from a forwarding header sent by a trusted peer.
		 *
		 * The list is read from the right. Hops this site trusts are skipped,
		 * so a visitor cannot prepend a fake address in front of the one the
		 * proxy appended.
		 *
		 * @return string Empty when no usable client address is present.
		 */
		private static function get_forwarded_client_ip() {
			$headers = array(
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'HTTP_X_REAL_IP',
				'HTTP_X_CLUSTER_CLIENT_IP',
				'HTTP_CLIENT_IP',
			);

			foreach ( $headers as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}

				$header_value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( '' === $header_value ) {
					continue;
				}

				$client = self::client_ip_from_forwarded_list( $header_value );
				if ( '' !== $client ) {
					return $client;
				}
			}

			return '';
		}

		/**
		 * First untrusted address in a comma-separated forwarding list.
		 *
		 * @param string $header_value Raw header value.
		 * @return string
		 */
		private static function client_ip_from_forwarded_list( $header_value ) {
			$parts = array_reverse( array_map( 'trim', explode( ',', $header_value ) ) );

			foreach ( $parts as $candidate ) {
				if ( 0 === stripos( $candidate, 'for=' ) ) {
					$candidate = trim( substr( $candidate, 4 ), " \t\"[]" );
				}

				if ( ! self::validate_ip( $candidate ) || self::is_trusted_proxy( $candidate ) ) {
					continue;
				}

				return $candidate;
			}

			return '';
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
		 * Bundled Cloudflare IP ranges (fallback; refreshed asynchronously).
		 *
		 * Source: https://www.cloudflare.com/ips-v4 / https://www.cloudflare.com/ips-v6
		 *
		 * @return array{v4: string[], v6: string[]}
		 */
		public static function get_default_cloudflare_ips() {
			return array(
				'v4' => array(
					'173.245.48.0/20',
					'103.21.244.0/22',
					'103.22.200.0/22',
					'103.31.4.0/22',
					'141.101.64.0/18',
					'108.162.192.0/18',
					'190.93.240.0/20',
					'188.114.96.0/20',
					'197.234.240.0/22',
					'198.41.128.0/17',
					'162.158.0.0/15',
					'104.16.0.0/13',
					'104.24.0.0/14',
					'172.64.0.0/13',
					'131.0.72.0/22',
				),
				'v6' => array(
					'2400:cb00::/32',
					'2606:4700::/32',
					'2803:f800::/32',
					'2405:b500::/32',
					'2405:8100::/32',
					'2a06:98c0::/29',
					'2c0f:f248::/32',
				),
			);
		}

		/**
		 * Whether cached Cloudflare ranges are usable.
		 *
		 * @param mixed $ip_addresses Transient value.
		 * @return bool
		 */
		private static function ranges_are_usable( $ip_addresses ) {
			return is_array( $ip_addresses )
				&& ! empty( $ip_addresses['v4'] )
				&& is_array( $ip_addresses['v4'] );
		}

		/**
		 * Get Cloudflare IP ranges.
		 *
		 * Never blocks page loads on HTTP. Uses the transient when present;
		 * otherwise serves bundled defaults and caches them until expiry.
		 * Ranges change rarely — plugin updates refresh the defaults.
		 *
		 * @return array{v4: string[], v6: string[]}
		 */
		public static function get_cloudflare_ips() {
			if ( self::$cloudflare_ips !== null ) {
				return self::$cloudflare_ips;
			}

			$transient_key = 'wp_ulike_cloudflare_ips';
			$ip_addresses  = get_transient( $transient_key );

			if ( self::ranges_are_usable( $ip_addresses ) ) {
				self::$cloudflare_ips = $ip_addresses;
				return self::$cloudflare_ips;
			}

			// Transient miss/empty (common after migrations): use bundled ranges.
			// Do not wp_remote_get() here — that used to add ~1s+ per request.
			self::$cloudflare_ips = self::get_default_cloudflare_ips();
			set_transient( $transient_key, self::$cloudflare_ips, WEEK_IN_SECONDS );

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
