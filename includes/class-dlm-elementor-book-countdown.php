<?php
/**
 * Elementor Book Countdown Widget Integration
 * Dedicated standalone countdown widget for upcoming book releases with comprehensive styling controls.
 *
 * @since      1.9.0
 * @package    DLM
 * @subpackage DLM/includes
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Elementor_Book_Countdown extends \Elementor\Widget_Base {

	/**
	 * Get widget key identifier
	 */
	public function get_name() {
		return 'dlm-book-countdown';
	}

	/**
	 * Get widget user-facing label
	 */
	public function get_title() {
		return __( 'DLM Book Countdown', 'digital-library-membership' );
	}

	/**
	 * Get widget edit icon
	 */
	public function get_icon() {
		return 'eicon-countdown';
	}

	/**
	 * Add categories to group widget in palette
	 */
	public function get_categories() {
		return array( 'digital-library', 'mipallab_category', 'general' );
	}

	/**
	 * Register control settings
	 */
	protected function register_controls() {
		// ==========================================
		// CONTENT TAB - TARGET & CONTENT
		// ==========================================
		$this->start_controls_section(
			'section_target_content',
			array(
				'label' => __( 'Countdown Target & Book', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'countdown_source',
			array(
				'label'   => __( 'Countdown Source', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'book'   => __( 'Select a Library Book', 'digital-library-membership' ),
					'custom' => __( 'Custom Release Date & Time', 'digital-library-membership' ),
				),
				'default' => 'book',
			)
		);

		// Prepare books list
		$db = new DLM_DB();
		$all_books = $db->get_books( 'publish', true );
		$book_options = array();
		if ( ! empty( $all_books ) ) {
			foreach ( $all_books as $b ) {
				$rel_str = ! empty( $b->publish_date ) ? ' (' . date_i18n( 'M d, Y', strtotime( $b->publish_date ) ) . ')' : '';
				$book_options[ $b->id ] = '#' . $b->id . ' - ' . $b->title . $rel_str;
			}
		}

		$this->add_control(
			'book_id',
			array(
				'label'       => __( 'Select Book', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $book_options,
				'description' => __( 'Select an upcoming or scheduled book to count down to its release date.', 'digital-library-membership' ),
				'condition'   => array(
					'countdown_source' => 'book',
				),
			)
		);

		$this->add_control(
			'custom_target_date',
			array(
				'label'       => __( 'Target Date & Time', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::DATE_TIME,
				'default'     => wp_date( 'Y-m-d H:i', strtotime( '+7 days' ) ),
				'description' => __( 'Set the target date and time for the countdown.', 'digital-library-membership' ),
				'condition'   => array(
					'countdown_source' => 'custom',
				),
			)
		);

		$this->add_control(
			'heading_type',
			array(
				'label'   => __( 'Heading Source', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'book_title' => __( 'Use Book Title', 'digital-library-membership' ),
					'custom'     => __( 'Custom Heading Text', 'digital-library-membership' ),
				),
				'default' => 'book_title',
			)
		);

		$this->add_control(
			'custom_title',
			array(
				'label'       => __( 'Heading Text', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Upcoming Book Release', 'digital-library-membership' ),
				'placeholder' => __( 'Enter title here', 'digital-library-membership' ),
				'condition'   => array(
					'heading_type' => 'custom',
				),
			)
		);

		$this->add_control(
			'show_subtitle',
			array(
				'label'        => __( 'Show Pre-Title / Subtitle', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'subtitle_text',
			array(
				'label'     => __( 'Subtitle Text', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'COUNTDOWN TO DIGITAL RELEASE', 'digital-library-membership' ),
				'condition' => array(
					'show_subtitle' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => __( 'Show Description', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'description_text',
			array(
				'label'       => __( 'Description Text', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => __( 'Get ready! This exclusive publication will unlock in our digital library when the timer reaches zero.', 'digital-library-membership' ),
				'placeholder' => __( 'Enter description', 'digital-library-membership' ),
				'condition'   => array(
					'show_description' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_cover',
			array(
				'label'        => __( 'Show Book Cover', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'custom_cover_image',
			array(
				'label'     => __( 'Custom Cover Image', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'condition' => array(
					'show_cover'       => 'yes',
					'countdown_source' => 'custom',
				),
			)
		);

		$this->add_control(
			'layout_style',
			array(
				'label'   => __( 'Card Layout', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'horizontal' => __( 'Side by Side (Cover + Countdown)', 'digital-library-membership' ),
					'stacked'    => __( 'Stacked Center (Hero Card)', 'digital-library-membership' ),
				),
				'default' => 'horizontal',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// CONTENT TAB - UNITS & LABELS
		// ==========================================
		$this->start_controls_section(
			'section_units_labels',
			array(
				'label' => __( 'Units & Labels', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_days',
			array(
				'label'        => __( 'Show Days', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_days',
			array(
				'label'     => __( 'Days Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Days', 'digital-library-membership' ),
				'condition' => array(
					'show_days' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_hours',
			array(
				'label'        => __( 'Show Hours', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_hours',
			array(
				'label'     => __( 'Hours Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Hours', 'digital-library-membership' ),
				'condition' => array(
					'show_hours' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_minutes',
			array(
				'label'        => __( 'Show Minutes', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_minutes',
			array(
				'label'     => __( 'Minutes Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Minutes', 'digital-library-membership' ),
				'condition' => array(
					'show_minutes' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_seconds',
			array(
				'label'        => __( 'Show Seconds', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_seconds',
			array(
				'label'     => __( 'Seconds Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Seconds', 'digital-library-membership' ),
				'condition' => array(
					'show_seconds' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// CONTENT TAB - EXPIRY & ACTIONS
		// ==========================================
		$this->start_controls_section(
			'section_expiry_actions',
			array(
				'label' => __( 'Expiry & Action Buttons', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'expiry_message',
			array(
				'label'   => __( 'Message When Released / Expired', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( '🎉 This book is now live in the library!', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'show_action_btn',
			array(
				'label'        => __( 'Show Action Button', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'digital-library-membership' ),
				'label_off'    => __( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'action_btn_text',
			array(
				'label'     => __( 'Button Text', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Explore Library', 'digital-library-membership' ),
				'condition' => array(
					'show_action_btn' => 'yes',
				),
			)
		);

		$this->add_control(
			'action_btn_url',
			array(
				'label'       => __( 'Button Link', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://yoursite.com/library/',
				'condition'   => array(
					'show_action_btn' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - CONTAINER / CARD
		// ==========================================
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => __( 'Container Card', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => __( 'Card Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1c1c',
				'selectors' => array(
					'{{WRAPPER}} .dlm-book-countdown-card' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .dlm-book-countdown-card',
			)
		);

		$this->add_responsive_control(
			'card_border_radius',
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
					'{{WRAPPER}} .dlm-book-countdown-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'default'    => array(
					'top'      => '40',
					'right'    => '48',
					'bottom'   => '40',
					'left'     => '48',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-book-countdown-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .dlm-book-countdown-card',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - COUNTDOWN BOXES
		// ==========================================
		$this->start_controls_section(
			'section_style_timer_boxes',
			array(
				'label' => __( 'Countdown Digit Boxes', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'timer_box_bg',
			array(
				'label'     => __( 'Digit Box Background', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.12)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-single-countdown-box' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'timer_box_border',
				'selector' => '{{WRAPPER}} .dlm-single-countdown-box',
			)
		);

		$this->add_responsive_control(
			'timer_box_radius',
			array(
				'label'      => __( 'Box Border Radius', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'default'    => array(
					'top'      => '16',
					'right'    => '16',
					'bottom'   => '16',
					'left'     => '16',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-single-countdown-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'timer_box_min_width',
			array(
				'label'      => __( 'Box Min Width', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 45,
						'max' => 150,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 75,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-single-countdown-box' => 'min-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'timer_box_padding',
			array(
				'label'      => __( 'Box Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '14',
					'bottom'   => '12',
					'left'     => '14',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-single-countdown-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'timer_boxes_gap',
			array(
				'label'      => __( 'Gap Between Boxes', 'digital-library-membership' ),
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
					'size' => 12,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-book-countdown-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'timer_box_shadow',
				'selector' => '{{WRAPPER}} .dlm-single-countdown-box',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - DIGITS & LABELS TYPOGRAPHY
		// ==========================================
		$this->start_controls_section(
			'section_style_digits_labels',
			array(
				'label' => __( 'Digits & Labels Typography', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		// Digits
		$this->add_control(
			'heading_digits_style',
			array(
				'label' => __( 'Digits (Numbers)', 'digital-library-membership' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'digits_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-number',
			)
		);

		$this->add_control(
			'digits_color',
			array(
				'label'     => __( 'Digits Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-number' => 'color: {{VALUE}} !important;',
				),
			)
		);

		// Labels
		$this->add_control(
			'heading_labels_style',
			array(
				'label'     => __( 'Labels (Days, Hours, Min, Sec)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'labels_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-unit-label',
			)
		);

		$this->add_control(
			'labels_color',
			array(
				'label'     => __( 'Labels Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fde68a',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-unit-label' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'labels_margin_top',
			array(
				'label'      => __( 'Labels Margin Top', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 4,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-unit-label' => 'margin-top: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - HEADINGS & TEXT
		// ==========================================
		$this->start_controls_section(
			'section_style_headings',
			array(
				'label' => __( 'Headings & Text Typography', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		// Subtitle
		$this->add_control(
			'heading_subtitle_style',
			array(
				'label' => __( 'Subtitle', 'digital-library-membership' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-subtitle',
			)
		);

		$this->add_control(
			'subtitle_color',
			array(
				'label'     => __( 'Subtitle Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f59e0b',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-subtitle' => 'color: {{VALUE}} !important;',
				),
			)
		);

		// Title
		$this->add_control(
			'heading_main_title_style',
			array(
				'label'     => __( 'Main Title', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'main_title_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-main-title',
			)
		);

		$this->add_control(
			'main_title_color',
			array(
				'label'     => __( 'Main Title Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-main-title' => 'color: {{VALUE}} !important;',
				),
			)
		);

		// Description
		$this->add_control(
			'heading_description_style',
			array(
				'label'     => __( 'Description', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-description',
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Description Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.8)',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-description' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - BOOK COVER SIZING & 3D
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
					'size' => 180,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-cover-wrap' => 'width: {{SIZE}}{{UNIT}} !important; max-width: {{SIZE}}{{UNIT}} !important;',
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
					'size' => 260,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-cover-wrap' => 'height: {{SIZE}}{{UNIT}} !important;',
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
					'{{WRAPPER}} .dlm-countdown-cover-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .dlm-countdown-cover-wrap img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'cover_box_shadow',
				'selector' => '{{WRAPPER}} .dlm-countdown-cover-wrap',
			)
		);

		$this->add_control(
			'cover_3d_tilt',
			array(
				'label'        => __( '3D Perspective Tilt', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
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
					'size' => -8,
				),
				'condition' => array(
					'cover_3d_tilt' => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-cover-wrap' => 'transform: perspective(800px) rotateY({{SIZE}}deg) rotateX(4deg) !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB - ACTION BUTTON
		// ==========================================
		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => __( 'Action Button', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => '{{WRAPPER}} .dlm-countdown-action-btn',
			)
		);

		$this->start_controls_tabs( 'tabs_btn_style' );

		$this->start_controls_tab(
			'tab_btn_normal',
			array(
				'label' => __( 'Normal', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#855300',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-action-btn' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_text_color',
			array(
				'label'     => __( 'Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-action-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_border',
				'selector' => '{{WRAPPER}} .dlm-countdown-action-btn',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_shadow',
				'selector' => '{{WRAPPER}} .dlm-countdown-action-btn',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_btn_hover',
			array(
				'label' => __( 'Hover', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'btn_bg_color_hover',
			array(
				'label'     => __( 'Background Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6b4200',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-action-btn:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_text_color_hover',
			array(
				'label'     => __( 'Text Color', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .dlm-countdown-action-btn:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => __( 'Padding', 'digital-library-membership' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => '14',
					'right'    => '32',
					'bottom'   => '14',
					'left'     => '32',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .dlm-countdown-action-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'btn_border_radius',
			array(
				'label'      => __( 'Border Radius', 'digital-library-membership' ),
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
					'{{WRAPPER}} .dlm-countdown-action-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on frontend
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$db = new DLM_DB();

		$book = null;
		$title = '';
		$cover_url = '';
		$target_iso = '';

		if ( $settings['countdown_source'] === 'book' && ! empty( $settings['book_id'] ) ) {
			$book = $db->get_book( intval( $settings['book_id'] ) );
			if ( $book ) {
				$title = ( $settings['heading_type'] === 'custom' && ! empty( $settings['custom_title'] ) ) ? $settings['custom_title'] : $book->title;
				$cover_url = ! empty( $book->cover_image_url ) ? $book->cover_image_url : DLM_URL . 'public/images/recommendation_cover.png';
				if ( ! empty( $book->publish_date ) ) {
					$target_iso = wp_date( 'c', strtotime( $book->publish_date ) );
				}
			}
		} else {
			$title = ! empty( $settings['custom_title'] ) ? $settings['custom_title'] : __( 'Upcoming Release', 'digital-library-membership' );
			if ( ! empty( $settings['custom_cover_image']['url'] ) ) {
				$cover_url = $settings['custom_cover_image']['url'];
			}
			if ( ! empty( $settings['custom_target_date'] ) ) {
				$target_iso = wp_date( 'c', strtotime( $settings['custom_target_date'] ) );
			}
		}

		if ( empty( $target_iso ) ) {
			$target_iso = wp_date( 'c', strtotime( '+7 days' ) );
		}

		$widget_id = 'dlm-countdown-' . $this->get_id();
		$layout = ! empty( $settings['layout_style'] ) ? $settings['layout_style'] : 'horizontal';
		?>
		<div class="dlm-book-countdown-wrapper" id="<?php echo esc_attr( $widget_id ); ?>" data-target-time="<?php echo esc_attr( $target_iso ); ?>" style="width:100%; box-sizing:border-box;">
			<div class="dlm-book-countdown-card dlm-layout-<?php echo esc_attr( $layout ); ?>" style="display:flex; <?php echo $layout === 'stacked' ? 'flex-direction:column; text-align:center; align-items:center;' : 'flex-direction:row; align-items:center; justify-content:space-between; flex-wrap:wrap;'; ?> gap:32px; box-sizing:border-box;">
				
				<!-- Left / Center Content -->
				<div class="dlm-countdown-info" style="<?php echo $layout === 'stacked' ? 'max-width:700px;' : 'flex:1; min-width:280px;'; ?> display:flex; flex-direction:column; justify-content:center;">
					<?php if ( $settings['show_subtitle'] === 'yes' && ! empty( $settings['subtitle_text'] ) ) : ?>
						<span class="dlm-countdown-subtitle" style="font-size:12px; font-weight:800; letter-spacing:1px; text-transform:uppercase; margin-bottom:8px; display:block;">
							<?php echo esc_html( $settings['subtitle_text'] ); ?>
						</span>
					<?php endif; ?>

					<h2 class="dlm-countdown-main-title" style="font-size:32px; font-weight:800; line-height:1.25; margin:0 0 12px 0;">
						<?php echo esc_html( $title ); ?>
					</h2>

					<?php if ( $settings['show_description'] === 'yes' && ! empty( $settings['description_text'] ) ) : ?>
						<p class="dlm-countdown-description" style="font-size:15px; line-height:1.6; margin:0 0 24px 0;">
							<?php echo esc_html( $settings['description_text'] ); ?>
						</p>
					<?php endif; ?>

					<!-- 4-Unit Countdown Box Grid -->
					<div class="dlm-book-countdown-grid" style="display:flex; align-items:center; <?php echo $layout === 'stacked' ? 'justify-content:center;' : ''; ?> flex-wrap:wrap; margin-bottom:24px;">
						<?php if ( $settings['show_days'] === 'yes' ) : ?>
							<div class="dlm-single-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
								<span class="dlm-countdown-number countdown-days font-mono" style="font-size:28px; font-weight:800; line-height:1;">00</span>
								<span class="dlm-countdown-unit-label" style="font-size:11px; font-weight:700; text-transform:uppercase;"><?php echo esc_html( $settings['label_days'] ); ?></span>
							</div>
						<?php endif; ?>

						<?php if ( $settings['show_hours'] === 'yes' ) : ?>
							<div class="dlm-single-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
								<span class="dlm-countdown-number countdown-hours font-mono" style="font-size:28px; font-weight:800; line-height:1;">00</span>
								<span class="dlm-countdown-unit-label" style="font-size:11px; font-weight:700; text-transform:uppercase;"><?php echo esc_html( $settings['label_hours'] ); ?></span>
							</div>
						<?php endif; ?>

						<?php if ( $settings['show_minutes'] === 'yes' ) : ?>
							<div class="dlm-single-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
								<span class="dlm-countdown-number countdown-minutes font-mono" style="font-size:28px; font-weight:800; line-height:1;">00</span>
								<span class="dlm-countdown-unit-label" style="font-size:11px; font-weight:700; text-transform:uppercase;"><?php echo esc_html( $settings['label_minutes'] ); ?></span>
							</div>
						<?php endif; ?>

						<?php if ( $settings['show_seconds'] === 'yes' ) : ?>
							<div class="dlm-single-countdown-box" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
								<span class="dlm-countdown-number countdown-seconds font-mono" style="font-size:28px; font-weight:800; line-height:1;">00</span>
								<span class="dlm-countdown-unit-label" style="font-size:11px; font-weight:700; text-transform:uppercase;"><?php echo esc_html( $settings['label_seconds'] ); ?></span>
							</div>
						<?php endif; ?>
					</div>

					<!-- Expiry State Banner & Action Button -->
					<div class="dlm-countdown-action-wrap" style="display:flex; align-items:center; <?php echo $layout === 'stacked' ? 'justify-content:center;' : ''; ?> gap:16px; flex-wrap:wrap;">
						<div class="dlm-countdown-expired-msg" style="display:none; font-weight:700; font-size:16px; color:#22c55e;">
							<?php echo esc_html( $settings['expiry_message'] ); ?>
						</div>

						<?php if ( $settings['show_action_btn'] === 'yes' && ! empty( $settings['action_btn_text'] ) ) : 
							$btn_href = ! empty( $settings['action_btn_url']['url'] ) ? $settings['action_btn_url']['url'] : ( $book ? home_url( '/read/' . $book->id . '/' ) : home_url( '/library/' ) );
						?>
							<a href="<?php echo esc_url( $btn_href ); ?>" class="dlm-countdown-action-btn" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; font-size:15px; transition:all 0.2s ease;">
								<span><?php echo esc_html( $settings['action_btn_text'] ); ?></span>
								<svg style="margin-left:8px; width:16px; height:16px; fill:currentColor;" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Right / Top Cover Image -->
				<?php if ( $settings['show_cover'] === 'yes' && ! empty( $cover_url ) ) : ?>
					<div class="dlm-countdown-cover-container" style="display:flex; align-items:center; justify-content:center; flex-shrink:0;">
						<div class="dlm-countdown-cover-wrap" style="overflow:hidden; position:relative; transition:transform 0.4s ease;">
							<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="width:100%; height:100%; object-fit:cover; display:block;" loading="lazy">
						</div>
					</div>
				<?php endif; ?>

			</div>
		</div>

		<!-- Real-time live countdown engine script -->
		<script>
		(function() {
			function initCountdownWidget() {
				var wrap = document.getElementById('<?php echo esc_js( $widget_id ); ?>');
				if (!wrap || wrap.dataset.initialized === 'true') return;
				wrap.dataset.initialized = 'true';

				var rawTime = wrap.getAttribute('data-target-time');
				if (!rawTime) return;

				function parseIso(s) {
					var str = String(s).trim();
					if (str.indexOf(' ') > 0 && str.indexOf('T') === -1) str = str.replace(' ', 'T');
					var parsed = Date.parse(str);
					if (!isNaN(parsed) && parsed > 0) return parsed;
					var d = new Date(s);
					var t = d.getTime();
					return isNaN(t) ? 0 : t;
				}

				var targetTime = parseIso(rawTime);
				if (!targetTime) return;

				var daysEl = wrap.querySelector('.countdown-days');
				var hoursEl = wrap.querySelector('.countdown-hours');
				var minsEl = wrap.querySelector('.countdown-minutes');
				var secsEl = wrap.querySelector('.countdown-seconds');
				var expiredMsg = wrap.querySelector('.dlm-countdown-expired-msg');

				function updateTimer() {
					var now = new Date().getTime();
					var distance = targetTime - now;

					if (distance <= 0) {
						if (daysEl) daysEl.textContent = '00';
						if (hoursEl) hoursEl.textContent = '00';
						if (minsEl) minsEl.textContent = '00';
						if (secsEl) secsEl.textContent = '00';
						if (expiredMsg) expiredMsg.style.display = 'block';
						return;
					}

					var days = Math.floor(distance / (1000 * 60 * 60 * 24));
					var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
					var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
					var seconds = Math.floor((distance % (1000 * 60)) / 1000);

					if (daysEl) daysEl.textContent = days < 10 ? '0' + days : '' + days;
					if (hoursEl) hoursEl.textContent = hours < 10 ? '0' + hours : '' + hours;
					if (minsEl) minsEl.textContent = minutes < 10 ? '0' + minutes : '' + minutes;
					if (secsEl) secsEl.textContent = seconds < 10 ? '0' + seconds : '' + seconds;
				}

				updateTimer();
				setInterval(updateTimer, 1000);
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initCountdownWidget);
			} else {
				initCountdownWidget();
			}

			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/dlm-book-countdown.default', function() {
					initCountdownWidget();
				});
			}
		})();
		</script>
		<?php
	}
}
