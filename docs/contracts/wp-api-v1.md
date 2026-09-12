# WP ↔ API Contract (v1 — Phase 1)

Phase 1 connector surface only. The Pricing API owns cost/rival engines, notifications, reports, and analyses. WordPress is a store connector: authenticate, push products, read recommendations, and acknowledge applied prices.

**Base URL (placeholder):** `https://api.pricing.example/v1`  
**Auth:** `Authorization: Bearer <api_key>`  
**Content-Type:** `application/json`  
**OpenAPI sibling:** [`wp-api-v1.openapi.yaml`](./wp-api-v1.openapi.yaml)

---

## Auth

All Phase 1 connector endpoints require:

```http
Authorization: Bearer <api_key>
```

| Status | Meaning |
|--------|---------|
| `401` | Missing or invalid API key |
| `403` | Key valid but shop/plan inactive or not allowed |
| `422` | Request body/path failed validation |

Error body shape (all non-2xx unless noted):

```json
{
  "error": {
    "code": "invalid_api_key",
    "message": "Human-readable explanation."
  }
}
```

---

## Operations

### 1. Validate API key

`POST /v1/connector/validate`

Confirms the key and returns a minimal shop/plan stub for WP connection UI (plan 1.3+).

**Request body:** empty object or omitted.

```json
{}
```

**Response `200`:**

```json
{
  "ok": true,
  "shop": {
    "id": "shop_abc123",
    "name": "Example Store"
  },
  "plan": {
    "id": "plan_starter",
    "status": "active",
    "label": "Starter"
  }
}
```

---

### 2. Sync products

`POST /v1/connector/products/sync`

Upserts WooCommerce products from the store into the API.

**Request body:**

```json
{
  "currency": "IRR",
  "products": [
    {
      "external_id": "42",
      "sku": "SKU-001",
      "name": "Sample Product",
      "price": "1500000"
    }
  ]
}
```

| Field | Type | Notes |
|-------|------|--------|
| `currency` | string | ISO-like store currency (e.g. `IRR`, `IRT`) |
| `products[].external_id` | string | WooCommerce product ID as string |
| `products[].sku` | string \| null | Optional SKU |
| `products[].name` | string | Product title |
| `products[].price` | string | Current regular/sale price as decimal string |

**Response `200`:**

```json
{
  "synced": 1,
  "accepted": ["42"],
  "rejected": []
}
```

`rejected` items (if any):

```json
{
  "external_id": "99",
  "code": "invalid_price",
  "message": "Price must be a non-negative decimal string."
}
```

---

### 3. Get recommendation

`GET /v1/connector/products/{external_id}/recommendation`

Fetches the recommended price and floor-related flags for one synced product.

**Path:** `external_id` — WooCommerce product ID (string).

**Response `200`:**

```json
{
  "external_id": "42",
  "recommended_price": "1450000",
  "currency": "IRR",
  "below_floor": false,
  "floor_price": "1200000",
  "updated_at": "2026-09-07T12:00:00Z"
}
```

| Field | Type | Notes |
|-------|------|--------|
| `recommended_price` | string | Price WP may offer to apply |
| `below_floor` | boolean | If `true`, WP should refuse apply (plan 1.8) |
| `floor_price` | string \| null | Floor when known |
| `updated_at` | string | ISO-8601 UTC |

**Response `404`:** product not known to the API (sync first).

---

### 4. Acknowledge applied price

`POST /v1/connector/products/{external_id}/applied`

Tells the API which price WordPress wrote to WooCommerce after a **manual** apply.

**Request body:**

```json
{
  "applied_price": "1450000",
  "currency": "IRR",
  "source": "manual",
  "applied_at": "2026-09-07T12:05:00Z"
}
```

| Field | Type | Notes |
|-------|------|--------|
| `applied_price` | string | Price written to WooCommerce |
| `currency` | string | Same currency as sync |
| `source` | string | Phase 1: always `"manual"` |
| `applied_at` | string | ISO-8601 UTC |

**Response `200`:**

```json
{
  "ok": true,
  "external_id": "42",
  "applied_price": "1450000"
}
```

---

## Out of scope for WordPress (API-owned)

Do **not** call or implement these from the WP plugin in Phase 1 (or as WP responsibilities later):

- Notifications / alerts inbox
- Reports and analyses dashboards
- Cost engine configuration
- Rival price ingestion beyond what the API already computed into recommendations
- Auto-apply, cron-driven price writes, or webhooks **into** WP (later phases if product requires them)

---

## Phase 1 micro-plan map (WP repo)

| Plan | Uses this contract |
|------|--------------------|
| 1.1 Fundamentals | Document + scaffold only |
| 1.3 API key connect | `POST .../validate` |
| 1.5 Product sync | `POST .../products/sync` |
| 1.6 Recommendations UI | `GET .../recommendation` |
| 1.7 Manual apply | `POST .../applied` + WooCommerce price write |
