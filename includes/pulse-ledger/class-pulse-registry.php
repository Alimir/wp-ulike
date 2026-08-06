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

		const KIND_VOTE  = 'vote';
		const KIND_EMOJI = 'emoji';
		const KIND_STAR  = 'star';

		/**
		 * Per-request memoization cache for table_exists().
		 *
		 * @var array<string,bool>
		 */
		private static $table_exists_cache = array();

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
	 * Settings/UI profile per content type (table suffix, column, cookies, etc.).
	 *
	 * @param string $type Setting slug or canonical item type.
	 * @return array<string,string>
	 */
		public static function setting_profile( $type ) {
			$item_type = self::normalize_item_type( $type );
			$source    = self::legacy_source_for_type( $item_type );

			if ( ! $source ) {
				$source = self::legacy_source_for_type( self::ITEM_POST );
			}

			global $wpdb;

			$profiles = array(
				self::ITEM_POST => array(
					'setting' => 'posts_group',
					'key'     => '_liked',
					'cookie'  => 'liked-',
				),
				self::ITEM_COMMENT => array(
					'setting' => 'comments_group',
					'key'     => '_commentliked',
					'cookie'  => 'comment-liked-',
				),
				self::ITEM_ACTIVITY => array(
					'setting' => 'buddypress_group',
					'key'     => '_activityliked',
					'cookie'  => 'activity-liked-',
				),
				self::ITEM_TOPIC => array(
					'setting' => 'bbpress_group',
					'key'     => '_topicliked',
					'cookie'  => 'topic-liked-',
				),
			);

			$ui = isset( $profiles[ $source['item_type'] ] ) ? $profiles[ $source['item_type'] ] : $profiles[ self::ITEM_POST ];

			return array(
				'setting' => $ui['setting'],
				'table'   => str_replace( $wpdb->prefix, '', $source['table'] ),
				'column'  => $source['column'],
				'key'     => $ui['key'],
				'slug'    => $source['item_type'],
				'cookie'  => $ui['cookie'],
			);
		}

		/**
		 * Query/join metadata for wp_ulike_get_table_info().
		 *
		 * @param string $type Setting slug or canonical item type.
		 * @return array<string,string>
		 */
		public static function table_info( $type = 'post' ) {
			global $wpdb;

			$source = self::legacy_source_for_type( $type );
			if ( ! $source ) {
				$source = self::legacy_source_for_type( self::ITEM_POST );
			}

			$suffix = str_replace( $wpdb->prefix, '', $source['table'] );

			switch ( $source['item_type'] ) {
				case self::ITEM_COMMENT:
					return array(
						'table'                => $suffix,
						'column'               => $source['column'],
						'related_table'        => 'comments',
						'related_table_prefix' => $wpdb->comments,
						'related_column'       => 'comment_ID',
					);

				case self::ITEM_ACTIVITY:
					return array(
						'table'                => $suffix,
						'column'               => $source['column'],
						'related_table'        => 'bp_activity',
						'related_table_prefix' => is_multisite() ? $wpdb->base_prefix . 'bp_activity' : $wpdb->prefix . 'bp_activity',
						'related_column'       => 'id',
					);

				case self::ITEM_TOPIC:
					return array(
						'table'                => $suffix,
						'column'               => $source['column'],
						'related_table'        => 'posts',
						'related_table_prefix' => $wpdb->posts,
						'related_column'       => 'ID',
					);

				default:
					return array(
						'table'                => $suffix,
						'column'               => $source['column'],
						'related_table'        => 'posts',
						'related_table_prefix' => $wpdb->posts,
						'related_column'       => 'ID',
					);
			}
		}

		/**
		 * Admin stats content-type keys mapped to canonical item types.
		 *
		 * @return array<string,string>
		 */
		public static function stats_table_map() {
			$key_map = array(
				'posts'      => 'posts',
				'comments'   => 'comments',
				'activities' => 'activities',
				'forums'     => 'topics',
			);

			$map = array();
			foreach ( self::legacy_sources() as $slug => $source ) {
				$key         = isset( $key_map[ $slug ] ) ? $key_map[ $slug ] : $slug;
				$map[ $key ] = $source['item_type'];
			}

			return $map;
		}

		/**
		 * Reverse lookup: legacy table suffix → canonical item type slug.
		 *
		 * @param string $table_suffix Table name with or without prefix.
		 * @return string|null
		 */
		public static function type_by_table_suffix( $table_suffix ) {
			global $wpdb;

			$suffix = str_replace( $wpdb->prefix, '', $table_suffix );

			foreach ( self::legacy_sources() as $source ) {
				if ( str_replace( $wpdb->prefix, '', $source['table'] ) === $suffix ) {
					return $source['item_type'];
				}
			}

			return null;
		}

		/**
		 * Likers-list args: table suffix, column, content type slug.
		 *
		 * @param string $content_type Content type slug.
		 * @return array{0:string,1:string,2:string}|null
		 */
		public static function likers_list_config( $content_type ) {
			$profile = self::setting_profile( $content_type );

			if ( empty( $profile['table'] ) || empty( $profile['column'] ) ) {
				return null;
			}

			return array( $profile['table'], $profile['column'], $profile['slug'] );
		}

		/**
		 * Normalize a setting slug, item type, or legacy table suffix for Log Bridge.
		 *
		 * @param string $identifier Setting slug, item type, or legacy suffix.
		 * @return string|null Canonical item type.
		 */
		public static function resolve_log_identifier( $identifier ) {
			$by_suffix = self::type_by_table_suffix( $identifier );
			if ( $by_suffix ) {
				return $by_suffix;
			}

			$item_type = self::normalize_item_type( $identifier );
			return self::legacy_source_for_type( $item_type ) ? $item_type : null;
		}

		/**
		 * Legacy vote tables for Site Health when Pulse bridge is unavailable.
		 *
		 * @return array<string,string>
		 */
		public static function legacy_health_tables() {
			global $wpdb;

			$tables = array(
				'meta' => $wpdb->prefix . 'ulike_meta',
			);

			foreach ( self::legacy_sources() as $slug => $source ) {
				$tables[ $slug ] = $source['table'];
			}

		return $tables;
	}

	/**
	 * Memoized table-existence check. Table existence does not change
	 * during a request except during install/upgrade, which calls
	 * flush_table_exists_cache() after creating tables.
	 *
	 * @param string $table Full or suffix table name.
	 * @return bool
	 */
	public static function table_exists( $table ) {
		global $wpdb;

		$full = 0 === strpos( $table, $wpdb->prefix ) ? $table : $wpdb->prefix . $table;

		if ( isset( self::$table_exists_cache[ $full ] ) ) {
			return self::$table_exists_cache[ $full ];
		}

		$found  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $full ) ) );
		$exists = $found === $full;

		self::$table_exists_cache[ $full ] = $exists;
		return $exists;
	}

	/**
	 * Clear the memoized table-existence cache.
	 *
	 * Call after install/upgrade routines create or drop tables so the
	 * next table_exists() check reflects the new schema state.
	 */
	public static function flush_table_exists_cache() {
		self::$table_exists_cache = array();
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
	}
}
