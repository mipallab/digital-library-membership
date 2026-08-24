<?php
/**
 * Handle GitHub-based plugin updates using Plugin Update Checker.
 *
 * @package           DLM
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Updater {

	/**
	 * Initialize the update checker.
	 *
	 * @param string $plugin_file Path to the main plugin file.
	 * @param string $repo_url    GitHub repository URL.
	 * @param string $slug        Plugin slug.
	 * @param string $token       Optional GitHub Personal Access Token.
	 */
	public static function init( $plugin_file, $repo_url, $slug = '', $token = '' ) {
		// Only run if GitHub updater library is loaded
		$puc_class = implode( '\\', array( 'YahnisElsts', 'PluginUpdateChecker', 'v5', 'PucFactory' ) );
		
		if ( ! class_exists( $puc_class ) ) {
			$manual_puc = dirname( $plugin_file ) . '/vendor/plugin-update-checker/plugin-update-checker.php';
			if ( file_exists( $manual_puc ) ) {
				require_once $manual_puc;
			}
		}

		if ( ! class_exists( $puc_class ) || ! is_callable( array( $puc_class, 'buildUpdateChecker' ) ) ) {
			return;
		}

		if ( empty( $slug ) ) {
			$slug = basename( dirname( $plugin_file ) );
		}

		try {
			$update_checker = call_user_func( array( $puc_class, 'buildUpdateChecker' ), $repo_url, $plugin_file, $slug );

			// Pass authentication token if available
			if ( ! empty( $token ) && is_object( $update_checker ) && method_exists( $update_checker, 'setAuthentication' ) ) {
				$update_checker->setAuthentication( $token );
			}
		} catch ( \Throwable $e ) {
			// Fail silently in production
			return;
		}
	}
}
