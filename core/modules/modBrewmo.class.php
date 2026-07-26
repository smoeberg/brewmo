<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBrewmo extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 500100;
        $this->rights_class = 'brewmo';
        $this->family = "interface";
        $this->module_position = '50';
        $this->name = preg_replace('/^mod/i', '', __CLASS__);
        $this->description = "BrewMo 2.0 Brewery Management & Production Module";
        $this->version = '2.0.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';

        $this->module_parts = array(
            'triggers' => 1,
            'css' => array(),
            'js' => array(),
        );

        $this->dirs = array("/brewmo");

        $this->config_page_url = array("setup.php@brewmo");

        $this->langfiles = array("brewmo@brewmo");

        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 500101;
        $this->rights[$r][1] = 'Read brewmo data';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 500102;
        $this->rights[$r][1] = 'Create/Update brewmo data';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = 500103;
        $this->rights[$r][1] = 'Delete brewmo data';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';
        $r++;

        // Menus - Using Dolibarr standard custom module pathing
        $this->menu = array();
        $r = 0;

        // Top menu
        $this->menu[$r] = array(
            'type'     => 'top',
            'titre'    => 'Brewmo',
            'mainmenu' => 'brewmo',
            'leftmenu' => '',
            'url'      => '/custom/brewmo/www/brewsession_list.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 100,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;

        // Recipes
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=brewmo',
            'type'     => 'left',
            'titre'    => 'Opskrifter',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_recipes',
            'url'      => '/custom/brewmo/www/recipe_list.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 10,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;

        // Batches
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=brewmo',
            'type'     => 'left',
            'titre'    => 'Batches / bryg',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_batches',
            'url'      => '/custom/brewmo/www/brewsession_list.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 20,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;

        // Tanks
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=brewmo',
            'type'     => 'left',
            'titre'    => 'Tanke / fermentorer',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_tanks',
            'url'      => '/custom/brewmo/www/tank_list.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 30,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;

        // Labels
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=brewmo',
            'type'     => 'left',
            'titre'    => 'Etiketter / QR',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_labels',
            'url'      => '/custom/brewmo/www/packaging_labels.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 40,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;

        // MRP
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=brewmo',
            'type'     => 'left',
            'titre'    => 'MRP',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_mrp',
            'url'      => '/custom/brewmo/www/mrp_overview.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 50,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;
    }

    public function init($options = '')
    {
        $sql = array(
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_session_v2 (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                ref VARCHAR(64) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                recipe_id INT NULL,
                vessel_id INT NULL,
                state VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
                planned_volume_liters DOUBLE(24,8) NOT NULL DEFAULT 0,
                lot_number VARCHAR(64) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_recipe (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                ref VARCHAR(64) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                target_batch_size DOUBLE(24,8) NOT NULL DEFAULT 0,
                og DOUBLE(24,8) NOT NULL DEFAULT 1.050,
                fg DOUBLE(24,8) NOT NULL DEFAULT 1.010,
                ibu DOUBLE(24,8) NOT NULL DEFAULT 0,
                ebc DOUBLE(24,8) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_qc_log (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                brew_session_id INT NOT NULL,
                measurement_type VARCHAR(32) NOT NULL,
                value DOUBLE(24,8) NOT NULL,
                unit VARCHAR(16) NOT NULL,
                recorded_by VARCHAR(128) NOT NULL,
                recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_container (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                barcode VARCHAR(128) NOT NULL UNIQUE,
                container_type VARCHAR(32) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'IN_BREWERY',
                current_location VARCHAR(255) NULL,
                thirdparty_id INT NULL,
                last_scanned_at DATETIME NULL
            ) ENGINE=InnoDB;"
        );
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "brew_vessel (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                ref VARCHAR(64) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(32) NOT NULL,
                capacity_liters DOUBLE(24,8) NOT NULL DEFAULT 0,
                is_clean TINYINT(1) NOT NULL DEFAULT 1,
                is_occupied TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;"
