-- BrewMo module install SQL (Dolibarr 16+ compatible, tested for v23)

CREATE TABLE IF NOT EXISTS llx_brew_recipes (
  rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity          INTEGER NOT NULL DEFAULT 1,
  ref             VARCHAR(128) NOT NULL,
  label           VARCHAR(255) NOT NULL,
  fk_product      INTEGER NULL,
  abv             DOUBLE NULL,
  ibu             DOUBLE NULL,
  color_ebc       DOUBLE NULL,
  og_sg           DOUBLE NULL,
  fg_sg           DOUBLE NULL,
  batch_volume_l  DOUBLE NULL,
  description     TEXT NULL,
  datec           DATETIME NULL,
  tms             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_brew_recipes_entity (entity),
  INDEX idx_brew_recipes_ref (ref)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_recipe_malt (
  rowid       INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity      INTEGER NOT NULL DEFAULT 1,
  fk_recipe   INTEGER NOT NULL,
  fk_product  INTEGER NOT NULL,
  qty_kg      DOUBLE NOT NULL,
  note        TEXT NULL,
  tms         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_malt_fk_recipe (fk_recipe)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_recipe_hop (
  rowid       INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity      INTEGER NOT NULL DEFAULT 1,
  fk_recipe   INTEGER NOT NULL,
  fk_product  INTEGER NOT NULL,
  qty_g       DOUBLE NOT NULL,
  use_phase   VARCHAR(32) DEFAULT 'boil',
  time_min    DOUBLE DEFAULT 0,
  note        TEXT NULL,
  tms         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_hop_fk_recipe (fk_recipe)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_recipe_yeast (
  rowid       INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity      INTEGER NOT NULL DEFAULT 1,
  fk_recipe   INTEGER NOT NULL,
  fk_product  INTEGER NOT NULL,
  qty_g       DOUBLE NOT NULL,
  note        TEXT NULL,
  tms         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_yeast_fk_recipe (fk_recipe)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_recipe_extra (
  rowid       INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity      INTEGER NOT NULL DEFAULT 1,
  fk_recipe   INTEGER NOT NULL,
  fk_product  INTEGER NOT NULL,
  qty_unit    DOUBLE NOT NULL,
  note        TEXT NULL,
  tms         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_extra_fk_recipe (fk_recipe)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_brewsession (
  rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity          INTEGER NOT NULL DEFAULT 1,
  ref             VARCHAR(128) NOT NULL,
  fk_recipe       INTEGER NOT NULL,
  fk_tank         INTEGER NULL,
  status          SMALLINT DEFAULT 0,
  volume_l        DOUBLE NULL,
  note_public     TEXT NULL,
  note_private    TEXT NULL,
  datec           DATETIME NULL,
  date_start      DATETIME NULL,
  date_end        DATETIME NULL,
  tms             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_brew_brewsession_entity (entity),
  INDEX idx_brew_brewsession_ref (ref),
  INDEX idx_brew_brewsession_recipe (fk_recipe),
  INDEX idx_brew_brewsession_tank (fk_tank)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_brewsession_mash (
  rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity          INTEGER NOT NULL DEFAULT 1,
  fk_brewsession  INTEGER NOT NULL,
  step_order      INTEGER NOT NULL,
  name            VARCHAR(255) NULL,
  target_temp_c   DOUBLE NOT NULL,
  time_min        DOUBLE NOT NULL,
  actual_temp_c   DOUBLE NULL,
  started_at      DATETIME NULL,
  finished_at     DATETIME NULL,
  tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_mash_fk_brewsession (fk_brewsession)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_brewsession_ferm (
  rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity          INTEGER NOT NULL DEFAULT 1,
  fk_brewsession  INTEGER NOT NULL,
  name            VARCHAR(255),
  temp_c          DOUBLE DEFAULT 0,
  time_days       DOUBLE DEFAULT 0,
  note            TEXT NULL,
  sg_reading      DOUBLE NULL,
  logged_at       DATETIME NULL,
  tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_ferm_fk_brewsession (fk_brewsession)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_tank (
  rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity          INTEGER NOT NULL DEFAULT 1,
  ref             VARCHAR(64) NOT NULL,
  label           VARCHAR(255) NULL,
  capacity_l      DOUBLE NOT NULL,
  tank_type       VARCHAR(32) DEFAULT 'fermenter',
  location        VARCHAR(255) NULL,
  is_active       INTEGER NOT NULL DEFAULT 1,
  note_public     TEXT NULL,
  note_private    TEXT NULL,
  tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_brew_tank_entity (entity),
  INDEX idx_brew_tank_ref (ref)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_brew_packaging (
  rowid        INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity       INTEGER NOT NULL DEFAULT 1,
  fk_session   INTEGER NOT NULL,
  fk_product   INTEGER NOT NULL,
  fk_warehouse INTEGER NOT NULL,
  qty          DOUBLE NOT NULL,
  lot          VARCHAR(64) NULL,
  datem        DATETIME NULL,
  tms          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brew_packaging_session (fk_session),
  INDEX idx_brew_packaging_product (fk_product)
) ENGINE=innodb;
