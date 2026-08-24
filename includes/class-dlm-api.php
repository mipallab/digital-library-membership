<?php
/**
 * REST API Endpoints for Secure PDF Streaming & Progress tracking
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_API {

	private $db;

	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Register API routes
	 */
	public function register_routes() {
		register_rest_route( 'dlm/v1', '/book/(?P<id>\d+)/details', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_book_details' ),
			'permission_callback' => array( $this, 'check_read_permission' ),
		) );

		register_rest_route( 'dlm/v1', '/book/(?P<id>\d+)/stream', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'stream_book_file' ),
			'permission_callback' => array( $this, 'check_read_permission' ),
		) );

		register_rest_route( 'dlm/v1', '/book/(?P<id>\d+)/progress', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'update_reading_progress' ),
			'permission_callback' => array( $this, 'check_read_permission' ),
		) );

		register_rest_route( 'dlm/v1', '/book/(?P<id>\d+)/analytics', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'log_analytics_event' ),
			'permission_callback' => array( $this, 'check_read_permission' ),
		) );

		register_rest_route( 'dlm/v1', '/book/(?P<id>\d+)/download-token', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_download_token' ),
			'permission_callback' => array( $this, 'check_download_permission' ),
		) );

		register_rest_route( 'dlm/v1', '/book/(?P<id>\d+)/download', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'download_book_file' ),
			'permission_callback' => '__return_true', // Validated via signed token inside callback
		) );
	}

	/**
	 * Validate REST request reader capabilities using the central access matrix
	 */
	public function check_read_permission( $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'dlm_unauthorized', __( 'You must be logged in.', 'digital-library-membership' ), array( 'status' => 401 ) );
		}

		$book_id = intval( $request['id'] );
		$access  = dlm_user_can_access_book( $user_id, $book_id );

		if ( 'locked' === $access ) {
			return new WP_Error( 'dlm_access_locked', __( 'Active subscription or book purchase required to access this title.', 'digital-library-membership' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Validate REST request download token generation permissions
	 */
	public function check_download_permission( $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'dlm_unauthorized', __( 'You must be logged in.', 'digital-library-membership' ), array( 'status' => 401 ) );
		}

		$book_id = intval( $request['id'] );
		$access  = dlm_user_can_access_book( $user_id, $book_id );

		if ( 'read_download' !== $access ) {
			return new WP_Error( 'dlm_download_forbidden', __( 'Download access is not available for this title with your current access level.', 'digital-library-membership' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Get metadata details for a book
	 */
	public function get_book_details( $request ) {
		$book_id = intval( $request['id'] );
		$book = $this->db->get_book( $book_id );

		if ( ! $book ) {
			return new WP_Error( 'dlm_not_found', __( 'Book not found.', 'digital-library-membership' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( array(
			'id'          => $book->id,
			'title'       => $book->title,
			'author'      => $book->author,
			'file_type'   => $book->file_type,
			'cover'       => $book->cover_image_url,
			'description' => $book->description,
		) );
	}

	/**
	 * Stream secure e-book PDF using HTTP Range chunks
	 */
	public function stream_book_file( $request ) {
		$book_id = intval( $request['id'] );
		$book = $this->db->get_book( $book_id );

		if ( ! $book || ! file_exists( $book->file_path ) ) {
			return new WP_Error( 'dlm_not_found', __( 'File not found.', 'digital-library-membership' ), array( 'status' => 404 ) );
		}

		// Prevent search engine caching or intermediate proxies
		header( 'Cache-Control: private, no-transform, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Content-Disposition: inline; filename="' . basename( $book->file_path ) . '"' );
		header( 'Accept-Ranges: bytes' );

		// Set content type
		$mime_type = ( $book->file_type === 'epub' ) ? 'application/epub+zip' : 'application/pdf';
		header( 'Content-Type: ' . $mime_type );

		$file_path = $book->file_path;
		$file_size = filesize( $file_path );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$fp = @fopen( $file_path, 'rb' );

		if ( ! $fp ) {
			return new WP_Error( 'dlm_read_error', __( 'Unable to open file stream.', 'digital-library-membership' ), array( 'status' => 500 ) );
		}

		$start = 0;
		$end = $file_size - 1;

		// Parse HTTP Range header if present (standard linearized PDF page streaming)
		if ( isset( $_SERVER['HTTP_RANGE'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$range = sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) );
			if ( preg_match( '/bytes=\s*(\d+)-(\d*)/i', $range, $matches ) ) {
				$start = intval( $matches[1] );
				if ( ! empty( $matches[2] ) ) {
					$end = intval( $matches[2] );
				}
			}

			// Validate range constraints
			if ( $start > $end || $start >= $file_size || $end >= $file_size ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes */$file_size" );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $fp );
				exit;
			}

			header( 'HTTP/1.1 206 Partial Content' );
			header( "Content-Range: bytes $start-$end/$file_size" );
		}

		$length = $end - $start + 1;
		header( "Content-Length: $length" );

		// Seek file pointer to start position
		fseek( $fp, $start );

		// Read and flush contents in 8KB chunks
		$buffer = 8192;
		$bytes_sent = 0;

		while ( ! feof( $fp ) && $bytes_sent < $length && ( connection_status() === 0 ) ) {
			$to_read = min( $buffer, $length - $bytes_sent );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$data = fread( $fp, $to_read );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $data;
			flush();
			$bytes_sent += strlen( $data );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $fp );
		exit;
	}

	/**
	 * Save Bookmark reading progress
	 */
	public function update_reading_progress( $request ) {
		$book_id = intval( $request['id'] );
		$params  = $request->get_json_params();

		$page    = isset( $params['page'] ) ? intval( $params['page'] ) : 1;
		$percent = isset( $params['percent'] ) ? intval( $params['percent'] ) : 0;
		$user_id = get_current_user_id();

		$this->db->save_reading_progress( $user_id, $book_id, $page, $percent );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Log reader engagement events (e.g. opens, page views)
	 */
	public function log_analytics_event( $request ) {
		$book_id = intval( $request['id'] );
		$params  = $request->get_json_params();

		$event_type  = isset( $params['event'] ) ? sanitize_text_field( $params['event'] ) : '';
		$page_number = isset( $params['page'] ) ? intval( $params['page'] ) : null;
		$time_spent  = isset( $params['time'] ) ? intval( $params['time'] ) : 0;

		if ( ! empty( $event_type ) ) {
			$user_id = get_current_user_id();
			$this->db->log_analytics_event( $user_id, $book_id, $event_type, $page_number, $time_spent );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get a signed, time-limited download token URL for authorized user
	 */
	public function get_download_token( $request ) {
		$book_id = intval( $request['id'] );
		$user_id = get_current_user_id();

		$token_data = dlm_generate_download_token( $user_id, $book_id );

		return rest_ensure_response( array(
			'success'      => true,
			'download_url' => $token_data['url'],
			'expires'      => $token_data['expires'],
		) );
	}

	/**
	 * Securely stream and force file download using validated time-limited signed token
	 */
	public function download_book_file( $request ) {
		$book_id = intval( $request['id'] );
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$expires = isset( $_GET['expires'] ) ? intval( $_GET['expires'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$uid     = isset( $_GET['uid'] ) ? intval( $_GET['uid'] ) : get_current_user_id();

		if ( ! dlm_verify_download_token( $uid, $book_id, $token, $expires ) ) {
			wp_die( esc_html__( 'Invalid, expired, or tampered download token. Please request a new download link from your member library.', 'digital-library-membership' ), 403 );
		}

		if ( dlm_user_can_access_book( $uid, $book_id ) !== 'read_download' ) {
			wp_die( esc_html__( 'You do not have permission to download this title.', 'digital-library-membership' ), 403 );
		}

		$book = $this->db->get_book( $book_id );
		if ( ! $book || empty( $book->file_path ) ) {
			wp_die( esc_html__( 'The requested book file does not exist on this server.', 'digital-library-membership' ), 404 );
		}

		$real_file_path = realpath( $book->file_path );
		if ( ! $real_file_path || ! is_file( $real_file_path ) || ! is_readable( $real_file_path ) ) {
			wp_die( esc_html__( 'The requested book file is unreadable or missing from this server.', 'digital-library-membership' ), 404 );
		}

		// Clean filename for attachment
		$file_ext       = pathinfo( $real_file_path, PATHINFO_EXTENSION ) ?: 'pdf';
		$clean_filename = sanitize_file_name( $book->title ) . '.' . $file_ext;

		// Clean all output buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Security & caching headers
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $clean_filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: private, must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $real_file_path ) );

		// Stream file out
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $real_file_path );
		exit;
	}
}

