# Board Brief: Margino (مارژینو) — Meeting #2 (Post-Validation & PRD Review)

## 1. Executive Summary & Evolution Since Meeting #1
In Meeting #1, the board raised four primary warnings:
1. High onboarding friction (entering cost prices across 500 SKUs).
2. Fragility of Torob/Snapp scrapers.
3. Fear of price collapse from false AI matching.
4. Resistance to recurring monthly SaaS fees vs. one-time Zhaket plugins.

Since then, the founder completed the formal Product Requirements Document ([docs/PRD.md](file:///c:/Users/mohammadALi/iran-saas/docs/PRD.md)), ran a 20-buyer simulation panel ([founder/panel/results.md](file:///c:/Users/mohammadALi/iran-saas/founder/panel/results.md)), and constructed a comprehensive offer stack ([founder/offer.md](file:///c:/Users/mohammadALi/iran-saas/founder/offer.md)) that boosted buyer intent from 45% to 65%.

## 2. Updated Product Architecture & Guardrails
- **Zero Manual Catalog Entry:** The lightweight plugin automatically syncs existing WooCommerce products, titles, brands, and barcodes in under 60 seconds. Store owners never input products manually.
- **100% Cloud Architecture & Zero Site Load:** 99.9% of all scraper execution, AI entity matching, and cost math runs on Margino's cloud backend (Laravel + Horizon). The WordPress connector is an ultra-lightweight REST client (< 1MB) with zero database locks and dedicated cache-purging hooks for LiteSpeed Cache and WP Rocket.
- **1-Click Iranian Niche Cost Templates:** Instead of keying in hundreds of unit costs, merchants select pre-calculated templates (e.g., Perfume: 35k packing + 3% breakage + 25% margin; Silver: 20k packing + 5% metal buffer + 30% margin).
- **Safety Circuit Breakers:** A hardcoded rule prevents any automated repricing from changing a price by more than ±10% in 24 hours without explicit human merchant approval, plus a one-tap master Kill Switch.

## 3. Evidence from the Market & Consumer Panel
- **Initial Demand:** 9 Buy / 11 Pass (45% buy rate). Top hesitations were site speed fears and data-entry exhaustion.
- **Demand After Architectural & Offer Clarification:** 13 Buy / 7 Pass (**65% buy rate**).
- **Economic Sensitivity (Van Westendorp Curve):**
  - Point of Least Resistance (OPP): ~305,000 Toman/mo
  - Indifference / Fair Value (IPP): ~550,000 Toman/mo (matches Starter Plan at 500k)
  - Point of Marginal Expensiveness (PME): ~1,095,000 Toman/mo (caps Pro Plan at 980k)

## 4. Current Offer & Pricing Structure
- **Starter Plan:** 500,000 Toman/month (~$6 USD). Up to 100 SKUs, daily competitor refresh, manual 1-click apply.
- **Pro Plan (Hero Tier):** 980,000 Toman/month (~$13 USD). Up to 1,000 SKUs, 6-hour refresh, automated apply with safety circuit breakers, priority alerts.
- **Risk Reversal:** 7-day free trial (no card needed) + 30-day 100% money-back guarantee.

## 5. Remaining Constraints
- Scraper dependency on Torob and Snapp (requires rotating residential proxy pools).
- Trust building in a market that favors word-of-mouth Telegram/Instagram recommendations over cold signups.
