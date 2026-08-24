<?php
/**
 * Security and capabilities management
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Security {

	/**
	 * Verify Nonce with post_max_size detection and multi-key fallback
	 *
	 * @param string $action    Nonce action name.
	 * @param string $query_arg Field name containing nonce (default '_wpnonce' or 'dlm_nonce').
	 */
	public static function verify_nonce( $action, $query_arg = '_wpnonce' ) {
		// Detect if PHP silently discarded $_POST due to post_max_size / upload_max_filesize limit exceeded
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
		if ( 'POST' === $request_method ) {
			$content_length = isset( $_SERVER['CONTENT_LENGTH'] ) ? intval( $_SERVER['CONTENT_LENGTH'] ) : 0;
			if ( $content_length > 0 && empty( $_POST ) && empty( $_FILES ) ) {
				$post_max = ini_get( 'post_max_size' );
				$upload_max = ini_get( 'upload_max_filesize' );
				wp_die(
					sprintf(
						/* translators: 1: post_max_size, 2: upload_max_filesize */
						esc_html__( 'Upload failed: The submitted document file exceeds your server\'s PHP post_max_size limit (%1$s) or upload_max_filesize limit (%2$s). Please upload a smaller file or increase these limits in your php.ini configuration.', 'digital-library-membership' ),
						esc_html( $post_max ?: 'unknown' ),
						esc_html( $upload_max ?: 'unknown' )
					)
				);
			}
		}

		$nonce = '';
		// Check primary specified query arg
		if ( ! empty( $_POST[ $query_arg ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST[ $query_arg ] ) );
		} elseif ( ! empty( $_GET[ $query_arg ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET[ $query_arg ] ) );
		} elseif ( ! empty( $_REQUEST[ $query_arg ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) );
		} elseif ( ! empty( $_POST['dlm_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['dlm_nonce'] ) );
		} elseif ( ! empty( $_GET['dlm_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['dlm_nonce'] ) );
		} elseif ( ! empty( $_POST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
		} elseif ( ! empty( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		} elseif ( ! empty( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $action ) ) {
			// Check if action variation works (e.g. without _nonce suffix)
			if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, str_replace( '_nonce', '', $action ) ) ) {
				return;
			}

			// If current user is administrator with manage_options capability, log and prevent hard lockouts
			if ( current_user_can( 'manage_options' ) && ! empty( $nonce ) ) {
				if ( check_admin_referer( $action, $query_arg, false ) ) {
					return;
				}
			}

			wp_die( esc_html__( 'Security check failed. Invalid or expired session nonce. Please refresh the page and try again.', 'digital-library-membership' ) );
		}
	}

	/**
	 * Check capability to edit DLM documents
	 */
	public static function check_admin_capabilities() {
		if ( ! current_user_can( 'manage_dlm_library' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'digital-library-membership' ) );
		}
	}

	/**
	 * Check capability to read books
	 */
	public static function check_read_capabilities() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		if ( ! current_user_can( 'read_dlm_library' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to read books in the library.', 'digital-library-membership' ) );
		}
	}

	/**
	 * Secure Input Sanitizer
	 */
	public static function sanitize_input_array( $array ) {
		$sanitized = array();
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = self::sanitize_input_array( $value );
			} else {
				$sanitized[ $key ] = sanitize_text_field( $value );
			}
		}
		return $sanitized;
	}
}

