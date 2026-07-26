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
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
