<?php
/**
 * WP Frontend Auth – Rate Limiting
 *
 * Uses transients to track failed attempts per IP address.
 * Works on both single-site and multisite.
 *
 * @package WP_Frontend_Auth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the anonymised IP used as the transient key.
 * IPv4: last octet zeroed. IPv6: last 80 bits zeroed.
 *
 * @return string
 */
function wpfa_rate_limit_get_ip() {
    $ip = '';

    $headers = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    );

    foreach ( $headers as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
            break;
        }
    }

    // Anonymise IPv4 – zero last octet
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
        $parts    = explode( '.', $ip );
        $parts[3] = '0';
        $ip       = implode( '.', $parts );
    }

    // Anonymise IPv6 – keep only first 48 bits
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
        $expanded = inet_pton( $ip );
        if ( false !== $expanded ) {
            $bytes = str_split( $expanded );
            for ( $i = 6; $i < 16; $i++ ) {
                $bytes[ $i ] = "\x00";
            }
            $ip = inet_ntop( implode( '', $bytes ) );
        }
    }

    return $ip;
}

/**
 * Transient key for a given action and IP.
 *
 * @param string $action  e.g. 'login' | 'register'
 * @param string $ip
 * @return string
 */
function wpfa_rate_limit_key( $action, $ip = '' ) {
    if ( '' === $ip ) {
        $ip = wpfa_rate_limit_get_ip();
    }
    // Max transient key length is 172 chars; md5 keeps us safe.
    return 'wpfa_rl_' . $action . '_' . md5( $ip );
}

/**
 * Check whether the current IP is locked out for a given action.
 *
 * @param string $action
 * @return bool  true = locked out.
 */
function wpfa_rate_limit_is_locked( $action ) {
    $attempts = (int) get_transient( wpfa_rate_limit_key( $action ) );
    $limit    = wpfa_get_rate_limit();

    return $limit > 0 && $attempts >= $limit;
}

/**
 * Clear the attempt counter (e.g. after a successful login).
 *
 * @param string $action
 */
function wpfa_rate_limit_clear( $action ) {
    delete_transient( wpfa_rate_limit_key( $action ) );
}

/**
 * Return remaining seconds of the lockout, or 0 if not locked.
 *
 * WordPress does not expose transient TTL natively, so we store a
 * separate timestamp transient alongside the counter.
 *
 * @param string $action
 * @return int
 */
function wpfa_rate_limit_remaining_seconds( $action ) {
    $ts_key   = wpfa_rate_limit_key( $action ) . '_ts';
    $set_at   = (int) get_transient( $ts_key );
    if ( ! $set_at ) {
        return 0;
    }
    $window  = wpfa_get_rate_limit_window() * MINUTE_IN_SECONDS;
    $elapsed = time() - $set_at;
    return max( 0, $window - $elapsed );
}

/**
 * Like wpfa_rate_limit_record() but also stores the timestamp.
 *
 * Call this version from handlers.
 *
 * @param string $action
 * @return int  New attempt count.
 */
function wpfa_rate_limit_bump( $action ) {
    $key    = wpfa_rate_limit_key( $action );
    $ts_key = $key . '_ts';
    $window = wpfa_get_rate_limit_window() * MINUTE_IN_SECONDS;

    $attempts = (int) get_transient( $key );

    if ( 0 === $attempts ) {
        // First failure in this window – record start time
        set_transient( $ts_key, time(), $window );
    }

    $attempts++;
    set_transient( $key, $attempts, $window );

    do_action( 'wpfa_rate_limit_recorded', $action, $attempts );

    return $attempts;
}
