<?php
/**
 * WP ULike Activator
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class wp_ulike_activator {

	protected static $tables, $core_pages;

	public static function activate() {
		self::install_tables();
	}

	public static function install_tables(){
		global $wpdb;

		$max_index_length = 191;
		$charset_collate  = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		if( ! function_exists('maybe_create_table') ){
			// Add one library admin function for next function
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		// Posts table
		$posts_table = $wpdb->prefix . "ulike";
		maybe_create_table( $posts_table, "CREATE TABLE IF NOT EXISTS `{$posts_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`post_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `post_id` (`post_id`),
			KEY `date_time` (`date_time`),
			KEY `user_id` (`user_id`),
			KEY `status` (`status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Comments table
		$comments_table = $wpdb->prefix . "ulike_comments";
		maybe_create_table( $comments_table, "CREATE TABLE IF NOT EXISTS `{$comments_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`comment_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `comment_id` (`comment_id`),
			KEY `date_time` (`date_time`),
			KEY `user_id` (`user_id`),
			KEY `status` (`status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Activities table
		$activities_table = $wpdb->prefix . "ulike_activities";
		maybe_create_table( $activities_table, "CREATE TABLE IF NOT EXISTS `{$activities_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`activity_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `activity_id` (`activity_id`),
			KEY `date_time` (`date_time`),
			KEY `user_id` (`user_id`),
			KEY `status` (`status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Forums table
		$forums_table = $wpdb->prefix . "ulike_forums";
		maybe_create_table( $forums_table, "CREATE TABLE IF NOT EXISTS `{$forums_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`topic_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `topic_id` (`topic_id`),
			KEY `date_time` (`date_time`),
			KEY `user_id` (`user_id`),
			KEY `status` (`status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Meta values table
		$meta_table = $wpdb->prefix . "ulike_meta";
		maybe_create_table( $meta_table, "CREATE TABLE IF NOT EXISTS `{$meta_table}` (
			`meta_id` bigint(20) unsigned NOT NULL auto_increment,
			`item_id` bigint(20) unsigned NOT NULL default '0',
			`meta_group` varchar(100) default NULL,
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY  (`meta_id`),
			KEY `item_id` (`item_id`),
			KEY `meta_group` (`meta_group`),
			KEY `meta_key` (`meta_key`($max_index_length))
		) $charset_collate AUTO_INCREMENT=1;" );

	}

	public function upgrade(){
		// Upgrade Tables
		if ( version_compare( get_option( 'wp_ulike_dbVersion', '1.6' ), WP_ULIKE_DB_VERSION, '<' ) ) {
			// Posts ugrades
			$wpdb->query( "
				ALTER TABLE $posts_table
				ADD INDEX( `post_id`, `date_time`, `user_id`, `status`),
				CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
				CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
			" );
			// Comments ugrades
			$wpdb->query( "
				ALTER TABLE $comments_table
				ADD INDEX( `comment_id`, `date_time`, `user_id`, `status`),
				CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
				CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
			" );
			// BuddyPress ugrades
			$wpdb->query( "
				ALTER TABLE $activities_table
				ADD INDEX( `activity_id`, `date_time`, `user_id`, `status`),
				CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
				CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
			" );
			// bbPress upgrades
			$wpdb->query( "
				ALTER TABLE $forums_table
				ADD INDEX( `topic_id`, `date_time`, `user_id`, `status`),
				CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
				CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
			" );
			// Update db version
			update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
		}

	}
}