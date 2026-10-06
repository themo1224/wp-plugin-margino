# Marketplace packaging (Zhaket / RTL)

## Product rule

Sell **SaaS access** for WordPress users. Do **not** sell a one-time plugin that forever unlocks the engine + rivals (`STRATEGY.md`).

## Zip contents

Package folder as `pricing/` (contains `pricing.php`):

- `pricing.php`, `includes/`, `assets/`, `languages/`, `readme.txt`
- Optional: `assets/screenshots/` (admin connect + products)

Exclude: `.git`, local SQL seeds, unused docs.

## Listing copy (short)

- Connector for WooCommerce → Pricing SaaS
- Requires active subscription (dashboard signup)
- RTL / Farsi admin
- Manual price apply; auto-apply is Pro later

## After purchase flow

1. Buyer creates SaaS account → gets `shop_public_id` + API key.
2. Marketplace webhook (or ops) calls `POST /webhooks/marketplace/license` with expiry.
3. Buyer pastes API URL + key into plugin Connection page.

See [`docs/deploy/production.md`](../../docs/deploy/production.md).
