<?php
/**
 * Library Book Carousel & Grid Elementor Widget
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

class DLM_Widget_Library_Carousel extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dlm_library_carousel';
	}

	public function get_title() {
		return esc_html__( 'Library Book Carousel (DLM)', 'digital-library-membership' );
	}

	public function get_icon() {
		return 'eicon-carousel';
	}

	public function get_categories() {
		return array( 'digital-library', 'mipallab_category', 'general' );
	}

	public function get_keywords() {
		return array( 'library', 'books', 'carousel', 'dlm', 'slider', 'publications', 'grid', 'mipallab' );
	}

	protected function register_controls() {

		// CONTENT TAB: Settings
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Library Section Settings', 'digital-library-membership' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_tag',
			array(
				'label'   => esc_html__( 'Section Badge Tag', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'DIGITAL LIBRARY COLLECTION', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'       => esc_html__( 'Section Title', 'digital-library-membership' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Explore Featured Library Books', 'digital-library-membership' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'section_desc',
			array(
				'label'   => esc_html__( 'Section Subtitle', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Browse our curated selection of digital books. Read instantly or join membership for unlimited access.', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'display_mode',
			array(
				'label'   => esc_html__( 'Display Layout Mode', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'carousel',
				'options' => array(
					'carousel' => esc_html__( 'Swiper Carousel', 'digital-library-membership' ),
					'grid'     => esc_html__( 'Full Library Grid (with Live Search)', 'digital-library-membership' ),
				),
			)
		);

		$this->add_control(
			'books_limit',
			array(
				'label'   => esc_html__( 'Max Books to Load', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 12,
			)
		);

		$this->add_control(
			'slides_per_view',
			array(
				'label'     => esc_html__( 'Slides Per View (Desktop)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 6,
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'slides_per_view_tablet',
			array(
				'label'     => esc_html__( 'Slides Per View (Tablet)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 2,
				'min'       => 1,
				'max'       => 4,
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'slides_per_view_mobile',
			array(
				'label'     => esc_html__( 'Slides Per View (Mobile)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 1,
				'min'       => 1,
				'max'       => 2,
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'space_between',
			array(
				'label'     => esc_html__( 'Space Between Slides (px)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 24,
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'     => esc_html__( 'Autoplay', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => esc_html__( 'Autoplay Delay (ms)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 4500,
				'condition' => array(
					'display_mode' => 'carousel',
					'autoplay'     => 'yes',
				),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'     => esc_html__( 'Transition Speed (ms)', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 750,
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'     => esc_html__( 'Infinite Loop', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'     => esc_html__( 'Navigation Arrows', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'     => esc_html__( 'Pagination Dots', 'digital-library-membership' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'display_mode' => 'carousel' ),
			)
		);

		$this->add_control(
			'view_all_text',
			array(
				'label'   => esc_html__( 'View All Button Text', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Explore Full Library Catalog', 'digital-library-membership' ),
			)
		);

		$this->add_control(
			'view_all_link',
			array(
				'label'   => esc_html__( 'View All Link', 'digital-library-membership' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '/library' ),
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
				'default' => 'rgba(133, 83, 0, 0.04)',
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

	protected function render() {
		$settings       = $this->get_settings_for_display();
		$limit          = ! empty( $settings['books_limit'] ) ? intval( $settings['books_limit'] ) : 12;
		$books          = DLM_Books_Helper::get_books( $limit );

		$section_tag    = ! empty( $settings['section_tag'] ) ? $settings['section_tag'] : '';
		$section_title  = ! empty( $settings['section_title'] ) ? $settings['section_title'] : '';
		$section_desc   = ! empty( $settings['section_desc'] ) ? $settings['section_desc'] : '';
		$display_mode   = ! empty( $settings['display_mode'] ) ? $settings['display_mode'] : 'carousel';

		$bg_color       = ! empty( $settings['bg_color'] ) ? esc_attr( $settings['bg_color'] ) : 'rgba(133, 83, 0, 0.04)';
		$primary_color  = ! empty( $settings['primary_color'] ) ? esc_attr( $settings['primary_color'] ) : '#855300';
		$text_color     = ! empty( $settings['text_color'] ) ? esc_attr( $settings['text_color'] ) : '#1a1c1c';

		$slides_desktop = ! empty( $settings['slides_per_view'] ) ? intval( $settings['slides_per_view'] ) : 3;
		$slides_tablet  = ! empty( $settings['slides_per_view_tablet'] ) ? intval( $settings['slides_per_view_tablet'] ) : 2;
		$slides_mobile  = ! empty( $settings['slides_per_view_mobile'] ) ? intval( $settings['slides_per_view_mobile'] ) : 1;
		$space_between  = ! empty( $settings['space_between'] ) ? intval( $settings['space_between'] ) : 24;

		$is_autoplay    = ( isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'] ) ? 'true' : 'false';
		$autoplay_delay = ! empty( $settings['autoplay_delay'] ) ? intval( $settings['autoplay_delay'] ) : 4500;
		$speed          = ! empty( $settings['speed'] ) ? intval( $settings['speed'] ) : 750;
		$is_loop        = ( isset( $settings['loop'] ) && 'yes' === $settings['loop'] ) ? 'true' : 'false';
		$has_arrows     = ( isset( $settings['show_arrows'] ) && 'yes' === $settings['show_arrows'] );
		$has_dots       = ( isset( $settings['show_dots'] ) && 'yes' === $settings['show_dots'] );

		$view_all_text  = ! empty( $settings['view_all_text'] ) ? $settings['view_all_text'] : '';
		$view_all_url   = ! empty( $settings['view_all_link']['url'] ) ? esc_url( $settings['view_all_link']['url'] ) : '';
		?>
		<div class="dlm-library-section mipallab-library-section <?php echo ( 'grid' === $display_mode ) ? 'dlm-library-grid-section mipallab-library-grid-section' : ''; ?>" style="background-color: <?php echo esc_attr( $bg_color ); ?>; padding: 80px 24px; border-radius: 24px; font-family: 'Plus Jakarta Sans', sans-serif; position: relative;">
			<div style="max-width: 1200px; margin: 0 auto;">

				<!-- Header Area -->
				<div class="gsap-fade-up" style="text-align: center; margin-bottom: <?php echo ( 'grid' === $display_mode ) ? '36px' : '48px'; ?>;">
					<?php if ( ! empty( $section_tag ) ) : ?>
						<div style="display: inline-block; background: rgba(133, 83, 0, 0.12); color: <?php echo esc_attr( $primary_color ); ?>; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; padding: 6px 18px; border-radius: 50px; margin-bottom: 14px;">
							<?php echo esc_html( $section_tag ); ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $section_title ) ) : ?>
						<h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: <?php echo esc_attr( $text_color ); ?>; margin: 0 0 16px 0; line-height: 1.2;">
							<?php echo esc_html( $section_title ); ?>
						</h2>
					<?php endif; ?>

					<?php if ( ! empty( $section_desc ) ) : ?>
						<p style="font-size: 1.1rem; color: rgba(26, 28, 28, 0.75); max-width: 620px; margin: 0 auto; line-height: 1.7;">
							<?php echo esc_html( $section_desc ); ?>
						</p>
					<?php endif; ?>

					<?php if ( 'grid' === $display_mode ) : ?>
						<div style="max-width: 580px; margin: 24px auto 0; position: relative;">
							<input type="text" class="dlm-library-search-input mipallab-library-search-input" placeholder="<?php esc_attr_e( '🔍 Search books by title, author, or category...', 'digital-library-membership' ); ?>" style="width: 100%; padding: 14px 22px; border-radius: 50px; border: 2px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; box-shadow: 0 8px 25px rgba(0,0,0,0.04); outline-color: <?php echo esc_attr( $primary_color ); ?>;" />
							<div style="margin-top: 12px; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $primary_color ); ?>;">
								<?php esc_html_e( 'Showing', 'digital-library-membership' ); ?> <span class="dlm-book-count-num mipallab-book-count-num"><?php echo count( $books ); ?></span> <?php esc_html_e( 'Available Titles', 'digital-library-membership' ); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( 'grid' === $display_mode ) : ?>
					<!-- Full Grid Layout -->
					<div class="dlm-books-grid mipallab-books-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 28px;">
						<?php foreach ( $books as $book ) : 
							$cover = ! empty( $book['cover_image_url'] ) ? esc_url( $book['cover_image_url'] ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png';
							$title = ! empty( $book['title'] ) ? $book['title'] : esc_html__( 'Digital Book', 'digital-library-membership' );
							$author = ! empty( $book['author'] ) ? $book['author'] : esc_html__( 'Bridgeway Author', 'digital-library-membership' );
							$desc = ! empty( $book['description'] ) ? wp_trim_words( wp_strip_all_tags( $book['description'] ), 16 ) : esc_html__( 'Read this publication online in our digital library.', 'digital-library-membership' );
							$read_link = ! empty( $book['read_url'] ) ? esc_url( $book['read_url'] ) : ( ! empty( $book['file_url'] ) ? esc_url( $book['file_url'] ) : '#' );
							$cat = ! empty( $book['category'] ) ? $book['category'] : esc_html__( 'Digital Book', 'digital-library-membership' );
							$rating = ! empty( $book['rating'] ) ? $book['rating'] : '4.9';
						?>
							<div class="dlm-book-grid-item mipallab-book-grid-item dlm-hover-lift mipallab-hover-lift" data-title="<?php echo esc_attr( $title ); ?>" data-author="<?php echo esc_attr( $author ); ?>" data-category="<?php echo esc_attr( $cat ); ?>" style="background: #ffffff; border-radius: 20px; padding: 24px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 10px 30px rgba(0,0,0,0.06); display: flex; flex-direction: column; height: 100%;">
								<div style="position: relative; width: 100%; height: 260px; border-radius: 14px; overflow: hidden; background: rgba(133,83,0,0.05); margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
									<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="max-height: 240px; width: auto; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);" loading="lazy" />
								</div>

								<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
									<span style="font-size: 12px; font-weight: 700; color: <?php echo esc_attr( $primary_color ); ?>; background: rgba(133, 83, 0, 0.1); padding: 4px 10px; border-radius: 20px;"><?php echo esc_html( $cat ); ?></span>
									<span style="color: #f59e0b; font-size: 14px; font-weight: 700;">★ <?php echo esc_html( $rating ); ?></span>
								</div>

								<h3 style="font-size: 1.25rem; font-weight: 800; color: <?php echo esc_attr( $text_color ); ?>; margin: 0 0 6px 0; line-height: 1.3;">
									<?php echo esc_html( $title ); ?>
								</h3>

								<div style="font-size: 0.9rem; font-weight: 600; color: rgba(26,28,28,0.6); margin-bottom: 12px;">
									<?php esc_html_e( 'By', 'digital-library-membership' ); ?> <?php echo esc_html( $author ); ?>
								</div>

								<p style="font-size: 0.95rem; color: rgba(26,28,28,0.75); line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
									<?php echo esc_html( $desc ); ?>
								</p>

								<div style="display: flex; gap: 10px; flex-wrap: wrap;">
									<a href="<?php echo esc_url( $read_link ); ?>" style="flex: 1; background: <?php echo esc_attr( $primary_color ); ?>; color: #ffffff; padding: 12px 16px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; text-decoration: none; text-align: center; display: block; box-shadow: 0 4px 15px rgba(133, 83, 0, 0.2);">
										📖 <?php esc_html_e( 'Read Online', 'digital-library-membership' ); ?>
									</a>
									<?php if ( ! empty( $book['file_url'] ) ) : ?>
										<a href="<?php echo esc_url( $book['file_url'] ); ?>" download style="background: transparent; color: <?php echo esc_attr( $primary_color ); ?>; border: 1.5px solid <?php echo esc_attr( $primary_color ); ?>; padding: 12px 16px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; text-decoration: none; text-align: center; display: block;">
											⬇️ PDF
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<!-- Swiper Carousel Layout -->
					<div style="position: relative; width: 100%;">
						<div class="swiper dlm-swiper-container mipallab-swiper-container" 
							 data-speed="<?php echo esc_attr( $speed ); ?>" 
							 data-autoplay="<?php echo esc_attr( $is_autoplay ); ?>" 
							 data-delay="<?php echo esc_attr( $autoplay_delay ); ?>" 
							 data-loop="<?php echo esc_attr( $is_loop ); ?>" 
							 data-slides="<?php echo esc_attr( $slides_desktop ); ?>" 
							 data-slides-tablet="<?php echo esc_attr( $slides_tablet ); ?>" 
							 data-slides-mobile="<?php echo esc_attr( $slides_mobile ); ?>" 
							 data-space="<?php echo esc_attr( $space_between ); ?>">
							
							<div class="swiper-wrapper">
								<?php foreach ( $books as $book ) : 
									$cover = ! empty( $book['cover_image_url'] ) ? esc_url( $book['cover_image_url'] ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png';
									$title = ! empty( $book['title'] ) ? $book['title'] : esc_html__( 'Digital Book', 'digital-library-membership' );
									$author = ! empty( $book['author'] ) ? $book['author'] : esc_html__( 'Bridgeway Author', 'digital-library-membership' );
									$desc = ! empty( $book['description'] ) ? wp_trim_words( wp_strip_all_tags( $book['description'] ), 14 ) : esc_html__( 'Discover this exclusive digital publication in our library.', 'digital-library-membership' );
									$read_link = ! empty( $book['read_url'] ) ? esc_url( $book['read_url'] ) : ( ! empty( $book['file_url'] ) ? esc_url( $book['file_url'] ) : '#' );
									$cat = ! empty( $book['category'] ) ? $book['category'] : esc_html__( 'Digital Book', 'digital-library-membership' );
									$rating = ! empty( $book['rating'] ) ? $book['rating'] : '4.9';
								?>
									<div class="swiper-slide">
										<div class="dlm-hover-lift mipallab-hover-lift" style="background: #ffffff; border-radius: 20px; padding: 24px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 10px 30px rgba(0,0,0,0.06); display: flex; flex-direction: column; height: 100%;">
											<div style="position: relative; width: 100%; height: 260px; border-radius: 14px; overflow: hidden; background: rgba(133,83,0,0.05); margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
												<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="max-height: 240px; width: auto; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);" loading="lazy" />
											</div>

											<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
												<span style="font-size: 12px; font-weight: 700; color: <?php echo esc_attr( $primary_color ); ?>; background: rgba(133, 83, 0, 0.1); padding: 4px 10px; border-radius: 20px;"><?php echo esc_html( $cat ); ?></span>
												<span style="color: #f59e0b; font-size: 14px; font-weight: 700;">★ <?php echo esc_html( $rating ); ?></span>
											</div>

											<h3 style="font-size: 1.25rem; font-weight: 800; color: <?php echo esc_attr( $text_color ); ?>; margin: 0 0 6px 0; line-height: 1.3;">
												<?php echo esc_html( $title ); ?>
											</h3>

											<div style="font-size: 0.9rem; font-weight: 600; color: rgba(26,28,28,0.6); margin-bottom: 12px;">
												<?php esc_html_e( 'By', 'digital-library-membership' ); ?> <?php echo esc_html( $author ); ?>
											</div>

											<p style="font-size: 0.95rem; color: rgba(26,28,28,0.75); line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
												<?php echo esc_html( $desc ); ?>
											</p>

											<div style="display: flex; gap: 10px; flex-wrap: wrap;">
												<a href="<?php echo esc_url( $read_link ); ?>" style="flex: 1; background: <?php echo esc_attr( $primary_color ); ?>; color: #ffffff; padding: 12px 18px; border-radius: 10px; font-weight: 700; text-decoration: none; text-align: center; display: block; box-shadow: 0 4px 15px rgba(133, 83, 0, 0.2);">
													📖 <?php esc_html_e( 'Read Online', 'digital-library-membership' ); ?>
												</a>
												<?php if ( ! empty( $book['file_url'] ) ) : ?>
													<a href="<?php echo esc_url( $book['file_url'] ); ?>" download style="background: transparent; color: <?php echo esc_attr( $primary_color ); ?>; border: 1.5px solid <?php echo esc_attr( $primary_color ); ?>; padding: 12px 14px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; text-decoration: none; text-align: center; display: block;">
														⬇️
													</a>
												<?php endif; ?>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>

							<?php if ( $has_arrows ) : ?>
								<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-prev mipallab-swiper-nav-btn mipallab-swiper-nav-prev" aria-label="<?php esc_attr_e( 'Previous Slide', 'digital-library-membership' ); ?>">
									<svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
								</button>
								<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-next mipallab-swiper-nav-btn mipallab-swiper-nav-next" aria-label="<?php esc_attr_e( 'Next Slide', 'digital-library-membership' ); ?>">
									<svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"></polyline></svg>
								</button>
							<?php endif; ?>

							<?php if ( $has_dots ) : ?>
								<div class="swiper-pagination"></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- View All Button -->
				<?php if ( ! empty( $view_all_text ) && ! empty( $view_all_url ) ) : ?>
					<div style="text-align: center; margin-top: 48px;">
						<a href="<?php echo esc_url( $view_all_url ); ?>" class="dlm-hover-lift mipallab-hover-lift" style="display: inline-flex; align-items: center; gap: 10px; background: transparent; border: 2px solid <?php echo esc_attr( $primary_color ); ?>; color: <?php echo esc_attr( $primary_color ); ?>; padding: 14px 36px; border-radius: 12px; font-size: 1.05rem; font-weight: 800; text-decoration: none;">
							<span><?php echo esc_html( $view_all_text ); ?></span>
							<span>→</span>
						</a>
					</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
	}
}

// Backward Compatibility Class Alias
if ( ! class_exists( 'Mipallab_DLM_Library_Carousel_Widget' ) ) {
	class Mipallab_DLM_Library_Carousel_Widget extends DLM_Widget_Library_Carousel {
		public function get_name() {
			return 'mipallab_dlm_library_carousel';
		}
	}
}
