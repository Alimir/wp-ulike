<?php
/**
 * Handle data for the current user session.
 * Implements the wp_ulike_session abstract class.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Session handler class.
 */
class wp_ulike_session_handler extends wp_ulike_session {

	/**
	 * Cookie name used for the session.
	 *
	 * @var string cookie name
	 */
	protected $_cookie;

	/**
	 * Stores session expiry.
	 *
	 * @var string session due to expire timestamp
	 */
	protected $_session_expiring;

	/**
	 * Stores session due to expire timestamp.
	 *
	 * @var string session expiration timestamp
	 */
	protected $_session_expiration;

	/**
	 * True when the cookie exists.
	 *
	 * @var bool Based on whether a cookie exists.
	 */
	protected $_has_cookie = false;

	/**
	 * Table name for session data.
	 *
	 * @var string Custom session table name
	 */
	protected $_table;

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {
		global $wpdb;

		$this->_cookie = apply_filters( 'wp_ulike_cookie', 'wp_ulike_session_' . COOKIEHASH );
		$this->_table  = $wpdb->prefix . 'ulike_sessions';
	}

	/**
	 * Init hooks and session data.
	 *
	 * @since 3.3.0
	 */
	public function init() {
		$this->init_session_cookie();

		add_action( 'wp_ulike_set_user_cookie', array( $this, 'set_user_session_cookie' ), 10 );
		add_action( 'wp_ulike_delete_user_cookie', array( $this, 'destroy_session' ), 10 );

		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'maybe_update_nonce_user_logged_out' ), 10, 2 );
		}
	}

	/**
	 * Setup cookie and user ID.
	 *
	 * @since 3.6.0
	 */
	public function init_session_cookie() {
		$cookie = $this->get_session_cookie();

		if ( $cookie ) {
			// user ID will be an MD5 hash id this is a guest session.
			$this->_user_id            = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;
			$this->_data               = $this->get_session_data();

			if ( ! $this->is_session_cookie_valid() ) {
				$this->destroy_session();
				$this->set_session_expiration();
			}

			// If the user logs in, update session.
			if ( is_user_logged_in() && strval( get_current_user_id() ) !== $this->_user_id ) {
				$guest_session_id   = $this->_user_id;
				$this->_user_id = strval( get_current_user_id() );
				$this->_dirty       = true;
				$this->save_data( $guest_session_id );
				$this->set_user_session_cookie( true );
			}

			// Update session if its close to expiring.
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_user_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_user_id = $this->generate_user_id();
			$this->_data    = $this->get_session_data();
		}
	}

	/**
	 * Checks if session cookie is expired, or belongs to a logged out user.
	 *
	 * @return bool Whether session cookie is valid.
	 */
	private function is_session_cookie_valid() {
		// If session is expired, session cookie is invalid.
		if ( time() > $this->_session_expiration ) {
			return false;
		}

		// If user has logged out, session cookie is invalid.
		if ( ! is_user_logged_in() && ! $this->is_user_guest( $this->_user_id ) ) {
			return false;
		}

		// Session from a different user is not valid. (Although from a guest user will be valid)
		if ( is_user_logged_in() && ! $this->is_user_guest( $this->_user_id ) && strval( get_current_user_id() ) !== $this->_user_id ) {
			return false;
		}

		return true;
	}

	/**
	 * Sets the session cookie on-demand.
	 *
	 * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
	 *
	 * Warning: Cookies will only be set if this is called before the headers are sent.
	 *
	 * @param bool $set Should the session cookie be set.
	 */
	public function set_user_session_cookie( $set ) {
		if ( $set ) {
			$to_hash           = $this->_user_id . '|' . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_user_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {
				wp_ulike_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, $this->use_secure_cookie(), true );
			}
		}
	}

	/**
	 * Should the session cookie be secure?
	 *
	 * @since 3.6.0
	 * @return bool
	 */
	protected function use_secure_cookie() {
		return apply_filters( 'wp_ulike_session_use_secure_cookie', wp_ulike_site_is_https() && is_ssl() );
	}

	/**
	 * Return true if the current user has an active session, i.e. a cookie to retrieve values.
	 *
	 * @return bool
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in(); // @codingStandardsIgnoreLine.
	}

	/**
	 * Set session expiration.
	 */
	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'wp_ulike_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
		$this->_session_expiration = time() + intval( apply_filters( 'wp_ulike_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
	}

	/**
	 * Generate a unique user ID for guests, or return user ID if logged in.
	 *
	 * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
	 *
	 * @return string
	 */
	public function generate_user_id() {
		$user_id = '';

		if ( is_user_logged_in() ) {
			$user_id = strval( get_current_user_id() );
		}

		if ( empty( $user_id ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher      = new PasswordHash( 8, false );
			$user_id = 't_' . substr( md5( $hasher->get_random_bytes( 32 ) ), 2 );
		}

		return $user_id;
	}

	/**
	 * Checks if this is an auto-generated user ID.
	 *
	 * @param string|int $user_id user ID to check.
	 *
	 * @return bool Whether user ID is randomly generated.
	 */
	private function is_user_guest( $user_id ) {
		$user_id = strval( $user_id );

		if ( empty( $user_id ) ) {
			return true;
		}

		if ( 't_' === substr( $user_id, 0, 2 ) ) {
			return true;
		}

		/**
		 * Legacy checks. This is to handle sessions that were created from a previous release.
		 * Maybe we can get rid of them after a few releases.
		 */

		// Almost all random $user_ids will have some letters in it, while all actual ids will be integers.
		if ( strval( (int) $user_id ) !== $user_id ) {
			return true;
		}

		// Performance hack to potentially save a DB query, when same user as $user_id is logged in.
		if ( is_user_logged_in() && strval( get_current_user_id() ) === $user_id ) {
			return false;
		} else {
			$user = get_user_by( 'id', $user_id );

			if ( 0 === $user->ID ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Get the session cookie, if set. Otherwise return false.
	 *
	 * Session cookies without a user ID are invalid.
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false; // @codingStandardsIgnoreLine.

		if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
			return false;
		}

		list( $user_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $cookie_value );

		if ( empty( $user_id ) ) {
			return false;
		}

		// Validate hash.
		$to_hash = $user_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $user_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * Get session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_user_id, array() ) : array();
	}

	/**
	 * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
	 *
	 * @return string
	 */
	private function get_cache_prefix() {
		return wp_ulike_cache_helper::get_cache_prefix( WP_ULIKE_SESSION_CACHE_GROUP );
	}

	/**
	 * Save data and delete guest session.
	 *
	 * @param int $old_session_key session ID before user logs in.
	 */
	public function save_data( $old_session_key = 0 ) {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}ulike_sessions (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)
 					ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)",
					$this->_user_id,
					maybe_serialize( $this->_data ),
					$this->_session_expiration
				)
			);

			wp_cache_set( $this->get_cache_prefix() . $this->_user_id, $this->_data, WP_ULIKE_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
			$this->_dirty = false;
			if ( get_current_user_id() != $old_session_key && ! is_object( get_user_by( 'id', $old_session_key ) ) ) {
				$this->delete_session( $old_session_key );
			}
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		$this->delete_session( $this->_user_id );
		$this->forget_session();
	}

	/**
	 * Forget all session data without destroying it.
	 */
	public function forget_session() {
		wp_ulike_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, $this->use_secure_cookie(), true );

		$this->_data    = array();
		$this->_dirty   = false;
		$this->_user_id = $this->generate_user_id();
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce to using the user/session ID.
	 * This filter runs everything `wp_verify_nonce()` and `wp_create_nonce()` gets called.
	 *
	 * @param int    $uid    User ID.
	 * @param string $action The nonce action.
	 * @return int|string
	 */
	public function maybe_update_nonce_user_logged_out( $uid, $action ) {
		if ( wp_ulike_string_util_starts_with( $action, 'wp-ulike' ) ) {
			return $this->has_session() && $this->_user_id ? $this->_user_id : $uid;
		}

		return $uid;
	}

	/**
	 * Cleanup session data from the database and clear caches.
	 */
	public function cleanup_sessions() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) ); // @codingStandardsIgnoreLine.

		if ( class_exists( 'wp_ulike_cache_helper' ) ) {
			wp_ulike_cache_helper::invalidate_cache_group( WP_ULIKE_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * Returns the session.
	 *
	 * @param string $user_id Custo ID.
	 * @param mixed  $default Default session value.
	 * @return string|array
	 */
	public function get_session( $user_id, $default = false ) {
		global $wpdb;

		if ( defined('WP_SETUP_CONFIG') ) {
			return false;
		}

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = wp_cache_get( $this->get_cache_prefix() . $user_id, WP_ULIKE_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $user_id ) ); // @codingStandardsIgnoreLine.

			if ( is_null( $value ) ) {
				$value = $default;
			}

			$cache_duration = $this->_session_expiration - time();
			if ( 0 < $cache_duration ) {
				wp_cache_add( $this->get_cache_prefix() . $user_id, $value, WP_ULIKE_SESSION_CACHE_GROUP, $cache_duration );
			}
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param int $user_id user ID.
	 */
	public function delete_session( $user_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $user_id, WP_ULIKE_SESSION_CACHE_GROUP );

		$wpdb->delete(
			$this->_table,
			array(
				'session_key' => $user_id,
			)
		);
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @param string $user_id user ID.
	 * @param int    $timestamp Timestamp to expire the cookie.
	 */
	public function update_session_timestamp( $user_id, $timestamp ) {
		global $wpdb;

		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp,
			),
			array(
				'session_key' => $user_id,
			),
			array(
				'%d',
			)
		);
	}
}