<?php
/**
 * Review Switcher (Video/Text/Google) Elementor Widget
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
		return array( 'digital-library' );
	}

	public function get_keywords() {
		return array( 'review', 'testimonials', 'video', 'google', 'ratings', 'switcher', 'dlm', 'feedback' );
	}

	protected function register_controls() {

		// =========================================================================
		// 1. SECTION HEADER
		// =========================================================================
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

		$this->add_control(
			'section_desc',
			array(
				'label'       => esc_html__( 'Section Subtitle / Description', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Real feedback from our global community of readers, researchers, and book subscribers.', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		// =========================================================================
		// 2. SWITCHER & TAB CONTROLS
		// =========================================================================
		$this->start_controls_section(
			'section_switcher_settings',
			array(
				'label' => esc_html__( 'Switcher & Tab Navigation', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_switcher_nav',
			array(
				'label'        => esc_html__( 'Show Switcher Tab Bar', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'digital-library-membership' ),
				'label_off'    => esc_html__( 'Hide', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toggle to show or hide the tab switcher button bar.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'default_active_tab',
			array(
				'label'   => esc_html__( 'Default Active Tab', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'video',
				'options' => array(
					'video'  => esc_html__( 'Video Reviews', 'digital-library-membership' ),
					'text'   => esc_html__( 'Text Testimonials', 'digital-library-membership' ),
					'google' => esc_html__( 'Google Reviews', 'digital-library-membership' ),
				),
			)
		);

		$this->add_control(
			'heading_tabs_visibility',
			array(
				'label'     => esc_html__( 'Tab Visibility & Labels', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'enable_video_tab',
			array(
				'label'        => esc_html__( 'Enable Video Reviews Tab', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'digital-library-membership' ),
				'label_off'    => esc_html__( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'video_tab_label',
			array(
				'label'     => esc_html__( 'Video Tab Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( '🎥 Video Reviews', 'digital-library-membership' ),
				'condition' => array(
					'enable_video_tab' => 'yes',
				),
			)
		);

		$this->add_control(
			'enable_text_tab',
			array(
				'label'        => esc_html__( 'Enable Text Testimonials Tab', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'digital-library-membership' ),
				'label_off'    => esc_html__( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'text_tab_label',
			array(
				'label'     => esc_html__( 'Text Tab Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( '💬 Text Testimonials', 'digital-library-membership' ),
				'condition' => array(
					'enable_text_tab' => 'yes',
				),
			)
		);

		$this->add_control(
			'enable_google_tab',
			array(
				'label'        => esc_html__( 'Enable Google Reviews Tab', 'digital-library-membership' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'digital-library-membership' ),
				'label_off'    => esc_html__( 'No', 'digital-library-membership' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'google_tab_label',
			array(
				'label'     => esc_html__( 'Google Tab Label', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( '⭐ Google Reviews', 'digital-library-membership' ),
				'condition' => array(
					'enable_google_tab' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// =========================================================================
		// 3. VIDEO REVIEWS REPEATER
		// =========================================================================
		$this->start_controls_section(
			'section_video_reviews',
			array(
				'label'     => esc_html__( 'Video Reviews', 'digital-library-membership' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'enable_video_tab' => 'yes',
				),
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
				'label'       => esc_html__( 'Video / Embed URL (YouTube, Vimeo, MP4)', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
				'placeholder' => 'https://www.youtube.com/watch?v=... or embed URL',
				'label_block' => true,
			)
		);
		$video_repeater->add_control(
			'rating',
			array(
				'label'   => esc_html__( 'Star Rating (1-5)', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '5',
				'options' => array(
					'5' => '5 Stars ★★★★★',
					'4' => '4 Stars ★★★★☆',
					'3' => '3 Stars ★★★☆☆',
					'2' => '2 Stars ★★☆☆☆',
					'1' => '1 Star ★☆☆☆☆',
				),
			)
		);

		$this->add_control(
			'video_items',
			array(
				'label'       => esc_html__( 'Video Review Items', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $video_repeater->get_controls(),
				'title_field' => '{{{ name }}} ({{{ role }}})',
				'default'     => array(
					array(
						'name'      => 'Dr. Sarah Moon',
						'role'      => 'AI Specialist',
						'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
						'rating'    => '5',
					),
					array(
						'name'      => 'Julian Vance',
						'role'      => 'Tech Entrepreneur',
						'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
						'rating'    => '5',
					),
				),
			)
		);

		$this->end_controls_section();

		// =========================================================================
		// 4. TEXT REVIEWS REPEATER
		// =========================================================================
		$this->start_controls_section(
			'section_text_reviews',
			array(
				'label'     => esc_html__( 'Text Testimonials', 'digital-library-membership' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'enable_text_tab' => 'yes',
				),
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
		$text_repeater->add_control(
			'rating',
			array(
				'label'   => esc_html__( 'Star Rating (1-5)', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '5',
				'options' => array(
					'5' => '5 Stars ★★★★★',
					'4' => '4 Stars ★★★★☆',
					'3' => '3 Stars ★★★☆☆',
					'2' => '2 Stars ★★☆☆☆',
					'1' => '1 Star ★☆☆☆☆',
				),
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
						'rating'         => '5',
					),
					array(
						'reviewer_name'  => 'Lina Eklund',
						'reviewer_title' => 'Architectural Designer',
						'review_text'    => 'The reader interface feels so tactile and immersive. Best digital book reading experience on WordPress!',
						'avatar'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg' ),
						'rating'         => '5',
					),
				),
			)
		);

		$this->end_controls_section();

		// =========================================================================
		// 5. GOOGLE REVIEWS INTEGRATION & CONTROLS
		// =========================================================================
		$this->start_controls_section(
			'section_google_reviews',
			array(
				'label'     => esc_html__( 'Google Reviews Integration', 'digital-library-membership' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'enable_google_tab' => 'yes',
				),
			)
		);

		$this->add_control(
			'google_mode',
			array(
				'label'       => esc_html__( 'Integration Mode', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'manual',
				'options'     => array(
					'manual'    => esc_html__( 'Manual Score Card & Customer Reviews', 'digital-library-membership' ),
					'shortcode' => esc_html__( 'Google Reviews Plugin Shortcode / Embed Code', 'digital-library-membership' ),
					'api'       => esc_html__( 'Google Places API & Place Search ID', 'digital-library-membership' ),
				),
				'description' => esc_html__( 'Compatible with third-party Google Reviews plugins (Trustindex, Plugin for Google Reviews, WP Google Review Slider, etc.), or use built-in card controls.', 'digital-library-membership' ),
			)
		);

		// Shortcode / Plugin mode fields
		$this->add_control(
			'google_shortcode',
			array(
				'label'       => esc_html__( 'Google Reviews Plugin Shortcode / Embed', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'placeholder' => '[trustindex no-registration=google] or [google-reviews-pro]',
				'condition'   => array(
					'google_mode' => 'shortcode',
				),
				'description' => esc_html__( 'Paste any shortcode from your installed Google Reviews plugin (e.g. Trustindex, Widgets for Google Reviews, etc.).', 'digital-library-membership' ),
			)
		);

		// API mode fields
		$this->add_control(
			'google_api_key',
			array(
				'label'       => esc_html__( 'Google Places API Key (Optional)', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'AIzaSy...',
				'condition'   => array(
					'google_mode' => 'api',
				),
			)
		);

		$this->add_control(
			'google_place_id',
			array(
				'label'       => esc_html__( 'Google Place ID / Account Search (Optional)', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'ChIJ...',
				'condition'   => array(
					'google_mode' => 'api',
				),
			)
		);

		// Shared / Manual fields
		$this->add_control(
			'google_business_name',
			array(
				'label'       => esc_html__( 'Business / Library Name', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'Bridgeway Digital Library',
				'label_block' => true,
				'condition'   => array(
					'google_mode!' => 'shortcode',
				),
			)
		);

		$this->add_control(
			'google_score',
			array(
				'label'     => esc_html__( 'Google Rating Score Display', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => '4.9 ★★★★★',
				'condition' => array(
					'google_mode!' => 'shortcode',
				),
			)
		);

		$this->add_control(
			'google_subtext',
			array(
				'label'       => esc_html__( 'Google Rating Subtext', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'Based on 1,280+ Verified Google Reviews',
				'label_block' => true,
				'condition'   => array(
					'google_mode!' => 'shortcode',
				),
			)
		);

		$this->add_control(
			'google_place_url',
			array(
				'label'       => esc_html__( 'Google Maps / Place URL', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'https://maps.google.com',
				'placeholder' => 'https://maps.google.com/...',
				'label_block' => true,
				'condition'   => array(
					'google_mode!' => 'shortcode',
				),
			)
		);

		$this->add_control(
			'google_btn_text',
			array(
				'label'     => esc_html__( 'Google Button Text', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'Write a Review on Google',
				'condition' => array(
					'google_mode!' => 'shortcode',
				),
			)
		);

		// Google Reviews Repeater
		$google_repeater = new \Elementor\Repeater();
		$google_repeater->add_control(
			'author_name',
			array(
				'label'   => esc_html__( 'Reviewer Name', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'David Miller',
			)
		);
		$google_repeater->add_control(
			'time_ago',
			array(
				'label'   => esc_html__( 'Review Date / Time Ago', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '3 days ago',
			)
		);
		$google_repeater->add_control(
			'review_text',
			array(
				'label'   => esc_html__( 'Review Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'Incredible digital collection! The 3D reader experience is unmatched and downloading reference PDFs is instantaneous.',
			)
		);
		$google_repeater->add_control(
			'author_avatar',
			array(
				'label'   => esc_html__( 'Reviewer Avatar Image', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg' ),
			)
		);
		$google_repeater->add_control(
			'rating',
			array(
				'label'   => esc_html__( 'Star Rating (1-5)', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '5',
				'options' => array(
					'5' => '5 Stars ★★★★★',
					'4' => '4 Stars ★★★★☆',
					'3' => '3 Stars ★★★☆☆',
					'2' => '2 Stars ★★☆☆☆',
					'1' => '1 Star ★☆☆☆☆',
				),
			)
		);

		$this->add_control(
			'google_review_items',
			array(
				'label'       => esc_html__( 'Google Customer Review Cards', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $google_repeater->get_controls(),
				'title_field' => '{{{ author_name }}} ({{{ time_ago }}})',
				'condition'   => array(
					'google_mode!' => 'shortcode',
				),
				'default'     => array(
					array(
						'author_name'   => 'David Miller',
						'time_ago'      => '3 days ago',
						'review_text'   => 'Incredible digital collection! The 3D reader experience is unmatched and downloading reference PDFs is instantaneous.',
						'author_avatar' => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg' ),
						'rating'        => '5',
					),
					array(
						'author_name'   => 'Sophia Alverez',
						'time_ago'      => '1 week ago',
						'review_text'   => 'Verified subscriber here. The research publications and book selection have been indispensable for our academic team.',
						'author_avatar' => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg' ),
						'rating'        => '5',
					),
				),
			)
		);

		$this->end_controls_section();

		// =========================================================================
		// 6. STYLE TAB
		// =========================================================================
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

		$this->add_control(
			'card_bg',
			array(
				'label'   => esc_html__( 'Card Background Color', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
			)
		);

		$this->add_control(
			'star_color',
			array(
				'label'   => esc_html__( 'Star Rating Color', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#f59e0b',
			)
		);

		$this->end_controls_section();
	}

	public static function render_review_switcher_html( $section_tag = 'COMMUNITY TESTIMONIALS', $section_title = 'What Readers Say About Our Library', $video_items = array(), $text_items = array(), $google_score = '4.9 ★★★★★', $google_subtext = 'Based on 1,280+ Verified Google Reviews', $primary_color = '#855300', $text_color = '#1a1c1c', $extra = array() ) {
		DLM_Home_Widgets::render_review_switcher_html( $section_tag, $section_title, $video_items, $text_items, $google_score, $google_subtext, $primary_color, $text_color, $extra );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$section_tag   = ! empty( $settings['section_tag'] ) ? $settings['section_tag'] : '';
		$section_title = ! empty( $settings['section_title'] ) ? $settings['section_title'] : '';
		$video_items   = ! empty( $settings['video_items'] ) ? $settings['video_items'] : array();
		$text_items    = ! empty( $settings['text_items'] ) ? $settings['text_items'] : array();
		$google_score  = ! empty( $settings['google_score'] ) ? $settings['google_score'] : '4.9 ★★★★★';
		$google_sub    = ! empty( $settings['google_subtext'] ) ? $settings['google_subtext'] : 'Based on 1,280+ Verified Google Reviews';
		$primary_color = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#855300';
		$text_color    = ! empty( $settings['text_color'] ) ? $settings['text_color'] : '#1a1c1c';

		$extra = array(
			'section_desc'         => ! empty( $settings['section_desc'] ) ? $settings['section_desc'] : '',
			'show_switcher_nav'    => ! empty( $settings['show_switcher_nav'] ) ? $settings['show_switcher_nav'] : 'yes',
			'default_active_tab'   => ! empty( $settings['default_active_tab'] ) ? $settings['default_active_tab'] : 'video',
			'enable_video_tab'     => ! empty( $settings['enable_video_tab'] ) ? $settings['enable_video_tab'] : 'yes',
			'video_tab_label'      => ! empty( $settings['video_tab_label'] ) ? $settings['video_tab_label'] : esc_html__( '🎥 Video Reviews', 'digital-library-membership' ),
			'enable_text_tab'      => ! empty( $settings['enable_text_tab'] ) ? $settings['enable_text_tab'] : 'yes',
			'text_tab_label'       => ! empty( $settings['text_tab_label'] ) ? $settings['text_tab_label'] : esc_html__( '💬 Text Testimonials', 'digital-library-membership' ),
			'enable_google_tab'    => ! empty( $settings['enable_google_tab'] ) ? $settings['enable_google_tab'] : 'yes',
			'google_tab_label'     => ! empty( $settings['google_tab_label'] ) ? $settings['google_tab_label'] : esc_html__( '⭐ Google Reviews', 'digital-library-membership' ),
			'google_mode'          => ! empty( $settings['google_mode'] ) ? $settings['google_mode'] : 'manual',
			'google_business_name' => ! empty( $settings['google_business_name'] ) ? $settings['google_business_name'] : 'Bridgeway Digital Library',
			'google_place_url'     => ! empty( $settings['google_place_url'] ) ? $settings['google_place_url'] : 'https://maps.google.com',
			'google_btn_text'      => ! empty( $settings['google_btn_text'] ) ? $settings['google_btn_text'] : 'Write a Review on Google',
			'google_shortcode'     => ! empty( $settings['google_shortcode'] ) ? $settings['google_shortcode'] : '',
			'google_review_items'  => ! empty( $settings['google_review_items'] ) ? $settings['google_review_items'] : array(),
			'card_bg'              => ! empty( $settings['card_bg'] ) ? $settings['card_bg'] : '#ffffff',
			'star_color'           => ! empty( $settings['star_color'] ) ? $settings['star_color'] : '#f59e0b',
		);

		self::render_review_switcher_html( $section_tag, $section_title, $video_items, $text_items, $google_score, $google_sub, $primary_color, $text_color, $extra );
	}
}

// Backward Compatibility Class Alias (hidden from panel)
if ( ! class_exists( 'Mipallab_Review_Switcher_Widget' ) ) {
	class Mipallab_Review_Switcher_Widget extends DLM_Widget_Review_Switcher {
		public function get_name() {
			return 'mipallab_review_switcher';
		}
		public function show_in_panel() {
			return false;
		}
	}
}
