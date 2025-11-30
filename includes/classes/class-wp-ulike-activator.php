<?php
/**
 * WP ULike Activator
 *
 * Handles plugin activation and database table creation/upgrades.
 *
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class wp_ulike_activator
 *
 * @since 1.0.0
 */
class wp_ulike_activator {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Database tables array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $tables;

	/**
	 * WordPress database object.
	 *
	 * @since 1.0.0
	 * @var wpdb
	 */
	protected $database;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->database = $wpdb;
		$this->tables   = array(
			'posts'      => $this->database->prefix . 'ulike',
			'comments'   => $this->database->prefix . 'ulike_comments',
			'activities' => $this->database->prefix . 'ulike_activities',
			'forums'     => $this->database->prefix . 'ulike_forums',
			'meta'       => $this->database->prefix . 'ulike_meta',
		);
	}

	/**
	 * Activate plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		$this->install_tables();
	}

	/**
	 * Install database tables.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function install_tables() {

		$max_index_length = 191;

		$charset_collate = '';

		if ( $this->database->has_cap( 'collation' ) ) {
			$charset_collate = $this->database->get_charset_collate();
		}

		if ( ! function_exists( 'maybe_create_table' ) ) {
			// Add one library admin function for next function.
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Extract array to variables
		$posts = isset( $this->tables['posts'] ) ? $this->tables['posts'] : '';
		$comments = isset( $this->tables['comments'] ) ? $this->tables['comments'] : '';
		$activities = isset( $this->tables['activities'] ) ? $this->tables['activities'] : '';
		$forums = isset( $this->tables['forums'] ) ? $this->tables['forums'] : '';
		$meta = isset( $this->tables['meta'] ) ? $this->tables['meta'] : '';

		// Posts table
		maybe_create_table( $posts, "CREATE TABLE `{$posts}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`post_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`fingerprint` VARCHAR(64) DEFAULT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `post_id` (`post_id`),
			KEY `user_id` (`user_id`),
			KEY `date_time` (`date_time`),
			KEY `status` (`status`),
			KEY `fingerprint` (`fingerprint`),
			KEY `post_id_user_id` (`post_id`, `user_id`),
			KEY `post_id_status` (`post_id`, `status`),
			KEY `post_id_fingerprint` (`post_id`, `fingerprint`),
			KEY `user_id_status` (`user_id`, `status`),
			KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`),
			KEY `status_date_time` (`status`, `date_time`),
			KEY `post_id_date_time_user_id_status` (`post_id`, `date_time`, `user_id`, `status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Comments table
		maybe_create_table( $comments, "CREATE TABLE `{$comments}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`comment_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`fingerprint` VARCHAR(64) DEFAULT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `comment_id` (`comment_id`),
			KEY `user_id` (`user_id`),
			KEY `date_time` (`date_time`),
			KEY `status` (`status`),
			KEY `fingerprint` (`fingerprint`),
			KEY `comment_id_user_id` (`comment_id`, `user_id`),
			KEY `comment_id_status` (`comment_id`, `status`),
			KEY `comment_id_fingerprint` (`comment_id`, `fingerprint`),
			KEY `user_id_status` (`user_id`, `status`),
			KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`),
			KEY `status_date_time` (`status`, `date_time`),
			KEY `comment_id_date_time_user_id_status` (`comment_id`, `date_time`, `user_id`, `status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Activities table
		maybe_create_table( $activities, "CREATE TABLE `{$activities}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`activity_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`fingerprint` VARCHAR(64) DEFAULT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `activity_id` (`activity_id`),
			KEY `user_id` (`user_id`),
			KEY `date_time` (`date_time`),
			KEY `status` (`status`),
			KEY `fingerprint` (`fingerprint`),
			KEY `activity_id_user_id` (`activity_id`, `user_id`),
			KEY `activity_id_status` (`activity_id`, `status`),
			KEY `activity_id_fingerprint` (`activity_id`, `fingerprint`),
			KEY `user_id_status` (`user_id`, `status`),
			KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`),
			KEY `status_date_time` (`status`, `date_time`),
			KEY `activity_id_date_time_user_id_status` (`activity_id`, `date_time`, `user_id`, `status`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Forums table
		maybe_create_table( $forums, "CREATE TABLE `{$forums}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`topic_id` bigint(20) NOT NULL,
			`date_time` datetime NOT NULL,
			`ip` varchar(100) NOT NULL,
			`user_id` varchar(100) NOT NULL,
			`fingerprint` VARCHAR(64) DEFAULT NULL,
			`status` varchar(30) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `topic_id` (`topic_id`),
			KEY `user_id` (`user_id`),
			KEY `date_time` (`date_time`),
			KEY `status` (`status`),
			KEY `fingerprint` (`fingerprint`),
			KEY `topic_id_user_id` (`topic_id`, `user_id`),
			KEY `topic_id_status` (`topic_id`, `status`),
			KEY `topic_id_fingerprint` (`topic_id`, `fingerprint`),
			KEY `user_id_status` (`user_id`, `status`),
			KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`),
			KEY `status_date_time` (`status`, `date_time`),
			KEY `topic_id_date_time_user_id_status` (`topic_id`, `date_time`, `user_id`, `status`)
		) $charset_collate AUTO_INCREMENT=1;" );


		// Meta values table
		maybe_create_table( $meta, "CREATE TABLE `{$meta}` (
			`meta_id` bigint(20) unsigned NOT NULL auto_increment,
			`item_id` bigint(20) unsigned NOT NULL default '0',
			`meta_group` varchar(100) default NULL,
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY  (`meta_id`),
			KEY `item_id` (`item_id`),
			KEY `meta_key` (`meta_key`($max_index_length)),
			KEY `item_id_meta_group` (`item_id`, `meta_group`),
			KEY `meta_group_meta_key_item_id` (`meta_group`, `meta_key`, `item_id`)
		) $charset_collate AUTO_INCREMENT=1;" );

		// Update db version.
		if ( false === get_option( 'wp_ulike_dbVersion' ) ) {
			update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
		}
	}

	/**
	 * Check if a column exists on a table
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return bool
	 */
	protected function column_exists( $table_name, $column_name ) {
		$table_escaped = esc_sql( $table_name );
		$column_escaped = esc_sql( $column_name );
		$result = $this->database->get_var( $this->database->prepare(
			"SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
			DB_NAME,
			$table_escaped,
			$column_escaped
		) );
		return (int) $result > 0;
	}

	/**
	 * Execute a database query with error handling
	 *
	 * @param string $query
	 * @return bool
	 */
	protected function execute_query( $query ) {
		$result = $this->database->query( $query );
		if ( false === $result ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WP ULike: Database query failed - ' . $this->database->last_error . ' | Query: ' . $query );
			}
			return false;
		}
		return true;
	}

	/**
	 * Upgrade database to version 2.1.
	 *
	 * @since 2.1.0
	 * @return bool
	 */
	public function upgrade_0() {
		// Extract array to variables
		$posts = isset( $this->tables['posts'] ) ? $this->tables['posts'] : '';
		$comments = isset( $this->tables['comments'] ) ? $this->tables['comments'] : '';
		$activities = isset( $this->tables['activities'] ) ? $this->tables['activities'] : '';
		$forums = isset( $this->tables['forums'] ) ? $this->tables['forums'] : '';

		$success = true;

		// Posts upgrades.
		$posts_escaped = esc_sql( $posts );
		// Note: Indexes are NOT added during upgrade to avoid locking large tables.
		// Indexes will be created automatically for new installations via install_tables().
		$query = "ALTER TABLE `{$posts_escaped}` CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL, CHANGE `ip` `ip` VARCHAR(100) NOT NULL";
		if ( ! $this->execute_query( $query ) ) {
			$success = false;
		}

		// Comments upgrades.
		$comments_escaped = esc_sql( $comments );
		$query = "ALTER TABLE `{$comments_escaped}` CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL, CHANGE `ip` `ip` VARCHAR(100) NOT NULL";
		if ( ! $this->execute_query( $query ) ) {
			$success = false;
		}

		// BuddyPress upgrades.
		$activities_escaped = esc_sql( $activities );
		$query = "ALTER TABLE `{$activities_escaped}` CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL, CHANGE `ip` `ip` VARCHAR(100) NOT NULL";
		if ( ! $this->execute_query( $query ) ) {
			$success = false;
		}

		// bbPress upgrades.
		$forums_escaped = esc_sql( $forums );
		$query = "ALTER TABLE `{$forums_escaped}` CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL, CHANGE `ip` `ip` VARCHAR(100) NOT NULL";
		if ( ! $this->execute_query( $query ) ) {
			$success = false;
		}

		// Update db version only if all queries succeeded
		if ( $success ) {
			update_option( 'wp_ulike_dbVersion', '2.1' );
		}

		return $success;
	}

	/**
	 * Upgrade database to version 2.2.
	 *
	 * @since 2.2.0
	 * @return bool
	 */
	public function upgrade_1() {
		// Extract array to variables.
		$meta = isset( $this->tables['meta'] ) ? $this->tables['meta'] : '';

		// Meta table upgrades.
		$meta_escaped = esc_sql( $meta );
		// Only change column if it's not already VARCHAR(100)
		if ( $this->column_exists( $meta, 'item_id' ) ) {
			$query = "ALTER TABLE `{$meta_escaped}` CHANGE `item_id` `item_id` VARCHAR(100) NOT NULL";
			if ( ! $this->execute_query( $query ) ) {
				return false;
			}
		}

		// Update db version.
		update_option( 'wp_ulike_dbVersion', '2.2' );
		return true;
	}

	/**
	 * Upgrade database to version 2.3.
	 *
	 * @since 2.3.0
	 * @return bool
	 */
	public function upgrade_2() {
		// Extract array to variables.
		$meta = isset( $this->tables['meta'] ) ? $this->tables['meta'] : '';

		// Maybe not installed meta table.
		$this->install_tables();

		// Meta table upgrades.
		$meta_escaped = esc_sql( $meta );
		// Only change column if it exists and is not already bigint(20) unsigned
		if ( $this->column_exists( $meta, 'item_id' ) ) {
			$query = "ALTER TABLE `{$meta_escaped}` CHANGE `item_id` `item_id` bigint(20) unsigned NOT NULL";
			if ( ! $this->execute_query( $query ) ) {
				return false;
			}
		}

		// Update db version.
		update_option( 'wp_ulike_dbVersion', '2.3' );
		return true;
	}

	/**
	 * Upgrade database to version 2.4.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	public function upgrade_3() {
		// Extract table names.
		$posts      = isset( $this->tables['posts'] ) ? $this->tables['posts'] : '';
		$comments   = isset( $this->tables['comments'] ) ? $this->tables['comments'] : '';
		$activities = isset( $this->tables['activities'] ) ? $this->tables['activities'] : '';
		$forums     = isset( $this->tables['forums'] ) ? $this->tables['forums'] : '';

		$success = true;

		// Add 'fingerprint' column to posts table.
		$posts_escaped = esc_sql( $posts );
		if ( ! $this->column_exists( $posts, 'fingerprint' ) ) {
			$query = "ALTER TABLE `{$posts_escaped}` ADD COLUMN `fingerprint` VARCHAR(64) DEFAULT NULL AFTER `user_id`";
			if ( ! $this->execute_query( $query ) ) {
				$success = false;
			}
		}

		// Add 'fingerprint' column to comments table.
		$comments_escaped = esc_sql( $comments );
		if ( ! $this->column_exists( $comments, 'fingerprint' ) ) {
			$query = "ALTER TABLE `{$comments_escaped}` ADD COLUMN `fingerprint` VARCHAR(64) DEFAULT NULL AFTER `user_id`";
			if ( ! $this->execute_query( $query ) ) {
				$success = false;
			}
		}

		// Add 'fingerprint' column to activities table.
		$activities_escaped = esc_sql( $activities );
		if ( ! $this->column_exists( $activities, 'fingerprint' ) ) {
			$query = "ALTER TABLE `{$activities_escaped}` ADD COLUMN `fingerprint` VARCHAR(64) DEFAULT NULL AFTER `user_id`";
			if ( ! $this->execute_query( $query ) ) {
				$success = false;
			}
		}

		// Add 'fingerprint' column to forums table.
		$forums_escaped = esc_sql( $forums );
		if ( ! $this->column_exists( $forums, 'fingerprint' ) ) {
			$query = "ALTER TABLE `{$forums_escaped}` ADD COLUMN `fingerprint` VARCHAR(64) DEFAULT NULL AFTER `user_id`";
			if ( ! $this->execute_query( $query ) ) {
				$success = false;
			}
		}

		// Note: Indexes are NOT added during upgrade to avoid locking large tables.
		// Indexes will be created automatically for new installations via install_tables().
		// For existing installations, indexes can be added manually if needed during low-traffic periods.

		// Update db version only if all queries succeeded
		if ( $success ) {
			update_option( 'wp_ulike_dbVersion', '2.4' );
		}

		return $success;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}