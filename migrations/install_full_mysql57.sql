-- =====================================================================
-- BREWMO - Full Install (MySQL 5.7+ safe, idempotent)
-- Consolidates: base brew tables, UI extensions, fk_product links,
-- equipment/vessels/packaging lines + calendar/booking, packaging,
-- brewsession fk_bom/fk_mo patch + realization tables.
-- =====================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();
SET FOREIGN_KEY_CHECKS=0;

-- -------------------------------------------------
-- CORE: Recipes and sub tables (CREATE IF NOT EXISTS)
-- -------------------------------------------------

-- Recipes
CREATE TABLE IF NOT EXISTS llx_brew_recipes (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(128) NULL,
  label VARCHAR(255) NOT NULL,
  batch_volume_l DOUBLE NULL,
  boil_time_min DOUBLE NULL,
  efficiency_pct DOUBLE NULL,
  og_sg DOUBLE NULL,
  fg_sg DOUBLE NULL,
  abv DOUBLE NULL,
  ibu DOUBLE NULL,
  color_ebc DOUBLE NULL,
  note TEXT NULL,
  fk_equipment INT NULL,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  INDEX idx_brew_recipes_ref (ref),
  INDEX idx_brew_recipes_entity (entity),
  INDEX idx_brew_recipes_fk_equipment (fk_equipment)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Malt lines
CREATE TABLE IF NOT EXISTS llx_brew_recipe_malts (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  amount_kg DOUBLE DEFAULT 0,
  ebc DOUBLE NULL,
  yield_pct DOUBLE NULL,
  potential_ppg DOUBLE NULL,
  fk_product INT NULL,
  product_ref VARCHAR(128) NULL,
  INDEX idx_brew_malt_recipe (fk_recipe),
  INDEX idx_brew_malt_fk_product (fk_product),
  INDEX idx_brew_malt_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Hop lines
CREATE TABLE IF NOT EXISTS llx_brew_recipe_hops (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  alpha_acid DOUBLE DEFAULT 0,
  grams DOUBLE DEFAULT 0,
  boil_time_min DOUBLE DEFAULT 0,
  use_stage VARCHAR(32) NULL,
  whirlpool_temp_c DOUBLE NULL,
  dryhop_days DOUBLE NULL,
  fk_product INT NULL,
  product_ref VARCHAR(128) NULL,
  INDEX idx_brew_hop_recipe (fk_recipe),
  INDEX idx_brew_hop_fk_product (fk_product),
  INDEX idx_brew_hop_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Yeast lines
CREATE TABLE IF NOT EXISTS llx_brew_recipe_yeasts (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  grams DOUBLE DEFAULT 0,
  attenuation DOUBLE NULL,
  fk_product INT NULL,
  product_ref VARCHAR(128) NULL,
  INDEX idx_brew_yeast_recipe (fk_recipe),
  INDEX idx_brew_yeast_fk_product (fk_product),
  INDEX idx_brew_yeast_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Extras
CREATE TABLE IF NOT EXISTS llx_brew_recipe_extras (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  grams DOUBLE DEFAULT 0,
  when_stage VARCHAR(128) NULL,
  fk_product INT NULL,
  product_ref VARCHAR(128) NULL,
  INDEX idx_brew_extra_recipe (fk_recipe),
  INDEX idx_brew_extra_fk_product (fk_product),
  INDEX idx_brew_extra_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Mash steps
CREATE TABLE IF NOT EXISTS llx_brew_mash_steps (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  temp_c DOUBLE NULL,
  time_min DOUBLE NULL,
  INDEX idx_brew_mash_recipe (fk_recipe),
  INDEX idx_brew_mash_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Fermentation steps
CREATE TABLE IF NOT EXISTS llx_brew_fermentation_steps (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  temp_c DOUBLE NULL,
  time_days DOUBLE NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_ferm_recipe (fk_recipe),
  INDEX idx_brew_ferm_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Carbonation steps
CREATE TABLE IF NOT EXISTS llx_brew_carbonation_steps (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  temp_c DOUBLE NULL,
  time_days DOUBLE NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_carb_recipe (fk_recipe),
  INDEX idx_brew_carb_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Pasteurization steps
CREATE TABLE IF NOT EXISTS llx_brew_pasteurization_steps (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NOT NULL,
  name VARCHAR(255) NULL,
  days_after_bottling DOUBLE NULL,
  temp_c DOUBLE NULL,
  time_min DOUBLE NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_pasteur_recipe (fk_recipe),
  INDEX idx_brew_pasteur_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------
-- BREW SESSION (minimal skeleton + links to BOM/MO)
-- -------------------------------------------------
CREATE TABLE IF NOT EXISTS llx_brew_brewsession (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_recipe INT NULL,
  planned_volume_l DOUBLE NULL,
  actual_volume_l DOUBLE NULL,
  status VARCHAR(32) DEFAULT 'draft',
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  fk_bom INT NULL,
  fk_mo INT NULL,
  fk_vessel INT NULL,
  note VARCHAR(255) NULL,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  INDEX idx_brewsession_recipe (fk_recipe),
  INDEX idx_brewsession_entity (entity),
  INDEX idx_brewsession_fk_bom (fk_bom),
  INDEX idx_brewsession_fk_mo (fk_mo),
  INDEX idx_brewsession_fk_vessel (fk_vessel),
  INDEX idx_brewsession_status_time (status, started_at, finished_at)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------
-- PACKAGING (types + instances)
-- -------------------------------------------------
CREATE TABLE IF NOT EXISTS llx_brew_packaging_type (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(64),
  label VARCHAR(255),
  volume_l DOUBLE DEFAULT 0,
  enabled SMALLINT DEFAULT 1,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  UNIQUE KEY uk_brew_packaging_type_ref (entity, ref),
  INDEX idx_brew_packaging_type_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_brew_packaging (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_brewsession INT NOT NULL,
  fk_packaging_type INT NOT NULL,
  units DOUBLE DEFAULT 0,
  total_volume_l DOUBLE DEFAULT 0,
  status SMALLINT DEFAULT 0,
  date_packaged DATETIME NULL,
  fk_product INT NULL,
  note VARCHAR(255) NULL,
  fk_packaging_line INT NULL,
  expected_minutes DOUBLE NULL,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  INDEX idx_brew_packaging_fk_brewsession (fk_brewsession),
  INDEX idx_brew_packaging_fk_type (fk_packaging_type),
  INDEX idx_brew_packaging_fk_line (fk_packaging_line),
  INDEX idx_brew_packaging_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------
-- EQUIPMENT / VESSELS / LINES / CALENDAR (consolidated)
-- -------------------------------------------------
CREATE TABLE IF NOT EXISTS llx_brew_equipment (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(64) NOT NULL,
  label VARCHAR(255) NOT NULL,
  efficiency_pct DOUBLE NULL,
  boiloff_l_h DOUBLE NULL,
  trub_loss_l DOUBLE NULL,
  lauter_deadspace_l DOUBLE NULL,
  mash_tun_l DOUBLE NULL,
  kettle_l DOUBLE NULL,
  note VARCHAR(255) NULL,
  enabled SMALLINT DEFAULT 1,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  UNIQUE KEY uk_brew_equipment_ref (entity, ref),
  INDEX idx_brew_equipment_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Ensure fk_equipment present on recipes (idempotent add)
SELECT IF (
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_recipes' AND COLUMN_NAME='fk_equipment'
  ),
  'SELECT 1',
  'ALTER TABLE llx_brew_recipes ADD COLUMN fk_equipment INT NULL'
) INTO @stmt; PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS llx_brew_vessels (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(64) NOT NULL,
  label VARCHAR(255) NOT NULL,
  capacity_l DOUBLE NULL,
  type VARCHAR(32) DEFAULT 'fermenter',
  location VARCHAR(128) NULL,
  status VARCHAR(32) DEFAULT 'available',
  note VARCHAR(255) NULL,
  enabled SMALLINT DEFAULT 1,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  UNIQUE KEY uk_brew_vessels_ref (entity, ref),
  INDEX idx_brew_vessels_entity (entity),
  INDEX idx_brew_vessels_type_status (type, status)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_brew_packaging_lines (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(64) NOT NULL,
  label VARCHAR(255) NOT NULL,
  line_type VARCHAR(32) DEFAULT 'bottle',
  units_per_hour DOUBLE NULL,
  note VARCHAR(255) NULL,
  enabled SMALLINT DEFAULT 1,
  datec DATETIME NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author INT NULL,
  fk_user_modif INT NULL,
  UNIQUE KEY uk_brew_packaging_lines_ref (entity, ref),
  INDEX idx_brew_packaging_lines_entity (entity)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- Optional vessel link on brewsession (idempotent add)
SELECT IF (
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_brewsession' AND COLUMN_NAME='fk_vessel'
  ),
  'SELECT 1',
  'ALTER TABLE llx_brew_brewsession ADD COLUMN fk_vessel INT NULL'
) INTO @stmt; PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS llx_brew_vessel_allocations (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_brewsession INT NOT NULL,
  fk_vessel INT NOT NULL,
  allocated_at DATETIME NULL,
  released_at DATETIME NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_vessel_alloc_entity (entity),
  INDEX idx_brew_vessel_alloc_brewsession (fk_brewsession),
  INDEX idx_brew_vessel_alloc_vessel (fk_vessel)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_brew_vessel_status_log (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_vessel INT NOT NULL,
  status VARCHAR(32) NOT NULL,
  note VARCHAR(255) NULL,
  changed_at DATETIME NOT NULL,
  INDEX idx_brew_vessel_status_entity (entity),
  INDEX idx_brew_vessel_status_vessel_time (fk_vessel, changed_at)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_brew_packline_bookings (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_packaging_line INT NOT NULL,
  fk_packaging INT NULL,
  title VARCHAR(255) NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  note VARCHAR(255) NULL,
  status VARCHAR(32) DEFAULT 'planned',
  INDEX idx_brew_packline_bookings_entity (entity),
  INDEX idx_brew_packline_bookings_line (fk_packaging_line),
  INDEX idx_brew_packline_bookings_time (start_datetime, end_datetime),
  INDEX idx_brew_packline_bookings_pack (fk_packaging)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------
-- UI ADDITIONS: yield/ppg on malts; use_stage/whirlpool/dryhop on hops
-- (safe on MySQL 5.7 via INFORMATION_SCHEMA checks)
-- -------------------------------------------------
SET @tbl='llx_brew_recipe_malts'; SET @col='yield_pct';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_malts ADD COLUMN yield_pct DOUBLE NULL',
 'SELECT "yield_pct exists"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_malts'; SET @col='potential_ppg';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_malts ADD COLUMN potential_ppg DOUBLE NULL',
 'SELECT "potential_ppg exists"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_hops'; SET @col='use_stage';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_hops ADD COLUMN use_stage VARCHAR(32) NULL',
 'SELECT "use_stage exists"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_hops'; SET @col='whirlpool_temp_c';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_hops ADD COLUMN whirlpool_temp_c DOUBLE NULL',
 'SELECT "whirlpool_temp_c exists"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_hops'; SET @col='dryhop_days';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_hops ADD COLUMN dryhop_days DOUBLE NULL',
 'SELECT "dryhop_days exists"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------
-- LINK TO DOLIBARR PRODUCTS (fk_product + product_ref) + indexes
-- -------------------------------------------------
SET @tbl='llx_brew_recipe_malts'; SET @col='fk_product';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_malts ADD COLUMN fk_product INT NULL',
 'SELECT "fk_product exists on llx_brew_recipe_malts"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_malts'; SET @col='product_ref';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_malts ADD COLUMN product_ref VARCHAR(128) NULL',
 'SELECT "product_ref exists on llx_brew_recipe_malts"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_hops'; SET @col='fk_product';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_hops ADD COLUMN fk_product INT NULL',
 'SELECT "fk_product exists on llx_brew_recipe_hops"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_hops'; SET @col='product_ref';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_hops ADD COLUMN product_ref VARCHAR(128) NULL',
 'SELECT "product_ref exists on llx_brew_recipe_hops"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_yeasts'; SET @col='fk_product';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_yeasts ADD COLUMN fk_product INT NULL',
 'SELECT "fk_product exists on llx_brew_recipe_yeasts"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_yeasts'; SET @col='product_ref';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_yeasts ADD COLUMN product_ref VARCHAR(128) NULL',
 'SELECT "product_ref exists on llx_brew_recipe_yeasts"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_extras'; SET @col='fk_product';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_extras ADD COLUMN fk_product INT NULL',
 'SELECT "fk_product exists on llx_brew_recipe_extras"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl='llx_brew_recipe_extras'; SET @col='product_ref';
SET @sql = IF ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col)=0,
 'ALTER TABLE llx_brew_recipe_extras ADD COLUMN product_ref VARCHAR(128) NULL',
 'SELECT "product_ref exists on llx_brew_recipe_extras"'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index helpers (5.7: no IF NOT EXISTS on index)
SELECT IF (
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_recipe_malts' AND INDEX_NAME='idx_brew_malt_fk_product'),
  'SELECT 1',
  'ALTER TABLE llx_brew_recipe_malts ADD INDEX idx_brew_malt_fk_product (fk_product)'
) INTO @stmt; PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF (
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_recipe_hops' AND INDEX_NAME='idx_brew_hop_fk_product'),
  'SELECT 1',
  'ALTER TABLE llx_brew_recipe_hops ADD INDEX idx_brew_hop_fk_product (fk_product)'
) INTO @stmt; PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF (
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_recipe_yeasts' AND INDEX_NAME='idx_brew_yeast_fk_product'),
  'SELECT 1',
  'ALTER TABLE llx_brew_recipe_yeasts ADD INDEX idx_brew_yeast_fk_product (fk_product)'
) INTO @stmt; PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF (
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_recipe_extras' AND INDEX_NAME='idx_brew_extra_fk_product'),
  'SELECT 1',
  'ALTER TABLE llx_brew_recipe_extras ADD INDEX idx_brew_extra_fk_product (fk_product)'
) INTO @stmt; PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- -------------------------------------------------
-- PATCH: fk_bom & fk_mo on brewsession (+ indexes) (idempotent)
-- -------------------------------------------------
SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_brewsession' AND COLUMN_NAME='fk_bom'),
    'SELECT 1',
    'ALTER TABLE llx_brew_brewsession ADD COLUMN fk_bom INT NULL'
) INTO @stmt; PREPARE x FROM @stmt; EXECUTE x; DEALLOCATE PREPARE x;

SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_brewsession' AND COLUMN_NAME='fk_mo'),
    'SELECT 1',
    'ALTER TABLE llx_brew_brewsession ADD COLUMN fk_mo INT NULL'
) INTO @stmt; PREPARE x FROM @stmt; EXECUTE x; DEALLOCATE PREPARE x;

SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_brewsession' AND INDEX_NAME='idx_brew_brewsession_fk_bom'),
    'SELECT 1',
    'ALTER TABLE llx_brew_brewsession ADD INDEX idx_brew_brewsession_fk_bom (fk_bom)'
) INTO @stmt; PREPARE x FROM @stmt; EXECUTE x; DEALLOCATE PREPARE x;

SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=@db AND TABLE_NAME='llx_brew_brewsession' AND INDEX_NAME='idx_brew_brewsession_fk_mo'),
    'SELECT 1',
    'ALTER TABLE llx_brew_brewsession ADD INDEX idx_brew_brewsession_fk_mo (fk_mo)'
) INTO @stmt; PREPARE x FROM @stmt; EXECUTE x; DEALLOCATE PREPARE x;

-- Note: FK-constraints mod BOM/MO er bevidst udeladt for kompatibilitet
-- og bør kun aktiveres hvis tabellerne med sikkerhed findes i jeres Dolibarr.

-- =====================================================================
-- REALIZATION TABLES (forbrug, målinger, step-udførsel, tab/udbytte)
-- =====================================================================

-- A) Materialeforbrug (plan vs. faktisk)
CREATE TABLE IF NOT EXISTS llx_brew_consumption (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_brewsession INT NOT NULL,
  fk_product INT NULL,
  product_ref VARCHAR(128) NULL,
  plan_qty DOUBLE NULL,
  actual_qty DOUBLE NULL,
  unit VARCHAR(16) NULL,              -- kg, g, L, stk, ...
  fk_warehouse INT NULL,
  fk_lot INT NULL,
  posted SMALLINT DEFAULT 0,          -- 1 = bogført til lager
  fk_stock_movement INT NULL,
  note VARCHAR(255) NULL,
  fk_user INT NULL,
  tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_brew_cons_entity (entity),
  INDEX idx_brew_cons_brewsession (fk_brewsession),
  INDEX idx_brew_cons_product (fk_product)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- B) Procesmålinger (timeseries)
CREATE TABLE IF NOT EXISTS llx_brew_measurement (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_brewsession INT NOT NULL,
  stage VARCHAR(32) NOT NULL,         -- mash/lauter/boil/whirlpool/fermentation/packaging
  metric VARCHAR(32) NOT NULL,        -- temp/ph/gravity_sg/pressure/do_ppm/...
  value_numeric DOUBLE NOT NULL,
  unit VARCHAR(16) NULL,              -- °C, pH, SG, bar, ppm, ...
  at DATETIME NOT NULL,
  fk_user INT NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_meas_entity (entity),
  INDEX idx_brew_meas_brewsession (fk_brewsession),
  INDEX idx_brew_meas_stage (stage, metric, at)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- C) Trin-afvikling (plan vs. faktisk)
CREATE TABLE IF NOT EXISTS llx_brew_step_actual (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_brewsession INT NOT NULL,
  type VARCHAR(24) NOT NULL,          -- mash/boil/ferm/carb/pasteur
  name VARCHAR(255) NULL,
  plan_temp_c DOUBLE NULL,
  plan_time_min DOUBLE NULL,
  plan_time_days DOUBLE NULL,
  actual_temp_c DOUBLE NULL,
  actual_time_min DOUBLE NULL,
  actual_time_days DOUBLE NULL,
  started_at DATETIME NULL,
  ended_at DATETIME NULL,
  fk_user INT NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_step_entity (entity),
  INDEX idx_brew_step_brewsession (fk_brewsession, type)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- D) Tab/udbytte (fordampning, trub, deadspace, spild …)
CREATE TABLE IF NOT EXISTS llx_brew_yield_loss (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_brewsession INT NOT NULL,
  loss_type VARCHAR(32) NOT NULL,     -- trub/deadspace/spill/evap/filterloss/other
  volume_l DOUBLE NOT NULL,
  at DATETIME NOT NULL,
  note VARCHAR(255) NULL,
  INDEX idx_brew_loss_entity (entity),
  INDEX idx_brew_loss_brewsession (fk_brewsession)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4;

-- =====================================================================

SET FOREIGN_KEY_CHECKS=1;
