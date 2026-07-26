/* ===============================================================
   BREWMO – Migration v1
   Kræver MySQL/MariaDB med IF NOT EXISTS på ADD COLUMN (8.0+/10.0+)
   Kør i den samme database som Dolibarr.
   =============================================================== */

/* ---------------------------------------------------------------
   1) Udvid brygsession med nødvendige felter
   --------------------------------------------------------------- */
ALTER TABLE llx_brew_brewsession
  ADD COLUMN IF NOT EXISTS realized_volume_l   DOUBLE        NULL,
  ADD COLUMN IF NOT EXISTS production_cost     DOUBLE        NULL,
  ADD COLUMN IF NOT EXISTS status              SMALLINT      NOT NULL DEFAULT 0,  -- 0=draft,1=started,2=finished
  ADD COLUMN IF NOT EXISTS started_at          DATETIME      NULL,
  ADD COLUMN IF NOT EXISTS finished_at         DATETIME      NULL;

ALTER TABLE llx_brew_brewsession
  ADD INDEX IF NOT EXISTS idx_brewsession_status    (status),
  ADD INDEX IF NOT EXISTS idx_brewsession_started   (started_at),
  ADD INDEX IF NOT EXISTS idx_brewsession_finished  (finished_at);

/* ---------------------------------------------------------------
   2) Forbrugslinjer (kladde/posteret) m. lot/lager-reference
   Bruges af Råvareforbrug-sektionen
   --------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS llx_brew_consumption (
  rowid             INT AUTO_INCREMENT PRIMARY KEY,
  entity            INT            NOT NULL DEFAULT 1,
  fk_brewsession    INT            NOT NULL,
  fk_product        INT            DEFAULT NULL,
  product_ref       VARCHAR(128)   DEFAULT NULL,
  plan_qty          DOUBLE         DEFAULT NULL,
  actual_qty        DOUBLE         DEFAULT NULL,
  unit              VARCHAR(16)    DEFAULT NULL,
  fk_warehouse      INT            DEFAULT NULL,
  fk_lot            INT            DEFAULT NULL,  -- peger mod llx_product_batch.rowid, hvis relevant
  posted            TINYINT        NOT NULL DEFAULT 0,
  fk_stock_movement INT            DEFAULT NULL,  -- peger mod llx_stock_mouvement.rowid
  note              TEXT           DEFAULT NULL,
  fk_user           INT            DEFAULT NULL,
  datec             DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bc_session (fk_brewsession),
  INDEX idx_bc_product (fk_product),
  INDEX idx_bc_posted  (posted),
  CONSTRAINT fk_bc_session FOREIGN KEY (fk_brewsession) REFERENCES llx_brew_brewsession(rowid) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---------------------------------------------------------------
   3) Målinger (temp/SG/pH/tryk osv.)
   Bruges af Målinger-sektionen
   --------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS llx_brew_measurement (
  rowid           INT AUTO_INCREMENT PRIMARY KEY,
  entity          INT           NOT NULL DEFAULT 1,
  fk_brewsession  INT           NOT NULL,
  stage           VARCHAR(32)   NOT NULL,         -- mash/boil/fermentation/...
  metric          VARCHAR(32)   NOT NULL,         -- temp/ph/gravity_sg/pressure/do_ppm
  value_numeric   DOUBLE        NOT NULL,
  unit            VARCHAR(16)   DEFAULT NULL,     -- °C, pH, SG, bar, ppm ...
  at              DATETIME      NOT NULL,
  fk_user         INT           DEFAULT NULL,
  note            TEXT          DEFAULT NULL,
  datec           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bm_session (fk_brewsession),
  INDEX idx_bm_at      (at),
  CONSTRAINT fk_bm_session FOREIGN KEY (fk_brewsession) REFERENCES llx_brew_brewsession(rowid) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---------------------------------------------------------------
   4) Faktiske proces-trin (start/stop, temperatur, tid)
   Bruges af Trin-sektionen
   --------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS llx_brew_step_actual (
  rowid             INT AUTO_INCREMENT PRIMARY KEY,
  entity            INT           NOT NULL DEFAULT 1,
  fk_brewsession    INT           NOT NULL,
  type              VARCHAR(32)   NOT NULL,       -- mash/boil/ferm/carb/pasteur/...
  name              VARCHAR(128)  DEFAULT NULL,
  plan_temp_c       DOUBLE        DEFAULT NULL,
  plan_time_min     DOUBLE        DEFAULT NULL,
  plan_time_days    DOUBLE        DEFAULT NULL,
  started_at        DATETIME      DEFAULT NULL,
  ended_at          DATETIME      DEFAULT NULL,
  actual_temp_c     DOUBLE        DEFAULT NULL,
  actual_time_min   DOUBLE        DEFAULT NULL,
  actual_time_days  DOUBLE        DEFAULT NULL,
  fk_user           INT           DEFAULT NULL,
  datec             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bsa_session (fk_brewsession),
  INDEX idx_bsa_type    (type),
  CONSTRAINT fk_bsa_session FOREIGN KEY (fk_brewsession) REFERENCES llx_brew_brewsession(rowid) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---------------------------------------------------------------
   5) Tab/udbytte (spild/fordampning/dødvolumen mv.)
   Bruges af Tab/udbytte-sektionen
   --------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS llx_brew_yield_loss (
  rowid           INT AUTO_INCREMENT PRIMARY KEY,
  entity          INT           NOT NULL DEFAULT 1,
  fk_brewsession  INT           NOT NULL,
  loss_type       VARCHAR(32)   NOT NULL,       -- trub/deadspace/spill/evap/filterloss/other
  volume_l        DOUBLE        NOT NULL,
  at              DATETIME      NOT NULL,
  note            TEXT          DEFAULT NULL,
  datec           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_byl_session (fk_brewsession),
  INDEX idx_byl_at      (at),
  CONSTRAINT fk_byl_session FOREIGN KEY (fk_brewsession) REFERENCES llx_brew_brewsession(rowid) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---------------------------------------------------------------
   6) Link: Brygsession -> leverandørordrelinjer (PO)
   Gør det muligt at kræve “modtaget” før produktion kan starte.
   --------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS llx_brew_session_po (
  rowid        INT AUTO_INCREMENT PRIMARY KEY,
  fk_session   INT NOT NULL,           -- llx_brew_brewsession.rowid
  fk_cfd       INT NOT NULL,           -- llx_commande_fournisseurdet.rowid
  UNIQUE KEY uk_session_cfd (fk_session, fk_cfd),
  INDEX idx_bspo_session (fk_session),
  CONSTRAINT fk_bspo_session FOREIGN KEY (fk_session)
      REFERENCES llx_brew_brewsession(rowid) ON DELETE CASCADE
  /* OBS: Fremmednøgle til llx_commande_fournisseurdet kan udelades hvis jeres DB ikke har FK på leverandørordrer.
     Hvis den må bruges, fjern kommentarerne herunder:
  , CONSTRAINT fk_bspo_cfd FOREIGN KEY (fk_cfd)
      REFERENCES llx_commande_fournisseurdet(rowid) ON DELETE CASCADE
  */
) ENGINE=InnoDB;

/* ---------------------------------------------------------------
   7) (VALGFRIT) Konstanter til standardlagre og færdigvare
   – Tilpas entity (typisk 1) og værdier før kørsel.
   --------------------------------------------------------------- */
/* 
INSERT INTO llx_const (name, value, type, visible, note, entity)
VALUES 
('BREWMO_DEFAULT_RAW_WAREHOUSE',      '1', 'chaine', 1, 'Standard råvarelager (entrepot rowid)', 1),
('BREWMO_DEFAULT_FINISHED_WAREHOUSE', '2', 'chaine', 1, 'Standard færdigvarelager (entrepot rowid)', 1),
('BREWMO_FINISHED_PRODUCT_ID',        '123', 'chaine', 1, 'Færdigvare produkt-id', 1),
('BREWMO_UNITS_PER_L',                '1.0', 'chaine', 1, 'Enheder pr. liter (fx flasker/L)', 1),
('BREWMO_REQUIRE_LOT_HOPS',           '1', 'chaine', 1, 'Kræv lot for humle', 1),
('BREWMO_REQUIRE_LOT_YEAST',          '1', 'chaine', 1, 'Kræv lot for gær', 1),
('BREWMO_CAT_HOPS',                   '45', 'chaine', 1, 'Kategori-id for humle', 1),
('BREWMO_CAT_YEAST',                  '46', 'chaine', 1, 'Kategori-id for gær', 1);
*/
