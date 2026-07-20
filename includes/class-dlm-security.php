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
	 * Verify Nonce
	 */
	public static function verify_nonce( $action, $query_arg = '_wpnonce' ) {
		$nonce = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( 'Security check failed. Invalid nonce.', 'digital-library-membership' ) );
		}
	}

	/**
	 * Check capability to edit DLM documents
	 */
	public static function check_admin_capabilities() {
		if ( ! current_user_can( 'manage_dlm_library' ) ) {
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

		if ( ! current_user_can( 'read_dlm_library' ) ) {
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

