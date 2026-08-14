<?php
/**
 * Headless Order Pay Template Override for Digital Library Membership
 *
 * Provides a clean, distraction-free checkout experience for headless book & subscription purchases.
 *
 * @since      2.0.0
 * @package    DLM
 * @subpackage DLM/templates/woocommerce/checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order_id   = $order->get_id();
$order_type = $order->get_meta( '_dlm_order_type' );
$book_id    = intval( $order->get_meta( '_dlm_book_id' ) );
$book       = $book_id ? ( new DLM_DB() )->get_book( $book_id ) : null;
$currency_symbol = get_woocommerce_currency_symbol( $order->get_currency() );
?>

<div class="dlm-pay-wrapper" style="max-width: 640px; margin: 40px auto; padding: 0 16px; font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;">
	<div class="dlm-pay-card" style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); border: 1px solid #e8e8e8; overflow: hidden; padding: 32px 36px;">
		
		<!-- Header -->
		<div style="text-align: center; margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0;">
			<div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 50%; background: #fdf6e9; color: #855300; margin-bottom: 16px; font-size: 26px;">
				<span class="dashicons dashicons-shield-alt" style="font-size: 28px; width: 28px; height: 28px; line-height: 1;"></span>
			</div>
			<h2 style="font-size: 24px; font-weight: 700; color: #1a1c1c; margin: 0 0 6px 0; letter-spacing: -0.5px;">
				<?php esc_html_e( 'Secure Checkout', 'digital-library-membership' ); ?>
			</h2>
			<p style="color: #6c757d; font-size: 14px; margin: 0;">
				<?php esc_html_e( 'Complete your payment to unlock instant digital access.', 'digital-library-membership' ); ?>
			</p>
		</div>

		<!-- Item Summary -->
		<div style="background: #f9f9fb; border-radius: 14px; padding: 20px; margin-bottom: 28px; display: flex; align-items: center; gap: 18px; border: 1px solid #f0f0f4;">
			<?php if ( $book && ! empty( $book->cover_image_url ) ) : ?>
				<img src="<?php echo esc_url( $book->cover_image_url ); ?>" alt="<?php echo esc_attr( $book->title ); ?>" style="width: 64px; height: 90px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); flex-shrink: 0;">
			<?php else : ?>
				<div style="width: 64px; height: 90px; background: #855300; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 28px; flex-shrink: 0;">
					📚
				</div>
			<?php endif; ?>
			
			<div style="flex-grow: 1;">
				<span style="display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #fdf6e9; color: #855300; padding: 3px 8px; border-radius: 6px; margin-bottom: 6px;">
					<?php echo ( $order_type === 'subscription' ) ? esc_html__( 'Membership Subscription', 'digital-library-membership' ) : esc_html__( 'Digital Book Access', 'digital-library-membership' ); ?>
				</span>
				<h3 style="font-size: 17px; font-weight: 700; color: #1a1c1c; margin: 0 0 4px 0; line-height: 1.3;">
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
					<p style="font-size: 13px; color: #71717a; margin: 0 0 4px 0;">
						<?php 
						/* translators: %s: Author name */
						echo esc_html( sprintf( __( 'by %s', 'digital-library-membership' ), $book->author ) ); 
						?>
					</p>
				<?php endif; ?>
				<p style="font-size: 12px; color: #16a34a; font-weight: 600; margin: 0;">
					✓ <?php esc_html_e( 'Instant Online Flipbook + PDF Download', 'digital-library-membership' ); ?>
				</p>
			</div>

			<div style="text-align: right; flex-shrink: 0;">
				<span style="display: block; font-size: 12px; color: #71717a;"><?php esc_html_e( 'Total Amount', 'digital-library-membership' ); ?></span>
				<span style="font-size: 22px; font-weight: 800; color: #855300;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
			</div>
		</div>

		<!-- Payment Form -->
		<form id="order_review" method="post" action="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">
			<?php if ( $order->needs_payment() ) : ?>
				<div id="payment" style="background: transparent; padding: 0;">
					<h4 style="font-size: 15px; font-weight: 700; color: #1a1c1c; margin: 0 0 16px 0;">
						<?php esc_html_e( 'Select Payment Method', 'digital-library-membership' ); ?>
					</h4>
					
					<ul class="wc_payment_methods payment_methods methods" style="list-style: none; padding: 0; margin: 0 0 24px 0;">
						<?php
						$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
						if ( ! empty( $available_gateways ) ) {
							current( $available_gateways )->set_current();
							foreach ( $available_gateways as $gateway ) {
								wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
							}
						} else {
							echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info" style="padding: 14px 18px; border-radius: 10px; background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; font-size: 14px;">' . esc_html__( 'No payment methods are available. Please contact library support.', 'digital-library-membership' ) . '</li>';
						}
						?>
					</ul>

					<div class="form-row" style="margin-top: 24px;">
						<input type="hidden" name="woocommerce_pay" value="1" />
						<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
						
						<button type="submit" class="button alt" id="place_order" value="<?php esc_attr_e( 'Complete Payment', 'digital-library-membership' ); ?>" data-value="<?php esc_attr_e( 'Complete Payment', 'digital-library-membership' ); ?>" style="width: 100%; padding: 16px 24px; background: #855300; color: #ffffff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(133, 83, 0, 0.25);">
							🔒 <?php esc_html_e( 'Pay Securely Now', 'digital-library-membership' ); ?> &mdash; <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>
		</form>

		<!-- Security footer badge -->
		<div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f0f0f0; color: #a1a1aa; font-size: 12px;">
			<span>🔒 256-Bit SSL Encryption</span>
			<span>•</span>
			<span>Verified Headless Gateway</span>
		</div>
	</div>
</div>
