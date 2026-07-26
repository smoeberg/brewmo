-- BrewMo 2.0 Database Schema Migration
-- Defines core tables for DDD entities: Brew Sessions, State History, QC Logs, Containers, Yeast Batches

CREATE TABLE IF NOT EXISTS llx_brew_session_v2 (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(64) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    fk_recipe INT NOT NULL,
    state VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
    planned_volume_liters DOUBLE(10,2) NOT NULL,
    fk_vessel INT DEFAULT NULL,
    lot_number VARCHAR(128) DEFAULT NULL,
    date_start DATETIME DEFAULT NULL,
    date_end DATETIME DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_brew_session_state (state),
    INDEX idx_brew_session_recipe (fk_recipe),
    INDEX idx_brew_session_lot (lot_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_brew_qc_log (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_brew_session INT NOT NULL,
    measurement_type VARCHAR(32) NOT NULL,
    measurement_value DOUBLE(10,4) NOT NULL,
    unit VARCHAR(16) NOT NULL,
    recorded_at DATETIME NOT NULL,
    notes TEXT DEFAULT NULL,
    sensor_id VARCHAR(64) DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_brew_session) REFERENCES llx_brew_session_v2(rowid) ON DELETE CASCADE,
    INDEX idx_qc_session (fk_brew_session),
    INDEX idx_qc_sensor (sensor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_brew_container (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(128) NOT NULL UNIQUE,
    container_type VARCHAR(64) NOT NULL, -- Keg 30L, Keg 50L, Cask, etc.
    status VARCHAR(32) NOT NULL DEFAULT 'IN_BREWERY',
    fk_current_location_thirdparty INT DEFAULT NULL,
    fk_current_brew_session INT DEFAULT NULL,
    date_last_cleaned DATETIME DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_container_barcode (barcode),
    INDEX idx_container_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
