# Production deploy (Full Starter)

## Stack

- PHP 8.3+, Composer, Node for Vite assets
- Postgres (or MySQL) in production — not SQLite
- Queue worker (`database` or Redis) for rival discover/refresh + alerts
- Scheduler: `* * * * * php artisan schedule:run`

## Env checklist

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-api.example

DB_CONNECTION=pgsql
QUEUE_CONNECTION=database
MAIL_MAILER=smtp

# Never enable stub recommendations in production
CONNECTOR_STUB_RECOMMENDATIONS=false

# Rivals: keep Torob off until spike ops sign-off
RIVALS_DRIVER=http
RIVALS_TOROB_ENABLED=false
RIVALS_SNAPP_ENABLED=false

ALERTS_ENABLED=true

# Zhaket / RTL license webhook (required for marketplace activate)
MARKETPLACE_WEBHOOK_SECRET=long-random-secret
```

## Release steps

1. `composer install --no-dev -o`
2. `php artisan migrate --force`
3. `npm ci && npm run build`
4. `php artisan config:cache && php artisan route:cache`
5. Run queue worker + scheduler
6. Health: `GET /up`

## Marketplace license mapping

`POST /webhooks/marketplace/license` with header `X-Marketplace-Secret`.

Body (JSON):

```json
{
  "source": "zhaket",
  "external_license_id": "purchase-id",
  "shop_public_id": "shop_…",
  "plan_code": "plan_starter",
  "ends_at": "2026-12-01T00:00:00Z"
}
```

Rules: marketplace sources **must** include `ends_at` (no forever unlock). Maps purchase → shop plan + `shop_licenses` row.

Seller signs up on the SaaS, gets `shop_public_id` from the dashboard, then marketplace purchase activates the plan.

## Starter entitlements (already on plans)

| Plan | Rival refresh | Max rival SKUs |
|------|---------------|----------------|
| Starter | 24h | 50 |
| Pro | 6h | 500 |
