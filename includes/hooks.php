<?php
/**
 * WP Frontend Auth – Hooks
 *
 * @package WP_Frontend_Auth
 */

defined( 'ABSPATH' ) || exit;

/* -----------------------------------------------------------------------
 * Init
 * -------------------------------------------------------------------- */
add_action( 'init', 'wpfa_register_default_forms', 1 );
add_action( 'init', 'wpfa_add_rewrite_tags' );
add_action( 'init', 'wpfa_add_rewrite_rules' );

/* -----------------------------------------------------------------------
 * Classic sidebar widgets
 * -------------------------------------------------------------------- */
add_action( 'widgets_init', 'wpfa_register_widgets' );

/* -----------------------------------------------------------------------
 * Elementor assets — register only (not enqueue); Elementor pulls them
 * via get_script_depends() / get_style_depends() on each widget.
 * -------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'wpfa_register_assets', 5 );

/* -----------------------------------------------------------------------
 * Frontend assets enqueue for non-Elementor / virtual-page contexts
 * -------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'wpfa_enqueue_assets', 10 );
add_action( 'wp',                 'wpfa_remove_unneeded_head_items' );

/* -----------------------------------------------------------------------
 * Redirect logged-in users away from login/register
 * -------------------------------------------------------------------- */
add_action( 'template_redirect', 'wpfa_maybe_redirect_logged_in_user', 1 );

/* -----------------------------------------------------------------------
 * URL rewrites
 * -------------------------------------------------------------------- */
add_filter( 'site_url',         'wpfa_filter_site_url',         10, 3 );
add_filter( 'network_site_url', 'wpfa_filter_site_url',         10, 3 );
add_filter( 'login_url',        'wpfa_filter_login_url',        10, 3 );
add_filter( 'logout_url',       'wpfa_filter_logout_url',       10, 2 );
add_filter( 'lostpassword_url', 'wpfa_filter_lostpassword_url', 10, 2 );

/* -----------------------------------------------------------------------
 * Virtual page support (for non-Elementor / plain-permalink installs)
 * These are no-ops when real pages exist with the same slug.
 * -------------------------------------------------------------------- */
add_filter( 'the_posts',          'wpfa_the_posts',      10, 2 );
add_filter( 'page_template',      'wpfa_page_template',  10    );
add_filter( 'body_class',         'wpfa_body_class',     10    );
add_filter( 'get_edit_post_link', 'wpfa_no_edit_link',   10, 2 );
add_filter( 'comments_array',     'wpfa_no_comments',    10    );

/* -----------------------------------------------------------------------
 * Handler functions
 * -------------------------------------------------------------------- */

function wpfa_add_rewrite_tags(): void {
    add_rewrite_tag( '%wpfa_action%', '([^/]+)' );
}

function wpfa_add_rewrite_rules(): void {
    if ( ! wpfa_use_permalinks() ) {
        return;
    }
    foreach ( wpfa()->get_actions() as $name => $action ) {
        // Only add rewrite rules for actions that don't already have a real page.
        // If the user has a real page at /login/, WordPress's own routing handles it.
        if ( wpfa_get_page_id( $name ) ) {
            continue;
        }
        $slug = wpfa_get_action_slug( $name );
        add_rewrite_rule( $slug . '/?$', 'index.php?wpfa_action=' . $name, 'top' );
    }
}

/**
 * Register assets (CSS + JS) without enqueueing.
 * Elementor widgets declare dependencies via get_script_depends() / get_style_depends(),
 * which requires the handles to be registered before Elementor calls wp_enqueue_script().
 */
function wpfa_register_assets(): void {
    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

    wp_register_style(
        'wp-frontend-auth',
        WPFA_URL . "assets/styles/wp-frontend-auth{$suffix}.css",
        [],
        WPFA_VERSION
    );

    // Script registration: no strategy:'defer' here because Elementor widgets
    // may need the script and Elementor's own scripts are not deferred.
    // We enqueue with defer only on non-Elementor pages (see wpfa_enqueue_assets).
    wp_register_script(
        'wp-frontend-auth',
        WPFA_URL . "assets/scripts/wp-frontend-auth{$suffix}.js",
        [ 'jquery' ],
        WPFA_VERSION,
        [ 'in_footer' => true ]
    );
}

/**
 * Enqueue assets on non-Elementor WPFA pages (virtual rewrite pages).
 * On Elementor pages, Elementor pulls assets via widget dependency declarations.
 */
function wpfa_enqueue_assets(): void {
    // Never enqueue inside the Elementor editor/preview/REST context.
    if ( wpfa_is_elementor_context() ) {
        return;
    }

    if ( ! wpfa_is_wpfa_page() ) {
        return;
    }

    wp_enqueue_style( 'wp-frontend-auth' );
    wp_enqueue_script( 'wp-frontend-auth' );

    $script_data = wp_json_encode( apply_filters( 'wpfa_script_data', [
        'useAjax' => wpfa_use_ajax(),
        'action'  => wpfa_get_current_action(),
        'i18n'    => [
            'genericError'       => __( 'An error occurred. Please try again.', 'wp-frontend-auth' ),
            'show'               => __( 'Show', 'wp-frontend-auth' ),
            'hide'               => __( 'Hide', 'wp-frontend-auth' ),
            'passwordToggle'     => __( 'Toggle password visibility', 'wp-frontend-auth' ),
            'strengthVeryWeak'   => __( 'Very weak', 'wp-frontend-auth' ),
            'strengthWeak'       => __( 'Weak', 'wp-frontend-auth' ),
            'strengthGood'       => __( 'Good', 'wp-frontend-auth' ),
            'strengthStrong'     => __( 'Strong', 'wp-frontend-auth' ),
            'msgRegistered'      => __( 'Registration successful! Please check your email for login instructions.', 'wp-frontend-auth' ),
            'msgCheckEmail'      => __( 'Check your email for a link to reset your password.', 'wp-frontend-auth' ),
            'msgPasswordChanged' => __( 'Your password has been reset. You can now log in.', 'wp-frontend-auth' ),
        ],
    ] ) );

    if ( $script_data ) {
        wp_add_inline_script( 'wp-frontend-auth', 'const wpFrontendAuth = ' . $script_data . ';', 'before' );
    }

    do_action( 'login_enqueue_scripts' );
}

/**
 * Inline script data for Elementor pages — called by Elementor widget render().
 * Outputs the wpFrontendAuth config object via wp_add_inline_script on the
 * registered handle so it always precedes the script regardless of load order.
 */
function wpfa_maybe_add_inline_script(): void {
    static $done = false;
    if ( $done ) {
        return;
    }
    $done = true;

    $script_data = wp_json_encode( apply_filters( 'wpfa_script_data', [
        'useAjax' => wpfa_use_ajax(),
        'action'  => wpfa_get_current_action(),
        'i18n'    => [
            'genericError'       => __( 'An error occurred. Please try again.', 'wp-frontend-auth' ),
            'show'               => __( 'Show', 'wp-frontend-auth' ),
            'hide'               => __( 'Hide', 'wp-frontend-auth' ),
            'passwordToggle'     => __( 'Toggle password visibility', 'wp-frontend-auth' ),
            'strengthVeryWeak'   => __( 'Very weak', 'wp-frontend-auth' ),
            'strengthWeak'       => __( 'Weak', 'wp-frontend-auth' ),
            'strengthGood'       => __( 'Good', 'wp-frontend-auth' ),
            'strengthStrong'     => __( 'Strong', 'wp-frontend-auth' ),
            'msgRegistered'      => __( 'Registration successful! Please check your email for login instructions.', 'wp-frontend-auth' ),
            'msgCheckEmail'      => __( 'Check your email for a link to reset your password.', 'wp-frontend-auth' ),
            'msgPasswordChanged' => __( 'Your password has been reset. You can now log in.', 'wp-frontend-auth' ),
        ],
    ] ) );

    if ( $script_data ) {
        wp_add_inline_script( 'wp-frontend-auth', 'const wpFrontendAuth = ' . $script_data . ';', 'before' );
    }
}

function wpfa_remove_unneeded_head_items(): void {
    if ( ! wpfa_is_wpfa_page() ) {
        return;
    }
    // Only strip these on virtual pages — real pages have real content.
    if ( get_query_var( 'wpfa_action', '' ) ) {
        remove_action( 'wp_head', 'feed_links',                      2  );
        remove_action( 'wp_head', 'feed_links_extra',                3  );
        remove_action( 'wp_head', 'rsd_link',                        10 );
        remove_action( 'wp_head', 'wlwmanifest_link',                10 );
        remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
        remove_filter( 'template_redirect', 'redirect_canonical'        );
    }
}

function wpfa_maybe_redirect_logged_in_user(): void {
    if ( wpfa_is_elementor_context() ) {
        return;
    }
    if ( ! is_user_logged_in() ) {
        return;
    }
    $action = wpfa_get_current_action();
    if ( ! in_array( $action, [ 'login', 'register' ], true ) ) {
        return;
    }
    $redirect = apply_filters( 'wpfa_logged_in_redirect', admin_url() );
    wp_safe_redirect( $redirect );
    exit;
}

/* -----------------------------------------------------------------------
 * Virtual post injection — only fires when NO real page exists for the action.
 * On Elementor sites with real pages this is a no-op.
 * -------------------------------------------------------------------- */

function wpfa_the_posts( array $posts, WP_Query $wp_query ): array {
    if ( ! $wp_query->is_main_query() ) {
        return $posts;
    }
    $action = get_query_var( 'wpfa_action', '' );
    if ( empty( $action ) || ! wpfa()->get_action( $action ) ) {
        return $posts;
    }
    // If a real page exists for this action, don't inject — real page wins.
    if ( wpfa_get_page_id( $action ) ) {
        return $posts;
    }

    $post = new WP_Post( (object) [
        'ID'                => -1,
        'post_author'       => 0,
        'post_status'       => 'publish',
        'post_date'         => current_time( 'mysql' ),
        'post_date_gmt'     => current_time( 'mysql', 1 ),
        'post_modified'     => current_time( 'mysql' ),
        'post_modified_gmt' => current_time( 'mysql', 1 ),
        'post_type'         => 'page',
        'post_content'      => '',
        'post_title'        => wpfa()->get_action( $action )['title'] ?? ucfirst( $action ),
        'post_excerpt'      => '',
        'post_name'         => wpfa_get_action_slug( $action ),
        'ping_status'       => 'closed',
        'comment_status'    => 'closed',
        'filter'            => 'raw',
        'guid'              => wpfa_get_action_url( $action ),
    ] );

    return [ $post ];
}

function wpfa_page_template( string $template ): string {
    if ( ! get_query_var( 'wpfa_action', '' ) ) {
        return $template;
    }
    if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
        return $template;
    }
    if ( ABSPATH . WPINC . '/template-canvas.php' === $template ) {
        return $template;
    }
    $action     = get_query_var( 'wpfa_action', '' );
    $candidates = [
        "wp-frontend-auth-{$action}.php",
        "wpfa-{$action}.php",
        'wp-frontend-auth.php',
        'wpfa.php',
        'page.php',
    ];
    $found = locate_template( $candidates );
    return $found ?: $template;
}

function wpfa_body_class( array $classes ): array {
    if ( wpfa_is_wpfa_page() ) {
        $classes[] = 'wpfa-page';
        $action    = wpfa_get_current_action();
        if ( $action ) {
            $classes[] = 'wpfa-action-' . sanitize_html_class( $action );
        }
    }
    return $classes;
}

function wpfa_no_edit_link( $link, $post_id ) {
    return ( get_query_var( 'wpfa_action', '' ) && -1 === (int) $post_id ) ? '' : $link;
}

function wpfa_no_comments( array $comments ): array {
    return get_query_var( 'wpfa_action', '' ) ? [] : $comments;
}

/* -----------------------------------------------------------------------
 * URL filters
 * -------------------------------------------------------------------- */

function wpfa_filter_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
    if ( wpfa_is_elementor_context() ) {
        return $login_url;
    }
    global $pagenow;
    if ( 'wp-login.php' === $pagenow || is_customize_preview() ) {
        return $login_url;
    }
    if ( ! wpfa()->get_action( 'login' ) ) {
        return $login_url;
    }
    $url = wpfa_get_action_url( 'login' );
    if ( ! empty( $redirect ) ) {
        $url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
    }
    if ( $force_reauth ) {
        $url = add_query_arg( 'reauth', '1', $url );
    }
    return $url;
}

function wpfa_filter_site_url( string $url, string $path, $scheme ): string {
    global $pagenow;
    if ( wpfa_is_elementor_context() ) {
        return $url;
    }
    if ( 'wp-login.php' === $pagenow || is_customize_preview() ) {
        return $url;
    }
    $parsed = parse_url( $url );
    $base   = ! empty( $parsed['path'] ) ? basename( trim( $parsed['path'], '/' ) ) : '';
    $query  = [];
    if ( ! empty( $parsed['query'] ) ) {
        parse_str( $parsed['query'], $query );
    }
    if ( isset( $query['interim-login'] ) ) {
        return $url;
    }
    $map = [
        'wp-login.php'  => 'login',
        'wp-signup.php' => 'register',
    ];
    if ( ! isset( $map[ $base ] ) ) {
        return $url;
    }
    $action_from_query = $query['action'] ?? '';
    if ( is_array( $action_from_query ) ) {
        return $url;
    }
    $action = 'wp-login.php' === $base
        ? ( '' !== $action_from_query ? $action_from_query : 'login' )
        : $map[ $base ];

    if ( 'retrievepassword' === $action ) {
        $action = 'lostpassword';
    } elseif ( 'rp' === $action ) {
        $action = 'resetpass';
    }
    if ( ! wpfa()->get_action( $action ) ) {
        return $url;
    }
    unset( $query['action'] );
    $new_url = wpfa_get_action_url( $action, 'network_site_url' === current_filter() );
    return add_query_arg( $query, $new_url );
}

function wpfa_filter_logout_url( string $url, string $redirect ): string {
    if ( ! wpfa()->get_action( 'logout' ) ) {
        return $url;
    }
    $url = wpfa_get_action_url( 'logout' );
    if ( ! empty( $redirect ) ) {
        $url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
    }
    return wp_nonce_url( $url, 'log-out' );
}

function wpfa_filter_lostpassword_url( string $url, string $redirect ): string {
    global $pagenow;
    if ( 'wp-login.php' === $pagenow ) {
        return $url;
    }
    if ( ! wpfa()->get_action( 'lostpassword' ) ) {
        return $url;
    }
    $url = wpfa_get_action_url( 'lostpassword' );
    if ( ! empty( $redirect ) ) {
        $url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
    }
    return $url;
}
