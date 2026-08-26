<?php
/**
 * Standalone SPA Member Dashboard Template
 * Overrides the theme template when page has [dlm_account] shortcode.
 *
 * @package DLM
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

$dlm_db = new DLM_DB();

// Global states to expose to JS if logged in
$achievements = array();
$notes = array();
$fav_books = array();
$user_display_name = '';
$user_email = '';
$avatar_url = '';
$has_active_sub = false;
$sub_details = null;
$books = array();
$categories_terms = array();

if ( $is_logged_in ) {
	// Achievements
	$ach_raw = get_user_meta( $user_id, 'dlm_achievements_state', true );
	if ( ! $ach_raw ) {
		$achievements = array(
			'streak' => 0,
			'lastVisit' => null,
			'xp' => 0,
			'level' => 1,
			'booksOpened' => 0,
			'badges' => array(),
			'goalMinutesToday' => 0,
			'dailyGoal' => 20
		);
	} else {
		$achievements = json_decode( $ach_raw, true );
		if ( ! is_array( $achievements ) ) {
			$achievements = array();
		}
	}

	// Badges verification (ensure joined badge exists)
	$joined_badge_exists = false;
	if ( ! empty( $achievements['badges'] ) ) {
		foreach ( $achievements['badges'] as $b ) {
			if ( $b['id'] === 'joined' ) {
				$joined_badge_exists = true;
				break;
			}
		}
	}
	if ( ! $joined_badge_exists ) {
		$achievements['badges'][] = array(
			'id' => 'joined',
			'label' => 'Joined the Archive',
			'earned' => gmdate( 'Y-m-d' )
		);
		update_user_meta( $user_id, 'dlm_achievements_state', json_encode( $achievements ) );
	}

	// Notes
	$notes_raw = get_user_meta( $user_id, 'dlm_journal_notes', true );
	$notes = $notes_raw ? json_decode( $notes_raw, true ) : array();

	// Favorites
	$fav_raw = get_user_meta( $user_id, 'dlm_favorite_books', true );
	$fav_books = $fav_raw ? json_decode( $fav_raw, true ) : array();

	// User details
	$wp_user = wp_get_current_user();
	$user_display_name = $wp_user->display_name;
	$user_email = $wp_user->user_email;
	
	// Custom avatar URL
	$avatar_url = get_user_meta( $user_id, 'dlm_avatar_url', true );
	if ( ! $avatar_url ) {
		// Fallback to standard gravatar
		$avatar_url = get_avatar_url( $user_id, array( 'size' => 128 ) );
	}

	$has_active_sub = $dlm_db->has_active_membership( $user_id );
	$sub_details = $dlm_db->get_subscription_by_user( $user_id );

	// Get published & scheduled books
	$books = $dlm_db->get_books( 'publish', true );
	$featured_books = $dlm_db->get_featured_books( 10 );
	if ( empty( $featured_books ) && ! empty( $books ) ) {
		$featured_books = array( $books[0] );
	}
	
	// Get all categories terms
	$categories_terms = get_terms( array(
		'taxonomy'   => 'dlm_book_category',
		'hide_empty' => false,
		'parent'     => 0,
	) );

	// Persistent notifications from database
	$db_notifications   = $dlm_db->get_user_notifications( $user_id, 20 );
	$unread_notif_count = $dlm_db->get_unread_notifications_count( $user_id );

	// Onboarding tour status
	$onboarding_completed   = get_user_meta( $user_id, 'dlm_onboarding_completed', true );
	$should_show_onboarding = ( empty( $onboarding_completed ) || $onboarding_completed === 'no' );
}

// Pricing options
$currency       = get_option( 'dlm_currency', '$' );
$payment_engine = function_exists( 'dlm_get_payment_engine' ) ? dlm_get_payment_engine() : get_option( 'dlm_payment_engine', 'default' );
$wc_is_active   = class_exists( 'WooCommerce' );

// Active Payment Gateways Configuration
$enable_wc     = function_exists( 'dlm_is_gateway_enabled' ) ? dlm_is_gateway_enabled( 'woocommerce' ) : class_exists( 'WooCommerce' );
$enable_stripe = function_exists( 'dlm_is_gateway_enabled' ) ? dlm_is_gateway_enabled( 'stripe' ) : true;
$enable_paypal = function_exists( 'dlm_is_gateway_enabled' ) ? dlm_is_gateway_enabled( 'paypal' ) : true;
$enable_manual = function_exists( 'dlm_is_gateway_enabled' ) ? dlm_is_gateway_enabled( 'manual' ) : true;

$active_gateways = array();
if ( $enable_wc )     { $active_gateways[] = 'woocommerce'; }
if ( $enable_stripe ) { $active_gateways[] = 'stripe'; }
if ( $enable_paypal ) { $active_gateways[] = 'paypal'; }
if ( $enable_manual ) { $active_gateways[] = 'manual'; }

$default_gateway = ! empty( $active_gateways ) ? reset( $active_gateways ) : '';

// Single source of truth packages
$all_packages    = dlm_get_packages();
$active_packages = array_filter( $all_packages, function( $p ) {
	return ! isset( $p['status'] ) || 'active' === $p['status'];
} );

$pkg_monthly  = dlm_get_package( 'monthly' );
$pkg_yearly   = dlm_get_package( 'yearly' );
$pkg_lifetime = dlm_get_package( 'lifetime' );

$price_monthly  = $pkg_monthly ? $pkg_monthly['price'] : '9.99';
$price_yearly   = $pkg_yearly ? $pkg_yearly['price'] : '99.99';
$price_lifetime = $pkg_lifetime ? $pkg_lifetime['price'] : '199.99';

$stripe_publishable_key = get_option( 'dlm_stripe_publishable_key' );
$paypal_client_id       = get_option( 'dlm_paypal_client_id' );
$paypal_monthly_plan    = ( $pkg_monthly && ! empty( $pkg_monthly['paypal_plan_id'] ) ) ? $pkg_monthly['paypal_plan_id'] : '';
$paypal_yearly_plan     = ( $pkg_yearly && ! empty( $pkg_yearly['paypal_plan_id'] ) ) ? $pkg_yearly['paypal_plan_id'] : '';
$paypal_lifetime_plan   = ( $pkg_lifetime && ! empty( $pkg_lifetime['paypal_plan_id'] ) ) ? $pkg_lifetime['paypal_plan_id'] : '';

// Public parameters localize
$dlm_public_nonce = wp_create_nonce( 'dlm_public_nonce' );
$ajax_url = admin_url( 'admin-ajax.php' );
?>
<!DOCTYPE html>
<html class="light" <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta content="width=device-width, initial-scale=1.0" name="viewport">
	<title>Member Portal | Bridgeway36 Digital Library</title>
	<?php wp_head(); ?>
	<script id="tailwind-config">
		tailwind.config = {
			darkMode: "class",
			theme: {
				extend: {
					"colors": {
						"primary-fixed-dim": "#ffb95f",
						"tertiary": "#00658b",
						"on-surface-variant": "#534434",
						"surface-container-lowest": "#ffffff",
						"on-background": "#1a1c1c",
						"tertiary-container": "#1abdff",
						"on-error": "#ffffff",
						"primary": "#855300",
						"surface-container-low": "#f3f3f3",
						"primary-container": "#f59e0b",
						"on-secondary-fixed": "#1b1b1d",
						"on-surface": "#1a1c1c",
						"surface-container": "#eeeeee",
						"tertiary-fixed": "#c5e7ff",
						"surface-container-highest": "#e2e2e2",
						"on-secondary": "#ffffff",
						"background": "#f9f9f9",
						"on-error-container": "#93000a",
						"on-primary-container": "#613b00",
						"on-secondary-fixed-variant": "#474649",
						"on-secondary-container": "#636264",
						"tertiary-fixed-dim": "#7fd0ff",
						"secondary-fixed-dim": "#c8c6c8",
						"inverse-on-surface": "#f0f1f1",
						"surface-tint": "#855300",
						"primary-fixed": "#ffddb8",
						"surface-dim": "#dadada",
						"inverse-surface": "#2f3131",
						"surface-container-high": "#e8e8e8",
						"error-container": "#ffdad6",
						"secondary": "#5f5e60",
						"outline": "#867461",
						"error": "#ba1a1a",
						"on-tertiary-fixed": "#001e2d",
						"on-tertiary-fixed-variant": "#004c6a",
						"secondary-fixed": "#e4e2e4",
						"secondary-container": "#e2dfe1",
						"surface-variant": "#e2e2e2",
						"on-primary": "#ffffff",
						"outline-variant": "#d8c3ad",
						"on-primary-fixed": "#2a1700",
						"on-primary-fixed-variant": "#653e00",
						"inverse-primary": "#ffb95f",
						"surface-bright": "#f9f9f9",
						"on-tertiary": "#ffffff",
						"surface": "#f9f9f9",
						"on-tertiary-container": "#004966"
					},
					"borderRadius": {
						"DEFAULT": "0.25rem",
						"lg": "0.5rem",
						"xl": "0.75rem",
						"full": "9999px"
					},
					"spacing": {
						"unit": "8px",
						"margin-desktop": "48px",
						"margin-mobile": "20px",
						"gutter": "24px",
						"container-max": "1440px"
					},
					"fontFamily": {
						"title-sm": ["Plus Jakarta Sans"],
						"display-lg-mobile": ["Plus Jakarta Sans"],
						"display-lg": ["Plus Jakarta Sans"],
						"body-lg": ["Inter"],
						"label-caps": ["Inter"],
						"body-md": ["Inter"],
						"headline-md": ["Plus Jakarta Sans"]
					},
					"fontSize": {
						"title-sm": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
						"display-lg-mobile": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700"}],
						"display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
						"body-lg": ["17px", {"lineHeight": "28px", "fontWeight": "400"}],
						"label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
						"body-md": ["15px", {"lineHeight": "22px", "fontWeight": "400"}],
						"headline-md": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}]
					}
				},
			},
		}
	</script>
	<style>
		/* Viewport Root and Admin Bar Height Alignment */
		.dlm-portal-root {
			height: 100vh;
			height: 100dvh;
			width: 100%;
			display: flex;
			justify-content: center;
			overflow: hidden;
			background-color: #FAFAFA;
		}
		@media (min-width: 768px) {
			html, body.dlm-member-portal-body {
				height: 100%;
				overflow: hidden;
			}
			body.admin-bar .dlm-portal-root {
				height: calc(100vh - 32px);
				height: calc(100dvh - 32px);
			}
		}
		@media (max-width: 782px) {
			body.admin-bar .dlm-portal-root {
				height: calc(100vh - 46px);
				height: calc(100dvh - 46px);
			}
		}
		body {
			background-color: #FAFAFA;
			color: #1a1c1c;
			-webkit-font-smoothing: antialiased;
		}
		.glass-sidebar {
			background: rgba(255, 255, 255, 0.82);
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
		}
		.glass-card {
			background: rgba(255, 255, 255, 0.7);
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
		}
		.book-card-shadow {
			box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.08);
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}
		.book-card-shadow:hover {
			transform: translateY(-4px);
			box-shadow: 0px 12px 30px rgba(0, 0, 0, 0.12);
		}
		.hide-scrollbar::-webkit-scrollbar {
			display: none;
		}
		.hide-scrollbar {
			-ms-overflow-style: none;
			scrollbar-width: none;
		}

		/* ========================================================= */
		/* SPA THEME CUSTOM SCROLLBARS (Amber / Bronze Palette)     */
		/* ========================================================= */
		::-webkit-scrollbar {
			width: 8px;
			height: 8px;
		}
		::-webkit-scrollbar-track {
			background: #f7f3ee;
			border-radius: 9999px;
		}
		::-webkit-scrollbar-thumb {
			background: linear-gradient(180deg, #c7924c 0%, #855300 100%);
			border-radius: 9999px;
			border: 2px solid #f7f3ee;
			background-clip: padding-box;
		}
		::-webkit-scrollbar-thumb:hover {
			background: linear-gradient(180deg, #f59e0b 0%, #613b00 100%);
			border: 2px solid #f7f3ee;
			background-clip: padding-box;
		}

		.custom-scrollbar::-webkit-scrollbar {
			width: 6px;
			height: 6px;
		}
		.custom-scrollbar::-webkit-scrollbar-track {
			background: rgba(216, 195, 173, 0.2);
			border-radius: 9999px;
		}
		.custom-scrollbar::-webkit-scrollbar-thumb {
			background: linear-gradient(180deg, #c7924c 0%, #855300 100%);
			border-radius: 9999px;
			border: 1px solid rgba(255, 255, 255, 0.6);
		}
		.custom-scrollbar::-webkit-scrollbar-thumb:hover {
			background: linear-gradient(180deg, #f59e0b 0%, #613b00 100%);
		}

		/* Firefox Scrollbars Support */
		* {
			scrollbar-width: thin;
			scrollbar-color: #855300 #f7f3ee;
		}
		.custom-scrollbar {
			scrollbar-width: thin;
			scrollbar-color: #855300 rgba(216, 195, 173, 0.2);
		}

		/* Vertical scrolling containment & hardware acceleration */
		#dlm-main-content,
		.custom-scrollbar,
		aside nav {
			-webkit-overflow-scrolling: touch;
			overscroll-behavior-y: contain;
		}

		/* Responsive adaptations for laptops and compact-height screens (e.g. 1363px / 1366x768) */
		@media (max-height: 850px) and (min-width: 768px) {
			aside.glass-sidebar {
				padding: 1rem 1.15rem !important;
				gap: 0.25rem !important;
			}
			aside.glass-sidebar .sidebar-brand-wrapper {
				margin-bottom: 0.75rem !important;
			}
			aside.glass-sidebar nav a {
				padding: 0.5rem 0.75rem !important;
				font-size: 0.875rem !important;
				gap: 0.625rem !important;
			}
			aside.glass-sidebar nav a i {
				font-size: 1rem !important;
			}
			aside.glass-sidebar .sidebar-cta-card {
				padding: 0.625rem 0.75rem !important;
				margin-bottom: 0.25rem !important;
			}
			aside.glass-sidebar .sidebar-cta-card p {
				margin-bottom: 0.25rem !important;
			}
			aside.glass-sidebar .sidebar-cta-card button {
				padding-top: 0.35rem !important;
				padding-bottom: 0.35rem !important;
				font-size: 0.75rem !important;
			}
			aside.glass-sidebar .sidebar-footer-links a {
				padding: 0.35rem 0.625rem !important;
				font-size: 0.8125rem !important;
			}
		}

		aside, main, header {
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		}

		/* Mobile Drawer Left Sidebar */
		#mobile-sidebar-drawer {
			transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1) !important;
			will-change: transform;
			-webkit-overflow-scrolling: touch;
		}
		#mobile-sidebar-backdrop {
			transition: opacity 0.3s ease-in-out !important;
		}

		.sidebar-collapsed aside {
			width: 80px;
			padding-left: 0.5rem;
			padding-right: 0.5rem;
			align-items: center;
		}
		.sidebar-collapsed aside .sidebar-brand-text,
		.sidebar-collapsed aside .sidebar-nav-text,
		.sidebar-collapsed aside .sidebar-cta-card,
		.sidebar-collapsed aside .sidebar-footer-links span {
			display: none !important;
		}
		.sidebar-collapsed aside nav a {
			justify-content: center;
			padding-left: 0;
			padding-right: 0;
			width: 48px;
			height: 48px;
			border-radius: 12px;
		}
		.sidebar-collapsed aside .sidebar-footer-links a {
			justify-content: center;
			padding-left: 0;
			padding-right: 0;
			width: 48px;
			height: 48px;
		}
		.sidebar-collapsed aside .pt-4 {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 8px;
			border-top: 1px solid rgba(134, 116, 97, 0.15);
			width: 100%;
		}
		.sidebar-collapsed #sidebar-collapse-icon {
			transform: rotate(180deg);
		}
		#sidebar-collapse-icon {
			transition: transform 0.3s ease;
		}
		
		/* Toast styles override */
		#aurelian-toast-root {
			pointer-events: none;
		}

		/* Isolate member dashboard form controls from Elementor and theme styles overrides */
		body.dlm-member-portal-body input[type="text"],
		body.dlm-member-portal-body input[type="password"],
		body.dlm-member-portal-body input[type="email"],
		body.dlm-member-portal-body input[type="number"],
		body.dlm-member-portal-body input[type="url"],
		body.dlm-member-portal-body select,
		body.dlm-member-portal-body textarea,
		body.dlm-member-portal-body #spa-login-form input[type="text"],
		body.dlm-member-portal-body #spa-login-form input[type="password"],
		body.dlm-member-portal-body #spa-register-form input[type="text"],
		body.dlm-member-portal-body #spa-register-form input[type="email"],
		body.dlm-member-portal-body #spa-register-form input[type="password"] {
			font-family: 'Inter', sans-serif !important;
			border: 1px solid rgba(134, 116, 97, 0.3) !important;
			background-color: #ffffff !important;
			color: #1a1c1c !important;
			border-radius: 0.75rem !important;
			padding: 0.75rem 1rem !important;
			line-height: 1.25rem !important;
			font-size: 0.875rem !important;
			box-shadow: none !important;
			height: auto !important;
			outline: none !important;
			width: 100% !important;
			box-sizing: border-box !important;
		}
		body.dlm-member-portal-body input[type="text"]:focus,
		body.dlm-member-portal-body input[type="password"]:focus,
		body.dlm-member-portal-body input[type="email"]:focus,
		body.dlm-member-portal-body input[type="number"]:focus,
		body.dlm-member-portal-body input[type="url"]:focus,
		body.dlm-member-portal-body select:focus,
		body.dlm-member-portal-body textarea:focus {
			border-color: #855300 !important;
			box-shadow: 0 0 0 2px rgba(133, 83, 0, 0.2) !important;
		}
		/* Force reset and protect all general button tags inside dashboard against Elementor overrides */
		body.dlm-member-portal-body button {
			font-family: 'Inter', sans-serif !important;
			text-transform: none !important;
			background: transparent !important;
			background-color: transparent !important;
			border: none !important;
			box-shadow: none !important;
			border-radius: 0 !important;
			padding: 0 !important;
			margin: 0 !important;
			height: auto !important;
			width: auto !important;
			color: inherit !important;
			line-height: normal !important;
			outline: none !important;
			cursor: pointer !important;
			transition: all 0.2s ease-in-out !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
		}

		/* Enforce primary action button backgrounds, dimensions and font constraints */
		body.dlm-member-portal-body button.bg-primary,
		body.dlm-member-portal-body a.bg-primary,
		body.dlm-member-portal-body #spa-login-form button[type="submit"],
		body.dlm-member-portal-body #spa-register-form button[type="submit"] {
			background-color: #855300 !important;
			color: #ffffff !important;
			border-radius: 0.75rem !important;
			height: 3.5rem !important;
			padding: 0 2rem !important;
			font-family: 'Inter', sans-serif !important;
			font-size: 0.875rem !important;
			font-weight: 700 !important;
			border: none !important;
			box-shadow: 0 10px 15px -3px rgba(133, 83, 0, 0.15) !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			gap: 0.5rem !important;
			text-transform: none !important;
			cursor: pointer !important;
			transition: all 0.2s ease-in-out !important;
			box-sizing: border-box !important;
		}
		body.dlm-member-portal-body button.bg-primary:hover,
		body.dlm-member-portal-body a.bg-primary:hover,
		body.dlm-member-portal-body #spa-login-form button[type="submit"]:hover,
		body.dlm-member-portal-body #spa-register-form button[type="submit"]:hover {
			background-color: #613b00 !important;
			opacity: 0.95 !important;
			color: #ffffff !important;
		}

		/* Full width overrides for action buttons */
		body.dlm-member-portal-body #spa-login-form button[type="submit"],
		body.dlm-member-portal-body #spa-register-form button[type="submit"] {
			width: 100% !important;
			margin-top: 1.5rem !important;
		}
		body.dlm-member-portal-body #stripe-checkout-container button,
		body.dlm-member-portal-body #manual-checkout-container button {
			width: 100% !important;
		}

		/* Explicitly protect Sign In / Create Account tabs from theme/Elementor button styling overrides */
		body.dlm-member-portal-body #auth-tabs button {
			background: transparent !important;
			background-color: transparent !important;
			border: none !important;
			box-shadow: none !important;
			border-radius: 0 !important;
			padding: 0 0 0.75rem 0 !important;
			margin: 0 !important;
			height: auto !important;
			width: 50% !important;
			font-family: 'Inter', sans-serif !important;
			text-transform: none !important;
			line-height: normal !important;
			outline: none !important;
			cursor: pointer !important;
			transition: all 0.2s ease-in-out !important;
			box-sizing: border-box !important;
			display: inline-block !important;
		}
		body.dlm-member-portal-body #auth-tabs button.border-primary {
			border-bottom: 2px solid #855300 !important;
			color: #1a1c1c !important;
			font-weight: 700 !important;
		}
		body.dlm-member-portal-body #auth-tabs button.border-transparent {
			border-bottom: 2px solid transparent !important;
			color: #5f5e60 !important;
			font-weight: 500 !important;
		}

		/* Enforce secondary and method button styles */
		body.dlm-member-portal-body .method-btn,
		body.dlm-member-portal-body button.method-btn {
			border: 1px solid rgba(216, 195, 173, 0.4) !important;
			background-color: #ffffff !important;
			border-radius: 0.75rem !important;
			padding: 1.25rem !important;
			display: flex !important;
			align-items: center !important;
			justify-content: space-between !important;
			width: 100% !important;
			text-align: left !important;
			height: auto !important;
			box-shadow: none !important;
			box-sizing: border-box !important;
		}
		body.dlm-member-portal-body .method-btn.border-primary,
		body.dlm-member-portal-body button.method-btn.border-primary {
			border-color: #855300 !important;
			border-width: 2px !important;
		}

		/* Library Book Card Action Buttons (Read & Download with White BG & Black Text) */
		body.dlm-member-portal-body .dlm-book-action-btn,
		body.dlm-member-portal-body button.dlm-book-action-btn {
			background: #ffffff !important;
			background-color: #ffffff !important;
			color: #000000 !important;
			font-family: 'Inter', sans-serif !important;
			font-weight: 700 !important;
			font-size: 0.75rem !important;
			line-height: 1rem !important;
			padding: 0.5rem 0.75rem !important;
			border-radius: 0.75rem !important;
			border: 1px solid rgba(0, 0, 0, 0.08) !important;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2) !important;
			width: 100% !important;
			max-width: 130px !important;
			text-align: center !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			cursor: pointer !important;
			transition: all 0.2s ease-in-out !important;
			text-transform: none !important;
			height: auto !important;
		}
		body.dlm-member-portal-body .dlm-book-action-btn:hover,
		body.dlm-member-portal-body button.dlm-book-action-btn:hover {
			background: #f3f3f3 !important;
			background-color: #f3f3f3 !important;
			color: #000000 !important;
			transform: translateY(-2px) scale(1.03) !important;
			box-shadow: 0 6px 18px rgba(0, 0, 0, 0.28) !important;
		}

		/* Preserve input paddings for inner icons */
		body.dlm-member-portal-body input.pl-12,
		body.dlm-member-portal-body #spa-register-form input.pl-12 {
			padding-left: 3rem !important;
		}
		body.dlm-member-portal-body input.pr-12 {
			padding-right: 3rem !important;
		}
		body.dlm-member-portal-body input.pl-10 {
			padding-left: 2.5rem !important;
		}
		body.dlm-member-portal-body input.pr-10 {
			padding-right: 2.5rem !important;
		}

		/* Protect specific functional button layouts */
		body.dlm-member-portal-body #notification-btn {
			background-color: transparent !important;
			border-radius: 9999px !important;
			width: 2.5rem !important;
			height: 2.5rem !important;
			display: flex !important;
		}
		body.dlm-member-portal-body button.rounded-full {
			border-radius: 9999px !important;
		}
		body.dlm-member-portal-body button.rounded-full.border {
			border: 1px solid rgba(134, 116, 97, 0.3) !important;
			width: 2.5rem !important;
			height: 2.5rem !important;
			background-color: transparent !important;
			display: flex !important;
		}
	</style>
</head>
<body class="font-body-md text-body-md bg-background text-on-background selection:bg-primary/20 dlm-member-portal-body">

<?php if ( ! $is_logged_in ) : ?>
	<!-- ========================================== -->
	<!-- LOGOUT AUTH PANELS (Login & Register Tabs) -->
	<!-- ========================================== -->
	<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-background">
		<!-- Ambient Background Elements (From login.html) -->
		<div class="absolute inset-0 z-0 pointer-events-none">
			<div class="absolute top-0 right-0 w-[60%] h-full opacity-40 mix-blend-multiply bg-cover bg-right" style="background: radial-gradient(circle at top right, rgba(133, 83, 0, 0.18), transparent 70%);"></div>
			<div class="absolute inset-0 bg-gradient-to-r from-background via-background/90 to-transparent"></div>
		</div>

		<main class="relative z-10 w-full max-w-[480px] py-8">
			<?php
			$dlm_public = new DLM_Public( $dlm_db, new DLM_Checkout() );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped HTML auth template.
			echo $dlm_public->get_login_prompt_html();
			?>
		</main>
	</div>
<?php else : ?>
	<!-- ========================================== -->
	<!-- MAIN SPA MEMBER DASHBOARD LAYOUT (Logged In) -->
	<!-- ========================================== -->
	<div class="dlm-portal-root bg-background">
		<div class="w-full max-w-[2560px] flex h-full overflow-hidden relative">

	<!-- Sidebar Menu (Desktop View) -->
	<aside class="h-full w-[260px] lg:w-[280px] flex-shrink-0 glass-sidebar border-r border-outline-variant/20 flex flex-col p-4 lg:p-6 gap-2 z-50 hidden md:flex transition-all duration-300 overflow-hidden">
		<!-- Brand & Logo -->
		<div class="sidebar-brand-wrapper mb-4 lg:mb-6 px-2 flex items-center gap-3 relative w-full flex-shrink-0">
			<div class="w-9 h-9 bg-primary rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm shadow-primary/20">
				<i class="fa-solid fa-book-open text-white text-[16px]"></i>
			</div>
			<div class="sidebar-brand-text">
				<span class="font-display-lg text-[19px] lg:text-[20px] text-primary font-bold tracking-tight block leading-tight">Bridgeway36</span>
				<p class="text-secondary text-[10px] font-semibold uppercase tracking-widest mt-0.5">Digital Library</p>
			</div>
		</div>

		<!-- Scrollable Nav Links List -->
		<nav class="flex-1 space-y-1 overflow-y-auto custom-scrollbar min-h-0 pr-1 -mr-1">
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 bg-primary/10 text-primary rounded-xl font-semibold scale-[0.98] transition-all cursor-pointer" data-tab="library" onclick="showTab('library')">
				<i class="fa-solid fa-book text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Library</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-xl transition-all cursor-pointer" data-tab="discover" onclick="showTab('discover')">
				<i class="fa-solid fa-compass text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Discover</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-xl transition-all cursor-pointer" data-tab="journal" onclick="showTab('journal')">
				<i class="fa-solid fa-pen-to-square text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Reading Journal</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-xl transition-all cursor-pointer" data-tab="collections" onclick="showTab('collections')">
				<i class="fa-solid fa-bookmark text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Collections</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-xl transition-all cursor-pointer" data-tab="membership" onclick="showTab('membership')">
				<i class="fa-solid fa-crown text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Membership</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-xl transition-all cursor-pointer" data-tab="achievements" onclick="showTab('achievements')">
				<i class="fa-solid fa-trophy text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Achievements</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2 lg:py-2.5 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-xl transition-all cursor-pointer" data-tab="settings" onclick="showTab('settings')">
				<i class="fa-solid fa-gear text-[17px] flex-shrink-0"></i>
				<span class="font-title-sm text-sm lg:text-title-sm sidebar-nav-text">Settings</span>
			</a>
		</nav>

		<!-- Bottom CTA & Actions (Pinned at bottom, always visible) -->
		<div class="mt-auto space-y-2.5 pt-2 flex-shrink-0">
			<?php 
			$is_pending = ( $sub_details && $sub_details->status === 'pending_approval' );
			if ( ! $has_active_sub && ! $is_pending ) : 
			?>
				<div class="bg-primary-container/15 border border-primary-container/30 p-3 rounded-xl text-on-primary-container sidebar-cta-card">
					<div class="flex items-center justify-between mb-1">
						<p class="font-bold text-xs text-primary">Upgrade to Pro</p>
						<span class="text-[10px] bg-primary text-white font-bold px-1.5 py-0.5 rounded">PRO</span>
					</div>
					<p class="text-[11px] text-secondary leading-tight mb-2">Access all premium digital books.</p>
					<button onclick="showTab('membership')" class="block text-center w-full py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:opacity-90 transition-opacity">Unlock All</button>
				</div>
			<?php elseif ( $is_pending ) : ?>
				<div class="bg-amber-50 border border-amber-200/50 p-2.5 rounded-xl text-amber-800 sidebar-cta-card">
					<p class="font-bold text-xs text-amber-900 mb-0.5">Pending Approval</p>
					<p class="text-[11px] text-secondary leading-tight mb-1.5">Under administrator review.</p>
					<button onclick="showTab('membership')" class="block text-center w-full py-1 bg-amber-100 text-amber-900 text-xs font-bold rounded-lg hover:bg-amber-200 transition-colors">Check Status</button>
				</div>
			<?php endif; ?>
			<div class="pt-2 border-t border-outline-variant/30 sidebar-footer-links flex flex-col gap-1">
				<a class="flex items-center gap-3 px-3.5 py-2 text-secondary hover:text-on-surface hover:bg-surface-container/60 rounded-xl text-body-md transition-all cursor-pointer" onclick="Aurelian.toast('Concierge support active hello@bridgeway36.com'); return false;">
					<i class="fa-regular fa-circle-question text-[17px] flex-shrink-0"></i>
					<span>Help</span>
				</a>
				<a class="flex items-center gap-3 px-3.5 py-2 text-red-700 hover:text-red-800 hover:bg-red-50/80 rounded-xl text-body-md font-semibold transition-all cursor-pointer group" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" title="Sign Out">
					<i class="fa-solid fa-right-from-bracket text-[17px] flex-shrink-0 text-red-600 group-hover:scale-110 transition-transform"></i>
					<span>Sign Out</span>
				</a>
			</div>
		</div>
	</aside>

	<!-- ========================================== -->
	<!-- MOBILE LEFT SIDEBAR DRAWER & BACKDROP      -->
	<!-- ========================================== -->
	<div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9998] transition-opacity duration-300 opacity-0 pointer-events-none md:hidden"></div>

	<aside id="mobile-sidebar-drawer" class="fixed top-0 left-0 bottom-0 w-[300px] max-w-[85vw] bg-white border-r border-outline-variant/30 z-[9999] shadow-2xl flex flex-col p-5 gap-3 transition-transform duration-300 ease-in-out -translate-x-full md:hidden overflow-y-auto custom-scrollbar">
		<!-- Brand Header + Close Button -->
		<div class="flex items-center justify-between pb-3 border-b border-outline-variant/20">
			<div class="flex items-center gap-2.5">
				<div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm shadow-primary/20">
					<i class="fa-solid fa-book-open text-white text-[14px]"></i>
				</div>
				<div>
					<span class="font-display-lg text-[17px] text-primary font-bold tracking-tight block leading-tight">Bridgeway36</span>
					<p class="text-secondary text-[9px] font-bold uppercase tracking-widest">Digital Library</p>
				</div>
			</div>
			<button id="mobile-drawer-close-btn" class="w-8 h-8 rounded-full bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-secondary hover:text-on-surface transition-all cursor-pointer" aria-label="Close Navigation">
				<i class="fa-solid fa-xmark text-sm"></i>
			</button>
		</div>

		<!-- User Profile Summary in Drawer -->
		<div class="flex items-center gap-3 p-3 bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-sm">
			<div class="w-10 h-10 rounded-full overflow-hidden border border-primary/20 flex-shrink-0">
				<img class="w-full h-full object-cover" src="<?php echo esc_url( $avatar_url ); ?>">
			</div>
			<div class="min-w-0 flex-1">
				<p class="font-bold text-xs text-on-surface truncate"><?php echo esc_html( $user_display_name ?: 'Member' ); ?></p>
				<span class="inline-flex items-center gap-1 text-[10px] font-bold <?php echo $has_active_sub ? 'text-amber-700' : 'text-secondary'; ?>">
					<i class="fa-solid <?php echo $has_active_sub ? 'fa-crown text-amber-600' : 'fa-user'; ?>"></i>
					<?php echo $has_active_sub ? 'PRO Member' : ( $is_pending ? 'Pending Approval' : 'Free Reader' ); ?>
				</span>
			</div>
		</div>

		<!-- Full Nav Links in Drawer -->
		<nav class="flex-1 space-y-1 overflow-y-auto custom-scrollbar min-h-0 py-2">
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 bg-primary/10 text-primary rounded-xl font-semibold transition-all cursor-pointer mobile-drawer-link" data-tab="library" onclick="showTab('library')">
				<i class="fa-solid fa-book text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Library</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 text-secondary hover:bg-surface-container hover:text-on-surface rounded-xl transition-all cursor-pointer mobile-drawer-link" data-tab="discover" onclick="showTab('discover')">
				<i class="fa-solid fa-compass text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Discover</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 text-secondary hover:bg-surface-container hover:text-on-surface rounded-xl transition-all cursor-pointer mobile-drawer-link" data-tab="journal" onclick="showTab('journal')">
				<i class="fa-solid fa-pen-to-square text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Reading Journal</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 text-secondary hover:bg-surface-container hover:text-on-surface rounded-xl transition-all cursor-pointer mobile-drawer-link" data-tab="collections" onclick="showTab('collections')">
				<i class="fa-solid fa-bookmark text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Collections</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 text-secondary hover:bg-surface-container hover:text-on-surface rounded-xl transition-all cursor-pointer mobile-drawer-link" data-tab="membership" onclick="showTab('membership')">
				<i class="fa-solid fa-crown text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Membership</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 text-secondary hover:bg-surface-container hover:text-on-surface rounded-xl transition-all cursor-pointer mobile-drawer-link" data-tab="achievements" onclick="showTab('achievements')">
				<i class="fa-solid fa-trophy text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Achievements</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-3.5 py-2.5 text-secondary hover:bg-surface-container hover:text-on-surface rounded-xl transition-all cursor-pointer mobile-drawer-link" data-tab="settings" onclick="showTab('settings')">
				<i class="fa-solid fa-gear text-[17px] flex-shrink-0"></i>
				<span class="text-sm font-semibold">Settings</span>
			</a>
		</nav>

		<!-- Bottom Drawer Actions -->
		<div class="mt-auto pt-3 border-t border-outline-variant/30 space-y-2 flex-shrink-0">
			<?php if ( ! $has_active_sub && ! $is_pending ) : ?>
				<div class="bg-primary-container/15 border border-primary-container/30 p-3 rounded-xl text-on-primary-container">
					<div class="flex items-center justify-between mb-1">
						<p class="font-bold text-xs text-primary">Upgrade to Pro</p>
						<span class="text-[10px] bg-primary text-white font-bold px-1.5 py-0.5 rounded">PRO</span>
					</div>
					<p class="text-[11px] text-secondary leading-tight mb-2">Access all premium digital books.</p>
					<button onclick="showTab('membership')" class="block text-center w-full py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:opacity-90 transition-opacity">Unlock All</button>
				</div>
			<?php endif; ?>
			<a class="flex items-center gap-3 px-3.5 py-2 text-secondary hover:text-on-surface hover:bg-surface-container/60 rounded-xl text-sm transition-all cursor-pointer" onclick="Aurelian.toast('Concierge support active hello@bridgeway36.com'); return false;">
				<i class="fa-regular fa-circle-question text-[17px] flex-shrink-0"></i>
				<span>Help & Support</span>
			</a>
			<a class="flex items-center gap-3 px-3.5 py-2 text-red-700 hover:text-red-800 hover:bg-red-50/80 rounded-xl text-sm font-semibold transition-all cursor-pointer group" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" title="Sign Out">
				<i class="fa-solid fa-right-from-bracket text-[17px] flex-shrink-0 text-red-600 group-hover:scale-110 transition-transform"></i>
				<span>Sign Out</span>
			</a>
		</div>
	</aside>

	<div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden">
		<!-- Top App Bar Navigation Header (Responsive on Mobile & Desktop) -->
		<header class="flex-shrink-0 z-40 bg-surface/90 backdrop-blur-xl border-b border-outline-variant/30 h-16 px-4 md:px-margin-desktop flex items-center justify-between transition-all duration-300">
			<div class="flex items-center gap-3">
				<!-- Universal Navigation Menu / Sidebar Toggle Button (All Devices) -->
				<button id="mobile-menu-trigger-btn" class="flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-outline-variant/30 shadow-sm text-primary hover:bg-surface-container-high/60 active:scale-95 transition-all cursor-pointer flex-shrink-0" aria-label="Toggle Navigation Menu">
					<i class="fa-solid fa-bars-staggered text-[17px]"></i>
				</button>

				<div class="flex items-center gap-2">
					<div class="w-8 h-8 bg-primary rounded-lg flex md:hidden items-center justify-center flex-shrink-0 shadow-sm shadow-primary/20">
						<i class="fa-solid fa-book-open text-white text-[14px]"></i>
					</div>
					<h1 class="font-headline-md text-[18px] md:text-headline-md text-primary tracking-tight font-bold" id="top-bar-title">Library</h1>
				</div>
			</div>
			<div class="flex items-center gap-2.5 md:gap-6">
				<!-- Header Search Bar (Desktop & Mobile) -->
				<div class="relative group" id="header-search-container">
					<i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-secondary group-focus-within:text-primary transition-colors text-xs md:text-sm"></i>
					<input class="pl-8 pr-3 md:pl-10 md:pr-4 py-1.5 md:py-2 bg-surface-container-lowest border border-outline-variant/30 rounded-xl w-28 sm:w-44 md:w-64 focus:w-36 sm:focus:w-56 md:focus:w-64 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-xs md:text-body-md" id="global-search-input" placeholder="Search..." type="text" aria-label="Search library books">
				</div>

				<!-- Streak Counter Nudge Badge -->
				<div id="header-streak-badge" class="flex items-center gap-1.5 pl-2.5 pr-3 md:pl-3 md:pr-4 py-1 md:py-1.5 bg-primary-container/20 border border-primary-container/40 rounded-full hover:bg-primary-container/30 cursor-pointer transition-colors" title="Your reading streak" onclick="showTab('achievements')">
					<i class="fa-solid fa-fire text-primary text-[15px] md:text-[18px]"></i>
					<span id="streak-count-header" class="font-label-caps text-[11px] md:text-label-caps text-on-primary-container font-bold">0d</span>
				</div>

				<!-- Notifications Menu Dropdown -->
				<div class="relative" id="notification-bell-wrapper">
					<?php
					$notifs_list = ! empty( $db_notifications ) ? $db_notifications : array();
					$unread_total = isset( $unread_notif_count ) ? intval( $unread_notif_count ) : 0;
					/* translators: %d: number of unread notifications */
					$notif_btn_label = $unread_total > 0 ? sprintf( __( 'Notifications (%d unread)', 'digital-library-membership' ), $unread_total ) : __( 'Notifications (No unread)', 'digital-library-membership' );
					?>
					<button id="notification-btn" class="p-2 text-secondary hover:bg-primary/5 rounded-full transition-colors relative cursor-pointer" type="button" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo esc_attr( $notif_btn_label ); ?>">
						<i class="fa-regular fa-bell text-[18px] md:text-[20px]"></i>
						<span id="notification-badge" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-surface shadow-sm<?php if ( $unread_total === 0 ) echo ' hidden'; ?>"><?php echo intval( $unread_total ); ?></span>
					</button>

					<div id="notification-dropdown" class="absolute right-0 top-12 w-80 md:w-96 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-2xl py-3 px-2 hidden z-50 transition-all" role="region" aria-label="<?php esc_attr_e( 'Notifications panel', 'digital-library-membership' ); ?>">
						<div class="px-3 pb-2.5 border-b border-outline-variant/20 flex justify-between items-center">
							<div class="flex items-center gap-2">
								<h4 class="font-bold text-on-surface text-sm"><?php esc_html_e( 'Notifications', 'digital-library-membership' ); ?></h4>
								<?php
								/* translators: %d: count of new notifications */
								$new_notifs_str = sprintf( __( '%d new', 'digital-library-membership' ), $unread_total );
								?>
								<span id="notification-unread-pill" class="text-[10px] font-bold bg-primary/10 text-primary px-2 py-0.5 rounded-full<?php if ( $unread_total === 0 ) echo ' hidden'; ?>"><?php echo esc_html( $new_notifs_str ); ?></span>
							</div>
							<button type="button" class="text-xs font-bold text-primary hover:underline cursor-pointer transition-colors p-1" id="mark-all-read-btn"><?php esc_html_e( 'Mark all read', 'digital-library-membership' ); ?></button>
						</div>
						<div class="max-h-80 overflow-y-auto mt-2 space-y-1.5 custom-scrollbar px-1" id="notification-list">
							<?php if ( empty( $notifs_list ) ) : ?>
								<div class="p-8 text-center text-secondary opacity-60" id="notification-empty-state">
									<i class="fa-regular fa-bell text-2xl mb-2 block"></i>
									<p class="text-xs font-semibold"><?php esc_html_e( 'No notifications yet', 'digital-library-membership' ); ?></p>
								</div>
							<?php else : ?>
								<?php foreach ( $notifs_list as $notif ) : 
									$is_notif_read = ! empty( $notif->is_read );
									$time_diff = human_time_diff( strtotime( $notif->created_at ), current_time( 'timestamp' ) );

									$icon_class = 'fa-bell';
									$icon_color = 'bg-primary/10 text-primary';
									if ( $notif->type === 'badge' ) {
										$icon_class = 'fa-trophy';
										$icon_color = 'bg-amber-100 text-amber-700';
									} elseif ( $notif->type === 'level_up' ) {
										$icon_class = 'fa-arrow-up-right-dots';
										$icon_color = 'bg-blue-100 text-blue-700';
									} elseif ( $notif->type === 'streak' ) {
										$icon_class = 'fa-fire';
										$icon_color = 'bg-orange-100 text-orange-600';
									} elseif ( $notif->type === 'purchase' ) {
										$icon_class = 'fa-bag-shopping';
										$icon_color = 'bg-emerald-100 text-emerald-700';
									} elseif ( $notif->type === 'subscription' ) {
										$icon_class = 'fa-crown';
										$icon_color = 'bg-amber-100 text-amber-800';
									} elseif ( $notif->type === 'featured_book' ) {
										$icon_class = 'fa-star';
										$icon_color = 'bg-purple-100 text-purple-700';
									}
								?>
									<div class="dlm-notif-row p-3 rounded-xl transition-all cursor-pointer flex items-start gap-3 relative <?php echo $is_notif_read ? 'bg-transparent hover:bg-surface-variant/30 text-on-surface-variant' : 'bg-primary-container/10 border border-primary/20 hover:bg-primary-container/20 text-on-surface font-semibold shadow-xs'; ?>" data-id="<?php echo intval( $notif->id ); ?>" data-link="<?php echo esc_attr( $notif->link_url ); ?>" data-is-read="<?php echo $is_notif_read ? '1' : '0'; ?>" role="button" tabindex="0">
										<div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-sm <?php echo esc_attr( $icon_color ); ?>">
											<i class="fa-solid <?php echo esc_attr( $icon_class ); ?>"></i>
										</div>
										<div class="flex-1 min-w-0">
											<div class="flex items-center justify-between gap-1 mb-0.5">
												<p class="text-xs font-bold text-on-surface truncate leading-tight"><?php echo esc_html( $notif->title ); ?></p>
												<?php
												/* translators: %s: relative time elapsed */
												$time_ago_str = sprintf( __( '%s ago', 'digital-library-membership' ), $time_diff );
												?>
												<span class="text-[10px] text-secondary shrink-0 opacity-80"><?php echo esc_html( $time_ago_str ); ?></span>
											</div>
											<p class="text-[11px] text-secondary leading-snug line-clamp-2"><?php echo esc_html( $notif->message ); ?></p>
										</div>
										<?php if ( ! $is_notif_read ) : ?>
											<span class="w-2 h-2 rounded-full bg-primary shrink-0 mt-1.5 notif-unread-dot"></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Quick Settings Nav -->
				<div class="h-6 md:h-8 w-px bg-outline-variant/30"></div>

				<div class="flex items-center gap-2">
					<a href="#" class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant/50 block hover:scale-105 transition-all flex-shrink-0" onclick="showTab('settings'); return false;">
						<img class="w-full h-full object-cover" id="header-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>">
					</a>
				</div>
			</div>
		</header>

		<!-- ========================================== -->
		<!-- SPA PAGE SECTION VIEWS CONTAINER           -->
		<!-- ========================================== -->
		<main id="dlm-main-content" class="flex-1 overflow-y-auto pt-4 md:pt-8 pb-28 md:pb-20 px-4 md:px-margin-desktop custom-scrollbar">

		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$payment_status = isset( $_GET['payment'] ) ? sanitize_key( wp_unslash( $_GET['payment'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$completed_order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		if ( 'success' === $payment_status ) : ?>
			<!-- Payment Success Celebration Modal -->
			<div id="dlm-payment-success-modal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fadeIn">
				<div class="relative w-full max-w-md bg-white rounded-3xl p-8 text-center shadow-2xl border border-primary/20 space-y-6 overflow-hidden">
					<div class="absolute -top-16 -right-16 w-36 h-36 rounded-full bg-primary/10 blur-2xl pointer-events-none"></div>
					<div class="absolute -bottom-16 -left-16 w-36 h-36 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>
					
					<div class="w-20 h-20 mx-auto rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-4xl shadow-inner border border-emerald-100">
						<i class="fa-solid fa-circle-check"></i>
					</div>
					
					<div class="space-y-2">
						<span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full uppercase tracking-wider">
							<?php esc_html_e( 'Payment Verified & Completed', 'digital-library-membership' ); ?>
						</span>
						<h3 class="text-2xl font-bold text-on-surface">
							<?php esc_html_e( 'Welcome to the Library!', 'digital-library-membership' ); ?>
						</h3>
						<p class="text-sm text-secondary leading-relaxed">
							<?php esc_html_e( 'Your transaction was successful and your access has been unlocked instantly.', 'digital-library-membership' ); ?>
						</p>
						<?php 
						if ( $completed_order_id > 0 ) : 
							$legit_order = function_exists( 'wc_get_order' ) ? wc_get_order( $completed_order_id ) : null;
							if ( $legit_order && $legit_order->get_customer_id() === get_current_user_id() ) :
						?>
							<p class="text-xs text-secondary/80 font-mono pt-1">
								<?php
								/* translators: %d: Completed order reference ID */
								echo esc_html( sprintf( __( 'Order Reference: #%d', 'digital-library-membership' ), $completed_order_id ) );
								?>
							</p>
						<?php 
							endif;
						endif; 
						?>
					</div>

					<div class="pt-2 flex flex-col sm:flex-row gap-3">
						<button onclick="jQuery('#dlm-payment-success-modal').fadeOut(); showTab('library');" class="flex-1 h-12 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-lg shadow-primary/20 cursor-pointer">
							<i class="fa-solid fa-book-open"></i>
							<span><?php esc_html_e( 'Start Reading Now', 'digital-library-membership' ); ?></span>
						</button>
						<button onclick="jQuery('#dlm-payment-success-modal').fadeOut(); showTab('bookshelf');" class="h-12 px-5 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold rounded-xl active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
							<span><?php esc_html_e( 'My Bookshelf', 'digital-library-membership' ); ?></span>
						</button>
					</div>
				</div>
			</div>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					if (window.history && window.history.replaceState) {
						var cleanUrl = window.location.pathname + window.location.hash;
						window.history.replaceState({}, document.title, cleanUrl);
					}
				});
			</script>
		<?php elseif ( 'cancelled' === $payment_status ) : ?>
			<div id="dlm-payment-cancelled-banner" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between gap-4">
				<div class="flex items-center gap-3">
					<div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">
						<i class="fa-solid fa-circle-info"></i>
					</div>
					<div>
						<h4 class="font-bold text-sm text-amber-900"><?php esc_html_e( 'Payment was not completed', 'digital-library-membership' ); ?></h4>
						<p class="text-xs text-amber-700"><?php esc_html_e( 'You can return to checkout and select any other payment method whenever you are ready.', 'digital-library-membership' ); ?></p>
					</div>
				</div>
				<button onclick="jQuery('#dlm-payment-cancelled-banner').fadeOut();" class="text-amber-700 hover:text-amber-900 p-2 text-sm"><i class="fa-solid fa-xmark"></i></button>
			</div>
		<?php endif; ?>

		<!-- SECTION 1: LIBRARY VIEW -->
		<div id="section-library" class="spa-page">
			<!-- Hero Card Carousel / Featured Book Slider -->
			<?php 
			$featured_count = ! empty( $featured_books ) ? count( $featured_books ) : 0;
			?>
			<section class="mb-8 md:mb-12 relative min-h-[380px] md:h-[430px] rounded-2xl md:rounded-3xl overflow-hidden group shadow-2xl bg-surface-container" id="member-hero-slider-section">
				<div class="relative w-full h-full min-h-[380px] md:h-[430px] overflow-hidden" id="hero-slider-track">
					<?php if ( ! empty( $featured_books ) ) : ?>
						<?php foreach ( $featured_books as $f_idx => $fb ) : 
							$f_title = ! empty( $fb->featured_title ) ? $fb->featured_title : $fb->title;
							$f_desc  = ! empty( $fb->featured_description ) ? $fb->featured_description : ( ! empty( $fb->description ) ? wp_strip_all_tags( $fb->description ) : '' );
							$f_banner = ! empty( $fb->featured_banner_url ) ? $fb->featured_banner_url : ( ! empty( $fb->cover_image_url ) ? $fb->cover_image_url : DLM_URL . 'public/images/featured_hero.png' );
							$f_cover = ! empty( $fb->cover_image_url ) ? $fb->cover_image_url : '';
							
							$f_access = dlm_user_can_access_book( $user_id, $fb->id );
							$f_price = isset( $fb->price ) ? floatval( $fb->price ) : 0.00;
							$f_is_future = ( ! empty( $fb->publish_date ) && strtotime( $fb->publish_date ) > current_time( 'timestamp' ) ) || ( isset( $fb->status ) && $fb->status === 'future' );
							$f_publish_iso = ! empty( $fb->publish_date ) ? wp_date( 'c', strtotime( $fb->publish_date ) ) : '';
							if ( empty( $f_publish_iso ) && ! empty( $fb->publish_date ) ) {
								$f_publish_iso = gmdate( 'c', strtotime( $fb->publish_date ) );
							}
							if ( empty( $f_publish_iso ) && ! empty( $fb->publish_date ) ) {
								$f_publish_iso = str_replace( ' ', 'T', trim( $fb->publish_date ) );
							}
							$f_publish_fmt = ! empty( $fb->publish_date ) ? date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $fb->publish_date ) ) : '';
							
							$btn1_label = ! empty( $fb->featured_button_1_label ) ? $fb->featured_button_1_label : '';
							$btn2_label = ! empty( $fb->featured_button_2_label ) ? $fb->featured_button_2_label : '';
							$is_f_fav = in_array( $fb->id, $fav_books );
						?>
							<div class="hero-slide absolute inset-0 w-full h-full transition-all duration-700 ease-in-out <?php echo $f_idx === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'; ?>" data-slide-index="<?php echo intval( $f_idx ); ?>" data-book-id="<?php echo intval( $fb->id ); ?>">
								<!-- Background Image & Gradient Overlays -->
								<div class="absolute inset-0">
									<img class="w-full h-full object-cover transform scale-100 group-hover:scale-105 transition-transform duration-1000" src="<?php echo esc_url( $f_banner ); ?>" alt="<?php echo esc_attr( $f_title ); ?>" loading="lazy">
									<div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/60 to-black/30 md:bg-gradient-to-r md:from-black/95 md:via-black/75 md:to-black/30"></div>
								</div>

								<!-- Slide Inner Content -->
								<div class="relative z-10 w-full h-full flex flex-col md:flex-row items-center justify-between p-6 sm:p-8 md:p-12 gap-6 text-white">
									<!-- Left Text & CTAs -->
									<div class="w-full md:max-w-xl lg:max-w-2xl flex flex-col justify-end md:justify-center h-full">
										<div class="flex items-center gap-2.5 mb-2.5 md:mb-4 flex-wrap">
											<span class="bg-primary text-white font-extrabold text-[10px] md:text-xs px-3.5 py-1 rounded-full uppercase tracking-wider shadow-md flex items-center gap-1.5">
												<i class="fa-solid fa-star text-[10px] text-amber-300"></i>
												<?php esc_html_e( 'Featured Book', 'digital-library-membership' ); ?>
											</span>
											<?php if ( $f_is_future ) : ?>
												<span class="bg-amber-600/90 backdrop-blur-md text-white font-extrabold text-[10px] md:text-xs px-3 py-1 rounded-full uppercase tracking-wider shadow-md flex items-center gap-1">
													<i class="fa-solid fa-clock text-[9px]"></i> <?php esc_html_e( 'Upcoming Release', 'digital-library-membership' ); ?>
												</span>
											<?php endif; ?>
										</div>

										<h2 class="font-display-lg text-2xl sm:text-3xl md:text-4xl lg:text-[40px] leading-tight mb-2 md:mb-3 font-bold text-white tracking-tight drop-shadow-md">
											<?php echo esc_html( $f_title ); ?>
										</h2>

										<?php if ( ! empty( $fb->author ) ) : ?>
											<p class="text-xs md:text-sm text-amber-200/90 font-medium mb-3 flex items-center gap-1.5">
												<i class="fa-solid fa-feather text-[10px]"></i> by <?php echo esc_html( $fb->author ); ?>
											</p>
										<?php endif; ?>

										<p class="font-body-lg text-xs md:text-sm text-white/85 mb-5 md:mb-7 line-clamp-3 md:line-clamp-3 max-w-xl leading-relaxed">
											<?php echo esc_html( $f_desc ); ?>
										</p>

										<!-- Action Buttons & Countdown -->
										<div class="flex flex-wrap items-center gap-3 md:gap-4 hero-cta-wrapper" data-book-id="<?php echo intval( $fb->id ); ?>">
											<?php if ( $f_is_future ) : ?>
												<!-- Scheduled Release Countdown + Coming Soon Badge -->
												<div class="flex flex-wrap items-center gap-3">
													<div class="grid grid-cols-4 gap-1 p-1.5 rounded-xl shadow-xl text-white dlm-countdown-timer shrink-0" style="background: linear-gradient(135deg, rgba(133, 83, 0, 0.92), rgba(97, 59, 0, 0.95)) !important; border: 1px solid rgba(255, 255, 255, 0.28) !important; backdrop-filter: blur(8px);" data-release-time="<?php echo esc_attr( $f_publish_iso ); ?>" data-book-id="<?php echo esc_attr( $fb->id ); ?>">
														<div class="flex flex-col items-center justify-center rounded-lg py-1 px-1.5 text-center shadow-xs min-w-[36px]" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
															<span class="countdown-days font-mono font-extrabold text-sm leading-tight text-white">00</span>
															<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Day</span>
														</div>
														<div class="flex flex-col items-center justify-center rounded-lg py-1 px-1.5 text-center shadow-xs min-w-[36px]" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
															<span class="countdown-hours font-mono font-extrabold text-sm leading-tight text-white">00</span>
															<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Hr</span>
														</div>
														<div class="flex flex-col items-center justify-center rounded-lg py-1 px-1.5 text-center shadow-xs min-w-[36px]" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
															<span class="countdown-minutes font-mono font-extrabold text-sm leading-tight text-white">00</span>
															<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Min</span>
														</div>
														<div class="flex flex-col items-center justify-center rounded-lg py-1 px-1.5 text-center shadow-xs min-w-[36px]" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
															<span class="countdown-seconds font-mono font-extrabold text-sm leading-tight text-white">00</span>
															<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Sec</span>
														</div>
													</div>
													<span class="px-4 py-2.5 bg-white/20 backdrop-blur-md text-white font-bold rounded-xl text-xs flex items-center gap-1.5 border border-white/20">
														<i class="fa-solid fa-bell text-amber-300"></i>
														<?php
														/* translators: %s: scheduled book release date */
														$release_str = sprintf( __( 'Releases %s', 'digital-library-membership' ), $f_publish_fmt );
														echo esc_html( $btn1_label ?: $release_str );
														?>
													</span>
												</div>
											<?php else : ?>
												<?php if ( $f_access === 'read_download' || $f_access === 'read_only' ) : ?>
													<button onclick="Aurelian.openBook(<?php echo intval($fb->id); ?>, '<?php echo esc_js($fb->title); ?>')" class="px-6 md:px-8 py-3 bg-white text-black font-extrabold rounded-xl hover:bg-slate-100 hover:scale-105 transition-all text-xs md:text-sm shadow-xl flex items-center gap-2">
														<i class="fa-solid fa-book-open text-primary"></i>
														<?php echo esc_html( $btn1_label ?: 'Read Now' ); ?>
													</button>
												<?php elseif ( $fb->access_type === 'purchase_only' || $fb->access_type === 'hybrid' ) : ?>
													<button onclick="Aurelian.buyBook(<?php echo intval($fb->id); ?>, '<?php echo esc_js($fb->title); ?>', <?php echo floatval($f_price); ?>)" class="px-6 md:px-8 py-3 bg-amber-500 hover:bg-amber-400 text-black font-extrabold rounded-xl hover:scale-105 transition-all text-xs md:text-sm shadow-xl flex items-center gap-2">
														<i class="fa-solid fa-cart-shopping"></i>
														<?php echo esc_html( $btn1_label ?: sprintf( 'Buy Book (%s %s)', number_format($f_price, 2), $currency ) ); ?>
													</button>
												<?php else : ?>
													<button onclick="showTab('membership')" class="px-6 md:px-8 py-3 bg-primary text-white font-extrabold rounded-xl hover:scale-105 transition-all text-xs md:text-sm shadow-xl flex items-center gap-2">
														<i class="fa-solid fa-id-card"></i>
														<?php echo esc_html( $btn1_label ?: 'Unlock Membership' ); ?>
													</button>
												<?php endif; ?>
											<?php endif; ?>

											<button class="px-5 md:px-6 py-3 bg-white/15 hover:bg-white/25 backdrop-blur-md text-white border border-white/25 font-bold rounded-xl transition-all text-xs md:text-sm flex items-center gap-2" onclick="toggleFavoriteBook(<?php echo intval($fb->id); ?>, this)">
												<i class="<?php echo $is_f_fav ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark text-amber-300"></i>
												<span><?php echo esc_html( $btn2_label ?: ( $is_f_fav ? 'Saved' : 'Add to Favorites' ) ); ?></span>
											</button>
										</div>
									</div>

									<!-- Right Floating Book Cover (Desktop & Tablet) -->
									<?php if ( $f_cover ) : ?>
										<div class="hidden md:flex items-center justify-center shrink-0 pr-4">
											<div class="w-36 lg:w-48 aspect-[3/4] rounded-2xl overflow-hidden shadow-2xl border-2 border-white/20 transform rotate-2 group-hover:rotate-0 group-hover:scale-105 transition-all duration-500 bg-slate-900">
												<img class="w-full h-full object-cover" src="<?php echo esc_url( $f_cover ); ?>" alt="<?php echo esc_attr( $f_title ); ?>" loading="lazy">
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<!-- Slider Navigation Controls (Only if count > 1) -->
				<?php if ( $featured_count > 1 ) : ?>
					<!-- Prev / Next Arrow Buttons -->
					<button id="hero-slider-prev" class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/40 hover:bg-black/80 backdrop-blur-md border border-white/20 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all focus:outline-none" aria-label="Previous Slide">
						<i class="fa-solid fa-chevron-left text-sm"></i>
					</button>
					<button id="hero-slider-next" class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/40 hover:bg-black/80 backdrop-blur-md border border-white/20 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all focus:outline-none" aria-label="Next Slide">
						<i class="fa-solid fa-chevron-right text-sm"></i>
					</button>

					<!-- Bottom Dots Pagination -->
					<div class="absolute bottom-4 right-6 z-20 flex items-center gap-2" id="hero-slider-dots">
						<?php for ( $i = 0; $i < $featured_count; $i++ ) : ?>
							<button class="hero-dot w-2.5 h-2.5 rounded-full transition-all duration-300 <?php echo $i === 0 ? 'w-7 bg-white' : 'bg-white/50 hover:bg-white/80'; ?>" data-dot-index="<?php echo intval( $i ); ?>" aria-label="Go to slide <?php echo intval( $i + 1 ); ?>"></button>
						<?php endfor; ?>
					</div>
				<?php endif; ?>
			</section>

			<!-- Category Chips Section -->
			<section class="mb-8 md:mb-10 flex items-center gap-2 md:gap-3 overflow-x-auto hide-scrollbar pb-2" id="category-chips">
				<button class="px-5 md:px-6 py-2 md:py-2.5 bg-primary text-white rounded-full font-bold text-xs md:text-body-md whitespace-nowrap active-chip" data-category="all" onclick="filterCategory('all', this)">All Library</button>
				<?php foreach ( $categories_terms as $term ) : ?>
					<button class="px-5 md:px-6 py-2 md:py-2.5 bg-surface-container-high/50 text-secondary hover:bg-primary-container hover:text-on-primary-container rounded-full font-semibold text-xs md:text-body-md whitespace-nowrap transition-colors" data-category="<?php echo esc_attr( $term->slug ); ?>" onclick="filterCategory('<?php echo esc_attr( $term->slug ); ?>', this)"><?php echo esc_html( $term->name ); ?></button>
				<?php endforeach; ?>
			</section>

			<!-- Continue Reading shelf -->
			<section class="mb-14" id="continue-reading-shelf">
				<div class="flex items-center justify-between mb-6">
					<h3 class="font-headline-md text-headline-md text-on-surface font-bold">Continue Reading</h3>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter" id="continue-reading-grid">
					<!-- Populated by JS -->
				</div>
			</section>

			<!-- Dynamic Library books grid -->
			<section>
				<div class="flex items-center justify-between mb-8">
					<div class="flex items-end gap-3">
						<h3 class="font-headline-md text-headline-md text-on-surface font-bold">Explore Library</h3>
						<span class="text-secondary text-body-md pb-1" id="book-count-text"><?php echo count($books); ?> titles available</span>
					</div>
				</div>

				<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-gutter gap-y-12" id="books-grid">
					<?php foreach ( $books as $book ) : ?>
						<?php 
						$progress = $dlm_db->get_reading_progress( $user_id, $book->id );
						$pct = $progress ? intval( $progress->progress_percent ) : 0;
						
						// Access calculation
						$user_access = dlm_user_can_access_book( $user_id, $book->id );
						$access_type = ! empty( $book->access_type ) ? $book->access_type : 'subscription_only';
						$price       = isset( $book->price ) ? floatval( $book->price ) : 0.00;

						// Get categories for filtering (rolling up child terms to parent category slugs)
						$cats_raw = wp_get_post_terms( $book->id, 'dlm_book_category' );
						$slugs = array();
						if ( ! empty( $cats_raw ) && ! is_wp_error( $cats_raw ) ) {
							foreach ( $cats_raw as $t ) {
								if ( $t->parent == 0 ) {
									$slugs[] = $t->slug;
								} else {
									$slugs[] = $t->slug;
									$parent_term = get_term( $t->parent, 'dlm_book_category' );
									if ( $parent_term && ! is_wp_error( $parent_term ) ) {
										$slugs[] = $parent_term->slug;
									}
								}
							}
						}
						$cat_slugs_str = implode( ' ', array_unique( $slugs ) );
						
						// Is favorited
						$is_fav = in_array( $book->id, $fav_books );

						// Future release status
						$is_future   = ( ! empty( $book->publish_date ) && strtotime( $book->publish_date ) > current_time( 'timestamp' ) ) || ( isset( $book->status ) && $book->status === 'future' );
						$publish_iso = '';
						if ( ! empty( $book->publish_date ) ) {
							$publish_iso = wp_date( 'c', strtotime( $book->publish_date ) );
							if ( empty( $publish_iso ) ) {
								$publish_iso = gmdate( 'c', strtotime( $book->publish_date ) );
							}
							if ( empty( $publish_iso ) ) {
								$publish_iso = str_replace( ' ', 'T', trim( $book->publish_date ) );
							}
						}
						$publish_fmt = ( ! empty( $book->publish_date ) ) ? date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $book->publish_date ) ) : '';
						?>
						<div class="group cursor-pointer book-card-el" data-book-id="<?php echo intval( $book->id ); ?>" data-title="<?php echo esc_attr( strtolower( $book->title ) ); ?>" data-author="<?php echo esc_attr( strtolower( $book->author ) ); ?>" data-categories="<?php echo esc_attr( $cat_slugs_str ); ?>" data-pct="<?php echo intval($pct); ?>" data-user-access="<?php echo esc_attr($user_access); ?>" data-access-type="<?php echo esc_attr($access_type); ?>" data-price="<?php echo esc_attr($price); ?>">
							<div class="aspect-[3/4] rounded-2xl overflow-hidden mb-4 book-card-shadow relative">
								<?php if ( $book->cover_image_url ) : ?>
									<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo esc_url( $book->cover_image_url ); ?>" loading="lazy">
								<?php else : ?>
									<div class="w-full h-full bg-surface-container flex items-center justify-center text-center p-4">
										<span class="font-bold text-xs"><?php echo esc_html( $book->title ); ?></span>
									</div>
								<?php endif; ?>
								
								<!-- Hover Bookmarks -->
								<div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity z-10">
									<button class="p-2 bg-white/90 backdrop-blur-md rounded-full shadow-lg text-primary hover:scale-110 transition-transform" onclick="event.stopPropagation(); toggleFavoriteBook(<?php echo intval($book->id); ?>, this)">
										<i class="<?php echo $is_fav ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark"></i>
									</button>
								</div>

								<?php if ( $is_future ) : ?>
									<!-- Upcoming Pill Badge -->
									<div class="absolute top-2.5 left-2.5 z-10">
										<span class="px-2.5 py-1 bg-amber-600/95 backdrop-blur-md text-white text-[10px] font-extrabold uppercase tracking-wider rounded-lg shadow-md flex items-center gap-1">
											<i class="fa-solid fa-clock text-[9px]"></i> Upcoming
										</span>
									</div>

									<!-- 4-Box Countdown Timer at Bottom of Cover -->
									<?php 
									$rel_time = ! empty( $publish_iso ) ? $publish_iso : ( ! empty( $book->publish_date ) ? $book->publish_date : '' );
									if ( ! empty( $rel_time ) ) : 
									?>
										<div class="absolute bottom-2 inset-x-2 z-10 grid grid-cols-4 gap-1 p-1 rounded-xl shadow-xl text-white dlm-countdown-timer pointer-events-none" style="background: linear-gradient(135deg, rgba(133, 83, 0, 0.92), rgba(97, 59, 0, 0.95)) !important; border: 1px solid rgba(255, 255, 255, 0.28) !important; backdrop-filter: blur(8px);" data-release-time="<?php echo esc_attr( $rel_time ); ?>" data-book-id="<?php echo esc_attr( $book->id ); ?>">
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-days font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Day</span>
											</div>
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-hours font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Hr</span>
											</div>
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-minutes font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Min</span>
											</div>
											<div class="flex flex-col items-center justify-center rounded-lg py-1 px-0.5 text-center shadow-xs" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.15);">
												<span class="countdown-seconds font-mono font-extrabold text-[12px] leading-tight text-white">00</span>
												<span class="text-[7.5px] uppercase font-bold tracking-tight text-amber-100/90 leading-none">Sec</span>
											</div>
										</div>
									<?php endif; ?>

									<!-- Upcoming Release Overlay -->
									<div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-1.5 transition-opacity p-3 text-center z-20">
										<span class="px-3 py-1.5 bg-white text-black font-extrabold text-xs rounded-xl shadow-lg uppercase tracking-wider">
											Coming Soon
										</span>
										<p class="text-white/90 text-[11px] font-medium leading-tight mt-1">
											Releases <?php echo esc_html( $publish_fmt ); ?>
										</p>
									</div>
								<?php else : ?>
									<!-- Reading & Download Trigger overlay -->
									<div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-2 transition-opacity p-3 z-10">
										<?php if ( $user_access === 'read_download' ) : ?>
											<button class="dlm-book-action-btn" onclick="event.stopPropagation(); Aurelian.openBook(<?php echo intval($book->id); ?>, '<?php echo esc_js($book->title); ?>')">
												Read
											</button>
											<button class="dlm-btn-download dlm-book-action-btn" onclick="event.stopPropagation(); Aurelian.downloadBook(<?php echo intval($book->id); ?>)">
												Download
											</button>
										<?php elseif ( $user_access === 'read_only' ) : ?>
											<button class="dlm-book-action-btn" onclick="event.stopPropagation(); Aurelian.openBook(<?php echo intval($book->id); ?>, '<?php echo esc_js($book->title); ?>')">
												Read
											</button>
										<?php else : ?>
											<?php if ( $access_type === 'purchase_only' || $access_type === 'hybrid' ) : ?>
												<button class="dlm-btn-buy dlm-book-action-btn" onclick="event.stopPropagation(); Aurelian.buyBook(<?php echo intval($book->id); ?>)">
													Buy <?php echo esc_html( number_format( $price, 2 ) . ' ' . $currency ); ?>
												</button>
											<?php else : ?>
												<button class="dlm-book-action-btn" onclick="event.stopPropagation(); showTab('membership')">
													Subscribe
												</button>
											<?php endif; ?>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
							<h5 class="font-bold text-on-surface leading-snug mb-1 group-hover:text-primary transition-colors line-clamp-1"><?php echo esc_html( $book->title ); ?></h5>
							<p class="text-xs text-secondary line-clamp-1"><?php echo esc_html( $book->author ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>

		<!-- SECTION 2: DISCOVER VIEW -->
		<div id="section-discover" class="spa-page hidden">
			<section class="mb-10">
				<span class="text-primary font-label-caps uppercase tracking-widest text-[10px] mb-2 block">Curated for you</span>
				<h2 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2 font-bold">Discover</h2>
				<p class="text-secondary max-w-xl">New arrivals, trending reads, and picks matched to your taste &mdash; refreshed daily.</p>
			</section>

			<!-- Daily Pick Highlight Box -->
			<section class="mb-14 relative rounded-3xl overflow-hidden bg-on-surface text-white p-8 md:p-12 flex flex-col md:flex-row items-center gap-8 book-card-shadow">
				<div class="flex-1">
					<span class="inline-flex items-center gap-2 bg-primary-container/20 text-primary-container px-3 py-1 rounded-full text-[11px] font-semibold mb-4">
						<i class="fa-solid fa-wand-magic-sparkles text-[14px]"></i>
						TODAY'S PICK FOR YOU
					</span>
					<?php if ( ! empty($books) ) : ?>
						<h3 class="font-display-lg-mobile text-display-lg-mobile mb-3 font-bold text-white"><?php echo esc_html( $books[count($books)-1]->title ); ?></h3>
						<p class="text-white/70 max-w-md mb-6">Based on your recent intellectual logs &mdash; readers who appreciate modern literature loved this clean composition.</p>
						<button onclick="Aurelian.openBook(<?php echo intval($books[count($books)-1]->id); ?>, '<?php echo esc_js($books[count($books)-1]->title); ?>')" class="px-8 py-3 bg-primary-container text-on-primary-container font-semibold rounded-xl hover:scale-105 transition-transform">Start Reading &middot; +15 XP</button>
					<?php else : ?>
						<h3 class="font-display-lg-mobile text-display-lg-mobile mb-3 font-bold text-white">Cultivating the Future</h3>
						<p class="text-white/70 max-w-md mb-6">Create notes and explore topics to generate personalized recommendation arrays.</p>
					<?php endif; ?>
				</div>
				<div class="w-40 h-56 rounded-2xl overflow-hidden flex-shrink-0 shadow-2xl rotate-2">
					<img class="w-full h-full object-cover" src="<?php echo esc_url( DLM_URL . 'public/images/recommendation_cover.png' ); ?>" alt="Recommended Book">
				</div>
			</section>

			<!-- Trending Shelves -->
			<section class="mb-14">
				<div class="flex items-center justify-between mb-6">
					<h3 class="font-headline-md text-headline-md text-on-surface font-bold">Trending This Week</h3>
					<span class="text-secondary text-[13px]">Updated hourly, based on reader activity</span>
				</div>
				<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-gutter">
					<?php
					$limit = min(6, count($books));
					for ( $i = 0; $i < $limit; $i++ ) :
						$b = $books[$i];
						?>
						<div class="group cursor-pointer" onclick="Aurelian.openBook(<?php echo intval($b->id); ?>, '<?php echo esc_js($b->title); ?>')">
							<div class="aspect-[3/4] rounded-2xl overflow-hidden mb-3 book-card-shadow relative">
								<?php if ( $b->cover_image_url ) : ?>
									<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo esc_url( $b->cover_image_url ); ?>">
								<?php else : ?>
									<div class="w-full h-full bg-surface-container flex items-center justify-center p-3 text-center"><span class="text-xs"><?php echo esc_html($b->title); ?></span></div>
								<?php endif; ?>
							</div>
							<h5 class="font-semibold text-on-surface text-[14px] mb-0.5 group-hover:text-primary transition-colors line-clamp-1"><?php echo esc_html( $b->title ); ?></h5>
							<p class="text-xs text-secondary"><?php echo esc_html( $b->author ); ?></p>
						</div>
					<?php endfor; ?>
				</div>
			</section>
		</div>

		<!-- SECTION 3: READING JOURNAL VIEW -->
		<div id="section-journal" class="spa-page hidden">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
				<div>
					<span class="text-primary font-label-caps uppercase tracking-widest text-[10px] mb-2 block">Archive Space</span>
					<h2 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface font-bold">Your Journal</h2>
					<p class="text-secondary mt-2 max-w-xl">A curated collection of your intellectual journey. Reflect on insights and manage notes from your recent readings.</p>
				</div>
				<button onclick="openNoteModal('add')" class="bg-primary text-white px-6 py-3 rounded-full font-bold text-body-md flex items-center justify-center gap-2 shadow-lg shadow-primary/20 hover:scale-[1.05] active:scale-95 transition-all self-start">
					<i class="fa-solid fa-plus"></i> New Note
				</button>
			</div>

			<!-- Notes Card Grid Container -->
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="journal-notes-grid">
				<!-- Loaded dynamically by JS -->
			</div>
		</div>

		<!-- SECTION 4: COLLECTIONS VIEW -->
		<div id="section-collections" class="spa-page hidden">
			<section class="mb-10">
				<span class="text-primary font-label-caps uppercase tracking-widest text-[10px] mb-2 block">Your shelves</span>
				<h2 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2 font-bold">Collections</h2>
				<p class="text-secondary max-w-xl">Organize your library into shelves &mdash; group by mood, topic, or project.</p>
			</section>

			<!-- Smart Shelves -->
			<section class="mb-14">
				<h3 class="font-headline-md text-headline-md text-on-surface mb-6 font-bold">Smart Shelves</h3>
				<div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
					<div class="block bg-white border border-outline-variant/30 rounded-2xl p-6 book-card-shadow cursor-pointer hover:-translate-y-1 transition-transform" onclick="filterCategory('continue', null)">
						<div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-4">
							<i class="fa-solid fa-circle-play"></i>
						</div>
						<h4 class="font-bold text-on-surface mb-1">Currently Reading</h4>
						<p class="text-secondary text-[13px] mb-3">Books you've started but not finished</p>
						<span class="text-primary text-[13px] font-semibold" id="currently-reading-count">0 books</span>
					</div>
					<div class="block bg-white border border-outline-variant/30 rounded-2xl p-6 book-card-shadow cursor-pointer hover:-translate-y-1 transition-transform" onclick="filterCategory('favorites', null)">
						<div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-4">
							<i class="fa-solid fa-heart"></i>
						</div>
						<h4 class="font-bold text-on-surface mb-1">Favorites Shelves</h4>
						<p class="text-secondary text-[13px] mb-3">Your bookmarked and starred books</p>
						<span class="text-primary text-[13px] font-semibold" id="favorites-count">0 books</span>
					</div>
					<div class="block bg-white border border-outline-variant/30 rounded-2xl p-6 book-card-shadow cursor-pointer hover:-translate-y-1 transition-transform" onclick="showTab('journal')">
						<div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-4">
							<i class="fa-solid fa-pen-to-square"></i>
						</div>
						<h4 class="font-bold text-on-surface mb-1">Journal Logs</h4>
						<p class="text-secondary text-[13px] mb-3">Reflections and saved quotes</p>
						<span class="text-primary text-[13px] font-semibold" id="journal-logs-count">0 logs</span>
					</div>
				</div>
			</section>
		</div>

		<!-- SECTION 5: MEMBERSHIP BILLING VIEW -->
		<div id="section-membership" class="spa-page hidden">
			<!-- Payment Status Alert Banner -->
			<div id="membership-payment-alert" class="max-w-4xl mx-auto mb-8 hidden">
				<div class="flex items-center gap-4 p-5 rounded-2xl border shadow-sm text-left alert-box-container">
					<div id="membership-payment-alert-icon" class="w-12 h-12 rounded-full flex items-center justify-center text-xl flex-shrink-0 shadow-sm"></div>
					<div class="flex-1 space-y-0.5">
						<h4 id="membership-payment-alert-title" class="font-bold text-sm"></h4>
						<p id="membership-payment-alert-desc" class="text-xs leading-relaxed"></p>
					</div>
					<button onclick="jQuery('#membership-payment-alert').fadeOut()" class="p-1 hover:bg-black/5 rounded-full text-secondary transition-colors cursor-pointer border-none bg-transparent flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
				</div>
			</div>

			<section class="mb-12 text-center max-w-2xl mx-auto">
				<h2 class="font-display-lg text-display-lg-mobile md:text-display-lg text-primary mb-4 leading-tight font-bold">Choose Your Journey</h2>
				<p class="font-body-lg text-secondary max-w-lg mx-auto">Unlock the full potential of the Bridgeway36 Digital Library. Access unlimited curated publications.</p>
			</section>

			<!-- Subscriptions Active Card -->
			<section class="max-w-4xl mx-auto mb-12">
				<div class="bg-white border border-outline-variant/30 rounded-3xl p-6 md:p-8 book-card-shadow">
					<h3 class="font-bold text-lg text-on-surface mb-4">Your Subscription Status</h3>
					<div class="flex flex-col md:flex-row md:items-center justify-between gap-6" id="sub-status-box">
						<!-- Content loaded dynamically based on PHP sub status -->
						<?php if ( $has_active_sub && $sub_details ) : ?>
							<div>
								<div class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold mb-2">
									<span class="w-2 h-2 rounded-full bg-green-500"></span> ACTIVE MEMBERSHIP
								</div>
								<p class="font-semibold text-lg text-on-surface uppercase"><?php echo esc_html($sub_details->plan_interval); ?> PLAN</p>
								<p class="text-sm text-secondary">Billed via <?php echo esc_html(ucfirst($sub_details->provider)); ?>. Expiry date: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub_details->expires_at))); ?></p>
							</div>
							<div class="flex gap-4">
								<button class="px-6 py-2.5 border border-outline-variant/30 text-secondary hover:bg-surface-container rounded-xl text-sm font-bold" onclick="Aurelian.toast('Subscription managed via payment provider profile')">Manage Billing</button>
							</div>
						<?php elseif ( get_user_meta( $user_id, 'dlm_manual_override', true ) === 'active' ) : ?>
							<div>
								<div class="inline-flex items-center gap-2 px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-bold mb-2">
									<span class="w-2 h-2 rounded-full bg-primary animate-ping"></span> UNLIMITED ACCESS
								</div>
								<p class="font-semibold text-lg text-on-surface">STAFF MANUAL ACCESS</p>
								<p class="text-sm text-secondary">Granted unlimited reading privileges by an administrator.</p>
							</div>
						<?php elseif ( $sub_details && $sub_details->status === 'pending_approval' ) : ?>
							<div>
								<div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold mb-2">
									<span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> PENDING APPROVAL
								</div>
								<p class="font-semibold text-lg text-on-surface uppercase"><?php echo esc_html($sub_details->plan_interval); ?> PLAN</p>
								<p class="text-sm text-secondary">Your transaction is waiting for admin approval. Once approved, your membership will be active.</p>
							</div>
						<?php else : ?>
							<div>
								<div class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold mb-2">
									<span class="w-2 h-2 rounded-full bg-red-500"></span> INACTIVE
								</div>
								<p class="font-semibold text-lg text-on-surface">No Active Membership Plan</p>
								<p class="text-sm text-secondary">Join a plan to unlock all manuscripts and flipbook read features.</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<!-- Pricing Tiers Grid -->
			<section class="mb-16">
				<?php if ( empty( $active_packages ) ) : ?>
					<div class="p-12 text-center bg-white border border-outline-variant/30 rounded-[32px] max-w-xl mx-auto book-card-shadow">
						<i class="fa-solid fa-crown text-4xl text-primary mb-4 block"></i>
						<h3 class="font-bold text-xl text-on-surface mb-2"><?php esc_html_e( 'No Plans Currently Available', 'digital-library-membership' ); ?></h3>
						<p class="text-sm text-secondary"><?php esc_html_e( 'Please check back soon or contact support for assistance.', 'digital-library-membership' ); ?></p>
					</div>
				<?php else : ?>
					<div class="grid grid-cols-1 <?php echo ( count( $active_packages ) === 1 ) ? 'max-w-md' : ( ( count( $active_packages ) === 2 ) ? 'md:grid-cols-2 max-w-3xl' : 'md:grid-cols-3 max-w-5xl' ); ?> gap-8 mx-auto">
						<?php 
						foreach ( $active_packages as $pkg_id => $pkg ) : 
							$pkg_price    = floatval( $pkg['price'] ?? 0 );
							$pkg_name     = ! empty( $pkg['name'] ) ? $pkg['name'] : ucfirst( $pkg_id );
							$pkg_badge    = ! empty( $pkg['badge'] ) ? $pkg['badge'] : '';
							$pkg_interval = ! empty( $pkg['interval'] ) ? $pkg['interval'] : $pkg_id;
							$pkg_features = ( ! empty( $pkg['features'] ) && is_array( $pkg['features'] ) ) ? $pkg['features'] : array();
							$is_featured  = ( 'yearly' === $pkg_interval || ! empty( $pkg['is_featured'] ) );

							$interval_label = '/month';
							if ( 'yearly' === $pkg_interval ) {
								$interval_label = '/year';
							} elseif ( 'lifetime' === $pkg_interval ) {
								$interval_label = '/one-time';
							} elseif ( 'quarterly' === $pkg_interval ) {
								$interval_label = '/quarter';
							}
						?>
						<div class="bg-white border <?php echo $is_featured ? 'border-2 border-primary shadow-xl' : 'border-outline-variant/30 shadow-sm'; ?> rounded-[32px] p-8 flex flex-col relative book-card-shadow transition-all duration-300 hover:-translate-y-1">
							<?php if ( $is_featured ) : ?>
								<div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-primary text-white px-6 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest whitespace-nowrap shadow-md">
									<?php echo esc_html( ! empty( $pkg['badge_top'] ) ? $pkg['badge_top'] : __( 'BEST VALUE', 'digital-library-membership' ) ); ?>
								</div>
							<?php endif; ?>

							<div class="mb-8">
								<?php if ( ! empty( $pkg_badge ) ) : ?>
									<span class="<?php echo $is_featured ? 'text-primary bg-primary/10' : 'text-secondary bg-secondary-container/40'; ?> px-3 py-1 rounded-full text-xs uppercase font-semibold mb-4 inline-block">
										<?php echo esc_html( $pkg_badge ); ?>
									</span>
								<?php endif; ?>
								<h3 class="font-bold text-xl text-on-surface mb-2"><?php echo esc_html( $pkg_name ); ?></h3>
								<div class="flex items-baseline gap-1 mt-4">
									<span class="text-3xl font-bold text-on-surface"><?php echo esc_html( $currency ); ?><?php echo esc_html( number_format( $pkg_price, 2 ) ); ?></span>
									<span class="text-secondary font-body-md"><?php echo esc_html( $interval_label ); ?></span>
								</div>
							</div>

							<ul class="space-y-4 mb-8 flex-1 text-sm text-on-surface-variant">
								<?php foreach ( $pkg_features as $feature ) : ?>
									<li class="flex items-center gap-2">
										<i class="fa-solid fa-circle-check text-primary shrink-0"></i>
										<span><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>

							<button onclick="goToCheckout('<?php echo esc_attr( $pkg_id ); ?>', '<?php echo esc_attr( number_format( $pkg_price, 2, '.', '' ) ); ?>', '<?php echo esc_attr( $pkg_name ); ?>')" class="w-full py-3 <?php echo $is_featured ? 'bg-primary text-white font-semibold shadow-lg shadow-primary/20 hover:bg-primary-container' : 'bg-secondary-container text-on-secondary-container font-semibold hover:opacity-80'; ?> rounded-2xl transition-all cursor-pointer">
								<?php 
								if ( 'lifetime' === $pkg_interval ) {
									esc_html_e( 'Unlock Lifetime', 'digital-library-membership' );
								} elseif ( 'yearly' === $pkg_interval ) {
									esc_html_e( 'Subscribe Yearly', 'digital-library-membership' );
								} else {
									esc_html_e( 'Select Plan', 'digital-library-membership' );
								}
								?>
							</button>
						</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</div>

		<!-- SECTION 6: ACHIEVEMENTS TAB VIEW -->
		<div id="section-achievements" class="spa-page hidden">
			<section class="mb-10">
				<span class="text-primary font-label-caps uppercase tracking-widest text-[10px] mb-2 block">Your progress</span>
				<h2 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2 font-bold">Achievements</h2>
				<p class="text-secondary max-w-xl">Track your streak, level, and the badges you've earned along the way.</p>
			</section>

			<!-- Achievements Stats Summary Row -->
			<section class="grid grid-cols-1 md:grid-cols-3 gap-gutter mb-14">
				<div class="bg-on-surface text-white rounded-2xl p-6 book-card-shadow flex items-center gap-5">
					<div class="w-14 h-14 rounded-full bg-primary-container/20 flex items-center justify-center flex-shrink-0">
						<i class="fa-solid fa-fire text-primary-container text-[28px]"></i>
					</div>
					<div>
						<p class="text-3xl font-bold text-white" id="streak-num">0</p>
						<p class="text-white/60 text-[12px] uppercase tracking-wide">Day Streak</p>
					</div>
				</div>
				<div class="bg-white border border-outline-variant/30 rounded-2xl p-6 book-card-shadow">
					<div class="flex items-center justify-between mb-2">
						<span class="font-semibold text-on-surface">Level Progress</span>
						<span class="text-primary font-bold" id="xp-level">Lv. 1</span>
					</div>
					<div class="w-full h-2.5 bg-surface-container-highest rounded-full overflow-hidden mb-2">
						<div class="h-full bg-primary rounded-full transition-all duration-500" style="width:0%" id="xp-bar"></div>
					</div>
					<p class="text-secondary text-xs" id="xp-fraction">0 / 150 XP</p>
				</div>
				<div class="bg-white border border-outline-variant/30 rounded-2xl p-6 book-card-shadow flex items-center gap-5">
					<div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
						<i class="fa-solid fa-trophy text-primary text-[28px]"></i>
					</div>
					<div>
						<p class="text-3xl font-bold text-on-surface" id="badge-count">0</p>
						<p class="text-secondary text-[12px] uppercase tracking-wide">Badges Earned</p>
					</div>
				</div>
			</section>

			<!-- Weekly streak calendar grid -->
			<section class="mb-14 bg-white border border-outline-variant/30 rounded-2xl p-6 md:p-8 book-card-shadow">
				<h3 class="font-headline-md text-headline-md text-on-surface mb-6 font-bold">This Week Progress</h3>
				<div class="grid grid-cols-7 gap-3 text-center" id="week-strip">
					<div data-day-offset="0" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Mon</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
					<div data-day-offset="1" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Tue</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
					<div data-day-offset="2" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Wed</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
					<div data-day-offset="3" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Thu</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
					<div data-day-offset="4" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Fri</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
					<div data-day-offset="5" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Sat</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
					<div data-day-offset="6" class="aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors bg-surface-container-low p-2">
						<span class="text-[10px] font-bold uppercase text-secondary">Sun</span>
						<i class="fa-solid fa-fire text-lg text-secondary"></i>
					</div>
				</div>
			</section>

			<!-- Badge Grid wall -->
			<section class="mb-16">
				<h3 class="font-headline-md text-headline-md text-on-surface mb-6 font-bold">Badge Wall</h3>
				<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-gutter" id="badge-wall">
					<div data-badge-id="joined" class="bg-white border border-outline-variant/30 rounded-2xl p-5 book-card-shadow text-center">
						<div class="w-14 h-14 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
							<i class="fa-solid fa-champagne-glasses text-[24px]"></i>
						</div>
						<h5 class="font-bold text-on-surface text-[14px] mb-1">Joined the Archive</h5>
						<p class="text-secondary text-[11px]">Create your account</p>
					</div>
					<div data-badge-id="first-book" class="bg-white border border-outline-variant/30 rounded-2xl p-5 book-card-shadow text-center opacity-40 grayscale">
						<div class="w-14 h-14 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
							<i class="fa-solid fa-book-open text-[24px]"></i>
						</div>
						<h5 class="font-bold text-on-surface text-[14px] mb-1">First Chapter</h5>
						<p class="text-secondary text-[11px]">Open your first book</p>
					</div>
					<div data-badge-id="streak-3" class="bg-white border border-outline-variant/30 rounded-2xl p-5 book-card-shadow text-center opacity-40 grayscale">
						<div class="w-14 h-14 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
							<i class="fa-solid fa-fire text-[24px]"></i>
						</div>
						<h5 class="font-bold text-on-surface text-[14px] mb-1">3 Day Streak</h5>
						<p class="text-secondary text-[11px]">Read 3 days in a row</p>
					</div>
					<div data-badge-id="streak-7" class="bg-white border border-outline-variant/30 rounded-2xl p-5 book-card-shadow text-center opacity-40 grayscale">
						<div class="w-14 h-14 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
							<i class="fa-solid fa-fire text-[24px]"></i>
						</div>
						<h5 class="font-bold text-on-surface text-[14px] mb-1">7 Day Streak</h5>
						<p class="text-secondary text-[11px]">Read 7 days in a row</p>
					</div>
					<div data-badge-id="member" class="bg-white border border-outline-variant/30 rounded-2xl p-5 book-card-shadow text-center opacity-40 grayscale">
						<div class="w-14 h-14 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
							<i class="fa-solid fa-crown text-[24px]"></i>
						</div>
						<h5 class="font-bold text-on-surface text-[14px] mb-1">Archive Member</h5>
						<p class="text-secondary text-[11px]">Join a paid membership</p>
					</div>
				</div>
			</section>
		</div>

		<!-- SECTION 7: USER PROFILE SETTINGS VIEW -->
		<div id="section-settings" class="spa-page hidden">
			<section class="mb-10">
				<h2 class="font-display-lg text-display-lg text-on-surface mb-2 font-bold">Profile Settings</h2>
				<p class="text-secondary">Update your display parameters and change credentials.</p>
			</section>

			<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
				<!-- Avatar box -->
				<div class="bg-white border border-outline-variant/30 rounded-3xl p-6 text-center shadow-sm">
					<div class="relative w-32 h-32 mx-auto mb-6 group cursor-pointer" onclick="document.getElementById('avatar-file-input').click()">
						<img class="w-full h-full object-cover rounded-full border-2 border-primary shadow-lg" id="settings-avatar-preview" src="<?php echo esc_url($avatar_url); ?>">
						<div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition-opacity">
							Change Photo
						</div>
					</div>
					<input type="file" id="avatar-file-input" class="hidden" accept="image/*" onchange="uploadAvatarImage(this)">
					<h3 class="font-bold text-lg text-on-surface leading-tight" id="profile-display-name-header"><?php echo esc_html( $user_display_name ); ?></h3>
					<p class="text-xs text-secondary mt-1"><?php echo esc_html( $user_email ); ?></p>
				</div>

				<!-- Edit Profile inputs -->
				<div class="lg:col-span-2 bg-white border border-outline-variant/30 rounded-3xl p-8 shadow-sm">
					<form id="profile-update-form" class="space-y-6" onsubmit="updateProfileSettings(event)">
						<div id="profile-alert" class="hidden p-4 rounded-xl text-sm mb-4"></div>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
							<div class="space-y-2">
								<label class="font-label-caps text-xs text-secondary uppercase block">Display Name</label>
								<input class="w-full h-12 px-4 bg-surface-container-lowest border border-outline-variant/30 rounded-xl text-body-md focus:border-primary focus:ring-0" name="display_name" value="<?php echo esc_attr($user_display_name); ?>" required type="text">
							</div>
							<div class="space-y-2">
								<label class="font-label-caps text-xs text-secondary uppercase block">Email Address</label>
								<input class="w-full h-12 px-4 bg-surface-container-lowest border border-outline-variant/30 rounded-xl text-body-md focus:border-primary focus:ring-0" name="user_email" value="<?php echo esc_attr($user_email); ?>" required type="email">
							</div>
						</div>
						<div class="space-y-2">
							<label class="font-label-caps text-xs text-secondary uppercase block">New Password (leave empty to keep current)</label>
							<div class="relative">
								<input class="w-full h-12 px-4 bg-surface-container-lowest border border-outline-variant/30 rounded-xl text-body-md focus:border-primary focus:ring-0" id="profile-new-password" name="new_password" placeholder="Min 6 characters" minlength="6" type="password">
								<button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" onclick="togglePasswordVisibility('profile-new-password')" type="button"><i class="fa-regular fa-eye"></i></button>
							</div>
						</div>
						<div class="flex justify-end pt-4">
							<button class="px-8 py-3 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-md shadow-primary/10 cursor-pointer" type="submit">Save Changes</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Interactive Onboarding Tour Replay Card -->
			<div class="mt-8 bg-white border border-outline-variant/30 rounded-3xl p-6 md:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
				<div class="space-y-1 max-w-xl">
					<div class="flex items-center gap-2.5 mb-1">
						<span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-base font-bold flex-shrink-0">
							<i class="fa-solid fa-route"></i>
						</span>
						<h3 class="font-bold text-lg text-on-surface">Member Onboarding Tour</h3>
					</div>
					<p class="text-sm text-secondary leading-relaxed">Need a quick walkthrough of all features and navigation tools? Replay the interactive spotlight tour anytime.</p>
				</div>
				<button id="dlm-replay-tour-btn" onclick="replayOnboardingTour()" type="button" class="px-6 py-3.5 bg-surface-container hover:bg-primary hover:text-white text-on-surface font-bold rounded-xl active:scale-[0.98] transition-all flex items-center justify-center gap-2.5 shadow-sm flex-shrink-0 cursor-pointer min-h-[44px]">
					<i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
					<span>Show Me Around Again</span>
				</button>
			</div>
		</div>

		<!-- SECTION 8: CHECKOUT PAYMENT VIEW -->
		<div id="section-checkout" class="spa-page hidden">
			<section class="mb-10 flex items-center gap-4">
				<button onclick="showTab('membership')" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container text-secondary transition-colors"><i class="fa-solid fa-arrow-left"></i></button>
				<div>
					<span class="text-primary font-label-caps uppercase tracking-widest text-[10px] mb-1 block">Review your selection</span>
					<h2 class="font-headline-md text-headline-md font-bold text-on-surface">Secure Checkout</h2>
				</div>
			</section>

			<!-- Checkout Column Grid -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
				<!-- Payment Options forms -->
				<div class="lg:col-span-7 space-y-8">
					<section class="space-y-4">
						<h3 class="font-bold text-[18px] text-on-surface">1. Choose Payment Method</h3>
						<?php if ( empty( $active_gateways ) ) : ?>
							<div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/30 text-center space-y-2">
								<i class="fa-solid fa-lock text-2xl text-secondary"></i>
								<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'No payment methods currently active.', 'digital-library-membership' ); ?></p>
								<p class="text-xs text-secondary"><?php esc_html_e( 'Please contact the site administration to complete your subscription.', 'digital-library-membership' ); ?></p>
							</div>
						<?php else : ?>
						<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-<?php echo esc_attr( (string) max( 1, min( 4, count( $active_gateways ) ) ) ); ?> gap-4">
							<?php if ( $enable_wc ) : ?>
							<!-- WooCommerce Option -->
							<button class="flex items-center justify-between p-4 border <?php echo ( 'woocommerce' === $default_gateway ) ? 'border-2 border-primary' : 'border-outline-variant/30'; ?> rounded-xl text-left method-btn cursor-pointer transition-all" id="checkout-method-woocommerce" onclick="toggleCheckoutPaymentMethod('woocommerce')">
								<div class="flex items-center gap-3">
									<i class="fa-solid fa-bag-shopping <?php echo ( 'woocommerce' === $default_gateway ) ? 'text-primary' : 'text-secondary'; ?> text-lg shrink-0"></i>
									<div>
										<p class="font-bold text-sm text-on-surface">WooCommerce</p>
										<p class="text-[10px] text-secondary">Store Payment Gateway</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border <?php echo ( 'woocommerce' === $default_gateway ) ? 'border-primary' : 'border-outline-variant'; ?> flex items-center justify-center shrink-0">
									<div class="w-2.5 h-2.5 rounded-full bg-primary <?php echo ( 'woocommerce' === $default_gateway ) ? '' : 'hidden'; ?>" id="woocommerce-dot"></div>
								</div>
							</button>
							<?php endif; ?>

							<?php if ( $enable_stripe ) : ?>
							<!-- Stripe options -->
							<button class="flex items-center justify-between p-4 border <?php echo ( 'stripe' === $default_gateway ) ? 'border-2 border-primary' : 'border-outline-variant/30'; ?> rounded-xl text-left method-btn cursor-pointer transition-all" id="checkout-method-stripe" onclick="toggleCheckoutPaymentMethod('stripe')">
								<div class="flex items-center gap-3">
									<i class="fa-solid fa-credit-card <?php echo ( 'stripe' === $default_gateway ) ? 'text-primary' : 'text-secondary'; ?> text-lg shrink-0"></i>
									<div>
										<p class="font-bold text-sm text-on-surface">Stripe</p>
										<p class="text-[10px] text-secondary">Card Checkout</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border <?php echo ( 'stripe' === $default_gateway ) ? 'border-primary' : 'border-outline-variant'; ?> flex items-center justify-center shrink-0">
									<div class="w-2.5 h-2.5 rounded-full bg-primary <?php echo ( 'stripe' === $default_gateway ) ? '' : 'hidden'; ?>" id="stripe-dot"></div>
								</div>
							</button>
							<?php endif; ?>

							<?php if ( $enable_paypal ) : ?>
							<!-- PayPal Option -->
							<button class="flex items-center justify-between p-4 border <?php echo ( 'paypal' === $default_gateway ) ? 'border-2 border-primary' : 'border-outline-variant/30'; ?> rounded-xl text-left method-btn cursor-pointer transition-all" id="checkout-method-paypal" onclick="toggleCheckoutPaymentMethod('paypal')">
								<div class="flex items-center gap-3">
									<i class="fa-brands fa-paypal <?php echo ( 'paypal' === $default_gateway ) ? 'text-primary' : 'text-secondary'; ?> text-lg shrink-0"></i>
									<div>
										<p class="font-bold text-sm text-on-surface">PayPal</p>
										<p class="text-[10px] text-secondary">External Wallet</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border <?php echo ( 'paypal' === $default_gateway ) ? 'border-primary' : 'border-outline-variant'; ?> flex items-center justify-center shrink-0">
									<div class="w-2.5 h-2.5 rounded-full bg-primary <?php echo ( 'paypal' === $default_gateway ) ? '' : 'hidden'; ?>" id="paypal-dot"></div>
								</div>
							</button>
							<?php endif; ?>

							<?php if ( $enable_manual ) : ?>
							<!-- Manual Option -->
							<button class="flex items-center justify-between p-4 border <?php echo ( 'manual' === $default_gateway ) ? 'border-2 border-primary' : 'border-outline-variant/30'; ?> rounded-xl text-left method-btn cursor-pointer transition-all" id="checkout-method-manual" onclick="toggleCheckoutPaymentMethod('manual')">
								<div class="flex items-center gap-3">
									<i class="fa-solid fa-building-columns <?php echo ( 'manual' === $default_gateway ) ? 'text-primary' : 'text-secondary'; ?> text-lg shrink-0"></i>
									<div>
										<p class="font-bold text-sm text-on-surface">Bank Transfer</p>
										<p class="text-[10px] text-secondary">Manual Review</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border <?php echo ( 'manual' === $default_gateway ) ? 'border-primary' : 'border-outline-variant'; ?> flex items-center justify-center shrink-0">
									<div class="w-2.5 h-2.5 rounded-full bg-primary <?php echo ( 'manual' === $default_gateway ) ? '' : 'hidden'; ?>" id="manual-dot"></div>
								</div>
							</button>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</section>

					<!-- Payment Forms -->
					<?php if ( $enable_wc ) : ?>
					<div id="woocommerce-checkout-container" class="<?php echo ( 'woocommerce' === $default_gateway ) ? '' : 'hidden'; ?> space-y-6">
						<div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/20 space-y-3">
							<div class="flex items-center gap-3">
								<div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg font-bold shrink-0">
									<i class="fa-solid fa-shield-halved"></i>
								</div>
								<div>
									<h4 class="font-bold text-sm text-on-surface"><?php esc_html_e( 'WooCommerce Secure Gateway', 'digital-library-membership' ); ?></h4>
									<p class="text-xs text-secondary"><?php esc_html_e( 'Pay safely via payment methods configured in your store checkout.', 'digital-library-membership' ); ?></p>
								</div>
							</div>
							<p class="text-xs text-secondary leading-relaxed pt-2 border-t border-outline-variant/20">
								<?php esc_html_e( 'Clicking the button below will create your headless order and redirect you to the payment page instantly.', 'digital-library-membership' ); ?>
							</p>
						</div>
						<button id="wc-checkout-btn" onclick="triggerWooCommerceSubscriptionOrder()" class="w-full h-14 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-3 cursor-pointer shadow-lg shadow-primary/20">
							<span><?php esc_html_e( 'Proceed to WooCommerce Checkout', 'digital-library-membership' ); ?></span>
							<i class="fa-solid fa-arrow-right"></i>
						</button>
					</div>
					<?php endif; ?>

					<?php if ( $enable_stripe ) : ?>
					<div id="stripe-checkout-container" class="<?php echo ( 'stripe' === $default_gateway ) ? '' : 'hidden'; ?> space-y-6">
						<div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/20">
							<p class="text-sm text-secondary leading-relaxed"><?php esc_html_e( 'Stripe handles card validation securely. Pressing "Complete Secure Checkout" redirects to Stripe\'s payment interface.', 'digital-library-membership' ); ?></p>
						</div>
						<button onclick="triggerStripeCheckoutSession()" class="w-full h-14 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-3 cursor-pointer">
							<span><?php esc_html_e( 'Complete Secure Checkout', 'digital-library-membership' ); ?></span> <i class="fa-solid fa-arrow-right"></i>
						</button>
					</div>
					<?php endif; ?>

					<?php if ( $enable_paypal ) : ?>
					<div id="paypal-checkout-container" class="<?php echo ( 'paypal' === $default_gateway ) ? '' : 'hidden'; ?> space-y-6">
						<div id="paypal-button-container" class="w-full"></div>
					</div>
					<?php endif; ?>

					<?php if ( $enable_manual ) : ?>
					<div id="manual-checkout-container" class="<?php echo ( 'manual' === $default_gateway ) ? '' : 'hidden'; ?> space-y-6">
						<div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/20 space-y-4">
							<h4 class="font-bold text-sm text-on-surface"><?php esc_html_e( 'Direct Bank Transfer Instructions', 'digital-library-membership' ); ?></h4>
							<div class="text-xs text-secondary leading-relaxed p-3 bg-white rounded-xl border border-outline-variant/30">
								<?php echo wp_kses_post( get_option( 'dlm_manual_payment_instructions', __( 'Please transfer funds directly to our bank details and submit your reference code below.', 'digital-library-membership' ) ) ); ?>
							</div>
							<div class="space-y-2">
								<label class="font-label-caps text-xs text-secondary uppercase block"><?php esc_html_e( 'Transaction Reference Code *', 'digital-library-membership' ); ?></label>
								<input class="w-full h-12 px-4 bg-white border border-outline-variant/30 rounded-xl text-body-md" id="checkout-manual-ref" placeholder="<?php esc_attr_e( 'e.g. Wire transaction reference ID', 'digital-library-membership' ); ?>" type="text">
							</div>
						</div>
						<button onclick="triggerManualPaymentSubmission()" class="w-full h-14 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-3 cursor-pointer">
							<span><?php esc_html_e( 'Submit Reference Code', 'digital-library-membership' ); ?></span> <i class="fa-solid fa-arrow-right"></i>
						</button>
					</div>
					<?php endif; ?>
				</div>

				<!-- Right summary Column -->
				<div class="lg:col-span-5">
					<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-[24px] overflow-hidden shadow-sm sticky top-32">
						<div class="relative h-48 w-full" style="background: linear-gradient(135deg, #855300 0%, #3e2600 100%);">
							<div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest via-transparent to-transparent"></div>
							<div class="absolute bottom-6 left-6">
								<span class="px-3 py-1 bg-primary text-white text-[11px] font-bold rounded-full mb-2 inline-block">SECURE GATEWAY</span>
								<h3 class="font-headline-md text-[24px] font-bold text-on-surface" id="checkout-plan-name">Monthly Plan</h3>
							</div>
						</div>
						<div class="p-8 space-y-6">
							<div class="flex justify-between items-center pb-4 border-b border-outline-variant/20">
								<div>
									<p class="font-bold" id="checkout-summary-title">Monthly Subscription</p>
									<p class="text-xs text-secondary">Unlimited digital books reading</p>
								</div>
								<p class="font-bold text-lg text-primary" id="checkout-summary-price">$12.00</p>
							</div>
							<div class="space-y-2 pt-4">
								<div class="flex justify-between text-secondary text-sm">
									<span>Subtotal</span>
									<span id="checkout-calc-subtotal">$12.00</span>
								</div>
								<div class="flex justify-between text-secondary text-sm">
									<span>VAT (0%)</span>
									<span>$0.00</span>
								</div>
								<div class="flex justify-between pt-4 border-t border-outline-variant/30">
									<span class="font-bold text-on-surface">Total Charge</span>
									<span class="font-bold text-primary text-[20px]" id="checkout-calc-total">$12.00</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


	</main>
	</div> <!-- End main content flex wrapper -->
	</div> <!-- End centered portal content wrapper -->
	</div> <!-- End outer centering wrapper -->

	<!-- Mobile Bottom Bar navigation (Responsive 5-Tab Bar) -->
	<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-2 py-2 pb-safe bg-white/95 backdrop-blur-xl border-t border-outline-variant/30 shadow-2xl">
		<a class="flex flex-col items-center justify-center py-1 px-2 text-primary mobile-nav-btn cursor-pointer transition-all" data-tab="library" onclick="showTab('library')">
			<i class="fa-solid fa-book text-[18px] mb-0.5"></i>
			<span class="text-[10px] font-bold">Library</span>
		</a>
		<a class="flex flex-col items-center justify-center py-1 px-2 text-secondary mobile-nav-btn cursor-pointer transition-all" data-tab="discover" onclick="showTab('discover')">
			<i class="fa-solid fa-compass text-[18px] mb-0.5"></i>
			<span class="text-[10px] font-bold">Explore</span>
		</a>
		<a class="flex flex-col items-center justify-center py-1 px-2 text-secondary mobile-nav-btn cursor-pointer transition-all" data-tab="journal" onclick="showTab('journal')">
			<i class="fa-solid fa-pen-to-square text-[18px] mb-0.5"></i>
			<span class="text-[10px] font-bold">Journal</span>
		</a>
		<a class="flex flex-col items-center justify-center py-1 px-2 text-secondary mobile-nav-btn cursor-pointer transition-all" data-tab="membership" onclick="showTab('membership')">
			<i class="fa-solid fa-crown text-[18px] mb-0.5"></i>
			<span class="text-[10px] font-bold">Pro</span>
		</a>
		<a class="flex flex-col items-center justify-center py-1 px-2 text-secondary cursor-pointer transition-all" id="mobile-nav-menu-btn" onclick="openMobileDrawer()">
			<i class="fa-solid fa-bars-staggered text-[18px] mb-0.5"></i>
			<span class="text-[10px] font-bold">Menu</span>
		</a>
	</nav>

	<!-- Journal Note Add/Edit Overlay Modal -->
	<div id="journal-note-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 bg-black/40 backdrop-blur-sm hidden flex">
		<div class="bg-white rounded-3xl p-6 md:p-8 max-w-lg w-full book-card-shadow space-y-4">
			<div class="flex justify-between items-center pb-2 border-b border-outline-variant/30">
				<h3 class="font-bold text-lg text-on-surface" id="note-modal-title">New Journal Entry</h3>
				<button class="p-1 hover:bg-surface-container rounded-full text-secondary" onclick="closeNoteModal()"><i class="fa-solid fa-xmark"></i></button>
			</div>
			<form id="note-modal-form" onsubmit="saveJournalNote(event)">
				<input type="hidden" id="note-id-input">
				<div class="space-y-4">
					<div class="space-y-1">
						<label class="text-xs font-bold text-secondary uppercase block">Select Book *</label>
						<select id="note-book-select" class="w-full rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm py-2 px-3" required>
							<option value="">-- Choose a Book --</option>
							<?php foreach ( $books as $b ) : ?>
								<option value="<?php echo esc_attr( $b->title ); ?>"><?php echo esc_html( $b->title ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="grid grid-cols-2 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-secondary uppercase block">Chapter / Section</label>
							<input id="note-chapter-input" class="w-full rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm py-2 px-3" placeholder="e.g. Chapter 2" type="text">
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-secondary uppercase block">Genre Tag</label>
							<select id="note-tag-select" class="w-full rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm py-2 px-3">
								<option value="Philosophy">Philosophy</option>
								<option value="Design">Design</option>
								<option value="Classic">Classic</option>
								<option value="Photography">Photography</option>
								<option value="General">General</option>
							</select>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-secondary uppercase block">Note Content *</label>
						<textarea id="note-content-input" class="w-full h-32 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm py-2 px-3" placeholder="Write your reflections here..." required></textarea>
					</div>
				</div>
				<div class="flex justify-end gap-3 pt-6 border-t border-outline-variant/30 mt-6">
					<button type="button" onclick="closeNoteModal()" class="px-5 py-2.5 border border-outline-variant/30 rounded-xl text-secondary text-sm font-semibold hover:bg-surface-container">Cancel</button>
					<button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-bold shadow-md shadow-primary/10 hover:opacity-90">Save Entry</button>
				</div>
			</form>
		</div>
	</div>
<?php endif; ?>

<!-- ========================================== -->
<!-- SCRIPTS AND DATA BINDING LAYERS            -->
<!-- ========================================== -->
<?php if ( $is_logged_in ) : ?>
	<script>
		// Expose synced states on load
		window.dlmDashboardParams = {
			ajaxUrl: '<?php echo esc_js( $ajax_url ); ?>',
			nonce: '<?php echo esc_js( $dlm_public_nonce ); ?>',
			stripeKey: '<?php echo esc_js( $stripe_publishable_key ); ?>',
			hasActiveSub: <?php echo $has_active_sub ? 'true' : 'false'; ?>,
			isPendingApproval: <?php echo ( $sub_details && $sub_details->status === 'pending_approval' ) ? 'true' : 'false'; ?>,
			userAchievements: <?php echo json_encode( $achievements ); ?>,
			userNotes: <?php echo json_encode( $notes ); ?>,
			favoriteBooks: <?php echo json_encode( $fav_books ); ?>,
			checkoutUrl: '<?php echo esc_js( dlm_get_page_url( 'checkout' ) ); ?>',
			shouldShowOnboarding: <?php echo $should_show_onboarding ? 'true' : 'false'; ?>,
			onboardingCompleted: '<?php echo esc_js( $onboarding_completed ); ?>'
		};
		// Immediately populate and synchronize window.dlmParams for all inline handlers & closures
		window.dlmParams = window.dlmParams || {};
		for (var k in window.dlmDashboardParams) {
			if (Object.prototype.hasOwnProperty.call(window.dlmDashboardParams, k)) {
				window.dlmParams[k] = window.dlmDashboardParams[k];
			}
		}
	</script>
<?php else : ?>
	<script>
		window.dlmDashboardParams = {
			ajaxUrl: '<?php echo esc_js( $ajax_url ); ?>',
			nonce: '<?php echo esc_js( $dlm_public_nonce ); ?>'
		};
		window.dlmParams = window.dlmParams || {};
		for (var k in window.dlmDashboardParams) {
			if (Object.prototype.hasOwnProperty.call(window.dlmDashboardParams, k)) {
				window.dlmParams[k] = window.dlmDashboardParams[k];
			}
		}
	</script>
<?php endif; ?>

<script>
	// -------------------------------------------------------------
	// CORE SPA ROUTER AND NOTIFICATIONS SCRIPT
	// -------------------------------------------------------------
	
	function togglePasswordVisibility(id) {
		const input = document.getElementById(id);
		const btn = input.nextElementSibling;
		if (input.type === 'password') {
			input.type = 'text';
			if (btn) {
				const icon = btn.querySelector('i');
				if (icon) {
					icon.classList.remove('fa-eye');
					icon.classList.add('fa-eye-slash');
				}
			}
		} else {
			input.type = 'password';
			if (btn) {
				const icon = btn.querySelector('i');
				if (icon) {
					icon.classList.remove('fa-eye-slash');
					icon.classList.add('fa-eye');
				}
			}
		}
	}

	function switchAuthTab(tab) {
		const btnSignin = document.getElementById('tab-btn-signin');
		const btnRegister = document.getElementById('tab-btn-register');
		const formSignin = document.getElementById('spa-login-form');
		const formRegister = document.getElementById('spa-register-form');
		const alertBox = document.getElementById('auth-alert');
		
		alertBox.classList.add('hidden');
		
		if (tab === 'signin') {
			btnSignin.classList.add('border-primary', 'font-bold', 'text-on-surface');
			btnSignin.classList.remove('border-transparent', 'font-medium', 'text-secondary');
			btnRegister.classList.remove('border-primary', 'font-bold', 'text-on-surface');
			btnRegister.classList.add('border-transparent', 'font-medium', 'text-secondary');
			formSignin.classList.remove('hidden');
			formRegister.classList.add('hidden');
		} else {
			btnRegister.classList.add('border-primary', 'font-bold', 'text-on-surface');
			btnRegister.classList.remove('border-transparent', 'font-medium', 'text-secondary');
			btnSignin.classList.remove('border-primary', 'font-bold', 'text-on-surface');
			btnSignin.classList.add('border-transparent', 'font-medium', 'text-secondary');
			formRegister.classList.remove('hidden');
			formSignin.classList.add('hidden');
		}
	}

	// Sign In Submit AJAX
	jQuery('#spa-login-form').on('submit', function(e) {
		e.preventDefault();
		const alertBox = jQuery('#auth-alert');
		const btn = jQuery(this).find('button[type="submit"]');
		
		alertBox.hide().removeClass('bg-red-100 text-red-800 bg-green-100 text-green-800');
		btn.prop('disabled', true).text('Signing in...');
		
		jQuery.post(dlmParams.ajaxUrl, {
			action: 'dlm_ajax_login',
			nonce: dlmParams.nonce,
			username: jQuery(this).find('input[name="username"]').val().trim(),
			password: jQuery(this).find('input[name="password"]').val()
		}, function(res) {
			if (res.success) {
				alertBox.addClass('bg-green-100 text-green-800').text('Authentication successful! Loading dashboard...').fadeIn();
				setTimeout(function() { window.location.reload(); }, 600);
			} else {
				alertBox.addClass('bg-red-100 text-red-800').html(res.data.message || 'Incorrect credentials.').fadeIn();
				btn.prop('disabled', false).html('Sign In <i class="fa-solid fa-arrow-right"></i>');
			}
		}).fail(function() {
			alertBox.addClass('bg-red-100 text-red-800').text('Connection timeout. Try again.').fadeIn();
			btn.prop('disabled', false).html('Sign In <i class="fa-solid fa-arrow-right"></i>');
		});
	});

	// Register Submit AJAX
	jQuery('#spa-register-form').on('submit', function(e) {
		e.preventDefault();
		const alertBox = jQuery('#auth-alert');
		const btn = jQuery(this).find('button[type="submit"]');
		
		alertBox.hide().removeClass('bg-red-100 text-red-800 bg-green-100 text-green-800');
		btn.prop('disabled', true).text('Creating account...');
		
		jQuery.post(dlmParams.ajaxUrl, {
			action: 'dlm_ajax_register',
			nonce: dlmParams.nonce,
			name: jQuery('#reg-name').val().trim(),
			email: jQuery('#reg-email').val().trim(),
			password: jQuery('#reg-password').val()
		}, function(res) {
			if (res.success) {
				alertBox.addClass('bg-green-100 text-green-800').text('Account created! Logging in...').fadeIn();
				setTimeout(function() { window.location.reload(); }, 700);
			} else {
				alertBox.addClass('bg-red-100 text-red-800').html(res.data.message || 'Registration failed.').fadeIn();
				btn.prop('disabled', false).text('Create Account');
			}
		}).fail(function() {
			alertBox.addClass('bg-red-100 text-red-800').text('Connection error. Try again.').fadeIn();
			btn.prop('disabled', false).text('Create Account');
		});
	});

	<?php if ( $is_logged_in ) : ?>
		// Mobile Left Sidebar Drawer Controls
		function openMobileDrawer() {
			jQuery('#mobile-sidebar-drawer').removeClass('-translate-x-full');
			jQuery('#mobile-sidebar-backdrop').removeClass('opacity-0 pointer-events-none').addClass('opacity-100 pointer-events-auto');
		}
		window.openMobileDrawer = openMobileDrawer;

		function closeMobileDrawer() {
			jQuery('#mobile-sidebar-drawer').addClass('-translate-x-full');
			jQuery('#mobile-sidebar-backdrop').removeClass('opacity-100 pointer-events-auto').addClass('opacity-0 pointer-events-none');
		}
		window.closeMobileDrawer = closeMobileDrawer;

		// Replay Onboarding Tour Trigger
		function replayOnboardingTour() {
			if (window.DLMOnboardingTour && typeof window.DLMOnboardingTour.replay === 'function') {
				window.DLMOnboardingTour.replay();
			}
		}
		window.replayOnboardingTour = replayOnboardingTour;

		// Universal Sidebar / Drawer Toggle Trigger (Works across All Devices)
		jQuery('#mobile-menu-trigger-btn').on('click', function(e) {
			e.preventDefault();
			if (window.innerWidth < 768) {
				openMobileDrawer();
			} else {
				const collapsed = jQuery('body').toggleClass('sidebar-collapsed').hasClass('sidebar-collapsed');
				localStorage.setItem('sidebar_collapsed', collapsed);
			}
		});

		jQuery('#mobile-nav-menu-btn').on('click', function(e) {
			e.preventDefault();
			openMobileDrawer();
		});

		jQuery('#mobile-drawer-close-btn, #mobile-sidebar-backdrop, .mobile-drawer-link').on('click', function(e) {
			closeMobileDrawer();
		});

		if (window.innerWidth >= 768 && localStorage.getItem('sidebar_collapsed') === 'true') {
			jQuery('body').addClass('sidebar-collapsed');
		}

		// -------------------------------------------------------------
		// MEMBER NOTIFICATIONS CONTROLLER
		// -------------------------------------------------------------
		function toggleNotificationsDropdown(forceState) {
			const dropdown = jQuery('#notification-dropdown');
			const btn = jQuery('#notification-btn');
			const isHidden = dropdown.hasClass('hidden');
			const shouldOpen = typeof forceState === 'boolean' ? forceState : isHidden;

			if (shouldOpen) {
				dropdown.removeClass('hidden');
				btn.attr('aria-expanded', 'true');
			} else {
				dropdown.addClass('hidden');
				btn.attr('aria-expanded', 'false');
			}
		}

		jQuery('#notification-btn').on('click', function(e) {
			e.stopPropagation();
			toggleNotificationsDropdown();
		});

		jQuery(document).on('click', function(e) {
			if (!jQuery('#notification-dropdown').is(e.target) && jQuery('#notification-dropdown').has(e.target).length === 0 && !jQuery('#notification-btn').is(e.target)) {
				toggleNotificationsDropdown(false);
			}
		});

		jQuery(document).on('keydown', function(e) {
			if (e.key === 'Escape' && !jQuery('#notification-dropdown').hasClass('hidden')) {
				toggleNotificationsDropdown(false);
				jQuery('#notification-btn').focus();
			}
		});

		// Mark single notification as read & route to link destination (SPA tab vs Real URL)
		jQuery(document).on('click', '.dlm-notif-row', function(e) {
			e.preventDefault();
			const row = jQuery(this);
			const notifId = row.data('id');
			const linkUrl = row.data('link') ? String(row.data('link')).trim() : '';
			const isRead = parseInt(row.data('is-read'), 10) === 1;

			// Visual update immediately
			if (!isRead) {
				row.removeClass('bg-primary-container/10 border border-primary/20 hover:bg-primary-container/20 font-semibold shadow-xs')
				   .addClass('bg-transparent hover:bg-surface-variant/30 text-on-surface-variant')
				   .data('is-read', 1);
				row.find('.notif-unread-dot').remove();

				// Decrement badge count
				const badge = jQuery('#notification-badge');
				const currentCount = parseInt(badge.text(), 10) || 0;
				const newCount = Math.max(0, currentCount - 1);
				if (newCount > 0) {
					badge.text(newCount).removeClass('hidden');
					jQuery('#notification-unread-pill').text(`${newCount} new`).removeClass('hidden');
					jQuery('#notification-btn').attr('aria-label', `Notifications (${newCount} unread)`);
				} else {
					badge.text('0').addClass('hidden');
					jQuery('#notification-unread-pill').addClass('hidden');
					jQuery('#notification-btn').attr('aria-label', 'Notifications (No unread)');
				}

				// AJAX update
				if (window.dlmParams && dlmParams.ajaxUrl && dlmParams.nonce) {
					jQuery.post(dlmParams.ajaxUrl, {
						action: 'dlm_mark_notification_read',
						nonce: dlmParams.nonce,
						notification_id: notifId
					});
				}
			}

			// Close dropdown
			toggleNotificationsDropdown(false);

			// Smart routing: SPA in-page tab switch vs full browser URL navigation
			if (linkUrl) {
				if (linkUrl.startsWith('#') || linkUrl.startsWith('tab:')) {
					const tab = linkUrl.replace(/^#tab-|^#|^tab:/, '');
					if (typeof showTab === 'function') {
						showTab(tab);
					}
				} else if (linkUrl.startsWith('http://') || linkUrl.startsWith('https://') || linkUrl.startsWith('/')) {
					window.location.href = linkUrl;
				}
			}
		});

		// Keyboard enter / space trigger for notification row
		jQuery(document).on('keydown', '.dlm-notif-row', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				jQuery(this).trigger('click');
			}
		});

		// Mark all as read button
		jQuery('#mark-all-read-btn, #clear-notifications-btn').on('click', function(e) {
			e.stopPropagation();
			const badge = jQuery('#notification-badge');
			badge.text('0').addClass('hidden');
			jQuery('#notification-unread-pill').addClass('hidden');
			jQuery('#notification-btn').attr('aria-label', 'Notifications (No unread)');

			jQuery('.dlm-notif-row').each(function() {
				jQuery(this).removeClass('bg-primary-container/10 border border-primary/20 hover:bg-primary-container/20 font-semibold shadow-xs')
				           .addClass('bg-transparent hover:bg-surface-variant/30 text-on-surface-variant')
				           .data('is-read', 1);
				jQuery(this).find('.notif-unread-dot').remove();
			});

			if (window.dlmParams && dlmParams.ajaxUrl && dlmParams.nonce) {
				jQuery.post(dlmParams.ajaxUrl, {
					action: 'dlm_mark_all_notifications_read',
					nonce: dlmParams.nonce
				});
			}

			if (typeof showToast === 'function') {
				showToast('All notifications marked as read.', 'info');
			}
		});

		// Lightweight background polling for unread count (every 60 seconds)
		setInterval(function() {
			if (!window.dlmParams || !dlmParams.ajaxUrl || !dlmParams.nonce) return;
			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_get_unread_notifications_count',
				nonce: dlmParams.nonce
			}, function(res) {
				if (res && res.success && typeof res.data.unread_count !== 'undefined') {
					const count = parseInt(res.data.unread_count, 10);
					const badge = jQuery('#notification-badge');
					const pill = jQuery('#notification-unread-pill');
					if (count > 0) {
						badge.text(count).removeClass('hidden');
						pill.text(`${count} new`).removeClass('hidden');
						jQuery('#notification-btn').attr('aria-label', `Notifications (${count} unread)`);
					} else {
						badge.text('0').addClass('hidden');
						pill.addClass('hidden');
						jQuery('#notification-btn').attr('aria-label', 'Notifications (No unread)');
					}
				}
			});
		}, 60000);

		// -------------------------------------------------------------
		// TAB NAVIGATION LAYER
		// -------------------------------------------------------------
		function showTab(tabName) {
			const validTabs = ['library', 'discover', 'journal', 'collections', 'membership', 'achievements', 'settings', 'checkout'];
			if (!validTabs.includes(tabName)) {
				tabName = 'library';
			}

			// Auto close mobile drawer on tab switch
			closeMobileDrawer();

			// Toggle views
			jQuery('.spa-page').addClass('hidden');
			jQuery('#section-' + tabName).removeClass('hidden');

			// Sync URL hash without page jump
			try {
				if (window.history && window.history.replaceState) {
					const currentUrl = new URL(window.location.href);
					currentUrl.hash = tabName;
					window.history.replaceState(null, '', currentUrl.toString());
				}
			} catch (err) {}

			// Update title
			let pageTitle = 'Library';
			if (tabName === 'discover') pageTitle = 'Discover';
			else if (tabName === 'journal') pageTitle = 'Reading Journal';
			else if (tabName === 'collections') pageTitle = 'Collections';
			else if (tabName === 'membership') pageTitle = 'Membership';
			else if (tabName === 'achievements') pageTitle = 'Achievements';
			else if (tabName === 'settings') pageTitle = 'Settings';
			else if (tabName === 'checkout') pageTitle = 'Checkout';
			
			jQuery('#top-bar-title').text(pageTitle);

			// Active classes on sidebar
			jQuery('.nav-tab-link').removeClass('bg-primary/10 text-primary font-semibold').addClass('text-secondary hover:bg-surface-container-high/50 hover:text-on-surface');
			jQuery('.nav-tab-link[data-tab="' + tabName + '"]').addClass('bg-primary/10 text-primary font-semibold').removeClass('text-secondary hover:bg-surface-container-high/50 hover:text-on-surface');

			// Active classes on mobile navigation
			jQuery('.mobile-nav-btn').removeClass('text-primary scale-110').addClass('text-secondary');
			jQuery('.mobile-nav-btn[data-tab="' + tabName + '"]').addClass('text-primary scale-110').removeClass('text-secondary');

			// Hide search bar if not Library or Discover
			if (tabName === 'library' || tabName === 'discover') {
				jQuery('#header-search-container').removeClass('opacity-0 pointer-events-none');
			} else {
				jQuery('#header-search-container').addClass('opacity-0 pointer-events-none');
			}

			// Redraw widgets
			if (tabName === 'journal') {
				renderJournalNotes();
			} else if (tabName === 'achievements') {
				paintWeeklyStrip();
				paintBadgesWall();
			}
			const mainContentEl = document.getElementById('dlm-main-content');
			if (mainContentEl) {
				mainContentEl.scrollTo({ top: 0, behavior: 'smooth' });
			} else {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		}
		window.showTab = showTab;

		// -------------------------------------------------------------
		// LIBRARY FILTERING & SEARCH
		// -------------------------------------------------------------
		function filterCategory(cat, btnEl) {
			showTab('library');
			if (btnEl) {
				jQuery('#category-chips button').removeClass('active-chip active');
				jQuery(btnEl).addClass('active-chip active');
			}

			const params = window.dlmParams || window.dlmDashboardParams || {};
			const favList = Array.isArray(params.favoriteBooks) ? params.favoriteBooks : [];

			jQuery('.book-card-el').each(function() {
				const rawCats = jQuery(this).data('categories') || '';
				const cardCat = rawCats.toString().toLowerCase().split(' ');
				const isFav = favList.includes(jQuery(this).data('book-id'));
				const readPct = parseInt(jQuery(this).data('pct'), 10) || 0;
				const targetCat = cat.toString().toLowerCase();

				if (targetCat === 'all') {
					jQuery(this).show();
				} else if (targetCat === 'favorites') {
					if (isFav) jQuery(this).show();
					else jQuery(this).hide();
				} else if (targetCat === 'continue') {
					if (readPct > 0 && readPct < 100) jQuery(this).show();
					else jQuery(this).hide();
				} else {
					if (cardCat.includes(targetCat)) jQuery(this).show();
					else jQuery(this).hide();
				}
			});
		}

		// Global library search
		jQuery('#global-search-input').on('input', function() {
			const query = jQuery(this).val().toLowerCase().trim();
			jQuery('.book-card-el').each(function() {
				const title = jQuery(this).data('title').toString().toLowerCase();
				const author = jQuery(this).data('author').toString().toLowerCase();
				if (title.indexOf(query) !== -1 || author.indexOf(query) !== -1) {
					jQuery(this).show();
				} else {
					jQuery(this).hide();
				}
			});
		});

		// Toggle book bookmark / favorite status
		function toggleFavoriteBook(bookId, btnEl) {
			const params = window.dlmParams || window.dlmDashboardParams || {};
			if (!params.ajaxUrl || !params.nonce) return;
			jQuery.post(params.ajaxUrl, {
				action: 'dlm_toggle_favorite',
				nonce: params.nonce,
				book_id: bookId
			}, function(res) {
				if (res.success) {
					params.favoriteBooks = res.data.favorites;
					if (window.dlmParams) window.dlmParams.favoriteBooks = res.data.favorites;
					if (window.dlmDashboardParams) window.dlmDashboardParams.favoriteBooks = res.data.favorites;
					const icon = jQuery(btnEl).find('i');
					if (res.data.is_favorite) {
						icon.removeClass('fa-regular').addClass('fa-solid');
						Aurelian.toast('Book bookmarked in Favorites');
					} else {
						icon.removeClass('fa-solid').addClass('fa-regular');
						Aurelian.toast('Book removed from Favorites');
					}
					syncSmartShelvesCount();
				}
			});
		}

		// Sync helper
		function syncSmartShelvesCount() {
			const params = window.dlmParams || window.dlmDashboardParams || {};
			const favList = Array.isArray(params.favoriteBooks) ? params.favoriteBooks : [];
			const notesList = Array.isArray(params.userNotes) ? params.userNotes : [];
			jQuery('#favorites-count').text(favList.length + ' books');
			
			let readingCount = 0;
			jQuery('.book-card-el').each(function() {
				const readPct = parseInt(jQuery(this).data('pct'), 10);
				if (readPct > 0 && readPct < 100) readingCount++;
			});
			jQuery('#currently-reading-count').text(readingCount + ' books');
			jQuery('#journal-logs-count').text(notesList.length + ' logs');
		}

		// -------------------------------------------------------------
		// READING JOURNAL LOGIC (CRUD AJAX)
		// -------------------------------------------------------------
		function renderJournalNotes() {
			const container = jQuery('#journal-notes-grid');
			container.html('');

			const params = window.dlmParams || window.dlmDashboardParams || {};
			const notesList = Array.isArray(params.userNotes) ? params.userNotes : [];

			if (notesList.length === 0) {
				container.html(`
					<div class="col-span-full py-16 text-center bg-white border border-outline-variant/30 rounded-3xl book-card-shadow">
						<i class="fa-solid fa-pen-to-square text-secondary/40 text-4xl mb-3 block"></i>
						<p class="font-bold text-on-surface">No entry logs found</p>
						<p class="text-xs text-secondary mt-1 max-w-xs mx-auto">Create reading journal entries to save your insights and unlock XP awards!</p>
					</div>
				`);
				return;
			}

			dlmParams.userNotes.forEach(function(note) {
				container.append(`
					<div class="bg-white rounded-2xl p-6 book-card-shadow border border-outline-variant/30 flex flex-col h-full relative">
						<div class="flex justify-between items-start mb-4">
							<span class="text-[11px] font-bold text-secondary/60 tracking-wider">${note.date}</span>
							<div class="flex gap-2">
								<button onclick="openNoteModal('edit', '${note.id}')" class="w-8 h-8 rounded-full hover:bg-surface-container flex items-center justify-center text-secondary transition-colors" title="Edit Note"><i class="fa-solid fa-pen text-sm"></i></button>
								<button onclick="deleteJournalNote('${note.id}')" class="w-8 h-8 rounded-full hover:bg-surface-container flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors" title="Delete Note"><i class="fa-solid fa-trash-can text-sm"></i></button>
							</div>
						</div>
						<div class="mb-4">
							<h3 class="text-primary font-bold text-base leading-snug line-clamp-1">${note.title}</h3>
							<p class="text-xs text-secondary italic line-clamp-1">${note.chapter || 'General Notes'}</p>
						</div>
						<p class="text-on-surface text-sm leading-relaxed line-clamp-4 flex-grow mb-6 whitespace-pre-line">${note.content}</p>
						<div class="flex items-center gap-2 pt-4 border-t border-outline-variant/10 text-[11px] font-bold text-secondary uppercase tracking-tight">
							<i class="fa-solid fa-tag text-primary"></i>
							<span>${note.tag}</span>
							<span class="ml-auto">${note.readTime}</span>
						</div>
					</div>
				`);
			});
		}

		function openNoteModal(mode, noteId = '') {
			jQuery('#note-modal-form')[0].reset();
			jQuery('#note-id-input').val('');
			
			if (mode === 'add') {
				jQuery('#note-modal-title').text('New Journal Entry');
				jQuery('#note-id-input').val('');
			} else {
				jQuery('#note-modal-title').text('Edit Journal Entry');
				const note = dlmParams.userNotes.find(n => n.id === noteId);
				if (note) {
					jQuery('#note-id-input').val(note.id);
					jQuery('#note-book-select').val(note.title);
					jQuery('#note-chapter-input').val(note.chapter);
					jQuery('#note-tag-select').val(note.tag);
					jQuery('#note-content-input').val(note.content);
				}
			}
			jQuery('#journal-note-modal').removeClass('hidden');
		}

		function closeNoteModal() {
			jQuery('#journal-note-modal').addClass('hidden');
		}

		function saveJournalNote(e) {
			e.preventDefault();
			const id = jQuery('#note-id-input').val();
			const noteAction = id ? 'edit' : 'add';
			const title = jQuery('#note-book-select').val();
			const chapter = jQuery('#note-chapter-input').val();
			const tag = jQuery('#note-tag-select').val();
			const content = jQuery('#note-content-input').val().trim();

			if (!title || !content) {
				alert('Please complete all required fields.');
				return;
			}

			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_manage_journal_notes',
				nonce: dlmParams.nonce,
				note_action: noteAction,
				id: id,
				title: title,
				chapter: chapter,
				tag: tag,
				content: content
			}, function(res) {
				if (res.success) {
					dlmParams.userNotes = res.data.notes;
					renderJournalNotes();
					closeNoteModal();
					syncSmartShelvesCount();

					if (noteAction === 'add') {
						Aurelian.toast('Note entry saved! · +5 XP');
						// Award XP on adding note
						const state = Aurelian.loadState();
						const leveled = Aurelian.addXP(state, 5);
						Aurelian.saveState(state);
						Aurelian.syncStreakBadges(state);
						if (leveled) {
							setTimeout(() => Aurelian.toast('✨ Level up! You reached Level ' + state.level, { accent: true }), 700);
						}
					} else {
						Aurelian.toast('Note updated successfully');
					}
				} else {
					alert(res.data.message || 'An error occurred saving note.');
				}
			}).fail(function() {
				alert('Server timeout. Try again.');
			});
		}

		// Delete Log
		function deleteJournalNote(noteId) {
			if (!confirm('Are you sure you want to delete this journal note?')) return;

			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_manage_journal_notes',
				nonce: dlmParams.nonce,
				note_action: 'delete',
				id: noteId
			}, function(res) {
				if (res.success) {
					dlmParams.userNotes = res.data.notes;
					renderJournalNotes();
					Aurelian.toast('Note removed');
					syncSmartShelvesCount();
				}
			});
		}

		// -------------------------------------------------------------
		// SETTINGS / PROFILE AJAX EDIT
		// -------------------------------------------------------------
		function updateProfileSettings(e) {
			e.preventDefault();
			const form = jQuery('#profile-update-form');
			const alertBox = jQuery('#profile-alert');
			const btn = form.find('button[type="submit"]');

			alertBox.hide().removeClass('bg-red-100 text-red-800 bg-green-100 text-green-800');
			btn.prop('disabled', true).text('Saving changes...');

			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_update_profile',
				nonce: dlmParams.nonce,
				display_name: form.find('input[name="display_name"]').val().trim(),
				user_email: form.find('input[name="user_email"]').val().trim(),
				new_password: jQuery('#profile-new-password').val()
			}, function(res) {
				if (res.success) {
					alertBox.addClass('bg-green-100 text-green-800').html(res.data.message).fadeIn();
					jQuery('#profile-display-name-header').text(form.find('input[name="display_name"]').val());
					jQuery('#profile-new-password').val('');
				} else {
					alertBox.addClass('bg-red-100 text-red-800').html(res.data.message).fadeIn();
				}
				btn.prop('disabled', false).text('Save Changes');
			}).fail(function() {
				alertBox.addClass('bg-red-100 text-red-800').text('Failed connection. Try again.').fadeIn();
				btn.prop('disabled', false).text('Save Changes');
			});
		}

		function uploadAvatarImage(input) {
			if (input.files && input.files[0]) {
				const file = input.files[0];
				const formData = new FormData();
				formData.append('action', 'dlm_upload_avatar');
				formData.append('nonce', dlmParams.nonce);
				formData.append('avatar', file);

				Aurelian.toast('Uploading avatar...');

				jQuery.ajax({
					url: dlmParams.ajaxUrl,
					type: 'POST',
					data: formData,
					contentType: false,
					processData: false,
					success: function(res) {
						if (res.success && res.data.avatar_url) {
							jQuery('#settings-avatar-preview, #header-avatar-img').attr('src', res.data.avatar_url);
							Aurelian.toast('Profile photo updated successfully!');
						} else {
							alert(res.data.message || 'Avatar upload failed.');
						}
					},
					error: function() {
						alert('Connection timeout upload avatar.');
					}
				});
			}
		}

		// -------------------------------------------------------------
		// CHECKOUT BILLING GATEWAY FLOW
		// -------------------------------------------------------------
		let checkoutInterval = 'monthly';
		let checkoutMethod = '<?php echo esc_js( $default_gateway ); ?>';
		let checkoutPrice = '<?php echo esc_js( $price_monthly ); ?>';

		function goToCheckout(interval, price, planName = '') {
			if (window.dlmParams.isPendingApproval) {
				Aurelian.toast('You already have a payment pending approval.', { duration: 4000 });
				return;
			}
			checkoutInterval = interval;
			checkoutPrice = price;

			let planLabel = planName || (interval.charAt(0).toUpperCase() + interval.slice(1) + ' Plan');
			let sumTitle = planName || (interval.charAt(0).toUpperCase() + interval.slice(1) + ' Subscription');

			jQuery('#checkout-plan-name').text(planLabel);
			jQuery('#checkout-summary-title').text(sumTitle);
			jQuery('#checkout-summary-price, #checkout-calc-subtotal, #checkout-calc-total').text('<?php echo esc_js( $currency ); ?>' + parseFloat(price).toFixed(2));

			showTab('checkout');
			
			const defaultMethod = '<?php echo esc_js( $default_gateway ); ?>';
			if (defaultMethod) {
				toggleCheckoutPaymentMethod(defaultMethod);
			}
		}

		function toggleCheckoutPaymentMethod(method) {
			checkoutMethod = method;
			jQuery('.method-btn').removeClass('border-primary').addClass('border-outline-variant/30');
			jQuery('.method-btn svg, .method-btn i').removeClass('text-primary').addClass('text-secondary');
			jQuery('.method-btn div.border-primary').addClass('border-outline-variant').removeClass('border-primary');
			jQuery('.method-btn #woocommerce-dot, .method-btn #stripe-dot, .method-btn #paypal-dot, .method-btn #manual-dot').addClass('hidden');

			const activeBtn = jQuery('#checkout-method-' + method);
			if (activeBtn.length) {
				activeBtn.addClass('border-primary').removeClass('border-outline-variant/30');
				activeBtn.find('i').addClass('text-primary').removeClass('text-secondary');
				activeBtn.find('#' + method + '-dot').removeClass('hidden');
				activeBtn.find('div.border-outline-variant').addClass('border-primary').removeClass('border-outline-variant');
			}

			jQuery('#woocommerce-checkout-container, #stripe-checkout-container, #paypal-checkout-container, #manual-checkout-container').addClass('hidden');
			jQuery('#' + method + '-checkout-container').removeClass('hidden');

			if (method === 'paypal') {
				setupPayPalSDKInstance();
			}
		}

		function triggerWooCommerceSubscriptionOrder() {
			const btn = jQuery('#wc-checkout-btn');
			btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> <span>Connecting to WooCommerce...</span>');
			Aurelian.toast('Initializing secure WooCommerce checkout...', { accent: true });

			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_wc_create_subscription_order',
				nonce: dlmParams.nonce,
				interval: checkoutInterval
			}, function(res) {
				if (res.success && res.data && res.data.redirect) {
					window.location.href = res.data.redirect;
				} else {
					const msg = (res && res.data && res.data.message) ? res.data.message : 'Unable to proceed to WooCommerce checkout.';
					Aurelian.toast(msg);
					btn.prop('disabled', false).html('<span>Proceed to WooCommerce Checkout</span> <i class="fa-solid fa-arrow-right"></i>');
				}
			}).fail(function() {
				Aurelian.toast('Checkout connection timeout.');
				btn.prop('disabled', false).html('<span>Proceed to WooCommerce Checkout</span> <i class="fa-solid fa-arrow-right"></i>');
			});
		}

		function triggerStripeCheckoutSession() {
			const btn = jQuery('#stripe-checkout-container button');
			btn.prop('disabled', true).text('Connecting to Stripe securely...');

			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_stripe_create_session',
				nonce: dlmParams.nonce,
				interval: checkoutInterval
			}, function(res) {
				if (res.success && res.data.url) {
					window.location.href = res.data.url;
				} else {
					alert(res.data.message || 'Stripe initialization failed.');
					btn.prop('disabled', false).html('<span>Complete Secure Checkout</span> <i class="fa-solid fa-arrow-right"></i>');
				}
			}).fail(function() {
				alert('Stripe server timeout.');
				btn.prop('disabled', false).html('<span>Complete Secure Checkout</span> <i class="fa-solid fa-arrow-right"></i>');
			});
		}

		function setupPayPalSDKInstance() {
			jQuery('#paypal-button-container').html('');
			if (typeof paypal === 'undefined') {
				jQuery('#paypal-button-container').html('<p class="text-xs text-red-500 font-semibold">PayPal failed to initialize. Confirm Client ID setting.</p>');
				return;
			}

			paypal.Buttons({
				style: {
					shape: 'rect',
					color: 'gold',
					layout: 'vertical',
					label: checkoutInterval === 'lifetime' ? 'checkout' : 'subscribe'
				},
				createSubscription: function(data, actions) {
					let paypalPlanId = (checkoutInterval === 'yearly') ? 
						'<?php echo esc_js( $paypal_yearly_plan ); ?>' : 
						'<?php echo esc_js( $paypal_monthly_plan ); ?>';

					if (!paypalPlanId) {
						alert('PayPal Subscription Plan ID is missing in settings.');
						return;
					}

					return actions.subscription.create({
						plan_id: paypalPlanId
					});
				},
				createOrder: function(data, actions) {
					if (checkoutInterval === 'lifetime') {
						return actions.order.create({
							purchase_units: [{
								amount: {
									value: parseFloat(checkoutPrice).toFixed(2),
									currency_code: 'USD'
								},
								description: 'Bridgeway36 Digital Library Lifetime Access'
							}]
						});
					}
				},
				onApprove: function(data, actions) {
					jQuery('#paypal-button-container').html('<p class="text-xs text-primary font-bold">Verifying payment with server...</p>');
					const txnId = (checkoutInterval === 'lifetime') ? data.orderID : data.subscriptionID;

					jQuery.post(dlmParams.ajaxUrl, {
						action: 'dlm_paypal_create_subscription',
						nonce: dlmParams.nonce,
						subscription_id: txnId,
						interval: checkoutInterval
					}, function(res) {
						if (res.success && res.data.redirect) {
							Aurelian.toast('PayPal verified! Access granted');
							const state = Aurelian.loadState();
							Aurelian.awardBadge(state, 'member', 'Archive Member');
							Aurelian.addXP(state, 50);
							Aurelian.saveState(state);
							
							setTimeout(function() { window.location.href = res.data.redirect; }, 1000);
						} else {
							alert(res.data.message || 'PayPal capture verify failed.');
						}
					}).fail(function() {
						alert('PayPal sync server timeout.');
					});
				}
			}).render('#paypal-button-container');
		}

		function triggerManualPaymentSubmission() {
			const ref = jQuery('#checkout-manual-ref').val().trim();
			const btn = jQuery('#manual-checkout-container button');

			if (!ref) {
				alert('Please supply the wire reference transfer code.');
				return;
			}

			btn.prop('disabled', true).text('Submitting verification code...');

			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_submit_manual_payment',
				nonce: dlmParams.nonce,
				interval: checkoutInterval,
				reference: ref
			}, function(res) {
				if (res.success && res.data.redirect) {
					Aurelian.toast('Bank reference registered successfully!');
					setTimeout(function() { window.location.href = res.data.redirect; }, 1000);
				} else {
					alert(res.data.message || 'Verification submit failed.');
					btn.prop('disabled', false).html('<span>Submit Reference Code</span> <i class="fa-solid fa-arrow-right"></i>');
				}
			}).fail(function() {
				alert('Server timeout. Try again.');
				btn.prop('disabled', false).html('<span>Submit Reference Code</span> <i class="fa-solid fa-arrow-right"></i>');
			});
		}

		// -------------------------------------------------------------
		// GAMIFICATION PERSISTENCE & LOCALSTORAGE ENGINE (meta-synced)
		// -------------------------------------------------------------
		function paintWeeklyStrip() {
			const s = Aurelian.loadState();
			const days = document.querySelectorAll('#week-strip [data-day-offset]');
			const todayIdx = (new Date().getDay() + 6) % 7; // Monday=0
			days.forEach(function(el) {
				const offset = parseInt(el.getAttribute('data-day-offset'), 10);
				const isToday = offset === todayIdx;
				const isPastActive = offset < todayIdx && (todayIdx - offset) < s.streak;
				
				el.className = "aspect-square rounded-xl flex flex-col items-center justify-center gap-1 transition-colors p-2";
				if (isToday && s.streak > 0) {
					el.classList.add('bg-primary', 'text-white');
					el.querySelector('i').className = "fa-solid fa-fire text-lg text-white";
					el.querySelector('span').className = "text-[10px] font-bold uppercase text-white/80";
				} else if (isPastActive) {
					el.classList.add('bg-primary/20', 'text-primary');
					el.querySelector('i').className = "fa-solid fa-fire text-lg text-primary";
					el.querySelector('span').className = "text-[10px] font-bold uppercase text-primary/80";
				} else {
					el.classList.add('bg-surface-container-low', 'text-secondary');
					el.querySelector('i').className = "fa-solid fa-fire text-lg text-secondary/40";
					el.querySelector('span').className = "text-[10px] font-bold uppercase text-secondary/60";
				}
			});
		}

		function paintBadgesWall() {
			const s = Aurelian.loadState();
			document.querySelectorAll('#badge-wall [data-badge-id]').forEach(function(el) {
				const id = el.getAttribute('data-badge-id');
				const earned = s.badges.some(function(b) { return b.id === id; });
				if (earned) {
					el.classList.remove('opacity-40', 'grayscale');
				} else {
					el.classList.add('opacity-40', 'grayscale');
				}
			});
		}

		// Currently reading shelves loader
		function renderContinueReadingShelf() {
			const grid = jQuery('#continue-reading-grid');
			grid.html('');

			let added = 0;
			jQuery('.book-card-el').each(function() {
				const bookId = jQuery(this).data('book-id');
				const title = jQuery(this).find('h5').text();
				const author = jQuery(this).find('p').text();
				const cover = jQuery(this).find('img').attr('src');
				const pct = parseInt(jQuery(this).data('pct'), 10);

				if (pct > 0 && pct < 100 && added < 4) {
					grid.append(`
						<div onclick="Aurelian.openBook(${bookId}, '${title.replace(/'/g, "\\'")}')" class="flex gap-4 p-4 bg-white border border-outline-variant/30 rounded-2xl book-card-shadow group cursor-pointer">
							<div class="w-20 h-28 flex-shrink-0 rounded-lg overflow-hidden shadow-md">
								<img class="w-full h-full object-cover" src="${cover || ''}">
							</div>
							<div class="flex flex-col justify-between py-1 flex-1">
								<div>
									<h4 class="font-bold text-on-surface line-clamp-1 group-hover:text-primary transition-colors text-sm">${title}</h4>
									<p class="text-xs text-secondary">${author}</p>
								</div>
								<div class="space-y-1.5">
									<div class="flex justify-between text-[10px] font-bold text-secondary">
										<span>${pct}% READ</span>
									</div>
									<div class="w-full h-1 bg-surface-container-highest rounded-full overflow-hidden">
										<div class="bg-primary w-[${pct}%] h-full rounded-full"></div>
									</div>
								</div>
							</div>
						</div>
					`);
					added++;
				}
			});

			if (added === 0) {
				jQuery('#continue-reading-shelf').addClass('hidden');
			} else {
				jQuery('#continue-reading-shelf').removeClass('hidden');
			}
		}
	<?php endif; ?>
</script>

<?php if ( $is_logged_in ) : ?>
	<script>
		// Override client-side state engine to perform background server sync
		(function (global) {
			const STORE_KEY = 'aurelian_state_v1';
			const DAY_MS = 24 * 60 * 60 * 1000;

			function todayKey(d = new Date()) {
				return d.toISOString().slice(0, 10);
			}

			function loadState() {
				const params = window.dlmParams || window.dlmDashboardParams || {};
				let state = params.userAchievements;
				if (!state || Object.keys(state).length === 0) {
					try {
						state = JSON.parse(localStorage.getItem(STORE_KEY));
					} catch (e) {
						state = null;
					}
				}
				
				if (!state || Object.keys(state).length === 0) {
					state = {
						streak: 0,
						lastVisit: null,
						xp: 0,
						level: 1,
						booksOpened: 0,
						badges: [],
						goalMinutesToday: 0,
						dailyGoal: 20
					};
				}
				return state;
			}

			// Sync localStorage to server User Meta
			function saveState(state) {
				localStorage.setItem(STORE_KEY, JSON.stringify(state));
				
				const params = window.dlmParams || window.dlmDashboardParams || {};
				if (!params.ajaxUrl || !params.nonce) return;

				jQuery.post(params.ajaxUrl, {
					action: 'dlm_sync_achievements',
					nonce: params.nonce,
					state: JSON.stringify(state)
				});
			}

			function xpForNextLevel(level) {
				return level * 150;
			}

			function addXP(state, amount) {
				state.xp += amount;
				const needed = xpForNextLevel(state.level);
				if (state.xp >= needed) {
					state.xp -= needed;
					state.level += 1;
					return true; // leveled up
				}
				return false;
			}

			function awardBadge(state, id, label) {
				if (!state.badges.some(b => b.id === id)) {
					state.badges.push({ id, label, earned: todayKey() });
					return true;
				}
				return false;
			}

			function bumpStreakOnVisit(state) {
				const today = todayKey();
				if (state.lastVisit === today) {
					return { changed: false };
				}
				const y = new Date(Date.now() - DAY_MS);
				const wasYesterday = state.lastVisit === todayKey(y);
				if (wasYesterday) {
					state.streak += 1;
				} else {
					state.streak = 1;
				}
				state.lastVisit = today;
				state.goalMinutesToday = 0;
				let leveled = addXP(state, 10);
				let newBadge = null;
				if (state.streak === 3) newBadge = awardBadge(state, 'streak-3', '3 Day Streak') ? '3 Day Streak' : null;
				if (state.streak === 7) newBadge = awardBadge(state, 'streak-7', '7 Day Streak') ? '7 Day Streak' : null;
				return { changed: true, leveled, newBadge };
			}

			function ensureToastRoot() {
				let root = document.getElementById('aurelian-toast-root');
				if (!root) {
					root = document.createElement('div');
					root.id = 'aurelian-toast-root';
					root.style.cssText = [
						'position:fixed', 'top:20px', 'left:50%', 'transform:translateX(-50%)',
						'z-index:9999', 'display:flex', 'flex-direction:column', 'gap:8px',
						'align-items:center', 'pointer-events:none'
					].join(';');
					document.body.appendChild(root);
				}
				return root;
			}

			function toast(message, opts = {}) {
				const root = ensureToastRoot();
				const el = document.createElement('div');
				el.textContent = message;
				el.style.cssText = [
					'background:rgba(26,28,28,0.95)', 'color:#fff', 'padding:10px 20px',
					'border-radius:999px', 'font-family:Inter,sans-serif', 'font-size:13px',
					'font-weight:600', 'box-shadow:0 8px 24px rgba(0,0,0,0.15)',
					'backdrop-filter:blur(8px)', 'opacity:0', 'transform:translateY(-10px)',
					'transition:all .3s ease-out', 'white-space:nowrap'
				].join(';');
				if (opts.accent) {
					el.style.background = '#855300';
				}
				root.appendChild(el);
				requestAnimationFrame(() => {
					el.style.opacity = '1';
					el.style.transform = 'translateY(0)';
				});
				setTimeout(() => {
					el.style.opacity = '0';
					el.style.transform = 'translateY(-10px)';
					setTimeout(() => el.remove(), 400);
				}, opts.duration || 3200);
			}

			function syncStreakBadges(state) {
				document.querySelectorAll('[id^="streak-count"]').forEach(el => {
					el.textContent = `${state.streak} day streak`;
				});
				jQuery('#streak-num').text(state.streak);
				jQuery('#badge-count').text(state.badges.length);
				jQuery('#xp-level').text(`Lv. ${state.level}`);
				
				const nextLevelXP = xpForNextLevel(state.level);
				const pct = Math.min(100, Math.round((state.xp / nextLevelXP) * 100));
				jQuery('#xp-bar').css('width', pct + '%');
				jQuery('#xp-fraction').text(`${state.xp} / ${nextLevelXP} XP`);
			}

			function downloadBook(bookId) {
				toast('Requesting secure download token...', { accent: true });
				jQuery.ajax({
					url: '<?php echo esc_js( esc_url_raw( rest_url( 'dlm/v1/book/' ) ) ); ?>' + bookId + '/download-token',
					method: 'GET',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>');
					},
					success: function(res) {
						if (res && res.download_url) {
							toast('Starting secure download...', { accent: true });
							window.location.href = res.download_url;
						} else {
							toast('Failed to generate download token.');
						}
					},
					error: function(xhr) {
						const err = xhr.responseJSON ? xhr.responseJSON.message : 'Download permission denied.';
						toast(err);
					}
				});
			}

			function buyBook(bookId) {
				toast('Preparing direct checkout...', { accent: true });
				jQuery.post('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
					action: 'dlm_wc_create_book_order',
					nonce: '<?php echo esc_js( $dlm_public_nonce ); ?>',
					book_id: bookId
				}, function(res) {
					if (res.success && res.data && res.data.redirect) {
						window.location.href = res.data.redirect;
					} else {
						const msg = (res && res.data && res.data.message) ? res.data.message : 'Unable to initiate purchase.';
						toast(msg);
					}
				}).fail(function() {
					toast('Connection timeout. Please try again.');
				});
			}

			function openBook(bookId, title) {
				const card = jQuery(`.book-card-el[data-book-id="${bookId}"]`);
				const access = card.length ? card.attr('data-user-access') : null;
				const accessType = card.length ? card.attr('data-access-type') : 'subscription_only';

				if (access === 'locked') {
					if (accessType === 'purchase_only' || accessType === 'hybrid') {
						buyBook(bookId);
						return;
					}
					Aurelian.toast('Access locked. Select a membership plan first', { duration: 4000 });
					showTab('membership');
					return;
				}

				const state = loadState();
				state.booksOpened += 1;
				const leveled = addXP(state, 15);
				let newBadge = null;
				if (state.booksOpened === 1) newBadge = awardBadge(state, 'first-book', 'First Chapter') ? 'First Chapter' : null;
				
				saveState(state);
				toast(`Opening “${title}” · +15 XP`, { accent: true });
				
				if (newBadge) {
					setTimeout(() => toast(`🏅 Badge unlocked: ${newBadge}`, { accent: true, duration: 3800 }), 500);
				} else if (leveled) {
					setTimeout(() => toast(`✨ Level up! You're now Level ${state.level}`, { accent: true, duration: 3800 }), 500);
				}

				setTimeout(() => {
					window.location.href = '<?php echo esc_js( home_url( '/read/' ) ); ?>' + bookId + '/';
				}, 650);
			}

			function surpriseMe() {
				const bookCards = jQuery('.book-card-el');
				if (bookCards.length === 0) return;
				const randIndex = Math.floor(Math.random() * bookCards.length);
				const target = jQuery(bookCards[randIndex]);
				const id = target.data('book-id');
				const title = target.data('title');
				
				toast(`Selecting random recommendation...`, { duration: 1500 });
				setTimeout(function() {
					openBook(id, title);
				}, 1000);
			}

			function handlePaymentRedirectStatus() {
				const urlParams = new URLSearchParams(window.location.search);
				const payment = urlParams.get('payment');
				if (!payment) return;

				// Clean up url parameters without reloading
				window.history.replaceState({}, document.title, window.location.pathname);

				const alertContainer = jQuery('#membership-payment-alert');
				const alertBox = alertContainer.find('.alert-box-container');
				const iconContainer = jQuery('#membership-payment-alert-icon');
				const titleEl = jQuery('#membership-payment-alert-title');
				const descEl = jQuery('#membership-payment-alert-desc');

				// Reset state classes
				alertBox.removeClass('bg-[#e6f4ea] border-green-200 text-[#137333] bg-[#fef7e0] border-amber-200 text-[#b06000] bg-surface-container border-outline-variant/30 text-secondary bg-red-50 border-red-200 text-red-800');
				iconContainer.removeClass().addClass('w-12 h-12 rounded-full flex items-center justify-center text-xl flex-shrink-0 shadow-sm');

				if (payment === 'active' || payment === 'success') {
					alertBox.addClass('bg-[#e6f4ea] border-green-200 text-[#137333]');
					iconContainer.addClass('bg-green-100').html('<i class="fa-solid fa-check"></i>');
					titleEl.text('Payment Successful!');
					descEl.text('Thank you! Your subscription is now active. You have been granted full reading access to the entire digital library.');
				} else if (payment === 'pending') {
					alertBox.addClass('bg-[#fef7e0] border-amber-200 text-[#b06000]');
					iconContainer.addClass('bg-amber-100').html('<i class="fa-solid fa-clock"></i>');
					titleEl.text('Verification Pending');
					descEl.text('Your bank transfer reference code has been recorded. An administrator will verify the transaction and activate your account shortly.');
				} else if (payment === 'cancelled' || payment === 'cancel') {
					alertBox.addClass('bg-surface-container border-outline-variant/30 text-secondary');
					iconContainer.addClass('bg-surface-container-high').html('<i class="fa-solid fa-xmark"></i>');
					titleEl.text('Payment Cancelled');
					descEl.text('The checkout process was cancelled. No charges were made.');
				} else if (payment === 'faild' || payment === 'failed') {
					alertBox.addClass('bg-red-50 border-red-200 text-red-800');
					iconContainer.addClass('bg-red-100').html('<i class="fa-solid fa-triangle-exclamation"></i>');
					titleEl.text('Payment Failed');
					descEl.text('We were unable to process your payment. Please try again or choose a different payment method.');
				} else {
					showTab('library');
					return;
				}

				alertContainer.removeClass('hidden').fadeIn();
				showTab('membership');
			}

			function initTabFromUrl() {
				const validTabs = ['library', 'discover', 'journal', 'collections', 'membership', 'achievements', 'settings', 'checkout'];
				let targetTab = null;

				// 1. Check URL query parameter for plan (?plan=yearly, ?plan=lifetime, ?plan=monthly)
				try {
					const urlParams = new URLSearchParams(window.location.search);
					const planParam = urlParams.get('plan');
					if (planParam) {
						const rawPackages = <?php echo json_encode( $active_packages ); ?>;
						let targetPkg = rawPackages[planParam] || Object.values(rawPackages).find(p => (p.interval === planParam || p.id === planParam));
						if (targetPkg) {
							const pkgPrice = targetPkg.price || '0.00';
							const pkgName = targetPkg.name || planParam;
							const pkgInterval = targetPkg.interval || targetPkg.id || planParam;
							goToCheckout(pkgInterval, pkgPrice, pkgName);
							return;
						} else {
							const defaultPrices = {
								monthly: '<?php echo esc_js( $price_monthly ); ?>',
								yearly: '<?php echo esc_js( $price_yearly ); ?>',
								lifetime: '<?php echo esc_js( $price_lifetime ); ?>'
							};
							const pKey = planParam.toLowerCase();
							const pPrice = defaultPrices[pKey] || '9.99';
							const pName = planParam.charAt(0).toUpperCase() + planParam.slice(1) + ' Plan';
							goToCheckout(pKey, pPrice, pName);
							return;
						}
					}
				} catch (e) {}

				// 2. Check URL hash (#membership, #library, etc.)
				if (window.location.hash) {
					const hash = window.location.hash.replace('#', '').toLowerCase().trim();
					if (validTabs.includes(hash)) {
						targetTab = hash;
					}
				}

				// 3. Check URL query parameter (?tab=membership, etc.)
				if (!targetTab) {
					try {
						const urlParams = new URLSearchParams(window.location.search);
						const tabParam = urlParams.get('tab');
						if (tabParam && validTabs.includes(tabParam.toLowerCase().trim())) {
							targetTab = tabParam.toLowerCase().trim();
						}
					} catch (e) {}
				}

				if (targetTab && targetTab !== 'library') {
					showTab(targetTab);
				}
			}

			function init() {
				const state = loadState();
				const result = bumpStreakOnVisit(state);
				saveState(state);
				syncStreakBadges(state);
				renderContinueReadingShelf();
				syncSmartShelvesCount();

				if (result.changed && state.streak > 1) {
					toast(`🔥 Day ${state.streak} streak — welcome back!`, { accent: true });
				}
				if (result.newBadge) {
					setTimeout(() => toast(`🏅 Badge unlocked: ${result.newBadge}`, { accent: true, duration: 3800 }), 900);
				} else if (result.leveled) {
					setTimeout(() => toast(`✨ Level up! You reached Level ${state.level}`, { accent: true, duration: 3800 }), 900);
				}

				handlePaymentRedirectStatus();
				initTabFromUrl();

				// Responsive back/forward and hash navigation listener
				window.addEventListener('hashchange', function() {
					if (window.location.hash) {
						const h = window.location.hash.replace('#', '').toLowerCase().trim();
						const valid = ['library', 'discover', 'journal', 'collections', 'membership', 'achievements', 'settings', 'checkout'];
						if (valid.includes(h)) {
							showTab(h);
						}
					}
				});

				// Real-time countdown timer for scheduled release books
				function parseReleaseTime(str) {
					if (!str) return 0;
					let s = String(str).trim();
					if (s.indexOf(' ') > 0 && s.indexOf('T') === -1) {
						s = s.replace(' ', 'T');
					}
					const parsed = Date.parse(s);
					if (!isNaN(parsed) && parsed > 0) {
						return parsed;
					}
					const d = new Date(str);
					const t = d.getTime();
					return isNaN(t) ? 0 : t;
				}

				function updateCountdowns() {
					const now = new Date().getTime();
					jQuery('.dlm-countdown-timer').each(function() {
						const $timer = jQuery(this);
						const releaseIso = $timer.attr('data-release-time') || $timer.data('release-time');
						if (!releaseIso) return;

						const targetTime = parseReleaseTime(releaseIso);
						if (!targetTime) return;

						const distance = targetTime - now;

						if (distance <= 0) {
							$timer.find('.countdown-days').text('00');
							$timer.find('.countdown-hours').text('00');
							$timer.find('.countdown-minutes').text('00');
							$timer.find('.countdown-seconds').text('00');
							$timer.find('.countdown-digits').text('Available Now!').removeClass('text-amber-900').addClass('text-green-700 font-bold');
							return;
						}

						const days = Math.floor(distance / (1000 * 60 * 60 * 24));
						const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
						const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
						const seconds = Math.floor((distance % (1000 * 60)) / 1000);

						const dStr = days < 10 ? '0' + days : '' + days;
						const hStr = hours < 10 ? '0' + hours : '' + hours;
						const mStr = minutes < 10 ? '0' + minutes : '' + minutes;
						const sStr = seconds < 10 ? '0' + seconds : '' + seconds;

						$timer.find('.countdown-days').text(dStr);
						$timer.find('.countdown-hours').text(hStr);
						$timer.find('.countdown-minutes').text(mStr);
						$timer.find('.countdown-seconds').text(sStr);
					});
				}

				updateCountdowns();
				setInterval(updateCountdowns, 1000);

				// Hero Slider Controller
				function initHeroSlider() {
					const $slides = jQuery('.hero-slide');
					const totalSlides = $slides.length;
					if (totalSlides <= 1) return;

					let currentIndex = 0;
					let slideInterval = null;
					const autoPlayDelay = 6000;

					function goToSlide(index) {
						if (index < 0) index = totalSlides - 1;
						if (index >= totalSlides) index = 0;
						currentIndex = index;

						$slides.each(function(i) {
							if (i === currentIndex) {
								jQuery(this).removeClass('opacity-0 z-0 pointer-events-none').addClass('opacity-100 z-10');
							} else {
								jQuery(this).removeClass('opacity-100 z-10').addClass('opacity-0 z-0 pointer-events-none');
							}
						});

						// Update dots
						jQuery('.hero-dot').each(function(i) {
							if (i === currentIndex) {
								jQuery(this).removeClass('bg-white/50 hover:bg-white/80 w-2.5').addClass('w-7 bg-white');
							} else {
								jQuery(this).removeClass('w-7 bg-white').addClass('w-2.5 bg-white/50 hover:bg-white/80');
							}
						});
					}

					function startAutoplay() {
						stopAutoplay();
						slideInterval = setInterval(() => {
							goToSlide(currentIndex + 1);
						}, autoPlayDelay);
					}

					function stopAutoplay() {
						if (slideInterval) {
							clearInterval(slideInterval);
							slideInterval = null;
						}
					}

					// Event handlers
					jQuery('#hero-slider-next').on('click', function(e) {
						e.preventDefault();
						goToSlide(currentIndex + 1);
						startAutoplay();
					});

					jQuery('#hero-slider-prev').on('click', function(e) {
						e.preventDefault();
						goToSlide(currentIndex - 1);
						startAutoplay();
					});

					jQuery(document).on('click', '.hero-dot', function(e) {
						e.preventDefault();
						const idx = parseInt(jQuery(this).attr('data-dot-index'), 10);
						if (!isNaN(idx)) {
							goToSlide(idx);
							startAutoplay();
						}
					});

					// Pause on hover
					const $section = jQuery('#member-hero-slider-section');
					$section.on('mouseenter', stopAutoplay).on('mouseleave', startAutoplay);

					// Touch swipe support
					let touchStartX = 0;
					let touchEndX = 0;
					$section.on('touchstart', function(e) {
						touchStartX = e.originalEvent.touches[0].clientX;
					}, { passive: true });

					$section.on('touchend', function(e) {
						touchEndX = e.originalEvent.changedTouches[0].clientX;
						const diff = touchStartX - touchEndX;
						if (Math.abs(diff) > 45) {
							if (diff > 0) {
								goToSlide(currentIndex + 1);
							} else {
								goToSlide(currentIndex - 1);
							}
							startAutoplay();
						}
					}, { passive: true });

					startAutoplay();
				}

				initHeroSlider();
			}

			jQuery(document).ready(init);

			global.Aurelian = {
				loadState, saveState, addXP, awardBadge, toast, openBook, downloadBook, buyBook, surpriseMe,
				syncStreakBadges, xpForNextLevel
			};
		})(window);
	</script>
<?php endif; ?>

<?php wp_footer(); ?>
<script>
	// Merge dashboard parameters into localized dlmParams after wp_footer runs
	window.dlmParams = window.dlmParams || {};
	if (window.dlmDashboardParams) {
		for (var key in window.dlmDashboardParams) {
			if (window.dlmDashboardParams.hasOwnProperty(key)) {
				window.dlmParams[key] = window.dlmDashboardParams[key];
			}
		}
	}
</script>
</body>
</html>
