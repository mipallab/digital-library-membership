<?php
/**
 * Custom Distraction-Free Book Reader Template
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/templates
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$book_id = get_query_var( 'dlm_reader' );
if ( ! $book_id ) {
	wp_safe_redirect( home_url( '/library/' ) );
	exit;
}

$db = new DLM_DB();
$user_id = get_current_user_id();

// Verify access entitlement
if ( ! $db->has_active_membership( $user_id ) ) {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php esc_html_e( 'Access Required', 'digital-library-membership' ); ?></title>
		<?php wp_head(); ?>
		<style>
			body {
				background-color: #f5f5f7;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
				display: flex;
				align-items: center;
				justify-content: center;
				min-height: 100vh;
				margin: 0;
				padding: 20px;
				color: #1d1d1f;
			}
			.error-card {
				background: #ffffff;
				border-radius: 24px;
				padding: 48px 40px;
				max-width: 460px;
				width: 100%;
				text-align: center;
				box-shadow: 0 12px 40px rgba(0, 0, 0, 0.04);
				border: 1px solid #d2d2d7;
			}
			.error-icon {
				font-size: 54px;
				margin-bottom: 24px;
				display: block;
			}
			.error-title {
				font-size: 26px;
				font-weight: 700;
				margin: 0 0 16px 0;
				letter-spacing: -0.5px;
				color: #1d1d1f;
			}
			.error-msg {
				font-size: 16px;
				line-height: 1.5;
				color: #8e8e93;
				margin: 0 0 32px 0;
			}
			.pricing-btn {
				display: inline-block;
				background-color: #0071e3;
				color: #ffffff;
				font-weight: 600;
				font-size: 15px;
				padding: 14px 36px;
				border-radius: 980px;
				text-decoration: none;
				transition: background-color 0.2s ease, transform 0.2s ease;
			}
			.pricing-btn:hover {
				background-color: #0077ed;
				transform: translateY(-1px);
			}
			.pricing-btn:active {
				transform: translateY(0);
			}
		</style>
	</head>
	<body>
		<div class="error-card">
			<div class="error-icon">🔒</div>
			<h1 class="error-title"><?php esc_html_e( 'Access Required', 'digital-library-membership' ); ?></h1>
			<p class="error-msg"><?php esc_html_e( 'Active subscription required to access library books.', 'digital-library-membership' ); ?></p>
			<a href="<?php echo esc_url( dlm_get_page_url( 'pricing' ) ); ?>" class="pricing-btn">
				<?php esc_html_e( 'View Pricing & Plans', 'digital-library-membership' ); ?>
			</a>
		</div>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}

$book = $db->get_book( $book_id );
if ( ! $book ) {
	wp_die( esc_html__( 'Requested book could not be found.', 'digital-library-membership' ) );
}

// Check reading progress
$progress = $db->get_reading_progress( $user_id, $book_id );
$last_page = $progress ? intval( $progress->last_page ) : 1;

$user_obj = wp_get_current_user();
$ip_addr  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
$watermark_text = esc_attr( $user_obj->display_name . ' (' . $user_obj->user_email . ') - ' . $ip_addr );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dlm-reader-html">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title><?php echo esc_html( $book->title ); ?> - Reader</title>
	<?php wp_head(); ?>
</head>
<body class="dlm-reader-body theme-light" data-book-id="<?php echo intval( $book_id ); ?>" data-start-page="<?php echo intval( $last_page ); ?>" data-watermark="<?php echo esc_attr( $watermark_text ); ?>">

	<!-- DRM Protections: Overlay to capture click events -->
	<div class="dlm-reader-shield"></div>

	<!-- Top Toolbar -->
	<header class="dlm-reader-toolbar">
		<div class="dlm-toolbar-left">
			<a href="<?php echo esc_url( home_url( '/library/' ) ); ?>" class="dlm-toolbar-btn back-btn" title="Back to Library">
				<span class="dlm-icon">←</span> <span class="lbl"><?php esc_html_e( 'Library', 'digital-library-membership' ); ?></span>
			</a>
			<span class="dlm-book-title-lbl"><?php echo esc_html( $book->title ); ?></span>
		</div>
		<div class="dlm-toolbar-right">
			<!-- Theme Selector -->
			<button id="dlm-theme-btn" class="dlm-toolbar-btn" title="Toggle Appearance">☀️</button>
			<!-- Zoom controls -->
			<button id="dlm-zoom-out" class="dlm-toolbar-btn" title="Zoom Out">−</button>
			<button id="dlm-zoom-in" class="dlm-toolbar-btn" title="Zoom In">+</button>
			<!-- Toggle Sidebar -->
			<button id="dlm-sidebar-toggle" class="dlm-toolbar-btn" title="Bookmarks & Chapters">📋</button>
		</div>
	</header>

	<!-- Main Workspace -->
	<div class="dlm-reader-workspace">
		<!-- Sidebar panel -->
		<aside class="dlm-reader-sidebar" id="dlm-sidebar" style="display:none;">
			<div class="dlm-sidebar-header">
				<h3><?php esc_html_e( 'Navigation', 'digital-library-membership' ); ?></h3>
			</div>
			<div class="dlm-sidebar-tabs">
				<button class="dlm-tab-btn active" data-tab="toc"><?php esc_html_e( 'Chapters', 'digital-library-membership' ); ?></button>
				<button class="dlm-tab-btn" data-tab="bookmarks"><?php esc_html_e( 'Bookmarks', 'digital-library-membership' ); ?></button>
			</div>
			<div class="dlm-sidebar-content">
				<div class="dlm-sidebar-pane" id="pane-toc">
					<ul id="dlm-toc-list" class="dlm-nav-list">
						<!-- Loaded dynamically by JS -->
						<li class="dlm-placeholder"><?php esc_html_e( 'Parsing chapters...', 'digital-library-membership' ); ?></li>
					</ul>
				</div>
				<div class="dlm-sidebar-pane" id="pane-bookmarks" style="display:none;">
					<button id="dlm-add-bookmark" class="dlm-btn dlm-btn-secondary dlm-btn-sm dlm-btn-block"><?php esc_html_e( '+ Add Current Page', 'digital-library-membership' ); ?></button>
					<ul id="dlm-bookmarks-list" class="dlm-nav-list">
						<!-- Bookmarks list -->
					</ul>
				</div>
			</div>
		</aside>

		<!-- Flipbook Container -->
		<main class="dlm-reader-viewport">
			<!-- Floating Side Navigation Buttons -->
			<button id="dlm-prev-page-side" class="dlm-side-nav-btn prev" title="Previous Page">&#10094;</button>

			<div class="dlm-book-container" id="dlm-book-container">
				<!-- Custom 3D Page flip container -->
				<div class="dlm-flipbook" id="dlm-flipbook">
					<!-- Page templates will load inside here -->
					<div class="dlm-page-sheet left-page" id="page-left">
						<div class="dlm-page-canvas-wrapper">
							<canvas id="canvas-left"></canvas>
							<div class="dlm-watermark-overlay"></div>
						</div>
					</div>
					<div class="dlm-page-sheet right-page" id="page-right">
						<div class="dlm-page-canvas-wrapper">
							<canvas id="canvas-right"></canvas>
							<div class="dlm-watermark-overlay"></div>
						</div>
					</div>
				</div>
			</div>

			<button id="dlm-next-page-side" class="dlm-side-nav-btn next" title="Next Page">&#10095;</button>

			<!-- Mobile swipe prompt -->
			<div class="dlm-swipe-tip" style="display:none;"><?php esc_html_e( 'Swipe or use arrow keys to flip pages', 'digital-library-membership' ); ?></div>
		</main>
	</div>

	<!-- Bottom Navigation Bar -->
	<footer class="dlm-reader-nav">
		<button id="dlm-prev-page" class="dlm-nav-btn" title="Previous Page">◀</button>
		
		<div class="dlm-nav-progress">
			<input type="range" id="dlm-page-slider" min="1" max="100" value="1">
			<span class="dlm-page-counter"><span id="current-page-num">1</span> / <span id="total-page-num">...</span></span>
		</div>

		<button id="dlm-next-page" class="dlm-nav-btn" title="Next Page">▶</button>
	</footer>

	<!-- Elegant Splash Transition loading overlay -->
	<div class="dlm-loading-overlay" id="dlm-loading-overlay">
		<div class="dlm-loader-card">
			<div class="dlm-loader-spinner"></div>
			<div class="dlm-loader-text"><?php esc_html_e( 'Opening secure reading container...', 'digital-library-membership' ); ?></div>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>

