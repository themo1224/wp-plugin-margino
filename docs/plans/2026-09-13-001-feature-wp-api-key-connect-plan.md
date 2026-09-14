<!--
artifact_contract: ce-unified-plan/v1
artifact_readiness: implementation-ready
product_contract_source: ce-plan-bootstrap
execution: code
-->

---
title: WP plugin API key connect + HTTP client (Phase 1.3–1.4)
date: 2026-09-13
artifact_contract: ce-unified-plan/v1
artifact_readiness: implementation-ready
product_contract_source: ce-plan-bootstrap
execution: code
origin: docs/plans/2026-09-07-002-feature-wp-admin-shell-plan.md; docs/contracts/wp-api-v1.md
---

# WP plugin API key connect + HTTP client (Phase 1.3–1.4)

## Goal Capsule

**Objective:** Let an Iranian WooCommerce admin connect the Pricing plugin to the live API — paste a base URL and API key, call `POST /v1/connector/validate`, and see connected vs disconnected on the existing **اتصال** screen.

**Product Authority:** Mohammad Ali (project owner)

**Authority hierarchy:** `STRATEGY.md` → WP↔API contract → this plan. Backend Phase 1 (B0–B5) is already shipped; this plan only consumes `validate`.

**Stop conditions:** Do not sync products, fetch recommendations, apply prices, auto-apply, or add extra connector endpoints. Do not build a seller dashboard in WP. Do not introduce Composer.

**Depends on:** Plugin 1.2 shell (`plugin/pricing/`). API `POST /v1/connector/validate` running (local: `http://localhost:8000/v1`).

**Product call:** Original roadmap split 1.3 (connect UI) and 1.4 (HTTP client). Ship them as **one user outcome**. A HTTP wrapper with no screen is not a product slice.

---

## Product Contract

### Problem

The plugin admin shell is live, and the API can validate keys, but Connection is still an empty state. A seller cannot complete step 1 of the WP loop: connect the store.

### Primary Actor

WooCommerce shop admin (`manage_woocommerce`) on a local or production WordPress site.

### Desired Outcome

After this plan: the **اتصال** page has a form; a valid key shows the shop name and plan from the API; an invalid key or unreachable API shows a clear disconnected/error state. Later plans (1.5+) reuse the same HTTP client.

### Requirements

- R1. Connection screen collects **API base URL** and **API key**, with Connect and Disconnect actions.
- R2. Connect calls only `POST {base}/connector/validate` with `Authorization: Bearer {key}` and JSON body `{}` (or omitted).
- R3. HTTP goes through a shared WP wrapper (`wp_remote_*`): timeout, JSON decode, contract error envelope `{ error: { code, message } }`.
- R4. Status strip on all Pricing screens reflects real state: connected (shop name) vs disconnected (reason).
- R5. Options use prefix `pricing_`. Do not log the API key. Do not print it back in HTML `value` after save (password field; blank means keep existing).
- R6. Capability `manage_woocommerce` + nonce on all writes. No new top-level menus.
- R7. Farsi copy on the Connection UI; keep 1.2 RTL chrome and brand buttons already reserved in `admin.css`.

### Key Flows

- F1. Admin enters `http://localhost:8000/v1` + seeded key → Connect → 200 → status **متصل** with shop name + plan label.
- F2. Admin enters a wrong key → 401 → not connected; Farsi error (invalid key). Previous successful connection is cleared only if we attempted connect with a new key that failed? **Decision:** failed connect does **not** keep a stale “connected” shop. Save URL; do not save the failed key; mark disconnected.
- F3. Admin clicks Disconnect → key and shop stub removed → status disconnected.
- F4. API down / timeout → disconnected with a “cannot reach server” message; do not crash admin.

### Acceptance Examples

- AE1. Given API seeded and running, when admin connects with `dev_pk_local_connector_key_do_not_use_in_prod`, then strip shows connected and shop **Local Dev Store** / plan **Starter**.
- AE2. Given a garbage key, when Connect is submitted, then 401 path shows disconnected and `invalid_api_key` (Farsi explanation).
- AE3. Given inactive plan (403 `plan_inactive`), when Connect is submitted, then disconnected with a plan-not-allowed message.
- AE4. Given no key stored, when any Pricing page loads, then strip says not connected — never “configured” without a successful validate.

### Scope

**In scope**

- HTTP client class
- Option storage (URL, key, last successful shop/plan)
- Connection form on existing `pricing-connection` page
- Live status strip
- Disconnect
- README smoke steps for `localhost:8880` WP + `localhost:8000` API
- Version bump `0.1.1` → `0.2.0`

**Out of scope**

- Product sync / recommendations / apply (1.5–1.7)
- Health polling / last-sync (1.8)
- Billing, dashboard, issuing keys in WP (keys stay seeder/B8)
- CORS, JS fetch to the API (all calls server-side)

---

## Planning Contract

### Settled decisions

- KTD1. Combine WP 1.3 + 1.4 into this artifact. `Governs R1–R3`
- KTD2. Auth header is `Authorization: Bearer` only (contract). No `X-Pricing-Key`. `Governs R2`
- KTD3. Base URL is a setting (no hardcoded host). Local default **placeholder in the empty field**: `http://localhost:8000/v1` as `placeholder` only, not auto-saved. `Governs R1`
- KTD4. Failed validate → disconnected; do not persist the rejected key. Persist URL so they can retry. `Governs F2`
- KTD5. No Composer / PHPUnit in the plugin this slice. Manual smoke on the user’s WP (`localhost:8880/wordpress`). `Governs R6`
- KTD6. HTTP timeout 15 seconds; `sslverify` left as WP default (HTTP localhost has no TLS). `Governs R3`

### Technical design

**Options**

| Option | Autoload | Contents |
|--------|----------|----------|
| `pricing_api_base_url` | yes | Sanitized URL, no trailing slash |
| `pricing_api_key` | **no** | Plaintext key (WP options table; same as typical plugin keys). Never echo, never `error_log`. |
| `pricing_connection` | yes | Array: `shop_id`, `shop_name`, `plan_id`, `plan_status`, `plan_label`, `connected_at` (UTC mysql datetime). Empty / absent = disconnected. |

Connected **iff** key exists **and** `pricing_connection` is a non-empty array from last 200 validate.

**HTTP client** (`Pricing_Http_Client`)

- `request( $method, $path, $body = null, $api_key = null, $base_url = null )`
- Resolve base + key from args or options
- `$path` like `/connector/validate` (leading slash)
- Full URL = `untrailingslashit( $base ) . $path`
- Headers: `Authorization: Bearer …`, `Content-Type: application/json`, `Accept: application/json`
- Return a small result object/array: `status` (int), `data` (array|null), `error_code`, `error_message`, `ok` (2xx)
- Map WP errors (timeout, DNS) to `error_code` `http_error` and a Farsi-safe English `message` the UI translates
- Parse JSON `error.code` / `error.message` when present

**Admin POST**

- Form on Connection page, `method="post"`, `action` = `admin-post.php`
- Actions: `pricing_connect`, `pricing_disconnect`
- Handlers: nonce `pricing_connection`, cap `manage_woocommerce`
- Redirect back to `admin.php?page=pricing-connection` with `pricing_notice=connected|disconnected|error` and optional `pricing_error` slug (not the raw key)

**UI**

- Replace `render_connection_body()` empty copy with labeled fields: آدرس سرویس، کلید API، اتصال، قطع اتصال
- Status strip: green dot when connected; gold/muted when not
- Connected hint: `{shop_name} — {plan_label}`
- Use existing `.pricing-admin .button-primary` rules; add only form layout CSS (stacked RTL fields)

**Files**

```
plugin/pricing/
  pricing.php                          # require new classes; bump version
  includes/
    class-http-client.php              # NEW
    class-connection.php               # NEW: options, connect/disconnect handlers
    class-plugin.php                   # register connection hooks
    class-admin-pages.php              # form + live status strip
    class-admin-menu.php               # unchanged unless needed
  assets/css/admin.css                 # form layout
  README.md                            # connect smoke
```

### Assumptions

- Local WP is already running at `http://localhost:8880/wordpress/` (confirmed). Plugin folder is already installed; after code change, copy/symlink `plugin/pricing/` again — **no reinstall required** unless they are not using a live copy of this folder.
- API: `composer dev` + `php artisan db:seed` on port 8000.
- Same machine: PHP in WP can open `http://localhost:8000`. If WP were in Docker later, URL would change — out of this plan.

### Phase 1 roadmap (after this)

1.1 → 1.2 → **1.3+1.4 (this)** → 1.5 Product sync → 1.6 Recs UI → 1.7 Manual apply → 1.8 Status/safety.

---

## Implementation Units

### U1. HTTP client

**Goal:** One place for connector HTTP so 1.5–1.7 do not invent `wp_remote_post`.

**Requirements:** R2, R3  
**Files:** Create `plugin/pricing/includes/class-http-client.php`; require from `pricing.php`.

**Approach:** Stateless class, static or thin instance methods. No SDK. Do not call validate here — only generic request. Treat non-JSON 2xx as error. Empty body for POST is `'{}'`.

**Test scenarios:**

- T1. Builds `http://localhost:8000/v1/connector/validate` from base + path (no double slash).
- T2. Sends Bearer header.
- T3. Timeout / connection refused returns `ok=false` and `http_error`, not a PHP fatal.

### U2. Connection options + admin-post handlers

**Goal:** Persist URL/key/shop stub; connect and disconnect safely.

**Requirements:** R1, R2, R4, R5, R6  
**Files:** Create `plugin/pricing/includes/class-connection.php`; modify `class-plugin.php` to `Pricing_Connection::register_hooks()`.

**Approach:**

- `connect()`: sanitize URL (`esc_url_raw`, require `http` or `https`), read key from POST or existing option if POST key blank, call client `POST /connector/validate`, on 200 save key (if posted), URL, and `pricing_connection` from `shop`/`plan` JSON; on failure delete `pricing_connection` and do not write a new key.
- `disconnect()`: `delete_option` key + connection; keep URL.
- Map `invalid_api_key`, `plan_inactive`, `validation_error`, `http_error` to Farsi admin notices.

**Test scenarios:**

- T1. 200 stores shop/plan; `is_connected()` true.
- T2. 401 does not store `pricing_connection`; `is_connected()` false.
- T3. Disconnect removes key; strip disconnected.
- T4. User without `manage_woocommerce` cannot hit admin-post (WP 403).

### U3. Connection form + live status strip

**Goal:** Replace 1.2 empty copy with the seller-facing connect UI.

**Requirements:** R1, R4, R7  
**Files:** `class-admin-pages.php`, `assets/css/admin.css`, `pricing.php` version `0.2.0`.

**Approach:**

- Form fields: `pricing_api_base_url` (text, `dir="ltr"`), `pricing_api_key` (password, `dir="ltr"`, autocomplete=new-password).
- Submit buttons: اتصال / قطع اتصال (disconnect only if a key exists).
- `render_status_strip()` reads `Pricing_Connection::status()` instead of hardcoded “هنوز پیکربندی نشده”.
- Admin notices from redirect query args; `esc_html` all copy.

**Test scenarios:**

- T1. Connection page shows two fields; no PHP notice.
- T2. All three Pricing screens show the same live strip.
- T3. CSS still only enqueued on Pricing screens.

### U4. Docs + copy-over note

**Goal:** Operator can connect WP at `:8880` to API at `:8000` without chat history.

**Requirements:** AE1  
**Files:** `plugin/pricing/README.md`; this plan stays the spec.

**Approach:** Document: API `composer dev` + seed; WP copy of `plugin/pricing/`; base URL `http://localhost:8000/v1`; key `dev_pk_local_connector_key_do_not_use_in_prod`; no reinstall required if the install path is already this folder (refresh the plugin files).

**Test scenarios:**

- T1. README lists validate-only (no sync/apply as required for this slice).

---

## Verification Contract

Manual smoke (user’s site):

1. API up: `POST http://localhost:8000/v1/connector/validate` with seeded Bearer returns 200.
2. Refresh/copy `plugin/pricing/` into `wp-content/plugins/pricing/` if not a symlink. **Do not** require deactivate/reactivate unless PHP fatals on old opcode cache — then restart PHP.
3. Open `wp-admin/admin.php?page=pricing-connection`.
4. Connect with good key → shop name in strip.
5. Connect with bad key → error notice, disconnected.
6. Disconnect → key gone, strip disconnected.
7. Overview and Products show the same strip.
8. No fatals; no key in page HTML after successful save (password input empty).

No Pest/PHPUnit in the plugin for this plan.

---

## Definition of Done

- U1–U4 complete per test scenarios
- Only `validate` is called
- Plugin version `0.2.0`
- Ready for a separate plan 1.5 (product sync) that reuses `Pricing_Http_Client`

---

## Appendix

### Local values (dev only)

| Setting | Value |
|---------|--------|
| WP admin (confirmed) | `http://localhost:8880/wordpress/wp-admin/admin.php?page=pricing-connection` |
| API base | `http://localhost:8000/v1` |
| Seeded key | `dev_pk_local_connector_key_do_not_use_in_prod` |

### Error copy (implementer may tighten wording)

| Code | Farsi direction |
|------|-----------------|
| `invalid_api_key` | کلید نامعتبر است. |
| `plan_inactive` | فروشگاه یا پلن اجازه اتصال ندارد. |
| `http_error` | به سرویس Pricing دسترسی نیست. API را روشن کنید. |
| `validation_error` | آدرس یا کلید درست نیست. |
