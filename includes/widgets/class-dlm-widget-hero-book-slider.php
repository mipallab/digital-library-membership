<?php
/**
 * Hero Featured Book Slider Elementor Widget
 *
 * @since      3.0.0
 * @package    DLM
 * @subpackage DLM/includes/widgets
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class DLM_Widget_Hero_Book_Slider extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dlm_hero_book_slider';
	}

	public function get_title() {
		return esc_html__( 'Hero Featured Book Slider', 'digital-library-membership' );
	}

	public function get_icon() {
		return 'eicon-slider-album';
	}

	public function get_categories() {
		return array( 'digital-library' );
	}

	public function get_keywords() {
		return array( 'hero', 'slider', 'book', 'bestseller', 'carousel', 'dlm' );
	}

	protected function register_controls() {

		// ==========================================
		// CONTENT TAB: Slides
		// ==========================================
		$this->start_controls_section(
			'section_slides',
			array(
				'label' => esc_html__( 'Book Slides', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'       => esc_html__( 'Data Source', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'dynamic',
				'options'     => array(
					'dynamic' => esc_html__( 'Dynamic (Live Books from Database)', 'digital-library-membership' ),
					'custom'  => esc_html__( 'Custom Slides (Manual Repeater)', 'digital-library-membership' ),
				),
				'description' => esc_html__( 'Dynamic mode automatically pulls published & featured books directly from your Digital Library database.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'dynamic_limit',
			array(
				'label'     => esc_html__( 'Number of Dynamic Slides', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5,
				'min'       => 1,
				'max'       => 20,
				'condition' => array(
					'source' => 'dynamic',
				),
			)
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'badge_text',
			array(
				'label'   => esc_html__( 'Badge Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'FEATURED BESTSELLER', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Book Title', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'THE AI JOB SHIFT', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'subtitle',
			array(
				'label'       => esc_html__( 'Subtitle / Author', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'How to Transition Into AI-Supported Careers', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Description', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Future-proof your career with artificial intelligence. A practical roadmap to adapt, upskill, and thrive in an AI-driven global workforce.', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'rating',
			array(
				'label'   => esc_html__( 'Rating Score', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( '4.9 / 5.0 (2,450+ Reviews)', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'book_cover',
			array(
				'label'   => esc_html__( 'Book Cover Image', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array(
					'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png',
				),
			)
		);

		$repeater->add_control(
			'btn_read_text',
			array(
				'label'   => esc_html__( 'Primary Button Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Read Online Now', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'btn_read_link',
			array(
				'label'   => esc_html__( 'Primary Button Link', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#library' ),
			)
		);

		$repeater->add_control(
			'btn_secondary_text',
			array(
				'label'   => esc_html__( 'Secondary Button Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'View Membership Plans', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'btn_secondary_link',
			array(
				'label'   => esc_html__( 'Secondary Button Link', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#membership' ),
			)
		);

		$this->add_control(
			'slides',
			array(
				'label'       => esc_html__( 'Slides List', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'title'              => esc_html__( 'THE AI JOB SHIFT', 'digital-library-membership' ),
						'subtitle'           => esc_html__( 'How to Transition Into AI-Supported Careers', 'digital-library-membership' ),
						'badge_text'         => esc_html__( 'FEATURED BESTSELLER', 'digital-library-membership' ),
						'description'        => esc_html__( 'Future-proof your career with artificial intelligence. A practical roadmap to adapt, upskill, and thrive in an AI-driven global workforce.', 'digital-library-membership' ),
						'book_cover'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png' ),
						'rating'             => esc_html__( '4.9 / 5.0 (2,450+ Reviews)', 'digital-library-membership' ),
						'btn_read_text'      => esc_html__( 'Read Online Now', 'digital-library-membership' ),
						'btn_read_link'      => array( 'url' => '#library' ),
						'btn_secondary_text' => esc_html__( 'View Membership Plans', 'digital-library-membership' ),
						'btn_secondary_link' => array( 'url' => '#membership' ),
					),
					array(
						'title'              => esc_html__( 'Light and Shadow', 'digital-library-membership' ),
						'subtitle'           => esc_html__( 'Architectural Intent & Sensory Harmony', 'digital-library-membership' ),
						'badge_text'         => esc_html__( 'NEW RELEASE', 'digital-library-membership' ),
						'description'        => esc_html__( 'In the profound stillness of a space carved from granite, explore silence, light, and modern architectural retreat.', 'digital-library-membership' ),
						'book_cover'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg' ),
						'rating'             => esc_html__( '5.0 / 5.0 (1,120+ Reviews)', 'digital-library-membership' ),
						'btn_read_text'      => esc_html__( 'Read Online Now', 'digital-library-membership' ),
						'btn_read_link'      => array( 'url' => '#library' ),
						'btn_secondary_text' => esc_html__( 'View Membership Plans', 'digital-library-membership' ),
						'btn_secondary_link' => array( 'url' => '#membership' ),
					),
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// CONTENT TAB: Slider Settings
		// ==========================================
		$this->start_controls_section(
			'section_slider_settings',
			array(
				'label' => esc_html__( 'Slider Settings', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => esc_html__( 'Show Navigation Arrows', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'digital-library-membership' ),
				'label_off'    => esc_html__( 'Hide', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toggle navigation arrows (Previous / Next) on or off.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'        => esc_html__( 'Show Pagination Dots', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'digital-library-membership' ),
				'label_off'    => esc_html__( 'Hide', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toggle bottom slide pagination dots on or off.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'     => esc_html__( 'Autoplay', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => esc_html__( 'Autoplay Delay (ms)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => esc_html__( 'Transition Speed (ms)', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 800,
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'   => esc_html__( 'Infinite Loop', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: Slide Content & Padding Style
		// ==========================================
		$this->start_controls_section(
			'section_slide_padding_style',
			array(
				'label' => esc_html__( 'Slide Padding & Layout', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'slide_content_padding',
			array(
				'label'      => esc_html__( 'Slide Inner Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => 30,
					'right'    => 40,
					'bottom'   => 30,
					'left'     => 40,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-hero-slide-grid' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'slide_columns_gap',
			array(
				'label'      => esc_html__( 'Gap Between Text & Book (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 120,
						'step' => 2,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 48,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-hero-slide-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'slide_bg_color',
			array(
				'label'     => esc_html__( 'Slide Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .swiper-slide' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .dlm-hero-slide-grid' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'slide_border_radius',
			array(
				'label'      => esc_html__( 'Slide Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-slide' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .dlm-hero-slide-grid' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: Container / Box Layout
		// ==========================================
		$this->start_controls_section(
			'section_container_layout',
			array(
				'label' => esc_html__( 'Container / Box Layout', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'container_type',
			array(
				'label'   => esc_html__( 'Container Width Type', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'boxed',
				'options' => array(
					'boxed'      => esc_html__( 'Boxed Container', 'digital-library-membership' ),
					'full_width' => esc_html__( 'Full Width', 'digital-library-membership' ),
				),
			)
		);

		$this->add_responsive_control(
			'container_max_width',
			array(
				'label'      => esc_html__( 'Box Max Width', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 300,
						'max'  => 1920,
						'step' => 10,
					),
					'%'  => array(
						'min' => 20,
						'max' => 100,
					),
					'vw' => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 1200,
				),
				'condition'  => array(
					'container_type' => 'boxed',
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-hero-box-container' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'container_bg_color',
			array(
				'label'     => esc_html__( 'Container Box Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .dlm-hero-box-container' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'container_padding',
			array(
				'label'      => esc_html__( 'Container Inner Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-hero-box-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'container_border_radius',
			array(
				'label'      => esc_html__( 'Container Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-hero-box-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_box_shadow',
				'label'    => esc_html__( 'Container Box Shadow', 'digital-library-membership' ),
				'selector' => '{{WRAPPER}} .dlm-hero-box-container',
			)
		);

		$this->add_control(
			'heading_outer_section',
			array(
				'label'     => esc_html__( 'Outer Section Settings', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => esc_html__( 'Outer Section Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(133, 83, 0, 0.08)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-hero-section' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'section_padding',
			array(
				'label'      => esc_html__( 'Outer Section Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => 90,
					'right'    => 24,
					'bottom'   => 90,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-hero-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: Content Typography & Colors
		// ==========================================
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Content Typography & Colors', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'primary_color',
			array(
				'label'     => esc_html__( 'Primary Accent Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1c1c',
				'selectors' => array(
					'{{WRAPPER}} .dlm-hero-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title Typography', 'digital-library-membership' ),
				'selector' => '{{WRAPPER}} .dlm-hero-title',
			)
		);

		$this->add_control(
			'subtitle_color',
			array(
				'label'     => esc_html__( 'Subtitle Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .dlm-hero-subtitle' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'label'    => esc_html__( 'Subtitle Typography', 'digital-library-membership' ),
				'selector' => '{{WRAPPER}} .dlm-hero-subtitle',
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Description Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(26, 28, 28, 0.82)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-hero-desc' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'label'    => esc_html__( 'Description Typography', 'digital-library-membership' ),
				'selector' => '{{WRAPPER}} .dlm-hero-desc',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: Navigation Arrows Style
		// ==========================================
		$this->start_controls_section(
			'section_arrows_style',
			array(
				'label'     => esc_html__( 'Navigation Arrows Style', 'digital-library-membership' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_arrows' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'arrows_size',
			array(
				'label'      => esc_html__( 'Button Size (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'range'      => array(
					'px' => array(
						'min' => 30,
						'max' => 70,
					),
				),
				'default'    => array(
					'size' => 46,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
				),
			)
		);

		$this->add_responsive_control(
			'arrows_icon_size',
			array(
				'label'      => esc_html__( 'Icon Size (px)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 36,
					),
				),
				'default'    => array(
					'size' => 20,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn svg' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_arrows_style' );

		$this->start_controls_tab(
			'tab_arrows_normal',
			array(
				'label' => esc_html__( 'Normal', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'arrows_color',
			array(
				'label'     => esc_html__( 'Arrow Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrows_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrows_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(133, 83, 0, 0.18)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_arrows_hover',
			array(
				'label' => esc_html__( 'Hover', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'arrows_color_hover',
			array(
				'label'     => esc_html__( 'Arrow Color (Hover)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrows_bg_color_hover',
			array(
				'label'     => esc_html__( 'Background Color (Hover)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrows_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color (Hover)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .dlm-swiper-nav-btn:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: Pagination Dots Style
		// ==========================================
		$this->start_controls_section(
			'section_dots_style',
			array(
				'label'     => esc_html__( 'Pagination Dots Style', 'digital-library-membership' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_dots' => 'yes',
				),
			)
		);

		$this->add_control(
			'dots_color',
			array(
				'label'     => esc_html__( 'Dots Inactive Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dots_active_color',
			array(
				'label'     => esc_html__( 'Dots Active Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'dots_size',
			array(
				'label'     => esc_html__( 'Dots Size (px)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 6,
						'max' => 20,
					),
				),
				'default'   => array(
					'size' => 10,
				),
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings       = $this->get_settings_for_display();
		$source         = ! empty( $settings['source'] ) ? $settings['source'] : 'dynamic';
		$bg_color       = ! empty( $settings['bg_color'] ) ? esc_attr( $settings['bg_color'] ) : 'rgba(133, 83, 0, 0.08)';
		$primary_color  = ! empty( $settings['primary_color'] ) ? esc_attr( $settings['primary_color'] ) : '#855300';
		$title_color    = ! empty( $settings['title_color'] ) ? esc_attr( $settings['title_color'] ) : '#1a1c1c';
		$desc_color     = ! empty( $settings['desc_color'] ) ? esc_attr( $settings['desc_color'] ) : 'rgba(26, 28, 28, 0.82)';

		$is_autoplay    = ( isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'] ) ? 'true' : 'false';
		$autoplay_delay = ! empty( $settings['autoplay_delay'] ) ? intval( $settings['autoplay_delay'] ) : 5000;
		$speed          = ! empty( $settings['speed'] ) ? intval( $settings['speed'] ) : 800;
		$is_loop        = ( isset( $settings['loop'] ) && 'yes' === $settings['loop'] ) ? 'true' : 'false';

		$show_arrows    = ( ! isset( $settings['show_arrows'] ) || 'yes' === $settings['show_arrows'] ) ? 'yes' : 'no';
		$show_dots      = ( ! isset( $settings['show_dots'] ) || 'yes' === $settings['show_dots'] ) ? 'yes' : 'no';

		if ( 'dynamic' === $source ) {
			$limit        = ! empty( $settings['dynamic_limit'] ) ? intval( $settings['dynamic_limit'] ) : 5;
			$books        = DLM_Books_Helper::get_books( $limit );
			$slides       = array();
			$checkout_url = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'checkout' ) : '#membership';

			foreach ( $books as $b ) {
				/* translators: %s: author name */
				$hero_subtitle = ! empty( $b['author'] ) ? sprintf( esc_html__( 'By %s', 'digital-library-membership' ), $b['author'] ) : esc_html__( 'Digital Edition', 'digital-library-membership' );
				$slides[] = array(
					'badge_text'         => esc_html__( 'FEATURED PUBLICATION', 'digital-library-membership' ),
					'title'              => $b['title'],
					'subtitle'           => $hero_subtitle,
					'description'        => ! empty( $b['description'] ) ? wp_trim_words( wp_strip_all_tags( $b['description'] ), 28 ) : '',
					'rating'             => ! empty( $b['rating'] ) ? $b['rating'] . ' / 5.0 (Verified)' : '5.0 / 5.0 (Verified)',
					'book_cover'         => array( 'url' => $b['cover_image_url'] ),
					'btn_read_text'      => esc_html__( 'Read Online Now', 'digital-library-membership' ),
					'btn_read_link'      => array( 'url' => $b['read_url'] ),
					'btn_secondary_text' => esc_html__( 'View Membership Plans', 'digital-library-membership' ),
					'btn_secondary_link' => array( 'url' => $checkout_url ),
				);
			}
		} else {
			$slides = ! empty( $settings['slides'] ) ? $settings['slides'] : array();
		}

		if ( empty( $slides ) ) {
			return;
		}

		$container_type  = ! empty( $settings['container_type'] ) ? $settings['container_type'] : 'boxed';
		$container_class = ( 'boxed' === $container_type ) ? 'dlm-hero-box-container dlm-hero-is-boxed' : 'dlm-hero-box-container dlm-hero-is-full';
		$nav_class       = ( 'yes' === $show_arrows ) ? 'has-arrows' : 'no-arrows';
		?>
		<div class="dlm-hero-section mipallab-hero-section" style="position: relative; overflow: hidden; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div class="<?php echo esc_attr( $container_class ); ?>" style="position: relative; margin: 0 auto; width: 100%; box-sizing: border-box;">
				<div class="swiper dlm-swiper-container mipallab-swiper-container <?php echo esc_attr( $nav_class ); ?>" 
					 data-speed="<?php echo esc_attr( $speed ); ?>" 
					 data-autoplay="<?php echo esc_attr( $is_autoplay ); ?>" 
					 data-delay="<?php echo esc_attr( $autoplay_delay ); ?>" 
					 data-loop="<?php echo esc_attr( $is_loop ); ?>" 
					 data-slides="1" 
					 data-slides-tablet="1" 
					 data-slides-mobile="1"
					 data-space="0"
					 data-space-mobile="0">
					<div class="swiper-wrapper">
						<?php foreach ( $slides as $slide ) : 
							$cover_url = ! empty( $slide['book_cover']['url'] ) ? esc_url( $slide['book_cover']['url'] ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png';
							$read_url  = ! empty( $slide['btn_read_link']['url'] ) ? esc_url( $slide['btn_read_link']['url'] ) : '#library';
							$sec_url   = ! empty( $slide['btn_secondary_link']['url'] ) ? esc_url( $slide['btn_secondary_link']['url'] ) : '#membership';
						?>
							<div class="swiper-slide" style="box-sizing: border-box;">
								<div class="dlm-hero-slide-grid">
									<div class="dlm-motion-fade-up dlm-hero-slide-text">
										<?php if ( ! empty( $slide['badge_text'] ) ) : ?>
											<div class="dlm-hero-badge mipallab-hero-badge" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(133, 83, 0, 0.12); color: <?php echo esc_attr( $primary_color ); ?>; padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 800; letter-spacing: 1px; margin-bottom: 20px;">
												<span>⭐</span> <?php echo esc_html( $slide['badge_text'] ); ?>
											</div>
										<?php endif; ?>

										<h1 class="dlm-hero-title mipallab-hero-title" style="font-size: clamp(2.2rem, 5vw, 3.8rem); font-weight: 800; color: <?php echo esc_attr( $title_color ); ?>; line-height: 1.15; margin: 0 0 16px 0; letter-spacing: -0.5px;">
											<?php echo esc_html( $slide['title'] ); ?>
										</h1>

										<?php if ( ! empty( $slide['subtitle'] ) ) : ?>
											<h3 class="dlm-hero-subtitle" style="font-size: 1.2rem; font-weight: 700; color: <?php echo esc_attr( $primary_color ); ?>; margin: 0 0 20px 0;">
												<?php echo esc_html( $slide['subtitle'] ); ?>
											</h3>
										<?php endif; ?>

										<?php if ( ! empty( $slide['description'] ) ) : ?>
											<p class="dlm-hero-desc" style="font-size: 1.08rem; color: <?php echo esc_attr( $desc_color ); ?>; line-height: 1.7; margin-bottom: 26px; max-width: 540px;">
												<?php echo esc_html( $slide['description'] ); ?>
											</p>
										<?php endif; ?>

										<?php if ( ! empty( $slide['rating'] ) ) : ?>
											<div class="dlm-hero-rating mipallab-hero-rating" style="display: flex; align-items: center; gap: 10px; margin-bottom: 30px; font-weight: 700; color: <?php echo esc_attr( $title_color ); ?>;">
												<div style="color: #f59e0b; font-size: 18px;">★★★★★</div>
												<span><?php echo esc_html( $slide['rating'] ); ?></span>
											</div>
										<?php endif; ?>

										<div class="dlm-hero-cta mipallab-hero-cta" style="display: flex; flex-wrap: wrap; gap: 16px;">
											<?php if ( ! empty( $slide['btn_read_text'] ) ) : ?>
												<a href="<?php echo esc_url( $read_url ); ?>" style="background: <?php echo esc_attr( $primary_color ); ?>; color: #ffffff; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 1.05rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(133, 83, 0, 0.25);">
													📖 <?php echo esc_html( $slide['btn_read_text'] ); ?>
												</a>
											<?php endif; ?>

											<?php if ( ! empty( $slide['btn_secondary_text'] ) ) : ?>
												<a href="<?php echo esc_url( $sec_url ); ?>" style="background: transparent; color: <?php echo esc_attr( $primary_color ); ?>; border: 2px solid <?php echo esc_attr( $primary_color ); ?>; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 1.05rem; text-decoration: none; display: inline-flex; align-items: center;">
													<?php echo esc_html( $slide['btn_secondary_text'] ); ?> →
												</a>
											<?php endif; ?>
										</div>
									</div>

									<div class="dlm-hero-slide-cover" style="position: relative; text-align: center;">
										<div class="dlm-motion-float" style="position: relative; display: inline-block;">
											<div style="position: absolute; inset: -15px; background: radial-gradient(circle, rgba(133, 83, 0, 0.22), transparent 70%); border-radius: 30px; filter: blur(20px); z-index: 1;"></div>
											<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>" style="position: relative; z-index: 2; max-width: 100%; max-height: 480px; width: auto; object-fit: contain; border-radius: 18px; box-shadow: 0 20px 45px rgba(0,0,0,0.2); transform: perspective(1000px) rotateY(-4deg) rotateX(2deg);" loading="lazy" />
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( 'yes' === $show_arrows ) : ?>
						<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-prev mipallab-swiper-nav-btn mipallab-swiper-nav-prev" aria-label="<?php esc_attr_e( 'Previous Slide', 'digital-library-membership' ); ?>">
							<svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
						</button>
						<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-next mipallab-swiper-nav-btn mipallab-swiper-nav-next" aria-label="<?php esc_attr_e( 'Next Slide', 'digital-library-membership' ); ?>">
							<svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</button>
					<?php endif; ?>

					<?php if ( 'yes' === $show_dots ) : ?>
						<div class="swiper-pagination"></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}

// Backward Compatibility Class Alias (hidden from panel)
if ( ! class_exists( 'Mipallab_Hero_Book_Slider_Widget' ) ) {
	class Mipallab_Hero_Book_Slider_Widget extends DLM_Widget_Hero_Book_Slider {
		public function get_name() {
			return 'mipallab_hero_book_slider';
		}
		public function show_in_panel() {
			return false;
		}
	}
}
