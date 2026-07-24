<?php
/**
 * Setup Wizard Template
 *
 * Standalone-styled SPA setup wizard for first-time configuration.
 */

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

		<!-- Progress Bar -->
		<div class="dlm-progress-bar-container">
			<div class="dlm-progress-steps">
				<div class="step-node active" data-step="1">
					<div class="node-circle">1</div>
					<span class="node-label">Required Pages</span>
				</div>
				<div class="step-line" id="line-1-2"></div>
				<div class="step-node" data-step="2">
					<div class="node-circle">2</div>
					<span class="node-label">Legal Pages</span>
				</div>
				<div class="step-line" id="line-2-3"></div>
				<div class="step-node" data-step="3">
					<div class="node-circle">3</div>
					<span class="node-label">Bot Protection</span>
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

			<!-- Step 2: Legal Pages -->
			<div class="setup-step-pane" id="pane-step-2">
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
					<button class="dlm-wizard-btn btn-outline" id="btn-skip-step-2">Skip Step</button>
					<button class="dlm-wizard-btn btn-primary" id="btn-next-step-2">
						Save & Next <i class="fa-solid fa-arrow-right"></i>
					</button>
				</div>
			</div>

			<!-- Step 3: Google ReCAPTCHA Setup -->
			<div class="setup-step-pane" id="pane-step-3">
				<h2>Spam & Bot Protection</h2>
				<p class="pane-description">Enable Google ReCAPTCHA to protect login, registration, and checkout forms from bot attacks. You can skip this and configure it later.</p>

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
					<label for="setup-recaptcha-secret-key">Secret Key</label>
					<input type="password" id="setup-recaptcha-secret-key" class="dlm-input" placeholder="e.g. 6LdK_secret..." value="<?php echo esc_attr( get_option( 'dlm_recaptcha_secret_key' ) ); ?>">
				</div>

				<div class="pane-actions flex-between">
					<button class="dlm-wizard-btn btn-outline" id="btn-skip-step-3">Skip & Finish</button>
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
</style>

<script>
jQuery(document).ready(function($) {
	const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	const nonce = '<?php echo $public_nonce; ?>';

	// Step 1 Click
	$('#btn-next-step-1').on('click', function() {
		const btn = $(this);
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...');
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'pages'
		}, function(res) {
			if (res.success) {
				// Mark step 1 completed
				$('.step-node[data-step="1"]').removeClass('active').addClass('completed');
				$('#line-1-2').addClass('completed');
				
				// Activate step 2
				$('.step-node[data-step="2"]').addClass('active');
				$('#pane-step-1').removeClass('active');
				$('#pane-step-2').addClass('active');
			} else {
				alert(res.data.message || 'Verification failed.');
				btn.prop('disabled', false).html('Confirm & Continue <i class="fa-solid fa-arrow-right"></i>');
			}
		});
	});

	// Step 2 Click (Save)
	$('#btn-next-step-2').on('click', function() {
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
				goToStep3();
			} else {
				alert(res.data.message || 'Error occurred saving settings.');
				btn.prop('disabled', false).html('Save & Next <i class="fa-solid fa-arrow-right"></i>');
			}
		});
	});

	// Step 2 Skip
	$('#btn-skip-step-2').on('click', function() {
		goToStep3();
	});

	function goToStep3() {
		$('.step-node[data-step="2"]').removeClass('active').addClass('completed');
		$('#line-2-3').addClass('completed');
		
		$('.step-node[data-step="3"]').addClass('active');
		$('#pane-step-2').removeClass('active');
		$('#pane-step-3').addClass('active');
	}

	// Step 3 Click (Complete)
	$('#btn-finish-setup').on('click', function() {
		const btn = $(this);
		const recaptchaVer = $('#setup-recaptcha-version').val();
		const siteKey = $('#setup-recaptcha-site-key').val();
		const secretKey = $('#setup-recaptcha-secret-key').val();
		
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Completing...');
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'recaptcha',
			recaptcha_version: recaptchaVer,
			recaptcha_site_key: siteKey,
			recaptcha_secret_key: secretKey
		}, function(res) {
			if (res.success) {
				finishSetup();
			} else {
				alert(res.data.message || 'Error saving recaptcha credentials.');
				btn.prop('disabled', false).html('Complete Setup <i class="fa-solid fa-circle-check"></i>');
			}
		});
	});

	// Step 3 Skip & Finish
	$('#btn-skip-step-3').on('click', function() {
		const btn = $(this);
		btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Completing...');
		
		$.post(ajaxurl, {
			action: 'dlm_save_setup_wizard',
			nonce: nonce,
			step: 'recaptcha',
			recaptcha_version: 'v2',
			recaptcha_site_key: '',
			recaptcha_secret_key: ''
		}, function(res) {
			if (res.success) {
				finishSetup();
			} else {
				btn.prop('disabled', false).text('Skip & Finish');
			}
		});
	});

	function finishSetup() {
		$('.step-node[data-step="3"]').removeClass('active').addClass('completed');
		window.location.href = '<?php echo admin_url("admin.php?page=dlm-library"); ?>';
	}
});
</script>
