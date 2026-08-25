<?php
/**
 * Standalone Full-Screen Library Checkout Template
 * Overrides the theme template when page has [dlm_checkout] shortcode or on /library-checkout/
 *
 * @package DLM
 * @subpackage DLM/templates
 * @version 3.2.4
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
?>
<!DOCTYPE html>
<html class="light" <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta content="width=device-width, initial-scale=1.0" name="viewport">
	<title><?php esc_html_e( 'Secure Checkout', 'digital-library-membership' ); ?> | <?php echo esc_html( $site_name ); ?></title>
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
					"fontFamily": {
						"headline": ["Playfair Display", "serif"],
						"body": ["Inter", "sans-serif"],
						"label": ["Plus Jakarta Sans", "sans-serif"]
					}
				}
			}
		};
	</script>
	<style>
		body {
			margin: 0;
			padding: 0;
			background-color: #fafafa;
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			-webkit-font-smoothing: antialiased;
		}
	</style>
</head>
<body class="bg-[#fafafa] text-[#1a1c1c] antialiased">
	<div id="dlm-checkout-root" class="min-h-screen">
		<?php echo do_shortcode( '[dlm_checkout]' ); ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
