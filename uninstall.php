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
			$this->drop_tables();
			$this->delete_transients();
			$this->delete_options();
			$this->delete_files();
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
			{$wpdb->prefix}ulike_meta"
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
		$wpdb->query( "DELETE from $wpdb->options WHERE option_name LIKE '_transient_wp-ulike%'" );
		$wpdb->query( "DELETE from $wpdb->options WHERE option_name LIKE '_transient_timeout_wp-ulike%'" );
		$wpdb->query( "DELETE from $wpdb->options WHERE option_name LIKE '_transient_wp_ulike%'" );
		$wpdb->query( "DELETE from $wpdb->options WHERE option_name LIKE '_transient_timeout_wp_ulike%'" );
	}

	/**
	 * Delete plugin options from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_options() {

		delete_option( 'wp_ulike_dbVersion' );
		delete_option( 'widget_wp_ulike' );
		delete_option( 'wp_ulike_settings' );
		delete_option( 'wp_ulike_use_inline_custom_css' );
		delete_option( 'wp_ulike_customize' );

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
}

new wp_ulike_uninstall();
