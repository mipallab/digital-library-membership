<?php
/**
 * Headless WooCommerce Payment Engine Manager
 *
 * Handles virtual product synchronization, direct WC_Order creation (skipping cart),
 * custom order-pay template overrides, payment completion access granting, and refund revocation.
 *
 * @since      2.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_WooCommerce {

	/**
	 * Database manager instance
	 *
	 * @var DLM_DB
	 */
	private $db;

	/**
	 * Checkout manager instance
	 *
	 * @var DLM_Checkout
	 */
	private $checkout;

	/**
	 * Constructor
	 */
	public function __construct( $db, $checkout ) {
		$this->db       = $db;
		$this->checkout = $checkout;
	}

	/**
	 * Initialize hooks and filters
	 */
	public function init() {
		// AJAX endpoints for headless order creation
		add_action( 'wp_ajax_dlm_wc_create_book_order', array( $this, 'ajax_create_book_order' ) );
		add_action( 'wp_ajax_nopriv_dlm_wc_create_book_order', array( $this, 'ajax_create_book_order' ) );
		add_action( 'wp_ajax_dlm_wc_create_subscription_order', array( $this, 'ajax_create_subscription_order' ) );
		add_action( 'wp_ajax_nopriv_dlm_wc_create_subscription_order', array( $this, 'ajax_create_subscription_order' ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Payment completion hooks
		add_action( 'woocommerce_payment_complete', array( $this, 'handle_order_payment_completed' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_status_completed' ), 10, 2 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'handle_order_status_completed' ), 10, 2 );

		// Refund and cancellation hooks for immediate access revocation
		add_action( 'woocommerce_order_refunded', array( $this, 'handle_order_refunded' ), 10, 2 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'handle_order_status_refunded' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'handle_order_status_refunded' ), 10, 1 );

		// Headless template override for checkout/order-pay
		add_filter( 'wc_get_template', array( $this, 'override_order_pay_template' ), 20, 5 );
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_order_pay_template' ), 20, 3 );

		// Custom return redirect URL for DLM orders
		add_filter( 'woocommerce_get_return_url', array( $this, 'filter_order_return_url' ), 20, 2 );
	}

	/**
	 * Synchronize a book with a linked WooCommerce virtual product
	 *
	 * @param int   $book_id
	 * @param array $book_data
	 * @return int Linked Product ID
	 */
	public function sync_book_wc_product( $book_id, $book_data ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}

		$access_type = isset( $book_data['access_type'] ) ? $book_data['access_type'] : 'subscription_only';
		$price       = isset( $book_data['price'] ) ? floatval( $book_data['price'] ) : 0.00;
		$title       = ! empty( $book_data['title'] ) ? sanitize_text_field( $book_data['title'] ) : '';

		// If access type is subscription_only, no individual purchase product is needed
		if ( $access_type === 'subscription_only' ) {
			return 0;
		}

		$existing_product_id = 0;
		$book = $this->db->get_book( $book_id );
		if ( $book && ! empty( $book->wc_product_id ) ) {
			$existing_product_id = intval( $book->wc_product_id );
		}

		// Check if product exists in WordPress
		$product = null;
		if ( $existing_product_id ) {
			$product = wc_get_product( $existing_product_id );
		}

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			$product = new WC_Product_Simple();
		}

		$product->set_name( $title );
		$product->set_regular_price( strval( $price ) );
		$product->set_price( strval( $price ) );
		$product->set_virtual( true );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' ); // Never visible in shop or search

		if ( ! empty( $book_data['description'] ) ) {
			$product->set_short_description( wp_strip_all_tags( $book_data['description'] ) );
		}

		// Set cover image if available
		if ( ! empty( $book_data['cover_image_url'] ) ) {
			$attach_id = attachment_url_to_postid( $book_data['cover_image_url'] );
			if ( $attach_id ) {
				$product->set_image_id( $attach_id );
			}
		}

		$product_id = $product->save();

		if ( $product_id ) {
			update_post_meta( $product_id, '_linked_book_id', $book_id );
			update_post_meta( $product_id, '_dlm_virtual_book', 'yes' );
			
			// Update book record with linked wc_product_id
			$this->db->update_book( $book_id, array( 'wc_product_id' => $product_id ) );
		}

		return $product_id;
	}

	/**
	 * Get or create a WooCommerce virtual product for subscription intervals
	 *
	 * @param string $interval ('monthly', 'yearly', 'lifetime')
	 * @return int Product ID
	 */
	public function get_or_create_subscription_wc_product( $interval ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}

		$option_key = 'dlm_wc_' . $interval . '_product';
		$product_id = intval( get_option( $option_key ) );

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && is_a( $product, 'WC_Product' ) ) {
				return $product_id;
			}
		}

		// Auto-generate virtual product for subscription plan if none configured
		$price = '9.99';
		$name  = 'Digital Library - Monthly Membership';

		if ( $interval === 'yearly' ) {
			$price = get_option( 'dlm_pricing_yearly', '99.99' );
			$name  = 'Digital Library - Yearly Membership';
		} elseif ( $interval === 'lifetime' ) {
			$price = get_option( 'dlm_pricing_lifetime', '199.99' );
			$name  = 'Digital Library - Lifetime Access';
		} else {
			$price = get_option( 'dlm_pricing_monthly', '9.99' );
		}

		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( strval( $price ) );
		$product->set_price( strval( $price ) );
		$product->set_virtual( true );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );

		$new_product_id = $product->save();
		if ( $new_product_id ) {
			update_post_meta( $new_product_id, '_dlm_subscription_interval', $interval );
			update_option( $option_key, $new_product_id );
			return $new_product_id;
		}

		return 0;
	}

	/**
	 * AJAX handler: Create headless WC_Order for individual book purchase
	 */
	public function ajax_create_book_order() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please sign in or register to purchase books.', 'digital-library-membership' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce payment engine is not available.', 'digital-library-membership' ) ) );
		}

		$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
		if ( ! $book_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid book selection.', 'digital-library-membership' ) ) );
		}

		$book = $this->db->get_book( $book_id );
		if ( ! $book ) {
			wp_send_json_error( array( 'message' => __( 'Book not found.', 'digital-library-membership' ) ) );
		}

		$user_id = get_current_user_id();

		// Check if already purchased
		if ( $this->db->has_purchased_book( $user_id, $book_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You already own this book! Opening reader...', 'digital-library-membership' ), 'already_owned' => true, 'read_url' => home_url( '/read/' . $book_id . '/' ) ) );
		}

		// Ensure linked WooCommerce product exists
		$product_id = intval( $book->wc_product_id );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			$product_id = $this->sync_book_wc_product( $book_id, array(
				'title'       => $book->title,
				'access_type' => $book->access_type,
				'price'       => $book->price,
				'description' => $book->description,
				'cover_image_url' => $book->cover_image_url,
			) );
			$product = $product_id ? wc_get_product( $product_id ) : null;
		}

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Could not initialize payment item for this book.', 'digital-library-membership' ) ) );
		}

		$user = wp_get_current_user();

		try {
			// Create WC_Order directly in PHP without touching cart
			$order = wc_create_order( array( 'customer_id' => $user->ID ) );
			$order->add_product( $product, 1 );

			$order->set_address( array(
				'first_name' => $user->first_name ?: $user->display_name,
				'last_name'  => $user->last_name ?: '',
				'email'      => $user->user_email,
			), 'billing' );

			$order->update_meta_data( '_dlm_order_type', 'book_purchase' );
			$order->update_meta_data( '_dlm_book_id', $book_id );
			$order->calculate_totals();
			$order->update_status( 'pending', __( 'DLM headless pay-per-book order initialized.', 'digital-library-membership' ) );

			// Insert pending purchase record into plugin database
			$currency = get_option( 'dlm_currency', 'USD' );
			$this->db->insert_book_purchase( array(
				'user_id'        => $user->ID,
				'book_id'        => $book_id,
				'order_id'       => (string) $order->get_id(),
				'amount'         => floatval( $order->get_total() ),
				'currency'       => $currency,
				'payment_engine' => 'woocommerce',
				'status'         => 'pending',
			) );

			$pay_url = $order->get_checkout_payment_url();
			wp_send_json_success( array(
				'redirect' => $pay_url,
				'order_id' => $order->get_id(),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler: Create headless WC_Order for subscription plan purchase
	 */
	public function ajax_create_subscription_order() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please sign in or register to subscribe.', 'digital-library-membership' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce payment engine is not available.', 'digital-library-membership' ) ) );
		}

		$interval = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';
		if ( ! in_array( $interval, array( 'monthly', 'yearly', 'lifetime' ), true ) ) {
			$interval = 'monthly';
		}

		$product_id = $this->get_or_create_subscription_wc_product( $interval );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Could not find or create membership plan item in WooCommerce.', 'digital-library-membership' ) ) );
		}

		$user = wp_get_current_user();

		try {
			// Create WC_Order directly in PHP without touching cart
			$order = wc_create_order( array( 'customer_id' => $user->ID ) );
			$order->add_product( $product, 1 );

			$order->set_address( array(
				'first_name' => $user->first_name ?: $user->display_name,
				'last_name'  => $user->last_name ?: '',
				'email'      => $user->user_email,
			), 'billing' );

			$order->update_meta_data( '_dlm_order_type', 'subscription' );
			$order->update_meta_data( '_dlm_plan_interval', $interval );
			$order->calculate_totals();
			$order->update_status( 'pending', __( 'DLM headless subscription order initialized.', 'digital-library-membership' ) );

			$pay_url = $order->get_checkout_payment_url();
			wp_send_json_success( array(
				'redirect' => $pay_url,
				'order_id' => $order->get_id(),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle order payment complete hook
	 */
	public function handle_order_payment_completed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$this->process_completed_order( $order );
	}

	/**
	 * Handle order status completed / processing hook
	 */
	public function handle_order_status_completed( $order_id, $order = null ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		$this->process_completed_order( $order );
	}

	/**
	 * Process successful order fulfillment
	 */
	private function process_completed_order( $order ) {
		$order_id   = $order->get_id();
		$user_id    = $order->get_user_id();
		$order_type = $order->get_meta( '_dlm_order_type' );

		if ( ! $user_id ) {
			return;
		}

		if ( $order_type === 'book_purchase' ) {
			$book_id = intval( $order->get_meta( '_dlm_book_id' ) );
			if ( $book_id ) {
				$currency = get_option( 'dlm_currency', 'USD' );

				// Update or insert book purchase log
				$this->db->insert_book_purchase( array(
					'user_id'        => $user_id,
					'book_id'        => $book_id,
					'order_id'       => (string) $order_id,
					'amount'         => floatval( $order->get_total() ),
					'currency'       => $currency,
					'payment_engine' => 'woocommerce',
					'status'         => 'completed',
				) );

				// Record financial transaction
				$this->db->insert_transaction( array(
					'user_id'         => $user_id,
					'subscription_id' => 'BOOK-' . $book_id,
					'transaction_id'  => 'WC-BOOK-' . $order_id . '-' . time(),
					'provider'        => 'woocommerce',
					'amount'          => floatval( $order->get_total() ),
					'currency'        => $currency,
					'status'          => 'completed',
				) );

				delete_transient( 'dlm_analytics_summary' );
				delete_transient( 'dlm_trending_books' );

				// Send confirmation email
				$book = $this->db->get_book( $book_id );
				$user = get_userdata( $user_id );
				if ( $book && $user ) {
					/* translators: %s: Book title */
					$subject = sprintf( __( 'Book Purchase Confirmed: %s', 'digital-library-membership' ), $book->title );
					$body    = sprintf(
						/* translators: 1: User display name, 2: Book title, 3: Book URL */
						__( "Hello %1\$s,\n\nThank you for purchasing \"%2\$s\"!\n\nYou now have permanent online reading and PDF download access to this title.\n\nRead your book here:\n%3\$s\n\nBest regards,\nDigital Library Team", 'digital-library-membership' ),
						$user->display_name,
						$book->title,
						home_url( '/read/' . $book_id . '/' )
					);
					wp_mail( $user->user_email, $subject, $body );
				}
			}
		} elseif ( $order_type === 'subscription' ) {
			$interval = sanitize_text_field( $order->get_meta( '_dlm_plan_interval' ) );
			if ( empty( $interval ) ) {
				$interval = 'monthly';
			}

			$expires_at = '2099-12-31 23:59:59';
			if ( $interval === 'monthly' ) {
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
			} elseif ( $interval === 'yearly' ) {
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
			}

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

			$existing = $this->db->get_subscription_by_gateway_id( $sub_id );
			if ( $existing ) {
				$this->db->update_subscription( $sub_id, array(
					'status'     => 'active',
					'expires_at' => $expires_at,
					'updated_at' => current_time( 'mysql' ),
				) );
			} else {
				$this->db->insert_subscription( $sub_data );
			}

			// Record financial transaction
			$currency = get_option( 'dlm_currency', 'USD' );
			$this->db->insert_transaction( array(
				'user_id'         => $user_id,
				'subscription_id' => $sub_id,
				'transaction_id'  => 'WC-SUB-' . $order_id . '-' . time(),
				'provider'        => 'woocommerce',
				'amount'          => floatval( $order->get_total() ),
				'currency'        => $currency,
				'status'          => 'completed',
			) );

			delete_transient( 'dlm_analytics_summary' );
			delete_transient( 'dlm_trending_books' );

			// Grant capability
			$wp_user = new WP_User( $user_id );
			$wp_user->add_cap( 'read_dlm_library' );

			DLM::send_subscription_active_email( $user_id, $interval, $expires_at );
		} else {
			// Check if items match legacy mapped subscription products
			$this->checkout->handle_woocommerce_order_completed( $order_id, $order );
		}
	}

	/**
	 * Handle order refund hook - immediately revokes access
	 */
	public function handle_order_refunded( $order_id, $refund_id = 0 ) {
		$this->process_refunded_order( $order_id );
	}

	/**
	 * Handle order status refunded/cancelled hook
	 */
	public function handle_order_status_refunded( $order_id ) {
		$this->process_refunded_order( $order_id );
	}

	/**
	 * Revoke access on refund
	 */
	private function process_refunded_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id    = $order->get_user_id();
		$order_type = $order->get_meta( '_dlm_order_type' );

		if ( $order_type === 'book_purchase' ) {
			$this->db->refund_book_purchase( (string) $order_id );
			delete_transient( 'dlm_analytics_summary' );
			delete_transient( 'dlm_trending_books' );
		} elseif ( $order_type === 'subscription' ) {
			$sub_id = 'WC-ORDER-' . $order_id;
			$this->db->update_subscription( $sub_id, array(
				'status'     => 'refunded',
				'updated_at' => current_time( 'mysql' ),
			) );

			if ( $user_id ) {
				$wp_user = new WP_User( $user_id );
				$wp_user->remove_cap( 'read_dlm_library' );
			}

			delete_transient( 'dlm_analytics_summary' );
			delete_transient( 'dlm_trending_books' );
		}
	}

	/**
	 * Template override: locate checkout/form-pay.php
	 */
	public function locate_order_pay_template( $template, $template_name, $template_path ) {
		if ( 'checkout/form-pay.php' === $template_name ) {
			$custom_pay_template = DLM_PATH . 'templates/woocommerce/checkout/form-pay.php';
			if ( file_exists( $custom_pay_template ) ) {
				return $custom_pay_template;
			}
		}
		return $template;
	}

	/**
	 * Template override filter via wc_get_template
	 */
	public function override_order_pay_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'checkout/form-pay.php' === $template_name ) {
			$custom_pay_template = DLM_PATH . 'templates/woocommerce/checkout/form-pay.php';
			if ( file_exists( $custom_pay_template ) ) {
				return $custom_pay_template;
			}
		}
		return $located;
	}

	/**
	 * Custom return redirect URL filter for DLM orders
	 */
	public function filter_order_return_url( $return_url, $order ) {
		if ( ! $order ) {
			return $return_url;
		}

		$order_type = $order->get_meta( '_dlm_order_type' );
		if ( ! empty( $order_type ) ) {
			$account_url = dlm_get_page_url( 'account' );
			return add_query_arg(
				array(
					'payment'  => 'success',
					'order_id' => $order->get_id(),
				),
				$account_url
			);
		}

		return $return_url;
	}
}
