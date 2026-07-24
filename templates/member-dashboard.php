<?php
/**
 * Standalone SPA Member Dashboard Template
 * Overrides the theme template when page has [dlm_account] shortcode.
 */

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

	// Get published books
	$books = $dlm_db->get_books( 'publish' );
	
	// Get all categories terms
	$categories_terms = get_terms( array(
		'taxonomy'   => 'dlm_book_category',
		'hide_empty' => false,
		'parent'     => 0,
	) );
}

// Pricing options
$price_monthly = get_option( 'dlm_pricing_monthly', '12.00' );
$price_yearly = get_option( 'dlm_pricing_yearly', '99.00' );
$price_lifetime = get_option( 'dlm_pricing_lifetime', '199.00' );
$stripe_publishable_key = get_option( 'dlm_stripe_publishable_key' );
$paypal_client_id = get_option( 'dlm_paypal_client_id' );
$paypal_monthly_plan = get_option( 'dlm_paypal_monthly_plan_id' );
$paypal_yearly_plan = get_option( 'dlm_paypal_yearly_plan_id' );
$paypal_lifetime_plan = get_option( 'dlm_paypal_lifetime_plan_id' );

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
		body {
			background-color: #FAFAFA;
			color: #1a1c1c;
			-webkit-font-smoothing: antialiased;
		}
		.glass-sidebar {
			background: rgba(255, 255, 255, 0.7);
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
		aside, main, header {
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
		body.dlm-member-portal-body #sidebar-collapse-btn {
			background-color: #ffffff !important;
			border: 1px solid rgba(134, 116, 97, 0.3) !important;
			border-radius: 9999px !important;
			width: 1.5rem !important;
			height: 1.5rem !important;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
			display: flex !important;
		}
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
			<div class="absolute top-0 right-0 w-[60%] h-full opacity-40 mix-blend-multiply bg-cover bg-right" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCN5XWEA_e7fhXdjDPfF7f60RufK7a6FR3e5K6BNZzK6XE9Of4WbEQP9RBevx5y0YTFlPoA0KRQGlk70QJYqwZgrTQi_SHKFSwbPWLNyiXtO6m7fDwnPTDueKaW4-85BSlDtXlyybNlzNn_lczDMCqRGUuFjVBPHE3xp5d913wnj-c_ZqqlbK16duF1KP1X2Qd0u8nLbw21brHyJtOS-BZ-B_-rn2xmmBpZJf41QRgpN9NIpmivqqF1')"></div>
			<div class="absolute inset-0 bg-gradient-to-r from-background via-background/90 to-transparent"></div>
		</div>

		<main class="relative z-10 w-full max-w-[480px] py-8">
			<?php
			$dlm_public = new DLM_Public( $dlm_db, new DLM_Checkout() );
			echo $dlm_public->get_login_prompt_html();
			?>
		</main>
	</div>
<?php else : ?>
	<!-- ========================================== -->
	<!-- MAIN SPA MEMBER DASHBOARD LAYOUT (Logged In) -->
	<!-- ========================================== -->
	<div class="min-h-screen bg-background flex justify-center">
		<div class="w-full max-w-[2560px] flex relative min-h-screen">

	<!-- Sidebar Menu (Desktop View) -->
	<aside class="sticky top-0 h-screen w-[280px] flex-shrink-0 glass-sidebar border-r border-outline-variant/20 flex flex-col p-6 gap-2 z-50 hidden md:flex transition-all duration-300">
		<!-- Brand & Logo -->
		<div class="mb-10 px-2 flex items-center gap-3 relative w-full">
			<div class="w-9 h-9 bg-primary rounded-lg flex items-center justify-center flex-shrink-0">
				<i class="fa-solid fa-book-open text-white text-[16px]"></i>
			</div>
			<div class="sidebar-brand-text">
				<span class="font-display-lg text-[20px] text-primary font-bold tracking-tight block leading-tight">Bridgeway36</span>
				<p class="text-secondary text-[10px] font-semibold uppercase tracking-widest mt-0.5">Digital Library</p>
			</div>
			<!-- Collapse Toggle Button -->
			<button id="sidebar-collapse-btn" class="absolute -right-9 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-white border border-outline-variant shadow-md flex items-center justify-center hover:bg-primary-fixed-dim hover:text-primary transition-all z-50 cursor-pointer">
				<i class="fa-solid fa-chevron-left text-[11px]" id="sidebar-collapse-icon"></i>
			</button>
		</div>

		<!-- Nav Links List -->
		<nav class="flex-1 space-y-1">
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-lg font-semibold scale-[0.98] transition-all cursor-pointer" data-tab="library" onclick="showTab('library')">
				<i class="fa-solid fa-book text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Library</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-lg transition-all cursor-pointer" data-tab="discover" onclick="showTab('discover')">
				<i class="fa-solid fa-compass text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Discover</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-lg transition-all cursor-pointer" data-tab="journal" onclick="showTab('journal')">
				<i class="fa-solid fa-pen-to-square text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Reading Journal</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-lg transition-all cursor-pointer" data-tab="collections" onclick="showTab('collections')">
				<i class="fa-solid fa-bookmark text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Collections</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-lg transition-all cursor-pointer" data-tab="membership" onclick="showTab('membership')">
				<i class="fa-solid fa-crown text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Membership</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-lg transition-all cursor-pointer" data-tab="achievements" onclick="showTab('achievements')">
				<i class="fa-solid fa-trophy text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Achievements</span>
			</a>
			<a class="nav-tab-link flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-high/50 hover:text-on-surface rounded-lg transition-all cursor-pointer" data-tab="settings" onclick="showTab('settings')">
				<i class="fa-solid fa-gear text-[18px] flex-shrink-0"></i>
				<span class="font-title-sm text-title-sm sidebar-nav-text">Settings</span>
			</a>
		</nav>

		<!-- Bottom CTA & Actions -->
		<div class="mt-auto space-y-4 pt-6">
			<?php 
			$is_pending = ( $sub_details && $sub_details->status === 'pending_approval' );
			if ( ! $has_active_sub && ! $is_pending ) : 
			?>
				<div class="bg-primary-container/20 border border-primary-container/30 p-4 rounded-2xl text-on-primary-container sidebar-cta-card">
					<p class="font-semibold text-body-md mb-2 text-primary">Upgrade to Pro</p>
					<p class="text-xs text-secondary leading-tight mb-4">Access our entire archive of premium digital editions.</p>
					<button onclick="showTab('membership')" class="block text-center w-full py-2.5 bg-primary text-white text-body-md font-semibold rounded-xl hover:opacity-90 transition-opacity">Unlock All</button>
				</div>
			<?php elseif ( $is_pending ) : ?>
				<div class="bg-amber-50 border border-amber-200/50 p-4 rounded-2xl text-amber-800 sidebar-cta-card">
					<p class="font-semibold text-body-md mb-2 text-amber-900">Pending Approval</p>
					<p class="text-xs text-secondary leading-tight mb-4">Your subscription is being reviewed by our administrators.</p>
					<button onclick="showTab('membership')" class="block text-center w-full py-2.5 bg-amber-100 text-amber-900 text-body-md font-semibold rounded-xl hover:bg-amber-200 transition-colors">Check Status</button>
				</div>
			<?php endif; ?>
			<div class="pt-4 border-t border-outline-variant/30 sidebar-footer-links flex flex-col gap-1">
				<a class="flex items-center gap-3 px-4 py-2 text-secondary hover:text-on-surface text-body-md transition-all cursor-pointer" onclick="Aurelian.toast('Concierge support active hello@bridgeway36.com'); return false;">
					<i class="fa-regular fa-circle-question text-[18px] flex-shrink-0"></i>
					<span>Help</span>
				</a>
				<a class="flex items-center gap-3 px-4 py-2 text-secondary hover:text-on-surface text-body-md transition-all cursor-pointer" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">
					<i class="fa-solid fa-right-from-bracket text-[18px] flex-shrink-0"></i>
					<span>Sign Out</span>
				</a>
			</div>
		</div>
	</aside>

	<div class="flex-1 flex flex-col min-w-0">
		<!-- Top App Bar Navigation Header -->
		<header class="sticky top-0 z-40 bg-surface/80 backdrop-blur-xl border-b border-outline-variant/30 h-16 px-margin-desktop hidden md:flex items-center justify-between transition-all duration-300">
			<div class="flex items-center gap-4">
				<h1 class="font-headline-md text-headline-md text-primary tracking-tight" id="top-bar-title">Library</h1>
			</div>
			<div class="flex items-center gap-6">
				<!-- Header Search Bar -->
				<div class="relative group" id="header-search-container">
					<i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-secondary group-focus-within:text-primary transition-colors"></i>
					<input class="pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant/30 rounded-xl w-64 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-body-md" id="global-search-input" placeholder="Search titles, authors..." type="text">
				</div>

				<!-- Streak Counter Nudge Badge -->
				<div class="flex items-center gap-4">
					<div class="flex items-center gap-1.5 pl-3 pr-4 py-1.5 bg-primary-container/20 border border-primary-container/40 rounded-full hover:bg-primary-container/30 cursor-pointer transition-colors" title="Your reading streak" onclick="showTab('achievements')">
						<i class="fa-solid fa-fire text-primary text-[18px]"></i>
						<span id="streak-count-header" class="font-label-caps text-label-caps text-on-primary-container font-semibold">0 day streak</span>
					</div>

					<!-- Notifications Menu Dropdown -->
					<div class="relative">
						<button id="notification-btn" class="p-2 text-secondary hover:bg-primary/5 rounded-full transition-colors relative">
							<i class="fa-regular fa-bell text-[20px]"></i>
							<span id="notification-badge" class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full border-2 border-surface"></span>
						</button>

						<div id="notification-dropdown" class="absolute right-0 top-12 w-80 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xl py-4 px-2 hidden z-50">
							<div class="px-4 pb-2 border-b border-outline-variant/30 flex justify-between items-center">
								<h4 class="font-bold text-on-surface text-body-md">Notifications</h4>
								<span class="text-[11px] text-primary hover:underline cursor-pointer" id="clear-notifications-btn">Clear all</span>
							</div>
							<div class="max-h-64 overflow-y-auto mt-2 space-y-1" id="notification-list">
								<div class="p-3 hover:bg-surface-variant/30 rounded-xl transition-all cursor-pointer">
									<div class="flex gap-2.5 items-start">
										<div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
											<i class="fa-solid fa-fire text-sm"></i>
										</div>
										<div>
											<p class="text-body-md font-bold text-on-surface leading-snug">Welcome to Bridgeway36!</p>
											<p class="text-xs text-secondary opacity-70 mt-0.5">Start reading your first book to unlock achievements!</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Quick Settings Nav -->
					<div class="h-8 w-px bg-outline-variant/30"></div>

					<div class="flex items-center gap-3">
						<a href="#" class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant/50 block hover:scale-105 transition-all" onclick="showTab('settings'); return false;">
							<img class="w-full h-full object-cover" id="header-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>">
						</a>
					</div>
				</div>
			</div>
		</header>

		<!-- ========================================== -->
		<!-- SPA PAGE SECTION VIEWS CONTAINER           -->
		<!-- ========================================== -->
		<main class="flex-1 pt-8 pb-20 px-margin-mobile md:px-margin-desktop">

		<!-- SECTION 1: LIBRARY VIEW -->
		<div id="section-library" class="spa-page">
			<!-- Hero Card Carousel -->
			<section class="mb-12 relative h-[400px] rounded-3xl overflow-hidden group shadow-lg">
				<div class="absolute inset-0">
					<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="<?php echo esc_url( DLM_URL . 'public/images/featured_hero.png' ); ?>" alt="Featured Book">
					<div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent"></div>
				</div>
				<div class="absolute bottom-0 left-0 p-10 text-white w-full md:w-2/3">
					<div class="flex items-center gap-2 mb-4">
						<span class="bg-primary-container text-on-primary-container px-3 py-1 rounded-full text-[12px] font-semibold">FEATURED THIS MONTH</span>
					</div>
					<h2 class="font-display-lg text-display-lg-mobile md:text-[38px] leading-tight mb-4 font-bold text-white">The Architecture of Silence</h2>
					<p class="font-body-lg text-body-lg text-white/90 mb-8 max-w-xl">Explore the serene intersection of minimalist design and acoustic psychology in this month's curated editorial spotlight.</p>
					<div class="flex gap-4">
						<?php if ( ! empty( $books ) ) : ?>
							<button onclick="Aurelian.openBook(<?php echo intval($books[0]->id); ?>, '<?php echo esc_js($books[0]->title); ?>')" class="px-8 py-3 bg-primary-container text-on-primary-container font-semibold rounded-xl hover:scale-105 transition-transform inline-block">Read Now</button>
						<?php endif; ?>
						<button class="px-8 py-3 bg-white/20 backdrop-blur-md text-white border border-white/30 font-semibold rounded-xl hover:bg-white/30 transition-all animate-pulse" onclick="Aurelian.surpriseMe()">Surprise Me</button>
					</div>
				</div>
			</section>

			<!-- Category Chips Section -->
			<section class="mb-10 flex items-center gap-3 overflow-x-auto hide-scrollbar pb-2" id="category-chips">
				<button class="px-6 py-2.5 bg-primary text-white rounded-full font-bold text-body-md whitespace-nowrap active-chip" data-category="all" onclick="filterCategory('all', this)">All Library</button>
				<?php foreach ( $categories_terms as $term ) : ?>
					<button class="px-6 py-2.5 bg-surface-container-high/50 text-secondary hover:bg-primary-container hover:text-on-primary-container rounded-full font-semibold text-body-md whitespace-nowrap transition-colors" data-category="<?php echo esc_attr( $term->slug ); ?>" onclick="filterCategory('<?php echo esc_attr( $term->slug ); ?>', this)"><?php echo esc_html( $term->name ); ?></button>
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
						?>
						<div class="group cursor-pointer book-card-el" data-book-id="<?php echo intval( $book->id ); ?>" data-title="<?php echo esc_attr( strtolower( $book->title ) ); ?>" data-author="<?php echo esc_attr( strtolower( $book->author ) ); ?>" data-categories="<?php echo esc_attr( $cat_slugs_str ); ?>" data-pct="<?php echo intval($pct); ?>">
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

								<!-- Reading Trigger overlay -->
								<div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity" onclick="Aurelian.openBook(<?php echo intval($book->id); ?>, '<?php echo esc_js($book->title); ?>')">
									<span class="px-4 py-2 bg-white text-on-surface font-semibold text-xs rounded-xl shadow-lg hover:scale-105 transition-transform">
										<?php echo $has_active_sub ? 'Read Now' : 'Subscribe to Read'; ?>
									</span>
								</div>
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
				<div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
					<!-- Monthly Plan Card -->
					<div class="bg-white border border-outline-variant/30 rounded-[32px] p-8 flex flex-col shadow-sm book-card-shadow">
						<div class="mb-8">
							<span class="text-secondary bg-secondary-container/40 px-3 py-1 rounded-full text-xs uppercase font-semibold mb-4 inline-block">The Reader</span>
							<h3 class="font-bold text-xl text-on-surface mb-2">Monthly Access</h3>
							<div class="flex items-baseline gap-1 mt-4">
								<span class="text-3xl font-bold text-on-surface">$<?php echo esc_html( $price_monthly ); ?></span>
								<span class="text-secondary font-body-md">/month</span>
							</div>
						</div>
						<ul class="space-y-4 mb-8 flex-1 text-sm text-on-surface-variant">
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Unlimited digital reading</li>
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Real-time reading journal logs</li>
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Saves streaks & achievements</li>
						</ul>
						<button onclick="goToCheckout('monthly', '<?php echo esc_attr( $price_monthly ); ?>')" class="w-full py-3 bg-secondary-container text-on-secondary-container font-semibold rounded-2xl hover:opacity-80 transition-opacity">Select Plan</button>
					</div>

					<!-- Yearly Plan Card -->
					<div class="bg-white border-2 border-primary rounded-[32px] p-8 flex flex-col relative shadow-xl book-card-shadow">
						<div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-primary text-white px-6 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest whitespace-nowrap">BEST VALUE</div>
						<div class="mb-8">
							<span class="text-primary bg-primary/10 px-3 py-1 rounded-full text-xs uppercase font-semibold mb-4 inline-block">The Scholar</span>
							<h3 class="font-bold text-xl text-on-surface mb-2">Yearly Membership</h3>
							<div class="flex items-baseline gap-1 mt-4">
								<span class="text-3xl font-bold text-on-surface">$<?php echo esc_html( $price_yearly ); ?></span>
								<span class="text-secondary font-body-md">/year</span>
							</div>
						</div>
						<ul class="space-y-4 mb-8 flex-1 text-sm text-on-surface-variant">
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Everything in Monthly</li>
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Save ~30% annually</li>
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Collector badges unlocked</li>
						</ul>
						<button onclick="goToCheckout('yearly', '<?php echo esc_attr( $price_yearly ); ?>')" class="w-full py-3 bg-primary text-white font-semibold rounded-2xl hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">Subscribe Yearly</button>
					</div>

					<!-- Lifetime Plan Card -->
					<div class="bg-white border border-outline-variant/30 rounded-[32px] p-8 flex flex-col shadow-sm book-card-shadow">
						<div class="mb-8">
							<span class="text-secondary bg-secondary-container/40 px-3 py-1 rounded-full text-xs uppercase font-semibold mb-4 inline-block">The Collector</span>
							<h3 class="font-bold text-xl text-on-surface mb-2">Lifetime Access</h3>
							<div class="flex items-baseline gap-1 mt-4">
								<span class="text-3xl font-bold text-on-surface">$<?php echo esc_html( $price_lifetime ); ?></span>
								<span class="text-secondary font-body-md">/one-time</span>
							</div>
						</div>
						<ul class="space-y-4 mb-8 flex-1 text-sm text-on-surface-variant">
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> Unlimited permanent access</li>
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> No recurring bills or fees</li>
							<li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-primary"></i> All future books included</li>
						</ul>
						<button onclick="goToCheckout('lifetime', '<?php echo esc_attr( $price_lifetime ); ?>')" class="w-full py-3 bg-secondary-container text-on-secondary-container font-semibold rounded-2xl hover:opacity-80 transition-opacity">Unlock Lifetime</button>
					</div>
				</div>
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
							<button class="px-8 py-3 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-md shadow-primary/10" type="submit">Save Changes</button>
						</div>
					</form>
				</div>
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
						<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
							<!-- Stripe options -->
							<button class="flex items-center justify-between p-5 border-2 border-primary rounded-xl text-left method-btn" id="checkout-method-stripe" onclick="toggleCheckoutPaymentMethod('stripe')">
								<div class="flex items-center gap-3">
									<i class="fa-solid fa-credit-card text-primary text-lg"></i>
									<div>
										<p class="font-bold text-sm">Stripe</p>
										<p class="text-[10px] text-secondary">Card Checkout</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border border-primary flex items-center justify-center"><div class="w-2.5 h-2.5 rounded-full bg-primary" id="stripe-dot"></div></div>
							</button>

							<!-- PayPal Option -->
							<button class="flex items-center justify-between p-5 border border-outline-variant/30 rounded-xl text-left method-btn" id="checkout-method-paypal" onclick="toggleCheckoutPaymentMethod('paypal')">
								<div class="flex items-center gap-3">
									<i class="fa-brands fa-paypal text-secondary text-lg"></i>
									<div>
										<p class="font-bold text-sm">PayPal</p>
										<p class="text-[10px] text-secondary">External Wallet</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border border-outline-variant flex items-center justify-center"><div class="w-2.5 h-2.5 rounded-full bg-primary hidden" id="paypal-dot"></div></div>
							</button>

							<!-- Manual Option -->
							<button class="flex items-center justify-between p-5 border border-outline-variant/30 rounded-xl text-left method-btn" id="checkout-method-manual" onclick="toggleCheckoutPaymentMethod('manual')">
								<div class="flex items-center gap-3">
									<i class="fa-solid fa-building-columns text-secondary text-lg"></i>
									<div>
										<p class="font-bold text-sm">Bank Transfer</p>
										<p class="text-[10px] text-secondary">Manual Review</p>
									</div>
								</div>
								<div class="w-4 h-4 rounded-full border border-outline-variant flex items-center justify-center"><div class="w-2.5 h-2.5 rounded-full bg-primary hidden" id="manual-dot"></div></div>
							</button>
						</div>
					</section>

					<!-- Payment Forms -->
					<div id="stripe-checkout-container" class="space-y-6">
						<div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/20">
							<p class="text-sm text-secondary leading-relaxed">Stripe handles card validation securely. Pressing "Complete Secure Checkout" redirects to Stripe's payment interface.</p>
						</div>
						<button onclick="triggerStripeCheckoutSession()" class="w-full h-14 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-3">
							<span>Complete Secure Checkout</span> <i class="fa-solid fa-arrow-right"></i>
						</button>
					</div>

					<div id="paypal-checkout-container" class="hidden space-y-6">
						<div id="paypal-button-container" class="w-full"></div>
					</div>

					<div id="manual-checkout-container" class="hidden space-y-6">
						<div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/20 space-y-4">
							<h4 class="font-bold text-sm text-on-surface">Direct Bank Transfer Instructions</h4>
							<div class="text-xs text-secondary leading-relaxed p-3 bg-white rounded-xl border border-outline-variant/30">
								<?php echo wp_kses_post( get_option( 'dlm_manual_payment_instructions', __( 'Please transfer funds directly to our bank details and submit your reference code below.', 'digital-library-membership' ) ) ); ?>
							</div>
							<div class="space-y-2">
								<label class="font-label-caps text-xs text-secondary uppercase block">Transaction Reference Code *</label>
								<input class="w-full h-12 px-4 bg-white border border-outline-variant/30 rounded-xl text-body-md" id="checkout-manual-ref" placeholder="e.g. Wire transaction reference ID" type="text">
							</div>
						</div>
						<button onclick="triggerManualPaymentSubmission()" class="w-full h-14 bg-primary text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-3">
							<span>Submit Reference Code</span> <i class="fa-solid fa-arrow-right"></i>
						</button>
					</div>
				</div>

				<!-- Right summary Column -->
				<div class="lg:col-span-5">
					<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-[24px] overflow-hidden shadow-sm sticky top-32">
						<div class="relative h-48 w-full">
							<div class="w-full h-full bg-cover bg-center" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDSk4Nw5d3UbuCs0_cZAVEqJFuQtjwXGTi0Kt3TfE8dvKAoXlWMLQ4UlKclAjmjrKwCRt2rsOD8PdaTc2q9KUz8f-IAEkUBUubZAK1SJHvewSJd_qhcYrcyAdLP3tQn4LgTSjodSkJBfqWz-cLAfLuUsX5AunYsqDaYPsxwkDSPFcYBuOKgRdNND5kwvQ12vIl64f-YnC_MacXp0yMeA9NOT_90heYbIOTqLQYecL1oPnuQMldkw5lsrC4Kg1LrRBsneXBVqaueG6E')"></div>
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

	<!-- Mobile Bottom Bar navigation (Responsive) -->
	<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 py-3 pb-safe bg-surface/80 backdrop-blur-xl border-t border-outline-variant/30 shadow-lg rounded-t-xl">
		<a class="flex flex-col items-center justify-center text-primary scale-110 mobile-nav-btn" data-tab="library" onclick="showTab('library')">
			<i class="fa-solid fa-book text-[20px] mb-0.5"></i>
			<span class="text-[10px] mt-1 font-semibold">Library</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary mobile-nav-btn" data-tab="discover" onclick="showTab('discover')">
			<i class="fa-solid fa-compass text-[20px] mb-0.5"></i>
			<span class="text-[10px] mt-1 font-semibold">Explore</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary mobile-nav-btn" data-tab="journal" onclick="showTab('journal')">
			<i class="fa-solid fa-pen-to-square text-[20px] mb-0.5"></i>
			<span class="text-[10px] mt-1 font-semibold">Journal</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary mobile-nav-btn" data-tab="settings" onclick="showTab('settings')">
			<i class="fa-solid fa-user text-[20px] mb-0.5"></i>
			<span class="text-[10px] mt-1 font-semibold">Profile</span>
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
			checkoutUrl: '<?php echo esc_js( home_url( '/checkout/' ) ); ?>'
		};
	</script>
<?php else : ?>
	<script>
		window.dlmDashboardParams = {
			ajaxUrl: '<?php echo esc_js( $ajax_url ); ?>',
			nonce: '<?php echo esc_js( $dlm_public_nonce ); ?>'
		};
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
		// Sidebar Collapse trigger
		jQuery('#sidebar-collapse-btn').on('click', function() {
			const collapsed = jQuery('body').toggleClass('sidebar-collapsed').hasClass('sidebar-collapsed');
			localStorage.setItem('sidebar_collapsed', collapsed);
		});
		
		if (localStorage.getItem('sidebar_collapsed') === 'true') {
			jQuery('body').addClass('sidebar-collapsed');
		}

		// Notifications drawer
		jQuery('#notification-btn').on('click', function(e) {
			e.stopPropagation();
			jQuery('#notification-dropdown').toggleClass('hidden');
		});

		jQuery(document).on('click', function(e) {
			if (!jQuery('#notification-dropdown').is(e.target) && jQuery('#notification-dropdown').has(e.target).length === 0 && !jQuery('#notification-btn').is(e.target)) {
				jQuery('#notification-dropdown').addClass('hidden');
			}
		});

		jQuery('#clear-notifications-btn').on('click', function(e) {
			e.stopPropagation();
			jQuery('#notification-list').html('<div class="p-6 text-center text-secondary opacity-60"><i class="fa-regular fa-bell text-xl mb-1 block"></i>No new alerts</div>');
			jQuery('#notification-badge').addClass('hidden');
		});

		// -------------------------------------------------------------
		// TAB NAVIGATION LAYER
		// -------------------------------------------------------------
		function showTab(tabName) {
			// Toggle views
			jQuery('.spa-page').addClass('hidden');
			jQuery('#section-' + tabName).removeClass('hidden');

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
			
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}

		// -------------------------------------------------------------
		// LIBRARY FILTERING & SEARCH
		// -------------------------------------------------------------
		function filterCategory(cat, btnEl) {
			showTab('library');
			if (btnEl) {
				jQuery('#category-chips button').removeClass('active-chip active');
				jQuery(btnEl).addClass('active-chip active');
			}

			jQuery('.book-card-el').each(function() {
				const rawCats = jQuery(this).data('categories') || '';
				const cardCat = rawCats.toString().toLowerCase().split(' ');
				const isFav = dlmParams.favoriteBooks.includes(jQuery(this).data('book-id'));
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
			jQuery.post(dlmParams.ajaxUrl, {
				action: 'dlm_toggle_favorite',
				nonce: dlmParams.nonce,
				book_id: bookId
			}, function(res) {
				if (res.success) {
					dlmParams.favoriteBooks = res.data.favorites;
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
			jQuery('#favorites-count').text(dlmParams.favoriteBooks.length + ' books');
			
			let readingCount = 0;
			jQuery('.book-card-el').each(function() {
				const readPct = parseInt(jQuery(this).data('pct'), 10);
				if (readPct > 0 && readPct < 100) readingCount++;
			});
			jQuery('#currently-reading-count').text(readingCount + ' books');
			jQuery('#journal-logs-count').text(dlmParams.userNotes.length + ' logs');
		}

		// -------------------------------------------------------------
		// READING JOURNAL LOGIC (CRUD AJAX)
		// -------------------------------------------------------------
		function renderJournalNotes() {
			const container = jQuery('#journal-notes-grid');
			container.html('');

			if (dlmParams.userNotes.length === 0) {
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
		let checkoutMethod = 'stripe';
		let checkoutPrice = '12.00';

		function goToCheckout(interval, price) {
			if (window.dlmParams.isPendingApproval) {
				Aurelian.toast('You already have a payment pending approval.', { duration: 4000 });
				return;
			}
			checkoutInterval = interval;
			checkoutPrice = price;

			let planLabel = 'Monthly Plan';
			let sumTitle = 'Monthly Subscription';
			if (interval === 'yearly') {
				planLabel = 'Yearly Plan';
				sumTitle = 'Yearly Subscription';
			} else if (interval === 'lifetime') {
				planLabel = 'Lifetime Access';
				sumTitle = 'Lifetime Access Membership';
			}

			jQuery('#checkout-plan-name').text(planLabel);
			jQuery('#checkout-summary-title').text(sumTitle);
			jQuery('#checkout-summary-price, #checkout-calc-subtotal, #checkout-calc-total').text('$' + price);

			showTab('checkout');
			
			if (checkoutMethod === 'paypal') {
				setupPayPalSDKInstance();
			}
		}

		function toggleCheckoutPaymentMethod(method) {
			checkoutMethod = method;
			jQuery('.method-btn').removeClass('border-primary').addClass('border-outline-variant/30');
			jQuery('.method-btn svg, .method-btn i').removeClass('text-primary').addClass('text-secondary');
			jQuery('.method-btn div.border-primary').addClass('border-outline-variant').removeClass('border-primary');
			jQuery('.method-btn #stripe-dot, .method-btn #paypal-dot, .method-btn #manual-dot').addClass('hidden');

			jQuery('#checkout-method-' + method).addClass('border-primary').removeClass('border-outline-variant/30');
			jQuery('#checkout-method-' + method + ' #stripe-dot, #checkout-method-' + method + ' #paypal-dot, #checkout-method-' + method + ' #manual-dot').removeClass('hidden');

			jQuery('#stripe-checkout-container, #paypal-checkout-container, #manual-checkout-container').addClass('hidden');
			jQuery('#' + method + '-checkout-container').removeClass('hidden');

			if (method === 'paypal') {
				setupPayPalSDKInstance();
			}
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
				let state = dlmParams.userAchievements;
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
				
				jQuery.post(dlmParams.ajaxUrl, {
					action: 'dlm_sync_achievements',
					nonce: dlmParams.nonce,
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

			function openBook(bookId, title) {
				if (!dlmParams.hasActiveSub) {
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
			}

			jQuery(document).ready(init);

			global.Aurelian = {
				loadState, saveState, addXP, awardBadge, toast, openBook, surpriseMe,
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
