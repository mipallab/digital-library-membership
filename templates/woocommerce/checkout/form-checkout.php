<?php
/**
 * Checkout Form Template Override for Digital Library Membership
 *
 * Implements the exact luxury aesthetic of the Library Member Dashboard `#checkout` tab.
 *
 * @package DLM
 * @subpackage DLM/templates/woocommerce/checkout
 * @version 3.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure checkout CSS & Font Awesome are enqueued
wp_enqueue_style( 'dlm-woocommerce-checkout', DLM_URL . 'public/css/dlm-woocommerce-checkout.css', array(), DLM_VERSION );
wp_enqueue_style( 'dlm-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );

// Get account URL for back button
$account_url = dlm_get_page_url( 'account' );

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, user cannot checkout
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>

<div class="dlm-checkout-page-root">
	<div class="dlm-checkout-container">
		
		<!-- Page Header -->
		<div class="dlm-checkout-header">
			<a href="<?php echo esc_url( $account_url . '#membership' ); ?>" class="dlm-checkout-back-btn" title="<?php esc_attr_e( 'Back to Library', 'digital-library-membership' ); ?>">
				<i class="fa-solid fa-arrow-left"></i>
			</a>
			<div class="dlm-checkout-header-content">
				<span class="dlm-badge-eyebrow"><?php esc_html_e( 'Review your selection', 'digital-library-membership' ); ?></span>
				<h1 class="dlm-checkout-title"><?php esc_html_e( 'Secure Checkout', 'digital-library-membership' ); ?></h1>
			</div>
		</div>

		<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

			<div class="dlm-checkout-grid">
				
				<!-- LEFT COLUMN: Customer Information & Payment Methods -->
				<div class="dlm-checkout-left-col">
					
					<?php if ( $checkout->get_checkout_fields() ) : ?>

						<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

						<div class="dlm-checkout-section-card" id="customer_details">
							<h3 class="dlm-checkout-section-title">
								<span class="dlm-step-num">1</span>
								<?php esc_html_e( 'Billing & Account Information', 'digital-library-membership' ); ?>
							</h3>
							
							<div class="dlm-billing-fields-wrap">
								<?php do_action( 'woocommerce_checkout_billing' ); ?>
								<?php do_action( 'woocommerce_checkout_shipping' ); ?>
							</div>
						</div>

						<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

					<?php endif; ?>

					<!-- Payment Section Card -->
					<div class="dlm-checkout-section-card">
						<h3 class="dlm-checkout-section-title">
							<span class="dlm-step-num">2</span>
							<?php esc_html_e( 'Choose Payment Method', 'digital-library-membership' ); ?>
						</h3>

						<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
						
						<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

						<div id="order_review" class="woocommerce-checkout-review-order">
							<?php do_action( 'woocommerce_checkout_order_review' ); ?>
						</div>

						<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
					</div>

				</div>

				<!-- RIGHT COLUMN: Sticky Order Summary Card -->
				<div class="dlm-checkout-right-col">
					<div class="dlm-summary-card">
						
						<!-- Luxury Gradient Banner Header -->
						<div class="dlm-summary-header-banner">
							<span class="dlm-summary-badge">
								<i class="fa-solid fa-lock"></i> <?php esc_html_e( 'SECURE GATEWAY', 'digital-library-membership' ); ?>
							</span>
							<h3 class="dlm-summary-plan-title">
								<?php
								$cart_items = WC()->cart ? WC()->cart->get_cart() : array();
								if ( ! empty( $cart_items ) ) {
									$first_item = reset( $cart_items );
									$prod = $first_item['data'];
									echo esc_html( $prod->get_name() );
								} else {
									esc_html_e( 'Order Summary', 'digital-library-membership' );
								}
								?>
							</h3>
						</div>

						<!-- Summary Card Body -->
						<div class="dlm-summary-body">
							
							<div class="dlm-summary-items-list">
								<?php
								if ( ! empty( $cart_items ) ) {
									foreach ( $cart_items as $cart_item_key => $cart_item ) {
										$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
										$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

										if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
											?>
											<div class="dlm-summary-item-row">
												<div>
													<div class="dlm-summary-item-name">
														<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
														<?php if ( $cart_item['quantity'] > 1 ) : ?>
															<span style="color: #855300; font-size: 12px;">&times; <?php echo esc_html( $cart_item['quantity'] ); ?></span>
														<?php endif; ?>
													</div>
													<div class="dlm-summary-item-desc">
														<?php esc_html_e( 'Unlimited Digital Reader & Flipbook Access', 'digital-library-membership' ); ?>
													</div>
												</div>
												<div class="dlm-summary-item-price">
													<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
												</div>
											</div>
											<?php
										}
									}
								}
								?>
							</div>

							<!-- Calculation Breakdown -->
							<div class="dlm-summary-calc-row">
								<span><?php esc_html_e( 'Subtotal', 'digital-library-membership' ); ?></span>
								<span><?php if ( function_exists( 'wc_cart_totals_subtotal_html' ) ) { wc_cart_totals_subtotal_html(); } ?></span>
							</div>

							<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
								<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
									<div class="dlm-summary-calc-row" style="color: #16a34a;">
										<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
										<span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
									</div>
								<?php endforeach; ?>

								<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
									<div class="dlm-summary-calc-row">
										<span><?php echo esc_html( $fee->name ); ?></span>
										<span><?php wc_cart_totals_fee_html( $fee ); ?></span>
									</div>
								<?php endforeach; ?>

								<?php if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
									<div class="dlm-summary-calc-row">
										<span><?php esc_html_e( 'Tax', 'digital-library-membership' ); ?></span>
										<span><?php echo esc_html( WC()->cart->get_total_tax() ); ?></span>
									</div>
								<?php endif; ?>
							<?php endif; ?>

							<div class="dlm-summary-total-row">
								<span class="dlm-summary-total-label"><?php esc_html_e( 'Total Due', 'digital-library-membership' ); ?></span>
								<span class="dlm-summary-total-amount"><?php if ( function_exists( 'wc_cart_totals_order_total_html' ) ) { wc_cart_totals_order_total_html(); } ?></span>
							</div>

							<!-- Trust & Features List -->
							<div class="dlm-summary-trust-list">
								<div class="dlm-summary-trust-item">
									<span class="dlm-check"><i class="fa-solid fa-check"></i></span>
									<span><?php esc_html_e( '256-Bit SSL Encrypted Checkout', 'digital-library-membership' ); ?></span>
								</div>
								<div class="dlm-summary-trust-item">
									<span class="dlm-check"><i class="fa-solid fa-check"></i></span>
									<span><?php esc_html_e( 'Instant Digital Access After Payment', 'digital-library-membership' ); ?></span>
								</div>
								<div class="dlm-summary-trust-item">
									<span class="dlm-check"><i class="fa-solid fa-check"></i></span>
									<span><?php esc_html_e( 'Automated Member Activation', 'digital-library-membership' ); ?></span>
								</div>
							</div>

						</div>
					</div>

					<!-- Security Footer Badges -->
					<div class="dlm-checkout-security-footer">
						<span><i class="fa-solid fa-shield-halved"></i> <?php esc_html_e( '256-Bit SSL Encryption', 'digital-library-membership' ); ?></span>
						<span>&bull;</span>
						<span><i class="fa-solid fa-bolt"></i> <?php esc_html_e( 'Verified Gateway', 'digital-library-membership' ); ?></span>
					</div>
				</div>

			</div>

		</form>

	</div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
