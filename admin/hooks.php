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
 * When a slug option is saved:
 *  1. Update the real WP page's post_name to match the new slug.
 *  2. Flush rewrite rules so new URL takes effect immediately.
 *
 * Without step 1, wpfa_get_action_url() returns get_permalink() of the
 * real page — which still has the OLD slug. The option value is ignored.
 *
 * Hook signature: update_option_{option}( $old_value, $new_value, $option )
 */
add_action( 'update_option_wpfa_slug_login',        'wpfa_admin_on_slug_change', 10, 3 );
add_action( 'update_option_wpfa_slug_logout',       'wpfa_admin_on_slug_change', 10, 3 );
add_action( 'update_option_wpfa_slug_register',     'wpfa_admin_on_slug_change', 10, 3 );
add_action( 'update_option_wpfa_slug_lostpassword', 'wpfa_admin_on_slug_change', 10, 3 );
add_action( 'update_option_wpfa_slug_resetpass',    'wpfa_admin_on_slug_change', 10, 3 );
add_action( 'update_option_wpfa_use_permalinks',    'wpfa_admin_on_slug_change', 10, 3 );

function wpfa_admin_on_slug_change( $old_value, $new_value, $option ): void {
    // Extract the action name from the option: "wpfa_slug_lostpassword" → "lostpassword"
    $action = str_replace( 'wpfa_slug_', '', $option );

    // Update the real page's slug if one exists for this action.
    $page_id = wpfa_get_page_id( $action );
    if ( $page_id && get_post( $page_id ) instanceof WP_Post ) {
        $new_slug = sanitize_title( $new_value );
        if ( '' !== $new_slug ) {
            wp_update_post( [
                'ID'        => $page_id,
                'post_name' => $new_slug,
            ] );
        }
    }

    wpfa_flush_rewrite_rules();
}

/* -----------------------------------------------------------------------
 * Fix #11 — Enqueue editor-only CSS for the Reset Password preview wrapper.
 * Replaces hardcoded hex colours with CSS-variable-driven classes.
 * -------------------------------------------------------------------- */
add_action( 'elementor/editor/after_enqueue_styles', 'wpfa_enqueue_elementor_editor_styles' );

function wpfa_enqueue_elementor_editor_styles(): void {
    if ( ! did_action( 'elementor/loaded' ) ) {
        return;
    }
    wp_enqueue_style(
        'wp-frontend-auth-editor',
        WPFA_URL . 'assets/styles/wp-frontend-auth-editor.css',
        [],
        WPFA_VERSION
    );
}
