<?php
/**
 * Stats bootstrap meta for the free React admin panel.
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Stats_Meta' ) ) {
	/**
	 * Builds bootstrap meta consumed by wp_ulike_stats_api.
	 */
	final class WP_Ulike_Stats_Meta {

		/**
		 * Site meta for the free React admin bootstrap.
		 *
		 * @param array $content_types Active stats content types.
		 * @return array{voting_mode:string}
		 */
		public static function get_site_stats_meta( $content_types = array() ) {
			static $cache = array();

			$types = array_values( array_filter( (array) $content_types ) );
			if ( empty( $types ) ) {
				$types = array( 'posts' );
			}

			sort( $types );
			$cache_key = implode( ',', $types );

			if ( isset( $cache[ $cache_key ] ) ) {
				return $cache[ $cache_key ];
			}

			$cache[ $cache_key ] = array(
				'voting_mode' => self::get_site_voting_mode( $types ),
			);

			return $cache[ $cache_key ];
		}

		/**
		 * @param array $content_types Active stats content types.
		 * @return string logged_in_only|guest_only|both
		 */
		private static function get_site_voting_mode( $content_types ) {
			$requires_login = 0;
			$allows_guests  = 0;

			foreach ( (array) $content_types as $type ) {
				if ( self::type_requires_login( $type ) ) {
					++$requires_login;
				} else {
					++$allows_guests;
				}
			}

			if ( $allows_guests === 0 ) {
				return 'logged_in_only';
			}

			if ( $requires_login === 0 ) {
				return 'guest_only';
			}

			return 'both';
		}

		/**
		 * @param string $type posts|comments|activities|topics
		 * @return bool
		 */
		private static function type_requires_login( $type ) {
			$map = array(
				'posts'      => 'post',
				'comments'   => 'comment',
				'activities' => 'activity',
				'topics'     => 'topic',
			);

			$setting_type = isset( $map[ $type ] ) ? $map[ $type ] : $type;

			return wp_ulike_setting_repo::requireLogin( $setting_type );
		}
	}
}
