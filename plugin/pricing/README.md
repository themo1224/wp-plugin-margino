# Pricing (WordPress / WooCommerce connector)

Phase 1.2: installable plugin + RTL admin shell (Overview / Connection / Products). No API key UI, HTTP client, sync, or price apply yet.

Contract: [`docs/contracts/wp-api-v1.md`](../../docs/contracts/wp-api-v1.md)

## Prefix / text domain

- Function / option / hook prefix: `pricing_`
- Text domain: `pricing`

## Local install smoke test

1. Copy or symlink this folder into your WordPress site as `wp-content/plugins/pricing/` (the folder that contains `pricing.php`).
2. Ensure **WooCommerce** is installed and active.
3. In **Plugins**, activate **Pricing**. It should stay active with no PHP fatals.
4. Open **Pricing** in the admin sidebar:
   - **نمای کلی** — branded RTL overview shell
   - **اتصال** — empty state (no form)
   - **محصولات** — empty state (no list/sync)
5. Confirm brand greens + Vazirmatn on those pages only (other WP admin screens unchanged).
6. Optional: deactivate WooCommerce, then try activating Pricing again — activation should stop with a clear message (WooCommerce required).

Requires Plugins header / runtime check: WooCommerce must be present (`class_exists( 'WooCommerce' )`).

## Layout

```
pricing.php
includes/
  class-plugin.php
  class-dependencies.php
  class-admin-menu.php
  class-admin-pages.php
assets/
  css/admin-tokens.css
  css/admin.css
  fonts/vazirmatn/*.woff2
languages/   # placeholder for fa_IR later
```

No Composer for Phase 1.2.
