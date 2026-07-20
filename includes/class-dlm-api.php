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
	}

	/**
	 * Validate REST request reader capabilities
	 */
	public function check_read_permission( $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'dlm_unauthorized', __( 'You must be logged in.', 'digital-library-membership' ), array( 'status' => 401 ) );
		}
		
		if ( ! $this->db->has_active_membership( $user_id ) ) {
			return new WP_Error( 'dlm_no_subscription', __( 'Active subscription required.', 'digital-library-membership' ), array( 'status' => 403 ) );
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
}

