<?php
// BrewMo main module descriptor for Dolibarr

if (!defined('DOL_DOCUMENT_ROOT')) {
    define('DOL_DOCUMENT_ROOT', dirname(__FILE__, 6));
}
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBrewmo extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        $this->numero       = 590123;
        $this->rights_class = 'brewmo';
        $this->family       = 'other';
        $this->name         = 'Brewmo';
        $this->description  = 'Brewery management (recipes, batches, tanks, packaging, MRP)';
        $this->version      = '0.7.0';

        $this->const_name   = 'MAIN_MODULE_BREWMO';
        $this->picto        = 'generic';

        $this->module_position = 90;
        $this->dirs = array('/brewmo');

        // Opdateret til Dolibarr 23
        $this->need_dolibarr_version = array(17, 0, -1); // Ændret fra 16 til 17 (Dolibarr 23 bruger version 17+)
        $this->phpmin = array(7, 4, 0);
        $this->need_php = array(7, 4, 0); // Tilføjet - nyere format i v23

        $this->langfiles = array('brewmo@brewmo');

        // Opdateret modul-dele (nye nøgler i v23)
        $this->module_parts = array(
            'triggers' => 1,
            'hooks'    => array(),
            'css'      => array(), // Tilføjet - til egne stylesheets
            'js'       => array(), // Tilføjet - til egne JavaScript-filer
            'models'   => array(), // Tilføjet - til PDF-modeller
            'tpl'      => array()  // Tilføjet - til skabeloner
        );

        // Konfigurationssider (opdateret format)
        $this->config_page_url = array('setup.php@brewmo');

        // Rights - beholdt samme struktur, men tilføjet ekstra sikkerhed
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 59012301;
        $this->rights[$r][1] = 'Read Brewmo';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1; // 1 = permission by default
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 59012302;
        $this->rights[$r][1] = 'Write Brewmo';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = 59012303;
        $this->rights[$r][1] = 'Admin Brewmo';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'admin';
        $r++;

        // Menus - opdateret til Dolibarr 23 standard
        $this->menu = array();
        $r = 0;

        // Top menu
        $this->menu[$r] = array(
            'type'     => 'top',
            'titre'    => 'Brewmo',
            'mainmenu' => 'brewmo',
            'leftmenu' => '',
            'url'      => '/brewmo/www/brewsession_list.php',
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
            'url'      => '/brewmo/www/recipe_list.php',
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
            'url'      => '/brewmo/www/brewsession_list.php',
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
            'url'      => '/brewmo/www/tank_list.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 30,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;

        // Labels / QR
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=brewmo',
            'type'     => 'left',
            'titre'    => 'Etiketter / QR',
            'mainmenu' => 'brewmo',
            'leftmenu' => 'brewmo_labels',
            'url'      => '/brewmo/www/packaging_labels.php',
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
            'url'      => '/brewmo/www/mrp_overview.php',
            'langs'    => 'brewmo@brewmo',
            'position' => 50,
            'enabled'  => '$conf->brewmo->enabled',
            'perms'    => '$user->rights->brewmo->read || $user->admin',
            'target'   => '',
            'user'     => 2
        );
        $r++;
    }

    /**
     * Initialiser modulet (opretter tabeller, konfigurationer, etc.)
     * 
     * @param   string  $options    Options
     * @return  int                 1 hvis success, 0 hvis fejl
     */
    public function init($options = '')
    {
        global $db, $langs;
        
        // SQL til at oprette BrewMo-tabeller
        $sql = array();
        
        // Eksempel: Opret brewsession-tabel
        $sql[] = "CREATE TABLE IF NOT EXISTS llx_brewmo_brewsession (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            ref varchar(30) NOT NULL,
            recipe_id integer NOT NULL,
            tank_id integer,
            batch_number varchar(50),
            status varchar(30) DEFAULT 'draft',
            planned_start_date datetime,
            actual_start_date datetime,
            fermentation_end_date datetime,
            conditioning_end_date datetime,
            packaging_date datetime,
            volume_planned decimal(10,2),
            volume_actual decimal(10,2),
            og decimal(5,3),
            fg decimal(5,3),
            abv decimal(4,2),
            ibu integer,
            ebc integer,
            ph decimal(3,2),
            note text,
            entity integer DEFAULT 1,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            date_modification datetime,
            fk_user_creat integer,
            fk_user_modif integer,
            import_key varchar(14),
            status_import integer DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        // Eksempel: Opret recipe-tabel
        $sql[] = "CREATE TABLE IF NOT EXISTS llx_brewmo_recipe (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            ref varchar(30) NOT NULL,
            name varchar(255) NOT NULL,
            style varchar(100),
            description text,
            target_volume decimal(10,2),
            boil_time integer,
            fermentation_temp decimal(4,1),
            carbonation decimal(4,1),
            og decimal(5,3),
            fg decimal(5,3),
            abv decimal(4,2),
            ibu integer,
            ebc integer,
            ph decimal(3,2),
            instructions text,
            entity integer DEFAULT 1,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            date_modification datetime,
            fk_user_creat integer,
            fk_user_modif integer,
            import_key varchar(14),
            status_import integer DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        // Kald _init med SQL-sætningerne
        return $this->_init($sql, $options);
    }

    /**
     * Afinstaller modulet (fjerner tabeller, konfigurationer, etc.)
     * 
     * @param   string  $options    Options
     * @return  int                 1 hvis success, 0 hvis fejl
     */
    public function remove($options = '')
    {
        global $db, $langs;
        
        // SQL til at slette BrewMo-tabeller
        $sql = array();
        $sql[] = "DROP TABLE IF EXISTS llx_brewmo_brewsession;";
        $sql[] = "DROP TABLE IF EXISTS llx_brewmo_recipe;";
        // Tilføj flere tabeller efter behov
        
        // Kald _remove med SQL-sætningerne
        return $this->_remove($sql, $options);
    }
}
