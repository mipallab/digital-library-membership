<?php
/**
 * Core plugin class that orchestrates actions and filters
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM {

	/**
	 * Core classes instances
	 */
	protected $db;
	protected $security;
	protected $checkout;
	protected $api;
	protected $admin;
	protected $public;

	/**
	 * Define the core loader
	 */
	public function __construct() {
		// Initialize DB and Security utilities first
		$this->db       = new DLM_DB();
		$this->security = new DLM_Security();

		// Initialize Payment Gateway integrations
		$this->checkout = new DLM_Checkout();

		// Initialize admin hooks
		if ( is_admin() ) {
			$this->admin = new DLM_Admin( $this->db, $this->checkout );
		}

		// Initialize REST API routes
		$this->api = new DLM_API( $this->db );

		// Initialize public-facing screens
		$this->public = new DLM_Public( $this->db, $this->checkout );
	}

	/**
	 * Register all actions, filters, and shortcodes
	 */
	public function run() {
		// Enqueue scripts/styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

		// REST API Init
		add_action( 'rest_api_init', array( $this->api, 'register_routes' ) );

		// Hook into page templates for customized library/single book/reader experience
		add_filter( 'template_include', array( $this, 'custom_templates' ) );

		// Add custom endpoints and rewrites
		add_action( 'init', array( $this, 'register_custom_rewrites' ) );
		add_action( 'init', array( $this, 'register_post_type_and_taxonomies' ) );

		// WooCommerce order integration
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_order_status_completed', array( $this->checkout, 'handle_woocommerce_order_completed' ), 10, 2 );
		}

		// WooCommerce add to cart AJAX
		add_action( 'wp_ajax_dlm_wc_add_to_cart_redirect', array( $this->checkout, 'ajax_wc_add_to_cart_redirect' ) );
		add_action( 'wp_ajax_nopriv_dlm_wc_add_to_cart_redirect', array( $this->checkout, 'ajax_wc_add_to_cart_redirect' ) );

		// Defer public scripts
		add_filter( 'script_loader_tag', array( $this, 'defer_public_scripts' ), 10, 3 );

		// Redirect any query param payment requests to account page
		add_action( 'template_redirect', array( $this, 'handle_payment_status_redirect' ) );

		// Admin hooks
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
			add_action( 'admin_post_dlm_save_book', array( $this->admin, 'handle_save_book' ) );
			add_action( 'admin_post_dlm_edit_book', array( $this->admin, 'handle_edit_book' ) );
			add_action( 'admin_post_dlm_delete_book', array( $this->admin, 'handle_delete_book' ) );
			add_action( 'admin_post_dlm_member_override', array( $this->admin, 'handle_member_override' ) );
			add_action( 'admin_post_dlm_export_subscribers', array( $this->admin, 'handle_export_subscribers' ) );
			add_action( 'admin_post_dlm_export_transactions', array( $this->admin, 'handle_export_transactions' ) );
			add_action( 'admin_post_dlm_approve_subscription', array( $this->admin, 'handle_approve_subscription' ) );
			add_action( 'admin_post_dlm_reject_subscription', array( $this->admin, 'handle_reject_subscription' ) );
			add_action( 'admin_post_dlm_send_member_email', array( $this->admin, 'handle_send_member_email' ) );
			add_action( 'admin_post_dlm_delete_subscription', array( $this->admin, 'handle_delete_subscription' ) );
			add_action( 'admin_post_dlm_add_member', array( $this->admin, 'handle_add_member' ) );
			add_action( 'admin_post_dlm_save_transaction', array( $this->admin, 'handle_save_transaction' ) );
			add_action( 'admin_post_dlm_edit_transaction', array( $this->admin, 'handle_edit_transaction' ) );
			add_action( 'admin_post_dlm_delete_transaction', array( $this->admin, 'handle_delete_transaction' ) );
			add_action( 'admin_post_dlm_goto_members', array( $this->admin, 'handle_goto_members' ) );
			add_action( 'admin_post_dlm_recreate_pages', array( $this->admin, 'handle_recreate_pages' ) );
		}

		// Public shortcodes
		add_shortcode( 'dlm_library', array( $this->public, 'render_library' ) );
		add_shortcode( 'dlm_pricing', array( $this->public, 'render_pricing' ) );
		add_shortcode( 'dlm_checkout', array( $this->public, 'render_checkout' ) );
		add_shortcode( 'dlm_account', array( $this->public, 'render_account' ) );

		// Checkout & Auth AJAX actions
		add_action( 'wp_ajax_dlm_stripe_create_session', array( $this->checkout, 'ajax_stripe_create_session' ) );
		add_action( 'wp_ajax_nopriv_dlm_stripe_create_session', array( $this->checkout, 'ajax_stripe_create_session' ) );
		add_action( 'wp_ajax_dlm_paypal_create_subscription', array( $this->checkout, 'ajax_paypal_create_subscription' ) );
		add_action( 'wp_ajax_nopriv_dlm_paypal_create_subscription', array( $this->checkout, 'ajax_paypal_create_subscription' ) );
		add_action( 'wp_ajax_dlm_submit_manual_payment', array( $this->checkout, 'ajax_submit_manual_payment' ) );
		add_action( 'wp_ajax_dlm_ajax_login', array( $this->public, 'ajax_login' ) );
		add_action( 'wp_ajax_nopriv_dlm_ajax_login', array( $this->public, 'ajax_login' ) );
		add_action( 'wp_ajax_dlm_ajax_register', array( $this->public, 'ajax_register' ) );
		add_action( 'wp_ajax_nopriv_dlm_ajax_register', array( $this->public, 'ajax_register' ) );

		// Admin Setup Wizard AJAX
		add_action( 'wp_ajax_dlm_save_setup_wizard', array( $this->admin, 'ajax_save_setup_wizard' ) );

		// Member SPA AJAX actions
		add_action( 'wp_ajax_dlm_sync_achievements', array( $this->public, 'ajax_sync_achievements' ) );
		add_action( 'wp_ajax_dlm_manage_journal_notes', array( $this->public, 'ajax_manage_journal_notes' ) );
		add_action( 'wp_ajax_dlm_update_profile', array( $this->public, 'ajax_update_profile' ) );
		add_action( 'wp_ajax_dlm_upload_avatar', array( $this->public, 'ajax_upload_avatar' ) );
		add_action( 'wp_ajax_dlm_toggle_favorite', array( $this->public, 'ajax_toggle_favorite' ) );

		// Webhooks listeners
		add_action( 'init', array( $this->checkout, 'handle_webhooks' ) );

		// Admin alerts hook
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		// Daily expiration checker cron hook
		add_action( 'dlm_daily_subscription_check', array( $this, 'run_expiry_checks' ) );

		// Hide admin bar for normal subscriber role users on frontend
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar_for_subscribers' ) );
	}

	/**
	 * Enqueue Admin Scripts & Styles
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only enqueue on our plugin pages
		if ( strpos( $hook, 'digital-library-membership' ) === false && strpos( $hook, 'dlm' ) === false ) {
			return;
		}

		wp_enqueue_media(); // Load media uploader for covers

		// Enqueue Tailwind CDN local copy
		wp_enqueue_script( 'dlm-tailwind', DLM_URL . 'admin/js/tailwindcss.js', array(), DLM_VERSION, false );
		$tailwind_config = "
			tailwind.config = {
				darkMode: 'class',
				theme: {
					extend: {
						colors: {
							primary: '#855300',
							secondary: '#5f5e60',
							accent: '#A66E12',
							background: '#f9f9f9',
							surface: '#ffffff',
							'surface-container': '#eeeeee',
							'surface-container-high': '#e8e8e8',
							'surface-container-highest': '#e2e2e2',
							'surface-container-low': '#f3f3f3',
							'surface-container-lowest': '#ffffff',
							'outline-variant': '#d8c3ad',
							'on-surface': '#1a1c1c',
							'on-surface-variant': '#534434',
							error: '#ba1a1a',
							'error-container': '#ffdad6',
							'success-green': '#1b5e20',
							'error-red': '#b71c1c'
						},
						borderRadius: {
							'DEFAULT': '0.25rem',
							'lg': '0.5rem',
							'xl': '0.75rem',
							'full': '9999px'
						}
					}
				}
			}
		";
		wp_add_inline_script( 'dlm-tailwind', $tailwind_config, 'after' );

		wp_enqueue_style( 'dlm-font-awesome', DLM_URL . 'admin/css/font-awesome.min.css', array(), '6.4.0' );
		wp_enqueue_style( 'dlm-admin-css', DLM_URL . 'admin/css/dlm-admin.css', array(), DLM_VERSION );
		wp_enqueue_script( 'dlm-admin-js', DLM_URL . 'admin/js/dlm-admin.js', array( 'jquery' ), DLM_VERSION, true );
		
		// Chart.js local bundle
		wp_enqueue_script( 'dlm-chart-js', DLM_URL . 'admin/js/chart.min.js', array(), '4.4.1', true );
	}

	/**
	 * Enqueue Frontend Scripts & Styles
	 */
	public function enqueue_public_assets() {
		// Only load assets if library shortcodes are present or on the reader page
		$should_load = false;
		if ( get_query_var( 'dlm_reader' ) ) {
			$should_load = true;
		} else {
			global $post;
			if ( is_a( $post, 'WP_Post' ) ) {
				if ( has_shortcode( $post->post_content, 'dlm_library' ) || 
					 has_shortcode( $post->post_content, 'dlm_pricing' ) || 
					 has_shortcode( $post->post_content, 'dlm_checkout' ) || 
					 has_shortcode( $post->post_content, 'dlm_account' ) ) {
					$should_load = true;
				}
			}
		}

		if ( ! $should_load ) {
			return;
		}

		// Enqueue public core stylesheet
		wp_enqueue_style( 'dlm-public-css', DLM_URL . 'public/css/dlm-public.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-font-awesome', DLM_URL . 'admin/css/font-awesome.min.css', array(), '6.4.0' );

		wp_enqueue_script( 'dlm-tailwind', DLM_URL . 'admin/js/tailwindcss.js', array(), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-public-js', DLM_URL . 'public/js/dlm-public.js', array( 'jquery' ), DLM_VERSION, true );

		// PayPal SDK
		$paypal_client_id = get_option( 'dlm_paypal_client_id' );
		if ( $paypal_client_id ) {
			wp_enqueue_script( 'dlm-paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . esc_attr( $paypal_client_id ) . '&vault=true&intent=subscription', array(), DLM_VERSION, true );
		}

		// Google ReCAPTCHA Integration
		$recaptcha_site_key = get_option( 'dlm_recaptcha_site_key' );
		$recaptcha_version  = get_option( 'dlm_recaptcha_version', 'v2' );
		if ( $recaptcha_site_key ) {
			if ( $recaptcha_version === 'v3' ) {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ), array(), null, true );
			} else {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
			}
		}

		// Enqueue reader styles & PDF.js if we are on the reader page
		if ( get_query_var( 'dlm_reader' ) ) {
			wp_enqueue_style( 'dlm-reader-css', DLM_URL . 'public/css/dlm-reader.css', array(), DLM_VERSION );
			
			// PDF.js local load
			wp_enqueue_script( 'dlm-pdf-js', DLM_URL . 'public/js/pdf.min.js', array(), '3.11.174', false );
			wp_enqueue_script( 'dlm-reader-js', DLM_URL . 'public/js/dlm-reader.js', array( 'jquery', 'dlm-pdf-js' ), DLM_VERSION, true );

			// Pass settings to reader script
			wp_localize_script( 'dlm-reader-js', 'dlmReaderParams', array(
				'apiUrl'      => esc_url_raw( rest_url( 'dlm/v1' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'pdfWorkerUrl'=> DLM_URL . 'public/js/pdf.worker.min.js',
			) );
		}

		// Localize public js
		wp_localize_script( 'dlm-public-js', 'dlmParams', array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'stripePublishable' => get_option( 'dlm_stripe_publishable_key' ),
			'paypalClientId'    => get_option( 'dlm_paypal_client_id' ),
			'paypalMonthlyPlanId' => get_option( 'dlm_paypal_monthly_plan_id' ),
			'paypalYearlyPlanId'  => get_option( 'dlm_paypal_yearly_plan_id' ),
			'paypalLifetimePlanId' => get_option( 'dlm_paypal_lifetime_plan_id' ),
			'nonce'             => wp_create_nonce( 'dlm_public_nonce' ),
			'useWooCommerce'    => class_exists( 'WooCommerce' ) && ( get_option( 'dlm_wc_monthly_product' ) || get_option( 'dlm_wc_yearly_product' ) || get_option( 'dlm_wc_lifetime_product' ) ),
			'recaptchaSiteKey'  => get_option( 'dlm_recaptcha_site_key' ),
			'recaptchaVersion'  => get_option( 'dlm_recaptcha_version', 'v2' ),
		) );
	}

	/**
	 * Custom template router
	 */
	public function custom_templates( $template ) {
		// Handle custom Reader view
		if ( get_query_var( 'dlm_reader' ) ) {
			$reader_template = DLM_PATH . 'templates/reader.php';
			if ( file_exists( $reader_template ) ) {
				return $reader_template;
			}
		}

		// Handle custom Member Dashboard SPA view
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dlm_account' ) ) {
			$dashboard_template = DLM_PATH . 'templates/member-dashboard.php';
			if ( file_exists( $dashboard_template ) ) {
				return $dashboard_template;
			}
		}

		return $template;
	}

	/**
	 * Register URL query variables and rewrites for reader endpoint: example.com/read/123
	 */
	public function register_custom_rewrites() {
		add_rewrite_rule( '^read/([0-9]+)/?', 'index.php?dlm_reader=$matches[1]', 'top' );
		add_rewrite_tag( '%dlm_reader%', '([0-9]+)' );

		// One-time auto-flush rule resolver to fix 404 errors on "read/x"
		if ( ! get_option( 'dlm_rules_flushed_v130' ) ) {
			flush_rewrite_rules();
			update_option( 'dlm_rules_flushed_v130', 1 );
		}
	}

	/**
	 * Display warning banner inside admin/editor panel for pending manual sub approvals
	 */
	public function display_admin_notices() {
		if ( ! current_user_can( 'manage_dlm_library' ) ) {
			return;
		}

		// Check for missing DLM pages
		$required_page_options = array( 'dlm_library_page_id', 'dlm_account_page_id', 'dlm_pricing_page_id', 'dlm_checkout_page_id' );
		$has_missing_page      = false;
		foreach ( $required_page_options as $page_opt ) {
			$page_id = get_option( $page_opt );
			if ( ! $page_id || ! get_post( $page_id ) || 'trash' === get_post_status( $page_id ) ) {
				$has_missing_page = true;
				break;
			}
		}

		if ( $has_missing_page ) {
			$recreate_url = wp_nonce_url( admin_url( 'admin-post.php?action=dlm_recreate_pages' ), 'dlm_recreate_pages_nonce' );
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Digital Library:', 'digital-library-membership' ); ?></strong>
					<?php esc_html_e( 'One or more required frontend library pages are missing.', 'digital-library-membership' ); ?>
					<a href="<?php echo esc_url( $recreate_url ); ?>" class="button button-secondary" style="margin-left:10px;"><?php esc_html_e( 'Recreate Missing Pages', 'digital-library-membership' ); ?></a>
				</p>
			</div>
			<?php
		}

		// Notice if WooCommerce is explicitly set as primary gateway but WooCommerce plugin is missing
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$enable_wc = get_option( 'dlm_enable_woocommerce', '0' );
		if ( '1' === $enable_wc && ! class_exists( 'WooCommerce' ) && isset( $_GET['page'] ) && 'dlm-library' === $_GET['page'] ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'WooCommerce Gateway Notice:', 'digital-library-membership' ); ?></strong>
					<?php esc_html_e( 'WooCommerce integration is enabled in settings, but WooCommerce is not active. Install WooCommerce or disable WooCommerce Gateway in settings.', 'digital-library-membership' ); ?>
				</p>
			</div>
			<?php
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dlm_subscriptions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pending_count = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = %s", $table, 'pending_approval' ) ) );

		if ( $pending_count > 0 ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Digital Library:', 'digital-library-membership' ); ?></strong>
					<?php 
					echo esc_html( sprintf( 
						// translators: %d: Number of pending manual subscription requests
						_n( 'There is %d manual subscription request pending your approval.', 'There are %d manual subscription requests pending your approval.', $pending_count, 'digital-library-membership' ), 
						$pending_count 
					) ); 
					?>
					<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=dlm_goto_members' ) ); ?>"><?php esc_html_e( 'Review and Approve Subscriptions', 'digital-library-membership' ); ?></a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Fetch subscriptions ending in 3 days and send alerts
	 */
	public function run_expiry_checks() {
		global $wpdb;
		$table = $wpdb->prefix . 'dlm_subscriptions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return;
		}

		$target_date = gmdate( 'Y-m-d', strtotime( '+3 days' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i WHERE status = 'active' AND DATE(expires_at) = %s",
			$table,
			$target_date
		) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $sub ) {
				$user = get_userdata( $sub->user_id );
				if ( ! $user ) {
					continue;
				}

				$to      = $user->user_email;
				$subject = __( 'Your Digital Library Subscription Expires in 3 Days', 'digital-library-membership' );
				/* translators: 1: User name, 2: Plan interval, 3: Expiration date, 4: Checkout page URL */
				$body    = sprintf(
					__( "Hello %1\$s,\n\nThis is a friendly reminder that your Digital Library Membership (%2\$s plan) is expiring in 3 days on %3\$s.\n\nPlease visit your account checkout to renew your subscription and maintain uninterrupted access to our digital books:\n%4\$s\n\nBest regards,\nDigital Library Team", 'digital-library-membership' ),
					$user->display_name,
					ucfirst( $sub->plan_interval ),
					date_i18n( get_option( 'date_format' ), strtotime( $sub->expires_at ) ),
					home_url( '/checkout/' )
				);

				wp_mail( $to, $subject, $body );
			}
		}
	}

	/**
	 * Send activation email helper (Static)
	 */
	public static function send_subscription_active_email( $user_id, $interval, $expires_at ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$to      = $user->user_email;
		$subject = __( 'Digital Library Subscription Activated!', 'digital-library-membership' );
		/* translators: 1: User name, 2: Membership tier, 3: Expiration date, 4: Library URL */
		$body    = sprintf(
			__( "Hello %1\$s,\n\nWe are pleased to inform you that your subscription to the Digital Library has been activated successfully!\n\nMembership Tier: %2\$s\nExpiration Date: %3\$s\n\nYou can start reading our digital library collection here:\n%4\$s\n\nBest regards,\nDigital Library Team", 'digital-library-membership' ),
			$user->display_name,
			ucfirst( $interval ),
			( $interval === 'lifetime' ) ? __( 'Lifetime (Never Expires)', 'digital-library-membership' ) : date_i18n( get_option( 'date_format' ), strtotime( $expires_at ) ),
			home_url( '/library/' )
		);

		wp_mail( $to, $subject, $body );
	}

	/**
	 * Hide WordPress Admin Bar for subscribers on frontend
	 */
	public function hide_admin_bar_for_subscribers( $show ) {
		if ( ! current_user_can( 'manage_dlm_library' ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * Register Custom Post Type and Taxonomies for Book categorizing & tagging
	 */
	public function register_post_type_and_taxonomies() {
		register_post_type( 'dlm_book', array(
			'labels' => array(
				'name'          => __( 'Books', 'digital-library-membership' ),
				'singular_name' => __( 'Book', 'digital-library-membership' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor' ),
		) );

		register_taxonomy( 'dlm_book_category', 'dlm_book', array(
			'labels' => array(
				'name'          => __( 'Book Category', 'digital-library-membership' ),
				'singular_name' => __( 'Book Category', 'digital-library-membership' ),
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		) );

		register_taxonomy( 'dlm_book_tag', 'dlm_book', array(
			'labels' => array(
				'name'          => __( 'Book Tags', 'digital-library-membership' ),
				'singular_name' => __( 'Book Tag', 'digital-library-membership' ),
			),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		) );
	}

	/**
	 * Defer public front end scripts for faster loading times
	 */
	public function defer_public_scripts( $tag, $handle, $src ) {
		if ( in_array( $handle, array( 'dlm-public-js', 'dlm-reader-js' ), true ) ) {
			return str_replace( ' src', ' defer src', $tag );
		}
		return $tag;
	}

	/**
	 * Handles redirection to the Setup Wizard on plugin activation
	 */
	public function handle_activation_redirect() {
		if ( get_option( 'dlm_activation_redirect' ) ) {
			delete_option( 'dlm_activation_redirect' );
			
			// Only redirect if setup is not already completed
			if ( 'yes' !== get_option( 'dlm_setup_completed' ) ) {
				// Don't redirect on bulk activation
				if ( ! isset( $_GET['activate-multi'] ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=dlm-setup-wizard' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Redirect any page loaded with payment query parameters to the account dashboard
	 */
	public function handle_payment_status_redirect() {
		if ( isset( $_GET['payment'] ) ) {
			$payment = sanitize_key( $_GET['payment'] );
			$valid_statuses = array( 'success', 'active', 'pending', 'cancelled', 'cancel', 'failed', 'faild' );
			if ( in_array( $payment, $valid_statuses, true ) ) {
				$account_page_id = dlm_get_page_id( 'account' );
				if ( ! is_page( $account_page_id ) ) {
					$query_args = array(
						'payment' => $payment,
					);
					if ( isset( $_GET['session_id'] ) ) {
						$query_args['session_id'] = sanitize_text_field( $_GET['session_id'] );
					}
					
					$redirect_url = add_query_arg( $query_args, dlm_get_page_url( 'account' ) );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}
	}
}

/**
 * Global helper function to get DLM page ID
 */
function dlm_get_page_id( $page_key ) {
	return (int) get_option( 'dlm_' . $page_key . '_page_id', 0 );
}

/**
 * Global helper function to get DLM page URL
 */
function dlm_get_page_url( $page_key ) {
	$page_id = dlm_get_page_id( $page_key );
	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}
	return home_url( '/' . $page_key . '/' );
}

/**
 * Global helper function to check if a user has an active membership subscription
 */
function dlm_user_has_active_subscription( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}
	$db = new DLM_DB();
	return $db->has_active_membership( $user_id );
}

/**
 * Global helper function to verify Google ReCAPTCHA token (v2 or v3)
 */
function dlm_verify_recaptcha( $token ) {
	$secret_key = get_option( 'dlm_recaptcha_secret_key' );
	if ( empty( $secret_key ) ) {
		return true; // Skip verification if not configured
	}

	if ( empty( $token ) ) {
		return false;
	}

	$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
		'body' => array(
			'secret'   => $secret_key,
			'response' => $token,
			'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		),
	) );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body   = wp_remote_retrieve_body( $response );
	$result = json_decode( $body, true );

	return ! empty( $result['success'] ) && $result['success'];
}


