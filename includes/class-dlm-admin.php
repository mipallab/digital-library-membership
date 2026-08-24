<?php
/**
 * Admin Dashboard Manager
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, Generic.PHP.ForbiddenFunctions.Found, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
class DLM_Admin {

	private $db;
	private $checkout;

	public function __construct( $db, $checkout ) {
		$this->db       = $db;
		$this->checkout = $checkout;
	}

	/**
	 * Add plugin admin menus
	 */
	public function add_admin_menu() {
		global $wpdb;
		$table_subs = $wpdb->prefix . 'dlm_subscriptions';
		$table_tx   = $wpdb->prefix . 'dlm_transactions';
		$pending_subs = 0;
		$pending_tx   = 0;
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_subs ) ) === $table_subs ) {
			$pending_subs = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = %s", $table_subs, 'pending_approval' ) ) );
		}
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_tx ) ) === $table_tx ) {
			$pending_tx = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = %s", $table_tx, 'waiting_approval' ) ) );
		}

		$total_pending = $pending_subs + $pending_tx;

		$menu_title = __( 'Digital Library', 'digital-library-membership' );
		if ( $total_pending > 0 ) {
			$menu_title .= sprintf(
				' <span class="awaiting-mod update-plugins count-%d"><span class="pending-count">%d</span></span>',
				$total_pending,
				$total_pending
			);
		}

		add_menu_page(
			__( 'Digital Library', 'digital-library-membership' ),
			$menu_title,
			'manage_dlm_library',
			'dlm-library',
			array( $this, 'render_admin_dashboard' ),
			'dashicons-book',
			30
		);

		// Submenu 1: Dashboard
		add_submenu_page(
			'dlm-library',
			__( 'Dashboard', 'digital-library-membership' ),
			__( 'Dashboard', 'digital-library-membership' ),
			'manage_dlm_library',
			'dlm-library',
			array( $this, 'render_admin_dashboard' )
		);

		// Submenu 2: Book Categories
		add_submenu_page(
			'dlm-library',
			__( 'Book Categories', 'digital-library-membership' ),
			__( 'Book Categories', 'digital-library-membership' ),
			'manage_dlm_library',
			'edit-tags.php?taxonomy=dlm_book_category&post_type=dlm_book'
		);

		// Submenu 3: Book Tags
		add_submenu_page(
			'dlm-library',
			__( 'Book Tags', 'digital-library-membership' ),
			__( 'Book Tags', 'digital-library-membership' ),
			'manage_dlm_library',
			'edit-tags.php?taxonomy=dlm_book_tag&post_type=dlm_book'
		);

		// Hidden setup wizard menu page
		add_submenu_page(
			null, // No parent menu = hidden
			__( 'Plugin Setup Wizard', 'digital-library-membership' ),
			__( 'Setup Wizard', 'digital-library-membership' ),
			'manage_dlm_library',
			'dlm-setup-wizard',
			array( $this, 'render_setup_wizard' )
		);
	}

	/**
	 * Register settings for gateways and subscription pricing
	 */
	public function register_settings() {
		$settings = array(
			'dlm_stripe_secret_key',
			'dlm_stripe_publishable_key',
			'dlm_stripe_webhook_secret',
			'dlm_paypal_client_id',
			'dlm_paypal_secret_key',
			'dlm_paypal_webhook_id',
			'dlm_manual_payment_instructions',
			'dlm_privacy_policy_page_id',
			'dlm_terms_page_id',
			'dlm_recaptcha_version',
			'dlm_recaptcha_site_key',
			'dlm_recaptcha_secret_key',
			'dlm_recaptcha_mode',
			'dlm_setup_completed',
			'dlm_github_token',
			'dlm_payment_engine',
			'dlm_enable_google_login',
			'dlm_google_client_id',
			'dlm_google_client_secret',
			'dlm_enable_apple_login',
			'dlm_apple_services_id',
			'dlm_apple_team_id',
			'dlm_apple_key_id',
			'dlm_delete_data_on_uninstall',
		);

		foreach ( $settings as $opt ) {
			register_setting( 'dlm_settings_group', $opt, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		}

		register_setting( 'dlm_settings_group', 'dlm_apple_private_key', array(
			'sanitize_callback' => 'sanitize_textarea_field',
		) );

		register_setting( 'dlm_settings_group', 'dlm_currency', array(
			'default'           => 'USD',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		register_setting( 'dlm_settings_group', 'dlm_max_upload_size', array(
			'type'              => 'integer',
			'default'           => 50,
			'sanitize_callback' => array( $this, 'sanitize_max_upload_size' ),
		) );
	}

	/**
	 * Calculate the maximum file upload size supported by the server environment in Megabytes.
	 */
	public function get_server_max_upload_size() {
		$upload_max = wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
		$post_max   = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
		$max_bytes  = min( $upload_max, $post_max );
		return round( $max_bytes / ( 1024 * 1024 ), 2 ); // in MB
	}

	/**
	 * Sanitize and validate max upload size setting against server configuration.
	 */
	public function sanitize_max_upload_size( $value ) {
		$value = intval( $value );
		$server_max = intval( $this->get_server_max_upload_size() );
		if ( $value > $server_max ) {
			add_settings_error(
				'dlm_max_upload_size',
				'dlm_max_upload_size_error',
				sprintf(
					/* translators: 1: Requested upload size in MB, 2: Server max upload size in MB */
					__( 'Cannot set max upload size (%1$d MB) greater than the server limit (%2$d MB). Reverted to default value of 50 MB.', 'digital-library-membership' ),
					$value,
					$server_max
				),
				'error'
			);
			return 50;
		}
		if ( $value <= 0 ) {
			return 50;
		}
		return $value;
	}

	/**
	 * Render the Setup Wizard standalone page
	 */
	public function render_setup_wizard() {
		DLM_Security::check_admin_capabilities();

		// Auto create pages if they aren't already created (insurance check)
		DLM_Activator::create_pages();

		// Enqueue FontAwesome for admin (normally handled, but we ensure it is loaded)
		wp_enqueue_style( 'font-awesome', DLM_URL . 'admin/css/font-awesome.min.css', array(), DLM_VERSION );

		// Load template
		include DLM_PATH . 'admin/templates/setup-wizard.php';
	}

	/**
	 * AJAX handler to save Setup Wizard configurations
	 */
	public function ajax_save_setup_wizard() {
		// Check security
		if ( ! current_user_can( 'manage_dlm_library' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'digital-library-membership' ) ) );
		}

		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$step = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : '';

		if ( $step === 'pages' ) {
			// Step 1: Pages are automatically verified and created. 
			// We just verify they exist.
			wp_send_json_success( array( 'message' => __( 'Pages verified and active.', 'digital-library-membership' ) ) );
		} elseif ( $step === 'legal' ) {
			// Step 2: Legal Pages settings
			$privacy_policy_id = isset( $_POST['privacy_policy_page_id'] ) ? intval( $_POST['privacy_policy_page_id'] ) : 0;
			$terms_id          = isset( $_POST['terms_page_id'] ) ? intval( $_POST['terms_page_id'] ) : 0;

			update_option( 'dlm_privacy_policy_page_id', $privacy_policy_id );
			update_option( 'dlm_terms_page_id', $terms_id );

			wp_send_json_success( array( 'message' => __( 'Legal pages successfully matched.', 'digital-library-membership' ) ) );
		} elseif ( $step === 'recaptcha' ) {
			// Step 3: Google ReCAPTCHA configurations
			$recaptcha_version = isset( $_POST['recaptcha_version'] ) ? sanitize_key( $_POST['recaptcha_version'] ) : 'v2';
			$recaptcha_site    = isset( $_POST['recaptcha_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_site_key'] ) ) : '';
			$recaptcha_secret  = isset( $_POST['recaptcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_secret_key'] ) ) : '';
			$import_demo       = ! empty( $_POST['import_demo'] ) && ( $_POST['import_demo'] === '1' || $_POST['import_demo'] === 'true' );

			update_option( 'dlm_recaptcha_version', $recaptcha_version );
			update_option( 'dlm_recaptcha_site_key', $recaptcha_site );
			update_option( 'dlm_recaptcha_secret_key', $recaptcha_secret );

			// Save Social Login if provided
			if ( ! empty( $_POST['google_client_id'] ) ) {
				update_option( 'dlm_google_client_id', sanitize_text_field( wp_unslash( $_POST['google_client_id'] ) ) );
				if ( ! empty( $_POST['google_client_secret'] ) ) {
					update_option( 'dlm_google_client_secret', sanitize_text_field( wp_unslash( $_POST['google_client_secret'] ) ) );
				}
				update_option( 'dlm_enable_google_login', '1' );
			}

			// Mark setup as completed!
			update_option( 'dlm_setup_completed', 'yes' );

			// If admin opted to import demo data during wizard setup
			if ( $import_demo ) {
				$demo_mgr = new DLM_Demo_Data( $this->db, $this->checkout );
				$demo_mgr->import();
			}

			wp_send_json_success( array( 'message' => __( 'Configuration saved. Setup completed!', 'digital-library-membership' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Invalid setup step.', 'digital-library-membership' ) ) );
	}

	/**
	 * AJAX handler to install and activate recommended plugins (Elementor, WooCommerce)
	 */
	public function ajax_install_activate_plugin() {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied. Administrator capabilities required.', 'digital-library-membership' ) ) );
		}

		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$slug = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';
		$allowed_plugins = array(
			'elementor'   => array(
				'name' => 'Elementor',
				'file' => 'elementor/elementor.php',
				'slug' => 'elementor',
			),
			'woocommerce' => array(
				'name' => 'WooCommerce',
				'file' => 'woocommerce/woocommerce.php',
				'slug' => 'woocommerce',
			),
		);

		if ( ! isset( $allowed_plugins[ $slug ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid plugin requested.', 'digital-library-membership' ) ) );
		}

		$plugin_info = $allowed_plugins[ $slug ];
		$plugin_file = $plugin_info['file'];

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// 1. Check if already active
		if ( is_plugin_active( $plugin_file ) ) {
			/* translators: %s: plugin name */
			$msg_active = sprintf( __( '%s is already active.', 'digital-library-membership' ), $plugin_info['name'] );
			wp_send_json_success( array(
				'status'  => 'active',
				'message' => $msg_active,
			) );
		}

		// 2. Check if installed but inactive
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			$activated = activate_plugin( $plugin_file );
			if ( is_wp_error( $activated ) ) {
				wp_send_json_error( array( 'message' => $activated->get_error_message() ) );
			}
			/* translators: %s: plugin name */
			$msg_activated = sprintf( __( '%s activated successfully!', 'digital-library-membership' ), $plugin_info['name'] );
			wp_send_json_success( array(
				'status'  => 'active',
				'message' => $msg_activated,
			) );
		}

		// 3. Not installed — install from WordPress.org API
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$api = plugins_api( 'plugin_information', array(
			'slug'   => $plugin_info['slug'],
			'fields' => array( 'sections' => false ),
		) );

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( array( 'message' => $api->get_error_message() ) );
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$install  = $upgrader->install( $api->download_link );

		if ( is_wp_error( $install ) ) {
			wp_send_json_error( array( 'message' => $install->get_error_message() ) );
		}

		if ( true === $install || is_null( $install ) ) {
			$activated = activate_plugin( $plugin_file );
			if ( is_wp_error( $activated ) ) {
				wp_send_json_error( array( 'message' => $activated->get_error_message() ) );
			}
			/* translators: %s: plugin name */
			$msg_installed = sprintf( __( '%s installed and activated successfully!', 'digital-library-membership' ), $plugin_info['name'] );
			wp_send_json_success( array(
				'status'  => 'active',
				'message' => $msg_installed,
			) );
		}

		/* translators: %s: plugin name */
		$msg_failed = sprintf( __( 'Could not install %s. Please install it manually from Plugins > Add New.', 'digital-library-membership' ), $plugin_info['name'] );
		wp_send_json_error( array( 'message' => $msg_failed ) );
	}

	/**
	 * AJAX handler: Import demo data (supports modular steps or full execution)
	 */
	public function ajax_import_demo_data() {
		if ( ! current_user_can( 'manage_dlm_library' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'digital-library-membership' ) ) );
		}

		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$step     = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : 'all';
		$demo_mgr = new DLM_Demo_Data( $this->db, $this->checkout );

		switch ( $step ) {
			case 'init':
				$result = $demo_mgr->import_step_init();
				break;
			case 'books':
				$result = $demo_mgr->import_step_books();
				break;
			case 'users':
				$result = $demo_mgr->import_step_users();
				break;
			case 'orders':
				$result = $demo_mgr->import_step_orders();
				break;
			case 'finalize':
				$result = $demo_mgr->import_step_finalize();
				break;
			case 'all':
			default:
				$result = $demo_mgr->import();
				break;
		}

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler: Remove demo data (supports modular steps or full execution)
	 */
	public function ajax_remove_demo_data() {
		if ( ! current_user_can( 'manage_dlm_library' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'digital-library-membership' ) ) );
		}

		check_ajax_referer( 'dlm_public_nonce', 'nonce' );

		$step     = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : 'all';
		$demo_mgr = new DLM_Demo_Data( $this->db, $this->checkout );

		switch ( $step ) {
			case 'orders':
				$result = $demo_mgr->remove_step_orders();
				break;
			case 'catalog':
				$result = $demo_mgr->remove_step_catalog();
				break;
			case 'users_finalize':
				$result = $demo_mgr->remove_step_users_finalize();
				break;
			case 'all':
			default:
				$result = $demo_mgr->remove();
				break;
		}

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Render Admin Dashboard tabs
	 */
	public function render_admin_dashboard() {
		DLM_Security::check_admin_capabilities();

		// Fetch dynamic metrics and data with transient caching
		$summary = get_transient( 'dlm_analytics_summary' );
		if ( false === $summary ) {
			$summary = $this->db->get_analytics_summary();
			set_transient( 'dlm_analytics_summary', $summary, 15 * MINUTE_IN_SECONDS );
		}

		$trending_books = get_transient( 'dlm_trending_books' );
		if ( false === $trending_books ) {
			$trending_books = $this->db->get_trending_books( 10 );
			set_transient( 'dlm_trending_books', $trending_books, 15 * MINUTE_IN_SECONDS );
		}

		$currency       = get_option( 'dlm_currency', 'USD' );
		$books          = $this->db->get_books( 'all' );
		$book_purchases = $this->db->get_book_purchases();

		// Demo data statistics
		$demo_mgr       = new DLM_Demo_Data( $this->db, $this->checkout );
		$is_demo_active = $demo_mgr->is_demo_imported();
		$demo_stats     = $demo_mgr->get_demo_stats();

		// Compute metrics for the catalog views
		$total_books     = count( $books );
		$published_books = 0;
		$draft_books     = 0;
		$authors_list    = array();
		foreach ( $books as $b ) {
			if ( $b->status === 'publish' ) {
				$published_books++;
			} else {
				$draft_books++;
			}
			if ( ! empty( $b->author ) ) {
				$authors_list[] = $b->author;
			}
		}
		$total_authors = count( array_unique( $authors_list ) );
		$subscribers   = isset( $summary['subscribers_list'] ) ? $summary['subscribers_list'] : array();
		$packages      = dlm_get_packages();

		// Render SPA dashboard template
		include DLM_PATH . 'admin/templates/admin-dashboard.php';
	}

	/**
	 * Process form submission to upload/save book metadata and secure file
	 */
	public function handle_save_book() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_save_book_nonce', 'dlm_nonce' );

		if ( empty( $_POST['title'] ) || empty( $_FILES['book_file']['name'] ) ) {
			wp_die( esc_html__( 'Please supply all required parameters.', 'digital-library-membership' ) );
		}

		$file = $_FILES['book_file'];

		// Validate file size limit
		$max_size_mb = intval( get_option( 'dlm_max_upload_size', 50 ) );
		$max_size_bytes = $max_size_mb * 1024 * 1024;
		if ( $file['size'] > $max_size_bytes ) {
			$uploaded_size_mb = round( $file['size'] / ( 1024 * 1024 ), 2 );
			wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&error=file_too_large&max_size=' . $max_size_mb . '&uploaded_size=' . $uploaded_size_mb ) );
			exit;
		}
		$file_name = sanitize_file_name( $file['name'] );
		$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

		// Validate PDF only
		if ( $file_ext !== 'pdf' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&error=pdf_only' ) );
			exit;
		}

		// Verify the real MIME type of the file content using finfo or wp_check_filetype_and_ext
		$real_mime = '';
		if ( function_exists( 'finfo_open' ) ) {
			$finfo     = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$real_mime = mime_content_type( $file['tmp_name'] );
		} else {
			$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file_name );
			$real_mime   = $wp_filetype['type'];
		}

		if ( strpos( $real_mime, 'application/pdf' ) === false ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&error=pdf_only' ) );
			exit;
		}

		// Secure Upload Destination
		$secure_dir = DLM_PROTECTED_DIR;
		if ( ! file_exists( $secure_dir ) ) {
			wp_mkdir_p( $secure_dir );
		}

		// Save unique file path
		$random_prefix = wp_generate_password( 24, false );
		$secure_file_name = $random_prefix . '-' . $file_name;
		$target_path = $secure_dir . '/' . $secure_file_name;

		if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
			wp_die( esc_html__( 'Failed to move uploaded document to secure repository.', 'digital-library-membership' ) );
		}

		$access_type  = isset( $_POST['access_type'] ) ? sanitize_text_field( wp_unslash( $_POST['access_type'] ) ) : 'subscription_only';
		$price        = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00;
		$publish_date = ! empty( $_POST['publish_date'] ) ? sanitize_text_field( wp_unslash( $_POST['publish_date'] ) ) : null;
		$is_featured    = ! empty( $_POST['is_featured'] ) ? 1 : 0;
		$featured_title = isset( $_POST['featured_title'] ) ? sanitize_text_field( wp_unslash( $_POST['featured_title'] ) ) : '';
		$featured_desc  = isset( $_POST['featured_description'] ) ? wp_kses_post( wp_unslash( $_POST['featured_description'] ) ) : null;
		$banner_id      = isset( $_POST['featured_banner_id'] ) ? intval( $_POST['featured_banner_id'] ) : 0;
		$banner_url     = isset( $_POST['featured_banner_url'] ) ? esc_url_raw( wp_unslash( $_POST['featured_banner_url'] ) ) : '';
		$btn1_label     = isset( $_POST['featured_button_1_label'] ) ? sanitize_text_field( wp_unslash( $_POST['featured_button_1_label'] ) ) : '';
		$btn2_label     = isset( $_POST['featured_button_2_label'] ) ? sanitize_text_field( wp_unslash( $_POST['featured_button_2_label'] ) ) : '';
		$featured_order = isset( $_POST['featured_order'] ) ? intval( $_POST['featured_order'] ) : 0;

		$status = 'publish';
		if ( isset( $_POST['status'] ) && sanitize_text_field( wp_unslash( $_POST['status'] ) ) === 'draft' ) {
			$status = 'draft';
		} elseif ( ! empty( $publish_date ) && strtotime( $publish_date ) > current_time( 'timestamp' ) ) {
			$status = 'future';
		}

		// Insert DB entry
		$book_data = array(
			'title'                   => sanitize_text_field( wp_unslash( $_POST['title'] ) ),
			'author'                  => sanitize_text_field( wp_unslash( $_POST['author'] ) ),
			'description'             => wp_kses_post( wp_unslash( $_POST['description'] ) ),
			'cover_image_url'         => esc_url_raw( wp_unslash( $_POST['cover_image_url'] ) ),
			'file_path'               => $target_path,
			'file_type'               => $file_ext,
			'status'                  => $status,
			'access_type'             => $access_type,
			'price'                   => $price,
			'publish_date'            => $publish_date,
			'is_featured'             => $is_featured,
			'featured_title'          => $featured_title,
			'featured_description'    => $featured_desc,
			'featured_banner_id'      => $banner_id,
			'featured_banner_url'     => $banner_url,
			'featured_button_1_label' => $btn1_label,
			'featured_button_2_label' => $btn2_label,
			'featured_order'          => $featured_order,
		);

		$book_id = $this->db->insert_book( $book_data );

		if ( $book_id ) {
			// Save category
			if ( isset( $_POST['book_category'] ) ) {
				$cat_id = intval( $_POST['book_category'] );
				wp_set_object_terms( $book_id, $cat_id, 'dlm_book_category' );
			}
			// Save tags
			if ( isset( $_POST['book_tags'] ) ) {
				$tags = sanitize_text_field( $_POST['book_tags'] );
				$tag_names = array_map( 'trim', explode( ',', $tags ) );
				$tag_names = array_filter( $tag_names );
				wp_set_object_terms( $book_id, $tag_names, 'dlm_book_tag' );
			}

			// Synchronize with WooCommerce virtual product if purchase_only or hybrid
			$wc_manager = new DLM_WooCommerce( $this->db, $this->checkout );
			$wc_manager->sync_book_wc_product( $book_id, $book_data );

			delete_transient( 'dlm_analytics_summary' );
			delete_transient( 'dlm_trending_books' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&success=add_book' ) );
		exit;
	}

	/**
	 * Process form submission to update book metadata and optionally replace file
	 */
	public function handle_edit_book() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_edit_book_nonce', 'dlm_nonce' );

		$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
		if ( ! $book_id ) {
			wp_die( esc_html__( 'Invalid book ID.', 'digital-library-membership' ) );
		}

		if ( empty( $_POST['title'] ) ) {
			wp_die( esc_html__( 'Please supply a book title.', 'digital-library-membership' ) );
		}

		$book = $this->db->get_book( $book_id );
		if ( ! $book ) {
			wp_die( esc_html__( 'Book not found.', 'digital-library-membership' ) );
		}

		$access_type  = isset( $_POST['access_type'] ) ? sanitize_text_field( wp_unslash( $_POST['access_type'] ) ) : 'subscription_only';
		$price        = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00;
		$publish_date = ! empty( $_POST['publish_date'] ) ? sanitize_text_field( wp_unslash( $_POST['publish_date'] ) ) : null;
		$is_featured    = ! empty( $_POST['is_featured'] ) ? 1 : 0;
		$featured_title = isset( $_POST['featured_title'] ) ? sanitize_text_field( wp_unslash( $_POST['featured_title'] ) ) : '';
		$featured_desc  = isset( $_POST['featured_description'] ) ? wp_kses_post( wp_unslash( $_POST['featured_description'] ) ) : null;
		$banner_id      = isset( $_POST['featured_banner_id'] ) ? intval( $_POST['featured_banner_id'] ) : 0;
		$banner_url     = isset( $_POST['featured_banner_url'] ) ? esc_url_raw( wp_unslash( $_POST['featured_banner_url'] ) ) : '';
		$btn1_label     = isset( $_POST['featured_button_1_label'] ) ? sanitize_text_field( wp_unslash( $_POST['featured_button_1_label'] ) ) : '';
		$btn2_label     = isset( $_POST['featured_button_2_label'] ) ? sanitize_text_field( wp_unslash( $_POST['featured_button_2_label'] ) ) : '';
		$featured_order = isset( $_POST['featured_order'] ) ? intval( $_POST['featured_order'] ) : 0;

		$status = 'publish';
		if ( isset( $_POST['status'] ) && $_POST['status'] === 'draft' ) {
			$status = 'draft';
		} elseif ( ! empty( $publish_date ) && strtotime( $publish_date ) > current_time( 'timestamp' ) ) {
			$status = 'future';
		}

		$book_data = array(
			'title'                   => sanitize_text_field( $_POST['title'] ),
			'author'                  => sanitize_text_field( $_POST['author'] ),
			'description'             => wp_kses_post( $_POST['description'] ),
			'cover_image_url'         => esc_url_raw( $_POST['cover_image_url'] ),
			'status'                  => $status,
			'access_type'             => $access_type,
			'price'                   => $price,
			'publish_date'            => $publish_date,
			'is_featured'             => $is_featured,
			'featured_title'          => $featured_title,
			'featured_description'    => $featured_desc,
			'featured_banner_id'      => $banner_id,
			'featured_banner_url'     => $banner_url,
			'featured_button_1_label' => $btn1_label,
			'featured_button_2_label' => $btn2_label,
			'featured_order'          => $featured_order,
		);

		// Check if a new file was uploaded
		if ( ! empty( $_FILES['book_file']['name'] ) ) {
			$file = $_FILES['book_file'];

			// Validate file size limit
			$max_size_mb = intval( get_option( 'dlm_max_upload_size', 50 ) );
			$max_size_bytes = $max_size_mb * 1024 * 1024;
			if ( $file['size'] > $max_size_bytes ) {
				$uploaded_size_mb = round( $file['size'] / ( 1024 * 1024 ), 2 );
				wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&error=file_too_large&max_size=' . $max_size_mb . '&uploaded_size=' . $uploaded_size_mb ) );
				exit;
			}
			$file_name = sanitize_file_name( $file['name'] );
			$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

			// Validate PDF only
			if ( $file_ext !== 'pdf' ) {
				wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&error=pdf_only' ) );
				exit;
			}

			// Verify the real MIME type of the file content using finfo or wp_check_filetype_and_ext
			$real_mime = '';
			if ( function_exists( 'finfo_open' ) ) {
				$finfo     = finfo_open( FILEINFO_MIME_TYPE );
				$real_mime = finfo_file( $finfo, $file['tmp_name'] );
				finfo_close( $finfo );
			} elseif ( function_exists( 'mime_content_type' ) ) {
				$real_mime = mime_content_type( $file['tmp_name'] );
			} else {
				$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file_name );
				$real_mime   = $wp_filetype['type'];
			}

			if ( strpos( $real_mime, 'application/pdf' ) === false ) {
				wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&error=pdf_only' ) );
				exit;
			}

			// Secure Upload Destination
			$secure_dir = DLM_PROTECTED_DIR;
			if ( ! file_exists( $secure_dir ) ) {
				wp_mkdir_p( $secure_dir );
			}

			// Delete old file
			if ( file_exists( $book->file_path ) ) {
				wp_delete_file( $book->file_path );
			}

			// Save unique file path
			$random_prefix = wp_generate_password( 24, false );
			$secure_file_name = $random_prefix . '-' . $file_name;
			$target_path = $secure_dir . '/' . $secure_file_name;

			if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
				wp_die( esc_html__( 'Failed to move uploaded document to secure repository.', 'digital-library-membership' ) );
			}

			$book_data['file_path'] = $target_path;
			$book_data['file_type'] = $file_ext;
		}

		$this->db->update_book( $book_id, $book_data );

		// Save category
		if ( isset( $_POST['book_category'] ) ) {
			$cat_id = intval( $_POST['book_category'] );
			wp_set_object_terms( $book_id, $cat_id, 'dlm_book_category' );
		}
		// Save tags
		if ( isset( $_POST['book_tags'] ) ) {
			$tags = sanitize_text_field( $_POST['book_tags'] );
			$tag_names = array_map( 'trim', explode( ',', $tags ) );
			$tag_names = array_filter( $tag_names );
			wp_set_object_terms( $book_id, $tag_names, 'dlm_book_tag' );
		}

		// Synchronize with WooCommerce virtual product if purchase_only or hybrid
		$wc_manager = new DLM_WooCommerce( $this->db, $this->checkout );
		$wc_manager->sync_book_wc_product( $book_id, $book_data );

		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&success=edit_book' ) );
		exit;
	}

	/**
	 * Process form submission to delete book metadata and physical file
	 */
	public function handle_delete_book() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_delete_book_nonce', 'dlm_nonce' );

		$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
		if ( $book_id ) {
			$this->db->delete_book( $book_id );
			delete_transient( 'dlm_analytics_summary' );
			delete_transient( 'dlm_trending_books' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=books&success=delete_book' ) );
		exit;
	}

	/**
	 * Process manual user capability overrides
	 */
	public function handle_member_override() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_member_override_nonce', 'dlm_nonce' );

		$user_email = sanitize_email( $_POST['user_email'] );
		$status     = sanitize_text_field( $_POST['override_status'] );
		$name       = isset( $_POST['display_name'] ) ? sanitize_text_field( $_POST['display_name'] ) : '';

		$user = get_user_by( 'email', $user_email );
		if ( ! $user ) {
			wp_die( esc_html__( 'No user registered with that email address.', 'digital-library-membership' ) );
		}

		if ( ! empty( $name ) ) {
			wp_update_user( array(
				'ID'           => $user->ID,
				'display_name' => $name,
			) );
		}

		$u = new WP_User( $user->ID );

		if ( $status === 'active' ) {
			update_user_meta( $user->ID, 'dlm_manual_override', 'active' );
			$u->add_cap( 'read_dlm_library' );

			$plan_interval = isset( $_POST['plan_interval'] ) ? sanitize_text_field( $_POST['plan_interval'] ) : 'monthly';
			$custom_expiry = isset( $_POST['expires_at'] ) ? sanitize_text_field( $_POST['expires_at'] ) : '';

			if ( $plan_interval === 'lifetime' ) {
				$expires_at = '2099-12-31 23:59:59';
			} elseif ( ! empty( $custom_expiry ) ) {
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $custom_expiry . ' 23:59:59' ) );
			} else {
				if ( $plan_interval === 'monthly' ) {
					$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
				} else {
					$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				}
			}

			$sub = $this->db->get_subscription_by_user( $user->ID );
			if ( $sub ) {
				global $wpdb;
				$table = $this->db->get_table_name( 'subscriptions' );
				$wpdb->update(
					$table,
					array(
						'status'        => 'active',
						'plan_interval' => $plan_interval,
						'expires_at'    => $expires_at,
						'updated_at'    => current_time( 'mysql' ),
					),
					array( 'user_id' => $user->ID )
				);
			} else {
				$customer_id = 'manual_' . wp_generate_password( 12, false );
				$this->db->add_subscriber( array(
					'user_id'       => $user->ID,
					'customer_id'   => $customer_id,
					'status'        => 'active',
					'provider'      => 'manual',
					'plan_interval' => $plan_interval,
					'expires_at'    => $expires_at,
				) );
			}
		} else {
			delete_user_meta( $user->ID, 'dlm_manual_override' );
			$u->remove_cap( 'read_dlm_library' );

			$sub = $this->db->get_subscription_by_user( $user->ID );
			if ( $sub ) {
				global $wpdb;
				$table = $this->db->get_table_name( 'subscriptions' );
				$wpdb->update(
					$table,
					array(
						'status'     => 'inactive',
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'user_id' => $user->ID )
				);
			}
		}

		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&success=1' ) );
		exit;
	}

	/**
	 * Approve pending manual bank transfer subscriptions
	 */
	public function handle_approve_subscription() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_approve_subscription_nonce', 'dlm_nonce' );

		$db_id = isset( $_POST['subscription_db_id'] ) ? intval( $_POST['subscription_db_id'] ) : 0;
		if ( ! $db_id ) {
			wp_die( esc_html__( 'Invalid subscription selection.', 'digital-library-membership' ) );
		}

		global $wpdb;
		$table = $this->db->get_table_name( 'subscriptions' );
		$sub = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $table, $db_id ) );

		if ( ! $sub ) {
			wp_die( esc_html__( 'Subscription record not found.', 'digital-library-membership' ) );
		}

		// Calculate expiry
		$expiry = '2099-12-31 23:59:59';
		if ( $sub->plan_interval === 'monthly' ) {
			$expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
		} elseif ( $sub->plan_interval === 'yearly' ) {
			$expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
		}

		// Update Subscription Status
		$wpdb->update(
			$table,
			array(
				'status'     => 'active',
				'expires_at' => $expiry,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $db_id )
		);

		// Record Transaction
		$pkg   = dlm_get_package( $sub->plan_interval );
		$price = ( $pkg && isset( $pkg['price'] ) ) ? floatval( $pkg['price'] ) : 0.00;

		$this->db->insert_transaction( array(
			'user_id'         => $sub->user_id,
			'subscription_id' => $sub->subscription_id,
			'transaction_id'  => 'MAN-' . $sub->subscription_id . '-' . time(),
			'provider'        => 'manual',
			'amount'          => floatval( $price ),
			'currency'        => get_option( 'dlm_currency', 'USD' ),
			'status'          => 'completed',
		) );

		// Add reading capabilities
		$user = new WP_User( $sub->user_id );
		$user->add_cap( 'read_dlm_library' );

		// Send subscription active email notice
		DLM::send_subscription_active_email( $sub->user_id, $sub->plan_interval, $expiry );

		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&success=approve_member' ) );
		exit;
	}

	/**
	 * Reject pending manual bank transfer subscriptions
	 */
	public function handle_reject_subscription() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_reject_subscription_nonce', 'dlm_nonce' );

		$db_id = isset( $_POST['subscription_db_id'] ) ? intval( $_POST['subscription_db_id'] ) : 0;
		if ( ! $db_id ) {
			wp_die( esc_html__( 'Invalid subscription selection.', 'digital-library-membership' ) );
		}

		global $wpdb;
		$table = $this->db->get_table_name( 'subscriptions' );
		$wpdb->update(
			$table,
			array(
				'status'     => 'rejected',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $db_id )
		);

		// Clear cache transients
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&success=reject_member' ) );
		exit;
	}

	/**
	 * Export Subscribers list as CSV
	 */
	public function handle_export_subscribers() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_export_subscribers_nonce', 'dlm_nonce' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="dlm-subscribers-' . time() . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'User Name', 'Email', 'Gateway', 'Interval', 'Status', 'Expiry Date' ) );

		$data = $this->db->get_analytics_summary();
		$subscribers = $data['subscribers_list'];

		foreach ( $subscribers as $sub ) {
			fputcsv( $output, array(
				$sub->display_name,
				$sub->user_email,
				$sub->provider,
				$sub->plan_interval,
				$sub->status,
				$sub->expires_at
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export Transactions log as CSV
	 */
	public function handle_export_transactions() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_export_transactions_nonce', 'dlm_nonce' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="dlm-transactions-' . time() . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Transaction ID', 'User ID', 'Subscription ID', 'Gateway', 'Amount', 'Currency', 'Status', 'Date' ) );

		$data = $this->db->get_analytics_summary();
		$txs = $data['transactions'];

		foreach ( $txs as $tx ) {
			fputcsv( $output, array(
				$tx->transaction_id,
				$tx->user_id,
				$tx->subscription_id,
				$tx->provider,
				$tx->amount,
				$tx->currency,
				$tx->status,
				$tx->created_at
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Send direct email to a library member
	 */
	public function handle_send_member_email() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_send_email_nonce', 'dlm_nonce' );

		$recipient = sanitize_email( $_POST['email_recipient'] );
		$subject   = sanitize_text_field( $_POST['email_subject'] );
		$message   = wp_kses_post( $_POST['email_message'] );

		if ( ! empty( $recipient ) && ! empty( $subject ) && ! empty( $message ) ) {
			wp_mail( $recipient, $subject, $message );
			wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&email_sent=1' ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&email_error=1' ) );
		}
		exit;
	}

	/**
	 * Delete user subscription from database
	 */
	public function handle_delete_subscription() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_delete_subscription_nonce', 'dlm_nonce' );

		$db_id = isset( $_POST['subscription_db_id'] ) ? intval( $_POST['subscription_db_id'] ) : 0;
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( $db_id ) {
			global $wpdb;
			$table = $this->db->get_table_name( 'subscriptions' );
			$wpdb->delete( $table, array( 'id' => $db_id ), array( '%d' ) );
		} elseif ( $user_id ) {
			// If deleting placeholder user, delete WP user
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
		}

		// Clear transients
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&success=delete_member' ) );
		exit;
	}

	/**
	 * Process manually creating a member
	 */
	public function handle_add_member() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_add_member_nonce', 'dlm_nonce' );

		$display_name      = sanitize_text_field( wp_unslash( $_POST['display_name'] ) );
		$user_email        = sanitize_email( wp_unslash( $_POST['user_email'] ) );
		$plan_interval     = sanitize_text_field( wp_unslash( $_POST['plan_interval'] ) );
		$user_pass         = isset( $_POST['user_pass'] ) ? wp_unslash( $_POST['user_pass'] ) : '';
		$user_pass_confirm = isset( $_POST['user_pass_confirm'] ) ? wp_unslash( $_POST['user_pass_confirm'] ) : '';
		$status            = 'active';
		$custom_expiry     = '';

		if ( empty( $user_email ) || empty( $display_name ) ) {
			wp_die( esc_html__( 'Please provide a valid name and email address.', 'digital-library-membership' ) );
		}

		// Check if user exists
		$user = get_user_by( 'email', $user_email );
		if ( ! $user ) {
			if ( empty( $user_pass ) || strlen( $user_pass ) < 6 ) {
				wp_die( esc_html__( 'Please supply a password of at least 6 characters.', 'digital-library-membership' ) );
			}
			if ( $user_pass !== $user_pass_confirm ) {
				wp_die( esc_html__( 'Passwords do not match.', 'digital-library-membership' ) );
			}

			// Generate username from email prefix
			$email_parts = explode( '@', $user_email );
			$username = sanitize_user( $email_parts[0] );

			// Ensure username is unique
			$username_orig = $username;
			$i = 1;
			while ( username_exists( $username ) ) {
				$username = $username_orig . $i;
				$i++;
			}

			// Insert WordPress user with specified password
			$user_id = wp_insert_user( array(
				'user_login'   => $username,
				'user_pass'    => $user_pass,
				'user_email'   => $user_email,
				'display_name' => $display_name,
				'role'         => 'customer'
			) );

			if ( is_wp_error( $user_id ) ) {
				wp_die( esc_html( $user_id->get_error_message() ) );
			}
		} else {
			$user_id = $user->ID;
		}

		// Calculate expiry date
		if ( $plan_interval === 'lifetime' ) {
			$expires_at = '2099-12-31 23:59:59';
		} elseif ( ! empty( $custom_expiry ) ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $custom_expiry . ' 23:59:59' ) );
		} else {
			if ( $plan_interval === 'monthly' ) {
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
			} else {
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
			}
		}

		// Save subscription to database
		$customer_id = 'manual_' . wp_generate_password( 12, false );
		$this->db->add_subscriber( array(
			'user_id'       => $user_id,
			'customer_id'   => $customer_id,
			'status'        => $status,
			'provider'      => 'manual',
			'plan_interval' => $plan_interval,
			'expires_at'    => $expires_at,
		) );

		// Manage user capability
		$wp_user = new WP_User( $user_id );
		if ( $status === 'active' ) {
			$wp_user->add_cap( 'read_dlm_library' );
			update_user_meta( $user_id, 'dlm_manual_override', 'active' );
		} else {
			delete_user_meta( $user_id, 'dlm_manual_override' );
		}

		// Send welcome email to the member
		$subject = __( 'Your Library Membership Account Created', 'digital-library-membership' );
		
		// If it's a new user, send a password reset link instead of raw password.
		if ( ! $user ) {
			$created_user = get_user_by( 'id', $user_id );
			$reset_key    = get_password_reset_key( $created_user );
			$reset_url    = is_wp_error( $reset_key ) ? wp_login_url() : add_query_arg( array( 'action' => 'rp', 'key' => $reset_key, 'login' => rawurlencode( $created_user->user_login ) ), wp_login_url() );

			$body = sprintf(
				__( "Hello %1\$s,\n\nAn administrator has created a library membership account for you.\n\nUsername/Email: %2\$s\n\nPlease set your password using the link below:\n%3\$s\n\nLogin Page: %4\$s\n\nEnjoy reading our premium digital books.\n\nBest regards,\nDigital Library Team", 'digital-library-membership' ),
				$display_name,
				$user_email,
				$reset_url,
				home_url( '/checkout/' )
			);
		} else {
			$body = sprintf(
				__( "Hello %1\$s,\n\nAn administrator has activated a manual membership subscription on your account.\n\nLogin Page: %2\$s\n\nEnjoy reading our premium digital books.\n\nBest regards,\nDigital Library Team", 'digital-library-membership' ),
				$display_name,
				home_url( '/checkout/' )
			);
		}
		
		wp_mail( $user_email, $subject, $body );

		// Clear cache transients to show the new member immediately
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members&success=add_member' ) );
		exit;
	}

	/**
	 * Save manually created transaction
	 */
	public function handle_save_transaction() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_save_transaction_nonce', 'dlm_nonce' );

		$user_id         = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$subscription_id = sanitize_text_field( $_POST['subscription_id'] );
		$transaction_id  = sanitize_text_field( $_POST['transaction_id'] );
		$provider        = sanitize_text_field( $_POST['provider'] );
		$amount          = floatval( $_POST['amount'] );
		$currency        = sanitize_text_field( $_POST['currency'] );
		$status          = sanitize_text_field( $_POST['status'] );

		if ( ! $user_id || empty( $transaction_id ) ) {
			wp_die( esc_html__( 'Please supply all required parameters.', 'digital-library-membership' ) );
		}

		$this->db->insert_transaction( array(
			'user_id'         => $user_id,
			'subscription_id' => $subscription_id,
			'transaction_id'  => $transaction_id,
			'provider'        => $provider,
			'amount'          => $amount,
			'currency'        => $currency,
			'status'          => $status,
		) );

		// Clear cache transients
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=transactions&success=tx_added' ) );
		exit;
	}

	/**
	 * Edit transaction details and manage subscription approval/refund
	 */
	public function handle_edit_transaction() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_edit_transaction_nonce', 'dlm_nonce' );

		$id     = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$status = sanitize_text_field( $_POST['status'] );

		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid transaction ID.', 'digital-library-membership' ) );
		}

		$tx = $this->db->get_transaction( $id );
		if ( ! $tx ) {
			wp_die( esc_html__( 'Transaction record not found.', 'digital-library-membership' ) );
		}

		$old_status = $tx->status;
		
		// Update transaction status
		$this->db->update_transaction( $id, array( 'status' => $status ) );

		// Manage subscription status if status transitioned
		if ( $old_status !== $status ) {
			$sub = $this->db->get_subscription_by_gateway_id( $tx->subscription_id );
			$user_id = $tx->user_id;
			$user_data = get_userdata( $user_id );
			$admin_email = get_option( 'admin_email' );

			if ( $status === 'completed' ) {
				// Approve / activate subscription
				$expiry = '2099-12-31 23:59:59';
				$interval = 'monthly';
				if ( $sub ) {
					$interval = $sub->plan_interval;
					if ( $interval === 'monthly' ) {
						$expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
					} elseif ( $interval === 'yearly' ) {
						$expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
					}
					
					$this->db->update_subscription( $tx->subscription_id, array(
						'status'     => 'active',
						'expires_at' => $expiry,
						'updated_at' => current_time( 'mysql' ),
					) );
				} else {
					// Create default manual active subscription
					$this->db->insert_subscription( array(
						'user_id'         => $user_id,
						'provider'        => $tx->provider,
						'subscription_id' => $tx->subscription_id,
						'customer_id'     => 'cust_' . $user_id,
						'status'          => 'active',
						'plan_interval'   => 'monthly',
						'expires_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) ),
					) );
				}

				// Grant library capability
				$user = new WP_User( $user_id );
				$user->add_cap( 'read_dlm_library' );

				// Send email to user
				if ( $user_data ) {
					$user_subject = __( 'Your Subscription has been Approved!', 'digital-library-membership' );
					/* translators: 1: User display name, 2: Transaction amount, 3: Currency */
					$user_body    = sprintf(
						__( "Hello %1\$s,\n\nWe are pleased to inform you that your manual payment transaction of %2\$s %3\$s has been approved.\n\nYour digital book library membership is now active!\n\nBest regards,\nDigital Library", 'digital-library-membership' ),
						$user_data->display_name,
						number_format( $tx->amount, 2 ),
						$tx->currency
					);
					wp_mail( $user_data->user_email, $user_subject, $user_body );
				}

				// Send email to admin
				$admin_subject = __( 'Order Transaction Approved', 'digital-library-membership' );
				/* translators: 1: Transaction ID, 2: User email or identifier */
				$admin_body    = sprintf(
					__( "Transaction ID: %1\$s for user: %2\$s has been approved and subscription activated successfully.", 'digital-library-membership' ),
					$tx->transaction_id,
					$user_data ? $user_data->user_email : 'User #' . $user_id
				);
				wp_mail( $admin_email, $admin_subject, $admin_body );

			} elseif ( $status === 'refunded' ) {
				// If transaction is for a book, refund and revoke access
				if ( strpos( $tx->subscription_id, 'BOOK-' ) === 0 ) {
					$this->db->refund_book_purchase( $tx->transaction_id );
				}

				// Deactivate user subscription
				if ( $sub ) {
					$this->db->update_subscription( $tx->subscription_id, array(
						'status'     => 'inactive',
						'updated_at' => current_time( 'mysql' ),
					) );
				}

				// Remove library capability
				$user = new WP_User( $user_id );
				$user->remove_cap( 'read_dlm_library' );

				// Send email to user
				if ( $user_data ) {
					$user_subject = __( 'Your Subscription / Book Access has been Refunded', 'digital-library-membership' );
					/* translators: 1: User display name, 2: Transaction amount, 3: Currency */
					$user_body    = sprintf(
						__( "Hello %1\$s,\n\nWe would like to inform you that your order transaction of %2\$s %3\$s has been marked as refunded/cancelled.\n\nYour digital access has been revoked.\n\nBest regards,\nDigital Library", 'digital-library-membership' ),
						$user_data->display_name,
						number_format( $tx->amount, 2 ),
						$tx->currency
					);
					wp_mail( $user_data->user_email, $user_subject, $user_body );
				}

				// Send email to admin
				$admin_subject = __( 'Order Transaction Refunded', 'digital-library-membership' );
				$admin_body    = sprintf(
					// translators: %s is the transaction ID
					__( "Transaction ID: %s has been marked as refunded and access suspended.", 'digital-library-membership' ),
					$tx->transaction_id
				);
				wp_mail( $admin_email, $admin_subject, $admin_body );
			}
		}

		// Clear cache transients
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=transactions&success=tx_updated' ) );
		exit;
	}

	/**
	 * Delete transaction by database ID
	 */
	public function handle_delete_transaction() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_delete_transaction_nonce', 'dlm_nonce' );

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		if ( $id ) {
			$this->db->delete_transaction( $id );
		}

		// Clear cache transients
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=transactions&success=tx_deleted' ) );
		exit;
	}

	/**
	 * Redirect notice target to dashboard members tab
	 */
	public function handle_goto_members() {
		DLM_Security::check_admin_capabilities();
		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=members' ) );
		exit;
	}

	/**
	 * Recreate missing frontend library pages
	 */
	public function handle_recreate_pages() {
		DLM_Security::check_admin_capabilities();
		check_admin_referer( 'dlm_recreate_pages_nonce' );

		DLM_Activator::create_pages();

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=settings&success=pages_recreated' ) );
		exit;
	}

	/**
	 * Process form submission to add a new subscription package
	 */
	public function handle_save_package() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_package_action_nonce', 'dlm_nonce' );

		$name        = isset( $_POST['package_name'] ) ? sanitize_text_field( wp_unslash( $_POST['package_name'] ) ) : '';
		$badge       = isset( $_POST['package_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['package_badge'] ) ) : '';
		$description = isset( $_POST['package_description'] ) ? sanitize_text_field( wp_unslash( $_POST['package_description'] ) ) : '';
		$interval    = isset( $_POST['billing_cycle'] ) ? sanitize_key( $_POST['billing_cycle'] ) : 'monthly';
		$price       = isset( $_POST['package_price'] ) ? floatval( $_POST['package_price'] ) : 0.00;
		$status      = isset( $_POST['package_status'] ) && 'inactive' === $_POST['package_status'] ? 'inactive' : 'active';
		$features_raw = isset( $_POST['package_features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['package_features'] ) ) : '';

		if ( empty( $name ) ) {
			wp_die( esc_html__( 'Please provide a valid package name.', 'digital-library-membership' ) );
		}

		if ( ! in_array( $interval, array( 'monthly', 'yearly', 'lifetime' ), true ) ) {
			$interval = 'monthly';
		}

		$features = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $features_raw ) ) ) );

		$packages = dlm_get_packages();

		// Generate unique package ID
		$base_id = sanitize_title( $name );
		if ( empty( $base_id ) ) {
			$base_id = 'pkg_' . $interval;
		}
		$package_id = $base_id;
		$suffix = 1;
		while ( isset( $packages[ $package_id ] ) ) {
			$package_id = $base_id . '_' . $suffix;
			$suffix++;
		}

		$new_package = array(
			'id'              => $package_id,
			'name'            => $name,
			'badge'           => $badge,
			'description'     => $description,
			'interval'        => $interval,
			'price'           => $price,
			'features'        => array_values( $features ),
			'status'          => $status,
			'stripe_price_id' => isset( $_POST['stripe_price_id'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_price_id'] ) ) : '',
			'paypal_plan_id'  => isset( $_POST['paypal_plan_id'] ) ? sanitize_text_field( wp_unslash( $_POST['paypal_plan_id'] ) ) : '',
			'wc_product_id'   => isset( $_POST['wc_product_id'] ) ? intval( $_POST['wc_product_id'] ) : 0,
		);

		// Automated Multi-Gateway Sync (WooCommerce, Stripe, PayPal)
		$this->auto_sync_package_gateways( $new_package );

		$packages[ $package_id ] = $new_package;
		dlm_save_packages( $packages );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=plans&success=package_saved' ) );
		exit;
	}

	/**
	 * Process form submission to edit an existing subscription package
	 */
	public function handle_edit_package() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_package_action_nonce', 'dlm_nonce' );

		$package_id = isset( $_POST['package_id'] ) ? sanitize_text_field( wp_unslash( $_POST['package_id'] ) ) : '';
		if ( empty( $package_id ) ) {
			wp_die( esc_html__( 'Invalid package ID.', 'digital-library-membership' ) );
		}

		$packages = dlm_get_packages();
		if ( ! isset( $packages[ $package_id ] ) ) {
			wp_die( esc_html__( 'Package not found.', 'digital-library-membership' ) );
		}

		$name        = isset( $_POST['package_name'] ) ? sanitize_text_field( wp_unslash( $_POST['package_name'] ) ) : $packages[ $package_id ]['name'];
		$badge       = isset( $_POST['package_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['package_badge'] ) ) : '';
		$description = isset( $_POST['package_description'] ) ? sanitize_text_field( wp_unslash( $_POST['package_description'] ) ) : '';
		$interval    = isset( $_POST['billing_cycle'] ) ? sanitize_key( $_POST['billing_cycle'] ) : $packages[ $package_id ]['interval'];
		$price       = isset( $_POST['package_price'] ) ? floatval( $_POST['package_price'] ) : $packages[ $package_id ]['price'];
		$status      = isset( $_POST['package_status'] ) && 'inactive' === $_POST['package_status'] ? 'inactive' : 'active';
		$features_raw = isset( $_POST['package_features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['package_features'] ) ) : '';

		if ( ! in_array( $interval, array( 'monthly', 'yearly', 'lifetime' ), true ) ) {
			$interval = $packages[ $package_id ]['interval'];
		}

		$features = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $features_raw ) ) ) );

		$packages[ $package_id ]['name']            = $name;
		$packages[ $package_id ]['badge']           = $badge;
		$packages[ $package_id ]['description']     = $description;
		$packages[ $package_id ]['interval']        = $interval;
		$packages[ $package_id ]['price']           = $price;
		$packages[ $package_id ]['features']        = array_values( $features );
		$packages[ $package_id ]['status']          = $status;
		$packages[ $package_id ]['stripe_price_id'] = isset( $_POST['stripe_price_id'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_price_id'] ) ) : '';
		$packages[ $package_id ]['paypal_plan_id']  = isset( $_POST['paypal_plan_id'] ) ? sanitize_text_field( wp_unslash( $_POST['paypal_plan_id'] ) ) : '';
		$packages[ $package_id ]['wc_product_id']   = isset( $_POST['wc_product_id'] ) ? intval( $_POST['wc_product_id'] ) : 0;

		// Automated Multi-Gateway Sync (WooCommerce, Stripe, PayPal)
		$this->auto_sync_package_gateways( $packages[ $package_id ] );

		dlm_save_packages( $packages );

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=plans&success=package_updated' ) );
		exit;
	}

	/**
	 * Automatically sync and create gateway objects (Stripe Price, PayPal Plan, WooCommerce Virtual Product)
	 *
	 * @param array $package Package reference array.
	 */
	public function auto_sync_package_gateways( &$package ) {
		// 1. Auto-create WooCommerce virtual product if WooCommerce is active and not already linked
		if ( class_exists( 'WooCommerce' ) && empty( $package['wc_product_id'] ) ) {
			$wc_mgr = new DLM_WooCommerce( $this->db, $this->checkout );
			$package['wc_product_id'] = $wc_mgr->get_or_create_subscription_wc_product( $package['id'] );
		}

		// 2. Auto-create Stripe Product and Price if Stripe secret key is configured and stripe_price_id is empty
		if ( empty( $package['stripe_price_id'] ) ) {
			$secret_key = get_option( 'dlm_stripe_secret_key' );
			if ( ! empty( $secret_key ) && class_exists( '\Stripe\Stripe' ) ) {
				try {
					\Stripe\Stripe::setApiKey( $secret_key );

					// Create Stripe Product
					$product = \Stripe\Product::create( array(
						'name'        => 'Digital Library - ' . $package['name'],
						'description' => ! empty( $package['description'] ) ? $package['description'] : ( 'Subscription package: ' . $package['name'] ),
						'metadata'    => array(
							'dlm_package_id' => $package['id'],
							'dlm_interval'   => $package['interval'],
						),
					) );

					if ( ! empty( $product->id ) ) {
						$price_args = array(
							'product'     => $product->id,
							'unit_amount' => intval( round( floatval( $package['price'] ) * 100 ) ),
							'currency'    => strtolower( get_option( 'dlm_currency', 'USD' ) ),
						);

						if ( $package['interval'] !== 'lifetime' ) {
							$price_args['recurring'] = array(
								'interval' => ( $package['interval'] === 'yearly' ) ? 'year' : 'month',
							);
						}

						$price = \Stripe\Price::create( $price_args );
						if ( ! empty( $price->id ) ) {
							$package['stripe_price_id'] = $price->id;
						}
					}
				} catch ( Exception $e ) {
					// Gracefully capture Stripe error without blocking package persistence
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( 'DLM Stripe auto-sync error: ' . $e->getMessage() );
					}
				}
			}
		}

		// 3. Auto-create PayPal Catalog Product and Billing Plan if PayPal credentials configured and plan_id is empty
		if ( empty( $package['paypal_plan_id'] ) && $package['interval'] !== 'lifetime' ) {
			$client_id = get_option( 'dlm_paypal_client_id' );
			$secret    = get_option( 'dlm_paypal_secret_key' );

			if ( ! empty( $client_id ) && ! empty( $secret ) ) {
				$mode     = get_option( 'dlm_paypal_mode', 'live' );
				$base_url = ( $mode === 'sandbox' ) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

				// 3a. Get OAuth2 Token
				$auth_response = wp_remote_post( $base_url . '/v1/oauth2/token', array(
					'headers' => array(
						'Accept'        => 'application/json',
						'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
					),
					'body'    => array(
						'grant_type' => 'client_credentials',
					),
				) );

				if ( ! is_wp_error( $auth_response ) ) {
					$auth_body = json_decode( wp_remote_retrieve_body( $auth_response ) );
					$token     = ! empty( $auth_body->access_token ) ? $auth_body->access_token : '';

					if ( ! empty( $token ) ) {
						// 3b. Create Catalog Product
						$prod_response = wp_remote_post( $base_url . '/v1/catalogs/products', array(
							'headers' => array(
								'Content-Type'  => 'application/json',
								'Authorization' => 'Bearer ' . $token,
							),
							'body'    => wp_json_encode( array(
								'name'        => 'Digital Library - ' . $package['name'],
								'description' => ! empty( $package['description'] ) ? $package['description'] : ( 'Membership plan: ' . $package['name'] ),
								'type'        => 'DIGITAL',
								'category'    => 'DIGITAL_MEDIA_BOOKS_MOVIES_MUSIC',
							) ),
						) );

						$prod_id = '';
						if ( ! is_wp_error( $prod_response ) ) {
							$prod_body = json_decode( wp_remote_retrieve_body( $prod_response ) );
							$prod_id   = ! empty( $prod_body->id ) ? $prod_body->id : '';
						}

						// 3c. Create Billing Plan
						if ( ! empty( $prod_id ) ) {
							$interval_unit = ( $package['interval'] === 'yearly' ) ? 'YEAR' : 'MONTH';
							$currency      = strtoupper( get_option( 'dlm_currency', 'USD' ) );
							$price_str     = number_format( floatval( $package['price'] ), 2, '.', '' );

							$plan_response = wp_remote_post( $base_url . '/v1/billing/plans', array(
								'headers' => array(
									'Content-Type'      => 'application/json',
									'Authorization'     => 'Bearer ' . $token,
									'PayPal-Request-Id' => 'DLM-PLAN-' . $package['id'] . '-' . time(),
								),
								'body'    => wp_json_encode( array(
									'product_id'          => $prod_id,
									'name'                => $package['name'] . ' Subscription',
									'description'         => 'Digital library recurring subscription for ' . $package['name'],
									'status'              => 'ACTIVE',
									'billing_cycles'      => array(
										array(
											'frequency'      => array(
												'interval_unit'  => $interval_unit,
												'interval_count' => 1,
											),
											'tenure_type'    => 'REGULAR',
											'sequence'       => 1,
											'total_cycles'   => 0,
											'pricing_scheme' => array(
												'fixed_price' => array(
													'value'         => $price_str,
													'currency_code' => $currency,
												),
											),
										),
									),
									'payment_preferences' => array(
										'auto_bill_outstanding'     => true,
										'setup_fee'                 => array(
											'value'         => '0',
											'currency_code' => $currency,
										),
										'setup_fee_failure_action'  => 'CONTINUE',
										'payment_failure_threshold' => 3,
									),
								) ),
							) );

							if ( ! is_wp_error( $plan_response ) ) {
								$plan_body = json_decode( wp_remote_retrieve_body( $plan_response ) );
								if ( ! empty( $plan_body->id ) ) {
									$package['paypal_plan_id'] = $plan_body->id;
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Process form submission to delete a subscription package
	 * (Deletes immediately without blocking, showing warning in UI only)
	 */
	public function handle_delete_package() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_package_action_nonce', 'dlm_nonce' );

		$package_id = isset( $_POST['package_id'] ) ? sanitize_text_field( wp_unslash( $_POST['package_id'] ) ) : '';
		if ( empty( $package_id ) ) {
			wp_die( esc_html__( 'Invalid package ID.', 'digital-library-membership' ) );
		}

		$packages = dlm_get_packages();
		if ( isset( $packages[ $package_id ] ) ) {
			unset( $packages[ $package_id ] );
			dlm_save_packages( $packages );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=plans&success=package_deleted' ) );
		exit;
	}

	/**
	 * Process request to toggle active/inactive status of a subscription package
	 */
	public function handle_toggle_package_status() {
		DLM_Security::check_admin_capabilities();
		DLM_Security::verify_nonce( 'dlm_package_action_nonce', 'dlm_nonce' );

		$package_id = isset( $_POST['package_id'] ) ? sanitize_text_field( wp_unslash( $_POST['package_id'] ) ) : '';
		if ( empty( $package_id ) ) {
			wp_die( esc_html__( 'Invalid package ID.', 'digital-library-membership' ) );
		}

		$packages = dlm_get_packages();
		if ( isset( $packages[ $package_id ] ) ) {
			$current_status = isset( $packages[ $package_id ]['status'] ) ? $packages[ $package_id ]['status'] : 'active';
			$packages[ $package_id ]['status'] = ( 'active' === $current_status ) ? 'inactive' : 'active';
			dlm_save_packages( $packages );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=dlm-library&tab=plans&success=package_status_toggled' ) );
		exit;
	}

	/**
	 * Hide unused WooCommerce admin menus for headless payment setup
	 * Hides Products, Analytics, and Marketing sidebar menus for all admins
	 * while keeping Orders and WooCommerce > Settings > Payments fully visible.
	 */
	public function hide_headless_wc_admin_menus() {
		// Hide Products and all product submenus (Add New, Categories, Tags, Attributes)
		remove_menu_page( 'edit.php?post_type=product' );

		// Hide Marketing top-level and submenu
		remove_menu_page( 'woocommerce-marketing' );
		remove_submenu_page( 'woocommerce', 'woocommerce-marketing' );

		// Hide Analytics top-level and submenu
		remove_menu_page( 'wc-admin&path=/analytics/overview' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/overview' );
	}
}

