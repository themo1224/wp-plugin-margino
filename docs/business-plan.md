# Business plan — Pricing (simple)

**Last updated:** 2026-09-26  
**Product:** SaaS for buy-and-resell shops (cost floor + rival prices). WordPress plugin = WooCommerce connector only.

---

## 1. What we sell

| Channel | What the buyer gets | What they do **not** get |
|---------|---------------------|---------------------------|
| Own site | Monthly / yearly SaaS plan | Forever unlock |
| RTL / Zhaket | Time-bound access (maps to a plan) | One-time plugin that unlocks engine + rivals forever |

Rule: marketplace purchase = subscription window, not a forever license.

---

## 2. Who pays

- Primary: perfume / silver accessories / similar resellers on WooCommerce
- Pain: dollar & costs move; Torob/Snapp rivals move; they lose money or lose sales without noticing
- Willingness to pay: shops with ~100–500 orders/month → push **Pro**

---

## 3. SaaS prices (toman)

Yearly = **10× monthly** (≈ 2 months free).

| Plan | Monthly | Yearly | For |
|------|---------|--------|-----|
| **پایه (Starter)** | **249,000** | **2,490,000** | 1 shop, few SKUs, rivals slower / capped |
| **حرفه‌ای (Pro)** | **690,000** | **6,900,000** | Main plan — more SKUs, faster rivals, stronger alerts, optional auto-apply later |
| **فروشگاه‌ها (Business)** | **1,490,000** | **14,900,000** | Multi-shop / big catalogs, priority support, API when ready |

**Why these numbers**

- Starter is a cheap door (easy yes on RTL / first try).
- Pro is ~2.8× Starter (matches strategy: Pro ≈ 2–3×).
- One wrong price on perfume/accessories often costs more than one month of Pro → easy ROI story.

**Limits (suggest for launch)**

| | Starter | Pro | Business |
|-|---------|-----|----------|
| Products with rival refresh | 50 | 300 | 1,500+ |
| Rival refresh | every 48h | every 12–24h | every 6–12h |
| Shops | 1 | 1 | 3+ |
| Manual apply (WP) | yes | yes | yes |
| Auto-apply (later) | no | yes | yes |

---

## 4. RTL / Zhaket listing price

Do **not** list a cheap forever plugin.

| Listing | Price (toman) | Maps to |
|---------|---------------|---------|
| **Starter — ۱ ساله** | **2,490,000** | Starter yearly |
| **Pro — ۱ ساله** (featured) | **6,900,000** | Pro yearly |
| Optional: Starter — ۶ ماهه | **1,490,000** | Starter ~6 months |

**Listing copy (short)**

- Connector for WooCommerce → Pricing SaaS  
- Needs active plan (dashboard signup + API key)  
- RTL/Farsi admin  
- Cost-safe recommend + rival watch (in panel)  
- Not a forever unlock of the engine  

**After purchase:** buyer signs up → gets API key → webhook sets plan expiry → paste key in plugin.

---

## 5. AI subscription (when we add AI)

AI = **matching & help only** (find Torob/Snapp listing, suggest confirm).  
AI does **not** invent prices. Floor + rules still decide the recommend.

### Option A — include in plans (simpler)

| Plan | AI |
|------|-----|
| Starter | Basic match, lower confidence → more “confirm” taps; soft monthly AI cap |
| Pro | Full AI match + higher auto-link confidence; higher cap |
| Business | Highest cap + priority |

No separate AI SKU. Raise Pro story: “رقبا را هوشمند پیدا می‌کنیم.”

### Option B — AI add-on (if cost is high)

| Add-on | Monthly | Yearly | Includes |
|--------|---------|--------|----------|
| **هوش مصنوعی (AI Match)** | **290,000** | **2,900,000** | Auto rival match, smarter search, fewer manual confirms |
| **AI Insights** (later) | **190,000** | **1,900,000** | Weekly Farsi summary: “above rivals / below floor / stale” |

Stack example: Pro + AI Match ≈ **980,000**/month.

**Recommend for launch:** Option A (AI inside Pro). Add Option B only if LLM cost hurts margins.

---

## 6. Simple unit economics (sanity check)

| | Assume |
|-|--------|
| Target first year | 30 paying shops |
| Mix | 10 Starter + 18 Pro + 2 Business (monthly avg) |
| Rough MRR | ~ (10×249k) + (18×690k) + (2×1.49M) ≈ **17.5M toman/month** |
| Yearly bias | Many buy yearly on RTL → cash upfront, lower churn |

Costs to watch: rival scrape infra, LLM match calls, support. Keep Starter AI capped.

---

## 7. Go-to-market (short)

1. Sell Pro as the default story; Starter only as trial door / small shops.  
2. RTL/Zhaket for WordPress discovery; own site for renewals + Business.  
3. Instagram outreach = learning + leads, not main cash.  
4. Proof in panel: “we found your rivals” — not only a number.

---

## 8. Decisions locked for now

| Topic | Decision |
|-------|----------|
| Forever one-time plugin | **No** |
| RTL price | Yearly Starter **2.49M** / Pro **6.9M** |
| Main plan | **Pro @ 690k / month** |
| AI at launch | Inside Pro (Option A); add-on only if needed |
| Currency | Toman; review every 3–6 months with inflation |

---

## 9. Open later

- Exact product caps after first 10 paid users  
- Free trial length (suggest 7–14 days, no rivals or rivals delayed)  
- Zhaket fee % vs own-site discount for yearly  
- Business custom quotes above 1,500 SKUs  
