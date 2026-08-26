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

		// Template override for WooCommerce native checkout (form-checkout.php)
		add_filter( 'wc_get_template', array( $this, 'override_checkout_template' ), 20, 5 );
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_checkout_template' ), 20, 3 );

		// Enqueue luxury Library Checkout styles on WooCommerce checkout page
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_styles' ), 20 );

		// Clean unneeded checkout fields (company, order notes, shipping) for sleek digital checkout
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 20 );

		// Ensure essential WooCommerce pages exist
		add_action( 'init', array( $this, 'ensure_woocommerce_pages_exist' ), 5 );

		// Transfer cart custom metadata to order line item during WooCommerce checkout
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'transfer_cart_item_meta_to_order' ), 10, 4 );

		// Force account registration to be strictly required on checkout (Guest checkout disabled for digital access)
		add_filter( 'woocommerce_checkout_registration_required', '__return_true', 999 );
		add_filter( 'woocommerce_checkout_registration_enabled', '__return_true', 999 );

		// Return & Cancel URL overrides to return cleanly to DLM Library Account dashboard
		add_filter( 'woocommerce_get_return_url', array( $this, 'filter_order_return_url' ), 999, 2 );
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'filter_order_return_url' ), 999, 2 );
		add_filter( 'woocommerce_get_cancel_url', array( $this, 'filter_order_cancel_url' ), 999, 2 );
		add_filter( 'woocommerce_get_cancel_url_bare', array( $this, 'filter_order_cancel_url' ), 999, 2 );

		// Redirect standard non-checkout WooCommerce views (Shop, Cart, My Account) to DLM Library Account
		add_action( 'template_redirect', array( $this, 'redirect_woocommerce_pages' ), 1 );

		// Redirect standard WooCommerce auth / return-to-shop redirects to Library Account
		add_filter( 'woocommerce_login_redirect', array( $this, 'filter_woocommerce_auth_redirect' ), 20, 2 );
		add_filter( 'woocommerce_registration_redirect', array( $this, 'filter_woocommerce_auth_redirect' ), 20, 1 );
		add_filter( 'woocommerce_return_to_shop_redirect', array( $this, 'filter_woocommerce_shop_redirect' ), 20, 1 );
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
	 * Get or create a WooCommerce virtual product for any subscription package
	 *
	 * @param string $package_id_or_interval Package ID or interval ('monthly', 'yearly', 'lifetime', etc.)
	 * @return int Product ID
	 */
	public function get_or_create_subscription_wc_product( $package_id_or_interval ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}

		$pkg = dlm_get_package( $package_id_or_interval );
		if ( ! $pkg ) {
			$packages = dlm_get_packages();
			$pkg      = reset( $packages );
		}

		$package_id = $pkg ? $pkg['id'] : $package_id_or_interval;
		$product_id = ( $pkg && ! empty( $pkg['wc_product_id'] ) ) ? intval( $pkg['wc_product_id'] ) : 0;

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && is_a( $product, 'WC_Product' ) ) {
				return $product_id;
			}
		}

		// Auto-generate virtual product for subscription package
		$name     = $pkg && ! empty( $pkg['name'] ) ? ( 'Digital Library - ' . $pkg['name'] ) : ( 'Digital Library - ' . ucfirst( $package_id_or_interval ) . ' Membership' );
		$price    = $pkg && isset( $pkg['price'] ) ? strval( $pkg['price'] ) : '9.99';
		$interval = $pkg && ! empty( $pkg['interval'] ) ? $pkg['interval'] : ( in_array( $package_id_or_interval, array( 'monthly', 'yearly', 'lifetime' ), true ) ? $package_id_or_interval : 'monthly' );

		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( strval( $price ) );
		$product->set_price( strval( $price ) );
		$product->set_virtual( true );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' ); // Hidden from shop catalog

		if ( $pkg && ! empty( $pkg['description'] ) ) {
			$product->set_short_description( wp_strip_all_tags( $pkg['description'] ) );
		}

		$new_product_id = $product->save();
		if ( $new_product_id ) {
			update_post_meta( $new_product_id, '_dlm_package_id', $package_id );
			update_post_meta( $new_product_id, '_dlm_subscription_interval', $interval );
			update_post_meta( $new_product_id, '_dlm_virtual_subscription', 'yes' );

			// Link newly created WC product back to package registry
			$packages = dlm_get_packages();
			if ( isset( $packages[ $package_id ] ) ) {
				$packages[ $package_id ]['wc_product_id'] = $new_product_id;
				dlm_save_packages( $packages );
			}

			return $new_product_id;
		}

		return 0;
	}

	/**
	 * AJAX handler: Add book virtual product to WooCommerce cart and return native checkout redirect URL
	 */
	public function ajax_create_book_order() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please sign in or register to purchase books.', 'digital-library-membership' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce payment engine is not available.', 'digital-library-membership' ) ) );
		}

		$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
		if ( ! $book_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid book selection.', 'digital-library-membership' ) ) );
		}

		$book = $this->db->get_book( $book_id );
		if ( ! $book || ( isset( $book->status ) && 'publish' !== $book->status ) ) {
			wp_send_json_error( array( 'message' => __( 'This book is not available for purchase.', 'digital-library-membership' ) ) );
		}

		if ( isset( $book->access_type ) && 'subscription_only' === $book->access_type ) {
			wp_send_json_error( array( 'message' => __( 'This book is exclusive to active subscribers. Please select a membership plan.', 'digital-library-membership' ) ) );
		}

		if ( isset( $book->access_type ) && 'free' === $book->access_type ) {
			wp_send_json_error( array( 'message' => __( 'This book is freely available to read in our library!', 'digital-library-membership' ), 'already_owned' => true, 'read_url' => home_url( '/read/' . $book_id . '/' ) ) );
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

		// Ensure essential WooCommerce checkout pages exist
		$this->ensure_woocommerce_pages_exist();

		try {
			// Clear cart and add the selected book with item metadata
			WC()->cart->empty_cart();
			$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), array(
				'dlm_order_type' => 'book_purchase',
				'dlm_book_id'    => $book_id,
			) );

			if ( ! $cart_item_key ) {
				wp_send_json_error( array( 'message' => __( 'Could not add book to checkout cart.', 'digital-library-membership' ) ) );
			}

			wp_send_json_success( array(
				'redirect' => wc_get_checkout_url(),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler: Add subscription virtual product to WooCommerce cart and return native checkout redirect URL
	 */
	public function ajax_create_subscription_order() {
		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please sign in or register to subscribe.', 'digital-library-membership' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce payment engine is not available.', 'digital-library-membership' ) ) );
		}

		$plan    = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';
		$package = dlm_get_package( $plan );
		if ( ! $package || ( isset( $package['status'] ) && 'inactive' === $package['status'] ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected membership package is not currently available.', 'digital-library-membership' ) ) );
		}

		$package_id = $package['id'];
		$interval   = ! empty( $package['interval'] ) ? $package['interval'] : 'monthly';
		$plan_name  = ! empty( $package['name'] ) ? $package['name'] : ucfirst( $interval );

		// Same-plan detection: if user already has an active subscription on the same interval, reject
		$user_id    = get_current_user_id();
		$existing   = $this->db->get_subscription_by_user( $user_id );
		if ( $existing && in_array( $existing->status, array( 'active', 'trialing' ), true ) ) {
			$existing_interval = ! empty( $existing->plan_interval ) ? $existing->plan_interval : '';
			if ( $existing_interval === $interval ) {
				$dashboard_url = dlm_get_page_url( 'account' );
				wp_send_json_error( array(
					'message' => sprintf(
						/* translators: 1: Plan name, 2: Dashboard URL */
						__( 'You are already subscribed to the %1$s plan. To switch plans, please visit your <a href="%2$s#membership" style="color:#855300;font-weight:600;text-decoration:underline;">Membership Dashboard</a>.', 'digital-library-membership' ),
						esc_html( $plan_name ),
						esc_url( $dashboard_url )
					),
				) );
			}
		}

		$product_id = $this->get_or_create_subscription_wc_product( $package_id );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Could not find or create membership plan item in WooCommerce.', 'digital-library-membership' ) ) );
		}

		// Ensure essential WooCommerce checkout pages exist
		$this->ensure_woocommerce_pages_exist();

		try {
			// Clear cart and add the selected membership package with item metadata
			WC()->cart->empty_cart();
			$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), array(
				'dlm_order_type'    => 'subscription',
				'dlm_package_id'    => $package_id,
				'dlm_plan_interval' => $interval,
			) );

			if ( ! $cart_item_key ) {
				wp_send_json_error( array( 'message' => __( 'Could not add membership plan to checkout cart.', 'digital-library-membership' ) ) );
			}

			wp_send_json_success( array(
				'redirect' => wc_get_checkout_url(),
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

		// Atomic Lock & Idempotency: prevent race conditions between async webhooks and sync browser redirects
		$lock_key = 'dlm_proc_lock_' . $order_id;
		if ( get_transient( $lock_key ) || 'yes' === $order->get_meta( '_dlm_processed' ) ) {
			return;
		}
		set_transient( $lock_key, '1', 60 );

		$order->update_meta_data( '_dlm_processed', 'yes' );
		$order->save();

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
			$package_id = sanitize_text_field( $order->get_meta( '_dlm_package_id' ) );
			$interval   = sanitize_text_field( $order->get_meta( '_dlm_plan_interval' ) );

			if ( empty( $package_id ) ) {
				$package_id = $interval ?: 'monthly';
			}

			$pkg = dlm_get_package( $package_id );
			if ( $pkg && ! empty( $pkg['interval'] ) ) {
				$interval = $pkg['interval'];
			} elseif ( empty( $interval ) ) {
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
				'plan_interval'   => $package_id,
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
	 * Automatically create or restore essential WooCommerce pages (Checkout & Cart) if deleted.
	 */
	public function ensure_woocommerce_pages_exist() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// 1. Ensure published Checkout page exists with classic [woocommerce_checkout] shortcode
		$checkout_page_id = (int) get_option( 'woocommerce_checkout_page_id', 0 );
		$checkout_page    = ( $checkout_page_id > 0 ) ? get_post( $checkout_page_id ) : null;

		if ( ! $checkout_page || 'publish' !== $checkout_page->post_status || 'trash' === $checkout_page->post_status ) {
			$existing_checkout = get_page_by_path( 'checkout' );
			if ( $existing_checkout && 'publish' === $existing_checkout->post_status ) {
				update_option( 'woocommerce_checkout_page_id', (int) $existing_checkout->ID );
				$checkout_page = $existing_checkout;
			} else {
				$new_checkout_id = wp_insert_post( array(
					'post_title'     => __( 'Checkout', 'digital-library-membership' ),
					'post_name'      => 'checkout',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_content'   => '[woocommerce_checkout]',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				) );
				if ( ! is_wp_error( $new_checkout_id ) && $new_checkout_id > 0 ) {
					update_option( 'woocommerce_checkout_page_id', (int) $new_checkout_id );
					$checkout_page = get_post( $new_checkout_id );
				}
			}
		}

		// Ensure checkout page doesn't have Gutenberg Block which ignores PHP template overrides
		if ( $checkout_page && false !== strpos( $checkout_page->post_content, 'wp:woocommerce/checkout' ) ) {
			wp_update_post( array(
				'ID'           => $checkout_page->ID,
				'post_content' => '[woocommerce_checkout]',
			) );
		}

		// 2. Ensure published Cart page exists
		$cart_page_id = (int) get_option( 'woocommerce_cart_page_id', 0 );
		$cart_page    = ( $cart_page_id > 0 ) ? get_post( $cart_page_id ) : null;

		if ( ! $cart_page || 'publish' !== $cart_page->post_status || 'trash' === $cart_page->post_status ) {
			$existing_cart = get_page_by_path( 'cart' );
			if ( $existing_cart && 'publish' === $existing_cart->post_status ) {
				update_option( 'woocommerce_cart_page_id', (int) $existing_cart->ID );
			} else {
				$new_cart_id = wp_insert_post( array(
					'post_title'     => __( 'Cart', 'digital-library-membership' ),
					'post_name'      => 'cart',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_content'   => '[woocommerce_cart]',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				) );
				if ( ! is_wp_error( $new_cart_id ) && $new_cart_id > 0 ) {
					update_option( 'woocommerce_cart_page_id', (int) $new_cart_id );
				}
			}
		}
	}

	/**
	 * Transfer cart custom metadata to order line item during WooCommerce checkout
	 */
	public function transfer_cart_item_meta_to_order( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['dlm_order_type'] ) ) {
			$item->update_meta_data( '_dlm_order_type', $values['dlm_order_type'] );
			$order->update_meta_data( '_dlm_order_type', $values['dlm_order_type'] );
		}
		if ( isset( $values['dlm_package_id'] ) ) {
			$item->update_meta_data( '_dlm_package_id', $values['dlm_package_id'] );
			$order->update_meta_data( '_dlm_package_id', $values['dlm_package_id'] );
		}
		if ( isset( $values['dlm_plan_interval'] ) ) {
			$item->update_meta_data( '_dlm_plan_interval', $values['dlm_plan_interval'] );
			$order->update_meta_data( '_dlm_plan_interval', $values['dlm_plan_interval'] );
		}
		if ( isset( $values['dlm_book_id'] ) ) {
			$item->update_meta_data( '_dlm_book_id', $values['dlm_book_id'] );
			$order->update_meta_data( '_dlm_book_id', $values['dlm_book_id'] );
		}
	}

	/**
	 * Enqueue luxury Library Checkout styles on WooCommerce checkout page
	 */
	public function enqueue_checkout_styles() {
		if ( ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'dlm_is_checkout_page' ) && dlm_is_checkout_page() ) ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'dlm-font-awesome', DLM_URL . 'admin/css/font-awesome.min.css', array(), '6.4.0' );
			wp_enqueue_style( 'dlm-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap', array(), DLM_VERSION );
			wp_enqueue_style( 'dlm-woocommerce-checkout', DLM_URL . 'public/css/dlm-woocommerce-checkout.css', array(), DLM_VERSION );
		}
	}

	/**
	 * Clean up unneeded checkout fields (company, order notes, shipping) for digital membership and book checkout.
	 * Preserves essential billing fields (name, email, phone/address if required) and never modifies WooCommerce nonces/form actions.
	 *
	 * @param array $fields WooCommerce checkout fields array.
	 * @return array Cleaned fields.
	 */
	public function filter_checkout_fields( $fields ) {
		if ( isset( $fields['billing']['billing_company'] ) ) {
			unset( $fields['billing']['billing_company'] );
		}
		if ( isset( $fields['order']['order_comments'] ) ) {
			unset( $fields['order']['order_comments'] );
		}
		if ( isset( $fields['shipping'] ) ) {
			unset( $fields['shipping'] );
		}
		return $fields;
	}

	/**
	 * Template override: locate checkout/form-checkout.php
	 */
	public function locate_checkout_template( $template, $template_name, $template_path ) {
		if ( false !== strpos( $template_name, 'form-checkout.php' ) ) {
			$custom_checkout_template = DLM_PATH . 'templates/woocommerce/checkout/form-checkout.php';
			if ( file_exists( $custom_checkout_template ) ) {
				return $custom_checkout_template;
			}
		}
		return $template;
	}

	/**
	 * Template override filter via wc_get_template
	 */
	public function override_checkout_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( false !== strpos( $template_name, 'form-checkout.php' ) ) {
			$custom_checkout_template = DLM_PATH . 'templates/woocommerce/checkout/form-checkout.php';
			if ( file_exists( $custom_checkout_template ) ) {
				return $custom_checkout_template;
			}
		}
		return $located;
	}

	/**
	 * Custom return redirect URL filter for DLM orders (handles order objects and IDs)
	 */
	public function filter_order_return_url( $return_url, $order = null ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$order_id = 0;
		if ( $order && is_a( $order, 'WC_Order' ) ) {
			$order_id = $order->get_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['order_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = absint( wp_unslash( $_GET['order_id'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['order-pay'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = absint( wp_unslash( $_GET['order-pay'] ) );
		}

		$account_url = dlm_get_page_url( 'account' );
		return add_query_arg(
			array(
				'payment'  => 'success',
				'order_id' => $order_id ?: '',
			),
			$account_url
		);
	}

	/**
	 * Custom cancellation redirect URL filter for DLM orders
	 */
	public function filter_order_cancel_url( $cancel_url, $order = null ) {
		$account_url = dlm_get_page_url( 'account' );
		return add_query_arg( array( 'payment' => 'cancelled' ), $account_url ) . '#checkout';
	}

	/**
	 * Transparent redirect logic for non-checkout WooCommerce pages.
	 * WooCommerce operates purely as a headless payment processing engine for the digital library.
	 */
	public function redirect_woocommerce_pages() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// 1. SYSTEM EXEMPTIONS: Never redirect in Admin, AJAX, Cron, REST API, or Gateway Callbacks
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['wc-api'] ) || ! empty( $_GET['wc-ajax'] ) ) {
			return;
		}

		// 2. RETRY / PAYMENT / ORDER ENDPOINT EXEMPTIONS:
		// Always allow direct access to payment retry, order review, and thank-you endpoints
		if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'view-order' ) ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['pay_for_order'] ) || isset( $_GET['order-pay'] ) ) {
			return;
		}

		// 3. DLM CUSTOM PAGE EXEMPTIONS: Never redirect if already on custom DLM pages
		if ( ( function_exists( 'dlm_is_checkout_page' ) && dlm_is_checkout_page() ) || ( function_exists( 'dlm_is_account_page' ) && dlm_is_account_page() ) ) {
			return;
		}

		$is_logged_in   = is_user_logged_in();
		$dashboard_url  = dlm_get_page_url( 'account' );
		$library_url    = dlm_get_page_url( 'library' );
		
		// Determine login-aware destination:
		// Logged-in users -> DLM Library Account Dashboard (/library-account/)
		// Logged-out users -> DLM Auth Portal with redirect_to pointing directly to Dashboard
		if ( $is_logged_in ) {
			$auth_target_url = $dashboard_url ?: home_url( '/' );
		} else {
			$auth_target_url = add_query_arg(
				array( 'redirect_to' => rawurlencode( $dashboard_url ) ),
				$dashboard_url ?: wp_login_url( $dashboard_url )
			);
		}

		// 4. RULE: WooCommerce Checkout (/checkout/)
		// If cart has items, NEVER redirect. If empty and not paying for order, redirect to Library/Auth.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
				return;
			}
			wp_safe_redirect( $library_url ?: $auth_target_url );
			exit;
		}

		// 5. RULE: WooCommerce Cart (/cart/)
		// Digital items skip cart directly to checkout; if cart has items, forward to /checkout/, else to library/auth.
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
				wp_safe_redirect( wc_get_checkout_url() );
			} else {
				wp_safe_redirect( $is_logged_in ? ( $library_url ?: $dashboard_url ) : $auth_target_url );
			}
			exit;
		}

		// 6. RULE: WooCommerce Shop Catalog (/shop/)
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			wp_safe_redirect( $is_logged_in ? ( $library_url ?: $dashboard_url ) : $auth_target_url );
			exit;
		}

		// 7. RULE: WooCommerce Single Product & Product Taxonomies (categories, tags)
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_safe_redirect( $is_logged_in ? ( $library_url ?: $dashboard_url ) : $auth_target_url );
			exit;
		}
		if ( ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) {
			wp_safe_redirect( $is_logged_in ? ( $library_url ?: $dashboard_url ) : $auth_target_url );
			exit;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post_type'] ) && 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
			wp_safe_redirect( $is_logged_in ? ( $library_url ?: $dashboard_url ) : $auth_target_url );
			exit;
		}

		// 8. RULE: WooCommerce Base My Account Page (/my-account/)
		// Sub-endpoints like view-order or order-pay are exempted in Rule 2 above.
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			wp_safe_redirect( $auth_target_url );
			exit;
		}
	}

	/**
	 * Filter auth redirects to point to DLM Library Account
	 */
	public function filter_woocommerce_auth_redirect( $redirect, $user = null ) {
		return dlm_get_page_url( 'account' );
	}

	/**
	 * Filter 'Return to Shop' button URL in WooCommerce
	 */
	public function filter_woocommerce_shop_redirect( $redirect_url ) {
		return dlm_get_page_url( 'account' );
	}
}
