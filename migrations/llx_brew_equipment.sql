-- =====================================================================
-- Brewmo: Produktionsudstyr
-- =====================================================================

CREATE TABLE IF NOT EXISTS llx_brew_equipment (
  rowid              INT AUTO_INCREMENT PRIMARY KEY,
  entity             INT NOT NULL DEFAULT 1,

  ref                VARCHAR(128) NOT NULL,
  label              VARCHAR(255) NOT NULL,
  note               TEXT NULL,

  efficiency_pct     DOUBLE NULL,
  boiloff_l_h        DOUBLE NULL,
  trub_loss_l        DOUBLE NULL,
  lauter_deadspace_l DOUBLE NULL,
  mash_tun_l         DOUBLE NULL,
  kettle_l           DOUBLE NULL,

  enabled            TINYINT NOT NULL DEFAULT 1,

  datec              DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  fk_user_creat      INT NULL,
  fk_user_modif      INT NULL,

  INDEX idx_brew_eq_ref (ref),
  INDEX idx_brew_eq_entity (entity)
) ENGINE=innodb DEFAULT CHARSET=utf8mb4;
