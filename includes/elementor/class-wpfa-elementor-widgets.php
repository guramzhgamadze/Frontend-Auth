<?php
/**
 * WP Frontend Auth – Elementor Widgets
 *
 * Registers native Elementor\Widget_Base widgets so all auth forms appear
 * in the Elementor panel and can be dragged onto any page.
 *
 * These are entirely separate from the classic WP_Widget classes in widgets.php.
 * WP_Widget = WordPress sidebar widget areas.
 * Widget_Base = Elementor panel drag-and-drop editor.
 *
 * Required per elementor-patterns.md:
 *  - get_name(), get_title(), get_icon(), get_categories(), get_keywords()
 *  - get_script_depends(), get_style_depends()
 *  - has_widget_inner_wrapper(): false  (V4 compatibility)
 *  - is_dynamic_content(): true  (auth forms are user-specific — disable caching)
 *  - register_controls()
 *  - render()
 *  - content_template()
 *
 * @package WP_Frontend_Auth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register all Elementor widgets.
 * Called from wpfa_load_elementor_widgets() in wp-frontend-auth.php.
 */
function wpfa_register_elementor_widgets( \Elementor\Widgets_Manager $manager ): void {
    $manager->register( new WPFA_Elementor_Login_Widget() );
    $manager->register( new WPFA_Elementor_Register_Widget() );
    $manager->register( new WPFA_Elementor_Lost_Password_Widget() );
    $manager->register( new WPFA_Elementor_Reset_Password_Widget() );
    $manager->register( new WPFA_Elementor_Dashboard_Widget() );
}

/* -----------------------------------------------------------------------
 * Abstract base — shared boilerplate for all five widgets
 * -------------------------------------------------------------------- */

abstract class WPFA_Elementor_Base_Widget extends \Elementor\Widget_Base {

    public function get_categories(): array {
        return [ 'general' ];
    }

    public function get_keywords(): array {
        return [ 'login', 'auth', 'register', 'password', 'wpfa', 'frontend auth' ];
    }

    /**
     * All WPFA widgets declare the shared CSS handle.
     * Elementor loads it only on pages that actually use the widget.
     */
    public function get_style_depends(): array {
        return [ 'wp-frontend-auth' ];
    }

    /**
     * All WPFA widgets declare the shared JS handle.
     */
    public function get_script_depends(): array {
        return [ 'wp-frontend-auth' ];
    }

    /**
     * V4 compatibility — remove the redundant inner wrapper div.
     * Required on all new widgets per elementor-patterns.md.
     */
    public function has_widget_inner_wrapper(): bool {
        return false;
    }

    /**
     * Auth forms output is user-specific (logged-in state, errors, nonces).
     * Disable Elementor's output caching for all WPFA widgets.
     */
    protected function is_dynamic_content(): bool {
        return true;
    }

    /**
     * Register the "redirect_to" and "show_links" controls shared by all widgets.
     */
    protected function register_common_controls(): void {
        $this->add_control( 'redirect_to', [
            'label'       => esc_html__( 'Redirect URL after success', 'wp-frontend-auth' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'Default: admin dashboard', 'wp-frontend-auth' ),
            'label_block' => true,
        ] );

        $this->add_control( 'show_links', [
            'label'        => esc_html__( 'Show action links below form', 'wp-frontend-auth' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'wp-frontend-auth' ),
            'label_off'    => esc_html__( 'No', 'wp-frontend-auth' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );
    }

    /**
     * Build render args from Elementor settings for wpfa_render_form().
     */
    protected function build_render_args( array $settings ): array {
        return [
            'show_links'  => 'yes' === ( $settings['show_links'] ?? 'yes' ),
            'redirect_to' => esc_url( $settings['redirect_to'] ?? '' ),
        ];
    }

    /**
     * Ensure the inline script data object is output once per page.
     * Elementor loads the script handle but does not call wpfa_enqueue_assets()
     * (that path is for non-Elementor pages). We inject the config object here.
     */
    protected function maybe_print_script_data(): void {
        wpfa_maybe_add_inline_script();
    }

    /**
     * Render an editor-mode placeholder when the form is not available
     * (e.g. registration disabled, user already logged in).
     */
    protected function render_editor_placeholder( string $message ): void {
        $is_editor = \Elementor\Plugin::$instance->editor
                     && \Elementor\Plugin::$instance->editor->is_edit_mode();
        if ( $is_editor ) {
            echo '<div style="padding:1.5em;background:#f0f6fc;border:1px dashed #0073aa;border-radius:4px;font-size:13px;color:#0c447c;">'
                . esc_html( $message )
                . '</div>';
        }
    }
}

/* -----------------------------------------------------------------------
 * 1. Login Widget
 * -------------------------------------------------------------------- */

class WPFA_Elementor_Login_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-login'; }
    public function get_title(): string { return esc_html__( 'Login Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-lock-user'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Login Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->register_common_controls();

        $this->add_control( 'show_dashboard', [
            'label'        => esc_html__( 'Show dashboard panel when logged in', 'wp-frontend-auth' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'wp-frontend-auth' ),
            'label_off'    => esc_html__( 'No', 'wp-frontend-auth' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->end_controls_section();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $settings = $this->get_settings_for_display();

        if ( is_user_logged_in() ) {
            if ( 'yes' === ( $settings['show_dashboard'] ?? 'yes' ) ) {
                echo wpfa_render_dashboard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return;
        }

        echo wpfa_render_form( 'login', $this->build_render_args( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    protected function content_template(): void {
        ?>
        <div class="wpfa wpfa-form wpfa-form-login">
            <p style="opacity:0.6;font-size:13px;"><?php esc_html_e( '[Login Form — visible on frontend]', 'wp-frontend-auth' ); ?></p>
        </div>
        <?php
    }
}

/* -----------------------------------------------------------------------
 * 2. Register Widget
 * -------------------------------------------------------------------- */

class WPFA_Elementor_Register_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-register'; }
    public function get_title(): string { return esc_html__( 'Registration Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-person'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Registration Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->register_common_controls();
        $this->end_controls_section();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $settings = $this->get_settings_for_display();

        if ( ! get_option( 'users_can_register' ) ) {
            $this->render_editor_placeholder( __( 'Registration Form — user registration is currently disabled in WordPress settings.', 'wp-frontend-auth' ) );
            return;
        }

        if ( is_user_logged_in() ) {
            return;
        }

        echo wpfa_render_form( 'register', $this->build_render_args( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    protected function content_template(): void {
        ?>
        <div class="wpfa wpfa-form wpfa-form-register">
            <p style="opacity:0.6;font-size:13px;"><?php esc_html_e( '[Registration Form — visible on frontend]', 'wp-frontend-auth' ); ?></p>
        </div>
        <?php
    }
}

/* -----------------------------------------------------------------------
 * 3. Lost Password Widget
 * -------------------------------------------------------------------- */

class WPFA_Elementor_Lost_Password_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-lost-password'; }
    public function get_title(): string { return esc_html__( 'Lost Password Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-email'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Lost Password Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->register_common_controls();
        $this->end_controls_section();
    }

    protected function render(): void {
        $this->maybe_print_script_data();

        if ( is_user_logged_in() ) {
            return;
        }

        $settings = $this->get_settings_for_display();
        echo wpfa_render_form( 'lostpassword', $this->build_render_args( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    protected function content_template(): void {
        ?>
        <div class="wpfa wpfa-form wpfa-form-lostpassword">
            <p style="opacity:0.6;font-size:13px;"><?php esc_html_e( '[Lost Password Form — visible on frontend]', 'wp-frontend-auth' ); ?></p>
        </div>
        <?php
    }
}

/* -----------------------------------------------------------------------
 * 4. Reset Password Widget
 * -------------------------------------------------------------------- */

class WPFA_Elementor_Reset_Password_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-reset-password'; }
    public function get_title(): string { return esc_html__( 'Reset Password Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-lock'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Reset Password Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'invalid_key_message', [
            'label'       => esc_html__( 'Invalid/expired link message', 'wp-frontend-auth' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => '',
            'placeholder' => esc_html__( 'Leave empty for default message.', 'wp-frontend-auth' ),
            'rows'        => 3,
        ] );

        $this->end_controls_section();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $settings = $this->get_settings_for_display();

        // Validate that the URL contains the required reset parameters.
        $rp_key   = sanitize_text_field( wp_unslash( $_GET['key']   ?? '' ) );
        $rp_login = sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) );

        $is_editor = \Elementor\Plugin::$instance->editor
                     && \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ( empty( $rp_key ) || empty( $rp_login ) ) {
            if ( $is_editor ) {
                $this->render_editor_placeholder( __( 'Reset Password Form — requires ?key=…&login=… in URL (sent in reset email).', 'wp-frontend-auth' ) );
            } else {
                $msg = ! empty( $settings['invalid_key_message'] )
                    ? $settings['invalid_key_message']
                    : __( 'This password reset link is invalid or has expired. Please request a new one.', 'wp-frontend-auth' );
                echo '<div class="wpfa wpfa-form wpfa-form-resetpass">'
                    . '<ul class="wpfa-errors" role="alert"><li class="wpfa-error">' . esc_html( $msg ) . '</li></ul>'
                    . '<p class="wpfa-links"><a href="' . esc_url( wpfa_get_action_url( 'lostpassword' ) ) . '">'
                    . esc_html__( 'Request a new password reset link', 'wp-frontend-auth' )
                    . '</a></p></div>';
            }
            return;
        }

        echo wpfa_render_form( 'resetpass', [ 'show_links' => false, 'redirect_to' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    protected function content_template(): void {
        ?>
        <div class="wpfa wpfa-form wpfa-form-resetpass">
            <p style="opacity:0.6;font-size:13px;"><?php esc_html_e( '[Reset Password Form — requires reset link parameters in URL]', 'wp-frontend-auth' ); ?></p>
        </div>
        <?php
    }
}

/* -----------------------------------------------------------------------
 * 5. Dashboard Widget
 * -------------------------------------------------------------------- */

class WPFA_Elementor_Dashboard_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-dashboard'; }
    public function get_title(): string { return esc_html__( 'Auth Dashboard', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-user-circle-o'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Auth Dashboard', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_login_when_logged_out', [
            'label'        => esc_html__( 'Show login form when logged out', 'wp-frontend-auth' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'wp-frontend-auth' ),
            'label_off'    => esc_html__( 'No', 'wp-frontend-auth' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->register_common_controls();
        $this->end_controls_section();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $settings = $this->get_settings_for_display();

        if ( is_user_logged_in() ) {
            echo wpfa_render_dashboard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        if ( 'yes' === ( $settings['show_login_when_logged_out'] ?? 'yes' ) ) {
            echo wpfa_render_form( 'login', $this->build_render_args( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    protected function content_template(): void {
        ?>
        <div class="wpfa wpfa-dashboard">
            <p style="opacity:0.6;font-size:13px;"><?php esc_html_e( '[Auth Dashboard — shows greeting when logged in, login form when logged out]', 'wp-frontend-auth' ); ?></p>
        </div>
        <?php
    }
}
