# Product Requirements Document (PRD): Margino (مارژینو)
## Dynamic Pricing & Rival Intelligence Platform for Iranian E-Commerce

---

## 1. Executive Summary & Vision

### 1.1 Executive Summary
**Margino** (مارژینو) is a specialized pricing intelligence and dynamic repricing SaaS platform tailored for Iranian online retailers (especially buy-and-resell categories such as perfume, silver jewelry, cosmetics, and electronics). Due to high currency volatility (USD/IRR), inflation, and changing supplier pricing, store owners routinely sell products below their real replacement costs without realizing it. Concurrently, aggressive competition across aggregators and marketplaces (Torob, Snapp, Digikala, Basalam) results in lost sales when stores are priced above the prevailing market.

Margino solves both sides of this equation:
1. **Never Sell Below Cost:** Calculates an uncompromising, real cost floor (factoring product purchase cost, gateway fees, shipping/packaging, overheads, and minimum target margin).
2. **Never Miss the Market:** Automatically tracks competitor pricing via major aggregators (Torob & Snapp) without forcing merchants to manually collect rival URLs.
3. **Actionable Recommendations:** Delivers verified, safe price recommendations visible directly in the merchant's workflow and safely synced back to their WooCommerce store.

### 1.2 Product Vision & Core Principle
> **"حتی اگر حواس‌ات نباشد، ضرر نمی‌کنی."**  
> *(Even when you're not looking, you won't lose money.)*

- **Hard Rule on Pricing:** Margino never uses speculative AI hallucinated numbers. The cost floor is immutable; competitor prices shape the recommendation above the floor. If market prices plunge below cost, Margino alerts the seller that the product is *"Cannot match profitably"* rather than racing to the bottom.
- **Architectural Separation:**
  - **API Engine:** Central brain managing math, scrapers, queues, auth, and multi-tenant data.
  - **Seller Panel:** Dedicated React SPA delivering rich rival insights, cost configurations, and analytics.
  - **WooCommerce Plugin:** Ultra-light connector ensuring catalog sync and price execution, avoiding heavy UI within WordPress.

---

## 2. Target Audience & Personas

### 2.1 Target Audience
- **Primary Segment:** Iranian independent e-commerce merchants operating on WooCommerce.
- **Key Categories:** Buy-and-resell products with frequent wholesale price revisions (e.g., perfumes, jewelry, cosmetics, mobile accessories, small appliances).
- **Store Volume:** 50 to 2,000 SKUs with 100 to 1,000 monthly orders.

### 2.2 User Personas

| Persona | Role | Pain Points | Desired Outcome |
| :--- | :--- | :--- | :--- |
| **Reza (Store Owner / Buyer)** | Manages wholesale purchasing & pricing | Forgets to update prices when USD jumps; sells inventory at a loss; spends hours searching Torob manually. | "Tell me exactly what my floor price is and alert me immediately if Torob is undercutting me or if I'm losing money." |
| **Sara (Store Operator / Admin)** | Manages WooCommerce catalog & order fulfillment | Tedious work editing 300+ WooCommerce product prices every week; prone to human typo errors. | "One-click approval or safe automatic price updates directly to WooCommerce without breaking inventory." |

---

## 3. System Architecture & Interaction Flow

```
                                    +------------------------------+
                                    |     External Marketplaces    |
                                    |     (Torob, Snapp, etc.)     |
                                    +--------------+---------------+
                                                   |
                                            Scrapers & AI
                                              Matching
                                                   v
+------------------------+             +-----------+----------+             +------------------------+
|  Seller Web Panel      |  REST JSON  |  Margino API Engine  |  REST JSON  | WooCommerce Store      |
|  (Vite + React 18 SPA) | <=========> |  (Laravel + Horizon) | <=========> | (Margino Connector Plg)|
|                        |   Sanctum   |                      |   API Key   |                        |
| - Cost Profiles        |             | - Cost Floor Math    |             | - Catalog Sync         |
| - Rival Comparison UI  |             | - Safe Recommendations|            | - Recommendation Badge |
| - Price Overrides      |             | - Rival Match Engine |             | - Manual Apply         |
| - Alerts & Quotas      |             | - Auto-apply Queues  |             | - Background Sync-back |
+------------------------+             +----------------------+             +------------------------+
```

### 3.1 End-to-End Workflow
1. **Handshake:** Merchant installs the Margino WordPress plugin, enters the API Key generated in the Margino Seller Panel. The plugin validates the key against `POST /v1/connector/validate`.
2. **Catalog Ingestion:** Plugin pushes products in batches (`external_id`, `sku`, `title`, `price`, `brand`, `barcode`) via `POST /v1/connector/products/sync`.
3. **Rival Discovery & Matching:** The API runs background jobs (`DiscoverRivalsJob`) searching aggregator APIs/scrapes based on title, brand, and barcode. AI assists with confidence scoring. High-confidence matches are auto-linked; ambiguous matches are flagged for 1-click review.
4. **Cost Engine Evaluation:** Merchant configures default cost parameters (supplier cost %, packaging overhead, gateway fee %, target margin %) in the Seller Panel.
5. **Recommendation Generation:** The engine evaluates `Cost Floor = Base Cost + Fixed Costs + Gateway + Margin`. It compares the store price with the rival cheapest/median:
   - If `Rival Price > Cost Floor`: Recommends optimal competitive price (e.g. Rival Cheapest - Buffer).
   - If `Rival Price <= Cost Floor`: Clamps recommendation to `Cost Floor` with flag `below_floor: false, cannot_match_profitably: true`.
6. **Execution (Two Modes):**
   - **Mode A (In-WP):** Store manager browses WooCommerce products list, sees the Margino recommendation badge, and clicks "Apply". The plugin updates the price and calls `POST /v1/connector/products/{id}/applied`.
   - **Mode B (Panel / Auto-Apply):** Merchant approves prices in bulk or enables automated sync (Pro Plan). The API registers an `applied_price`. The WooCommerce plugin polls `GET /v1/connector/prices/pending` on its hourly cron, writes the new prices to WooCommerce, and confirms via `POST /v1/connector/prices/ack`.

---

## 4. Subsystem 1: Backend API Engine (`iran-saas/api`)

### 4.1 Data Models & Relationships
- **`Shop`**: Multi-tenant merchant profile, domain, currency (IRT/IRR), plan status, auto-apply settings.
- **`User`**: Merchant authentication credentials linked to a Shop.
- **`ApiKey`**: Hashed secret tokens for connector communication.
- **`Product`**: Synced catalog item (`shop_id`, `external_id`, `name`, `sku`, `regular_price`, `sale_price`, `brand`, `barcode`, `cost_price`, `recommended_price`, `floor_price`, `status`).
- **`ShopCostProfile`**: Global & category cost structures (default margin %, packing cost, shipping cost, gateway %, fixed overhead).
- **`ProductRivalMatch`**: Mapping between a shop product and an aggregator target URL/ID (`status`: `pending_review`, `confirmed`, `rejected`, `confidence_score`).
- **`RivalSnapshot`**: Periodic price captures from rivals (`cheapest_price`, `median_price`, `rival_count`, `raw_payload`, `scraped_at`).
- **`AppliedPrice`**: Audit trail of every price change (`previous_price`, `new_price`, `source`: `manual_wp` | `panel_single` | `panel_batch` | `auto_apply`, `status`: `pending_sync` | `applied`).
- **`Plan` & `ShopLicense`**: Quotas and tiers (Starter, Pro, Business) governing SKU limits, refresh frequencies, and auto-apply privileges.
- **`ShopAlertDispatch`**: Logged alerts (below cost, rival price dropped, stale match) sent via SMS (Kavenegar/Faraz) or Webhook.

### 4.2 Cost Engine Calculation Logic
```
Base Cost = Product Cost Price (or synced baseline)
Variable Deductions = (Selling Price * Gateway Fee %) + (Selling Price * Tax %)
Fixed Deductions = Packaging Cost + Handling Fee + Fixed Overhead Allocation
Net Revenue = Selling Price - Variable Deductions - Fixed Deductions

Floor Price Formula:
Floor Price = (Base Cost + Fixed Deductions) / (1 - Gateway% - Tax% - MinimumMargin%)
```
- **Rule 1 (Hard Stop):** No recommended price can ever be lower than `Floor Price`.
- **Rule 2 (Rival Positioning):** When rivals exist:
  $$\text{Recommended Price} = \max(\text{Floor Price}, \min(\text{Rival Cheapest} - \text{Delta}, \text{Store Current Price}))$$
- **Rule 3 (Stale Data Protection):** If rival data is older than 48 hours, mark as `stale: true` and rely exclusively on cost profile + inflation indices.

### 4.3 API Endpoints Specification

#### A. Connector API (`/v1/connector/*` — Header: `Authorization: Bearer <api_key>`)
1. `POST /v1/connector/validate`: Verifies API key, returns shop identity and plan level.
2. `POST /v1/connector/products/sync`: Bulk upserts products from WooCommerce.
3. `GET /v1/connector/products/{external_id}/recommendation`: Returns recommended price, floor price, and status flags.
4. `POST /v1/connector/products/{external_id}/applied`: Acknowledges manual price updates triggered inside WP.
5. `GET /v1/connector/prices/pending`: Provides pending price updates scheduled from the Panel.
6. `POST /v1/connector/prices/ack`: Confirms successful update of pending prices in WooCommerce.

#### B. Seller Panel API (`/api/seller/*` — Header: `Authorization: Bearer <sanctum_token>`)
1. **Authentication:** `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `GET /me`.
2. **Dashboard Overview:** `GET /pricing/overview` (KPIs: products below floor, products with rival advantage, loss prevented).
3. **Products & Rivals:**
   - `GET /pricing/products`: Paginated catalog with rival summary, filters (below cost, rival cheaper, unmatched).
   - `GET /pricing/products/{product}`: Detailed breakdown of rival listings, price history, and math breakdown.
   - `POST /pricing/products/{product}/apply-price`: Immediate price recommendation approval.
   - `POST /pricing/products/{product}/refresh-rivals`: On-demand rival rescrape request.
   - `POST /pricing/products/{product}/rivals/{match}/confirm`: Confirm an AI-matched competitor.
   - `POST /pricing/products/{product}/unlink-rival`: Remove an incorrect rival match.
4. **Batch Operations:**
   - `POST /pricing/products/batch-apply-price`: Bulk apply recommendations to selected or filtered SKUs.
   - `POST /pricing/products/batch-refresh`: Bulk refresh competitor data.
5. **Cost Profiles:** `GET /pricing/costs`, `PUT /pricing/costs`.
6. **Alerts & Notifications:** `GET /pricing/alerts`, `PUT /pricing/alerts`.
7. **Auto-Apply Engine (Pro):** `GET /pricing/auto-apply`, `PUT /pricing/auto-apply`, `POST /pricing/auto-apply/run-now`, `POST /pricing/products/{product}/toggle-auto-apply`.
8. **Subscription & Quotas:** `GET /pricing/plan`, `POST /pricing/plan/upgrade`.
9. **API Keys:** `GET /pricing/api-keys`, `POST /pricing/api-keys`, `POST /pricing/api-keys/{id}/revoke`.

---

## 5. Subsystem 2: Seller Panel (`pricing/panel`)

### 5.1 Technology & UX Directives
- **Framework:** Vite + React 18/19 SPA.
- **Styling & UI:** Tailwind CSS, Radix primitives / shadcn/ui, Lucide icons.
- **Language & Direction:** Native Persian (Farsi) with strict RTL (`dir="rtl"`), typography set to **Vazirmatn**.
- **Palette (Brand Tokens):**
  - Primary / Brand Accent: `#237227` (Forest Green)
  - Secondary: `#519A66`
  - Warning / Attention: `#FFAA00`
  - Highlight Accent: `#FFD786`

### 5.2 Key Views & Features

#### 1. Overview Dashboard (`/`)
- **Metric Cards:**
  - *محصولات زیر کف قیمت (Products Below Cost Floor)*: Urgent red counter indicating active money-losing sales.
  - *فرصت‌های افزایش قیمت (Price Increase Opportunities)*: SKUs priced below the market where margins can be recovered.
  - *محصولات گران‌تر از رقبا (Overpriced SKUs)*: SKUs at risk of losing conversions on Torob.
  - *میزان ضرر پیشگیری‌شده (Estimated Loss Prevented)*: Toman estimation of savings generated by Margino.
- **Onboarding Guide (`OnboardingGuide.tsx`):** Progress stepper (Connect WooCommerce -> Set Cost Profile -> Review First Rival Match -> Apply First Price).

#### 2. Products & Rival Intelligence View (`/products`)
- **Interactive Data Table:**
  - Thumbnail, Product Title, SKU.
  - Current Store Price vs. Recommended Safe Price.
  - Cost Floor indicator pill (`کف قیمت`).
  - **Rival Matrix Column (`RivalCompare.tsx`):**
    - Lowest competitor price + aggregator tag (Torob / Snapp).
    - Store difference badge (e.g. `+12% گران‌تر` or `-5% ارزان‌تر`).
    - Rival count indicator (e.g. `۱۲ فروشنده`).
  - Action items: One-click "Apply Price", "Edit Cost", "Inspect Rivals".
- **Product Rival Detail Sheet (`ProductRivalDetailSheet.tsx`):**
  - Slide-over drawer detailing all scraped listings for that product.
  - Confidence percentage for AI-matched items.
  - Direct links to the competitor storefronts on Torob.
  - Buttons to "Confirm Match" or "Unlink Competitor".
- **Bulk Action Toolbar (`BulkActionToolbar.tsx`):**
  - Multi-select actions: "اعمال قیمت پیشنهادی برای موارد انتخابی" (Batch Apply), "بروزرسانی زنده قیمت رقبا" (Batch Refresh).
  - Safety validation counter before bulk commit.
- **Price Override Popover (`PriceOverridePopover.tsx`):**
  - Allows merchants to set an ad-hoc custom price with real-time feedback showing whether the custom price violates their floor limit.

#### 3. Cost Profile Configuration & Iranian Niche Templates (`/costs`)
- **1-Click Iranian E-Commerce Cost Templates (قالب‌های هزینه آماده):**
  - **Perfume (عطر و ادکلن):** High fragile packing allowance (~35,000 Toman box/bubble wrap), 1% gateway, 3% transit breakage reserve, 25% target margin.
  - **Silver Accessories (نقره و بدلیجات):** Protective pouch/gift box (~20,000 Toman), 1% gateway, 5% raw metal fluctuation buffer, 30% target margin.
  - **Cosmetics (آرایشی و بهداشتی):** Standard packaging (~18,000 Toman), 1% gateway, 20% target margin.
  - **Tech Gadgets (لوازم جانبی دیجیتال):** Tested packaging (~25,000 Toman), 1% gateway, 2% warranty reserve, 12% target margin.
- **Custom Overheads Override:**
  - Default supplier buy-margin deduction.
  - Packaging & box cost (Toman).
  - Payment gateway commission percentage (e.g. 1% Shaparak / Zarinpal).
  - Return / damage contingency reserve (%).
  - Target net profit margin (%).

#### 4. Auto-Apply Settings & Safety Circuit Breakers (Pro Plan)
- **Hard Safety Circuit Breaker (کلید قطع اضطراری):**
  - Strict algorithmic guardrail preventing any automated price update exceeding **±10% in a 24-hour window** without explicit merchant confirmation.
  - Max price increase / decrease caps per execution.
  - Category and tag exclusion toggles.
  - Instant Master "Kill Switch": One-tap toggle to immediately freeze all automated price changes across the entire store.

#### 5. Alerts & API Key Management (`/alerts`, `/api-keys`)
- SMS alert triggers for: "Product selling below replacement cost", "Competitor undercut current price by > 10%".
- Generation of secret keys for the WordPress connector.

---

## 6. Subsystem 3: WordPress Connector Plugin (`plugin/pricing`)

### 6.1 Design Philosophy: "Connector Only" & Zero-Load Guarantee
- **Zero-Load on WordPress:** 99.9% of computations, scraping, and AI matching run entirely on Margino's cloud engine.
- **LiteSpeed Cache & WP Rocket Certified:** The connector is an ultra-light REST client (< 1MB). It creates zero background database locks and zero impact on Time-To-First-Byte (TTFB).
- **Cache Purging Integration:** Automatically hooks into `litespeed_purge_post`, `rocket_clean_post`, and WooCommerce transients on price updates to ensure customer-facing prices refresh instantaneously.

### 6.2 Key Modules & Capabilities
1. **Connection Manager (`class-connection.php`):**
   - Admin settings tab in WooCommerce.
   - Enter API Key -> Ajax validation to Margino API.
   - Displays real-time connection status, connected shop name, and subscription tier.
2. **Product Sync Engine (`class-product-sync.php`):**
   - Automated initial bulk sync with chunked batching (50 products/batch) to avoid PHP memory exhaustion or timeout issues.
   - Hooked to WooCommerce lifecycle events: `woocommerce_new_product`, `woocommerce_update_product` for continuous delta sync.
   - Extracts standard and extended fields: Regular Price, Sale Price, Brand taxonomy, Barcode/GTIN custom fields (e.g., Persian WooCommerce Barcode plugins).
3. **WooCommerce Admin UI Extensions (`class-admin-pages.php` & `class-recommendations.php`):**
   - **Product List Table Column:** Injects a "مارژینو (Margino)" column in the standard `wp-admin/edit.php?post_type=product` screen showing:
     - Recommended Price badge.
     - Warning icon if current price is below floor.
     - Quick "اعمال قیمت" (Apply) button.
   - **Product Edit Screen Metabox:** Injects pricing guidance metabox inside single product edit screen showing:
     - Current Store Price, Safe Floor Price, Recommended Price.
     - One-click button to replace regular price with recommended price.
4. **Sync-Back & Price Application (`class-price-apply.php` & `class-price-sync-back.php`):**
   - Handles manual apply requests with nonce validation.
   - **Background Polling:** Runs via Action Scheduler or WP-Cron every 30-60 minutes:
     - Queries `GET /v1/connector/prices/pending`.
     - Atomically updates `_regular_price` and `_price` post meta.
     - Clears WooCommerce transients & caches.
     - Reports back batch execution via `POST /v1/connector/prices/ack`.

---

## 7. Security, Reliability & Quotas

### 7.1 Security Architecture
- **Connector Authentication:** Cryptographically generated random 64-character tokens, stored hashed (SHA-256) in the database.
- **Seller Panel Auth:** Laravel Sanctum stateful/token authentication with rate limiting on login attempts (`throttle:5,1`).
- **Data Isolation:** All database queries are strictly scoped through the authenticated `Shop` tenant ID.
- **Rollback Safety:** Price change logs (`AppliedPrice`) store the previous price, enabling 1-click restore in case of accidental bulk changes.

### 7.2 Quotas & Subscription Tiers

| Tier | SKU Limit | Rival Refresh Frequency | Auto-Apply Feature | Channels | Target Persona |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Starter (پایه)** | Up to 100 SKUs | Once daily (24h) | Manual only | 1 WooCommerce Store | Small shop, boutique sellers |
| **Pro (حرفه‌ای)** *(Hero Plan)* | Up to 1,000 SKUs | Every 6 hours | Included (with safety limits) | 1 Store + Priority Alerts | High-volume cosmetics / perfume |
| **Business (تجاری)** | Up to 5,000 SKUs | Hourly / On-demand | Full automation + API | Multiple stores | Multi-branch online retailers |

---

## 8. Success Metrics (KPIs)

1. **Activation Rate:** % of connected stores that configure a Cost Profile within 48 hours of installation (> 60%).
2. **Product Engagement:** % of active shops that view the Rival Intelligence sheet weekly (> 75%).
3. **Price Actions Taken:** Total count of price updates executed through Margino (manual or auto-apply).
4. **Churn Rate:** Monthly SaaS subscription churn kept below 4%.
5. **Loss Prevention Value:** Cumulative Toman difference saved by preventing sales below cost floors.
