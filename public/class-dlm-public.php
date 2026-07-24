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

		$raw_books = $this->db->get_books( 'publish' );
		$books_data = array();
		$categories_set = array();

		if ( ! empty( $raw_books ) ) {
			foreach ( $raw_books as $b ) {
				$progress         = ( $is_logged_in && $user_id ) ? $this->db->get_reading_progress( $user_id, $b->id ) : null;
				$progress_percent = $progress ? intval( $progress->progress_percent ) : 0;
				$category         = ! empty( $b->category ) ? $b->category : __( 'General', 'digital-library-membership' );

				if ( ! in_array( $category, $categories_set, true ) ) {
					$categories_set[] = $category;
				}

				$books_data[] = array(
					'id'       => $b->id,
					'title'    => $b->title,
					'author'   => $b->author,
					'category' => $category,
					'progress' => $progress_percent,
					'cover'    => ! empty( $b->cover_image_url ) ? $b->cover_image_url : '',
					'date'     => ! empty( $b->created_at ) ? $b->created_at : date( 'Y-m-d' ),
					'read_url' => home_url( '/read/' . $b->id . '/' ),
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
		<div class="dlm-container max-w-[1440px] mx-auto px-margin-mobile md:px-margin-desktop py-4">
			<!-- Header / Status Bar -->
			<header class="dlm-library-header flex justify-between items-center mb-8 pb-4 border-b border-outline-muted">
				<div>
					<h1 class="font-display-lg text-2xl md:text-3xl font-extrabold tracking-tight text-on-surface serif-title m-0"><?php esc_html_e( 'Digital Library Catalog', 'digital-library-membership' ); ?></h1>
					<p class="text-xs md:text-sm text-secondary mt-1"><?php esc_html_e( 'Explore our collection of digital manuscripts and books.', 'digital-library-membership' ); ?></p>
				</div>
				<div class="flex items-center gap-3">
					<?php if ( $is_active ) : ?>
						<span class="dlm-status-badge active"><?php esc_html_e( 'Active Member', 'digital-library-membership' ); ?></span>
					<?php elseif ( $is_logged_in ) : ?>
						<span class="dlm-status-badge inactive"><?php esc_html_e( 'No Subscription', 'digital-library-membership' ); ?></span>
						<a href="<?php echo esc_url( $pricing_url ); ?>" class="dlm-btn dlm-btn-primary dlm-btn-sm"><?php esc_html_e( 'Join Membership', 'digital-library-membership' ); ?></a>
					<?php else : ?>
						<span class="dlm-status-badge guest"><?php esc_html_e( 'Guest Preview', 'digital-library-membership' ); ?></span>
						<a href="<?php echo esc_url( $pricing_url ); ?>" class="dlm-btn dlm-btn-primary dlm-btn-sm"><?php esc_html_e( 'View Plans', 'digital-library-membership' ); ?></a>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( ! $is_logged_in ) : ?>
				<div class="dlm-msg-box info flex justify-between items-center flex-wrap gap-4 mb-8">
					<div>
						<strong class="font-bold text-on-surface"><?php esc_html_e( 'Welcome to our Digital Library!', 'digital-library-membership' ); ?></strong>
						<p class="text-xs text-secondary mt-0.5"><?php esc_html_e( 'Browse our catalog below. Sign up or log in to unlock full reading access.', 'digital-library-membership' ); ?></p>
					</div>
					<a href="<?php echo esc_url( $pricing_url ); ?>" class="dlm-btn dlm-btn-accent dlm-btn-sm"><?php esc_html_e( 'Get Membership Access', 'digital-library-membership' ); ?></a>
				</div>
			<?php endif; ?>

			<!-- Filters & Controls Bar -->
			<div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
				<!-- Category Filter Pills -->
				<div id="category-filters" class="flex items-center gap-3 overflow-x-auto scrollbar-hide pb-2 md:pb-0">
					<button data-category="All" class="filter-btn active px-6 py-2 bg-primary text-white font-bold rounded-full whitespace-nowrap transition-all shadow-sm"><?php esc_html_e( 'All Books', 'digital-library-membership' ); ?></button>
					<?php foreach ( $categories_set as $cat ) : ?>
						<button data-category="<?php echo esc_attr( $cat ); ?>" class="filter-btn px-6 py-2 bg-surface-container text-secondary font-bold rounded-full hover:bg-surface-variant transition-all whitespace-nowrap"><?php echo esc_html( $cat ); ?></button>
					<?php endforeach; ?>
				</div>

				<!-- Search Input & Sort Dropdown -->
				<div class="relative flex items-center gap-4 text-secondary flex-wrap">
					<!-- Search Input -->
					<div class="relative min-w-[240px]">
						<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-[20px]">search</span>
						<input id="search-input" class="w-full pl-10 pr-4 py-2 bg-surface-container rounded-full border-none focus:ring-2 focus:ring-primary text-body-md placeholder:text-secondary transition-all duration-300" placeholder="<?php esc_attr_e( 'Search title or author...', 'digital-library-membership' ); ?>" type="text">
					</div>

					<!-- Sort Selector -->
					<div class="relative flex items-center gap-2 cursor-pointer select-none" id="sort-trigger">
						<span class="text-label-caps uppercase select-none"><?php esc_html_e( 'Sorted by:', 'digital-library-membership' ); ?> <span id="current-sort-label" class="text-on-surface font-bold"><?php esc_html_e( 'Recent', 'digital-library-membership' ); ?></span></span>
						<button class="p-2 hover:bg-surface-variant rounded-lg transition-colors" type="button">
							<span class="material-symbols-outlined">filter_list</span>
						</button>

						<!-- Dropdown Menu -->
						<div id="sort-dropdown" class="hidden absolute right-0 top-12 w-48 bg-white border border-outline-variant/30 rounded-2xl shadow-xl z-50 py-2 animate-fade-in">
							<button data-sort="recent" class="sort-opt w-full text-left px-4 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container flex items-center justify-between" type="button">
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
				<!-- Rendered via dlm-public.js -->
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

				<div class="flex gap-6 mb-6">
					<img id="modal-cover" class="w-24 h-36 object-cover rounded-xl shadow-md border border-outline-variant/30" src="" alt="Book cover">
					<div class="flex-1 space-y-2 pt-2">
						<span id="modal-category" class="text-label-micro text-primary font-bold uppercase tracking-wider"></span>
						<h3 id="modal-title" class="font-title-sm text-on-surface serif-title text-xl font-bold"></h3>
						<p id="modal-author" class="text-sm text-secondary"></p>

						<div class="pt-3">
							<div class="flex justify-between text-xs font-semibold mb-1">
								<span class="text-secondary"><?php esc_html_e( 'Current Progress', 'digital-library-membership' ); ?></span>
								<span id="modal-progress-text" class="text-primary font-bold">0%</span>
							</div>
							<div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
								<div id="modal-progress-bar" class="h-full bg-surface-amber transition-all duration-500" style="width: 0%;"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="space-y-4 pt-2 border-t border-outline-muted">
					<p class="text-xs text-secondary leading-relaxed">
						<?php esc_html_e( 'You are opening this digital manuscript. Continue your reading session or update your progress below.', 'digital-library-membership' ); ?>
					</p>

					<div class="flex gap-3">
						<button id="modal-read-now-btn" class="flex-1 py-3.5 bg-primary text-white font-bold rounded-full hover:opacity-90 transition-all shadow-md" type="button">
							<?php esc_html_e( 'Start Reading', 'digital-library-membership' ); ?>
						</button>
						<button id="modal-mark-complete-btn" class="px-6 py-3.5 bg-surface-container text-on-surface font-bold rounded-full hover:bg-surface-variant transition-all" type="button">
							<?php esc_html_e( '+15% Progress', 'digital-library-membership' ); ?>
						</button>
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

		$monthly_price  = get_option( 'dlm_pricing_monthly', '9.99' );
		$yearly_price   = get_option( 'dlm_pricing_yearly', '99.99' );
		$lifetime_price = get_option( 'dlm_pricing_lifetime', '199.99' );
		$currency       = get_option( 'dlm_currency', 'USD' );

		// Fetch configured or default benefits lists
		$features_monthly_raw = get_option( 'dlm_features_monthly', '' );
		if ( ! empty( $features_monthly_raw ) ) {
			$features_monthly = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $features_monthly_raw ) ) ) );
		} else {
			$features_monthly = array(
				__( 'Unlimited frontend reading access', 'digital-library-membership' ),
				__( 'Physical book-like page turn reader', 'digital-library-membership' ),
				__( 'High-resolution rendering canvas', 'digital-library-membership' ),
				__( 'Saves reading bookmarks automatically', 'digital-library-membership' ),
				__( 'Cancel online anytime', 'digital-library-membership' ),
			);
		}

		$features_yearly_raw = get_option( 'dlm_features_yearly', '' );
		if ( ! empty( $features_yearly_raw ) ) {
			$features_yearly = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $features_yearly_raw ) ) ) );
		} else {
			$features_yearly = array(
				__( 'Everything in Monthly plan included', 'digital-library-membership' ),
				__( 'Locked in low pricing for 1 year', 'digital-library-membership' ),
				__( 'Priority customer support access', 'digital-library-membership' ),
				__( 'No price increases during billing year', 'digital-library-membership' ),
			);
		}

		$features_lifetime_raw = get_option( 'dlm_features_lifetime', '' );
		if ( ! empty( $features_lifetime_raw ) ) {
			$features_lifetime = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $features_lifetime_raw ) ) ) );
		} else {
			$features_lifetime = array(
				__( 'Everything in Monthly plan included', 'digital-library-membership' ),
				__( 'No recurring bills or subscription fees', 'digital-library-membership' ),
				__( 'Permanent lifetime access to library', 'digital-library-membership' ),
				__( 'Free updates to reader & future uploads', 'digital-library-membership' ),
			);
		}

		$checkout_url = dlm_get_page_url( 'checkout' );

		ob_start();
		?>
		<div class="dlm-pricing-container dlm-container">
			<h1 class="dlm-checkout-title"><?php esc_html_e( 'Choose Your Plan', 'digital-library-membership' ); ?></h1>
			<p class="dlm-checkout-subtitle"><?php esc_html_e( 'Get instant, unlimited access to our entire library of digital books. Cancel anytime.', 'digital-library-membership' ); ?></p>

			<?php if ( $is_active ) : ?>
				<div class="dlm-msg-box success" style="background:#e6f4ea; border:1px solid #ceead6; color:#137333; padding:15px 20px; border-radius:12px; margin-bottom:30px; text-align:center;">
					<p style="margin:0; font-size:15px;">
						<?php esc_html_e( 'You already have an active membership subscription!', 'digital-library-membership' ); ?>
						<a href="<?php echo esc_url( dlm_get_page_url( 'account' ) ); ?>" style="font-weight:700; text-decoration:underline; margin-left:8px; color:#137333;"><?php esc_html_e( 'Manage Account', 'digital-library-membership' ); ?></a>
					</p>
				</div>
			<?php endif; ?>

			<div class="dlm-pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
				<!-- Monthly Plan -->
				<div class="dlm-price-card" id="card-monthly">
					<div class="dlm-price-header">
						<h3><?php esc_html_e( 'Monthly Membership', 'digital-library-membership' ); ?></h3>
						<div class="dlm-price">
							<span class="currency">$</span>
							<span class="amount"><?php echo esc_html( $monthly_price ); ?></span>
							<span class="period">/<?php esc_html_e( 'mo', 'digital-library-membership' ); ?></span>
						</div>
					</div>
					<ul class="dlm-features-list">
						<?php foreach ( $features_monthly as $feat ) : ?>
							<li><?php echo esc_html( $feat ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url( add_query_arg( 'plan', 'monthly', $checkout_url ) ); ?>" class="dlm-btn dlm-btn-secondary dlm-btn-block" style="text-decoration:none; text-align:center; display:block; box-sizing:border-box;"><?php esc_html_e( 'Subscribe Monthly', 'digital-library-membership' ); ?></a>
				</div>

				<!-- Yearly Plan -->
				<div class="dlm-price-card popular" id="card-yearly">
					<div class="dlm-popular-badge"><?php esc_html_e( 'Best Value (Save ~20%)', 'digital-library-membership' ); ?></div>
					<div class="dlm-price-header">
						<h3><?php esc_html_e( 'Yearly Membership', 'digital-library-membership' ); ?></h3>
						<div class="dlm-price">
							<span class="currency">$</span>
							<span class="amount"><?php echo esc_html( $yearly_price ); ?></span>
							<span class="period">/<?php esc_html_e( 'yr', 'digital-library-membership' ); ?></span>
						</div>
					</div>
					<ul class="dlm-features-list">
						<?php foreach ( $features_yearly as $feat ) : ?>
							<li><?php echo esc_html( $feat ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url( add_query_arg( 'plan', 'yearly', $checkout_url ) ); ?>" class="dlm-btn dlm-btn-accent dlm-btn-block" style="text-decoration:none; text-align:center; display:block; box-sizing:border-box;"><?php esc_html_e( 'Subscribe Yearly', 'digital-library-membership' ); ?></a>
				</div>

				<!-- Lifetime Plan -->
				<div class="dlm-price-card" id="card-lifetime">
					<div class="dlm-price-header">
						<h3><?php esc_html_e( 'Lifetime Access', 'digital-library-membership' ); ?></h3>
						<div class="dlm-price">
							<span class="currency">$</span>
							<span class="amount"><?php echo esc_html( $lifetime_price ); ?></span>
							<span class="period">/<?php esc_html_e( 'one-time', 'digital-library-membership' ); ?></span>
						</div>
					</div>
					<ul class="dlm-features-list">
						<?php foreach ( $features_lifetime as $feat ) : ?>
							<li><?php echo esc_html( $feat ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url( add_query_arg( 'plan', 'lifetime', $checkout_url ) ); ?>" class="dlm-btn dlm-btn-secondary dlm-btn-block" style="text-decoration:none; text-align:center; display:block; box-sizing:border-box;"><?php esc_html_e( 'Buy Lifetime Access', 'digital-library-membership' ); ?></a>
				</div>
			</div>
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
		if ( ! in_array( $selected_plan, array( 'monthly', 'yearly', 'lifetime' ), true ) ) {
			$selected_plan = 'monthly';
		}

		$monthly_price  = get_option( 'dlm_pricing_monthly', '9.99' );
		$yearly_price   = get_option( 'dlm_pricing_yearly', '99.99' );
		$lifetime_price = get_option( 'dlm_pricing_lifetime', '199.99' );
		$currency       = get_option( 'dlm_currency', 'USD' );

		$plan_name   = __( 'Monthly Membership', 'digital-library-membership' );
		$plan_price  = $monthly_price;
		$plan_period = __( '/mo', 'digital-library-membership' );

		if ( $selected_plan === 'yearly' ) {
			$plan_name   = __( 'Yearly Membership', 'digital-library-membership' );
			$plan_price  = $yearly_price;
			$plan_period = __( '/yr', 'digital-library-membership' );
		} elseif ( $selected_plan === 'lifetime' ) {
			$plan_name   = __( 'Lifetime Access', 'digital-library-membership' );
			$plan_price  = $lifetime_price;
			$plan_period = __( '/one-time', 'digital-library-membership' );
		}

		$user_id   = get_current_user_id();
		$sub       = $user_id ? $this->db->get_subscription_by_user( $user_id ) : null;
		$is_active = $user_id ? $this->db->has_active_membership( $user_id ) : false;

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
			$wc_product_id = intval( get_option( 'dlm_wc_' . $selected_plan . '_product' ) );
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
				<?php echo $this->get_login_prompt_html(); ?>
			<?php else : ?>
				<!-- Payment Methods Container for Logged-In Users -->
				<div class="dlm-payment-box" style="background:#fff; border:1px solid #d2d2d7; border-radius:20px; padding:30px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
					<h3 style="margin-top:0; margin-bottom:20px; font-size:18px; font-weight:700; color:#1d1d1f;"><?php esc_html_e( 'Select Payment Method', 'digital-library-membership' ); ?></h3>

					<!-- Google ReCAPTCHA Bot Protection -->
					<?php 
					$recaptcha_site_key = get_option( 'dlm_recaptcha_site_key' );
					$recaptcha_version  = get_option( 'dlm_recaptcha_version', 'v2' );
					if ( $recaptcha_site_key && $recaptcha_version === 'v2' ) : ?>
						<div id="dlm-checkout-recaptcha-wrapper" style="margin-bottom: 20px; display: flex; justify-content: center;">
							<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"></div>
						</div>
					<?php endif; ?>

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
					<div class="flex items-center gap-3">
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
						$recaptcha_site_key = get_option( 'dlm_recaptcha_site_key' );
						$recaptcha_version  = get_option( 'dlm_recaptcha_version', 'v2' );
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
			wp_send_json_error( array( 'message' => __( 'Security verification failed (ReCAPTCHA). Please try again.', 'digital-library-membership' ) ) );
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

			if ( ! empty( $redirect_post ) ) {
				$redirect = $redirect_post;
			} elseif ( ! empty( $referer ) && strpos( $referer, strtok( $checkout_url, '?' ) ) !== false ) {
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
			wp_send_json_error( array( 'message' => __( 'Security verification failed (ReCAPTCHA). Please try again.', 'digital-library-membership' ) ) );
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

		if ( ! empty( $redirect_post ) ) {
			$redirect = $redirect_post;
		} elseif ( ! empty( $referer ) && strpos( $referer, strtok( $checkout_url, '?' ) ) !== false ) {
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
		$new_password = isset( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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

		if ( ! empty( $new_password ) ) {
			if ( strlen( $new_password ) < 6 ) {
				wp_send_json_error( array( 'message' => __( 'New password must be at least 6 characters.', 'digital-library-membership' ) ) );
			}
			$userdata['user_pass'] = $new_password;
		}

		$updated_user_id = wp_update_user( $userdata );
		if ( is_wp_error( $updated_user_id ) ) {
			wp_send_json_error( array( 'message' => $updated_user_id->get_error_message() ) );
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
}

