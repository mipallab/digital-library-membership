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
			created_at datetime NOT NULL,
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_books );
		dbDelta( $sql_subscriptions );
		dbDelta( $sql_transactions );
		dbDelta( $sql_progress );
		dbDelta( $sql_analytics );

		// Setup secure storage directory
		self::setup_secure_directory();

		// Add custom capability to administrator and editor
		self::setup_roles_and_capabilities();

		// Auto-create required frontend pages
		self::create_pages();
	}

	/**
	 * Auto-create required frontend pages if they don't already exist.
	 */
	public static function create_pages() {
		$pages = array(
			'library'  => array(
				'title'     => __( 'Library', 'digital-library-membership' ),
				'shortcode' => '[dlm_library]',
				'option'    => 'dlm_library_page_id',
			),
			'account'  => array(
				'title'     => __( 'Library Account', 'digital-library-membership' ),
				'shortcode' => '[dlm_account]',
				'option'    => 'dlm_account_page_id',
			),
			'pricing'  => array(
				'title'     => __( 'Plan', 'digital-library-membership' ),
				'shortcode' => '[dlm_pricing]',
				'option'    => 'dlm_pricing_page_id',
			),
			'checkout' => array(
				'title'     => __( 'Checkout', 'digital-library-membership' ),
				'shortcode' => '[dlm_checkout]',
				'option'    => 'dlm_checkout_page_id',
			),
		);

		foreach ( $pages as $page_info ) {
			$page_id  = get_option( $page_info['option'] );
			$page_obj = $page_id ? get_post( $page_id ) : null;

			if ( ! $page_obj || 'trash' === $page_obj->post_status ) {
				$new_page_id = wp_insert_post( array(
					'post_title'     => $page_info['title'],
					'post_content'   => $page_info['shortcode'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
				) );

				if ( $new_page_id && ! is_wp_error( $new_page_id ) ) {
					update_option( $page_info['option'], $new_page_id );
				}
			}
		}
	}

	/**
	 * Create secure uploads folder and write htaccess
	 */
	private static function setup_secure_directory() {
		if ( ! file_exists( DLM_PROTECTED_DIR ) ) {
			wp_mkdir_p( DLM_PROTECTED_DIR );
		}

		// Deny direct file access via htaccess
		$htaccess_file = DLM_PROTECTED_DIR . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$rules = "Order Deny,Allow\nDeny from all\n";
			file_put_contents( $htaccess_file, $rules );
		}

		// Prevent folder listings index file
		$index_file = DLM_PROTECTED_DIR . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Register roles and add capabilities
	 */
	private static function setup_roles_and_capabilities() {
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

		// Add subscriber & customer capability to check library access
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'read_dlm_library' );
		}
		$customer = get_role( 'customer' );
		if ( $customer ) {
			$customer->add_cap( 'read_dlm_library' );
		}
	}
}
