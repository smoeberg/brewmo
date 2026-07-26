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

        $this->need_dolibarr_version = array(16, 0, -1);
        $this->phpmin = array(7, 4, 0);

        $this->langfiles = array('brewmo@brewmo');

        $this->module_parts = array(
            'triggers' => 1,
            'hooks'    => array()
        );

        $this->config_page_url = array('setup.php@brewmo');

        // Rights
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 59012301;
        $this->rights[$r][1] = 'Read Brewmo';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
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

        // Menus
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

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
