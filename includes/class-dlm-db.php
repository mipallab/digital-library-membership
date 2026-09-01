<?php
/**
 * Database operations manager
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
class DLM_DB {

	/**
	 * Get table names
	 */
	public function get_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . 'dlm_' . $table;
	}

	/**
	 * Get single book
	 */
	public function get_book( $id ) {
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $table, $id ) );
	}

	/**
	 * Get list of books
	 */
	public function get_books( $status = 'publish', $include_future = false ) {
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		if ( $status === 'all' ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY created_at DESC", $table ) );
		}
		
		if ( $status === 'publish' && ! $include_future ) {
			$now = current_time( 'mysql' );
			// Self-healing runtime transition: flip any overdue future books to publish instantly
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = 'publish' WHERE status = 'future' AND publish_date IS NOT NULL AND publish_date != '' AND publish_date <= %s",
					$table,
					$now
				)
			);

			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE status = %s AND (publish_date IS NULL OR publish_date = '' OR publish_date <= %s) ORDER BY created_at DESC",
					$table,
					'publish',
					$now
				)
			);
		}

		if ( $status === 'publish' && $include_future ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE status IN ('publish', 'future') ORDER BY created_at DESC",
					$table
				)
			);
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC", $table, $status ) );
	}

	/**
	 * Automatically ensure featured book columns exist in wp_dlm_books
	 */
	public function check_featured_books_schema() {
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		
		if ( get_transient( 'dlm_featured_schema_checked' ) ) {
			return;
		}

		$columns = $wpdb->get_col( $wpdb->prepare( "DESCRIBE %i", $table ) );
		if ( empty( $columns ) ) {
			return;
		}

		if ( ! in_array( 'is_featured', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN is_featured tinyint(1) DEFAULT 0", $table ) );
		}
		if ( ! in_array( 'featured_title', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_title varchar(255) DEFAULT ''", $table ) );
		}
		if ( ! in_array( 'featured_description', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_description text DEFAULT NULL", $table ) );
		}
		if ( ! in_array( 'featured_banner_id', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_banner_id bigint(20) DEFAULT 0", $table ) );
		}
		if ( ! in_array( 'featured_banner_url', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_banner_url varchar(255) DEFAULT ''", $table ) );
		}
		if ( ! in_array( 'featured_button_1_label', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_button_1_label varchar(100) DEFAULT ''", $table ) );
		}
		if ( ! in_array( 'featured_button_2_label', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_button_2_label varchar(100) DEFAULT ''", $table ) );
		}
		if ( ! in_array( 'featured_order', $columns, true ) ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN featured_order int(11) DEFAULT 0", $table ) );
		}

		set_transient( 'dlm_featured_schema_checked', 1, DAY_IN_SECONDS );
	}

	/**
	 * Get featured books for hero slider and widgets
	 *
	 * @param int $limit Max number of featured books to return.
	 * @return array
	 */
	public function get_featured_books( $limit = 10 ) {
		$this->check_featured_books_schema();
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		$limit = max( 1, intval( $limit ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE is_featured = 1 AND status IN ('publish', 'future') ORDER BY featured_order ASC, created_at DESC LIMIT %d",
				$table,
				$limit
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Insert/Create book
	 */
	public function insert_book( $data ) {
		$this->check_featured_books_schema();
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		
		$publish_date = null;
		if ( ! empty( $data['publish_date'] ) ) {
			$publish_date = date( 'Y-m-d H:i:s', strtotime( str_replace( 'T', ' ', $data['publish_date'] ) ) );
		}

		$insert_data = array(
			'title'                   => sanitize_text_field( $data['title'] ),
			'author'                  => sanitize_text_field( $data['author'] ),
			'description'             => wp_kses_post( $data['description'] ),
			'cover_image_url'         => esc_url_raw( $data['cover_image_url'] ),
			'file_path'               => sanitize_text_field( $data['file_path'] ),
			'file_type'               => sanitize_text_field( $data['file_type'] ),
			'status'                  => sanitize_text_field( $data['status'] ),
			'access_type'             => isset( $data['access_type'] ) ? sanitize_text_field( $data['access_type'] ) : 'subscription_only',
			'price'                   => isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00,
			'publish_date'            => $publish_date,
			'is_featured'             => ! empty( $data['is_featured'] ) ? 1 : 0,
			'featured_title'          => isset( $data['featured_title'] ) ? sanitize_text_field( $data['featured_title'] ) : '',
			'featured_description'    => isset( $data['featured_description'] ) ? wp_kses_post( $data['featured_description'] ) : null,
			'featured_banner_id'      => isset( $data['featured_banner_id'] ) ? intval( $data['featured_banner_id'] ) : 0,
			'featured_banner_url'     => isset( $data['featured_banner_url'] ) ? esc_url_raw( $data['featured_banner_url'] ) : '',
			'featured_button_1_label' => isset( $data['featured_button_1_label'] ) ? sanitize_text_field( $data['featured_button_1_label'] ) : '',
			'featured_button_2_label' => isset( $data['featured_button_2_label'] ) ? sanitize_text_field( $data['featured_button_2_label'] ) : '',
			'featured_order'          => isset( $data['featured_order'] ) ? intval( $data['featured_order'] ) : 0,
			'wc_product_id'           => isset( $data['wc_product_id'] ) ? intval( $data['wc_product_id'] ) : 0,
			'created_at'              => current_time( 'mysql' ),
		);

		$wpdb->insert( $table, $insert_data );
		return $wpdb->insert_id;
	}

	/**
	 * Delete book and its physical file
	 */
	public function delete_book( $id ) {
		global $wpdb;
		$book = $this->get_book( $id );
		if ( ! $book ) {
			return false;
		}

		// Delete physical file
		if ( file_exists( $book->file_path ) ) {
			wp_delete_file( $book->file_path );
		}

		$table = $this->get_table_name( 'books' );
		return $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Get subscription details for user (prioritizes active/approved subscription records)
	 */
	public function get_subscription_by_user( $user_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'subscriptions' );

		// Prioritize active, approved, or completed subscriptions
		$active_sub = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE user_id = %d AND status IN ('active', 'approved', 'completed') ORDER BY id DESC LIMIT 1", $table, $user_id ) );
		if ( $active_sub ) {
			return $active_sub;
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE user_id = %d ORDER BY id DESC LIMIT 1", $table, $user_id ) );
	}

	/**
	 * Check if user has active membership
	 */
	public function has_active_membership( $user_id ) {
		if ( ! $user_id ) {
			return false;
		}

		// Admin override capability
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Check WP User Meta for manual overrides first
		$manual_override = get_user_meta( $user_id, 'dlm_manual_override', true );
		if ( 'active' === $manual_override ) {
			return true;
		}

		$meta_status = get_user_meta( $user_id, 'dlm_subscription_status', true );
		if ( 'active' === $meta_status ) {
			return true;
		}

		$sub = $this->get_subscription_by_user( $user_id );
		if ( ! $sub ) {
			return false;
		}

		// If status is active, approved, or completed
		$status = strtolower( trim( $sub->status ) );
		if ( in_array( $status, array( 'active', 'approved', 'completed' ), true ) ) {
			// Lifetime interval never expires
			if ( 'lifetime' === strtolower( trim( $sub->plan_interval ) ) ) {
				return true;
			}

			// Empty or zero-date expiry is considered non-expiring active
			if ( empty( $sub->expires_at ) || '0000-00-00 00:00:00' === $sub->expires_at || '0000-00-00' === $sub->expires_at ) {
				return true;
			}

			$exp_timestamp = strtotime( $sub->expires_at );
			if ( false === $exp_timestamp ) {
				return false; // Fail closed — treat unparsable expiry as expired
			}

			return $exp_timestamp > time();
		}

		return false;
	}

	/**
	 * Insert/Create subscription
	 */
	public function insert_subscription( $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'subscriptions' );
		$wpdb->insert(
			$table,
			array(
				'user_id'         => intval( $data['user_id'] ),
				'provider'        => sanitize_text_field( $data['provider'] ),
				'subscription_id' => sanitize_text_field( $data['subscription_id'] ),
				'customer_id'     => sanitize_text_field( $data['customer_id'] ),
				'status'          => sanitize_text_field( $data['status'] ),
				'plan_interval'   => sanitize_text_field( $data['plan_interval'] ),
				'expires_at'      => $data['expires_at'],
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			)
		);
		return $wpdb->insert_id;
	}

	/**
	 * Update existing subscription
	 */
	public function update_subscription( $subscription_id, $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'subscriptions' );
		$data['updated_at'] = current_time( 'mysql' );
		return $wpdb->update(
			$table,
			$data,
			array( 'subscription_id' => $subscription_id )
		);
	}

	/**
	 * Get subscription by Gateway Subscription ID
	 */
	public function get_subscription_by_gateway_id( $subscription_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'subscriptions' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE subscription_id = %s", $table, $subscription_id ) );
	}

	/**
	 * Log financial transaction
	 */
	public function insert_transaction( $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'transactions' );
		$wpdb->insert(
			$table,
			array(
				'user_id'         => intval( $data['user_id'] ),
				'subscription_id' => sanitize_text_field( $data['subscription_id'] ),
				'transaction_id'  => sanitize_text_field( $data['transaction_id'] ),
				'provider'        => sanitize_text_field( $data['provider'] ),
				'amount'          => floatval( $data['amount'] ),
				'currency'        => sanitize_text_field( $data['currency'] ),
				'status'          => sanitize_text_field( $data['status'] ),
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			)
		);
		return $wpdb->insert_id;
	}

	/**
	 * Get reading progress for user & book
	 */
	public function get_reading_progress( $user_id, $book_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'progress' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE user_id = %d AND book_id = %d", $table, $user_id, $book_id ) );
	}

	/**
	 * Update bookmark page and completion progress
	 */
	public function save_reading_progress( $user_id, $book_id, $page, $percent ) {
		global $wpdb;
		$table = $this->get_table_name( 'progress' );
		$now = current_time( 'mysql' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i (user_id, book_id, last_page, progress_percent, updated_at) 
				VALUES (%d, %d, %d, %d, %s)
				ON DUPLICATE KEY UPDATE 
				last_page = %d, progress_percent = %d, updated_at = %s",
				$table,
				$user_id, $book_id, $page, $percent, $now,
				$page, $percent, $now
			)
		);
	}

	/**
	 * Log reader engagement events
	 */
	public function log_analytics_event( $user_id, $book_id, $event_type, $page_number = null, $time_spent = 0 ) {
		global $wpdb;
		$table = $this->get_table_name( 'analytics' );
		$wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id ? intval( $user_id ) : null,
				'book_id'     => intval( $book_id ),
				'event_type'  => sanitize_text_field( $event_type ),
				'page_number' => $page_number ? intval( $page_number ) : null,
				'time_spent'  => intval( $time_spent ),
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Query Analytics Reports for Dashboard
	 */
	public function get_analytics_summary() {
		global $wpdb;
		$t_subs = $this->get_table_name( 'subscriptions' );
		$t_tx   = $this->get_table_name( 'transactions' );
		$t_an   = $this->get_table_name( 'analytics' );
		$t_bks  = $this->get_table_name( 'books' );

		// Total active subscribers
		$active_subscribers = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM %i WHERE status = %s AND expires_at > %s",
			$t_subs, 'active', current_time( 'mysql' )
		) );

		// Total subscribers in system
		$total_subscribers = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM %i", $t_subs ) );

		// MRR (Monthly Recurring Revenue estimated from payments last 30 days)
		$mrr = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(amount) FROM %i WHERE status = %s AND created_at >= DATE_SUB(%s, INTERVAL 30 DAY)",
			$t_tx, 'completed', current_time( 'mysql' )
		) );

		// Total sales (all time)
		$total_sales = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(amount) FROM %i WHERE status = %s",
			$t_tx, 'completed'
		) );

		// Transactions history log (Last 7 days, ordered newest first)
		$seven_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$transactions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i WHERE created_at >= %s ORDER BY created_at DESC",
			$t_tx, $seven_days_ago
		) );

		// Popular books by opens
		$popular_books = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.title, COUNT(a.id) as opens 
			FROM %i a
			JOIN %i b ON a.book_id = b.id
			WHERE a.event_type = %s
			GROUP BY a.book_id
			ORDER BY opens DESC
			LIMIT 10",
			$t_an, $t_bks, 'open'
		) );

		// Drop-off/Engagement statistics per page
		$engagement = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.title, a.page_number, COUNT(a.id) as read_count
			FROM %i a
			JOIN %i b ON a.book_id = b.id
			WHERE a.event_type = %s
			GROUP BY a.book_id, a.page_number
			ORDER BY b.title, a.page_number",
			$t_an, $t_bks, 'page_view'
		) );

		// Active subscribers list
		$subs_in_db = $wpdb->get_results( 
			$wpdb->prepare(
				"SELECT s.*, u.user_email, u.display_name 
				FROM %i s
				JOIN %i u ON s.user_id = u.ID
				ORDER BY s.updated_at DESC",
				$t_subs, $wpdb->users
			)
		);

		// Index by user ID
		$subs_by_user = array();
		foreach ( $subs_in_db as $s ) {
			$subs_by_user[ intval( $s->user_id ) ] = $s;
		}

		// Fetch all WordPress users with role 'subscriber', 'customer', or default role
		$default_role = get_option( 'default_role', 'subscriber' );
		$roles = array_unique( array( 'subscriber', 'customer', $default_role ) );
		$wp_subscribers = get_users( array( 'role__in' => $roles ) );
		$subscribers_list = array();

		// Add all 'subscriber' role users
		foreach ( $wp_subscribers as $u ) {
			$user_id = intval( $u->ID );
			if ( isset( $subs_by_user[ $user_id ] ) ) {
				$subscribers_list[] = $subs_by_user[ $user_id ];
			} else {
				// Placeholder non-active member
				$subscribers_list[] = (object) array(
					'id'              => 0,
					'user_id'         => $user_id,
					'subscription_id' => 'NONE-' . $user_id,
					'customer_id'     => '',
					'status'          => 'inactive',
					'provider'        => 'none',
					'plan_interval'   => 'none',
					'expires_at'      => '0000-00-00 00:00:00',
					'created_at'      => $u->user_registered,
					'updated_at'      => $u->user_registered,
					'user_email'      => $u->user_email,
					'display_name'    => $u->display_name ?: $u->user_login,
				);
			}
		}

		// Also add other users in database with subscriptions that don't have subscriber role (e.g. admins)
		foreach ( $subs_in_db as $s ) {
			$user_id = intval( $s->user_id );
			$found = false;
			foreach ( $subscribers_list as $sl ) {
				if ( intval( $sl->user_id ) === $user_id ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$subscribers_list[] = $s;
			}
		}

		// Query all completed/approved transactions for historical analytics charts
		$completed_transactions = $wpdb->get_results( $wpdb->prepare(
			"SELECT amount, created_at FROM %i WHERE status = %s ORDER BY created_at ASC",
			$t_tx, 'completed'
		) );

		return array(
			'active_subscribers'     => intval( $active_subscribers ),
			'total_subscribers'      => intval( $total_subscribers ),
			'mrr'                    => floatval( $mrr ),
			'total_sales'            => floatval( $total_sales ),
			'transactions'           => $transactions,
			'completed_transactions' => $completed_transactions,
			'popular_books'          => $popular_books,
			'engagement'             => $engagement,
			'subscribers_list'       => $subscribers_list,
		);
	}

	/**
	 * Update existing book metadata
	 */
	public function update_book( $id, $data ) {
		$this->check_featured_books_schema();
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		
		$fields = array();

		if ( array_key_exists( 'title', $data ) ) {
			$fields['title'] = sanitize_text_field( $data['title'] );
		}
		if ( array_key_exists( 'author', $data ) ) {
			$fields['author'] = sanitize_text_field( $data['author'] );
		}
		if ( array_key_exists( 'description', $data ) ) {
			$fields['description'] = wp_kses_post( $data['description'] );
		}
		if ( array_key_exists( 'cover_image_url', $data ) ) {
			$fields['cover_image_url'] = esc_url_raw( $data['cover_image_url'] );
		}
		if ( array_key_exists( 'status', $data ) ) {
			$fields['status'] = sanitize_text_field( $data['status'] );
		}
		if ( array_key_exists( 'access_type', $data ) ) {
			$fields['access_type'] = sanitize_text_field( $data['access_type'] );
		}
		if ( array_key_exists( 'price', $data ) ) {
			$fields['price'] = floatval( $data['price'] );
		}
		if ( array_key_exists( 'publish_date', $data ) ) {
			$fields['publish_date'] = ! empty( $data['publish_date'] ) ? date( 'Y-m-d H:i:s', strtotime( str_replace( 'T', ' ', $data['publish_date'] ) ) ) : null;
		}
		if ( array_key_exists( 'is_featured', $data ) ) {
			$fields['is_featured'] = ! empty( $data['is_featured'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'featured_title', $data ) ) {
			$fields['featured_title'] = sanitize_text_field( $data['featured_title'] );
		}
		if ( array_key_exists( 'featured_description', $data ) ) {
			$fields['featured_description'] = wp_kses_post( $data['featured_description'] );
		}
		if ( array_key_exists( 'featured_banner_id', $data ) ) {
			$fields['featured_banner_id'] = intval( $data['featured_banner_id'] );
		}
		if ( array_key_exists( 'featured_banner_url', $data ) ) {
			$fields['featured_banner_url'] = esc_url_raw( $data['featured_banner_url'] );
		}
		if ( array_key_exists( 'featured_button_1_label', $data ) ) {
			$fields['featured_button_1_label'] = sanitize_text_field( $data['featured_button_1_label'] );
		}
		if ( array_key_exists( 'featured_button_2_label', $data ) ) {
			$fields['featured_button_2_label'] = sanitize_text_field( $data['featured_button_2_label'] );
		}
		if ( array_key_exists( 'featured_order', $data ) ) {
			$fields['featured_order'] = intval( $data['featured_order'] );
		}
		if ( array_key_exists( 'wc_product_id', $data ) ) {
			$fields['wc_product_id'] = intval( $data['wc_product_id'] );
		}
		if ( ! empty( $data['file_path'] ) ) {
			$fields['file_path'] = sanitize_text_field( $data['file_path'] );
		}
		if ( ! empty( $data['file_type'] ) ) {
			$fields['file_type'] = sanitize_text_field( $data['file_type'] );
		}

		if ( empty( $fields ) ) {
			return false;
		}

		return $wpdb->update( $table, $fields, array( 'id' => intval( $id ) ) );
	}

	/**
	 * Check if user has completed a purchase for a specific book
	 */
	public function has_purchased_book( $user_id, $book_id ) {
		if ( ! $user_id || ! $book_id ) {
			return false;
		}

		// Admin override
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		global $wpdb;
		$table = $this->get_table_name( 'book_purchases' );
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return false;
		}

		$purchase = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE user_id = %d AND book_id = %d AND status = %s LIMIT 1",
				$table,
				$user_id,
				$book_id,
				'completed'
			)
		);

		return ! empty( $purchase );
	}

	/**
	 * Get array of book IDs purchased by user
	 */
	public function get_user_purchased_book_ids( $user_id ) {
		if ( ! $user_id ) {
			return array();
		}

		global $wpdb;
		$table = $this->get_table_name( 'book_purchases' );
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return array();
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT book_id FROM %i WHERE user_id = %d AND status = %s",
				$table,
				$user_id,
				'completed'
			)
		);

		return ! empty( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * Insert a book purchase record
	 */
	public function insert_book_purchase( $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'book_purchases' );

		// Check if record already exists for this order
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT id FROM %i WHERE order_id = %s", $table, $data['order_id'] )
		);

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'status'     => sanitize_text_field( $data['status'] ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id )
			);
			return $existing->id;
		}

		$wpdb->insert(
			$table,
			array(
				'user_id'        => intval( $data['user_id'] ),
				'book_id'        => intval( $data['book_id'] ),
				'order_id'       => sanitize_text_field( $data['order_id'] ),
				'amount'         => floatval( $data['amount'] ),
				'currency'       => sanitize_text_field( $data['currency'] ),
				'payment_engine' => sanitize_text_field( $data['payment_engine'] ),
				'status'         => sanitize_text_field( $data['status'] ),
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			)
		);

		$purchase_id = $wpdb->insert_id;
		if ( $purchase_id && isset( $data['status'] ) && 'completed' === $data['status'] ) {
			$book = $this->get_book( intval( $data['book_id'] ) );
			$book_title = $book ? $book->title : __( 'Book', 'digital-library-membership' );
			/* translators: %s: book title */
			$notif_title = sprintf( __( 'Purchase Confirmed: %s', 'digital-library-membership' ), $book_title );
			/* translators: %s: book title */
			$notif_msg   = sprintf( __( 'Your purchase is confirmed. You now have permanent access to read and download "%s".', 'digital-library-membership' ), $book_title );
			$this->create_notification( array(
				'user_id'   => intval( $data['user_id'] ),
				'type'      => 'purchase',
				'title'     => $notif_title,
				'message'   => $notif_msg,
				'link_url'  => home_url( '/read/' . intval( $data['book_id'] ) . '/' ),
			) );
		}

		return $purchase_id;
	}

	/**
	 * Update book purchase by order ID
	 */
	public function update_book_purchase( $order_id, $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'book_purchases' );
		$data['updated_at'] = current_time( 'mysql' );
		return $wpdb->update(
			$table,
			$data,
			array( 'order_id' => sanitize_text_field( $order_id ) )
		);
	}

	/**
	 * Refund a book purchase and immediately revoke access
	 */
	public function refund_book_purchase( $order_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'book_purchases' );
		return $wpdb->update(
			$table,
			array(
				'status'     => 'refunded',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'order_id' => sanitize_text_field( $order_id ) )
		);
	}

	/**
	 * Get book purchases with optional filtering for admin dashboard
	 */
	public function get_book_purchases( $filters = array() ) {
		global $wpdb;
		$t_pur = $this->get_table_name( 'book_purchases' );
		$t_bks = $this->get_table_name( 'books' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_pur ) ) !== $t_pur ) {
			return array();
		}
		$has_access = ( ! empty( $filters['access_type'] ) && $filters['access_type'] !== 'all' ) ? 1 : 0;
		$access_val = $has_access ? sanitize_text_field( $filters['access_type'] ) : '';

		$has_book   = ( ! empty( $filters['book_id'] ) ) ? 1 : 0;
		$book_val   = $has_book ? intval( $filters['book_id'] ) : 0;

		$has_status = ( ! empty( $filters['status'] ) && $filters['status'] !== 'all' ) ? 1 : 0;
		$status_val = $has_status ? sanitize_text_field( $filters['status'] ) : '';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*, b.title as book_title, b.access_type, b.cover_image_url, u.display_name, u.user_email 
				FROM %i p
				LEFT JOIN %i b ON p.book_id = b.id
				LEFT JOIN %i u ON p.user_id = u.ID
				WHERE (%d = 0 OR b.access_type = %s)
				  AND (%d = 0 OR p.book_id = %d)
				  AND (%d = 0 OR p.status = %s)
				ORDER BY p.created_at DESC",
				$t_pur,
				$t_bks,
				$wpdb->users,
				$has_access,
				$access_val,
				$has_book,
				$book_val,
				$has_status,
				$status_val
			)
		);
	}

	/**
	 * Auto-cancel stale pending orders older than 24 hours (Cron job)
	 */
	public function cleanup_stale_orders() {
		global $wpdb;
		$t_pur  = $this->get_table_name( 'book_purchases' );
		$t_subs = $this->get_table_name( 'subscriptions' );
		$t_tx   = $this->get_table_name( 'transactions' );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

		// Clean up stale book purchases
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_pur ) ) === $t_pur ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = %s, updated_at = %s WHERE status = %s AND created_at < %s",
					$t_pur,
					'failed',
					current_time( 'mysql' ),
					'pending_payment',
					$cutoff
				)
			);
		}

		// Clean up stale subscriptions
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_subs ) ) === $t_subs ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = %s, updated_at = %s WHERE status = %s AND created_at < %s",
					$t_subs,
					'cancelled',
					current_time( 'mysql' ),
					'pending',
					$cutoff
				)
			);
		}

		// Clean up stale transactions
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_tx ) ) === $t_tx ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = %s, updated_at = %s WHERE status = %s AND created_at < %s",
					$t_tx,
					'failed',
					current_time( 'mysql' ),
					'pending',
					$cutoff
				)
			);
		}
	}

	/**
	 * Flip scheduled books with publish_date in past to 'publish' status (Cron job)
	 */
	public function publish_scheduled_books() {
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		$now   = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET status = 'publish' WHERE status = 'future' AND publish_date IS NOT NULL AND publish_date != '' AND (publish_date <= %s OR REPLACE(publish_date, 'T', ' ') <= %s)",
				$table,
				$now,
				$now
			)
		);
	}

	/**
	 * Add manually created subscriber
	 */
	public function add_subscriber( $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'subscriptions' );

		return $wpdb->insert(
			$table,
			array(
				'user_id'       => intval( $data['user_id'] ),
				'customer_id'   => sanitize_text_field( $data['customer_id'] ),
				'status'        => sanitize_text_field( $data['status'] ),
				'provider'      => sanitize_text_field( $data['provider'] ),
				'plan_interval' => sanitize_text_field( $data['plan_interval'] ),
				'expires_at'    => sanitize_text_field( $data['expires_at'] ),
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Update transaction by ID
	 */
	public function update_transaction( $id, $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'transactions' );
		return $wpdb->update(
			$table,
			$data,
			array( 'id' => intval( $id ) )
		);
	}

	/**
	 * Get transaction by ID
	 */
	public function get_transaction( $id ) {
		global $wpdb;
		$table = $this->get_table_name( 'transactions' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $table, $id ) );
	}

	/**
	 * Delete transaction by ID
	 */
	public function delete_transaction( $id ) {
		global $wpdb;
		$table = $this->get_table_name( 'transactions' );
		return $wpdb->delete(
			$table,
			array( 'id' => intval( $id ) )
		);
	}

	/**
	 * Fetch top trending books ordered by engagement count
	 */
	public function get_trending_books( $limit = 10 ) {
		global $wpdb;
		$t_an  = $this->get_table_name( 'analytics' );
		$t_bks = $this->get_table_name( 'books' );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.*, COUNT(a.id) as engagement 
			FROM %i b
			LEFT JOIN %i a ON a.book_id = b.id AND a.event_type IN ('open', 'page_view')
			GROUP BY b.id
			ORDER BY engagement DESC, b.created_at DESC
			LIMIT %d",
			$t_bks, $t_an, $limit
		) );

		return $results;
	}

	/**
	 * Create a new user notification
	 *
	 * @param array $data Notification data (user_id, type, title, message, link_url, is_read, deduplicate_days)
	 * @return int|false Notification ID or false
	 */
	public function create_notification( $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'notifications' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			DLM_Activator::check_and_upgrade_db();
		}

		$user_id  = isset( $data['user_id'] ) ? intval( $data['user_id'] ) : 0;
		$type     = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'general';
		$title    = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$message  = isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '';
		$link_url = isset( $data['link_url'] ) ? sanitize_text_field( $data['link_url'] ) : '';
		$is_read  = ! empty( $data['is_read'] ) ? 1 : 0;

		if ( ! $user_id || empty( $title ) ) {
			return false;
		}

		// Idempotency check if deduplicate_days is specified
		if ( ! empty( $data['deduplicate_days'] ) ) {
			$days  = intval( $data['deduplicate_days'] );
			$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM %i WHERE user_id = %d AND type = %s AND title = %s AND created_at >= %s LIMIT 1",
				$table,
				$user_id,
				$type,
				$title,
				$since
			) );
			if ( $exists ) {
				return intval( $exists );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'type'       => $type,
				'title'      => $title,
				'message'    => $message,
				'link_url'   => $link_url,
				'is_read'    => $is_read,
				'created_at' => current_time( 'mysql' ),
			)
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Check if a notification already exists for user
	 *
	 * @param int $user_id
	 * @param string $type
	 * @param string|null $title_like
	 * @param string|null $since_date
	 * @return bool
	 */
	public function notification_exists( $user_id, $type, $title_like = null, $since_date = null ) {
		global $wpdb;
		$table = $this->get_table_name( 'notifications' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return false;
		}

		$uid        = intval( $user_id );
		$type_clean = sanitize_key( $type );

		if ( ! empty( $title_like ) && ! empty( $since_date ) ) {
			$title_param = '%' . $wpdb->esc_like( $title_like ) . '%';
			$since_param = sanitize_text_field( $since_date );
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM %i WHERE user_id = %d AND type = %s AND title LIKE %s AND created_at >= %s LIMIT 1",
					$table,
					$uid,
					$type_clean,
					$title_param,
					$since_param
				)
			);
		} elseif ( ! empty( $title_like ) ) {
			$title_param = '%' . $wpdb->esc_like( $title_like ) . '%';
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM %i WHERE user_id = %d AND type = %s AND title LIKE %s LIMIT 1",
					$table,
					$uid,
					$type_clean,
					$title_param
				)
			);
		} elseif ( ! empty( $since_date ) ) {
			$since_param = sanitize_text_field( $since_date );
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM %i WHERE user_id = %d AND type = %s AND created_at >= %s LIMIT 1",
					$table,
					$uid,
					$type_clean,
					$since_param
				)
			);
		} else {
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM %i WHERE user_id = %d AND type = %s LIMIT 1",
					$table,
					$uid,
					$type_clean
				)
			);
		}

		return ! empty( $found );
	}

	/**
	 * Get paginated notifications for user
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function get_user_notifications( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table = $this->get_table_name( 'notifications' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$table,
			intval( $user_id ),
			intval( $limit ),
			intval( $offset )
		) );
	}

	/**
	 * Get count of unread notifications for user
	 *
	 * @param int $user_id
	 * @return int
	 */
	public function get_unread_notifications_count( $user_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'notifications' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM %i WHERE user_id = %d AND is_read = 0",
			$table,
			intval( $user_id )
		) );

		return intval( $count );
	}

	/**
	 * Mark a single notification as read
	 *
	 * @param int $notification_id
	 * @param int $user_id
	 * @return int|false
	 */
	public function mark_notification_read( $notification_id, $user_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'notifications' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			$table,
			array( 'is_read' => 1 ),
			array(
				'id'      => intval( $notification_id ),
				'user_id' => intval( $user_id ),
			)
		);
	}

	/**
	 * Mark all notifications as read for a user
	 *
	 * @param int $user_id
	 * @return int|false
	 */
	public function mark_all_notifications_read( $user_id ) {
		global $wpdb;
		$table = $this->get_table_name( 'notifications' );

		return $wpdb->update(
			$table,
			array( 'is_read' => 1 ),
			array( 'user_id' => intval( $user_id ) )
		);
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
