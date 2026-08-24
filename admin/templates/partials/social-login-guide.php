<?php
/**
 * Shared Social Login Setup Guide Partial (Google & Apple)
 * Included in Settings -> Social Login panel and the Setup Wizard.
 *
 * @since      2.2.0
 * @package    DLM
 * @subpackage DLM/admin/templates/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$google_callback_url = DLM_Social_Auth::get_callback_url( 'google' );
$apple_callback_url  = DLM_Social_Auth::get_callback_url( 'apple' );
$site_domain         = wp_parse_url( home_url(), PHP_URL_HOST );
?>

<div class="dlm-social-guide-wrapper space-y-6">
	<!-- Google Cloud Setup Accordion Card -->
	<div class="border border-[#e2e8f0] dark:border-[#334155] rounded-xl overflow-hidden bg-[#f8fafc] dark:bg-[#1e293b]/50">
		<button type="button" class="w-full px-5 py-4 flex items-center justify-between text-left font-bold text-[#1e293b] dark:text-white hover:bg-[#f1f5f9] dark:hover:bg-[#334155]/50 transition-colors cursor-pointer" onclick="toggleSocialGuideSection('dlm-guide-google-content', this)">
			<div class="flex items-center gap-3">
				<div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center flex-shrink-0">
					<svg class="w-4 h-4" viewBox="0 0 24 24">
						<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
						<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
						<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
						<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
					</svg>
				</div>
				<div>
					<span class="text-sm font-bold block"><?php esc_html_e( 'How to get Google Client ID & Secret (Step-by-Step)', 'digital-library-membership' ); ?></span>
					<span class="text-xs text-[#64748b] dark:text-[#94a3b8] font-normal"><?php esc_html_e( 'Free to set up on Google Cloud Console — takes ~3 minutes', 'digital-library-membership' ); ?></span>
				</div>
			</div>
			<i class="fa-solid fa-chevron-down text-xs text-[#64748b] transition-transform duration-200"></i>
		</button>

		<div id="dlm-guide-google-content" class="px-5 pb-5 pt-2 text-xs leading-relaxed text-[#334155] dark:text-[#cbd5e1] space-y-3 border-t border-[#e2e8f0] dark:border-[#334155]" style="display:none;">
			<ol class="list-decimal list-inside space-y-2 pt-2">
				<li>
					<?php 
					/* translators: %s: Google Cloud Console URL */
					$guide_google_text = __( 'Go to the <a href="%s" target="_blank" class="text-[#855300] dark:text-[#d4a373] underline font-bold">Google Cloud Console Credentials Page &nearr;</a> and sign in with your Google account.', 'digital-library-membership' );
					printf( 
						wp_kses_post( $guide_google_text ),
						'https://console.cloud.google.com/apis/credentials'
					); 
					?>
				</li>
				<li><?php esc_html_e( 'Click "Create Project" (or select an existing project) at the top of the page.', 'digital-library-membership' ); ?></li>
				<li><?php esc_html_e( 'Under "OAuth consent screen" on the left menu, select "External", fill in your App Name and support email, then click Save and Continue.', 'digital-library-membership' ); ?></li>
				<li><?php esc_html_e( 'Navigate back to "Credentials", click "+ CREATE CREDENTIALS" at the top, and select "OAuth client ID".', 'digital-library-membership' ); ?></li>
				<li><?php esc_html_e( 'Choose Application type: "Web application".', 'digital-library-membership' ); ?></li>
				<li>
					<?php esc_html_e( 'Under "Authorized redirect URIs", click "+ ADD URI" and paste this exact URL:', 'digital-library-membership' ); ?>
					<div class="mt-1 flex items-center gap-2 bg-white dark:bg-[#0f172a] p-2 rounded-lg border border-[#cbd5e1] dark:border-[#475569]">
						<code class="text-[11px] font-mono text-[#0f172a] dark:text-[#38bdf8] flex-1 break-all select-all"><?php echo esc_html( $google_callback_url ); ?></code>
						<button type="button" class="px-2.5 py-1 bg-[#855300] hover:bg-[#613b00] text-white rounded text-[10px] font-bold cursor-pointer transition-colors" onclick="dlmCopyText('<?php echo esc_js( $google_callback_url ); ?>', this)">
							<?php esc_html_e( 'Copy URI', 'digital-library-membership' ); ?>
						</button>
					</div>
				</li>
				<li><?php esc_html_e( 'Click "Create". Copy the Client ID and Client Secret into the settings fields below.', 'digital-library-membership' ); ?></li>
			</ol>
		</div>
	</div>

	<!-- Apple Developer Setup Accordion Card -->
	<div class="border border-[#e2e8f0] dark:border-[#334155] rounded-xl overflow-hidden bg-[#f8fafc] dark:bg-[#1e293b]/50">
		<button type="button" class="w-full px-5 py-4 flex items-center justify-between text-left font-bold text-[#1e293b] dark:text-white hover:bg-[#f1f5f9] dark:hover:bg-[#334155]/50 transition-colors cursor-pointer" onclick="toggleSocialGuideSection('dlm-guide-apple-content', this)">
			<div class="flex items-center gap-3">
				<div class="w-8 h-8 rounded-lg bg-black text-white shadow-sm flex items-center justify-center flex-shrink-0">
					<i class="fa-brands fa-apple text-base"></i>
				</div>
				<div>
					<span class="text-sm font-bold block"><?php esc_html_e( 'How to get Apple Sign-In Credentials (Step-by-Step)', 'digital-library-membership' ); ?></span>
					<span class="text-xs text-[#64748b] dark:text-[#94a3b8] font-normal"><?php esc_html_e( 'Requires an active Apple Developer Program membership ($99/year)', 'digital-library-membership' ); ?></span>
				</div>
			</div>
			<i class="fa-solid fa-chevron-down text-xs text-[#64748b] transition-transform duration-200"></i>
		</button>

		<div id="dlm-guide-apple-content" class="px-5 pb-5 pt-2 text-xs leading-relaxed text-[#334155] dark:text-[#cbd5e1] space-y-3 border-t border-[#e2e8f0] dark:border-[#334155]" style="display:none;">
			<div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 rounded-lg text-amber-800 dark:text-amber-300 font-medium">
				<i class="fa-solid fa-circle-info mr-1"></i>
				<?php esc_html_e( 'Prerequisite: You must be enrolled in the Apple Developer Program ($99/year) to generate Keys and Services IDs for Sign in with Apple.', 'digital-library-membership' ); ?>
			</div>

			<ol class="list-decimal list-inside space-y-2 pt-1">
				<li>
					<?php 
					/* translators: %s: Apple Developer Portal URL */
					$guide_apple_text = __( 'Go to the <a href="%s" target="_blank" class="text-[#855300] dark:text-[#d4a373] underline font-bold">Apple Developer Certificates & Identifiers &nearr;</a> portal.', 'digital-library-membership' );
					printf( 
						wp_kses_post( $guide_apple_text ),
						'https://developer.apple.com/account/resources/identifiers/list'
					); 
					?>
				</li>
				<li><?php esc_html_e( 'Find your 10-character Team ID in the top right corner of the Apple Developer portal (or under Membership details).', 'digital-library-membership' ); ?></li>
				<li><?php esc_html_e( 'Create an App ID: Click "+" next to Identifiers &rarr; App IDs &rarr; Check "Sign in with Apple" &rarr; Register.', 'digital-library-membership' ); ?></li>
				<li>
					<?php esc_html_e( 'Create a Services ID: Click "+" next to Identifiers &rarr; Services IDs &rarr; Enter an Identifier (e.g. com.yourdomain.login).', 'digital-library-membership' ); ?>
				</li>
				<li>
					<?php esc_html_e( 'Configure Services ID: Enable "Sign in with Apple" on your Services ID &rarr; Click Configure &rarr; Add Primary App ID.', 'digital-library-membership' ); ?>
					<div class="mt-1 space-y-1">
						<div><strong><?php esc_html_e( 'Domain:', 'digital-library-membership' ); ?></strong> <code class="font-mono bg-white dark:bg-[#0f172a] px-1.5 py-0.5 rounded border border-[#cbd5e1] dark:border-[#475569]"><?php echo esc_html( $site_domain ); ?></code></div>
						<div>
							<strong><?php esc_html_e( 'Return URL:', 'digital-library-membership' ); ?></strong>
							<div class="mt-0.5 flex items-center gap-2 bg-white dark:bg-[#0f172a] p-2 rounded-lg border border-[#cbd5e1] dark:border-[#475569]">
								<code class="text-[11px] font-mono text-[#0f172a] dark:text-[#38bdf8] flex-1 break-all select-all"><?php echo esc_html( $apple_callback_url ); ?></code>
								<button type="button" class="px-2.5 py-1 bg-[#855300] hover:bg-[#613b00] text-white rounded text-[10px] font-bold cursor-pointer transition-colors" onclick="dlmCopyText('<?php echo esc_js( $apple_callback_url ); ?>', this)">
									<?php esc_html_e( 'Copy URI', 'digital-library-membership' ); ?>
								</button>
							</div>
						</div>
					</div>
				</li>
				<li><?php esc_html_e( 'Create a Key: Go to Keys &rarr; "+" &rarr; Name it "DLM Apple Sign In" &rarr; Check "Sign in with Apple" &rarr; Configure with your Primary App ID &rarr; Register &rarr; Download the .p8 private key file and note your 10-character Key ID.', 'digital-library-membership' ); ?></li>
				<li><?php esc_html_e( 'Open the downloaded .p8 file in a text editor (Notepad, VS Code) and copy the entire contents into the Private Key box below.', 'digital-library-membership' ); ?></li>
			</ol>
		</div>
	</div>
</div>

<script>
function toggleSocialGuideSection(contentId, btn) {
	const content = document.getElementById(contentId);
	const icon = btn.querySelector('.fa-chevron-down') || btn.querySelector('i');
	if (content) {
		if (content.style.display === 'none' || !content.style.display) {
			content.style.display = 'block';
			if (icon) icon.style.transform = 'rotate(180deg)';
		} else {
			content.style.display = 'none';
			if (icon) icon.style.transform = 'rotate(0deg)';
		}
	}
}

function dlmCopyText(text, btn) {
	navigator.clipboard.writeText(text).then(() => {
		const originalText = btn.innerText;
		btn.innerText = 'Copied!';
		btn.style.backgroundColor = '#16a34a';
		setTimeout(() => {
			btn.innerText = originalText;
			btn.style.backgroundColor = '';
		}, 2000);
	}).catch(() => {
		prompt('Copy this URL:', text);
	});
}
</script>
