<?php
/**
 * Thank You (Order Received) Template Override for Digital Library Membership
 *
 * Implements the luxury amber aesthetic matching the Library Member Dashboard.
 *
 * @package DLM
 * @subpackage DLM/templates/woocommerce/checkout
 * @version 3.2.5
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$account_url = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'account' ) : home_url( '/library-account/' );
$library_url = function_exists( 'dlm_get_page_url' ) ? dlm_get_page_url( 'library' ) : home_url( '/library/' );
if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
	global $wp;
	$order_id = 0;
	if ( ! empty( $order ) && is_numeric( $order ) ) {
		$order_id = absint( $order );
	} elseif ( isset( $wp->query_vars['order-received'] ) ) {
		$order_id = absint( $wp->query_vars['order-received'] );
	} elseif ( isset( $_GET['order_id'] ) ) {
		$order_id = absint( $_GET['order_id'] );
	}
	if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
	}
}
?>

<div class="dlm-checkout-page-root dlm-thankyou-page-root">
	<div class="dlm-thankyou-container">

		<?php if ( $order ) : ?>

			<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

			<?php if ( $order->has_status( 'failed' ) ) : ?>

				<!-- Order Failed State -->
				<div class="dlm-thankyou-card dlm-thankyou-failed-card">
					<div class="dlm-thankyou-icon-wrap dlm-icon-failed">
						<i class="fa-solid fa-circle-exclamation"></i>
					</div>
					<span class="dlm-badge-eyebrow dlm-badge-failed"><?php esc_html_e( 'Payment Incomplete', 'digital-library-membership' ); ?></span>
					<h1 class="dlm-thankyou-title"><?php esc_html_e( 'Payment Could Not Be Completed', 'digital-library-membership' ); ?></h1>
					
					<p class="dlm-thankyou-message">
						<?php esc_html_e( 'Unfortunately, your payment could not be processed. Please try again with another payment method or review your details.', 'digital-library-membership' ); ?>
					</p>

					<div class="dlm-thankyou-actions">
						<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="dlm-btn-primary">
							<i class="fa-solid fa-rotate-right"></i> <?php esc_html_e( 'Retry Payment', 'digital-library-membership' ); ?>
						</a>
						<a href="<?php echo esc_url( $account_url . '#membership' ); ?>" class="dlm-btn-secondary">
							<?php esc_html_e( 'Back to Library', 'digital-library-membership' ); ?>
						</a>
					</div>
				</div>

			<?php else : ?>

				<?php
				// Detect Order Type and Product Details for Tailored Confirmation
				$order_type   = $order->get_meta( '_dlm_order_type' );
				$book_id      = absint( $order->get_meta( '_dlm_book_id' ) );
				$package_id   = sanitize_text_field( $order->get_meta( '_dlm_package_id' ) );
				$plan_interval = sanitize_text_field( $order->get_meta( '_dlm_plan_interval' ) );

				$first_item_name = '';
				$items           = $order->get_items();
				if ( ! empty( $items ) ) {
					$first_item = reset( $items );
					$first_item_name = $first_item->get_name();
					if ( empty( $order_type ) ) {
						$order_type = $first_item->get_meta( '_dlm_order_type' );
					}
					if ( empty( $book_id ) ) {
						$book_id = absint( $first_item->get_meta( '_dlm_book_id' ) );
					}
				}

				$is_subscription = ( 'subscription' === $order_type || ! empty( $package_id ) || ! empty( $plan_interval ) );
				$is_book         = ( 'single_book' === $order_type || ! empty( $book_id ) );

				// Target dashboard tab
				$dashboard_target_url = $account_url;
				if ( $is_subscription ) {
					$dashboard_target_url = $account_url . '#membership';
				} elseif ( $is_book ) {
					$dashboard_target_url = $account_url . '#library';
				}
				?>

				<!-- Success Hero Card -->
				<div class="dlm-thankyou-card">
					
					<!-- Animated Glowing Success Icon -->
					<div class="dlm-thankyou-icon-wrap dlm-icon-success">
						<div class="dlm-icon-pulse-ring"></div>
						<i class="fa-solid fa-check"></i>
					</div>

					<span class="dlm-badge-eyebrow">
						<i class="fa-solid fa-shield-check"></i> <?php esc_html_e( 'Order Confirmed & Instant Access Activated', 'digital-library-membership' ); ?>
					</span>

					<h1 class="dlm-thankyou-title"><?php esc_html_e( 'Thank You for Your Order!', 'digital-library-membership' ); ?></h1>

					<p class="dlm-thankyou-message">
						<?php if ( $is_subscription ) : ?>
							<?php
							/* translators: %s: Plan name */
							echo sprintf( esc_html__( 'Your %s membership is now active! You have unlimited access to our entire digital catalog, flipbooks, and reading tools.', 'digital-library-membership' ), '<strong>' . esc_html( $first_item_name ?: __( 'Library', 'digital-library-membership' ) ) . '</strong>' );
							?>
						<?php elseif ( $is_book ) : ?>
							<?php
							/* translators: %s: Book title */
							echo sprintf( esc_html__( 'You have successfully unlocked %s! It is now available in your personal library to read online or download.', 'digital-library-membership' ), '<strong>' . esc_html( $first_item_name ?: __( 'Book', 'digital-library-membership' ) ) . '</strong>' );
							?>
						<?php else : ?>
							<?php esc_html_e( 'Your payment has been successfully processed and your digital access is ready.', 'digital-library-membership' ); ?>
						<?php endif; ?>
					</p>

					<!-- 4-Metric Key Overview Grid -->
					<div class="dlm-thankyou-metrics-grid">
						<div class="dlm-metric-card">
							<span class="dlm-metric-label"><?php esc_html_e( 'Order Number', 'digital-library-membership' ); ?></span>
							<strong class="dlm-metric-value">#<?php echo esc_html( $order->get_order_number() ); ?></strong>
						</div>
						<div class="dlm-metric-card">
							<span class="dlm-metric-label"><?php esc_html_e( 'Date', 'digital-library-membership' ); ?></span>
							<strong class="dlm-metric-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
						</div>
						<div class="dlm-metric-card">
							<span class="dlm-metric-label"><?php esc_html_e( 'Total Paid', 'digital-library-membership' ); ?></span>
							<strong class="dlm-metric-value dlm-metric-gold"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
						</div>
						<div class="dlm-metric-card">
							<span class="dlm-metric-label"><?php esc_html_e( 'Payment Method', 'digital-library-membership' ); ?></span>
							<strong class="dlm-metric-value"><?php echo esc_html( $order->get_payment_method_title() ?: __( 'Online Gateway', 'digital-library-membership' ) ); ?></strong>
						</div>
					</div>

					<!-- Order Line Items Breakdown Card -->
					<div class="dlm-thankyou-items-card">
						<h3 class="dlm-thankyou-section-heading">
							<i class="fa-solid fa-layer-group"></i> <?php esc_html_e( 'Purchased Items & Digital Access', 'digital-library-membership' ); ?>
						</h3>

						<div class="dlm-thankyou-items-list">
							<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
								<div class="dlm-thankyou-item-row">
									<div class="dlm-thankyou-item-info">
										<span class="dlm-thankyou-item-badge">
											<i class="fa-solid fa-bolt"></i> <?php esc_html_e( 'Instant Unlock', 'digital-library-membership' ); ?>
										</span>
										<h4 class="dlm-thankyou-item-title">
											<?php echo esc_html( $item->get_name() ); ?>
											<?php if ( $item->get_quantity() > 1 ) : ?>
												<span class="dlm-qty">&times; <?php echo esc_html( $item->get_quantity() ); ?></span>
											<?php endif; ?>
										</h4>
										<span class="dlm-thankyou-item-desc">
											<?php esc_html_e( 'Digital Reader Access & Flipbook Features', 'digital-library-membership' ); ?>
										</span>
									</div>
									<div class="dlm-thankyou-item-price">
										<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>

						<!-- Financial Breakdown -->
						<div class="dlm-thankyou-calc-wrap">
							<div class="dlm-thankyou-calc-row">
								<span><?php esc_html_e( 'Subtotal', 'digital-library-membership' ); ?></span>
								<span><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></span>
							</div>
							<?php if ( $order->get_total_discount() > 0 ) : ?>
								<div class="dlm-thankyou-calc-row dlm-discount-row">
									<span><?php esc_html_e( 'Discount', 'digital-library-membership' ); ?></span>
									<span>-<?php echo wp_kses_post( wc_price( $order->get_total_discount(), array( 'currency' => $order->get_currency() ) ) ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( $order->get_total_tax() > 0 ) : ?>
								<div class="dlm-thankyou-calc-row">
									<span><?php esc_html_e( 'Tax', 'digital-library-membership' ); ?></span>
									<span><?php echo wp_kses_post( wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ); ?></span>
								</div>
							<?php endif; ?>
							<div class="dlm-thankyou-calc-row dlm-total-row">
								<span class="dlm-total-label"><?php esc_html_e( 'Total Amount', 'digital-library-membership' ); ?></span>
								<span class="dlm-total-amount"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
							</div>
						</div>
					</div>

					<!-- Primary Action CTAs -->
					<div class="dlm-thankyou-actions">
						<a href="<?php echo esc_url( $dashboard_target_url ); ?>" class="dlm-btn-primary">
							<i class="fa-solid fa-sparkles"></i>
							<?php
							if ( $is_book ) {
								esc_html_e( 'Start Reading in Library', 'digital-library-membership' );
							} else {
								esc_html_e( 'Go to Member Dashboard', 'digital-library-membership' );
							}
							?>
						</a>
						<a href="<?php echo esc_url( $library_url ); ?>" class="dlm-btn-secondary">
							<i class="fa-solid fa-book-open"></i> <?php esc_html_e( 'Explore Library Catalog', 'digital-library-membership' ); ?>
						</a>
					</div>

					<!-- Trust / Automated Access Note -->
					<div class="dlm-thankyou-trust-note">
						<i class="fa-solid fa-envelope-circle-check"></i>
						<span>
							<?php
							/* translators: %s: Customer email */
							echo sprintf( esc_html__( 'A payment confirmation and receipt have been sent to %s.', 'digital-library-membership' ), '<strong>' . esc_html( $order->get_billing_email() ) . '</strong>' );
							?>
						</span>
					</div>

					<!-- Standard Gateway Specific Outputs / Hooks -->
					<div class="dlm-thankyou-gateway-extra">
						<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
					</div>

				</div>

			<?php endif; ?>

		<?php else : ?>

			<!-- Fallback if accessed without active order context -->
			<div class="dlm-thankyou-card">
				<div class="dlm-thankyou-icon-wrap dlm-icon-success">
					<i class="fa-solid fa-check"></i>
				</div>
				<span class="dlm-badge-eyebrow"><?php esc_html_e( 'Order Received', 'digital-library-membership' ); ?></span>
				<h1 class="dlm-thankyou-title"><?php esc_html_e( 'Thank You for Your Order!', 'digital-library-membership' ); ?></h1>
				<p class="dlm-thankyou-message">
					<?php esc_html_e( 'Your order has been received. Your digital access will be active on your account.', 'digital-library-membership' ); ?>
				</p>
				<div class="dlm-thankyou-actions">
					<a href="<?php echo esc_url( $account_url ); ?>" class="dlm-btn-primary">
						<?php esc_html_e( 'Go to Member Dashboard', 'digital-library-membership' ); ?>
					</a>
				</div>
			</div>

		<?php endif; ?>

	</div>
</div>
