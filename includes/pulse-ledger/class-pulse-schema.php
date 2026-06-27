<?php
/**
 * Pulse Ledger — table schema and installation.
 *
 * Optimized single-table design (CTO review vs original proposal):
 * - Fewer redundant indexes (removed overlapping vote/identity keys).
 * - idx_rankings covers period leaderboards with item_id suffix for sort stability.
 * - idx_item_active is the primary item counter / EXISTS lookup path.
 * - dedupe_token UNIQUE enforces one slot per voter+item in distinct mode.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Schema' ) ) {

	final class WP_Ulike_Pulse_Schema {

		const TABLE_SUFFIX = 'ulike_pulse';

		const DB_VERSION     = '1.0';
		const VERSION_OPTION = 'wp_ulike_pulse_db_version';

		const BATCH_SIZE_DEFAULT = 500;
		const BATCH_SIZE_MIN     = 50;
		const BATCH_SIZE_MAX     = 2000;

		/**
		 * @return string Full table name.
		 */
		public static function table() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE_SUFFIX;
		}

		/**
		 * @return bool
		 */
		public static function table_exists() {
			return WP_Ulike_Pulse_Registry::table_exists( self::table() );
		}

		/**
		 * @return string CREATE TABLE SQL.
		 */
		public static function ddl() {
			$table = self::table();
			$collate = '';

			global $wpdb;
			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			return "CREATE TABLE `{$table}` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`item_id` bigint(20) unsigned NOT NULL,
				`item_type` varchar(32) NOT NULL,
				`engagement_kind` varchar(20) NOT NULL DEFAULT 'vote',
				`engagement_key` varchar(20) NOT NULL DEFAULT 'like',
				`value` tinyint(3) unsigned DEFAULT NULL,
				`status` varchar(10) NOT NULL DEFAULT 'active',
				`date_time` datetime NOT NULL,
				`ip` varchar(100) NOT NULL DEFAULT '',
				`user_id` varchar(100) NOT NULL DEFAULT '0',
				`fingerprint` varchar(64) DEFAULT NULL,
				`country_code` char(2) DEFAULT NULL,
				`device` varchar(50) DEFAULT NULL,
				`os` varchar(50) DEFAULT NULL,
				`browser` varchar(50) DEFAULT NULL,
				`dedupe_token` char(64) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `idx_dedupe` (`dedupe_token`),
				KEY `idx_item_active` (`item_type`,`item_id`,`engagement_kind`,`engagement_key`,`status`),
				KEY `idx_user_vote` (`user_id`(50),`item_type`,`item_id`,`engagement_kind`),
				KEY `idx_rankings` (`item_type`,`engagement_kind`,`engagement_key`,`status`,`date_time`,`item_id`),
				KEY `idx_fingerprint` (`item_type`,`item_id`,`fingerprint`),
				KEY `idx_country_date` (`country_code`,`date_time`),
				KEY `idx_device_date` (`device`,`date_time`)
			) {$collate};";
		}

		/**
		 * Create or verify pulse table.
		 *
		 * @return bool
		 */
		public static function install() {
			if ( self::table_exists() ) {
				return true;
			}

			if ( ! function_exists( 'maybe_create_table' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			$created = maybe_create_table( self::table(), self::ddl() );

			if ( ! self::table_exists() ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					global $wpdb;
					error_log( 'WP ULike Pulse: failed to create table ' . self::table() . ' — ' . $wpdb->last_error );
				}
				return false;
			}

			update_option( self::VERSION_OPTION, self::DB_VERSION, false );
			return (bool) $created;
		}

		/**
		 * Create table on any request when missing (idempotent self-heal).
		 *
		 * @return void
		 */
		public static function ensure_installed() {
			if ( self::table_exists() ) {
				return;
			}

			if ( ! class_exists( 'wp_ulike_activator' ) ) {
				require_once WP_ULIKE_INC_DIR . '/classes/class-wp-ulike-activator.php';
			}

			wp_ulike_activator::get_instance()->ensure_pulse_schema( false );
		}

		/**
		 * Bootstrap storage mode after schema exists.
		 *
		 * @param bool $is_fresh_install No prior wp_ulike_dbVersion.
		 * @return void
		 */
		public static function bootstrap_mode( $is_fresh_install ) {
			if ( ! self::table_exists() ) {
				return;
			}

			$config = WP_Ulike_Pulse_Config::get();
			if ( self::MODE_ALREADY_SET === self::detect_existing_mode( $config ) ) {
				return;
			}

			if ( $is_fresh_install || ! WP_Ulike_Pulse_Registry::site_has_legacy_rows() ) {
				WP_Ulike_Pulse_Config::init_fresh();
				return;
			}

			WP_Ulike_Pulse_Config::init_dual();
		}

		const MODE_ALREADY_SET = 'set';

		/**
		 * @param array<string,mixed> $config Stored config.
		 * @return string
		 */
		private static function detect_existing_mode( $config ) {
			if ( ! empty( $config['mode'] ) && WP_Ulike_Pulse_Config::MODE_LEGACY !== $config['mode'] ) {
				return self::MODE_ALREADY_SET;
			}

			if ( ! empty( $config['dual_since'] ) ) {
				return self::MODE_ALREADY_SET;
			}

			return 'unset';
		}

		/**
		 * Build dedupe token for distinct-mode votes.
		 *
		 * @param int|string $item_id   Item ID.
		 * @param string     $item_type Canonical type.
		 * @param string     $user_id   Voter identity.
		 * @param string     $kind      Engagement kind.
		 * @param string     $key       Engagement key.
		 * @return string|null Null when append-only logging.
		 */
		public static function dedupe_token( $item_id, $item_type, $user_id, $kind = 'vote', $key = 'like' ) {
			$item_id   = absint( $item_id );
			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
			$user_id   = (string) $user_id;
			$kind      = sanitize_key( $kind );
			$key       = sanitize_key( $key );

			if ( ! $item_id || '' === $user_id ) {
				return null;
			}

			return hash( 'sha256', implode( '|', array( $item_type, $item_id, $user_id, $kind, $key ) ) );
		}
	}
}
