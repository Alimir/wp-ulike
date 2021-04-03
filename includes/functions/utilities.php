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
		$final_ip = '127.0.0.1';

		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode(',', $_SERVER[$key]) as $ip ) {
					// trim for safety measures
					$ip = trim( $ip );
					// attempt to validate IP
					if ( wp_ulike_validate_ip( $ip ) ) {
						$final_ip = $ip;
					}
				}
			}
		}

		return $final_ip;
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
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ? false : true;
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
		if( wp_ulike_validate_ip(  $user_ip  ) ) {
		    return ip2long( $user_ip );
		} else {
			// Get non-anonymise IP address
			$user_ip    = wp_ulike_get_user_ip();
			$binary_val = '';
		    foreach ( unpack( 'C*', inet_pton( $user_ip ) ) as $byte ) {
		        $binary_val .= str_pad( decbin( $byte ), 8, "0", STR_PAD_LEFT );
		    }
		    return base_convert( ltrim( $binary_val, '0' ), 2, 10 );
		}
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
		$period_limit = '';

		if( is_array( $date_range ) && isset( $date_range['start'] ) ){
			if( $date_range['start'] === $date_range['end'] ){
				$period_limit = sprintf( 'AND DATE(`date_time`) = \'%s\'', $date_range['start'] );
			} else {
				$period_limit = sprintf( 'AND DATE(`date_time`) >= \'%s\' AND DATE(`date_time`) <= \'%s\'', $date_range['start'], $date_range['end'] );
			}
		} elseif( !empty( $date_range )) {
			switch ($date_range) {
				case "today":
					$period_limit = " AND DATE(date_time) = DATE(NOW())";
					break;
				case "yesterday":
					$period_limit = " AND DATE(date_time) = DATE(subdate(current_date, 1))";
					break;
				case "week":
					$period_limit = " AND week(DATE(date_time)) = week(DATE(NOW()))";
					break;
				case "month":
					$period_limit = " AND month(DATE(date_time)) = month(DATE(NOW()))";
					break;
				case "year":
					$period_limit = " AND year(DATE(date_time)) = year(DATE(NOW()))";
					break;
			}
		}

		return $period_limit;
	}
}