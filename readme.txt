=== Digital Library Membership ===
Contributors: mipallab123
Donate link: https://profiles.wordpress.org/mipallab123
Tags: library, membership, flipbook, pdf-reader, elementor
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 3.2.1
Elementor tested up to: 3.25.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Secure digital library membership with realistic 3D flipbook reader, DRM protection, WooCommerce sync, and native Elementor widgets.

== Description ==

**Digital Library Membership** turns your WordPress site into a secure, high-performance digital library platform. Manage book catalogs, subscription plans, and individual book sales with an Apple Books-inspired reading experience. Fully compatible with Gutenberg Block Editor and Elementor Page Builder. Includes a native suite of 6 high-converting Elementor widgets with GSAP motion and Swiper touch carousels.

### Key Features

* **Home Widgets & Elementor Addon Suite**:
  * **Hero Featured Book Slider**: 3D perspective floating covers, dynamic CTA buttons, rating scores, and ambient glow.
  * **Library Carousel & Search Grid**: Ultra-smooth Swiper carousel and live instant search filter grid with category pills and direct reader links.
  * **Membership Pricing Section**: Interactive pricing plans with featured badges, customizable features list, and direct checkout buttons.
  * **Review Switcher (Video/Text/Google)**: Dynamic 3-tab switcher with 16:9 video embeds, reader testimonials with avatars, and Google review summaries.
* **3-Tier Book Access Control**:
  * **Subscription Only**: Exclusively accessible to active members with recurring subscription plans.
  * **Purchase Only**: Individual direct sales with permanent read & download access.
  * **Hybrid Access**: Free for active subscribers or available for one-off purchase by non-subscribers.
* **Gutenberg & Elementor Ready**:
  * Native Gutenberg blocks and shortcode compatibility across all page templates.
  * Dedicated Elementor Header Navigation widget (`DLM_Elementor_Header_Nav`) with comprehensive styling controls.
  * Elementor Featured Book Hero Slider (`DLM_Elementor_Featured_Slider`) with 3D perspective floating covers, dynamic cache-safe CTA hydration, and live release countdown timers.
* **1-Click Social Sign-In**:
  * Seamless "Continue with Google" (OAuth 2.0 / OIDC) and "Continue with Apple" authentication with CSRF state protection and auto-account provisioning.
* **Dual Payment Gateway Engine**:
  * **Default Direct Engine**: Stripe Checkout & PayPal Subscriptions with automated webhook synchronizations.
  * **Headless WooCommerce Engine**: Seamless headless checkout flow, order-to-access provisioning, instant refund revocations, and time-limited secure downloads.
* **Hardened DRM & Reading Security**:
  * Chunked range streaming through an authenticated REST API (raw file path never exposed).
  * Anti-copy protections: right-click disable, selection blocking, print media suppression, and dynamic user watermarking.
* **1-Click Demo Data Suite (Chunked Pipeline)**:
  * Multi-step batched AJAX import/delete pipeline with live progress indicators, idempotent retries, and comprehensive cleanup safety.
* **Modern SPA Member Dashboard**:
  * Front-end account portal featuring live reading streaks, reading progress indicators, dark/light themes, and purchase history.

== Installation ==

1. Upload the `digital-library-membership` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Follow the automated **Setup Wizard** to create required library pages and configure bot protection.
4. Navigate to **Digital Library** in your WordPress admin menu to manage books, membership pricing, and settings.

== Frequently Asked Questions ==

= Where are book PDF files stored? =
PDFs are stored in a dedicated protected directory (`/wp-content/uploads/dlm-protected-books/`) secured with an `.htaccess` rule that prevents direct browser access.

= Can I use WooCommerce for payments? =
Yes. The plugin includes a built-in Payment Method Switcher in Settings that lets you route all book purchases and subscription plans through WooCommerce.

= Does this plugin support scheduled book publishing? =
Yes. You can set future publish dates on books. They will automatically unlock and appear in the public catalog once the release date arrives.

= How does Social Login work? =
Users can log in or register with one click using their verified Google or Apple accounts. The plugin links existing accounts by email or creates a new subscriber account automatically.

== External Services & Privacy Disclosure ==

This plugin integrates with the following third-party services to handle authentication, payments, bot protection, and fonts:

1. **Google OAuth 2.0 API (Google LLC)**:
   * **Purpose**: Authenticates user sign-in and registration when Google Login is enabled.
   * **Data Transmitted**: Client ID, authorization codes, and requested user profile scopes (openid, email, profile).
   * **Privacy Policy**: [Google Privacy Policy](https://policies.google.com/privacy)

2. **Apple Sign In REST API (Apple Inc.)**:
   * **Purpose**: Authenticates user sign-in and registration when Apple Login is enabled.
   * **Data Transmitted**: Services ID, client secret JWTs, authorization codes, and name/email payload.
   * **Privacy Policy**: [Apple Privacy Policy](https://www.apple.com/legal/privacy/)

3. **Stripe API (Stripe, Inc.)**:
   * **Purpose**: Processes customer credit/debit card subscription payments and book purchases when Stripe is enabled.
   * **Data Transmitted**: Customer email, chosen plan interval, transaction amounts, and session identifiers.
   * **Privacy Policy**: [Stripe Privacy Policy](https://stripe.com/privacy)

4. **PayPal REST API (PayPal Holdings, Inc.)**:
   * **Purpose**: Processes recurring subscription plans and one-time payments when PayPal is enabled.
   * **Data Transmitted**: Subscriber identifier, plan ID, transaction amounts, and webhook payloads.
   * **Privacy Policy**: [PayPal Privacy Statement](https://www.paypal.com/webapps/mpp/ua/privacy-full)

5. **Google reCAPTCHA (Google LLC)**:
   * **Purpose**: Bot and spam protection on checkout, registration, and sign-in forms.
   * **Data Transmitted**: IP address, user interactions, and browser metadata.
   * **Terms & Privacy**: [Google Terms of Service](https://policies.google.com/terms) and [Google Privacy Policy](https://policies.google.com/privacy)

6. **Google Fonts (Google LLC)**:
   * **Purpose**: Typography styling for the header navigation widget (`Plus Jakarta Sans`, `Material Symbols`).
   * **Data Transmitted**: Browser font requests (no personal user data transmitted).
   * **Privacy Policy**: [Google Fonts Privacy](https://developers.google.com/fonts/faq/privacy)

== Screenshots ==

1. Dynamic 3D Book Reader Canvas - Physical page-turning feel with custom watermark overlay.
2. Administrative Control Center - Metrics, subscription managers, and DRM security audit logs.
3. Front-End Member Dashboard - Bookshelf, achievements, and purchase history.
4. Settings & Payment Gateways - Stripe, PayPal, and WooCommerce switchers.
5. Social Sign-In Settings - Google & Apple credential configuration and setup guide.

== Changelog ==

= 3.2.1 =
* Fixed: Auto-restoration engine for WooCommerce Checkout and Cart pages if missing or deleted from database.
* Enhanced: Native WooCommerce cart priming and order routing ensuring all installed payment gateways (bKash, Nagad, Stripe, PayPal, Rocket, Cards) render natively and complete transactions flawlessly.
* Enhanced: Robust multi-tier page discovery (`dlm_is_account_page`) and payment return routing to eliminate redirect loops across all device viewports.
* Verified: 100% clean PHPCS and WordPress.org security compliance.

= 3.2.0 =
* Security: Enforced strict direct script execution checks (`defined('ABSPATH')`) across all plugin bootstrap and class files.
* Security: Hardened protected file repository with dual Apache 2.4+ (`Require all denied`) and Apache 2.2 (`Order Deny,Allow / Deny from all`) directives.
* Security: Implemented `realpath()`, `is_file()`, and `is_readable()` validation on file downloads and reader streams to eliminate path-traversal vulnerabilities.
* Fixed: Added missing global helper functions (`dlm_user_can_access_book`, `dlm_get_payment_engine`, `dlm_get_recaptcha_connection_status`) with defensive fallbacks.
* Standards: Aligned Tested up to: 7.1 and achieved 100% compliance with WordPress Plugin Developer Handbook & Security Hardening guidelines.

= 3.1.0 =
* Security & Standards: 100% WordPress Plugin Check and PHPCS compliance passed.
* Performance: Locally bundled all Swiper and GSAP vendor libraries inside plugin distribution.
* Security: Full sanitization, unslashing, and comprehensive escaping applied across all shortcodes and Elementor widget engines.
* Internationalization: Complete translators comments coverage across all i18n placeholders.

= 3.0.0 =
* Merged: Integrated Mipallab Home Widgets & Addons natively into Digital Library Membership.
* Added: 6 New Elementor Widgets (`DLM_Widget_Hero_Book_Slider`, `DLM_Widget_Library_Carousel`, `DLM_Widget_Membership_Section`, `DLM_Widget_Review_Switcher`, `DLM_Widget_Contact_Section`, `DLM_Widget_About_Author`).
* Added: Standalone Dynamic Shortcodes suite (`[dlm_library_carousel]`, `[dlm_library_grid]`, `[dlm_membership]`, `[dlm_review_switcher]`, `[dlm_contact_form]`, `[dlm_hero_slider]`, `[dlm_about_author]`).
* Added: Full backward compatibility layer with class aliases and legacy shortcode/widget tags (`[mipallab_...]`, `Mipallab_Home_Widgets_Extension`, `Mipallab_Books_Helper`).
* Added: Native GSAP Motion Helpers, ScrollTrigger animations, and ultra-smooth Swiper touch carousel engine.
* Added: Interactive AJAX Contact Form endpoint with strict nonce verification, input sanitization, option logging, and admin email notifications.
* Added: Real-time client-side library search filtering for instant book discovery.

= 2.6.3 =
* Security: Converted database queries (DLM_DB::get_book_purchases and DLM_DB::notification_exists) to inline string literal $wpdb->prepare() queries to satisfy strict PluginCheck static analysis.
* Improved: Updated Elementor Book Countdown target date control to site-timezone-safe wp_date().

= 2.6.2 =
* Fixed: Resolved duplicate endif statement in admin dashboard settings template.
* Fixed: Cleaned up stray duplicate handler code in DLM_Admin package manager.
* Security: Full audit completed with 100% prepared queries, sanitized inputs, and escaped template outputs.

= 2.6.1 =
* Security: Hardened database queries with strict $wpdb->prepare() parameter bindings and identifier placeholders (%i).
* Security: Added output escaping (esc_attr(), esc_html()) on notification unread badges, time diffs, and subscription package counters.
* Security: Added wp_unslash() and absint integer sanitization on AJAX featured book access IDs.
* Security: Guarded Stripe error logging with WP_DEBUG check for production safety.
* Improved: Timezone-safe ISO date generation with wp_date() and gmdate() fallbacks.

= 2.6.0 =
* Added: Subscription Package Management Admin Screen (`sec-plans`) with Bento KPI metrics, table management, live subscriber counts, and 1-click active/inactive toggle.
* Added: Automated Multi-Gateway Provisioning for Stripe (Products & Prices), PayPal (Catalog Products & Billing Plans), and WooCommerce (Virtual Simple Products).
* Added: Dynamic Frontend Plan Grid Rendering in pricing sections and checkout pages reading from the single source of truth package registry.
* Improved: Eliminated dual-writes and consolidated all pricing/feature storage into `dlm_subscription_packages`.
* Improved: Streamlined admin settings by removing redundant scalar pricing fields and adding direct links to the plan manager.

= 2.5.0 =
* Added: First-Time Member Onboarding Tour (Spotlight-style) with pure Vanilla JS engine, smart callout positioning, auto-scroll, single-batch mobile drawer batching, and WCAG accessibility compliance.
* Added: Server-side onboarding completion, skip, and reset tracking with "Show Me Around Again" replay tour button in Settings.
* Added: Dedicated standalone `DLM Book Countdown` Elementor widget (`DLM_Elementor_Book_Countdown`) for upcoming book releases.
* Added: Custom "Digital Library" Elementor category (`elementor/elements/categories_registered`) grouping all DLM visual widgets.
* Enhanced: `DLM Featured Book Slider` widget with complete typography, text color, text shadow, button normal/hover, countdown styling, and book cover image resizing & 3D tilt controls.
* Improved: Removed conflicting inline styles in Elementor widgets to ensure 100% full fidelity control customization from the Elementor editor panel.

= 2.4.0 =
* Added: Elementor Featured Book Hero Slider widget (`DLM_Elementor_Featured_Slider`) with 3D perspective floating covers and customizable banner hero styling.
* Added: Client-side cache-safe dynamic CTA hydration for featured sliders to ensure instant live access mapping across cached environments.
* Added: Live 4-box amber countdown timer for upcoming scheduled book releases in featured sliders.
* Added: Multi-step chunked AJAX pipeline for 1-Click Demo Data Import (5 steps) and Removal (3 steps) with real-time step progress indicators.
* Fixed: Admin dashboard nonce resolution (`dlmAdminParams.nonce`) preventing false connection timeout / 403 errors during demo data operations.
* Improved: Precise HTTP error handling (403, 404, 500, 502/504) replacing generic failure alerts with exact server diagnostic feedback.

= 2.3.0 =
* Added: Mobile-First Member Dashboard UX with left-side sliding menu drawer and 5-item responsive app bar.
* Added: Custom stacked action buttons ("Read" & "Download") with high-visibility white background and bold typography.
* Added: Amber & bronze SPA custom-themed scrollbars with smooth scrolling bounds.
* Improved: Google reCAPTCHA developer testing mode synchronization and error prevention.
* Improved: Viewport height calculation for WordPress Admin Bar on all screen resolutions.

= 2.2.0 =
* Added: One-click Social Sign-In with Google & Apple (OAuth2 / OIDC).
* Added: Step-by-step Social Login setup guide in Settings and Setup Wizard.
* Added: Standard `uninstall.php` uninstallation database cleanup configuration.
* Improved: 100% WordPress.org Plugin Check (PCP) directory compliance audit.

= 2.1.0 =
* Added: Book Access Control Matrix (`subscription_only`, `purchase_only`, `hybrid`).
* Added: Headless WooCommerce payment engine integration with automated refund revocation.
* Added: 1-Click Demo Data Importer and Safe Removal engine.
* Added: Auto-cancellation cron for stale pending transactions.
* Improved: Strict database query preparation with %i identifiers.

= 2.0.0 =
* Added: Standalone SPA Member Dashboard with dark/light mode toggle.
* Added: Dynamic SVG user watermark overlay in book reader.
* Added: Reading progress bookmarks and streak counter.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.5.0 =
Upgrade to version 2.5.0 for the Member Onboarding Tour, standalone Book Countdown Elementor widget, and full styling/typography controls on Featured Book Slider.

= 2.4.0 =
Upgrade to version 2.4.0 for the Elementor Featured Hero Slider widget, chunked demo data import/delete pipeline, and enhanced admin AJAX resilience.
