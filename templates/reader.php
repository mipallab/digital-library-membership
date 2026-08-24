<?php
/**
 * Custom Distraction-Free Book Reader Template
 *
 * @since      1.0.0
 * @package    DLM
 * @subpackage DLM/templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

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

$book = $db->get_book( $book_id );
if ( ! $book ) {
	wp_die( esc_html__( 'Requested book could not be found.', 'digital-library-membership' ) );
}

$access_status = dlm_user_can_access_book( $user_id, $book_id );
$currency      = get_option( 'dlm_currency', 'USD' );

// Verify access entitlement
if ( $access_status === 'locked' ) {
	$access_type = ! empty( $book->access_type ) ? $book->access_type : 'subscription_only';
	$price       = isset( $book->price ) ? floatval( $book->price ) : 0.00;
	$pricing_url = dlm_get_page_url( 'pricing' );
	$library_url = dlm_get_page_url( 'library' );
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php esc_html_e( 'Access Required', 'digital-library-membership' ); ?> - <?php echo esc_html( $book->title ); ?></title>
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
				max-width: 480px;
				width: 100%;
				text-align: center;
				box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
				border: 1px solid #d2d2d7;
			}
			.error-icon {
				font-size: 54px;
				margin-bottom: 20px;
				display: block;
			}
			.error-title {
				font-size: 24px;
				font-weight: 700;
				margin: 0 0 12px 0;
				letter-spacing: -0.5px;
				color: #1d1d1f;
			}
			.error-msg {
				font-size: 15px;
				line-height: 1.5;
				color: #6e6e73;
				margin: 0 0 28px 0;
			}
			.pricing-btn {
				display: inline-block;
				background-color: #855300;
				color: #ffffff !important;
				font-weight: 600;
				font-size: 14px;
				padding: 13px 32px;
				border-radius: 980px;
				text-decoration: none;
				transition: background-color 0.2s ease, transform 0.2s ease;
				cursor: pointer;
			}
			.pricing-btn:hover {
				background-color: #613b00;
				transform: translateY(-1px);
			}
			.pricing-btn.secondary-btn {
				background-color: #f2f2f3;
				color: #1d1d1f !important;
				margin-left: 8px;
			}
			.pricing-btn.secondary-btn:hover {
				background-color: #e5e5ea;
			}
		</style>
	</head>
	<body>
		<div class="error-card">
			<div class="error-icon">🔒</div>
			<h1 class="error-title">
				<?php 
				if ( $access_type === 'purchase_only' ) {
					esc_html_e( 'Purchase Required', 'digital-library-membership' );
				} elseif ( $access_type === 'hybrid' ) {
					esc_html_e( 'Access Required', 'digital-library-membership' );
				} else {
					esc_html_e( 'Membership Access Required', 'digital-library-membership' );
				}
				?>
			</h1>
			<p class="error-msg">
				<?php 
				if ( $access_type === 'purchase_only' ) {
					/* translators: %s: Price with currency */
					printf( esc_html__( 'This book is available as an individual purchase for %s.', 'digital-library-membership' ), esc_html( number_format( $price, 2 ) . ' ' . $currency ) );
				} elseif ( $access_type === 'hybrid' ) {
					/* translators: %s: Price with currency */
					printf( esc_html__( 'This book is available free for active subscribers or can be purchased individually for %s.', 'digital-library-membership' ), esc_html( number_format( $price, 2 ) . ' ' . $currency ) );
				} else {
					esc_html_e( 'An active library membership subscription is required to read this book online.', 'digital-library-membership' );
				}
				?>
			</p>
			<div style="display:flex; justify-content:center; flex-wrap:wrap; gap:8px;">
				<?php if ( $access_type === 'purchase_only' ) : ?>
					<a href="<?php echo esc_url( home_url( '/library/?buy=' . $book_id ) ); ?>" class="pricing-btn">
						<?php 
						/* translators: %s: Price and currency formatted string */
						printf( esc_html__( 'Purchase Book (%s)', 'digital-library-membership' ), esc_html( number_format( $price, 2 ) . ' ' . $currency ) ); 
						?>
					</a>
				<?php elseif ( $access_type === 'hybrid' ) : ?>
					<a href="<?php echo esc_url( $pricing_url ); ?>" class="pricing-btn">
						<?php esc_html_e( 'Join Membership', 'digital-library-membership' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/library/?buy=' . $book_id ) ); ?>" class="pricing-btn secondary-btn">
						<?php 
						/* translators: %s: Price and currency formatted string */
						printf( esc_html__( 'Buy for %s', 'digital-library-membership' ), esc_html( number_format( $price, 2 ) . ' ' . $currency ) ); 
						?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $pricing_url ); ?>" class="pricing-btn">
						<?php esc_html_e( 'View Pricing & Plans', 'digital-library-membership' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
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
<body class="dlm-reader-body theme-light" data-book-id="<?php echo intval( $book_id ); ?>" data-start-page="<?php echo intval( $last_page ); ?>" data-watermark="<?php echo esc_attr( $watermark_text ); ?>" data-access-level="<?php echo esc_attr( $access_status ); ?>">

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
			<?php if ( $access_status === 'read_download' ) : ?>
				<!-- Download Button for Read+Download Entitlement -->
				<button id="dlm-download-doc-btn" class="dlm-toolbar-btn" title="<?php esc_attr_e( 'Download PDF Document', 'digital-library-membership' ); ?>" data-book-id="<?php echo intval( $book_id ); ?>">
					⬇ <span class="lbl" style="font-size:12px; font-weight:bold; margin-left:4px;"><?php esc_html_e( 'Download', 'digital-library-membership' ); ?></span>
				</button>
			<?php endif; ?>
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

