<?php
/**
 * Order Pay Template Override for Digital Library Membership
 *
 * Provides a clean, distraction-free luxury checkout experience for pending order payments.
 *
 * @since      2.0.0
 * @package    DLM
 * @subpackage DLM/templates/woocommerce/checkout
 * @version    3.2.5
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order_id   = $order->get_id();
$order_type = $order->get_meta( '_dlm_order_type' );
$book_id    = intval( $order->get_meta( '_dlm_book_id' ) );
$book       = $book_id ? ( new DLM_DB() )->get_book( $book_id ) : null;
$account_url = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'account' ) : home_url( '/library-account/' );
?>

<div class="dlm-checkout-page-root dlm-pay-page-root">
	<div class="dlm-pay-container" style="max-width: 680px; margin: 0 auto;">
		
		<div class="dlm-checkout-header" style="justify-content: center; text-align: center; margin-bottom: 28px;">
			<div class="dlm-checkout-header-content">
				<span class="dlm-badge-eyebrow"><i class="fa-solid fa-lock"></i> <?php esc_html_e( 'Secure Gateway Payment', 'digital-library-membership' ); ?></span>
				<h1 class="dlm-checkout-title"><?php esc_html_e( 'Complete Your Order', 'digital-library-membership' ); ?></h1>
			</div>
		</div>

		<div class="dlm-checkout-section-card" style="margin-bottom: 24px;">
			
			<!-- Item Summary Header -->
			<div class="dlm-pay-item-summary" style="display: flex; align-items: center; gap: 20px; padding-bottom: 20px; margin-bottom: 24px; border-bottom: 1px solid rgba(0,0,0,0.08);">
				<?php if ( $book && ! empty( $book->cover_image_url ) ) : ?>
					<img src="<?php echo esc_url( $book->cover_image_url ); ?>" alt="<?php echo esc_attr( $book->title ); ?>" style="width: 68px; height: 96px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); flex-shrink: 0;">
				<?php else : ?>
					<div style="width: 68px; height: 96px; background: linear-gradient(135deg, #855300 0%, #3e2600 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 24px; flex-shrink: 0;">
						<i class="fa-solid fa-book"></i>
					</div>
				<?php endif; ?>
				
				<div style="flex-grow: 1;">
					<span class="dlm-badge-eyebrow" style="margin-bottom: 4px;">
						<?php echo ( $order_type === 'subscription' ) ? esc_html__( 'Membership Subscription', 'digital-library-membership' ) : esc_html__( 'Digital Book Access', 'digital-library-membership' ); ?>
					</span>
					<h3 style="font-size: 18px; font-weight: 700; color: #1a1c1c; margin: 0 0 4px 0; line-height: 1.3;">
						<?php 
						if ( $book ) {
							echo esc_html( $book->title );
						} else {
							foreach ( $order->get_items() as $item ) {
								echo esc_html( $item->get_name() );
								break;
							}
						}
						?>
					</h3>
					<?php if ( $book && ! empty( $book->author ) ) : ?>
						<p style="font-size: 13px; color: #71717a; margin: 0 0 6px 0;">
							<?php 
							/* translators: %s: Author name */
							echo esc_html( sprintf( __( 'by %s', 'digital-library-membership' ), $book->author ) ); 
							?>
						</p>
					<?php endif; ?>
					<p style="font-size: 12px; color: #16a34a; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 6px;">
						<i class="fa-solid fa-circle-check"></i> <?php esc_html_e( 'Instant Digital Access Upon Payment', 'digital-library-membership' ); ?>
					</p>
				</div>

				<div style="text-align: right; flex-shrink: 0;">
					<span style="display: block; font-size: 12px; color: #71717a;"><?php esc_html_e( 'Total Due', 'digital-library-membership' ); ?></span>
					<span style="font-size: 24px; font-weight: 800; color: #855300; letter-spacing: -0.02em;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
				</div>
			</div>

			<!-- Payment Gateways Form -->
			<div class="dlm-checkout-left-col">
				<form id="order_review" method="post" action="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">
					<?php if ( $order->needs_payment() ) : ?>
						<div id="payment" class="woocommerce-checkout-payment">
							<h3 class="dlm-checkout-section-title">
								<span class="dlm-step-num"><i class="fa-solid fa-credit-card"></i></span>
								<?php esc_html_e( 'Select Payment Method', 'digital-library-membership' ); ?>
							</h3>
							
							<ul class="wc_payment_methods payment_methods methods">
								<?php
								$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
								if ( ! empty( $available_gateways ) ) {
									current( $available_gateways )->set_current();
									foreach ( $available_gateways as $gateway ) {
										wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
									}
								} else {
									echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . esc_html__( 'No payment methods are available. Please contact library support.', 'digital-library-membership' ) . '</li>';
								}
								?>
							</ul>

							<div class="form-row" style="margin-top: 24px;">
								<input type="hidden" name="woocommerce_pay" value="1" />
								<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
								
								<button type="submit" class="button alt" id="place_order" value="<?php esc_attr_e( 'Complete Payment', 'digital-library-membership' ); ?>" data-value="<?php esc_attr_e( 'Complete Payment', 'digital-library-membership' ); ?>">
									<i class="fa-solid fa-lock"></i> <?php esc_html_e( 'Pay Securely Now', 'digital-library-membership' ); ?> &mdash; <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
								</button>
							</div>
						</div>
					<?php endif; ?>
				</form>
			</div>

		</div>

		<!-- Security footer badge -->
		<div class="dlm-checkout-security-footer">
			<span><i class="fa-solid fa-shield-halved"></i> <?php esc_html_e( '256-Bit SSL Encryption', 'digital-library-membership' ); ?></span>
			<span>&bull;</span>
			<span><i class="fa-solid fa-bolt"></i> <?php esc_html_e( 'Verified Secure Gateway', 'digital-library-membership' ); ?></span>
		</div>

	</div>
</div>
