<?php
/**
 * About Author & Highlights Elementor Widget
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

class DLM_Widget_About_Author extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dlm_about_author';
	}

	public function get_title() {
		return esc_html__( 'About Author Section', 'digital-library-membership' );
	}

	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	public function get_categories() {
		return array( 'digital-library' );
	}

	public function get_keywords() {
		return array( 'about', 'author', 'biography', 'profile', 'writer', 'dlm' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Author Details', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'author_image',
			array(
				'label'   => esc_html__( 'Author Photo', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array(
					'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg',
				),
			)
		);

		$this->add_control(
			'section_tag',
			array(
				'label'   => esc_html__( 'Section Tagline', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'MEET THE AUTHOR', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'author_name',
			array(
				'label'   => esc_html__( 'Author Name', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Avery Noble & Bridgeway Team', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'author_bio',
			array(
				'label'   => esc_html__( 'Author Bio Paragraph', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'With over a decade of dedicated experience in literature, artificial intelligence research, and quiet architectural philosophy, our authors craft insightful publications that empower readers to thrive. From career transformation in the AI era to modern living, each publication combines practical wisdom with profound depth.', 'digital-library-membership' ),
			)
		);

		// Stats Repeater
		$stats_repeater = new \Elementor\Repeater();
		$stats_repeater->add_control(
			'stat_number',
			array(
				'label'   => esc_html__( 'Stat Number', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '25+',
			)
		);
		$stats_repeater->add_control(
			'stat_label',
			array(
				'label'   => esc_html__( 'Stat Label', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Books Published', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'stats',
			array(
				'label'       => esc_html__( 'Statistics / Highlights', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $stats_repeater->get_controls(),
				'title_field' => '{{{ stat_number }}} {{{ stat_label }}}',
				'default'     => array(
					array(
						'stat_number' => '25+',
						'stat_label'  => esc_html__( 'Published Titles', 'digital-library-membership' ),
					),
					array(
						'stat_number' => '100K+',
						'stat_label'  => esc_html__( 'Global Readers', 'digital-library-membership' ),
					),
					array(
						'stat_number' => '15+',
						'stat_label'  => esc_html__( 'Years Experience', 'digital-library-membership' ),
					),
					array(
						'stat_number' => '4.9★',
						'stat_label'  => esc_html__( 'Average Rating', 'digital-library-membership' ),
					),
				),
			)
		);

		// Social Links Repeater
		$social_repeater = new \Elementor\Repeater();
		$social_repeater->add_control(
			'platform',
			array(
				'label'   => esc_html__( 'Platform Name', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Twitter / X',
			)
		);
		$social_repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link URL', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#' ),
			)
		);
		$social_repeater->add_control(
			'icon_text',
			array(
				'label'   => esc_html__( 'Icon Symbol', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '🌐',
			)
		);

		$this->add_control(
			'social_links',
			array(
				'label'       => esc_html__( 'Social Profiles', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $social_repeater->get_controls(),
				'title_field' => '{{{ platform }}}',
				'default'     => array(
					array(
						'platform'  => 'Goodreads',
						'icon_text' => '📚',
						'link'      => array( 'url' => '#' ),
					),
					array(
						'platform'  => 'Twitter / X',
						'icon_text' => '🐦',
						'link'      => array( 'url' => '#' ),
					),
					array(
						'platform'  => 'LinkedIn',
						'icon_text' => '💼',
						'link'      => array( 'url' => '#' ),
					),
				),
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

	public static function render_about_author_html( $section_tag = 'MEET THE AUTHOR', $author_name = 'Avery Noble & Bridgeway Team', $author_bio = '', $author_img = 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg', $stats = array(), $social_links = array(), $bg_color = '#ffffff', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		DLM_Home_Widgets::render_about_author_html( $section_tag, $author_name, $author_bio, $author_img, $stats, $social_links, $bg_color, $primary_color, $text_color );
	}

	protected function render() {
		$settings      = $this->get_settings_for_display();
		$section_tag   = ! empty( $settings['section_tag'] ) ? $settings['section_tag'] : esc_html__( 'MEET THE AUTHOR', 'digital-library-membership' );
		$author_name   = ! empty( $settings['author_name'] ) ? $settings['author_name'] : esc_html__( 'Avery Noble & Bridgeway Team', 'digital-library-membership' );
		$author_bio    = ! empty( $settings['author_bio'] ) ? $settings['author_bio'] : '';
		$author_img    = ! empty( $settings['author_image']['url'] ) ? $settings['author_image']['url'] : '';
		$stats         = ! empty( $settings['stats'] ) ? $settings['stats'] : array();
		$social_links  = ! empty( $settings['social_links'] ) ? $settings['social_links'] : array();
		$bg_color      = ! empty( $settings['bg_color'] ) ? $settings['bg_color'] : '#ffffff';
		$primary_color = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#855300';
		$text_color    = ! empty( $settings['text_color'] ) ? $settings['text_color'] : '#1a1c1c';

		self::render_about_author_html( $section_tag, $author_name, $author_bio, $author_img, $stats, $social_links, $bg_color, $primary_color, $text_color );
	}
}

// Backward Compatibility Class Alias (hidden from panel)
if ( ! class_exists( 'Mipallab_About_Author_Widget' ) ) {
	class Mipallab_About_Author_Widget extends DLM_Widget_About_Author {
		public function get_name() {
			return 'mipallab_about_author';
		}
		public function show_in_panel() {
			return false;
		}
	}
}
