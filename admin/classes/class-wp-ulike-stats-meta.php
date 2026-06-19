<?php
/**
 * Stats bootstrap meta for the React admin panel.
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
		 * Combined stats meta for React admin bootstrap.
		 *
		 * @param array $content_types Active stats content types.
		 * @return array{voting_mode:string,dislikes_enabled:bool}
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

			$dislikes_enabled = false;

			foreach ( $types as $type ) {
				if ( self::content_type_supports_dislikes( $type ) ) {
					$dislikes_enabled = true;
					break;
				}
			}

			$cache[ $cache_key ] = array(
				'voting_mode'      => self::get_site_voting_mode( $types ),
				'dislikes_enabled' => $dislikes_enabled,
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
		private static function content_type_supports_dislikes( $type ) {
			$template = self::get_content_type_template( $type );

			if ( empty( $template ) ) {
				return false;
			}

			$templates = wp_ulike_generate_templates_list();

			if ( ! isset( $templates[ $template ] ) ) {
				return false;
			}

			$template_data = $templates[ $template ];

			return ! empty( $template_data['is_percentage_support'] ) || ! empty( $template_data['has_subtotal'] );
		}

		/**
		 * @param string $type posts|comments|activities|topics
		 * @return string
		 */
		private static function get_content_type_template( $type ) {
			$map = array(
				'posts'      => 'post',
				'comments'   => 'comment',
				'activities' => 'activity',
				'topics'     => 'topic',
			);

			$setting_type = isset( $map[ $type ] ) ? $map[ $type ] : $type;
			$setting_key  = wp_ulike_setting_type::get_instance( $setting_type )->getSettingKey();
			$template     = wp_ulike_get_option( $setting_key . '|template', 'wpulike-default' );

			return is_string( $template ) && $template ? $template : 'wpulike-default';
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
