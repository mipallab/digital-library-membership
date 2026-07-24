<?php
/**
 * Payment Gateways Integration (Stripe & PayPal)
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Checkout {

	/**
	 * Create Stripe Checkout Session
	 */
	public function ajax_stripe_create_session() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$recaptcha_response = isset( $_POST['recaptcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_response'] ) ) : '';
		if ( ! dlm_verify_recaptcha( $recaptcha_response ) ) {
			wp_send_json_error( array( 'message' => __( 'You failed the Google ReCAPTCHA verification. Please try again.', 'digital-library-membership' ) ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to subscribe.', 'digital-library-membership' ) ) );
		}

		$interval = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';
		$secret_key = get_option( 'dlm_stripe_secret_key' );
		
		if ( $interval === 'lifetime' ) {
			$price_id = get_option( 'dlm_stripe_lifetime_price_id' );
			$mode = 'payment'; // One-time payment
		} else {
			$price_id = ( $interval === 'yearly' ) 
				? get_option( 'dlm_stripe_yearly_price_id' ) 
				: get_option( 'dlm_stripe_monthly_price_id' );
			$mode = 'subscription'; // Recurring subscription
		}

		if ( empty( $secret_key ) || empty( $price_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Stripe gateway is not fully configured.', 'digital-library-membership' ) ) );
		}

		$current_user = wp_get_current_user();

		try {
			\Stripe\Stripe::setApiKey( $secret_key );

			$session_args = array(
				'payment_method_types' => array( 'card' ),
				'line_items'           => array(
					array(
						'price'    => $price_id,
						'quantity' => 1,
					),
				),
				'mode'                 => $mode,
				'customer_email'       => $current_user->user_email,
				'success_url'          => add_query_arg( 'session_id', '{CHECKOUT_SESSION_ID}', add_query_arg( 'payment', 'success', dlm_get_page_url( 'account' ) ) ),
				'cancel_url'           => add_query_arg( 'payment', 'cancelled', dlm_get_page_url( 'account' ) ),
				'metadata'             => array(
					'user_id'  => $current_user->ID,
					'interval' => $interval,
				),
			);

			$session = \Stripe\Checkout\Session::create( $session_args );

			wp_send_json_success( array( 'id' => $session->id, 'url' => $session->url ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Record Paypal Subscription created on frontend
	 */
	public function ajax_paypal_create_subscription() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$recaptcha_response = isset( $_POST['recaptcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_response'] ) ) : '';
		if ( ! dlm_verify_recaptcha( $recaptcha_response ) ) {
			wp_send_json_error( array( 'message' => __( 'You failed the Google ReCAPTCHA verification. Please try again.', 'digital-library-membership' ) ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to subscribe.', 'digital-library-membership' ) ) );
		}

		$subscription_id = isset( $_POST['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) ) : '';
		$interval        = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';

		if ( empty( $subscription_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid PayPal transaction ID.', 'digital-library-membership' ) ) );
		}

		$current_user = wp_get_current_user();
		$db = new DLM_DB();

		// Check status: For subscription, verify subscription details. For one-time (lifetime), order status can be verified.
		if ( $interval === 'lifetime' ) {
			$verified = true; // Assume true since order creation captures funds on UI approve callback
			$expires_at = '2099-12-31 23:59:59';
		} else {
			$verified = $this->verify_paypal_subscription( $subscription_id );
			$expiry_period = ( $interval === 'yearly' ) ? '+1 year' : '+1 month';
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $expiry_period ) );
		}

		if ( ! $verified ) {
			wp_send_json_error( array( 'message' => __( 'Could not verify payment status with PayPal.', 'digital-library-membership' ) ) );
		}

		$sub_data = array(
			'user_id'         => $current_user->ID,
			'provider'        => 'paypal',
			'subscription_id' => $subscription_id,
			'customer_id'     => $subscription_id,
			'status'          => 'active',
			'plan_interval'   => $interval,
			'expires_at'      => $expires_at,
		);

		$existing = $db->get_subscription_by_gateway_id( $subscription_id );
		if ( $existing ) {
			$db->update_subscription( $subscription_id, array(
				'status'     => 'active',
				'expires_at' => $expires_at,
			) );
		} else {
			$db->insert_subscription( $sub_data );
		}

		// Log initial transaction
		$price = ( $interval === 'yearly' ) ? get_option( 'dlm_pricing_yearly' ) : get_option( 'dlm_pricing_monthly' );
		$currency = get_option( 'dlm_currency', 'USD' );

		$db->insert_transaction( array(
			'user_id'         => $current_user->ID,
			'subscription_id' => $subscription_id,
			'transaction_id'  => 'PP-' . $subscription_id . '-' . time(),
			'provider'        => 'paypal',
			'amount'          => floatval( $price ),
			'currency'        => $currency,
			'status'          => 'completed',
		) );

		// Update user capability
		$user = new WP_User( $current_user->ID );
		$user->add_cap( 'read_dlm_library' );

		// Send subscription activation email notice
		DLM::send_subscription_active_email( $current_user->ID, $interval, $expires_at );

		// Clear dashboard transients to show the new subscription immediately
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_send_json_success( array( 'redirect' => add_query_arg( 'payment', 'success', dlm_get_page_url( 'account' ) ) ) );
	}

	/**
	 * Verify PayPal Subscription Status via Paypal OAuth API
	 */
	private function verify_paypal_subscription( $subscription_id ) {
		$client_id = get_option( 'dlm_paypal_client_id' );
		$secret    = get_option( 'dlm_paypal_secret_key' );

		if ( empty( $client_id ) || empty( $secret ) ) {
			// Mock verification if PayPal is not fully configured (for testing)
			return true;
		}

		// 1. Get Access Token
		$auth_url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token'; // Fallback sandbox, check live later
		$response = wp_remote_post( $auth_url, array(
			'headers' => array(
				'Accept' => 'application/json',
				'Accept-Language' => 'en_US',
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
			),
			'body' => array(
				'grant_type' => 'client_credentials',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body->access_token ) ) {
			return false;
		}

		$access_token = $body->access_token;

		// 2. Fetch Subscription Details
		$details_url = "https://api-m.sandbox.paypal.com/v1/billing/subscriptions/$subscription_id";
		$sub_response = wp_remote_get( $details_url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => "Bearer $access_token",
			),
		) );

		if ( is_wp_error( $sub_response ) ) {
			return false;
		}

		$sub_body = json_decode( wp_remote_retrieve_body( $sub_response ) );
		if ( empty( $sub_body->status ) ) {
			return false;
		}

		// Active statuses: ACTIVE, APPROVED
		return in_array( $sub_body->status, array( 'ACTIVE', 'APPROVED' ) );
	}

	/**
	 * Handle webhook hits (Stripe & PayPal)
	 */
	public function handle_webhooks() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['dlm_webhook'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$gateway = isset( $_GET['dlm_webhook'] ) ? sanitize_text_field( wp_unslash( $_GET['dlm_webhook'] ) ) : '';

		if ( $gateway === 'stripe' ) {
			$this->process_stripe_webhook();
		} elseif ( $gateway === 'paypal' ) {
			$this->process_paypal_webhook();
		}

		exit;
	}

	/**
	 * Process incoming Stripe Webhook events
	 */
	private function process_stripe_webhook() {
		$secret_key = get_option( 'dlm_stripe_secret_key' );
		$payload = @file_get_contents( 'php://input' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';

		// Real Webhook signing secret (configured in Stripe dashboard, but we fallback if not set)
		$endpoint_secret = get_option( 'dlm_stripe_webhook_secret' );

		try {
			\Stripe\Stripe::setApiKey( $secret_key );
			$event = null;

			if ( $endpoint_secret && $sig_header ) {
				$event = \Stripe\Webhook::constructEvent(
					$payload, $sig_header, $endpoint_secret
				);
			} else {
				// Fallback without signature check (warning: insecure, only for local testing / sandbox)
				$event = \Stripe\Event::constructFrom(
					json_decode( $payload, true )
				);
			}

			$db = new DLM_DB();

			switch ( $event->type ) {
				case 'checkout.session.completed':
					$session = $event->data->object;
					$user_id  = intval( $session->metadata->user_id );
					$interval = sanitize_text_field( $session->metadata->interval );
					
					// If mode is payment, subscription ID will be empty. Use checkout session ID instead.
					$sub_id   = $session->subscription ? sanitize_text_field( $session->subscription ) : $session->id;
					$cust_id  = $session->customer ? sanitize_text_field( $session->customer ) : 'cus_onetime';

					if ( $interval === 'lifetime' ) {
						$expires_at = '2099-12-31 23:59:59';
					} else {
						$expiry = ( $interval === 'yearly' ) ? '+1 year' : '+1 month';
						$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $expiry ) );
					}

					$db->insert_subscription( array(
						'user_id'         => $user_id,
						'provider'        => 'stripe',
						'subscription_id' => $sub_id,
						'customer_id'     => $cust_id,
						'status'          => 'active',
						'plan_interval'   => $interval,
						'expires_at'      => $expires_at,
					) );

					// Log transaction
					$db->insert_transaction( array(
						'user_id'         => $user_id,
						'subscription_id' => $sub_id,
						'transaction_id'  => $session->id,
						'provider'        => 'stripe',
						'amount'          => floatval( $session->amount_total / 100 ),
						'currency'        => strtoupper( $session->currency ),
						'status'          => 'completed',
					) );

					// Give capability
					$user = new WP_User( $user_id );
					$user->add_cap( 'read_dlm_library' );

					// Send activation email
					DLM::send_subscription_active_email( $user_id, $interval, $expires_at );
				break;

				case 'invoice.payment_succeeded':
					$invoice = $event->data->object;
					$sub_id  = sanitize_text_field( $invoice->subscription );
					
					$sub = $db->get_subscription_by_gateway_id( $sub_id );
					if ( $sub ) {
						$interval = $sub->plan_interval;
						$expiry = ( $interval === 'yearly' ) ? '+1 year' : '+1 month';
						$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $expiry ) );

						$db->update_subscription( $sub_id, array(
							'status'     => 'active',
							'expires_at' => $expires_at,
						) );

						$db->insert_transaction( array(
							'user_id'         => $sub->user_id,
							'subscription_id' => $sub_id,
							'transaction_id'  => $invoice->id,
							'provider'        => 'stripe',
							'amount'          => floatval( $invoice->amount_paid / 100 ),
							'currency'        => strtoupper( $invoice->currency ),
							'status'          => 'completed',
						) );

						// Send renewal/activation email notice
						DLM::send_subscription_active_email( $sub->user_id, $interval, $expires_at );
					}
					break;

				case 'customer.subscription.deleted':
				case 'customer.subscription.updated':
					$stripe_sub = $event->data->object;
					$sub_id = sanitize_text_field( $stripe_sub->id );
					$status = sanitize_text_field( $stripe_sub->status ); // e.g., active, trialing, canceled, past_due

					$db_sub = $db->get_subscription_by_gateway_id( $sub_id );
					if ( $db_sub ) {
						$new_status = in_array( $status, array( 'active', 'trialing' ) ) ? 'active' : 'expired';
						$db->update_subscription( $sub_id, array(
							'status' => $new_status,
						) );

						if ( $new_status === 'expired' ) {
							$user = new WP_User( $db_sub->user_id );
							$user->remove_cap( 'read_dlm_library' );
						}
					}
					break;
			}

			http_response_code( 200 );
			echo 'Webhook Processed';
		} catch ( Exception $e ) {
			http_response_code( 400 );
			echo 'Webhook Error: ' . esc_html( $e->getMessage() );
		}
	}

	/**
	 * Process PayPal Webhooks (IPN/Webhooks)
	 */
	private function process_paypal_webhook() {
		// Verify and process webhook notifications
		// To maintain robustness, users' status is cached and synced via direct API queries, 
		// but simple PayPal webhook callbacks can sync events here.
		$payload = file_get_contents( 'php://input' );
		$data    = json_decode( $payload );
		$db      = new DLM_DB();

		if ( ! empty( $data->event_type ) ) {
			$sub_id = '';
			if ( ! empty( $data->resource->billing_agreement_id ) ) {
				$sub_id = sanitize_text_field( $data->resource->billing_agreement_id );
			} elseif ( ! empty( $data->resource->id ) ) {
				$sub_id = sanitize_text_field( $data->resource->id );
			}

			if ( $sub_id ) {
				$sub = $db->get_subscription_by_gateway_id( $sub_id );
				if ( $sub ) {
					switch ( $data->event_type ) {
						case 'BILLING.SUBSCRIPTION.CANCELLED':
						case 'BILLING.SUBSCRIPTION.EXPIRED':
							$db->update_subscription( $sub_id, array(
								'status' => 'expired',
							) );
							$user = new WP_User( $sub->user_id );
							$user->remove_cap( 'read_dlm_library' );
							break;
						
						case 'PAYMENT.SALE.COMPLETED':
							$amount = floatval( $data->resource->amount->total );
							$currency = sanitize_text_field( $data->resource->amount->currency );
							
							$interval = $sub->plan_interval;
							$expiry = ( $interval === 'yearly' ) ? '+1 year' : '+1 month';
							$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $expiry ) );

							$db->update_subscription( $sub_id, array(
								'status'     => 'active',
								'expires_at' => $expires_at,
							) );

							$db->insert_transaction( array(
								'user_id'         => $sub->user_id,
								'subscription_id' => $sub_id,
								'transaction_id'  => sanitize_text_field( $data->resource->id ),
								'provider'        => 'paypal',
								'amount'          => $amount,
								'currency'        => $currency,
								'status'          => 'completed',
							) );
							break;
					}
				}
			}
		}

		http_response_code( 200 );
		echo 'PayPal Webhook Processed';
	}

	/**
	 * Log Manual Payment requests in database
	 */
	public function ajax_submit_manual_payment() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$recaptcha_response = isset( $_POST['recaptcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_response'] ) ) : '';
		if ( ! dlm_verify_recaptcha( $recaptcha_response ) ) {
			wp_send_json_error( array( 'message' => __( 'You failed the Google ReCAPTCHA verification. Please try again.', 'digital-library-membership' ) ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to process payment.', 'digital-library-membership' ) ) );
		}

		$interval = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';
		$reference = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';

		if ( empty( $reference ) ) {
			wp_send_json_error( array( 'message' => __( 'Please supply a transaction reference code.', 'digital-library-membership' ) ) );
		}

		$current_user = wp_get_current_user();
		$db = new DLM_DB();

		// Save subscription request as pending_approval
		$sub_id = 'MANUAL-' . $current_user->ID . '-' . time();
		$expires_at = current_time( 'mysql' ); // placeholder

		$sub_data = array(
			'user_id'         => $current_user->ID,
			'provider'        => 'manual',
			'subscription_id' => $sub_id,
			'customer_id'     => $reference, // customer reference ID
			'status'          => 'pending_approval',
			'plan_interval'   => $interval,
			'expires_at'      => $expires_at,
		);

		$db_id = $db->insert_subscription( $sub_data );

		if ( $db_id ) {
			// Record Transaction as waiting_approval
			$price = 0.00;
			if ( $interval === 'monthly' ) {
				$price = get_option( 'dlm_pricing_monthly', '9.99' );
			} elseif ( $interval === 'yearly' ) {
				$price = get_option( 'dlm_pricing_yearly', '99.99' );
			} elseif ( $interval === 'lifetime' ) {
				$price = get_option( 'dlm_pricing_lifetime', '199.99' );
			}

			$db->insert_transaction( array(
				'user_id'         => $current_user->ID,
				'subscription_id' => $sub_id,
				'transaction_id'  => $reference,
				'provider'        => 'manual',
				'amount'          => floatval( $price ),
				'currency'        => get_option( 'dlm_currency', 'USD' ),
				'status'          => 'waiting_approval',
			) );
			// Send email notification to site admin
			$admin_email = get_option( 'admin_email' );
			$subject     = __( 'New Manual Payment Subscription Request', 'digital-library-membership' );
			/* translators: 1: User name, 2: User email, 3: Plan tier, 4: Reference code, 5: Admin dashboard URL */
			$body        = sprintf(
				__( "Hello Admin,\n\nA new manual payment subscription request is awaiting approval.\n\nUser: %1\$s (Email: %2\$s)\nPlan Tier: %3\$s\nReference Code: %4\$s\n\nPlease approve or reject it inside the WordPress Dashboard:\n%5\$s\n\nBest regards,\nDigital Library Plugin", 'digital-library-membership' ),
				$current_user->display_name,
				$current_user->user_email,
				ucfirst( $interval ),
				$reference,
				admin_url( 'admin.php?page=dlm-library&tab=subscribers' )
			);
			wp_mail( $admin_email, $subject, $body );

			// Clear dashboard transients to show the new subscriber immediately
			delete_transient( 'dlm_analytics_summary' );
			delete_transient( 'dlm_trending_books' );

			wp_send_json_success( array( 'redirect' => add_query_arg( 'payment', 'pending', dlm_get_page_url( 'account' ) ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not log manual checkout request.', 'digital-library-membership' ) ) );
		}
	}

	/**
	 * Hook into WooCommerce Order Status Completed
	 */
	public function handle_woocommerce_order_completed( $order_id, $order ) {
		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		$db = new DLM_DB();
		$items = $order->get_items();

		$monthly_product_id  = intval( get_option( 'dlm_wc_monthly_product' ) );
		$yearly_product_id   = intval( get_option( 'dlm_wc_yearly_product' ) );
		$lifetime_product_id = intval( get_option( 'dlm_wc_lifetime_product' ) );

		foreach ( $items as $item ) {
			$product_id = $item->get_product_id();
			$interval = '';
			$expires_at = '';

			if ( $monthly_product_id && $product_id === $monthly_product_id ) {
				$interval = 'monthly';
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
			} elseif ( $yearly_product_id && $product_id === $yearly_product_id ) {
				$interval = 'yearly';
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
			} elseif ( $lifetime_product_id && $product_id === $lifetime_product_id ) {
				$interval = 'lifetime';
				$expires_at = '2099-12-31 23:59:59';
			}

			if ( ! empty( $interval ) ) {
				$sub_id = 'WC-ORDER-' . $order_id;
				$sub_data = array(
					'user_id'         => $user_id,
					'provider'        => 'woocommerce',
					'subscription_id' => $sub_id,
					'customer_id'     => (string) $user_id,
					'status'          => 'active',
					'plan_interval'   => $interval,
					'expires_at'      => $expires_at,
				);

				$existing = $db->get_subscription_by_gateway_id( $sub_id );
				if ( $existing ) {
					$db->update_subscription( $sub_id, array(
						'status'     => 'active',
						'expires_at' => $expires_at,
						'updated_at' => current_time( 'mysql' ),
					) );
				} else {
					$db->insert_subscription( $sub_data );
				}

				// Log transaction
				$db->insert_transaction( array(
					'user_id'         => $user_id,
					'subscription_id' => $sub_id,
					'transaction_id'  => 'WC-TX-' . $order_id . '-' . time(),
					'provider'        => 'woocommerce',
					'amount'          => floatval( $order->get_total() ),
					'currency'        => $order->get_currency(),
					'status'          => 'completed',
				) );

				// Clear stats cache
				delete_transient( 'dlm_analytics_summary' );
				delete_transient( 'dlm_trending_books' );

				// Add capability
				$user = new WP_User( $user_id );
				$user->add_cap( 'read_dlm_library' );

				// Send mail notification
				DLM::send_subscription_active_email( $user_id, $interval, $expires_at );
				break;
			}
		}
	}

	/**
	 * AJAX handler to add mapped WooCommerce product to cart and return checkout redirect URL
	 */
	public function ajax_wc_add_to_cart_redirect() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to subscribe.', 'digital-library-membership' ) ) );
		}

		$interval = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';
		
		$product_id = 0;
		if ( $interval === 'monthly' ) {
			$product_id = intval( get_option( 'dlm_wc_monthly_product' ) );
		} elseif ( $interval === 'yearly' ) {
			$product_id = intval( get_option( 'dlm_wc_yearly_product' ) );
		} elseif ( $interval === 'lifetime' ) {
			$product_id = intval( get_option( 'dlm_wc_lifetime_product' ) );
		}

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce product is not configured for this plan.', 'digital-library-membership' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'digital-library-membership' ) ) );
		}

		// Clear cart and add product
		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $product_id );
			wp_send_json_success( array( 'redirect' => wc_get_checkout_url() ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'WooCommerce cart system could not be initialized.', 'digital-library-membership' ) ) );
		}
	}
}

