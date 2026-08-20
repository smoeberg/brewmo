-- ==========================================================
-- BrewMo 2.0 / Flavor-to-Recipe & Custom Order Migration
-- Supports Online Flavor Configurator, 36-Bottle Test Batches,
-- Feedback Lifecycle & Full Commercial Production Scaling.
-- ==========================================================

-- 1. Table for Flavor Profiles attached to Recipes
CREATE TABLE IF NOT EXISTS llx_brew_flavor_profile (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    fk_recipe INT NOT NULL,
    bitterness INT NOT NULL DEFAULT 5,   -- Scale 1-10 (Maps to IBU)
    sweetness INT NOT NULL DEFAULT 5,    -- Scale 1-10 (Maps to residual sugars / FG)
    roastiness INT NOT NULL DEFAULT 1,   -- Scale 1-10 (Maps to dark/roasted malts & EBC)
    fruitiness INT NOT NULL DEFAULT 5,   -- Scale 1-10 (Maps to aroma hops & dry-hopping)
    body INT NOT NULL DEFAULT 5,         -- Scale 1-10 (Maps to mash temp & dextrins)
    alcohol_target VARCHAR(16) NOT NULL DEFAULT 'STANDARD', -- LIGHT, STANDARD, STRONG
    target_style VARCHAR(64) NULL,       -- e.g. 'Session IPA', 'Stout', 'Hazy Pale Ale'
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_flavor_recipe (fk_recipe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table for Custom Orders (Test Batch -> Feedback -> Upscale Full Production)
CREATE TABLE IF NOT EXISTS llx_brew_custom_order (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    ref VARCHAR(64) NOT NULL UNIQUE,
    fk_soc INT NOT NULL,                 -- Dolibarr Thirdparty / Customer (llx_societe)
    fk_commande_test INT NULL,          -- Dolibarr Order ID for 36-bottle test batch
    fk_commande_full INT NULL,          -- Dolibarr Order ID for scaled full production
    fk_recipe_test INT NOT NULL,        -- 36-bottle recipe (12-18L)
    fk_recipe_full INT NULL,            -- Scaled recipe (e.g. 500L - 2000L)
    stage VARCHAR(32) NOT NULL DEFAULT 'TEST_36_BOTTLES',
    test_session_id INT NULL,           -- BrewSession for pilot brew
    full_session_id INT NULL,           -- BrewSession for commercial brew
    customer_feedback_rating INT NULL,  -- 1-5 stars
    customer_feedback_notes TEXT NULL,
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_custom_stage (stage),
    INDEX idx_custom_soc (fk_soc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- 3. SQL Views for Business Intelligence & Reporting
-- ==========================================================

-- View: Conversion Funnel from 36-bottle test to full production
CREATE OR REPLACE VIEW llx_view_brew_conversion_funnel AS
SELECT 
    DATE_FORMAT(date_creation, '%Y-%m') AS order_month,
    COUNT(rowid) AS total_test_orders,
    SUM(CASE WHEN stage IN ('TEST_DELIVERED', 'FEEDBACK_RECEIVED', 'FULL_PROD_ORDERED', 'COMPLETED') THEN 1 ELSE 0 END) AS test_delivered_count,
    SUM(CASE WHEN stage IN ('FEEDBACK_RECEIVED', 'FULL_PROD_ORDERED', 'COMPLETED') THEN 1 ELSE 0 END) AS feedback_received_count,
    SUM(CASE WHEN stage IN ('FULL_PROD_ORDERED', 'COMPLETED') THEN 1 ELSE 0 END) AS full_scale_orders,
    ROUND((SUM(CASE WHEN stage IN ('FULL_PROD_ORDERED', 'COMPLETED') THEN 1 ELSE 0 END) / COUNT(rowid)) * 100, 2) AS conversion_rate_percent
FROM llx_brew_custom_order
GROUP BY DATE_FORMAT(date_creation, '%Y-%m');

-- View: Popular Flavor Dimensions and Preferences
CREATE OR REPLACE VIEW llx_view_flavor_preferences AS
SELECT 
    fp.target_style,
    fp.alcohol_target,
    ROUND(AVG(fp.bitterness), 1) AS avg_bitterness,
    ROUND(AVG(fp.sweetness), 1) AS avg_sweetness,
    ROUND(AVG(fp.roastiness), 1) AS avg_roastiness,
    ROUND(AVG(fp.fruitiness), 1) AS avg_fruitiness,
    ROUND(AVG(fp.body), 1) AS avg_body,
    COUNT(fp.rowid) AS total_configured_recipes
FROM llx_brew_flavor_profile fp
GROUP BY fp.target_style, fp.alcohol_target;
