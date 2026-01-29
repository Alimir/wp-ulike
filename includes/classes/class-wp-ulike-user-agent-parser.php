<?php
/**
 * WP ULike User Agent Parser
 * 
 * Lightweight, high-performance user agent parser without YAML dependencies
 * 
 * @package WP_ULike
 * @since 5.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_Ulike_User_Agent_Parser' ) ) {

		/**
		 * Lightweight User Agent Parser Class
		 * 
		 * Uses regex patterns and pattern matching for fast parsing
		 */
	class WP_Ulike_User_Agent_Parser {

		/**
		 * Parsed user agent data cache
		 * 
		 * @var array
		 */
		private static $cache = array();

		/**
		 * User agent string
		 * 
		 * @var string
		 */
		private $user_agent;

		/**
		 * Parsed client information
		 * 
		 * @var array
		 */
		private $client_info = null;

		/**
		 * Parsed OS information
		 * 
		 * @var array
		 */
		private $os_info = null;

		/**
		 * Is bot flag
		 * 
		 * @var bool|null
		 */
		private $is_bot = null;

		/**
		 * Constructor
		 * 
		 * @param string $user_agent User agent string
		 */
		public function __construct( $user_agent = '' ) {
			$this->user_agent = $user_agent ?: ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		}

		/**
		 * Parse user agent and extract all information
		 * 
		 * @return self
		 */
		public function parse() {
			// Use cache if available
			$cache_key = md5( $this->user_agent );
			if ( isset( self::$cache[ $cache_key ] ) ) {
				$this->client_info = self::$cache[ $cache_key ]['client'];
				$this->os_info     = self::$cache[ $cache_key ]['os'];
				$this->is_bot      = self::$cache[ $cache_key ]['is_bot'];
				return $this;
			}

			// Parse client (browser)
			$this->client_info = $this->parse_client();
			
			// Parse OS
			$this->os_info = $this->parse_os();
			
			// Check if bot
			$this->is_bot = $this->check_bot();

			// Cache results
			self::$cache[ $cache_key ] = array(
				'client' => $this->client_info,
				'os'     => $this->os_info,
				'is_bot' => $this->is_bot,
			);

			return $this;
		}

		/**
		 * Get client (browser) information
		 * 
		 * @return array Array with 'name' and 'version' keys
		 */
		public function get_client() {
			if ( $this->client_info === null ) {
				$this->parse();
			}
			return $this->client_info;
		}

		/**
		 * Get OS information
		 * 
		 * @return array Array with 'name' and 'version' keys
		 */
		public function get_os() {
			if ( $this->os_info === null ) {
				$this->parse();
			}
			return $this->os_info;
		}

		/**
		 * Check if user agent is a bot
		 * 
		 * @return bool
		 */
		public function is_bot() {
			if ( $this->is_bot === null ) {
				$this->parse();
			}
			return $this->is_bot;
		}

		/**
		 * Parse client (browser) from user agent
		 * 
		 * @return array
		 */
		private function parse_client() {
			$ua = $this->user_agent;
			$name = 'unknown-client';
			$version = '0.0';

			// Chrome (including Chromium-based browsers)
			if ( preg_match( '/(?:Chrome|CriOS)\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Chrome';
				$version = $this->normalize_version( $matches[1] );
			}
			// Edge (Chromium-based)
			elseif ( preg_match( '/Edg(?:e|A|iOS)?\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Edge';
				$version = $this->normalize_version( $matches[1] );
			}
			// Firefox
			elseif ( preg_match( '/Firefox\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Firefox';
				$version = $this->normalize_version( $matches[1] );
			}
			// Safari (not Chrome-based)
			elseif ( preg_match( '/Version\/([0-9.]+).*Safari/i', $ua, $matches ) && ! preg_match( '/Chrome|CriOS/i', $ua ) ) {
				$name = 'Safari';
				$version = $this->normalize_version( $matches[1] );
			}
			// Opera
			elseif ( preg_match( '/OPR\/([0-9.]+)/i', $ua, $matches ) || preg_match( '/Opera\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Opera';
				$version = $this->normalize_version( $matches[1] );
			}
			// Internet Explorer
			elseif ( preg_match( '/MSIE ([0-9.]+)/i', $ua, $matches ) || preg_match( '/Trident\/.*rv:([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'IE';
				$version = $this->normalize_version( $matches[1] );
			}
			// Samsung Internet
			elseif ( preg_match( '/SamsungBrowser\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Samsung Internet';
				$version = $this->normalize_version( $matches[1] );
			}
			// UC Browser
			elseif ( preg_match( '/UCBrowser\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'UC Browser';
				$version = $this->normalize_version( $matches[1] );
			}
			// Brave
			elseif ( preg_match( '/Brave\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Brave';
				$version = $this->normalize_version( $matches[1] );
			}
			// Vivaldi
			elseif ( preg_match( '/Vivaldi\/([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Vivaldi';
				$version = $this->normalize_version( $matches[1] );
			}

			return array(
				'name'    => $name,
				'version' => $version,
			);
		}

		/**
		 * Parse OS from user agent
		 * 
		 * @return array
		 */
		private function parse_os() {
			$ua = $this->user_agent;
			$name = 'unknown-os';
			$version = '0.0';

			// Windows
			if ( preg_match( '/Windows NT ([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Windows';
				$version = $this->normalize_windows_version( $matches[1] );
			}
			// macOS
			elseif ( preg_match( '/Mac OS X ([0-9_]+)/i', $ua, $matches ) ) {
				$name = 'macOS';
				$version = $this->normalize_macos_version( $matches[1] );
			}
			// iOS
			elseif ( preg_match( '/(?:iPhone|iPad|iPod).*OS ([0-9_]+)/i', $ua, $matches ) ) {
				$name = 'iOS';
				$version = str_replace( '_', '.', $matches[1] );
			}
			// Android
			elseif ( preg_match( '/Android ([0-9.]+)/i', $ua, $matches ) ) {
				$name = 'Android';
				$version = $this->normalize_version( $matches[1] );
			}
			// Linux
			elseif ( preg_match( '/Linux/i', $ua ) && ! preg_match( '/Android/i', $ua ) ) {
				$name = 'Linux';
				$version = '0.0';
				
				// Try to detect Linux distribution
				if ( preg_match( '/Ubuntu/i', $ua ) ) {
					$name = 'Ubuntu';
				} elseif ( preg_match( '/Fedora/i', $ua ) ) {
					$name = 'Fedora';
				} elseif ( preg_match( '/Debian/i', $ua ) ) {
					$name = 'Debian';
				} elseif ( preg_match( '/CentOS/i', $ua ) ) {
					$name = 'CentOS';
				}
			}
			// Chrome OS
			elseif ( preg_match( '/CrOS/i', $ua ) ) {
				$name = 'Chrome OS';
				$version = '0.0';
			}

			return array(
				'name'    => $name,
				'version' => $version,
			);
		}

		/**
		 * Check if user agent is a bot
		 * 
		 * @return bool
		 */
		private function check_bot() {
			$ua = strtolower( $this->user_agent );

			// Empty user agent is likely a bot
			if ( empty( $ua ) ) {
				return true;
			}

			// Comprehensive bot patterns (2026 updated list)
			$bot_patterns = array(
				// Search engines
				'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
				'yandexbot', 'sogou', 'exabot', 'petalbot', 'applebot',
				
				// Social media bots
				'facebookexternalhit', 'facebookcatalog', 'twitterbot', 'linkedinbot',
				'pinterest', 'pinterestbot', 'slackbot', 'discordbot', 'telegrambot',
				'whatsapp', 'line-poker', 'line-bot',
				
				// Content aggregators
				'embedly', 'quora link preview', 'showyoubot', 'outbrain',
				'flipboard', 'tumblr', 'bitlybot', 'skypeuripreview',
				'nuzzel', 'bitrix link preview', 'xing-contenttabreceiver',
				
				// SEO/analytics bots
				'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot', 'rogerbot',
				'majestic', 'blexbot', 'screaming frog', 'siteauditbot',
				
				// Monitoring/validation
				'w3c_validator', 'validator', 'chrome-lighthouse', 'gtmetrix',
				'pingdom', 'uptimerobot', 'monitor', 'crawler',
				
				// Generic patterns
				'bot', 'crawler', 'spider', 'scraper', 'crawl', 'fetcher',
				'indexer', 'parser', 'checker', 'monitor', 'scanner',
			);

			foreach ( $bot_patterns as $pattern ) {
				if ( strpos( $ua, $pattern ) !== false ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Normalize version string
		 * 
		 * @param string $version Version string
		 * @return string Normalized version
		 */
		private function normalize_version( $version ) {
			// Extract major.minor version
			if ( preg_match( '/^([0-9]+)(?:\.([0-9]+))?/', $version, $matches ) ) {
				return $matches[1] . '.' . ( $matches[2] ?? '0' );
			}
			return '0.0';
		}

		/**
		 * Normalize Windows version
		 * 
		 * @param string $version Windows NT version
		 * @return string Windows version name
		 */
		private function normalize_windows_version( $version ) {
			$version_map = array(
				'10.0' => '10',
				'6.3'  => '8.1',
				'6.2'  => '8',
				'6.1'  => '7',
				'6.0'  => 'Vista',
				'5.1'  => 'XP',
				'5.0'  => '2000',
			);
			return $version_map[ $version ] ?? $version;
		}

		/**
		 * Normalize macOS version
		 * 
		 * @param string $version macOS version (e.g., 10_15_7)
		 * @return string Normalized version (e.g., 10.15.7)
		 */
		private function normalize_macos_version( $version ) {
			return str_replace( '_', '.', $version );
		}

		/**
		 * Clear static cache
		 * Useful for testing or memory management
		 * 
		 * @return void
		 */
		public static function clear_cache() {
			self::$cache = array();
		}
	}
}
