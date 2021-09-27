<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class wp_ulike_blacklist_validator {
    /**
     * @return bool
     */
    public static function isValid( $args = array() )
    {
        $target = implode("\n", array_filter($args));
        $isValid = self::validateBlacklist($target);
        return apply_filters('wp_ulike_validate_blacklist', $isValid, $target, $args );
    }

    /**
     * @return string
     */
    protected static function blacklist()
    {
        return 'comments' === wp_ulike_get_option('blacklist_integration')
            ? trim(get_option( 'disallowed_keys' ))
            : trim(wp_ulike_get_option('blacklist_entries'));
    }

    /**
     * @param string $target
     * @return bool
     */
    protected static function validateBlacklist($target)
    {
        if (empty($blacklist = self::blacklist())) {
            return true;
        }
        $lines = explode("\n", $blacklist);
        foreach ((array) $lines as $line) {
            $line = trim($line);
            if (empty($line) || 256 < strlen($line)) {
                continue;
            }
            $pattern = sprintf('#%s#i', preg_quote($line, '#'));
            if (preg_match($pattern, $target)) {
                return false;
            }
        }
        return true;
    }
}