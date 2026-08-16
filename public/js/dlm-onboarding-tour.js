/**
 * Digital Library Membership - Member Onboarding Tour (Spotlight-style)
 * Pure Vanilla JavaScript engine with responsive step-sequencing,
 * single-batch mobile drawer transition, server-side AJAX state persistence,
 * and complete WCAG accessibility compliance.
 */
(function(window, document) {
	'use strict';

	// Step definitions for Desktop (>= 768px)
	const DESKTOP_STEPS = [
		{
			id: 'library',
			selector: 'aside nav a.nav-tab-link[data-tab="library"]',
			fallbackSelector: 'a[data-tab="library"]',
			title: 'Library (Home)',
			description: 'Your central library where you can browse the complete catalog of digital books, explore featured recommendations, and quickly jump back into your reading.',
			isDrawerResident: false
		},
		{
			id: 'discover',
			selector: 'aside nav a.nav-tab-link[data-tab="discover"]',
			fallbackSelector: 'a[data-tab="discover"]',
			title: 'Discover',
			description: 'Explore personalized book recommendations, daily reading selections, and curated collections tailored to your reading interests.',
			isDrawerResident: false
		},
		{
			id: 'journal',
			selector: 'aside nav a.nav-tab-link[data-tab="journal"]',
			fallbackSelector: 'a[data-tab="journal"]',
			title: 'Reading Journal',
			description: 'Keep private reading notes, record chapter reflections, and track your insights as you read through your favorite books.',
			isDrawerResident: false
		},
		{
			id: 'collections',
			selector: 'aside nav a.nav-tab-link[data-tab="collections"]',
			fallbackSelector: 'a[data-tab="collections"]',
			title: 'Collections',
			description: 'Quickly find your bookmarked favorites, currently active reading books, and custom reading shelves in one convenient place.',
			isDrawerResident: false
		},
		{
			id: 'membership',
			selector: 'aside nav a.nav-tab-link[data-tab="membership"]',
			fallbackSelector: 'a[data-tab="membership"]',
			title: 'Membership',
			description: 'Check your active membership tier, review subscription benefits, or upgrade your plan to unlock unlimited access to the entire catalog.',
			isDrawerResident: false
		},
		{
			id: 'achievements',
			selector: 'aside nav a.nav-tab-link[data-tab="achievements"]',
			fallbackSelector: 'a[data-tab="achievements"]',
			title: 'Achievements',
			description: 'Track your reader level, earned milestone badges, reading streaks, and weekly reading habit calendar as you read.',
			isDrawerResident: false
		},
		{
			id: 'settings',
			selector: 'aside nav a.nav-tab-link[data-tab="settings"]',
			fallbackSelector: 'a[data-tab="settings"]',
			title: 'Settings',
			description: 'Manage your display profile, change your password, update your avatar photo, or replay this onboarding tour anytime.',
			isDrawerResident: false
		},
		{
			id: 'search',
			selector: '#header-search-container',
			fallbackSelector: '#global-search-input',
			title: 'Search Bar',
			description: 'Type here to quickly find any book by title, author name, or topic across the entire digital archive.',
			isDrawerResident: false
		},
		{
			id: 'streak',
			selector: '#header-streak-badge',
			fallbackSelector: 'header div[title*="streak"]',
			title: 'Reading Streak',
			description: 'Keep track of your consecutive reading days. Reading every day builds your habit and earns extra reader experience points.',
			isDrawerResident: false
		},
		{
			id: 'notifications',
			selector: '#notification-bell-wrapper',
			fallbackSelector: '#notification-btn',
			title: 'Notifications',
			description: 'Click here to see updates when you earn a badge, level up, keep a reading streak, complete a purchase, or when your membership is about to renew.',
			isDrawerResident: false
		},
		{
			id: 'signout',
			selector: 'aside .sidebar-footer-links a[title="Sign Out"]',
			fallbackSelector: 'aside a[href*="action=logout"]',
			title: 'Sign Out',
			description: 'Safely log out of your library account when you finish your reading session, especially on shared or public computers.',
			isDrawerResident: false
		}
	];

	// Step definitions for Mobile (< 768px):
	// Phase 1 (Steps 1-7): All Non-Drawer Items (Bottom Navigation & Header)
	// Phase 2 (Steps 8-11): Single-Batch Drawer Items (Drawer opens once & stays open)
	const MOBILE_STEPS = [
		{
			id: 'library',
			selector: 'nav.md\\:hidden a.mobile-nav-btn[data-tab="library"], nav a.mobile-nav-btn[data-tab="library"]',
			fallbackSelector: 'a[data-tab="library"]',
			title: 'Library (Home)',
			description: 'Your central library where you can browse the complete catalog of digital books, explore featured recommendations, and resume reading.',
			isDrawerResident: false
		},
		{
			id: 'discover',
			selector: 'nav.md\\:hidden a.mobile-nav-btn[data-tab="discover"], nav a.mobile-nav-btn[data-tab="discover"]',
			fallbackSelector: 'a[data-tab="discover"]',
			title: 'Discover',
			description: 'Explore personalized book recommendations, daily reading selections, and curated collections tailored to your reading interests.',
			isDrawerResident: false
		},
		{
			id: 'journal',
			selector: 'nav.md\\:hidden a.mobile-nav-btn[data-tab="journal"], nav a.mobile-nav-btn[data-tab="journal"]',
			fallbackSelector: 'a[data-tab="journal"]',
			title: 'Reading Journal',
			description: 'Keep private reading notes, record chapter reflections, and track your insights as you read through your favorite books.',
			isDrawerResident: false
		},
		{
			id: 'membership',
			selector: 'nav.md\\:hidden a.mobile-nav-btn[data-tab="membership"], nav a.mobile-nav-btn[data-tab="membership"]',
			fallbackSelector: 'a[data-tab="membership"]',
			title: 'Membership',
			description: 'Check your active membership tier, review subscription benefits, or upgrade your plan to unlock unlimited reading access.',
			isDrawerResident: false
		},
		{
			id: 'search',
			selector: '#header-search-container',
			fallbackSelector: '#global-search-input',
			title: 'Search Bar',
			description: 'Type here to quickly find any book by title, author name, or topic across the entire digital archive.',
			isDrawerResident: false
		},
		{
			id: 'streak',
			selector: '#header-streak-badge',
			fallbackSelector: 'header div[title*="streak"]',
			title: 'Reading Streak',
			description: 'Keep track of your consecutive reading days. Reading every day builds your habit and earns extra reader experience points.',
			isDrawerResident: false
		},
		{
			id: 'notifications',
			selector: '#notification-bell-wrapper',
			fallbackSelector: '#notification-btn',
			title: 'Notifications',
			description: 'Click here to see updates when you earn a badge, level up, keep a reading streak, complete a purchase, or when your membership is about to renew.',
			isDrawerResident: false
		},
		{
			id: 'collections',
			selector: '#mobile-sidebar-drawer a.mobile-drawer-link[data-tab="collections"]',
			fallbackSelector: '#mobile-sidebar-drawer a[data-tab="collections"]',
			title: 'Collections',
			description: 'Quickly find your bookmarked favorites, currently active reading books, and custom reading shelves in one convenient place.',
			isDrawerResident: true
		},
		{
			id: 'achievements',
			selector: '#mobile-sidebar-drawer a.mobile-drawer-link[data-tab="achievements"]',
			fallbackSelector: '#mobile-sidebar-drawer a[data-tab="achievements"]',
			title: 'Achievements',
			description: 'Track your reader level, earned milestone badges, reading streaks, and weekly reading habit calendar as you read.',
			isDrawerResident: true
		},
		{
			id: 'settings',
			selector: '#mobile-sidebar-drawer a.mobile-drawer-link[data-tab="settings"]',
			fallbackSelector: '#mobile-sidebar-drawer a[data-tab="settings"]',
			title: 'Settings',
			description: 'Manage your display profile, change your password, update your avatar photo, or replay this onboarding tour anytime.',
			isDrawerResident: true
		},
		{
			id: 'signout',
			selector: '#mobile-sidebar-drawer a[title="Sign Out"], #mobile-sidebar-drawer a[href*="action=logout"]',
			fallbackSelector: '#mobile-sidebar-drawer .sidebar-footer-links a, #mobile-sidebar-drawer a[href*="action=logout"]',
			title: 'Sign Out',
			description: 'Safely log out of your library account when you finish your reading session, especially on shared or public devices.',
			isDrawerResident: true
		}
	];

	class DLMOnboardingTour {
		constructor() {
			this.currentStep = 0;
			this.isActive = false;
			this.rootEl = null;
			this.cutoutEl = null;
			this.borderEl = null;
			this.cardEl = null;
			this.liveRegionEl = null;
			this.boundKeyHandler = this.handleKeydown.bind(this);
			this.boundResizeHandler = this.handleResize.bind(this);
			this.resizeTimeout = null;
		}

		isMobile() {
			return window.innerWidth < 768;
		}

		getSteps() {
			return this.isMobile() ? MOBILE_STEPS : DESKTOP_STEPS;
		}

		init() {
			// Check if we should auto-trigger on first login
			const params = window.dlmParams || window.dlmDashboardParams || {};
			if (params.shouldShowOnboarding === true || params.shouldShowOnboarding === '1') {
				// Give dashboard a slight moment to finish rendering layout & fonts
				setTimeout(() => {
					this.start(1);
				}, 600);
			}
		}

		createOverlay() {
			if (document.getElementById('dlm-tour-root')) {
				return;
			}

			const root = document.createElement('div');
			root.id = 'dlm-tour-root';
			root.className = 'dlm-tour-root';

			root.innerHTML = `
				<svg class="dlm-tour-svg-mask" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
					<defs>
						<mask id="dlm-tour-mask">
							<rect x="0" y="0" width="100%" height="100%" fill="white" />
							<rect id="dlm-tour-cutout" x="0" y="0" width="0" height="0" rx="14" ry="14" fill="black" />
						</mask>
					</defs>
					<rect x="0" y="0" width="100%" height="100%" fill="rgba(15, 23, 42, 0.78)" mask="url(#dlm-tour-mask)" />
				</svg>
				<div id="dlm-tour-highlight-border" class="dlm-tour-highlight-border"></div>
				<div id="dlm-tour-card" class="dlm-tour-card" role="dialog" aria-modal="true" aria-labelledby="dlm-tour-title" aria-describedby="dlm-tour-desc" tabindex="-1">
					<div class="dlm-tour-header">
						<span class="dlm-tour-badge">
							<span class="dlm-tour-badge-dot"></span>
							<span id="dlm-tour-step-counter">Step 1 of 11</span>
						</span>
						<button type="button" class="dlm-tour-skip-btn" id="dlm-tour-skip-btn" aria-label="Skip onboarding tour">
							Skip Tour
						</button>
					</div>

					<div class="dlm-tour-body">
						<h3 class="dlm-tour-title" id="dlm-tour-title"></h3>
						<p class="dlm-tour-desc" id="dlm-tour-desc"></p>
						<div class="dlm-tour-progress-track" aria-hidden="true">
							<div class="dlm-tour-progress-bar" id="dlm-tour-progress-bar" style="width: 9%;"></div>
						</div>
					</div>

					<div class="dlm-tour-footer">
						<button type="button" class="dlm-tour-btn dlm-tour-btn-back" id="dlm-tour-back-btn" aria-label="Go to previous step">
							<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
							<span>Back</span>
						</button>
						<button type="button" class="dlm-tour-btn dlm-tour-btn-next" id="dlm-tour-next-btn" aria-label="Go to next step">
							<span id="dlm-tour-next-text">Next</span>
							<i class="fa-solid fa-arrow-right" id="dlm-tour-next-icon" aria-hidden="true"></i>
						</button>
					</div>
				</div>
				<div id="dlm-tour-live-region" class="sr-only" aria-live="polite" style="position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0;"></div>
			`;

			document.body.appendChild(root);

			this.rootEl = root;
			this.cutoutEl = document.getElementById('dlm-tour-cutout');
			this.borderEl = document.getElementById('dlm-tour-highlight-border');
			this.cardEl = document.getElementById('dlm-tour-card');
			this.liveRegionEl = document.getElementById('dlm-tour-live-region');

			// Attach button listeners
			document.getElementById('dlm-tour-skip-btn').addEventListener('click', (e) => {
				e.preventDefault();
				this.skip();
			});

			document.getElementById('dlm-tour-back-btn').addEventListener('click', (e) => {
				e.preventDefault();
				this.prev();
			});

			document.getElementById('dlm-tour-next-btn').addEventListener('click', (e) => {
				e.preventDefault();
				this.next();
			});

			window.addEventListener('keydown', this.boundKeyHandler, true);
			window.addEventListener('resize', this.boundResizeHandler, { passive: true });
			window.addEventListener('scroll', this.boundResizeHandler, { passive: true });
		}

		start(stepNumber = 1) {
			this.createOverlay();
			this.isActive = true;
			this.goToStep(stepNumber);
		}

		replay() {
			// Save reset status to server
			this.saveStatus('reset');

			// Switch to library tab first
			if (typeof window.showTab === 'function') {
				window.showTab('library');
			}

			// Start from step 1
			setTimeout(() => {
				this.start(1);
			}, 300);
		}

		ensureMobileDrawerState(step) {
			return new Promise((resolve) => {
				if (!this.isMobile()) {
					resolve();
					return;
				}

				const drawer = document.getElementById('mobile-sidebar-drawer');
				const backdrop = document.getElementById('mobile-sidebar-backdrop');
				if (!drawer) {
					resolve();
					return;
				}

				const isDrawerOpen = !drawer.classList.contains('-translate-x-full');

				if (step.isDrawerResident) {
					if (!isDrawerOpen) {
						// Single batch open: open once and wait for 320ms transition
						drawer.classList.remove('-translate-x-full');
						if (backdrop) {
							backdrop.classList.remove('opacity-0', 'pointer-events-none');
							backdrop.classList.add('opacity-100', 'pointer-events-auto');
						}
						setTimeout(resolve, 320);
					} else {
						// Drawer is already open, no delay needed
						resolve();
					}
				} else {
					if (isDrawerOpen) {
						// Close drawer if moving back to a non-drawer step
						drawer.classList.add('-translate-x-full');
						if (backdrop) {
							backdrop.classList.remove('opacity-100', 'pointer-events-auto');
							backdrop.classList.add('opacity-0', 'pointer-events-none');
						}
						setTimeout(resolve, 320);
					} else {
						resolve();
					}
				}
			});
		}

		async goToStep(stepNumber) {
			const steps = this.getSteps();
			const totalSteps = steps.length;
			const targetIndex = Math.max(0, Math.min(stepNumber - 1, totalSteps - 1));
			const step = steps[targetIndex];
			this.currentStep = targetIndex + 1;

			// Handle mobile drawer batching state
			await this.ensureMobileDrawerState(step);

			// Find target element
			let targetEl = document.querySelector(step.selector);
			if (!targetEl && step.fallbackSelector) {
				targetEl = document.querySelector(step.fallbackSelector);
			}

			// If target still not found, fallback gracefully to main header or portal root
			if (!targetEl) {
				targetEl = document.querySelector('header') || document.querySelector('.dlm-portal-root') || document.body;
			}

			// Bring element into view inside drawer or dashboard
			if (typeof targetEl.scrollIntoView === 'function') {
				targetEl.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
			}

			// Small tick for smooth scroll to settle
			setTimeout(() => {
				this.renderStep(step, targetEl, totalSteps);
			}, 60);
		}

		renderStep(step, targetEl, totalSteps) {
			const targetRect = targetEl.getBoundingClientRect();
			const padding = 8;
			const cutoutX = Math.max(0, targetRect.left - padding);
			const cutoutY = Math.max(0, targetRect.top - padding);
			const cutoutW = Math.min(window.innerWidth - cutoutX, targetRect.width + padding * 2);
			const cutoutH = Math.min(window.innerHeight - cutoutY, targetRect.height + padding * 2);

			// Update Cutout SVG
			if (this.cutoutEl) {
				this.cutoutEl.setAttribute('x', cutoutX);
				this.cutoutEl.setAttribute('y', cutoutY);
				this.cutoutEl.setAttribute('width', cutoutW);
				this.cutoutEl.setAttribute('height', cutoutH);
			}

			// Update Highlight Border
			if (this.borderEl) {
				this.borderEl.style.top = `${cutoutY}px`;
				this.borderEl.style.left = `${cutoutX}px`;
				this.borderEl.style.width = `${cutoutW}px`;
				this.borderEl.style.height = `${cutoutH}px`;
			}

			// Update Card Contents
			const stepCounter = document.getElementById('dlm-tour-step-counter');
			const titleEl = document.getElementById('dlm-tour-title');
			const descEl = document.getElementById('dlm-tour-desc');
			const progressBar = document.getElementById('dlm-tour-progress-bar');
			const backBtn = document.getElementById('dlm-tour-back-btn');
			const nextBtn = document.getElementById('dlm-tour-next-btn');
			const nextText = document.getElementById('dlm-tour-next-text');
			const nextIcon = document.getElementById('dlm-tour-next-icon');

			if (stepCounter) stepCounter.textContent = `Step ${this.currentStep} of ${totalSteps}`;
			if (titleEl) titleEl.textContent = step.title;
			if (descEl) descEl.textContent = step.description;

			const progressPct = Math.round((this.currentStep / totalSteps) * 100);
			if (progressBar) progressBar.style.width = `${progressPct}%`;

			// Button States
			if (backBtn) {
				backBtn.disabled = (this.currentStep === 1);
			}

			const isLastStep = (this.currentStep === totalSteps);
			if (nextText) {
				nextText.textContent = isLastStep ? '🎉 Finish Tour' : 'Next';
			}
			if (nextIcon) {
				nextIcon.className = isLastStep ? 'fa-solid fa-check' : 'fa-solid fa-arrow-right';
			}
			if (nextBtn) {
				if (isLastStep) {
					nextBtn.classList.add('dlm-tour-btn-finish');
				} else {
					nextBtn.classList.remove('dlm-tour-btn-finish');
				}
			}

			// Announce via ARIA live region
			if (this.liveRegionEl) {
				this.liveRegionEl.textContent = `Step ${this.currentStep} of ${totalSteps}: ${step.title}. ${step.description}`;
			}

			// Position Tooltip Card
			this.positionCard(targetRect, cutoutX, cutoutY, cutoutW, cutoutH, step);

			// Trap focus on primary action
			if (nextBtn) {
				nextBtn.focus();
			}
		}

		positionCard(targetRect, cutoutX, cutoutY, cutoutW, cutoutH, step) {
			if (!this.cardEl) return;

			const cardWidth = this.cardEl.offsetWidth || 380;
			const cardHeight = this.cardEl.offsetHeight || 250;
			const margin = 16;
			let cardTop = 0;
			let cardLeft = 0;

			if (!this.isMobile()) {
				// Desktop Layout Positioning
				if (targetRect.left < 320) {
					// Sidebar Item -> Position to the right of target
					cardLeft = cutoutX + cutoutW + 18;
					cardTop = Math.max(margin, Math.min(window.innerHeight - cardHeight - margin, cutoutY + (cutoutH - cardHeight) / 2));
				} else {
					// Header or Main Item -> Position below target
					cardTop = cutoutY + cutoutH + 18;
					cardLeft = Math.max(margin, Math.min(window.innerWidth - cardWidth - margin, cutoutX + (cutoutW - cardWidth) / 2));
					if (cardTop + cardHeight > window.innerHeight - margin) {
						// Flip above if tight at bottom
						cardTop = Math.max(margin, cutoutY - cardHeight - 18);
					}
				}
			} else {
				// Mobile Layout Positioning
				if (step.isDrawerResident) {
					// Inside mobile drawer: Dock near the item or bottom of screen
					const spaceBelow = window.innerHeight - (cutoutY + cutoutH);
					if (spaceBelow >= cardHeight + margin) {
						cardTop = cutoutY + cutoutH + 12;
					} else if (cutoutY >= cardHeight + margin) {
						cardTop = cutoutY - cardHeight - 12;
					} else {
						cardTop = Math.max(margin, window.innerHeight - cardHeight - margin);
					}
					cardLeft = Math.max(12, Math.min(window.innerWidth - cardWidth - 12, (window.innerWidth - cardWidth) / 2));
				} else {
					// Non-drawer mobile items (Bottom Nav or Header)
					if (targetRect.top > window.innerHeight / 2) {
						// Bottom Nav Item -> Position above target
						cardTop = Math.max(margin, cutoutY - cardHeight - 16);
					} else {
						// Header Item -> Position below target
						cardTop = cutoutY + cutoutH + 16;
					}
					cardLeft = Math.max(12, Math.min(window.innerWidth - cardWidth - 12, (window.innerWidth - cardWidth) / 2));
				}
			}

			this.cardEl.style.top = `${cardTop}px`;
			this.cardEl.style.left = `${cardLeft}px`;
		}

		handleResize() {
			if (!this.isActive) return;
			clearTimeout(this.resizeTimeout);
			this.resizeTimeout = setTimeout(() => {
				this.goToStep(this.currentStep);
			}, 100);
		}

		handleKeydown(e) {
			if (!this.isActive) return;

			if (e.key === 'Escape') {
				e.preventDefault();
				this.skip();
				return;
			}

			// Focus trap within card
			if (e.key === 'Tab') {
				const focusableElements = this.cardEl.querySelectorAll('button:not(:disabled), [tabindex="0"]');
				if (focusableElements.length === 0) return;

				const firstEl = focusableElements[0];
				const lastEl = focusableElements[focusableElements.length - 1];

				if (e.shiftKey) {
					if (document.activeElement === firstEl) {
						e.preventDefault();
						lastEl.focus();
					}
				} else {
					if (document.activeElement === lastEl) {
						e.preventDefault();
						firstEl.focus();
					}
				}
			}
		}

		next() {
			const steps = this.getSteps();
			if (this.currentStep < steps.length) {
				this.goToStep(this.currentStep + 1);
			} else {
				this.finish();
			}
		}

		prev() {
			if (this.currentStep > 1) {
				this.goToStep(this.currentStep - 1);
			}
		}

		finish() {
			this.closeTour();
			this.saveStatus('completed');

			// Friendly success toast moment using the plugin's existing toast system
			if (window.Aurelian && typeof window.Aurelian.toast === 'function') {
				setTimeout(() => {
					window.Aurelian.toast('🎉 You\'re all set! Enjoy exploring the digital library.', { accent: true });
				}, 400);
			}
		}

		skip() {
			this.closeTour();
			this.saveStatus('skipped');

			if (window.Aurelian && typeof window.Aurelian.toast === 'function') {
				setTimeout(() => {
					window.Aurelian.toast('Tour skipped. You can replay it anytime from Settings.');
				}, 400);
			}
		}

		closeTour() {
			this.isActive = false;

			// Close mobile drawer if it was left open by the tour
			if (typeof window.closeMobileDrawer === 'function') {
				window.closeMobileDrawer();
			} else {
				const drawer = document.getElementById('mobile-sidebar-drawer');
				const backdrop = document.getElementById('mobile-sidebar-backdrop');
				if (drawer) drawer.classList.add('-translate-x-full');
				if (backdrop) backdrop.classList.add('opacity-0', 'pointer-events-none');
			}

			// Clean up listeners
			window.removeEventListener('keydown', this.boundKeyHandler, true);
			window.removeEventListener('resize', this.boundResizeHandler);
			window.removeEventListener('scroll', this.boundResizeHandler);

			// Remove overlay DOM
			if (this.rootEl) {
				this.rootEl.remove();
				this.rootEl = null;
			}
		}

		saveStatus(status) {
			const params = window.dlmParams || window.dlmDashboardParams || {};
			if (!params.ajaxUrl || !params.nonce) return;

			if (window.jQuery) {
				window.jQuery.post(params.ajaxUrl, {
					action: 'dlm_update_onboarding_status',
					status: status,
					nonce: params.nonce
				}).done(function(res) {
					if (params) {
						params.shouldShowOnboarding = (status === 'reset');
						params.onboardingCompleted = (status === 'completed' || status === 'skipped') ? 'yes' : 'no';
					}
				});
			}
		}
	}

	// Expose globally
	const tourInstance = new DLMOnboardingTour();
	window.DLMOnboardingTour = tourInstance;

	// Auto-init when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => tourInstance.init());
	} else {
		tourInstance.init();
	}

})(window, document);
