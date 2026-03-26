<?php
/**
 * Plugin Name:       WP Frontend Auth
 * Description:       Secure, accessible frontend login, registration, and password recovery forms — with rate limiting, honeypot protection, AJAX support, and native Elementor widgets.
 * Version:           1.2.1
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-frontend-auth
 * Domain Path:       /languages
 * Network:           true
 */
// NOTE: No "Requires Plugins: elementor" header — this plugin works without Elementor
// for classic WP_Widget sidebar use. Elementor widgets are loaded conditionally only
// when Elementor is active. See the did_action('elementor/loaded') guard in hooks.php.

defined( 'ABSPATH' ) || exit;

define( 'WPFA_VERSION', '1.2.1' );
define( 'WPFA_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WPFA_URL',     plugin_dir_url( __FILE__ ) );

/* -----------------------------------------------------------------------
 * Translations — must run on 'init' so WP locale is finalised first.
 * -------------------------------------------------------------------- */
add_action( 'init', 'wpfa_load_textdomain', 0 );
function wpfa_load_textdomain(): void {
    load_plugin_textdomain(
        'wp-frontend-auth',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

/* -----------------------------------------------------------------------
 * Core files — always loaded
 * -------------------------------------------------------------------- */
require WPFA_PATH . 'includes/options.php';
require WPFA_PATH . 'includes/helpers.php';
require WPFA_PATH . 'includes/rate-limit.php';
require WPFA_PATH . 'includes/class-wpfa.php';
require WPFA_PATH . 'includes/class-wpfa-form.php';
require WPFA_PATH . 'includes/forms.php';
require WPFA_PATH . 'includes/handlers.php';
require WPFA_PATH . 'includes/widgets.php';
require WPFA_PATH . 'includes/hooks.php';
require WPFA_PATH . 'includes/ms-hooks.php';

/* -----------------------------------------------------------------------
 * Admin files
 * -------------------------------------------------------------------- */
if ( is_admin() ) {
    require WPFA_PATH . 'admin/settings.php';
    require WPFA_PATH . 'admin/hooks.php';
}

/* -----------------------------------------------------------------------
 * Elementor integration — loaded only when Elementor is active.
 * -------------------------------------------------------------------- */

// Register the custom widget category in the Elementor panel sidebar.
add_action( 'elementor/elements/categories_registered', 'wpfa_maybe_register_elementor_category' );
function wpfa_maybe_register_elementor_category( \Elementor\Elements_Manager $elements_manager ): void {
    if ( ! did_action( 'elementor/loaded' ) ) {
        return;
    }
    require_once WPFA_PATH . 'includes/elementor/class-wpfa-elementor-widgets.php';
    wpfa_register_elementor_category( $elements_manager );
}

// Register the widgets themselves.
add_action( 'elementor/widgets/register', 'wpfa_load_elementor_widgets' );
function wpfa_load_elementor_widgets( \Elementor\Widgets_Manager $manager ): void {
    if ( ! did_action( 'elementor/loaded' ) ) {
        return;
    }
    require_once WPFA_PATH . 'includes/elementor/class-wpfa-elementor-widgets.php';
    wpfa_register_elementor_widgets( $manager );
}

/* -----------------------------------------------------------------------
 * Activation / Deactivation
 * -------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'wpfa_activate' );
register_deactivation_hook( __FILE__, 'wpfa_deactivate' );

function wpfa_activate(): void {
    // BUG-HIGH-3 fix: use null check, not false check.
    // get_option() returns false for missing options AND for options stored as false.
    // null is never stored by add_option/update_option so === null is unambiguous.
    if ( null === get_option( 'wpfa_rate_limit', null ) ) {
        add_option( 'wpfa_rate_limit',        10      );
        add_option( 'wpfa_rate_limit_window', 15      );
        add_option( 'wpfa_use_ajax',          false   );
        add_option( 'wpfa_user_passwords',    false   );
        add_option( 'wpfa_auto_login',        false   );
        add_option( 'wpfa_honeypot',          true    );
        add_option( 'wpfa_login_type',        'default' );
        add_option( 'wpfa_use_permalinks',    true    );
    }
    update_option( 'wpfa_version', WPFA_VERSION );

    // Create real WP pages on activation so Elementor can target them.
    // See options.php: wpfa_create_action_pages()
    wpfa_create_action_pages();
    wpfa_flush_rewrite_rules();
}

function wpfa_deactivate(): void {
    wpfa_flush_rewrite_rules();
}
