-- BrewMo 2.0 Database Schema & Migrations
-- Strict relational integrity, foreign keys, constraints, and audit fields.

-- 1. Recipe Master Table
CREATE TABLE IF NOT EXISTS llx_brew_recipe (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    ref VARCHAR(64) NOT NULL,
    title VARCHAR(255) NOT NULL,
    target_batch_size DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    og DECIMAL(6,3) NOT NULL DEFAULT 1.050,
    fg DECIMAL(6,3) NOT NULL DEFAULT 1.010,
    ibu DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    ebc DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    datec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat INT NULL,
    fk_user_modif INT NULL,
    CONSTRAINT uk_brew_recipe_ref UNIQUE (entity, ref),
    CONSTRAINT chk_recipe_batch_size CHECK (target_batch_size >= 0),
    CONSTRAINT chk_recipe_og CHECK (og >= 0.900 AND og <= 1.300),
    CONSTRAINT chk_recipe_fg CHECK (fg >= 0.800 AND fg <= 1.200),
    CONSTRAINT chk_recipe_ibu CHECK (ibu >= 0),
    CONSTRAINT chk_recipe_ebc CHECK (ebc >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Recipe Ingredients (Malt, Hops, Yeast, Extra)
CREATE TABLE IF NOT EXISTS llx_brew_recipe_ingredient (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_recipe INT NOT NULL,
    fk_product INT NULL,
    ingredient_type VARCHAR(32) NOT NULL,
    name VARCHAR(255) NOT NULL,
    amount DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(16) NOT NULL DEFAULT 'kg',
    CONSTRAINT fk_recipe_ing_recipe FOREIGN KEY (fk_recipe) REFERENCES llx_brew_recipe(rowid) ON DELETE CASCADE,
    CONSTRAINT chk_recipe_ing_amount CHECK (amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Vessel / Tank Table
CREATE TABLE IF NOT EXISTS llx_brew_vessel (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    ref VARCHAR(64) NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'FERMENTER',
    capacity_liters DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_clean TINYINT(1) NOT NULL DEFAULT 1,
    is_occupied TINYINT(1) NOT NULL DEFAULT 0,
    datec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_brew_vessel_ref UNIQUE (entity, ref),
    CONSTRAINT chk_vessel_capacity CHECK (capacity_liters >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Brew Session V2 Table
CREATE TABLE IF NOT EXISTS llx_brew_session_v2 (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    ref VARCHAR(64) NOT NULL,
    title VARCHAR(255) NOT NULL,
    fk_recipe INT NULL,
    fk_vessel INT NULL,
    state VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
    planned_volume_liters DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    lot_number VARCHAR(64) NULL,
    datec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat INT NULL,
    fk_user_modif INT NULL,
    CONSTRAINT uk_brew_session_ref UNIQUE (entity, ref),
    CONSTRAINT fk_session_recipe FOREIGN KEY (fk_recipe) REFERENCES llx_brew_recipe(rowid) ON DELETE SET NULL,
    CONSTRAINT fk_session_vessel FOREIGN KEY (fk_vessel) REFERENCES llx_brew_vessel(rowid) ON DELETE SET NULL,
    CONSTRAINT chk_session_volume CHECK (planned_volume_liters >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Quality Control & Sensor Log
CREATE TABLE IF NOT EXISTS llx_brew_qc_log (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_brew_session INT NOT NULL,
    measurement_type VARCHAR(32) NOT NULL,
    val DECIMAL(10,4) NOT NULL,
    unit VARCHAR(16) NOT NULL,
    recorded_by VARCHAR(128) NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qc_session FOREIGN KEY (fk_brew_session) REFERENCES llx_brew_session_v2(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Container / Keg Tracking
CREATE TABLE IF NOT EXISTS llx_brew_container (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    barcode VARCHAR(128) NOT NULL,
    container_type VARCHAR(32) NOT NULL DEFAULT 'KEG_30L',
    status VARCHAR(32) NOT NULL DEFAULT 'IN_BREWERY',
    current_location VARCHAR(255) NULL,
    thirdparty_id INT NULL,
    last_scanned_at DATETIME NULL,
    datec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_brew_container_barcode UNIQUE (entity, barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for high-performance joins and queries
CREATE INDEX idx_brew_session_entity_state ON llx_brew_session_v2 (entity, state);
CREATE INDEX idx_brew_recipe_entity ON llx_brew_recipe (entity);
CREATE INDEX idx_brew_vessel_entity_status ON llx_brew_vessel (entity, is_occupied, is_clean);
CREATE INDEX idx_brew_qc_session_type ON llx_brew_qc_log (fk_brew_session, measurement_type);
