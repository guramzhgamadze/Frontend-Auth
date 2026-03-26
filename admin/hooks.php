<?php
/**
 * WP Frontend Auth – Admin Hooks
 *
 * @package WP_Frontend_Auth
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'wpfa_admin_enqueue_scripts' );

function wpfa_admin_enqueue_scripts( string $hook ): void {
    if ( 'toplevel_page_wp-frontend-auth' !== $hook ) {
        return;
    }
}

/**
 * Add a "Settings" link on the plugins list page.
 */
add_filter( 'plugin_action_links_wp-frontend-auth/wp-frontend-auth.php', 'wpfa_plugin_action_links' );

function wpfa_plugin_action_links( array $links ): array {
    array_unshift(
        $links,
        '<a href="' . esc_url( admin_url( 'admin.php?page=wp-frontend-auth' ) ) . '">'
            . esc_html__( 'Settings', 'wp-frontend-auth' )
        . '</a>'
    );
    return $links;
}

/**
 * Flush rewrite rules when slugs are saved, so new rules take effect immediately.
 */
add_action( 'update_option_wpfa_slug_login',        'wpfa_admin_flush_on_slug_change' );
add_action( 'update_option_wpfa_slug_logout',       'wpfa_admin_flush_on_slug_change' );
add_action( 'update_option_wpfa_slug_register',     'wpfa_admin_flush_on_slug_change' );
add_action( 'update_option_wpfa_slug_lostpassword', 'wpfa_admin_flush_on_slug_change' );
add_action( 'update_option_wpfa_slug_resetpass',    'wpfa_admin_flush_on_slug_change' );
add_action( 'update_option_wpfa_slug_dashboard',    'wpfa_admin_flush_on_slug_change' );
add_action( 'update_option_wpfa_use_permalinks',    'wpfa_admin_flush_on_slug_change' );

function wpfa_admin_flush_on_slug_change(): void {
    wpfa_flush_rewrite_rules();
}
