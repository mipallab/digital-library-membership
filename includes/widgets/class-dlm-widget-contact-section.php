<?php
/**
 * Interactive Contact Section Elementor Widget
 *
 * @since      3.0.0
 * @package    DLM
 * @subpackage DLM/includes/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class DLM_Widget_Contact_Section extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dlm_contact_section';
	}

	public function get_title() {
		return esc_html__( 'Interactive Contact Section', 'digital-library-membership' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'digital-library', 'mipallab_category', 'general' );
	}

	public function get_keywords() {
		return array( 'contact', 'form', 'email', 'support', 'message', 'inquiry', 'dlm', 'mipallab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Section Header & Info', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_tag',
			array(
				'label'   => esc_html__( 'Badge Tagline', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'GET IN TOUCH', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'       => esc_html__( 'Section Title', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Have Questions? Contact Us', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'section_desc',
			array(
				'label'   => esc_html__( 'Subtitle', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Reach out to our author team or customer support. We respond within 24 hours.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'email_address',
			array(
				'label'   => esc_html__( 'Email Address', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'support@bridgeway36.com',
			)
		);

		$this->add_control(
			'phone_number',
			array(
				'label'   => esc_html__( 'Phone Number', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '+1 (800) 555-0199',
			)
		);

		$this->add_control(
			'location_text',
			array(
				'label'   => esc_html__( 'Office Location', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'New York & Global Headquarters',
			)
		);

		$this->add_control(
			'form_title',
			array(
				'label'   => esc_html__( 'Form Box Title', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Send Us a Message', 'digital-library-membership' ),
			)
		);

		$this->end_controls_section();

		// STYLE TAB
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Styling', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'   => esc_html__( 'Background Color', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
			)
		);

		$this->add_control(
			'primary_color',
			array(
				'label'   => esc_html__( 'Primary Accent Color', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#855300',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'   => esc_html__( 'Text Color', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#1a1c1c',
			)
		);

		$this->end_controls_section();
	}

	public static function render_contact_html( $section_tag = 'GET IN TOUCH', $section_title = 'Have Questions? Contact Us', $section_desc = 'Reach out to our author team or customer support. We respond within 24 hours.', $email = 'support@bridgeway36.com', $phone = '+1 (800) 555-0199', $location = 'New York & Global Headquarters', $form_title = 'Send Us a Message', $bg_color = '#ffffff', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		DLM_Home_Widgets::render_contact_html( $section_tag, $section_title, $section_desc, $email, $phone, $location, $form_title, $bg_color, $primary_color, $text_color );
	}

	protected function render() {
		$settings      = $this->get_settings_for_display();
		$section_tag   = ! empty( $settings['section_tag'] ) ? $settings['section_tag'] : '';
		$section_title = ! empty( $settings['section_title'] ) ? $settings['section_title'] : '';
		$section_desc  = ! empty( $settings['section_desc'] ) ? $settings['section_desc'] : '';
		$email         = ! empty( $settings['email_address'] ) ? $settings['email_address'] : '';
		$phone         = ! empty( $settings['phone_number'] ) ? $settings['phone_number'] : '';
		$location      = ! empty( $settings['location_text'] ) ? $settings['location_text'] : '';
		$form_title    = ! empty( $settings['form_title'] ) ? $settings['form_title'] : esc_html__( 'Send Us a Message', 'digital-library-membership' );
		$bg_color      = ! empty( $settings['bg_color'] ) ? $settings['bg_color'] : '#ffffff';
		$primary_color = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#855300';
		$text_color    = ! empty( $settings['text_color'] ) ? $settings['text_color'] : '#1a1c1c';

		self::render_contact_html( $section_tag, $section_title, $section_desc, $email, $phone, $location, $form_title, $bg_color, $primary_color, $text_color );
	}
}

// Backward Compatibility Class Alias
if ( ! class_exists( 'Mipallab_Contact_Section_Widget' ) ) {
	class Mipallab_Contact_Section_Widget extends DLM_Widget_Contact_Section {
		public function get_name() {
			return 'mipallab_contact_section';
		}
	}
}
