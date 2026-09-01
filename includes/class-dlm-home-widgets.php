<?php
/**
 * Home Widgets & Addons Extension for Digital Library Membership
 * Registers Elementor Widgets, GSAP Motion, Swiper Carousels, Standalone Shortcodes, and AJAX Endpoints.
 *
 * @since      3.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multi-Source Book Fetching & Normalization Engine
 */
class DLM_Books_Helper {

	/**
	 * Normalize book data from various schema formats
	 *
	 * @param array|object $row
	 * @param int          $index
	 * @return array
	 */
	public static function normalize_book( $row, $index = 0 ) {
		if ( is_object( $row ) ) {
			$row = (array) $row;
		}

		$id = $row['id'] ?? $row['ID'] ?? $row['book_id'] ?? ( $index + 1 );

		// Title detection
		$title = $row['title'] ?? $row['book_title'] ?? $row['post_title'] ?? $row['name'] ?? $row['book_name'] ?? '';
		if ( empty( $title ) ) {
			$title = esc_html__( 'Digital Book #', 'digital-library-membership' ) . $id;
		}

		// Author detection
		$author = $row['author'] ?? $row['book_author'] ?? $row['writer'] ?? $row['creator'] ?? $row['author_name'] ?? 'Bridgeway Author';

		// Description detection
		$description = $row['description'] ?? $row['book_desc'] ?? $row['desc'] ?? $row['post_content'] ?? $row['post_excerpt'] ?? $row['summary'] ?? $row['excerpt'] ?? esc_html__( 'Explore this digital publication in our collection.', 'digital-library-membership' );

		// Cover image detection
		$cover = $row['cover_image_url'] ?? $row['cover_image'] ?? $row['cover_url'] ?? $row['cover'] ?? $row['book_cover'] ?? $row['image_url'] ?? $row['image'] ?? $row['thumbnail'] ?? '';
		if ( is_numeric( $cover ) && intval( $cover ) > 0 ) {
			$att_url = wp_get_attachment_image_url( intval( $cover ), 'large' );
			if ( $att_url ) {
				$cover = $att_url;
			}
		}
		if ( empty( $cover ) ) {
			$sample_covers = array(
				'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png',
				'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg',
				'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed.jpg',
				'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg',
				'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg',
			);
			$cover = $sample_covers[ $index % count( $sample_covers ) ];
		}

		// File / Read URL detection
		$file_url = $row['file_url'] ?? $row['pdf_url'] ?? $row['download_url'] ?? $row['file'] ?? $row['download'] ?? '';
		$read_url = $row['read_url'] ?? $row['reader_url'] ?? $row['link'] ?? $row['url'] ?? $row['permalink'] ?? $file_url;
		if ( empty( $read_url ) ) {
			$read_url = $file_url ? $file_url : '#book-' . $id;
		}

		// Category / Genre
		$category = $row['category'] ?? $row['genre'] ?? $row['book_category'] ?? $row['tag'] ?? esc_html__( 'Digital Book', 'digital-library-membership' );

		// Rating
		$rating = $row['rating'] ?? '4.9';

		return array(
			'id'              => $id,
			'title'           => $title,
			'author'          => $author,
			'description'     => $description,
			'cover_image_url' => $cover,
			'file_url'        => $file_url,
			'read_url'        => $read_url,
			'category'        => $category,
			'rating'          => $rating,
			'status'          => $row['status'] ?? 'publish',
		);
	}

	/**
	 * Retrieve list of books with multi-source fallback
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function get_books( $limit = 100 ) {
		global $wpdb;
		$books = array();
		$limit = max( 1, intval( $limit ) );

		// 1. Try Custom Table wp_dlm_books
		$table_dlm_books = $wpdb->prefix . 'dlm_books';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found_table     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_dlm_books ) ) );
		if ( $found_table && strtolower( $found_table ) === strtolower( $table_dlm_books ) ) {
			$now  = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM `{$wpdb->prefix}dlm_books` WHERE (status IN ('publish', 'published', 'active', '1', '') OR status IS NULL) AND (publish_date IS NULL OR publish_date = '' OR publish_date <= %s) ORDER BY id DESC LIMIT %d",
					$now,
					$limit
				),
				ARRAY_A
			);

			if ( empty( $rows ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}dlm_books` ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
			}

			if ( ! empty( $rows ) ) {
				foreach ( $rows as $i => $row ) {
					$books[] = self::normalize_book( $row, $i );
				}
			}
		}

		// 2. Try Custom Table wp_dlm_downloads or wp_dlm_book
		if ( empty( $books ) ) {
			$allowed_tables = array(
				'dlm_downloads' => $wpdb->prefix . 'dlm_downloads',
				'dlm_book'      => $wpdb->prefix . 'dlm_book',
			);
			foreach ( $allowed_tables as $key => $table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
				if ( $found && strtolower( $found ) === strtolower( $table ) ) {
					$safe_table = ( 'dlm_downloads' === $key ) ? "{$wpdb->prefix}dlm_downloads" : "{$wpdb->prefix}dlm_book";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$safe_table}` ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
					if ( ! empty( $rows ) ) {
						foreach ( $rows as $i => $row ) {
							$books[] = self::normalize_book( $row, $i );
						}
						break;
					}
				}
			}
		}

		// 3. Try WordPress Custom Post Types: dlm_download, dlm_book, book, digital_book, download
		if ( empty( $books ) ) {
			$post_types = array( 'dlm_download', 'dlm_book', 'book', 'digital_book', 'download' );
			foreach ( $post_types as $pt ) {
				if ( post_type_exists( $pt ) ) {
					$posts = get_posts(
						array(
							'post_type'        => $pt,
							'post_status'      => array( 'publish', 'inherit' ),
							'numberposts'      => $limit,
							'orderby'          => 'date',
							'order'            => 'DESC',
							'suppress_filters' => false,
							'no_found_rows'    => true,
						)
					);
					if ( ! empty( $posts ) ) {
						foreach ( $posts as $i => $p ) {
							$thumb       = get_the_post_thumbnail_url( $p->ID, 'large' );
							$author_meta = get_post_meta( $p->ID, 'author', true ) ?: get_post_meta( $p->ID, '_dlm_author', true ) ?: get_post_meta( $p->ID, 'book_author', true ) ?: get_the_author_meta( 'display_name', $p->post_author );
							$file_meta   = get_post_meta( $p->ID, '_download_url', true ) ?: get_post_meta( $p->ID, 'file_url', true ) ?: get_post_meta( $p->ID, 'pdf_url', true ) ?: '';

							$books[] = self::normalize_book(
								array(
									'id'              => $p->ID,
									'title'           => $p->post_title,
									'author'          => $author_meta,
									'description'     => ! empty( $p->post_excerpt ) ? $p->post_excerpt : $p->post_content,
									'cover_image_url' => $thumb,
									'file_url'        => $file_meta,
									'read_url'        => get_permalink( $p->ID ),
								),
								$i
							);
						}
						wp_reset_postdata();
						break;
					}
				}
			}
		}

		// 4. Default High Quality Books Fallback (Guarantees Library is NEVER Blank)
		if ( empty( $books ) ) {
			$sample_books = array(
				array(
					'id'              => 1,
					'title'           => 'THE AI JOB SHIFT',
					'author'          => 'Bridgeway36',
					'description'     => 'Future-proof your career with artificial intelligence. A practical roadmap to adapt, upskill, and thrive in an AI-driven global workforce.',
					'cover_image_url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png',
					'file_url'        => '',
					'read_url'        => '#',
					'category'        => 'AI & Technology',
					'rating'          => '4.9',
				),
				array(
					'id'              => 2,
					'title'           => 'Light and Shadow',
					'author'          => 'Avery Noble',
					'description'     => 'Architectural silence, sensory hierarchy, and material truth carved from granite. Exploring space, shadow, and quiet design philosophy.',
					'cover_image_url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg',
					'file_url'        => '',
					'read_url'        => '#',
					'category'        => 'Architecture & Design',
					'rating'          => '5.0',
				),
				array(
					'id'              => 3,
					'title'           => 'The Quiet Room',
					'author'          => 'Elias Vance',
					'description'     => 'Tactile honesty and soft light designed for deep focus, creative tranquility, and intentional modern living spaces.',
					'cover_image_url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed.jpg',
					'file_url'        => '',
					'read_url'        => '#',
					'category'        => 'Living & Philosophy',
					'rating'          => '4.8',
				),
				array(
					'id'              => 4,
					'title'           => 'Spectrum of Thought',
					'author'          => 'Marcus Webb',
					'description'     => 'Exploring cognitive depth, lateral thinking, and visual clarity in modern writing, business leadership, and research methodology.',
					'cover_image_url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg',
					'file_url'        => '',
					'read_url'        => '#',
					'category'        => 'Cognitive Science',
					'rating'          => '4.9',
				),
				array(
					'id'              => 5,
					'title'           => 'Minimalist Living',
					'author'          => 'Lina Eklund',
					'description'     => 'Removing noise to reveal the fundamental foundation of life, daily clarity, and intentional habits that sustain well-being.',
					'cover_image_url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg',
					'file_url'        => '',
					'read_url'        => '#',
					'category'        => 'Lifestyle & Well-being',
					'rating'          => '4.9',
				),
			);
			foreach ( $sample_books as $i => $sb ) {
				$books[] = self::normalize_book( $sb, $i );
			}
		}

		return $books;
	}
}

// Backward Compatibility Class Alias for Books Helper
if ( ! class_exists( 'Mipallab_Books_Helper' ) ) {
	class_alias( 'DLM_Books_Helper', 'Mipallab_Books_Helper' );
}

/**
 * Master Home Widgets Extension Class
 */
final class DLM_Home_Widgets {

	/**
	 * Singleton instance
	 *
	 * @var DLM_Home_Widgets|null
	 */
	private static $_instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return DLM_Home_Widgets
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks, categories, widgets, assets, shortcodes, and AJAX
	 */
	public function init() {
		// Elementor Categories & Widgets (Priority 999 ensures category sits at the very TOP)
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_categories' ), 999 );
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_elementor_widgets_legacy' ) );

		// Asset Enqueues (Frontend, Elementor Editor, Preview Iframe)
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
		add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_assets' ), 999 );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_assets' ), 999 );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_assets' ), 999 );

		// Safe Shortcodes: Library Carousel & Grid
		$this->register_shortcode_safe( 'dlm_library_carousel', array( $this, 'render_library_carousel_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_library_carousel', array( $this, 'render_library_carousel_shortcode' ) );
		$this->register_shortcode_safe( 'dlm_library_grid', array( $this, 'render_library_grid_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_library_grid', array( $this, 'render_library_grid_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_library', array( $this, 'render_library_grid_shortcode' ) );

		// Safe Shortcodes: Membership & Pricing
		$this->register_shortcode_safe( 'dlm_membership', array( $this, 'render_membership_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_membership', array( $this, 'render_membership_shortcode' ) );

		// Safe Shortcodes: Review Switcher
		$this->register_shortcode_safe( 'dlm_review_switcher', array( $this, 'render_review_switcher_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_review_switcher', array( $this, 'render_review_switcher_shortcode' ) );

		// Safe Shortcodes: Contact Form
		$this->register_shortcode_safe( 'dlm_contact_form', array( $this, 'render_contact_form_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_contact_form', array( $this, 'render_contact_form_shortcode' ) );

		// Safe Shortcodes: Hero Slider & About Author
		$this->register_shortcode_safe( 'dlm_hero_slider', array( $this, 'render_hero_slider_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_hero_slider', array( $this, 'render_hero_slider_shortcode' ) );
		$this->register_shortcode_safe( 'dlm_about_author', array( $this, 'render_about_author_shortcode' ) );
		$this->register_shortcode_safe( 'mipallab_about_author', array( $this, 'render_about_author_shortcode' ) );

		// AJAX Form Submissions
		add_action( 'wp_ajax_dlm_contact_form_submit', array( $this, 'handle_contact_submit' ) );
		add_action( 'wp_ajax_nopriv_dlm_contact_form_submit', array( $this, 'handle_contact_submit' ) );
		add_action( 'wp_ajax_mipallab_contact_form_submit', array( $this, 'handle_contact_submit' ) );
		add_action( 'wp_ajax_nopriv_mipallab_contact_form_submit', array( $this, 'handle_contact_submit' ) );
	}

	/**
	 * Safely register shortcode without overwriting core DLM shortcodes unless intended
	 */
	private function register_shortcode_safe( $tag, $callback ) {
		if ( ! function_exists( 'shortcode_exists' ) || ! shortcode_exists( $tag ) ) {
			add_shortcode( $tag, $callback );
		} else {
			if ( strpos( $tag, 'mipallab_' ) === 0 || strpos( $tag, 'dlm_' ) === 0 ) {
				add_shortcode( $tag, $callback );
			}
		}
	}

	/**
	 * Register Custom Elementor Categories and place them at the very top of the editor
	 */
	public function register_elementor_categories( $elements_manager ) {
		if ( ! is_object( $elements_manager ) ) {
			return;
		}

		if ( method_exists( $elements_manager, 'add_category' ) ) {
			$elements_manager->add_category(
				'digital-library',
				array(
					'title' => esc_html__( 'Digital Library', 'digital-library-membership' ),
					'icon'  => 'eicon-book',
				)
			);
		}

		// Reorder categories to place Digital Library at the very top of the Elementor edit sidebar
		try {
			$reorder_cats = function () {
				if ( ! isset( $this->categories ) || ! is_array( $this->categories ) ) {
					return;
				}

				$top_slugs = array( 'digital-library' );
				$top_cats  = array();

				foreach ( $top_slugs as $slug ) {
					if ( isset( $this->categories[ $slug ] ) ) {
						$top_cats[ $slug ] = $this->categories[ $slug ];
						unset( $this->categories[ $slug ] );
					}
				}

				if ( ! empty( $top_cats ) ) {
					$this->categories = $top_cats + $this->categories;
				}
			};

			$reorder_cats->call( $elements_manager );
		} catch ( \Throwable $e ) {
			try {
				$reflection = new \ReflectionClass( $elements_manager );
				if ( $reflection->hasProperty( 'categories' ) ) {
					$prop = $reflection->getProperty( 'categories' );
					$prop->setAccessible( true );
					$categories = $prop->getValue( $elements_manager );
					if ( is_array( $categories ) ) {
						$top_slugs = array( 'digital-library' );
						$top_cats  = array();
						foreach ( $top_slugs as $slug ) {
							if ( isset( $categories[ $slug ] ) ) {
								$top_cats[ $slug ] = $categories[ $slug ];
								unset( $categories[ $slug ] );
							}
						}
						if ( ! empty( $top_cats ) ) {
							$prop->setValue( $elements_manager, $top_cats + $categories );
						}
					}
				}
			} catch ( \Throwable $ex ) {
				// Silently fail safe
			}
		}
	}

	/**
	 * Include all widget class files
	 */
	private function include_widget_files() {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		$widgets = array(
			'class-dlm-widget-hero-book-slider.php',
			'class-dlm-widget-about-author.php',
			'class-dlm-widget-library-carousel.php',
			'class-dlm-widget-membership-section.php',
			'class-dlm-widget-review-switcher.php',
			'class-dlm-widget-contact-section.php',
		);

		foreach ( $widgets as $widget_file ) {
			$file_path = DLM_PATH . 'includes/widgets/' . $widget_file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Register Elementor Widgets (Elementor 3.5.0+)
	 */
	public function register_elementor_widgets( $widgets_manager ) {
		$this->include_widget_files();

		// Register canonical DLM widgets
		if ( class_exists( '\DLM_Widget_Hero_Book_Slider' ) ) {
			$widgets_manager->register( new \DLM_Widget_Hero_Book_Slider() );
		}
		if ( class_exists( '\DLM_Widget_About_Author' ) ) {
			$widgets_manager->register( new \DLM_Widget_About_Author() );
		}
		if ( class_exists( '\DLM_Widget_Library_Carousel' ) ) {
			$widgets_manager->register( new \DLM_Widget_Library_Carousel() );
		}
		if ( class_exists( '\DLM_Widget_Membership_Section' ) ) {
			$widgets_manager->register( new \DLM_Widget_Membership_Section() );
		}
		if ( class_exists( '\DLM_Widget_Review_Switcher' ) ) {
			$widgets_manager->register( new \DLM_Widget_Review_Switcher() );
		}
		if ( class_exists( '\DLM_Widget_Contact_Section' ) ) {
			$widgets_manager->register( new \DLM_Widget_Contact_Section() );
		}
	}

	/**
	 * Register Elementor Widgets (Legacy)
	 */
	public function register_elementor_widgets_legacy( $widgets_manager ) {
		$this->include_widget_files();

		if ( class_exists( '\DLM_Widget_Hero_Book_Slider' ) ) {
			$widgets_manager->register_widget_type( new \DLM_Widget_Hero_Book_Slider() );
		}
		if ( class_exists( '\DLM_Widget_About_Author' ) ) {
			$widgets_manager->register_widget_type( new \DLM_Widget_About_Author() );
		}
		if ( class_exists( '\DLM_Widget_Library_Carousel' ) ) {
			$widgets_manager->register_widget_type( new \DLM_Widget_Library_Carousel() );
		}
		if ( class_exists( '\DLM_Widget_Membership_Section' ) ) {
			$widgets_manager->register_widget_type( new \DLM_Widget_Membership_Section() );
		}
		if ( class_exists( '\DLM_Widget_Review_Switcher' ) ) {
			$widgets_manager->register_widget_type( new \DLM_Widget_Review_Switcher() );
		}
		if ( class_exists( '\DLM_Widget_Contact_Section' ) ) {
			$widgets_manager->register_widget_type( new \DLM_Widget_Contact_Section() );
		}
	}

	/**
	 * Enqueue Google Fonts, GSAP, Swiper, and Custom Styles/Scripts
	 */
	public function enqueue_assets() {
		// Enqueue Google Fonts
		wp_enqueue_style( 'dlm-google-fonts-plus-jakarta', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,700&display=swap', array(), DLM_VERSION );

		// Enqueue Local Swiper CSS & JS
		wp_enqueue_style( 'dlm-swiper-bundle-css', DLM_URL . 'public/vendor/swiper-bundle.min.css', array(), '11.0.5' );
		wp_enqueue_script( 'dlm-swiper-bundle-js', DLM_URL . 'public/vendor/swiper-bundle.min.js', array( 'jquery' ), '11.0.5', true );

		// Enqueue Local GSAP & ScrollTrigger
		wp_enqueue_script( 'dlm-gsap-core', DLM_URL . 'public/vendor/gsap.min.js', array(), '3.12.5', true );
		wp_enqueue_script( 'dlm-gsap-scrolltrigger', DLM_URL . 'public/vendor/ScrollTrigger.min.js', array( 'dlm-gsap-core' ), '3.12.5', true );

		// Enqueue Scoped Responsive CSS
		wp_enqueue_style( 'dlm-home-widgets-css', DLM_URL . 'public/css/dlm-home-widgets.css', array( 'dlm-swiper-bundle-css' ), DLM_VERSION );

		// Enqueue Initializer Script
		wp_enqueue_script( 'dlm-home-widgets-js', DLM_URL . 'public/js/dlm-home-widgets.js', array( 'jquery', 'dlm-swiper-bundle-js', 'dlm-gsap-core' ), DLM_VERSION, true );

		// Pass Ajax Config & Nonces
		$nonce = wp_create_nonce( 'dlm_contact_nonce' );
		wp_localize_script(
			'dlm-home-widgets-js',
			'dlm_home_widgets_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => $nonce,
			)
		);

		wp_localize_script(
			'dlm-home-widgets-js',
			'mipallab_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => $nonce,
			)
		);
	}

	/**
	 * Shortcode: [dlm_library_carousel] / [mipallab_library_carousel]
	 */
	public function render_library_carousel_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'         => 12,
				'slides'        => 3,
				'slides_tablet' => 2,
				'slides_mobile' => 1,
				'speed'         => 750,
				'autoplay'      => 'true',
				'delay'         => 4500,
				'loop'          => 'true',
				'primary_color' => '#855300',
				'bg_color'      => 'rgba(133, 83, 0, 0.08)',
			),
			$atts,
			'dlm_library_carousel'
		);

		$books = DLM_Books_Helper::get_books( intval( $atts['limit'] ) );

		ob_start();
		?>
		<div class="dlm-library-section mipallab-library-section" style="background: <?php echo esc_attr( $atts['bg_color'] ); ?>; padding: 60px 24px; border-radius: 24px; font-family: 'Plus Jakarta Sans', sans-serif; position: relative;">
			<div class="swiper dlm-swiper-container mipallab-swiper-container" 
				 data-speed="<?php echo esc_attr( $atts['speed'] ); ?>" 
				 data-autoplay="<?php echo esc_attr( $atts['autoplay'] ); ?>" 
				 data-delay="<?php echo esc_attr( $atts['delay'] ); ?>"
				 data-loop="<?php echo esc_attr( $atts['loop'] ); ?>"
				 data-slides="<?php echo esc_attr( $atts['slides'] ); ?>"
				 data-slides-tablet="<?php echo esc_attr( $atts['slides_tablet'] ); ?>"
				 data-slides-mobile="<?php echo esc_attr( $atts['slides_mobile'] ); ?>">
				
				<div class="swiper-wrapper">
					<?php foreach ( $books as $book ) : 
						$cover     = ! empty( $book['cover_image_url'] ) ? esc_url( $book['cover_image_url'] ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png';
						$title     = ! empty( $book['title'] ) ? $book['title'] : esc_html__( 'Digital Book', 'digital-library-membership' );
						$author    = ! empty( $book['author'] ) ? $book['author'] : esc_html__( 'Author', 'digital-library-membership' );
						$desc      = ! empty( $book['description'] ) ? wp_trim_words( wp_strip_all_tags( $book['description'] ), 14 ) : esc_html__( 'Read this digital publication in our library.', 'digital-library-membership' );
						$read_link = ! empty( $book['read_url'] ) ? esc_url( $book['read_url'] ) : ( ! empty( $book['file_url'] ) ? esc_url( $book['file_url'] ) : '#' );
					?>
						<div class="swiper-slide">
							<div class="dlm-hover-lift mipallab-hover-lift" style="background: #ffffff; border-radius: 20px; padding: 24px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 10px 30px rgba(0,0,0,0.06); display: flex; flex-direction: column; height: 100%;">
								<div style="position: relative; width: 100%; height: 260px; border-radius: 14px; overflow: hidden; background: rgba(133,83,0,0.05); margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
									<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="max-height: 240px; width: auto; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);" loading="lazy" />
								</div>

								<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
									<span style="font-size: 12px; font-weight: 700; color: <?php echo esc_attr( $atts['primary_color'] ); ?>; background: rgba(133, 83, 0, 0.1); padding: 4px 10px; border-radius: 20px;"><?php echo esc_html( $book['category'] ); ?></span>
									<span style="color: #f59e0b; font-size: 14px;">★★★★★</span>
								</div>

								<h3 style="font-size: 1.25rem; font-weight: 800; color: #1a1c1c; margin: 0 0 6px 0; line-height: 1.3;">
									<?php echo esc_html( $title ); ?>
								</h3>

								<div style="font-size: 0.9rem; font-weight: 600; color: rgba(26,28,28,0.6); margin-bottom: 12px;">
									<?php esc_html_e( 'By', 'digital-library-membership' ); ?> <?php echo esc_html( $author ); ?>
								</div>

								<p style="font-size: 0.95rem; color: rgba(26,28,28,0.75); line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
									<?php echo esc_html( $desc ); ?>
								</p>

								<a href="<?php echo esc_url( $read_link ); ?>" style="background: <?php echo esc_attr( $atts['primary_color'] ); ?>; color: #ffffff; padding: 12px 20px; border-radius: 10px; font-weight: 700; text-decoration: none; text-align: center; display: block; box-shadow: 0 4px 15px rgba(133, 83, 0, 0.2);">
									📖 <?php esc_html_e( 'Read Online', 'digital-library-membership' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-prev mipallab-swiper-nav-btn mipallab-swiper-nav-prev" aria-label="<?php esc_attr_e( 'Previous Slide', 'digital-library-membership' ); ?>">
					<svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-next mipallab-swiper-nav-btn mipallab-swiper-nav-next" aria-label="<?php esc_attr_e( 'Next Slide', 'digital-library-membership' ); ?>">
					<svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>

				<div class="swiper-pagination"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [dlm_library_grid] / [mipallab_library_grid]
	 */
	public function render_library_grid_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'         => 50,
				'primary_color' => '#855300',
				'bg_color'      => 'rgba(133, 83, 0, 0.04)',
				'title'         => esc_html__( 'Digital Library Collection', 'digital-library-membership' ),
				'tag'           => esc_html__( 'BROWSE OUR COMPLETE CATALOG', 'digital-library-membership' ),
			),
			$atts,
			'dlm_library_grid'
		);

		$books   = DLM_Books_Helper::get_books( intval( $atts['limit'] ) );
		$primary = esc_attr( $atts['primary_color'] );

		ob_start();
		?>
		<div class="dlm-library-section dlm-library-grid-section mipallab-library-section mipallab-library-grid-section" style="background: <?php echo esc_attr( $atts['bg_color'] ); ?>; padding: 60px 24px; border-radius: 24px; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div style="max-width: 1200px; margin: 0 auto;">
				
				<div style="text-align: center; margin-bottom: 40px;">
					<div style="display: inline-block; background: rgba(133, 83, 0, 0.12); color: <?php echo esc_attr( $primary ); ?>; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; padding: 6px 18px; border-radius: 50px; margin-bottom: 12px;">
						<?php echo esc_html( $atts['tag'] ); ?>
					</div>
					<h2 style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; color: #1a1c1c; margin: 0 0 20px 0;">
						<?php echo esc_html( $atts['title'] ); ?>
					</h2>

					<div style="max-width: 580px; margin: 0 auto; position: relative;">
						<input type="text" class="dlm-library-search-input mipallab-library-search-input" placeholder="<?php esc_attr_e( '🔍 Search books by title, author, or topic...', 'digital-library-membership' ); ?>" style="width: 100%; padding: 14px 22px; border-radius: 50px; border: 2px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; box-shadow: 0 8px 25px rgba(0,0,0,0.04); outline-color: <?php echo esc_attr( $primary ); ?>;" />
						<div style="margin-top: 12px; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $primary ); ?>;">
							<?php esc_html_e( 'Showing', 'digital-library-membership' ); ?> <span class="dlm-book-count-num mipallab-book-count-num"><?php echo count( $books ); ?></span> <?php esc_html_e( 'Available Titles', 'digital-library-membership' ); ?>
						</div>
					</div>
				</div>

				<div class="dlm-books-grid mipallab-books-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 28px;">
					<?php foreach ( $books as $book ) : 
						$cover     = ! empty( $book['cover_image_url'] ) ? esc_url( $book['cover_image_url'] ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/the-ai-job-shift.png';
						$title     = ! empty( $book['title'] ) ? $book['title'] : esc_html__( 'Digital Book', 'digital-library-membership' );
						$author    = ! empty( $book['author'] ) ? $book['author'] : esc_html__( 'Bridgeway Author', 'digital-library-membership' );
						$desc      = ! empty( $book['description'] ) ? wp_trim_words( wp_strip_all_tags( $book['description'] ), 16 ) : esc_html__( 'Read this publication online in our digital library.', 'digital-library-membership' );
						$read_link = ! empty( $book['read_url'] ) ? esc_url( $book['read_url'] ) : ( ! empty( $book['file_url'] ) ? esc_url( $book['file_url'] ) : '#' );
						$cat       = ! empty( $book['category'] ) ? $book['category'] : esc_html__( 'Digital Book', 'digital-library-membership' );
						$rating    = ! empty( $book['rating'] ) ? $book['rating'] : '4.9';
					?>
						<div class="dlm-book-grid-item mipallab-book-grid-item dlm-hover-lift mipallab-hover-lift" data-title="<?php echo esc_attr( $title ); ?>" data-author="<?php echo esc_attr( $author ); ?>" data-category="<?php echo esc_attr( $cat ); ?>" style="background: #ffffff; border-radius: 20px; padding: 24px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 10px 30px rgba(0,0,0,0.06); display: flex; flex-direction: column; height: 100%;">
							<div style="position: relative; width: 100%; height: 260px; border-radius: 14px; overflow: hidden; background: rgba(133,83,0,0.05); margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
								<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="max-height: 240px; width: auto; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);" loading="lazy" />
							</div>

							<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
								<span style="font-size: 12px; font-weight: 700; color: <?php echo esc_attr( $primary ); ?>; background: rgba(133, 83, 0, 0.1); padding: 4px 10px; border-radius: 20px;"><?php echo esc_html( $cat ); ?></span>
								<span style="color: #f59e0b; font-size: 14px; font-weight: 700;">★ <?php echo esc_html( $rating ); ?></span>
							</div>

							<h3 style="font-size: 1.25rem; font-weight: 800; color: #1a1c1c; margin: 0 0 6px 0; line-height: 1.3;">
								<?php echo esc_html( $title ); ?>
							</h3>

							<div style="font-size: 0.9rem; font-weight: 600; color: rgba(26,28,28,0.6); margin-bottom: 12px;">
								<?php esc_html_e( 'By', 'digital-library-membership' ); ?> <?php echo esc_html( $author ); ?>
							</div>

							<p style="font-size: 0.95rem; color: rgba(26,28,28,0.75); line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
								<?php echo esc_html( $desc ); ?>
							</p>

							<div style="display: flex; gap: 10px; flex-wrap: wrap;">
								<a href="<?php echo esc_url( $read_link ); ?>" style="flex: 1; background: <?php echo esc_attr( $primary ); ?>; color: #ffffff; padding: 12px 16px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; text-decoration: none; text-align: center; display: block; box-shadow: 0 4px 15px rgba(133, 83, 0, 0.2);">
									📖 <?php esc_html_e( 'Read Online', 'digital-library-membership' ); ?>
								</a>
								<?php if ( ! empty( $book['file_url'] ) ) : ?>
									<a href="<?php echo esc_url( $book['file_url'] ); ?>" download style="background: transparent; color: <?php echo esc_attr( $primary ); ?>; border: 1.5px solid <?php echo esc_attr( $primary ); ?>; padding: 12px 16px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; text-decoration: none; text-align: center; display: block;">
										⬇️ PDF
									</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render HTML for Review Switcher (Self-Contained Engine with Video, Google & Text controls)
	 */
	public static function render_review_switcher_html( $section_tag = 'COMMUNITY TESTIMONIALS', $section_title = 'What Readers Say About Our Library', $video_items = array(), $text_items = array(), $google_score = '4.9 ★★★★★', $google_subtext = 'Based on 1,280+ Verified Google Reviews', $primary_color = '#855300', $text_color = '#1a1c1c', $extra = array() ) {
		$primary     = ! empty( $primary_color ) ? esc_attr( $primary_color ) : '#855300';
		$text        = ! empty( $text_color ) ? esc_attr( $text_color ) : '#1a1c1c';
		$card_bg     = ! empty( $extra['card_bg'] ) ? esc_attr( $extra['card_bg'] ) : '#ffffff';
		$star_color  = ! empty( $extra['star_color'] ) ? esc_attr( $extra['star_color'] ) : '#f59e0b';
		$uid         = 'dlm_rev_' . wp_rand( 1000, 9999 );

		$show_switcher_nav = isset( $extra['show_switcher_nav'] ) ? ( $extra['show_switcher_nav'] === 'yes' || $extra['show_switcher_nav'] === true || $extra['show_switcher_nav'] === '1' ) : true;
		$enable_video_tab  = isset( $extra['enable_video_tab'] ) ? ( $extra['enable_video_tab'] === 'yes' || $extra['enable_video_tab'] === true || $extra['enable_video_tab'] === '1' ) : true;
		$enable_text_tab   = isset( $extra['enable_text_tab'] ) ? ( $extra['enable_text_tab'] === 'yes' || $extra['enable_text_tab'] === true || $extra['enable_text_tab'] === '1' ) : true;
		$enable_google_tab = isset( $extra['enable_google_tab'] ) ? ( $extra['enable_google_tab'] === 'yes' || $extra['enable_google_tab'] === true || $extra['enable_google_tab'] === '1' ) : true;

		$video_tab_label  = ! empty( $extra['video_tab_label'] ) ? $extra['video_tab_label'] : esc_html__( '🎥 Video Reviews', 'digital-library-membership' );
		$text_tab_label   = ! empty( $extra['text_tab_label'] ) ? $extra['text_tab_label'] : esc_html__( '💬 Text Testimonials', 'digital-library-membership' );
		$google_tab_label = ! empty( $extra['google_tab_label'] ) ? $extra['google_tab_label'] : esc_html__( '⭐ Google Reviews', 'digital-library-membership' );

		// Determine initial active tab
		$default_tab = ! empty( $extra['default_active_tab'] ) ? $extra['default_active_tab'] : 'video';
		if ( 'video' === $default_tab && ! $enable_video_tab ) {
			$default_tab = $enable_text_tab ? 'text' : ( $enable_google_tab ? 'google' : 'video' );
		} elseif ( 'text' === $default_tab && ! $enable_text_tab ) {
			$default_tab = $enable_video_tab ? 'video' : ( $enable_google_tab ? 'google' : 'text' );
		} elseif ( 'google' === $default_tab && ! $enable_google_tab ) {
			$default_tab = $enable_video_tab ? 'video' : ( $enable_text_tab ? 'text' : 'google' );
		}

		$google_mode          = ! empty( $extra['google_mode'] ) ? $extra['google_mode'] : 'manual';
		$google_business_name = ! empty( $extra['google_business_name'] ) ? $extra['google_business_name'] : esc_html__( 'Digital Library & Research Hub', 'digital-library-membership' );
		$google_place_url     = ! empty( $extra['google_place_url'] ) ? $extra['google_place_url'] : 'https://maps.google.com';
		$google_btn_text      = ! empty( $extra['google_btn_text'] ) ? $extra['google_btn_text'] : esc_html__( 'Write a Review on Google', 'digital-library-membership' );
		$google_shortcode     = ! empty( $extra['google_shortcode'] ) ? $extra['google_shortcode'] : '';
		$google_review_items  = ! empty( $extra['google_review_items'] ) && is_array( $extra['google_review_items'] ) ? $extra['google_review_items'] : array();

		// Default Google review sample items if empty
		if ( empty( $google_review_items ) && 'manual' === $google_mode ) {
			$google_review_items = array(
				array(
					'author_name'   => 'David Miller',
					'rating'        => '5',
					'time_ago'      => '3 days ago',
					'review_text'   => 'Incredible digital collection! The 3D reader experience is unmatched and downloading reference PDFs is instantaneous.',
					'author_avatar' => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg' ),
					'is_verified'   => 'yes',
				),
				array(
					'author_name'   => 'Sophia Alverez',
					'rating'        => '5',
					'time_ago'      => '1 week ago',
					'review_text'   => 'Verified subscriber here. The research publications and book selection have been indispensable for our academic team.',
					'author_avatar' => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg' ),
					'is_verified'   => 'yes',
				),
			);
		}
		?>
		<div class="dlm-review-section mipallab-review-section" style="padding: 80px 24px; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div style="max-width: 1100px; margin: 0 auto;">

				<div class="gsap-fade-up" style="text-align: center; margin-bottom: 40px;">
					<?php if ( ! empty( $section_tag ) ) : ?>
						<div style="display: inline-block; color: <?php echo esc_attr( $primary ); ?>; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; margin-bottom: 12px; text-transform: uppercase;">
							<?php echo esc_html( $section_tag ); ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $section_title ) ) : ?>
						<h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 16px 0; line-height: 1.2;">
							<?php echo esc_html( $section_title ); ?>
						</h2>
					<?php endif; ?>

					<?php if ( ! empty( $extra['section_desc'] ) ) : ?>
						<p style="font-size: 1.05rem; color: rgba(26, 28, 28, 0.75); max-width: 650px; margin: 0 auto; line-height: 1.6;">
							<?php echo esc_html( $extra['section_desc'] ); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="dlm-review-switcher-wrapper mipallab-review-switcher-wrapper" data-primary-color="<?php echo esc_attr( $primary ); ?>" data-text-color="<?php echo esc_attr( $text ); ?>">
					
					<?php if ( $show_switcher_nav ) : ?>
						<!-- Interactive Tab Buttons -->
						<div style="text-align: center; margin-bottom: 36px;">
							<div style="display: inline-flex; background: rgba(133, 83, 0, 0.08); padding: 6px; border-radius: 50px; gap: 6px; flex-wrap: wrap; justify-content: center;">
								<?php if ( $enable_video_tab ) : ?>
									<button type="button" class="dlm-review-tab-btn mipallab-review-tab-btn <?php echo 'video' === $default_tab ? 'active' : ''; ?>" data-pane="<?php echo esc_attr( $uid ); ?>_video" data-target="<?php echo esc_attr( $uid ); ?>_video" onclick="switchDLMTab(this, '<?php echo esc_js( $uid ); ?>_video')" style="background: <?php echo 'video' === $default_tab ? esc_attr( $primary ) : 'transparent'; ?>; color: <?php echo 'video' === $default_tab ? '#ffffff' : esc_attr( $text ); ?>; border: none; padding: 10px 24px; border-radius: 40px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.25s ease;">
										<?php echo esc_html( $video_tab_label ); ?>
									</button>
								<?php endif; ?>

								<?php if ( $enable_text_tab ) : ?>
									<button type="button" class="dlm-review-tab-btn mipallab-review-tab-btn <?php echo 'text' === $default_tab ? 'active' : ''; ?>" data-pane="<?php echo esc_attr( $uid ); ?>_text" data-target="<?php echo esc_attr( $uid ); ?>_text" onclick="switchDLMTab(this, '<?php echo esc_js( $uid ); ?>_text')" style="background: <?php echo 'text' === $default_tab ? esc_attr( $primary ) : 'transparent'; ?>; color: <?php echo 'text' === $default_tab ? '#ffffff' : esc_attr( $text ); ?>; border: none; padding: 10px 24px; border-radius: 40px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.25s ease;">
										<?php echo esc_html( $text_tab_label ); ?>
									</button>
								<?php endif; ?>

								<?php if ( $enable_google_tab ) : ?>
									<button type="button" class="dlm-review-tab-btn mipallab-review-tab-btn <?php echo 'google' === $default_tab ? 'active' : ''; ?>" data-pane="<?php echo esc_attr( $uid ); ?>_google" data-target="<?php echo esc_attr( $uid ); ?>_google" onclick="switchDLMTab(this, '<?php echo esc_js( $uid ); ?>_google')" style="background: <?php echo 'google' === $default_tab ? esc_attr( $primary ) : 'transparent'; ?>; color: <?php echo 'google' === $default_tab ? '#ffffff' : esc_attr( $text ); ?>; border: none; padding: 10px 24px; border-radius: 40px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.25s ease;">
										<?php echo esc_html( $google_tab_label ); ?>
									</button>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $enable_video_tab ) : ?>
						<!-- Pane 1: Video Reviews -->
						<div id="<?php echo esc_attr( $uid ); ?>_video" class="dlm-rev-pane dlm-review-content-pane mipallab-rev-pane mipallab-review-content-pane" style="display: <?php echo ( 'video' === $default_tab || ! $show_switcher_nav ) ? 'block' : 'none'; ?>;">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px;">
								<?php foreach ( $video_items as $vitem ) : 
									$raw_vurl = ! empty( $vitem['video_url'] ) ? trim( $vitem['video_url'] ) : 'https://www.youtube.com/embed/dQw4w9WgXcQ';
									$vurl     = $raw_vurl;
									if ( strpos( $vurl, 'watch?v=' ) !== false ) {
										$vurl = preg_replace( '/.*watch\?v=([a-zA-Z0-9_-]+).*/', 'https://www.youtube.com/embed/$1', $vurl );
									} elseif ( strpos( $vurl, 'youtu.be/' ) !== false ) {
										$vurl = preg_replace( '/.*youtu\.be\/([a-zA-Z0-9_-]+).*/', 'https://www.youtube.com/embed/$1', $vurl );
									} elseif ( strpos( $vurl, 'vimeo.com/' ) !== false && strpos( $vurl, 'player.vimeo.com' ) === false ) {
										$vurl = preg_replace( '/.*vimeo\.com\/([0-9]+).*/', 'https://player.vimeo.com/video/$1', $vurl );
									}
									$vname   = ! empty( $vitem['name'] ) ? $vitem['name'] : 'Verified Reader';
									$vrole   = ! empty( $vitem['role'] ) ? $vitem['role'] : 'Library Member';
									$vrating = ! empty( $vitem['rating'] ) ? intval( $vitem['rating'] ) : 5;
								?>
									<div class="dlm-hover-lift mipallab-hover-lift" style="background: <?php echo esc_attr( $card_bg ); ?>; border-radius: 20px; overflow: hidden; border: 1px solid rgba(133, 83, 0, 0.15); box-shadow: 0 10px 30px rgba(0,0,0,0.06);">
										<div style="position: relative; padding-bottom: 56.25%; height: 0; background: #000;">
											<iframe src="<?php echo esc_url( $vurl ); ?>" title="<?php echo esc_attr( $vname ); ?>" style="position: absolute; top:0; left:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
										</div>
										<div style="padding: 20px;">
											<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
												<div style="font-size: 1.1rem; font-weight: 800; color: <?php echo esc_attr( $text ); ?>;"><?php echo esc_html( $vname ); ?></div>
												<div style="color: <?php echo esc_attr( $star_color ); ?>; font-size: 15px;">
													<?php echo esc_html( str_repeat( '★', max( 1, min( 5, $vrating ) ) ) ); ?>
												</div>
											</div>
											<div style="color: <?php echo esc_attr( $primary ); ?>; font-size: 0.9rem; font-weight: 700;"><?php echo esc_html( $vrole ); ?></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $enable_text_tab ) : ?>
						<!-- Pane 2: Text Testimonials -->
						<div id="<?php echo esc_attr( $uid ); ?>_text" class="dlm-rev-pane dlm-review-content-pane mipallab-rev-pane mipallab-review-content-pane" style="display: <?php echo ( 'text' === $default_tab && ( $show_switcher_nav || ! $enable_video_tab ) ) ? 'block' : 'none'; ?>;">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px;">
								<?php foreach ( $text_items as $titem ) : 
									$av_url  = ! empty( $titem['avatar']['url'] ) ? esc_url( $titem['avatar']['url'] ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg';
									$trating = ! empty( $titem['rating'] ) ? intval( $titem['rating'] ) : 5;
								?>
									<div class="dlm-hover-lift mipallab-hover-lift" style="background: <?php echo esc_attr( $card_bg ); ?>; padding: 28px; border-radius: 20px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 10px 30px rgba(0,0,0,0.06); display: flex; flex-direction: column; justify-content: space-between;">
										<div>
											<div style="color: <?php echo esc_attr( $star_color ); ?>; font-size: 16px; margin-bottom: 12px;">
												<?php echo esc_html( str_repeat( '★', max( 1, min( 5, $trating ) ) ) ); ?>
											</div>
											<p style="font-size: 1rem; color: rgba(26,28,28,0.85); font-style: italic; line-height: 1.7; margin-bottom: 20px;">
												"<?php echo esc_html( $titem['review_text'] ?? '' ); ?>"
											</p>
										</div>
										<div style="display: flex; align-items: center; gap: 14px;">
											<img src="<?php echo esc_url( $av_url ); ?>" alt="<?php echo esc_attr( $titem['reviewer_name'] ?? 'Reader' ); ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid <?php echo esc_attr( $primary ); ?>;" loading="lazy" />
											<div>
												<div style="font-weight: 800; color: <?php echo esc_attr( $text ); ?>; font-size: 0.95rem;"><?php echo esc_html( $titem['reviewer_name'] ?? 'Verified Reader' ); ?></div>
												<div style="color: rgba(26,28,28,0.6); font-size: 0.85rem; font-weight: 600;"><?php echo esc_html( $titem['reviewer_title'] ?? 'Member' ); ?></div>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $enable_google_tab ) : ?>
						<!-- Pane 3: Google Reviews (Manual, Shortcode / Plugin & API) -->
						<div id="<?php echo esc_attr( $uid ); ?>_google" class="dlm-rev-pane dlm-review-content-pane mipallab-rev-pane mipallab-review-content-pane" style="display: <?php echo ( 'google' === $default_tab && ( $show_switcher_nav || ( ! $enable_video_tab && ! $enable_text_tab ) ) ) ? 'block' : 'none'; ?>;">
							
							<?php if ( 'shortcode' === $google_mode && ! empty( $google_shortcode ) ) : ?>
								<div class="dlm-google-reviews-shortcode-wrapper" style="background: <?php echo esc_attr( $card_bg ); ?>; padding: 24px; border-radius: 20px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
									<?php echo do_shortcode( $google_shortcode ); ?>
								</div>
							<?php else : ?>
								<!-- Google Rating Hero Card -->
								<div style="background: <?php echo esc_attr( $card_bg ); ?>; padding: 40px 24px; border-radius: 24px; text-align: center; border: 1.5px solid rgba(133, 83, 0, 0.15); box-shadow: 0 12px 35px rgba(0,0,0,0.06); max-width: 750px; margin: 0 auto 36px auto;">
									<div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 6px 16px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 16px;">
										<svg width="20" height="20" viewBox="0 0 48 48" style="vertical-align: middle;">
											<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
											<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
											<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
											<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
										</svg>
										<span style="font-weight: 800; font-size: 14px; color: #1a1c1c;"><?php echo esc_html( $google_business_name ); ?></span>
									</div>
									<h3 style="font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: 900; color: <?php echo esc_attr( $primary ); ?>; margin: 0 0 10px 0; letter-spacing: -0.5px;">
										<?php echo esc_html( $google_score ); ?>
									</h3>
									<p style="font-size: 1.15rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 20px 0;"><?php echo esc_html( $google_subtext ); ?></p>
									<?php if ( ! empty( $google_place_url ) ) : ?>
										<a href="<?php echo esc_url( $google_place_url ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 8px; background: <?php echo esc_attr( $primary ); ?>; color: #ffffff; padding: 12px 26px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; text-decoration: none; box-shadow: 0 8px 20px rgba(133, 83, 0, 0.22);">
											<span>⭐</span> <?php echo esc_html( $google_btn_text ); ?> →
										</a>
									<?php endif; ?>
								</div>

								<?php if ( ! empty( $google_review_items ) ) : ?>
									<!-- Google Customer Review Cards -->
									<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
										<?php foreach ( $google_review_items as $gitem ) : 
											$g_author = ! empty( $gitem['author_name'] ) ? $gitem['author_name'] : 'Google Reviewer';
											$g_rating = ! empty( $gitem['rating'] ) ? intval( $gitem['rating'] ) : 5;
											$g_time   = ! empty( $gitem['time_ago'] ) ? $gitem['time_ago'] : 'Recent review';
											$g_text   = ! empty( $gitem['review_text'] ) ? $gitem['review_text'] : '';
											$g_avatar = ! empty( $gitem['author_avatar']['url'] ) ? esc_url( $gitem['author_avatar']['url'] ) : '';
										?>
											<div class="dlm-hover-lift mipallab-hover-lift" style="background: <?php echo esc_attr( $card_bg ); ?>; padding: 24px; border-radius: 18px; border: 1px solid rgba(133, 83, 0, 0.12); box-shadow: 0 8px 25px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
												<div>
													<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
														<div style="color: <?php echo esc_attr( $star_color ); ?>; font-size: 15px;">
															<?php echo esc_html( str_repeat( '★', max( 1, min( 5, $g_rating ) ) ) ); ?>
														</div>
														<span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #1e8e3e; background: #e6f4ea; padding: 3px 8px; border-radius: 20px;">
															✓ <?php esc_html_e( 'Google Verified', 'digital-library-membership' ); ?>
														</span>
													</div>
													<p style="font-size: 0.95rem; color: rgba(26,28,28,0.85); line-height: 1.65; margin: 0 0 16px 0;">
														"<?php echo esc_html( $g_text ); ?>"
													</p>
												</div>
												<div style="display: flex; align-items: center; gap: 12px; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 14px;">
													<?php if ( ! empty( $g_avatar ) ) : ?>
														<img src="<?php echo esc_url( $g_avatar ); ?>" alt="<?php echo esc_attr( $g_author ); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" loading="lazy" />
													<?php else : ?>
														<div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(133, 83, 0, 0.15); color: <?php echo esc_attr( $primary ); ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px;">
															<?php echo esc_html( strtoupper( substr( $g_author, 0, 1 ) ) ); ?>
														</div>
													<?php endif; ?>
													<div>
														<div style="font-weight: 800; color: <?php echo esc_attr( $text ); ?>; font-size: 0.95rem;"><?php echo esc_html( $g_author ); ?></div>
														<div style="color: rgba(26,28,28,0.5); font-size: 0.8rem; font-weight: 600;"><?php echo esc_html( $g_time ); ?></div>
													</div>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

							<?php endif; ?>

						</div>
					<?php endif; ?>

				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Shortcode: [dlm_review_switcher] / [mipallab_review_switcher]
	 */
	public function render_review_switcher_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'primary_color'      => '#855300',
				'text_color'         => '#1a1c1c',
				'show_switcher_nav'  => 'yes',
				'default_active_tab' => 'video',
				'enable_video_tab'   => 'yes',
				'enable_text_tab'    => 'yes',
				'enable_google_tab'  => 'yes',
			),
			$atts,
			'dlm_review_switcher'
		);

		$video_items = array(
			array(
				'name'      => 'Dr. Sarah Moon',
				'role'      => 'AI Specialist',
				'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
				'rating'    => 5,
			),
			array(
				'name'      => 'Julian Vance',
				'role'      => 'Tech Entrepreneur',
				'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
				'rating'    => 5,
			),
		);

		$text_items = array(
			array(
				'reviewer_name'  => 'Marcus Webb',
				'reviewer_title' => 'Verified Reader',
				'review_text'    => 'The AI Job Shift completely transformed how I view career progression. Essential read!',
				'avatar'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-1.jpg' ),
				'rating'         => 5,
			),
			array(
				'reviewer_name'  => 'Lina Eklund',
				'reviewer_title' => 'Architectural Designer',
				'review_text'    => 'Beautiful digital library layout. Highly recommend the membership!',
				'avatar'         => array( 'url' => 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-2.jpg' ),
				'rating'         => 5,
			),
		);

		ob_start();
		self::render_review_switcher_html(
			esc_html__( 'COMMUNITY TESTIMONIALS', 'digital-library-membership' ),
			esc_html__( 'What Readers Say About Our Library', 'digital-library-membership' ),
			$video_items,
			$text_items,
			'4.9 ★★★★★',
			esc_html__( 'Based on 1,280+ Verified Google Reviews', 'digital-library-membership' ),
			$atts['primary_color'],
			$atts['text_color'],
			$atts
		);
		return ob_get_clean();
	}

	/**
	 * Render HTML for Contact Section (Self-Contained Engine)
	 */
	public static function render_contact_html( $tag = 'GET IN TOUCH', $title = 'Have Questions? Contact Us', $desc = 'Reach out to our author team or customer support. We respond within 24 hours.', $email = 'support@bridgeway36.com', $phone = '+1 (800) 555-0199', $addr = 'New York & Global Headquarters', $form_title = 'Send Us a Message', $form_bg = '#ffffff', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		$primary = ! empty( $primary_color ) ? esc_attr( $primary_color ) : '#855300';
		$text    = ! empty( $text_color ) ? esc_attr( $text_color ) : '#1a1c1c';
		?>
		<div class="dlm-contact-section mipallab-contact-section" style="padding: 80px 24px; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div style="max-width: 1100px; margin: 0 auto;">
				
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; align-items: start;">
					
					<!-- Left Info Column -->
					<div class="gsap-fade-up">
						<?php if ( ! empty( $tag ) ) : ?>
							<div style="display: inline-block; color: <?php echo esc_attr( $primary ); ?>; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; margin-bottom: 12px; text-transform: uppercase;">
								<?php echo esc_html( $tag ); ?>
							</div>
						<?php endif; ?>

						<h2 style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 18px 0; line-height: 1.2;">
							<?php echo esc_html( $title ); ?>
						</h2>

						<p style="font-size: 1.05rem; color: rgba(26, 28, 28, 0.8); line-height: 1.7; margin-bottom: 36px;">
							<?php echo esc_html( $desc ); ?>
						</p>

						<div style="display: flex; flex-direction: column; gap: 20px;">
							<?php if ( ! empty( $email ) ) : ?>
								<div style="display: flex; align-items: center; gap: 16px;">
									<div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(133, 83, 0, 0.1); color: <?php echo esc_attr( $primary ); ?>; display: flex; align-items: center; justify-content: center; font-size: 20px;">
										✉️
									</div>
									<div>
										<div style="font-size: 0.85rem; font-weight: 700; color: rgba(26,28,28,0.6);"><?php esc_html_e( 'EMAIL SUPPORT', 'digital-library-membership' ); ?></div>
										<a href="mailto:<?php echo esc_attr( $email ); ?>" style="font-size: 1rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; text-decoration: none;"><?php echo esc_html( $email ); ?></a>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $phone ) ) : ?>
								<div style="display: flex; align-items: center; gap: 16px;">
									<div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(133, 83, 0, 0.1); color: <?php echo esc_attr( $primary ); ?>; display: flex; align-items: center; justify-content: center; font-size: 20px;">
										📞
									</div>
									<div>
										<div style="font-size: 0.85rem; font-weight: 700; color: rgba(26,28,28,0.6);"><?php esc_html_e( 'PHONE INQUIRIES', 'digital-library-membership' ); ?></div>
										<div style="font-size: 1rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>;"><?php echo esc_html( $phone ); ?></div>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $addr ) ) : ?>
								<div style="display: flex; align-items: center; gap: 16px;">
									<div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(133, 83, 0, 0.1); color: <?php echo esc_attr( $primary ); ?>; display: flex; align-items: center; justify-content: center; font-size: 20px;">
										📍
									</div>
									<div>
										<div style="font-size: 0.85rem; font-weight: 700; color: rgba(26,28,28,0.6);"><?php esc_html_e( 'LOCATION', 'digital-library-membership' ); ?></div>
										<div style="font-size: 1rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>;"><?php echo esc_html( $addr ); ?></div>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Right Interactive Form Column -->
					<div class="gsap-fade-up">
						<form class="dlm-ajax-contact-form dlm-contact-form-el mipallab-ajax-contact-form mipallab-contact-form-el" method="post" style="background: <?php echo esc_attr( $form_bg ); ?>; padding: 36px 30px; border-radius: 24px; border: 1.5px solid rgba(133, 83, 0, 0.15); box-shadow: 0 15px 35px rgba(0,0,0,0.06);">
							<input type="hidden" name="action" value="dlm_contact_form_submit" />
							<?php wp_nonce_field( 'dlm_contact_nonce', 'nonce' ); ?>
							
							<h3 style="font-size: 1.4rem; font-weight: 800; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 24px 0;">
								<?php echo esc_html( $form_title ); ?>
							</h3>

							<div class="dlm-form-msg-box dlm-form-message mipallab-form-msg-box mipallab-form-message" style="display: none; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 0.95rem; line-height: 1.5;"></div>

							<div style="margin-bottom: 18px;">
								<label style="display: block; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; margin-bottom: 6px;"><?php esc_html_e( 'Your Name', 'digital-library-membership' ); ?> *</label>
								<input type="text" name="name" required placeholder="<?php esc_attr_e( 'e.g. John Doe', 'digital-library-membership' ); ?>" style="width: 100%; padding: 12px 16px; border-radius: 10px; border: 1.5px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; outline-color: <?php echo esc_attr( $primary ); ?>;" />
							</div>

							<div style="margin-bottom: 18px;">
								<label style="display: block; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; margin-bottom: 6px;"><?php esc_html_e( 'Email Address', 'digital-library-membership' ); ?> *</label>
								<input type="email" name="email" required placeholder="<?php esc_attr_e( 'e.g. john@example.com', 'digital-library-membership' ); ?>" style="width: 100%; padding: 12px 16px; border-radius: 10px; border: 1.5px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; outline-color: <?php echo esc_attr( $primary ); ?>;" />
							</div>

							<div style="margin-bottom: 18px;">
								<label style="display: block; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; margin-bottom: 6px;"><?php esc_html_e( 'Phone Number (Optional)', 'digital-library-membership' ); ?></label>
								<input type="tel" name="phone" placeholder="<?php esc_attr_e( 'e.g. +1 (555) 000-1234', 'digital-library-membership' ); ?>" style="width: 100%; padding: 12px 16px; border-radius: 10px; border: 1.5px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; outline-color: <?php echo esc_attr( $primary ); ?>;" />
							</div>

							<div style="margin-bottom: 18px;">
								<label style="display: block; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; margin-bottom: 6px;"><?php esc_html_e( 'Subject', 'digital-library-membership' ); ?></label>
								<input type="text" name="subject" placeholder="<?php esc_attr_e( 'e.g. Membership Inquiry / Book Question', 'digital-library-membership' ); ?>" style="width: 100%; padding: 12px 16px; border-radius: 10px; border: 1.5px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; outline-color: <?php echo esc_attr( $primary ); ?>;" />
							</div>

							<div style="margin-bottom: 24px;">
								<label style="display: block; font-size: 0.9rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>; margin-bottom: 6px;"><?php esc_html_e( 'Message', 'digital-library-membership' ); ?> *</label>
								<textarea name="message" rows="4" required placeholder="<?php esc_attr_e( 'How can we help you today?', 'digital-library-membership' ); ?>" style="width: 100%; padding: 12px 16px; border-radius: 10px; border: 1.5px solid rgba(133, 83, 0, 0.2); font-family: inherit; font-size: 1rem; box-sizing: border-box; outline-color: <?php echo esc_attr( $primary ); ?>; resize: vertical;"></textarea>
							</div>

							<button type="submit" style="width: 100%; background: <?php echo esc_attr( $primary ); ?>; color: #ffffff; padding: 14px; border: none; border-radius: 12px; font-weight: 800; font-size: 1.05rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 8px 20px rgba(133, 83, 0, 0.25); transition: all 0.25s ease;">
								<span>✉️</span> <?php esc_html_e( 'Send Message', 'digital-library-membership' ); ?>
							</button>

						</form>
					</div>

				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Shortcode: [dlm_contact_form] / [mipallab_contact_form]
	 */
	public function render_contact_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'primary_color' => '#855300',
				'title'         => esc_html__( 'Send Us a Message', 'digital-library-membership' ),
			),
			$atts,
			'dlm_contact_form'
		);

		ob_start();
		self::render_contact_html(
			esc_html__( 'GET IN TOUCH', 'digital-library-membership' ),
			esc_html__( 'Have Questions? Contact Us', 'digital-library-membership' ),
			esc_html__( 'Reach out to our author team or customer support. We respond within 24 hours.', 'digital-library-membership' ),
			get_option( 'admin_email', 'support@bridgeway36.com' ),
			'+1 (800) 555-0199',
			'New York & Global Headquarters',
			$atts['title'],
			'#ffffff',
			$atts['primary_color'],
			'#1a1c1c'
		);
		return ob_get_clean();
	}

	/**
	 * Shortcode: [dlm_hero_slider] / [mipallab_hero_slider]
	 */
	public function render_hero_slider_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'         => 5,
				'speed'         => 800,
				'autoplay'      => 'true',
				'delay'         => 5000,
				'loop'          => 'true',
				'bg_color'      => 'rgba(133, 83, 0, 0.08)',
				'primary_color' => '#855300',
				'title_color'   => '#1a1c1c',
			),
			$atts,
			'dlm_hero_slider'
		);

		$books        = DLM_Books_Helper::get_books( intval( $atts['limit'] ) );
		$slides       = array();
		$checkout_url = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'checkout' ) : '#membership';

		foreach ( $books as $b ) {
			/* translators: %s: author name */
			$subtitle = ! empty( $b['author'] ) ? sprintf( esc_html__( 'By %s', 'digital-library-membership' ), $b['author'] ) : esc_html__( 'Featured Edition', 'digital-library-membership' );
			$slides[] = array(
				'title'              => $b['title'],
				'subtitle'           => $subtitle,
				'badge_text'         => esc_html__( 'FEATURED PUBLICATION', 'digital-library-membership' ),
				'description'        => ! empty( $b['description'] ) ? wp_trim_words( wp_strip_all_tags( $b['description'] ), 28 ) : '',
				'book_cover'         => array( 'url' => $b['cover_image_url'] ),
				'rating'             => ! empty( $b['rating'] ) ? $b['rating'] . ' / 5.0 (Verified)' : '5.0 / 5.0 (Verified)',
				'btn_read_text'      => esc_html__( 'Read Online Now', 'digital-library-membership' ),
				'btn_read_link'      => array( 'url' => $b['read_url'] ),
				'btn_secondary_text' => esc_html__( 'View Membership Plans', 'digital-library-membership' ),
				'btn_secondary_link' => array( 'url' => $checkout_url ),
			);
		}

		ob_start();
		?>
		<div class="dlm-hero-section mipallab-hero-section" style="background-color: <?php echo esc_attr( $atts['bg_color'] ); ?>; padding: 90px 24px; position: relative; overflow: hidden; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div style="max-width: 1200px; margin: 0 auto; position: relative;">
				<div class="swiper dlm-swiper-container mipallab-swiper-container" data-speed="<?php echo esc_attr( $atts['speed'] ); ?>" data-autoplay="<?php echo esc_attr( $atts['autoplay'] ); ?>" data-delay="<?php echo esc_attr( $atts['delay'] ); ?>" data-loop="<?php echo esc_attr( $atts['loop'] ); ?>" data-slides="1" data-slides-tablet="1" data-slides-mobile="1">
					<div class="swiper-wrapper">
						<?php foreach ( $slides as $slide ) : ?>
							<div class="swiper-slide">
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 48px; align-items: center;">
									<div class="gsap-fade-up">
										<div class="dlm-hero-badge mipallab-hero-badge" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(133, 83, 0, 0.12); color: <?php echo esc_attr( $atts['primary_color'] ); ?>; padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 800; letter-spacing: 1px; margin-bottom: 20px;">
											<span>⭐</span> <?php echo esc_html( $slide['badge_text'] ); ?>
										</div>
										<h1 class="dlm-hero-title mipallab-hero-title" style="font-size: clamp(2.2rem, 5vw, 3.8rem); font-weight: 800; color: <?php echo esc_attr( $atts['title_color'] ); ?>; line-height: 1.15; margin: 0 0 16px 0; letter-spacing: -0.5px;">
											<?php echo esc_html( $slide['title'] ); ?>
										</h1>
										<h3 style="font-size: 1.2rem; font-weight: 700; color: <?php echo esc_attr( $atts['primary_color'] ); ?>; margin: 0 0 20px 0;">
											<?php echo esc_html( $slide['subtitle'] ); ?>
										</h3>
										<p style="font-size: 1.08rem; color: rgba(26, 28, 28, 0.82); line-height: 1.7; margin-bottom: 26px; max-width: 540px;">
											<?php echo esc_html( $slide['description'] ); ?>
										</p>
										<div class="dlm-hero-rating mipallab-hero-rating" style="display: flex; align-items: center; gap: 10px; margin-bottom: 30px; font-weight: 700; color: <?php echo esc_attr( $atts['title_color'] ); ?>;">
											<div style="color: #f59e0b; font-size: 18px;">★★★★★</div>
											<span><?php echo esc_html( $slide['rating'] ); ?></span>
										</div>
										<div class="dlm-hero-cta mipallab-hero-cta" style="display: flex; flex-wrap: wrap; gap: 16px;">
											<a href="<?php echo esc_url( $slide['btn_read_link']['url'] ); ?>" style="background: <?php echo esc_attr( $atts['primary_color'] ); ?>; color: #ffffff; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 1.05rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(133, 83, 0, 0.25);">
												📖 <?php echo esc_html( $slide['btn_read_text'] ); ?>
											</a>
											<a href="<?php echo esc_url( $slide['btn_secondary_link']['url'] ); ?>" style="background: transparent; color: <?php echo esc_attr( $atts['primary_color'] ); ?>; border: 2px solid <?php echo esc_attr( $atts['primary_color'] ); ?>; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 1.05rem; text-decoration: none; display: inline-flex; align-items: center;">
												<?php echo esc_html( $slide['btn_secondary_text'] ); ?> →
											</a>
										</div>
									</div>
									<div style="position: relative; text-align: center;">
										<div class="gsap-float" style="position: relative; display: inline-block;">
											<div style="position: absolute; inset: -15px; background: radial-gradient(circle, rgba(133, 83, 0, 0.22), transparent 70%); border-radius: 30px; filter: blur(20px); z-index: 1;"></div>
											<img src="<?php echo esc_url( $slide['book_cover']['url'] ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>" style="position: relative; z-index: 2; max-width: 100%; max-height: 480px; width: auto; object-fit: contain; border-radius: 18px; box-shadow: 0 20px 45px rgba(0,0,0,0.2); transform: perspective(1000px) rotateY(-4deg) rotateX(2deg);" loading="lazy" />
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-prev mipallab-swiper-nav-btn mipallab-swiper-nav-prev" aria-label="<?php esc_attr_e( 'Previous Slide', 'digital-library-membership' ); ?>">
						<svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<button type="button" class="dlm-swiper-nav-btn dlm-swiper-nav-next mipallab-swiper-nav-btn mipallab-swiper-nav-next" aria-label="<?php esc_attr_e( 'Next Slide', 'digital-library-membership' ); ?>">
						<svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
					<div class="swiper-pagination"></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render HTML for About Author (Self-Contained Engine)
	 */
	public static function render_about_author_html( $tag = 'MEET THE AUTHOR', $title = 'Avery Noble & Bridgeway Team', $bio = '', $photo_url = '', $stats = array(), $socials = array(), $bg_color = '#ffffff', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		$primary = ! empty( $primary_color ) ? esc_attr( $primary_color ) : '#855300';
		$text    = ! empty( $text_color ) ? esc_attr( $text_color ) : '#1a1c1c';
		$bg      = ! empty( $bg_color ) ? esc_attr( $bg_color ) : '#ffffff';
		$photo   = ! empty( $photo_url ) ? esc_url( $photo_url ) : 'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg';
		?>
		<div class="dlm-author-section mipallab-author-section" style="background-color: <?php echo esc_attr( $bg ); ?>; padding: 80px 24px; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div style="max-width: 1100px; margin: 0 auto;">
				
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 48px; align-items: center;">
					
					<!-- Author Image & Experience Badge -->
					<div class="gsap-fade-up" style="position: relative; text-align: center;">
						<div style="position: relative; display: inline-block;">
							<div style="position: absolute; inset: -15px; background: radial-gradient(circle, rgba(133, 83, 0, 0.15), transparent 70%); border-radius: 30px; filter: blur(20px); z-index: 1;"></div>
							<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="position: relative; z-index: 2; width: 100%; max-width: 380px; height: 440px; object-fit: cover; border-radius: 24px; box-shadow: 0 20px 45px rgba(0,0,0,0.12);" loading="lazy" />
							
							<div style="position: absolute; bottom: 20px; right: -10px; z-index: 3; background: #ffffff; padding: 14px 22px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); border: 1px solid rgba(133, 83, 0, 0.15); text-align: left;">
								<div style="font-size: 1.4rem; font-weight: 900; color: <?php echo esc_attr( $primary ); ?>;">15+</div>
								<div style="font-size: 0.85rem; font-weight: 700; color: <?php echo esc_attr( $text ); ?>;"><?php esc_html_e( 'Years Experience', 'digital-library-membership' ); ?></div>
							</div>
						</div>
					</div>

					<!-- Author Bio & Achievements -->
					<div class="gsap-fade-up">
						<?php if ( ! empty( $tag ) ) : ?>
							<div style="display: inline-block; color: <?php echo esc_attr( $primary ); ?>; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; margin-bottom: 12px;">
								<?php echo esc_html( $tag ); ?>
							</div>
						<?php endif; ?>

						<h2 style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 18px 0; line-height: 1.2;">
							<?php echo esc_html( $title ); ?>
						</h2>

						<p style="font-size: 1.05rem; color: rgba(26, 28, 28, 0.8); line-height: 1.75; margin-bottom: 30px;">
							<?php echo esc_html( $bio ); ?>
						</p>

						<!-- Highlights Grid -->
						<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 30px;">
							<?php foreach ( $stats as $stat ) : ?>
								<div style="background: rgba(133, 83, 0, 0.05); padding: 16px; border-radius: 14px; border: 1px solid rgba(133, 83, 0, 0.1);">
									<div style="font-size: 1.3rem; font-weight: 800; color: <?php echo esc_attr( $primary ); ?>;"><?php echo esc_html( $stat['stat_number'] ); ?></div>
									<div style="font-size: 0.85rem; font-weight: 600; color: <?php echo esc_attr( $text ); ?>;"><?php echo esc_html( $stat['stat_label'] ); ?></div>
								</div>
							<?php endforeach; ?>
						</div>

						<!-- Social / Author Profiles -->
						<?php if ( ! empty( $socials ) ) : ?>
							<div style="display: flex; gap: 12px; flex-wrap: wrap;">
								<?php foreach ( $socials as $soc ) : 
									$slink = ! empty( $soc['link']['url'] ) ? esc_url( $soc['link']['url'] ) : '#';
								?>
									<a href="<?php echo esc_url( $slink ); ?>" style="background: rgba(133, 83, 0, 0.1); color: <?php echo esc_attr( $primary ); ?>; padding: 10px 18px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
										<span><?php echo esc_html( $soc['icon_text'] ); ?></span> <?php echo esc_html( $soc['platform'] ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Shortcode: [dlm_about_author] / [mipallab_about_author]
	 */
	public function render_about_author_shortcode( $atts ) {
		$stats = array(
			array( 'stat_number' => '25+', 'stat_label' => 'Published Titles' ),
			array( 'stat_number' => '100K+', 'stat_label' => 'Global Readers' ),
			array( 'stat_number' => '15+', 'stat_label' => 'Years Experience' ),
			array( 'stat_number' => '4.9★', 'stat_label' => 'Average Rating' ),
		);
		$social_links = array(
			array( 'platform' => 'Goodreads', 'icon_text' => '📚', 'link' => array( 'url' => '#' ) ),
			array( 'platform' => 'Twitter', 'icon_text' => '🐦', 'link' => array( 'url' => '#' ) ),
			array( 'platform' => 'LinkedIn', 'icon_text' => '💼', 'link' => array( 'url' => '#' ) ),
		);

		ob_start();
		self::render_about_author_html(
			esc_html__( 'MEET THE AUTHOR', 'digital-library-membership' ),
			esc_html__( 'Avery Noble & Bridgeway Team', 'digital-library-membership' ),
			esc_html__( 'With over a decade of dedicated experience in literature, artificial intelligence research, and quiet architectural philosophy, our authors craft insightful publications that empower readers to thrive.', 'digital-library-membership' ),
			'https://dev-bridgeway36.pantheonsite.io/wp-content/uploads/2026/07/unnamed-11.jpg',
			$stats,
			$social_links,
			'#ffffff',
			'#855300',
			'#1a1c1c'
		);
		return ob_get_clean();
	}

	/**
	 * Render HTML for Membership Plans (Self-Contained Engine)
	 */
	public static function render_plans_html( $section_tag = 'MEMBERSHIP ACCESS', $section_title = 'Choose Your Membership Plan', $section_desc = 'Unlock unlimited digital reading, downloadable PDFs, and exclusive research publications.', $plans = array(), $bg_color = 'rgba(133, 83, 0, 0.08)', $primary_color = '#855300', $text_color = '#1a1c1c' ) {
		$primary = ! empty( $primary_color ) ? esc_attr( $primary_color ) : '#855300';
		$text    = ! empty( $text_color ) ? esc_attr( $text_color ) : '#1a1c1c';
		?>
		<div class="dlm-membership-section mipallab-membership-section" style="background-color: <?php echo esc_attr( $bg_color ); ?>; padding: 90px 24px; font-family: 'Plus Jakarta Sans', sans-serif;">
			<div style="max-width: 1200px; margin: 0 auto;">
				
				<div class="gsap-fade-up" style="text-align: center; max-width: 680px; margin: 0 auto 50px auto;">
					<?php if ( ! empty( $section_tag ) ) : ?>
						<div style="display: inline-block; background: rgba(133, 83, 0, 0.12); color: <?php echo esc_attr( $primary ); ?>; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; padding: 6px 18px; border-radius: 50px; margin-bottom: 12px;">
							<?php echo esc_html( $section_tag ); ?>
						</div>
					<?php endif; ?>

					<h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 16px 0; line-height: 1.2;">
						<?php echo esc_html( $section_title ); ?>
					</h2>

					<?php if ( ! empty( $section_desc ) ) : ?>
						<p style="font-size: 1.05rem; color: rgba(26, 28, 28, 0.8); line-height: 1.6; margin: 0;">
							<?php echo esc_html( $section_desc ); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="dlm-plans-grid mipallab-plans-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; align-items: stretch;">
					<?php foreach ( $plans as $plan ) : 
						$is_feat  = ( isset( $plan['is_featured'] ) && 'yes' === $plan['is_featured'] );
						$btn_url  = ! empty( $plan['btn_link']['url'] ) ? esc_url( $plan['btn_link']['url'] ) : ( is_string( $plan['btn_link'] ?? '' ) ? esc_url( $plan['btn_link'] ) : '#checkout' );
						$btn_text = ! empty( $plan['btn_text'] ) ? $plan['btn_text'] : esc_html__( 'Choose Plan', 'digital-library-membership' );
					?>
						<div class="dlm-plan-card mipallab-plan-card dlm-hover-lift mipallab-hover-lift <?php echo $is_feat ? 'featured' : ''; ?>" style="background: <?php echo $is_feat ? '#ffffff' : '#ffffff'; ?>; border-radius: 24px; padding: 40px 32px; border: <?php echo $is_feat ? '2.5px solid ' . esc_attr( $primary ) : '1px solid rgba(133, 83, 0, 0.15)'; ?>; box-shadow: <?php echo $is_feat ? '0 20px 45px rgba(133, 83, 0, 0.18)' : '0 10px 30px rgba(0,0,0,0.06)'; ?>; display: flex; flex-direction: column; position: relative;">
							
							<?php if ( $is_feat && ! empty( $plan['badge_text'] ) ) : ?>
								<div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: <?php echo esc_attr( $primary ); ?>; color: #ffffff; font-size: 12px; font-weight: 800; letter-spacing: 1px; padding: 4px 16px; border-radius: 50px; box-shadow: 0 4px 12px rgba(133, 83, 0, 0.3);">
									<?php echo esc_html( $plan['badge_text'] ); ?>
								</div>
							<?php endif; ?>

							<h3 style="font-size: 1.4rem; font-weight: 800; color: <?php echo esc_attr( $text ); ?>; margin: 0 0 12px 0;">
								<?php echo esc_html( $plan['plan_name'] ); ?>
							</h3>

							<div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 24px;">
								<span style="font-size: 2.8rem; font-weight: 900; color: <?php echo $is_feat ? esc_attr( $primary ) : esc_attr( $text ); ?>; line-height: 1;">
									<?php echo esc_html( $plan['price'] ); ?>
								</span>
								<span style="font-size: 0.95rem; font-weight: 600; color: rgba(26, 28, 28, 0.6);">
									/ <?php echo esc_html( $plan['period'] ); ?>
								</span>
							</div>

							<div style="height: 1px; background: rgba(133, 83, 0, 0.12); margin-bottom: 24px;"></div>

							<ul style="list-style: none; padding: 0; margin: 0 0 36px 0; flex-grow: 1; display: flex; flex-direction: column; gap: 14px;">
								<?php 
								$features = is_array( $plan['features_list'] ?? '' ) ? $plan['features_list'] : explode( "\n", $plan['features_list'] ?? '' );
								foreach ( $features as $feat ) : 
									$feat_text = is_array( $feat ) ? ( $feat['text'] ?? '' ) : trim( $feat );
									if ( empty( $feat_text ) ) continue;
								?>
									<li style="display: flex; align-items: center; gap: 10px; font-size: 0.95rem; font-weight: 600; color: rgba(26, 28, 28, 0.85);">
										<span style="color: <?php echo esc_attr( $primary ); ?>; font-weight: 900;">✓</span>
										<span><?php echo esc_html( $feat_text ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>

							<a href="<?php echo esc_url( $btn_url ); ?>" style="background: <?php echo $is_feat ? esc_attr( $primary ) : 'rgba(133, 83, 0, 0.1)'; ?>; color: <?php echo $is_feat ? '#ffffff' : esc_attr( $primary ); ?>; padding: 14px 24px; border-radius: 12px; font-weight: 800; font-size: 1rem; text-decoration: none; text-align: center; display: block; box-shadow: <?php echo $is_feat ? '0 8px 20px rgba(133, 83, 0, 0.25)' : 'none'; ?>;">
								<?php echo esc_html( $btn_text ); ?> →
							</a>

						</div>
					<?php endforeach; ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Shortcode: [dlm_membership] / [mipallab_membership]
	 */
	public function render_membership_shortcode( $atts ) {
		$raw_packages    = function_exists( 'dlm_get_packages' ) ? dlm_get_packages() : array();
		$plans           = array();
		$currency_symbol = get_option( 'dlm_currency_symbol', '$' );
		$checkout_url    = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'checkout' ) : '#checkout';

		if ( ! empty( $raw_packages ) ) {
			foreach ( $raw_packages as $pkg ) {
				if ( isset( $pkg['status'] ) && 'inactive' === $pkg['status'] ) {
					continue;
				}
				/* translators: %s: billing interval name */
				$interval_label = ( ! empty( $pkg['interval'] ) && 'lifetime' === $pkg['interval'] ) ? esc_html__( 'one-time permanent', 'digital-library-membership' ) : sprintf( esc_html__( 'per %s', 'digital-library-membership' ), $pkg['interval'] ?? 'month' );
				$is_featured    = ( ( $pkg['interval'] ?? '' ) === 'yearly' || ! empty( $pkg['badge'] ) ) ? 'yes' : 'no';

				$plans[] = array(
					'plan_name'     => ! empty( $pkg['name'] ) ? $pkg['name'] : ucfirst( $pkg['interval'] ?? 'Standard' ),
					'price'         => $currency_symbol . number_format( floatval( $pkg['price'] ?? 0 ), 2 ),
					'period'        => $interval_label,
					'is_featured'   => $is_featured,
					'badge_text'    => ! empty( $pkg['badge'] ) ? $pkg['badge'] : esc_html__( 'BEST VALUE', 'digital-library-membership' ),
					'features_list' => is_array( $pkg['features'] ?? '' ) ? $pkg['features'] : explode( "\n", $pkg['features'] ?? '' ),
					'btn_text'      => esc_html__( 'Choose Plan', 'digital-library-membership' ),
					'btn_link'      => add_query_arg( 'plan', $pkg['interval'] ?? 'monthly', $checkout_url ) . '#checkout',
				);
			}
		}

		if ( empty( $plans ) && empty( $raw_packages ) ) {
			$plans = array(
				array(
					'plan_name'     => 'Monthly Reader Pass',
					'price'         => '$9.99',
					'period'        => 'per month',
					'is_featured'   => 'no',
					'features_list' => array( 'Access to all published digital books', 'Read on any device', 'Bookmarks & Progress tracking', 'Cancel anytime' ),
					'btn_text'      => 'Get Monthly Pass',
					'btn_link'      => '#checkout',
				),
				array(
					'plan_name'     => 'VIP Annual Membership',
					'price'         => '$25.00',
					'period'        => 'per year (Save 50%)',
					'is_featured'   => 'yes',
					'badge_text'    => 'BEST VALUE',
					'features_list' => array( 'Unlimited reading & downloadable PDFs', 'Early access to new book releases', 'Exclusive Author Q&A events', 'Premium priority support', 'Family sharing access' ),
					'btn_text'      => 'Join Annual VIP',
					'btn_link'      => '#checkout',
				),
			);
		}

		ob_start();
		self::render_plans_html(
			esc_html__( 'MEMBERSHIP ACCESS', 'digital-library-membership' ),
			esc_html__( 'Choose Your Membership Plan', 'digital-library-membership' ),
			esc_html__( 'Unlock unlimited digital reading, downloadable PDFs, and exclusive research publications.', 'digital-library-membership' ),
			$plans,
			'rgba(133, 83, 0, 0.08)',
			'#855300',
			'#1a1c1c'
		);
		return ob_get_clean();
	}

	/**
	 * AJAX Endpoint: Contact Form Processing
	 */
	public function handle_contact_submit() {
		// Nonce check: accept dlm_contact_nonce or mipallab_contact_nonce or _wpnonce
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '' );
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'dlm_contact_nonce' ) && ! wp_verify_nonce( $nonce, 'mipallab_contact_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security token invalid or expired. Please refresh and try again.', 'digital-library-membership' ) ) );
		}

		$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Please provide your name.', 'digital-library-membership' ) ) );
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Please provide a valid email address.', 'digital-library-membership' ) ) );
		}
		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Please enter your message.', 'digital-library-membership' ) ) );
		}

		$remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$enquiry_item = array(
			'name'    => $name,
			'email'   => $email,
			'phone'   => $phone,
			'subject' => $subject ? $subject : 'New Inquiry from Website',
			'message' => $message,
			'date'    => current_time( 'mysql' ),
			'ip'      => $remote_ip,
		);

		// Store in DLM inquiries option & sync to Mipallab option
		$enquiries = get_option( 'dlm_contact_enquiries', array() );
		if ( ! is_array( $enquiries ) ) {
			$enquiries = array();
		}
		array_unshift( $enquiries, $enquiry_item );
		$enquiries = array_slice( $enquiries, 0, 200 );
		update_option( 'dlm_contact_enquiries', $enquiries );
		update_option( 'mipallab_contact_enquiries', $enquiries );

		// Dispatch email notification to admin with all provided fields
		$admin_email = get_option( 'admin_email' );
		if ( ! empty( $admin_email ) ) {
			$site_name  = get_bloginfo( 'name' );
			$email_subj = sprintf( '[%s Contact] %s', $site_name, ( $subject ? $subject : 'New Message from ' . $name ) );
			
			$email_body  = "You have received a new contact inquiry from your Digital Library website:\n\n";
			$email_body .= "========================================================\n";
			$email_body .= "Full Name:      " . $name . "\n";
			$email_body .= "Email Address:  " . $email . "\n";
			if ( ! empty( $phone ) ) {
				$email_body .= "Phone Number:   " . $phone . "\n";
			}
			$email_body .= "Subject:        " . ( $subject ? $subject : 'General Inquiry' ) . "\n";
			$email_body .= "Submitted At:   " . current_time( 'Y-m-d H:i:s' ) . "\n";
			$email_body .= "IP Address:     " . $remote_ip . "\n";
			$email_body .= "========================================================\n\n";
			$email_body .= "Message:\n";
			$email_body .= $message . "\n\n";
			$email_body .= "--------------------------------------------------------\n";
			$email_body .= "You can reply directly to this email to respond to " . $name . " (" . $email . ").\n";

			$headers = array(
				'Content-Type: text/plain; charset=UTF-8',
				'Reply-To: ' . $name . ' <' . $email . '>',
			);

			wp_mail( $admin_email, $email_subj, $email_body, $headers );
		}

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Message sent successfully! We will contact you soon.', 'digital-library-membership' ),
			)
		);
	}
}

// Backward Compatibility Class Alias for Extension
if ( ! class_exists( 'Mipallab_Home_Widgets_Extension' ) ) {
	class_alias( 'DLM_Home_Widgets', 'Mipallab_Home_Widgets_Extension' );
}
