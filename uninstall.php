<?php
/**
 * Fired when the plugin is deleted via the WordPress Plugins administration screen.
 *
 * @since      2.2.0
 * @package    DLM
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only proceed with destructive database cleanup if explicitly configured by the site administrator
$delete_data = get_option( 'dlm_delete_data_on_uninstall', '0' );

if ( '1' === $delete_data || true === $delete_data ) {
	global $wpdb;

	// Drop all custom tables created by DLM
	$tables = array(
		$wpdb->prefix . 'dlm_books',
		$wpdb->prefix . 'dlm_subscriptions',
		$wpdb->prefix . 'dlm_transactions',
		$wpdb->prefix . 'dlm_book_purchases',
		$wpdb->prefix . 'dlm_analytics',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Delete all plugin options
	$options = array(
		'dlm_stripe_secret_key',
		'dlm_stripe_publishable_key',
		'dlm_stripe_monthly_price_id',
		'dlm_stripe_yearly_price_id',
		'dlm_stripe_lifetime_price_id',
		'dlm_stripe_webhook_secret',
		'dlm_paypal_client_id',
		'dlm_paypal_secret_key',
		'dlm_paypal_monthly_plan_id',
		'dlm_paypal_yearly_plan_id',
		'dlm_paypal_lifetime_plan_id',
		'dlm_paypal_webhook_id',
		'dlm_pricing_monthly',
		'dlm_pricing_yearly',
		'dlm_pricing_lifetime',
		'dlm_manual_payment_instructions',
		'dlm_features_monthly',
		'dlm_features_yearly',
		'dlm_features_lifetime',
		'dlm_wc_monthly_product',
		'dlm_wc_yearly_product',
		'dlm_wc_lifetime_product',
		'dlm_privacy_policy_page_id',
		'dlm_terms_page_id',
		'dlm_recaptcha_enable',
		'dlm_recaptcha_version',
		'dlm_recaptcha_site_key',
		'dlm_recaptcha_secret_key',
		'dlm_recaptcha_mode',
		'dlm_setup_completed',
		'dlm_github_token',
		'dlm_payment_engine',
		'dlm_enable_woocommerce',
		'dlm_enable_woocommerce_gateway',
		'dlm_enable_stripe_gateway',
		'dlm_enable_paypal_gateway',
		'dlm_enable_manual_gateway',
		'dlm_currency',
		'dlm_max_upload_size',
		'dlm_db_version',
		'dlm_demo_data_imported',
		'dlm_enable_google_login',
		'dlm_google_client_id',
		'dlm_google_client_secret',
		'dlm_enable_apple_login',
		'dlm_apple_services_id',
		'dlm_apple_team_id',
		'dlm_apple_key_id',
		'dlm_apple_private_key',
		'dlm_subscription_packages',
		'dlm_delete_data_on_uninstall',
	);

	foreach ( $options as $opt ) {
		delete_option( $opt );
	}

	// Delete all cached transients
	delete_transient( 'dlm_analytics_summary' );
	delete_transient( 'dlm_trending_books' );
	delete_transient( 'dlm_importing_demo' );
}
