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

	protected static $instance = null;

	public function activate() {
		$this->install_tables( false === get_option( 'wp_ulike_dbVersion', false ) );
	}

	/**
	 * Create missing meta + pulse tables and bootstrap storage mode.
	 *
	 * @param bool $is_fresh_install Treat as brand-new site (pulse-only mode).
	 * @param bool $set_db_version   Update wp_ulike_dbVersion when appropriate.
	 * @return bool
	 */
	public function install_tables( $is_fresh_install = false, $set_db_version = true ) {
		if ( ! WP_Ulike_Meta_Schema::install() ) {
			return false;
		}

		if ( ! WP_Ulike_Pulse_Schema::install() ) {
			return false;
		}

		WP_Ulike_Pulse_Schema::bootstrap_mode( $is_fresh_install );

		if ( $set_db_version && ( $is_fresh_install || false === get_option( 'wp_ulike_dbVersion', false ) ) ) {
			update_option( 'wp_ulike_dbVersion', WP_ULIKE_DB_VERSION );
		}

		return true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
