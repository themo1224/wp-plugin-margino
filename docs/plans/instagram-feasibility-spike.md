# Instagram feasibility spike (I2)

**Audience:** Product owner, eng lead  
**Phase:** I2 — [`instagram-phases.md`](./instagram-phases.md)  
**Time-box:** ≤ 1 week research (completed as written spike 2026-09-24)  
**Scope:** Docs only — no Meta app, no bot, no panel feature beyond existing به‌زودی  
**Product truth:** [`STRATEGY.md`](../../STRATEGY.md)

---

## Question

How should Pricing eventually let sellers act on recommended prices for Instagram — if at all — without violating STRATEGY’s “coming soon until core + paid users” rule?

---

## Options

| Option | Idea | Ban / ToS risk | Iran / access risk | Effort |
|--------|------|----------------|--------------------|--------|
| **A. Official Meta APIs** | Commerce / Instagram Content / Catalog APIs to update catalog fields or media captions where allowed | Medium (app review, policy) | **High** — Meta platform access, payment, and app review for Iranian businesses are constrained; Graph API availability from Iran infra is unreliable |
| **B. Manual assist** | Panel shows safe recommend + copy-ready caption / checklist; seller posts or edits on Instagram themselves | **Low** | Low — no Meta dependency | Low–medium (panel UX only) |
| **C. Browser / unofficial automation** | Bot logs in or drives unofficial clients to edit captions | **Very high** — ToS, account ban, legal | High operational fragility | Medium–high |
| **D. No-build** | Keep coming soon indefinitely; IG-only sellers use panel + change prices manually elsewhere | None | None | None |

---

## What Meta allows (for our use case)

Relevant product intent: **surface a recommended price and help the seller get that number onto an Instagram post/caption or catalog listing.**

| Path | Practical reading |
|------|-------------------|
| Instagram Graph API (Business / Creator + Facebook Page linked) | Can manage content for assets the seller authorizes; caption/media edits are possible in principle via official APIs after app review and correct permissions. Not a free-for-all “edit any caption” bot. |
| Instagram Shopping / Catalog | Catalog price updates are the cleanest “official” price surface when Commerce is available for the account — **not** guaranteed for all IR sellers. |
| Consumer scraping / session automation | Not an allowed product path for us (maps to Option C). |

**Spike conclusion on Meta:** Official APIs exist for *some* connected Business accounts with Shopping/catalog or content permissions, but they do **not** remove Iran access, app-review, and “seller must OAuth a viable IG Professional account” constraints. They also do not justify shipping automation as live v1 before engine + paid WP users (STRATEGY).

---

## Iran / access constraints

1. **Platform access:** Meta developer apps, Business verification, and Commerce onboarding are often blocked, delayed, or impractical for Iran-based operators and many local sellers.
2. **Seller reality:** Many ICP shops are IG-heavy with personal or lightly professional accounts; not all can complete Shopping / Business linking.
3. **Infra:** Calling Meta Graph from IR-hosted API may need proxies or offshore workers — ops cost and policy risk even for Option A.
4. **Trust:** STRATEGY already forbids shipping Instagram automation as live v1; outreach learning (M0–M1) does not require APIs.

These constraints make **A** a later, gated bet — not the default MVP.

---

## Recommendation

**Primary: B — Manual assist** (when PO re-opens Instagram as a delivery channel after engine + first paid users).

**Rationale:**

- Matches STRATEGY honesty: coming soon until ready; never claim a live bot.
- Lowest ban/ToS risk; works for IG-heavy sellers who cannot complete Commerce onboarding.
- Reuses panel recommend already required as key product proof (rival + safe price).
- Natural MVP sentence for I4 later: *Seller sees recommend in panel and copies a caption block for Instagram; we do not auto-edit posts in MVP.*

**Secondary (later only):** Revisit **A** if (1) paid WP cohort exists, (2) a spike with a real seller Business account proves catalog/content update in staging, (3) I3 legal review passes.

**C — Reject by default.** Do not build unofficial automation unless PO explicitly accepts ban/legal risk in writing (tracker rule).

**D — Fallback:** If even B fails the honesty test (e.g. we cannot map SKUs to posts without toxic UX), keep coming soon and point IG-only sellers at panel + manual price change. Prefer trying B before locking D forever.

---

## Go / no-go for I3+

| Decision | Value |
|----------|--------|
| **Go to I3?** | **Go** — but scoped to **assist-first (B)** risk posture, not full automation |
| **Go to I5–I7 now?** | **No-go** — wait for PO re-open + engine + first paid users; I7 only if A is later approved |
| **Option C** | **No-go** unless explicit PO accept |
| **Ship product automation in v1?** | **No** — STRATEGY unchanged |

**Next when unblocked:** I3 legal/ToS review assuming B (and optional future A); then I4 lock the one-sentence MVP.

---

## Time-box record

| Item | Value |
|------|-------|
| Budget | ≤ 1 week |
| Actual | Written spike 2026-09-24 (research note; no Meta sandbox built) |
| Follow-up spike (optional) | 2–3 days hands-on Graph/Commerce only after PO re-open + one willing seller Business account |

---

## Exit criteria (I2)

- [x] Spike note: what Meta allows for our use case  
- [x] Iran / access constraints called out  
- [x] Recommendation: **B** (with A later / C reject / D fallback)  
- [x] Time-box recorded  
- [x] Written go / no-go for I3+ (assist-scoped go; automation no-go for now)
