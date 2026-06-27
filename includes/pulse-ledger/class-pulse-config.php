<?php
/**
 * Pulse Ledger — single source of truth for storage mode.
 *
 * Three modes replace the old profile/phase/read_mode matrix:
 *  - legacy : read + write legacy tables (pre-pulse sites only).
 *  - dual   : write pulse, read merged legacy + pulse (existing sites).
 *  - pulse  : read + write pulse only (fresh installs, post-migration).
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Config' ) ) {

	final class WP_Ulike_Pulse_Config {

		const OPTION = 'wp_ulike_pulse_config';

		const MODE_LEGACY = 'legacy';
		const MODE_DUAL   = 'dual';
		const MODE_PULSE  = 'pulse';

		const READ_LEGACY = 'legacy';
		const READ_MERGED = 'merged';
		const READ_PULSE  = 'pulse';

		/**
		 * @return array<string,mixed>
		 */
		public static function defaults() {
			return array(
				'mode'       => self::MODE_LEGACY,
				'dual_since' => '',
				'admin_ui'   => array(
					'dismissed' => false,
				),
				'migration'  => array(
					'status'  => 'idle',
					'sources' => array(),
				),
			);
		}

		/**
		 * @return array<string,mixed>
		 */
		public static function get() {
			$config = get_option( self::OPTION, array() );
			if ( ! is_array( $config ) ) {
				$config = array();
			}

			return wp_parse_args( $config, self::defaults() );
		}

		/**
		 * @param array<string,mixed> $changes Partial config.
		 * @return void
		 */
		public static function update( array $changes ) {
			$config = self::get();
			$config = array_replace_recursive( $config, $changes );
			update_option( self::OPTION, $config, false );
		}

		/**
		 * @return string legacy|dual|pulse
		 */
		public static function mode() {
			$mode = self::get()['mode'];

			if ( ! in_array( $mode, array( self::MODE_LEGACY, self::MODE_DUAL, self::MODE_PULSE ), true ) ) {
				return self::MODE_LEGACY;
			}

			if ( ! WP_Ulike_Pulse_Schema::table_exists() ) {
				return self::MODE_LEGACY;
			}

			return $mode;
		}

		/**
		 * @return string legacy|merged|pulse
		 */
		public static function read_mode() {
			switch ( self::mode() ) {
				case self::MODE_PULSE:
					return self::READ_PULSE;
				case self::MODE_DUAL:
					return self::READ_MERGED;
				default:
					return self::READ_LEGACY;
			}
		}

		/**
		 * @return bool
		 */
		public static function writes_pulse() {
			return in_array( self::mode(), array( self::MODE_DUAL, self::MODE_PULSE ), true );
		}

		/**
		 * @return string UTC datetime when dual mode began (legacy snapshot cutoff).
		 */
		public static function dual_since() {
			$since = self::get()['dual_since'];
			return is_string( $since ) ? $since : '';
		}

		/**
		 * Fresh install: pulse table only, pulse mode.
		 *
		 * @return void
		 */
		public static function init_fresh() {
			self::update(
				array(
					'mode'       => self::MODE_PULSE,
					'dual_since' => '',
					'migration'  => array(
						'status'  => 'done',
						'sources' => array(),
					),
				)
			);
		}

		/**
		 * Existing site with legacy data: enable dual mode.
		 *
		 * @return void
		 */
		public static function init_dual() {
			$since = self::dual_since();
			if ( empty( $since ) ) {
				$since = current_time( 'mysql', true );
			}

			self::update(
				array(
					'mode'       => self::MODE_DUAL,
					'dual_since' => $since,
					'migration'  => array(
						'status' => 'idle',
					),
				)
			);
		}

		/**
		 * Cut over reads to pulse after migration completes.
		 *
		 * @return void
		 */
		public static function switch_to_pulse() {
			self::update(
				array(
					'mode'       => self::MODE_PULSE,
					'migration'  => array(
						'status' => 'done',
					),
				)
			);
		}

		/**
		 * @return bool
		 */
		public static function migration_running() {
			return 'running' === self::get()['migration']['status'];
		}

		/**
		 * @return bool
		 */
		public static function needs_migration_ui() {
			if ( self::MODE_PULSE === self::mode() ) {
				return false;
			}

			if ( ! WP_Ulike_Pulse_Registry::site_has_legacy_rows() ) {
				return false;
			}

			return 'done' !== self::get()['migration']['status'];
		}

		/**
		 * Whether the Pulse Storage admin submenu should appear.
		 *
		 * @return bool
		 */
		public static function should_show_admin_menu() {
			if ( self::is_admin_dismissed() ) {
				return false;
			}

			$mode = self::mode();

			if ( self::MODE_DUAL === $mode || self::needs_migration_ui() ) {
				return true;
			}

			if ( self::MODE_PULSE === $mode && class_exists( 'WP_Ulike_Pulse_Legacy_Cleanup' ) ) {
				return WP_Ulike_Pulse_Legacy_Cleanup::legacy_tables_exist();
			}

			return false;
		}

		/**
		 * @return bool
		 */
		public static function is_admin_dismissed() {
			return ! empty( self::get()['admin_ui']['dismissed'] );
		}

		/**
		 * Hide Pulse Storage menu after migration cleanup or explicit dismiss.
		 *
		 * @return void
		 */
		public static function mark_admin_dismissed() {
			self::update(
				array(
					'admin_ui' => array(
						'dismissed' => true,
					),
				)
			);
		}
	}
}
