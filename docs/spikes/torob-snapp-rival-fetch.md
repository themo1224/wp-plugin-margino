# Spike: Torob / Snapp rival fetch (B7)

**Date:** 2026-09-23  
**Decision:** Conditional **go** for Torob behind a feature flag; Snapp deferred to adapter stub.  
**Authority:** `STRATEGY.md` (aggregators first; no seller URL paste; no Digikala/Basalam direct scrape).

---

## Torob

| Question | Finding |
|----------|---------|
| Can we fetch a product price reliably? | **Yes, with caveats.** Torob exposes JSON search/detail endpoints used by their web app (commonly `api.torob.com` search + base-product details). Community clients (`torob-client`, `torob-integration`) hit the same surface. Not a documented public partner API for read pricing. |
| Rate limits? | Official **seller webhook** is capped at **20 req/min/shop** (write path). Read/search limits are undocumented; third-party proxies advertise ~5–100 req/min. Treat production as **≤1 req/s**, cache aggressively, back off on 429. |
| Block risk? | **Medium.** Undocumented read usage can change shape or block IPs. Mitigate: feature flag off by default, User-Agent honesty, cache TTLs aligned to plan refresh, Fake driver for CI/local. |
| Matching | Prefer **barcode/GTIN** when present → brand+name → name-only search. AI/heuristic ranks candidates; confidence ≥ threshold auto-links; else one-tap confirm. **No seller URL paste.** |
| Legal / ToS | **Gray.** Official docs cover seller sync webhooks, not scraping for competitor intel. Robots/ToS may restrict automated harvesting. **Go for MVP only with:** flag off in prod until ops review, polite rate limits, store only listing id + aggregate prices (cheapest/median/count), no wholesale republishing of Torob UI. Revisit if Torob offers a licensed data feed. |

**Verdict:** **Go (flagged)** — Torob is the primary rival source for Full Starter.

---

## Snapp (price comparison)

| Question | Finding |
|----------|---------|
| Reliability | Snapp comparison surface is less consistently documented than Torob’s JSON search; HTML/app APIs shift more often. |
| Rate / block | Assume similar anti-bot risk; do not scrape until a dedicated spike with live fixtures. |
| Matching | Same auto-match pipeline can feed a future Snapp adapter. |

**Verdict:** **No-go for production scrape in this slice.** Ship `SnappCatalogClient` as a stub that returns empty results so the multi-source interface is ready.

---

## Refresh cadence (ops contract)

| Plan | Max rival SKUs | Refresh |
|------|----------------|---------|
| Starter (`plan_starter`) | 50 | every **24h** |
| Pro (`plan_pro`) | 500 | every **6h** |

Stale after ~1.5× refresh interval → engine falls back to cost floor and sets `rivals_stale`.

---

## Matching (STRATEGY-aligned)

- After product sync → queue auto-discover (no seller URLs).
- Build search queries from name / brand / barcode.
- Score candidates; auto-link high confidence; rare one-tap confirm only.
- Snapshots feed recommendation; **never recommend below effective floor**.
- Missing/stale rivals → cost-based recommend + stale flag.

---

## Implementation notes

- Default driver: `fake` in testing; HTTP Torob only when `RIVALS_TOROB_ENABLED=true`.
- Digikala/Basalam are **not** primary scrape targets (appear only via aggregators).
