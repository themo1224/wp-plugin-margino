---
name: Pricing
last_updated: 2026-09-23
---

# Pricing Strategy

## Target problem

Online sellers who buy and resell goods lose money when costs go up (supplier, dollar, rent, staff, other overhead) but prices stay old — and lose sales when they sit above rivals on Torob/Digikala/Basalam and don’t notice. What’s hard is tracking full costs and rival prices by hand across many products, so they can be losing money or overpriced and not know it.

## Our approach

We win by turning the seller’s full cost profile plus live rival prices into a safe recommended price — never below cost + margin — and delivering that answer where they sell. Sellers do the minimum: sync the catalog and enter costs. We find rivals, match products, and refresh prices ourselves. WordPress is the first delivery path (plugin as a tool for WooCommerce users only). Instagram and an open API are part of the product vision and shown as coming soon until ready.

**Recommended price:** cost floor + margin is the hard floor; rival data (when available) shapes the suggest — never below the floor. No AI inventing prices.

## Who it's for

**Primary:** Buy-and-resell shops (perfume, silver accessories, and similar) whose buy-price moves with the dollar and inflation — They're hiring Pricing to show hidden losses from real costs or underpricing, and to show when they’re above rivals with no profit.

## Key metrics

- **Paid sales this month** - plan / product revenue in toman (own site, Zhaket, RTL)
- **Cost profiles completed** - shops that finished a real cost profile
- **First recommended price** - shops that got at least one recommended price
- **Price action taken** - shops that changed a price or acted on an alert after a recommendation
- **Auto-matched rivals** - products with a confident rival link without seller URL entry

## Tracks

### Cost & price engine

Cost profile, cost floor, and safe recommended price.

_Why it serves the approach:_ This is the core “never sell blind / never sell below cost” bet.

### Rival prices

Best UX: sellers never paste rival URLs. After sync, we auto-discover rivals.

- **Price source:** Torob and Snapp (price-comparison aggregators). Marketplace prices (Digikala/Basalam, etc.) show up through those aggregators — we do **not** scrape Digikala/Basalam as a primary path (fragile anti-bot).
- **Matching:** AI helps search and pick the right listing from catalog fields (name, brand, barcode when present). Confidence-gated auto-link; rare one-tap confirm only when confidence is low — still no URL entry.
- **Numbers:** scrape/cache aggregator prices on a schedule; AI does not invent rival prices.
- **Resilience:** stale or missing rivals → still recommend from cost floor; never block the product on scrape failure.

_Why it serves the approach:_ Without rival data, the recommended price can’t answer “am I too expensive?” — and without auto-match, rival UX fails.

### Delivery channels

Ship WordPress/WooCommerce first (plugin as a connector). Surface Instagram and API as coming soon; build them after the core engine and first paid users.

_Why it serves the approach:_ Reach sellers where they already sell, without pretending we are “a plugin company.”

### Get and keep buyers

Sell the SaaS on our site and via Zhaket/RTL for WordPress users; Instagram cold outreach to find and learn from buy-and-resell shops, not as the main cash channel.

_Why it serves the approach:_ Short-path revenue and the right first customers.

## Not working on

- Digikala / Basalam as *customers* (we don’t manage their marketplace listings)
- Direct Digikala / Basalam scraping as the main rival pipeline (aggregators first; no seller URL pasting for rivals)
- Asking sellers to paste Torob/Snapp/product URLs to enable rivals
- Using AI as the price authority (AI = matching help only; floor + rules decide recommend)
- Shipping Instagram automation or the public API as live v1 (coming soon only for now)
- Restaurant menus, unique game accounts, and other non-SKU categories as first customers
- Influencer discovery platform
- Positioning ourselves as a WordPress plugin vendor
- Selling a one-time Zhaket/RTL plugin that unlocks the full product forever

## Marketing

**Landing (hero):**
- عنوان: حتی اگر حواس‌ات نباشد، ضرر نمی‌کنی.
- توضیح: قیمت کالاهایت را با هزینه‌ات و قیمت روز به‌روز نگه می‌داریم و قیمت رقبا را هم رصد می‌کنیم — نه برای گران‌فروشی، برای اینکه بی‌خبر ضرر نکنی.

**One-liner:** قیمت به‌روز از روی هزینه و قیمت روز؛ رقبا زیر نظر — ضرر بی‌خبر، نه.

**Key message:** Promise is keep product prices updated from costs + قیمت روز, plus rival monitoring we run for them (no rival URL chores), so the seller doesn’t lose money even when they’re not watching. Not “raise prices.” WordPress connector exists; don’t put WooCommerce in the first screen. Instagram and API coming soon.

## Pricing

We sell a **SaaS subscription** (monthly / yearly). WooCommerce is a connector inside the plan, not a separate product. Zhaket/RTL may sell access for WordPress users but must not be a cheap one-time plugin that replaces the subscription (no forever access to the engine + rival prices). Yearly = about 10 months (2 months free). Exact toman amounts are not locked yet; Starter is the cheap door, Pro is ~2–3× Starter and is the plan we push.

- **پایه (Starter)** — Cost profile, recommended price, rival prices (limited products / slower refresh), alerts, WooCommerce connector. Instagram + API stay coming soon. For one shop, few SKUs.
- **حرفه‌ای (Pro)** — Everything in Starter, more products, faster rival refresh, stronger alerts (below cost / above rivals), optional WooCommerce auto-apply. For perfume / accessories shops with ~100–500 orders/month. **Primary paid plan.**
- **فروشگاه‌ها (Business)** — Several shops / more SKUs, priority support, API when it ships.
