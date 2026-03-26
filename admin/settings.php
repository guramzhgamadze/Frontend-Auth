<?php
/**
 * WP Frontend Auth – Admin Settings
 *
 * FIX: Added 'wpfa_use_permalinks' setting which was read but never
 * registered or rendered, making it impossible to toggle from the UI.
 *
 * @package WP_Frontend_Auth
 */

defined( 'ABSPATH' ) || exit;

/* -----------------------------------------------------------------------
 * Menu registration
 * -------------------------------------------------------------------- */
add_action( 'admin_menu', 'wpfa_admin_add_menu' );

function wpfa_admin_add_menu(): void {
    add_menu_page(
        __( 'Frontend Auth', 'wp-frontend-auth' ),      // page title
        __( 'Frontend Auth', 'wp-frontend-auth' ),       // menu title
        'manage_options',                                 // capability
        'wp-frontend-auth',                               // menu slug
        'wpfa_admin_settings_page',                       // callback
        'dashicons-lock',                                 // icon
        71                                                // position (after Users = 70)
    );
}

/* -----------------------------------------------------------------------
 * Register settings
 * -------------------------------------------------------------------- */
add_action( 'admin_init', 'wpfa_admin_register_settings' );

function wpfa_admin_register_settings(): void {

    // ----- General section
    add_settings_section(
        'wpfa_general',
        __( 'General', 'wp-frontend-auth' ),
        '__return_null',
        'wp-frontend-auth'
    );

    $general_fields = [
        'wpfa_login_type' => [
            'title'    => __( 'Login with', 'wp-frontend-auth' ),
            'callback' => 'wpfa_admin_field_login_type',
            'sanitize' => 'wpfa_sanitize_login_type',
        ],
        'wpfa_use_permalinks' => [
            'title'    => __( 'Use pretty URLs', 'wp-frontend-auth' ),
            'callback' => 'wpfa_admin_field_use_permalinks',
            'sanitize' => 'absint',
        ],
        'wpfa_use_ajax' => [
            'title'    => __( 'AJAX forms', 'wp-frontend-auth' ),
            'callback' => 'wpfa_admin_field_use_ajax',
            'sanitize' => 'absint',
        ],
        'wpfa_user_passwords' => [
            'title'    => __( 'Allow users to set own password', 'wp-frontend-auth' ),
            'callback' => 'wpfa_admin_field_user_passwords',
            'sanitize' => 'absint',
        ],
        'wpfa_auto_login' => [
            'title'    => __( 'Auto-login after registration', 'wp-frontend-auth' ),
            'callback' => 'wpfa_admin_field_auto_login',
            'sanitize' => 'absint',
        ],
        'wpfa_honeypot' => [
            'title'    => __( 'Honeypot spam protection', 'wp-frontend-auth' ),
            'callback' => 'wpfa_admin_field_honeypot',
            'sanitize' => 'absint',
        ],
    ];

    foreach ( $general_fields as $id => $field ) {
        add_settings_field( $id, $field['title'], $field['callback'], 'wp-frontend-auth', 'wpfa_general' );
        register_setting( 'wp-frontend-auth', $id, [ 'sanitize_callback' => $field['sanitize'] ] );
    }

    // ----- Rate limiting section
    add_settings_section(
        'wpfa_rate_limiting',
        __( 'Rate Limiting', 'wp-frontend-auth' ),
        function () {
            echo '<p>' . esc_html__( 'Limit the number of failed attempts per IP address before a temporary lockout.', 'wp-frontend-auth' ) . '</p>';
        },
        'wp-frontend-auth'
    );

    add_settings_field( 'wpfa_rate_limit', __( 'Max attempts', 'wp-frontend-auth' ), 'wpfa_admin_field_rate_limit', 'wp-frontend-auth', 'wpfa_rate_limiting' );
    register_setting( 'wp-frontend-auth', 'wpfa_rate_limit', [ 'sanitize_callback' => 'absint' ] );

    add_settings_field( 'wpfa_rate_limit_window', __( 'Lockout window (minutes)', 'wp-frontend-auth' ), 'wpfa_admin_field_rate_limit_window', 'wp-frontend-auth', 'wpfa_rate_limiting' );
    register_setting( 'wp-frontend-auth', 'wpfa_rate_limit_window', [ 'sanitize_callback' => 'absint' ] );

    // ----- URL slugs section
    add_settings_section(
        'wpfa_slugs',
        __( 'Page Slugs', 'wp-frontend-auth' ),
        function () {
            echo '<p>' . esc_html__( 'Customise the URL slug for each action page. Changes require saving the permalink structure (Settings → Permalinks → Save).', 'wp-frontend-auth' ) . '</p>';
        },
        'wp-frontend-auth'
    );

    $slug_actions = [ 'login', 'logout', 'register', 'lostpassword', 'resetpass', 'dashboard' ];
    foreach ( $slug_actions as $action ) {
        $option = "wpfa_slug_{$action}";
        add_settings_field(
            $option,
            sprintf( __( '%s slug', 'wp-frontend-auth' ), ucfirst( $action ) ),
            function () use ( $option, $action ) {
                $value = get_option( $option, wpfa_get_action_slug_default( $action ) );
                echo '<input type="text" class="regular-text" name="' . esc_attr( $option ) . '" value="' . esc_attr( $value ) . '">';
            },
            'wp-frontend-auth',
            'wpfa_slugs'
        );
        register_setting( 'wp-frontend-auth', $option, [ 'sanitize_callback' => 'sanitize_title' ] );
    }
}

/* -----------------------------------------------------------------------
 * Sanitize callbacks
 * -------------------------------------------------------------------- */

function wpfa_sanitize_login_type( $value ): string {
    $allowed = [ 'default', 'username', 'email' ];
    $value   = sanitize_text_field( (string) $value );
    return in_array( $value, $allowed, true ) ? $value : 'default';
}

/* -----------------------------------------------------------------------
 * Field callbacks
 * -------------------------------------------------------------------- */

function wpfa_admin_field_login_type(): void {
    $value   = get_option( 'wpfa_login_type', 'default' );
    $options = [
        'default'  => __( 'Username or Email', 'wp-frontend-auth' ),
        'username' => __( 'Username only', 'wp-frontend-auth' ),
        'email'    => __( 'Email only', 'wp-frontend-auth' ),
    ];
    echo '<select name="wpfa_login_type">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '" ' . selected( $value, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function wpfa_admin_field_use_permalinks(): void {
    global $wp_rewrite;
    $checked      = (bool) get_option( 'wpfa_use_permalinks', true );
    $has_permalinks = $wp_rewrite instanceof WP_Rewrite && $wp_rewrite->using_permalinks();
    echo '<label><input type="checkbox" name="wpfa_use_permalinks" value="1" ' . checked( $checked, true, false ) . '> '
        . esc_html__( 'Use pretty URLs (e.g. /login/ instead of ?action=login)', 'wp-frontend-auth' ) . '</label>';
    if ( ! $has_permalinks ) {
        echo '<p class="description">' . esc_html__( 'WordPress permalinks are currently set to "Plain". Enable a permalink structure under Settings → Permalinks to use pretty URLs.', 'wp-frontend-auth' ) . '</p>';
    }
}

function wpfa_admin_field_use_ajax(): void {
    $checked = (bool) get_option( 'wpfa_use_ajax', false );
    echo '<label><input type="checkbox" name="wpfa_use_ajax" value="1" ' . checked( $checked, true, false ) . '> '
        . esc_html__( 'Submit forms without a page reload', 'wp-frontend-auth' ) . '</label>';
}

function wpfa_admin_field_user_passwords(): void {
    $checked = (bool) get_option( 'wpfa_user_passwords', false );
    echo '<label><input type="checkbox" name="wpfa_user_passwords" value="1" ' . checked( $checked, true, false ) . '> '
        . esc_html__( 'Show a password field on the registration form', 'wp-frontend-auth' ) . '</label>';
}

function wpfa_admin_field_auto_login(): void {
    $checked = (bool) get_option( 'wpfa_auto_login', false );
    echo '<label><input type="checkbox" name="wpfa_auto_login" value="1" ' . checked( $checked, true, false ) . '> '
        . esc_html__( 'Automatically log users in after they register', 'wp-frontend-auth' ) . '</label>';
}

function wpfa_admin_field_honeypot(): void {
    $checked = (bool) get_option( 'wpfa_honeypot', true );
    echo '<label><input type="checkbox" name="wpfa_honeypot" value="1" ' . checked( $checked, true, false ) . '> '
        . esc_html__( 'Add a hidden honeypot field to forms to catch bots', 'wp-frontend-auth' ) . '</label>';
}

function wpfa_admin_field_rate_limit(): void {
    $value = (int) get_option( 'wpfa_rate_limit', 10 );
    echo '<input type="number" name="wpfa_rate_limit" value="' . esc_attr( $value ) . '" min="0" max="100" class="small-text">';
    echo '<p class="description">' . esc_html__( 'Set to 0 to disable rate limiting.', 'wp-frontend-auth' ) . '</p>';
}

function wpfa_admin_field_rate_limit_window(): void {
    $value = (int) get_option( 'wpfa_rate_limit_window', 15 );
    echo '<input type="number" name="wpfa_rate_limit_window" value="' . esc_attr( $value ) . '" min="1" max="1440" class="small-text"> '
        . esc_html__( 'minutes', 'wp-frontend-auth' );
}

/* -----------------------------------------------------------------------
 * Settings page HTML
 * -------------------------------------------------------------------- */

function wpfa_admin_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'WP Frontend Auth Settings', 'wp-frontend-auth' ); ?></h1>
        <p><?php esc_html_e( 'After changing page slugs, visit Settings → Permalinks and click Save Changes to flush the rewrite rules.', 'wp-frontend-auth' ); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wp-frontend-auth' );
            do_settings_sections( 'wp-frontend-auth' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
