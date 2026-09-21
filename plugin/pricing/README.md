# Pricing (WordPress / WooCommerce connector)

Phase 1.6: RTL admin shell + API key connect + product sync + recommended prices UI. No price apply yet (1.7).

Contract: [`docs/contracts/wp-api-v1.md`](../../docs/contracts/wp-api-v1.md)

## Prefix / text domain

- Function / option / hook prefix: `pricing_`
- Text domain: `pricing`
- Version: `0.4.0`

## Local connect smoke test

Prerequisites:

1. **API** on port 8000: from `api/`, run `composer dev` and `php artisan db:seed`.
2. **WordPress** at `http://localhost:8880/wordpress/` with WooCommerce active.
3. Copy or refresh this folder into `wp-content/plugins/pricing/` (the folder that contains `pricing.php`). If the install path is already this repo folder (symlink/live copy), just refresh the files — **no deactivate/reactivate** required unless PHP fatals (then restart PHP).

Steps:

1. Open **Pricing → اتصال** (`wp-admin/admin.php?page=pricing-connection`).
2. **آدرس سرویس:** `http://localhost:8000/v1` (placeholder only; not auto-saved).
3. **کلید API:** `dev_pk_local_connector_key_do_not_use_in_prod`
4. Click **اتصال**. Status strip should show **متصل** with shop **Local Dev Store** / plan **Starter**.
5. Retry with a garbage key → error notice, strip **متصل نیست** (failed key is not saved).
6. Click **قطع اتصال** → key removed, strip disconnected.
7. Confirm **نمای کلی** and **محصولات** show the same live strip.
8. After a successful connect, the password field stays empty (key never echoed in HTML).

## Local product sync smoke test

1. Stay **متصل** (reconnect if needed).
2. In WooCommerce, create at least one **published** product with a price (simple is enough).
3. Open **Pricing → محصولات** → click **همگام‌سازی محصولات**.
4. Expect a success notice with accepted / rejected / skipped counts; **آخرین همگام‌سازی** panel updates.
5. Click sync again → same ids accepted (upsert).
6. Disconnect → Products shows CTA to Connection (no sync button).
7. Drafts and variation children are not sent as separate rows.

## Local recommendations smoke test

1. Stay **متصل** and sync at least once.
2. Open **Pricing → محصولات** → panel **قیمت‌های پیشنهادی**.
3. Expect a table: name, current price, recommended price, currency, updated time, status.
4. Stub engine: recommended price matches the synced store price; status **آماده**.
5. Create a new published WC product **without** syncing → row status **همگام‌سازی نشده** (no fatal).
6. With 21+ published products, use **قبلی / بعدی** — page 2 loads only that page’s recommendations.
7. Stop the API → page shows an unreachable error notice; admin does not fatal.
8. Confirm there is **no Apply button** and WooCommerce prices are unchanged.

This slice calls `POST {base}/connector/validate`, `POST {base}/connector/products/sync`, and `GET {base}/connector/products/{id}/recommendation`. Manual apply arrives in plan 1.7.

## Layout

```
pricing.php
includes/
  class-plugin.php
  class-dependencies.php
  class-http-client.php
  class-connection.php
  class-product-sync.php
  class-recommendations.php
  class-admin-menu.php
  class-admin-pages.php
assets/
  css/admin-tokens.css
  css/admin.css
  fonts/vazirmatn/*.woff2
languages/   # placeholder for fa_IR later
```

No Composer / PHPUnit in the plugin for this slice.
