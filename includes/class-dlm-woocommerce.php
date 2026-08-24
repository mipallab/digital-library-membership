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
		add_filter( 'the_content', array( $this, 'ensure_order_pay_rendered' ), 1 );

		// Register order-pay rewrite endpoint
		add_action( 'init', array( $this, 'register_order_pay_rewrite' ) );

		// Standalone URL and Page overrides so WooCommerce never redirects to home or missing pages
		add_filter( 'woocommerce_get_checkout_page_id', array( $this, 'filter_checkout_page_id' ), 999 );
		add_filter( 'woocommerce_is_checkout', array( $this, 'filter_is_checkout' ), 999 );
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'filter_woocommerce_checkout_url' ), 999 );
		add_filter( 'woocommerce_get_checkout_payment_url', array( $this, 'filter_checkout_payment_url' ), 999, 2 );
		add_filter( 'woocommerce_get_return_url', array( $this, 'filter_order_return_url' ), 999, 2 );
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'filter_order_return_url' ), 999, 2 );
		add_filter( 'woocommerce_get_cancel_url', array( $this, 'filter_order_cancel_url' ), 999, 2 );
		add_filter( 'woocommerce_get_cancel_url_bare', array( $this, 'filter_order_cancel_url' ), 999, 2 );

		// Redirect all frontend WooCommerce standard views (Shop, Cart, My Account, Product views, standard Checkout) to DLM Library Account
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

			$pay_url = $this->get_clean_order_pay_url( $order );
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

		$plan    = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'monthly';
		$package = dlm_get_package( $plan );
		if ( ! $package ) {
			$packages = dlm_get_packages();
			$package  = reset( $packages );
		}

		$package_id = $package ? $package['id'] : $plan;
		$interval   = ( $package && ! empty( $package['interval'] ) ) ? $package['interval'] : 'monthly';

		$product_id = $this->get_or_create_subscription_wc_product( $package_id );
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
			$order->update_meta_data( '_dlm_package_id', $package_id );
			$order->update_meta_data( '_dlm_plan_interval', $interval );
			$order->calculate_totals();
			$order->update_status( 'pending', __( 'DLM headless subscription order initialized.', 'digital-library-membership' ) );

			$pay_url = $this->get_clean_order_pay_url( $order );
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
	 * Register rewrite rule so /order-pay/45/ is recognized cleanly without 404
	 */
	public function register_order_pay_rewrite() {
		add_rewrite_tag( '%dlm_order_pay%', '([0-9]+)' );
		add_rewrite_rule( '^order-pay/([0-9]+)/?$', 'index.php?dlm_order_pay=$matches[1]&pay_for_order=true', 'top' );
	}

	/**
	 * Build clean, 100% standalone headless order payment URL that has ZERO dependency on WooCommerce checkout page.
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @return string Clean headless order payment URL.
	 */
	public function get_clean_order_pay_url( $order ) {
		if ( function_exists( 'dlm_get_order_pay_url' ) ) {
			return dlm_get_order_pay_url( $order );
		}

		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return home_url( '/' );
		}

		$order_id    = $order->get_id();
		$order_key   = $order->get_order_key();
		$account_url = dlm_get_page_url( 'account' );

		$pay_url = add_query_arg(
			array(
				'dlm_action'    => 'order_pay',
				'order-pay'     => $order_id,
				'pay_for_order' => 'true',
				'key'           => $order_key,
			),
			$account_url
		);

		return apply_filters( 'dlm_woocommerce_order_pay_url', $pay_url, $order );
	}

	/**
	 * Ensure WooCommerce always has a valid checkout page ID (fallback to Library Account page)
	 */
	public function filter_checkout_page_id( $page_id ) {
		if ( $page_id <= 0 || 'publish' !== get_post_status( $page_id ) ) {
			$account_id = dlm_get_page_id( 'account' );
			if ( $account_id > 0 ) {
				return $account_id;
			}
		}
		return $page_id;
	}

	/**
	 * Mark is_checkout = true during DLM order payment flows
	 */
	public function filter_is_checkout( $is_checkout ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['dlm_action'] ) && 'order_pay' === sanitize_key( wp_unslash( $_GET['dlm_action'] ) ) ) {
			return true;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) ) {
			return true;
		}
		return $is_checkout;
	}

	/**
	 * Render standalone headless order pay screen with zero dependency on WooCommerce pages.
	 *
	 * @param WC_Order $order
	 */
	public function render_standalone_order_pay_template( $order ) {
		global $wp;
		$wp->query_vars['order-pay'] = $order->get_id();

		// Handle direct POST submission fallback if needed
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['woocommerce_pay'], $_POST['woocommerce-pay-nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-pay-nonce'] ) ), 'woocommerce-pay' ) ) {
				$order_id = $order->get_id();
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
				if ( ! empty( $payment_method ) && function_exists( 'WC' ) && WC()->payment_gateways() ) {
					$gateways = WC()->payment_gateways()->get_available_payment_gateways();
					if ( isset( $gateways[ $payment_method ] ) ) {
						$result = $gateways[ $payment_method ]->process_payment( $order_id );
						if ( isset( $result['result'] ) && 'success' === $result['result'] && ! empty( $result['redirect'] ) ) {
							wp_redirect( $result['redirect'] );
							exit;
						}
					}
				}
			}
		}

		// Enqueue necessary WooCommerce scripts and styles for payment gateways
		if ( class_exists( 'WC_Frontend_Scripts' ) ) {
			WC_Frontend_Scripts::load_scripts();
		}
		wp_enqueue_script( 'wc-checkout' );
		wp_enqueue_style( 'woocommerce-general' );
		wp_enqueue_style( 'woocommerce-layout' );
		wp_enqueue_style( 'dashicons' );

		get_header();

		$custom_pay_template = DLM_PATH . 'templates/woocommerce/checkout/form-pay.php';
		if ( file_exists( $custom_pay_template ) ) {
			include $custom_pay_template;
		} else {
			woocommerce_order_pay( $order->get_id() );
		}

		get_footer();
	}

	/**
	 * Ensure WooCommerce Order Pay form renders if current request is a validated order-pay action
	 */
	public function ensure_order_pay_rendered( $content ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) && function_exists( 'is_checkout' ) && is_checkout() ) {
			if ( ! shortcode_exists( 'woocommerce_checkout' ) || false === strpos( $content, 'woocommerce-checkout' ) ) {
				ob_start();
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_pay_id = isset( $_GET['order-pay'] ) ? absint( wp_unslash( $_GET['order-pay'] ) ) : 0;
				woocommerce_order_pay( $order_pay_id );
				$pay_html = ob_get_clean();
				if ( ! empty( $pay_html ) ) {
					return $pay_html;
				}
			}
		}
		return $content;
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
	 * Override WooCommerce Checkout URL when checkout page is not present
	 */
	public function filter_woocommerce_checkout_url( $url ) {
		$checkout_page_id = wc_get_page_id( 'checkout' );
		if ( $checkout_page_id <= 0 || 'publish' !== get_post_status( $checkout_page_id ) ) {
			return dlm_get_page_url( 'account' );
		}
		return $url;
	}

	/**
	 * Override checkout payment URL to point directly to clean standalone headless order-pay
	 */
	public function filter_checkout_payment_url( $url, $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( $order && is_a( $order, 'WC_Order' ) ) {
			return $this->get_clean_order_pay_url( $order );
		}
		return $url;
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
	 * Redirect all standard WooCommerce pages (Shop, Cart, My Account, Product views,
	 * and standard empty Cart Checkout) to the DLM Library Account Dashboard page.
	 * WooCommerce operates purely as a headless payment processing engine.
	 */
	public function redirect_woocommerce_pages() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Never redirect in WP Admin, WP AJAX, WP CRON, WP CLI, REST API, or WooCommerce background API/Webhooks
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Check for pay_for_order request (either via query args or URI path)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_dlm_pay_action = isset( $_GET['dlm_action'] ) && 'order_pay' === sanitize_key( wp_unslash( $_GET['dlm_action'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $is_dlm_pay_action || isset( $_GET['pay_for_order'] ) || isset( $_GET['order-pay'] ) || isset( $_GET['key'] ) ) {
			$order_id = 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['order-pay'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_id = absint( $_GET['order-pay'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $_GET['order_id'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_id = absint( $_GET['order_id'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $_GET['dlm_order_pay'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_id = absint( $_GET['dlm_order_pay'] );
			} else {
				$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				if ( preg_match( '#/order-pay/(\d+)#', $req_uri, $matches ) ) {
					$order_id = absint( $matches[1] );
				}
			}

			if ( $order_id > 0 ) {
				$order = wc_get_order( $order_id );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
				if ( $order && ! empty( $key ) && hash_equals( $order->get_order_key(), $key ) ) {
					// Ensure 404 is cleared
					global $wp_query;
					if ( is_object( $wp_query ) ) {
						$wp_query->is_404 = false;
					}
					status_header( 200 );

					$clean_url = $this->get_clean_order_pay_url( $order );

					// If current URL is a legacy /order-pay/ path, redirect safely to clean standalone pay URL
					$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
					if ( ! empty( $req_uri ) && false !== strpos( $req_uri, '/order-pay/' ) && ! $is_dlm_pay_action ) {
						wp_safe_redirect( $clean_url );
						exit;
					}

					// Render standalone headless order pay screen directly
					$this->render_standalone_order_pay_template( $order );
					exit;
				}
			}
		}

		// Allow WooCommerce order payment page (e.g. /checkout/order-pay/123/?pay_for_order=true&key=wc_order_xyz)
		if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}

		// Allow gateway IPN / API callbacks
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check for WooCommerce gateway callbacks.
		if ( ! empty( $_GET['wc-api'] ) || ! empty( $_GET['wc-ajax'] ) || ! empty( $_GET['pay_for_order'] ) ) {
			return;
		}

		$should_redirect = false;
		$redirect_url    = dlm_get_page_url( 'account' );

		// 1. My Account page and all sub-endpoints (/my-account/*)
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			$should_redirect = true;
		}

		// 2. Cart page
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			$should_redirect = true;
		}

		// 3. Shop catalog, Product single views, Categories, Tags, and Product Taxonomies
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$should_redirect = true;
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			$should_redirect = true;
		}
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			$should_redirect = true;
		}
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$should_redirect = true;
		}
		if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			$should_redirect = true;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query arg check for product archive.
		if ( isset( $_GET['post_type'] ) && 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
			$should_redirect = true;
		}

		// 4. Thank You / Order Received endpoint - redirect to Library Account with success query arg
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			global $wp;
			$order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
			$redirect_url = add_query_arg(
				array(
					'payment'  => 'success',
					'order_id' => $order_id ?: '',
				),
				dlm_get_page_url( 'account' )
			);
			$should_redirect = true;
		}

		// 5. Standard Checkout (redirect when it's NOT an order-pay screen)
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			if ( ! is_wc_endpoint_url( 'order-pay' ) && ! is_wc_endpoint_url( 'order-received' ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['plan'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$plan = sanitize_key( wp_unslash( $_GET['plan'] ) );
					$redirect_url = add_query_arg( array( 'plan' => $plan ), dlm_get_page_url( 'account' ) ) . '#checkout';
				}
				$should_redirect = true;
			}
		}

		if ( $should_redirect ) {
			wp_safe_redirect( $redirect_url );
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
