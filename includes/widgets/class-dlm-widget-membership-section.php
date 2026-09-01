<?php
/**
 * Digital Library Membership Plans Elementor Widget
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

class DLM_Widget_Membership_Section extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dlm_membership_section';
	}

	public function get_title() {
		return esc_html__( 'Digital Library Membership Plans', 'digital-library-membership' );
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	public function get_categories() {
		return array( 'digital-library' );
	}

	public function get_keywords() {
		return array( 'membership', 'pricing', 'plans', 'subscription', 'library', 'vip', 'dlm' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
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
				'default' => esc_html__( 'MEMBERSHIP ACCESS', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'       => esc_html__( 'Section Title', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Choose Your Membership Plan', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'section_desc',
			array(
				'label'   => esc_html__( 'Subtitle', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Unlock unlimited digital reading, downloadable PDFs, and exclusive research publications.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'source',
			array(
				'label'       => esc_html__( 'Data Source', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'dynamic',
				'options'     => array(
					'dynamic' => esc_html__( 'Dynamic (Live DLM Subscription Plans)', 'digital-library-membership' ),
					'custom'  => esc_html__( 'Custom Plans (Manual Repeater)', 'digital-library-membership' ),
				),
				'description' => esc_html__( 'Dynamic mode automatically syncs pricing and perks directly from your Digital Library Subscription Packages registry.', 'digital-library-membership' ),
			)
		);

		// PRICING CARDS REPEATER
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'plan_name',
			array(
				'label'   => esc_html__( 'Plan Name', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Standard Reader', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'price',
			array(
				'label'   => esc_html__( 'Price', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '$15.00',
			)
		);

		$repeater->add_control(
			'period',
			array(
				'label'   => esc_html__( 'Billing Period', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'per month', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'is_featured',
			array(
				'label'   => esc_html__( 'Is Popular / Featured?', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'no',
			)
		);

		$repeater->add_control(
			'badge_text',
			array(
				'label'     => esc_html__( 'Badge Text (if featured)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( 'MOST POPULAR', 'digital-library-membership' ),
				'condition' => array( 'is_featured' => 'yes' ),
			)
		);

		$repeater->add_control(
			'features_list',
			array(
				'label'   => esc_html__( 'Features (one per line)', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => "Unlimited Online Book Reading\nAccess to New Releases\nHigh-speed Digital Reader\nStandard Support",
			)
		);

		$repeater->add_control(
			'btn_text',
			array(
				'label'   => esc_html__( 'Button Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Subscribe Now', 'digital-library-membership' ),
			)
		);

		$repeater->add_control(
			'btn_link',
			array(
				'label'   => esc_html__( 'Button Link', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '/checkout' ),
			)
		);

		$this->add_control(
			'plans',
			array(
				'label'       => esc_html__( 'Membership Plans', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ plan_name }}}',
				'default'     => array(
					array(
						'plan_name'     => esc_html__( 'Monthly Reader Pass', 'digital-library-membership' ),
						'price'         => '$9.99',
						'period'        => esc_html__( 'per month', 'digital-library-membership' ),
						'is_featured'   => 'no',
						'features_list' => "Access to all published digital books\nRead on any device\nBookmarks & Progress tracking\nCancel anytime",
						'btn_text'      => esc_html__( 'Get Monthly Pass', 'digital-library-membership' ),
						'btn_link'      => array( 'url' => '#checkout' ),
					),
					array(
						'plan_name'     => esc_html__( 'VIP Annual Membership', 'digital-library-membership' ),
						'price'         => '$25.00',
						'period'        => esc_html__( 'per year (Save 50%)', 'digital-library-membership' ),
						'is_featured'   => 'yes',
						'badge_text'    => esc_html__( 'BEST VALUE', 'digital-library-membership' ),
						'features_list' => "Unlimited reading & downloadable PDFs\nEarly access to new book releases\nExclusive Author Q&A events\nPremium priority support\nFamily sharing access",
						'btn_text'      => esc_html__( 'Join Annual VIP', 'digital-library-membership' ),
						'btn_link'      => array( 'url' => '#checkout' ),
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
				'default' => 'rgba(133, 83, 0, 0.08)',
			)
		);

		$this->add_control(
			'primary_color',
			array(
				'label'   => esc_html__( 'Primary Color', 'digital-library-membership' ),
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

	public static function render_plans_html( $section_tag = 'MEMBERSHIP ACCESS', $section_title = 'Choose Your Membership Plan', $section_desc = 'Unlock unlimited digital reading, downloadable PDFs, and exclusive research publications.', $plans = array(), $bg_color = 'rgba(133, 83, 0, 0.08)', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		DLM_Home_Widgets::render_plans_html( $section_tag, $section_title, $section_desc, $plans, $bg_color, $primary_color, $text_color );
	}

	protected function render() {
		$settings      = $this->get_settings_for_display();
		$source        = ! empty( $settings['source'] ) ? $settings['source'] : 'dynamic';
		$section_tag   = ! empty( $settings['section_tag'] ) ? $settings['section_tag'] : '';
		$section_title = ! empty( $settings['section_title'] ) ? $settings['section_title'] : '';
		$section_desc  = ! empty( $settings['section_desc'] ) ? $settings['section_desc'] : '';
		$bg_color      = ! empty( $settings['bg_color'] ) ? $settings['bg_color'] : 'rgba(133, 83, 0, 0.08)';
		$primary_color = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#855300';
		$text_color    = ! empty( $settings['text_color'] ) ? $settings['text_color'] : '#1a1c1c';

		if ( 'dynamic' === $source ) {
			$raw_packages    = function_exists( 'dlm_get_packages' ) ? dlm_get_packages() : array();
			$plans           = array();
			$currency_symbol = get_option( 'dlm_currency_symbol', '$' );
			$checkout_url    = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'checkout' ) : '#checkout';

			if ( ! empty( $raw_packages ) ) {
				foreach ( $raw_packages as $pkg ) {
					if ( isset( $pkg['status'] ) && 'inactive' === $pkg['status'] ) {
						continue;
					}
					/* translators: %s: billing interval */
					$interval_label = ( ! empty( $pkg['interval'] ) && 'lifetime' === $pkg['interval'] ) ? esc_html__( 'one-time permanent', 'digital-library-membership' ) : sprintf( esc_html__( 'per %s', 'digital-library-membership' ), $pkg['interval'] ?? 'month' );
					$is_featured    = ( ( $pkg['interval'] ?? '' ) === 'yearly' || ! empty( $pkg['badge'] ) ) ? 'yes' : 'no';

					$plans[] = array(
						'plan_name'     => ! empty( $pkg['name'] ) ? $pkg['name'] : ucfirst( $pkg['interval'] ?? 'Standard' ),
						'price'         => $currency_symbol . number_format( floatval( $pkg['price'] ?? 0 ), 2 ),
						'period'        => $interval_label,
						'is_featured'   => $is_featured,
						'badge_text'    => ! empty( $pkg['badge'] ) ? $pkg['badge'] : esc_html__( 'BEST VALUE', 'digital-library-membership' ),
						'features_list' => is_array( $pkg['features'] ?? '' ) ? implode( "\n", $pkg['features'] ) : ( $pkg['features'] ?? '' ),
						'btn_text'      => esc_html__( 'Choose Plan', 'digital-library-membership' ),
						'btn_link'      => array( 'url' => add_query_arg( 'plan', $pkg['interval'] ?? 'monthly', $checkout_url ) . '#checkout' ),
					);
				}
			}

			if ( empty( $plans ) && empty( $raw_packages ) ) {
				$plans = ! empty( $settings['plans'] ) ? $settings['plans'] : array();
			}
		} else {
			$plans = ! empty( $settings['plans'] ) ? $settings['plans'] : array();
		}

		self::render_plans_html( $section_tag, $section_title, $section_desc, $plans, $bg_color, $primary_color, $text_color );
	}
}

// Backward Compatibility Class Alias (hidden from panel)
if ( ! class_exists( 'Mipallab_Membership_Section_Widget' ) ) {
	class Mipallab_Membership_Section_Widget extends DLM_Widget_Membership_Section {
		public function get_name() {
			return 'mipallab_membership_section';
		}
		public function show_in_panel() {
			return false;
		}
	}
}
