# Dynamic Repricer for Iranian Online Sellers — Plan

<!--
artifact_contract: ce-unified-plan/v1
artifact_readiness: requirements-only
product_contract_source: ce-brainstorm
-->

---

## Goal Capsule

**Objective:** Build a dynamic pricing SaaS that helps Iranian online sellers (WordPress/WooCommerce stores, custom-coded sites, Instagram shops) protect their margins and stay competitive during inflation — by combining their cost structure with live competitor prices scraped from Torob and Snapp, and automatically or alerting price changes across their sales channels.

**Product Authority:** Mohammad Ali (project owner)

**Open Blockers:**
- Torob and Snapp scraping feasibility and rate limits need spiking before v1 commit
- Instagram bot approach (API vs scraping vs DM-based) is undecided
- Legal/ToS risk of scraping Iranian price-comparison platforms needs assessment

---

## Product Contract

### Problem

Iranian online sellers face a constant, manual repricing burden driven by inflation, currency fluctuation (IRR vs USD), and supplier cost changes. Their current process is largely manual: they either guess, apply a fixed markup, or periodically check competitor listings themselves. This results in two failure modes:
1. **Underselling** — prices don't keep up with cost increases, eroding margin or creating losses
2. **Overpricing** — prices stay too high relative to competitors, losing sales

There is no tool tailored to Iran's sales channels (Digikala, Basalam, Instagram, WooCommerce) that combines cost-structure awareness with competitive intelligence.

### Primary Actor

**Iranian online seller** — runs a physical-product shop on one or more of: their own WordPress/WooCommerce site, a custom-coded website, or Instagram. Sells in IRR. May also list on Digikala or Basalam but that is not the primary channel this product manages initially.

### Desired Outcome

The seller sets their cost profile once. The system monitors competitor prices continuously and either:
- **Automatically updates** product prices within the seller's defined margin band (automation mode), or
- **Alerts** the seller when their price is no longer competitive or is below their cost floor (manual mode)

The seller never accidentally sells below cost. Their price stays competitive without daily manual effort.

### Core Requirements

#### Cost Profile
- Seller inputs a **business-level cost profile**: staff cost, rent, utilities, other fixed overhead — entered once, updatable at any time
- The system divides fixed overhead across the seller's product catalog (configurable allocation method)
- Per-product **direct cost** (COGS) is entered per SKU
- The system computes a **cost floor** per SKU: `(allocated overhead per unit) + (direct cost per unit)`
- Seller defines a **minimum margin percentage** per product or globally
- **Effective price floor** = `cost floor × (1 + minimum margin)`

#### Competitor Price Intelligence
- The system scrapes **Torob** and **Snapp** (price-comparison aggregators) for matching product listings
- Product matching is by: product name search, barcode/GTIN, or seller-defined mapping
- Scraped prices are refreshed on a configurable schedule (e.g. every 6 hours, daily)
- The system surfaces: cheapest competitor price, median competitor price, and number of competitors found

#### Pricing Recommendation Engine
- Given cost floor + competitor prices, the system outputs a **recommended price**
- Recommended price logic: configurable strategy — e.g. "match cheapest competitor," "undercut by X%," "price at median," "price at cost floor + target margin"
- **Hard constraint:** recommended price is never below the effective price floor — this is non-negotiable regardless of competitor prices
- Seller can set a **maximum price cap** per product

#### Delivery Channels

**WooCommerce Plugin**
- Installs on the seller's WordPress site
- Connects to the SaaS via API key
- Pulls recommended prices and applies them to WooCommerce product prices automatically (automation mode) or surfaces alerts in the WP admin dashboard (alert mode)
- Respects the seller's chosen update frequency

**REST API**
- Authenticated REST API for sellers with custom-coded websites
- Endpoints: get recommended price per SKU, get competitor price data per SKU, push current price, subscribe to price-change webhooks

**Alert System**
- Email and/or SMS notifications when: a product's current price drops below its effective price floor, a competitor undercuts the seller by more than a configured threshold, or the seller's cost profile has not been updated in X days (inflation staleness warning)

**Instagram Bot**
- A bot that can update prices in Instagram posts/captions/stories on the seller's behalf
- Approach to be decided (Instagram API vs automation): this is a known feasibility risk
- Seller defines caption templates with a price placeholder the bot fills
- Alert-only fallback if full automation is not feasible

#### Safety Floor (Non-Negotiable)
- No automated price update — from any channel — may set a price below the effective price floor
- If a competitor's price is below the seller's floor, the system alerts the seller rather than matching it
- The system surfaces a clear warning: "Competitor price is below your cost floor. You cannot match this price profitably."

### Out of Scope (This Brainstorm)
- Direct Digikala or Basalam marketplace listing management (their seller APIs are undetermined)
- Macro inflation signal integration (exchange rate / CPI-driven cost prediction) — noted as a future differentiator
- Multi-seller / agency features
- B2B / wholesale pricing

### Success Criteria
- A seller can onboard (cost profile + first product) in under 10 minutes
- The system catches a price-below-floor condition before any automated update is applied
- Competitor prices are refreshed at least once per day per active product
- WooCommerce plugin auto-updates prices with zero manual intervention once configured
- Sellers in alert mode receive notifications within 1 hour of a relevant price change

### Key Risks
- **Scraping fragility:** Torob/Snapp may block or rate-limit the scraper; product matching accuracy is inherently imperfect
- **Instagram automation:** Instagram's platform restrictions make bot-based caption updates legally and technically risky
- **Cost profile staleness:** If the seller doesn't update their costs, the floor is wrong — the system should surface staleness warnings but cannot enforce accuracy
- **Trust barrier:** Sellers may distrust automated price changes; alert mode is the trust-building on-ramp

### Outstanding Questions
- What is the business model — subscription tiers, per-store pricing, freemium?
- How should overhead be allocated across SKUs — equal split, by revenue weight, manual?
- Should the system support multiple stores per account (seller with both a WooCommerce site and a custom site)?
- What is the minimum viable product — which single channel (WooCommerce or Instagram or API) ships first?
- Are there any existing Iranian repricing tools this product must differentiate against?
