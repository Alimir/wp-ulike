<?php
/**
 * wp_ulike_cache_helper class.
 */

defined( 'ABSPATH' ) || exit;

/**
 * wp_ulike_cache_helper.
 */
class wp_ulike_cache_helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'nocache_headers', array( __CLASS__, 'additional_nocache_headers' ), 10 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ) );
	}

	/**
	 * Set additional nocache headers.
	 *
	 * @param array $headers Header names and field values.
	 * @since 3.6.0
	 */
	public static function additional_nocache_headers( $headers ) {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$set_cache = false;

		/**
		 * Allow plugins to enable nocache headers. Enabled for Google weblight.
		 *
		 * @param bool $enable_nocache_headers Flag indicating whether to add nocache headers. Default: false.
		 */
		if ( apply_filters( 'wp_ulike_enable_nocache_headers', false ) ) {
			$set_cache = true;
		}

		/**
		 * Enabled for Google weblight.
		 *
		 * @see https://support.google.com/webmasters/answer/1061943?hl=en
		 */
		if ( false !== strpos( $agent, 'googleweblight' ) ) {
			// no-transform: Opt-out of Google weblight. https://support.google.com/webmasters/answer/6211428?hl=en.
			$set_cache = true;
		}

		if ( $set_cache ) {
			$headers['Cache-Control'] = 'no-transform, no-cache, no-store, must-revalidate';
		}

		return $headers;
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key
		$prefix = wp_cache_get( 'wp_ulike_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'wp_ulike_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'wp_ulike_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 3.9.0
	 */
	public static function invalidate_cache_group( $group ) {
		wp_cache_set( 'wp_ulike_' . $group . '_cache_prefix', microtime(), $group );
	}


	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}
		$page_ids = apply_filters( 'wp_ulike_prevent_caching_pages', array() );

		if ( is_page( $page_ids ) ) {
			self::set_nocache_constants();
			nocache_headers();
		}
	}

	/**
	 * Get transient version.
	 *
	 * When using transients with unpredictable names, e.g. those containing an md5
	 * hash in the name, we need a way to invalidate them all at once.
	 *
	 * When using default WP transients we're able to do this with a DB query to
	 * delete transients manually.
	 *
	 * With external cache however, this isn't possible. Instead, this function is used
	 * to append a unique string (based on time()) to each transient. When transients
	 * are invalidated, the transient version will increment and data will be regenerated.
	 *
	 * @param  string  $group   Name for the group of transients we need to invalidate.
	 * @param  boolean $refresh true to force a new version.
	 * @return string transient version based on time(), 10 digits.
	 */
	public static function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			$transient_value = (string) time();

			set_transient( $transient_name, $transient_value );
		}

		return $transient_value;
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		wp_ulike_maybe_define_constant( 'DONOTCACHEPAGE', true );
		wp_ulike_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		wp_ulike_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

	/**
	 * Notices function.
	 */
	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance( 'W3_Config' );
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_wp_ulike_session_', $settings, true ) ) {
			?>
<div class="error">
    <p>
        <?php
				/* translators: 1: key 2: URL */
				echo wp_kses_post( sprintf( __( 'In order for <strong>database caching</strong> to work with WP ULike you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', WP_ULIKE_SLUG ), '<code>_wp_ulike_session_</code>', esc_url( admin_url( 'admin.php?page=w3tc_dbcache' ) ) ) );
				?>
    </p>
</div>
<?php
		}
	}
}