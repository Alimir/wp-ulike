<?php
/**
 * Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Uninstall class
 *
 * @class wp_ulike_uninstall
 * @since 1.0.0
 */
class wp_ulike_uninstall {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		if ( is_multisite() ) {
			$this->uninstall_sites();
		} else {
			$this->uninstall_site();
		}
	}

	/**
	 * Process uninstall on each sites (multisite)
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function uninstall_sites() {

		global $wpdb;

		// Save current blog ID.
		$current  = $wpdb->blogid;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

		// Create tables for each blog ID.
		foreach ( $blog_ids as $blog_id ) {

			switch_to_blog( $blog_id );
			$this->uninstall_site();

		}

		// Go back to current blog.
		switch_to_blog( $current );

	}

	/**
	 * Process uninstall on current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function uninstall_site() {
		/*
		* Only remove ALL data if WP_ULIKE_REMOVE_ALL_DATA constant is set to true in user's
		* wp-config.php. This is to prevent data loss when deleting the plugin from the backend
		* and to ensure only the site owner can perform this action.
		*/
		if ( defined( 'WP_ULIKE_REMOVE_ALL_DATA' ) && true === WP_ULIKE_REMOVE_ALL_DATA ) {
			$this->clear_scheduled_tasks();
			$this->drop_tables();
			$this->delete_transients();
			$this->delete_options();
			$this->delete_user_meta();
			$this->delete_counter_meta();
			$this->delete_files();
			$this->delete_lock_files();
		}
	}

	/**
	 * Unschedule WP-Cron and Action Scheduler jobs registered by the plugin.
	 *
	 * @since 5.2.0
	 * @access public
	 * @return void
	 */
	public function clear_scheduled_tasks() {
		$pulse_hook      = 'wp_ulike_pulse_sync_batch';
		$pulse_group     = 'wp_ulike_pulse';
		$migration_hook  = 'wp_ulike_engagement_migration_batch';
		$migration_group = 'wp_ulike_engagement_migration';

		wp_clear_scheduled_hook( $pulse_hook );
		wp_clear_scheduled_hook( $migration_hook );

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $pulse_hook, array(), $pulse_group );
			as_unschedule_all_actions( $migration_hook, array(), $migration_group );
		}
	}

	/**
	 * Drop plugin custom tables from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function drop_tables() {

		global $wpdb;

		$wpdb->query(
			"DROP TABLE IF EXISTS
			{$wpdb->prefix}ulike,
			{$wpdb->prefix}ulike_comments,
			{$wpdb->prefix}ulike_activities,
			{$wpdb->prefix}ulike_forums,
			{$wpdb->prefix}ulike_meta,
			{$wpdb->prefix}ulike_pulse,
			{$wpdb->prefix}ulike_engagements"
		);

	}

	/**
	 * Delete plugin transients from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_transients() {

		global $wpdb;

		// Delete all plugin metadata.
		$options_table = $wpdb->options;
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_wp-ulike%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_timeout_wp-ulike%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_wp_ulike%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE from `{$options_table}` WHERE option_name LIKE %s", '_transient_timeout_wp_ulike%' ) );
	}

	/**
	 * Delete plugin options from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_options() {

		global $wpdb;

		delete_option( 'widget_wp_ulike' );

		// Remove all wp_ulike_* options (free, Pro, pulse, legacy, unknown).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->options}` WHERE option_name LIKE %s",
				$wpdb->esc_like( 'wp_ulike_' ) . '%'
			)
		);
	}

	/**
	 * Delete plugin files
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_files() {

		global $wp_filesystem;

		// Get filesystem.
		if ( empty( $wp_filesystem ) ) {

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
			}

			WP_Filesystem();

		}

		$wp_content = $wp_filesystem->wp_content_dir();

		$wp_filesystem->delete( $wp_content . '/uploads/wp-ulike', true );
	}

	/**
	 * Delete activation pointer and other plugin user meta.
	 *
	 * @since 5.0.6
	 * @access public
	 * @return void
	 */
	public function delete_user_meta() {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->usermeta}` WHERE meta_key LIKE %s AND meta_key NOT LIKE %s",
				$wpdb->esc_like( 'wp_ulike_' ) . '%',
				$wpdb->esc_like( 'wp_ulike_pro_' ) . '%'
			)
		);
	}

	/**
	 * Delete legacy counter meta stored on WordPress posts and comments.
	 *
	 * BuddyPress activity counters live in BP meta tables and are not removed here.
	 *
	 * @since 5.2.0
	 * @access public
	 * @return void
	 */
	public function delete_counter_meta() {

		global $wpdb;

		$post_meta_keys = array( '_liked', '_topicliked' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$wpdb->postmeta}` WHERE meta_key IN ( %s, %s )",
				$post_meta_keys[0],
				$post_meta_keys[1]
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->commentmeta,
			array( 'meta_key' => '_commentliked' ),
			array( '%s' )
		);
	}

	/**
	 * Delete stale vote lock files from the system temp directory.
	 *
	 * Lock files use wp-ulike-{type}-{id}.lock (no blog ID). They are removed
	 * after each vote; uninstall only cleans leftovers from crashed requests.
	 *
	 * On single-site installs the temp dir is site-specific enough to glob safely.
	 * On multisite, the same temp dir may be shared — skip to avoid touching other sites.
	 *
	 * @since 5.0.5
	 * @access public
	 * @return void
	 */
	public function delete_lock_files() {

		if ( is_multisite() ) {
			return;
		}

		$pattern = trailingslashit( get_temp_dir() ) . 'wp-ulike-*.lock';
		$files   = glob( $pattern );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
}

new wp_ulike_uninstall();
