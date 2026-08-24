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
	protected $woocommerce;
	protected $social_auth;
	protected $api;
	protected $admin;
	protected $public;
	protected $home_widgets;

	/**
	 * Define the core loader
	 */
	public function __construct() {
		// Initialize DB and Security utilities first
		$this->db       = new DLM_DB();
		$this->security = new DLM_Security();

		// Initialize Payment Gateway integrations
		$this->checkout = new DLM_Checkout();

		// Initialize Headless WooCommerce engine
		$this->woocommerce = new DLM_WooCommerce( $this->db, $this->checkout );

		// Initialize Social Authentication engine
		$this->social_auth = new DLM_Social_Auth( $this->db );

		// Initialize admin hooks
		if ( is_admin() ) {
			$this->admin = new DLM_Admin( $this->db, $this->checkout );
		}

		// Initialize REST API routes
		$this->api = new DLM_API( $this->db );

		// Initialize public-facing screens
		$this->public = new DLM_Public( $this->db, $this->checkout );

		// Initialize Home Widgets & Addons Engine
		$this->home_widgets = DLM_Home_Widgets::instance();
	}

	/**
	 * Register all actions, filters, and shortcodes
	 */
	public function run() {
		// Automatic DB schema migration check
		DLM_Activator::check_and_upgrade_db();

		// Headless WooCommerce integration
		$this->woocommerce->init();

		// Enqueue scripts/styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

		// REST API Init
		add_action( 'rest_api_init', array( $this->api, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this->social_auth, 'register_routes' ) );

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
			add_action( 'admin_init', array( $this, 'restrict_admin_area' ) );

			// Clear connection cache on key changes
			add_action( 'update_option_dlm_stripe_secret_key', 'dlm_clear_stripe_conn_transient' );
			add_action( 'update_option_dlm_paypal_client_id', 'dlm_clear_paypal_conn_transient' );
			add_action( 'update_option_dlm_paypal_secret_key', 'dlm_clear_paypal_conn_transient' );
			add_action( 'update_option_dlm_recaptcha_site_key', 'dlm_clear_recaptcha_conn_transient' );
			add_action( 'update_option_dlm_recaptcha_secret_key', 'dlm_clear_recaptcha_conn_transient' );
			add_action( 'update_option_dlm_recaptcha_mode', 'dlm_clear_recaptcha_conn_transient' );
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
			add_action( 'admin_post_dlm_save_package', array( $this->admin, 'handle_save_package' ) );
			add_action( 'admin_post_dlm_edit_package', array( $this->admin, 'handle_edit_package' ) );
			add_action( 'admin_post_dlm_delete_package', array( $this->admin, 'handle_delete_package' ) );
			add_action( 'admin_post_dlm_toggle_package_status', array( $this->admin, 'handle_toggle_package_status' ) );
			add_action( 'admin_menu', array( $this->admin, 'hide_headless_wc_admin_menus' ), 999 );
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
		add_action( 'wp_ajax_dlm_install_activate_plugin', array( $this->admin, 'ajax_install_activate_plugin' ) );

		// Demo Data Management AJAX
		add_action( 'wp_ajax_dlm_import_demo_data', array( $this->admin, 'ajax_import_demo_data' ) );
		add_action( 'wp_ajax_dlm_remove_demo_data', array( $this->admin, 'ajax_remove_demo_data' ) );

		// Member SPA AJAX actions
		add_action( 'wp_ajax_dlm_sync_achievements', array( $this->public, 'ajax_sync_achievements' ) );
		add_action( 'wp_ajax_dlm_manage_journal_notes', array( $this->public, 'ajax_manage_journal_notes' ) );
		add_action( 'wp_ajax_dlm_update_profile', array( $this->public, 'ajax_update_profile' ) );
		add_action( 'wp_ajax_dlm_upload_avatar', array( $this->public, 'ajax_upload_avatar' ) );
		add_action( 'wp_ajax_dlm_toggle_favorite', array( $this->public, 'ajax_toggle_favorite' ) );
		add_action( 'wp_ajax_dlm_get_user_featured_access', array( $this->public, 'ajax_get_featured_access' ) );
		add_action( 'wp_ajax_nopriv_dlm_get_user_featured_access', array( $this->public, 'ajax_get_featured_access' ) );

		// Member In-App Notification AJAX actions
		add_action( 'wp_ajax_dlm_get_notifications', array( $this->public, 'ajax_get_notifications' ) );
		add_action( 'wp_ajax_dlm_mark_notification_read', array( $this->public, 'ajax_mark_notification_read' ) );
		add_action( 'wp_ajax_dlm_mark_all_notifications_read', array( $this->public, 'ajax_mark_all_notifications_read' ) );
		add_action( 'wp_ajax_dlm_get_unread_notifications_count', array( $this->public, 'ajax_get_unread_notifications_count' ) );

		// Member Onboarding Tour AJAX action
		add_action( 'wp_ajax_dlm_update_onboarding_status', array( $this->public, 'ajax_update_onboarding_status' ) );

		// Register Elementor Category & Custom Widgets
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_featured_slider' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_elementor_featured_slider_legacy' ) );

		// Webhooks listeners
		add_action( 'init', array( $this->checkout, 'handle_webhooks' ) );

		// Admin alerts hook
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		// Cron hooks
		add_action( 'dlm_daily_subscription_check', array( $this, 'run_expiry_checks' ) );
		add_action( 'dlm_check_scheduled_books', array( $this->db, 'publish_scheduled_books' ) );
		add_action( 'dlm_cleanup_stale_orders', array( $this->db, 'cleanup_stale_orders' ) );

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
		
		wp_localize_script( 'dlm-admin-js', 'dlmAdminParams', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dlm_public_nonce' ),
		) );

		// Chart.js local bundle
		wp_enqueue_script( 'dlm-chart-js', DLM_URL . 'admin/js/chart.min.js', array(), '4.4.1', true );
	}

	/**
	 * Enqueue Frontend Scripts & Styles
	 */
	public function enqueue_public_assets() {
		if ( is_admin() ) {
			return;
		}

		// Only load assets if library shortcodes are present, on singular pages, or on the reader page
		$should_load = false;
		if ( get_query_var( 'dlm_reader' ) ) {
			$should_load = true;
		} else {
			global $post;
			if ( is_a( $post, 'WP_Post' ) ) {
				if ( has_shortcode( $post->post_content, 'dlm_library' ) || 
					 has_shortcode( $post->post_content, 'dlm_pricing' ) || 
					 has_shortcode( $post->post_content, 'dlm_checkout' ) || 
					 has_shortcode( $post->post_content, 'dlm_account' ) ||
					 has_shortcode( $post->post_content, 'dlm_member_dashboard' ) ) {
					$should_load = true;
				}
			}
			// In case shortcode is inside an Elementor template or custom builder page
			if ( ! $should_load && ( is_singular() || is_page() ) ) {
				$should_load = true;
			}
		}

		if ( ! $should_load ) {
			return;
		}

		// Enqueue public core stylesheet
		wp_enqueue_style( 'dlm-public-css', DLM_URL . 'public/css/dlm-public.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-onboarding-tour-css', DLM_URL . 'public/css/dlm-onboarding-tour.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-font-awesome', DLM_URL . 'admin/css/font-awesome.min.css', array(), '6.4.0' );

		wp_enqueue_script( 'dlm-tailwind', DLM_URL . 'admin/js/tailwindcss.js', array(), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-public-js', DLM_URL . 'public/js/dlm-public.js', array( 'jquery' ), DLM_VERSION, true );
		wp_enqueue_script( 'dlm-onboarding-tour-js', DLM_URL . 'public/js/dlm-onboarding-tour.js', array( 'jquery' ), DLM_VERSION, true );

		// PayPal SDK
		$paypal_client_id = get_option( 'dlm_paypal_client_id' );
		if ( $paypal_client_id ) {
			wp_enqueue_script( 'dlm-paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . esc_attr( $paypal_client_id ) . '&vault=true&intent=subscription', array(), DLM_VERSION, true );
		}

		// Google ReCAPTCHA Integration
		$recaptcha_mode     = get_option( 'dlm_recaptcha_mode', 'production' );
		$recaptcha_site_key = ( $recaptcha_mode === 'testing' ) ? '6LeIxAcTAAAAAJcZVRqy9m71zuoE0tV7mP9XXqgC' : get_option( 'dlm_recaptcha_site_key' );
		$recaptcha_version  = ( $recaptcha_mode === 'testing' ) ? 'v2' : get_option( 'dlm_recaptcha_version', 'v2' );

		if ( $recaptcha_site_key ) {
			if ( $recaptcha_version === 'v3' ) {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ), array(), DLM_VERSION, true );
			} else {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), DLM_VERSION, true );
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
			'recaptchaSiteKey'  => ( $recaptcha_mode === 'testing' ) ? '6LeIxAcTAAAAAJcZVRqy9m71zuoE0tV7mP9XXqgC' : get_option( 'dlm_recaptcha_site_key' ),
			'recaptchaVersion'  => ( $recaptcha_mode === 'testing' ) ? 'v2' : get_option( 'dlm_recaptcha_version', 'v2' ),
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

		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'dlm' ) === false ) {
			return;
		}

		$recaptcha_mode = get_option( 'dlm_recaptcha_mode', 'production' );
		if ( 'testing' === $recaptcha_mode ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'DLM Security Notice:', 'digital-library-membership' ); ?></strong>
					<?php esc_html_e( 'reCAPTCHA is currently running in TEST mode. Real bot protection is disabled site-wide.', 'digital-library-membership' ); ?>
				</p>
			</div>
			<?php
		}

		$recaptcha_secret = get_option( 'dlm_recaptcha_secret_key' );
		if ( 'testing' !== $recaptcha_mode && empty( $recaptcha_secret ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'DLM Notice:', 'digital-library-membership' ); ?></strong>
					<?php esc_html_e( 'reCAPTCHA secret key is not set. Login and registration forms currently have no bot protection.', 'digital-library-membership' ); ?>
				</p>
			</div>
			<?php
		}

		$stripe_secret = get_option( 'dlm_stripe_secret_key' );
		$stripe_webhook_secret = get_option( 'dlm_stripe_webhook_secret' );
		if ( ! empty( $stripe_secret ) && empty( $stripe_webhook_secret ) ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'DLM Security Warning:', 'digital-library-membership' ); ?></strong>
					<?php esc_html_e( 'Stripe webhook secret is not configured. Webhooks will be refused to prevent unauthorized access.', 'digital-library-membership' ); ?>
				</p>
			</div>
			<?php
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

		// Notice: Elementor Integration
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
			if ( ! function_exists( 'is_plugin_active' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins_dir     = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/plugins' : ABSPATH . 'wp-content/plugins' );
			$is_el_installed = file_exists( $plugins_dir . '/elementor/elementor.php' );
			$el_action_url   = $is_el_installed 
				? wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=elementor%2Felementor.php' ), 'activate-plugin_elementor/elementor.php' )
				: ( function_exists( 'self_admin_url' ) ? wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' ) : '#' );
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Digital Library Membership:', 'digital-library-membership' ); ?></strong>
					<?php esc_html_e( 'Elementor Page Builder is recommended to enable the Featured Book Hero Carousel slider and custom drag-and-drop templates.', 'digital-library-membership' ); ?>
					<a href="<?php echo esc_url( $el_action_url ); ?>" class="button button-primary" style="margin-left:10px;">
						<?php echo $is_el_installed ? esc_html__( 'Activate Elementor', 'digital-library-membership' ) : esc_html__( 'Install Elementor', 'digital-library-membership' ); ?>
					</a>
				</p>
			</div>
			<?php
		}

		// Notice: WooCommerce Integration
		if ( ! class_exists( 'WooCommerce' ) ) {
			if ( ! function_exists( 'is_plugin_active' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins_dir     = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/plugins' : ABSPATH . 'wp-content/plugins' );
			$is_wc_installed = file_exists( $plugins_dir . '/woocommerce/woocommerce.php' );
			$wc_action_url   = $is_wc_installed 
				? wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce%2Fwoocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' )
				: ( function_exists( 'self_admin_url' ) ? wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' ) : '#' );
			$enable_wc       = get_option( 'dlm_enable_woocommerce', '0' );
			?>
			<div class="notice notice-<?php echo '1' === $enable_wc ? 'warning' : 'info'; ?> is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Digital Library Membership:', 'digital-library-membership' ); ?></strong>
					<?php echo '1' === $enable_wc 
						? esc_html__( 'WooCommerce gateway is enabled in settings, but WooCommerce is not active.', 'digital-library-membership' ) 
						: esc_html__( 'WooCommerce is recommended for advanced e-commerce cart, checkout gateways, and automated book product syncing.', 'digital-library-membership' ); ?>
					<a href="<?php echo esc_url( $wc_action_url ); ?>" class="button <?php echo '1' === $enable_wc ? 'button-primary' : 'button-secondary'; ?>" style="margin-left:10px;">
						<?php echo $is_wc_installed ? esc_html__( 'Activate WooCommerce', 'digital-library-membership' ) : esc_html__( 'Install WooCommerce', 'digital-library-membership' ); ?>
					</a>
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
			<div class="notice notice-info is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Pending Approval Alert:', 'digital-library-membership' ); ?></strong>
					<?php 
					/* translators: %d: Count of pending manual subscriptions */
					printf( esc_html__( 'You have %d manual payment subscription(s) waiting for admin approval.', 'digital-library-membership' ), intval( $pending_count ) ); 
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dlm-library#tab-members' ) ); ?>" style="font-weight:bold; text-decoration:underline; margin-left: 5px;">
						<?php esc_html_e( 'Review Members', 'digital-library-membership' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Fetch subscriptions ending in 3 days, handle expired subscriptions, and send alerts & notifications
	 */
	public function run_expiry_checks() {
		global $wpdb;
		$table = $wpdb->prefix . 'dlm_subscriptions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return;
		}

		$db = new DLM_DB();

		// 1. Check subscriptions expiring in 3 days
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

				// Idempotent In-App Notification (Checks if 3-day notice sent within last 7 days)
				$since_7d = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
				if ( ! $db->notification_exists( $sub->user_id, 'subscription', 'Expiring in 3 Days', $since_7d ) ) {
					$db->create_notification( array(
						'user_id'   => $sub->user_id,
						'type'      => 'subscription',
						'title'     => __( 'Subscription Expiring in 3 Days', 'digital-library-membership' ),
						'message'   => sprintf(
							/* translators: 1: Plan name, 2: Expiry date */
							__( 'Your %1$s subscription is expiring on %2$s. Click here to renew your membership and keep reading.', 'digital-library-membership' ),
							ucfirst( $sub->plan_interval ),
							date_i18n( get_option( 'date_format' ), strtotime( $sub->expires_at ) )
						),
						'link_url'  => '#membership',
					) );
				}
			}
		}

		// 2. Check and expire lapsed subscriptions
		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$lapsed = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i WHERE status = 'active' AND expires_at < %s AND plan_interval != 'lifetime'",
			$table,
			$now
		) );

		if ( ! empty( $lapsed ) ) {
			foreach ( $lapsed as $l_sub ) {
				// Mark expired
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					array( 'status' => 'expired', 'updated_at' => current_time( 'mysql' ) ),
					array( 'id' => $l_sub->id )
				);

				// Revoke capability
				$u = new WP_User( $l_sub->user_id );
				if ( $u->exists() ) {
					$u->remove_cap( 'read_dlm_library' );
				}

				// Idempotent In-App Notification (Checks if expired notice sent within last 30 days)
				$since_30d = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
				if ( ! $db->notification_exists( $l_sub->user_id, 'subscription', 'Subscription Expired', $since_30d ) ) {
					$db->create_notification( array(
						'user_id'   => $l_sub->user_id,
						'type'      => 'subscription',
						'title'     => __( 'Subscription Expired', 'digital-library-membership' ),
						'message'   => __( 'Your digital library membership has expired. Click here to renew your plan and regain full access.', 'digital-library-membership' ),
						'link_url'  => '#membership',
					) );
				}
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

		// In-App Notification
		$db = new DLM_DB();
		/* translators: %s: Plan interval name */
		$sub_title = sprintf( __( 'Subscription Active: %s', 'digital-library-membership' ), ucfirst( $interval ) );
		/* translators: 1: Plan interval name, 2: Expiry date */
		$sub_template = __( 'Your %1$s membership is now active (%2$s). You have unlimited access to the entire digital book catalog.', 'digital-library-membership' );
		$sub_msg      = sprintf(
			$sub_template,
			ucfirst( $interval ),
			( $interval === 'lifetime' ) ? __( 'Lifetime', 'digital-library-membership' ) : date_i18n( get_option( 'date_format' ), strtotime( $expires_at ) )
		);
		$db->create_notification( array(
			'user_id'          => $user_id,
			'type'             => 'subscription',
			'title'            => $sub_title,
			'message'          => $sub_msg,
			'link_url'         => '#membership',
			'deduplicate_days' => 1,
		) );
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
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ! isset( $_GET['activate-multi'] ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=dlm-setup-wizard' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Redirect any page loaded with payment or plan query parameters to the account dashboard
	 */
	public function handle_payment_status_redirect() {
		// Do not redirect in AJAX or REST API requests
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$account_page_id = dlm_get_page_id( 'account' );

		// 1. Redirect if ?plan=... parameter is accessed on non-account page (e.g. /checkout/?plan=yearly)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['plan'] ) && ( ! $account_page_id || ! is_page( $account_page_id ) ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$plan = sanitize_key( wp_unslash( $_GET['plan'] ) );
			$redirect_url = add_query_arg( array( 'plan' => $plan ), dlm_get_page_url( 'account' ) ) . '#checkout';
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// 2. Redirect if payment return status parameter is present
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['payment'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$payment = sanitize_key( wp_unslash( $_GET['payment'] ) );
			$valid_statuses = array( 'success', 'active', 'pending', 'cancelled', 'cancel', 'failed', 'faild' );
			if ( in_array( $payment, $valid_statuses, true ) ) {
				if ( ! $account_page_id || ! is_page( $account_page_id ) ) {
					$query_args = array(
						'payment' => $payment,
					);
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['session_id'] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$query_args['session_id'] = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );
					}
					
					$redirect_url = add_query_arg( $query_args, dlm_get_page_url( 'account' ) );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}
	}



	/**
	 * Restrict non-admin users from accessing wp-admin dashboard
	 */
	public function restrict_admin_area() {
		// Do not redirect AJAX requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Check if the user is logged in but does not have administrative privileges
		if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
			$redirect_url = dlm_get_page_url( 'account' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Register custom Elementor category 'digital-library' at the very top of the editor
	 */
	public function register_elementor_category( $elements_manager ) {
		DLM_Home_Widgets::instance()->register_elementor_categories( $elements_manager );
	}

	/**
	 * Register Elementor Custom Widgets (Featured Slider & Book Countdown)
	 */
	public function register_elementor_featured_slider( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once DLM_PATH . 'includes/class-dlm-elementor-featured-slider.php';
		require_once DLM_PATH . 'includes/class-dlm-elementor-book-countdown.php';

		$widgets_manager->register( new DLM_Elementor_Featured_Slider() );
		$widgets_manager->register( new DLM_Elementor_Book_Countdown() );
	}

	/**
	 * Register Elementor Custom Widgets for older Elementor versions
	 */
	public function register_elementor_featured_slider_legacy( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			require_once DLM_PATH . 'includes/class-dlm-elementor-featured-slider.php';
			require_once DLM_PATH . 'includes/class-dlm-elementor-book-countdown.php';

			$widgets_manager->register_widget_type( new DLM_Elementor_Featured_Slider() );
			$widgets_manager->register_widget_type( new DLM_Elementor_Book_Countdown() );
		}
	}
}

/**
 * Global helper function to get DLM page ID
 */
if ( ! function_exists( 'dlm_get_page_id' ) ) {
	function dlm_get_page_id( $page_key ) {
		return (int) get_option( 'dlm_' . $page_key . '_page_id', 0 );
	}
}

/**
 * Global helper function to get DLM page URL with smart fallbacks
 */
if ( ! function_exists( 'dlm_get_page_url' ) ) {
	function dlm_get_page_url( $page_key ) {
		$page_id = dlm_get_page_id( $page_key );
		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			return get_permalink( $page_id );
		}

		// Fallback for checkout or membership: point to Member Account Dashboard
		if ( 'checkout' === $page_key || 'membership' === $page_key ) {
			$account_id = dlm_get_page_id( 'account' );
			if ( $account_id && 'publish' === get_post_status( $account_id ) ) {
				return get_permalink( $account_id );
			}
		}

		// Fallback for pricing / plan: point to pricing page or account dashboard
		if ( 'pricing' === $page_key || 'plan' === $page_key ) {
			$pricing_id = dlm_get_page_id( 'pricing' );
			if ( $pricing_id && 'publish' === get_post_status( $pricing_id ) ) {
				return get_permalink( $pricing_id );
			}
			$account_id = dlm_get_page_id( 'account' );
			if ( $account_id && 'publish' === get_post_status( $account_id ) ) {
				return get_permalink( $account_id );
			}
		}

		return home_url( '/' . $page_key . '/' );
	}
}

/**
 * Global helper function to check if a user has an active membership subscription
 */
if ( ! function_exists( 'dlm_user_has_active_subscription' ) ) {
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
}

/**
 * Global helper function to determine user access level for a specific book
 *
 * @param int $user_id User ID (defaults to current logged-in user).
 * @param int $book_id Book ID.
 * @return string 'read_download' | 'read_only' | 'locked'
 */
if ( ! function_exists( 'dlm_user_can_access_book' ) ) {
	function dlm_user_can_access_book( $user_id = 0, $book_id = 0 ) {
		if ( ! $book_id ) {
			return 'locked';
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Admin full access override
		if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
			return 'read_download';
		}

		$db = new DLM_DB();
		$book = $db->get_book( $book_id );
		if ( ! $book ) {
			return 'locked';
		}

		$access_type = ! empty( $book->access_type ) ? $book->access_type : 'subscription_only';
		$allow_dl    = ! empty( $book->allow_download );

		// 1. If user has direct completed purchase for this book -> full read + download
		if ( $user_id && $db->has_purchased_book( $user_id, $book_id ) ) {
			return 'read_download';
		}

		// 2. If book is free
		if ( 'free' === $access_type || ( isset( $book->is_free ) && 1 === intval( $book->is_free ) ) ) {
			return $allow_dl ? 'read_download' : 'read_only';
		}

		// 3. If book is purchase only and user hasn't purchased it -> locked
		if ( 'purchase_only' === $access_type ) {
			return 'locked';
		}

		// 4. If book is subscription_only or hybrid -> check active subscription
		if ( $user_id && $db->has_active_membership( $user_id ) ) {
			return $allow_dl ? 'read_download' : 'read_only';
		}

		return 'locked';
	}
}

/**
 * Global helper function to verify Google ReCAPTCHA token (v2 or v3)
 */
if ( ! function_exists( 'dlm_verify_recaptcha' ) ) {
	function dlm_verify_recaptcha( $token ) {
		$recaptcha_mode = get_option( 'dlm_recaptcha_mode', 'production' );
		if ( $recaptcha_mode === 'testing' ) {
			$secret_key = '6LeIxAcTAAAAAGG-vFI1TnFTxW2mYgPGW7N5a3BJ';
		} else {
			$secret_key = get_option( 'dlm_recaptcha_secret_key' );
		}

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
			'timeout' => 5,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( empty( $result['success'] ) || ! $result['success'] ) {
			return false;
		}

		$version = get_option( 'dlm_recaptcha_version', 'v2' );
		if ( 'v3' === $version ) {
			$threshold = floatval( get_option( 'dlm_recaptcha_score_threshold', 0.5 ) );
			return isset( $result['score'] ) && floatval( $result['score'] ) >= $threshold;
		}

		return true;
	}
}

/**
 * Live check ReCAPTCHA connection status
 */
if ( ! function_exists( 'dlm_get_recaptcha_connection_status' ) ) {
	function dlm_get_recaptcha_connection_status() {
		$mode = get_option( 'dlm_recaptcha_mode', 'production' );
		if ( 'testing' === $mode ) {
			return array(
				'status'  => 'testing',
				'message' => __( 'Google Test Keys Active (Always Passes)', 'digital-library-membership' ),
			);
		}

		$site_key   = get_option( 'dlm_recaptcha_site_key', '' );
		$secret_key = get_option( 'dlm_recaptcha_secret_key', '' );

		if ( empty( $site_key ) || empty( $secret_key ) ) {
			return array(
				'status'  => 'not_set',
				'message' => __( 'ReCAPTCHA keys are not configured.', 'digital-library-membership' ),
			);
		}

		return array(
			'status'  => 'connected',
			'message' => __( 'ReCAPTCHA credentials configured.', 'digital-library-membership' ),
		);
	}
}

/**
 * Clear Stripe connection cache
 */
if ( ! function_exists( 'dlm_clear_stripe_conn_transient' ) ) {
	function dlm_clear_stripe_conn_transient() {
		delete_transient( 'dlm_stripe_conn_status' );
	}
}

/**
 * Clear PayPal connection cache
 */
if ( ! function_exists( 'dlm_clear_paypal_conn_transient' ) ) {
	function dlm_clear_paypal_conn_transient() {
		delete_transient( 'dlm_paypal_conn_status' );
	}
}

/**
 * Live check Stripe connection status
 */
if ( ! function_exists( 'dlm_get_stripe_connection_status' ) ) {
	function dlm_get_stripe_connection_status() {
		$status = get_transient( 'dlm_stripe_conn_status' );
		if ( false === $status ) {
			$secret_key = get_option( 'dlm_stripe_secret_key' );
			if ( empty( $secret_key ) ) {
				$status = array( 'status' => 'not_set' );
			} else {
				$response = wp_remote_get( 'https://api.stripe.com/v1/account', array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $secret_key,
					),
					'timeout' => 5,
				) );

				if ( is_wp_error( $response ) ) {
					$status = array( 'status' => 'error', 'message' => $response->get_error_message() );
				} else {
					$code = wp_remote_retrieve_response_code( $response );
					if ( 200 === $code ) {
						$data = json_decode( wp_remote_retrieve_body( $response ), true );
						$status = array(
							'status'       => 'connected',
							'account_id'   => $data['id'] ?? '',
							'business_name'=> $data['business_profile']['name'] ?? $data['settings']['dashboard']['display_name'] ?? '',
							'livemode'     => ! empty( $data['livemode'] ),
						);
					} else {
						$body = json_decode( wp_remote_retrieve_body( $response ), true );
						$status = array( 'status' => 'invalid_key', 'message' => $body['error']['message'] ?? 'Authentication failed' );
					}
				}
			}
			set_transient( 'dlm_stripe_conn_status', $status, 5 * MINUTE_IN_SECONDS );
		}
		return $status;
	}
}

/**
 * Live check PayPal connection status
 */
if ( ! function_exists( 'dlm_get_paypal_connection_status' ) ) {
	function dlm_get_paypal_connection_status() {
		$status = get_transient( 'dlm_paypal_conn_status' );
		if ( false === $status ) {
			$client_id = get_option( 'dlm_paypal_client_id' );
			$mode      = get_option( 'dlm_paypal_mode', 'sandbox' );

			if ( empty( $client_id ) ) {
				$status = array( 'status' => 'not_set' );
			} else {
				$endpoint = ( $mode === 'live' )
					? 'https://api-m.paypal.com/v1/oauth2/token'
					: 'https://api-m.sandbox.paypal.com/v1/oauth2/token';

				$response = wp_remote_post( $endpoint, array(
					'headers' => array(
						'Accept' => 'application/json',
					),
					'body' => array(
						'grant_type' => 'client_credentials',
					),
					'timeout' => 5,
				) );

				if ( is_wp_error( $response ) ) {
					$status = array( 'status' => 'error', 'message' => $response->get_error_message() );
				} else {
					$code = wp_remote_retrieve_response_code( $response );
					if ( 401 === $code ) {
						$status = array( 'status' => 'client_id_found', 'mode' => $mode );
					} elseif ( 200 === $code ) {
						$status = array( 'status' => 'connected', 'mode' => $mode );
					} else {
						$status = array( 'status' => 'unknown', 'code' => $code );
					}
				}
			}
			set_transient( 'dlm_paypal_conn_status', $status, 5 * MINUTE_IN_SECONDS );
		}
		return $status;
	}
}

/**
 * Check all payment gateways status at once
 */
if ( ! function_exists( 'dlm_check_all_gateways_status' ) ) {
	function dlm_check_all_gateways_status() {
		$stripe_live = get_option( 'dlm_stripe_live_mode', '0' );
		$stripe_key  = ( $stripe_live === '1' ) ? get_option( 'dlm_stripe_live_secret_key' ) : get_option( 'dlm_stripe_test_secret_key' );
		$stripe_pub  = ( $stripe_live === '1' ) ? get_option( 'dlm_stripe_live_publishable_key' ) : get_option( 'dlm_stripe_test_publishable_key' );

		$stripe_configured = ( ! empty( $stripe_key ) && ! empty( $stripe_pub ) );

		$paypal_mode = get_option( 'dlm_paypal_mode', 'sandbox' );
		$paypal_client = get_option( 'dlm_paypal_client_id' );
		$paypal_configured = ! empty( $paypal_client );

		$results = array(
			'stripe' => array(
				'configured' => $stripe_configured,
				'mode'       => ( $stripe_live === '1' ) ? 'live' : 'test',
			),
			'paypal' => array(
				'configured' => $paypal_configured,
				'mode'       => $paypal_mode,
			),
		);

		return $results;
	}
}

/**
 * Generate a signed, time-limited download URL for a book
 */
if ( ! function_exists( 'dlm_generate_download_token' ) ) {
	function dlm_generate_download_token( $user_id, $book_id, $ttl_seconds = 3600 ) {
		$expires = time() + $ttl_seconds;
		$token   = hash_hmac( 'sha256', $user_id . '|' . $book_id . '|' . $expires, wp_salt( 'nonce' ) );

		return array(
			'token'   => $token,
			'expires' => $expires,
			'url'     => add_query_arg(
				array(
					'uid'     => $user_id,
					'expires' => $expires,
					'token'   => $token,
				),
				rest_url( "dlm/v1/book/{$book_id}/download" )
			),
		);
	}
}

/**
 * Verify signed, time-limited download token
 */
if ( ! function_exists( 'dlm_verify_download_token' ) ) {
	function dlm_verify_download_token( $user_id, $book_id, $token, $expires ) {
		if ( empty( $token ) || empty( $expires ) || ! $user_id || ! $book_id ) {
			return false;
		}

		if ( intval( $expires ) < time() ) {
			return false; // Token has expired
		}

		$expected = hash_hmac( 'sha256', $user_id . '|' . $book_id . '|' . $expires, wp_salt( 'nonce' ) );
		return hash_equals( $expected, $token );
	}
}

/**
 * Get all subscription packages from single source of truth
 */
if ( ! function_exists( 'dlm_get_packages' ) ) {
	function dlm_get_packages() {
		$packages = get_option( 'dlm_subscription_packages' );

		if ( ! is_array( $packages ) || empty( $packages ) ) {
			$features_monthly_raw = get_option( 'dlm_features_monthly', '' );
			if ( ! empty( $features_monthly_raw ) ) {
				$features_monthly = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $features_monthly_raw ) ) ) );
			} else {
				$features_monthly = array(
					__( 'Unlimited digital reading', 'digital-library-membership' ),
					__( 'Real-time reading journal logs', 'digital-library-membership' ),
					__( 'Saves streaks & achievements', 'digital-library-membership' ),
				);
			}

			$features_yearly_raw = get_option( 'dlm_features_yearly', '' );
			if ( ! empty( $features_yearly_raw ) ) {
				$features_yearly = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $features_yearly_raw ) ) ) );
			} else {
				$features_yearly = array(
					__( 'Everything in Monthly', 'digital-library-membership' ),
					__( 'Save ~30% annually', 'digital-library-membership' ),
					__( 'Collector badges unlocked', 'digital-library-membership' ),
				);
			}

			$features_lifetime_raw = get_option( 'dlm_features_lifetime', '' );
			if ( ! empty( $features_lifetime_raw ) ) {
				$features_lifetime = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $features_lifetime_raw ) ) ) );
			} else {
				$features_lifetime = array(
					__( 'Unlimited permanent access', 'digital-library-membership' ),
					__( 'No recurring bills or fees', 'digital-library-membership' ),
					__( 'All future books included', 'digital-library-membership' ),
				);
			}

			$packages = array(
				'monthly' => array(
					'id'              => 'monthly',
					'name'            => __( 'Monthly Access', 'digital-library-membership' ),
					'badge'           => __( 'The Reader', 'digital-library-membership' ),
					'description'     => __( 'Instant access to all digital books billed every month.', 'digital-library-membership' ),
					'interval'        => 'monthly',
					'price'           => floatval( get_option( 'dlm_pricing_monthly', '9.99' ) ),
					'features'        => array_values( $features_monthly ),
					'status'          => 'active',
					'stripe_price_id' => get_option( 'dlm_stripe_monthly_price_id', '' ),
					'paypal_plan_id'  => get_option( 'dlm_paypal_monthly_plan_id', '' ),
					'wc_product_id'   => intval( get_option( 'dlm_wc_monthly_product', 0 ) ),
				),
				'yearly' => array(
					'id'              => 'yearly',
					'name'            => __( 'Yearly Membership', 'digital-library-membership' ),
					'badge'           => __( 'The Scholar', 'digital-library-membership' ),
					'description'     => __( 'Full year of unlimited reading. Best value for avid readers.', 'digital-library-membership' ),
					'interval'        => 'yearly',
					'price'           => floatval( get_option( 'dlm_pricing_yearly', '99.99' ) ),
					'features'        => array_values( $features_yearly ),
					'status'          => 'active',
					'stripe_price_id' => get_option( 'dlm_stripe_yearly_price_id', '' ),
					'paypal_plan_id'  => get_option( 'dlm_paypal_yearly_plan_id', '' ),
					'wc_product_id'   => intval( get_option( 'dlm_wc_yearly_product', 0 ) ),
				),
				'lifetime' => array(
					'id'              => 'lifetime',
					'name'            => __( 'Lifetime Access', 'digital-library-membership' ),
					'badge'           => __( 'The Collector', 'digital-library-membership' ),
					'description'     => __( 'One-time payment for permanent access to all current and future books.', 'digital-library-membership' ),
					'interval'        => 'lifetime',
					'price'           => floatval( get_option( 'dlm_pricing_lifetime', '199.99' ) ),
					'features'        => array_values( $features_lifetime ),
					'status'          => 'active',
					'stripe_price_id' => get_option( 'dlm_stripe_lifetime_price_id', '' ),
					'paypal_plan_id'  => get_option( 'dlm_paypal_lifetime_plan_id', '' ),
					'wc_product_id'   => intval( get_option( 'dlm_wc_lifetime_product', 0 ) ),
				),
			);

			update_option( 'dlm_subscription_packages', $packages );
		}

		return $packages;
	}
}

/**
 * Get single subscription package by ID or interval
 */
if ( ! function_exists( 'dlm_get_package' ) ) {
	function dlm_get_package( $id ) {
		$packages = dlm_get_packages();
		if ( isset( $packages[ $id ] ) ) {
			return $packages[ $id ];
		}
		foreach ( $packages as $pkg ) {
			if ( isset( $pkg['id'] ) && $pkg['id'] === $id ) {
				return $pkg;
			}
			if ( isset( $pkg['interval'] ) && $pkg['interval'] === $id ) {
				return $pkg;
			}
		}
		return null;
	}
}

/**
 * Get live active subscriber count for a package
 */
if ( ! function_exists( 'dlm_get_package_subscriber_count' ) ) {
	function dlm_get_package_subscriber_count( $package_id, $interval = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dlm_subscriptions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return 0;
		}

		if ( empty( $interval ) ) {
			$pkg = dlm_get_package( $package_id );
			$interval = $pkg && ! empty( $pkg['interval'] ) ? $pkg['interval'] : $package_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE status = %s AND (plan_interval = %s OR plan_interval = %s)",
				$table,
				'active',
				$package_id,
				$interval
			)
		);

		return intval( $count );
	}
}

/**
 * Save packages array to options (single source of truth)
 */
if ( ! function_exists( 'dlm_save_packages' ) ) {
	function dlm_save_packages( $packages ) {
		if ( ! is_array( $packages ) ) {
			return false;
		}

		$updated = update_option( 'dlm_subscription_packages', $packages );
		delete_transient( 'dlm_analytics_summary' );

		return $updated;
	}
}

/**
 * Get active payment engine ('default' or 'woocommerce')
 */
if ( ! function_exists( 'dlm_get_payment_engine' ) ) {
	function dlm_get_payment_engine() {
		$engine = get_option( 'dlm_payment_engine', 'default' );
		if ( 'woocommerce' === $engine && ! class_exists( 'WooCommerce' ) ) {
			return 'default';
		}
		return $engine ? $engine : 'default';
	}
}
