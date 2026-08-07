<?php
/**
 * WP ULike Activator — installs meta + pulse storage.
 *
 * // @echo HEADER
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wp_ulike_activator {

	const OPTION_INSTALL_ERRORS = 'wp_ulike_db_install_errors';

	protected static $instance = null;

	public function activate() {
		$this->install_tables( false === get_option( 'wp_ulike_dbVersion', false ) );
	}

	/**
	 * Create missing meta + pulse tables. Call from activate, repair, or DB upgrade only.
	 *
	 * @param bool $is_fresh_install Treat as brand-new site (pulse-only mode).
	 * @param bool $set_db_version   Update wp_ulike_dbVersion when appropriate.
	 * @return bool
	 */
	public function install_tables( $is_fresh_install = false, $set_db_version = true ) {
		global $wpdb;

		$errors = array();

		if ( ! WP_Ulike_Meta_Schema::install() ) {
			$errors['meta'] = $wpdb->last_error ? $wpdb->last_error : 'Could not create the meta table.';
		}

		if ( ! WP_Ulike_Pulse_Schema::install() ) {
			$errors['pulse'] = $wpdb->last_error ? $wpdb->last_error : 'Could not create the pulse table.';
		}

		if ( $errors ) {
			update_option( self::OPTION_INSTALL_ERRORS, $errors, false );
		} else {
			delete_option( self::OPTION_INSTALL_ERRORS );
		}

		if ( WP_Ulike_Pulse_Schema::table_exists() ) {
			WP_Ulike_Pulse_Schema::bootstrap_mode( $is_fresh_install );
		}

		if ( ! $errors && $set_db_version && ( $is_fresh_install || false === get_option( 'wp_ulike_dbVersion', false ) ) ) {
			update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
		}

		return empty( $errors );
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_install_errors() {
		$errors = get_option( self::OPTION_INSTALL_ERRORS, array() );
		return is_array( $errors ) ? $errors : array();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
