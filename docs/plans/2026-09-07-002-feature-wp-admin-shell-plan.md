<!--
artifact_contract: ce-unified-plan/v1
artifact_readiness: implementation-ready
product_contract_source: ce-plan-bootstrap
execution: code
-->

---
title: WP plugin admin shell + brand (Phase 1.2)
date: 2026-09-07
artifact_contract: ce-unified-plan/v1
artifact_readiness: implementation-ready
product_contract_source: ce-plan-bootstrap
execution: code
origin: docs/plans/2026-09-07-001-feature-wp-plugin-fundamentals-plan.md
---

# WP plugin admin shell + brand (Phase 1.2)

## Goal Capsule

**Objective:** Give the Pricing WooCommerce connector an RTL-ready, branded admin chrome (Overview / Connection / Products) so later Phase 1 plans can drop API key and sync UI into a finished shell.

**Stop conditions:** No API key forms, HTTP client, product sync, recommendations, or price apply in this plan.

**Depends on:** Plan 1.1 scaffold in `plugin/pricing/`.

---

## Product Contract

### Requirements

- R1. Three admin screens: Overview (home), Connection (placeholder), Products (placeholder).
- R2. Shared RTL shell (`dir="rtl"` `lang="fa"`) with brand header, nav, and status strip.
- R3. Brand CSS using landing tokens; self-hosted Vazirmatn; enqueue only on Pricing screens.
- R4. Connection and Products show Farsi empty states only — no inputs or network calls.

### Out of scope

- API key connect (1.3), HTTP client (1.4), sync / recommendations / apply (1.5–1.7), status/safety (1.8).

---

## Planning Contract

### Approach

- `Pricing_Admin_Menu` registers menus and assets.
- `Pricing_Admin_Pages` renders shared header + page bodies.
- `admin-tokens.css` + `admin.css` + `assets/fonts/vazirmatn/*.woff2`.

### Phase 1 roadmap (unchanged)

1.1 Fundamentals → **1.2 Admin shell (this)** → 1.3 API key → 1.4 HTTP → 1.5 Sync → 1.6 Recommendations → 1.7 Manual apply → 1.8 Status/safety.

---

## Implementation Units

### U1. Submenus + shared shell

**Files:** `plugin/pricing/includes/class-admin-menu.php`, `plugin/pricing/includes/class-admin-pages.php`, `plugin/pricing/pricing.php`

### U2. Brand CSS + Vazirmatn

**Files:** `plugin/pricing/assets/css/admin-tokens.css`, `plugin/pricing/assets/css/admin.css`, `plugin/pricing/assets/fonts/vazirmatn/`

### U3. Empty states + docs

**Files:** page body methods in `class-admin-pages.php`, `plugin/pricing/README.md`, this plan artifact.

---

## Verification Contract

- Activate with WooCommerce; open all three Pricing screens without fatals.
- Confirm RTL Farsi chrome, brand colors, Vazirmatn; no forms on Connection/Products.
- Confirm CSS not loaded on unrelated admin screens.

## Definition of Done

- U1–U3 complete; ready for Plan 1.3 (API key connect).
