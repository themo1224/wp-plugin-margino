-- Seed WooCommerce products for Pricing plugin smoke tests (WP Phase 1.5).
-- Target DB example: margino (http://localhost:8880/wordpress/)
--
-- Prerequisites: WordPress + WooCommerce installed. Prefix must be `wp_`
-- (search-replace wp_ if different).
--
-- Run this whole file in HeidiSQL / phpMyAdmin / mysql CLI.
-- No PROCEDURE / DELIMITER (those break in HeidiSQL).
--
-- Creates:
--   3 published simple products with prices  -> sync accept
--   1 published product without price        -> local skip
--   1 draft product with price               -> not synced

SET NAMES utf8mb4;

-- Ensure "simple" product_type term
INSERT INTO wp_terms (name, slug, term_group)
SELECT 'simple', 'simple', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'simple' LIMIT 1);

INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT t.term_id, 'product_type', '', 0, 0
FROM wp_terms t
WHERE t.slug = 'simple'
  AND NOT EXISTS (
    SELECT 1
    FROM wp_term_taxonomy tt
    WHERE tt.term_id = t.term_id
      AND tt.taxonomy = 'product_type'
  )
LIMIT 1;

-- =============================================================================
-- 1) چای ایرانی — publish + price
-- =============================================================================
INSERT INTO wp_posts (
  post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
  post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
  menu_order, post_type, post_mime_type, comment_count
)
SELECT
  1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 'چای ایرانی (تست Pricing)', '',
  'publish', 'closed', 'closed', '', 'pricing-seed-tea', '', '',
  UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 0, '',
  0, 'product', '', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM wp_posts WHERE post_name = 'pricing-seed-tea' AND post_type = 'product'
);

SET @pid := (
  SELECT ID FROM wp_posts WHERE post_name = 'pricing-seed-tea' AND post_type = 'product' LIMIT 1
);

UPDATE wp_posts
SET guid = CONCAT('http://localhost:8880/wordpress/?post_type=product&p=', @pid)
WHERE ID = @pid;

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT @pid, m.meta_key, m.meta_value
FROM (
  SELECT '_sku' AS meta_key, 'PRICING-TEA-001' AS meta_value UNION ALL
  SELECT '_regular_price', '1500000' UNION ALL
  SELECT '_price', '1500000' UNION ALL
  SELECT '_sale_price', '' UNION ALL
  SELECT '_manage_stock', 'no' UNION ALL
  SELECT '_stock_status', 'instock' UNION ALL
  SELECT '_tax_status', 'taxable' UNION ALL
  SELECT '_tax_class', '' UNION ALL
  SELECT 'total_sales', '0' UNION ALL
  SELECT '_virtual', 'no' UNION ALL
  SELECT '_downloadable', 'no' UNION ALL
  SELECT '_backorders', 'no' UNION ALL
  SELECT '_sold_individually', 'no' UNION ALL
  SELECT '_product_version', '9.0.0'
) AS m
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_postmeta pm
    WHERE pm.post_id = @pid AND pm.meta_key = m.meta_key
  );

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT @pid, tt.term_taxonomy_id, 0
FROM wp_term_taxonomy tt
INNER JOIN wp_terms t ON t.term_id = tt.term_id
WHERE @pid IS NOT NULL
  AND t.slug = 'simple'
  AND tt.taxonomy = 'product_type'
  AND NOT EXISTS (
    SELECT 1 FROM wp_term_relationships tr
    WHERE tr.object_id = @pid AND tr.term_taxonomy_id = tt.term_taxonomy_id
  )
LIMIT 1;

INSERT INTO wp_wc_product_meta_lookup (
  product_id, sku, `virtual`, downloadable, min_price, max_price,
  onsale, stock_quantity, stock_status, rating_count, average_rating,
  total_sales, tax_status, tax_class
)
SELECT
  @pid, 'PRICING-TEA-001', 0, 0, '1500000', '1500000',
  0, NULL, 'instock', 0, 0,
  0, 'taxable', ''
FROM DUAL
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_wc_product_meta_lookup l WHERE l.product_id = @pid
  );

-- =============================================================================
-- 2) زعفران نگین — publish + price
-- =============================================================================
INSERT INTO wp_posts (
  post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
  post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
  menu_order, post_type, post_mime_type, comment_count
)
SELECT
  1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 'زعفران نگین (تست Pricing)', '',
  'publish', 'closed', 'closed', '', 'pricing-seed-saffron', '', '',
  UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 0, '',
  0, 'product', '', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM wp_posts WHERE post_name = 'pricing-seed-saffron' AND post_type = 'product'
);

SET @pid := (
  SELECT ID FROM wp_posts WHERE post_name = 'pricing-seed-saffron' AND post_type = 'product' LIMIT 1
);

UPDATE wp_posts
SET guid = CONCAT('http://localhost:8880/wordpress/?post_type=product&p=', @pid)
WHERE ID = @pid;

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT @pid, m.meta_key, m.meta_value
FROM (
  SELECT '_sku' AS meta_key, 'PRICING-SAFFRON-001' AS meta_value UNION ALL
  SELECT '_regular_price', '4500000' UNION ALL
  SELECT '_price', '4500000' UNION ALL
  SELECT '_sale_price', '' UNION ALL
  SELECT '_manage_stock', 'no' UNION ALL
  SELECT '_stock_status', 'instock' UNION ALL
  SELECT '_tax_status', 'taxable' UNION ALL
  SELECT '_tax_class', '' UNION ALL
  SELECT 'total_sales', '0' UNION ALL
  SELECT '_virtual', 'no' UNION ALL
  SELECT '_downloadable', 'no' UNION ALL
  SELECT '_backorders', 'no' UNION ALL
  SELECT '_sold_individually', 'no' UNION ALL
  SELECT '_product_version', '9.0.0'
) AS m
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_postmeta pm
    WHERE pm.post_id = @pid AND pm.meta_key = m.meta_key
  );

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT @pid, tt.term_taxonomy_id, 0
FROM wp_term_taxonomy tt
INNER JOIN wp_terms t ON t.term_id = tt.term_id
WHERE @pid IS NOT NULL
  AND t.slug = 'simple'
  AND tt.taxonomy = 'product_type'
  AND NOT EXISTS (
    SELECT 1 FROM wp_term_relationships tr
    WHERE tr.object_id = @pid AND tr.term_taxonomy_id = tt.term_taxonomy_id
  )
LIMIT 1;

INSERT INTO wp_wc_product_meta_lookup (
  product_id, sku, `virtual`, downloadable, min_price, max_price,
  onsale, stock_quantity, stock_status, rating_count, average_rating,
  total_sales, tax_status, tax_class
)
SELECT
  @pid, 'PRICING-SAFFRON-001', 0, 0, '4500000', '4500000',
  0, NULL, 'instock', 0, 0,
  0, 'taxable', ''
FROM DUAL
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_wc_product_meta_lookup l WHERE l.product_id = @pid
  );

-- =============================================================================
-- 3) پسته اکبری — publish + price
-- =============================================================================
INSERT INTO wp_posts (
  post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
  post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
  menu_order, post_type, post_mime_type, comment_count
)
SELECT
  1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 'پسته اکبری (تست Pricing)', '',
  'publish', 'closed', 'closed', '', 'pricing-seed-pistachio', '', '',
  UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 0, '',
  0, 'product', '', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM wp_posts WHERE post_name = 'pricing-seed-pistachio' AND post_type = 'product'
);

SET @pid := (
  SELECT ID FROM wp_posts WHERE post_name = 'pricing-seed-pistachio' AND post_type = 'product' LIMIT 1
);

UPDATE wp_posts
SET guid = CONCAT('http://localhost:8880/wordpress/?post_type=product&p=', @pid)
WHERE ID = @pid;

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT @pid, m.meta_key, m.meta_value
FROM (
  SELECT '_sku' AS meta_key, 'PRICING-PISTACHIO-001' AS meta_value UNION ALL
  SELECT '_regular_price', '2800000' UNION ALL
  SELECT '_price', '2800000' UNION ALL
  SELECT '_sale_price', '' UNION ALL
  SELECT '_manage_stock', 'no' UNION ALL
  SELECT '_stock_status', 'instock' UNION ALL
  SELECT '_tax_status', 'taxable' UNION ALL
  SELECT '_tax_class', '' UNION ALL
  SELECT 'total_sales', '0' UNION ALL
  SELECT '_virtual', 'no' UNION ALL
  SELECT '_downloadable', 'no' UNION ALL
  SELECT '_backorders', 'no' UNION ALL
  SELECT '_sold_individually', 'no' UNION ALL
  SELECT '_product_version', '9.0.0'
) AS m
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_postmeta pm
    WHERE pm.post_id = @pid AND pm.meta_key = m.meta_key
  );

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT @pid, tt.term_taxonomy_id, 0
FROM wp_term_taxonomy tt
INNER JOIN wp_terms t ON t.term_id = tt.term_id
WHERE @pid IS NOT NULL
  AND t.slug = 'simple'
  AND tt.taxonomy = 'product_type'
  AND NOT EXISTS (
    SELECT 1 FROM wp_term_relationships tr
    WHERE tr.object_id = @pid AND tr.term_taxonomy_id = tt.term_taxonomy_id
  )
LIMIT 1;

INSERT INTO wp_wc_product_meta_lookup (
  product_id, sku, `virtual`, downloadable, min_price, max_price,
  onsale, stock_quantity, stock_status, rating_count, average_rating,
  total_sales, tax_status, tax_class
)
SELECT
  @pid, 'PRICING-PISTACHIO-001', 0, 0, '2800000', '2800000',
  0, NULL, 'instock', 0, 0,
  0, 'taxable', ''
FROM DUAL
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_wc_product_meta_lookup l WHERE l.product_id = @pid
  );

-- =============================================================================
-- 4) بدون قیمت — publish, empty price (local skip)
-- =============================================================================
INSERT INTO wp_posts (
  post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
  post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
  menu_order, post_type, post_mime_type, comment_count
)
SELECT
  1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 'محصول بدون قیمت (رد محلی)', '',
  'publish', 'closed', 'closed', '', 'pricing-seed-no-price', '', '',
  UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 0, '',
  0, 'product', '', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM wp_posts WHERE post_name = 'pricing-seed-no-price' AND post_type = 'product'
);

SET @pid := (
  SELECT ID FROM wp_posts WHERE post_name = 'pricing-seed-no-price' AND post_type = 'product' LIMIT 1
);

UPDATE wp_posts
SET guid = CONCAT('http://localhost:8880/wordpress/?post_type=product&p=', @pid)
WHERE ID = @pid;

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT @pid, m.meta_key, m.meta_value
FROM (
  SELECT '_sku' AS meta_key, 'PRICING-NOPRICE-001' AS meta_value UNION ALL
  SELECT '_regular_price', '' UNION ALL
  SELECT '_price', '' UNION ALL
  SELECT '_sale_price', '' UNION ALL
  SELECT '_manage_stock', 'no' UNION ALL
  SELECT '_stock_status', 'instock' UNION ALL
  SELECT '_tax_status', 'taxable' UNION ALL
  SELECT '_tax_class', '' UNION ALL
  SELECT 'total_sales', '0' UNION ALL
  SELECT '_virtual', 'no' UNION ALL
  SELECT '_downloadable', 'no' UNION ALL
  SELECT '_backorders', 'no' UNION ALL
  SELECT '_sold_individually', 'no' UNION ALL
  SELECT '_product_version', '9.0.0'
) AS m
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_postmeta pm
    WHERE pm.post_id = @pid AND pm.meta_key = m.meta_key
  );

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT @pid, tt.term_taxonomy_id, 0
FROM wp_term_taxonomy tt
INNER JOIN wp_terms t ON t.term_id = tt.term_id
WHERE @pid IS NOT NULL
  AND t.slug = 'simple'
  AND tt.taxonomy = 'product_type'
  AND NOT EXISTS (
    SELECT 1 FROM wp_term_relationships tr
    WHERE tr.object_id = @pid AND tr.term_taxonomy_id = tt.term_taxonomy_id
  )
LIMIT 1;

INSERT INTO wp_wc_product_meta_lookup (
  product_id, sku, `virtual`, downloadable, min_price, max_price,
  onsale, stock_quantity, stock_status, rating_count, average_rating,
  total_sales, tax_status, tax_class
)
SELECT
  @pid, 'PRICING-NOPRICE-001', 0, 0, NULL, NULL,
  0, NULL, 'instock', 0, 0,
  0, 'taxable', ''
FROM DUAL
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_wc_product_meta_lookup l WHERE l.product_id = @pid
  );

-- =============================================================================
-- 5) پیش‌نویس — draft + price (must NOT sync)
-- =============================================================================
INSERT INTO wp_posts (
  post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
  post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
  menu_order, post_type, post_mime_type, comment_count
)
SELECT
  1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 'محصول پیش‌نویس (نباید همگام شود)', '',
  'draft', 'closed', 'closed', '', 'pricing-seed-draft', '', '',
  UTC_TIMESTAMP(), UTC_TIMESTAMP(), '', 0, '',
  0, 'product', '', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM wp_posts WHERE post_name = 'pricing-seed-draft' AND post_type = 'product'
);

SET @pid := (
  SELECT ID FROM wp_posts WHERE post_name = 'pricing-seed-draft' AND post_type = 'product' LIMIT 1
);

UPDATE wp_posts
SET guid = CONCAT('http://localhost:8880/wordpress/?post_type=product&p=', @pid)
WHERE ID = @pid;

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT @pid, m.meta_key, m.meta_value
FROM (
  SELECT '_sku' AS meta_key, 'PRICING-DRAFT-001' AS meta_value UNION ALL
  SELECT '_regular_price', '999000' UNION ALL
  SELECT '_price', '999000' UNION ALL
  SELECT '_sale_price', '' UNION ALL
  SELECT '_manage_stock', 'no' UNION ALL
  SELECT '_stock_status', 'instock' UNION ALL
  SELECT '_tax_status', 'taxable' UNION ALL
  SELECT '_tax_class', '' UNION ALL
  SELECT 'total_sales', '0' UNION ALL
  SELECT '_virtual', 'no' UNION ALL
  SELECT '_downloadable', 'no' UNION ALL
  SELECT '_backorders', 'no' UNION ALL
  SELECT '_sold_individually', 'no' UNION ALL
  SELECT '_product_version', '9.0.0'
) AS m
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_postmeta pm
    WHERE pm.post_id = @pid AND pm.meta_key = m.meta_key
  );

INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT @pid, tt.term_taxonomy_id, 0
FROM wp_term_taxonomy tt
INNER JOIN wp_terms t ON t.term_id = tt.term_id
WHERE @pid IS NOT NULL
  AND t.slug = 'simple'
  AND tt.taxonomy = 'product_type'
  AND NOT EXISTS (
    SELECT 1 FROM wp_term_relationships tr
    WHERE tr.object_id = @pid AND tr.term_taxonomy_id = tt.term_taxonomy_id
  )
LIMIT 1;

INSERT INTO wp_wc_product_meta_lookup (
  product_id, sku, `virtual`, downloadable, min_price, max_price,
  onsale, stock_quantity, stock_status, rating_count, average_rating,
  total_sales, tax_status, tax_class
)
SELECT
  @pid, 'PRICING-DRAFT-001', 0, 0, '999000', '999000',
  0, NULL, 'instock', 0, 0,
  0, 'taxable', ''
FROM DUAL
WHERE @pid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM wp_wc_product_meta_lookup l WHERE l.product_id = @pid
  );

-- Refresh simple term count
UPDATE wp_term_taxonomy tt
INNER JOIN wp_terms t ON t.term_id = tt.term_id
SET tt.count = (
  SELECT COUNT(*) FROM wp_term_relationships tr WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
)
WHERE t.slug = 'simple' AND tt.taxonomy = 'product_type';

-- Verify
SELECT p.ID, p.post_title, p.post_status, p.post_name,
       MAX(CASE WHEN pm.meta_key = '_sku' THEN pm.meta_value END) AS sku,
       MAX(CASE WHEN pm.meta_key = '_price' THEN pm.meta_value END) AS price
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID AND pm.meta_key IN ('_sku', '_price')
WHERE p.post_type = 'product'
  AND p.post_name LIKE 'pricing-seed-%'
GROUP BY p.ID, p.post_title, p.post_status, p.post_name
ORDER BY p.ID;
