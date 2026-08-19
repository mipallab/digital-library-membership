<?php
/**
 * Frontend Views & Controllers
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Public {

	private $db;
	private $checkout;

	public function __construct( $db, $checkout ) {
		$this->db       = $db;
		$this->checkout = $checkout;
	}

	/**
	 * Shortcode dlm_library - Renders grid layout of all books
	 * Accessible to everyone (guests, non-subscribers, subscribers)
	 */
	public function render_library( $atts ) {
		$is_logged_in = is_user_logged_in();
		$user_id      = $is_logged_in ? get_current_user_id() : 0;
		$is_active    = $is_logged_in ? $this->db->has_active_membership( $user_id ) : false;
		$pricing_url  = dlm_get_page_url( 'pricing' );

		// Enqueue styles & scripts for shortcode rendering
		wp_enqueue_style( 'dlm-public-css', DLM_URL . 'public/css/dlm-public.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-font-awesome', DLM_URL . 'admin/css/font-awesome.min.css', array(), '6.4.0' );
		wp_enqueue_script( 'dlm-tailwind', DLM_URL . 'admin/js/tailwindcss.js', array(), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-public-js', DLM_URL . 'public/js/dlm-public.js', array( 'jquery' ), DLM_VERSION, true );

		$raw_books = $this->db->get_books( 'publish', true );
		$books_data = array();
		$categories_set = array();

		if ( ! empty( $raw_books ) ) {
			foreach ( $raw_books as $b ) {
				$progress         = ( $is_logged_in && $user_id ) ? $this->db->get_reading_progress( $user_id, $b->id ) : null;
				$progress_percent = $progress ? intval( $progress->progress_percent ) : 0;
				$cats = wp_get_post_terms( $b->id, 'dlm_book_category' );
				$category = __( 'General', 'digital-library-membership' );
				if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
					foreach ( $cats as $t ) {
						if ( $t->parent == 0 ) {
							$category = $t->name;
							break;
						} else {
							$parent_term = get_term( $t->parent, 'dlm_book_category' );
							if ( $parent_term && ! is_wp_error( $parent_term ) ) {
								$category = $parent_term->name;
								break;
							}
						}
					}
				}

				if ( ! in_array( $category, $categories_set, true ) ) {
					$categories_set[] = $category;
				}

				$access_type = ! empty( $b->access_type ) ? $b->access_type : 'subscription_only';
				$price       = isset( $b->price ) ? floatval( $b->price ) : 0.00;
				$currency    = get_option( 'dlm_currency', 'USD' );
				$user_access = dlm_user_can_access_book( $user_id, $b->id );
				$has_bought  = ( $is_logged_in && $user_id ) ? $this->db->has_purchased_book( $user_id, $b->id ) : false;
				$is_future   = ( ! empty( $b->publish_date ) && strtotime( $b->publish_date ) > current_time( 'timestamp' ) ) || ( isset( $b->status ) && $b->status === 'future' );
				$publish_iso = '';
				if ( ! empty( $b->publish_date ) ) {
					$publish_iso = wp_date( 'c', strtotime( $b->publish_date ) );
					if ( empty( $publish_iso ) ) {
						$publish_iso = date( 'c', strtotime( $b->publish_date ) );
					}
					if ( empty( $publish_iso ) ) {
						$publish_iso = str_replace( ' ', 'T', trim( $b->publish_date ) );
					}
				}
				$publish_fmt = ( ! empty( $b->publish_date ) ) ? date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $b->publish_date ) ) : '';

				$books_data[] = array(
					'id'                => $b->id,
					'title'             => $b->title,
					'author'            => $b->author,
					'description'       => ! empty( $b->description ) ? wp_strip_all_tags( $b->description ) : '',
					'category'          => $category,
					'progress'          => $progress_percent,
					'cover'             => ! empty( $b->cover_image_url ) ? $b->cover_image_url : '',
					'date'              => ! empty( $b->created_at ) ? date_i18n( get_option( 'date_format' ), strtotime( $b->created_at ) ) : '',
					'created_at_iso'    => ! empty( $b->created_at ) ? wp_date( 'c', strtotime( $b->created_at ) ) : '',
					'read_url'          => home_url( '/read/' . $b->id . '/' ),
					'access_type'       => $access_type,
					'price'             => $price,
					'price_formatted'   => number_format( $price, 2 ) . ' ' . $currency,
					'user_access'       => $user_access, // 'locked' | 'read_only' | 'read_download'
					'has_purchased'     => $has_bought,
					'is_future'         => $is_future,
					'publish_date'      => ! empty( $b->publish_date ) ? $b->publish_date : '',
					'publish_iso'       => $publish_iso,
					'publish_formatted' => $publish_fmt,
				);
			}
		}

		ob_start();
		?>
		<script id="tailwind-config">
			if (typeof tailwind !== 'undefined') {
				tailwind.config = {
					darkMode: "class",
					theme: {
						extend: {
							"colors": {
								"tertiary-container": "#00658b",
								"error": "#ba1a1a",
								"secondary-fixed-dim": "#c8c6c8",
								"on-background": "#1a1c1c",
								"on-secondary": "#ffffff",
								"on-tertiary": "#ffffff",
								"on-error-container": "#93000a",
								"surface-amber": "#f59e0b",
								"on-tertiary-fixed-variant": "#004c6a",
								"on-surface": "#1a1c1c",
								"primary-fixed-dim": "#fdb965",
								"inverse-on-surface": "#f0f1f1",
								"error-container": "#ffdad6",
								"secondary": "#5f5e60",
								"tertiary": "#004c6a",
								"on-secondary-fixed-variant": "#474648",
								"tertiary-fixed-dim": "#88cffa",
								"surface-container-high": "#e8e8e8",
								"surface-tint": "#855300",
								"primary-fixed": "#ffddb8",
								"surface-container-highest": "#e2e2e2",
								"background": "#f9f9f9",
								"surface-dim": "#dadada",
								"surface-variant": "#e2e2e2",
								"on-surface-amber": "#613b00",
								"surface-container-lowest": "#ffffff",
								"primary": "#653e00",
								"tertiary-fixed": "#c5e7ff",
								"inverse-surface": "#2f3131",
								"secondary-fixed": "#e5e2e4",
								"on-surface-variant": "#514538",
								"surface-container": "#eeeeee",
								"secondary-container": "#e2dfe1",
								"on-secondary-container": "#636264",
								"primary-container": "#855300",
								"on-primary-container": "#ffd09a",
								"surface": "#f9f9f9",
								"inverse-primary": "#fdb965",
								"outline-muted": "rgba(134, 116, 97, 0.3)",
								"on-tertiary-fixed": "#001e2d",
								"on-tertiary-container": "#addeff",
								"surface-bright": "#f9f9f9",
								"outline-variant": "#d5c4b2",
								"on-primary-fixed-variant": "#653e00",
								"on-primary-fixed": "#2a1700",
								"on-error": "#ffffff",
								"on-primary": "#ffffff",
								"outline": "#837566",
								"surface-container-low": "#f3f3f3",
								"surface-background": "#fafafa",
								"on-secondary-fixed": "#1b1b1d"
							},
							"borderRadius": {
								"DEFAULT": "0.25rem",
								"lg": "0.5rem",
								"xl": "0.75rem",
								"full": "9999px"
							},
							"spacing": {
								"container-max": "1440px",
								"sidebar-width": "280px",
								"margin-mobile": "20px",
								"margin-desktop": "48px",
								"unit": "8px",
								"gutter": "24px"
							},
							"fontFamily": {
								"body-md": ["Inter"],
								"title-sm": ["Plus Jakarta Sans"],
								"label-caps": ["Inter"],
								"display-lg-mobile": ["Plus Jakarta Sans"],
								"headline-md": ["Plus Jakarta Sans"],
								"body-lg": ["Inter"],
								"label-micro": ["Inter"],
								"display-lg": ["Plus Jakarta Sans"],
								"serif": ["Playfair Display", "serif"]
							}
						}
					}
				};
			}
		</script>
		<style>
		.dlm-library-container-wrapper .filter-btn {
			background-color: #f2f2f3 !important;
			color: #4a5568 !important;
			font-weight: 500 !important;
			border-radius: 9999px !important;
			padding: 10px 24px !important;
			font-size: 14px !important;
			transition: all 0.2s ease-in-out !important;
			border: none !important;
			outline: none !important;
			cursor: pointer !important;
			box-shadow: none !important;
		}
		.dlm-library-container-wrapper .filter-btn:hover {
			background-color: #f2a115 !important;
			color: #3a2200 !important;
			font-weight: 500 !important;
			padding: 10px 24px !important;
		}
		.dlm-library-container-wrapper .filter-btn.active {
			background-color: #855300 !important;
			color: #ffffff !important;
			font-weight: 500 !important;
			padding: 10px 24px !important;
			box-shadow: 0 4px 12px rgba(133, 83, 0, 0.15) !important;
		}
		#reader-modal #close-modal-btn {
			border: 1px solid #e5e7eb !important;
			outline: none !important;
			background-color: transparent !important;
			color: #4a5568 !important;
			box-shadow: none !important;
		}
		#reader-modal #close-modal-btn:hover {
			background-color: #f5f5f7 !important;
		}
		#modal-action-btn {
			background-color: #855300 !important;
			color: #ffffff !important;
			border: none !important;
			outline: none !important;
			font-weight: 700 !important;
			box-shadow: 0 4px 12px rgba(133, 83, 0, 0.15) !important;
			transition: all 0.2s ease !important;
		}
		#modal-action-btn:hover {
			background-color: #613b00 !important;
			color: #ffffff !important;
		}
		</style>
		<div class="dlm-container dlm-library-container-wrapper max-w-[1440px] mx-auto px-margin-mobile md:px-margin-desktop py-4">
			<!-- Header / Status Bar -->
			<header class="dlm-library-header flex justify-between items-center mb-8 pb-4 border-b border-outline-muted">
				<div>
					<h1 class="font-display-lg text-2xl md:text-3xl font-extrabold tracking-tight text-on-surface m-0"><?php esc_html_e( 'Digital Library Catalog', 'digital-library-membership' ); ?></h1>
					<p class="text-xs md:text-sm text-secondary mt-1"><?php esc_html_e( 'Explore our collection of digital manuscripts and books.', 'digital-library-membership' ); ?></p>
				</div>
				<div class="flex items-center gap-3">
					<?php if ( $is_active ) : ?>
						<span class="dlm-status-badge active"><?php esc_html_e( 'Active Member', 'digital-library-membership' ); ?></span>
					<?php elseif ( $is_logged_in ) : ?>
						<span class="dlm-status-badge inactive"><?php esc_html_e( 'No Subscription', 'digital-library-membership' ); ?></span>
						<a href="<?php echo esc_url( $pricing_url ); ?>" class="px-5 py-2.5 bg-primary hover:bg-[#613b00] text-white font-bold rounded-full text-xs transition-all shadow-sm block text-center" style="text-decoration:none;"><?php esc_html_e( 'Join Membership', 'digital-library-membership' ); ?></a>
					<?php else : ?>
						<span class="dlm-status-badge guest bg-[#f5f5f7] text-secondary border border-outline-variant/30 rounded-full text-xs font-semibold px-4 py-2"><?php esc_html_e( 'Guest Preview', 'digital-library-membership' ); ?></span>
						<a href="<?php echo esc_url( $pricing_url ); ?>" class="px-5 py-2.5 bg-[#0071e3] hover:bg-[#005bb5] text-white font-bold rounded-full text-xs transition-all shadow-sm block text-center" style="text-decoration:none;"><?php esc_html_e( 'View Plans', 'digital-library-membership' ); ?></a>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( ! $is_logged_in ) : ?>
				<div class="dlm-msg-box info flex justify-between items-center flex-wrap gap-4 mb-8 bg-[#f0f7ff] border border-[#cce5ff] text-[#004085] p-5 rounded-2xl">
					<div>
						<strong class="font-bold text-on-surface"><?php esc_html_e( 'Welcome to our Digital Library!', 'digital-library-membership' ); ?></strong>
						<p class="text-xs text-secondary mt-0.5"><?php esc_html_e( 'Browse our catalog below. Sign up or log in to unlock full reading access.', 'digital-library-membership' ); ?></p>
					</div>
					<a href="<?php echo esc_url( $pricing_url ); ?>" class="px-5 py-2.5 bg-[#34c759] hover:bg-[#28a745] text-white font-bold rounded-full text-xs transition-all shadow-sm text-center" style="text-decoration:none;"><?php esc_html_e( 'Get Membership Access', 'digital-library-membership' ); ?></a>
				</div>
			<?php endif; ?>

			<!-- Filters & Controls Bar -->
			<div class="dlm-filters-bar mb-10">
				<!-- Category Filter Pills -->
				<div id="category-filters" class="dlm-category-filters-row">
					<button data-category="All" class="filter-btn active whitespace-nowrap"><?php esc_html_e( 'All Library', 'digital-library-membership' ); ?></button>
					<?php foreach ( $categories_set as $cat ) : ?>
						<button data-category="<?php echo esc_attr( $cat ); ?>" class="filter-btn whitespace-nowrap"><?php echo esc_html( $cat ); ?></button>
					<?php endforeach; ?>
				</div>

				<!-- Search Input & Sort Dropdown Row -->
				<div class="dlm-search-sort-row">
					<!-- Search Input -->
					<div class="dlm-search-wrapper">
						<span class="dlm-search-icon">🔍</span>
						<input id="search-input" class="dlm-search-input" placeholder="<?php esc_attr_e( 'Search titles, authors...', 'digital-library-membership' ); ?>" type="text">
					</div>

					<!-- Sort Selector -->
					<div class="dlm-sort-wrapper" id="sort-trigger">
						<span class="dlm-sort-label"><?php esc_html_e( 'Sorted by:', 'digital-library-membership' ); ?> <span id="current-sort-label" class="dlm-sort-current-val"><?php esc_html_e( 'Recent', 'digital-library-membership' ); ?></span></span>
						<span class="dlm-sort-icon">⇅</span>

						<!-- Dropdown Menu -->
						<div id="sort-dropdown" class="dlm-sort-dropdown hidden">
							<button data-sort="recent" class="sort-opt w-full text-left px-4 py-2.5 text-sm font-bold text-on-surface hover:bg-surface-container flex items-center justify-between" type="button">
								<?php esc_html_e( 'Recent', 'digital-library-membership' ); ?> <span>✓</span>
							</button>
							<button data-sort="title-asc" class="sort-opt w-full text-left px-4 py-2.5 text-sm font-medium text-secondary hover:bg-surface-container flex items-center justify-between" type="button">
								<?php esc_html_e( 'Title (A - Z)', 'digital-library-membership' ); ?>
							</button>
							<button data-sort="progress-desc" class="sort-opt w-full text-left px-4 py-2.5 text-sm font-medium text-secondary hover:bg-surface-container flex items-center justify-between" type="button">
								<?php esc_html_e( 'Progress (Highest)', 'digital-library-membership' ); ?>
							</button>
							<button data-sort="category" class="sort-opt w-full text-left px-4 py-2.5 text-sm font-medium text-secondary hover:bg-surface-container flex items-center justify-between" type="button">
								<?php esc_html_e( 'Category', 'digital-library-membership' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Result Count Banner -->
			<div id="result-stats" class="mb-6 text-sm text-secondary font-medium hidden">
				<?php esc_html_e( 'Showing', 'digital-library-membership' ); ?> <span id="visible-count-num" class="font-bold text-on-surface">0</span> <?php esc_html_e( 'of', 'digital-library-membership' ); ?> <span id="total-count-num" class="font-bold text-on-surface">0</span> <?php esc_html_e( 'items', 'digital-library-membership' ); ?>
			</div>

			<!-- Books Grid -->
			<div id="books-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-x-gutter gap-y-12 min-h-[400px]">
				<?php if ( ! empty( $books_data ) ) : ?>
					<?php foreach ( array_slice( $books_data, 0, 12 ) as $book ) : 
						$user_access = $book['user_access'];
						$price_formatted = $book['price_formatted'];
						$is_future = ! empty( $book['is_future'] );
					?>
						<div class="group cursor-pointer animate-fade-in dlm-book-card-item" data-book-id="<?php echo esc_attr( $book['id'] ); ?>">
							<div class="relative aspect-[3/4] mb-4 rounded-2xl overflow-hidden book-card-shadow border border-outline-variant/10">
								<?php if ( ! empty( $book['cover'] ) ) : ?>
									<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo esc_url( $book['cover'] ); ?>" alt="<?php echo esc_attr( $book['title'] ); ?>" loading="lazy">
								<?php else : ?>
									<div class="w-full h-full bg-surface-container flex items-center justify-center text-center p-4">
										<span class="font-bold text-xs"><?php echo esc_html( $book['title'] ); ?></span>
									</div>
								<?php endif; ?>

								<?php if ( $is_future ) : ?>
									<!-- Upcoming Badge on Cover -->
									<div class="absolute top-2.5 left-2.5 z-10">
										<span class="px-2.5 py-1 bg-amber-600/95 backdrop-blur-md text-white text-[10px] font-extrabold uppercase tracking-wider rounded-lg shadow-md flex items-center gap-1">
											<i class="fa-solid fa-clock text-[9px]"></i> <?php esc_html_e( 'Upcoming', 'digital-library-membership' ); ?>
										</span>
									</div>

									<!-- 4-Box Countdown Timer at Bottom of Cover -->
									<?php 
									$rel_time = ! empty( $book['publish_iso'] ) ? $book['publish_iso'] : ( ! empty( $book['publish_date'] ) ? $book['publish_date'] : '' );
									if ( ! empty( $rel_time ) ) : 
									?>
										<div class="absolute bottom-2 inset-x-2 z-10 grid grid-cols-4 gap-1 p-1 rounded-xl shadow-xl text-white dlm-countdown-timer pointer-events-none" style="background: linear-gradient(135deg, rgba(133, 83, 0, 0.92), rgba(97, 59, 0, 0.95)) !important; border: 1px solid rgba(255, 255, 255, 0.28) !important; backdrop-filter: blur(8px);" data-release-time="<?php echo esc_attr( $rel_time ); ?>" data-book-id="<?php echo esc_attr( $book['id'] ); ?>">
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-days font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none"><?php esc_html_e( 'Day', 'digital-library-membership' ); ?></span>
											</div>
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-hours font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none"><?php esc_html_e( 'Hr', 'digital-library-membership' ); ?></span>
											</div>
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-minutes font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none"><?php esc_html_e( 'Min', 'digital-library-membership' ); ?></span>
											</div>
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-seconds font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none"><?php esc_html_e( 'Sec', 'digital-library-membership' ); ?></span>
											</div>
										</div>
									<?php endif; ?>

									<!-- Upcoming Release Overlay -->
									<div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center gap-1.5 p-3 text-center z-20">
										<span class="px-3 py-1.5 bg-white text-black font-extrabold text-xs rounded-xl shadow-lg uppercase tracking-wider">
											<?php esc_html_e( 'Coming Soon', 'digital-library-membership' ); ?>
										</span>
										<p class="text-white/90 text-[11px] font-medium leading-tight mt-1">
											<?php echo esc_html( sprintf( __( 'Releases %s', 'digital-library-membership' ), $book['publish_formatted'] ) ); ?>
										</p>
									</div>
								<?php else : ?>
									<!-- Regular Actions Overlay -->
									<div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center gap-2 p-3 z-10">
										<?php if ( $user_access === 'read_download' ) : ?>
											<span class="dlm-book-action-btn" style="background:#ffffff !important; color:#000000 !important; font-weight:700; font-size:12px; padding:6px 12px; border-radius:10px; text-align:center; width:100%; max-width:120px; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
												<?php echo ( $book['progress'] > 0 ) ? esc_html__( 'Continue', 'digital-library-membership' ) : esc_html__( 'Read', 'digital-library-membership' ); ?>
											</span>
											<button class="dlm-btn-download dlm-book-action-btn" data-book-id="<?php echo esc_attr( $book['id'] ); ?>" style="background:#ffffff !important; color:#000000 !important; font-weight:700; font-size:12px; padding:6px 12px; border-radius:10px; text-align:center; width:100%; max-width:120px; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.2); border:none; cursor:pointer;">
												<?php esc_html_e( 'Download', 'digital-library-membership' ); ?>
											</button>
										<?php elseif ( $user_access === 'read_only' ) : ?>
											<span class="dlm-book-action-btn" style="background:#ffffff !important; color:#000000 !important; font-weight:700; font-size:12px; padding:6px 12px; border-radius:10px; text-align:center; width:100%; max-width:120px; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
												<?php echo ( $book['progress'] > 0 ) ? esc_html__( 'Continue', 'digital-library-membership' ) : esc_html__( 'Read', 'digital-library-membership' ); ?>
											</span>
										<?php else : ?>
											<?php if ( $book['access_type'] === 'purchase_only' || $book['access_type'] === 'hybrid' ) : ?>
												<button class="dlm-btn-buy dlm-book-action-btn" data-book-id="<?php echo esc_attr( $book['id'] ); ?>" style="background:#ffffff !important; color:#000000 !important; font-weight:700; font-size:12px; padding:6px 12px; border-radius:10px; text-align:center; width:100%; max-width:120px; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.2); border:none; cursor:pointer;">
												<?php echo esc_html( sprintf( __( 'Buy (%s)', 'digital-library-membership' ), $price_formatted ) ); ?>
											</button>
										<?php elseif ( $is_logged_in ) : ?>
											<span class="dlm-book-action-btn" style="background:#ffffff !important; color:#000000 !important; font-weight:700; font-size:12px; padding:6px 12px; border-radius:10px; text-align:center; width:100%; max-width:120px; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
												<?php esc_html_e( 'Subscribe', 'digital-library-membership' ); ?>
											</span>
										<?php else : ?>
											<span class="dlm-book-action-btn" style="background:#ffffff !important; color:#000000 !important; font-weight:700; font-size:12px; padding:6px 12px; border-radius:10px; text-align:center; width:100%; max-width:120px; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
												<?php esc_html_e( 'Sign In', 'digital-library-membership' ); ?>
											</span>
										<?php endif; ?>
									<?php endif; ?>
									</div>
								<?php endif; ?>

								<?php if ( ! $is_future && $book['progress'] > 0 ) : ?>
									<div class="absolute bottom-0 left-0 w-full h-1.5 bg-black/20">
										<div class="h-full bg-surface-amber transition-all duration-300" style="width: <?php echo intval( $book['progress'] ); ?>%;"></div>
									</div>
								<?php endif; ?>
							</div>
							<div class="space-y-1">
								<div class="flex items-center justify-between">
									<span class="text-label-micro text-primary font-bold uppercase tracking-wider"><?php echo esc_html( $book['category'] ); ?></span>
									<?php if ( ! $is_future && $book['progress'] > 0 ) : ?>
										<span class="text-label-micro text-secondary font-semibold"><?php echo intval( $book['progress'] ); ?>% Read</span>
									<?php endif; ?>
								</div>
								<h5 class="font-bold text-on-surface leading-snug mb-1 group-hover:text-primary transition-colors line-clamp-1"><?php echo esc_html( $book['title'] ); ?></h5>
								<p class="text-xs text-secondary line-clamp-1"><?php echo esc_html( $book['author'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="col-span-full text-center py-16">
						<span class="material-symbols-outlined text-[64px] text-outline-variant mb-4">menu_book</span>
						<h3 class="text-xl font-bold text-on-surface mb-2"><?php esc_html_e( 'No Books Found', 'digital-library-membership' ); ?></h3>
						<p class="text-secondary text-sm max-w-sm mx-auto"><?php esc_html_e( 'No published books are currently available in the digital library catalog.', 'digital-library-membership' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Empty State Container -->
			<div id="empty-state" class="hidden text-center py-16">
				<span class="material-symbols-outlined text-[64px] text-outline-variant mb-4">search_off</span>
				<h3 class="text-xl font-bold text-on-surface mb-2"><?php esc_html_e( 'No matching books found', 'digital-library-membership' ); ?></h3>
				<p class="text-secondary text-sm max-w-sm mx-auto mb-6"><?php esc_html_e( 'Try adjusting your search terms or category filters to find what you\'re looking for.', 'digital-library-membership' ); ?></p>
				<button id="reset-filters-btn" class="px-6 py-2.5 bg-primary text-white font-bold rounded-full hover:bg-primary-container transition-all" type="button"><?php esc_html_e( 'Reset All Filters', 'digital-library-membership' ); ?></button>
			</div>

			<!-- Pagination / Load More -->
			<div class="mt-16 flex justify-center">
				<button id="load-more-btn" class="flex items-center gap-2 px-10 py-4 bg-surface-container hover:bg-surface-variant text-on-surface font-bold rounded-full transition-all group shadow-sm" type="button">
					<span id="load-more-text"><?php esc_html_e( 'Load More Manuscripts', 'digital-library-membership' ); ?></span>
					<span class="material-symbols-outlined group-hover:translate-y-1 transition-transform">keyboard_arrow_down</span>
				</button>
			</div>
		</div>

		<!-- Reading Modal -->
		<div id="reader-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
			<div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative animate-fade-in border border-outline-variant/30">
				<button id="close-modal-btn" class="absolute top-6 right-6 w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-secondary transition-colors" type="button">
					<span class="material-symbols-outlined">close</span>
				</button>

				<div class="flex gap-6 mb-4">
					<img id="modal-cover" class="w-24 h-36 object-cover rounded-xl shadow-md border border-outline-variant/30 flex-shrink-0" src="" alt="Book cover">
					<div class="flex-1 space-y-1.5 pt-1">
						<span id="modal-category" class="text-label-micro text-primary font-bold uppercase tracking-wider"></span>
						<h3 id="modal-title" class="font-title-sm text-on-surface text-xl font-bold leading-tight"></h3>
						<p id="modal-author" class="text-sm text-secondary"></p>
						<p id="modal-published" class="text-xs text-secondary/70"></p>
					</div>
				</div>

				<!-- Upcoming Release Countdown in Modal -->
				<div id="modal-countdown-container" class="hidden my-4 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-900">
					<div class="flex items-center justify-between mb-2.5">
						<span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
							<i class="fa-solid fa-clock text-amber-600"></i>
							<?php esc_html_e( 'Release Countdown', 'digital-library-membership' ); ?>
						</span>
						<span id="modal-release-date-badge" class="text-[11px] font-bold text-amber-800 bg-amber-200/70 px-2.5 py-0.5 rounded-full"></span>
					</div>
					<div class="grid grid-cols-4 gap-2 text-center dlm-countdown-timer" id="modal-countdown-timer" data-release-time="">
						<div class="bg-white/90 backdrop-blur-sm py-2.5 px-1 rounded-xl shadow-xs border border-amber-200/60 flex flex-col items-center justify-center">
							<span class="countdown-days font-mono font-extrabold text-xl text-amber-950 block leading-tight">00</span>
							<span class="text-[9px] uppercase font-extrabold tracking-wider text-amber-700 mt-0.5"><?php esc_html_e( 'Day', 'digital-library-membership' ); ?></span>
						</div>
						<div class="bg-white/90 backdrop-blur-sm py-2.5 px-1 rounded-xl shadow-xs border border-amber-200/60 flex flex-col items-center justify-center">
							<span class="countdown-hours font-mono font-extrabold text-xl text-amber-950 block leading-tight">00</span>
							<span class="text-[9px] uppercase font-extrabold tracking-wider text-amber-700 mt-0.5"><?php esc_html_e( 'Hr', 'digital-library-membership' ); ?></span>
						</div>
						<div class="bg-white/90 backdrop-blur-sm py-2.5 px-1 rounded-xl shadow-xs border border-amber-200/60 flex flex-col items-center justify-center">
							<span class="countdown-minutes font-mono font-extrabold text-xl text-amber-950 block leading-tight">00</span>
							<span class="text-[9px] uppercase font-extrabold tracking-wider text-amber-700 mt-0.5"><?php esc_html_e( 'Min', 'digital-library-membership' ); ?></span>
						</div>
						<div class="bg-white/90 backdrop-blur-sm py-2.5 px-1 rounded-xl shadow-xs border border-amber-200/60 flex flex-col items-center justify-center">
							<span class="countdown-seconds font-mono font-extrabold text-xl text-amber-950 block leading-tight">00</span>
							<span class="text-[9px] uppercase font-extrabold tracking-wider text-amber-700 mt-0.5"><?php esc_html_e( 'Sec', 'digital-library-membership' ); ?></span>
						</div>
					</div>
				</div>

				<div class="space-y-4 pt-4 border-t border-outline-muted">
					<div>
						<h4 class="text-xs font-bold text-on-surface uppercase tracking-wider mb-2"><?php esc_html_e( 'Synopsis', 'digital-library-membership' ); ?></h4>
						<p id="modal-description" class="text-xs text-secondary leading-relaxed max-h-32 overflow-y-auto pr-2"></p>
					</div>

					<div class="flex gap-3 pt-2">
						<a id="modal-action-btn" href="#" class="w-full py-3.5 text-center block text-sm rounded-full" style="text-decoration:none; box-sizing:border-box;"></a>
					</div>
				</div>
			</div>
		</div>

		<!-- Notification Toast -->
		<div id="toast-notif" class="fixed bottom-6 right-6 z-50 bg-inverse-surface text-inverse-on-surface px-6 py-3.5 rounded-2xl shadow-xl text-sm font-semibold flex items-center gap-3 transform translate-y-20 opacity-0 transition-all duration-300">
			<span class="material-symbols-outlined text-primary-fixed">auto_stories</span>
			<span id="toast-message"><?php esc_html_e( 'Reading session launched!', 'digital-library-membership' ); ?></span>
		</div>

		<script>
			window.dlmLibraryData = {
				isLoggedIn: <?php echo json_encode( $is_logged_in ); ?>,
				isActive: <?php echo json_encode( $is_active ); ?>,
				pricingUrl: <?php echo json_encode( $pricing_url ); ?>,
				currency: <?php echo json_encode( $currency ); ?>,
				paymentEngine: <?php echo json_encode( dlm_get_payment_engine() ); ?>,
				ajaxUrl: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
				nonce: <?php echo json_encode( wp_create_nonce( 'dlm_public_nonce' ) ); ?>,
				restNonce: <?php echo json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
				restBase: <?php echo json_encode( esc_url_raw( rest_url( 'dlm/v1' ) ) ); ?>,
				books: <?php echo json_encode( $books_data ); ?>
			};
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode dlm_pricing - Renders membership pricing plans for user selection
	 * Accessible to everyone (guests, non-subscribers, subscribers)
	 */
	public function render_pricing() {
		$is_logged_in = is_user_logged_in();
		$user_id      = $is_logged_in ? get_current_user_id() : 0;
		$is_active    = $is_logged_in ? $this->db->has_active_membership( $user_id ) : false;

		$all_packages    = dlm_get_packages();
		$active_packages = array();
		foreach ( $all_packages as $pkg ) {
			if ( ! isset( $pkg['status'] ) || 'active' === $pkg['status'] ) {
				$active_packages[] = $pkg;
			}
		}

		$currency     = get_option( 'dlm_currency', 'USD' );
		$checkout_url = dlm_get_page_url( 'checkout' );

		ob_start();
		?>
		<script id="tailwind-config-pricing">
			if (typeof tailwind !== 'undefined') {
				tailwind.config = {
					darkMode: "class",
					theme: {
						extend: {
							"colors": {
								"primary": "#855300",
								"primary-container": "#613b00",
								"secondary": "#5f5e60",
								"secondary-container": "#fdb965",
								"on-secondary-container": "#855300",
								"on-surface": "#1d1d1f",
								"on-surface-variant": "#514538",
								"background": "#fafafa"
							}
						}
					}
				};
			}
		</script>
		<div class="dlm-pricing-container max-w-5xl mx-auto px-4 py-12" style="font-family: 'Plus Jakarta Sans', sans-serif;">
			<div class="text-center mb-12">
				<h1 class="text-3xl md:text-4xl font-extrabold text-on-surface tracking-tight mb-3"><?php esc_html_e( 'Choose Your Plan', 'digital-library-membership' ); ?></h1>
				<p class="text-sm md:text-base text-secondary max-w-xl mx-auto"><?php esc_html_e( 'Get instant, unlimited access to our entire library of digital books. Cancel anytime.', 'digital-library-membership' ); ?></p>
			</div>

			<?php if ( $is_active ) : ?>
				<div class="dlm-msg-box success bg-green-50 border border-green-200 text-green-700 p-4 rounded-2xl mb-8 text-center max-w-xl mx-auto text-sm font-semibold">
					<?php esc_html_e( 'You already have an active membership subscription!', 'digital-library-membership' ); ?>
					<a href="<?php echo esc_url( dlm_get_page_url( 'account' ) ); ?>" class="underline ml-2 text-green-800 font-bold"><?php esc_html_e( 'Manage Account', 'digital-library-membership' ); ?></a>
				</div>
			<?php endif; ?>

			<?php if ( empty( $active_packages ) ) : ?>
				<div class="p-8 text-center bg-white rounded-3xl border border-outline-variant/20 max-w-md mx-auto">
					<p class="text-secondary text-sm"><?php esc_html_e( 'No membership plans are currently active. Please check back soon!', 'digital-library-membership' ); ?></p>
				</div>
			<?php else : 
				$cols_class = count( $active_packages ) === 1 ? 'grid-cols-1 max-w-md' : ( count( $active_packages ) === 2 ? 'grid-cols-1 md:grid-cols-2 max-w-3xl' : 'grid-cols-1 md:grid-cols-3 max-w-5xl' );
			?>
				<!-- Pricing Tiers Grid -->
				<div class="grid <?php echo esc_attr( $cols_class ); ?> gap-8 mx-auto">
					<?php foreach ( $active_packages as $pkg ) : 
						$is_highlighted = ! empty( $pkg['badge'] ) && ( stripos( $pkg['badge'], 'best' ) !== false || stripos( $pkg['badge'], 'scholar' ) !== false || 'yearly' === $pkg['interval'] );
						$period_suffix  = ( 'lifetime' === $pkg['interval'] ) ? __( '/one-time', 'digital-library-membership' ) : ( ( 'yearly' === $pkg['interval'] ) ? __( '/year', 'digital-library-membership' ) : __( '/month', 'digital-library-membership' ) );
						$cta_label      = ( 'lifetime' === $pkg['interval'] ) ? __( 'Unlock Lifetime', 'digital-library-membership' ) : ( ( 'yearly' === $pkg['interval'] ) ? __( 'Subscribe Yearly', 'digital-library-membership' ) : __( 'Select Plan', 'digital-library-membership' ) );
					?>
						<div class="bg-white <?php echo $is_highlighted ? 'border-2 border-primary shadow-xl hover:shadow-2xl' : 'border border-outline-variant/30 shadow-sm hover:shadow-lg'; ?> rounded-[32px] p-8 flex flex-col relative book-card-shadow transition-all duration-300 hover:-translate-y-1">
							<?php if ( $is_highlighted && ! empty( $pkg['badge'] ) && stripos( $pkg['badge'], 'best' ) !== false ) : ?>
								<div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-primary text-white px-6 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest whitespace-nowrap"><?php echo esc_html( $pkg['badge'] ); ?></div>
							<?php endif; ?>

							<div class="mb-8">
								<?php if ( ! empty( $pkg['badge'] ) && stripos( $pkg['badge'], 'best' ) === false ) : ?>
									<span class="<?php echo $is_highlighted ? 'text-primary bg-primary/10' : 'text-secondary bg-[#f5f5f7]'; ?> px-3 py-1 rounded-full text-xs uppercase font-semibold mb-4 inline-block"><?php echo esc_html( $pkg['badge'] ); ?></span>
								<?php endif; ?>
								<h3 class="font-bold text-xl text-on-surface mb-2"><?php echo esc_html( $pkg['name'] ); ?></h3>
								<?php if ( ! empty( $pkg['description'] ) ) : ?>
									<p class="text-xs text-secondary mb-3 leading-relaxed"><?php echo esc_html( $pkg['description'] ); ?></p>
								<?php endif; ?>
								<div class="flex items-baseline gap-1 mt-4">
									<span class="text-3xl font-bold text-on-surface">$<?php echo esc_html( number_format( floatval( $pkg['price'] ), 2 ) ); ?></span>
									<span class="text-secondary text-xs"><?php echo esc_html( $period_suffix ); ?></span>
								</div>
							</div>

							<ul class="space-y-4 mb-8 flex-1 text-sm text-on-surface-variant">
								<?php if ( ! empty( $pkg['features'] ) && is_array( $pkg['features'] ) ) : ?>
									<?php foreach ( $pkg['features'] as $feat ) : ?>
										<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary shrink-0"></i> <span><?php echo esc_html( $feat ); ?></span></li>
									<?php endforeach; ?>
								<?php endif; ?>
							</ul>

							<a href="<?php echo esc_url( add_query_arg( 'plan', $pkg['id'], $checkout_url ) ); ?>" class="w-full py-3.5 <?php echo $is_highlighted ? 'bg-primary hover:bg-primary-container text-white shadow-md' : 'bg-[#f5f5f7] hover:bg-[#e5e5ea] text-on-surface'; ?> font-semibold rounded-2xl text-center transition-all block text-sm" style="text-decoration: none; box-sizing: border-box;"><?php echo esc_html( $cta_label ); ?></a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode dlm_checkout - Renders checkout & payment options for the selected plan
	 */
	public function render_checkout() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_plan = isset( $_GET['plan'] ) ? sanitize_key( $_GET['plan'] ) : 'monthly';
		$package       = dlm_get_package( $selected_plan );

		if ( ! $package ) {
			// Fallback to first active package or default monthly
			$packages = dlm_get_packages();
			$package  = reset( $packages );
		}

		$plan_name     = $package ? $package['name'] : __( 'Monthly Membership', 'digital-library-membership' );
		$plan_price    = $package ? number_format( floatval( $package['price'] ), 2 ) : '9.99';
		$plan_interval = $package && ! empty( $package['interval'] ) ? $package['interval'] : 'monthly';
		$plan_period   = ( 'lifetime' === $plan_interval ) ? __( '/one-time', 'digital-library-membership' ) : ( ( 'yearly' === $plan_interval ) ? __( '/yr', 'digital-library-membership' ) : __( '/mo', 'digital-library-membership' ) );

		$currency      = get_option( 'dlm_currency', 'USD' );
		$user_id       = get_current_user_id();
		$sub           = $user_id ? $this->db->get_subscription_by_user( $user_id ) : null;
		$is_active     = $user_id ? $this->db->has_active_membership( $user_id ) : false;

		if ( $sub && $sub->status === 'pending_approval' ) {
			return '<div class="dlm-msg-box info" style="background:#fff9e6; border:1px solid #ffe0b3; color:#b36b00; padding:15px; border-radius:12px; margin-bottom:20px;">
				<p>' . esc_html__( 'Your subscription request (Manual Payment) is pending administrator approval. Please wait for the admin to verify and approve your transaction.', 'digital-library-membership' ) . '</p>
			</div>';
		}

		if ( $is_active ) {
			return '<div class="dlm-msg-box success"><p>' . __( 'You already have an active membership subscription! Visit your account page to manage your subscription.', 'digital-library-membership' ) . ' <a href="' . esc_url( dlm_get_page_url( 'account' ) ) . '">' . __( 'Library Account', 'digital-library-membership' ) . '</a></p></div>';
		}

		// Check if WooCommerce product is configured for this plan
		$wc_product_id = 0;
		if ( class_exists( 'WooCommerce' ) ) {
			if ( ! empty( $package['wc_product_id'] ) ) {
				$wc_product_id = intval( $package['wc_product_id'] );
			} else {
				$wc_product_id = intval( get_option( 'dlm_wc_' . $plan_interval . '_product' ) );
			}
		}

		ob_start();
		?>
		<div class="dlm-checkout-page-container dlm-container" style="max-width: 680px; margin: 0 auto;">
			<!-- Plan Summary Card -->
			<div class="dlm-plan-summary-card" style="background:#fff; border:1px solid #d2d2d7; border-radius:20px; padding:25px 30px; margin-bottom:25px; box-shadow:0 4px 20px rgba(0,0,0,0.03); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
				<div>
					<span style="font-size:12px; font-weight:700; text-transform:uppercase; color:#8e8e93; letter-spacing:0.05em;"><?php esc_html_e( 'Selected Subscription Plan', 'digital-library-membership' ); ?></span>
					<h2 style="margin:4px 0 0 0; font-size:22px; color:#1d1d1f;"><?php echo esc_html( $plan_name ); ?></h2>
				</div>
				<div style="text-align:right;">
					<div style="font-size:24px; font-weight:800; color:#0071e3;">
						$<span id="selected-plan-amount"><?php echo esc_html( $plan_price ); ?></span>
						<span style="font-size:14px; font-weight:400; color:#8e8e93;"><?php echo esc_html( $plan_period ); ?></span>
					</div>
					<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" style="font-size:13px; color:#0071e3; text-decoration:underline; font-weight:600;"><?php esc_html_e( 'Change Plan', 'digital-library-membership' ); ?></a>
				</div>
			</div>

			<?php if ( ! is_user_logged_in() ) : ?>
				<!-- Guest Checkout Notice & Auth Prompt -->
				<div class="dlm-msg-box info" style="background:#f0f7ff; border:1px solid #cce5ff; color:#004085; padding:15px 20px; border-radius:12px; margin-bottom:20px; text-align:center;">
					<p style="margin:0; font-size:14px;"><?php esc_html_e( 'Please sign in or create an account to complete your checkout.', 'digital-library-membership' ); ?></p>
				</div>
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped HTML auth template.
				echo $this->get_login_prompt_html(); 
				?>
			<?php else : ?>
				<!-- Payment Methods Container for Logged-In Users -->
				<div class="dlm-payment-box" style="background:#fff; border:1px solid #d2d2d7; border-radius:20px; padding:30px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
					<h3 style="margin-top:0; margin-bottom:20px; font-size:18px; font-weight:700; color:#1d1d1f;"><?php esc_html_e( 'Select Payment Method', 'digital-library-membership' ); ?></h3>



					<div class="dlm-payment-options">
						<!-- Stripe Checkout Button -->
						<button id="dlm-stripe-btn" class="dlm-btn dlm-btn-stripe dlm-btn-block select-plan-btn" data-interval="<?php echo esc_attr( $selected_plan ); ?>">
							<span class="stripe-icon"></span> <?php esc_html_e( 'Pay with Credit/Debit Card (Stripe)', 'digital-library-membership' ); ?>
						</button>

						<div style="text-align: center; margin: 15px 0; color: #888;">— <?php esc_html_e( 'OR', 'digital-library-membership' ); ?> —</div>

						<!-- PayPal Button container -->
						<div id="paypal-button-container" style="margin-bottom:15px;"></div>

						<!-- WooCommerce Checkout Option (if configured & active) -->
						<?php if ( $wc_product_id > 0 ) : ?>
							<div style="text-align: center; margin: 15px 0; color: #888;">— <?php esc_html_e( 'OR', 'digital-library-membership' ); ?> —</div>
							<button id="dlm-wc-btn" class="dlm-btn dlm-btn-block select-plan-btn" style="background:#7f54b7; color:#fff;" data-interval="<?php echo esc_attr( $selected_plan ); ?>">
								🛒 <?php esc_html_e( 'Pay via WooCommerce Checkout', 'digital-library-membership' ); ?>
							</button>
						<?php endif; ?>

						<div style="text-align: center; margin: 15px 0; color: #888;">— <?php esc_html_e( 'OR', 'digital-library-membership' ); ?> —</div>

						<!-- Manual Bank Transfer Option -->
						<button id="dlm-manual-toggle-btn" class="dlm-btn dlm-btn-block" style="background:#f5f5f7; border: 1px solid #d2d2d7; color:#1d1d1f;">
							💼 <?php esc_html_e( 'Direct Bank / Manual Transfer', 'digital-library-membership' ); ?>
						</button>

						<!-- Manual Payment Form (Hidden initially) -->
						<div id="dlm-manual-checkout-fields" style="display:none; margin-top:20px; border-top:1px solid #d2d2d7; padding-top:20px; text-align:left;">
							<div class="dlm-manual-instructions" style="background:#f5f5f7; padding: 15px; border-radius: 12px; font-size:13px; line-height:1.4; color:#515154; margin-bottom:15px; border-left:4px solid #0071e3;">
								<?php echo wp_kses_post( get_option( 'dlm_manual_payment_instructions', __( 'Please transfer funds directly to our bank details and submit your reference code below.', 'digital-library-membership' ) ) ); ?>
							</div>
							<p>
								<label for="manual_txn_reference" style="font-weight:600; font-size:13px;"><?php esc_html_e( 'Transaction Reference Code *', 'digital-library-membership' ); ?></label>
								<input type="text" id="manual_txn_reference" style="width:100%; border:1px solid #d2d2d7; border-radius:8px; padding:10px; margin-top:5px; font-size:14px;" placeholder="e.g. wire transfer confirmation code">
							</p>
							<button id="dlm-submit-manual-payment-btn" class="dlm-btn dlm-btn-primary dlm-btn-block" style="margin-top:10px;"><?php esc_html_e( 'Submit Reference Code', 'digital-library-membership' ); ?></button>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				if (typeof renderPayPalButtons === 'function') {
					renderPayPalButtons('<?php echo esc_js( $selected_plan ); ?>');
				}
			});
		</script>

		<!-- PayPal JS SDK loads dynamically based on Client ID setting -->
		<?php
		$paypal_client_id = get_option( 'dlm_paypal_client_id' );
		if ( $paypal_client_id ) :
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript, WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script( 'dlm-paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . esc_attr( $paypal_client_id ) . '&vault=true&intent=subscription', array(), null, true );
		endif;
		?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode dlm_account - Renders user account profile, subscription status, and billing logs
	 */
	public function render_account() {
		if ( ! is_user_logged_in() ) {
			return $this->get_login_prompt_html();
		}

		$user_id = get_current_user_id();
		$sub = $this->db->get_subscription_by_user( $user_id );
		$is_active = $this->db->has_active_membership( $user_id );

		ob_start();
		?>
		<div class="dlm-account-container dlm-container">
			<h1><?php esc_html_e( 'My Library Account', 'digital-library-membership' ); ?></h1>

			<div class="dlm-account-grid">
				<div class="dlm-account-card">
					<h2><?php esc_html_e( 'Membership Status', 'digital-library-membership' ); ?></h2>
					<?php if ( $is_active && $sub ) : ?>
						<div class="dlm-sub-details">
							<div class="status-indicator active">
								<strong><?php esc_html_e( 'Active Membership', 'digital-library-membership' ); ?></strong>
							</div>
							<p><?php 
								/* translators: %s: Plan interval */
								echo wp_kses( sprintf( __( 'Plan: %s billing cycles', 'digital-library-membership' ), '<strong style="text-transform:uppercase;">' . esc_html( $sub->plan_interval ) . '</strong>' ), array( 'strong' => array( 'style' => array() ) ) ); 
							?></p>
							<p><?php 
								/* translators: %s: Expiration date */
								echo wp_kses( sprintf( __( 'Renews/Expires on: %s', 'digital-library-membership' ), '<strong>' . esc_html( date_i18n( get_option('date_format'), strtotime($sub->expires_at) ) ) . '</strong>' ), array( 'strong' => array() ) ); 
							?></p>
							<p><span class="meta-info"><?php 
								/* translators: %s: Payment provider name */
								echo esc_html( sprintf( __( 'Billed via: %s', 'digital-library-membership' ), ucfirst($sub->provider) ) ); 
							?></span></p>

							<?php if ( $sub->status === 'active' ) : ?>
								<div style="margin-top: 20px;">
									<p style="font-size:12px; color:#888;"><?php esc_html_e( 'Need to change or cancel? You can manage renewals directly from your billing provider dashboard.', 'digital-library-membership' ); ?></p>
								</div>
							<?php endif; ?>
						</div>
					<?php elseif ( get_user_meta( $user_id, 'dlm_manual_override', true ) === 'active' ) : ?>
						<div class="dlm-sub-details">
							<div class="status-indicator active">
								<strong><?php esc_html_e( 'Active (Staff / Manual Override)', 'digital-library-membership' ); ?></strong>
							</div>
							<p><?php esc_html_e( 'Your account has been granted unlimited reading permissions by an administrator.', 'digital-library-membership' ); ?></p>
						</div>
					<?php else : ?>
						<div class="dlm-sub-details">
							<div class="status-indicator inactive">
								<strong><?php esc_html_e( 'No Active Membership', 'digital-library-membership' ); ?></strong>
							</div>
							<p><?php esc_html_e( 'Subscribe to unlock reading capabilities for all digital books.', 'digital-library-membership' ); ?></p>
							<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="dlm-btn dlm-btn-primary"><?php esc_html_e( 'View Pricing Plans', 'digital-library-membership' ); ?></a>
						</div>
					<?php endif; ?>
				</div>

				<div class="dlm-account-card">
					<h2><?php esc_html_e( 'Profile Details', 'digital-library-membership' ); ?></h2>
					<?php
					$user = wp_get_current_user();
					?>
					<p><strong><?php esc_html_e( 'Display Name:', 'digital-library-membership' ); ?></strong> <?php echo esc_html( $user->display_name ); ?></p>
					<p><strong><?php esc_html_e( 'Email Address:', 'digital-library-membership' ); ?></strong> <?php echo esc_html( $user->user_email ); ?></p>
					<p><strong><?php esc_html_e( 'Registered On:', 'digital-library-membership' ); ?></strong> <?php echo esc_html( date_i18n( get_option('date_format'), strtotime($user->user_registered) ) ); ?></p>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Simple Login / Registration prompt for non-logged-in visitors
	 */
	public function get_login_prompt_html() {
		ob_start();
		?>
		<div class="dlm-auth-container-wrapper relative z-10 w-full max-w-[480px] mx-auto">
			<div class="glass-card book-card-shadow rounded-2xl p-6 md:p-10 border border-[#d8c3ad]/40 bg-white/95 backdrop-blur-xl">
				<!-- Brand Identity & Tab Toggle -->
				<div class="dlm-auth-header-row flex items-center justify-between mb-8 pb-4 border-b border-[#d8c3ad]/30">
					<div class="dlm-auth-brand-wrapper flex items-center gap-3">
						<div class="w-10 h-10 bg-[#855300] rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
							<i class="fa-solid fa-book-open text-white text-[16px]"></i>
						</div>
						<div class="sidebar-brand-text">
							<span class="font-title-sm text-[17px] text-[#1a1c1c] font-bold tracking-tight block leading-tight">Bridgeway36</span>
							<p class="text-[#5f5e60] text-[10px] font-semibold uppercase tracking-widest mt-0.5">Digital Library</p>
						</div>
					</div>

					<!-- Auth Mode Tabs (Sign In / Register) -->
					<div class="dlm-auth-mode-tabs flex bg-[#eeeeee] p-1 rounded-xl gap-1">
						<button type="button" class="dlm-auth-tab-btn active px-3 py-1.5 rounded-lg text-[12px] font-semibold text-[#5f5e60] transition-all cursor-pointer" data-tab="login"><?php esc_html_e( 'Sign In', 'digital-library-membership' ); ?></button>
						<button type="button" class="dlm-auth-tab-btn px-3 py-1.5 rounded-lg text-[12px] font-semibold text-[#5f5e60] transition-all cursor-pointer" data-tab="register"><?php esc_html_e( 'Register', 'digital-library-membership' ); ?></button>
					</div>
				</div>

				<!-- LOGIN PANEL -->
				<div class="dlm-auth-panel" id="panel-login">
					<header class="mb-6">
						<h1 class="text-[28px] md:text-[34px] font-bold text-[#1a1c1c] mb-2 leading-tight tracking-tight"><?php esc_html_e( 'Welcome Back', 'digital-library-membership' ); ?></h1>
						<p class="text-[15px] text-[#5f5e60] leading-relaxed"><?php esc_html_e( 'Continue your journey through the curated archives.', 'digital-library-membership' ); ?></p>
					</header>

					<?php 
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['social_error'] ) ) : 
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$social_err = sanitize_key( wp_unslash( $_GET['social_error'] ) );
						$err_msg = __( 'Social authentication could not be completed. Please try again or use your password.', 'digital-library-membership' );
						if ( 'google_access_denied' === $social_err || 'apple_access_denied' === $social_err ) {
							$err_msg = __( 'Sign-in was cancelled by the provider.', 'digital-library-membership' );
						} elseif ( 'unverified_email' === $social_err ) {
							$err_msg = __( 'Your social account email is not verified.', 'digital-library-membership' );
						}
					?>
						<div class="dlm-auth-alert text-xs p-3 rounded-xl mb-4 font-medium bg-red-50 text-red-700 border border-red-200 block">
							<i class="fa-solid fa-circle-exclamation mr-1.5"></i><?php echo esc_html( $err_msg ); ?>
						</div>
					<?php endif; ?>

					<?php 
					$enable_google = get_option( 'dlm_enable_google_login', '0' );
					$enable_apple  = get_option( 'dlm_enable_apple_login', '0' );
					$has_social    = ( '1' === $enable_google || '1' === $enable_apple );
					if ( $has_social ) : ?>
						<div class="dlm-social-buttons space-y-2.5 mb-5">
							<?php if ( '1' === $enable_google ) : ?>
								<a href="<?php echo esc_url( DLM_Social_Auth::get_auth_url( 'google' ) ); ?>" class="w-full h-12 bg-white hover:bg-gray-50 border border-[#d8c3ad] text-[#1a1c1c] font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-3 shadow-sm hover:shadow hover:border-gray-400 no-underline cursor-pointer">
									<svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
										<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
										<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
										<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
										<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
									</svg>
									<span><?php esc_html_e( 'Continue with Google', 'digital-library-membership' ); ?></span>
								</a>
							<?php endif; ?>

							<?php if ( '1' === $enable_apple ) : ?>
								<a href="<?php echo esc_url( DLM_Social_Auth::get_auth_url( 'apple' ) ); ?>" class="w-full h-12 bg-black hover:bg-neutral-800 text-white font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2.5 shadow-sm hover:shadow no-underline cursor-pointer">
									<i class="fa-brands fa-apple text-lg leading-none"></i>
									<span><?php esc_html_e( 'Continue with Apple', 'digital-library-membership' ); ?></span>
								</a>
							<?php endif; ?>

							<div class="relative flex py-1.5 items-center">
								<div class="flex-grow border-t border-[#d8c3ad]/50"></div>
								<span class="flex-shrink mx-3 text-[11px] uppercase font-bold text-[#867461] tracking-wider"><?php esc_html_e( 'Or with email', 'digital-library-membership' ); ?></span>
								<div class="flex-grow border-t border-[#d8c3ad]/50"></div>
							</div>
						</div>
					<?php endif; ?>

					<form id="dlm-login-form" class="space-y-4">
						<div class="dlm-auth-alert text-xs p-3 rounded-xl mb-3 font-medium" style="display:none;"></div>

						<!-- Email/Username Field -->
						<div class="field-group space-y-1.5">
							<label class="text-[11px] font-bold text-[#5f5e60] uppercase tracking-wider block" for="dlm_username"><?php esc_html_e( 'Email Address or Username', 'digital-library-membership' ); ?></label>
							<div class="input-relative-wrapper relative">
								<input class="w-full h-13 px-4 bg-white border border-[#d8c3ad] rounded-xl text-[14px] text-[#1a1c1c] input-focus-ring btn-transition placeholder:text-[#867461]/60" id="dlm_username" name="username" placeholder="name@example.com" required type="text" />
								<i class="fa-regular fa-envelope input-icon-right absolute right-4 top-1/2 -translate-y-1/2 text-[#867461]"></i>
							</div>
						</div>

						<!-- Password Field -->
						<div class="field-group space-y-1.5">
							<div class="flex justify-between items-center">
								<label class="text-[11px] font-bold text-[#5f5e60] uppercase tracking-wider block" for="dlm_password"><?php esc_html_e( 'Password', 'digital-library-membership' ); ?></label>
							</div>
							<div class="input-relative-wrapper relative">
								<input class="w-full h-13 px-4 bg-white border border-[#d8c3ad] rounded-xl text-[14px] text-[#1a1c1c] input-focus-ring btn-transition placeholder:text-[#867461]/60" id="dlm_password" name="password" placeholder="••••••••" required type="password" />
								<button class="dlm-pwd-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#867461] hover:text-[#855300] transition-colors p-1 cursor-pointer border-none bg-transparent flex items-center justify-center" onclick="togglePasswordVisibility('dlm_password', this)" type="button">
									<i class="fa-solid fa-eye text-[15px]"></i>
								</button>
							</div>
						</div>

						<!-- Remember Me -->
						<div class="remember-row flex items-center gap-2.5 pt-1">
							<input class="w-4 h-4 rounded border-[#d8c3ad] text-[#855300] focus:ring-[#855300]/20 cursor-pointer" id="remember" type="checkbox" checked />
							<label class="text-[13px] text-[#5f5e60] select-none cursor-pointer" for="remember"><?php esc_html_e( 'Keep me signed in on this device', 'digital-library-membership' ); ?></label>
						</div>

						<!-- Google ReCAPTCHA -->
						<?php 
						$recaptcha_mode     = get_option( 'dlm_recaptcha_mode', 'production' );
						$recaptcha_site_key = ( $recaptcha_mode === 'testing' ) ? '6LeIxAcTAAAAAJcZVRqy9m71zuoE0tV7mP9XXqgC' : get_option( 'dlm_recaptcha_site_key' );
						$recaptcha_version  = ( $recaptcha_mode === 'testing' ) ? 'v2' : get_option( 'dlm_recaptcha_version', 'v2' );
						if ( $recaptcha_site_key && $recaptcha_version === 'v2' ) : ?>
							<div class="g-recaptcha flex justify-center my-3" data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"></div>
						<?php endif; ?>

						<!-- Sign In Button -->
						<button class="w-full h-13 bg-[#855300] hover:bg-[#613b00] text-white font-semibold text-[15px] rounded-xl btn-transition shadow-md hover:shadow-lg active:scale-[0.98] mt-4 flex items-center justify-center gap-2 cursor-pointer" type="submit">
							<span><?php esc_html_e( 'Sign In', 'digital-library-membership' ); ?></span>
							<i class="fa-solid fa-arrow-right text-xs"></i>
						</button>
					</form>

					<!-- Footer Switcher -->
					<footer class="mt-8 pt-6 border-t border-[#d8c3ad]/50 flex flex-col items-center gap-1.5 text-center">
						<p class="text-[13px] text-[#5f5e60]"><?php esc_html_e( 'New to the Library?', 'digital-library-membership' ); ?></p>
						<button type="button" class="dlm-auth-tab-btn font-semibold text-[14px] text-[#1a1c1c] hover:text-[#855300] transition-colors flex items-center gap-1 group cursor-pointer" data-tab="register">
							<span><?php esc_html_e( 'Register for Membership', 'digital-library-membership' ); ?></span>
							<i class="fa-solid fa-chevron-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
						</button>
					</footer>
				</div>

				<!-- REGISTER PANEL -->
				<div class="dlm-auth-panel" id="panel-register" style="display:none;">
					<header class="mb-6">
						<h1 class="text-[28px] md:text-[34px] font-bold text-[#1a1c1c] mb-2 leading-tight tracking-tight"><?php esc_html_e( 'Create Account', 'digital-library-membership' ); ?></h1>
						<p class="text-[15px] text-[#5f5e60] leading-relaxed"><?php esc_html_e( 'Join our digital library and unlock unlimited access.', 'digital-library-membership' ); ?></p>
					</header>

					<?php if ( $has_social ) : ?>
						<div class="dlm-social-buttons space-y-2.5 mb-5">
							<?php if ( '1' === $enable_google ) : ?>
								<a href="<?php echo esc_url( DLM_Social_Auth::get_auth_url( 'google' ) ); ?>" class="w-full h-12 bg-white hover:bg-gray-50 border border-[#d8c3ad] text-[#1a1c1c] font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-3 shadow-sm hover:shadow hover:border-gray-400 no-underline cursor-pointer">
									<svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
										<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
										<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
										<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
										<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
									</svg>
									<span><?php esc_html_e( 'Continue with Google', 'digital-library-membership' ); ?></span>
								</a>
							<?php endif; ?>

							<?php if ( '1' === $enable_apple ) : ?>
								<a href="<?php echo esc_url( DLM_Social_Auth::get_auth_url( 'apple' ) ); ?>" class="w-full h-12 bg-black hover:bg-neutral-800 text-white font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2.5 shadow-sm hover:shadow no-underline cursor-pointer">
									<i class="fa-brands fa-apple text-lg leading-none"></i>
									<span><?php esc_html_e( 'Continue with Apple', 'digital-library-membership' ); ?></span>
								</a>
							<?php endif; ?>

							<div class="relative flex py-1.5 items-center">
								<div class="flex-grow border-t border-[#d8c3ad]/50"></div>
								<span class="flex-shrink mx-3 text-[11px] uppercase font-bold text-[#867461] tracking-wider"><?php esc_html_e( 'Or with email', 'digital-library-membership' ); ?></span>
								<div class="flex-grow border-t border-[#d8c3ad]/50"></div>
							</div>
						</div>
					<?php endif; ?>

					<form id="dlm-register-form" class="space-y-4">
						<div class="dlm-auth-alert text-xs p-3 rounded-xl mb-3 font-medium" style="display:none;"></div>

						<!-- Display Name Field -->
						<div class="field-group space-y-1.5">
							<label class="text-[11px] font-bold text-[#5f5e60] uppercase tracking-wider block" for="dlm_reg_name"><?php esc_html_e( 'Full Name', 'digital-library-membership' ); ?></label>
							<div class="input-relative-wrapper relative">
								<input class="w-full h-13 px-4 bg-white border border-[#d8c3ad] rounded-xl text-[14px] text-[#1a1c1c] input-focus-ring btn-transition placeholder:text-[#867461]/60" id="dlm_reg_name" name="reg_name" placeholder="e.g. Alex Morgan" required type="text" />
								<i class="fa-regular fa-user input-icon-right absolute right-4 top-1/2 -translate-y-1/2 text-[#867461]"></i>
							</div>
						</div>

						<!-- Email Field -->
						<div class="field-group space-y-1.5">
							<label class="text-[11px] font-bold text-[#5f5e60] uppercase tracking-wider block" for="dlm_reg_email"><?php esc_html_e( 'Email Address', 'digital-library-membership' ); ?></label>
							<div class="input-relative-wrapper relative">
								<input class="w-full h-13 px-4 bg-white border border-[#d8c3ad] rounded-xl text-[14px] text-[#1a1c1c] input-focus-ring btn-transition placeholder:text-[#867461]/60" id="dlm_reg_email" name="reg_email" placeholder="alex@example.com" required type="email" />
								<i class="fa-regular fa-envelope input-icon-right absolute right-4 top-1/2 -translate-y-1/2 text-[#867461]"></i>
							</div>
						</div>

						<!-- Password Field -->
						<div class="field-group space-y-1.5">
							<label class="text-[11px] font-bold text-[#5f5e60] uppercase tracking-wider block" for="dlm_reg_password"><?php esc_html_e( 'Choose Password', 'digital-library-membership' ); ?></label>
							<div class="input-relative-wrapper relative">
								<input class="w-full h-13 px-4 bg-white border border-[#d8c3ad] rounded-xl text-[14px] text-[#1a1c1c] input-focus-ring btn-transition placeholder:text-[#867461]/60" id="dlm_reg_password" name="reg_password" placeholder="Minimum 6 characters" required minlength="6" type="password" />
								<button class="dlm-pwd-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#867461] hover:text-[#855300] transition-colors p-1 cursor-pointer border-none bg-transparent flex items-center justify-center" onclick="togglePasswordVisibility('dlm_reg_password', this)" type="button">
									<i class="fa-solid fa-eye text-[15px]"></i>
								</button>
							</div>
						</div>

						<!-- Google ReCAPTCHA -->
						<?php 
						if ( $recaptcha_site_key && $recaptcha_version === 'v2' ) : ?>
							<div class="g-recaptcha flex justify-center my-3" data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"></div>
						<?php endif; ?>

						<!-- Register Button -->
						<button class="w-full h-13 bg-[#855300] hover:bg-[#613b00] text-white font-semibold text-[15px] rounded-xl btn-transition shadow-md hover:shadow-lg active:scale-[0.98] mt-4 flex items-center justify-center gap-2 cursor-pointer" type="submit">
							<span><?php esc_html_e( 'Register & Auto-Login', 'digital-library-membership' ); ?></span>
							<i class="fa-solid fa-arrow-right text-xs"></i>
						</button>
					</form>

					<!-- Footer Switcher -->
					<footer class="mt-8 pt-6 border-t border-[#d8c3ad]/50 flex flex-col items-center gap-1.5 text-center">
						<p class="text-[13px] text-[#5f5e60]"><?php esc_html_e( 'Already have an account?', 'digital-library-membership' ); ?></p>
						<button type="button" class="dlm-auth-tab-btn font-semibold text-[14px] text-[#1a1c1c] hover:text-[#855300] transition-colors flex items-center gap-1 group cursor-pointer" data-tab="login">
							<span><?php esc_html_e( 'Sign In to Your Account', 'digital-library-membership' ); ?></span>
							<i class="fa-solid fa-chevron-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
						</button>
					</footer>
				</div>
			</div>
			
			<!-- Footer Nav Links -->
			<div class="mt-6 flex justify-center gap-6 opacity-70">
				<?php 
				$privacy_id = get_option( 'dlm_privacy_policy_page_id' );
				$terms_id    = get_option( 'dlm_terms_page_id' );
				if ( $privacy_id ) : ?>
					<a class="text-[11px] font-semibold text-[#5f5e60] hover:text-[#855300] transition-colors uppercase tracking-widest" href="<?php echo esc_url( get_permalink( $privacy_id ) ); ?>" target="_blank">Privacy Policy</a>
				<?php else : ?>
					<a class="text-[11px] font-semibold text-[#5f5e60] hover:text-[#855300] transition-colors uppercase tracking-widest" href="#" onclick="return false;">Privacy Policy</a>
				<?php endif; ?>
				
				<?php if ( $terms_id ) : ?>
					<a class="text-[11px] font-semibold text-[#5f5e60] hover:text-[#855300] transition-colors uppercase tracking-widest" href="<?php echo esc_url( get_permalink( $terms_id ) ); ?>" target="_blank">Terms of Access</a>
				<?php else : ?>
					<a class="text-[11px] font-semibold text-[#5f5e60] hover:text-[#855300] transition-colors uppercase tracking-widest" href="#" onclick="return false;">Terms of Access</a>
				<?php endif; ?>
				
				<a class="text-[11px] font-semibold text-[#5f5e60] hover:text-[#855300] transition-colors uppercase tracking-widest" href="#" onclick="return false;">Contact Support</a>
			</div>
		</div>

		<script>
		function togglePasswordVisibility(inputId, btn) {
			const pwdInput = document.getElementById(inputId);
			if (pwdInput) {
				const icon = btn.querySelector('i') || btn;
				if (pwdInput.type === 'password') {
					pwdInput.type = 'text';
					icon.className = 'fa-solid fa-eye-slash text-[#867461] text-[15px]';
				} else {
					pwdInput.type = 'password';
					icon.className = 'fa-solid fa-eye text-[#867461] text-[15px]';
				}
			}
		}
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX login handler
	 */
	public function ajax_login() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$recaptcha_response = isset( $_POST['recaptcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_response'] ) ) : '';
		if ( ! dlm_verify_recaptcha( $recaptcha_response ) ) {
			wp_send_json_error( array( 'message' => __( 'You failed the Google ReCAPTCHA verification. Please try again.', 'digital-library-membership' ) ) );
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		// Passwords should not be sanitized as it would alter the value
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		if ( empty( $username ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Fields cannot be empty.', 'digital-library-membership' ) ) );
		}

		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => true,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => wp_kses_post( $user->get_error_message() ) ) );
		} else {
			$redirect_post = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
			$referer       = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$checkout_url  = dlm_get_page_url( 'checkout' );

			if ( ! empty( $redirect_post ) && wp_validate_redirect( $redirect_post, false ) ) {
				$redirect = $redirect_post;
			} elseif ( ! empty( $referer ) && strpos( $referer, strtok( $checkout_url, '?' ) ) !== false && wp_validate_redirect( $referer, false ) ) {
				$redirect = $referer;
			} else {
				$redirect = dlm_get_page_url( 'account' );
			}

			wp_send_json_success( array( 'redirect' => $redirect ) );
		}
	}

	/**
	 * AJAX registration handler with auto-login
	 */
	public function ajax_register() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$recaptcha_response = isset( $_POST['recaptcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_response'] ) ) : '';
		if ( ! dlm_verify_recaptcha( $recaptcha_response ) ) {
			wp_send_json_error( array( 'message' => __( 'You failed the Google ReCAPTCHA verification. Please try again.', 'digital-library-membership' ) ) );
		}

		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		// Passwords should not be sanitized as it would alter the value
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		if ( empty( $name ) || empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'All fields are required.', 'digital-library-membership' ) ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'digital-library-membership' ) ) );
		}

		if ( email_exists( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Email address already registered.', 'digital-library-membership' ) ) );
		}

		// Generate unique username from name / email
		$username = sanitize_user( current( explode( '@', $email ) ) );
		if ( username_exists( $username ) ) {
			$username .= '_' . wp_generate_password( 4, false );
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => wp_kses_post( $user_id->get_error_message() ) ) );
		}

		// Update Display Name and add subscriber roles
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => $name,
		) );

		$user = new WP_User( $user_id );
		$user->set_role( 'customer' );

		// Clear dashboard transients to show the new user immediately
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		// Send registration email
		$subject = __( 'Welcome to the Digital Library!', 'digital-library-membership' );
		/* translators: 1: User name, 2: Username, 3: Login page URL */
		$body    = sprintf(
			__( "Hello %1\$s,\n\nThank you for registering at the Digital Library! Your account is active and you can now log in.\n\nUsername: %2\$s\nLogin Page: %3\$s\n\nEnjoy reading our premium digital books.\n\nBest regards,\nDigital Library Team", 'digital-library-membership' ),
			$name,
			$username,
			dlm_get_page_url( 'account' )
		);
		wp_mail( $email, $subject, $body );

		// Sign in automatically
		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => true,
		);
		wp_signon( $creds, is_ssl() );

		$redirect_post = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		$referer       = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$checkout_url  = dlm_get_page_url( 'checkout' );

		if ( ! empty( $redirect_post ) && wp_validate_redirect( $redirect_post, false ) ) {
			$redirect = $redirect_post;
		} elseif ( ! empty( $referer ) && strpos( $referer, strtok( $checkout_url, '?' ) ) !== false && wp_validate_redirect( $referer, false ) ) {
			$redirect = $referer;
		} else {
			$redirect = dlm_get_page_url( 'account' );
		}

		wp_send_json_success( array( 'redirect' => $redirect ) );
	}

	/**
	 * AJAX sync achievements state
	 */
	public function ajax_sync_achievements() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$state_json = isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		if ( empty( $state_json ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid state data.', 'digital-library-membership' ) ) );
		}

		$state = json_decode( $state_json, true );
		if ( ! is_array( $state ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid state format.', 'digital-library-membership' ) ) );
		}

		// Sanitize achievements state fields
		$sanitized_state = array(
			'streak'           => isset( $state['streak'] ) ? intval( $state['streak'] ) : 0,
			'lastVisit'        => isset( $state['lastVisit'] ) ? sanitize_text_field( $state['lastVisit'] ) : null,
			'xp'               => isset( $state['xp'] ) ? intval( $state['xp'] ) : 0,
			'level'            => isset( $state['level'] ) ? intval( $state['level'] ) : 1,
			'booksOpened'      => isset( $state['booksOpened'] ) ? intval( $state['booksOpened'] ) : 0,
			'badges'           => array(),
			'goalMinutesToday' => isset( $state['goalMinutesToday'] ) ? intval( $state['goalMinutesToday'] ) : 0,
			'dailyGoal'        => isset( $state['dailyGoal'] ) ? intval( $state['dailyGoal'] ) : 20,
		);

		if ( isset( $state['badges'] ) && is_array( $state['badges'] ) ) {
			foreach ( $state['badges'] as $badge ) {
				if ( isset( $badge['id'] ) ) {
					$sanitized_state['badges'][] = array(
						'id'     => sanitize_key( $badge['id'] ),
						'label'  => isset( $badge['label'] ) ? sanitize_text_field( $badge['label'] ) : '',
						'earned' => isset( $badge['earned'] ) ? sanitize_text_field( $badge['earned'] ) : '',
					);
				}
			}
		}

		// Fetch previous state to detect newly unlocked events
		$old_ach_raw = get_user_meta( $user_id, 'dlm_achievements_state', true );
		$old_ach     = $old_ach_raw ? json_decode( $old_ach_raw, true ) : array();
		$old_level   = isset( $old_ach['level'] ) ? intval( $old_ach['level'] ) : 1;
		$old_badges  = array();
		if ( isset( $old_ach['badges'] ) && is_array( $old_ach['badges'] ) ) {
			foreach ( $old_ach['badges'] as $ob ) {
				if ( isset( $ob['id'] ) ) {
					$old_badges[] = $ob['id'];
				}
			}
		}

		// Trigger notifications for newly earned badges
		if ( ! empty( $sanitized_state['badges'] ) ) {
			foreach ( $sanitized_state['badges'] as $nb ) {
				if ( ! in_array( $nb['id'], $old_badges, true ) ) {
					$badge_label = ! empty( $nb['label'] ) ? $nb['label'] : $nb['id'];
					if ( ! $this->db->notification_exists( $user_id, 'badge', $badge_label ) ) {
						$this->db->create_notification( array(
							'user_id'   => $user_id,
							'type'      => 'badge',
							'title'     => sprintf( __( 'Badge Unlocked: %s', 'digital-library-membership' ), $badge_label ),
							'message'   => sprintf( __( 'Congratulations! You unlocked the "%s" milestone achievement badge.', 'digital-library-membership' ), $badge_label ),
							'link_url'  => '#achievements',
						) );
					}
				}
			}
		}

		// Trigger notification for level up
		if ( $sanitized_state['level'] > $old_level ) {
			$level_title = sprintf( __( 'Level Up: Level %d!', 'digital-library-membership' ), $sanitized_state['level'] );
			if ( ! $this->db->notification_exists( $user_id, 'level_up', 'Level ' . $sanitized_state['level'] ) ) {
				$this->db->create_notification( array(
					'user_id'   => $user_id,
					'type'      => 'level_up',
					'title'     => $level_title,
					'message'   => sprintf( __( 'You reached Level %d. Keep reading to earn more XP and unlock rewards!', 'digital-library-membership' ), $sanitized_state['level'] ),
					'link_url'  => '#achievements',
				) );
			}
		}

		// Trigger notification for streak milestones (3, 7, 14, 30, 60, 100 days)
		if ( in_array( $sanitized_state['streak'], array( 3, 7, 14, 30, 60, 100 ), true ) ) {
			$streak_title = sprintf( __( '%d-Day Reading Streak!', 'digital-library-membership' ), $sanitized_state['streak'] );
			$since_30d    = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
			if ( ! $this->db->notification_exists( $user_id, 'streak', $sanitized_state['streak'] . '-Day', $since_30d ) ) {
				$this->db->create_notification( array(
					'user_id'   => $user_id,
					'type'      => 'streak',
					'title'     => $streak_title,
					'message'   => sprintf( __( 'Incredible dedication! You have read for %d consecutive days. Keep the streak alive!', 'digital-library-membership' ), $sanitized_state['streak'] ),
					'link_url'  => '#achievements',
				) );
			}
		}

		update_user_meta( $user_id, 'dlm_achievements_state', json_encode( $sanitized_state ) );
		wp_send_json_success( array( 'message' => __( 'State synced successfully.', 'digital-library-membership' ) ) );
	}

	/**
	 * AJAX manage reading journal notes
	 */
	public function ajax_manage_journal_notes() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$note_action = isset( $_POST['note_action'] ) ? sanitize_key( $_POST['note_action'] ) : '';
		
		$notes_raw = get_user_meta( $user_id, 'dlm_journal_notes', true );
		$notes = $notes_raw ? json_decode( $notes_raw, true ) : array();
		if ( ! is_array( $notes ) ) {
			$notes = array();
		}

		if ( $note_action === 'add' ) {
			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$chapter = isset( $_POST['chapter'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter'] ) ) : '';
			$content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
			$tag = isset( $_POST['tag'] ) ? sanitize_text_field( wp_unslash( $_POST['tag'] ) ) : 'General';

			if ( empty( $title ) || empty( $content ) ) {
				wp_send_json_error( array( 'message' => __( 'Title and note content are required.', 'digital-library-membership' ) ) );
			}

			// Estimate read time (approx. 200 words per minute)
			$word_count = str_word_count( wp_strip_all_tags( $content ) );
			$read_time = max( 1, ceil( $word_count / 200 ) );

			$new_note = array(
				'id'       => wp_generate_password( 8, false ),
				'date'     => date_i18n( 'M d, Y' ),
				'title'    => $title,
				'chapter'  => $chapter,
				'content'  => $content,
				'tag'      => $tag,
				// translators: %d is the note reading time in minutes
				'readTime' => sprintf( _n( '%d min read', '%d min read', $read_time, 'digital-library-membership' ), $read_time ),
			);

			$notes[] = $new_note;
			update_user_meta( $user_id, 'dlm_journal_notes', json_encode( $notes ) );
			wp_send_json_success( array( 'notes' => $notes, 'added_note' => $new_note ) );

		} elseif ( $note_action === 'edit' ) {
			$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$chapter = isset( $_POST['chapter'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter'] ) ) : '';
			$content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
			$tag = isset( $_POST['tag'] ) ? sanitize_text_field( wp_unslash( $_POST['tag'] ) ) : 'General';

			if ( empty( $id ) || empty( $title ) || empty( $content ) ) {
				wp_send_json_error( array( 'message' => __( 'Missing fields for note update.', 'digital-library-membership' ) ) );
			}

			$found = false;
			foreach ( $notes as &$note ) {
				if ( $note['id'] === $id ) {
					$note['title'] = $title;
					$note['chapter'] = $chapter;
					$note['content'] = $content;
					$note['tag'] = $tag;
					
					$word_count = str_word_count( wp_strip_all_tags( $content ) );
					$read_time = max( 1, ceil( $word_count / 200 ) );
					// translators: %d is the note reading time in minutes
					$note['readTime'] = sprintf( _n( '%d min read', '%d min read', $read_time, 'digital-library-membership' ), $read_time );
					
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				wp_send_json_error( array( 'message' => __( 'Note not found.', 'digital-library-membership' ) ) );
			}

			update_user_meta( $user_id, 'dlm_journal_notes', json_encode( $notes ) );
			wp_send_json_success( array( 'notes' => $notes ) );

		} elseif ( $note_action === 'delete' ) {
			$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
			if ( empty( $id ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid note ID.', 'digital-library-membership' ) ) );
			}

			$filtered_notes = array();
			foreach ( $notes as $note ) {
				if ( $note['id'] !== $id ) {
					$filtered_notes[] = $note;
				}
			}

			update_user_meta( $user_id, 'dlm_journal_notes', json_encode( $filtered_notes ) );
			wp_send_json_success( array( 'notes' => $filtered_notes ) );
		}

		wp_send_json_error( array( 'message' => __( 'Invalid note action.', 'digital-library-membership' ) ) );
	}

	/**
	 * AJAX update user profile (display name, email, password)
	 */
	public function ajax_update_profile() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$new_password     = isset( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$current_password = isset( $_POST['current_password'] ) ? wp_unslash( $_POST['current_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $display_name ) || empty( $user_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Display name and email are required.', 'digital-library-membership' ) ) );
		}

		if ( ! is_email( $user_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'digital-library-membership' ) ) );
		}

		// Check if email belongs to someone else
		$existing_user = get_user_by( 'email', $user_email );
		if ( $existing_user && $existing_user->ID !== $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Email address is already in use.', 'digital-library-membership' ) ) );
		}

		$userdata = array(
			'ID'           => $user_id,
			'display_name' => $display_name,
			'user_email'   => $user_email,
		);

		$password_changed = false;
		if ( ! empty( $new_password ) ) {
			if ( empty( $current_password ) ) {
				wp_send_json_error( array( 'message' => __( 'Current password is required to change your password.', 'digital-library-membership' ) ) );
			}

			$user = get_user_by( 'id', $user_id );
			if ( ! $user || ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
				wp_send_json_error( array( 'message' => __( 'Incorrect current password.', 'digital-library-membership' ) ) );
			}

			if ( strlen( $new_password ) < 6 ) {
				wp_send_json_error( array( 'message' => __( 'New password must be at least 6 characters.', 'digital-library-membership' ) ) );
			}
			$userdata['user_pass'] = $new_password;
			$password_changed = true;
		}

		$updated_user_id = wp_update_user( $userdata );
		if ( is_wp_error( $updated_user_id ) ) {
			wp_send_json_error( array( 'message' => $updated_user_id->get_error_message() ) );
		}

		if ( $password_changed ) {
			wp_mail(
				$user_email,
				__( 'Your password was changed', 'digital-library-membership' ),
				__( "Hello,\n\nYour Digital Library account password was successfully updated. If you did not make this change, please contact support immediately.\n\nBest regards,\nDigital Library Team", 'digital-library-membership' )
			);
		}

		wp_send_json_success( array( 'message' => __( 'Profile updated successfully.', 'digital-library-membership' ) ) );
	}

	/**
	 * AJAX upload user profile avatar
	 */
	public function ajax_upload_avatar() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();

		if ( ! empty( $_FILES['avatar'] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			$attachment_id = media_handle_upload( 'avatar', 0 );
			if ( ! is_wp_error( $attachment_id ) ) {
				$avatar_url = wp_get_attachment_url( $attachment_id );
				update_user_meta( $user_id, 'dlm_avatar_url', $avatar_url );
				wp_send_json_success( array( 
					'message'    => __( 'Avatar uploaded successfully.', 'digital-library-membership' ),
					'avatar_url' => $avatar_url 
				) );
			} else {
				wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
			}
		}

		wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'digital-library-membership' ) ) );
	}

	/**
	 * AJAX toggle book favorite status
	 */
	public function ajax_toggle_favorite() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
		if ( ! $book_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid book ID.', 'digital-library-membership' ) ) );
		}

		$fav_books_raw = get_user_meta( $user_id, 'dlm_favorite_books', true );
		$fav_books = $fav_books_raw ? json_decode( $fav_books_raw, true ) : array();
		if ( ! is_array( $fav_books ) ) {
			$fav_books = array();
		}

		if ( in_array( $book_id, $fav_books, true ) ) {
			$fav_books = array_values( array_diff( $fav_books, array( $book_id ) ) );
			$is_fav = false;
		} else {
			$fav_books[] = $book_id;
			$is_fav = true;
		}

		update_user_meta( $user_id, 'dlm_favorite_books', json_encode( $fav_books ) );
		wp_send_json_success( array( 
			'is_favorite' => $is_fav, 
			'favorites'   => $fav_books 
		) );
	}

	/**
	 * AJAX dynamic cache-safe user access resolution for Featured Books
	 */
	public function ajax_get_featured_access() {
		// Nonce check: verify dlm_public_nonce or dlm_nonce with soft-fallback for cached pages
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'dlm_public_nonce' ) && ! wp_verify_nonce( $nonce, 'dlm_nonce' ) ) {
			// If invalid nonce was supplied explicitly, return json error
			wp_send_json_error( array( 'message' => __( 'Security check failed. Invalid nonce.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$is_logged = is_user_logged_in();
		
		// Sanitize array of book IDs to safe positive integers
		$raw_ids = isset( $_POST['book_ids'] ) ? (array) $_POST['book_ids'] : array();
		$book_ids = array_filter( array_map( 'intval', $raw_ids ) );

		$currency = get_option( 'dlm_currency', 'USD' );
		$pricing_url = get_permalink( get_option( 'dlm_pricing_page_id' ) );
		if ( ! $pricing_url ) {
			$pricing_url = home_url( '/pricing/' );
		}
		$account_url = dlm_get_page_url( 'account' );

		$fav_books = array();
		if ( $is_logged ) {
			$fav_books_raw = get_user_meta( $user_id, 'dlm_favorite_books', true );
			$fav_books = $fav_books_raw ? json_decode( $fav_books_raw, true ) : array();
			if ( ! is_array( $fav_books ) ) {
				$fav_books = array();
			}
		}

		$results = array();

		foreach ( $book_ids as $bid ) {
			if ( ! $bid || $bid <= 0 ) continue;
			$book = $this->db->get_book( $bid );
			if ( ! $book ) continue;

			$access_status = dlm_user_can_access_book( $user_id, $bid );
			$is_future = ! empty( $book->publish_date ) && ( strtotime( $book->publish_date ) > current_time( 'timestamp' ) );
			$price = isset( $book->price ) ? floatval( $book->price ) : 0.00;
			$publish_iso = ! empty( $book->publish_date ) ? wp_date( 'c', strtotime( $book->publish_date ) ) : '';
			if ( empty( $publish_iso ) && ! empty( $book->publish_date ) ) {
				$publish_iso = str_replace( ' ', 'T', trim( $book->publish_date ) );
			}

			// Format default dynamic button label based on live user access
			$default_btn_label = '';
			$target_url = home_url( '/read/' . $bid . '/' );

			if ( $access_status === 'read_download' || $access_status === 'read_only' ) {
				$default_btn_label = __( 'Read Now', 'digital-library-membership' );
				$target_url = home_url( '/read/' . $bid . '/' );
			} elseif ( $book->access_type === 'purchase_only' || $book->access_type === 'hybrid' ) {
				$default_btn_label = sprintf( __( 'Buy Book (%s)', 'digital-library-membership' ), number_format( $price, 2 ) . ' ' . $currency );
				$target_url = home_url( '/read/' . $bid . '/' );
			} elseif ( ! $is_logged ) {
				$default_btn_label = __( 'Sign In to Read', 'digital-library-membership' );
				$target_url = $account_url;
			} else {
				$default_btn_label = __( 'Unlock Membership', 'digital-library-membership' );
				$target_url = $pricing_url;
			}

			$results[ $bid ] = array(
				'id'                => $bid,
				'access'            => $access_status, // 'read_download', 'read_only', 'locked'
				'access_type'       => $book->access_type,
				'is_future'         => $is_future,
				'publish_date'      => $book->publish_date,
				'publish_iso'       => $publish_iso,
				'price'             => $price,
				'price_formatted'   => number_format( $price, 2 ) . ' ' . $currency,
				'reader_url'        => home_url( '/read/' . $bid . '/' ),
				'target_url'        => $target_url,
				'btn1_label'        => ! empty( $book->featured_button_1_label ) ? $book->featured_button_1_label : $default_btn_label,
				'btn2_label'        => ! empty( $book->featured_button_2_label ) ? $book->featured_button_2_label : '',
				'is_favorite'       => in_array( $bid, $fav_books, true ),
			);
		}

		wp_send_json_success( array(
			'is_logged_in' => $is_logged,
			'user_id'      => $user_id,
			'pricing_url'  => $pricing_url,
			'account_url'  => $account_url,
			'access_map'   => $results,
		) );
	}

	/**
	 * AJAX fetch latest notifications and unread count
	 */
	public function ajax_get_notifications() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id       = get_current_user_id();
		$limit         = isset( $_POST['limit'] ) ? max( 1, min( 50, intval( $_POST['limit'] ) ) ) : 20;
		$raw_notifs    = $this->db->get_user_notifications( $user_id, $limit );
		$unread_count  = $this->db->get_unread_notifications_count( $user_id );
		$notifications = array();

		if ( ! empty( $raw_notifs ) ) {
			foreach ( $raw_notifs as $n ) {
				$time_diff = human_time_diff( strtotime( $n->created_at ), current_time( 'timestamp' ) );
				$notifications[] = array(
					'id'            => intval( $n->id ),
					'type'          => $n->type,
					'title'         => $n->title,
					'message'       => $n->message,
					'link_url'      => $n->link_url,
					'is_read'       => intval( $n->is_read ),
					'created_at'    => $n->created_at,
					'time_relative' => sprintf(
						/* translators: %s: Human-readable time difference (e.g. 5 mins) */
						__( '%s ago', 'digital-library-membership' ),
						$time_diff
					),
				);
			}
		}

		wp_send_json_success( array(
			'notifications' => $notifications,
			'unread_count'  => $unread_count,
		) );
	}

	/**
	 * AJAX mark a single notification as read
	 */
	public function ajax_mark_notification_read() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id         = get_current_user_id();
		$notification_id = isset( $_POST['notification_id'] ) ? intval( $_POST['notification_id'] ) : 0;

		if ( ! $notification_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notification ID.', 'digital-library-membership' ) ) );
		}

		$this->db->mark_notification_read( $notification_id, $user_id );
		$unread_count = $this->db->get_unread_notifications_count( $user_id );

		wp_send_json_success( array(
			'notification_id' => $notification_id,
			'unread_count'    => $unread_count,
		) );
	}

	/**
	 * AJAX mark all notifications as read for current user
	 */
	public function ajax_mark_all_notifications_read() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$this->db->mark_all_notifications_read( $user_id );

		wp_send_json_success( array(
			'message'      => __( 'All notifications marked as read.', 'digital-library-membership' ),
			'unread_count' => 0,
		) );
	}

	/**
	 * AJAX get lightweight unread count for periodic polling
	 */
	public function ajax_get_unread_notifications_count() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id      = get_current_user_id();
		$unread_count = $this->db->get_unread_notifications_count( $user_id );

		wp_send_json_success( array(
			'unread_count' => $unread_count,
		) );
	}

	/**
	 * AJAX update member onboarding tour completion/skip/reset state
	 */
	public function ajax_update_onboarding_status() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();
		$status  = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';

		if ( $status === 'completed' ) {
			update_user_meta( $user_id, 'dlm_onboarding_completed', 'yes' );
			update_user_meta( $user_id, 'dlm_onboarding_completed_at', current_time( 'mysql' ) );
			wp_send_json_success( array(
				'onboarding_completed' => 'yes',
				'message'              => __( 'Onboarding tour completed.', 'digital-library-membership' ),
			) );
		} elseif ( $status === 'skipped' ) {
			update_user_meta( $user_id, 'dlm_onboarding_completed', 'yes' );
			update_user_meta( $user_id, 'dlm_onboarding_skipped_at', current_time( 'mysql' ) );
			wp_send_json_success( array(
				'onboarding_completed' => 'yes',
				'message'              => __( 'Onboarding tour skipped.', 'digital-library-membership' ),
			) );
		} elseif ( $status === 'reset' ) {
			update_user_meta( $user_id, 'dlm_onboarding_completed', 'no' );
			delete_user_meta( $user_id, 'dlm_onboarding_completed_at' );
			delete_user_meta( $user_id, 'dlm_onboarding_skipped_at' );
			wp_send_json_success( array(
				'onboarding_completed' => 'no',
				'message'              => __( 'Onboarding tour reset for replay.', 'digital-library-membership' ),
			) );
		}

		wp_send_json_error( array( 'message' => __( 'Invalid onboarding status.', 'digital-library-membership' ) ) );
	}
}


