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

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE status = %s AND (publish_date IS NULL OR publish_date <= %s) ORDER BY created_at DESC",
					$table,
					'publish',
					$now
				)
			);
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC", $table, $status ) );
	}

	/**
	 * Insert/Create book
	 */
	public function insert_book( $data ) {
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		
		$insert_data = array(
			'title'           => sanitize_text_field( $data['title'] ),
			'author'          => sanitize_text_field( $data['author'] ),
			'description'     => wp_kses_post( $data['description'] ),
			'cover_image_url' => esc_url_raw( $data['cover_image_url'] ),
			'file_path'       => sanitize_text_field( $data['file_path'] ),
			'file_type'       => sanitize_text_field( $data['file_type'] ),
			'status'          => sanitize_text_field( $data['status'] ),
			'access_type'     => isset( $data['access_type'] ) ? sanitize_text_field( $data['access_type'] ) : 'subscription_only',
			'price'           => isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00,
			'publish_date'    => ! empty( $data['publish_date'] ) ? sanitize_text_field( $data['publish_date'] ) : null,
			'wc_product_id'   => isset( $data['wc_product_id'] ) ? intval( $data['wc_product_id'] ) : 0,
			'created_at'      => current_time( 'mysql' ),
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
		global $wpdb;
		$table = $this->get_table_name( 'books' );
		
		$fields = array(
			'title'           => sanitize_text_field( $data['title'] ),
			'author'          => sanitize_text_field( $data['author'] ),
			'description'     => wp_kses_post( $data['description'] ),
			'cover_image_url' => esc_url_raw( $data['cover_image_url'] ),
			'status'          => sanitize_text_field( $data['status'] ),
		);

		if ( isset( $data['access_type'] ) ) {
			$fields['access_type'] = sanitize_text_field( $data['access_type'] );
		}
		if ( isset( $data['price'] ) ) {
			$fields['price'] = floatval( $data['price'] );
		}
		if ( array_key_exists( 'publish_date', $data ) ) {
			$fields['publish_date'] = ! empty( $data['publish_date'] ) ? sanitize_text_field( $data['publish_date'] ) : null;
		}
		if ( isset( $data['wc_product_id'] ) ) {
			$fields['wc_product_id'] = intval( $data['wc_product_id'] );
		}

		if ( ! empty( $data['file_path'] ) ) {
			$fields['file_path'] = sanitize_text_field( $data['file_path'] );
		}
		if ( ! empty( $data['file_type'] ) ) {
			$fields['file_type'] = sanitize_text_field( $data['file_type'] );
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

		return $wpdb->insert_id;
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

		$where = array( '1=1' );
		$args  = array();

		if ( ! empty( $filters['access_type'] ) && $filters['access_type'] !== 'all' ) {
			$where[] = 'b.access_type = %s';
			$args[]  = $filters['access_type'];
		}

		if ( ! empty( $filters['book_id'] ) ) {
			$where[] = 'p.book_id = %d';
			$args[]  = intval( $filters['book_id'] );
		}

		if ( ! empty( $filters['status'] ) && $filters['status'] !== 'all' ) {
			$where[] = 'p.status = %s';
			$args[]  = $filters['status'];
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT p.*, b.title as book_title, b.access_type, b.cover_image_url, u.display_name, u.user_email 
			FROM {$t_pur} p
			LEFT JOIN {$t_bks} b ON p.book_id = b.id
			LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
			WHERE {$where_clause}
			ORDER BY p.created_at DESC";

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare( $query, $args ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $query );
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
					"UPDATE %i SET status = 'cancelled', updated_at = %s WHERE status = 'pending' AND created_at < %s",
					$t_pur,
					current_time( 'mysql' ),
					$cutoff
				)
			);
		}

		// Clean up stale pending manual subscriptions older than 24 hours
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_subs ) ) === $t_subs ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = 'expired', updated_at = %s WHERE status = 'pending' AND created_at < %s",
					$t_subs,
					current_time( 'mysql' ),
					$cutoff
				)
			);
		}

		// Clean up stale transactions
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_tx ) ) === $t_tx ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = 'cancelled' WHERE status IN ('pending', 'waiting_approval') AND created_at < %s",
					$t_tx,
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
				"UPDATE %i SET status = 'publish' WHERE status = 'future' AND publish_date IS NOT NULL AND publish_date <= %s",
				$table,
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
}
