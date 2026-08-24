<?php
/**
 * Setup Wizard Template
 *
 * Standalone-styled SPA setup wizard for first-time configuration.
 *
 * @package DLM
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch all WP pages for dropdown selectors
$wp_pages = get_pages();
$pages_list = array();
foreach ( $wp_pages as $p ) {
	$pages_list[] = array(
		'id'    => $p->ID,
		'title' => $p->post_title,
	);
}

// Check which library pages exist
$pages_to_check = array(
	'library'  => array( 'title' => __( 'Library', 'digital-library-membership' ), 'opt' => 'dlm_library_page_id' ),
	'account'  => array( 'title' => __( 'Library Account', 'digital-library-membership' ), 'opt' => 'dlm_account_page_id' ),
	'pricing'  => array( 'title' => __( 'Library Pricing Plan', 'digital-library-membership' ), 'opt' => 'dlm_pricing_page_id' ),
	'checkout' => array( 'title' => __( 'Library Checkout', 'digital-library-membership' ), 'opt' => 'dlm_checkout_page_id' ),
);

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$is_elementor_active    = did_action( 'elementor/loaded' ) || is_plugin_active( 'elementor/elementor.php' );
$is_elementor_installed = file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' );

$is_wc_active           = class_exists( 'WooCommerce' ) || is_plugin_active( 'woocommerce/woocommerce.php' );
$is_wc_installed        = file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

$public_nonce = wp_create_nonce( 'dlm_public_nonce' );
?>

<div class="dlm-setup-wizard-wrapper">
	<div class="dlm-setup-card">
		<!-- Header -->
		<div class="dlm-setup-header">
			<div class="dlm-setup-logo">
				<i class="fa-solid fa-book-open-reader"></i>
			</div>
			<h1>Digital Library Membership</h1>
			<p class="subtitle">Quick setup to configure your digital publication portal.</p>
		</div>

		<!-- Progress Bar (4 Steps) -->
		<div class="dlm-progress-bar-container">
			<div class="dlm-progress-steps">
				<div class="step-node active" data-step="1">
					<div class="node-circle">1</div>
					<span class="node-label">Pages</span>
				</div>
				<div class="step-line" id="line-1-2"></div>
				<div class="step-node" data-step="2">
					<div class="node-circle">2</div>
					<span class="node-label">Plugins</span>
				</div>
				<div class="step-line" id="line-2-3"></div>
				<div class="step-node" data-step="3">
					<div class="node-circle">3</div>
					<span class="node-label">Legal</span>
				</div>
				<div class="step-line" id="line-3-4"></div>
				<div class="step-node" data-step="4">
					<div class="node-circle">4</div>
					<span class="node-label">Security</span>
				</div>
			</div>
		</div>

		<!-- Step Content -->
		<div class="dlm-step-contents">
			<!-- Step 1: Pages Checklist -->
			<div class="setup-step-pane active" id="pane-step-1">
				<h2>Verify System Pages</h2>
				<p class="pane-description">The plugin automatically generates standard frontend views for your digital library. Let's make sure they are active:</p>

				<div class="pages-checklist">
					<?php foreach ( $pages_to_check as $key => $info ) : 
						$page_id = get_option( $info['opt'] );
						$exists = $page_id && 'publish' === get_post_status( $page_id );
					?>
						<div class="checklist-item <?php echo $exists ? 'verified' : 'pending'; ?>" id="checklist-<?php echo esc_attr( $key ); ?>">
							<div class="item-icon">
								<?php if ( $exists ) : ?>
									<i class="fa-solid fa-circle-check"></i>
								<?php else : ?>
									<i class="fa-solid fa-circle-notch fa-spin"></i>
								<?php endif; ?>
							</div>
							<div class="item-info">
								<strong><?php echo esc_html( $info['title'] ); ?></strong>
								<span class="status-lbl"><?php echo $exists ? esc_html__( 'Active & Configured', 'digital-library-membership' ) : esc_html__( 'Creating page...', 'digital-library-membership' ); ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="pane-actions text-center">
					<button class="dlm-wizard-btn btn-primary" id="btn-next-step-1">
						Confirm & Continue <i class="fa-solid fa-arrow-right"></i>
					</button>
				</div>
			</div>

			<!-- Step 2: Essential Plugins (Elementor & WooCommerce) -->
			<div class="setup-step-pane" id="pane-step-2">
				<h2><?php esc_html_e( 'Essential Plugin Integrations', 'digital-library-membership' ); ?></h2>
				<p class="pane-description"><?php esc_html_e( 'Elevate your digital library with page builder features and e-commerce checkout:', 'digital-library-membership' ); ?></p>

				<div class="plugins-checklist">
					<!-- Elementor Card -->
					<div class="plugin-install-card <?php echo $is_elementor_active ? 'verified' : ''; ?>" id="card-plugin-elementor">
						<div class="plugin-card-left">
							<div class="plugin-icon-box" style="background: #92003b; color: #fff;">
								<i class="fa-solid fa-cube"></i>
							</div>
							<div class="plugin-meta">
								<strong>Elementor Page Builder</strong>
								<p><?php esc_html_e( 'Powers the Featured Books Hero Carousel slider and custom drag-and-drop widgets.', 'digital-library-membership' ); ?></p>
								<div class="plugin-status-badge <?php echo $is_elementor_active ? 'active' : ( $is_elementor_installed ? 'inactive' : 'missing' ); ?>" id="status-badge-elementor">
									<i class="fa-solid <?php echo $is_elementor_active ? 'fa-circle-check' : ( $is_elementor_installed ? 'fa-circle-pause' : 'fa-circle-exclamation' ); ?>"></i>
									<span><?php echo $is_elementor_active ? esc_html__( 'Active & Ready', 'digital-library-membership' ) : ( $is_elementor_installed ? esc_html__( 'Installed (Inactive)', 'digital-library-membership' ) : esc_html__( 'Not Installed', 'digital-library-membership' ) ); ?></span>
								</div>
							</div>
						</div>
						<div class="plugin-card-action">
							<?php if ( $is_elementor_active ) : ?>
								<button class="dlm-wizard-btn btn-installed" disabled>
									<i class="fa-solid fa-check"></i> <?php esc_html_e( 'Active', 'digital-library-membership' ); ?>
								</button>
							<?php else : ?>
								<button class="dlm-wizard-btn btn-primary dlm-btn-install-plugin" data-slug="elementor">
									<i class="fa-solid fa-download"></i> <?php echo $is_elementor_installed ? esc_html__( 'Activate', 'digital-library-membership' ) : esc_html__( 'Install & Activate', 'digital-library-membership' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>

					<!-- WooCommerce Card -->
					<div class="plugin-install-card <?php echo $is_wc_active ? 'verified' : ''; ?>" id="card-plugin-woocommerce">
						<div class="plugin-card-left">
							<div class="plugin-icon-box" style="background: #7f54b3; color: #fff;">
								<i class="fa-solid fa-cart-shopping"></i>
							</div>
							<div class="plugin-meta">
								<strong>WooCommerce</strong>
								<p><?php esc_html_e( 'Recommended for advanced cart checkout gateways and automated book product syncing.', 'digital-library-membership' ); ?></p>
								<div class="plugin-status-badge <?php echo $is_wc_active ? 'active' : ( $is_wc_installed ? 'inactive' : 'missing' ); ?>" id="status-badge-woocommerce">
									<i class="fa-solid <?php echo $is_wc_active ? 'fa-circle-check' : ( $is_wc_installed ? 'fa-circle-pause' : 'fa-circle-exclamation' ); ?>"></i>
									<span><?php echo $is_wc_active ? esc_html__( 'Active & Ready', 'digital-library-membership' ) : ( $is_wc_installed ? esc_html__( 'Installed (Inactive)', 'digital-library-membership' ) : esc_html__( 'Not Installed', 'digital-library-membership' ) ); ?></span>
								</div>
							</div>
						</div>
						<div class="plugin-card-action">
							<?php if ( $is_wc_active ) : ?>
								<button class="dlm-wizard-btn btn-installed" disabled>
									<i class="fa-solid fa-check"></i> <?php esc_html_e( 'Active', 'digital-library-membership' ); ?>
								</button>
							<?php else : ?>
								<button class="dlm-wizard-btn btn-primary dlm-btn-install-plugin" data-slug="woocommerce">
									<i class="fa-solid fa-download"></i> <?php echo $is_wc_installed ? esc_html__( 'Activate', 'digital-library-membership' ) : esc_html__( 'Install & Activate', 'digital-library-membership' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="pane-actions flex-between">
					<button class="dlm-wizard-btn btn-outline" id="btn-skip-step-2"><?php esc_html_e( 'Skip Step', 'digital-library-membership' ); ?></button>
					<button class="dlm-wizard-btn btn-primary" id="btn-next-step-2">
						<?php esc_html_e( 'Continue', 'digital-library-membership' ); ?> <i class="fa-solid fa-arrow-right"></i>
					</button>
				</div>
			</div>

			<!-- Step 3: Legal Pages -->
			<div class="setup-step-pane" id="pane-step-3">
				<h2>Legal Page Preferences</h2>
				<p class="pane-description">Select your Privacy Policy and Terms and Conditions pages. You can setup this in the admin dashboard later.</p>

				<div class="setup-form-group">
					<label for="setup-privacy-page">Privacy Policy Page</label>
					<select id="setup-privacy-page" class="dlm-select">
						<option value="0">-- <?php esc_html_e( 'Select Page (Optional)', 'digital-library-membership' ); ?> --</option>
						<?php foreach ( $pages_list as $p ) : ?>
							<option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( get_option( 'dlm_privacy_policy_page_id' ), $p['id'] ); ?>><?php echo esc_html( $p['title'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="setup-form-group">
					<label for="setup-terms-page">Terms & Conditions Page</label>
					<select id="setup-terms-page" class="dlm-select">
						<option value="0">-- <?php esc_html_e( 'Select Page (Optional)', 'digital-library-membership' ); ?> --</option>
						<?php foreach ( $pages_list as $p ) : ?>
							<option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( get_option( 'dlm_terms_page_id' ), $p['id'] ); ?>><?php echo esc_html( $p['title'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pane-actions flex-between">
					<button class="dlm-wizard-btn btn-outline" id="btn-skip-step-3">Skip Step</button>
					<button class="dlm-wizard-btn btn-primary" id="btn-next-step-3">
						Save & Next <i class="fa-solid fa-arrow-right"></i>
					</button>
				</div>
			</div>

			<!-- Step 4: Google ReCAPTCHA & Demo Setup -->
			<div class="setup-step-pane" id="pane-step-4">
				<h2>Spam & Bot Protection</h2>
				<p class="pane-description">Enable Google ReCAPTCHA to protect login, registration, and checkout forms from bot attacks. Get your keys from the <a href="https://www.google.com/recaptcha/admin/create" target="_blank" style="color: var(--primary-color); font-weight: bold; text-decoration: underline;">Google ReCAPTCHA Admin Console</a>. You can skip this and configure it later.</p>

				<div class="setup-form-group">
					<label for="setup-recaptcha-version">ReCAPTCHA Version</label>
					<select id="setup-recaptcha-version" class="dlm-select">
						<option value="v2" <?php selected( get_option( 'dlm_recaptcha_version', 'v2' ), 'v2' ); ?>>v2 Checkbox ("I'm not a robot")</option>
						<option value="v3" <?php selected( get_option( 'dlm_recaptcha_version' ), 'v3' ); ?>>v3 Invisible (Risk-based score)</option>
					</select>
				</div>

				<div class="setup-form-group">
					<label for="setup-recaptcha-site-key">Site Key</label>
					<input type="text" id="setup-recaptcha-site-key" class="dlm-input" placeholder="e.g. 6LdK..." value="<?php echo esc_attr( get_option( 'dlm_recaptcha_site_key' ) ); ?>">
				</div>

				<div class="setup-form-group">
					<label for="setup-recaptcha-secret-key"><?php esc_html_e( 'Secret Key', 'digital-library-membership' ); ?></label>
					<input type="password" id="setup-recaptcha-secret-key" class="dlm-input" placeholder="e.g. 6LdK_secret..." value="<?php echo esc_attr( get_option( 'dlm_recaptcha_secret_key' ) ); ?>">
				</div>

				<!-- Social Sign-In Optional Card -->
				<div class="setup-demo-toggle-card" style="flex-direction: column; align-items: stretch; gap: 12px;">
					<div style="display: flex; align-items: center; justify-content: space-between;">
						<div class="demo-toggle-info">
							<div class="demo-toggle-title">
								<i class="fa-solid fa-share-nodes" style="color: #855300;"></i>
								<strong><?php esc_html_e( 'Social Sign-In (Optional)', 'digital-library-membership' ); ?></strong>
							</div>
							<p class="demo-toggle-desc"><?php esc_html_e( 'Allow one-click sign in with Google or Apple. You can configure now or later from Settings.', 'digital-library-membership' ); ?></p>
						</div>
						<button type="button" class="dlm-wizard-btn btn-outline" style="padding: 6px 12px; font-size: 12px;" onclick="$('#setup-social-fields').slideToggle();">
							<?php esc_html_e( 'Configure', 'digital-library-membership' ); ?>
						</button>
					</div>

					<div id="setup-social-fields" style="display: none; padding-top: 10px; border-top: 1px solid #eadecc;">
						<div class="setup-form-group">
							<label for="setup-google-client-id"><?php esc_html_e( 'Google Client ID', 'digital-library-membership' ); ?></label>
							<input type="text" id="setup-google-client-id" class="dlm-input" placeholder="<?php esc_attr_e( 'Enter your Google OAuth Client ID', 'digital-library-membership' ); ?>" value="<?php echo esc_attr( get_option( 'dlm_google_client_id' ) ); ?>">
						</div>
						<div class="setup-form-group">
							<label for="setup-google-client-secret"><?php esc_html_e( 'Google Client Secret', 'digital-library-membership' ); ?></label>
							<input type="password" id="setup-google-client-secret" class="dlm-input" placeholder="e.g. GOCSPX-xxxx..." value="<?php echo esc_attr( get_option( 'dlm_google_client_secret' ) ); ?>">
						</div>
						<div style="margin-top: 12px;">
							<?php require DLM_PATH . 'admin/templates/partials/social-login-guide.php'; ?>
						</div>
					</div>
				</div>

				<!-- Demo Data Import Toggle Card -->
				<div class="setup-demo-toggle-card">
					<div class="demo-toggle-info">
						<div class="demo-toggle-title">
							<i class="fa-solid fa-wand-magic-sparkles" style="color: #855300;"></i>
							<strong><?php esc_html_e( 'Import Demo Data (Recommended for Testing)', 'digital-library-membership' ); ?></strong>
						</div>
						<p class="demo-toggle-desc"><?php esc_html_e( 'Automatically populate realistic books (covering all 3 access models), member accounts, categories, tags, and sample orders to test immediately.', 'digital-library-membership' ); ?></p>
					</div>
					<label class="dlm-switch">
						<input type="checkbox" id="setup-import-demo-toggle" checked>
						<span class="dlm-slider"></span>
					</label>
				</div>

				<div class="pane-actions flex-between">
					<button class="dlm-wizard-btn btn-outline" id="btn-skip-step-4">Skip & Finish</button>
					<button class="dlm-wizard-btn btn-primary" id="btn-finish-setup">
						Complete Setup <i class="fa-solid fa-circle-check"></i>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
/* CSS Overlay to hide standard WordPress UI */
#wpadminbar, #adminmenumain, #wpfooter, .notice, #wpscreenbind, .error {
	display: none !important;
}
#wpcontent {
	margin-left: 0 !important;
	padding: 0 !important;
	background: #faf7f2 !important;
	min-height: 100vh;
	display: flex;
	align-items: center;
	justify-content: center;
}
#wpbody-content {
	padding-bottom: 0 !important;
}

/* Beautiful setup wizard styling */
.dlm-setup-wizard-wrapper {
	font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	width: 100%;
	max-width: 580px;
	padding: 20px;
	box-sizing: border-box;
}

.dlm-setup-card {
	background: #ffffff;
	border: 1px solid rgba(133, 83, 0, 0.15);
	border-radius: 28px;
	padding: 40px;
	box-shadow: 0 10px 30px rgba(133, 83, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
}

.dlm-setup-header {
	text-align: center;
	margin-bottom: 35px;
}

.dlm-setup-logo {
	width: 64px;
	height: 64px;
	background: #855300;
	color: #ffffff;
	border-radius: 20px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28px;
	margin: 0 auto 16px auto;
	box-shadow: 0 8px 20px rgba(133, 83, 0, 0.2);
}

.dlm-setup-header h1 {
	font-size: 26px;
	font-weight: 800;
	color: #2b1a00;
	margin: 0 0 8px 0;
	line-height: 1.2;
}

.dlm-setup-header .subtitle {
	font-size: 14px;
	color: #72604d;
	margin: 0;
}

/* Progress bar */
.dlm-progress-bar-container {
	margin-bottom: 40px;
}

.dlm-progress-steps {
	display: flex;
	align-items: center;
	justify-content: space-between;
	position: relative;
}

.step-node {
	display: flex;
	flex-direction: column;
	align-items: center;
	z-index: 2;
	position: relative;
	width: 80px;
}

.node-circle {
	width: 36px;
	height: 36px;
	border-radius: 50%;
	background: #f4ede4;
	color: #8c7860;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: 700;
	font-size: 14px;
	border: 2px solid transparent;
	transition: all 0.3s ease;
}

.node-label {
	font-size: 11px;
	font-weight: 700;
	color: #8c7860;
	margin-top: 8px;
	text-align: center;
	white-space: nowrap;
	transition: all 0.3s ease;
}

.step-line {
	flex: 1;
	height: 3px;
	background: #eadecc;
	margin: -20px 8px 0 8px;
	z-index: 1;
	transition: all 0.3s ease;
}

.step-node.active .node-circle {
	background: #855300;
	color: #ffffff;
	box-shadow: 0 4px 10px rgba(133, 83, 0, 0.25);
}

.step-node.active .node-label {
	color: #855300;
}

.step-node.completed .node-circle {
	background: #e6f4ea;
	color: #137333;
	border-color: #137333;
}

.step-node.completed .node-label {
	color: #137333;
}

.step-line.completed {
	background: #137333;
}

/* Step pane styles */
.setup-step-pane {
	display: none;
}

.setup-step-pane.active {
	display: block;
	animation: fadeInStep 0.4s ease forwards;
}

@keyframes fadeInStep {
	from { opacity: 0; transform: translateY(8px); }
	to { opacity: 1; transform: translateY(0); }
}

.setup-step-pane h2 {
	font-size: 18px;
	font-weight: 700;
	color: #2b1a00;
	margin: 0 0 8px 0;
}

.pane-description {
	font-size: 13.5px;
	color: #72604d;
	line-height: 1.5;
	margin: 0 0 25px 0;
}

/* Checklist step 1 */
.pages-checklist {
	display: flex;
	flex-direction: column;
	gap: 12px;
	margin-bottom: 30px;
}

.checklist-item {
	display: flex;
	align-items: center;
	gap: 16px;
	padding: 16px;
	border-radius: 16px;
	border: 1px solid #eadecc;
	background: #fdfcfb;
	transition: all 0.3s ease;
}

.checklist-item.verified {
	border-color: rgba(19, 115, 51, 0.2);
	background: #f6fbf7;
}

.checklist-item.verified .item-icon {
	color: #137333;
	font-size: 20px;
}

.checklist-item.pending .item-icon {
	color: #855300;
	font-size: 18px;
}

.checklist-item .item-info {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.checklist-item .item-info strong {
	font-size: 14px;
	color: #2b1a00;
}

.checklist-item .item-info .status-lbl {
	font-size: 12px;
	color: #8c7860;
}

.checklist-item.verified .item-info .status-lbl {
	color: #137333;
}

/* Form Styles */
.setup-form-group {
	margin-bottom: 20px;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.setup-form-group label {
	font-size: 13px;
	font-weight: 700;
	color: #2b1a00;
}

.dlm-select, .dlm-input {
	width: 100%;
	box-sizing: border-box;
	padding: 12px 16px;
	border-radius: 12px;
	border: 1px solid #eadecc;
	background: #ffffff;
	font-size: 14px;
	color: #2b1a00;
	transition: all 0.2s ease;
	outline: none;
}

.dlm-select:focus, .dlm-input:focus {
	border-color: #855300;
	box-shadow: 0 0 0 3px rgba(133, 83, 0, 0.1);
}

/* Buttons */
.pane-actions {
	margin-top: 30px;
	display: flex;
	gap: 15px;
}

.pane-actions.text-center {
	justify-content: center;
}

.pane-actions.flex-between {
	justify-content: space-between;
}

.dlm-wizard-btn {
	padding: 12px 24px;
	border-radius: 12px;
	font-weight: 700;
	font-size: 14px;
	cursor: pointer;
	transition: all 0.2s ease;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	outline: none;
}

.dlm-wizard-btn.btn-primary {
	background: #855300;
	color: #ffffff;
	border: none;
	box-shadow: 0 4px 12px rgba(133, 83, 0, 0.15);
}

.dlm-wizard-btn.btn-primary:hover {
	opacity: 0.95;
	transform: translateY(-1px);
}

.dlm-wizard-btn.btn-outline {
	background: transparent;
	color: #72604d;
	border: 1px solid #eadecc;
}

.dlm-wizard-btn.btn-outline:hover {
	background: #faf7f2;
	color: #2b1a00;
}

.btn-primary:active, .btn-outline:active {
	transform: scale(0.98);
}

.setup-demo-toggle-card {
	background: #fdfbf7;
	border: 1px solid #eadecc;
	border-radius: 16px;
	padding: 16px 20px;
	margin-top: 24px;
	margin-bottom: 24px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
}

.demo-toggle-info {
	flex: 1;
}

.demo-toggle-title {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	color: #2b1a00;
	margin-bottom: 4px;
}

.demo-toggle-desc {
	font-size: 12px;
	color: #72604d;
	margin: 0;
	line-height: 1.4;
}

.dlm-switch {
	position: relative;
	display: inline-block;
	width: 48px;
	height: 26px;
	flex-shrink: 0;
}

.dlm-switch input {
	opacity: 0;
	width: 0;
	height: 0;
}

.dlm-slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #d1c7b8;
	transition: .3s;
	border-radius: 26px;
}

.dlm-slider:before {
	position: absolute;
	content: "";
	height: 20px;
	width: 20px;
	left: 3px;
	bottom: 3px;
	background-color: white;
	transition: .3s;
	border-radius: 50%;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.dlm-switch input:checked + .dlm-slider {
	background-color: #855300;
}

.dlm-switch input:checked + .dlm-slider:before {
	transform: translateX(22px);
}

/* Plugin Installer Cards */
.plugins-checklist {
	display: flex;
	flex-direction: column;
	gap: 14px;
	margin-bottom: 25px;
}

.plugin-install-card {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	padding: 16px 20px;
	border-radius: 18px;
	border: 1px solid #eadecc;
	background: #fdfcfb;
	transition: all 0.3s ease;
}

.plugin-install-card.verified {
	border-color: rgba(19, 115, 51, 0.25);
	background: #f6fbf7;
}

.plugin-card-left {
	display: flex;
	align-items: flex-start;
	gap: 14px;
	flex: 1;
}

.plugin-icon-box {
	width: 42px;
	height: 42px;
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 18px;
	flex-shrink: 0;
	margin-top: 2px;
}

.plugin-meta strong {
	font-size: 14.5px;
	color: #2b1a00;
	display: block;
	margin-bottom: 3px;
}

.plugin-meta p {
	font-size: 12px;
	color: #72604d;
	margin: 0;
	line-height: 1.4;
}

.plugin-status-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-size: 11px;
	font-weight: 700;
	padding: 2px 8px;
	border-radius: 20px;
	margin-top: 6px;
}

.plugin-status-badge.active {
	background: #e6f4ea;
	color: #137333;
}

.plugin-status-badge.inactive {
	background: #fef7e0;
	color: #b06000;
}

.plugin-status-badge.missing {
	background: #fce8e6;
	color: #c5221f;
}

.dlm-wizard-btn.btn-installed {
	background: #e6f4ea;
	color: #137333;
	border: 1px solid rgba(19, 115, 51, 0.3);
	cursor: default;
	pointer-events: none;
}
</style>

<script>
jQuery(document).ready(function($) {
	const ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	const nonce = '<?php echo esc_attr( $public_nonce ); ?>';

	// Step 1: Pages Confirmation
	$('#btn-next-step-1').on('click', function() {
		const btn = $(this);
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...');
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'pages'
		}, function(res) {
			if (res.success) {
				goToStep2();
			} else {
				alert(res.data.message || 'Verification failed.');
				btn.prop('disabled', false).html('Confirm & Continue <i class="fa-solid fa-arrow-right"></i>');
			}
		});
	});

	function goToStep2() {
		$('.step-node[data-step="1"]').removeClass('active').addClass('completed');
		$('#line-1-2').addClass('completed');
		
		$('.step-node[data-step="2"]').addClass('active');
		$('#pane-step-1').removeClass('active');
		$('#pane-step-2').addClass('active');
	}

	// 1-Click Plugin Installation / Activation
	$(document).on('click', '.dlm-btn-install-plugin', function(e) {
		e.preventDefault();
		const btn = $(this);
		const slug = btn.data('slug');
		const originalText = btn.html();
		
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Installing...');
		
		$.post(ajaxurl, {
			action: 'dlm_install_activate_plugin',
			nonce: nonce,
			slug: slug
		}, function(res) {
			if (res.success) {
				btn.removeClass('btn-primary dlm-btn-install-plugin').addClass('btn-installed').html('<i class="fa-solid fa-check"></i> Active');
				const card = $(`#card-plugin-${slug}`);
				card.addClass('verified');
				const badge = $(`#status-badge-${slug}`);
				badge.removeClass('inactive missing').addClass('active').html('<i class="fa-solid fa-circle-check"></i> <span>Active & Ready</span>');
			} else {
				alert(res.data.message || 'Failed to install plugin.');
				btn.prop('disabled', false).html(originalText);
			}
		}).fail(function() {
			alert('Network request failed. Please install manually.');
			btn.prop('disabled', false).html(originalText);
		});
	});

	// Step 2: Plugins Next / Skip
	$('#btn-next-step-2, #btn-skip-step-2').on('click', function() {
		goToStep3();
	});

	function goToStep3() {
		$('.step-node[data-step="2"]').removeClass('active').addClass('completed');
		$('#line-2-3').addClass('completed');
		
		$('.step-node[data-step="3"]').addClass('active');
		$('#pane-step-2').removeClass('active');
		$('#pane-step-3').addClass('active');
	}

	// Step 3: Legal Pages Save
	$('#btn-next-step-3').on('click', function() {
		const btn = $(this);
		const privacyVal = $('#setup-privacy-page').val();
		const termsVal = $('#setup-terms-page').val();
		
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...');
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'legal',
			privacy_policy_page_id: privacyVal,
			terms_page_id: termsVal
		}, function(res) {
			if (res.success) {
				goToStep4();
			} else {
				alert(res.data.message || 'Error occurred saving settings.');
				btn.prop('disabled', false).html('Save & Next <i class="fa-solid fa-arrow-right"></i>');
			}
		});
	});

	// Step 3: Legal Pages Skip
	$('#btn-skip-step-3').on('click', function() {
		goToStep4();
	});

	function goToStep4() {
		$('.step-node[data-step="3"]').removeClass('active').addClass('completed');
		$('#line-3-4').addClass('completed');
		
		$('.step-node[data-step="4"]').addClass('active');
		$('#pane-step-3').removeClass('active');
		$('#pane-step-4').addClass('active');
	}

	// Step 4: Complete Setup
	$('#btn-finish-setup').on('click', function() {
		const btn = $(this);
		const recaptchaVer = $('#setup-recaptcha-version').val();
		const siteKey = $('#setup-recaptcha-site-key').val();
		const secretKey = $('#setup-recaptcha-secret-key').val();
		const googleClientId = $('#setup-google-client-id').val();
		const googleClientSecret = $('#setup-google-client-secret').val();
		const importDemo = $('#setup-import-demo-toggle').is(':checked') ? 1 : 0;
		
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> ' + (importDemo ? 'Importing demo & completing...' : 'Completing...'));
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'recaptcha',
			recaptcha_version: recaptchaVer,
			recaptcha_site_key: siteKey,
			recaptcha_secret_key: secretKey,
			google_client_id: googleClientId,
			google_client_secret: googleClientSecret,
			import_demo: importDemo
		}, function(res) {
			if (res.success) {
				finishSetup();
			} else {
				alert(res.data.message || 'Error saving credentials.');
				btn.prop('disabled', false).html('Complete Setup <i class="fa-solid fa-circle-check"></i>');
			}
		});
	});

	// Step 4: Skip & Finish
	$('#btn-skip-step-4').on('click', function() {
		const btn = $(this);
		const importDemo = $('#setup-import-demo-toggle').is(':checked') ? 1 : 0;

		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> ' + (importDemo ? 'Importing demo & completing...' : 'Completing...'));
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'recaptcha',
			recaptcha_version: 'v2',
			recaptcha_site_key: '',
			recaptcha_secret_key: '',
			import_demo: importDemo
		}, function(res) {
			if (res.success) {
				finishSetup();
			} else {
				btn.prop('disabled', false).text('Skip & Finish');
			}
		});
	});

	function finishSetup() {
		$('.step-node[data-step="4"]').removeClass('active').addClass('completed');
		window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=dlm-library' ) ); ?>';
	}
});
</script>
