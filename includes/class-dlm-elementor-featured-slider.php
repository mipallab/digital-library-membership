<?php
/**
 * Elementor Featured Book Slider Widget Integration
 * Defines comprehensive controls, Swiper markup, countdown styling, cover resizing, and cache-safe dynamic hydration.
 *
 * @since      1.8.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Elementor_Featured_Slider extends \Elementor\Widget_Base {

	/**
	 * Get widget key identifier
	 */
	public function get_name() {
		return 'dlm-featured-slider';
	}

	/**
	 * Get widget user-facing label
	 */
	public function get_title() {
		return __( 'DLM Featured Book Slider', 'digital-library-membership' );
	}

	/**
	 * Get widget edit icon
	 */
	public function get_icon() {
		return 'eicon-post-slider';
	}

	/**
	 * Add categories to group widget in palette
	 */
	public function get_categories() {
		return array( 'digital-library', 'mipallab_category', 'general' );
	}

	/**
	 * Get style dependencies
	 */
	public function get_style_depends() {
		return array( 'swiper' );
	}

	/**
	 * Get script dependencies
	 */
	public function get_script_depends() {
		return array( 'swiper' );
	}

	/**
	 * Register control settings
	 */
	protected function register_controls() {
		// ==========================================
		// CONTENT TAB - SLIDER SETTINGS
		// ==========================================
		$this->start_controls_section(
			'section_slider_content',
			array(
				'label' => __( 'Slider Content & Behavior', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'book_source',
			array(
				'label'   => __( 'Book Source', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'featured' => __( 'Featured Books (Auto-query by Priority)', 'digital-library-membership' ),
					'manual'   => __( 'Manual Selection (Specific Books)', 'digital-library-membership' ),
				),
				'default' => 'featured',
			)
		);

		// Prepare books list for manual select
		$db = new DLM_DB();
		$all_books = $db->get_books( 'publish', true );
		$book_options = array();
		if ( ! empty( $all_books ) ) {
			foreach ( $all_books as $b ) {
				$book_options[ $b->id ] = '#' . $b->id . ' - ' . $b->title . ( ! empty( $b->author ) ? ' (' . $b->author . ')' : '' );
			}
		}

		$this->add_control(
			'manual_book_ids',
			array(
				'label'       => __( 'Select Books', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $book_options,
				'description' => __( 'Choose specific books to display in the slider.', 'digital-library-membership' ),
				'condition'   => array(
					'book_source' => 'manual',
				),
			)
		);

		$this->add_control(
			'slides_limit',
			array(
				'label'     => __( 'Maximum Featured Books', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 20,
				'step'      => 1,
				'default'   => 5,
				'condition' => array(
					'book_source' => 'featured',
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => __( 'Autoplay Delay (ms)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1000,
				'max'       => 30000,
				'step'      => 500,
				'default'   => 6000,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => __( 'Pause on Hover', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Infinite Loop', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => __( 'Transition Effect', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'slide' => __( 'Slide', 'digital-library-membership' ),
					'fade'  => __( 'Fade', 'digital-library-membership' ),
				),
				'default' => 'slide',
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Show Navigation Arrows', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'        => __( 'Show Pagination Dots', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => __( 'Show "Featured Book" Badge', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_author',
			array(
				'label'        => __( 'Show Author', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_floating_cover',
			array(
				'label'        => __( 'Show Book Cover Image', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_countdown',
			array(
				'label'        => __( 'Show Release Countdown for Upcoming Books', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - CONTAINER & DIMENSIONS
		// ==========================================
		$this->start_controls_section(
			'section_style_container',
			array(
				'label' => __( 'Slider Container & Height', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'slider_height',
			array(
				'label'      => __( 'Height', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 250,
						'max' => 900,
					),
					'vh' => array(
						'min' => 30,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 460,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-featured-swiper' => 'min-height: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .dlm-featured-slide'  => 'min-height: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'content_padding',
			array(
				'label'      => __( 'Content Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'default'    => array(
					'top'      => '40',
					'right'    => '60',
					'bottom'   => '40',
					'left'     => '60',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-slide-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'overlay_gradient',
			array(
				'label'     => __( 'Overlay Gradient / Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.78)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-overlay' => 'background: linear-gradient(to right, rgba(0,0,0,0.95) 0%, {{VALUE}} 55%, rgba(0,0,0,0.3) 100%) !important;',
				),
			)
		);

		$this->add_responsive_control(
			'slider_border_radius',
			array(
				'label'      => __( 'Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'default'    => array(
					'top'      => '24',
					'right'    => '24',
					'bottom'   => '24',
					'left'     => '24',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-featured-slider-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .dlm-featured-swiper'         => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'slider_box_shadow',
				'selector' => '{{WRAPPER}} .dlm-featured-slider-wrapper',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - TYPOGRAPHY & COLORS
		// ==========================================
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => __( 'Typography & Colors', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		// Badge Style
		$this->add_control(
			'heading_badge_style',
			array(
				'label'     => __( 'Badge', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .dlm-slide-badge, {{WRAPPER}} .dlm-slide-upcoming-badge',
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => __( 'Badge Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-badge' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => __( 'Badge Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-badge, {{WRAPPER}} .dlm-slide-upcoming-badge' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'badge_padding',
			array(
				'label'      => __( 'Badge Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-slide-badge, {{WRAPPER}} .dlm-slide-upcoming-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		// Title Style
		$this->add_control(
			'heading_title_style',
			array(
				'label'     => __( 'Book Title', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .dlm-slide-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-title' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'title_text_shadow',
				'selector' => '{{WRAPPER}} .dlm-slide-title',
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => __( 'Title Margin Bottom', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 10,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-slide-title' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// Author Style
		$this->add_control(
			'heading_author_style',
			array(
				'label'     => __( 'Author', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'author_typography',
				'selector' => '{{WRAPPER}} .dlm-slide-author',
			)
		);

		$this->add_control(
			'author_color',
			array(
				'label'     => __( 'Author Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fde68a',
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-author' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'author_margin',
			array(
				'label'      => __( 'Author Margin Bottom', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 14,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-slide-author' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// Description Style
		$this->add_control(
			'heading_desc_style',
			array(
				'label'     => __( 'Description / Blurb', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .dlm-slide-desc',
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => __( 'Description Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.88)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-desc' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'desc_line_clamp',
			array(
				'label'     => __( 'Maximum Lines to Show', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 10,
				'default'   => 3,
				'selectors' => array(
					'{{WRAPPER}} .dlm-slide-desc' => '-webkit-line-clamp: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'desc_margin',
			array(
				'label'      => __( 'Description Margin Bottom', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-slide-desc' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - COUNTDOWN TIMER (Full Customization)
		// ==========================================
		$this->start_controls_section(
			'section_style_countdown',
			array(
				'label' => __( 'Countdown Timer Style', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'countdown_container_bg',
			array(
				'label'     => __( 'Countdown Container Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(133, 83, 0, 0.92)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-timer' => 'background: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'countdown_container_border',
				'selector' => '{{WRAPPER}} .dlm-countdown-timer',
			)
		);

		$this->add_responsive_control(
			'countdown_container_radius',
			array(
				'label'      => __( 'Countdown Container Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-timer' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'countdown_container_padding',
			array(
				'label'      => __( 'Countdown Container Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => '6',
					'right'    => '6',
					'bottom'   => '6',
					'left'     => '6',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-timer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'countdown_box_gap',
			array(
				'label'      => __( 'Boxes Gap (Spacing)', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 2,
						'max' => 20,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 6,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-timer' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// Individual Box Style
		$this->add_control(
			'heading_countdown_box',
			array(
				'label'     => __( 'Individual Digit Boxes', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'countdown_box_bg',
			array(
				'label'     => __( 'Box Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.18)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-box' => 'background: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'countdown_box_border',
				'selector' => '{{WRAPPER}} .dlm-countdown-box',
			)
		);

		$this->add_responsive_control(
			'countdown_box_radius',
			array(
				'label'      => __( 'Box Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '8',
					'right'    => '8',
					'bottom'   => '8',
					'left'     => '8',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'countdown_box_min_width',
			array(
				'label'      => __( 'Box Min Width', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 30,
						'max' => 90,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 44,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-box' => 'min-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// Digits Typography
		$this->add_control(
			'heading_countdown_digits',
			array(
				'label'     => __( 'Digits Typography & Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'countdown_digits_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-timer .countdown-digits',
			)
		);

		$this->add_control(
			'countdown_digits_color',
			array(
				'label'     => __( 'Digits Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-timer .countdown-digits' => 'color: {{VALUE}} !important;',
				),
			)
		);

		// Labels Typography
		$this->add_control(
			'heading_countdown_labels',
			array(
				'label'     => __( 'Labels Typography & Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'countdown_labels_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-timer .countdown-label',
			)
		);

		$this->add_control(
			'countdown_labels_color',
			array(
				'label'     => __( 'Labels Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fde68a',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-timer .countdown-label' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - CTA BUTTONS (Primary & Secondary)
		// ==========================================
		$this->start_controls_section(
			'section_style_cta',
			array(
				'label' => __( 'CTA Buttons Style', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => '{{WRAPPER}} .dlm-btn-primary, {{WRAPPER}} .dlm-btn-secondary, {{WRAPPER}} .dlm-btn-release-info',
			)
		);

		$this->add_responsive_control(
			'btn_gap',
			array(
				'label'      => __( 'Buttons Gap', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 4,
						'max' => 30,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 14,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-featured-cta-wrap' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// Primary Button Tab
		$this->add_control(
			'heading_primary_btn',
			array(
				'label'     => __( 'Primary Button (Read / Action)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'tabs_primary_btn' );

		$this->start_controls_tab(
			'tab_primary_normal',
			array(
				'label' => __( 'Normal', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'btn_primary_bg',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-primary' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_primary_text',
			array(
				'label'     => __( 'Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1c1c',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-primary' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_primary_border',
				'selector' => '{{WRAPPER}} .dlm-btn-primary',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_primary_shadow',
				'selector' => '{{WRAPPER}} .dlm-btn-primary',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_primary_hover',
			array(
				'label' => __( 'Hover', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'btn_primary_bg_hover',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f8fafc',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-primary:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_primary_text_hover',
			array(
				'label'     => __( 'Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0f172a',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-primary:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_primary_border_hover',
			array(
				'label'     => __( 'Border Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-primary:hover' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_primary_shadow_hover',
				'selector' => '{{WRAPPER}} .dlm-btn-primary:hover',
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_primary_padding',
			array(
				'label'      => __( 'Primary Button Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '28',
					'bottom'   => '12',
					'left'     => '28',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-btn-primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'btn_primary_radius',
			array(
				'label'      => __( 'Primary Button Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-btn-primary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		// Secondary Button Tab
		$this->add_control(
			'heading_secondary_btn',
			array(
				'label'     => __( 'Secondary Button (Pricing / Info)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'tabs_secondary_btn' );

		$this->start_controls_tab(
			'tab_secondary_normal',
			array(
				'label' => __( 'Normal', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'btn_secondary_bg',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.18)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-secondary' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_secondary_text',
			array(
				'label'     => __( 'Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-secondary' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_secondary_border',
				'selector' => '{{WRAPPER}} .dlm-btn-secondary',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_secondary_hover',
			array(
				'label' => __( 'Hover', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'btn_secondary_bg_hover',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.32)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-secondary:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_secondary_text_hover',
			array(
				'label'     => __( 'Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-btn-secondary:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_secondary_padding',
			array(
				'label'      => __( 'Secondary Button Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '22',
					'bottom'   => '12',
					'left'     => '22',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-btn-secondary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'btn_secondary_radius',
			array(
				'label'      => __( 'Secondary Button Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-btn-secondary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - BOOK COVER IMAGE SIZING & 3D
		// ==========================================
		$this->start_controls_section(
			'section_style_cover',
			array(
				'label' => __( 'Book Cover Image Sizing & 3D', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'cover_width',
			array(
				'label'      => __( 'Cover Width', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min' => 80,
						'max' => 500,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 170,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-floating-cover' => 'width: {{SIZE}}{{UNIT}} !important; max-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'cover_height',
			array(
				'label'      => __( 'Cover Height', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 100,
						'max' => 650,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 245,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-floating-cover' => 'height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'cover_border_radius',
			array(
				'label'      => __( 'Cover Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '14',
					'right'    => '14',
					'bottom'   => '14',
					'left'     => '14',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-floating-cover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .dlm-floating-cover img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'cover_box_shadow',
				'selector' => '{{WRAPPER}} .dlm-floating-cover',
			)
		);

		$this->add_control(
			'enable_3d_tilt',
			array(
				'label'        => __( 'Enable 3D Perspective Tilt', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'cover_rotate_y',
			array(
				'label'     => __( '3D Tilt Y (Degrees)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => -45,
						'max' => 45,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => -10,
				),
				'condition' => array(
					'enable_3d_tilt' => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .dlm-floating-cover' => 'transform: perspective(800px) rotateY({{SIZE}}deg) rotateX(4deg) !important;',
				),
			)
		);

		$this->add_control(
			'cover_hover_scale',
			array(
				'label'     => __( 'Hover Zoom Scale', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 1.0,
						'max'  => 1.3,
						'step' => 0.02,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 1.05,
				),
				'selectors' => array(
					'{{WRAPPER}} .dlm-floating-cover:hover' => 'transform: perspective(800px) rotateY(0deg) rotateX(0deg) scale({{SIZE}}) !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - NAVIGATION & PAGINATION
		// ==========================================
		$this->start_controls_section(
			'section_style_nav',
			array(
				'label' => __( 'Navigation Arrows & Dots', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrows_color',
			array(
				'label'     => __( 'Arrows Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-prev, {{WRAPPER}} .swiper-button-next' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .swiper-button-prev:after, {{WRAPPER}} .swiper-button-next:after' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'arrows_bg_color',
			array(
				'label'     => __( 'Arrows Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.4)',
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-prev, {{WRAPPER}} .swiper-button-next' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'dots_active_color',
			array(
				'label'     => __( 'Active Dot Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'dots_inactive_color',
			array(
				'label'     => __( 'Inactive Dots Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.5)',
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$db = new DLM_DB();
		$featured_books = array();

		if ( ! empty( $settings['book_source'] ) && $settings['book_source'] === 'manual' ) {
			$raw_manual_ids = ! empty( $settings['manual_book_ids'] ) ? (array) $settings['manual_book_ids'] : array();
			$sanitized_ids = array_filter( array_map( 'intval', $raw_manual_ids ) );
			foreach ( $sanitized_ids as $m_id ) {
				if ( $m_id > 0 ) {
					$bk = $db->get_book( $m_id );
					if ( $bk ) {
						$featured_books[] = $bk;
					}
				}
			}
		} else {
			$limit = ! empty( $settings['slides_limit'] ) ? intval( $settings['slides_limit'] ) : 5;
			$featured_books = $db->get_featured_books( $limit );
			if ( empty( $featured_books ) ) {
				$published_books = $db->get_books( 'publish', true );
				if ( ! empty( $published_books ) ) {
					$featured_books = array_slice( $published_books, 0, $limit );
				}
			}
		}

		if ( empty( $featured_books ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="dlm-featured-empty-notice" style="padding:40px; text-align:center; background:#f9f9f9; border:2px dashed #ddd; border-radius:16px;">' . esc_html__( 'No published or featured books found to display in the slider.', 'digital-library-membership' ) . '</div>';
			}
			return;
		}

		$slider_id = 'dlm-featured-slider-' . $this->get_id();
		$total_slides = count( $featured_books );

		$swiper_options = array(
			'loop'           => $settings['loop'] === 'yes' && $total_slides > 1,
			'effect'         => ! empty( $settings['effect'] ) ? $settings['effect'] : 'slide',
			'speed'          => 700,
			'grabCursor'     => true,
			'observer'       => true,
			'observeParents' => true,
		);

		if ( $settings['autoplay'] === 'yes' && $total_slides > 1 ) {
			$swiper_options['autoplay'] = array(
				'delay'                => ! empty( $settings['autoplay_delay'] ) ? intval( $settings['autoplay_delay'] ) : 6000,
				'disableOnInteraction' => false,
				'pauseOnMouseEnter'    => $settings['pause_on_hover'] === 'yes',
			);
		}

		if ( $settings['show_arrows'] === 'yes' && $total_slides > 1 ) {
			$swiper_options['navigation'] = array(
				'nextEl' => '#' . $slider_id . ' .swiper-button-next',
				'prevEl' => '#' . $slider_id . ' .swiper-button-prev',
			);
		}

		if ( $settings['show_dots'] === 'yes' && $total_slides > 1 ) {
			$swiper_options['pagination'] = array(
				'el'        => '#' . $slider_id . ' .swiper-pagination',
				'clickable' => true,
			);
		}

		$currency = get_option( 'dlm_currency', 'USD' );
		$pricing_url = get_permalink( get_option( 'dlm_pricing_page_id' ) );
		if ( ! $pricing_url ) {
			$pricing_url = home_url( '/pricing/' );
		}
		$public_nonce = wp_create_nonce( 'dlm_public_nonce' );
		?>
		<div class="dlm-featured-slider-wrapper" id="<?php echo esc_attr( $slider_id ); ?>" data-slider-settings="<?php echo esc_attr( wp_json_encode( $swiper_options ) ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( $public_nonce ); ?>" style="position:relative; overflow:hidden; width:100%;">
			<!-- Dual-class swiper container for full compatibility with Elementor 3.0 to 3.25+ -->
			<div class="swiper swiper-container dlm-featured-swiper" style="width:100%; position:relative;">
				<div class="swiper-wrapper">
					<?php foreach ( $featured_books as $fb ) : 
						$f_title = ! empty( $fb->featured_title ) ? $fb->featured_title : $fb->title;
						$f_desc  = ! empty( $fb->featured_description ) ? $fb->featured_description : ( ! empty( $fb->description ) ? wp_strip_all_tags( $fb->description ) : '' );
						$f_banner = ! empty( $fb->featured_banner_url ) ? $fb->featured_banner_url : ( ! empty( $fb->cover_image_url ) ? $fb->cover_image_url : DLM_URL . 'public/images/featured_hero.png' );
						$f_cover = ! empty( $fb->cover_image_url ) ? $fb->cover_image_url : '';
						
						$f_price = isset( $fb->price ) ? floatval( $fb->price ) : 0.00;
						$f_is_future = ( ! empty( $fb->publish_date ) && strtotime( $fb->publish_date ) > current_time( 'timestamp' ) ) || ( isset( $fb->status ) && $fb->status === 'future' );
						$f_publish_iso = ! empty( $fb->publish_date ) ? wp_date( 'c', strtotime( $fb->publish_date ) ) : '';
						if ( empty( $f_publish_iso ) && ! empty( $fb->publish_date ) ) {
							$f_publish_iso = str_replace( ' ', 'T', trim( $fb->publish_date ) );
						}
						/* translators: %s: scheduled release date */
						$release_date_str = sprintf( __( 'Releases %s', 'digital-library-membership' ), $f_publish_fmt );
						$btn1_label = ! empty( $fb->featured_button_1_label ) ? $fb->featured_button_1_label : ( $f_is_future ? $release_date_str : __( 'Read Book', 'digital-library-membership' ) );
						$btn2_label = ! empty( $fb->featured_button_2_label ) ? $fb->featured_button_2_label : __( 'Add to Favorites', 'digital-library-membership' );
					?>
						<div class="swiper-slide dlm-featured-slide" data-book-id="<?php echo intval( $fb->id ); ?>" style="position:relative; width:100%; display:flex; align-items:center; overflow:hidden; background:#111;">
							<!-- Banner Background Image with Deep Gradient -->
							<div class="dlm-slide-bg" style="position:absolute; inset:0; z-index:1;">
								<img src="<?php echo esc_url( $f_banner ); ?>" alt="<?php echo esc_attr( $f_title ); ?>" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
								<div class="dlm-slide-overlay" style="position:absolute; inset:0;"></div>
							</div>

							<!-- Slide Content Container -->
							<div class="dlm-slide-content" style="position:relative; z-index:2; width:100%; height:100%; display:flex; justify-content:space-between; align-items:center; box-sizing:border-box;">
								<!-- Left Column: Blurb, Title, Author & CTAs -->
								<div class="dlm-slide-left" style="max-width:650px; display:flex; flex-direction:column; justify-content:center; flex:1;">
									<div class="dlm-slide-badges" style="display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
										<?php if ( $settings['show_badge'] === 'yes' ) : ?>
											<span class="dlm-slide-badge" style="display:inline-flex; align-items:center; gap:5px; border-radius:50px; font-weight:800; text-transform:uppercase; font-size:11px; padding:4px 12px; letter-spacing:0.8px;">
												★ <?php esc_html_e( 'Featured Book', 'digital-library-membership' ); ?>
											</span>
										<?php endif; ?>

										<?php if ( $f_is_future ) : ?>
											<span class="dlm-slide-upcoming-badge" style="display:inline-flex; align-items:center; gap:5px; background:rgba(217, 119, 6, 0.9); color:#ffffff; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; padding:4px 12px; border-radius:50px; box-shadow:0 2px 8px rgba(0,0,0,0.2);">
												⏱ <?php esc_html_e( 'Upcoming Release', 'digital-library-membership' ); ?>
											</span>
										<?php endif; ?>
									</div>

									<h2 class="dlm-slide-title" style="margin:0 0 10px 0;">
										<?php echo esc_html( $f_title ); ?>
									</h2>

									<?php if ( $settings['show_author'] === 'yes' && ! empty( $fb->author ) ) : ?>
										<p class="dlm-slide-author" style="margin:0 0 14px 0; font-weight:600;">
											<?php esc_html_e( 'by', 'digital-library-membership' ); ?> <?php echo esc_html( $fb->author ); ?>
										</p>
									<?php endif; ?>

									<p class="dlm-slide-desc" style="margin:0 0 24px 0; display:-webkit-box; -webkit-box-orient:vertical; overflow:hidden;">
										<?php echo esc_html( $f_desc ); ?>
									</p>

									<!-- Cache-Safe CTA Container (Dynamically hydrated on client-side) -->
									<div class="dlm-featured-cta-wrap" data-book-id="<?php echo intval( $fb->id ); ?>" style="display:flex; align-items:center; flex-wrap:wrap;">
										<?php if ( $f_is_future && ( empty( $settings['show_countdown'] ) || $settings['show_countdown'] === 'yes' ) ) : ?>
											<!-- 4-Box Countdown Timer -->
											<div class="dlm-countdown-timer dlm-featured-countdown shrink-0" data-release-time="<?php echo esc_attr( $f_publish_iso ); ?>" data-book-id="<?php echo esc_attr( $fb->id ); ?>" style="display:grid; grid-template-columns:repeat(4, 1fr); backdrop-filter:blur(8px);">
												<div class="dlm-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:4px 8px;">
													<span class="countdown-days countdown-digits font-mono" style="font-weight:800; font-size:14px; line-height:1;">00</span>
													<span class="countdown-label" style="font-size:8px; text-transform:uppercase; font-weight:700; line-height:1; margin-top:2px;"><?php esc_html_e( 'Day', 'digital-library-membership' ); ?></span>
												</div>
												<div class="dlm-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:4px 8px;">
													<span class="countdown-hours countdown-digits font-mono" style="font-weight:800; font-size:14px; line-height:1;">00</span>
													<span class="countdown-label" style="font-size:8px; text-transform:uppercase; font-weight:700; line-height:1; margin-top:2px;"><?php esc_html_e( 'Hr', 'digital-library-membership' ); ?></span>
												</div>
												<div class="dlm-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:4px 8px;">
													<span class="countdown-minutes countdown-digits font-mono" style="font-weight:800; font-size:14px; line-height:1;">00</span>
													<span class="countdown-label" style="font-size:8px; text-transform:uppercase; font-weight:700; line-height:1; margin-top:2px;"><?php esc_html_e( 'Min', 'digital-library-membership' ); ?></span>
												</div>
												<div class="dlm-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:4px 8px;">
													<span class="countdown-seconds countdown-digits font-mono" style="font-weight:800; font-size:14px; line-height:1;">00</span>
													<span class="countdown-label" style="font-size:8px; text-transform:uppercase; font-weight:700; line-height:1; margin-top:2px;"><?php esc_html_e( 'Sec', 'digital-library-membership' ); ?></span>
												</div>
											</div>
											<span class="dlm-btn-release-info" style="display:inline-flex; align-items:center; padding:10px 18px; background:rgba(255,255,255,0.18); backdrop-filter:blur(6px); color:#ffffff; font-weight:700; font-size:13px; border-radius:12px; border:1px solid rgba(255,255,255,0.2);">
												<?php echo esc_html( $btn1_label ); ?>
											</span>
										<?php else : ?>
											<a href="<?php echo esc_url( home_url( '/read/' . $fb->id . '/' ) ); ?>" class="dlm-btn-primary dlm-btn-cta-1" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:all 0.2s ease;">
												<span><?php echo esc_html( $btn1_label ); ?></span>
											</a>
										<?php endif; ?>

										<a href="<?php echo esc_url( $pricing_url ); ?>" class="dlm-btn-secondary dlm-btn-cta-2" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:all 0.2s ease;">
											<span><?php echo esc_html( $btn2_label ); ?></span>
										</a>
									</div>
								</div>

								<!-- Right Column: Resizable 3D Perspective Floating Book Cover -->
								<?php if ( $settings['show_floating_cover'] === 'yes' && ! empty( $f_cover ) ) : ?>
									<div class="dlm-slide-right" style="display:flex; align-items:center; justify-content:center; padding-left:24px; flex-shrink:0;">
										<div class="dlm-floating-cover" style="position:relative; overflow:hidden; transition:transform 0.4s ease;">
											<img src="<?php echo esc_url( $f_cover ); ?>" alt="<?php echo esc_attr( $f_title ); ?>" style="width:100%; height:100%; object-fit:cover; display:block;" loading="lazy">
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Navigation Controls -->
				<?php if ( $settings['show_arrows'] === 'yes' && $total_slides > 1 ) : ?>
					<div class="swiper-button-prev" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;"></div>
					<div class="swiper-button-next" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;"></div>
				<?php endif; ?>

				<?php if ( $settings['show_dots'] === 'yes' && $total_slides > 1 ) : ?>
					<div class="swiper-pagination" style="bottom:14px;"></div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Client-Side Cache-Safe Dynamic Hydration Script -->
		<script>
		(function() {
			function initSliderElement() {
				var container = document.getElementById('<?php echo esc_js( $slider_id ); ?>');
				if (!container || container.dataset.initialized === 'true') return;
				container.dataset.initialized = 'true';

				var swiperElem = container.querySelector('.swiper, .swiper-container');
				if (!swiperElem) return;

				var rawSettings = container.getAttribute('data-slider-settings');
				var options = {};
				try {
					options = JSON.parse(rawSettings) || {};
				} catch(e) {}

				// Initialize Swiper instance
				if (typeof Swiper !== 'undefined') {
					new Swiper(swiperElem, options);
				} else if (window.elementorFrontend && window.elementorFrontend.utils && window.elementorFrontend.utils.swiper) {
					new window.elementorFrontend.utils.swiper(swiperElem, options);
				}

				// Cache-Safe CTA Dynamic Hydration via AJAX
				var bookIds = [];
				var ctaWrappers = container.querySelectorAll('.dlm-featured-cta-wrap');
				ctaWrappers.forEach(function(wrap) {
					var bid = wrap.getAttribute('data-book-id');
					if (bid && bookIds.indexOf(bid) === -1) {
						bookIds.push(bid);
					}
				});

				if (bookIds.length > 0 && typeof jQuery !== 'undefined') {
					var ajaxUrl = container.getAttribute('data-ajax-url') || '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
					var nonce = container.getAttribute('data-nonce') || (window.dlmParams && window.dlmParams.nonce) || '';
					jQuery.post(ajaxUrl, {
						action: 'dlm_get_user_featured_access',
						book_ids: bookIds,
						nonce: nonce
					}, function(res) {
						if (res && res.success && res.data && res.data.access_map) {
							var map = res.data.access_map;
							ctaWrappers.forEach(function(wrap) {
								var bid = wrap.getAttribute('data-book-id');
								var bookData = map[bid];
								if (!bookData) return;

								// If not a future release, update CTA dynamically based on live access
								if (!bookData.is_future) {
									var btn1 = wrap.querySelector('.dlm-btn-cta-1');
									if (btn1) {
										var targetHref = bookData.target_url || bookData.reader_url;
										btn1.setAttribute('href', targetHref);
										btn1.innerHTML = '<span>' + (bookData.btn1_label || 'Read Now') + '</span>';
										btn1.style.opacity = '1';
									}
								}
							});
						}
					});
				}

				// Countdown timer updater
				function parseReleaseTime(str) {
					if (!str) return 0;
					var s = String(str).trim();
					if (s.indexOf(' ') > 0 && s.indexOf('T') === -1) s = s.replace(' ', 'T');
					var parsed = Date.parse(s);
					if (!isNaN(parsed) && parsed > 0) return parsed;
					var d = new Date(str);
					var t = d.getTime();
					return isNaN(t) ? 0 : t;
				}

				function updateCountdowns() {
					var now = new Date().getTime();
					var timers = container.querySelectorAll('.dlm-countdown-timer');
					timers.forEach(function(timer) {
						var releaseIso = timer.getAttribute('data-release-time');
						if (!releaseIso) return;
						var targetTime = parseReleaseTime(releaseIso);
						if (!targetTime) return;
						var distance = targetTime - now;

						if (distance <= 0) {
							timer.querySelectorAll('.countdown-days, .countdown-hours, .countdown-minutes, .countdown-seconds').forEach(function(el) {
								el.textContent = '00';
							});
							return;
						}

						var days = Math.floor(distance / (1000 * 60 * 60 * 24));
						var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
						var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
						var seconds = Math.floor((distance % (1000 * 60)) / 1000);

						var dEl = timer.querySelector('.countdown-days');
						var hEl = timer.querySelector('.countdown-hours');
						var mEl = timer.querySelector('.countdown-minutes');
						var sEl = timer.querySelector('.countdown-seconds');

						if (dEl) dEl.textContent = days < 10 ? '0' + days : '' + days;
						if (hEl) hEl.textContent = hours < 10 ? '0' + hours : '' + hours;
						if (mEl) mEl.textContent = minutes < 10 ? '0' + minutes : '' + minutes;
						if (sEl) sEl.textContent = seconds < 10 ? '0' + seconds : '' + seconds;
					});
				}

				updateCountdowns();
				setInterval(updateCountdowns, 1000);
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initSliderElement);
			} else {
				initSliderElement();
			}

			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/dlm-featured-slider.default', function() {
					initSliderElement();
				});
			}
		})();
		</script>
		<?php
	}
}
