<?php
/**
 * Pulse Ledger — ulike_pulse table schema only.
 *
 * @package WP_Ulike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pulse_Schema' ) ) {

	final class WP_Ulike_Pulse_Schema {

		const TABLE_SUFFIX = 'ulike_pulse';

		const BATCH_SIZE_DEFAULT = 500;
		const BATCH_SIZE_MIN     = 50;
		const BATCH_SIZE_MAX     = 2000;

		/**
		 * @return string Full pulse table name.
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
			$table   = self::table();
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
			`status` enum('active','removed') NOT NULL DEFAULT 'active',
			`date_time` datetime NOT NULL,
			`ip` varchar(45) NOT NULL DEFAULT '',
			`user_id` varchar(45) NOT NULL DEFAULT '0',
			`fingerprint` varchar(64) DEFAULT NULL,
			`country_code` char(2) DEFAULT NULL,
			`device` varchar(50) DEFAULT NULL,
			`os` varchar(50) DEFAULT NULL,
			`browser` varchar(50) DEFAULT NULL,
			`dedupe_token` binary(32) DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `idx_dedupe` (`dedupe_token`),
			KEY `idx_item_active` (`item_type`,`item_id`,`engagement_kind`,`engagement_key`,`status`),
			KEY `idx_user_vote` (`user_id`,`item_type`,`item_id`,`engagement_kind`),
			KEY `idx_rankings` (`item_type`,`engagement_kind`,`engagement_key`,`status`,`date_time`,`item_id`),
			KEY `idx_fingerprint` (`item_type`,`item_id`,`fingerprint`),
			KEY `idx_country_date` (`country_code`,`date_time`),
			KEY `idx_device_date` (`device`,`date_time`),
			KEY `idx_type_status_date` (`item_type`,`status`,`date_time`),
			KEY `idx_engager_ranking` (`engagement_kind`,`engagement_key`,`status`,`date_time`)
		) {$collate};";
		}

	/**
	 * Create ulike_pulse when missing.
	 *
	 * @return bool
	 */
	public static function install() {
		global $wpdb;

		if ( self::table_exists() ) {
			return true;
		}

		// Direct query (not maybe_create_table): follow-up SHOW TABLES clears last_error.
		$wpdb->query( self::ddl() );
		$error = trim( (string) $wpdb->last_error );

		WP_Ulike_Pulse_Registry::flush_table_exists_cache();

		if ( self::table_exists() ) {
			return true;
		}

		if ( $error ) {
			$wpdb->last_error = $error;
		}

		return false;
	}

	/**
	 * Bootstrap storage mode after pulse table exists.
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
	 * Build dedupe token for distinct-mode rows.
	 *
	 * One row per (item, identity, kind) — not per engagement_key — so
	 * like↔dislike and emoji key switches update the same row (matching
	 * legacy vote semantics and Pro emoji update-by-id).
	 *
	 * Identity is the logged-in user_id, or — for guests (user_id 0/empty) —
	 * the fingerprint. Without this, every guest voting on the same item
	 * would share one token and collapse into a single pulse row.
	 *
	 * @param int|string $item_id     Item ID.
	 * @param string     $item_type   Canonical type.
	 * @param string     $user_id     Voter identity (logged-in).
	 * @param string     $kind        Engagement kind.
	 * @param string     $key         Unused (kept for call-site BC).
	 * @param string     $fingerprint Guest fingerprint (used when user_id is 0/empty).
	 * @return string|null Null when no identity is available (cannot dedupe).
	 */
	public static function dedupe_token( $item_id, $item_type, $user_id, $kind = 'vote', $key = 'like', $fingerprint = '' ) {
		$item_id   = absint( $item_id );
		$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );
		$user_id   = (string) $user_id;
		$kind      = sanitize_key( $kind );
		unset( $key ); // Key must not be part of the token (like↔dislike / emoji switch).

		if ( ! $item_id ) {
			return null;
		}

		// Logged-in users dedupe by user_id; guests dedupe by fingerprint so
		// distinct mode does not merge every guest vote into one row.
		$identity = '';
		if ( '' !== $user_id && '0' !== $user_id ) {
			$identity = 'u:' . $user_id;
		} else {
			$fingerprint = (string) $fingerprint;
			if ( '' !== $fingerprint ) {
				$identity = 'f:' . $fingerprint;
			}
		}

		if ( '' === $identity ) {
			return null;
		}

		return hash( 'sha256', implode( '|', array( $item_type, $item_id, $identity, $kind ) ), true );
	}

	/**
	 * Stable dedupe token for one legacy log row during append-mode migration.
	 *
	 * Live append votes keep a NULL token (multiple votes allowed). Migrated
	 * history rows need a unique token so a crashed/resumed batch cannot
	 * insert the same legacy id twice via idx_dedupe.
	 *
	 * @param string $item_type Canonical item type (unique per legacy source).
	 * @param int    $legacy_id Legacy table primary key.
	 * @return string|null Binary sha256, or null when id is missing.
	 */
	public static function migration_dedupe_token( $item_type, $legacy_id ) {
		$legacy_id = absint( $legacy_id );
		if ( ! $legacy_id ) {
			return null;
		}

		$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $item_type );

		return hash( 'sha256', implode( '|', array( 'migrate', $item_type, $legacy_id ) ), true );
	}
}
}
