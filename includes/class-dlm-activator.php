<?php
/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Activator {

	/**
	 * Run DB creations and directories creation on activation.
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table 1: Books Metadata
		$table_books = $wpdb->prefix . 'dlm_books';
		$sql_books = "CREATE TABLE $table_books (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			author varchar(255) DEFAULT '',
			description text DEFAULT NULL,
			cover_image_url varchar(255) DEFAULT '',
			file_path varchar(255) NOT NULL,
			file_type varchar(50) NOT NULL,
			status varchar(20) DEFAULT 'publish',
			access_type varchar(30) DEFAULT 'subscription_only',
			price decimal(10,2) DEFAULT '0.00',
			publish_date datetime DEFAULT NULL,
			is_featured tinyint(1) DEFAULT 0,
			featured_title varchar(255) DEFAULT '',
			featured_description text DEFAULT NULL,
			featured_banner_id bigint(20) DEFAULT 0,
			featured_banner_url varchar(255) DEFAULT '',
			featured_button_1_label varchar(100) DEFAULT '',
			featured_button_2_label varchar(100) DEFAULT '',
			featured_order int(11) DEFAULT 0,
			wc_product_id bigint(20) DEFAULT 0,
			is_demo tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Table 2: Subscriptions
		$table_subscriptions = $wpdb->prefix . 'dlm_subscriptions';
		$sql_subscriptions = "CREATE TABLE $table_subscriptions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			provider varchar(50) NOT NULL,
			subscription_id varchar(255) NOT NULL,
			customer_id varchar(255) NOT NULL,
			status varchar(50) NOT NULL,
			plan_interval varchar(20) NOT NULL,
			expires_at datetime NOT NULL,
			is_demo tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY subscription_id (subscription_id)
		) $charset_collate;";

		// Table 3: Transactions Log
		$table_transactions = $wpdb->prefix . 'dlm_transactions';
		$sql_transactions = "CREATE TABLE $table_transactions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			subscription_id varchar(255) NOT NULL,
			transaction_id varchar(255) NOT NULL,
			provider varchar(50) NOT NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(10) NOT NULL,
			status varchar(50) NOT NULL,
			is_demo tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) $charset_collate;";

		// Table 4: Reading Progress (Bookmarking)
		$table_progress = $wpdb->prefix . 'dlm_progress';
		$sql_progress = "CREATE TABLE $table_progress (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			book_id bigint(20) NOT NULL,
			last_page int(11) DEFAULT 1,
			progress_percent int(11) DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_book (user_id, book_id)
		) $charset_collate;";

		// Table 5: Analytics Events
		$table_analytics = $wpdb->prefix . 'dlm_analytics';
		$sql_analytics = "CREATE TABLE $table_analytics (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT NULL,
			book_id bigint(20) NOT NULL,
			event_type varchar(50) NOT NULL,
			page_number int(11) DEFAULT NULL,
			time_spent int(11) DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY book_id (book_id),
			KEY user_id (user_id)
		) $charset_collate;";

		// Table 6: Book Purchases Log
		$table_purchases = $wpdb->prefix . 'dlm_book_purchases';
		$sql_purchases = "CREATE TABLE $table_purchases (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			book_id bigint(20) NOT NULL,
			order_id varchar(255) NOT NULL,
			amount decimal(10,2) NOT NULL DEFAULT '0.00',
			currency varchar(10) NOT NULL DEFAULT 'USD',
			payment_engine varchar(50) NOT NULL DEFAULT 'default',
			status varchar(50) NOT NULL DEFAULT 'completed',
			is_demo tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY book_id (book_id),
			KEY order_id (order_id)
		) $charset_collate;";

		// Table 7: User Notifications Log
		$table_notifications = $wpdb->prefix . 'dlm_notifications';
		$sql_notifications = "CREATE TABLE $table_notifications (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			message text NOT NULL,
			link_url varchar(255) DEFAULT '',
			is_read tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY is_read (is_read)
		) $charset_collate;";

		if ( ! function_exists( 'dbDelta' ) && file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql_books );
			dbDelta( $sql_subscriptions );
			dbDelta( $sql_transactions );
			dbDelta( $sql_progress );
			dbDelta( $sql_analytics );
			dbDelta( $sql_purchases );
			dbDelta( $sql_notifications );
		}

		// Update DB version tracking
		update_option( 'dlm_db_version', '2.6.0' );

		// Setup secure storage directory
		self::setup_secure_directory();

		// Add custom capability to administrator and editor
		self::setup_roles_and_capabilities();

		// Auto-create required frontend pages
		self::create_pages();

		// Set flag for setup wizard redirection if not already completed
		if ( 'yes' !== get_option( 'dlm_setup_completed' ) ) {
			update_option( 'dlm_activation_redirect', 'yes' );
		}
	}

	/**
	 * Auto-create required frontend pages if they don't already exist.
	 */
	public static function create_pages() {
		if ( ! function_exists( 'wp_insert_post' ) ) {
			return;
		}

		$pages = array(
			'library'  => array(
				'title'     => __( 'Library', 'digital-library-membership' ),
				'slug'      => 'library',
				'shortcode' => '[dlm_library]',
				'option'    => 'dlm_library_page_id',
			),
			'account'  => array(
				'title'     => __( 'Library Account', 'digital-library-membership' ),
				'slug'      => 'library-account',
				'shortcode' => '[dlm_account]',
				'option'    => 'dlm_account_page_id',
			),
			'pricing'  => array(
				'title'     => __( 'Plan', 'digital-library-membership' ),
				'slug'      => 'plan',
				'shortcode' => '[dlm_pricing]',
				'option'    => 'dlm_pricing_page_id',
			),
			'checkout' => array(
				'title'     => __( 'Library Checkout', 'digital-library-membership' ),
				'slug'      => 'library-checkout',
				'shortcode' => '[dlm_checkout]',
				'option'    => 'dlm_checkout_page_id',
			),
		);

		foreach ( $pages as $page_key => $page_info ) {
			$page_id  = get_option( $page_info['option'] );
			$page_obj = $page_id ? get_post( $page_id ) : null;

			// Check if page exists by slug if option is missing or invalid
			if ( ! $page_obj || 'trash' === $page_obj->post_status ) {
				$existing_by_slug = get_page_by_path( $page_info['slug'] );
				if ( $existing_by_slug && 'trash' !== $existing_by_slug->post_status ) {
					$page_obj = $existing_by_slug;
					update_option( $page_info['option'], (int) $existing_by_slug->ID );
				}
			}

			if ( ! $page_obj || 'trash' === $page_obj->post_status ) {
				$new_page_id = wp_insert_post( array(
					'post_title'     => $page_info['title'],
					'post_name'      => $page_info['slug'],
					'post_content'   => $page_info['shortcode'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
				) );

				if ( $new_page_id && ! is_wp_error( $new_page_id ) ) {
					update_option( $page_info['option'], $new_page_id );
				}
			} else {
				// If existing checkout page still has conflicting slug 'checkout' or title 'Checkout', migrate it to 'library-checkout'
				if ( 'checkout' === $page_key ) {
					$needs_update = false;
					$update_args  = array( 'ID' => $page_obj->ID );

					if ( 'checkout' === $page_obj->post_name ) {
						$update_args['post_name'] = 'library-checkout';
						$needs_update             = true;
					}
					if ( 'Checkout' === $page_obj->post_title ) {
						$update_args['post_title'] = __( 'Library Checkout', 'digital-library-membership' );
						$needs_update              = true;
					}
					if ( empty( $page_obj->post_content ) || false === strpos( $page_obj->post_content, '[dlm_checkout]' ) ) {
						$update_args['post_content'] = '[dlm_checkout]';
						$needs_update                = true;
					}

					if ( $needs_update ) {
						wp_update_post( $update_args );
					}
				}
			}
		}
	}

	/**
	 * Create secure uploads folder and write htaccess
	 */
	private static function setup_secure_directory() {
		if ( defined( 'DLM_PROTECTED_DIR' ) && ! file_exists( DLM_PROTECTED_DIR ) ) {
			wp_mkdir_p( DLM_PROTECTED_DIR );
		}

		if ( defined( 'DLM_PROTECTED_DIR' ) && is_dir( DLM_PROTECTED_DIR ) ) {
			// Deny direct file access via htaccess
			$htaccess_file = DLM_PROTECTED_DIR . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				$rules  = "# Apache 2.4+\n";
				$rules .= "<IfModule mod_authz_core.c>\n";
				$rules .= "    Require all denied\n";
				$rules .= "</IfModule>\n";
				$rules .= "# Apache 2.2\n";
				$rules .= "<IfModule !mod_authz_core.c>\n";
				$rules .= "    Order Deny,Allow\n";
				$rules .= "    Deny from all\n";
				$rules .= "</IfModule>\n";
				@file_put_contents( $htaccess_file, $rules );
			}

			// Prevent folder listings index file
			$index_file = DLM_PROTECTED_DIR . '/index.php';
			if ( ! file_exists( $index_file ) ) {
				@file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
			}
		}
	}

	/**
	 * Register roles and add capabilities
	 */
	private static function setup_roles_and_capabilities() {
		if ( ! function_exists( 'get_role' ) ) {
			return;
		}

		// Admin
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'manage_dlm_library' );
		}

		// Editor
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'manage_dlm_library' );
		}

		// Note: read_dlm_library capability is granted dynamically per-user upon valid active subscription.
	}

	/**
	 * Check and execute DB schema migration if version mismatch
	 */
	public static function check_and_upgrade_db() {
		global $wpdb;
		$installed_ver = get_option( 'dlm_db_version', '1.0.0' );

		// 1. Ensure updated_at column exists in dlm_transactions table on existing sites
		$table_tx = $wpdb->prefix . 'dlm_transactions';
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_tx ) ) === $table_tx ) {
			$col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM %i LIKE %s", $table_tx, 'updated_at' ) );
			if ( empty( $col ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( "ALTER TABLE `{$table_tx}` ADD `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`" );
			}
		}

		// 2. Full DB upgrade if version is older
		if ( version_compare( $installed_ver, '3.3.0', '<' ) ) {
			self::activate();
			update_option( 'dlm_db_version', '3.3.0' );
		}
	}
}
