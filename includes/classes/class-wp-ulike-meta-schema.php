<?php
/**
 * WP ULike meta table schema (ulike_meta).
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Meta_Schema' ) ) {

	final class WP_Ulike_Meta_Schema {

		const META_SUFFIX = 'ulike_meta';

		/**
		 * @return string Full table name.
		 */
		public static function table() {
			global $wpdb;
			return $wpdb->prefix . self::META_SUFFIX;
		}

		/**
		 * @return bool
		 */
		public static function table_exists() {
			global $wpdb;

			$table = self::table();
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

			return $found === $table;
		}

		/**
		 * @return string
		 */
		public static function ddl() {
			global $wpdb;

			$table   = self::table();
			$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

			return "CREATE TABLE `{$table}` (
				`meta_id` bigint(20) unsigned NOT NULL auto_increment,
				`item_id` bigint(20) unsigned NOT NULL default '0',
				`meta_group` varchar(100) default NULL,
				`meta_key` varchar(255) default NULL,
				`meta_value` longtext,
				PRIMARY KEY  (`meta_id`),
				KEY `item_id` (`item_id`),
				KEY `meta_key` (`meta_key`(191)),
				KEY `item_id_meta_group` (`item_id`, `meta_group`),
				KEY `meta_group_meta_key_item_id` (`meta_group`, `meta_key`, `item_id`)
			) {$collate} AUTO_INCREMENT=1;";
		}

		/**
		 * @return bool
		 */
		public static function install() {
			if ( self::table_exists() ) {
				return true;
			}

			if ( ! function_exists( 'maybe_create_table' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			return (bool) maybe_create_table( self::table(), self::ddl() );
		}
	}
}
