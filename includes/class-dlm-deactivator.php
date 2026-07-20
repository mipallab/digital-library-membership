<?php
/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Deactivator {

	/**
	 * Run deactivation tasks.
	 */
	public static function deactivate() {
		// Flush rewrite rules on deactivation
		flush_rewrite_rules();

		// Clear daily subscription checks schedule
		wp_clear_scheduled_hook( 'dlm_daily_subscription_check' );
	}
}
