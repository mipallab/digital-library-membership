<?php
/**
 * Elementor Header Nav Widget Integration
 * Defines comprehensive controls and styling fields for the elementor editor.
 *
 * @since      1.7.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Elementor_Header_Nav extends \Elementor\Widget_Base {

	/**
	 * Get widget key identifier
	 */
	public function get_name() {
		return 'dlm-header-nav';
	}

	/**
	 * Get widget user-facing label
	 */
	public function get_title() {
		return __( 'DLM Header Navigation', 'digital-library-membership' );
	}

	/**
	 * Get widget edit icon
	 */
	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	/**
	 * Add categories to group widget in palette
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Register control settings
	 */
	protected function register_controls() {
		// ==========================================
		// CONTENT TAB
		// ==========================================
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content Settings', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'      => __( 'Avatar Size (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 36,
				),
			)
		);

		$this->add_control(
			'spacing',
			array(
				'label'      => __( 'Gap Spacing (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 16,
				),
			)
		);

		$this->add_control(
			'logout_btn_text',
			array(
				'label'       => __( 'Sign In Button Text', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Sign In', 'digital-library-membership' ),
				'placeholder' => __( 'Sign In', 'digital-library-membership' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: GENERAL GEOMETRY
		// ==========================================
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'General Styling', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Username Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-user-first-name' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-header-nav-container' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-header-nav-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'margin',
			array(
				'label'      => __( 'Margin', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-header-nav-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: LOGOUT STATE BUTTON
		// ==========================================
		$this->start_controls_section(
			'logout_btn_style_section',
			array(
				'label' => __( 'Logout State Button', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'btn_text_color',
			array(
				'label'     => __( 'Button Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-header-signin-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => __( 'Button Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-header-signin-btn' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => '{{WRAPPER}} .dlm-header-signin-btn',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_border',
				'selector' => '{{WRAPPER}} .dlm-header-signin-btn',
			)
		);

		$this->add_responsive_control(
			'btn_border_radius',
			array(
				'label'      => __( 'Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-header-signin-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => __( 'Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-header-signin-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'btn_margin',
			array(
				'label'      => __( 'Margin', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-header-signin-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_box_shadow',
				'selector' => '{{WRAPPER}} .dlm-header-signin-btn',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: DYNAMIC PROFILE PHOTO
		// ==========================================
		$this->start_controls_section(
			'avatar_style_section',
			array(
				'label' => __( 'Profile Photo', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'avatar_padding',
			array(
				'label'      => __( 'Avatar Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-user-avatar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'avatar_margin',
			array(
				'label'      => __( 'Avatar Margin', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-user-avatar' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'avatar_border',
				'label'    => __( 'Avatar Border', 'digital-library-membership' ),
				'selector' => '{{WRAPPER}} .dlm-user-avatar',
			)
		);

		$this->add_responsive_control(
			'avatar_border_radius',
			array(
				'label'      => __( 'Avatar Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-user-avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: NOTIFICATION BELL & RED DOT
		// ==========================================
		$this->start_controls_section(
			'notif_bell_style_section',
			array(
				'label' => __( 'Notification Icon & Badge', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'bell_color',
			array(
				'label'     => __( 'Bell Icon Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-bell-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'bell_hover_color',
			array(
				'label'     => __( 'Bell Icon Hover Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-bell-btn:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'bell_size',
			array(
				'label'      => __( 'Bell Icon Size (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 12,
						'max'  => 48,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-notif-bell-btn .material-symbols-outlined' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// RED DOT BADGE
		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => __( 'Badge (Red Dot) Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-badge' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'badge_size',
			array(
				'label'      => __( 'Badge Size (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 4,
						'max'  => 24,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-notif-badge' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'badge_horizontal_position',
			array(
				'label'      => __( 'Badge Horizontal Offset (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => -10,
						'max'  => 20,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-notif-badge' => 'right: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'badge_vertical_position',
			array(
				'label'      => __( 'Badge Vertical Offset (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => -10,
						'max'  => 20,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-notif-badge' => 'top: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: ALERTS DROPDOWN
		// ==========================================
		$this->start_controls_section(
			'dropdown_style_section',
			array(
				'label' => __( 'Notification Dropdown Box', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'dropdown_bg_color',
			array(
				'label'     => __( 'Dropdown Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-dropdown' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'dropdown_border',
				'label'    => __( 'Dropdown Border', 'digital-library-membership' ),
				'selector' => '{{WRAPPER}} .dlm-notif-dropdown',
			)
		);

		$this->add_responsive_control(
			'dropdown_border_radius',
			array(
				'label'      => __( 'Dropdown Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-notif-dropdown' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'dropdown_header_text_color',
			array(
				'label'     => __( 'Header Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-header-title' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'dropdown_item_title_color',
			array(
				'label'     => __( 'Alert Item Title Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-title' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'dropdown_item_time_color',
			array(
				'label'     => __( 'Alert Item Time Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-time' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'dropdown_item_hover_bg',
			array(
				'label'     => __( 'Alert Item Hover Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-notif-item:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings  = $this->get_settings_for_display();
		$avatar_sz = isset( $settings['avatar_size']['size'] ) ? $settings['avatar_size']['size'] . 'px' : '36px';
		$spc       = isset( $settings['spacing']['size'] ) ? $settings['spacing']['size'] . 'px' : '16px';
		$btn_text  = isset( $settings['logout_btn_text'] ) ? $settings['logout_btn_text'] : '';

		// Render via the modular shortcode logic
		echo do_shortcode( sprintf(
			'[dlm_header_nav avatar_size="%s" spacing="%s" btn_text="%s"]',
			esc_attr( $avatar_sz ),
			esc_attr( $spc ),
			esc_attr( $btn_text )
		) );
	}
}
