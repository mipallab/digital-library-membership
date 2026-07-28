<?php
/**
 * Handle GitHub-based plugin updates using Plugin Update Checker.
 *
 * @package           DLM
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

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

		// Ensure we load the library if not already loaded by Composer
		if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
			$manual_puc = dirname( $plugin_file ) . '/vendor/plugin-update-checker/plugin-update-checker.php';
			if ( file_exists( $manual_puc ) ) {
				require_once $manual_puc;
			}
		}

		// If the class still doesn't exist, fail silently
		if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
			return;
		}

		if ( empty( $slug ) ) {
			$slug = basename( dirname( $plugin_file ) );
		}

		try {
			$update_checker = PucFactory::buildUpdateChecker(
				$repo_url,
				$plugin_file,
				$slug
			);

			// Enable release assets so updates are pulled from release zip files
			// Note: Commented out because the default source code ZIP from GitHub is sufficient
			// and doesn't require uploading a custom built ZIP file to every Release's assets section.
			/*
			if ( method_exists( $update_checker, 'getVcsApi' ) ) {
				$vcs_api = $update_checker->getVcsApi();
				if ( $vcs_api && method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
					$vcs_api->enableReleaseAssets();
				}
			}
			*/

			// Pass authentication token if available
			if ( ! empty( $token ) && method_exists( $update_checker, 'setAuthentication' ) ) {
				$update_checker->setAuthentication( $token );
			}
		} catch ( \Throwable $e ) {
			// Fail silently, but log if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'DLM Plugin Update Checker Error: ' . $e->getMessage() );
			}
		}
	}
}
