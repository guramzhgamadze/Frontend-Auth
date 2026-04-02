<?php
/**
 * WP Frontend Auth – Uninstall
 *
 * Runs when the plugin is deleted (not just deactivated).
 * Removes all plugin options and the auto-created pages.
 *
 * MEDIUM FIX: Deactivation left database debris (pages + options).
 * uninstall.php is the correct place for cleanup — deactivation alone
 * should not delete user content (options survive deactivation by design).
 *
 * @package WP_Frontend_Auth
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Options to delete
$options = [
    'wpfa_version',
    'wpfa_rate_limit',
    'wpfa_rate_limit_window',
    'wpfa_use_ajax',
    'wpfa_user_passwords',
    'wpfa_auto_login',
    'wpfa_honeypot',
    'wpfa_login_type',
    'wpfa_use_permalinks',
    'wpfa_slug_login',
    'wpfa_slug_logout',
    'wpfa_slug_register',
    'wpfa_slug_lostpassword',
    'wpfa_slug_resetpass',
];

$page_actions = [ 'login', 'register', 'lostpassword', 'resetpass' ];

if ( is_multisite() ) {
    // Clean up every sub-site on network uninstall.
    $sites = get_sites( [ 'fields' => 'ids', 'number' => 0 ] );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        wpfa_uninstall_site( $options, $page_actions );
        restore_current_blog();
    }
} else {
    wpfa_uninstall_site( $options, $page_actions );
}

function wpfa_uninstall_site( array $options, array $page_actions ): void {
    // Delete options.
    foreach ( $options as $option ) {
        delete_option( $option );
    }
    // Delete auto-created pages and their stored IDs.
    // FIX: Only delete pages that were auto-created by the plugin (flagged with
    // _wpfa_auto_created post meta). Pages the user created manually and the
    // plugin merely "adopted" by storing their ID are left intact.
    foreach ( $page_actions as $action ) {
        $opt     = "wpfa_page_id_{$action}";
        $page_id = (int) get_option( $opt, 0 );
        if ( $page_id ) {
            if ( get_post_meta( $page_id, '_wpfa_auto_created', true ) ) {
                wp_delete_post( $page_id, true ); // true = force delete, skip trash
            }
        }
        delete_option( $opt );
    }
}
