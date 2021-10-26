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

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Other variables
	 */
	protected $tables, $database;

	public function __construct(){
		global $wpdb;

		$this->database = $wpdb;
		$this->tables   = array(
			'posts'      => $this->database->prefix . "ulike",
			'comments'   => $this->database->prefix . "ulike_comments",
			'activities' => $this->database->prefix . "ulike_activities",
			'forums'     => $this->database->prefix . "ulike_forums",
			'meta'       => $this->database->prefix . "ulike_meta"
		);
	}


	public function activate() {
		$this->install_tables();
	}

	public function install_tables(){

		$max_index_length = 191;
		$charset_collate  = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET " . $this->database->charset;
		}
		if ( ! empty( $this->database->collate ) ) {
			$charset_collate .= " COLLATE " . $this->database->collate;
		}

		if( ! function_exists('maybe_create_table') ){
			// Add one library admin function for next function
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		// Extract array to variables
		extract( $this->tables );

		// Posts table
		maybe_create_table( $posts, "CREATE TABLE IF NOT EXISTS `{$posts}` (
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
		maybe_create_table( $comments, "CREATE TABLE IF NOT EXISTS `{$comments}` (
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
		maybe_create_table( $activities, "CREATE TABLE IF NOT EXISTS `{$activities}` (
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
		maybe_create_table( $forums, "CREATE TABLE IF NOT EXISTS `{$forums}` (
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
		maybe_create_table( $meta, "CREATE TABLE IF NOT EXISTS `{$meta}` (
			`meta_id` bigint(20) unsigned NOT NULL auto_increment,
			`item_id` varchar(100) unsigned NOT NULL default '0',
			`meta_group` varchar(100) default NULL,
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY  (`meta_id`),
			KEY `item_id` (`item_id`),
			KEY `meta_group` (`meta_group`),
			KEY `meta_key` (`meta_key`($max_index_length))
		) $charset_collate AUTO_INCREMENT=1;" );

	}

	public function upgrade_0(){
		// Extract array to variables
		extract( $this->tables );

		// Posts ugrades
		$this->database->query( "
			ALTER TABLE $posts
			ADD INDEX( `post_id`, `date_time`, `user_id`, `status`),
			CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
			CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
		" );
		// Comments ugrades
		$this->database->query( "
			ALTER TABLE $comments
			ADD INDEX( `comment_id`, `date_time`, `user_id`, `status`),
			CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
			CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
		" );
		// BuddyPress ugrades
		$this->database->query( "
			ALTER TABLE $activities
			ADD INDEX( `activity_id`, `date_time`, `user_id`, `status`),
			CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
			CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
		" );
		// bbPress upgrades
		$this->database->query( "
			ALTER TABLE $forums
			ADD INDEX( `topic_id`, `date_time`, `user_id`, `status`),
			CHANGE `user_id` `user_id` VARCHAR(100) NOT NULL,
			CHANGE `ip` `ip` VARCHAR(100) NOT NULL;
		" );

		// Update db version
		update_option( 'wp_ulike_dbVersion', '2.1' );
	}

	public function upgrade_1(){
		// Extract array to variables
		extract( $this->tables );

		// Posts ugrades
		$this->database->query( "
			ALTER TABLE $meta
			CHANGE `item_id` `item_id` VARCHAR(100) NOT NULL;
		" );
		// Update db version
		update_option( 'wp_ulike_dbVersion', '2.2' );
	}


    /**
    * Return an instance of this class.
    *
    * @return    object    A single instance of this class.
    */
    public static function get_instance() {
      // If the single instance hasn't been set, set it now.
      if ( null == self::$instance ) {
        self::$instance = new self;
      }

      return self::$instance;
    }
}