<?php
/**
 * Review Switcher (Video/Text/Google) Elementor Widget
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

class DLM_Widget_Review_Switcher extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dlm_review_switcher';
	}

	public function get_title() {
		return esc_html__( 'Review Switcher (Video/Text/Google)', 'digital-library-membership' );
	}

	public function get_icon() {
		return 'eicon-star-rating';
	}

	public function get_categories() {
		return array( 'digital-library', 'mipallab_category', 'general' );
	}

	public function get_keywords() {
		return array( 'review', 'testimonials', 'video', 'google', 'ratings', 'switcher', 'dlm', 'mipallab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_general',
			array(
				'label' => esc_html__( 'Section Header', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_tag',
			array(
				'label'   => esc_html__( 'Badge Tagline', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'COMMUNITY TESTIMONIALS', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'       => esc_html__( 'Section Title', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'What Readers Say About Our Library', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		// VIDEO REVIEWS TAB REPEATER
		$this->start_controls_section(
			'section_video_reviews',
			array(
				'label' => esc_html__( 'Video Reviews', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$video_repeater = new \Elementor\Repeater();
		$video_repeater->add_control(
			'name',
			array(
				'label'   => esc_html__( 'Reviewer Name', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Dr. Sarah Moon',
			)
		);
		$video_repeater->add_control(
			'role',
			array(
				'label'   => esc_html__( 'Role / Occupation', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'AI Researcher',
			)
		);
		$video_repeater->add_control(
			'video_url',
			array(
				'label'   => esc_html__( 'Video Embed URL', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
			)
		);

		$this->add_control(
			'video_items',
			array(
				'label'       => esc_html__( 'Video Review Items', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $video_repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array(
						'name'      => 'Dr. Sarah Moon',
						'role'      => 'AI Specialist',
						'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
					),
					array(
						'name'      => 'Julian Vance',
						'role'      => 'Tech Entrepreneur',
						'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
					),
				),
			)
		);

		$this->end_controls_section();

		// TEXT REVIEWS TAB REPEATER
		$this->start_controls_section(
			'section_text_reviews',
			array(
				'label' => esc_html__( 'Text Testimonials', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$text_repeater = new \Elementor\Repeater();
		$text_repeater->add_control(
			'reviewer_name',
			array(
				'label'   => esc_html__( 'Name', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Marcus Webb',
			)
		);
		$text_repeater->add_control(
			'reviewer_title',
			array(
				'label'   => esc_html__( 'Title / Badge', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Verified Reader',
			)
		);
		$text_repeater->add_control(
			'review_text',
			array(
				'label'   => esc_html__( 'Review Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'The AI Job Shift completely transformed how I view career progression. Superb depth and practical exercises!',
			)
		);
		$text_repeater->add_control(
			'avatar',
			array(
				'label'   => esc_html__( 'Avatar Image', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg' ),
			)
		);

		$this->add_control(
			'text_items',
			array(
				'label'       => esc_html__( 'Text Testimonial Items', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $text_repeater->get_controls(),
				'title_field' => '{{{ reviewer_name }}}',
				'default'     => array(
					array(
						'reviewer_name'  => 'Marcus Webb',
						'reviewer_title' => 'Verified Reader',
						'review_text'    => 'The AI Job Shift completely transformed how I view career progression. Superb depth and practical exercises!',
						'avatar'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg' ),
					),
					array(
						'reviewer_name'  => 'Lina Eklund',
						'reviewer_title' => 'Architectural Designer',
						'review_text'    => 'The reader interface feels so tactile and immersive. Best digital book reading experience on WordPress!',
						'avatar'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg' ),
					),
				),
			)
		);

		$this->end_controls_section();

		// GOOGLE REVIEWS TAB
		$this->start_controls_section(
			'section_google_reviews',
			array(
				'label' => esc_html__( 'Google Reviews Stat', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'google_score',
			array(
				'label'   => esc_html__( 'Google Rating Score', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '4.9 ★★★★★',
			)
		);

		$this->add_control(
			'google_subtext',
			array(
				'label'   => esc_html__( 'Google Rating Subtext', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Based on 1,280+ Verified Google Reviews',
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

	public static function render_review_switcher_html( $section_tag = 'COMMUNITY TESTIMONIALS', $section_title = 'What Readers Say About Our Library', $video_items = array(), $text_items = array(), $google_score = '4.9 ★★★★★', $google_subtext = 'Based on 1,280+ Verified Google Reviews', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		DLM_Home_Widgets::render_review_switcher_html( $section_tag, $section_title, $video_items, $text_items, $google_score, $google_subtext, $primary_color, $text_color );
	}

	protected function render() {
		$settings      = $this->get_settings_for_display();
		$section_tag   = ! empty( $settings['section_tag'] ) ? $settings['section_tag'] : '';
		$section_title = ! empty( $settings['section_title'] ) ? $settings['section_title'] : '';
		$video_items   = ! empty( $settings['video_items'] ) ? $settings['video_items'] : array();
		$text_items    = ! empty( $settings['text_items'] ) ? $settings['text_items'] : array();
		$google_score  = ! empty( $settings['google_score'] ) ? $settings['google_score'] : '4.9 ★★★★★';
		$google_sub    = ! empty( $settings['google_subtext'] ) ? $settings['google_subtext'] : 'Based on 1,280+ Verified Google Reviews';
		$primary_color = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#855300';
		$text_color    = ! empty( $settings['text_color'] ) ? $settings['text_color'] : '#1a1c1c';

		self::render_review_switcher_html( $section_tag, $section_title, $video_items, $text_items, $google_score, $google_sub, $primary_color, $text_color );
	}
}

// Backward Compatibility Class Alias
if ( ! class_exists( 'Mipallab_Review_Switcher_Widget' ) ) {
	class Mipallab_Review_Switcher_Widget extends DLM_Widget_Review_Switcher {
		public function get_name() {
			return 'mipallab_review_switcher';
		}
	}
}
