<?php
/**
 * Demo Data Importer and Removal Manager
 *
 * Provides a unified engine to import and safely clean up realistic sample content
 * covering all 3 access models (subscription_only, purchase_only, hybrid), future scheduled
 * publishing, taxonomies (categories and tags), demo members, and purchases/transactions.
 *
 * @since      2.1.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Demo_Data {

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
	public function __construct( $db = null, $checkout = null ) {
		$this->db       = $db ?: new DLM_DB();
		$this->checkout = $checkout ?: new DLM_Checkout();
	}

	/**
	 * Check if demo data is currently imported
	 *
	 * @return bool
	 */
	public function is_demo_imported() {
		if ( 'yes' === get_option( 'dlm_demo_data_imported' ) ) {
			return true;
		}

		global $wpdb;
		$table_books = $this->db->get_table_name( 'books' );
		
		// Check if table exists before querying
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_books ) );
		if ( ! $table_exists ) {
			return false;
		}

		// Check if any books are tagged as demo
		$has_demo_books = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE is_demo = 1", $table_books ) );
		if ( intval( $has_demo_books ) > 0 ) {
			return true;
		}

		// Check if any demo users exist
		$demo_users = get_users( array(
			'meta_key'   => '_dlm_is_demo',
			'meta_value' => '1',
			'number'     => 1,
			'fields'     => 'ID',
		) );

		return ! empty( $demo_users );
	}

	/**
	 * Get breakdown statistics of imported demo data
	 *
	 * @return array
	 */
	public function get_demo_stats() {
		global $wpdb;

		$stats = array(
			'books'        => 0,
			'users'        => 0,
			'purchases'    => 0,
			'transactions' => 0,
			'subscriptions'=> 0,
			'wc_products'  => 0,
		);

		$table_books     = $this->db->get_table_name( 'books' );
		$table_purchases = $this->db->get_table_name( 'book_purchases' );
		$table_txs       = $this->db->get_table_name( 'transactions' );
		$table_subs      = $this->db->get_table_name( 'subscriptions' );

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_books ) ) ) {
			$stats['books'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE is_demo = 1", $table_books ) );
		}

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_purchases ) ) ) {
			$stats['purchases'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE is_demo = 1", $table_purchases ) );
		}

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_txs ) ) ) {
			$stats['transactions'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE is_demo = 1", $table_txs ) );
		}

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_subs ) ) ) {
			$stats['subscriptions'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE is_demo = 1", $table_subs ) );
		}

		$demo_users = get_users( array(
			'meta_key'   => '_dlm_is_demo',
			'meta_value' => '1',
			'fields'     => 'ID',
		) );
		$stats['users'] = count( $demo_users );

		$wc_demo_products = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => '_dlm_is_demo',
			'meta_value'     => '1',
			'fields'         => 'ids',
		) );
		$stats['wc_products'] = count( $wc_demo_products );

		return $stats;
	}

	/**
	 * Generate a minimal valid sample PDF document on disk
	 *
	 * @return string File path
	 */
	private function ensure_sample_pdf() {
		if ( ! file_exists( DLM_PROTECTED_DIR ) ) {
			wp_mkdir_p( DLM_PROTECTED_DIR );
		}

		$pdf_path = DLM_PROTECTED_DIR . '/demo_sample_manuscript.pdf';
		if ( ! file_exists( $pdf_path ) ) {
			// Construct a minimal valid 1-page PDF file
			$pdf_content = "%PDF-1.4\n" .
				"1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n" .
				"2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n" .
				"3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n" .
				"4 0 obj << /Length 135 >> stream\n" .
				"BT\n" .
				"/F1 22 Tf\n" .
				"50 720 Td\n" .
				"(Bridgeway36 Digital Library - Sample Manuscript) Tj\n" .
				"/F1 12 Tf\n" .
				"0 -30 Td\n" .
				"(This is a sample document for testing book reader and download features.) Tj\n" .
				"ET\n" .
				"endstream\n" .
				"endobj\n" .
				"5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n" .
				"xref\n" .
				"0 6\n" .
				"0000000000 65535 f \n" .
				"0000000009 00000 n \n" .
				"0000000058 00000 n \n" .
				"0000000115 00000 n \n" .
				"0000000244 00000 n \n" .
				"0000000431 00000 n \n" .
				"trailer << /Size 6 /Root 1 0 R >>\n" .
				"startxref\n" .
				"508\n" .
				"%%EOF";

			file_put_contents( $pdf_path, $pdf_content );
		}

		return $pdf_path;
	}

	/**
	 * Setup demo taxonomy categories and tags
	 *
	 * @return array Array containing created terms mapped by name
	 */
	private function setup_taxonomies() {
		$categories = array(
			'Architecture & Design'            => 'architecture-design',
			'Computer Science & Engineering'   => 'computer-science',
			'Ancient History & Civilizations'  => 'ancient-history',
			'Artificial Intelligence & Data'   => 'ai-data',
			'Philosophy & Creative Arts'       => 'philosophy-arts',
		);

		$tags = array(
			'Bestseller' => 'bestseller',
			'Essential'  => 'essential',
			'Research'   => 'research',
			'Tutorial'   => 'tutorial',
			'Classic'    => 'classic',
			'Featured'   => 'featured',
		);

		$created_cats = array();
		foreach ( $categories as $name => $slug ) {
			$term = get_term_by( 'slug', $slug, 'dlm_book_category' );
			if ( ! $term ) {
				$inserted = wp_insert_term( $name, 'dlm_book_category', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $inserted ) ) {
					$created_cats[ $name ] = $inserted['term_id'];
				}
			} else {
				$created_cats[ $name ] = $term->term_id;
			}
		}

		$created_tags = array();
		foreach ( $tags as $name => $slug ) {
			$term = get_term_by( 'slug', $slug, 'dlm_book_tag' );
			if ( ! $term ) {
				$inserted = wp_insert_term( $name, 'dlm_book_tag', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $inserted ) ) {
					$created_tags[ $name ] = $inserted['term_id'];
				}
			} else {
				$created_tags[ $name ] = $term->term_id;
			}
		}

		return array(
			'categories' => $created_cats,
			'tags'       => $created_tags,
		);
	}

	/**
	 * Import full realistic demo dataset
	 *
	 * @return array Result summary
	 */
	public function import() {
		// Prevent concurrent executions
		if ( get_transient( 'dlm_importing_demo' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Import is currently in progress. Please wait a moment.', 'digital-library-membership' ),
			);
		}

		set_transient( 'dlm_importing_demo', '1', 60 );

		// 1. Ensure DB schemas have is_demo column
		DLM_Activator::check_and_upgrade_db();

		// 2. Ensure pricing settings have realistic defaults
		if ( ! get_option( 'dlm_pricing_monthly' ) ) {
			update_option( 'dlm_pricing_monthly', '9.99' );
		}
		if ( ! get_option( 'dlm_pricing_yearly' ) ) {
			update_option( 'dlm_pricing_yearly', '99.99' );
		}
		if ( ! get_option( 'dlm_pricing_lifetime' ) ) {
			update_option( 'dlm_pricing_lifetime', '199.99' );
		}
		if ( ! get_option( 'dlm_currency' ) ) {
			update_option( 'dlm_currency', 'USD' );
		}

		// 3. Ensure sample PDF & Taxonomies
		$pdf_path = $this->ensure_sample_pdf();
		$tax = $this->setup_taxonomies();

		$wc_manager = new DLM_WooCommerce( $this->db, $this->checkout );

		// 4. Create Demo Books
		$books_definitions = array(
			// Subscription Only Books
			array(
				'title'           => 'The Architecture of Silence',
				'author'          => 'Elena Vance',
				'description'     => 'An insightful exploration into acoustic proportion, minimalist sacred spaces, and the psychology of silence in classical and contemporary architecture.',
				'access_type'     => 'subscription_only',
				'price'           => 0.00,
				'category'        => 'Architecture & Design',
				'tags'            => array( 'Essential', 'Featured' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),
			array(
				'title'           => 'Quantum Computing for Thinkers',
				'author'          => 'Dr. Marcus Sterling',
				'description'     => 'A clear, intuitive journey through qubits, quantum superposition, and cryptographic algorithms that will reshape tomorrow\'s computational landscape.',
				'access_type'     => 'subscription_only',
				'price'           => 0.00,
				'category'        => 'Computer Science & Engineering',
				'tags'            => array( 'Research', 'Bestseller' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),
			array(
				'title'           => 'Voices of the Ancient Mediterranean',
				'author'          => 'Sophia Hadad',
				'description'     => 'A rich historical anthology examining maritime trade routes, Bronze Age collapses, and literary fragments from ancient civilizations.',
				'access_type'     => 'subscription_only',
				'price'           => 0.00,
				'category'        => 'Ancient History & Civilizations',
				'tags'            => array( 'Classic' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),

			// Purchase Only Books (Paid individual)
			array(
				'title'           => 'Mastering Distributed Systems',
				'author'          => 'Liam Chen',
				'description'     => 'Definitive engineering handbook covering consensus protocols (Raft, Paxos), eventual consistency, event streaming, and resilient microservices architectures.',
				'access_type'     => 'purchase_only',
				'price'           => 29.99,
				'category'        => 'Computer Science & Engineering',
				'tags'            => array( 'Tutorial', 'Bestseller' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),
			array(
				'title'           => 'The Visual Narrative: Film Composition',
				'author'          => 'Maya Lin',
				'description'     => 'An exhaustive visual textbook on color grading, anamorphic framing, lighting schemes, and cinematic storytelling techniques.',
				'access_type'     => 'purchase_only',
				'price'           => 19.99,
				'category'        => 'Philosophy & Creative Arts',
				'tags'            => array( 'Featured' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),

			// Hybrid Books (Subscribers get free + non-subscribers can buy)
			array(
				'title'           => 'Algorithmic Geometry in Modern Typography',
				'author'          => 'Jonathan Ray',
				'description'     => 'Analyzing Bézier curves, parametric font generation, optical kerning math, and dynamic generative letterforms.',
				'access_type'     => 'hybrid',
				'price'           => 14.99,
				'category'        => 'Architecture & Design',
				'tags'            => array( 'Research' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),
			array(
				'title'           => 'Chronicles of the Deep Cosmos',
				'author'          => 'Arthur Pendelton',
				'description'     => 'An astrophysics narrative tracing stellar nucleosynthesis, black hole thermodynamics, and cosmic microwave background observations.',
				'access_type'     => 'hybrid',
				'price'           => 24.99,
				'category'        => 'Philosophy & Creative Arts',
				'tags'            => array( 'Essential' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => null,
			),

			// Scheduled Future Hybrid Book
			array(
				'title'           => 'Frontiers of Artificial Superintelligence',
				'author'          => 'Alan K. Sterling',
				'description'     => 'Philosophical and engineering perspectives on foundation models, AI alignment, neural reasoning architectures, and autonomous agents.',
				'access_type'     => 'hybrid',
				'price'           => 34.99,
				'category'        => 'Artificial Intelligence & Data',
				'tags'            => array( 'Bestseller' ),
				'cover_image_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=800&q=80',
				'publish_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
			),
		);

		$created_book_ids = array();

		foreach ( $books_definitions as $b_def ) {
			$book_data = array(
				'title'           => $b_def['title'],
				'author'          => $b_def['author'],
				'description'     => $b_def['description'],
				'cover_image_url' => $b_def['cover_image_url'],
				'file_path'       => $pdf_path,
				'file_type'       => 'pdf',
				'status'          => 'publish',
				'access_type'     => $b_def['access_type'],
				'price'           => $b_def['price'],
				'publish_date'    => $b_def['publish_date'],
			);

			$book_id = $this->db->insert_book( $book_data );
			if ( $book_id ) {
				// Mark as demo in database
				global $wpdb;
				$table_books = $this->db->get_table_name( 'books' );
				$wpdb->update( $table_books, array( 'is_demo' => 1 ), array( 'id' => $book_id ) );

				// Assign taxonomy terms
				if ( ! empty( $b_def['category'] ) && isset( $tax['categories'][ $b_def['category'] ] ) ) {
					wp_set_object_terms( $book_id, array( $tax['categories'][ $b_def['category'] ] ), 'dlm_book_category' );
				}

				if ( ! empty( $b_def['tags'] ) ) {
					$tag_ids = array();
					foreach ( $b_def['tags'] as $t_name ) {
						if ( isset( $tax['tags'][ $t_name ] ) ) {
							$tag_ids[] = $tax['tags'][ $t_name ];
						}
					}
					if ( ! empty( $tag_ids ) ) {
						wp_set_object_terms( $book_id, $tag_ids, 'dlm_book_tag' );
					}
				}

				// If purchasable, synchronize with WooCommerce virtual product
				if ( $b_def['access_type'] !== 'subscription_only' && class_exists( 'WooCommerce' ) ) {
					$wc_prod_id = $wc_manager->sync_book_wc_product( $book_id, $book_data );
					if ( $wc_prod_id ) {
						update_post_meta( $wc_prod_id, '_dlm_is_demo', '1' );
					}
				}

				$created_book_ids[] = $book_id;
			}
		}

		// 5. Create Demo Users
		$users_definitions = array(
			array(
				'user_login'   => 'demo_sarah',
				'user_email'   => 'demo_sarah@example.com',
				'display_name' => 'Sarah Jenkins',
				'first_name'   => 'Sarah',
				'last_name'    => 'Jenkins',
				'avatar_url'   => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=256&q=80',
				'role'         => 'subscriber',
				'has_cap'      => true,
				'sub_type'     => 'monthly',
			),
			array(
				'user_login'   => 'demo_alex',
				'user_email'   => 'demo_alex@example.com',
				'display_name' => 'Alex Rivera',
				'first_name'   => 'Alex',
				'last_name'    => 'Rivera',
				'avatar_url'   => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=256&q=80',
				'role'         => 'subscriber',
				'has_cap'      => true,
				'sub_type'     => 'yearly',
			),
			array(
				'user_login'   => 'demo_david',
				'user_email'   => 'demo_david@example.com',
				'display_name' => 'David Thorne',
				'first_name'   => 'David',
				'last_name'    => 'Thorne',
				'avatar_url'   => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=256&q=80',
				'role'         => 'subscriber',
				'has_cap'      => false,
				'sub_type'     => null, // Individual book buyer
			),
			array(
				'user_login'   => 'demo_clara',
				'user_email'   => 'demo_clara@example.com',
				'display_name' => 'Clara Oswald',
				'first_name'   => 'Clara',
				'last_name'    => 'Oswald',
				'avatar_url'   => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&q=80',
				'role'         => 'subscriber',
				'has_cap'      => false,
				'sub_type'     => 'inactive',
			),
		);

		$created_users = array();

		foreach ( $users_definitions as $u_def ) {
			$user_id = email_exists( $u_def['user_email'] );
			if ( ! $user_id ) {
				$user_id = username_exists( $u_def['user_login'] );
			}

			if ( ! $user_id ) {
				$user_id = wp_insert_user( array(
					'user_login'   => $u_def['user_login'],
					'user_email'   => $u_def['user_email'],
					'display_name' => $u_def['display_name'],
					'first_name'   => $u_def['first_name'],
					'last_name'    => $u_def['last_name'],
					'user_pass'    => 'demo12345!',
					'role'         => $u_def['role'],
				) );
			}

			if ( ! is_wp_error( $user_id ) ) {
				update_user_meta( $user_id, '_dlm_is_demo', '1' );
				if ( ! empty( $u_def['avatar_url'] ) ) {
					update_user_meta( $user_id, 'dlm_avatar_url', esc_url_raw( $u_def['avatar_url'] ) );
				}

				$wp_user = new WP_User( $user_id );
				if ( $u_def['has_cap'] ) {
					$wp_user->add_cap( 'read_dlm_library' );
				} else {
					$wp_user->remove_cap( 'read_dlm_library' );
				}

				$created_users[ $u_def['user_login'] ] = array(
					'id'       => $user_id,
					'def'      => $u_def,
				);
			}
		}

		// 6. Create Demo Subscriptions
		global $wpdb;
		$table_subs = $this->db->get_table_name( 'subscriptions' );

		// Sarah - Active Monthly
		if ( isset( $created_users['demo_sarah'] ) ) {
			$s_uid = $created_users['demo_sarah']['id'];
			$wpdb->insert( $table_subs, array(
				'user_id'         => $s_uid,
				'provider'        => 'stripe',
				'subscription_id' => 'DEMO-SUB-STRIPE-SARAH',
				'customer_id'     => 'cus_demo_sarah',
				'status'          => 'active',
				'plan_interval'   => 'monthly',
				'expires_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '+30 days' ) ),
				'is_demo'         => 1,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			) );
		}

		// Alex - Active Yearly
		if ( isset( $created_users['demo_alex'] ) ) {
			$a_uid = $created_users['demo_alex']['id'];
			$wpdb->insert( $table_subs, array(
				'user_id'         => $a_uid,
				'provider'        => 'paypal',
				'subscription_id' => 'DEMO-SUB-PP-ALEX',
				'customer_id'     => 'I-DEMO-ALEX-9988',
				'status'          => 'active',
				'plan_interval'   => 'yearly',
				'expires_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '+365 days' ) ),
				'is_demo'         => 1,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			) );
		}

		// Clara - Lapsed / Inactive
		if ( isset( $created_users['demo_clara'] ) ) {
			$c_uid = $created_users['demo_clara']['id'];
			$wpdb->insert( $table_subs, array(
				'user_id'         => $c_uid,
				'provider'        => 'manual',
				'subscription_id' => 'DEMO-SUB-MAN-CLARA',
				'customer_id'     => 'cust_demo_clara',
				'status'          => 'inactive',
				'plan_interval'   => 'monthly',
				'expires_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '-15 days' ) ),
				'is_demo'         => 1,
				'created_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '-45 days' ) ),
				'updated_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '-15 days' ) ),
			) );
		}

		// 7. Create Demo Purchases & Transactions
		$table_purchases = $this->db->get_table_name( 'book_purchases' );
		$table_txs       = $this->db->get_table_name( 'transactions' );

		// Demo transactions for subscriptions
		if ( isset( $created_users['demo_sarah'] ) ) {
			$wpdb->insert( $table_txs, array(
				'user_id'         => $created_users['demo_sarah']['id'],
				'subscription_id' => 'DEMO-SUB-STRIPE-SARAH',
				'transaction_id'  => 'DEMO-TX-STRIPE-101',
				'provider'        => 'stripe',
				'amount'          => 9.99,
				'currency'        => 'USD',
				'status'          => 'completed',
				'is_demo'         => 1,
				'created_at'      => current_time( 'mysql' ),
			) );
		}

		if ( isset( $created_users['demo_alex'] ) ) {
			$wpdb->insert( $table_txs, array(
				'user_id'         => $created_users['demo_alex']['id'],
				'subscription_id' => 'DEMO-SUB-PP-ALEX',
				'transaction_id'  => 'DEMO-TX-PP-102',
				'provider'        => 'paypal',
				'amount'          => 99.99,
				'currency'        => 'USD',
				'status'          => 'completed',
				'is_demo'         => 1,
				'created_at'      => current_time( 'mysql' ),
			) );
		}

		// Demo purchases on books
		if ( count( $created_book_ids ) >= 7 ) {
			$b4_id = $created_book_ids[3]; // Mastering Distributed Systems ($29.99)
			$b5_id = $created_book_ids[4]; // The Visual Narrative ($19.99)
			$b6_id = $created_book_ids[5]; // Algorithmic Geometry ($14.99)
			$b7_id = $created_book_ids[6]; // Chronicles of the Deep Cosmos ($24.99)

			// 1. David Thorne owns Book 4 (Completed)
			if ( isset( $created_users['demo_david'] ) ) {
				$d_uid = $created_users['demo_david']['id'];
				$wpdb->insert( $table_purchases, array(
					'user_id'        => $d_uid,
					'book_id'        => $b4_id,
					'order_id'       => 'DEMO-ORD-WC-501',
					'amount'         => 29.99,
					'currency'       => 'USD',
					'payment_engine' => 'woocommerce',
					'status'         => 'completed',
					'is_demo'        => 1,
					'created_at'     => current_time( 'mysql' ),
					'updated_at'     => current_time( 'mysql' ),
				) );

				$wpdb->insert( $table_txs, array(
					'user_id'         => $d_uid,
					'subscription_id' => 'BOOK-' . $b4_id,
					'transaction_id'  => 'DEMO-TX-WC-501',
					'provider'        => 'woocommerce',
					'amount'          => 29.99,
					'currency'        => 'USD',
					'status'          => 'completed',
					'is_demo'         => 1,
					'created_at'      => current_time( 'mysql' ),
				) );

				// 2. David Thorne has pending purchase on Book 5
				$wpdb->insert( $table_purchases, array(
					'user_id'        => $d_uid,
					'book_id'        => $b5_id,
					'order_id'       => 'DEMO-ORD-MAN-502',
					'amount'         => 19.99,
					'currency'       => 'USD',
					'payment_engine' => 'default',
					'status'         => 'pending',
					'is_demo'        => 1,
					'created_at'     => current_time( 'mysql' ),
					'updated_at'     => current_time( 'mysql' ),
				) );

				$wpdb->insert( $table_txs, array(
					'user_id'         => $d_uid,
					'subscription_id' => 'BOOK-' . $b5_id,
					'transaction_id'  => 'DEMO-TX-MAN-502',
					'provider'        => 'manual',
					'amount'          => 19.99,
					'currency'        => 'USD',
					'status'          => 'pending',
					'is_demo'         => 1,
					'created_at'      => current_time( 'mysql' ),
				) );
			}

			// 3. Clara Oswald bought Book 6 (Completed)
			if ( isset( $created_users['demo_clara'] ) ) {
				$c_uid = $created_users['demo_clara']['id'];
				$wpdb->insert( $table_purchases, array(
					'user_id'        => $c_uid,
					'book_id'        => $b6_id,
					'order_id'       => 'DEMO-ORD-WC-503',
					'amount'         => 14.99,
					'currency'       => 'USD',
					'payment_engine' => 'woocommerce',
					'status'         => 'completed',
					'is_demo'        => 1,
					'created_at'     => current_time( 'mysql' ),
					'updated_at'     => current_time( 'mysql' ),
				) );

				$wpdb->insert( $table_txs, array(
					'user_id'         => $c_uid,
					'subscription_id' => 'BOOK-' . $b6_id,
					'transaction_id'  => 'DEMO-TX-WC-503',
					'provider'        => 'woocommerce',
					'amount'          => 14.99,
					'currency'        => 'USD',
					'status'          => 'completed',
					'is_demo'         => 1,
					'created_at'      => current_time( 'mysql' ),
				) );
			}

			// 4. Sarah Jenkins had Book 7 Refunded
			if ( isset( $created_users['demo_sarah'] ) ) {
				$s_uid = $created_users['demo_sarah']['id'];
				$wpdb->insert( $table_purchases, array(
					'user_id'        => $s_uid,
					'book_id'        => $b7_id,
					'order_id'       => 'DEMO-ORD-WC-504',
					'amount'         => 24.99,
					'currency'       => 'USD',
					'payment_engine' => 'woocommerce',
					'status'         => 'refunded',
					'is_demo'        => 1,
					'created_at'     => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
					'updated_at'     => current_time( 'mysql' ),
				) );

				$wpdb->insert( $table_txs, array(
					'user_id'         => $s_uid,
					'subscription_id' => 'BOOK-' . $b7_id,
					'transaction_id'  => 'DEMO-TX-WC-504',
					'provider'        => 'woocommerce',
					'amount'          => 24.99,
					'currency'        => 'USD',
					'status'          => 'refunded',
					'is_demo'         => 1,
					'created_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
				) );
			}
		}

		// 8. Mark demo import completed and clear caches
		update_option( 'dlm_demo_data_imported', 'yes' );
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );
		delete_transient( 'dlm_importing_demo' );

		$stats = $this->get_demo_stats();

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: Books count, 2: Users count, 3: Purchases count, 4: Transactions count */
				__( 'Demo data imported successfully! Added %1$d books, %2$d members, %3$d purchases, and %4$d transactions.', 'digital-library-membership' ),
				$stats['books'],
				$stats['users'],
				$stats['purchases'],
				$stats['transactions']
			),
			'stats'   => $stats,
		);
	}

	/**
	 * Remove all tagged demo data cleanly from the system
	 *
	 * @return array Result summary
	 */
	public function remove() {
		global $wpdb;

		// 1. Delete all demo users
		$demo_users = get_users( array(
			'meta_key'   => '_dlm_is_demo',
			'meta_value' => '1',
			'fields'     => 'ID',
		) );

		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( $demo_users as $uid ) {
			wp_delete_user( $uid );
		}

		// Also check user_login with demo_ prefix if meta was missing
		$prefix_users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'demo_%'" );
		if ( ! empty( $prefix_users ) ) {
			foreach ( $prefix_users as $p_uid ) {
				wp_delete_user( $p_uid );
			}
		}

		// 2. Delete all demo book purchases
		$table_purchases = $this->db->get_table_name( 'book_purchases' );
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_purchases ) ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE is_demo = 1 OR order_id LIKE %s", $table_purchases, 'DEMO-%' ) );
		}

		// 3. Delete all demo transactions
		$table_txs = $this->db->get_table_name( 'transactions' );
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_txs ) ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE is_demo = 1 OR transaction_id LIKE %s", $table_txs, 'DEMO-%' ) );
		}

		// 4. Delete all demo subscriptions
		$table_subs = $this->db->get_table_name( 'subscriptions' );
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_subs ) ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE is_demo = 1 OR subscription_id LIKE %s", $table_subs, 'DEMO-%' ) );
		}

		// 5. Delete all demo linked WooCommerce virtual products
		$wc_demo_products = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => '_dlm_is_demo',
			'meta_value'     => '1',
			'fields'         => 'ids',
		) );

		foreach ( $wc_demo_products as $wc_pid ) {
			wp_delete_post( $wc_pid, true );
		}

		// 6. Delete all demo books
		$table_books = $this->db->get_table_name( 'books' );
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_books ) ) ) {
			$demo_book_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM %i WHERE is_demo = 1", $table_books ) );
			foreach ( $demo_book_ids as $bid ) {
				$this->db->delete_book( $bid );
			}
		}

		// 7. Clean up sample PDF file
		$sample_pdf = DLM_PROTECTED_DIR . '/demo_sample_manuscript.pdf';
		if ( file_exists( $sample_pdf ) ) {
			wp_delete_file( $sample_pdf );
		}

		// 8. Reset options and clear cache transients
		delete_option( 'dlm_demo_data_imported' );
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );
		delete_transient( 'dlm_importing_demo' );

		return array(
			'success' => true,
			'message' => __( 'All demo content, users, purchases, and products have been cleanly removed.', 'digital-library-membership' ),
		);
	}
}
