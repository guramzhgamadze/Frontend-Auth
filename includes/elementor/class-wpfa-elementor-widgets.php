<?php
/**
 * WP Frontend Auth – Elementor Widgets
 *
 * v1.3.0 — Full rewrite with complete Content + Style tab controls.
 * All CSS is applied via Elementor's {{WRAPPER}} selectors system.
 * Follows elementor-patterns.md V4 rules (no .elementor-widget-container).
 *
 * @package WP_Frontend_Auth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the custom widget category in the Elementor panel.
 * Called on 'elementor/elements/categories_registered' from wp-frontend-auth.php.
 */
function wpfa_register_elementor_category( \Elementor\Elements_Manager $elements_manager ): void {
    $elements_manager->add_category( 'wp-frontend-auth', [
        'title' => esc_html__( 'Frontend Auth', 'wp-frontend-auth' ),
        'icon'  => 'eicon-lock-user',
    ] );
}

/**
 * Register all Elementor widgets.
 */
function wpfa_register_elementor_widgets( \Elementor\Widgets_Manager $manager ): void {
    $manager->register( new WPFA_Elementor_Login_Widget() );
    $manager->register( new WPFA_Elementor_Register_Widget() );
    $manager->register( new WPFA_Elementor_Lost_Password_Widget() );
    $manager->register( new WPFA_Elementor_Reset_Password_Widget() );
    $manager->register( new WPFA_Elementor_Dashboard_Widget() );
}

/* =======================================================================
 * Abstract base — boilerplate + shared style controls
 * ===================================================================== */

abstract class WPFA_Elementor_Base_Widget extends \Elementor\Widget_Base {

    public function get_categories(): array  { return [ 'wp-frontend-auth' ]; }

    public function get_keywords(): array {
        return [ 'login', 'auth', 'register', 'password', 'wpfa', 'frontend auth' ];
    }

    public function get_style_depends(): array  { return [ 'wp-frontend-auth' ]; }
    public function get_script_depends(): array { return [ 'wp-frontend-auth' ]; }

    public function has_widget_inner_wrapper(): bool { return false; }

    protected function is_dynamic_content(): bool { return true; }

    /* -------------------------------------------------------------------
     * Shared CONTENT controls
     * ---------------------------------------------------------------- */

    protected function register_common_content_controls(): void {

        $this->add_control( 'form_title_text', [
            'label'       => esc_html__( 'Form Title', 'wp-frontend-auth' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'Leave empty to hide', 'wp-frontend-auth' ),
            'label_block' => true,
        ] );

        $this->add_control( 'form_title_tag', [
            'label'   => esc_html__( 'Title HTML Tag', 'wp-frontend-auth' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'h3',
            'options' => [
                'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4',
                'h5' => 'H5', 'h6' => 'H6', 'div' => 'div', 'span' => 'span', 'p' => 'p',
            ],
            'condition' => [ 'form_title_text!' => '' ],
        ] );

        $this->add_control( 'redirect_to', [
            'label'       => esc_html__( 'Redirect URL after success', 'wp-frontend-auth' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'Default: admin dashboard', 'wp-frontend-auth' ),
            'label_block' => true,
            'separator'   => 'before',
        ] );

        $this->add_control( 'show_links', [
            'label'        => esc_html__( 'Show action links', 'wp-frontend-auth' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'wp-frontend-auth' ),
            'label_off'    => esc_html__( 'No', 'wp-frontend-auth' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );
    }

    /* -------------------------------------------------------------------
     * Shared STYLE controls — full form styling
     * ---------------------------------------------------------------- */

    protected function register_form_style_controls(): void {

        /* ── Form Container ── */
        $this->start_controls_section( 'section_style_form', [
            'label' => esc_html__( 'Form Container', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'form_bg_color', [
            'label'     => esc_html__( 'Background Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-form' => 'background-color: {{VALUE}};' ],
        ] );

        $this->add_responsive_control( 'form_padding', [
            'label'      => esc_html__( 'Padding', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name'     => 'form_border',
            'selector' => '{{WRAPPER}} .wpfa-form',
        ] );

        $this->add_responsive_control( 'form_border_radius', [
            'label'      => esc_html__( 'Border Radius', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'form_box_shadow',
            'selector' => '{{WRAPPER}} .wpfa-form',
        ] );

        $this->end_controls_section();

        /* ── Form Title ── */
        $this->start_controls_section( 'section_style_title', [
            'label'     => esc_html__( 'Form Title', 'wp-frontend-auth' ),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [ 'form_title_text!' => '' ],
        ] );

        $this->add_control( 'title_color', [
            'label'     => esc_html__( 'Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-form-title' => 'color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .wpfa-form-title',
        ] );

        $this->add_responsive_control( 'title_align', [
            'label'   => esc_html__( 'Alignment', 'wp-frontend-auth' ),
            'type'    => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left'   => [ 'title' => esc_html__( 'Left', 'wp-frontend-auth' ),   'icon' => 'eicon-text-align-left' ],
                'center' => [ 'title' => esc_html__( 'Center', 'wp-frontend-auth' ), 'icon' => 'eicon-text-align-center' ],
                'right'  => [ 'title' => esc_html__( 'Right', 'wp-frontend-auth' ),  'icon' => 'eicon-text-align-right' ],
            ],
            'selectors' => [ '{{WRAPPER}} .wpfa-form-title' => 'text-align: {{VALUE}};' ],
        ] );

        $this->add_responsive_control( 'title_spacing', [
            'label'      => esc_html__( 'Bottom Spacing', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-form-title' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->end_controls_section();

        /* ── Labels ── */
        $this->start_controls_section( 'section_style_labels', [
            'label' => esc_html__( 'Labels', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'label_color', [
            'label'     => esc_html__( 'Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-label' => 'color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'label_typography',
            'selector' => '{{WRAPPER}} .wpfa-label',
        ] );

        $this->add_responsive_control( 'label_spacing', [
            'label'      => esc_html__( 'Bottom Spacing', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-label' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->end_controls_section();

        /* ── Input Fields ── */
        $this->start_controls_section( 'section_style_fields', [
            'label' => esc_html__( 'Input Fields', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'field_text_color', [
            'label'     => esc_html__( 'Text Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-field' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'field_placeholder_color', [
            'label'     => esc_html__( 'Placeholder Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-field::placeholder' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'field_bg_color', [
            'label'     => esc_html__( 'Background Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-field' => 'background-color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'field_typography',
            'selector' => '{{WRAPPER}} .wpfa-field',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name'     => 'field_border',
            'selector' => '{{WRAPPER}} .wpfa-field',
        ] );

        $this->add_responsive_control( 'field_border_radius', [
            'label'      => esc_html__( 'Border Radius', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-field' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'field_padding', [
            'label'      => esc_html__( 'Padding', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em' ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_control( 'heading_field_focus', [
            'label'     => esc_html__( 'Focus State', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'field_focus_border_color', [
            'label'     => esc_html__( 'Border Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-field:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}};' ],
        ] );

        $this->add_responsive_control( 'field_spacing', [
            'label'      => esc_html__( 'Field Spacing', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-field-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
            'separator'  => 'before',
        ] );

        $this->end_controls_section();

        /* ── Submit Button ── */
        $this->start_controls_section( 'section_style_button', [
            'label' => esc_html__( 'Button', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'button_typography',
            'selector' => '{{WRAPPER}} .wpfa-submit-button',
        ] );

        $this->add_responsive_control( 'button_width', [
            'label'   => esc_html__( 'Width', 'wp-frontend-auth' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto' => esc_html__( 'Auto', 'wp-frontend-auth' ),
                '100%' => esc_html__( 'Full Width', 'wp-frontend-auth' ),
            ],
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button' => 'width: {{VALUE}};' ],
        ] );

        $this->add_responsive_control( 'button_padding', [
            'label'      => esc_html__( 'Padding', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em' ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-submit-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'button_border_radius', [
            'label'      => esc_html__( 'Border Radius', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-submit-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->start_controls_tabs( 'button_style_tabs' );

        // Normal
        $this->start_controls_tab( 'button_normal', [
            'label' => esc_html__( 'Normal', 'wp-frontend-auth' ),
        ] );

        $this->add_control( 'button_text_color', [
            'label'     => esc_html__( 'Text Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'button_bg_color', [
            'label'     => esc_html__( 'Background', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button' => 'background-color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name'     => 'button_border',
            'selector' => '{{WRAPPER}} .wpfa-submit-button',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'button_shadow',
            'selector' => '{{WRAPPER}} .wpfa-submit-button',
        ] );

        $this->end_controls_tab();

        // Hover
        $this->start_controls_tab( 'button_hover', [
            'label' => esc_html__( 'Hover', 'wp-frontend-auth' ),
        ] );

        $this->add_control( 'button_text_color_hover', [
            'label'     => esc_html__( 'Text Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button:hover, {{WRAPPER}} .wpfa-submit-button:focus' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'button_bg_color_hover', [
            'label'     => esc_html__( 'Background', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button:hover, {{WRAPPER}} .wpfa-submit-button:focus' => 'background-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'button_border_color_hover', [
            'label'     => esc_html__( 'Border Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button:hover, {{WRAPPER}} .wpfa-submit-button:focus' => 'border-color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'button_shadow_hover',
            'selector' => '{{WRAPPER}} .wpfa-submit-button:hover, {{WRAPPER}} .wpfa-submit-button:focus',
        ] );

        $this->add_control( 'button_hover_transition', [
            'label'     => esc_html__( 'Transition (ms)', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'range'     => [ 'px' => [ 'min' => 0, 'max' => 1000, 'step' => 50 ] ],
            'default'   => [ 'size' => 200 ],
            'selectors' => [ '{{WRAPPER}} .wpfa-submit-button' => 'transition-duration: {{SIZE}}ms;' ],
        ] );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();

        /* ── Action Links ── */
        $this->start_controls_section( 'section_style_links', [
            'label' => esc_html__( 'Action Links', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'links_color', [
            'label'     => esc_html__( 'Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-links a' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'links_color_hover', [
            'label'     => esc_html__( 'Hover Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-links a:hover' => 'color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'links_typography',
            'selector' => '{{WRAPPER}} .wpfa-links',
        ] );

        $this->add_responsive_control( 'links_align', [
            'label'   => esc_html__( 'Alignment', 'wp-frontend-auth' ),
            'type'    => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left'   => [ 'title' => esc_html__( 'Left', 'wp-frontend-auth' ),   'icon' => 'eicon-text-align-left' ],
                'center' => [ 'title' => esc_html__( 'Center', 'wp-frontend-auth' ), 'icon' => 'eicon-text-align-center' ],
                'right'  => [ 'title' => esc_html__( 'Right', 'wp-frontend-auth' ),  'icon' => 'eicon-text-align-right' ],
            ],
            'selectors' => [ '{{WRAPPER}} .wpfa-links' => 'text-align: {{VALUE}};' ],
        ] );

        $this->end_controls_section();

        /* ── Messages & Errors ── */
        $this->start_controls_section( 'section_style_messages', [
            'label' => esc_html__( 'Messages & Errors', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'heading_errors', [
            'label' => esc_html__( 'Error Notices', 'wp-frontend-auth' ),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ] );

        $this->add_control( 'error_text_color', [
            'label'     => esc_html__( 'Text Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-error' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'error_bg_color', [
            'label'     => esc_html__( 'Background', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-error' => 'background-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'error_border_color', [
            'label'     => esc_html__( 'Border Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-error' => 'border-left-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'heading_success', [
            'label'     => esc_html__( 'Success Messages', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'message_text_color', [
            'label'     => esc_html__( 'Text Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-message' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'message_bg_color', [
            'label'     => esc_html__( 'Background', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-message' => 'background-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'message_border_color', [
            'label'     => esc_html__( 'Border Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-message' => 'border-left-color: {{VALUE}};' ],
        ] );

        $this->end_controls_section();
    }

    /* -------------------------------------------------------------------
     * Dashboard style controls (for Login + Dashboard widgets)
     * ---------------------------------------------------------------- */

    protected function register_dashboard_style_controls(): void {

        $this->start_controls_section( 'section_style_dashboard', [
            'label' => esc_html__( 'Dashboard Panel', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'dash_greeting_color', [
            'label'     => esc_html__( 'Greeting Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-dashboard-greeting' => 'color: {{VALUE}};' ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'dash_greeting_typography',
            'selector' => '{{WRAPPER}} .wpfa-dashboard-greeting',
        ] );

        $this->add_responsive_control( 'dash_avatar_size', [
            'label'      => esc_html__( 'Avatar Size', 'wp-frontend-auth' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 24, 'max' => 150 ] ],
            'selectors'  => [ '{{WRAPPER}} .wpfa-dashboard-avatar img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_control( 'dash_link_color', [
            'label'     => esc_html__( 'Link Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-dashboard-links a' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'dash_link_hover_color', [
            'label'     => esc_html__( 'Link Hover Color', 'wp-frontend-auth' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .wpfa-dashboard-links a:hover' => 'color: {{VALUE}};' ],
        ] );

        $this->add_responsive_control( 'dash_align', [
            'label'   => esc_html__( 'Alignment', 'wp-frontend-auth' ),
            'type'    => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left'   => [ 'title' => esc_html__( 'Left', 'wp-frontend-auth' ),   'icon' => 'eicon-text-align-left' ],
                'center' => [ 'title' => esc_html__( 'Center', 'wp-frontend-auth' ), 'icon' => 'eicon-text-align-center' ],
                'right'  => [ 'title' => esc_html__( 'Right', 'wp-frontend-auth' ),  'icon' => 'eicon-text-align-right' ],
            ],
            'selectors' => [ '{{WRAPPER}} .wpfa-dashboard' => 'text-align: {{VALUE}};' ],
        ] );

        $this->end_controls_section();
    }

    /* -------------------------------------------------------------------
     * Shared render helpers
     * ---------------------------------------------------------------- */

    protected function build_render_args( array $settings ): array {
        return [
            'show_links'  => 'yes' === ( $settings['show_links'] ?? 'yes' ),
            'redirect_to' => esc_url( $settings['redirect_to'] ?? '' ),
        ];
    }

    protected function maybe_print_script_data(): void {
        wpfa_maybe_add_inline_script();
    }

    protected function render_form_title( array $settings ): void {
        $title = $settings['form_title_text'] ?? '';
        if ( '' === $title ) { return; }
        $tag = $settings['form_title_tag'] ?? 'h3';
        $allowed = [ 'h1','h2','h3','h4','h5','h6','div','span','p' ];
        if ( ! in_array( $tag, $allowed, true ) ) { $tag = 'h3'; }
        echo '<' . esc_attr( $tag ) . ' class="wpfa-form-title">' . esc_html( $title ) . '</' . esc_attr( $tag ) . '>';
    }

    protected function render_editor_placeholder( string $message ): void {
        $is_editor = \Elementor\Plugin::$instance->editor
                     && \Elementor\Plugin::$instance->editor->is_edit_mode();
        if ( $is_editor ) {
            echo '<div style="padding:1.5em;background:#f0f6fc;border:1px dashed #0073aa;border-radius:4px;font-size:13px;color:#0c447c;">'
                . esc_html( $message ) . '</div>';
        }
    }
}


/* =======================================================================
 * 1. Login Widget
 * ===================================================================== */

class WPFA_Elementor_Login_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-login'; }
    public function get_title(): string { return esc_html__( 'Login Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-lock-user'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Login Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->register_common_content_controls();
        $this->add_control( 'show_dashboard', [
            'label'        => esc_html__( 'Show dashboard when logged in', 'wp-frontend-auth' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'wp-frontend-auth' ),
            'label_off'    => esc_html__( 'No', 'wp-frontend-auth' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );
        $this->end_controls_section();

        $this->register_form_style_controls();
        $this->register_dashboard_style_controls();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $s = $this->get_settings_for_display();
        if ( is_user_logged_in() ) {
            if ( 'yes' === ( $s['show_dashboard'] ?? 'yes' ) ) {
                echo wpfa_render_dashboard(); // phpcs:ignore
            }
            return;
        }
        $this->render_form_title( $s );
        echo wpfa_render_form( 'login', $this->build_render_args( $s ) ); // phpcs:ignore
    }

    protected function content_template(): void { ?>
        <# var tag = settings.form_title_tag || 'h3'; if ( settings.form_title_text ) { #>
            <{{{ tag }}} class="wpfa-form-title">{{{ settings.form_title_text }}}</{{{ tag }}}>
        <# } #>
        <div class="wpfa wpfa-form wpfa-form-login"><div class="wpfa-inner-form">
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'Username or Email', 'wp-frontend-auth' ); ?></label><input type="text" class="wpfa-field" disabled></p>
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'Password', 'wp-frontend-auth' ); ?></label><input type="password" class="wpfa-field" disabled></p>
            <p class="wpfa-submit"><button type="button" class="wpfa-button wpfa-submit-button"><?php esc_html_e( 'Log In', 'wp-frontend-auth' ); ?></button></p>
        </div>
        <# if ( 'yes' === settings.show_links ) { #>
        <p class="wpfa-links"><a href="#"><?php esc_html_e( 'Register', 'wp-frontend-auth' ); ?></a> &bull; <a href="#"><?php esc_html_e( 'Lost your password?', 'wp-frontend-auth' ); ?></a></p>
        <# } #></div>
    <?php }
}


/* =======================================================================
 * 2. Register Widget
 * ===================================================================== */

class WPFA_Elementor_Register_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-register'; }
    public function get_title(): string { return esc_html__( 'Registration Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-person'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Registration Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->register_common_content_controls();
        $this->end_controls_section();
        $this->register_form_style_controls();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $s = $this->get_settings_for_display();
        if ( ! get_option( 'users_can_register' ) ) {
            $this->render_editor_placeholder( __( 'Registration Form — registration is disabled in Settings → General.', 'wp-frontend-auth' ) );
            return;
        }
        if ( is_user_logged_in() ) { return; }
        $this->render_form_title( $s );
        echo wpfa_render_form( 'register', $this->build_render_args( $s ) ); // phpcs:ignore
    }

    protected function content_template(): void { ?>
        <# var tag = settings.form_title_tag || 'h3'; if ( settings.form_title_text ) { #>
            <{{{ tag }}} class="wpfa-form-title">{{{ settings.form_title_text }}}</{{{ tag }}}>
        <# } #>
        <div class="wpfa wpfa-form wpfa-form-register"><div class="wpfa-inner-form">
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'Username', 'wp-frontend-auth' ); ?></label><input type="text" class="wpfa-field" disabled></p>
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'Email Address', 'wp-frontend-auth' ); ?></label><input type="email" class="wpfa-field" disabled></p>
            <p class="wpfa-submit"><button type="button" class="wpfa-button wpfa-submit-button"><?php esc_html_e( 'Register', 'wp-frontend-auth' ); ?></button></p>
        </div>
        <# if ( 'yes' === settings.show_links ) { #>
        <p class="wpfa-links"><a href="#"><?php esc_html_e( 'Log In', 'wp-frontend-auth' ); ?></a></p>
        <# } #></div>
    <?php }
}


/* =======================================================================
 * 3. Lost Password Widget
 * ===================================================================== */

class WPFA_Elementor_Lost_Password_Widget extends WPFA_Elementor_Base_Widget {

    public function get_name(): string  { return 'wpfa-lost-password'; }
    public function get_title(): string { return esc_html__( 'Lost Password Form', 'wp-frontend-auth' ); }
    public function get_icon(): string  { return 'eicon-email'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Lost Password Form', 'wp-frontend-auth' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->register_common_content_controls();
        $this->end_controls_section();
        $this->register_form_style_controls();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        if ( is_user_logged_in() ) { return; }
        $s = $this->get_settings_for_display();
        $this->render_form_title( $s );
        echo wpfa_render_form( 'lostpassword', $this->build_render_args( $s ) ); // phpcs:ignore
    }

    protected function content_template(): void { ?>
        <# var tag = settings.form_title_tag || 'h3'; if ( settings.form_title_text ) { #>
            <{{{ tag }}} class="wpfa-form-title">{{{ settings.form_title_text }}}</{{{ tag }}}>
        <# } #>
        <div class="wpfa wpfa-form wpfa-form-lostpassword"><div class="wpfa-inner-form">
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'Username or Email', 'wp-frontend-auth' ); ?></label><input type="text" class="wpfa-field" disabled></p>
            <p class="wpfa-submit"><button type="button" class="wpfa-button wpfa-submit-button"><?php esc_html_e( 'Get New Password', 'wp-frontend-auth' ); ?></button></p>
        </div>
        <# if ( 'yes' === settings.show_links ) { #>
        <p class="wpfa-links"><a href="#"><?php esc_html_e( 'Log In', 'wp-frontend-auth' ); ?></a></p>
        <# } #></div>
    <?php }
}


/* =======================================================================
 * 4. Reset Password Widget
 * ===================================================================== */

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
        $this->add_control( 'form_title_text', [
            'label'       => esc_html__( 'Form Title', 'wp-frontend-auth' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'Leave empty to hide', 'wp-frontend-auth' ),
            'label_block' => true,
        ] );
        $this->add_control( 'form_title_tag', [
            'label'   => esc_html__( 'Title HTML Tag', 'wp-frontend-auth' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'h3',
            'options' => [ 'h1'=>'H1','h2'=>'H2','h3'=>'H3','h4'=>'H4','h5'=>'H5','h6'=>'H6','div'=>'div','p'=>'p' ],
            'condition' => [ 'form_title_text!' => '' ],
        ] );
        $this->end_controls_section();
        $this->register_form_style_controls();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $s = $this->get_settings_for_display();
        $rp_key   = sanitize_text_field( wp_unslash( $_GET['key']   ?? '' ) );
        $rp_login = sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) );
        $is_editor = \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ( empty( $rp_key ) || empty( $rp_login ) ) {
            if ( $is_editor ) {
                $this->render_editor_placeholder( __( 'Reset Password Form — requires ?key=…&login=… in URL (sent in reset email).', 'wp-frontend-auth' ) );
            } else {
                $msg = ! empty( $s['invalid_key_message'] ) ? $s['invalid_key_message']
                    : __( 'This password reset link is invalid or has expired. Please request a new one.', 'wp-frontend-auth' );
                echo '<div class="wpfa wpfa-form wpfa-form-resetpass">'
                    . '<ul class="wpfa-errors" role="alert"><li class="wpfa-error">' . esc_html( $msg ) . '</li></ul>'
                    . '<p class="wpfa-links"><a href="' . esc_url( wpfa_get_action_url( 'lostpassword' ) ) . '">'
                    . esc_html__( 'Request a new password reset link', 'wp-frontend-auth' ) . '</a></p></div>';
            }
            return;
        }
        $this->render_form_title( $s );
        echo wpfa_render_form( 'resetpass', [ 'show_links' => false, 'redirect_to' => '' ] ); // phpcs:ignore
    }

    protected function content_template(): void { ?>
        <# var tag = settings.form_title_tag || 'h3'; if ( settings.form_title_text ) { #>
            <{{{ tag }}} class="wpfa-form-title">{{{ settings.form_title_text }}}</{{{ tag }}}>
        <# } #>
        <div class="wpfa wpfa-form wpfa-form-resetpass"><div class="wpfa-inner-form">
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'New Password', 'wp-frontend-auth' ); ?></label><input type="password" class="wpfa-field" disabled></p>
            <p class="wpfa-field-wrap"><label class="wpfa-label"><?php esc_html_e( 'Confirm New Password', 'wp-frontend-auth' ); ?></label><input type="password" class="wpfa-field" disabled></p>
            <p class="wpfa-submit"><button type="button" class="wpfa-button wpfa-submit-button"><?php esc_html_e( 'Reset Password', 'wp-frontend-auth' ); ?></button></p>
        </div></div>
    <?php }
}


/* =======================================================================
 * 5. Dashboard Widget
 * ===================================================================== */

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
        $this->register_common_content_controls();
        $this->end_controls_section();

        $this->register_form_style_controls();
        $this->register_dashboard_style_controls();
    }

    protected function render(): void {
        $this->maybe_print_script_data();
        $s = $this->get_settings_for_display();
        if ( is_user_logged_in() ) {
            echo wpfa_render_dashboard(); // phpcs:ignore
            return;
        }
        if ( 'yes' === ( $s['show_login_when_logged_out'] ?? 'yes' ) ) {
            $this->render_form_title( $s );
            echo wpfa_render_form( 'login', $this->build_render_args( $s ) ); // phpcs:ignore
        }
    }

    protected function content_template(): void { ?>
        <div class="wpfa wpfa-dashboard">
            <div class="wpfa-dashboard-avatar"><img src="https://secure.gravatar.com/avatar/?s=64&d=mm&r=g" width="64" height="64" style="border-radius:50%" alt=""></div>
            <p class="wpfa-dashboard-greeting"><?php esc_html_e( 'Hello, User!', 'wp-frontend-auth' ); ?></p>
            <ul class="wpfa-dashboard-links">
                <li><a href="#"><?php esc_html_e( 'Edit Profile', 'wp-frontend-auth' ); ?></a></li>
                <li><a href="#"><?php esc_html_e( 'Log Out', 'wp-frontend-auth' ); ?></a></li>
            </ul>
        </div>
    <?php }
}
