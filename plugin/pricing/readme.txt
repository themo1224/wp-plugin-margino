=== Pricing ===
Contributors: Pricing
Tags: woocommerce, pricing, iran, saas, rtl
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.6.0
License: Proprietary
License URI: https://pricing.example/terms

WooCommerce connector for Pricing SaaS — sync products, see recommended prices, apply manually. Engine, rivals, and alerts live on the SaaS (not unlocked forever by this plugin).

== Description ==

Pricing is a **SaaS subscription**. This plugin is only the WooCommerce connector.

1. Sign up at the Pricing dashboard and create an API key.
2. Install this plugin on WordPress + WooCommerce.
3. Paste the API base URL and key under Pricing → اتصال.
4. Sync products and apply recommended prices manually.

Purchase / renew via our site or authorized marketplaces (Zhaket / RTL). Marketplace access maps to a **time-bound** plan — not a forever unlock of the pricing engine or rival data.

Farsi / RTL admin UI.

== Installation ==

1. Buy or subscribe to Pricing (SaaS). Complete signup and copy your API key.
2. Upload the `pricing` folder to `/wp-content/plugins/pricing/`.
3. Activate "Pricing" (WooCommerce required).
4. Open Pricing → اتصال and enter your SaaS base URL (`https://your-host/v1`) and API key.

== Frequently Asked Questions ==

= Does buying the plugin once unlock everything forever? =

No. The plugin is a connector. Cost engine, rivals, and alerts require an active SaaS plan.

= Where do I enter costs? =

On the Pricing web dashboard, not in WordPress.

== Changelog ==

= 0.6.0 =
* Phase 1 connector: connect, sync, recommendations, manual apply, below-floor refuse.
