<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://profiles.wordpress.org/mipallab123
 * @since             1.5.2
 * @package           DLM
 *
 * @wordpress-plugin
 * Plugin Name:       Digital Library Membership
 * Plugin URI:        https://profiles.wordpress.org/mipallab123/digital-library-membership
 * Description:       A premium, secure subscription membership plugin to read digital books frontend with a physical page-flip feel.
 * Version:           1.5.3
 * Author:            Majadul Islam Pallab
 * Author URI:        https://profiles.wordpress.org/mipallab123
 * License:           GPL-2.0+
 * Text Domain:       digital-library-membership
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      8.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Plugin Constants
 */
define( 'DLM_VERSION', '1.5.3' );
define( 'DLM_PATH', plugin_dir_path( __FILE__ ) );
define( 'DLM_URL', plugin_dir_url( __FILE__ ) );

// Setup uploads path for protected documents
$upload_dir = wp_upload_dir();
define( 'DLM_PROTECTED_DIR', $upload_dir['basedir'] . '/dlm-protected-books' );
define( 'DLM_PROTECTED_URL', $upload_dir['baseurl'] . '/dlm-protected-books' );

/**
 * Register Autoloader for namespaces (PSR-4-like loading for includes)
 */
spl_autoload_register( function ( $class ) {
	// Only load classes with DLM prefix
	if ( strpos( $class, 'DLM' ) !== 0 ) {
		return;
	}

	if ( $class === 'DLM' ) {
		$file_name = 'class-dlm.php';
	} else {
		// Convert class name to file path format: DLM_Admin -> class-dlm-admin.php
		$class_name = str_replace( 'DLM_', '', $class );
		$file_name  = 'class-dlm-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	}
	
	// Check includes directory first, then public directory
	$file_path  = DLM_PATH . 'includes/' . $file_name;
	if ( ! file_exists( $file_path ) ) {
		$file_path = DLM_PATH . 'public/' . $file_name;
	}

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

/**
 * The code that runs during plugin activation.
 */
function activate_digital_library_membership() {
	require_once DLM_PATH . 'includes/class-dlm-activator.php';
	DLM_Activator::activate();

	// Schedule daily subscription expiry warning
	if ( ! wp_next_scheduled( 'dlm_daily_subscription_check' ) ) {
		wp_schedule_event( time(), 'daily', 'dlm_daily_subscription_check' );
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_digital_library_membership() {
	require_once DLM_PATH . 'includes/class-dlm-deactivator.php';
	DLM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_digital_library_membership' );
register_deactivation_hook( __FILE__, 'deactivate_digital_library_membership' );

/**
 * Initialize Composer Autoloader if available
 */
if ( file_exists( DLM_PATH . 'vendor/autoload.php' ) ) {
	require_once DLM_PATH . 'vendor/autoload.php';
}

/**
 * Begins execution of the plugin.
 */
function run_digital_library_membership() {
	// Instantiate core plugin class
	$plugin = new DLM();
	$plugin->run();
}
run_digital_library_membership();
