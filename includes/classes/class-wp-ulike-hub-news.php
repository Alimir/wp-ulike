<?php
/**
 * ULike Hub: short messages from the WP ULike team.
 *
 * Twice a day a background task downloads the published messages from
 * wpulike.com. The request carries no site data: targeting rules arrive with
 * the messages and are checked here, on the site. Panel opens only read the
 * stored copy, so a slow or unreachable server never slows the Hub.
 *
 * Messages are plain text with at most one https link to a WP ULike domain,
 * and they are rendered as text, never as HTML.
 *
 * Settings → General → "WP ULike news in the Hub" turns this off, and so does
 * "Hide Plugin Admin Notices".
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Hub_News' ) ) {

	/**
	 * Remote Hub messages.
	 */
	class WP_Ulike_Hub_News {

		const OPTION     = 'wp_ulike_hub_news';
		const HOOK       = 'wp_ulike_hub_refresh_news';
		const OPTION_KEY = 'enable_hub_news';
		const ENDPOINT   = 'https://wpulike.com/api/audit/v1/notifications?surface=hub';
		const TTL        = 12 * HOUR_IN_SECONDS;
		const RETRY      = 3 * HOUR_IN_SECONDS;
		const MAX_ITEMS  = 20;
		const TITLE_MAX  = 80;
		const TEXT_MAX   = 280;
		const LABEL_MAX  = 30;

		/**
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'refresh' ) );
		}

		/**
		 * @return bool
		 */
		public static function is_enabled() {
			if ( ! WP_Ulike_Hub::is_setting_on() || ! wp_ulike_is_true( wp_ulike_get_option( self::OPTION_KEY, true ) ) ) {
				return false;
			}

			if ( wp_ulike_is_true( wp_ulike_get_option( 'disable_admin_notice', false ) ) ) {
				return false;
			}

			/**
			 * Whether the Hub downloads and shows messages from the WP ULike team.
			 *
			 * @param bool $enabled Enabled.
			 */
			return (bool) apply_filters( 'wp_ulike_hub_news_enabled', true );
		}

		/**
		 * Stored messages; schedules a download when they are due.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function items() {
			if ( ! self::is_enabled() ) {
				return array();
			}

			$stored = get_option( self::OPTION );
			if ( ! is_array( $stored ) || empty( $stored['next'] ) || (int) $stored['next'] < time() ) {
				if ( ! wp_next_scheduled( self::HOOK ) ) {
					wp_schedule_single_event( time(), self::HOOK );
				}
			}

			return is_array( $stored ) && ! empty( $stored['items'] ) ? (array) $stored['items'] : array();
		}

		/**
		 * Download and store the messages. Keeps the old copy when it fails.
		 *
		 * @return void
		 */
		public static function refresh() {
			if ( ! self::is_enabled() ) {
				delete_option( self::OPTION );
				return;
			}

			$stored = get_option( self::OPTION );
			$items  = is_array( $stored ) && isset( $stored['items'] ) ? (array) $stored['items'] : array();

			$response = wp_safe_remote_get(
				self::ENDPOINT,
				array(
					'timeout'             => 5,
					'redirection'         => 0,
					// WordPress's default user agent includes the site address.
					'user-agent'          => 'WP ULike/' . WP_ULIKE_VERSION,
					'limit_response_size' => 65536,
					'headers'             => array( 'Accept' => 'application/json' ),
				)
			);

			$body = 200 === (int) wp_remote_retrieve_response_code( $response ) ? json_decode( (string) wp_remote_retrieve_body( $response ), true ) : null;

			// Only the Hub envelope counts; an older server answers with the
			// Statistics feed as a bare list, which is not meant for the Hub.
			if ( ! is_array( $body ) || ! isset( $body['surface'], $body['items'] ) || 'hub' !== $body['surface'] || ! is_array( $body['items'] ) ) {
				update_option(
					self::OPTION,
					array(
						'items' => $items,
						'next'  => time() + self::RETRY,
					),
					false
				);
				return;
			}

			$items = array();
			foreach ( array_slice( $body['items'], 0, self::MAX_ITEMS ) as $raw ) {
				$item = self::sanitize_item( $raw );
				if ( $item ) {
					$items[] = $item;
				}
			}

			update_option(
				self::OPTION,
				array(
					'items' => $items,
					'next'  => time() + self::TTL,
				),
				false
			);
		}

		/**
		 * The first message for this site and user, or null.
		 *
		 * @param array<string, int>   $dismissed Dismissed IDs and when.
		 * @param array<string, mixed> $live      Live numbers from WP_Ulike_Hub_Insights::live().
		 * @return array<string, mixed>|null
		 */
		public static function pick( $dismissed, $live ) {
			$items = self::items();
			if ( ! $items ) {
				return null;
			}

			$build   = defined( 'WP_ULIKE_PRO_VERSION' ) ? 'pro' : 'free';
			$context = array(
				'current_time'       => time(),
				'site_lang'          => self::lang( get_bloginfo( 'language' ) ),
				'overview_total'     => (int) $live['all_time'],
				'overview_today'     => (int) $live['today'],
				'overview_yesterday' => (int) $live['yesterday'],
				'build_type'         => $build,
			);

			usort(
				$items,
				static function ( $a, $b ) {
					return $a['priority'] === $b['priority'] ? $b['id'] - $a['id'] : $a['priority'] - $b['priority'];
				}
			);

			foreach ( $items as $item ) {
				$id = 'news_' . $item['id'];
				if ( isset( $dismissed[ $id ] ) ) {
					continue;
				}
				if ( 'general' !== $item['audience'] && $build !== $item['audience'] ) {
					continue;
				}
				// "Today" is unknown just after midnight; rules about it wait.
				if ( null === $live['today'] && self::uses_day_numbers( $item['rules'] ) ) {
					continue;
				}
				if ( ! self::rules_pass( $item['rules'], $context ) ) {
					continue;
				}

				$site_name = WP_Ulike_Hub::plain_text( get_bloginfo( 'name' ) );

				return array(
					'id'     => $id,
					'title'  => str_replace( '{{site_name}}', $site_name, $item['title'] ),
					'text'   => str_replace( '{{site_name}}', $site_name, $item['text'] ),
					'action' => $item['button'],
					'time'   => (int) $item['modified'],
				);
			}

			return null;
		}

		/* ------------------------------------------------------------------
		 * Rules: same meaning as in the Statistics notifications
		 * ------------------------------------------------------------------ */

		/**
		 * Shown when a "show" rule matches and no "hide" rule does. No rules: shown.
		 *
		 * @param array<int, array<string, string>> $rules   Rules.
		 * @param array<string, mixed>              $context Site values.
		 * @return bool
		 */
		public static function rules_pass( $rules, $context ) {
			if ( empty( $rules ) ) {
				return true;
			}

			$show = false;
			foreach ( $rules as $rule ) {
				if ( ! self::rule_matches( $rule, $context ) ) {
					continue;
				}
				if ( 'hide' === $rule['action'] ) {
					return false;
				}
				$show = true;
			}

			return $show;
		}

		/**
		 * @param array<string, string> $rule    Rule.
		 * @param array<string, mixed>  $context Site values.
		 * @return bool
		 */
		protected static function rule_matches( $rule, $context ) {
			$param = $rule['param'];
			$have  = $context[ $param ];
			$want  = $rule['value'];

			switch ( $param ) {
				case 'current_time':
					$want = self::parse_time( $want );
					if ( null === $want ) {
						return false;
					}
					break;
				case 'site_lang':
					$want = self::lang( $want );
					break;
				case 'build_type':
					$want = strtolower( $want );
					break;
				default:
					if ( ! is_numeric( $want ) ) {
						return false;
					}
					$want = (float) $want;
			}

			switch ( $rule['operator'] ) {
				case '==':
					return $have === $want || ( is_numeric( $have ) && is_numeric( $want ) && (float) $have === (float) $want );
				case '!=':
					return ! ( $have === $want || ( is_numeric( $have ) && is_numeric( $want ) && (float) $have === (float) $want ) );
				case '>':
					return $have > $want;
				case '<':
					return $have < $want;
				case '>=':
					return $have >= $want;
				case '<=':
					return $have <= $want;
				case '==contains':
					return is_string( $have ) && is_string( $want ) && '' !== $want && false !== strpos( $have, $want );
				case '!=empty':
					return null !== $have && '' !== $have;
				case '==empty':
					return null === $have || '' === $have;
			}

			return false;
		}

		/**
		 * Rule dates are written as local time in the site's time zone.
		 *
		 * @param string $value Date such as 2026-12-01T09:00.
		 * @return int|null
		 */
		protected static function parse_time( $value ) {
			try {
				$date = new DateTimeImmutable( (string) $value, wp_timezone() );
			} catch ( Exception $e ) {
				return null;
			}

			return $date->getTimestamp();
		}

		/**
		 * @param string $value Locale or code.
		 * @return string Two-letter code, as the Statistics rules use.
		 */
		protected static function lang( $value ) {
			return substr( strtolower( (string) $value ), 0, 2 );
		}

		/**
		 * @param array<int, array<string, string>> $rules Rules.
		 * @return bool
		 */
		protected static function uses_day_numbers( $rules ) {
			foreach ( $rules as $rule ) {
				if ( in_array( $rule['param'], array( 'overview_today', 'overview_yesterday' ), true ) ) {
					return true;
				}
			}

			return false;
		}

		/* ------------------------------------------------------------------
		 * Sanitizing
		 * ------------------------------------------------------------------ */

		/**
		 * Keep only well-formed messages, as plain text with a safe link.
		 *
		 * @param mixed $raw Item from the server.
		 * @return array<string, mixed>|null
		 */
		protected static function sanitize_item( $raw ) {
			if ( ! is_array( $raw ) || empty( $raw['id'] ) ) {
				return null;
			}

			$title = self::text( isset( $raw['title'] ) ? $raw['title'] : '', self::TITLE_MAX );
			$text  = self::text( isset( $raw['text'] ) ? $raw['text'] : '', self::TEXT_MAX );
			if ( '' === $text && '' === $title ) {
				return null;
			}

			$audience = isset( $raw['audience'] ) ? sanitize_key( $raw['audience'] ) : 'general';

			$button = null;
			if ( ! empty( $raw['button']['label'] ) && ! empty( $raw['button']['url'] ) && self::is_allowed_url( $raw['button']['url'] ) ) {
				$button = array(
					'label' => self::text( $raw['button']['label'], self::LABEL_MAX ),
					'url'   => esc_url_raw( (string) $raw['button']['url'], array( 'https' ) ),
				);
			}

			$rules = array();
			foreach ( isset( $raw['rules'] ) && is_array( $raw['rules'] ) ? $raw['rules'] : array() as $rule ) {
				$rule = self::sanitize_rule( $rule );
				if ( ! $rule ) {
					// A rule this version cannot read could be what keeps the
					// message from the wrong sites, so the message is skipped.
					return null;
				}
				$rules[] = $rule;
			}

			return array(
				'id'       => absint( $raw['id'] ),
				'title'    => $title,
				'text'     => $text,
				'audience' => in_array( $audience, array( 'general', 'free', 'pro' ), true ) ? $audience : 'general',
				'priority' => isset( $raw['priority'] ) ? (int) $raw['priority'] : 10,
				'rules'    => $rules,
				'button'   => $button,
				'modified' => isset( $raw['modified'] ) ? absint( $raw['modified'] ) : 0,
			);
		}

		/**
		 * @param mixed $rule Rule from the server.
		 * @return array<string, string>|null
		 */
		protected static function sanitize_rule( $rule ) {
			$params    = array( 'current_time', 'site_lang', 'overview_total', 'overview_today', 'overview_yesterday', 'build_type' );
			$operators = array( '==', '!=', '>', '<', '>=', '<=', '==contains', '!=empty', '==empty' );

			if ( ! is_array( $rule ) || empty( $rule['param'] ) || empty( $rule['operator'] ) ) {
				return null;
			}
			if ( ! in_array( $rule['param'], $params, true ) || ! in_array( $rule['operator'], $operators, true ) ) {
				return null;
			}

			return array(
				'param'    => $rule['param'],
				'operator' => $rule['operator'],
				'value'    => substr( sanitize_text_field( isset( $rule['value'] ) ? (string) $rule['value'] : '' ), 0, 100 ),
				'action'   => isset( $rule['action'] ) && 'hide' === $rule['action'] ? 'hide' : 'show',
			);
		}

		/**
		 * Links may only lead to WP ULike's own sites, over https.
		 *
		 * @param mixed $url Link.
		 * @return bool
		 */
		public static function is_allowed_url( $url ) {
			$url = (string) $url;
			if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
				return false;
			}

			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

			/**
			 * Domains Hub messages may link to.
			 *
			 * @param string[] $domains Domains; subdomains are allowed too.
			 */
			$domains = (array) apply_filters( 'wp_ulike_hub_news_domains', array( 'wpulike.com', 'technowich.com', 'wordpress.org' ) );

			foreach ( $domains as $domain ) {
				$domain = strtolower( (string) $domain );
				if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Plain text, cut to a length.
		 *
		 * @param mixed $text  Text.
		 * @param int   $limit Maximum characters.
		 * @return string
		 */
		protected static function text( $text, $limit ) {
			// preg_replace() returns null on invalid UTF-8; such text is dropped.
			$text = trim( (string) preg_replace( '/\s+/u', ' ', WP_Ulike_Hub::plain_text( is_scalar( $text ) ? (string) $text : '' ) ) );

			if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $limit ) {
				return rtrim( mb_substr( $text, 0, $limit - 1 ) ) . '…';
			}

			return strlen( $text ) > $limit * 4 ? substr( $text, 0, $limit * 4 ) : $text;
		}
	}
}
