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

		$books = $this->db->get_books( 'publish' );
		
		ob_start();
		?>
		<div class="dlm-container">
			<header class="dlm-library-header">
				<h1><?php esc_html_e( 'Digital Library', 'digital-library-membership' ); ?></h1>
				<div class="dlm-sub-bar">
					<?php if ( $is_active ) : ?>
						<span class="dlm-status-badge active"><?php esc_html_e( 'Active Member', 'digital-library-membership' ); ?></span>
					<?php elseif ( $is_logged_in ) : ?>
						<span class="dlm-status-badge inactive"><?php esc_html_e( 'No Active Membership', 'digital-library-membership' ); ?></span>
						<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="dlm-btn dlm-btn-primary dlm-btn-sm"><?php esc_html_e( 'Join Membership', 'digital-library-membership' ); ?></a>
					<?php else : ?>
						<span class="dlm-status-badge guest" style="background:#e8eaed; color:#3c4043; padding:4px 10px; border-radius:6px; font-weight:600; font-size:12px; margin-right:10px;"><?php esc_html_e( 'Guest Preview', 'digital-library-membership' ); ?></span>
						<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="dlm-btn dlm-btn-primary dlm-btn-sm"><?php esc_html_e( 'View Membership Plans', 'digital-library-membership' ); ?></a>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( ! $is_logged_in ) : ?>
				<div class="dlm-msg-box info" style="background:#f0f7ff; border:1px solid #cce5ff; color:#004085; padding:15px 20px; border-radius:12px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
					<div>
						<strong><?php esc_html_e( 'Welcome to our Digital Library!', 'digital-library-membership' ); ?></strong>
						<p style="margin:4px 0 0 0; font-size:13px; color:#555;"><?php esc_html_e( 'Browse our catalog below. Log in or create an account to unlock full reading access.', 'digital-library-membership' ); ?></p>
					</div>
					<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="dlm-btn dlm-btn-accent dlm-btn-sm"><?php esc_html_e( 'Get Access Now', 'digital-library-membership' ); ?></a>
				</div>
			<?php endif; ?>

			<!-- Search & Filter -->
			<div class="dlm-filter-bar">
				<input type="text" id="dlm-search" placeholder="<?php esc_attr_e( 'Search by title or author...', 'digital-library-membership' ); ?>">
			</div>

			<div class="dlm-books-grid" id="dlm-books-grid">
				<?php if ( empty( $books ) ) : ?>
					<div class="dlm-empty-state">
						<p><?php esc_html_e( 'No books are currently available in the library.', 'digital-library-membership' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $books as $book ) : ?>
						<?php
						$progress         = ( $is_logged_in && $user_id ) ? $this->db->get_reading_progress( $user_id, $book->id ) : null;
						$last_page        = $progress ? intval( $progress->last_page ) : 1;
						$progress_percent = $progress ? intval( $progress->progress_percent ) : 0;
						?>
						<div class="dlm-book-card" data-title="<?php echo esc_attr( strtolower( $book->title ) ); ?>" data-author="<?php echo esc_attr( strtolower( $book->author ) ); ?>">
							<div class="dlm-book-cover-wrapper">
								<?php if ( $book->cover_image_url ) : ?>
									<img src="<?php echo esc_url( $book->cover_image_url ); ?>" alt="<?php echo esc_attr( $book->title ); ?>" class="dlm-book-cover" loading="lazy">
								<?php else : ?>
									<div class="dlm-book-cover-placeholder">
										<span><?php echo esc_html( $book->title ); ?></span>
									</div>
								<?php endif; ?>
								
								<div class="dlm-book-overlay">
									<?php if ( $is_active ) : ?>
										<a href="<?php echo esc_url( home_url( '/read/' . $book->id . '/' ) ); ?>" class="dlm-btn dlm-btn-read">
											<?php echo ( $progress_percent > 0 ) ? esc_html__( 'Continue Reading', 'digital-library-membership' ) : esc_html__( 'Read Book', 'digital-library-membership' ); ?>
										</a>
									<?php elseif ( $is_logged_in ) : ?>
										<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="dlm-btn dlm-btn-subscribe">
											<?php esc_html_e( 'Unlock Access', 'digital-library-membership' ); ?>
										</a>
									<?php else : ?>
										<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="dlm-btn dlm-btn-subscribe">
											<?php esc_html_e( 'Sign Up to Read', 'digital-library-membership' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
							<div class="dlm-book-details">
								<h3 class="dlm-book-title"><?php echo esc_html( $book->title ); ?></h3>
								<p class="dlm-book-author"><?php echo esc_html( $book->author ); ?></p>
								
								<?php if ( $progress_percent > 0 ) : ?>
									<div class="dlm-progress-container">
										<div class="dlm-progress-bar" style="width: <?php echo intval( $progress_percent ); ?>%;"></div>
										<span class="dlm-progress-text"><?php 
											/* translators: %d: Progress percentage */
											echo esc_html( sprintf( __( '%d%% Read', 'digital-library-membership' ), $progress_percent ) ); 
										?></span>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<?php
		$search_js = "
			jQuery(document).ready(function($) {
				$('#dlm-search').on('input', function() {
					var query = $(this).val().toLowerCase();
					$('.dlm-book-card').each(function() {
						var title = $(this).data('title');
						var author = $(this).data('author');
						if (title.indexOf(query) !== -1 || author.indexOf(query) !== -1) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				});
			});
		";
		wp_add_inline_script( 'jquery', $search_js );
		?>
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
		<div class="dlm-container dlm-auth-container">
			<div class="dlm-auth-card">
				<div class="dlm-auth-tabs">
					<button class="dlm-auth-tab-btn active" data-tab="login"><?php esc_html_e( 'Sign In', 'digital-library-membership' ); ?></button>
					<button class="dlm-auth-tab-btn" data-tab="register"><?php esc_html_e( 'Create Account', 'digital-library-membership' ); ?></button>
				</div>

				<!-- Login Panel -->
				<div class="dlm-auth-panel" id="panel-login">
					<form id="dlm-login-form">
						<div class="dlm-auth-alert" style="display:none;"></div>
						<p>
							<label for="dlm_username"><?php esc_html_e( 'Username or Email', 'digital-library-membership' ); ?></label>
							<input type="text" id="dlm_username" class="dlm-auth-input" required>
						</p>
						<p>
							<label for="dlm_password"><?php esc_html_e( 'Password', 'digital-library-membership' ); ?></label>
							<input type="password" id="dlm_password" class="dlm-auth-input" required>
						</p>
						<button type="submit" class="dlm-btn dlm-btn-primary dlm-btn-block" style="margin-top:20px;"><?php esc_html_e( 'Sign In', 'digital-library-membership' ); ?></button>
					</form>
				</div>

				<!-- Register Panel -->
				<div class="dlm-auth-panel" id="panel-register" style="display:none;">
					<form id="dlm-register-form">
						<div class="dlm-auth-alert" style="display:none;"></div>
						<p>
							<label for="dlm_reg_name"><?php esc_html_e( 'Display Name', 'digital-library-membership' ); ?></label>
							<input type="text" id="dlm_reg_name" class="dlm-auth-input" required>
						</p>
						<p>
							<label for="dlm_reg_email"><?php esc_html_e( 'Email Address', 'digital-library-membership' ); ?></label>
							<input type="email" id="dlm_reg_email" class="dlm-auth-input" required>
						</p>
						<p>
							<label for="dlm_reg_password"><?php esc_html_e( 'Choose Password', 'digital-library-membership' ); ?></label>
							<input type="password" id="dlm_reg_password" class="dlm-auth-input" required minlength="6">
						</p>
						<button type="submit" class="dlm-btn dlm-btn-accent dlm-btn-block" style="margin-top:20px;"><?php esc_html_e( 'Register & Auto-Login', 'digital-library-membership' ); ?></button>
					</form>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX login handler
	 */
	public function ajax_login() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

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
			wp_send_json_error( array( 'message' => esc_html( $user->get_error_message() ) ) );
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
			wp_send_json_error( array( 'message' => esc_html( $user_id->get_error_message() ) ) );
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

