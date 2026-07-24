=== Digital Library Membership ===
Contributors: mipallab123
Tags: library, membership, reader, flipbook, stripe
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.9.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# Digital Library Membership

A premium WordPress plugin for subscription-based digital book reading with realistic physical-style page flip animation.

## Key Features

1. **Security & DRM Hardening**:
   - Files are stored in a protected folder (`wp-content/uploads/dlm-protected-books/`) secured by a local `.htaccess` block, which rejects all direct URL access.
   - Books are streamed page-by-page/chunk-by-chunk using HTTP 206 Range requests from an authenticated REST API endpoint, ensuring the raw file is never exposed to the frontend.
   - Anti-extraction measures include right-click blocking, text-selection blocking, copy/save/print keyboard shortcuts interception, print-CSS media blocks, and a repeating diagonal SVG watermark overlaying the reader canvas containing the user's name, email, and IP address.

2. **Apple-Inspired UX**:
   - Clean, minimal design language with generous margins, refined typography, and responsive, fluid grid lists.
   - Realistic 3D book layout with spine shadows and single/double-page spread adaptivity.
   - Next-page prefetching background loops for zero-lag reading.
   - Custom sepia/dark appearance toggle controls and canvas zoom factors.

3. **Gateways & Memberships**:
   - Stripe Subscription Checkout session creation using the official Stripe SDK.
   - PayPal Subscriptions using frontend JavaScript buttons tied to custom webhook endpoints.
   - Manual override dashboard controls for administrator override configurations.

4. **Analytics Reports**:
   - Sales metrics tracking (Total Sales, Estimated MRR).
   - Reading progress bookmarks logged in DB on reader exit or page navigation.
   - Visual Popular Books chart using locally bundled Chart.js.
   - CSV export logs for transaction records and subscribers.

---

## File Structure

```
digital-library-membership/
├── composer.json               # PHP dependency management (Stripe)
├── composer.lock               # Composer lock file
├── php.ini                     # Local PHP configuration for composer build
├── digital-library-membership.php # Main plugin bootstrap file
├── includes/
│   ├── class-dlm-activator.php  # Activation DB setup and folder securing
│   ├── class-dlm-deactivator.php# Deactivation cleanup routines
│   ├── class-dlm.php            # Main class hooking public/admin loops
│   ├── class-dlm-db.php         # DB abstraction controller
│   ├── class-dlm-security.php   # Nonces, sanitation, and capabilities check
│   ├── class-dlm-checkout.php   # Stripe & PayPal subscription routes
│   └── class-dlm-api.php        # Chunked stream range API and progress routes
├── public/
│   ├── css/
│   │   ├── dlm-public.css       # Clean Apple styling
│   │   └── dlm-reader.css       # 3D page flip reader canvas theme
│   └── js/
│       ├── dlm-public.js        # Stripe checkout AJAX and PayPal Buttons
│       ├── dlm-reader.js        # Canvas drawing, watermarks, shortcuts blocks
│       ├── pdf.min.js           # PDF.js core library
│       └── pdf.worker.min.js    # PDF.js background worker
├── templates/
│   └── reader.php               # Clean distraction-free reader screen layout
└── vendor/                      # Third party Composer packages (Stripe-PHP)
```

---

## Server Requirements & Installation

1. **Requirements**:
   - PHP 8.1 or higher.
   - WordPress 6.0 or higher.
   - Local directory write permissions (to create and protect the uploads folder).

2. **Installation**:
   - Place the `digital-library-membership` folder inside the `wp-content/plugins/` directory of your WordPress installation.
   - Go to your WordPress Dashboard -> Plugins and click **Activate**.
   - Custom tables (`wp_dlm_books`, `wp_dlm_subscriptions`, etc.) and the secure documents folder (`wp-content/uploads/dlm-protected-books/`) will be generated automatically.

3. **Configuration**:
   - Under the newly added **Digital Library** sidebar menu, click the **Settings** tab.
   - Configure pricing and enter your **Stripe** (Secret / Publishable Keys & Price IDs) and **PayPal** (Client ID & Plan IDs) credentials.
   - Go to the **Books** tab, upload e-books in `.pdf` or `.epub` format, select cover arts, and click **Save Book**.
   - Create 3 custom pages in your WordPress site using standard editor blocks and paste the respective shortcodes:
     - Library Grid page: `[dlm_library]`
     - Plans Pricing checkout: `[dlm_checkout]`
     - Member profile page: `[dlm_account]`
