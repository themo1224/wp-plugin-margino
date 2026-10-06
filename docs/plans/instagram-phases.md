# Instagram phases tracker

**Audience:** Product owner, product manager, panel / API / growth  
**Last reviewed:** 2026-09-24  
**Status source of truth:** this file (checkboxes). Product truth: [`STRATEGY.md`](../../STRATEGY.md).

---

## How to use this document

1. Instagram has **two separate tracks**. Do not mix them in planning or marketing copy.
2. Mark `- [x]` only when verified. Coming-soon UI does **not** mean the product channel is live.
3. Do **not** start **I5+** (product build) until PO re-opens Instagram as a delivery channel — STRATEGY currently forbids shipping automation as live v1. I2 spike is written; I3+ still gated.
4. Marketing outreach (**M** phases) can run anytime; it does not require Meta APIs or bots.

| Mark | Meaning |
|------|---------|
| `- [ ]` | Remaining |
| `- [x]` | Done and verified |
| Status: **Not started** | No meaningful work yet |
| Status: **In progress** | Some checkboxes done, phase not shippable |
| Status: **Done** | Exit criteria met |
| Status: **Deferred** | Explicitly waiting on STRATEGY / PO re-open |
| Status: **Blocked** | Waiting on a decision or external spike |

---

## Current snapshot (2026-09-24)

| Track | Today | Gap |
|-------|--------|-----|
| **Product channel** (update prices on Instagram) | Coming-soon in panel; I2 spike recommends **manual assist (B)** | No Meta integration, no bot; I3–I8 deferred until PO re-open |
| **Marketing outreach** (find buy-and-resell shops) | Playbook + 30-seed lead list + weekly log in `docs/growth/` | Need real handles filled + ≥1 month logged conversations |

**Rule (STRATEGY):** Surface Instagram as **coming soon**; build product automation **after** the core engine and first paid users. Do **not** ship Instagram automation or treat Instagram as a live v1 channel.

---

## Two tracks (do not merge)

| Track | Goal | Ships when |
|-------|------|------------|
| **A — Product channel** | Seller links shop ↔ Instagram and applies recommended prices to posts/captions (or a safe manual assist) | After engine + paid WP users; PO re-opens; legal/tech spike passes |
| **B — Growth outreach** | Use Instagram DMs/content to **find and learn** from perfume / accessories resellers | Anytime; human-led; not product automation |

---

## Phase map

| ID | Phase | Track | Unblocks | Status |
|----|--------|-------|----------|--------|
| **I0** | Strategy lock + coming-soon surface | A + B | Honest product story | **Done** |
| **I1** | Panel coming-soon UX polish | A | Sellers see Instagram as future channel | **Done** (thin) |
| **M0** | Outreach playbook (human) | B | First learning conversations | **Done** |
| **M1** | Lead list + weekly cadence | B | Repeatable discovery | **In progress** |
| **I2** | Feasibility spike (API vs assist vs no-build) | A | Go / no-go for automation | **Done** (recommend B) |
| **I3** | Legal / ToS / ban-risk review | A | Safe MVP scope | **Deferred** |
| **I4** | Product MVP decision (locked approach) | A | Spec for I5+ | **Deferred** |
| **I5** | Seller link + catalog mapping | A | Know which IG post ↔ which SKU | **Deferred** |
| **I6** | Apply recommend (manual assist first) | A | Value without full bot | **Deferred** |
| **I7** | Automation / Meta API (if approved) | A | Optional auto update | **Deferred** |
| **I8** | Entitlements (Starter coming-soon → Pro/Business) | A | Plan packaging | **Deferred** |

**Must-precede (product):** I0 → I1 → *(PO re-open)* → I2 → I3 → I4 → I5 → I6 → (I7 if approved) → I8.

**Must-precede (growth):** M0 → M1 (independent of I2+).

---

## Overview: done vs remaining

### Done

- [x] STRATEGY: Instagram = coming soon for product; not live v1 automation
- [x] STRATEGY: Instagram cold outreach allowed for learning / lead find (not main cash channel)
- [x] Backend phases list Instagram bot under “Later / do not start”
- [x] Seller panel shows Instagram as **به‌زودی** (coming-soon chip in sidebar)
- [x] Landing / marketing key message: Instagram coming soon (alongside public API)
- [x] Written outreach playbook (`docs/growth/instagram-outreach-playbook.md`)
- [x] Feasibility spike with A/B/C/D recommendation (`docs/plans/instagram-feasibility-spike.md` → **B**)

### Remaining (product channel — deferred)

- [ ] Legal / Meta ToS / account-ban risk written decision (I3)
- [ ] Locked MVP: what “Instagram for Pricing” means in one sentence (I4)
- [ ] Seller connects Instagram identity to shop
- [ ] Map SKUs ↔ Instagram posts / product stickers / captions
- [ ] Show recommend in panel with “copy for Instagram” or guided apply
- [ ] Optional automation only if I2+I3+I4 approve (I2 recommends assist-first; A later)
- [ ] Plan entitlements when channel goes live

### Remaining (growth — in progress)

- [x] Written outreach playbook (who, what message, what we learn)
- [x] Target list for perfume / silver / accessories resellers (30 seeded; handles TBD)
- [x] Weekly cadence + notes template in-repo
- [ ] Fill real handles + run outreach; ≥1 month tracked conversations + 3 insights/month

---

## I0 — Strategy lock + coming-soon surface

**Status:** Done  
**Outcome:** Everyone agrees Instagram product automation is not v1; marketing may still use Instagram for outreach.

- [x] STRATEGY “Delivery channels”: WP first; Instagram coming soon until core + paid users
- [x] STRATEGY “Not working on”: shipping Instagram automation as live v1
- [x] STRATEGY “Get and keep buyers”: Instagram cold outreach for learning, not main cash
- [x] Starter plan copy: Instagram stays coming soon
- [x] Cross-link from backend “Later” table to this tracker

**Exit criteria**

- [x] No sprint treats Instagram bot as in-scope for connector MVP / Pricing MVP

---

## I1 — Panel coming-soon UX

**Status:** Done (thin)  
**Apps:** `panel/` (seller SPA)

- [x] Sidebar / secondary nav chip: اینستاگرام — به‌زودی
- [x] Chip is non-navigating (no fake live feature)
- [ ] Optional later: dedicated `/instagram` placeholder page with one short Farsi explanation + CTA to products/rivals (still not a product)

**Exit criteria**

- [x] Seller can see Instagram is planned without believing it works today

---

## M0 — Outreach playbook (human)

**Status:** Done  
**Track:** Growth only — no code required  
**Artifact:** [`../growth/instagram-outreach-playbook.md`](../growth/instagram-outreach-playbook.md)

- [x] Define ICP for cold outreach (buy-and-resell, perfume / accessories, WooCommerce or IG-heavy)
- [x] Script: problem (cost + rivals), not “raise prices”
- [x] Learning questions (where they sell, how they change price, Torob pain)
- [x] Compliance: no spammy automation; manual or approved tools only
- [x] Where notes live — picked **`docs/growth/`** (not Notion/sheets)

**Exit criteria**

- [x] A one-page playbook a teammate can run without inventing copy

---

## M1 — Lead list + cadence

**Status:** In progress  
**Depends on:** M0  
**Artifacts:** [`../growth/instagram-lead-list.md`](../growth/instagram-lead-list.md), [`../growth/instagram-outreach-log.md`](../growth/instagram-outreach-log.md)

- [x] Seed 20–50 target accounts / shops (30 seeded; handles filled before DM)
- [x] Weekly outreach quota + reply tracking (template live; first week not yet run)
- [ ] Feed 3 product insights back per month (what sellers asked for)

**Exit criteria**

- [ ] At least one month of tracked conversations logged

---

## I2 — Feasibility spike

**Status:** Done  
**Must-precede:** I0, I1; engine + first paid users preferred before **building** I5+  
**Artifact:** [`instagram-feasibility-spike.md`](./instagram-feasibility-spike.md)

Decide **one** primary approach (document in a short spike note under `docs/plans/`):

| Option | Idea | Risk |
|--------|------|------|
| **A. Official Meta APIs** | Commerce / content APIs where available in Iran context | Access, app review, region limits |
| **B. Manual assist** | Panel shows recommend + copy-ready caption / checklist; seller posts | Low ban risk; slower |
| **C. Browser / unofficial automation** | Bot edits captions | High ToS / ban / legal risk — default **reject** unless PO accepts |
| **D. No-build** | Keep coming soon indefinitely; Instagram-only sellers use panel + manual price | Honest if spike fails |

- [x] Spike note: what Meta allows for our use case
- [x] Iran / access constraints called out
- [x] Recommendation: **B** (A later if proven; C reject; D fallback)
- [x] Time-box (≤ 1 week) — written spike 2026-09-24

**Exit criteria**

- [x] Written go / no-go; C requires explicit PO accept of ban risk  
  → **Go I3** scoped to assist-first (B); **no-go** I5–I7 build and automation until PO re-open + paid cohort

---

## I3 — Legal / ToS / ban-risk review

**Status:** Deferred  
**Must-precede:** I2

- [ ] Meta terms relevant to price updates / automation summarized
- [ ] Seller liability vs our liability
- [ ] Support playbook if an account is restricted
- [ ] Decision recorded: proceed / assist-only / stop

**Exit criteria**

- [ ] PO sign-off on risk posture

---

## I4 — Product MVP decision (locked)

**Status:** Deferred  
**Must-precede:** I2, I3

Lock **one** sentence, e.g.:

> “Seller sees recommend in panel and copies a caption block for Instagram; we do not auto-edit posts in MVP.”

- [ ] In-scope / out-of-scope list
- [ ] Success metric (e.g. sellers who used “copy for IG” after a recommend)
- [ ] Update STRATEGY when approach leaves “coming soon”

**Exit criteria**

- [ ] STRATEGY + this file agree on the locked MVP

---

## I5 — Seller link + catalog mapping

**Status:** Deferred  
**Must-precede:** I4

- [ ] Auth / connect model (OAuth or manual IG handle + proof)
- [ ] Data model: shop ↔ Instagram identity
- [ ] Map product SKU ↔ post URL / media id / caption token (confidence rules)
- [ ] Panel UI: linked products vs unlinked
- [ ] No rival URL pasting pattern — keep STRATEGY matching rules elsewhere

**Exit criteria**

- [ ] Seller can link ≥1 product to an Instagram post in staging

---

## I6 — Apply recommend (manual assist first)

**Status:** Deferred  
**Must-precede:** I5  
**Default MVP if automation blocked**

- [ ] From product row: show safe recommend + suggested caption snippet
- [ ] One-tap copy; mark “applied on Instagram” (honor system or screenshot later)
- [ ] Analytics: copy / mark-applied events
- [ ] Empty / error states in Farsi

**Exit criteria**

- [ ] Seller can act on a recommend for Instagram without a bot

---

## I7 — Automation / Meta API (optional)

**Status:** Deferred  
**Must-precede:** I4 chose Option A (or PO-approved path); I5; I3

- [ ] Implement approved API path only
- [ ] Rate limits, retries, audit log of edits
- [ ] Kill switch + disconnect
- [ ] Never silent-edit without seller consent setting

**Exit criteria**

- [ ] Staging proof: one caption/price field updated via approved API with audit trail

---

## I8 — Entitlements + packaging

**Status:** Deferred  
**Must-precede:** I6 (or I7 if that is the live path)

- [ ] Starter: coming soon or limited assist
- [ ] Pro / Business: higher limits when live
- [ ] Marketplace / landing copy updated from “به‌زودی” to live only when true

**Exit criteria**

- [ ] Plan matrix and panel chips match reality

---

## Explicitly out of scope (until STRATEGY changes)

- Instagram as the **main** cash channel
- Unofficial scrapers / banned automation as default
- Influencer discovery platform
- Replacing WooCommerce connector with Instagram-only onboarding for v1
- Building Instagram UI inside the WordPress plugin

---

## Related docs

| Doc | Role |
|-----|------|
| [`STRATEGY.md`](../../STRATEGY.md) | Product approach; coming soon vs outreach |
| [`backend-phases.md`](./backend-phases.md) | API engine; lists Instagram bot under Later |
| [`instagram-feasibility-spike.md`](./instagram-feasibility-spike.md) | I2 go/no-go; recommend manual assist (B) |
| [`../growth/instagram-outreach-playbook.md`](../growth/instagram-outreach-playbook.md) | M0 human DM playbook |
| [`../growth/instagram-lead-list.md`](../growth/instagram-lead-list.md) | M1 seed targets |
| [`../growth/instagram-outreach-log.md`](../growth/instagram-outreach-log.md) | M1 weekly cadence + insights |
| [`2026-09-03-001-feature-dynamic-repricer-iran-plan.md`](./2026-09-03-001-feature-dynamic-repricer-iran-plan.md) | Early note: IG bot approach undecided / risky |
| Seller panel `NavSecondary` | Coming-soon chip |

---

## How status rolls up

| Question | Answer (2026-09-24) |
|----------|---------------------|
| Can sellers update Instagram prices via Pricing today? | **No** |
| Is that a bug? | **No** — intentional coming soon |
| What is done? | Strategy + panel coming-soon + M0 playbook + I2 spike (**B**) |
| What can we do now without Meta? | **M1** outreach (fill handles, log conversations) |
| When does product work start? | After PO re-opens **I3+** (I2 done; build still gated) |
| Spike recommendation? | **Manual assist (B)**; reject C; A later if proven |
