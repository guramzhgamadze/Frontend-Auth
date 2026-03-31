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
 * SECURITY FIX (v1.4.5):
 * The previous implementation trusted HTTP_CF_CONNECTING_IP and
 * HTTP_X_FORWARDED_FOR unconditionally. Both headers are set by the CLIENT
 * and can be forged freely on any server not sitting behind a verified proxy.
 * An attacker could send "X-Forwarded-For: 1.2.3.4" with each request to
 * rotate their apparent IP, completely bypassing rate limiting.
 *
 * Fix: default to REMOTE_ADDR only, which is the actual TCP connection IP
 * and cannot be forged. Site admins running behind Cloudflare or a trusted
 * reverse proxy can use the 'wpfa_rate_limit_ip_headers' filter to add
 * additional headers — but ONLY after verifying that REMOTE_ADDR belongs to
 * the trusted proxy infrastructure.
 *
 * Source: developer.wordpress.org/reference/functions/wp_get_server_protocol/
 *         OWASP IP Address Spoofing via HTTP Headers
 *
 * @return string
 */
function wpfa_rate_limit_get_ip() {
    $ip = '';

    /**
     * Filter the list of $_SERVER headers checked for the client IP.
     *
     * IMPORTANT: only add forwarded-for headers when you have verified that
     * REMOTE_ADDR belongs to a trusted proxy (e.g. Cloudflare's published IP
     * ranges). Without that verification, any client can forge these headers
     * to bypass rate limiting.
     *
     * Example for Cloudflare sites (check REMOTE_ADDR first!):
     *   add_filter( 'wpfa_rate_limit_ip_headers', function( $headers ) {
     *       // Verify REMOTE_ADDR is a Cloudflare IP before trusting CF header.
     *       $cf_ranges = [ '103.21.244.0/22', '103.22.200.0/22', /* ... *\/ ];
     *       // ... validate REMOTE_ADDR against $cf_ranges, then:
     *       return [ 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR' ];
     *   } );
     *
     * @param string[] $headers Ordered list of $_SERVER keys to try.
     */
    $headers = (array) apply_filters( 'wpfa_rate_limit_ip_headers', [ 'REMOTE_ADDR' ] );

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
