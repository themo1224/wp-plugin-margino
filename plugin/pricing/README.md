# Pricing (WordPress / WooCommerce connector)

Phase **1.8** (Phase 1 connector complete): RTL admin + connect + sync + recommendations + manual apply for simple products + **refuse apply below floor** + last-sync / connection status polish.

Contract: [`docs/contracts/wp-api-v1.md`](../../docs/contracts/wp-api-v1.md)

## Prefix / text domain

- Function / option / hook prefix: `pricing_`
- Text domain: `pricing`
- Version: `0.6.0`

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
5. Status strip and **نمای کلی → وضعیت فعلی** show the last sync UTC time (or **هنوز همگام‌سازی نشده** before the first sync).
6. Click sync again → same ids accepted (upsert).
7. Disconnect → Products shows CTA to Connection (no sync button).
8. Drafts and variation children are not sent as separate rows.

## Local recommendations smoke test

1. Stay **متصل** and sync at least once.
2. Open **Pricing → محصولات** → panel **قیمت‌های پیشنهادی**.
3. Expect a table: name, current price, recommended price, currency, updated time, status, actions.
4. With a cost profile (seeded API), recommended price is floor-aware; without profile in local stub mode it may match store price.
5. Create a new published WC product **without** syncing → row status **همگام‌سازی نشده** (no fatal).
6. With 21+ published products, use **قبلی / بعدی** — page 2 loads only that page’s recommendations.
7. Stop the API → page shows an unreachable error notice; admin does not fatal.

## Local manual apply smoke test

1. Stay **متصل** and sync at least one **simple** published product that is **not** below floor.
2. On **قیمت‌های پیشنهادی**, click **اعمال قیمت** on that simple row.
3. Expect success notice; open the product in WooCommerce → regular/active price matches the recommended value.
4. Confirm the API recorded the apply (`POST .../applied` / `applied_prices` row).
5. A **variable** (or grouped/external) row shows **فقط محصول ساده** — no Apply button.
6. Unsynced simple row has no Apply button.
7. If WooCommerce updates but API ack fails → error notice explains WC was updated; fix API and re-apply to notify.

## Local below-floor refuse smoke test (1.8)

1. Create a situation where the API sets `below_floor: true` (store price below effective floor — e.g. raise cost floor / lower WC price, then sync).
2. Open **قیمت‌های پیشنهادی** → row status **زیر کف**, badge shown, actions show **زیر کف — اعمال ممنوع** (no Apply button).
3. Forging an apply POST for that product id → error notice; WooCommerce price unchanged.

This Phase 1 connector calls `POST .../validate`, `POST .../products/sync`, `GET .../recommendation`, and `POST .../applied`. Later: marketplace packaging; API B8 cost UI / B7 rivals.

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
  class-price-apply.php
  class-admin-menu.php
  class-admin-pages.php
assets/
  css/admin-tokens.css
  css/admin.css
  fonts/vazirmatn/*.woff2
languages/   # placeholder for fa_IR later
```

No Composer / PHPUnit in the plugin for this slice.
