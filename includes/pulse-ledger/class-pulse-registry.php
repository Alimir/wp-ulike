<?php
/**
 * Pulse Ledger — entity registry and legacy source mapping.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Registry' ) ) {

	final class WP_Ulike_Pulse_Registry {

		const KIND_VOTE = 'vote';

		/**
		 * Canonical item types.
		 */
		const ITEM_POST     = 'post';
		const ITEM_COMMENT  = 'comment';
		const ITEM_ACTIVITY = 'activity';
		const ITEM_TOPIC    = 'topic';

		/**
		 * @return array<string,array<string,mixed>>
		 */
		public static function legacy_sources() {
			global $wpdb;

			return array(
				'posts' => array(
					'item_type'   => self::ITEM_POST,
					'table'       => $wpdb->prefix . 'ulike',
					'column'      => 'post_id',
					'meta_groups' => array( 'post', 'posts', 'likeThis' ),
				),
				'comments' => array(
					'item_type'   => self::ITEM_COMMENT,
					'table'       => $wpdb->prefix . 'ulike_comments',
					'column'      => 'comment_id',
					'meta_groups' => array( 'comment', 'comments', 'likeThisComment' ),
				),
				'activities' => array(
					'item_type'   => self::ITEM_ACTIVITY,
					'table'       => $wpdb->prefix . 'ulike_activities',
					'column'      => 'activity_id',
					'meta_groups' => array( 'activity', 'activities', 'buddypress', 'likeThisActivity' ),
				),
				'forums' => array(
					'item_type'   => self::ITEM_TOPIC,
					'table'       => $wpdb->prefix . 'ulike_forums',
					'column'      => 'topic_id',
					'meta_groups' => array( 'topic', 'topics', 'bbpress', 'likeThisTopic' ),
				),
			);
		}

		/**
		 * @param string $type Setting slug or canonical item type.
		 * @return string
		 */
		public static function normalize_item_type( $type ) {
			$type = sanitize_key( (string) $type );

			$map = array(
				'post'              => self::ITEM_POST,
				'posts'             => self::ITEM_POST,
				'likethis'          => self::ITEM_POST,
				'comment'           => self::ITEM_COMMENT,
				'comments'          => self::ITEM_COMMENT,
				'likethiscomment'   => self::ITEM_COMMENT,
				'activity'          => self::ITEM_ACTIVITY,
				'activities'        => self::ITEM_ACTIVITY,
				'buddypress'        => self::ITEM_ACTIVITY,
				'likethisactivity'  => self::ITEM_ACTIVITY,
				'topic'             => self::ITEM_TOPIC,
				'topics'            => self::ITEM_TOPIC,
				'bbpress'           => self::ITEM_TOPIC,
				'likethistopic'     => self::ITEM_TOPIC,
			);

			if ( isset( $map[ $type ] ) ) {
				return $map[ $type ];
			}

			return $type;
		}

		/**
		 * @param string $setting_type wp_ulike_setting_type slug.
		 * @return string
		 */
		public static function from_setting_type( $setting_type ) {
			return self::normalize_item_type( $setting_type );
		}

		/**
		 * @param string $item_type Canonical item type.
		 * @return array|null Legacy source config.
		 */
		public static function legacy_source_for_type( $item_type ) {
			$item_type = self::normalize_item_type( $item_type );

			foreach ( self::legacy_sources() as $source ) {
				if ( $source['item_type'] === $item_type ) {
					return $source;
				}
			}

			return null;
		}

		/**
		 * Prefixed legacy vote log tables (single source for cross-table queries).
		 *
		 * @return string[]
		 */
		public static function log_table_names() {
			$names = array();
			foreach ( self::legacy_sources() as $source ) {
				$names[] = $source['table'];
			}
			return $names;
		}

		/**
		 * Legacy table suffix (without prefix) for a setting slug.
		 *
		 * @param string $type Setting slug or item type.
		 * @return string|null
		 */
		public static function legacy_table_suffix( $type ) {
			$source = self::legacy_source_for_type( $type );
			if ( ! $source ) {
				return null;
			}

			global $wpdb;
			return str_replace( $wpdb->prefix, '', $source['table'] );
		}

		/**
		 * @param string $type Setting slug or item type.
		 * @return string[]
		 */
		public static function meta_groups_for_type( $type ) {
			$item_type = self::normalize_item_type( $type );

			foreach ( self::legacy_sources() as $source ) {
				if ( $source['item_type'] === $item_type ) {
					return $source['meta_groups'];
				}
			}

			return array( $item_type );
		}

		/**
		 * @param string $table Full or suffix table name.
		 * @return bool
		 */
		public static function table_exists( $table ) {
			global $wpdb;

			$full = 0 === strpos( $table, $wpdb->prefix ) ? $table : $wpdb->prefix . $table;
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) );

			return $found === $full;
		}

		/**
		 * @return bool
		 */
		public static function site_has_legacy_rows() {
			global $wpdb;

			foreach ( self::legacy_sources() as $source ) {
				if ( ! self::table_exists( $source['table'] ) ) {
					continue;
				}

				$table  = esc_sql( $source['table'] );
				$exists = (int) $wpdb->get_var( "SELECT 1 FROM `{$table}` LIMIT 1" );
				if ( $exists ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * @param string $item_type Canonical item type.
		 * @return int
		 */
		public static function count_legacy_rows( $item_type ) {
			global $wpdb;

			$source = self::legacy_source_for_type( $item_type );
			if ( ! $source || ! self::table_exists( $source['table'] ) ) {
				return 0;
			}

			$table = esc_sql( $source['table'] );
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}
	}
}
