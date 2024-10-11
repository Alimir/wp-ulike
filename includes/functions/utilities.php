<?php
/**
 * Utilities
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'wp_ulike_set_transient' ) ) {
	/**
	 * Set/update the value of a transient.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 *
	 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
	 *                           172 characters or fewer in length.
	 * @param mixed  $value      Transient value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
	 * @return bool False if value was not set and true if value was set.
	 */
	function wp_ulike_set_transient( $transient, $value, $expiration = 0 ) {
		global $_wp_using_ext_object_cache;

		$current_using_cache = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		$result = set_transient( $transient, $value, $expiration );

		$_wp_using_ext_object_cache = $current_using_cache;

		return $result;
	}
}

if( ! function_exists( 'wp_ulike_get_transient' ) ) {
	/**
	 * Get the value of a transient.
	 *
	 * If the transient does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @return mixed Value of transient.
	 */
	function wp_ulike_get_transient( $transient ) {
		global $_wp_using_ext_object_cache;

		$current_using_cache = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		$result = get_transient( $transient );

		$_wp_using_ext_object_cache = $current_using_cache;

		return $result;
	}
}

if( ! function_exists( 'wp_ulike_delete_transient' ) ) {
	/**
	 * Delete a transient.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @return bool true if successful, false otherwise
	 */
	function wp_ulike_delete_transient( $transient ) {
		global $_wp_using_ext_object_cache;

		$current_using_cache = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		$result = delete_transient( $transient );

		$_wp_using_ext_object_cache = $current_using_cache;

		return $result;
	}
}

if( ! function_exists('wp_ulike_is_true') ){
	/**
	 * Check variable status
	 *
	 * @return void
	 */
    function wp_ulike_is_true( $var ) {
        if ( is_bool( $var ) ) {
            return $var;
        }
        if ( is_string( $var ) ){
            $var = strtolower( $var );
            if( in_array( $var, array( 'yes', 'on', 'true', 'checked' ) ) ){
                return true;
            }
        }
        if ( is_numeric( $var ) ) {
            return (bool) $var;
        }
        return false;
    }
}

if( ! function_exists('wp_ulike_is_cache_exist') ){
	/**
	 * Check cache existence
	 *
	 * @return void
	 */
	function wp_ulike_is_cache_exist(){
		$cache_exist = wp_ulike_get_option( 'cache_exist', false );
		return $cache_exist || ( defined( 'WP_CACHE' ) && WP_CACHE === true );
	}
}

if( ! function_exists( 'wp_ulike_date_i18n' ) ){
	/**
	 * Date in localized format
	 *
	 * @author       	Alimir
	 * @param           String (Date)
	 * @since           2.3
	 * @return          String
	 */
	function wp_ulike_date_i18n($date){
		return date_i18n(
			get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ),
			strtotime($date)
		);
	}
}

if( ! function_exists( 'wp_ulike_get_user_ip' ) ){
	/**
	 * Get user IP
	 *
	 * @return string
	 */
	function wp_ulike_get_user_ip(){
        $whitelist = [];
        $isUsingCloudflare = !empty(filter_input(INPUT_SERVER, 'CF-Connecting-IP'));

        if (apply_filters('wp_ulike_whip_whitelist_cloudflare', $isUsingCloudflare)) {
            $cloudflareIps = wp_ulike_get_cloudflare_ips();
            $whitelist[\Vectorface\Whip\Whip::CLOUDFLARE_HEADERS] = [\Vectorface\Whip\Whip::IPV4 => $cloudflareIps['v4']];
            if (defined('AF_INET6')) {
                $whitelist[\Vectorface\Whip\Whip::CLOUDFLARE_HEADERS][\Vectorface\Whip\Whip::IPV6] = $cloudflareIps['v6'];
            }
        }

        $whitelist = apply_filters('wp_ulike_whip_whitelist', $whitelist);
        $methods   = apply_filters('wp_ulike_whip_methods', \Vectorface\Whip\Whip::ALL_METHODS);

        $whip = new \Vectorface\Whip\Whip($methods, $whitelist);

		do_action( 'wp_ulike_whip_action', $whip );

		if (false === ($clientAddress = $whip->getValidIpAddress())) {
            $clientAddress = '127.0.0.1';
        }

		return apply_filters( 'wp_ulike_get_user_ip', $clientAddress );
	}
}

if( ! function_exists( 'wp_ulike_validate_ip' ) ){
	/**
	 * Ensures an ip address is both a valid IP and does not fall within a private network range.
	 *
	 * @param string $ip
	 * @return boolean
	 */
	function wp_ulike_validate_ip( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP ) === false ? false : true;
	}
}

if( ! function_exists( 'wp_ulike_generate_user_id' ) ){
	/**
	 * Convert IP to a integer value
	 *
	 * @author       	Alimir
	 * @param           String $user_ip
	 * @since           3.4
	 * @return          String
	 */
	function wp_ulike_generate_user_id( $user_ip ) {

		// set client identifier based on user ip
		$client_identifier = $user_ip;

		if ( wp_ulike_validate_ip( $user_ip ) ) {
			if ( filter_var( $user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false ) {
				// Convert IPv6 address to a standardized format
				$standardized_ipv6 = inet_ntop( inet_pton( $user_ip ) );
				$hash = crc32( $standardized_ipv6 );
				$client_identifier = abs( $hash % 10000000000000000 ); // Ensure it's less than 16 digits
			} else {
				$client_identifier = ip2long( $user_ip );
			}
		}

		return apply_filters( 'wp_ulike_generate_client_identifier', $client_identifier, $user_ip );
	}
}

if( ! function_exists( 'wp_ulike_get_template_between' ) ){
	/**
	 * Get template between
	 *
	 * @author       	Alimir
	 * @param           String $string
	 * @param           String $start
	 * @param           String $end
	 * @since           2.0
	 * @return			String
	 */
	function wp_ulike_get_template_between( $string, $start, $end ){
		$string 	= " ".$string;
		$ini 		= strpos($string,$start);
		if ( $ini == 0 ){
			return "";
		}
		$ini 		+= strlen($start);
		$len 		= strpos($string,$end,$ini) - $ini;

		return substr( $string, $ini, $len );
	}
}

if( ! function_exists( 'wp_ulike_put_template_between' ) ){
	/**
	 * Put template between
	 *
	 * @author       	Alimir
	 * @param           String $string
	 * @param           String $inner_string
	 * @param           String $start
	 * @param           String $end
	 * @since           2.0
	 * @return			String
	 */
	function wp_ulike_put_template_between( $string, $inner_string, $start, $end ){
		$string 	= " ".$string;
		$ini 		= strpos($string,$start);
		if ($ini == 0){
			return "";
		}

		$ini 		+= strlen($start);
		$len		= strpos($string,$end,$ini) - $ini;
		$newstr 	= substr_replace($string,$inner_string,$ini,$len);

		return str_replace(
			array( "%START_WHILE%", "%END_WHILE%" ),
			array( "", "" ),
			$newstr
		);
	}
}

if( ! function_exists('wp_ulike_get_period_limit_sql') ){
    /**
     * Get period limit as a sql string
     *
     * @param string|array $date_range
     * @return string
     */
    function wp_ulike_get_period_limit_sql( $date_range ){
		global $wpdb;
        $period_limit = '';

        if( is_array( $date_range ) ){
            if( isset( $date_range['interval_value'] ) ){
                // Interval time
				$unit = empty( $date_range['interval_unit'] ) ? 'DAY' : esc_sql( $date_range['interval_unit'] );
                $period_limit = $wpdb->prepare(
                    " AND date_time >= DATE_ADD( NOW(), INTERVAL - %d {$unit})",
                    $date_range['interval_value']
                );
            } elseif( isset( $date_range['start'] ) ){
                // Start/End time
                if( $date_range['start'] === $date_range['end'] ){
                    $period_limit = $wpdb->prepare( ' AND DATE(`date_time`) = %s', $date_range['start'] );
                } else {
                    $period_limit = $wpdb->prepare(
                        ' AND DATE(`date_time`) >= %s AND DATE(`date_time`) <= %s',
                        $date_range['start'],
                        $date_range['end']
                    );
                }
            }
        } elseif( !empty( $date_range )) {
            switch ($date_range) {
                case "today":
                    $period_limit = " AND DATE(date_time) = DATE(NOW())";
                    break;
                case "yesterday":
                    $period_limit = " AND DATE(date_time) = DATE(subdate(current_date, 1))";
                    break;
                case "day_before_yesterday":
                    $period_limit = " AND DATE(date_time) = DATE(subdate(current_date, 2))";
                    break;
                case "week":
                    $period_limit = " AND WEEK(DATE(date_time), 1) = WEEK(DATE(NOW()), 1) AND YEAR(DATE(date_time)) = YEAR(DATE(NOW()))";
                    break;
                case "last_week":
                    $period_limit = " AND WEEK(DATE(date_time), 1) = ( WEEK(DATE(NOW()), 1) - 1 ) AND YEAR(DATE(date_time)) = YEAR(DATE(NOW()))";
                    break;
                case "month":
                    $period_limit = " AND MONTH(DATE(date_time)) = MONTH(DATE(NOW())) AND YEAR(DATE(date_time)) = YEAR(DATE(NOW()))";
                    break;
                case "last_month":
                    $period_limit = " AND MONTH(DATE(date_time)) = MONTH(DATE(NOW()) - INTERVAL 1 MONTH) AND YEAR(DATE(date_time)) = YEAR(DATE(NOW()) - INTERVAL 1 MONTH)";
                    break;
                case "year":
                    $period_limit = " AND YEAR(DATE(date_time)) = YEAR(DATE(NOW()))";
                    break;
                case "last_year":
                    $period_limit = " AND YEAR(DATE(date_time)) = YEAR(DATE(NOW()) - INTERVAL 1 YEAR)";
                    break;
            }
        }

        return apply_filters( 'wp_ulike_period_limit_sql', $period_limit, $date_range );
    }
}


if( ! function_exists('wp_ulike_get_cloudflare_ips') ){
	/**
	 * Get cloudflare ips
	 *
	 * @return array
	 */
    function wp_ulike_get_cloudflare_ips(){
        if (false === ($ipAddresses = get_transient('wp_ulike_cloudflare_ips'))) {
            $ipAddresses = array_fill_keys(['v4', 'v6'], []);
            foreach (array_keys($ipAddresses) as $version) {
                $url = 'https://www.cloudflare.com/ips-'.$version;
                $response = wp_remote_get($url, ['sslverify' => false]);
                if (is_wp_error($response)) {
                    continue;
                }
                if ('200' != ($statusCode = wp_remote_retrieve_response_code($response))) {
                    continue;
                }
                $ipAddresses[$version] = array_filter(
                    (array) preg_split('/\R/', wp_remote_retrieve_body($response))
                );
            }
            set_transient('wp_ulike_cloudflare_ips', $ipAddresses, WEEK_IN_SECONDS);
        }
        return $ipAddresses;
    }
}

if( ! function_exists('wp_ulike_site_is_https') ){
	/**
	 * Check if the home URL is https. If it is, we don't need to do things such as 'force ssl'.
	 *
	 * @return bool
	 */
	function wp_ulike_site_is_https() {
		return false !== strstr( get_option( 'home' ), 'https:' );
	}
}

if( ! function_exists('wp_ulike_setcookie') ){
	/**
	 * Set a cookie - wrapper for setcookie using WP constants.
	 *
	 * @param  string  $name   Name of the cookie being set.
	 * @param  string  $value  Value of the cookie.
	 * @param  integer $expire Expiry of the cookie.
	 * @param  bool    $secure Whether the cookie should be served only over https.
	 * @param  bool    $httponly Whether the cookie is only accessible over HTTP, not scripting languages like JavaScript. @since 3.6.0.
	 */
	function wp_ulike_setcookie( $name, $value, $expire = 0, $secure = false, $httponly = false ) {
		if ( ! apply_filters( 'wp_ulike_set_cookie_enabled', true, $name ,$value, $expire, $secure ) ) {
			return;
		}
		if ( ! headers_sent() ) {
			$options = apply_filters(
				'wp_ulike_set_cookie_options',
				array(
					'expires'  => $expire,
					'secure'   => $secure,
					'path'     => '/',
					'domain'   => COOKIE_DOMAIN,
					'httponly' => apply_filters( 'wp_ulike_cookie_httponly', $httponly, $name, $value, $expire, $secure ),
				),
				$name,
				$value
			);

			if ( version_compare( PHP_VERSION, '7.3.0', '>=' ) ) {
				setcookie( $name, $value, $options );
			} else {
				setcookie( $name, $value, $options['expires'], $options['path'], $options['domain'], $options['secure'], $options['httponly'] );
			}
		} elseif ( defined('WP_DEBUG') && WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
		}
	}
}

if( ! function_exists('wp_ulike_maybe_define_constant') ){
	/**
	 * Define a constant if it is not already defined.
	 *
	 * @since 3.0.0
	 * @param string $name  Constant name.
	 * @param mixed  $value Value.
	 */
	function wp_ulike_maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}

if( ! function_exists('wp_ulike_validate_x_wp_nonce') ){
	/**
	 * Validate wp nonce in header request
	 *
	 * @param string $action
	 * @return boolean
	 */
	function wp_ulike_validate_x_wp_nonce( $action = 'wp_ulike_nonce_action' ) {
		// Get all headers
		$headers = getallheaders();

		// Check if the X-WP-Nonce header is set
		if (isset($headers['X-WP-Nonce'])) {
			$nonce = $headers['X-WP-Nonce'];

			// Verify the nonce
			if (!wp_verify_nonce($nonce, $action)) {
				return false;
			}

			return true; // Nonce is valid
		} else {
			return false;
		}
	}
}