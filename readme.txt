=== Digital Library Membership ===
Contributors: mipallab123
Donate link: https://profiles.wordpress.org/mipallab123
Tags: library, membership, flipbook, pdf reader, gutenberg, elementor, access control
Requires at least: 6.2
Tested up to: 7.0.4
Requires PHP: 8.1
Stable tag: 2.2.0
Elementor tested up to: 3.25.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure, premium subscription membership plugin to read digital books with realistic 3D page-flip experience, DRM protection, and flexible access control.

== Description ==

**Digital Library Membership** turns your WordPress site into a secure, high-performance digital library platform. Manage book catalogs, subscription plans, and individual book sales with an Apple Books-inspired reading experience. Fully compatible with Gutenberg Block Editor and Elementor Page Builder.

### Key Features

* **3-Tier Book Access Control**:
  * **Subscription Only**: Exclusively accessible to active members with recurring subscription plans.
  * **Purchase Only**: Individual direct sales with permanent read & download access.
  * **Hybrid Access**: Free for active subscribers or available for one-off purchase by non-subscribers.
* **Gutenberg & Elementor Ready**:
  * Native Gutenberg blocks and shortcode compatibility across all page templates.
  * Dedicated Elementor Header Navigation widget (`DLM_Elementor_Header_Nav`) with comprehensive styling controls.
* **1-Click Social Sign-In**:
  * Seamless "Continue with Google" (OAuth 2.0 / OIDC) and "Continue with Apple" authentication with CSRF state protection and auto-account provisioning.
* **Dual Payment Gateway Engine**:
  * **Default Direct Engine**: Stripe Checkout & PayPal Subscriptions with automated webhook synchronizations.
  * **Headless WooCommerce Engine**: Seamless headless checkout flow, order-to-access provisioning, instant refund revocations, and time-limited secure downloads.
* **Hardened DRM & Reading Security**:
  * Chunked range streaming through an authenticated REST API (raw file path never exposed).
  * Anti-copy protections: right-click disable, selection blocking, print media suppression, and dynamic user watermarking.
* **1-Click Demo Data Suite**:
  * Instantly populate realistic books across all 3 access models, membership accounts, categories, tags, and orders for rapid testing. Clean 1-click removal.
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

1. Admin Library Dashboard - Catalog management and analytics.
2. Distraction-Free Book Reader - 3D page-flip canvas with responsive spreads.
3. Front-End Member Dashboard - Bookshelf, achievements, and purchase history.
4. Settings & Payment Gateways - Stripe, PayPal, and WooCommerce switchers.
5. Social Sign-In Settings - Google & Apple credential configuration and setup guide.

== Changelog ==

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

= 2.2.0 =
Upgrade to version 2.2.0 for Google & Apple Social Sign-In, setup guides, and full WordPress.org compliance.
