<?php
/**
 * \file    core/modules/modBrewmo.class.php
 * \ingroup brewmo
 * \brief   Module descriptor for BrewMo
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBrewmo extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->modo_id = 500000;
        $this->name = 'Brewmo';
        $this->description = 'BrewMo - Brewery Management & Manufacturing Execution System for Dolibarr';
        $this->version = '2.0.0';
        $this->family = 'other';
        $this->editor_name = 'Rool';
        $this->editor_url = 'https://rool.app';
        $this->special = 0;
        $this->picto = 'generic';

        // Directory name
        $this->module_parts = array(
            'triggers' => 0,
            'css' => array(),
            'js' => array(),
        );

        $this->dirs = array();
        $this->config_page_url = array();

        $this->rights = array();
        $this->rights_class = 'brewmo';

        $r = 0;
        $this->rights[$r][0] = 500001;
        $this->rights[$r][1] = 'Read brewmo objects';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';

        $r++;
        $this->rights[$r][0] = 500002;
        $this->rights[$r][1] = 'Create/Update brewmo objects';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';

        $r++;
        $this->rights[$r][0] = 500003;
        $this->rights[$r][1] = 'Delete brewmo objects';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';

        // Menus
        $this->menu = array();
        $m = 0;

        // Top Menu
        $this->menu[$m] = array(
            'fk_menu' => 0,
            'type' => 'top',
            'titre' => 'BrewMo',
            'mainmenu' => 'brewmo',
            'leftmenu' => '',
            'url' => '/custom/brewmo/www/brewsession_list.php',
            'langs' => 'brewmo@brewmo',
            'position' => 100,
            'enabled' => '$conf->brewmo->enabled',
            'perms' => '$user->rights->brewmo->read',
            'target' => '',
            'user' => 2
        );
        $m++;

        // Left Menu - Brew Sessions
        $this->menu[$m] = array(
            'fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'Brew Sessions',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_sessions',
            'url' => '/custom/brewmo/www/brewsession_list.php',
            'langs' => 'brewmo@brewmo',
            'position' => 100,
            'enabled' => '$conf->brewmo->enabled',
            'perms' => '$user->rights->brewmo->read',
            'target' => '',
            'user' => 2
        );
        $m++;

        // Left Menu - Recipes
        $this->menu[$m] = array(
            'fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'Recipes',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_recipes',
            'url' => '/custom/brewmo/www/recipe_list.php',
            'langs' => 'brewmo@brewmo',
            'position' => 101,
            'enabled' => '$conf->brewmo->enabled',
            'perms' => '$user->rights->brewmo->read',
            'target' => '',
            'user' => 2
        );
        $m++;

        // Left Menu - Tanks
        $this->menu[$m] = array(
            'fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'Tanks / Vessels',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_tanks',
            'url' => '/custom/brewmo/www/tank_list.php',
            'langs' => 'brewmo@brewmo',
            'position' => 102,
            'enabled' => '$conf->brewmo->enabled',
            'perms' => '$user->rights->brewmo->read',
            'target' => '',
            'user' => 2
        );
        $m++;

        // Left Menu - MRP
        $this->menu[$m] = array(
            'fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'MRP Calculation',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_mrp',
            'url' => '/custom/brewmo/www/mrp_overview.php',
            'langs' => 'brewmo@brewmo',
            'position' => 103,
            'enabled' => '$conf->brewmo->enabled',
            'perms' => '$user->rights->brewmo->read',
            'target' => '',
            'user' => 2
        );
        $m++;

        // Left Menu - Packaging Labels
        $this->menu[$m] = array(
            'fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'Packaging Labels',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_labels',
            'url' => '/custom/brewmo/www/packaging_labels.php',
            'langs' => 'brewmo@brewmo',
            'position' => 104,
            'enabled' => '$conf->brewmo->enabled',
            'perms' => '$user->rights->brewmo->read',
            'target' => '',
            'user' => 2
        );
    }

    public function init($options = '')
    {
        $sql = array(
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_session_v2 (
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
                CONSTRAINT uk_brew_session_ref UNIQUE (entity, ref)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_recipe (
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
                CONSTRAINT uk_brew_recipe_ref UNIQUE (entity, ref)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_vessel (
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
                CONSTRAINT uk_brew_vessel_ref UNIQUE (entity, ref)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_qc_log (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                fk_brew_session INT NOT NULL,
                measurement_type VARCHAR(32) NOT NULL,
                val DECIMAL(10,4) NOT NULL,
                unit VARCHAR(16) NOT NULL,
                recorded_by VARCHAR(128) NOT NULL,
                recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_container (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
